<?php /**
 * Display operator performance
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
include("../functions/functions.performance.php");

/**
 * Display functions
 */
include("../functions/functions.display.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

/**
 * Operator functions
 */
include("../functions/functions.operator.php");

xhtml_head(T_("Operator Performance"),true,array("../include/bootstrap/css/bootstrap.min.css","../css/custom.css"),array("../js/window.js"));

//$rs = get_stats_total(get_stats());
//print "<h2>" . T_("Overall") . "</h2>";
//xhtml_table($rs,array("firstName","completions","totalcalls","time","callt","CPH","CALLSPH","effectiveness"),array(T_("Operator"),T_("Completions"),T_("Calls"),T_("Total time"),T_("Call time"),T_("Completions p/h"),T_("Calls p/h"),T_("Effectiveness")));

$questionnaire_id = false;
print "<h3 class='form-inline pull-left'>" . T_("Please select a questionnaire") . "&emsp;</h3>";
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);
display_questionnaire_chooser($questionnaire_id,false,"form-inline clearfix", "form-control");

if ($questionnaire_id)
{
	$rs = get_stats_total(get_stats_by_questionnaire($questionnaire_id));
	print "<h2>" . T_("This project") . "</h2>";
	xhtml_table($rs,array("firstName","completions","totalcalls","time","callt","CPH","CALLSPH","effectiveness"),array(T_("Operator"),T_("Completions"),T_("Calls"),T_("Total time"),T_("Call time"),T_("Completions p/h"),T_("Calls p/h"),T_("Effectiveness")));

	$operator_id = get_operator_id();

	$shift_id = false;
	if (isset($_GET['shift_id'])) $shift_id = bigintval($_GET['shift_id']);

	$sql = "SELECT s.shift_id as value,CONCAT(DATE_FORMAT(CONVERT_TZ(s.start,'UTC',o.Time_zone_name),'" . DATE_TIME_FORMAT . "'),' " . TQ_("till") . " ',DATE_FORMAT(CONVERT_TZ(s.end,'UTC',o.Time_zone_name),'" . TIME_FORMAT . "')) as description,CASE WHEN s.shift_id = '$shift_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM shift as s
		LEFT JOIN (operator as o) on (o.operator_id = '$operator_id')
		WHERE s.questionnaire_id = '$questionnaire_id'
		ORDER BY s.start ASC";
		
	$rs = $db->GetAll($sql);

	print "</br><h3 class='form-inline pull-left'>" . T_("Please select a shift") . "&emsp;</h3>";
	
	display_chooser($rs,"shift_id","shift_id",true,"questionnaire_id=$questionnaire_id",true,true,false,true,"form-inline form-group");//,false,true,false,true,"pull-left"

	if ($shift_id)
	{
		$rs = get_stats_total(get_stats_by_shift($questionnaire_id,$shift_id));
		print "<h2>" . T_("This shift") . "</h2>";
		xhtml_table($rs,array("firstName","completions","totalcalls","time","callt","CPH","CALLSPH","effectiveness"),array(T_("Operator"),T_("Completions"),T_("Calls"),T_("Total time"),T_("Call time"),T_("Completions p/h"),T_("Calls p/h"),T_("Effectiveness")));
	}
}

xhtml_foot();
?>

