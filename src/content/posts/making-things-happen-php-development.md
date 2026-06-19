---
title: "Making Things Happen - From PHP Idea to Production"
description: "Learn how to take a PHP application from development to production using Makefiles, CI/CD pipelines, deployment automation, and monitoring strategies."
pubDate: "2022-09-14 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Development", "Production", "DevOps", "Deployment"]
selected: false
---

This month we are straying slightly from PHP tooling to cover `make` and how it simplifies every stage of the development lifecycle — from setting up a project to deploying to production. Make is an application common to Linux and macOS systems. A Makefile lets you create commands that execute long, complex operations: managing dependencies, setting up test environments, running test suites, building Docker images, and deploying your application.

The Makefile lives in the project root, gets checked into version control, and ensures every team member and CI/CD runner uses the exact same commands. No more "it works on my machine." No more digging through README files for setup instructions.

## Your First Makefile

Makefiles require Tab indentation — not spaces. This is the most common stumbling block. With that in mind, here is a Makefile for a typical PHP application:

```makefile
.DEFAULT_GOAL := setup

.PHONY: setup
setup:
	cp .env.example .env
	composer install
	npm install
	npm run development
	mysql -u root -e "CREATE DATABASE IF NOT EXISTS app;"
	php artisan key:generate
	php artisan migrate

.PHONY: clean
clean:
	rm -rf vendor/
	rm -rf node_modules/
```

Running `make setup` executes all seven commands in sequence. Running `make clean` removes the `vendor` and `node_modules` directories for a fresh start.

## Everyday Development Tasks

### Resetting the Database

```makefile
.PHONY: clean_db
clean_db:
	php artisan migrate:fresh
	php artisan db:seed
```

Instead of typing `php artisan migrate:fresh && php artisan db:seed` every time, you run `make clean_db`. Simple, repeatable, and documented in the Makefile.

### Running Tests

```makefile
.PHONY: test
test:
	php vendor/bin/phpunit
```

Now `make test` runs your full test suite. No need to remember `vendor/bin/phpunit` or `./vendor/bin/phpunit` — the Makefile abstracts it.

```bash
$ make test
PHPUnit 9.5.20

......................................... 41 / 41 (100%)

Time: 00:01.827, Memory: 48.50 MB
OK (41 tests, 250 assertions)
```

## CI/CD Integration

The real power of Makefiles reveals itself when you integrate with CI/CD. Instead of writing platform-specific YAML that duplicates your setup logic, your CI pipeline calls the same Makefile targets you use locally.

### Before: Raw Commands in GitHub Actions

```yaml
steps:
  - name: Install dependencies & Setup App
    run: |
      composer install && php artisan key:generate --ansi && php artisan migrate --force && php artisan db:seed --force
    env:
      DB_PORT: ${{ job.services.mysql.ports[3306] }}

  - name: Execute tests
    run: vendor/bin/phpunit
    env:
      DB_PORT: ${{ job.services.mysql.ports[3306] }}
```

### After: Makefile Targets

```yaml
steps:
  - name: Install dependencies & Setup App
    run: make setup
    env:
      DB_PORT: ${{ job.services.mysql.ports[3306] }}

  - name: Execute tests
    run: make test
    env:
      DB_PORT: ${{ job.services.mysql.ports[3306] }}
```

The CI configuration shrinks dramatically. More importantly, you can reproduce exactly what the runner does by running `make setup && make test` on your own machine. When a test fails in CI, you do not need to debug differences between environments — the commands are identical.

This pattern works with any CI platform: GitHub Actions, GitLab CI, Jenkins, TeamCity. Every platform supports running shell commands. Every platform can call `make`.

## Deploying to Production

Deploying a PHP application typically involves pulling the latest code, installing dependencies (without dev requirements), running database migrations, and performing any cleanup. A Makefile target wraps this:

```makefile
.PHONY: deploy
deploy:
	php vendor/bin/envoy run deploy
```

For Laravel applications, Envoy provides a Blade-based deployment script:

```blade
@servers(['prod' => 'phparch@phparch.com'])

@task('deploy', ['on' => 'prod'])
cd /home/phparch/awesome-php
git pull origin main
/usr/bin/php8.1 /usr/bin/composer install --no-dev
/usr/bin/php8.1 artisan migrate --force
@endtask
```

The GitHub Actions deploy step becomes:

```yaml
- name: Install SSH key
  uses: shimataro/ssh-key-action@v2
  with:
    key: ${{ secrets.SSH_KEY }}
    known_hosts: ${{ secrets.KNOWN_HOSTS }}
  if: github.ref == 'refs/heads/main'

- name: Deploy
  run: make deploy
  if: github.ref == 'refs/heads/main'
```

SSH keys go in GitHub Secrets. The deploy command stays standard across environments.

## Building Docker Images

Makefiles shine for Docker workflows where build commands get long:

```makefile
REGISTRY := registry.phparch.com
CONTAINER := phparch/awesome-php
HASH := $(shell git rev-parse HEAD)
BUILD = DOCKER_BUILDKIT=1 docker build --build-arg HASH=$(HASH)

ifndef GITHUB_RUN
GITHUB_RUN=latest
endif

.PHONY: docker_build
docker_build:
	$(BUILD) --label=GIT_HASH=$(HASH) \
	-t $(REGISTRY)/$(CONTAINER):$(GITHUB_RUN) .

.PHONY: docker_push
docker_push:
	docker push $(REGISTRY)/$(CONTAINER):$(GITHUB_RUN)
```

Variables keep the Makefile reusable across projects. The `GITHUB_RUN` environment variable provides a unique CI run ID in GitHub Actions. Locally, it defaults to `latest`, so you can build and test images without CI.

The git hash gets baked into the image as a label. Run `docker inspect` on any deployed image to trace it back to the exact commit.

## The Workflow

A complete CI/CD pipeline using Make targets follows three steps:

```bash
make setup     # Install dependencies, configure environment
make test      # Run test suite
make deploy    # Deploy to production
```

That is it. Three commands. Every developer on the team uses the same commands. CI uses the same commands. New contributors set up their environment in one step.

## Why Make?

Make is already installed on virtually every Linux and macOS system. It has no dependencies, no runtime, and no version manager needed. A Makefile is a plain text file that documents your project's operations in a standardized format.

Think of targets as command-line aliases that are:
- **Version-controlled** — Everyone gets the same aliases
- **Documented** — The Makefile itself is the documentation
- **Scriptable** — CI/CD platforms call make targets directly
- **Composable** — Targets can depend on other targets

The `.PHONY` declaration tells Make that these targets do not produce files — they run commands. This prevents Make from checking for a file named `setup` or `test` and skipping the task.

## What about Portability?

A common concern is that Makefiles use shell commands that may not work on Windows. For Laravel projects, the commands are typically PHP and Node.js — both available on Windows through WSL. For pure PHP projects, `composer` and `php` commands work on all platforms.

The alternative is maintaining platform-specific scripts in `package.json` or `.bat` files. One Makefile that runs in WSL or natively on Linux/macOS is simpler than maintaining two scripts.

## Summary

Make transforms how your team interacts with your project:

- `make setup` — One command to go from clone to running
- `make test` — Run the test suite the same way CI does
- `make deploy` — Push to production with one command
- `make docker_build` — Build container images with proper tags

The habit of using Make targets in CI/CD saves an enormous amount of time debugging environment differences. When the runner is only executing your Makefile targets, reproducing any failure is as simple as running the same target locally.

Make is one of those tools that more developers should learn. It makes complex commands boring, and boring is good. Boring means you can stop thinking about setup and start solving real problems.
