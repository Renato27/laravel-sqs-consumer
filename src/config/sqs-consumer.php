<?php

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
