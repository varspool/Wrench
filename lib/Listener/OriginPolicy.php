<?php

namespace Wrench\Listener;

use Wrench\Connection;
use Wrench\Exception\InvalidOriginException;
use Wrench\Protocol\Protocol;
use Wrench\Server;

class OriginPolicy implements Listener, HandshakeRequestListener
{
    protected $allowed = [];

    public function __construct(array $allowed)
    {
        $this->allowed = $allowed;
    }

    /**
     * Handshake request listener
     * Closes the connection on handshake from an origin that isn't allowed
     *
     * @param Connection $connection
     * @param string     $path
     * @param string     $origin
     * @param string     $key
     * @param array      $extensions
     */
    public function onHandshakeRequest(
        Connection $connection,
        string $path,
        string $origin,
        string $key,
        array $extensions
    ): void {
        if (!$this->isAllowed($origin)) {
            $connection->close(Protocol::CLOSE_NORMAL, 'Not allowed origin during handshake request');
        }
    }

    /**
     * Whether the specified origin is allowed under this policy
     *
     * @param string $origin
     * @return bool
     */
    public function isAllowed(string $origin): bool
    {
        $scheme = parse_url($origin, PHP_URL_SCHEME);
        $host = parse_url($origin, PHP_URL_HOST) ?: $origin;

        foreach ($this->allowed as $allowed) {
            $allowed_scheme = parse_url($allowed, PHP_URL_SCHEME);

            if ($allowed_scheme && $scheme != $allowed_scheme) {
                continue;
            }

            $allowed_host = parse_url($allowed, PHP_URL_HOST) ?: $allowed;

            if ($host != $allowed_host) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param Server $server
     */
    public function listen(Server $server): void
    {
        $server->addListener(
            Server::EVENT_HANDSHAKE_REQUEST,
            [$this, 'onHandshakeRequest']
        );
    }
}
