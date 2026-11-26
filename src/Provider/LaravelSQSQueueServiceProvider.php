<?php

namespace Renatomaldonado\LaravelSqsConsume\Provider;

use Renatomaldonado\LaravelSqsConsume\Executer\Connector;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class LaravelSQSQueueServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/sqs-consumer.php' => config_path('sqs-consumer.php')
        ], 'config');

        Queue::after(fn (JobProcessed $event) => static::deleteProcessedJob($event));
    }

    /**
     * Delete a raw SQS message once its handler returned, unless the handler released it back to the
     * queue (or already deleted it): a released message must come back after its delay. Only jobs of
     * connections using the `sqs-consumer` driver are touched.
     *
     * @param \Illuminate\Queue\Events\JobProcessed $event
     * @return void
     */
    public static function deleteProcessedJob(JobProcessed $event)
    {
        if (config("queue.connections.{$event->connectionName}.driver") !== 'sqs-consumer') {
            return;
        }

        if (! $event->job->isReleased() && ! $event->job->isDeleted()) {
            $event->job->delete();
        }
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
