#[RouteGroup('/api/users')]
class APIController
{
  public function __construct(
    protected UserService $userService
  ) { }

  #[RouteInfo('/')]
  public function listAction()
  {
    return new JsonResponse([
      '_embedded' => [
         'users' => $this->userService->all(),
      ],
    ]);
  }

  #[RouteInfo('/', methods: ['POST'])]
  public function createAction(
    ServerRequestInterface $request
  ): JsonResponse {
    ... Create User ...
  }

  #[RouteInfo('/{id}')]
  public function getUser(
    ServerRequestInterface $request,
    string $id
  ): JsonResponse {
    ... Get User or Fail
  }

  #[RouteInfo('/{id}/login', methods: ['POST'])]
  public function login(
    ServerRequestInterface $request,
    string $id
  ): JsonResponse {
     ...
  }

  #[RouteInfo('/{id}/validate', methods: ['POST'])]
  public function validate(
    ServerRequestInterface $request,
    string $id
  ) {
     ... Verify Password
  }
}