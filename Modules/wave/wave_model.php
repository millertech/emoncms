<?php
/*
 All Emoncms code is released under the GNU Affero General Public License.
 See COPYRIGHT.txt and LICENSE.txt.

 ---------------------------------------------------------------------
 Emoncms - open source energy visualisation
 Part of the OpenEnergyMonitor project:
 http://openenergymonitor.org
 */

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');
 class ProcessArg {
    const VALUE = 0;
    const INPUTID = 1;
    const FEEDID = 2;
  }

  class DataType {
    const UNDEFINED = 0;
    const REALTIME = 1;
    const DAILY = 2;
    const HISTOGRAM = 3;
  }
  //-------------------------------------------------------------------------
  function register_wave_input($userid,$nodeid,$datapairs,$time)
  {
  //--------------------------------------------------------------------------------------------------------------
  // 2) Register incoming inputs
  //--------------------------------------------------------------------------------------------------------------
  $inputs = array();
  foreach ($datapairs as $datapair)       
  {
    $datapair = explode(":", $datapair);
    $name = preg_replace('/[^\w\s-.]/','',$datapair[0]); 	// filter out all except for alphanumeric white space and dash
    var_dump($name);
    $value = $datapair[1];
    echo "Datapair <br><br><br>";
    var_dump($value);
    
    if ($nodeid) $name = "node".$nodeid."_".$name;

    $id = get_input_id($userid,$name);				// If input does not exist this return's a zero

    if ($id==0) {
      $id = create_wave_input_timevalue($userid,$name,$nodeid,$time,$value);	// Create input if it does not exist

      // auto_configure_inputs($userid,$id,$name);

    } else {			
      $inputs[] = array($id,$time,$value);	
      set_wave_input_timevalue($id,$time,$value);			// Set time and value if it does
    }
  }

  return $inputs;
  }
  
  function create_wave_input_timevalue($user, $name, $nodeid, $time, $value)
{
  $time = date("Y-n-j H:i:s", $time);
   $wave = explode(",",$value);
   $waveCount = count($wave);
  db_query("INSERT INTO input (userid,name,nodeid,time,value_list,value) VALUES ('$user','$name','$nodeid','$time','$value','$waveCount')");
  $inputid = db_insert_id();
  return $inputid;
}

function set_wave_input_timevalue($id, $time, $value)
{
  $time = date("Y-n-j H:i:s", $time);
  $wave = explode(",",$value);
   $waveCount = count($wave);
  db_query("UPDATE input SET time='$time', value_list = '$value', value = '$waveCount' WHERE id = '$id'");
}
 function process_wave_input($userid,$inputs,$time)
  {
  //--------------------------------------------------------------------------------------------------------------
  // 3) Process inputs according to input processlist
  //--------------------------------------------------------------------------------------------------------------
	  foreach ($inputs as $input)            
	  {
	    $id = $input[0];
	    $input_processlist =  get_wave_input_processlist($userid,$id);
	    if ($input_processlist)
	    {
	      $processlist = explode(",",$input_processlist);				
	      $value = $input[2];
	      foreach ($processlist as $inputprocess)    			        
	      {
	        $inputprocess = explode(":", $inputprocess); 		// Divide into process id and arg
	        $processid = $inputprocess[0];				// Process id
	        $arg = $inputprocess[1];	 			// Can be value or feed id
	
	        $process_list = get_wave_process_list();
	        $process_function = $process_list[$processid][2];	// get process function name
	        $value = $process_function($arg,$time,$value);		// execute process function
	      }
	    }
	  }
  }
  
  function get_wave_input_processlist($userid, $id)
{
  $result = db_query("SELECT processList FROM input WHERE userid='$userid' AND id='$id'");
  $array = db_fetch_array($result);
  return $array['processList'];
}
function log_waveform2($id, $time, $value)
{
    $wave = explode(",",$value);
    echo "value:<br>";
    var_dump($value);
    echo "value:<br>";
    var_dump($wave);
    $timeOffset = count($wave);
    foreach ($wave as $input)
    {
        $newTime = $time - $timeOffset;
        insert_wave_feed_data($id, $newTime, floatval($input));
        $timeOffset--;
    }
    //die();
  

  return $value;
}

function insert_wave_feed_data($feedid,$feedtime,$value)
  { 
    if (get_feed_field($feedid,'status')==1) return $value;	// Dont insert if deleted

    $feedname = "feed_".trim($feedid)."";

    // a. Insert data value in feed table
    db_query("INSERT INTO $feedname (`time`,`data`) VALUES ('$feedtime','$value')");

    return $value;
  }
  
function get_wave_process_list()
{
  $list = array();

  // Process description
  // Arg type
  // Function Name
  // No. of datafields if creating feed
  // Data type

  $list[1] = array(
    _("Log to feed"),
    ProcessArg::FEEDID,
    "log_to_feed",
    1,
    DataType::REALTIME
  );
  $list[2] = array(
    "x",
    ProcessArg::VALUE,
    "scale",
    0,
    DataType::UNDEFINED
  );
  $list[3] = array(
    "+",
    ProcessArg::VALUE,
    "offset",
    0,
    DataType::UNDEFINED
  );
  $list[4] = array(
    _("Power to kWh"),
    ProcessArg::FEEDID,
    "power_to_kwh",
    1,
    DataType::REALTIME
  );
  $list[5] = array(
    _("Power to kWh/d"),
    ProcessArg::FEEDID,
    "power_to_kwhd",
    1,
    DataType::DAILY
  );
  $list[6] = array(
    _("x input"),
    ProcessArg::INPUTID,
    "times_input",
    0,
    DataType::UNDEFINED
  );
  $list[7] = array(
    _("input on-time"),
    ProcessArg::FEEDID,
    "input_ontime",
    1,
    DataType::DAILY
  );
  $list[8] = array(
    _("kWhinc to kWh/d"),
    ProcessArg::FEEDID,
    "kwhinc_to_kwhd",
    1,
    DataType::DAILY
  );
  $list[9] = array(
    _("kWh to kWh/d (OLD)"),
    ProcessArg::FEEDID,
    "kwh_to_kwhd",
    1,
    DataType::DAILY
  );
  $list[10] = array(
    _("update feed @time"),
    ProcessArg::FEEDID,
    "update_feed_data",
    1,
    DataType::UNDEFINED
  );
  $list[11] = array(
    _("+ input"),
    ProcessArg::INPUTID,
    "add_input",
    0,
    DataType::UNDEFINED
  );
  $list[12] = array(
    _("/ input"),
    ProcessArg::INPUTID,
    "divide_input",
    0,
    DataType::UNDEFINED
  );
  $list[13] = array(
    _("phaseshift"),
    ProcessArg::VALUE,
    "phaseshift",
    0,
    DataType::UNDEFINED
  );
  $list[14] = array(
    _("accumulator"),
    ProcessArg::FEEDID,
    "accumulator",
    1,
    DataType::REALTIME
  );
  $list[15] = array(
    _("rate of change"),
    ProcessArg::FEEDID,
    "ratechange",
    1,
    DataType::REALTIME
  );
  $list[16] = array(
    _("histogram"),
    ProcessArg::FEEDID,
    "histogram",
    2,
    DataType::HISTOGRAM
  );
  $list[17] = array(
    _("average"),
    ProcessArg::FEEDID,
    "average",
    2,
    DataType::HISTOGRAM
  );

  $list[18] = array(
    _("heat flux"),
    ProcessArg::FEEDID,
    "heat_flux",
    1,
    DataType::REALTIME
  );

  $list[19] = array(
    _("power gained to kWh/d"),
    ProcessArg::FEEDID,
    "power_acc_to_kwhd",
    1,
    DataType::DAILY
  );
  
  $list[20] = array(
    _("pulse difference"),
    ProcessArg::FEEDID,
    "pulse_diff",
    1,
    DataType::REALTIME  
  );
  
  $list[21] = array(
    _("KWh to Power"),
    ProcessArg::FEEDID,
    "kwh_to_power",
    1,
    DataType::REALTIME  
  );
  $list[22] = array(
    _("- input"),
    ProcessArg::INPUTID,
    "subtract_input",
    0,
    DataType::UNDEFINED
  );
  $list[23] = array(
    _("kWh to kWh/d"),
    ProcessArg::FEEDID,
    "kwh_to_kwhd2",
    2,
    DataType::HISTOGRAM
  );
  $list[24] = array(
    _("Log Waveform"),
    ProcessArg::FEEDID,
    "log_waveform2",
    1,
    DataType::REALTIME
  );

  return $list;
}
  ?>
