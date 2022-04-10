<?php

namespace LaravelKafka\Kafka;

use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RuntimeException;

class Consumer
{
    public const CONFIG_GROUP_ID                      = 'group.id';
    public const CONFIG_HEARTBEAT_INTERVAL_MS         = 'heartbeat.interval.ms';
    public const CONFIG_AUTO_OFFSET_RESET             = 'auto.offset.reset';
    public const CONFIG_ENABLE_AUTO_COMMIT            = 'enable.auto.commit';
    public const CONFIG_PARTITION_ASSIGNMENT_STRATEGY = 'partition.assignment.strategy';

    /** @var KafkaConsumer */
    private KafkaConsumer $consumer;

    /** @var int */
    private int $timeout;

    /**
     * @param GlobalConfig $globalConfig
     * @param string       $groupName
     * @param int          $timeout
     * @param int          $heartbeat
     */
    public function __construct(GlobalConfig $globalConfig, string $groupName, int $timeout, int $heartbeat)
    {
        $globalConfig->set(self::CONFIG_GROUP_ID, $groupName);
        $globalConfig->set(self::CONFIG_HEARTBEAT_INTERVAL_MS, "{$heartbeat}");
        $globalConfig->set(self::CONFIG_AUTO_OFFSET_RESET, 'earliest');
        $globalConfig->set(self::CONFIG_ENABLE_AUTO_COMMIT, 'false');
        $globalConfig->set(self::CONFIG_PARTITION_ASSIGNMENT_STRATEGY, 'roundrobin');

        $this->timeout  = $timeout;
        $this->consumer = new KafkaConsumer($globalConfig);
    }

    /**
     * @param string $topic
     * @return Message|null
     * @throws RuntimeException
     */
    public function consume(string $topic): ?Message
    {
        if (!in_array($topic, $this->consumer->getSubscription())) {
            $this->consumer->subscribe([$topic, $topic . GlobalConfig::DELAYED_QUEUE_POSTFIX]);
        }

        $message = $this->consumer->consume($this->timeout);

        switch ($message->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                return $message;
            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                return null;
            default:
                throw new RuntimeException($message->errstr(), $message->err);
        }
    }

    /**
     * @return void
     */
    public function commitOffset(): void
    {
        $this->consumer->commit();
    }
}
