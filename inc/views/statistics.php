<div style="float: left;">
	<label for="period">Period:</label> <select id="period" name="period"
		nonce="<?php echo $nonce; ?>">
		<option value="43200">Past 30 days</option>
		<option value="10080">Past 7 days</option>
		<option value="1440" selected>Past day</option>
	</select>
</div>
<div>
	<span class="spinner" style="float: left;"></span>
</div>
<div style="clear: both;"></div>
<br />
<div id='wpss_stats_error'
	style="width: 100%; color: red; font-weight: bold;"></div>
<br />
<div style="width: 100%">
	<div id="pageviews"
		style="width: 100%; max-width: 300px; height: 300px; float: left; display: none;"></div>
	<div id="uniques"
		style="width: 100%; max-width: 300px; height: 300px; float: left; display: none;"></div>
	<div id="bandwidth"
		style="width: 100%; max-width: 300px; height: 300px; float: left; display: none;"></div>
	<div style="clear: both;"></div>
</div>
<div style="clear: both;"></div>

<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
	var stats = null;<?php /* echo json_encode($stats); */ ?>;
	//
	function get_charts() {
		jQuery('div#pageviews').html('');
		jQuery('div#uniques').html('');
		jQuery('div#bandwidth').html('');
		if (typeof(stats.stats.msg) == 'string') {
			jQuery('div#wpss_stats_error').html('API message: '+stats.stats.msg+'.');
		}
		else if ((typeof(stats.stats.error) == 'string')) {
			jQuery('div#wpss_stats_error').html('API message: '+stats.stats.error+'.');
		}
		else {
			jQuery('div#wpss_stats_error').html('');
			jQuery('#pageviews').css('display','block');
			jQuery('#uniques').css('display','block');
			jQuery('#bandwidth').css('display','block');
			drawChartPageViews();
			drawChartUniques();
			drawBandwidth();
		}
	}
	//
   	google.load("visualization", "1", {packages:["corechart"]});
  	google.setOnLoadCallback(start_charts);
	//
    function drawChartPageViews() {
      	var pageviews = stats.stats.result.totals.pageviews;
      	//console.log(pageviews);
		var data = google.visualization.arrayToDataTable([
          	['Page Views', "Views"],
          	['All', pageviews.all],
        ]);

        var options = {
          	title: 'Page Views (Total)',
	        legend: {position: 'top', maxLines: 3},
    	    titleTextStyle: {fontSize: 15},
        	pieSliceText: 'value',
          	width: 340
        };

        var chart = new google.visualization.PieChart(document.getElementById('pageviews'));

        chart.draw(data, options);
    }
    //
    function drawChartUniques() {
      	var pageviews = stats.stats.result.totals.requests;
		var data = google.visualization.arrayToDataTable([
          	['Page Views', "Views"],
          	['Cached', pageviews.cached ],
          	['Uncached', pageviews.all-pageviews.cached ]
        ]);

        var options = {
          	title: 'Requests',
          	legend: {position: 'top', maxLines: 3},
          	titleTextStyle: {fontSize: 15},
          	pieSliceText: 'value',
          	width: 340
        };

        var chart = new google.visualization.PieChart(document.getElementById('uniques'));

        chart.draw(data, options);
    }
    //
    function drawBandwidth() {
      	var pageviews = stats.stats.result.totals.bandwidth;
		var data = google.visualization.arrayToDataTable([
         	['Bandwidth [MB]', "MB"],
          	['Cached', pageviews.cached/1024 ],
          	['Uncached', (pageviews.all-pageviews.cached)/1024 ]
        ]);

        var options = {
          	title: 'Bandwidth',
          	legend: {position: 'top', maxLines: 3},
          	titleTextStyle: {fontSize: 15},
          	pieSliceText: 'value',
          	width: 340
        };

        var chart = new google.visualization.PieChart(document.getElementById('bandwidth'));

        chart.draw(data, options);
    }
   	function start_charts() {
	   	jQuery("#period").change();
	}
	//
	jQuery(document).ready( function() {
   		jQuery("#period").change( function() {
   			jQuery(".spinner").css('display','inline');
			var period = jQuery("#period").val();
			var nonce = jQuery("#period").attr('nonce');
			jQuery('div#wpss_stats_error').html('');
	      	jQuery.ajax({
  	       		type : "post",
    	     	dataType : "json",
      	   		url : "<?php echo admin_url('admin-ajax.php'); ?>",
        	 	data : {action: 'wpss_stat', period: period, nonce: nonce},
	         	success: function(ret) {
	  	       		stats = ret;	  	       		
					get_charts();
        		},
	         	complete: function() {
  	       			jQuery(".spinner").css('display','none');
    	     	}
      		})
	   	})
	})
</script>
