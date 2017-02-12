<?php

namespace Wrench\Application;

/**
 * Example application for Wrench: echo server
 */
class EchoApplication implements DataHandlerInterface
{
    public function onData($data, $client)
    {
        $client->send($data);
    }
}
