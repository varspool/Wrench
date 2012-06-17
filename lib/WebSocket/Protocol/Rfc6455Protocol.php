<?php

namespace WebSocket\Protocol;

use WebSocket\Protocol\HybiProtocol;

/**
 * This is the version of websockets used by Chrome versions 17 through 19.
 */
class Rfc6455Protocol extends HybiProtocol
{
    const VERSION = 13;

    public function getVersion()
    {
        return self::VERSION;
    }
}