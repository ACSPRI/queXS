<?php 
/**
 * Popup screen to manage calling and hanging up and assigning outcomes to calls
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
 * LimeSurvey functions
 */
include("functions/functions.limesurvey.php");


global $db;

$operator_id = get_operator_id();

$msg = "";

if (isset($_POST['firstname']))
{
	//validate email address
	if (validate_email($_POST['email']))
	{		
		$case_id = get_case_id($operator_id);
		$lime_sid = get_lime_sid($case_id);
		$token = get_token($case_id);
		$email = $db->qstr($_POST['email']);
		$firstname = $db->qstr($_POST['firstname']);
		$lastname = $db->qstr($_POST['lastname']);

		//update the limesurvey database email details
		$sql = "UPDATE " . LIME_PREFIX ."tokens_{$lime_sid}
			SET email = $email, firstname = $firstname, lastname = $lastname, emailstatus = 'OK'
			WHERE token = '$token'";

		$db->Execute($sql);


		if (isset($_POST['submith']))
		{
			end_call($operator_id,41); //end with outcome sent 
			if (is_voip_enabled($operator_id))
			{
				include("functions/functions.voip.php");
				$v = new voip();
				$v->connect(VOIP_SERVER);
				$v->hangup(get_extension($operator_id));
			}
			//disable recording
			$newtext = T_("Start REC");
				$js = "js/window.js";
			if (browser_ie()) $js = "js/window_ie6.js";
			xhtml_head(T_("Email"),true,array("css/call.css"),array($js),"onload='toggleRec(\"$newtext\",\"record.php?start=start\",\"offline\"); openParentObject(\"main-content\",\"" . get_respondentselection_url($operator_id) . "\"); parent.closePopup();'");

		}
		else if (isset($_POST['submit']))
		{
			$call_id = get_call($operator_id);

			$sql = "UPDATE `call` as c
				SET c.outcome_id = 41
				WHERE c.call_id = $call_id";

			$db->Execute($sql);
		
			xhtml_head(T_("Email"),true,array("css/call.css"),array($js),"onload='parent.closePopup();'");
		}
		xhtml_foot();
		die();
	}
	else
	{
		$msg = T_("The email address is not valid");
	}
}

$case_id = get_case_id($operator_id);

$js = "js/window.js";
if (browser_ie()) $js = "js/window_ie6.js";

xhtml_head(T_("Email"),true,array("css/call.css"),array($js));


$sql = "SELECT sv1.val as firstname, sv2.val as lastname, sv3.val as email
	FROM `case` as c
	LEFT JOIN sample_var as sv1 on (sv1.sample_id = c.sample_id AND sv1.type = 6)
	LEFT JOIN sample_var as sv2 on (sv2.sample_id = c.sample_id AND sv2.type = 7)
	LEFT JOIN sample_var as sv3 on (sv3.sample_id = c.sample_id AND sv3.type = 8)
	WHERE c.case_id = $case_id";

$rs = $db->GetRow($sql);

print "<div class='status'>" . T_("Email respondent for self completion") . "</div>";
if (!empty($msg)) print "<p>$msg</p>";
print "<form action='?' method='post'>";
print "<div><label for='firstname'>" . T_("First name") . "</label><input type='text' value='{$rs['firstname']}' name='firstname' id='firstname'/></div>";
print "<div><label for='lastname'>" . T_("Last name") . "</label><input type='text' value='{$rs['lastname']}' name='lastname' id='lastname'/></div>";
print "<div><label for='email'>" . T_("Email") . "</label><input type='text' value='{$rs['email']}' name='email' id='email'/></div>";
print "<div><input type='submit' value='" . T_("Send invitation") . "' name='submit' id='submit'/></div>";
print "<div><input type='submit' value='" . T_("Send invitation and Hang up") . "' name='submith' id='submith'/></div></form>";


xhtml_foot();

?>
