---
title: "PSRs in Action - Real-World PHP Standards From PHP-FIG"
description: "Explore how PHP-FIG's PSRs apply in real-world PHP development. Learn PSR-4 autoloading, PSR-11 containers, PSR-3 logging, and PSR-7 HTTP messaging with practical code examples."
pubDate: "2023-09-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "PSR", "PHP-FIG", "Standards", "Autoloading", "PSR-4", "PSR-7", "PSR-11", "PSR-3", "Interoperability"]
selected: false
---

The PHP Framework Interoperability Group (PHP-FIG) has produced a series of PHP Standards Recommendations (PSRs) that shape how modern PHP code is written. But if you read the specifications in isolation, they can feel abstract. Namespaces, autoloading, container interfaces, logging contracts — these concepts need concrete examples to make sense.

This article walks through the most widely adopted PSRs using a single cohesive example: a service class that depends on a repository, logs its activity, and is wired together by a container. You will see how each PSR contributes to code that is interchangeable, testable, and framework-agnostic.

## What You'll Learn

- How PSR-4 autoloading eliminates require_once statements
- Building a PSR-11 compatible dependency injection container
- Implementing PSR-3 logging for structured, interchangeable logging
- Understanding PSR-7 HTTP message interfaces
- How PSRs work together to create portable PHP code
- Real-world patterns for framework-agnostic service design

## PSR-4: Autoloading

Before PSR-4, every PHP file started with a series of `require_once` or `require` statements. If you moved a class, you had to update every file that referenced it. If a dependency updated its directory structure, your application broke.

PSR-4 established a convention: the fully qualified class name maps directly to the file path. `App\Services\TheService` lives at `src/Services/TheService.php`. The namespace prefix maps to a base directory, and the rest of the class name maps to the relative path within that directory.

```json
{
    "autoload": {
        "psr-4": {
            "PsrsInAction\\": "src/"
        }
    }
}
```

With this configuration in `composer.json`, Composer generates an autoloader that maps any class under the `PsrsInAction` namespace to the `src/` directory. When you write `new PsrsInAction\Services\TheService()`, Composer loads `src/Services/TheService.php` automatically.

The practical benefit is zero manual file management. You create a class, and the autoloader finds it. You rename a class, and your IDE refactors the file path automatically.

## PSR-11: Container Interface

Dependency injection containers solve the problem of wiring dependencies together. A service needs a repository. The repository needs a database connection. Manually instantiating this chain creates tight coupling and repetitive code.

PSR-11 defines two methods that a container must implement: `get($id)` and `has($id)`. Any framework that conforms to PSR-11 — Laravel's container, Symfony's DI container, PHP-DI — can be used interchangeably.

Here is a minimal PSR-11 container:

```php
namespace PsrsInAction\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

class SimpleContainer implements ContainerInterface
{
    private array $bindings = [];
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (!isset($this->instances[$id])) {
            if (!isset($this->bindings[$id])) {
                throw new class($id) extends \RuntimeException
                    implements NotFoundExceptionInterface {};
            }

            $this->instances[$id] =
                $this->bindings[$id]($this);
        }

        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }
}
```

The `get` method lazily instantiates services and caches them as singletons. The `has` method checks whether a binding exists. Both methods throw PSR-11-specific exceptions when the requested entry is not found.

Wiring the container:

```php
$container = new SimpleContainer();

$container->set(
    RepositoryInterface::class,
    fn () => new DatabaseRepository(
        new DatabaseConnection('mysql:host=localhost;dbname=app')
    )
);

$container->set(
    ServiceInterface::class,
    fn (ContainerInterface $c) => new TheService(
        $c->get(RepositoryInterface::class)
    )
);

$service = $container->get(ServiceInterface::class);
```

The repository is created once and injected into the service. If another service also needs the repository, it receives the same instance.

## PSR-3: Logger Interface

Logging is a cross-cutting concern. Every service logs errors, warnings, and informational messages. But tying your code to a specific logging library — Monolog, Log4PHP, or a framework's built-in logger — makes it impossible to switch later.

PSR-3 defines eight log levels — `emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug` — and a `LoggerInterface` that any logger can implement.

Here is the service class that uses PSR-3 logging:

```php
namespace PsrsInAction\Services;

use PsrsInAction\Contracts\ServiceInterface;
use PsrsInAction\Contracts\RepositoryInterface;
use Psr\Log\LoggerInterface;

class TheService implements ServiceInterface
{
    public function __construct(
        private RepositoryInterface $repository,
        private ?LoggerInterface $logger = null,
    ) {}

    public function process(string $data): array
    {
        $this->logger?->info(
            'Processing data', ['input' => $data]
        );

        try {
            $result = $this->repository->findByData($data);

            $this->logger?->info(
                'Data processed successfully',
                ['count' => count($result)]
            );

            return $result;
        } catch (\Throwable $e) {
            $this->logger?->error(
                'Failed to process data',
                [
                    'input' => $data,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            throw $e;
        }
    }
}
```

The logger is optional — the constructor parameter defaults to null. This prevents a logging failure from breaking the application. The nullsafe operator `?->` ensures that calling methods on null is safe.

The context array is the key to PSR-3's power. Every log message includes structured data that can be searched, filtered, and analyzed by log aggregation tools.

```php
// Wiring the logger into the container
$container->set(
    LoggerInterface::class,
    fn () => (new \Monolog\Logger('app'))
        ->pushHandler(
            new \Monolog\Handler\StreamHandler(
                'php://stdout', \Monolog\Level::Debug
            )
        )
);

$container->set(
    ServiceInterface::class,
    fn (ContainerInterface $c) => new TheService(
        $c->get(RepositoryInterface::class),
        $c->get(LoggerInterface::class)
    )
);
```

If you later decide to switch from Monolog to a different logger, you change only the container binding. The service class remains untouched.

## PSR-7: HTTP Message Interfaces

Modern PHP applications handle HTTP requests and responses extensively. PSR-7 standardizes how these messages are represented. A `ServerRequestInterface` represents the incoming request. A `ResponseInterface` represents the outgoing response. Both are immutable — modifying them returns a new instance.

```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ActionHandler
{
    public function __construct(
        private ServiceInterface $service,
        private LoggerInterface $logger,
    ) {}

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $data = $request->getParsedBody()['data'] ?? '';

        try {
            $result = $this->service->process($data);

            $response->getBody()->write(
                json_encode(['success' => true, 'data' => $result])
            );

            return $response->withStatus(200)
                ->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $this->logger->error(
                'Action failed', ['error' => $e->getMessage()]
            );

            $response->getBody()->write(
                json_encode(['success' => false])
            );

            return $response->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
```

The immutability of PSR-7 messages means that `withStatus` and `withHeader` return new response objects. The original response is never modified. This prevents bugs where a middleware layer accidentally mutates a request that subsequent code still needs.

## PSR-15: HTTP Middleware

Building on PSR-7's HTTP message interfaces, PSR-15 defines how middleware components process requests and responses. Middleware is the backbone of modern PHP frameworks — it handles authentication, CORS, rate limiting, logging, and content negotiation.

```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Authenticator $authenticator,
        private LoggerInterface $logger,
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $token = $request->getHeaderLine('Authorization');

        if (empty($token)) {
            $this->logger->warning(
                'Request without authorization header',
                ['method' => $request->getMethod(),
                 'path' => (string) $request->getUri()]
            );

            return new JsonResponse(
                ['error' => 'Authentication required'],
                401
            );
        }

        try {
            $user = $this->authenticator->validate($token);
            $request = $request->withAttribute('user', $user);

            return $handler->handle($request);
        } catch (AuthenticationException $e) {
            $this->logger->error(
                'Authentication failed',
                ['error' => $e->getMessage()]
            );

            return new JsonResponse(
                ['error' => 'Invalid token'],
                401
            );
        }
    }
}
```

The `process` method receives the request and a handler. If authentication succeeds, it passes the request (with the authenticated user attached) to the next handler. If it fails, it returns an error response immediately. This composable structure lets you build complex request processing pipelines from simple middleware components.

## PSR-14: Event Dispatcher

Event-driven architecture separates the code that triggers an action from the code that responds to it. PSR-14 standardizes this pattern.

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class OrderPlacedEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        public readonly Order $order,
        public readonly User $customer,
    ) {}

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}

class SendOrderConfirmationListener
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(
        OrderPlacedEvent $event
    ): void {
        $this->logger->info(
            'Sending order confirmation',
            ['order_id' => $event->order->id]
        );

        $this->mailer->send(
            $event->customer->email,
            'order_confirmation',
            ['order' => $event->order]
        );
    }
}
```

Listeners are registered with a provider, and the dispatcher iterates through them when an event is dispatched. This decouples the core business logic from side effects like email notifications, logging, and cache invalidation.

## How the PSRs Work Together

The real value of PSRs emerges when they are combined. The service uses PSR-3 for logging, PSR-11 for dependency resolution, and PSR-7 for HTTP interaction. PSR-4 makes the entire codebase loadable without manual file management.

A framework-agnostic service built on PSRs can be:
- Used in Laravel with its PSR-11 container and PSR-7 implementation
- Used in Symfony with its DI container and HTTP kernel
- Used in a Slim application with its PSR-7 implementation
- Tested with PHPUnit without loading any framework

```php
// Test the service with a mock repository
$repository = $this->createMock(RepositoryInterface::class);
$logger = $this->createMock(LoggerInterface::class);

$service = new TheService($repository, $logger);

$repository->method('findByData')
    ->willReturn(['result1', 'result2']);

$result = $service->process('test-data');

$this->assertCount(2, $result);
```

The test is pure PHP. No framework bootstrapping. No container configuration. No HTTP server. The PSR abstractions make this isolation possible.

## Real-World Use Cases

**Framework-Agnostic Packages.** Most popular PHP packages implement PSR interfaces. `phpunit/phpunit` uses PSR-4 autoloading. `monolog/monolog` implements PSR-3. `guzzlehttp/psr7` provides a PSR-7 implementation. Packages that depend on PSR interfaces rather than concrete implementations work across any PHP framework.

**Middleware Stacks.** PSR-15 builds on PSR-7 to define HTTP middleware interfaces. A middleware stack processes requests through a pipeline of handlers, each adding authentication, logging, rate limiting, or CORS headers.

**Service Containers.** Laravel's container, Symfony's DI container, and PHP-DI all implement PSR-11. This means you can write container-aware code that works across frameworks.

**Bridge Libraries.** Libraries like `ocramius/proxy-manager` and `doctrine/common` use PSRs to provide functionality that integrates with any framework.

## Best Practices

**Code to interfaces, not implementations.** Your classes should depend on `Psr\Log\LoggerInterface`, not `Monolog\Logger`. Your controllers should type-hint `Psr\Http\Message\ServerRequestInterface`, not `Symfony\Component\HttpFoundation\Request`.

**Make loggers optional where appropriate.** PSR-3 loggers should be optional constructor parameters when logging is not critical to the operation. Use the nullsafe operator `?->` for clean method calls.

**Treat PSR-7 messages as immutable.** Never modify a request or response in place. Always use `with*` methods that return new instances.

**Use PSR-4 for all custom code.** If your project does not already use PSR-4 autoloading, migrate immediately. The benefits for long-term maintainability are enormous.

## Common Mistakes to Avoid

**Ignoring the context array in PSR-3.** A log message without context is nearly useless for debugging. Always include relevant identifiers, input data excerpts, and error details.

**Modifying PSR-7 messages in place.** PSR-7 messages are designed to be immutable. Modifying them directly violates the contract and causes subtle bugs in middleware pipelines.

**Hardcoding container lookups.** Using `$container->get()` inside your service classes creates an invisible dependency on the container itself. This is known as the Service Locator anti-pattern. Always inject dependencies through constructors.

**Forgetting to configure PSR-4 in composer.json.** Without proper autoloading configuration, your beautifully namespaced classes will not be found at runtime.

## Frequently Asked Questions

**Do I need to implement PSRs from scratch?**
No. Use existing implementations. `guzzlehttp/psr7` for PSR-7 messages. `monolog/monolog` for PSR-3 logging. `php-di/php-di` or `pimple/pimple` for PSR-11 containers.

**Are PSRs backward compatible?**
Major PSR versions can introduce breaking changes. PSR-7 was a significant departure from how PHP handled HTTP messages. Always check the version compatibility when upgrading.

**Does Laravel implement PSRs?**
Laravel's container implements PSR-11. Laravel's HTTP layer is built on Symfony's HTTP Kernel, which uses PSR-7 through bridges. Laravel's logging can use PSR-3 loggers.

**Should I write PSR specifications for my own packages?**
Only if your package needs to be framework-agnostic. If you are building for a specific framework, you can use that framework's conventions.

**What is the most important PSR to adopt first?**
PSR-4 autoloading. It is the foundation that makes all other PSRs practical. Without autoloading, you are still managing file inclusion manually.

## Conclusion

PHP-FIG's PSRs are not academic specifications. They are practical tools that make PHP code portable, testable, and maintainable. PSR-4 eliminates manual autoloading. PSR-11 standardizes dependency injection. PSR-3 provides structured logging. PSR-7 brings type safety to HTTP handling.

When you build your code on PSR interfaces, you are not tied to a single framework. Your services work in Laravel, Symfony, Slim, or plain PHP. Your packages are usable by the entire PHP community. Your tests run without framework bootstrapping.

Start with PSR-4 autoloading and PSR-3 logging. Those two alone will transform how you structure PHP code. Then explore PSR-11 containers and PSR-7 HTTP messages as your applications grow in complexity.
