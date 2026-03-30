class DataService {
	private array $data = [];
	public function __construct(string $filename) {
		$fh = fopen($filename, 'r');
		while (($row = fgetcsv($fh, 1024)) !== false) {
			$this->data[] = $row;
		}
		fclose($fh);
	}

	public function getData(): array {
		return $this->data;
	}
}

$dataService = new DataService('really-big-file.csv');
foreach ($dataService->getData() as $row) {
	// Do something with $row
}