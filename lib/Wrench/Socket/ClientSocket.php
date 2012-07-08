<?php
namespace Wrench\Socket;

use Wrench\Socket\UriSocket;

/**
 * Options:
 *  - timeout_connect      => int, seconds, default 2
 */
class ClientSocket extends UriSocket
{
    /**
     * Default connection timeout
     *
     * @var int seconds
     */
    const TIMEOUT_CONNECT = 2;

    /**
     * @see Wrench\Socket.Socket::configure()
     */
    protected function configure(array $options)
    {
        $options = array_merge(array(
            'timeout_connect' => self::TIMEOUT_CONNECT
        ), $options);

        parent::configure($options);
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
            $this->getUri(),
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
}
