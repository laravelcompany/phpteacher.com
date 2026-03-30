use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
	public function__construct(
		protected MiddlewareInterface     $middleware,
		protected RequestHandlerInterface $nextHandler)
  {}

	public function handle(
        ServerRequestInterface $request
    ): ResponseInterface {
	    return $this->middleware
            ->process($request, $this->nextHandler);
	}
}

$initialHandler =
    new class() implements RequestHandlerInterface {
	    public function __construct(
            protected ResponseFactory $responseFactory
        ) {}

        public function handle(
            ServerRequestInterface $request
        ): ResponseInterface {
            // handle the request to return a
            // response using $responseFactory
        }
    };

$initialResponse = new RequestHandler(
    new RoutingMiddleware(), $initialHandler
);

$nextResponse = new RequestHandler(
    new AuthorizationMiddle(),
    $initialResponse
);