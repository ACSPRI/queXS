<?php
/**
 * Display sample call attempt report (A listing of how many attempts made for cases within a sample)
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
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2009
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
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
 * Display functions
 */
include("../functions/functions.display.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

xhtml_head(T_("Sample call attempt"),true,array("../css/table.css"),array("../js/window.js"));

print "<h2>" . T_("Overall") . "</h2>";

$sql = "SELECT ca1 AS callattempts, COUNT( ca1 ) AS sample
	FROM (	SELECT count( call_attempt_id ) AS ca1
		FROM call_attempt
		GROUP BY case_id) AS t1
	GROUP BY ca1";

xhtml_table($db->GetAll($sql),array("sample","callattempts"),array(T_("Number of cases"),T_("Call attempts made")),"tclass",false,array("sample"));

print "<h2>" . T_("Please select a questionnaire") . "</h2>";
$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);
display_questionnaire_chooser($questionnaire_id);

if ($questionnaire_id)
{
	$sql = "SELECT ca1 AS callattempts, COUNT( ca1 ) AS sample
		FROM (	SELECT count( call_attempt.call_attempt_id ) AS ca1
			FROM call_attempt
			JOIN `case` ON (`case`.case_id = call_attempt.case_id AND `case`.questionnaire_id = '$questionnaire_id')
			GROUP BY call_attempt.case_id) AS t1
		GROUP BY ca1";

	$cal = $db->GetAll($sql);

	if (!empty($cal))
	{

		xhtml_table($cal,array("sample","callattempts"),array(T_("Number of cases"),T_("Call attempts made")),"tclass",false,array("sample"));
	
		print "<h2>" . T_("Please select a sample") . "</h2>";
		$sample_import_id = false;
		if (isset($_GET['sample_import_id'])) $sample_import_id = bigintval($_GET['sample_import_id']);
		display_sample_chooser($questionnaire_id,$sample_import_id);

		if ($sample_import_id)
		{
			$sql = "SELECT ca1 AS callattempts, COUNT( ca1 ) AS sample
				FROM (	SELECT count( call_attempt.call_attempt_id ) AS ca1
					FROM call_attempt
					JOIN `case` ON (`case`.case_id = call_attempt.case_id AND `case`.questionnaire_id = '$questionnaire_id')
					JOIN sample ON (sample.sample_id = `case`.sample_id AND sample.import_id = '$sample_import_id')
					GROUP BY call_attempt.case_id) AS t1
				GROUP BY ca1";

			$cal = $db->GetAll($sql);

			if (!empty($cal))
			{
				xhtml_table($cal,array("sample","callattempts"),array(T_("Number of cases"),T_("Call attempts made")),"tclass",false,array("sample"));
	
				$questionnaire_sample_quota_row_id = false;
				if (isset($_GET['questionnaire_sample_quota_row_id'])) $questionnaire_sample_quota_row_id = bigintval($_GET['questionnaire_sample_quota_row_id']);
				print "<h2>" . T_("Please select a quota") . "</h2>";
				display_quota_chooser($questionnaire_id,$sample_import_id,$questionnaire_sample_quota_row_id);
	
				if ($questionnaire_sample_quota_row_id)
				{
					$sql = "SELECT ca1 AS callattempts, COUNT( ca1 ) AS sample
						FROM (	SELECT count( call_attempt.call_attempt_id ) AS ca1
							FROM call_attempt
							JOIN `case` ON (`case`.case_id = call_attempt.case_id AND `case`.questionnaire_id = '$questionnaire_id')
							JOIN sample ON (sample.sample_id = `case`.sample_id AND sample.import_id = '$sample_import_id')
							JOIN questionnaire_sample_quota_row as q ON (q.questionnaire_sample_quota_row_id = '$questionnaire_sample_quota_row_id')
							JOIN sample_var ON (sample_var.sample_id = `case`.sample_id AND sample_var.var LIKE q.exclude_var AND sample_var.val LIKE q.exclude_val)
							GROUP BY call_attempt.case_id) AS t1
						GROUP BY ca1";

					$cal = $db->GetAll($sql);
					if (empty($cal))
						print "<p>" . T_("No calls for this quota") . "</p>";
					else
						xhtml_table($cal,array("sample","callattempts"),array(T_("Number of cases"),T_("Call attempts made")),"tclass",false,array("sample"));
				}
			}
			else
				print "<p>" . T_("No calls for this sample") . "</p>";
		}
	}
	else
		print "<p>" . T_("No calls for this questionnaire") . "</p>";
}

xhtml_foot();

?>
