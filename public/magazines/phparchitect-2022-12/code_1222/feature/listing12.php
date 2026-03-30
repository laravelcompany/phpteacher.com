namespace PhpArch\Ast\Node;

abstract class AbstractJsonNode
{
    public function __construct(public string $name)
    {
    }
}