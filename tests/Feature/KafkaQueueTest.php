<?php

namespace LaravelKafka\Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use LaravelKafka\Kafka\Consumer;
use LaravelKafka\Kafka\GlobalConfig;
use LaravelKafka\Kafka\Producer;
use LaravelKafka\KafkaJob;
use LaravelKafka\KafkaQueue;
use Mockery;
use Orchestra\Testbench\TestCase;
use Throwable;
use RdKafka\Message;

class KafkaQueueTest extends TestCase
{
    use WithFaker;

    public const DEFAULT_QUEUE = 'test';

    /** @var KafkaQueue */
    private KafkaQueue $queue;

    /** @var mixed */
    private $producer;

    /** @var mixed */
    private $consumer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = Mockery::mock(Producer::class);
        $this->consumer = Mockery::mock(Consumer::class);
        $this->queue    = new KafkaQueue($this->producer, $this->consumer, self::DEFAULT_QUEUE);
        $this->queue->setContainer($this->app);
    }

    /**
     * @return void
     */
    public function testCanGetSize(): void
    {
        self::assertEquals(0, $this->queue->size($this->faker->uuid));
    }

    /**
     * @dataProvider queueTestDataProvider
     *
     * @param string|null $queue
     * @return void
     * @throws Throwable
     */
    public function testCanPushToQueue(?string $queue): void
    {
        $this->producer->shouldReceive('produce')->once()->with(
            $queue ?? self::DEFAULT_QUEUE,
            Mockery::any(),
            null
        );

        $result = $this->queue->push(
            function () {
            },
            '',
            $queue
        );

        self::assertTrue(Str::isUuid($result));
    }

    /**
     * @dataProvider queueTestDataProvider
     *
     * @param string|null $queue
     * @return void
     * @throws Throwable
     */
    public function testCanPushToDelayedQueue(?string $queue): void
    {
        $delay = $this->faker->numberBetween();

        $this->producer->shouldReceive('produce')->once()->with(
            ($queue ?? self::DEFAULT_QUEUE) . GlobalConfig::DELAYED_QUEUE_POSTFIX,
            Mockery::any(),
            now()->addRealSeconds($delay)->timestamp
        );

        $result = $this->queue->later(
            $delay,
            function () {
            },
            '',
            $queue
        );

        self::assertTrue(Str::isUuid($result));
    }

    /**
     * @dataProvider queueTestDataProvider
     *
     * @param string|null $queue
     * @return void
     * @throws Throwable
     */
    public function testCanPopNextJob(?string $queue): void
    {
        $jobId   = $this->faker->uuid;
        $message = $this->mockKafkaMessage(null, $queue, $jobId);

        $this->consumer->shouldReceive('consume')->once()->with(
            $queue ?? self::DEFAULT_QUEUE
        )->andReturn($message);
        $this->consumer->shouldReceive('commitOffset')->once();

        $result = $this->queue->pop($queue);

        self::assertInstanceOf(KafkaJob::class, $result);
        self::assertEquals($message, $result->getMessage());
        self::assertEquals($jobId, $result->getJobId());
        self::assertEquals(1, $result->attempts());
    }

    /**
     * @return array
     */
    public function queueTestDataProvider(): array
    {
        $this->setUp();

        return [
            [null],
            [$this->faker->uuid]
        ];
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testReturnsNullIfThereAreNoJobs(): void
    {
        $this->consumer->shouldReceive('consume')->once()->andReturnNull();
        $this->consumer->shouldReceive('commitOffset')->never();

        self::assertNull($this->queue->pop());
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testReQueuesDelayedJob(): void
    {
        $delayedMessage = $this->mockKafkaMessage(now()->addHour()->getTimestampMs());
        $nextMessage    = $this->mockKafkaMessage();

        $this->consumer->shouldReceive('consume')->andReturn($delayedMessage, $nextMessage);
        $this->producer->shouldReceive('produce')->once()->withArgs(
            function (string $queue, string $payload, int $timestamp, callable $callback) use ($delayedMessage) {
                $callback();

                return $queue === (self::DEFAULT_QUEUE . GlobalConfig::DELAYED_QUEUE_POSTFIX)
                    && $payload === $delayedMessage->payload
                    && $timestamp === (int)($delayedMessage->timestamp / 1000);
            }
        );
        $this->consumer->shouldReceive('commitOffset')->twice();

        self::assertEquals($nextMessage, $this->queue->pop()->getMessage());
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testReturnsNullIfThereAreNoJobsAfterReQueue(): void
    {
        $delayedMessage = $this->mockKafkaMessage(now()->addHour()->getTimestampMs());

        $this->consumer->shouldReceive('consume')->andReturn($delayedMessage);
        $this->producer->shouldReceive('produce')->twice();

        self::assertNull($this->queue->pop());
    }

    /**
     * @param int|null    $timestamp
     * @param string|null $queue
     * @return Message
     */
    private function mockKafkaMessage(int $timestamp = null, string $queue = null, string $jobId = null): Message
    {
        $message             = new Message();
        $message->payload    = json_encode(
            array_merge(
                $this->faker->rgbColorAsArray,
                [
                    'id'   => $jobId ?? $this->faker->uuid,
                    'uuid' => $this->faker->uuid,
                ]
            )
        );
        $message->timestamp  = $timestamp ?? now()->getTimestampMs();
        $message->topic_name = $queue ?? $this->faker->word;

        return $message;
    }
}
