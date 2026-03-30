final readonly class FolderService
{
    public function __construct(
        private Client $client,
    ) {}

    public function all()
    {
        $response = $this->client->get('/folders');

        if ($response->failed()) {
            throw new FailedToFetchFolders(
                response: $response,
            );
        }

        // Process payload and turn it into objects.
    }
}