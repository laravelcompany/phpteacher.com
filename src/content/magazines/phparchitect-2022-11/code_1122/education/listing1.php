use Laminas\Diactoros\Request\Serializer as ReqSerializer;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\Serializer as ResSerializer;

while (true) {
  $readSockets = $origReadSockets;
  $numChanged = socket_select(
    $readSockets, $writeSockets,
    $exceptSockets, 0
  );
  if ($numChanged === false) { continue; }
  if ($numChanged === 0) { continue; }

  foreach ($exceptSockets as $badSocket) {
    echo 'Closing bad socket' . PHP_EOL;
    socket_close($badSocket);
    $unset = array_search(
      $badSocket, $readSockets
    );
    unset($readSockets[$unset]);
    $unset = array_search($badSocket, $exceptSockets);
    unset($exceptSockets[$unset]);
  }

  if (in_array($socket, $readSockets)) {
    $newSocket = socket_accept($socket);
    $origReadSockets[] = $newSocket;
    $unset = array_search($socket, $readSockets);
    unset($readSockets[$unset]);
  }

  foreach ($readSockets as $currentSocket) {
    $data = socket_read($currentSocket, 1024);
    if ($data === false) {
      $unset = array_search(
        $currentSocket, $origReadSockets
      );
      unset($origReadSockets[$unset]);
      continue;
    }

    $data = trim($data);
    if ( ! empty($data)) {
      $request = ReqSerializer::fromString($data);
      $response = (new Response())->withStatus(200);
      $msg = 'You requested ' . $request->getUriString() .
        ' with verb ' . $request->getMethod();
      $response->getBody()->write($msg);
      $respString = ResSerializer::toString($response);

      socket_write($currentSocket, $respString);
      socket_close($currentSocket);
      $unset = array_search(
        $currentSocket, $origReadSockets
      );
      unset($origReadSockets[$unset]);
	 }
  }
}
