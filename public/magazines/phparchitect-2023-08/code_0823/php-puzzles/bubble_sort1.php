function bubble_sort(array &$list): void
{
    $max = count($list);
    do {
        // stop two elements before max so we have two
        // elements to swap
        $swapped = false;
        for ($i = 0; $i < $max -1; $i++) {
            if ($list[$i] > $list[$i + 1]) {
                $swapped = true;
                swap_elements($list, $i, $i + 1);
            }
        }
    } while ($swapped);
}