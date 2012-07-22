<?php

namespace Wrench\Socket;

use Wrench\Socket\Socket;

abstract class UriSocket extends Socket
{
    protected $scheme;
    protected $host;
    protected $port;

    /**
     * URI Socket constructor
     *
     * @param string $uri     WebSocket URI, e.g. ws://example.org:8000/chat
     * @param array  $options (optional)
     *   Options:
     *     - protocol             => Wrench\Protocol object, latest protocol
     *                                 version used if not specified
     *     - timeout_socket       => int, seconds, default 5
     *     - server_ssl_cert_file => string, server SSL certificate
     *                                 file location. File should contain
     *                                 certificate and private key
     *     - server_ssl_passphrase => string, passphrase for the key
     *     - server_ssl_allow_self_signed => boolean, whether to allows self-
     *                                 signed certs
     */
    public function __construct($uri, array $options = array())
    {
        parent::__construct($options);

        list($this->scheme, $this->host, $this->port)
            = $this->protocol->validateSocketUri($uri);
    }

    /**
     * Gets the canonical/normalized URI for this socket
     *
     * @return string
     */
    protected function getUri()
    {
        return sprintf(
            '%s://%s:%d',
            $this->scheme,
            $this->host,
            $this->port
        );
    }

    /**
     * @todo DNS lookup? Override getIp()?
     * @see Wrench\Socket.Socket::getName()
     */
    protected function getName()
    {
        return sprintf('%s:%s', $this->host, $this->port);
    }

    /**
     * Gets the host name
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @see Wrench\Socket.Socket::getPort()
     */
    public function getPort()
    {
        return $this->port;
    }
}
