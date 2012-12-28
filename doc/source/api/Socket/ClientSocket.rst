----------------------------
Wrench\\Socket\\ClientSocket
----------------------------

.. php:namespace: Wrench\\Socket

.. php:class:: ClientSocket

    Options:
     - timeout_connect      => int, seconds, default 2

    .. php:const:: TIMEOUT_CONNECT

        Default connection timeout

    .. php:const:: TIMEOUT_SOCKET

        Default timeout for socket operations (reads, writes)

    .. php:const:: DEFAULT_RECEIVE_LENGTH

    .. php:const:: NAME_PART_IP

        Socket name parts

    .. php:attr:: scheme

        protected

    .. php:attr:: host

        protected

    .. php:attr:: port

        protected

    .. php:attr:: socket

        protected resource

    .. php:attr:: context

        protected

        Stream context

    .. php:attr:: connected

        protected boolean

        Whether the socket is connected to a server

        Note, the connection may not be ready to use, but the socket is connected
        at least. See $handshaked, and other properties in subclasses.

    .. php:attr:: firstRead

        protected boolean

        Whether the current read is the first one to the socket

    .. php:attr:: name

        protected string

        The socket name according to stream_socket_get_name

    .. php:attr:: options

        protected array

    .. php:attr:: protocol

        protected Protocol

    .. php:method:: configure($options)

        :param $options:

    .. php:method:: connect()

        Connects to the given socket

    .. php:method:: reconnect()

    .. php:method:: getSocketStreamContextOptions()

    .. php:method:: getSslStreamContextOptions()

    .. php:method:: __construct($uri, $options = array())

        URI Socket constructor

        :type $uri: string
        :param $uri: WebSocket URI, e.g. ws://example.org:8000/chat
        :param $options:

    .. php:method:: getUri()

        Gets the canonical/normalized URI for this socket

        :returns: string

    .. php:method:: getName()

    .. php:method:: getHost()

        Gets the host name

    .. php:method:: getPort()

    .. php:method:: getStreamContext($listen = false)

        Gets a stream context

        :param $listen:

    .. php:method:: getNamePart($name, $part)

        Gets part of the name of the socket

        PHP seems to return IPV6 address/port combos like this:
        ::1:1234, where ::1 is the address and 1234 the port So, the part number
        here is either the last : delimited section (the port)
        or all the other sections (the whole initial part, the address).

        :type $name: string
        :param $name: (from $this->getName() usually)
        :param $part:
        :returns: string

    .. php:method:: getIp()

        Gets the IP address of the socket

        :returns: string

    .. php:method:: getLastError()

        Get the last error that occurred on the socket

        :returns: int|string

    .. php:method:: isConnected()

        Whether the socket is currently connected

        :returns: boolean

    .. php:method:: disconnect()

        Disconnect the socket

        :returns: void

    .. php:method:: getResource()

    .. php:method:: getResourceId()

    .. php:method:: send($data)

        :type $data: unknown_type
        :param $data:
        :returns: boolean|int The number of bytes sent or false on error

    .. php:method:: receive($length = self::DEFAULT_RECEIVE_LENGTH)

        Recieve data from the socket

        :type $length: int
        :param $length:
        :returns: string

    .. php:method:: configureProtocol()

        Configures the protocol option
