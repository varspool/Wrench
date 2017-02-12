.. vim: set tw=78 sw=4 ts=4 :

***************
Getting Started
***************

-----------------
Starting a Server
-----------------

The first thing you'll want to do to serve WebSockets from PHP is start a
WebSockets server. Wrench provides a simple Server class that implements the
most recent version of the WebSockets protocol. Subclassing the Server class is
encouraged: see WebSocket\BasicServer for an example.

When you're ready for your server to start responding to requests, call
$server->run()::

    use Wrench\BasicServer;

    $server = new BasicServer('ws://localhost:8000', array(
        'allowed_origins' => array(
            'mysite.com',
            'mysite.dev.localdomain'
        )
    ));

    // Logging is via PSR3
    $logger = new Monolog\Logger('name');
    $server->setLogger($logger);

    // Register your applications here
    $server->registerApplication('echo', new \Wrench\Examples\EchoApplication());
    $server->registerApplication('chat', new \My\ChatApplication());

    $server->run();
