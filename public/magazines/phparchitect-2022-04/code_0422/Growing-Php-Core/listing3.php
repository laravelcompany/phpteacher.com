--TEST--
zlib_get_coding_type() is gzip
--EXTENSIONS--
zlib
--ENV--
HTTP_ACCEPT_ENCODING=gzip
--FILE--
<?php
ini_set('zlib.output_compression', 'Off');
$off = zlib_get_coding_type();
ini_set('zlib.output_compression', 'On');
$gzip = zlib_get_coding_type();
var_dump($on);
var_dump($gzip);
?>
--EXPECT--
bool(false)
string(4) "gzip"