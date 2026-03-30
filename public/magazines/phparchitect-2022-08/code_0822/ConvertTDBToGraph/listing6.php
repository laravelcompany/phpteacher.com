public function doubleCommenters(int $articleId): array {
    	return $this->session->run(<<<'CYPHER'
    	MATCH (b:Article) <- [:COMMENTED_ON*1..] - (:Comment) <- [:Commented] - (u:User),
          	(u) - [:COMMENTED] -> (:Comment) - [:COMMENTED_ON*1..] -> (a:Article)
    	WHERE a <> b
    	RETURN DISTINCT u AS user
    	CYPHER)
        	->pluck('user')
        	->toArray();
}