<?php

namespace Renatomaldonado\LaravelSqsConsume\Provider;

use Illuminate\Support\ServiceProvider;

class ConfigQueueServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'config/sqs-consumer.php' => config_path('sqs-consumer.php')
        ], 'config');
    }

    /**
     * @return void
     */
    public function register()
    {
        //
    }
}
