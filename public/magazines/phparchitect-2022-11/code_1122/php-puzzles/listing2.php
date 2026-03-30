// create the groups
$groups = $done = [];
foreach(range('A','H') as $label) {
    $groups[] = new Group($label, 4);
}

// randomize the teams
shuffle($all);

// start with assigning teams to group 4
$target = array_shift($groups);
$x = 0;
while ($all && $x < 60) {
    // grab a team to assign from the top of the stack of teams
    $candidate = array_pop($all);
    if ($target->addTeam($candidate)) {
        if ($target->isFull()) {
            // this group is done, move to the next
            $done[] = $target;
            // and grab the next group
            $target = array_shift($groups);
        }
    } else {
        // stick it at the bottom of our stack to try again later
        array_unshift($all, $candidate);
    }

    $x++;
    if ($x == 60) {
        // avoid infinite loops
        foreach ($all as $team) {
            $target->forceAddTeam($team);
        }
        $done[] = $target;
    }
}