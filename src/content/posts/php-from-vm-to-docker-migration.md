---
title: "PHP From VM to Docker - Migrating Your Development Environment"
seoTitle: "PHP From VM to Docker - Migrating Your Development Environment"
description: "Migrate your PHP development environment from VM to Docker. Learn containerization benefits and practical migration strategies."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-07-18 21:00:00"
tags: ["Docker", "PHP", "VM", "Development", "DevOps", "Containers"]
---

Containers are here to stay. If you've been running PHP applications on Vagrant-managed virtual machines and wondering how to make the leap to Docker, this guide walks through a real migration. We'll take a Laravel application running on Ubuntu via Apache and MariaDB with Redis caching and containerize every piece.

## Why Docker Compose Instead of Raw Docker?

Docker Compose is a tool for defining and running multi-container applications. Once you move past `docker run -it` basics, Compose makes managing container services dramatically easier than tracking arbitrary `docker run` commands. It gives you declarative service definitions, environment management, and orchestration in a single YAML file.

Why not Kubernetes? While k8s has become the de facto standard for container orchestration at scale, it's overkill for most PHP applications. Docker Compose is the right starting point. It lets you grasp the fundamentals of shifting services from running directly on a host to running each in their own container, and you can pick up and move the entire stack to any host that supports Docker Compose.

## The Application

Our application is a user management system that serves an API consumed by an on-site authentication system guarding physical access to a shared workshop. Admins associate RFID tokens with users, and users manage their information via the web application.

Currently, it runs on an Ubuntu VM with PHP 7.4, MariaDB, and Redis. We're migrating this VM setup to Docker Compose.

## Building the Application Image

The source of your container image is the Dockerfile. It reads like a shell script but uses specific syntax to provision containers just as you would a traditional server.

```dockerfile
FROM ubuntu:20.04

RUN export DEBIAN_FRONTEND=noninteractive; \
    export DEBCONF_NONINTERACTIVE_SEEN=true; \
    echo 'tzdata tzdata/Areas select Etc' | debconf-set-selections; \
    echo 'tzdata tzdata/Zones/Etc select UTC' | debconf-set-selections; \
    apt-get update -qqy && apt-get install -qqy software-properties-common \
    && add-apt-repository -y ppa:ondrej/php \
    && apt-get install -qqy --no-install-recommends \
        apt-utils apache2 apache2-bin libapache2-mod-php8.1 \
        php8.1-curl php8.1-mysql php8.1-gd php8.1-xml \
        php8.1-mbstring php8.1-zip php8.1-bcmath \
        curl wget git cron unzip \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN useradd -m --uid 1000 --gid 50 docker
RUN echo export APACHE_RUN_USER=docker >> /etc/apache2/envvars
RUN echo export APACHE_RUN_GROUP=staff >> /etc/apache2/envvars

COPY docker/000-default.conf /etc/apache2/sites-enabled/000-default.conf

RUN a2enmod rewrite

COPY . /var/www/html
WORKDIR /var/www/html

COPY docker/docker.env /var/www/html/.env
RUN chown -R docker /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

USER docker
RUN composer install --no-dev --working-dir=/var/www/html
USER root

COPY docker/startup.sh /
RUN chmod +x /startup.sh

CMD ["/startup.sh"]
EXPOSE 80
```

Build the image:

```bash
docker build . -t myapp/php-app
```

### Key Dockerfile Decisions

**Base image**: Ubuntu 20.04 LTS gives you a traditional Linux environment in the container. Using a familiar distribution reduces the learning curve when migrating from VMs.

**Ondrej PPA**: PHP installations via apt on Ubuntu are outdated. The Ondrej PPA provides current PHP versions—in this case, we've moved from PHP 7.4 (the VM) to PHP 8.1 (the container). This migration is a perfect time to upgrade PHP versions.

**Composer multi-stage**: `COPY --from=composer:latest /usr/bin/composer /usr/bin/composer` pulls the Composer binary from the official Composer image without installing PHP dependencies in the build phase. This is a multi-stage build pattern that keeps the final image lean.

**User management**: Creating a `docker` user and configuring Apache to run as that user prevents permission headaches with mounted volumes and log files.

## Supporting Services

### MariaDB Configuration

```yaml
services:
  mariadb:
    image: mariadb:10.7.4-focal
    volumes:
      - type: volume
        source: mariadb-data
        target: /var/lib/mysql
        volume:
          nocopy: true
    ports:
      - "33060:3306"
    healthcheck:
      test: mysqladmin ping -h 127.0.0.1 -u $$MYSQL_USER --password=$$MYSQL_PASSWORD
      interval: 3s
      timeout: 1s
      retries: 5
    env_file:
      - docker/docker.env
```

The healthcheck is critical. It tells Docker how to test if the container is working correctly. `mysqladmin ping` runs every 3 seconds with a 1-second timeout, retrying 5 times. This lets dependent services wait for the database to be truly ready before connecting.

### Redis Configuration

```yaml
  redis:
    image: redis:6.2.5-buster
    ports:
      - "6379:6379"
    volumes:
      - type: volume
        source: redis-data
        target: /data
        volume:
          nocopy: true
```

Redis starts fast, so the health condition for dependencies is simply `service_started` rather than the more rigorous healthcheck.

## The Environment File

```env
# MariaDB Parameters
MYSQL_ROOT_PASSWORD=your_secure_root_password
MYSQL_DATABASE=app
MYSQL_USER=app
MYSQL_PASSWORD=your_secure_app_password

# Application Environment
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=your_secure_app_password
APP_URL=https://app.local
APP_KEY=your_app_key
```

Note the hostname `mariadb` for `DB_HOST`. Docker Compose creates a network where services are reachable by their service name, so your application connects to the database using the service name as the host.

## Complete Docker Compose Configuration

```yaml
version: '3'

services:
  app:
    image: myapp/php-app:latest
    volumes:
      - ./logs:/var/www/html/storage/logs
    ports:
      - "8000:80"
    depends_on:
      mariadb:
        condition: service_healthy
      redis:
        condition: service_started
    env_file:
      - docker/docker.env

  mariadb:
    image: mariadb:10.7.4-focal
    volumes:
      - type: volume
        source: mariadb-data
        target: /var/lib/mysql
    ports:
      - "33060:3306"
    healthcheck:
      test: mysqladmin ping -h 127.0.0.1 -u $$MYSQL_USER --password=$$MYSQL_PASSWORD
      interval: 3s
      timeout: 1s
      retries: 5
    env_file:
      - docker/docker.env

  redis:
    image: redis:6.2.5-buster
    ports:
      - "6379:6379"
    volumes:
      - type: volume
        source: redis-data
        target: /data

volumes:
  mariadb-data:
  redis-data:
```

## Running the Stack

```bash
docker compose up
```

Docker Compose starts services in dependency order. Redis starts first (no dependencies), then MariaDB. The application waits for MariaDB's healthcheck to pass before starting. The `startup.sh` script in the application container handles any runtime setup—file ownership, directory creation, and database migrations.

```bash
docker compose up -d  # Run in detached mode
docker compose logs -f  # Follow logs from all services
docker compose logs app  # Logs from a specific service
```

## Running Artisan Commands

With the application containerized, running Artisan commands requires telling Docker to execute them inside the running container:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan cache:clear
docker compose exec app php artisan tinker
```

You can create shell aliases to simplify this:

```bash
alias art='docker compose exec app php artisan'
art migrate
art queue:work
```

## Connecting to MariaDB from the Host

The container exposes MariaDB on port 33060 (mapped to 3306 inside the container). This avoids conflicts with any local MySQL/MariaDB installation:

```bash
mysql -h 127.0.0.1 -u root -p -P 33060
```

## Development Considerations

**Volumes for live code reloading**: Mount your application source code as a volume in development so changes reflect immediately without rebuilding:

```yaml
volumes:
  - ./app:/var/www/html
```

**Xdebug**: Configure Xdebug in your Dockerfile and map the IDE key and host IP in environment variables:

```dockerfile
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug
```

**Mailpit for email testing**: Add a Mailhog or Mailpit service for catching emails in development:

```yaml
  mailpit:
    image: axllent/mailpit:latest
    ports:
      - "1025:1025"
      - "8025:8025"
```

## Docker Compose for Symfony Applications

If you're migrating a Symfony application instead of Laravel, the same patterns apply with minor adjustments:

```yaml
services:
  php:
    image: myapp/php:latest
    volumes:
      - ./app:/var/www/html
    depends_on:
      database:
        condition: service_healthy

  nginx:
    image: nginx:stable-alpine
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
      - ./app/public:/var/www/html/public
    ports:
      - "8000:80"
    depends_on:
      - php

  database:
    image: postgres:14-alpine
    volumes:
      - postgres-data:/var/lib/postgresql/data
    environment:
      POSTGRES_DB: app
      POSTGRES_USER: app
      POSTGRES_PASSWORD: secret
    healthcheck:
      test: pg_isready -U app
      interval: 5s
      timeout: 3s
      retries: 5
```

Symfony uses Nginx + PHP-FPM instead of Apache + mod_php. The PHP container runs PHP-FPM, and the Nginx container proxies requests to it. This separation lets you scale PHP and Nginx independently based on load.

## CI/CD with Docker Compose

Your migration isn't complete until you can build and deploy automatically. Here's a GitHub Actions workflow that builds your application image and deploys via Compose:

```yaml
name: Build and Deploy

on:
  push:
    branches: [main]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v2

      - name: Log in to registry
        uses: docker/login-action@v2
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Build and push
        uses: docker/build-push-action@v4
        with:
          context: .
          push: true
          tags: ghcr.io/myorg/myapp:latest

  deploy:
    needs: build
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to server
        uses: appleboy/ssh-action@v0.1.5
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          username: ${{ secrets.DEPLOY_USER }}
          key: ${{ secrets.DEPLOY_KEY }}
          script: |
            cd /opt/myapp
            docker compose pull
            docker compose up -d --force-recreate
            docker image prune -f
```

## Troubleshooting Common Migration Issues

### Container Can't Connect to Database

Verify the database service name in `docker-compose.yml` matches the `DB_HOST` in your environment file. Docker Compose's internal DNS resolves service names automatically, but only if the service is defined and running.

```bash
# Verify connection from inside the container
docker compose exec app php -r "
    try {
        new PDO('mysql:host=mariadb;dbname=app', 'app', 'secret');
        echo 'Connected!' . PHP_EOL;
    } catch (PDOException \$e) {
        echo 'Failed: ' . \$e->getMessage() . PHP_EOL;
    }
"
```

### Permission Denied Errors

File permission issues are the most common Docker migration problem. Inside the container, PHP needs write access to storage directories. The solution: match the container user ID to your host user ID.

```dockerfile
# In Dockerfile
ARG UID=1000
RUN useradd -m --uid ${UID} --gid 50 docker
```

```yaml
# In docker-compose.yml
services:
  app:
    build:
      context: .
      args:
        UID: "${UID:-1000}"
```

### Volumes Not Mounting

If your application changes don't appear in the container, check volume paths. They must be absolute paths or relative paths from the location of the `docker-compose.yml` file:

```yaml
volumes:
  - ./app:/var/www/html  # relative to docker-compose.yml
  - /absolute/path:/container/path  # absolute path
```

### Port Conflicts

If port 80 is already in use, change the host-side mapping:

```yaml
ports:
  - "8080:80"  # access via localhost:8080
```

The container still listens on port 80, but the host maps it to 8080. Other services like `mariadb:33060` intentionally use non-standard ports to avoid conflicts with local installations.

## Production Hardening

- Use a reverse proxy like Traefik or Nginx Proxy Manager for SSL termination
- Implement proper database backup and restoration procedures
- Pin image versions (don't use `:latest` in production)
- Use Docker secrets or a secret management service for sensitive values
- Set memory and CPU limits on containers
- Run as non-root user inside containers
- Scan images for vulnerabilities with Docker Scout or Trivy

## What's Next?

This migration is still early in the container journey. Next steps include:

- Running the Compose file as a Docker Stack (Docker Swarm mode) for production stability
- Implementing CI/CD with GitHub Actions to build and push images
- Setting up healthcheck endpoints in your Laravel/Symfony application
- Using Docker's built-in logging drivers to centralize logs
- Adding horizontal scaling with Docker Compose's `replicas` configuration

## Conclusion

Migrating from VMs to Docker Compose simplifies deployment, development environment consistency, and service management. The key is treating each service as an independent, composable unit with clear health checks and dependency ordering. Start with Docker Compose, get comfortable with the patterns, and you'll have a solid foundation whether you stay with Compose or eventually move to Kubernetes.

Your VM-based PHP application can run in containers today. Build the Dockerfile, define the services in Compose, and test the stack locally. Once it works on your machine, it works everywhere Docker runs. That consistency alone makes the migration worthwhile.
