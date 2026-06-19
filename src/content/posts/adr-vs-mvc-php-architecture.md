---
title: "ADR vs MVC: Choosing the Right PHP Architecture"
description: "Compare ADR (Action-Domain-Response) and MVC (Model-View-Controller) patterns for PHP applications. Learn when each architecture shines and how to implement them."
pubDate: "2023-05-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["ADR", "MVC", "PHP", "Architecture", "Action-Domain-Response", "Design Patterns", "Web Development", "Application Design"]
---

MVC has been the dominant web application architecture for decades. Controllers receive requests, models represent data, and views render responses. It is familiar, widely documented, and supported by every major PHP framework.

But MVC has shortcomings. Controllers often become "fat" with mixed responsibilities. The boundary between controller and model blurs. Testing individual concerns becomes harder as the application grows.

ADR (Action-Domain-Response) offers a different decomposition. Instead of three components, it defines three layers with stricter separation. Actions handle input. Domain contains business logic. Responses handle output. The result is cleaner boundaries and more testable code.

## What You'll Learn

- The structural differences between ADR and MVC
- How ADR separates input handling, domain logic, and response building
- Implementing ADR with practical PHP examples
- When to choose ADR over MVC
- Migrating from MVC to ADR incrementally
- Real-world ADR applications in Laravel and Symfony

## MVC Architecture: The Traditional Approach

MVC splits the application into three components that interact bidirectionally.

```
Request → Controller → Model
                ↓
             View
                ↓
            Response
```

The controller receives the request, interacts with the model (domain logic), and passes data to the view for rendering.

```php
<?php

// Traditional MVC controller
class BlogController
{
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->get('blog');
            $blog = $this->blogService->create($data);
            return new RedirectResponse('/blog/' . $blog->id);
        }

        $html = $this->template->render('blog/create.php', [
            'blog' => $this->blogService->newInstance(),
        ]);

        return new Response($html);
    }
}
```

This looks clean for a single action. The problem emerges as controllers accumulate multiple actions with conditional logic for different HTTP methods, content types, and response formats.

## ADR Architecture: The Specialized Approach

ADR decomposes the same responsibility into three distinct classes per action.

```
Request → Action → Domain → Responder → Response
```

Each action gets its own Action class (input), Domain class (business logic), and Responder class (output).

```php
<?php

// ADR Action: handles input only
class BlogCreateAction
{
    public function __construct(
        private Request $request,
        private BlogCreateResponder $responder,
        private BlogService $domain,
    ) {}

    public function __invoke()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost('blog');
            $blog = $this->domain->create($data);
        } else {
            $blog = $this->domain->newInstance();
        }

        return $this->responder->response($blog);
    }
}
```

The Action class has a single responsibility: extract input from the request and pass it to the domain. It does not know about rendering, templates, or response formats.

```php
<?php

// ADR Responder: handles output only
class BlogCreateResponder
{
    public function __construct(
        private Response $response,
        private TemplateView $view,
    ) {}

    public function response(BlogModel $blog): Response
    {
        if ($blog->id) {
            // Already saved, redirect
            $this->response->setHeader(
                'Location',
                '/blog/edit/' . $blog->id
            );
        } else {
            // Show creation form
            $html = $this->view->render(
                'blog/create.php',
                ['blog' => $blog]
            );
            $this->response->setContent($html);
        }

        return $this->response;
    }
}
```

The Responder decides how to present the result. It checks the domain result and renders HTML or issues a redirect. No request logic, no domain logic.

## Directory Structure Comparison

MVC structure:

```
src/
  Controllers/
    BlogController.php
  Models/
    BlogModel.php
  Views/
    blog/
      create.php
      read.php
      update.php
      delete.php
```

ADR structure:

```
src/
  Domain/
    Blog/
      BlogModel.php
      BlogService.php
  Ui/
    Web/
      Blog/
        Create/
          BlogCreateAction.php
          BlogCreateResponder.php
        Read/
          BlogReadAction.php
          BlogReadResponder.php
        Update/
          BlogUpdateAction.php
          BlogUpdateResponder.php
        Delete/
          BlogDeleteAction.php
          BlogDeleteResponder.php
```

The ADR structure groups files by action rather than by layer. Each action has its own input handler and output handler.

## Why ADR Matters

### Separation of Concerns

MVC puts input handling and response logic in the same class. The controller decides both what to do with the request and how to render the result. ADR splits these, making each class responsible for one thing.

### Testability

Testing ADR actions is straightforward. You pass a mock request and assert the responder receives the correct domain result. Testing responders is equally simple: pass domain results and assert the response output. In contrast, testing MVC controllers requires mocking both the domain service and the view, because the controller handles both input and output concerns.

**MVC Controller Test:**

```php
<?php

use PHPUnit\Framework\TestCase;

class BlogControllerTest extends TestCase
{
    public function test_create_shows_form_on_get(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('isMethod')->willReturn(false);

        $service = $this->createMock(BlogService::class);
        $service->method('newInstance')->willReturn(new BlogModel());

        $template = $this->createMock(TemplateView::class);
        $template->method('render')->willReturn('<html>form</html>');

        $controller = new BlogController($service, $template);
        $response = $controller->create($request);

        $this->assertInstanceOf(Response::class, $response);
    }
}
```

The MVC test must mock the template engine and verify response construction. The controller's mixed responsibilities create a more complex test setup.

**ADR Action Test:**

```php
<?php

use PHPUnit\Framework\TestCase;

```php
<?php

use PHPUnit\Framework\TestCase;

class BlogCreateActionTest extends TestCase
{
    public function test_create_action_shows_form_on_get(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('isPost')->willReturn(false);

        $domain = $this->createMock(BlogService::class);
        $domain->method('newInstance')->willReturn(new BlogModel());

        $responder = $this->createMock(BlogCreateResponder::class);
        $responder->expects($this->once())
            ->method('response')
            ->with($this->isInstanceOf(BlogModel::class));

        $action = new BlogCreateAction($request, $responder, $domain);
        $action();
    }
}
```

### Parallel Development

Multiple developers can work on the same feature simultaneously. One builds the Action, another the Domain, and another the Responder. As long as they agree on the data contracts, they work independently.

### Content Negotiation

ADR makes content negotiation natural. You swap the Responder based on the requested content type.

```php
<?php

// In your routing/factory
$responder = match ($request->getHeaderLine('Accept')) {
    'application/json' => new BlogCreateJsonResponder(),
    default => new BlogCreateHtmlResponder(),
};

$action = new BlogCreateAction($request, $responder, $domain);
```

## When to Choose ADR

ADR shines when:

- **Your application has complex input handling** - Multiple authentication checks, validation steps, or authorization rules before domain logic executes.
- **Multiple response formats** - JSON, HTML, XML, and HAL endpoints for the same action.
- **Strict separation is enforced by policy** - Teams where clear boundaries between input, domain, and output improve code quality.
- **Actions are independently deployable** - Microservices or lambda functions where each endpoint is its own deployment unit.

## When MVC Is Sufficient

MVC remains appropriate when:

- **Simple CRUD applications** - Basic create, read, update, delete operations with minimal business logic.
- **Rapid prototyping** - MVC requires fewer files and less structure for early-stage projects.
- **Small teams, simple domains** - The overhead of ADR's per-action classes is not justified.

## Migrating from MVC to ADR

You can migrate incrementally. Start with the most complex controller.

```php
<?php

// Step 1: Extract responder from controller
class BlogController
{
    public function __construct(
        private BlogService $domain,
        private BlogCreateResponder $responder,
    ) {}

    public function create(Request $request): Response
    {
        if ($request->isPost()) {
            $data = $request->get('blog');
            $blog = $this->domain->create($data);
        } else {
            $blog = $this->domain->newInstance();
        }

        return $this->responder->response($blog);
    }
}

// Step 2: Extract action
class BlogCreateAction
{
    public function __construct(
        private BlogService $domain,
        private BlogCreateResponder $responder,
    ) {}

    public function __invoke(Request $request): Response
    {
        // Move request handling from controller
        if ($request->isPost()) {
            $data = $request->get('blog');
            $blog = $this->domain->create($data);
        } else {
            $blog = $this->domain->newInstance();
        }

        return $this->responder->response($blog);
    }
}
```

Each extraction improves testability without breaking the existing application.

### Content Negotiation in ADR

A key advantage of ADR over MVC is natural content negotiation. Because each action has its own responder, you can swap the responder based on the client's Accept header without changing any action code:

```php
<?php

// In your routing or factory configuration
$responder = match ($request->getHeaderLine('Accept')) {
    'application/json' => new BlogCreateJsonResponder(),
    'text/html' => new BlogCreateHtmlResponder(),
    default => new BlogCreateHtmlResponder(),
};

$action = new BlogCreateAction($request, $responder, $domain);
```

This pattern keeps content negotiation entirely in the infrastructure layer. The action never knows whether it is returning JSON or HTML.

## Real-World Use Cases

### API Gateway

Each API endpoint is an Action. Authentication and validation live in the Action. Domain logic is shared. Responders format JSON, XML, or CSV based on the Accept header.

### Admin Panels

Complex forms with conditional fields, multi-step wizards, and varied response types benefit from ADR's clear separation.

### Payment Processing

Checkout actions involve payment gateway interaction, order creation, and customer notification. ADR separates the input validation, payment domain logic, and thank-you page rendering.

### Content Management Systems

Publishing workflows with preview, draft, and published states. Actions handle the workflow transition, domain ensures consistency, and responders render the appropriate view.

## Best Practices

- **One action per class** - Each Action class handles exactly one use case.
- **Keep responders format-specific** - Create separate responders for HTML, JSON, and other formats.
- **Share domain logic** - Actions are thin. Domain services contain reusable business logic.
- **Use responders for all output decisions** - Redirect vs. render, success vs. error, cache headers.
- **Test actions and responders separately** - Action tests use mock responders. Responder tests use expected domain output.

## Common Mistakes to Avoid

- **Fat Actions** - If your Action has more than a few lines of input handling, extract validation or authorization to dedicated classes.
- **Domain logic in Responders** - Responders should only format output. Never call services or query databases in a responder.
- **Skipping ADR for "simple" endpoints** - Consistency matters. If most endpoints use ADR, new endpoints should too.
- **Over-engineering** - Do not use ADR for applications with three endpoints. MVC is fine for small projects.
- **Tight coupling between Action and Responder** - Actions should not know about response details. They call the responder and trust it to handle output.

## Frequently Asked Questions

### Is ADR a replacement for MVC?

ADR is an alternative, not a replacement. Many applications use both: ADR for complex features, MVC for simple CRUD.

### Does Laravel support ADR?

Laravel does not enforce ADR, but you can implement it. Use invokable controllers as Actions and dedicated form request classes for input handling.

### Is ADR compatible with DDD?

Yes. ADR's Domain layer maps naturally to DDD's application services and domain model. ADR provides the outer structure; DDD organizes the inner domain.

### Does ADR work with REST APIs?

Adopt! Each REST endpoint becomes an Action. Content negotiation via Responders handles JSON, XML, and HAL responses.

### What about the "C" in MVC? Is it a controller?

In MVC, the controller handles input and selects output. ADR splits this into Action (input) and Responder (output). Both are valid approaches.

### How do I handle middleware with ADR?

Middleware runs before the Action. Authentication, logging, and CORS headers are middleware concerns in both MVC and ADR.

### Is ADR more code than MVC?

Yes, ADR typically requires more files. Each action has its own Action and Responder classes. The tradeoff is clearer separation and better testability.

## Conclusion

ADR brings discipline to PHP web applications by enforcing strict separation between input handling, domain logic, and output rendering. While MVC remains a valid and productive pattern, ADR's specialization makes it easier to test, maintain, and extend complex applications.

The choice between ADR and MVC depends on your application's complexity, team size, and testing requirements. Start with MVC for simple projects. Introduce ADR when controllers grow unwieldy and you need clearer boundaries.

Either way, the most important architectural principle remains: separate your concerns. Whether you use two classes (MVC) or three (ADR), keep input, logic, and output distinct.
