function swap_elements(
    array &$list,
    int $i,
    int $j
): void {
    // safety check
    if (
        array_key_exists($i, $list) &&
        array_key_exists($j, $list)
    ) {
        $tmp = $list[$i];
        $list[$i] = $list[$j];
        $list[$j] = $tmp;
        return;
    }
    $message = "Invalid index specified";
    throw new \InvalidArgumentException($message);
}