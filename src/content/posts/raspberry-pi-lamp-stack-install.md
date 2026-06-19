---
title: "Install a LAMP Stack on Raspberry Pi - Apache, MySQL, PHP Guide"
description: "Step-by-step guide to installing a full LAMP stack on your Raspberry Pi. From SSH setup to Apache, MariaDB, PHP, and Adminer - build a web server on $35 hardware."
pubDate: "2022-02-18 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Raspberry Pi", "LAMP", "Apache", "MySQL", "PHP", "MariaDB", "Adminer", "IoT", "Linux"]
selected: false
---

# Install a LAMP Stack on Raspberry Pi — Apache, MariaDB, PHP, and Adminer

You can buy a web server for $35. It will sip 5 watts of power, make no noise, and fit in the palm of your hand. It won't handle a million concurrent users, but it doesn't need to — it's for something far more interesting.

The Raspberry Pi running a LAMP stack is the perfect prototyping environment for PHP developers. Need to spin up a quick API for a weekend project? Want to test a Laravel deployment pipeline before pushing to production? Building an internal dashboard for your home automation system? The Pi handles all of it.

This guide walks you through installing a complete LAMP stack — Linux, Apache, MariaDB (a drop-in MySQL replacement), and PHP — on a Raspberry Pi running Raspberry Pi OS. By the end, you'll have a fully functional web server that you can SSH into from anywhere on your network, serving dynamic PHP pages backed by a relational database.

And this is Part 2 of the series. If you haven't set up your Pi yet — headless SSH, Wi-Fi configuration, Raspberry Pi OS Lite — go back and read [Part 1](/posts/raspberry-pi-home-hacking-guide). You'll need a running Pi with SSH access before you start here.

---

## What You'll Learn

By the end of this guide, you will have:

- SSH access to your Raspberry Pi from your development machine
- Apache 2 installed and serving web pages
- MariaDB running with a secured root account and a test database
- PHP 7.4 (or 8.x via the Ondrej PPA) integrated with Apache through `mod_php`
- A dynamic PHP page serving content from your database
- Adminer installed for browser-based database management
- A clear mental model of how each LAMP layer fits together on ARM hardware

If you've ever configured a LAMP stack on an Ubuntu VPS, this will feel familiar — with a few Pi-specific quirks that we'll cover explicitly.

---

## Step 1 — SSH Into Your Raspberry Pi

Assuming you configured your Pi with SSH enabled and Wi-Fi credentials baked in (as covered in Part 1), your Pi should be on your network and reachable via mDNS.

From your development machine, open a terminal:

```bash
ssh pi@raspberrypi.local
```

If you changed the hostname during setup, substitute `raspberrypi.local` with your hostname. If mDNS isn't working on your network, find the Pi's IP address from your router's DHCP client list and use that instead:

```bash
ssh pi@192.168.1.XXX
```

The first time you connect, you'll see the typical SSH fingerprint prompt. Verify it and accept. You're now sitting at a terminal on a $35 Linux computer.

### Before You Do Anything — Update

Fresh installs of Raspberry Pi OS ship with package lists that are weeks or months old. Always run updates immediately:

```bash
sudo apt update && sudo apt upgrade -y
```

This pulls down the latest package indexes and upgrades every installed package. On a Pi Zero W, this can take 10–15 minutes. On a Pi 4 or 5, it's significantly faster. Let it finish. Reboot if the kernel was updated:

```bash
sudo reboot
```

Then SSH back in.

---

## Step 2 — Install Apache

Apache 2 is in the default Raspberry Pi OS repositories and installs without any configuration tweaks:

```bash
sudo apt install apache2 -y
```

That's it. Apache starts immediately and is configured to launch on boot. Verify it's running:

```bash
sudo systemctl status apache2
```

You should see `active (running)` in green. If you see `active (exited)`, the service stopped — check the logs with `journalctl -u apache2`.

### Verify Apache from Your Browser

Find your Pi's IP address:

```bash
hostname -I
```

Open a browser on your development machine and navigate to `http://<PI_IP_ADDRESS>`. You should see the default Apache2 Debian default page — a white page with the Apache logo and some basic text.

If you don't see it, check two things:
- Your development machine and the Pi are on the same network
- No firewall is blocking port 80 (Raspberry Pi OS ships with no firewall by default, so this usually isn't the issue)

### Replace the Default Page with Bootstrap

The default Apache page lives at `/var/www/html/index.html`. Replace it with something that looks like an actual web page. On your development machine, create a file called `index.html`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pi LAMP Server</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
    <div class="container text-center mt-5">
        <h1>Your Raspberry Pi LAMP Server</h1>
        <p class="lead">Apache is running. Next up: MariaDB and PHP.</p>
        <div class="alert alert-info mt-4">
            IP: <span id="ip">detecting...</span>
        </div>
    </div>
    <script>
        fetch('/api/ip.php')
            .then(r => r.text())
            .then(ip => document.getElementById('ip').textContent = ip);
    </script>
</body>
</html>
```

Copy it to the Pi using SCP:

```bash
scp index.html pi@raspberrypi.local:/var/www/html/index.html
```

Refresh your browser. You should see your custom Bootstrap-styled page. If you get a permission denied error, the file ownership on `/var/www/html/` might be wrong. Fix it:

```bash
sudo chown -R pi:www-data /var/www/html/
sudo chmod -R 775 /var/www/html/
```

This grants your `pi` user write access while keeping the files readable by Apache's `www-data` group.

---

## Step 3 — Install MariaDB (MySQL Replacement)

MariaDB is a fully compatible drop-in replacement for MySQL. The Raspberry Pi OS repositories ship MariaDB, not Oracle MySQL, and this is the recommended choice for ARM Linux.

```bash
sudo apt install mariadb-server -y
```

Verify the installation:

```bash
sudo systemctl status mysql
```

Yes, the service is named `mysql` even though it's MariaDB. This is intentional — MariaDB maintains command-line and service-name compatibility with MySQL so that all your existing tools work without modification.

### Secure the Installation

Run the MySQL command-line client:

```bash
sudo mysql
```

You're now in the MariaDB monitor. The prompt changes to `MariaDB [(none)]>`. Run your first query:

```sql
SHOW DATABASES;
```

You should see the default databases: `information_schema`, `mysql`, `performance_schema`, and `sys`.

Now secure the installation. MariaDB does not install the `mysql_secure_installation` script by default on Raspberry Pi OS, so you need to handle security manually:

```sql
-- Set a root password (replace 'your_password' with a real password)
ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_password';

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Disable remote root login
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- Remove test database
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';

-- Apply changes
FLUSH PRIVILEGES;
```

Exit the monitor:

```sql
EXIT;
```

Now when you connect, you'll need a password:

```bash
mysql -u root -p
```

Enter the password you set. If you're authenticated, everything is working.

### Create a Test Database and User

Create a database for your PHP application:

```sql
CREATE DATABASE pi_lamp;
CREATE USER 'pi_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON pi_lamp.* TO 'pi_user'@'localhost';
FLUSH PRIVILEGES;
```

Verify the new user can connect:

```bash
mysql -u pi_user -p pi_lamp
```

```sql
SHOW TABLES;
-- Should be empty
EXIT;
```

---

## Step 4 — Install PHP

The default Raspberry Pi OS repositories ship PHP 7.4. This is not the latest version — PHP 8.0, 8.1, and 8.2 are available — but 7.4 is stable, well-tested on ARM, and sufficient for learning. We'll cover upgrading to 8.x afterward.

Install PHP and the MySQL connector:

```bash
sudo apt install php php-mysql -y
```

Verify the version:

```bash
php -v
```

You should see output like `PHP 7.4.28` (the exact patch version depends on when you're reading this). The `php-mysql` extension provides the `mysqli` and `PDO_mysql` drivers that PHP needs to talk to MariaDB.

Apache needs to be restarted to load the PHP module:

```bash
sudo systemctl restart apache2
```

### Enable Display Errors for Debugging

When you're developing, you want to see PHP errors in the browser rather than staring at a blank white screen of death. Edit the PHP configuration file:

```bash
sudo nano /etc/php/7.4/apache2/php.ini
```

Find these directives and change them:

```ini
display_errors = On
display_startup_errors = On
```

In a terminal-based editor, you can search with `Ctrl+W`. Save and exit, then restart Apache:

```bash
sudo systemctl restart apache2
```

> **Warning:** Never enable `display_errors` on a production server exposed to the internet. It leaks internal paths, database schema details, and other information. This is a development-only setting.

### Convert index.html to index.php

Rename your existing file and add dynamic content:

```bash
cd /var/www/html/
sudo mv index.html index.php
```

Now edit `index.php` to add a PHP block that displays the current date and the PHP version:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pi LAMP Server</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
    <div class="container text-center mt-5">
        <h1>Your Raspberry Pi LAMP Server</h1>
        <p class="lead">Apache is running. MariaDB is connected. PHP is active.</p>

        <div class="row mt-5">
            <div class="col-md-4">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Server Time</h5>
                        <p class="card-text display-6"><?= date('H:i:s') ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <h5 class="card-title">PHP Version</h5>
                        <p class="card-text display-6"><?= PHP_VERSION ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Database</h5>
                        <p class="card-text display-6" id="db-status">checking...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
```

Refresh your browser. You should see a Bootstrap-styled dashboard showing the current server time and PHP version, updating on each page load.

### Verify the Database Connection

Create a file at `/var/www/html/check-db.php`:

```bash
sudo nano /var/www/html/check-db.php
```

```php
<?php
$host = 'localhost';
$user = 'pi_user';
$pass = 'secure_password';
$db   = 'pi_lamp';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo json_encode(['status' => 'connected', 'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
```

Navigate to `http://<PI_IP>/check-db.php`. You should see:

```json
{"status":"connected","driver":"mysql"}
```

If you see an error, check your credentials in the script and verify MariaDB is running:

```bash
sudo systemctl status mysql
```

---

## Step 5 — Install Adminer

Adminer is a single-file database management tool — a replacement for phpMyAdmin that's dramatically simpler to install and lighter to run. The entire application is one PHP file that you download with `wget`.

```bash
cd /var/www/html/
sudo wget -O adminer.php https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1.php
```

Navigate to `http://<PI_IP>/adminer.php`. You'll see the Adminer login screen. Select **MySQL** as the database system (this works with MariaDB), enter your credentials (`pi_user` / `secure_password`), and connect.

From here you can browse tables, run SQL queries, export data, and manage your database schema entirely through the browser. If you prefer a GUI over the MySQL command line, Adminer is a massive quality-of-life improvement.

---

## Upgrading to PHP 8.x (Optional)

PHP 7.4 reached its end of life in November 2022. For new projects, you should be running PHP 8.0 or 8.1 at minimum. The Ondrej PPA provides PHP 8.x packages for Debian-based ARM systems, including Raspberry Pi OS.

Add the repository:

```bash
sudo apt install -y apt-transport-https lsb-release ca-certificates
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update
```

Install PHP 8.1 (or 8.2/8.3 if available):

```bash
sudo apt install php8.1 php8.1-mysql libapache2-mod-php8.1 -y
```

Disable the PHP 7.4 module and enable 8.1:

```bash
sudo a2dismod php7.4
sudo a2enmod php8.1
sudo systemctl restart apache2
```

Verify the switch:

```bash
php -v
# Should show PHP 8.1.x
```

The Ondrej PPA is well-maintained and is the standard way to get modern PHP on Debian-based ARM systems. The packages are the same ones used on Ubuntu, compiled for ARM architecture.

---

## Real-World Use Cases

A LAMP stack on a Raspberry Pi isn't going to power the next Facebook. But it excels in these specific scenarios:

### Home Media Server Dashboard

Combine Apache with a PHP frontend that catalogs your media library. MariaDB stores file paths, metadata, and watch history. The Pi serves a searchable, filterable interface to your movie and music collection. With the Pi's HDMI output, you can even connect it directly to a TV.

### IoT Sensor Dashboard

This is the natural next step after Part 1. Sensors connected to the Pi's GPIO pins report temperature, humidity, vibration, or motion data. PHP scripts read the sensor values and write them to MariaDB. Apache serves real-time dashboards built with Chart.js or Bootstrap. Your phone browser becomes the control panel for your home.

### Internal Tool Server

Need a team wiki? A time tracker? A URL shortener for your home network? A Pi running LAMP can host any PHP application. Deploy a Laravel or Symfony app the same way you would on a production server — the architecture is identical, only the scale is smaller. It's an excellent staging environment for testing deployments before they hit your cloud VPS.

### Learning Environment

If you're teaching yourself or others web development, a Pi LAMP stack is a safe, isolated sandbox. Break the server — reflash the SD card in 15 minutes and start over. No cloud costs, no hosting subscriptions, no accidentally exposing a test database to the internet because you forgot to set a firewall rule.

---

## Best Practices

### Change the Default Password

The default Raspberry Pi password is well-known. Change it immediately:

```bash
passwd
```

Use a password manager to generate and store something strong.

### Enable the Firewall

Raspberry Pi OS ships with no firewall. UFW makes it simple:

```bash
sudo apt install ufw -y
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
sudo ufw enable
```

This blocks everything except SSH and HTTP/HTTPS. If you plan to access Adminer remotely, consider tunneling through SSH instead of opening port 443 for it.

### Disable Remote MySQL Access

Your MariaDB should only listen on localhost. Verify:

```sql
SHOW VARIABLES LIKE 'bind_address';
```

It should show `127.0.0.1`. If it shows `0.0.0.0`, edit `/etc/mysql/mariadb.conf.d/50-server.cnf` and set `bind-address = 127.0.0.1`, then restart MariaDB.

### Keep Packages Updated

Set a mental reminder to run updates weekly:

```bash
sudo apt update && sudo apt upgrade -y
```

Or automate it with an `unattended-upgrades` package:

```bash
sudo apt install unattended-upgrades -y
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

### Remove the Default `pi` User (Advanced)

For production-facing Pis, create a new user with sudo access, add your SSH key, then delete the `pi` user. This removes a well-known attack surface.

---

## Common Mistakes

### Forgetting to Enable SSH in Imager

If you skip the advanced options menu in Raspberry Pi Imager, your Pi boots with SSH disabled. You either need to reflash the card or connect a monitor and keyboard to enable it manually. Always press `Ctrl+Shift+X` before writing the image.

### Using the Wrong Wi-Fi Band

Raspberry Pi Zero W only supports 2.4 GHz Wi-Fi. If your network broadcasts only on 5 GHz, the Pi won't connect. Ensure your router has a 2.4 GHz SSID or enable band steering. The Pi 4 and 5 support 5 GHz, but range is shorter at higher frequencies.

### Not Updating Packages First

Installing Apache, MariaDB, or PHP before running `apt update` can pull outdated versions or fail entirely if the package indexes are stale. Make `apt update && apt upgrade` the first thing you do after every fresh install.

### PHP 7.4 vs 8.x Confusion

The default repositories install PHP 7.4. If you need 8.x features — match expressions, named arguments, enums, readonly properties — you must add the Ondrej PPA. Many developers assume `apt install php` gives them the latest version, but on Raspberry Pi OS (which is Debian-based), stability is prioritized over currency.

### Forgetting to Install php-mysql

PHP and MariaDB install independently. Without `php-mysql`, your PHP scripts will throw "Class 'PDO' not found" or "Call to undefined function mysqli_connect()" errors. Always install `php-mysql` (or `php8.1-mysql` on PHP 8.x) and restart Apache afterward.

### Permission Issues on /var/www/html

If SCP gives you "Permission denied" when copying files, Apache's `www-data` user owns `/var/www/html/` by default, and your `pi` user doesn't have write access. Fix ownership with `chown -R pi:www-data /var/www/html/` so you can deploy files without `sudo`.

---

## FAQ

### Can a Raspberry Pi handle a production web server?

It depends on your definition of production. For internal tools serving a handful of users, absolutely. For public-facing applications with thousands of concurrent visitors, no — the Pi's single-core performance and limited RAM will bottleneck hard. Use a cloud VPS for public traffic and keep the Pi for internal tools, IoT, and prototyping.

### What's the difference between MariaDB and MySQL?

MariaDB is a fork of MySQL by the original MySQL developers. It's a drop-in replacement — same commands, same syntax, same drivers. The Raspberry Pi OS repositories ship MariaDB because it's fully open source and better optimized for ARM. Everything that connects to MySQL (PHP's PDO, mysqli, Adminer, Laravel) works identically with MariaDB.

### How do I find my Pi's IP address without a monitor?

Run `hostname -I` on the Pi via SSH. If you don't have SSH access yet, check your router's DHCP client list, use a network scanner like `nmap -sn 192.168.1.0/24`, or try `ping raspberrypi.local` if mDNS is working on your network.

### Can I use Nginx instead of Apache?

Yes. Install `nginx` and `php-fpm` instead of Apache. The architecture is slightly different — Nginx passes PHP requests to PHP-FPM rather than embedding the interpreter via `mod_php` — but the end result is the same. Nginx typically uses less memory on the Pi Zero, making it a better choice for extremely resource-constrained setups.

### Do I need a static IP for the Pi?

Not for basic use. DHCP reservations (set on your router) are better than static IP configuration — you get the same IP every time without managing network config files on the Pi. Most router admin panels have a DHCP reservation feature under LAN settings. Reserve an address for your Pi's MAC address and it will never change.

### How do I access Adminer remotely?

Don't expose Adminer directly to the internet — it's a single-file PHP application with no built-in authentication. Instead, use SSH tunneling: `ssh -L 8080:localhost:80 pi@raspberrypi.local`. Then open `http://localhost:8080/adminer.php` on your local machine. The tunnel encrypts the connection and bypasses the need to open additional ports.

### What size SD card do I need?

16 GB is the sweet spot. Raspberry Pi OS Lite uses about 2 GB. Apache, MariaDB, PHP, and Adminer add another 500 MB. That leaves over 13 GB for application code, database tables, and log files. 8 GB works if you're frugal. 32 GB is overkill unless you're storing sensor data for years.

### Why is my Pi running slowly after installing the LAMP stack?

Check RAM usage with `free -h` and swap with `swapon --show`. On a Pi Zero W with 512 MB of RAM, Apache and MariaDB can consume most of your available memory. Solutions: use Nginx instead of Apache, reduce PHP worker processes, increase swap space, or — the simplest fix — upgrade to a Pi 4 or 5 with more RAM.

---

## Conclusion

You now have a fully functional LAMP stack running on a $35 computer. Apache serves web pages. MariaDB stores relational data. PHP connects them together and renders dynamic content. Adminer gives you a visual database console. The entire stack runs on 5 watts of power, makes no noise, and fits in a drawer.

This is the foundation. From here, everything you already know about PHP web development applies directly. Deploy a Laravel app. Build a REST API. Set up a WordPress site. The Pi is now a first-class web server on your network — small, cheap, and surprisingly capable.

In Part 3 of this series, we'll connect physical sensors to the Pi, read data from them using PHP, and store the measurements in MariaDB. You'll build a real-time dashboard that displays temperature, humidity, and vibration data — the intersection of LAMP and IoT.

Your $35 web server is about to grow eyes and ears.
