<?php

namespace Wrench\Application;

use Wrench\Connection;
use Wrench\Payload\Payload;

/**
 * Example application demonstrating how to use Application::onUpdate
 * Pushes the server time to all clients every update tick.
 */
class ServerTimeApplication implements ConnectionHandlerInterface, DataHandlerInterface, UpdateHandlerInterface
{
    protected $clients = [];
    protected $lastTimestamp = null;

    public function onConnect($client)
    {
        $this->clients[] = $client;
    }

    public function onDisconnect($connection)
    {
        $a = $connection;
    }

    public function onUpdate()
    {
        // limit updates to once per second
        if (time() > $this->lastTimestamp) {
            $this->lastTimestamp = time();

            foreach ($this->clients as $sendto) {
                $sendto->send(date('d-m-Y H:i:s'));
            }
        }
    }

    /**
     * Handle data received from a client
     *
     * @param Payload|string    $payload A payload object, that supports __toString()
     * @param Connection $connection
     */
    public function onData(string $payload, Connection $connection)
    {
        return;
    }
}
