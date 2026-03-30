$result = $dbFFI->duckdb_connect(
    $database,
    FFI::addr($connection)
);

if ($result === $dbFFI->DuckDBError) {
    $dbFFI->duckdb_disconnect(FFI::addr($connection));
    $dbFFI->duckdb_close(FFI::addr($database));
    throw new Exception('Cannot connect to database');
}

$result = $dbFFI->duckdb_query(
    $connection,
    'CREATE TABLE integers(i INTEGER, j INTEGER);',
    null
);

if ($result === $dbFFI->DuckDBError) {
   // Error handling, memory clean up
}

$result = $dbFFI->duckdb_query(
  $connection,
  'INSERT INTO integers VALUES (3,4), (5,6), (7, NULL)',
  null
);

if ($result === $dbFFI->DuckDBError) {
  // Error handling, memory clean up
}