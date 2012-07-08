<?php

namespace Wrench\Listener;

use Wrench\Server;

class RateLimiter implements Listener
{
    /**
     * @see Wrench\Listener.Listener::listen()
     */
    public function listen(Server $server)
    {
        $server->addListener(
            Server::EVENT_SOCKET_CONNECT,
            array($this, 'onSocketConnect')
        );

        $server->addListener(
            Server::EVENT_CLIENT_DATA,
            array($this, 'onClientData')
        );
    }

    public function onSocketConnect($socket, $connection)
    {
        throw new \Exception();
        die('eyah!');
    }

    public function onClientData($socket, $connection)
    {
        throw new \Exception();
        die('do some rate limiting');
    }

    /**
     * Adds a new ip to ip storage.
     *
     * @param string $ip An ip address.
     */
    private function _addIpToStorage($ip)
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

    /**
     * Removes an ip from ip storage.
     *
     * @param string $ip An ip address.
     * @return bool True if ip could be removed.
     */
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

    /**
     * Checks if an ip has reached the maximum connection limit.
     *
     * @param string $ip An ip address.
     * @return bool False if ip has reached max. connection limit. True if connection is allowed.
     */
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

    /**
     * Checkes if a client has reached its max. requests per minute limit.
     *
     * @param string $clientId A client id. (unique client identifier)
     * @return bool True if limit is not yet reached. False if request limit is reached.
     */
    private function _checkRequestLimit($clientId)
    {
        // no data in storage - no danger:
        if(!isset($this->_requestStorage[$clientId]))
        {
            $this->_requestStorage[$clientId] = array(
                'lastRequest' => time(),
                'totalRequests' => 1
            );
            return true;
        }

        // time since last request > 1min - no danger:
        if(time() - $this->_requestStorage[$clientId]['lastRequest'] > 60)
        {
            $this->_requestStorage[$clientId] = array(
                'lastRequest' => time(),
                'totalRequests' => 1
            );
            return true;
        }

        // did requests in last minute - check limits:
        if($this->_requestStorage[$clientId]['totalRequests'] > $this->_maxRequestsPerMinute)
        {
            return false;
        }

        $this->_requestStorage[$clientId]['totalRequests']++;
        return true;
    }

    /**
     * Adds a domain to the allowed origin storage.
     *
     * @param sting $domain A domain name from which connections to server are allowed.
     * @return bool True if domain was added to storage.
     */
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

    /**
     * Sets value for the max. connection per ip to this server.
     *
     * @param int $limit Connection limit for an ip.
     * @return bool True if value could be set.
     */
    public function setMaxConnectionsPerIp($limit)
    {
        if(!is_int($limit))
        {
            return false;
        }
        $this->_maxConnectionsPerIp = $limit;
        return true;
    }

    /**
     * Returns the max. connections per ip value.
     *
     * @return int Max. simoultanous  allowed connections for an ip to this server.
     */
    public function getMaxConnectionsPerIp()
    {
        return $this->_maxConnectionsPerIp;
    }

    /**
     * Sets how many requests a client is allowed to do per minute.
     *
     * @param int $limit Requets/Min limit (per client).
     * @return bool True if value could be set.
     */
    public function setMaxRequestsPerMinute($limit)
    {
        if(!is_int($limit))
        {
            return false;
        }
        $this->_maxRequestsPerMinute = $limit;
        return true;
    }

    /**
     * Sets how many clients are allowed to connect to server until no more
     * connections are accepted.
     *
     * @param in $max Max. total connections to server.
     * @return bool True if value could be set.
     */
    public function setMaxClients($max)
    {
        if((int)$max === 0)
        {
            return false;
        }
        $this->_maxClients = (int)$max;
        return true;
    }

    /**
     * Returns total max. connection limit of server.
     *
     * @return int Max. connections to this server.
     */
    public function getMaxClients()
    {
        return $this->_maxClients;
    }

    public function onConnect(Connection $connection)
    {
                if(count($this->clients) > $this->_maxClients)
            {
                $connection->onDisconnect();
                if($this->getApplication('status') !== false)
                {
                    $this->getApplication('status')->statusMsg('Attention: Client Limit Reached!', 'warning');
                }
                continue;
            }

            $this->_addIpToStorage($connection->getClientIp());
            if($this->_checkMaxConnectionsPerIp($connection->getClientIp()) === false)
            {
                $connection->onDisconnect();
                if($this->getApplication('status') !== false)
                {
                    $this->getApplication('status')->statusMsg('Connection/Ip limit for ip ' . $connection->getClientIp() . ' was reached!', 'warning');
                }
                continue;
            }
    }
}