<?php

namespace Wrench\Application;

use Wrench\Connection;

/**
 * @deprecated Rather than extending this class, just implement one or more of these optional interfaces:
 *              - Wrench\Application\DataHandlerInterface for onData()
 *              - Wrench\Application\ConnectionHandlerInterface for onConnect() and onDisconnect()
 *              - Wrench\Application\UpdateHandlerInterface for onUpdate()
 */
abstract class Application implements DataHandlerInterface
{
    /**
     * Handle data received from a client
     *
     * @param string     $data
     * @param Connection $connection
     * @return void
     */
    abstract public function onData(string $data, Connection $connection): void;
}
