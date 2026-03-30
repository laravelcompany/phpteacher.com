phparch=# \\dt
                List of relations
 Schema |          Name          | Type  | Owner
--------+------------------------+-------+-------
 public | failed_jobs            | table | halo
 public | migrations             | table | halo
 public | password_reset_tokens  | table | halo
 public | personal_access_tokens | table | halo
 public | users                  | table | halo
(5 rows)

phparch=# select * from users;
 id | name | email | email_verified_at | password |
 remember_token | created_at | updated_at
----+------+-------+-------------------+----------+
----------------+------------+------------
(0 rows)

phparch=#