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
	protected $context = null;

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
		$url = 'tcp://'.$host.':'.$port;
		$this->context = stream_context_create();
		if(!$this->master = stream_socket_server($url, $errno, $err, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $this->context))
		{
			die('Error creating socket: ' . $err);
		}
		$this->allsockets[] = $this->master;
	}  
	
	// method originally found in phpws project:
	protected function readBuffer($resource)
	{
		$buffer = '';
		$buffsize = 8192;

		$metadata['unread_bytes'] = 0;

		do
		{
			if(feof($resource))
			{
				return false;
			}

			$result = fread($resource, $buffsize);
			if($result === false || feof($resource))
			{
			        return false;
			}
			$buffer .= $result;
			$metadata = stream_get_meta_data($resource);
			$buffsize = min($buffsize, $metadata['unread_bytes']);

		} while($metadata['unread_bytes'] > 0);

		return $buffer;
	}
	
	// method originally found in phpws project:
	public function writeBuffer($resource, $string)
	{		
		for ($written = 0; $written < strlen($string); $written += $fwrite)
		{
			$fwrite = fwrite($resource, substr($string, $written));			
			if($fwrite === false)
			{
				return $written;
			}
		}
		return $written;
	}
}