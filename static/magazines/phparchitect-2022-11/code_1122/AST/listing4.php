<?php

use Nette\Neon\Node;
use Nette\Neon\Node\ArrayItemNode;
use Nette\Neon\Node\LiteralNode;
use Nette\Neon\Traverser;

// here we parse the YAML content to AST Node

$neonTraverser = new Traverser();

$traversedNode = $neonTraverser->traverse(
    $neonAstNode,
    function (Node $node) {
        // is it array item?
        if (!$node instanceof ArrayItemNode) {
            return null;
        }

        // is it "public" key? continue
        if (!$node->key instanceof LiteralNode &&
            $node->key->value !== 'public'
        ) {
            return null;
        }

        // is it `false` value already? skip it
        if ($node->value->toValue() === false) {
            return null;
        }

        // Change the value to `false` - job done!
        $node->value = new LiteralNode(false);
        return $node;
    }
);