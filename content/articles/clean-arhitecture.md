---
title: Clean Architecture
publishDate: 2025-02-09 00:00:00
description: From Code Review to Async and Beyond, PHP developers can continue to improve their skills and build more effective and resilient applications.
image: /assets/services/security.svg
author: Bogdanel Stefan
tags:
  - article
  - php
  - february
  - 2025
---

## Understanding Clean Architecture in Laravel Framework


Awesome! Let's continue with our geeky transformation! 🚀

**Code Techniques: Level Up Your API Game! 🎮**

Let's turbocharge our code with some pro-level techniques that'll make your fellow devs go "whoaaaaa!" 

**Strict Typing: Because YOLO is Not a Type**
When PHP gives you type hints, use them! Strict typing is like putting guardrails on your code highway:

```php
<?php
declare(strict_types=1);

namespace App\Services;
class ExampleService {
// Your strictly-typed awesomeness here
}
```

**Final Classes: The Ultimate Boss Battle**
Make your classes final like a boss fight - no one gets to extend them without your permission! 🛡️

```php
final class GitHubService {
 // Once final, always final!
}
```

**Readonly Properties: Write Once, Read Forever**
Because immutability is the new black! Make your properties readonly and sleep better at night:

```php
final readonly class Repo {
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        // More readonly goodness...
    ) {}
}
```

**DTOs: Your Data's VIP Lounge 🎭**
Stop passing arrays around like it's the wild west! DTOs are like bouncers for your data:

```php
final readonly class NewRepoData {
    public function __construct(
        public string $name,
        public string $description,
        public bool $isPrivate,
    ) {}
}
```

**Composition: Like LEGO for Your Code**
Why inherit when you can compose? It's like building with LEGO blocks - each piece has its own superpower:

```php
class UsageService {
    public function recordAction(ActionDetails $actionDetails): array {
    // Recording action like a boss...
    }
}

class GitHubService {
    public function __construct(private UsageService $usageService) {}
    // The magic happens here...
}
```

**Enter Saloon: Your API Swiss Army Knife 🔧**

Saloon is like having an API butler - it handles all the boring stuff while you focus on the cool features! 

**Getting Started with Saloon**

1. First, summon Saloon to your project:
```bash
composer require saloonphp/saloon saloonphp/laravel-plugin
```

2. Want Laravel's HTTP client? Say no more:
```bash
composer require saloonphp/laravel-http-sender
```

3. Generate your API warrior classes with these magical incantations:
```bash
php artisan saloon:connector GitHub GitHubConnector
php artisan saloon:request GitHub GetAllRepos
# More artisan magic available!
```

**Real-World Battle: GitHub API Integration**

Let's build something cool with the GitHub API! Here's how the pros do it:

1. **The Connector (Your API Command Center):**
```php
// Your connector code here... [Previous code remains the same]
```

2. **The Request (Your API Messenger):**
```php
// Your request code here... [Previous code remains the same]
```

**OAuth 2.0: Because Security is Not Optional 🔒**

OAuth 2.0 is like having a bouncer for your API club. Let's set it up like a pro:

```php
// Your OAuth implementation code here... [Previous code remains the same]
```

**Webhooks: Your Real-Time Superpower ⚡**

Webhooks are like having a bat-signal for your API - when something happens, BAM! You know about it instantly.

**Setting Up Your Webhook Fortress:**

1. **Create Your Webhook Endpoint:**
```php
// Your webhook route code here... [Previous code remains the same]
```

2. **The Controller (Your Webhook Command Center):**
```php
// Your webhook controller code here... [Previous code remains the same]
```

**Testing: Because YOLO Doesn't Work in Production 🧪**

Time to make sure your code is bulletproof:

```php
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;

// Mock it like it's hot!
Saloon::fake([
    GetRepo::class => MockResponse::make(['data' => 'awesome']),
    MockResponse::make(['message' => 'Nope!'], 403)
]);
```

**The Epic Conclusion 🏆**

And there you have it, fellow code warriors! You're now armed with the knowledge to build API integrations that would make even senior devs jealous. Remember:
- Keep your code clean
- Your types strict
- Your tests thorough
- And your coffee strong! ☕

Now go forth and build something awesome! And remember, with great APIs comes great responsibility! 💪

Questions? Bugs? Feature requests? Drop them in the comments below or submit a PR! Happy coding! 🚀