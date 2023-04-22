<?php

namespace Renatomaldonado\LaravelSqsConsume\Executer;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\Arr;

class Connector extends SqsConnector
{
     /**
     * Gera a conexão com a nova fila
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if (isset($config['key']) && isset($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        $queue = new Queue(
            new SqsClient($config),
            $config['queue'],
            Arr::get($config, 'prefix', '')
        );

        return $queue;
    }
}
