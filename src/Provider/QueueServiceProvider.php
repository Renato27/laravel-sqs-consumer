<?php

namespace Renatomaldonado\LaravelSqsConsume\Provider;

use Renatomaldonado\LaravelSqsConsume\Executer\Connector;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::after(function (JobProcessed $event) {
            $event->job->delete();
        });
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app['queue']->addConnector('sqs-consumer', function (){
            return new Connector();
        });
    }
}
