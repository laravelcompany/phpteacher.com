---
title: "Continuous Code - Automated Testing, CI, and Delivery for PHP"
description: "Implement continuous integration and delivery for PHP applications. From automated testing to deployment pipelines, build a robust CI/CD workflow for your PHP projects."
pubDate: "2022-05-14 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "CI/CD", "Continuous Integration", "GitHub Actions", "Testing", "DevOps", "Automation"]
selected: false
---

You just pushed to `main`. Your tests pass locally. You've reviewed the diff twice. You hit deploy, grab coffee, and wait. Ten minutes later, a notification lights up your phone — the build failed. A test you didn't run locally breaks in CI because the database version differs. The migration you wrote works on SQLite but throws a syntax error on MySQL. The static analysis tool you never bothered to configure catches an undefined variable in the code you merged last week.

That gap between "it works on my machine" and "it works in production" is where CI/CD lives. Continuous integration and continuous delivery are not DevOps buzzwords to slap on a job description. They are the disciplined practice of automating everything between writing code and shipping it to users. For PHP projects, this discipline matters more than most developers admit.

This guide walks you through setting up a complete CI/CD pipeline for PHP applications — from automated testing on every push to production deployment — using the tools and practices that work in 2022.

## What Continuous Code Actually Means

Continuous code is the umbrella concept that covers three connected practices: continuous integration, continuous delivery, and continuous deployment. They form a progression.

**Continuous integration** means every developer merges their changes into the shared main branch frequently — multiple times per day. Every merge triggers an automated build that runs the test suite, checks code quality, and reports failures immediately. When CI fails, the team stops everything to fix it. No exceptions.

**Continuous delivery** extends CI by ensuring your codebase is always in a deployable state. The automated pipeline packages your application, runs acceptance tests against a staging environment, and produces a release artifact that can go to production with a single manual approval.

**Continuous deployment** removes the manual gate. Every change that passes the full pipeline goes straight to production without human intervention.

Most PHP teams start with CI and work their way toward delivery. That is the right approach. Get the feedback loop tight before you automate the deployment.

## Why PHP Projects Need CI

PHP has a reputation for being too dynamic to test effectively. That reputation is outdated. PHP 8.1 ships with typed properties, readonly properties, enums, union types, intersection types, and first-class callable syntax. The language has evolved past the "glue code for HTML templates" era. Your pipeline needs to evolve with it.

The arguments for CI in PHP projects are the same as for any other language. You want to catch regressions before they reach production. You want to enforce coding standards without manual code review. You want to know immediately when a dependency update breaks your application. But PHP introduces specific challenges that make CI particularly valuable:

**Platform diversity.** Your production server runs PHP 8.1 on Ubuntu with OPcache enabled. Your local machine runs PHP 8.0 on macOS. Your teammate develops on Windows with Xdebug loaded. These differences surface bugs that only appear under specific configurations. CI lets you test across all of them without asking every developer to replicate the full matrix locally.

**Extension availability.** PDO, mbstring, intl, imagick, redis — PHP extensions behave differently across platforms and versions. Your CI pipeline catches extension-level issues the moment you push code.

**Composer dependency graph.** A transitive dependency update can break your application in subtle ways. CI with a locked `composer.lock` and scheduled dependency updates keeps you informed about breakage before it reaches users.

**Database schema drift.** PHP applications evolve their database schemas through migrations. CI validates that migrations run cleanly against a fresh database, catching issues like incompatible column types or missing indexes before they corrupt production data.

## Setting Up PHPUnit in Your CI Pipeline

PHPUnit remains the foundation of PHP testing. Before you layer on static analysis, mutation testing, or browser tests, make sure your PHPUnit suite runs in CI and fails the build when tests break.

Start with a `phpunit.xml` configuration that works across environments:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         failOnRisky="true"
         failOnWarning="true"
         cacheResultFile=".phpunit.cache/test-results">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage cacheDirectory=".phpunit.cache/code-coverage">
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

A few details matter here. `failOnRisky` and `failOnWarning` harden the pipeline. Risky tests — tests that don't assert anything, or tests that rely on global state — undermine confidence in the suite. Warnings about deprecated methods or missing dependencies should stop the build, not scroll past unnoticed in the logs.

Set environment variables in the XML file rather than relying on a `.env` file. CI runners should be stateless. A `.env` file is one more thing that can differ between local and CI environments. Put testing configuration directly in `phpunit.xml` so the CI runner needs zero configuration to run the suite.

Run the suite in CI with coverage disabled unless you specifically need it for a badge or a merge gate:

```bash
php vendor/bin/phpunit --no-coverage
```

Coverage generation is expensive. A suite that runs in 30 seconds with coverage disabled takes 3 minutes with coverage enabled. For CI, run a fast feedback suite on every push and run coverage on a schedule or before release.

## GitHub Actions Workflow for PHP

GitHub Actions is the most accessible CI platform for PHP projects. It integrates directly with your repository, offers free minutes for public repositories, and supports matrix builds natively.

Here is a complete workflow file that runs tests, static analysis, and coding standards checks on every push and pull request:

```yaml
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.1, 8.2]
        dependency-stability: [prefer-lowest, prefer-stable]
      fail-fast: false

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: >-
          --health-cmd "mysqladmin ping --silent"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mbstring, pdo, pdo_mysql, intl, gd
          coverage: none
          tools: composer:v2

      - name: Validate composer.json
        run: composer validate --strict

      - name: Cache Composer dependencies
        uses: actions/cache@v3
        with:
          path: vendor
          key: ${{ runner.os }}-php-${{ hashFiles('composer.lock') }}

      - name: Install dependencies
        run: |
          composer install --no-progress --prefer-dist
          ${{ matrix.dependency-stability == 'prefer-lowest' && 'composer update --prefer-lowest --prefer-stable --no-progress --prefer-dist' || '' }}

      - name: Run PHPUnit
        run: php vendor/bin/phpunit --no-coverage
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: root

      - name: Run PHPStan
        run: php vendor/bin/phpstan analyse --no-progress --error-format=github

      - name: Run PHP-CS-Fixer
        run: php vendor/bin/php-cs-fixer fix --dry-run --diff --format=checkstyle
```

Several design decisions in this workflow deserve explanation.

The `fail-fast: false` option on the matrix strategy is critical. When one matrix permutation fails — say PHP 8.1 with `prefer-lowest` — the default behavior cancels all other in-progress jobs. That means you lose information about whether PHP 8.2 passes or whether `prefer-stable` works. Disable fail-fast. Let every permutation run to completion so you get the full picture.

The `services` block spins up a MySQL container linked to the runner. Your tests connect to it via `127.0.0.1` on port 3306. The health check ensures MySQL is accepting connections before the workflow proceeds to the test step. Without the health check, your tests race against the database container startup and fail intermittently.

The two dependency install strategies — `prefer-stable` and `prefer-lowest` — catch different classes of bugs. `prefer-stable` installs the latest versions within your version constraints. `prefer-lowest` installs the minimum versions. Running both tells you whether your code is compatible with the full range of dependency versions you claim to support.

## Code Quality Checks That Belong in CI

Testing is necessary but not sufficient. A passing test suite tells you the code behaves correctly. It does not tell you the code is well-structured, type-safe, or consistent with your project's coding standards. Code quality tools fill that gap.

### PHPStan

PHPStan performs static analysis on PHP code without running it. It catches type mismatches, undefined variables, unreachable code, and method signatures that violate inherited contracts.

Install it and configure a baseline:

```bash
composer require --dev phpstan/phpstan
php vendor/bin/phpstan analyse --generate-baseline
```

The baseline is essential for adopting PHPStan on an existing codebase. A level 6 or level 9 analysis on a legacy project produces thousands of errors. The baseline captures the current state and tells PHPStan to ignore existing violations while flagging new ones. Over time, you chip away at the baseline until you reach the analysis level you want.

Your `phpstan.neon` configuration:

```neon
parameters:
    level: 6
    paths:
        - src
    excludePaths:
        - src/Kernel.php
    checkMissingIterableValueType: true
    checkGenericClassInNonGenericObjectType: false
    treatPhpDocTypesAsCertain: false
```

Run it in CI and fail the build on any new violation:

```yaml
- name: PHPStan
  run: php vendor/bin/phpstan analyse --no-progress --error-format=github
```

### PHP-CS-Fixer

Coding standards are a team agreement, not a personal preference. PHP-CS-Fixer automates enforcement so code review focuses on logic, not whitespace.

```bash
composer require --dev friendsofphp/php-cs-fixer
```

Your `.php-cs-fixer.dist.php`:

```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(['src', 'tests'])
    ->exclude('var');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder);
```

In CI, run the fixer in dry-run mode so it reports violations without modifying files:

```bash
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

### Psalm

Psalm is an alternative to PHPStan with a different analysis engine. Some teams use one or the other. Some use both. Psalm's taint analysis feature is unique — it tracks user input through your application and flags potential security vulnerabilities like stored XSS or SQL injection.

```bash
composer require --dev vimeo/psalm
php vendor/bin/psalm --init
php vendor/bin/psalm --alter --issues=UnusedMethod
```

Run both PHPStan and Psalm in CI if your project has the budget. They catch different classes of issues. PHPStan excels at type-system violations. Psalm's taint analysis catches security issues that PHPStan ignores entirely.

## Database Migrations in CI

PHP applications couple their logic to a database schema. Migrations are the mechanism for evolving that schema. CI validates that migrations run correctly against a clean database.

Create a dedicated CI migration step after the database service starts:

```yaml
- name: Run migrations
  run: php bin/console doctrine:migrations:migrate --no-interaction
  env:
    DB_HOST: 127.0.0.1
    DB_PORT: 3306
    DB_DATABASE: testing
    DB_USERNAME: root
    DB_PASSWORD: root
```

This step catches several common failure modes:

- A migration references a table or column that doesn't exist yet.
- A migration uses MySQL-specific syntax that breaks on MariaDB.
- A migration runs successfully but produces a schema that doesn't match the entity definitions.
- A migration is missing a `down()` method and cannot be rolled back.

Add a rollback step to verify that down migrations work:

```yaml
- name: Verify rollback
  run: |
    php bin/console doctrine:migrations:migrate prev --no-interaction
    php bin/console doctrine:migrations:migrate --no-interaction
```

This ensures the migration is reversible. A deployment that cannot roll back is a deployment that forces an incident response when something goes wrong.

For teams using raw SQL migrations or a custom migration system, wrap the migration runner in a test:

```php
public function test_migrations_run_cleanly(): void
{
    $output = new BufferedOutput();
    $application = new Application($kernel);
    $application->setAutoExit(false);

    $exitCode = $application->run(
        new ArrayInput(['command' => 'doctrine:migrations:migrate', '--no-interaction' => true]),
        $output
    );

    $this->assertSame(0, $exitCode, $output->fetch());
}
```

## Testing Across Multiple PHP Versions

PHP 8.1 introduced enums, readonly properties, and intersection types. PHP 8.2 adds readonly classes, standalone `true` type, and a deprecation notice for dynamic properties. If your library or application claims to support multiple PHP versions, you need to test against all of them.

The matrix strategy in GitHub Actions handles this natively:

```yaml
strategy:
  matrix:
    php: [8.0, 8.1, 8.2]
    include:
      - php: 8.0
        composer-flags: "--ignore-platform-req=php"
```

Use platform requirement flags carefully. If your `composer.json` requires `php: ^8.1` but you want to test against 8.0 to confirm a deprecation notice or a backward-compatible change, you need `--ignore-platform-req=php` to bypass Composer's platform check.

A more robust approach is to express your true requirements in `composer.json` and use a separate workflow for forward-compatibility testing:

```yaml
name: Forward Compatibility

on:
  schedule:
    - cron: "0 6 * * 1"
  workflow_dispatch:

jobs:
  php84:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          extensions: mbstring, pdo, pdo_mysql
      - run: composer update --ignore-platform-req=php
      - run: php vendor/bin/phpunit
```

The scheduled workflow runs weekly against the next PHP version to catch incompatibilities early. When PHP 8.4 ships, you already know whether your application works.

## Docker in the CI Pipeline

Docker simplifies CI by eliminating the "works on my machine" problem. Your CI runner can build a container image that matches your production environment exactly, then run tests inside that container.

Start with a production-optimized Dockerfile:

```dockerfile
FROM php:8.1-fpm as base

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libicu-dev \
    && docker-php-ext-install pdo_pgsql intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader

COPY . .
RUN chown -R www-data:www-data var
```

Build the image in CI and test within it:

```yaml
- name: Build Docker image
  run: docker build --target base -t app .

- name: Run tests in container
  run: |
    docker run --rm \
      -e APP_ENV=test \
      -e DATABASE_URL=postgres://postgres:password@db:5432/test \
      app \
      php vendor/bin/phpunit
```

Docker Compose extends this pattern for multi-service architectures:

```yaml
- name: Start services
  run: |
    docker compose -f docker-compose.ci.yml up -d
    docker compose exec -T app php bin/console doctrine:database:create
    docker compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction

- name: Run tests
  run: docker compose exec -T app php vendor/bin/phpunit
```

The key advantage of Docker in CI is environment parity. Your CI build runs the same base image, the same extension versions, the same system libraries as your staging and production environments. When a test passes in CI, you know it passes on the same stack that runs in production.

## Deployment Strategies for PHP Applications

Continuous delivery requires an automated deployment process. PHP offers several battle-tested options.

### Deployer

[Deployer](https://deployer.org) is a PHP-based deployment tool that handles zero-downtime deployments, rollbacks, and multi-server coordination.

Install it:

```bash
composer require --dev deployer/deployer
```

A deployment recipe for a Symfony application:

```php
<?php

namespace Deployer;

require 'recipe/symfony.php';

host('production')
    ->setHostname('app.example.com')
    ->setPort(22)
    ->setRemoteUser('deploy')
    ->setIdentityFile('~/.ssh/deploy_key')
    ->setDeployPath('/var/www/app');

set('repository', 'git@github.com:org/app.git');
set('git_tty', false);
set('keep_releases', 5);

after('deploy:failed', 'deploy:unlock');

task('build:frontend', function () {
    cd('{{release_path}}');
    run('npm ci && npm run build');
});

before('deploy:symlink', 'build:frontend');
```

Trigger deployments from CI after the test suite passes:

```yaml
- name: Deploy to production
  if: github.ref == 'refs/heads/main'
  run: php vendor/bin/dep deploy production
  env:
    DEPLOYER_PRIVATE_KEY: ${{ secrets.DEPLOY_KEY }}
```

Deployer creates symlinks for atomic releases. Each deployment creates a new directory, runs build steps, and swaps the `current` symlink. If the build fails, the symlink never updates and the site stays on the previous release.

### Envoy

Laravel's Envoy is a lighter alternative — a single PHP file that defines SSH tasks:

```php
<?php

use Illuminate\Envoy\Configuration;

return Configuration::create()
    ->host('production', 'root@app.example.com')
    ->task('deploy', function (): void {
        $this->cd('/var/www/app');
        $this->run('git pull origin main');
        $this->run('composer install --no-dev --no-interaction');
        $this->run('php bin/console doctrine:migrations:migrate --no-interaction');
        $this->run('php bin/console cache:clear');
    })
    ->task('rollback', function (): void {
        $this->cd('/var/www/app');
        $this->run('git reset --hard HEAD~1');
        $this->run('composer install --no-dev --no-interaction');
        $this->run('php bin/console cache:clear');
    });
```

Run it from CI:

```bash
php vendor/bin/envoy run deploy
```

Envoy is simple and transparent. There is no release directory structure. It runs the commands you specify on the remote server. This makes it a good fit for smaller projects where a full Deployer setup is excessive.

### SSH Deployment

The most direct approach is executing deployment commands over SSH directly from CI. This skips the abstraction layer and gives you full control over the deployment process.

```yaml
- name: SSH deploy
  uses: appleboy/ssh-action@v0.1.5
  with:
    host: ${{ secrets.DEPLOY_HOST }}
    username: ${{ secrets.DEPLOY_USER }}
    key: ${{ secrets.DEPLOY_KEY }}
    script: |
      cd /var/www/app
      git pull origin main
      composer install --no-dev --no-interaction --optimize-autoloader
      php bin/console doctrine:migrations:migrate --no-interaction
      php bin/console cache:clear
      sudo systemctl reload php8.1-fpm
```

SSH-based deployment is the simplest to set up but the hardest to make robust. There is no built-in rollback mechanism. If a command fails midway through the script, the server is in an undefined state. Use this approach for staging environments or small applications where downtime risk is acceptable.

## Conditional Deployments and Environments

Not every push should go to production. A robust pipeline separates concerns across environments.

Configure GitHub Environments to gate deployments:

```yaml
- name: Deploy to staging
  if: github.ref == 'refs/heads/develop'
  run: php vendor/bin/dep deploy staging

- name: Deploy to production
  if: github.ref == 'refs/heads/main'
  environment: production
  run: php vendor/bin/dep deploy production
```

The `environment: production` line creates a deployment protection rule. GitHub pauses the workflow and waits for an authorized reviewer to approve the deployment. This gives you continuous delivery without continuous deployment — every change is deployable, but production releases require human approval.

For true continuous deployment, remove the environment gate and automate the entire pipeline end to end. This is appropriate for applications with comprehensive test coverage, feature flags, and robust monitoring. If your tests miss bugs regularly, do not enable continuous deployment.

## Secrets Management

CI pipelines need access to secrets — SSH keys, API tokens, database passwords. Store them in GitHub Secrets or your CI platform's equivalent vault. Never hardcode secrets in repository files.

Access secrets in your workflow:

```yaml
- name: Deploy
  run: php vendor/bin/dep deploy production
  env:
    DEPLOYER_PRIVATE_KEY: ${{ secrets.DEPLOY_KEY }}
```

Restrict secret access to the jobs that need them. The PHPUnit job does not need the production deploy key. The deploy job does not need the Stripe API key. Use granular secret scoping to limit blast radius.

For database credentials in CI, use the service container's defaults rather than secrets:

```yaml
services:
  postgres:
    image: postgres:14
    env:
      POSTGRES_USER: test
      POSTGRES_PASSWORD: test
      POSTGRES_DB: test
```

The test database only exists for the duration of the CI job. Its credentials are not sensitive.

## Common CI Failures and How to Fix Them

Even a well-configured pipeline fails occasionally. Here are the most common PHP CI failures and their fixes.

**Composer memory exhaustion.** CI runners have limited memory. Composer's dependency resolution can exceed it on large projects. Fix with:

```yaml
- name: Install dependencies
  run: COMPOSER_MEMORY_LIMIT=-1 composer install --no-progress --prefer-dist
```

**Flaky tests dependent on test order.** PHPUnit should not care about test execution order. If tests pass when run in isolation but fail in CI, you have a shared-state problem. Look for static properties, global state, or database records that tests fail to clean up. Enable PHPUnit's `--order-by=random` and `--repeat=5` flags to surface flaky tests:

```bash
php vendor/bin/phpunit --order-by=random --repeat=3
```

**Timeouts during test execution.** CI runners are slower than developer machines. A test that finishes in 200ms locally might hit a 500ms timeout in CI. Add a safety margin to timeout-sensitive tests:

```php
$this->assertTrue(
    $event->wait(5000), // 5 seconds instead of default 1 second
    'Webhook response did not arrive in time'
);
```

**Missing extensions in CI.** The `shivammathur/setup-php` action installs PHP extensions via the `extensions` parameter. If your application requires an extension not in the default list, add it explicitly:

```yaml
- uses: shivammathur/setup-php@v2
  with:
    php-version: 8.1
    extensions: redis, imagick, pcov, soap
```

## Pipeline Performance Optimization

A slow CI pipeline is a dead CI pipeline. Developers learn to ignore builds that take 30 minutes. Optimize for feedback speed.

**Parallelize test suites.** Split your test suite into groups and run them in parallel:

```yaml
strategy:
  matrix:
    test-group: [1, 2, 3, 4]

steps:
  - run: php vendor/bin/phpunit --testsuite=feature --group=group${{ matrix.test-group }}
```

Codeception, PHPUnit's parallel runner, and Paraunit all support parallel test execution.

**Cache Composer dependencies.** Composer install without cache takes 2–3 minutes on a cold runner. With cache, it drops to 10 seconds:

```yaml
- uses: actions/cache@v3
  with:
    path: vendor
    key: ${{ runner.os }}-php-${{ hashFiles('composer.lock') }}
```

**Cache PHPUnit result cache.** PHPUnit's result cache skips tests that haven't changed since the last run:

```yaml
- uses: actions/cache@v3
  with:
    path: .phpunit.cache
    key: phpunit-${{ hashFiles('**/*.php') }}
```

**Run fast tests first.** Structure your pipeline so the quickest checks run earliest. PHP-CS-Fixer runs in seconds. PHPUnit unit tests run in milliseconds. Acceptance tests run in minutes. If a formatting issue fails the build, you want to know in 5 seconds, not 5 minutes.

## Building a Quality Gate

A quality gate is the set of conditions that must pass before code merges. Define it explicitly in your repository's branch protection rules.

For a PHP project, a reasonable quality gate includes:

- PHPUnit suite passes with zero failures, zero warnings, zero risky tests.
- PHPStan analysis passes at level 6 with zero new errors against the baseline.
- PHP-CS-Fixer dry-run reports zero violations.
- Psalm analysis passes with zero issues.
- Database migrations run cleanly forward and backward.
- Composer validate passes.
- The build passes on all PHP versions in the support matrix.

Configure branch protection in GitHub to require all checks to pass before merge. Require up-to-date branches so an outdated branch cannot merge if another branch introduced a failure. Require pull request reviews as a human gate on top of the automated gate.

The quality gate is not optional. When a critical bug requires a hotfix, the temptation to bypass the gate is strong. Resist it. If your pipeline is fast and reliable, waiting for it costs less than deploying a broken fix.

## Measuring Pipeline Health

Track your pipeline metrics. Without data, you cannot improve.

- **Build time.** How long does a full CI run take? Trend it over time. If it creeps up, investigate.
- **Failure rate.** What percentage of builds fail? When a change pushes the failure rate above 5%, stop and fix the root cause.
- **Time to detect.** How long between pushing code and knowing whether the build passes? Target under 10 minutes for the full suite.
- **Deployment frequency.** How often do you deploy to production? Increase this as your pipeline matures.
- **Rollback rate.** How often do you revert a deployment? This should decrease as your quality gate improves.

Most CI platforms surface these metrics in their dashboards. GitHub Actions exposes workflow run times, success rates, and queue times in the Insights tab. Review them weekly.

## The Pipeline as Code

Your CI configuration is code. Treat it with the same rigor you apply to your application code. Review pipeline changes in pull requests. Version them alongside the application code. Test pipeline changes in a branch before merging to main.

A change to the pipeline can break the pipeline. A broken pipeline blocks the entire team. Treat pipeline configuration with respect.

Document your deployment process in a `DEPLOYMENT.md` file in the repository root. Include the manual steps, the rollback procedure, and the team member responsible for production deployments. Automation does not eliminate the need for human process — it reduces the surface area for human error.

## Continuous Improvement

CI/CD is not a project you finish. It is a process you refine. Start with PHPUnit in CI. Add coding standards enforcement. Layer on static analysis. Add database migration validation. Set up deployment automation. Measure and iterate.

The teams that deploy multiple times per day did not start there. They started with one test in CI and built from there. The pipeline described in this guide is ambitious but achievable. Implement it step by step. Every check you add prevents a bug that would otherwise reach production.

Your code ships automatically. Your tests run on every push. Your deployments roll back cleanly. That is continuous code. That is the standard PHP development should hold itself to.
