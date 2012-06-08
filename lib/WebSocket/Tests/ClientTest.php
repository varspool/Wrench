<?php

namespace WebSocket\Tests;

use WebSocket\Client;
use WebSocket\Tests\Test;

class ClientTest extends Test
{

    public function testConstructor()
    {
        $this->assertInstanceOf('WebSocket\Client', new Client(), 'No arguments');

        $this->assertInstanceOf('WebSocket\Client', new Client(array()), 'Empty array');

        $this->assertInstanceOf('WebSocket\Client', new Client(array(
            'salt' => 'klnpuhuIUBBD&*aaa7hda87ad870g*E%^*E%^$%*1391 -+A UAWD-9y231'
        )), 'Salt option');
    }

    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testConstructorTypeHint()
    {
        $w = new Client('Bad argument');
    }
}
