<?php

declare(strict_types=1);

namespace Chassis\Framework\Providers;

use Chassis\Framework\Threads\ThreadInstance;
use Chassis\Framework\Threads\ThreadInstanceInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

class ThreadInstanceServiceProvider extends AbstractServiceProvider
{
    /**
     * @param string $id
     *
     * @return bool
     */
    public function provides(string $id): bool
    {
        return $id === ThreadInstanceInterface::class;
    }

    /**
     * @return void
     */
    public function register(): void
    {
        // add key/value pair
        $this->getContainer()
            ->add(ThreadInstanceInterface::class, ThreadInstance::class)
            ->setShared(false);
    }
}
