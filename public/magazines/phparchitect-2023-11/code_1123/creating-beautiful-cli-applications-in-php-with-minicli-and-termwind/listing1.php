#!/usr/bin/env php
<?php

if (php_sapi_name() !== 'cli') {
    exit;
}

try {
    echo "Hello, CLI";
    return 0;
} catch (Throwable $exception) {
    echo "An error occurred\n";
    echo $exception->getMessage();
    return 1;
}