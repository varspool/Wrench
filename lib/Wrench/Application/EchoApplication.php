<?php

namespace Wrench\Application;

use Wrench\Application\Application;
use Wrench\Application\NamedApplication;

/**
 * Example application for Wrench: echo server
 */
class EchoApplication extends Application
{
    /**
     * @see Wrench\Application.Application::onData()
     */
    public function onData($data, $client)
    {
        $client->send($data);
    }
}