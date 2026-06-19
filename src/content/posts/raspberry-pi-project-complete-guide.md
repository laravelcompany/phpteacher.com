---
title: "Raspberry Pi PHP Project - Complete End-to-End IoT Guide"
description: "The final installment of the Raspberry Pi PHP series. Recap the entire journey from zero to IoT dashboard, with lessons learned, full architecture overview, and next steps."
pubDate: "2022-05-12 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Raspberry Pi", "PHP", "IoT", "Complete Project", "Dashboard", "Sensors", "Data Visualization"]
selected: false
---

# Raspberry Pi PHP Project — Complete End-to-End IoT Guide

This is it. The final slice.

Over the last five months, you have taken a bare Raspberry Pi Zero W and turned it into a functioning IoT sensor node with a web dashboard — using PHP as the glue between hardware, database, and browser. You soldered header pins, debugged I2C addresses, wrote a C data logger, crafted PHP API endpoints, designed a Chart.js dashboard, and wired the whole thing together with systemd.

You solved a real problem: a dryer with a chime so quiet you could not hear it upstairs.

This post recaps the entire journey, shows you how everything fits together at a system level, breaks down what it cost, and points you toward where you can take this project next.

If you have been following along, you already know most of what follows — but seeing the full architecture in one place changes how you think about the project. If you are new here, this post gives you the map before you start the hike.

---

## The Problem

An old clothes dryer in the basement. It dries clothes fine. The mechanical timer works. The heating element cycles correctly. But the end-of-cycle chime is a pathetic little buzz — barely audible through a wooden floor, let alone from a living room two stories up.

You can buy a smart dryer for $1,200. You can buy a Wi-Fi-enabled dryer buzzer for $60. Or you can build your own solution for under $40 using tools you already know and learn embedded systems programming in the process.

The requirements were simple:

- Detect when the dryer stops vibrating (i.e., the cycle ends)
- Log every reading to a database
- Display a live dashboard accessible from any device on the network
- Send timestamped data so you can review historical cycles
- Run 24/7 with zero maintenance

Five months later, that system is running in a basement, logging data every second, rendering a live chart that works on phones, tablets, and laptops. The total hardware cost was $38. The electricity cost is negligible — the Pi Zero W draws under one watt.

---

## The Solution Timeline

Here is how the project unfolded, stage by stage.

### Part 1: Zero to SSH (January)

The Raspberry Pi Zero W arrived in a tiny cardboard box. No case. No power supply. No SD card. Just the board — $10 worth of silicon, copper, and potential.

The first milestone was getting it running headless. No monitor. No keyboard. No mouse. Just the Pi, a microSD card, and a USB serial adapter as a fallback:

1. Flashed Raspberry Pi OS Lite (32-bit) onto a 32 GB microSD card using the Raspberry Pi Imager
2. Configured Wi-Fi credentials and enabled SSH by placing `ssh` and `wpa_supplicant.conf` files on the boot partition
3. Found the Pi's IP address from the router's DHCP lease table
4. SSH'd in, changed the default password, ran `raspi-config` to expand the filesystem and enable I2C
5. Ran `sudo apt update && sudo apt upgrade` to get everything current

Total time from unboxing to a terminal prompt: about 20 minutes.

**Key lesson:** The boot partition configuration trick works on any Raspberry Pi OS installation. You do not need a keyboard or monitor at any point. A headless setup is faster and forces you to learn the command line, which you will need for every subsequent step.

### Part 2: LAMP Stack (February)

With SSH access established, the next step was turning the Pi into a web server. The LAMP stack — Linux, Apache, MariaDB, PHP — is the backbone of the entire project:

1. Installed Apache 2 with `sudo apt install apache2`
2. Verified it was serving pages by hitting the Pi's IP address from a browser
3. Installed MariaDB with `sudo apt install mariadb-server`
4. Ran `mysql_secure_installation` to lock down the root account
5. Created a dedicated database and user for the sensor project
6. Installed PHP 7.4 with `sudo apt install php php-mysqli php-mbstring php-curl`
7. Verified PHP processing with a `phpinfo()` page

The Pi Zero W's 512 MB of RAM is plenty for Apache serving a single-user dashboard. MariaDB uses about 50 MB at idle. PHP-FPM adds another 20 MB. The entire web stack fits comfortably within the Pi's memory budget.

**Key lesson:** You do not need Nginx or a CDN or a load balancer. For an internal IoT dashboard serving one household, Apache on a $10 computer is overkill in the best possible way. Use the simplest thing that works.

### Part 3: Accelerometer and Data Logging (March)

This was the hardest part. The MMA8452Q accelerometer is a three-axis digital accelerometer that communicates over I2C. It cost $3 on a breakout board from a generic electronics retailer.

The wiring was straightforward:

| MMA8452Q Pin | Raspberry Pi GPIO Pin |
|---|---|
| VCC | Pin 1 (3.3 V) |
| GND | Pin 6 (Ground) |
| SDA | Pin 3 (GPIO 2 / SDA) |
| SCL | Pin 5 (GPIO 3 / SCL) |

The I2C address defaulted to `0x1D` (the SA0 pin is pulled high on this breakout board). If yours reads `0x1C`, the SA0 pin is grounded — either way, `i2cdetect -y 1` reveals it immediately.

The data logger is a C program because PHP is not suited for tight I2C register read loops. The C program:

1. Opens `/dev/i2c-1` using the Linux I2C device interface
2. Sets the slave address to `0x1D`
3. Configures the MMA8452Q into active mode by writing to register `0x2A`
4. Reads six bytes from registers `0x01` through `0x06` (X, Y, Z — each axis is two bytes)
5. Combines the high and low bytes into signed 14-bit values
6. Inserts a row into the `readings` table via the MariaDB C connector
7. Sleeps for one second and repeats

A systemd service unit keeps the logger running across reboots. If the C program crashes (which it should not, but hardware is unpredictable), systemd restarts it automatically.

**Key lessons:** Soldering the GPIO header onto the Pi Zero W was the single most stressful part of the entire project. The pads are tiny and easy to bridge. Practice on a scrap board first. Also, I2C debugging is 90 percent address checking — if `i2cdetect` does not show your device, check your wiring before you check your code.

### Part 4: PHP API and Chart.js Dashboard (April)

With data flowing into MySQL, the last piece was making it visible. The PHP API layer has three scripts:

1. **`api.php`** — Accepts optional `start` and `end` query parameters, queries the `readings` table with parameterized prepared statements, and returns a JSON array of objects. Each object contains `timestamp`, `x`, `y`, and `z`.

2. **`latest.php`** — Returns only the most recent reading from the database. The dashboard polls this endpoint every two seconds to update the current values without reloading the entire chart.

3. **`dashboard.php`** — Renders the HTML page containing a Chart.js multi-line chart and a table of current readings. The chart plots X, Y, and Z acceleration on the Y axis against time on the X axis, with three colored lines and a legend.

The front-end uses vanilla JavaScript — no React, no Vue, no build tools. It creates a `Chart` instance from the Chart.js CDN, fetches data from `api.php` on page load, then polls `latest.php` every two seconds and appends new data points with `chart.data.labels.push()` and `chart.update('none')` for smooth animation without full redraws.

A date-range filter uses two `<input type="date">` elements that trigger a fresh fetch from `api.php` with `start` and `end` parameters. The chart updates instantly.

**Key lesson:** Chart.js's `'none'` transition mode is the difference between a janky dashboard and a smooth one. Without it, every two-second poll triggers a full chart re-render, which looks terrible. With it, new points slide in silently and the chart stays responsive.

---

## Full System Architecture

Here is how every piece connects. This diagram represents the entire system running in the author's basement:

```
+------------------+       I2C bus       +---------------------+
|  MMA8452Q        |<------------------>|  Raspberry Pi Zero W |
|  Accelerometer   |  SDA / SCL / VCC   |                     |
|  (I2C addr 0x1D) |       / GND        |  C Data Logger       |
+------------------+                     |  (systemd service)   |
                                          |         |            |
                                          |    MariaDB          |
                                          |   (sensor_db)       |
                                          |         |            |
                                          |    Apache 2         |
                                          |    PHP 7.4          |
                                          |   /var/www/html/    |
                                          |  api.php            |
                                          |  latest.php         |
                                          |  dashboard.php      |
                                          +---------------------+
                                                   |
                                                HTTP |
                                                   |
                                          +---------------------+
                                          |  Client Browser     |
                                          |  Chart.js           |
                                          |  Vanilla JS         |
                                          |  Any device         |
                                          |  on LAN             |
                                          +---------------------+
```

Data flows strictly in one direction: sensor to C logger to MySQL to PHP API to browser. There is no bidirectional control in this version. The sensor reads, the database stores, the API serves, the browser renders. Each layer is decoupled and independently replaceable.

---

## Key Technologies Used

**Raspberry Pi Zero W** — $10, 512 MB RAM, 1 GHz single-core CPU, built-in Wi-Fi and Bluetooth. Draws under one watt at idle. Runs 24/7 with no heat sink needed.

**MMA8452Q Accelerometer** — $3 breakout board. Three-axis digital accelerometer, I2C interface, configurable range (2G / 4G / 8G), 14-bit resolution. Also serves as a crude vibration sensor.

**C (data logger)** — Necessary because PHP cannot directly access the Linux I2C device interface with tight timing. The C program is about 150 lines including the MySQL insertion logic.

**PHP 7.4** — Handles API serving, database queries, prepared statements, and HTML rendering. No framework. No ORM. Just `mysqli` and careful input sanitization.

**MariaDB** — Drop-in MySQL replacement, stores time-series sensor data in a simple `readings` table with an auto-increment primary key, float columns for X/Y/Z, and a datetime timestamp.

**Chart.js** — Renders the multi-line chart in the browser. MIT-licensed, zero dependencies besides a canvas element, works on mobile browsers.

**systemd** — Manages the C data logger as a service. Auto-starts on boot, restarts on failure, logs stdout/stderr to the journal.

---

## Lessons Learned

### Soldering Is a Real Skill

The Pi Zero W ships without GPIO header pins soldered. You need to solder a 40-pin header onto surface-mount through-hole pads. If you have never soldered before, this is a trial by fire.

What I learned: use flux. A soldering iron without flux is like a car without power steering — you can make it work, but everything is harder. Apply flux to every pad, tin the tip, touch each pin for two seconds max, and move on. If you bridge two adjacent pins, use solder wick to clean it up.

### I2C Debugging Is 90 Percent Address Checking

"I2C not working" almost always means the device is not responding at its expected address. The fix is almost always:
1. Run `i2cdetect -y 1` and check if the address appears
2. If not, check VCC and GND with a multimeter
3. If power is good, check SDA and SCL connections
4. If connections are solid, try the alternate address (0x1C vs 0x1D)

I spent two hours debugging what turned out to be a loose jumper wire. Check your physical connections first.

### Permissions Will Bite You

The `i2c` group exists for a reason. The C data logger needs read/write access to `/dev/i2c-1`. If you run it as root (which systemd does by default for system services), permissions are not an issue. But if you test it from your user account without adding yourself to the `i2c` group, you get "Permission denied" every time.

Add your user to the `i2c` group: `sudo usermod -a -G i2c $USER`. Then log out and back in.

### Data Logging Frequency Matters

Logging accelerometer data every second produces about 86,400 rows per day. That is perfectly manageable — even after a month, you are looking at under three million rows. Indexing the `timestamp` column keeps queries fast regardless of table size.

But do you really need one-second resolution? For dryer detection, probably not. Five-second intervals would work fine and reduce storage by 80 percent. The project logs every second because it can, not because it must.

### systemd Makes Everything Easier

Before systemd, keeping a background process alive required init scripts, PID files, and manual restart logic. With systemd, a 10-line service unit file handles automatic start on boot, crash recovery, log management, and status checking.

---

## Cost Breakdown

| Component | Price |
|---|---|
| Raspberry Pi Zero W | $10.00 |
| 32 GB microSD card | $8.00 |
| Micro USB power supply | $6.00 |
| MMA8452Q breakout board | $3.00 |
| GPIO header pins (40-pin) | $1.00 |
| Jumper wires (female-to-female, 10-pack) | $2.00 |
| Solder, flux, miscellaneous | $8.00 |
| **Total** | **$38.00** |

You probably already own a soldering iron, a multimeter, and a USB serial adapter. If not, add about $25 for a basic soldering kit. The total is still under the cost of a single dinner delivery.

Compare this to an off-the-shelf smart dryer notification system: $60 for a Wi-Fi buzzer that only buzzes, or $1,200 for a new dryer with smart features. The $38 DIY solution logs data, renders charts, stores history, and taught you embedded systems programming.

---

## Advice for Building IoT Projects With PHP

### PHP Is Not the Wrong Choice

There is a persistent myth that PHP is only for WordPress blogs and that hardware projects require Python, C, or Rust. That is false. PHP excels at exactly what this project needs: database interaction, HTTP request handling, JSON serialization, and HTML rendering. The data logger needed C because of the I2C timing requirements, but everything above the sensor layer is pure PHP.

Pick the right tool for each layer. C for hardware access. PHP for the web layer. JavaScript for the front-end. Each language does what it does best.

### Use Prepared Statements From Day One

When you are building a dashboard that only you will access on your local network, it is tempting to skip SQL injection defenses. Do not. Build the habit of using parameterized queries in every PHP script that touches the database. It costs nothing and prevents a class of bugs that are hard to catch later.

### Keep the API Separate From the Presentation

`api.php` returns JSON. `dashboard.php` returns HTML. If you ever want to add a mobile app, a command-line client, or a webhook integration, you already have a clean API. You do not need to re-architect anything.

### Log Everything During Development

The C data logger writes diagnostic messages to stdout, which systemd captures in the journal. If something breaks at 3 AM, `journalctl -u dryer-logger.service` shows you exactly what happened. The PHP API logs query errors to Apache's error log. The front-end logs fetch failures to the browser console. Every layer has observability built in.

---

## Next Steps and Enhancements

The project works. The dashboard renders. The dryer is monitored. But the system is far from finished. Here is what comes next.

### Alerts and Notifications

The biggest missing feature: push notifications. Right now you have to open a browser tab and look at the chart to see if the dryer is running. A notification system would send a message to your phone when the vibration stops.

The simplest approach: a PHP script that checks the latest reading, compares it against a threshold, and sends a Pushover or Gotify notification. Run it as a cron job every 30 seconds.

```php
$latest = $db->query("SELECT x, y, z FROM readings ORDER BY id DESC LIMIT 1")->fetch_assoc();
$magnitude = sqrt($latest['x'] ** 2 + $latest['y'] ** 2 + $latest['z'] ** 2);
if ($magnitude < 1.1) {
    // Send notification: dryer cycle complete
}
```

### Additional Sensors

The MMA8452Q is a general-purpose accelerometer. You can repurpose the exact same hardware and code for:
- Door open/close detection (mount it on the door frame, look for a brief vibration spike)
- Washing machine cycle monitoring (same vibration detection logic)
- Drawer opened alert (mount inside a drawer, detect the tilt)
- Fridge door left open (combine with a temperature sensor)

Add a DHT22 temperature and humidity sensor on the same I2C bus (you need a different I2C address) and extend the database schema with a `temperature` column. Extend the API to serve the new data. Add a second chart to the dashboard.

### Remote Access

The dashboard is currently only accessible on your local network. To access it remotely:

1. **Tailscale** — Install Tailscale on the Pi and your phone/laptop. Access the dashboard via the Pi's Tailscale IP address. Zero configuration, encrypted tunnel, no open ports. This is the safest option.

2. **Cloudflare Tunnel** — Install `cloudflared` on the Pi and create an Argo Tunnel to a public domain. Lets you access the dashboard from anywhere without opening ports on your router.

3. **Port forwarding and Dynamic DNS** — The traditional approach. Open port 443 on your router, forward it to the Pi, set up Dynamic DNS for a domain name. Works but requires SSL configuration and vigilance about security.

### Machine Learning Classification

With enough labeled data, you can train a simple model to distinguish between "dryer running," "dryer off," and "dryer with laundry but not running." A Raspberry Pi Zero W cannot run TensorFlow, but it can run a simple threshold-based classifier or export CSV data for training on a laptop.

### Grafana Integration

If the PHP and Chart.js dashboard starts feeling limited, replace it with Grafana. Install Grafana on the Pi (or on another machine), connect it to the MariaDB database as a data source, and build dashboards with Grafana's query builder. You get alerting, annotations, and a professional-grade UI for free.

---

## The Full Series

If you are just discovering this project, here are all five parts in order:

- [Part 1: Raspberry Pi Home Hacking — Complete Setup Guide](/posts/raspberry-pi-home-hacking-guide) — OS installation, headless SSH, configuration
- [Part 2: LAMP Stack on Raspberry Pi](/posts/raspberry-pi-lamp-stack-install) — Apache, MariaDB, PHP installation
- [Part 3: Accelerometer Sensor Data Logging](/posts/raspberry-pi-accelerometer-sensor-data-logging) — MMA8452Q wiring, C logger, systemd
- [Part 4: PHP API and Chart.js Dashboard](/posts/raspberry-pi-php-api-data-visualization) — Data visualization and API layer
- **Part 5: Complete Project Wrap-Up (this post)** — Architecture review, lessons learned, next steps

---

## Final Thoughts

Five months ago, the phrase "IoT development" sounded like something requiring a computer science degree, a pile of expensive hardware, and fluency in three languages you do not speak. It turns out you just need a $10 computer, a $3 sensor, and a PHP installation.

The dryer knows when it is done now. Not because it has a "smart" badge and a $200 premium baked into its retail price, but because someone with a soldering iron and a PHP script decided that a quiet chime was not a good enough reason to keep walking down to the basement to check.

Build something. Solder something. Break something. Fix it. Then build something else. Every PHP developer who writes a `for` loop has the foundation to read a sensor and render it on a dashboard. The only difference between thinking about it and doing it is the first wire you connect.

That next iteration — alerts, notifications, additional sensors, remote access — is where the project evolves from a prototype into an appliance. The architecture supports it. The database is ready for new columns. The API can serve whatever data you throw at it. The dashboard can grow as many charts as you need.

Go build it.
