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

if (isset($_POST['firstname']))
{
	//validate email address
	if (validate_email($_POST['email']))
	{		
		$case_id = get_case_id($operator_id);
		$lime_sid = get_lime_sid($case_id);
		$ca = get_call_attempt($operator_id);
		$token = get_token($case_id);
		$email = $db->qstr($_POST['email']);
		$firstname = $db->qstr($_POST['firstname']);
		$lastname = $db->qstr($_POST['lastname']);

		//update the limesurvey database email details
		$sql = "UPDATE " . LIME_PREFIX ."tokens_{$lime_sid}
			SET email = $email, firstname = $firstname, lastname = $lastname, emailstatus = 'OK'
			WHERE token = '$token'";

		$db->Execute($sql);

		//Send email
		include_once("include/limesurvey/classes/phpmailer/class.phpmailer.php");
		$fieldsarray = array();
		$fieldsarray["{EMAIL}"]=$_POST['email'];
		$fieldsarray["{FIRSTNAME}"]=$_POST['firstname'];
		$fieldsarray["{LASTNAME}"]=$_POST['lastname'];
		$fieldsarray["{TOKEN}"]=$token;
		$fieldsarray["{LANGUAGE}"]=DEFAULT_LOCALE;
		$fieldsarray["{SID}"]=$fieldsarray["{SURVEYID}"]=$lime_sid;
		//$fieldsarray["{SURVEYNAME}"]=$thissurvey["surveyls_title"];

		$sql = "SELECT sivr.var,sv.val
			FROM `sample_var` as sv, `sample_import_var_restrict` as sivr, `case` as c
			WHERE c.case_id = $case_id
			AND sv.sample_id = c.sample_id
			AND sivr.var_id = sv.var_id";

		$attributes = $db->GetAssoc($sql);

		//Assign sample variables
		foreach ($attributes as $attributefield=>$val)
		{
			$fieldsarray['{SAMPLE:'.strtoupper($attributefield).'}']=$val;
		}

		$fieldsarray["{OPTOUTURL}"]=LIME_URL . "optout.php?lang=".trim(DEFAULT_LOCALE)."&sid=$lime_sid&token={$token}";
		$fieldsarray["{SURVEYURL}"]=LIME_URL . "index.php?lang=".trim(DEFAULT_LOCALE)."&sid=$lime_sid&token={$token}";
		$barebone_link=$fieldsarray["{SURVEYURL}"];
                       
		$customheaders = array( '1' => "X-surveyid: ".$lime_sid, '2' => "X-tokenid: ".$fieldsarray["{TOKEN}"]);

		$sql = "SELECT surveyls_email_invite_subj, surveyls_email_invite
			FROM `lime_surveys_languagesettings`
			WHERE `surveyls_survey_id` = $lime_sid
			order by surveyls_language LIKE '" . DEFAULT_LOCALE . "' DESC";

		$econtent = $db->GetRow($sql);

		$modsubject =  $econtent['surveyls_email_invite_subj'];
		$modmessage =  $econtent['surveyls_email_invite'];

		foreach ( $fieldsarray as $key => $value )
		{   
			$modsubject=str_replace($key, $value, $modsubject);
		}

		foreach ( $fieldsarray as $key => $value )
		{   
			$modmessage=str_replace($key, $value, $modmessage);
		}

		$modsubject = str_replace("@@SURVEYURL@@", $barebone_link, $modsubject);
		$modmessage = str_replace("@@SURVEYURL@@", $barebone_link, $modmessage);

		$mail = new PHPMailer;

		$mail->CharSet = 'UTF-8';

		$sql = "SELECT stg_value
			FROM " . LIME_PREFIX . "settings_global
			WHERE stg_name = 'emailsmtpssl'";

		$emailsmtpssl = $db->GetOne($sql);

		if (isset($emailsmtpssl) && trim($emailsmtpssl)!=='' && $emailsmtpssl!==0) 
		{
			if ($emailsmtpssl===1) 
			{
				$mail->SMTPSecure = "ssl";
			}
			else 
			{
				$mail->SMTPSecure = $emailsmtpssl;
			}
		}


		$sql = "SELECT stg_value
			FROM " . LIME_PREFIX . "settings_global
			WHERE stg_name = 'emailmethod'";

		$emailmethod = $db->GetOne($sql);

		    switch ($emailmethod) {
			case "qmail":
			    $mail->IsQmail();
			    break;
			case "smtp":
			    $mail->IsSMTP();
			    
				$sql = "SELECT stg_name,stg_value
					FROM " . LIME_PREFIX . "settings_global";				
				$ec =$db->GetAssoc($sql);
		
			    if (strpos($ec['emailsmtphost'],':')>0)
			    {
				$mail->Host = substr($ec['emailsmtphost'],0,strpos($ec['emailsmtphost'],':'));
				$mail->Port = substr($ec['emailsmtphost'],strpos($ec['emailsmtphost'],':')+1);
			    }
			    else {
				$mail->Host = $ec['emailsmtphost'];
			    }
			    $mail->Username =$ec['emailsmtpuser'];
			    $mail->Password =$ec['emailsmtppassword'];
			    if (trim($ec['emailsmtpuser'])!="")
			    {
				$mail->SMTPAuth = true;
			    }
			    break;
			case "sendmail":
			    $mail->IsSendmail();
			    break;
			default:
			    //Set to the default value to rule out incorrect settings.
			    $emailmethod="mail";
			    $mail->IsMail();
		    }


		$sql = "SELECT admin,adminemail
			FROM " . LIME_PREFIX . "surveys
			WHERE sid = $lime_sid";

		$from = $db->GetRow($sql);

		$mail->SetFrom($from['adminemail'],$from['admin']);
		$mail->Sender = $from['adminemail'];

		$mail->AddAddress($_POST['email']);
		foreach ($customheaders as $key=>$val) 
		{
			$mail->AddCustomHeader($val);
		}
		$mail->AddCustomHeader("X-Surveymailer: queXS Emailer (quexs.sourceforge.net)");

		$mail->IsHTML(true);
		$mail->Body = $modmessage;
		$mail->AltBody = trim(strip_tags(html_entity_decode($modmessage,ENT_QUOTES,'UTF-8')));
		$mail->Subject = $modsubject;


		if ($mail->Send())
		{
			// Put call attempt id in to sent
			$sql = "UPDATE ". LIME_PREFIX . "tokens_{$lime_sid}
				SET sent='$ca' 
				WHERE token='$token'";

			$db->Execute($sql);

			//Add a note that sent

			$sql = "INSERT INTO `case_note` (case_id,operator_id,note,datetime)
				VALUES ($case_id,$operator_id,'" . TQ_("Self completion invitation sent via email to") . ": " . $_POST['email'] . "',CONVERT_TZ(NOW(),'System','UTC'))";

			$db->Execute($sql);

			//set to start frm the first page if the format for the respondent is not question by question
			$sql = "SELECT q.lime_mode
				FROM questionnaire as q, `case` as c
				WHERE c.case_id = $case_id
				AND q.questionnaire_id = c.questionnaire_id";

			$lmode = $db->GetOne($sql);

			if ($lmode != "question")
			{
				$sql = "UPDATE " . LIME_PREFIX ."survey_{$lime_sid}
					SET lastpage = 0
					WHERE token = '$token'";
	
				$db->Execute($sql);
			}

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
				if (isset($_GET['interface2']))
				{
					xhtml_head(T_("Email"),true,array("css/call.css"),array($js),"onload='openParent(\"endcase=endcase\");'");
				}
				else
				{
					xhtml_head(T_("Email"),true,array("css/call.css"),array($js),"onload='toggleRec(\"$newtext\",\"record.php?start=start\",\"offline\"); openParentObject(\"main-content\",\"" . get_respondentselection_url($operator_id) . "\"); parent.closePopup();'");
				}
	
			}
			else if (isset($_POST['submit']))
			{
				xhtml_head(T_("Email"),true,array("css/call.css"),false,"onload='parent.closePopup();'");
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

$js = "js/window.js";
if (browser_ie()) $js = "js/window_ie6.js";

xhtml_head(T_("Email"),true,array("css/call.css"),array($js));

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
	
	print "<div class='status'>" . T_("Email respondent for self completion") . "</div>";
	if (!empty($msg)) print "<p>$msg</p>";
	print "<form action='?";
	if (isset($_GET['interface2']))
	{
		print "interface2=true";
	}
	print "' method='post'>";
	print "<div><label for='firstname'>" . T_("First name") . "</label><input type='text' value='{$rs['firstname']}' name='firstname' id='firstname'/></div>";
	print "<div><label for='lastname'>" . T_("Last name") . "</label><input type='text' value='{$rs['lastname']}' name='lastname' id='lastname'/></div>";
	print "<div><label for='email'>" . T_("Email") . "</label><input type='text' value='{$rs['email']}' name='email' id='email'/></div>";
	if (!isset($_GET['interface2']))
	{
		print "<div><input type='submit' value=\"" . T_("Send invitation") . "\" name='submit' id='submit'/></div>";
	}
	print "<div><input type='submit' value=\"" . T_("Send invitation and Hang up") . "\" name='submith' id='submith'/></div></form>";
}
else
{
	print "<p>" . T_("Self completion email not available for this questionnaire") . "</p>";
}

xhtml_foot();

?>
