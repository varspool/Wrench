<?php

namespace Wrench\Application;

use Wrench\Connection;
use Wrench\Payload\Payload;

/**
 * Example application for Wrench: echo server
 */
class EchoApplication implements DataHandlerInterface
{
    public function onData(string $data, Connection $client): void
    {
        $client->send($data);
    }
}
