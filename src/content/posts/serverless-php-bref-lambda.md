---
title: "Serverless PHP with Bref: AWS Lambda Deployment Guide"
description: "Deploy PHP applications on AWS Lambda using Bref. Learn serverless PHP setup, configuration, deployment, and cost optimization for modern cloud applications."
pubDate: "2023-07-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Serverless", "Bref", "AWS Lambda", "PHP", "Cloud", "Serverless Framework", "FaaS", "AWS"]
---

Serverless computing is transforming how PHP applications are deployed. Instead of managing servers, you upload your code and the cloud provider handles scaling, availability, and infrastructure. For PHP developers, Bref makes AWS Lambda deployment practical and straightforward.

Serverless PHP means no more SSHing into servers, no more configuring Nginx or Apache, and no more paying for idle capacity. Your code runs on demand and you pay only for execution time. The provider decides how to deploy your code, the available environments, and even how to scale your application. As a developer, you focus on writing code, not managing infrastructure.

## What You'll Learn

- Understanding serverless computing for PHP
- Setting up Bref and the Serverless Framework
- Configuring AWS Lambda for PHP applications
- Deploying function-based and application-based PHP services
- Cost comparison: serverless vs traditional hosting
- Environment configuration and secrets management
- Real-world serverless PHP patterns

## What Is Serverless Computing

Serverless computing means the cloud provider manages the infrastructure. You provide code, and the provider decides how to run it, scale it, and make it available.

For PHP, serverless has been challenging because most platforms assumed PHP runs on a persistent web server. Bref solves this by providing a PHP runtime for AWS Lambda, complete with common extensions.

```php
<?php

// A basic serverless "function"
return function ($request) {
    return [
        'status' => 200,
        'body' => [
            'message' => 'Hello World',
        ],
    ];
};
```

## Functions vs Applications

Serverless PHP supports two deployment patterns:

**Functions**: A single PHP function that receives a request and returns a response. Suitable for simple API endpoints, webhooks, and event processors.

**Applications**: Full PHP applications using frameworks like Laravel or Symfony. Bref handles the translation between API Gateway and PSR-7 requests.

```php
<?php

// Application pattern with Slim
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function (
    RequestInterface $request,
    ResponseInterface $response,
) {
    $response->getBody()->write(
        "Hello from serverless PHP!"
    );
    return $response;
});

$app->get('/hello/{name}', function (
    RequestInterface $request,
    ResponseInterface $response,
    array $args,
) {
    $name = $args['name'] ?? 'world';
    $response->getBody()->write("Hello {$name}");
    return $response;
});

$app->run();
```

## Why Deploy Serverless

### Cost Savings

Traditional VPS hosting charges $4-$5 per month whether you have traffic or not. Serverless platforms charge only for execution time.

DigitalOcean Functions charges $0.0000185 per gibibit-second. That same $4 handles roughly 216,000 requests at 100ms each. Most platforms include a free tier. DigitalOcean gives 90,000 gibibyte-seconds free, covering roughly 1 million requests.

### Automatic Scaling

Lambda scales from zero to thousands of concurrent executions instantly based on incoming traffic. During a traffic spike, Lambda creates additional container instances to handle the load. When traffic drops, it scales back down. You never over-provision or under-provision capacity.

### High Availability

AWS Lambda runs across multiple Availability Zones within each region. If one zone experiences an outage, Lambda automatically routes traffic to healthy zones. Your application remains available without any manual intervention.

### Operational Simplicity

No server patches, no SSH key management, no Nginx configuration, no PHP-FPM tuning. Upload your code and the provider handles everything.

### Automatic Scaling

Lambda scales from zero to thousands of concurrent executions instantly. No auto-scaling groups, no load balancer configuration, no capacity planning.

## Tooling Setup

### Serverless Framework

The Serverless Framework CLI orchestrates deployments. Install it via npm:

```bash
npm install -g serverless
```

### Bref

Bref provides PHP runtimes for Lambda and integrates with the Serverless Framework.

```bash
composer require bref/bref
```

### Configuration

Create `serverless.yaml`:

```yaml
service: app

provider:
  name: aws
  region: us-east-1

plugins:
  - ./vendor/bref/bref

functions:
  api:
    handler: index.php
    description: ''
    runtime: php-82-fpm
    timeout: 28
    events:
      - httpApi: '*'

package:
  patterns:
    - '!node_modules/**'
    - '!tests/**'
```

## Deploying

```bash
serverless deploy
```

The first deployment takes a few minutes. Subsequent deployments are faster. The output includes your API endpoint URL.

```bash
$ serverless deploy
...
endpoints:
  ANY https://xxxxxxxxxx.execute-api.us-east-1.amazonaws.com
functions:
  api: app-dev-api
```

## Environment Configuration

Store environment-specific values in `.env` files or Serverless Framework parameters.

```yaml
# serverless.yaml
provider:
  environment:
    APP_ENV: ${env:APP_ENV, 'production'}
    DATABASE_URL: ${env:DATABASE_URL}
```

## Debugging and Logging

Serverless PHP requires a different approach to debugging. You cannot SSH into a server to inspect logs or run commands.

### CloudWatch Logs

Bref automatically sends PHP errors, warnings, and stdout/stderr output to AWS CloudWatch Logs. Each Lambda invocation creates a log stream.

```php
<?php

error_log('Processing order: ' . $orderId);
// This appears in CloudWatch Logs automatically
```

Use structured logging for better searchability:

```php
<?php

function logInfo(string $message, array $context = []): void
{
    error_log(json_encode([
        'level' => 'info',
        'message' => $message,
        'context' => $context,
        'timestamp' => (new DateTimeImmutable())
            ->format(DateTimeInterface::ATOM),
    ]));
}

logInfo('Order processed', ['order_id' => $orderId, 'amount' => 99.95]);
```

### Monitoring with CloudWatch Dashboards

After deploying, monitor your Lambda functions through CloudWatch. Create a dashboard that displays invocation count, error rate, duration, and throttles. Set alarms for error rate spikes and duration anomalies. Bref automatically forwards PHP warnings and errors to CloudWatch Logs, so you can search for specific error patterns using CloudWatch Logs Insights.

### Local Development with Bref

Bref provides a local development tool that simulates the Lambda environment:

```bash
composer require --dev bref/bref
vendor/bin/bref local
```

This starts a local HTTP server that handles requests the same way Lambda does, enabling local debugging with Xdebug.

## Database Connections

Serverless PHP applications need database connection management. Traditional persistent connections do not work because Lambda containers are recycled.

```php
<?php

// Use PDO with short timeouts
$pdo = new PDO(
    'mysql:host=...;dbname=...',
    'username',
    'password',
    [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);
```

For better performance, use a serverless-aware database like Aurora Serverless or connect through a connection pooler like RDS Proxy.

### CORS Configuration for Serverless APIs

When deploying serverless PHP APIs, configure CORS in the API Gateway or handle it in your application code:

```php
<?php

return function ($event) {
    $headers = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
    ];

    // Handle preflight
    if ($event['requestContext']['http']['method'] === 'OPTIONS') {
        return [
            'statusCode' => 200,
            'headers' => $headers,
            'body' => '',
        ];
    }

    return [
        'statusCode' => 200,
        'headers' => $headers,
        'body' => json_encode(['message' => 'Hello from serverless PHP!']),
    ];
};
```

Alternatively, configure CORS at the API Gateway level in `serverless.yaml` using the `httpApi.cors` setting.

## Cost Monitoring

Serverless costs can surprise you if traffic spikes unexpectedly.

```yaml
# serverless.yaml
provider:
  usage:
    - metric: RequestCount
      threshold: 100000
      notification:
        - email: ops@example.com
```

## Real-World Use Cases

### API Backends

REST and GraphQL APIs are natural fits for serverless. Each endpoint is a Lambda function. Automatic scaling handles traffic spikes.

### Webhook Handlers

Stripe webhooks, GitHub hooks, and Slack integrations benefit from serverless. They run infrequently and have unpredictable traffic patterns.

### Image Processing

Upload images to S3 triggers a Lambda function that resizes and optimizes. You pay only for processing time.

```php
<?php

use Aws\S3\S3Client;

function handleImageUpload(array $event): array
{
    $bucket = $event['Records'][0]['s3']['bucket']['name'];
    $key = $event['Records'][0]['s3']['object']['key'];

    $s3 = new S3Client(['version' => 'latest', 'region' => 'us-east-1']);
    $image = $s3->getObject(['Bucket' => $bucket, 'Key' => $key]);
    $source = imagecreatefromstring($image['Body']->getContents());

    $thumb = imagescale($source, 300, 300);
    ob_start();
    imagejpeg($thumb);
    $thumbData = ob_get_clean();

    $s3->putObject([
        'Bucket' => $bucket,
        'Key' => 'thumbnails/' . basename($key),
        'Body' => $thumbData,
        'ContentType' => 'image/jpeg',
    ]);

    imagedestroy($source);
    imagedestroy($thumb);

    return ['status' => 'success'];
}
```

### Scheduled Tasks with CloudWatch Events

Cron jobs run as scheduled Lambda functions. Define them in serverless.yaml:

```yaml
functions:
  nightlyCleanup:
    handler: cleanup.php
    runtime: php-82
    events:
      - schedule: cron(0 2 * * ? *)  # Run at 2 AM daily
  hourlyDigest:
    handler: digest.php
    runtime: php-82
    events:
      - schedule: rate(1 hour)
```

The PHP handler runs on schedule, performs the task, and exits. You are charged only for the execution duration. No server costs for idle time between runs.

```php
<?php

// cleanup.php
return function ($event) {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'],
    );

    // Delete records older than 90 days
    $stmt = $pdo->prepare(
        "DELETE FROM audit_logs WHERE created_at < NOW() - INTERVAL 90 DAY"
    );
    $stmt->execute();

    return [
        'status' => 'success',
        'deleted' => $stmt->rowCount(),
    ];
};
```

### Form Processing

Contact forms, survey submissions, and lead capture forms process data without maintaining a server.

## Best Practices

- **Optimize cold starts** - Lambda containers freeze after inactivity. The first request after idle time is slower. Keep functions warm with scheduled pings if needed.
- **Keep deployments small** - Use the `package.patterns` configuration to exclude unnecessary files. Smaller packages deploy faster.
- **Set appropriate timeouts** - API Gateway has a 29-second timeout. Lambda supports up to 15 minutes for asynchronous functions.
- **Use environment variables for secrets** - Never hardcode credentials. Use Lambda environment variables or AWS Secrets Manager.
- **Monitor costs** - Set CloudWatch alarms for request count and duration anomalies.

## Common Mistakes to Avoid

- **Using persistent connections** - Lambda containers are recycled. Connection pools do not persist across invocations.
- **Ignoring cold starts** - Cold starts add 500ms-2s latency. Warm functions with scheduled CloudWatch Events.
- **Uploading large packages** - Include only production dependencies. Exclude tests, node_modules, and development files.
- **Hardcoding environment variables** - Use environment-specific configuration files or parameter store.
- **Forgetting IAM permissions** - Lambda functions need IAM roles with appropriate permissions for S3, DynamoDB, etc.

## Frequently Asked Questions

### Does Bref support Laravel and Symfony?

Yes. Bref provides runtime configurations for both Laravel and Symfony. Follow the framework-specific guides in the Bref documentation.

### How does serverless PHP handle file uploads?

Store uploads in S3. Lambda's /tmp directory is limited to 512MB and is not shared between invocations.

### What PHP extensions are available in Bref?

Bref includes common extensions: PDO, MySQL, PostgreSQL, Redis, imagick, gd, mbstring, and more. Custom extensions can be compiled as Lambda layers.

### Can I run PHP CLI commands on Lambda?

Yes. Define CLI functions in serverless.yaml with a `schedule` event for cron-like execution.

### How do I handle database migrations?

Run migrations as a separate CLI function. Execute `serverless invoke --function migrate` after deployment.

### What is the maximum Lambda execution time?

15 minutes for asynchronous functions. API Gateway functions have a 29-second timeout.

### How does serverless PHP compare to traditional hosting?

Serverless excels for variable traffic patterns, reduces operational overhead, and can be cheaper for low-to-medium traffic sites. Traditional hosting is simpler to set up and predict costs for steady traffic.

## Conclusion

Serverless PHP with Bref opens new deployment possibilities for PHP developers. The combination of Lambda's automatic scaling, pay-per-execution pricing, and Bref's PHP runtime makes it practical and cost-effective.

Start with a simple API endpoint. Add more functions as you become comfortable with the deployment workflow. Monitor costs and cold start latency. Adjust configuration as your traffic patterns evolve.

The serverless approach lets you focus on writing PHP code instead of managing infrastructure. Give it a try with your next project.
