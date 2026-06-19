---
title: "Drupal 9 - Introduction and Installation Guide for PHP Developers"
description: "Learn how to install Drupal 9 from scratch. Step-by-step guide covering system requirements, installation wizard, first configuration, and essential post-install steps."
pubDate: "2022-04-18 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Drupal", "Drupal 9", "PHP", "CMS", "Installation", "Web Development", "Content Management"]
selected: false
---

You know WordPress. You've built a few Laravel apps. Maybe you've even tinkered with Symfony. Then someone mentions Drupal and you picture a clunky admin panel from 2010 with a steep learning curve and a reputation for being "for developers only."

That picture is outdated. Drupal 9 is a modern, component-based content management framework built on Symfony components. It powers 2.3% of the web—including sites for Tesla, The Economist, NASA, and the Australian government. It handles millions of pages of content with granular access control, multi-site setups, and a content modeling system that puts most "headless" CMS platforms to shame.

This is the first article in a new column about Drupal. We'll start from zero—downloading Drupal, configuring your environment, and walking through the installation wizard. You'll end with a running Drupal 9 site, a clear picture of the directory layout, and a roadmap for what to do next.

## What Is Drupal 9?

Drupal 9 is the latest major version of the open-source content management framework first released in 2001. Version 9 launched in June 2020 and represents a strategic shift: rather than a ground-up rewrite like the jump from Drupal 6 to 7 or 7 to 8, Drupal 9 is essentially Drupal 8 with outdated dependencies removed and deprecated code stripped out. If you've used Drupal 8, you already know Drupal 9. The upgrade path is the smoothest in Drupal's history.

**Who uses Drupal?** Enterprise organizations, higher education institutions, government agencies, and any team that needs a content platform flexible enough to model complex relationships. Drupal's entity system lets you define custom content types, taxonomies, and fields through the admin UI without writing a single line of code. You can build a blog, a product catalog, a multi-language news site, or a membership portal—all from the same installation.

**Why choose Drupal over other platforms?**

- **Content modeling** — You define your data structures in the admin panel. Content types, fields, entity references, paragraphs, and taxonomy vocabularies. No need to migrate schemas or rebuild tables when requirements change.
- **Access control** — Drupal's permission system is granular down to the field level. You can let editors create articles but restrict which fields they can edit, which revisions they can view, and which publishing workflows they can trigger.
- **Multi-site** — A single Drupal codebase can serve multiple sites with separate databases, domains, themes, and modules. Large organizations run dozens of sites from one deployment.
- **API-first** — Drupal ships with REST and JSON:API support out of the box. You can use it as a headless backend with a React, Vue, or Next.js frontend.
- **Multilingual** — Drupal supports content translation at the field level with language detection, URL prefixes, and fallback strategies built in.

## System Requirements

Drupal 9 runs on a standard LAMP/LEMP stack. Here's what you need:

| Requirement | Recommended |
|---|---|
| **PHP** | 7.3 or higher (PHP 8.0+ recommended) |
| **Database** | MySQL 5.7.8+, MariaDB 10.3.7+, or PostgreSQL 10+ |
| **Web Server** | Apache 2.4+ (with mod_rewrite) or Nginx 1.15+ |
| **Memory** | 256 MB PHP memory limit minimum |

**PHP extensions required:**

- `pdo` (with MySQL or PostgreSQL driver)
- `mbstring`
- `gd` (image processing)
- `xml`
- `json`
- `curl`
- `openssl`

Install missing extensions on Ubuntu or Debian:

```bash
sudo apt install php8.1-cli php8.1-common php8.1-mysql \
  php8.1-gd php8.1-xml php8.1-curl php8.1-mbstring \
  php8.1-opcache composer
```

Verify with `php -v` and `php -m | grep -E "(pdo|mbstring|gd|xml|json|curl|openssl)"`.

## Downloading Drupal 9

You have two options: Composer (recommended) or the tarball.

### Option 1: Composer (Recommended)

Drupal uses Composer for dependency management just like any modern PHP project. The canonical way to start a new Drupal 9 project uses the `drupal/recommended-project` template:

```bash
composer create-project drupal/recommended-project:^9 example-site
cd example-site
```

This creates a `web/` directory containing the Drupal core files and a `vendor/` directory with Composer-managed dependencies. The template locks Drupal core to a specific version range and sets up sensible defaults for Drush and other tools.

### Option 2: Tarball

If Composer isn't an option, download the tarball from drupal.org:

```bash
wget https://www.drupal.org/download-latest/tar.gz -O drupal.tar.gz
tar -xzf drupal.tar.gz
mv drupal-9.*/ example-site/
```

You'll need to run `composer install` inside the directory afterward to pull in dependencies. The tarball includes Drupal core but not its vendor libraries.

## Directory Structure

Here's the layout of a Composer-installed Drupal 9 project:

```
example-site/
├── vendor/              # Composer dependencies (Symfony, Twig, Guzzle, etc.)
├── web/                 # Webroot — point your server here
│   ├── core/            # Drupal core files (never modify)
│   ├── modules/         # Contributed and custom modules
│   │   ├── contrib/     # Modules from drupal.org
│   │   └── custom/      # Your custom modules
│   ├── themes/          # Contributed and custom themes
│   │   ├── contrib/
│   │   └── custom/
│   ├── sites/           # Site-specific configuration
│   │   ├── default/     # Default site settings
│   │   │   ├── settings.php       # Main config file
│   │   │   ├── services.yml       # Service container overrides
│   │   │   └── files/            # Public file storage
│   │   └── example.com/ # Multi-site directories
│   ├── index.php        # Front controller
│   └── .htaccess        # Apache rewrite rules
├── composer.json
├── composer.lock
└── drush/               # Drush command configuration
```

The key difference from WordPress or Laravel: Drupal's webroot is `web/`, not the project root. This keeps system files away from the document root, a security best practice. If you're coming from Laravel, this is the same pattern as Laravel's `public/` directory.

## Web Server Configuration

### Apache

Drupal ships with a `.htaccess` file in `web/` that handles URL rewriting. You just need to enable `mod_rewrite` and point your virtual host to `web/`:

```apache
<VirtualHost *:80>
    ServerName drupal.local
    DocumentRoot /var/www/example-site/web

    <Directory /var/www/example-site/web>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/drupal_error.log
    CustomLog ${APACHE_LOG_DIR}/drupal_access.log combined
</VirtualHost>
```

Enable the site and reload Apache:

```bash
sudo a2ensite drupal.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### Nginx

Nginx requires explicit rewrite rules since it doesn't use `.htaccess` files:

```nginx
server {
    listen 80;
    server_name drupal.local;
    root /var/www/example-site/web;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ ^/sites/.*/files/styles/ {
        try_files $uri @rewrite;
    }

    location @rewrite {
        rewrite ^ /index.php;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires max;
        log_not_found off;
    }
}
```

## Database Setup

Create a database and user for Drupal:

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE drupal9 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'drupal'@'localhost' IDENTIFIED BY 'your-strong-password';
GRANT ALL PRIVILEGES ON drupal9.* TO 'drupal'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Drupal 9 defaults to the `utf8mb4` character set for full Unicode support, including emoji characters in content. Don't use the older `utf8` collation—it only supports characters up to 3 bytes.

## Running the Installation Wizard

### Web UI Installation

Navigate to `http://drupal.local` in your browser. Drupal detects this is a fresh installation and redirects you to `install.php`.

**Step 1 — Choose language.** Select English or any available translation. Drupal downloads translations automatically if your server can reach drupal.org.

**Step 2 — Choose installation profile.** You have three choices:

- **Standard** — Installs with commonly used features enabled. Good for most sites.
- **Minimal** — Minimal modules. Start from a blank slate.
- **Custom** — Not available in the UI; uses a custom profile you've written.

For your first Drupal site, pick Standard.

**Step 3 — Database configuration.** Enter the database credentials you created:

```
Database name: drupal9
Database username: drupal
Database password: your-strong-password
Host: localhost
Port: 3306
```

Leave "Table prefix" empty unless you're sharing a database with another application.

**Step 4 — Install.** Drupal runs the installation process. This takes 30–60 seconds. It creates the database schema, runs default content migrations, and sets up configuration.

**Step 5 — Site configuration.** After installation, fill in:

- **Site name** — Your site's display name.
- **Site email address** — Used for system notifications.
- **Username / Password / Email** — The admin account. Use a strong password. Do not use "admin" as the username—bots scan for it.
- **Default country and timezone** — Used for date formatting and cron scheduling.

**Step 6 — Done.** Drupal redirects you to the homepage. Click "Manage" in the toolbar to access the admin dashboard.

### Installation via Drush

Drush (Drupal Shell) is the command-line tool for Drupal, similar to Laravel's Artisan. Install it globally:

```bash
composer global require drush/drush
```

Or from your project root (it's already included if you used `drupal/recommended-project`):

```bash
./vendor/bin/drush site:install standard \
  --db-url=mysql://drupal:your-strong-password@localhost/drupal9 \
  --site-name="My Drupal Site" \
  --account-name=admin \
  --account-pass=secure-password
```

Drush is significantly faster for repeated installations and essential for automated deployment workflows. You'll reach for it constantly once you're past the initial setup.

## First Configuration Steps

Your site is running. Now harden it before you start building.

### Enable Clean URLs

Clean URLs remove the `?q=` parameter from Drupal paths. The installation wizard checks for clean URL support, but you can verify it's working by visiting a path like `/admin/config` without query parameters. If you see a 404, your web server rewrite rules aren't configured correctly.

On Apache, ensure `mod_rewrite` is enabled and `AllowOverride All` is set on the site directory. On Nginx, confirm the `try_files` directive is in place. Once clean URLs work, Drupal generates search-engine-friendly paths automatically.

### Trusted Host Patterns

Drupal 9 includes HTTP host header validation to prevent poisoning attacks. Open `web/sites/default/settings.php` and uncomment the `trusted_host_patterns` setting:

```php
$settings['trusted_host_patterns'] = [
  '^drupal\.local$',
  '^www\.drupal\.local$',
];
```

Each pattern is a regex matching a valid hostname for your site. If you skip this, Drupal logs a warning and some redirect-based functionality may behave unexpectedly. Add all domains your site will serve from, including the production domain and any staging URLs.

### Configuration Sync

Drupal stores configuration in the database by default. For version control, you need to export configuration to the filesystem:

```bash
mkdir config/sync
```

Then in `settings.php`:

```php
$settings['config_sync_directory'] = 'sites/default/files/config_XXXX/sync';
```

Drupal generates the `XXXX` hash automatically during installation—check your `settings.php` to confirm the exact path. Alternatively, set your own path outside the webroot:

```php
$settings['config_sync_directory'] = '../config/sync';
```

Once configured, use Drush to export and import configuration:

```bash
drush config:export
drush config:import
```

This is the Drupal equivalent of Laravel's `php artisan config:cache`—it moves configuration out of the database and into files you can track with Git. Commit the exported config alongside your code for deterministic deployments.

## Essential Modules

Drupal core handles the basics. These contributed modules extend it for real-world use:

| Module | Purpose |
|---|---|
| **Admin Toolbar** | Improves the admin navigation with dropdown menus and better hierarchy visibility. |
| **Pathauto** | Generates clean URL aliases automatically based on content type patterns. No more `node/123`. |
| **Metatag** | Adds meta tag management for SEO—title tags, descriptions, Open Graph, Twitter cards. |
| **Redirect** | Creates automatic 301 redirects when URL aliases change. Prevents broken links. |
| **Paragraphs** | Replaces the WYSIWYG with a flexible component builder. Each paragraph type is a reusable fieldable widget. |
| **Webform** | A drag-and-drop form builder for contact forms, surveys, registrations, and payment forms. |
| **Twig Tweak** | Provides additional Twig functions and filters for template development. |
| **Devel** | Developer helper with database query logging, variable inspection, and content generation. Never enable on production. |

Install modules via Composer:

```bash
composer require drupal/admin_toolbar
composer require drupal/pathauto
composer require drupal/metatag
```

Then enable them through Drush:

```bash
drush en admin_toolbar pathauto metatag
```

This pattern—`composer require` then `drush en`—is how you manage Drupal modules in a modern workflow. Never download modules as tarballs and extract them into `modules/contrib`. Composer handles dependencies and updates automatically.

## Drupal vs WordPress vs Laravel

If you're a PHP developer evaluating platforms, here's the honest comparison.

**WordPress** dominates the lower end of the market. It powers 43% of the web. You can install it in five minutes, add a theme, and have a blog running in an hour. The plugin ecosystem is enormous, but quality varies wildly. WordPress is the right choice for simple sites, marketing pages, and client projects where the team includes non-developers who need to edit content directly.

**Drupal** shines when your content model is complex. If you need a publishing platform with 15 content types, each with different workflows, access controls, and revision rules, Drupal handles that natively. The learning curve is steeper than WordPress, but you don't hit a wall when requirements grow. Drupal is the right choice for enterprise content platforms, multi-site networks, and government sites.

**Laravel** is a framework, not a CMS. You build everything from scratch. You control the architecture, the database schema, and every line of code. Laravel is the right choice when you're building a custom application that happens to manage content—a SaaS product, an API, a marketplace. You don't get a free admin panel or content workflow system. You build those yourself.

The three platforms exist on a spectrum:

```
WordPress ───── Drupal ───── Laravel
< Less Control        More Control >
< More Built-in        Less Built-in >
```

Drupal occupies the middle ground: it gives you a complete content management platform but lets you override almost everything through hooks, events, and plugins. If you need a CMS that stays out of your way until you need to bend it, Drupal is the answer.

## The Drupal Community

Drupal has one of the most active open-source communities in PHP. The project runs on a meritocratic governance model with elected leadership, formal contribution tracks, and global events.

**Key resources:**

- **drupal.org** — The project home. Module and theme repositories, issue queues, documentation.
- **Drupal Slack** — Real-time discussion in dozens of channels. The `#beginners` channel is friendly and active.
- **Drupal Answers (Stack Exchange)** — Q&A site for technical Drupal questions. High signal-to-noise ratio.
- **Local Drupal groups** — Meetups in most major cities. Many host contribution sprints where you can learn by fixing real issues.
- **DrupalCon** — The annual conference rotates between North America, Europe, and Asia. Sessions cover development, site building, DevOps, and business strategy.

**Contributing to Drupal:**

Drupal's contribution workflow is well-documented. You can contribute code, documentation, translations, or testing. The issue queue at drupal.org uses a structured process: file an issue, get feedback, write a patch, upload it for review, and iterate. Core committers review and commit patches that meet the project's standards.

The community also maintains the Drupal Certified Partner program and the Drupal Association, a non-profit that funds infrastructure and events.

## What's Next

You now have a functioning Drupal 9 installation. The next steps depend on what you're building:

- **Content architecture** — Define content types, fields, and taxonomies that model your domain.
- **Theme development** — Drupal 9 uses Twig for templates. You can create a custom theme by extending a base theme like Classy or Bootstrap.
- **Module development** — Drupal modules are PHP classes using Symfony-like annotations and plugins. Write a custom module when you need business logic that doesn't exist in contrib.
- **Headless Drupal** — Enable JSON:API and build a decoupled frontend with the framework of your choice.

In the next article in this column, we'll build a custom content type, add fields, configure display modes, and set up a view to list and filter content. You'll learn how Drupal's entity system works and why it's the foundation of everything the platform does.

Until then, explore the admin interface. Click around. Break something—you're running locally, and you can always run `drush sql:drop && drush site:install` to start fresh.
