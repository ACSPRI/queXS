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
* $Id: sessioncontrol.php 4916 2008-05-25 17:25:33Z c_schmitz $
*/

// Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB     

//SESSIONCONTROL.PHP FILE MANAGES ADMIN SESSIONS. 
//Ensure script is not run directly, avoid path disclosure

if (!isset($dbprefix) || isset($_REQUEST['dbprefix'])) {die("Cannot run this script directly");}

// Read the session name from the settings table
$usquery = "SELECT stg_value FROM ".db_table_name("settings_global")." where stg_name='SessionName'";
$usresult = db_execute_assoc($usquery,'',true);       // CHecked
if ($usresult)
{
	$usrow = $usresult->FetchRow();
	@session_name($usrow['stg_value']);
}
 else {session_name("LimeSurveyAdmin");}
 
 
if (session_id() == "") 
{
   if ($debug==0) {@session_start();}
    else  {session_start();}
}
//LANGUAGE ISSUES
// if changelang is called from the login page, then there is no userId 
//  ==> thus we just change the login form lang: no user profile update
// if changelang is called from another form (after login) then update user lang
// when a loginlang is specified at login time, the user profile is updated in usercontrol.php 
if (returnglobal('action') == "changelang" && (!isset($login) || !$login ))	
	{
	$_SESSION['adminlang']=returnglobal('lang');
	// if user is logged in update language in database
	if(isset($_SESSION['loginID']))
		{
		$uquery = "UPDATE {$dbprefix}users SET lang='{$_SESSION['adminlang']}' WHERE uid={$_SESSION['loginID']}";	//		added by Dennis
		$uresult = $connect->Execute($uquery); //Checked
		}
	}
elseif (!isset($_SESSION['adminlang']) || $_SESSION['adminlang']=='' )
	{
	$_SESSION['adminlang']=$defaultlang;
	}

// if changehtmleditormode is called then update user htmleditormode
if (returnglobal('action') == "changehtmleditormode" )	
	{
	$_SESSION['htmleditormode']=returnglobal('htmleditormode');
	if(isset($_SESSION['loginID']))
		{
		$uquery = "UPDATE {$dbprefix}users SET htmleditormode='{$_SESSION['htmleditormode']}' WHERE uid={$_SESSION['loginID']}";	//		added by Dennis
		$uresult = $connect->Execute($uquery) or die("Can't update htmleditor setting"); //Checked
		}
	}
elseif (!isset($_SESSION['htmleditormode']) || $_SESSION['htmleditormode']=='' )
	{
	$_SESSION['htmleditormode']=$defaulthtmleditormode;
	}

// Construct the language class, and set the language.
if (isset($_REQUEST['rootdir'])) {die('You cannot start this script directly');}
require_once($rootdir.'/classes/core/language.php');
$clang = new limesurvey_lang($_SESSION['adminlang']);

// get user rights
if(isset($_SESSION['loginID'])) {GetSessionUserRights($_SESSION['loginID']);}
	
// TIBO check wrong GET request
$dangerousActionsArray = Array
	(
		'changelang' => Array(),
		'changehtmleditormode' => Array(),
		'deluser' => Array(),
		'moduser' => Array(),
		'usertemplates' => Array(),
		'adduser' => Array(),
		'usergroupindb' => Array(),
		'editusergroupindb' => Array(),
		'deleteuserfromgroup' => Array(),
		'addusertogroup' => Array(),
		'delusergroup' => Array(),
		'mailsendusergroup' => Array(),
		'insertnewsurvey' => Array(),
		'importsurvey' => Array(),
		'updatesurvey' => Array(),
		'importsurvresources' => Array(),
		'updatesurvey2' => Array(),
		'deletesurvey' => Array(),
		'renumberquestions' => Array(),
		'insertnewgroup' => Array(),
		'importgroup' => Array(),
		'updategroup' => Array(),
		'delgroup' => Array(),
		'insertnewquestion' => Array(),
		'importquestion' => Array(),
		'updatequestion' => Array(),
		'copynewquestion' => Array(),
		'delquestion' => Array(),
		'addattribute' => Array(),
		'editattribute' => Array(),
		'delattribute' => Array(),
		'modanswer' => Array(),
		'resetsurveylogic' => Array(),
		'activate' => Array(
				0 => Array ('ok' => 'Y')
				),
		'deactivate' => Array(
				0 => Array('ok' => 'Y')
				),
		'conditions' => Array(
			0 => Array('subaction' => 'insertcondition'),
			1 => Array('subaction' => 'delete'),
			2 => Array('subaction' => 'copyconditions'),
			),
		'insertlabelset' => Array(),
		'importlabels' => Array(),
		'modlabelsetanswers' => Array(),
		'importlabelresources' => Array(),
		'deletelabelset' => Array(),
		'templatecopy' => Array(),
		'templaterename' => Array(),
		'assessmentadd' => Array(),
		'assessmentedit' => Array(),
		'assessmentdelete' => Array(),
		'dataentry' => Array(
			0 => Array('subaction' => 'delete'),
			1 => Array('subaction' => 'update'),
			2 => Array('subaction' => 'insert'),
			),
		'tokens' => Array(
			0 => Array('subaction' => 'updatetoken'),
			1 => Array('subaction' => 'inserttoken'),
			2 => Array('subaction' => 'upload'),
			3 => Array('subaction' => 'uploadldap'),
			4 => Array('subaction' => 'updateemailsettings'),
			5 => Array('subaction' => 'email', 'ok' => 'absolutely'),
			6 => Array('subaction' => 'remind', 'ok' => 'absolutely'),
			7 => Array('subaction' => 'tokenify'),
			8 => Array('subaction' => 'kill'),
			9 => Array('subaction' => 'delete'),
			10 => Array('subaction' => 'clearinvites'),
			11 => Array('subaction' => 'cleartokens'),
			12 => Array('subaction' => 'deleteall'),
			13 => Array('createtable' => 'Y')
			),
		'quotas' => Array(
			0 => Array('subaction' => 'new_quota'),
			1 => Array('subaction' => 'insertquota'),
			2 => Array('subaction' => 'quota_delquota'),
			2 => Array('subaction' => 'modifyquota'),
			3 => Array('subaction' => 'new_answer_two'),
			4 => Array('subaction' => 'new_answer'),
			5 => Array('subaction' => 'insertquotaanswer'),
			6 => Array('subaction' => 'quota_delans')
			)
	);

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && 
	isset($dangerousActionsArray[$_GET['action']]))
{
	$getauthorized=true;
	if (is_array($dangerousActionsArray[$_GET['action']]))
	{
		foreach ($dangerousActionsArray[$_GET['action']] as $key => $arrayparams)
		{
			//error_log("TIBO trying dangerous-subparams number $key");
			$totalparamcount=count($arrayparams);
			$matchparamcount=0;
			foreach ($arrayparams as $param => $val)
			{
				if (isset($_GET[$param]) &&
					$_GET[$param] == $val)
				{
					$matchparamcount++;
					//error_log("TIBO match param=$param val=$val count=$matchparamcount/$totalparamcount");
				}
			}
			if ($matchparamcount == $totalparamcount)
			{
				$getauthorized=false;
				break;
			}
		}
	}
	else
	{ // ERROR
		$getauthorized=false;
	}

	if ($getauthorized === false)
	{
		$_GET['action'] = 'FakeGET';
		$action = 'FakeGET';
		$_REQUEST['action'] = 'FakeGET';
		if (isset($_GET['subaction'])) {unset($_GET['subaction']);}
		if (isset($_REQUEST['subaction'])) {unset($_REQUEST['subaction']);}
	}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && 
	returnglobal('action') != 'login' &&
    returnglobal('action') != 'forgotpass' &&
    returnglobal('action') != 'changelang' &&
	returnglobal('action') != '')
{
	if (returnglobal('checksessionbypost') != $_SESSION['checksessionpost'])
	{
		error_log("LimeSurvey ERROR while checking POST session- Probable CSRF attack Received=".returnglobal('checksessionbypost')." / Expected= ".$_SESSION['checksessionpost']." for action=".returnglobal('action')." .");
		$subaction='';
		if (isset($_POST['action'])) {unset($_POST['action']);}
		if (isset($_REQUEST['action'])) {unset($_REQUEST['action']);}
		if (isset($_POST['subaction'])) {unset($_POST['subaction']);}
		if (isset($_REQUEST['subaction'])) {unset($_REQUEST['subaction']);}
		$_POST['action'] = 'CSRFwarn';
		$_REQUEST['action'] = 'CSRFwarn';
		$action='CSRFwarn';
		//include("access_denied.php");
	}
}

function GetSessionUserRights($loginID)
{
	global $dbprefix,$connect; 
    $squery = "SELECT create_survey, configurator, create_user, delete_user, superadmin, manage_template, manage_label FROM {$dbprefix}users WHERE uid=$loginID";
    $sresult = db_execute_assoc($squery); //Checked
    if ($sresult->RecordCount()>0)
        {
        $fields = $sresult->FetchRow();
        $_SESSION['USER_RIGHT_CREATE_SURVEY'] = $fields['create_survey'];
        $_SESSION['USER_RIGHT_CONFIGURATOR'] = $fields['configurator'];
        $_SESSION['USER_RIGHT_CREATE_USER'] = $fields['create_user'];
        $_SESSION['USER_RIGHT_DELETE_USER'] = $fields['delete_user'];
        $_SESSION['USER_RIGHT_SUPERADMIN'] = $fields['superadmin'];
        $_SESSION['USER_RIGHT_MANAGE_TEMPLATE'] = $fields['manage_template'];
        $_SESSION['USER_RIGHT_MANAGE_LABEL'] = $fields['manage_label'];
        }



	// SuperAdmins
	// * original superadmin with uid=1 unless manually changed and defined
	//   in config-defaults.php
	// * or any user having USER_RIGHT_SUPERADMIN right

	// Let's check if I am the Initial SuperAdmin
	$adminquery = "SELECT uid FROM {$dbprefix}users WHERE parent_id=0";
	$adminresult = db_select_limit_assoc($adminquery, 1);
	$row=$adminresult->FetchRow();
	if($row['uid'] == $_SESSION['loginID'])
	{
		$initialSuperadmin=true;
	}
	else
	{
		$initialSuperadmin=false;
	}

	if ( $initialSuperadmin === true)
	{
		$_SESSION['USER_RIGHT_SUPERADMIN'] = 1;
		$_SESSION['USER_RIGHT_INITIALSUPERADMIN'] = 1;
	}
	else
	{
		$_SESSION['USER_RIGHT_INITIALSUPERADMIN'] = 0;
	}
}


	
?>
