<?php
/**
 * A webchat application.
 * 
 * @author Simon Samtleben <web@lemmingzshadow.net>
 */

namespace WebSocket\Application;

class ChatApplication extends Application
{
    private $_clients = array();
	private $_nicknames = array();
	private $_floodProtection = array();

	public function onConnect($client)
    {
		$id = $client->getClientId();
        $this->_clients[$id] = $client;
		
		$this->_clients[$id]->send('', 'ping');
    }

    public function onDisconnect($client)
    {
        $id = $client->getClientId();
		unset($this->_nicknames[$id]);
		unset($this->_clients[$id]);     
    }

	public function onData($data, $client)
    {
		$decodedData = json_decode($data);
		if($decodedData === null)
		{
			return false;
		}
		
		// handle action:
		if(!method_exists($this, '_action'.  ucfirst($decodedData->action)))
		{
			return false;			
		}
		call_user_func(array($this,  '_action'.  ucfirst($decodedData->action)), $decodedData->data, $client);
		return true;
    }
	
	private function _sendClient($idClient, $data, $action = 'msg')
	{
		$payload = $this->_encodePayload($data, $action);
		$this->_clients[$idClient]->send($payload);
	}
	
	private function _sendAll($data, $action = 'msg')
	{
		$payload = $this->_encodePayload($data, $action);
		foreach(array_keys($this->_clients) as $clientId)
		{
            $this->_clients[$clientId]->send($payload);
        }
	}
	
	private function _encodePayload($data, $action = 'msg')
	{
		$payload = array(
			'action' => $action,
			'data' => $data
		);
		return json_encode($payload);
	}
	
	private function _actionMsg($data, $client)
	{
		$clientId = $client->getClientId();
		if(!isset($this->_nicknames[$clientId]))
		{
			$this->_sendClient($clientId, 'Please select a nick to send messages.');
			return false;
		}
		if($this->_floodCheck($clientId) === true)
		{
			$this->_sendClient($clientId, 'Flood Protection. No hammering please!');
			return false;
		}
		$data = htmlentities(strip_tags($data));
		$data = '['.$this->_nicknames[$clientId].'] ' . $data; 
		$this->_sendAll($data);
	}
	
	private function _actionNickselect($data, $client)
	{
		$idClient = $client->getClientId();
		$nick = preg_replace('#[^a-z0-9]#i', '', $data);
		if(empty($nick))
		{
			$this->_sendClient($idClient, 'Invalid nickname.');
			return false;
		}
		if(in_array($nick, $this->_nicknames))
		{
			$this->_sendClient($idClient, 'Sry, this nick is already in use.');
			return false;
		}
		
		$this->_nicknames[$idClient] = $nick;
		$this->_sendClient($idClient, 'Your nick is now ' . $nick);
	}
	
	private function _floodCheck($idClient)
	{
		if(!isset($this->_floodProtection[$idClient]))
		{
			$this->_floodProtection[$idClient] = array(
				'last_msg' => time(),
				'count' => 1
			);
			return false;
		}
		
		if(time() - $this->_floodProtection[$idClient]['last_msg'] < 10)
		{
			if($this->_floodProtection[$idClient]['count'] === 5)
			{
				return true;
			}
			else
			{
				$this->_floodProtection[$idClient]['count']++;
				return false;
			}
		}
		else
		{
			$this->_floodProtection[$idClient] = array(
				'last_msg' => time(),
				'count' => 1
			);
			return false;
		}
	}
}