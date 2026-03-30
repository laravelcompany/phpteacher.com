echo "Number of columns: " .
       $queryResult->__deprecated_column_count."\n";

$resultAddr = FFI::addr($queryResult);
$rowCount = $dbFFI->duckdb_row_count($resultAddr);
$colCount = $dbFFI->duckdb_column_count($resultAddr);

for ($row = 0; $row < $rowCount; $row++) {
  for ($column = 0; $column < $colCount; $column++) {
    $value = $dbFFI->duckdb_value_varchar(
        $resultAddr,
        $column,
        $row
    );
    echo ($value !== null ?
        FFI::string($value)  :
        '')." ";
    $dbFFI->duckdb_free($value);
  }

  echo "\n";
}

$dbFFI->duckdb_destroy_result(FFI::addr($queryResult));
$dbFFI->duckdb_disconnect(FFI::addr($connection));
$dbFFI->duckdb_close(FFI::addr($database));