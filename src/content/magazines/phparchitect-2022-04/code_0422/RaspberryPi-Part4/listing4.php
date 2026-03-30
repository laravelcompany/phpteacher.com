require_once('AccelerometerData.php');

class AccelerometerDataManager
{
    const HOST = "localhost";
    const DB = "AccelerometerData";
    const USER = "accelerometer";
    const PW = "accelerometer";

    public function readLatest()
    { ... }

    public function readFromIdToLatest($id)
    { ... }

    private function
    getJsonEncodedDataFromAccelDataObjects(
        $accelDataObjects
    )
    { ... }
}