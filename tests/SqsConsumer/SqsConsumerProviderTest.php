<?php

namespace SqsConsumer;

use Illuminate\Foundation\Testing\TestCase;
use Orchestra\Testbench\Concerns\CreatesApplication;
use Renatomaldonado\LaravelSqsConsume\Executer\Connector;

class SqsConsumerProviderTest extends TestCase
{
    use CreatesApplication;

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
            'Renatomaldonado\LaravelSqsConsume\Provider\QueueServiceProvider',
        ];
    }

    public function testWillRegisterQueueConnector()
    {
        $reflectionQueueManager = new \ReflectionClass($this->app['queue']);
        $reflectionQueueManagerGetConnectorMethod = $reflectionQueueManager->getMethod('getConnector');
        $reflectionQueueManagerGetConnectorMethod->setAccessible(true);
        $connector = $reflectionQueueManagerGetConnectorMethod->invoke(
            $this->app['queue'],
            'sqs-consumer'
        );
        
        $this->assertInstanceOf(Connector::class, $connector);
    }
}
