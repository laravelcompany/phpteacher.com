						...
				} // End IF 600 or more rows

        float x;
        float y;
        float z;

        getAccelerometerData(fileDescriptor, x, y, z);

        string accelerometer_insert_query =
            "INSERT INTO accelerometer_data "
            + "(axis_x, axis_y, axis_z) VALUES ("
            + std::to_string(x) + ", " + std::to_string(y)
            + ", " + std::to_string(z) + ")";

        // Send insert query to database
        query_state = mysql_query(
                      connection,
                      accelerometer_insert_query.c_str()
                      );

        if(query_state != 0)
        {
            cout << mysql_error(connection) << endl;
            return 1;
        }

        usleep(100000); // 100 milliseconds
    } // End Forever Loop

    mysql_close(&mysql);

    return 0;