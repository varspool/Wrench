<?php

namespace WebSocket;

/**
 * WebSocket Connection class
 *
 * @author Nico Kaiser <nico@kaiser.me>
 */
class Connection
{
    private $server;
    
    private $socket;

    private $handshaked = false;

    private $application = null;
    
    public function __construct($server, $socket)
    {
        $this->server = $server;
        $this->socket = $socket;

        $this->log('Connected');
    }
    
    private function handshake($data)
    {
        $this->log('Performing handshake');
        
        $lines = preg_split("/\r\n/", $data);
        if (count($lines)  && preg_match('/<policy-file-request.*>/', $lines[0])) {
            $this->log('Flash policy file request');
            $this->serveFlashPolicy();
            return false;
        }

        if (! preg_match('/\AGET (\S+) HTTP\/1.1\z/', $lines[0], $matches)) {
            $this->log('Invalid request: ' . $lines[0]);
            socket_close($this->socket);
            return false;
        }
        
        $path = $matches[1];

        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }

        $key3 = '';
        preg_match("#\r\n(.*?)\$#", $data, $match) && $key3 = $match[1];

        $origin = $headers['Origin'];
        $host = $headers['Host'];

        $this->application = $this->server->getApplication(substr($path, 1)); // e.g. '/echo'
        if (! $this->application) {
            $this->log('Invalid application: ' . $path);
            socket_close($this->socket);
            return false;
        }
        
        $status = '101 Web Socket Protocol Handshake';
        if (array_key_exists('Sec-WebSocket-Key1', $headers)) {
            // draft-76
            $def_header = array(
                'Sec-WebSocket-Origin' => $origin,
                'Sec-WebSocket-Location' => "ws://{$host}{$path}"
            );
            $digest = $this->securityDigest($headers['Sec-WebSocket-Key1'], $headers['Sec-WebSocket-Key2'], $key3);
        } else {
            // draft-75
            $def_header = array(
                'WebSocket-Origin' => $origin,
                'WebSocket-Location' => "ws://{$host}{$path}"  
            );
            $digest = '';
        }
        $header_str = '';
        foreach ($def_header as $key => $value) {
            $header_str .= $key . ': ' . $value . "\r\n";
        }

        $upgrade = "HTTP/1.1 ${status}\r\n" .
            "Upgrade: WebSocket\r\n" .
            "Connection: Upgrade\r\n" .
            "${header_str}\r\n$digest";

        socket_write($this->socket, $upgrade, strlen($upgrade));
        
        $this->handshaked = true;
        $this->log('Handshake sent');

        $this->application->onConnect($this);

        return true;
    }
    
    public function onData($data)
    {
        if ($this->handshaked) {
            $this->handle($data);
        } else {
            $this->handshake($data);
        }
    }
    
    private function handle($data)
    {
        $chunks = explode(chr(255), $data);

        for ($i = 0; $i < count($chunks) - 1; $i++) {
            $chunk = $chunks[$i];
            if (substr($chunk, 0, 1) != chr(0)) {
                $this->log('Data incorrectly framed. Dropping connection');
                socket_close($this->socket);
                return false;
            }
            $this->application->onData(substr($chunk, 1), $this);
        }

        return true;
    }
    
    private function serveFlashPolicy()
    {
        $policy = '<?xml version="1.0"?>' . "\n";
        $policy .= '<!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd">' . "\n";
        $policy .= '<cross-domain-policy>' . "\n";
        $policy .= '<allow-access-from domain="*" to-ports="*"/>' . "\n";
        $policy .= '</cross-domain-policy>' . "\n";
        socket_write($this->socket, $policy, strlen($policy));
        socket_close($this->socket);
    }
    
    public function send($data)
    {
        if (! @socket_write($this->socket, chr(0) . $data . chr(255), strlen($data) + 2)) {
            @socket_close($this->socket);
            $this->socket = false;
        }
    }
    
    public function onDisconnect()
    {
        $this->log('Disconnected', 'info');
        
        if ($this->application) {
            $this->application->onDisconnect($this);
        }
        socket_close($this->socket);
    }

    private function securityDigest($key1, $key2, $key3)
    {
        return md5(
            pack('N', $this->keyToBytes($key1)) .
            pack('N', $this->keyToBytes($key2)) .
            $key3, true);
    }

    /**
     * WebSocket draft 76 handshake by Andrea Giammarchi
     * see http://webreflection.blogspot.com/2010/06/websocket-handshake-76-simplified.html
     */
    private function keyToBytes($key)
    {
        return preg_match_all('#[0-9]#', $key, $number) && preg_match_all('# #', $key, $space) ?
            implode('', $number[0]) / count($space[0]) :
            '';
    }

    public function log($message, $type = 'info')
    {
        socket_getpeername($this->socket, $addr, $port);
        $this->server->log('[client ' . $addr . ':' . $port . '] ' . $message, $type);
    }
}