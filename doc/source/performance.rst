.. vim: set tw=78 sw=4 ts=4 :

***********
Performance
***********

Wrench uses a single-process server, without threads, and blocks while
processing data from any client. This means it has little hope of scaling in
production.

You might like to use some middleware between your PHP application code and
WebSocket clients in production. For example, you might use something like
`RabbitMQ's STOMP + WebSockets Plugin
<http://www.rabbitmq.com/blog/2012/05/14/introducing-rabbitmq-web-stomp/>`_. In
any case, if you're hoping to serve large numbers of clients, you should
probably look into one of the evented IO based servers.
