<?php 
/**
 * View cases referred to the supervisor and add notes/assign outcomes
 */

/**
 * Configuration file
 */
include("../config.inc.php");

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
include("../functions/functions.xhtml.php");

/**
 * Display functions
 */
include("../functions/functions.display.php");

/**
 * Limesurvey functions
 */
include("../functions/functions.limesurvey.php");

/**
 * Operator functions
 */
include("../functions/functions.operator.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

$css = array(
"../include/bootstrap/css/bootstrap.min.css", 
"../include/bootstrap/css/bootstrap-theme.min.css",
"../include/font-awesome/css/font-awesome.css",
//"../include/iCheck/skins/square/blue.css",
"../css/custom.css"
			);
$js_head = array(
"../include/jquery/jquery.min.js",
"../include/bootstrap/js/bootstrap.min.js"
				);
$js_foot = array(
"../js/bootstrap-confirmation.js",
"../include/iCheck/icheck.min.js",
"../js/window.js",
"../js/custom.js"
				);

global $db;

$operator_id = get_operator_id();

$case_id = false;
if (isset($_GET['case_id'])) 	$case_id = bigintval($_GET['case_id']);

if (isset($_GET['deidentify-confirm-btn']))
{	
	//remove all sample vars
	$db->StartTrans();

	$sql = "SELECT sample_id
		FROM `case`
		WHERE case_id = $case_id";

	$sample_id = $db->GetOne($sql);

	$sql = "DELETE FROM sample_var
		WHERE sample_id = $sample_id";

	$db->Execute($sql);

	//clear number from sample table
		
	$sql = "UPDATE `sample`
		SET phone = ''
		WHERE sample_id = $sample_id";

	$db->Execute($sql);

	//clear respondent table (firstName,lastName)

	$sql = "UPDATE `respondent`
		SET firstName = '', lastName = ''
		WHERE case_id = $case_id";

	$db->Execute($sql);

	//clear contact phone (phone,description)

	$sql = "UPDATE `contact_phone`
		SET phone = '', description = ''
		WHERE case_id = $case_id";

	$db->Execute($sql);

	$db->CompleteTrans();
}

if (isset($_GET['case_note_id']))
{
	$case_note_id = bigintval($_GET['case_note_id']);

	$sql = "DELETE FROM case_note
		WHERE case_id = '$case_id'
		AND case_note_id = '$case_note_id'";

	$db->Execute($sql);
}

xhtml_head(T_("Assign outcomes to cases"),true,$css,$js_head);

?>

<form action="" method="get" class="form-inline">
<div class="form-group pull-left">
<label for="case_id" class="control-label  text-right"><?php  echo T_("Case id:"); ?> </label>
<input type="text" class="form-control" name="case_id" id="case_id" value="<?php  echo $case_id; ?>" placeholder="<?php  echo T_("Enter a case id"); ?>"></input>
<button type="submit" class=" btn btn-default " name="case_form" value=""><i class="fa fa-check-square-o fa-fw fa-lg text-primary"></i>&ensp;<?php  echo T_("Select case"); ?></button>
</div>
</form>

<?php 
$sql = "SELECT c.case_id as value, c.case_id as description, CASE WHEN c.case_id = '$case_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM  `case` AS c,  `outcome` AS o,  `questionnaire` AS q,  `sample` AS s,  `sample_import` AS si
	LEFT JOIN (questionnaire_sample_quota as qsq) on (si.sample_import_id  = qsq.sample_import_id)
	LEFT JOIN (questionnaire_sample_quota_row as qsqr) on (si.sample_import_id = qsqr.sample_import_id)
	WHERE c.current_outcome_id = o.outcome_id
	AND q.questionnaire_id = c.questionnaire_id
	AND s.sample_id = c.sample_id
	AND s.import_id = si.sample_import_id
	AND q.enabled = 1
	AND si.enabled =1
	AND (qsq.quota_reached IS NULL OR qsq.quota_reached != 1 )
	AND (qsqr.quota_reached IS NULL OR qsqr.quota_reached != 1)
	AND o.outcome_type_id =2
	GROUP BY c.case_id ORDER BY c.case_id ASC";

$rs = $db->GetAll($sql);

if (!empty($rs))
{
	print "<form class='form-inline '><div class='form-group'>&emsp;<b>" . T_("or") . "</b>&emsp;<label for='case' class='control-label text-right text-warning' >". T_("Select case from list of cases referred to the supervisor:") . "&emsp;</label>";
	display_chooser($rs,"case","case_id",true,false,true,false);
	print "</div></form>";
}
?>
	<div class="modal fade" id="call_outcome_change" tabindex="-1" role="dialog" aria-labelledby="calloutcome" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h3 class="modal-title" id="calloutcome"><?php echo T_("Set an outcome for this call");?></h3>
				</div><form method="get" action="?" class="form-inline ">
				<div class="modal-body">
			<?php 	
			if (isset($_GET['call_id'])){ $call_id = bigintval($_GET['call_id']); 
			$sql = "SELECT o.outcome_id as value,description, CASE WHEN o.outcome_id = c.outcome_id THEN 'selected=\'selected\'' ELSE '' END AS selected
				FROM outcome as o, `call` as c
				WHERE c.call_id = '$call_id'";
			$rs2 = $db->GetAll($sql);
			translate_array($rs2,array("description"));
			display_chooser($rs2, "set_outcome_id", "set_outcome_id",true,false,false,false);	?>
					<input type="hidden" name="call_id" value="<?php  echo $call_id;?>"/><input type="hidden" name="case_id" value="<?php  echo $case_id;?>"/> <?php } ?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php  echo T_("Cancel"); ?></button>
					<button  type="submit" name="submit" class="submitclass btn btn-primary"><?php  echo T_("Set outcome"); ?></button>
				</div></form>
			</div>
		</div>
	</div>

<div class="modal fade" id="deidentify-confirm" name="deidentify-confirm" tabindex="-1" role="dialog" aria-labelledby="deidentify-confirm" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
		<div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          <h4 class="modal-title text-danger " ><?php echo T_("WARNING !");?></h4>
        </div>
		<div class="modal-body">
			<p><?php echo T_("Are you sure you want to delete") . "&ensp;" . T_("Sample ID") . "&ensp;<b class='text-danger'>" . "</b>?";?></p>		
		</div>
	  <div class="modal-footer">
        <button type="button" class="btn btn-default pull-left" data-dismiss="modal"><?php echo T_("NOOOO...");?></button>
        <button  class="btn btn-danger submitclass" href=" " type="submit" name="deidentify-confirm-btn" ><?php echo T_("Yes"),",&ensp;",T_("Delete");?></button>
      </div>
    </div>
  </div>
</div>

<?php
//name="deidentify"

if (isset($_GET['call_id']))
{
	$call_id = bigintval($_GET['call_id']);
	if (isset($_GET['set_outcome_id']))
	{
		$outcome_id = bigintval($_GET['set_outcome_id']);

		if ($outcome_id > 0)
		{
			$sql = "UPDATE `call`
				SET outcome_id = '$outcome_id'
				WHERE call_id = '$call_id'";

			$db->Execute($sql);
		}
	}
	else
	{
		print "<script type='text/javascript' > $(function() { $('#call_outcome_change').modal('show'); }); </script>";
	}
	
	//unset($_GET['call_id']);
}
if ($case_id != false)
{
	if (isset($_GET['note']))
	{
		$note = $db->qstr($_GET['note']);
		
		$sql = "INSERT INTO `case_note` (case_note_id,case_id,operator_id,note,datetime)
			VALUES (NULL,'$case_id','$operator_id',$note,CONVERT_TZ(NOW(),'System','UTC'))";
		$db->Execute($sql);
	}

	if (isset($_GET['outcome_id']))
	{
		$outcome_id = bigintval($_GET['outcome_id']);

		if ($outcome_id > 0)
		{
			$sql = "UPDATE `case`
				SET current_outcome_id = $outcome_id
				WHERE case_id = '$case_id'";
	
			$db->Execute($sql);
		}
	}

	if (isset($_GET['operator_id']))
	{
		$case_operator_id = bigintval($_GET['operator_id']);

		if ($case_operator_id == 0)
		{
			//clear the next case if set to no operator
			$sql = "UPDATE `operator`
				SET next_case_id = NULL
				WHERE next_case_id = '$case_id'";
		}
		else
		{
			$sql = "UPDATE `operator`
				SET next_case_id = '$case_id'
				WHERE operator_id = '$case_operator_id'";	
		}

		$db->Execute($sql);
	}

	if (isset($_GET['submitag']))
	{
		$db->StartTrans();

		$sql = "DELETE FROM case_availability
			WHERE case_id = '$case_id'";

		$db->Execute($sql);

		foreach($_GET as $key => $val)
		{
			if (substr($key,0,2) == "ag")
			{
				$sql = "INSERT INTO case_availability (case_id,availability_group_id)
					VALUES ($case_id,'$val')";
				$db->Execute($sql);						
			}
		}
		$db->CompleteTrans();
	}
	
	$sql = "SELECT o.description,o.outcome_id, q.description as qd, si.description as sd, s.import_id as sid
		FROM `case` as c, `outcome` as o, questionnaire as q, sample as s, sample_import as si
		WHERE c.case_id = '$case_id'
		AND q.questionnaire_id = c.questionnaire_id
		AND s.sample_id = c.sample_id
		AND si.sample_import_id = s.import_id
		AND c.current_outcome_id = o.outcome_id";

	$rs = $db->GetRow($sql);

	if (!empty($rs))
	{
		print "	<div class='clearfix'></div>
				<h3 class='pull-right'>&emsp;" . T_("Project") . ": <span class='text-primary'>{$rs['qd']}&emsp;</span></h3>
				<h3 class='pull-right'>&emsp;" . T_("Sample") . ": <span class='text-primary'>{$rs['sd']}&emsp;</span></h3>
				<h2 class=''>". T_("Current outcome:") ."<span class='text-info'> " . T_($rs['description']) . "</span></h2>";

		$current_outcome_id = $rs['outcome_id'];
		$sid = $rs['sid'];

		// view sample details
		print "<div class='panel-body'><h4 class=''><i class='fa fa-book'></i>&emsp;" . T_("Sample details")."</h4>";
		
		$sql = "SELECT sv.sample_id, MIN(c.case_id) as case_id , MIN(s.Time_zone_name) as Time_zone_name,
			MIN(TIME_FORMAT(CONVERT_TZ(NOW(),'System',s.Time_zone_name),'". TIME_FORMAT ."')) as time
			FROM sample_var AS sv
			LEFT JOIN (`case` AS c , sample as s) ON ( c.sample_id = sv.sample_id AND s.sample_id = c.sample_id ) WHERE c.case_id = '$case_id'
			GROUP BY sv.sample_id";
		$r = $db->GetAll($sql);
		if ($r){
		$fnames = array("sample_id", "Time_zone_name", "time");
		$fdesc = array(T_("Sample id"),T_("Timezone"),T_("Time NOW"));
		$varr= array();
		$sql = "SELECT var,var_id
				FROM sample_import_var_restrict
				WHERE sample_import_id = $sid AND type IN (2,3,6,7)
				ORDER by var DESC";
		$rs = $db->GetAll($sql);

			foreach($rs as $rsw)
			{
				$fnames[] = $rsw['var_id'];
				$fdesc[] = $rsw['var'];
				$varr[] = $rsw['var_id']; //array for valid var_id's
			}
			$varr= implode(",",$varr);
			foreach($r as &$rw)
			{
				$sql = "SELECT var_id,val
					FROM sample_var
					WHERE sample_id = {$rw['sample_id']} AND var_id IN ($varr)";
				$rs = $db->GetAll($sql);
				foreach($rs as $rsw){
					$rw[$rsw['var_id']] = $rsw['val'];
				}
			}
		
			xhtml_table($r,$fnames,$fdesc,"tclass");
		}else{
			print "<p class='alert text-danger'>" . T_("No sample data for this case") . "</p>";
		}
		print "</div>";
		
		//View appointments 
		print "<div class='panel-body'><h4 class=''><i class='fa fa-clock-o'></i>&emsp;" . T_("Appointments")."</h4>";

		$sql = "SELECT  
		MIN(CONVERT_TZ(a.start,'UTC',co.Time_zone_name)) as start,
		MIN(CONVERT_TZ(a.end,'UTC',co.Time_zone_name)) as end, 
		MIN(CONCAT(r.firstName,' ', r.lastName)) as resp,
		MIN(IFNULL(ou.description,'" . T_("Not yet called") . "')) as outcome, 
		MIN(CONCAT (oo.firstName,' ', oo.lastName)) as makerName, 
		MIN(CONCAT (ooo.firstName,' ', ooo.lastName)) as callerName,
		MIN(CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>')) as case_id,
		MIN(CONCAT('&emsp;<a href=\'\' data-toggle=\'confirmation\' data-title=\'" . TQ_("ARE YOU SURE?") . "\' data-btnOkLabel=\'" . TQ_("Yes") . "\' data-btnCancelLabel=\'" . TQ_("No") . "\' data-placement=\'left\' data-href=\'displayappointments.php?case_id=', c.case_id, '&amp;appointment_id=', a.appointment_id,'&amp;delete=delete\'><i class=\'fa fa-trash fa-lg text-danger\' data-toggle=\'tooltip\' title=\'" . TQ_("Delete") . "\'></i></a>&emsp;')) as link,
		MIN(CONCAT('&emsp;<a href=\'displayappointments.php?case_id=', c.case_id, '&amp;appointment_id=', a.appointment_id, '\' data-toggle=\'tooltip\' title=\'" . TQ_("Edit") . "\'><i class=\'fa fa-edit fa-lg\'></i></a>&emsp;')) as edit
		FROM appointment as a
		JOIN (`case` as c, respondent as r, questionnaire as q, operator as oo, call_attempt as cc, operator as co) on (a.case_id = c.case_id and a.respondent_id = r.respondent_id and q.questionnaire_id = c.questionnaire_id and a.call_attempt_id = cc.call_attempt_id and cc.operator_id =  oo.operator_id)
		LEFT JOIN (`call` as ca, outcome as ou, operator as ooo) ON (ca.call_id = a.completed_call_id and ou.outcome_id = ca.outcome_id and ca.operator_id = ooo.operator_id)
		WHERE c.case_id = '$case_id'
		AND co.operator_id = '$operator_id'
		GROUP BY a.appointment_id
		ORDER BY a.start ASC";
	
		$rs = $db->GetAll($sql);
		
		if (!empty($rs))
		{
			translate_array($rs,array("outcome"));
			xhtml_table($rs,array("start","end","edit","makerName","resp","outcome","callerName","link"),array(T_("Start"),T_("End"),"&emsp;<i class='fa fa-edit fa-lg'></i>&emsp;",T_("Operator"),T_("Respondent"),T_("Current outcome"),T_("Operator who called"),"&emsp;<i class='fa fa-trash fa-lg'></i>&emsp;"),"table-hover table-bordered table-condensed col-sm-10");
		}
		else
			print "<div class='alert text-danger col-sm-6' role='alert'><b>" . T_("No appointments for this case") . "</b></div>";
		
// * disable appointment creation if no sample_id
		if (isset($r[0]['sample_id'])){
			$rtz= $r[0]['Time_zone_name'];
			print "&emsp;<a href='displayappointments.php?case_id=$case_id&rtz=$rtz&new=new' class='btn btn-default'><i class='fa fa-clock-o fa-lg'></i>&emsp;" . T_("Create appointment") . "</a>"; }

		print "</div>";


		//view calls and outcomes
		$sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.start,'UTC',r.Time_zone_name),'".DATE_TIME_FORMAT."') as start,CONVERT_TZ(c.end,'UTC',r.Time_zone_name) as end, CONCAT(op.firstName,' ',op.lastName) as firstName, o.description as des, CONCAT('&emsp;<a href=\'?case_id=$case_id&amp;call_id=', c.call_id, '\' data-toggle=\'tooltip\' title=\'" . TQ_("Change outcome") . "\'><i class=\'fa fa-edit fa-lg \'></i></a>') as link, cp.phone as phone
			FROM `call` as c
			JOIN (operator as op, outcome as o, respondent as r, contact_phone as cp) on (c.operator_id = op.operator_id and c.outcome_id = o.outcome_id and r.respondent_id = c.respondent_id and cp.contact_phone_id = c.contact_phone_id)
			WHERE c.case_id = '$case_id'
			ORDER BY c.start DESC";
		$rs = $db->GetAll($sql);

		print "<div class='panel-body col-sm-6'><h4 class=''><i class='fa fa-phone'></i>&emsp;" . T_("Call list")."</h4>";
		if (empty($rs))
			print "<div class='alert text-info' role='alert'><h4>" . T_("No calls made") . "</h4></div>";
		else
		{
			translate_array($rs,array("des"));
			xhtml_table($rs,array("start","phone","firstName","des","link"),array(T_("Date/Time"),T_("Phone number"),T_("Operator"),T_("Outcome"),"&emsp;<i class='fa fa-edit fa-lg'></i>&emsp;"));
		}
		print "</div>";

		//view notes
		$sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.datetime,'UTC',op.Time_zone_name),'".DATE_TIME_FORMAT."') as time, CONCAT(op.firstName,' ',op.lastName) as firstName, c.note as note,  CONCAT('<a href=\'\' data-toggle=\'confirmation\' data-title=\'" . TQ_("ARE YOU SURE?") . "\' data-btnOkLabel=\'" . TQ_("Yes") . "\' data-btnCancelLabel=\'" . TQ_("No") . "\' data-placement=\'left\' data-href=\'?case_id=$case_id&amp;case_note_id=', c.case_note_id, '\' class=\'btn  center-block\' ><i class=\'fa fa-trash fa-lg text-danger\' data-toggle=\'tooltip\' title=\'" . TQ_("Delete") . "\'></i></a>') as link 
			FROM `case_note` as c
			JOIN (operator as op) on (c.operator_id = op.operator_id)
			WHERE c.case_id = '$case_id'
			ORDER BY c.datetime DESC";
		$rs = $db->GetAll($sql);

		print "<div class='panel-body col-sm-6'><h4 class=''><i class='fa fa-file-text'></i>&emsp;" . T_("Case notes")."</h4>";

		if (empty($rs))
			print "<p class='alert text-info'>" . T_("No notes") . "</p>";
		else {
			xhtml_table($rs,array("time","firstName","note","link"),array(T_("Date/Time"),T_("Operator"),T_("Note"),"&emsp;<i class='fa fa-trash fa-lg'></i>&emsp;"));
			print "<br/>";
			}	
		//add a note
		?>
		<form method="get" action="?" class="form-inline" >	
		<input type="hidden" name="case_id" value="<?php  echo $case_id;?>"/>
		<input type="text" class="textclass form-control" name="note" id="note" style="width: 70%;"/>&ensp;
		<button class="submitclass btn btn-default" type="submit" name="submit"><i class="fa fa-file-text"></i>&emsp;<?php  echo T_("Add note"); ?></button> 
		</form>
		<?php 
		print "</div>";
		
		//view timeslots
		$sql = "SELECT count(*)
            FROM questionnaire_timeslot as q, `case` as c
            WHERE c.case_id = $case_id
            AND c.questionnaire_id = q.questionnaire_id";

		if ($db->GetOne($sql) >= 1)
		{
		$sql = "SELECT ag.description, (SELECT COUNT(*) FROM availability as a, `call_attempt` as ca 
			WHERE ca.case_id = c.case_id 
			AND a.availability_group_id = ag.availability_group_id
			AND (a.day_of_week = DAYOFWEEK(CONVERT_TZ(ca.start,'UTC',s.Time_zone_name)) 
			AND TIME(CONVERT_TZ(ca.start, 'UTC' , s.Time_zone_name)) >= a.start 
			AND TIME(CONVERT_TZ(ca.start, 'UTC' , s.Time_zone_name)) <= a.end))  as cou
              FROM availability_group as ag, `case` as c, `questionnaire_timeslot` as qt, sample as s
              WHERE c.case_id = '$case_id'
              AND s.sample_id = c.sample_id
              AND qt.questionnaire_id = c.questionnaire_id AND ag.availability_group_id = qt.availability_group_id";
		if ( array("cou") >=1){
			print "<div class='panel-body col-sm-6'><h4 class=''><i class='fa fa-list'></i>&emsp;" . T_("Call attempts by timeslot") . "</h4>";
			xhtml_table($db->GetAll($sql),array('description','cou'),array(T_("Time slot"),T_("Call attempts")));//,"tclass",false,array("cou")
			print "</div>";
			}
		} else { print "<b class=' text-info col-sm-6'>" . T_("Time slots NOT defined") . "</b>";	}

	
		print "<div class='clearfix '></div><div class='col-sm-6'>";
		
		if (isset($r[0]['sample_id'])){  //if sample data exists assign this to an operator for their next case
		
		print "<div class='panel-body'><h4><i class='fa fa-link'></i>&emsp;" . T_("Assign this case to operator (will appear as next case for them)") . "</h4>";
		?>
		<form method="get" action="?" class="form-inline">
		<?php               
			$sql = "SELECT operator_id as value,CONCAT(firstName,' ', lastName) as description, CASE WHEN next_case_id = '$case_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
				FROM operator
				WHERE enabled = 1";
	
			$rs3 = $db->GetAll($sql);
			display_chooser($rs3, "operator_id", "operator_id",true,false,false,false);
		?>
		<input type="hidden" name="case_id" value="<?php echo $case_id;?>"/>
		<button class="submitclass btn btn-default" type="submit" name="submit" ><i class="fa fa-link fa-lg"></i>&emsp;<?php echo T_("Assign this case to operator"); ?></button>
		</form></div>
		<?php 
		}
		
		//Modify the case in Limesurvey
		$sid = get_lime_sid($case_id);
    $id = get_lime_id($case_id);
    $url = get_lime_url($case_id);
		print "<div class='panel-body'>";
		if ($id)
			print "<h4><a href='" . $url  . "/admin/dataentry/sa/editdata/subaction/edit/surveyid/$sid/id/$id' class='btn btn-default btn-lime'><i class='fa fa-lemon-o fa-lg'></i>&emsp;" . T_("Modify responses for this case") . "</a></h4>";
		else
			print "<div class='alert text-danger' role='alert'>" . T_("Case not yet started in Limesurvey") .  "</div>";
		print "</div></div>";
		
		if (isset($r[0]['sample_id'])){   // if sample data exists  view availability
		
		print "<div class='panel-body col-sm-6'><h4 class=''><i class='fa fa-calendar'></i>&emsp;" . T_("Availability groups") . "</h4>";
		if (is_using_availability($case_id))
		{
			//List all availability group items and whether selected or not (all selected by default unless >= 1 availability group is in use for this case
			$sql = "SELECT qa.availability_group_id,ag.description,ca.availability_group_id as selected_group_id
				FROM `case` as c
				JOIN questionnaire_availability AS qa ON (qa.questionnaire_id = c.questionnaire_id)
				JOIN availability_group AS ag ON (ag.availability_group_id = qa.availability_group_id)
				LEFT JOIN case_availability AS ca ON (ca.availability_group_id = qa.availability_group_id and ca.case_id = c.case_id)
				WHERE c.case_id = '$case_id'";

			$rs = $db->GetAll($sql);

			//Display all availability groups as checkboxes
			print "<form action='?' method='get' class='form-horizontal '>";
			print "<h5 class=''>" . T_("Select groups to limit availability (Selecting none means always available)") .  "</h5><div class='col-sm-6'>";
			foreach ($rs as $g)
			{
				$checked = "";

				//if ($allselected || $g['availability_group_id'] == $g['selected_group_id'])
				if ($g['availability_group_id'] == $g['selected_group_id'])
					$checked = "checked='checked'";
				
				print "&ensp;<input type='checkbox' name='ag{$g['availability_group_id']}' id='ag{$g['availability_group_id']}'	value='{$g['availability_group_id']}' $checked />&ensp; <label class='control-label' for='ag{$g['availability_group_id']}'>{$g['description']}</label></br>";
			}
		?>	</div>
			<input type="hidden" name="case_id" value="<?php echo $case_id;?>"/>
			<button class="submitclass btn btn-default pull-right" type="submit" name="submitag"><i class="fa fa-calendar fa-lg"></i>&emsp;<?php echo T_("Update case availability");?></button>
			</form>
		<?php 
		}
		else
		{
			print "<div class='alert text-info' role='alert'><h5>" . T_("Availability groups not defined for this questionnaire") . "</h5></div>";
		}
	  	print "</div>"; }

		//set an outcome
		print "<div class='clearfix '></div><div class='panel-body col-sm-6 '><h4><i class='fa fa-dot-circle-o'></i>&emsp;" . T_("Set a case outcome") . "</h4>";
		?>
		<form method="get" action="?" class="form-inline">
		<?php               
			$sql = "SELECT outcome_id as value,description, CASE WHEN outcome_id = '$current_outcome_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
				FROM outcome";
	
			$rs2 = $db->GetAll($sql);
			translate_array($rs2,array("description"));
			display_chooser($rs2, "outcome_id", "outcome_id",true,false,false,false);
		?>
		<input type="hidden" name="case_id" value="<?php  echo $case_id;?>" /><br/><br/>
		<button class="submitclass btn btn-primary" type="submit" name="submit" ><i class="fa fa-dot-circle-o fa-lg"></i>&emsp;<?php  echo T_("Set outcome"); ?></button>
		</form>
		<?php 
		print "</div>";	
		
		if (isset($r[0]['sample_id'])){   // if sample data exists  deidentify record
		print "<div class='panel-body col-sm-6 pull-right'><h4 class ='text-danger'><i class='fa fa-trash-o fa-lg'></i>&emsp;" . T_("Deidentify") . "</h4>";
		print "<div class='well'>" . T_("Remove all sample details and contact numbers from this case") . "</div>";
		?>
		<form method="get" action="?">
		<input type="hidden" name="case_id" value="<?php echo $case_id;?>"/>
		<button class=" btn btn-danger" name="deidentify" id="deidentify" data-toggle="confirmation" ><i class="fa fa-trash fa-lg"></i>&emsp;<?php echo T_("Deidentify");?></button>
		</form></div>
		<?php }
	}
	else
	{
		print "<h3 class='alert alert-warning'>" . T_("Case does not exist") . "</h3>";
	}
}
xhtml_foot($js_foot);//  deidentify  data-toggle="modal" type="submit"submitclass
?>

<script type="text/javascript">
$('[data-toggle="confirmation"]').confirmation();
$("#deidentify").click(function(){
		$("#deidentify-confirm").modal('show');});
</script>

<script type="text/javascript">
$('input').iCheck({
	/* checkboxClass: 'icheckbox_square-blue', */
	/* increaseArea: '15%' */
checkboxClass: 'fa fa-lg', // text-primary
checkedCheckboxClass: 'fa-check-square-o text-primary',
uncheckedCheckboxClass: 'fa-square-o'	
});
</script>
