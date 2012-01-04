$(document).ready(function() {		
	log = function(msg){
		$('#log').prepend(msg + '<br />')
	};
	
	var socket;
	if ( $.browser.mozilla )
	{
		socket = new MozWebSocket('ws://localhost:8000/status');
	}
	else
	{
		socket = new WebSocket('ws://localhost:8000/status');
	}	

	socket.onopen = function(msg){
		$('#status').removeClass().addClass('online').html('connected');				
	};
	socket.onmessage = function(msg){
		var response = JSON.parse(msg.data);
		switch(response.action)
		{
			case 'statusMsg':
				log(response.data);
			break;
			
			case 'clientConnected':
				clientConnected(response.data);
			break;
			
			case 'clientDisconnected':
				clientDisconnected(response.data);
			break;
			
			case 'clientList':
				refreshClientlist(response.data);
			break;
			
			case 'clientActivity':
				clientActivity(response.data);
			break;
		}
	};
	socket.onclose = function(msg){
		$('#status').removeClass().addClass('offline').html('disconnected');
	};

	$('#send').click(function(){
		var payload = new Object();				
		payload['action'] = $('#action').val();
		payload['data'] = $('#data').val();				
		socket.send(JSON.stringify(payload));
	});	
	
	$('#status').click(function(){
		socket.close();		
	});
	
	function statusMsg(msg)
	{
		log(msg);
	}
	
	function clientConnected(data)
	{		
		$('#clientListSelect').append(new Option(data.ip + ':' + data.port, data.port));		
	}
	
	function clientDisconnected(port)
	{
		$("#clientListSelect option[value='" + port + "']").remove();
	}
	
	function refreshClientlist(clients)
	{
		for(port in clients)
		{
			$('#clientListSelect').append(new Option(clients[port] + ':' + port, port));
		}
	}
	
	function clientActivity(port)
	{
		$("#clientListSelect option[value='" + port + "']").css("color", "red").animate({opacity: 100}, 600, function(){ 
			$(this).css("color", "black")
		});
	}
});