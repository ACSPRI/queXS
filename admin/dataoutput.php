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
 * Display functions
 */
include("../functions/functions.display.php");

if (isset($_GET['key']) || isset($_GET['sample']))
{
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sample_import_id = bigintval($_GET['sample_import_id']);

	$sql = "SELECT sivr.var as value, sivr.var_id as var_id
		FROM `sample_import_var_restrict` as sivr
		WHERE sivr.sample_import_id = $sample_import_id";

	$svars = $db->GetAll($sql);

	$fn = "key_";
	if (isset($_GET['sample'])) $fn = "sample_";

	$fn .= T_("ALL") . "_Qid=" . $questionnaire_id . "_Sid=" . $sample_import_id .".csv";

	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=$fn");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
	Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache");                          // HTTP/1.0

	echo("token,".T_("Case ID")."");
	foreach($svars as $s)
	{
		echo("," . $s['value']);
	}
	
	if (isset($_GET['sample']))
	{
		echo(",".T_("Current Outcome").",".T_("Number of call attempts").",".T_("Number of calls").",".T_("Case notes").",".T_("Total interview time over all calls (mins)").",".T_("Interview time for last call (mins)").",".T_("Last number dialled").",".T_("DATE/TIME Last number dialled").",".T_("Operator username for last call").",".T_("Shift report").", AAPOR");
	}
	
	echo("\n");

	$sql = "SELECT c.token,c.case_id ";

	if (isset($_GET['sample'])) $sql .= ", o.description, 
	(SELECT COUNT(ca.call_attempt_id) FROM `call_attempt` as ca WHERE ca.case_id = c.case_id ) as callattempts,
	(SELECT COUNT(cl.call_id) FROM `call` as cl WHERE cl.case_id = c.case_id ) as calls,
	(SELECT GROUP_CONCAT(cn1.note SEPARATOR '|') FROM `case_note`  as cn1 WHERE c.case_id = cn1.case_id GROUP BY cn1.case_id)as casenotes,
	(SELECT ROUND(SUM( TIMESTAMPDIFF(SECOND , cl2.start,IFNULL(cl2.end,CONVERT_TZ(NOW(),'System','UTC'))))/60,2) FROM `call_attempt` as cl2	WHERE cl2.case_id = c.case_id) as interviewtimec,
	(SELECT ROUND(TIMESTAMPDIFF(SECOND , cl3.start,IFNULL(cl3.end,CONVERT_TZ(NOW(),'System','UTC')))/60,2) 	FROM `call_attempt` as cl3 WHERE cl3.case_id = c.case_id ORDER BY cl3.call_attempt_id DESC LIMIT 1) as interviewtimel,
	(SELECT cp1.phone FROM `call` as cl4, `contact_phone` as cp1 WHERE cl4.call_id = c.last_call_id AND cp1.contact_phone_id = cl4.contact_phone_id ) as lastnumber,
	(SELECT cl55.start  FROM `call` as cl55 WHERE cl55.call_id = c.last_call_id ) as lastcallstart,
	(SELECT op1.username FROM `call` as cl5, `operator` as op1 WHERE cl5.call_id = c.last_call_id AND op1.operator_id = cl5.operator_id) as operatoru, 
	(SELECT GROUP_CONCAT(DISTINCT sr1.report SEPARATOR '|') FROM `call` as cl6, `shift` as sh1, `shift_report` as sr1 WHERE cl6.case_id = c.case_id AND sr1.shift_id = sh1.shift_id AND sh1.questionnaire_id = c.questionnaire_id AND cl6.start >= sh1.start AND cl6.end < sh1.end GROUP BY sr1.shift_id) as shiftr,
	o.aapor_id ";

	$i = 0;
	foreach ($svars as $s)
	{
		$sql .= ", sv$i.val as v$i";
		$i++;
	}

	$sql .= " FROM sample ";

	//left join if getting whole sample file
	if (isset($_GET['sample'])) $sql .= "LEFT ";

	$sql .= "JOIN `case` as c ON (c.questionnaire_id = '$questionnaire_id' AND c.sample_id = `sample`.sample_id) ";

	if (isset($_GET['sample'])) $sql .= " LEFT JOIN `outcome` as o ON (o.outcome_id = c.current_outcome_id)";

	$i = 0;
	foreach ($svars as $s)
	{
		$sql .= " LEFT JOIN sample_var AS sv$i ON (sv$i.sample_id = `sample`.sample_id AND sv$i.var_id = '{$s['var_id']}') ";
		$i++;
	}

	$sql .= " WHERE `sample`.import_id = '$sample_import_id'";

	$list = $db->GetAll($sql);

	if (!empty($list))
	{
		foreach($list as $l)
		{
			echo $l['token'] . "," . $l['case_id'];
			$i = 0;
			foreach ($svars as $s)
			{
				echo "," . str_replace(","," ",$l["v$i"]);
				$i++;
			}
			if (isset($_GET['sample']))
			{
				$l['description'] = T_($l['description']);
				echo "," . str_replace(","," ",$l['description']) . "," .$l['callattempts']."," .$l['calls']."," .$l['casenotes'].",".$l['interviewtimec'].",".$l['interviewtimel'].",".$l['lastnumber'].",".$l['lastcallstart'].",".$l['operatoru'].",".$l['shiftr'].",". $l['aapor_id'];
			}
			echo  "\n";
		}
	}
	exit;
}

if (isset($_GET['sample_var'])){ 
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sample_import_id = bigintval($_GET['sample_import_id']);
	$varid = intval($_GET['sample_var']);

	$sql = "SELECT c.token, c.case_id, sv.val, sivr.var
		FROM `case` as c, `sample_import_var_restrict` as sivr, `sample_var` as sv
		WHERE c.questionnaire_id = $questionnaire_id
		AND sivr.sample_import_id = $sample_import_id
		AND c.sample_id = sv.sample_id 
		AND sivr.var_id = sv.var_id
		AND sivr.var_id = $varid";

	$list = $db->GetAll($sql);
	$sample_var = $list[0]['var'];

	$fn = "key-" . $sample_var . "_Qid=$questionnaire_id" . "_Sid=" . $sample_import_id .".csv";

	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=$fn");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
	Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache");                          // HTTP/1.0

	echo("token,caseid,$sample_var\n");

	if (!empty($list))
	{
		foreach($list as $l)
		{
			echo $l['token'] . "," . $l['case_id'] . "," . $l['val'] . "\n";
		}
	}

	exit;
}


xhtml_head(T_("Data output"),true,array("../include/bootstrap/css/bootstrap.min.css","../css/custom.css"),array("../js/window.js"));

print "<div class='form-group clearfix'><h3 class='col-sm-4 text-right'>" . T_("Please select a questionnaire") . ":&emsp;</h3>";
$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);
display_questionnaire_chooser($questionnaire_id,false,"form-inline col-sm-3 pull-left", "form-control");

if ($questionnaire_id)
{
	$sql = "SELECT lime_sid 
		FROM questionnaire
		WHERE questionnaire_id = $questionnaire_id";

	$ls = $db->GetRow($sql);
	$lsid = $ls['lime_sid'];

	print "&emsp;<a href='" . LIME_URL . "admin/admin.php?action=exportresults&amp;sid=$lsid' class='btn btn-default fa btn-lime'>".  T_("Download data for this questionnaire via Limesurvey") . "</a></div>";

	print "<div class='form-group clearfix'><h3 class='col-sm-4 text-right'>" . T_("Please select a sample") . ":&emsp;</h3>";
	$sample_import_id = false;
	if (isset($_GET['sample_import_id'])) $sample_import_id = bigintval($_GET['sample_import_id']);
	display_sample_chooser($questionnaire_id,$sample_import_id,false,"form-inline col-sm-3 pull-left", "form-control");

	if ($sample_import_id)
	{
		print "&emsp;<a href='" .LIME_URL . "admin/admin.php?action=exportresults&amp;sid=$lsid&amp;quexsfilterinc=$questionnaire_id:$sample_import_id' class='btn btn-default fa btn-lime'>" . T_("Download data for this sample via Limesurvey") . "</a></div>";
		//get sample vars
		$sql = "SELECT sivr.var_id as value, sivr.var as description
		FROM `sample_import_var_restrict` as sivr
		WHERE sivr.sample_import_id = $sample_import_id";
		$rs = $db->GetAll($sql);
	
		//download a key file linking the caseid to the sample
		print "<div class='form-group '><h3 class='col-sm-4 text-right'>" . T_("Download key file: select sample var") . ":&emsp;</h3>";

		display_chooser($rs,"sample_var","sample_var",true,"questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id",true,true,false,true,"form-inline col-sm-3 pull-left");
		
		print "</div><div class=' col-sm-4'>";
		
		//download complete key file
		print "<a href='?key=key&amp;questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id' class='btn btn-default fa'>" . T_("Download complete key file") . "</a></br></br>";

		//download complete sample file with outcomes
		print "<a href='?sample=sample&amp;questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id' class='btn btn-default fa'>" . T_("Download complete sample file with current outcomes") . "</a></div>";
	}
}

xhtml_foot();

?>
