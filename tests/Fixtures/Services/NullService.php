<?php

declare(strict_types=1);

namespace ChassisTests\Fixtures\Services;

use Chassis\Application;
use Chassis\Framework\Adapters\Message\MessageInterface;

class NullService
{
    private ?MessageInterface $message;
    private ?Application $application;

    /**
     * @param MessageInterface|null $message
     * @param Application|null $application
     */
    public function __construct(
        MessageInterface $message = null,
        Application $application = null
    ) {
        $this->message = $message;
        $this->application = $application;
    }

    /**
     * Nobody cares about the implementation
     *
     * @param MessageInterface $message
     *
     * @return void
     */
    public function __invoke(MessageInterface $message): void
    {
    }

    /**
     * @return null
     */
    public function noOperation()
    {
        return null;
    }
}
