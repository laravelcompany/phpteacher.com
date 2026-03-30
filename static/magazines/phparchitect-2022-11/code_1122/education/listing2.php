function processRequest(RequestInterface $request): ResponseInterface
{
  $paths = ['/' => HomepageAction::class];

  if (in_array($request->getUri()->getPath(),  $paths)) {
    $class = $paths[$request->getUri()->getPath()];
    return (new $class())($request);
  } else {
    return (new FourOFourAction())($request);
  }
}

class HomepageAction {
  function __invoke(RequestInterface $request): ResponseInterface
  {
    $response = (new Laminas\\Diactoros\\Response())->withStatus(200);
    $response->getBody()->write('<h1>Hello World!</h1>');
    return $response;
  }
}

class FourOFourAction {
  function __invoke(RequestInterface $request): ResponseInterface
  {
    $response = (new Laminas\\Diactoros\\Response())->withStatus(404);
    $response->getBody()->write('<h1>Not Found!</h1>');
    return $response;
  }
}