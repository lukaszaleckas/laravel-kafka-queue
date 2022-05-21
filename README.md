# Laravel Kafka Queue Driver

Laravel Kafka queue driver with support for delayed jobs.

## Installation

1. Run:

```
composer require lukaszaleckas/laravel-kafka-queue
```

Service provider should be automatically registered, if not add

```php
LaravelKafka\KafkaQueueServiceProvider::class
```

to application's your `app.php`.

2. Add Kafka's connection to your `queue.php` config: 

```php
'kafka'      => [
    'driver'             => 'kafka',
    'host'               => 'your_host_here',
    'port'               => 9092,
    'queue'              => 'default_queue_name',
    'heartbeat'          => 5 * 1000, //Heartbeat in milliseconds
    'group_name'         => 'group_name',
    'producer_timeout'   => 3 * 1000, //Producer timeout in milliseconds
    'consumer_timeout'   => 3 * 1000, //Consumer timeout in milliseconds
]
```