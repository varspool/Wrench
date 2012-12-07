<?php

namespace Wrench\Application;

use Wrench\Application\Application;
use Wrench\Application\NamedApplication;

/**
 * Example application demonstrating how to use Application::onUpdate
 *
 * Pushes the server time to all clients every update tick.
 */
class ServerTimeApplication extends Application
{
    protected $clients = array();
    protected $lastTimestamp = null;

    /**
     * @see Wrench\Application.Application::onConnect()
     */
    public function onConnect($client)
    {
        $this->clients[] = $client;
    }

    /**
     * @see Wrench\Application.Application::onUpdate()
     */
    public function onUpdate()
    {
        // limit updates to once per second
        if(time() > $this->lastTimestamp) {
            $this->lastTimestamp = time();

            foreach ($this->clients as $sendto) {
                $sendto->send(date('d-m-Y H:i:s'));
            }
        }
    }
}
