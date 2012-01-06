$(document).ready ->
	log = (msg) -> $('#log').prepend("#{msg}<br />")	
	serverUrl = 'ws://localhost:8000/status'
	if $.browser.mozilla
		socket = new MozWebSocket(serverUrl)
	else
		socket = new WebSocket(serverUrl)

	socket.onopen = (msg) ->
		$('#status').removeClass().addClass('online').html('connected')

	socket.onmessage = (msg) ->
		response = JSON.parse(msg.data)
		switch response.action
			when "statusMsg"			then log response.data
			when "clientConnected"		then clientConnected response.data
			when "clientDisconnected"	then clientDisconnected response.data
			when "clientList"			then refreshClientlist response.data
			when "clientActivity"		then clientActivity response.data

	socket.onclose = (msg) ->
		$('#status').removeClass().addClass('offline').html('disconnected')

	$('#status').click ->
		socket.close()

	statusMsg = (msg) ->
		log(msg)

	clientConnected = (data) ->
		$('#clientListSelect').append(new Option("#{data.ip}:#{data.port}", data.port))

	clientDisconnected = (port) ->
		$("#clientListSelect option[value='" + port + "']").remove()

	refreshClientlist = (clients) ->
		for port, ip of clients
			$('#clientListSelect').append(new Option(ip + ':' + port, port));

	clientActivity = (port) ->
		$("#clientListSelect option[value='" + port + "']").css("color", "red").animate({opacity: 100}, 600, ->
			$(this).css("color", "black")
		)