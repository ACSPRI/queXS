<?php
/**
 * Output data as a fixed width ASCII file
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
 * Input functions
 */
include("../functions/functions.input.php");


if (isset($_GET['data']))
{
	/**
	 * Limesurvey functions
	 */
	include("../functions/functions.limesurvey.php");

	$questionnaire_id = false;
	$sample_import_id = false;
	if (isset($_GET['sample_import_id'])) $sample_import_id = bigintval($_GET['sample_import_id']);
	if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);

	limesurvey_export_fixed_width($questionnaire_id,$sample_import_id);

	exit();
}


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

if (isset($_GET['key']) || isset($_GET['sample']))
{
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sample_import_id = bigintval($_GET['sample_import_id']);

	$sql = "SELECT sv.var as value
		FROM `sample_var` as sv
		WHERE sv.sample_id = (SELECT sample_id FROM sample WHERE import_id = '$sample_import_id' LIMIT 1)";

	$svars = $db->GetAll($sql);

	$fn = "key_all_";
	if (isset($_GET['sample'])) $fn = "sample_all_";

	$fn .= $questionnaire_id . "_" . $sample_import_id .".csv";

	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=$fn");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
	Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache");                          // HTTP/1.0

	echo("caseid");
	foreach($svars as $s)
	{
		echo("," . $s['value']);
	}
	if (isset($_GET['sample']))
	{
		echo(",Outcome,AAPOR");
	}

	echo("\n");

	$sql = "SELECT c.case_id ";

	if (isset($_GET['sample'])) $sql .= ", o.description, o.aapor_id ";

	$i = 0;
	foreach ($svars as $s)
	{
		$sql .= ", sv$i.val as v$i";
		$i++;
	}

	$sql .= " FROM sample ";

	//left join if getting whole sample file
	if (isset($_GET['sample'])) $sql .= "LEFT ";

	$sql .= "JOIN `case` as c ON (c.questionnaire_id = '$questionnaire_id' AND c.sample_id = sample.sample_id) ";

	if (isset($_GET['sample'])) $sql .= " LEFT JOIN `outcome` as o ON (o.outcome_id = c.current_outcome_id) ";

	$i = 0;
	foreach ($svars as $s)
	{
		$sql .= " LEFT JOIN sample_var AS sv$i ON (sv$i.sample_id = sample.sample_id AND sv$i.var = '{$s['value']}') ";
		$i++;
	}

	$sql .= " WHERE sample.import_id = '$sample_import_id'";

	$list = $db->GetAll($sql);


	if (!empty($list))
	{
		foreach($list as $l)
		{
			echo $l['case_id'];
			$i = 0;
			foreach ($svars as $s)
			{
				echo "," . str_replace(","," ",$l["v$i"]);
				$i++;
			}
			if (isset($_GET['sample']))
			{
				echo "," . str_replace(","," ",$l['description']) . "," . $l['aapor_id'];
			}
			echo  "\n";
		}
	}

	exit;
}

if (isset($_GET['sample_var']))
{
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sample_import_id = bigintval($_GET['sample_import_id']);
	$sample_var = $db->quote($_GET['sample_var']);

	$sql = "SELECT c.case_id, sv.val
		FROM sample, `case` as c, sample_var as sv
		WHERE c.questionnaire_id = '$questionnaire_id'
		AND sample.import_id = '$sample_import_id'
		AND c.sample_id = sample.sample_id
		AND sv.sample_id = sample.sample_id
		AND sv.var = $sample_var";

	$list = $db->GetAll($sql);

	$fn = "key_$questionnaire_id" . "_" . $sample_import_id .".csv";

	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=$fn");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
	Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache");                          // HTTP/1.0

	echo("caseid,$sample_var\n");

	if (!empty($list))
	{
		foreach($list as $l)
		{
			echo $l['case_id'] . "," . $l['val'] . "\n";
		}
	}

	exit;
}



xhtml_head(T_("Data output"),true,false,array("../js/window.js"));

print "<h3>" . T_("Please select a questionnaire") . "</h3>";
$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);
display_questionnaire_chooser($questionnaire_id);

if ($questionnaire_id)
{
	$sql = "SELECT lime_sid 
		FROM questionnaire
		WHERE questionnaire_id = $questionnaire_id";

	$ls = $db->GetRow($sql);
	$lsid = $ls['lime_sid'];

	print "<p><a href='" . LIME_URL . "admin/admin.php?action=exportresults&amp;sid=$lsid'>".  T_("Download data for this questionnaire via Limesurvey") . "</a></p>";

	print "<h3>" . T_("Please select a sample") . "</h3>";
	$sample_import_id = false;
	if (isset($_GET['sample_import_id'])) $sample_import_id = bigintval($_GET['sample_import_id']);
	display_sample_chooser($questionnaire_id,$sample_import_id);

	if ($sample_import_id)
	{
		print "<p><a href='" .LIME_URL . "admin/admin.php?action=exportresults&amp;sid=$lsid&amp;quexsfilterinc=$questionnaire_id:$sample_import_id'>" . T_("Download data for this sample via Limesurvey") . "</a></p>";
		//get sample vars
		$sql = "SELECT sv.var as value, sv.var as description 
			FROM `sample_var` as sv
			WHERE sv.sample_id = (SELECT sample_id FROM sample WHERE import_id = '$sample_import_id' LIMIT 1)";
	
		//download a key file linking the caseid to the sample
		print "<h3>" . T_("Download key file: select sample var") . "</h3>";

		display_chooser($db->GetAll($sql),"sample_var","sample_var",true,"questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id");
		//download complete key file
		print "<p><a href='?key=key&amp;questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id'>" . T_("Download complete key file") . "</a></p>";

		//download complete sample file with outcomes
		print "<p><a href='?sample=sample&amp;questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id'>" . T_("Download complete sample file with current outcomes") . "</a></p>";


	}
}

xhtml_foot();



?>

