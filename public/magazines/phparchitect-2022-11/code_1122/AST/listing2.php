use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\NodeVisitorAbstract;
final class ChangeEchoNumberToStringNodeVisitor
        extends NodeVisitorAbstract
{
    public function enterNode(Node $node): ?Echo_
    {
        // is it echo node?
        if (! $node instanceof Echo_) {
            return null;
        }

        // does echo node have number in it?
        if (! $node->expr instanceof LNumber) {
            return null;
        }

        // yes, here we have exact pattern match
        // change value from a number to a string
        $node->expr = new String_((string) $node->expr);
        // return node to modify it

        return $node;
    }
}