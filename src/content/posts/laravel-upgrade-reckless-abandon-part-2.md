---
title: "Laravel Upgrade with Reckless Abandon Part 2: Execution"
description: "Execute a reckless Laravel 5.6 to 9 upgrade. Fresh app creation, code migration, test fixes, factory updates, route syntax changes, and deployment of the upgrade."
pubDate: "2023-02-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Laravel Upgrade", "Laravel 9", "PHP 8.2", "Test Suite", "Framework Migration", "Laravel Reckless", "Upgrade Guide", "Laravel Migration"]
selected: false
---

Last month, we covered the preparation for upgrading an ancient Laravel application from version 5.6 to 9. This month, we actually do it. No careful staging. No incremental commits. No upgrade guides. We are going full reckless abandon.

What gives us the audacity to skip five major upgrade guides? Tests. A comprehensive test suite describes how the application should behave. When tests fail after a change, we know exactly where the problem is. This confidence is what empowers the Dragon Drop method.

This upgrade process is not for the faint of heart. It is not for those who meticulously curate their commits. It is for teams who trust their test suite and want to get the upgrade done in hours, not weeks.

## What You'll Learn

- The Dragon Drop upgrade method: creating a fresh app and migrating code
- Handling model namespace changes from `app/` to `app/Models`
- Updating factories from legacy syntax to modern class-based factories
- Converting old route declarations to class-based route syntax
- Configuring Laravel Scout, databases, and queues for the new version
- Managing composer dependencies during a major version bump
- Dealing with data migrations in database migrations

## The Dragon Drop Method

The Dragon Drop method (say "drag and drop" fast) is brutal but effective. Create a fresh Laravel 9 application. Drop your old code on top. Let the test suite tell you what is broken.

```bash
# Create the fresh application
laravel new app

cd app/

# Install required packages
composer require laravel/ui
php artisan ui bootstrap --auth
composer require laravel/scout
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
composer require league/csv
composer require doctrine/dbal
composer require laravel/browser-kit-testing --dev
npm install && npm run dev
```

This gives us a pristine Laravel 9 installation with all the packages we need. The application compiles. The welcome page loads. Now the real work begins.

## Configuring the New Application

Before copying any code, configure the new application's environment. The `config` folder is the heart of binding environment variables to application configuration.

```php
<?php

// config/scout.php - Add MySQL driver configuration
'mysql' => [
    'mode' => 'NATURAL_LANGUAGE',
    'model_directories' => [app_path('Models')],
    'min_search_length' => 0,
    'min_fulltext_search_length' => 4,
    'min_fulltext_search_fallback' => 'LIKE',
    'query_expansion' => false,
],
```

Update `.env` with the `SCOUT_DRIVER=database` setting. Configure `config/app.php`, `config/mail.php`, and `config/services.php` with settings from the old application. Review every config file for customizations.

## Test Configuration and Setup

Remove the default test files that came with the fresh application:

```bash
rm tests/Feature/ExampleTest.php
rm tests/Unit/ExampleTest.php
```

Copy your existing tests from the old application. Our test suite covers donors, gifts, and referrals. Each test verifies that records can be created and updated, plus domain-specific requirements.

Since we removed the `tests/Unit` folder, remove the corresponding entry from `phpunit.xml`:

```xml
<!-- Remove this from phpunit.xml -->
<testsuite name="Unit">
    <directory suffix="Test.php">./tests/Unit</directory>
</testsuite>
```

Now run the tests:

```bash
./vendor/bin/phpunit
```

Expect failure. The first error: `Call to undefined function factory()`.

## Fixing Factory Usage

The legacy application uses the global `factory()` helper. Laravel 9 uses class-based factories.

Old code:
```php
$user = factory(App\User::class)->create();
```

New code:
```php
$user = \App\Models\User::factory()->create();
```

Use your editor's find-and-replace functionality across the entire project. This is where the "reckless" name pays off — you are making sweeping changes and relying on the test suite to catch any missed spots.

### Updating Database Seeders

Old DatabaseSeeder:
```php
public function run()
{
    App\User::create([
        'name' => 'admin',
        'email' => 'admin@admin.com',
        'password' => Hash::make('admin'),
    ]);

    factory(App\User::class, 5)->create();
    factory(App\Referral::class, 15)->create();
    factory(App\Gift::class, 35)->create();
}
```

New DatabaseSeeder:
```php
public function run(): void
{
    \App\Models\User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@admin.com',
        'password' => Hash::make('admin'),
    ]);

    \App\Models\User::factory(5)->create();
    \App\Models\Referral::factory(15)->create();
    \App\Models\Gift::factory(35)->create();
}
```

## Model Namespace Changes

The legacy application stores models in `app/` with the `App` namespace. Laravel 9 uses `app/Models/` with the `App\Models` namespace.

Move each model file:
```bash
mv app/User.php app/Models/User.php
mv app/Donor.php app/Models/Donor.php
mv app/Gift.php app/Models/Gift.php
mv app/Referral.php app/Models/Referral.php
```

Update each model's namespace from `namespace App;` to `namespace App\Models;`. Add the `HasFactory` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    // ... rest of the model
}
```

Update every reference to the old namespace throughout the application. Search for `App\User` and replace with `App\Models\User`. Do the same for all other models.

## Converting Factories

Legacy factories use the `$factory->define()` syntax. Modern factories are classes extending `Illuminate\Database\Eloquent\Factories\Factory`.

Use `php artisan make:factory` to create the new factory files:

```bash
php artisan make:factory GiftFactory --model=Gift
php artisan make:factory ReferralFactory --model=Referral
php artisan make:factory DonorFactory --model=Donor
```

Old factory syntax:
```php
$factory->define(App\Gift::class, function (Faker $faker) {
    $referrals = \App\Referral::all();

    return [
        'description' => $faker->sentence,
        'amount' => $faker->randomFloat(2, 0, 10000),
        'referral_id' => $referrals->random()->id,
    ];
});
```

New factory syntax:
```php
<?php

namespace Database\Factories;

use App\Models\Gift;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;

class GiftFactory extends Factory
{
    protected $model = Gift::class;

    public function definition(): array
    {
        $referrals = Referral::all();

        return [
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 0, 10000),
            'referral_id' => $referrals->random()->id,
        ];
    }
}
```

## Migrations

Copy your application's migration files from the old database folder to the new one. Be careful not to overwrite Laravel's built-in migrations (the 2014 and 2019 timestamped files).

```bash
# Copy only your application migrations
cp ../old-app/database/migrations/2020_* database/migrations/
cp ../old-app/database/migrations/2021_* database/migrations/
cp ../old-app/database/migrations/2022_* database/migrations/
```

### Data Migrations

Our application has a data migration that moves image metadata from the Referral model to a new VerificationImage table. Data migrations during database migrations can be risky, but for small datasets (a few thousand rows), the risk is acceptable.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $referrals = \App\Models\Referral::all();

        foreach ($referrals as $referral) {
            if (empty($referral->verification)) {
                continue;
            }

            $input = [
                'referral_id' => $referral->id,
                'filename' => $referral->verification,
            ];

            if (file_exists($referral->verification)) {
                $input['hash'] = sha1_file($referral->verification);
            }

            \App\Models\VerificationImage::create($input);
        }

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn('verification');
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->string('verification')->nullable();
        });
    }
};
```

Run the migrations:
```bash
php artisan migrate
php artisan db:seed
```

## Routes

Legacy Laravel used string-based syntax for route definitions. Laravel 9 uses class-based syntax.

Old syntax:
```php
Route::get('/', ['as' => 'home', 'uses' => 'HomeController@index']);
Route::get('/referrals', ['as' => 'referrals.index', 'uses' => 'ReferralController@index']);
Route::post('/referrals', ['as' => 'referrals.create', 'uses' => 'ReferralController@create']);
```

New syntax:
```php
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReferralController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
Route::post('/referrals', [ReferralController::class, 'create'])->name('referrals.create');
```

This applies to all routes. Update named routes, resource routes, and grouped routes.

## Controllers and Form Requests

Copy the controller files and Form Request classes from the old application. Update namespace references for models `App\User` becomes `App\Models\User`.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Models\Gift;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $referrals = Referral::with('gifts')->paginate(20);

        return view('referrals.index', compact('referrals'));
    }

    public function create(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:referrals,email',
        ]);

        $referral = Referral::create($validated);

        return redirect()
            ->route('referrals.index')
            ->with('success', "Referral {$referral->name} created.");
    }
}
```

After copying all controllers, run `composer dump-autoload` to regenerate the classmap.

## Views and Frontend

Copy Blade templates from the old application's `resources/views` folder. If your layouts and partials have not changed names, they should work without modification.

Frontend asset compilation is where many upgrades stall. The old application uses Laravel Mix (Webpack). Laravel 9 uses Vite by default. You have two options:

1. Migrate to Vite (recommended for new projects)
2. Keep Laravel Mix running (pragmatic for this upgrade path)

For a reckless upgrade, we keep the old frontend configuration working and migrate to Vite later. The tests pass, the application works, and we can iterate on the frontend separately.

```bash
# Keep Laravel Mix for now
npm install laravel-mix --save-dev
npm run production
```

## Running Tests

Now the moment of truth:

```bash
./vendor/bin/phpunit

PHPUnit 9.5.26 by Sebastian Bergmann and contributors.

....... 7 / 7 (100%)

Time: 1.07 seconds, Memory: 22.00MB

OK (7 tests, 28 assertions)
```

All seven tests pass. Twenty-eight assertions confirm the application behaves correctly. We have successfully upgraded from Laravel 5.6 to 9, from PHP 7.1 to 8.2.

## Committing the Changes

Version control purists, look away:

```bash
git checkout -b reckless-abandon-upgrade
rm -rf .
git commit -am "Scorched Earth - removed files"
cp -r ../app .
git add .
git commit -am "Laravel 9 upgrade with Dragon Drop method"
git push origin reckless-abandon-upgrade
```

The resulting pull request is massive. Hundreds of files changed. Thousands of lines added and removed. The commit history shows a clean slate — a full replacement of the old application with the new one.

But the test suite passes. The application is deployed. Users notice no difference except improved performance from PHP 8.2.

## Real-World Use Cases

### Legacy Application Modernization

A Laravel 5.1 application built in 2015 powers a growing SaaS platform. The team cannot upgrade to PHP 8 because Laravel 5.1 does not support it. Security patches for PHP 7.4 are ending. The Dragon Drop method moves the application to Laravel 9 in two days. The team can now deploy PHP 8.2 features, use modern Laravel packages, and attract developers who want to work with current technology.

### Merger of Multiple Codebases

Two companies merge. Each has a Laravel application built on different versions. The Dragon Drop method upgrades both to Laravel 9, then the teams merge the codebases with a unified test suite. The test suite validates that both sets of functionality work correctly after the merge.

## Best Practices

### Have a Complete Test Suite

The reckless abandon method only works if your test suite covers the critical paths. Spend time writing tests before attempting the upgrade. Every untested feature is a risk.

### Keep Changes Reversible

Use version control. Create a branch. If the upgrade fails, you can discard the branch and try again. The reckless method is not the irreversible method.

### Run Tests Frequently

After each batch of changes, run the tests. The first failure tells you exactly what to fix next. Do not wait until all changes are made to run tests — you will lose the connection between the change and the failure.

### Document Trade-offs

The reckless method leaves technical debt. Old frontend configurations. Deprecated method calls that still work. Keep a list of these trade-offs so the team can address them later.

## Common Mistakes to Avoid

**Without a test suite.** Do not attempt this method without tests. You will not know what broke, and you will spend weeks debugging instead of hours.

**Ignoring dependency compatibility.** Verify that every Composer package supports the target Laravel version. A single incompatible package can break the entire upgrade.

**Forgetting `composer dump-autoload`.** After copying files into the new application, run `composer dump-autoload`. Otherwise, classes might not be found.

**Modifying Laravel core files.** Do not edit files in the `vendor` directory. If you need customization, use service providers or config files.

## Frequently Asked Questions

### Is the Dragon Drop method safe for production applications?

It is safe when backed by a comprehensive test suite and deployed with a rollback plan. Create a database backup before running migrations. Test the upgraded application in a staging environment before production.

### How long does a Laravel 5.6 to 9 upgrade take?

With the Dragon Drop method, a skilled developer can complete the upgrade in one to two days. The time depends on test coverage, code complexity, and the number of custom packages.

### What if my application uses deprecated features?

The test suite will catch runtime errors. Replace deprecated methods with their modern equivalents. Laravel's upgrade guides provide detailed migration paths for deprecated features.

### Should I upgrade Laravel version incrementally?

The reckless method jumps directly to the latest version. Incremental upgrades (5.6 to 6, 6 to 7, etc.) are safer but take much longer. Choose based on your risk tolerance and test coverage.

### Can I revert to the old version if something goes wrong?

Yes. The git branch preserves the old codebase. You can revert by checking out the old branch and restoring the database from backup.

## Conclusion

The reckless abandon upgrade method is not for every team or every application. It requires confidence in your test suite, tolerance for an ugly git history, and willingness to address issues as they appear.

But when it works, it works remarkably well. The application jumps from Laravel 5.6 to 9, PHP 7.1 to 8.2, and gains access to modern features, better performance, and continued security support. The upgrade that would take weeks of careful planning and incremental changes is completed in days.

The moral of the story: tests empower recklessness. Without them, you are gambling. With them, you are making a calculated bet.

**Ready to upgrade your Laravel application?** Start by writing tests for every critical feature. Then create a fresh Laravel 9 application, copy your code, and let the test suite guide your fixes. Your application will thank you.
