$pool = getCachePool('my_caching_pool');
$item = $pool->getItem('PHPArch_is_awesome_1');

if ( ! $item->isHit() ) {
    $item->set( generateDataArray() );
    $pool->save($item);
}

return $item;