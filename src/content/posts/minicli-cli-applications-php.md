---
title: "Minicli: Build CLI PHP Applications the Minimalist Way"
description: "Build command-line PHP applications with Minicli, a minimalist framework for CLI apps. Learn shebang scripts, command registration, and printer helpers."
pubDate: "2023-05-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Minicli", "CLI", "PHP", "Console Applications", "Command Line", "PHP 8.2", "Shebang Scripts", "Minimalist Framework"]
---

Console applications are a staple of PHP development. Whether you are writing cron jobs, deployment scripts, data importers, or development tooling, the command line is often the most natural interface. Symfony Console and Laravel Artisan dominate this space, but they come with significant overhead for simple use cases.

## What You'll Learn

- Setting up a Minicli project from scratch
- Creating executable PHP scripts with shebang lines
- Registering commands and handling arguments
- Using Minicli's printer for colored output
- Building interactive CLI applications with readline
- Structuring larger CLI applications with command controllers
- Creating multi-command tools with namespaces
- Best practices for CLI application error handling
- Real-world use cases for minimalist CLI tools

## Getting Started with Minicli

Installation is straightforward with Composer.

```bash
mkdir demo && cd demo
touch example
chmod +x example
composer require minicli/minicli
```

The `example` file is our CLI entry point. Making it executable lets us run `./example` directly.

```php
#!/opt/homebrew/bin/php
<?php

if (php_sapi_name() !== 'cli') {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use Minicli\App;

$app = new App([
    'app_path' => __DIR__ . '/app/Command',
    'theme' => '\\Unicorn',
    'debug' => false,
]);

$app->registerCommand('demo', function () use ($app) {
    $app->getPrinter()->success('Hello php[architect] :D');
    $app->getPrinter()->info('Info message with background');
    $app->getPrinter()->error('Error Message :(');
});

$app->runCommand($argv);
```

The shebang line points to your PHP binary. Use `which php` to find your path. Common locations are `/usr/bin/php`, `/usr/local/bin/php`, or Homebrew's `/opt/homebrew/bin/php`.

Running `./example demo` produces color-coded output. Success messages print to stdout, errors to stderr, and info messages highlight with background color.

## Understanding Commands

Minicli maps command names to closures or controller classes. The first argument after the script name is the command.

```php
$app->registerCommand('hello', function () use ($app) {
    $app->getPrinter()->display('Hello, World!');
});
```

Run with `./example hello`.

### Command Parameters

Arguments after the command name are available as parameters.

```php
$app->registerCommand('greet', function () use ($app) {
    $params = $app->getParameters();
    $name = $params[0] ?? 'World';
    $app->getPrinter()->display("Hello, {$name}!");
});
```

Call with `./example greet Alice` to get "Hello, Alice!".

## Building a CLI Application Controller

For larger applications, use controller classes instead of closures.

```php
<?php

namespace App\Command\Demo;

use Minicli\Command\CommandController;

class TpsController extends CommandController
{
    public function handle(): void
    {
        $this->getPrinter()->info('TPS Report Generator');
        
        $params = $this->getParams();
        $reportTitle = $params['report'] ?? 'Untitled';
        $department = $params['dept'] ?? 'General';
        
        $this->getPrinter()->display(
            "Generating: {$reportTitle} for {$department}"
        );
    }
}
```

Place this in `app/Command/Demo/TpsController.php`. Minicli's autoloading maps the `Demo` directory to the `demo` command namespace.

Call with `./example demo tps report="Q3 Summary" dept=Engineering`.

Named parameters use the `=` syntax. This makes your CLI commands self-documenting.

## Creating a Multi-Command Tool

Real CLI tools have multiple commands. Let's build a todo list manager.

```php
<?php

namespace App\Command\Todo;

use Minicli\Command\CommandController;

class ListController extends CommandController
{
    public function handle(): void
    {
        $todos = $this->loadTodos();
        
        if (empty($todos)) {
            $this->getPrinter()->info('No todos found.');
            return;
        }
        
        foreach ($todos as $index => $todo) {
            $status = $todo['done'] ? '✓' : '○';
            $this->getPrinter()->display(
                "[{$status}] {$index}: {$todo['task']}"
            );
        }
    }

    private function loadTodos(): array
    {
        $file = __DIR__ . '/../../../todos.json';
        if (!file_exists($file)) {
            return [];
        }
        return json_decode(file_get_contents($file), true) ?? [];
    }
}
```

```php
<?php

namespace App\Command\Todo;

use Minicli\Command\CommandController;

class AddController extends CommandController
{
    public function handle(): void
    {
        $params = $this->getParams();
        $task = $params['task'] ?? null;
        
        if (!$task) {
            $this->getPrinter()->error('Usage: todo add task="Buy milk"');
            return;
        }
        
        $todos = $this->loadTodos();
        $todos[] = ['task' => $task, 'done' => false];
        $this->saveTodos($todos);
        
        $this->getPrinter()->success('Todo added!');
    }

    private function loadTodos(): array
    {
        $file = __DIR__ . '/../../../todos.json';
        if (!file_exists($file)) {
            return [];
        }
        return json_decode(file_get_contents($file), true) ?? [];
    }

    private function saveTodos(array $todos): void
    {
        file_put_contents(
            __DIR__ . '/../../../todos.json',
            json_encode($todos, JSON_PRETTY_PRINT)
        );
    }
}
```

Register in your entry point:

```php
$app->registerCommand('todo', null); // Enables subcommands
```

Usage:
```
./example todo list
./example todo add task="Fix login bug"
```

## Interactive Input with Readline

Minicli supports interactive input through PHP's `readline` extension.

```php
$app->registerCommand('interactive', function () use ($app) {
    $name = readline('Enter your name: ');
    $app->getPrinter()->display("Hello, {$name}!");
});
```

For more complex interactions:

```php
$app->registerCommand('setup', function () use ($app) {
    $printer = $app->getPrinter();
    
    $printer->info('Application Setup Wizard');
    
    $appName = readline('Application name: ');
    $dbHost = readline('Database host [localhost]: ') ?: 'localhost';
    $dbName = readline('Database name: ');
    
    // Generate config
    $config = [
        'app' => ['name' => $appName],
        'database' => ['host' => $dbHost, 'name' => $dbName],
    ];
    
    file_put_contents(
        'config.json',
        json_encode($config, JSON_PRETTY_PRINT)
    );
    
    $printer->success('Configuration saved!');
});
```

## File System Operations CLI

Build a practical file watcher or batch processor.

```php
$app->registerCommand('find-large', function () use ($app) {
    $printer = $app->getPrinter();
    $params = $app->getParameters();
    
    $dir = $params[0] ?? '.';
    $minSize = ($params[1] ?? 10) * 1024 * 1024; // Default 10MB
    
    $printer->info("Scanning {$dir} for files larger than {$minSize} bytes...");
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getSize() > $minSize) {
            $printer->display(sprintf(
                "%s: %.2f MB",
                $file->getPathname(),
                $file->getSize() / 1024 / 1024
            ));
        }
    }
});
```

## Using Configuration in Commands

Real applications need configuration. Minicli's constructor accepts configuration values that commands can access.

```php
<?php

$config = [
    'app_path' => __DIR__ . '/app/Command',
    'theme' => '\\Unicorn',
    'debug' => false,
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'myapp',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
];

$app = new App($config);

$app->registerCommand('db-status', function () use ($app) {
    $config = $app->config;
    $printer = $app->getPrinter();

    try {
        $pdo = new PDO(
            "mysql:host={$config['database']['host']};dbname={$config['database']['name']}",
            $config['database']['user'],
            $config['database']['pass'],
            [PDO::ATTR_TIMEOUT => 3],
        );
        $printer->success('Database connection OK');
    } catch (\Exception $e) {
        $printer->error('Database connection failed: ' . $e->getMessage());
    }
});
```

## Error Handling in CLI Applications

Robust CLI applications handle errors gracefully. Minicli's output methods write errors to stderr automatically, but you should also implement proper error handling.

```php
<?php

$app->registerCommand('process', function () use ($app) {
    $printer = $app->getPrinter();
    $params = $app->getParameters();
    $file = $params[0] ?? null;

    if (!$file) {
        $printer->error('Usage: process <filename>');
        exit(1);
    }

    if (!file_exists($file)) {
        $printer->error("File not found: {$file}");
        exit(1);
    }

    try {
        $result = processFile($file);
        $printer->success("Processed {$file}: {$result} rows");
    } catch (\Exception $e) {
        $printer->error("Error: {$e->getMessage()}");
        exit(2);
    }
});
```

### Global Error Handler for CLI Applications

Set up a global exception handler to catch unhandled errors and display them cleanly:

```php
<?php

set_exception_handler(function (\Throwable $e) {
    fwrite(STDERR, "Fatal error: " . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL);
    exit(255);
});
```

Place this at the top of your entry script, after the shebang and before requiring autoload. It ensures that any uncaught exception produces a clean error message instead of a PHP stack trace dump.

Using exit codes is important for scripting. Exit code 0 means success. Non-zero codes indicate different error types. CI/CD pipelines and shell scripts use these codes to determine whether a command succeeded.

## Real-World Use Cases

### Deployment Scripts

Run database migrations, clear caches, and restart services with a single command. Minicli's command structure keeps deployment logic organized.

### Data Import Tools

Import CSV files, process API responses, and transform data between formats. Interactive prompts configure the import parameters.

### Development Scaffolding

Generate boilerplate code, create migration files, and scaffold new project structures. Controllers keep each generation command separate.

### Cron Job Management

Schedule and run maintenance tasks. Minicli scripts are lightweight enough for frequent cron execution without overhead.

### DevOps Tooling

Build internal tools for server management, log analysis, and system monitoring. Minicli's small footprint means fast startup times, making it ideal for tools that run frequently.

### Log File Analysis

Parse application logs directly from the command line. Minicli scripts can extract error counts, filter by severity level, and generate summary reports:

```php
$app->registerCommand('log-summary', function () use ($app) {
    $printer = $app->getPrinter();
    $params = $app->getParameters();
    $logFile = $params[0] ?? 'app.log';

    if (!file_exists($logFile)) {
        $printer->error("Log file not found: {$logFile}");
        exit(1);
    }

    $counts = [];
    foreach (file($logFile) as $line) {
        if (preg_match('/\[(\w+)\]/', $line, $matches)) {
            $level = $matches[1];
            $counts[$level] = ($counts[$level] ?? 0) + 1;
        }
    }

    $printer->info("Log summary for {$logFile}");
    foreach ($counts as $level => $count) {
        $printer->display("  {$level}: {$count}");
    }
});
```

## Best Practices

- **Always check SAPI** - Verify `php_sapi_name() === 'cli'` to prevent web access to console scripts.
- **Use shebang lines** - Executable scripts with shebangs are more convenient than `php script.php`.
- **Separate commands into controllers** - For more than a few commands, use controller classes in the `app/Command` directory.
- **Provide clear usage messages** - Add help output for every command.
- **Handle edge cases** - Empty input, missing files, and permission errors should produce user-friendly messages.
- **Use exit codes** - Return 0 for success, non-zero for errors.

## Common Mistakes to Avoid

- **Forgetting the shebang** - Without it, executing `./script` fails with a permission error or wrong interpreter.
- **Hardcoding PHP paths** - Use `which php` or assume common locations. Document the requirement.
- **Mixing stdout and stderr** - Minicli's printer handles this correctly. Use `error()` for error messages, not `display()`.
- **Ignoring `ext-readline`** - Interactive commands fail silently without this extension.
- **Over-engineering** - Minicli is for simple CLI tools. If you need Symfony Console's features, use it instead.

## Frequently Asked Questions

### How is Minicli different from Symfony Console?

Minicli is minimalist. It has no dependency injection, no event dispatcher, and no formatter. It provides commands, printing, and parameter parsing. Symfony Console is a full-featured framework for complex CLI applications.

### Can I use Minicli with Laravel or Symfony?

Yes. You can build Minicli scripts that bootstrap Laravel or Symfony to access your application's services. This is useful for custom Artisan commands or Symfony Console replacements.

### Does Minicli support command autocomplete?

Not natively. You can implement autocomplete by generating shell completion scripts from your registered commands.

### How do I test Minicli commands?

Write PHPUnit tests that instantiate your controllers and call `handle()`. Mock the printer to assert output.

### Can Minicli handle long-running processes?

Minicli is designed for short-lived CLI commands. For long-running daemons or workers, use a dedicated solution like ReactPHP or Amp.

### Does Minicli support progress bars?

Not built-in. You can implement progress display using `\r` (carriage return) to overwrite the current line.

## Conclusion

Minicli proves that CLI tools do not need to be complex. With a few lines of PHP and Composer, you have a functional command-line application with colored output, parameter parsing, and command organization.

For simple scripts, cron jobs, and internal tooling, Minicli hits the sweet spot between raw PHP scripts and full-featured CLI frameworks. It gives you enough structure to stay organized without the overhead of Symfony Console or Laravel Artisan.

Install it today with `composer require minicli/minicli` and start building your next CLI tool.
