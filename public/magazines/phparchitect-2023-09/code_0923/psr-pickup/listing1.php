namespace PsrsInAction;

class TheService implements ServiceInterface
{
    protected RepositoryInterface $repository;

    public function __construct(
        RepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

	public function getRepository():
      RepositoryInterface
    {
		return $this->repository;
	}
}