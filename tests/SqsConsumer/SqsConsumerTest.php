<?php

namespace SqsConsumer;

use Renatomaldonado\LaravelSqsConsume\Executer\QueueSqs;
use Renatomaldonado\LaravelSqsConsume\Tests\TestCase;

class SqsConsumerTest extends TestCase
{

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

        $queue = new QueueSqs($this->sqsClient, 'default_queue');
        
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

        $this->assertInstanceOf(QueueSqs::class, $job);
        $this->assertEquals(json_encode($expectedRawBody), $job->getRawBody());
    }
}
