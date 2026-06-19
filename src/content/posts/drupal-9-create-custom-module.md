---
title: "Create a Custom Module in Drupal 9"
seoTitle: "Creating a Custom Module in Drupal 9 - Step-by-Step Guide"
description: "Step-by-step guide to creating a custom module in Drupal 9. Learn module structure, hooks, routing, and best practices for Drupal development."
category: "Drupal"
banner: "/logo.svg"
pubDate: "2022-07-20 21:00:00"
tags: ["Drupal", "Drupal 9", "Custom Module", "PHP", "Development"]
---

Drupal's module system is one of its greatest strengths. Whether you need a simple content type, custom business logic, or integration with an external API, creating a custom module gives you full control. This guide walks through building a complete Drupal 9 module from scratch: a "Health Sites" module that verifies whether hostnames and URLs are up or down.

We'll cover everything from the basic `.info.yml` file through routes, controllers, forms, hooks, and cron jobs.

## Module Structure

Every Drupal module starts with a folder in the right place. Create `web/modules/custom/health_sites/` with this structure:

```
health_sites/
├── health_sites.info.yml
├── health_sites.module
├── health_sites.routing.yml
├── health_sites.permissions.yml
├── health_sites.links.menu.yml
├── health_sites.links.task.yml
├── health_sites.libraries.yml
├── css/
│   └── health_sites.css
├── templates/
│   └── health-sites-main-page.html.twig
└── src/
    ├── Controller/
    │   └── HealthSitesController.php
    └── Form/
        ├── HealthSitesConfigureForm.php
        ├── HealthSitesConfigureUrlsForm.php
        └── HealthSitesVerifyHealthForm.php
```

## The .info.yml File

This is the first file you create. It tells Drupal about your module:

```yaml
name: 'Health Sites'
description: 'Verify whether hostnames/URLs are up or down'
type: module
core_version_requirement: ^9.3 || ^10
package: 'Custom modules'
```

The `core_version_requirement` field checks compatibility between the module and Drupal core. Using `^9.3 || ^10` means it works with Drupal 9.3+ and Drupal 10.x.

Enable the module:

```bash
drush pm:enable health_sites
```

## The Routing System

Drupal 8+ routing is fundamentally different from Drupal 7's `hook_menu`. Routes, permissions, menu links, and local tasks each live in their own YAML file.

### health_sites.routing.yml

```yaml
health_sites.main:
  path: '/admin/health_sites'
  defaults:
    _controller: '\Drupal\health_sites\Controller\HealthSitesController::index'
    _title: 'Health Sites'
  requirements:
    _permission: 'health sites configure'

health_sites.configure:
  path: '/admin/health_sites/configure'
  defaults:
    _form: '\Drupal\health_sites\Form\HealthSitesConfigureForm'
    _title: 'Health Sites configure'
  requirements:
    _permission: 'health sites configure'

health_sites.configure_urls:
  path: '/admin/health_sites/configure-urls'
  defaults:
    _form: '\Drupal\health_sites\Form\HealthSitesConfigureUrlsForm'
    _title: 'Health Sites - URLs configure'
  requirements:
    _permission: 'health sites configure'

health_sites.verify:
  path: '/admin/health_sites/verify-health'
  defaults:
    _form: '\Drupal\health_sites\Form\HealthSitesVerifyHealthForm'
    _title: 'Health Sites - Verify URLs'
  requirements:
    _permission: 'health sites verify urls'
```

The route name (e.g., `health_sites.main`) is the key used throughout Drupal code. If the URL path changes, you only update this file—all references use the route name.

### health_sites.permissions.yml

```yaml
health sites configure:
  title: 'Configure health sites'
  description: 'Configure hostnames and URLs to verify'
  restrict access: true

health sites verify urls:
  title: 'Verify health of hostnames/urls'
  description: 'Check if URLs are UP or DOWN'
  restrict access: true
```

`restrict access: true` displays a warning on the permissions page, alerting administrators that granting this permission carries security implications.

### health_sites.links.menu.yml

```yaml
health_sites.main:
  title: 'Health Sites'
  description: 'Health Sites'
  parent: 'system.admin'
  route_name: health_sites.main

health_sites.configure_sites:
  title: 'Configure Health Sites list'
  description: 'Configure Health Sites list'
  parent: 'health_sites.main'
  route_name: health_sites.configure

health_sites.configure_urls:
  title: 'Health Sites - URLs configure'
  description: 'Configure URLs'
  parent: 'health_sites.main'
  route_name: health_sites.configure_urls

health_sites.verify_sites:
  title: 'Verify Health Sites'
  description: 'Verify Health Sites'
  parent: 'health_sites.main'
  route_name: health_sites.verify
```

The `parent` key creates the menu hierarchy. Setting `health_sites.main`'s parent to `system.admin` places it under the main Administration menu.

### health_sites.links.task.yml

Local tabs appear as secondary navigation under the parent route:

```yaml
health_sites.configure_sites:
  title: 'Configure Health Sites list'
  route_name: health_sites.configure
  base_route: health_sites.configure

health_sites.configure_urls:
  title: 'Health Sites - URLs configure'
  route_name: health_sites.configure_urls
  base_route: health_sites.configure

health_sites.verify_sites:
  title: 'Verify Health Sites'
  route_name: health_sites.verify
  base_route: health_sites.configure
```

## The Controller

Controllers render pages. Here's `HealthSitesController.php`:

```php
<?php

namespace Drupal\health_sites\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class HealthSitesController extends ControllerBase
{
    public function index(string $module_name, Request $request): array
    {
        $routingFilePath = DRUPAL_ROOT . '/' .
            drupal_get_path('module', $module_name) .
            '/health_sites.routing.yml';

        $routingFileContents = file_get_contents($routingFilePath);
        $results = Yaml::decode($routingFileContents);

        $links = [];
        foreach ($results as $key => $item) {
            if ($key !== 'health_sites.main') {
                $links[$item['path']] = $item['defaults']['_title'];
            }
        }

        return [
            '#theme' => 'health_sites_main_page',
            '#title' => $this->t('List of configure pages'),
            '#links' => $links,
        ];
    }
}
```

The method returns a render array referencing a Twig template. Drupal converts this into HTML.

## Hooks in .module

### hook_help

```php
<?php

use Drupal\Core\Routing\RouteMatchInterface;

function health_sites_help($route_name, RouteMatchInterface $route_match): string
{
    switch ($route_name) {
        case 'help.page.health_sites':
            $output = '';
            $output .= '<h3>' . t('About') . '</h3>';
            $output .= '<p>' . t('This module verifies the health of hostnames and URLs.') . '</p>';
            return $output;
    }
}
```

### hook_cron

The cron hook performs periodic health checks:

```php
function health_sites_cron(): void
{
    $config = \Drupal::config('health_sites.settings');
    $hostnames = $config->get('hostnames_to_verify');
    $urls_to_verify = $config->get('urls_to_verify');

    $urls = [];
    foreach ($hostnames as $key => $hostname) {
        if (!isset($urls_to_verify[$key])) {
            $urls[] = $hostname;
        } else {
            foreach ($urls_to_verify[$key] as $url) {
                $hostname = str_starts_with($hostname, 'http')
                    ? $hostname
                    : 'http://' . $hostname;
                $urls[$hostname][] = $hostname . $url;
            }
        }
    }

    foreach ($urls as $hostname => $list_urls) {
        foreach ($list_urls as $url) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Store or update the health check result
            $query = \Drupal::entityQuery('node')
                ->condition('type', 'history_url')
                ->condition('field_hostname', $hostname)
                ->condition('field_url', str_replace($hostname, '', $url));
            $nid = $query->execute();

            if (empty($nid)) {
                $node = \Drupal::entityTypeManager()
                    ->getStorage('node')
                    ->create([
                        'type' => 'history_url',
                        'title' => 'Status of ' . $url,
                        'field_hostname' => $hostname,
                        'field_url' => str_replace($hostname, '', $url),
                        'langcode' => 'en',
                        'status' => 1,
                    ]);
            } else {
                $nid = array_pop($nid);
                $node = Node::load($nid);
                $node->setNewRevision();
                $node->revision_log = 'Auto-generated revision';
                $node->setRevisionCreationTime(time());
            }

            $node->set('field_status_up', $httpcode === 200 ? 1 : 0);
            $node->save();
        }
    }
}
```

### hook_theme

This hook registers a Twig template for the controller:

```php
function health_sites_theme(array $existing, string $type, string $theme, string $path): array
{
    return [
        'health_sites_main_page' => [
            'variables' => ['title' => null, 'links' => null],
            'template' => 'health-sites-main-page',
        ],
    ];
}
```

## Building Forms

### Configuration Form

```php
<?php

namespace Drupal\health_sites\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class HealthSitesConfigureForm extends FormBase
{
    public function getFormId(): string
    {
        return 'health_sites_configure_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = \Drupal::config('health_sites.settings');
        $hostnames = $config->get('hostnames_to_verify') ?? [];
        $hostnames_string = implode("\n", $hostnames);

        $form['hostnames'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Hostnames to verify'),
            '#default_value' => $hostnames_string,
            '#description' => $this->t('Enter one hostname per line.'),
            '#required' => true,
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
        ];

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $hostnames = explode("\n", $form_state->getValue('hostnames'));
        $hostnames = array_map('trim', $hostnames);
        $hostnames = array_filter($hostnames);

        $config = \Drupal::service('config.factory')
            ->getEditable('health_sites.settings');
        $config->set('hostnames_to_verify', $hostnames)->save();

        $this->messenger()->addMessage($this->t('Configuration saved.'));
    }
}
```

### AJAX-Based URL Configuration

The URLs form uses Drupal's AJAX framework to dynamically show URLs based on the selected hostname:

```php
$form['hostname'] = [
    '#type' => 'select',
    '#title' => $this->t('Hostname'),
    '#options' => $hostnames,
    '#ajax' => [
        'callback' => '::getUrlsAjaxCallback',
        'event' => 'change',
        'wrapper' => 'wrapper-urls',
        'progress' => ['type' => 'throbber'],
    ],
];

$form['urls'] = [
    '#type' => 'textarea',
    '#title' => $this->t('URLs'),
    '#prefix' => '<div id="wrapper-urls">',
    '#suffix' => '</div>',
];
```

When the user selects a hostname, the AJAX callback populates the URLs field with previously saved values.

## The Twig Template

The `hook_theme` implementation registered a template file. Here's `health-sites-main-page.html.twig`:

```twig
{% extends 'page.html.twig' %}

{% block content %}
    <h2>{{ title }}</h2>
    <ul>
    {% for path, label in links %}
        <li><a href="{{ path }}">{{ label }}</a></li>
    {% endfor %}
    </ul>
{% endblock %}
```

Templates automatically receive the variables defined in `hook_theme`. The `#title` and `#links` values from the controller's render array become `{{ title }}` and `{{ links }}` in the template. Drupal's theme system handles caching, asset aggregation, and rendering automatically.

## Adding JavaScript and CSS

To include custom CSS or JavaScript, define libraries in `health_sites.libraries.yml`:

```yaml
health_sites:
  version: 1.x
  css:
    layout:
      css/health_sites.css: {}
  js:
    js/health_sites.js: {}
  dependencies:
    - core/jquery
    - core/drupal
```

Attach the library to a specific page by adding it to the render array:

```php
$build['#attached']['library'][] = 'health_sites/health_sites';
```

Or attach it in a hook:

```php
function health_sites_page_attachments(array &$attachments): void
{
    if (\Drupal::routeMatch()->getRouteName() === 'health_sites.verify') {
        $attachments['#attached']['library'][] = 'health_sites/health_sites';
    }
}
```

This replaces Drupal 7's `drupal_add_js()` and `drupal_add_css()` functions, which were removed in Drupal 8+.

## Services and Dependency Injection

Drupal 9's module system embraces Symfony's service container. Instead of calling static methods like `\Drupal::entityTypeManager()`, you inject services via the container:

```php
<?php

namespace Drupal\health_sites\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class HealthCheckService
{
    public function __construct(
        private readonly ConfigFactoryInterface $configFactory,
        private readonly EntityTypeManagerInterface $entityTypeManager,
    ) {}

    public function checkAllUrls(): array
    {
        $config = $this->configFactory->get('health_sites.settings');
        $hostnames = $config->get('hostnames_to_verify') ?? [];
        $results = [];

        foreach ($hostnames as $hostname) {
            $results[$hostname] = $this->checkUrl($hostname);
        }

        return $results;
    }

    private function checkUrl(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 400;
    }
}
```

Register the service in `health_sites.services.yml`:

```yaml
services:
  health_sites.health_check:
    class: Drupal\health_sites\Service\HealthCheckService
    arguments:
      - '@config.factory'
      - '@entity_type.manager'
```

Now any controller, form, or hook can access the service via dependency injection, making your code testable and decoupled from Drupal's global state.

## Testing Custom Modules

Drupal 9 provides a robust testing framework based on PHPUnit. Create unit tests in `tests/src/Unit/`:

```php
<?php

namespace Drupal\Tests\health_sites\Unit\Service;

use Drupal\health_sites\Service\HealthCheckService;
use Drupal\Tests\UnitTestCase;

class HealthCheckServiceTest extends UnitTestCase
{
    public function test_service_creation(): void
    {
        $configFactory = $this->createMock('Drupal\Core\Config\ConfigFactoryInterface');
        $entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');

        $service = new HealthCheckService($configFactory, $entityTypeManager);
        $this->assertInstanceOf(HealthCheckService::class, $service);
    }
}
```

For integration tests that test against a real Drupal installation, use `KernelTestBase`:

```php
<?php

namespace Drupal\Tests\health_sites\Kernel;

use Drupal\KernelTests\KernelTestBase;

class ModuleInstallationTest extends KernelTestBase
{
    protected static $modules = ['health_sites'];

    public function test_module_installs(): void
    {
        $this->assertTrue(\Drupal::moduleHandler()->moduleExists('health_sites'));
    }
}
```

Run tests with:

```bash
phpunit web/modules/custom/health_sites/tests
```

## Drupal 10 Compatibility

The module structure explained here works identically in Drupal 10. The core_version_requirement `^9.3 || ^10` covers both major versions. A few things changed in Drupal 10 that affect custom modules:

- **CKEditor 5 replaces CKEditor 4** — If your module provides WYSIWYG plugins, update them for CKEditor 5
- **Symfony 6** — Drupal 10 ships with Symfony 6 components, which may require updates to custom service tags
- **PHP 8.1 minimum** — You can use PHP 8.1 features like enums, readonly properties, and intersection types without worrying about backward compatibility with older PHP versions
- **Removed deprecated code** — Functions and methods deprecated in Drupal 9 are removed in Drupal 10

## Conclusion

Drupal's module system has evolved significantly. YAML configuration files replace many of Drupal 7's hooks (like `hook_permission` becoming `permissions.yml`). Services and dependency injection replace static function calls. The Twig templating engine separates logic from presentation.

Building a custom module in Drupal 9 follows these key patterns:

1. Declare your module in `.info.yml`
2. Define routes in `.routing.yml`
3. Register permissions in `.permissions.yml`
4. Create menu links and local tasks in their respective YAML files
5. Implement hooks in `.module`
6. Build controllers for pages
7. Create forms for data input
8. Use libraries for CSS/JS

These same patterns apply to Drupal 10 and beyond. The foundations are solid, the API is stable, and the module ecosystem continues to grow.

Building a custom module is the gateway to mastering Drupal development. Start with a simple module like Health Sites, add features incrementally, and soon you'll be building integrations, custom content entities, and complex business logic—all within Drupal's clean, modern, object-oriented module system.
