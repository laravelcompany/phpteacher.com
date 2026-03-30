use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Create a logger
$logger = new Logger('graphql');
$handler = new StreamHandler('file.log', Logger::DEBUG));
$logger->pushHandler($handler);

// Log something
$logger->info('Query executed successfully.');