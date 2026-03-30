namespace PhpArch\Ast;

use PhpArch\Ast\Node\AbstractJsonNode;
use PhpArch\Ast\Node\ChildAwareItemNode;

final class JsonNodeTraverser
{
  /**
   * @param AbstractJsonNode[] $jsonNodes
   */
  public function traverse(array $jsonNodes): void
  {
    foreach ($jsonNodes as $jsonNode) {
      // @todo add node visitors

      // traverse all the children
      if ($jsonNode instanceof ChildAwareItemNode) {
        // @todo add node visitors
        $this->traverse($jsonNode->subNodes);
      }
    }
  }
}