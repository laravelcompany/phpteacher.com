final readonly class NewFolder
{
    public function __construct(
        private string $name,
        private string $icon = 'x-folder-icon',
        private null|string $parentId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'parent_id' => $this->parentId,
        ];
    }

    public function validate(): bool
    {
        return Validator::make($this->toArray(), [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'icon' => ['required', 'string'],
            'parent_id' => ['nullable', 'string']
        ])->fails();
    }
}