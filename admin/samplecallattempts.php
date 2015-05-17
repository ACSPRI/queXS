<?php /**
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

/**
 * Generate the sample call attempt report
 * 
 * @param mixed  $questionnaire_id The quesitonnaire, if specified
 * @param string $sample_id        The sample, if speified
 * @param mixed  $qsqri            THe questionnaire sample quota row id, if specified
 * 
 * @return false if empty otherwise true if table drawn
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2012-10-02
 */
function sample_call_attempt_report($questionnaire_id = false, $sample_id = false, $qsqri  = false)
{
	global $db;

	$q = "";
	if ($questionnaire_id !== false && $questionnaire_id != -1)
		$q = "AND c.questionnaire_id = $questionnaire_id";

	$s = "";
	if ($sample_id !== false)
		$s = "JOIN sample as s ON (s.sample_id = c.sample_id AND s.import_id = '$sample_id')";

	$qs = "";
	if ($qsqri !== false)
		$qs = "JOIN questionnaire_sample_quota_row as q ON (q.questionnaire_sample_quota_row_id = '$qsqri')
			JOIN sample_var ON (sample_var.sample_id = c.sample_id AND sample_var.var_id = q.exclude_var_id AND sample_var.val LIKE q.exclude_val)";

	$sql = "SELECT ca1 AS callattempts, COUNT( ca1 ) AS sample
		FROM (	SELECT count( ca.call_attempt_id ) AS ca1
			FROM call_attempt as ca
			JOIN `case` as c ON (c.case_id = ca.case_id $q)
			$s
			$qs
			GROUP BY ca.case_id) AS t1
		GROUP BY ca1";

	$overall = $db->GetAll($sql);

	if (empty($overall)) 
		return false;


	$sql = "SELECT outcome_id,description 
		FROM outcome";

	$outcomes = $db->GetAssoc($sql);

	translate_array($outcomes,array("description"));

	$rep = array("callattempts","sample");
	$rept = array(T_("Call attempts made"),T_("Number of cases"));
	$totals = array("sample");

	$outcomesfilled = array();

	foreach($outcomes as $key => $val)
	{
		$rep[] = $key;
		$rept[] = $val;
		$outcomesfilled[$key] = 0;
	}

	//Add breakdown by call attempt
	for ($i = 0; $i < count($overall); $i++)
	{
		//break down by outcome
		$sql = "SELECT oi1, ca1 as callattempts, count(ca1) as sample
			FROM ( SELECT count( ca.call_attempt_id ) AS ca1, ca.case_id, c.current_outcome_id as oi1
				FROM call_attempt AS ca
				JOIN `case` AS c ON ( c.case_id = ca.case_id $q)
				$s
				$qs
				GROUP BY ca.case_id
				) as t1
			GROUP BY ca1,oi1
			HAVING ca1 = {$overall[$i]['callattempts']}";
		
		$byoutcome = $db->GetAssoc($sql);

		foreach($outcomes as $key => $val)
		{
			$sample = 0;
			if (isset($byoutcome[$key]))
			{
				$outcomesfilled[$key] = 1;
				$sample = $byoutcome[$key]['sample'];
			}

			$overall[$i][$key] = $sample;
		}
	}

	//Remove columns that are empty
	$count = 2;
	foreach ($outcomesfilled as $key => $val)
	{
		if ($val == 0)
		{
			unset($rep[$count]);
			unset($rept[$count]);
		}
		else
			$totals[] = $key;

		$count++;
	}

	xhtml_table($overall,$rep,$rept,"tclass",false,$totals);
	print "</br>";
	return true;
}


xhtml_head(T_("Sample call attempt"),true,array("../include/bootstrap-3.3.2/css/bootstrap.min.css","../css/custom.css"),array("../js/window.js"));

print "<h3 class='form-inline pull-left'>" . T_("Please select a questionnaire") . "&emsp;</h3>";
$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) $questionnaire_id = intval($_GET['questionnaire_id']);
display_questionnaire_chooser($questionnaire_id,array(-1,T_("Overall")),"form-inline clearfix", "form-control");


if ($questionnaire_id || $questionnaire_id == -1)
{
	if (sample_call_attempt_report($questionnaire_id,false,false))
	{
		if ($questionnaire_id != -1)
		{
			print "<h3 class='form-inline pull-left'>" . T_("Please select a sample") . "&emsp;</h3>";
			$sample_import_id = false;
			if (isset($_GET['sample_import_id'])) $sample_import_id = bigintval($_GET['sample_import_id']);
			display_sample_chooser($questionnaire_id,$sample_import_id,false,"form-inline clearfix", "form-control");

			if ($sample_import_id)
			{
				if (sample_call_attempt_report($questionnaire_id,$sample_import_id,false))
				{
					$questionnaire_sample_quota_row_id = false;
					if (isset($_GET['questionnaire_sample_quota_row_id'])) $questionnaire_sample_quota_row_id = bigintval($_GET['questionnaire_sample_quota_row_id']);
					print "<h3 class='form-inline pull-left'>" . T_("Please select a quota") . "&emsp;</h3>";
					display_quota_chooser($questionnaire_id,$sample_import_id,$questionnaire_sample_quota_row_id,"form-inline clearfix", "form-control");
		
					if ($questionnaire_sample_quota_row_id)
					{
						if (!sample_call_attempt_report($questionnaire_id,$sample_import_id,$questionnaire_sample_quota_row_id))
							print "<p class='well text-danger'>" . T_("No calls for this quota") . "</p>";
						
					}
				}
				else
					print "<p class='well text-danger'>" . T_("No calls for this sample") . "</p>";
			}
		}
	}
	else
		print "<p class='well text-danger'>" . T_("No calls for this questionnaire") . "</p>";
}

xhtml_foot("../js/custom.js");

?>
