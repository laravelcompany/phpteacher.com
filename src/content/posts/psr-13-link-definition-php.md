---
title: "PSR-13 Link Definition - Hypermedia Links in PHP"
description: "Explore PSR-13 Link Definition for hypermedia links in PHP APIs. Implement HATEOAS and discover how link relations enable discoverable web services."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-12-26 21:00:00"
tags: ["PSR-13", "PHP", "HATEOAS", "Hypermedia", "PSR", "PHP-FIG", "API"]
---

Nearly everyone with a browser understands what a link is: something on the page you click that takes you somewhere else. Links, however, are not just for navigation. They are also references to resources that direct the browser to load assets or identify related documents. Some links are invisible to the viewer but critical for the system loading the document, like stylesheet references or RSS feed URIs.

PSR-13 defines interfaces for these links, management of link collections, and methods for serializing and providing links in a standardized way.

## The Two Types of Links

There are two basic types of links in web applications:

1. **Link tags** — `<link rel="stylesheet" href="styles.css"/>` placed in the `<head>`. These reference resources that should be loaded alongside the document.

2. **Anchor tags** — `<a href="https://example.com">Visit Example</a>`. These redirect users to another resource.

The key difference: an anchor tag redirects the user, while a link tag references a resource. A linked resource identifies an asset that should be loaded or recognized. Search engine robots use `<link rel="alternate" type="application/rss+xml" href="feed.xml"/>` to find associated RSS feeds.

## PSR-13 LinkInterface

The primary interface in PSR-13 is `Psr\Link\LinkInterface`:

```php
namespace Psr\Link;

interface LinkInterface
{
    public function getHref();
    public function isTemplated();
    public function getRels();
    public function getAttributes();
}
```

Each method serves a specific purpose:

- `getHref()` — Returns the URI of the link.
- `isTemplated()` — Boolean indicating whether the URI is templated. A templated URI looks like `https://api.example.org/users/{user_id}/orders/{order_id}`, where the variables would be interpolated at runtime.
- `getRels()` — Returns the list of resource relations that apply to the link, like `alternate`, `stylesheet`, `next`, `prev`. The full list of registered link relations is maintained by IANA.
- `getAttributes()` — Returns all attributes that should be applied to the link.

A link must consist of at minimum a URI and a relationship defining how it relates to the source.

## EvolvableLinkInterface

PSR-13 defines `EvolvableLinkInterface` for immutability, similar to PSR-7 message objects. Links are considered value objects, and any changes should produce a new instance:

```php
namespace Psr\Link;

interface EvolvableLinkInterface extends LinkInterface
{
    public function withHref($href);
    public function withRel($rel);
    public function withoutRel($rel);
    public function withAttribute($attribute, $value);
    public function withoutAttribute($attribute);
}
```

Each method must return a new instance with the modified property. The `$attribute` parameter in `withAttribute()` and `withoutAttribute()` accepts `string|Stringable|int|float|bool|array`. Multiple calls to `withAttribute()` with the same name should replace previously provided values.

## LinkProviderInterface

The provider interface returns a list of Link objects:

```php
namespace Psr\Link;

interface LinkProviderInterface
{
    public function getLinks();
    public function getLinksByRel($rel);
}
```

Both methods should return an iterable collection of Link objects, or an empty collection if no links match. `getLinksByRel()` filters by the specified relationship.

## EvolvableLinkProviderInterface

For mutable collections:

```php
namespace Psr\Link;

interface EvolvableLinkProviderInterface extends LinkProviderInterface
{
    public function withLink(LinkInterface $link);
    public function withoutLink(LinkInterface $link);
}
```

Both methods return new instances of the collection. `withLink()` adds the Link object or ensures only one instance exists if it is already present. `withoutLink()` removes it.

## Implementing PSR-13 in a REST API

A practical implementation brings hypermedia to your API responses:

```php
final class Link implements EvolvableLinkInterface
{
    private ?string $href = null;
    private array $rels = [];
    private array $attributes = [];
    private bool $templated = false;

    public function __construct(
        string $href = null,
        array $rels = []
    ) {
        if ($href !== null) {
            $this->href = $href;
            $this->templated = str_contains($href, '{');
        }
        $this->rels = $rels;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function isTemplated(): bool
    {
        return $this->templated;
    }

    public function getRels(): array
    {
        return array_keys($this->rels);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function withHref($href): static
    {
        $new = clone $this;
        $new->href = $href;
        $new->templated = str_contains($href, '{');
        return $new;
    }

    public function withRel($rel): static
    {
        $new = clone $this;
        $new->rels[$rel] = true;
        return $new;
    }

    public function withoutRel($rel): static
    {
        $new = clone $this;
        unset($new->rels[$rel]);
        return $new;
    }

    public function withAttribute($attribute, $value): static
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;
        return $new;
    }

    public function withoutAttribute($attribute): static
    {
        $new = clone $this;
        unset($new->attributes[$attribute]);
        return $new;
    }
}
```

## API Pagination with PSR-13 Links

One of the most common use cases is adding pagination links to API responses:

```php
final class PaginatedResponse
{
    /** @param Link[] $links */
    public function __construct(
        private readonly array $data,
        private readonly array $links,
    ) {
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            '_links' => array_map(
                fn (Link $link) => [
                    'href' => $link->getHref(),
                    'rel' => $link->getRels(),
                ],
                $this->links
            ),
        ];
    }
}

function createPaginatedResponse(
    array $items,
    int $page,
    int $perPage,
    int $total,
    string $baseUrl
): PaginatedResponse {
    $links = [];
    $lastPage = (int) ceil($total / $perPage);

    $links[] = (new Link(
        sprintf('%s?page=%d&per_page=%d', $baseUrl, $page, $perPage),
        ['self']
    ));

    if ($page > 1) {
        $links[] = (new Link(
            sprintf('%s?page=%d&per_page=%d', $baseUrl, 1, $perPage),
            ['first']
        ));
        $links[] = (new Link(
            sprintf('%s?page=%d&per_page=%d', $baseUrl, $page - 1, $perPage),
            ['prev']
        ));
    }

    if ($page < $lastPage) {
        $links[] = (new Link(
            sprintf('%s?page=%d&per_page=%d', $baseUrl, $page + 1, $perPage),
            ['next']
        ));
        $links[] = (new Link(
            sprintf('%s?page=%d&per_page=%d', $baseUrl, $lastPage, $perPage),
            ['last']
        ));
    }

    return new PaginatedResponse($items, $links);
}
```

The response body includes `_links` with `self`, `first`, `prev`, `next`, and `last` relations. A client can navigate the entire collection without constructing URLs manually.

## HATEOAS Resource Modeling

Hypermedia as the Engine of Application State (HATEOAS) means the API tells the client what it can do next:

```php
class UserResource
{
    public function __construct(
        private readonly array $user,
        private readonly bool $canEdit,
        private readonly bool $canDelete,
    ) {
    }

    public function toArray(): array
    {
        $links = [
            (new Link(
                sprintf('/api/users/%d', $this->user['id']),
                ['self']
            )),
            (new Link(
                '/api/users',
                ['collection']
            )),
        ];

        if ($this->canEdit) {
            $links[] = (new Link(
                sprintf('/api/users/%d', $this->user['id']),
                ['edit']
            ))->withAttribute('method', 'PUT');
        }

        if ($this->canDelete) {
            $links[] = (new Link(
                sprintf('/api/users/%d', $this->user['id']),
                ['delete']
            ))->withAttribute('method', 'DELETE');
        }

        return [
            'id' => $this->user['id'],
            'name' => $this->user['name'],
            'email' => $this->user['email'],
            '_links' => array_map(
                fn (Link $link) => [
                    'href' => $link->getHref(),
                    'rel' => $link->getRels(),
                    'attributes' => $link->getAttributes(),
                ],
                $links
            ),
        ];
    }
}
```

A `GET` request returns the user data along with available actions. The client does not need prior knowledge of the API structure. The response tells the client what it can do.

## Templated URIs

RFC 6570 defines URI templates. A templated URI contains variables that get expanded:

```php
$link = new Link('https://api.example.org/users/{user_id}', ['self']);
var_dump($link->isTemplated()); // true
```

Expansion happens on the client side:

```php
$expanded = str_replace(
    ['{user_id}'],
    ['42'],
    $link->getHref()
);
// https://api.example.org/users/42
```

## Serialization Strategies

Links can be serialized in different formats depending on the API's content type:

```php
class LinkSerializer
{
    /** @param LinkInterface[] $links */
    public function toJson(array $links): string
    {
        return json_encode(
            array_map(fn (LinkInterface $link) => [
                'href' => $link->getHref(),
                'rel' => $link->getRels(),
                'templated' => $link->isTemplated(),
                'attributes' => $link->getAttributes(),
            ], $links),
            JSON_THROW_ON_ERROR
        );
    }

    /** @param LinkInterface[] $links */
    public function toHttpHeader(array $links): string
    {
        return implode(', ', array_map(
            fn (LinkInterface $link) => sprintf(
                '<%s>; rel="%s"',
                $link->getHref(),
                implode(' ', $link->getRels())
            ),
            $links
        ));
    }
}
```

The `Link` HTTP header format:

```
Link: </api/users?page=1>; rel="first", </api/users?page=3>; rel="next"
```

## The Value of Standardized Links

PSR-13 provides a consistent, predictable way to handle links across applications and frameworks. The benefits include:

- **Screen reader support** — Standardized link structures help assistive technologies navigate content.
- **Mobile optimization** — Consistent links make responsive layouts easier to implement.
- **Error reduction** — A defined interface reduces typos and inconsistencies.
- **Framework interoperability** — Any PSR-13-compatible library can produce and consume links.

PSR-7 implementations already handle HTTP messages. PSR-18 handles HTTP clients. PSR-13 fills the gap for hypermedia links, completing the picture for RESTful API development in PHP. Whether you build a full HATEOAS API or simply need pagination links, PSR-13 provides the right abstraction.
