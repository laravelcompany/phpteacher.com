---
title: "Headless CMS Possibilities for PHP Developers"
description: "Explore headless CMS options for PHP developers—traditional vs decoupled, Drupal headless mode, API-first platforms, and building heads with PHP backends."
pubDate: "2023-02-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Headless CMS", "PHP", "Decoupled Architecture", "Drupal", "API Platform", "JAMstack", "Content Management", "Headless WordPress"]
selected: false
---

The term "headless" has swept through the content management industry. A headless CMS is a content repository with an API interface but no built-in presentation layer. Instead of rendering HTML, it delivers structured content via REST or GraphQL to any client — a website, a mobile app, a digital kiosk, or a smartwatch.

For PHP developers, the headless revolution presents both opportunity and complexity. PHP powers the majority of traditional CMS platforms — WordPress, Drupal, Joomla — but the headless shift has largely been driven by JavaScript frontend frameworks and static site generators. Where does PHP fit in this new landscape?

The answer: everywhere. PHP developers can build headless backends, consume headless APIs, create "head" layers for content delivery, and extend traditional CMS platforms to serve as headless content repositories. This article explores the full spectrum of headless CMS possibilities from a PHP perspective.

## What You'll Learn

- The difference between traditional, hybrid, and headless CMS architectures
- How PHP-based CMS platforms serve as headless backends
- PHP SDKs for popular headless CMS services
- Pure PHP headless solutions like Cockpit and API Platform
- Building the "head" layer with PHP backends
- Pros and cons of headless approaches for PHP teams
- Real-world architecture decisions for decoupled content management

## Headless vs Traditional vs Hybrid

### Traditional Monolithic CMS

A traditional CMS like WordPress or Drupal in its default configuration handles both content management and presentation. The CMS processes a request, queries the database, renders HTML through templates, and returns a complete web page. Everything is tightly coupled.

This approach excels for simple websites. Installation is straightforward. Themes control appearance. Plugins add functionality. A content editor logs in, writes content, and publishes. No additional development required.

### Headless CMS

A headless CMS removes the presentation layer entirely. It provides an API for content creation and retrieval, but the content is delivered as raw data — typically JSON. A separate frontend application fetches this data and handles rendering.

This decoupling offers flexibility. The same content can serve a website, a mobile app, and a digital signage system. Frontend developers can use any technology stack. Performance can be optimized independently on each layer.

### Hybrid / DXP

Digital Experience Platforms (DXPs) attempt to bridge both worlds. A DXP like Acquia (Drupal's commercial offering) provides traditional CMS capabilities alongside an API layer. This hybrid approach lets teams start with a standard website and add headless delivery channels over time.

Gartner now has a DXP Magic Quadrant instead of a CMS Magic Quadrant. This reflects the market reality that content management is no longer just about web pages — it is about orchestrating digital experiences across every touchpoint.

## PHP-Based CMS Platforms in Headless Mode

### Drupal

Drupal has been a headless pioneer among PHP CMS platforms. Since Drupal 8, the core system includes a RESTful API and JSON API module. Drupal 9 and 10 continue this trajectory with improved API-first capabilities.

```php
<?php

// Fetching content from Drupal via JSON API
$client = new \GuzzleHttp\Client([
    'base_uri' => 'https://example.com',
    'headers' => ['Accept' => 'application/vnd.api+json'],
]);

$response = $client->get('/jsonapi/node/article', [
    'query' => [
        'filter[status]' => 1,
        'sort' => '-created',
        'page[limit]' => 10,
        'include' => 'field_image,uid',
    ],
]);

$articles = json_decode($response->getBody(), true);

foreach ($articles['data'] as $article) {
    echo $article['attributes']['title'] . PHP_EOL;
    echo $article['attributes']['body']['processed'] . PHP_EOL;
}
```

Drupal's JSON API module follows the JSON API specification, making it predictable and toolable. You can generate client SDKs from the specification or consume it directly with any HTTP client.

### WordPress

WordPress entered the headless space with the WordPress REST API, introduced in WordPress 4.7. The API exposes posts, pages, custom post types, taxonomies, users, and settings as RESTful endpoints.

```php
<?php

// Fetching WordPress posts via REST API
$response = wp_remote_get('https://example.com/wp-json/wp/v2/posts', [
    'headers' => ['Accept' => 'application/json'],
]);

if (is_wp_error($response)) {
    throw new \RuntimeException($response->get_error_message());
}

$posts = json_decode(wp_remote_retrieve_body($response), true);

foreach ($posts as $post) {
    printf(
        '<h2>%s</h2><p>%s</p>',
        esc_html($post['title']['rendered']),
        esc_html(wp_trim_words($post['excerpt']['rendered'], 30))
    );
}
```

The Rooftop CMS project offers an API-first WordPress distribution that strips away the traditional theme layer and optimizes WordPress specifically for headless use. This is useful when you need WordPress's familiar admin interface but want a modern frontend.

### Other PHP Options

**Ibexa** (formerly eZ Platform) provides a full content management system with REST API support. **Pimcore** combines CMS, DAM, and PIM capabilities with a flexible API layer. **Sulu** offers three distinct modes for headless operation. **Craft CMS** includes a configuration option that switches the entire CMS to headless mode. **Statamic** (flat-file CMS) provides a REST API and GraphQL support out of the box.

## Pure PHP Headless Solutions

### Cockpit CMS

Cockpit is a lightweight, open-source headless CMS built from the ground up for API-first content management. It does not have the legacy of a traditional CMS. It is pure headless.

```php
<?php

// Fetching content from Cockpit CMS via its REST API
$response = Http::withHeaders([
    'api-key' => config('services.cockpit.api_key'),
])->get('https://cockpit.example.com/api/collections/get/pages', [
    'filter' => ['published' => true],
    'limit' => 20,
    'sort' => ['created' => -1],
]);

$pages = $response->json();

foreach ($pages['entries'] as $page) {
    echo $page['title'] . PHP_EOL;
    echo markdown_to_html($page['content']) . PHP_EOL;
}
```

Cockpit is ideal for smaller projects where a full Drupal or WordPress installation would be overkill. It has a simple admin panel, flexible content modeling, and a straightforward API.

### API Platform

API Platform is not exactly a CMS, but it is a powerful framework for building API-first web applications. It generates a complete API layer from your PHP data model, including OpenAPI documentation, admin forms, and frontend client code.

```php
<?php

// src/Entity/Article.php
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['article:read']],
    denormalizationContext: ['groups' => ['article:write']],
)]
#[ORM\Entity]
class Article
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['article:read', 'article:write'])]
    public string $title;

    #[ORM\Column(type: 'text')]
    #[Groups(['article:read', 'article:write'])]
    public string $content;

    #[ORM\Column]
    #[Groups(['article:read'])]
    public \DateTimeImmutable $createdAt;
}
```

API Platform automatically generates REST and GraphQL endpoints, filters, pagination, and OpenAPI documentation. For PHP teams building custom content management solutions, it provides a robust API foundation without reinventing the wheel.

## PHP SDKs for SaaS Headless CMS

Many SaaS headless CMS platforms provide PHP SDKs. This is an alphabetical list of notable options:

- **ButterCMS** — PHP client library for straightforward blog and content management
- **Contentful** — Robust PHP SDK with rich query capabilities
- **Contentstack** — PHP SDK with asset management and localization support
- **Kontent by Kentico** — PHP SDK for their headless CMS platform
- **Prismic** — PHP client with GraphQL and REST support
- **Sanity** — Official PHP client for Sanity's structured content platform
- **Storyblok** — PHP client library with component-based content modeling

All these SDKs work similarly: configure a client with an API key or token, then fetch content by type or identifier.

```php
<?php

// Contentful PHP SDK example
$client = new \Contentful\Delivery\Client(
    $_ENV['CONTENTFUL_ACCESS_TOKEN'],
    $_ENV['CONTENTFUL_SPACE_ID']
);

$query = new \Contentful\Delivery\Query();
$query->setContentType('blogPost')
      ->where('fields.published', true)
      ->orderBy('fields.publishDate', Query::ORDER_DESC)
      ->setLimit(10);

$entries = $client->getEntries($query);

foreach ($entries as $entry) {
    echo $entry->getTitle() . PHP_EOL;
    echo $entry->getBody() . PHP_EOL;
}
```

If a platform lacks a PHP SDK, check whether it provides an OpenAPI specification. Tools like JanePHP can auto-generate PHP client code from OpenAPI specs, saving significant development time.

## Building the Head with PHP

When you use a headless CMS, you still need a "head" — the presentation layer that fetches content and renders it for end users. While JavaScript frameworks like Next.js and Gatsby dominate this space, PHP has options.

### Netgen Layouts

Netgen Layouts is a Symfony-based solution for managing the frontend layer decoupled from the content repository. It provides a visual interface for non-technical users to manage page layouts, block placements, and content variations.

Netgen Layouts integrates with Contentful and other headless backends while running on a PHP stack. This allows PHP teams to maintain control of the presentation layer without switching to JavaScript.

### Symfony + Twig

A headless frontend can be a Symfony application that fetches content via API at request time or build time. Twig templates render the content. Caching layers like Varnish or Symfony's HTTP cache handle performance.

```php
<?php

// src/Controller/PageController.php
class PageController extends AbstractController
{
    public function __construct(
        private ContentfulClient $contentful,
        private CacheInterface $cache,
    ) {}

    public function show(string $slug): Response
    {
        $entry = $this->cache->get("page.{$slug}", function () use ($slug) {
            return $this->contentful->getEntryBySlug($slug, 'page');
        });

        if (!$entry) {
            throw $this->createNotFoundException('Page not found');
        }

        return $this->render('page/show.html.twig', [
            'page' => $entry,
        ]);
    }
}
```

This approach keeps the full power of the Symfony ecosystem — forms, security, caching, and CLI commands — while using a headless CMS as the content source.

## Pros and Cons of Going Headless

### Advantages

**Multi-channel delivery.** Content is created once and published everywhere. The same API endpoint serves your website, iOS app, and smartwatch interface. This is the COPE principle — Create Once, Publish Everywhere.

**Technology flexibility.** The frontend team can use React, Vue, or plain HTML without backend constraints. The backend team can choose the best CMS for content management needs.

**Performance optimization.** The frontend can be fully static with CDN caching. The backend API can be optimized separately. Each layer scales independently.

**Better developer experience.** Frontend developers work with structured JSON instead of opaque template tags. Backend developers focus on content modeling and API design.

### Disadvantages

**Missing core CMS features.** Traditional CMS platforms provide search, media management, workflow, versioning, scheduling, taxonomy management, and edit conflict resolution out of the box. Headless systems often lack these features or require additional services.

**You must build the head.** Every common website feature — navigation menus, breadcrumbs, search results, pagination, sitemaps, RSS feeds — must be implemented from scratch or integrated via third-party services.

**Content preview is harder.** In a traditional CMS, previewing unpublished content is straightforward. In a headless setup, the preview must be built into the frontend application.

**Operational complexity.** Running a separate frontend application alongside the backend increases deployment and infrastructure complexity.

## Real-World Use Cases

### Multi-Brand Enterprise Portal

A large organization manages content for five distinct brands. Each brand needs a website, a mobile app, and a digital signage system. A single headless Drupal instance manages all content. Each brand's frontend — built with different technologies — consumes the same API.

### E-commerce with CMS Integration

A PHP e-commerce platform uses Sylius with API Platform. Product descriptions, blog posts, and landing pages are managed through a headless CMS. The PHP backend fetches CMS content via API and merges it with product data at render time.

### SaaS Documentation Platform

A SaaS company maintains documentation across multiple product versions and languages. Content is managed in a headless CMS. A Laravel application fetches the documentation, applies version-specific transformations, and caches the output. Search is powered by Algolia, indexed from the CMS content.

## Best Practices

### Model Content Strategically

Invest time in content modeling before building. A well-designed content model is the foundation of a successful headless project. Each content type should represent a distinct concept with clear relationships.

### Plan for Missing Features

Before choosing a headless CMS, audit the features you need: search, media management, workflow, versioning, localization. Map each feature to the CMS or a companion service. Do not assume the CMS provides everything.

### Build a Preview Layer

Content editors need to preview unpublished content. Build a preview mechanism into your frontend application, using draft API endpoints or query parameters.

### Use API Versioning

Your frontend and backend will evolve independently. Version your API from day one to prevent breaking changes from disrupting existing consumers.

### Cache Aggressively

Headless CMS APIs are not optimized for high-traffic delivery. Implement aggressive caching at every layer — CDN, reverse proxy, application cache, and in-memory cache.

## Common Mistakes to Avoid

**Choosing headless when you only need a website.** A traditional CMS is faster and cheaper for a single website. Only go headless when you have multiple delivery channels.

**Ignoring content editor experience.** The best API in the world is useless if editors cannot write and publish content efficiently. Evaluate the content editing interface as carefully as you evaluate the API.

**Building a custom CMS.** Unless your content management requirements are truly unique, use an existing headless CMS. Building and maintaining a custom CMS is expensive.

**Assuming headless means faster.** A poorly optimized frontend can be slower than a traditional CMS. Performance depends on implementation, not architecture.

## Frequently Asked Questions

### When should I choose headless over traditional CMS?

Choose headless when you need to deliver content to multiple channels (web, mobile, IoT), when your frontend team needs technology flexibility, or when your content editors and developers need to work independently.

### Can WordPress work as a headless CMS?

Yes. The WordPress REST API provides full headless capabilities. The Rooftop CMS distribution optimizes WordPress specifically for headless use. Many production sites use WordPress as a headless backend with React or Next.js frontends.

### What is the best PHP headless CMS?

The answer depends on your needs. Drupal is strongest for complex content models and enterprise features. Cockpit is simplest for small projects. API Platform is best for custom API-first applications. Evaluate each against your specific requirements.

### Do I need GraphQL or REST for my headless CMS?

REST is simpler and more widely supported. GraphQL offers flexibility for complex queries and reduces over-fetching. Many platforms support both. Start with REST and add GraphQL if needed.

### How do I handle authentication for a headless CMS API?

Most SaaS headless CMS platforms use API tokens or keys. For self-hosted solutions, implement JWT-based authentication or OAuth2. Always use HTTPS. Consider API gateways for rate limiting and access control.

### Can I use Laravel as a headless CMS?

Laravel is not a CMS, but you can build a custom headless CMS on Laravel. Use Laravel Nova for the admin interface, and packages like Laravel REST API or Lighthouse PHP for the API layer.

## Conclusion

Headless CMS technology opens new possibilities for PHP developers. You can use traditional PHP CMS platforms in headless mode, consume SaaS headless CMS APIs, build custom headless backends with PHP frameworks, or create the "head" layer with PHP frontends.

The right approach depends on your team's skills, your content requirements, and your delivery channels. A traditional CMS is still the best choice for simple websites. A hybrid DXP serves mid-range complexity. A pure headless approach shines when content must reach multiple platforms.

PHP is not left behind in the headless revolution. It is a first-class participant. Evaluate the options, match them to your use case, and build the decoupled content system your project deserves.

**Ready to explore headless with PHP?** Start by installing Cockpit CMS or setting up Drupal in headless mode. Create a simple content model, then build a PHP frontend that consumes the API. Experience the decoupled architecture firsthand.
