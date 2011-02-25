<?php

namespace WebSocket;

/**
 * Simple WebSockets server
 *
 * @author Nico Kaiser <nico@kaiser.me>
 */
class Server extends Socket
{   
    private $clients = array();

    private $applications = array();

    public function __construct($host = 'localhost', $port = 8000, $max = 100)
    {
        parent::__construct($host, $port, $max);

        $this->log('Server created');
    }

    public function run()
    {
        while (true) {
            $changed_sockets = $this->allsockets;
            @socket_select($changed_sockets, $write = NULL, $except = NULL, 1);
            foreach ($this->applications as $application) {
                $application->onTick();
            }
            foreach ($changed_sockets as $socket) {
                if ($socket == $this->master) {
                    if (($ressource = socket_accept($this->master)) < 0) {
                        $this->log('Socket error: ' . socket_strerror(socket_last_error($ressource)));
                        continue;
                    } else {
                        $client = new Connection($this, $ressource);
                        $this->clients[$ressource] = $client;
                        $this->allsockets[] = $ressource;
                    }
                } else {
                    $client = $this->clients[$socket];
                    $bytes = @socket_recv($socket, $data, 4096, 0);
                    if ($bytes === 0) {
                        $client->onDisconnect();
                        unset($this->clients[$socket]);
                        $index = array_search($socket, $this->allsockets);
                        unset($this->allsockets[$index]);
                        unset($client);
                    } else {
                        $client->onData($data);
                    }
                }
            }
        }
    }

    public function getApplication($key)
    {
        if (array_key_exists($key, $this->applications)) {
            return $this->applications[$key];
        } else {
            return false;
        }
    }

    public function registerApplication($key, $application)
    {
        $this->applications[$key] = $application;
    }
    
    public function log($message, $type = 'info')
    {
        echo date('Y-m-d H:i:s') . ' [' . ($type ? $type : 'error') . '] ' . $message . PHP_EOL;
    }
}
