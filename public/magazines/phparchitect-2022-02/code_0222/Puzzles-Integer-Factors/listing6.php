function findAllFactors(int $product) : array {
    // get the positive factors
    $pairs = findFactors(abs($product));
    $negatives = [];

    if ($product > 0) {
        // also add pairs of negative integers
        foreach ($pairs as $pair) {
            $negatives[] = [$pair[0] * -1, $pair[1] * -1];
        }

        return array_merge($pairs, $negatives);
    } elseif ($product < 0) {
        foreach ($pairs as $pair) {
            $negatives[] = [$pair[0] * -1, $pair[1]];
            $negatives[] = [$pair[0], $pair[1] * -1];
        }

        return $negatives;
    }

    return [];
}