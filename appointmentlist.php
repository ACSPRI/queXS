<?
/**
 * Display appointments for this case and their outcomes if any
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
 * @subpackage user
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include ("config.inc.php");

/**
 * Database file
 */
include ("db.inc.php");

/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include("functions/functions.operator.php");


xhtml_head(T_("Appointment List"),true,array("css/table.css"),false,false,15);

//List the case appointment
// display in respondent time so that the operator will be able to
// quote verbatim to the respondent if necessary

$db->StartTrans();

$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);

if ($case_id)
{
	$sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.start,'UTC',r.Time_zone_name),'".DATE_TIME_FORMAT."') as start,DATE_FORMAT(CONVERT_TZ(c.end,'UTC',r.Time_zone_name),'".TIME_FORMAT."') as end, c.completed_call_id, IFNULL(ou.firstName,'" . T_("Not yet called") . "') as firstName, CONCAT(r.firstName, ' ', r.lastName) as respname, IFNULL(o.description,'" . T_("Not yet called") . "') as des
		FROM `appointment` as c
		JOIN respondent as r on  (r.respondent_id = c.respondent_id)
		LEFT JOIN (`call` as ca, outcome as o, operator as ou) on (ca.call_id = c.completed_call_id and ca.outcome_id = o.outcome_id and ou.operator_id = ca.operator_id)
		WHERE c.case_id = '$case_id'
		ORDER BY c.start DESC";
}
else
{
	$sql = "SELECT q.description, DATE_FORMAT(CONVERT_TZ(c.start,'UTC',r.Time_zone_name),'".DATE_TIME_FORMAT."') as start,DATE_FORMAT(CONVERT_TZ(c.end,'UTC',r.Time_zone_name),'".TIME_FORMAT."') as end, c.completed_call_id, IFNULL(ou.firstName,'" . T_("Not yet called") . "') as firstName, r.firstName as rname, r.lastName as rsname, IFNULL(o.description,'" . T_("Not yet called") . "') as des
		FROM `appointment` as c
		JOIN respondent as r on  (r.respondent_id = c.respondent_id)
		JOIN `case` as cas on (cas.case_id = c.case_id)
		LEFT JOIN (`call` as ca, outcome as o, operator as ou) on (ca.call_id = c.completed_call_id and ca.outcome_id = o.outcome_id and ou.operator_id = ca.operator_id)
		WHERE oq.operator_id = '$operator_id'
		AND ca.questionnaire_id = oq.questionnaire_id
		ORDER BY c.start DESC";
}
$rs = $db->GetAll($sql);
if (empty($rs))
{
	if ($case_id)
		print "<p>" . T_("No appointments made") . "</p>";
	else
		print "<p>" . T_("No future appointments scheduled") . "</p>";
}
else
{
	translate_array($rs,array("des"));
	xhtml_table($rs,array("start","end","respname","des","firstName"),array(T_("Start"),T_("End"),T_("Respondent"),T_("Outcome"),T_("Operator")));
}
		

xhtml_foot();
$db->CompleteTrans();

?>
