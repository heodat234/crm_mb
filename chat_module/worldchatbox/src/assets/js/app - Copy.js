//(function(){
	var io = require('socket.io-client');
	var socket  =  io('localhost:3001');
	var getUrlParameter = function getUrlParameter(sParam) {
		var sPageURL = decodeURIComponent(window.location.search.substring(1)),
		sURLVariables = sPageURL.split('&'),
		sParameterName,
		i;
		for (i = 0; i < sURLVariables.length; i++) {
			sParameterName = sURLVariables[i].split('=');

			if (sParameterName[0] === sParam) {
				return sParameterName[1] === undefined ? true : sParameterName[1];
			}
		}
	};

	var livechat = {
		getId: function(e){
			return getUrlParameter('app_id');
		},
		get: function(){
			// socket.emit('getlivechat', {livechat_id:this.getId()});
		}
	}
	var room = {
		init: function(e){

		},
		open: function(e){

		},
		close: function(e){

		},
		show: function(e){
			// emit('open_channel');
		},
		hide: function(e){

		},
		close: function(e){

		},
	};
	var user = {
		get: function(e){
			// alert('ngon1');

		},
		isExists: function(e){
			// alert('ngon1');
		},
		update: function(e){
			// alert('ngon1');
		},
		setProperties: function(e){
			// alert('ngon1');
		},
		setName: function(e){
			// alert('ngon1');
		},
		setPhone: function(e){
			// alert('ngon1');
		},
		setMeta: function(e){
			// alert('ngon1');
		},
		setLocale: function(e){
			// alert('ngon1');
		},
		create: function(e){
			// alert('ngon1');
		},

	};
	var App = {
		init: function(){
			// alert(livechat.getId());
			// livechat.get();
			
		}
	};
// 	App.init();
// })();

// alert(livechat.getId());