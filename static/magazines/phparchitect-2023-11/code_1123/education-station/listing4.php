<?php

class Person {
  private $name; // Emulating Java's private behavior

  // A public constructor, akin to Java's public methods
  public function __construct(private $name) {
  }

  // A public method, reinforcing Java's approach
  public function printName() {
    echo "Name of the person is: " . $this->name . "\\n";
  }

  // A protected method that can be used in child classes
  protected function getFirstName() {
    return explode(' ', $this->name);
  }
}

$per1 = new Person("John");
$per2 = new Person("Alice");

$per1->printName();
$per2->printName();