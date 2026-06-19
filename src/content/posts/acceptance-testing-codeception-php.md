---
title: "Acceptance Testing With Codeception - Get Started With PHP Testing"
description: "Learn acceptance testing with Codeception for PHP applications. From installation to writing your first test, make your application more robust with automated browser testing."
pubDate: "2022-04-16 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Codeception", "Testing", "PHP", "Acceptance Testing", "BDD", "PHPUnit", "Automation", "QA"]
selected: false
---

Your application works on your machine. The unit tests pass. The database migrations run cleanly. You deploy to production feeling confident. Then the first bug report rolls in — a form doesn't submit, a JavaScript feature breaks, or a page returns a 500 error that none of your low-level tests caught.

This scenario plays out constantly in PHP development. Unit tests verify individual methods. Integration tests check that components can talk to each other. But neither tells you whether a real user can sign up, log in, and complete a purchase from end to end. That gap is what acceptance testing fills.

[Codeception](https://codeception.com) is the dominant acceptance testing framework in the PHP ecosystem. It wraps PHPUnit's assertion engine with a higher-level API designed to simulate real user behavior. You write tests that click buttons, fill forms, navigate pages, and verify you see the right content — all from PHP code that runs against a live browser or an HTTP emulator.

By the end of this guide, you'll know how to install Codeception, structure your test suites, write your first acceptance test, apply maintainable patterns like PageObject, and integrate everything into a CI pipeline.

## Why Acceptance Testing Matters

Unit tests ask: does this method return the right value when I pass specific arguments? Functional tests ask: does this controller return a 200 response with the expected JSON structure? Both are essential, but neither answers the question your users care about: **does the application work when I use it like a human would?**

Acceptance tests bridge this gap. They interact with your application the same way a user does — through the browser interface. A well-written acceptance test can:

- Open a registration page
- Fill in the name, email, and password fields
- Submit the form
- Verify the user sees a welcome message
- Confirm a confirmation email was sent
- Log out and log back in with the new credentials

This single test exercises your routing, controllers, validation logic, database interactions, mailer configuration, authentication middleware, session handling, and view rendering — all in one coherent flow. If any piece of that chain breaks, the acceptance test catches it before your users do.

The return on investment is significant. A single acceptance test can replace dozens of unit tests that only verify components in isolation. That doesn't mean you should abandon unit tests. You need both layers. But without acceptance tests, you're flying blind at the integration boundaries where most real-world bugs live.

## Codeception vs PHPUnit

If you've used PHPUnit, you already know most of Codeception's assertion vocabulary. Codeception is built on top of PHPUnit and delegates its assertion engine to it. Every `$this->assert*()` call you know from PHPUnit works inside a Codeception test.

The difference is scope. PHPUnit is a unit testing framework. Codeception is a full-stack testing framework that includes PHPUnit under the hood but adds:

- **Test suites** — organize tests by type (acceptance, functional, unit)
- **Modules** — pluggable backends for database, browser emulation, mail testing, and more
- **Helpers** — custom modules for application-specific logic
- **PageObject and StepObject** — design patterns that keep tests maintainable
- **Built-in reporters** — HTML, XML, console output with color-coded results
- **Parallel execution** — run tests across multiple processes

You can think of Codeception as PHPUnit with superpowers. If you already know PHPUnit, you're 80% of the way to knowing Codeception. The remaining 20% is learning Codeception's test organization conventions and its module system.

## Installation and Bootstrap

Codeception installs via Composer like any modern PHP package. Create a new project or navigate to an existing one, then require the package:

```bash
composer require --dev codeception/codeception
```

Once the package downloads, bootstrap Codeception in your project root:

```bash
vendor/bin/codecept bootstrap
```

This command generates the default directory structure:

```
tests/
  acceptance.suite.yml
  acceptance/
  functional.suite.yml
  functional/
  unit.suite.yml
  unit/
  _support/
    AcceptanceTester.php
    FunctionalTester.php
    UnitTester.php
    _generated/
codeception.yml
```

The `codeception.yml` file at the root holds global configuration. Each suite has its own `.suite.yml` file that configures modules specific to that test type. The `_support` directory holds tester classes and custom helpers. Codeception auto-generates helper classes in `_generated/` — you never edit those files directly.

## Understanding the Test Suites

Codeception ships with three default suites. Each serves a distinct purpose:

### Unit Suite

The unit suite is plain PHPUnit under Codeception's harness. Tests in `tests/unit/` follow the same conventions you already know: class instantiation, method calls, and assertions against return values. Unit tests run fast and don't touch the database, filesystem, or network by default.

```php
<?php
// tests/unit/UserServiceTest.php
class UserServiceTest extends \Codeception\Test\Unit
{
    public function testCreateUserReturnsId()
    {
        $service = new UserService(new InMemoryUserRepository());
        $id = $service->createUser('alice@example.com', 'password');
        
        $this->assertIsString($id);
        $this->assertNotEmpty($id);
    }
}
```

### Functional Suite

Functional tests exercise your application's full stack — routing, controllers, middleware, database — but without a real web server. Codeception's functional module makes HTTP requests internally, processes the response through your framework's kernel, and lets you assert on the output.

Functional tests are faster than acceptance tests because they skip browser rendering. They're ideal for testing form submissions, authentication flows, and API endpoints where you don't need JavaScript execution.

### Acceptance Suite

Acceptance tests run against a live application — either through a real browser (via WebDriver) or through an HTTP emulator (via PhpBrowser). These tests answer the question: does everything work together?

We'll spend the rest of this guide focused on the acceptance suite, since that's where Codeception provides the most value over plain PHPUnit.

## Configuring the Acceptance Suite

Open `tests/acceptance.suite.yml`. This is where you choose your testing backend. Two options are available:

### PhpBrowser

PhpBrowser uses Symfony's BrowserKit and the Goutte web scraping library. It makes real HTTP requests and parses the HTML response. It cannot execute JavaScript. For applications that don't rely heavily on JavaScript, PhpBrowser is fast and requires no additional infrastructure:

```yaml
# tests/acceptance.suite.yml
actor: AcceptanceTester
modules:
    enabled:
        - PhpBrowser:
            url: http://localhost:8080
        - \Helper\Acceptance
```

### WebDriver

WebDriver controls a real browser (Chrome, Firefox, or others) through the W3C WebDriver protocol. It executes JavaScript, handles AJAX requests, and renders CSS. This is the closest simulation of a real user, but it requires a running Selenium server or a headless Chrome instance:

```yaml
# tests/acceptance.suite.yml
actor: AcceptanceTester
modules:
    enabled:
        - WebDriver:
            url: http://localhost:8080
            browser: chrome
            host: localhost
            port: 9515
            capabilities:
                goog:chromeOptions:
                    args:
                        - "--headless"
                        - "--disable-gpu"
                        - "--no-sandbox"
        - \Helper\Acceptance
```

The `headless` flag runs Chrome without a visible window, which is essential for CI environments. The `no-sandbox` flag avoids permission issues inside Docker containers.

## Writing Your First Acceptance Test

Generate a new acceptance test using Codeception's built-in generator:

```bash
vendor/bin/codecept generate:cept acceptance Welcome
```

This creates `tests/acceptance/WelcomeCest.php`. The `Cest` suffix indicates a test file organized around a scenario.

A Cest test has a simple structure. Each public method ending in `Cest` is treated as a test. The first parameter is the `AcceptanceTester` object — your primary interface for interacting with the application:

```php
<?php
// tests/acceptance/WelcomeCest.php
class WelcomeCest
{
    public function homePageLoads(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Welcome');
        $I->seeElement('nav');
        $I->seeResponseCodeIs(200);
    }
}
```

Run it:

```bash
vendor/bin/codecept run acceptance WelcomeCest
```

Codeception outputs a color-coded result. A green checkmark means the test passed. A red `F` means failure, with details about what went wrong and where.

### A Realistic Example: User Registration

Let's write a test that walks through a complete user registration flow:

```php
<?php
// tests/acceptance/RegistrationCest.php
class RegistrationCest
{
    public function registerNewUser(AcceptanceTester $I)
    {
        $I->amOnPage('/register');
        $I->see('Create Account');
        
        $I->fillField('name', 'Alice Johnson');
        $I->fillField('email', 'alice@example.com');
        $I->fillField('password', 'securePassword123');
        $I->fillField('password_confirmation', 'securePassword123');
        $I->checkOption('#terms');
        $I->click('Sign Up');
        
        $I->seeCurrentUrlEquals('/dashboard');
        $I->see('Welcome, Alice Johnson');
        $I->see('Your account has been created');
        
        $I->seeElement('.user-menu');
        $I->dontSee('Register');
    }
}
```

Read this test aloud: it describes exactly what a user does. The code is declarative, not imperative. You don't write DOM traversal logic or HTTP plumbing. You say what you want to happen, and Codeception translates that into browser actions.

## Selectors and Assertions

Codeception provides multiple ways to locate elements on a page. Understanding the priority order helps you write tests that are both robust and readable:

### CSS Selectors

CSS selectors are the default. When you call `$I->click('.submit-btn')`, Codeception uses the CSS class `submit-btn` to find the element. CSS selectors are fast and well-understood:

```php
$I->click('#submit-button');
$I->seeElement('form.registration');
$I->see('Success', '.alert-success');
```

### XPath

For complex queries that CSS can't express, use XPath:

```php
$I->click('//form[@id="registration"]//button[contains(text(), "Submit")]');
```

XPath is more powerful but less readable. Prefer CSS selectors for common cases and save XPath for edge cases like finding elements by their text content or traversing the DOM in non-standard ways.

### Named Selectors

Codeception also supports named selectors for form fields. You can reference fields by name, label, or placeholder:

```php
$I->fillField('email', 'alice@example.com');
$I->fillField('input[name="email"]', 'alice@example.com');
$I->fillField('#email', 'alice@example.com');
```

All three lines produce the same result. The first form — referencing by the field's name attribute — is usually the most readable.

### Key Assertions

Here are the assertions you'll use most often:

| Method | What it checks |
|--------|----------------|
| `$I->see('text')` | Text exists anywhere on the page |
| `$I->dontSee('text')` | Text does not exist on the page |
| `$I->seeElement('.selector')` | A CSS element exists in the DOM |
| `$I->dontSeeElement('.selector')` | A CSS element does not exist in the DOM |
| `$I->seeInCurrentUrl('/path')` | The URL contains a substring |
| `$I->seeCurrentUrlEquals('/path')` | The URL matches exactly |
| `$I->seeResponseCodeIs(200)` | The HTTP response code matches |
| `$I->seeLink('Click Here')` | A link with the given text exists |
| `$I->seeCheckboxIsChecked('#terms')` | A checkbox is checked |
| `$I->seeInField('email', 'alice@example.com')` | A field contains a specific value |

Memorize this table. These twelve methods will cover 90% of your acceptance test assertions.

## The PageObject Pattern

Acceptance tests grow fast. A registration test that fills five fields, clicks two buttons, and checks three assertions is readable. Now imagine maintaining fifty tests that each hard-code selectors for the same login form. When the CSS class on the email field changes, you have to update every test. That's fragile and error-prone.

The PageObject pattern solves this by encapsulating page-specific selectors and behavior into dedicated classes. Each page in your application gets a corresponding class that exposes meaningful methods instead of raw selectors.

Generate a PageObject:

```bash
vendor/bin/codecept generate:pageobject Login
```

This creates `tests/_support/Page/Login.php`:

```php
<?php
// tests/_support/Page/Login.php
namespace Page;

class Login
{
    public static string $URL = '/login';
    
    public static string $usernameField = 'email';
    public static string $passwordField = 'password';
    public static string $submitButton = 'Log In';
    public static string $errorMessage = '.alert-danger';

    public static function route(string $param): string
    {
        return static::$URL;
    }
}
```

Now your test references selectors through the PageObject instead of hard-coding them:

```php
<?php
// tests/acceptance/LoginCest.php
use Page\Login;

class LoginCest
{
    public function loginWithValidCredentials(AcceptanceTester $I)
    {
        $I->amOnPage(Login::$URL);
        $I->fillField(Login::$usernameField, 'alice@example.com');
        $I->fillField(Login::$passwordField, 'securePassword123');
        $I->click(Login::$submitButton);
        
        $I->seeCurrentUrlEquals('/dashboard');
    }
    
    public function loginWithInvalidPasswordShowsError(AcceptanceTester $I)
    {
        $I->amOnPage(Login::$URL);
        $I->fillField(Login::$usernameField, 'alice@example.com');
        $I->fillField(Login::$passwordField, 'wrongPassword');
        $I->click(Login::$submitButton);
        
        $I->see('Invalid credentials', Login::$errorMessage);
        $I->seeCurrentUrlEquals('/login');
    }
}
```

When the login form changes, you update one file — `Page/Login.php` — and all tests automatically reflect the change. This is the same principle that makes CSS custom properties more maintainable than hard-coded color values. Centralize what changes.

## StepObject Pattern

Where PageObject groups selectors, StepObject groups actions. If multiple tests perform the same multi-step workflow — for example, creating a new project in a project management app — extract that workflow into a StepObject:

```bash
vendor/bin/codecept generate:stepobject ProjectCreator
```

```php
<?php
// tests/_support/Step/Acceptance/ProjectCreator.php
namespace Step\Acceptance;

use Page\ProjectCreate;

class ProjectCreator extends \AcceptanceTester
{
    public function createProject(string $name, string $description): void
    {
        $I = $this;
        
        $I->amOnPage(ProjectCreate::$URL);
        $I->fillField(ProjectCreate::$nameField, $name);
        $I->fillField(ProjectCreate::$descriptionField, $description);
        $I->click(ProjectCreate::$submitButton);
        $I->see('Project created successfully');
    }
}
```

Your test calls `$I->createProject('My Project', 'A description')` — a single line that encapsulates an entire workflow. Tests become declarations of intent rather than sequences of low-level instructions.

## Configuration for Different Environments

Your acceptance tests need different configurations depending on where they run. Locally, you might use PhpBrowser at `http://localhost:8080`. In CI, you need WebDriver with headless Chrome at `http://web:4444/wd/hub`.

Codeception supports environment-specific configuration through the `modules:config` block and environment overrides:

```yaml
# codeception.yml
actor: Tester
paths:
    tests: tests
    log: tests/_output
    data: tests/_data
    support: tests/_support
    envs: tests/_envs
settings:
    bootstrap: _bootstrap.php
    colors: true
    memory_limit: 1024M
```

Create environment-specific files in `tests/_envs/`:

```yaml
# tests/_envs/local.yml
modules:
    config:
        PhpBrowser:
            url: http://localhost:8080
```

```yaml
# tests/_envs/ci.yml
modules:
    config:
        WebDriver:
            url: http://localhost
            browser: chrome
            host: chrome
            port: 4444
```

Run tests against a specific environment:

```bash
vendor/bin/codecept run --env ci
```

This pattern lets you switch between PhpBrowser (fast, no JS) and WebDriver (full browser) without modifying your test code.

## Database Fixtures and Cleanup

Acceptance tests that touch the database need consistent starting data and clean teardown. Codeception provides the Db module for this:

```yaml
# tests/acceptance.suite.yml
actor: AcceptanceTester
modules:
    enabled:
        - WebDriver:
            url: http://localhost
            browser: chrome
        - Db:
            dsn: 'mysql:host=localhost;dbname=testdb'
            user: 'root'
            password: ''
            dump: tests/_data/dump.sql
            populate: true
            cleanup: true
```

The `Db` module loads the SQL dump before each test (or before each suite, depending on configuration) and cleans up afterward. Every test starts with a known database state. No test can pollute the data for another test.

For Laravel applications, use the `Laravel` module instead, which provides database transactions and model factories:

```yaml
modules:
    enabled:
        - Laravel:
            environment_file: .env.testing
            part: ORM
        - WebDriver:
            url: http://localhost
```

## CI Integration

Acceptance tests require a running application and a browser. CI pipelines must orchestrate these services. Here's how to set up both GitHub Actions and GitLab CI.

### GitHub Actions

```yaml
# .github/workflows/acceptance.yml
name: Acceptance Tests
on: [push, pull_request]

jobs:
    acceptance:
        runs-on: ubuntu-latest
        
        services:
            mysql:
                image: mysql:8.0
                env:
                    MYSQL_ALLOW_EMPTY_PASSWORD: yes
                    MYSQL_DATABASE: testdb
                ports:
                    - 3306:3306
            
            chrome:
                image: selenium/standalone-chrome:latest
                ports:
                    - 4444:4444
        
        steps:
            - uses: actions/checkout@v3
            
            - uses: shivammathur/setup-php@v2
              with:
                  php-version: 8.1
                  extensions: mbstring, pdo_mysql, curl
                  
            - run: composer install --no-progress
            
            - run: cp .env.testing .env
            
            - run: php artisan serve --port=8000 &
            
            - run: vendor/bin/codecept build
            
            - run: vendor/bin/codecept run acceptance --env ci
```

The `services` block spins up MySQL and Selenium Chrome containers alongside your test runner. The PHP built-in server serves your application on port 8000. Codeception connects to both the application and the browser, runs your acceptance tests, and reports results.

### GitLab CI

```yaml
# .gitlab-ci.yml
stages:
    - test

acceptance:
    stage: test
    image: php:8.1-cli
    
    services:
        - mysql:8.0
        - selenium/standalone-chrome:latest
    
    before_script:
        - docker-php-ext-install mbstring pdo_mysql
        - curl -sS https://getcomposer.org/installer | php
        - php composer.phar install
        - cp .env.testing .env
        - php artisan serve --port=8000 &
        - vendor/bin/codecept build
    
    script:
        - vendor/bin/codecept run acceptance --env ci
```

The structure mirrors GitHub Actions. Docker services provide the database and browser. Your application boots in the background. Codeception runs the tests.

## Common Pitfalls and How to Avoid Them

### Flaky Tests

A test that passes sometimes and fails other times is worse than a test that always fails. Flaky tests destroy confidence in your suite. Common causes and fixes:

**Timing issues.** JavaScript-rendered content isn't immediately available. Use `waitForElement` and `waitForText` instead of `see` and `seeElement` when testing dynamic content:

```php
$I->waitForElement('.user-menu', 5); // wait up to 5 seconds
$I->waitForText('Welcome', 5);
```

**Test ordering dependencies.** Tests that leave state behind — like a logged-in session or a created database record — break subsequent tests. Each test must work in isolation. Use the Db module's cleanup feature or wrap each test in a database transaction.

**Hard-coded URLs.** Never use absolute URLs like `http://localhost:8080` in your test code. Always use relative paths with `amOnPage()` and configure the base URL in the suite configuration. CI environments use different hostnames and ports.

### Slow Suites

Acceptance tests are slow by nature. A full browser test takes seconds per scenario. Multiply that by hundreds of tests and your suite takes hours.

**Parallel execution.** Codeception supports parallel test execution with the `--process` flag, but it requires the `codeception/module-queue` package. Alternatively, split tests across multiple CI jobs:

```bash
vendor/bin/codecept run acceptance --env ci --group critical --no-exit
vendor/bin/codecept run acceptance --env ci --group auth --no-exit
vendor/bin/codecept run acceptance --env ci --group billing --no-exit
```

**Use PhpBrowser when possible.** If a page doesn't need JavaScript, test it with PhpBrowser instead of WebDriver. PhpBrowser tests are an order of magnitude faster. Split your acceptance suite into JS-required and JS-not-required groups:

```yaml
# tests/acceptance.suite.yml
groups:
    requires-js: [tests/acceptance/*JsCest.php]
    no-js: [tests/acceptance/*NoJsCest.php]
```

### Hard-to-Maintain Selectors

CSS classes change during refactoring. When a class changes, any test that references it breaks. Mitigate this by:

1. **Using PageObjects** for all selectors (discussed above)
2. **Adding test-specific attributes** to critical elements:

```html
<button data-test="submit-registration">Sign Up</button>
```

```php
$I->click('[data-test="submit-registration"]');
```

Test attributes never change during design refactors because they're not styling hooks. They're part of your testing contract.

### Ignoring Console Errors

WebDriver can capture browser console errors — JavaScript exceptions, failed resource loads, CSP violations. Enable this in your suite configuration:

```yaml
modules:
    config:
        WebDriver:
            capabilities:
                loggingPrefs:
                    browser: ALL
```

Then assert no console errors in your teardown:

```php
public function _after(AcceptanceTester $I): void
{
    $logs = $I->executeJS('return window.__consoleErrors || []');
    
    if (!empty($logs)) {
        throw new \RuntimeException(
            'Browser console errors: ' . implode(', ', $logs)
        );
    }
}
```

This catches JavaScript errors that would otherwise slip past your assertions.

## Running Tests During Development

You don't need CI to run acceptance tests. During development, use the PHP built-in server and PhpBrowser for fast feedback:

```bash
php -S localhost:8080 -t public &
vendor/bin/codecept run acceptance
```

When you need to debug a failing test, Codeception's `--debug` flag provides detailed output:

```bash
vendor/bin/codecept run acceptance --debug
```

The HTML reporter generates a visual report you can open in a browser:

```bash
vendor/bin/codecept run acceptance --html
```

Open `tests/_output/report.html` to see a formatted report with pass/fail status for each test.

## Conclusion

Acceptance testing transforms how you think about quality in PHP applications. Instead of verifying that individual methods return the correct values, you verify that real user workflows produce the correct outcomes. The shift in perspective is subtle but powerful.

Codeception gives you the tools to write these tests without fighting the framework. Its PHPUnit foundation means you already know the assertion syntax. Its module system handles the complexity of browser automation, database management, and cross-environment configuration. Its PageObject and StepObject patterns keep your tests maintainable as your application grows.

Start small. Configure Codeception in an existing project. Write one acceptance test for your most critical user flow — login, registration, or checkout. Run it locally. Add it to your CI pipeline. Once you see how much confidence a single acceptance test provides, you'll want to cover every major workflow in your application.

The alternative is what you're doing now: deploying changes and hoping nothing breaks. Acceptance testing replaces hope with evidence. And in production, evidence is the only thing that matters.
