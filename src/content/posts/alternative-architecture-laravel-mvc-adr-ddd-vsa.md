---
title: "Beyond MVC: Alternative Laravel Architectures"
description: "Explore MVC, ADR, DDD, and Vertical Slice Architecture in Laravel. Learn when to move beyond traditional patterns for scalable applications."
pubDate: "2023-11-20 22:00:00"
category: "laravel"
banner: "/logo.svg"
tags: ["Laravel", "MVC", "ADR", "Domain Driven Design", "Vertical Slice Architecture", "PHP Architecture", "Software Design", "Laravel Patterns"]
selected: false
---

Laravel developers learn Model-View-Controller (MVC) from day one. The documentation reinforces it. The community embraces it. And for good reason — MVC provides an intuitive separation of concerns that works beautifully for straightforward applications.

But what happens when your application outgrows MVC? When features pile up, your team expands, and the architecture that once served you well becomes a bottleneck? Most developers look to microservices, but that's not always the right answer. Sometimes the solution is right inside your Laravel application, waiting in alternative architectural patterns.

## What You'll Learn

- The limitations of MVC in large-scale Laravel applications
- How Action Domain Responder (ADR) improves separation of concerns
- Combining Domain Driven Design with Vertical Slice Architecture
- Practical code examples for each architectural approach
- When to use each pattern and how to transition between them

## Understanding MVC's Role and Limitations

MVC separates your code into three layers: Models handle data, Views handle presentation, and Controllers handle logic. This works wonderfully for simple websites, blogs, and even e-commerce stores where you lean on external services for complex operations.

The trouble starts when business logic becomes genuinely complex. Controllers swell with conditional logic. Models accumulate unrelated responsibilities. Service classes multiply without clear organization. Your application still works, but making changes becomes increasingly risky.

Consider a shipping management application. You have Stock Management, Quality Control, Logistics, Operations, and Finance. In MVC, these concerns quickly blur together. A delivery acceptance endpoint in MVC might look clean initially, but as requirements grow, the controller becomes a dumping ground for orchestration logic.

## Action Domain Responder (ADR)

ADR is an evolution of MVC that explicitly separates the response logic from the controller. The flow is straightforward: an Action receives a request, delegates to a Domain layer for business logic, and passes the result to a Responder that handles the response format.

The key difference from Laravel's Action pattern is that ADR split actions into domain-specific areas with dedicated responder classes. Let's see a practical comparison.

### MVC Implementation

Here's how a delivery acceptance endpoint might look in traditional MVC:

```php
<?php

final class DeliveryAcceptedController
{
    public function __construct(
        private readonly DeliveryService $deliveryService,
    ) {}

    public function __invoke(
        Request $request,
        string $delivery
    ): JsonResponse {
        if (!$this->deliveryService->expecting($delivery)) {
            throw new UnexpectedDeliveryException(
                message: "Unexpected delivery found: {$delivery}",
            );
        }

        if (!$this->deliveryService->validateContents(
            $delivery,
            $request->get('items')
        )) {
            throw new DeliveryItemsMisalignmentException(
                message: "Failed to validate contents of delivery, manual check is required.",
            );
        }

        dispatch(new DeliveryProcessed($delivery));

        return new JsonResponse(
            data: [
                'message' => 'Delivery accepted.',
                'status' => DeliveryStatus::ACCEPTED,
            ],
            status: Status::ACCEPTED,
        );
    }
}
```

This works, but the controller is responsible for response formatting, exception handling, and business orchestration. Any change to response format requires modifying this class.

### ADR Implementation

Now the same endpoint in ADR:

```php
<?php

final class DeliveryAcceptedController
{
    public function __construct(
        private readonly DeliveryService $deliveryService,
        private readonly DeliveryFailedResponder $failed,
        private readonly DeliverySuccessfulResponder $responder,
    ) {}

    public function __invoke(
        Request $request,
        string $delivery
    ): JsonResponse {
        if (!$this->deliveryService->expecting($delivery)) {
            return $this->failed->respondWithUnexpectedDelivery(
                $request,
                $delivery,
            );
        }

        if (!$this->deliveryService->validateContents(
            $delivery,
            $request->get('items')
        )) {
            return $this->failed->respondWithInvalidContents(
                $request,
                $delivery,
            );
        }

        return $this->responder->deliverySuccessful($delivery);
    }
}
```

The controller's single job is now accepting the request, checking failure conditions, and returning a response. How failures are communicated is the responder's concern. What happens after success (dispatching jobs, sending notifications) is also the responder's concern. This separation means you can change response formats without touching the controller.

### Drawbacks of ADR

Code repetition can become a real problem with ADR. Similar response patterns get duplicated across responders, and it doesn't fundamentally solve the scaling challenges that come with growing applications. For that, we need to look further.

## Domain Driven Design Meets Vertical Slice Architecture

Vertical Slice Architecture (VSA) is almost the opposite of Clean Architecture. Instead of organizing by technical concern (controllers, models, views), you organize by feature. Each slice is a vertical cross-section through all layers, containing everything needed for a specific feature.

When you pair VSA with Domain Driven Design (DDD), each slice aligns with a bounded context or domain. Features don't talk to other features. Each slice is independent.

### Directory Structure

Here's how a VSA Laravel application might look:

```
└── app
    ├── Deliveries
    │   ├── Commands
    │   ├── Controllers
    │   │   └── AcceptNewDeliveryController.php
    │   ├── DeliveryServiceProvider
    │   ├── Events
    │   │   ├── DeliveryAccepted.php
    │   │   ├── DeliveryProcessed.php
    │   │   └── DeliveryReceived.php
    │   ├── Jobs
    │   │   └── DeliveryProcessed.php
    │   ├── Routes
    │   │   ├── api.php
    │   │   └── web.php
    │   ├── Services
    │   │   └── IncomingDeliveryService.php
    │   └── Validators
    │       └── NewDeliveryValidator.php
    ├── Finance
    ├── Logistics
    └── StockManagement
```

Each domain is self-contained with its own controllers, routes, events, and services. This structure scales naturally as your application grows because new features don't need to find homes in existing directories.

### Inter-Slice Communication

Slices shouldn't call each other directly. Instead, use asynchronous messaging for cross-domain communication:

```php
<?php

final class IncomingDeliveryService implements CommunicationService
{
    public function __construct(
        private readonly MessagingClient $message,
        private readonly DeliveryRepository $repository,
    ) {}

    public function expected(string $delivery): bool
    {
        return $this->repository->expectingDeliveryId(
            id: $delivery,
        );
    }

    public function validateContents(
        array $items,
        string $delivery
    ): bool {
        $valid = $this->repository->deliveryContainsItems(
            items: $items,
            id: $delivery,
        );

        if (!$valid) {
            $this->message->distribute(
                new InvalidContents($items, $delivery)
            );

            return false;
        }

        return true;
    }
}
```

Using Apache Kafka, RabbitMQ, or even Laravel's event system, domains communicate through messages rather than direct method calls. This keeps bounded contexts properly isolated.

## Real-World Use Cases

- **Multi-tenant SaaS platforms**: Each tenant's features live in independent slices
- **Enterprise ERP systems**: Different business domains (inventory, billing, HR) map naturally to slices
- **Marketplace applications**: Buyer and seller domains have fundamentally different concerns
- **Fintech platforms**: Audit, transaction, and compliance domains require strict separation
- **Healthcare systems**: Patient data, billing, and scheduling domains need independent deployment capability

## Best Practices

- **Start with MVC**: Don't over-architect from day one. Migrate patterns as complexity demands
- **Use service providers per slice**: Register routes, bindings, and config within each domain
- **Keep slices truly independent**: Direct coupling between slices defeats the purpose
- **Prefer composition over inheritance**: VSA works best with composed services, not deep class hierarchies
- **Test at slice boundaries**: Integration tests should verify slice interactions through events or messages

## Common Mistakes to Avoid

- **Adding VSA too early**: Premature architectural complexity wastes time on imaginary problems
- **Leaving shared code unorganized**: Common utilities should live in a dedicated shared kernel
- **Making slices too small**: A slice should represent a meaningful business capability, not a single action
- **Ignoring the deployment implications**: True VSA works best when slices can be developed and deployed independently
- **Mixing architectural patterns inconsistently**: Pick a pattern and apply it uniformly across the codebase

## Frequently Asked Questions

**Can I combine ADR with Vertical Slice Architecture?**
Yes, ADR works naturally within each slice. The controller, action, and responder live together in the feature directory.

**Does VSA replace DDD?**
No, VSA complements DDD. VSA is about code organization; DDD is about domain modeling. They work best together.

**How do I handle cross-cutting concerns like authentication?**
Middleware and service providers work at the application level and wrap around slices. Each slice shouldn't implement its own auth.

**Will VSA make my Laravel application slower?**
No, the organization is purely structural. Performance depends on code quality and database queries, not directory structure.

**How do I migrate an existing MVC application to VSA?**
Start by identifying bounded contexts, then move related classes into feature directories one domain at a time. Use strangler fig pattern.

**Can I deploy slices independently?**
With monorepo tools and modular service providers, yes. Each slice can be enabled or disabled through configuration.

**What replaces Laravel's make commands in VSA?**
Use an IDE with custom file templates or build your own generators. The artisan make commands won't know about your slice structure.

## Transitioning Between Architectures

Migrating from MVC to an alternative architecture doesn't require a complete rewrite. Use the strangler fig pattern: identify bounded contexts one at a time and extract them into slices.

Start with the most stable, well-understood domain in your application. Extract its controllers, models, and views into a feature directory. Set up its own service provider. Keep the old code in place until the new structure is proven.

Over months, the old MVC directories shrink as slices grow. Eventually, what remains is either migrated or retired.

### Identifying Slice Boundaries

Good slice boundaries follow business domains, not technical convenience. Signs that a group of features forms a natural slice:

- They relate to the same business entity (Orders, Invoices, Users)
- They change for the same business reasons
- They share domain language that differs from other parts of the application
- They could theoretically be extracted into their own microservice

### Common Slice Patterns

**Feature slices**: Each user-facing feature gets its own directory with controllers, views, and domain logic. Best for applications where features are relatively independent.

**Domain slices**: Organize by domain concept (Billing, Shipping, Inventory). Best for applications with complex domain logic.

**Layer + domain hybrid**: Keep technical layers (Controllers, Models) but split into domain subdirectories. Best for gradual migration paths.

## Testing Alternative Architectures

Each architecture pattern affects how you test.

### Testing ADR

ADR makes testing straightforward because responders handle response logic independently:

```php
<?php

final class DeliverySuccessfulResponderTest extends TestCase
{
    public function testReturnsJsonResponse(): void
    {
        $responder = new DeliverySuccessfulResponder();

        $response = $responder->deliverySuccessful('DEL-001');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

### Testing VSA Slices

Each slice can be tested independently, with dependencies mocked at slice boundaries:

```php
<?php

final class AcceptDeliveryFeatureTest extends TestCase
{
    public function testAcceptValidDelivery(): void
    {
        $messagingClient = $this->createMock(MessagingClient::class);
        $repository = $this->createMock(DeliveryRepository::class);

        $service = new IncomingDeliveryService(
            $messagingClient,
            $repository
        );

        $repository->method('expectingDeliveryId')
            ->willReturn(true);

        $result = $service->expected('DEL-001');

        $this->assertTrue($result);
    }
}
```

## When to Stay with MVC

Not every application needs DDD or VSA. MVC remains the right choice when:

- The application has straightforward CRUD operations with limited business logic
- The team is small and the codebase will stay small
- The application is a prototype or MVP where speed matters more than long-term maintainability
- The domain complexity is low — forms that store and retrieve data without complex rules

The key insight: start with MVC and add architectural complexity only when MVC actively causes pain. Premature architecture is as harmful as no architecture.

The key insight is that you don't need to choose one pattern for your entire application. Start with MVC, introduce ADR where response logic becomes unwieldy, and organize into slices when features grow too numerous to manage in flat directories. Software architecture is a journey, not a destination.

Your architecture should serve your application, not constrain it. When MVC starts fighting you instead of helping you, it's time to explore alternatives. Start small, refactor deliberately, and let your application's needs guide your architectural decisions.

**[Learn more about Vertical Slice Architecture](https://www.jimmybogard.com/vertical-slice-architecture/) | [Explore Domain Driven Design](https://www.domainlanguage.com/ddd/)**
