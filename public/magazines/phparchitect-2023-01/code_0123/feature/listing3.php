class ReflectionWrapper
{
  private $object;

  private $reflectedObj;

  public function __construct($object)
  {
    $this->object = $object;
    $this->reflectedObj = new ReflectionClass($object);
  }

  public function get($propertyName)
  {
    $property = $this->reflectedObj
                     ->getProperty($propertyName);

    if (
        $property->isPrivate()
        || $property->isProtected()
    ) {
      $property->setAccessible(true);
      $value = $property->getValue($this->object);
      $property->setAccessible(false);
    } else {
      $value = $property->getValue($this->object);
    }

    return $value;
  }
}