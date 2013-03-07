<?php 
/**
 * Display a list of calls and outcomes for all calls  
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
include ("../config.inc.php");

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
include("../functions/functions.operator.php");


//List the case call history
$operator_id = get_operator_id();

if ($operator_id)
{
	$sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.start,'UTC',op.Time_zone_name),'".DATE_TIME_FORMAT."') as start,DATE_FORMAT(CONVERT_TZ(c.end,'UTC',op.Time_zone_name),'".TIME_FORMAT."') as end, o.description as des, (CONCAT(r.firstName,' ',r.lastName)) as firstName, opp.firstName as opname, ";

	if (isset($_GET['csv']))
		$sql .= " c.case_id ";
	else
		$sql .= " CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>') ";

	$sql .=	" as case_id, q.description as qd
		FROM `call` as c
		JOIN (operator as op, respondent as r) on (op.operator_id = '$operator_id' and r.respondent_id = c.respondent_id)
		JOIN (`case` as ca, questionnaire as q) ON (ca.case_id = c.case_id AND q.questionnaire_id = ca.questionnaire_id)
		LEFT JOIN (outcome as o) on (c.outcome_id = o.outcome_id)
		LEFT JOIN (operator as opp) on (opp.operator_id = c.operator_id)
		ORDER BY c.start DESC";

	if (!isset($_GET['csv'])) 
		$sql .= " LIMIT 500";
		
	$rs = $db->Execute($sql);
	
	if (empty($rs))
	{
		xhtml_head(T_("Call History List"),true,array("../css/table.css"));
		print "<p>" . T_("No calls ever made") . "</p>";
	}
	else
	{
		if (isset($_GET['csv']))
		{
			$fn = "callhistory.csv";

			header("Content-Type: text/csv");
			header("Content-Disposition: attachment; filename=$fn");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Pragma: no-cache");                          // HTTP/1.0

			echo(T_("Date/Time call start") . "," . T_("Time end") . "," . T_("Case ID") . "," . T_("Questionnaire") . "," . T_("Operator") . "," . T_("Outcome") . "," . T_("Respondent") . "\n");

			while ($r = $rs->FetchRow())
			{
				translate_array($r,array("des"));
				echo $r['start'] . "," . $r['end'] . "," . $r['case_id'] . "," . $r['qd'] . "," . $r['opname'] . ",\"" . $r['des'] . "\"," . $r['firstName'] . "\n";
			}
			exit;
		}			
		else
		{
			$rs = $rs->GetArray();
			translate_array($rs,array("des"));
			xhtml_head(T_("Call History List"),true,array("../css/table.css"));
			print "<p><a href='?csv=csv'>" . T_("Download Call History List") . "</a></p>";
			xhtml_table($rs,array("start","end","case_id","qd","opname","des","firstName"),array(T_("Date/Time call start"),T_("Time end"),T_("Case ID"),T_("Questionnaire"),T_("Operator"),T_("Outcome"),T_("Respondent")));
		}
	}
}
else
{
	xhtml_head(T_("Call History List"),true,array("../css/table.css"));
	print "<p>" . T_("No operator") . "</p>";
}

xhtml_foot();


?>
