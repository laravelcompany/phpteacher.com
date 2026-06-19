---
title: "Insecure Direct Object References - Prevention in PHP Applications"
description: "Prevent Insecure Direct Object References (IDOR) in PHP applications. Learn authorization strategies and input validation techniques to close security gaps."
category: "Security"
banner: "/logo.svg"
pubDate: "2022-11-18 21:00:00"
tags: ["IDOR", "Security", "PHP", "OWASP", "Authorization"]
---

Insecure Direct Object References (IDOR) made the OWASP Top Ten for good reason. It's the vulnerability where an application exposes a direct reference to an internal object — like a database ID — and the attacker can manipulate that reference to access unauthorized data.

The fix sounds simple: "check authorization." But the subtle ways IDOR creeps into code require constant vigilance.

## The Vulnerable Pattern

Consider a Laravel API with account management. The routes expose account IDs directly in the URL:

```php
use App\Http\Controllers\AccountController;

Route::controller(AccountController::class)->group(function () {
    Route::post('/account', 'create');
    Route::get('/account/{id}', 'read');
    Route::patch('/account/{id}', 'update');
    Route::delete('/account/{id}', 'delete');
});
```

The controller handles each operation naively:

```php
namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function create(Request $request)
    {
        $account = new Account;
        // ... populate the model
        $account->save();
        return $account;
    }

    public function read(int $id)
    {
        return Account::findOrFail($id);
    }

    public function update(Request $request, int $id)
    {
        $account = Account::findOrFail($id);
        // ... populate changes
        $account->save();
        return $account;
    }

    public function delete(int $id)
    {
        return Account::findOrFail($id)->delete();
    }
}
```

There's no authentication at all. Adding the `auth` middleware partially helps:

```php
Route::get('/account/{id}', 'read')->middleware('auth');
```

But authenticated users can now read *any* account — not just their own. Integer IDs make enumeration trivial. An attacker modifies the `id` parameter, and the API happily returns other users' data.

## Fixing IDOR in Laravel

### Authorization Gates

Define Gates that compare the authenticated user's ID with the target account ID:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    $this->registerPolicies();

    Gate::define('read-account', function (User $user, int $accountId) {
        return $user->id === $accountId;
    });

    Gate::define('update-account', function (User $user, int $accountId) {
        return $user->id === $accountId;
    });

    Gate::define('delete-account', function (User $user, int $accountId) {
        return $user->id === $accountId;
    });
}
```

Apply the gates in the controller:

```php
namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function read(Request $request, int $id)
    {
        if (! Gate::allows('read-account', $id)) {
            abort(403);
        }

        return Account::findOrFail($id);
    }
}
```

A 403 response tells an attacker the account exists. A 404 tells them it doesn't. Gate-based checks can leak existence information. Consider whether this matters for your threat model.

### Session-Based Authorization

If Gates aren't available, compare against the session directly:

```php
namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function read(Request $request, int $id)
    {
        $userId = $request->session()->get('user_id');

        if ($userId !== $id) {
            abort(403);
        }

        return Account::findOrFail($id);
    }
}
```

## Using Indirect References

Instead of exposing auto-increment IDs, use indirect references:

```php
// UUID approach
Route::get('/account/{uuid}', 'read');

public function read(string $uuid)
{
    $account = Account::where('uuid', $uuid)->firstOrFail();

    if (! Gate::allows('read-account', $account->id)) {
        abort(403);
    }

    return $account;
}
```

UUIDs prevent enumeration. An attacker can't guess valid UUIDs the way they can iterate `1, 2, 3, ...`. But UUIDs don't replace authorization — you still need the Gate check.

Hashids or encrypted IDs on the client side provide another layer of indirection, but they should never replace server-side authorization. Any client can modify parameters.

## Testing for IDOR

Write tests that verify authorization boundaries:

```php
public function test_user_cannot_read_another_users_account(): void
{
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $account = Account::factory()->for($user2)->create();

    $this->actingAs($user1)
        ->getJson("/account/{$account->id}")
        ->assertForbidden();
}

public function test_user_can_read_own_account(): void
{
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson("/account/{$account->id}")
        ->assertOk();
}
```

Test every CRUD operation with unauthorized users. Batch operations are especially dangerous — an endpoint that accepts `{ "ids": [1, 2, 3] }` must check each ID individually.

## Beyond Account IDs

IDOR isn't limited to user accounts. Check every reference:

- **File paths**: `GET /download?file=report-123.pdf` — can user access report-456?
- **Order IDs**: `POST /orders/987/cancel` — does this order belong to the authenticated user?
- **Document references**: `GET /api/documents/abc-123` — is the user in the document's access control list?
- **Foreign keys**: `POST /api/comments` with `post_id` — does the user have access to that post?

## OWASP Guidance

The OWASP Top 10 classifies IDOR under "Broken Access Control" (A01). Key recommendations:

- Use access control lists or role-based access control for every object reference
- Avoid exposing internal identifiers where possible
- Use indirect reference maps for sensitive objects
- Validate authorization on every request that references a protected object
- Test authorization logic programmatically

## The Mindset Shift

Security isn't a feature — it's a property of your system. The difference between secure and insecure code is how often you pause to consider threats. Bake threat modeling into code review. Question every route parameter and every query parameter. Ask: "What if an attacker modifies this value?"

IDOR is easy to introduce and hard to catch in manual testing because the application behaves correctly for legitimate users. Automated tests that specifically violate authorization are your best defense.
