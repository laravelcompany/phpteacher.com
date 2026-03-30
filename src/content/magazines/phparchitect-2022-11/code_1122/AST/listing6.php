<?php
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\NodeTraverser;
use Twig\NodeVisitor\NodeVisitorInterface;

// here we parse the input to Twig AST Node

final class ReplaceTitleWithNameNodeVisitor
    implements NodeVisitorInterface
{
    public function enterNode(
            Node $node,
            Environment $env
    ): Node {
        // we look for "title"
        if (! $node instanceof ConstantExpression) {
            return $node;
        }

        if ($node->getAttribute('value') !== 'title') {
            return $node;
        }

        return new ConstantExpression(
                'name',
                $node->getTemplateLine()
        );
    }

    public function leaveNode(
            Node $node,
            Environment $env
    ): ?Node {
        return $node;
    }

    public function getPriority()
    {
    }
}

$nodeVisitor = new ReplaceTitleWithNameNodeVisitor();

$twigTraverser = new NodeTraverser(
        $environment, 
        [$nodeVisitor]
);

$traversedNode = $twigTraverser->traverse($twigAstNode);