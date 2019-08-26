'use strict';
console.log('yeahhuhu');
// var chatController = require('../controllers/chat');

module.exports = [
	/*{
		method: 'GET', //View a Ticket
		path:'/api/v2/chat/{id}', 
		config: {auth: 'jwt'},
		handler: (request, reply) => {
			var id = request.params.id;
			var result = chatController.viewTicket(id);
			reply(result);
		}
	},*/
	{
		method: 'POST',
		path:'/api/v2/chat', 
		// config: {auth: 'jwt'},
		handler: (request, reply) => {
			// res.send('hello world')
			// var result = chatController.createMessager( request );
			// reply( g);
			// console.log(request);
			// return request;
			// console.log('okhahaha');
			// io.sockets.emit('update'); // how?
		}
	}/*,
	{
		method: 'GET',
		path:'/api/v2/chat', 
		config: {auth: 'jwt'},
		handler: (request, reply) => {
			var result = chatController.listAllchat(request);
			reply(result);
		}
	},
	{
		method: 'GET',
		path:'/api/v2/search/chat', 
		config: {auth: false},
		handler: (request, reply)  => {
			var result = chatController.filterchat(request);
			reply(result);
		}
	},
	{
		method: 'PUT',
		path:'/api/v2/chat/{id}', 
		config: {auth: 'jwt'},
		handler: (request, reply)  => {
			var result = chatController.updateTicket(request);
			reply(result);
		}
	},
	{
		method: 'DELETE',
		path:'/api/v2/chat/{id}',
		config: {auth: 'jwt'}, 
		handler: (request, reply)  => {
			var result = chatController.deleteTicket(request);
			reply(result);
		}
	},
	{
		method: 'GET',
		path:'/api/v2/chat/{id}/conversations',
		config: {auth: 'jwt'}, 
		handler: (request, reply)  => {
			var result = chatController.conversations(request.params.id);
			reply(result);
		}
	}*/		
];