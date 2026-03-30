function shell_sort_ciura(array &$list): void
{
  $data = [1, 4, 10, 23, 57, 132, 301, 701];
  $gaps = array_reverse($data);

  foreach ($gaps as $gap) {
    if ($gap > count($list)) continue;

    $i = $gap;
    while ($i < count($list)) {
      for ($j = $i;
          ($j >= $gap && $list[$j-$gap] > $list[$j]);
          $j -= $gap
      ) {
        swap_elements($list, $j, $j - $gap);
      }
      ++$i;
    }
  }
}