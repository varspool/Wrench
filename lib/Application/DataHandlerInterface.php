<?php

namespace Wrench\Application;

use Wrench\Connection;
use Wrench\Payload\Payload;

interface DataHandlerInterface
{
    /**
     * Handle data received from a client
     *
     * @param Payload    $payload A payload object, that supports __toString()
     * @param Connection $connection
     */
    public function onData($payload, $connection);
}
