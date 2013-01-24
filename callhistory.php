<?php 
/**
 * Display a list of calls and outcomes for this operator over time
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

xhtml_head(T_("Case History List"),true,array("css/table.css"),$js,false,60);

//List the case call history
// display in respondent time so that the operator will be able to
// quote verbatim to the respondent if necessary

$operator_id = get_operator_id();

if ($operator_id)
{
	global $db;
	
	$sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.start,'UTC',op.Time_zone_name),'".DATE_TIME_FORMAT."') as start,CONVERT_TZ(c.end,'UTC',op.Time_zone_name) as end, o.description as des, c.case_id as case_id, r.firstName as firstName
		FROM `call` as c
		JOIN (operator as op, outcome as o, respondent as r) on (c.operator_id = op.operator_id and c.outcome_id = o.outcome_id and r.respondent_id = c.respondent_id)
		WHERE c.operator_id = '$operator_id'
		ORDER BY c.start DESC
		LIMIT 25";
	
	
	$rs = $db->GetAll($sql);
	
	if (empty($rs))
		print "<p>" . T_("No calls ever made") . "</p>";
	else
	{
		translate_array($rs,array("des"));
		xhtml_table($rs,array("start","case_id","des","firstName"),array(T_("Date/Time"),T_("Case ID"),T_("Outcome"),T_("Respondent")));
	}
}
else
	print "<p>" . T_("No operator") . "</p>";

xhtml_foot();


?>
