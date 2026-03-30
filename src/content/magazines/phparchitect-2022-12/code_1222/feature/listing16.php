namespace PhpArch\Ast;

use PhpArch\Ast\Contract\JsonNodeVisitorInterface;
use PhpArch\Ast\Node\AbstractJsonNode;
use PhpArch\Ast\Node\ChildAwareItemNode;

final class JsonNodeTraverser
{
  /**
   * @var JsonNodeVisitorInterface[]
   */
  private array $jsonNodeVisitors = [];

  public function addVisitor(
    JsonNodeVisitorInterface $jsonNodeVisitor
  ): void {
    $this->jsonNodeVisitors[] = $jsonNodeVisitor;
  }

  /**
   * @param AbstractJsonNode[] $jsonNodes
   */
  public function traverse(array $jsonNodes): void
  {
    foreach ($jsonNodes as $jsonNode) {
      foreach ($this->jsonNodeVisitors as $visitor) {
         $visitor->enterNode($jsonNode);

        // traverse all the children
        if ($jsonNode instanceof ChildAwareItemNode) {
          $this->traverse($jsonNode->subNodes);
        }
      }
    }
  }
}