<?php

declare(strict_types=1);

namespace Chassis\Framework\Bus\AMQP\Connector;

use Chassis\Framework\AsyncApi\AsyncContractInterface;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPIOException;

class AMQPConnector implements AMQPConnectorInterface
{
    private AsyncContractInterface $asyncContract;
    private AMQPStreamConnection $streamConnection;
    private int $heartbeatLastActivity;

    /**
     * @param AsyncContractInterface $asyncContract
     */
    public function __construct(AsyncContractInterface $asyncContract)
    {
        $this->asyncContract = $asyncContract;
        $this->createStreamConnection();
        // initialize heartbeat reference
        $this->heartbeatLastActivity = time();
    }

    /**
     * @inheritDoc
     */
    public function getChannel(?int $id = null): AMQPChannel
    {
        return $this->streamConnection->channel($id);
    }

    /**
     * @inheritdoc
     */
    public function disconnect(): void
    {
        if (isset($this->streamConnection) && $this->streamConnection->isConnected()) {
            $this->streamConnection->close();
        }
    }

    /**
     * @inheritDoc
     *
     * @throws AMQPIOException
     */
    public function checkHeartbeat(): void
    {
        $heartbeat = $this->streamConnection->getHeartbeat();
        if (
            $heartbeat === 0
            || !$this->streamConnection->isConnected()
            || $this->streamConnection->isWriting()
        ) {
            return;
        }

        if ($this->heartbeatLastActivity < time()) {
            $this->streamConnection->checkHeartBeat();
            $this->heartbeatLastActivity = (int)(time() + ceil($heartbeat / 2));
        }
    }

    /**
     * @return void
     */
    protected function createStreamConnection(): void
    {
        $this->streamConnection = new AMQPStreamConnection(
            ...$this->asyncContract->getTransformer()->toConnectionArguments()
        );
    }
}
