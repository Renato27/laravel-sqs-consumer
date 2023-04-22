<?php

namespace SqsConsumer;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use Tests\TestCase;
use Renatomaldonado\LaravelSqsConsume\Provider\QueueServiceProvider;
use Renatomaldonado\LaravelSqsConsume\Executer\Connector;

class SqsConsumerProviderTest extends TestCase
{
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();
        Facade::clearResolvedInstances();
        $this->getEnvironmentSetUp();
        $app = new Application();
        $this->provider = new QueueServiceProvider($app);
    }

    protected function getEnvironmentSetUp()
    {
        Config::set('queue.connections.sqs-consumer', [
            'driver' => 'sqs-consumer',
            'key'    => env('AWS_ACCESS_KEY', 'your-public-key'),
            'secret' => env('AWS_SECRET_ACCESS_KEY', 'your-secret-key'),
            'queue'  => env('QUEUE_URL', 'your-queue-url'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1')
        ]);
        Config::set('queue.default', 'sqs-consumer');
    }

    public function testWillRegisterQueueConnector()
    {
        $reflectionQueueManager = new \ReflectionClass(Config::get('queue'));
        $reflectionQueueManagerGetConnectorMethod = $reflectionQueueManager->getMethod('getConnector');
        $reflectionQueueManagerGetConnectorMethod->setAccessible(true);

        $connector = $reflectionQueueManagerGetConnectorMethod->invoke(
            Config::get('queue'),
            'sqs-consumer'
        );
        
        $this->assertInstanceOf(Connector::class, $connector);
    }
}
