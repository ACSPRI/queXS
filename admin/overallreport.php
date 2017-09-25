<?php 
/**
 * Report by operator/questionnaire
 */

/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

/**
 * Database file
 */
include ("../db.inc.php");

/**
 * Authentication file
 */
require ("auth-admin.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Performance functions
 */
include ("../functions/functions.performance.php");

/**
 * Operator functions
 */
include ("../functions/functions.operator.php");

/**
 * Input functions
 */
include ("../functions/functions.input.php");

/**
 * Calendar functions
 */
include ("../functions/functions.calendar.php");

$css = array(
"../include/bootstrap/css/bootstrap.min.css", 
"../include/bootstrap/css/bootstrap-theme.min.css",
"../include/font-awesome/css/font-awesome.css",
"../include/jquery-ui/jquery-ui.min.css",
"../include/timepicker/jquery-ui-timepicker-addon.css",
"../css/custom.css",
"../include/bootstrap-toggle/css/bootstrap-toggle.min.css",
"../include/datatables/datatables.min.css",
			);
$js_head = array(
"../include/jquery/jquery.min.js",
"../include/bootstrap/js/bootstrap.min.js",
"../include/jquery-ui/jquery-ui.min.js",
"../include/timepicker/jquery-ui-timepicker-addon.js",
"../include/datatables/datatables.min.js",
"../include/bootstrap-toggle/js/bootstrap-toggle.min.js",
				);
if($locale != "en"){
	$js_head[] = "../include/jquery-ui/i18n/datepicker-" . $locale . ".js";
	$js_head[] = "../include/timepicker/i18n/jquery-ui-timepicker-" . $locale . ".js";
				}
$js_foot = array(
"../js/bootstrap-confirmation.js",
"../js/custom.js"
				);

$start = "";
$end = "";
$rtype = "";

if (isset($_GET['start']) && isset($_GET['end'])) {	
	$start = $_GET['start'];
  $end = $_GET['end'];
} 

if (isset($_GET['rtype'])) {
  $rtype = "checked=\"checked\"";
}

$title= T_("Overall performance report");

//Display a search form
xhtml_head($title,true,$css,$js_head,false,false,false,$subtitle);

print "<script type='text/javascript'> 
$(document).ready(function() { var startDateTextBox = $('#start'); var endDateTextBox = $('#end');
  $.timepicker.datetimeRange( 
    startDateTextBox,endDateTextBox,{
    numberOfMonths: 2,
    dateFormat: 'yy-mm-dd', 
    timeFormat: 'HH:mm:ss',
    showSecond: false,
    regional: '$locale',
    hourMin: 0,
    hourMax: 23,
    stepMinute: 5,
    hourGrid: 2,
    minuteGrid: 10,
    });});</script>";

print "<form action='?' method='get' class='form-vertical'>";

print "<div class='form-group'><label for='start'>" . T_("Start time") . "</label>
    <input type='text' value='$start' id='start' name='start'/></div>";
print "<div class='form-group'><label for='end'>" . T_("End time") . "</label>
    <input type='text' value='$end' id='end' name='end'/></div>";

?>

  <div class="form-group">
    <label><?php  echo T_("Report type"); ?></label>
      <input name="rtype" type="checkbox" <?php echo $rtype; ?> data-toggle="toggle" data-on="<?php echo T_("by Operator"); ?>" data-off="<?php echo T_("by Questionnaire"); ?>" data-width="200" />
  </div>

<?php

print "<div><button class='submitclass btn btn-default' type='submit' name='submit'><i class='fa fa-file-text'></i>&emsp;" . T_("Generate report") ."</button></div>";

print "</form>";


//generate report
if (isset($_GET['start'])) {

  $report = get_stats_by_time($start,$end,isset($_GET['rtype']));

  print "<div>";
	xhtml_table($report,array("firstName","description","completions","totalcalls","time","callt","CPH","CALLSPH","effectiveness"),array(T_("Operator"),T_("Questionnaire"),T_("Completions"),T_("Calls"),T_("Total time"),T_("Call time"),T_("Completions p/h"),T_("Calls p/h"),T_("Effectiveness")),"tclass",false,false,"bs-table");
  print "</div>";

?>
  <script type="text/javascript">
  $(document).ready(function() {
    $('#bs-table').DataTable( {
        "dom": 'Bfrtip',
        "buttons": ['copy','csv','excel','pdf','print'],
        "paging":   false,
        "ordering": false,
    } );
  } );
  </script>
<?php

}



xhtml_foot($js_foot);
