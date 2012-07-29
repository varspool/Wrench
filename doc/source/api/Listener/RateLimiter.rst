---------------------------
Wrench\Listener\RateLimiter
---------------------------

.. php:class:: Wrench\Listener\RateLimiter

    .. php:attr:: server
    
        The server being limited

    .. php:attr:: ips
    
        Connection counts per IP address

    .. php:attr:: requests
    
        Request tokens per IP address

    .. php:attr:: options
    


    .. php:attr:: protocol
    


    .. php:method:: __construct(array $options = Array)
    
        Constructor
        
        :param array $options:

    .. php:method:: configure(array $options)
    
        :param array $options:

    .. php:method:: listen(Server $server)
    
        :param Server $server:

    .. php:method:: onSocketConnect(resource $socket, Connection $connection)
    
        Event listener
        
        :param resource $socket: 
        :param Connection $connection:

    .. php:method:: onSocketDisconnect(resource $socket, Connection $connection)
    
        Event listener
        
        :param resource $socket: 
        :param Connection $connection:

    .. php:method:: onClientData(resource $socket, Connection $connection)
    
        Event listener
        
        :param resource $socket: 
        :param Connection $connection:

    .. php:method:: checkConnections(Connection $connection)
    
        Idempotent
        
        :param Connection $connection:

    .. php:method:: checkConnectionsPerIp(Connection $connection)
    
        NOT idempotent, call once per connection
        
        :param Connection $connection:

    .. php:method:: releaseConnection(Connection $connection)
    
        NOT idempotent, call once per disconnection
        
        :param Connection $connection:

    .. php:method:: checkRequestsPerMinute(Connection $connection)
    
        NOT idempotent, call once per data
        
        :param Connection $connection:

    .. php:method:: limit(Connection $connection, string $limit)
    
        Limits the given connection
        
        :param Connection $connection: 
        :param string $limit: Reason

    .. php:method:: log(string $message, string $priority = info)
    
        Logger
        
        :param string $message: 
        :param string $priority:

    .. php:method:: configureProtocol()
    
        Configures the protocol option

