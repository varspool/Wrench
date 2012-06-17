<?php
namespace WebSocket;

use WebSocket\Util\Configurable;

use WebSocket\Socket;
use WebSocket\Server;
use \RuntimeException;
use WebSocket\Exception as WebSocketException;

/**
 * Represents a client connection on the server side
 *
 * i.e. the `Server` manages a bunch of `Connection`s
 *
 * @author Nico Kaiser <nico@kaiser.me>
 * @author Simon Samtleben <web@lemmingzshadow.net>
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
    private $handshaked = false;

    /**
     * The application this connection belongs to
     *
     * @var Application
     */
    private $application = null;

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
     * @see WebSocket\Util.Configurable::configure()
     */
    protected function configure(array $options)
    {
        $options = array_merge(array(
            'socket_class'         => 'WebSocket\Socket\ServerClientSocket',
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

    public function handshake($data)
    {
        try {
            $this->protocol->validateRequestHandshake($data);
            $response = $this->protocol->getResponseHandshake();
        } catch (WebSocketException $e) {
            $this->log('Handshake failed: ' . $e, 'err');
            throw $e;
        }

        $this->socket->send($response);
    }

	public function sendHttpResponse($httpStatusCode = 400)
	{
		$httpHeader = 'HTTP/1.1 ';
		switch($httpStatusCode)
		{
			case 400:
				$httpHeader .= '400 Bad Request';
			break;

			case 401:
				$httpHeader .= '401 Unauthorized';
			break;

			case 403:
				$httpHeader .= '403 Forbidden';
			break;

			case 404:
				$httpHeader .= '404 Not Found';
			break;

			case 501:
				$httpHeader .= '501 Not Implemented';
			break;
		}
		$httpHeader .= "\r\n";
		$this->server->writeBuffer($this->socket, $httpHeader);
	}


    private function handle($data)
    {
		if($this->waitingForData === true)
		{
			$data = $this->_dataBuffer . $data;
			$this->_dataBuffer = '';
			$this->waitingForData = false;
		}

		$decodedData = $this->hybi10Decode($data);

		if($decodedData === false)
		{
			$this->waitingForData = true;
			$this->_dataBuffer .= $data;
			return false;
		}
		else
		{
			$this->_dataBuffer = '';
			$this->waitingForData = false;
		}

		// trigger status application:
		if($this->server->getApplication('status') !== false)
		{
			$this->server->getApplication('status')->clientActivity($this->port);
		}

		switch($decodedData['type'])
		{
			case 'text':
				$this->application->onData($decodedData['payload'], $this);
			break;

			case 'binary':
				if(method_exists($this->application, 'onBinaryData'))
				{
					$this->application->onBinaryData($decodedData['payload'], $this);
				}
				else
				{
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
		}

		return true;
    }

    public function send($payload, $type = 'text', $masked = false)
    {
		$encodedData = $this->hybi10Encode($payload, $type, $masked);
		if(!$this->server->writeBuffer($this->socket, $encodedData))
		{
			$this->server->removeClientOnError($this);
			return false;
		}
		return true;
    }

	public function close($statusCode = 1000)
	{
		$payload = str_split(sprintf('%016b', $statusCode), 8);
		$payload[0] = chr(bindec($payload[0]));
		$payload[1] = chr(bindec($payload[1]));
		$payload = implode('', $payload);

		switch($statusCode)
		{
			case 1000:
				$payload .= 'normal closure';
			break;

			case 1001:
				$payload .= 'going away';
			break;

			case 1002:
				$payload .= 'protocol error';
			break;

			case 1003:
				$payload .= 'unknown data (opcode)';
			break;

			case 1004:
				$payload .= 'frame too large';
			break;

			case 1007:
				$payload .= 'utf8 expected';
			break;

			case 1008:
				$payload .= 'message violates server policy';
			break;
		}

		if($this->send($payload, 'close', false) === false)
		{
			return false;
		}

		if($this->application)
		{
            $this->application->onDisconnect($this);
        }
		stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
		$this->server->removeClientOnClose($this);
	}

	public function onDisconnect()
    {
        $this->log('Disconnected', 'info');
        $this->close(1000);
    }

    public function log($message, $priority = 'info')
    {
        $this->manager->log(__CLASS__ . ': ' . $message, $priority);
    }

	public function getIp()
	{
		return $this->ip;
	}

	public function getPort()
	{
		return $this->port;
	}

	public function getId()
	{
		return $this->connectionId;
	}

	public function getSocket()
	{
		return $this->socket;
	}

	public function getClientApplication()
	{
		return (isset($this->application)) ? $this->application : false;
	}
}