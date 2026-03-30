use Pheanstalk\\Pheanstalk;

$pheanstalk = Pheanstalk::create('127.0.0.1');
$pheanstalk->watch('reports');
while (true) {
    $job = $pheanstalk->reserve();

    try {
        $jobPayload = json_decode($job->getData(), true);
        // Do some work
        $pheanstalk->delete($job);
    } catch(\\Exception $e) {
        $pheanstalk->release($job);
    }
}
