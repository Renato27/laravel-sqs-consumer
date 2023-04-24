<?php

namespace SqsConsumer;

use Aws\Sqs\SqsClient;
use Illuminate\Foundation\Testing\TestCase;
use Orchestra\Testbench\Concerns\CreatesApplication;
use Renatomaldonado\LaravelSqsConsume\Executer\Queue;

class SqsConsumerTest extends TestCase
{
    use CreatesApplication;

    private $sqsClient;

    protected function setUp():void
    {
        $this->sqsClient = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['receiveMessage'])
            ->getMock();
        
    }

    public function testWillPopMessageOffQueue()
    {
        $body = json_encode(
            [
                'MessageId' => 'bc065409-fe1b-59c2-b17c-0e056cd19d5d',
                'TopicArn' => 'arn:aws:sqs',
                'Subject' => 'Subject#action',
                'Message' => '',
            ]
        );

        $message = [
            'Body' => $body,
        ];

        $this->sqsClient->method('receiveMessage')->willReturn([
            'Messages' => [
                $message,
            ],
        ]);

        $queue = new Queue($this->sqsClient, 'default_queue');
        
        $queue->setContainer($this->createMock(\Illuminate\Container\Container::class));

        $job = $queue->pop();
        $expectedRawBody = [
            'uuid' =>  'bc065409-fe1b-59c2-b17c-0e056cd19d5d',
            'displayName' => '\\Job',
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'commandName' => '\\Job',
                'command' => 'N;',
            ],
        ];

        $this->assertInstanceOf(Queue::class, $job);
        $this->assertEquals(json_encode($expectedRawBody), $job->getRawBody());
    }
}
