public function readLatest()
{
  $retVal = NULL;

  $db = new PDO("mysql:host=" . self::HOST . ";dbname="
      . self::DB, self::USER, self::PW);
  $db->setAttribute(PDO::ATTR_ERRMODE,
      PDO::ERRMODE_EXCEPTION);

  // Get the latest measurement
  $sql = "SELECT id, created, axis_x, axis_y, axis_z "
      . "FROM accelerometer_data "
      . "ORDER BY id DESC LIMIT 1";

  try
  {
    $query = $db->prepare($sql);
    $query->execute();

    $results = $query->fetchAll(PDO::FETCH_CLASS,
      "AccelerometerData");

    if (is_array($results) && count($results) == 1)
    {
      return $this->
        getJsonEncodedDataFromAccelDataObjects($results);
    }
    else
    {
      $retVal = 0;
      $retVal = json_encode($retVal, JSON_PRETTY_PRINT);
    }
  }
  catch(Exception $ex)
  {
    echo "{$ex->getMessage()}<br/>\n";
  }

  return $retVal;
}