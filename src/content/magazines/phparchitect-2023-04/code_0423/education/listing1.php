<?php

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('my-app');
// Log only anything higher than a Warning level

$stream = new StreamHandler(
    'php://stderr',
    Level::Warning
);

$log->pushHandler($stream);