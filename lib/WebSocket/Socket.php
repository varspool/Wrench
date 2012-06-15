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
	protected $ssl = null;
	
	public function __construct($host = 'localhost', $port = 8000, $ssl=null, $pem_file=null, $pem_passphrase=null)
    {
        ob_implicit_flush(true);
		$this->ssl = $ssl;
        $this->createSocket($host, $port, $ssl, $pem_file, $pem_passphrase);
    }

    /**
     * Create a socket on given host/port
     * 
     * @param string $host The host/bind address to use
     * @param int $port The actual port to bind on
     */
	private function createSocket($host, $port, $ssl, $pem_file, $pem_passphrase)
	{
		$protocol = ($this->ssl !== null) ? $ssl.'://' : 'tcp://';
		$url = $protocol.$host.':'.$port;
		$this->context = stream_context_create();
		if($this->ssl !== null)
		{
			$this->applySSLContext($pem_file, $pem_passphrase);
		}
		if(!$this->master = stream_socket_server($url, $errno, $err, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $this->context))
		{
			die('Error creating socket: ' . $err);
		}		
		
		$this->allsockets[] = $this->master;
	}  
	
	private function applySSLContext($pem_file, $pem_passphrase)
	{		
		// apply ssl context:
		stream_context_set_option($this->context, 'ssl', 'local_cert', $pem_file);
		if( $pem_passphrase !== null ) {
			stream_context_set_option($this->context, 'ssl', 'passphrase', $pem_passphrase);
		}
		stream_context_set_option($this->context, 'ssl', 'allow_self_signed', true);
		stream_context_set_option($this->context, 'ssl', 'verify_peer', false);		
	}
	
	// method originally found in phpws project:
	protected function readBuffer($resource)
	{
		if($this->ssl === true)
		{
			$buffer = fread($resource, 8192);
			// extremely strange chrome behavior: first frame with ssl only contains 1 byte?!
			if(strlen($buffer) === 1)
			{
				$buffer .= fread($resource, 8192);
			}		
			return $buffer;
		}
		else
		{
			$buffer = '';
			$buffsize = 8192;
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
				$buffsize = ($metadata['unread_bytes'] > $buffsize) ? $buffsize : $metadata['unread_bytes'];
			} while($metadata['unread_bytes'] > 0);		
			
			return $buffer;
		}
	}
	
	// method originally found in phpws project:
	public function writeBuffer($resource, $string)
	{		
		$stringLength = strlen($string);
		for($written = 0; $written < $stringLength; $written += $fwrite)
		{
			$fwrite = @fwrite($resource, substr($string, $written));			
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
	
	/**
	 * Generates a new PEM File given the informations
	 *
	 * @param $pem_file the path of the PEM file to create
	 * @param $pem_passphrase the passphrase to protect the PEM file or if you don't want to use a passphrase
	 * @param $country_name the country code of the new PEM file. e.g.: EN
	 * @param $state_or_province_name the state or province name of the new PEM file
	 * @param $locality_name the name of the locality
	 * @param $organisation_name the name of the organisation. e.g.: MyCompany
	 * @param $organisational_unit_name the organisation unit name
	 * @param $commonName the common name
	 * @parm $email_addresse the email address
	 */
	public static function generatePEMFile($pem_file, $pem_passphrase, $country_name, $state_or_province_name, 
		$locality_name, $organization_name, $organizational_unit_name, $common_name, $email_address)
	{
		// Generate PEM file
		$dn = array(
			"countryName" => $country_name,
			"stateOrProvinceName" => $state_or_province_name,
			"localityName" => $locality_name,
			"organizationName" => $organization_name,
			"organizationalUnitName" => $organizational_unit_name,
			"commonName" => $common_name,
			"emailAddress" => $email_address
		);
		$privkey = openssl_pkey_new();
		$cert    = openssl_csr_new($dn, $privkey);
		$cert    = openssl_csr_sign($cert, null, $privkey, 365);			
		$pem = array();
		openssl_x509_export($cert, $pem[0]);
		if( $pem_passphrase !== null )
		{
			openssl_pkey_export($privkey, $pem[1], $pem_passphrase);
		}
		$pem = implode($pem);		
		file_put_contents($pem_file, $pem);
	}
}