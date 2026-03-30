class AccelerometerData
{
    private $id;
    private $created;
    private $axis_x;
    private $axis_y;
    private $axis_z;

    // Get/Set
    public function __get($ivar)
    {
        return $this->$ivar;
    }

    public function __set($ivar, $value)
    {
        $this->$ivar = $value;
    }

    // Serialize
    public function __toString()
    {
        $format =
            "<hr/>Id: %s<br/>Created: "
            . "%s<br/>X: %s<br/>Y: "
            . "%s<br/>Z: %s<hr/>";

        return sprintf(
            $format,
            $this->__get('id'),
            $this->__get('created'),
            $this->__get('axis_x'),
            $this->__get('axis_y'),
            $this->__get('axis_z')
        );
    }
}