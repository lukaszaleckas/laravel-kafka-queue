<?php

namespace LaravelKafka;

use Illuminate\Queue\Connectors\ConnectorInterface;
use LaravelKafka\Kafka\Consumer;
use LaravelKafka\Kafka\GlobalConfig;
use LaravelKafka\Kafka\Producer;

class KafkaConnector implements ConnectorInterface
{
    public const DEFAULT_TOPIC = 'default';

    public const CONFIG_HOST             = 'host';
    public const CONFIG_PORT             = 'port';
    public const CONFIG_TOPIC            = 'queue';
    public const CONFIG_HEARTBEAT        = 'heartbeat';
    public const CONFIG_GROUP_NAME       = 'group_name';
    public const CONFIG_PRODUCER_TIMEOUT = 'producer_timeout';
    public const CONFIG_CONSUMER_TIMEOUT = 'consumer_timeout';

    /**
     * @param array $config
     * @return KafkaQueue
     */
    public function connect(array $config): KafkaQueue
    {
        return new KafkaQueue(
            $this->buildProducer($config),
            $this->buildConsumer($config),
            $config[self::CONFIG_TOPIC] ?? self::DEFAULT_TOPIC
        );
    }

    /**
     * @param array $config
     * @return Producer
     */
    private function buildProducer(array $config): Producer
    {
        return new Producer(
            $this->buildGlobalConfig($config),
            $config[self::CONFIG_PRODUCER_TIMEOUT]
        );
    }

    /**
     * @param array $config
     * @return Consumer
     */
    private function buildConsumer(array $config): Consumer
    {
        return new Consumer(
            $this->buildGlobalConfig($config),
            $config[self::CONFIG_GROUP_NAME],
            $config[self::CONFIG_CONSUMER_TIMEOUT],
            $config[self::CONFIG_HEARTBEAT]
        );
    }

    /**
     * @param array $config
     * @return GlobalConfig
     */
    private function buildGlobalConfig(array $config): GlobalConfig
    {
        return new GlobalConfig(
            $config[self::CONFIG_HOST],
            $config[self::CONFIG_PORT]
        );
    }
}
