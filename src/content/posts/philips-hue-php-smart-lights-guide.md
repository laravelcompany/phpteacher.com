---
title: "Control Philips Hue Smart Lights With PHP - Complete Guide"
description: "Learn how to control Philips Hue smart lights using PHP. Connect to the Hue Bridge, manage bulbs, create scenes, and build a web dashboard for your smart home lighting."
pubDate: "2022-06-10 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Philips Hue", "PHP", "Smart Home", "IoT", "API", "Lighting", "REST API"]
selected: false
---

If you own Philips Hue smart bulbs, you already know how convenient they are with the mobile app. But did you know you can control every light in your home directly from PHP? The Hue Bridge exposes a local REST API that gives you full programmatic control over bulbs, scenes, sensors, and schedules. No cloud dependency, no subscription fees — just your PHP app talking to your Bridge over your local network.

This guide walks you through connecting to the Hue Bridge, manipulating lights, building automations, and putting a web dashboard on top. All code targets PHP 8.1+ and uses zero proprietary SDKs — just `json_encode`, `curl`, and the Hue API docs.

## The Philips Hue Ecosystem

Before writing any code, you need to understand the hardware you're talking to.

At the center sits the **Hue Bridge** — a small rectangular box that plugs into your router via Ethernet. The Bridge speaks Zigbee to your bulbs, sensors, and switches, and exposes a REST API over HTTP on your local network. Every command you send goes to the Bridge, and the Bridge relays it to the bulbs.

**Bulbs** come in various generations: white-only, white-ambiance (tunable white temperature), and color (full RGB plus white temperature). The API treats them uniformly — each bulb has a unique ID, a state object, and a set of capabilities.

**Sensors** include motion detectors, ambient light sensors, temperature sensors, and the Hue Tap or dimmer switches. The Bridge polls these sensors and exposes their state through the API, so your PHP code can react to motion, button presses, or ambient brightness changes.

The whole system runs locally. Philips does offer a cloud bridge, but the local API is more reliable, faster, and keeps your data off the internet.

## Finding Your Bridge on the Network

Philips uses UPnP (Universal Plug and Play) so the Bridge advertises itself on your network. You can discover it with a simple HTTP request to the Philips discovery service, or by sniffing your local network.

### Discovery via the Philips NUPNP Service

Philips runs a public endpoint that returns the internal IP addresses of Bridges on your network:

```php
$response = file_get_contents('https://discovery.meethue.com/');
$bridges = json_decode($response, true);

if (!empty($bridges)) {
    $bridgeIp = $bridges[0]['internalipaddress'];
    echo "Bridge found at: {$bridgeIp}\n";
}
```

This hits an external server, which some people dislike. The alternative is to scan your local network for port 80 on common Hue IPs, or check your router's DHCP lease table. In practice, the discovery endpoint is reliable and maintained by Signify (the company behind Philips Hue).

### Manual Configuration

If you'd rather hardcode the IP, log into your router, find the Bridge in the connected devices list (it usually shows as "Philips Hue" or "Bridge"), and note its IP. Put it in a config file or environment variable:

```php
define('HUE_BRIDGE_IP', '192.168.1.100');
```

Putting the IP in an environment variable keeps your code portable between environments.

## Pairing Your App With the Bridge

The Hue API requires you to register an application and get an API key. The process is simple but requires physical access to the Bridge — you must press the link button.

### Registering a New User

Press the physical button on top of your Hue Bridge, then immediately run this:

```php
$data = json_encode(['devicetype' => 'my_php_app#laptop']);

$ch = curl_init("http://{$bridgeIp}/api");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result[0]['success']['username'])) {
    $apiKey = $result[0]['success']['username'];
    echo "API Key: {$apiKey}\n";
} elseif (isset($result[0]['error'])) {
    echo "Error: " . $result[0]['error']['description'] . "\n";
}
```

If the button wasn't pressed, you get an error: "link button not pressed". Press it and try again within 30 seconds.

**Important**: Store this API key securely. Treat it like a password. Anyone with the key and network access to your Bridge can control every light in your home. Put it in a `.env` file, not in your source code.

```php
// config.php
define('HUE_BRIDGE_IP', $_ENV['HUE_BRIDGE_IP']);
define('HUE_API_KEY', $_ENV['HUE_API_KEY']);
```

## Building a PHP Client for the Hue API

Now you have an IP and a key. Let's build a reusable client. The Hue API lives at `http://{$ip}/api/{$apiKey}/` and returns JSON for every endpoint.

### A Minimal HTTP Client

```php
class HueClient
{
    private string $baseUrl;

    public function __construct(
        private readonly string $ip,
        private readonly string $apiKey,
    ) {
        $this->baseUrl = "http://{$ip}/api/{$apiKey}";
    }

    public function get(string $path): array
    {
        $response = file_get_contents("{$this->baseUrl}/{$path}");
        return json_decode($response, true);
    }

    public function put(string $path, array $data): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
            ],
        ]);
        $response = file_get_contents("{$this->baseUrl}/{$path}", false, $context);
        return json_decode($response, true);
    }

    public function post(string $path, array $data): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
            ],
        ]);
        $response = file_get_contents("{$this->baseUrl}/{$path}", false, $context);
        return json_decode($response, true);
    }
}
```

This client handles GET, POST, and PUT. The Hue API uses PUT for state changes and POST for resource creation. You can swap `file_get_contents` for cURL if you need more control over timeouts or error handling.

## Listing Lights and Reading Their State

The simplest endpoint is `/lights`. It returns every bulb registered with your Bridge.

```php
$hue = new HueClient(HUE_BRIDGE_IP, HUE_API_KEY);
$lights = $hue->get('lights');

foreach ($lights as $id => $light) {
    printf(
        "[%s] %s — %s | reachable: %s\n",
        $id,
        $light['name'],
        $light['state']['on'] ? 'on' : 'off',
        $light['state']['reachable'] ? 'yes' : 'no',
    );
}
```

Each light has a `state` object containing `on`, `bri` (brightness, 0–254), `hue` (0–65535), `sat` (saturation, 0–254), `ct` (color temperature in mireds), `xy` (CIE 1931 color coordinates), and `alert` / `effect` modes. The exact fields depend on the bulb model.

### Understanding Brightness, Hue, and Saturation

The Hue API uses raw ranges that don't map intuitively to percentages.

- **Brightness (`bri`)**: 0 to 254. Zero is off (but use `on: false` to turn lights off properly).
- **Hue (`hue`)**: 0 to 65535, wrapping around the color wheel. 0 is red, 25500 is green, 46920 is blue.
- **Saturation (`sat`)**: 0 to 254. 0 is white, 254 is fully saturated color.
- **Color temperature (`ct`)**: 153 to 500, in mireds. 153 is cool (6500K), 500 is warm (2000K).

Helper functions make these ranges pleasant to work with:

```php
function percentToBri(int $percent): int
{
    return (int) round(($percent / 100) * 254);
}

function hueFromDegrees(int $degrees): int
{
    return (int) round(($degrees / 360) * 65535);
}
```

## Controlling Lights: On, Off, Brightness, and Color

Turning a light on or off is a PUT to `/lights/{id}/state`.

```php
$hue->put('lights/1/state', ['on' => true]);   // turn on
$hue->put('lights/1/state', ['on' => false]);  // turn off
```

Set brightness and color in the same call:

```php
$hue->put('lights/1/state', [
    'on'  => true,
    'bri' => percentToBri(75),         // 75% brightness
    'hue' => hueFromDegrees(220),       // blue
    'sat' => 200,                       // high saturation
]);
```

For white-ambiance bulbs, set color temperature instead of hue/sat:

```php
$hue->put('lights/2/state', [
    'on' => true,
    'bri' => percentToBri(50),
    'ct' => 350,  // warm white, ~2850K
]);
```

### Transition Time

You can make lights fade smoothly instead of snapping. Add the `transitiontime` field (in units of 100 milliseconds):

```php
$hue->put('lights/1/state', [
    'on'  => true,
    'bri' => percentToBri(100),
    'transitiontime' => 10,  // 1 second fade
]);
```

A value of 10 means 1 second. For a 30-minute sunrise simulation, use `transitiontime: 18000`.

### Alert and Effect Modes

The Hue API has two built-in effects:

```php
// One blink
$hue->put('lights/1/state', ['alert' => 'select']);

// Multiple blinks
$hue->put('lights/1/state', ['alert' => 'lselect']);

// Color loop (only on color bulbs)
$hue->put('lights/1/state', ['effect' => 'colorloop']);
```

Turn off the color loop by setting `effect` back to `'none'`.

## Grouping Lights for Simultaneous Control

Controlling lights one by one is slow and causes visible delays between bulbs changing. Use groups instead.

The Hue Bridge already maintains groups for each room and zone you've configured in the mobile app. You can also create your own.

### Listing Groups

```php
$groups = $hue->get('groups');

foreach ($groups as $id => $group) {
    printf("[%s] %s — type: %s | lights: %s\n",
        $id,
        $group['name'],
        $group['type'],
        implode(', ', $group['lights']),
    );
}
```

### Controlling a Group

Send state changes to `/groups/{id}/action`:

```php
$hue->put('groups/1/action', [
    'on'  => true,
    'bri' => percentToBri(100),
    'hue' => hueFromDegrees(0),     // red
    'sat' => 254,
]);
```

The Bridge handles distributing the command to every bulb in the group simultaneously. This is much faster than individual calls and keeps all bulbs in sync.

### Creating a Custom Group

```php
$hue->post('groups', [
    'name' => 'Desk Area',
    'lights' => ['3', '4', '5'],
    'type' => 'LightGroup',
]);
```

## Scenes: Saving and Applying Light States

Scenes store a snapshot of every light's state so you can recall it later. You can create scenes through the API, or read existing ones created in the mobile app.

### Listing Scenes

```php
$scenes = $hue->get('scenes');

foreach ($scenes as $id => $scene) {
    printf("[%s] %s — lights: %s\n",
        $id,
        $scene['name'],
        implode(', ', $scene['lights']),
    );
}
```

### Creating a Scene

A scene stores per-light state and belongs to a group:

```php
$hue->post('scenes', [
    'name' => 'Relax Evening',
    'lights' => ['1', '2', '3'],
    'recycle' => true,
]);

// Then set each light's state in the scene
$sceneId = $response[0]['success']['id'];
$hue->put("scenes/{$sceneId}/lightstates/1", [
    'on' => true, 'bri' => 180, 'ct' => 400,
]);
$hue->put("scenes/{$sceneId}/lightstates/2", [
    'on' => true, 'bri' => 120, 'ct' => 350,
]);
$hue->put("scenes/{$sceneId}/lightstates/3", [
    'on' => true, 'bri' => 80, 'ct' => 300,
]);
```

### Recalling a Scene

```php
$hue->put("groups/{$groupId}/action", [
    'scene' => $sceneId,
]);
```

That's it. Every light in the group transitions to its scene state simultaneously.

## Schedules and Timers

The Hue Bridge runs schedules internally, so your PHP script doesn't need to stay running. You create a schedule as a resource on the Bridge, and the Bridge fires it at the specified time.

### Creating a Schedule

```php
$hue->post('schedules', [
    'name' => 'Morning Wake-Up',
    'description' => 'Turn on bedroom lights at 7 AM',
    'command' => [
        'address' => '/api/' . HUE_API_KEY . '/groups/1/action',
        'method' => 'PUT',
        'body' => [
            'on' => true,
            'bri' => percentToBri(100),
        ],
    ],
    'time' => '2022-06-11T07:00:00',
    'recycle' => true,
]);
```

Set `recycle` to `true` to auto-delete the schedule after it fires. For recurring schedules, you can use a cron-like `localtime` format:

```php
'time' => 'W124/T07:00:00',  // every weekday at 7 AM
```

The format is `W{bits}/T{time}` where `bits` is a bitmask of days (Monday=64, Tuesday=32, Wednesday=16, Thursday=8, Friday=4, Saturday=2, Sunday=1). Sum the bits for the days you want.

For example, weekdays = 64+32+16+8+4 = 124. Weekend = 2+1 = 3.

```php
// Weekdays at 7:00 AM, recycle so it repeats
$hue->post('schedules', [
    'name' => 'Weekday Wake-Up',
    'command' => [
        'address' => '/api/' . HUE_API_KEY . '/groups/1/action',
        'method' => 'PUT',
        'body' => [
            'on' => true,
            'bri' => percentToBri(80),
        ],
    ],
    'time' => 'W124/T07:00:00',
    'localtime' => 'W124/T07:00:00',
    'recycle' => true,
    'autodelete' => false,
]);
```

Using `localtime` instead of `time` makes the schedule work with your local timezone instead of UTC. The Bridge handles DST transitions correctly.

## Reacting to Sensor Events

The Hue Bridge tracks sensor state and exposes it through the API. You can poll sensors from PHP and react to changes. Motion sensors are the most useful.

### Reading Sensor State

```php
$sensors = $hue->get('sensors');

foreach ($sensors as $id => $sensor) {
    if ($sensor['type'] === 'ZLLPresence') {
        $presence = $sensor['state']['presence'] ? 'detected' : 'clear';
        echo "[{$id}] {$sensor['name']}: {$presence}\n";
    }
}
```

But polling is wasteful. You should poll once, cache the state, and only act on changes. Better yet, let the Bridge handle the logic through rules.

### Rules: Bridge-Level Automations

Rules let you define if-then logic directly on the Bridge. When a sensor's state changes, the rule fires a command. No PHP process needed.

```php
$hue->post('rules', [
    'name' => 'Motion → Hall Light',
    'conditions' => [
        [
            'address' => '/sensors/3/state/presence',
            'operator' => 'eq',
            'value' => 'true',
        ],
    ],
    'actions' => [
        [
            'address' => '/groups/2/action',
            'method' => 'PUT',
            'body' => [
                'on' => true,
                'bri' => 200,
            ],
        ],
    ],
]);
```

You can chain multiple conditions with AND logic, and multiple actions. This is how the official Hue app implements its "Hue Labs" formulas without running any external code.

### Timed Rules

Rules can also fire based on time. A common pattern: turn lights off 5 minutes after motion clears.

```php
$hue->post('rules', [
    'name' => 'Motion → Off After 5 Min',
    'conditions' => [
        [
            'address' => '/sensors/3/state/presence',
            'operator' => 'eq',
            'value' => 'false',
        ],
        [
            'address' => '/sensors/3/state/presence',
            'operator' => 'ddx',
            'value' => 'PT05:00',  // delayed for 5 minutes
        ],
    ],
    'actions' => [
        [
            'address' => '/groups/2/action',
            'method' => 'PUT',
            'body' => ['on' => false],
        ],
    ],
]);
```

The `ddx` operator means "delayed since this sensor's state changed to the current value." It fires the rule if the condition has been true for the specified duration.

## Building a PHP Web Dashboard

Let's bring everything together into a single-page web dashboard. This example uses no framework — just PHP with a clean separation between the API client and the presentation layer.

### Dashboard Controller

```php
// dashboard.php
require 'vendor/autoload.php';  // or just include HueClient.php

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$hue = new HueClient($_ENV['HUE_BRIDGE_IP'], $_ENV['HUE_API_KEY']);

$lights = $hue->get('lights');
$groups = $hue->get('groups');

$action = $_POST['action'] ?? '';
$lightId = $_POST['light_id'] ?? '';
$groupId = $_POST['group_id'] ?? '';

match ($action) {
    'toggle_light' => $hue->put("lights/{$lightId}/state", [
        'on' => $_POST['state'] === 'true',
    ]),
    'set_brightness' => $hue->put("lights/{$lightId}/state", [
        'on' => true,
        'bri' => (int) $_POST['bri'],
    ]),
    'group_on' => $hue->put("groups/{$groupId}/action", [
        'on' => true,
        'bri' => 200,
    ]),
    'group_off' => $hue->put("groups/{$groupId}/action", [
        'on' => false,
    ]),
    'scene' => $hue->put("groups/{$groupId}/action", [
        'scene' => $_POST['scene_id'],
    ]),
    default => null,
};

// Refresh state after action
$lights = $hue->get('lights');
$groups = $hue->get('groups');
```

### Simple HTML Template

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Hue Dashboard</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #111; color: #eee; padding: 2rem; }
        .card { background: #1e1e1e; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        .group { background: #2a2a2a; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; }
        .light { display: flex; align-items: center; gap: 1rem; padding: 0.5rem 0; }
        .on { color: #ffd700; }
        .off { color: #666; }
        button { padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; }
        .btn-on { background: #ffd700; color: #111; }
        .btn-off { background: #444; color: #aaa; }
        input[type="range"] { width: 200px; }
    </style>
</head>
<body>
    <h1>🏠 Hue Dashboard</h1>

    <?php foreach ($groups as $id => $group): ?>
    <div class="group">
        <h2><?= htmlspecialchars($group['name']) ?></h2>
        <form method="post" style="display:inline">
            <input type="hidden" name="group_id" value="<?= $id ?>">
            <button type="submit" name="action" value="group_on" class="btn-on">On</button>
            <button type="submit" name="action" value="group_off" class="btn-off">Off</button>
        </form>

        <?php foreach ($group['lights'] as $lightId): ?>
        <?php $light = $lights[$lightId] ?? null; if (!$light) continue; ?>
        <div class="light">
            <span class="<?= $light['state']['on'] ? 'on' : 'off' ?>">
                💡 <?= htmlspecialchars($light['name']) ?>
            </span>
            <span>(<?= $light['state']['on'] ? 'on' : 'off' ?>)</span>
            <form method="post" style="display:inline">
                <input type="hidden" name="light_id" value="<?= $lightId ?>">
                <input type="hidden" name="state" value="<?= $light['state']['on'] ? 'false' : 'true' ?>">
                <button type="submit" name="action" value="toggle_light">
                    Toggle
                </button>
            </form>
            <form method="post" style="display:inline">
                <input type="hidden" name="light_id" value="<?= $lightId ?>">
                <input type="range" name="bri" min="0" max="254"
                       value="<?= $light['state']['bri'] ?? 0 ?>"
                       onchange="this.form.submit()">
                <input type="hidden" name="action" value="set_brightness">
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</body>
</html>
```

This dashboard lists every room, shows each bulb, and lets you toggle or dim them. The slider auto-submits onchange — fast enough for a local network dashboard.

## Real-World Use Cases

A PHP-powered Hue setup opens up possibilities that the official app doesn't offer.

### Sunrise Alarm Clock

Simulate a sunrise over 30 minutes before your alarm. A cron job triggers a PHP script that gradually increases brightness and warms the color temperature.

```php
// wake-up.php — triggered by cron at 5:30 AM
$hue = new HueClient(HUE_BRIDGE_IP, HUE_API_KEY);
$bedroomGroup = '1';  // bedroom group ID

// Start very dim and warm
$hue->put("groups/{$bedroomGroup}/action", [
    'on' => true,
    'bri' => 1,
    'ct' => 500,
    'transitiontime' => 0,
]);

// 30-minute transition to full bright, cool white
$hue->put("groups/{$bedroomGroup}/action", [
    'bri' => 254,
    'ct' => 250,
    'transitiontime' => 18000,  // 30 minutes
]);
```

Cron entry: `30 5 * * * php /path/to/wake-up.php`

### Notification Lights

Flash lights when a deploy fails, an alert triggers, or an email arrives from a specific sender.

```php
// deploy-notify.php
function flashLights(HueClient $hue, string $groupId, string $color = 'red'): void
{
    $hue->put("groups/{$groupId}/action", [
        'on' => true,
        'hue' => $color === 'red' ? 0 : 25500,
        'sat' => 254,
        'bri' => 200,
        'alert' => 'lselect',  // blinks 60 seconds
    ]);
}

// Triggered by a webhook from your CI pipeline
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (($payload['status'] ?? '') === 'failure') {
        $hue = new HueClient(HUE_BRIDGE_IP, HUE_API_KEY);
        flashLights($hue, '2', 'red');
    }
}
```

### Mood Lighting via CLI

A quick CLI script for setting a mood:

```php
// mood.php
[$command, $group, ...$mood] = $argv;

$moods = [
    'focus'    => ['bri' => 254, 'ct' => 200],
    'relax'    => ['bri' => 100, 'ct' => 450],
    'party'    => ['bri' => 254, 'sat' => 254, 'effect' => 'colorloop'],
    'night'    => ['on' => false],
    'reading'  => ['bri' => 200, 'ct' => 300],
];

$group = $group ?: '1';
$moodName = $mood[0] ?? 'relax';

if (!isset($moods[$moodName])) {
    echo "Unknown mood. Available: " . implode(', ', array_keys($moods)) . "\n";
    exit(1);
}

$hue = new HueClient(HUE_BRIDGE_IP, HUE_API_KEY);
$state = array_merge(['on' => true], $moods[$moodName]);
$hue->put("groups/{$group}/action", $state);

echo "Set group {$group} to '{$moodName}' mood.\n";
```

Usage: `php mood.php 1 relax`

### Responding to Motion With PHP

If you want more complex logic than the Bridge's rules support, poll sensors from a long-running PHP script or a cron job.

```php
// motion-watch.php
$hue = new HueClient(HUE_BRIDGE_IP, HUE_API_KEY);
$lastState = null;

while (true) {
    $sensor = $hue->get('sensors/3');
    $current = $sensor['state']['presence'];

    if ($current !== $lastState) {
        $lastState = $current;

        if ($current) {
            $hue->put('groups/2/action', [
                'on' => true,
                'bri' => 150,
                'transitiontime' => 0,
            ]);
        } else {
            // Wait 5 minutes, then check again before turning off
            sleep(300);
            $sensor = $hue->get('sensors/3');
            if (!$sensor['state']['presence']) {
                $hue->put('groups/2/action', ['on' => false]);
            }
        }
    }

    sleep(2);  // poll interval
}
```

This runs as a daemon, which is fine for a dedicated Raspberry Pi or home server. For lighter weight, use the Bridge's built-in rules (shown earlier) and only use PHP for the occasional override.

## Automations With PHP Cron Jobs

Combining PHP scripts with cron gives you powerful recurring automations. Here are practical examples.

### Evening Wind-Down

```php
// 30 minutes before bedtime, dim lights and shift to warm
// cron: 0 21 * * * php evening-wind-down.php

$hue = new HueClient(HUE_BRIDGE_IP, HUE_API_KEY);

$hue->put('groups/1/action', [
    'bri' => 80,
    'ct' => 400,
    'transitiontime' => 600,  // 60 seconds
]);

// Turn off kitchen and hallway after 30 mins
$hue->post('schedules', [
    'name' => 'Auto Off - Night',
    'command' => [
        'address' => '/api/' . HUE_API_KEY . '/groups/3/action',
        'method' => 'PUT',
        'body' => ['on' => false],
    ],
    'localtime' => date('H:i:s', time() + 1800),
    'autodelete' => true,
]);
```

### Away Mode Simulation

Scramble light states at random intervals to simulate occupancy:

```php
// away-mode.php
// cron: */5 * * * * php away-mode.php

$hue = new HueClient(HUE_BRIDGE_IP, HUE_API_KEY);

$lights = $hue->get('lights');

foreach ($lights as $id => $light) {
    if (!($light['state']['reachable'] ?? false)) {
        continue;
    }

    // 40% chance to change this light
    if (rand(1, 100) > 40) {
        continue;
    }

    $state = ['transitiontime' => 10];

    if (rand(0, 1)) {
        $state['on'] = true;
        $state['bri'] = rand(100, 200);
        $state['ct'] = rand(250, 450);
    } else {
        $state['on'] = false;
    }

    $hue->put("lights/{$id}/state", $state);
}
```

## Security Considerations

The Hue local API runs on HTTP with no encryption. That means every command you send travels in plain text across your network. The security model relies on network isolation, not transport security.

### Do's and Don'ts

**Do** keep your API key out of version control. Use environment variables or a `.env` file loaded by your app.

```bash
# .env
HUE_BRIDGE_IP=192.168.1.100
HUE_API_KEY=your-secret-key-here
```

Add `.env` to your `.gitignore`:

```
.env
```

**Do** restrict access to your dashboard with HTTP basic auth or a reverse proxy. If your dashboard is accessible from a web server, add authentication:

```php
// dashboard.php — simple auth guard
$username = $_ENV['DASHBOARD_USER'] ?? 'admin';
$password = $_ENV['DASHBOARD_PASS'] ?? '';

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== $username || $_SERVER['PHP_AUTH_PW'] !== $password) {
    header('WWW-Authenticate: Basic realm="Hue Dashboard"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authentication required';
    exit;
}
```

**Do** run your dashboard and cron scripts on a separate VLAN if your network gear supports it. Isolate IoT devices from your main computers. The Hue Bridge only needs to talk to your PHP server.

**Don't** expose the Hue Bridge directly to the internet. Never forward port 80 or 443 to the Bridge. If you need remote access, use a VPN (WireGuard or Tailscale) to reach your PHP dashboard, and let the dashboard talk to the Bridge locally.

**Don't** share your API key in logs. If you log requests, sanitize outgoing URLs:

```php
function sanitizeUrl(string $url): string
{
    return preg_replace('/\/api\/[a-f0-9]+\//', '/api/REDACTED/', $url);
}
```

### Key Rotation

If you suspect your API key is compromised, delete it through the official Hue app (Settings → Hue Bridges → your Bridge → Advanced → Delete authorized app), then register a new one using the link-button flow shown earlier.

## Performance Tips

The Hue Bridge handles about 20–30 requests per second before it starts dropping packets. For most home setups this is fine, but batch operations matter.

- **Always use groups** instead of individual bulbs when you want to change multiple lights. One group command replaces N individual commands.
- **Use transitiontime** instead of polling until a light reaches its target state. The Bridge handles the fade internally.
- **Cache light and sensor state** in memory or a file and only update when you know something changed. Polling every second from multiple clients will slow down the Bridge.
- **Reuse your HTTP connection** if making many requests. Keep cURL handles alive or use `stream_context_create` with a persistent connection.

## Going Further

The Hue API exposes more endpoints than what we covered here:

- **`/config`** — read Bridge configuration, update name, check firmware version
- **`/resourcelinks`** — link scenes, groups, and rules together into complex automations
- **`/capabilities`** — introspect what your Bridge supports

If you prefer not to write raw HTTP calls, check out the community package `mtibben/hue-php` on Packagist. It wraps the entire Hue API in PHP objects. But honestly, the API is simple enough that you might not need it — the entire control surface is just PUT, GET, and POST on a small set of URL patterns.

Developing for the Hue API is refreshing compared to cloud-dependent smart home platforms. There's no OAuth dance, no rate limit tiers, no deprecation schedule for a version you relied on. The Bridge is a fixed target on your network that does one thing and does it well. Your PHP code talks to it directly, and it responds within milliseconds.

Whether you're building a wake-up light that simulates dawn, a notification system that flashes your office lights when your CI pipeline fails, or a full home automation dashboard, the combination of PHP and the Hue Bridge is fast, reliable, and fun to build. The Bridge handles the real-time control; PHP handles the logic, scheduling, and web interface. It's a good combination.

Start small — get your API key, turn a single light on and off. Then add groups, scenes, schedules, and sensor reactions. Before you know it, you'll have a smart home that answers to your code.
