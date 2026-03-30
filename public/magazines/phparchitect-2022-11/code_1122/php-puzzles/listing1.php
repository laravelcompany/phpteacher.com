<?php

class Group
{
    private array $teams = [];

    public function __construct(
        public string $label,
        public int $max
    ){}

    public function isFull(): bool {
        return count($this->teams) === $this->max;
    }

    public function addTeam(Team $team): bool
    {
        // todo
    }
}
