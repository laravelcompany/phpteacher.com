$queryResult = $dbFFI->new('duckdb_result');

$result = $dbFFI->duckdb_query(
    $connection,
    'SELECT * FROM integers; ',
    FFI::addr($queryResult)
);

if ($result === $dbFFI->DuckDBError) {
    $resultAddr = FFFI::addr($queryResult);
    $error = "Error in query: " .
    $dbFFI->duckdb_result_error($resultAddr);
    $dbFFI->duckdb_destroy_result($resultAddr);
    $dbFFI->duckdb_disconnect(FFI::addr($connection));
    $dbFFI->duckdb_close(FFI::addr($database));
    throw new Exception($error);
}