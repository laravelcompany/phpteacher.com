<?php

final class CountEvents
{
    private RCountEvents $repository;

    public function __construct(RCountEvents $repository)
    {
        $this->repository = $repository;
    }

    public function insertCurrentCount(): void
    {
        $count = $this->repository->collectCount();
        $this->repository->storeCount($count);
    }
}