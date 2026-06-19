---
title: "Build Beautiful PHP CLI Apps with MiniCLI and Termwind"
description: "Learn to craft modern, beautiful command-line PHP applications using MiniCLI and Termwind. Step-by-step guide with hands-on code examples for CLImage image tool."
pubDate: "2023-11-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP CLI", "MiniCLI", "Termwind", "Command Line", "PHP 8.x", "CLI Applications", "PHP Development", "Console Tools"]
selected: false
---

Most PHP developers spend their days building web applications, but the language is equally powerful for crafting command-line tools. Whether you need automation scripts, deployment utilities, or internal developer tools, PHP provides everything you need to build robust CLI applications.

However, traditional PHP CLI scripts often suffer from poor user experience. Plain text output, no interactive prompts, and zero visual appeal. Users expect more from their terminal tools today, and with modern PHP libraries like MiniCLI and Termwind, you can deliver beautiful, interactive command-line experiences with minimal effort.

## What You'll Learn

- How to create PHP CLI executables with proper shebang configuration
- Building interactive CLI applications with MiniCLI's micro-framework
- Styling terminal output using Termwind's Tailwind-inspired syntax
- Creating image processing CLI tools with Imagick integration
- Implementing input validation and custom exceptions in CLI apps
- Structuring reusable commands with MiniCLI's file-based routing

## Getting Started with PHP CLI Scripts

Before diving into full application development, let's understand the fundamentals of PHP CLI scripting. A basic PHP CLI script is surprisingly simple.

### Your First CLI Script

Create a file called `greet` (no extension) in your project directory:

```php
#!/usr/bin/env php
<?php

if (php_sapi_name() !== 'cli') {
    exit;
}

try {
    echo "Hello, CLI\n";
    return 0;
} catch (Throwable $exception) {
    echo "An error occurred\n";
    echo $exception->getMessage() . "\n";
    return 1;
}
```

The `#!/usr/bin/env php` shebang tells the operating system to use PHP when executing this file. The `php_sapi_name()` check prevents accidental web access. After making the file executable with `chmod +x greet`, you can run it directly with `./greet`.

### Accepting User Input

Make your scripts interactive using the `readline` function:

```php
#!/usr/bin/env php
<?php

if (php_sapi_name() !== 'cli') {
    exit;
}

try {
    $name = readline("What's your name?\n> ");
    echo "Hello, {$name}\n";
    return 0;
} catch (Throwable $exception) {
    echo "An error occurred\n";
    echo $exception->getMessage() . "\n";
    return 1;
}
```

The `readline` function accepts a string prompt and returns the user's input. Always validate user input, as `readline` returns `false` when the user hits enter without providing a value.

## PHP Libraries for CLI Applications

Several excellent libraries exist for building PHP CLI applications, each with different strengths.

### Symfony Console

The Symfony Console component is the most established PHP CLI framework. It provides a robust foundation for defining commands, handling parameters, and testing. Many other CLI libraries, including Laravel's Artisan, build on top of it. It's ideal for complex applications with many commands but can feel heavy for simpler tools.

### Laravel Zero

Laravel Zero is a streamlined version of Laravel designed specifically for CLI applications. It brings Laravel's elegant syntax, Eloquent ORM, task scheduling, and PHAR compilation to the command line. If you're already comfortable with Laravel, this is a natural choice.

### Laravel Prompts

Laravel Prompts adds rich interactive input features to CLI applications. It provides select menus, checkboxes, progress bars, and validation — functionality typically reserved for web applications — all in the terminal.

### MiniCLI

MiniCLI is a lightweight micro-framework with zero dependencies. It uses a file-based routing convention where directories and filenames determine command names. This minimal approach makes it perfect for small to medium CLI tools where you want control without overhead.

### Termwind

Termwind brings Tailwind CSS styling to the terminal. Instead of using ANSI escape codes directly, you write HTML with Tailwind-like classes and Termwind renders it in your console. This makes styled terminal output as natural as building a web interface.

## Building CLImage: A Practical CLI Application

Let's build a real application: CLImage, a helper tool for resizing and converting images. We'll use MiniCLI with Termwind for styling.

### Project Setup

Start by creating a new project using the MiniTerm template, which pre-configures MiniCLI with Termwind and the Plates template engine:

```bash
composer create-project minicli/miniterm CLImage && cd CLImage
```

Rename the executable and configure your application name:

```bash
mv ./minicli CLImage
```

Update `config/app.php`:

```php
<?php

declare(strict_types=1);

return [
    'app_name' => 'CLImage - Helper Tool for Images',
    'app_path' => [
        __DIR__ . '/../app/Command',
        '@minicli/command-help'
    ],
    'theme' => '',
    'debug' => true,
    'output_path' => __DIR__ . '/../output',
];
```

### Understanding MiniCLI Commands

MiniCLI uses a file-based routing convention. A command named `demo` corresponds to the `app/Command/Demo` directory. Running `./CLImage demo` executes `app/Command/Demo/DefaultController.php`. Sub-commands like `demo test` map to `app/Command/Demo/TestController.php`.

Here's how a basic controller looks:

```php
<?php

declare(strict_types=1);

namespace App\Command\Demo;

use App\Command\BaseController;

class DefaultController extends BaseController
{
    public function handle(): void
    {
        $this->render(<<<HTML
        <div class="py-2">
            <div class="px-1 bg-cyan-600">INFO</div>
            <span class="ml-1">
                Run <span class="font-bold italic">./minicli help</span> for usage help.
            </span>
        </div>
        HTML);
    }
}
```

The `render` method accepts HTML with Termwind CSS classes. This is the same familiar syntax as Tailwind CSS, but rendered in your terminal.

### Handling Command Parameters

MiniCLI provides `hasParam` and `getParam` methods for parsing command-line arguments:

```php
<?php

declare(strict_types=1);

namespace App\Command\Demo;

use App\Command\BaseController;

class TestController extends BaseController
{
    public function handle(): void
    {
        $name = $this->hasParam('user')
            ? $this->getParam('user')
            : 'World';

        $this->render(<<<HTML
        <div class="py-2">
            <div class="px-1 bg-green-600">MiniTerm</div>
            <em class="ml-1">Hello, {$name}</em>
        </div>
        HTML);
    }
}
```

Run it with `./CLImage demo test user=YourName`.

### Creating the Image Service

CLImage processes images using the Imagick PHP extension. Create the service skeleton:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Minicli\App;
use Minicli\ServiceInterface;

final class ImageService implements ServiceInterface
{
    public function load(App $app): void
    {
        // Nothing to initialize
    }
}
```

Register it in `config/services.php`:

```php
<?php

declare(strict_types=1);

use App\Services\ImageService;
use App\Services\PlatesService;
use App\Services\TermwindService;

return [
    'services' => [
        'termwind' => TermwindService::class,
        'plates' => PlatesService::class,
        'image' => ImageService::class,
    ],
];
```

Now extend the service with image processing methods:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ImageFormat;
use App\Support\ImageInfo;
use Imagick;
use ImagickException;
use Minicli\App;
use Minicli\ServiceInterface;

final class ImageService implements ServiceInterface
{
    public function info(string $imagePath): ImageInfo
    {
        $image = $this->read($imagePath);

        return new ImageInfo(
            filename: basename($imagePath),
            width: $image->getImageWidth(),
            height: $image->getImageHeight(),
        );
    }

    public function resize(
        string $imagePath,
        int $width,
        int $height,
        string $outputPath
    ): ImageInfo {
        $image = $this->read($imagePath);

        $image->resizeImage(
            columns: $width,
            rows: $height,
            filter: Imagick::FILTER_UNDEFINED,
            blur: 1,
            bestfit: true
        );

        $filename = basename($imagePath);
        $output = "{$outputPath}/{$filename}";
        $image->writeImage($output);

        return new ImageInfo(
            filename: realpath($output),
            width: $width,
            height: $height,
        );
    }

    public function convert(
        string $imagePath,
        ImageFormat $format,
        string $outputPath
    ): ImageInfo {
        $image = $this->read($imagePath);
        $image->setImageFormat($format->value);

        $filename = pathinfo($imagePath, PATHINFO_FILENAME)
            . ".{$format->value}";
        $output = "{$outputPath}/{$filename}";
        $image->writeImage($output);

        return new ImageInfo(
            filename: realpath($output),
            width: $image->getImageWidth(),
            height: $image->getImageHeight(),
        );
    }

    private function read(string $imagePath): Imagick
    {
        $image = new Imagick();
        $image->readImage($imagePath);

        return $image;
    }

    public function load(App $app): void
    {
        // Nothing to do
    }
}
```

### Creating Supporting Types

Use a PHP 8.1 enum to define supported image formats:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum ImageFormat: string
{
    case JPEG = 'jpeg';
    case PNG = 'png';

    public static function allowedFormats(): array
    {
        return array_map(
            fn($item) => $item->value,
            self::cases()
        );
    }
}
```

And a readonly DTO (using PHP 8.2's readonly class feature) for image information:

```php
<?php

declare(strict_types=1);

namespace App\Support;

final readonly class ImageInfo
{
    public function __construct(
        public string $filename,
        public int $width,
        public int $height,
    ) {}
}
```

### Building the Resize Command

Create the command structure:

```bash
mkdir -p app/Command/Resize
```

```php
<?php

declare(strict_types=1);

namespace App\Command\Resize;

use App\Command\BaseController;
use App\Exceptions\FileNotFoundException;
use App\Exceptions\InvalidDimensionException;

final class DefaultController extends BaseController
{
    public function handle(): void
    {
        $question = 'Which image do you want to resize?';
        $imagePath = $this->ask($this->buildQuestion($question));

        if (!realpath($imagePath)) {
            throw new FileNotFoundException();
        }

        $imageInfo = $this->app->image->info($imagePath);
        $this->printImageInfo($imageInfo);

        $newWidth = $this->ask(
            $this->buildQuestion(
                "What is the new WIDTH? [10..{$imageInfo->width}]"
            )
        );

        if ($newWidth < 10 || $newWidth > $imageInfo->width) {
            throw new InvalidDimensionException($imageInfo->width);
        }

        $newHeight = $this->ask(
            $this->buildQuestion(
                "What is the new HEIGHT? [10..{$imageInfo->height}]"
            )
        );

        if ($newHeight < 10 || $newHeight > $imageInfo->height) {
            throw new InvalidDimensionException($imageInfo->height);
        }

        $result = $this->app->image->resize(
            $imagePath,
            (int) $newWidth,
            (int) $newHeight,
            $this->app->config->output_path
        );

        $this->successMessage('Resized successfully! Check the details below:');
        $this->printImageInfo($result);
    }
}
```

### Custom Exceptions

MiniCLI uses custom exceptions for error handling. Create meaningful exceptions:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class FileNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('File does not exist!');
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class InvalidDimensionException extends Exception
{
    public function __construct(int $max)
    {
        parent::__construct(
            "The value must be between 10 and {$max}"
        );
    }
}
```

### Building the Convert Command

```bash
mkdir -p app/Command/Convert
```

```php
<?php

declare(strict_types=1);

namespace App\Command\Convert;

use App\Command\BaseController;
use App\Enums\ImageFormat;
use App\Exceptions\FileNotFoundException;
use App\Exceptions\InvalidFormatException;

final class DefaultController extends BaseController
{
    public function handle(): void
    {
        $imagePath = $this->ask(
            $this->buildQuestion('Which image do you want to convert?')
        );

        if (!realpath($imagePath)) {
            throw new FileNotFoundException();
        }

        $imageInfo = $this->app->image->info($imagePath);
        $this->printImageInfo($imageInfo);

        $allowedFormats = ImageFormat::allowedFormats();
        $newFormat = $this->ask(
            $this->buildQuestion(
                'What is the new FORMAT? [' . implode(', ', $allowedFormats) . ']'
            )
        );

        if (!in_array($newFormat, $allowedFormats)) {
            throw new InvalidFormatException();
        }

        $result = $this->app->image->convert(
            $imagePath,
            ImageFormat::from($newFormat),
            $this->app->config->output_path
        );

        $this->successMessage('Success! Check the details below:');
        $this->printImageInfo($result);
    }
}
```

### Extending the Base Controller

Add reusable helper methods to the base controller:

```php
<?php

declare(strict_types=1);

namespace App\Command;

use App\Support\ImageInfo;
use Minicli\Command\CommandController;

abstract class BaseController extends CommandController
{
    protected function buildQuestion(string $question): string
    {
        return <<<HTML
        <div class="py-2">
            <div class="px-1 bg-purple-600">CLImage</div>
            <em class="ml-1">{$question}</em>
        </div>
        HTML;
    }

    protected function successMessage(string $message): void
    {
        $this->render(<<<HTML
        <div class="py-2">
            <div class="px-1 bg-green-600">SUCCESS</div>
            <em class="ml-1">{$message}</em>
        </div>
        HTML);
    }

    protected function printImageInfo(ImageInfo $imageInfo): void
    {
        $this->view('table', [
            'headers' => ['FILENAME', 'WIDTH', 'HEIGHT'],
            'rows' => [
                [
                    $imageInfo->filename,
                    "{$imageInfo->width} px",
                    "{$imageInfo->height} px"
                ]
            ],
        ]);
    }
}
```

## Real-World Use Cases

- **DevOps tooling**: Build deployment scripts, log analyzers, and health check tools with styled output
- **Data processing pipelines**: Create ETL tools that process CSV files, transform data, and show progress
- **SaaS administration**: Build CLI management tools for multi-tenant applications
- **CI/CD integration**: Develop local development utilities that integrate with build systems
- **Content management**: Script batch image processing, file organization, and asset optimization tools

## Best Practices

- **Always check the SAPI**: Use `php_sapi_name() !== 'cli'` to prevent web access to CLI scripts
- **Use exit codes**: Return 0 for success, 1 for errors so scripts compose well in pipelines
- **Validate input thoroughly**: CLI input is user input and must be validated just like web forms
- **Leverage custom exceptions**: Create meaningful exception classes for domain-specific errors
- **Structure with services**: Keep business logic in services, not in command controllers

## Common Mistakes to Avoid

- **Hard-coding paths**: Always use configuration files or environment variables for paths
- **Ignoring error handling**: CLI scripts without try/catch blocks fail silently and confuse users
- **Missing shebang lines**: Forgetting `#!/usr/bin/env php` means scripts can't execute directly
- **Over-engineering simple tasks**: Not every CLI tool needs a full framework; match complexity to need
- **Poor user feedback**: Silent commands frustrate users; always show progress and results

## Frequently Asked Questions

**Can I use MiniCLI without Composer?**
Yes, but it's designed for Composer-based projects. You can install it manually, but dependency management is smoother with Composer.

**Does Termwind support all Tailwind CSS classes?**
No, Termwind supports a subset of Tailwind classes relevant to terminal output, including colors, spacing, typography, and borders.

**Can I use MiniCLI with Symfony Console commands?**
MiniCLI is a standalone micro-framework. For Symfony Console integration, use Symfony Console directly instead.

**How do I distribute CLI PHP applications to users?**
Package them as PHAR archives using tools like Box or use the PHAR compilation feature in Laravel Zero.

**Does CLImage work without Imagick?**
Imagick is required for image processing. Alternatives include GD, but Imagick provides better quality resizing and format conversion.

**Can I create interactive menus with Termwind?**
Yes, Termwind supports ask and select prompts for interactive input collection.

**Is MiniCLI suitable for production use?**
Absolutely. It's stable, well-documented, and used in production environments for various CLI tools.

**How do I test MiniCLI commands?**
MiniCLI commands extend base controllers that can be instantiated and tested with PHPUnit. Mock the app container for isolated testing.

## Conclusion

PHP is a powerful language for CLI application development, and with modern libraries like MiniCLI and Termwind, you can create tools that rival anything built in other languages. The combination of a lightweight micro-framework with beautiful terminal styling lets you focus on solving problems rather than wrestling with ANSI escape codes.

The CLImage application demonstrates the core patterns: file-based command routing, service containers for business logic, Termwind for styled output, and custom exceptions for error handling. These patterns scale from simple scripts to complex multi-command tools.

Start building your CLI tools today. The terminal is your canvas, and PHP is your brush. Create something beautiful.

**[Try MiniCLI on GitHub](https://github.com/minicli/minicli) | [Explore Termwind Documentation](https://github.com/nunomaduro/termwind)**
