//initialize database connection
mysql_init(&mysql);

// the three zeros are:
// Which port to connect to, which socket to connect to
// and what client flags to use.  unless you're changing
// the defaults you only need to put 0 here
connection = mysql_real_connect(
        &mysql, HOST, USER, PASSWD, DB, 0, 0, 0);

// Report error if failed to connect to database
if (connection == NULL)
{
    cout << mysql_error(&mysql) << endl;
    return 1;
}