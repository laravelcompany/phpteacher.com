namespace PhpArch\Ast\JsonNodeVisitor;

use PhpArch\Ast\Contract\JsonNodeVisitorInterface;
use PhpArch\Ast\Node\AbstractJsonNode;
use PhpArch\Ast\Node\ItemNode;

final class ValidateCityJsonNodeVisitor
              implements JsonNodeVisitorInterface
{
  public function enterNode(AbstractJsonNode $jsonNode)
  {
    if (! $jsonNode instanceof ItemNode) {
      return;
    }

    if ($jsonNode->name !== 'city') {
      return;
    }

    validate_city_name($jsonNode->value);
  }
}