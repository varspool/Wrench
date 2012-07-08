<?php

namespace Wrench;

use Wrench\Protocol\Protocol;

use Wrench\Payload\Payload;

use Wrench\Util\Configurable;
use Wrench\Socket;
use Wrench\Server;
use Wrench\Exception as WrenchException;
use Wrench\Exception\CloseException;
use Wrench\Exception\ConnectionException;
use Wrench\Exception\HandshakeException;
use Wrench\Exception\BadRequestException;

use \Exception;
use \RuntimeException;

/**
 * Represents a client connection on the server side
 *
 * i.e. the `Server` manages a bunch of `Connections
 */
class Connection extends Configurable
{
    protected $manager;

    /**
     * Socket object
     *
     * Wraps the client connection resource
     *
     * @var Socket
     */
    protected $socket;

    /**
     * Whether the connection has successfully handshaken
     *
     * @var boolean
     */
    protected $handshaked = false;

    /**
     * The application this connection belongs to
     *
     * @var Application
     */
    protected $application = null;

    /**
     * The IP address of the client
     *
     * @var string
     */
	protected $ip;

	/**
	 * The port of the client
	 *
	 * @var int
	 */
	protected $port;

	/**
	 * Connection ID
	 *
	 * @var string|null
	 */
	protected $id = null;

	public $waitingForData = false;

	protected $payload;

	private $_dataBuffer = '';

    /**
     * Constructor
     *
     * @param Server $server
     * @param resource $socket
     * @param array $options
     * @throws InvalidArgumentException
     */
	public function __construct(
	    ConnectionManager $manager,
	    $socket,
	    array $options = array()
    ) {
        $this->manager = $manager;

        parent::__construct($options);

        $this->configureSocket($socket);
        $this->configureClientInformation();

		$this->log('Connected');
    }

    /**
     * @see Wrench\Util.Configurable::configure()
     */
    protected function configure(array $options)
    {
        $options = array_merge(array(
            'socket_class'         => 'Wrench\Socket\ServerClientSocket',
            'socket_options'       => array(),
            'connection_id_secret' => 'asu5gj656h64Da(0crt8pud%^WAYWW$u76dwb',
            'connection_id_algo'   => 'sha512',
        ), $options);

        parent::configure($options);
    }

    /**
     * @param resource $socket
     */
    protected function configureSocket($socket)
    {
        $class   = $this->options['socket_class'];
        $options = $this->options['socket_options'];
        $this->socket = new $class($socket, $options);
    }

    /**
     * @throws RuntimeException
     */
    protected function configureClientInformation()
    {
		$name = stream_socket_get_name($this->socket->getResource(), true);

		$tmp = explode(':', $name);
		if (count($tmp) == 2) {
    		$this->ip = $tmp[0];
    		$this->port = $tmp[1];
    		$this->configureClientId();
		} else {
		    throw new RuntimeException('Could not get client information');
		}
    }

    /**
     * Configures the client ID
     *
     * We hash the client ID to prevent leakage of information if another client
     * happens to get a hold of an ID. The secret *must* be lengthy, and must
     * be kept secret for this to work: otherwise it's trivial to search the space
     * of possible IP addresses/ports (well, if not trivial, at least very fast).
     */
    protected function configureClientId()
    {
        $message = sprintf(
		    '%s:uri=%s&ip=%s&port=%s',
            $this->options['connection_id_secret'],
		    rawurlencode($this->manager->getUri()),
		    rawurlencode($this->ip),
	        rawurlencode($this->port)
        );

        $algo = $this->options['connection_id_algo'];

        if (extension_loaded('gmp')) {
            $hash = hash($algo, $message, true);
            $hash = gmp_strval(gmp_init($hash, 16), 62);
        } else {
            $hash = hash($algo, $message);
        }

        $this->id = $hash;
    }

	/**
	 * Data receiver
	 *
	 * Called by the connection manager when the connection has received data
	 *
	 * @param string $data
	 */
	public function onData($data)
    {
        if (!$this->handshaked) {
            return $this->handshake($data);
        }
        return $this->handle($data);
    }

    /**
     * Performs a websocket handshake
     *
     * @param string $data
     * @throws BadRequestException
     * @throws HandshakeException
     * @throws WrenchException
     */
    public function handshake($data)
    {
        try {
            list($path, $origin, $key, $extensions)
                = $this->protocol->validateRequestHandshake($data);

            $this->application = $this->manager->getApplicationForPath($path);
            if (!$this->application) {
                throw new BadRequestException('Invalid application');
            }

            $this->manager->getServer()->notify(
                Server::EVENT_HANDSHAKE_REQUEST,
                array($this, $path, $origin, $key, $extensions)
            );

            $response = $this->protocol->getResponseHandshake($key);

            if ($this->socket->send($response) === false) {
                throw new HandshakeException('Could not send handshake response');
            }

            $this->handshaked = true;

            $this->log(sprintf(
                'Handshake successful: %s:%d (%s) connected to %s',
                $this->getIp(),
                $this->getPort(),
                $this->getId(),
                $path
            ), 'info');

            $this->manager->getServer()->notify(
                Server::EVENT_HANDSHAKE_SUCCESSFUL,
                array($this)
            );

            $this->application->onConnect($this);
        } catch (WrenchException $e) {
            $this->log('Handshake failed: ' . $e, 'err');
            throw $e;
        }
    }

    /**
     * Sends an HTTP response to the client
     *
     * @param int $httpStatusCode
     */
	public function sendHttpResponse($httpStatusCode = 400)
	{
	    throw new Exception("no longer implemented");

// 		$httpHeader = 'HTTP/1.1 ';
// 		switch($httpStatusCode)
// 		{
// 			case 400:
// 				$httpHeader .= '400 Bad Request';
// 			break;

// 			case 401:
// 				$httpHeader .= '401 Unauthorized';
// 			break;

// 			case 403:
// 				$httpHeader .= '403 Forbidden';
// 			break;

// 			case 404:
// 				$httpHeader .= '404 Not Found';
// 			break;

// 			case 501:
// 				$httpHeader .= '501 Not Implemented';
// 			break;
// 		}
// 		$httpHeader .= "\r\n";
// 		$this->server->writeBuffer($this->socket, $httpHeader);
	}


	/**
	 * Handle data received from the client
	 *
	 * The data passed in may belong to several different frames across one or
	 * more protocols. It may not even contain a single complete frame. This method
	 * manages slotting the data into separate payload objects.
	 *
	 * @todo An endpoint MUST be capable of handling control frames in the
     *        middle of a fragmented message.
	 * @param string $data
	 */
    protected function handle($data)
    {
        if (!$this->payload) {
            $this->payload = $this->protocol->getPayload();
        }

        while ($data) {
            $size = strlen($data);
            $remaining = $this->payload->getRemainingData();

            // If we don't yet know how much data is remaining, read data into
            // the payload in two byte chunks (the size of a WebSocket frame
            // header to get the initial length)
            //
            // Then re-loop. For extended lengths, this will happen once or four
            // times extra, as the extended length is read in.
            if ($remaining === null) {
                $chunk_size = 2;
            } elseif ($remaining > 0) {
                $chunk_size = $remaining;
            } elseif ($remaining === 0) {
                $chunk_size = 0;
            }

            $chunk_size = min(strlen($data), $chunk_size);
            $chunk = substr($data, 0, $chunk_size);
            $data = substr($data, $chunk_size);

            $this->payload->receiveData($chunk);

            if ($remaining !== 0) {
                continue;
            }

            if ($this->payload->isComplete()) {
                $this->handlePayload($this->payload);
                $this->payload = $this->protocol->getPayload();
            } else {
                throw new ConnectionException('Payload will not complete');
            }
        }
    }

    /**
     * Handle a complete payload received from the client
     *
     * @param string $payload
     */
    protected function handlePayload(Payload $payload)
    {
        $app = $this->getClientApplication();

        $this->log('Handling payload: ' . $payload->getPayload(), 'debug');

        switch ($payload->getType()) {
            case Protocol::TYPE_TEXT:
                if (method_exists($app, 'onData')) {
                    $app->onData($payload, $this);
                }
                return;

			case Protocol::TYPE_BINARY:
				if(method_exists($app, 'onBinaryData')) {
					$app->onBinaryData($payload, $this);
				} else {
					$this->close(1003);
				}
			break;

			case 'ping':
				$this->send($decodedData['payload'], 'pong', false);
				$this->log('Ping? Pong!');
			break;

			case 'pong':
				// server currently not sending pings, so no pong should be received.
			break;

			case 'close':
				$this->close();
				$this->log('Disconnected');
			break;

            default:
                throw new ConnectionException('Unhandled payload type');
        }
    }

    /**
     * Sends the payload to the connection
     *
     * @param string $payload
     * @param string $type
     * @throws HandshakeException
     * @throws ConnectionException
     * @return boolean
     */
    public function send($data, $type = Protocol::TYPE_TEXT)
    {
        if (!$data) {
            return false;
        }

        if (!$this->handshaked) {
            throw new HandshakeException('Connection is not handshaked');
        }

        $payload = $this->protocol->getPayload();

        // Servers don't send masked payloads
        $payload->encode($data, $type, false);

        if (!$payload->sendToSocket($this->socket)) {
            $this->log('Could not send payload to client', 'warn');
            throw new ConnectionException('Could not send data to connection: ' . $this->socket->getLastError());
        }

		return true;
    }

    /**
     * Processes an exception which occured on the connection
     *
     * @param Exception $e
     */
    public function processException(Exception $e)
    {
        throw new Exception('Just call close!');
        $this->close($e);
    }

    /**
     * Processes data on the socket
     *
     * @throws CloseException
     */
    public function process()
    {
        $data = $this->socket->receive();
        $bytes = strlen($data);

        if ($bytes === 0 || $data === false) {
            throw new CloseException('Error reading data from socket: ' . $this->socket->getLastError());
        }

        $this->onData($data);
    }

    /**
     * Closes the connection according to the WebSocket protocol
     *
     * @param int|Exception $statusCode
     * @return boolean
     */
	public function close($code = Protocol::CLOSE_NORMAL)
	{
	    try {
	        if (!$this->handshaked) {
                $response = $this->protocol->getResponseError($e);
                $this->socket->send($response);
            } else {
                $response = $this->protocol->getCloseFrame($e);
                $this->socket->send($response);
            }
	    } catch (Exception $e) {
            $this->log('Unable to send close message', 'warning');
        }




		if($this->application)
		{
            $this->application->onDisconnect($this);
        }
		stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
		$this->server->removeClientOnClose($this);
	}

	/**
	 * Event handler for disconnections
	 *
	 * @deprecated Just use close
	 */
	public function onDisconnect()
    {
        throw new Exception('Deprecated: just use close');
        $this->log('Disconnected', 'info');
        $this->close(1000);
    }

    /**
     * Logs a message
     *
     * @param string $message
     * @param string $priority
     */
    public function log($message, $priority = 'info')
    {
        $this->manager->log(sprintf(
            '%s: %s:%d (%s): %s',
            __CLASS__,
            $this->getIp(),
            $this->getPort(),
            $this->getId(),
            $message
        ), $priority);
    }

    /**
     * Gets the IP address of the connection
     *
     * @return string Usually dotted quad notation
     */
	public function getIp()
	{
		return $this->ip;
	}

	/**
	 * Gets the port of the connection
	 *
	 * @return int
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * Gets the connection ID
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Gets the socket object
	 *
	 * @return Socket\ServerClientSocket
	 */
	public function getSocket()
	{
		return $this->socket;
	}

	/**
	 * Gets the client application
	 *
	 * @return Application
	 */
	public function getClientApplication()
	{
		return (isset($this->application)) ? $this->application : false;
	}
}