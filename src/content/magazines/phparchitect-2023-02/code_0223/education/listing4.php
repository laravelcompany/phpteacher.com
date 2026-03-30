#[RouteGroup('/api/posts')]
class APIController
{
  protected $services = [];
  public function __construct(
    protected PostService $postService
  ) {
    $guzzle = new Client();
    $uri = 'http://localhost:8082/api/services/users';
    $response = $guzzle->get($uri);
    $body = $response->getBody()->getContents();
    $data = json_decode($body, true);
    $this->services['user'] = $data[0];
  }

	// ...

  #[RouteInfo('/', methods: ['POST'])]
  public function createAction(
    ServerRequestInterface $request
  ) {
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true);

    $auth = $request->getHeader('Authorization');
    $token = str_replace('Bearer ', '', $auth);
    $guzzle = new Client();
    $res = $guzzle->post(
      $this->services['user']
      . '/api/users/'
      . $data['author_id']
      . '/validate',
      [
        'json' => ['token' => $token]
      ]
    );

    if ($res !== 200) {
      return new EmptyResponse(403);
    }

    $post = $this->postService->createPost($data);
    return new JsonResponse($post);
  }
}