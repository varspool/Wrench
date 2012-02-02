<?php

namespace WebSocket;

/**
 * Socket class
 *
 * @author Moritz Wutz <moritzwutz@gmail.com>
 * @author Nico Kaiser <nico@kaiser.me>
 * @version 0.2
 */

/**
 * This is the main socket class
 */
class Socket
{
    /**
     * @var Socket Holds the master socket
     */
    protected $master;

    /**
     * @var array Holds all connected sockets
     */
    protected $allsockets = array();
	protected $context = null;

	public function __construct($host = 'localhost', $port = 8000, $ssl = false)
    {
        ob_implicit_flush(true);
        $this->createSocket($host, $port, $ssl);
    }

    /**
     * Create a socket on given host/port
     * 
     * @param string $host The host/bind address to use
     * @param int $port The actual port to bind on
     */
	private function createSocket($host, $port, $ssl = false)
	{
		$protocol = ($ssl === true) ? 'tls://' : 'tcp://';
		$url = $protocol.$host.':'.$port;
		$this->context = stream_context_create();
		
		if($ssl === true)
		{
			// Certificate data:
			$dn = array(
				"countryName" => "DE",
				"stateOrProvinceName" => "none",
				"localityName" => "none",
				"organizationName" => "none",
				"organizationalUnitName" => "none",
				"commonName" => "foo.lh",
				"emailAddress" => "baz@foo.lh"
			);

			// Generate certificate
			$privkey = openssl_pkey_new();
			$cert    = openssl_csr_new($dn, $privkey);
			$cert    = openssl_csr_sign($cert, null, $privkey, 365);

			// Generate PEM file
			# Optionally change the passphrase from 'comet' to whatever you want, or leave it empty for no passphrase
			$pem_passphrase = 'comet';
			$pem = array();
			openssl_x509_export($cert, $pem[0]);
			openssl_pkey_export($privkey, $pem[1], $pem_passphrase);
			$pem = implode($pem);

			// Save PEM file
			$pemfile = './server.pem';
			file_put_contents($pemfile, $pem);

			// local_cert must be in PEM format
			stream_context_set_option($this->context, 'ssl', 'local_cert', $pemfile);
			stream_context_set_option($this->context, 'ssl', 'passphrase', $pem_passphrase);
			stream_context_set_option($this->context, 'ssl', 'allow_self_signed', true);
			stream_context_set_option($this->context, 'ssl', 'verify_peer', false);					
		}
		
		if(!$this->master = stream_socket_server($url, $errno, $err, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $this->context))
		{
			die('Error creating socket: ' . $err);
		}		
		
		$this->allsockets[] = $this->master;
	}  
	
	// method originally found in phpws project:
	protected function readBuffer($resource)
	{		
		$buffer = '';
		$buffsize = 1500;
		$metadata['unread_bytes'] = 0;	
		do
		{
			if(feof($resource))
			{
				return false;
			}			
			$result = fread($resource, $buffsize);			
			if($result === false || feof($resource))
			{
			        return false;
			}
			$buffer .= $result;			
			$metadata = stream_get_meta_data($resource);
		} while($metadata['unread_bytes'] > 0);	
		
		return $buffer;
		
	}
	
	// method originally found in phpws project:
	public function writeBuffer($resource, $string)
	{
		$stringLength = strlen($string);
		for($written = 0; $written < $stringLength; $written += $fwrite)
		{
			$fwrite = fwrite($resource, substr($string, $written));	
			if($fwrite === false)
			{
				return false;
			}
			elseif($fwrite === 0)
			{
				return false;
			}
		}
		return $written;
	}
}