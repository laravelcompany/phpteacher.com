Route::get('/articles', IndexController::class)
    ->name('articles:index');

interface ArticleRepository
{
    public function all(): Collection;
}

final class EloquentArticleRepository
            implements ArticleRepository
{
    public function all(): Collection
    {
        return Article::query()->get();
    }
}

interface LaravelService {}

final class ArticleService implements LaravelService
{
    public function __construct(
		private readonly ArticleRepository $repository,
	) {}

	public function all(): Collection
	{
		return $this->repository->all();
	}
}

final readonly class IndexController
{
    public function __construct(
        private ArticleService $service,
    ) {}

    public function __invoke(
        Request $request
    ): JsonResponse {
        return new JsonResponse(
            data: $this->service->all(),
            status: Response::HTTP_OK,
        );
    }
}