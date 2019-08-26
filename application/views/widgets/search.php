<!-- Search Form -->
<form class="navbar-form-custom">
	<div class="form-group">
    	<input type="text" id="top-search" name="top-search" class="form-control" placeholder="@Search@.." autocomplete="off" style="width: 100%; min-height: 50px">
	</div>
</form>
<!-- END Search Form -->
<script type="text/x-kendo-template" id="topSearchTemplate">
    <div class="pull-left text-name">#if(typeof name != 'undefined'){##: name ##}#</div>
    <div class="pull-right text-muted">#if(typeof typeText != 'undefined'){##: typeText ##}#</div>
</script>
<script type="text/javascript">
	$topSearch = $("#top-search");
	$topSearch.on("mouseover", (e) => {
		if(!$topSearch.hasClass("hasBound")) {
			$topSearch.addClass("hasBound");
			$topSearch.kendoAutoComplete({
				dataSource: {
					serverFiltering: true,
					transport: {
						read: ENV.vApi + "widget/search",
						parameterMap: parameterMap
					},
					schema: {
						data: "data"
					}
				},
				dataTextField: "name",
				dataValueField: "name",
				height: 500,
				minLength: 2,
				enforceMinLength: true,
				filter: "contains",
				template: $("#topSearchTemplate").html(),
				select: function(e) {
					switch (e.dataItem.type) {
						case "Customer":
							location.href = "manage/customer/#/detail/" + e.dataItem.id;
							break;
						case "Page": default:
							location.href = e.dataItem.uri;
							break;
					}
				},
				filtering: function(e) {
					e.preventDefault();
					if(e.filter.value.length >= 2) {
						e.sender.dataSource.filter({filters: [
								{field: "name", operator: "contains", value: e.filter.value, ignoreCase: true},
								{field: "cif", operator: "startswith", value: e.filter.value},
								{field: "phone", operator: "startswith", value: e.filter.value},
							], logic: "or"})
					}
				}
			});
		}
	})
</script>

<style>
	.navbar-form-custom span.k-state-focused {
	    position: absolute;
	    top: 0;
	    left: 0;
	    right: 0;
	    font-size: 18px;
	    padding: 10px 20px;
	}

	#top-search-list .text-name {
	    white-space: nowrap; 
		width: 50%; 
		overflow: hidden;
		text-overflow: ellipsis; 
	}
</style>