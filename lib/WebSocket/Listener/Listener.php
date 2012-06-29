<?php

namespace WebSocket\Listener;

interface Listener
{
    public function listen(Server $server);
}