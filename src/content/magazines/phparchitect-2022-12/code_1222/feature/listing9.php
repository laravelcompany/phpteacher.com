namespace PhpArch\Ast\Node;

final class ChildAwareItemNode
{
    /**
     * @param ItemNode[] $subNodes
     */
    public function __construct(
        public string|int $name,
        public array $subNodes
    ) {
    }
}