// Constants
#define HOST "localhost"
#define USER "accelerometer"
#define PASSWD "accelerometer"
#define DB "AccelerometerData"

MYSQL *connection, mysql;
MYSQL_RES *result;
MYSQL_ROW row;
int query_state;

using namespace std;