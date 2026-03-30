namespace PhpArch\Ast;

final class JsonParser
{
  public function parse(string $inputResponse): array
  {
    $jsonData = json_decode($inputContents, true);

    return $this->createNodes($jsonData);
  }

  private function createNodes(array $jsonArray): array
  {
    $nodes = [];

    foreach ($jsonArray as $key => $value) {
      // @todo parse the $key and $value to objects
    }

    return $nodes;
  }
}