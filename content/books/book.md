---
title: Book Review - Allen A. Consuming APIs in Laravel
publishDate: 2025-02-04 00:00:00
description: From Code Review to Async and Beyond, PHP developers can continue to improve their skills and build more effective and resilient applications.
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - february
  - 2025
---


**Consuming APIs in Laravel: A Comprehensive Guide**

This article will explore how to effectively consume APIs within your Laravel applications, covering essential aspects such as different types of APIs, authentication, code techniques, and the use of the Saloon package. We'll also delve into OAuth for secure authorization and how to handle webhooks for real-time updates.

**Understanding APIs**

An API (Application Programming Interface) allows different software applications to communicate with each other.  APIs come in various forms, including:

*   **REST APIs:** These are the most common type of API you'll encounter when building Laravel applications. They use HTTP methods (GET, POST, PUT, PATCH, DELETE) to perform CRUD operations on resources, often using JSON for data transfer.
*  **GraphQL APIs**: These APIs are product-centric, and the API consumers specify the data needed instead of the server determining what data an API endpoint returns.
*   **RPC APIs:** (Remote Procedure Call) APIs, another type of API you may come across.
*   **SOAP APIs:** (Simple Object Access Protocol) APIs are also mentioned, though the book focuses more on REST APIs.

**Benefits of Using APIs**

Using APIs in your applications can provide several benefits:
*   **Access to third-party services:** Leverage functionality from platforms like Stripe for payments, Mailgun for emails, or Twilio for SMS.
*   **Efficiency:** Integrate existing services instead of building everything from scratch.
*   **Maintainability:**  Abstracting API interactions into separate classes makes your code cleaner and more maintainable.

**Authentication**

Authentication is crucial for securing API interactions. Common methods include:

*   **API Keys:**  These are often used to identify your application. They should be stored as environment variables, not hardcoded in your code.
    ```php
    // Example of using an API key from environment variables
    $mailgunApiKey = config('services.mailgun.key');
    ```
*   **OAuth 2.0:** A framework for secure delegated access, allowing users to authorize applications to access their data without sharing their credentials. We'll discuss this further below.
*   **Passing Credentials in the Authorization Header:** It is recommended to pass the Client ID and Client Secret in the Authorization header as a base64 encoded string instead of in the body.

**Code Techniques for Better API Integrations**

The source emphasizes several code techniques to improve maintainability, testability and extensibility:

*   **Strict Type Checking:** Use `declare(strict_types=1)` to enable strict type checking in your PHP files.
   ```php
   <?php
    declare(strict_types=1);

    namespace App\Services;
    class ExampleService {
    // ...
    }
   ```
*   **Final Classes:** Use the `final` keyword to prevent inheritance, ensuring that your classes cannot be extended.
    ```php
    final class GitHubService {
     //...
    }
    ```
*   **Readonly Classes and Properties:**  Use the `readonly` keyword to make classes and properties immutable after instantiation.
    ```php
    final readonly class Repo {
        public function __construct(
            public readonly int $id,
            public readonly string $name,
            // ...
        ) {}
    }
    ```
*  **Data Transfer Objects (DTOs):** Use DTOs to structure data passed between parts of your application. This helps reduce errors related to referencing keys that do not exist and makes the code more readable.
    ```php
    final readonly class NewRepoData {
        public function __construct(
            public string $name,
            public string $description,
            public bool $isPrivate,
        ) {}
    }
    ```
*   **Composition Over Inheritance:** Prefer composing classes from smaller, focused classes rather than using deep inheritance hierarchies. Create smaller classes for focused use cases.
    ```php
    class UsageService {
        public function recordAction(ActionDetails $actionDetails): array {
        // Record action to database...
        }
    }

    class GitHubService {
        public function __construct(private UsageService $usageService) {}
        public function fixRepoStyling(string $repoName): void {
             $this->usageService->recordAction(
            // ...
        );
        }
    }
    ```
*   **Interfaces and the Service Container:** Use interfaces to define contracts for your classes and use Laravel's service container for dependency injection to decouple the code from the implementation.
    ```php
     interface GitHubServiceInterface {
        public function getRepo(string $owner, string $repo): array;
        public function getRepos(string $owner): array;
    }

    class GitHubService implements GitHubServiceInterface {
      // ...
    }

    // Service provider binding
    $this->app->bind(
        GitHubServiceInterface::class,
        GitHubService::class
    );

    // Using it in a controller
    public function index(GitHubServiceInterface $gitHubService) {
        //...
    }
    ```
*   **Enums:** Use enums to ensure that only valid values are passed to methods and to represent columns in your database as casts in your Eloquent Models.
    ```php
    enum RepoType: string {
       case ALL = 'all';
       case OWNER = 'owner';
       case PUBLIC = 'public';
       case PRIVATE = 'private';
       case MEMBER = 'member';
    }
    ```

**Using Saloon for API Consumption**

Saloon is a PHP library that simplifies API consumption in Laravel. Key features of Saloon include:

*   **Object-Oriented Approach:**  Encourages building API integrations using classes ("connectors" and "requests").
*   **Testing Tools:** Makes it easy to mock API responses and write tests.
*   **Laravel Integration:** Provides Artisan commands for creating connectors and request classes, a facade for testing, and uses Laravel's HTTP client.

**Installation and Configuration**

1.  **Install Saloon:** Use Composer to install the core Saloon package and Laravel plugin:
    ```bash
    composer require saloonphp/saloon saloonphp/laravel-plugin
    ```
2.  **Configure HTTP Sender (Optional):** To use Laravel's HTTP client instead of Guzzle, install the `saloonphp/laravel-http-sender` package and update the `config/saloon.php` file:
    ```bash
    composer require saloonphp/laravel-http-sender
    ```
   ```php
    // config/saloon.php
    return [
      'default_sender' => \Saloon\HttpSender\HttpSender::class,
    ];
   ```
3.  **Artisan Commands:** Saloon provides Artisan commands to generate connectors, requests, responses, plugins and authenticator classes:
    ```bash
    php artisan saloon:connector GitHub GitHubConnector
    php artisan saloon:request GitHub GetAllRepos
    php artisan saloon:response GitHub GitHubResponse
     php artisan saloon:plugin GitHub GitHubPlugin
     php artisan saloon:auth GitHub GitHubAuthenticator
    ```
    
**Example: Consuming the GitHub API**

1.  **Create a Connector:**
    ```php
    // app/Http/Integrations/GitHub/GitHubConnector.php
    namespace App\Http\Integrations\GitHub;
    use Saloon\Http\Connector;
    use Saloon\Http\Auth\TokenAuthenticator;
    class GitHubConnector extends Connector
    {
       public function resolveBaseUrl(): string
        {
           return 'https://api.github.com';
        }

        protected function defaultAuth(): ?Authenticator
        {
           return new TokenAuthenticator(config('services.github.token'));
        }
    }
    ```
2.  **Create a Request:**
    ```php
    // app/Http/Integrations/GitHub/Requests/GetRepo.php
    namespace App\Http\Integrations\GitHub\Requests;
    use Saloon\Enums\Method;
    use Saloon\Http\Request;
    final class GetRepo extends Request
    {
       protected Method $method = Method::GET;

       public function __construct(
          private string $owner,
          private string $repo
       ) {}

        public function resolveEndpoint(): string
        {
           return "/repos/{$this->owner}/{$this->repo}";
        }
    }
    ```
3.  **Create a Service:**
    ```php
    // app/Services/GitHub/GitHubService.php
    namespace App\Services\GitHub;

    use App\Interfaces\GitHub;
    use App\DataTransferObjects\GitHub\Repo;
    use App\Http\Integrations\GitHub\Requests\GetRepo;
    final readonly class GitHubService implements GitHub
    {
        public function getRepo(string $owner, string $repoName): Repo
        {
          return $this->connector()
              ->send(new GetRepo($owner, $repoName))
              ->dtoOrFail();
        }

        private function connector(): GitHubConnector
        {
           return new GitHubConnector();
        }
    }

    ```
4.  **Bind the interface with implementation:**
  ```php
  // AppServiceProvider.php
     use App\Interfaces\GitHub;
     use App\Services\GitHub\GitHubService;
    // ...
      public function register(): void
        {
            $this->app->bind(
               abstract: GitHub::class,
               concrete: fn (): GitHub => new GitHubService(
               token: config('services.github.token'),
               )
            );
        }
  ```
5.  **Use in a Controller:**
  ```php
  // GitHubController.php
    use App\Interfaces\GitHub;
    // ...
    public function show(string $owner, string $name, GitHub $gitHub): View
    {
        $repo = $gitHub->getRepo(
            owner: $owner,
            repoName: $name,
        );
        return view('repos.show')->with([
            'repo' => $repo,
        ]);
    }
  ```

**OAuth 2.0 for Secure Authorization**

OAuth 2.0 allows users to authorize applications to access their data without sharing their credentials. Common flows include:
*   **Authorization Code Grant:** This is the most common flow for web applications. It involves obtaining an authorization code, then exchanging it for an access token.
    *   **Client ID and Client Secret:** These are used to identify your application and are like a username and password.
    *   **Access Token:** A token used to access the protected resources.
    *   **Refresh Token:**  Used to obtain a new access token when the current one expires.
*   **Client Credentials Grant:**  Used for machine-to-machine authentication. Only the access token is used in this flow.

**Implementing OAuth with Saloon**

1.  **Create a Connector with OAuth:**
    ```php
    // app/Http/Integrations/Spotify/SpotifyConnector.php
    namespace App\Http\Integrations\Spotify;
    use Saloon\Helpers\OAuth2\OAuthConfig;
    use Saloon\Http\Connector;
    use Saloon\Traits\OAuth2\AuthorizationCodeGrant;
    use Saloon\Traits\Plugins\AcceptsJson;

    class SpotifyConnector extends Connector
    {
        use AuthorizationCodeGrant;
        use AcceptsJson;

        public function resolveBaseUrl(): string
        {
          return 'https://api.spotify.com/v1';
        }
          protected function defaultOauthConfig(): OAuthConfig
        {
        return OAuthConfig::make()
              ->setClientId(config('services.spotify.client_id'))
              ->setClientSecret(config('services.spotify.client_secret'))
              ->setRedirectUri(config('services.spotify.redirect_uri'))
              ->setScopes(['user-top-read']);
        }
    }
    ```
2.  **Create an Authorization Redirect Request:**
    ```php
    // app/Services/Spotify/SpotifyService.php

    public function getAuthRedirectDetails(): AuthorizationRedirectDetails
    {
      $connector = new SpotifyConnector();
       $authorizationUrl = $connector->getAuthorizationUrl();

       return new AuthorizationRedirectDetails(
          authorizationUrl: $authorizationUrl,
          state: $connector->getState(),
          codeVerifier: $connector->getCodeVerifier(),
        );
    }
    ```
3.   **Handle the Authorization Callback:**
    ```php
    // app/Http/Controllers/SpotifyController.php
     public function callback(Request $request, Spotify $spotify)
    {
      $callbackDetails = new AuthorizationCallbackDetails(
        code: $request->query('code'),
        state: $request->query('state'),
      );

      $accessTokenDetails = $spotify->authorize($callbackDetails);
      auth()->user()->updateSpotifyOAuthDetails($accessTokenDetails);
      return redirect()->route('dashboard');
    }
    ```
    ```php
    // app/Services/Spotify/SpotifyService.php
      public function authorize(AuthorizationCallbackDetails $callbackDetails): AccessTokenDetails
        {
            $connector = new SpotifyConnector();
             $tokenDetails = $connector->getAccessToken(
                code: $callbackDetails->code,
                state: $callbackDetails->state
            );

        return new AccessTokenDetails(
            accessToken: $tokenDetails->accessToken,
            refreshToken: $tokenDetails->refreshToken,
            expiresAt: $tokenDetails->expiresAt,
        );

        }
    ```
4. **Create a request for access token:**
   ```php
   // app/Http/Integrations/Spotify/Requests/GetAccessTokenRequest.php
    namespace App\Http\Integrations\Spotify\Requests;
    use Saloon\Contracts\Body\HasBody;
    use Saloon\Enums\Method;
    use Saloon\Helpers\OAuth2\OAuthConfig;
    use Saloon\Http\Request;
    use Saloon\Traits\Body\HasFormBody;
    use Saloon\Traits\Plugins\AcceptsJson;

    final class GetAccessTokenRequest extends Request implements HasBody
    {
      use HasFormBody;
       use AcceptsJson;

       protected Method $method = Method::POST;

      public function __construct(
          private readonly string $code,
          private readonly OAuthConfig $oauthConfig,
      ) {
          $this->withBasicAuth(
              $oauthConfig->getClientId(), $oauthConfig->getClientSecret()
          );
      }

      public function resolveEndpoint(): string
      {
          return '/api/token';
       }

    protected function defaultConfig(): array
    {
         return [
            'headers' => [
              'Content-Type' => 'application/x-www-form-urlencoded',
            ],
          ];
    }

    public function defaultBody(): array
    {
        return [
         'grant_type' => 'authorization_code',
         'code' => $this->code,
         'redirect_uri' => $this->oauthConfig->getRedirectUri(),
          ];
      }
    }
   ```

**Webhooks for Real-Time Updates**

Webhooks allow an application to send an HTTP request to another application when an event occurs. This enables real-time updates without the need for constant polling.

*   **Security:** Webhook routes must be secured using middleware to verify the request origin and prevent malicious users from sending fake requests.
*   **Rate Limiting:** Use queues and Laravel's rate-limiting features to manage the load from incoming webhook requests.
*   **Queues:** Processing webhooks using queues allows for asynchronous processing, retries in case of failure, and smoother performance.

**Building Webhook Routes**

1.  **Create a Route:**
    ```php
    // routes/api.php
    use App\Http\Controllers\Api\Webhooks\MailgunController;
    use Illuminate\Support\Facades\Route;

    Route::post(
        '/webhooks/mailgun/{status}',
        MailgunController::class
    )->name('webhooks.mailgun');
    ```
2.  **Create a Controller:**
    ```php
    // app/Http/Controllers/Api/Webhooks/MailgunController.php
    namespace App\Http\Controllers\Api\Webhooks;

    use App\Enums\EmailStatus;
    use App\Models\EmailLog;
    use Illuminate\Http\Request;
    use Symfony\Component\HttpFoundation\Response;

    final class MailgunController
    {
       public function __invoke(Request $request, EmailStatus $status): Response
       {
         EmailLog::create([
            'status' => $status,
            'data' => $request->all(),
           ]);

           return response()->noContent();
        }
    }
    ```
3.  **Create an enum:**
    ```php
    // app/Enums/EmailStatus.php
    namespace App\Enums;
    enum EmailStatus: string {
        case Delivered = 'delivered';
        case TemporaryFail = 'temporary_fail';
        case PermanentFail = 'permanent_fail';
      }
    ```
4.  **Create a Middleware to Verify Webhook Requests:**
    ```php
    // app/Http/Middleware/VerifyMailgunWebhook.php
    namespace App\Http\Middleware;
    use Carbon\Carbon;
    use Closure;
    use Illuminate\Http\Request;
    use Symfony\Component\HttpFoundation\Response;

    final readonly class VerifyMailgunWebhook
    {
       public function handle(Request $request, Closure $next): Response
       {
         $timestamp = $request->input('timestamp');
         $token = $request->input('token');
         $signature = $request->input('signature');

            if (!$timestamp || !$token || !$signature) {
              abort(403);
            }

            if (abs(Carbon::now()->timestamp - $timestamp) > 15) {
                abort(403);
            }

            $signingKey = config('services.mailgun.signing_key');
            $expectedSignature = hash_hmac('sha256', $timestamp.$token, $signingKey);

            if (!hash_equals($expectedSignature, $signature)) {
                abort(403);
             }

        return $next($request);
        }
    }
    ```
5.  **Apply the middleware to route:**
  ```php
    use App\Http\Controllers\Api\Webhooks\MailgunController;
    use App\Http\Middleware\VerifyMailgunWebhook;
    use Illuminate\Support\Facades\Route;
    Route::post('/webhooks/mailgun/{status}', MailgunController::class)
           ->name('webhooks.mailgun')
           ->middleware(VerifyMailgunWebhook::class);
  ```
6.  **Use Queues to Process Webhooks:**
   ```php
   // app/Jobs/Webhooks/Mailgun/ProcessEmailStatusWebhook.php
    namespace App\Jobs\Webhooks\Mailgun;
    use App\Enums\EmailStatus;
    use App\Models\EmailLog;
    use Illuminate\Bus\Queueable;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Foundation\Bus\Dispatchable;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Queue\SerializesModels;
    final class ProcessEmailStatusWebhook implements ShouldQueue
    {
      use Dispatchable;
      use InteractsWithQueue;
      use Queueable;
      use SerializesModels;

        public function __construct(
          public readonly array $data,
          public readonly EmailStatus $status
        ) {}

        public function handle(): void
        {
          EmailLog::create([
              'status' => $this->status,
              'data' => $this->data,
          ]);
       }
    }
   ```
  ```php
    // app/Http/Controllers/Api/Webhooks/MailgunController.php
     namespace App\Http\Controllers\Api\Webhooks;

    use App\Enums\EmailStatus;
    use App\Jobs\Webhooks\Mailgun\ProcessEmailStatusWebhook;
    use Illuminate\Http\Request;
    use Symfony\Component\HttpFoundation\Response;

    final class MailgunController
    {
        public function __invoke(Request $request, EmailStatus $status): Response
        {
        ProcessEmailStatusWebhook::dispatch($request->all(), $status);
            return response()->noContent();
        }
    }
  ```
7.  **Add Rate limiting to queue jobs**
    ```php
      // AppServiceProvider.php
      use Illuminate\Cache\RateLimiting\Limit;
      use Illuminate\Support\Facades\RateLimiter;
    // ...
      public function boot(): void
        {
            RateLimiter::for(
               name: 'mailgun-webhooks',
               callback: static fn ($job) => Limit::perMinute(100)
            );
         }
    ```
  ```php
    // app/Jobs/Webhooks/Mailgun/ProcessEmailStatusWebhook.php
    namespace App\Jobs\Webhooks\Mailgun;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Saloon\RateLimitPlugin\Helpers\ApiRateLimited;
    // ...
      public function middleware(): array
        {
            return [new RateLimited('mailgun-webhooks')];
        }
  ```

**Testing**

*   **Mocking API Requests:** Use Saloon's mocking capabilities to test API interactions without making real requests.
    ```php
    use Saloon\Http\Faking\MockResponse;
    use Saloon\Laravel\Facades\Saloon;
    Saloon::fake([
        GetRepo::class => MockResponse::make(['single-repo-response-body-here']),
        MockResponse::make(['message' => 'Forbidden'], 403)
    ]);
    // ...
    ```
*   **Test Doubles and Fakes:** Create test doubles for interfaces to isolate parts of your code for testing and to avoid testing implementation details, creating fake implementations of interfaces.
*   **Fixtures:** Store example responses in fixture files to improve the readability of your test.

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