<!--
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
-->

<?php
  global $path, $embed;
?>

 <!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
 <script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.min.js"></script>
 <script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/jquery.flot.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.selection.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/inst.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/proc.js"></script>

<?php if (!$embed) { ?>
<h2>Raw data: <?php echo $feedidname; ?></h2>
<?php } ?>

    <div id="graph_bound" style="width:100%; height:400px; position:relative; ">
      <div id="graph"></div>
<!---      
      <div style="position:absolute; top:20px; left:40px;">

        <input class="time" type="button" value="D" time="1"/>
        <input class="time" type="button" value="W" time="7"/>
        <input class="time" type="button" value="M" time="30"/>
        <input class="time" type="button" value="Y" time="365"/> | 

        <input id="zoomin" type="button" value="+"/>
        <input id="zoomout" type="button" value="-"/>
        <input id="left" type="button" value="<"/>
        <input id="right" type="button" value=">"/>

      </div>
--->
        <h3 style="position:absolute; top:0px; left:310px;"><span id="stats"></span></h3>
    </div>

<script id="source" language="javascript" type="text/javascript">

  var feedid = "<?php echo $feedid; ?>";
  var altFeedId = "<?php echo $altFeedId; ?>";
  if(feedid == altFeedId || altFeedId =="")
  {
    altFeed = false;
  }else{
    altFeed = true;
    }
  var feedname = "<?php echo $feedidname; ?>";
  var path = "<?php echo $path; ?>";
  var apikey = "<?php echo $apikey; ?>";
  var valid = "<?php echo $valid; ?>";

  var plotfill = 0;
  if (plotfill==1) plotfill = true; else plotfill = false;
  var units = "V";

  var embed = <?php echo $embed; ?>;
  $('#graph').width($('#graph_bound').width());
  $('#graph').height($('#graph_bound').height());
  if (embed) $('#graph').height($(window).height());

  var timeWindow = 41;//(3600000*24.0*7);				//Initial time window
  var start = 0; //((new Date()).getTime())-timeWindow;		//Get start time
  var end = timeWindow; //(new Date()).getTime();				//Get end time
  

  var plotlist = [];
  plotlist[0] = {id: feedid, selected: 1, plot: {data: null, label: feedname, lines: { show: true, fill: 0 } } };
  plotlist[0].plot.yaxis = 1;
  
  if (altFeed==true){
    plotlist[1] = {id: altFeedId, selected: 1, plot: {data: null, label: feedname, lines: { show: true, fill: 0 } } };
    plotlist[1].plot.yaxis = 2;
  }

    
    
  var graph_data = [];
  vis_feed_data();

  $(window).resize(function(){
    $('#graph').width($('#graph_bound').width());
    if (embed) $('#graph').height($(window).height());
    plot();
  });

  function vis_feed_data()
  {
    plotdata = [];
    for(var i in plotlist) {
      if (plotlist[i].selected) {        
        if (!plotlist[i].plot.data) plotlist[i].plot.data = get_wave_feed_data(plotlist[i].id,timeWindow);
        if ( plotlist[i].plot.data) plotdata.push(plotlist[i].plot);
      }
    }

    plot();
    /*
    if (valid) graph_data = get_wave_feed_data(feedid,timeWindow);
   
    var stats = power_stats(graph_data);
    var out = "Average: "+stats['average'].toFixed(0)+units;
    if (units=='W') out+= " | "+stats['kwh'].toFixed(2)+" kWh";
    $("#stats").html(out);   
    plot();*/
  }
  
//-------------------------------------------------------------------------------
  // Get feed data
  //-------------------------------------------------------------------------------
  function get_wave_feed_data(feedID,datacount)
  {
    var feedIn = [];
    var query = "&id="+feedID+"&datacount="+datacount;
    if (apikey!="") query+= "&apikey="+apikey;

    $.ajax({                                    
      url: path+'feed/latestdata.json',                         
      data: query,  
      dataType: 'json',                           
      async: false,
      success: function(datain) { feedIn = datain; }
    });
    return feedIn;
  }
  
  function plot()
  {
    $.plot($("#graph"), plotdata, {
      grid: { show: true, hoverable: true, clickable: true },
      xaxis: {  },
      selection: { mode: "xy" },
      legend: { position: "nw"}
    });
  }
  //mode: "time", localTimezone: true, min: start, max: end
  function plotold()
  {
    var plot = $.plot($("#graph"), [{data: graph_data, lines: { show: true, fill: plotfill }}], {
      grid: { show: true, hoverable: true, clickable: true },
      xaxis: {   },
      selection: { mode: "xy" }
    });
  }

  //--------------------------------------------------------------------------------------
  // Graph zooming
  //--------------------------------------------------------------------------------------
  $("#graph").bind("plotselected", function (event, ranges) { start = ranges.xaxis.from; end = ranges.xaxis.to; vis_feed_data(); });
  //----------------------------------------------------------------------------------------------
  // Operate buttons
  //----------------------------------------------------------------------------------------------
  $("#zoomout").click(function () {inst_zoomout(); vis_feed_data();});
  $("#zoomin").click(function () {inst_zoomin(); vis_feed_data();});
  $('#right').click(function () {inst_panright(); vis_feed_data();});
  $('#left').click(function () {inst_panleft(); vis_feed_data();});
  $('.time').click(function () {inst_timewindow($(this).attr("time")); vis_feed_data();});
  //-----------------------------------------------------------------------------------------------
</script>

