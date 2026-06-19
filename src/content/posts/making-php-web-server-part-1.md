---
title: "Making Our Own PHP Web Server - Part 1: HTTP From Scratch"
description: "Build a basic HTTP server in PHP using socket programming. Learn how to handle TCP connections, parse HTTP requests, serve responses, and understand what happens under the hood."
pubDate: "2022-10-16 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Web Server", "HTTP", "Socket Programming", "Networking"]
selected: false
---

PHP applications rarely handle HTTP connections directly. We install Apache or nginx, configure PHP-FPM, and let the web server deal with the messy details of TCP connections. But what if PHP could handle its own requests?

Contrast this with Node.js. Install Express, write a few routes, run `node server.js`, and you can hit `http://localhost:3000` immediately. The application owns the connection.

PHP's built-in development server (`php -S`) exists but has always been labeled as not production ready. It's single-threaded, poor at serving static files, and lacks the sophisticated request handling of full web servers. But what if we built our own?

## The PHP Process Lifecycle

PHP still runs on an old model: start, process, die. The engine doesn't understand persistent connections or request multiplexing. It expects a web server to:

1. Accept the TCP connection
2. Parse the HTTP request
3. Invoke PHP through a module (mod_php in Apache) or FastCGI (PHP-FPM)
4. Pass the response back to the client

In Apache's mod_php setup, httpd starts a PHP engine inside each worker process. When a request arrives, httpd pipes the file through PHP. PHP executes the code and generates a response. PHP never touches the raw connection.

With FastCGI, PHP-FPM manages a pool of worker processes. nginx hands off FastCGI requests to an available worker. The worker processes one request, returns the response, and waits for the next. Again, ten workers means ten concurrent requests max.

PHP sidesteps socket programming because it's genuinely complex. But the core functions are available if you know where to look.

## The PHP Development Server

PHP 5.4 introduced a development server:

```bash
$ php -S <ip>[:port] [-t /docroot] [/router.php]
```

```bash
$ cd path/to/application/
$ php -S localhost:8080 -t public/
```

This works for development but has significant limitations:

- Single-threaded (multi-threaded added in 7.4, still not production-ready)
- Only understands about 50 mime types
- No URI rewriting without a router script
- Cannot handle concurrent connections without blocking

## Socket Programming Basics

PHP exposes low-level socket functions that mirror C's socket API. A network socket is an endpoint for TCP/IP communication, composed of an IP address and a port number. Here are the seven essential functions:

- `socket_create()` — creates a socket
- `socket_bind()` — binds to an IP address and port
- `socket_listen()` — starts listening for connections
- `socket_accept()` — accepts an incoming connection
- `socket_read()` — reads data from the connection
- `socket_write()` — writes data back
- `socket_close()` — closes the connection

Creating and binding a socket:

```php
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

socket_bind($socket, '0.0.0.0', 8080);

socket_listen($socket);
```

`AF_INET` specifies IPv4. `SOCK_STREAM` means a bidirectional stream socket. `SOL_TCP` selects the TCP protocol. The bind address `0.0.0.0` listens on all network interfaces.

## Accepting and Responding to Connections

Now we wait for connections in an infinite loop:

```php
while (true) {
    $connection = socket_accept($socket);
    $buffer = socket_read($connection, 1024, PHP_NORMAL_READ);
    echo $buffer;

    socket_write($connection, $buffer);
    socket_read($connection, 1024);
    socket_close($connection);
}
```

`socket_accept()` blocks until a client connects. `socket_read()` reads up to 1024 bytes with `PHP_NORMAL_READ` (stops at line endings). We echo the raw data to the console, send it back verbatim, then close the connection.

Test it with netcat:

```bash
$ netcat localhost 8080
test
test
```

The server echoes whatever you send. It works, but it's not HTTP yet.

## Parsing HTTP Requests

An HTTP request is a formatted string:

```
GET / HTTP/1.1
Host: localhost:8080
User-Agent: insomnia/2022.4.2
Content-Type: application/json
Accept: */*
Content-Length: 17
```

The first line contains the HTTP verb, URI, and version. Following lines are headers as key-value pairs. After a blank line comes the optional body.

Parsing this by hand is error-prone. The HTTP specification is detailed, and many clients deviate from it. Instead, use laminas-diactoros which includes a PSR-7 request parser:

```bash
$ composer require laminas/laminas-diactoros
```

Now integrate it into our server:

```php
use Laminas\Diactoros\Request\Serializer;

while (true) {
    $connection = socket_accept($socket);
    $buffer = socket_read($connection, 1024, PHP_NORMAL_READ);

    $request = Serializer::fromString($buffer);

    // Handle the response...
}
```

`Serializer::fromString()` converts the raw HTTP string into a PSR-7 `ServerRequestInterface` object. Any framework that understands PSR-7 can now process this request.

## Generating HTTP Responses

An HTTP response follows a similar structure: status line, headers, body. Again, laminas-diactoros handles the serialization:

```php
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\Serializer;

while (true) {
    $connection = socket_accept($socket);
    $buffer = socket_read($connection, 1024, PHP_NORMAL_READ);

    $request = Serializer::fromString($buffer);

    $response = (new Response())->withStatus(200);
    $message = 'You requested ' . $request->getUriString();
    $message .= ' with verb ' . $request->getMethod();
    $response->getBody()->write($message);

    $responseString = Serializer::toString($response);

    socket_write($connection, $responseString);
    socket_close($connection);
}
```

We create a 200 response, write a message containing the URI and HTTP method, serialize it to a string, and write it back through the socket. Browsers and API tools like Insomnia or Postman can now talk to our server.

Complete working loop:

```php
<?php

require 'vendor/autoload.php';

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Request\Serializer as RequestSerializer;
use Laminas\Diactoros\Response\Serializer as ResponseSerializer;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, '0.0.0.0', 8080);
socket_listen($socket);

echo "Listening on http://localhost:8080\n";

while (true) {
    $connection = socket_accept($socket);
    $buffer = socket_read($connection, 1024, PHP_NORMAL_READ);

    $request = RequestSerializer::fromString($buffer);

    $response = (new Response())->withStatus(200);
    $body = 'You requested: ' . $request->getUriString() . PHP_EOL;
    $body .= 'Method: ' . $request->getMethod() . PHP_EOL;
    $response->getBody()->write($body);

    $responseString = ResponseSerializer::toString($response);
    socket_write($connection, $responseString);
    socket_close($connection);
}
```

## What We Accomplished

Our hand-built server can now:

- Accept TCP connections on a configurable address and port
- Parse raw HTTP requests into PSR-7 objects
- Generate proper HTTP responses
- Work with standard HTTP clients (browsers, curl, API tools)

One thing we haven't improved is concurrent connection handling. The server processes one request at a time. While one request is being handled, all others queue up. Next month, we'll look at making this server handle multiple connections simultaneously using techniques like process forking or event loops.

The development server's limitations — single-threading, poor static file serving, no advanced routing — remain. But we've built something the development server cannot do: directly manipulate requests before handling them with userland code, no extensions required. That opens up interesting possibilities for middleware stacks, custom routing, and learning exactly what happens when a browser talks to a server.
