<?php

namespace Wrench\Application;

use Wrench\Connection;

interface DataHandlerInterface
{
    /**
     * Handle data received from a client
     *
     * @param string     $data
     * @param Connection $connection
     * @return void
     */
    public function onData(string $data, Connection $connection): void;
}
