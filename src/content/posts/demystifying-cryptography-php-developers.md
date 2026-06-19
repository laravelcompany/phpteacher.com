---
title: "Demystifying Cryptography for PHP Developers"
description: "Understand symmetric, asymmetric, and envelope encryption in PHP. Practical guide to Libsodium, OpenSSL, and choosing the right cryptographic approach."
pubDate: "2023-12-20 23:30:00"
category: "security"
banner: "/logo.svg"
tags: ["PHP", "Cryptography", "Encryption", "Libsodium", "Security", "OpenSSL", "PHP Security", "Data Protection"]
selected: false
---

Cryptography often feels like black magic to developers. Terms like symmetric encryption, asymmetric encryption, and envelope encryption get thrown around without clear explanations of what they mean or when to use which.

But cryptography doesn't have to be mysterious. The underlying concepts are straightforward. And PHP makes implementing them accessible through two excellent libraries: OpenSSL and Libsodium.

## What You'll Learn

- The difference between symmetric, asymmetric, and hybrid encryption
- How TLS and HTTPS use cryptography to secure connections
- Using PHP's Libsodium extension for secure encryption
- When to use each encryption approach in your applications
- Common cryptographic pitfalls and how to avoid them

## Symmetric Encryption

Symmetric encryption uses the same key for both encryption and decryption. Think of it like a lockbox with a single key: you lock it with the key, and the same key unlocks it.

### How It Works

Both parties share a secret key. The sender encrypts data with this key. The receiver decrypts with the same key. The security depends entirely on keeping the shared key secret.

Real-world examples include full-disk encryption on laptops, database encryption at rest, and encrypted file storage. In each case, a single secret key protects the data.

### PHP Implementation

PHP provides symmetric encryption through OpenSSL:

```php
<?php

function encryptData(string $plaintext, string $key): string
{
    $iv = random_bytes(openssl_cipher_iv_length('aes-256-gcm'));

    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return base64_encode($iv . $tag . $ciphertext);
}

function decryptData(string $encrypted, string $key): string
{
    $data = base64_decode($encrypted);

    $ivLength = openssl_cipher_iv_length('aes-256-gcm');
    $iv = substr($data, 0, $ivLength);
    $tag = substr($data, $ivLength, 16);
    $ciphertext = substr($data, $ivLength + 16);

    return openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
}
```

The key insight: AES-256-GCM provides both confidentiality and authentication. The authentication tag detects tampering. A modified ciphertext will fail decryption.

## Asymmetric Encryption

Asymmetric encryption uses a pair of keys: a public key and a private key. The public key encrypts. The private key decrypts. Anyone can encrypt a message using your public key, but only you can decrypt it with your private key.

### How It Works

Imagine a mailbox with a slot. Anyone can drop a letter through the slot (encrypt with public key). Only the person with the mailbox key can open it and read the letters (decrypt with private key).

This solves the key distribution problem of symmetric encryption. You don't need a secure channel to share a key — you just publish your public key.

### Limitation: Key Size

Asymmetric encryption has a critical limitation: it can only encrypt data up to the size of the key. A 2048-bit RSA key can encrypt at most 256 bytes. This is fine for encrypting a symmetric key but impractical for encrypting files or messages directly.

## Envelope Encryption (Hybrid Approach)

Envelope encryption combines the strengths of both approaches. Use asymmetric encryption to securely exchange a symmetric key, then use that symmetric key for bulk encryption.

This is exactly how TLS works:

1. The client and server perform an asymmetric key exchange (using RSA or Elliptic Curve Diffie-Hellman)
2. Both parties derive the same symmetric session key without ever transmitting it
3. All subsequent communication uses symmetric encryption (AES-256-GCM) for performance

### PHP with Libsodium

Libsodium makes envelope encryption straightforward:

```php
<?php

use Sodium\crypto_box;

// Generate key pairs (typically done once per user)
$aliceKeyPair = sodium_crypto_box_keypair();
$aliceSecretKey = sodium_crypto_box_secretkey($aliceKeyPair);
$alicePublicKey = sodium_crypto_box_publickey($aliceKeyPair);

$bobKeyPair = sodium_crypto_box_keypair();
$bobSecretKey = sodium_crypto_box_secretkey($bobKeyPair);
$bobPublicKey = sodium_crypto_box_publickey($bobKeyPair);

// Alice encrypts a message for Bob
function encryptFor(string $message, string $recipientPublicKey, string $senderSecretKey): string
{
    $nonce = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);

    $ciphertext = sodium_crypto_box(
        $message,
        $nonce,
        $recipientPublicKey,
        $senderSecretKey
    );

    sodium_memzero($senderSecretKey);

    return base64_encode($nonce . $ciphertext);
}

// Bob decrypts a message from Alice
function decryptFrom(
    string $encrypted,
    string $senderPublicKey,
    string $recipientSecretKey
): string {
    $data = base64_decode($encrypted);

    $nonce = substr($data, 0, SODIUM_CRYPTO_BOX_NONCEBYTES);
    $ciphertext = substr($data, SODIUM_CRYPTO_BOX_NONCEBYTES);

    $plaintext = sodium_crypto_box_open(
        $ciphertext,
        $nonce,
        $senderPublicKey,
        $recipientSecretKey
    );

    sodium_memzero($recipientSecretKey);

    return $plaintext;
}

$encrypted = encryptFor('Hello Bob!', $bobPublicKey, $aliceSecretKey);
$decrypted = decryptFrom($encrypted, $alicePublicKey, $bobSecretKey);

echo $decrypted; // Hello Bob!
```

The `crypto_box` functions handle the entire envelope: key agreement, ephemeral symmetric key generation, and AES-256 encryption. The library ensures you use safe defaults without needing a cryptography PhD.

### Symmetric Encryption with Libsodium

For scenarios where both parties already share a secret, use `crypto_secret_box`:

```php
<?php

function encryptSymmetric(string $plaintext, string $key): string
{
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

    $ciphertext = sodium_crypto_secretbox(
        $plaintext,
        $nonce,
        $key
    );

    return base64_encode($nonce . $ciphertext);
}

function decryptSymmetric(string $encrypted, string $key): string
{
    $data = base64_decode($encrypted);

    $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

    return sodium_crypto_secretbox_open(
        $ciphertext,
        $nonce,
        $key
    );
}

$key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
$encrypted = encryptSymmetric('Sensitive data', $key);
$decrypted = decryptSymmetric($encrypted, $key);
```

## Choosing the Right Approach

| Scenario | Approach | PHP Tool |
|---|---|---|
| Encrypt data for storage | Symmetric | `sodium_crypto_secretbox` |
| Share encrypted data with others | Asymmetric envelope | `sodium_crypto_box` |
| Hash passwords | One-way hashing | `password_hash` |
| Sign/verify data | Asymmetric signatures | `sodium_crypto_sign` |
| Secure HTTP traffic | TLS (automatic) | cURL/OpenSSL |

## Symmetric vs. Asymmetric: When to Use Each

The choice between symmetric and asymmetric encryption depends on your threat model and operational requirements.

### Symmetric Encryption Is Best When

- You control both the encryption and decryption (e.g., encrypting data in your database)
- Performance matters — symmetric is 100-1000x faster than asymmetric
- Key distribution is manageable (one server, one key)
- You need to encrypt large amounts of data

### Asymmetric Encryption Is Best When

- Multiple parties need to encrypt data for a single recipient
- You need digital signatures (prove who sent a message)
- Key distribution happens over untrusted channels
- You need non-repudiation (the sender can't deny sending a message)

### Envelope Encryption Is Best When

- You need both security and performance
- You're encrypting data for multiple recipients
- You need to rotate keys without re-encrypting all data

## Key Management Fundamentals

The security of any cryptographic system depends on key management, not the algorithm.

### Key Generation

Always use a cryptographically secure random generator:

```php
<?php

// Good - cryptographically secure
$key = random_bytes(32); // 256-bit key for AES-256

// Bad - not cryptographically secure
$key = md5('my password'); // Do not do this
$key = sha1('my password'); // Do not do this
```

### Key Storage

Store keys outside the application codebase:

```php
<?php

// .env file
// ENCRYPTION_KEY=base64:abc123...

// config/encryption.php
return [
    'key' => base64_decode(env('ENCRYPTION_KEY')),
];
```

### Key Rotation

Establish a rotation schedule and support multiple active keys:

```php
<?php

class KeyManager
{
    private array $keys = [];

    public function __construct()
    {
        // Load current and previous keys
        $this->keys[1] = $this->loadKey('ENCRYPTION_KEY_V1');
        $this->keys[2] = $this->loadKey('ENCRYPTION_KEY_V2');
    }

    public function encrypt(string $data): array
    {
        $keyId = max(array_keys($this->keys));
        $key = $this->keys[$keyId];

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($data, $nonce, $key);

        return [
            'key_id' => $keyId,
            'nonce' => base64_encode($nonce),
            'ciphertext' => base64_encode($ciphertext),
        ];
    }

    public function decrypt(array $encrypted): string
    {
        $key = $this->keys[$encrypted['key_id']];
        $nonce = base64_decode($encrypted['nonce']);
        $ciphertext = base64_decode($encrypted['ciphertext']);

        return sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
    }

    private function loadKey(string $env): string
    {
        return base64_decode(env($env));
    }
}
```

When rotating keys, re-encrypt data during low-traffic periods using the background job pattern.

## Common Encryption Mistakes in PHP Applications

### Mistake 1: Using Weak Algorithms

```php
<?php

// Bad: DES is 56-bit, trivially brute-forced
$encrypted = openssl_encrypt($data, 'des', $key);

// Bad: RC4 is broken
$encrypted = openssl_encrypt($data, 'rc4', $key);

// Good: AES-256-GCM provides strong security with authentication
$encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
```

### Mistake 2: ECB Mode

ECB mode encrypts each block independently, meaning identical plaintext blocks produce identical ciphertext blocks. This leaks patterns:

```php
<?php

// Bad: ECB mode reveals patterns
$encrypted = openssl_encrypt($data, 'aes-256-ecb', $key);

// Good: GCM mode provides both confidentiality and authentication
$encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
```

### Mistake 3: Reusing Nonces

Never encrypt two messages with the same nonce and key:

```php
<?php

// Bad: Static nonce reused for every encryption
function badEncrypt(string $data, string $key): string
{
    $nonce = str_repeat("\0", SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return sodium_crypto_secretbox($data, $nonce, $key);
}

// Good: Fresh random nonce per encryption
function goodEncrypt(string $data, string $key): string
{
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return $nonce . sodium_crypto_secretbox($data, $nonce, $key);
}
```

## Real-World Use Cases

- **API token encryption**: Encrypt session tokens in cookies or mobile app storage using symmetric encryption
- **End-to-end messaging**: Use asymmetric encryption so only the intended recipient can read messages
- **PCI-compliant data storage**: Encrypt credit card data at rest with envelope encryption where the data key is wrapped by a master key
- **File sharing**: Encrypt files with a random symmetric key, then encrypt the key with each recipient's public key
- **Password reset tokens**: Sign tokens with a private key so the application can verify they haven't been tampered with

## Best Practices

- **Use Libsodium by default**: It's part of PHP core since 7.2, well-audited, and designed to prevent common mistakes
- **Never roll your own crypto**: Standard libraries have been reviewed by experts. Your custom implementation almost certainly has flaws
- **Authenticate your encryption**: Always use authenticated modes (GCM, ChaCha20-Poly1305) that detect tampering
- **Rotate keys regularly**: Establish a key rotation schedule and automate the re-encryption of data
- **Separate encryption from authentication keys**: Don't reuse the same key for different purposes
- **Use constant-time comparisons**: When comparing hashes or MACs, use `hash_equals()` to prevent timing attacks

## Common Mistakes to Avoid

- **Using ECB mode**: Electronic Codebook mode reveals patterns in the ciphertext. Always use GCM or CBC with a random IV
- **Storing keys in the database**: Encryption keys belong in environment variables or a key management service, not alongside the encrypted data
- **Reusing nonces**: Never encrypt two messages with the same nonce and key. Always generate a fresh random nonce
- **Encrypting without authentication**: An attacker can modify ciphertext in CBC mode. Always authenticate or use GCM
- **Using weak algorithms**: DES, RC4, and MD5 are broken. Use AES-256, ChaCha20, SHA-256 or SHA-3

## Frequently Asked Questions

**What's the difference between encryption and hashing?**
Encryption is reversible (you can decrypt). Hashing is one-way (you cannot recover the original input). Use hashing for passwords and integrity checks. Use encryption for confidentiality.

**Should I use OpenSSL or Libsodium?**
Libsodium. It's simpler, harder to misuse, and included in PHP core. OpenSSL is more flexible but requires more expertise to use safely.

**How long should encryption keys be?**
For symmetric encryption, 256 bits (32 bytes). For RSA, 2048 bits minimum, 4096 recommended. For Elliptic Curve, 256 bits (P-256).

**Can encrypted data be compressed?**
Yes, but compress before encrypting. Encrypted data appears random and won't compress effectively.

**How do I securely store encryption keys?**
Use environment variables, a key management service (AWS KMS, HashiCorp Vault), or a hardware security module. Never hard-code keys.

**What's a nonce/IV?**
A number used once. It ensures the same plaintext encrypts to different ciphertexts each time. Always use a fresh random nonce for each encryption operation.

**Is HTTPS enough for my API?**
HTTPS provides encryption in transit. You may also need encryption at rest (database encryption) and end-to-end encryption depending on your threat model.

## Conclusion

Cryptography doesn't need to be intimidating. The three approaches — symmetric, asymmetric, and hybrid — each solve specific problems with well-understood tradeoffs. PHP's Libsodium extension makes implementing them safely straightforward.

The critical rule: use established libraries, not custom implementations. Libsodium provides a "pit of success" — the default usage is the safe usage. When you need encryption, reach for `sodium_crypto_secretbox` or `sodium_crypto_box` and let the library handle the hard parts.

Your users' data security depends on getting cryptography right. PHP gives you the tools to do it.

**[PHP Libsodium Documentation](https://www.php.net/manual/en/book.sodium.php) | [Sodium Documentation](https://doc.libsodium.org/)**
