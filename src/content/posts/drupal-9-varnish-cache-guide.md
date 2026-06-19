---
title: "Drupal 9 and Varnish - Caching Performance Guide"
description: "Learn how to install, configure, and optimize Varnish cache with Drupal 9 for dramatically improved performance, including VCL configuration and cache invalidation."
pubDate: "2022-09-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Drupal", "Varnish", "Caching", "PHP", "Performance"]
selected: false
---

How many times have you looked at a newly completed portal and wondered why it is so slow? This problem affects countless CMS and e-commerce sites. The solution often involves Varnish Cache — a powerful HTTP accelerator that sits between the browser and your web server, serving cached versions of pages to reduce load on the backend.

This article covers installing Varnish, configuring it for Drupal 9, and setting up cache invalidation so content updates appear immediately while static pages fly out of the cache.

## Installing Varnish

The following assumes Ubuntu 21.10 with Apache and Drupal 9 already installed via Composer.

```bash
# Check available version
apt info varnish
# Install Varnish
apt install varnish
```

After installation, swap the ports. The browser expects your site on port 80. Varnish will listen on port 80, and Apache will move to port 8080.

```bash
service apache stop
service varnish stop
```

Change Apache's port in `/etc/apache2/sites-enabled/000-default.conf`:

```apache
<VirtualHost *:8080>
    # ... rest of configuration
</VirtualHost>
```

Change Varnish's port in `/etc/systemd/system/multi-user.target.wants/varnish.service`. Find the `-a` argument and set it to `:80`.

```bash
service apache start
service varnish start
```

Varnish now accepts requests on port 80 and proxies them to Apache on port 8080.

## Installing Drupal Contrib Modules

Drupal communicates with Varnish through the Purge module ecosystem — a modular external cache invalidation framework.

### Purge Module

This is the foundation. It manages invalidation of external caching systems, reverse proxies, and CDNs as content changes.

```bash
composer require drupal/purge
drush en purge
drush en purge_drush
drush en purge_tokens
drush en purge_ui
drush en purge_processor_cron
drush en purge_queuer_coretags
drush en purge_processor_lateruntime
```

### Generic HTTP Purger

Provides HTTP-based purging support:

```bash
composer require drupal/purge_purger_http
drush en purge_purger_http
drush en purge_purger_http_tagsheader
```

### Varnish Purger

The Varnish-specific implementation:

```bash
composer require drupal/varnish_purge
drush en varnish_purger
drush en varnish_focal_point_purge
drush en varnish_image_purge
drush en varnish_purge_tags
```

## Configuring Cache and Purge

### Set TTL

Navigate to `/admin/config/development/performance`. Set **Browser and proxy cache maximum age** to a long value like one year (31536000 seconds). When content is updated, cache invalidation handles removing it — the TTL is the fallback maximum, not the actual expiration for most content.

### Configure Purge

Go to the Purge section at `/admin/config/development/performance/purge`:

1. **Add a purger** — Select "Varnish Purger"
2. **Configure the purger** — Give it a name, set it to purge everything. Configure the hostname and port where Varnish is listening
3. **Configure the queue** — Queuers add items to the queue when content changes. Processors empty the queue and trigger purgers. Mid-to-high traffic sites should use cron-based processing

### VCL Configuration

Drupal needs custom VCL (Varnish Configuration Language) to handle cache tags correctly:

```vcl
vcl 4.0;

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

sub vcl_recv {
    # Send purge requests directly to the backend
    if (req.method == "PURGE") {
        return (purge);
    }

    # Bypass cache for Drupal admin paths
    if (req.url ~ "^/admin" || req.url ~ "^/user") {
        return (pass);
    }

    # Remove cookies for anonymous users
    if (req.http.cookie !~ "DRUPAL_UID" && req.http.Authorization !~ "^Basic") {
        unset req.http.cookie;
    }

    # Remove trailing ? and parameters from static files
    if (req.url ~ "\.(css|js|png|gif|jpg|jpeg|ico|svg|woff2)$") {
        unset req.http.cookie;
    }
}

sub vcl_backend_response {
    # Cache tagged responses from Drupal
    if (beresp.http.X-Drupal-Cache-Tags) {
        set beresp.ttl = 1w;
        unset beresp.http.Set-Cookie;
    }

    # Static files get maximum TTL
    if (bereq.url ~ "\.(css|js|png|gif|jpg|jpeg|ico|svg|woff2)$") {
        set beresp.ttl = 365d;
    }
}

sub vcl_deliver {
    # Add debugging headers
    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
    } else {
        set resp.http.X-Cache = "MISS";
    }
}
```

## Cache Invalidation with Tags

Drupal 9's cache tag system is the key to efficient invalidation. Every rendered element — node, block, view, entity — is associated with cache tags. When content changes, Drupal sends a `BAN` request for the specific tags, Varnish matches them against cached objects, and removes only the affected items.

This means you can cache a page for months without worrying about stale content. When you update a node, only pages containing that node are invalidated. The rest stay cached.

## Verifying It Works

Request a page and check the response headers:

```
X-Cache: MISS
X-Drupal-Cache-Tags: node:1, node_list, config:system.core
```

The first request is a miss. The second request:

```
X-Cache: HIT
```

Varnish is serving the cached copy. Apache and PHP never touched the request.

## Performance Tuning

### Memory

Varnish allocates a fixed amount of memory for storage. For a Drupal site with heavy traffic, start with 1-2 GB:

```
# In /etc/varnish/varnish.service
ExecStart=/usr/sbin/varnishd -a :80 -f /etc/varnish/default.vcl -s malloc,2g
```

### Grace Mode

Serve stale content while refreshing in the background:

```vcl
sub vcl_backend_response {
    set beresp.grace = 24h;
}
```

If the backend is slow or down, Varnish serves stale content while fetching a fresh version in the background. Users never see errors.

### Saint Mode

Mark failing backends as "sick" temporarily:

```vcl
sub vcl_backend_response {
    if (beresp.status >= 500) {
        set beresp.saintmode = 10s;
        return (retry);
    }
}
```

## Requirements

- PHP 7.3+ (PHP 8 supported from Drupal 9.1.0)
- Apache 2.4.7+
- Varnish 6.0
- Drush 10+ (PHP 7.1+) or Drush 11+ (PHP 7.4+)
- Composer

## Summary

Varnish transforms Drupal 9 performance. With proper configuration, anonymous traffic hits Varnish memory instead of Apache and PHP. The purge module ecosystem ensures content updates propagate instantly through cache invalidation.

The VCL configuration controls every aspect of caching — which URLs to cache, how long to keep content, how to handle cookies, and what to do when the backend fails. Combined with Drupal's cache tags, you get surgical invalidation that removes only what changed.

Install Varnish, configure the purge modules, tune the VCL, and watch your response times drop from seconds to milliseconds.
