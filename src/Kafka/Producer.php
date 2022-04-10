<?php

namespace LaravelKafka\Kafka;

use Illuminate\Support\Str;
use RdKafka\Producer as KafkaProducer;
use Throwable;

class Producer
{
    public const CONFIG_TRANSACTIONAL_ID = 'transactional.id';

    /** @var KafkaProducer */
    private KafkaProducer $producer;

    /** @var int */
    private int $timeout;

    /** @var bool */
    private bool $transactionsInitialized;

    /**
     * @param GlobalConfig $globalConfig
     * @param int          $timeout
     */
    public function __construct(GlobalConfig $globalConfig, int $timeout)
    {
        $globalConfig->set(self::CONFIG_TRANSACTIONAL_ID, uniqid());

        $this->timeout                 = $timeout;
        $this->transactionsInitialized = false;
        $this->producer                = new KafkaProducer($globalConfig);
    }

    /**
     * @param string        $topic
     * @param string        $payload
     * @param int|null      $timestampSeconds
     * @param callable|null $callback
     * @return void
     * @throws Throwable
     */
    public function produce(string $topic, string $payload, ?int $timestampSeconds, callable $callback = null): void
    {
        $this->runInProducerTransaction(
            function () use ($topic, $payload, $timestampSeconds, $callback) {
                $this->producer->newTopic($topic)->producev(
                    RD_KAFKA_PARTITION_UA,
                    0,
                    $payload,
                    $this->getMessageId(),
                    null,
                    (int)(($timestampSeconds ?? now()->timestamp) * 1000)
                );

                if ($callback !== null) {
                    $callback();
                }
            }
        );

        $this->producer->poll(0);
    }

    /**
     * @param callable $callback
     * @return void
     * @throws Throwable
     */
    private function runInProducerTransaction(callable $callback): void
    {
        if (!$this->transactionsInitialized) {
            $this->producer->initTransactions($this->timeout);
            $this->transactionsInitialized = true;
        }

        $this->producer->beginTransaction();

        try {
            $callback();
        } catch (Throwable $exception) {
            $this->producer->abortTransaction($this->timeout);

            throw $exception;
        }

        $this->producer->commitTransaction($this->timeout);
    }

    /**
     * @return string
     */
    private function getMessageId(): string
    {
        return Str::orderedUuid()->toString();
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->producer->flush($this->timeout);
    }
}
