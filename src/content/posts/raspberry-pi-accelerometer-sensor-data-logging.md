---
title: "Raspberry Pi Accelerometer - Sensor Data Logging With PHP and MySQL"
description: "Connect an MMA8452 accelerometer to your Raspberry Pi, log sensor data to MySQL, and build a PHP dashboard. Complete IoT project guide with C, PHP, and hardware setup."
pubDate: "2022-03-12 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Raspberry Pi", "Accelerometer", "PHP", "IoT", "Sensors", "GPIO", "C Programming", "Data Logging", "MySQL"]
selected: false
---

# Raspberry Pi Accelerometer — Sensor Data Logging With PHP and MySQL

Your Raspberry Pi is sitting on your desk with a fresh LAMP stack, serving web pages on your network. Apache is running. MariaDB has a database waiting for data. PHP is installed and wired into the web server. You have a functioning Linux box that costs less than a dinner out.

Now make it useful.

This guide walks you through connecting an MMA8452Q accelerometer to the Pi's GPIO pins over I2C, writing a C program that reads acceleration data and inserts it into MySQL, creating a systemd service to keep your logger running across reboots, and building a PHP dashboard to visualize the data. By the end, you will have a complete IoT sensor node — data flows from physical hardware into a database, and a web dashboard renders it in real time.

This is Part 3 of the series. If you have not set up your Pi yet, go back to [Part 1](/posts/raspberry-pi-home-hacking-guide). If you need the LAMP stack installed, work through [Part 2](/posts/raspberry-pi-lamp-stack-install). You need both before you start here.

---

## What You'll Build

By the end of this guide you will have:

- An MMA8452Q accelerometer wired to your Raspberry Pi's GPIO header and communicating over the I2C bus
- A C program that reads the accelerometer registers at a configurable interval and inserts readings into a MariaDB database
- A MySQL table schema optimized for time-series sensor data
- A systemd service unit that starts the logger on boot, runs it in the background, and shuts it down cleanly
- A PHP dashboard served by Apache that queries the database and displays acceleration charts using Chart.js
- A durable, production-ready sensor logging pipeline that survives power loss, network interruptions, and reboots

If you have never written a C program that talks to a database or created a systemd service, do not worry. Every step is explicit, every command is shown, and every configuration file is reproduced in full.

---

## What You'll Need

- **Raspberry Pi** (Zero 2 W, Pi 3, Pi 4, or Pi 5) with Raspberry Pi OS Lite installed, SSH accessible, I2C enabled, and a LAMP stack running
- **MMA8452Q accelerometer breakout board** — available from Adafruit, SparkFun, or any electronics distributor for roughly $3–$6
- **Female-to-female jumper wires** — four wires for the initial connection
- **Breadboard** (optional but helpful for prototyping)
- **Your development machine** with SSH access to the Pi

If you are using a Pi Zero W without pre-soldered GPIO headers, you need to solder a 20-pin female header before connecting anything. The Pi Zero 2 W, Pi 3, Pi 4, and Pi 5 all ship with the header pre-installed.

---

## Understanding I2C on the Raspberry Pi

I2C (Inter-Integrated Circuit) is a serial communication protocol that uses two bidirectional wires: SDA (serial data) and SCL (serial clock). Multiple devices can share the same two wires because each device has a unique 7-bit address. The Raspberry Pi is the controller (master), and the MMA8452Q is the target (slave) at address `0x1D`.

The Pi's hardware I2C bus appears at `/dev/i2c-1`. User-space programs read from and write to this device file using the Linux I2C device interface. The kernel module `i2c-dev` provides the ioctl calls that let you send and receive data without any kernel programming.

Enabling I2C is a one-time operation in `raspi-config`:

```bash
sudo raspi-config
```

Navigate to **Interface Options > I2C**, select **Yes**, and reboot. After the reboot, verify the bus exists:

```bash
ls /dev/i2c*
```

You should see `/dev/i2c-1`. If you see only `/dev/i2c-0`, you are on a very old Pi revision — use bus 0 instead, but everything in this guide assumes bus 1.

Install the I2C tools:

```bash
sudo apt install i2c-tools -y
```

Probe the bus to see connected devices:

```bash
i2cdetect -y 1
```

You should see a grid of addresses. With the MMA8452Q connected (which we will do next), address `0x1D` shows `1D` in the grid. If you see all dashes, your sensor is not connected or not powered.

---

## Wiring the MMA8452Q to the Pi

The MMA8452Q breakout board has four pins you need to connect. The board runs on 3.3 V logic. The Raspberry Pi's GPIO header provides 3.3 V output on pin 1 (physical pin numbering). Do not connect the sensor to 5 V — you will damage it.

### Pin Mapping

| MMA8452Q Pin | Pi GPIO Pin | Physical Pin Number | Purpose |
|---|---|---|---|
| VCC | 3.3 V | Pin 1 | Power (3.3 V) |
| GND | Ground | Pin 6 | Ground |
| SDA | GPIO 2 (SDA) | Pin 3 | I2C data line |
| SCL | GPIO 3 (SCL) | Pin 5 | I2C clock line |

Using female-to-female jumper wires:

1. Connect VCC on the breakout to Pin 1 on the Pi (3.3 V)
2. Connect GND on the breakout to Pin 6 on the Pi (Ground)
3. Connect SDA on the breakout to Pin 3 on the Pi (GPIO 2 / SDA)
4. Connect SCL on the breakout to Pin 5 on the Pi (GPIO 3 / SCL)

Double-check your connections before powering on. A reversed VCC/GND connection destroys the sensor. A miswired SDA/SCL connection simply fails to communicate — no damage, it just will not show up on the I2C bus.

Once connected, probe the bus again:

```bash
i2cdetect -y 1
```

You should see `1D` in the grid at column 0, row 0x1. If you see `1D`, the sensor is alive and communicating. If you see `--`, power the Pi down and recheck your wiring.

### Understanding the MMA8452Q Registers

The MMA8452Q is a 3-axis accelerometer with 14-bit resolution and a full-scale range of ±2 g, ±4 g, or ±8 g (selectable via a configuration register). It exposes its data through several registers:

| Register | Address | Purpose |
|---|---|---|
| STATUS | `0x00` | Read to check if new data is available |
| OUT_X_MSB | `0x01` | X-axis high byte |
| OUT_X_LSB | `0x02` | X-axis low byte |
| OUT_Y_MSB | `0x03` | Y-axis high byte |
| OUT_Y_LSB | `0x04` | Y-axis low byte |
| OUT_Z_MSB | `0x05` | Z-axis high byte |
| OUT_Z_LSB | `0x06` | Z-axis low byte |
| CTRL_REG1 | `0x2A` | Configuration — set standby vs. active mode, output data rate |

To read acceleration data, you set the sensor to active mode by writing to `CTRL_REG1`, poll `STATUS` to confirm fresh data, then read six consecutive bytes starting at `OUT_X_MSB`. Each axis value is a 14-bit signed integer spread across two bytes.

---

## Installing the Build Tools

You need a C compiler, the MySQL client library (MariaDB connector), and the I2C development headers:

```bash
sudo apt install build-essential libmariadb-dev libi2c-dev -y
```

- `build-essential` provides `gcc`, `make`, and the standard C library headers
- `libmariadb-dev` provides the MySQL client library (MariaDB's version, fully compatible with MySQL's `libmysqlclient`)
- `libi2c-dev` provides the Linux I2C user-space header files (specifically `linux/i2c-dev.h`)

Verify the MariaDB library is available:

```bash
pkg-config --cflags --libs libmariadb
```

You should see output like `-I/usr/include/mariadb -lmariadb`. If `pkg-config` fails, the library is not installed or the `.pc` file is missing. Reinstall `libmariadb-dev` and try again.

---

## Creating the Database Schema

Log into MariaDB and create a database and table for the accelerometer data:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE sensor_log;
CREATE USER 'sensor_user'@'localhost' IDENTIFIED BY 'sensor_password';
GRANT ALL PRIVILEGES ON sensor_log.* TO 'sensor_user'@'localhost';
FLUSH PRIVILEGES;

USE sensor_log;

CREATE TABLE accelerometer_readings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recorded_at TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    x_axis FLOAT NOT NULL,
    y_axis FLOAT NOT NULL,
    z_axis FLOAT NOT NULL,
    magnitude FLOAT NOT NULL,
    INDEX idx_recorded_at (recorded_at)
);

EXIT;
```

The schema stores each reading as a row with millisecond-precision timestamp, individual axis values as floats, and a computed magnitude. The magnitude is the vector length: `sqrt(x² + y² + z²)`. Computing this in the C program rather than in SQL saves the database CPU cycles and makes queries simpler.

The index on `recorded_at` is critical — your PHP dashboard will query by time range, and without an index, those queries perform full table scans as the table grows.

---

## Writing the C Logger Program

The C program does four things in a loop:

1. Open the I2C device and configure the MMA8452Q
2. Read the accelerometer registers
3. Convert the raw register values to g-force floats
4. Insert the reading into MariaDB

Create a file called `accel_logger.c` on the Pi:

```bash
nano ~/accel_logger.c
```

Here is the complete program:

```c
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdint.h>
#include <unistd.h>
#include <fcntl.h>
#include <sys/ioctl.h>
#include <linux/i2c-dev.h>
#include <mysql/mysql.h>
#include <math.h>
#include <time.h>

#define I2C_BUS "/dev/i2c-1"
#define MMA8452_ADDR 0x1D
#define STATUS_REG 0x00
#define OUT_X_MSB 0x01
#define CTRL_REG1 0x2A
#define STANDBY 0x00
#define ACTIVE 0x01
#define READ_RATE 4
#define ODR_100HZ 0x28

static int init_i2c(void) {
    int fd = open(I2C_BUS, O_RDWR);
    if (fd < 0) {
        fprintf(stderr, "Failed to open I2C bus: %s\n", I2C_BUS);
        return -1;
    }
    if (ioctl(fd, I2C_SLAVE, MMA8452_ADDR) < 0) {
        fprintf(stderr, "Failed to set I2C slave address 0x%02X\n", MMA8452_ADDR);
        close(fd);
        return -1;
    }
    return fd;
}

static int write_register(int fd, uint8_t reg, uint8_t value) {
    uint8_t buf[2] = {reg, value};
    if (write(fd, buf, 2) != 2) {
        fprintf(stderr, "Failed to write register 0x%02X\n", reg);
        return -1;
    }
    return 0;
}

static int read_register(int fd, uint8_t reg, uint8_t *value) {
    if (write(fd, &reg, 1) != 1) {
        fprintf(stderr, "Failed to select register 0x%02X\n", reg);
        return -1;
    }
    if (read(fd, value, 1) != 1) {
        fprintf(stderr, "Failed to read register 0x%02X\n", reg);
        return -1;
    }
    return 0;
}

static int configure_sensor(int fd) {
    if (write_register(fd, CTRL_REG1, STANDBY) < 0) return -1;
    usleep(10000);
    if (write_register(fd, CTRL_REG1, ODR_100HZ | ACTIVE) < 0) return -1;
    usleep(20000);
    return 0;
}

static int read_acceleration(int fd, float *x, float *y, float *z) {
    uint8_t status;
    if (read_register(fd, STATUS_REG, &status) < 0) return -1;
    if (!(status & 0x08)) {
        return -1;
    }

    uint8_t reg = OUT_X_MSB;
    if (write(fd, &reg, 1) != 1) return -1;

    uint8_t buf[6];
    if (read(fd, buf, 6) != 6) return -1;

    int16_t raw_x = (int16_t)((buf[0] << 8) | buf[1]) >> 2;
    int16_t raw_y = (int16_t)((buf[2] << 8) | buf[3]) >> 2;
    int16_t raw_z = (int16_t)((buf[4] << 8) | buf[5]) >> 2;

    *x = raw_x * 0.0009765625f;
    *y = raw_y * 0.0009765625f;
    *z = raw_z * 0.0009765625f;

    return 0;
}

static MYSQL *init_database(void) {
    MYSQL *conn = mysql_init(NULL);
    if (conn == NULL) {
        fprintf(stderr, "mysql_init failed\n");
        return NULL;
    }
    if (mysql_real_connect(conn, "localhost", "sensor_user",
                           "sensor_password", "sensor_log", 0, NULL, 0) == NULL) {
        fprintf(stderr, "Database connection failed: %s\n", mysql_error(conn));
        mysql_close(conn);
        return NULL;
    }
    return conn;
}

static int insert_reading(MYSQL *conn, float x, float y, float z, float mag) {
    char query[256];
    int len = snprintf(query, sizeof(query),
        "INSERT INTO accelerometer_readings (x_axis, y_axis, z_axis, magnitude) "
        "VALUES (%.4f, %.4f, %.4f, %.4f)", x, y, z, mag);

    if (mysql_real_query(conn, query, len) != 0) {
        fprintf(stderr, "Insert failed: %s\n", mysql_error(conn));
        return -1;
    }
    return 0;
}

int main(void) {
    int i2c_fd = init_i2c();
    if (i2c_fd < 0) return 1;

    if (configure_sensor(i2c_fd) < 0) {
        close(i2c_fd);
        return 1;
    }

    MYSQL *conn = init_database();
    if (conn == NULL) {
        close(i2c_fd);
        return 1;
    }

    fprintf(stderr, "Accelerometer logger started. Sampling every %d seconds.\n", READ_RATE);

    while (1) {
        float x, y, z;
        int result = read_acceleration(i2c_fd, &x, &y, &z);

        if (result == 0) {
            float mag = sqrtf(x * x + y * y + z * z);
            time_t now;
            struct tm *tm_info;
            char timestamp[32];

            time(&now);
            tm_info = localtime(&now);
            strftime(timestamp, sizeof(timestamp), "%Y-%m-%d %H:%M:%S", tm_info);

            fprintf(stderr, "[%s] X=%.4f Y=%.4f Z=%.4f |mag=%.4f|\n",
                    timestamp, x, y, z, mag);

            insert_reading(conn, x, y, z, mag);
        }

        sleep(READ_RATE);
    }

    mysql_close(conn);
    close(i2c_fd);
    return 0;
}
```

### How the Program Works

The `init_i2c` function opens `/dev/i2c-1` and sets the slave address to `0x1D`. The `configure_sensor` function first puts the sensor in standby mode by writing `0x00` to `CTRL_REG1`, then sets it to active mode with a 100 Hz output data rate by writing `0x29`. The standby-to-active transition is required by the MMA8452Q datasheet — configuration registers can only be modified in standby mode.

The `read_acceleration` function checks the STATUS register for the "XYZ data ready" bit (bit 3). When new data is available, it reads six consecutive bytes starting at register `0x01`. The raw values are 14-bit signed integers left-aligned in 16-bit registers, so each value needs a right-shift by 2. The conversion to g-force uses the sensor's sensitivity at ±2 g range: 0.0009765625 g per count (which is 1/1024).

The `init_database` function connects to MariaDB using the credentials you created earlier. The `insert_reading` function builds a parameterized SQL query and executes it. Note that we compute the magnitude in C rather than in SQL to reduce database load.

The main loop polls the sensor every 4 seconds, reads the three axis values, computes the magnitude, prints a log line to stderr, and inserts the row into MariaDB. Using stderr for logging keeps stdout available for piping if needed.

### Compile the Program

```bash
gcc -o accel_logger accel_logger.c -lmariadb -lm -I/usr/include/mariadb
```

If you installed `libmariadb-dev` but `pkg-config` points to a different include path, adjust the `-I` flag:

```bash
pkg-config --cflags libmariadb
# Example output: -I/usr/include/mariadb -I/usr/include/mariadb/mysql
```

The `-lmariadb` flag links the MariaDB client library. The `-lm` flag links the math library for `sqrtf`. If the compilation succeeds, you have an executable called `accel_logger` in your current directory.

### Test the Logger Manually

Run the program in the foreground to verify it reads data and inserts into the database:

```bash
./accel_logger
```

You should see output like this every 4 seconds:

```
[2022-03-12 14:23:01] X=0.0127 Y=0.0088 Z=1.0020 |mag=1.0021|
[2022-03-12 14:23:05] X=0.0117 Y=0.0098 Z=1.0010 |mag=1.0011|
```

The Z-axis reads approximately 1 g because gravity is pulling downward. If the sensor is sitting flat on your desk, Z should be close to 1.0 while X and Y should be near 0. Tilt the sensor and watch the values change.

If you see "Database connection failed," verify your MariaDB credentials and that the `sensor_log` database exists. If you see "Failed to open I2C bus," verify I2C is enabled and the sensor is connected.

Verify data is being inserted:

```bash
mysql -u sensor_user -p sensor_log -e "SELECT COUNT(*) AS total_rows FROM accelerometer_readings;"
```

The count should increase every 4 seconds.

---

## Creating a systemd Service

Running the logger in an SSH session is fine for testing, but a production sensor node needs to start automatically on boot, restart on failure, and shut down cleanly. systemd handles all of this.

### Move the Binary

Create a directory for your compiled programs and move the logger:

```bash
sudo mkdir -p /opt/sensor-logger
sudo mv ~/accel_logger /opt/sensor-logger/
sudo chown root:root /opt/sensor-logger/accel_logger
```

### Create the Service Unit File

```bash
sudo nano /etc/systemd/system/accel-logger.service
```

```ini
[Unit]
Description=MMA8452Q Accelerometer Data Logger
Documentation=https://github.com/kenmarks/pi-sensor-logger
After=network.target mysql.service
Wants=mysql.service

[Service]
Type=simple
ExecStart=/opt/sensor-logger/accel_logger
Restart=always
RestartSec=10
StandardError=syslog
User=root
Group=root

[Install]
WantedBy=multi-user.target
```

The `After` and `Wants` directives ensure MariaDB starts before the logger. If MariaDB is not ready when the logger starts, the program fails its database connection and exits — but `Restart=always` with a 10-second delay handles this gracefully. The logger attempts to connect, fails, and retries 10 seconds later, by which time MariaDB is usually available.

### Enable and Start the Service

```bash
sudo systemctl daemon-reload
sudo systemctl enable accel-logger.service
sudo systemctl start accel-logger.service
```

Check the status:

```bash
sudo systemctl status accel-logger.service
```

You should see `active (running)`. View the log output:

```bash
sudo journalctl -u accel-logger.service -f
```

The `-f` flag follows new log entries. You should see the same sensor readings you saw during manual testing.

### Testing the Service

Reboot the Pi:

```bash
sudo reboot
```

Wait for the Pi to come back, then SSH in and verify the service started automatically:

```bash
sudo systemctl status accel-logger.service
sudo journalctl -u accel-logger.service --since "1 minute ago"
```

Count the rows in the database:

```bash
mysql -u sensor_user -p sensor_log -e "SELECT MIN(recorded_at), MAX(recorded_at), COUNT(*) FROM accelerometer_readings;"
```

If the timestamps span the reboot time, the logger resumed collection immediately after the Pi came back online.

---

## Building the PHP Dashboard

The hardware is logging data. The database is filling with rows. Now build a web dashboard that displays the data in real time.

### Directory Setup

Create a directory for the dashboard inside Apache's document root:

```bash
sudo mkdir -p /var/www/html/accelerometer
sudo chown -R pi:www-data /var/www/html/accelerometer
sudo chmod -R 775 /var/www/html/accelerometer
```

### The PHP Data Endpoint

Create a PHP file that queries the last 60 minutes of data and returns it as JSON:

```bash
nano /var/www/html/accelerometer/data.php
```

```php
<?php
$host = 'localhost';
$user = 'sensor_user';
$pass = 'sensor_password';
$db   = 'sensor_log';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$interval = isset($_GET['minutes']) ? (int) $_GET['minutes'] : 60;
$interval = max(1, min(1440, $interval));

$stmt = $pdo->prepare("
    SELECT recorded_at, x_axis, y_axis, z_axis, magnitude
    FROM accelerometer_readings
    WHERE recorded_at >= NOW() - INTERVAL :minutes MINUTE
    ORDER BY recorded_at ASC
");
$stmt->execute(['minutes' => $interval]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($rows);
```

This endpoint accepts an optional `minutes` query parameter (default 60, clamped between 1 and 1440) and returns the data as a JSON array. Each element contains the timestamp, the three axis values, and the magnitude.

### The Dashboard Page

Create the main dashboard HTML file:

```bash
nano /var/www/html/accelerometer/index.php
```

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accelerometer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
</head>
<body class="bg-dark text-white">
    <div class="container-fluid py-4">
        <h1 class="mb-4">MMA8452Q Accelerometer Readings</h1>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">X-Axis</h6>
                        <p class="card-text display-6" id="current-x">--</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Y-Axis</h6>
                        <p class="card-text display-6" id="current-y">--</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Z-Axis</h6>
                        <p class="card-text display-6" id="current-z">--</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Magnitude</h6>
                        <p class="card-text display-6" id="current-mag">--</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <canvas id="accelChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card bg-secondary text-white">
                    <div class="card-body">
                        <h5>Raw Data</h5>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-dark table-striped table-sm" id="data-table">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>X</th>
                                        <th>Y</th>
                                        <th>Z</th>
                                        <th>Mag</th>
                                    </tr>
                                </thead>
                                <tbody id="data-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('accelChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'X', data: [], borderColor: '#ff6384', fill: false, tension: 0.2, pointRadius: 0 },
                    { label: 'Y', data: [], borderColor: '#36a2eb', fill: false, tension: 0.2, pointRadius: 0 },
                    { label: 'Z', data: [], borderColor: '#ffcd56', fill: false, tension: 0.2, pointRadius: 0 },
                    { label: 'Mag', data: [], borderColor: '#4bc0c0', fill: false, tension: 0.2, pointRadius: 0 }
                ]
            },
            options: {
                responsive: true,
                animation: false,
                scales: {
                    x: { ticks: { color: '#ccc', maxTicksLimit: 10 } },
                    y: { ticks: { color: '#ccc' } }
                },
                plugins: {
                    legend: { labels: { color: '#fff' } }
                }
            }
        });

        function updateDashboard() {
            fetch('data.php')
                .then(r => r.json())
                .then(data => {
                    if (data.length === 0) return;

                    const last = data[data.length - 1];
                    document.getElementById('current-x').textContent = parseFloat(last.x_axis).toFixed(4);
                    document.getElementById('current-y').textContent = parseFloat(last.y_axis).toFixed(4);
                    document.getElementById('current-z').textContent = parseFloat(last.z_axis).toFixed(4);
                    document.getElementById('current-mag').textContent = parseFloat(last.magnitude).toFixed(4);

                    const labels = data.map(r => r.recorded_at.substring(11, 19));
                    chart.data.labels = labels;
                    chart.data.datasets[0].data = data.map(r => r.x_axis);
                    chart.data.datasets[1].data = data.map(r => r.y_axis);
                    chart.data.datasets[2].data = data.map(r => r.z_axis);
                    chart.data.datasets[3].data = data.map(r => r.magnitude);
                    chart.update();

                    const tbody = document.getElementById('data-body');
                    tbody.innerHTML = data.slice(-50).reverse().map(r => `
                        <tr>
                            <td>${r.recorded_at}</td>
                            <td>${parseFloat(r.x_axis).toFixed(4)}</td>
                            <td>${parseFloat(r.y_axis).toFixed(4)}</td>
                            <td>${parseFloat(r.z_axis).toFixed(4)}</td>
                            <td>${parseFloat(r.magnitude).toFixed(4)}</td>
                        </tr>
                    `).join('');
                });
        }

        updateDashboard();
        setInterval(updateDashboard, 5000);
    </script>
</body>
</html>
```

The dashboard polls the `data.php` endpoint every 5 seconds, updates four stat cards with the latest readings, refreshes the Chart.js line chart with the full dataset, and populates a scrollable raw data table with the 50 most recent rows.

Chart.js renders entirely in the browser, so rendering load is on the client, not the Pi. The `animation: false` option prevents chart transitions from consuming CPU cycles on every refresh.

### Access the Dashboard

Open a browser on your development machine and navigate to:

```
http://<PI_IP>/accelerometer/
```

You should see the four stat cards updating every 5 seconds, a line chart plotting all four series, and a table of raw readings. Tilt the sensor and watch the X and Y values change. Tap the desk next to the sensor and watch the magnitude spike.

If the dashboard shows no data, check that the C logger is running (`sudo systemctl status accel-logger.service`) and that data exists in the database (`mysql -u sensor_user -p sensor_log -e "SELECT COUNT(*) FROM accelerometer_readings;"`).

---

## Making the Dashboard Persistent

The dashboard is served by Apache, which starts automatically on boot. There is nothing extra to configure — your LAMP stack from Part 2 handles this. The PHP dashboard lives at `/var/www/html/accelerometer/` and is available as long as Apache is running.

However, there is one edge case: if the sensor logger service starts before MariaDB is ready, the logger fails to connect and enters its restart loop. After 10 seconds it retries, and by that point MariaDB is usually available. If you find that the logger consistently fails to start on boot, add a longer sleep to the service unit:

```ini
ExecStartPre=/bin/sleep 15
ExecStart=/opt/sensor-logger/accel_logger
```

The `ExecStartPre` directive runs a 15-second sleep before the main process starts, giving MariaDB time to initialize. This is a crude solution — a more robust approach is to use `systemd` socket activation for MariaDB, but that is beyond the scope of this guide. For a home IoT project, the sleep approach is reliable enough.

---

## Real-World Applications

A 3-axis accelerometer logging to a database sounds like a science experiment. Here are three concrete applications that use this exact setup:

### Dryer Cycle Detection

Mount the sensor to the side of your clothes dryer. While the dryer runs, the accelerometer registers continuous vibration. When the cycle ends, vibration stops and the magnitude drops to a steady 1 g (gravity only). A PHP script that queries the last 5 minutes of magnitude data and detects sustained stillness triggers a push notification to your phone. You stop forgetting laundry in the drum.

### Door and Window Monitoring

Mount the sensor on a door or window. The accelerometer registers a distinct change in orientation when the door opens or closes (gravity shifts from one axis to another). Your dashboard logs every state change, and you can query the database to see exactly when someone entered or left a room.

### Vibration Analysis

Mount the sensor on machinery — a server rack fan, a refrigerator compressor, a washing machine. Log the vibration magnitude over days and weeks. When the baseline vibration pattern changes, it indicates wear or imbalance. Your PHP dashboard flags the anomaly. You fix the bearing before it fails.

---

## Best Practices

### Log Rotation

The `accelerometer_readings` table grows without bound. At a 4-second interval, that is 21,600 rows per day. After a year, that is nearly 8 million rows. The index on `recorded_at` keeps queries fast, but the table size still consumes disk space. Implement a cleanup strategy:

```sql
DELETE FROM accelerometer_readings WHERE recorded_at < NOW() - INTERVAL 30 DAY;
```

Run this as a cron job weekly:

```bash
crontab -e
```

Add:

```
0 3 * * 0 /usr/bin/mysql -u sensor_user -p'sensor_password' sensor_log -e "DELETE FROM accelerometer_readings WHERE recorded_at < NOW() - INTERVAL 30 DAY;"
```

This deletes data older than 30 days every Sunday at 3 AM. Adjust the retention period based on your needs.

### Error Handling in the Logger

The C program in this guide prints errors to stderr but does not attempt reconnection if the database connection drops mid-loop. In production, wrap the database operations in a retry loop:

```c
static int ensure_connection(MYSQL **conn) {
    if (mysql_ping(*conn) != 0) {
        mysql_close(*conn);
        *conn = init_database();
        if (*conn == NULL) return -1;
    }
    return 0;
}
```

Call `ensure_connection` before every insert. This handles the case where MariaDB is restarted while the logger is running.

### SD Card Health

The logger writes to the database, which writes to the SD card. SD cards have limited write endurance. Mitigate this:

- Use a high-endurance SD card (samsung PRO Endurance, SanDisk Max Endurance)
- Move the MariaDB data directory to an external USB SSD if you log for months or years
- Increase the polling interval from 4 seconds to 30 or 60 seconds if sub-second precision is not needed

---

## Troubleshooting

### Sensor Not Detected on I2C Bus

Run `i2cdetect -y 1`. If the address `0x1D` does not appear:

- Verify the sensor is powered (VCC to 3.3 V, GND to ground)
- Swap SDA and SCL — some breakout boards label them reversed
- Check for loose jumper wires
- Try a lower I2C bus speed by editing `/boot/config.txt` and adding `dtparam=i2c_arm_baudrate=10000`, then reboot

### Logger Compilation Errors

If `gcc` complains about missing `mysql/mysql.h`, install the MariaDB development headers:

```bash
sudo apt install libmariadb-dev
```

If it complains about `linux/i2c-dev.h`:

```bash
sudo apt install libi2c-dev
```

### Database Connection Refused

Verify MariaDB is running and listening on localhost:

```bash
sudo systemctl status mysql
sudo ss -tlnp | grep 3306
```

If nothing is listening on port 3306, start MariaDB:

```bash
sudo systemctl start mysql
```

If MariaDB is listening but the logger still cannot connect, verify the `sensor_user` account exists and has a correct password:

```bash
mysql -u sensor_user -p sensor_log
```

### Empty Dashboard

The dashboard shows no data if the C logger is not running or the database is empty:

```bash
sudo systemctl status accel-logger.service
mysql -u sensor_user -p sensor_log -e "SELECT COUNT(*) FROM accelerometer_readings;"
```

If the logger is stopped, start it:

```bash
sudo systemctl start accel-logger.service
```

If the logger is running but the database is empty, check the journal for I2C errors:

```bash
sudo journalctl -u accel-logger.service --since "5 minutes ago"
```

---

## Next Steps

You now have a complete IoT sensor pipeline running on a Raspberry Pi. An MMA8452Q accelerometer sends physical acceleration data over I2C to a C program, which inserts readings into MariaDB. A systemd service keeps the logger running across reboots. A PHP dashboard served by Apache visualizes the data in real time. The entire system runs on 5 watts of power.

From here, the possibilities expand:

- Add a DHT22 temperature and humidity sensor to the same I2C bus (different address, same wiring pattern)
- Build a multi-sensor dashboard that shows accelerometer, temperature, and humidity on the same page
- Replace the PHP dashboard polling with WebSockets for sub-second updates
- Add push notifications using Firebase Cloud Messaging when the sensor detects an event
- Connect multiple Pi sensor nodes across your house and aggregate data on a central server

The pattern you have built here — hardware sensor, C or Python reader, database, web dashboard — is the same pattern used in industrial IoT installations that cost tens of thousands of dollars. You built it on a $35 computer with tools you already know.

Your Pi has eyes and ears now. The question is what you will teach it to notice.
