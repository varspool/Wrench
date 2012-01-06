(function() {
  $(document).ready(function() {
    var clientActivity, clientConnected, clientDisconnected, log, refreshClientlist, serverUrl, socket, statusMsg;
    log = function(msg) {
      return $('#log').prepend("" + msg + "<br />");
    };
    serverUrl = 'ws://localhost:8000/status';
    if ($.browser.mozilla) {
      socket = new MozWebSocket(serverUrl);
    } else {
      socket = new WebSocket(serverUrl);
    }
    socket.onopen = function(msg) {
      return $('#status').removeClass().addClass('online').html('connected');
    };
    socket.onmessage = function(msg) {
      var response;
      response = JSON.parse(msg.data);
      switch (response.action) {
        case "statusMsg":
          return log(response.data);
        case "clientConnected":
          return clientConnected(response.data);
        case "clientDisconnected":
          return clientDisconnected(response.data);
        case "clientList":
          return refreshClientlist(response.data);
        case "clientActivity":
          return clientActivity(response.data);
      }
    };
    socket.onclose = function(msg) {
      return $('#status').removeClass().addClass('offline').html('disconnected');
    };
    $('#status').click(function() {
      return socket.close();
    });
    statusMsg = function(msg) {
      return log(msg);
    };
    clientConnected = function(data) {
      return $('#clientListSelect').append(new Option("" + data.ip + ":" + data.port, data.port));
    };
    clientDisconnected = function(port) {
      return $("#clientListSelect option[value='" + port + "']").remove();
    };
    refreshClientlist = function(clients) {
      var ip, port, _results;
      _results = [];
      for (port in clients) {
        ip = clients[port];
        _results.push($('#clientListSelect').append(new Option(ip + ':' + port, port)));
      }
      return _results;
    };
    return clientActivity = function(port) {
      return $("#clientListSelect option[value='" + port + "']").css("color", "red").animate({
        opacity: 100
      }, 600, function() {
        return $(this).css("color", "black");
      });
    };
  });
}).call(this);