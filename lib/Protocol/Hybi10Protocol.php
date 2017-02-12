<?php

namespace Wrench\Protocol;

/**
 * http://tools.ietf.org/html/draft-ietf-hybi-thewebsocketprotocol-10
 */
class Hybi10Protocol extends HybiProtocol
{
    const VERSION = 10;

    /**
     * @see Protocol::getVersion()
     */
    public function getVersion()
    {
        return self::VERSION;
    }

    /**
     * @see Protocol::acceptsVersion()
     */
    public function acceptsVersion($version)
    {
        $version = (int)$version;

        if ($version <= 10 && $version >= 10) {
            return true;
        }
    }
}
