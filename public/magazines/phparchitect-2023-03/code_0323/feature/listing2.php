$dbFFI = FFI::load('duckdb-ffi.h');

$database   = $dbFFI->new("duckdb_database");
$connection = $dbFFI->new("duckdb_connection");

$result = $dbFFI->duckdb_open(
    null,
    FFI::addr($database)
);

if ($result === $dbFFI->DuckDBError) {
    $dbFFI->duckdb_disconnect(FFI::addr($connection));
    $dbFFI->duckdb_close(FFI::addr($database));
    throw new Exception('Cannot open database');
}