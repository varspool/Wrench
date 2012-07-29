--------------
Wrench\\Client
--------------

.. php:namespace: Wrench

.. php:class:: Client

    Client class

    Represents a Wrench client

    .. php:const:: MAX_HANDSHAKE_RESPONSE

    .. php:attr:: uri

    .. php:attr:: origin

    .. php:attr:: socket

    .. php:attr:: headers

        Request headers

    .. php:attr:: protocol

        Protocol instance

    .. php:attr:: options

        Options

    .. php:attr:: connected

        Whether the client is connected

    .. php:method:: __construct(string $uri, string $origin, $options = Array)

        Constructor

        :param string $uri:
        :param string $origin:  The origin to include in the handshake (required in later versions of the protocol)
        :param unknown $options:

    .. php:method:: configure(array $options)

        Configure options

        :param array $options:
        :returns: void

    .. php:method:: __destruct()

        Destructor

    .. php:method:: addRequestHeader(string $name, string $value)

        Adds a request header to be included in the initial handshake

        For example, to include a Cookie header

        :param string $name:
        :param string $value:
        :returns: void

    .. php:method:: sendData(string $data, string $type = text, boolean $masked = )

        Sends data to the socket

        :param string $data:
        :param string $type: Payload type
        :param boolean $masked:
        :returns: int bytes written

    .. php:method:: connect()

        Connect to the Wrench server

        :returns: boolean Whether a new connection was made

    .. php:method:: isConnected()

        Whether the client is currently connected

        :returns: boolean

    .. php:method:: disconnect()
