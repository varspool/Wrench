$(document).ready ->
	log = (msg) -> $('#log').prepend("#{msg}<br />")	
	serverUrl = 'ws://localhost:8000/status'
	if window.MozWebSocket		
		socket = new MozWebSocket serverUrl
	else if window.WebSocket		
		socket = new WebSocket serverUrl

	socket.onopen = (msg) ->
		$('#status').removeClass().addClass('online').html('connected')

	socket.onmessage = (msg) ->
		response = JSON.parse(msg.data)
		switch response.action
			when "statusMsg"			then statusMsg response.data
			when "clientConnected"		then clientConnected response.data
			when "clientDisconnected"	then clientDisconnected response.data			
			when "clientActivity"		then clientActivity response.data
			when "serverInfo"			then refreshServerinfo response.data

	socket.onclose = (msg) ->
		$('#status').removeClass().addClass('offline').html('disconnected')

	$('#status').click ->
		socket.close()

	statusMsg = (msgData) ->
		switch msgData.type
			when "info" then log msgData.text
			when "warning" then log "<span class=\"warning\">#{msgData.text}</span>"

	clientConnected = (data) ->		
		$('#clientListSelect').append(new Option("#{data.ip}:#{data.port}", data.port))
		$('#clientCount').text(data.clientCount)

	clientDisconnected = (data) ->
		$("#clientListSelect option[value='#{data.port}']").remove()
		$('#clientCount').text(data.clientCount)

	refreshServerinfo = (serverinfo) ->	
		$('#clientCount').text(serverinfo.clientCount)
		$('#maxClients').text(serverinfo.maxClients)
		$('#maxConnections').text(serverinfo.maxConnectionsPerIp)
		$('#maxRequetsPerMinute').text(serverinfo.maxRequetsPerMinute)
		for port, ip of serverinfo.clients			
			$('#clientListSelect').append(new Option(ip + ':' + port, port));	

	clientActivity = (port) ->
		$("#clientListSelect option[value='#{port}']").css("color", "red").animate({opacity: 100}, 600, ->
			$(this).css("color", "black")
		)