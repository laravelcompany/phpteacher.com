<?php

use Rect...\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;

return static function (
    RectorConfig $rectorConfig
): void {
    $rectorConfig->paths([
        __DIR__ . '/tests'
    ]);

    // register a single rule
    $rectorConfig->rule(
       InlineConstructorDefaultToPropertyRector::class
    );

    // define sets of rules
    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::CODING_STYLE,
    ]);
};