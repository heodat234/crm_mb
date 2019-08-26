'use strict';
console.log('yeahhuhu12');

module.exports = function (io) { 
    return [
    	/*Chiều từ agent đi social*/
	    {
	    	method: 'POST',
	    	path:'/localapiout/v1/sendchat/text',
			// config: {auth: 'jwt'},
			handler: (request, reply) => {
				console.log('/localapiout/v1/sendchat/text');
				console.log(request.payload);
				var data = request.payload;
				io.sockets.in(data.channel).emit('receiveMes', data);
				return reply.response({error: 0, message: 'success'}).code(200);
			}
		},
		{
	    	method: 'POST',
	    	path:'/localapiout/v1/sendchat/image',
			// config: {auth: 'jwt'},
			handler: (request, reply) => {
				// console.log(request.payload);
				console.log(request.payload);
				var data = request.payload;
				io.sockets.in(data.channel).emit('receiveMesImg', data);
				return reply.response({error: 0, message: 'success'}).code(200);
			}
		},
		{
	    	method: 'POST',
	    	path:'/localapiout/v1/sendchat/file',
			// config: {auth: 'jwt'},
			handler: (request, reply) => {
				// console.log(request.payload);
				var data = request.payload;
				io.sockets.in(data.channel).emit('receiveMes', data);
				return reply.response({error: 0, message: 'success'}).code(200);
			}
		},


		/*Chiều từ social về omnisales*/
		{
	    	method: 'POST',
	    	path:'/localapiin/v1/sendchat/text',
			// config: {auth: 'jwt'},
			handler: (request, reply) => {
				console.log('/localapiin/v1/sendchat/text');
				console.log(request.payload);
				var data = request.payload;
				/*if (data.source=="facebook") {
					data.details = data.details;
				}*/
				io.sockets.in(data.channel).emit('receiveMes', data);
				return reply.response({error: 0, message: 'success'}).code(200);
			}
		},

		{
	    	method: 'POST',
	    	path:'/localapiin/v1/sendchat/link',
			// config: {auth: 'jwt'},
			handler: (request, reply) => {
				// console.log(request.payload);
				var data = request.payload;
				io.sockets.in(data.channel).emit('receiveMes', data);
				return reply.response({error: 0, message: 'success'}).code(200);
			}
		},



		{
	    	method: 'POST',
	    	path:'/localapiin/v1/sendchat/image',
			// config: {auth: 'jwt'},
			handler: (request, reply) => {
				// console.log(request.payload);
				var data = request.payload;
				io.sockets.in(data.channel).emit('receiveMesImg', data);
				return reply.response({error: 0, message: 'success'}).code(200);
			}
		},

		{
	    	method: 'POST',
	    	path:'/localapiin/v1/sendchat/file',
			// config: {auth: 'jwt'},
			handler: (request, reply) => {
				// console.log(request.payload);
				var data = request.payload;
				io.sockets.in(data.channel).emit('receiveMesImg', data);
				return reply.response({error: 0, message: 'success'}).code(200);
			}
		},


		{
	    	method: 'POST',
	    	path:'/localapiin/v1/msg_delivered',
			// config: {auth: 'jwt'},
			handler: (request, reply) => {
				console.log('msg_delivered');
				var data = request.payload;
				console.log(data);
				io.sockets.in(data.channel).emit('msg_delivered', data);
				return reply.response({error: 0, message: 'success'}).code(200);
			}
		},

		{
	    	method: 'POST',
	    	path:'/localapiin/v1/msg_error',
			// config: {auth: 'jwt'},
			handler: (request, reply) => {
				console.log('msg_error');				
				var data = request.payload;
				console.log(data);
				io.sockets.in(data.channel).emit('msg_error', data);
				return reply.response({error: 0, message: 'success'}).code(200);
			}
		},
    ];
};