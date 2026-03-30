if (! isset($item['city'])) {
    return;
}

$city = $item['city'];
if (! is_string($city)) {
    return;
}

validate_city_name($city);