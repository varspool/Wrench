<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Very basic websocket client.
 * Supporting handshake from drafts:
 *	draft-hixie-thewebsocketprotocol-76
 *	draft-ietf-hybi-thewebsocketprotocol-00
 *  draft-ietf-hybi-thewebsocketprotocol-10
 * 
 * @author Simon Samtleben <web@lemmingzshadow.net>
 * @version 2011-09-20
 */

class WebsocketClient
{
	const DRAFT = 'hybi10'; // currently supports hypi00 and hybi10

	private $_Socket = null;
	
	public function __construct($host, $port, $path = '/')
	{
		$this->_connect($host, $port, $path);	
	}
	
	public function __destruct()
	{
		$this->_disconnect();
	}

	public function sendData($data)
	{
		switch(self::DRAFT)
		{
			case 'hybi00':
				fwrite($this->_Socket, "\x00" . $data . "\xff" ) or die('Error:' . $errno . ':' . $errstr); 
				$wsData = fread($this->_Socket, 2000);
				$retData = trim($wsData,"\x00\xff");		
			break;
		
			case 'hybi10':
				fwrite($this->_Socket, $this->_hybi10EncodeData($data)) or die('Error:' . $errno . ':' . $errstr); 
				$wsData = fread($this->_Socket, 2000);				
				$retData = $this->_hybi10DecodeData($wsData);
			break;
		}
		
		return $retData;
	}

	private function _connect($host, $port, $path)
	{
		switch(self::DRAFT)
		{
			case 'hybi00':
				$key1 = $this->_generateRandomString(32);
				$key2 = $this->_generateRandomString(32);
				$key3 = $this->_generateRandomString(8, false, true);		

				$header = "GET " . $path . " HTTP/1.1\r\n";
				$header.= "Upgrade: WebSocket\r\n";
				$header.= "Connection: Upgrade\r\n";
				$header.= "Host: ".$host.":".$port."\r\n";
				$header.= "Origin: http://foobar.com\r\n";
				$header.= "Sec-WebSocket-Key1: " . $key1 . "\r\n";
				$header.= "Sec-WebSocket-Key2: " . $key2 . "\r\n";
				$header.= "\r\n";
				$header.= $key3;
			break;
		
			case 'hybi10':
				$key = base64_encode($this->_generateRandomString(16, false, true));
				
				$header = "GET " . $path . " HTTP/1.1\r\n";
				$header.= "Host: ".$host.":".$port."\r\n";
				$header.= "Upgrade: websocket\r\n";
				$header.= "Connection: Upgrade\r\n";
				$header.= "Sec-WebSocket-Key: " . $key . "\r\n";
				$header.= "Sec-WebSocket-Origin: http://foobar.com\r\n";				
				$header.= "Sec-WebSocket-Version: 8\r\n";
			break;
		}		
		
		$this->_Socket = fsockopen($host, $port, $errno, $errstr, 2); 
		fwrite($this->_Socket, $header) or die('Error: ' . $errno . ':' . $errstr); 
		$response = fread($this->_Socket, 2000);		

		if(self::DRAFT === 'hybi10')
		{
			preg_match('#Sec-WebSocket-Accept:\s(.*)$#mU', $response, $matches);
			$keyAccept = trim($matches[1]);
			$expectedResonse = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
			
			return ($keyAccept === $expectedResonse) ? true : false;
		}
		else
		{
			/**
			 * No key verification for draft hybi00, cause it's already deprecated.
			 */
			return true;
		}	
	}
	
	private function _disconnect()
	{
		fclose($this->_Socket);
	}

	private function _generateRandomString($length = 10, $addSpaces = true, $addNumbers = true)
	{  
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';
		$useChars = array();
		// select some random chars:    
		for($i = 0; $i < $length; $i++)
		{
			$useChars[] = $characters[mt_rand(0, strlen($characters)-1)];
		}
		// add spaces and numbers:
		if($addSpaces === true)
		{
			array_push($useChars, ' ', ' ', ' ', ' ', ' ', ' ');
		}
		if($addNumbers === true)
		{
			array_push($useChars, rand(0,9), rand(0,9), rand(0,9));
		}
		shuffle($useChars);
		$randomString = trim(implode('', $useChars));
		$randomString = substr($randomString, 0, $length);
		return $randomString;
	}
	
	private function _hybi10EncodeData($data)
	{
		$frame = Array();
		$mask = array(rand(0, 255), rand(0, 255), rand(0, 255), rand(0, 255));
		$encodedData = '';
		$frame[0] = 0x81;
		$dataLength = strlen($data);

		if($dataLength <= 125)
		{		
			$frame[1] = $dataLength + 128;		
		}
		else
		{
			$frame[1] = 254;  
			$frame[2] = $dataLength >> 8;
			$frame[3] = $dataLength & 0xFF; 
		}	
		$frame = array_merge($frame, $mask);	
		for($i = 0; $i < strlen($data); $i++)
		{		
			$frame[] = ord($data[$i]) ^ $mask[$i % 4];
		}

		for($i = 0; $i < sizeof($frame); $i++)
		{
			$encodedData .= chr($frame[$i]);
		}		
		
		return $encodedData;
	}
	
	private function _hybi10DecodeData($data)
	{		
		$bytes = $data;
		$dataLength = '';
		$mask = '';
		$coded_data = '';
		$decodedData = '';
		$secondByte = sprintf('%08b', ord($bytes[1]));		
		$masked = ($secondByte[0] == '1') ? true : false;		
		$dataLength = ($masked === true) ? ord($bytes[1]) & 127 : ord($bytes[1]);
		if($masked === true)
		{
			if($dataLength === 126)
			{
			   $mask = substr($bytes, 4, 4);
			   $coded_data = substr($bytes, 8);
			}
			elseif($dataLength === 127)
			{
				$mask = substr($bytes, 10, 4);
				$coded_data = substr($bytes, 14);
			}
			else
			{
				$mask = substr($bytes, 2, 4);		
				$coded_data = substr($bytes, 6);		
			}	
			for($i = 0; $i < strlen($coded_data); $i++)
			{		
				$decodedData .= $coded_data[$i] ^ $mask[$i % 4];
			}
		}
		else
		{
			if($dataLength === 126)
			{		   
			   $decodedData = substr($bytes, 4);
			}
			elseif($dataLength === 127)
			{			
				$decodedData = substr($bytes, 10);
			}
			else
			{				
				$decodedData = substr($bytes, 2);		
			}		
		}
		
		return $decodedData;
	}
}

$WebSocketClient = new WebsocketClient('127.0.0.1', 8000, '/echo');
echo $WebSocketClient->sendData('foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar foo bar ');
unset($WebSocketClient);