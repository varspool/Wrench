<?php

namespace Wrench\Tests;

use Wrench\Util\Ssl;

class SslTest extends Test
{
    /**
     * Gets the class under test
     *
     * @return string
     */
    protected function getClass()
    {
        return 'Wrench\\Util\\Ssl';
    }

    public function setUp()
    {
        parent::setUp();

        $this->tmp = tempnam('/tmp', 'wrench');
    }

    public function tearDown()
    {
        parent::tearDown();

        if ($this->tmp) {
            @unlink($this->tmp);
        }
    }

    public function testGeneratePemWithPassphrase()
    {
        Ssl::generatePemFile(
            $this->tmp,
            'password',
            'nz',
            'Somewhere',
            'Over the rainbow',
            'Way up high',
            'Birds fly, inc.',
            'Over the rainbow division',
            '127.0.0.1',
            'nobody@example.com'
        );

        $this->assertFileExists($this->tmp);

        $contents = file_get_contents($this->tmp);

        $this->assertRegExp('/BEGIN CERTIFICATE/', $contents, 'PEM file contains certificate');
        $this->assertRegExp('/BEGIN ENCRYPTED PRIVATE KEY/', $contents, 'PEM file contains encrypted private key');
    }

    public function testGeneratePemWithoutPassphrase()
    {
        Ssl::generatePemFile(
            $this->tmp,
            null,
            'de',
            'Somewhere',
            'Over the rainbow',
            'Way up high',
            'Birds fly, inc.',
            'Over the rainbow division',
            '127.0.0.1',
            'nobody@example.com'
        );

        $this->assertFileExists($this->tmp);

        $contents = file_get_contents($this->tmp);

        $this->assertRegExp('/BEGIN CERTIFICATE/', $contents, 'PEM file contains certificate');
        $this->assertRegExp('/BEGIN PRIVATE KEY/', $contents, 'PEM file contains encrypted private key');
    }
}
