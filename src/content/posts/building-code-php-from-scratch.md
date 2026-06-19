---
title: "Building Code - Creating PHP Applications From Scratch"
description: "Learn how to start a PHP project from scratch with proper architecture using Factory and Builder patterns, testing setup, and coding standards."
pubDate: "2022-09-16 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "From Scratch", "Architecture", "Project Setup", "Best Practices"]
selected: false
---

Humans are good at recognizing patterns. Early humans detected changes in visual and auditory patterns to spot danger — that patch of grass moving differently means something is coming to eat you. Developers do the same thing with code. After writing enough of it, you start seeing repeated structures. Some come from copy-paste. Others come from genuine similarity in design.

Two patterns that emerge frequently are the Factory and the Builder. They solve two different problems: creating objects in multiple places, and creating a variety of similar but slightly different objects. Both help you build code from scratch with intention rather than accident.

## Factories

Factories are usually the first creational pattern developers encounter. The idea is simple: supply a factory with some data, and it returns a fully functional object. This encapsulates construction logic in one place and provides a clean API for creating objects.

### The Problem Factories Solve

Imagine an API that returns JSON responses. You build a `JsonResponse` class:

```php
class JsonResponse
{
    public function __construct(
        protected array $data = [],
        protected int $returnCode = 200,
    ) {}

    public function output(): void
    {
        header('Content-Type: application/json');
        http_response_code($this->returnCode);
        echo json_encode($this->data);
    }
}
```

This works until a client says their legacy system only speaks XML. You add an `XmlResponse`:

```php
class XmlResponse
{
    public function __construct(
        protected array $data = [],
        protected int $returnCode = 200,
    ) {}

    public function output(): void
    {
        header('Content-Type: application/xml');
        http_response_code($this->returnCode);
        $xml = new SimpleXMLElement('<root/>');
        array_walk_recursive(
            array_flip($this->data),
            [$xml, 'addChild']
        );
        echo $xml->asXML();
    }
}
```

Now every controller needs a switch statement:

```php
class OurController
{
    public function someAction(): void
    {
        switch (getallheaders()['Accept'] ?? '') {
            case 'application/xml':
                $resp = new XmlResponse($returnData);
                break;
            default:
                $resp = new JsonResponse($returnData);
                break;
        }
        $resp->output();
    }
}
```

This switch statement duplicates across every endpoint. Every new response type means editing every controller. A Factory eliminates this.

### Building a Response Factory

First, define an interface all responses implement:

```php
interface ResponseInterface
{
    public function __construct(mixed $data);
    public function output(): void;
    public function setReturnCode(int $code): static;
}
```

Create an abstract base to share common logic:

```php
abstract class AbstractResponse implements ResponseInterface
{
    protected int $returnCode = 200;

    public function __construct(
        protected mixed $data,
    ) {}

    public function setReturnCode(int $code): static
    {
        $this->returnCode = $code;
        return $this;
    }

    abstract public function output(): void;
}

class JsonResponse extends AbstractResponse
{
    public function output(): void
    {
        header('Content-Type: application/json');
        http_response_code($this->returnCode);
        echo json_encode($this->data);
    }
}

class XmlResponse extends AbstractResponse
{
    public function output(): void
    {
        header('Content-Type: application/xml');
        http_response_code($this->returnCode);
        $xml = new SimpleXMLElement('<root/>');
        array_walk_recursive(
            array_flip($this->data),
            [$xml, 'addChild']
        );
        echo $xml->asXML();
    }
}
```

Now the factory:

```php
class ResponseFactory
{
    public static function create(
        mixed $data,
        int $returnCode = 200
    ): ResponseInterface {
        $resp = match (getallheaders()['Accept'] ?? '') {
            'application/xml' => new XmlResponse($data),
            default => new JsonResponse($data),
        };
        $resp->setReturnCode($returnCode);
        return $resp;
    }
}
```

The controller simplifies to:

```php
class OurController
{
    public function someAction(): void
    {
        $resp = ResponseFactory::create($returnData);
        $resp->output();
    }
}
```

Adding a new response type means editing one file — the factory. Controllers never change.

### Factories with Dependencies

When a factory's products share dependencies, pass those through the factory's constructor:

```php
class RepositoryFactory
{
    public function __construct(
        protected PDO $pdo
    ) {}

    public function create(
        string $entityName,
        string $idColumn = 'id',
        ?string $tableName = null
    ): object {
        $tableName ??= $entityName;

        $customRepo = $entityName . 'Repository';
        if (class_exists($customRepo)) {
            return new $customRepo($this->pdo);
        }

        return new GenericRepository(
            $this->pdo,
            $idColumn,
            $tableName,
        );
    }
}
```

The `PDO` connection is injected once. Every repository created by the factory shares it without the caller needing to know.

## The Builder Pattern

Builders solve a different problem. Where factories take all information at once, builders collect options through a fluent interface. Use a Builder when objects have many optional parameters or when most objects are conceptually the same but rarely have identical configurations.

Builders maintain state between method calls. This makes them powerful for creating batches of similar objects, but also introduces the risk of stale options.

### Book Builder Example

Let's build a system that creates book objects for a printer. PHP 8.1 enums provide type-safe options:

```php
enum BookCover: string
{
    case HARDCOVER = 'hardcover';
    case PAPERBACK = 'paperback';
}

enum BookSize: string
{
    case TRADE_5x8 = 't58';
    case TRADE_6x9 = 't69';
    case TRADE_8x10 = 't810';
    case MAGAZINE = 'mag';
}
```

The builder collects properties with fluent setters:

```php
class BookBuilder
{
    protected string $author = 'Anonymous';
    protected string $contents = '';
    protected BookCover $cover = BookCover::PAPERBACK;
    protected string $coverImage = 'generic.png';
    protected BookSize $size = BookSize::TRADE_5x8;
    protected string $title = '';

    public function setAuthor(string $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function setContents(string $contents): static
    {
        $this->contents = $contents;
        return $this;
    }

    public function setCover(BookCover $cover): static
    {
        $this->cover = $cover;
        return $this;
    }

    public function setCoverImage(string $image): static
    {
        $this->coverImage = $image;
        return $this;
    }

    public function setSize(BookSize $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function build(): Book
    {
        return new Book(
            title: $this->title,
            contents: $this->contents,
            author: $this->author,
            cover: $this->cover,
            coverImage: $this->coverImage,
            size: $this->size,
        );
    }
}
```

Using the builder:

```php
$magazineBuilder = (new BookBuilder())
    ->setCover(BookCover::PAPERBACK)
    ->setSize(BookSize::MAGAZINE);

$jan2022 = $magazineBuilder
    ->setTitle('php[architect] January 2022')
    ->setCoverImage('jan2022.png')
    ->setContents($archJan2022)
    ->build();

$feb2022 = $magazineBuilder
    ->setTitle('php[architect] February 2022')
    ->setCoverImage('feb2022.png')
    ->setContents($archFeb2022)
    ->build();
```

No objects are created until `build()` is called. The builder retains state between calls — set the cover and size once, then vary only the title, content, and image.

### The State Pitfall

Builder statefulness is powerful but dangerous:

```php
$devLeadBook = $magazineBuilder
    ->setTitle('The Dev Lead Trenches')
    ->setContents($devTrenches)
    ->build();
```

This book will have the `jan2022.png` cover image because we did not update it. The builder still holds the previous value. If you retrieve a builder from a service locator, ensure you always get a fresh instance to prevent cross-request contamination.

## Factory vs Builder: When to Use Which

| Scenario | Pattern |
|---|---|
| One method call produces a complete object | Factory |
| Objects have many optional parameters | Builder |
| Object type depends on runtime input | Factory |
| Same construction process creates different representations | Builder |
| All objects share a common dependency | Factory constructor |
| Objects differ in only a few fields | Builder with state |

## Other Creational Patterns

**Abstract Factory** — A factory that creates factories. Useful when you need families of related objects. A `PageFactory` might have `createDigitalView()` and `createPrintView()`, with different factory implementations for mobile, desktop, and terminal.

**Prototype** — Clone an existing object and modify it. PHP's `clone` keyword creates a shallow copy. Unlike `$b = $a` (which makes both variables point to the same object), `$b = clone $a` creates a separate instance.

```php
$prototype = new Book(
    title: 'Template',
    contents: '',
    author: 'Anonymous',
    cover: BookCover::PAPERBACK,
    coverImage: 'generic.png',
    size: BookSize::TRADE_6x9,
);

$specific = clone $prototype;
// Now modify $specific's properties
```

## Building from Scratch

When starting a PHP project from scratch, the first decision is how objects get created. Factories and Builders are not the flashiest patterns, but they shape how the rest of the codebase grows. A well-placed factory eliminates switch statements across dozens of controllers. A builder turns complex object creation into readable, chainable calls.

Look at your code for patterns in how you create objects. If you are repeating the same construction logic, extract it. If an object has five optional parameters with sensible defaults, consider a builder. If you switch between implementations based on input, write a factory.

These patterns will not save you from the lion in the grass, but they will save you from the lion in your codebase.
