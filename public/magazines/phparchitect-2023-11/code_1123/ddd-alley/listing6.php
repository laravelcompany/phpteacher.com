
declare(strict_types=1);

namespace Subsystems\IT_Tools\Populate\KML_Import;

use LegacyBoundedContexts\Infrastructure\WrapDBAL\
       DomainModel\Interfaces\IHandCodedWrite;
use SimpleXMLElement;

use function explode;
use function file_get_contents;
use function preg_replace;
use function str_replace;
use function trim;

class RKml implements SQLKml
{
  private IHandCodedWrite $write;

  public function __construct(IHandCodedWrite $write)
  {
    $this->write = $write;
  }

  public function importKml(string $path): void
  {
    $sql = self::SQL_INSERT_PLACEMARKS;
    $path = preg_replace('|^.+/|', '', $path)
    $leagueId = (int)str_replace('.kml', '', $path);
    $xml = new SimpleXMLElement(file_get_contents($path));
    $docName = trim((string)$xml->Document->name);
    $placemarks = $xml->Document->Folder->Placemark;
    foreach ($placemarks as $placemark) {
      $pointName = trim((string)$placemark->name);
      $coords = trim($placemark->Point->coordinates);
      [$coordX, $coordY, $coordZ] = explode(',', $coords);
      $parms = [$leagueId, $docName, $pointName,
        $coordX, $coordY, $coordZ,];
      $this->write->updateRow($sql, $parms);
    }
  }
}