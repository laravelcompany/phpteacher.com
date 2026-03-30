final class IndexController
{
  use HasInertiaResponse;

  public function __invoke(Request $request): Response
  {
    return $this->response->render(
      component: 'PageName/Component',
      props: [
        'articles' => $this->response->lazy(
          callback: fn () => Article::query()->all(),
        ),
      ],
    );
  }
}