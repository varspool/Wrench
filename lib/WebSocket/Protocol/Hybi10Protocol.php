<?php

namespace WebSocket\Protocol;

use WebSocket\Protocol\HybiProtocol;

/**
 * http://tools.ietf.org/html/draft-ietf-hybi-thewebsocketprotocol-10
 */
class Hybi10Protocol extends HybiProtocol
{
    const VERSION = 10;

    /**
     * @see WebSocket\Protocol.Protocol::getVersion()
     */
    public function getVersion()
    {
        return self::VERSION;
    }
}