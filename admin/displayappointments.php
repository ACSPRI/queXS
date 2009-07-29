<?php
/**
 * Display appointments
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

//Create a new blank appointment and then edit it
if (isset($_GET['new']) && isset($_GET['case_id']))
{
	$case_id = bigintval($_GET['case_id']);
	
	$db->StartTrans();
	
	//First create a call attempt
	$operator_id = get_operator_id();

	if ($operator_id == false) die();

	//get the first respondent id for this case
	$sql = "SELECT respondent_id
		FROM respondent
		WHERE case_id = '$case_id'";

	$rs = $db->GetRow($sql);

	$respondent_id = $rs['respondent_id'];

	//get the first contact_phone_id for this case
	$sql = "SELECT contact_phone_id
		FROM contact_phone
		WHERE case_id = '$case_id'";

	$rs = $db->GetRow($sql);

	$contact_phone_id = $rs['contact_phone_id'];

	$sql = "INSERT INTO call_attempt (call_attempt_id,case_id,operator_id,respondent_id,start,end)
		VALUES (NULL,$case_id,$operator_id,$respondent_id,CONVERT_TZ(NOW(),'System','UTC'),CONVERT_TZ(NOW(),'System','UTC'))";

	$db->Execute($sql);

	$call_attempt_id = $db->Insert_ID();

	
      $sql = "INSERT INTO `appointment`
                (appointment_id,case_id,contact_phone_id,call_attempt_id,start,end,require_operator_id,respondent_id,completed_call_id)
                VALUES (NULL,'$case_id','$contact_phone_id','$call_attempt_id',CONVERT_TZ(NOW() + INTERVAL 1 DAY,'System','UTC'),CONVERT_TZ(NOW() + INTERVAL 1 DAY,'System','UTC'),NULL,$respondent_id,NULL)";

        $db->Execute($sql);


	$appointment_id = $db->Insert_ID();

	$db->CompleteTrans();

	$_GET['appointment_id'] = $appointment_id;
}


//update appointment
if (isset($_GET['start']) && isset($_GET['appointment_id']))
{
	$appointment_id = bigintval($_GET['appointment_id']);
	$start = $db->qstr($_GET['start']);
	$end = $db->qstr($_GET['end']);
	$contact_phone_id = bigintval($_GET['contact_phone_id']);
	$respondent_id = bigintval($_GET['respondent_id']);
	
	//Edit this appointment in the database
	$sql = "UPDATE appointment as a, respondent as r
		SET a.start = CONVERT_TZ($start,r.Time_zone_name,'UTC'), a.end = CONVERT_TZ($end,r.Time_zone_name,'UTC'), a.contact_phone_id = $contact_phone_id, a.respondent_id = $respondent_id
		WHERE a.appointment_id = $appointment_id
		AND r.respondent_id = $respondent_id";

	$db->Execute($sql);
}
	


if (isset($_GET['appointment_id']) && isset($_GET['case_id']))
{
	$appointment_id = bigintval($_GET['appointment_id']);
	$case_id = bigintval($_GET['case_id']);

	if (isset($_GET['delete']))
	{
		$sql = "DELETE FROM appointment
			WHERE appointment_id = '$appointment_id'";
	
		$db->Execute($sql);
	
		xhtml_head(T_("Now modify case outcome"));
	
		print "<p>" . T_("The appointment has been deleted. Now you must modify the case outcome") . "</p>";
		print "<p><a href='supervisor.php?case_id=$case_id'>" . T_("Modify case outcome") . "</a></p>";
	}
	else
	{
		//Display an edit form
		xhtml_head(T_("Edit appointment"));
		
		$sql = "SELECT a.contact_phone_id,a.call_attempt_id,CONVERT_TZ(a.start,'UTC',r.Time_zone_name) as start,CONVERT_TZ(a.end,'UTC',r.Time_zone_name) as end,a.respondent_id
			FROM appointment as a, respondent as r
			WHERE a.appointment_id = '$appointment_id'
			AND a.case_id = '$case_id'
			AND r.respondent_id = a.respondent_id";

		$rs = $db->GetRow($sql);

		if (!empty($rs))
		{
			$respondent_id = $rs['respondent_id'];
			$contact_phone_id = $rs['contact_phone_id'];
			$start = $rs['start'];
			$end = $rs['end'];

			print "<p><form action='?' method='get'>";
			print "<div><label for='respondent_id'>" . T_("Respondent") . "</label>";
			display_chooser($db->GetAll("SELECT 	respondent_id as value,	firstname as description, CASE when respondent_id = '$respondent_id' THEN 'selected=\'selected\'' ELSE '' END as selected
							FROM respondent
							WHERE case_id = '$case_id'"),"respondent_id","respondent_id",false,false,false,false);
			

			print "</div><div><label for='contact_phone_id'>" . T_("Contact phone") . "</label>";
			display_chooser($db->GetAll("SELECT 	contact_phone_id as value,
								phone as description,
								CASE when contact_phone_id = '$contact_phone_id' THEN 'selected=\'selected\'' ELSE '' END as selected
							FROM contact_phone
							WHERE case_id = '$case_id'"),
							"contact_phone_id","contact_phone_id",false,false,false,false);
			
			print "</div><div><label for='start'>" . T_("Start time") . "</label><input type='text' value='$start' id='start' name='start'/></div>";
			print "<div><label for='end'>" . T_("End time") . "</label><input type='text' value='$end' id='end' name='end'/></div>";
			print "<input type='hidden' value='$appointment_id' id='appointment_id' name='appointment_id'/>";
			print "<div><input type='submit' value='" . T_("Edit appointment") . "'/></div>";

			print "</form></p>";
			print "<p><a href='?'>" . T_("Cancel edit") . "</a></p>";
		}
	}
}

else
{

	$operator_id = get_operator_id();

	xhtml_head(T_("Display Appointments"),true,array("../css/table.css"));
	
	print "<h1>" . T_("Appointments") . "</h1><h2>" . T_("All appointments (with times displayed in your time zone)") . "</h2>";
	
	$sql = "SELECT q.description, CONVERT_TZ(a.start,'UTC',o.Time_zone_name) as start, CONVERT_TZ(a.end,'UTC',o.Time_zone_name) as end, r.firstName, r.lastName, IFNULL(ou.description,'" . T_("Not yet called") . "') as outcome, oo.firstName as makerName, ooo.firstName as callerName, CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>') as case_id, CONCAT('<a href=\'?case_id=', c.case_id, '&amp;appointment_id=', a.appointment_id, '&amp;delete=delete\'>". T_("Delete") . "</a>') as link, CONCAT('<a href=\'?case_id=', c.case_id, '&amp;appointment_id=', a.appointment_id, '\'>". T_("Edit") . "</a>') as edit

		FROM appointment as a
		JOIN (`case` as c, respondent as r, questionnaire as q, operator as o, operator as oo, call_attempt as cc) on (a.case_id = c.case_id and a.respondent_id = r.respondent_id and q.questionnaire_id = c.questionnaire_id and o.operator_id = '$operator_id' and a.call_attempt_id = cc.call_attempt_id and cc.operator_id =  oo.operator_id)
		LEFT JOIN (`call` as ca, outcome as ou, operator as ooo) ON (ca.call_id = a.completed_call_id and ou.outcome_id = ca.outcome_id and ca.operator_id = ooo.operator_id)
		WHERE a.end >= CONVERT_TZ(NOW(),'System','UTC')
		ORDER BY a.start ASC";
	
	$rs = $db->GetAll($sql);
	
	if (!empty($rs))
	{
		translate_array($rs,array("outcome"));
		xhtml_table($rs,array("description","case_id","start","end","makerName","firstName","lastName","outcome","callerName","link","edit"),array(T_("Questionnaire"),T_("Case ID"),T_("Start"),T_("End"),T_("Operator Name"),T_("Respondent Name"),T_("Surname"),T_("Current outcome"),T_("Operator who called"),T_("Delete"),T_("Edit")));
	}
	else
		print "<p>" . T_("No appointments in the future") . "</p>";
	
}
xhtml_foot();

?>
