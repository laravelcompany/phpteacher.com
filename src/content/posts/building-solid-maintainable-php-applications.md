---
title: "Building Solid and Maintainable PHP Applications - Architecture Guide"
description: "Learn how to build PHP applications that stand the test of time. Covers SOLID principles, clean architecture, dependency injection, and patterns for maintainable code."
pubDate: "2022-06-12 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Architecture", "SOLID", "Clean Code", "Best Practices", "Design Patterns", "Maintainability"]
selected: false
---

Every PHP application starts simple. A few routes, some database queries, templates that mix logic with presentation. Then features pile on. The `UserController` grows to 2000 lines. The `OrderService` knows about emails, PDF generation, payment gateways, and inventory. Making one change breaks three unrelated features. Deployments become stressful events.

This trajectory is not inevitable. You can build PHP applications that remain easy to change five years from now. The techniques exist — SOLID principles, dependency injection, layered architecture, and disciplined testing. They do not require a particular framework or a massive upfront design. They require intentional application of engineering principles from day one, and a willingness to refactor when you see rot forming.

This guide covers the architectural patterns and practices that turn fragile PHP codebases into solid, maintainable systems.

## What Makes a PHP Application "Solid" and Maintainable?

A solid PHP application exhibits specific measurable properties:

**Changeability.** A new feature requires adding new code, not rewriting existing code. You add a payment gateway without touching the order processing logic. You change the email provider by swapping a class, not hunting through 40 files.

**Testability.** Every meaningful piece of business logic can be tested in isolation. You test the order total calculation without booting a database. You test the email rendering without sending a real email.

**Discoverability.** A new developer on the team can find the relevant code for a feature within minutes. The directory structure reveals the application's intent: controllers in one place, domain logic in another, infrastructure concerns in a third.

**Safety.** The compiler or static analyzer catches entire categories of bugs before they reach production. PHP 8.1's type system, when used aggressively, eliminates null pointer exceptions, invalid argument types, and forgotten return values.

These properties come from applying SOLID principles, choosing the right abstractions, and enforcing boundaries between layers of the application.

## SOLID Principles Applied to PHP

SOLID is not academic theory. Each principle addresses a specific class of maintenance problem that emerges in real PHP projects. Let's walk through each one with practical code.

### Single Responsibility Principle

> A class should have one, and only one, reason to change.

The most violated principle in PHP applications. When a single class handles HTTP requests, runs database queries, sends emails, and renders HTML, it changes for every reason. New endpoint? Change the controller. New email template? Change the controller. Different database? Change the controller.

Consider this common violation:

```php
class OrderController
{
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'product_id' => 'required|int',
            'quantity' => 'required|int|min:1',
        ]);

        $product = Product::find($validated['product_id']);

        if ($product->stock < $validated['quantity']) {
            return back()->withErrors(['Stock insufficient']);
        }

        $order = Order::create([
            'product_id' => $product->id,
            'quantity' => $validated['quantity'],
            'total' => $product->price * $validated['quantity'],
        ]);

        Mail::to($request->user())->send(new OrderConfirmation($order));

        return redirect('/orders/' . $order->id);
    }
}
```

This controller does validation, business logic, persistence, and email sending. Every one of those responsibilities is a reason to change the class. The fix is to separate concerns:

```php
class OrderController
{
    public function __construct(
        private OrderService $orders,
        private OrderConfirmationMailer $mailer,
    ) {}

    public function store(StoreOrderRequest $request): Response
    {
        $order = $this->orders->place(
            $request->user(),
            $request->toDto(),
        );

        $this->mailer->send($order);

        return redirect('/orders/' . $order->id);
    }
}

class OrderService
{
    public function __construct(
        private OrderRepository $orders,
        private ProductRepository $products,
    ) {}

    public function place(User $user, OrderDto $dto): Order
    {
        $product = $this->products->findOrFail($dto->productId);
        $product->assertSufficientStock($dto->quantity);

        return $this->orders->save(
            new Order($user, $product, $dto->quantity)
        );
    }
}
```

Now `OrderController` only changes when HTTP contract changes. `OrderService` only changes when business rules change. You test them independently.

### Open/Closed Principle

> Classes should be open for extension but closed for modification.

You should be able to add new behavior without editing existing, tested code. Use polymorphism and composition instead of conditionals and switches.

Bad example — a notification system that requires modification for every new channel:

```php
class NotificationService
{
    public function send(User $user, string $message): void
    {
        if ($user->prefersEmail()) {
            // send email
        } elseif ($user->prefersSms()) {
            // send SMS
        } elseif ($user->prefersSlack()) {
            // send Slack message
        }
    }
}
```

Adding a new channel means editing this class. Someone will forget one branch and introduce a bug. The fix uses an interface and a registry:

```php
interface Channel
{
    public function send(User $user, string $message): void;
}

class EmailChannel implements Channel { /* ... */ }
class SmsChannel implements Channel { /* ... */ }
class SlackChannel implements Channel { /* ... */ }

class NotificationService
{
    /** @param Channel[] $channels */
    public function __construct(
        private iterable $channels,
    ) {}

    public function send(User $user, string $message): void
    {
        foreach ($this->channels as $channel) {
            $channel->send($user, $message);
        }
    }
}
```

Adding a `PushChannel` means writing a new class and registering it. You never touch `NotificationService`. Test coverage from last year still protects the existing channels.

### Liskov Substitution Principle

> Subtypes must be substitutable for their base types without altering the correctness of the program.

If a piece of code works with a base class or interface, it must also work with any subclass without knowing about it. Violations happen when subclasses strengthen preconditions, weaken postconditions, or throw unexpected exceptions.

Common PHP violation — a `FileLoader` that assumes all loaders return content:

```php
class FileLoader
{
    public function load(string $path): string
    {
        return file_get_contents($path);
    }
}

class CachedFileLoader extends FileLoader
{
    public function load(string $path): string
    {
        if ($this->cache->has($path)) {
            return $this->cache->get($path);
        }

        if (!file_exists($path)) {
            return ''; // Violates LSP — returns empty string instead of failing
        }

        return parent::load($path);
    }
}
```

A consumer that checks `strlen($loader->load($path)) > 0` now silently accepts paths that don't exist. The subclass changed the contract. The fix is to keep the contract identical or use a `Maybe`/`Result` type consistently across both classes.

### Interface Segregation Principle

> No client should be forced to depend on methods it does not use.

Fat interfaces create coupling. A class that implements `UserRepositoryInterface` with 12 methods but only uses 3 still depends on the other 9 to compile. Changes to unused method signatures ripple through the codebase.

Violation:

```php
interface OrderRepositoryInterface
{
    public function find(int $id): ?Order;
    public function save(Order $order): void;
    public function delete(int $id): void;
    public function search(Criteria $criteria): OrderCollection;
    public function countByStatus(Status $status): int;
    public function latestByUser(int $userId): OrderCollection;
    // ... 6 more methods
}
```

A `OrderReportGenerator` that only needs `search` and `countByStatus` still depends on the full interface. Instead, segregate:

```php
interface OrderFinder
{
    public function find(int $id): ?Order;
}

interface OrderSearcher
{
    public function search(Criteria $criteria): OrderCollection;
}

interface OrderReporter
{
    public function countByStatus(Status $status): int;
}

class DoctrineOrderRepository implements OrderFinder, OrderSearcher, OrderReporter
{
    // All three contracts, one implementation
}
```

Now `OrderReportGenerator` depends only on `OrderReporter`. It has one reason to change — reporting requirements.

### Dependency Inversion Principle

> Depend on abstractions, not concretions. High-level modules should not depend on low-level modules.

This principle reverses the natural dependency direction in most PHP projects. Instead of your domain logic importing a concrete MySQL repository class, the domain logic defines an interface, and the infrastructure layer implements it.

Violation:

```php
class OrderService
{
    private MySqlOrderRepository $repository;

    public function __construct()
    {
        $this->repository = new MySqlOrderRepository();
    }
}
```

`OrderService` — a high-level domain concept — depends directly on MySQL. Switching to PostgreSQL means editing the domain class. Testing means booting a real database.

Following DIP:

```php
interface OrderRepository
{
    public function save(Order $order): void;
    public function find(int $id): ?Order;
}

class OrderService
{
    public function __construct(
        private OrderRepository $repository,
    ) {}

    public function place(User $user, Cart $cart): Order
    {
        $order = new Order($user, $cart);
        $this->repository->save($order);
        return $order;
    }
}

class MySqlOrderRepository implements OrderRepository { /* ... */ }
class InMemoryOrderRepository implements OrderRepository { /* ... */ }
```

The domain layer defines the contract. Infrastructure implements it. Tests use `InMemoryOrderRepository` without any database. This is the foundation of clean architecture.

## Dependency Injection Container

Applying SOLID principles creates a problem: who wires all these dependencies together? Your `OrderController` needs an `OrderService`, which needs `OrderRepository` and `ProductRepository`, which need `EntityManager`, which needs a `Connection`. Manually instantiating this tree is tedious and error-prone.

A dependency injection container solves this. PHP has excellent options: the Symfony DI Container, PHP-DI, and Laravel's service container all handle automatic resolution.

Here is a minimal container configuration using PHP-DI:

```php
use function DI\autowire;
use function DI\get;

return [
    OrderRepository::class => autowire(DoctrineOrderRepository::class),
    ProductRepository::class => autowire(DoctrineProductRepository::class),
    OrderService::class => autowire()
        ->constructorParameter('taxCalculator', get(SalesTaxCalculator::class)),
    Channel::class => [
        get(EmailChannel::class),
        get(SmsChannel::class),
        get(SlackChannel::class),
    ],
];
```

The container reads constructor type-hints, resolves the entire dependency graph, and returns fully constructed objects. You never write `new` for services. You never pass dependencies manually.

This does more than reduce boilerplate. It makes your dependency graph explicit and centralized. You can swap implementations in one place. You can see the full wiring of your application without reading every constructor.

## Clean Architecture and Hexagonal Architecture

Clean Architecture, popularized by Robert C. Martin, and Hexagonal Architecture (Alistair Cockburn) solve the same problem with slightly different terminology. Both define concentric layers with a strict dependency rule: **inner layers never depend on outer layers**.

Here is how the layers map to a PHP application, moving from the core outward:

### Domain Layer (innermost)

Entities and value objects. Pure business logic with no side effects, no framework dependencies, and no database calls.

```php
class Order
{
    public function __construct(
        private string $id,
        private UserId $userId,
        private LineItems $items,
        private OrderStatus $status = OrderStatus::Pending,
        private ?\DateTimeImmutable $placedAt = null,
    ) {}

    public function place(): void
    {
        if ($this->status !== OrderStatus::Pending) {
            throw new OrderAlreadyPlacedException();
        }
        if ($this->items->isEmpty()) {
            throw new CannotPlaceEmptyOrderException();
        }
        $this->status = OrderStatus::Confirmed;
        $this->placedAt = new \DateTimeImmutable();
    }

    public function total(): Money
    {
        return $this->items->total();
    }
}
```

No framework. No database. No HTTP. Pure PHP that you can test without any infrastructure.

### Application Layer

Use cases and application services. Orchestrates domain objects to fulfill a single user story. Depends on interfaces defined in the domain layer, never on concrete infrastructure.

```php
class PlaceOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private ProductRepository $products,
        private UnitOfWork $uow,
    ) {}

    public function handle(PlaceOrderCommand $command): void
    {
        $product = $this->products->findOrFail($command->productId);
        $order = new Order(
            id: Uuid::v7()->toString(),
            userId: $command->userId,
            items: new LineItems([new LineItem($product, $command->quantity)]),
        );
        $order->place();
        $this->orders->save($order);
        $this->uow->commit();
    }
}
```

### Infrastructure Layer

Implements domain interfaces. Contains database repositories, HTTP clients, mailers, file systems, and framework-specific code.

```php
class DoctrineOrderRepository implements OrderRepository
{
    public function __construct(
        private EntityManager $em,
    ) {}

    public function save(Order $order): void
    {
        $this->em->persist($order);
    }

    public function find(int $id): ?Order
    {
        return $this->em->find(Order::class, $id);
    }
}
```

### Presentation Layer

Controllers, CLI commands, queue listeners, API resource classes. Thin adapters that translate external input into commands the application layer understands.

```php
class PlaceOrderController
{
    public function __construct(
        private PlaceOrderHandler $handler,
    ) {}

    public function __invoke(Request $request): Response
    {
        $command = new PlaceOrderCommand(
            userId: $request->user()->id,
            productId: $request->input('product_id'),
            quantity: (int) $request->input('quantity'),
        );

        $this->handler->handle($command);

        return new JsonResponse(['status' => 'created'], 201);
    }
}
```

### The Dependency Rule

The critical rule: source code dependencies can only point inward. The domain layer imports nothing from Laravel, Symfony, Doctrine, or your database driver. The application layer imports only domain interfaces. Infrastructure and presentation depend on application and domain.

This rule means you can replace your web framework, your database, your email provider, and your queue system independently of your business logic. Each swap requires rewriting only the outer layer implementation of the relevant interface.

## Testing Maintainable Code

Architecture without tests is speculation. The layered approach above makes testing straightforward because each layer can be tested in isolation with minimal setup.

### Unit Testing Domain Logic

Domain entities and value objects contain pure logic. Test them directly with no mocking:

```php
class OrderTest extends TestCase
{
    public function test_cannot_place_empty_order(): void
    {
        $order = new Order(
            id: Uuid::v7()->toString(),
            userId: UserId::generate(),
            items: new LineItems(),
        );

        $this->expectException(CannotPlaceEmptyOrderException::class);

        $order->place();
    }

    public function test_placing_order_sets_confirmed_status(): void
    {
        $order = new Order(
            id: Uuid::v7()->toString(),
            userId: UserId::generate(),
            items: new LineItems([new LineItem($this->product(), 2)]),
        );

        $order->place();

        $this->assertSame(OrderStatus::Confirmed, $order->status());
    }
}
```

No database. No HTTP. No framework bootstrap. These tests run in milliseconds.

### Testing Application Services

Test use cases by injecting test doubles for the domain interfaces:

```php
class PlaceOrderHandlerTest extends TestCase
{
    public function test_creates_order_with_correct_items(): void
    {
        $products = new InMemoryProductRepository([
            1 => new Product(/* ... */),
        ]);
        $orders = new InMemoryOrderRepository();
        $handler = new PlaceOrderHandler(
            orders: $orders,
            products: $products,
            uow: new InMemoryUnitOfWork(),
        );

        $handler->handle(new PlaceOrderCommand(
            userId: 42,
            productId: 1,
            quantity: 3,
        ));

        $saved = $orders->find(1);
        $this->assertNotNull($saved);
        $this->assertSame(3, $saved->items()->count());
    }
}
```

`InMemoryOrderRepository` implements `OrderRepository` with an array. No database needed. Tests run fast and deterministically.

### Testing Infrastructure

Integration tests verify that your repository implementation correctly persists and retrieves data. These use a real or test database but exercise only one adapter at a time.

This testing pyramid works precisely because of the dependency inversion. Domain tests need no infrastructure. Application tests need only in-memory implementations. Infrastructure tests verify the adapter against its contract.

## Common Anti-Patterns and How to Avoid Them

### The God Object

A class that knows everything and does everything. The `UserService` that handles registration, password resets, profile updates, billing, and admin functions.

**Solution:** Split by aggregate root. A `RegistrationService` handles sign-up. A `PasswordResetService` handles resets. A `BillingService` handles payments. Each class has a clear scope and a single reason to change.

### Static Calls Everywhere

`User::find()`, `Mail::send()`, `Cache::get()`. Static methods create hidden coupling. You cannot substitute them in tests. Every call to a static method is a hard dependency.

**Solution:** Inject dependencies through constructors. If you cannot avoid a static facade, wrap it behind an interface and inject the wrapper.

### Transaction Script in Controllers

Putting business logic inside controllers. The controller is the first place developers look, so it accumulates if statements, database calls, and view logic until it becomes unmaintainable.

**Solution:** Controllers should translate HTTP input to a command or DTO, pass it to an application service, and return a response. Nothing else.

### ActiveRecord for Complex Domain Logic

ActiveRecord (like Eloquent) works well for simple CRUD. But when you have 50-line method chains mixing query builder calls with business rules, you have a problem.

**Solution:** Use ActiveRecord for simple entities or queries. For complex business logic, create a separate domain model that depends on a repository interface, and implement the repository using your ORM.

### Ignoring Types

PHP 8.1 and 8.2 added intersection types, readonly properties, enums, and never return types. Projects that ignore these features miss the compiler's ability to catch bugs.

```php
// Instead of this:
public function getStatus()
{
    return $this->status;
}

// Write this:
public function status(): OrderStatus
{
    return $this->status;
}
```

Enums eliminate entire categories of invalid state:

```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
```

You cannot accidentally pass `'shipping'` or `123` where an `OrderStatus` is expected.

## Refactoring Legacy Code Toward Maintainability

Large codebases do not need to be rewritten. They need to be refactored systematically. The strategy is incremental: carve out bounded contexts, extract interfaces, and add tests at each step.

### Step 1: Identify a Seam

Find a piece of logic that you understand well enough to define its boundary. Maybe the password reset flow, the checkout process, or the report generation. It should be small — something you can refactor in a day.

### Step 2: Write Characterization Tests

Before changing anything, write tests that capture the current behavior. Use snapshot testing if the output is complex. These tests document the existing contract and catch regressions as you refactor.

```php
public function test_existing_checkout_behavior(): void
{
    $response = $this->post('/checkout', [
        'product_id' => 1,
        'quantity' => 2,
    ]);

    // Capture the full behavior before changing it
    $this->assertSame(201, $response->getStatusCode());
    $this->assertStringContainsString('Order #', $response->getContent());
}
```

### Step 3: Extract an Interface

Identify the infrastructure dependency in the code — the database call, the HTTP client, the mailer. Define an interface for it. Make the existing implementation the first adapter.

```php
interface PaymentGateway
{
    public function charge(Money $amount, PaymentToken $token): PaymentResult;
}

class StripePaymentGateway implements PaymentGateway
{
    // Move existing Stripe code here
}
```

### Step 4: Inject the Interface

Replace the direct instantiation or static call with constructor injection. Use a dependency injection container to wire the concrete implementation. Now the code depends on an abstraction.

### Step 5: Write Real Unit Tests

With the interface extracted and injected, write unit tests using a test double. Verify the business logic without touching the real payment gateway or database.

### Step 6: Expand Iteratively

Rinse and repeat. Each cycle extracts a small piece of infrastructure behind an interface and protects it with tests. Over weeks and months, the legacy codebase transforms into a modular, testable system.

The goal is not to rewrite everything at once. It is to make each day's work leave the codebase slightly better than you found it. An architecture that evolves this way, guided by SOLID principles and enforced by tests, produces applications that survive for years without collapsing under their own weight.

## Summary

Building maintainable PHP applications requires intentional architectural decisions. Apply SOLID principles to keep classes focused and loosely coupled. Use dependency injection to wire your application together without hidden dependencies. Structure your code in layers with the dependency rule pointing inward — domain at the core, infrastructure at the edges. Test each layer independently using the abstractions you've defined.

The result is a codebase where adding a feature means writing new code, not untangling old code. Where tests run in milliseconds and catch regressions before deployment. Where changing a framework, database, or third-party service requires swapping an adapter, not rewriting the application.

That is the definition of a solid PHP application.
