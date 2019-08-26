var base_url = ENV.baseUrl;
var socket = io.connect(ENV.OMNI_WEBHOOK_SOCKET_URL, {
	reconnection: true,
	reconnectionDelay: 500,
	reconnectionDelayMax : 5000,
	reconnectionAttempts: Infinity
        /*rememberTransport: false,
        transports: ['WebSocket', 'Flash Socket', 'AJAX long-polling']*/
});
socket.emit('load', {user: ENV.extension, parent_user: 'global_channel'});

