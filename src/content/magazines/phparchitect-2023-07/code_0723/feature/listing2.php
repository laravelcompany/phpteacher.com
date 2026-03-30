//Instead of this

$result = array();

foreach ($myArray as $item) {
    $result[] = $item * 2;
}

//do this

$result = array_map(function($item) { 
    return $item * 2; 
}, $myArray);