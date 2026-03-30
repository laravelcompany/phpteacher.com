<?php

declare(strict_types=1);

namespace ...\ReportException\ApplicationServices;

final class RecordExceptionReport
{
  private static array $captures = [];

  private function __construct()
  {
  }

  public static function capture(
    string $description,
    array $detail = []
  ): void {
    self::$captures[] =
      new ExceptionReportEntity($description, $detail);
  }

  public static function flush(bool $isTest = false): void
  {
    if ( ! count(self::$captures)) {
      return;
    }
    if ($isTest) {
      self::reset();
    } else {
      $repository = new RExceptionReport();
      if ($repository->flush(self::$captures)) {
        self::reset();
      }
    }
  }

  public static function reset(): void
  {
    self::$captures = [];
  }

  /**
   * Unit test support
   */
  public static function errorCount(): int
  {
    return count(self::$captures);
  }
}