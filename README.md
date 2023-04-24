# laravel-sqs-consumer

A custom SQS connector for Laravel (or Lumen) that supports many jobs e any message types based in library https://github.com/dusterio/laravel-plain-sqs.

But each job will be responsible for one queue sqs. All will be execute, if message in some queue exists it will be processed. 

## Dependencies

* PHP >= 7.1.3
* Laravel (or Lumen) >= 7.0

## Installation via Composer

To install simply run:

```
composer require renatomaldonado/laravel-sqs-consume
```

### Usage in Laravel 7

```php
// Add in your config/app.php

'providers' => [
    '...',
    'Renatomaldonado\LaravelSqsConsume\Provider\SQSQueueServiceProvider',
];
```

### Usage in Lumen 7

```php
// Add in your bootstrap/app.php
$app->register(Renatomaldonado\LaravelSqsConsume\Provider\SQSQueueServiceProvider::class);
```

## Configuration

```php
// Generate standard config file (Laravel only)
php artisan vendor:publish --provider="Renatomaldonado\LaravelSqsConsume\Provider\ConfigQueueServiceProvider" 

// In Lumen, create it manually (see example below) and register it in bootstrap/app.php
$app->configure('sqs-consumer');
```

Edit config/sqs-consumer.php to suit your needs. This config matches SQS queues with handler classes.

```php
return [

    /**
     * Queues array for execute in each job
     */
    'queues' => ['queue-sqs-name-example', 'queue-sqs-name-example-two'],

    'handlers' => [
        'queue-sqs-name-example' => 'your-job',
        'queue-sqs-name-example-two' => 'your-job-two',
    ],

    'default-handler' => 'job-default'
];
```

If queue is not found in 'handlers' array, SQS payload is passed to default handler.

Add sqs-consumer connection to your config/queue.php, eg:
```php
        ...
        'sqs-consumer' => [
            'driver' => 'sqs-consumer',
            'key'    => env('AWS_KEY', ''),
            'secret' => env('AWS_SECRET', ''),
            'prefix' => 'https://sqs.ea-northheast-1.amazonaws.com/123456/',
            'queue'  => 'queue-sqs-name-example',
            'region' => 'ea-northheast-1',
        ],
        ...
```

In your .env file, choose sqs-consumer as your new default queue connection:
```
QUEUE_CONNECTION=sqs-consumer
```
### Receiving from SQS

If a third-party application is creating custom-format JSON messages or any message, just add a handler in the config file and
implement a handler class as follows:

```php
use Illuminate\Contracts\Queue\Job as LaravelJob;

class ExempleJob extends Job
{
    protected $data;

    /**
     * @param LaravelJob $job
     * @param $data
     */
    public function handle(LaravelJob $job, $data)
    {
        // This is incoming JSON payload, already decoded to an array
        var_dump($data);

        // Raw JSON payload from SQS, if necessary
        var_dump($job->getRawBody());
    }
}

```