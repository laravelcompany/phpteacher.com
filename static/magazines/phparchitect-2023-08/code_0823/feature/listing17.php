final readonly class TagService
{
    public function __construct(
        private Client $client,
        private null|string $note = null,
    ) {}

    public function sync(array $tags): Collection
    {
        // Here we would send the request through to
        // the client as we usually would do.
    }
}