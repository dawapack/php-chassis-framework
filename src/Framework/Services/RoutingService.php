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

        var_dump([__METHOD__, $this->message]);

        if (empty($this->message->getProperty("reply_to"))) {
            return;
        }
        return $this->response()
            ->fromContext($this->message)
            ->setStatus(404, "ROUTE NOT FOUND");
    }
}
