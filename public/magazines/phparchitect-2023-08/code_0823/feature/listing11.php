final readonly class Folder
{
    public function __construct(
        public string $id,
        public string $name,
        public string $icon,
        public bool $parent,
        public string $parentId,
    ) {}

    public static function fromResponse(
        array $data
    ): Folder {
        return new Folder(
            id: $data['id'],
            name: $data['attributes']['name'],
            icon: $data['attributes']['icon'],
            parent: $data['attributes']['parent'],
            parentId: $data['attributes']['parent_id'],
        );
    }
}