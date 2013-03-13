<?php /**
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

$admin_operator_id = get_operator_id();

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

	print "<p>" . T_("Case availability (cases with temporary or appointment outcomes)") ."</p>";

	$sql = "SELECT count(c.case_id) as available, si.description
                                        FROM `case`  as c
                                        LEFT JOIN `call` as a on (a.call_id = c.last_call_id)
                                        JOIN (sample as s, sample_import as si) on (s.sample_id = c.sample_id and si.sample_import_id = s.import_id)
                                        JOIN (questionnaire_sample as qs, questionnaire as q, outcome as ou) on (q.questionnaire_id = $questionnaire_id and c.questionnaire_id = q.questionnaire_id and qs.sample_import_id = s.import_id and qs.questionnaire_id = q.questionnaire_id and ou.outcome_id = c.current_outcome_id)
                                        LEFT JOIN shift as sh on (sh.questionnaire_id = q.questionnaire_id and (CONVERT_TZ(NOW(),'System','UTC') >= sh.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= sh.end))
                                        LEFT JOIN appointment as ap on (ap.case_id = c.case_id AND ap.completed_call_id is NULL AND (ap.start > CONVERT_TZ(NOW(),'System','UTC')))
                                        LEFT JOIN appointment as apn on (apn.case_id = c.case_id AND apn.completed_call_id is NULL AND (CONVERT_TZ(NOW(),'System','UTC') >= apn.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= apn.end))
                                        LEFT JOIN call_restrict as cr on (cr.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= cr.start and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= cr.end)
                                        LEFT JOIN questionnaire_sample_exclude_priority AS qsep ON (qsep.questionnaire_id = c.questionnaire_id AND qsep.sample_id = c.sample_id)
                                        LEFT JOIN case_availability AS casa ON (casa.case_id = c.case_id)
                                        LEFT JOIN availability AS ava ON (ava.availability_group_id = casa.availability_group_id)
                                        WHERE c.current_operator_id IS NULL
					AND ou.outcome_type_id IN (1,5)
                                        AND (casa.case_id IS NULL OR (ava.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(),'System',s.Time_zone_name)) AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= ava.start AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= ava.end  ))
                                        AND (a.call_id is NULL or (a.end < CONVERT_TZ(DATE_SUB(NOW(), INTERVAL ou.default_delay_minutes MINUTE),'System','UTC')))
                                        AND ap.case_id is NULL
                                        AND ((qsep.questionnaire_id is NULL) or qsep.exclude = 0)
                                        AND !(q.restrict_work_shifts = 1 AND sh.shift_id IS NULL AND ou.outcome_type_id != 2)
                                        AND !(si.call_restrict = 1 AND cr.day_of_week IS NULL AND ou.outcome_type_id != 2)
                                        AND ((apn.appointment_id IS NOT NULL) or qs.call_attempt_max = 0 or ((SELECT count(*) FROM call_attempt WHERE case_id = c.case_id) < qs.call_attempt_max))
                                        AND ((apn.appointment_id IS NOT NULL) or qs.call_max = 0 or ((SELECT count(*) FROM `call` WHERE case_id = c.case_id) < qs.call_max))
                                        AND (SELECT count(*) FROM `questionnaire_sample_quota` WHERE questionnaire_id = c.questionnaire_id AND sample_import_id = s.import_id AND quota_reached = 1) = 0
                                     
group by s.import_id";

	$rs = $db->GetAll($sql);
	
	if (empty($rs))
	{
		print "<div>" . T_("No cases currently available to call") . "</div>";
	}
	else
	{
		xhtml_table($rs,array("description","available"),array(T_("Sample"),T_("Cases currently available to call")),"tclass",false,array("available"));
	}

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
	
	
	$sql = "SELECT CONCAT('<a href=\'casesbyoutcome.php?questionnaire_id=$questionnaire_id&amp;outcome_id=', o.outcome_id, '\'>', o.description, '</a>') as des, o.outcome_id, count( c.case_id ) as count, ROUND((count(c.case_id) / (SELECT count(case_id) FROM `case` WHERE questionnaire_id = '$questionnaire_id')) * 100,2) as perc
		FROM `case` AS c, `outcome` AS o
		WHERE c.questionnaire_id = '$questionnaire_id'
		AND c.current_outcome_id = o.outcome_id
		GROUP BY o.outcome_id";
	
	$rs = $db->GetAll($sql);
	
	if (!empty($rs))
	{
		translate_array($rs,array("des"));
		xhtml_table($rs,array("des","count","perc"),array(T_("Outcome"),T_("Count"),T_("%")),"tclass",array("des" => "Complete"),array("count","perc"));

		$operator_id = false;
		if (isset($_GET['operator_id'])) $operator_id = bigintval($_GET['operator_id']); 

		//display a list of operators
		$sql = "SELECT s.operator_id as value,s.firstname as description, CASE WHEN s.operator_id = '$operator_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
			FROM operator as s, operator_questionnaire as q
			WHERE s.operator_id = q.operator_id
			AND q.questionnaire_id = '$questionnaire_id'";

		$r = $db->GetAll($sql);

		print "<h2>" . T_("Operator") . ": " . "</h2>";
		if(!empty($r))
			display_chooser($r,"operator_id","operator_id",true,"questionnaire_id=$questionnaire_id");

		if ($operator_id != false)
		{
			print "<p>" . T_("Operator call outcomes") . "</p>";
		
			$sql = "SELECT o.description as des, o.outcome_id, count( c.call_id ) as count, ROUND((count(c.call_id) / (SELECT count(call.call_id) FROM `call` JOIN `case` ON (call.case_id = `case`.case_id AND `case`.questionnaire_id = $questionnaire_id ) WHERE call.operator_id = '$operator_id')) * 100,2) as perc
				FROM `call` AS c, `case` as ca, `outcome` AS o
				WHERE ca.questionnaire_id = '$questionnaire_id'
				AND ca.case_id = c.case_id
				AND c.operator_id = '$operator_id'
				AND c.outcome_id = o.outcome_id
				GROUP BY o.outcome_id";
			
			$rs = $db->GetAll($sql);
		
			if (!empty($rs))
			{
				translate_array($rs,array("des"));
				xhtml_table($rs,array("des","count","perc"),array(T_("Outcome"),T_("Count"),T_("%")),"tclass",array("des" => "Complete"),array("count","perc"));
			}
		}

		$sample_import_id = false;
		if (isset($_GET['sample_import_id'])) $sample_import_id = bigintval($_GET['sample_import_id']); 

		//display a list of samples
		$sql = "SELECT s.sample_import_id as value,s.description, CASE WHEN s.sample_import_id = '$sample_import_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
			FROM sample_import as s, questionnaire_sample as q
			WHERE s.sample_import_id = q.sample_import_id
			AND q.questionnaire_id = '$questionnaire_id'";

		$r = $db->GetAll($sql);

		
		print "<h2>" . T_("Sample") . ": " . "</h2>";
		if(!empty($r))
			display_chooser($r,"sample_import_id","sample_import_id",true,"questionnaire_id=$questionnaire_id");


		if ($sample_import_id != false)
		{
			print "<p>" . T_("Sample status") . "</p>";

			$sql = "SELECT CASE WHEN (c.sample_id is not null) = 1 THEN '" . T_("Drawn from sample") . "' ELSE '" . T_("Remain in sample") . "' END as drawn,
					count(*) as count
				FROM sample as s
				JOIN questionnaire_sample as qs ON (qs.questionnaire_id = '$questionnaire_id' and qs.sample_import_id = s.import_id)
				LEFT JOIN `case` as c ON (c.questionnaire_id = qs.questionnaire_id and c.sample_id = s.sample_id)
				WHERE s.import_id = '$sample_import_id'
				GROUP BY (c.sample_id is not null)";

			xhtml_table($db->GetAll($sql),array("drawn","count"),array(T_("Status"),T_("Number")));


			print "<p>" . T_("Outcomes") . "</p>";


			$sql = "SELECT o.description as des, o.outcome_id, count( c.case_id ) as count,ROUND(count(c.case_id) / (SELECT count(case_id) FROM `case` JOIN sample ON (`case`.sample_id = sample.sample_id AND sample.import_id = '$sample_import_id') WHERE questionnaire_id = '$questionnaire_id' ) * 100,2) as perc

				FROM `case` AS c, `outcome` AS o, sample as s
				WHERE c.questionnaire_id = '$questionnaire_id'
				AND c.sample_id = s.sample_id
				AND s.import_id = '$sample_import_id'
				AND c.current_outcome_id = o.outcome_id
				GROUP BY o.outcome_id";
		
			$rs = $db->GetAll($sql);
			
			if (!empty($rs))
			{
				translate_array($rs,array("des"));
				xhtml_table($rs,array("des","count","perc"),array(T_("Outcome"),T_("Count"),T_("%")),"tclass",array("des" => "Complete"),array("count","perc"));
			}
			else
				print "<p>" . T_("No outcomes recorded for this sample") . "</p>";
		}
	
	}
	else
		print "<p>" . T_("No outcomes recorded for this questionnaire") . "</p>";


	//display a list of shifts with completions and a link to either add a report or view reports
	print "<h2>" . T_("Shifts") . "</h2>";

	$sql = "SELECT s.shift_id, CONCAT(DATE_FORMAT(CONVERT_TZ(s.start,'UTC',o.Time_zone_name),'" . DATE_TIME_FORMAT ."'), ' - ', DATE_FORMAT(CONVERT_TZ(s.end,'UTC',o.Time_zone_name),'" . DATE_TIME_FORMAT ."')) as description,
		CASE WHEN sr.shift_id IS NULL THEN CONCAT('<a href=\'shiftreport.php?questionnaire_id=$questionnaire_id&amp;shift_id=', s.shift_id, '&amp;createnewreport=yes\'>" . T_("No shift reports: Add report") . "</a>') ELSE CONCAT('<a href=\'shiftreport.php?questionnaire_id=$questionnaire_id&amp;shift_id=', s.shift_id, '\'>" . T_("View shift reports") . "</a>') END AS link, c.completions as completions, CONCAT('<a href=\'operatorperformance.php?questionnaire_id=$questionnaire_id&amp;shift_id=', s.shift_id, '\'>" . T_("View operator performance") . "</a>') as operform
		FROM `shift` as s
		JOIN operator as o on (o.operator_id = '$admin_operator_id')
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

