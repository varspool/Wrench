-----------------------------
Wrench\\Listener\\RateLimiter
-----------------------------

.. php:namespace: Wrench\\Listener

.. php:class:: RateLimiter

    .. php:attr:: server

        protected Server

        The server being limited

    .. php:attr:: ips

        protected array<int>

        Connection counts per IP address

    .. php:attr:: requests

        protected array<array<int>>

        Request tokens per IP address

    .. php:attr:: options

        protected array

    .. php:attr:: protocol

        protected Protocol

    .. php:method:: __construct($options = array())

        Constructor

        :type $options: array
        :param $options:

    .. php:method:: configure($options)

        :type $options: array
        :param $options:

    .. php:method:: listen(Server $server)

        :type $server: Server
        :param $server:

    .. php:method:: onSocketConnect($socket, $connection)

        Event listener

        :type $socket: resource
        :param $socket:
        :type $connection: Connection
        :param $connection:

    .. php:method:: onSocketDisconnect($socket, $connection)

        Event listener

        :type $socket: resource
        :param $socket:
        :type $connection: Connection
        :param $connection:

    .. php:method:: onClientData($socket, $connection)

        Event listener

        :type $socket: resource
        :param $socket:
        :type $connection: Connection
        :param $connection:

    .. php:method:: checkConnections($connection)

        Idempotent

        :type $connection: Connection
        :param $connection:

    .. php:method:: checkConnectionsPerIp($connection)

        NOT idempotent, call once per connection

        :type $connection: Connection
        :param $connection:

    .. php:method:: releaseConnection($connection)

        NOT idempotent, call once per disconnection

        :type $connection: Connection
        :param $connection:

    .. php:method:: checkRequestsPerMinute($connection)

        NOT idempotent, call once per data

        :type $connection: Connection
        :param $connection:

    .. php:method:: limit($connection, $limit)

        Limits the given connection

        :type $connection: Connection
        :param $connection:
        :type $limit: string
        :param $limit: Reason

    .. php:method:: log($message, $priority = 'info')

        Logger

        :type $message: string
        :param $message:
        :type $priority: string
        :param $priority:

    .. php:method:: configureProtocol()

        Configures the protocol option
