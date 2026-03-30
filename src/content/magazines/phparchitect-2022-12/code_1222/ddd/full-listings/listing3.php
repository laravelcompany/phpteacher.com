<?php

final class CountEventsFactory
{
    #[Pure]
    public static function countEvents(): CountEvents
    {
        $repository = new RCountEvents();
        return new CountEvents($repository);
    }
}