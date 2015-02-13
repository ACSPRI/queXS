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

$css = array(
"../include/bootstrap-3.3.2/css/bootstrap.min.css", 
"../include/bootstrap-3.3.2/css/bootstrap-theme.min.css",
"../include/font-awesome-4.3.0/css/font-awesome.css",
"../include/bs-data-table/css/jquery.bdt.css",
//"../css/bootstrap-switch.min.css",
//"../css/table.css",
"../css/custom.css"
			);
$js_head = array(
//"../js/modernizr.js",
"../js/jquery-2.1.3.min.js",
//"//code.jquery.com/jquery-migrate-1.2.1.min.js",
"../include/bootstrap-3.3.2/js/bootstrap.min.js",
//"../js/bootstrap-switch.min.js"
				);
$js_foot = array(
"../include/bs-data-table/js/vendor/jquery.sortelements.js",
"../include/bs-data-table/js/jquery.bdt.js",
"../js/custom.js"
				);

//List the case call history
$operator_id = get_operator_id();
/* 
	Modified Call history list to have more information more suitable way with filtering, soring, paging and submenu for Cse history with asterisk records....
	Need to be linked with cdr records from asterisk!! for monitoring (requires addtional field for call_attempt table to request and store asterisk UniqueID  as a reference to CDR .wav file list  at /var/spool/asterisk/monitor/ )
*/
if ($operator_id)
{
	$sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.start,'UTC',op.Time_zone_name),'".DATE_FORMAT."') as start_date, DATE_FORMAT(CONVERT_TZ(c.start,'UTC',op.Time_zone_name),'".TIME_FORMAT."') as start_time, DATE_FORMAT(CONVERT_TZ(c.end,'UTC',op.Time_zone_name),'".TIME_FORMAT."') as end, o.description as descr, (CONCAT(r.firstName,' ',r.lastName)) as firstName, opp.firstName as opname,
			(SELECT GROUP_CONCAT(cn1.note SEPARATOR '&para; &emsp; \r' ) FROM `case_note`  as cn1 WHERE c.case_id = cn1.case_id GROUP BY cn1.case_id)as casenotes,";

	if (isset($_GET['csv']))
		$sql .= " c.case_id ";
	else
		$sql .= " CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>') ";

	$sql .=	" as case_id, q.description as qd , contact_phone.phone as cpi, sample_import.description as spl 
		FROM `call` as c
		JOIN (operator as op, respondent as r) on (op.operator_id = '$operator_id' and r.respondent_id = c.respondent_id)
		JOIN (`case` as ca, questionnaire as q) ON (ca.case_id = c.case_id AND q.questionnaire_id = ca.questionnaire_id)
		LEFT JOIN (outcome as o) on (c.outcome_id = o.outcome_id)
		LEFT JOIN (operator as opp) on (opp.operator_id = c.operator_id),
		contact_phone, sample_import, sample
		WHERE c.contact_phone_id = contact_phone.contact_phone_id AND sample_import.sample_import_id = sample.import_id 
		AND sample.sample_id = ca.sample_id
		ORDER BY c.start DESC";

	if (!isset($_GET['csv'])) 
		$sql .= " LIMIT 500";
		
	$rs = $db->Execute($sql);
	
	if (empty($rs))
	{
		print "<div class='alert alert-warning col-sm-4'>" . T_("No calls ever made") . "</div>";
	}
	else
	{
		if (isset($_GET['csv']))
		{
			$fn = "callhistory" . date("_d-M-Y__H-i") . ".csv";

			header("Content-Type: text/csv");
			header("Content-Disposition: attachment; filename=$fn");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Pragma: no-cache");                          // HTTP/1.0

			echo(T_("Date") . ",".T_("Start time") . "," . T_("End time") . "," . T_("Case ID") . "," . T_("Questionnaire") . "," . T_("Sample") . "," . T_("Phone number") . "," . T_("Operator") . "," . T_("Outcome") . ",".T_("Case notes")."," . T_("Respondent") . "\n");

			while ($r = $rs->FetchRow())
			{
				translate_array($r,array("des"));
				echo $r['start_date'] . "," .$r['start_time'] . "," . $r['end'] . "," . $r['case_id'] . "," . $r['qd'] . "," . $r['spl'] . "," . $r['cpi'] . "," . $r['opname'] . "," . $r['descr'] . "," . $r['casenotes'] . "," . $r['firstName'] . "\n";
			}
			exit;
		}			
		else
		{
			$rs = $rs->GetArray();
			translate_array($rs,array("des"));
			xhtml_head(T_("Call History List"),true,$css,$js_head); //array("../css/table.css")
			print "<a href='?csv=csv' class='btn btn-default  pull-right'><i class='fa fa-download fa-lg text-primary'></i>&emsp;" . T_("Download Call History List") . "</a>";
			xhtml_table($rs,array("start_date", "start_time", "end","case_id","qd","spl","cpi","opname","descr","casenotes","firstName"),array(T_("Date"), T_("Start time"), T_("End time"),T_("Case ID"),T_("Questionnaire"),T_("Sample"),T_("Phone number"),T_("Operator"),T_("Outcome"),T_("Case notes"),T_("Respondent")), "tclass",false,false,"bs-table");
		}
	}
}
else
{

	print "<div class='alert alert-warning col-sm-4'>" . T_("No operator") . "</div>";
}

xhtml_foot($js_foot);
?>
<script type="text/javascript">
$('#bs-table').bdt();
</script>