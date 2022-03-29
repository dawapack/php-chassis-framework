<?php

declare(strict_types=1);

namespace Chassis\Framework\Providers;

use Chassis\Framework\Threads\Configuration\ThreadsConfigurationInterface;
use Chassis\Framework\Threads\ThreadInstance;
use Chassis\Framework\Threads\ThreadInstanceInterface;
use Chassis\Framework\Threads\ThreadsManager;
use Chassis\Framework\Threads\ThreadsManagerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use parallel\Events;
use Psr\Log\LoggerInterface;

class ThreadsServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        $ids = [
            ThreadInstanceInterface::class,
            ThreadsManagerInterface::class
        ];

        return in_array($id, $ids);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ThreadInstanceInterface::class, ThreadInstance::class)
            ->setShared(false);

        $container->add(ThreadsManagerInterface::class, ThreadsManager::class)
            ->addArguments([ThreadsConfigurationInterface::class, new Events(), LoggerInterface::class]);
    }
}
