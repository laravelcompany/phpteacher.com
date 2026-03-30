phparch=# SELECT table_name, column_name, data_type
FROM information_schema.columns
WHERE table_name = 'users';
 table_name |    column_name    |     data_type
------------+-------------------+---------------------
 users      | id                | bigint
 users      | email_verified_at | timestamp without tz
 users      | created_at        | timestamp without tz
 users      | updated_at        | timestamp without tz
 users      | password          | character varying
 users      | name              | character varying
 users      | email             | character varying
 users      | remember_token    | character varying
(8 rows)