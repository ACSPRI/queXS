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
* $Id: index.php 5138 2008-06-20 20:44:53Z lemeur $
*/

// Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB   

require_once(dirname(__FILE__).'/classes/core/startup.php');

require_once(dirname(__FILE__).'/config-defaults.php');
require_once(dirname(__FILE__).'/common.php');
require_once(dirname(__FILE__).'/classes/core/language.php');
require_once(dirname(__FILE__).'/classes/core/html_entity_decode_php4.php');
@ini_set('session.gc_maxlifetime', $sessionlifetime);

$loadname=returnglobal('loadname');
$loadpass=returnglobal('loadpass');
$scid=returnglobal('scid');
$thisstep=returnglobal('thisstep');
$move=sanitize_paranoid_string(returnglobal('move'));
$clienttoken=trim(sanitize_xss_string(strip_tags(returnglobal('token'))));      
if (!isset($thisstep)) {$thisstep = "";}


if (!isset($surveyid)) {$surveyid=returnglobal('sid');}
else {
		//This next line ensures that the $surveyid value is never anything but a number.
		$surveyid=sanitize_int($surveyid);
	 }

//DEFAULT SETTINGS FOR TEMPLATES
if (!$publicdir) {$publicdir=".";}
$tpldir="$publicdir/templates";

@session_start();

if (isset($_GET['loadall']) && $_GET['loadall'] == "reload" && isset($_GET['token']))
{
	$_POST['loadall']="reload";
	$_POST['token']=$_GET['token'];

	
	//Must destroy the session
	session_unset();
}

// First check if survey is active
// if not: copy some vars from the admin session 
// to a new user session

if ($surveyid)
{
	$issurveyactive=false;
	$actquery="SELECT * FROM ".db_table_name('surveys')." WHERE sid=$surveyid and active='Y'";
	$actresult=db_execute_assoc($actquery) or safe_die ("Couldn't access survey settings<br />$query<br />".$connect->ErrorMsg());      //Checked
	if ($actresult->RecordCount() > 0)
	{
		$issurveyactive=true;
	}
}


if ($surveyid && 
	$issurveyactive===false && 
	isset ($surveyPreview_require_Auth) &&
	$surveyPreview_require_Auth === true)
{
	// admin session and permission have not already been imported
	// for this particular survey
	if ( !isset($_SESSION['USER_RIGHT_PREVIEW']) ||
		$_SESSION['USER_RIGHT_PREVIEW'] != $surveyid)
	{
		// Store initial session name
		$initial_session_name=session_name();

		// One way (not implemented here) would be to start the
		// user session from a duplicate of the admin session
		// - destroy the new session
		// - load admin session (with correct session name)
		// - close admin session
		// - change used session name to default
		// - open new session (takes admin session id)
		// - regenerate brand new session id for this session

		// The solution implemented here is to copy some
		// fields from the admin session to the new session
		// - first destroy the new (empty) user session
		// - then open admin session
		// - record interresting values from the admin session
		// - duplicate admin session under another name and Id
		// - destroy the duplicated admin session
		// - start a brand new user session
		// - copy interresting values in this user session

		@session_destroy();	// make it silent because for
					// some strange reasons it fails sometimes
					// which is not a problem
					// but if it throughs an error then future
					// session functions won't work because
					// headers are already sent.
		$usquery = "SELECT stg_value FROM ".db_table_name("settings_global")." where stg_name='SessionName'";
		$usresult = db_execute_assoc($usquery,'',true);          //Checked 
		if ($usresult)
		{
			$usrow = $usresult->FetchRow();
			@session_name($usrow['stg_value']);
		}
		else
		{
			session_name("LimeSurveyAdmin");
		}
		@session_start(); // Loads Admin Session

		$previewright=false;
		$savesessionvars=Array();
		if (isset($_SESSION['loginID']))
		{
			$rightquery="SELECT * FROM {$dbprefix}surveys_rights WHERE sid=".db_quote($surveyid)." AND uid = ".db_quote($_SESSION['loginID']);
			$rightresult = db_execute_assoc($rightquery);      //Checked 

			// Currently it is enough to be listed in the survey
			// user operator list to get preview access
			if ($rightresult->RecordCount() > 0 || $_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
			{
				$previewright=true;
				$savesessionvars["USER_RIGHT_PREVIEW"]=$surveyid;
				$savesessionvars["loginID"]=$_SESSION['loginID'];
				$savesessionvars["user"]=$_SESSION['user'];
			}
		}

		// change session name and id 
		// then delete this new session
		// ==> the original admin session remains valid
		// ==> it is possible to start a new session
		session_name($initial_session_name);
		if (session_regenerate_id() === false) { safe_die("Error Regenerating Session Id");}
		@session_destroy();

		// start new session
		@session_start();
		// regenerate id so that the header geenrated by previous
		// regenerate_id is overwritten
		// needed after clearall
		if (session_regenerate_id() === false) { safe_die("Error Regenerating Session Id");}

		if ( $previewright === true)
		{
			foreach ($savesessionvars as $sesskey => $sessval)
			{
				$_SESSION[$sesskey]=$sessval;
			}
		}
	}
	else
	{ // already authorized
		$previewright = true;
	}

	if ( $previewright === false)
	{
		// print an error message
		if (isset($_REQUEST['rootdir'])) {safe_die('You cannot start this script directly');}
		require_once(dirname(__FILE__).'/classes/core/language.php');
		$baselang = GetBaseLanguageFromSurveyID($surveyid);
		$clang = new limesurvey_lang($baselang);
		//A nice exit
		sendcacheheaders();
		doHeader();
	
		echo templatereplace(file_get_contents("$tpldir/default/startpage.pstpl"));
		echo "\t\t<center><br />\n"
		."\t\t\t<font color='RED'><strong>".$clang->gT("ERROR")."</strong></font><br />\n"
		."\t\t\t".$clang->gT("We are sorry but you don't have permissions to do this.")."<br /><br />\n"
		."\t\t\t".sprintf($clang->gT("Please contact %s ( %s ) for further assistance."),$siteadminname,$siteadminemail)."\n"
		."\t\t</center><br />\n";
	
		echo templatereplace(file_get_contents("$tpldir/default/endpage.pstpl"));
		doFooter();
		exit;
	}
}

if (isset($_SESSION['srid']))
{
	$saved_id = $_SESSION['srid'];
}

if (!isset($_SESSION['grouplist'])  && (isset($move)) )
// geez ... a session time out! RUN! 
{
    if (isset($_REQUEST['rootdir'])) {safe_die('You cannot start this script directly');}
    require_once(dirname(__FILE__).'/classes/core/language.php');
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	$clang = new limesurvey_lang($baselang);
	//A nice exit
	sendcacheheaders();
	doHeader();

	echo templatereplace(file_get_contents("$tpldir/default/startpage.pstpl"));
	echo "\t\t<center><br />\n"
	."\t\t\t<font color='RED'><strong>".$clang->gT("ERROR")."</strong></font><br />\n"
	."\t\t\t".$clang->gT("We are sorry but your session has expired.")."<br />".$clang->gT("Either you have been inactive for too long, you have cookies disabled for your browser, or there were problems with your connection.")."<br />\n"
    ."\t\t\t".sprintf($clang->gT("Please contact %s ( %s ) for further assistance."),$siteadminname,$siteadminemail)."\n"
	."\t\t</center><br />\n";

	echo templatereplace(file_get_contents("$tpldir/default/endpage.pstpl"));
	doFooter();
	exit;
};

// Set the language of the survey, either from POST, GET parameter of session var
if (isset($_POST['lang']) && $_POST['lang']!='')  // this one comes from the language question
{
    $templang = sanitize_languagecode($_POST['lang']);
	$clang = SetSurveyLanguage( $surveyid, $templang);
	UpdateSessionGroupList($templang);  // to refresh the language strings in the group list session variable
	UpdateFieldArray();        // to refresh question titles and question text 
} 
else 
if (isset($_GET['lang']) && $surveyid)
{
    $templang = sanitize_languagecode($_GET['lang']);
	$clang = SetSurveyLanguage( $surveyid, $templang);
	UpdateSessionGroupList($templang);  // to refresh the language strings in the group list session variable
	UpdateFieldArray();        // to refresh question titles and question text 
} 

    if (isset($_SESSION['s_lang']))
    {
	    $clang = SetSurveyLanguage( $surveyid, $_SESSION['s_lang']);
    } 
    elseif (isset($surveyid) && $surveyid)
    {
	    $baselang = GetBaseLanguageFromSurveyID($surveyid);
	    $clang = SetSurveyLanguage( $surveyid, $baselang);
    }
     else 
     {
         $baselang=$defaultlang;
     }

if (isset($_REQUEST['embedded_inc'])) {safe_die('You cannot start this script directly');}
if ( $embedded_inc != '' )
require_once( $embedded_inc );



//CHECK FOR REQUIRED INFORMATION (sid)
if (!$surveyid)
{
    if(isset($_GET['lang']))
    {
      $baselang = sanitize_languagecode($_GET['lang']);
    }
     elseif (!isset($baselang)) 
     {
      		$baselang=$defaultlang;
	 }
	$clang = new limesurvey_lang($baselang);
    if(!isset($defaulttemplate)) {$defaulttemplate="default";}
    $languagechanger = makelanguagechanger();
    //Find out if there are any publicly available surveys
	$query = "SELECT a.sid, b.surveyls_title 
	          FROM ".db_table_name('surveys')." AS a 
			  INNER JOIN ".db_table_name('surveys_languagesettings')." AS b 
			  ON ( surveyls_survey_id = a.sid AND surveyls_language = a.language ) 
			  WHERE surveyls_survey_id=a.sid 
			  AND surveyls_language=a.language 
			  AND a.active='Y'
			  AND a.listpublic='Y'
			  AND ((a.expires >= '".date("Y-m-d")."'
			  AND a.useexpiry = 'Y') OR
			  (a.useexpiry = 'N'))
			  ORDER BY surveyls_title";
	$result = db_execute_assoc($query,false,true) or die("Could not connect to database. If you try to install LimeSurvey please refer to the <a href='http://docs.limesurvey.org'>installation docs</a> and/or contact the system administrator of this webpage."); //Checked 
	$list=array();
	if($result->RecordCount() > 0) 
	{
		while($rows = $result->FetchRow())
		{
      $link = "<li class='surveytitle'><a href='$relativeurl/index.php?sid=".$rows['sid'];
    if (isset($_GET['lang'])) {$link .= "&amp;lang=".sanitize_languagecode($_GET['lang']);}
		$link .= "' >".$rows['surveyls_title']."</a></li>\n";
		$list[]=$link;
	    }
	}
	if(count($list) < 1)
	{
	    $list[]="<li class='surveytitle'>".$clang->gT("No available surveys")."</li>";
	}
	$surveylist=array(
	                  "nosid"=>$clang->gT("You have not provided a survey identification number"),
	                  "contact"=>sprintf($clang->gT("Please contact %s ( %s ) for further assistance."),$siteadminname,$siteadminemail),
                      "listheading"=>$clang->gT("The following surveys are available:"),
					  "list"=>implode("\n",$list),
					  );

	//A nice exit
	sendcacheheaders();
	doHeader();
	echo templatereplace(file_get_contents("$tpldir/$defaulttemplate/startpage.pstpl"));
	
	echo templatereplace(file_get_contents("$tpldir/$defaulttemplate/surveylist.pstpl"));
	
	echo templatereplace(file_get_contents("$tpldir/$defaulttemplate/endpage.pstpl"));
	doFooter();
	exit;
}

// Get token
if (!isset($token))
{
	$token=$clienttoken;
}

//GET BASIC INFORMATION ABOUT THIS SURVEY
$totalBoilerplatequestions =0;
$thissurvey=getSurveyInfo($surveyid, $_SESSION['s_lang']);

if (is_array($thissurvey))
{
	$surveyexists=1;
} 
else 
{
	$surveyexists=0;
}

// If token was submitted from token form
// Disabled for the moment (1.50) with function captcha_enabled
//if (isset($_GET['tokenSEC']) && $_GET['tokenSEC'] == 1 && function_exists("ImageCreate") && captcha_enabled('surveyaccessscreen',$thissurvey['usecaptcha']))
//{
//	if (!isset($_GET['loadsecurity']) || $_GET['loadsecurity'] != $_SESSION['secanswer'])
//	{
//		$secerror = $clang->gT("The answer to the security question is incorrect")."<br />\n";
//		$_GET['token'] = "";
//	}
//}

if (isset($_GET['newtest']) && $_GET['newtest'] = "Y") unset($_GET['token']);



//SEE IF SURVEY USES TOKENS
$i = 0; //$tokensexist = 0;
if ($surveyexists == 1 && bHasSurveyGotTokentable($thissurvey))
{
	$tokensexist = 1;
}
else
{
	$tokensexist = 0;
}



//SET THE TEMPLATE DIRECTORY
if (!$thissurvey['templatedir']) 
{
    $thistpl=$tpldir."/".$defaulttemplate;
} 
    else 
    {
        $thistpl=$tpldir."/".validate_templatedir($thissurvey['templatedir']);
    }



//MAKE SURE SURVEY HASN'T EXPIRED
if ($thissurvey['expiry'] < date("Y-m-d") && $thissurvey['useexpiry'] == "Y")
{
	sendcacheheaders();
	doHeader();

	echo templatereplace(file_get_contents("$tpldir/default/startpage.pstpl"));
	echo "\t\t<center><br />\n"
	."\t\t\t".$clang->gT("This survey is no longer available.")."<br /><br />\n"
    ."\t\t\t".sprintf($clang->gT("Please contact %s ( %s ) for further assistance."),$thissurvey['adminname'],$thissurvey['adminemail']).".\n"
	."<br /><br />\n";

	echo templatereplace(file_get_contents("$tpldir/default/endpage.pstpl"));
	doFooter();
	exit;
}

//CHECK FOR PREVIOUSLY COMPLETED COOKIE
//If cookies are being used, and this survey has been completed, a cookie called "PHPSID[sid]STATUS" will exist (ie: SID6STATUS) and will have a value of "COMPLETE"
$cookiename="PHPSID".returnglobal('sid')."STATUS";
if (isset($_COOKIE[$cookiename]) && $_COOKIE[$cookiename] == "COMPLETE" && $thissurvey['usecookie'] == "Y" && $tokensexist != 1 && (!isset($_GET['newtest']) || $_GET['newtest'] != "Y"))
{
	sendcacheheaders();
	doHeader();

	echo templatereplace(file_get_contents("$tpldir/default/startpage.pstpl"));
	echo "\t\t<center><br />\n"
	."\t\t\t<font color='RED'><strong>".$clang->gT("Error")."</strong></font><br />\n"
	."\t\t\t".$clang->gT("You have already completed this survey.")."<br /><br />\n"
    ."\t\t\t".sprintf($clang->gT("Please contact %s ( %s ) for further assistance."),$thissurvey['adminname'],$thissurvey['adminemail'])."\n"
	."<br /><br />\n";

	echo templatereplace(file_get_contents("$tpldir/default/endpage.pstpl"));
	doFooter();
	exit;
}

//CHECK IF SURVEY ID DETAILS HAVE CHANGED
if (isset($_SESSION['oldsid'])) {$oldsid=$_SESSION['oldsid'];}

if (!isset($oldsid)) {$_SESSION['oldsid'] = $surveyid;}

if (isset($oldsid) && $oldsid && $oldsid != $surveyid)
{
	$savesessionvars=Array();
	if (isset($_SESSION['USER_RIGHT_PREVIEW']))
	{
		$savesessionvars["USER_RIGHT_PREVIEW"]=$surveyid;
		$savesessionvars["loginID"]=$_SESSION['loginID'];
		$savesessionvars["user"]=$_SESSION['user'];
	}
	session_unset();
	$_SESSION['oldsid']=$surveyid;
	foreach ($savesessionvars as $sesskey => $sessval)
	{
		$_SESSION[$sesskey]=$sessval;
	}
}



//LOAD SAVED SURVEY
if (isset($_POST['loadall']) && $_POST['loadall'] == "reload")
{
	$errormsg="";
	/*

	// if (loadname is not set) or if ((loadname is set) and (loadname is NULL))
	if (!isset($loadname) || (isset($loadname) && ($loadname == null)))
	{
		$errormsg .= $clang->gT("You did not provide a name")."<br />\n";
	}
	// if (loadpass is not set) or if ((loadpass is set) and (loadpass is NULL))
	if (!isset($loadpass) || (isset($loadpass) && ($loadpass == null)))
	{
		$errormsg .= $clang->gT("You did not provide a password")."<br />\n";
	}

	// if security question answer is incorrect
    if (function_exists("ImageCreate") && captcha_enabled('saveandloadscreen',$thissurvey['usecaptcha']))
    {
	    if ( (!isset($_POST['loadsecurity']) || 
			!isset($_SESSION['secanswer']) || 
			$_POST['loadsecurity'] != $_SESSION['secanswer']) &&
		 !isset($_GET['scid']))
	    {
		    $errormsg .= $clang->gT("The answer to the security question is incorrect")."<br />\n";
	    }
    }
	 */
	// Load session before loading the values from the saved data
	if (isset($_GET['loadall']))
	{
		buildsurveysession();
	}

	$_SESSION['holdname']=$_POST['token']; //Session variable used to load answers every page.
	$_SESSION['holdpass']=$_POST['token']; //Session variable used to load answers every page.

	if ($errormsg == "") loadanswers();
	$move = "movecurrent";

	if ($errormsg)
	{
		$_POST['loadall'] = $clang->gT("Load Unfinished Survey");
	}
}

/*
//Allow loading of saved survey
if (isset($_POST['loadall']) && $_POST['loadall'] == $clang->gT("Load Unfinished Survey"))
{
	require_once("load.php");
}
 */

//Check if TOKEN is used for EVERY PAGE
//This function fixes a bug where users able to submit two surveys/votes
//by checking that the token has not been used at each page displayed.
// bypass only this check at first page (Step=0) because
// this check is done in buildsurveysession and error message
// could be more interresting there (takes into accound captcha if used)
if ($tokensexist == 1 && isset($token) && $token &&
	isset($_SESSION['step']) && $_SESSION['step']>0)
{
	//check if token actually does exist

	$tkquery = "SELECT COUNT(*) FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote($token)."' AND (completed = 'N' or completed='')";
	$tkresult = db_execute_num($tkquery); //Checked 
	list($tkexist) = $tkresult->FetchRow();
	if (!$tkexist)
	{
		sendcacheheaders();
		doHeader();
		//TOKEN DOESN'T EXIST OR HAS ALREADY BEEN USED. EXPLAIN PROBLEM AND EXIT
		echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
		echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
		echo "\t<center><br />\n"
		."\t".$clang->gT("This is a closed-access survey, so you must supply a valid token.  Please contact the administrator for assistance.")."<br /><br />\n"
		."\t".$clang->gT("The token you have provided is either not valid, or has already been used.")."\n"
		."\t".sprintf($clang->gT("For further information contact %s"), $thissurvey['adminname'])
		."(<a href='mailto:{$thissurvey['adminemail']}'>"
		."{$thissurvey['adminemail']}</a>)<br /><br />\n"
		."\t<a href='javascript: self.close()'>".$clang->gT("Close this Window")."</a><br />&nbsp;\n";
		echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
		exit;
	}
}
//CLEAR SESSION IF REQUESTED
if (isset($_GET['move']) && $_GET['move'] == "clearall")
{
	$s_lang = $_SESSION['s_lang'];
	session_unset();
	session_destroy();
	setcookie(session_name(),"EXPIRED",time()-120);
	sendcacheheaders();
	if (isset($_GET['redirect'])) {
		session_write_close();
		header("Location: {$_GET['redirect']}");
	}
	doHeader();
	echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
	echo "\n\n<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->\n"
	."\t<script type='text/javascript'>\n"
	."\t<!--\n"
	."\t\tfunction checkconditions(value, name, type)\n"
	."\t\t\t{\n"
	."\t\t\t}\n"
	."\t//-->\n"
	."\t</script>\n\n";

	//Present the clear all page using clearall.pstpl template
	echo templatereplace(file_get_contents("$thistpl/clearall.pstpl"));

	echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
	doFooter();
	exit;
}

if (isset($_GET['newtest']) && $_GET['newtest'] == "Y")
{
	$savesessionvars=Array();
	if (isset($_SESSION['USER_RIGHT_PREVIEW']))
	{
		$savesessionvars["USER_RIGHT_PREVIEW"]=$surveyid;
		$savesessionvars["loginID"]=$_SESSION['loginID'];
		$savesessionvars["user"]=$_SESSION['user'];
	}
	session_unset();
	$_SESSION['oldsid']=$surveyid;
	foreach ($savesessionvars as $sesskey => $sessval)
	{
		$_SESSION[$sesskey]=$sessval;
	}
	//DELETE COOKIE (allow to use multiple times)
	setcookie("$cookiename", "INCOMPLETE", time()-120);
	//echo "Reset Cookie!";
}

//Check to see if a refering URL has been captured.
getreferringurl();
// Let's do this only if 
//  - a saved answer record hasn't been loaded through the saved feature
//  - the survey is not anonymous
//  - the survey is active
//  - a token information has been provided
//  - the survey is setup to allow token-response-persistence
if ($thissurvey['tokenanswerspersistence'] == 'Y' &&
	!isset($_SESSION['srid']) && 
	$thissurvey['private'] == "N" &&
	$thissurvey['active'] == "Y" && $token !='')
{
	// load previous answers if any (dataentry with nosubmit)
	$srquery="SELECT id FROM {$thissurvey['tablename']}"
		. " WHERE {$thissurvey['tablename']}.token='".$token."'\n";

	$result = db_execute_assoc($srquery) or safe_die ("Error loading results<br />$query<br />".$connect->ErrorMsg());   //Checked 
	while ($srrow = $result->FetchRow() )
	{
		$_SESSION['srid'] = $srrow['id'];
	}
}

// SAVE POSTED ANSWERS TO DATABASE IF MOVE (NEXT,PREV,LAST, or SUBMIT) or RETURNING FROM SAVE FORM
if (isset($move) || isset($_POST['saveprompt']))
{
	require_once("save.php");

	// RELOAD THE ANSWERS INCASE SOMEONE ELSE CHANGED THEM
	if ($thissurvey['active'] == "Y" && $thissurvey['allowsave'] == "Y") {
		loadanswers();
	}
}



sendcacheheaders();
//CALL APPROPRIATE SCRIPT
switch ($thissurvey['format'])
{
	case "A": //All in one
	require_once("survey.php");
	break;
	case "S": //One at a time
	require_once("question.php");
	break;
	case "G": //Group at a time
	require_once("group.php");
	break;
	default:
	require_once("question.php");
}


//save now
//
if (isset($_POST['move']) || isset($_POST['saveprompt'])) savedcontrol();


if (isset($_POST['saveall']))
{
	//print "<script language='JavaScript'> alert('".$clang->gT("Survey Saved","js")."'); </script>";
}

function loadanswers()
{
	global $dbprefix,$surveyid,$errormsg;
	global $thissurvey, $thisstep, $clang;
    global $databasetype, $clienttoken;
    $scid=returnglobal('scid');
	if (isset($_POST['loadall']) && $_POST['loadall'] == "reload")
	{
		$query = "SELECT * FROM ".db_table_name('saved_control')." INNER JOIN {$thissurvey['tablename']}
			ON ".db_table_name('saved_control').".srid = {$thissurvey['tablename']}.id
			WHERE ".db_table_name('saved_control').".sid=$surveyid\n";
		if (isset($scid)) //Would only come from email
		{
			$query .= "AND ".db_table_name('saved_control').".scid={$scid}\n";
		}
		$query .="AND ".db_table_name('saved_control').".identifier = '".auto_escape($_SESSION['holdname'])."'
				  AND ".db_table_name('saved_control').".access_code ". (($databasetype == 'mysql')? "=": "like" ) ." '".md5(auto_unescape($_SESSION['holdpass']))."'\n";
	}
	elseif (isset($_SESSION['srid']))
	{
		$query = "SELECT * FROM {$thissurvey['tablename']}
			WHERE {$thissurvey['tablename']}.id=".$_SESSION['srid']."\n";
	}
	else
	{
		return;
	}
	$result = db_execute_assoc($query) or safe_die ("Error loading results<br />$query<br />".$connect->ErrorMsg());   //Checked 
	if ($result->RecordCount() < 1)
	{
		$errormsg .= $clang->gT("There is no matching saved survey")."<br />\n";
	}
	else
	{
		//A match has been found. Let's load the values!
		//If this is from an email, build surveysession first
		$row=$result->FetchRow();
		foreach ($row as $column => $value)
		{
			if ($column == "token")
			{
				$clienttoken=$value;
				$token=$value;
			}
			if ($column == "saved_thisstep")
			{
				$_SESSION['step']=$value;
                $thisstep=$value-1;
			}
			if ($column == "scid")
			{
				$_SESSION['scid']=$value;
			}
			if ($column == "srid")
			{
				$_SESSION['srid']=$value;
			}
			if ($column == "datestamp")
			{
				$_SESSION['datestamp']=$value;
			}
			else
			{
				//Only make session variables for those in insertarray[]
				if (in_array($column, $_SESSION['insertarray']))
				{
					$_SESSION[$column]=$value;
				}
			}
		} // foreach
	}
	return true;
}


function getTokenData($surveyid, $token)
{
	global $dbprefix, $connect;
	$query = "SELECT * FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote($token)."'";
	$result = db_execute_assoc($query) or safe_die("Couldn't get token info in getTokenData()<br />".$query."<br />".$connect->ErrorMsg());    //Checked 
	while($row=$result->FetchRow())
	{
		$thistoken=array("firstname"=>$row['firstname'],
		"lastname"=>$row['lastname'],
		"email"=>$row['email'],
		"language" =>$row['language'],
		"attribute_1"=>$row['attribute_1'],
		"attribute_2"=>$row['attribute_2']);
	} // while
	return $thistoken;
}

function makegraph($currentstep, $total)
{
	global $thissurvey;
	global $publicurl, $clang;

	$shchart = "$publicurl/templates/".validate_templatedir($thissurvey['templatedir'])."/chart.jpg";

	$graph = "<table class='graph' width='100' align='center' cellpadding='2'><tr><td>\n"
	. "<table width='180' align='center' cellpadding='0' cellspacing='0' border='0' class='innergraph'>\n"
	. "<tr><td align='right' width='40'>0%&nbsp;</td>\n";
	$size=intval(($currentstep-1)/$total*100);
	$graph .= "<td width='100' align='left'>\n"
	. "<table cellspacing='0' cellpadding='0' border='0' width='100%'>\n"
	. "<tr><td>\n"
    . "<img src='$shchart' width='$size' align='left' alt='".sprintf($clang->gT('%s %% complete'), $size)."' />\n"
	. "</td></tr>\n"
	. "</table>\n"
	. "</td>\n"
	. "<td align='left' width='40'>&nbsp;100%</td></tr>\n"
	. "</table>\n"
	. "</td></tr><tr><td align='center'>Question $currentstep of: $total</td></tr>\n</table>\n";
	return $graph;
}


function makelanguagechanger()
{
  global $relativeurl;
  if (!isset($surveyid)) 
  {	
    $surveyid=returnglobal('sid');
  }
  if (isset($surveyid)) 
  {
      $slangs = GetAdditionalLanguagesFromSurveyID($surveyid);
  }

  $token = trim(sanitize_xss_string(strip_tags(returnglobal('token'))));      
  if ($token != '')
  {
    $tokenparam = "&token=$token";
  }
  else
  {
    $tokenparam = "";
  }
    
  if (!empty($slangs))
  {
    if (isset($_SESSION['s_lang']) && $_SESSION['s_lang'] != '')
    { 
      $lang = sanitize_languagecode($_SESSION['s_lang']);
    }
    else if(isset($_POST['lang']) && $_POST['lang']!='')
    { 
      $lang = sanitize_languagecode($_POST['lang']);
    }
    else if (isset($_GET['lang']) && $_GET['lang'] != '')
    {
      $lang = sanitize_languagecode($_GET['lang']);
    }
    else
      $lang = GetBaseLanguageFromSurveyID($surveyid);
      
    $htmlcode ="<select name=\"select\" class='languagechanger' onchange=\"javascript:window.location=this.value\">\n";
    $htmlcode .= "<option value=\"$relativeurl/index.php?sid=". $surveyid ."&lang=". $lang ."$tokenparam\">".getLanguageNameFromCode($lang,false)."</option>\n";
    
    foreach ($slangs as $otherlang)
    {
        if($otherlang != $lang)
        $htmlcode .= "\t<option value=\"$relativeurl/index.php?sid=". $surveyid ."&lang=". $otherlang ."$tokenparam\" >".getLanguageNameFromCode($otherlang,false)."</option>\n";
    }
    if($lang != GetBaseLanguageFromSurveyID($surveyid))
    {
      $htmlcode .= "<option value=\"$relativeurl/index.php?sid=".$surveyid."&lang=".GetBaseLanguageFromSurveyID($surveyid)."$tokenparam\">".getLanguageNameFromCode(GetBaseLanguageFromSurveyID($surveyid),false)."</option>\n";  
    }
    
    $htmlcode .= "</select>\n";
//    . "</form>";
        
    return $htmlcode;
  } elseif (!isset($surveyid)) {
    global $defaultlang, $baselang;
    $htmlcode = "<select name=\"select\" class='languagechanger' onchange=\"javascript:window.location=this.value\">\n";
    $htmlcode .= "<option value=\"$relativeurl/index.php?lang=". $defaultlang ."$tokenparam\">".getLanguageNameFromCode($defaultlang,false)."</option>\n";
    foreach(getlanguagedata() as $key=>$val)
    {
	    $htmlcode .= "\t<option value=\"$relativeurl/index.php?lang=".$key."$tokenparam\" ";
		if($key == $baselang) {$htmlcode .= " selected";}
		$htmlcode .= ">".getLanguageNameFromCode($key,false)."</option>\n";
	}
	$htmlcode .= "</select>\n";
    return $htmlcode;
  }
}


function checkgroupfordisplay($gid)
{
	//This function checks all the questions in a group to see if they have
	//conditions, and if the do - to see if the conditions are met.
	//If none of the questions in the group are set to display, then
	//the function will return false, to indicate that the whole group
	//should not display at all.
	global $dbprefix, $connect;
	$countQuestionsInThisGroup=0;
	$countConditionalQuestionsInThisGroup=0;
	foreach ($_SESSION['fieldarray'] as $ia) //Run through all the questions
	{
		if ($ia[5] == $gid) //If the question is in the group we are checking:
		{
			$countQuestionsInThisGroup++;
			if ($ia[7] == "Y") //This question is conditional
			{
				$countConditionalQuestionsInThisGroup++;
				$QuestionsWithConditions[]=$ia; //Create an array containing all the conditional questions
			}
		}
	}
	if ($countQuestionsInThisGroup != $countConditionalQuestionsInThisGroup || !isset($QuestionsWithConditions))
	{
		//One of the questions in this group is NOT conditional, therefore
		//the group MUST be displayed
		return true;
	}
	else
	{
		//All of the questions in this group are conditional. Now we must
		//check every question, to see if the condition for each has been met.
		//If 1 or more have their conditions met, then the group should
		//be displayed.
		foreach ($QuestionsWithConditions as $cc)
		{
			$totalands=0;
			$query = "SELECT * FROM ".db_table_name('conditions')."\n"
				."WHERE qid=$cc[0] ORDER BY cqid";
			$result = db_execute_assoc($query) or safe_die("Couldn't check conditions<br />$query<br />".$connect->ErrorMsg());   //Checked 

			$andedMultipleCqidCount=0; // count of multiple cqids for type Q or K
			while($row=$result->FetchRow())
			{
				//Iterate through each condition for this question and check if it is met.
				$query2= "SELECT type, gid FROM ".db_table_name('questions')."\n"
					." WHERE qid={$row['cqid']} AND language='".$_SESSION['s_lang']."'";
				$result2=db_execute_assoc($query2) or safe_die ("Coudn't get type from questions<br />$ccquery<br />".$connect->ErrorMsg());   //Checked 
				while($row2=$result2->FetchRow())
				{
					$cq_gid=$row2['gid'];
					//Find out the "type" of the question this condition uses
					$thistype=$row2['type'];
				}

				if ($thistype == 'K' || $thistype == 'Q')
				{
					// will be used as an index for Multiple conditions
					// on type Q or K so that there will be ANDed and 
					// not ORed
					$andedMultipleCqidCount++;
				}

				if ($gid == $cq_gid)
				{
					//Don't do anything - this cq is in the current group
				}
				elseif ($thistype == "M" || $thistype == "P")
				{
					// For multiple choice type questions, the "answer" value will be "Y"
					// if selected, the fieldname will have the answer code appended.
					$fieldname=$row['cfieldname'].$row['value'];
					$cvalue="Y";     
					if (isset($_SESSION[$fieldname])) { $cfieldname=$_SESSION[$fieldname]; } else { $cfieldname=""; }
				}
				elseif (ereg('^@([0-9]+X[0-9]+X[^@]+)@',$row['value'],$targetconditionfieldname) &&
					isset($_SESSION[$targetconditionfieldname[1]]) )
				{ 
					// If value uses @SIDXGIDXQID@ codes i
					// then try to replace them with a 
					// value recorded in SESSION if any
					$cvalue=$_SESSION[$targetconditionfieldname[1]];
					if (isset($_SESSION[$fieldname])) { $cfieldname=$_SESSION[$fieldname]; } else { $cfieldname=""; }
				}
				else
				{
					//For all other questions, the "answer" value will be the answer code.
					if (isset($_SESSION[$row['cfieldname']])) {$cfieldname=$_SESSION[$row['cfieldname']];} else {$cfieldname=' ';}
					$cvalue=$row['value'];
				}

				if ($row['method'] != 'RX')
				{
					if (trim($row['method'])=='') 
                    {
                        $row['method']='==';
                    }
                    if (eval('if (trim($cfieldname)'. $row['method'].' trim($cvalue)) return true; else return false;'))
					{
						$conditionMatches=true;
						//This condition is met
					}
					else
					{
						$conditionMatches=false;
					}
				}
				else
				{
					if (ereg(trim($cvalue),trim($cfieldname)))
					{
						$conditionMatches=true;

					}
					else
					{
						$conditionMatches=false;
					}
				}

				if ($conditionMatches === true)
				{
					if ($thistype != 'Q' && $thistype != 'K')
					{
						//This condition is met
						if (!isset($distinctcqids[$row['cqid']])  || $distinctcqids[$row['cqid']] == 0)
						{
							$distinctcqids[$row['cqid']]=1;
						}
					}
					else
					{ //$andedMultipleCqidCount
						if (!isset($distinctcqids[$row['cqid']."-$andedMultipleCqidCount"])  || $distinctcqids[$row['cqid']] == 0)
						{
							$distinctcqids[$row['cqid']."-$andedMultipleCqidCount"]=1;
						}
					
					}
				}
				else
				{
					if ($thistype != 'Q' && $thistype != 'K')
					{
						if (!isset($distinctcqids[$row['cqid']]))
						{
							$distinctcqids[$row['cqid']]=0;
						}
					}
					else
					{
						if (!isset($distinctcqids[$row['cqid']."-$andedMultipleCqidCount"]))
						{
							$distinctcqids[$row['cqid']."-$andedMultipleCqidCount"]=0;
						}
					}

				}
			} // while
			foreach($distinctcqids as $key=>$val)
			{
				//Because multiple cqids are treated as "AND", we only check
				//one condition per conditional qid (cqid). As long as one
				//match is found for each distinct cqid, then the condition is met.
				$totalands=$totalands+$val;
			}
			if ($totalands >= count($distinctcqids))
			{
				//The number of matches to conditions exceeds the number of distinct
				//conditional questions, therefore a condition has been met.
				//As soon as any condition for a question is met, we MUST show the group.
				return true;
			}
			unset($distinctcqids);
		}
		//Since we made it this far, there mustn't have been any conditions met.
		//Therefore the group should not be displayed.
		return false;
	}
}

function checkconfield($value)
{
	global $dbprefix, $connect;
	//$value is the fieldname for the field we are checking for conditions
	foreach ($_SESSION['fieldarray'] as $sfa) //Go through each field
	{
		if ($sfa[1] == $value && $sfa[7] == "Y" && isset($_SESSION[$value]) && $_SESSION[$value]) //Do this if there is a condition based on this answer
		{
			$currentcfield="";
			$query = "SELECT ".db_table_name('conditions').".*, ".db_table_name('questions').".type "
			. "FROM ".db_table_name('conditions').", ".db_table_name('questions')." "
			. "WHERE ".db_table_name('conditions').".cqid=".db_table_name('questions').".qid "
			. "AND ".db_table_name('conditions').".qid=$sfa[0] "
			. "ORDER BY ".db_table_name('conditions').".qid";
			$result=db_execute_assoc($query) or safe_die($query."<br />".$connect->ErrorMsg());         //Checked 
			while($rows = $result->FetchRow()) //Go through the condition on this field
			{
				if($rows['type'] == "M" || $rows['type'] == "P")
				{
					$matchfield=$rows['cfieldname'].$rows['value'];
                    $matchmethod=$rows['method'];
					$matchvalue="Y";
				}
				else
				{
					$matchfield=$rows['cfieldname'];
                    $matchmethod=$rows['method'];
					$matchvalue=$rows['value'];
				}
				$cqval[]=array("cfieldname"=>$rows['cfieldname'],
				"value"=>$rows['value'],
				"type"=>$rows['type'],
				"matchfield"=>$matchfield,
				"matchvalue"=>$matchvalue,
                "matchmethod"=>$matchmethod
                );
				if ($rows['cfieldname'] != $currentcfield)
				{
					$container[]=$rows['cfieldname'];
				}
				$currentcfield=$rows['cfieldname'];
			}
			//At least one match must be found for each "$container"
			$total=0;
			foreach($container as $con)
			{
				$addon=0;
				foreach($cqval as $cqv)
				{//Go through each condition
					// Replace @SGQA@ condition values
					// By corresponding value
					if (ereg('^@([0-9]+X[0-9]+X[^@]+)@',$cqv["matchvalue"], $targetconditionfieldname))
					{
						$cqv["matchvalue"] = $_SESSION[$targetconditionfieldname[1]];
					}
					// Use == as default operator
					if (trim($cqv['matchmethod'])=='') {$cqv['matchmethod']='==';}
					if($cqv['cfieldname'] == $con)
					{
						if ($cqv['matchmethod'] != "RX")
						{
							if (isset($_SESSION[$cqv['matchfield']]) && eval('if ($_SESSION[$cqv["matchfield"]]'.$cqv['matchmethod'].' $cqv["matchvalue"]) {return true;} else {return false;}'))
							{//plug successful matches into appropriate container
								$addon=1;
							}
						}
						elseif (ereg($cqv["matchvalue"],$_SESSION[$cqv["matchfield"]]))
						{
								$addon=1;
						}
					}
				}
				if($addon==1){$total++;}
			}
			if($total<count($container))
			{
			    //If this is not a "moveprev" then
				// Reset the value in SESSION
	            //if(isset($move) && $move != "moveprev")
	            //{
				    $_SESSION[$value]="";
				//}
			}
			unset($cqval);
			unset($container);
		}
	}

}

function checkmandatorys($move, $backok=null)
{
	global $clang, $thisstep;
	if ((isset($_POST['mandatory']) && $_POST['mandatory']) && (!isset($backok) || $backok != "Y"))
	{
		$chkmands=explode("|", $_POST['mandatory']); //These are the mandatory questions to check
		$mfns=explode("|", $_POST['mandatoryfn']); //These are the fieldnames of the mandatory questions
		$mi=0;
		foreach ($chkmands as $cm)
		{
			if (!isset($multiname) || (isset($multiname) && $multiname != "MULTI$mfns[$mi]"))  //no multiple type mandatory set, or does not match this question (set later on for first time)
			{
				if ((isset($multiname) && $multiname) && (isset($_POST[$multiname]) && $_POST[$multiname])) //This isn't the first time (multiname exists, and is a posted variable)
				{
					if ($$multiname == $$multiname2) //The number of questions not answered is equal to the number of questions
					{
						//The number of questions not answered is equal to the number of questions
						//This section gets used if it is a multiple choice type question
						if (isset($move) && $move == "moveprev") {$_SESSION['step'] = $thisstep;}
						if (isset($move) && $move == "movenext") {$_SESSION['step'] = $thisstep;}
						$notanswered[]=substr($multiname, 5, strlen($multiname));
						$$multiname=0;
						$$multiname2=0;
					}
				}
				$multiname="MULTI$mfns[$mi]";
				$multiname2=$multiname."2"; //POSSIBLE CORRUPTION OF PROCESS - CHECK LATER
				$$multiname=0;
				$$multiname2=0;
			}
			else {$multiname="MULTI$mfns[$mi]";}
			$dtcm = "tbdisp$cm";
			if (isset($_SESSION[$cm]) && ($_SESSION[$cm] == "0" || $_SESSION[$cm]))
			{
			}
			elseif ((!isset($_POST[$multiname]) || !$_POST[$multiname]) && (!isset($_POST[$dtcm]) || $_POST[$dtcm] == "on"))
			{
				//One of the mandatory questions hasn't been asnwered
				if (isset($move) && $move == "moveprev") {$_SESSION['step'] = $thisstep;}
				if (isset($move) && $move == "movenext") {$_SESSION['step'] = $thisstep;}
				$notanswered[]=$mfns[$mi];
			}
			else
			{
				//One of the mandatory questions hasn't been answered
				$$multiname++;
			}
			$$multiname2++;
			$mi++;
		}
		if ($multiname && isset($_POST[$multiname]) && $_POST[$multiname]) // Catch the last multiple options question in the lot
		{
			if ($$multiname == $$multiname2) //so far all multiple choice options are unanswered
			{
				//The number of questions not answered is equal to the number of questions
				if (isset($move) && $move == "moveprev") {$_SESSION['step'] = $thisstep;}
				if (isset($move) && $move == "movenext") {$_SESSION['step'] = $thisstep;}
				$notanswered[]=substr($multiname, 5, strlen($multiname));
				$$multiname="";
				$$multiname2="";
			}
		}
	}
	if (!isset($notanswered)) {return false;}//$notanswered=null;}
	return $notanswered;
}

function checkconditionalmandatorys($move, $backok=null)
{
    global $thisstep;
	if ((isset($_POST['conmandatory']) && $_POST['conmandatory']) && (!isset($backok) || $backok != "Y")) //Mandatory conditional questions that should only be checked if the conditions for displaying that question are met
	{
		$chkcmands=explode("|", $_POST['conmandatory']);
		$cmfns=explode("|", $_POST['conmandatoryfn']);
		$mi=0;
		foreach ($chkcmands as $ccm)
		{
			if (!isset($multiname) || $multiname != "MULTI$cmfns[$mi]") //the last multipleanswerchecked is different to this one
			{
				if (isset($multiname) && $multiname && isset($_POST[$multiname]) && $_POST[$multiname])
				{
					if ($$multiname == $$multiname2) //For this lot all multiple choice options are unanswered
					{
						//The number of questions not answered is equal to the number of questions
						if (isset($move) && $move == "moveprev") {$_SESSION['step'] = $thisstep;}
						if (isset($move) && $move == "movenext") {$_SESSION['step'] = $thisstep;}
						$notanswered[]=substr($multiname, 5, strlen($multiname));
						$$multiname=0;
						$$multiname2=0;
					}
				}
				$multiname="MULTI$cmfns[$mi]";
				$multiname2=$multiname."2"; //POSSIBLE CORRUPTION OF PROCESS - CHECK LATER
				$$multiname=0;
				$$multiname2=0;
			}
			else{$multiname="MULTI$cmfns[$mi]";}
			$dccm="display$cmfns[$mi]";
			$dtccm = "tbdisp$ccm";
			if (isset($_SESSION[$ccm]) && ($_SESSION[$ccm] == "0" || $_SESSION[$ccm]) && isset($_POST[$dccm]) && $_POST[$dccm] == "on") //There is an answer
			{
				//The question has an answer, and the answer was displaying
			}
			elseif ((isset($_POST[$dccm]) && $_POST[$dccm] == "on") && (!isset($_POST[$multiname]) || !$_POST[$multiname]) && (!isset($_POST[$dtccm]) || $_POST[$dtccm] == "on")) // Question and Answers is on, there is no answer, but it's a multiple
			{
				if (isset($move) && $move == "moveprev") {$_SESSION['step'] = $thisstep;}
				if (isset($move) && $move == "movenext") {$_SESSION['step'] = $thisstep;}
				$notanswered[]=$cmfns[$mi];
			}
			elseif (isset($_POST[$dccm]) && $_POST[$dccm] == "on")
			{
				//One of the conditional mandatory questions was on, but hasn't been answered
				$$multiname++;
			}
			$$multiname2++;
			$mi++;
		}
		if (isset($multiname) && $multiname && isset($_POST[$multiname]) && $_POST[$multiname])
		{
			if ($$multiname == $$multiname2) //so far all multiple choice options are unanswered
			{
				//The number of questions not answered is equal to the number of questions
				if (isset($move) && $move == "moveprev") {$_SESSION['step'] = $thisstep;}
				if (isset($move) && $move == "movenext") {$_SESSION['step'] = $thisstep;}
				$notanswered[]=substr($multiname, 5, strlen($multiname));
			}
		}
	}
	if (!isset($notanswered)) {return false;}//$notanswered=null;}
	return $notanswered;
}

function checkpregs($move,$backok=null)
{
	global $connect, $thisstep;
	if (!isset($backok) || $backok != "Y")
	{
		global $dbprefix;
		$fieldmap=createFieldMap(returnglobal('sid'));
		if (isset($_POST['fieldnames']))
		{
			$fields=explode("|", $_POST['fieldnames']);
			foreach ($fields as $field)
			{
				//Get question information
				if (isset($_POST[$field]) && ($_POST[$field] == "0" || $_POST[$field])) //Only do this if there is an answer
				{
					$fieldinfo=arraySearchByKey($field, $fieldmap, "fieldname", 1);
					$pregquery="SELECT preg\n"
					."FROM ".db_table_name('questions')."\n"
					."WHERE qid=".$fieldinfo['qid']." "
					. "AND language='".$_SESSION['s_lang']."'";
					$pregresult=db_execute_assoc($pregquery) or safe_die("ERROR: $pregquery<br />".$connect->ErrorMsg());      //Checked 
					while($pregrow=$pregresult->FetchRow())
					{
						$preg=$pregrow['preg'];
					} // while
					if (isset($preg) && $preg)
					{
						if (!@preg_match($preg, $_POST[$field]))
						{
							$notvalidated[]=$field;
						}
					}
				}
			}
		}
        //The following section checks for question attribute validation, looking for values in a particular field
		if (isset($_POST['qattribute_answer']))
		{
		foreach ($_POST['qattribute_answer'] as $maxvalueanswer)
		    {
		        //$maxvalue_answername="maxvalue_answer".$maxvalueanswer;
		        if (!empty($_POST['qattribute_answer'.$maxvalueanswer]))
		            {
        			if (isset($move) && $move == "moveprev") {$_SESSION['step'] = $thisstep;}
        			if (isset($move) && $move == "movenext") {$_SESSION['step'] = $thisstep;}
					$notvalidated[]=$maxvalueanswer;
        			return $notvalidated;
					}
			}
		}

		if (isset($notvalidated) && is_array($notvalidated))
		{
			if (isset($move) && $move == "moveprev") {$_SESSION['step'] = $thisstep;}
			if (isset($move) && $move == "movenext") {$_SESSION['step'] = $thisstep;}
			return $notvalidated;
		}
	}
}

function addtoarray_single($array1, $array2)
{
	//Takes two single element arrays and adds second to end of first if value exists
	if (is_array($array2))
	{
		foreach ($array2 as $ar)
		{
			if ($ar && $ar !== null)
			{
				$array1[]=$ar;
			}
		}
	}
	return $array1;
}

function remove_nulls_from_array($array)
{
	foreach ($array as $ar)
	{
		if ($ar !== null)
		{
			$return[]=$ar;
		}
	}
	if (isset($return))
	{
		return $return;
	}
	else
	{
		return false;
	}
}

function submittokens()
{
	global $thissurvey, $timeadjust;
	global $dbprefix, $surveyid, $connect;
	global $sitename, $thistpl, $clang, $clienttoken;

	// Put date into sent and completed

	$today = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i", $timeadjust);     
	$utquery = "UPDATE {$dbprefix}tokens_$surveyid\n";
	if (bIsTokenCompletedDatestamped($thissurvey))
	{
		$utquery .= "SET completed='$today'\n";
	}
	else
	{
		$utquery .= "SET completed='Y'\n";
	}
	$utquery .= "WHERE token='".db_quote($clienttoken)."'";

	$utresult = $connect->Execute($utquery) or safe_die ("Couldn't update tokens table!<br />\n$utquery<br />\n".$connect->ErrorMsg());     //Checked 

	// TLR change to put date into sent and completed
	$cnfquery = "SELECT * FROM ".db_table_name("tokens_$surveyid")." WHERE token='".db_quote($clienttoken)."' AND completed!='N' AND completed!=''";

	$cnfresult = db_execute_assoc($cnfquery);       //Checked 
	$cnfrow = $cnfresult->FetchRow();
	if (isset($cnfrow))
	{
		$from = "{$thissurvey['adminname']} <{$thissurvey['adminemail']}>";
		$to = $cnfrow['email'];
		$subject=$thissurvey['email_confirm_subj'];

		$fieldsarray["{ADMINNAME}"]=$thissurvey['adminname'];
		$fieldsarray["{ADMINEMAIL}"]=$thissurvey['adminemail'];
		$fieldsarray["{SURVEYNAME}"]=$thissurvey['name'];
		$fieldsarray["{SURVEYDESCRIPTION}"]=$thissurvey['description'];
		$fieldsarray["{FIRSTNAME}"]=$cnfrow['firstname'];
		$fieldsarray["{LASTNAME}"]=$cnfrow['lastname'];
		$fieldsarray["{ATTRIBUTE_1}"]=$cnfrow['attribute_1'];
		$fieldsarray["{ATTRIBUTE_2}"]=$cnfrow['attribute_2'];

		$subject=Replacefields($subject, $fieldsarray);

		$subject=html_entity_decode_php4($subject, ENT_QUOTES, "UTF-8");

		if (getEmailFormat($surveyid) == 'html')
		{
			$ishtml=true;
		}
		else
		{
			$ishtml=false;
		}           

		if ($thissurvey['email_confirm'])
		{
			$message=$thissurvey['email_confirm'];
			$message=Replacefields($message, $fieldsarray);

			if (!$ishtml)
			{
				$message=strip_tags(br2nl(html_entity_decode_php4($message, ENT_QUOTES, "UTF-8")));
			}
			else 
			{
				$message=html_entity_decode_php4($message,ENT_QUOTES, "UTF-8");
			}
		}
		else
		{
			//Get the default email_confirm from the default language file
			// Todo: This can't be right
			$message = conditional_nl2br($clang->gT("Dear {FIRSTNAME},\n\nThis email is to confirm that you have completed the survey titled {SURVEYNAME} and your response has been saved. Thank you for participating.\n\nIf you have any further questions about this email, please contact {ADMINNAME} on {ADMINEMAIL}.\n\nSincerely,\n\n{ADMINNAME}"),$ishtml);
			$message=Replacefields($message, $fieldsarray);
		}

		//Only send confirmation email if there is a valid email address

		if (validate_email($cnfrow['email']))
		{
			MailTextMessage($message, $subject, $to, $from, $sitename,$ishtml);
		}
	}
}

function sendsubmitnotification($sendnotification)
{
	global $thissurvey, $debug;
	global $dbprefix, $clang;
	global $sitename, $homeurl, $surveyid, $publicurl, $maildebug;

	$subject = $clang->gT("Answer Submission for Survey","unescaped")." ".$thissurvey['name'];

	$message = $clang->gT("Survey Submitted","unescaped")." - {$thissurvey['name']}\n"
	. $clang->gT("A new response was entered for your survey","unescaped")."\n\n";
	if ($thissurvey['allowsave'] == "Y" && isset($_SESSION['scid']))
	{
		$message .= $clang->gT("Click the following link to reload the survey:","unescaped")."\n";
		$message .= "  $publicurl/index.php?sid=$surveyid&loadall=reload&scid=".$_SESSION['scid']."&loadname=".urlencode($_SESSION['holdname'])."&loadpass=".urlencode($_SESSION['holdpass'])."\n\n";
	}

	$message .= $clang->gT("Click the following link to see the individual response:","unescaped")."\n"
	. "  $homeurl/admin.php?action=browse&sid=$surveyid&subaction=id&id=".$_SESSION['srid']."\n\n"
	// Add link to edit individual responses from notification email
	. $clang->gT("Click the following link to edit the individual response:","unescaped")."\n"

	. "  $homeurl/admin.php?action=dataentry&sid=$surveyid&subaction=edit&surveytable=survey_$surveyid&id=".$_SESSION['srid']."\n\n"
	. $clang->gT("View statistics by clicking here:","unescaped")."\n"
	. "  $homeurl/admin.php?action=statistics&sid=$surveyid\n\n";
	if ($sendnotification > 1)
	{ //Send results as well. Currently just bare-bones - will be extended in later release
		$message .= "----------------------------\n";
		foreach ($_SESSION['insertarray'] as $value)
		{
			$questiontitle=strip_tags(html_entity_decode_php4(returnquestiontitlefromfieldcode($value), ENT_QUOTES, "UTF-8"));
			$message .= "$questiontitle:   ";
			$details = arraySearchByKey($value, createFieldMap($surveyid),"fieldname", 1);
			if ( $details['type'] == "T" or $details['type'] == "U")
			{
				$message .= "\r\n";
				if (isset($_SESSION[$value]))
				{
					foreach (explode("\n",getextendedanswer($value,$_SESSION[$value])) as $line) 
					{
					 		$message .= "\t" . strip_tags(html_entity_decode_php4($line, ENT_QUOTES, "UTF-8"));
							$message .= "\n";
					}
				}
			}
			elseif (isset($_SESSION[$value]))
			{
				$message .= strip_tags(html_entity_decode_php4(getextendedanswer($value, $_SESSION[$value],ENT_QUOTES, "UTF-8")));
				$message .= "\n";
			}
		}
		$message .= "\n\n----------------------------\n\n";
	}
	$message.= "LimeSurvey";
	$from = $thissurvey['adminname'].' <'.$thissurvey['adminemail'].'>';

	if ($recips=explode(";", $thissurvey['adminemail']))
	{
		foreach ($recips as $rc)
		{
			if (!MailTextMessage($message, $subject, trim($rc), $from, $sitename, false, getBounceEmail($surveyid)))
            {
                if ($debug>0) {echo '<br />Email could not be sent. Reason: '.$maildebug.'<br/>';}
            }
        }
	}
	else
	{
		if (!MailTextMessage($message, $subject, $thissurvey['adminemail'], $from, $sitename, false, getBounceEmail($surveyid)))
        {
            if ($debug>0) {echo '<br />Email could not be sent. Reason: '.$maildebug.'<br/>';}
        }
	}
}

function submitfailed($errormsg)
{
	global $thissurvey, $clang;
	global $thistpl, $subquery, $surveyid, $connect;
	sendcacheheaders();
	doHeader();
	echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
	$completed = "<br /><strong><font size='2' color='red'>"
	. $clang->gT("Did Not Save")."</strong></font><br /><br />\n\n"
	. $clang->gT("An unexpected error has occurred and your responses cannot be saved.")."<br /><br />\n";
	if ($thissurvey['adminemail'])
	{
		$completed .= $clang->gT("Your responses have not been lost and have been emailed to the survey administrator and will be entered into our database at a later point.")."<br /><br />\n";
		$email=$clang->gT("An error occurred saving a response to survey id","unescaped")." ".$thissurvey['name']." - $surveyid\n\n";
		$email .= $clang->gT("DATA TO BE ENTERED","unescaped").":\n";
		foreach ($_SESSION['insertarray'] as $value)
		{
			$email .= "$value: {$_SESSION[$value]}\n";
		}
		$email .= "\n".$clang->gT("SQL CODE THAT FAILED","unescaped").":\n"
		. "$subquery\n\n"
		. $clang->gT("ERROR MESSAGE","unescaped").":\n"
		. $errormsg."\n\n";
		MailTextMessage($email, $clang->gT("Error saving results","unescaped"), $thissurvey['adminemail'], $thissurvey['adminemail'], "LimeSurvey", false, getBounceEmail($surveyid));
		echo "<!-- EMAIL CONTENTS:\n$email -->\n";
		//An email has been sent, so we can kill off this session.
		session_unset();
		session_destroy();
	}
	else
	{
		$completed .= "<a href='javascript:location.reload()'>".$clang->gT("Try to submit again")."</a><br /><br />\n";
		$completed .= $subquery;
	}
	return $completed;
}

function buildsurveysession()
{
	// Performance optimized	: Nov 22, 2006
	// Performance Improvement	: 17%
	// Optimized By				: swales

	global $thissurvey, $secerror, $clienttoken;
	global $tokensexist, $thistpl;
	global $surveyid, $dbprefix, $connect;
	global $register_errormsg, $clang;
	global $totalBoilerplatequestions;
	global $templang;
	
	$totalBoilerplatequestions = 0;

	//This function builds all the required session variables when a survey is first started.
	//It is called from each various format script (ie: group.php, question.php, survey.php)
	//if the survey has just begun. This funcion also returns the variable $totalquestions.


	// NO TOKEN REQUIRED BUT CAPTCHA ENABLED FOR SURVEY ACCESS
	if ($tokensexist == 0 &&
		captcha_enabled('surveyaccessscreen',$thissurvey['usecaptcha']))
	{

		// IF CAPTCHA ANSWER IS NOT CORRECT OR NOT SET
		if (!isset($_GET['loadsecurity']) || 
			!isset($_SESSION['secanswer']) || 
			$_GET['loadsecurity'] != $_SESSION['secanswer'])
		{
			sendcacheheaders();
			doHeader();
			// No or bad answer to required security question
	
			echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
	        //echo makedropdownlist();
			echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));

			if (isset($_GET['loadsecurity']))
			{ // was a bad answer
				echo "<font color='#FF0000'>".$clang->gT("The answer to the security question is incorrect")."</font><br />"; 
			}

		      echo "<tr><td>".$clang->gT("Please confirm access to survey by answering the security question below and click continue.")."<br />&nbsp;
			        <form method='get' action='".$_SERVER['PHP_SELF']."'>
			        <table align='center'>
				        <tr>
					        <td align='right' valign='middle'>
					        <input type='hidden' name='sid' value='".$surveyid."' id='sid' />
					        <input type='hidden' name='lang' value='".$templang."' id='lang' />";


			echo "			
				        </td>
			        </tr>";
	                if (function_exists("ImageCreate") && captcha_enabled('surveyaccessscreen', $thissurvey['usecaptcha']))
	                { echo "<tr>
				                <td align='center' valign='middle'>".$clang->gT("Security Question")."</td><td align='left' valign='middle'><table><tr><td valign='center'><img src='verification.php'></td><td valign='center'><input type='text' size='5' maxlength='3' name='loadsecurity' value=''></td></tr></table>
				                </td>
			                </tr>";}
			        echo "<tr><td colspan='2' align='center'><input class='submit' type='submit' value='".$clang->gT("Continue")."' /></td></tr>
		        </table>
		        </form>
		        <br />&nbsp;</center>";
	
			echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
			exit;
		}
	}

	//BEFORE BUILDING A NEW SESSION FOR THIS SURVEY, LET'S CHECK TO MAKE SURE THE SURVEY SHOULD PROCEED!

	// TOKEN REQUIRED BUT NO TOKEN PROVIDED
	if ($tokensexist == 1 && !returnglobal('token'))
	{
		// DISPLAY REGISTER-PAGE if needed
		// DISPLAY CAPTCHA if needed
		sendcacheheaders();
		doHeader();

		echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
        //echo makedropdownlist();
		echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
		if (isset($thissurvey) && $thissurvey['allowregister'] == "Y")
		{
			echo templatereplace(file_get_contents("$thistpl/register.pstpl"));
		}
		else
		{
          echo " <center><br />";
	      if (isset($secerror)) echo "<font color='#FF0000'>".$secerror."</font><br />"; 
	      echo $clang->gT("This is a controlled survey. You need a valid token to participate.")."<br /><br />";
	      echo $clang->gT("If you have been issued a token, please enter it in the box below and click continue.")."<br />&nbsp;
	        <form method='get' action='".$_SERVER['PHP_SELF']."'>
	        <table align='center'>
		        <tr>
			        <td align='right' valign='middle'>
			        <input type='hidden' name='sid' value='".$surveyid."' id='sid' />
			        <input type='hidden' name='tokenSEC' value='1' id='sid' />"
			        .$clang->gT("Token")."</td><td align='left' valign='middle'><input class='text' type='text' name='token'>
			        </td>
		        </tr>";
                if (function_exists("ImageCreate") && captcha_enabled('surveyaccessscreen', $thissurvey['usecaptcha']))
                { echo "<tr>
			                <td align='center' valign='middle'>".$clang->gT("Security Question")."</td><td align='left' valign='middle'><table><tr><td valign='center'><img src='verification.php'></td><td valign='center'><input type='text' size='5' maxlength='3' name='loadsecurity' value=''></td></tr></table>
			                </td>
		                </tr>";}
		        echo "<tr><td colspan='2' align='center'><input class='submit' type='submit' value='".$clang->gT("Continue")."' /></td></tr>
	        </table>
	        </form>
	        <br />&nbsp;</center>";
		}

		echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
		exit;
	}
	// TOKENS REQUIRED, A TOKEN PROVIDED
	// SURVEY WITH NO NEED TO USE CAPTCHA
	elseif ($tokensexist == 1 && returnglobal('token') &&
		!captcha_enabled('surveyaccessscreen',$thissurvey['usecaptcha']))
	{

		//check if token actually does exist
		$tkquery = "SELECT COUNT(*) FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote(trim(sanitize_xss_string(strip_tags(returnglobal('token')))))."' AND (completed = 'N' or completed='')";
		$tkresult = db_execute_num($tkquery);    //Checked 
		list($tkexist) = $tkresult->FetchRow();
		if (!$tkexist)
		{
			sendcacheheaders();
			doHeader();
			//TOKEN DOESN'T EXIST OR HAS ALREADY BEEN USED. EXPLAIN PROBLEM AND EXIT

			echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));

			echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
			echo "\t<center><br />\n"
			."\t".$clang->gT("This is a controlled survey. You need a valid token to participate.")."<br /><br />\n"
			."\t".$clang->gT("The token you have provided is either not valid, or has already been used.")."\n"
			."\t".sprintf($clang->gT("For further information contact %s"), $thissurvey['adminname'])
			."(<a href='mailto:{$thissurvey['adminemail']}'>"
			."{$thissurvey['adminemail']}</a>)<br /><br />\n"
			."\t<a href='javascript: self.close()'>".$clang->gT("Close this Window")."</a><br />&nbsp;\n";

			echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
			exit;
		}
	}
	// TOKENS REQUIRED, A TOKEN PROVIDED
	// SURVEY CAPTCHA REQUIRED
	elseif ($tokensexist == 1 && returnglobal('token') &&
		captcha_enabled('surveyaccessscreen',$thissurvey['usecaptcha']))
	{

		// IF CAPTCHA ANSWER IS CORRECT
		if (isset($_GET['loadsecurity']) && 
			isset($_SESSION['secanswer']) && 
			$_GET['loadsecurity'] == $_SESSION['secanswer'])
		{
			//check if token actually does exist
			$tkquery = "SELECT COUNT(*) FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote(trim(sanitize_xss_string(strip_tags(returnglobal('token')))))."' AND (completed = 'N' or completed='')";
			$tkresult = db_execute_num($tkquery);     //Checked 
			list($tkexist) = $tkresult->FetchRow();
			if (!$tkexist)
			{
				sendcacheheaders();
				doHeader();
				//TOKEN DOESN'T EXIST OR HAS ALREADY BEEN USED. EXPLAIN PROBLEM AND EXIT
	
				echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
	
				echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
				echo "\t<center><br />\n"
				."\t".$clang->gT("This is a controlled survey. You need a valid token to participate.")."<br /><br />\n"
				."\t".$clang->gT("The token you have provided is either not valid, or has already been used.")."\n"
				."\t".sprintf($clang->gT("For further information contact %s"), $thissurvey['adminname'])
				."(<a href='mailto:{$thissurvey['adminemail']}'>"
				."{$thissurvey['adminemail']}</a>)<br /><br />\n"
				."\t<a href='javascript: self.close()'>".$clang->gT("Close this Window")."</a><br />&nbsp;\n";
	
				echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
				exit;
			}
		}
		// IF CAPTCHA ANSWER IS NOT CORRECT
		else
		{
			$gettoken = $clienttoken;
			sendcacheheaders();
			doHeader();
			// No or bad answer to required security question
	
			echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
	        //echo makedropdownlist();
			echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
			// If token wasn't provided and public registration
			// is enabled then show registration form
			if ( !isset($gettoken) && isset($thissurvey) && $thissurvey['allowregister'] == "Y")
			{
				echo templatereplace(file_get_contents("$thistpl/register.pstpl"));
			}
			else
			{ // only show CAPTCHA
	          echo " <center><br />";

			if (isset($_GET['loadsecurity']))
			{ // was a bad answer
				echo "<font color='#FF0000'>".$clang->gT("The answer to the security question is incorrect")."</font><br />"; 
			}

		      echo $clang->gT("This is a controlled survey. You need a valid token to participate.")."<br /><br />";
			// IF TOKEN HAS BEEN GIVEN THEN AUTOFILL IT
			// AND HIDE ENTRY FIELD
			if (!isset($gettoken))
			{
			      echo $clang->gT("If you have been issued with a token, please enter it in the box below and click continue.")."<br />&nbsp;
			        <form method='get' action='".$_SERVER['PHP_SELF']."'>
			        <table align='center'>
				        <tr>
					        <td align='right' valign='middle'>
					        <input type='hidden' name='sid' value='".$surveyid."' id='sid' />
					        <input type='hidden' name='tokenSEC' value='1' id='sid' />";

			echo	        $clang->gT("Token")."</td><td align='left' valign='middle'><input class='text' type='text' name='token'>";
			}
			else
			{
			      echo $clang->gT("Please confirm the token by answering the security question below and click continue.")."<br />&nbsp;
			        <form method='get' action='".$_SERVER['PHP_SELF']."'>
			        <table align='center'>
				        <tr>
					        <td align='right' valign='middle'>
					        <input type='hidden' name='sid' value='".$surveyid."' id='sid' />
					        <input type='hidden' name='tokenSEC' value='1' id='sid' />";

			echo	        $clang->gT("Token").":</td><td align='left' valign='middle'>&nbsp;$gettoken<input type='hidden' name='token' value='$gettoken'>";
			}

			echo "			
				        </td>
			        </tr>";
	                if (function_exists("ImageCreate") && captcha_enabled('surveyaccessscreen', $thissurvey['usecaptcha']))
	                { echo "<tr>
				                <td align='center' valign='middle'>".$clang->gT("Security Question")."</td><td align='left' valign='middle'><table><tr><td valign='center'><img src='verification.php'></td><td valign='center'><input type='text' size='5' maxlength='3' name='loadsecurity' value=''></td></tr></table>
				                </td>
			                </tr>";}
			        echo "<tr><td colspan='2' align='center'><input class='submit' type='submit' value='".$clang->gT("Continue")."' /></td></tr>
		        </table>
		        </form>
		        <br />&nbsp;</center>";
			}
	
			echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
			exit;
		}
	}

	//RESET ALL THE SESSION VARIABLES AND START AGAIN
	unset($_SESSION['grouplist']);
	unset($_SESSION['fieldarray']);
	unset($_SESSION['insertarray']);
	unset($_SESSION['thistoken']);


	//RL: multilingual support

	if (isset($_GET['token'])){
	//get language from token (if one exists)
		$tkquery2 = "SELECT * FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote($clienttoken)."' AND (completed = 'N' or completed='')";
		//echo $tkquery2;
		$result = db_execute_assoc($tkquery2) or safe_die ("Couldn't get tokens<br />$tkquery<br />".$connect->ErrorMsg());    //Checked 
		while ($rw = $result->FetchRow())
		{
			$tklanguage=$rw['language'];
		}
	}
	if (returnglobal('lang')) { $language_to_set=returnglobal('lang');
		} elseif (isset($tklanguage)) { $language_to_set=$tklanguage;}
		else {$language_to_set = $thissurvey['language'];}

	if (!isset($_SESSION['s_lang'])) {
		SetSurveyLanguage($surveyid, $language_to_set);
	}


UpdateSessionGroupList($_SESSION['s_lang']);



// Optimized Query
	// Change query to use sub-select to see if conditions exist.
	$query = "SELECT ".db_table_name('questions').".*, ".db_table_name('groups').".*,\n"
	." (SELECT count(1) FROM ".db_table_name('conditions')."\n"
	." WHERE ".db_table_name('questions').".qid = ".db_table_name('conditions').".qid) AS hasconditions\n"
    ." FROM ".db_table_name('groups')." INNER JOIN ".db_table_name('questions')." ON ".db_table_name('groups').".gid = ".db_table_name('questions').".gid\n"
    ." WHERE ".db_table_name('questions').".sid=".$surveyid."\n"
    ." AND ".db_table_name('groups').".language='".$_SESSION['s_lang']."'\n"
    ." AND ".db_table_name('questions').".language='".$_SESSION['s_lang']."'\n"
    ." ORDER BY ".db_table_name('groups').".group_order,".db_table_name('questions').".question_order";

 //var_dump($_SESSION);
//	echo $query."<br>";
	$result = db_execute_assoc($query);    //Checked 

	$arows = $result->GetRows();

	$totalquestions = $result->RecordCount();

	//2. SESSION VARIABLE: totalsteps
	//The number of "pages" that will be presented in this survey
	//The number of pages to be presented will differ depending on the survey format
	switch($thissurvey['format'])
	{
		case "A":
		$_SESSION['totalsteps']=1;
		break;
		case "G":
		if (isset($_SESSION['grouplist'])) {$_SESSION['totalsteps']=count($_SESSION['grouplist']);}
		break;
		case "S":
		$_SESSION['totalsteps']=$totalquestions;
	}

	if ($totalquestions == "0")	//break out and crash if there are no questions!
	{
		sendcacheheaders();
		doHeader();
		echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
		echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
		echo "\t<center><br />\n"
		."\t".$clang->gT("This survey does not yet have any questions and cannot be tested or completed.")."<br /><br />\n"
		."\t".sprintf($clang->gT("For further information contact %s"), $thissurvey['adminname'])
		." (<a href='mailto:{$thissurvey['adminemail']}'>"
		."{$thissurvey['adminemail']}</a>)<br /><br />\n"
		."\t<a href='javascript: self.close()'>".$clang->gT("Close this Window")."</a><br />&nbsp;\n";

		echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
		exit;
	}

	//Perform a case insensitive natural sort on group name then question title of a multidimensional array
//	usort($arows, 'CompareGroupThenTitle');

	//3. SESSION VARIABLE - insertarray
	//An array containing information about used to insert the data into the db at the submit stage
	//4. SESSION VARIABLE - fieldarray
	//See rem at end..
	if ($thissurvey['private'] == "N")
	{
		$_SESSION['token'] = $clienttoken;
		$_SESSION['insertarray'][]= "token";
	}

	if ($tokensexist == 1 && $thissurvey['private'] == "N")
	{
		//Gather survey data for "non anonymous" surveys, for use in presenting questions
		$_SESSION['thistoken']=getTokenData($surveyid, $clienttoken);
	}

	foreach ($arows as $arow)
	{
		//WE ARE CREATING A SESSION VARIABLE FOR EVERY FIELD IN THE SURVEY
		$fieldname = "{$arow['sid']}X{$arow['gid']}X{$arow['qid']}";
		if ($arow['type'] == "M" || $arow['type'] == "A" || $arow['type'] == "B" ||
		$arow['type'] == "C" || $arow['type'] == "E" || $arow['type'] == "F" ||
		$arow['type'] == "H" || $arow['type'] == "P" || $arow['type'] == "^")
		{

// Optimized Query
			$abquery = "SELECT ".db_table_name('answers').".code, ".db_table_name('questions').".other\n"
			. " FROM ".db_table_name('answers')."\n"
			. " INNER JOIN ".db_table_name('questions')."\n"
			. " ON ".db_table_name('answers').".qid=".db_table_name('questions').".qid\n"
			. " WHERE ".db_table_name('questions').".sid=$surveyid\n"
			. " AND ".db_table_name('questions').".qid={$arow['qid']}\n"
			. " AND ".db_table_name('questions').".language='".$_SESSION['s_lang']."' \n"
			. " AND ".db_table_name('answers').".language='".$_SESSION['s_lang']."' \n"
			. " ORDER BY ".db_table_name('answers').".sortorder, ".db_table_name('answers').".answer";

			$abresult = db_execute_assoc($abquery);       //Checked 
			while ($abrow = $abresult->FetchRow())
			{
				$_SESSION['insertarray'][] = $fieldname.$abrow['code'];
				$alsoother = "";
				if ($abrow['other'] == "Y") {$alsoother = "Y";}
				if ($arow['type'] == "P")
				{
					$_SESSION['insertarray'][] = $fieldname.$abrow['code']."comment";
				}
			}
			if (isset($alsoother) && $alsoother) //Add an extra field for storing "Other" answers
			{
				$_SESSION['insertarray'][] = $fieldname."other";
				if ($arow['type'] == "P")
				{
					$_SESSION['insertarray'][] = $fieldname."othercomment";
				}
			}
		}
		elseif ($arow['type'] == "1")	// Multi Scale
		{

// Optimized Query
			$abquery = "SELECT ".db_table_name('answers').".code, ".db_table_name('questions').".other\n"
			. " FROM ".db_table_name('answers')."\n"
			. " INNER JOIN ".db_table_name('questions')."\n"
			. " ON ".db_table_name('answers').".qid=".db_table_name('questions').".qid\n"
			. " WHERE ".db_table_name('questions').".sid=$surveyid\n"
			. " AND ".db_table_name('questions').".qid={$arow['qid']}\n"
			. " AND ".db_table_name('questions').".language='".$_SESSION['s_lang']."' \n"
			. " AND ".db_table_name('answers').".language='".$_SESSION['s_lang']."' \n"
			. " ORDER BY ".db_table_name('answers').".sortorder, ".db_table_name('answers').".answer";
			$abresult = db_execute_assoc($abquery);       //Checked 
			while ($abrow = $abresult->FetchRow())
			{
				$abmultiscalequery = "SELECT l.* FROM ".db_table_name('questions')." as q, ".db_table_name('labels')." as l, ".db_table_name('answers')." as a"
					     ." WHERE a.qid=q.qid AND sid=$surveyid AND q.qid={$arow['qid']} "
	                     ." AND l.lid=q.lid AND sid=$surveyid AND q.qid={$arow['qid']}"
                         ." AND l.language='".$_SESSION['s_lang']. "' "
                         ." AND a.language='".$_SESSION['s_lang']. "' "
                         ." AND q.language='".$_SESSION['s_lang']. "' ";
                        
				$abmultiscaleresult=db_execute_assoc($abmultiscalequery) or safe_die ("Couldn't get perform answers query<br />$abquery<br />".$connect->ErrorMsg());  //Checked 
				$abmultiscalecount=$abmultiscaleresult->RecordCount();
				if ($abmultiscalecount>0)
				{
						$_SESSION['insertarray'][] = $fieldname.$abrow['code']."#0";
						$alsoother = "";
						
						if ($abrow['other'] == "Y") {$alsoother = "Y";}
						if ($arow['type'] == "P")
						{
							$_SESSION['insertarray'][] = $fieldname.$abrow['code']."comment";
						}

				}
				// multi scale
				$abmultiscalequery = "SELECT l.* FROM ".db_table_name('questions')." as q, ".db_table_name('labels')." as l, ".db_table_name('answers')." as a"
					     ." WHERE a.qid=q.qid AND sid=$surveyid AND q.qid={$arow['qid']} "
	                     ." AND l.lid=q.lid1 AND sid=$surveyid AND q.qid={$arow['qid']}"
                         ." AND l.language='".$_SESSION['s_lang']. "' "
                         ." AND a.language='".$_SESSION['s_lang']. "' "
                         ." AND q.language='".$_SESSION['s_lang']. "' ";
                       
				$abmultiscaleresult=db_execute_assoc($abmultiscalequery) or safe_die ("Couldn't get perform answers query<br />$abquery<br />".$connect->ErrorMsg());  //Checked 
				$abmultiscalecount=$abmultiscaleresult->RecordCount();
				if ($abmultiscalecount>0)
				{
						$_SESSION['insertarray'][] = $fieldname.$abrow['code']."#1";
						$alsoother = "";
						
						if ($abrow['other'] == "Y") {$alsoother = "Y";}
						if ($arow['type'] == "P")
						{
							$_SESSION['insertarray'][] = $fieldname.$abrow['code']."comment";
						}
				}
		
			}
			if (isset($alsoother) && $alsoother) //Add an extra field for storing "Other" answers
			{
				$_SESSION['insertarray'][] = $fieldname."other";
				if ($arow['type'] == "P")
				{
					$_SESSION['insertarray'][] = $fieldname."othercomment";
				}
			}
		}

		elseif ($arow['type'] == "R")	// Ranking
		{


// Optimized Query
			$abquery = "SELECT ".db_table_name('answers').".code, ".db_table_name('questions').".other\n"
			. " FROM ".db_table_name('answers')."\n"
			. " INNER JOIN ".db_table_name('questions')."\n"
			. " ON ".db_table_name('answers').".qid=".db_table_name('questions').".qid\n"
			. " WHERE ".db_table_name('questions').".sid=$surveyid\n"
			. " AND ".db_table_name('questions').".qid={$arow['qid']}\n"
			. " AND ".db_table_name('questions').".language='".$_SESSION['s_lang']."' \n"
			. " AND ".db_table_name('answers').".language='".$_SESSION['s_lang']."' \n"
			. " ORDER BY ".db_table_name('answers').".sortorder, ".db_table_name('answers').".answer";

			$abresult = $connect->Execute($abquery) or safe_die("ERROR:<br />".$abquery."<br />".$connect->ErrorMsg());  //Checked 
			$abcount = $abresult->RecordCount();
			for ($i=1; $i<=$abcount; $i++)
			{
				$_SESSION['insertarray'][] = "$fieldname".$i;
			}
		}


		elseif ($arow['type'] == "Q" || $arow['type'] == "J" || $arow['type'] == "K")	// Multiple Short Text - ???
		{

// Optimized Query
            $abquery = "SELECT ".db_table_name('answers').".code\n"
            . " FROM ".db_table_name('answers')." \n"
            . " WHERE qid={$arow['qid']}\n"
            . " AND language='".$_SESSION['s_lang']."' \n"
            . " ORDER BY sortorder, answer";

			$abresult = db_execute_assoc($abquery);  //Checked 
			while ($abrow = $abresult->FetchRow())
			{
				$_SESSION['insertarray'][] = $fieldname.$abrow['code'];
			}
		}
		elseif ($arow['type'] == "O")	// List With Comment
		{
			$_SESSION['insertarray'][] = $fieldname;
			$fn2 = $fieldname."comment";
			$_SESSION['insertarray'][] = $fn2;
		}
		elseif ($arow['type'] == "L" || $arow['type'] == "!" || $arow['type'] == "Z" || $arow['type'] == "L")	// List (Radio) - List (Dropdown)
		{
			$_SESSION['insertarray'][] = $fieldname;
			if ($arow['other'] == "Y") { $_SESSION['insertarray'][] = $fieldname."other";}

		//go through answers, and if there is a default, register it now so that conditions work properly the first time

			$abquery = "SELECT a.code, a.default_value\n"
			. " FROM ".db_table_name('answers')." as a \n"
			. " WHERE a.qid={$arow['qid']}\n"
			. " ORDER BY a.sortorder, a.answer";

			$abresult = db_execute_assoc($abquery);       //Checked 
			while($abrow = $abresult->FetchRow())
			{
				if ($abrow['default_value'] == "Y")
				{
					$_SESSION[$fieldname] = $abrow['code'];
				}
			}
		}
		elseif  ($arow['type'] == "X")	// Boilerplate Question
		{
			$totalBoilerplatequestions++;
			$_SESSION['insertarray'][] = $fieldname;
		}
		else
		{
			$_SESSION['insertarray'][] = $fieldname;
		}



//		Separate query for each row no necessary because query above includes a sub-select now.
//		Increases performance by at least 17% and reduces number of queries executed
//		//Check to see if there are any conditions set for this question


		if ($arow['hasconditions']>0)
		{
			$conditions = "Y";
		}
		else
		{
			$conditions = "N";
		}


		//3(b) See if any of the insertarray values have been passed in the query URL

		if (isset($_SESSION['insertarray']))        
		{
            foreach($_SESSION['insertarray'] as $field)
		    {
			    if (isset($_GET[$field]))
			    {
				    $_SESSION[$field]=urldecode($_GET[$field]);
			    }
		    }
		}

		//4. SESSION VARIABLE: fieldarray
		//NOW WE'RE CREATING AN ARRAY CONTAINING EACH FIELD AND RELEVANT INFO
		//ARRAY CONTENTS - 	[0]=questions.qid,
		//					[1]=fieldname,
		//					[2]=questions.title,
		//					[3]=questions.question
		//                 	[4]=questions.type,
		//					[5]=questions.gid,
		//					[6]=questions.mandatory,
		//					[7]=conditionsexist
		$_SESSION['fieldarray'][] = array($arow['qid'],
		$fieldname,
		$arow['title'],
		$arow['question'],
		$arow['type'],
		$arow['gid'],
		$arow['mandatory'],
		$conditions);
	}
	// Check if the current survey language is set - if not set it
	// this way it can be changed later (for example by a special question type)

	return $totalquestions;
}

function surveymover()
{
	//This function creates the form elements in the survey navigation bar
	//with "<<PREV" or ">>NEXT" in them. The "submit" value determines how the script moves from
	//one survey page to another. It is a hidden element, updated by clicking
	//on the  relevant button - allowing "NEXT" to be the default setting when
	//a user presses enter.
	//
	//Attribute accesskey added for keyboard navigation.
	global $thissurvey, $clang;
	global $surveyid, $presentinggroupdescription;
	$surveymover = "";
	if (isset($_SESSION['step']) && $_SESSION['step'] && ($_SESSION['step'] == $_SESSION['totalsteps']) && !$presentinggroupdescription && $thissurvey['format'] != "A")
	{
		$surveymover = "<input type=\"hidden\" name=\"move\" value=\"movesubmit\" id=\"movesubmit\" />";
	}
	else
	{
		$surveymover = "<input type=\"hidden\" name=\"move\" value=\"movenext\" id=\"movenext\" />";
	}
	
	
	if (isset($_SESSION['step']) && $_SESSION['step'] > 0 && $thissurvey['format'] != "A" && !$presentinggroupdescription && $thissurvey['allowprev'] != "N")
	{
		$surveymover .= "<input class='submit' accesskey='p' type='button' onclick=\"javascript:document.limesurvey.move.value = 'moveprev'; document.limesurvey.submit();\" value=' << "
		. $clang->gT("Previous")." ' name='move2' />\n";
	}
	if (isset($_SESSION['step']) && $_SESSION['step'] && (!$_SESSION['totalsteps'] || ($_SESSION['step'] < $_SESSION['totalsteps'])))
	{
		$surveymover .=  "\t\t\t\t\t<input class='submit' type='submit' accesskey='n' onclick=\"javascript:document.limesurvey.move.value = 'movenext';\" value=' "
		. $clang->gT("Next")." >> ' name='move2' />\n";
	}
    // here, in some lace, is where I must modify to turn the next button conditionable
	if (!isset($_SESSION['step']) || !$_SESSION['step'])
	{
		$surveymover .=  "\t\t\t\t\t<input class='submit' type='submit' accesskey='n' onclick=\"javascript:document.limesurvey.move.value = 'movenext';\" value=' "
		. $clang->gT("Next")." >> ' name='move2' />\n";
	}
	if (isset($_SESSION['step']) && $_SESSION['step'] && ($_SESSION['step'] == $_SESSION['totalsteps']) && $presentinggroupdescription == "yes")
	{
		$surveymover .=  "\t\t\t\t\t<input class='submit' type='submit' onclick=\"javascript:document.limesurvey.move.value = 'movenext';\" value=' "
		. $clang->gT("Next")." >> ' name='move2' />\n";
	}
	if ($_SESSION['step'] && ($_SESSION['step'] == $_SESSION['totalsteps']) && !$presentinggroupdescription)
	{
		$surveymover .= "\t\t\t\t\t<input class='submit' type='submit' accesskey='l' onclick=\"javascript:document.limesurvey.move.value = 'movesubmit';\" value=' "
		. $clang->gT("Submit")." ' name='move2' />\n";
	}

//	$surveymover .= "<input type='hidden' name='PHPSESSID' value='".session_id()."' id='PHPSESSID' />\n";
	return $surveymover;
}

function doAssessment($surveyid)
{
	global $dbprefix, $thistpl, $connect;
	$query = "SELECT * FROM ".db_table_name('assessments')."
			  WHERE sid=$surveyid
			  ORDER BY scope";
	if ($result = db_execute_assoc($query))   //Checked 
	{
		if ($result->RecordCount() > 0)
		{
			while ($row=$result->FetchRow())
			{
				if ($row['scope'] == "G")
				{
					$assessment['group'][$row['gid']][]=array("name"=>$row['name'],
					"min"=>$row['minimum'],
					"max"=>$row['maximum'],
					"message"=>$row['message'],
					"link"=>$row['link']);
				}
				else
				{
					$assessment['total'][]=array( "name"=>$row['name'],
					"min"=>$row['minimum'],
					"max"=>$row['maximum'],
					"message"=>$row['message'],
					"link"=>$row['link']);
				}
			}
			$fieldmap=createFieldMap($surveyid, "full");
			$i=0;
			$total=0;
			
			// I added this condition : if (($field['type'] == "M") and ($_SESSION[$field['fieldname']] == "Y"))
			// because the internal representation of the answer of multiple Options type questions is Y, not the answer code
			// for this type of questions I use $field['aid'] insted of $_SESSION[$field['fieldname']]
			
			foreach($fieldmap as $field)
			{
				if (($field['fieldname'] != "datestamp") and ($field['fieldname'] != "refurl") and ($field['fieldname'] != "ipaddr") and ($field['fieldname'] != "token"))
				{
					if (isset($_SESSION[$field['fieldname']])) 
					{
						if (($field['type'] == "M") and ($_SESSION[$field['fieldname']] == "Y")) 	// for Multiple Options type questions
						{
							$fieldmap[$i]['answer']=$field['aid'];
							$total=$total+$field['aid'];
						}
						else     // any other type of question
						{
							$fieldmap[$i]['answer']=$_SESSION[$field['fieldname']];
							$total=$total+$_SESSION[$field['fieldname']];
						}
					}
					else {$fieldmap[$i]['answer']=0;}
					$groups[]=$field['gid'];
				}
                $i++;
			}
			$groups=array_unique($groups);

			foreach($groups as $group)
			{
				$grouptotal=0;
				foreach ($fieldmap as $field)
				{
					if ($field['gid'] == $group && isset($field['answer']))
					{
						//$grouptotal=$grouptotal+$field['answer'];
						if (isset ($_SESSION[$field['fieldname']])) 
						{
							if (($field['type'] == "M") and ($_SESSION[$field['fieldname']] == "Y")) 	// for Multiple Options type questions
								$grouptotal=$grouptotal+$field['aid'];
							else																		// any other type of question
								$grouptotal=$grouptotal+$_SESSION[$field['fieldname']];
						}
					}
				}
				$subtotal[$group]=$grouptotal;
			}
		}
		$assessments = "";
		if (isset($subtotal) && is_array($subtotal))
		{
			foreach($subtotal as $key=>$val)
			{
				if (isset($assessment['group'][$key]))
				{
					foreach($assessment['group'][$key] as $assessed)
					{
						if ($val >= $assessed['min'] && $val <= $assessed['max'])
						{
							$assessments .= "\t\t\t<!-- GROUP ASSESSMENT: Score: $val Min: ".$assessed['min']." Max: ".$assessed['max']."-->
        					    <table align='center'>
								 <tr>
								  <th>".str_replace(array("{PERC}", "{TOTAL}"), array($val, $val), stripslashes($assessed['name']))."
								  </th>
								 </tr>
								 <tr>
								  <td align='center'>".str_replace(array("{PERC}", "{TOTAL}"), array($val, $val), stripslashes($assessed['message']))."
								 </td>
								</tr>
							  	<tr>
								 <td align='center'><a href='".$assessed['link']."'>".$assessed['link']."</a>
								 </td>
								</tr>
							   </table><br />\n";
						}
					}
				}
			}
		}

		if (isset($assessment['total']))
		{
			foreach($assessment['total'] as $assessed)
			{
				if ($total >= $assessed['min'] && $total <= $assessed['max'])
				{
					$assessments .= "\t\t\t<!-- TOTAL ASSESSMENT: Score: $total Min: ".$assessed['min']." Max: ".$assessed['max']."-->
						<table align='center'><tr><th>".str_replace(array("{PERC}", "{TOTAL}"), array($val, $val), stripslashes($assessed['name']))."
						 </th></tr>
						 <tr>
						  <td align='center'>".str_replace(array("{PERC}", "{TOTAL}"), array($val, $val), stripslashes($assessed['message']))."
						  </td>
						 </tr>
						 <tr>
						  <td align='center'><a href='".$assessed['link']."'>".$assessed['link']."</a>
						  </td>
						 </tr>
						</table>\n";
				}
			}
		}

		return $assessments;
	}
}

function UpdateSessionGroupList($language)
//1. SESSION VARIABLE: grouplist
//A list of groups in this survey, ordered by group name.
{
   global $surveyid;
    unset ($_SESSION['grouplist']);
	$query = "SELECT * FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='".$language."' ORDER BY ".db_table_name('groups').".group_order";
	$result = db_execute_assoc($query) or safe_die ("Couldn't get group list<br />$query<br />".$connect->ErrorMsg());  //Checked 
	while ($row = $result->FetchRow())
	{
		$_SESSION['grouplist'][]=array($row['gid'], $row['group_name'], $row['description']);
	}
}

function UpdateFieldArray()
//The FieldArray contains all necessary information regarding the questions
//This function is needed to update it in case the survey is switched to another language

{
    global $surveyid;

    if (isset($_SESSION['fieldarray'])) 
    {
    	reset($_SESSION['fieldarray']);
    	while ( list($key) = each($_SESSION['fieldarray']) )
    	{
      		$questionarray =& $_SESSION['fieldarray'][$key];

           	$query = "SELECT * FROM ".db_table_name('questions')." WHERE qid=".$questionarray[0]." AND language='".$_SESSION['s_lang']."'";
        	$result = db_execute_assoc($query) or safe_die ("Couldn't get question <br />$query<br />".$connect->ErrorMsg());      //Checked 
        	$row = $result->FetchRow();
   	  		$questionarray[2]=$row['title'];
  	  		$questionarray[3]=$row['question'];
      		unset($questionarray);
    	}
    }

// This seems to only work in PHP 5 because of the referenced (&) array in the foreach construct
/*    foreach($_SESSION['fieldarray'] as &$questionarray) 
    {
    }
    */
}

/**
* getQuotaInformation() returns quota information for the current survey
* @param string $surveyid - Survey identification number
* @return array - nested array, Quotas->Members->Fields
*/
function getQuotaInformation($surveyid)
{
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	$query = "SELECT * FROM ".db_table_name('quota')." WHERE sid='{$surveyid}'";
	$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());    //Checked 
	$quota_info = array();
	$x=0;
	
	// Check all quotas for the current survey
	if ($result->RecordCount() > 0)
	{
		while ($survey_quotas = $result->FetchRow())
		{
			array_push($quota_info,array('Name' => $survey_quotas['name'],'Limit' => $survey_quotas['qlimit'],'Action' => $survey_quotas['action']));
			$query = "SELECT * FROM ".db_table_name('quota_members')." WHERE quota_id='{$survey_quotas['id']}'";
			$result_qe = db_execute_assoc($query) or safe_die($connect->ErrorMsg());      //Checked 
			$quota_info[$x]['members'] = array();
			if ($result_qe->RecordCount() > 0)
			{
				while ($quota_entry = $result_qe->FetchRow())
				{
					$query = "SELECT type, title,gid FROM ".db_table_name('questions')." WHERE qid='{$quota_entry['qid']}' AND language='{$baselang}'";
					$result_quest = db_execute_assoc($query) or safe_die($connect->ErrorMsg());     //Checked 
					$qtype = $result_quest->FetchRow();
					
					$fieldnames = "0";
					
					if ($qtype['type'] == "I" || $qtype['type'] == "G" || $qtype['type'] == "Y")
					{
						$fieldnames=array(0 => $surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid']);
						$value = $quota_entry['code'];
					}
					
					if($qtype['type'] == "M")
					{
						$fieldnames=array(0 => $surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid'].$quota_entry['code']);
						$value = "Y";
					}
					
					if($qtype['type'] == "A" || $qtype['type'] == "B")
					{
						$temp = explode('-',$quota_entry['code']);
						$fieldnames=array(0 => $surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid'].$temp[0]);
						$value = $temp[1];
					}
					
					array_push($quota_info[$x]['members'],array('Title' => $qtype['title'],'type' => $qtype['type'],'code' => $quota_entry['code'],'value' => $value,'qid' => $quota_entry['qid'],'fieldnames' => $fieldnames));
				}
			}
			$x++;
		}
	}
	return $quota_info;
}

/**
* check_quota() returns quota information for the current survey
* @param string $checkaction - action the function must take after completing:
* 								enforce: Enforce the quota action
* 								return: Return the updated quota array from getQuotaAnswers()
* @param string $surveyid - Survey identification number
* @return array - nested array, Quotas->Members->Fields, includes quota status and which members matched in session.
*/
function check_quota($checkaction,$surveyid)
{
	global $thistpl, $clang, $clienttoken;
	$global_matched = false;
	$quota_info = getQuotaInformation($surveyid);
	$x=0;

	if(count($quota_info) > 0) // Quota's have to exist
	{
		// Check each quota on saved data to see if it is full
		$querycond = array();
		foreach ($quota_info as $quota)
		{
			if (count($quota['members']) > 0) // Quota can't be empty
			{
				$fields_list = array(); // Keep a list of fields for easy reference
				$y=0;
				// We need to make the conditions for the select statement here
				// I'm supporting more than one field for a question/answer, not sure if this is necessary.
				unset($querycond);
				foreach($quota['members'] as $member)
				{
					$fields_query = array();
					$select_query = " (";
					foreach($member['fieldnames'] as $fieldname)
					{			
						$fields_list[] = $fieldname;
						$fields_query[] = "$fieldname = '{$member['value']}'";
						// Check which quota fields and codes match in session, for later use.
						// Incase of multiple fields for an answer - only needs to match once.
						if (isset($_SESSION[$fieldname]) && $_SESSION[$fieldname] == $member['value'])
						{
							$quota_info[$x]['members'][$y]['insession'] = "true";
						}
					}
					$select_query.= implode(' OR ',$fields_query)." )";
					$querycond[] = $select_query;
					unset($fields_query);
					$y++;
				}

				// Lets only continue if any of the quota fields is in the posted page
				$matched_fields = false;
				if (isset($_POST['fieldnames'])) 
				{
					$posted_fields = explode("|",$_POST['fieldnames']);
					foreach ($fields_list as $checkfield)
					{
						if (in_array($checkfield,$posted_fields))
						{
							$matched_fields = true;
							$global_matched = true;
						}
					}
				}

				// A field was submitted that is part of the quota
				
				if ($matched_fields == true)
				{
					
					// Check the status of the quota, is it full or not
					$querysel = "SELECT id FROM ".db_table_name('survey_'.$surveyid)." WHERE ".implode(' AND ',$querycond)." "." AND submitdate !=''";

					$result = db_execute_assoc($querysel) or safe_die($connect->ErrorMsg());    //Checked 
					$quota_check = $result->FetchRow();

					if ($result->RecordCount() >= $quota['Limit']) // Quota is full!! 
					{
						// Now we have to check if the quota matches in the current session
						// This will let us know if this person is going to exceed the quota
						
						$counted_matches = 0;
						foreach($quota_info[$x]['members'] as $member)
						{
							if (isset($member['insession']) && $member['insession'] == "true") $counted_matches++;
						}
						if($counted_matches == count($quota['members']))
						{
							// They are going to exceed the quota if data is submitted
							$quota_info[$x]['status']="matched";
							
						} else 
						{
							$quota_info[$x]['status']="notmatched";
						}

					} else
					{
						// Quota is no in danger of being exceeded.
						$quota_info[$x]['status']="notmatched";
					}
				}

			}
			$x++;
		}

	} else 
	{
		return false;
	}
	
	// Now we have all the information we need about the quotas and their status.
	// Lets see what we should do now
	if ($checkaction == 'return')
	{
		return $quota_info;
	} else if ($global_matched == true && $checkaction == 'enforce')
	{
		// Need to add quota action enforcement here.
		reset($quota_info);

		$tempmsg ="";
		$found = false;
		foreach($quota_info as $quota)
		{
			if ((isset($quota['status']) && $quota['status'] == "matched") && (isset($quota['Action']) && $quota['Action'] == "1"))
			{
				session_destroy();
				sendcacheheaders();
				doHeader();
				echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
				echo "\t<center><br />\n";
				echo "\t".$clang->gT("Sorry your responses have exceeded a quota on this survey.")."<br /></center>&nbsp;\n";
				echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
				doFooter();
				exit;
			}
			
			if ((isset($quota['status']) && $quota['status'] == "matched") && (isset($quota['Action']) && $quota['Action'] == "2"))
			{

				sendcacheheaders();
				doHeader();
				echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
				echo "\t<center><br />\n";
				echo "\t".$clang->gT("Sorry your responses have exceeded a quota on this survey.")."<br /></center>&nbsp;\n";
				echo "<form method='post' action='".$_SERVER['PHP_SELF']."' id='limesurvey' name='limesurvey'><input type=\"hidden\" name=\"move\" value=\"movenext\" id=\"movenext\" /><input class='submit' accesskey='p' type='button' onclick=\"javascript:document.limesurvey.move.value = 'moveprev'; document.limesurvey.submit();\" value=' << prev ' name='move2' />
					<input type='hidden' name='thisstep' value='".($_SESSION['step'])."' id='thisstep' />
					<input type='hidden' name='sid' value='".returnglobal('sid')."' id='sid' />
					<input type='hidden' name='token' value='".$clienttoken."' id='token' /></form>\n";
				echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
				doFooter();
				exit;
			}
		}
		
			
	} else {
		// Unknown value
		return false;
	}
	
}


?>
