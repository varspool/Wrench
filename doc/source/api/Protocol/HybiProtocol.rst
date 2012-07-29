------------------------------
Wrench\\Protocol\\HybiProtocol
------------------------------

.. php:namespace: Wrench\\Protocol

.. php:class:: HybiProtocol

    .. php:const:: SCHEME_WEBSOCKET

        Relevant schemes

    .. php:const:: HEADER_HOST

        HTTP headers

    .. php:const:: HTTP_SWITCHING_PROTOCOLS

        HTTP error statuses

    .. php:const:: CLOSE_NORMAL

        Close statuses

    .. php:const:: TYPE_CONTINUATION

        Frame types

        %x0 denotes a continuation frame
         %x1 denotes a text frame
         %x2 denotes a binary frame
         %x3-7 are reserved for further non-control frames
         %x8 denotes a connection close
         %x9 denotes a ping
         %xA denotes a pong
         %xB-F are reserved for further control frames

    .. php:const:: MAGIC_GUID

        Magic GUID

        Used in the WebSocket accept header

    .. php:const:: UPGRADE_VALUE

        The request MUST contain an |Upgrade| header field whose value
          MUST include the "websocket" keyword.

    .. php:const:: CONNECTION_VALUE

        The request MUST contain a |Connection| header field whose value
          MUST include the "Upgrade" token.

    .. php:const:: REQUEST_LINE_FORMAT

        Request line format

    .. php:const:: REQUEST_LINE_REGEX

        Request line regex

        Used for parsing requested path

    .. php:const:: RESPONSE_LINE_FORMAT

        Response line format

    .. php:const:: HEADER_LINE_FORMAT

        Header line format

    .. php:attr:: schemes

        Valid schemes

    .. php:attr:: closeReasons

        Close status codes

    .. php:attr:: frameTypes

        Frame types

    .. php:attr:: httpResponses

        HTTP errors

    .. php:method:: getPayload()

    .. php:method:: getVersion()

        Gets a version number

    .. php:method:: acceptsVersion($version)

        Subclasses should implement this method and return a boolean to the given
        version string, as to whether they would like to accept requests from
        user agents that specify that version.

        :param unknown $version:
        :returns: boolean

    .. php:method:: generateKey()

        Generates a key suitable for use in the protocol

        This base implementation returns a 16-byte (128 bit) random key as a
        binary string.

        :returns: string

    .. php:method:: getRequestHandshake(string $uri, string $key, string $origin, $headers = Array)

        Gets request handshake string

        The leading line from the client follows the Request-Line format.
        The leading line from the server follows the Status-Line format.  The
        Request-Line and Status-Line productions are defined in [RFC2616].

        An unordered set of header fields comes after the leading line in both
        cases.  The meaning of these header fields is specified in Section 4 of
        this document.  Additional header fields may also be present, such as
        cookies [RFC6265].  The format and parsing of headers is as defined in
        [RFC2616].

        :param string $uri:    WebSocket URI, e.g. ws://example.org:8000/chat
        :param string $key:    16 byte binary string key
        :param string $origin: Origin of the request
        :param unknown $headers:
        :returns: string

    .. php:method:: getResponseHandshake(string $key, array $headers = Array)

        Gets a handshake response body

        :param string $key:
        :param array $headers:

    .. php:method:: getResponseError(int|Exception $e, array $headers = Array)

        Gets a response to an error in the handshake

        :param int|Exception $e: Exception or HTTP error
        :param array $headers:

    .. php:method:: getHttpResponse(int $status, array $headers = Array)

        Gets an HTTP response

        :param int $status:
        :param array $headers:

    .. php:method:: validateResponseHandshake(unknown_type $response, unknown_type $key)

        :param unknown_type $response:
        :param unknown_type $key:
        :returns: boolean

    .. php:method:: getEncodedHash(string $key)

        Gets an encoded hash for a key

        :param string $key:
        :returns: string

    .. php:method:: validateRequestHandshake(string $request)

        Validates a request handshake

        :param string $request:

    .. php:method:: getCloseFrame(Exception|int $e)

        Gets a suitable WebSocket close frame

        :param Exception|int $e:

    .. php:method:: validateUri(string $uri)

        Validates a WebSocket URI

        :param string $uri:
        :returns: array(string $scheme, string $host, int $port, string $path)

    .. php:method:: validateSocketUri(string $uri)

        Validates a socket URI

        :param string $uri:
        :returns: array(string $scheme, string $host, string $port)

    .. php:method:: validateOriginUri(string $origin)

        Validates an origin URI

        :param string $origin:
        :returns: string

    .. php:method:: validateRequestLine(string $line)

        Validates a request line

        :param string $line:

    .. php:method:: getAcceptValue($encoded_key)

        Gets the expected accept value for a handshake response

        Note that the protocol calls for the base64 encoded value to be hashed,
        not the original 16 byte random key.

        :param unknown $encoded_key:

    .. php:method:: getHeaders(string $response, $request_line)

        Gets the headers from a full response

        :param string $response:
        :param unknown $request_line:
        :returns: array()

    .. php:method:: getRequestHeaders(string $response)

        Gets request headers

        :param string $response:
        :returns: array<string, array<string>> The request line, and an array of headers

    .. php:method:: validateScheme(string $scheme)

        Validates a scheme

        :param string $scheme:
        :returns: string Underlying scheme

    .. php:method:: getDefaultRequestHeaders(string $host, string $key, string $origin)

        Gets the default request headers

        :param string $host:
        :param string $key:
        :param string $origin:
        :returns: multitype:unknown string NULL

    .. php:method:: getSuccessResponseHeaders(string $key)

        Gets the default response headers

        :param string $key:

    .. php:method:: getPort($scheme)

        Gets the default port for a scheme

        By default, the WebSocket Protocol uses port 80 for regular WebSocket
        connections and port 443 for WebSocket connections tunneled over Transport
        Layer Security

        :param unknown $scheme:
        :returns: int
