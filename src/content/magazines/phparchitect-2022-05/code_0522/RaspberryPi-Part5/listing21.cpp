...
// Forever Loop
while(1)
{
    string newest_entry_query = "SELECT axis_x, axis_y, axis_z FROM accelerometer_data ORDER BY created DESC LIMIT 1";

    // Send newest entry query to database
    query_state = mysql_query(connection, newest_entry_query.c_str());

    if(query_state != 0)
    {
        cout << mysql_error(connection) << endl;
        return 1;
    }

    // Get result of query (newest axis data)
    result = mysql_store_result(connection);

    row = mysql_fetch_row(result);

    tAxis axis;

    axis.X = stof(row[0]);
    axis.Y = stof(row[1]);
    axis.Z = stof(row[2]);

    // Free result or a memory leak will occur
    mysql_free_result(result);
		...
} // End forever loop
...