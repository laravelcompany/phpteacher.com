---
title: "Pest PHP Testing Framework - Modern Testing for Laravel and PHP"
description: "Learn Pest PHP, the elegant testing framework for PHP. From installation to advanced features like higher-order tests, arch presets, and parallel testing."
pubDate: "2022-05-18 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Pest", "PHP", "Testing", "PHPUnit", "Laravel", "TDD", "Automated Testing"]
selected: false
---

Testing is the safety net that lets you ship with confidence. But let's be honest — traditional PHPUnit tests can feel verbose, rigid, and frankly, a bit boring. Enter Pest PHP, the testing framework that treats developer experience as a first-class citizen. If you haven't tried it yet, you're about to see why the PHP community is buzzing.

## What is Pest PHP?

Pest PHP is a modern testing framework built on top of PHPUnit. It doesn't replace PHPUnit — it wraps it with a cleaner, more expressive syntax that makes tests easier to read and faster to write. Think of it as the difference between writing vanilla JavaScript and using a framework like Vue or React. The underlying engine is the same, but the developer experience is worlds apart.

Pest was created by Nuno Maduro and has quickly become one of the most exciting projects in the PHP ecosystem. It takes inspiration from Jest (JavaScript's beloved testing framework) and brings that same elegance to PHP. The result is a testing framework that feels natural, intuitive, and dare I say, enjoyable to use.

## Why Pest? The Problem with Traditional PHPUnit

PHPUnit is powerful and battle-tested. It's been the standard for PHP testing for over a decade. But it has pain points:

**Verbose boilerplate.** Every test class needs a class declaration, method visibility, docblocks, and oftentimes setUp methods. You write more scaffolding than actual test logic.

**Scattered assertions.** PHPUnit forces you to think in terms of class hierarchies and method names rather than behaviors and expectations.

**Mediocre defaults.** Output formatting isn't great out of the box, and features like parallel testing require third-party packages.

Pest addresses all of this. It gives you:

- Clean, standalone test functions instead of classes
- Expressive `expect()` API inspired by Jest
- Beautiful, color-coded output by default
- First-class parallel testing support
- Built-in architecture testing
- A fluent, chainable assertion syntax

## Installation

Getting started with Pest is straightforward. You can install it in any PHP project using Composer.

For a **new project**, create a directory and require Pest:

```bash
mkdir pest-demo && cd pest-demo
composer init --name="demo/pest-demo" --type="project" --require="php:^8.1" -n
composer require pestphp/pest --dev
```

This installs Pest along with PHPUnit under the hood. You'll get a `phpunit.xml` configuration file and a `tests` directory scaffolded automatically.

For an **existing project**, just run:

```bash
composer require pestphp/pest --dev
./vendor/bin/pest --init
```

The `--init` flag sets up Pest in your project, creating the `tests/Pest.php` file where you define global configurations and custom helpers.

You can verify everything works with:

```bash
./vendor/bin/pest
```

If you see the green "Tests: 0 skipped, 0 passed" message, you're ready to go.

## Writing Your First Test

Pest tests are just functions. No classes, no method visibility modifiers, no boilerplate.

Create a file called `tests/Unit/ExampleTest.php`:

```php
<?php

test('the application returns a successful response', function () {
    $response = true;

    expect($response)->toBeTrue();
});
```

You can also use the `it()` alias for a more natural reading flow:

```php
<?php

it('returns true', function () {
    expect(true)->toBeTrue();
});
```

Run your tests:

```bash
./vendor/bin/pest
```

You'll see a clean, colorized output with a green checkmark. Each test name reads like a sentence, so your terminal output doubles as living documentation.

Pest also supports test descriptions as strings, which means you skip the `testSomething` camelCase convention entirely. No more `test_it_returns_the_correct_total_when_calculating_the_sum_of_two_numbers`. Just write what you mean:

```php
test('calculate total returns the sum of two numbers', function () {
    // ...
});
```

## Pest vs PHPUnit: A Side-by-Side Comparison

Let's look at the same test written in both frameworks.

**PHPUnit:**

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MathTest extends TestCase
{
    public function test_addition()
    {
        $result = 1 + 1;
        $this->assertEquals(2, $result);
    }

    public function test_subtraction()
    {
        $result = 5 - 3;
        $this->assertEquals(2, $result);
    }
}
```

**Pest:**

```php
<?php

test('addition', function () {
    $result = 1 + 1;
    expect($result)->toEqual(2);
});

test('subtraction', function () {
    $result = 5 - 3;
    expect($result)->toEqual(2);
});
```

The Pest version ditches the class, the namespace boilerplate, the method visibility, and the `$this->` prefix on assertions. Every line serves a purpose.

Here's a quick reference map of common assertions:

| PHPUnit | Pest |
|---|---|
| `$this->assertEquals($expected, $actual)` | `expect($actual)->toEqual($expected)` |
| `$this->assertTrue($condition)` | `expect($condition)->toBeTrue()` |
| `$this->assertNull($value)` | `expect($value)->toBeNull()` |
| `$this->assertCount($n, $array)` | `expect($array)->toHaveCount($n)` |
| `$this->assertContains($value, $array)` | `expect($array)->toContain($value)` |
| `$this->assertInstanceOf($class, $obj)` | `expect($obj)->toBeInstanceOf($class)` |
| `$this->expectException($exception)` | `expect(fn() => ...)->toThrow($exception)` |
| `$this->assertGreaterThan($n, $value)` | `expect($value)->toBeGreaterThan($n)` |

The `expect()` API chains beautifully too:

```php
expect($response)
    ->toBeInstanceOf(Response::class)
    ->status->toBe(200)
    ->json->toHaveKey('data');
```

Each assertion returns the same expectation instance, so you can chain as many checks as you need. This eliminates the repetitive `$this->` prefix and makes your intentions crystal clear.

## Higher-Order Tests

This is where Pest truly shines. Higher-order tests let you describe test scenarios without writing callback functions. You chain assertions directly off the `test()` or `it()` return value using `->assert`.

Consider a typical Laravel controller test. Instead of writing:

```php
test('guests are redirected to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});
```

You can write:

```php
test('guests are redirected to login')
    ->get('/dashboard')
    ->assertRedirect('/login');
```

No callback function. No curly braces. The test reads exactly like the sequence of operations it performs.

Higher-order tests work because Pest leverages PHP 8's named arguments and reflection to wire everything together. You can chain arbitrary methods on the test result, and Pest treats them as operations on the object returned by the previous call.

Here's a more complex example:

```php
test('authenticated user can view their profile')
    ->actingAs($user)
    ->get('/profile')
    ->assertOk()
    ->assertSee($user->name)
    ->assertSee($user->email);
```

The flow is linear and obvious. You act as a user, you hit a route, you assert the response. No indirection, no confusion.

You can even use `tap()` to inspect values mid-chain:

```php
test('the dashboard loads with correct data')
    ->actingAs($user)
    ->get('/dashboard')
    ->assertOk()
    ->tap(fn ($response) => expect($response['posts'])->toHaveCount(10));
```

These higher-order tests are a game-changer for feature and integration tests. They make the testing experience feel like writing a specification, not debugging a program.

## Custom Helpers and Expectations

Pest lets you extend the `expect()` function with your own custom assertions. This is invaluable when you find yourself repeating the same assertion logic across multiple tests.

Define custom expectations in your `tests/Pest.php` file:

```php
<?php

use Pest\Expectation;

expect()->extend('toBeValidEmail', function () {
    return $this->toMatch('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/');
});

expect()->extend('toBeBetween', function (int $min, int $max) {
    return $this->toBeGreaterThanOrEqual($min)
        ->toBeLessThanOrEqual($max);
});
```

Now use them in your tests:

```php
test('email validation', function () {
    expect('john@example.com')->toBeValidEmail();
    expect(25)->toBeBetween(18, 99);
});
```

You can also define global helper functions in `tests/Pest.php`:

```php
<?php

function createUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function actingAsUser(): TestCase
{
    $user = createUser();
    return test()->actingAs($user);
}
```

These helpers are automatically available in all your test files without imports. This keeps your tests clean and your setup logic centralized.

For more advanced use cases, you can create custom expectation classes that implement `Pest\Contracts\HasExpectations`. But for 90% of projects, the `extend()` method is all you need.

## Architecture Testing

Architecture testing is one of Pest's killer features. It lets you enforce coding standards, naming conventions, and structural rules programmatically. Think of it as automated code review that runs as part of your test suite.

Pest ships with several built-in architectural assertions through the `arch()` function:

```php
test('strict types are enforced')
    ->arch()
    ->expect('App')
    ->toUseStrictTypes();

test('controllers extend base controller')
    ->arch()
    ->expect('App\Http\Controllers')
    ->toExtend(Illuminate\Routing\Controller::class);

test('services are final')
    ->arch()
    ->expect('App\Services')
    ->toBeFinal();

test('global facades are not used')
    ->arch()
    ->expect('App')
    ->not->toUse('Illuminate\Support\Facades');
```

These tests prevent architectural drift. When a new developer joins the team and accidentally uses a facade instead of dependency injection, the architecture test catches it before the PR is merged.

You can combine multiple expectations:

```php
test('application architecture')
    ->arch()
    ->expect('App')
    ->toUseStrictTypes()
    ->toBeFinal()
    ->not->toUse(['die', 'var_dump', 'dd']);
```

The `not` property inverts any assertion, so `not->toUse('Facades')` means "must not use facades." This declarative style makes your architectural rules self-documenting.

Pest also includes **arch presets** for common PHP frameworks. For Laravel:

```php
arch()
    ->preset()
    ->laravel();
```

This single line enforces dozens of Laravel best practices: controllers extend the base controller, models use proper traits, service providers are registered correctly, and so on. You can customize any preset or build your own.

Architecture tests run alongside your regular tests. They're fast because Pest caches the reflection results. There's no excuse not to have them.

## Parallel Testing

As your test suite grows, execution time becomes a bottleneck. Pest solves this with built-in parallel testing powered by the `brianium/paratest` library under the hood.

Run your tests in parallel with a single flag:

```bash
./vendor/bin/pest --parallel
```

Pest automatically detects the number of CPU cores available and spawns that many worker processes. Each process runs a subset of your tests, cutting execution time dramatically.

You can control the process count manually:

```bash
./vendor/bin/pest --parallel --processes=8
```

Parallel testing works with PHPUnit's built-in database migrations and transactions, as long as each process uses its own database connection. Pest integrates with Laravel's `RefreshDatabase` trait to handle this automatically.

The output from parallel runs is collected and displayed coherently — no garbled interleaving. Each test's result is attributed correctly, and failures include the process number for debugging.

For CI pipelines, parallel testing is a godsend. A suite that takes 10 minutes sequentially can drop to under a minute with 16 parallel processes. That's the difference between blocking a deploy and letting it sail through.

## Testing Laravel Applications with Pest

Pest has first-class support for Laravel through the `pestphp/pest-plugin-laravel` package. If you're starting a new Laravel project, use the official Laravel installer with the Pest flag:

```bash
laravel new my-app --pest
```

For existing Laravel projects:

```bash
composer require pestphp/pest-plugin-laravel --dev
php artisan pest:install
```

The plugin provides Laravel-specific test helpers and integrates seamlessly with Laravel's testing utilities. Here's what a typical feature test looks like:

```php
<?php

use App\Models\User;
use App\Models\Post;

uses(Tests\TestCase::class)->in('Feature');

test('authenticated user can create a post', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post('/posts', [
            'title' => 'My First Post',
            'body' => 'This is the body of my post.',
        ]);

    $response->assertRedirect('/posts');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('posts', [
        'title' => 'My First Post',
        'user_id' => $user->id,
    ]);
});
```

The `uses()` function at the top tells Pest to use the `Tests\TestCase` class for all tests in the `Feature` directory. This gives you access to Laravel's `$this->get()`, `$this->post()`, `$this->actingAs()`, and all the other HTTP testing methods.

You can also use the `refreshDatabase` trait easily:

```php
<?php

uses(Tests\TestCase::class)
    ->in('Feature')
    ->beforeEach(fn () => $this->refreshDatabase());

test('users can register', function () {
    $response = $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    expect(User::count())->toBe(1);
});
```

The `beforeEach()` and `afterEach()` hooks let you set up and tear down state per test, just like PHPUnit's `setUp()` and `tearDown()` methods. You can chain them globally for whole test directories or use them inline for specific test files.

Pest's Laravel integration also supports **model factories**, **assertDatabaseHas**, **HTTP sessions**, **mail faking**, **notification faking**, and every other testing utility that Laravel provides. Nothing is lost; everything is enhanced.

## TDD Workflow with Pest

Test-driven development (TDD) follows the red-green-refactor cycle: write a failing test, make it pass, then clean up the code. Pest makes this cycle faster and more enjoyable.

Here's a typical TDD session with Pest:

**Step 1 — Write a failing test (Red):**

```php
<?php

test('calculate total price with tax', function () {
    $calculator = new PriceCalculator;
    $total = $calculator->calculate(100.00);

    expect($total)->toEqual(121.00);
});
```

Run Pest:

```bash
./vendor/bin/pest
```

You get a red failure: `Class "PriceCalculator" not found.` Perfect — we haven't written the class yet.

**Step 2 — Write the minimum code to pass (Green):**

```php
<?php

class PriceCalculator
{
    public function calculate(float $price): float
    {
        return $price * 1.21;
    }
}
```

Run Pest again. Green. The test passes.

**Step 3 — Refactor:**

Now you can clean up the implementation — extract the tax rate to a constant, add type hints, or inject the tax rate as a dependency. Run Pest after each change to make sure you haven't broken anything.

The tight feedback loop is what makes TDD powerful. Pest's fast startup time (it doesn't need to boot a full framework for unit tests) keeps the loop under a second. You write, test, and iterate without losing focus.

Combine this with Pest's **watch mode**:

```bash
./vendor/bin/pest --watch
```

Pest watches your test and source files for changes and re-runs automatically. This is the ultimate TDD companion — you edit a file, save it, and see results in your terminal instantly without switching contexts.

## IDE Support and Community

Pest has excellent IDE support. The `pestphp/pest` package ships with PhpStorm metadata that enables autocompletion for `expect()`, `test()`, `it()`, `beforeEach()`, and all other Pest globals. No extra plugins required.

For VS Code users, the **Pest Test Explorer** extension provides a visual test runner with pass/fail indicators, code lenses to run individual tests, and debugging support through the VS Code PHP debug adapter.

The Pest community is vibrant and growing. The framework has over 8,000 stars on GitHub, an active Discord server, and regular releases. The ecosystem includes:

- **pestphp/pest-plugin-faker** — Generate fake data in tests
- **pestphp/pest-plugin-mock** — Mock objects without Mockery
- **pestphp/pest-plugin-livewire** — Test Livewire components
- **pestphp/pest-plugin-inertia** — Test Inertia responses

Third-party packages are joining the ecosystem too. Spatie's `ray` debugger has Pest support. Database testing tools like `lazylegacy/factory-generator` generate Pest-compatible factories. The community is all-in.

## Conclusion

Pest PHP is more than a testing framework — it's a statement about what PHP development can be. Clean, expressive, and joyful. It doesn't compromise on power; underneath the elegant syntax lies the full might of PHPUnit. Every assertion, every mock, every data provider you know from PHPUnit still works. Pest just removes the friction.

If you're starting a new Laravel project, there's no reason not to use Pest. If you're maintaining an existing PHPUnit suite, you can adopt Pest incrementally — mixed test suites run just fine. Install Pest, write your next test in it, and see how it feels. I suspect you won't go back.

Pest proves that developer experience matters at every level of the stack. Testing shouldn't feel like a chore. It should feel like a safety net that you're excited to use. That's what Pest delivers.

Now go write some tests — and enjoy it.
