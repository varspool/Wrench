<?php
/**
 * Displays server-status. Connected clients, e.g.
 * 
 * @author Simon Samtleben <web@lemmingzshadow.net>
 */

namespace WebSocket\Application;

class StatusApplication extends Application
{
    private $clients = array();

    public function onConnect($client)
    {
        $this->clients[] = $client;		
    }

    public function onDisconnect($client)
    {
        $key = array_search($client, $this->clients);
        if($key)
		{
            unset($this->clients[$key]);
        }
    }

	public function onData($data, $client)
    {
		// currently client can not send any data.
        return false;
    }
	
	public function clientConnected($id, $ip, $port)
	{		
		$this->_sendAll('Client connected: ' . $id . ' (' . $ip . ':' . $port . ')');
	}
	
	public function clientDisconnected($id, $ip, $port)
	{
		$this->_sendAll('Client disconnected: ' . $id . ' (' . $ip . ':' . $port . ')');
	}
	
	private function _sendAll($data)
	{
		foreach($this->clients as $sendto)
		{
            $sendto->send($data);
        }
	}
}