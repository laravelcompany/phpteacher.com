#!/usr/bin/php
<?php
if(php_sapi_name() !== 'cli') { exit;}
require __DIR__ . '/vendor/autoload.php';
use Minicli\App;
use Minicli\Exception\CommandNotFoundException;

$app = new App([
    'app_path' => [__DIR__ . '/app/Command'],
    'theme' => '\\Unicorn', 'debug' => false,
]);

$app = new App([
    'app_path' => [
        __DIR__ . '/app/Command'
    ],
    'theme' => '\\Unicorn',
    'debug' => true
]);

try {
    $app->runCommand($argv);
} catch (CommandNotFoundException $exception) {
    $app->getPrinter()->error("Command Not Found.");
    return 1;
} catch (Exception $exception) {
    if ($app->config->debug) {
        $printer = $app->getPrinter();
        $printer->error("An error occurred:");
        $printer->error($exception->getMessage());
    }
    return 1;
}

return 0;