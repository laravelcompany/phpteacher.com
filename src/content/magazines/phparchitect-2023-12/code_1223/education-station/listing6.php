class SocketDataGenerator {
    public function __construct(private $socket) {
    }

    public function readData(): \Generator {
        while (
            ($buffer = fread($this->socket, 1024))
                    !== false
        ) {
            $command = (yield $buffer);
            if ($command === 'disconnect') {
                $this->isActive = false;
                socket_close($this->socket);
                break;
            }
        }
    }
}