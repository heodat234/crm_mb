<style type="text/css">
	.metrotable > thead > tr > th {
	    padding: .5em 1em 0 0;
	    text-align: left;
	    font-size: 1.5em;
	    font-weight: lighter;
	    color: #bbb;
	    border-bottom: 1px solid #ccc;
	}

	.metrotable > tbody > tr > td {
	    padding: .5em 1em .5em 0;
	    text-align: left;
	    font-size: 1.2em;
	    font-weight: lighter;
	    color: #787878;
	    border-bottom: 1px solid #e1e1e1;
	}
</style>
<script id="row-template" type="text/x-kendo-template">
	<tr>
        <td data-bind="text: name"></td>
        <td data-bind="text: port"></td>
        <td>#= gridBoolean(data.status) #</td>
    </tr>
</script>
<div id="page-content">
	<ul class="breadcrumb breadcrumb-top">
        <li>@Admin@</li>
        <li>@Server@</li>
        <li class="pull-right none-breakcrumb">
            <a role="button" class="btn btn-sm" onclick="checkService()"><i class="fa fa-plug"></i> <b>Check other services</b></a>
        </li>
    </ul>
    <div class="container-fluid">
		<!-- Dashboard 2 Content -->
	    <div class="row">
	    	<div class="col-sm-3">
	    		<div style="margin-top: 10px">
		    		<div class="alert alert-success">
				    	<h4>UPTIME</h4>
				    	<p><b>Logon: </b><span id="logon-users"></span></p>
				        <p class="text-right text-muted"><b>Running for: </b><span id="running-for"></span></p>
				    </div>
				</div>
	    	</div>
	    	<div class="col-sm-3">
	    	<?php foreach ($disks as $disk) { ?>
	    		<div style="margin-top: 10px">
				    <div class="alert alert-info">
				    	<h4>DISK <b><?= $disk["disk_name"] ?></b></h4>
				        <p><b>Total: </b><span><?= getSymbolByQuantity($disk["disk_space"]) ?></span></p>
				        <p class="text-right text-muted"><b>Free: </b><span><?= getSymbolByQuantity($disk["disk_free"]) ?></span></p>
				    </div>
				</div>
	    	<?php } ?>
	    	</div>
	    	<div class="col-sm-6" id="service-status">
	    		<table class="metrotable" style="width: 100%; margin-bottom: 5px">
		            <thead>
		                <tr>
		                    <th>Service</th>
		                    <th>Port</th>
		                    <th>Status</th>
		                </tr>
		            </thead>
		            <tbody data-template="row-template"
			         data-bind="source: dataSource">
			         	<tr>
					        <td>Web 4Xs</td>
					        <td>80</td>
					        <td><span class="fa fa-check text-success"></span></td>
					    </tr>
			         	<tr>
					        <td>Mongo</td>
					        <td>27017</td>
					        <td><span class="fa fa-check text-success"></span></td>
					    </tr>
			         </tbody>
		        </table>
	    	</div>
		</div>
		<div class="row">
	        <div class="col-md-6">
	            <!-- Web Server Block -->
	            <div class="block full">
	                <!-- Web Server Title -->
	                <div class="block-title">
	                    <div class="block-options pull-right">
	                    	<a role="button" class="btn btn-sm btn-alt btn-success" href="javascript:void(0)" onclick="topcpuDetail(this)"><b>TOP</b></a>
	                    	<span id="load-avg" class="label label-info"></span>
	                        <span id="cpu-load-live-info" class="label label-primary">%</span>
	                        <span class="label label-danger animation-pulse">CPU Load</span>
	                    </div>
	                    <h2><strong>CPU</strong> Server (<span id="numcores"></span> cores)</h2>
	                </div>
	                <!-- END Web Server Title -->

	                <!-- Web Server Content -->
	                <!-- Flot Charts (initialized in js/pages/index2.js), for more examples you can check out http://www.flotcharts.org/ -->
	                <div id="cpu-load-live" class="chart"></div>
	                <!-- END Web Server Content -->
	                <pre id="topcpu-detail" style="background-color: white; display: none; margin-top: 10px"></pre>
	            </div>
	            <!-- END Web Server Block -->
	        </div>
	        <div class="col-md-6">
	            <!-- Web Server Block -->
	            <div class="block full">
	                <!-- Web Server Title -->
	                <div class="block-title">
	                    <div class="block-options pull-right">
	                    	<a role="button" class="btn btn-sm btn-alt btn-success" href="javascript:void(0)" onclick="topmemDetail(this)"><b>TOP</b></a>
	                    	<span id="ram-detail" class="label label-info"></span>
	                        <span id="ram-load-live-info" class="label label-primary">%</span>
	                        <span class="label label-danger animation-pulse">RAM Usage</span>
	                    </div>
	                    <h2><strong>RAM</strong> Server</h2>
	                </div>
	                <!-- END Web Server Title -->

	                <!-- Web Server Content -->
	                <!-- Flot Charts (initialized in js/pages/index2.js), for more examples you can check out http://www.flotcharts.org/ -->
	                <div id="ram-load-live" class="chart"></div>
	                <pre id="topmem-detail" style="background-color: white; display: none; margin-top: 10px"></pre>
	                <!-- END Web Server Content -->
	            </div>
	            <!-- END Web Server Block -->
	        </div>
	    </div>
    	<!-- END Dashboard 2 Content -->
    </div>
    <script type="text/javascript">

    	var timeOutCheck = 1000;

    	function topcpuDetail(ele) {
    		$(ele).hide();
    		$.ajax({
    			url: ENV.reportApi + "server/topcpu",
    			global: false,
    			dataType: "text",
    			success: function(response) {
    				$('#topcpu-detail').show().html(response);
    			}
    		})
    		setTimeout(topcpuDetail, timeOutCheck);
    	}

    	function topmemDetail(ele) {
    		$(ele).hide();
    		$.ajax({
    			url: ENV.reportApi + "server/topmem",
    			global: false,
    			dataType: "text",
    			success: function(response) {
    				$('#topmem-detail').show().html(response);
    			}
    		})
    		setTimeout(topmemDetail, timeOutCheck);
    	}

    	function getSymbolByQuantity(Kbytes) {
			let symbol = ['KiB', 'MiB', 'GiB', 'TiB'];
			let exp = Math.floor(Math.log(Kbytes)/Math.log(1024));
			let stogare = Math.floor(Kbytes/Math.pow(1024, Math.floor(exp)) * 1000) / 1000;

			return [stogare, symbol[exp]];
		}

		function checkService() {
			var model = {
		        dataSource: new kendo.data.DataSource({
		            transport: {
		                read: `${ENV.reportApi}server/service`
		            },
		            schema: {
		                data: "data"
		            }
		        })
		    };
		    $serviceStatus = $("#service-status");
		    kendo.bind($serviceStatus, kendo.observable(model));
		}

    	var Server = function() {
		    return {
		        init: function(timeOutCheck) {
		        	var n = 300;
		        	var numcores = <?= isset($numcores) ? $numcores : 1 ?>;
		        	var total_mem = <?= isset($total_mem) ? $total_mem : 0 ?>;
		        	$("#numcores").text(numcores);
		            /*
		             * Flot Jquery plugin is used for charts
		             *
		             * For more examples or getting extra plugins you can check http://www.flotcharts.org/
		             * Plugins included in this template: pie, resize, stack, time
		             */

		            // Get the element to init
		            var chartLive = $('#cpu-load-live');

		            // Live Chart
		            var dataLive = [];

		            function getInit() {

		            	for (var i = 0; i < n; i++) {
		            		dataLive.push(0);
		            	}

		                var res = [];
		                for (var i = 0; i < dataLive.length; ++i)
		                    res.push([i, dataLive[i]]);

		                return res;
		            }

		            async function getCPUData() {

		                if (dataLive.length > 0)
		                    dataLive = dataLive.slice(1);

		                while (dataLive.length < n) {
		                	var y = 0;
		                	if(dataLive.length + 1 == n) {
			                	var loadAvg = await $.ajax({
			                		url: ENV.reportApi + "server/loadavg",
			                		global: false
			                	});
			                	if(loadAvg) {
				                    y = loadAvg.data[0] * 100 / numcores;
				                    $('#cpu-load-live-info').html(y.toFixed(0) + '%');
				                    $('#load-avg').text(loadAvg.data.join(" - "));
				                    $('#logon-users').text(loadAvg.users);
				                    $('#running-for').text(loadAvg.runningfor);
			                    }
		                    }
		                    dataLive.push(y);
		                }

		                var res = [];
		                for (var i = 0; i < dataLive.length; ++i)
		                    res.push([i, dataLive[i]]);

		                // Show live chart info
		                return res;
		            }

		            // Initialize live chart
		            var chartLive = $.plot(chartLive,
		                [{data: getInit()}],
			            {
			                series: {shadowSize: 0},
			                lines: {show: true, lineWidth: 1, fill: true, fillColor: {colors: [{opacity: 0.2}, {opacity: 0.2}]}},
			                colors: ['#34495e'],
			                grid: {borderWidth: 0, color: '#aaaaaa'},
			                yaxis: {show: true, min: 0, max: 105},
			                xaxis: {show: false}
			            }
		            );

		            // RAM
		            var chartLiveRam = $('#ram-load-live');

		            var dataLiveRam = [];

		            function getInitRam() {

		            	for (var i = 0; i < n; i++) {
		            		dataLiveRam.push(0);
		            	}

		                var res = [];
		                for (var i = 0; i < dataLiveRam.length; ++i)
		                    res.push([i, dataLiveRam[i]]);

		                return res;
		            }

		            async function getRAMData() {

		                if (dataLiveRam.length > 0)
		                    dataLiveRam = dataLiveRam.slice(1);

		                while (dataLiveRam.length < n) {
		                	var y = 0;
		                	if(dataLiveRam.length + 1 == n) {
			                	var ram = await $.ajax({
			                		url: ENV.reportApi + "server/ram",
			                		global: false
			                	});
			                	if(ram) {
				                	var used_mem = total_mem - ram.free; 
				                    y = used_mem * 100 / total_mem;
				                    $('#ram-load-live-info').html(y.toFixed(0) + '%');
				                    $('#ram-detail').text(getSymbolByQuantity(used_mem).join(" ") + " / " + getSymbolByQuantity(total_mem).join(" "));
			                    }
		                    }
		                    dataLiveRam.push(y);
		                }

		                var res = [];
		                for (var i = 0; i < dataLiveRam.length; ++i)
		                    res.push([i, dataLiveRam[i]]);

		                // Show live chart info
		                return res;
		            }

		            // Initialize live chart
		            var chartLiveRam = $.plot(chartLiveRam,
		                [{data: getInitRam()}],
			            {
			                series: {shadowSize: 0},
			                lines: {show: true, lineWidth: 1, fill: true, fillColor: {colors: [{opacity: 0.2}, {opacity: 0.2}]}},
			                colors: ['#34495e'],
			                grid: {borderWidth: 0, color: '#aaaaaa'},
			                yaxis: {show: true, min: 0, max: 105},
			                xaxis: {show: false}
			            }
		            );

		            // Update live chart
		            async function updateChartLive() {
		            	try {
			                chartLive.setData([await getCPUData()]);
			                chartLive.draw();
			                chartLiveRam.setData([await getRAMData()]);
			                chartLiveRam.draw();
		            	} catch(err) {
							console.log(err);
						}
		                setTimeout(updateChartLive, timeOutCheck);
		            }

		            // Start getting new data
		            updateChartLive();
		        }
		    };
		}();
		Server.init(timeOutCheck);
    </script>
</div>