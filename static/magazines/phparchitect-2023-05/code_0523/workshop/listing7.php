$test = false;
if ($this->hasFlag('test')) {
    $this->getPrinter()
        ->display("Operating in test mode!");
    $test = true;
}
// Process data and need to update a DB
if (!$test) { # if we're not using test flag
    // update database!
}