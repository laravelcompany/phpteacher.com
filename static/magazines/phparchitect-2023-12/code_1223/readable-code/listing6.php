class Logger {
	public function log(string $message) {
		// Log message to a file or database
	}
}

// In different parts of your PHP application or projects
$logger = new Logger();

$logger->log("Error: Something went wrong.");