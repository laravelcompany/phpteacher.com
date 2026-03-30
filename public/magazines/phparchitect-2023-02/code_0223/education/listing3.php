#[RouteGroup('/api/services')]
class APIController
{
  protected array $services = [];

  public function __construct()
  {
    if (file_exists(getcwd() . '/services.json')) {
      $this->services = json_decode(
        file_get_contents(getcwd() . '/services.json'),
        true
      );
    }
  }

  #[RouteInfo('/')]
  public function listAction()
  {
    return new JsonResponse([
      '_embedded' => [
        'services' => $this->services
      ]
    ]);
 }

  #[RouteInfo('/', methods: ['POST'])]
  public function createAction(
    ServerRequestInterface $request
  ) {
    $body = $request->getBody()->getContents();
    $data = json_decode($body, true);
    $this->services[$data['name']][] = [
      'address' => $data['address']
    ];
    file_put_contents(
      getcwd() . '/services.json',
      json_encode($this->services)
    );

    return new JsonResponse($body);
  }

  #[RouteInfo('/{serviceName}')]
  public function getDepartment(
    ServerRequestInterface $request,
    string $serviceName
  ) {
    return new JsonResponse(
      $this->services[$serviceName]
    );
  }
}