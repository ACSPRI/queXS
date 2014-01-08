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

	$sql = "SELECT s.var
		FROM sample_var as s, `case` as c
		WHERE c.case_id = '$case_id'
		AND s.sample_id = c.sample_id
		AND s.type = 3";

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
		$sql = "SELECT s.var,s.val, s.type
			FROM sample_var as s, `case` as c
			WHERE c.case_id = '$case_id'
			AND s.sample_id = c.sample_id";

		$rs = $db->GetAll($sql);

		$tzone = DEFAULT_TIME_ZONE; //set this to default

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
			$sql = "INSERT INTO `sample_var` (`sample_id`,`var`,`val`,`type`)
				VALUES ('$sample_id','{$r['var']}'," . $db->qstr($_POST['v_' . $r['var']]) . ",'{$r['type']}')";
			$db->Execute($sql);
		}

		//Add CASEREFERREDFROM record
		$sql = "INSERT INTO `sample_var` (`sample_id`,`var`,`val`,`type`)
			VALUES ('$sample_id','CASEREFERREDFROM','$case_id','1')";

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
			$msg = T_("Created referral case - you may now close this window");
		}
		else
		{
			$msg = T_("Failed to create referral case - please check your input and try again");
		}

	}
	else
	{
		$msg = T_("You must supply a primary phone number");
	}
}

$case_id = get_case_id($operator_id);

$js = "js/window.js";
if (browser_ie()) $js = "js/window_ie6.js";

xhtml_head(T_("Referral"),true,array("css/call.css"),array($js));

$sql = "SELECT q.referral
	FROM questionnaire as q, `case` as c
	WHERE c.case_id = $case_id 
	AND c.questionnaire_id = q.questionnaire_id";

$sc = $db->GetOne($sql);

if ($sc == 1)
{
	print "<div class='status'>" . T_("Create referral") . "</div>";
	if (!empty($msg)) print "<p>$msg</p>";
	print "<form action='?' method='post'>";

	//Create a list of sample records matching this current case 

	$sql = "SELECT sv.var,t.description,sv.type
		FROM sample_var as sv, `case` as c, sample_var_type as t
		WHERE sv.sample_id = c.sample_id
		AND c.case_id = '$case_id'
		AND sv.type = t.type";

	$rs = $db->GetAll($sql);

	foreach ($rs as $r)
	{
		$var = $r['var'];
		print "<div><label for='v_$var'>";

		if ($r['type'] != 1)
				print T_($r['description']);		
		else
				print $var;

		print "</label><input type='text' name='v_$var' id='v_$var' ";

		if (isset($_POST['v_' . $var]))
			print "value='" . $_POST['v_' .$var] . "' ";
		
		print " /></div>";
	}

	print "<div><label for='makecase'>" . T_("Call this new referral immediately after this case?") .  "</label><input type='checkbox' name='makecase' id='makecase' checked='checked'/></div>";

	print "<div><input type='submit' value='" . T_("Create referral") . "' name='submit' id='submit'/></div>";
	print "</form>";
}
else
{
	print "<p>" . T_("Referrals not available for this questionnaire") . "</p>";
}

xhtml_foot();

?>
