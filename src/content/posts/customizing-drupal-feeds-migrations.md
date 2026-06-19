---
title: "Customizing Drupal Feeds For Smooth Migrations"
seoTitle: "Customizing Drupal Feeds Module for Smooth Data Migrations"
description: "Learn how to customize the Drupal Feeds module for seamless data migrations. Step-by-step guide to importing and transforming content."
category: "Drupal"
banner: "/logo.svg"
pubDate: "2022-07-12 21:00:00"
tags: ["Drupal", "Drupal Feeds", "Migrations", "PHP", "Data Import", "Content Migration"]
---

The Drupal Feeds module has long been a go-to solution for importing content. With its elegant UI and robust functionality, it handles everything from periodic RSS feeds to one-time data migrations. But every migration project eventually hits edge cases where the built-in options aren't enough. That's where custom Feeds Tamper plugins come in.

This guide walks through building a real-world custom tamper plugin to solve a migration challenge: converting URL aliases into entity references during a Drupal 7 to Drupal 9 migration.

## The Challenge

Picture this: you're migrating an Article content type from Drupal 7 to Drupal 9. There's a field called Internal Link that should reference another piece of content. In Drupal 9, you want this to be an entity reference field. The problem? In Drupal 7, this field was a plain link field containing only the aliased URL—no node ID, no title, nothing but a path like `/blog/my-article`.

The path aliases were preserved in Drupal 9, but entity references need target IDs, not URLs. How do you bridge that gap?

## Why Feeds Tamper?

The Feeds Tamper module offers dozens of built-in data manipulation options:

- Converting dates to timestamps and vice versa
- Making fields required to skip empty items
- Setting default values
- Exploding and imploding strings
- Array filtering and deduplication
- Math operations and number formatting
- String case changes, trimming, padding, truncating
- Find and replace (string or regex)
- Keyword filtering
- Encoding and decoding (URL, HTML entities, JSON, serialization)
- Country name to ISO code conversion

None of these solve the problem of looking up a node ID from a URL alias. You need a custom tamper plugin.

## Creating the Tamper Plugin

Building a custom Feeds Tamper plugin requires three things:

### 1. Create a Custom Module

Create a module folder, say `/modules/custom/my_tamper`, with an info file:

```yaml
name: 'My Tamper'
type: module
description: 'Provides custom feeds tamper plugins'
core_version_requirement: ^9.3 || ^10
package: 'Custom'
```

### 2. Create the Plugin Class

Create a class extending `TamperBase` in `src/Plugin/Tamper/UrlToArticleRef.php`:

```php
<?php

namespace Drupal\my_tamper\Plugin\Tamper;

use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for converting URL into Article field_id.
 *
 * @Tamper(
 *   id = "url_to_article_ref",
 *   label = @Translation("Convert URL into Article field_id"),
 *   description = @Translation("Convert URL into Article field_id"),
 *   category = "Text"
 * )
 */
class UrlToArticleRef extends TamperBase {}
```

### 3. Add the Plugin Annotation

The annotation tells Drupal's plugin system about your tamper. Once the module is enabled (via the Extend UI or `drush pm-enable my_tamper`), the new tamper appears in the list of available plugins for any feed type field mapping.

## Injecting Services with Dependency Injection

Your tamper needs the `path_alias.manager` service to convert URLs to internal paths. Rather than reaching for `\Drupal::service()` (which works but makes testing harder), use proper dependency injection.

Implement `ContainerFactoryPluginInterface` and inject the service:

```php
<?php

namespace Drupal\my_tamper\Plugin\Tamper;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\tamper\SourceDefinitionInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Tamper(
 *   id = "url_to_article_ref",
 *   label = @Translation("Convert URL into Article field_id"),
 *   description = @Translation("Convert URL into Article field_id"),
 *   category = "Text"
 * )
 */
class UrlToArticleRef extends TamperBase implements ContainerFactoryPluginInterface
{
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        SourceDefinitionInterface $source_definition,
        private AliasManagerInterface $path_alias_manager,
    ) {
        parent::__construct(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $source_definition
        );
    }

    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition,
    ): static {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $configuration['source_definition'],
            $container->get('path_alias.manager')
        );
    }

    public function tamper($data, ?TamperableItemInterface $item = null)
    {
        // Remove query parameters
        $data = strtok($data, '?');

        $path = $this->path_alias_manager
            ->getPathByAlias($data);

        if (preg_match('/node\/(\d+)/', $path, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
```

### PHP 8 Constructor Property Promotion

Notice the `private AliasManagerInterface $path_alias_manager` in the constructor. This is PHP 8.1's constructor property promotion—it simultaneously declares the property, accepts the constructor parameter, and assigns it. No separate property declaration or assignment needed. This is the kind of modern PHP syntax that makes Drupal module development cleaner.

## How the Tamper Works

The `tamper()` method receives the incoming data and returns the transformed value:

1. **Clean the URL**: `strtok($data, '?')` strips any query parameters that might prevent alias matching
2. **Resolve the alias**: `getPathByAlias($data)` converts `/blog/my-article` to `/node/42`
3. **Extract the node ID**: `preg_match('/node\/(\d+)/', ...)` captures the numeric ID
4. **Return the ID**: The entity reference field gets the target node ID

If the URL can't be resolved, it returns an empty string. Since Internal Link is a required field, pair this tamper with the built-in "Required" tamper to skip importing items that can't find their target.

## Reusability Is the Real Win

The power of custom tamper plugins comes from reusability. If you used URL-based link fields in one Drupal 7 content type, you probably used them in others. Now you have a single tamper plugin that handles this conversion across all your imports. Build up a toolkit of custom tampers over time, and your future migration projects will move dramatically faster.

## Beyond URL Conversion

The same pattern works for any complex data transformation during imports:

- Look up taxonomy terms by name and return TIDs
- Convert legacy date formats to ISO 8601
- Fetch remote content and extract specific values
- Cross-reference data between multiple CSV columns
- Call external APIs to enrich imported content

## Migration Strategies with Feeds

While Feeds excels at straightforward imports, consider these strategies for smooth migrations:

**Test with a subset**. Before running a full import, create a feed type, map a few fields, and test with 10-20 records. Verify every field landed where expected.

**Use the "Skip" behaviors**. The Feeds Tamper module lets you skip items that don't meet criteria (required fields, regex matches, etc.). This prevents partial imports that leave your content in an inconsistent state.

**Run imports in batches**. For large datasets (10,000+ items), split the import into manageable chunks. Feeds handles this natively with its import page, letting you resume from where you left off.

**Leverage Drush**. For headless imports during maintenance windows, use Drush commands. `drush feeds:import` gives you full control without the browser.

**Validate post-import**. After the import completes, run checks: does every imported node have all required fields? Are entity references pointing to valid targets? A validation step catches issues early.

## When to Use Migrate Instead

Feeds handles straightforward migrations beautifully, but there are cases where Drupal's Migrate module (with Migrate Plus and Migrate Tools) is the better choice:

- Complex architecture with Paragraphs or nested entity references
- Multi-step transformations that span multiple sources
- Rollback support during development
- Deep integration with Drupal's configuration management
- High-performance imports with progress tracking

The Migrate ecosystem is a Swiss Army knife for data imports. Feeds is the power drill with an intuitive trigger. Pick the right tool for the job.

## Advanced Tamper Configuration

For more complex tampers, you may need user-configurable options. Implementing `buildConfigurationForm()`, `submitConfigurationForm()`, and `defaultConfiguration()` gives your tamper a settings UI:

```php
<?php

namespace Drupal\my_tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;

class ConfigurableUrlConverter extends UrlToArticleRef
{
    public function defaultConfiguration(): array
    {
        return [
            'fallback_to_empty' => true,
            'strict_matching' => false,
        ];
    }

    public function buildConfigurationForm(
        array $form,
        FormStateInterface $form_state
    ): array {
        $form['fallback_to_empty'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Return empty string on failure'),
            '#default_value' => $this->getConfiguration('fallback_to_empty'),
        ];

        $form['strict_matching'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Strict alias matching (no fallback)'),
            '#default_value' => $this->getConfiguration('strict_matching'),
        ];

        return $form;
    }

    public function submitConfigurationForm(
        array &$form,
        FormStateInterface $form_state
    ): void {
        $this->setConfiguration([
            'fallback_to_empty' => $form_state->getValue('fallback_to_empty'),
            'strict_matching' => $form_state->getValue('strict_matching'),
        ]);
    }
}
```

Now your tamper appears in the UI with checkboxes. Different feed types can use the same tamper with different settings. This pattern scales: you can build a configurable "Lookup entity reference by field value" tamper that handles any entity type and any matching field, all configured through the UI.

## Debugging Tamper Plugins

When a tamper doesn't produce the expected output, you need visibility into what's happening during import. These debugging approaches save hours of frustration:

**Log the incoming data and result**:

```php
public function tamper($data, ?TamperableItemInterface $item = null)
{
    $result = // ... transformation logic

    \Drupal::logger('my_tamper')->debug(
        'Tamper input: @input, output: @output',
        ['@input' => print_r($data, true), '@output' => print_r($result, true)]
    );

    return $result;
}
```

**Use Drush to test a single import**:

```bash
drush feeds:import --file=/path/to/test.csv --limit=1
```

This imports a single item, making it easy to check the tamper output without processing thousands of records.

**Create a unit test for your tamper**:

```php
<?php

namespace Drupal\Tests\my_tamper\Unit\Plugin\Tamper;

use Drupal\my_tamper\Plugin\Tamper\UrlToArticleRef;
use Drupal\Tests\UnitTestCase;

class UrlToArticleRefTest extends UnitTestCase
{
    public function testExtractsNodeIdFromAlias(): void
    {
        $aliasManager = $this->createMock('Drupal\path_alias\AliasManagerInterface');
        $aliasManager->method('getPathByAlias')
            ->with('/blog/my-article')
            ->willReturn('/node/42');

        $tamper = new UrlToArticleRef(
            [], 'url_to_article_ref', [],
            $this->createMock('Drupal\tamper\SourceDefinitionInterface'),
            $aliasManager
        );

        $this->assertEquals('42', $tamper->tamper('/blog/my-article'));
    }

    public function testReturnsEmptyForUnmatchedAlias(): void
    {
        $aliasManager = $this->createMock('Drupal\path_alias\AliasManagerInterface');
        $aliasManager->method('getPathByAlias')
            ->willReturn('/blog/unknown');

        $tamper = new UrlToArticleRef(
            [], 'url_to_article_ref', [],
            $this->createMock('Drupal\tamper\SourceDefinitionInterface'),
            $aliasManager
        );

        $this->assertEquals('', $tamper->tamper('/blog/unknown'));
    }
}
```

## Handling Large Imports

When importing tens of thousands of items, memory and timeout limits become relevant. Feeds handles this by importing in batches, but your tamper plugin should also be efficient:

**Avoid Doctrine entity lookups in loops**. Each `\Drupal::entityTypeManager()->getStorage('node')->load()` call is a database query. If your tamper needs to look up entities, batch the IDs and load them all at once if possible.

**Use the Feeds progress API**. The `Feeds\StateInterface` passed to import methods lets you report progress and check if the import should stop:

```php
public function tamper($data, ?TamperableItemInterface $item = null)
{
    $state = $item?->getSourceState();
    if ($state && $state->progress !== 1.0) {
        // Limit the number of API calls during import
        static $apiCalls = 0;
        if (++$apiCalls > 100) {
            // Use cached data instead
        }
    }

    // ... transformation
}
```

**Cache expensive lookups**. If your tamper calls an external API or performs a complex database query, cache the results within a single import batch:

```php
private array $cache = [];

public function tamper($data, ?TamperableItemInterface $item = null)
{
    $key = md5($data);

    if (!isset($this->cache[$key])) {
        $this->cache[$key] = $this->expensiveLookup($data);
    }

    return $this->cache[$key];
}
```

## Conclusion

Custom Feeds Tamper plugins bridge the gap between what Feeds can do out of the box and what your data actually needs. The pattern is straightforward: create a module, extend `TamperBase`, implement `ContainerFactoryPluginInterface` for clean dependency injection, and override `tamper()` with your transformation logic. Use PHP 8.1's constructor property promotion to keep the code clean.

Building a reusable toolkit of tamper plugins transforms Drupal migrations from painful one-off projects into repeatable, predictable processes. Your future self—and your clients—will thank you.

The next time you face a migration challenge with non-standard data—URLs that need resolving, taxonomies that need cross-referencing, or legacy formats that need converting—remember the pattern: create a custom module, extend `TamperBase`, inject what you need via the container, and override `tamper()`. The investment of an hour today saves days of manual data cleanup tomorrow.
