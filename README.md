PHP WebSocket
=============
A websocket server implemented in php.

- Supports websocket draft hybi-10,13 (Currently tested with Chrome 16 and Firefox 9).
- Supports origin-check.
- Supports various security/performance settings.
- Supports binary frames. (Currently receive only)
- Supports wss (Very Alpha! Chrome only!)
- Application module, the server can be extended by custom behaviors.

## Bugs/Todos/Hints
- Optimize whole WSS/TLS stuff
- Optimize readBuffer() method. (Ideas welcome!)
- Add support for fragmented frames.

## Server example

This creates a server on localhost:8000 with one Application that listens on `ws://localhost:8000/demo`:

	$server = new \WebSocket\Server('127.0.0.1', 8000, false); // host,port,ssl

	// server settings:	
	$server->setCheckOrigin(true);
	$server->setAllowedOrigin('foo.lh');
	$server->setMaxClients(20);
	$server->setMaxConnectionsPerIp(5);
	$server->setMaxRequestsPerMinute(50);

	$server->registerApplication('demo', \WebSocket\Application\DemoApplication::getInstance());
	$server->run();

## Libraries used

- [SplClassLoader](http://gist.github.com/221634) by the PHP Standards Working Group
- [jQuery](http://jquery.com/)
- [CoffeeScript PHP] (https://github.com/alxlit/coffeescript-php)