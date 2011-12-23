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

    public function __construct($host = 'localhost', $port = 8000)
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
    	if(!($this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)))
		{
			die("socket_create() failed, reason: " . socket_strerror(socket_last_error()));
		}		
		
		socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);		
		
		if(!socket_bind($this->master, $host, $port))
		{
			die("socket_bind() failed, reason: " . socket_strerror(socket_last_error($this->master)));
		}		
		
		if(!socket_listen($this->master, 5))
		{
			die("socket_listen() failed, reason: " . socket_strerror(socket_last_error($this->master)));
		}		
		
		$this->allsockets[] = $this->master;
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