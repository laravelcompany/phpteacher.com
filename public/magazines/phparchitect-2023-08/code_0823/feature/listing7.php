$volumes = $service->volumes->list(
    query: 'Henry David Thoreau',
    parameters: [
        'filter' => 'free-ebooks',
    ]
);

foreach ($volumes as $volume) {
  // At this point we have a Volume class and
  // can call $volume->title;
}