<?php

declare(strict_types=1);

namespace Chassis\Framework\Services;

use Chassis\Framework\Brokers\Amqp\BrokerResponse;

class RoutingService extends BrokerAbstractService
{
    /**
     * @return BrokerResponse|void
     */
    public function routeNotfound()
    {
        if (empty($this->messageBag->getProperty("reply_to"))) {
            return;
        }
        return $this->response()
            ->fromContext($this->messageBag)
            ->setStatus(404, "ROUTE NOT FOUND");
    }
}
