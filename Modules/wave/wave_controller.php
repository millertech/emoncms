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

  function wave_controller()
  {
    require "Modules/input/input_model.php";
    require "Modules/feed/feed_model.php";
    require "Modules/input/process_model.php";
    require "Modules/wave/wave_model.php";
    
    global $session, $route;

    $format = $route['format'];
    $action = $route['action'];
    $subaction = $route['subaction'];
    
    $output['content'] = "";
    $output['message'] = "";
    
    if ($action == 'post' && $session['write'])
    {
      $node = intval(get('node'));
      $wave = intval(get('wave'));
      $csv = db_real_escape_string(get('csv'));
      var_dump($csv);
      $datapairs = array();
  
      /*if ($csv)
      {
        $values = explode(',', $csv);
        var_dump($values);
        $i = 0;
        foreach ($values as $value)
        {
          $i++; 
          if ($node) $key = $i; else $key = "wave".$i;
          $datapairs[] = $key.":".$value;
        }
      }
      */
        $i = 0; 
        if ($wave) $key = "wave".$wave; else $key = "wave".$i;
        $datapairs[] = $key.":".$csv;
        var_dump($datapairs);
        
      if ($csv)
      {
        $time = time();						// get the time - data recived time
        if (isset($_GET["time"])) $time = intval($_GET["time"]);	// or use sent timestamp if present 
  
        $inputs = register_wave_input($session['userid'],$node,$datapairs,$time);      // register inputs
        process_wave_input($session['userid'],$inputs,$time);                          // process inputs to feeds etc
        $output['message'] = "ok";
      }
      else
      {
        $output['message'] = "No csv or json input data present";
      }
    }
    return $output;
}

?>
