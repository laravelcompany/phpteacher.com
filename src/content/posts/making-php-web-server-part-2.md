---
title: "Making Our Own Web Server in PHP - Part 2: Routes and Middleware"
description: "Build routes and middleware for your PHP web server. Part 2 of creating a custom HTTP server from scratch in PHP."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-11-16 21:00:00"
tags: ["PHP", "Web Server", "HTTP", "Routing", "Middleware"]
---

PHP ships with a development server, but it's single-threaded and not meant for production. Last month we built a basic single-connection server. This month we add concurrent connection handling, routing, and PSR-7 request/response support.

## Handling Concurrent Connections

The standard PHP server blocks on a single connection until it finishes. C's `select()` method — exposed as `socket_select()` in PHP — lets us monitor multiple sockets simultaneously.

Setup is similar to a basic server:

```php
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, '0.0.0.0', 8080);
socket_listen($socket);
```

`SO_REUSEADDR` lets you kill and restart immediately without waiting 30-120 seconds for the address to release.

### The Main Loop

`socket_select()` takes three arrays: sockets to read, sockets to write, and sockets with exceptions. We start with one socket to read — the one we created:

```php
$originalReadSockets = [$socket];
$writeSockets = [];
$exceptSockets = [];
```

```php
use Laminas\Diactoros\Request\Serializer as ReqSerializer;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\Serializer as ResSerializer;

while (true) {
    $readSockets = $originalReadSockets;
    $numChanged = socket_select(
        $readSockets, $writeSockets,
        $exceptSockets, 0
    );
    if ($numChanged === false) { continue; }
    if ($numChanged === 0) { continue; }

    foreach ($exceptSockets as $badSocket) {
        echo 'Closing bad socket' . PHP_EOL;
        socket_close($badSocket);
        $unset = array_search($badSocket, $readSockets);
        unset($readSockets[$unset]);
        $unset = array_search($badSocket, $exceptSockets);
        unset($exceptSockets[$unset]);
    }

    if (in_array($socket, $readSockets)) {
        $newSocket = socket_accept($socket);
        $originalReadSockets[] = $newSocket;
        $unset = array_search($socket, $readSockets);
        unset($readSockets[$unset]);
    }

    foreach ($readSockets as $curSocket) {
        $data = socket_read($curSocket, 1024);
        if ($data === false) {
            $unset = array_search($curSocket, $originalReadSockets);
            unset($originalReadSockets[$unset]);
            continue;
        }

        $data = trim($data);
        if (! empty($data)) {
            $request = ReqSerializer::fromString($data);
            $response = (new Response())->withStatus(200);
            $msg = 'You requested ' . $request->getUriString()
                . ' with verb ' . $request->getMethod();
            $response->getBody()->write($msg);
            $responseString = ResSerializer::toString($response);

            socket_write($curSocket, $responseString);
            socket_close($curSocket);
            $unset = array_search($curSocket, $originalReadSockets);
            unset($originalReadSockets[$unset]);
        }
    }
}
```

The loop resets the read sockets to the original list each iteration, ensuring the main socket is always monitored. New connections get added to `$originalReadSockets`. Error sockets get cleaned up.

## Executing Custom Code with Routing

Instead of hardcoded responses, match request URIs to callable handlers. This uses the Action-Domain-Responder pattern with invokable classes:

```php
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Request\Serializer as ReqSerializer;
use Laminas\Diactoros\Response\Serializer as ResSerializer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

function processRequest(RequestInterface $request): ResponseInterface
{
    $paths = ['/' => HomepageAction::class];

    if (in_array($request->getUri()->getPath(), $paths)) {
        $class = $paths[$request->getUri()->getPath()];
        return (new $class())($request);
    }

    return (new FourOFourAction())($request);
}

class HomepageAction
{
    function __invoke(RequestInterface $request): ResponseInterface
    {
        $response = (new Response())->withStatus(200);
        $response->getBody()->write('<h1>Hello World!</h1>');
        return $response;
    }
}

class FourOFourAction
{
    function __invoke(RequestInterface $request): ResponseInterface
    {
        $response = (new Response())->withStatus(404);
        $response->getBody()->write('<h1>Not Found!</h1>');
        return $response;
    }
}

// In the main loop body:
$request = ReqSerializer::fromString($data);
$response = processRequest($request);
$responseString = ResSerializer::toString($response);
socket_write($curSocket, $responseString);
```

The `processRequest()` function becomes your router. Each handler receives a PSR-7 request and returns a PSR-7 response. This lets you use any PSR-15 middleware or PSR-7 compatible library.

### PSR-7 Middleware Pipeline

Since handlers return PSR-7 responses, you can layer middleware for logging, authentication, CORS, and more before the request reaches your handler. The middleware pattern wraps the handler:

```php
interface MiddlewareInterface
{
    public function process(
        RequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface;
}
```

Each middleware either returns a response (short-circuiting the pipeline) or delegates to the next handler.

## Static File Serving

When the requested path matches a file in your document root, read and return it:

```php
public function serveStaticFile(string $docRoot, string $path): ?ResponseInterface
{
    $filePath = $docRoot . '/' . ltrim($path, '/');

    if (! file_exists($filePath) || is_dir($filePath)) {
        return null;
    }

    $response = new Response();
    $response->getBody()->write(file_get_contents($filePath));

    $contentType = mime_content_type($filePath);
    $response = $response->withHeader('Content-Type', $contentType);

    return $response;
}
```

Check for static files before routing to dynamic handlers.

## Downsides and Considerations

### Global State

Traditional PHP applications follow a "start, execute, stop" lifecycle — no state persists between requests. Our server is long-running, so shared state is dangerous. Sessions opened for one user are visible to all connections. Authentication data stored in the global scope leaks across requests. You need to architect around this.

### Blocking I/O

PHP's internals and most extensions are synchronous. When one request blocks on a slow operation, it holds up that socket. The server can still accept new connections, but request processing is single-threaded.

### Production Readiness

This server is educational, not production-grade. Apache and NGINX have decades of optimization, security hardening, and feature development behind them. What you gain from building your own is understanding.

## Understanding Is Key

The goal of this series is insight, not replacement. When you understand what happens inside a web server — socket listening, connection handling, request parsing, routing — you make better architectural decisions in your PHP applications.

The full object-oriented implementation is available at [waiter-http](https://github.com/dragonmantank/waiter-http) if you want to see a more polished version of these concepts.
