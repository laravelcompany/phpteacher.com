<?php
//See full listing in this articles code download

namespace LegacyBoundedContexts\...\ApplicationServices;

use function array_merge;
use function is_array;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

abstract class BaseAppEvent
     implements CAppEventOriginatingContexts, IAppEvent
{
  public function save(Connection $connection): void
  {
    if (self::DISABLE_APP_EVENT) {
      return;
    }

    $parms = [
      $this->action,
      static::$subsystem,
      $this->description,
      $this->detail,
      $this->uuid,
    ];
    $this->readback = $this->repository
      ->save(
        static::$insert,
        static::$read,
        $parms,
        $connection
      );
  }

  public function notify(): void
  {
    if (self::DISABLE_APP_EVENT) {
      return;
    }

    if (empty($this->readback)) {
      return;
    }
    $domainEvent = DomainEventFactory::domainEvent();
    $domainEvent->notifyDomainEvent(
      static::$sourceTable,
      $this->readback
    );
  }
}