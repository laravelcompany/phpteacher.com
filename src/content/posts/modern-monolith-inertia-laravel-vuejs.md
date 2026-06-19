---
title: "Building Modern Monoliths With InertiaJS, Laravel, and Vue"
description: "Build modern monolithic PHP apps by unifying frontend and backend code with InertiaJS, Laravel, and Vue. Learn setup, lazy loading, testing, and best practices."
pubDate: "2023-09-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Laravel", "InertiaJS", "VueJS", "Monolith", "PHP", "Full-Stack", "SPA", "PestPHP"]
selected: false
---

The tension between single-page applications and traditional server-rendered frameworks has defined the last decade of web development. On one side, you get rich interactivity and a smooth user experience. On the other, you get the simplicity, SEO-friendliness, and maintainability of server-driven architectures.

What if you could have both without maintaining two separate codebases, two deployment pipelines, and two teams? That is exactly what InertiaJS delivers.

InertiaJS is not another JavaScript framework. It is a middleware layer that sits between your Laravel backend and your Vue (or React or Svelte) frontend, letting you build modern monolithic applications where both frontend and backend code live in the same repository, share the same routing, and communicate without a traditional JSON API.

## What You'll Learn

- How InertiaJS bridges the gap between server-rendered and client-rendered applications
- Setting up a Laravel project with InertiaJS and Vue 3 with TypeScript
- Building responsive controllers using the Inertia response factory
- Implementing lazy loading and partial reloads for performance
- Writing tests for Inertia-powered pages using PestPHP
- Real-world patterns for authentication, caching, and form handling
- Best practices for modern monolithic architecture

## The Case for the Modern Monolith

For years, the industry pushed toward microservices and decoupled frontends. The JAMstack promised better performance and developer experience. But in practice, maintaining separate frontend and backend repositories introduced overhead: synchronizing API contracts, managing CORS, duplicating validation logic, and coordinating deployments.

The modern monolith rejects the premise that you must choose between a clean architecture and a great user experience. With InertiaJS, you build your Laravel application as you normally would — controllers, routes, Eloquent models, Blade service providers — but instead of returning Blade views, your controllers return Inertia pages.

The frontend becomes a collection of Vue components that receive data as props. No API endpoints to build. No token-based authentication to manage. No CORS headaches.

### How Inertia Works Under the Hood

When a browser makes an initial request to an Inertia-powered app, Laravel renders the full page server-side, including the Vue JavaScript bundle. On subsequent navigations, Inertia intercepts clicks on `<Link>` components and makes XHR requests to the server. The server returns JSON containing the component name and props instead of a full HTML document. Inertia then swaps out the page component client-side, updates the browser history, and scrolls to the top.

This approach gives you the fast navigation of an SPA without the complexity of a separate API layer.

## Setting Up a Laravel + Inertia + Vue Project

The fastest way to bootstrap an Inertia application is using the Laravel Installer with Laravel Breeze, which provides scaffolding for authentication with Vue and TypeScript.

```bash
laravel new github-browser --git --pest
composer require --dev laravel/breeze
php artisan breeze:install vue --typescript
```

Breeze sets up the `app.blade.php` root view, the `HandlesInertiaRequests` middleware, the TypeScript configuration, and the Vue component structure under `resources/js`. You get a working authentication system with login, registration, password reset, and email verification — all driven by Inertia.

### The HandlesInertiaRequests Middleware

This middleware is the backbone of your Inertia integration. It is registered as global middleware for all web routes and is responsible for sharing global props with every page component.

```php
public function share(Request $request): array
{
    $auth = Auth::check();

    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $auth
                ? new UserResource(resource: Auth::user())
                : null,
        ],
        'projects' => $auth
            ? ProjectResource::collection(
                resource: Cache::remember(
                    key: Auth::id() . '-projects',
                    ttl: CacheTime::HOUR->value * 5,
                    callback: static fn () =>
                        Project::query()
                            ->where('user_id', Auth::id())
                            ->get(),
                )
            ) : null,
        'ziggy' => function () use ($request) {
            return array_merge(
                (new Ziggy)->toArray(),
                ['location' => $request->URL()],
            );
        },
    ]);
}
```

Notice how this checks authentication state on every request. Sessions can expire mid-session, so this conditional loading prevents errors when a user's session is invalidated between navigations. Projects are cached for five hours using a PHP 8.2 enum for TTLs, reducing database load on every page visit.

## Building Inertia Controllers

Controllers in Inertia applications look different from traditional Laravel controllers. Instead of returning `view()`, you return an Inertia response.

### Basic Controller Pattern

```php
final class IndexController
{
    use HasInertiaResponse;

    public function __invoke(Request $request): Response
    {
        return $this->response->render('PageName/Component');
    }
}
```

This uses the Inertia `ResponseFactory` directly through dependency injection rather than the `Inertia::render()` facade. The facade performs a service container lookup on every call. Injecting the factory once in the constructor is more efficient and testable.

### Passing Data

Data is passed as the second argument to `render()`:

```php
final class IndexController
{
    use HasInertiaResponse;

    public function __invoke(Request $request): Response
    {
        return $this->response->render(
            component: 'PageName/Component',
            props: [
                'articles' => Article::query()->all(),
            ],
        );
    }
}
```

Props can be evaluated eagerly (as shown above) or lazily using closures. Lazy evaluation means the data is only fetched when the prop is actually requested, which is useful for partial reloads.

```php
'articles' => fn () => Article::query()->all(),
```

### Lazy Loading with the Response Factory

The most powerful approach is to use the `lazy()` method provided by the response factory. This ensures data is never loaded on the first visit, optionally included on partial reloads, and only evaluated when needed.

```php
'articles' => $this->response->lazy(
    callback: fn () => Article::query()->all(),
),
```

This pairs beautifully with skeleton UI components on the frontend. The Vue component can trigger a partial reload on mount and swap the skeleton for real content once the data arrives.

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Inertia } from '@inertiajs/inertia'

const loaded = ref(false)

onMounted(() => {
    Inertia.reload()
    loaded.value = true
})
</script>
```

## Forms and State Management

Inertia's form handling is one of its most polished features. The `useForm` helper provides a reactive form object with built-in validation error handling and submission state tracking.

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/inertia-vue3'

const form = useForm({
    email: '',
    password: '',
})

const submit = async () => {
    form.post(route('login'), {
        preserveState: false,
        preserveScroll: true,
    })
}
</script>
```

The `preserveState` option controls whether form values persist after submission. `preserveScroll` prevents the page from scrolling to the top, which is especially useful for long forms.

For maintaining form state across navigation, Inertia provides `useRemember`:

```vue
<script setup lang="ts">
import { useRemember } from '@inertiajs/inertia-vue3'

const form = useRemember({
    title: '',
    name: '',
    email: '',
    more_fields: '',
}, 'unique-component-id')
</script>
```

This restores form state when the user navigates back via the browser's back button — a small detail that dramatically improves the user experience on complex forms.

## Testing Inertia Pages

The Inertia testing helpers make assertion a pleasure, especially when paired with PestPHP:

```php
it('renders timeline component', function (): void {
    get(
        uri: action(InvokableController::class),
    )->assertOk()
     ->assertInertia(
         fn (AssertableInertia $page) =>
             $page->component('Timeline/View')
                  ->has('posts', 15,
                      fn (AssertableInertia $page) =>
                          $page->where('topic', 'Foo Bar')
                               ->etc()
                  )
     );
});
```

This test verifies that the correct Inertia component is rendered, that the `posts` prop contains exactly fifteen items, and that each post has the expected shape. The `etc()` method allows other properties to exist without explicit assertion, keeping tests flexible.

## Real-World Use Cases

**Internal Dashboards.** Inertia combined with Filament or custom Vue components creates responsive admin panels without the overhead of a separate SPA. Teams can iterate quickly since the full stack lives in one repository.

**SaaS Applications.** Multi-tenant apps benefit from Inertia's lazy loading. Tenant-specific data can be loaded on demand, reducing initial page weight. The shared middleware pattern makes it easy to inject the current tenant into global props.

**E-Commerce Platforms.** Product listings benefit from partial reloads. Category filters, sorting, and pagination can update the product grid without a full page refresh while the server retains control over SEO-critical initial renders.

**Content Management Systems.** Inertia's remember state feature is invaluable for long-form content editors who might accidentally navigate away from an unsaved draft.

## Best Practices

**Inject the ResponseFactory, not the facade.** Relying on the container via `Inertia::render()` adds unnecessary overhead. Inject `ResponseFactory` into your controller's constructor or action method.

**Cache aggressively in the HandlesInertiaRequests middleware.** Global props like user preferences, site settings, and navigation data should be cached. Use PHP 8 enums for TTL constants to keep cache durations consistent.

**Prefer lazy evaluation for expensive queries.** Use closures or the `lazy()` method for props that involve slow database queries or external API calls. This ensures the initial page load stays fast.

**Use API Resources for prop transformation.** Eloquent API Resources provide a consistent way to shape data before sending it to the frontend. They also make it easy to include related data through resource relationships.

**Write Inertia-specific tests.** The `assertInertia` method validates component names, prop shapes, and prop counts. This gives you confidence that your backend is sending the correct data structure to your Vue components.

## Common Mistakes to Avoid

**Treating Inertia as an SPA framework.** Inertia is a monolith tool, not a frontend framework. Do not build a separate API layer alongside Inertia. If you need a public API, keep it separate from your Inertia routes.

**Over-fetching in global middleware.** Only share props that are genuinely needed on every page. Everything else should be passed per-page or loaded lazily.

**Ignoring SSR.** Inertia supports server-side rendering for Vue and React. If SEO is critical for public pages, enable SSR. The Breeze scaffolding can optionally configure this for you.

**Skipping TypeScript.** Vue components with TypeScript catch prop type mismatches at compile time. The initial setup cost is minimal compared to the debugging time it saves.

## Frequently Asked Questions

**When should I choose Inertia over Livewire?**
Inertia is ideal when you want a rich JavaScript frontend with Vue, React, or Svelte. Livewire is better for teams that want to stay entirely in PHP with minimal JavaScript. Livewire excels at reactive widgets within Blade views, while Inertia excels at page-level interactivity.

**Does Inertia work with other frameworks besides Laravel?**
Yes. Inertia has server-side adapters for Laravel, Rails, Django, Symfony, and others. The client-side adapters support Vue 2/3, React, and Svelte.

**How does authentication work with Inertia?**
Authentication works exactly as it does in a standard Laravel application. Sessions and cookies handle authentication. No tokens, no JWTs, no localStorage. Laravel Breeze and Jetstream both support Inertia scaffolding out of the box.

**Can I use Inertia with an existing Laravel application?**
Yes. Install the Inertia Laravel adapter, set up the root Blade view, and start converting your routes to return Inertia responses incrementally. You can mix Blade and Inertia pages in the same application during the transition.

**Does Inertia support file uploads?**
Yes. Inertia's form helper supports file inputs natively. Uploads are handled as multipart form data, just like standard HTML forms.

## Conclusion

InertiaJS represents a pragmatic middle ground in the endless framework wars. It gives you the fast, interactive experience users expect from modern web applications without forcing you to split your codebase into separate frontend and backend projects.

The modern monolith is not a step backward — it is a recognition that most applications do not need the complexity of microservices or decoupled frontends. By keeping your stack unified, you reduce cognitive overhead, simplify deployment, and ship features faster.

If you have been hesitant to adopt a JavaScript framework because of the maintenance burden, give Inertia a try. Start with a small internal tool or a new feature in an existing Laravel application. The developer experience is refreshingly simple, and the results speak for themselves.

Ready to modernize your monolithic applications? Grab the latest Laravel, install Breeze with Inertia and Vue, and start building.
