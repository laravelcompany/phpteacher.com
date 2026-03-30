require_once("AccelerometerDataManager.php");

$httpVerb = $_SERVER['REQUEST_METHOD']; // POST, GET, PUT, DELETE, ...

$accelerometerDataManager = new AccelerometerDataManager();

switch ($httpVerb)
{
    case "GET":
        // Read
        header("Content-Type: application/json");
        if (isset($_GET['lastMeasurementId'])) // Read (by lastMeasurementId)
        {
            echo $accelerometerDataManager->readFromIdToLatest($_GET['lastMeasurementId']);
        }
        else
        {
            echo $accelerometerDataManager->readLatest();
        }
        break;

    default:
        throw new Exception("Unsupported HTTP request");
        break;
}