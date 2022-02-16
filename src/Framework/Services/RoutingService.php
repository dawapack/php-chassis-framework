<?php

declare(strict_types=1);

namespace Chassis\Framework\Services;

use Chassis\Framework\Brokers\Amqp\BrokerResponse;
use Chassis\Framework\Brokers\Amqp\MessageBags\MessageBagInterface;

class RoutingService extends BrokerAbstractService
{
    /**
     * @param MessageBagInterface $messageBag
     *
     * @return BrokerResponse|void
     */
    public function routeNotfound(MessageBagInterface $messageBag)
    {
        if (empty($messageBag->getProperty("reply_to"))) {
            return;
        }
        return $this->response()
            ->fromContext($messageBag)
            ->setStatus(404, "ROUTE NOT FOUND");
    }
}
