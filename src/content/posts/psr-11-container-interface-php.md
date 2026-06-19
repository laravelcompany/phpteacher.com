---
title: "PSR-11 Container Interface - Dependency Injection Standard for PHP"
description: "Understand the PSR-11 Container Interface and how it standardizes dependency injection in PHP. Practical examples for modern PHP applications."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-11-26 21:00:00"
tags: ["PSR-11", "PHP", "Dependency Injection", "Container", "PHP-FIG", "PSR"]
---

PSR-11 defines a standard interface for dependency injection containers. It's deliberately minimal: two methods, one interface. That simplicity makes it powerful — any PSR-11 container can be swapped for another without changing your application code.

## The Interface

```php
namespace Psr\Container;

interface ContainerInterface
{
    public function get(string $id): mixed;
    public function has(string $id): bool;
}
```

That's the entire specification.

- `get(string $id)` returns the entry identified by `$id`. It can return anything — an object, a scalar, an array. It throws `NotFoundExceptionInterface` if the identifier is not found and `ContainerExceptionInterface` if something goes wrong during retrieval.
- `has(string $id)` returns `true` if the container knows about the entry, `false` otherwise. If `has` returns `false`, `get` MUST throw `NotFoundExceptionInterface`.

The identifier can be any PHP-legal string of at least one character. Common practice uses the fully-qualified class name:

```php
$container->get(LoggerInterface::class);
```

## Container as Service Locator (Don't Do This)

PSR-11 explicitly warns against passing the container to your objects as a service locator:

```php
class ServiceLocatorExample
{
    protected $service;

    public function __construct(ContainerInterface $cont)
    {
        $this->service = $cont->get('service');
    }
}
```

Problems with this approach:

- The service must be registered under the exact string `'service'`, which could conflict with other entries
- Testing requires registering mock objects in the real container instead of straightforward constructor injection
- Dependencies are hidden — reading the constructor doesn't tell you what the class needs

## Container as Dependency Injector (Do This)

The recommended approach is standard constructor injection:

```php
class DependencyInjectionExample
{
    public function __construct(
        protected DatabaseServiceInterface $dbService,
    ) {}
}
```

The framework retrieves `DatabaseServiceInterface` from the container and injects it. The class clearly declares its dependencies. Testing is trivial:

```php
$mock = $this->createMock(DatabaseServiceInterface::class);
$example = new DependencyInjectionExample($mock);
```

No container needed in tests.

## When the Container Must Be Injected

PSR-11's meta document identifies legitimate cases for injecting the container — specifically routers and factories.

### Router Example

A router derives entry identifiers from URL paths and uses the container to fetch controllers:

```php
class Router
{
    public function __construct(
        protected ContainerInterface $container,
    ) {}

    public function handle(Request $request): Response
    {
        $identifier = $this->getControllerIdentifier($request->getUrl());
        $controller = $this->container->get($identifier);
        // ...
    }
}
```

The router doesn't know what controllers exist — it maps URLs to identifiers and delegates to the container.

### Factory Example

A factory needs the container to provide dependencies for the objects it creates:

```php
interface FactoryInterface
{
    public function newInstance(): object;
}

class DocumentFactory implements FactoryInterface
{
    public function __construct(
        protected ContainerInterface $container,
    ) {}

    public function newInstance(): DocumentRepository
    {
        return new DocumentRepository(
            $this->container->get('document_store'),
        );
    }
}
```

The factory doesn't care how `document_store` is created. It only knows its repository needs one.

## Implementing a Container

PSR-11 doesn't specify *how* a container works internally. Implementations vary widely:

### Array-based

```php
class SimpleContainer implements ContainerInterface
{
    private array $entries = [];

    public function set(string $id, mixed $entry): void
    {
        $this->entries[$id] = $entry;
    }

    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new NotFoundException("Entry '$id' not found");
        }

        return $this->entries[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->entries[$id]);
    }
}
```

### Factory-based

```php
class FactoryContainer implements ContainerInterface
{
    /**
     * @param array<string, callable> $factories
     */
    public function __construct(
        private array $factories = [],
    ) {}

    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new NotFoundException("Entry '$id' not found");
        }

        return ($this->factories[$id])($this);
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }
}
```

### Auto-wiring via Reflection

```php
class AutoWireContainer implements ContainerInterface
{
    public function __construct(
        private array $definitions = [],
    ) {}

    public function get(string $id): mixed
    {
        if (isset($this->definitions[$id])) {
            return ($this->definitions[$id])($this);
        }

        if (! class_exists($id)) {
            throw new NotFoundException("Cannot resolve '$id'");
        }

        return $this->autowire($id);
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || class_exists($id);
    }

    private function autowire(string $class): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $params = array_map(
            fn(ReflectionParameter $param) => $this->get(
                $param->getType()->getName()
            ),
            $constructor->getParameters(),
        );

        return $reflection->newInstanceArgs($params);
    }
}
```

## Popular Implementations

| Container | Auto-wiring | Performance | Configuration |
|-----------|------------|-------------|---------------|
| PHP-DI | Yes | Moderate | PHP attributes, arrays, YAML |
| Symfony DI | Yes | Fast | YAML, XML, PHP |
| The PHP League Container | No | Fast | Array |
| Pimple | No | Fast | Closure |

PHP-DI offers attribute-based definitions in PHP 8+:

```php
use DI\Attribute\Inject;

class UserService
{
    public function __construct(
        #[Inject]
        private readonly UserRepository $repository,
    ) {}
}
```

Symfony DI compiles container configuration into plain PHP classes for maximum performance in production.

## Testing with Containers

When testing classes that receive the container:

```php
class RouterTest extends TestCase
{
    public function test_router_resolves_controller(): void
    {
        $controller = $this->createMock(HomeController::class);
        $controller->expects($this->once())
            ->method('__invoke')
            ->willReturn(new Response());

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('home_controller')
            ->willReturn($controller);

        $router = new Router($container);
        $request = new Request(['url' => '/']);

        $response = $router->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

Mock the container interface. Verify it's asked for the correct identifier and returns the expected object.

## Key Takeaways

- PSR-11 is two methods: `get` and `has`
- Prefer constructor injection over service locator
- Routers and factories are legitimate exceptions
- Different containers suit different performance and complexity needs
- Always mock the interface in tests — never use a real container

The Container Interface abstraction lets your framework choose the implementation while your code stays portable. That's the value of a well-designed PSR.
