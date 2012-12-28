-------------------------
Wrench\\ConnectionManager
-------------------------

.. php:namespace: Wrench

.. php:class:: ConnectionManager

    .. php:attr:: server

        protected Server

    .. php:attr:: socket

        protected Socket

        Master socket

    .. php:attr:: connections

        protected array<int

        An array of client connections

    .. php:attr:: resources

        protected array<int

        An array of raw socket resources, corresponding to connections, roughly

    .. php:attr:: options

        protected array

    .. php:attr:: protocol

        protected Protocol

    .. php:method:: __construct(Server $server, $options = array())

        Constructor

        :type $server: Server
        :param $server:
        :type $options: array
        :param $options:

    .. php:method:: count()

    .. php:method:: configure($options)

        :param $options:

    .. php:method:: getApplicationForPath($path)

        Gets the application associated with the given path

        :type $path: string
        :param $path:

    .. php:method:: configureMasterSocket()

        Configures the main server socket

    .. php:method:: listen()

        Listens on the main socket

        :returns: void

    .. php:method:: getAllResources()

        Gets all resources

        :returns: array<int => resource)

    .. php:method:: getConnectionForClientSocket($socket)

        Returns the Connection associated with the specified socket resource

        :type $socket: resource
        :param $socket:
        :returns: Connection

    .. php:method:: selectAndProcess()

        Select and process an array of resources

    .. php:method:: processMasterSocket()

        Process events on the master socket ($this->socket)

        :returns: void

    .. php:method:: createConnection($resource)

        Creates a connection from a socket resource

        The create connection object is based on the options passed into the
        constructor ('connection_class', 'connection_options'). This connection
        instance and its associated socket resource are then stored in the
        manager.

        :type $resource: resource
        :param $resource: A socket resource
        :returns: Connection

    .. php:method:: processClientSocket($socket)

        Process events on a client socket

        :type $socket: resource
        :param $socket:

    .. php:method:: resourceId($resource)

        This server makes an explicit assumption: PHP resource types may be cast
        to a integer. Furthermore, we assume this is bijective. Both seem to be
        true in most circumstances, but may not be guaranteed.

        This method (and $this->getResourceId()) exist to make this assumption
        explicit.

        This is needed on the connection manager as well as on resources

        :type $resource: resource
        :param $resource:

    .. php:method:: getUri()

        Gets the connection manager's listening URI

        :returns: string

    .. php:method:: log($message, $priority = 'info')

        Logs a message

        :type $message: string
        :param $message:
        :type $priority: string
        :param $priority:

    .. php:method:: getServer()

        :returns: \Wrench\Server

    .. php:method:: removeConnection(Connection $connection)

        Removes a connection

        :type $connection: Connection
        :param $connection:

    .. php:method:: configureProtocol()

        Configures the protocol option
