<?php

namespace WebSocket\Protocol;

use WebSocket\Protocol\HybiProtocol;

class Rfc6455Protocol extends HybiProtocol
{
    const VERSION = 13;

    public function getVersion()
    {
        return self::VERSION;
    }
}