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

    .. php:attr:: host

    .. php:attr:: port

    .. php:attr:: socket

    .. php:attr:: context

        Stream context

    .. php:attr:: connected

        Whether the socket is connected to a server

        Note, the connection may not be ready to use, but the socket is connected
        at least. See $handshaked, and other properties in subclasses.

    .. php:attr:: firstRead

        Whether the current read is the first one to the socket

    .. php:attr:: name

        The socket name according to stream_socket_get_name

    .. php:attr:: options

    .. php:attr:: protocol

    .. php:method:: configure($options)

        :param unknown $options:

    .. php:method:: connect()

        Connects to the given socket

    .. php:method:: reconnect()

    .. php:method:: getSocketStreamContextOptions()

    .. php:method:: getSslStreamContextOptions()

    .. php:method:: __construct(string $uri, $options = Array)

        URI Socket constructor

        :param string $uri:     WebSocket URI, e.g. ws://example.org:8000/chat
        :param unknown $options:

    .. php:method:: getUri()

        Gets the canonical/normalized URI for this socket

        :returns: string

    .. php:method:: getName()

    .. php:method:: getHost()

        Gets the host name

    .. php:method:: getPort()

    .. php:method:: getStreamContext($listen = )

        Gets a stream context

        :param unknown $listen:

    .. php:method:: getNamePart(string $name, $part)

        Gets part of the name of the socket

        PHP seems to return IPV6 address/port combos like this:
        ::1:1234, where ::1 is the address and 1234 the port So, the part number
        here is either the last : delimited section (the port)
        or all the other sections (the whole initial part, the address).

        :param string $name: (from $this->getName() usually)
        :param unknown $part:
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

    .. php:method:: send(unknown_type $data)

        :param unknown_type $data:
        :returns: boolean|int The number of bytes sent or false on error

    .. php:method:: receive(int $length = 1400)

        Recieve data from the socket

        :param int $length:
        :returns: string

    .. php:method:: configureProtocol()

        Configures the protocol option
