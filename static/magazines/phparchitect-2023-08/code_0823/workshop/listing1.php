<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rect...\TypedPropertyFromStrictConstructorRector;

return static function (
    RectorConfig $rectorConfig
): void {
    // register single rule
    $rectorConfig->rule(
       TypedPropertyFromStrictConstructorRector::class
    );

    // here we can define, what sets of rules 
    // will be applied
    // tip: use "SetList" class to autocomplete
    // sets with your IDE
    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
    ]);
};