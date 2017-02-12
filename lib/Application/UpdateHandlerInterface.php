<?php

namespace Wrench\Application;

use Wrench\Connection;
use Wrench\Payload\Payload;

interface UpdateHandlerInterface
{
    /**
     * Handle an update tick
     */
    public function onUpdate();
}
