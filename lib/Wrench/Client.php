<?php

namespace Wrench;

use Wrench\Socket\ClientSocket;
use Wrench\Protocol\Protocol;
use Wrench\Protocol\Rfc6455Protocol;

use \InvalidArgumentException;
use \RuntimeException;

/**
 * Client class
 *
 * Represents a Wrench client
 */
class Client
{
    /**
     * @var int bytes
     */
    const MAX_HANDSHAKE_RESPONSE = '1500';

    protected $uri;
    protected $origin;
    protected $socket;

    /**
     * Request headers
     *
     * @var array
     */
    protected $headers = array();

    /**
     * Protocol instance
     *
     * @var Protocol
     */
    protected $protocol;

    /**
     * Options
     *
     * @var array
     */
    protected $options = array();

    /**
     * Whether the client is connected
     *
     * @var boolean
     */
    protected $connected = false;

    /**
     * Constructor
     *
     * @param string $uri
     * @param string $origin  The origin to include in the handshake (required
     *                          in later versions of the protocol)
     * @param array  $options (optional) Array of options
     *                         - socket   => Socket instance (otherwise created)
     *                         - protocol => Protocol
     */
    public function __construct($uri, $origin, array $options = array())
    {
        $uri = (string)$uri;
        if (!$uri) {
            throw new InvalidArgumentException('No URI specified');
        }
        $this->uri = $uri;

        $origin = (string)$origin;
        if (!$origin) {
            throw new InvalidArgumentException('No origin specified');
        }
        $this->origin = $origin;

        $this->configure($options);

        $this->socket = $this->options['socket'];
        $this->protocol = $this->options['protocol'];

        $this->protocol->validateUri($this->uri);
        $this->protocol->validateOriginUri($this->origin);
    }

    /**
     * Configure options
     *
     * @param array $options
     * @return void
     */
    protected function configure(array $options)
    {
        $this->options = array_merge(array(
            'protocol'        => new Rfc6455Protocol(),
            'socket'          => new ClientSocket($this->uri)
        ), $options);
    }

    /**
     * Adds a request header to be included in the initial handshake
     *
     * For example, to include a Cookie header
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public function addRequestHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Sends data to the socket
     *
     * @param string $data
     * @param string $type Payload type
     * @param boolean $masked
     * @return int bytes written
     */
    public function sendData($data, $type = 'text', $masked = true)
    {
        $encoded = $this->protocol->encode($data, $type, $masked);
        return $this->socket->send($encoded);
    }

    /**
     * Connect to the Wrench server
     *
     * @return boolean Whether a new connection was made
     */
    public function connect()
    {
        if ($this->isConnected()) {
            return false;
        }

        $this->socket->connect();

        $key       = $this->protocol->generateKey();
        $handshake = $this->protocol->getRequestHandshake(
            $this->uri,
            $key,
            $this->origin,
            $this->headers
        );

        $this->socket->send($handshake);
        $response = $this->socket->receive(self::MAX_HANDSHAKE_RESPONSE);
        return ($this->connected =
                    $this->protocol->validateResponseHandshake($response, $key));
    }

    /**
     * Whether the client is currently connected
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * @todo Bug: what if connect has been called twice. The first socket never
     *        gets closed.
     */
    public function disconnect()
    {
        if ($this->socket) {
            $this->socket->disconnect();
        }
        $this->connected = false;
    }
}
