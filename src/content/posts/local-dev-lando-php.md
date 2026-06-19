---
title: "Local PHP Development With Lando - Docker Dev Environments Made Easy"
description: "Set up local PHP development environments with Lando and Docker. Streamline your DevOps workflow and simplify team onboarding."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-11-14 21:00:00"
tags: ["Lando", "Docker", "PHP", "Local Development", "DevOps"]
---

Local development environments are deeply personal. Every developer has opinions, and tools come and go. Lando stands out by wrapping Docker containers in a dead-simple configuration layer, letting you focus on code instead of containers.

## What Is Lando?

Lando isolates all your development needs on a per-application basis using Docker. It bundles Docker Desktop on macOS and Windows, so you're ready out of the box. Unlike Valet, services run in Docker containers rather than directly on your system. Unlike Homestead, you only virtualize the necessities — not an entire operating system.

Lando supports Linux, Windows, and macOS. This cross-platform compatibility reduces team friction when adopting new tools.

## Installing Lando

Download the latest release from [Lando's GitHub releases](https://github.com/lando/lando/releases) and select your platform package. Installation requires privileged access. Once complete, the `lando` command is available in your CLI.

## Configuring a Project

Running `lando init` starts an interactive questionnaire about your project. Lando generates a `.lando.yml` configuration file. For a Laravel project:

```yaml
name: rfid
recipe: laravel
config:
  webroot: public
  php: '8.0'
  database: mysql
  xdebug: true
  cache: redis
```

Recipes in Lando are high-level abstractions containing routing, services, and tooling for specific project types like Laravel, Symfony, Drupal, WordPress, and more.

### Starting Your Environment

```bash
lando start
```

Lando downloads all required Docker images and configures your application. When you see "Booomshakalaka!!!", you're running.

Behind the scenes, `docker ps` shows:

- A Redis container
- A MySQL container (Bitnami)
- An Apache/PHP container
- A Traefik reverse proxy handling URL routing

## Adding Services

Forgot Mailhog for local email processing? Edit `.lando.yml`:

```yaml
services:
  mailhog:
    type: mailhog:v1.0.0
    portforward: true
    hogfrom:
      - appserver
```

Apply changes:

```bash
lando rebuild -y
```

The rebuild adds Mailhog URLs. Update your `.env` to point at Lando's services:

```
APP_URL=https://rfid.lndo.site
DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=laravel
```

## Tooling Commands

Lando maps your typical CLI commands into the correct container:

```bash
lando artisan migrate
lando composer install
lando mysql
lando db:seed
lando db-export backup.sql
lando db-import backup.sql
```

Use `lando info` to get connection details for external tools:

```json
{
  "service": "database",
  "type": "mysql",
  "external_connection": { "host": "127.0.0.1", "port": "64802" },
  "creds": { "database": "db", "password": "pw", "user": "user" }
}
```

Plug these into PhpStorm or any SQL client.

## Adding Node Services

For front-end asset building, add a Node service:

```yaml
services:
  mailhog:
    type: mailhog:v1.0.0
    portforward: true
    hogfrom:
      - appserver
  node:
    type: node:16
    command: npm run dev
```

After `lando rebuild -y` you'll see Node URLs in your service list. Many developers still run NPM commands directly on their host machine. Use whatever makes sense for your workflow.

## Recipes and Plugins

Lando supports far more than Laravel. Use recipes for:

- WordPress
- Drupal
- Symfony
- Joomla
- Custom recipes via `type: php`, `type: node`, etc.

Plugins extend Lando further — MSSQL, Varnish, and more are available. When you remove the complexity of configuring and installing services, you enable developers to experiment with new tools.

## Lifecycle Management

```bash
lando stop      # Stop all services (preserves data)
lando start     # Restart services
lando destroy   # Delete everything (database, storage)
lando rebuild   # Rebuild with config changes
```

## The Result

One file — `.lando.yml` — defines your entire development environment. Unlike Laravel Sail, there are no supplemental configuration files or templates added to your project. Unlike Homestead, you're not virtualizing a full operating system.

Lando isn't perfect. The more services you add, the more system resources you consume. Power matters. But it succeeds where many tools fail: it gets out of your way. You feel productive immediately, without fighting proxies, PHP versions, or database drivers.

Whether your project needs Apache, NGINX, PHP 8.0, MySQL, Redis, Mailhog, Node, MSSQL, or Varnish, Lando wraps the Docker complexity so you don't have to be a container expert. That's the mark of a great local development tool.
