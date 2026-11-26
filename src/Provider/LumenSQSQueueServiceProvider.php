<?php

namespace Renatomaldonado\LaravelSqsConsume\Provider;

use Renatomaldonado\LaravelSqsConsume\Executer\Connector;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class LumenSQSQueueServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::after(fn (JobProcessed $event) => LaravelSQSQueueServiceProvider::deleteProcessedJob($event));
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->resolving('queue', function ($manager) {
            $manager->extend('sqs-consumer', function () {
                return new Connector();
            });
        });
    }
}
