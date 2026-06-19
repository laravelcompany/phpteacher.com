---
title: "Laravel Upgrade Guide — Jumping Versions Without Fear"
description: "Learn how to upgrade Laravel from 5.6 to 9 across multiple major versions. Real-world guide covering PHP 8.1, testing strategies, and key breaking changes at each step."
pubDate: "2023-01-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Laravel", "PHP", "Upgrade", "Migration", "Testing", "PHP 8.1", "Legacy Code"]
selected: false
---

Legacy Laravel applications accumulate technical debt. You inherit a project running Laravel 5.6 on PHP 7.1, and the feature requests keep coming. The framework is three major versions behind, and every new package you install complains about outdated dependencies.

The common instinct is to rewrite everything from scratch. That is almost always the wrong move. A rewrite throws away years of bug fixes, business logic, and real-world testing. What you need instead is a disciplined upgrade path — one that moves through each major version methodically, guided by a solid test suite.

This article walks through a real project that went from Laravel 5.6 on PHP 7.1 to Laravel 9 on PHP 8.1. The application was a referral management system built for a nonprofit organization during GiveCamp Memphis, a charity hackathon where developers donate their time to build software for community organizations. The techniques used here apply to any Laravel application facing a multi-version upgrade gap.

## What You'll Learn

- How to safely upgrade Laravel across multiple major versions
- Why a test suite is non-negotiable before attempting version jumps
- Key breaking changes between Laravel 5.6 and 9
- How to handle PHP version upgrades alongside framework upgrades
- Strategies for dealing with deprecated packages like Laravel Collective
- Real-world patterns for testing legacy applications before and after upgrades

## The Project Context

The referral management system handled a single critical workflow: nonprofit staff members filled out a large HTML form to refer clients to partner services. The application managed CRUD operations for referrals, clients, and service providers. It supported file uploads for documentation and generated reports for grant compliance.

The original stack was straightforward:

- **Laravel 5.6** on PHP 7.1
- **Laravel Collective HTML** for form building
- **Laravel Scout** with a custom MySQL driver for search
- **League CSV** for report generation
- **BrowserKit testing** for integration tests
- **Laravel Homestead** for local development

Seven tests with twenty-eight assertions covered the core functionality. That was the safety net.

### Why Tests Matter Most

Before touching a single dependency version, the project had working tests. This is the most important prerequisite for any major upgrade. Without tests, you are flying blind. You make changes, refresh the browser, and hope nothing broke. With tests, you have immediate feedback on whether your upgrade steps broke existing behavior.

Twenty-eight assertions across seven tests is not comprehensive coverage. But it was enough to validate the critical paths: creating a referral, uploading a document, searching records, and generating a CSV export. Every upgrade step started and ended with a green test suite.

## Version-by-Version Upgrade Path

The upgrade skipped zero versions. Each Laravel major version has an upgrade guide published on Laravel.com, and each one was applied sequentially. Jumping directly from 5.6 to 9 would have required resolving every breaking change simultaneously — a recipe for disaster.

### Laravel 5.6 to 6

Laravel 6 was the first version to adopt semantic versioning. The shift from 5.x to 6.x was relatively smooth, but several changes required attention.

**Helper package extraction.** Many helper functions like `array_get`, `array_only`, and `str_contains` moved from the Laravel framework into a separate `laravel/helpers` package. If your codebase relied on these, you needed to either install the new package or replace calls with native PHP equivalents.

```php
// Before — Laravel 5.6
$value = array_get($array, 'key', 'default');

// Option A: Install laravel/helpers
composer require laravel/helpers

// Option B: Native PHP 7+
$value = $array['key'] ?? 'default';
```

**Guzzle HTTP client upgrade.** Laravel 6 bumped the underlying Guzzle dependency. If your application made HTTP requests using Laravel's HTTP facade or directly through Guzzle, you needed to verify compatibility.

**Collision package.** Testing output received a significant upgrade with the Collision package, providing more readable error output during testing.

```bash
composer require laravel/framework:^6.0
```

### Laravel 6 to 7

Laravel 7 introduced several new features and deprecations that affected the upgrade path.

**Laravel Sanctum.** While primarily known as an API token authentication system, Sanctum replaced older approaches to SPA authentication. If you were not using any authentication package, this change was transparent.

**Blade component improvements.** Laravel 7 introduced a new Blade component syntax. The old `@component('alert')` pattern still worked but was superseded by the class-based component approach.

```php
// Old syntax — still worked
@component('components.alert', ['type' => 'danger'])
    Something went wrong.
@endcomponent

// New syntax — Laravel 7+
<x-alert type="danger">
    Something went wrong.
</x-alert>
```

**HTTP Client.** Laravel 7 introduced a fluent HTTP client built on top of Guzzle. This was backward-compatible but worth adopting for cleaner external API calls.

```bash
composer require laravel/framework:^7.0
```

### Laravel 7 to 8

Laravel 8 introduced several significant changes that required careful migration.

**Jetstream.** Laravel 8 shipped with Jetstream as the new application scaffolding system, replacing Laravel UI and the earlier authentication scaffolding. For existing applications, this did not break anything unless you were starting a new project.

**Dynamic Blade components.** Laravel 8 allowed dynamic component rendering, enabling computed component names at runtime.

**Model factory overhaul.** This was one of the biggest breaking changes for test suites. Laravel 8 moved from the classic `Faker\Generator`-based factories to class-based factories. The `factory()` helper function was deprecated.

```php
// Before — Laravel 7 style
$user = factory(User::class)->create();

// After — Laravel 8+ style
$user = User::factory()->create();
```

The legacy factory syntax was preserved behind the `laravel/legacy-factories` package, but migrating to class-based factories was strongly recommended.

```bash
composer require laravel/framework:^8.0
```

### Laravel 8 to 9

Laravel 9 was the most significant upgrade in this path, with changes driven by underlying dependency updates.

**Symfony 6.** Laravel 9 upgraded from Symfony 5 to Symfony 6. This meant breaking changes in several areas, particularly around the request and response lifecycle. Custom implementations of Symfony interfaces needed updating.

**PHP 8.1 requirement.** Laravel 9 required PHP 8.1 or higher. This was the biggest jump from PHP 7.1. Many PHP 7.1 features like scalar type hints were already in use, but PHP 8.1 introduced enum support, readonly properties, and fiber-based concurrency.

**Flysystem 3.** Laravel 9 upgraded from Flysystem 2 to Flysystem 3, which changed the filesystem abstraction layer. If your application used file uploads — and this referral system did — you needed to update adapter configurations.

```php
// Laravel 9 filesystem configuration
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
        'throw' => false,
    ],
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

**PHP return types.** Laravel 9 added PHP 8.1 return types throughout the framework codebase. While this did not break application code directly, it set a new standard that affected custom implementations of framework interfaces.

```bash
composer require laravel/framework:^9.0
composer require php:^8.1
```

## Handling Deprecated Packages

### Laravel Collective

The referral system used Laravel Collective HTML extensively. The form builder and HTML helpers were deeply embedded in Blade templates. Laravel Collective was archived and no longer received updates for newer Laravel versions.

The migration path involved replacing Collective helpers with native Blade and HTML:

```php
// Before — Laravel Collective
{!! Form::open(['url' => 'referrals', 'files' => true]) !!}
    {!! Form::label('client_name', 'Client Name') !!}
    {!! Form::text('client_name') !!}
    
    {!! Form::label('document', 'Upload Document') !!}
    {!! Form::file('document') !!}
    
    {!! Form::submit('Create Referral') !!}
{!! Form::close() !!}

// After — Native Blade
<form action="/referrals" method="POST" enctype="multipart/form-data">
    @csrf
    <label for="client_name">Client Name</label>
    <input type="text" name="client_name" id="client_name">
    
    <label for="document">Upload Document</label>
    <input type="file" name="document" id="document">
    
    <button type="submit">Create Referral</button>
</form>
```

Extracting the form into Blade components made the transition cleaner and improved maintainability.

### Laravel Scout with Custom MySQL Driver

The application used Laravel Scout for search functionality with a custom MySQL driver that provided full-text search through raw database queries. Scout itself was maintained and compatible with Laravel 9, but the custom driver needed updating.

The key was checking the Scout driver interface for any signature changes and adjusting the MySQL implementation to match.

### BrowserKit Testing

BrowserKit testing was the legacy approach for integration tests in Laravel. It simulated browser requests without JavaScript. Laravel moved away from BrowserKit in favor of Laravel Dusk for browser testing and the built-in HTTP test methods.

```php
// Before — BrowserKit test
class ReferralTest extends TestCase
{
    use DatabaseMigrations;

    public function test_user_can_create_referral()
    {
        $user = factory(User::class)->create();
        
        $this->actingAs($user)
            ->visit('/referrals/create')
            ->type('Jane Doe', 'client_name')
            ->type('jane@example.com', 'client_email')
            ->press('Create Referral')
            ->seePageIs('/referrals')
            ->see('Jane Doe')
            ->seeInDatabase('referrals', [
                'client_name' => 'Jane Doe',
                'user_id' => $user->id,
            ]);
    }
}

// After — Laravel HTTP test
class ReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_referral(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->post('/referrals', [
                'client_name' => 'Jane Doe',
                'client_email' => 'jane@example.com',
            ]);
        
        $response->assertRedirect('/referrals');
        $this->assertDatabaseHas('referrals', [
            'client_name' => 'Jane Doe',
            'user_id' => $user->id,
        ]);
    }
}
```

The seven original tests were rewritten to use Laravel's modern HTTP testing methods. The assertion count remained at twenty-eight, but the tests were now compatible with every supported Laravel version.

## Real-World Use Cases

**Nonprofit organization software.** Small nonprofits often run on donated or budget-limited software. Upgrading keeps their data secure without requiring a full rebuild. The GiveCamp model proves that incremental upgrades can be delivered in weekends, not months.

**SaaS platforms with long-lived customers.** Many SaaS products have customers running on older PHP versions due to their own compliance requirements. An incremental upgrade strategy allows supporting legacy customers while modernizing the core codebase.

**Internal enterprise applications.** Large organizations accumulate Line of Business applications over decades. Most run on versions that are multiple major releases behind. The sequential upgrade path demonstrated here applies directly to enterprise legacy modernization.

**E-commerce systems.** WooCommerce, Magento, and custom e-commerce platforms are notoriously difficult to upgrade due to complex business rules. A test-guided upgrade approach minimizes downtime and transaction risks.

**Marketplaces and referral systems.** Any platform that manages referrals, commissions, or partner networks benefits from staying current. Security patches and compliance requirements make version stagnation a liability.

## Best Practices

**Maintain a comprehensive test suite before upgrading.** Without tests, you cannot distinguish between upgrade-induced breakage and pre-existing bugs. Aim for critical-path coverage even if total line coverage is low.

**Upgrade one major version at a time.** Every Laravel upgrade guide addresses specific breaking changes. Combining two versions of breaking changes doubles the debugging surface. Sequential upgrades are slower but safer.

**Use a dedicated upgrade branch.** Create a branch specifically for the upgrade. Do not mix feature work with version upgrades. Keep the branch isolated until all tests pass.

**Pin dependency versions during upgrades.** Run `composer update` with specific constraints. Letting Composer resolve the latest of everything while upgrading Laravel versions introduces unpredictable dependency interactions.

```bash
composer require "laravel/framework:^8.0" --no-update
composer update --with-all-dependencies
```

**Run tests after every major version bump.** Do not batch multiple Laravel versions before running tests. If tests fail after the 6-to-7 upgrade, you know exactly where the problem is.

**Document every breaking change you encounter.** Create a project-specific upgrade diary. When you repeat the upgrade on another project — and you will — your notes save hours of re-debugging.

**Update PHP before or alongside the framework.** Laravel 9 requires PHP 8.1. Trying to run Laravel 9 on PHP 7.1 is impossible. Plan the PHP version upgrade as part of the Laravel version sequence.

## Common Mistakes

**Skipping versions.** Jumping from Laravel 5.6 to 9 directly means resolving every breaking change simultaneously. The surface area for bugs explodes, and you lose the ability to pinpoint which version introduced a regression.

**Ignoring the test suite first.** Upgrading without tests is guesswork disguised as work. If the existing test suite does not pass before the upgrade, it cannot validate the upgrade.

**Blindly trusting shift tools.** Laravel Shift and automated upgrade tools are helpful but not perfect. They handle the mechanical parts of an upgrade but cannot understand your business logic. Always verify automated changes manually.

**Forgetting about third-party packages.** Before upgrading Laravel, check every third-party package for compatibility with the target version. Abandoned packages like Laravel Collective require replacement planning.

**Neglecting the PHP version constraint.** Laravel 8 and below support older PHP versions, but running Laravel on an unsupported PHP version defeats the purpose of upgrading. Match the PHP version to the framework requirements.

**Upgrading during a feature freeze violation.** If the business needs a new feature next week, do not start a major version upgrade. Wait until the feature ships, then upgrade. Splitting attention between features and upgrades causes both to suffer.

## Frequently Asked Questions

**How long does a multi-version Laravel upgrade take?** For a small to medium application with reasonable test coverage, expect one to two days per major version. A four-version jump like 5.6 to 9 typically takes one to two weeks of part-time work.

**Can I upgrade directly from Laravel 5.6 to 9?** Technically you can attempt it, but it is not recommended. The breaking changes between 5.6 and 9 are extensive enough that debugging becomes exponentially harder with each skipped version.

**Do I need to rewrite all my Blade templates?** No. Blade templates are largely backward-compatible. The main change is around component syntax, which is additive. However, if you used Laravel Collective HTML helpers extensively, those templates need manual conversion.

**Will my database migrations break?** Database migrations are generally stable across Laravel versions. Schema builder methods rarely change. The risk is in seeders and factories, which changed significantly with the class-based factory system in Laravel 8.

**Should I upgrade PHP at the same time?** Yes, but do it as part of the version sequence. Upgrade PHP to match each Laravel version's minimum requirement. For example, Laravel 8 supports PHP 7.3 through 8.1, but Laravel 9 requires PHP 8.1.

**What if my application uses Laravel Collective?** You need to replace Laravel Collective calls with native HTML or Blade components. Collective was archived and does not support Laravel 8 or 9. Start this replacement early in the upgrade process.

**How do I handle custom packages I built for the application?** Custom internal packages need the same upgrade treatment as the main application. Update their Laravel and PHP dependencies, run their tests, and address breaking changes one version at a time.

**Is Laravel Homestead still the best local development environment?** Homestead works well for Laravel 9, but many developers have moved to Laravel Sail (Docker-based) for better portability and CI consistency. Consider switching during the upgrade.

**What about Laravel Vapor deployment?** If you deploy on Vapor, check that your target Laravel version is supported. Vapor typically supports the latest Laravel version within days of release.

**Can I roll back if the upgrade breaks something?** Yes, if you use version control properly. Tag the pre-upgrade state, create a dedicated upgrade branch, and keep the old branch deployable until the upgrade is fully tested and verified.

## Conclusion

Upgrading Laravel across multiple major versions is not a rewrite. It is a disciplined, sequential process that preserves the business value of your existing application while modernizing its foundation. The referral management system described here went from Laravel 5.6 on PHP 7.1 to Laravel 9 on PHP 8.1 in about two weeks of focused effort, guided by seven tests and twenty-eight assertions.

The test suite provided the confidence to jump. The sequential upgrade path provided the control to land safely. The result was an application that could receive security patches, support modern packages, and continue serving its nonprofit mission without disruption.

Your legacy Laravel application can follow the same path. Start by writing the tests. Then take it one version at a time. The framework has evolved significantly, but the upgrade guides are thorough, and the community has documented every edge case you will encounter.

If you are planning a Laravel upgrade, start with your test suite today. Run it. Make it pass. Then open the upgrade guide for your current version and take the first step.
