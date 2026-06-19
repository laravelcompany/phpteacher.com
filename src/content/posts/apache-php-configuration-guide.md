---
title: "Apache and PHP Configuration Guide - Back to Basics"
description: "Master Apache and PHP configuration from the ground up. Learn virtual hosts, mod_php, PHP-FPM, SSL setup, and production-ready server configuration."
pubDate: "2022-01-18 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Apache", "PHP", "Web Server", "DevOps", "Configuration", "Virtual Hosts", "LAMP", "Ubuntu"]
selected: false
---

Web servers are the invisible backbone of the internet. Every time you load a page, submit a form, or stream a video, an Apache or Nginx process is working behind the scenes to serve that content. Despite being invisible to end users, the way you configure your web server directly determines your application's performance, security, and reliability.

If you're a PHP developer who has relied on all-in-one environments like XAMPP, Laravel Valet, or Laravel Herd and never peeked under the hood, this guide is for you. Understanding Apache and PHP configuration from the ground up isn't just an academic exercise — it's the skill that separates developers who can debug production outages from those who have to escalate them.

## What You'll Learn

By the end of this guide, you'll be able to:

- Install and configure Apache + PHP on Ubuntu 20.04/22.04
- Understand Apache's directory structure and configuration hierarchy
- Switch between PHP versions using both mod_php and PHP-FPM
- Create virtual hosts for multiple projects on a single server
- Set up SSL certificates for development and production
- Deploy a Laravel application with proper document root and permissions
- Avoid the most common Apache/PHP configuration pitfalls

Let's get our hands dirty.

## Installing Apache and PHP on Ubuntu

We'll target Ubuntu 20.04 LTS and 22.04 LTS, since together they power the majority of production PHP servers. The default Ubuntu repositories ship with PHP 8.0 (20.04) and PHP 8.1 (22.04), so you're starting from a solid place.

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 php libapache2-mod-php php-cli php-common php-mbstring php-xml php-curl php-zip php-bcmath php-json php-tokenizer
```

The key package here is `libapache2-mod-php`. This is Apache's `mod_php` — it embeds the PHP interpreter directly into the Apache worker process. When Apache receives a request for a `.php` file, `mod_php` hands it off to the PHP engine without needing an external FastCGI process. This is the simplest deployment model and it works great for single-server setups.

Once the packages are installed, start Apache and enable it to launch on boot:

```bash
sudo systemctl start apache2
sudo systemctl enable apache2
```

Verify Apache is running by hitting your server's IP address or `http://localhost` in a browser. You should see the default Apache welcome page. To confirm PHP is wired correctly, create a `phpinfo()` file:

```bash
echo "<?php phpinfo();" | sudo tee /var/www/html/info.php
```

Navigate to `http://localhost/info.php`. If you see the PHP information page, congratulations — Apache and PHP are talking to each other.

**Security note:** Delete `info.php` immediately after verifying. Leaving `phpinfo()` output accessible is a gift to attackers.

## Understanding Apache's Configuration Structure

One of the most intimidating aspects of Apache for newcomers is its configuration layout. Unlike Nginx's single-file approach, Apache uses a modular, directory-based system. Once you understand it, though, it's elegantly simple.

### The Directory Tree

Here's what matters inside `/etc/apache2/`:

```
/etc/apache2/
├── apache2.conf          # Main configuration file
├── ports.conf            # Listening ports (80, 443)
├── conf-enabled/         # Symlinks to conf-available/*.conf
├── mods-enabled/         # Symlinks to mods-available/*
├── sites-enabled/        # Symlinks to sites-available/*
├── conf-available/       # Available configuration fragments
├── mods-available/       # Available modules
└── sites-available/      # Available virtual hosts
```

The `*-enabled` directories contain symlinks to their `*-available` counterparts. Enabling a module, configuration, or site is the act of creating that symlink. You can do this manually with `ln -s`, but Apache provides helper commands that are safer:

- `a2enmod <module>` / `a2dismod <module>` — Enable/disable Apache modules
- `a2ensite <site>` / `a2dissite <site>` — Enable/disable virtual hosts
- `a2enconf <conf>` / `a2disconf <conf>` — Enable/disable configuration files

### The Main Configuration File

The heart of Apache lives in `apache2.conf`. It's surprisingly readable:

```apache
# /etc/apache2/apache2.conf
DefaultRuntimeDir ${APACHE_RUN_DIR}

PidFile ${APACHE_PID_FILE}

Timeout 300
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5

User ${APACHE_RUN_USER}
Group ${APACHE_RUN_GROUP}

HostnameLookups Off

ErrorLog ${APACHE_LOG_DIR}/error.log
LogLevel warn

IncludeOptional mods-enabled/*.load
IncludeOptional mods-enabled/*.conf
Include ports.conf
IncludeOptional conf-enabled/*.conf
IncludeOptional sites-enabled/*.conf
```

The `IncludeOptional` directives at the bottom are where the magic happens. They pull in all enabled modules, port configurations, custom config fragments, and virtual host definitions. The order matters — `mods-enabled` loads first so that modules are available when virtual host files are processed.

## Virtual Hosts: Hosting Multiple Projects

A virtual host is Apache's way of serving multiple websites from a single server instance. Each virtual host defines a `ServerName`, a `DocumentRoot`, and optionally its own logging, directory permissions, and SSL configuration.

### Creating a Virtual Host

Let's create a virtual host for a project called `myapp`. We'll put the application code in `/var/www/myapp` and its logs in `/var/log/apache2/myapp/`.

```bash
sudo mkdir -p /var/www/myapp/public
sudo mkdir -p /var/log/apache2/myapp
sudo chown -R $USER:$USER /var/www/myapp
```

Now create the virtual host configuration:

```apache
# /etc/apache2/sites-available/myapp.conf
<VirtualHost *:80>
    ServerName myapp.local
    ServerAdmin webmaster@myapp.local
    DocumentRoot /var/www/myapp/public

    ErrorLog ${APACHE_LOG_DIR}/myapp/error.log
    CustomLog ${APACHE_LOG_DIR}/myapp/access.log combined

    <Directory /var/www/myapp>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Key directives explained:

- **ServerName**: The domain name this virtual host responds to. Apache uses the `Host` header from the HTTP request to match requests to the correct virtual host.
- **DocumentRoot**: The directory Apache serves files from. For Laravel and most modern PHP frameworks, this should point to the `public/` directory.
- **ErrorLog / CustomLog**: Logs specific to this virtual host. Separating logs per project makes debugging infinitely easier.
- **AllowOverride All**: Allows `.htaccess` files to override configuration. Laravel's `public/.htaccess` handles URL rewriting for pretty URLs.

Enable the site and reload Apache:

```bash
sudo a2ensite myapp.conf
sudo systemctl reload apache2
```

### Local DNS with /etc/hosts

Since `myapp.local` isn't a real domain, you need to point it to your local machine:

```
# /etc/hosts
127.0.0.1   localhost
127.0.0.1   myapp.local
```

Now visit `http://myapp.local` — your virtual host is live.

## Switching PHP Versions with mod_php

The default Ubuntu repositories only carry one PHP version, but projects have different requirements. Maybe you're maintaining a legacy app stuck on PHP 7.4 while developing new projects on PHP 8.3.

Ondřej Surý maintains a PPA that packages multiple PHP versions for Ubuntu. Add it and install the version you need:

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.3 libapache2-mod-php8.3 php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip
```

When using `mod_php`, only one PHP module can be active in Apache at a time. Switching is straightforward:

```bash
sudo a2dismod php8.1
sudo a2enmod php8.3
sudo systemctl restart apache2
```

This is the primary limitation of `mod_php` — you can't run different PHP versions for different virtual hosts on the same Apache server. If you need that capability, you need PHP-FPM.

## PHP-FPM: Running Multiple PHP Versions

PHP-FPM (FastCGI Process Manager) runs PHP as a separate service that Apache communicates with over a Unix socket or TCP port. This decouples the PHP runtime from the web server, giving you several advantages:

- Run different PHP versions per virtual host
- Better process management and pooling
- Improved performance under high concurrency
- Isolation between PHP processes

### Installing PHP-FPM

```bash
sudo apt install php8.3-fpm
sudo apt install php8.1-fpm  # Second version if needed
```

Enable the proxy modules Apache needs to communicate with FPM:

```bash
sudo a2enmod proxy_fcgi setenvif
sudo systemctl restart apache2
```

### Configuring a Virtual Host for PHP-FPM

```apache
<VirtualHost *:80>
    ServerName myapp.local
    DocumentRoot /var/www/myapp/public

    <Directory /var/www/myapp>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Route PHP requests to PHP-FPM via Unix socket
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/var/run/php/php8.3-fpm.sock|fcgi://localhost/"
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/myapp/error.log
    CustomLog ${APACHE_LOG_DIR}/myapp/access.log combined
</VirtualHost>
```

The `SetHandler` directive tells Apache to proxy any request ending in `.php` to the PHP-FPM socket. If you wanted to use PHP 8.1 for a different virtual host, you'd simply point its `SetHandler` to the `php8.1-fpm.sock` socket instead.

## SSL Configuration

### Self-Signed Certificates for Development

For local development, self-signed certificates are fine. Generate one with OpenSSL:

```bash
sudo mkdir -p /etc/apache2/ssl
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/myapp.key \
    -out /etc/apache2/ssl/myapp.crt \
    -subj "/C=US/ST=State/L=City/O=Organization/CN=myapp.local"
```

Create an SSL virtual host:

```apache
<VirtualHost *:443>
    ServerName myapp.local
    DocumentRoot /var/www/myapp/public

    SSLEngine on
    SSLCertificateFile /etc/apache2/ssl/myapp.crt
    SSLCertificateKeyFile /etc/apache2/ssl/myapp.key

    <Directory /var/www/myapp>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/myapp/error.log
    CustomLog ${APACHE_LOG_DIR}/myapp/access.log combined
</VirtualHost>
```

Enable SSL and the virtual host:

```bash
sudo a2enmod ssl
sudo a2ensite myapp-ssl.conf
sudo systemctl reload apache2
```

You'll get a browser warning about the self-signed certificate — that's expected. For local development, it's safe.

### Let's Encrypt for Production

For production, never use self-signed certificates. Let's Encrypt provides free, automated SSL certificates via Certbot:

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d myapp.com -d www.myapp.com
```

Certbot will automatically modify your Apache virtual hosts to serve SSL and set up automatic renewal via a systemd timer. Verify renewal works:

```bash
sudo certbot renew --dry-run
```

## Real-World Use Cases

### Local Laravel Development

Setting up Laravel with Apache is straightforward once you have virtual hosts configured:

```bash
composer create-project laravel/laravel /var/www/myapp
sudo chown -R www-data:www-data /var/www/myapp/storage
sudo chown -R www-data:www-data /var/www/myapp/bootstrap/cache
```

Point your virtual host's `DocumentRoot` to `/var/www/myapp/public`, enable the site, and you're set. Laravel's `public/.htaccess` handles all URL rewriting — Apache just needs `AllowOverride All` on the document root directory.

### Multi-Site Hosting for Agencies

If you're a freelancer or agency hosting multiple client sites on a single VPS, virtual hosts are your best friend. Each client gets their own:

- Virtual host configuration file
- Document root directory
- Log files
- PHP version (if using FPM)
- SSL certificate

This isolation means one client's traffic spike or security issue doesn't affect the others.

### Staging and Production Parity

One of the most common causes of deployment bugs is environment drift — differences between your local setup and production. By running Apache locally with the same configuration patterns you use in production, you eliminate an entire class of surprises. Tools like Ansible or Envoy can template your Apache virtual hosts so that local, staging, and production configurations stay in sync.

## Best Practices

### Security Headers

Hardening Apache doesn't require a security degree. Start with these headers in your virtual host or a global config:

```apache
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

### DocumentRoot Permissions

Your web files should be owned by your user account with group ownership set to `www-data`:

```bash
sudo chown -R $USER:www-data /var/www/myapp
sudo chmod -R 755 /var/www/myapp
sudo chmod -R 775 /var/www/myapp/storage
```

The `storage` directory needs group write access so that Laravel can write logs and cache files.

### Log Management

Logs grow without bound. Rotate them with `logrotate`:

```
# /etc/logrotate.d/apache2
/var/log/apache2/myapp/*.log {
    weekly
    missingok
    rotate 12
    compress
    delaycompress
    notifempty
    create 640 root adm
    sharedscripts
    postrotate
        /etc/init.d/apache2 reload > /dev/null
    endscript
}
```

## Common Mistakes to Avoid

### Permissions Errors

The most common Apache/PHP issue is a permissions problem. If you see "Access denied!" or a blank white page, check these first:

- Does the `www-data` user have read access to the files?
- Does `www-data` have write access to `storage/` and `bootstrap/cache/`?
- Is SELinux or AppArmor blocking Apache? Check `audit.log` or run `sudo aa-status`.

### Forgetting to Enable Sites and Modules

Creating a configuration file in `sites-available` does nothing until you run `a2ensite`. Similarly, you need to enable modules explicitly. Always run `sudo systemctl reload apache2` and check for syntax errors:

```bash
sudo apache2ctl configtest
```

### SSL Misconfiguration

If your browser shows "ERR_SSL_PROTOCOL_ERROR", the issue is almost always one of these:

- The SSL virtual host is listening on port 443 but `mod_ssl` isn't enabled
- The certificate and key files aren't readable by the Apache process
- You're missing a `ServerName` directive and Apache is falling back to the default host

### Mixing mod_php and PHP-FPM

If you have both `mod_php` and PHP-FPM enabled for the same virtual host, you'll get unpredictable behavior. Either remove the `mod_php` module with `a2dismod` or ensure your FPM virtual host explicitly handles `.php` files with `SetHandler` while the mod_php host uses the default handler.

## Comparison with Modern Alternatives

The LAMP stack isn't the only game in town anymore. Here's how it stacks up against popular alternatives:

**Laravel Valet** — Valet runs Nginx in the background and dynamically resolves project directories using DNS in `/etc/hosts`. It's macOS-only and designed for single-user development. Valet is faster to set up than Apache but hides all the configuration details. Great for prototyping, less great for learning.

**Laravel Herd** — Herd is the evolution of Valet, available for both macOS and Windows. It provides a GUI for managing PHP versions and services. Like Valet, it abstracts away the underlying server configuration entirely.

**Laravel Sail** — Sail runs PHP, Apache/Nginx, MySQL, and Redis in Docker containers. It delivers environment parity better than any native setup, but at the cost of additional complexity and resource overhead.

If your goal is to learn how web servers actually work, nothing beats configuring Apache by hand. If your goal is to ship a product quickly, Herd or Sail will be more productive. Neither choice is wrong — but knowing what's happening under the hood makes you a better developer regardless of which tool you reach for.

## Frequently Asked Questions

**Q: Should I use mod_php or PHP-FPM?**

A: For single-server, single-application setups, `mod_php` is simpler and performs well. For multi-application servers or when you need different PHP versions per site, use PHP-FPM. For high-traffic sites, PHP-FPM's process management scales better.

**Q: How do I change the PHP version globally on my server?**

A: With `mod_php`, run `sudo a2dismod php8.1 && sudo a2enmod php8.3 && sudo systemctl restart apache2`. With PHP-FPM, change the socket path in each virtual host's `SetHandler` directive and restart FPM with `sudo systemctl restart php8.3-fpm`.

**Q: Why am I seeing a 403 Forbidden error?**

A: Check that `Require all granted` is set in the `<Directory>` block for your document root, and that the `www-data` user can read the files. Also verify the `DocumentRoot` path is correct and the directory exists.

**Q: Do I need `.htaccess` files if I have server config access?**

A: No. In fact, if you have access to the virtual host configuration, you should put rules there instead of `.htaccess`. Apache checks `.htaccess` on every request, which adds overhead. Disable `.htaccess` with `AllowOverride None` and put rewrite rules in the `<Directory>` block directly.

**Q: Can I run Apache and Nginx on the same server?**

A: Not on ports 80 and 443 simultaneously. You could run Apache on 8080 and Nginx on 80 as a reverse proxy, but for learning purposes, pick one and master it.

**Q: How do I secure a production Apache server?**

A: At minimum: disable directory listing (`Options -Indexes`), remove the Apache version banner (`ServerTokens Prod` + `ServerSignature Off`), enable HTTPS with Let's Encrypt, set security headers, and keep PHP and Apache updated via `unattended-upgrades`.

**Q: What's the difference between `AllowOverride All` and `AllowOverride None`?**

A: `All` allows `.htaccess` files to override virtually any directive. `None` ignores `.htaccess` entirely, improving performance. Use `All` only when you need `.htaccess` (e.g., Laravel's URL rewriting) and use `None` for static file directories.

**Q: How do I debug a 500 Internal Server Error?**

A: Check Apache's error log at `/var/log/apache2/error.log` first. Enable PHP error display in your virtual host: `php_flag display_errors on`. If using PHP-FPM, check `/var/log/php8.x-fpm.log`. Run `sudo apache2ctl configtest` to rule out syntax errors.

## Conclusion

Apache and PHP remain the backbone of a massive portion of the web. Understanding how they work — not just how to install them — gives you the ability to debug problems, optimize performance, and make informed architectural decisions. Whether you're running a single Laravel site on a $5 VPS or managing a fleet of application servers, the fundamentals covered here will serve you every day.

The web may be moving toward serverless and containers, but someone still has to configure the servers that run those containers. Make that person you.

Now go enable some modules and break things. That's how you learn.
