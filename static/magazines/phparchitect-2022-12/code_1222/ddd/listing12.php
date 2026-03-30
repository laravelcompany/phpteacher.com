<?php

namespace ...\ReportException\Repository;

final class RExceptionReport
{
  use ExceptionReportTrait;

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
    $this->loadExceptionReportsTable();
    $conn = $this->exceptionReportsTable->getConnection();

    try {
      $conn->transactional(function () use ($captures) {
        foreach ($captures as $capture) {
          $data = $capture->data();
          $entity = $this->exceptionReportsTable
                                    ->newEntity($data);
          $this->exceptionReportsTable
                                 ->saveOrFail($entity);
        }
      });
    } catch (Exception) {
      return false;
    }
    return true;
  }
}