<?php

abstract class BaseAppEvent implements CAppEventOriginatingContexts, IAppEvent
{
    public function save(Connection $conn): void
    {
        $parms = [
            $this->action,
            static::$subsystem,
            $this->description,
            $this->detail,
            $this->uuid,
        ];
        $this->readback = $this->repository
            ->save(static::$insert, static::$read, $parms, $conn);
    }
}