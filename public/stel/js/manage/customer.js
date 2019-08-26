var layoutViewModel = kendo.observable({
	breadcrumb: "",
	activeArray: [],
	buttonSelect: "#top-row .btn-group",
	init: function() {
		var hash = (window.location.hash || "#/").toString(),
			$currentTarget = $(this.buttonSelect).find(`button[href='${hash}']`),
			index = $(this.buttonSelect).find("button").index($currentTarget);
		this.set("activeArray", new Array($(this.buttonSelect).find("button").length));
		this.set("breadcrumb", $currentTarget.text());
		this.setActive(index);
	},
	goTo: function(e) {
		var $currentTarget = $(e.currentTarget);
		var index = $(this.buttonSelect).find("button").index($currentTarget);
		var nav = $currentTarget.attr("href");
		if(nav) {
			router.navigate(nav);

			this.set("breadcrumb", $currentTarget.text());
			if(index > -1) this.setActive(index);
		}
	},
	setActive: function(index) {
		for (var i = 0; i < this.activeArray.length; i++) {
			if(i == index)
				this.set(`activeArray[${i}]`, true);
			else this.set(`activeArray[${i}]`, false);
		}
	},
	hasDetail: false,
	customerDetailList: [],
	addCustomerDetail: function(customer) {
		var link = ENV.currentUri + '/#/detail/' + customer.id;
		var check = this.customerDetailList.find(obj => obj.id == customer.id);
		if(!check) {
			this.customerDetailList.push({id: customer.id, url: link, name: customer.name, active: true})
		}
		for (var i = 0; i < this.customerDetailList.length; i++) {
			this.set(`customerDetailList[${i}].active`, (this.customerDetailList[i].id == customer.id) ? true : false);
		}
		this.set("hasDetail", true);
	}
})

// views, layouts
var layout = new kendo.Layout(`layout`, {model: layoutViewModel, wrap: false , init: layoutViewModel.init.bind(layoutViewModel)});

// routing
var router = new kendo.Router({routeMissing: function(e) { router.navigate("/") }});

router.bind("init", function() {
    layout.render($("#page-content"));
});

router.route("/", async function() {
	var HTML = await $.get(`${Config.templateApi}customer/overview`);
	var kendoView = new kendo.View(HTML, { model: {}, template: false, wrap: false });
    layout.showIn("#bottom-row", kendoView);
    var widget = await $.get(`${Config.templateApi}customer/widget`);
    $("#page-widget").html(widget);
});

router.route("/detail/:id", async function(id) {
	layoutViewModel.setActive(1);
	var dataItemFull = await $.get(`${ENV.restApi}customer/${id}`);
	if(!dataItemFull) {
		notification.show("Can't find customer", "error");
		return;
	}
	layoutViewModel.addCustomerDetail(dataItemFull);
	layoutViewModel.set("breadcrumb", `${dataItemFull.name}`);
	var HTML = await $.get(`${Config.templateApi}customer/detail?id=${id}`);
	var model = {
	}
	var kendoView = new kendo.View(HTML, { model: model, template: false, wrap: false });
    layout.showIn("#bottom-row", kendoView);
});

router.route("/import", async function(id) {
	var HTML = await $.get(`${Config.templateApi}customer/import`);
	var model = {
		file: {},
		columns: [{
            field: "name",
            title: "Name",
            width: 140
        },{
            field: "phone",
            title: "Main phone",
            width: 100
        },{
            field: "email",
            title: "Email",
        },{
            field: "address",
            title: "Address",
        },{
            field: "description",
            title: "Description",
        }],
		visibleData: false,
		data: new kendo.data.DataSource(),
		originalDataColumns: [],
		dataColumns: [],
		moveDataColumns: function(oldIndex, newIndex) {
			var columns = this.dataColumns.slice(0);
			var column = columns[oldIndex];
			if(newIndex > oldIndex) {
				columns.splice(newIndex + 1, 0, column);
				columns.splice(oldIndex, 1);
			} else {
				columns.splice(oldIndex, 1);
				columns.splice(newIndex, 0, column);
			}
			columns.map((ele, idx) => {
				ele.index = idx; ele.title = this.originalDataColumns[idx].title + ` (${ele.field})`;
			});
			this.set("dataColumns", columns);
			return columns;
		},
		import: function() {
			swal({
                title: "Are you sure?",
                text: `Import this data.`,
                icon: "warning",
                buttons: true,
                dangerMode: false,
            })
            .then((sure) => {
            	if(sure) {
					var fieldArray = this.columns.slice(0).map(ele => ele.field),
						dataColumns = this.get("dataColumns"),
						data = this.data.data().toJSON(),
						file = {};
					["lastModified", "name", "size", "type"].forEach(field => {
						if(this.file[field])
							file[field] = this.file[field];
					});
					data.map((doc, idx) => {
						for (var index = 0; index < dataColumns.length; index++) {
							if(dataColumns[index].field) {
								doc[fieldArray[index]] = doc[dataColumns[index].field];
								delete doc[dataColumns[index].field];
							}
						}
						doc.imported_file_info = file;
					})
					this.save(data);
				}
			})
		},
		save: function(data) {
			$.ajax({
				url: `${ENV.restApi}customer`,
				type: "PATCH",
				contentType: "application/json; charset=utf-8",
				data: kendo.stringify(data),
				success: function() {
					syncDataSource();
					router.navigate(`/`);
				},
				error: errorDataSource
			})
		}
	};
	var kendoView = new kendo.View(HTML, {model: kendo.observable(model)});
    layout.showIn("#bottom-row", kendoView);
});

router.start();

async function addForm() {
	var formHtml = await $.ajax({
	    url: Config.templateApi + Config.collection + "/form",
	    error: errorDataSource
	});
	var model = Object.assign(Config.observable, {
		item: {},
		save: function() {
			Table.dataSource.add(this.item);
			Table.dataSource.sync().then(() => {Table.dataSource.read()});
		},
		updateEmailInfo: function() {
			
		}
	});
	kendo.destroy($("#right-form"));
	$("#right-form").empty();
	var kendoView = new kendo.View(formHtml, { wrap: false, model: model, evalTemplate: false });
	kendoView.render($("#right-form"));
}

document.onkeydown = function(evt) {
    evt = evt || window.event;
    if (evt.keyCode == 27) {
    	router.navigate(`/`);
    	layoutViewModel.init();
    }
};