---
title: "Broken Authentication - Preventing Auth Flaws in PHP Applications"
description: "Learn how to prevent broken authentication vulnerabilities in PHP applications. Covers OWASP Top 10 auth flaws, password hashing with bcrypt/argon2, session security, and MFA."
pubDate: "2022-08-18 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Authentication", "Security", "PHP", "OWASP", "Session Management"]
selected: false
---

In 2021, OWASP redefined one of its top risks as Identification and Authentication Failures. Previously known as Broken Authentication, this category covers everything from weak password policies to session fixation attacks. Authentication is the gatekeeper of your application. When it fails, everything else is compromised.

This article covers the most common authentication flaws in PHP applications and shows you how to fix them with modern PHP 8.1+ practices.

## Authentication vs. Authorization

The most common mistake developers make is confusing authentication with authorization.

- **Authentication** answers "Who are you?" It proves identity.
- **Authorization** answers "What are you allowed to do?" It proves access or intent.

Your application needs both. Authenticating a user tells you who they are, but it doesn't tell you whether they can delete records, manage users, or view admin panels. Keep these concepts completely separate in your code.

## Common Authentication Flaws

### Weak Password Policies

Allowing users to set passwords like "password123" is a vulnerability, not a feature. Enforce minimum requirements:

```php
class PasswordValidator
{
    public static function validate(string $password): void
    {
        if (strlen($password) < 12) {
            throw new \InvalidArgumentException(
                'Password must be at least 12 characters'
            );
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException(
                'Password must contain an uppercase letter'
            );
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new \InvalidArgumentException(
                'Password must contain a lowercase letter'
            );
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new \InvalidArgumentException(
                'Password must contain a digit'
            );
        }

        if (!preg_match('/[^\w]/', $password)) {
            throw new \InvalidArgumentException(
                'Password must contain a special character'
            );
        }
    }
}
```

Length matters more than complexity. A 20-character lowercase passphrase is harder to brute force than an 8-character mixed-case password with symbols.

### No Rate Limiting on Login

Without rate limiting, attackers can brute force passwords at thousands of attempts per second. Implement exponential backoff:

```php
class LoginRateLimiter
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function __construct(
        private \PDO $db
    ) {}

    public function isLockedOut(string $identifier): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier = :identifier
             AND attempted_at > :since
             AND successful = 0'
        );

        $stmt->execute([
            'identifier' => $identifier,
            'since' => (new \DateTime())
                ->modify('-' . self::LOCKOUT_MINUTES . ' minutes')
                ->format('Y-m-d H:i:s'),
        ]);

        return $stmt->fetchColumn() >= self::MAX_ATTEMPTS;
    }

    public function recordAttempt(
        string $identifier,
        bool $successful
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO login_attempts
             (identifier, successful, attempted_at, ip_address)
             VALUES (:identifier, :successful, :attempted_at, :ip)'
        );

        $stmt->execute([
            'identifier' => $identifier,
            'successful' => $successful ? 1 : 0,
            'attempted_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);
    }
}
```

Track both the username and the IP address. A distributed attack from multiple IPs targeting a single account needs username-based locking. A single IP targeting multiple accounts needs IP-based locking.

### Session Fixation

Session fixation occurs when an attacker sets a user's session ID before they log in. After login, if the session ID doesn't change, the attacker can hijack the authenticated session.

Regenerate the session ID after successful login:

```php
class AuthenticationService
{
    public function authenticate(
        string $email,
        string $password
    ): User {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || !password_verify($password, $user->passwordHash)) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user->id;
        $_SESSION['logged_in_at'] = time();

        return $user;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
```

## Password Hashing With Bcrypt and Argon2

Never store plain text passwords. Never use MD5, SHA1, or even simple SHA256 for passwords. PHP's `password_hash()` and `password_verify()` handle the complexity for you.

### Bcrypt

Bcrypt is the default algorithm and works everywhere:

```php
class UserRegistrationService
{
    public function register(string $email, string $password): User
    {
        $hash = password_hash(
            $password,
            PASSWORD_BCRYPT,
            ['cost' => 12]
        );

        if ($hash === false) {
            throw new \RuntimeException('Password hashing failed');
        }

        return $this->userRepository->create($email, $hash);
    }
}
```

The `cost` parameter controls how slow the hash is. Start at 10 and increase it yearly as hardware improves. A single hash should take 100-200 milliseconds to compute.

### Argon2

PHP 8.1 ships with Argon2id, which resists both GPU and side-channel attacks. It's the recommended algorithm:

```php
class UserRegistrationService
{
    public function register(string $email, string $password): User
    {
        $hash = password_hash(
            $password,
            PASSWORD_ARGON2ID,
            [
                'memory_cost' => 65536, // 64 MB
                'time_cost' => 4,
                'threads' => 3,
            ]
        );

        if ($hash === false) {
            throw new \RuntimeException('Password hashing failed');
        }

        return $this->userRepository->create($email, $hash);
    }
}
```

Argon2id's configurable memory cost makes it resistant to ASIC and GPU attacks. Bcrypt is still acceptable if Argon2 isn't available, but Argon2id is the standard for new applications.

### Verifying and Rehashing

Verify passwords with `password_verify()`:

```php
class LoginService
{
    public function attempt(string $email, string $password): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user->passwordHash)) {
            return false;
        }

        // Rehash if algorithm or cost has changed
        if (password_needs_rehash(
            $user->passwordHash,
            PASSWORD_ARGON2ID,
            [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3,
            ]
        )) {
            $this->userRepository->updatePassword(
                $user->id,
                password_hash($password, PASSWORD_ARGON2ID)
            );
        }

        return true;
    }
}
```

The `password_needs_rehash()` check ensures existing passwords automatically upgrade to stronger algorithms as you adjust your configuration.

## Session Security

### Secure Cookie Configuration

PHP session cookies need strict settings:

```php
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', 1); // when using HTTPS
ini_set('session.gc_maxlifetime', 7200); // 2 hours
```

Or better, configure session handling in one place:

```php
class SessionConfigurator
{
    public static function configure(): void
    {
        $isHttps = (!empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) === 443;

        session_set_cookie_params([
            'lifetime' => 7200,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}
```

### Session Timeout

Implement absolute and idle timeouts:

```php
class SessionManager
{
    private const IDLE_TIMEOUT = 1800;     // 30 minutes
    private const ABSOLUTE_TIMEOUT = 28800; // 8 hours

    public function validateSession(): void
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }

        $idle = time() - $_SESSION['last_activity'];
        if ($idle > self::IDLE_TIMEOUT) {
            $this->terminateSession('idle timeout');
        }

        $lifetime = time() - ($_SESSION['logged_in_at'] ?? time());
        if ($lifetime > self::ABSOLUTE_TIMEOUT) {
            $this->terminateSession('absolute timeout');
        }

        $_SESSION['last_activity'] = time();
    }

    private function terminateSession(string $reason): void
    {
        error_log("Session terminated: $reason for user "
            . ($_SESSION['user_id'] ?? 'unknown'));

        session_unset();
        session_destroy();

        throw new SessionExpiredException(
            'Your session has expired. Please log in again.'
        );
    }
}
```

## Multi-Factor Authentication (MFA)

MFA adds a second verification factor beyond passwords. The most common approach is TOTP (Time-based One-Time Password):

```php
use RobThree\Auth\TwoFactorAuth;

class MfaService
{
    public function __construct(
        private TwoFactorAuth $tfa
    ) {}

    public function enableMfa(User $user): array
    {
        $secret = $this->tfa->createSecret();
        $qrCode = $this->tfa->getQRCodeImageAsDataUri(
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code' => $qrCode,
        ];
    }

    public function verifyCode(User $user, string $code): bool
    {
        return $this->tfa->verifyCode(
            $user->mfaSecret,
            $code
        );
    }
}
```

Store the MFA secret encrypted in your database. Require MFA for sensitive operations like password changes, admin access, and financial transactions.

## Error Handling: Don't Swallow Auth Failures

One of the most dangerous patterns is catching authentication exceptions and doing nothing with them:

```php
// DANGEROUS: This silently swallows all auth failures
function checkAuthentication(string $token, array $scopes): void
{
    global $idp;

    try {
        $idp->validateToken($token, $scopes);
    } catch (\Exception $error) {
        error_log('Auth failed: ' . $error->getMessage());
    }
}
```

This function always succeeds. Valid tokens pass. Invalid tokens also pass—they just log an error. The application continues as if authentication succeeded.

Always propagate authentication failures:

```php
function checkAuthentication(
    string $token,
    array $scopes
): void {
    global $idp;

    try {
        $idp->validateToken($token, $scopes);
    } catch (\Exception $error) {
        throw new AuthenticationException(
            'Authentication failed',
            previous: $error
        );
    }
}
```

## OWASP Prevention Checklist

Follow OWASP's guidance for authentication:

1. Use strong password hashing (Argon2id or bcrypt)
2. Implement account lockout after failed attempts
3. Regenerate session IDs after login
4. Set secure, HttpOnly, SameSite cookies
5. Implement idle and absolute session timeouts
6. Provide multi-factor authentication
7. Use rate limiting on all auth endpoints
8. Log all authentication events
9. Never disclose whether a username exists or just the password is wrong
10. Use secure password reset flows with expiring tokens

## Conclusion

Broken authentication remains one of the most damaging vulnerabilities in web applications. PHP 8.1+ gives you the tools to get it right: `password_hash()` with Argon2id, `session_regenerate_id()`, and strict session cookie configuration.

The principles are straightforward: hash passwords properly, rotate session IDs on login, timeout idle sessions, rate limit login attempts, and authenticate timeouts as failures. Implement these patterns from the start. Retrofitting security is always harder than building it in.
