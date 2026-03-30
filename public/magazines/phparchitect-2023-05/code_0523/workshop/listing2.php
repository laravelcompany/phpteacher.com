#!/opt/homebrew/bin/php
<?php
if(php_sapi_name() !== 'cli') { exit;}
require __DIR__ . '/vendor/autoload.php';
use Minicli\App;

$app = new App([
    'app_path' => [__DIR__ . '/app/Command'],
    'theme' => '\\Unicorn', 'debug' => false,
]);

$app->registerCommand('demo', function () use ($app) {
    $app->getPrinter()
        ->success('Hello php[architect] :D' , false);
    $app->getPrinter()
        ->info('Info message with background' , true);
    $app->getPrinter()
        ->error('Error Message :(', false);
});

$app->runCommand($argv);