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

        protected array<string>

        Valid schemes

    .. php:attr:: closeReasons

        array<int

        Close status codes

    .. php:attr:: frameTypes

        array<string

        Frame types

    .. php:attr:: httpResponses

        array<int

        HTTP errors

    .. php:method:: getPayload()

    .. php:method:: getVersion()

        Gets a version number

    .. php:method:: acceptsVersion($version)

        Subclasses should implement this method and return a boolean to the given
        version string, as to whether they would like to accept requests from
        user agents that specify that version.

        :param $version:
        :returns: boolean

    .. php:method:: generateKey()

        Generates a key suitable for use in the protocol

        This base implementation returns a 16-byte (128 bit) random key as a
        binary string.

        :returns: string

    .. php:method:: getRequestHandshake($uri, $key, $origin, $headers = array())

        Gets request handshake string

        The leading line from the client follows the Request-Line format.
        The leading line from the server follows the Status-Line format.  The
        Request-Line and Status-Line productions are defined in [RFC2616].

        An unordered set of header fields comes after the leading line in both
        cases.  The meaning of these header fields is specified in Section 4 of
        this document.  Additional header fields may also be present, such as
        cookies [RFC6265].  The format and parsing of headers is as defined in
        [RFC2616].

        :type $uri: string
        :param $uri: WebSocket URI, e.g. ws://example.org:8000/chat
        :type $key: string
        :param $key: 16 byte binary string key
        :type $origin: string
        :param $origin: Origin of the request
        :param $headers:
        :returns: string

    .. php:method:: getResponseHandshake($key, $headers = array())

        Gets a handshake response body

        :type $key: string
        :param $key:
        :type $headers: array
        :param $headers:

    .. php:method:: getResponseError($e, $headers = array())

        Gets a response to an error in the handshake

        :type $e: int|Exception
        :param $e: Exception or HTTP error
        :type $headers: array
        :param $headers:

    .. php:method:: getHttpResponse($status, $headers = array())

        Gets an HTTP response

        :type $status: int
        :param $status:
        :type $headers: array
        :param $headers:

    .. php:method:: validateResponseHandshake($response, $key)

        :type $response: unknown_type
        :param $response:
        :type $key: unknown_type
        :param $key:
        :returns: boolean

    .. php:method:: getEncodedHash($key)

        Gets an encoded hash for a key

        :type $key: string
        :param $key:
        :returns: string

    .. php:method:: validateRequestHandshake($request)

        Validates a request handshake

        :type $request: string
        :param $request:

    .. php:method:: getCloseFrame($e)

        Gets a suitable WebSocket close frame

        :type $e: Exception|int
        :param $e:

    .. php:method:: validateUri($uri)

        Validates a WebSocket URI

        :type $uri: string
        :param $uri:
        :returns: array(string $scheme, string $host, int $port, string $path)

    .. php:method:: validateSocketUri($uri)

        Validates a socket URI

        :type $uri: string
        :param $uri:
        :returns: array(string $scheme, string $host, string $port)

    .. php:method:: validateOriginUri($origin)

        Validates an origin URI

        :type $origin: string
        :param $origin:
        :returns: string

    .. php:method:: validateRequestLine($line)

        Validates a request line

        :type $line: string
        :param $line:

    .. php:method:: getAcceptValue($encoded_key)

        Gets the expected accept value for a handshake response

        Note that the protocol calls for the base64 encoded value to be hashed,
        not the original 16 byte random key.

        :param $encoded_key:

    .. php:method:: getHeaders($response, $request_line = null)

        Gets the headers from a full response

        :type $response: string
        :param $response:
        :param $request_line:
        :returns: array()

    .. php:method:: getRequestHeaders($response)

        Gets request headers

        :type $response: string
        :param $response:
        :returns: array<string, array<string>> The request line, and an array of headers

    .. php:method:: validateScheme($scheme)

        Validates a scheme

        :type $scheme: string
        :param $scheme:
        :returns: string Underlying scheme

    .. php:method:: getDefaultRequestHeaders($host, $key, $origin)

        Gets the default request headers

        :type $host: string
        :param $host:
        :type $key: string
        :param $key:
        :type $origin: string
        :param $origin:
        :returns: multitype:unknown string NULL

    .. php:method:: getSuccessResponseHeaders($key)

        Gets the default response headers

        :type $key: string
        :param $key:

    .. php:method:: getPort($scheme)

        Gets the default port for a scheme

        By default, the WebSocket Protocol uses port 80 for regular WebSocket
        connections and port 443 for WebSocket connections tunneled over Transport
        Layer Security

        :param $scheme:
        :returns: int
