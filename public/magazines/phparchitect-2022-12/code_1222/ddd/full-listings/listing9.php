<?php

final class RAppEventDefault implements IRAppEvent
{
    /**
     * Should be called while within a transaction
     */
    public function save(
        string $insert,
        string $read,
        array $parms,
        Connection $conn
    ): array {
        $statement = $conn->prepare($insert);
        $statement->execute($parms);
        $statement = $conn->prepare($read);
        $statement->execute([$statement->lastInsertId()]);
        $readback = $statement->fetchAll('assoc');
        if (!(is_array($readback) && array_key_exists(0, $readback))) {
            throw new DatabaseException('Event readback failed');
        }
        return $readback[0];
    }
}