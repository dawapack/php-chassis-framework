<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Routers;

use Chassis\Framework\Adapters\Message\InboundMessage;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\OutboundAdapters\Broker\RouteNotFound;
use Chassis\Framework\Routers\InboundRouter;
use Chassis\Framework\Routers\OutboundRouter;
use Chassis\Framework\Routers\RouteDispatcher;
use ChassisTests\Traits\AMQPMessageTrait;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class InboundRouterTest extends TestCase
{
    use AMQPMessageTrait;

    private RouteDispatcher $routeDispatcher;
    private OutboundRouter $outboundRouter;
    private InboundMessage $inboundMessage;
    private OutboundMessage $outboundMessage;
    private InboundRouter $sut;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->routeDispatcher = $this->getMockBuilder(RouteDispatcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['dispatch'])
            ->getMock();
        $this->outboundRouter = $this->getMockBuilder(OutboundRouter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['route'])
            ->getMock();
        $this->inboundMessage = $this->getMockBuilder(InboundMessage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->outboundMessage = $this->getMockBuilder(OutboundMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sut = new InboundRouter($this->routeDispatcher, $this->outboundRouter, new NullLogger(), []);
    }

    /**
     * @return void
     */
    public function testSutCanRouteAnInboundMessage(): void
    {
        $this->routeDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn(null);

        $this->sut->route("anyNotFoundOperation", $this->inboundMessage);
    }

    /**
     * @return void
     */
    public function testSutCanRouteAResponseMessage(): void
    {
        $this->routeDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn($this->outboundMessage);
        $this->outboundRouter->expects($this->once())
            ->method('route')
            ->with(null, $this->outboundMessage, $this->inboundMessage)
            ->willReturn(null);

        $this->sut->route("anyNotFoundOperation", $this->inboundMessage);
    }

    /**
     * @return void
     */
    public function testSutCanRouteAResponseMessageAndHandleAnException(): void
    {
        $this->routeDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn($this->outboundMessage);
        $this->outboundRouter->expects($this->once())
            ->method('route')
            ->with(null, $this->outboundMessage, $this->inboundMessage)
            ->willThrowException(new Exception("the exception must be caught"));

        $this->sut->route("anyNotFoundOperation", $this->inboundMessage);
    }
}
