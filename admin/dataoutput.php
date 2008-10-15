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

print "<h3>Please select a questionnaire</h3>";
$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);
display_questionnaire_chooser($questionnaire_id);

if ($questionnaire_id)
{
	print "<p><a href='?data&amp;questionnaire_id=$questionnaire_id'>Download all data for this questionnaire</a></p>";

	$sample_import_id = false;
	if (isset($_GET['sample_import_id'])) $sample_import_id = bigintval($_GET['sample_import_id']);
	display_sample_chooser($questionnaire_id,$sample_import_id);

	if ($sample_import_id)
	{
		print "<p><a href='?data&amp;questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id'>Download data for this sample</a></p>";
		//get sample vars
		$sql = "SELECT sv.var as value, sv.var as description 
			FROM `sample_var` as sv
			WHERE sv.sample_id = (SELECT sample_id FROM sample WHERE import_id = '$sample_import_id' LIMIT 1)";
	
		//download a key file linking the caseid to the sample
		print "<h3>Download key file: select sample var</h3>";

		display_chooser($db->GetAll($sql),"sample_var","sample_var",true,"questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id");

	}
}

xhtml_foot();



?>

