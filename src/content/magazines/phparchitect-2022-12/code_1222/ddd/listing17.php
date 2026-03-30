<?php

namespace LegacyBoundedContexts\...\AppEvent\Repository;

final class RAppEventDefault
  extends BaseModel implements IRAppEvent
{
  /**
   * Should be called while within a transaction
   *
   * @throws \Doctrine\DBAL\DBALException
   */
  public function save(
    string $insert,
    string $read,
    array $parms,
    Connection $connection
  ): array
  {
    $connection->executeUpdate($insert, $parms);
    $id = (int)$connection->lastInsertId();
    $statement = $connection->executeQuery($read, [$id]);
    $rows = $statement->fetchAll();
    if (empty($rows)) {
      throw new InvalidArgumentException('Readback failed');
    }
    return $rows[0];
  }
}