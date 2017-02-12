<?php

namespace Wrench\Util;

use InvalidArgumentException;
use Wrench\Protocol\Protocol;
use Wrench\Protocol\Rfc6455Protocol;

/**
 * Configurable base class
 */
abstract class Configurable
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var Protocol
     */
    protected $protocol;

    /**
     * Configurable constructor
     *
     * @param array $options           (optional)
     *                                 Options:
     *                                 - protocol             => Wrench\Protocol object, latest protocol
     *                                 version used if not specified
     */
    public function __construct(
        array $options = []
    ) {
        $this->configure($options);
        $this->configureProtocol();
    }

    /**
     * Configures the options
     *
     * @param array $options
     */
    protected function configure(array $options)
    {
        $this->options = array_merge([
            'protocol' => new Rfc6455Protocol(),
        ], $options);
    }

    /**
     * Configures the protocol option
     *
     * @throws InvalidArgumentException
     */
    protected function configureProtocol()
    {
        $protocol = $this->options['protocol'];

        if (!$protocol || !($protocol instanceof Protocol)) {
            throw new InvalidArgumentException('Invalid protocol option');
        }

        $this->protocol = $protocol;
    }
}
