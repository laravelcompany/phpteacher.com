namespace PhpArch\Ast\Contract;

use PhpArch\Ast\Node\AbstractJsonNode;

interface JsonNodeVisitorInterface
{
  public function enterNode(AbstractJsonNode $jsonNode);
}