<?php

declare(strict_types=1);

namespace Chassis\Framework\InterProcessCommunication\DataTransferObject;

use Spatie\DataTransferObject\DataTransferObject;

class IPCMessage extends DataTransferObject
{
    /**
     * @var \Chassis\Framework\InterProcessCommunication\DataTransferObject\IPCMessageHeaders
     */
    public IPCMessageHeaders $headers;

    /**
     * @var mixed|null
     */
    public $body = null;

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        if (empty($parameters)) {
            $parameters["headers"] = [];
        }
        parent::__construct($parameters);
    }

    /**
     * @param string $method
     * @param array|string|null $body
     * @param array $headers
     *
     * @return IPCMessage
     */
    public function set(string $method, $body = null, array $headers = []): IPCMessage
    {
        $this->body = $body;
        $this->headers = new IPCMessageHeaders(array_merge($headers, ["method" => $method]));

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getHeader(string $key)
    {
        return $this->headers->{$key} ?? null;
    }
}
