<?php

namespace WebSocket;

/**
 * Socket class
 *
 * @author Moritz Wutz <moritzwutz@gmail.com>
 * @author Nico Kaiser <nico@kaiser.me>
 * @version 0.2
 */

/**
 * This is the main socket class
 */
class Socket
{
    /**
     * @var Socket Holds the master socket
     */
    protected $master;

    /**
     * @var array Holds all connected sockets
     */
    protected $allsockets = array();

    public function __construct($host = 'localhost', $port = 8000, $max = 100)
    {
        ob_implicit_flush(true);
        $this->createSocket($host, $port);
    }

    /**
     * Create a socket on given host/port
     * 
     * @param string $host The host/bind address to use
     * @param int $port The actual port to bind on
     */
    private function createSocket($host, $port)
    {
        if (($this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0) {
            die("socket_create() failed, reason: " . socket_strerror($this->master));
        }

        self::console("Socket {$this->master} created.");

        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);
        #socket_set_option($master,SOL_SOCKET,SO_KEEPALIVE,1);

        if (($ret = socket_bind($this->master, $host, $port)) < 0) {
            die("socket_bind() failed, reason: " . socket_strerror($ret));
        }

        self::console("Socket bound to {$host}:{$port}.");

        if (($ret = socket_listen($this->master, 5)) < 0) {
            die("socket_listen() failed, reason: " . socket_strerror($ret));
        }

        self::console('Start listening on Socket.');

        $this->allsockets[] = $this->master;
    }

    /**
     * Log a message
     *
     * @param string $msg The message
     * @param string $type The type of the message
     */
    protected function console($msg, $type='System')
    {
        /* $msg = explode("\n", $msg);
        foreach ($msg as $line)
            echo date('Y-m-d H:i:s') . " {$type}: {$line}\n"; */
    }

    /**
     * Sends a message over the socket
     * @param socket $client The destination socket
     * @param string $msg The message
     */
    protected function send($client, $msg)
    {
        socket_write($client, $msg, strlen($msg));
    }

}
