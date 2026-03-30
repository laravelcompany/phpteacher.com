$fileStreamList = [
    fopen('file1.txt', 'r'),
    fopen('file2.txt', 'r'),
    // ...
];
foreach ($fileStreamList as $fileStream) {
    stream_set_blocking($fileStream, false);
}