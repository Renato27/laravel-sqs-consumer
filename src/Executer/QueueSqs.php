<?php

namespace Renatomaldonado\LaravelSqsConsume\Executer;

use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\SqsQueue;

class QueueSqs extends SqsQueue
{
     /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queues = $this->container['config']->get('sqs-consumer.queues');

        if(empty($queues)) return $this->executeQueue($queue);

        foreach ($queues as $queue) {
            return $this->executeQueue($queue);
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
            'data' => $body,
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
    private function executeQueue(string $queue)
    {
        $queue = $this->getQueue($queue);

        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue,
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (isset($response['Messages']) && count($response['Messages']) > 0) {
            $queueId = explode('/', $queue);
            $queueId = array_pop($queueId);

            $class = (array_key_exists($queueId, $this->container['config']->get('sqs-consumer.handlers')))
                ? $this->container['config']->get('sqs-consumer.handlers')[$queueId]
                : $this->container['config']->get('sqs-consumer.default-handler');

            $response = $this->modifyPayload($response['Messages'][0], $class);

            if (preg_match('/(5\.[4-8]\..*)|(6\.[0-9]*\..*)|(7\.[0-9]*\..*)|(8\.[0-9]*\..*)|(9\.[0-9]*\..*)/', $this->container->version())) {
                return new SqsJob($this->container, $this->sqs, $response, $this->connectionName, $queue);
            }

            return new SqsJob($this->container, $this->sqs, $response, $this->connectionName, $queue);
        }
    }
}
