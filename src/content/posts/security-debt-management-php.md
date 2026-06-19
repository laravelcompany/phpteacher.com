---
title: "Security Debt Management - Reducing Technical Security Debt in PHP"
description: "Reduce technical security debt in PHP applications. Learn to identify, prioritize, and remediate security issues in legacy and modern codebases."
category: "Security"
banner: "/logo.svg"
pubDate: "2022-12-18 21:00:00"
tags: ["Security Debt", "PHP", "Technical Debt", "Security", "OWASP"]
---

Every successful development team has two things in common: they shipped a product, and they accepted compromises to make that shipment possible. Developers are inevitably forced to compromise. Commit a quick fix without tests. Ship an experimental module without documentation. Add a feature to satisfy a Friday afternoon client request.

Each one of these compromises is debt.

## Not All Debt Is Bad

Business schools teach a nuanced view of debt. In personal finances, debt is usually negative. In corporate finances, it is often a necessary tool for growth. Businesses are rated on their effective use of debt.

Technical debt works the same way. It is not always bad. It is a powerful tool that helps teams ship faster, iterate more quickly, and launch products to customers' delight.

Ward Cunningham, who coined the term, said it best: "Shipping first time code is like going into debt. A little debt speeds development so long as it is paid back promptly with a rewrite."

The negative aspects of technical debt occur when the to-do list grows and affects the stability and security of the application itself.

## The Security Implications of Technical Debt

Complexity within a large codebase hides security issues. Data passes through the lifecycle of an application, and the longer it lives in memory, the easier it is to lose track of where it came from. Determining whether a specific value was properly sanitized earlier in a program's execution becomes difficult.

The security impact of technical debt is often subtle.

Every project has external dependencies. Libraries we import to save time. External systems with which we integrate. The underlying operating system and runtime. Every moving part added to an application is a compromise. It is trusting that some other development team cares as much about your application's security as you do.

And trusting that your team will keep these dependencies up to date.

### The SolarWinds Lesson

The SolarWinds breach of late 2020 is a definitive example. SolarWinds' products were used by over 300,000 customers, including key Fortune 500 companies. Remote attackers injected code into software updates distributed by SolarWinds itself. Organizations dependent on and trusting of SolarWinds inadvertently granted those same attackers full access to their systems.

The breach eroded trust in upstream dependencies. It is critical that engineers work rapidly to ship product value. When that rapid turnaround necessitates reliance on third-party code, you intentionally weaken the security stance of your own application. That library, vendor, or other dependency is a convenient shortcut that also increases security debt.

## Identifying Security Debt

Security debt hides in plain sight. Here are the most common sources in PHP applications:

### Outdated Dependencies

Every `composer.json` lists dependencies with version constraints. When was the last time you reviewed them?

```bash
composer outdated --direct
```

This command shows every direct dependency with a newer version available. If you are running a version more than a year old, you likely have unpatched vulnerabilities. Tools like `composer audit` (available since Composer 2.4) check known vulnerabilities:

```bash
composer audit
```

### Weak Input Validation

Validation scattered across controllers, models, and middleware creates gaps. A Value Object approach consolidates validation in one place:

```php
final class EmailAddress
{
    public function __construct(
        public readonly string $value
    ) {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
    }
}
```

Every route that accepts an email address uses the same validation. No gaps.

### Inconsistent Authentication Logic

Authentication sprinkled across controllers with different implementations is a security debt hotspot. Extract authentication into middleware or a dedicated service:

```php
final class AuthenticationMiddleware
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenService $tokens,
    ) {
    }

    public function handle(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $token = $request->getHeaderLine('Authorization');

        if (! $this->tokens->isValid($token)) {
            return new Response(401, [], 'Unauthorized');
        }

        $user = $this->tokens->decode($token);
        return $handler->handle(
            $request->withAttribute('user', $user)
        );
    }
}
```

One implementation. One place to audit. One place to fix.

### Unvalidated Redirects and Forwards

PHP applications that accept a redirect URL as a parameter without validation are vulnerable to phishing. Every open redirect in your codebase is security debt:

```php
// Security debt: open redirect
header('Location: ' . $_GET['redirect']);
```

The fix is to validate against a whitelist:

```php
$allowedRedirects = ['/dashboard', '/profile', '/settings'];

$redirect = $_GET['redirect'] ?? '/dashboard';
if (! in_array($redirect, $allowedRedirects, true)) {
    $redirect = '/dashboard';
}

header('Location: ' . $redirect);
```

Better: store redirect targets in session state rather than accepting them as parameters.

## Prioritizing Security Debt Fixes

Not all security debt is equal. A prioritization framework helps you focus on what matters most.

**Critical** — Active exploitation or high likelihood of exploitation. Remote code execution, SQL injection, authentication bypass. Fix immediately.

**High** — Significant data exposure risk. XSS with admin access, IDOR that exposes PII, CSRF on state-changing actions. Fix within the current sprint.

**Medium** — Elevated privileges, information disclosure, missing security headers. Schedule within the next iteration.

**Low** — Best practices, hardening, defense in depth. HSTS headers, CSP policy tightening, logging improvements. Add to the backlog with a target sprint.

## Tools for Tracking Security Debt

### OWASP Dependency-Check

```bash
docker run --rm \
  -v $(pwd):/src \
  owasp/dependency-check \
  --scan /src/composer.lock \
  --format HTML \
  --out /src/reports
```

This generates an HTML report of known CVEs in your dependencies.

### Psalm and PHPStan with Security Rules

Static analysis tools catch security issues before they reach production:

```bash
vendor/bin/psalm --taint-analysis
```

Psalm's taint analysis tracks user input through your application and flags paths where unsanitized data reaches sensitive operations.

### SARIF and SIEM Integration

Export security debt findings in SARIF format for ingestion into your security monitoring infrastructure. This makes debt visible alongside other security telemetry.

## Making Security Debt Visible

Security debt that nobody sees never gets paid. Make it visible through:

1. **A dedicated security debt board.** Alongside feature work and bug fixes, security debt items have their own column.

2. **Debt budgets.** Decide what percentage of each sprint goes to debt reduction. Start with 10 percent. Adjust based on how much time emergency security fixes consume.

3. **Debt tracking in code.** Comment `TODO: SECURITY` in the specific locations where debt exists. Most IDEs surface these across the codebase:

```php
// TODO: SECURITY — This query uses string interpolation.
// Refactor to prepared statement.
$stmt = $pdo->query("SELECT * FROM users WHERE id = " . $id);
```

4. **Security debt burndown charts.** Track the number of open security debt items over time. A growing curve signals trouble. A shrinking curve shows progress.

## Getting Out of Security Debt

Technical debt is unavoidable. One day, your team will make a necessary compromise to get a feature out the door. This is not a bad thing, but it needs tracking.

Like financial debt, if technical debt is not paid off over time, it accrues interest. This interest manifests as problematic legacy code and longer lead times for refactoring or bug fixing.

Track compromises when they happen. Immediately make plans for revisiting the code to reduce the impact of the debt you just incurred. Devote a future sprint to refactoring from a different angle. Schedule downtime for critical system upgrades. Make room for long-term solutions as you take short-term shortcuts.

### The 20 Percent Rule

Allocate 20 percent of each development cycle to debt reduction:

```php
// Sprint planning
$sprint = [
    'features' => 0.60,   // 60% new work
    'debt' => 0.20,        // 20% technical/security debt
    'bugs' => 0.15,        // 15% bug fixes
    'learning' => 0.05,    // 5% experimentation
];
```

Each debt cycle addresses one or two items from the prioritized list. Over four sprints, you have effectively dedicated one full sprint to debt reduction.

## Intentional Coding

Security debt should never be incurred by mistake. Discuss priorities with the team and work together to find agreement on the best way to get the product out the door. Commit to following up on deprioritized work so it does not disappear into a perpetual backlog.

Intentional technical debt gets your product in front of customers before the competition. Intentionally paying off that same debt keeps your product stable and secure long into the future.

Make security debt visible. Prioritize it honestly. Pay it down consistently. The applications you build today will be the legacy code someone else inherits tomorrow. Leave them with a codebase that is not only functional but secure.
