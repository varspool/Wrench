PHP WebSocket
=============

A simple PHP WebSocket server and client.
ATTENTION: Totally beta and just for testing/development!

- Supports draft hybi-10
- Application module, the server can be extended by custom behaviors

## Server example

This creates a server on localhost:8000 with one Application that listens on `ws://localhost:8000/demo`:

	$server = new \WebSocket\Server('localhost', 8000);

	// server settings:
	$server->setCheckOrigin(true);
	$server->setAllowedOrigin('foo.lh');

	$server->registerApplication('demo', \WebSocket\Application\DemoApplication::getInstance());
	$server->run();

## Libraries used

- [SplClassLoader](http://gist.github.com/221634) by the PHP Standards Working Group
- [jQuery](http://jquery.com/)
