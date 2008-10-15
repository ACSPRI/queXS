<?php
/**
 * Display outcomes by questionnaire
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
 * AAPOR calculation functions
 */
include ("../functions/functions.aapor.php");

/**
 * Display functions
 */
include ("../functions/functions.display.php");

/**
 * Performance functions
 */
include ("../functions/functions.performance.php");

/**
 * Operator functions
 */
include ("../functions/functions.operator.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

$operator_id = get_operator_id();

$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);


xhtml_head(T_("Questionnaire Outcomes"),true,array("../css/table.css"),array("../js/window.js"));


print "<h3>" . T_("Select a questionnaire from the list below") . "</h3>";

display_questionnaire_chooser($questionnaire_id);

if ($questionnaire_id != false)
{
	print "<h1>" . T_("Outcomes") . "</h1>";
	
	print "<p>" . T_("Sample status") . "</p>";

	$sql = "SELECT CASE WHEN (c.sample_id is not null) = 1 THEN '" . T_("Drawn from sample") . "' ELSE '" . T_("Remain in sample") . "' END as drawn,
			count(*) as count
		FROM sample as s
		JOIN questionnaire_sample as qs ON (qs.questionnaire_id = '$questionnaire_id' and qs.sample_import_id = s.import_id)
		LEFT JOIN `case` as c ON (c.questionnaire_id = qs.questionnaire_id and c.sample_id = s.sample_id)
		GROUP BY (c.sample_id is not null)";

	xhtml_table($db->GetAll($sql),array("drawn","count"),array(T_("Status"),T_("Number")));

	$atime = get_average_time_questionnaire(10,$questionnaire_id);
	$mins = intval($atime / 60);
	$secs = $atime % 60;

	print "<p>" . T_("Average time on a completed questionnaire") . ": $mins " . T_("Min") . " $secs " . T_("Secs") . "</p>";


	$sql = "SELECT o.calc, count( c.case_id )
		FROM `case` AS c, `outcome` AS o
		WHERE c.questionnaire_id = '$questionnaire_id'
		AND c.current_outcome_id = o.outcome_id
		GROUP BY o.calc";
	
	$a = $db->GetAssoc($sql);
	$a = aapor_clean($a);

	
	print "<table><tr><th>" . T_("Outcome") . "</th><th>" . T_("Rate") . "</th></tr>"; 
	print "<tr><td>" . T_("Response Rate 1") . "</td><td>" . round(aapor_rr1($a),2) . "</td></tr>";
	print "<tr><td>" . T_("Refusal Rate 1") . "</td><td>" . round(aapor_ref1($a),2) . "</td></tr>";
	print "<tr><td>" . T_("Cooperation Rate 1") . "</td><td>" . round(aapor_coop1($a),2) . "</td></tr>";
	print "<tr><td>" . T_("Contact Rate 1") . "</td><td>" . round(aapor_con1($a),2) . "</td></tr>";
	print "</table>";
	
	
	$sql = "SELECT o.description as des, o.outcome_id, count( c.case_id ) as count
		FROM `case` AS c, `outcome` AS o
		WHERE c.questionnaire_id = '$questionnaire_id'
		AND c.current_outcome_id = o.outcome_id
		GROUP BY o.outcome_id";
	
	$rs = $db->GetAll($sql);
	
	if (!empty($rs))
		xhtml_table($rs,array("des","count"),array(T_("Outcome"),T_("Count")),"tclass",array("des" => "Complete"));
	else
		print "<p>" . T_("No outcomes recorded for this questionnaire") . "</p>";

	//display a list of shifts with completions and a link to either add a report or view reports
	print "<h2>" . T_("Shifts") . "</h2>";

	$sql = "SELECT s.shift_id, CONCAT(DATE_FORMAT(CONVERT_TZ(s.start,'UTC',o.Time_zone_name),'" . DATE_TIME_FORMAT ."'), ' - ', DATE_FORMAT(CONVERT_TZ(s.end,'UTC',o.Time_zone_name),'" . DATE_TIME_FORMAT ."')) as description,
		CASE WHEN sr.shift_id IS NULL THEN CONCAT('<a href=\'shiftreport.php?questionnaire_id=$questionnaire_id&amp;shift_id=', s.shift_id, '&amp;createnewreport=yes\'>" . T_("No shift reports: Add report") . "</a>') ELSE CONCAT('<a href=\'shiftreport.php?questionnaire_id=$questionnaire_id&amp;shift_id=', s.shift_id, '\'>" . T_("View shift reports") . "</a>') END AS link, c.completions as completions, CONCAT('<a href=\'operatorperformance.php?questionnaire_id=$questionnaire_id&amp;shift_id=', s.shift_id, '\'>" . T_("View operator performance") . "</a>') as operform
		FROM `shift` as s
		JOIN operator as o on (o.operator_id = '$operator_id')
		LEFT JOIN shift_report as sr on (sr.shift_id = s.shift_id)
		LEFT JOIN (  SELECT count(*) as completions,sh.shift_id
			FROM `call` as a, `case` as b, shift as sh
			WHERE a.outcome_id = '10'
			AND a.case_id = b.case_id
			AND b.questionnaire_id = '$questionnaire_id'
			AND sh.start <= a.start
			AND sh.end >= a.start
			GROUP BY sh.shift_id) as c on (s.shift_id = c.shift_id)
		WHERE s.questionnaire_id = '$questionnaire_id'
		GROUP BY shift_id
		ORDER BY s.start ASC";

	$r = $db->GetAll($sql);

	if (empty($r))
		print "<p>" . T_("No shifts defined for this questionnaire") . "</p>";
	else
		xhtml_table($r,array("description","completions","link","operform"),array(T_("Shift"),T_("Completions"),T_("Shift report"),T_("Operator performance")),"tclass");


	
}

xhtml_foot();

?>

