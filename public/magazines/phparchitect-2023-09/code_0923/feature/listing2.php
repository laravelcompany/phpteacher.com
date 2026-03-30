final class IndexController
{
  use HasInertiaResponse;

  public function __invoke(Request $request): Response
  {
	return $this->response
                ->render('PageName/Component');
  }
}