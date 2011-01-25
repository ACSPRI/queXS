<?
/**
 * Display the shifts for this case, if no case, show all shifts that the operator is assigned to
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

$js = false;
if (AUTO_LOGOUT_MINUTES !== false)
        $js = array("include/jquery-ui/js/jquery-1.4.2.min.js","js/childnap.js");


xhtml_head(T_("Shift List"),true,array("css/table.css"),$js,false,600);

//List the shifts
// display in operator time

$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);

if ($case_id)
{
	global $db;
	$sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.start,'UTC',op.Time_zone_name),'".DATE_TIME_FORMAT."') as start,DATE_FORMAT(CONVERT_TZ(c.end,'UTC',op.Time_zone_name),'".TIME_FORMAT."') as end
		FROM `shift` as c, `case` as ca, `operator` as op
		WHERE ca.case_id = '$case_id'
		AND op.operator_id = '$operator_id'
		AND c.questionnaire_id = ca.questionnaire_id
		AND c.end >= CONVERT_TZ(NOW(),'System','UTC')
		ORDER BY c.start ASC";
	
	
	$rs = $db->GetAll($sql);
	
	if (empty($rs))
		print "<p>" . T_("No shifts for this project") . "</p>";
	else
		xhtml_table($rs,array("start","end"),array(T_("Start"),T_("End")));
}
else
{
	//no case so show all shifts for all projects that I am assigned to
	global $db;
	$sql = "SELECT q.description,DATE_FORMAT(CONVERT_TZ(c.start,'UTC',op.Time_zone_name),'".DATE_TIME_FORMAT."') as start,DATE_FORMAT(CONVERT_TZ(c.end,'UTC',op.Time_zone_name),'".TIME_FORMAT."') as end
		FROM `shift` as c, `operator` as op, `operator_questionnaire` as oq, `questionnaire` as q
		WHERE op.operator_id = '$operator_id'
		AND op.operator_id = oq.operator_id
		AND oq.questionnaire_id = c.questionnaire_id
		AND q.questionnaire_id = oq.questionnaire_id
		AND c.end >= CONVERT_TZ(NOW(),'System','UTC')
		ORDER BY c.start ASC";
	
	
	$rs = $db->GetAll($sql);

	if (empty($rs))
		print "<p>" . T_("No future shifts scheduled") . "</p>";
	else
		xhtml_table($rs,array("description","start","end"),array(T_("Questionnaire"),T_("Start"),T_("End")));

}
	
xhtml_foot();

?>
