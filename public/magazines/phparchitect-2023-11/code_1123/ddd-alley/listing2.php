<?php

declare(strict_types=1);

use LegacyBoundedContexts\Infrastructure\WrapDBAL\
        Repository\HandCodedWrite;
use Service\ViewService;
use Subsystems\IT_Tools\Populate\KML_Import\Kml;
use Subsystems\IT_Tools\Populate\KML_Import\RKml;

class KmlFactory
{
  public static function create(): Kml
  {
    $view = (new ViewService())->setView(__DIR__ . '/View');
    return new Kml($view, self::repository());
  }

  private static function repository(): RKml
  {
    return new RKml(new HandCodedWrite());
  }
}