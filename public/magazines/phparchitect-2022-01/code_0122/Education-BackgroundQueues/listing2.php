use Pheanstalk\\Pheanstalk;

$pheanstalk = Pheanstalk::create('127.0.0.1');

// Queue a Job
$tube = $pheanstalk->useTube('reports');
$message = ['type' => 'analytics_summary', 'user' => 5];
$tube->put(json_encode($message));
