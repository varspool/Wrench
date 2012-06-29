<?php

namespace WebSocket\Protocol;

use WebSocket\Payload\HybiPayload;

use WebSocket\Exception\ConnectionException;

use WebSocket\Protocol\Protocol;
use \InvalidArgumentException;

/**
 * @see http://tools.ietf.org/html/rfc6455#section-5.2
 */
abstract class HybiProtocol extends Protocol
{
    /**
     * @see WebSocket\Protocol.Protocol::getPayload()
     */
    public function getPayload()
    {
        return new HybiPayload();
    }

    /**
     * @see WebSocket\Protocol.Protocol::encode($data, $payload, $masked)
     * @deprecated
     */
    public function encode($payload, $type = self::PAYLOAD_TEXT, $masked = true)
    {
        if (is_string($type)) {
            $type = isset($this->payloadTypes[$type]) ? $this->payloadTypes[$type] : false;
        }

        if (!in_array($type, $this->payloadTypes)) {
            throw new InvalidArgumentException('Invalid payload type');
        }

        $frameHead = array($type);
        $frame = '';
        $payloadLength = strlen($payload);

        // set mask and payload length (using 1, 3 or 9 bytes)
        if($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for($i = 0; $i < 8; $i++) {
                $frameHead[$i+2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0 (close connection if frame too big)
            if($frameHead[2] > 127) {
                $this->close(1004);
                return false;
            }
        } elseif($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }

        // convert frame-head to string:
        foreach(array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }

        if ($masked === true) {
            // generate a random mask:
            $mask = array();
            for($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }
            $frameHead = array_merge($frameHead, $mask);
        }

        $frame = implode('', $frameHead);

        // append payload to frame:
        $framePayload = array();
        for($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    /**
     * @deprecated Just use Payload class directly
     */
    public function decode($data)
    {
        $payload = new HybiPayload();
        $payload->decode($data);

        if (!$payload->isMasked()) {
            throw new ConnectionException('Unmasked frame received');
        }

//         $payloadLength = '';
//         $mask = '';
//         $unmaskedPayload = '';
//         $decodedData = array();

        // estimate frame type:
//         $firstByteBinary = sprintf('%08b', ord($data[0]));
//         $secondByteBinary = sprintf('%08b', ord($data[1]));


//         $opcode = bindec(substr($firstByteBinary, 4, 4));
//         $isMasked = ($secondByteBinary[0] == '1') ? true : false;

//         $payloadLength = ord($data[1]) & 127;

        // close connection if unmasked frame is received:
//         if($isMasked === false)
//         {
//             $this->close(1002);
//         }

//         switch($opcode)
//         {
//             // text frame:
//             case 1:
//                 $decodedData['type'] = 'text';
//             break;

//             case 2:
//                 $decodedData['type'] = 'binary';
//             break;

//             // connection close frame:
//             case 8:
//                 $decodedData['type'] = 'close';
//             break;

//             // ping frame:
//             case 9:
//                 $decodedData['type'] = 'ping';
//             break;

//             // pong frame:
//             case 10:
//                 $decodedData['type'] = 'pong';
//             break;

//             default:
//                 // Close connection on unknown opcode:
//                 $this->close(1003);
//             break;
//         }

//         if($payloadLength === 126)
//         {
//            $mask = substr($data, 4, 4);
//            $payloadOffset = 8;
//            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
//         }
//         elseif($payloadLength === 127)
//         {
//             $mask = substr($data, 10, 4);
//             $payloadOffset = 14;
//             $tmp = '';
//             for($i = 0; $i < 8; $i++)
//             {
//                 $tmp .= sprintf('%08b', ord($data[$i+2]));
//             }
//             $dataLength = bindec($tmp) + $payloadOffset;
//             unset($tmp);
//         }
//         else
//         {
//             $mask = substr($data, 2, 4);
//             $payloadOffset = 6;
//             $dataLength = $payloadLength + $payloadOffset;
//         }

//         /**
//          * We have to check for large frames here. socket_recv cuts at 1024 bytes
//          * so if websocket-frame is > 1024 bytes we have to wait until whole
//          * data is transferd.
//          */
//         if(strlen($data) < $dataLength)
//         {
//             return false;
//         }

//         if($isMasked === true)
//         {
//             for($i = $payloadOffset; $i < $dataLength; $i++)
//             {
//                 $j = $i - $payloadOffset;
//                 if(isset($data[$i]))
//                 {
//                     $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
//                 }
//             }
//             $decodedData['payload'] = $unmaskedPayload;
//         }
//         else
//         {
//             $payloadOffset = $payloadOffset - 4;
//             $decodedData['payload'] = substr($data, $payloadOffset);
//         }

//         return $decodedData;
    }
}