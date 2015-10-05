<?php 
/**
 * Popup screen to manage referrals
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2013
 * @package queXS
 * @subpackage user
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include ("config.inc.php");

/**
 * Database file
 */
include ("db.inc.php");

/** 
 * Authentication
 */
require ("auth-interviewer.php");


/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include("functions/functions.operator.php");

/**
 * Input functions
 */
include("functions/functions.input.php");

/**
 * Import functions
 */
include("functions/functions.import.php");

global $db;

$operator_id = get_operator_id();

$msg = "";

if (isset($_POST['submit']))
{
	$case_id = get_case_id($operator_id);

	$sql = "SELECT sivr.var
			FROM `sample_import_var_restrict` as sivr, `sample_var` as s, `case` as c
			WHERE c.case_id = '$case_id'
            AND s.var_id = sivr.var_id
			AND s.sample_id = c.sample_id
			AND sivr.type = 3";

	$pphone = $db->GetOne($sql);

	//validate primary phone number supplied
	if (isset($_POST["v_$pphone"]) && only_numbers($_POST["v_$pphone"]) != "")
	{		
		$phone = $db->qstr(only_numbers($_POST["v_$pphone"]));

		//Create a new sample record and add CASEREFERREDFROM to be this current case id
		$db->StartTrans();

		//create a new sample entry
		$sql = "SELECT s.import_id
			FROM `sample` as s, `case` as c
			WHERE c.case_id = '$case_id'
			AND s.sample_id = c.sample_id";

		$import_id = $db->GetOne($sql);

		//get all sample records
		$sql = "SELECT sivr.var,s.val, sivr.type
			FROM `sample_import_var_restrict` as sivr, `sample_var` as s, `case` as c
			WHERE c.case_id = '$case_id'
			AND s.sample_id = c.sample_id
			AND s.var_id = sivr.var_id";

		$rs = $db->GetAll($sql);

		$tzone = get_setting("DEFAULT_TIME_ZONE"); //set this to default

		//Get the timezone
		foreach($rs as $r)
		{                
			$tz = get_time_zone($_POST["v_" . $r['var']],$r['type']);
			if ($tz !== false)
			{
				$tzone = $tz;
				break;
			}
		}

		$sql = "INSERT INTO `sample` (import_id,Time_zone_name,phone)
			VALUES ($import_id,'$tzone',$phone)";

		$db->Execute($sql);

		$sample_id = $db->Insert_ID();

		//insert sample var records
		foreach($rs as $r)
		{
			
			$sql = "INSERT INTO `sample_import_var_restrict` (`var`,`type`)
					VALUES ('{$r['var']}','{$r['type']}')";
			$db->Execute($sql);

			$varid = $db->Insert_ID();
			
			$sql = "INSERT INTO `sample_var` (`sample_id`,`var_id`,`val`)
				VALUES ('$sample_id','$varid'," . $db->qstr($_POST['v_' . $r['var']]) . ")";
			$db->Execute($sql);

		}

		//Add CASEREFERREDFROM record
		$sql = "INSERT INTO `sample_import_var_restrict` (`var`,`type`)
				VALUES ('CASEREFERREDFROM','1')";
		$db->Execute($sql);
		
		$varid = $db->Insert_ID();
		
		$sql = "INSERT INTO `sample_var` (`sample_id`,`var_id`,`val`)
			VALUES ('$sample_id','$varid','$case_id')";

		$db->Execute($sql);

		//Create a new case
		$sql = "SELECT questionnaire_id
			FROM `case`
			WHERE case_id = '$case_id'";

		$questionnaire_id = $db->GetOne($sql);	

		$ncase_id = add_case($sample_id,$questionnaire_id);

		//If selected to call now - assign to this operator
		if (isset($_POST['makecase']))
		{
			$sql = "SELECT MAX(sortorder)
				FROM case_queue
				WHERE operator_id = '$operator_id'";
		
			$sortorder = $db->GetOne($sql);
			
			$sortorder++;

			$sql = "INSERT INTO case_queue (case_id,operator_id,sortorder)
				VALUES ('$ncase_id', '$operator_id', '$sortorder')";

			$db->Execute($sql);
	
		}

		//Add a note that we have referred another case
		$sql = "INSERT INTO `case_note` (case_id,operator_id,note,datetime)
			VALUES ($case_id,$operator_id,'" . T_("Generated referral to case id") . ": $ncase_id',CONVERT_TZ(NOW(),'System','UTC'))";

		$db->Execute($sql);

		//Add a note that it is referred from another case
		$sql = "INSERT INTO `case_note` (case_id,operator_id,note,datetime)
			VALUES ($ncase_id,$operator_id,'" . T_("Generated as referral from case id") . ": $case_id',CONVERT_TZ(NOW(),'System','UTC'))";

		$db->Execute($sql);

		if ($db->CompleteTrans())
		{
			$msg = "<p class='alert alert-info'>" . T_("Created referral case - you may now close this window") . "</p>";
		}
		else
		{
			$msg = "<p class='alert alert-warning'>" . T_("Failed to create referral case - please check your input and try again") . "</p>";
		}

	}
	else
	{
		$msg = "<p class='alert alert-warning'>" . T_("You must supply a primary phone number") . "</p>";
	}
}

$case_id = get_case_id($operator_id);

if (isset($_GET['interface2'])) { if (browser_ie()) $js = "js/window_ie6_interface2.js"; else $js = "js/window_interface2.js"; } 
else { if (browser_ie()) $js = "js/window_ie6.js"; else $js = "js/window.js"; }

xhtml_head(T_("Referral"),false,array("include/bootstrap/css/bootstrap.min.css"),array($js));

$sql = "SELECT q.referral
	FROM questionnaire as q, `case` as c
	WHERE c.case_id = $case_id 
	AND c.questionnaire_id = q.questionnaire_id";

$sc = $db->GetOne($sql);

if ($sc == 1)
{
	print "<div class='col-md-12 '><h3>" . T_("Create referral") . "</h3>";
	if (!empty($msg)) print $msg;
	print "<form action='?' method='post' class='form-horizontal'>";

	//Create a list of sample records matching this current case 

	$sql = "SELECT sivr.var,t.description,sivr.type, sv.val 
		FROM `sample_import_var_restrict` as sivr,`sample_var` as sv, `case` as c, `sample_var_type` as t
		WHERE c.case_id = '$case_id'
		AND sv.sample_id = c.sample_id
		AND sv.var_id = sivr.var_id
		AND sivr.type = t.type";

	$rs = $db->GetAll($sql);

	foreach ($rs as $r)
	{
		$var = $r['var'];
		print "<label for='v_$var' class='control-label'>";

		if ($r['type'] != 1)
				print T_($r['description']);		
		else
				print $var;

		print "</label><div><input type='text' name='v_$var' id='v_$var' class='form-control'";

		if (isset($_POST['v_' . $var]))
			print "value='" . $_POST['v_' .$var] . "' ";
		
		if ($r['type'] == 3) print "required";
		
		print " /></div>";
	}

	print "<br/><p><label for='makecase' class='control-label pull-left'>" . T_("Call this new referral immediately after this case?") .  "&emsp;</label> <input type='checkbox' name='makecase' id='makecase' checked='checked'/></p>";

	print "<input type='submit' value='" . T_("Create referral") . "' name='submit' id='submit' class='btn btn-primary'/>";
		print "<div class='col-md-6 pull-right'><a class='btn btn-default pull-right' href='javascript:parent.closePopup();'>".T_("Cancel")."</a></div><div class='clearfix'></div>";

	print "</form></div>";
}
else
{
	print "<p>" . T_("Referrals not available for this questionnaire") . "</p>";
}

xhtml_foot();

?>
