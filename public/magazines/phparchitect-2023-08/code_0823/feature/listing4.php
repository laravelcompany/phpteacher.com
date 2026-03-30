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
}