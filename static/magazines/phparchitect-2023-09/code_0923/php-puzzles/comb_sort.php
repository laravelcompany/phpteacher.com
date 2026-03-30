/**
 * We're assuming sequential integer keys
 * @param array<int, scalar> $list
 */
function comb_sort(array &$list): void
{
    $max = count($list);
    $shrinkFactor = 2;
    $gap = ceil($max / $shrinkFactor);
    do {
        echo "\nGap: " . $gap;
        // stop two elements before max so we have two
        // elements to swap
        $swapped = false;
        for ($i = 0; $i < $max - $gap; $i++) {
            $target = $i + $gap;

            if ($list[$i] > $list[$target]) {
                $swapped = true;
                swap_elements($list, $i, $target);
            }
        }
        // reduce the gap again
        $gap = ceil($gap / $shrinkFactor);
    } while ($swapped);
}
