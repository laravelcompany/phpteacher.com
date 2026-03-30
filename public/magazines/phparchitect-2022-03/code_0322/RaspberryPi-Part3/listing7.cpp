...
// Forever Loop
while(1)
{
    string accelerometer_count_query =
        "SELECT COUNT(*) FROM accelerometer_data";

    // Send count query to database
    query_state = mysql_query(
        connection,
        accelerometer_count_query.c_str());

    if(query_state != 0)
    {
        cout << mysql_error(connection) << endl;
        return 1;
    }

    // store result
    result = mysql_store_result(connection);

    int num_of_rows = atoi(mysql_fetch_row(result)[0]);
		...