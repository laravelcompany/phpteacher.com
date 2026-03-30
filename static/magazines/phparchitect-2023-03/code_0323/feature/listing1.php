<?php

$ffi = FFI::cdef(
    // function declaration in C language
    'int abs(int j);',
    // library from which the function will be called
    'libc.so.6'
);

var_dump($ffi->abs(-42)); // int(42)