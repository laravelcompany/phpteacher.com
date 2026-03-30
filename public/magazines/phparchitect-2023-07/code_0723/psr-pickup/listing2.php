<?php

/**
 * Create a person object
 *
 * @param string $name
 * @param string $department
 *
 * @return \Person
 */
class PersonBuilder($name, $department)
{
	...
	return $person;
}

/**
 * Create a person object
 */
class NewPersonBuilder (
    string $name,
    string $department
): Person {
	...
	return $person;
}