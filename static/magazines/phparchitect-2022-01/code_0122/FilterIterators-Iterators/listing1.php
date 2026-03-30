<?php

spl_autoload_register(function (string $className) {
    require_once $className . '.php';
});

$httpReader = new HTTPReader();
$jsonData = file_get_contents($_ENV['OFFERS_ENDPOINT']);

$offerCollection = $httpReader->read($jsonData);

$subcommand = $argv[1];

$productIterator = $offerCollection->getIterator();
$subcommand2FilterMapping = [
    'count_by_price_range' => function (Iterator $productIterator, array $argv) {
        return new PriceFilterIterator($productIterator, $argv[2], $argv[3]);
    },
    'count_by_vendor_id' => function(Iterator $productIterator, array $argv) {
        return new VendorIdFilterIterator($productIterator, intval($argv[2]));
    },
];

// Please note that validation is left out to simplify the example.

echo iterator_count($subcommand2FilterMapping[$argv[1]]($productIterator, $argv));