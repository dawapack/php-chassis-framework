<?php

declare(strict_types=1);

namespace Chassis\Framework\Providers;

use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

class WorkersServiceProvider extends AbstractServiceProvider
{
    // interface/concrete key/value pairs
    protected array $inboundAdapters = [];
    protected array $outboundAdapters = [];
    private array $ids = [];

    public function provides(string $id): bool
    {
        return in_array($id, $this->ids);
    }

    public function register(): void
    {
        $this->populateIds();
        $container = $this->getContainer();
        // register inbound adapters
        foreach ($this->inboundAdapters as $interface => $implementation) {
            $this->adapterRegister($container, $interface, $implementation);
        }
        // register outbound adapters
        foreach ($this->outboundAdapters as $interface => $implementation) {
            $this->adapterRegister($container, $interface, $implementation);
        }
    }

    /**
     * @param DefinitionContainerInterface $container
     * @param string $interface
     * @param array|string $implementation
     *
     * @return void
     */
    private function adapterRegister(
        DefinitionContainerInterface $container,
        string $interface,
        $implementation
    ): void {
        if (is_string($implementation)) {
            $container->add($interface, $implementation);
            return;
        }
        $container->add($interface, $implementation["concrete"])
            ->addArguments($implementation["arguments"]);
    }

    /**
     * @return void
     */
    private function populateIds(): void
    {
        $this->ids = array_merge(
            array_keys($this->inboundAdapters),
            array_keys($this->outboundAdapters)
        );
    }
}
