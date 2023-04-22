<?php

return [

    /**
     * Queues array for execute in each job
     */
    'queues' => ['queue-sqs-name-example'],

    'handlers' => [
        'queue-sqs-name-example' => 'your-job',
    ],

    'default-handler' => 'job-default'
];
