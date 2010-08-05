PHP WebSocket
=============

A simple PHP 5.3 WebSocket server implementation that follows draft 75 and draft 76 if the WebSockets specification.

- Supports draft 75 and draft 76 of WebSocket RFC
- Supports Flash Socket Policy requests
- Application module, the server can be extended by custom behaviors

## Server example

This creates a server on localhost:8000 with one Application that listens on `ws://localhost:8000/echo`:

	$server = new \WebSocket\Server('localhost', 8000);
	$server->registerApplication('echo', \WebSocket\Application\EchoApplication::getInstance());
	$server->run();

## Example server Application

This server Applications simply echoes all messages back to all connected clients.

	class EchoApplication extends Application
	{
	    private $clients = array();

		public function onConnect($client)
	    {
	        $this->clients[] = $client;
	    }

	    public function onDisconnect($client)
	    {
	        $key = array_search($client, $this->clients);
	        if ($key) {
	            unset($this->clients[$key]);
	        }
	    }

	    public function onData($data, $client)
	    {
	        foreach ($this->clients as $sendto) {
	            $sendto->send($data);
	        }
	    }
	}

## Client

	var server = new WebPush('ws://localhost:8000/echo');
	
	server.bind('open', function() {
		// Connection openend...
		server.send("Hello, I'm there!");
	});
	
	server.bind('close', function() {
		// Connection closed... 
	});
	
	server.bind('message', function(data) {
		// Data received
	});	

## Libraries used

- [SplClassLoader](http://gist.github.com/221634) by the PHP Standards Working Group
- [phpWebSockets](http://code.google.com/p/phpwebsockets/) by Moritz Wutz
- [jQuery](http://jquery.com/)
- [web-socket-js](http://github.com/gimite/web-socket-js) by Hiroshi Ichikawa
- [SWFObject](http://code.google.com/p/swfobject/)
