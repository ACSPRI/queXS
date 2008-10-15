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

if (isset($_GET['appointment_id']) && isset($_GET['case_id']))
{
	$appointment_id = bigintval($_GET['appointment_id']);
	$case_id = bigintval($_GET['case_id']);

	$sql = "DELETE FROM appointment
		WHERE appointment_id = '$appointment_id'";

	$db->Execute($sql);

	xhtml_head(T_("Now modify case outcome"));

	print "<p>" . T_("The appointment has been deleted. Now you must modify the case outcome") . "</p>";
	print "<p><a href='supervisor.php?case_id=$case_id'>" . T_("Modify case outcome") . "</a></p>";

}
else
{

	$operator_id = get_operator_id();

	xhtml_head(T_("Display Appointments"),true,array("../css/table.css"));
	
	print "<h1>" . T_("Appointments") . "</h1><h2>" . T_("All appointments (with times displayed in your time zone)") . "</h2>";
	
	$sql = "SELECT q.description, CONVERT_TZ(a.start,'UTC',o.Time_zone_name) as start, CONVERT_TZ(a.end,'UTC',o.Time_zone_name) as end, r.firstName, r.lastName, IFNULL(ou.description,'" . T_("Not yet called") . "') as outcome, oo.firstName as makerName, ooo.firstName as callerName, CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>') as case_id, CONCAT('<a href=\'?case_id=', c.case_id, '&amp;appointment_id=', a.appointment_id, '\'>". T_("Delete") . "</a>') as link
		FROM appointment as a
		JOIN (`case` as c, respondent as r, questionnaire as q, operator as o, operator as oo, call_attempt as cc) on (a.case_id = c.case_id and a.respondent_id = r.respondent_id and q.questionnaire_id = c.questionnaire_id and o.operator_id = '$operator_id' and a.call_attempt_id = cc.call_attempt_id and cc.operator_id =  oo.operator_id)
		LEFT JOIN (`call` as ca, outcome as ou, operator as ooo) ON (ca.call_id = a.completed_call_id and ou.outcome_id = ca.outcome_id and ca.operator_id = ooo.operator_id)
		WHERE a.end >= CONVERT_TZ(NOW(),'System','UTC')
		ORDER BY a.start ASC";
	
	$rs = $db->GetAll($sql);
	
	if (!empty($rs))
		xhtml_table($rs,array("description","case_id","start","end","makerName","firstName","lastName","outcome","callerName","link"),array(T_("Questionnaire"),T_("Case ID"),T_("Start"),T_("End"),T_("Operator Name"),T_("Respondent Name"),T_("Surname"),T_("Current outcome"),T_("Operator who called"),T_("Delete")));
	else
		print "<p>" . T_("No appointments in the future") . "</p>";
	
}
xhtml_foot();

?>

