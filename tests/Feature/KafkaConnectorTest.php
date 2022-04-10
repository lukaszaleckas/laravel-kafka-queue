<?php

namespace LaravelKafka\Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use LaravelKafka\KafkaConnector;
use LaravelKafka\KafkaQueue;
use Orchestra\Testbench\TestCase;

class KafkaConnectorTest extends TestCase
{
    use WithFaker;

    /**
     * @return void
     */
    public function testCanGetKafkaQueue(): void
    {
        $config = [
            KafkaConnector::CONFIG_HOST             => $this->faker->ipv4,
            KafkaConnector::CONFIG_PORT             => $this->faker->numberBetween(1, 10000),
            KafkaConnector::CONFIG_TOPIC            => $this->faker->word,
            KafkaConnector::CONFIG_GROUP_NAME       => $this->faker->word,
            KafkaConnector::CONFIG_HEARTBEAT        => $this->faker->numberBetween(1, 10000),
            KafkaConnector::CONFIG_PRODUCER_TIMEOUT => $this->faker->numberBetween(1, 10000),
            KafkaConnector::CONFIG_CONSUMER_TIMEOUT => $this->faker->numberBetween(1, 10000),
        ];

        self::assertInstanceOf(
            KafkaQueue::class,
            (new KafkaConnector())->connect($config)
        );
    }
}
