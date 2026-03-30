--FILE--
<?php
ini_set('zlib.output_compression', 'Off');
$off = zlib_get_coding_type();
ini_set('zlib.output_compression', 'On');
$on = zlib_get_coding_type();
$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
$gzip = zlib_get_coding_type();
var_dump($off);
var_dump($on);
var_dump($gzip);
?>
--EXPECT--
bool(false)
bool(false)
string(4) "gzip"