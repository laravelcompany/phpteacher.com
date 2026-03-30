register_shutdown_function(function () {
    global $argv;
    echo 'Restarting the worker' . PHP_EOL;
    pcntl_exec($_SERVER['_'], $argv);
});

use Pheanstalk\\Pheanstalk;

$pheanstalk = Pheanstalk::create('127.0.0.1');
$pheanstalk->watch('reports');
for ($i = 0; $i < 100; $i++) {
    $job = $pheanstalk->reserve();
    try {
        $jobPayload = json_decode($job->getData(), true);
        // Do some work
        $pheanstalk->delete($job);
    } catch(\\Exception $e) {
        $pheanstalk->release($job);
    }
}
