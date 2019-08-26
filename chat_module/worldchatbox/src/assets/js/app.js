"use strict";
//(function(){

	//window.aaa = 'aaaHú';
	window.io = require('socket.io-client');
	// window.socket  =  io('https://websocketsanbox.worldfone.vn/worldchatbox/');
	window.socket = io.connect('http://115.146.126.84:8006', {
                // path: "/worldchatbox/socket.io/",
                reconnection: true,
                reconnectionDelay: 500,
                reconnectionDelayMax : 3000,
                reconnectionAttempts: Infinity
            });

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


	window.livechat = {
		currentMes: 0,
		setting_noti:true,
		setting_sound:true,
		//surveyshow: false,
		hasUserdata:false,
		setSettingSound: function(e){
			this.setting_sound = e;
			localStorage.setItem('setting_sound', e);
		},
		setSettingNoti: function(e){
			this.setting_noti = e;
			localStorage.setItem('setting_noti', e);
		},
		sethasUserdata: function(e){
			this.hasUserdata = e;
		},
		getId: function(e){
			return getUrlParameter('app_id');
		},
		
		get: function(){
			// socket.emit('getlivechat', {livechat_id:this.getId()});
		},
		validateEmail(email) {
			var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			return re.test(String(email).toLowerCase());
		},
		isEmpty(obj) {
			for(var key in obj) {
				if(obj.hasOwnProperty(key))
					return false;
			}
			return true;
		},
		init: function(){
			if (localStorage.getItem("setting_sound") === null || localStorage.getItem("setting_sound") == 'true' ) {
				this.setSettingSound(true);
			}else{
				this.setSettingSound(false);
			}

			if (localStorage.getItem("setting_noti") === null || localStorage.getItem("setting_noti") == 'true' ) {
				this.setSettingNoti(true);
			}else{
				this.setSettingNoti(false);
			}
		}
	}
	window.livechat.init();
	window.room = {
		room_id: '',
		init: function(e){

		},
		setRoomId: function(e){
			this.room_id = e;
		
		},
		getRoomId: function(){
			return this.room_id;
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
	};
	window.user = {
		properties : {},
		name: '',
		phone: '',
		email: '',
		address: '',
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
			this.properties = e;
		},
		getProperties: function(e){
			return this.properties = e;
		},
		setName: function(e){
			this.name = e;
		},
		setPhone: function(e){
			this.phone = e;
		},
		setEmail: function(e){
			this.email = e;
		},
		setAddress: function(e){
			this.address = e;
		},
		setMeta: function(e){
		},
		setLocale: function(e){
		},
		create: function(e){
		},

	};
	window.App = {
		init: function(){

			// alert(livechat.getId());
			// livechat.get();
			
		}
	};
// 	App.init();
// })();

// alert(livechat.getId());

function receiveMessage(event){
	// console.log('received'+ event.data,event);
    //if (event.origin !== "https://omnisalessanbox.worldfone.vn")
    //    return;
    if (typeof(event.data['user_data']) !== 'undefined') {
    	// console.log(event.data['user_data']);
    	window.user.setName(event.data['user_data'].name);
    	window.user.setPhone(event.data['user_data'].phone);
    	window.user.setEmail(event.data['user_data'].email);
    	window.user.setAddress(event.data['user_data'].address);
    	var properties = [];
    	for(var i in event.data['user_data'].properties){
    		properties.push({
    			name: i,
    			value: event.data['user_data'].properties[i]
    		});
    		// console.log(i);
    		// console.log(event.data['user_data'].properties[i]);
    	};
    	//console.log(event.data['user_data'].properties);
    	//console.log(properties);
    	window.user.setProperties(properties);
    	if (event.data['user_data'].name !='' || event.data['user_data'].phone !='' || event.data['user_data'].email !='' || event.data['user_data'].address !='' || !window.livechat.isEmpty(properties)) {
    		window.livechat.sethasUserdata(true);
    	}
    	// console.log(window.livechat.hasUserdata);

    	// console.log(!window.livechat.isEmpty(properties));

    	// console.log('received'+ event.data,event);
    }
}
window.addEventListener("message", receiveMessage, false);

// console.log(window.location.protocol+'//'+window.location.hostname);
// console.log(document.URL);