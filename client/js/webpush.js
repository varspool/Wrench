
var WebPush = function(url) {
    this.callbacks = {};
    this.url = url;
    this.connected = false;
    this.retries = 0;
    this.connect();

    var self = this;
};

WebPush.prototype = {

    connect: function() {        
        WebPush.allow_reconnect = true;
        WebPush.log('WebPush: Connecting: ' + this.url);

        var self = this;

        if (window["WebSocket"]) {
            this.connection = new WebSocket(this.url);
            this.connection.onmessage = function() {
                self.onmessage.apply(self, arguments);
            };
            this.connection.onclose = function() {
                self.onclose.apply(self, arguments);
            };
            this.connection.onopen = function() {
                self.onopen.apply(self, arguments);
            }
        } else {
            this.connection = {};
            setTimeout(function(){
                self.dispatch("connection_failed", {})
            }, 3000);
        }
    },

    disconnect: function() {
        WebPush.log('WebPush: disconnecting');
        WebPush.allow_reconnect = false;
        WebPush.retries = 0;
        this.connection.close();
    },

    bind: function(event_name, callback) {
        this.callbacks[event_name] = this.callbacks[event_name] || [];
        this.callbacks[event_name].push(callback);
        return this;
    },
    
    dispatch: function(event_name, data) {
        var callbacks = this.callbacks[event_name];
        if (callbacks) {
            for (var i = 0; i < callbacks.length; i++) {
                callbacks[i](data);
            }
        } else {
            WebPush.log('WebPush: No callbacks for ' + event_name);
        }
    },

    send: function(data) {
        this.connection.send(data);
    },

    onmessage: function(evt) {
        this.dispatch('message', evt.data);
    },

    onclose: function() {
        var self = this;
        this.dispatch('close', null);
        WebPush.log("WebPush: Socket closed");
        var time = 5000;
        if (this.connected == true) {
            this.dispatch("connection_disconnected", {});
            if (WebPush.allow_reconnect) {
                WebPush.log('WebPush: Reconnecting in 5 seconds...');
                setTimeout(function(){
		            self.connect();
		        }, time);
            }
        } else {
            self.dispatch("connection_failed", {});
            this.retries = this.retries + 1;
            setTimeout(function(){
	            self.connect();
	        }, time);
	
            if (this.retries == 0) {
                time = 100;
            }
        }
        this.connected = false;
    },

    onopen: function() {
        this.dispatch('open', null);
    }
};

WebPush.log = function(msg){};
WebPush.allow_reconnect = true;
