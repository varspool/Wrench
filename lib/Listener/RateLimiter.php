<?php

namespace Wrench\Listener;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Wrench\Connection;
use Wrench\Exception\RateLimiterException;
use Wrench\Server;
use Wrench\Util\Configurable;

class RateLimiter extends Configurable implements Listener, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * The server being limited
     *
     * @var Server
     */
    protected $server;

    /**
     * Connection counts per IP address
     *
     * @var array<int>
     */
    protected $ips = [];

    /**
     * Request tokens per IP address
     *
     * @var array<array<int>>
     */
    protected $requests = [];

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $this->logger = new NullLogger();
    }

    public function listen(Server $server): void
    {
        $this->server = $server;

        $server->addListener(
            Server::EVENT_SOCKET_CONNECT,
            [$this, 'onSocketConnect']
        );

        $server->addListener(
            Server::EVENT_SOCKET_DISCONNECT,
            [$this, 'onSocketDisconnect']
        );

        $server->addListener(
            Server::EVENT_CLIENT_DATA,
            [$this, 'onClientData']
        );
    }

    /**
     * Event listener
     *
     * @param resource   $socket
     * @param Connection $connection
     */
    public function onSocketConnect($socket, Connection $connection): void
    {
        $this->checkConnections($connection);
        $this->checkConnectionsPerIp($connection);
    }

    /**
     * Idempotent
     *
     * @param Connection $connection
     */
    protected function checkConnections($connection)
    {
        $connections = $connection->getConnectionManager()->count();

        if ($connections > $this->options['connections']) {
            $this->limit($connection, 'Max connections');
        }
    }

    /**
     * Limits the given connection
     *
     * @param Connection $connection
     * @param string     $limit Reason
     */
    protected function limit($connection, $limit)
    {
        $this->logger->notice(sprintf(
            'Limiting connection %s: %s',
            $connection->getIp(),
            $limit
        ));

        $connection->close(new RateLimiterException($limit));
    }

    /**
     * NOT idempotent, call once per connection
     *
     * @param Connection $connection
     */
    protected function checkConnectionsPerIp($connection)
    {
        $ip = $connection->getIp();

        if (!$ip) {
            $this->logger->warning('Cannot check connections per IP');
            return;
        }

        if (!isset($this->ips[$ip])) {
            $this->ips[$ip] = 1;
        } else {
            $this->ips[$ip] = min(
                $this->options['connections_per_ip'],
                $this->ips[$ip] + 1
            );
        }

        if ($this->ips[$ip] > $this->options['connections_per_ip']) {
            $this->limit($connection, 'Connections per IP');
        }
    }

    /**
     * Event listener
     *
     * @param resource   $socket
     * @param Connection $connection
     */
    public function onSocketDisconnect($socket, Connection $connection): void
    {
        $this->releaseConnection($connection);
    }

    /**
     * NOT idempotent, call once per disconnection
     *
     * @param Connection $connection
     */
    protected function releaseConnection(Connection $connection): void
    {
        $ip = $connection->getIp();

        if (!$ip) {
            $this->logger->warning('Cannot release connection');
            return;
        }

        if (!isset($this->ips[$ip])) {
            $this->ips[$ip] = 0;
        } else {
            $this->ips[$ip] = max(0, $this->ips[$ip] - 1);
        }

        unset($this->requests[$connection->getId()]);
    }

    /**
     * Event listener
     *
     * @param resource   $socket
     * @param Connection $connection
     */
    public function onClientData($socket, Connection $connection): void
    {
        $this->checkRequestsPerMinute($connection);
    }

    /**
     * NOT idempotent, call once per data
     *
     * @param Connection $connection
     */
    protected function checkRequestsPerMinute(Connection $connection)
    {
        $id = $connection->getId();

        if (!isset($this->requests[$id])) {
            $this->requests[$id] = [];
        }

        // Add current token
        $this->requests[$id][] = time();

        // Expire old tokens
        while (reset($this->requests[$id]) < time() - 60) {
            array_shift($this->requests[$id]);
        }

        if (count($this->requests[$id]) > $this->options['requests_per_minute']) {
            $this->limit($connection, 'Requests per minute');
        }
    }

    /**
     * @param array $options
     */
    protected function configure(array $options): void
    {
        $options = array_merge([
            'connections' => 200, // Total
            'connections_per_ip' => 5,   // At once
            'requests_per_minute' => 200  // Per connection
        ], $options);

        parent::configure($options);
    }
}
