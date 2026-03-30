#[RouteGroup('/api/posts')]
class APIController
{
    public function __construct(
        protected PostService $postService
    ) { }

    #[RouteInfo('/')]
    public function listAction()
    {
        return new JsonResponse([
            '_embedded' => [
                'posts' => $this->postService->all(),
            ],
        ]);
    }

    #[RouteInfo('/{id}')]
    public function getPost(
        ServerRequestInterface $request,
        string $id
    ): JsonResponse {
        try {
            return new JsonResponse(
                $this->postService->find($id)
            );
        } catch (\\RuntimeException $e) {
            return new JsonResponse(
                [
                    'title' => 'Post Not Found',
                    'detail' => 'Post was not found',
                ],
                404
            );
        }
    }
}