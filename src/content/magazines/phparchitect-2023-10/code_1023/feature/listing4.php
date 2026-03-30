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

final readonly class IndexController
{
    public function __construct(
        private ArticleRepository $articleRepository,
    ) {}

    public function __invoke(
        Request $request
    ): JsonResponse {
        return new JsonResponse(
            data: $this->articleRepository->all(),
            status: Response::HTTP_OK,
        );
    }
}