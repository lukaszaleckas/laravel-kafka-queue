<?php

namespace LaravelKafka\Tests\Integration\Contracts;

use LaravelKafka\Kafka\Consumer;
use LaravelKafka\Kafka\GlobalConfig;
use LaravelKafka\Kafka\Producer;
use Orchestra\Testbench\TestCase;
use Throwable;

abstract class AbstractIntegrationTestCase extends TestCase
{
    public const TEST_TOPIC = 'test_topic';

    /** @var Producer */
    protected Producer $producer;

    /** @var Consumer */
    protected Consumer $consumer;

    /**
     * @return void
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = new Producer($this->buildGlobalConfig(), 3 * 1000);
        $this->consumer = new Consumer(
            $this->buildGlobalConfig(),
            'test',
            3 * 1000,
            5 * 1000
        );

        $this->producer->produce(self::TEST_TOPIC, '', now()->timestamp);
        $this->producer->produce(self::TEST_TOPIC . GlobalConfig::DELAYED_QUEUE_POSTFIX, '', now()->timestamp);

        $this->cleanupTestTopics();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->producer->__destruct();

        parent::tearDown();
    }

    /**
     * @return GlobalConfig
     */
    private function buildGlobalConfig(): GlobalConfig
    {
        return new GlobalConfig('kafka', 9092);
    }

    /**
     * @return void
     */
    private function cleanupTestTopics(): void
    {
        while ($this->consumer->consume(self::TEST_TOPIC) != null) {
            $this->consumer->commitOffset();
        }
    }
}
