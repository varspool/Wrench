<?php

namespace WebSocket\Application;

/**
 * Simple Echo WebSocket Application
 * 
 * @author Nico Kaiser <nico@kaiser.me>
 */
class EchoApplication extends Application
{
    private $clients = array();

    public function onConnect($client)
    {
        $this->clients[] = $client;
    }

    public function onDisconnect($client)
    {
        $key = array_search($client, $this->clients);
        if ($key) {
            unset($this->clients[$key]);
        }
    }

    public function onData($data, $client)
    {
        foreach ($this->clients as $sendto) {
            $sendto->send($data);
        }
    }
}