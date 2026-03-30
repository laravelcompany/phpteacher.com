function make_test_list(
    int $size,
    int $min=0,
    int $max=20000
): array {
    $list = array_fill(0, $size, 0);
    array_walk($list,
        function(&$item) use ($min, $max): void {
            $item = random_int($min, $max);
        }
    );
    return $list;
}