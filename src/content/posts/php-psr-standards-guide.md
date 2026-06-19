---
title: "PHP PSR Standards Guide - PSR-0, PSR-1, and Improving Developer Experience"
description: "Learn the PHP PSR (PHP Standard Recommendations) from PSR-0 to PSR-12. Understand autoloading, coding standards, and how PSRs improve the PHP developer experience."
pubDate: "2022-03-22 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PSR", "PHP Standards", "PSR-0", "PSR-1", "PHP-FIG", "Autoloading", "Coding Standards", "Best Practices"]
selected: false
---

Ever tried to integrate a Symfony form into a Laravel application? Or plug a Doctrine ORM entity into a Zend Framework controller? Before PSRs, that was a nightmare of adapter classes, wrapper libraries, and tears.

PHP Standard Recommendations—PSRs—changed everything. They're the reason you can install any Composer package and have it *just work*. They're why modern PHP frameworks interoperate. They're the invisible scaffolding holding the entire PHP ecosystem together.

This guide walks through every major PSR, from the deprecated PSR-0 to the current PSR-12 standard, with practical PHP 8.1+ code examples. You'll understand what each PSR does, why it exists, and how it makes your daily development life better.

## What Is PHP-FIG?

The PHP Framework Interoperability Group (PHP-FIG) formed in 2009. A group of framework authors—representing Symfony, Zend Framework, CakePHP, Drupal, and others—sat down to solve a shared problem: their frameworks couldn't talk to each other.

PHP-FIG is not a governing body. It has no authority over the PHP language or its maintainers. It's a voluntary working group that proposes standards, votes on them, and publishes recommendations. Anyone can participate through the mailing list, GitHub repositories, and regular meetings.

A PSR starts as a proposal, goes through draft phases, receives community feedback, and finally reaches an acceptance vote. Once accepted, it enters a "review" phase where the community adopts it and PHP-FIG may issue revisions. Some PSRs eventually get deprecated and superseded by better ones.

## PSR-0: The Autoloading Standard (Deprecated)

Before PSR-0, every framework had its own autoloading convention. Symfony loaded classes one way. Zend Framework another. If you wanted to use libraries from both, you'd register multiple autoloaders and pray they didn't collide.

PSR-0 defined a mapping between fully-qualified class names and file paths:
- Each namespace segment corresponds to a directory
- Each class name maps to a PHP file
- Underscores in class names became directory separators

```php
// Before PSR-0 — manual includes everywhere
require_once 'path/to/Symfony/Component/HttpFoundation/Request.php';
require_once 'path/to/Zend/Http/Request.php';

// PSR-0 convention:
// \Symfony\Component\HttpFoundation\Request
// → path/Symfony/Component/HttpFoundation/Request.php
//
// \Zend_Http_Request (underscore style)
// → path/Zend/Http/Request.php
```

The underscore-as-directory-separator rule was a bridge for legacy PHP 5.2-era code that didn't use namespaces. PSR-0 worked, but it had quirks. Nesting could get deep. The underscore rule confused newcomers. And the leading vendor directory convention (`Vendor_ Package_Class`) felt clunky.

PSR-0 was officially deprecated in 2014. Do not use it for new code.

## PSR-1: Basic Coding Standard

PSR-1 lays the foundation for all subsequent coding standards. It's short, simple, and covers four areas.

### PHP Tags

Use `<?php` or `<?=` for short echo tags. Never use short open tags (`<?`) or ASP-style tags (`<%`).

```php
<?php
// This is correct

<?= $variable ?>
// This is correct for echo statements
```

### Side Effects

A file should either declare symbols (classes, functions, constants) or execute logic with side effects, but never both. This rule ensures autoloaders can safely include files without triggering unintended behavior.

```php
<?php
// Declare — no side effects
namespace App\Service;

class UserMailer
{
    public function send(string $email, string $message): bool
    {
        return mail($email, 'Notification', $message);
    }
}
```

```php
<?php
// Side effects — no declarations
$config = require 'config.php';
$mailer = new App\Service\UserMailer();

foreach ($config['users'] as $user) {
    $mailer->send($user['email'], 'Welcome!');
}
```

Never mix the two in a single file.

### Autoloading and Namespaces

PHP 5.3 introduced namespaces. PSR-1 requires you to use them. Each class must be in a namespace with a vendor-level prefix.

```php
<?php
// PSR-1 compliant namespace structure
namespace PhpTeacher\Blog\Domain;

class Post
{
    public function __construct(
        private string $title,
        private string $body,
    ) {}
}
```

The vendor prefix (`PhpTeacher`) prevents collisions with other packages.

### Class Names

Class names must follow `StudlyCaps` convention. Constants must be `UPPERCASE_WITH_UNDERSCORES`. Method names use `camelCase`.

```php
class BlogPostController  // StudlyCaps
{
    public const MAX_POSTS_PER_PAGE = 20;  // UPPER_CASE

    public function listPosts(): array  // camelCase
    {
        // ...
    }
}
```

PSR-1 is the bedrock. Every other coding standard PSR builds on it.

## PSR-2: Coding Style Guide (Deprecated — Merged Into PSR-12)

PSR-2 expanded PSR-1 with specific formatting rules: 4-space indentation (no tabs), one line between methods, opening braces on the same line for classes and methods, closing braces on their own line.

```php
// PSR-2 style
class User
{
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
```

PSR-2 served well for a decade, but PHP evolved. PHP 7 added typed properties and return types. PHP 8 added constructor promotion, attributes, match expressions, and named arguments. PSR-2 didn't cover any of these, so PHP-FIG merged it into PSR-12 in 2019 and deprecated it.

## PSR-3: Logger Interface

Before PSR-3, every logging library had its own API. Monolog used different method signatures than Analog, which differed from log4php. Switching loggers meant rewriting every `$log->write()` call in your application.

PSR-3 defines eight methods matching RFC 5424 log levels:

```php
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class OrderProcessor
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function process(Order $order): void
    {
        $this->logger->info('Processing order {id}', [
            'id' => $order->id,
        ]);

        try {
            $order->charge();
        } catch (PaymentFailedException $e) {
            $this->logger->error('Payment failed for order {id}: {error}', [
                'id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

The magic is in the placeholder syntax (`{id}`, `{error}`). Libraries replace placeholders with context values, so you get structured logging without string concatenation.

Any PSR-3 logger can replace any other. Monolog, Loggy, Slim's logger, Symfony's logger—they all implement `LoggerInterface`. Your application code never needs to change.

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Swap implementations freely — both satisfy LoggerInterface
$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('php://stderr'));

// Or switch to Symfony's logger — zero changes to OrderProcessor
$logger = new \Symfony\Component\Console\Logger\ConsoleLogger($output);
```

This is interoperability in action.

## PSR-4: Autoloader (The Current Standard)

PSR-4 replaced PSR-0 with a simpler, faster autoloading convention. It dropped the underscore-as-directory-separator rule entirely. It also removed the deep nesting requirement. The mapping is straightforward: each namespace prefix maps to a base directory, and the rest of the class name maps directly to a file path.

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

With that `composer.json`, the class `App\Service\Mailer\SmtpTransport` resolves to `src/Service/Mailer/SmtpTransport.php`. No underscores. No vendor prefix duplication. Just a clean, one-to-one mapping.

```php
// PSR-4 resolution example
namespace App\Service\Mailer;

class SmtpTransport
{
    // File lives at: src/Service/Mailer/SmtpTransport.php
    // Namespace maps: App\ → src/
    // Remaining: \Service\Mailer\SmtpTransport
    // File path: src/Service/Mailer/SmtpTransport.php
}
```

Why did PSR-4 win? Three reasons:

1. **Simplicity** — No underscore rules. No deep nesting. The namespace prefix maps directly to a directory.
2. **Performance** — PSR-4 autoloaders generate fewer file system lookups because they don't transform underscores or search multiple directory depths.
3. **Flexibility** — You can map multiple namespace prefixes to different directories, or even map a single namespace prefix to multiple directories.

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/",
            "App\\Tests\\": "tests/",
            "Acme\\": "vendor/acme/lib/src/"
        }
    }
}
```

Composer uses PSR-4 by default for all packages. Almost everything you install today uses PSR-4 autoloading. PSR-0 only survives in legacy packages that never updated their `composer.json`.

## PSR-6: Caching Interface

Caching looks simple: store a value, retrieve it later. But every cache system has quirks. APC's API differs from Memcached's, which differs from Redis's, which differs from filesystem caching. PSR-6 solves this with a `CacheItemPoolInterface` and `CacheItemInterface`.

```php
use Psr\Cache\CacheItemPoolInterface;

class ExpensiveReportGenerator
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {}

    public function generate(string $date): array
    {
        $cacheKey = "report_{$date}";
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            return $item->get();
        }

        $data = $this->computeReport($date);

        $item->set($data);
        $item->expiresAfter(3600);
        $this->cache->save($item);

        return $data;
    }
}
```

The interface handles cache misses gracefully with `isHit()`, supports TTL via `expiresAfter()`, and provides deferred saves via `saveDeferred()` for batch operations. Any PSR-6 implementation (Symfony Cache, PHP-Cache, APCu adapter) plugs in without changing your business logic.

PSR-6 is verbose by design. A simpler alternative exists: PSR-16 (Simple Cache) provides a single `CacheInterface` with `get`, `set`, `delete`, and `clear` methods. Use PSR-16 for simple key-value needs and PSR-6 for advanced use cases like tagging and deferred saves.

## PSR-7: HTTP Message Interfaces

PSR-7 is arguably the most impactful PHP standard ever created. It defines interfaces for HTTP messages: requests and responses.

Before PSR-7, every framework had its own HTTP message objects. Symfony's `Request` was incompatible with Zend's `Request`. Middleware couldn't pass requests between frameworks. HTTP message handling was a walled garden.

PSR-7 defines `RequestInterface`, `ResponseInterface`, `ServerRequestInterface`, and `MessageInterface`. All HTTP messages share these properties:

```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class CorsMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
```

PSR-7 messages are **immutable**. `withHeader()` returns a *new* response object instead of modifying the original. This prevents bugs where a middleware accidentally mutates a request that later middlewares still need.

```php
// Immutable — these return NEW objects
$response = $response->withHeader('X-Debug', 'true');
$response = $response->withStatus(404);

// The original $response is unchanged until you reassign
```

PSR-7 enabled the middleware revolution in PHP. Frameworks like Slim, Expressive (now Mezzio), and Diactoros built entirely on PSR-7. Laravel adopted PSR-7 through bridges. Symfony added PSR-7 support. Modern PHP HTTP handling is PSR-7 all the way down.

## PSR-11: Container Interface

Dependency injection containers are everywhere in PHP, but before PSR-11, every container had a different interface. Symfony's container used `get()` and `has()`. Laravel's used `make()`, `bind()`, and `instance()`. PHP-DI used its own API. Switching containers meant rewriting service location code.

PSR-11 defines `ContainerInterface` with exactly two methods:

```php
use Psr\Container\ContainerInterface;

class ReportController
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function view(string $id): Response
    {
        // Never do this in real apps — inject dependencies directly
        $repository = $this->container->get(ReportRepository::class);
        $renderer = $this->container->get(PdfRenderer::class);

        $report = $repository->find($id);

        return $renderer->render($report);
    }
}
```

```php
namespace Psr\Container;

interface ContainerInterface
{
    public function get(string $id): mixed;
    public function has(string $id): bool;
}
```

`get()` throws `NotFoundExceptionInterface` if the entry doesn't exist and `ContainerExceptionInterface` if the container encounters an error during resolution. Two methods. Two possible exceptions. That's the entire contract.

Every major container implements PSR-11: Symfony's Container, Laravel's `Illuminate\Container\Container`, PHP-DI, and the minimalist `container-interop` implementations. Framework-agnostic libraries can now use dependency injection without coupling to any specific container.

## PSR-12: Extended Coding Style Guide

PSR-12 is the current coding standard. It supersedes PSR-2 and covers everything PSR-2 did, plus modern PHP features.

Key additions in PSR-12:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Post;
use Psr\Http\Message\ResponseInterface;

class PostController
{
    // Typed properties — PSR-12 covers the formatting
    private ResponseInterface $response;

    // Constructor promotion
    public function __construct(
        private PostRepository $repository,
    ) {}

    // Return type declarations
    public function show(int $id): ResponseInterface
    {
        // Match expression formatting
        $status = match (true) {
            $id <= 0 => 400,
            $id > 1000 => 404,
            default => 200,
        };

        // Nullable types with ?
        $post = $this->repository->find($id)?->toArray();

        // Named arguments
        return $this->render(template: 'post.show', data: $post);
    }
}
```

Specific PSR-12 rules:
- **Opening braces** on same line for classes, methods, closures, and control structures
- **Spacing**: one space after control structure keywords (`if`, `foreach`, `while`, `match`), no space before parentheses in method calls
- **Use declarations**: one per line, grouped (classes, functions, constants), alphabetically ordered
- **Visibility**: required on all properties and methods (`public`, `protected`, or `private`)

```php
// PSR-12 compliant
if ($condition) {
    // ...
}

foreach ($items as $item) {
    // ...
}

$result = array_map(function (int $n): int {
    return $n * 2;
}, $numbers);
```

PSR-12 also adds rules for `declare(strict_types=1)` placement — it goes on line 2, after the opening `<?php` tag, with a blank line after it.

Use PHP-CodeSniffer or PHP-CS-Fixer to enforce PSR-12 automatically. Do not rely on memory.

```bash
# Check PSR-12 compliance
vendor/bin/phpcs --standard=PSR-12 src/

# Auto-fix PSR-12 violations
vendor/bin/phpcbf --standard=PSR-12 src/
```

## PSR-14: Event Dispatcher

PSR-14 standardizes event dispatching. Before it, every framework had its own event system: Symfony's EventDispatcher, Laravel's events, Yii's events. PSR-14 defines a generic interface that any event system can adopt.

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class UserRegistered
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
    ) {}
}

class SendWelcomeEmailListener
{
    public function __invoke(UserRegistered $event): void
    {
        mail(
            $event->email,
            'Welcome!',
            "Hi {$event->name}, thanks for joining!"
        );
    }
}

// Provider connects listeners to events
$provider = new class implements ListenerProviderInterface {
    public function getListenersForEvent(object $event): iterable
    {
        if ($event instanceof UserRegistered) {
            yield new SendWelcomeEmailListener();
        }
    }
};

// Dispatcher iterates listeners from the provider
$dispatcher = new class($provider) implements EventDispatcherInterface {
    public function __construct(
        private ListenerProviderInterface $provider,
    ) {}

    public function dispatch(object $event): object
    {
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        return $event;
    }
};

$dispatcher->dispatch(new UserRegistered('alice@example.com', 'Alice'));
```

The pattern separates three concerns: the dispatcher (sends events), the provider (maps events to listeners), and the event object itself. You can swap any implementation without touching the others.

PHP 8.1's `readonly` properties and constructor promotion make defining event objects trivially clean.

## How PSRs Improve the Developer Experience

PSRs solve real, painful problems. Here's what they do for you daily without you noticing.

**Composer works because of PSR-4.** Every time you run `composer install`, PSR-4 tells Composer exactly where to find every class. No manual autoloader configuration. No "class not found" errors from mismatched paths. Just drop in a package and use it.

**Frameworks interoperate because of PSR-7.** Laravel can send requests to Symfony middleware. Slim can handle a route and pass the response to a Mezzio handler. PHP-HTTP (PSR-18) lets you swap HTTP clients without changing a single line of business code. The HTTP layer is universal.

**Logging is portable because of PSR-3.** Your library ships with a `LoggerInterface` dependency. The user plugs in Monolog, or Sentry's PSR-3 adapter, or a simple `NullLogger` for testing. Your library never needs to know. Zero coupling.

**Caching is swappable because of PSR-6.** Develop against a filesystem cache locally, switch to Redis in staging, Memcached in production. The cache pool changes, your code doesn't.

**Containers are replaceable because of PSR-11.** Framework-agnostic libraries can request dependencies through `ContainerInterface` without hard-coding a container implementation. The container is a detail, not a constraint.

## Practical Example: A PSR-Compliant Package

Here's a minimal PHP package that follows every relevant PSR:

```php
<?php

declare(strict_types=1);

namespace PhpTeacher\PsrExample;

use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemPoolInterface;

class GreetingService
{
    public function __construct(
        private LoggerInterface $logger,
        private CacheItemPoolInterface $cache,
    ) {}

    public function greet(string $name): string
    {
        $cacheKey = "greeting_{$name}";
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $this->logger->info('Cache hit for {name}', ['name' => $name]);
            return $item->get();
        }

        $greeting = sprintf('Hello, %s!', $name);

        $item->set($greeting);
        $item->expiresAfter(300);
        $this->cache->save($item);

        $this->logger->info('Generated greeting for {name}', ['name' => $name]);

        return $greeting;
    }
}
```

```json
{
    "name": "phpteacher/psr-example",
    "autoload": {
        "psr-4": {
            "PhpTeacher\\PsrExample\\": "src/"
        }
    },
    "require": {
        "php": ">=8.1",
        "psr/log": "^3.0",
        "psr/cache": "^3.0"
    }
}
```

This package:
- Uses PSR-4 autoloading — `composer install` and it's ready
- Imports PSR-3 and PSR-6 interfaces — works with any logger or cache
- Follows PSR-12 coding style — consistent formatting
- Declares `strict_types=1` per PSR-12
- Uses PHP 8.1 constructor promotion and typed properties — modern PHP

The consumer can wire in any PSR-compliant implementations:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('php://stdout'));

$cache = new FilesystemAdapter(directory: '/tmp/cache');

$service = new GreetingService($logger, $cache);

echo $service->greet('World');
// Output: "Hello, World!"
// Logs: "Generated greeting for World" to stdout
```

Monolog and Symfony Cache are different packages from different ecosystems. They interoperate without adapters because they both implement PSR interfaces. That's the entire point.

## The Bottom Line

PSRs are not theoretical ivory-tower standards. They're pragmatic solutions to real interoperability problems that plagued PHP for years. Every major framework implements them. Every modern PHP package follows them. Composer enforces them.

If you write PHP today, you write PSR-compliant code whether you realize it or not. Understanding the standards explicitly—rather than just following them by accident—gives you a deeper appreciation for how the ecosystem fits together and makes you a better architect.

The full list of accepted PSRs is available at [php-fig.org](https://www.php-fig.org/psr/). Read a few. Implement them in your next package. Your users will thank you.
