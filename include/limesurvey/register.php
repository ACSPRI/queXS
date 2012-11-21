<?php
/*
 * LimeSurvey
 * Copyright (C) 2007 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 * $Id: register.php 11664 2011-12-16 05:19:42Z tmswhite $
 */

// Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

require_once(dirname(__FILE__).'/classes/core/startup.php');    // Since this file can be directly run
require_once(dirname(__FILE__).'/config-defaults.php');
require_once(dirname(__FILE__).'/common.php');
require_once($rootdir.'/classes/core/language.php');

$surveyid=returnglobal('sid');
$postlang=returnglobal('lang');

//Check that there is a SID
if (!isset($surveyid))
{
    //You must have an SID to use this
    include "index.php";
    exit;
}


$usquery = "SELECT stg_value FROM ".db_table_name("settings_global")." where stg_name='SessionName'";
$usresult = db_execute_assoc($usquery,'',true);          //Checked
if ($usresult)
{
    $usrow = $usresult->FetchRow();
    $stg_SessionName=$usrow['stg_value'];
    @session_name($stg_SessionName.'-runtime-'.$surveyid);
}
else
{
    session_name("LimeSurveyRuntime-$surveyid");
}

session_set_cookie_params(0,$relativeurl.'/');
session_start();

// Get passed language from form, so that we dont loose this!
if (!isset($postlang) || $postlang == "")
{
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    $clang = new limesurvey_lang($baselang);
} else {
    $clang = new limesurvey_lang($postlang);
    $baselang = $postlang;
}

$thissurvey=getSurveyInfo($surveyid,$baselang);

$register_errormsg = "";

// Check the security question's answer
if (function_exists("ImageCreate") && captcha_enabled('registrationscreen',$thissurvey['usecaptcha']) )
{
    if (!isset($_POST['loadsecurity']) ||
    !isset($_SESSION['secanswer']) ||
    $_POST['loadsecurity'] != $_SESSION['secanswer'])
    {
        $register_errormsg .= $clang->gT("The answer to the security question is incorrect.")."<br />\n";
    }
}

//Check that the email is a valid style address
if (!validate_email(returnglobal('register_email')))
{
    $register_errormsg .= $clang->gT("The email you used is not valid. Please try again.");
}

if ($register_errormsg != "")
{
    include "index.php";
    exit;
}

//Check if this email already exists in token database
$query = "SELECT email FROM {$dbprefix}tokens_$surveyid\n"
. "WHERE email = ".db_quoteall(sanitize_email(returnglobal('register_email')));
$result = $connect->Execute($query) or safe_die ($query."<br />".$connect->ErrorMsg());   //Checked
if (($result->RecordCount()) > 0)
{
    $register_errormsg=$clang->gT("The email you used has already been registered.");
    include "index.php";
    exit;
}

$mayinsert = false;
while ($mayinsert != true)
{
	$tlquery = "SELECT tokenlength FROM ".db_table_name("surveys")." WHERE sid=$surveyid";
	$tlresult = db_execute_assoc($tlquery);
	while ($tlrow = $tlresult->FetchRow())
	{
		$tokenlength = $tlrow['tokenlength'];
	}
	$newtoken = sRandomChars($tokenlength);
    $ntquery = "SELECT * FROM {$dbprefix}tokens_$surveyid WHERE token='$newtoken'";
    $ntresult = $connect->Execute($ntquery); //Checked
    if (!$ntresult->RecordCount()) {$mayinsert = true;}
}

$postfirstname=sanitize_xss_string(strip_tags(returnglobal('register_firstname')));
$postlastname=sanitize_xss_string(strip_tags(returnglobal('register_lastname')));
/*$postattribute1=sanitize_xss_string(strip_tags(returnglobal('register_attribute1')));
 $postattribute2=sanitize_xss_string(strip_tags(returnglobal('register_attribute2')));   */

//Insert new entry into tokens db
$query = "INSERT INTO {$dbprefix}tokens_$surveyid\n"
. "(firstname, lastname, email, emailstatus, token)\n"
. "VALUES (?, ?, ?, ?, ?)";
$result = $connect->Execute($query, array($postfirstname,
$postlastname,
returnglobal('register_email'),
                                          'OK',
$newtoken)
//                             $postattribute1,   $postattribute2)
) or safe_die ($query."<br />".$connect->ErrorMsg());  //Checked - According to adodb docs the bound variables are quoted automatically
$tid=$connect->Insert_ID("{$dbprefix}tokens_$surveyid","tid");


$fieldsarray["{ADMINNAME}"]=$thissurvey['adminname'];
$fieldsarray["{ADMINEMAIL}"]=$thissurvey['adminemail'];
$fieldsarray["{SURVEYNAME}"]=$thissurvey['name'];
$fieldsarray["{SURVEYDESCRIPTION}"]=$thissurvey['description'];
$fieldsarray["{FIRSTNAME}"]=$postfirstname;
$fieldsarray["{LASTNAME}"]=$postlastname;
$fieldsarray["{EXPIRY}"]=$thissurvey["expiry"];
$fieldsarray["{TOKEN}"]=$newtoken;
$fieldsarray["{SID}"]=$fieldsarray["{SURVEYID}"]=$surveyid;

$message=$thissurvey['email_register'];
$subject=$thissurvey['email_register_subj'];


$from = "{$thissurvey['adminname']} <{$thissurvey['adminemail']}>";

if (getEmailFormat($surveyid) == 'html')
{
    $useHtmlEmail = true;
    $fieldsarray["{SURVEYURL}"]="<a href='$publicurl/index.php?lang=".$baselang."&sid=$surveyid&token=$newtoken'>".htmlspecialchars("$publicurl/index.php?lang=".$baselang."&sid=$surveyid&token=$newtoken")."</a>";
    $fieldsarray["{OPTOUTURL}"]="<a href='$publicurl/optout.php?lang=".$baselang."&sid=$surveyid&token=$newtoken'>".htmlspecialchars("$publicurl/optout.php?lang=".$baselang."&sid=$surveyid&token=$newtoken")."</a>";
}
else
{
    $useHtmlEmail = false;
    $fieldsarray["{SURVEYURL}"]="$publicurl/index.php?lang=".$baselang."&sid=$surveyid&token=$newtoken";
    $fieldsarray["{OPTOUTURL}"]="$publicurl/optout.phplang=".$baselang."&sid=$surveyid&token=$newtoken";
}

$message=ReplaceFields($message, $fieldsarray);
$subject=ReplaceFields($subject, $fieldsarray);

$html=""; //Set variable

if (SendEmailMessage(null, $message, $subject, returnglobal('register_email'), $from, $sitename,$useHtmlEmail,getBounceEmail($surveyid)))
{
    // TLR change to put date into sent
    //	$query = "UPDATE {$dbprefix}tokens_$surveyid\n"
    //			."SET sent='Y' WHERE tid=$tid";
    $today = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i", $timeadjust);
    $query = "UPDATE {$dbprefix}tokens_$surveyid\n"
    ."SET sent='$today' WHERE tid=$tid";
    $result=$connect->Execute($query) or safe_die ("$query<br />".$connect->ErrorMsg());     //Checked
    $html="<center>".$clang->gT("Thank you for registering to participate in this survey.")."<br /><br />\n".$clang->gT("An email has been sent to the address you provided with access details for this survey. Please follow the link in that email to proceed.")."<br /><br />\n".$clang->gT("Survey Administrator")." {ADMINNAME} ({ADMINEMAIL})";
    $html=ReplaceFields($html, $fieldsarray);
    $html .= "<br /><br /></center>\n";
}
else
{
    $html="Email Error";
}

//PRINT COMPLETED PAGE
if (!$thissurvey['template'])
{
    $thistpl=sGetTemplatePath(validate_templatedir('default'));
}
else
{
    $thistpl=sGetTemplatePath(validate_templatedir($thissurvey['template']));
}

sendcacheheaders();
doHeader();

foreach(file("$thistpl/startpage.pstpl") as $op)
{
    echo templatereplace($op);
}
foreach(file("$thistpl/survey.pstpl") as $op)
{
    echo "\t".templatereplace($op);
}
echo $html;
foreach(file("$thistpl/endpage.pstpl") as $op)
{
    echo templatereplace($op);
}
doFooter();

// Closing PHP tag is intentially left out (yes, it's fine!)
