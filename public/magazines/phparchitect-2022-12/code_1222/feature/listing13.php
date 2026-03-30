namespace PhpArch\Ast;

use PhpArch\Ast\Node\AbstractJsonNode;
use PhpArch\Ast\Node\ChildAwareItemNode;
use PhpArch\Ast\Node\ItemNode;

final class JsonParser
{
  // ...

  /**
   * @return AbstractJsonNode[]
   */
  private function createNodes(array $jsonArray): array
  {
    $nodes = [];

    foreach ($jsonArray as $key => $value) {
      if (is_array($value)) {
        // A. has array children?
        $nestedNodes = $this->createNodes($value);
        $nodes[] = new ChildAwareItemNode(
                        $key,
                        $nestedNodes
                    );
      } else {
        // B. is simple node?
        $nodes[] = new ItemNode($key, $value);
      }
    }

    return $nodes;
  }
}