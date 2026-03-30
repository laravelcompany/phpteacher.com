$jsonArray = json_decode($response, true);
foreach ($jsonArray['coffees'] ?? [] as $coffee) {
    $coffeeCity = $coffee['city'];
    // validate the $coffeeCity here
}

$coffeeGroups = $jsonArray['coffee-groups'] ?? [];
foreach ($coffeeGroups as $groups) {
    foreach ($groups as $groupName => $coffees) {
        foreach ($coffees as $coffees) {
            $coffeeCity = $coffee['city'];
            // validate the $coffeeCity here
        }
    }
}