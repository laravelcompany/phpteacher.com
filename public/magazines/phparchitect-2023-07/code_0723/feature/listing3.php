try {
    $contents = $s3Client->listObjects([
        'Bucket' => 'acme-bucket',
        "Prefix" => "path/to/", // optional
    ]);
    echo "Bucket contents: \n";
    foreach ($contents['Contents'] as $content) {
        echo $content['Key'] . "\n";
    }
} catch (Exception $e) {
    // Handle failed listing of objects.
    echo('Error: ' . $e->getMessage() . PHP_EOL);
}