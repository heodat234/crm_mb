<!-- Page content -->
<div id="page-content">
    <!-- Table Styles Header -->
    <ul class="breadcrumb breadcrumb-top">
        <li>Admin</li>
        <li>Configuration</li>
        <li class="pull-right none-breakcrumb">
        	<a role="button" href="javascript:void(0)" class="btn btn-sm" data-bind="click: clearCache"><b>Clear cache</b></a>
            <a role="button" href="javascript:void(0)" class="btn btn-sm" data-bind="click: save"><b>Save</b></a>
        </li>
    </ul>
    <!-- END Table Styles Header -->
	<div class="container-fluid">
		<div class="row" style="margin: 0 10px">
			<h4 class="fieldset-legend" style="margin: 0 0 20px"><span style="font-weight: 500; line-height: 1">FOR SYSTEM</span></h4>
		</div>
		<div class="row" style="margin: 10px 30px 0 60px">
			<form class="form-horizontal">
				<div class="col-md-6">
				    <div class="form-group">
				        <label class="control-label col-sm-3">Version</label>
				        <div class="col-sm-3">
				            <input class="k-textbox" style="width: 100%" data-bind="value: item.wff_version">
				        </div>
				        <label class="control-label col-sm-3">Environment</label>
				        <div class="col-sm-3">
				        	<input data-role="dropdownlist"               
		                    data-bind="value: item.wff_env, source: envOption" style="width: 100%">
				        </div>
				    </div>
				    <div class="form-group">
				        <label class="control-label col-sm-3">Unique login</label>
				        <div class="col-sm-3">
				        	<label class="switch switch-primary">
						        <input type="checkbox" data-bind="checked: item.wff_unique_login"><span></span>
						    </label>
				        </div>
				        <label class="control-label col-sm-3">Redirect auth</label>
				        <div class="col-sm-3">
				            <label class="switch switch-primary">
						        <input type="checkbox" data-bind="checked: item.wff_auth_redirect"><span></span>
						    </label>
				        </div>
				    </div>
				</div>
				<div class="col-md-6">
				    <div class="form-group">
				        <label class="control-label col-sm-3">Time cache</label>
				        <div class="col-sm-3">
				            <input data-role="numerictextbox" style="width: 100%" data-bind="value: item.wff_time_cache">
				        </div>
				        <label class="control-label col-sm-3">Use worker</label>
				        <div class="col-sm-3">
				        	<label class="switch switch-primary">
						        <input type="checkbox" data-bind="checked: item.use_worker"><span></span>
						    </label>
				        </div>
				    </div>
				    <div class="form-group">
				        <label class="control-label col-sm-3">Loader layer</label>
				        <div class="col-sm-3">
				        	<label class="switch switch-primary">
						        <input type="checkbox" data-bind="checked: item.loader_layer"><span></span>
						    </label>
				        </div>
				        <label class="control-label col-sm-3">Record activity</label>
				        <div class="col-sm-3">
				        	<label class="switch switch-primary">
						        <input type="checkbox" data-bind="checked: item.record_activity"><span></span>
						    </label>
				        </div>
				    </div>
				</div>
			</form>
		</div>
		<div class="row hidden" style="margin: 0 10px">
			<h4 class="fieldset-legend" style="margin: 0 0 20px"><span style="font-weight: 500; line-height: 1">FOR DISPLAY DATA</span></h4>
		</div>
		<div class="row hidden" style="margin: 10px 30px 0 60px">
			<form class="form-horizontal">
				<div class="col-md-6">
				    <div class="form-group">
				        <label class="control-label col-sm-3">Show customer</label>
				        <div class="col-sm-9">
				        	<input data-role="dropdownlist"
							data-text-field="text"
							data-value-field="value"
		                    data-value-primitive="true"                 
		                    data-bind="value: item.show_customer, source: showCustomerOption" style="width: 100%">
				        </div>
				    </div>
				</div>
				<div class="col-md-6">
				    <div class="form-group">
				        <label class="control-label col-sm-3">Show CDR</label>
				        <div class="col-sm-9">
				        	<input data-role="dropdownlist"
							data-text-field="text"
							data-value-field="value"
		                    data-value-primitive="true"                 
		                    data-bind="value: item.show_cdr, source: showCDROption" style="width: 100%">
				        </div>
				    </div>
				</div>
			</form>
		</div>
		<div class="row" style="margin: 0 10px">
			<h4 class="fieldset-legend" style="margin: 0 0 20px"><span style="font-weight: 500; line-height: 1">BRAND</span></h4>
		</div>
		<div class="row" style="margin: 10px 30px 0 60px">
			<form class="form-horizontal">
				<div class="col-md-6">
				    <div class="form-group">
				        <label class="control-label col-sm-3">Title</label>
				        <div class="col-sm-9">
				        	<input class="k-textbox" style="width: 100%" data-bind="value: item.brand_title">
				        </div>
				    </div>
				</div>
				<div class="col-md-6">
				    <div class="form-group">
				        <label class="control-label col-sm-4">Avatar <br><a href="javascript:void(0)" data-bind="click: defaultLogo"><small>Default</small></a></label>
				        <div class="col-sm-3" style="padding-top: 5px">
				        	<img src="<?= STEL_PATH ?>img/logo-stel.png" data-bind="invisible: item.brand_logo, click: uploadBrandLogo" class="preview-avatar">
				        	<img data-bind="attr: {src: item.brand_logo}, visible: item.brand_logo, click: uploadBrandLogo" class="preview-avatar">
				        </div>
				        <div class="col-sm-5 hidden" style="padding-top: 5px">
				        	<input name="file" type="file" id="upload-logo"
		                   data-role="upload"
		                   data-multiple="false"
		                   data-async="{ saveUrl: '/api/v1/upload/avatar/logo', autoUpload: true }"
		                   data-bind="events: { success: uploadSuccessLogo }">
				        </div>
				    </div>
				</div>
			</form>
		</div>
		<div class="row" style="margin: 0 10px">
			<h4 class="fieldset-legend" style="margin: 0 0 20px"><span style="font-weight: 500; line-height: 1">PHONE CONFIG</span></h4>
		</div>
		<div class="row" style="margin: 10px 30px 0 60px">
			<form class="form-horizontal">
				<div class="col-md-6">
				    <div class="form-group">
				        <label class="control-label col-sm-3">Softphone</label>
				        <div class="col-sm-9">
				        	<input data-role="dropdownlist"
							data-text-field="text"
							data-value-field="value"
		                    data-value-primitive="true"                 
		                    data-bind="value: item.softphone, source: softphoneOption" style="width: 100%">
				        </div>
				    </div>
				    <div class="form-group">
				        <label class="control-label col-sm-3">IP SIP Server</label>
				        <div class="col-sm-9">
				        	<input class="k-textbox" data-bind="value: item.ip_sip_server" style="width: 100%"/>
				        </div>
				    </div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label class="control-label col-sm-3"></label>
				        <div class="col-sm-9">
				        	<label class="switch switch-primary">
						        <input type="checkbox" data-bind="checked: item.login_logout_ipphone"><span></span>
						    </label>
						    <b>Auto login-logout IP Phone</b>
				        </div>
					</div>
				</div>
			</form>
		</div>
	</div>
	<style>
		.preview-avatar {
			height: 35px;
			border: 2px solid lightgray;
			cursor: pointer;
		}
	</style>
	<script type="text/javascript">
		window.onload = async function() {
			var item = await $.get(ENV.vApi + "config/detail");
			kendo.bind($("#page-content"), kendo.observable({
				item: item,
				envOption: ["DEV","UAT","LIVE"],
				showCustomerOption: [{text: "All", value: "ALL"}, {text: "Only created by agent", value: "ONLY"}],
				showCDROption: [{text: "All", value: "ALL"}, {text: "Only created by agent", value: "ONLY"}],
				softphoneOption: [
					{text: "Other", value: ""},
					{text: "Zoiper", value: "zoiper"},
					{text: "XLite", value: "xlite"},
					{text: "MicroSIP", value: "microsip"}
				],
				defaultLogo: function(e) {
					this.set("item.brand_logo", "");
				},
				uploadBrandLogo: function(e) {
					$("#upload-logo").click();
				},
				uploadSuccessLogo: function(e) {
					notification.show(e.response.message, e.response.status ? "success" : "error");
      				e.sender.clearAllFiles();
      				if(e.response.filepath) {
      					this.set("item.brand_logo", e.response.filepath);
      				}
				},
				save: function() {
					var data = this.item.toJSON();
					$.ajax({
						url: ENV.vApi + "config/update",
						type: "POST",
                    	contentType: "application/json; charset=utf-8",
						data: kendo.stringify(data),
						success: function() {
							syncDataSource();
							location.reload();
						},
						error: errorDataSource
					})
				},
				clearCache: function() {
					$.ajax({
						url: ENV.vApi + "config/clear_cache",
						success: function(response) {
							if(response.status) {
								notification.show(`Clear ${response.count} file cache`, "success");
							} else notification.show(`Clear ${response.count} file cache`, "error");
						},
						error: errorDataSource
					})
				}
			}));
		}
	</script>
</div>
<!-- END Page Content -->