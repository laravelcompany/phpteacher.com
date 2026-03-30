
while (true) {

$connection = socket_accept($socket);

$buffer = socket_read($connection, 1024, PHP_NORMAL_READ);

echo $buffer;

socket_write($connection, $buffer);

socket_read($connection, 1024);

socket_close($connection);

}