<?php

namespace LaravelKafka\Kafka;

use RdKafka\Conf;

class GlobalConfig extends Conf
{
    public const DELAYED_QUEUE_POSTFIX = '-delayed';

    public const CONFIG_BROKER_LIST = 'metadata.broker.list';

    /**
     * @param string $host
     * @param int    $port
     */
    public function __construct(string $host, int $port)
    {
        parent::__construct();

        $this->set(self::CONFIG_BROKER_LIST, "{$host}:{$port}");
    }
}
