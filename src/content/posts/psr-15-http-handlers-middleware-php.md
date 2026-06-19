---
title: "PSR-15 HTTP Server Request Handlers and Middleware in PHP"
description: "Master PSR-15 for PHP HTTP middleware and request handlers. Learn RequestHandlerInterface, MiddlewareInterface, middleware piping, and practical implementation."
pubDate: "2023-02-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PSR-15", "PHP", "HTTP Middleware", "Request Handlers", "PSR-7", "PHP-FIG", "Server Request", "Middleware Pipeline"]
selected: false
---

Every HTTP request that reaches a PHP application passes through several layers before it reaches your business logic. Authentication, authorization, rate limiting, logging, input validation, CORS handling — these are not part of your core application. They are middleware, sitting between the transport layer and your application logic.

Before PSR-15, every framework implemented middleware differently. Laravel used its own pipeline. Symfony had its event-driven kernel. Slim had a different approach. Zend (now Laminas) had yet another. Moving middleware between frameworks meant rewriting it from scratch.

PSR-15 solves this by standardizing how HTTP middleware and request handlers work in PHP. Two interfaces — `RequestHandlerInterface` and `MiddlewareInterface` — define a contract that any middleware can implement and any framework can consume.

This article explains PSR-15 from the ground up, with practical examples you can use in any PHP application.

## What You'll Learn

- The PSR-15 RequestHandlerInterface and MiddlewareInterface
- How middleware pipes work: single pass vs. double pass
- Composing middleware into processing pipelines
- Practical middleware examples: authentication, logging, rate limiting
- How popular PHP frameworks implement PSR-15
- Building reusable middleware libraries
- Testing middleware components

## The PSR-15 Interfaces

PSR-15 defines two interfaces in the `Psr\Http\Server` namespace.

### RequestHandlerInterface

```php
<?php

namespace Psr\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
```

A request handler processes a server request and returns a response. It is the terminal in the middleware chain — the code that actually handles the business logic and produces the response.

### MiddlewareInterface

```php
<?php

namespace Psr\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface;
}
```

A middleware component receives a request and a handler. It can inspect the request, modify it, perform actions, and either return a response directly or delegate to the next handler.

## Single Pass vs. Double Pass

Before PSR-15, the dominant middleware pattern was "double pass." The signature was:

```php
function (Request $request, Response $response, callable $next): Response
```

Both the request and response were passed in. The middleware could modify the response before passing it to the next middleware. This is called "double pass" because both request and response are passed through.

PSR-15 uses "single pass":

```php
function process(ServerRequestInterface $request, RequestHandlerInterface $handler): Response
```

Only the request is passed. The response is created by the handler or by the middleware itself. This avoids several problems with double pass:

- **Empty response objects**: In double pass, an empty response is passed in with no guarantee a usable response comes out.
- **Response mutation**: Middleware can modify the response before passing it forward, potentially corrupting data other middleware already wrote.
- **Weak typing**: The `$next` callable in double pass cannot be strongly typed.

Single pass solves these by putting response creation under the control of the terminal handler, not the middleware chain.

## How Middleware Piping Works

A middleware pipeline is a stack of middleware that wraps a core handler. The request enters the outer middleware, which may process it and pass it inward, until the core handler produces a response. The response then flows back outward through the middleware.

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// A request handler that composes middleware
class MiddlewareRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private MiddlewareInterface $middleware,
        private RequestHandlerInterface $nextHandler
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, $this->nextHandler);
    }
}

// The core handler - this is where your application logic lives
$coreHandler = new class() implements RequestHandlerInterface {
    public function __construct(
        private ResponseFactory $responseFactory
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write('Hello from the core handler!');
        return $response;
    }
};

// Build the pipeline: Authentication -> Authorization -> Core
$pipeline = new MiddlewareRequestHandler(
    new AuthorizationMiddleware(),
    new MiddlewareRequestHandler(
        new AuthenticationMiddleware(),
        $coreHandler
    )
);

// Execute
$response = $pipeline->handle($request);
```

The outer middleware (Authorization) runs first. It calls `$this->nextHandler->handle($request)`, which delegates to the Authentication middleware. Authentication calls its `$this->nextHandler->handle($request)`, which reaches the core handler. The response flows back in reverse.

## Practical Middleware Examples

### Authentication Middleware

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TokenService $tokenService,
        private ResponseFactory $responseFactory,
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return $this->unauthorized('Missing Authorization header');
        }

        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $user = $this->tokenService->validateToken($token);
        } catch (TokenExpiredException $e) {
            return $this->unauthorized('Token expired');
        } catch (TokenInvalidException $e) {
            return $this->unauthorized('Invalid token');
        }

        // Attach user to request attributes for downstream use
        $request = $request->withAttribute('user', $user);

        // Delegate to the next handler
        return $handler->handle($request);
    }

    private function unauthorized(string $message): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(401);
        $response->getBody()->write(
            json_encode(['error' => $message])
        );
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

**Explanation:** This middleware checks for a Bearer token in the Authorization header. If the token is missing, expired, or invalid, it returns a 401 response immediately without delegating to the next handler. If the token is valid, it decodes the user and attaches it to the request as an attribute, then delegates.

### Logging Middleware

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $start = hrtime(true);
        $requestId = bin2hex(random_bytes(16));

        $request = $request->withAttribute('request_id', $requestId);

        $this->logger->info('Incoming request', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => (string) $request->getUri(),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
        ]);

        $response = $handler->handle($request);

        $duration = (hrtime(true) - $start) / 1_000_000; // ms

        $this->logger->info('Request completed', [
            'request_id' => $requestId,
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration, 2),
        ]);

        return $response;
    }
}
```

**Explanation:** This middleware logs every request and response with timing information. It runs both before and after the inner handler. The pre-processing adds a request ID and logs the incoming request. The post-processing logs the response status and duration. This pattern — executing code both before and after the delegate — is the hallmark of powerful middleware.

### Rate Limiting Middleware

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RateLimitingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,
        private int $maxRequests = 100,
        private int $windowSeconds = 60,
        private ResponseFactory $responseFactory,
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $key = 'rate_limit:' . $request->getAttribute('user')->id
            ?? $request->getServerParams()['REMOTE_ADDR'];

        $current = (int) $this->cache->get($key, 0);
        $ttl = $this->cache->ttl($key);

        if ($current >= $this->maxRequests) {
            $response = $this->responseFactory->createResponse(429);
            $response->getBody()->write(
                json_encode(['error' => 'Too many requests'])
            );

            return $response
                ->withHeader('Retry-After', (string) $ttl)
                ->withHeader('Content-Type', 'application/json');
        }

        $this->cache->increment($key, 1, $this->windowSeconds);

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) ($this->maxRequests - $current - 1));
    }
}
```

**Explanation:** This middleware tracks request counts per user or IP address. If the count exceeds the limit, it returns a 429 Too Many Requests response. Otherwise, it increments the counter and delegates to the next handler, adding rate limit headers to the response.

## Composing Middleware

The real power of PSR-15 emerges when you compose middleware into named groups that can be reused across routes.

```php
<?php

class MiddlewarePipeline implements RequestHandlerInterface
{
    private array $middleware = [];

    public function __construct(
        private RequestHandlerInterface $coreHandler,
        private ResponseFactory $responseFactory,
        private ContainerInterface $container,
    ) {}

    public function add(MiddlewareInterface|string $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->coreHandler;

        // Wrap the core handler with each middleware from outside to inside
        foreach (array_reverse($this->middleware) as $middleware) {
            if (is_string($middleware)) {
                $middleware = $this->container->get($middleware);
            }

            $handler = new class($middleware, $handler) implements RequestHandlerInterface {
                public function __construct(
                    private MiddlewareInterface $mw,
                    private RequestHandlerInterface $next
                ) {}

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->mw->process($request, $this->next);
                }
            };
        }

        return $handler->handle($request);
    }
}
```

Usage:

```php
<?php

$pipeline = new MiddlewarePipeline(
    coreHandler: $appHandler,
    responseFactory: $responseFactory,
    container: $container,
);

$pipeline
    ->add(LoggingMiddleware::class)
    ->add(RateLimitingMiddleware::class)
    ->add(AuthenticationMiddleware::class)
    ->add(AuthorizationMiddleware::class);

$response = $pipeline->handle($request);
```

## PSR-15 in Popular Frameworks

### Laravel

Laravel's middleware system predates PSR-15 but follows the same conceptual model. Laravel middleware receives a `$next` closure that delegates to the next middleware in the pipeline.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->input('token') !== config('app.secret')) {
            return redirect('login');
        }

        return $next($request);
    }
}
```

Laravel groups middleware for route assignment:

```php
<?php

// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        \App\Http\Middleware\EnsureTokenIsValid::class,
        \App\Http\Middleware\RateLimitRequests::class,
    ],
];
```

### Slim

Slim 4 fully implements PSR-15. Its application class is a `RequestHandlerInterface`, and middleware is added with the `add()` method.

```php
<?php

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->add(new AuthenticationMiddleware());
$app->add(new LoggingMiddleware());
$app->add(new RateLimitingMiddleware());

$app->get('/api/users', function ($request, $response) {
    $payload = json_encode(['users' => []]);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
```

### Mezzio (formerly Zend Expressive)

Mezzio is built entirely around PSR-15. The entire application is a middleware pipeline.

```php
<?php

use Mezzio\AppFactory;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

$app = AppFactory::create();

$app->pipe(new AuthenticationMiddleware());
$app->pipe(new RateLimitingMiddleware());

$app->get('/api/users', App\Handler\UsersHandler::class);

$app->run();
```

## Real-World Use Cases

### API Gateway

An API gateway is the ultimate middleware composition. Every request passes through authentication, rate limiting, logging, CORS handling, request transformation, and caching before reaching the backend service. Each of these is a PSR-15 middleware component.

### Multi-Tenant Applications

Multi-tenant applications use middleware to resolve the tenant from the request — subdomain, header, or path — and configure database connections, caching, and configuration accordingly.

### Content Negotiation

A content negotiation middleware inspects the Accept header and sets the response content type. It could also format the response as JSON, XML, or HTML based on client preference.

### Maintenance Mode

A maintenance mode middleware checks a flag in Redis or the database. If maintenance mode is active, it returns a 503 Service Unavailable response for all requests except those from whitelisted IPs.

## Best Practices

### Keep Middleware Single Responsibility

Each middleware should do one thing. Authentication middleware authenticates. Logging middleware logs. Do not combine them. Single-responsibility middleware is easier to test, compose, and reason about.

### Return Early When Possible

If your middleware determines the request cannot proceed — authentication fails, rate limit exceeded — return a response immediately. Do not call the next handler. This prevents unnecessary processing.

### Use Request Attributes for Data Sharing

Attach data to the request via `withAttribute()` rather than using global state, service locators, or static properties. Request attributes are the standard PSR-7 mechanism for carrying middleware-generated data.

### Handle Exceptions

Wrap `$handler->handle($request)` in a try-catch if your middleware needs to handle errors gracefully. An exception handler middleware at the outer edge of the pipeline can catch all exceptions and return appropriate error responses.

## Common Mistakes to Avoid

**Modifying the response after the next handler returns it.** The response object is immutable in PSR-7. You must create a new response with `withHeader()` or similar methods rather than mutating the existing one.

**Not calling the next handler.** If your middleware does not delegate, the inner middleware and core handler never execute. Ensure every path either returns a response or delegates.

**Assuming middleware runs in order specified.** In a stacked pipeline, the outermost middleware (added first) runs first. Understand your framework's middleware ordering.

**Putting too much logic in middleware.** Middleware should handle cross-cutting concerns, not business logic. Business logic belongs in the core handler or service layer.

## Frequently Asked Questions

### What is the difference between PSR-7 and PSR-15?

PSR-7 defines HTTP message interfaces (Request, Response, Uri, etc.). PSR-15 defines how those messages are processed through middleware and request handlers. They work together — PSR-15 uses PSR-7 interfaces.

### Can I use PSR-15 middleware in Laravel?

Laravel does not natively implement PSR-15, but you can wrap PSR-15 middleware in a Laravel-compatible adapter. The concepts are similar enough that porting middleware between the two requires minimal changes.

### Is PSR-15 obsolete with PHP attributes?

No. PSR-15 defines an interface contract independent of how middleware is registered. PHP attributes can be used for registration, but the runtime interfaces remain relevant.

### How do I test PSR-15 middleware?

Create a mock request handler and assert the middleware returns the expected response. Test each middleware in isolation, then test compositions with integration tests.

### Does PSR-15 support async middleware?

PSR-15 is synchronous by design. For async processing, wrap async operations in a synchronous middleware or use a queue-based approach for specific tasks.

## Conclusion

PSR-15 standardizes PHP HTTP middleware and request handlers, enabling interoperability between frameworks and libraries. The single-pass architecture avoids the pitfalls of the double-pass pattern while keeping the interface simple and powerful.

Middleware composes into pipelines that handle cross-cutting concerns — authentication, logging, rate limiting, CORS — without coupling to the core application logic. Each middleware component is testable in isolation and reusable across projects.

Whether you use Slim, Mezzio, Laravel, or a custom framework, understanding PSR-15 gives you a portable skill for building clean, composable HTTP processing layers.

**Ready to build your first PSR-15 middleware?** Start by identifying a cross-cutting concern in your application — logging, authentication, or rate limiting — and implement it as a PSR-15 middleware component. Compose it into a pipeline and see how the separation of concerns improves your codebase.
