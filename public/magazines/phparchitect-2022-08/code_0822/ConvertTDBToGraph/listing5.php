public function topCategoryNode(int $articleId): void
{
    	$node =  $this->session->run(<<<'CYPHER'
    	MATCH (c:Category) - [:CATEGORIZES] -> (node)
    	WITH node, collect(c) AS categoryDegree
    	RETURN node
    	ORDER BY categoryDegree DESC
    	LIMIT 1
    	CYPHER, compact('articleId'))
        	->getAsCypherMap(0)
        	->getAsNode('node');

    	echo 'LABEL: ' . $node->getLabels()->first() . PHP_EOL;
    	echo 'ID: ' . $node->getProperty('id') . PHP_EOL;
}