$host = '127.0.0.1';
$port = 5000;
$socket = stream_socket_client(
    "tcp://$host:$port", $errno, $errstr, 30
);
$dataGenerator = new SocketDataGenerator($socket);

foreach ($dataGenerator->readData() as $data) {
    // Process the data

    if (str_contains($data, '--TERMINATE--')) {
        $data->send('disconnect');
    }
}