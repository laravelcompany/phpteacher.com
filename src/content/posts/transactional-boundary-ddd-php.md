---
title: "Transactional Boundary - Managing Transactions in Domain-Driven Design PHP"
description: "Manage transactional boundaries in Domain-Driven Design PHP applications. Learn aggregate design patterns for consistent and reliable database operations."
category: "DDD"
banner: "/logo.svg"
pubDate: "2022-11-24 21:00:00"
tags: ["DDD", "Transactions", "PHP", "Database", "Aggregates"]
---

The Aggregate pattern is the most powerful tactical pattern in Domain-Driven Design. But its power doesn't come from grouping related objects together. It comes from the underlying concept: the **transactional boundary**.

An Aggregate marks the scope within which invariants must be maintained at every stage of the lifecycle. Understanding the difference between transactional consistency and eventual consistency is the key to designing aggregates correctly.

## What Is a Transactional Boundary?

A transactional boundary is a protective boundary — but different from other boundaries in software. Other protective boundaries (modules, services, bounded contexts) prevent blurring and intermingling between separate models. A transactional boundary ensures that everything inside it stays consistent **together**.

Think of it like "One for all and all for one" — if one thing fails inside the boundary, everything fails. If one thing succeeds, everything succeeds.

Transactional consistency is immediate and atomic. Eventual consistency means the data will be consistent at some future point.

## Invariants Drive Boundaries

An **invariant** is a business rule that must always be true. Identifying invariants is how you decide where to draw transactional boundaries.

Suppose an e-commerce site awards rewards points. The business rule: once you reach 100 points, you get a 5% discount. Ask yourself: how soon must the discount take effect?

- If the discount must apply to the current purchase that earned the points, you need **transactional consistency**.
- If the discount can apply the next business day, you need **eventual consistency**.

The question isn't about database tables or object graphs. It's about how the business needs the data to behave.

## Unit of Work Pattern

Martin Fowler defines the Unit of Work pattern in *Patterns of Enterprise Application Architecture*:

> Unit of Work maintains a list of objects affected by a business transaction and coordinates the writing out of changes and the resolution of concurrency problems.

Many PHP frameworks implement Unit of Work automatically. Doctrine's EntityManager is the most prominent example:

```php
$entityManager->beginTransaction();

try {
    $invoice = new Invoice(/* ... */);
    $lineItem = new LineItem(/* ... */);

    $entityManager->persist($invoice);
    $entityManager->persist($lineItem);
    $entityManager->flush();

    $entityManager->commit();
} catch (Exception $e) {
    $entityManager->rollback();
    throw $e;
}
```

Flush and commit happen together. If either persist fails, the database stays clean.

## Manual Transactions

When domain events enter the picture, the Unit of Work may not catch everything. Consider the rule: an Application Event can only be published if the database update succeeds. This is an invariant requiring transactional consistency.

Symfony's Doctrine integration makes this explicit:

```php
use Doctrine\DBAL\Connection;

readonly class InvoiceService
{
    public function __construct(
        private Connection $connection,
        private EventDispatcher $dispatcher,
    ) {}

    public function process(Invoice $invoice): void
    {
        $this->connection->transactional(function () use ($invoice) {
            $invoice->markAsProcessed();
            $this->entityManager->flush();

            $event = new InvoiceProcessed($invoice->id());
            $this->dispatcher->dispatch($event);
        });
    }
}
```

The `transactional()` method wraps everything in a try/catch. If the dispatch fails, the database changes roll back. The Application Event only publishes on success.

## The Aggregate Pattern's Purpose

Aggregates are **not** about compositional convenience. Making aggregates too large or too small both cause problems. The correct size is determined by the invariants.

Vaughn Vernon, in *Implementing Domain-Driven Design*:

> **Rule:** Model true invariants in consistency boundaries.

The Aggregate pattern marks off the scope within which invariants must be maintained. It's a consistency boundary, not a data structure.

```php
readonly class Order
{
    /**
     * @param LineItem[] $lineItems
     */
    public function __construct(
        private OrderId $id,
        private CustomerId $customerId,
        private array $lineItems,
        private OrderStatus $status,
    ) {}

    public function addItem(ProductId $productId, int $quantity): void
    {
        if ($this->status !== OrderStatus::Draft) {
            throw new OrderNotModifiableException('Only draft orders can be modified');
        }

        $this->lineItems[] = new LineItem($productId, $quantity);
    }

    public function submit(): void
    {
        if (count($this->lineItems) === 0) {
            throw new OrderMustHaveItemsException();
        }

        $this->status = OrderStatus::Submitted;
    }
}
```

The `Order` aggregate ensures that:

- Items can only be added to draft orders
- An order cannot be submitted without items
- All line items change together within a single transaction

These are the invariants. The aggregate is the consistency boundary.

## Transactional vs. Eventual Consistency

| Characteristic | Transactional | Eventual |
|---|---|---|
| Timing | Immediate | Delayed |
| Atomicity | All or nothing | Partial allowed temporarily |
| Complexity | Higher | Lower |
| Suitable for | True invariants | Reporting, notifications, analytics |

Not everything needs to be in the same transaction. If a user changes their address, the shipping department doesn't need to know immediately — eventual consistency is fine. If an order is placed and payment is captured, those must be transactionally consistent.

## Practical Example: E-Commerce Checkout

```php
readonly class CheckoutService
{
    public function __construct(
        private Connection $connection,
        private OrderRepository $orders,
        private InventoryService $inventory,
        private PaymentGateway $payments,
        private EventDispatcher $events,
    ) {}

    public function placeOrder(CheckoutCommand $command): OrderId
    {
        return $this->connection->transactional(function () use ($command) {
            $orderId = $this->orders->nextIdentity();
            $order = Order::place($orderId, $command->customerId, $command->items);

            foreach ($command->items as $item) {
                $this->inventory->reserve($item->productId, $item->quantity);
            }

            $payment = $this->payments->charge(
                $command->paymentToken,
                $order->total(),
            );

            $order->confirmPayment($payment->transactionId());
            $this->orders->save($order);

            $this->events->dispatch(new OrderPlaced($orderId));

            return $orderId;
        });
    }
}
```

Everything inside `transactional()` either succeeds or fails as a unit. The payment capture and inventory reservation are inside the same boundary as the order persistence — because that's where the truth of "the order was placed" lives.

## Finding Your Boundaries

Three questions to determine your aggregate boundaries:

1. **What must be true?** Identify the invariants — business rules that can't be violated.
2. **How immediate?** Does the rule need to hold right now (transactional), or can it catch up (eventual)?
3. **What's the scope?** Everything that must be transactionally consistent belongs in the same aggregate.

The skill of identifying true invariants — distinguishing what must be immediate from what can be eventual — is the most valuable thing you'll learn from DDD. It's transferable across projects, industries, and languages. It's not about knowing a specific business. It's about knowing how to discover what a business needs to keep consistent.
