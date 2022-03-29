<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Routers;

use Chassis\Framework\Adapters\Message\InboundMessage;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\Routers\Exceptions\RouteNotFoundException;
use Chassis\Framework\Routers\OutboundRouter;
use Chassis\Framework\Routers\RouteDispatcher;
use ChassisTests\Fixtures\Adapters\NullOperation;
use PHPUnit\Framework\TestCase;

class OutboundRouterTest extends TestCase
{
    private RouteDispatcher $routeDispatcher;
    private InboundMessage $inboundMessage;
    private OutboundMessage $outboundMessage;
    private OutboundRouter $sut;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->routeDispatcher = $this->getMockBuilder(RouteDispatcher::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['dispatch', 'dispatchResponse'])
            ->getMock();
        $this->inboundMessage = $this->getMockBuilder(InboundMessage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->outboundMessage = $this->getMockBuilder(OutboundMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sut = new OutboundRouter(
            $this->routeDispatcher,
            ["nullOperation" => NullOperation::class]
        );
    }

    /**
     * @return void
     *
     * @throws RouteNotFoundException
     */
    public function testSutCanRouteAnOutboundMessage(): void
    {
        $this->routeDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(NullOperation::class, $this->outboundMessage)
            ->willReturn(null);

        $this->assertNull(
            $this->sut->route("nullOperation", $this->outboundMessage)
        );
    }

    /**
     * @return void
     *
     * @throws RouteNotFoundException
     */
    public function testSutCanRouteAResponse(): void
    {
        $this->routeDispatcher->expects($this->once())
            ->method('dispatchResponse')
            ->with($this->outboundMessage, $this->inboundMessage)
            ->willReturn(null);

        $this->assertNull(
            $this->sut->route(null, $this->outboundMessage, $this->inboundMessage)
        );
    }

    /**
     * @return void
     *
     * @throws RouteNotFoundException
     */
    public function testSutMustThrowRouteNotFoundException(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->sut->route("notFoundOperation", $this->outboundMessage);
    }
}
