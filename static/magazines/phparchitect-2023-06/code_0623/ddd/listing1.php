<?php

$start = microtime(true) * 1000.0;

/* initialization/bootstrap not shown */

$timing = new Timing($start);
$timing->measure('Began timing sections');

$service = RenderBodyFactory::create($timing);
$service->execute();

echo PHP_EOL . $timing->show(PHP_EOL) . PHP_EOL;