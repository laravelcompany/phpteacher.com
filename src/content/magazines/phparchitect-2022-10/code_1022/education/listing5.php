
while (true) {

$connection = socket_accept($socket);

$buffer = socket_read($connection, 1024, PHP_NORMAL_READ);

$request = Laminas\Diactoros\Request\Serializer::fromString($buffer);

$response = (new Laminas\Diactoros\Response())->withStatus(200);

$response->getBody()->write('You requested ' .  $request->getUriString() . ' with verb ' . $request->getMethod());

$responseString = Laminas\Diactoros\Response\Serializer::toString($response);

socket_write($client, $responseString);

socket_close($client);

}