<?php

declare(strict_types=1);

namespace Chassis\Framework\Brokers\Amqp\Configurations\DataTransferObject;

use Chassis\Framework\Brokers\Amqp\Configurations\BindingsInterface;
use Spatie\DataTransferObject\DataTransferObject;

class BrokerConnection extends DataTransferObject implements BindingsInterface
{
    /**
     *
     * @var string
     */
    public string $protocol;

    /**
     * @var string
     */
    public string $host;

    /**
     * @var int
     */
    public int $port;

    /**
     * @var string
     */
    public string $user;

    /**
     * @var string
     */
    public string $pass;

    /**
     * @var string
     */
    public string $vhost;

    /**
     * @var bool
     */
    public bool $insist = false;

    /**
     * @var string
     */
    public string $login_method = 'AMQPLAIN';

    /**
     * @var string|null
     * @deprecated
     */
    public ?string $login_response = null;

    /**
     * @var string
     */
    public string $locale = 'en_US';

    /**
     * @var float
     */
    public float $connection_timeout = 5.0;

    /**
     * @var float
     */
    public float $read_write_timeout = 30.0;

    /**
     * @var resource|array|null
     */
    public $context = null;

    /**
     * @var bool
     */
    public bool $keepalive = false;

    /**
     * @var int
     */
    public int $heartbeat = 0;

    /**
     * @var float
     */
    public float $channel_rpc_timeout = 30.0;

    /**
     * @var string|null
     */
    public ?string $ssl_protocol = null;

    /**
     * @inheritDoc
     */
    public function toFunctionArguments(bool $onlyValues = true): array
    {
        return $onlyValues
            ? array_values($this->except("protocol")->toArray())
            : $this->except("protocol")->toArray();
    }

    public function toLazyConnectionFunctionArguments(bool $onlyValues = true): array
    {
        $functionArguments = array_merge(
            $this->only(
                ...array_values([
                    "host",
                    "port",
                    "user",
                    "pass",
                    "vhost"
                ])
            )->toArray(),
            [
                "options" => $this->only(
                    ...array_values([
                        "insist",
                        "login_method",
                        "login_response",
                        "locale",
                        "keepalive",
                        "heartbeat"
                    ])
                )->toArray()
            ]
        );
        $functionArguments["options"]["read_timeout"] = $this->read_write_timeout;
        $functionArguments["options"]["write_timeout"] = $this->read_write_timeout;

        return $onlyValues ? array_values($functionArguments) : $functionArguments;
    }
}
