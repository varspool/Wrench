<?php

namespace WebSocket\Socket;

use WebSocket\Socket\Socket;

class ServerClientSocket extends Socket
{
    public function __construct($accepted_socket, array $options = array())
    {
        parent::__construct($options);

        $this->socket = $accepted_socket;
        $this->connected = true;
    }
}