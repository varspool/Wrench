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

    private $wsversion;
    
    private $needBytes = 0;
    
    private $cursor;
    private $opcode;
    private $isFinal;
    private $frame;
    private $payload;
    
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
        if(array_key_exists('Sec-WebSocket-Key', $headers))
        {
            // hybi-07 ~ RCF
            $this->wsversion = 7;
            $digest = base64_encode(sha1($headers['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        } elseif (array_key_exists('Sec-WebSocket-Key1', $headers)) {
            // hixi-76
            $this->wsversion = 76;
            $def_header = array(
                'Sec-WebSocket-Origin' => $origin,
                'Sec-WebSocket-Location' => "ws://{$host}{$path}"
            );
            $digest = $this->securityDigest($headers['Sec-WebSocket-Key1'], $headers['Sec-WebSocket-Key2'], $key3);
        } else {
            // hixi-75
            $this->wsversion = 75;
            $def_header = array(
                'WebSocket-Origin' => $origin,
                'WebSocket-Location' => "ws://{$host}{$path}"  
            );
            $digest = '';
        }
        $upgrade = '';
        if($this->wsversion == 7){
            $upgrade = "HTTP/1.1 101 Switching Protocols\r\n" .
                                "Upgrade: websocket\r\n" .
                                "Connection: Upgrade\r\n" .
                                "Sec-WebSocket-Accept: $digest\r\n\r\n";
        } else{
            $header_str = '';
            foreach ($def_header as $key => $value) {
                $header_str .= $key . ': ' . $value . "\r\n";
            }
            $upgrade = "HTTP/1.1 ${status}\r\n" .
                "Upgrade: WebSocket\r\n" .
                "Connection: Upgrade\r\n" .
                "${header_str}\r\n$digest";
        }
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
        if($this->wsversion == 7){
            if($this->needBytes == 0){
                $this->frame = $data;
            } else {
                $this->frame .= $data;
            }
            if($this->getPayload()) return;
            switch($this->opcode){
                case 8: // close
                    socket_close($this->socket);
                    return;
                case 9: // ping
                    // pong返信
                    $this->send(chr(10) . $this->payload);
                    return;
            }
            $this->application->onData(chr($this->opcode) . $this->payload, $this);
        } else {
            if($this->needBytes == 0){
                $this->frame = $data;
                if($data[strlen($data) - 1] != chr(255)){
                    $this->needBytes = 1;
                }
            } else {
                $this->frame .= $data;
                if($data[strlen($data) - 1] == chr(255)){
                    $this->needBytes = 0;
                }
            }
            $chunks = explode(chr(255), $this->frame);
            for ($i = 0; $i < count($chunks) - 1; $i++) {
                $chunk = $chunks[$i];
                if (substr($chunk, 0, 1) != chr(0)) {
                    $this->log('Data incorrectly framed. Dropping connection');
                    socket_close($this->socket);
                    return false;
                }
                $this->application->onData(chr(1) . substr($chunk, 1), $this);
            }
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
        $opcode = ord(substr($data, 0, 1));
        $data = substr($data, 1);
        $sendData = '';
        if($this->wsversion == 7){
            $this->createFrame($data, $opcode);
        } else {
            if($opcode == 1){
                $this->frame = chr(0) . $data . chr(255);
            }
        }
        if (! @socket_write($this->socket, $this->frame, strlen($this->frame))) {
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
    
    private function getPayload(){
        $this->cursor = 0;
        $co = $this->readByte();
        $this->isFinal = ($co & 0x80) == 0x80;
        $this->opcode = ($co & 0x0f);
        $ml = $this->readByte();
        $isMask = ($ml & 0x80) == 0x80;
        $len = $ml & 0x7f;
        $this->needBytes = 2;
        if($len == 126){
            $this->needBytes += 2;
            if(strlen($this->frame) < $this->needBytes){
                return true;
            }
            $len = $this->readNumeric(2);
        } elseif($len == 127){
            $this->needBytes += 8;
            if(strlen($this->frame) < $this->needBytes){
                return true;
            }
            $len = $this->readNumeric(8);
        }
        if($isMask){
            $this->needBytes += 4;
            if(strlen($this->frame) < $this->needBytes){
                return true;
            }
            $mask = $this->readBytes(4);
        }
        $this->needBytes += $len;
        if(strlen($this->frame) < $this->needBytes){
            return true;
        }
        $this->payload = $this->readBytes($len);
        if($isMask){
            $this->payload = $this->unmask($mask, $this->payload);
        }
        $this->needBytes = 0;
        return false;
    }
    private function readByte(){
        $ret = ord($this->frame[$this->cursor++]); 
        return $ret;
    }
    private function readBytes($size){
        $ret = substr($this->frame, $this->cursor, $size);
        $this->cursor += $size;
        return $ret;
    }
    private function readNumeric($size){
        for (;$size > 0; $size--) {
            $value <<= 8;
            $value += ord($this->frame[$this->cursor++]);
        }
        return $value;
    }
    private function unmask($mask, $data){
        for($i = 0; $i < strlen($data); $i++){
            $data[$i] = $mask[$i % 4] ^ $data[$i];
        }
        return $data;
    }
    
    private function createFrame($data, $opcode){
        $this->frame = '';
        if($data == null) $data = '';
        $len = strlen($data);
        $ml = '';
        if($len > 126){
            if($len < 65536){
                $lenb = 2;
                $ml= chr(126);
            } else {
                $lenb = 8;
                $ml = chr(127);
            }
            for($i = 0; $i < $lenb; $i++){
                $this->frame = chr($len & 0xff) . $this->frame ;
                $len >>= 8;
            }
        } else {
            $this->frame = chr($len);
        }
        $this->frame = chr(0x80 | $opcode) . $ml . $this->frame . $data;
    }
      
    public function log($message, $type = 'info')
    {
        socket_getpeername($this->socket, $addr, $port);
        $this->server->log('[client ' . $addr . ':' . $port . '] ' . $message, $type);
    }
}