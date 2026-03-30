final class Client
{
    public function __construct(
        private readonly PendingRequest $request,
    ) {}

    public function folders(): FolderService
    {
        return new FolderService(
            client: $this,
        );
    }

    public function notes(): NoteService
    {
        return new NoteService(
            client: $this,
        );
    }

    public function get(string $endpoint): Response
    {
        return $this->request->get($endpoint);
    }
}