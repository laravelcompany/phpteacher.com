git clone [git@github.com](mailto:git@github.com):transistive/book-example.git
cd book-example

composer install # install PHP all libraries

docker-compose up -d # set up MariaDB and Neo4J
vendor/bin/phinx migrate # migrate the SQL schema
vendor/bin/phinx seed:run # generate a random dataset in MariaDB
php migrate_to_neo4j.php # migrate the data to Neo4J