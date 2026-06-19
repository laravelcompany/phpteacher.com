---
title: "Security in Drupal 9 - Best Practices for Securing Your Site"
description: "Best practices for securing your Drupal 9 site. Covering access control, input validation, secure configuration, and vulnerability management."
category: "Drupal"
banner: "/logo.svg"
pubDate: "2022-11-28 21:00:00"
tags: ["Drupal", "Drupal 9", "Security", "PHP", "Best Practices"]
---

Writing clean, secure code in Drupal is useless if users can enter `123456` as a password or if your site leaks HTTP headers revealing its CMS. Drupal 9's core has eliminated many attack vectors — PHP code in fields, unsafe text formats — but a secure site requires intentional configuration and the right contributed modules.

This guide covers the essential security modules and configurations every Drupal 9 site should implement. You can apply most of these without server access.

## User Authentication Security

### Login Security

The [Login Security](https://www.drupal.org/project/login_security) module adds access control features to login forms:

- Limit invalid login attempts before blocking accounts
- Temporarily or permanently deny access by IP address
- Email notifications for password guessing attempts
- Email notifications for brute force login attempts
- Disable Drupal core's login error messages (obfuscate failure reasons)

```php
// Example: Configure in settings.php
$config['login_security.settings']['user_blocks'] = 5;
$config['login_security.settings']['user_lock'] = 600; // 10 minutes
$config['login_security.settings']['host_blocks'] = 10;
```

### Password Policy

The [Password Policy](https://www.drupal.org/project/password_policy) module enforces restrictions on user passwords. Define policies for minimum length, character types, and password age. Policies apply to passwords set via web forms (not Drush or other CLI tools).

```php
// Example policy configuration
$config['password_policy.password_policy']['password_requirements'] = [
    'length' => 12,
    'uppercase' => 1,
    'lowercase' => 1,
    'digit' => 1,
    'special' => 1,
];
```

## Hardening Modules

### Security Kit

[Security Kit (SecKit)](https://www.drupal.org/project/seckit) provides various security-hardening options to mitigate common web vulnerabilities:

- **Cross-site Scripting (XSS)**: Prevents attackers from injecting malicious scripts
- **Cross-site Request Forgery (CSRF)**: Prevents unwanted actions on authenticated sessions
- **Clickjacking**: Prevents UI redressing attacks
- **SSL/TLS**: Enforces secure connections

```php
$config['seckit.settings']['seckit_clickjacking'] = [
    'description' => 'Deny',
    'origin' => 'SAMEORIGIN',
];
$config['seckit.settings']['seckit_xss'] = [
    'csp' => [
        'checkbox' => true,
        'report-only' => false,
        'default-src' => ["'self'"],
    ],
];
```

### Security Review

The [Security Review](https://www.drupal.org/project/security_review) module automates security checks. It scans your site for common misconfigurations and reports findings. It doesn't fix them automatically — you make corrections manually — but it tells you what needs attention.

Checks include:

- File system permissions
- PHP filter availability
- User registration settings
- Error reporting settings
- Database configuration

### Website Security

The [Website Security](https://www.drupal.org/project/website_security) module provides enterprise-level protection in a single module:

- Login and registration security
- Brute force attack prevention
- IP monitoring and blocklisting
- DOS attack prevention
- Strong password enforcement

## Input and Upload Protection

### File Upload Secure Validator

The [File Upload Secure Validator](https://www.drupal.org/project/file_upload_secure_validator) module performs server-side validation on uploaded files. Attackers often change file extensions to upload malicious content. This module uses PHP's `fileinfo` library to check the actual MIME type against the allowed extensions:

```php
// Example: Allowed extensions for image fields
$config = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
];
```

### reCAPTCHA

[reCAPTCHA](https://www.drupal.org/project/recaptcha) uses Google's reCAPTCHA service — tough on bots, easy on humans. It's one of many CAPTCHA options available. Whatever you choose, having CAPTCHA on forms prevents automated abuse.

### SpamSpan Filter

The [SpamSpan filter](https://www.drupal.org/project/spamspan) obfuscates email addresses to prevent spambots from harvesting them. No technique is foolproof — the arms race between security and attackers never ends — but every layer helps.

## Network and Access Controls

### Restrict Login or Role Access by IP

The [Restrict by IP](https://www.drupal.org/project/restrict_by_ip) module restricts access for users or roles by IP address. Even the initial administrator account (user 1) can be restricted.

### Advanced Ban

[Advanced Ban](https://www.drupal.org/project/advban) extends Drupal core's ban module with:

- IP range banning (IPv4)
- Blocked IP expiration (via cron)
- Unblock all IPs
- IP search
- Custom ban text formatting

### Drupal Perimeter Defense

The [Perimeter Defense](https://www.drupal.org/project/perimeter_defense) module bans IPs sending suspicious requests. Bots probe sites without knowing if they're Drupal, WordPress, or Magento. This module blocks requests that match suspicious patterns using regular expressions.

### Disable Login Page

The [Disable Login Page](https://www.drupal.org/project/disable_login) module prevents anonymous access to the default Drupal login page. Access requires a secret key name-value pair configured by the admin. Without it, users get "Access denied." Useful for corporate sites or personal blogs with no public user registration.

## Information Disclosure Prevention

### Remove HTTP Headers

The [Remove HTTP Headers](https://www.drupal.org/project/remove_http_headers) module removes identifying HTTP headers from responses. This obfuscates that your site runs Drupal. However, standard file paths like `sites/default/files` can still reveal the CMS — no technique is foolproof.

### Private Files Download Permission

The [Private Files Download Permission](https://www.drupal.org/project/private_files_download_permission) module adds granular control over file downloads by role and directory. Important for sites with sensitive document access.

## Module Security Advisory Coverage

The [Module Security Advisory Coverage Report](https://www.drupal.org/project/msacr) shows which installed modules have Drupal security advisory coverage and which don't. Essential for corporate security assessment compliance.

## Security Pack

The [Security Pack](https://www.drupal.org/project/security_pack) installs and configures a recommended set of security modules in one step:

- Password Policy
- Auto Logout
- Antibot
- Login Security
- Security Kit
- Username Enumeration Prevention

If you don't know where to start, install Security Pack and build from there.

## Ongoing Practices

Modules are not fire-and-forget. Security requires continuous effort:

1. **Apply security updates**: Drupal core and contributed modules release security advisories. Subscribe to the [Drupal Security Advisory feed](https://www.drupal.org/security) and apply updates promptly.
2. **Review permissions**: Every new module adds permissions. Audit them regularly. Remove unused roles.
3. **Monitor logs**: Watch for failed login attempts, suspicious requests, and unauthorized access attempts.
4. **Test your security**: The Security Review module helps, but also conduct manual penetration testing.
5. **Trusted host patterns**: Configure `settings.php` to restrict which hostnames serve your site:

```php
$settings['trusted_host_patterns'] = [
    '^www\.example\.com$',
    '^example\.com$',
];
```

Too often, we say "I'll check security later" and never do. Take a moment today to review your Drupal site's security posture. Install a module you haven't tried. Run a security review. The hackers aren't waiting.
