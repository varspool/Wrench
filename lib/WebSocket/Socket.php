<?php

namespace WebSocket;

use WebSocket\Protocol\Protocol;
use WebSocket\Protocol\Rfc6455Protocol;
use \InvalidArgumentException;
use \RuntimeException;

/**
 * Socket class
 *
 * Implements low level logic for connecting, serving, reading to, and
 * writing from WebSocket connections using PHP's streams.
 *
 * Unlike in previous versions of this library, a Socket instance now
 * represents a single underlying socket resource. It's designed to be used
 * by aggregation, rather than inheritence.
 */
class Socket
{
    /**
     * Default connection timeout
     *
     * @var int seconds
     */
    const TIMEOUT_CONNECT = 2;

    /**
     * Default timeout for socket operations (reads, writes)
     *
     * @var int seconds
     */
    const TIMEOUT_SOCKET = 5;

    /**
     * @var int
     */
    const DEFAULT_RECEIVE_LENGTH = '1400';

    /**
     * @var resource
     */
    private $socket = null;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Stream context
     */
    protected $context = null;

    protected $scheme;
    protected $host;
    protected $port;
    protected $connected = false;
    protected $firstRead = true;

    /**
     * Socket constructor
     *
     * @param string $uri     WebSocket URI, e.g. ws://example.org:8000/chat
     * @param array  $options (optional)
     *   Options:
     *     - protocol             => WebSocket\Protocol object, latest protocol
     *                                 version used if not specified
     *     - timeout_connect      => int, seconds, default 2
     *     - timeout_socket       => int, seconds, default 5
     *     - server_ssl_cert_file => string, server SSL certificate
     *                                 file location. File should contain
     *                                 certificate and private key
     *     - server_ssl_passphrase => string, passphrase for the key
     *     - server_ssl_allow_self_signed => boolean, whether to allows self-
     *                                 signed certs
     */
    public function __construct(
        $uri,
        array $options = array()
    ) {
        $this->configure($options);
        $this->configureProtocol();

        list($this->scheme, $this->host, $this->port) = $this->protocol->validateSocketUri($uri);
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
            'timeout_connect' => self::TIMEOUT_CONNECT,
            'timeout_socket'  => self::TIMEOUT_SOCKET
        ), $options);
    }

    /**
     * Configures the protocol option
     *
     * @throws InvalidArgumentException
     */
    protected function configureProtocol()
    {
        $protocol = $this->options['protocol'];

        if (!$protocol || !($protocol instanceof Protocol)) {
            throw new InvalidArgumentException('Invalid protocol option');
        }

        $this->protocol = $protocol;
    }

    /**
     * Whether the socket is currently connected
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * Connects to the given socket
     */
    public function connect()
    {
        if ($this->isConnected()) {
            return true;
        }

        $errno = null;
        $errstr = null;

        $this->socket = stream_socket_client(
            sprintf('tcp://%s:%d', $this->host, $this->port),
            $errno,
            $errstr,
            $this->options['timeout_connect'],
            STREAM_CLIENT_CONNECT,
            $this->getStreamContext()
        );

        if (!$this->socket) {
            throw new ConnectionException(sprintf(
                'Could not connect to socket: %s (%d)',
                $errstr,
                $errno
            ));
        }

        stream_set_timeout($this->socket, $this->options['timeout_socket']);

        return ($this->connected = true);
    }

    public function reconnect()
    {
        $this->disconnect();
        $this->connect();
    }

    /**
     * Disconnect the socket
     *
     * @return void
     */
    public function disconnect()
    {
        if ($this->socket) {
            fclose($this->socket);
        }
        $this->socket = null;
        $this->connected = false;
    }

    /**
     * Gets a stream context
     */
    protected function getStreamContext()
    {
        $options = array();

        if ($this->scheme == Protocol::SCHEME_UNDERLYING_SECURE) {
            $options['ssl'] = $this->getSslStreamContextOptions();
        }

        return stream_context_create(
            $options,
            array()
        );
    }

    public function getResource()
    {
        return $this->socket;
    }

    protected function getSslStreamContextOptions()
    {
        $options = array();

        if (isset($this->options['server_ssl_cert_file'])) {
            $options['local_cert'] = $this->options['server_ssl_cert_file'];
            if (isset($this->options['server_ssl_passphrase'])) {
                $options['passphrase'] = $this->options['server_ssl_passphrase'];
            }
        }

        if ($this->options['server_ssl_allow_self_signed']) {
            $options['allow_self_signed'] = true;
        }

        return array(
            'local_cert'        => $this->options['server_ssl_cert_file'],
            'passphrase'        => $this->options['server_ssl_passphrase'],
            'allow_self_signed' => true,
            'verify_peer'       => false
        );
    }

    public function send($data)
    {
        if (!$this->isConnected()) {
            throw new RuntimeException('Socket is not connected');
        }

        $length = strlen($data);

        if ($length == 0) {
            return true;
        }

        for ($i = $length; $i > 0; $i -= $written) {
            $written = fwrite($this->socket, substr($data, -1 * $i));
            if ($written === false) {
                return false;
            } elseif ($written === 0) {
                return false;
            }
        }

        return $length;
    }

    public function receive($length = self::DEFAULT_RECEIVE_LENGTH)
    {
        if (!$this->isConnected()) {
            throw new RuntimeException('Socket is not connected');
        }

        $remaining = $length;

        $buffer = '';
        $metadata['unread_bytes'] = 0;

        do {
            if (feof($this->socket)) {
                return $buffer;
            }

            $result = fread($this->socket, $length);
            $this->firstRead = false;

            if ($result === false) {
                return $buffer;
            }

            $buffer .= $result;

            if (feof($this->socket)) {
                return $buffer;
            }

            $continue = false;

            if ($this->firstRead == true && strlen($result) == 1) {
                // Workaround Chrome behavior (still needed?)
                $continue = true;
            }

            // Continue if more data to be read
            $metadata = stream_get_meta_data($this->socket);
            if ($metadata && isset($metadata['unread_bytes']) && $metadata['unread_bytes']) {
                $continue = true;
                $length = $metadata['unread_bytes'];
            }
        } while ($continue);

        return $buffer;
    }
}
