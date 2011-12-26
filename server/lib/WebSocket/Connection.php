<?php
namespace WebSocket;

/**
 * WebSocket Connection class
 *
 * @author Nico Kaiser <nico@kaiser.me>
 * @author Simon Samtleben <web@lemmingzshadow.net>
 */
class Connection
{
    private $server;
    private $socket;
    private $handshaked = false;
    private $application = null;	
	
	private $ip;
	private $port;
	private $connectionId = null;
    
    public function __construct($server, $socket)
    {
		$this->server = $server;
		$this->socket = $socket;

		// set some client-information:
		socket_getpeername($this->socket, $ip, $port);
		$this->ip = $ip;
		$this->port = $port;
		$this->connectionId = md5($ip . $port . spl_object_hash($this));

		$this->log('Connected');
    }
    
    private function handshake($data)
    {		
        $this->log('Performing handshake');	    
        $lines = preg_split("/\r\n/", $data);
		
		// check for valid http-header:
        if(!preg_match('/\AGET (\S+) HTTP\/1.1\z/', $lines[0], $matches))
		{
            $this->log('Invalid request: ' . $lines[0]);
			$this->sendHttpResponse(400);
            socket_close($this->socket);
            return false;
        }                
		
		// check for valid application:
		$path = $matches[1];
		$this->application = $this->server->getApplication(substr($path, 1));
        if(!$this->application)
		{
            $this->log('Invalid application: ' . $path);
			$this->sendHttpResponse(404);
            socket_close($this->socket);
			$this->server->removeClientOnError($this);
            return false;
        }

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
		
		// check for supported websocket version:		
		if(!isset($headers['Sec-WebSocket-Version']) || $headers['Sec-WebSocket-Version'] < 6)
		{
			$this->log('Unsupported websocket version.');
			$this->sendHttpResponse(501);
            socket_close($this->socket);
			$this->server->removeClientOnError($this);
            return false;
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
				socket_close($this->socket);
				$this->server->removeClientOnError($this);
				return false;
			}
			
			if(empty($origin))
			{
				$this->log('Empty origin provided.');
				$this->sendHttpResponse(401);
				socket_close($this->socket);
				$this->server->removeClientOnError($this);
				return false;
			}
			
			if($this->server->checkOrigin($origin) === false)
			{
				$this->log('Invalid origin provided.');
				$this->sendHttpResponse(401);
				socket_close($this->socket);
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
		socket_write($this->socket, $response, strlen($response));      
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
		socket_write($this->socket, $httpHeader, strlen($httpHeader));
	}
	
	public function onData($data)
    {		
        if($this->handshaked)
		{			
            $this->handle($data);
        }
		else
		{
            $this->handshake($data);
        }
    }
    
    private function handle($data)
    {	
		$decodedData = $this->hybi10Decode($data);		
		
		switch($decodedData['type'])
		{
			case 'text':
				$this->application->onData($decodedData['payload'], $this);
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
    
    public function send($payload, $type = 'text', $masked = true)
    {		
		$encodedData = $this->hybi10Encode($payload, $type, $masked);
		if(!socket_write($this->socket, $encodedData, strlen($encodedData)))
		{
			socket_close($this->socket);
			$this->socket = false;
		}
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
		$this->send($payload, 'close', false);
		
		if($this->application)
		{
            $this->application->onDisconnect($this);
        }
		socket_close($this->socket);
		$this->server->removeClientOnClose($this);
	}


	public function onDisconnect()
    {		
        $this->log('Disconnected', 'info');
        $this->close(1000);
    }     

    public function log($message, $type = 'info')
    {        
        $this->server->log('[client ' . $this->ip . ':' . $this->port . '] ' . $message, $type);
    }
	
	private function hybi10Encode($payload, $type = 'text', $masked = true)
	{
		$frameHead = array();
		$frame = '';
		$payloadLength = strlen($payload);
		
		switch($type)
		{		
			case 'text':
				// first byte indicates FIN, Text-Frame (10000001):
				$frameHead[0] = 129;				
			break;			
		
			case 'close':
				// first byte indicates FIN, Close Frame(10001000):
				$frameHead[0] = 136;
			break;
		
			case 'ping':
				// first byte indicates FIN, Ping frame (10001001):
				$frameHead[0] = 137;
			break;
		
			case 'pong':
				// first byte indicates FIN, Pong frame (10001010):
				$frameHead[0] = 138;
			break;
		}
		
		// set mask and payload length (using 1, 3 or 9 bytes) 
		if($payloadLength > 65535)
		{
			$payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
			$frameHead[1] = ($masked === true) ? 255 : 127;
			for($i = 0; $i < 8; $i++)
			{
				$frameHead[$i+2] = bindec($payloadLengthBin[$i]);
			}
			// most significant bit MUST be 0 (close connection if frame too big)
			if($frameHead[2] > 127)
			{
				$this->close(1004);
				return false;
			}
		}
		elseif($payloadLength > 125)
		{
			$payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
			$frameHead[1] = ($masked === true) ? 254 : 126;
			$frameHead[2] = bindec($payloadLengthBin[0]);
			$frameHead[3] = bindec($payloadLengthBin[1]);
		}
		else
		{
			$frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
		}

		// convert frame-head to string:
		foreach(array_keys($frameHead) as $i)
		{
			$frameHead[$i] = chr($frameHead[$i]);
		}
		if($masked === true)
		{
			// generate a random mask:
			$mask = array();
			for($i = 0; $i < 4; $i++)
			{
				$mask[$i] = chr(rand(0, 255));
			}
			
			$frameHead = array_merge($frameHead, $mask);			
		}						
		$frame = implode('', $frameHead);

		// append payload to frame:
		$framePayload = array();	
		for($i = 0; $i < $payloadLength; $i++)
		{		
			$frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
		}

		return $frame;
	}
	
	private function hybi10Decode($data)
	{
		$payloadLength = '';
		$mask = '';
		$unmaskedPayload = '';
		$decodedData = array();
		
		// estimate frame type:
		$firstByteBinary = sprintf('%08b', ord($data[0]));		
		$secondByteBinary = sprintf('%08b', ord($data[1]));
		$opcode = bindec(substr($firstByteBinary, 4, 4));
		$isMasked = ($secondByteBinary[0] == '1') ? true : false;
		$payloadLength = ord($data[1]) & 127;
		
		// close connection if unmasked frame is received:
		if($isMasked === false)
		{
			$this->close(1002);
		}
		
		switch($opcode)
		{
			// text frame:
			case 1:
				$decodedData['type'] = 'text';				
			break;
			
			// connection close frame:
			case 8:
				$decodedData['type'] = 'close';
			break;
			
			// ping frame:
			case 9:
				$decodedData['type'] = 'ping';				
			break;
			
			// pong frame:
			case 10:
				$decodedData['type'] = 'pong';
			break;
			
			default:
				// Close connection on unknown opcode:
				$this->close(1003);
			break;
		}
		
		if($payloadLength === 126)
		{
		   $mask = substr($data, 4, 4);
		   $payloadOffset = 8;
		   $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
		}
		elseif($payloadLength === 127)
		{
			$mask = substr($data, 10, 4);
			$payloadOffset = 14;
			$tmp = '';
			for($i = 0; $i < 8; $i++)
			{
				$tmp .= sprintf('%08b', ord($data[$i+2]));
			}
			$dataLength = bindec($tmp) + $payloadOffset;
			unset($tmp);
		}
		else
		{
			$mask = substr($data, 2, 4);	
			$payloadOffset = 6;
			$dataLength = $payloadLength + $payloadOffset;
		}
		
		if($isMasked === true)
		{
			for($i = $payloadOffset; $i < $dataLength; $i++)
			{
				$j = $i - $payloadOffset;
				$unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
			}
			$decodedData['payload'] = $unmaskedPayload;
		}
		else
		{
			$payloadOffset = $payloadOffset - 4;
			$decodedData['payload'] = substr($data, $payloadOffset);
		}
		
		return $decodedData;
	}
	
	public function getClientIp()
	{
		return $this->ip;
	}
	
	public function getClientPort()
	{
		return $this->port;
	}
	
	public function getClientId()
	{
		return $this->connectionId;
	}
	
	public function getClientSocket()
	{
		return $this->socket;
	}
	
	public function getClientApplication()
	{
		return (isset($this->application)) ? $this->application : false;
	}
}