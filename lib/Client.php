<?php

namespace Wrench;

use InvalidArgumentException;
use Wrench\Exception;
use Wrench\Payload\Payload;
use Wrench\Payload\PayloadHandler;
use Wrench\Protocol\Protocol;
use Wrench\Socket\ClientSocket;
use Wrench\Util\Configurable;

/**
 * Client class
 * Represents a websocket client
 */
class Client extends Configurable
{
    /**
     * @var int bytes
     */
    const MAX_HANDSHAKE_RESPONSE = '1500';

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $origin;

    /**
     * @var ClientSocket
     */
    protected $socket;

    /**
     * Request headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Whether the client is connected
     *
     * @var boolean
     */
    protected $connected = false;

    /**
     * @var PayloadHandler
     */
    protected $payloadHandler = null;

    /**
     * Complete received payloads
     *
     * @var array<Payload>
     */
    protected $received = [];

    /**
     * Constructor
     *
     * @param string $uri
     * @param string $origin    The origin to include in the handshake (required
     *                          in later versions of the protocol)
     * @param array  $options   (optional) Array of options
     *                          - socket   => Socket instance (otherwise created)
     *                          - protocol => Protocol
     */
    public function __construct($uri, $origin, array $options = [])
    {
        parent::__construct($options);

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

        $this->protocol->validateUri($this->uri);
        $this->protocol->validateOriginUri($this->origin);

        $this->configureSocket();
        $this->configurePayloadHandler();
    }

    /**
     * Configures the client socket
     */
    protected function configureSocket()
    {
        $class = $this->options['socket_class'];
        $options = $this->options['socket_options'];
        $this->socket = new $class($this->uri, $options);
    }

    /**
     * Configures the payload handler
     */
    protected function configurePayloadHandler()
    {
        $this->payloadHandler = new PayloadHandler([$this, 'onData'], $this->options);
    }

    /**
     * Payload receiver
     * Public because called from our PayloadHandler. Don't call us, we'll call
     * you (via the on_data_callback option).
     *
     * @param Payload $payload
     */
    public function onData(Payload $payload)
    {
        $this->received[] = $payload;
        if (($callback = $this->options['on_data_callback'])) {
            call_user_func($callback, $payload);
        }
    }

    /**
     * Adds a request header to be included in the initial handshake
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
     * @param string  $data
     * @param int     $type See Protocol::TYPE_*
     * @param boolean $masked
     * @return bool Success
     */
    public function sendData(string $data, int $type = Protocol::TYPE_TEXT, $masked = true)
    {
        if (!$this->isConnected()) {
            return false;
        }

        $payload = $this->protocol->getPayload();

        $payload->encode(
            $data,
            $type,
            $masked
        );

        return $payload->sendToSocket($this->socket);
    }

    /**
     * Returns whether the client is currently connected
     * Also checks the state of the underlying socket
     *
     * @return bool
     */
    public function isConnected()
    {
        if ($this->connected === false) {
            return false;
        }

        // Check if the socket is still connected
        if ($this->socket->isConnected() === false) {
            $this->connected = false;

            return false;
        }

        return true;
    }

    /**
     * Receives data sent by the server
     *
     * @return array<Payload> Payload received since the last call to receive()
     */
    public function receive(): ?array
    {
        if (!$this->isConnected()) {
            return null;
        }

        $data = $this->socket->receive();

        if (!$data) {
            return $data;
        }

        $old = $this->received;
        $this->payloadHandler->handle($data);
        return array_diff_assoc($this->received, $old);
    }

    /**
     * Connect to the server
     *
     * @return bool Whether a new connection was made
     */
    public function connect()
    {
        if ($this->isConnected()) {
            return false;
        }

        try {
            $this->socket->connect();
        } catch (\Exception $ex) {
            return false;
        }

        $key = $this->protocol->generateKey();
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
     * Disconnects the underlying socket, and marks the client as disconnected
     *
     * @param int $reason Reason for disconnecting. See Protocol::CLOSE_*
     * @throws Exception\FrameException
     * @throws Exception\SocketException
     */
    public function disconnect($reason = Protocol::CLOSE_NORMAL)
    {
        if ($this->connected === false) {
            return false;
        }

        $payload = $this->protocol->getClosePayload($reason);

        if ($this->socket) {
            if (!$payload->sendToSocket($this->socket)) {
                throw new Exception("Unexpected exception when sending Close frame.");
            }
            // The client SHOULD wait for the server to close the connection
            $this->socket->receive();
            $this->socket->disconnect();
        }

        $this->connected = false;

        return true;
    }

    /**
     * Configure options
     *
     * @param array $options
     * @return void
     */
    protected function configure(array $options)
    {
        $options = array_merge([
            'socket_class' => ClientSocket::class,
            'on_data_callback' => null,
            'socket_options' => [],
        ], $options);

        parent::configure($options);
    }
}
