public function connectCommentToArticles(): ResultSummary
{
    	return $this->session->run(<<<'CYPHER'
    	MATCH (c:Comment), (a:Article {id: c.article_id})
    	MERGE (c) - [:COMMENTED_ON] -> (a)
    	CYPHER)->getSummary();
}

public function connectParentComments(): ResultSummary
{
    	return $this->session->run(<<<'CYPHER'
    	MATCH (c:Comment), (p:Comment {id: c.parent_id})
    	MERGE (c) - [:COMMENTED_ON] -> (p)
    	CYPHER)->getSummary();
}