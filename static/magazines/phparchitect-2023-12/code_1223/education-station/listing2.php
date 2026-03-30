class DataService {
	public function __construct(
        private string $filename
    ) {
	}

	public function getData(): \Generator {
		$fh = fopen($this->filename, 'r');
		while (($row = fgetcsv($fh, 1024)) !== false) {
			yield $row;
		}
		fclose($fh);
	}
}

$dataService = new DataService('really-big-file.csv');
foreach ($dataService->getData() as $row) {
	// Do something with $row
}