<?php

declare(strict_types=1);

namespace Chassis\Framework\Threads\InterProcessCommunication;

use Chassis\Framework\Threads\DataTransferObject\IPCMessage;
use parallel\Channel;

interface IPCChannelsInterface
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
     * @return void
     */
    public function eventsPoll(): void;

    /**
     * @return void
     */
    public function destroy(): void;
}
