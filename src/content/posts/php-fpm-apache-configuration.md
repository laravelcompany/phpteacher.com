---
title: "PHP-FPM and Apache Configuration - Move Beyond mod_php"
description: "Learn how to configure PHP-FPM with Apache for better performance, multiple PHP versions, and process isolation. Step-by-step guide with virtual host setup and production tips."
pubDate: "2022-02-14 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP-FPM", "Apache", "PHP", "Web Server", "Configuration", "FPM", "Performance", "DevOps", "Ubuntu"]
selected: false
---

mod_php works great until you need to run two different PHP versions on the same server. Suddenly you're hunting for workarounds, pinning hopes on Docker, or considering a full re-architecture. There's a better way and it's been production-ready for over a decade.

PHP-FPM (FastCGI Process Manager) pulls PHP execution out of Apache and runs it as a separate service. This one architectural shift unlocks process isolation, per-pool user permissions, graceful reloads, and the ability to run PHP 7.4, 8.0, 8.1, and 8.3 side by side on the same machine. No containers required.

This guide walks through a complete PHP-FPM and Apache setup on Ubuntu — from package installation through virtual host configuration, pool tuning, and production hardening. Every command, every config file, and every explanation is here so you can ship it with confidence.

## What You'll Learn

- How PHP-FPM differs from mod_php and why that matters
- Full installation on Ubuntu using Ondřej Surý's PPA
- Pool configuration for user isolation and socket permissions
- Apache virtual host setup with `ProxyPassMatch` and `SetHandler`
- Unix sockets versus TCP sockets — when to use each
- Running multiple PHP versions simultaneously
- Real-world .htaccess patterns
- Production best practices and common mistakes

## mod_php vs PHP-FPM — Architecture

With mod_php, Apache embeds the PHP interpreter inside the web server process. Every Apache worker thread carries a full PHP runtime. This means:

- All PHP processes run as the Apache user (typically `www-data`)
- Configuration changes require a full Apache restart
- A PHP crash can take down the worker (and sometimes the entire server)
- Running two PHP versions is impractical without separate server instances

PHP-FPM decouples PHP from Apache entirely. A separate FPM master process spawns worker pools that listen on Unix sockets or TCP ports. Apache forwards requests to these pools via the FastCGI protocol. The result:

- PHP runs as whatever user you assign to the pool, not `www-data`
- FPM workers can be recycled without restarting Apache
- A PHP segfault kills one worker; the master spawns a replacement
- Each PHP version runs its own FPM instance with its own pool config
- Opcache, ini settings, and extensions are completely version-isolated

## Installation

Start with a fresh Ubuntu server. Add Ondřej Surý's PPA — it maintains the most reliable PHP packages for Ubuntu and Debian.

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
```

Install Apache and multiple PHP versions with FPM:

```bash
sudo apt install -y apache2
sudo apt install -y php8.3-fpm php8.3-cli php8.3-{common,mbstring,xml,curl,bcmath,gmp,zip,intl,sqlite3,mysql,gd,imagick,redis}
sudo apt install -y php8.1-fpm php8.1-cli php8.1-{common,mbstring,xml,curl,bcmath,gmp,zip,intl,sqlite3,mysql,gd,imagick,redis}
sudo apt install -y php8.0-fpm php8.0-cli php8.0-{common,mbstring,xml,curl,bcmath,gmp,zip,intl,sqlite3,mysql,gd,imagick,redis}
```

Enable the Apache modules needed to proxy requests to FPM:

```bash
sudo a2enmod proxy_fcgi setenvif
sudo systemctl restart apache2
```

## Configuration Deep Dive

### PHP Directory Structure

Each installed PHP version gets its own directory tree under `/etc/php/`:

```
/etc/php/
├── 8.0
│   ├── cli/
│   ├── fpm/
│   ├── cgi/
│   └── mods-available/
├── 8.1
│   ├── cli/
│   ├── fpm/
│   ├── cgi/
│   └── mods-available/
└── 8.3
    ├── cli/
    ├── fpm/
    ├── cgi/
    └── mods-available/
```

- **cli** — used when running `php` on the command line
- **fpm** — used by the PHP-FPM service for that version
- **cgi** — used by the CGI variant (rarely needed)
- **mods-available** — shared extension configs symlinked into each SAPI

Enable extensions per version:

```bash
sudo phpenmod -v 8.3 redis imagick
sudo phpenmod -v 8.1 redis imagick
sudo phpenmod -v 8.0 redis imagick
```

### Pool Configuration (pool.d)

The real power of PHP-FPM lives in the pool files. Each pool defines a separate worker group with its own user, socket, and process manager settings.

Open the default pool config for PHP 8.3:

```bash
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

Key directives:

```ini
[www]
user = site-user
group = site-user
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

- **user/group** — the OS user that PHP processes run as. Create a dedicated user per site for proper isolation.
- **listen** — path to the Unix socket. Apache must be able to read and write this socket.
- **listen.owner/group** — sets socket ownership. `www-data` (Apache's user) needs access.
- **pm** — process manager mode: `dynamic`, `static`, or `ondemand`.
- **pm.max_children** — hard cap on worker processes. Prevents runaway memory usage.

### Virtual Host Configuration

Apache needs a `SetHandler` directive to forward PHP requests to the FPM socket.

```apache
<VirtualHost *:80>
    ServerName fresh.test
    ServerAlias www.fresh.test
    DocumentRoot /var/www/fresh.test/public

    <Directory /var/www/fresh.test/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.(php|phar|phtml)$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost/"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/fresh.test-error.log
    CustomLog ${APACHE_LOG_DIR}/fresh.test-access.log combined
</VirtualHost>
```

Breakdown of the `SetHandler` line:

```
proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost/
```

- `proxy:unix:` — tells Apache to connect via a Unix domain socket
- `/run/php/php8.3-fpm.sock` — the socket path from the pool config
- `|fcgi://localhost/` — the FastCGI endpoint identifier (the hostname is arbitrary but required)

Enable the site:

```bash
sudo a2ensite fresh.test.conf
sudo systemctl reload apache2
```

### Unix Socket vs TCP Socket

The pool `listen` directive accepts two forms:

- **Unix socket**: `listen = /run/php/php8.3-fpm.sock`
- **TCP socket**: `listen = 127.0.0.1:9000`

Unix sockets are faster because they bypass the network stack entirely. They also use filesystem permissions for access control — only the `listen.group` user can connect. For single-server setups, always use Unix sockets.

TCP sockets matter when PHP-FPM runs on a separate machine. You also need TCP if Windows is involved (Windows lacks native Unix socket support, though WSL 2 changes this).

The corresponding Apache config for TCP:

```apache
<FilesMatch \.(php|phar|phtml)$>
    SetHandler "proxy:fcgi://127.0.0.1:9000"
</FilesMatch>
```

## Running Multiple PHP Versions

The killer feature of PHP-FPM is side-by-side versioning. Each PHP version runs its own FPM service:

```bash
sudo systemctl start php8.3-fpm
sudo systemctl start php8.1-fpm
sudo systemctl start php8.0-fpm
```

Each service reads its own `/etc/php/{version}/fpm/pool.d/www.conf` and listens on a unique socket. You assign a specific PHP version to a virtual host through the `SetHandler` socket path.

Example: two sites on different PHP versions:

```apache
# /etc/apache2/sites-available/fresh.test.conf — PHP 8.3
<FilesMatch \.(php|phar|phtml)$>
    SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost/"
</FilesMatch>

# /etc/apache2/sites-available/legacy.test.conf — PHP 8.0
<FilesMatch \.(php|phar|phtml)$>
    SetHandler "proxy:unix:/run/php/php8.0-fpm.sock|fcgi://localhost/"
</FilesMatch>
```

This is how platforms like Laravel Forge and Spin run hundreds of customer sites on a single server — each with its own PHP version, user, and extension set.

## .htaccess Patterns

PHP-FPM respects Apache's `.htaccess` files for per-directory configuration. Here are patterns that work regardless of whether you use mod_php or FPM.

### SSL Enforcement

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

### Redirects

```apache
Redirect 301 /old-page /new-page
Redirect 301 /old-section/ /new-section/
```

### CakePHP (and similar frameworks)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### Deny Access to Sensitive Files

```apache
<FilesMatch "\.(env|yml|md|log|lock)$">
    Require all denied
</FilesMatch>
```

## Testing Your Setup

Create a `phpinfo.php` file to verify everything works:

```php
<?php phpinfo();
```

Place it in your document root and visit `http://fresh.test/phpinfo.php`. The Server API line should read `FPM/FastCGI`, and the PHP Version should match the pool you configured.

From the command line, confirm the FPM service is running:

```bash
sudo systemctl status php8.3-fpm
```

Check that Apache's proxy module is active:

```bash
sudo a2query -m proxy_fcgi
```

Watch the error log for any socket permission issues:

```bash
sudo tail -f /var/log/apache2/fresh.test-error.log
```

Common socket errors look like:

```
[proxy:error] AH01071: Got error 'connect to unix:/run/php/php8.3-fpm.sock failed
(13: Permission denied)'
```

This means the socket permissions in `www.conf` don't match Apache's user. Fix with:

```ini
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
```

## Real-World Use Cases

### Laravel Forge-Style Multi-Tenant Servers

Services like Laravel Forge create a separate system user and FPM pool for every site. Each pool listens on a unique socket. This means one tenant's PHP process can't read another tenant's files — even if they share the same Apache instance.

```ini
[site1]
user = site1
group = site1
listen = /run/php/site1.sock

[site2]
user = site2
group = site2
listen = /run/php/site2.sock
```

### Staging vs Production on One Server

Run staging sites on PHP 8.1 for testing, production sites on PHP 8.3 for stability. The exact same server, no VMs, no Docker overhead.

### Legacy + Modern PHP Coexistence

Got an old CakePHP 2.x app stuck on PHP 7.4 and a shiny new Laravel 11 app on PHP 8.3? Point each virtual host at the appropriate socket. Migrate the legacy app incrementally — route by route — without touching the server setup.

## Best Practices

**Separate users per pool.** Never run multiple sites under `www-data`. Create a system user per project:

```bash
sudo useradd -m -s /bin/bash site-user
```

**Lock down socket permissions.** The socket should be readable and writable only by Apache and the pool user. Mode `0660` with explicit `listen.owner` and `listen.group` does exactly this.

**Keep PHP versions isolated.** Don't share extension configs across versions. Use `phpenmod -v 8.3 <ext>` to target specific installs.

**Set sensible `pm.max_children`.** Calculate it from available RAM:

```
max_children = (total RAM - overhead) / avg PHP process size
```

With 8 GB RAM and processes averaging 50 MB: `max_children ≈ 120`.

**Monitor FPM status.** Enable the status page in your pool config:

```ini
pm.status_path = /status
```

Then proxy it in Apache:

```apache
<Location /fpm-status>
    SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost/"
    Require ip 127.0.0.1
</Location>
```

## Common Mistakes

**Forgetting to restart FPM after config changes.** Edit `www.conf` → `sudo systemctl restart php8.3-fpm`. Apache reloads don't affect FPM pools.

**Wrong socket path.** The socket in your Apache `SetHandler` must match `listen` in the pool config exactly. A typo gives you `AH01071: connection failed`.

**Missing PHP extensions.** Your app calls `json_encode()` but `php8.3-json` isn't installed. Use `php -v` to list loaded extensions per version:

```bash
php8.3 -m
```

**Permission errors on the socket.** Apache's user (`www-data`) can't access a socket owned by `root:root`. Set `listen.owner` and `listen.group` to `www-data`.

**Running out of FPM workers.** If `pm.max_children` is too low, new requests queue up. Watch logs for `WARNING: [pool www] server reached pm.max_children setting`. Raise the limit or switch to `ondemand` mode.

## FAQ

### What's the difference between `ondemand`, `static`, and `dynamic` process managers?

- **static** — keeps a fixed number of workers always running. Predictable but wasteful on low-traffic sites.
- **dynamic** — adjusts workers between `min_spare_servers` and `max_children` based on demand. Best balance for most setups.
- **ondemand** — starts workers only when a request arrives, idles them out after `pm.process_idle_timeout`. Lowest memory usage at the cost of latency on cold requests.

### Can I use PHP-FPM with Nginx instead of Apache?

Yes. PHP-FPM is server-agnostic. Nginx uses `fastcgi_pass` instead of `SetHandler`, but the pool configuration is identical.

### How do I set different `php.ini` values per pool?

Use `php_admin_value` and `php_admin_flag` in the pool config:

```ini
php_admin_value[upload_max_filesize] = 64M
php_admin_value[post_max_size] = 64M
php_admin_value[memory_limit] = 256M
```

### Should I disable mod_php after switching to FPM?

Yes. If both mod_php and FPM are active, Apache may serve requests through mod_php instead of the proxy. Disable it:

```bash
sudo a2dismod php8.3
sudo systemctl restart apache2
```

### Does PHP-FPM support opcache?

Yes, and each PHP version maintains its own opcache. This is crucial when running multiple versions — opcache entries from PHP 8.0 don't pollute PHP 8.3's cache.

### How do I tune `pm.max_children` for production?

Monitor with `htop` and the FPM status page. Start with 50, watch memory usage during peak traffic, and adjust. A single well-tuned server running PHP-FPM can handle hundreds of concurrent requests.

### Is PHP-FPM more secure than mod_php?

Significantly. Process isolation means a compromised PHP script on one pool can't read files owned by another pool's user. Chroot support (via `chroot` in the pool config) takes this further by jailing the pool to a specific directory.

### Can PHP-FPM run on a different server from Apache?

Yes — this is a common pattern for horizontal scaling. Set `listen = 9000` on the FPM server and point Apache's `SetHandler` at the remote IP. Secure the connection with a firewall rule that only allows the web server through.

## Conclusion

PHP-FPM transforms Apache from a monolithic PHP host into a flexible, multi-version application server. You get process isolation, per-user permissions, version coexistence, and graceful process management — all without leaving Apache's ecosystem.

If you're still running mod_php in production, the migration path is straightforward. Install FPM alongside your existing setup, configure a single virtual host pointing at the socket, test thoroughly, then disable mod_php. The hard part — writing good PHP — stays the same. The infrastructure underneath just gets a lot more capable.

Now go configure that pool.
