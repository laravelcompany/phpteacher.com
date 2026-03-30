typedef struct {
    // deprecated, use duckdb_column_count
    idx_t __deprecated_column_count;
    // deprecated, use duckdb_row_count
    idx_t __deprecated_row_count;
    // deprecated, use duckdb_rows_changed
    idx_t __deprecated_rows_changed;
    // deprecated, use duckdb_column_ family of funcs
    duckdb_column *__deprecated_columns;
    // deprecated, use duckdb_result_error
    char *__deprecated_error_message;
    void *internal_data;
} duckdb_result;