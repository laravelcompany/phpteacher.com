---
title: "Try or Try Not — DDD Consistency Boundaries in REST APIs"
description: "Extend transactional consistency boundaries to REST API responses in PHP. Learn CQRS, try/catch patterns, and error handling for domain-driven PHP applications."
pubDate: "2023-01-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "REST API", "CQRS", "PHP", "Error Handling", "CakePHP", "Transactions"]
selected: false
---

Yoda said "Do or do not; there is no try." That works for Jedi training, but it is terrible advice for PHP developers. In modern application development, try and catch are fundamental to building reliable, consistent APIs. The real mantra should be "try or try not" — wrapping operations in try/catch blocks and handling both success and failure paths with equal discipline.

This article explores how to extend transactional consistency boundaries to RESTful API responses. The core idea is simple: every HTTP request that modifies state should either complete entirely within a consistent boundary or fail cleanly, leaving the system in a known state. The mechanism for achieving this is the humble try/catch block, elevated from a language construct to an architectural pattern.

## What You'll Learn

- How to extend consistency boundaries from the database to the HTTP response
- Building a response object that captures error information for API clients
- CQRS-inspired separation of query and command controllers
- Wrapping database transactions inside try/catch for atomic command operations
- Error handling patterns that preserve domain invariants across network boundaries

## The Consistency Boundary Problem

Traditional transaction management focuses on the database. Start a transaction, perform operations, commit or roll back. The database is consistent, but what about the HTTP response? If the database commit succeeds but the response serialization fails, the client receives an error despite the operation succeeding on the server. If the commit fails but the error response has a bug, the client might think the operation succeeded.

The solution is to widen the consistency boundary to include the response itself. Every request that modifies state should promise one of two outcomes:

- A complete, internally-consistent success response
- A complete, internally-consistent error response

There is no third option where the database is updated but the client does not know the outcome.

## The CtgoResponse Object

A response object captures everything needed to communicate the result of an operation back to the client. The name comes from the "Clay Target Go!" sports team management application — an API-first system built on CakePHP — but the pattern applies to any PHP framework.

```php
<?php

declare(strict_types=1);

namespace App\Lib;

class CtgoResponse
{
    public function __construct(
        private readonly int $statusCode,
        private readonly string $statusText,
        private readonly ?string $errorSummary = null,
        private readonly ?string $errorDescription = null,
        private readonly mixed $body = null,
    ) {}

    public static function success(mixed $body = null): self
    {
        return new self(
            statusCode: 200,
            statusText: 'OK',
            body: $body,
        );
    }

    public static function error(
        int $statusCode,
        string $statusText,
        string $errorSummary,
        ?string $errorDescription = null,
    ): self {
        return new self(
            statusCode: $statusCode,
            statusText: $statusText,
            errorSummary: $errorSummary,
            errorDescription: $errorDescription,
        );
    }

    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function toArray(): array
    {
        if ($this->isSuccess()) {
            return [
                'status' => 'success',
                'data' => $this->body,
            ];
        }

        return [
            'status' => 'error',
            'error' => [
                'summary' => $this->errorSummary,
                'description' => $this->errorDescription,
            ],
        ];
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
```

The response object enforces a consistent structure. Every API response follows the same shape. Clients parse one format, not a dozen variations. The success and error factory methods make the intent explicit at the call site.

## The Exception Bridge

A response object is useful, but it needs a mechanism to travel from the domain layer to the controller. PHP exceptions provide that mechanism. A custom exception class carries the response object through the call stack without losing information.

```php
<?php

declare(strict_types=1);

namespace App\Lib;

class CtgoException extends \RuntimeException
{
    public function __construct(
        private readonly CtgoResponse $response,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $response->errorSummary ?? 'Unknown error',
            code: $response->statusCode(),
            previous: $previous,
        );
    }

    public function getResponse(): CtgoResponse
    {
        return $this->response;
    }
}
```

The `CtgoException` extends `RuntimeException` and wraps a `CtgoResponse`. When a domain operation fails, the code throws this exception. The controller catches it and uses the embedded response object to build the HTTP response. No information is lost between the domain layer and the transport layer.

## Query vs. Command Controllers

CQRS — Command Query Responsibility Segregation — separates read operations from write operations. Query controllers handle GET requests and return data. Command controllers handle POST, PATCH, PUT, and DELETE requests and change state.

Separating the two makes the transaction boundary explicit. Query controllers never modify state, so they have no need for transactions. Command controllers always modify state, so every one of them wraps its logic in a consistency boundary.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Lib\CtgoResponse;
use App\Lib\CtgoException;

/**
 * Query controller — read-only
 */
class TeamsController extends AppController
{
    public function view(int $id): void
    {
        try {
            $team = $this->Teams->get($id);
            $response = CtgoResponse::success($team->toArray());
            $this->set('response', $response);
        } catch (RecordNotFoundException $e) {
            $response = CtgoResponse::error(
                statusCode: 404,
                statusText: 'Not Found',
                errorSummary: 'Team not found',
                errorDescription: "No team exists with ID {$id}",
            );
            $this->response = $this->response->withStatus(404);
        } finally {
            $this->set('_serialize', ['response']);
            $this->viewBuilder()->setOption('serialize', ['response']);
        }
    }
}
```

The query controller still uses try/catch, but the scope is limited to data retrieval errors — typically 404 responses for missing resources.

## Command Controllers with Database Transactions

Command controllers add a database transaction layer inside the try/catch. The transaction ensures that every state change is atomic. If any part of the operation fails, the entire transaction rolls back.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Lib\CtgoResponse;
use App\Lib\CtgoException;
use Cake\Database\Connection;

/**
 * Command controller — write operations
 */
class TeamsController extends AppController
{
    public function __construct(
        private readonly Connection $connection,
        // ...
    ) {}

    public function add(): void
    {
        $this->connection->begin();

        try {
            $team = $this->Teams->newEntity($this->request->getData());
            
            if ($this->Teams->save($team)) {
                $this->connection->commit();
                $response = CtgoResponse::success($team->toArray());
                $this->response = $this->response->withStatus(201);
            } else {
                $this->connection->rollback();
                $response = CtgoResponse::error(
                    statusCode: 400,
                    statusText: 'Bad Request',
                    errorSummary: 'Validation failed',
                    errorDescription: $this->formatErrors($team->getErrors()),
                );
                $this->response = $this->response->withStatus(400);
            }
        } catch (CtgoException $e) {
            $this->connection->rollback();
            $response = $e->getResponse();
            $this->response = $this->response->withStatus($response->statusCode());
        } catch (\Throwable $e) {
            $this->connection->rollback();
            $response = CtgoResponse::error(
                statusCode: 500,
                statusText: 'Internal Server Error',
                errorSummary: 'An unexpected error occurred',
                errorDescription: $e->getMessage(),
            );
            $this->response = $this->response->withStatus(500);
        } finally {
            $this->set('response', $response);
            $this->viewBuilder()->setOption('serialize', ['response']);
        }
    }
}
```

The pattern is consistent:

1. Begin the transaction
2. Perform the domain operation
3. Commit on success, roll back on failure
4. Catch domain exceptions for known error cases
5. Catch generic exceptions for unexpected errors
6. Build the response object in every branch
7. Serialize and return

No code path leaves the response undefined. Every possible outcome produces a complete, internally-consistent response.

## The Request/Response Consistency Boundary

The full boundary includes both the database transaction and the HTTP response serialization. Consider what happens if serialization fails after the commit:

```php
// Problematic pattern
$this->connection->begin();

try {
    $team = $this->Teams->save($data);
    $this->connection->commit();
    
    // Serialization happens AFTER commit
    // If this fails, the database is committed but the client gets an error
    $response = $this->serialize($team);
    $this->set('response', $response);
} catch (\Throwable $e) {
    $this->connection->rollback();
    // The commit already happened — rollback does nothing
}
```

The solution is to prepare the response object before committing:

```php
// Consistent boundary
$this->connection->begin();

try {
    $team = $this->Teams->save($data);
    
    // Prepare the response while still inside the transaction
    $response = CtgoResponse::success($team->toArray());
    
    // Now commit — everything is ready
    $this->connection->commit();
} catch (\Throwable $e) {
    $this->connection->rollback();
    $response = $this->buildErrorResponse($e);
} finally {
    // Serialization happens after the transaction
    // but the response object is already built
    $this->set('response', $response);
}
```

Preparing the response before committing ensures that response construction failures are caught inside the transaction boundary. If the response object cannot be built, the transaction rolls back, and the system stays consistent.

## Real-World Use Cases

**Sports team management APIs.** The Clay Target Go! application manages shooting sports teams — participants, events, scores, and standings. Every registration, score submission, and team roster change is a command that must be atomic. The consistency boundary ensures that a partial registration never leaves a participant half-enrolled.

**Payment processing endpoints.** Payment APIs are the most obvious use case for transactional consistency boundaries. A charge that succeeds on the processor side but fails to record locally creates reconciliation nightmares. The boundary ensures that the local record and the processor state stay in sync.

**User registration flows.** Registration typically involves creating a user record, sending a verification email, and initializing default settings. If the email fails but the user is created, the user is stuck in an unverifiable state. A transaction boundary prevents this.

**Inventory reservation systems.** E-commerce platforms that reserve inventory during checkout need atomic operations. If the reservation succeeds but the response fails, the inventory is reserved but the customer sees an error. The boundary prevents phantom reservations.

**Multi-step form submissions.** Long forms that submit multiple related records in a single request benefit from transaction boundaries. If one step of submission fails, all previous steps roll back, and the user can resubmit without data duplication.

## Best Practices

**Always prepare responses before committing transactions.** Build the response object, then commit. This ensures that response construction failures are caught inside the consistency boundary.

**Use custom exception classes for domain errors.** A `CtgoException` that carries a response object preserves error context across architectural boundaries. Generic exceptions lose information.

**Separate query and command controllers explicitly.** Even in small applications, the mental separation between reads and writes prevents accidental state changes in query handlers.

**Keep command controllers thin.** The controller's job is to begin the transaction, call the domain service, catch exceptions, and build the response. Business logic belongs in domain services and entities, not in controllers.

**Log exceptions before rethrowing.** When catching unexpected exceptions in the controller, log the full stack trace before returning a sanitized error response. The client gets a clean 500 error, and the operations team gets the diagnostic information.

**Set the HTTP status code immediately.** When assigning the response object, also set the response status code. The two must stay synchronized.

## Common Mistakes

**Committing the transaction before preparing the response.** The classic boundary violation. If response construction fails after commit, the client receives an error but the state change persists.

**Using generic exceptions everywhere.** Throwing `\Exception` or `\RuntimeException` without context forces the controller to parse string messages or inspect error codes. Custom exception classes are cleaner and more reliable.

**Mixing query and command logic in the same controller method.** A controller action should either read data or write data, never both. Mixing them makes the transaction boundary ambiguous.

**Forgetting to set the HTTP status code.** Building a 404 response object but leaving the HTTP status at 200 confuses API clients. Always set both.

**Swallowing exceptions without logging.** Catching an exception, building an error response, and moving on without logging the original error makes debugging nearly impossible.

**Nesting transactions unnecessarily.** Most databases do not support nested transactions in the way developers expect. Use savepoints if you need sub-operations, or restructure the code to use a single transaction.

## Frequently Asked Questions

**Is this pattern specific to CakePHP?** No. The `CtgoResponse` and `CtgoException` pattern works with any PHP framework. The controller implementation varies, but the architectural principle is framework-agnostic.

**Do I need full CQRS to use this pattern?** No. Even without event sourcing or separate read models, separating query and command controllers at the HTTP layer is a lightweight CQRS application that pays dividends in clarity and safety.

**What about read operations that need transactions?** Some read operations — like reporting queries — might need database-level read locks or snapshot isolation. These are query concerns and should not modify state. The transaction is read-only.

**Can this pattern work with an event-driven architecture?** Yes. Publish domain events inside the transaction boundary. If event publishing fails, the transaction rolls back. This keeps the write model consistent even when events propagate asynchronously.

**How do I handle file uploads within the consistency boundary?** Treat file storage as a side effect with its own consistency requirements. Save the file, commit the database, and if the database commit fails, delete the uploaded file. Some systems prefer to stage files and only move them into place after a successful commit.

**Should I wrap every controller action in a try/catch?** Every command action needs a try/catch that handles both domain exceptions and unexpected exceptions. Query actions need try/catch primarily for not-found scenarios. Using a framework-level exception handler as a safety net is also recommended.

**What HTTP status codes should I use for domain errors?** Use 400 for validation failures, 401 for authentication failures, 403 for authorization failures, 404 for not-found errors, 409 for conflict errors, and 422 for unprocessable entity errors. Map domain exceptions to the most specific appropriate code.

**How does this pattern interact with API versioning?** Response objects are internal to the application. The serialization layer can transform them into different API version formats. The consistency boundary is unaffected by versioning.

**Can I use this pattern with GraphQL?** Yes. The try/catch boundary wraps the resolver or mutation handler. The `CtgoResponse` maps to GraphQL error types. The transaction management is identical.

**Does this pattern work with async processing?** For synchronous request-response flows, yes. For async command processing, the consistency boundary shifts to the message handler. The response is accepted immediately, and the result is delivered asynchronously.

## Conclusion

Yoda's advice was never meant for API developers. In modern PHP, try is essential. Every command that modifies state needs a consistency boundary that extends from the database through the response. The `CtgoResponse` and `CtgoException` pattern provides a clean, testable way to enforce that boundary.

Separate your query and command concerns. Wrap every command in a database transaction. Prepare your response before committing. Catch exceptions at the controller level and map them to structured error responses. Your API clients will thank you, your database will stay consistent, and your debugging sessions will be shorter.

The next time you write a POST or PATCH endpoint, structure it with an explicit try/catch boundary. Start the transaction before you do anything. Build the response object inside the try block. Commit only after the response is ready. Your future self — and everyone who maintains your code — will appreciate the discipline.
