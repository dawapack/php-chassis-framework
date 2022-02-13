<?php

declare(strict_types=1);

namespace Chassis\Framework\InterProcessCommunication;

use Chassis\Framework\InterProcessCommunication\DataTransferObject\IPCMessage;
use parallel\Channel;

interface ChannelsInterface
{
    /**
     * @param Channel $channel
     * @param bool $attachEventListener
     *
     * @return void
     */
    public function setWorkerChannel(Channel $channel, bool $attachEventListener = false): void;

    /**
     * @return Channel|null
     */
    public function getWorkerChannel(): ?Channel;

    /**
     * @param Channel $channel
     * @param bool $attachEventListener
     *
     * @return void
     */
    public function setThreadChannel(Channel $channel, bool $attachEventListener = false): void;

    /**
     * @return Channel|null
     */
    public function getThreadChannel(): ?Channel;

    /**
     * @return $this
     */
    public function sendTo(Channel $channel, IPCMessage $message): self;

    /**
     * @return IPCMessage|null
     */
    public function getMessage(): ?IPCMessage;

    /**
     * @return bool
     */
    public function isAborting(): bool;

    /**
     * @return bool
     */
    public function isAbortRequested(): bool;

    /**
     * @return bool
     */
    public function isRespawnRequested(): bool;

    /**
     * @return bool
     */
    public function eventsPoll(): bool;

    /**
     * @return void
     */
    public function destroy(): void;
}
