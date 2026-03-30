psql postgres
psql (14.8 (Homebrew))
Type "help" for help.

postgres=# CREATE ROLE my_super SUPERUSER LOGIN \
      PASSWORD 'secret';
CREATE ROLE
postgres=# \\du
                         List of roles
 Role name |               Attributes     | Member of
-----------+------------------------------+-----------
 halo      | Superuser, Create role,      | {}
           |     Create, DB, Replication  |
 my_super  | Superuser                    | {}

postgres=#