<?php

declare(strict_types=1);

use Subsystems\IT_Tools\Populate\KML_Import\KmlFactory;

$controller = KmlFactory::create();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->execute();
}
echo $controller->render();