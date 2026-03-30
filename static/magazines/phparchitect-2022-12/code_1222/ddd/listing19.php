<?php
namespace LegacyBoundedContexts\SpikeCountEvents\Repository;

final class RCountEvents extends BaseModel
{
  public function collectCount(): int
  {
    $sql = 'select count(*) row_count
        from local_app_events
        limit 1';
    try {
      $statement = $this->sql->executeQuery($sql, []);
      $rows = $statement->fetchAll();
    } catch (DBALException $e) {
      return 0;
    }
    if (is_array($rows) &&
           (1 === count($rows))) {
      return $rows[0]['row_count'];
    }
    return 0;
  }

  public function storeCount(int $count): void
  {
    $sql = 'insert into event_counts
         (when_counted, event_count, created, modified)
         VALUES (now(), ?, now(), now())';
    $parms = [$count];
    try {
      $connection = $this->db->getConnection();
      $appEvent = $this->appEvent();

      $connection->transactional(
        function ($conn)
        use ($sql, $parms, $appEvent) {
          $conn->executeUpdate($sql, $parms);
          $appEvent->save($conn);
        }
      );
    } catch (JsonException|DBALException|Exception $e) {
      $detail = [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'count' => $count,
      ];

      RecordExceptionReport::capture(
                 'Legacy store count failed', $detail);
      RecordExceptionReport::flush();
    }
  }

  private function appEvent(): IAppEvent
  {
    return AppEventFactory::dbStateChangeAppEvent(
      'Spike for legacy event count'
    );
  }
}