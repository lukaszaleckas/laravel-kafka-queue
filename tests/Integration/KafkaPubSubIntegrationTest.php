<?php

namespace LaravelKafka\Tests\Integration;

use Exception;
use Illuminate\Foundation\Testing\WithFaker;
use LaravelKafka\Tests\Integration\Contracts\AbstractIntegrationTestCase;
use RuntimeException;
use Throwable;

class KafkaPubSubIntegrationTest extends AbstractIntegrationTestCase
{
    use WithFaker;

    /**
     * @return void
     * @throws Throwable
     */
    public function testCanConsumeMessage(): void
    {
        $message   = $this->faker->text;
        $timestamp = $this->faker->unixTime;

        $this->producer->produce(self::TEST_TOPIC, $message, $timestamp);

        $result = $this->consumer->consume(self::TEST_TOPIC);

        self::assertNotNull($result);

        $this->consumer->commitOffset();

        self::assertEquals($message, $result->payload);
        self::assertEquals($timestamp * 1000, $result->timestamp);
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testAbortsTransactionIfExceptionIsThrownWhileProducing(): void
    {
        $exception = new Exception($this->faker->uuid);

        try {
            $this->producer->produce(
                self::TEST_TOPIC,
                '',
                $this->faker->unixTime,
                function () use ($exception) {
                    throw $exception;
                }
            );
        } catch (Exception $error) {
            self::assertEquals($exception, $error);
        }

        self::assertNull(
            $this->consumer->consume(self::TEST_TOPIC)
        );
    }

    /**
     * @return void
     */
    public function testThrowsExceptionOnUnknownTopic(): void
    {
        $this->expectExceptionObject(
            new RuntimeException('Broker: Unknown topic or partition', 3)
        );

        $this->consumer->consume($this->faker->uuid);
    }
}
