---
title: "PSR-7 HTTP Message Interface - Standardizing PHP HTTP Requests and Responses"
seoTitle: "PSR-7 HTTP Message Interface - Standardizing PHP HTTP Requests and Responses"
description: "A complete guide to PSR-7 HTTP Message Interface. Learn how standardized HTTP request and response objects enable framework interoperability in PHP."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-07-24 21:00:00"
tags: ["PSR-7", "PHP", "HTTP", "PSR", "PHP-FIG", "Middleware"]
---

Every web application handles HTTP requests and generates HTTP responses. Before PSR-7, every framework and library had its own way of representing these messages. Guzzle had its own request object. Symfony had its own. Laravel had its own. Moving between them—or writing middleware that worked across frameworks—required adapters and conversion layers.

PSR-7, the HTTP Message Interface, changed this by defining standard interfaces for HTTP messages. It's one of the most impactful PHP-FIG standards because it enables interoperability between libraries, frameworks, and middleware in a way that was previously impossible.

## What PSR-7 Defines

PSR-7 defines five key interfaces, all in the `Psr\Http\Message` namespace:

- **MessageInterface**: The base for all HTTP messages (both requests and responses)
- **RequestInterface**: Extends MessageInterface for outgoing client requests
- **ServerRequestInterface**: Extends RequestInterface for requests arriving at the server
- **ResponseInterface**: Extends MessageInterface for server responses
- **StreamInterface**: For handling message bodies as streams
- **UriInterface**: For representing and manipulating URIs
- **UploadedFileInterface**: For handling uploaded files

### Understanding HTTP Messages

An HTTP request looks like this:

```
POST /path HTTP/1.1
Host: example.com
Content-Type: application/x-www-form-urlencoded

bar=baz&boz=100
```

The first line is the request line (method, path, protocol version). Next come headers, then a blank line, then the body.

An HTTP response looks like this:

```
HTTP/1.1 200 OK
Content-Type: text/plain
Cache-Control: max-age=100

The body of the response.
```

The status line contains the protocol version, status code, and reason phrase. Then headers, blank line, body.

PSR-7 wraps all of this in well-defined objects with consistent, immutable APIs.

## Why Immutability Matters

PSR-7 interfaces are designed around **immutability**. When you call a `with*()` method, you don't modify the original object—you get a new object with the change applied. This prevents subtle bugs where one piece of code accidentally modifies a request that another piece of code is still using.

```php
<?php

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

// Create a base request
$uri = new Uri('http://example.com');
$baseRequest = new Request('GET', $uri, [
    'Authorization' => 'Bearer token123',
    'Accept' => 'application/json',
]);

// Create derived requests without modifying the base
$listRequest = $baseRequest
    ->withUri($uri->withPath('/list'))
    ->withMethod('GET');

$createRequest = $baseRequest
    ->withUri($uri->withPath('/users'))
    ->withMethod('POST');

// $baseRequest is still 'GET http://example.com'
// $listRequest is 'GET http://example.com/list'
// $createRequest is 'POST http://example.com/users'
```

The `$baseRequest` maintains its original state. The derived requests get new URIs and methods, but inherit the headers from the base. This is the power of the immutable value object pattern.

## Key Interfaces In Detail

### MessageInterface

The base interface for all HTTP messages:

```php
namespace Psr\Http\Message;

interface MessageInterface
{
    public function getProtocolVersion(): string;
    public function withProtocolVersion(string $version): static;

    public function getHeaders(): array;
    public function hasHeader(string $name): bool;
    public function getHeader(string $name): array;
    public function getHeaderLine(string $name): string;
    public function withHeader(string $name, $value): static;
    public function withAddedHeader(string $name, $value): static;
    public function withoutHeader(string $name): static;

    public function getBody(): StreamInterface;
    public function withBody(StreamInterface $body): static;
}
```

Headers are case-insensitive—`getHeaderLine('content-type')` returns the same as `getHeaderLine('Content-Type')`. The interface stores them internally in a canonical form.

### RequestInterface

```php
namespace Psr\Http\Message;

interface RequestInterface extends MessageInterface
{
    public function getRequestTarget(): string;
    public function withRequestTarget(string $requestTarget): static;

    public function getMethod(): string;
    public function withMethod(string $method): static;

    public function getUri(): UriInterface;
    public function withUri(UriInterface $uri, bool $preserveHost = false): static;
}
```

### ServerRequestInterface

This extends RequestInterface with server-specific features:

```php
namespace Psr\Http\Message;

interface ServerRequestInterface extends RequestInterface
{
    public function getServerParams(): array;
    public function getCookieParams(): array;
    public function withCookieParams(array $cookies): static;

    public function getQueryParams(): array;
    public function withQueryParams(array $query): static;

    public function getUploadedFiles(): array;
    public function withUploadedFiles(array $uploadedFiles): static;

    public function getParsedBody(): object|array|null;
    public function withParsedBody($data): static;

    public function getAttributes(): array;
    public function getAttribute(string $name, $default = null): mixed;
    public function withAttribute(string $name, $value): static;
    public function withoutAttribute(string $name): static;
}
```

Attributes are a key feature for middleware. A middleware can add attributes to the request (like the authenticated user, route parameters, or parsed JWT claims) and downstream handlers access them via `getAttribute()`.

```php
<?php

// Authentication middleware
$request = $request->withAttribute('user', $authenticatedUser);

// Controller
$user = $request->getAttribute('user');
```

### ResponseInterface

```php
namespace Psr\Http\Message;

interface ResponseInterface extends MessageInterface
{
    public function getStatusCode(): int;
    public function withStatus(int $code, string $reasonPhrase = ''): static;

    public function getReasonPhrase(): string;
}
```

### StreamInterface

PSR-7 uses streams for message bodies because bodies can be large. Loading an entire upload into memory would be wasteful. Streams allow efficient processing regardless of size:

```php
namespace Psr\Http\Message;

interface StreamInterface
{
    public function __toString(): string;
    public function close(): void;
    public function detach(): ?resource;
    public function getSize(): ?int;
    public function tell(): int;
    public function eof(): bool;
    public function isSeekable(): bool;
    public function seek(int $offset, int $whence = SEEK_SET): void;
    public function rewind(): void;
    public function isWritable(): bool;
    public function write(string $string): int;
    public function isReadable(): bool;
    public function read(int $length): string;
    public function getContents(): string;
    public function getMetadata(?string $key = null): mixed;
}
```

### UriInterface

URIs are composed into request objects but can also be manipulated independently:

```php
namespace Psr\Http\Message;

interface UriInterface
{
    public function getScheme(): string;
    public function getAuthority(): string;
    public function getUserInfo(): string;
    public function getHost(): string;
    public function getPort(): ?int;
    public function getPath(): string;
    public function getQuery(): string;
    public function getFragment(): string;
    public function withScheme(string $scheme): static;
    public function withUserInfo(string $user, ?string $password = null): static;
    public function withHost(string $host): static;
    public function withPort(?int $port): static;
    public function withPath(string $path): static;
    public function withQuery(string $query): static;
    public function withFragment(string $fragment): static;
    public function __toString(): string;
}
```

## Popular Implementations

Several high-quality PSR-7 implementations exist:

**Guzzle PSR-7** (`guzzlehttp/psr7`): The most widely used implementation, created by the Guzzle HTTP client team. It comes bundled with Guzzle but can be used standalone.

**Laminas Diactoros** (`laminas/laminas-diactoros`): The Zend Framework team's implementation (now under the Laminas project). Used by Mezzio (formerly Zend Expressive) and other frameworks.

**Slim PSR-7** (`slim/psr7`): A lightweight implementation used by the Slim framework.

**Nyholm PSR-7** (`nyholm/psr7`): A modern, well-tested implementation used by Symfony 5+.

## PSR-7 and PSR-15 Middleware

PSR-7 defines the messages; PSR-15 (HTTP Handlers) defines how middleware processes them. A PSR-15 middleware receives a `ServerRequestInterface` and a handler, processes the request, and delegates to the next handler:

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly TokenService $tokenService,
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            // Return early - no token provided
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $token = substr($authHeader, 7);

        try {
            $user = $this->tokenService->validateToken($token);
            $request = $request->withAttribute('user', $user);
        } catch (InvalidTokenException $e) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        return $handler->handle($request);
    }
}
```

The middleware can return a response early (short-circuiting the middleware stack) or delegate to the next handler. This is how frameworks like Laravel, Symfony, and Slim all handle middleware today.

## PSR-7 in Modern PHP Frameworks

### Laravel

Laravel's HTTP kernel uses PSR-7 internally through Symfony's HTTP Foundation. You can type-hint `Psr\Http\Message\ServerRequestInterface` in controller methods—Laravel automatically converts between its request object and PSR-7:

```php
<?php

namespace App\Http\Controllers;

use Psr\Http\Message\ServerRequestInterface;

class UserController extends Controller
{
    public function index(ServerRequestInterface $request)
    {
        $user = $request->getAttribute('user');

        return response()->json(['user' => $user]);
    }
}
```

### Symfony

Symfony 5+ uses Nyholm's PSR-7 implementation. The framework provides bridge services to convert between Symfony's `Request` and PSR-7:

```php
<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();

        return new JsonResponse(['received' => $data]);
    }
}
```

### Slim

Slim 4+ fully embraces PSR-7 and PSR-15. The framework's request and response objects directly implement the PSR-7 interfaces:

```php
<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;

$app->get('/api/users/{id}', function (Request $request, Response $response, array $args) {
    $userId = $args['id'];
    $routeContext = RouteContext::fromRequest($request);

    $data = ['user_id' => $userId];
    $response->getBody()->write(json_encode($data));

    return $response->withHeader('Content-Type', 'application/json');
});
```

## The Parsed Body and Content Negotiation

`ServerRequestInterface::getParsedBody()` returns the parsed request body. For form submissions, this is the `$_POST` data. For JSON APIs, middleware typically parses the JSON body and calls `withParsedBody()`:

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $contentType = $request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $contents = $request->getBody()->__toString();
            $data = json_decode($contents, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $request = $request->withParsedBody($data);
            }
        }

        return $handler->handle($request);
    }
}
```

This pattern lets your controllers work with parsed data regardless of the incoming content type. The controller calls `$request->getParsedBody()` and gets either form data, JSON, or XML depending on what the middleware parsed.

## Uploaded Files

PSR-7 handles file uploads through `UploadedFileInterface`. The interface provides methods to get the file size, media type, client filename, and most importantly, to move the file to a destination:

```php
<?php

use Psr\Http\Message\UploadedFileInterface;

function handleUpload(UploadedFileInterface $uploadedFile): void
{
    $clientFilename = $uploadedFile->getClientFilename();
    $mediaType = $uploadedFile->getClientMediaType();
    $size = $uploadedFile->getSize();

    // Validate
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mediaType, $allowedTypes, true)) {
        throw new \InvalidArgumentException('Invalid file type');
    }

    if ($size > 10 * 1024 * 1024) {
        throw new \InvalidArgumentException('File too large');
    }

    // Move to permanent location
    $destination = '/var/www/uploads/' . uniqid() . '_' . $clientFilename;
    $uploadedFile->moveTo($destination);
}
```

The `ServerRequestInterface::getUploadedFiles()` returns an array (potentially nested) of `UploadedFileInterface` instances, mirroring the structure of `$_FILES`.

## Building a PSR-7 Response

Responses are constructed with status code, headers, and body. Here's a factory for common response types:

```php
<?php

namespace App\Http;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

final class ResponseFactory
{
    public static function json(
        mixed $data,
        int $status = 200,
        array $headers = [],
    ): ResponseInterface {
        $body = new \GuzzleHttp\Psr7\Stream(
            fopen('php://temp', 'r+')
        );
        $body->write(json_encode($data, JSON_THROW_ON_ERROR));
        $body->rewind();

        return new Response(
            $status,
            array_merge(
                ['Content-Type' => 'application/json'],
                $headers,
            ),
            $body,
        );
    }

    public static function html(
        string $html,
        int $status = 200,
    ): ResponseInterface {
        $body = new \GuzzleHttp\Psr7\Stream(
            fopen('php://temp', 'r+')
        );
        $body->write($html);
        $body->rewind();

        return new Response(
            $status,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $body,
        );
    }

    public static function redirect(
        string $url,
        int $status = 302,
    ): ResponseInterface {
        return new Response($status, ['Location' => $url]);
    }

    public static function empty(int $status = 204): ResponseInterface
    {
        return new Response($status);
    }
}
```

Notice that all methods use `php://temp` streams. This keeps small responses in memory while transparently swapping to disk for large responses, preventing memory exhaustion.

## Why Not Just Use Superglobals?

Before PSR-7, most PHP applications accessed request data directly from superglobals like `$_GET`, `$_POST`, `$_SERVER`, and `$_FILES`. This works but has significant drawbacks:

- **Testability**: Testing a controller that reads `$_POST` directly requires setting superglobals before the test and cleaning them up afterward—a fragile, error-prone process.
- **Middleware**: Without a request object, middleware can't add attributes or modify the request without resorting to hacks like request-level static properties.
- **Immutability**: Superglobals are mutable by any code at any time. A PSR-7 request is immutable, guaranteeing that middleware further up the stack can't corrupt the request you're processing.
- **PSR-7 decouples your application from the SAPI**. The exact same request object works whether the request arrives via Apache mod_php, PHP-FPM, or a built-in PHP server. Your application code doesn't care about the SAPI. 

## PSR-7 and Async PHP

With the rise of async PHP frameworks like Amp and ReactPHP, PSR-7 becomes even more important. In async applications, you can't rely on superglobals because there may be multiple concurrent requests in the same process. Each request must be a self-contained PSR-7 object.

```php
<?php

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\HttpServer;
use Amp\Socket\Server as SocketServer;

$server = new HttpServer(
    SocketServer::listen('0.0.0.0:8080'),
    new class implements \Amp\Http\Server\RequestHandler {
        public function handleRequest(Request $request): Response
        {
            $name = $request->getAttribute('user') ?? 'World';

            $body = new \Amp\Http\HttpMessage\FormattedBody('text/plain', "Hello, {$name}!");

            return new Response(
                status: 200,
                body: $body,
            );
        }
    },
);
```

Async frameworks use PSR-7 as the foundation, proving the interfaces are not just for synchronous request-response cycles.

## Validating Header Data

PSR-7 includes important recommendations for header validation. Implementations should reject invalid header names and values without attempting to auto-correct them. Specifically, header names and values should reject:

- NUL (`0x00`)
- Carriage return (`0x0D`)
- Newline (`0x0A`)

Header names should also reject any character less than or equal to `0x20` (space).

This strict approach prevents header injection attacks. If user input somehow reaches a header value, invalid characters cause an error rather than allowing HTTP response splitting.

## Conclusion

PSR-7 changed PHP's ecosystem by standardizing how HTTP messages are represented. Before it, every framework reinvented this wheel. After it, middleware became portable, libraries became interoperable, and frameworks could share HTTP-related tooling.

Understanding PSR-7 isn't just about knowing five interfaces. It's about understanding how immutability prevents bugs, how streams handle large payloads efficiently, and how attributes enable clean middleware architectures. Whether you're building an API, a middleware stack, or an HTTP client, PSR-7 is the foundation everything else builds on.
