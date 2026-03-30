$runtimes = [];
$runs = 100;
$size = 100;
for ($i = 0; $i < $runs; $i++) {
    $list = make_test_list($size);
    $start = hrtime(true);
    bubble_sort($list);
    $end = hrtime(true);
    $runtimes[] = ($end - $start) / 1000000000;
    unset($list);
}
echo "\n\nFastest: " . min($runtimes) . "\n";
echo "Slowest: " . max($runtimes) . "\n";
echo "Mean:    ".(array_sum($runtimes) / $runs)."\n";