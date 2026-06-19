---
title: "PSR-17 HTTP Factories Guide - Standard PHP HTTP Objects"
description: "Learn PSR-17 HTTP factory interfaces for PSR-7. Master RequestFactory, ResponseFactory, StreamFactory, UriFactory, UploadedFileFactory, and ServerRequestFactory in PHP."
pubDate: "2023-03-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PSR-17", "HTTP Factories", "PSR-7", "PHP-FIG", "PHP Standards", "HTTP Messages", "Guzzle", "Diactoros", "PHP Interop"]
selected: false
---

Every PHP application deals with HTTP messages. Requests come in from browsers, API clients, or background jobs. Responses go back out with status codes, headers, and bodies. For years, each framework and library invented its own representation of these messages — creating an interoperability nightmare.

PSR-7 solved the representation problem by defining standard interfaces for HTTP messages. But it left a gap: how do you create PSR-7 objects without coupling your code to a specific implementation? If your application accepts a `RequestInterface` parameter, something has to instantiate that request. That something should not be hard-coded to Guzzle, Diactoros, or Slim's internal classes.

PSR-17 fills this gap. It defines factory interfaces for creating PSR-7 objects — requests, responses, streams, URIs, uploaded files, and server requests. With PSR-17, you write code against interfaces for both the HTTP messages and their creation. Your application never imports a concrete vendor class.

This guide covers all six PSR-17 factory interfaces, how they work together with PSR-7, practical implementation patterns from real libraries, and strategies for integrating them into your PHP applications.

## What You'll Learn

- The six PSR-17 factory interfaces and their purposes
- How PSR-17 complements PSR-7 for complete HTTP message handling
- Creating requests, responses, streams, URIs, and uploaded files through factories
- Implementing server request creation from superglobals
- How Guzzle and Diactoros implement PSR-17
- Best practices for factory interface segregation

## The Problem PSR-17 Solves

Before PSR-17, creating a PSR-7 request meant either calling a concrete constructor or writing ad-hoc factory code scattered throughout your application:

```php
// Without PSR-17 — coupled to implementation
$request = new GuzzleHttp\Psr7\Request('GET', 'https://api.example.com/users');
$response = new GuzzleHttp\Psr7\Response(200, [], '{"status":"ok"}');
```

This works until you need to switch implementations. Maybe you start with Guzzle for development but want to use a lighter library in production. Maybe a security audit requires a different HTTP message implementation. Every `new` call becomes a refactoring target.

PSR-17 solves this by defining factory interfaces. Your code depends on `RequestFactoryInterface` instead of a concrete class:

```php
// With PSR-17 — decoupled from implementation
$request = $this->requestFactory->createRequest('GET', 'https://api.example.com/users');
$response = $this->responseFactory->createResponse(200, null, '{"status":"ok"}');
```

The factory is injected through constructor dependency injection. You control which concrete factory to use at the composition root of your application.

## The Six PSR-17 Factory Interfaces

### RequestFactoryInterface

Creates `RequestInterface` objects. The `$uri` parameter accepts either a string or a `UriInterface` instance:

```php
namespace Psr\Http\Message;

interface RequestFactoryInterface
{
    public function createRequest(
        string $method,
        $uri
    ): RequestInterface;
}
```

**Explanation:** The `$uri` parameter is not strictly type-hinted to `UriInterface` by design. Requiring `UriInterface` would force every caller to either have access to a `UriFactoryInterface` or couple the `RequestFactoryInterface` implementation to a `UriFactoryInterface`. Accepting a string keeps the interface simple while allowing implementations to parse the string into a proper URI object internally.

### ResponseFactoryInterface

Creates `ResponseInterface` objects with optional status code and reason phrase:

```php
namespace Psr\Http\Message;

interface ResponseFactoryInterface
{
    public function createResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ): ResponseInterface;
}
```

**Explanation:** The `$reasonPhrase` parameter is optional. When empty, the implementation should use the standard reason phrase for the given status code (e.g., "OK" for 200, "Not Found" for 404). This aligns with PSR-7's `ResponseInterface::withStatus()`, which pairs status codes with reason phrases.

### StreamFactoryInterface

Creates `StreamInterface` objects from various sources:

```php
namespace Psr\Http\Message;

interface StreamFactoryInterface
{
    public function createStream(
        string $content = ''
    ): StreamInterface;

    public function createStreamFromFile(
        string $filename,
        string $mode = 'r'
    ): StreamInterface;

    public function createStreamFromResource(
        $resource
    ): StreamInterface;
}
```

**Explanation:** Streams can originate from string content, file paths, or PHP resources. The standard recommends that `createStream()` use a temporary stream (`php://temp`, `r+`) to make it writable and seekable. `createStreamFromFile()` opens a file with the specified mode. `createStreamFromResource()` wraps an existing PHP resource.

### UploadedFileFactoryInterface

Creates `UploadedFileInterface` objects from streams:

```php
namespace Psr\Http\Message;

interface UploadedFileFactoryInterface
{
    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface;
}
```

**Explanation:** Uploaded files are always backed by a stream created via `StreamFactoryInterface`. This ensures consistency — the stream is always a PSR-7 `StreamInterface` rather than a raw PHP `$_FILES` entry. The `$error` parameter defaults to `UPLOAD_ERR_OK` (value 0).

### UriFactoryInterface

Creates `UriInterface` objects from URI strings:

```php
namespace Psr\Http\Message;

interface UriFactoryInterface
{
    public function createUri(
        string $uri = ''
    ): UriInterface;
}
```

**Explanation:** This factory must produce URIs suitable for both client requests (absolute URLs like `https://example.com/path`) and server requests (relative paths or `$_SERVER['REQUEST_URI']` values). The implementation parses the string, extracts scheme, host, port, path, query, and fragment, and returns a proper `UriInterface`.

### ServerRequestFactoryInterface

Creates `ServerRequestInterface` objects:

```php
namespace Psr\Http\Message;

interface ServerRequestFactoryInterface
{
    public function createServerRequest(
        string $method,
        $uri,
        array $serverParams = []
    ): ServerRequestInterface;
}
```

**Explanation:** Server requests are special because they carry server parameters (`$_SERVER`-like data). The `$serverParams` array is required at creation time because `ServerRequestInterface` has no mutator method for server parameters. Notably, there is no factory method for creating a server request from superglobals — this is intentionally left to implementations because not all environments have superglobals (e.g., Swoole, ReactPHP, CLI workflows).

## Real-World Implementations

### Guzzle PSR-7

Guzzle's implementation does not expose a dedicated `ServerRequestFactoryInterface`. Instead, the `ServerRequest` class provides a static `fromGlobals()` method on the concrete class:

```php
use GuzzleHttp\Psr7\ServerRequest;

public static function fromGlobals(): ServerRequestInterface
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $headers = getallheaders();
    $uri = self::getUriFromGlobals();
    $body = new CachingStream(
        new LazyOpenStream('php://input', 'r+')
    );
    $protocol = isset($_SERVER['SERVER_PROTOCOL'])
        ? (new self())->getProtocolVersion()
        : '1.1';

    $serverRequest = new ServerRequest(
        $method,
        $uri,
        $headers,
        $body,
        $protocol,
        $_SERVER
    );

    return $serverRequest
        ->withCookieParams($_COOKIE)
        ->withQueryParams($_GET)
        ->withParsedBody($_POST)
        ->withUploadedFiles(
            self::normalizeFiles($_FILES)
        );
}
```

**Explanation:** Guzzle's approach is pragmatic — it collects everything from superglobals in one call. The `getUriFromGlobals()` method reconstructs the URI from `$_SERVER['HTTP_HOST']`, `$_SERVER['REQUEST_URI']`, `$_SERVER['HTTPS']`, and related values. The body is lazily opened from `php://input` to avoid reading it before the application is ready.

### Laminas Diactoros

Diactoros (formerly Zend Diactoros) takes a different approach with a proper `ServerRequestFactory`:

```php
use Laminas\Diactoros\ServerRequestFactory;

public static function fromGlobals(
    ?array $server = null,
    ?array $query = null,
    ?array $body = null,
    ?array $cookies = null,
    ?array $files = null,
    ?FilterServerRequestInterface $requestFilter = null
): ServerRequest {
    $requestFilter = $requestFilter
        ?: FilterUsingXForwardedHeaders::trustReservedSubnets();

    $server = normalizeServer(
        $server ?: $_SERVER,
        is_callable(self::$apacheRequestHeaders)
            ? self::$apacheRequestHeaders
            : null
    );

    $files = normalizeUploadedFiles($files ?: $_FILES);
    $headers = marshalHeadersFromSapi($server);

    if (null === $cookies
        && array_key_exists('cookie', $headers)
    ) {
        $cookies = parseCookieHeader($headers['cookie']);
    }

    return $requestFilter(new ServerRequest(
        $server,
        $files,
        UriFactory::createFromSapi($server, $headers),
        marshalMethodFromSapi($server),
        'php://input',
        $headers,
        $cookies ?: $_COOKIE,
        $query ?: $_GET,
        $body ?: $_POST,
        marshalProtocolVersionFromSapi($server)
    ));
}
```

**Explanation:** Diactoros's `fromGlobals()` accepts optional arrays for every superglobal, allowing you to override data during testing or in non-SAPI environments. The `FilterServerRequestInterface` parameter enables trusted proxy header filtering. Internal helpers like `marshalHeadersFromSapi()`, `normalizeServer()`, and `marshalMethodFromSapi()` handle the platform-specific extraction logic.

## Integrating PSR-17 Factories Into Your Application

### Dependency Injection Setup

The cleanest approach is to bind PSR-17 interfaces to concrete implementations in your DI container:

```php
// Using PHP-DI or any PSR-11 container
return [
    RequestFactoryInterface::class => function () {
        return new GuzzleHttp\Psr7\HttpFactory();
    },
    ResponseFactoryInterface::class => function () {
        return new GuzzleHttp\Psr7\HttpFactory();
    },
    StreamFactoryInterface::class => function () {
        return new GuzzleHttp\Psr7\HttpFactory();
    },
];
```

Guzzle's `HttpFactory` implements all PSR-17 interfaces in a single class. Diactoros provides separate factory classes.

### Creating Middleware That Uses Factories

```php
class JsonResponseMiddleware
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse();
        $body = $this->streamFactory->createStream(
            json_encode(['status' => 'ok'])
        );

        return $response
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json');
    }
}
```

**Explanation:** This middleware never references a concrete class. It requests a response factory and a stream factory through constructor injection. You can swap the implementation without changing this code.

## Best Practices for PSR-17

### Segregate Factory Interfaces

While Guzzle's `HttpFactory` implements all six interfaces in one class, the PSR-17 meta document recommends keeping them separate. Segregated factories produce smaller, more focused classes that follow the Interface Segregation Principle:

```php
class AppRequestFactory implements RequestFactoryInterface
{
    public function createRequest(
        string $method,
        $uri
    ): RequestInterface {
        // Custom request creation logic
    }
}
```

Separate factories are easier to test, swap individually, and compose in different contexts.

### Use Factories in Library Code

If you are writing a reusable PHP library, accept PSR-17 factories in constructors rather than creating HTTP messages internally. This lets your users control which HTTP implementation to use:

```php
class ApiClient
{
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private HttpClient $httpClient,
    ) {}
}
```

### Bridge Between Frameworks

PSR-17 serves as an integration point between frameworks. A Symfony controller can produce a PSR-7 response through a factory, which a middleware stack processes before converting back to the framework's native response.

### Handle Streams Properly

Streams created by `StreamFactoryInterface` should be rewound before reading. Always call `rewind()` or use `__toString()` (which internally rewinds) after writing to a stream.

```php
$stream = $this->streamFactory->createStream('initial content');
$stream->write(' appended content');
$stream->rewind();
echo $stream->getContents(); // 'initial content appended content'
```

## Common Mistakes to Avoid

### Coupling to Concrete Implementations

The most common mistake is importing a vendor class directly instead of using the factory interface. Every `new ServerRequest(...)` call is a missed opportunity for decoupling.

### Ignoring ServerRequest Server Params

The `$serverParams` array in `ServerRequestFactoryInterface::createServerRequest()` is required because you cannot set server parameters after creation. Always provide the full `$_SERVER` array or its equivalent.

### Using Superglobals in Async Environments

Swoole and ReactPHP run in event loops where superglobals may not be populated. Never hard-code `$_GET`, `$_POST`, or `$_SERVER` references in library code. Accept them through factory methods.

### Forgetting Stream Wrapping

Uploaded files from `UploadedFileFactoryInterface` expect a `StreamInterface`, not a raw file path. Always create the stream through `StreamFactoryInterface` first.

### Creating Multiple Factory Instances

Instantiating a new factory for every request adds unnecessary overhead. Register factories as singletons in your DI container and reuse them.

## Real-World Use Cases for PSR-17

### Framework-Agnostic Libraries

Any PHP library that makes HTTP requests or returns HTTP responses benefits from PSR-17. HTTP clients, OAuth implementations, webhook handlers, and API SDKs can all accept factory interfaces.

### Middleware Pipelines

PSR-15 middleware handlers need to create responses. With PSR-17 factories injected into middleware, the pipeline remains framework-agnostic.

### Testing and Mocking

PSR-17 interfaces make it trivial to mock HTTP message creation in tests:

```php
$factory = $this->createMock(RequestFactoryInterface::class);
$factory->method('createRequest')->willReturn(
    new Request('GET', 'https://example.com')
);
```

### Multi-Environment Deployments

Applications running across traditional web servers, CLI daemons, and async runtimes can adapt server request creation per environment while sharing the same business logic.

## Frequently Asked Questions

### What is the relationship between PSR-7 and PSR-17?

PSR-7 defines interfaces for HTTP messages (Request, Response, Stream, Uri, UploadedFile). PSR-17 defines factory interfaces for creating those objects. You use both together — PSR-17 to create, PSR-7 to interact.

### Can I use PSR-17 without PSR-7?

No. PSR-17 factories return PSR-7 interfaces. You must have a PSR-7 implementation available. Guzzle PSR-7 and Laminas Diactoros both provide PSR-7 implementations alongside their PSR-17 factories.

### Should I use a single factory class or separate classes?

The PSR-17 meta document recommends separate classes for each factory interface. This follows the Interface Segregation Principle and keeps each class focused on a single responsibility. However, a combined class like Guzzle's `HttpFactory` is convenient for simple use cases.

### How do I create a ServerRequest in a CLI environment?

CLI environments lack superglobals. Use `ServerRequestFactoryInterface::createServerRequest()` with explicit parameters. Set `$serverParams` to `['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']` or whatever your CLI command defines.

### Does PSR-17 work with async PHP frameworks?

Yes. Swoole and ReactPHP implement PSR-17 factories that work with their async contexts. The `ServerRequestFactoryInterface` receives server parameters explicitly rather than reading from superglobals, which is essential in non-blocking environments.

### Why does `createRequest()` accept a mixed `$uri` type?

The Meta document explains that requiring `UriInterface` would force consumers to also require `UriFactoryInterface`. Accepting a string simplifies the API while allowing implementations to parse the string into a URI internally.

### How do I change the implementation later?

If your code depends only on PSR-17 interfaces, changing implementations is a one-line change in your DI container configuration. Swap the concrete class binding, and everything works.

### Is PSR-17 compatible with popular frameworks?

Laravel, Symfony, and Slim all support PSR-17 through their HTTP abstractions. Laravel's HTTP client uses Guzzle internally. Symfony's HttpFoundation can convert to and from PSR-7 messages.

## Conclusion

PSR-17 completes the HTTP message standardization story that PSR-7 started. By defining factory interfaces for creating requests, responses, streams, URIs, uploaded files, and server requests, it enables truly decoupled HTTP handling in PHP applications.

The practical benefit is straightforward: write your code against PSR-7 and PSR-17 interfaces, inject the concrete implementation at the composition root, and never touch vendor-specific HTTP classes again. Your code becomes portable across frameworks, testable with mocked factories, and adaptable to any environment — traditional web servers, CLI tools, or async runtimes.

Start by choosing a PSR-17 implementation (Guzzle PSR-7 and Laminas Diactoros are the most mature), set up factory bindings in your DI container, and refactor your HTTP-creating code to accept factory interfaces. The decoupling you gain is immediately visible when you swap implementations or write your first mock-based test.
