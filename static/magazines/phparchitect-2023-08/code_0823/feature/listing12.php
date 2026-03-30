final readonly class FolderService
{
    public function __construct(
        private Client $client,
    ) {}

    public function all(): Collection
    {
        $response = $this->client->get('/folders');

        if ($response->failed()) {
            throw new FailedToFetchFolders(
                response: $response,
            );
        }

        return $response->collect('data')->map(
            fn (array $folder) =>
                        Folder::fromRequest($folder)
        );
    }
}