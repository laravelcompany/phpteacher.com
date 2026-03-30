public static function fromGlobals(
    ?array $server = null,
    ?array $query = null,
    ?array $body = null,
    ?array $cookies = null,
    ?array $files = null,
    ?FilterServerRequestInterface $requestFilter = null
): ServerRequest {
  $requestFilter = $requestFilter
  ?: FilterUsingXForwardedHeaders::trustReservedSubnets();

  $server  = normalizeServer(
    $server ?: $_SERVER,
    is_callable(self::$apacheRequestHeaders)
		? self::$apacheRequestHeaders
		: null
  );

  $files = normalizeUploadedFiles($files ?: $_FILES);
  $headers = marshalHeadersFromSapi($server);

  if (null === $cookies &&
      array_key_exists('cookie', $headers)
  ) {
    $cookies = parseCookieHeader($headers['cookie']);
  }

  return $requestFilter(new ServerRequest(
        $server,
        $files,
        UriFactory::createFromSapi($server, $headers),
        marshalMethodFromSapi($server),
        'php://input',
        $headers,
        $cookies ?: $_COOKIE,
        $query ?: $_GET,
        $body ?: $_POST,
        marshalProtocolVersionFromSapi($server)
  ));
}