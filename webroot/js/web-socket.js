class CakePHPWebSocketPayload {

    #attempts = 0;
    #plugin
    #controller;
    #action;
    #payload;

    constructor(plugin, controller, action, payload) {
        this.#plugin = plugin;
        this.#controller = controller;
        this.#action = action;
        this.#payload = payload;
    }

    get plugin() {
        return this.#plugin;
    }

    get controller() {
        return this.#controller;
    }

    get action() {
        return this.#action;
    }

    get payload() {
        return this.#payload;
    }

    /**
     *
     * @returns {number}
     */
    get attempts() {
        return this.#attempts;
    }

    /**
     *
     * @param {WebSocket} socket
     * @param {boolean} debug
     * @returns {boolean}
     */
    send(socket, debug) {
        this.#attempts++;
        if (socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({
                'plugin': this.#plugin,
                'controller': this.#controller,
                'action': this.#action,
                'payload': this.#payload,
            }));
            if (debug) {
                console.log('CakeWebSocket: (Attempt: ' + this.#attempts + ') Message sent to controller "' + this.#controller + '" and action "' + this.#action + '"', this.#payload);
            }
            return true;
        }
        console.error('CakeWebSocket: A attempt to send a message message to controller "' + this.#controller + '" and action "' + this.#action + '" did not send because the socket state is: ' + CakePHPWebSocket.getInstance().readyStateLabel());
        return false;
    }
}

class CakePHPWebSocket {

    #sendList = [];
    #sendInterval = undefined;
    #serverUrl;
    #initializePayload;
    #debug;
    #socket = undefined;
    static #instance = undefined;

    /**
     *
     * @param {string} serverUrl
     * @param {string} initializePayload
     * @param {boolean} debug
     */
    constructor(serverUrl, initializePayload, debug) {
        this.#serverUrl = serverUrl;
        this.#initializePayload = initializePayload;
        this.#debug = debug;
        this.connect();
    }

    /**
     *
     * @param {string} serverUrl
     * @param {string} initializePayload
     * @param {boolean} debug
     */
    static initialize(serverUrl, initializePayload, debug) {
        this.#instance = new CakePHPWebSocket(serverUrl, initializePayload, debug);
    }

    /**
     *
     * @returns {CakePHPWebSocket|void}
     */
    static getInstance() {
        if (this.#instance === undefined) {
            console.error('You must use the CakePHPWebSocket.initialize() method before use this "getInstance()".')
            return;
        }
        return this.#instance;
    }

    connect() {
        if (this.#serverUrl === null || this.#initializePayload === null) {
            console.error('connect method must to be called after initialize()');
            return;
        }
        let parent = this;
        this.#socket = new WebSocket(this.#serverUrl);
        if (this.#debug) {
            console.log('CakeWebSocket: Attempting to connect on "' + this.#serverUrl + '"...');
            setInterval(function () {
                console.log('CakeWebSocket: connection status: ' + parent.readyStateLabel());
            }, 60000);
        }

        this.#socket.onopen = this.#onOpen();
        this.#socket.onmessage = this.#onMessage();
        this.#socket.onerror = this.#onError();
        this.#socket.onclose = this.#onClose();
    }

    /**
     *
     * @param {string} controller
     * @param {string} action
     * @param {function} callable
     */
    addListener(controller, action, callable) {
        if (this.#debug) {
            console.log('CakeWebSocket: A new listener was added to controller "' + controller + '" and "' + action + '"');
        }
        document.addEventListener(controller + '.' + action, function (e) {
            callable(e.detail.payload);
        });
    }

    /**
     *
     * @param {string} controller
     * @param {string} action
     * @param {Object} payload
     * @param {boolean|string} plugin
     */
    sendMessage(controller, action, payload, plugin = false) {
        if (this.#socket === null) {
            console.error('CakeWebSocket is not connected')
            return;
        }
        this.#sendList.push(new CakePHPWebSocketPayload(plugin, controller, action, payload));
    }

    /**
     *
     * @returns {boolean}
     */
    isOpen() {
        if (this.#socket === null) {
            return false;
        }
        return this.readyStateLabel() === 'OPEN';
    }

    /**
     *
     * @returns {(function(): void)|*}
     */
    #onOpen() {
        let parent = this;
        return function () {
            if (parent.#debug) {
                console.log('CakeWebSocket: Connection is now OPEN!');
            }
            parent.#socket.send(JSON.stringify({
                initializePayload: parent.#initializePayload,
            }));

            parent.#sendInterval = setInterval(function () {
                parent.#sendList.forEach(
                    /**
                     *
                     * @param {CakePHPWebSocketPayload} payload
                     * @param {number} index
                     * @param {array} arr
                     */
                    function (payload, index, arr) {
                        if (payload.send(parent.#socket, parent.#debug)) {
                            parent.#sendList.splice(index, 1);
                        } else if (payload.attempts >= 5) {
                            parent.#sendList.splice(index, 1);
                        }
                    }
                );
            }, 100);
        }
    }

    /**
     *
     * @returns {(function(*): void)|*}
     */
    #onMessage() {
        let parent = this;
        return function (msg) {
            let response = JSON.parse(msg.data);
            let controller = response.controller;
            let action = response.action;
            let payload = response.payload;
            let event = new CustomEvent(controller + '.' + action, {detail: {'payload': payload}});
            if (parent.#debug) {
                console.log('CakeWebSocket: new message on controller "' + controller + '" and action "' + action + '"', payload);
            }
            document.dispatchEvent(event);
        }
    }

    /**
     *
     * @returns {(function(*=): void)|*}
     */
    #onError() {
        let parent = this;
        return function (event) {
            if (parent.#debug) {
                console.error('CakeWebSocket: An error was observed:', event);
            }
        };
    }

    /**
     *
     * @returns {(function(*=): void)|*}
     */
    #onClose() {
        let parent = this;
        return function (event) {
            if (parent.#sendInterval !== undefined) {
                clearInterval(parent.#sendInterval);
            }
            if (parent.#debug) {
                console.log('CakeWebSocket: Connection was closed. Closing reason: ' + CakePHPWebSocket.#closeCodeLabel(event));
            }
        };
    }

    /**
     *
     * @returns {string}
     */
    readyStateLabel() {
        if (this.#socket === undefined) {
            return 'CLOSED';
        }
        switch (this.#socket.readyState) {
            case WebSocket.OPEN:
                return 'OPEN';
            case WebSocket.CLOSED:
                return 'CLOSED';
            case WebSocket.CLOSING:
                return 'CLOSING';
            case WebSocket.CONNECTING:
                return 'CONNECTING';
            default:
                return 'OTHER (' + this.#socket.readyState + ')';
        }
    }

    /**
     *
     * @param {Object} event
     * @returns {string}
     */
    static #closeCodeLabel(event) {
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
                return "An endpoint is terminating the connection because it has received a message that \"violates its policy\". This reason is given either if there is no other suitable reason, or if there is a need to hide specific details about the policy.";
            case 1009:
                return "An endpoint is terminating the connection because it has received a message that is too big for it to process.";
            case 1010:
                return "An endpoint (client) is terminating the connection because it has expected the server to negotiate one or more extension, but the server didn't return them in the response message of the WebSocket handshake. Specifically, the extensions that are needed are: " + event.reason;
            case 1011:
                return "A server is terminating the connection because it encountered an unexpected condition that prevented it from fulfilling the request.";
            case 1015:
                return "The connection was closed due to a failure to perform a TLS handshake (e.g., the server certificate can't be verified).";
            default:
                return "Unknown reason";
        }
    }
}