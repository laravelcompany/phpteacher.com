---
title: "HTTP Tortilla: Build Custom PSR-7 Requests in PHP"
description: "Learn how HTTP Tortilla simplifies PSR-7 request and response wrapping in PHP. Build custom HTTP objects with traits for cleaner middleware and APIs."
pubDate: "2023-05-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["HTTP Tortilla", "PSR-7", "PHP", "Middleware", "PSR-15", "HTTP Requests", "API Design", "Traits"]
---

PSR-7 defines standard interfaces for HTTP request and response messages. These interfaces are invaluable for interoperability between libraries, testing, and middleware pipelines. But the standard interface is minimal by design. When you want convenience methods like `$request->getQueryParam('name', $default)` instead of `$request->getQueryParams()['name'] ?? $default`, you face a choice: extend a library class or implement the full interface yourself.

HTTP Tortilla by Tim Lytle provides a third path. It uses PHP traits to proxy PSR-7 method calls to a wrapped object, letting you create custom request and response classes with minimal boilerplate.

## What You'll Learn

- The challenge of extending PSR-7 objects
- How HTTP Tortilla uses traits for clean wrapping
- Building custom server requests with convenience methods
- Creating typed responses (JSON, HAL, API Problem)
- Implementing lazy responses for middleware pipelines
- Making exceptions implement PSR-7 ResponseInterface
- Real-world patterns for custom HTTP objects

## The PSR-7 Extension Problem

PSR-7 interfaces are immutable. Every `with*()` method returns a new instance. This design is excellent for correctness but creates friction when you want to extend behavior.

```php
<?php

// Standard PSR-7 usage
$queryParams = $request->getQueryParams();
$filter = $queryParams['filter'] ?? null;

// What you want instead
$filter = $request->getQueryParam('filter');
```

You could create a custom class that wraps a PSR-7 request. But then every method on PSR-7 must be proxied manually. That is dozens of methods across ServerRequestInterface, ResponseInterface, UriInterface, and MessageInterface.

```php
<?php

class CustomRequest
{
    private ServerRequestInterface $wrapped;

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->wrapped->getQueryParams()[$name] ?? $default;
    }

    // Must proxy ALL PSR-7 methods...
    public function getProtocolVersion(): string
    {
        return $this->wrapped->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): static
    {
        return $this->wrapped->withProtocolVersion($version);
    }

    // ... 50+ more methods
}
```

That is tedious, error-prone, and hard to maintain when PSR-7 evolves.

## Understanding PSR-7 Immutability

PSR-7 messages are immutable. Every modification returns a new instance. This design prevents side effects and makes messages safe to share between middleware.

```php
<?php

$originalRequest = $request;
$modifiedRequest = $request->withHeader('X-Custom', 'value');

// $originalRequest is unchanged
var_dump($originalRequest === $modifiedRequest); // false
```

This immutability is the root cause of the extension problem. When you wrap a PSR-7 request, `with*()` methods return the wrapped object, not your custom class. Without HTTP Tortilla's factory pattern, every `with*()` call breaks the chain of your custom type.

## HTTP Tortilla Traits Overview

The library provides four traits that mirror the PSR-7 interface hierarchy:

- `MessageWrapper` - Proxies `getProtocolVersion()` and `withProtocolVersion()`
- `RequestWrapper` - Extends MessageWrapper with request-specific methods
- `ServerRequestWrapper` - Extends RequestWrapper with server-specific methods (attributes, cookies, uploaded files, query params, parsed body)
- `ResponseWrapper` - Extends MessageWrapper with response-specific methods (status code, reason phrase)
- `UriWrapper` - Proxies all UriInterface methods

Each trait stores a reference to the wrapped object and a factory callable. The factory ensures that `with*()` returns an instance of your class, not the wrapped PSR-7 implementation.

## Building an API Gateway Request

In an API gateway, you might want authentication status directly on the request object:

```php
<?php

use Psr\Http\Message\ServerRequestInterface;

class ApiGatewayRequest implements ServerRequestInterface
{
    use ServerRequestWrapper;

    private ?Member $authenticatedMember = null;

    private function __construct(ServerRequestInterface $request)
    {
        $this->setWrapped($request);
        $this->setFactory([self::class, 'instance']);
    }

    public static function instance(ServerRequestInterface $request): self
    {
        return $request instanceof self
            ? $request
            : new self($request);
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticatedMember !== null;
    }

    public function getAuthenticatedMember(): ?Member
    {
        return $this->authenticatedMember;
    }

    public function setAuthenticatedMember(Member $member): void
    {
        $this->authenticatedMember = $member;
    }

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->getQueryParams()[$name] ?? $default;
    }
}
```

Middleware can authenticate and set the member. Downstream handlers call `$request->isAuthenticated()` without knowing where the authentication data came from.

```php
<?php

use Psr\Http\Message\ServerRequestInterface;

trait ServerRequestWrapper
{
    private ServerRequestInterface $wrapped;

    protected function setWrapped(ServerRequestInterface $request): void
    {
        $this->wrapped = $request;
    }

    private function getWrapped(): ServerRequestInterface
    {
        if (!$this->wrapped instanceof ServerRequestInterface) {
            throw new \UnexpectedValueException(
                'Must setWrapped before using'
            );
        }
        return $this->wrapped;
    }

    public function getServerParams(): array
    {
        return $this->getWrapped()->getServerParams();
    }

    // ... every other PSR-7 ServerRequestInterface method proxied
}
```

Now your custom request class is clean:

```php
<?php

use Psr\Http\Message\ServerRequestInterface;

class ServerRequest implements ServerRequestInterface
{
    use ServerRequestWrapper;

    public function __construct(ServerRequestInterface $request)
    {
        $this->setWrapped($request);
    }

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->getQueryParams()[$name] ?? $default;
    }
}
```

## Handling Immutability with Factories

PSR-7 `with*()` methods return new instances. With HTTP Tortilla, you define a factory callback so that `with*()` calls return your custom class, not the wrapped object.

```php
<?php

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ServerRequestInterface;

trait ServerRequestWrapper
{
    private ?callable $factory = null;

    protected function setFactory(callable $factory): void
    {
        $this->factory = $factory;
    }

    private function viaFactory(MessageInterface $message): MessageInterface
    {
        if (!$this->factory) {
            return $message;
        }
        return ($this->factory)($message);
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        return $this->viaFactory(
            $this->getWrapped()->withCookieParams($cookies)
        );
    }

    // ... all with*() methods use viaFactory
}
```

The factory is typically a static method on your custom class:

```php
<?php

class ServerRequest implements ServerRequestInterface
{
    use ServerRequestWrapper;

    private function __construct(ServerRequestInterface $request)
    {
        $this->setWrapped($request);
        $this->setFactory([self::class, 'instance']);
    }

    public static function instance(ServerRequestInterface $request): self
    {
        if (!$request instanceof self) {
            $request = new self($request);
        }
        return $request;
    }

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->getQueryParams()[$name] ?? $default;
    }
}
```

Now every `with*()` call preserves your custom class through the middleware chain.

## Building Typed Responses

Custom response types make your API handlers cleaner and more expressive.

### HAL+JSON Response

```php
<?php

use Psr\Http\Message\ResponseInterface;

class HalResponse implements ResponseInterface
{
    use ResponseWrapper;

    private array $hal;

    public function __construct(
        array $hal,
        int $status = 200,
        array $headers = [],
    ) {
        $json = json_encode($hal, JSON_THROW_ON_ERROR);
        $response = (new Response($json))
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/hal+json');

        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        $this->hal = $hal;
        $this->setWrapped($response);
    }

    public function getHal(): array
    {
        return $this->hal;
    }
}
```

### Exception as Response

Make exceptions implement ResponseInterface. Middleware can catch them and render directly.

```php
<?php

class MethodNotAllowedProblemResponse extends \RuntimeException implements ResponseInterface
{
    use ResponseWrapper;

    private function __construct()
    {
        $statusCode = 405;
        $title = 'Method Not Allowed';
        $detail = 'HTTP method used is not supported.';

        $this->setWrapped(
            ApiProblemFactory::make($statusCode, $title, ['detail' => $detail])
        );

        parent::__construct("{$title}: {$detail}", $statusCode);
    }

    public static function make(): self
    {
        return new self();
    }
}
```

Now you can throw it from anywhere in your domain logic:

```php
<?php

throw MethodNotAllowedProblemResponse::make();
```

Your PSR-15 middleware catches it and sends the response directly.

## Lazy Responses for Middleware Pipelines

A lazy response holds the data needed to build the response but waits until the last moment to render.

```php
<?php

class LazyResponse implements ResponseInterface
{
    use ResponseWrapper;

    public function __construct(
        public readonly object $resource,
        public readonly Transformer $transformer,
        public readonly int $status = 200,
    ) {}

    public function withResource(object $resource): self
    {
        return new self($resource, $this->transformer, $this->status);
    }
}
```

Middleware in the pipeline can inspect the lazy response, add paging or embedding, and the final rendering happens at the outermost layer.

## URI Wrapping

Route definitions can implement UriInterface. This lets you pass route objects directly to HTTP clients.

```php
<?php

use Psr\Http\Message\UriInterface;

class RouteDefinition implements UriInterface
{
    use UriWrapper;

    public function __construct(
        public readonly string $path,
        public readonly array $params,
    ) {
        $this->setWrapped(new Uri(
            (string) (new UriTemplate($this->path))->render($this->params)
        ));
    }
}
```

## Real-World Use Cases

### API Gateway Middleware

Custom request objects add authentication checks, rate limit headers, and request validation. The middleware chain stays clean because each middleware only adds what it needs.

### Microservice Communication

When one service calls another, wrapping the PSR-7 request adds tracing headers, correlation IDs, and retry logic without coupling to a specific HTTP client.

### Framework Integration

Use HTTP Tortilla to bridge your custom request/response objects with any PSR-7-compatible framework. Symfony, Laravel, Slim, and Mezzio all accept PSR-7 messages.

### Test Mocking

Create lightweight, focused request objects in your test suite without implementing the entire PSR-7 interface.

## Best Practices

- **Use factories for immutability** - Always configure the factory callback so `with*()` methods return your custom class.
- **Keep custom methods focused** - Add only the convenience methods your application needs. Too many methods make the class hard to maintain.
- **Type narrow your responses** - Create distinct response classes for JSON, HTML, HAL, and Problem+JSON. Each is self-documenting.
- **Throw responses, don't return errors** - Exceptions implementing ResponseInterface simplify error handling in middleware.
- **Compose traits carefully** - HTTP Tortilla provides separate traits for requests, responses, URIs. Use only what you need.

## Common Mistakes to Avoid

- **Forgetting the factory** - Without a factory, `with*()` calls return the wrapped object, breaking immutability expectations.
- **Over-wrapping** - You do not need a custom class for every endpoint. One or two well-designed request/response classes suffice.
- **Tight coupling** - Keep your custom classes framework-agnostic. They should depend only on PSR-7 interfaces.
- **Ignoring streams** - PSR-7 bodies are streams. When wrapping responses, ensure the body stream is properly managed.

## Frequently Asked Questions

### What is HTTP Tortilla?

HTTP Tortilla is an open-source PHP library by Tim Lytle that provides traits for wrapping PSR-7 messages. It is available via Composer as `phoneburner/http-tortilla`.

### Do I need HTTP Tortilla if I use Symfony or Laravel?

Frameworks provide their own request/response classes. HTTP Tortilla is most useful when you need custom behavior across framework boundaries or in library code.

### How does HTTP Tortilla handle immutability?

It uses a factory callback. When a `with*()` method is called, the trait passes the result to the factory, which wraps it in your custom class.

### Can I use HTTP Tortilla with PSR-15 middleware?

Absolutely. PSR-15 middleware operates on PSR-7 messages. Custom requests and responses from HTTP Tortilla work directly in any PSR-15 pipeline.

### Does HTTP Tortilla support all PSR-7 interfaces?

The library provides traits for ServerRequestInterface, ResponseInterface, MessageInterface, and UriInterface.

### How do I add custom methods without breaking PSR-7 compliance?

Add your methods directly to the class. The trait handles the PSR-7 interface methods. Your custom methods are separate.

### Is HTTP Tortilla production-ready?

The library has been used in production PHP applications since 2019. It is lightweight, well-tested, and has no external dependencies beyond PSR-7 interfaces.

## Conclusion

HTTP Tortilla solves the fundamental tension between PSR-7's minimal interface and your application's need for convenience methods. By using PHP traits to proxy standard methods, it lets you focus on what makes your request and response objects unique.

Whether you need query parameter helpers, typed API responses, lazy middleware rendering, or exceptions that double as responses, HTTP Tortilla provides the foundation. Your code becomes more expressive, your middleware cleaner, and your HTTP interactions more intentional.

Install it with `composer require phoneburner/http-tortilla` and start building the custom PSR-7 objects your application deserves.
