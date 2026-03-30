public function readFromIdToLatest($id)
{
  $retVal = NULL;

  $db = new PDO("mysql:host=" . self::HOST . ";dbname="
    . self::DB, self::USER, self::PW);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Get the 'created' date from the given id
  $sql = "SELECT id, created, axis_x, axis_y, axis_z "
    . "FROM accelerometer_data WHERE id > :id "
    . "ORDER BY id";

  try
  {
    $query = $db->prepare($sql);
    $query->bindParam(":id", $id, PDO::PARAM_INT);
    $query->execute();

    $results = $query->fetchAll(
        PDO::FETCH_CLASS,
        "AccelerometerData"
      );

    if (is_array($results) && count($results) >= 1)
    {
      return
        $this->getJsonEncodedDataFromAccelDataObjects($results);
    }
    else
    {
      // Just get the latest reading
      $retVal = $this->readLatest();
    }
  }
  catch(Exception $ex)
  {
    echo "{$ex->getMessage()}<br/>\n";
  }

  return $retVal;
}