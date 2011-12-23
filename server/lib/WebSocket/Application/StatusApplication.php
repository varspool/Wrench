<?php

namespace WebSocket\Application;

/**
 * Waschsalon WSS Status Application
 * Provides live server infos/messages to client/browser.
 * 
 * @author Simon Samtleben <web@lemmingzshadow.net>
 */
class StatusApplication extends Application
{
    private $_clients = array();	
	
	public function onConnect($client)
    {
		$id = $client->getClientId();
        $this->_clients[$id] = $client;
    }

    public function onDisconnect($client)
    {
        $id = $client->getClientId();		
		unset($this->_clients[$id]);     
    }

    public function onData($data, $client)
    {		
        // currently not in use...
    }
	
	public function clientConnected($ip, $port)
	{
		$this->statusMsg('Client connected. (-> ' .$ip.':'.$port . ')');
		$data = array(
			'ip' => $ip,
			'port' => $port
		);
		$encodedData = $this->_encodeData('clientConnected', $data);
		$this->_sendAll($encodedData);
	}
	
	public function clientDisconnected($ip, $port)
	{
		$this->statusMsg('Client disconnected. (<- ' .$ip.':'.$port . ')');
		$encodedData = $this->_encodeData('clientDisconnected', $port);
		$this->_sendAll($encodedData);
	}
	
	public function statusMsg($text)
	{
		$encodedData = $this->_encodeData('statusMsg', $text);		
		$this->_sendAll($encodedData);
	}
	
	private function _sendAll($encodedData)
	{
		foreach($this->_clients as $sendto)
		{
            $sendto->send($encodedData);
        }
	}
}