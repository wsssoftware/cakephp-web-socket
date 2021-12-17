
let CakePHPWebSocket = {
    socket: null,
    debug: false,
    connect: function (address, initialPayload, debug = false) {
        let parent = this;
        let serverUrl = address;
        this.debug = debug;
        this.socket = new WebSocket(serverUrl);

        if (debug) {
            console.log('CakeWebSocket: Attempting to connect on "' + serverUrl + '"...');
            setInterval(function () {
                console.log('CakeWebSocket: connection status: ' + parent.readyStateLabel(parent.socket.readyState));
            }, 60000);
        }

        this.socket.onopen = this.onOpen(initialPayload);
        this.socket.onmessage = this.onMessage();
        this.socket.onerror = this.onError();
        this.socket.onclose = this.onClose();
    },
    addListener: function (controller, action, callable) {
        if (this.debug) {
            console.log('CakeWebSocket: A new listener was added to controller "' + controller + '" and "' + action  + '"');
        }
        document.addEventListener(controller + '.' + action, function (e) {
            callable(e.detail.payload);
        });
    },
    sendMessage: function (controller, action, payload) {
        if (this.socket === null) {
            console.error('CakeWebSocket is not connected')
            return;
        }

        if (this.socket.readyState === WebSocket.OPEN) {
            this.socket.send(JSON.stringify({
                'controller': controller,
                'action': action,
                'payload': payload,
            }));
            if (this.debug) {
                console.log('CakeWebSocket: Message sent to controller "' + controller + '" and action "' + action  + '"', payload);
            }
        } else {
            console.error('CakeWebSocket: A attempt to send a message message to controller "' + controller + '" and action "' + action + '" did not send because the socket state is: ' + this.readyStateLabel(this.socket.readyState))
        }
    },
    readyStateLabel: function (readyState) {
        switch (readyState) {
            case WebSocket.OPEN:
                return 'OPEN';
            case WebSocket.CLOSED:
                return 'CLOSED';
            case WebSocket.CLOSING:
                return 'CLOSING';
            case WebSocket.CONNECTING:
                return 'CONNECTING';
            default:
                return 'OTHER (' + readyState + ')';
        }
    },
    closeCodeLabel: function (event) {
        // See https://www.rfc-editor.org/rfc/rfc6455#section-7.4.1
        switch (event.code) {
            case 1000:
                return "Normal closure, meaning that the purpose for which the connection was established has been fulfilled.";
            case 1001:
                return "An endpoint is \"going away\", such as a server going down or a browser having navigated away from a page.";
            case 1002:
                return "An endpoint is terminating the connection due to a protocol error";
            case 1003:
                return "An endpoint is terminating the connection because it has received a type of data it cannot accept (e.g., an endpoint that understands only text data MAY send this if it receives a binary message).";
            case 1004:
                return "Reserved. The specific meaning might be defined in the future.";
            case 1005:
                return "No status code was actually present.";
            case 1006:
                return "The connection was closed abnormally, e.g., without sending or receiving a Close control frame";
            case 1007:
                return "An endpoint is terminating the connection because it has received data within a message that was not consistent with the type of the message (e.g., non-UTF-8 [https://www.rfc-editor.org/rfc/rfc3629] data within a text message).";
            case 1008:
                return "An endpoint is terminating the connection because it has received a message that \"violates its policy\". This reason is given either if there is no other sutible reason, or if there is a need to hide specific details about the policy.";
            case 1009:
                return "An endpoint is terminating the connection because it has received a message that is too big for it to process.";
            case 1010:
                return "An endpoint (client) is terminating the connection because it has expected the server to negotiate one or more extension, but the server didn't return them in the response message of the WebSocket handshake. Specifically, the extensions that are needed are: " + event.reason;
            case 1011:
                return "A server is terminating the connection because it encountered an unexpected condition that prevented it from fulfilling the request.";
            case 1015:
                return "The connection was closed due to a failure to perform a TLS handshake (e.g., the server certificate can't be verified).";
            default:
                return  "Unknown reason";
        }
    },
    onOpen: function (initialPayload) {
        let parent = this;
        return function () {
            if (parent.debug) {
                console.log('CakeWebSocket: Connection is now OPEN!');
            }
            parent.socket.send(JSON.stringify({
                initialPayload: initialPayload,
            }));
        }
    },
    onMessage: function () {
        let parent = this;
        return function (msg) {
            let response = JSON.parse(msg.data);
            let controller = response.controller;
            let action = response.action;
            let payload = response.payload;
            let event = new CustomEvent(controller + '.' + action, {detail: {'payload': payload}});
            if (parent.debug) {
                console.log('CakeWebSocket: new message on controller "' + controller + '" and action "' + action + '"', payload);
            }
            document.dispatchEvent(event);
        }
    },
    onError: function () {
        let parent = this;
        return function(event) {
            if (parent.debug) {
                console.error('CakeWebSocket: An error was observed:', event);
            }
        };
    },
    onClose: function () {
        let parent = this;
        return function(event) {
            if (parent.debug) {
                console.log('CakeWebSocket: Connection was closed. Closing reason: ' + parent.closeCodeLabel(event));
            }
        };
    },
};