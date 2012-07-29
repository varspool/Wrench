-------------------------
Wrench\\ConnectionManager
-------------------------

.. php:namespace: Wrench

.. php:class:: ConnectionManager

    .. php:attr:: server

    .. php:attr:: socket

        Master socket

    .. php:attr:: connections

        An array of client connections

    .. php:attr:: resources

        An array of raw socket resources, corresponding to connections, roughly

    .. php:attr:: options

    .. php:attr:: protocol

    .. php:method:: __construct(Server $server, array $options = Array)

        Constructor

        :param Server $server:
        :param array $options:

    .. php:method:: count()

    .. php:method:: configure($options)

        :param unknown $options:

    .. php:method:: getApplicationForPath(string $path)

        Gets the application associated with the given path

        :param string $path:

    .. php:method:: configureMasterSocket()

        Configures the main server socket

    .. php:method:: listen()

        Listens on the main socket

        :returns: void

    .. php:method:: getAllResources()

        Gets all resources

        :returns: array<int => resource)

    .. php:method:: getConnectionForClientSocket(resource $socket)

        Returns the Connection associated with the specified socket resource

        :param resource $socket:
        :returns: Connection

    .. php:method:: selectAndProcess()

        Select and process an array of resources

    .. php:method:: processMasterSocket()

        Process events on the master socket ($this->socket)

        :returns: void

    .. php:method:: createConnection(resource $resource)

        Creates a connection from a socket resource

        The create connection object is based on the options passed into the
        constructor ('connection_class', 'connection_options'). This connection
        instance and its associated socket resource are then stored in the
        manager.

        :param resource $resource: A socket resource
        :returns: Connection

    .. php:method:: processClientSocket(resource $socket)

        Process events on a client socket

        :param resource $socket:

    .. php:method:: resourceId(resource $resource)

        This server makes an explicit assumption: PHP resource types may be cast
        to a integer. Furthermore, we assume this is bijective. Both seem to be
        true in most circumstances, but may not be guaranteed.

        This method (and $this->getResourceId()) exist to make this assumption
        explicit.

        This is needed on the connection manager as well as on resources

        :param resource $resource:

    .. php:method:: getUri()

        Gets the connection manager's listening URI

        :returns: string

    .. php:method:: log(string $message, string $priority = info)

        Logs a message

        :param string $message:
        :param string $priority:

    .. php:method:: getServer()

        :returns: \Wrench\Server

    .. php:method:: removeConnection(Connection $connection)

        Removes a connection

        :param Connection $connection:

    .. php:method:: configureProtocol()

        Configures the protocol option
