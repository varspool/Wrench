------------------------------
Wrench\\Listener\\OriginPolicy
------------------------------

.. php:namespace: Wrench\\Listener

.. php:class:: OriginPolicy

    .. php:attr:: allowed

    .. php:method:: __construct($allowed)

        :param unknown $allowed:

    .. php:method:: onHandshakeRequest(Connection $connection, string $path, string $origin, string $key, array $extensions)

        Handshake request listener

        Closes the connection on handshake from an origin that isn't allowed

        :param Connection $connection:
        :param string $path:
        :param string $origin:
        :param string $key:
        :param array $extensions:

    .. php:method:: isAllowed(string $origin)

        Whether the specified origin is allowed under this policy

        :param string $origin:
        :returns: boolean

    .. php:method:: listen(Server $server)

        :param Server $server:
