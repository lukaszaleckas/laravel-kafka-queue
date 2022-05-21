# Laravel Kafka Queue Driver

Laravel Kafka queue driver with support for delayed jobs.

## Installation

Just run:

```
composer require lukaszaleckas/laravel-kafka-queue
```

Service provider should be automatically registered, if not add

```php
LaravelKafka\KafkaQueueServiceProvider::class
```

to application's your `app.php`.