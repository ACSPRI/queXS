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
 * LimeSurvey functions
 */
include("functions/functions.limesurvey.php");


global $db;

$operator_id = get_operator_id();

$msg = "";

if (isset($_POST['email']) && ((isset($_POST['firstname']) && !empty($_POST['firstname'])) || (isset($_POST['lastname']) && !empty($_POST['lastname']))))
{
	//validate email address
	if (validate_email($_POST['email']))
	{		
		$case_id = get_case_id($operator_id);
		$lime_sid = get_lime_sid($case_id);
		$ca = get_call_attempt($operator_id);
		$token = get_token($case_id);
		$email = ($_POST['email']);
		$firstname = ($_POST['firstname']);
		$lastname = ($_POST['lastname']);

    
    $ret = lime_send_email($case_id,$email,$firstname,$lastname);

		if ($ret) //if mail sent 
		{
			//Add a note that sent

			$sql = "INSERT INTO `case_note` (case_id,operator_id,note,datetime)
				VALUES ($case_id,$operator_id,'" . TQ_("Self completion invitation sent via email to") . ": " . $_POST['email'] . "',CONVERT_TZ(NOW(),'System','UTC'))";

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
				
				if (isset($_GET['interface2'])) { if (browser_ie()) $js = "js/window_ie6_interface2.js"; else $js = "js/window_interface2.js";} 
				else { if (browser_ie()) $js = "js/window_ie6.js"; else $js = "js/window.js"; }
				
				if (isset($_GET['interface2']))
				{
					xhtml_head(T_("Invitation Email"),true,array("css/call.css"),array($js),"onload='openParent(\"endcase=endcase\");'");
				}
				else
				{
					xhtml_head(T_("Invitation Email"),true,array("css/call.css"),array($js),"onload='toggleRec(\"$newtext\",\"record.php?start=start\",\"offline\"); openParentObject(\"main-content\",\"" . get_respondentselection_url($operator_id) . "\"); parent.closePopup();'");
				}
	
			}
			else if (isset($_POST['submit']))
			{
				xhtml_head(T_("Invitation Email"),true,array("css/call.css"),false,"onload='parent.closePopup();'");
			}
			xhtml_foot();
			die();
		}
		else
		{
			$msg = T_("The email did not send");
		}
	}
	else
	{
		$msg = T_("The email address is not valid");
	}
}

$case_id = get_case_id($operator_id);

if (isset($_GET['interface2'])) { if (browser_ie()) $js = "js/window_ie6_interface2.js"; else $js = "js/window_interface2.js"; } 
else { if (browser_ie()) $js = "js/window_ie6.js"; else $js = "js/window.js"; }

xhtml_head(T_("Invitation Email"),true,array("include/bootstrap/css/bootstrap.min.css"),array($js));

$sql = "SELECT q.self_complete
	FROM questionnaire as q, `case` as c
	WHERE c.case_id = $case_id 
	AND c.questionnaire_id = q.questionnaire_id";

$sc = $db->GetOne($sql);

if ($sc == 1)
{
	$sql = "SELECT 
(SELECT  sv.val from sample_var as sv, `sample_import_var_restrict` as sivr WHERE sivr.var_id = sv.var_id AND sv.sample_id = c.sample_id AND sivr.type =6) as firstname, 
(SELECT  sv.val from sample_var as sv, `sample_import_var_restrict` as sivr WHERE sivr.var_id = sv.var_id AND sv.sample_id = c.sample_id AND sivr.type =7) as lastname, 
(SELECT  sv.val from sample_var as sv, `sample_import_var_restrict` as sivr WHERE sivr.var_id = sv.var_id AND sv.sample_id = c.sample_id AND sivr.type =8) as email
		FROM `case` as c
		WHERE c.case_id = $case_id";
	
	$rs = $db->GetRow($sql);
	
	print "<h4>" . T_("Email respondent for self completion") . "</h4>";
	if (!empty($msg)) print "<p class='alert alert-warning'>$msg</p>";
	print "<form action='?";
	if (isset($_GET['interface2']))
	{
		print "interface2=true";
	}
	print "' method='post' class='form-horizontal col-md-12'>";

	print "<div class='form-group '><label for='firstname' class='control-label'>" . T_("First name") . "</label>
			<input type='text' value='{$rs['firstname']}' name='firstname' id='firstname' class='form-control'/>
			</div>";
	print "<div class='form-group '><label for='lastname' class='control-label'>" . T_("Last name") . "</label> 
			<input type='text' value='{$rs['lastname']}' name='lastname' id='lastname' class='form-control'/>
			</div>";
	print "<div class='form-group '><label for='email' class='control-label'>" . T_("Email") . "</label>
			<input type='email' value='{$rs['email']}' name='email' id='email' class='form-control' required />
			</div>";
	if (!isset($_GET['interface2'])) {
		print "<div class='form-group '><input type='submit' class='btn btn-primary' value=\"" . T_("Send invitation") . "\" name='submit' id='submit'/></div>";
	}
	print "<div class='form-group '><input type='submit' class='btn btn-primary' value=\"" . T_("Send invitation and Hang up") . "\" name='submith' id='submith'/>
			<div class='col-md-6 pull-right'><a class='btn btn-default pull-right' href='javascript:parent.closePopup();'>".T_("Cancel")."</a></div><div class='clearfix'></div>
			</div></form>";
}
else
{
	print "<p>" . T_("Self completion email not available for this questionnaire") . "</p>";
}

xhtml_foot();

?>
