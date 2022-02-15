<?php

declare(strict_types=1);

namespace Chassis\Framework\Providers;

use Chassis\Framework\Threads\Configuration\ThreadsConfigurationInterface;
use Chassis\Framework\Threads\ThreadsManager;
use Chassis\Framework\Threads\ThreadsManagerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use parallel\Events;
use Psr\Log\LoggerInterface;

class ThreadsManagerServiceProvider extends AbstractServiceProvider
{
    /**
     * @param string $id
     *
     * @return bool
     */
    public function provides(string $id): bool
    {
        return $id === ThreadsManagerInterface::class;
    }

    /**
     * @return void
     */
    public function register(): void
    {
        // Instantiate ThreadsManager
        $this->getContainer()
            ->add(ThreadsManagerInterface::class, ThreadsManager::class)
            ->addArguments([
                ThreadsConfigurationInterface::class,
                new Events(),
                LoggerInterface::class
            ]);
    }
}
