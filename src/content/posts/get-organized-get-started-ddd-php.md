---
title: "Get Organized and Get Started - Beginning Your DDD Journey in PHP"
description: "Ready to start with Domain-Driven Design? Practical advice for getting organized, choosing your first bounded context, and taking the first step toward better architecture."
pubDate: "2022-05-22 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "Domain-Driven Design", "PHP", "Getting Started", "Architecture", "Best Practices"]
selected: false
---

You've read the blue book. Or the red book. Or both. You understand Ubiquitous Language, Entities, Value Objects, Aggregates, and Repositories in theory. But when you sit down to write code, nothing happens. The cursor blinks at you.

Analysis paralysis is the #1 reason developers never get started with Domain-Driven Design. The scope feels enormous. The patterns feel like overhead. What if you pick the wrong boundary? What if you over-engineer something simple?

Here's the truth: you will pick wrong boundaries. You will over-engineer things. And that's fine. DDD is an iterative discipline. The goal isn't a perfect model on the first try. The goal is a model that improves as your understanding grows.

## The Hardest Part Is Starting

Before writing any code, pick a single, small, bounded context from your project. This is the hardest and most important decision you'll make.

### What Makes a Good First Bounded Context

- **It's well-understood.** You know the domain language already. Everyone on the team agrees on the terminology.
- **It's small.** No more than 5-10 classes in the domain model. If your first context touches 15 tables, pick something smaller.
- **It has clear boundaries.** The context has obvious inputs and outputs. It doesn't bleed into everything else.
- **The business cares about it.** Work on something that matters. A billing system, an inventory tracker, a scheduling engine. Not a CSV exporter.

Bad first choice: "The entire e-commerce platform." Good first choice: "The shopping cart discount rules."

Not glamorous. But it's *achievable*. And achievable is what matters when you're learning.

## Setting Up the Project Structure

PHP doesn't enforce a project layout, and that's fine. The structure should communicate the domain, not the framework.

A common starting point for DDD in PHP:

```
src/
  Domain/
    Cart/
      Entity/
      ValueObject/
      Repository/
      Service/
  Application/
  Infrastructure/
  UI/
```

The key: classes are grouped by domain concept (Cart, Product, Invoice), not by technical layer (Models, Services, Controllers). Your namespace matches the ubiquitous language.

```php
namespace App\Domain\Cart\Entity;
namespace App\Domain\Cart\ValueObject;
namespace App\Domain\Cart\Repository;
```

This layout tells anyone reading the codebase what the domain is about. They don't need to dig through a folder called "Models" to figure out what the system does.

## Writing Your First Value Object

Value Objects are the easiest DDD building block to start with. They're immutable, self-validating, and equality-compared by their properties.

```php
namespace App\Domain\Cart\ValueObject;

use Money\Money;

class Discount
{
    public function __construct(
        private readonly string $code,
        private readonly Money  $amount,
        private readonly bool   $isPercentage
    ) {}

    public static function fromPercentage(string $code, float $percent, Money $maxAmount): self
    {
        if ($percent <= 0 || $percent > 100) {
            throw new \InvalidArgumentException('Percentage must be between 1 and 100');
        }

        return new self($code, $maxAmount, true);
    }

    public static function fixedAmount(string $code, Money $amount): self
    {
        if ($amount->isNegative() || $amount->isZero()) {
            throw new \InvalidArgumentException('Discount amount must be positive');
        }

        return new self($code, $amount, false);
    }

    public function applyTo(Money $price): Money
    {
        if ($this->isPercentage) {
            $discount = $price->multiply($this->amount->getAmount() / 100);

            return $discount->greaterThan($this->amount) ? $price->subtract($this->amount) : $price->subtract($discount);
        }

        return $price->subtract($this->amount);
    }

    public function code(): string
    {
        return $this->code;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code
            && $this->amount->equals($other->amount)
            && $this->isPercentage === $other->isPercentage;
    }
}
```

Notice the characteristics: constructor promotion with readonly properties, named constructors for validation, the `equals` method, no setters. The object can't be put in an invalid state.

## Creating Your First Entity and Aggregate

Entities have identity. Two Entity objects with the same property values but different IDs are different objects. An Aggregate is a cluster of Entities and Value Objects treated as a single unit.

```php
namespace App\Domain\Cart\Entity;

use App\Domain\Cart\ValueObject\CartItem;
use App\Domain\Cart\ValueObject\Discount;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

class Cart
{
    private string $id;
    private Collection $items;
    private ?Discount $appliedDiscount = null;

    public function __construct()
    {
        $this->id = (string) Uuid::v4();
        $this->items = new ArrayCollection();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function addItem(CartItem $item): void
    {
        foreach ($this->items as $existing) {
            if ($existing->productId() === $item->productId()) {
                $existing->increaseQuantity($item->quantity());

                return;
            }
        }

        $this->items->add($item);
    }

    public function applyDiscount(Discount $discount): void
    {
        if ($this->appliedDiscount !== null) {
            throw new \DomainException('A discount is already applied to this cart');
        }

        $this->appliedDiscount = $discount;
    }

    public function total(): Money
    {
        $subtotal = array_reduce(
            $this->items->toArray(),
            fn(Money $carry, CartItem $item) => $carry->add($item->subtotal()),
            new Money(0, new Currency('USD'))
        );

        if ($this->appliedDiscount !== null) {
            return $this->appliedDiscount->applyTo($subtotal);
        }

        return $subtotal;
    }
}
```

The Cart Aggregate enforces invariants: can't apply two discounts, quantities merge for the same product, total is always computed consistently. The outside world interacts with the Cart through its public methods, not by poking at its internals.

## Implementing a Repository

Repositories hide data access behind a collection-like interface. Your domain code never sees SQL, Eloquent, or Doctrine queries.

```php
namespace App\Domain\Cart\Repository;

use App\Domain\Cart\Entity\Cart;

interface CartRepository
{
    public function findById(string $id): ?Cart;
    public function save(Cart $cart): void;
    public function delete(string $id): void;
}
```

The implementation lives in Infrastructure:

```php
namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Cart\Entity\Cart;
use App\Domain\Cart\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineCartRepository implements CartRepository
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function findById(string $id): ?Cart
    {
        return $this->em->find(Cart::class, $id);
    }

    public function save(Cart $cart): void
    {
        $this->em->persist($cart);
        $this->em->flush();
    }

    public function delete(string $id): void
    {
        $cart = $this->findById($id);

        if ($cart !== null) {
            $this->em->remove($cart);
            $this->em->flush();
        }
    }
}
```

The domain layer only knows the interface. Swap Doctrine for Eloquent, for Redis, for an in-memory array — the domain doesn't change.

## Writing Your First Test

Testing a Value Object:

```php
use App\Domain\Cart\ValueObject\Discount;
use Money\Currency;
use Money\Money;

it('applies percentage discount correctly', function () {
    $discount = Discount::fromPercentage('SUMMER20', 20, new Money(5000, new Currency('USD')));
    $price = new Money(10000, new Currency('USD'));

    $result = $discount->applyTo($price);

    expect($result)->toEqual(new Money(8000, new Currency('USD')));
});

it('caps percentage discount at max amount', function () {
    $discount = Discount::fromPercentage('SUMMER20', 50, new Money(3000, new Currency('USD')));
    $price = new Money(10000, new Currency('USD'));

    $result = $discount->applyTo($price);

    expect($result)->toEqual(new Money(7000, new Currency('USD')));
});

it('prevents invalid percentage values', function () {
    Discount::fromPercentage('INVALID', 150, new Money(5000, new Currency('USD')));
})->throws(\InvalidArgumentException::class);
```

Testing an Aggregate:

```php
it('prevents applying two discounts to the same cart', function () {
    $cart = new Cart();
    $discount1 = Discount::fixedAmount('TENOFF', new Money(1000, new Currency('USD')));
    $discount2 = Discount::fixedAmount('FIVEOFF', new Money(500, new Currency('USD')));

    $cart->applyDiscount($discount1);
    $cart->applyDiscount($discount2);
})->throws(\DomainException::class);
```

You don't need a database to test domain logic. That's the point. Pure PHP objects, pure assertions.

## Building Up to Application Services

Application Services orchestrate domain objects. They're thin — they don't contain business logic:

```php
namespace App\Application\UseCase;

use App\Domain\Cart\Entity\Cart;
use App\Domain\Cart\Repository\CartRepository;
use App\Domain\Cart\ValueObject\CartItem;
use App\Domain\Product\Repository\ProductRepository;
use Psr\Log\LoggerInterface;

class AddItemToCartUseCase
{
    public function __construct(
        private CartRepository    $cartRepository,
        private ProductRepository $productRepository,
        private LoggerInterface   $logger,
    ) {}

    public function execute(string $cartId, string $productId, int $quantity): void
    {
        $product = $this->productRepository->findById($productId);

        if ($product === null) {
            throw new \InvalidArgumentException('Product not found');
        }

        $cart = $this->cartRepository->findById($cartId)
            ?? new Cart();

        $cart->addItem(new CartItem(
            productId: $product->id(),
            name:      $product->name(),
            price:     $product->price(),
            quantity:  $quantity,
        ));

        $this->cartRepository->save($cart);
        $this->logger->info('Item added to cart', [
            'cart_id'    => $cart->id(),
            'product_id' => $productId,
            'quantity'   => $quantity,
        ]);
    }
}
```

The use case has one job: coordinate the domain objects. If there's a business rule about how many items can be in a cart, it goes in the Cart aggregate, not in this service.

## Common Pitfalls for Beginners

### Over-engineering from day one

You do not need CQRS, Event Sourcing, a separate read model, or hexagonal architecture on your first DDD project. Start with Value Objects and Aggregates. Add complexity only when the pain of not having it exceeds the pain of implementing it.

### Putting business logic in services

If your Application Service is 100 lines of `if` statements, you've missed the point. Business logic belongs in Entities and Value Objects. The service should be delegating, not deciding.

### Making everything an Entity

Email addresses are not Entities. Dates are not Entities. Money is not an Entity. If something is identified by its properties and has no lifecycle, make it a Value Object.

### Ignoring the ubiquitous language

Your code should sound like your domain. If the business says "cancel order," your method is `$order->cancel()`, not `$order->updateStatus(OrderStatus::CANCELLED)`. The language gap between developers and domain experts causes more bugs than any technical mistake.

### Doctrine anemic entities

If you're using Doctrine, resist the urge to annotate properties as public or to generate getters/setters for everything. Keep the entity's internals private. Expose behavior, not data.

## Resources for Learning More

- **Domain-Driven Design: Tackling Complexity in the Heart of Software** (Eric Evans) — The original blue book. Read chapters 1-6 to start. Save the tactical patterns chapters for later.
- **Implementing Domain-Driven Design** (Vaughn Vernon) — The red book. More practical, more examples, less dense than Evans.
- **Domain-Driven Design in PHP** (Carlos Buenosvinos et al.) — PHP-specific examples. Dated in places but still useful.
- **Patterns, Principles, and Practices of Domain-Driven Design** (Scott Millett) — Great for bridging theory and practice.
- **mathiasverraes.com** — Verraes has been writing about DDD in PHP longer than almost anyone. His blog is a goldmine.

## Conclusion

Starting with DDD doesn't mean you need to boil the ocean. Pick a small bounded context. Write a Value Object. Add an Entity. Hook up a Repository. Test it without a database.

The first step is the hardest. After that, it's just iteration — refactoring toward deeper insight as you learn more about both DDD and your domain. You'll make mistakes. You'll rename things. You'll move classes between bounded contexts. That's not failure. That's the process.

Stop reading and start writing.
