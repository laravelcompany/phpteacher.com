<?php
namespace LegacyBoundedContexts\...tException\Repository;

final class RExceptionReport extends BaseModel
{
  /**
   * @param ExceptionReportEntity[] $captures
   *
   * @return bool
   */
  public function flush(array $captures): bool
  {
    if (empty($captures)) {
      return true;
    }

    $connection = $this->db->getConnection();
    $sql = 'insert into exception_reports
            (description, detail, created, modified)
            VALUES (?, ?, now(), now())';

    try {
      $connection->transactional(
        function ($conn) use ($sql, $captures) {
          foreach ($captures as $capture) {
            $parms = $capture->data();
            $conn->executeUpdate($sql, $parms);
          }
        }
      );
    } catch (DBALException|Exception $e) {
      return false;
    }

    return true;
  }
}