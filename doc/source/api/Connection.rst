------------------
Wrench\\Connection
------------------

.. php:namespace: Wrench

.. php:class:: Connection

    Represents a client connection on the server side

    i.e. the `Server` manages a bunch of `Connection`s

    .. php:attr:: manager

        The connection manager

    .. php:attr:: socket

        Socket object

        Wraps the client connection resource

    .. php:attr:: handshaked

        Whether the connection has successfully handshaken

    .. php:attr:: application

        The application this connection belongs to

    .. php:attr:: ip

        The IP address of the client

    .. php:attr:: port

        The port of the client

    .. php:attr:: id

        Connection ID

    .. php:attr:: payload

        The current payload

    .. php:attr:: options

    .. php:attr:: protocol

    .. php:method:: __construct(ConnectionManager $manager, ServerClientSocket $socket, array $options = Array)

        Constructor

        :param ConnectionManager $manager:
        :param ServerClientSocket $socket:
        :param array $options:

    .. php:method:: getConnectionManager()

        Gets the connection manager of this connection

        :returns: \Wrench\ConnectionManager

    .. php:method:: configure($options)

        :param unknown $options:

    .. php:method:: configureClientInformation()

    .. php:method:: configureClientId()

        Configures the client ID

        We hash the client ID to prevent leakage of information if another client
        happens to get a hold of an ID. The secret *must* be lengthy, and must be
        kept secret for this to work: otherwise it's trivial to search the space
        of possible IP addresses/ports (well, if not trivial, at least very fast).

    .. php:method:: onData(string $data)

        Data receiver

        Called by the connection manager when the connection has received data

        :param string $data:

    .. php:method:: handshake(string $data)

        Performs a websocket handshake

        :param string $data:

    .. php:method:: handle(string $data)

        Handle data received from the client

        The data passed in may belong to several different frames across one or
        more protocols. It may not even contain a single complete frame. This
        method manages slotting the data into separate payload objects.

        :param string $data:

    .. php:method:: handlePayload(Payload $payload)

        Handle a complete payload received from the client

        :param Payload $payload:

    .. php:method:: send($data, string $type = )

        Sends the payload to the connection

        :param unknown $data:
        :param string $type:
        :returns: boolean

    .. php:method:: process()

        Processes data on the socket

    .. php:method:: close($code = )

        Closes the connection according to the WebSocket protocol

        :param unknown $code:
        :returns: boolean

    .. php:method:: log(string $message, string $priority = info)

        Logs a message

        :param string $message:
        :param string $priority:

    .. php:method:: getIp()

        Gets the IP address of the connection

        :returns: string Usually dotted quad notation

    .. php:method:: getPort()

        Gets the port of the connection

        :returns: int

    .. php:method:: getId()

        Gets the connection ID

        :returns: string

    .. php:method:: getSocket()

        Gets the socket object

        :returns: Socket\ServerClientSocket

    .. php:method:: getClientApplication()

        Gets the client application

        :returns: Application

    .. php:method:: configureProtocol()

        Configures the protocol option
