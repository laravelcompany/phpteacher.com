$subcommand2FilterMapping = [
    'count_by_price_range' => function (Iterator $productIterator, array $argv) {
        return new PriceFilterIterator($productIterator, $argv[2], $argv[3]);
    },
    'count_by_vendor_id' => function (Iterator $productIterator, array $argv) {
        return new VendorIdFilterIterator($productIterator, intval($argv[2]));
    },
    'count_by_title_prefix' => function (Iterator $productIterator, array $argv) {
        return new ProductTitlePrefixFilterIterator($productIterator, $argv[2]);
    },
];