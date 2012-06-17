<?php

namespace WebSocket\Protocol;

use \InvalidArgumentException;

/**
 * Definitions and implementation helpers for the WebSockets protocol
 *
 * Based on RFC 6455: http://tools.ietf.org/html/rfc6455
 */
abstract class Protocol
{
    const MAGIC_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /**
     * New IANA registered schemes
     * @var string
     */
    const SCHEME_WEBSOCKET         = 'ws';
    const SCHEME_WEBSOCKET_SECURE  = 'wss';
    const SCHEME_UNDERLYING        = 'tcp';
    const SCHEME_UNDERLYING_SECURE = 'tls';

    /**#@+
     * Headers
     * @var string
     */
    const HEADER_HOST       = 'Host';
    const HEADER_KEY        = 'Sec-WebSocket-Key';
    const HEADER_PROTOCOL   = 'Sec-WebSocket-Protocol';
    const HEADER_VERSION    = 'Sec-WebSocket-Version';
    const HEADER_ACCEPT     = 'Sec-WebSocket-Accept';
    const HEADER_EXTENSIONS = 'Sec-WebSocket-Extensions';
    const HEADER_ORIGIN     = 'Origin';
    const HEADER_CONNECTION = 'Connection';
    const HEADER_UPGRADE    = 'Upgrade';
    /**#@-*/

    /**
     * The request MUST contain an |Upgrade| header field whose value
     *   MUST include the "websocket" keyword.
     */
    const UPGRADE_VALUE = 'websocket';

    /**
     * The request MUST contain a |Connection| header field whose value
     *   MUST include the "Upgrade" token.
     */
    const CONNECTION_VALUE = 'Upgrade';

    /**
     * Request line format
     *
     * @var string printf compatible, passed request path string
     */
    const REQUEST_LINE_FORMAT = 'GET %s HTTP/1.1';

    /**
     * Request line regex
     *
     * Used for parsing requested path
     *
     * @var string preg_* compatible
     */
    const REQUEST_LINE_REGEX = '/^GET (\S+) HTTP\/1.1$/';

    /**
     * Response line format
     *
     * @var string
     */
    const RESPONSE_LINE_FORMAT = '%d %s';

    /**
     * Header line format
     *
     * @var string printf compatible, passed header name and value
     */
    const HEADER_LINE_FORMAT = '%s: %s';

    /**
     * Valid schemes
     *
     * @var array<string>
     */
    protected $schemes = array(
        self::SCHEME_WEBSOCKET,
        self::SCHEME_WEBSOCKET_SECURE ,
        self::SCHEME_UNDERLYING,
        self::SCHEME_UNDERLYING_SECURE
    );

    /**
     * Gets a version number
     *
     * @return
     */
    abstract public function getVersion();


    /**
     * Encodes the given binary data into a frame
     *
     * @param string $data
     * @param string $payload
     * @param boolean $masked
     */
    abstract public function encode($data, $payload, $masked = true);

    /**
     * Generates a key suitable for use in the protocol
     *
     * This base implementation returns a 16-byte (128 bit) random key as a
     * binary string.
     *
     * @return string
     */
    public function generateKey()
    {
        if (extension_loaded('openssl')) {
            return openssl_random_pseudo_bytes(16);
        }

        // SHA1 is 128 bit (= 16 bytes)
        return sha1(mt_rand(0, PHP_INT_MAX) . uniqid('', true), true);
    }

    /**
     * Gets request handshake string
     *
     *   The leading line from the client follows the Request-Line format.
     *   The leading line from the server follows the Status-Line format.  The
     *   Request-Line and Status-Line productions are defined in [RFC2616].
     *
     *   An unordered set of header fields comes after the leading line in
     *   both cases.  The meaning of these header fields is specified in
     *   Section 4 of this document.  Additional header fields may also be
     *   present, such as cookies [RFC6265].  The format and parsing of
     *   headers is as defined in [RFC2616].
     *
     * @param string $uri    WebSocket URI, e.g. ws://example.org:8000/chat
     * @param string $key    16 byte binary string key
     * @param string $origin Origin of the request
     * @return string
     */
    public function getRequestHandshake(
        $uri,
        $key,
        $origin,
        array $headers = array()
    ) {
        if (!$uri || !$key || !$origin) {
            throw new InvalidArgumentException('You must supply a URI, key and origin');
        }

        list($host, $port, $path) = self::validateUri($uri);

        $handshake = array(
            sprintf(self::REQUEST_LINE_FORMAT, $path)
        );

        $headers = array_merge(
            $this->getDefaultRequestHeaders(
                $host, $key, $origin
            ),
            $headers
        );

        foreach ($headers as $name => $value) {
            $handshake[] = sprintf(self::HEADER_LINE_FORMAT, $name, $value);
        }

        return implode($handshake, "\r\n") . "\r\n\r\n";
    }


    public function getResponseHandshake($data)
    {

		// generate headers array:
		$headers = array();
        foreach($lines as $line)
		{
            $line = chop($line);
            if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
			{
                $headers[$matches[1]] = $matches[2];
            }
        }



		// check origin:
		if($this->server->getCheckOrigin() === true)
		{
			$origin = (isset($headers['Sec-WebSocket-Origin'])) ? $headers['Sec-WebSocket-Origin'] : false;
			$origin = (isset($headers['Origin'])) ? $headers['Origin'] : $origin;
			if($origin === false)
			{
				$this->log('No origin provided.');
				$this->sendHttpResponse(401);
				stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
				$this->server->removeClientOnError($this);
				return false;
			}

			if(empty($origin))
			{
				$this->log('Empty origin provided.');
				$this->sendHttpResponse(401);
				stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
				$this->server->removeClientOnError($this);
				return false;
			}

			if($this->server->checkOrigin($origin) === false)
			{
				$this->log('Invalid origin provided.');
				$this->sendHttpResponse(401);
				stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
				$this->server->removeClientOnError($this);
				return false;
			}
		}

		// do handyshake: (hybi-10)
		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		$response = "HTTP/1.1 101 Switching Protocols\r\n";
		$response.= "Upgrade: websocket\r\n";
		$response.= "Connection: Upgrade\r\n";
		$response.= "Sec-WebSocket-Accept: " . $secAccept . "\r\n";
		$response.= "Sec-WebSocket-Protocol: " . substr($path, 1) . "\r\n\r\n";
		if(false === ($this->server->writeBuffer($this->socket, $response)))
		{
			return false;
		}
		$this->handshaked = true;
		$this->log('Handshake sent');
		$this->application->onConnect($this);

		// trigger status application:
		if($this->server->getApplication('status') !== false)
		{
			$this->server->getApplication('status')->clientConnected($this->ip, $this->port);
		}

		return true;
    }







    /**
     * @todo better header handling
     * @todo throw exception
     * @param unknown_type $response
     * @param unknown_type $key
     * @return boolean
     */
    public function validateResponseHandshake($response, $key)
    {
        if (!$response) {
            return false;
        }

        $headers = $this->getHeaders($response);

        if (!isset($headers[self::HEADER_ACCEPT])) {
            throw new HandshakeException('No accept header receieved on handshake response');
        }

        $accept = $headers[self::HEADER_ACCEPT];

        if (!$accept) {
            throw new HandshakeException('Invalid accept header');
        }

        $expected = $this->getExpectedAcceptValue($key);


        preg_match('#Sec-WebSocket-Accept:\s(.*)$#mU', $response, $matches);
        $keyAccept = trim($matches[1]);

        $expectedResonse = base64_encode(pack('H*', sha1($key . self::MAGIC_GUID)));

        return ($keyAccept === $expectedResonse) ? true : false;
    }

    public function validateRequestHandshake($request)
    {
        if (!$request) {
            return false;
        }

        list($request, $headers) = $this->getRequestHeaders($request);
        $path = $this->validateRequestLine($request);

        var_dump($headers);
        die('asdasdkdas');


            $this->log('Performing handshake');
        $lines = preg_split("/\r\n/", $data);

		// check for valid http-header:
            $this->log('Invalid request: ' . $lines[0]);
			$this->sendHttpResponse(400);
            stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
            return false;

		// check for valid application:
		$path = $matches[1];
		$this->application = $this->server->getApplication(substr($path, 1));
        if(!$this->application)
		{
            $this->log('Invalid application: ' . $path);
			$this->sendHttpResponse(404);
			stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
			$this->server->removeClientOnError($this);
            return false;
        }
    		// check for supported websocket version:
		if(!isset($headers['Sec-WebSocket-Version']) || $headers['Sec-WebSocket-Version'] < 6)
		{
			$this->log('Unsupported websocket version.');
			$this->sendHttpResponse(501);
			stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
			$this->server->removeClientOnError($this);
            return false;
		}
    }

    /**
     * Validates a request line
     *
     * @param string $line
     * @throws BadRequestException
     */
    protected function validateRequestLine($line)
    {
        $matches = array(0 => null, 1 => null);

        if (!preg_match(self::REQUEST_LINE_REGEX, $line, $matches) || !$matches[1]) {
            throw new BadRequestException('Invalid request line', 400);
        }

        return $matches[1];
    }

    /**
     * Gets the expected accept value in the server's response
     *
     * @param string $key
     */
    protected function getExpectedAcceptValue($key)
    {
        $expected = sha1($key . self::MAGIC_GUID, true);
        return $this->encodeKey($key);
    }

    /**
     * Gets the headers from a full response
     *
     * @param string $response
     * @return array()
     * @throws InvalidArgumentException
     */
    protected function getHeaders($response, &$request_line = null)
    {
        $parts = explode("\r\n\r\n", $response, 2);

        if (count($parts) != 2) {
            throw new InvalidArgumentException('No headers in response');
        }

        list($headers, $body) = $parts;

        $return = array();
        foreach (explode("\r\n", $headers) as $header) {
            $parts = explode(': ', $header, 2);

            if (count($parts) != 2) {
                throw new InvalidArgumentException('Invalid header');
            }

            list($name, $value) = $parts;
            $return[$name] = $value;
        }

        return $return;
    }

    /**
     * Gets request headers
     *
     * @param string $response
     * @return array<string, array<string>> The request line, and an array of
     *             headers
     * @throws InvalidArgumentException
     */
    protected function getRequestHeaders($response)
    {
        $eol = strpos($response, "\r\n");

        if ($eol === false) {
            throw new InvalidArgumentException('Invalid request line');
        }

        $request = substr($response, 0, $eol);
        $headers = $this->getHeaders(substr($response, $eol + 2));

        return array($request, $headers);
    }

    /**
     * Validates a WebSocket URI
     *
     * @param string $uri
     * @return array(string $scheme, string $host, int $port, string $path)
     */
    public function validateUri($uri)
    {
        $uri = (string)$uri;
        if (!$uri) {
            throw new InvalidArgumentException('Invalid URI');
        }

        $scheme = parse_url($uri, PHP_URL_SCHEME);
        $this->validateScheme($scheme);

        $host = parse_url($uri, PHP_URL_HOST);
        if (!$host) {
            throw new InvalidArgumentException("Invalid host");
        }

        $port = parse_url($uri, PHP_URL_PORT);
        if (!$port) {
            $port = $this->getPort($scheme);
        }

        $path = parse_url($uri, PHP_URL_PATH);
        if (!$path) {
            throw new InvalidArgumentException('Invalid path');
        }

        return array($scheme, $host, $port, $path);
    }

    /**
     * Validates a socket URI
     *
     * @param string $uri
     * @throws InvalidArgumentException
     * @return array(string $scheme, string $host, string $port)
     */
    public function validateSocketUri($uri)
    {
        $uri = (string)$uri;
        if (!$uri) {
            throw new InvalidArgumentException('Invalid URI');
        }

        $scheme = parse_url($uri, PHP_URL_SCHEME);
        $scheme = $this->validateScheme($scheme);

        $host = parse_url($uri, PHP_URL_HOST);
        if (!$host) {
            throw new InvalidArgumentException("Invalid host");
        }

        $port = parse_url($uri, PHP_URL_PORT);
        if (!$port) {
            $port = $this->getPort($scheme);
        }

        return array($scheme, $host, $port);
    }

    /**
     * Validates an origin URI
     *
     * @param string $origin
     * @throws InvalidArgumentException
     * @return string
     */
    public function validateOriginUri($origin)
    {
        $origin = (string)$origin;
        if (!$origin) {
            throw new InvalidArgumentException('Invalid URI');
        }

        $scheme = parse_url($origin, PHP_URL_SCHEME);
        if (!$scheme) {
            throw new InvalidArgumentException('Invalid scheme');
        }

        $host = parse_url($origin, PHP_URL_HOST);
        if (!$host) {
            throw new InvalidArgumentException("Invalid host");
        }

        return $origin;
    }

    /**
     * Validates a scheme
     *
     * @param string $scheme
     * @return string Underlying scheme
     * @throws InvalidArgumentException
     */
    protected function validateScheme($scheme)
    {
        if (!$scheme) {
            throw new InvalidArgumentException('No scheme specified');
        }
        if (!in_array($scheme, $this->schemes)) {
            throw new InvalidArgumentException(
                'Unknown socket scheme: ' . $scheme
            );
        }

        if ($scheme == self::SCHEME_WEBSOCKET_SECURE) {
            return self::SCHEME_UNDERLYING_SECURE;
        }
        return self::SCHEME_UNDERLYING;
    }

    /**
     * Gets the default request headers
     *
     * @param string $host
     * @param string $key
     * @param string $origin
     * @param int $version
     * @return multitype:unknown string NULL
     */
    protected function getDefaultRequestHeaders($host, $key, $origin)
    {
        return array(
            self::HEADER_HOST       => $host,
            self::HEADER_UPGRADE    => self::UPGRADE_VALUE,
            self::HEADER_CONNECTION => self::CONNECTION_VALUE,
            self::HEADER_KEY        => $this->encodeKey($key),
            self::HEADER_ORIGIN     => $origin,
            self::HEADER_VERSION    => $this->getVersion()
        );
    }

    /**
     * Gets the default port for a scheme
     *
     * By default, the WebSocket Protocol uses port 80 for regular WebSocket
     *  connections and port 443 for WebSocket connections tunneled over
     *  Transport Layer Security
     *
     * @param string $uri
     * @return int
     */
    protected function getPort($scheme)
    {
        if ($scheme == self::SCHEME_WEBSOCKET) {
            return 80;
        } elseif ($scheme == self::SCHEME_WEBSOCKET_SECURE) {
            return 443;
        } elseif ($scheme == self::SCHEME_UNDERLYING) {
            return 80;
        } elseif ($scheme == self::SCHEME_UNDERLYING_SECURE) {
            return 443;
        } else {
            throw new InvalidArgumentException('Unknown websocket scheme');
        }
    }

    /**
     * Encodes a key for use in headers
     *
     * @param string $key
     * @return string
     */
    protected function encodeKey($key)
    {
        return base64_encode($key);
    }
}
