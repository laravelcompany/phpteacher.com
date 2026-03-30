$query = 'Henry David Thoreau';
$optParams = [
  'filter' => 'free-ebooks',
];
$results = $service->volumes->listVolumes(
        $query,
        $optParams
);

foreach ($results->getItems() as $item) {
  echo $item['volumeInfo']['title'], "<br /> \n";
}