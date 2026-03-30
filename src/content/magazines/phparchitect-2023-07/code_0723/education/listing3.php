// index.php

<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function(
	RequestInterface $request,
	ResponseInterface $response,
) {
	$response->getBody()->write(
	    "This is the main route of a Slim application"
	);
	return $response;
});

$app->get('/hello', function(
RequestInterface $request,
ResponseInterface $response,
) {
	parse_str($request->getUri()->getQuery(), $args);	
	$name = $args['name'] ?? 'world';	
	$response->getBody()->write("Hello {$name}");	
	return $response;
});

$app->run();