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
    echo $exception->getMessage();

    return 1;
}