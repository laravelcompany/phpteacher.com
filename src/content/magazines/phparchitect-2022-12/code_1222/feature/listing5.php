if (! isset($item['city'])) {
    return;
}

$city = $item['city'];
if (! is_string($city)) {
    return;
}

// here we know we're dealing with city
validate_city_name($city);