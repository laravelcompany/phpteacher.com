<?php

spl_autoload_register(function (string $className) {
    require_once $className . '.php';
});

$httpReader = new HTTPReader();
$jsonData = file_get_contents($_ENV['OFFERS_ENDPOINT']);

$offerCollection = $httpReader->read($jsonData);

$subcommand = $argv[1];

$subcommand2FilterMapping = [
    'count_by_price_range' => function (OfferCollection $collection, array $argv) {
        return count(array_filter($collection->toArray(), function (Offer $offer) use ($argv) {

            return $offer->getPrice() >= $argv[2] && $offer->getPrice() <= $argv[3];
        }));
    },
];

echo $subcommand2FilterMapping[$argv[1]]($offerCollection, $argv);