<?php
namespace WebSocket;

/**
 * Simple WebSockets server
 *
 * @author Nico Kaiser <nico@kaiser.me>
 * @author Simon Samtleben <web@lemmingzshadow.net>
 */
class Server extends Socket
{   
    private $clients = array();
    private $applications = array();
	private $_ipStorage = array();
	
	// server settings:
	private $_checkOrigin = true;
	private $_allowedOrigins = array();
	private $_maxConnectionsPerIp = 5;
	private $_maxRequestsPerMinute = 20; // @todo

    public function __construct($host = 'localhost', $port = 8000, $max = 100)
    {
        parent::__construct($host, $port, $max);
        $this->log('Server created');
    }

	public function run()
	{
		while(true)
		{
			$changed_sockets = $this->allsockets;
			socket_select($changed_sockets, $write = null, $except = null, 1);
			foreach($changed_sockets as $socket)
			{
				if($socket == $this->master)
				{
					if(($ressource = socket_accept($this->master)) < 0)
					{
						$this->log('Socket error: ' . socket_strerror(socket_last_error($ressource)));
						continue;
					}
					else
					{
						$client = new Connection($this, $ressource);						
						$this->clients[(int)$ressource] = $client;
						$this->allsockets[] = $ressource;
						$this->_addIpToStoragee($client->getClientIp());
						if($this->_checkMaxConnectionsPerIp($client->getClientIp()) === false)
						{
							$client->onDisconnect();
						}						
					}
				}
				else
				{
					$client = $this->clients[(int)$socket];
					$bytes = socket_recv($socket, $data, 4096, 0);
					if($bytes === 0)
					{
						$client->onDisconnect();						
					}
					else
					{
						$client->onData($data);
					}
				}
			}
		}
	}

	public function getApplication($key)
	{
		if(array_key_exists($key, $this->applications))
		{
			return $this->applications[$key];
		}
		else
		{
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
	
	public function removeClient($resource)
	{
		$client = $this->clients[(int)$resource];
		$this->_removeIpFromStorage($client->getClientIp());
		unset($this->clients[(int)$resource]);
		$index = array_search($resource, $this->allsockets);
		unset($this->allsockets[$index]);
		unset($client);		
	}
	
	public function checkOrigin($domain)
	{
		$domain = str_replace('http://', '', $domain);
		$domain = str_replace('www.', '', $domain);
		$domain = str_replace('/', '', $domain);
		
		return isset($this->_allowedOrigins[$domain]);
	}
	
	private function _addIpToStoragee($ip)
	{
		if(isset($this->_ipStorage[$ip]))
		{
			$this->_ipStorage[$ip]++;
		}
		else
		{
			$this->_ipStorage[$ip] = 1;
		}		
	}
	
	private function _removeIpFromStorage($ip)
	{
		if(!isset($this->_ipStorage[$ip]))
		{
			return false;
		}
		if($this->_ipStorage[$ip] === 1)
		{
			unset($this->_ipStorage[$ip]);
			return true;
		}
		$this->_ipStorage[$ip]--;
		
		return true;
	}
	
	private function _checkMaxConnectionsPerIp($ip)
	{
		if(empty($ip))
		{
			return false;
		}
		if(!isset ($this->_ipStorage[$ip]))
		{
			return true;
		}
		return ($this->_ipStorage[$ip] > $this->_maxConnectionsPerIp) ? false : true;
	}

	// Getter/Setter Methods...
	
	public function setCheckOrigin($doOriginCheck)
	{
		if(is_bool($doOriginCheck) === false)
		{
			return false;
		}
		$this->_checkOrigin = $doOriginCheck;
		return true;
	}
	
	public function getCheckOrigin()
	{
		return $this->_checkOrigin;
	}


	public function setAllowedOrigin($domain)
	{
		$domain = str_replace('http://', '', $domain);
		$domain = str_replace('www.', '', $domain);
		$domain = (strpos($domain, '/') !== false) ? substr($domain, 0, strpos($domain, '/')) : $domain;
		if(empty($domain))
		{
			return false;
		}
		$this->_allowedOrigins[$domain] = true;		
		return true;
	}
	
	public function setMaxConnectionsPerIp($limit)
	{
		if(!is_int($limit))
		{
			return false;
		}
		$this->_maxConnectionsPerIp = $limit;
		return true;
	}
	
	public function getMaxConnectionsPerIp()
	{
		return $this->_maxConnectionsPerIp;
	}
	
	public function setMaxRequestsPerMinute($limit)
	{
		if(!is_int($limit))
		{
			return false;
		}
		$this->_maxRequestsPerMinute = $limit;
		return true;
	}
}