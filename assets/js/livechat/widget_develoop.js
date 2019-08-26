window.oscWidget = {
	id: '',
	user:{
		properties : {},
		name: '',
		phone: '',
		email: '',
		address: '',
		setProperties: function(e){
			this.properties = e;
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
	},
	hasClass(el, className){
		if (el.classList)
			return el.classList.contains(className);
		return !!el.className.match(new RegExp('(\\s|^)' + className + '(\\s|$)'));
	},
	addClass(el, className){
		if (el.classList)
			el.classList.add(className)
		else if (!hasClass(el, className))
			el.className += " " + className;
	},
	removeClass(el, className){
		if (el.classList)
			el.classList.remove(className)
		else if (hasClass(el, className))
		{
			var reg = new RegExp('(\\s|^)' + className + '(\\s|$)');
			el.className = el.className.replace(reg, ' ');
		}
	},
	setCss: function(){
		var link = document.createElement('link');
        link.type = 'text/css';
        link.rel = 'stylesheet';
        link.href  = 'http://192.168.16.105/assets/js/livechat/widget.css';
		document.body.appendChild(link);
	},
	reloadIframe: function(){
		var iframe = document.getElementById('osc_widget');
		iframe.src = iframe.src;
	},
	setIframe: function(id){
		var iframe = document.createElement('div');
		// addClass
		iframe.className='osc-widget-normal';
		iframe.id='osc_frame';
		iframe.style.zIndex ='9999999999';
		iframe.innerHTML  = '<iframe id="osc_widget" width="100%" height="100%" frameborder="0" src="http://192.168.16.105:8082/?app_id='+id+'"></iframe>';
		document.body.appendChild(iframe);
	},
	setData: function(){
		//top.postMessage({testdata:'testdata thành công'}, 'https://omnisalessanbox.worldfone.vn/worldchatbox/?app_id=5c10714deb721dbf50ca3c82');
		var iframeWin = document.getElementById("osc_widget").contentWindow;
		iframeWin.postMessage({user_data:{name:window.oscWidget.user.name, phone:window.oscWidget.user.phone, email:window.oscWidget.user.email, address:window.oscWidget.user.address, properties:window.oscWidget.user.properties, }}, "http://192.168.16.105:8082");//?app_id=5c10714deb721dbf50ca3c82
	},
	init: function(data){
		this.id = data['token'];
		this.setCss(data['token']);
		this.setIframe(data['token']);			
	}
};


function receiveMessage(event){
	// window.location.protocol+'//'+window.location.hostname
    //if (event.origin !== "https://omnisalessanbox.worldfone.vn")
    //    return;
    if (typeof(event.data['st_popup_status']) !== 'undefined') {
    	var st_popup_status = event.data["st_popup_status"];
    	if (st_popup_status==0) {
    		var iframe_id = document.getElementById("osc_frame");
    		oscWidget.removeClass(iframe_id,'h-open-container');
    		oscWidget.removeClass(iframe_id,'fc-open');

    	}else if(st_popup_status==1){
    		var iframe_id = document.getElementById("osc_frame");
    		oscWidget.addClass(iframe_id,'h-open-container');
    		oscWidget.addClass(iframe_id,'fc-open');
    		
    	}
    	// console.log('received'+ event.data,event);
    }
    if (typeof(event.data['ready']) !== 'undefined') {
    	if(event.data['ready']==1){
    		window.oscWidget.setData();		
    	}
    }
    if (typeof(event.data['reload_iframe']) !== 'undefined') {
    	window.oscWidget.reloadIframe();
    }
    //console.log('received'+ event.data,event);
}
 
window.addEventListener("message", receiveMessage, false);