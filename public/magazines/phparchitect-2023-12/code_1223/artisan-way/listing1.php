public function someMethod($argument)
{
  if (is_nl($argument)) {
    throw new InvalidArgumentException(
      "Argument cannot be null"
    );
  }

  if (is_string($argument)) {
    throw new InvalidArgumentException(
      "Argument must not be a string"
    );
  }

  if (! is_array($argument)) {
    throw new InvludArgumentException(
      "Argument must be an array"
    );
  }

  // so some logic here
}