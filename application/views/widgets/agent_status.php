<li class="dropdown" id="agent-status-widget" data-toggle="tooltip" data-placement="left" title="@Call status@">
    <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" data-bind="css: {disabled: item.disabled}">
        <i class="gi gi-snowflake fa-spin text-muted" data-bind="visible: item.WAI"></i>
        <i class="gi gi-headset text-success" data-bind="visible: item.AVA" style="display: none"></i>
        <i class="gi gi-ban text-muted" data-bind="visible: item.SUN, click: sunClick" style="display: none"></i>
        <i class="gi gi-airplane text-danger" data-bind="visible: item.UNV" style="display: none"></i>
        <i class="gi gi-briefcase text-warning" data-bind="visible: item.ACW" style="display: none"></i>
        <i class="gi gi-earphone text-primary" data-bind="visible: item.SOC" style="display: none"></i>
        <span class="caret"></span>
        <small data-bind="text: item.substatus"></small>
    </a>
    <ul class="dropdown-menu dropdown-custom dropdown-options">
        <li class="dropdown-header" data-bind="css: {disabled: item.AVA}"><a href="javascript:void(0)" data-bind="click: changeStatus" data-code="1"><i class="gi gi-headset"></i> <span class="label label-success">@Ready@</span><span data-bind="text: item.time, visible: item.AVA"></span></a></li>
        <li data-bind="css: {disabled: item.ACW}"><a href="javascript:void(0)" data-bind="click: changeStatus" data-code="4"><i class="gi gi-briefcase"></i> <span class="label label-warning">@ACW@</span><span data-bind="text: item.time, visible: item.ACW"></span></a></li>
        <!-- <li class="dropdown-header" data-bind="css: {disabled: item.UNV}"><a href="javascript:void(0)" data-bind="click: changeStatus" data-code="3"><i class="gi gi-airplane"></i> <span class="label label-danger">Unvailable</span><span data-bind="text: item.time, visible: item.UNV"></span></a></li> -->
    </ul>
</li>
<script type="text/javascript">
    function subStatusDataSource(code) {
        return $.get(`${ENV.vApi}agentstatuscode/get_by_value/${code}`);
    }
    function agentStatusWidget(e) {
        var agentStatusObservable = kendo.observable({
            item: {WAI: true},
            sunClick: function() {
                swal({
                    title: "@Softphone Unvailable@.",
                    text: `@Availabe softphone to use@.`,
                    icon: "warning"
                });
            },
            changeStatus: function(e) {
                if(!$(e.currentTarget).closest("li").hasClass("disabled"))
                {
                    this.changeStatusAsync(e);
                }
            }, 
            changeStatusAsync: async function(e) {
            	var code = e.currentTarget.dataset.code;
                var status = await subStatusDataSource(code);
                if(status.sub && status.sub.length) {
                    var subStatusOption = status.sub;
                    var buttons = {cancel: true};
                    
                    for (var i = 0; i < subStatusOption.length; i++) {
                        buttons[i] = {text: subStatusOption[i]};
                    }
                    var type = swal({
                        title: "@Choose your reason@.",
                        text: `@Why you change to@ ${status.text}?`,
                        icon: "info",
                        buttons: buttons
                    }).then(index => {
                        if(index !== null && index !== false) {
                            var substatus = subStatusOption[index];
                            changeStatus(code, substatus);
                        }
                    })
                } else changeStatus(code);
            }
        })
        kendo.bind($("#agent-status-widget"), agentStatusObservable);
        var data = JSON.parse(e.data);
        if(!data) {
            // Loi khi chua co bang agent status code
            console.log("No agent status code");
            $.get(ENV.vApi + "agentstatuscode/init");
            return;
        }
        if(data.SUN || data.SOC) data.disabled = true;
        var countTime = data.lastupdate - data.starttime;
        var d = new Date();
        d.setHours(0,0,0,0);
        data.time =  " - " + kendo.toString(new Date(d.getTime() + (countTime) * 1000), "H:mm:ss");
        agentStatusObservable.set("item", data);
    }
</script>