--TEST--
zlib_get_coding_type()
--EXTENSIONS--
zlib
--FILE--
<?php
ini_set('zlib.output_compression', 'Off');
var_dump(zlib_get_coding_type());
ini_set('zlib.output_compression', 'On');
var_dump(zlib_get_coding_type());
?>
--EXPECT--
bool(false)
bool(false)