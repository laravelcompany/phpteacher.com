namespace PhpArch\Ast\Node;

final class ItemNode
{
    public function __construct(
        public string|int $name,
        public string $value
    ) {
    }
}