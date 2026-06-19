---
title: "PSR-12 PHP Coding Style Guide - Everything You Need to Know"
description: "Master PSR-12, the extended PHP coding style standard. Learn formatting rules for classes, methods, control structures, and how to automate style checking in your projects."
pubDate: "2022-04-22 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PSR-12", "PHP", "Coding Standards", "PSR", "PHP-FIG", "Code Style", "PHP-CS-Fixer", "Best Practices"]
selected: false
---

You open a pull request. The diff looks correct. The logic is sound. The tests pass. Then a teammate comments: *"Can you run the linter? The formatting doesn't match the rest of the codebase."*

This friction is the entire reason PSR-12 exists. When every developer on a team formats code differently — tabs vs spaces, brace on the same line vs the next line, uppercase `NULL` vs lowercase `null` — code reviews devolve into style debates instead of focusing on architecture and correctness. PSR-12 eliminates that noise.

PSR-12 is the PHP Framework Interop Group's (PHP-FIG) extended coding style recommendation. Released in 2019, it replaces the older PSR-2 standard and brings PHP formatting rules in line with modern PHP features: typed properties, anonymous classes, union types, and more. If you write PHP code today — whether you use Laravel, Symfony, WordPress, or a custom framework — PSR-12 is the closest thing the language has to an official style guide.

By the end of this article you'll understand every major rule in PSR-12, see correct and incorrect examples for each, learn how to automate enforcement with PHP-CS-Fixer, and get a preview of where the standard is heading with PER (PHP Evolving Recommendation).

## What Is PSR-12?

PSR-12 stands for **PHP Standard Recommendation 12: Extended Coding Style Guide**. It was accepted by PHP-FIG on October 15, 2019, and it supersedes PSR-2, which had been the de facto PHP style standard since 2012.

PSR-2 covered the basics: indentation, keywords, namespaces, class structure, and control flow. PSR-12 extends those rules to cover newer language features and tightens ambiguous wording from PSR-2. The governing principle is: **code MUST follow a consistent style, and that style SHOULD be PSR-12 unless you have a documented reason to deviate.**

PHP-FIG describes PSR-12 as a *"set of commonalities that can be used across projects."* It does not dictate every possible formatting choice — you still have freedom in areas like array formatting, operator spacing, and variable naming conventions. But for the structural elements of PHP code, PSR-12 provides clear, unambiguous rules.

## The Base Coding Standard

PSR-12 inherits everything from PSR-1 (the basic coding standard) and PSR-12 itself. Before you can understand the extended rules, you need the foundation.

### PHP Tags

Files MUST use either the long `<?php` tag or the short-echo `<?=` tag. You MUST NOT use the short open tag `<?` (without `php`) or ASP-style tags `<%`:

```php
<?php
// Correct: long open tag for files containing PHP logic

<?= $variable ?>
// Correct: short-echo tag in templates

<? // Wrong: short open tag — disabled on many servers
```

This rule exists because short tags were historically optional and could be disabled in `php.ini`. Code using `<?` would work on one server and silently output raw PHP source on another. The long tag and short-echo tag are always available regardless of configuration.

### Character Encoding

Files MUST use UTF-8 without BOM (Byte Order Mark). The BOM is a three-byte sequence (`0xEF 0xBB 0xBF`) that some editors prepend to UTF-8 files. PHP does not strip it, so it becomes part of your output — invisible in most editors but capable of breaking HTTP headers and API responses.

### Line Endings

Line endings MUST be Unix-style `\n` (LF), not Windows-style `\r\n` (CRLF). Git can handle the conversion with `core.autocrlf`, but the canonical form in the repository must be LF.

```php
<?php
echo "Hello, world!\n"; // LF only — always correct
```

### Line Length

There is no hard limit on line length. The soft limit is 120 characters. A hard stop at 80 characters is recommended but not required. The important rule is: lines longer than 120 characters SHOULD be wrapped, and any wrapped line SHOULD be indented with one level of indentation (4 spaces):

```php
// Acceptable — under 120 characters
$posts = $this->postRepository->findByStatusAndCategory('published', 'php');

// Too long — wrap at 120 characters if possible
$posts = $this->postRepository->findByStatusAndCategoryWithPaginationAndCaching('published', 'php', 1, 20, 'created_at', 'DESC');

// Better — wrapped with indentation
$posts = $this->postRepository->findByStatusAndCategoryWithPaginationAndCaching(
    'published',
    'php',
    1,
    20,
    'created_at',
    'DESC'
);
```

### Indentation

Indentation MUST be 4 **spaces**. Tabs MUST NOT be used. This is non-negotiable in PSR-12: four spaces, period.

```php
// Correct: 4 spaces for indentation
if ($condition) {
    doSomething();
    doSomethingElse();
}

// Wrong: tabs or mixed spacing
if ($condition) {
	doSomething(); // This line uses a tab
        doSomethingElse(); // This line uses 8 spaces
}
```

The four-space rule is one of the most debated parts of PSR-12. Developers who prefer tabs point out that tabs let each developer choose their own display width. PSR-12's counter-argument is consistency: when the canonical source uses spaces, every developer sees the same indentation regardless of editor settings.

### Keywords and Types

PHP keywords MUST be lowercase. This includes `true`, `false`, `null`, and all control flow keywords:

```php
// Correct: lowercase
$isActive = true;
$isEmpty = false;
$result = null;

// Wrong: uppercase or mixed case
$isActive = TRUE;
$isEmpty = FALSE;
$result = NULL;
```

Type names — including primitive types used in type hints — MUST also be lowercase:

```php
// Correct
function sum(int $a, int $b): int
{
    return $a + $b;
}

// Wrong
function sum(INT $a, Int $b): INT
{
    return $a + $b;
}
```

PHP's type system is case-insensitive, so `INT` and `int` mean the same thing to the engine. But consistent casing matters for readability and for tools like static analyzers that may treat them differently.

## Namespace and Import Statements

Namespaces and imports follow a strict ordering convention. The structure must contain, in order:

1. A `<?php` declaration
2. An optional `declare(strict_types=1)` directive
3. A namespace declaration
4. One or more `use` import statements (optional)
5. A blank line before the file-level code

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\PaymentGatewayInterface;
use App\Exception\PaymentFailedException;
use Psr\Log\LoggerInterface;

class PaymentService
{
    // ...
}
```

Key rules for imports:

- Each `use` declaration goes on its own line
- There MUST be one blank line after the `namespace` block and after the `use` block
- Imports MUST be alphabetically sorted (though PSR-12 *suggests* this rather than requires it)
- There MUST NOT be a leading backslash on `use` statements
- `use const` and `use function` imports come after class imports, in the same alphabetical order

```php
use App\Service\PaymentService;
use App\Service\RefundService;
use function array_map;
use function count;
use const PHP_INT_MAX;
```

Groups like `use App\Service\{PaymentService, RefundService};` are not covered by PSR-12. They were originally prohibited by PSR-2, but PSR-12 leaves the decision to individual projects.

## Classes, Properties, and Methods

### Class Declaration

The opening brace for a class MUST go on the next line:

```php
// Correct: opening brace on its own line
class UserController
{
    // ...
}

// Wrong: opening brace on the same line as the class name
class UserController {
    // ...
}
```

The `extends` and `implements` keywords go on the same line as the class name. If the list of implemented interfaces is long, you can break it across multiple lines with indentation:

```php
class UserController extends AbstractController implements
    LoggerAwareInterface,
    EventDispatcherInterface,
    CacheableInterface
{
    // ...
}
```

When breaking interfaces across lines, the first interface goes on the same line as `implements`, and subsequent interfaces are indented with 4 spaces.

### Visibility

Every property and method MUST declare a visibility keyword (`public`, `protected`, or `private`). This is not optional:

```php
// Correct: explicit visibility on everything
class User
{
    public string $name;
    protected string $email;
    private string $passwordHash;

    public function getName(): string
    {
        return $this->name;
    }
}

// Wrong: missing visibility — defaults to public but is ambiguous
class User
{
    string $name; // What visibility did the author intend?

    function getName(): string // Missing `public`
    {
        return $this->name;
    }
}
```

The `var` keyword MUST NOT be used to declare properties:

```php
// Wrong: var is not allowed in PSR-12
class User
{
    var string $name;
}
```

### Method Declaration

Methods follow the same brace placement rule as classes: the opening brace goes on the next line. The method signature has a specific order for modifiers:

```
visibility + static + abstract/final
```

More concretely, the order is:
1. `final` or `abstract`
2. `public`, `protected`, or `private`
3. `static`

```php
// Correct: ordered by language specification
abstract protected static function getTableName(): string;

final public static function getInstance(): self
{
    // ...
}

// Wrong: disorganized modifier order
static public function doSomething(): void; // static before public
public final function doNotOverride(): void; // public before final
```

The opening parenthesis of a method has no space before it. There is no space after the opening parenthesis and no space before the closing parenthesis:

```php
// Correct
public function calculateTotal(array $items, float $taxRate): float
{
    // ...
}

// Wrong
public function calculateTotal ( array $items, float $taxRate ): float
{
    // ...
}
```

Each argument goes on its own line when the signature is too long or when using multi-line formatting:

```php
// Multi-line method signature — each argument indented, closing parenthesis on its own line
public function createOrder(
    Customer $customer,
    array $lineItems,
    float $shippingCost,
    ?string $couponCode = null
): Order {
    // ...
}
```

Note the opening brace is on the next line even after a multi-line signature.

### Return Type Declarations

Return types MUST be declared with a colon and a single space before the type:

```php
public function getTotal(): float
{
    return $this->total;
}
```

For nullable return types in PHP 7.1+, use `?Type` syntax:

```php
public function find(int $id): ?User
{
    return $this->repository->find($id);
}
```

For union types (PHP 8.0+), join them with `|` without spaces between the pipe and types:

```php
public function find(int $id): User|AnonymousUser|null
{
    // ...
}
```

### Abstract and Final

Abstract and final declarations follow the same visibility-first ordering. Abstract methods have no body — they end with a semicolon:

```php
abstract class Repository
{
    abstract protected function getTableName(): string;
}

final class UserRepository extends Repository
{
    protected function getTableName(): string
    {
        return 'users';
    }
}
```

## Control Structures

Control structure formatting is where PSR-12 diverges most noticeably from other languages like JavaScript or C#. PHP puts the opening brace on the **same line** for classes and methods but on the **next line** for control structures — wait, no. Let me correct that. PSR-12 puts the opening brace on the **next line** for classes and methods, and on the **same line** for control structures.

### If, Elseif, Else

There MUST be one space between `if` and the opening parenthesis. The opening brace goes on the same line as the `if` statement. The closing brace and `else`/`elseif` go on the same line:

```php
// Correct
if ($condition) {
    doSomething();
} elseif ($otherCondition) {
    doSomethingElse();
} else {
    fallback();
}

// Wrong: no space after if, brace on wrong line, else on its own line
if($condition){
    doSomething();
}
else {
    fallback();
}
```

Note PSR-12 requires `elseif` (one word), not `else if` (two words). The two-word form creates ambiguity about brace placement and is explicitly discouraged.

### Switch

The `switch` statement follows the same pattern: space before the parenthesis, brace on the same line. The `case` statements are indented one level, and the `break` inside each case is indented another level:

```php
switch ($status) {
    case 'active':
        doActivate();
        break;
    case 'pending':
        doPending();
        break;
    default:
        doDefault();
        break;
}
```

If execution falls through from one case to the next (no `break`), you MUST include a comment like `// no break` to indicate the intent:

```php
switch ($status) {
    case 'active':
        doActivate();
        // no break
    case 'pending':
        doPending();
        break;
}
```

### While and Do-While

`while` uses the same brace-on-same-line pattern:

```php
while ($condition) {
    doWork();
    updateCondition();
}
```

`do-while` puts the opening brace on the same line as `do`, and the `while` keyword goes on the same line as the closing brace:

```php
do {
    processItem();
    advancePointer();
} while ($condition);
```

### For and Foreach

`for` and `foreach` follow the same brace placement:

```php
for ($i = 0; $i < 10; $i++) {
    process($i);
}

foreach ($items as $key => $value) {
    process($key, $value);
}
```

`foreach` uses `=>` with one space on each side of the fat arrow.

### Try, Catch, Finally

The opening brace of `try` goes on the same line. `catch` and `finally` go on the same line as the closing brace of the preceding block:

```php
try {
    doRiskyWork();
} catch (ConnectionException $e) {
    logError($e);
    retry();
} catch (TimeoutException $e) {
    logError($e);
    abort();
} finally {
    cleanup();
}
```

When catching multiple exception types (PHP 7.1+), use `|` without spaces:

```php
try {
    doRiskyWork();
} catch (ConnectionException | TimeoutException $e) {
    handleTransientError($e);
}
```

## Closures

Closures (anonymous functions) follow the same brace placement as regular functions: opening brace on the next line. They have specific spacing rules:

```php
// Correct
$multiplier = function (int $a, int $b): int {
    return $a * $b;
};

// Wrong: space before parenthesis, brace on same line
$multiplier = function (int $a, int $b): int {
    return $a * $b;
};
```

The `function` keyword has one space after it. The opening parenthesis has no space before it.

The `use` keyword for capturing variables has one space on each side and one space between `use` and its opening parenthesis:

```php
$taxRate = 0.2;
$calculateTotal = function (float $subtotal) use ($taxRate): float {
    return $subtotal * (1 + $taxRate);
};
```

Arguments in the `use` list follow the same spacing rules as function arguments: no space after the opening parenthesis, commas with trailing spaces, no space before the closing parenthesis:

```php
$processor = function (
    Order $order,
    LoggerInterface $logger
) use (
    $taxRate,
    $discountCalculator
): void {
    // ...
};
```

When a closure is passed directly as an argument — common with `array_map`, `usort`, and similar functions — PSR-12 recommends keeping the closure on the same line if it fits within the line length limit:

```php
$squared = array_map(function (int $n): int {
    return $n * $n;
}, $numbers);
```

## Anonymous Classes

Anonymous classes follow the same rules as regular classes, plus the closure rules. The `class` keyword goes on the same line as `new`, and the opening brace goes on the next line:

```php
// Correct
$logger = new class implements LoggerInterface {
    public function log(string $message): void
    {
        file_put_contents('app.log', $message, FILE_APPEND);
    }
};

// Wrong: opening brace on same line as class keyword
$logger = new class implements LoggerInterface {
    public function log(string $message): void {
        file_put_contents('app.log', $message, FILE_APPEND);
    }
};
```

Anonymous classes can be passed as arguments or returned from methods, and the same nesting rules apply:

```php
$container->set('logger', new class('log.txt') extends AbstractLogger {
    public function __construct(
        private string $logFile,
    ) {
    }

    public function log(string $message): void
    {
        // ...
    }
});
```

## Automating PSR-12 With PHP-CS-Fixer

Manually applying PSR-12 rules across a codebase of any size is impractical. You will miss spaces, forget brace placement, and introduce inconsistencies. The solution is automation.

[PHP-CS-Fixer](https://cs.sensiolabs.org) is the standard tool for enforcing PHP coding standards. It parses your PHP files and rewrites them to match a defined rule set. Installation is straightforward with Composer:

```bash
composer require --dev friendsofphp/php-cs-fixer
```

### Configuration File

Create `.php-cs-fixer.dist.php` in your project root. The PSR-12 rule set is built in:

```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
    ->exclude('var')
    ->exclude('storage');

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder);
```

This tells PHP-CS-Fixer to scan all files in the project (excluding common third-party and cache directories) and apply every PSR-12 rule.

### Running the Fixer

To see what would change without modifying files:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

To actually fix files:

```bash
vendor/bin/php-cs-fixer fix
```

Both commands exit with a non-zero status code if any file needs fixing, making them suitable for CI pipelines.

### CI Integration

Add a job to your CI configuration that runs the fixer in dry-run mode. If it fails, the build fails and developers know their formatting doesn't match the standard.

For GitHub Actions:

```yaml
- name: Check PSR-12 compliance
  run: vendor/bin/php-cs-fixer fix --dry-run --diff
```

For GitLab CI:

```yaml
php-cs-fixer:
  script:
    - vendor/bin/php-cs-fixer fix --dry-run --diff
```

### Pre-commit Hook Integration

You can also run PHP-CS-Fixer before every commit using a Git pre-commit hook. Install the [pre-commit](https://pre-commit.com) framework, then add this to `.pre-commit-config.yaml`:

```yaml
repos:
  - repo: https://github.com/FriendsOfPHP/PHP-CS-Fixer
    rev: v3.8.0
    hooks:
      - id: php-cs-fixer
```

Now every commit automatically fixes formatting on staged files. No more style comments in code reviews.

## Beyond PSR-12: PER Coding Style

PHP does not stand still, and neither do its coding standards. In 2021, PHP-FIG deprecated the PSR designation for coding style recommendations and introduced **PER — PHP Evolving Recommendation**.

PER Coding Style (PER 2.0) is the direct successor to PSR-12. It includes everything in PSR-12 plus new rules for features introduced in PHP 8.0 and 8.1:

- **Named arguments** — formatting rules for named argument syntax
- **Match expressions** — brace placement and spacing for `match`
- **Readonly properties** — how to format `readonly` in property declarations
- **Constructor promotion** — rules for formatting promoted constructor parameters
- **Enums** — formatting for PHP 8.1 enum declarations

PHP-CS-Fixer already supports `@PER` and `@PER-CS` rule sets. If you're starting a new project today, use `@PER-CS2.0` instead of `@PSR12`:

```php
return $config
    ->setRules([
        '@PER-CS2.0' => true,
    ])
    ->setFinder($finder);
```

PER is a living standard — it will continue to evolve as PHP adds new language features. PSR-12 is frozen, meaning it will never cover `match`, enums, or any syntax not available in PHP 7.x.

## Why Consistent Coding Style Matters

You might wonder: does any of this actually matter? Does a missing space or a brace on the wrong line affect how your application runs? The answer is no. PHP does not care about whitespace. The compiled bytecode is identical regardless of your formatting choices.

But consistent style matters for the humans working on the code. Here is why:

**Code is read far more often than it is written.** You might write a line once, but dozens of developers will read it over the life of a project. Consistent formatting reduces cognitive load. When every file looks the same, you stop noticing formatting and start focusing on logic.

**Code reviews become faster and more productive.** Without a style standard, reviewers spend mental energy on formatting nitpicks: "add a space here," "remove that blank line," "brace goes on the next line." With PSR-12 and automated enforcement, those comments vanish. Reviewers focus entirely on correctness, performance, and architecture.

**Onboarding new team members is smoother.** A developer joining your team does not need to learn a custom style guide. If you follow PSR-12, they already know the rules. They can read any file in the codebase and find the brace without thinking about it.

**Tooling works better.** Static analyzers, linters, auto-formatters, and code generators all produce more consistent output when the target style is standardized. If your codebase follows a custom, undocumented style, every new tool requires configuration. PSR-12 is supported out of the box by PHP-CS-Fixer, PHP_CodeSniffer, PhpStorm, VS Code, and every major PHP IDE.

**Diff noise disappears.** When a developer reformats code to match their personal style while also making logic changes, the pull request becomes unreadable. Lines change because of formatting, not because of behavior. A committed style standard enforced by automation eliminates this problem entirely. Every diff shows only what actually changed.

## Summary

PSR-12 is the established coding style standard for modern PHP. It replaces PSR-2 with updated rules for PHP 7.x and 8.x features. The standard covers PHP tags, line endings, indentation (4 spaces, no tabs), keyword casing, namespace ordering, class and method structure, control flow brace placement, closures, and anonymous classes.

Automation is essential. PHP-CS-Fixer applies PSR-12 rules across your entire codebase with a single command. Integrate it into your CI pipeline and pre-commit hooks to catch formatting violations automatically, before they reach code review.

For new projects, consider adopting PER Coding Style (PER-CS 2.0), the living successor to PSR-12 that covers PHP 8.0+ features like named arguments, match expressions, enums, and readonly properties.

Consistent coding style is not about personal preference — it is about team velocity. When formatting is automated and standardized, every developer on the team reads code the same way. Code reviews focus on substance. Diffs show only functional changes. And your codebase remains maintainable for years to come.

PSR-12 support is built into PHP-CS-Fixer, PHP_CodeSniffer, and every major IDE. Whether you use Laravel, Symfony, WordPress, or plain PHP, PSR-12 gives you a proven, battle-tested framework for writing code that any PHP developer can read and understand immediately. Start using it today — your future self (and your teammates) will thank you.
