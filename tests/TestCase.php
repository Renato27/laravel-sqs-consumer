<?php

namespace Renatomaldonado\LaravelSqsConsume\Tests;

use Aws\Sqs\SqsClient;
use Renatomaldonado\LaravelSqsConsume\Provider\LaravelSQSQueueServiceProvider;
use Renatomaldonado\LaravelSqsConsume\Provider\LumenSQSQueueServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $sqsClient;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->sqsClient = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['receiveMessage'])
            ->getMock();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('queue.connections.sqs-consumer', [
            'driver' => 'sqs-consumer',
            'key'    => env('AWS_ACCESS_KEY', 'your-public-key'),
            'secret' => env('AWS_SECRET_ACCESS_KEY', 'your-secret-key'),
            'queue'  => env('QUEUE_URL', 'your-queue-url'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1')
        ]);
        $app['config']->set('queue.default', 'sqs-consumer');
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelSQSQueueServiceProvider::class,
            LumenSQSQueueServiceProvider::class,
        ];
    }
}
