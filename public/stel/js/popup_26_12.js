var flagPopup           = false,
    waitingPopup        = false,
    flagCall            = false,
    timeOutCheckComplete= 1000,
    popupObservable     = {},
    currentTitle        = document.title,
    currentFavicon      = $("link[rel='shortcut icon']").attr("href");

async function startPopup(data) {
    window.waitingPopup = true;
    window.popupObservable = new defaultPopup(data);
}

function onRingPopup(data) {
    var ele = document.getElementById("popup-window");
    if(ele) {
        var time = (data.currentTime - data.starttime) * 1000;
        var dialog = $(ele).data("kendoWindow");
        var title = ele.dataset.title;
        title += " (" + kendo.toString(new Date(time), "mm:ss") + " - RINGING)";
        dialog.title(title);
        if(typeof window.intervalTimePopup != 'undefined') {
            clearInterval(window.intervalTimePopup);
        }
        window.intervalTimePopup = setInterval(intervalTimePopupFunction, 1000, time, "RINGING");
    }
    popupObservable.onRingEvent(data);
}

function onCallPopup(data) {
    var ele = document.getElementById("popup-window");
    if(ele) {
        var time = (data.currentTime - data.answertime) * 1000;
        var dialog = $(ele).data("kendoWindow");
        var title = ele.dataset.title;
        title += " (" + kendo.toString(new Date(time), "mm:ss") + " - ONCALL)";
        dialog.title(title);
        if(typeof window.intervalTimePopup != 'undefined') {
            clearInterval(window.intervalTimePopup);
        }
        window.intervalTimePopup = setInterval(intervalTimePopupFunction, 1000, time, "ONCALL");
    }
    popupObservable.onCallEvent(data);
}

function onCompletePopup() {
    if(!window.flagCall) {
        var calluuid = popupObservable.dataCall.calluuid;
        $.ajax({
            url: `${ENV.vApi}popup/get_call_by_id/${calluuid}`,
            success: function(response){
                if(response.status) {
                    if(response.doc.workstatus == "Complete") {
                        if(ele = document.getElementById("popup-window")) {
                            var title = ele.dataset.title,
                                callduration = (Number(response.doc.callduration) != NaN) ? Number(response.doc.callduration) : 0;
                            title += " (" + kendo.toString(new Date(Number(callduration) * 1000), "mm:ss") + " - "+response.doc.disposition+")";
                            $(ele).data("kendoWindow").title(title);
                        }
                        if(response.doc.disposition == "ANSWERED")
                            tabTitle(false,  "/public/stel/img/call-ico-green.png");
                        else tabTitle(false,  "/public/stel/img/call-ico-red.png");
                        if(typeof window.intervalTimePopup != 'undefined') clearInterval(window.intervalTimePopup); 
                        if(typeof window.timeoutPopup != 'undefined') clearTimeout(window.timeoutPopup);
                    }
                }
            }
        });
    }
}

function intervalTimePopupFunction(time, type) {
    var ele = document.getElementById("popup-window");
    if(ele) {
        time += 1000;
        var title = ele.dataset.title;
        title += ` (${kendo.toString(new Date(time), "mm:ss")} - ${type})`;
        $(ele).data("kendoWindow").title(title);
    }
    window.timeoutPopup = setTimeout(onCompletePopup, window.timeOutCheckComplete);
}

function executeCall(e) {
	var data = JSON.parse(e.data);
	if(data) {
        window.flagCall = true;
		switch(data.workstatus) {
			case "Ring":
                if(Math.floor(Date.now() / 1000) % 10 == 0) notification.show("Ringing", "warning");
				if(!flagPopup && sessionStorage.getItem('callPopup') == 'true') startPopup(data);
                if(flagPopup && typeof onRingPopup != "undefined" && !window.waitingPopup) onRingPopup(data);
				break;
			case "On-Call":
				if(Math.floor(Date.now() / 1000) % 10 == 0) notification.show("On call", "success");
                if(!flagPopup && sessionStorage.getItem('callPopup') == 'true') startPopup(data);
				if(flagPopup && typeof onCallPopup != "undefined" && !window.waitingPopup) onCallPopup(data);
				break;
			default:
				break;
		}
	} else window.flagCall = false;
}

function tabTitle(title = "", favicon = "") {
    if(title) {
        notifyTitle();
        notifyTitle(title);
        //document.title = title;
    } else if(typeof title == "string")  {
        notifyTitle();
        //document.title = currentTitle;
    }
    if(favicon) $("link[rel='shortcut icon']").attr("href", favicon);
    else $("link[rel='shortcut icon']").attr("href", currentFavicon);
}

class Popup {

    constructor (dataCall) {
        Object.assign(this, {
            item : {},
            _dataCall : dataCall,
            openPopup: function(e) {
                e.sender.wrapper.css({ top: 20 });
                window.flagPopup = true;
            },
            closePopup: function(){
                this.removePopup();
            },
            removePopup: function() {
                $("#popup-tabstrip").data("kendoTabStrip").destroy();
                $("#popup-window").data("kendoWindow").destroy();
                kendo.unbind($("#popup-contain"));
                $("#popup-contain").empty();
                window.flagPopup = false;
            }
        });
    }

    get dataCall() {
        return this._dataCall;
    }

    start(observable) {
        return Object.assign(this, observable);
    }

    async open() {
        var HTML = await $.ajax({
            url: ENV.baseUrl + "template/popup/" + this._popupType,
            data: this._dataCall
        });
        var kendoView = new kendo.View(HTML, {model: kendo.observable(this), wrap: false, template: false});
        kendoView.render($("#popup-contain"));
        var dialog = $("#popup-window").data("kendoWindow");
        if(dialog) dialog.center().open();
        window.waitingPopup = false;
        return this;
    }

    onRingEvent(data) {
    }

    onCallEvent(data) {
        tabTitle(false,  "/public/stel/img/call-ico-blue.png");
    }
}

class defaultPopup extends Popup {
    constructor(dataCall) {
        super(dataCall);
        Object.assign(this, {
            _fieldId : "customernumber",
            _popupType: "default",
            phone: dataCall.customernumber,
            openPopup: function(e) {
                e.sender.wrapper.css({ top: 50 });
                window.flagPopup = true;
                if(typeof tabTitle != 'undefined') tabTitle(` ★ ${dataCall.direction.toUpperCase()} ★ ${dataCall.customernumber}`,  "/public/stel/img/call-ico.png");
            },
            closePopup: function(){
                this.removePopup();
                if(typeof tabTitle != 'undefined') tabTitle();
            },
            openDetail: function(e){
                $("#detail-iframe").attr("src", this.detailUrl);
            }
        });
        this.init(dataCall[this._fieldId]);
        return this;
    }

    init(fieldId) {
        var oReq = new XMLHttpRequest();
        oReq.addEventListener("load", (response) => {
            if (oReq.readyState === XMLHttpRequest.DONE && oReq.status === 200) {
                var responseObj = JSON.parse(oReq.response);
                if(!responseObj.total) {
                    var detailUrl = `${ENV.baseUrl}manage/customer?omc=1`
                    this.start().open();
                } else if(responseObj.total == 1) {
                    this.item = responseObj.data[0];
                    var detailUrl = `${ENV.baseUrl}manage/customer?omc=1#/detail/${this.item.id}`
                    this.start({detailUrl: detailUrl}).open();
                } else {
                    var buttons = {cancel: true};
                    for (var i = 0; i < responseObj.total; i++) {
                        buttons[i] = {text: responseObj.data[i].name};
                    }
                    var type = swal({
                        title: "Choose one.",
                        text: `Greater than one customer have this number.`,
                        icon: "warning",
                        buttons: buttons
                    }).then(index => {
                        if(index !== null && index !== false) {
                            this.item = responseObj.data[index];
                            var detailUrl = `${ENV.baseUrl}manage/customer?omc=1#/detail/${this.item.id}`
                            this.start({detailUrl: detailUrl}).open();
                        }
                    })
                }
            } else {
                notification.show("Something is wrong", "danger");
            }
        });
        oReq.open("GET", ENV.vApi + `popup/get_customer_by_phone?_=${Date.now()}&phone=${fieldId}`);
        oReq.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        oReq.send();
    }
}