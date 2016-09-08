<?php 
/**
 * Display appointments
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
"../css/custom.css"
			);
$js_head = array(
"../include/jquery/jquery.min.js",
"../include/bootstrap/js/bootstrap.min.js",
"../include/jquery-ui/jquery-ui.min.js",
"../include/timepicker/jquery-ui-timepicker-addon.js",
				);
if($locale != "en"){
	$js_head[] = "../include/jquery-ui/i18n/datepicker-" . $locale . ".js";
	$js_head[] = "../include/timepicker/i18n/jquery-ui-timepicker-" . $locale . ".js";
				}
$js_foot = array(
"../js/bootstrap-confirmation.js",
"../js/custom.js"
				);

//create new or update appointment
if (isset($_GET['start']) && isset($_GET['end']) && isset($_GET['update']))
{	
	$start = $db->qstr($_GET['start']);
	$end = $db->qstr($_GET['end']);
	$case_id = bigintval($_GET['case_id']);
	$respondent_id = bigintval($_GET['respondent_id']);
	$require_operator_id = "NULL";
	if ($_GET['require_operator_id'] > 1) $require_operator_id = bigintval($_GET['require_operator_id']);
	
	//* add new number to db
	if ( isset($_GET['addphonenumber']) && !empty($_GET['addphonenumber'])){
		add_contact_phone($case_id,$_GET['addphonenumber']);
		$contact_phone_id = $db->Insert_ID();
	}
	else {
		$contact_phone_id = bigintval($_GET['contact_phone_id']);
	}
	
	if (isset($_GET['new']) && $_GET['new'] == 'create'){
		$operator_id = get_operator_id();
		if ($operator_id == false) die();
		$sql = "SELECT Time_zone_name FROM respondent WHERE respondent_id = '$respondent_id'";
		$respondent_tz = $db->GetOne($sql);

		// create a call attempt
		$sql = "INSERT INTO call_attempt (call_attempt_id,case_id,operator_id,respondent_id,start,end)
		VALUES (NULL,$case_id,$operator_id,$respondent_id,CONVERT_TZ(NOW(),@@session.time_zone,'UTC'),CONVERT_TZ(NOW(),@@session.time_zone,'UTC'))";
		$db->Execute($sql);
		
		$call_attempt_id = $db->Insert_ID();
		
		$sql = "INSERT INTO `appointment` (appointment_id,case_id,contact_phone_id,call_attempt_id,start,end,require_operator_id,respondent_id,completed_call_id)
				VALUES(NULL,$case_id,$contact_phone_id,$call_attempt_id,CONVERT_TZ($start,'$respondent_tz','UTC'),CONVERT_TZ($end,'$respondent_tz','UTC'),$require_operator_id,$respondent_id,NULL)";
        $db->Execute($sql);
		
		$appointment_id = $db->Insert_ID();

		$_GET['appointment_id'] = $appointment_id;
		$appointment_id = bigintval($_GET['appointment_id']);		
		
	} else {

		$appointment_id = bigintval($_GET['appointment_id']);
		
		//Edit this appointment in the database
		$sql = "UPDATE appointment as a, respondent as r
		SET a.start = CONVERT_TZ($start,r.Time_zone_name,'UTC'), a.end = CONVERT_TZ($end,r.Time_zone_name,'UTC'), a.contact_phone_id = $contact_phone_id, a.respondent_id = $respondent_id, a.require_operator_id = $require_operator_id
		WHERE a.appointment_id = $appointment_id
		AND r.respondent_id = $respondent_id";

		$db->Execute($sql);
	}	
	unset ($_GET['start'],$_GET['end'],$_GET['new'],$_GET['update'],$_GET['appointment_id'],$_GET['case_id'],$_GET['addphonenumber']); //
}
	

if ( (isset($_GET['appointment_id']) && isset($_GET['case_id'])) ||(isset($_GET['new']) && isset($_GET['case_id'])))
{
	if (isset($_GET['appointment_id'])) $appointment_id = bigintval($_GET['appointment_id']); else $appointment_id = "";
	if (isset($_GET['case_id'])) $case_id = bigintval($_GET['case_id']);
	$require_operator_id = "NULL";

	if (isset($_GET['delete']))
	{
		$sql = "DELETE FROM appointment
			WHERE appointment_id = '$appointment_id'";
		$db->Execute($sql);
	
		xhtml_head(T_("Now modify case outcome"),true,$css,$js_head);
	
		print "<div class='col-lg-6'><p class='well'>" . T_("The appointment has been deleted. Now you must modify the case outcome") . "</p>
				<a href='supervisor.php?case_id=$case_id' class='btn btn-default'>" . T_("Modify case outcome") . "</a></div>";
	}
	else
	{
		$sql = "SELECT  CONVERT_TZ(NOW(),'SYSTEM',r.Time_zone_name) as startdate, 
						CONVERT_TZ(DATE_ADD(NOW(), INTERVAL 10 YEAR),'SYSTEM',r.Time_zone_name) as enddate,
						r.respondent_id, ca.contact_phone_id
						FROM `case` as c, `respondent` as r, `call` as ca
						WHERE c.case_id = '$case_id'
                        AND r.case_id = c.case_id
                        AND c.last_call_id = ca.call_id";
		$rs = $db->GetRow($sql); 
		
		$startdate = $rs['startdate'];
		$enddate = $rs['enddate'];
		$respondent_id = $rs['respondent_id'];
		if (!isset($contact_phone_id)) $contact_phone_id = $rs['contact_phone_id'];

		if (isset($_GET['new']) &&  $_GET['new'] == 'new'){
			$title = T_("Create NEW appointment"); 
			$subtitle ="";
 			$start = $startdate;
			$end = $enddate;
			$rtz = $_GET['rtz'];
		} 
		
		if  (isset($_GET['appointment_id'])) {
			$title = T_("Edit appointment");
			$subtitle = "ID&ensp;" . $appointment_id;
			
			$sql = "SELECT a.contact_phone_id,a.call_attempt_id, CONVERT_TZ(a.start,'UTC',r.Time_zone_name) as `start`, CONVERT_TZ(a.end,'UTC',r.Time_zone_name) as `end`, a.respondent_id, a.require_operator_id, r.Time_zone_name as rtz
			FROM `appointment` as a, respondent as r
			WHERE a.appointment_id = '$appointment_id'
			AND a.case_id = '$case_id'
			AND r.respondent_id = a.respondent_id";

			$rs = $db->GetRow($sql);

			if (!empty($rs)){ 
				$respondent_id = $rs['respondent_id'];
				$contact_phone_id = $rs['contact_phone_id'];
				$require_operator_id = $rs['require_operator_id'];
				$start = $rs['start'];
				$end = $rs['end'];
				$rtz = $rs['rtz'];
			}
			else die(T_("ERROR in DB records, Check tables 'appointment' and 'respondent' and Time zone settings"));
		}

		//Display an edit form	
		xhtml_head($title,true,$css,$js_head,false,false,false,$subtitle);

		print "<script type='text/javascript'> 
		$(document).ready(function() { var startDateTextBox = $('#start'); var endDateTextBox = $('#end');
			$.timepicker.datetimeRange( 
				startDateTextBox,endDateTextBox,{
				minInterval: (1000*60*15), // 15min
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
				minDate: '$startdate',
				maxDate: '$enddate'
				});});</script>";

			print "<form action='?' method='get' class='form-horizontal form-group'>";
			print "<label class='pull-left text-right control-label col-lg-2' for='respondent_id'>" . T_("Respondent") . "</label>";

			display_chooser($db->GetAll("SELECT respondent_id as value, CONCAT(firstName,' ',lastName) as description, 
										CASE when respondent_id = '$respondent_id' THEN 'selected=\'selected\'' ELSE '' END as selected 
										FROM respondent
										WHERE case_id = '$case_id'"),"respondent_id","respondent_id",false,false,false,true,false,true,"pull-left");
				
			print "<br/><br/><label for='contact_phone_id' class='pull-left text-right control-label col-lg-2'>" . T_("Contact phone") . "</label>";
			
			$sql = "SELECT contact_phone_id as value, phone as description,
					CASE when contact_phone_id = '$contact_phone_id' THEN 'selected=\'selected\'' ELSE '' END as selected
					FROM contact_phone
					WHERE case_id = '$case_id'";
			$rs = $db->GetAll($sql);
			
//* added option to add new number		
			print "<div class=\"pull-left\"><select class=\"form-control\" id='contact_phone_id' name='contact_phone_id' 
					onchange=\"if($(this).val()=='add'){ $('#addPhone').show(); } else{ $('#addPhone').hide(); } \">";
			foreach($rs as $l)
				{
					print "<option value='{$l['value']}' {$l['selected']} >{$l['description']}</option>";
				}
			print "<option value='add'>" . T_("Add new phone number") . "</option></select></div>";

			print "<div class='col-lg-4' id='addPhone' style='display:none'>
						<div class='col-lg-6' id=''>
							<input type=\"tel\" maxlength=\"10\" pattern=\"[0-9]{10}\" class='form-control col-lg-2 ' name='addphonenumber'  />
						</div>
					</div>";
//*end option
			
		 	print "<div class='clearfix'></div></br><div class='alert alert-info col-lg-6 '>". T_("ATTENTION!    Keep in mind that you're setting 'Start' & 'End' appoinment times in RESPONDENT LOCAL TIME !!!") . "</div><div class='clearfix'></div>";
			
			date_default_timezone_set($rtz);
			
			print "<label class='text-right col-lg-2 control-label'>" . T_("Respondent TimeZone") . ":</label>
					<h4 class='col-lg-2  text-danger text-uppercase  fa-lg'>" . $rtz . "</h4>
					<label class=''>" . T_("Respondent Time") . ":&emsp;<b class='fa fa-2x '>" . date("H:i:s") . "</b></label>";

			print "<br/><br/><label class='pull-left text-right control-label col-lg-2' for='start'>" . T_("Start time") . "</label>
					<div class='pull-left'><input class='form-control' type='text' value='$start' id='start' name='start'/></div>";
			print "<br/><br/><label class='pull-left text-right control-label col-lg-2' for='end'>" . T_("End time") . "</label>
					<div class='pull-left'><input class='form-control' type='text' value='$end' id='end' name='end'/></div>";
			print "<br/><br/><label class='pull-left text-right control-label col-lg-2' for='require_operator_id'>" . T_("Appointment with") . "</label>";
			$ops = $db->GetAll("SELECT o.operator_id as value,
						CONCAT(o.firstName, ' ', o.lastName) as description,
						CASE WHEN o.operator_id = '$require_operator_id' THEN 'selected=\'selected\'' ELSE '' END as selected
						FROM operator as o");
			$selected = "selected=\'selected\'";
			foreach($ops as $o)
			{
				if (!empty($o['selected']))
				{
					$selected = "";
					break;
				}
			}
			array_unshift($ops,array('value'=>0,'description'=>T_("Any operator"),'selected'=>$selected));
			
			display_chooser($ops,"require_operator_id","require_operator_id",false,false,false,true,false,true,"pull-left");
			 
			print "	<input type='hidden' value='$appointment_id' id='appointment_id' name='appointment_id'/>
					<input type='hidden' value='update' id='update' name='update'/>
					<input type='hidden' value='$case_id' id='case_id' name='case_id'/>";
			
			if (isset($_GET['new']) && $_GET['new'] == 'new') { 
				print "<input type='hidden' value='create' id='new' name='new'/>";
				}

			print "<div class='clearfix'></div><br/><br/>
				<div class='col-lg-2'><a href='?'  class='btn btn-default pull-left'><i class='fa fa-ban fa-lg'></i>&emsp;" . T_("Cancel edit") . "</a></div>";
			
			print "<div class='col-lg-2'>
					<button type='submit' class='btn btn-primary btn-block'><i class='fa fa-floppy-o fa-lg'></i>&emsp;" . T_("Save changes") . "</button>
					</div>";

			print "<div class='col-lg-2'><a href='' class='btn btn-default pull-right'  toggle='confirmation' data-placement='left' data-href='?delete=delete&amp;appointment_id=$appointment_id&amp;case_id=$case_id' ><i class='fa fa-trash fa-lg text-danger'></i>&emsp;" . T_("Delete this appointment") . "</a></div>";

			print "</form>";
	}
}
else {
	$operator_id = get_operator_id();
	$subtitle = T_("Appointments"); 
	xhtml_head(T_("Display Appointments"),true,$css,$js_head,false,30);
	print "<h3>" . T_("All appointments (with times displayed in your time zone)") . "</h3>";

	$sql = "SELECT MIN(q.description) as description, MIN(si.description) as smpl, MIN(CONVERT_TZ(a.start,'UTC',@@session.time_zone)) as start, MIN(CONVERT_TZ(a.end,'UTC',@@session.time_zone)) as end,MIN(CONCAT(r.firstName, ' ', r.lastName)) as resp, MIN( IFNULL(ou.description,'" . TQ_("Not yet called") . "')) as outcome, MIN(oo.firstName) as makerName, MIN(ooo.firstName) as callerName, 
	CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>') as case_id, 
	MIN(CONCAT('&emsp;<a href=\'\'><i class=\'fa fa-trash-o fa-lg text-danger\' toggle=\'confirmation\' data-title=\'" . TQ_("ARE YOU SURE?") . "\' data-btnOkLabel=\'" . TQ_("Yes") . "\' data-btnCancelLabel=\'" . TQ_("No") . "\' data-placement=\'left\' data-href=\'?case_id=', c.case_id, '&amp;appointment_id=', a.appointment_id, '&amp;delete=delete\'  ></i></a>&emsp;')) as link, 
	MIN(CONCAT('&emsp;<a href=\'?case_id=', c.case_id, '&amp;appointment_id=', a.appointment_id, '\'><i class=\'fa fa-pencil-square-o fa-lg\' ></i></a>&emsp;')) as edit,MIN(IFNULL(ao.firstName,'" . TQ_("Any operator") . "')) as witho 
	FROM appointment as a 
	JOIN (`case` as c, respondent as r, questionnaire as q, operator as oo, call_attempt as cc, `sample` as s, sample_import as si) on (c.sample_id = s.sample_id and  a.case_id = c.case_id and a.respondent_id = r.respondent_id and q.questionnaire_id = c.questionnaire_id and a.call_attempt_id = cc.call_attempt_id and cc.operator_id =  oo.operator_id and si.sample_import_id = s.import_id) 
	LEFT JOIN (`call` as ca, outcome as ou, operator as ooo) ON (ca.call_id = a.completed_call_id and ou.outcome_id = ca.outcome_id and ca.operator_id = ooo.operator_id) 
	LEFT JOIN operator AS ao ON ao.operator_id = a.require_operator_id 
	LEFT JOIN (questionnaire_sample_quota as qsq) on (s.import_id  = qsq.sample_import_id and c.questionnaire_id = qsq.questionnaire_id)
	LEFT JOIN (questionnaire_sample_quota_row as qsqr) on (s.import_id = qsqr.sample_import_id  and c.questionnaire_id = qsqr.questionnaire_id)
	WHERE q.enabled=1 AND si.enabled=1 AND a.end >= CONVERT_TZ(NOW(),'System','UTC') AND c.current_outcome_id IN (19,20,21,22)
	AND (qsq.quota_reached IS NULL OR qsq.quota_reached != 1)
	AND (qsqr.quota_reached IS NULL OR qsqr.quota_reached != 1)
	GROUP BY c.case_id ORDER BY a.start ASC";
	$rs = $db->GetAll($sql);
	if (!empty($rs)) {
		translate_array($rs,array("outcome"));
		xhtml_table($rs,array("description","smpl","case_id","start","end","edit","makerName","witho","resp","outcome","callerName","link"),array(T_("Questionnaire"),T_("Sample"),T_("Case ID"),T_("Start"),T_("End"),"&emsp;<i class='fa fa-pencil-square-o fa-lg' data-toggle='tooltip' title='" . T_("Edit") . "'></i>&emsp;",T_("Created by"),T_("Appointment with"),T_("Respondent"),T_("Current outcome"),T_("Operator who called"),"&emsp;<i class='fa fa-trash-o fa-lg' data-toggle='tooltip' title='" . T_("Delete") . "'></i>&emsp;"),"tclass",false,false,"bs-table");
		
	} else print "<h4 class='well text-info'>" . T_("No future appointments") . "</h4>";
	
	print "<h3 style='color:red'>" . T_("Missed appointments (with times displayed in your time zone)") . "</h3>";

	$sql = "SELECT MIN(q.description), MIN(si.description) as smpl, MIN(CONVERT_TZ(a.start,'UTC',@@session.time_zone)) as start, MIN(CONVERT_TZ(a.end,'UTC',@@session.time_zone)) as end, MIN(CONCAT(r.firstName, ' ', r.lastName)) as resp, 
	MIN(CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>')) as case_id, 
	MIN(CONCAT('&emsp;<a href=\'\'><i class=\'fa fa-trash-o fa-lg text-danger\' toggle=\'confirmation\' data-title=\'" . TQ_("ARE YOU SURE?") . "\' data-btnOkLabel=\'" . TQ_("Yes") . "\' data-btnCancelLabel=\'" . TQ_("No") . "\' data-placement=\'left\' data-href=\'?case_id=', c.case_id, '&amp;appointment_id=', a.appointment_id, '&amp;delete=delete\'  ></i></a>&emsp;')) as link, 
	MIN(CONCAT('&emsp;<a href=\'?case_id=', c.case_id, '&amp;appointment_id=', a.appointment_id, '\'><i class=\'fa fa-pencil-square-o fa-lg\' ></i></a>&emsp;')) as edit 
	FROM appointment as a 
	JOIN (`case` as c, respondent as r, questionnaire as q, `sample` as s, sample_import as si) on (a.case_id = c.case_id and a.respondent_id = r.respondent_id and q.questionnaire_id = c.questionnaire_id and s.sample_id = c.sample_id and s.import_id= si.sample_import_id) 
	LEFT JOIN (`call` as ca) ON (ca.call_id = a.completed_call_id)
	LEFT JOIN (questionnaire_sample_quota as qsq) on (s.import_id  = qsq.sample_import_id and c.questionnaire_id = qsq.questionnaire_id)
	LEFT JOIN (questionnaire_sample_quota_row as qsqr) on (s.import_id = qsqr.sample_import_id  and c.questionnaire_id = qsqr.questionnaire_id)
	WHERE q.enabled=1 AND si.enabled=1 AND a.end < CONVERT_TZ(NOW(),'System','UTC') AND a.completed_call_id IS NULL AND c.current_outcome_id IN (19,20,21,22)
	AND (qsq.quota_reached IS NULL OR qsq.quota_reached != 1 )
	AND (qsqr.quota_reached IS NULL OR qsqr.quota_reached != 1)
	GROUP BY c.case_id
	ORDER BY a.start ASC";
	
	$rs = $db->GetAll($sql);
	if (!empty($rs)) {
		xhtml_table($rs,array("description","smpl","case_id","start","end","edit","resp","link"),array(T_("Questionnaire"),T_("Sample"),T_("Case ID"),T_("Start"),T_("End"),"&emsp;<i class='fa fa-pencil-square-o fa-lg' data-toggle='tooltip' title='" . T_("Edit") . "'></i>&emsp;",T_("Respondent"),"&emsp;<i class='fa fa-trash-o fa-lg' data-toggle='tooltip' title='" . T_("Delete") . "'></i>&emsp;"),"tclass",false,false,"bs-table");
		
	} else print "<h4 class='well text-info'>" . T_("No missed appointments") . "</h4>";
	
}
xhtml_foot($js_foot);
?>
<script type="text/javascript">
$('[toggle="confirmation"]').confirmation()
</script>
