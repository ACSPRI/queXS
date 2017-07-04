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
* $Id: index.php 12361 2012-02-05 19:40:30Z tmswhite $
*/

// Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

require_once(dirname(__FILE__).'/classes/core/startup.php');


require_once(dirname(__FILE__).'/config-defaults.php');
require_once(dirname(__FILE__).'/common.php');
require_once(dirname(__FILE__).'/classes/core/language.php');
include_once("quexs.php");

@ini_set('session.gc_maxlifetime', $sessionlifetime);

$loadname=returnglobal('loadname');
$loadpass=returnglobal('loadpass');
$scid=returnglobal('scid');
$thisstep=returnglobal('thisstep');
$move=sanitize_paranoid_string(returnglobal('move'));
$clienttoken=sanitize_token(returnglobal('token'));


if (!isset($thisstep))
{
    $thisstep = "";
}


if (!isset($surveyid))
{
    $surveyid=returnglobal('sid');
}
else
{
    //This next line ensures that the $surveyid value is never anything but a number.
    $surveyid=sanitize_int($surveyid);
}

//queXS Addition
if (isset($_GET['loadall']) && $_GET['loadall'] == "reload" && isset($_GET['token']))
{
	$_POST['loadall']="reload";
	$_POST['token']=$_GET['token'];
 	
 	//Must destroy the session
  session_unset();
  @session_destroy();
}

//end queXS Addition


//LimeExpressionManager::SetSurveyId($surveyid);  // must be called early - it clears internal cache if a new survey is being used

//DEFAULT SETTINGS FOR TEMPLATES
if (!$publicdir)
{
    $publicdir=".";
}

// First check if survey is active
// if not: copy some vars from the admin session
// to a new user session

if ($surveyid)
{
    $issurveyactive=false;
    $aRow=$connect->GetRow("SELECT * FROM ".db_table_name('surveys')." WHERE sid=$surveyid");
    if (isset($aRow['active']))
    {
        $surveyexists=true;
        if($aRow['active']=='Y')
        {
            $issurveyactive=true;
        }
    }
    else
    {
        $surveyexists=false;
    }
}

// Compute the Session name
// Session name is based:
// * on this specific limesurvey installation (Value SessionName in DB)
// * on the surveyid (from Get or Post param). If no surveyid is given we are on the public surveys portal
//$usquery = "SELECT stg_value FROM ".db_table_name("settings_global")." where stg_name='SessionName'";
//$usresult = db_execute_assoc($usquery,'',true);          //Checked
$usresult = LS_SESSION_NAME; //queXS Addition
if ($usresult)
{
    $stg_SessionName=$usresult;
    if ($surveyid && $surveyexists)
    {
        @session_name($stg_SessionName.'-runtime-'.$surveyid);
    }
    else
    {
        @session_name($stg_SessionName.'-runtime-publicportal');
    }
}
else
{
    session_name("LimeSurveyRuntime-$surveyid");
}
session_set_cookie_params(0,$relativeurl.'/');



if (!isset($_SESSION) || empty($_SESSION)) // the $_SESSION variable can be empty if register_globals is on
    @session_start();

if ( $embedded && $embedded_inc != '' )
{
    require_once( $embedded_inc );
}

//queXS Addition
//see who is doing this survey - an interviewer or the respondent directly
//

  $interviewer=returnglobal('interviewer');
  if (empty($interviewer))
  {
    $interviewer = false;
  }
  if (!isset($_SESSION['interviewer'])) {
    $_SESSION['interviewer'] = $interviewer;
  }
 



//CHECK FOR REQUIRED INFORMATION (sid)
if (!$surveyid || !$surveyexists)
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
    $languagechanger = makelanguagechanger();
    //Find out if there are any publicly available surveys
    $query = "SELECT a.sid, b.surveyls_title, a.publicstatistics,a.language
    FROM ".db_table_name('surveys')." AS a
    INNER JOIN ".db_table_name('surveys_languagesettings')." AS b
    ON ( surveyls_survey_id = a.sid AND surveyls_language = a.language )
    WHERE surveyls_survey_id=a.sid
    AND surveyls_language=a.language
    AND a.active='Y'
    AND a.listpublic='Y'
    AND ((a.expires >= '".date("Y-m-d H:i")."') OR (a.expires is null))
    AND ((a.startdate <= '".date("Y-m-d H:i")."') OR (a.startdate is null))
    ORDER BY surveyls_title";
    $result = db_execute_assoc($query,false,true) or die("Could not connect to database. If you try to install LimeSurvey please refer to the <a href='http://docs.limesurvey.org'>installation docs</a> and/or contact the system administrator of this webpage."); //Checked
    $list=array();
    if($result->RecordCount() > 0)
    {
        while($rows = $result->FetchRow())
        {
            $sLinkLanguage=$rows['language'];
            $result2 = db_execute_assoc("Select surveyls_title from ".db_table_name('surveys_languagesettings')." where surveyls_survey_id={$rows['sid']} and surveyls_language='$baselang'");
            if ($result2->RecordCount())
            {
                $languagedetails=$result2->FetchRow();
                $rows['surveyls_title']=$languagedetails['surveyls_title'];
                $sLinkLanguage=$baselang;
            }
            $link = "<li><a href='$rooturl/index.php?sid=".$rows['sid'];
            if (isset($_GET['lang']))
            {
                $link .= "&amp;lang=".$sLinkLanguage;
            }
            $link .= "'  class='surveytitle'>".$rows['surveyls_title']."</a>\n";
            if ($rows['publicstatistics'] == 'Y') $link .= "<a href='{$relativeurl}/statistics_user.php?sid={$rows['sid']}'>(".$clang->gT('View statistics').")</a>";
            $link .= "</li>\n";
            $list[]=$link;
        }
    }
    if(count($list) < 1)
    {
        $list[]="<li class='surveytitle'>".$clang->gT("No available surveys")."</li>";
    }

    if(!$surveyid)
    {
        $thissurvey['name']=$sitename;
        $nosid=$clang->gT("You have not provided a survey identification number");
    }
    else
    {
        $thissurvey['name']=$clang->gT("The survey identification number is invalid");
        $nosid=$clang->gT("The survey identification number is invalid");
    }
    $surveylist=array(
    "nosid"=>$clang->gT("You have not provided a survey identification number"),
    "contact"=>sprintf($clang->gT("Please contact %s ( %s ) for further assistance."),$siteadminname,encodeEmail($siteadminemail)),
    "listheading"=>$clang->gT("The following surveys are available:"),
    "list"=>implode("\n",$list),
    );

    $thissurvey['templatedir']=$defaulttemplate;

    //A nice exit
    sendcacheheaders();
    doHeader();
    echo templatereplace(file_get_contents(sGetTemplatePath($defaulttemplate)."/startpage.pstpl"));

    echo templatereplace(file_get_contents(sGetTemplatePath($defaulttemplate)."/surveylist.pstpl"));

    echo templatereplace(file_get_contents(sGetTemplatePath($defaulttemplate)."/endpage.pstpl"));
    doFooter();
    exit;
}

if ($clienttoken != '' && isset($_SESSION['token']) &&
$clienttoken != $_SESSION['token'])
{
    require_once(dirname(__FILE__).'/classes/core/language.php');
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    $clang = new limesurvey_lang($baselang);
    // Let's first regenerate a session id
    killSession();
    // Let's redirect the client to the same URL after having reseted the session
    header("Location: $rooturl/index.php?" .$_SERVER['QUERY_STRING']);
    sendcacheheaders();
    doHeader();

    echo templatereplace(file_get_contents(sGetTemplatePath($defaulttemplate)."/startpage.pstpl"));
    echo "\t<div id='wrapper'>\n"
    ."\t<p id='tokenmessage'>\n"
    ."\t<span class='error'>".$clang->gT("Token mismatch")."</span><br /><br />\n"
    ."\t".$clang->gT("The token you provided doesn't match the one in your session.")."<br /><br />\n"
    ."\t".$clang->gT("Please wait to begin with a new session.")."<br /><br />\n"
    ."\t</p>\n"
    ."\t</div>\n";

    echo templatereplace(file_get_contents(sGetTemplatePath($defaulttemplate)."/endpage.pstpl"));
    doFooter();
    exit;
}

if (isset($_SESSION['finished']) && $_SESSION['finished'] === true)
{
    require_once(dirname(__FILE__).'/classes/core/language.php');
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    $clang = new limesurvey_lang($baselang);
    // Let's first regenerate a session id
    killSession();
    // Let's redirect the client to the same URL after having reseted the session
    header("Location: $rooturl/index.php?" .$_SERVER['QUERY_STRING']);
    sendcacheheaders();
    doHeader();

    echo templatereplace(file_get_contents(sGetTemplatePath($defaulttemplate)."/startpage.pstpl"));
    echo "\t<div id='wrapper'>\n"
    ."\t<p id='tokenmessage'>\n"
    ."\t<span class='error'>".$clang->gT("Previous session is set to be finished.")."</span><br /><br />\n"
    ."\t".$clang->gT("Your browser reports that it was used previously to answer this survey. We are resetting the session so that you can start from the beginning.")."<br /><br />\n"
    ."\t".$clang->gT("Please wait to begin with a new session.")."<br /><br />\n"
    ."\t</p>\n"
    ."\t</div>\n";

    echo templatereplace(file_get_contents(sGetTemplatePath($defaulttemplate)."/endpage.pstpl"));
    doFooter();
    exit;
}
$previewgrp = false;
if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'previewgroup')){
    $rightquery="SELECT uid FROM {$dbprefix}survey_permissions WHERE sid=".db_quote($surveyid)." AND uid = ".db_quote($_SESSION['loginID'].' group by uid');
    $rightresult = db_execute_assoc($rightquery);
    if ($rightresult->RecordCount() > 0 || $_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
    {
        $previewgrp = true;
    }
}

if (($surveyid &&
$issurveyactive===false && $surveyexists &&
isset ($surveyPreview_require_Auth) &&
$surveyPreview_require_Auth == true) &&  $previewgrp == false)
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
        // but if it throws an error then future
        // session functions won't work because
        // headers are already sent.
        if (isset($stg_SessionName) && $stg_SessionName)
        {
            @session_name($stg_SessionName);
        }
        else
        {
            session_name("LimeSurveyAdmin");
        }
        session_start(); // Loads Admin Session

        $previewright=false;
        $savesessionvars=Array();
        if (isset($_SESSION['loginID']))
        {
            $rightquery="SELECT uid FROM {$dbprefix}survey_permissions WHERE sid=".db_quote($surveyid)." AND uid = ".db_quote($_SESSION['loginID'].' group by uid');
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
        if ($sessionhandler=='db')
        {
            adodb_session_regenerate_id();
        }
        elseif (session_regenerate_id() === false)
        {
            safe_die("Error Regenerating Session Id");
        }
        @session_destroy();

        // start new session
        @session_start();
        // regenerate id so that the header geenrated by previous
        // regenerate_id is overwritten
        // needed after clearall
        if ($sessionhandler=='db')
        {
            adodb_session_regenerate_id();
        }
        elseif (session_regenerate_id() === false)
        {
            safe_die("Error Regenerating Session Id");
        }

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

    if ($previewright === false)
    {
        // print an error message
        if (isset($_REQUEST['rootdir']))
        {
            safe_die('You cannot start this script directly');
        }
        require_once(dirname(__FILE__).'/classes/core/language.php');
        $baselang = GetBaseLanguageFromSurveyID($surveyid);
        $clang = new limesurvey_lang($baselang);
        //A nice exit
        sendcacheheaders();
        doHeader();

        echo templatereplace(file_get_contents(sGetTemplatePath($defaulttemplate)."/startpage.pstpl"));
        echo "\t<div id='wrapper'>\n"
        ."\t<p id='tokenmessage'>\n"
        ."\t<span class='error'>".$clang->gT("ERROR")."</span><br /><br />\n"
        ."\t".$clang->gT("We are sorry but you don't have permissions to do this.")."<br /><br />\n"
        ."\t".sprintf($clang->gT("Please contact %s ( %s ) for further assistance."),$siteadminname,encodeEmail($siteadminemail))."<br /><br />\n"
        ."\t</p>\n"
        ."\t</div>\n";

        echo templatereplace(file_get_contents(sGetTemplatePath($defaulttemplate)."/endpage.pstpl"));
        doFooter();
        exit;
    }
}
if (isset($_SESSION['srid']))
{
    $saved_id = $_SESSION['srid'];
}

if (isset($move) && (preg_match('/^changelang_/',$move)))
{
    // Then changing language from the language changer
    $_POST['lang'] = substr($_POST['move'],11); // since sanitizing $move removes hyphen in languages like de-informal
}

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
    else
        if (isset($_SESSION['s_lang']))
        {
            $clang = SetSurveyLanguage( $surveyid, $_SESSION['s_lang']);
        }
        elseif (isset($surveyid) && $surveyid)
        {
            $baselang = GetBaseLanguageFromSurveyID($surveyid);
            $clang = SetSurveyLanguage( $surveyid, $baselang);
        }

        if (isset($_REQUEST['embedded_inc']))
{
    safe_die('You cannot start this script directly');
}


// Get token
if (!isset($token))
{
    $token=$clienttoken;
}

//GET BASIC INFORMATION ABOUT THIS SURVEY
$totalBoilerplatequestions =0;
$thissurvey=getSurveyInfo($surveyid, $_SESSION['s_lang']);

if (isset($_GET['newtest']) && $_GET['newtest'] == "Y")
{
    //Removes any existing timer cookies so timers will start again
    setcookie ("limesurvey_timers", "", time() - 3600);
}

//SEE IF SURVEY USES TOKENS AND GROUP TOKENS
$i = 0; //$tokensexist = 0;
if ($surveyexists == 1 && tableExists('tokens_'.$thissurvey['sid']))
{
    $tokensexist = 1;

}
else
{
    $tokensexist = 0;
    unset ($_POST['token']);
    unset ($_GET['token']);
    unset($token);
    unset($clienttoken);
}


$qtmp = quexs_get_template($clienttoken);

if ($_SESSION['interviewer'] || $qtmp === false)
{
	//SET THE TEMPLATE DIRECTORY
	if (!$thissurvey['templatedir'])
	{
	    $thistpl=sGetTemplatePath($defaulttemplate);
	}
	else
	{
	    $thistpl=sGetTemplatePath($thissurvey['templatedir']);
	}
}
else
{
	$thissurvey['templatedir'] = $qtmp;
	$thistpl=sGetTemplatePath($qtmp);
}



//MAKE SURE SURVEY HASN'T EXPIRED
if ($thissurvey['expiry']!='' and date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust)>$thissurvey['expiry'] && $thissurvey['active']!='N')
{

    sendcacheheaders();
    doHeader();

    echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
    echo "\t<div id='wrapper'>\n"
    ."\t<p id='tokenmessage'>\n"
    ."\t".$clang->gT("This survey is no longer available.")."<br /><br />\n"
    ."\t".sprintf($clang->gT("Please contact %s ( %s ) for further assistance."),$thissurvey['adminname'],$thissurvey['adminemail']).".<br /><br />\n"
    ."\t</p>\n"
    ."\t</div>\n";

    echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
    doFooter();
    exit;
}

//MAKE SURE SURVEY IS ALREADY VALID
if ($thissurvey['startdate']!='' and  date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust)<$thissurvey['startdate'] && $thissurvey['active']!='N')
{
    sendcacheheaders();
    doHeader();

    echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
    echo "\t<div id='wrapper'>\n"
    ."\t<p id='tokenmessage'>\n"
    ."\t".$clang->gT("This survey is not yet started.")."<br /><br />\n"
    ."\t".sprintf($clang->gT("Please contact %s ( %s ) for further assistance."),$thissurvey['adminname'],$thissurvey['adminemail']).".<br /><br />\n"
    ."\t</p>\n"
    ."\t</div>\n";

    echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
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

    echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
    echo "\t<div id='wrapper'>\n"
    ."\t<p id='tokenmessage'>\n"
    ."\t<span class='error'>".$clang->gT("Error")."</span><br /><br />\n"
    ."\t".$clang->gT("You have already completed this survey.")."<br /><br />\n"
    ."\t".sprintf($clang->gT("Please contact %s ( %s ) for further assistance."),$thissurvey['adminname'],$thissurvey['adminemail'])."\n"
    ."\t</p>\n"
    ."\t</div>\n";

    echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
    doFooter();
    exit;
}




//CHECK IF SURVEY ID DETAILS HAVE CHANGED
if (isset($_SESSION['oldsid']))
{
    $oldsid=$_SESSION['oldsid'];
}

if (!isset($oldsid))
{
    $_SESSION['oldsid'] = $surveyid;
}

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


/* queXS Removal
if (isset($_GET['loadall']) && $_GET['loadall'] == "reload")
{
    if (returnglobal('loadname') && returnglobal('loadpass'))
    {
        $_POST['loadall']="reload";
    }
}
end queXS Removal */

//LOAD SAVED SURVEY
if (isset($_POST['loadall']) && $_POST['loadall'] == "reload")
{
    $errormsg="";
    // if (loadname is not set) or if ((loadname is set) and (loadname is NULL))
	
	/* queXS Removal

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
    // Not called if scid is set in GET params (when using email save/reload reminder URL)
    if (function_exists("ImageCreate") && captcha_enabled('saveandloadscreen',$thissurvey['usecaptcha']))
    {
        if ( (!isset($_POST['loadsecurity']) ||
        !isset($_SESSION['secanswer']) ||
        $_POST['loadsecurity'] != $_SESSION['secanswer']) &&
        !isset($_GET['scid']))
        {
            $errormsg .= $clang->gT("The answer to the security question is incorrect.")."<br />\n";
        }
    }

	end queXS Removal */ 

    // Load session before loading the values from the saved data
    if (isset($_GET['loadall']))
    {
        $totalquestions = buildsurveysession();
    }

    $_SESSION['holdname']=$_POST['token']; //Session variable used to load answers every page.
    $_SESSION['holdpass']=$_POST['token']; //Session variable used to load answers every page.

    if ($errormsg == "") loadanswers();
    $move = "movecurrent";
    $_SESSION['LEMreload']=true;

    if ($errormsg)
    {
        $_POST['loadall'] = $clang->gT("Load Unfinished Survey");
    }
}

/* queXS Removal 

//Allow loading of saved survey
if (isset($_POST['loadall']) && $_POST['loadall'] == $clang->gT("Load Unfinished Survey"))
{
    require_once("load.php");
}

end queXS Removal */

//Check if TOKEN is used for EVERY PAGE
//This function fixes a bug where users able to submit two surveys/votes
//by checking that the token has not been used at each page displayed.
// bypass only this check at first page (Step=0) because
// this check is done in buildsurveysession and error message
// could be more interresting there (takes into accound captcha if used)
if ($tokensexist == 1 && isset($token) && $token &&
isset($_SESSION['step']) && $_SESSION['step']>0 && db_tables_exist($dbprefix.'tokens_'.$surveyid))
{
    //check if tokens actually haven't been already used
    $areTokensUsed = usedTokens(db_quote(trim(strip_tags(returnglobal('token')))));
    // check if token actually does exist
    // check also if it is allowed to change survey after completion
    if ($thissurvey['alloweditaftercompletion'] == 'Y' ) {
        $tkquery = "SELECT * FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote($token)."' ";
    } else {
        $tkquery = "SELECT * FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote($token)."' AND (completed = 'N' or completed='')";
    }
    $tkresult = db_execute_num($tkquery); //Checked
    $tokendata = $tkresult->FetchRow();
    if ($tkresult->RecordCount()==0 || ($areTokensUsed && $thissurvey['alloweditaftercompletion'] != 'Y'))
    {
        sendcacheheaders();
        doHeader();
        //TOKEN DOESN'T EXIST OR HAS ALREADY BEEN USED. EXPLAIN PROBLEM AND EXIT

        echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
        echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
        echo "\t<div id='wrapper'>\n"
        ."\t<p id='tokenmessage'>\n"
        ."\t".$clang->gT("This is a controlled survey. You need a valid token to participate.")."<br /><br />\n"
        ."\t".$clang->gT("The token you have provided is either not valid, or has already been used.")."\n"
        ."\t".sprintf($clang->gT("For further information please contact %s"), $thissurvey['adminname']
        ." (<a href='mailto:{$thissurvey['adminemail']}'>"
        ."{$thissurvey['adminemail']}</a>)")."\n"
        ."\t</p>\n"
        ."\t</div>\n";

        echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
        killSession();
        doFooter();
        exit;
    }
}
if ($tokensexist == 1 && isset($token) && $token && db_tables_exist($dbprefix.'tokens_'.$surveyid)) //check if token is in a valid time frame
{

    // check also if it is allowed to change survey after completion
    if ($thissurvey['alloweditaftercompletion'] == 'Y' ) {
        $tkquery = "SELECT * FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote($token)."' ";
    } else {
        $tkquery = "SELECT * FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote($token)."' AND (completed = 'N' or completed='')";
    }
    $tkresult = db_execute_assoc($tkquery); //Checked
    $tokendata = $tkresult->FetchRow();
    if ((trim($tokendata['validfrom'])!='' && $tokendata['validfrom']>date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust)) ||
    (trim($tokendata['validuntil'])!='' && $tokendata['validuntil']<date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust)))
    {
        sendcacheheaders();
        doHeader();
        //TOKEN DOESN'T EXIST OR HAS ALREADY BEEN USED. EXPLAIN PROBLEM AND EXIT

        echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
        echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
        echo "\t<div id='wrapper'>\n"
        ."\t<p id='tokenmessage'>\n"
        ."\t".$clang->gT("We are sorry but you are not allowed to enter this survey.")."<br /><br />\n"
        ."\t".$clang->gT("Your token seems to be valid but can be used only during a certain time period.")."<br />\n"
        ."\t".sprintf($clang->gT("For further information please contact %s"), $thissurvey['adminname']
        ." (<a href='mailto:{$thissurvey['adminemail']}'>"
        ."{$thissurvey['adminemail']}</a>)")."\n"
        ."\t</p>\n"
        ."\t</div>\n";

        echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
        doFooter();
        killSession();
        exit;
    }
}



//Clear session and remove the incomplete response if requested.
if (isset($_GET['move']) && $_GET['move'] == "clearall")
{
    $s_lang = $_SESSION['s_lang'];
    if (isset($_SESSION['srid']) && !isCompleted($surveyid,$_SESSION['srid']))
    {
        // delete the response but only if not already completed
        $result = $connect->query('SELECT id FROM '.db_table_name('survey_'.$surveyid).' WHERE id='.$_SESSION['srid']." AND submitdate IS NULL");
        if($result->RecordCount()>0)
        {
            $connect->query('DELETE FROM '.db_table_name('survey_'.$surveyid).' WHERE id='.$_SESSION['srid']." AND submitdate IS NULL");
            // find out if there are any fuqt questions - checked
            $fieldmap = createFieldMap($surveyid);
            foreach ($fieldmap as $field)
            {
                if ($field['type'] == "|" && !strpos($field['fieldname'], "_filecount"))
                {
                    if (!isset($qid)) { $qid = array(); }
                    $qid[] = $field['fieldname'];
                }
            }
            // if yes, extract the response json to those questions
            if (isset($qid))
            {
                $query = "SELECT * FROM ".db_table_name("survey_".$surveyid)." WHERE id=".$_SESSION['srid'];
                $result = db_execute_assoc($query);
                while ($row = $result->FetchRow())
                {
                    foreach ($qid as $question)
                    {
                        $json = $row[$question];
                        if ($json == "" || $json == NULL)
                            continue;

                        // decode them
                        $phparray = json_decode($json);

                        foreach ($phparray as $metadata)
                        {
                            $target = "{$uploaddir}/surveys/{$surveyid}/files/";
                            // delete those files
                            unlink($target.$metadata->filename);
                        }
                    }
                }
            }
            // done deleting uploaded files
        }

        // also delete a record from saved_control when there is one, we can allway do it.
        $connect->query('DELETE FROM '.db_table_name('saved_control'). ' WHERE srid='.$_SESSION['srid'].' AND sid='.$surveyid);
    }
    session_unset();
    session_destroy();
    setcookie(session_name(),"EXPIRED",time()-120);
    sendcacheheaders();
    doHeader();
    echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
    echo "\n\n<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->\n"
    ."\t<script type='text/javascript'>\n"
    ."\t<!--\n"
    ."function checkconditions(value, name, type, evt_type)\n"
    ."\t{\n"
    ."\t}\n"
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
    setcookie($cookiename, "INCOMPLETE", time()-120);
    //echo "Reset Cookie!";
}

//Check to see if a refering URL has been captured.
GetReferringUrl();
// Let's do this only if
//  - a saved answer record hasn't been loaded through the saved feature
//  - the survey is not anonymous
//  - the survey is active
//  - a token information has been provided
//  - the survey is setup to allow token-response-persistence
if (!isset($_SESSION['srid']) && $thissurvey['anonymized'] == "N" && $thissurvey['active'] == "Y" && isset($token) && $token !='')
{
    // load previous answers if any (dataentry with nosubmit)
    $srquery="SELECT id,submitdate,lastpage FROM {$thissurvey['tablename']}"
    . " WHERE {$thissurvey['tablename']}.token='".db_quote($token)."' order by id desc";

    $result = db_select_limit_assoc($srquery,1);
    if ($result->RecordCount()>0)
    {
        $row=$result->FetchRow();
        if(($row['submitdate']==''  && $thissurvey['tokenanswerspersistence'] == 'Y' )|| ($row['submitdate']!='' && $thissurvey['alloweditaftercompletion'] == 'Y'))
        {
            $_SESSION['srid'] = $row['id'];
            if (!is_null($row['lastpage']) && $row['submitdate']=='')
            {
                $_SESSION['LEMtokenResume'] = true;
                $_SESSION['step'] = $row['lastpage'];
            }
        }
        buildsurveysession();
        loadanswers();
    }
}

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'previewgroup')){
    $thissurvey['format'] = 'G';
    buildsurveysession(true);
}

sendcacheheaders();
//CALL APPROPRIATE SCRIPT

require_once("group.php");  // works for all survey styles - rename to navigation_controller.php?

//queXS Addition
if (isset($_POST['move']) || isset($_POST['saveprompt'])) savedcontrol();

/* queXS Removal
if (isset($_POST['saveall']) || isset($flashmessage))
{
    echo "<script language='JavaScript'> $(document).ready( function() {alert('".$clang->gT("Your responses were successfully saved.","js")."');}) </script>";
}
end queXS Removal */

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
        $query .="AND ".db_table_name('saved_control').".identifier = '".auto_escape($_SESSION['holdname'])."' ";

        if ($databasetype=='odbc_mssql' || $databasetype=='odbtp' || $databasetype=='mssql_n' || $databasetype=='mssqlnative')
        {
            $query .="AND CAST(".db_table_name('saved_control').".access_code as varchar(32))= '".md5(auto_unescape($_SESSION['holdpass']))."'\n";
        }
        else
        {
            $query .="AND ".db_table_name('saved_control').".access_code = '".md5(auto_unescape($_SESSION['holdpass']))."'\n";
        }
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
        $_SESSION['LEMtokenResume']=true;

        $row=$result->FetchRow();
        foreach ($row as $column => $value)
        {
            if ($column == "token")
            {
                $clienttoken=$value;
                $token=$value;
            }
            elseif ($column == "saved_thisstep" && $thissurvey['alloweditaftercompletion'] != 'Y' )
            {
                $_SESSION['step']=$value;
                $thisstep=$value-1;
            }
            elseif ($column =='lastpage' && isset($_GET['token']) && $thissurvey['alloweditaftercompletion'] != 'Y' )
            {
                if ($value<1) $value=1;
                $_SESSION['step']=$value;
                $thisstep=$value-1;
            }
            /*
            Commented this part out because otherwise startlanguage would overwrite any other language during a running survey.
            We will need a new field named 'endlanguage' to save the current language (for example for returning participants)
            /the language the survey was completed in.
            elseif ($column =='startlanguage')
            {
            $clang = SetSurveyLanguage( $surveyid, $value);
            UpdateSessionGroupList($value);  // to refresh the language strings in the group list session variable
            UpdateFieldArray();        // to refresh question titles and question text
            }*/
            elseif ($column == "scid")
            {
                $_SESSION['scid']=$value;
            }
            elseif ($column == "srid")
            {
                $_SESSION['srid']=$value;
            }
            elseif ($column == "datestamp")
            {
                $_SESSION['datestamp']=$value;
            }
            if ($column == "startdate")
            {
                $_SESSION['startdate']=$value;
            }
            else
            {
                //Only make session variables for those in insertarray[]
                if (in_array($column, $_SESSION['insertarray']))
                {
                    //                    if (($_SESSION['fieldmap'][$column]['type'] == 'N' ||
                    //                            $_SESSION['fieldmap'][$column]['type'] == 'K' ||
                    //                            $_SESSION['fieldmap'][$column]['type'] == 'D') && $value == null)
                    //                    {   // For type N,K,D NULL in DB is to be considered as NoAnswer in any case.
                    //                        // We need to set the _SESSION[field] value to '' in order to evaluate conditions.
                    //                        // This is especially important for the deletenonvalue feature,
                    //                        // otherwise we would erase any answer with condition such as EQUALS-NO-ANSWER on such
                    //                        // question types (NKD)
                    //                        $_SESSION[$column]='';
                    //                    }
                    //                    else
                    //                    {
                    $_SESSION[$column]=$value;
                    //                }
                }  // if (in_array(
            }  // else
        } // foreach
    }
    return true;
}

function makegraph($currentstep, $total)
{
    global $thissurvey;
    global $publicurl, $clang;

    $size = intval(($currentstep-1)/$total*100);

    return  '<div>'.sprintf($clang->gT('%s%% complete'),$size).'</div>';
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
        $sBaseLanguage = GetBaseLanguageFromSurveyID($surveyid);

    }

    // TODO - When is this needed?
    $token = sanitize_token(returnglobal('token'));
    if ($token != '')
    {
        $tokenparam = "&token=$token";
    }
    else
    {
        $tokenparam = "";
    }
    $previewgrp = false;
    if (isset($_REQUEST['action']))
        if ($_REQUEST['action']=='previewgroup')
            $previewgrp = true;

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
                {
                    $lang = $sBaseLanguage;
        }
        $slangs[]=$sBaseLanguage;
        $aAllLanguages=getLanguageData();
        $slangs=array_keys(array_intersect_key($aAllLanguages,array_flip($slangs))); // Sort languages by their locale name

        // Changed how language changer works so that posts any currently set values.  This also ensures that token (and other) parmeters are also posted.
        //        $htmlcode ="<select name=\"select\" class='languagechanger' onchange=\"javascript:window.location=this.value\">\n";
        $htmlcode ="<select name=\"select\" class='languagechanger' "
        . " onchange=\"javascript:$('[name=move]').val('changelang_'+ this.value);$('#limesurvey').submit();\">\n";

        $sAddToURL = "";
        $sTargetURL = "$relativeurl/index.php";
        if ($previewgrp){
            $sAddToURL = "&amp;action=previewgroup&amp;gid={$_REQUEST['gid']}";
            $sTargetURL = "";
        }
        foreach ($slangs as $otherlang)
        {
            //            $htmlcode .= "\t<option value=\"$sTargetURL?sid=". $surveyid ."&amp;lang=". $otherlang ."$tokenparam$sAddToURL\" ";
            $htmlcode .= "\t<option value=\"". $otherlang ."\" ";

            if($otherlang == $lang)
            {
                $htmlcode .= " selected=\"selected\" ";
            }
            $htmlcode .= ">".$aAllLanguages[$otherlang]['nativedescription']."</option>\n";
        }

        $htmlcode .= "</select>\n";
        //    . "</form>";

        return $htmlcode;
    } elseif (!isset($surveyid))
    {
        global $defaultlang, $baselang;
        $htmlcode = "<select name=\"select\" class='languagechanger' onchange=\"javascript:window.location=this.value\">\n";
        $htmlcode .= "<option value=\"$relativeurl/index.php?lang=". $defaultlang ."$tokenparam\">".getLanguageNameFromCode($defaultlang,false)."</option>\n";
        foreach(getlanguagedata() as $key=>$val)
        {
            $htmlcode .= "\t<option value=\"$relativeurl/index.php?lang=".$key."$tokenparam\" ";
            $htmlcode .= ">".getLanguageNameFromCode($key,false)."</option>\n";
        }
        $htmlcode .= "</select>\n";
        return $htmlcode;
    }
}

function checkUploadedFileValidity($move, $backok=null)
{
    global $connect, $thisstep, $clang;
    if (!isset($backok) || $backok != "Y")
    {
        global $dbprefix;
        $fieldmap = createFieldMap(returnglobal('sid'));

        if (isset($_POST['fieldnames']) && $_POST['fieldnames']!="")
        {
            $fields = explode("|", $_POST['fieldnames']);

            foreach ($fields as $field)
            {
                if ($fieldmap[$field]['type'] == "|" && !strrpos($fieldmap[$field]['fieldname'], "_filecount"))
                {
                    $validation = array();

                    $query = "SELECT * FROM ".$dbprefix."question_attributes WHERE qid = ".$fieldmap[$field]['qid'];
                    $result = db_execute_assoc($query);
                    while ($row = $result->FetchRow())
                        $validation[$row['attribute']] = $row['value'];

                    $filecount = 0;

                    $json = $_POST[$field];
                    // if name is blank, its basic, hence check
                    // else, its ajax, don't check, bypass it.

                    if ($json != "" && $json != "[]")
                    {
                        $phparray = json_decode(stripslashes($json));
                        if ($phparray[0]->size != "")
                        { // ajax
                            $filecount = count($phparray);
                        }
                        else
                        { // basic
                            for ($i = 1; $i <= $validation['max_num_of_files']; $i++)
                            {
                                if (!isset($_FILES[$field."_file_".$i]) || $_FILES[$field."_file_".$i]['name'] == '')
                                    continue;

                                $filecount++;

                                $file = $_FILES[$field."_file_".$i];

                                // File size validation
                                if ($file['size'] > $validation['max_filesize'] * 1000)
                                {
                                    $filenotvalidated = array();
                                    $filenotvalidated[$field."_file_".$i] = sprintf($clang->gT("Sorry, the uploaded file (%s) is larger than the allowed filesize of %s KB."), $file['size'], $validation['max_filesize']);
                                    $append = true;
                                }

                                // File extension validation
                                $pathinfo = pathinfo(basename($file['name']));
                                $ext = $pathinfo['extension'];

                                $validExtensions = explode(",", $validation['allowed_filetypes']);
                                if (!(in_array($ext, $validExtensions)))
                                {
                                    if (isset($append) && $append)
                                    {
                                        $filenotvalidated[$field."_file_".$i] .= sprintf($clang->gT("Sorry, only %s extensions are allowed!"),$validation['allowed_filetypes']);
                                        unset($append);
                                    }
                                    else
                                    {
                                        $filenotvalidated = array();
                                        $filenotvalidated[$field."_file_".$i] .= sprintf($clang->gT("Sorry, only %s extensions are allowed!"),$validation['allowed_filetypes']);
                                    }
                                }
                            }
                        }
                    }
                    else
                        $filecount = 0;

                    if (isset($validation['min_num_of_files']) && $filecount < $validation['min_num_of_files'] && LimeExpressionManager::QuestionIsRelevant($fieldmap[$field]['qid']))
                    {
                        $filenotvalidated = array();
                        $filenotvalidated[$field] = $clang->gT("The minimum number of files has not been uploaded.");
                    }
                }
            }
        }
        if (isset($filenotvalidated))
        {
            if (isset($move) && $move == "moveprev")
                $_SESSION['step'] = $thisstep;
            if (isset($move) && $move == "movenext")
                $_SESSION['step'] = $thisstep;
            return $filenotvalidated;
        }
    }
    if (!isset($filenotvalidated))
        return false;
    else
        return $filenotvalidated;
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


/**
* Marks a tokens as completed and sends a confirmation email to the participiant.
* If $quotaexit is set to true then the user exited the survey due to a quota
* restriction and the according token is only marked as 'Q'
*
* @param mixed $quotaexit
*/
function submittokens($quotaexit=false)
{
    global $thissurvey, $timeadjust, $emailcharset ;
    global $dbprefix, $surveyid, $connect;
    global $sitename, $thistpl, $clang, $clienttoken;

    // Shift the date due to global timeadjust setting
    $today = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i", $timeadjust);

    // check how many uses the token has left
    $usesquery = "SELECT usesleft FROM {$dbprefix}tokens_$surveyid WHERE token='".db_quote($clienttoken)."'";
    $usesresult = db_execute_assoc($usesquery);
    $usesrow = $usesresult->FetchRow();
    if (isset($usesrow)) { $usesleft = $usesrow['usesleft']; }

    $utquery = "UPDATE {$dbprefix}tokens_$surveyid\n";
    if ($quotaexit==true)
    {
        $utquery .= "SET completed='Q', usesleft=usesleft-1\n";
    }
    elseif (bIsTokenCompletedDatestamped($thissurvey))
    {
        if (isset($usesleft) && $usesleft<=1)
        {
            $utquery .= "SET usesleft=usesleft-1, completed='$today'\n";
        }
        else
        {
            $utquery .= "SET usesleft=usesleft-1\n";
        }
    }
    else
    {
        if (isset($usesleft) && $usesleft<=1)
        {
            $utquery .= "SET usesleft=usesleft-1, completed='Y'\n";
        }
        else
        {
            $utquery .= "SET usesleft=usesleft-1\n";
        }
    }
    $utquery .= "WHERE token='".db_quote($clienttoken)."'";

    $utresult = $connect->Execute($utquery) or safe_die ("Couldn't update tokens table!<br />\n$utquery<br />\n".$connect->ErrorMsg());     //Checked

    if ($quotaexit==false)
    {
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
            $fieldsarray["{TOKEN}"]=$clienttoken;
            $attrfieldnames=GetAttributeFieldnames($surveyid);
            foreach ($attrfieldnames as $attr_name)
            {
                $fieldsarray["{".strtoupper($attr_name)."}"]=$cnfrow[$attr_name];
            }

            $dateformatdatat=getDateFormatData($thissurvey['surveyls_dateformat']);
            $numberformatdatat = getRadixPointData($thissurvey['surveyls_numberformat']);
            $fieldsarray["{EXPIRY}"]=convertDateTimeFormat($thissurvey["expiry"],'Y-m-d H:i:s',$dateformatdatat['phpdate']);

            $subject=ReplaceFields($subject, $fieldsarray, true);

            $subject=html_entity_decode($subject,ENT_QUOTES,$emailcharset);

            if (getEmailFormat($surveyid) == 'html')
            {
                $ishtml=true;
            }
            else
            {
                $ishtml=false;
            }

            if (trim(strip_tags($thissurvey['email_confirm'])) != "")
            {
                $message=$thissurvey['email_confirm'];
                $message=ReplaceFields($message, $fieldsarray, true);

                if (!$ishtml)
                {
                    $message=strip_tags(br2nl(html_entity_decode($message,ENT_QUOTES,$emailcharset)));
                }
                else
                {
                    $message=html_entity_decode($message,ENT_QUOTES, $emailcharset );
                }

                //Only send confirmation email if there is a valid email address
                if (validate_email($cnfrow['email']))
                {
                    SendEmailMessage(null,$message, $subject, $to, $from, $sitename,$ishtml);
                }
            }
            else
            {
                //There is nothing in the message, so don't send a confirmation email
                //This section only here as placeholder to indicate new feature :-)
            }
        }
    }
}

/**
* Send a submit notification to the email address specified in the notifications tab in the survey settings
*/
function SendSubmitNotifications()
{
    global $thissurvey, $debug;
    global $dbprefix, $clang, $emailcharset;
    global $sitename, $homeurl, $surveyid, $publicurl, $maildebug, $tokensexist;

    $bIsHTML = ($thissurvey['htmlemail'] == 'Y');

    $aReplacementVars=array();


    if ($thissurvey['allowsave'] == "Y" && isset($_SESSION['scid']))
    {
        $aReplacementVars['RELOADURL']="{$publicurl}/index.php?sid={$surveyid}&loadall=reload&scid=".$_SESSION['scid']."&loadname=".urlencode($_SESSION['holdname'])."&loadpass=".urlencode($_SESSION['holdpass']);
        if ($bIsHTML)
        {
            $aReplacementVars['RELOADURL']="<a href='{$aReplacementVars['RELOADURL']}'>{$aReplacementVars['RELOADURL']}</a>";
        }
    }
    else
    {
        $aReplacementVars['RELOADURL']='';
    }

    $aReplacementVars['ADMINNAME'] = $thissurvey['adminname'];
    $aReplacementVars['ADMINEMAIL'] = $thissurvey['adminemail'];
    $aReplacementVars['VIEWRESPONSEURL']="{$homeurl}/admin.php?action=browse&sid={$surveyid}&subaction=id&id={$_SESSION['srid']}";
    $aReplacementVars['EDITRESPONSEURL']="{$homeurl}/admin.php?action=dataentry&sid={$surveyid}&subaction=edit&surveytable=survey_{$surveyid}&id=".$_SESSION['srid'];
    $aReplacementVars['STATISTICSURL']="{$homeurl}/admin.php?action=statistics&sid={$surveyid}";
    if ($bIsHTML)
    {
        $aReplacementVars['VIEWRESPONSEURL']="<a href='{$aReplacementVars['VIEWRESPONSEURL']}'>{$aReplacementVars['VIEWRESPONSEURL']}</a>";
        $aReplacementVars['EDITRESPONSEURL']="<a href='{$aReplacementVars['EDITRESPONSEURL']}'>{$aReplacementVars['EDITRESPONSEURL']}</a>";
        $aReplacementVars['STATISTICSURL']="<a href='{$aReplacementVars['STATISTICSURL']}'>{$aReplacementVars['STATISTICSURL']}</a>";
    }
    $aReplacementVars['ANSWERTABLE']='';
    $aEmailResponseTo=array();
    $aEmailNotificationTo=array();
    $sResponseData="";

    if (!empty($thissurvey['emailnotificationto']))
    {
        $aRecipient=explode(";", $thissurvey['emailnotificationto']);
        {
            foreach($aRecipient as $sRecipient)
            {
                $sRecipient=ReplaceFields($sRecipient, array('ADMINEMAIL' =>$thissurvey['adminemail'] ), true); // Only need INSERTANS, ADMINMAIL and TOKEN
                if(validate_email($sRecipient))
                {
                    $aEmailNotificationTo[]=$sRecipient;
                }
            }
        }
    }

    if (!empty($thissurvey['emailresponseto']))
    {
        if (isset($_SESSION['token']) && $_SESSION['token'] != '' && db_tables_exist($dbprefix.'tokens_'.$surveyid))
        {
            //Gather token data for tokenised surveys
            $_SESSION['thistoken']=getTokenData($surveyid, $_SESSION['token']);
        }
        // there was no token used so lets remove the token field from insertarray
        elseif ($_SESSION['insertarray'][0]=='token')
        {
            unset($_SESSION['insertarray'][0]);
        }
        //Make an array of email addresses to send to
        $aRecipient=explode(";", $thissurvey['emailresponseto']);
        {
            foreach($aRecipient as $sRecipient)
            {
                $sRecipient=ReplaceFields($sRecipient, array('ADMINEMAIL' =>$thissurvey['adminemail'] ), true); // Only need INSERTANS, ADMINMAIL and TOKEN
                if(validate_email($sRecipient))
                {
                    $aEmailResponseTo[]=$sRecipient;
                }
            }
        }

        $aFullResponseTable=aGetFullResponseTable($surveyid,$_SESSION['srid'],$_SESSION['s_lang']);
        $ResultTableHTML = "<table class='printouttable' >\n";
        $ResultTableText ="\n\n";
        $oldgid = 0;
        $oldqid = 0;
        foreach ($aFullResponseTable as $sFieldname=>$fname)
        {
            if (substr($sFieldname,0,4)=='gid_')
            {

                $ResultTableHTML .= "\t<tr class='printanswersgroup'><td colspan='2'>{$fname[0]}</td></tr>\n";
                $ResultTableText .="\n{$fname[0]}\n\n";
            }
            elseif (substr($sFieldname,0,4)=='qid_')
            {
                $ResultTableHTML .= "\t<tr class='printanswersquestionhead'><td  colspan='2'>{$fname[0]}</td></tr>\n";
                $ResultTableText .="\n{$fname[0]}\n";
            }
            else
            {
                $ResultTableHTML .= "\t<tr class='printanswersquestion'><td>{$fname[0]} {$fname[1]}</td><td class='printanswersanswertext'>{$fname[2]}</td></tr>";
                $ResultTableText .="     {$fname[0]} {$fname[1]}: {$fname[2]}\n";
            }
        }

        $ResultTableHTML .= "</table>\n";
        $ResultTableText .= "\n\n";
        if ($bIsHTML)
        {
            $aReplacementVars['ANSWERTABLE']=$ResultTableHTML;
        }
        else
        {
            $aReplacementVars['ANSWERTABLE']=$ResultTableText;
        }
    }

    $sFrom = $thissurvey['adminname'].' <'.$thissurvey['adminemail'].'>';
    if (count($aEmailNotificationTo)>0)
    {
        $sMessage=templatereplace($thissurvey['email_admin_notification'],$aReplacementVars,($thissurvey['anonymized'] == "Y"));
        $sSubject=templatereplace($thissurvey['email_admin_notification_subj'],$aReplacementVars,($thissurvey['anonymized'] == "Y"));
        $oMail = new PHPMailer;
        foreach ($aEmailNotificationTo as $sRecipient)
        {
            if (!SendEmailMessage($oMail, $sMessage, $sSubject, $sRecipient, $sFrom, $sitename, true, getBounceEmail($surveyid)))
            {
                if ($debug>0)
                {
                    echo '<br />Email could not be sent. Reason: '.$maildebug.'<br/>';
                }
            }
        }
        $oMail->SmtpClose();
    }

    if (count($aEmailResponseTo)>0)
    {
        $sMessage=templatereplace($thissurvey['email_admin_responses'],$aReplacementVars);
        $sSubject=templatereplace($thissurvey['email_admin_responses_subj'],$aReplacementVars);
        $mail = new PHPMailer;
        foreach ($aEmailResponseTo as $sRecipient)
        {
            if (!SendEmailMessage($mail,$sMessage, $sSubject, $sRecipient, $sFrom, $sitename, true, getBounceEmail($surveyid)))
            {
                if ($debug>0)
                {
                    echo '<br />Email could not be sent. Reason: '.$maildebug.'<br/>';
                }
            }
        }
        $mail->SmtpClose();
    }


}

function submitfailed($errormsg='')
{
    global $debug;
    global $thissurvey, $clang;
    global $thistpl, $subquery, $surveyid, $connect;

    $completed = "<br /><strong><font size='2' color='red'>"
    . $clang->gT("Did Not Save")."</strong></font><br /><br />\n\n"
    . $clang->gT("An unexpected error has occurred and your responses cannot be saved.")."<br /><br />\n";
    if ($thissurvey['adminemail'])
    {
        $completed .= $clang->gT("Your responses have not been lost and have been emailed to the survey administrator and will be entered into our database at a later point.")."<br /><br />\n";
        if ($debug>0)
        {
            $completed.='Error message: '.htmlspecialchars($errormsg).'<br />';
        }
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
        SendEmailMessage(null,$email, $clang->gT("Error saving results","unescaped"), $thissurvey['adminemail'], $thissurvey['adminemail'], "LimeSurvey", false, getBounceEmail($surveyid));
        //echo "<!-- EMAIL CONTENTS:\n$email -->\n";
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

/**
* This function builds all the required session variables when a survey is first started and
* it loads any answer defaults from command line or from the table defaultvalues
* It is called from the related format script (group.php, question.php, survey.php)
* if the survey has just started.
*
* @returns  $totalquestions Total number of questions in the survey
*
*/
function buildsurveysession($previewGroup=false)
{
    global $thissurvey, $secerror, $clienttoken, $databasetype;
    global $tokensexist, $thistpl;
    global $surveyid, $dbprefix, $connect;
    global $register_errormsg, $clang;
    global $totalBoilerplatequestions;
    global $templang, $move, $rooturl, $publicurl;

    if (!isset($templang) || $templang=='')
    {
        $templang=$thissurvey['language'];
    }

    $totalBoilerplatequestions = 0;
    $loadsecurity = returnglobal('loadsecurity');
    // NO TOKEN REQUIRED BUT CAPTCHA ENABLED FOR SURVEY ACCESS
    if ($tokensexist == 0 &&
    captcha_enabled('surveyaccessscreen',$thissurvey['usecaptcha']))
    {

        // IF CAPTCHA ANSWER IS NOT CORRECT OR NOT SET
        if (!isset($loadsecurity) ||
        !isset($_SESSION['secanswer']) ||
        $loadsecurity != $_SESSION['secanswer'])
        {
            sendcacheheaders();
            doHeader();
            // No or bad answer to required security question

            echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
            //echo makedropdownlist();
            echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));

            if (isset($loadsecurity))
            { // was a bad answer
                echo "<font color='#FF0000'>".$clang->gT("The answer to the security question is incorrect.")."</font><br />";
            }

            echo "<p class='captcha'>".$clang->gT("Please confirm access to survey by answering the security question below and click continue.")."</p>
            <form class='captcha' method='get' action='{$publicurl}/index.php'>
            <table align='center'>
            <tr>
            <td align='right' valign='middle'>
            <input type='hidden' name='sid' value='".$surveyid."' id='sid' />
            <input type='hidden' name='lang' value='".$templang."' id='lang' />";
            // In case we this is a direct Reload previous answers URL, then add hidden fields
            if (isset($_GET['loadall']) && isset($_GET['scid'])
            && isset($_GET['loadname']) && isset($_GET['loadpass']))
            {
                echo "
                <input type='hidden' name='loadall' value='".htmlspecialchars($_GET['loadall'])."' id='loadall' />
                <input type='hidden' name='scid' value='".returnglobal('scid')."' id='scid' />
                <input type='hidden' name='loadname' value='".htmlspecialchars($_GET['loadname'])."' id='loadname' />
                <input type='hidden' name='loadpass' value='".htmlspecialchars($_GET['loadpass'])."' id='loadpass' />";
            }

            echo "
            </td>
            </tr>";
            if (function_exists("ImageCreate") && captcha_enabled('surveyaccessscreen', $thissurvey['usecaptcha']))
            {
                echo "<tr>
                <td align='center' valign='middle'><label for='captcha'>".$clang->gT("Security question:")."</label></td><td align='left' valign='middle'><table><tr><td valign='middle'><img src='$rooturl/verification.php?sid=$surveyid' alt='captcha' /></td>
                <td valign='middle'><input id='captcha' type='text' size='5' maxlength='3' name='loadsecurity' value='' /></td></tr></table>
                </td>
                </tr>";
            }
            echo "<tr><td colspan='2' align='center'><input class='submit' type='submit' value='".$clang->gT("Continue")."' /></td></tr>
            </table>
            </form>";

            echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
            doFooter();
            exit;
        }
    }

    //BEFORE BUILDING A NEW SESSION FOR THIS SURVEY, LET'S CHECK TO MAKE SURE THE SURVEY SHOULD PROCEED!

    // TOKEN REQUIRED BUT NO TOKEN PROVIDED
    if ($tokensexist == 1 && !returnglobal('token') && !$previewGroup)
    {
        if ($thissurvey['nokeyboard']=='Y')
        {
            vIncludeKeypad();
            $kpclass = "text-keypad";
        }
        else
        {
            $kpclass = "";
        }

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
            if (isset($secerror)) echo "<span class='error'>".$secerror."</span><br />";
            echo '<div id="wrapper"><p id="tokenmessage">'.$clang->gT("This is a controlled survey. You need a valid token to participate.")."<br />";
            echo $clang->gT("If you have been issued a token, please enter it in the box below and click continue.")."</p>
            <script type='text/javascript'>var focus_element='#token';</script>
            <form id='tokenform' method='get' action='{$publicurl}/index.php'>
            <ul>
            <li>
            <label for='token'>".$clang->gT("Token")."</label><input class='text $kpclass' id='token' type='text' name='token' />";

            echo "<input type='hidden' name='sid' value='".$surveyid."' id='sid' />
            <input type='hidden' name='lang' value='".$templang."' id='lang' />";
            if (isset($_GET['newtest']) && $_GET['newtest'] == "Y")
            {
                echo "  <input type='hidden' name='newtest' value='Y' id='newtest' />";

            }

            // If this is a direct Reload previous answers URL, then add hidden fields
            if (isset($_GET['loadall']) && isset($_GET['scid'])
            && isset($_GET['loadname']) && isset($_GET['loadpass']))
            {
                echo "
                <input type='hidden' name='loadall' value='".htmlspecialchars($_GET['loadall'])."' id='loadall' />
                <input type='hidden' name='scid' value='".returnglobal('scid')."' id='scid' />
                <input type='hidden' name='loadname' value='".htmlspecialchars($_GET['loadname'])."' id='loadname' />
                <input type='hidden' name='loadpass' value='".htmlspecialchars($_GET['loadpass'])."' id='loadpass' />";
            }
            echo "</li>";

            if (function_exists("ImageCreate") && captcha_enabled('surveyaccessscreen', $thissurvey['usecaptcha']))
            {
                echo "<li>
                <label for='captchaimage'>".$clang->gT("Security Question")."</label><img id='captchaimage' src='$rooturl/verification.php?sid=$surveyid' alt='captcha' /><input type='text' size='5' maxlength='3' name='loadsecurity' value='' />
                </li>";
            }
            echo "<li>
            <input class='submit' type='submit' value='".$clang->gT("Continue")."' />
            </li>
            </ul>
            </form></div>";
        }

        echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
        doFooter();
        exit;
    }


    // TOKENS REQUIRED, A TOKEN PROVIDED
    // SURVEY WITH NO NEED TO USE CAPTCHA
    elseif ($tokensexist == 1 && returnglobal('token') &&
    !captcha_enabled('surveyaccessscreen',$thissurvey['usecaptcha']))
    {
        //check if tokens actually haven't been already used
        $areTokensUsed = usedTokens(db_quote(trim(strip_tags(returnglobal('token')))));
        //check if token actually does exist
        // check also if it is allowed to change survey after completion
        if ($thissurvey['alloweditaftercompletion'] == 'Y' ) {
            $tkquery = "SELECT COUNT(*) FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote(trim(strip_tags(returnglobal('token'))))."' ";
        } else {
            $tkquery = "SELECT COUNT(*) FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote(trim(strip_tags(returnglobal('token'))))."' AND (completed = 'N' or completed='')";
        }

        $tkresult = db_execute_num($tkquery);    //Checked
        list($tkexist) = $tkresult->FetchRow();
        if (!$tkexist || ($areTokensUsed && $thissurvey['alloweditaftercompletion'] != 'Y'))
        {
            //TOKEN DOESN'T EXIST OR HAS ALREADY BEEN USED. EXPLAIN PROBLEM AND EXIT

            killSession();
            sendcacheheaders();
            doHeader();

            echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
            echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
            echo '<div id="wrapper"><p id="tokenmessage">'.$clang->gT("This is a controlled survey. You need a valid token to participate.")."<br /><br />\n"
            ."\t".$clang->gT("The token you have provided is either not valid, or has already been used.")."<br />\n"
            ."\t".sprintf($clang->gT("For further information please contact %s"), $thissurvey['adminname'])
            ." (<a href='mailto:{$thissurvey['adminemail']}'>"
            ."{$thissurvey['adminemail']}</a>)</p></div>\n";

            echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
            doFooter();
            exit;
        }

	//This should only happen once, so a good place to add the "start of the limesurrvey instrument code"
	//queXS Addition
	include_once("quexs.php");
	if(HEADER_EXPANDER_QUESTIONNAIRE && HEADER_EXPANDER_MANUAL)
	{
		global $js_header_includes;
		$js_header_includes[] = '/../../js/headerexpandquestionnaire.js'; //queXS addition
	}

    }
    // TOKENS REQUIRED, A TOKEN PROVIDED
    // SURVEY CAPTCHA REQUIRED
    elseif ($tokensexist == 1 && returnglobal('token') && captcha_enabled('surveyaccessscreen',$thissurvey['usecaptcha']))
    {

        // IF CAPTCHA ANSWER IS CORRECT
        if (isset($loadsecurity) &&
        isset($_SESSION['secanswer']) &&
        $loadsecurity == $_SESSION['secanswer'])
        {
            //check if tokens actually haven't been already used
            $areTokensUsed = usedTokens(db_quote(trim(strip_tags(returnglobal('token')))));
            //check if token actually does exist
            if ($thissurvey['alloweditaftercompletion'] == 'Y' )
            {
                $tkquery = "SELECT COUNT(*) FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote(trim(sanitize_xss_string(strip_tags(returnglobal('token')))))."'";
            }
            else
            {
                $tkquery = "SELECT COUNT(*) FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote(trim(sanitize_xss_string(strip_tags(returnglobal('token')))))."' AND (completed = 'N' or completed='')";
            }
            $tkresult = db_execute_num($tkquery);     //Checked
            list($tkexist) = $tkresult->FetchRow();
            if (!$tkexist || ($areTokensUsed && $thissurvey['alloweditaftercompletion'] != 'Y') )
            {
                sendcacheheaders();
                doHeader();
                //TOKEN DOESN'T EXIST OR HAS ALREADY BEEN USED. EXPLAIN PROBLEM AND EXIT

                echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
                echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
                echo "\t<div id='wrapper'>\n"
                ."\t<p id='tokenmessage'>\n"
                ."\t".$clang->gT("This is a controlled survey. You need a valid token to participate.")."<br /><br />\n"
                ."\t".$clang->gT("The token you have provided is either not valid, or has already been used.")."<br/>\n"
                ."\t".sprintf($clang->gT("For further information please contact %s"), $thissurvey['adminname'])
                ." (<a href='mailto:{$thissurvey['adminemail']}'>"
                ."{$thissurvey['adminemail']}</a>)\n"
                ."\t</p>\n"
                ."\t</div>\n";

                echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
                doFooter();
                exit;
            }
        }
        // IF CAPTCHA ANSWER IS NOT CORRECT
        else if (!isset($move) || is_null($move))
            {
                $gettoken = $clienttoken;
                sendcacheheaders();
                doHeader();
                // No or bad answer to required security question
                echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
                echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));
                // If token wasn't provided and public registration
                // is enabled then show registration form
                if ( !isset($gettoken) && isset($thissurvey) && $thissurvey['allowregister'] == "Y")
                {
                    echo templatereplace(file_get_contents("$thistpl/register.pstpl"));
                }
                else
                { // only show CAPTCHA

                    echo '<div id="wrapper"><p id="tokenmessage">';
                    if (isset($loadsecurity))
                    { // was a bad answer
                        echo "<span class='error'>".$clang->gT("The answer to the security question is incorrect.")."</span><br />";
                    }

                    echo $clang->gT("This is a controlled survey. You need a valid token to participate.")."<br /><br />";
                    // IF TOKEN HAS BEEN GIVEN THEN AUTOFILL IT
                    // AND HIDE ENTRY FIELD
                    if (!isset($gettoken))
                    {
                        echo $clang->gT("If you have been issued a token, please enter it in the box below and click continue.")."</p>
                        <form id='tokenform' method='get' action='{$publicurl}/index.php'>
                        <ul>
                        <li>
                        <input type='hidden' name='sid' value='".$surveyid."' id='sid' />
                        <input type='hidden' name='lang' value='".$templang."' id='lang' />";
                        if (isset($_GET['loadall']) && isset($_GET['scid'])
                        && isset($_GET['loadname']) && isset($_GET['loadpass']))
                        {
                            echo "<input type='hidden' name='loadall' value='".htmlspecialchars($_GET['loadall'])."' id='loadall' />
                            <input type='hidden' name='scid' value='".returnglobal('scid')."' id='scid' />
                            <input type='hidden' name='loadname' value='".htmlspecialchars($_GET['loadname'])."' id='loadname' />
                            <input type='hidden' name='loadpass' value='".htmlspecialchars($_GET['loadpass'])."' id='loadpass' />";
                        }

                        echo '<label for="token">'.$clang->gT("Token")."</label><input class='text' type='text' id='token' name='token'></li>";
                }
                else
                {
                    echo $clang->gT("Please confirm the token by answering the security question below and click continue.")."</p>
                    <form id='tokenform' method='get' action='{$publicurl}/index.php'>
                    <ul>
                    <li>
                    <input type='hidden' name='sid' value='".$surveyid."' id='sid' />
                    <input type='hidden' name='lang' value='".$templang."' id='lang' />";
                    if (isset($_GET['loadall']) && isset($_GET['scid'])
                    && isset($_GET['loadname']) && isset($_GET['loadpass']))
                    {
                        echo "<input type='hidden' name='loadall' value='".htmlspecialchars($_GET['loadall'])."' id='loadall' />
                        <input type='hidden' name='scid' value='".returnglobal('scid')."' id='scid' />
                        <input type='hidden' name='loadname' value='".htmlspecialchars($_GET['loadname'])."' id='loadname' />
                        <input type='hidden' name='loadpass' value='".htmlspecialchars($_GET['loadpass'])."' id='loadpass' />";
                    }
                    echo '<label for="token">'.$clang->gT("Token:")."</label><span id='token'>$gettoken</span>"
                    ."<input type='hidden' name='token' value='$gettoken'></li>";
                }


                if (function_exists("ImageCreate") && captcha_enabled('surveyaccessscreen', $thissurvey['usecaptcha']))
                {
                    echo "<li>
                    <label for='captchaimage'>".$clang->gT("Security Question")."</label><img id='captchaimage' src='$rooturl/verification.php?sid=$surveyid' alt='captcha' /><input type='text' size='5' maxlength='3' name='loadsecurity' value='' />
                    </li>";
                }
                echo "<li><input class='submit' type='submit' value='".$clang->gT("Continue")."' /></li>
                </ul>
                </form>
                </id>";
            }

            echo '</div>'.templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
            doFooter();
            unset($_SESSION['srid']);

            exit;
        }
    }

    //RESET ALL THE SESSION VARIABLES AND START AGAIN
    unset($_SESSION['grouplist']);
    unset($_SESSION['fieldarray']);
    unset($_SESSION['insertarray']);
    unset($_SESSION['thistoken']);
    unset($_SESSION['fieldnamesInfo']);
    $_SESSION['fieldnamesInfo'] = Array();


    //RL: multilingual support

    if (isset($_GET['token']) && db_tables_exist($dbprefix.'tokens_'.$surveyid))
    {
        //get language from token (if one exists)
        $tkquery2 = "SELECT * FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote($clienttoken)."' AND (completed = 'N' or completed='')";
        //echo $tkquery2;
        $result = db_execute_assoc($tkquery2) or safe_die ("Couldn't get tokens<br />$tkquery<br />".$connect->ErrorMsg());    //Checked
        while ($rw = $result->FetchRow())
        {
            $tklanguage=$rw['language'];
        }
    }
    if (returnglobal('lang'))
    {
        $language_to_set=returnglobal('lang');
    } elseif (isset($tklanguage))
    {
        $language_to_set=$tklanguage;
    }
    else
    {
        $language_to_set = $thissurvey['language'];
    }

    if (!isset($_SESSION['s_lang']))
    {
        SetSurveyLanguage($surveyid, $language_to_set);
    }


    UpdateSessionGroupList($_SESSION['s_lang']);



    // Optimized Query
    // Change query to use sub-select to see if conditions exist.
    $query = "SELECT ".db_table_name('questions').".*, ".db_table_name('groups').".*\n"
    //    ." (SELECT count(1) FROM ".db_table_name('conditions')."\n"
    //    ." WHERE ".db_table_name('questions').".qid = ".db_table_name('conditions').".qid) AS hasconditions,\n"
    //    ." (SELECT count(1) FROM ".db_table_name('conditions')."\n"
    //    ." WHERE ".db_table_name('questions').".qid = ".db_table_name('conditions').".cqid) AS usedinconditions\n"
    ." FROM ".db_table_name('groups')." INNER JOIN ".db_table_name('questions')." ON ".db_table_name('groups').".gid = ".db_table_name('questions').".gid\n"
    ." WHERE ".db_table_name('questions').".sid=".$surveyid."\n"
    ." AND ".db_table_name('groups').".language='".$_SESSION['s_lang']."'\n"
    ." AND ".db_table_name('questions').".language='".$_SESSION['s_lang']."'\n"
    ." AND ".db_table_name('questions').".parent_qid=0\n"
    ." ORDER BY ".db_table_name('groups').".group_order,".db_table_name('questions').".question_order";

    //var_dump($_SESSION);
    $result = db_execute_assoc($query);    //Checked

    //    $arows = $result->GetRows();  // Not used?

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
            if (isset($_SESSION['grouplist']))
            {
                $_SESSION['totalsteps']=count($_SESSION['grouplist']);
            }
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
        echo "\t<div id='wrapper'>\n"
        ."\t<p id='tokenmessage'>\n"
        ."\t".$clang->gT("This survey does not yet have any questions and cannot be tested or completed.")."<br /><br />\n"
        ."\t".sprintf($clang->gT("For further information please contact %s"), $thissurvey['adminname'])
        ." (<a href='mailto:{$thissurvey['adminemail']}'>"
        ."{$thissurvey['adminemail']}</a>)<br /><br />\n"
        ."\t</p>\n"
        ."\t</div>\n";

        echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
        doFooter();
        exit;
    }

    //Perform a case insensitive natural sort on group name then question title of a multidimensional array
    //	usort($arows, 'GroupOrderThenQuestionOrder');

    //3. SESSION VARIABLE - insertarray
    //An array containing information about used to insert the data into the db at the submit stage
    //4. SESSION VARIABLE - fieldarray
    //See rem at end..
    $_SESSION['token'] = $clienttoken;

    if ($thissurvey['anonymized'] == "N")
    {
        $_SESSION['insertarray'][]= "token";
    }

    if ($tokensexist == 1 && $thissurvey['anonymized'] == "N"  && db_tables_exist($dbprefix.'tokens_'.$surveyid))
    {
        //Gather survey data for "non anonymous" surveys, for use in presenting questions
        $_SESSION['thistoken']=getTokenData($surveyid, $clienttoken);
    }
    $qtypes=getqtypelist('','array');
    $fieldmap=createFieldMap($surveyid,'full',false,false,$_SESSION['s_lang']);

    // Randomization Groups

    // Find all defined randomization groups through question attribute values
    $randomGroups=array();
    if ($databasetype=='odbc_mssql' || $databasetype=='odbtp' || $databasetype=='mssql_n' || $databasetype=='mssqlnative')
    {
        $rgquery = "SELECT attr.qid, CAST(value as varchar(255)) FROM ".db_table_name('question_attributes')." as attr right join ".db_table_name('questions')." as quests on attr.qid=quests.qid WHERE attribute='random_group' and CAST(value as varchar(255)) <> '' and sid=$surveyid GROUP BY attr.qid, CAST(value as varchar(255))";
    }
    else
    {
        $rgquery = "SELECT attr.qid, value FROM ".db_table_name('question_attributes')." as attr right join ".db_table_name('questions')." as quests on attr.qid=quests.qid WHERE attribute='random_group' and value <> '' and sid=$surveyid GROUP BY attr.qid, value";
    }
    $rgresult = db_execute_assoc($rgquery);
    while($rgrow = $rgresult->FetchRow())
    {
        // Get the question IDs for each randomization group
        $randomGroups[$rgrow['value']][] = $rgrow['qid'];
    }

    // If we have randomization groups set, then lets cycle through each group and
    // replace questions in the group with a randomly chosen one from the same group
    if (count($randomGroups) > 0)
    {
        $copyFieldMap = array();
        $oldQuestOrder = array();
        $newQuestOrder = array();
        $randGroupNames = array();
        foreach ($randomGroups as $key=>$value)
        {
            $oldQuestOrder[$key] = $randomGroups[$key];
            $newQuestOrder[$key] = $oldQuestOrder[$key];
            // We shuffle the question list to get a random key->qid which will be used to swap from the old key
            shuffle($newQuestOrder[$key]);
            $randGroupNames[] = $key;
        }

        // Loop through the fieldmap and swap each question as they come up
        while (list($fieldkey,$fieldval) = each($fieldmap))
        {
            $found = 0;
            foreach ($randomGroups as $gkey=>$gval)
            {
                // We found a qid that is in the randomization group
                if (isset($fieldval['qid']) && in_array($fieldval['qid'],$oldQuestOrder[$gkey]))
                {
                    // Get the swapped question
                    $oldQuestFlip = array_flip($oldQuestOrder[$gkey]);
                    $qfieldmap = createFieldMap($surveyid,'full',true,$newQuestOrder[$gkey][$oldQuestFlip[$fieldval['qid']]],$_SESSION['s_lang']);
                    unset($qfieldmap['id']);
                    unset($qfieldmap['submitdate']);
                    unset($qfieldmap['lastpage']);
                    unset($qfieldmap['lastpage']);
                    unset($qfieldmap['token']);
                    unset($qfieldmap['startlanguage']);
                    foreach ($qfieldmap as $tkey=>$tval)
                    {
                        // Assign the swapped question (Might be more than one field)
                        $tval['random_gid'] = $fieldval['gid'];
                        //$tval['gid'] = $fieldval['gid'];
                        $copyFieldMap[$tkey]=$tval;
                    }
                    $found = 1;
                    break;
                } else
                {
                    $found = 2;
                }
            }
            if ($found == 2)
            {
                $copyFieldMap[$fieldkey]=$fieldval;
            }
            reset($randomGroups);
        }
        // reset the sequencing counts
        $gseq=-1;
        $_gid=-1;
        $qseq=-1;
        $_qid=-1;
        $copyFieldMap2 = array();
        foreach ($copyFieldMap as $key=>$val)
        {
            if (isset($val['random_gid']))
            {
                if ($val['gid'] != '' && $val['random_gid'] != '' && $val['random_gid'] != $_gid)
                {
                    $_gid = $val['random_gid'];
                    ++$gseq;
                }
            }
            else
            {
                if ($val['gid'] != '' && $val['gid'] != $_gid)
                {
                    $_gid = $val['gid'];
                    ++$gseq;
                }
            }

            if ($val['qid'] != '' && $val['qid'] != $_qid)
            {
                $_qid = $val['qid'];
                ++$qseq;
            }
            if ($val['gid'] != '' && $val['qid'] != '')
            {
                $val['groupSeq'] = $gseq;
                $val['questionSeq'] = $qseq;
            }
            $copyFieldMap2[$key] = $val;
        }
        unset($copyFieldMap);
        $fieldmap=$copyFieldMap2;

        $_SESSION['fieldmap-' . $surveyid . $_SESSION['s_lang']] = $fieldmap;
        $_SESSION['fieldmap-' . $surveyid . '-randMaster'] = 'fieldmap-' . $surveyid . $_SESSION['s_lang'];
    }
    //die(print_r($fieldmap));

    foreach ($fieldmap as $field)
    {
        if (isset($field['qid']) && $field['qid']!='')
        {
            $_SESSION['fieldnamesInfo'][$field['fieldname']]=$field['sid'].'X'.$field['gid'].'X'.$field['qid'];
            $_SESSION['insertarray'][]=$field['fieldname'];
            //fieldarray ARRAY CONTENTS -
            //            [0]=questions.qid,
            //			[1]=fieldname,
            //			[2]=questions.title,
            //			[3]=questions.question
            //                 	[4]=questions.type,
            //			[5]=questions.gid,
            //			[6]=questions.mandatory,
            //			[7]=conditionsexist,
            //			[8]=usedinconditions
            //			[8]=usedinconditions
            //			[9]=used in group.php for question count
            //			[10]=new group id for question in randomization group (GroupbyGroup Mode)
            if (!isset($_SESSION['fieldarray'][$field['sid'].'X'.$field['gid'].'X'.$field['qid']]))
            {
                $_SESSION['fieldarray'][$field['sid'].'X'.$field['gid'].'X'.$field['qid']]=array($field['qid'],
                $field['sid'].'X'.$field['gid'].'X'.$field['qid'],
                $field['title'],
                $field['question'],
                $field['type'],
                $field['gid'],
                $field['mandatory'],
                $field['hasconditions'],
                $field['usedinconditions']);
            }
            if (isset($field['random_gid']))
            {
                $_SESSION['fieldarray'][$field['sid'].'X'.$field['gid'].'X'.$field['qid']][10] = $field['random_gid'];
            }
        }

    }

    // Defaults need to be set within Expression Manager so that it can process defaults comprised of equations
    // Prefill questions/answers from command line params, except for Reserved var (put in in config-default.php ?)
    $reservedStartingValues= array('token','sid','gid','qid','lang','newtest','action');
    $startingValues=array();
    if (isset($_GET) && !$previewGroup)
    {
        foreach ($_GET as $k=>$v)
        {
            if (!in_array($k,$reservedStartingValues))
            {
                $startingValues[$k] = urldecode($v);
            }
        }
    }
    $_SESSION['startingValues']=$startingValues;

    if (isset($_SESSION['fieldarray'])) $_SESSION['fieldarray']=array_values($_SESSION['fieldarray']);

    // Check if the current survey language is set - if not set it
    // this way it can be changed later (for example by a special question type)
    //Check if a passthru label and value have been included in the query url
    if(isset($_GET['passthru']) && $_GET['passthru'] != "")
    {
        if(isset($_GET[$_GET['passthru']]) && $_GET[$_GET['passthru']] != "")
        {
            $_SESSION['passthrulabel']=$_GET['passthru'];
            $_SESSION['passthruvalue']=$_GET[$_GET['passthru']];
        }

    }
    // New: If no passthru variable is explicitely set, save the whole query_string - above method is obsolete and the new way should only be used
    elseif (isset($_SERVER['QUERY_STRING']))
    {
        $_SESSION['ls_initialquerystr']=$_SERVER['QUERY_STRING'];
    }
    // END NEW

    // Fix totalquestions by substracting Test Display questions
    $sNoOfTextDisplayQuestions=(int) $connect->GetOne("SELECT count(*)\n"
    ." FROM ".db_table_name('questions')
    ." WHERE type in ('X','*')\n"
    ." AND sid={$surveyid}"
    ." AND language='".$_SESSION['s_lang']."'"
    ." AND parent_qid=0");

    $_SESSION['therearexquestions'] = $totalquestions - $sNoOfTextDisplayQuestions; // must be global for THEREAREXQUESTIONS replacement field to work

    return $totalquestions-$sNoOfTextDisplayQuestions;
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

    if ($thissurvey['navigationdelay'] > 0 && (
    isset($_SESSION['maxstep']) && $_SESSION['maxstep'] > 0 && $_SESSION['maxstep'] == $_SESSION['step']))
    {
        $disabled = "disabled=\"disabled\"";
        $surveymover .= "<script type=\"text/javascript\">\n"
        . "  navigator_countdown(" . $thissurvey['navigationdelay'] . ");\n"
        . "</script>\n";
    }
    else
    {
        $disabled = "";
    }

    if (isset($_SESSION['step']) && $_SESSION['step'] && ($_SESSION['step'] == $_SESSION['totalsteps']) && !$presentinggroupdescription && $thissurvey['format'] != "A")
    {
        $surveymover .= "<input type=\"hidden\" name=\"move\" value=\"movesubmit\" id=\"movesubmit\" />";
    }
    else
    {
        $surveymover .= "<input type=\"hidden\" name=\"move\" value=\"movenext\" id=\"movenext\" />";
    }

    if (isset($_SESSION['step']) && $thissurvey['format'] != "A" && ($thissurvey['allowprev'] != "N" || $thissurvey['allowjumps'] == "Y") &&
    ($_SESSION['step'] > 0 || (!$_SESSION['step'] && $presentinggroupdescription && $thissurvey['showwelcome'] == 'Y')))
    {
        //To prevent too much complication in the if statement above I put it here...
        if ($thissurvey['showwelcome'] == 'N' && $_SESSION['step'] == 1) {
            //first step and we do not want to go back to the welcome screen since we don't show that...
            //so skip the prev button
        } else {
            $surveymover .= "<input class='submit' accesskey='p' type='button' onclick=\"javascript:document.limesurvey.move.value = 'moveprev'; $('#limesurvey').submit();\" value=' &lt;&lt; "
            . $clang->gT("Previous")." ' name='move2' id='moveprevbtn' $disabled />\n";
        }
    }
    if (isset($_SESSION['step']) && $_SESSION['step'] && (!$_SESSION['totalsteps'] || ($_SESSION['step'] < $_SESSION['totalsteps'])))
    {
        $surveymover .=  "\t<input class='submit' type='submit' accesskey='n' onclick=\"javascript:document.limesurvey.move.value = 'movenext';\" value=' "
        . $clang->gT("Next")." &gt;&gt; ' name='move2' id='movenextbtn' $disabled />\n";
    }
    // here, in some lace, is where I must modify to turn the next button conditionable
    if (!isset($_SESSION['step']) || !$_SESSION['step'])
    {
        $surveymover .=  "\t<input class='submit' type='submit' accesskey='n' onclick=\"javascript:document.limesurvey.move.value = 'movenext';\" value=' "
        . $clang->gT("Next")." &gt;&gt; ' name='move2' id='movenextbtn' $disabled />\n";
    }
    if (isset($_SESSION['step']) && $_SESSION['step'] && ($_SESSION['step'] == $_SESSION['totalsteps']) && $presentinggroupdescription == "yes")
    {
        $surveymover .=  "\t<input class='submit' type='submit' onclick=\"javascript:document.limesurvey.move.value = 'movenext';\" value=' "
        . $clang->gT("Next")." &gt;&gt; ' name='move2' id=\"movenextbtn\" $disabled />\n";
    }
    if (($_SESSION['step'] && ($_SESSION['step'] == $_SESSION['totalsteps']) && !$presentinggroupdescription) || $thissurvey['format'] == 'A')
    {
        $surveymover .= "\t<input class=\"submit\" type=\"submit\" accesskey=\"l\" onclick=\"javascript:document.limesurvey.move.value = 'movesubmit';\" value=\""
        . $clang->gT("Submit")."\" name=\"move2\" id=\"movesubmitbtn\" $disabled />\n";
    }

    //	$surveymover .= "<input type='hidden' name='PHPSESSID' value='".session_id()."' id='PHPSESSID' />\n";
    return $surveymover;
}


/**
* Caculate assessement scores
*
* @param mixed $surveyid
* @param mixed $returndataonly - only returns an array with data
*/
function doAssessment($surveyid, $returndataonly=false)
{
    global $dbprefix, $thistpl, $connect;
    $baselang=GetBaseLanguageFromSurveyID($surveyid);
    $total=0;
    if (!isset($_SESSION['s_lang']))
    {
        $_SESSION['s_lang']=$baselang;
    }
    $query = "SELECT * FROM ".db_table_name('assessments')."
    WHERE sid=$surveyid and language='{$_SESSION['s_lang']}'
    ORDER BY scope,id";
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
                    "message"=>$row['message']);
                }
                else
                {
                    $assessment['total'][]=array( "name"=>$row['name'],
                    "min"=>$row['minimum'],
                    "max"=>$row['maximum'],
                    "message"=>$row['message']);
                }
            }
            $fieldmap=createFieldMap($surveyid, "full");
            $i=0;
            $total=0;
            $groups=array();
            foreach($fieldmap as $field)
            {
                if (in_array($field['type'],array('1','F','H','W','Z','L','!','M','O','P')))
                {
                    $fieldmap[$field['fieldname']]['assessment_value']=0;
                    if (isset($_SESSION[$field['fieldname']]))
                    {
                        if (($field['type'] == "M") || ($field['type'] == "P")) //Multiflexi choice  - result is the assessment attribute value
                        {
                            if ($_SESSION[$field['fieldname']] == "Y")
                            {
                                $aAttributes=getQuestionAttributes($field['qid'],$field['type']);
                                $fieldmap[$field['fieldname']]['assessment_value']=(int)$aAttributes['assessment_value'];
                                $total=$total+(int)$aAttributes['assessment_value'];
                            }
                        }
                        else
                        {
                            $usquery = "SELECT assessment_value FROM ".db_table_name("answers")." where qid=".$field['qid']." and language='$baselang' and code=".db_quoteall($_SESSION[$field['fieldname']]);
                            $usresult = db_execute_assoc($usquery);          //Checked
                            if ($usresult)
                            {
                                $usrow = $usresult->FetchRow();
                                $fieldmap[$field['fieldname']]['assessment_value']=$usrow['assessment_value'];
                                $total=$total+$usrow['assessment_value'];
                            }
                        }
                    }
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
                    if ($field['gid'] == $group && isset($field['assessment_value']))
                    {
                        //$grouptotal=$grouptotal+$field['answer'];
                        if (isset ($_SESSION[$field['fieldname']]))
                        {
                            $grouptotal=$grouptotal+$field['assessment_value'];
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
                        if ($val >= $assessed['min'] && $val <= $assessed['max'] && $returndataonly===false)
                        {
                            $assessments .= "\t<!-- GROUP ASSESSMENT: Score: $val Min: ".$assessed['min']." Max: ".$assessed['max']."-->
                            <table class='assessments' align='center'>
                            <tr>
                            <th>".str_replace(array("{PERC}", "{TOTAL}"), array($val, $total), $assessed['name'])."
                            </th>
                            </tr>
                            <tr>
                            <td align='center'>".str_replace(array("{PERC}", "{TOTAL}"), array($val, $total), $assessed['message'])."
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
                if ($total >= $assessed['min'] && $total <= $assessed['max'] && $returndataonly===false)
                {
                    $assessments .= "\t\t\t<!-- TOTAL ASSESSMENT: Score: $total Min: ".$assessed['min']." Max: ".$assessed['max']."-->
                    <table class='assessments' align='center'><tr><th>".str_replace(array("{PERC}", "{TOTAL}"), array($val, $total), stripslashes($assessed['name']))."
                    </th></tr>
                    <tr>
                    <td align='center'>".str_replace(array("{PERC}", "{TOTAL}"), array($val, $total), stripslashes($assessed['message']))."
                    </td>
                    </tr>
                    </table>\n";
                }
            }
        }
        if ($returndataonly==true)
        {
            return array('total'=>$total);
        }
        else
        {
            return $assessments;
        }
    }
}

function UpdateSessionGroupList($language)
//1. SESSION VARIABLE: grouplist
//A list of groups in this survey, ordered by group name.

{
    global $surveyid;
    unset ($_SESSION['grouplist']);
    $query = "SELECT * FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='".$language."' ORDER BY group_order";
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

}


/**
* check_quota() returns quota information for the current survey
* @param string $checkaction - action the function must take after completing:
* 								enforce: Enforce the Quota action
* 								return: Return the updated quota array from getQuotaAnswers()
* @param string $surveyid - Survey identification number
* @return array - nested array, Quotas->Members->Fields, includes quota status and which members matched in session.
*/
function check_quota($checkaction,$surveyid)
{
    if (!isset($_SESSION['s_lang'])){
        return;
    }
    global $thistpl, $clang, $clienttoken, $publicurl;
    $global_matched = false;
    $quota_info = getQuotaInformation($surveyid, $_SESSION['s_lang']);
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
                unset($querycond);
                // fill the array of value and query for each fieldnames
                $fields_value_array = array();
                $fields_query_array = array();
                foreach($quota['members'] as $member)
                {
                    foreach($member['fieldnames'] as $fieldname)
                    {

                        if (!in_array($fieldname,$fields_list))
                        {
                            $fields_list[] = $fieldname;
                            $fields_value_array[$fieldname] = array();
                            $fields_query_array[$fieldname] = array();
                        }
                        $fields_value_array[$fieldname][]=$member['value'];
                        $fields_query_array[$fieldname][]= "s." . db_quote_id($fieldname)." = '{$member['value']}'";
                    }

                }
                // fill the $querycond array with each fields_query grouped by fieldname
                foreach($fields_list as $fieldname)
                {
                    $select_query = " ( ".implode(' OR ',$fields_query_array[$fieldname]).' )';
                    $querycond[] = $select_query;
                }
                // Test if the fieldname is in the array of value in the session
                foreach($quota['members'] as $member)
                {
                    foreach($member['fieldnames'] as $fieldname)
                    {
                        if (isset($_SESSION[$fieldname]))
                        {
                            if (in_array($_SESSION[$fieldname],$fields_value_array[$fieldname])){
                                $quota_info[$x]['members'][$y]['insession'] = "true";
                            }
                        }
                    }
                    $y++;
                }
                unset($fields_query_array);unset($fields_value_array);

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

		//queXS Addition
		include_once('quexs.php');
		$case_id = get_case_id(get_operator_id());

                    // Check the status of the quota, is it full or not
                    $querysel = "SELECT id FROM ".db_table_name('survey_'.$surveyid)." AS s
				     JOIN `case` AS cq ON (cq.case_id = '$case_id')
				     JOIN sample AS sampt ON (sampt.sample_id = cq.sample_id)
				     JOIN `case` AS c ON (c.token = s.token AND c.questionnaire_id = cq.questionnaire_id)
				     JOIN `sample` as sam ON (c.sample_id = sam.sample_id AND sam.import_id = sampt.import_id)
			             WHERE ".implode(' AND ',$querycond)." "." 
					AND s.submitdate IS NOT NULL";


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
            // Need to add Quota action enforcement here.
            reset($quota_info);

            $tempmsg ="";
            $found = false;
            foreach($quota_info as $quota)
            {
                if ((isset($quota['status']) && $quota['status'] == "matched") && (isset($quota['Action']) && $quota['Action'] == "1"))
                {
                    // If a token is used then mark the token as completed
                    if (isset($clienttoken) && $clienttoken)
                    {
                        submittokens(true);
                    }
                    session_destroy();
                sendcacheheaders();
                if($quota['AutoloadUrl'] == 1 && $quota['Url'] != "")
                {
                    header("Location: ".$quota['Url']."?message=".$quota['Message']);
                }
                doHeader();
                echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
                echo "\t<div class='quotamessage'>\n";
                echo "\t".$quota['Message']."<br /><br />\n";
                echo "\t<a href='".$quota['Url']."'>".$quota['UrlDescrip']."</a><br />\n";
                echo "\t</div>\n";
                echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
                doFooter();
                exit;
            }

            if ((isset($quota['status']) && $quota['status'] == "matched") && (isset($quota['Action']) && $quota['Action'] == "2"))
            {

                sendcacheheaders();
                doHeader();
                echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
                echo "\t<div class='quotamessage'>\n";
                echo "\t".$quota['Message']."<br /><br />\n";
                echo "\t<a href='".$quota['Url']."'>".$quota['UrlDescrip']."</a><br />\n";
                echo "<form method='post' action='{$publicurl}/index.php' id='limesurvey' name='limesurvey'><input type=\"hidden\" name=\"move\" value=\"movenext\" id=\"movenext\" /><input class='submit' accesskey='p' type='button' onclick=\"javascript:document.limesurvey.move.value = 'moveprev'; document.limesurvey.submit();\" value=' &lt;&lt; ". $clang->gT("Previous")." ' name='move2' />
                <input type='hidden' name='thisstep' value='".($_SESSION['step'])."' id='thisstep' />
                <input type='hidden' name='sid' value='".returnglobal('sid')."' id='sid' />
                <input type='hidden' name='token' value='".$clienttoken."' id='token' />
                </form>\n";
                echo "\t</div>\n";
                echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
                doFooter();
                exit;
            }
        }


    } else
    {
        // Unknown value
        return false;
    }

}

/**
* put your comment there...
*
* @param mixed $mail
* @param mixed $text
* @param mixed $class
* @param mixed $params
*/
function encodeEmail($mail, $text="", $class="", $params=array())
{
    $encmail ="";
    for($i=0; $i<strlen($mail); $i++)
    {
        $encMod = rand(0,2);
        switch ($encMod)
        {
            case 0: // None
                $encmail .= substr($mail,$i,1);
                break;
            case 1: // Decimal
                $encmail .= "&#".ord(substr($mail,$i,1)).';';
                break;
            case 2: // Hexadecimal
                $encmail .= "&#x".dechex(ord(substr($mail,$i,1))).';';
                break;
        }
    }

    if(!$text)
    {
        $text = $encmail;
    }
    return $text;
}



/**
* GetReferringUrl() returns the reffering URL
*/
function GetReferringUrl()
{
    global $clang,$stripQueryFromRefurl;
    if (isset($_SESSION['refurl']))
    {
        return; // do not overwrite refurl
    }

    // refurl is not set in session, read it from server variable
    if(isset($_SERVER["HTTP_REFERER"]))
    {
        if(!preg_match('/'.$_SERVER["SERVER_NAME"].'/', $_SERVER["HTTP_REFERER"]))
        {
            if (!isset($stripQueryFromRefurl) || !$stripQueryFromRefurl)
            {
                $_SESSION['refurl'] = $_SERVER["HTTP_REFERER"];
            }
            else
            {
                $aRefurl = explode("?",$_SERVER["HTTP_REFERER"]);
                $_SESSION['refurl'] = $aRefurl[0];
            }
        }
        else
        {
            $_SESSION['refurl'] = '-';
        }
    }
    else
    {
        $_SESSION['refurl'] = null;
    }
}

/**
* Shows the welcome page, used in group by group and question by question mode
*/
function display_first_page() {
    global $clang, $thistpl, $token, $surveyid, $thissurvey, $navigator,$publicurl;
    sendcacheheaders();
    doHeader();

    LimeExpressionManager::StartProcessingPage();
    LimeExpressionManager::StartProcessingGroup(-1, false, $surveyid);  // start on welcome page

    echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
    echo "\n<form method='post' action='{$publicurl}/index.php' id='limesurvey' name='limesurvey' autocomplete='off'>\n";

    echo "\n\n<!-- START THE SURVEY -->\n";

    echo templatereplace(file_get_contents("$thistpl/welcome.pstpl"))."\n";
    if ($thissurvey['anonymized'] == "Y")
    {
        echo templatereplace(file_get_contents("$thistpl/privacy.pstpl"))."\n";
    }
    $navigator = surveymover();
    echo templatereplace(file_get_contents("$thistpl/navigator.pstpl"));
    if ($thissurvey['active'] != "Y")
    {
        echo "<p style='text-align:center' class='error'>".$clang->gT("This survey is currently not active. You will not be able to save your responses.")."</p>\n";
    }
    echo "\n<input type='hidden' name='sid' value='$surveyid' id='sid' />\n";
    if (isset($token) && !empty($token)) {
        echo "\n<input type='hidden' name='token' value='$token' id='token' />\n";
    }
    echo "\n<input type='hidden' name='lastgroupname' value='_WELCOME_SCREEN_' id='lastgroupname' />\n"; //This is to ensure consistency with mandatory checks, and new group test
    $loadsecurity = returnglobal('loadsecurity');
    if (isset($loadsecurity)) {
        echo "\n<input type='hidden' name='loadsecurity' value='$loadsecurity' id='loadsecurity' />\n";
    }
    $_SESSION['LEMpostKey'] = mt_rand();
    echo "<input type='hidden' name='LEMpostKey' value='{$_SESSION['LEMpostKey']}' id='LEMpostKey' />\n";
    echo "<input type='hidden' name='thisstep' id='thisstep' value='0' />\n";

    echo "\n</form>\n";
    echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));

    echo LimeExpressionManager::GetRelevanceAndTailoringJavaScript();
    LimeExpressionManager::FinishProcessingPage();
    doFooter();
}
// Closing PHP tag intentionally left out - yes, it is okay
