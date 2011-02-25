<?php

namespace WebSocket\Application;

/**
 * Simple Time sending WebSocket Application
 * 
 * @author Nico Kaiser <nico@kaiser.me>
 */
class TimeApplication extends Application
{
    private $clients = array();
    
    private $lastTime = 0;

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
    
    public function onTick()
    {
        if (time() > $this->lastTime + 3) {
            $this->lastTime = time();
            foreach ($this->clients as $sendto) {
                $sendto->send(time());
            }
        }
    }
}