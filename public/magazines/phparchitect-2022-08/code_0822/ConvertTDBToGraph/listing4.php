public function listAllTags(int $articleId): array
{
    return $this->session->run(<<<'CYPHER'
    MATCH p = (:Article {id: $articleId}) <- [:HAS_PARENT*0..] - (:Article)
    UNWIND nodes(p) AS article
    WITH DISTINCT article
    MATCH (article) <- [:TAGS] - (tag:Tag)
    RETURN tag.name AS tag
    CYPHER, compact('articleId'))
        ->pluck('tag')
        ->toArray();
}
