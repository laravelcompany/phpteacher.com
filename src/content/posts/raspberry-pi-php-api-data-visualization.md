---
title: "Raspberry Pi API and Data Visualization With PHP and Chart.js"
description: "Build a PHP web API on your Raspberry Pi to serve accelerometer data and visualize it with Chart.js. Complete IoT dashboard tutorial with sensor data plotting."
pubDate: "2022-04-12 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Raspberry Pi", "PHP", "API", "Chart.js", "Data Visualization", "IoT", "Sensors", "Dashboard"]
selected: false
---

# Raspberry Pi API and Data Visualization With PHP and Chart.js

You have a C program running as a systemd service on your Raspberry Pi, reading an MMA8452Q accelerometer at one-second intervals and inserting every reading into a MariaDB database. Your sensor has been logging data for hours — maybe days. Thousands of rows sit in a MySQL table, each containing a timestamp and three acceleration values.

Now what?

Raw data in a database is like a book on a shelf in a language nobody reads. You need a translator — something that pulls those rows out of MySQL, packages them into a format a browser understands, and renders them as a chart a human can interpret at a glance. That is exactly what this guide delivers.

This is Part 4 of the Raspberry Pi home hacking series. You need Part 3 working — the sensor logger writing to MySQL — before you start here. If you need to catch up:

- [Part 1](/posts/raspberry-pi-home-hacking-guide): Raspberry Pi setup and OS configuration
- [Part 2](/posts/raspberry-pi-lamp-stack-install): LAMP stack installation
- [Part 3](/posts/raspberry-pi-accelerometer-sensor-data-logging): Accelerometer wiring, C logger, and data logging

This part covers building a PHP API layer between MySQL and the browser, creating an interactive Chart.js dashboard that plots X, Y, and Z acceleration over time, adding date-range filtering, auto-refresh, and a responsive layout that works on phones and tablets.

---

## Architecture Overview

The system has four layers. Understanding how they fit together makes the rest of the guide easier to follow.

1. **Hardware and logger** — The MMA8452Q accelerometer connected to the Pi's I2C bus, read every second by a C program that writes to MySQL.
2. **Database** — MariaDB with a single `readings` table that stores timestamped acceleration values for X, Y, and Z axes.
3. **PHP API** — A set of PHP scripts that query the database, format the results as JSON, and serve them over HTTP. This is the bridge between the database and the front-end.
4. **JavaScript front-end** — HTML and vanilla JavaScript running in the browser that fetches JSON from the PHP API, passes it to Chart.js, and renders an interactive multi-line chart. The user never talks to the database directly.

The API layer is critical. Without it, the browser would need direct MySQL credentials, which is a security disaster. The API enforces access control, sanitizes inputs, and returns only the data the front-end needs — no raw table access, no SQL injection holes, no exposed credentials.

---

## Database Schema Review

Before you write any PHP, confirm your `readings` table looks like this. Log into MariaDB and check:

```bash
sudo mariadb -u root -p
```

```sql
USE sensor_db;
DESCRIBE readings;
```

You should see:

| Field  | Type          | Null | Key | Default           | Extra          |
|--------|---------------|------|-----|-------------------|----------------|
| id     | int(11)       | NO   | PRI | NULL              | auto_increment |
| x      | float         | NO   |     | NULL              |                |
| y      | float         | NO   |     | NULL              |                |
| z      | float         | NO   |     | NULL              |                |
| logged_at | datetime   | NO   | MUL | current_timestamp |                |

The `logged_at` column should have an index. If you followed Part 3, it already does. That index is essential — every API query filters and sorts by `logged_at`, and without the index your queries slow to a crawl once the table grows past a few thousand rows.

Verify the index:

```sql
SHOW INDEX FROM readings;
```

You should see an entry for `logged_at` with `Seq_in_index` of 1. If it is missing, add it:

```sql
ALTER TABLE readings ADD INDEX idx_logged_at (logged_at);
```

Check how much data you have:

```sql
SELECT MIN(logged_at), MAX(logged_at), COUNT(*) FROM readings;
```

If your logger has been running for a while, you should see a satisfying row count. I had about 86,000 rows after 24 hours of logging.

---

## PHP API: The Data Bridge

The API consists of a single PHP file — `api.php` — that handles all requests through a router. A clean API has predictable endpoints, consistent JSON response formats, and proper HTTP status codes. One file with a simple router keeps deployment trivial and avoids the complexity of a framework for a single-purpose device like a Pi.

### Directory Structure

On your Raspberry Pi, create the API directory inside your web root:

```bash
sudo mkdir -p /var/www/html/api
```

All the files for this project live under `/var/www/html`. The structure looks like this:

```
/var/www/html/
├── api/
│   ├── api.php          # REST API entry point
│   └── .htaccess        # URL rewriting
├── dashboard/
│   └── index.html       # Chart.js dashboard
├── includes/
│   └── db.php           # Database connection config
└── index.php            # (optional, your existing index)
```

### Database Connection

Create the database configuration file. This file is included by every script that needs database access. Keeping it separate means you change credentials in one place.

```php
<?php
// /var/www/html/includes/db.php

declare(strict_types=1);

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = 'localhost';
        $db   = 'sensor_db';
        $user = 'sensor_user';
        $pass = 'your_strong_password_here';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, $user, $pass, $options);
    }

    return $pdo;
}
```

The `static $pdo` variable reuses the same connection for the lifetime of the request. The character set is `utf8mb4` because you never know when someone wants emoji in their IoT dashboard labels. `EMULATE_PREPARES` is off so real prepared statements go to the database — this prevents SQL injection even if you make a mistake later.

Set the correct permissions so Apache can read the file but the world cannot:

```bash
sudo chown www-data:www-data /var/www/html/includes/db.php
sudo chmod 640 /var/www/html/includes/db.php
```

### RESTful API Endpoint Design

The API exposes three endpoints, all handled by the same `api.php` file:

| Endpoint             | Method | Description                                      |
|----------------------|--------|--------------------------------------------------|
| `/api/api.php/readings` | GET    | Returns the latest N readings (default 100)     |
| `/api/api.php/range`    | GET    | Returns readings between two timestamps         |
| `/api/api.php/latest`   | GET    | Returns the single most recent reading          |

All responses use this JSON envelope:

```json
{
    "success": true,
    "data": [ ... ],
    "count": 100,
    "query": {
        "from": null,
        "to": null,
        "limit": 100
    }
}
```

Error responses look like:

```json
{
    "success": false,
    "error": "Invalid date format. Use YYYY-MM-DD HH:MM:SS."
}
```

### The API Router

Here is the complete API file. Every section is annotated so you understand what it does and why.

```php
<?php
// /var/www/html/api/api.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');

$segments = explode('/', $path);
$endpoint = end($segments);

try {
    $pdo = getDbConnection();

    match ($endpoint) {
        'readings' => handleReadings($pdo),
        'range'    => handleRange($pdo),
        'latest'   => handleLatest($pdo),
        default    => throw new \RuntimeException('Unknown endpoint'),
    };
} catch (\RuntimeException $e) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
```

The router uses PHP 8.0's `match` expression. It parses the URL, extracts the last path segment, and dispatches to the appropriate handler function. CORS headers are wide open because the dashboard runs on the same origin — if you ever proxy through a different domain you need to tighten them.

### Handler: Latest Readings

The `readings` endpoint returns the most recent readings with an optional `limit` parameter. The default is 100, the maximum is 1000.

```php
function handleReadings(PDO $pdo): void
{
    $limit = isset($_GET['limit'])
        ? min(max((int) $_GET['limit'], 1), 1000)
        : 100;

    $stmt = $pdo->prepare(
        'SELECT x, y, z, logged_at
         FROM readings
         ORDER BY logged_at DESC
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetchAll();
    $data = array_reverse($data);

    echo json_encode([
        'success' => true,
        'data'    => $data,
        'count'   => count($data),
        'query'   => [
            'from'  => null,
            'to'    => null,
            'limit' => $limit,
        ],
    ]);
}
```

`array_reverse` puts the readings in chronological order so Chart.js draws the line chart left-to-right correctly. The `bindValue` call with `PARAM_INT` prevents SQL injection on the limit parameter.

### Handler: Date Range

The `range` endpoint accepts `from` and `to` query parameters and returns all readings in that window. Both parameters are required and must match the MySQL datetime format.

```php
function handleRange(PDO $pdo): void
{
    $from = $_GET['from'] ?? null;
    $to   = $_GET['to'] ?? null;

    if ($from === null || $to === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'Both "from" and "to" parameters are required.',
        ]);
        return;
    }

    $fromDt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $from);
    $toDt   = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $to);

    if ($fromDt === false || $toDt === false) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'Invalid date format. Use YYYY-MM-DD HH:MM:SS.',
        ]);
        return;
    }

    $stmt = $pdo->prepare(
        'SELECT x, y, z, logged_at
         FROM readings
         WHERE logged_at BETWEEN :from AND :to
         ORDER BY logged_at ASC'
    );
    $stmt->bindValue(':from', $from, PDO::PARAM_STR);
    $stmt->bindValue(':to', $to, PDO::PARAM_STR);
    $stmt->execute();

    $data = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data'    => $data,
        'count'   => count($data),
        'query'   => [
            'from'  => $from,
            'to'    => $to,
            'limit' => null,
        ],
    ]);
}
```

Using `DateTimeImmutable::createFromFormat` gives strict validation — the string must match the exact format. `strtotime` would be too permissive and could lead to surprising behavior. Note the `BETWEEN` operator includes both boundaries, which is exactly what you want for inclusive date ranges.

### Handler: Latest Single Reading

The `latest` endpoint returns the single most recent reading. The dashboard uses this for live updates without redrawing the entire chart.

```php
function handleLatest(PDO $pdo): void
{
    $stmt = $pdo->query(
        'SELECT x, y, z, logged_at
         FROM readings
         ORDER BY logged_at DESC
         LIMIT 1'
    );

    $data = $stmt->fetch();

    if ($data === false) {
        echo json_encode([
            'success' => true,
            'data'    => null,
            'count'   => 0,
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'data'    => $data,
        'count'   => 1,
    ]);
}
```

This endpoint returns `null` data instead of an error when no readings exist. That lets the front-end handle the empty state gracefully — show a "Waiting for data..." message rather than a broken chart.

### .htaccess for Clean URLs

Create an `.htaccess` file in the `api` directory so Apache rewrites requests properly:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ api.php/$1 [QSA,L]
```

Enable `mod_rewrite` if you have not already:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Test the API immediately after setup:

```bash
curl http://localhost/api/api.php/latest
```

You should see a JSON response with your most recent accelerometer reading. If you get a blank page or a 500 error, check Apache's error log:

```bash
sudo tail -f /var/log/apache2/error.log
```

The most common issue is a database connection failure — double-check the credentials in `db.php` and confirm the `sensor_user` has the correct privileges.

---

## The Dashboard: HTML and Chart.js

The dashboard is a single HTML file that loads Chart.js from a CDN, fetches data from the API, and renders an interactive line chart. Keeping everything in one file means you can edit the dashboard on the Pi with nano or vi and see changes immediately — no build step, no bundler, no npm install.

### Complete Dashboard File

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raspberry Pi Accelerometer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <style>
        body {
            background: #0d1117;
            color: #c9d1d9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
        }
        .card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
        }
        .card-header {
            background: #21262d;
            border-bottom: 1px solid #30363d;
            color: #58a6ff;
            font-weight: 600;
        }
        .btn-primary {
            background: #238636;
            border: 1px solid rgba(240, 246, 252, 0.1);
        }
        .btn-primary:hover {
            background: #2ea043;
        }
        .stats-box {
            text-align: center;
            padding: 1rem;
        }
        .stats-box .value {
            font-size: 1.8rem;
            font-weight: 700;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }
        .stats-box .label {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #8b949e;
            letter-spacing: 0.05em;
        }
        .stat-x .value { color: #f85149; }
        .stat-y .value { color: #3fb950; }
        .stat-z .value { color: #58a6ff; }
        #chart-container {
            position: relative;
            height: 450px;
        }
        footer {
            text-align: center;
            padding: 2rem 0;
            color: #484f58;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <span style="color: #58a6ff;">&#9679;</span>
            Accelerometer Dashboard
        </h1>
        <div>
            <span class="badge bg-success" id="connection-status">Connected</span>
            <span class="badge bg-secondary" id="reading-count">0 readings</span>
        </div>
    </header>

    <div class="row g-3 mb-4" id="live-stats">
        <div class="col-md-4">
            <div class="card stats-box stat-x">
                <div class="label">X Axis</div>
                <div class="value" id="val-x">--</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-box stat-y">
                <div class="label">Y Axis</div>
                <div class="value" id="val-y">--</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-box stat-z">
                <div class="label">Z Axis</div>
                <div class="value" id="val-z">--</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Acceleration Over Time</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="btn-1m">1 min</button>
                <button class="btn btn-sm btn-outline-secondary" id="btn-5m">5 min</button>
                <button class="btn btn-sm btn-outline-secondary" id="btn-15m">15 min</button>
                <button class="btn btn-sm btn-outline-secondary" id="btn-1h">1 hour</button>
            </div>
        </div>
        <div class="card-body">
            <div id="chart-container">
                <canvas id="accel-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Custom Date Range</div>
        <div class="card-body">
            <form id="date-range-form" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="from-date" class="form-label">From</label>
                    <input type="datetime-local" class="form-control form-control-sm" id="from-date" required>
                </div>
                <div class="col-md-4">
                    <label for="to-date" class="form-label">To</label>
                    <input type="datetime-local" class="form-control form-control-sm" id="to-date" required>
                </div>
                <div class="col-md-4 d-grid">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Range</button>
                </div>
            </form>
            <div id="date-range-error" class="text-danger mt-2 d-none"></div>
        </div>
    </div>
</div>

<footer>
    Raspberry Pi &middot; PHP API &middot; Chart.js &middot; MMA8452Q Accelerometer
</footer>

<script>
const API_BASE = '/api/api.php';

const chart = new Chart(document.getElementById('accel-chart'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            {
                label: 'X Axis',
                data: [],
                borderColor: '#f85149',
                backgroundColor: 'rgba(248, 81, 73, 0.1)',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.2,
                fill: false,
            },
            {
                label: 'Y Axis',
                data: [],
                borderColor: '#3fb950',
                backgroundColor: 'rgba(63, 185, 80, 0.1)',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.2,
                fill: false,
            },
            {
                label: 'Z Axis',
                data: [],
                borderColor: '#58a6ff',
                backgroundColor: 'rgba(88, 166, 255, 0.1)',
                borderWidth: 2,
                pointRadius: 0,
                tension: 0.2,
                fill: false,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 300,
        },
        interaction: {
            intersect: false,
            mode: 'index',
        },
        plugins: {
            legend: {
                labels: {
                    color: '#c9d1d9',
                    usePointStyle: true,
                    pointStyle: 'circle',
                },
            },
            tooltip: {
                backgroundColor: '#161b22',
                titleColor: '#c9d1d9',
                bodyColor: '#8b949e',
                borderColor: '#30363d',
                borderWidth: 1,
                padding: 10,
                callbacks: {
                    label: function (ctx) {
                        return ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(3) + ' g';
                    },
                },
            },
        },
        scales: {
            x: {
                ticks: {
                    color: '#8b949e',
                    maxTicksLimit: 15,
                    maxRotation: 45,
                },
                grid: {
                    color: 'rgba(48, 54, 61, 0.5)',
                },
            },
            y: {
                ticks: {
                    color: '#8b949e',
                },
                grid: {
                    color: 'rgba(48, 54, 61, 0.5)',
                },
                title: {
                    display: true,
                    text: 'Acceleration (g)',
                    color: '#8b949e',
                },
            },
        },
    },
});

let currentLimit = 100;
let refreshInterval = null;
const REFRESH_MS = 5000;

async function fetchData(endpoint) {
    const url = API_BASE + '/' + endpoint;
    const res = await fetch(url);
    if (!res.ok) {
        throw new Error('API returned ' + res.status);
    }
    return res.json();
}

function updateChart(data) {
    const labels = data.map(r => {
        const d = new Date(r.logged_at);
        return d.toLocaleTimeString('en-US', { hour12: false });
    });

    chart.data.labels = labels;
    chart.data.datasets[0].data = data.map(r => r.x);
    chart.data.datasets[1].data = data.map(r => r.y);
    chart.data.datasets[2].data = data.map(r => r.z);
    chart.update('none');

    document.getElementById('reading-count').textContent =
        data.length + ' readings';
}

function updateLiveStats(data) {
    if (data.length === 0) return;
    const last = data[data.length - 1];
    document.getElementById('val-x').textContent = last.x.toFixed(3);
    document.getElementById('val-y').textContent = last.y.toFixed(3);
    document.getElementById('val-z').textContent = last.z.toFixed(3);
}

async function loadReadings(limit) {
    try {
        const json = await fetchData('readings?limit=' + limit);
        if (json.success && json.data) {
            updateChart(json.data);
            updateLiveStats(json.data);
        }
        document.getElementById('connection-status').className = 'badge bg-success';
        document.getElementById('connection-status').textContent = 'Connected';
    } catch (e) {
        document.getElementById('connection-status').className = 'badge bg-danger';
        document.getElementById('connection-status').textContent = 'Disconnected';
    }
}

async function loadRange(from, to) {
    try {
        const json = await fetchData(
            'range?from=' + encodeURIComponent(from) +
            '&to=' + encodeURIComponent(to)
        );
        if (json.success && json.data) {
            updateChart(json.data);
            updateLiveStats(json.data);
        }
    } catch (e) {
        const errEl = document.getElementById('date-range-error');
        errEl.textContent = 'Failed to load data for this range.';
        errEl.classList.remove('d-none');
    }
}

async function refreshLatest() {
    try {
        const json = await fetchData('latest');
        if (json.success && json.data) {
            updateLiveStats([json.data]);
        }
    } catch (e) {
        // Silent fail on live update — connection may be temporarily unavailable
    }
}

function startAutoRefresh() {
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(refreshLatest, REFRESH_MS);
}

document.getElementById('btn-1m').addEventListener('click', function () {
    currentLimit = 60;
    loadReadings(60);
});

document.getElementById('btn-5m').addEventListener('click', function () {
    currentLimit = 300;
    loadReadings(300);
});

document.getElementById('btn-15m').addEventListener('click', function () {
    currentLimit = 900;
    loadReadings(900);
});

document.getElementById('btn-1h').addEventListener('click', function () {
    currentLimit = 3600;
    loadReadings(3600);
});

document.getElementById('date-range-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const errEl = document.getElementById('date-range-error');
    errEl.classList.add('d-none');

    const fromVal = document.getElementById('from-date').value;
    const toVal = document.getElementById('to-date').value;

    if (!fromVal || !toVal) return;

    const from = fromVal.replace('T', ' ') + ':00';
    const to = toVal.replace('T', ' ') + ':00';

    loadRange(from, to);
});

loadReadings(100);
startAutoRefresh();
</script>
</body>
</html>
```

Save this file:

```bash
sudo nano /var/www/html/dashboard/index.html
```

Point your browser to `http://raspberry-pi.local/dashboard/` and you should see a dark-themed dashboard with your accelerometer data plotted on a live chart.

---

## How the Dashboard Works

The JavaScript does four things, and understanding each one helps you modify the dashboard later.

### Data Fetching

The `fetchData` function takes an endpoint path and returns parsed JSON. Every call goes through the same function, which means error handling and authentication (when you add it later) live in one place. The function throws on non-OK HTTP status codes so the calling code can catch network errors without checking `res.ok` every time.

### Chart Updates

`updateChart` maps the raw API response into Chart.js datasets. It converts each `logged_at` string into a localized time string — `HH:MM:SS` format — and assigns X, Y, and Z values to their respective datasets. The `chart.update('none')` call tells Chart.js to redraw without animation, which keeps the chart responsive when you switch between time ranges.

### Live Stats

Three stat boxes at the top show the most recent X, Y, and Z values. These update every five seconds via the `refreshLatest` function, which calls the `/latest` endpoint and updates the stat boxes without touching the chart. This is more efficient than redrawing the entire chart for every refresh — a one-second logger would produce 3600 data points per hour, and redrawing the chart every five seconds with thousands of points causes visible lag on a Pi.

### Range Selectors

The four preset buttons (1 min, 5 min, 15 min, 1 hour) call `loadReadings` with different limits. Each button loads a fresh dataset and redraws the chart. The custom date-range form converts the browser's `datetime-local` format — which uses a `T` separator — into MySQL's `YYYY-MM-DD HH:MM:SS` format by replacing the `T` with a space and appending `:00` seconds.

---

## Chart.js Configuration Explained

The chart is a multi-line chart with three datasets. Several configuration details matter for sensor data.

**`pointRadius: 0`** — At one reading per second, a chart with 100 points would have 100 dots per line. That is visual clutter. Removing the points keeps the lines clean and readable.

**`tension: 0.2`** — A small tension curves the lines slightly between data points. Real sensor data has noise, and a straight line between every point creates a jagged sawtooth. The tension smooths it just enough to see the trend without hiding the signal.

**`maxTicksLimit: 15`** — The x-axis represents time. With hundreds of points, Chart.js would try to label every point and produce an unreadable mess. This limit caps the number of tick labels to 15, which gives a clean time axis regardless of the data count.

**`animation.duration: 300`** — Full animation takes 800 milliseconds by default. That is too slow for a live dashboard. Dropping it to 300 ms keeps transitions smooth without feeling sluggish.

**Tooltip callback** — The custom tooltip label appends "g" (units of gravity) to each value so you know the scale. Acceleration values hover around -1 to +1 g when the sensor is stationary. If you see values in the hundreds, your sensor is either accelerating hard or your data parsing is wrong.

---

## Date Range Filtering

The date range form lets you zoom into any time window. The `datetime-local` input type gives you a browser-native date and time picker. When you submit the form, JavaScript converts the values to MySQL format and calls the `range` API endpoint.

The PHP handler validates both dates with `DateTimeImmutable::createFromFormat`. If either date is malformed, the API returns a 400 status code with a descriptive error message. The front-end displays this error in a red alert box below the form.

The `BETWEEN` clause in the SQL query includes both boundary timestamps. If you select 10:00:00 to 10:01:00, you get readings at 10:00:00, 10:00:01, and 10:01:00 — every reading in that 61-second window. If you want an exclusive upper bound you need `logged_at >= :from AND logged_at < :to`, but for a sensor dashboard inclusive is the intuitive behavior.

---

## Auto-Refresh and Live Data

The dashboard auto-refreshes the live stats every five seconds using `setInterval`. The `refreshLatest` function calls `/api/api.php/latest` and updates only the three stat boxes. The chart itself does not auto-refresh — you reload it by clicking one of the preset buttons or submitting a custom range.

This design is intentional. The chart contains hundreds of data points. Redrawing it every five seconds would:

1. Waste CPU cycles on the Pi — every redraw triggers layout recalculations in the browser
2. Create visual flicker as the chart re-renders
3. Use unnecessary bandwidth fetching hundreds of rows every five seconds

If you need the chart to auto-refresh, you can add a periodic call to `loadReadings(currentLimit)` instead of `refreshLatest`. Change the `setInterval` callback:

```javascript
refreshInterval = setInterval(() => loadReadings(currentLimit), REFRESH_MS);
```

Test the performance on your Pi before deploying this. A Raspberry Pi Zero 2 W running Chromium may struggle with frequent full chart redraws above 500 data points.

---

## Security Considerations for IoT APIs

Your Raspberry Pi is on your local network, but that does not mean you should ignore security. IoT devices have a reputation for being the weakest link in home networks, and a PHP API with default credentials is an open door.

### Database Credentials

The `db.php` file contains a plaintext MySQL password. Set restrictive filesystem permissions:

```bash
sudo chown www-data:www-data /var/www/html/includes/db.php
sudo chmod 640 /var/www/html/includes/db.php
```

This makes the file readable only by the web server user and the `www-data` group. No other user on the Pi can read it.

Create a dedicated MySQL user with minimal privileges instead of using root:

```sql
CREATE USER 'sensor_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT ON sensor_db.readings TO 'sensor_user'@'localhost';
FLUSH PRIVILEGES;
```

This user can only SELECT from the `readings` table. If the credentials leak, an attacker cannot drop tables, insert fake data, or access any other database.

### Input Validation

Every user-supplied parameter enters the database through a prepared statement. The `limit` parameter is cast to an integer and clamped between 1 and 1000. Date parameters are validated against a strict format. No raw user input ever reaches the SQL string.

### HTTPS

Your dashboard communicates over plain HTTP on the local network. This is acceptable for a home project where you trust every device on your LAN. If you expose the dashboard to the internet — through port forwarding or a reverse proxy — add HTTPS immediately. Let's Encrypt provides free certificates. Install Certbot on the Pi:

```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d your-domain.com
```

### Rate Limiting

The API has no rate limiting in this version. A misbehaving client or a script that polls the API in a tight loop can hammer your Pi's database. Apache's `mod_ratelimit` provides basic bandwidth throttling, or you can add a simple rate limiter in PHP using `$_SERVER['REMOTE_ADDR']` and a timestamp file or a Redis counter.

```apache
<IfModule mod_ratelimit.c>
    <Location /api/>
        SetOutputFilter RATE_LIMIT
        SetEnv rate-limit 400
    </Location>
</IfModule>
```

This limits throughput on the `/api/` path to 400 KB per second, which is enough for normal dashboard traffic but prevents your Pi's network from being saturated by an abusive client.

---

## Performance Tips for the Pi

A Raspberry Pi is not a cloud server. Every optimization matters, especially when you run the data logger, the database, Apache, and serve the dashboard all on the same tiny ARM board.

### Database Indexing

The `logged_at` index is non-negotiable. Without it, every range query performs a full table scan. Verify the index exists and monitor its usage:

```sql
SELECT index_name, seq_in_index, column_name
FROM information_schema.statistics
WHERE table_schema = 'sensor_db' AND table_name = 'readings';
```

### Query Limits

Always limit query results. The `readings` endpoint caps at 1000 rows. The `range` endpoint has no cap — a query spanning a full day of 1-second readings returns 86,400 rows. If your front-end struggles with that volume, add pagination or implement data downsampling on the PHP side.

### Downsampling for Large Ranges

For long date ranges, you do not need every individual reading. The human eye cannot distinguish 86,400 individual pixels on a chart. Implement downsampling by averaging readings into buckets:

```php
function handleRange(PDO $pdo): void
{
    // ... validation ...

    $intervalMinutes = 1; // Downsample to 1-minute averages

    $stmt = $pdo->prepare(
        'SELECT
            AVG(x) AS x,
            AVG(y) AS y,
            AVG(z) AS z,
            DATE_FORMAT(logged_at, "%Y-%m-%d %H:%i:00") AS logged_at
         FROM readings
         WHERE logged_at BETWEEN :from AND :to
         GROUP BY DATE_FORMAT(logged_at, "%Y-%m-%d %H:%i:00")
         ORDER BY logged_at ASC'
    );
    // ...
}
```

This reduces 86,400 rows to 1,440 one-minute averages. The chart looks nearly identical and the page loads in milliseconds instead of seconds.

### Apache Tuning

The default Apache configuration works, but tuning `MaxClients` and `KeepAlive` reduces memory pressure:

```apache
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5
```

On a Pi with 512 MB of RAM (Zero 2 W or Pi 3), lower `MaxClients` to 10. On a Pi 4 or 5 with 2+ GB, 25 is safe.

---

## Testing the Full Stack

After everything is configured, verify the entire pipeline works end to end.

1. **Sensor logging** — SSH into the Pi and check that new rows appear in the database every second:
   ```bash
   sudo mariadb -u root -p -e "SELECT COUNT(*) FROM sensor_db.readings;"
   ```
   Run the command twice, wait 10 seconds between runs. The count should increase by approximately 10.

2. **API endpoint** — Test each endpoint with curl:
   ```bash
   curl http://localhost/api/api.php/latest
   curl http://localhost/api/api.php/readings?limit=5
   curl 'http://localhost/api/api.php/range?from=2022-04-12+00:00:00&to=2022-04-12+23:59:59'
   ```

3. **Dashboard** — Open a browser on any device on your network and navigate to `http://raspberry-pi.local/dashboard/`. You should see the chart rendering your sensor data within seconds.

4. **Date filtering** — Click the 1-hour button to load 3600 readings. Submit a custom range that covers a window you know has data. Verify the chart updates.

5. **Auto-refresh** — Move the sensor (or tap it gently) and watch the live stats update within five seconds. The X, Y, and Z values should change in real time.

If any step fails, check Apache's error log and MariaDB's slow query log:

```bash
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/mysql/mariadb-slow.log
```

The slow query log requires enabling in MariaDB config. Add to `/etc/mysql/mariadb.conf.d/50-server.cnf`:

```ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mariadb-slow.log
long_query_time = 2
```

Then restart MariaDB:

```bash
sudo systemctl restart mariadb
```

---

## What You Built

You now have a complete IoT sensor pipeline running on a $35 computer:

- **Hardware** — MMA8452Q accelerometer wired to the Pi's GPIO header, communicating over I2C
- **Logger** — C program running as a systemd service, reading the sensor every second and inserting data into MariaDB
- **API** — PHP REST API with three endpoints, prepared statements, strict input validation, and JSON responses
- **Dashboard** — Single-page HTML application using Chart.js, Bootstrap 5, and vanilla JavaScript
- **Live updates** — Auto-refresh every five seconds that updates live stats without redrawing the full chart
- **Date filtering** — Four preset time ranges plus a custom date picker

This architecture scales beyond accelerometers. Swap the sensor for a temperature probe, a humidity sensor, a motion detector, or a light sensor, and the same API and dashboard work with minimal changes. The PHP handlers do not care what the data represents — they just read timestamps and floating-point numbers from a table and hand them to the front-end.

The principles here — a RESTful API between the database and the browser, prepared statements for security, Chart.js for visualization, and a responsive layout — apply to any data logging project you build on the Pi.

---

## Next Steps

The sensor is logging, the API is serving, and the dashboard is displaying. Where do you go from here?

**Add more sensors** — Wire additional I2C sensors to the same bus. Each sensor has a unique address, so they share SDA and SCL without conflict. Add columns to the `readings` table or create separate tables per sensor type.

**Alerting** — Write a PHP script that checks the latest reading and sends a push notification through Pushover or a webhook when acceleration exceeds a threshold. Run it as a cron job every minute.

**Data export** — Add a `/api/api.php/export` endpoint that returns CSV instead of JSON. A simple `Content-Type: text/csv` header and `fputcsv` loop is all it takes.

**Historical aggregation** — Create a nightly cron job that computes hourly and daily averages and inserts them into summary tables. The dashboard can then switch between raw data and aggregated views.

**Remote access** — Set up a Cloudflare Tunnel or Tailscale so you can check your sensor dashboard from anywhere without opening ports on your home router.

The full source code for this project is available in the project repository. Clone it, modify it, and make it your own. Your Pi is no longer just a hobby board — it is a data acquisition system with a web dashboard that you built yourself.
