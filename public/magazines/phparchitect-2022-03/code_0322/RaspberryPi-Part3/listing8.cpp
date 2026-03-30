while(1)
{
		...
		if (num_of_rows >= 600)
		{
		    string oldest_entry_query =
		        "SELECT id FROM accelerometer_data "
		        + "ORDER BY created ASC LIMIT 1";
		
		    // Send oldest entry query to database
		    query_state = mysql_query(
            connection,
            oldest_entry_to_delete.c_str());
		
		    if(query_state != 0)
		    {
		        cout << mysql_error(connection) << endl;
		        return 1;
		    }
		
		    result = mysql_store_result(connection);
		
		    string id_to_delete = mysql_fetch_row(result)[0];
		
		    string oldest_entry_to_delete =
            "DELETE FROM accelerometer_data WHERE id = "
            + id_to_delete;
		
		    // Send delete query to database
		    query_state = mysql_query(
            connection,
            oldest_entry_to_delete.c_str());
		
		    if(query_state != 0)
		    {
		        cout << mysql_error(connection) << endl;
		        return 1;
		    }
		
		    // Free result or a memory leak will occur
		    mysql_free_result(result);
		} // End IF 600 or more rows
		...
} // End Forever Loop