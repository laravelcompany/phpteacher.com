class SocketDataGenerator {
  public function __construct(private $socket) {
  }

  public function readData(): \Generator {
    $sock = $this->socket;
    while (($buffer = fread($sock, 1024)) !== false) {
      yield $buffer;
    }
  }
}

$host = '127.0.0.1';
$port = 5000;
$socket = stream_socket_client(
    "tcp://$host:$port",
    $errno,
    $errstr,
    30
);
$dataGenerator = new SocketDataGenerator($socket);

foreach ($dataGenerator->readData() as $data) {
    // Process the data
}