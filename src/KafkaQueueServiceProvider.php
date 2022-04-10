<?php

namespace LaravelKafka;

use Illuminate\Support\ServiceProvider;

class KafkaQueueServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        $this->app['queue']->addConnector('kafka', function () {
            return new KafkaConnector();
        });
    }
}
