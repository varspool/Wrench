<?php

namespace Wrench;

use Wrench\Server;

class BasicServer extends Server
{
    protected $rateLimiter;
    protected $originPolicy;

    /**
     * Constructor
     *
     * @param string $uri
     * @param array $options
     */
    public function __construct($uri, array $options = array())
    {
        parent::__construct($uri, $options);

        $this->configureRateLimiter();
        $this->configureOriginPolicy();
    }

    /**
     * @see Wrench.Server::configure()
     */
    protected function configure(array $options)
    {
        $options = array_merge(array(
            'check_origin'         => true,
            'allowed_origins'      => array(),
            'origin_policy_class'  => 'Wrench\Listener\OriginPolicy',
            'rate_limiter_class'   => 'Wrench\Listener\RateLimiter',
            'rate_limiter_options' => array(
                'connections'         => 200, // Total
                'connections_per_ip'  => 5,   // At once
                'requests_per_minute' => 200  // Per connection
            )
        ), $options);

        parent::configure($options);
    }

    protected function configureRateLimiter()
    {
        $class = $this->options['rate_limiter_class'];
        $this->rateLimiter = new $class($this->options['rate_limiter_options']);
        $this->rateLimiter->listen($this);
    }

    /**
     * Configures the origin policy
     */
    protected function configureOriginPolicy()
    {
        $class = $this->options['origin_policy_class'];
        $this->originPolicy = new $class($this->options['allowed_origins']);

        if ($this->options['check_origin']) {
            $this->originPolicy->listen($this);
        }
    }

    /**
     * Adds an allowed origin
     *
     * @param array $origin
     */
    public function addAllowedOrigin($origin)
    {
        $this->originPolicy->addAllowedOrigin($origin);
    }
}
