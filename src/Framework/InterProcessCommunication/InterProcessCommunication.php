<?php

declare(strict_types=1);

namespace Chassis\Framework\InterProcessCommunication;

use Chassis\Framework\InterProcessCommunication\DataTransferObject\IPCMessage;
use parallel\Channel;
use parallel\Events\Event;
use Throwable;

class InterProcessCommunication
{
    private IPCMessage $message;
    private bool $aborting = false;
    private bool $respawning = false;
    private ?Event $event;
    private ?Channel $channel;

    /**
     * @param Channel|null $channel
     * @param Event|null $event
     */
    public function __construct(
        ?Channel $channel = null,
        ?Event $event = null
    ) {
        $this->event = $event;
        $this->channel = $channel;
    }

    /**
     * @return InterProcessCommunication
     */
    public function handle(): InterProcessCommunication
    {
        $messageToHandle = new IPCMessage($this->event->value);
        switch ($messageToHandle->headers->method) {
//            case "abort":
//                $this->aborting = $this->setMessage("aborting")->send();
//                break;
            case "aborting":
                $this->aborting = true;
                break;
            case "respawn":
                $this->respawning = true;
                break;
            default:
                // TODO: implements event/listener pattern here to handle other methods
                break;
        }

        return $this;
    }

    /**
     * @param string $method
     * @param array|string|null $body
     * @param array $headers
     *
     * @return InterProcessCommunication
     */
    public function setMessage(string $method, $body = null, array $headers = []): InterProcessCommunication
    {
        $this->message = new IPCMessage([
            "headers" => array_merge($headers, ["method" => $method]),
            "body" => $body
        ]);
        return $this;
    }

    /**
     * @return bool
     */
    public function send(): bool
    {
        try {
            $this->channel->send($this->message->toArray());
            return true;
        } catch (Throwable $reason) {
            // fault-tolerant
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isAborting(): bool
    {
        return $this->aborting;
    }

    /**
     * @return bool
     */
    public function isRespawnRequested(): bool
    {
        return $this->respawning;
    }
}
