...
use Rector\...\ClassMethod\UnSpreadOperatorRector;
...
$rectorConfig->sets([
    SetList::CODE_QUALITY,
    SetList::DEAD_CODE,
    SetList::CODING_STYLE,
]);

// here we can define rules we want to ignore
$rectorConfig->skip([
    UnSpreadOperatorRector::class,
]);