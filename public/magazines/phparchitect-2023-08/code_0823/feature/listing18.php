final readonly class NoteService
{
    public function __construct(
        private Client $client,
    ) {}

    public function tags(string $note): TagService
    {
        return new TagService(
            client: $this->client,
            note: $note,
        );
    }
}