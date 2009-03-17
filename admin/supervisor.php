<?
/**
 * View cases referred to the supervisor and add notes/assign outcomes
 *
 *
 *	This file is part of queXS
 *	
 *	queXS is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *	
 *	queXS is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *	
 *	You should have received a copy of the GNU General Public License
 *	along with queXS; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *
 * @author Adam Zammit <adam.zammit@deakin.edu.au>
 * @copyright Deakin University 2007,2008
 * @package queXS
 * @subpackage admin
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 *
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

global $db;

$operator_id = get_operator_id();

$case_id = false;
if (isset($_GET['case_id'])) 	$case_id = bigintval($_GET['case_id']);

xhtml_head(T_("Supervisor functions"),true,array("../css/table.css"),array("../js/window.js"));

print "<h1>" . T_("Enter a case id or select a case from the list below:") . "</h1>";

$sql = "SELECT c.case_id as value, c.case_id as description, CASE WHEN c.case_id = '$case_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM `case` as c, `outcome` as o
	WHERE c.current_outcome_id = o.outcome_id
	AND o.outcome_type_id = 2";

$rs = $db->GetAll($sql);

if (!empty($rs))
{
	print "<div><label for=\"case\">". T_("Select case from list of cases referred to the supervisor:") . " </label>";
	display_chooser($rs,"case","case_id");
	print "</div>";
}

?>
<form action="" method="get">
<p>
<label for="case_id"><? echo T_("Case id:"); ?> </label><input type="text" name="case_id" id="case_id" value="<? echo $case_id; ?>"/>
<input type="submit" name="case_form" value="<? echo T_("Select case"); ?>"/></p>
</form>
<?

if (isset($_GET['call_id']))
{
	$call_id = bigintval($_GET['call_id']);
	if (isset($_GET['set_outcome_id']))
	{
		$outcome_id = bigintval($_GET['set_outcome_id']);
		$sql = "UPDATE `call`
			SET outcome_id = '$outcome_id'
			WHERE call_id = '$call_id'";
		$db->Execute($sql);
	}
	else
	{
		print "<h3>" . T_("Set an outcome for this call") . "</h3>";
	
		?>
		<form method="get" action="?">
		<?              
			$sql = "SELECT o.outcome_id as value,description, CASE WHEN o.outcome_id = c.outcome_id THEN 'selected=\'selected\'' ELSE '' END AS selected
				FROM outcome as o, `call` as c
				WHERE c.call_id = '$call_id'";
		
			$rs2 = $db->GetAll($sql);
			translate_array($rs2,array("description"));
			display_chooser($rs2, "set_outcome_id", "set_outcome_id",true,false,false);
		?>
		<p><input type="hidden" name="call_id" value="<? echo $call_id;?>"/><input type="hidden" name="case_id" value="<? echo $case_id;?>"/><input class="submitclass" type="submit" name="submit" value="<? echo T_("Set outcome"); ?>"/></p>
		</form>
		<?
	}
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

		$sql = "UPDATE `case`
			SET current_outcome_id = $outcome_id
			WHERE case_id = '$case_id'";

		$db->Execute($sql);
	}


	$sql = "SELECT o.description,o.outcome_id, q.description as qd, si.description as sd
		FROM `case` as c, `outcome` as o, questionnaire as q, sample as s, sample_import as si
		WHERE c.case_id = '$case_id'
		AND q.questionnaire_id = c.questionnaire_id
		AND s.sample_id = c.sample_id
		AND si.sample_import_id = s.import_id
		AND c.current_outcome_id = o.outcome_id";

	$rs = $db->GetRow($sql);

	if (!empty($rs))
	{
		print "<h1>" . T_("Project") . ": {$rs['qd']}</h1>";
		print "<h1>" . T_("Sample") . ": {$rs['sd']}</h1>";

		print "<h2>". T_("Current outcome:") ." " . T_($rs['description']) . "</h2>";

		$current_outcome_id = $rs['outcome_id'];


		print "<h3>" . T_("Appointments")."</h3>";

		//View appointments
		$sql = "SELECT q.description, CONVERT_TZ(a.start,'UTC',o.Time_zone_name) as start, CONVERT_TZ(a.end,'UTC',o.Time_zone_name) as end, r.firstName, r.lastName, IFNULL(ou.description,'" . T_("Not yet called") . "') as outcome, oo.firstName as makerName, ooo.firstName as callerName, CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>') as case_id, CONCAT('<a href=\'displayappointments.php?case_id=', c.case_id, '&amp;appointment_id=', a.appointment_id, '&amp;delete=delete\'>". T_("Delete") . "</a>') as link
		FROM appointment as a
		JOIN (`case` as c, respondent as r, questionnaire as q, operator as o, operator as oo, call_attempt as cc) on (a.case_id = c.case_id and a.respondent_id = r.respondent_id and q.questionnaire_id = c.questionnaire_id and a.call_attempt_id = cc.call_attempt_id and cc.operator_id =  oo.operator_id)
		LEFT JOIN (`call` as ca, outcome as ou, operator as ooo) ON (ca.call_id = a.completed_call_id and ou.outcome_id = ca.outcome_id and ca.operator_id = ooo.operator_id)
		WHERE c.case_id = '$case_id'
		GROUP BY a.appointment_id
		ORDER BY a.start ASC";
	
		$rs = $db->GetAll($sql);
	
		if (!empty($rs))
		{
			translate_array($rs,array("outcome"));
			xhtml_table($rs,array("description","start","end","makerName","firstName","lastName","outcome","callerName","link"),array(T_("Questionnaire"),T_("Start"),T_("End"),T_("Operator Name"),T_("Respondent Name"),T_("Surname"),T_("Current outcome"),T_("Operator who called"),T_("Delete")));
		}
		else
			print "<p>" . T_("No appointments for this case") . "</p>";



		//view calls and outcomes
		$sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.start,'UTC',r.Time_zone_name),'".DATE_TIME_FORMAT."') as start,CONVERT_TZ(c.end,'UTC',r.Time_zone_name) as end, op.firstName, op.lastName, o.description as des, CONCAT('<a href=\'?case_id=$case_id&amp;call_id=', c.call_id, '\'>". T_("Edit") . "</a>') as link, cp.phone as phone
			FROM `call` as c
			JOIN (operator as op, outcome as o, respondent as r, contact_phone as cp) on (c.operator_id = op.operator_id and c.outcome_id = o.outcome_id and r.respondent_id = c.respondent_id and cp.contact_phone_id = c.contact_phone_id)
			WHERE c.case_id = '$case_id'
			ORDER BY c.start DESC";
		
		$rs = $db->GetAll($sql);

		print "<h3>" . T_("Call list")."</h3>";
		if (empty($rs))
			print "<p>" . T_("No calls made") . "</p>";
		else
		{
			translate_array($rs,array("des"));
			xhtml_table($rs,array("start","des","phone","link","firstName"),array(T_("Date/Time"),T_("Outcome"),T_("Phone number"),T_("Change outcome"),T_("Operator")));
		}
	
		//view notes
		$sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.datetime,'UTC',op.Time_zone_name),'".DATE_TIME_FORMAT."') as time, op.firstName, op.lastName, c.note as note
				FROM `case_note` as c
				JOIN (operator as op) on (c.operator_id = op.operator_id)
				WHERE c.case_id = '$case_id'
				ORDER BY c.datetime DESC";
			
			
		$rs = $db->GetAll($sql);

		print "<h3>" . T_("Case notes")."</h3>";

		if (empty($rs))
			print "<p>" . T_("No notes") . "</p>";
		else
			xhtml_table($rs,array("time","firstName","note"),array(T_("Date/Time"),T_("Operator"),T_("Note")));
	
	
		//add a note
		?>
		<form method="get" action="?">
			<p>
			<input type="hidden" name="case_id" value="<? echo $case_id;?>"/><input type="text" class="textclass" name="note" id="note"/><input class="submitclass" type="submit" name="submit" value="<? echo T_("Add note"); ?>"/>
			</p>
		</form>
		<?
		
		//Modify the case in Limesurvey

		$sid = get_lime_sid($case_id);
		$id = get_lime_id($case_id);
		if ($id)
			print "<h3><a href='" . LIME_URL . "admin/admin.php?action=dataentry&amp;sid=$sid&amp;subaction=edit&amp;id=$id'>" . T_("Modify responses for this case") . "</a></h3>";
		else
			print "<h3>" . T_("Case not yet started in Limesurvey") .  "</h3>";

		//set an outcome

		print "<h3>" . T_("Set a case outcome") . "</h3>";

		?>
		<form method="get" action="?">
		<?              
			$sql = "SELECT outcome_id as value,description, CASE WHEN outcome_id = '$current_outcome_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
				FROM outcome";
	
			$rs2 = $db->GetAll($sql);
			translate_array($rs2,array("description"));
			display_chooser($rs2, "outcome_id", "outcome_id",true,false,false);
	
		?>
		<p><input type="hidden" name="case_id" value="<? echo $case_id;?>"/><input class="submitclass" type="submit" name="submit" value="<? echo T_("Set outcome"); ?>"/></p>
		</form>
		<?
	}
	else
	{
		print "<h2>" . T_("Case does not exist") . "</h2>";
	}
}
xhtml_foot();


?>
