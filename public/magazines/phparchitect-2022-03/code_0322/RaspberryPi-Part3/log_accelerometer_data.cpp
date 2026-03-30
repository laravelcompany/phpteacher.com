#include <iostream>
#include <unistd.h>
#include <string>
#include <stdio.h>
#include <stdlib.h>
#include <linux/i2c-dev.h>
#include <sys/ioctl.h>
#include <fcntl.h>
#include "/usr/include/mysql/mysql.h"

using namespace std;

MYSQL *connection, mysql;
MYSQL_RES *result;
MYSQL_ROW row;
int query_state;

#define HOST "localhost" // The hostname and credentials are all passed to the
#define USER "accelerometer" // mysql_real_connect() function
#define PASSWD "accelerometer" 
#define DB "AccelerometerData"

#define MAX_ROWS 600 // 60 seconds of accelerometer data

// Cursor Movement: Clear screen to print readable output of axis data
#define clearScreen() printf("\e[1;1H\e[2J")

void getAccelerometerData(int& fileDescriptor, float& xAxis, float& yAxis, float& zAxis)
{
    // Get I2C device, MMA8452Q I2C address is 0x1D(29)
    ioctl(fileDescriptor, I2C_SLAVE, 0x1D);

    // Select mode register(0x2A)
    // Standby mode(0x00)
    char config[2] = {0};
    config[0] = 0x2A;
    config[1] = 0x00;

    write(fileDescriptor, config, 2);

    // Select mode register(0x2A)
    // Active mode(0x01)
    config[0] = 0x2A;
    config[1] = 0x01;
    write(fileDescriptor, config, 2);

    // Select XYZ data configuration register(0x0E)
    // Set range to +/- 2g(0x00)
    config[0] = 0x0E;
    config[1] = 0x00;
    write(fileDescriptor, config, 2);

    usleep(5000); // 5 ms

    // Read 7 bytes of data
    // status, xAccl msb, xAccl lsb, yAccl msb, yAccl lsb, zAccl msb, zAccl lsb
    char data[7] = {0};

    if(read(fileDescriptor, data, 7) != 7)
    {
        printf("Error : Input/Output error \n");
    }
    else
    {
      	// Convert the data to 12-bits
      	int xAccl = ((data[1] * 256) + data[2]) / 16;
      	if(xAccl > 2047)
      	{
      		  xAccl -= 4096;
      	}

      	int yAccl = ((data[3] * 256) + data[4]) / 16;
      	if(yAccl > 2047)
      	{
      		  yAccl -= 4096;
      	}

      	int zAccl = ((data[5] * 256) + data[6]) / 16;
      	if(zAccl > 2047)
      	{
      		  zAccl -= 4096;
      	}

      	int scale = 2;

      	xAxis = (float) xAccl / (float)(1<<11) * (float)(scale);
      	yAxis = (float) yAccl / (float)(1<<11) * (float)(scale);
      	zAxis = (float) zAccl / (float)(1<<11) * (float)(scale);

        // Display to stdout
        clearScreen();
      	printf("G-Force in X-Axis : %f \n", xAxis);
      	printf("G-Force in Y-Axis : %f \n", yAxis);
      	printf("G-Force in Z-Axis : %f \n", zAxis);
    }
}

int main()
{
    // Open connection to I2C bus
    int fileDescriptor;
    char bus[] = "/dev/i2c-1";

    if((fileDescriptor = open(bus, O_RDWR)) < 0)
    {
      	printf("Failed to open the bus. \n");
      	exit(1);
    }

    //initialize database connection
    mysql_init(&mysql);

    // the three zeros are: Which port to connect to, which socket to connect to
    // and what client flags to use.  unless you're changing the defaults you only need to put 0 here
    connection = mysql_real_connect(&mysql,HOST,USER,PASSWD,DB,0,0,0);

    // Report error if failed to connect to database
    if (connection == NULL)
    {
        cout << mysql_error(&mysql) << endl;
        return 1;
    }

    // Forever Loop
    while(1)
    {
        string accelerometer_count_query = "SELECT COUNT(*) FROM accelerometer_data";

        // Send count query to database
        query_state = mysql_query(connection, accelerometer_count_query.c_str());

        if(query_state != 0)
        {
            cout << mysql_error(connection) << endl;
            return 1;
        }

        // store result
        result = mysql_store_result(connection);

        int num_of_rows = atoi(mysql_fetch_row(result)[0]);

        // Free result or a memory leak will occur
        mysql_free_result(result);

        if (num_of_rows >= 600)
        {
            string oldest_entry_query = "SELECT id FROM accelerometer_data ORDER BY created ASC LIMIT 1";

            // Send oldest entry query to database
            query_state = mysql_query(connection, oldest_entry_query.c_str());

            if(query_state != 0)
            {
                cout << mysql_error(connection) << endl;
                return 1;
            }

            result = mysql_store_result(connection);

            string id_to_delete = mysql_fetch_row(result)[0];

            string oldest_entry_to_delete = "DELETE FROM accelerometer_data WHERE id = " + id_to_delete;

            // Send delete query to database
            query_state = mysql_query(connection, oldest_entry_to_delete.c_str());

            if(query_state != 0)
            {
                cout << mysql_error(connection) << endl;
                return 1;
            }

            // Free result or a memory leak will occur
            mysql_free_result(result);
        } // End IF 600 or more rows

        float x;
        float y;
        float z;

        getAccelerometerData(fileDescriptor, x, y, z);

        string accelerometer_insert_query = "INSERT INTO accelerometer_data (axis_x, axis_y, axis_z) VALUES ("
                + std::to_string(x) + ", " + std::to_string(y) + ", " + std::to_string(z) + ")";

        // Send insert query to database
        query_state = mysql_query(connection, accelerometer_insert_query.c_str());

        if(query_state != 0)
        {
            cout << mysql_error(connection) << endl;
            return 1;
        }

        usleep(100000); // 100 milliseconds
    } // End Forever Loop

    mysql_close(&mysql);

    return 0;
}