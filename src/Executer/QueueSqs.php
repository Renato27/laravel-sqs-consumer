<?php

namespace Renatomaldonado\LaravelSqsConsume\Executer;

use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\SqsQueue;

class QueueSqs extends SqsQueue
{
    /**
     * Per-connection consumer options: wait_time (long poll seconds), visibility_timeout (seconds a received
     * message stays hidden) and queues (overrides the global sqs-consumer.queues list).
     *
     * @var array
     */
    protected $consumerOptions = [];

    /**
     * @param array $options
     * @return $this
     */
    public function setConsumerOptions(array $options)
    {
        $this->consumerOptions = $options;

        return $this;
    }

    /**
     * @return array
     */
    public function getConsumerOptions()
    {
        return $this->consumerOptions;
    }

     /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queues = $this->consumerOptions['queues'] ?? $this->container['config']->get('sqs-consumer.queues');

        if(empty($queues)) return $this->executeQueue($queue);

        foreach ($queues as $queue) {
            $message = $this->executeQueue($queue);

            if(is_null($message)) continue;

            return $message;
        }
    }

    /**
     * @param string|array $payload
     * @param string $class
     * @return array
     */
    private function modifyPayload($payload, $class)
    {
        if (!is_array($payload)) $payload = json_decode($payload, true);

        $body = json_decode($payload['Body'], true);

        $body = [
            'job' => $class . '@handle',
            'data' => $body ?? $payload['Body'],
            'uuid' => $payload['MessageId']
        ];

        $payload['Body'] = json_encode($body);

        return $payload;
    }

    /**
     * Execute each queue
     *
     * @param string $queue
     * @return void
     */
    private function executeQueue(?string $queue = null)
    {
        $queue = $this->getQueue($queue);

        $response = $this->sqs->receiveMessage(array_filter([
            'QueueUrl' => $queue,
            'AttributeNames' => ['ApproximateReceiveCount'],
            'WaitTimeSeconds' => $this->consumerOptions['wait_time'] ?? null,
            'VisibilityTimeout' => $this->consumerOptions['visibility_timeout'] ?? null,
        ], fn ($value) => $value !== null));

        if (isset($response['Messages']) && count($response['Messages']) > 0) {
            $queueId = explode('/', $queue);
            $queueId = array_pop($queueId);

            $class = (array_key_exists($queueId, $this->container['config']->get('sqs-consumer.handlers')))
                ? $this->container['config']->get('sqs-consumer.handlers')[$queueId]
                : $this->container['config']->get('sqs-consumer.default-handler');

            $response = $this->modifyPayload($response['Messages'][0], $class);

            return new SqsJob($this->container, $this->sqs, $response, $this->connectionName, $queue);
        }

        return null;
    }
}
