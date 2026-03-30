
while (true) {

$connection = socket_accept($socket);

$buffer = socket_read($connection, 1024, PHP_NORMAL_READ);

$request = Laminas\Diactoros\Request\Serializer::fromString($buffer);

// We'll handle the response in a moment

}