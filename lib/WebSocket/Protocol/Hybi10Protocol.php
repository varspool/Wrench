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

    /**
     * This is our most recent protocol class
     *
     * @see WebSocket\Protocol.Protocol::acceptsVersion()
     */
    public function acceptsVersion($version)
    {
        $version = (int)$version;

        if ($version <= 10 && $version >= 10) {
            return true;
        }
    }
}