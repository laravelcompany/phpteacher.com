$page = $client->fetchPage('*', 'homepage');
$client->fetchPosts([
    'page' => $page,
    'page_size' => 10
]);
$query = new \Contentful\Delivery\Query();
$query->setContentType('<product_content_type_id>')
$entries = $client->getEntries($query);
$items = $client->getItems((new QueryParams())
                   ->equals('system.type', 'article'));
$response = $api->query(
    Predicates::at('document.type', 'blog-post')
);

$results = $client->fetch(
    '*[_type == $type][0...3]',
    ['type' => 'product']
);