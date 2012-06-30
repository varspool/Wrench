<?php

namespace Wrench\Listener;

use Wrench\Server;

class OriginPolicy implements Listener
{
    protected $allowed = array();

    public function __construct(array $allowed)
    {
        $this->allowed = $allowed;
    }

    public function isAllowed($origin)
    {
        if (in_array($origin, $this->allowed)) {
            return true;
        }

        die('TODO better originc hecking required');

    		// check origin:
// 		if($this->server->getCheckOrigin() === true)
// 		{
// 			$origin = (isset($headers['Sec-WebSocket-Origin'])) ? $headers['Sec-WebSocket-Origin'] : false;
// 			$origin = (isset($headers['Origin'])) ? $headers['Origin'] : $origin;
// 			if($origin === false)
// 			{
// 				$this->log('No origin provided.');
// 				$this->sendHttpResponse(401);
// 				stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
// 				$this->server->removeClientOnError($this);
// 				return false;
// 			}

// 			if(empty($origin))
// 			{
// 				$this->log('Empty origin provided.');
// 				$this->sendHttpResponse(401);
// 				stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
// 				$this->server->removeClientOnError($this);
// 				return false;
// 			}

// 			if($this->server->checkOrigin($origin) === false)
// 			{
// 				$this->log('Invalid origin provided.');
// 				$this->sendHttpResponse(401);
// 				stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
// 				$this->server->removeClientOnError($this);
// 				return false;
// 			}
// 		}

        return false;
    }

    /**
     * @param Server $server
     */
    public function listen(Server $server)
    {
        $server->addListener(
            Server::EVENT_HANDSHAKE_REQUEST,
            array($this, 'onHandshakeRequest')
        );
    }
}