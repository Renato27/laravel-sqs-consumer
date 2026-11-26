<?php

namespace SqsConsumer;

use Illuminate\Container\Container;
use Renatomaldonado\LaravelSqsConsume\Executer\QueueSqs;
use Renatomaldonado\LaravelSqsConsume\Tests\TestCase;

class SqsConsumerTest extends TestCase
{
    private function message(string $messageId = 'bc065409-fe1b-59c2-b17c-0e056cd19d5d'): array
    {
        return [
            'Body' => json_encode([
                'MessageId' => $messageId,
                'TopicArn' => 'arn:aws:sqs',
                'Subject' => 'Subject#action',
                'Message' => '',
            ]),
            'MessageId' => $messageId,
        ];
    }

    private function container(array $config): Container
    {
        $container = new Container();
        $container->instance('config', new \Illuminate\Config\Repository($config));

        return $container;
    }

    public function testWillPopMessageOffQueue()
    {
        $message = $this->message();

        $this->sqsClient->shouldReceive('receiveMessage')->once()->andReturn([
            'Messages' => [$message],
        ]);

        $queue = new QueueSqs($this->sqsClient, 'default_queue');
        $queue->setContainer($this->container([
            'sqs-consumer' => [
                'queues' => [],
                'handlers' => [],
                'default-handler' => 'job-default',
            ],
        ]));

        $job = $queue->pop();

        $this->assertNotNull($job);
        $rawBody = json_decode($job->getRawBody(), true);
        $this->assertSame('bc065409-fe1b-59c2-b17c-0e056cd19d5d', $rawBody['uuid']);
        $this->assertSame('job-default@handle', $rawBody['job']);
    }

    public function testReturnsNullWhenQueueHasNoMessages()
    {
        $this->sqsClient->shouldReceive('receiveMessage')->once()->andReturn([
            'Messages' => [],
        ]);

        $queue = new QueueSqs($this->sqsClient, 'default_queue');
        $queue->setContainer($this->container([
            'sqs-consumer' => [
                'queues' => [],
                'handlers' => [],
                'default-handler' => 'job-default',
            ],
        ]));

        $this->assertNull($queue->pop());
    }

    public function testIteratesConfiguredQueuesUntilMessageIsFound()
    {
        $message = $this->message();

        $this->sqsClient->shouldReceive('receiveMessage')
            ->with(['QueueUrl' => '/queue-one', 'AttributeNames' => ['ApproximateReceiveCount']])
            ->once()
            ->andReturn(['Messages' => []]);

        $this->sqsClient->shouldReceive('receiveMessage')
            ->with(['QueueUrl' => '/queue-two', 'AttributeNames' => ['ApproximateReceiveCount']])
            ->once()
            ->andReturn(['Messages' => [$message]]);

        $queue = new QueueSqs($this->sqsClient, 'default_queue');
        $queue->setContainer($this->container([
            'sqs-consumer' => [
                'queues' => ['queue-one', 'queue-two'],
                'handlers' => ['queue-two' => 'queue-two-job'],
                'default-handler' => 'job-default',
            ],
        ]));

        $job = $queue->pop();

        $this->assertNotNull($job);
        $rawBody = json_decode($job->getRawBody(), true);
        $this->assertSame('queue-two-job@handle', $rawBody['job']);
    }

    public function testReturnsNullWhenNoConfiguredQueueHasMessages()
    {
        $this->sqsClient->shouldReceive('receiveMessage')
            ->twice()
            ->andReturn(['Messages' => []]);

        $queue = new QueueSqs($this->sqsClient, 'default_queue');
        $queue->setContainer($this->container([
            'sqs-consumer' => [
                'queues' => ['queue-one', 'queue-two'],
                'handlers' => [],
                'default-handler' => 'job-default',
            ],
        ]));

        $this->assertNull($queue->pop());
    }

    public function testFallsBackToDefaultHandlerForUnmappedQueue()
    {
        $message = $this->message();

        $this->sqsClient->shouldReceive('receiveMessage')->once()->andReturn([
            'Messages' => [$message],
        ]);

        $queue = new QueueSqs($this->sqsClient, 'some-unmapped-queue');
        $queue->setContainer($this->container([
            'sqs-consumer' => [
                'queues' => [],
                'handlers' => ['other-queue' => 'other-job'],
                'default-handler' => 'job-default',
            ],
        ]));

        $job = $queue->pop();
        $rawBody = json_decode($job->getRawBody(), true);

        $this->assertSame('job-default@handle', $rawBody['job']);
    }

    public function testLongPollsAndHidesTheMessageWhenConfigured()
    {
        $this->sqsClient->shouldReceive('receiveMessage')
            ->with([
                'QueueUrl' => '/default_queue',
                'AttributeNames' => ['ApproximateReceiveCount'],
                'WaitTimeSeconds' => 20,
                'VisibilityTimeout' => 900,
            ])
            ->once()
            ->andReturn(['Messages' => []]);

        $queue = new QueueSqs($this->sqsClient, 'default_queue');
        $queue->setConsumerOptions(['wait_time' => 20, 'visibility_timeout' => 900]);
        $queue->setContainer($this->container([
            'sqs-consumer' => ['queues' => [], 'handlers' => [], 'default-handler' => 'job-default'],
        ]));

        $this->assertNull($queue->pop());
    }

    public function testConnectionQueuesOverrideTheGlobalList()
    {
        $this->sqsClient->shouldReceive('receiveMessage')
            ->with(['QueueUrl' => '/own-queue', 'AttributeNames' => ['ApproximateReceiveCount']])
            ->once()
            ->andReturn(['Messages' => [$this->message()]]);

        $queue = new QueueSqs($this->sqsClient, 'default_queue');
        $queue->setConsumerOptions(['queues' => ['own-queue']]);
        $queue->setContainer($this->container([
            'sqs-consumer' => [
                'queues' => ['global-queue'],
                'handlers' => ['own-queue' => 'own-job'],
                'default-handler' => 'job-default',
            ],
        ]));

        $rawBody = json_decode($queue->pop()->getRawBody(), true);

        $this->assertSame('own-job@handle', $rawBody['job']);
    }

    public function testConnectorPassesTheConsumerOptions()
    {
        $queue = (new \Renatomaldonado\LaravelSqsConsume\Executer\Connector())->connect([
            'key' => 'k', 'secret' => 's', 'region' => 'eu-west-1', 'queue' => 'q',
            'wait_time' => 20, 'visibility_timeout' => 900, 'queues' => ['a', 'b'],
        ]);

        $this->assertSame(['wait_time' => 20, 'visibility_timeout' => 900, 'queues' => ['a', 'b']], $queue->getConsumerOptions());
    }
}
