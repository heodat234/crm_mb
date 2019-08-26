<!-- Table Styles Header -->
<ul class="breadcrumb breadcrumb-top">
    <li>Admin</li>
    <li>Database</li>
</ul>
<!-- END Table Styles Header -->
<div class="container-fluid">
	<div class="row" style="padding-top: 20px">
        <div class="col-md-4">
            <!-- Web Server Block -->
            <div class="block full">
                <!-- Web Server Title -->
                <div class="block-title">
                    <div class="block-options pull-right">
                    	<a role="button" class="btn btn-sm btn-alt btn-info btn-waring" href="javascript:void(0)" data-bind="click: backupDatabase"><i class="gi gi-coins"></i> <b>Dump</b></a>
                    </div>
                    <h2><strong>Database</strong></h2>
                </div>
                <!-- END Web Server Title -->

                <div>
                	<a role="button" class="btn btn-sm btn-alt btn-success btn-database" href="javascript:void(0)" data-name="worldfone4xs" data-bind="click: selectDatabase"><b>worldfone4xs</b></a>
                	<a role="button" class="btn btn-sm btn-alt btn-success btn-database" href="javascript:void(0)" data-name="_worldfone4xs" data-bind="click: selectDatabase"><b>_worldfone4xs</b></a>
                </div>
                <div style="margin-top: 10px">
                	<label class="checkbox-inline">
		    			<input type="checkbox" autocomplete="off" data-bind="checked: showFile, events: {change: showFileChange}">
	                	<span>Show file</span>
	                </label>
	                <label class="checkbox-inline">
		    			<input type="checkbox" autocomplete="off" data-bind="checked: visibleData">
	                	<span>Show data</span>
	                </label>
                </div>
            </div>
            <!-- END Web Server Block -->
        </div>

        <div class="col-md-8">
            <!-- Web Server Block -->
            <div class="block full">
                <!-- Web Server Title -->
                <div class="block-title">
                    <div class="block-options pull-right">
                    	<a role="button" class="btn btn-sm btn-alt btn-warning" href="javascript:void(0)" data-bind="click: restoreCollection, visible: item.desCollection"><i class="gi gi-coins"></i> <b>Restore</b></a>
                    </div>
                    <h2><strong>Collection</strong></h2>
                </div>
                <!-- END Web Server Title -->

                <div>
 					<div data-template="collection-template" data-bind="source: collections"></div>
                </div>

                <div style="margin-top: 20px" data-bind="visible: item.desCollection">
                	<label>Destination collection</label>
                	<input class="k-textbox" data-bind="value: item.desCollection"/>
                	<label class="checkbox-inline">
		    			<input type="checkbox" autocomplete="off" data-bind="checked: item.drop">
	                	<span>Drop old collection</span>
	                </label>
                </div>
            </div>
            <!-- END Web Server Block -->
        </div>
    </div>

    <div class="row" style="padding-top: 20px" data-bind="visible: visibleData">
        <div class="col-md-12">
            <!-- Web Server Block -->
            <div class="block full">
                <!-- Web Server Title -->
                <div class="block-title">
                    <h2><strong>Data</strong></h2>
                </div>
                <!-- END Web Server Title -->

                <div>
                	<div id="grid"></div>
                </div>

            </div>
            <!-- END Web Server Block -->
        </div>
    </div>
</div>
<style type="text/css">
	a.btn-database.selected, a.btn-collection.selected, a.btn-collection:hover {
		background-color: #7db831;
		border-color: #7db831;
		color: #ffffff;
	}
</style>
<script id="collection-template" type="text/x-kendo-template">
	<a class="label label-default btn-collection" href="javascript:void(0)" data-bind="text: name, click: selectCollection, css: {selected: selected}"></a>
</script>
<script type="text/javascript">
var Config = {
    crudApi: `${ENV.reportApi}database/`,
    templateApi: `${ENV.templateApi}`,
    database: "",
    collection: "",
    observable: {
    },
    model: {
        id: "id"
    },
    parse: function(res) {
    	res.data.map(doc => {
    		if(doc.data) doc.field_data = doc.data;
    		delete doc.data;
    	})
    	return res;
    },
    columns: [],
    filterable: KENDO.filterable,
    scrollable: true
}; 
var Table = function() {
    return {
        dataSource: {},
        columns: Config.columns,
        init: function() {
            var dataSource = this.dataSource = new kendo.data.DataSource({
                serverFiltering: true,
                serverPaging: true,
                serverSorting: true,
                serverGrouping: false,
                pageSize: 10,
                batch: false,
                schema: {
                    data: "data",
                    total: "total",
                    groups: "groups",
                    model: Config.model,
                    parse: Config.parse ? Config.parse : res => res
                },
                transport: {
                    read: {
                        url: Config.crudApi + "data/" + Config.database + "/" + Config.collection
                    },
                    parameterMap: parameterMap
                },
                sync: syncDataSource,
                error: errorDataSource
            });

            var grid = this.grid = $("#grid").kendoGrid({
                dataSource: dataSource,
                excel: {allPages: true},
                excelExport: function(e) {
                  var sheet = e.workbook.sheets[0];

                  for (var rowIndex = 1; rowIndex < sheet.rows.length; rowIndex++) {
                    var row = sheet.rows[rowIndex];
                    for (var cellIndex = 0; cellIndex < row.cells.length; cellIndex ++) {
                        if(row.cells[cellIndex].value instanceof Date) {
                            row.cells[cellIndex].format = "dd-MM-yy hh:mm:ss"
                        }
                    }
                  }
                },
                resizable: true,
                pageable: {
                    refresh: true,
                    pageSizes: true,
                    input: true,
                    messages: KENDO.pageableMessages ? KENDO.pageableMessages : {}
                },
                sortable: true,
                scrollable: Boolean(Config.scrollable),
                columns: this.columns,
                filterable: Config.filterable ? Config.filterable : true,
                editable: false,
                detailTemplate: kendo.template($("#detail-template").html()),
                detailInit:  function(e) {
                    var container = $(e.detailCell).find(".jsoneditor"); 
                    var options = {
                        mode: 'code',
                        modes: ['tree','code']
                    };
                    var jsonEditor = new JSONEditor(container[0], options);
                    jsonEditor.set(e.data);
                },
                noRecords: {
                    template: `<h2 class='text-danger'>${KENDO.noRecords}</h2>`
                }
            }).data("kendoGrid");

            grid.selectedKeyNames = function() {
                var items = this.select(),
                    that = this,
                    checkedIds = [];
                $.each(items, function(){
                    if(that.dataItem(this))
                        checkedIds.push(that.dataItem(this).uid);
                })
                return checkedIds;
            }
        }
    }
}();
</script>
<script type="text/javascript">

	window.onload = async function() {
		kendo.bind($("#page-content"), kendo.observable({
			item: {},
			showFile: true,
			showFileChange: function(e) {
				var showFile = e.currentTarget.checked;
				var dbname = this.get("dbname");
				if(dbname)
					this.collections.read({db: dbname, file: Number(showFile)});
			},
			collections: new kendo.data.DataSource({
				transport: {
					read: ENV.reportApi + "database/collections",
				},
				schema: {
					data: "data",
					total: "total"
				}
			}),
			selectDatabase: function(e) {
				$(".btn-database").removeClass("selected");
				$(e.currentTarget).addClass("selected");
				var dbname = $(e.currentTarget).data("name");
				var showFile = this.get("showFile");
				this.set("dbname", dbname);
				this.collections.read({db: dbname, file: Number(showFile)});
			},
			backupDatabase: function(e) {
				var dbname = this.get('dbname');
				if(dbname) {
					swal({
				        title: `Are you sure?`,
				        text: `Backup database ${dbname}`,
				        icon: "warning",
				        buttons: true,
				        dangerMode: false,
				    })
				    .then((sure) => {
				        if (sure) {
				            $.ajax({
						 		url: ENV.reportApi + "database/mongodump/" + dbname,
						 		success: (res) => {
						 			if(res.status)
						 				notification.show("Success", "success");
						 			else notification.show("Error", "error");
						 		}
						 	})
				        }
				    });
				} else {
					notification.show("Please select db");
				}
			},
			selectCollection: function(e) {
				$currentTarget = $(e.currentTarget);
				var collectionName = $currentTarget.text();
				var collectionData = this.collections.data().toJSON();
				this.set("item.srcCollection", collectionName);
				this.set("item.desCollection", collectionName);
				collectionData.map(doc => {
					if(doc.name == collectionName) {
						doc.selected = true;
					} else doc.selected = false;
				})
				this.collections.data(collectionData);
				detailData(this.get("dbname"), collectionName);
			},
			restoreCollection: function(e) {
				var dbname = this.get('dbname');
				var item = this.get('item').toJSON();
				swal({
			        title: `Are you sure?`,
			        text: `Restore collection ${item.desCollection} from ${item.srcCollection} ${item.drop ? "with option drop" : ""}`,
			        icon: "warning",
			        buttons: true,
			        dangerMode: false,
			    })
			    .then((sure) => {
			        if (sure) {
			            $.ajax({
					 		url: ENV.reportApi + "database/mongorestore_collection/" + dbname,
					 		data: item,
					 		success: (res) => {
					 			if(res.status) {
					 				notification.show(res.message, "success");
					 				this.collections.read({db: dbname});
					 			} else notification.show("Error", "error");
					 		}
					 	})
			        }
			    });
			}
		}));

		
	}

	function detailData(database, collection) {
		if(Table.grid) {
			Table.grid.destroy();
			Table.grid = false;
			$("#grid").empty();
		}
		var collectionFields = new kendo.data.DataSource({
			serverFiltering: true,
			serverSorting: true,
            serverPaging: true,
            pageSize: 1,
			transport: {
				read: `${Config.crudApi}data/${database}/${collection}`,
				parameterMap: parameterMap
			},
			schema: {
				data: "data",
			}
		})
		collectionFields.read().then(function(){
			var data = collectionFields.data().toJSON();
            if(data[0]) {
                var columns = [];
                for(var prop in data[0]) {
                    columns.push({field: prop, width: 140})
                }
    			Config.database = database;
    			Config.collection = collection;
    			Table.columns = columns;
    			Table.init();
            }
		})
	}
</script>

<script type="text/x-kendo-template" id="detail-template">
    <div class="jsoneditor" style="width: 100%; height: 400px;"></div>
</script>