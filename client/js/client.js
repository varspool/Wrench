$(document).ready(function() {		
	log = function(msg){
		$('#log').append(msg + '<br/>')
	};			
	
	var socket;
	if ( $.browser.mozilla )
	{
		socket = new MozWebSocket('ws://localhost:8000/demo');
	}
	else
	{
		socket = new WebSocket('ws://localhost:8000/demo');
	}

	socket.onopen = function(msg){
		$('#status').removeClass().addClass('online').html('online');				
	};
	socket.onmessage = function(msg){
		var response = JSON.parse(msg.data);
		log("Action: " + response.action);
		log("Data: " + response.data);
	};
	socket.onclose = function(msg){
		$('#status').removeClass().addClass('offline').html('offline');
	};

	$('#send').click(function(){
		var payload = new Object();				
		payload['action'] = $('#action').val();
		payload['data'] = $('#data').val();				
		socket.send(JSON.stringify(payload));
	});
});