---
title: "A Night With Symfony - Getting Started With the PHP Framework"
description: "Discover Symfony, the powerful PHP framework. From installation to your first controller, routes, templates, and Doctrine - everything you need for a productive Symfony experience."
pubDate: "2022-06-18 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Symfony", "PHP", "Framework", "Doctrine", "Twig", "Routing", "Web Development"]
selected: false
---

There comes a point in every PHP developer's life when vanilla PHP stops cutting it. You've built your share of include-based sites, wrestled with raw SQL, and manually sanitized every `$_GET` parameter until your eyes bled. You know there has to be a better way. Enter Symfony.

Symfony is not just another PHP framework. It's an ecosystem. A set of reusable components, a robust application structure, a massive community, and a philosophy of clean, maintainable code. It powers everything from small blogs to enterprise applications used by millions. And the best part? You can go from zero to a working application in a single night.

This guide walks you through the entire journey — installation, project structure, routing, controllers, templating with Twig, database work with Doctrine, forms, security, debugging, and what happens when you take your Symfony app to production. Grab your coffee. You're in for a productive evening.

## What Is Symfony and Why Should You Care?

Symfony is a set of PHP components and a full-stack web framework. Started by Fabien Potencier in 2005, it has grown into the most mature PHP framework available. Version 6.x brings modern PHP 8+ features — named arguments, attributes, enums, and readonly properties — directly into the framework's DNA.

Why choose Symfony over raw PHP or other frameworks?

**Reusable components.** Symfony's true strength is its decoupled components. Need an HTTP client? Use `symfony/http-client`. Need a router? Grab `symfony/routing`. These components work outside the full framework too, which is why Laravel, Drupal, phpBB, and hundreds of other projects use them internally.

**Long-term support.** Symfony follows a predictable release cycle. Each minor version gets eight months of support, and each major version gets three years. You can plan upgrades years in advance.

**Convention over configuration — but flexible.** Symfony gives you sensible defaults, but you can override almost everything. You control the structure, the services, the compilation process.

**Professional-grade debugging.** The Symfony profiler and debug toolbar are arguably the best debugging tools in PHP. Queries, memory usage, timing, logs — everything is one click away.

**Massive ecosystem.** Doctrine ORM, Twig templating, SwiftMailer (now Mailer), Security Bundle, Form Bundle, Serializer, Validator. Whatever you need, Symfony probably has a bundle for it.

## Installation: Symfony CLI vs Composer

You have two paths to create a Symfony project.

### Symfony CLI (Recommended)

The Symfony CLI gives you a local development server, automatic PHP version detection, and certificate management for HTTPS. Install it, then run:

```bash
symfony new my_project --full
```

The `--full` flag installs every first-party bundle (Doctrine, Twig, Security, Form, etc.). If you want a minimal skeleton, omit it — you'll get a microservice-friendly setup with just the routing framework.

### Composer

If you prefer Composer directly:

```bash
composer create-project symfony/skeleton my_project
cd my_project
composer require webapp
```

The `webapp` metapackage installs the same bundles as `symfony new --full`. Under the hood, Symfony CLI uses Composer anyway, so there's no functional difference.

Once installed, fire up the dev server:

```bash
cd my_project
symfony server:start
```

Visit `https://localhost:8000`. You're greeted by Symfony's welcome page. You're already running.

## Project Structure: What's All This Stuff?

Open the project in your editor. Here's what matters:

```
my_project/
├── config/
│   ├── packages/     # Bundle configuration (YAML)
│   ├── routes/       # Route definitions
│   ├── services.yaml # Service container configuration
│   └── bundles.php   # Enabled bundles
├── migrations/       # Doctrine migration files
├── public/
│   └── index.php     # Application entry point (front controller)
├── src/
│   ├── Command/      # Console commands
│   ├── Controller/   # HTTP controllers
│   ├── Entity/       # Doctrine entity classes
│   ├── Form/         # Form type classes
│   ├── Repository/   # Doctrine repository classes
│   ├── Security/     # Authenticators, voters, user providers
│   ├── Kernel.php    # Application kernel
│   └── ...           # Any other PHP class you create
├── templates/        # Twig template files
├── translations/     # Translation files
├── var/
│   ├── cache/        # Compiled cache (never commit)
│   └── log/          # Application logs (never commit)
├── vendor/           # Composer dependencies (never commit)
├── .env              # Environment variables
└── .env.local        # Local overrides (never commit)
```

Symfony follows the PSR-4 standard. You namespace your classes under `App\`, and they live in `src/`. The framework maps everything through the service container, which is Symfony's dependency injection implementation.

## Your First Route and Controller

In Symfony 6.x, you define routes using PHP 8 attributes directly on controller methods. Create `src/Controller/HomeController.php`:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return new Response('<h1>Hello, Symfony!</h1>');
    }
}
```

Visit `/` in your browser. You should see "Hello, Symfony!". That's your first route.

The `#[Route]` attribute maps the URL path `/` to the `index()` method. The `name` parameter gives the route a unique identifier you can reference when generating URLs. The method returns a `Response` object, which is Symfony's HTTP response abstraction.

You can define more complex routes with parameters:

```php
#[Route('/blog/{slug}', name: 'app_blog_show')]
public function show(string $slug): Response
{
    return new Response(sprintf('Blog post: %s', $slug));
}
```

Symfony matches `{slug}` to the `$slug` method parameter automatically. You can add requirements, defaults, and HTTP method constraints:

```php
#[Route('/blog/{id}', name: 'app_blog_show', requirements: ['id' => '\d+'], methods: ['GET'])]
public function show(int $id): Response
{
    // ...
}
```

## Twig Templating: Stop Echoing HTML

Returning raw HTML strings from controllers gets ugly fast. Twig is Symfony's templating engine — fast, secure, and elegant.

Install it (it's included in the full distribution, but just in case):

```bash
composer require twig
```

Create `templates/home/index.html.twig`:

```twig
{% extends 'base.html.twig' %}

{% block title %}Hello, Symfony!{% endblock %}

{% block body %}
    <h1>Hello, Symfony!</h1>
    <p>Welcome to your new application.</p>
{% endblock %}
```

Update your controller to render the template:

```php
#[Route('/', name: 'app_home')]
public function index(): Response
{
    return $this->render('home/index.html.twig');
}
```

The `base.html.twig` file ships with Symfony and includes HTML boilerplate, CSS, and JS blocks. You can customize it freely.

### Twig Essentials

Variables are rendered with double curly braces:

```twig
<h1>{{ title }}</h1>
```

Control structures use `{% %}`:

```twig
{% if user.isLoggedIn %}
    <p>Welcome back, {{ user.name }}!</p>
{% else %}
    <p>Please log in.</p>
{% endif %}

{% for item in items %}
    <li>{{ item.name }}</li>
{% endfor %}
```

Filters modify values:

```twig
{{ 'hello'|upper }}                {# HELLO #}
{{ '2022-06-18'|date('Y-m-d') }}   {# 2022-06-18 #}
{{ 'some text'|slice(0, 4) }}      {# some #}
```

Template inheritance is Twig's killer feature. Define blocks in a base template and override them in child templates. No more duplicating HTML.

## Doctrine ORM: Databases Without Pain

Doctrine is the default ORM for Symfony. It maps PHP objects to database tables and handles queries, relationships, migrations, and schema management.

### Configuration

Edit `.env` to set your database connection:

```
DATABASE_URL="mysql://root:password@127.0.0.1:3306/my_project?serverVersion=8.0"
```

Or for SQLite (perfect for development):

```
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

Create the database:

```bash
php bin/console doctrine:database:create
```

### Creating Entities

Entities are plain PHP classes that represent database tables. Create `src/Entity/BlogPost.php`:

```php
<?php

namespace App\Entity;

use App\Repository\BlogPostRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlogPostRepository::class)]
class BlogPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $publishedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }
}
```

Attributes like `#[ORM\Entity]`, `#[ORM\Column]`, and `#[ORM\Id]` tell Doctrine how to map this class to a database table.

### Migrations

Generate a migration from your entity:

```bash
php bin/console make:migration
```

This creates a file in `migrations/` with the SQL needed to synchronize the database. Review it, then run it:

```bash
php bin/console doctrine:migrations:migrate
```

Migrations are version-controlled SQL scripts. Your whole team applies them to stay in sync. No more manually diffing databases.

### Using Repositories

Doctrine generates repository classes for each entity. Inject them into your controllers or services:

```php
use App\Repository\BlogPostRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog', name: 'app_blog_index')]
public function index(BlogPostRepository $repo): Response
{
    $posts = $repo->findBy([], ['publishedAt' => 'DESC'], 10);

    return $this->render('blog/index.html.twig', [
        'posts' => $posts,
    ]);
}
```

The `findBy()` method accepts criteria, ordering, and limit. You can also define custom query methods in your repository class using DQL or raw SQL.

## Symfony Forms: Build, Validate, Render

Forms in Symfony handle rendering, validation, CSRF protection, and data binding. Create a form class for your entity:

```bash
php bin/console make:form BlogPostType
```

This generates `src/Form/BlogPostType.php`:

```php
<?php

namespace App\Form;

use App\Entity\BlogPost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlogPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('content', TextareaType::class)
            ->add('save', SubmitType::class, ['label' => 'Create Post'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogPost::class,
        ]);
    }
}
```

Use the form in a controller:

```php
use App\Entity\BlogPost;
use App\Form\BlogPostType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

#[Route('/blog/new', name: 'app_blog_new')]
public function new(Request $request, ManagerRegistry $doctrine): Response
{
    $post = new BlogPost();
    $post->setPublishedAt(new \DateTimeImmutable());

    $form = $this->createForm(BlogPostType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager = $doctrine->getManager();
        $entityManager->persist($post);
        $entityManager->flush();

        return $this->redirectToRoute('app_blog_index');
    }

    return $this->render('blog/new.html.twig', [
        'form' => $form->createView(),
    ]);
}
```

Twig renders the form in one line:

```twig
{{ form_start(form) }}
    {{ form_widget(form) }}
    {{ form_row(form.save) }}
{{ form_end(form) }}
```

Symfony handles CSRF tokens automatically. Validation constraints come from the Validator component — you can add `#[Assert\NotBlank]`, `#[Assert\Length]`, and other attributes to your entity properties, and Symfony enforces them on submission.

## Symfony Security: Authentication and Authorization

Security is where Symfony truly shines. The Security bundle handles authentication (who are you?) and authorization (what can you do?).

### User Entity

Start with the Maker bundle:

```bash
php bin/console make:user
```

This creates a `User` entity with email, password, and roles fields, plus a `UserRepository`. Then make an authentication system:

```bash
php bin/console make:auth
```

Choose "Login form authenticator" and let Maker generate the login template, authenticator class, and route configuration.

### Security Configuration

Here's a typical `config/packages/security.yaml`:

```yaml
security:
    enable_authenticator_manager: true

    password_hashers:
        App\Entity\User: 'auto'

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        main:
            lazy: true
            provider: app_user_provider
            custom_authenticator: App\Security\LoginFormAuthenticator
            logout:
                path: app_logout
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800
                path: /
                always_remember_me: true

    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/profile, roles: ROLE_USER }
```

Symfony hashes passwords automatically using the configured algorithm. The `access_control` section restricts routes by role. Users without the required role get redirected to the login page or receive a 403 response.

### Authorization in Controllers

```php
#[Route('/admin/dashboard', name: 'app_admin')]
public function dashboard(): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    return $this->render('admin/dashboard.html.twig');
}
```

You can also check permissions in Twig:

```twig
{% if is_granted('ROLE_ADMIN') %}
    <a href="/admin">Admin Panel</a>
{% endif %}
```

Voters let you define custom authorization logic. Need to check if a user owns a specific blog post? Create a voter. It's clean, testable, and decoupled.

## Debug Toolbar and Profiler

Symfony's debug toolbar is the first thing you notice in development mode. It appears at the bottom of every page and shows:

- Request and response details
- Route name and parameters
- Controller method called
- Twig template rendering time
- Database queries (with EXPLAIN capability)
- Cache operations
- Log messages
- Memory usage
- HTTP status

Click the token hash (a long hexadecimal string in the toolbar) to open the Symfony Profiler — a full-page debug interface with deep dives into every aspect of the request.

These tools are **disabled in production** automatically. You don't need to configure anything. The `APP_ENV=dev` environment variable controls the behavior.

Pro tip: When a page is slow, open the profiler and check the "Doctrine" section. N+1 queries are the most common performance killer, and the profiler makes them trivially easy to spot.

## Symfony Flex and Bundles

Symfony Flex is a Composer plugin that automates bundle configuration. When you run `composer require something`, Flex:

1. Downloads the package.
2. Executes a "recipe" that adds configuration files to `config/packages/`.
3. Enables the bundle in `config/bundles.php`.
4. Creates default template files, routes, or environment variables as needed.

This means you almost never write configuration from scratch. Want an API platform?

```bash
composer require api
```

Flex sets up API Platform, configures routing, creates a default entity, and adds documentation endpoints. Want a mailer?

```bash
composer require symfony/mailer
```

Flex adds the Mailer configuration, creates a `.env` entry for the DSN, and you're ready to send emails.

Bundles are Symfony's plugin system. Some are first-party (Twig, Doctrine, Security, Mailer, Messenger, Serializer). Many are third-party (EasyAdmin for admin panels, Sonata for CMS features, VichUploader for file uploads). Each bundle adds services to the container, which you can configure and use immediately.

## From "A Night With Symfony" to Production

You've built your application locally. Now it needs to run in the real world.

### Environment Configuration

Symfony uses `.env` files for configuration. The `.env` file contains default values and is committed to version control. Create `.env.local` with overrides — database passwords, API keys, secrets — and never commit it.

In production, set `APP_ENV=prod` and `APP_DEBUG=0`. This disables the profiler, compiles the service container into optimized PHP, dumps Twig templates to plain PHP files, and enables OPcache integration.

### Cache Warmup

Before deploying, run:

```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

The warmup compiles the service container, generates proxy classes, and dumps Twig templates. Your application starts fast and stays fast.

### Web Server

Symfony's recommended production setup uses PHP-FPM behind Nginx. The `public/` directory is the document root. The `index.php` front controller handles all requests via the framework's routing.

Here's a minimal Nginx configuration:

```nginx
server {
    listen 80;
    server_name myapp.com;
    root /var/www/myapp/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Deployment Automation

Deploy Symfony with Deployer, Ansistrano, or GitHub Actions. Typical steps:

1. Push code to the server.
2. Install Composer dependencies (`--no-dev --optimize-autoloader`).
3. Run database migrations.
4. Clear and warmup the cache.
5. Install assets (`php bin/console assets:install`).
6. Restart PHP-FPM.

## Common Beginner Questions

### Is Symfony hard to learn?

Symfony has a steeper learning curve than Laravel, but the concepts transfer to any modern framework. Dependency injection, ORM, templating, middleware — these are universal. Once you understand Symfony, you understand how most professional PHP applications work.

### Do I need to learn YAML?

YAML is Symfony's default configuration format, but you can use PHP or XML instead. Most developers stick with YAML because it's readable and concise. You'll pick it up in an hour.

### Can I use Symfony for an API-only backend?

Absolutely. Skip the Twig and Form bundles. Use the Serializer to return JSON. Add API Platform for a full REST or GraphQL layer. Symfony's `--skeleton` install gives you a minimal setup perfect for APIs.

### What about performance?

Symfony is fast in production. The compiled service container, autoloading optimization, and OPcache make it competitive with any framework. Most performance issues come from database queries, not the framework itself. The profiler helps you find and fix those problems.

### Is Symfony good for beginners?

Yes, but with guidance. Follow the official Symfonycasts tutorials, read the documentation, and build something real. A blog, a todo app, a URL shortener. The Maker bundle (`php bin/console make:*`) generates boilerplate so you focus on the logic, not the ceremony.

### How does Symfony compare to Laravel?

Laravel emphasizes developer experience with elegant syntax and batteries-included defaults. Symfony emphasizes flexibility, reusability, and long-term maintainability. Laravel is opinionated; Symfony is configurable. Both are excellent. Choose Symfony if you want granular control and components you can use outside the framework. Choose Laravel if you want rapid prototyping and an all-in-one ecosystem.

## The Night Is Just the Beginning

A single night with Symfony is enough to build a working application. Routes respond, templates render, databases persist, and users authenticate. But the real power of Symfony reveals itself over time — when you need to add a message queue, integrate a third-party API, build an admin panel, or scale to thousands of requests per second.

Symfony grows with you. What you build tonight can run in production tomorrow, scale for years, and remain maintainable throughout. That's the promise of a framework built on solid engineering principles.

Now stop reading and start coding. Your Symfony application is waiting.

---

### Resources

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [SymfonyCasts Tutorials](https://symfonycasts.com/)
- [Symfony Flex Recipes](https://github.com/symfony/recipes)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html)
- [Twig Documentation](https://twig.symfony.com/doc/3.x/)
