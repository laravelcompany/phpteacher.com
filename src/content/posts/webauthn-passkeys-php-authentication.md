---
title: "Implement WebAuthn and Passkeys in PHP Apps"
description: "Learn to implement passwordless authentication with WebAuthn and Passkeys in PHP. Step-by-step Laravel guide using the webauthn-framework package."
pubDate: "2023-11-20 23:30:00"
category: "php"
banner: "/logo.svg"
tags: ["WebAuthn", "Passkeys", "PHP Authentication", "Passwordless", "Laravel", "FIDO2", "Security", "PHP 8.x"]
selected: false
---

Passwords are broken. Users reuse them across sites. Phishing attacks steal them. Credential stuffing exploits them. Even with multi-factor authentication, the user experience suffers and security gaps remain.

The FIDO Alliance, backed by Apple, Google, Microsoft, Amazon, and 1Password, developed a better approach: passkeys. Using public-key cryptography and the WebAuthn standard, passkeys turn your device into your password. You authenticate with your fingerprint, face scan, or device PIN — something you already do dozens of times daily.

## What You'll Learn

- How WebAuthn and passkeys work under the hood
- The cryptographic flow: registration and authentication
- Implementing WebAuthn in Laravel with the webauthn-framework
- Configuring passkey authentication middleware and pipelines
- Customizing WebAuthn responses for SPAs and Inertia apps
- Local development requirements for WebAuthn

## How WebAuthn Works

WebAuthn (Web Authentication) is a W3C standard that enables passwordless authentication using public-key cryptography. The core idea is elegantly simple: instead of storing a password hash on your server, you store a public key. The private key never leaves the user's device.

### Registration Flow

When a user registers with WebAuthn:

1. The server sends a challenge and relying party information to the browser
2. The browser passes this to the authenticator (secure hardware on the device)
3. The authenticator generates a new public/private key pair
4. The private key is stored in secure hardware (TPM, Secure Enclave, etc.)
5. The public key and attestation are returned to the server
6. The server validates and stores the public key associated with the user account

### Authentication Flow

When the user returns to log in:

1. The server sends a challenge to the browser
2. The browser asks the authenticator to sign the challenge
3. The user verifies with their biometric or PIN
4. The signed challenge is returned to the server
5. The server validates the signature using the stored public key
6. Authentication succeeds without any password exchange

### Security Properties

- **Phishing-resistant**: The authenticator validates the relying party origin. A fake website can't trick the authenticator.
- **Credential stuffing immune**: Each site generates unique key pairs. A breach on one site doesn't affect others.
- **No shared secrets**: The private key never leaves the device. There's no password hash to steal.
- **Privacy-preserving**: Biometric data is processed locally and never transmitted.

## Implementation in Laravel

Building WebAuthn from scratch requires implementing credential creation, attestation validation, assertion verification, and key management. The `webauthn-framework` PHP library handles the cryptographic complexity, and the `asbiin/laravel-webauthn` package integrates it with Laravel.

### Installation

```bash
composer require asbiin/laravel-webauthn guzzlehttp/psr7
```

Publish the configuration:

```bash
php artisan vendor:publish --provider="LaravelWebauthn\WebauthnServiceProvider"
```

Run the migration to create WebAuthn credential storage tables:

```bash
php artisan migrate
```

### Configuration

Configure the authentication driver in `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'webauthn',
        'model' => App\Models\User::class,
    ],
],
```

Add the WebAuthn middleware to `app/Http/Kernel.php`:

```php
use LaravelWebauthn\Http\Middleware\WebauthnMiddleware;

protected $routeMiddleware = [
    // ... existing middleware
    'webauthn' => WebauthnMiddleware::class,
];
```

### Protecting Routes

Apply the middleware to routes that require passkey authentication:

```php
Route::middleware(['auth', 'webauthn'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
    // ... other protected routes
});
```

### Customizing the Authentication Pipeline

Laravel WebAuthn integrates with Laravel Fortify for a customizable authentication pipeline:

```php
use LaravelWebauthn\Services\Webauthn;

Webauthn::authenticateThrough(function (Request $request) {
    return array_filter([
        config('webauthn.limiters.login') !== null
            ? null
            : EnsureLoginIsNotThrottled::class,
        AttemptToAuthenticate::class,
        PrepareAuthenticatedSession::class,
    ]);
});
```

### JavaScript Integration

WebAuthn requires browser-side JavaScript to interact with the authenticator. The `@simplewebauthn/browser` package handles this:

```javascript
import { startAuthentication } from '@simplewebauthn/browser';

async function authenticate() {
    const resp = await fetch('/webauthn/login/options', {
        method: 'POST',
    });

    const options = await resp.json();

    const authResponse = await startAuthentication({ optionsJSON: options });

    const verificationResp = await fetch('/webauthn/login/verify', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(authResponse),
    });

    const verification = await verificationResp.json();

    if (verification.verified) {
        window.location.href = '/dashboard';
    }
}
```

The JavaScript handles platform-specific authenticator interactions — Touch ID on Mac, Windows Hello on PC, fingerprint on Android — and returns the cryptographic proof to your server.

### Events

The package dispatches events at each stage of the WebAuthn flow, allowing you to hook into the process:

```php
// On successful login via WebAuthn
\LaravelWebauthn\Events\WebauthnLogin

// On preparing authentication data challenge
\LaravelWebauthn\Events\WebauthnLoginData

// On failed login check
\Illuminate\Auth\Events\Failed

// On registering a new key
\LaravelWebauthn\Events\WebauthnRegister

// On preparing register data challenge
\LaravelWebauthn\Events\WebauthnRegisterData

// On failing to register a new key
\LaravelWebauthn\Events\WebauthnRegisterFailed
```

### Customizing Responses

For SPAs or Inertia.js applications, override the default response classes:

```php
use LaravelWebauthn\Services\Webauthn;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Webauthn::loginViewResponseUsing(
            LoginViewResponse::class
        );
    }
}
```

Your custom response class:

```php
<?php

class LoginViewResponse extends LoginViewResponseBase
{
    public function toResponse($request)
    {
        $publicKey = $this->publicKeyRequest($request);

        return Inertia::render('Webauthn/WebauthnLogin', [
            'publicKey' => $publicKey,
        ]);
    }
}
```

Available overridable responses:

| Method | Contract |
|---|---|
| `loginViewResponseUsing` | `LoginViewResponseContract` |
| `loginSuccessResponseUsing` | `LoginSuccessResponseContract` |
| `registerViewResponseUsing` | `RegisterViewResponseContract` |
| `registerSuccessResponseUsing` | `RegisterSuccessResponseContract` |
| `destroyViewResponseUsing` | `DestroyResponseContract` |
| `updateViewResponseUsing` | `UpdateResponseContract` |

### Local Development Requirements

WebAuthn requires a secure context. For local development:

1. Use a proper domain (`localhost` and `127.0.0.1` are rejected by WebAuthn)
2. Configure a trusted SSL/TLS certificate
3. Serve on port 443 (non-443 ports are rejected)

Use tools like Laravel Valet, `mkcert`, or ngrok to create a secure local environment:

```bash
# Using mkcert to create trusted local certificates
mkcert -install
mkcert myapp.test

# Using Laravel Valet (handles SSL automatically)
valet secure myapp.test
```

## The Registration Flow in Detail

When a user registers a passkey, the browser calls `navigator.credentials.create()` with options from your server. Here's what happens step by step:

1. **Server prepares challenge**: Your application generates a random challenge and specifies the relying party (your domain) and user information
2. **Client requests credential**: The browser sends this to the authenticator (secure hardware on the device)
3. **User verifies identity**: The user authenticates with their biometric (fingerprint, Face ID) or device PIN
4. **Key pair generated**: The authenticator creates a new public/private key pair uniquely tied to your domain
5. **Private key stored**: The private key is saved in the device's secure hardware — it can never be extracted
6. **Public key returned**: The authenticator returns the public key with an attestation signature
7. **Server validates**: Your application verifies the attestation and stores the public key

## The Authentication Flow in Detail

When the user returns to authenticate:

1. **Server sends challenge**: Your application generates a new random challenge and requests authentication
2. **Client prompts user**: The browser asks the user to authenticate using their biometric or PIN
3. **Challenge signed**: The authenticator signs the challenge with the private key
4. **Signed response returned**: The browser returns the signature to your server
5. **Server verifies**: Your application validates the signature using the stored public key
6. **Session created**: On successful verification, the user is authenticated

The critical security property: the authenticator checks that the requesting origin matches the domain the credential was created for. A phishing site cannot trick the authenticator into signing a challenge for a different domain.

## Real-World Use Cases

- **SaaS applications**: Eliminate password reset workflows, reduce support tickets
- **Enterprise portals**: Meet compliance requirements for phishing-resistant MFA
- **Financial platforms**: Reduce account takeover risk with hardware-backed authentication
- **Healthcare systems**: Comply with HIPAA requirements for strong authentication
- **Customer-facing apps**: Improve conversion rates by removing password friction

## Best Practices

- **Always offer fallback authentication**: Users need a way to authenticate when their device is unavailable
- **Support multiple credentials per user**: Allow users to register multiple devices
- **Implement rate limiting**: Prevent brute force against the challenge-response flow
- **Monitor registration events**: Watch for unusual patterns that might indicate enrollment fraud
- **Use WebAuthn as a second factor initially**: Gradually migrate from password+2FA to passkey-only

## Common Mistakes to Avoid

- **Requiring WebAuthn without fallback**: Not all users have compatible hardware
- **Ignoring browser compatibility**: While most modern browsers support WebAuthn, verify your target audience
- **Poor error handling**: WebAuthn failures can be cryptic; provide clear user-facing messages
- **Using insecure local development**: WebAuthn rejects non-HTTPS and non-standard ports
- **Not testing with real hardware**: Software-based authenticators behave differently from hardware keys

## Security Considerations

Passkeys eliminate entire categories of authentication attacks, but they introduce new considerations.

### Device Loss

When a user loses their device, they lose their passkeys unless they're synced through a cloud service. Always provide recovery options:

- Recovery codes generated during initial setup
- Email-based recovery links
- Secondary authentication methods (TOTP, SMS backup)
- Customer support verification process

### Sync Security

Passkeys that sync through iCloud or Google's password manager inherit the security of those accounts. A compromised Apple ID or Google account exposes all synced passkeys. Enterprise deployments may prefer device-bound passkeys that never sync.

### Phishing Resistance

WebAuthn's phishing resistance is its strongest security property. Each credential is scoped to a specific origin. A passkey created for `example.com` won't work on `examp1e.com`. This prevents the most common authentication attack vector.

### Server-Side Security

Your server stores only public keys. A database breach exposes public keys, which are useless for authentication without the corresponding private keys. This fundamentally changes the breach response — no passwords to reset, no credentials to rotate.

## Performance Implications

WebAuthn authentication is faster than password entry for users but involves cryptographic operations:

- **Registration**: ~100-200ms for key generation on modern hardware
- **Authentication**: ~50-100ms for challenge signing
- **Verification**: ~5-10ms for signature validation on the server

The user-perceived time is primarily the biometric or PIN prompt, which takes 1-2 seconds. This is comparable to or faster than typing a password, especially for complex passwords.

## Frequently Asked Questions

**What's the difference between WebAuthn and passkeys?**
WebAuthn is the standard protocol. Passkeys are the user-facing implementation that syncs credentials across devices via cloud services.

**Can users authenticate across devices?**
Yes. Passkeys can sync through iCloud Keychain, Google Password Manager, or third-party managers like 1Password, making them available across a user's devices.

**Do passkeys work offline?**
Yes, for authentication. The private key is stored on the device. Registration requires internet connectivity.

**How do I migrate existing users to passkeys?**
Offer passkey enrollment as an option during authentication. Gradually incentivize adoption by making passkey authentication faster than password entry.

**Is WebAuthn more secure than TOTP?**
Yes. WebAuthn is phishing-resistant, while TOTP codes can be intercepted through man-in-the-middle attacks.

**Can I use WebAuthn without a framework?**
Yes, the `webauthn-framework` PHP library works with any PHP application. The Laravel package adds convenient integration.

**What happens if a user loses their device?**
Recovery codes, backup authentication methods, or account recovery processes should be in place.

**Do passkeys work with security keys like YubiKey?**
Yes. FIDO2 security keys are compatible with WebAuthn and can be used as passkey authenticators.

## Conclusion

WebAuthn and passkeys represent the future of authentication. They eliminate the most common attack vectors while improving user experience. Users authenticate with the same gesture they use to unlock their phone — a fingerprint, face scan, or PIN. No passwords to remember, no passwords to steal.

The webauthn-framework ecosystem makes implementation straightforward in PHP. With the Laravel package, you can add passkey support in an afternoon. Start with optional enrollment alongside existing authentication, monitor adoption, and gradually reduce reliance on passwords.

The technology is mature, the browsers support it, and users are ready for a passwordless experience. Your applications should be too.

**[WebAuthn Framework Documentation](https://webauthn-doc.spomky-labs.com/) | [Laravel WebAuthn Package](https://github.com/asbiin/laravel-webauthn)**
