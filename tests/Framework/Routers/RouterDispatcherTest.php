<?php

declare(strict_types=1);

namespace ChassisTests\Framework\Routers;

use Chassis\Application;
use Chassis\Framework\Adapters\Message\InboundMessage;
use Chassis\Framework\Adapters\Message\OutboundMessage;
use Chassis\Framework\Adapters\Outbound\Bus\OutboundBusAdapter;
use Chassis\Framework\Routers\RouteDispatcher;
use ChassisTests\Fixtures\Services\NullService;
use PHPUnit\Framework\TestCase;

class RouterDispatcherTest extends TestCase
{
    private Application $application;
    private InboundMessage $inboundMessage;
    private OutboundMessage $outboundMessage;
    private OutboundBusAdapter $outboundBusAdapter;
    private RouteDispatcher $sut;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $this->outboundBusAdapter = $this->getMockBuilder(OutboundBusAdapter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['pushResponse'])
            ->getMock();
        $this->inboundMessage = $this->getMockBuilder(InboundMessage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->outboundMessage = $this->getMockBuilder(OutboundMessage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sut = new RouteDispatcher();
    }

    /**
     * @return void
     */
    public function testSutCanDispatchAnInboundMessageUsingInvokableService(): void
    {
        $this->assertNull($this->sut->dispatch(NullService::class, $this->inboundMessage));
    }

    /**
     * @return void
     */
    public function testSutCanDispatchAnInboundMessageUsingClassAndMethodService(): void
    {
        $this->assertNull(
            $this->sut->dispatch(
                [NullService::class, 'noOperation'],
                $this->inboundMessage
            )
        );
    }

    /**
     * @return void
     */
    public function testSutCanDispatchAResponseMessage(): void
    {
        $this->markTestSkipped('need to instantiate application in testing context for this test');

        /*
        $this->outboundBusAdapter->expects($this->once())
            ->method('pushResponse')
            ->with($this->outboundMessage, $this->inboundMessage)
            ->willReturn(null);
        $this->assertNull($this->sut->dispatchResponse($this->outboundMessage, $this->inboundMessage));
        */
    }
}
