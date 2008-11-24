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
* $Id: common.php 5142 2008-06-22 10:16:13Z c_schmitz $
*/

//Security Checked: POST, GET, SESSION, DB, REQUEST, returnglobal

//Ensure script is not run directly, avoid path disclosure
if (!isset($dbprefix) || isset($_REQUEST['dbprefix'])) {safe_die("Cannot run this script directly");}
$versionnumber = "1.71+";
$dbversionnumber = 126;
$buildnumber = "5147";



if ($debug>0) {
        error_reporting(E_ALL); //For debug purposes - switch on in config.phh  
        }

if (ini_get("max_execution_time")<120) @set_time_limit(120); // Maximum execution time - works only if safe_mode is off
@ini_set("memory_limit",$memorylimit); // Set Memory Limit for big surveys 

// Now check for PHP & db version
// Do not localize/translate this!
$ver = explode( '.', PHP_VERSION );
$ver_num = $ver[0] . $ver[1] . $ver[2];
$dieoutput='';
$maildebug='';

if ( $ver_num < 432 )
{
    $dieoutput .= 'This script needs PHP 4.3.2 or above! Your version: '.phpversion().'<br />';
}

if (!function_exists('mb_convert_encoding'))
{
    $dieoutput .= "This script needs the PHP Multibyte String Functions library installed: See <a href='http://docs.limesurvey.org/tiki-index.php?page=Installation+FAQ'>FAQ</a> and <a href='http://de.php.net/manual/en/ref.mbstring.php'>PHP documentation</a><br />";
}
if ($dieoutput!='') die($dieoutput);




##################################################################################
## DO NOT EDIT BELOW HERE
##################################################################################
require_once ($rootdir.'/classes/adodb/adodb.inc.php');
require_once ($rootdir.'/classes/phpmailer/class.phpmailer.php');
require_once ($rootdir.'/classes/php-gettext/gettextinc.php');
require_once ($rootdir.'/classes/core/surveytranslator.php');
require_once ($rootdir.'/classes/core/sanitize.php');

$dbprefix=strtolower($dbprefix);
define("_PHPVERSION", phpversion());

if($_SERVER['SERVER_SOFTWARE'] == "Xitami") //Deal with Xitami Issue
{
	$_SERVER['PHP_SELF'] = substr($_SERVER['SERVER_URL'], 0, -1) .$_SERVER['SCRIPT_NAME'];
}


/*
* $sourcefrom variable checks the location of the current script against
* the administration directory, and if the current script is running
* in the administration directory, it is set to "admin". Otherwise it is set
* to "public". When $sourcefrom is "admin" certain administration only functions
* are loaded.
*/

$scriptlocation=realpath(".");
$slashlesspath=str_replace(array("\\", "/"), "", $scriptlocation);
$slashlesshome=str_replace(array("\\", "/"), "", $homedir);

// Uncomment the following line for debug purposes
// echo $slashlesspath." - ".$slashlesshome;

if (eregi($slashlesshome, $slashlesspath) || eregi("dump", $_SERVER['PHP_SELF'])) {
	if (!eregi($slashlesshome."install", $slashlesspath))
	{
		$sourcefrom="admin";
	}
	else
	{
		$sourcefrom="install";
	}
} else {
	$sourcefrom="public";
}

// Set path for captcha verification.php
if ($sourcefrom == "admin")
    {
        $captchapath='../';
    }
    else
    {
        $captchapath='';
    }


//BEFORE SESSIONCONTOL BECAUSE OF THE CONNECTION
//CACHE DATA
$connect=&ADONewConnection($databasetype);
$database_exists = FALSE;
switch ($databasetype)
{
	case "mysql"     :if ($databaseport!="default") {$dbport="$databaselocation:$databaseport";}
		 			 	 else {$dbport=$databaselocation;}
	break;
	case "odbc_mssql": $dbport="Driver={SQL Server};Server=$databaselocation;Database=".$databasename;
	break;
	case "postgres": if ($databaseport!="default") {$dbport="$databaselocation:$databaseport";}
		 			 	 else {$dbport=$databaselocation;}
	break;
	default: safe_die("Unknown database type");
}
// Now try connecting to the database
if (@$connect->Connect($dbport, $databaseuser, $databasepass, $databasename))
{ $database_exists = TRUE;}
else {
 // If that doesnt work try connection without database-name
	$connect->database = '';
	if ($databasetype=='odbc_mssql') {$dbport="Driver={SQL Server};Server=$databaselocation;";}
	if (!@$connect->Connect($dbport, $databaseuser, $databasepass))
    {
       safe_die("Can't connect to LimeSurvey database. Reason: ".$connect->ErrorMsg());
    }
}

// AdoDB seems to be defaulting to ADODB_FETCH_NUM and we want to be sure that the right default mode is set

$connect->SetFetchMode(ADODB_FETCH_ASSOC);

$dbexistsbutempty=($database_exists && checkifemptydb());



if ($databasetype=='mysql') {
    if ($debug>1) { @$connect->Execute("SET SESSION SQL_MODE='STRICT_ALL_TABLES,ANSI'"); } //for development - use mysql in the strictest mode  //Checked
    $infoarray=$connect->ServerInfo();
    if (version_compare ($infoarray['version'],'4.1','<'))
    {
      safe_die ("<br />Error: You need at least MySQL version 4.1 to run LimeSurvey");
    }
    @$connect->Execute("SET CHARACTER SET 'utf8'");  //Checked    
}

// Setting dateformat for mssql driver. It seems if you don't do that the in- and output format could be different
if ($databasetype=='odbc_mssql') {
   @$connect->Execute('SET DATEFORMAT ymd;');     //Checked    
}


// Check if the DB is up to date
If ($dbexistsbutempty && $sourcefrom=='admin') {
     die ("<br />The LimeSurvey database does exist but it seems to be empty. Please run the <a href='$homeurl/install/index.php'>install script</a> to create the necessary tables.");
}



// Check if the DB is up to date
If (!$dbexistsbutempty && $sourcefrom=='admin')
{
    $usquery = "SELECT stg_value FROM ".db_table_name("settings_global")." where stg_name='DBVersion'"; 
    $usresult = db_execute_assoc($usquery,'',true); //checked
    if (!$usresult)
    {
     die ("<br />The configured LimeSurvey database does not seem to exist and the LimeSurvey tables weren't found. <br />Please check the <a href='http://docs.limesurvey.org'>online manual</a> for installation instructions.<br />If you already edited config.php please run the <a href='$homeurl/install/index.php'>installation script</a>.");
	}
    $usrow = $usresult->FetchRow();
    if (intval($usrow['stg_value'])<$dbversionnumber)
    {
     die ("<br />The LimeSurvey database is not up to date. <br />Please run the <a href='$homeurl/install/index.php'>installation script</a> to upgrade your database.");
    }

    if (is_dir($homedir."/install") && $debug<2)
    {
     die ("<br />Everything is fine - you just forgot to delete or rename your LimeSurvey installation directory (/admin/install). <br />Please do so since it may be a security risk.");
    }

}


//Admin menus and standards
//IF THIS IS AN ADMIN SCRIPT, RUN THE SESSIONCONTROL SCRIPT
if ($sourcefrom == "admin")
{
	include(dirname(__FILE__)."/admin/sessioncontrol.php");
	/**
    * @param string $htmlheader
    * This is the html header text for all administration pages
    *
    */
	$htmlheader = getAdminHeader();
}

//SET LANGUAGE DIRECTORY
if ($sourcefrom == "admin")
{
	$langdir="$publicurl/locale/".$_SESSION['adminlang']."/help";
	$langdirlocal="$rootdir/locale/".$_SESSION['adminlang']."/help";

	if (!is_dir($langdirlocal))  // is_dir only works on local dirs
	{
		$langdir="$publicurl/locale/en/help"; //default to english if there is no matching language dir
	}
}

//SET LOCAL TIME
if (substr($timeadjust,1,1)!='-' && substr($timeadjust,1,1)!='+') {$timeadjust='+'.$timeadjust.' hours';}

// SITE STYLES
$setfont = "<font size='2' face='verdana'>";

$singleborderstyle = "style='border: 1px solid #111111'";

/**
     * showadminmenu() function returns html text for the administration button bar
     * @global string $homedir
     * @global string $scriptname
     * @global string $surveyid
     * @global string $setfont
     * @global string $imagefiles
     * @return string $adminmenu
     */
    function showadminmenu()
        {
        global $homedir, $scriptname, $surveyid, $setfont, $imagefiles, $clang, $debug;
        $adminmenu  = "<table class='menubar'>\n";
        if  ($_SESSION['pw_notify'] && $debug<2)  {$adminmenu .="<tr><td align='center'><font color='red'>".$clang->gT("Warning: You are still using the default password ('password'). Please change your password and re-login again.")."</font></td></tr>";}
        $adminmenu  .="\t<tr>\n"
                    . "\t\t<td>\n"
                    . "\t\t\t<table class='menubar'>\n"
                    . "\t\t\t<tr>\n"
                    . "\t\t\t\t<td colspan='2' height='8' align='left'>\n"
                    . "\t\t\t\t<strong>".$clang->gT("Administration")."</strong>";
		if(isset($_SESSION['loginID']))
			{
			$adminmenu  .= " --  ".$clang->gT("Logged in as"). ": <strong>". $_SESSION['user'] ."</strong>"."\n";
			}
       	$adminmenu .= "\t\t\t\t</td>\n"
                    . "\t\t\t</tr>\n"
                    . "\t\t\t<tr>\n"
                    . "\t\t\t\t<td>\n"
                    . "\t\t\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname', '_self')\" title=\"".$clang->gTview("Default Administration Page")."\"" .
                     "onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Default Administration Page", "js")."');return false\">" .
                     "<img src='$imagefiles/home.png' name='HomeButton' alt='".$clang->gT("Default Administration Page")."' "
                    ."title=''" ."align='left' /></a>\n";

		$adminmenu .= "\t\t\t\t\t<img src='$imagefiles/blank.gif' alt='' width='11'  align='left' />\n"
                    . "\t\t\t\t\t<img src='$imagefiles/seperator.gif' alt=''  align='left' />\n";

		// edit users
		$adminmenu .= "\t\t\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=editusers', '_self')\" title=\"".$clang->gTview("Create/Edit Users")."\" " .
					"onmouseout=\"hideTooltip()\""
					. "onmouseover=\"showTooltip(event,'".$clang->gT("Create/Edit Users", "js")."');return false\">" .
					 "<img src='$imagefiles/security.png' name='AdminSecurity'"
					." title='' alt='".$clang->gT("Create/Edit Users")."'  align='left' /></a>";

		$adminmenu .="<a href=\"#\" onclick=\"window.open('$scriptname?action=editusergroups', '_self')\" title=\"".$clang->gTview("Create/Edit Groups")."\" "
					. "onmouseout=\"hideTooltip()\""
					. "onmouseover=\"showTooltip(event,'".$clang->gT("Create/Edit Groups", "js")."');return false\">" .
					"<img src='$imagefiles/usergroup.png' title='' align='left' alt='".$clang->gT("Create/Edit Groups")."' /></a>\n" ;

		// check settings
        //"\t\t\t\t\t<img src='$imagefiles/blank.gif' alt='' width='34'  align='left'>\n".
						$adminmenu .= "<a href=\"#\" onclick=\"window.open('$scriptname?action=checksettings', '_self')\" title=\"".$clang->gTview("Show System Summary")."\" "
					    . "onmouseout=\"hideTooltip()\""
                      	. "onmouseover=\"showTooltip(event,'".$clang->gT("Show System Summary", "js")."');return false\">"
						. "\t\t\t\t\t<img src='$imagefiles/summary.png' name='CheckSettings' title='"
						. "' alt='". $clang->gT("Show System Summary")."' align='left' /></a>"
						. "\t\t\t\t\t<img src='$imagefiles/seperator.gif' alt='' align='left' border='0' hspace='0' />\n";

		// check data cosistency
        if($_SESSION['USER_RIGHT_CONFIGURATOR'] == 1)
			{
			$adminmenu .= "<a href=\"#\" onclick=\"window.open('$scriptname?action=checkintegrity', '_self')\" title=\"".$clang->gTview("Check Data Integrity")."\" ".
						   "onmouseout=\"hideTooltip()\""
						  ."onmouseover=\"showTooltip(event,'".$clang->gT("Check Data Integrity", "js")."');return false\">".
						"<img src='$imagefiles/checkdb.png' name='CheckDataINtegrity' title=''  alt='".$clang->gT("Check Data Integrity")."' align='left' /></a>\n";
			}
		else
			{
			$adminmenu .= "\t\t\t\t\t<img src='$imagefiles/blank.gif' alt='' width='40'  align='left' />\n";
			}

		// list surveys
		$adminmenu .= "<a href=\"#\" onclick=\"window.open('$scriptname?action=listsurveys', '_self')\" title=\"".$clang->gTview("List Surveys")."\" "
		 			."onmouseout=\"hideTooltip()\""
                    ."onmouseover=\"showTooltip(event,'".$clang->gT("List Surveys", "js")."');return false\">\n"
		 			."<img src='$imagefiles/surveylist.png' name='ListSurveys' title=''"
		 			."  alt='".$clang->gT("List Surveys")."' align='left' onclick=\"window.open('$scriptname?action=listsurveys', '_self')\" />"
                    ."</a>" ;

		// db backup & label editor
		if($_SESSION['USER_RIGHT_CONFIGURATOR'] == 1)
			{
			$adminmenu  .= "<a href=\"#\" title=\"".$clang->gTview("Backup Entire Database")."\" "
						. "onclick=\"window.open('$scriptname?action=dumpdb', '_self')\""
						. "onmouseout=\"hideTooltip()\""
						. "onmouseover=\"showTooltip(event,'".$clang->gT("Backup Entire Database", "js")."');return false\">"
						."<img src='$imagefiles/backup.png' name='ExportDB' title='' alt='". $clang->gT("Backup Entire Database")."($surveyid)' align='left' />"
						."</a>\n"
						. "\t\t\t\t\t<img src='$imagefiles/seperator.gif' alt='' align='left' border='0' hspace='0' />\n";
			}
		else
			{
			  $adminmenu .= "\t\t\t\t\t<img src='$imagefiles/blank.gif' alt='' width='40'  align='left' />\n";
			}
		if($_SESSION['USER_RIGHT_MANAGE_LABEL'] == 1)
			{
			$adminmenu  .= "<a href=\"#\" onclick=\"window.open('$scriptname?action=labels', '_self')\" title=\"".$clang->gTview("Edit/Add Label Sets")."\" "
						. "onmouseout=\"hideTooltip()\""
						. "onmouseover=\"showTooltip(event,'".$clang->gT("Edit/Add Label Sets", "js")."');return false\">" .
						 "<img src='$imagefiles/labels.png' align='left' name='LabelsEditor' title='' alt='". $clang->gT("Edit/Add Label Sets")."' /></a>\n"
						. "\t\t\t\t\t<img src='$imagefiles/seperator.gif' alt='' align='left' border='0' hspace='0' />\n";
           	}
		else
			{
			  $adminmenu .= "\t\t\t\t\t<img src='$imagefiles/blank.gif' alt='' width='40'  align='left' />\n";
			}
        if($_SESSION['USER_RIGHT_MANAGE_TEMPLATE'] == 1)
			{
	        $adminmenu .= "<a href=\"#\" " .
	        			  "onclick=\"window.open('$scriptname?action=templates', '_self')\" title=\"".$clang->gTview("Template Editor")."\" "
	                    . "onmouseout=\"hideTooltip()\""
	                    . "onmouseover=\"showTooltip(event,'".$clang->gT("Template Editor", "js")."');return false\">" .
	                    "<img src='$imagefiles/templates.png' name='EditTemplates' title='' alt='". $clang->gT("Template Editor")."' align='left' /></a>\n"
	                    . "\t\t\t\t</td>\n";
            }
        if(isset($_SESSION['loginID'])) //ADDED by Moses to prevent errors by reading db while not logged in.
	        {
	        $adminmenu .= "\t\t\t\t<td align='right' width='430'>\n"
	                    . "<a href=\"#\" onclick=\"showhelp('show')\""
	                    . "title=\"".$clang->gTview("Show Help")."\" "
	                    . "onmouseout=\"hideTooltip()\""
	                    . "onmouseover=\"showTooltip(event,'".$clang->gT("Show Help", "js")."');return false\">"
	                    . "<img src='$imagefiles/showhelp.png' name='ShowHelp' title=''"
	                    . "alt='". $clang->gT("Show Help")."' align='right'  /></a>"
		                . "\t\t\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=logout', '_self')\""
                        . "title=\"".$clang->gTview("Logout")."\" "
                        . "onmouseout=\"hideTooltip()\""
					    . "onmouseover=\"showTooltip(event,'".$clang->gT("Logout", "js")."');return false\">"
                        . "<img src='$imagefiles/logout.png' name='Logout'"
					    . "title='' alt='".$clang->gT("Logout")."'  align='right' /></a>"
	                    . "\t\t\t\t\t<img src='$imagefiles/seperator.gif' alt='' align='right' border='0' hspace='0' />\n";

			if($_SESSION['USER_RIGHT_CREATE_SURVEY'] == 1)
				{
			$adminmenu .= "<a href=\"#\" onclick=\"window.open('$scriptname?action=newsurvey', '_self')\""
						. "title=\"".$clang->gTview("Create or Import New Survey")."\" "
						. "onmouseout=\"hideTooltip()\""
						. "onmouseover=\"showTooltip(event,'".$clang->gT("Create or Import New Survey", "js")."');return false\">" .
						"<img src='$imagefiles/add.png' align='right' name='AddSurvey' title='' alt='". $clang->gT("Create or Import New Survey")."' /></a>\n";
	             }
			$adminmenu .= "\t\t\t\t\t<font class=\"boxcaption\">".$clang->gT("Surveys").":</font>"
	                    . "\t\t\t\t\t<select onchange=\"window.open(this.options[this.selectedIndex].value,'_self')\">\n"
	                    . getsurveylist()
	                    . "\t\t\t\t\t</select>\n"
	                    . "\t\t\t\t</td>\n";
            }
            $adminmenu .= "\t\t\t</tr>\n"
	                    . "\t\t</table>\n"
	                    . "\t</td>\n"
	                    . "</tr>\n"
	                    . "</table>\n";
        return $adminmenu;
        }



//DATA TYPES
$qtypeselect = getqtypelist();

function &db_execute_num($sql,$inputarr=false)
{
	global $connect;

// Todo: Set fetchmode to previous state after changing
	//$oldfetchmode=
    $connect->SetFetchMode(ADODB_FETCH_NUM);
	$dataset=$connect->Execute($sql,$inputarr);  //Checked    
	//$connect->SetFetchMode($oldfetchmode);
	return $dataset;
}

function &db_select_limit_num($sql,$numrows=-1,$offset=-1,$inputarr=false)
{
	global $connect;

	$dataset=$connect->SelectLimit($sql,$numrows=-1,$offset=-1,$inputarr=false) or safe_die($sql);
	return $dataset;
}

function &db_execute_assoc($sql,$inputarr=false,$silent=false)
{
	global $connect;
// Todo: Set fetchmode to previous state after changing 
//	$oldfetchmode=
    $connect->SetFetchMode(ADODB_FETCH_ASSOC);
	$dataset=$connect->Execute($sql,$inputarr);    //Checked    
	if (!$silent && !$dataset)  {safe_die($connect->ErrorMsg().':'.$sql);}      
//	$connect->SetFetchMode($oldfetchmode);
	return $dataset;
}

function &db_select_limit_assoc($sql,$numrows=-1,$offset=-1,$inputarr=false,$dieonerror=true)
{
	global $connect;

	$connect->SetFetchMode(ADODB_FETCH_ASSOC);
	$dataset=$connect->SelectLimit($sql,$numrows,$offset,$inputarr=false);
    if (!$dataset && $dieonerror) {safe_die($connect->ErrorMsg().':'.$sql);}
	return $dataset;
}

function db_quote_id($id)
// This functions quotes fieldnames accordingly 
{
	global $databasetype;
    // WE DONT HAVE nor USE other thing that alfanumeric characters in the field names
//	$quote = $connect->nameQuote;
//	return $quote.str_replace($quote,$quote.$quote,$id).$quote;

    switch ($databasetype)
    {
        case "mysql" : 
            return "`".$id."`";
            break;
        case "odbc_mssql" : 
            return "[".$id."]";
            break;
        case "postgres": 
            return "\"".$id."\"";
            break;
        default: 
            return "`".$id."`";
    }
}

function db_random()
{
    global $connect,$databasetype;
    if ($databasetype=='odbc_mssql') {$srandom='NEWID()';}
    else {$srandom=$connect->random;}
    return $srandom;
    
}

function db_quote($str)
// This functions escapes the string only inside 
{
	global $connect;
	return $connect->escape($str);
}

function db_quoteall($str,$ispostvar=false)  
// This functions escapes the string inside and puts quotes around the string according to the used db type
// IF you are quoting a variable from a POST/GET then set $ispostvar to true so it doesnt get quoted twice.
{
	global $connect;
	if ($ispostvar) { return $connect->qstr($str, get_magic_quotes_gpc());}
	  else {return $connect->qstr($str);}
	
}

function db_table_name($name)
{
	global $dbprefix;
	return db_quote_id($dbprefix.$name);
}

function db_table_name_nq($name)
//returns the table name without quotes
{
    
    global $dbprefix;
    return $dbprefix.$name;
}

/*
 *  Return a sql statement for finding LIKE named tables
 */
function db_select_tables_like($table)
{
	global $databasetype;
	switch ($databasetype) {
		case 'mysql'	  : 
			return "SHOW TABLES LIKE '$table'";
		case 'odbc_mssql' : 
			return "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_TYPE='BASE TABLE' and TABLE_NAME LIKE '$table'";
		case 'postgres' : 
			return "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' and table_name like '$table'";
		default: safe_die ("Couldn't create 'select tables like' query for connection type 'databaseType'"); 
	}	
}

/*
 *  Return a boolean stating if the table(s) exist(s)
 *  Accepts '%' in names since it uses the 'like' statement
 */
function db_tables_exist($table)
{
	global $connect;

	$surveyHasTokensTblQ = db_select_tables_like("$table");
	$surveyHasTokensTblResult = db_execute_num($surveyHasTokensTblQ); //Checked

	if ($surveyHasTokensTblResult->RecordCount() >= 1)
	{
		return TRUE;
	}
	else
	{
		return FALSE;
	}
}

/**
* getsurveylist() Queries the database (survey table) for a list of existing surveys
* @global string $surveyid
* @global string $dbprefix
* @global string $scriptname
* @return string This string is returned containing <option></option> formatted list of existing surveys
*
*/
function getsurveylist()
    {
    global $surveyid, $dbprefix, $scriptname, $connect, $clang;
    $surveyidquery = "SELECT a.sid, a.owner_id, surveyls_title, surveyls_description, a.admin, a.active, surveyls_welcometext, a.useexpiry, a.expires, "
					. "a.adminemail, a.private, a.faxto, a.format, a.template, a.url, "
					. "a.language, a.datestamp, a.ipaddr, a.refurl, a.usecookie, a.notification, a.allowregister, a.attribute1, a.attribute2, "
					. "a.allowsave, a.autoredirect, a.allowprev, a.datecreated FROM ".db_table_name('surveys')." AS a "
					. "INNER JOIN ".db_table_name('surveys_languagesettings')." on (surveyls_survey_id=a.sid and surveyls_language=a.language) ";

	if ($_SESSION['USER_RIGHT_SUPERADMIN'] != 1)
	{
		$surveyidquery .= " INNER JOIN ".db_table_name('surveys_rights')." AS b ON a.sid = b.sid ";
		$surveyidquery .= "WHERE b.uid =".$_SESSION['loginID'];
	}

	$surveyidquery .= " order by active DESC, surveyls_title";

    $surveyidresult = db_execute_num($surveyidquery);  //Checked
    if (!$surveyidresult) {return "Database Error";}
    $surveyselecter = "";
    $surveynames = $surveyidresult->GetRows();
    $activesurveys='';
    $inactivesurveys='';
    if ($surveynames)
    {
        foreach($surveynames as $sv)
        {
            if($sv[5]!='Y') 
            { 
              $inactivesurveys .= "\t\t\t<option ";
        			if($_SESSION['loginID'] == $sv[1]) {$inactivesurveys .= " style=\"font-weight: bold;\"";}
        			if ($sv[0] == $surveyid) {$inactivesurveys .= " selected='selected'"; $svexist = 1;}
                    $inactivesurveys .=" value='$scriptname?sid=$sv[0]'>$sv[2]</option>\n";
            }
              else
              {
              $activesurveys .= "\t\t\t<option ";
        			if($_SESSION['loginID'] == $sv[1]) {$activesurveys .= " style=\"font-weight: bold;\"";}
        			if ($sv[0] == $surveyid) {$activesurveys .= " selected='selected'"; $svexist = 1;}
                    $activesurveys .=" value='$scriptname?sid=$sv[0]'>$sv[2]</option>\n";
              
              }
        }
		}
    //Only show each activesurvey group if there are some 
    if ($activesurveys!='') 
    {  
      $surveyselecter .= "\t\t\t<optgroup label='".$clang->gT("Active")."' class='activesurveyselect'>\n";
      $surveyselecter .= $activesurveys . "\t\t\t</optgroup>";
    }
    if ($inactivesurveys!='') 
    {  
      $surveyselecter .= "\t\t\t<optgroup label='".$clang->gT("Inactive")."' class='inactivesurveyselect'>\n";
      $surveyselecter .= $inactivesurveys . "\t\t\t</optgroup>";
    }    
    if (!isset($svexist)) {$surveyselecter = "\t\t\t<option selected='selected'>".$clang->gT("Please Choose...")."</option>\n".$surveyselecter;}
      else {$surveyselecter = "\t\t\t<option value='$scriptname?sid='>".$clang->gT("None")."</option>\n".$surveyselecter;}
    return $surveyselecter;
    }

/**
* getquestions() queries the database for a list of all questions matching the current survey sid
* @global string $surveyid
* @global string $gid
* @global string $qid
* @global string $dbprefix
* @global string $scriptname
* @return This string is returned containing <option></option> formatted list of questions to current survey
*/
function getquestions($surveyid,$gid,$selectedqid)
{
	global $dbprefix, $scriptname, $connect, $clang;
//MOD for multilanguage surveys
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	$qquery = 'SELECT * FROM '.db_table_name('questions')." WHERE sid=$surveyid AND gid=$gid AND language='{$s_lang}'";
	$qresult = db_execute_assoc($qquery); //checked
	$qrows = $qresult->GetRows();

	// Perform a case insensitive natural sort on group name then question title of a multidimensional array
	usort($qrows, 'CompareGroupThenTitle');
	if (!isset($questionselecter)) {$questionselecter="";}
	foreach ($qrows as $qrow)
	{
		$qrow['title'] = strip_tags($qrow['title']);
		$questionselecter .= "\t\t<option value='$scriptname?sid=$surveyid&amp;gid=$gid&amp;qid={$qrow['qid']}'";
		if ($selectedqid == $qrow['qid']) {$questionselecter .= " selected='selected'"; $qexists="Y";}
		$questionselecter .=">{$qrow['title']}:";
		$questionselecter .= " ";
		$question=strip_tags($qrow['question']);
		if (strlen($question)<35)
		{
			$questionselecter .= $question;
		}
		else
		{
			$questionselecter .= substr($question, 0, 35)."..";
		}
		$questionselecter .= "</option>\n";
	}

	if (!isset($qexists))
	{
		$questionselecter = "\t\t<option selected='selected'>".$clang->gT("Please Choose...")."</option>\n".$questionselecter;
	}
	return $questionselecter;
}


// Gets number of groups inside a particular survey
function getGroupSum($surveyid, $lang)
{
	global $surveyid,$dbprefix ;
	$sumquery3 = "SELECT * FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='".$lang."'"; //Getting a count of questions for this survey

	$sumresult3 = db_execute_assoc($sumquery3); //Checked
	$groupscount = $sumresult3->RecordCount();

	return $groupscount ;
}


// Gets number of questions inside a particular group 
function getQuestionSum($surveyid, $groupid)
{
	global $surveyid,$dbprefix ;
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	$sumquery3 = "SELECT * FROM ".db_table_name('questions')." WHERE gid=$groupid and sid=$surveyid AND language='{$s_lang}'"; //Getting a count of questions for this survey
	$sumresult3 = db_execute_assoc($sumquery3); //Checked
	$questionscount = $sumresult3->RecordCount();
	return $questionscount ;
}


/**
* getMaxgrouporder($surveyid) queries the database for the maximum sortorder of a group.
* @global string $surveyid
*/
function getMaxgrouporder($surveyid)
{
	global $surveyid ;
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	$max_sql = "SELECT max( group_order ) AS max FROM ".db_table_name('groups')." WHERE sid =$surveyid AND language='{$s_lang}'" ;
	$max_result =db_execute_assoc($max_sql) ; //Checked
	$maxrow = $max_result->FetchRow() ;
	$current_max = $maxrow['max'];
	if($current_max=="")
	{
		return "0" ;
	}
	else return ++$current_max ;
}

/**
* getGroupOrder($surveyid,$gid) queries the database for the sortorder of a group.
*/
function getGroupOrder($surveyid,$gid)
{
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	$grporder_sql = "SELECT group_order FROM ".db_table_name('groups')." WHERE sid =$surveyid AND language='{$s_lang}' AND gid=$gid" ;
	$grporder_result =db_execute_assoc($grporder_sql); //Checked
	$grporder_row = $grporder_result->FetchRow() ;
	$group_order = $grporder_row['group_order'];
	if($group_order=="")
	{
		return "0" ;
	}
	else return $group_order ;
}

/**
* getMaxquestionorder($gid) queries the database for the maximum sortorder of a question.
* @global string $surveyid
*/
function getMaxquestionorder($gid)
{
	global $surveyid ;
	$gid=sanitize_int($gid);
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	$max_sql = "SELECT max( question_order ) AS max FROM ".db_table_name('questions')." WHERE gid='$gid' AND language='$s_lang'";

	$max_result =db_execute_assoc($max_sql) ; //Checked
	$maxrow = $max_result->FetchRow() ;
	$current_max = $maxrow['max'];
	if($current_max=="")
	{
		return "0" ;
	}
	else return $current_max ;
}


/**
* getanswers() queries the database for a list of all answers matching the current question qid
* @global string $surveyid
* @global string $gid
* @global string $qid
* @global string $dbprefix
* @global string $code
* @return This string is returned containing <option></option> formatted list of answers matching current qid
*/
function getanswers()
{
	global $surveyid, $gid, $qid, $code, $dbprefix, $connect, $clang;
	$qid=sanitize_int($qid);
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	$aquery = "SELECT code, answer FROM ".db_table_name('answers')." WHERE qid=$qid AND language='$s_lang' ORDER BY sortorder, answer";

	$aresult = db_execute_assoc($aquery); //Checked
	$answerselecter = "";
	while ($arow = $aresult->FetchRow())
	{
		$answerselecter .= "\t\t<option value='$scriptname?sid=$surveyid&amp;gid=$gid&amp;qid=$qid&amp;code={$arow['code']}'";
		if ($code == $arow['code']) {$answerselecter .= " selected='selected'"; $aexists="Y";}
		$answerselecter .= ">{$arow['code']}: {$arow['answer']}</option>\n";
	}
	if (!$aexists) {$answerselecter = "\t\t<option selected='selected'>".$clang->gT("Please Choose...")."</option>\n".$answerselecter;}
	return $answerselecter;
}


/**
* getqtypelist() Returns list of question types available in LimeSurvey. Edit this if you are adding a new
*    question type
* @global string $publicurl
* @global string $sourcefrom
* @param string $SelectedCode Value of the Question Type (defaults to "T")
* @param string $ReturnType Type of output from this function (defaults to selector)
* @return depending on $ReturnType param, returns a straight "array" of question types, or an <option></option> list
*/
function getqtypelist($SelectedCode = "T", $ReturnType = "selector")
{
	global $publicurl;
	global $sourcefrom, $clang;
	if ($sourcefrom == "admin")
	{
		$qtypes = array(
		"1"=>$clang->gT("Array (Flexible Labels) Dual Scale"),
		"5"=>$clang->gT("5 Point Choice"),
		"A"=>$clang->gT("Array (5 Point Choice)"),
		"B"=>$clang->gT("Array (10 Point Choice)"),
		"C"=>$clang->gT("Array (Yes/No/Uncertain)"),
		"D"=>$clang->gT("Date"),
		"E"=>$clang->gT("Array (Increase, Same, Decrease)"),
		"F"=>$clang->gT("Array (Flexible Labels)"),
		"G"=>$clang->gT("Gender"),
		"H"=>$clang->gT("Array (Flexible Labels) by Column"),
		"I"=>$clang->gT("Language Switch"),
		"K"=>$clang->gT("Multiple Numerical Input"),
		"L"=>$clang->gT("List (Radio)"),
		"M"=>$clang->gT("Multiple Options"),
		"N"=>$clang->gT("Numerical Input"),
		"O"=>$clang->gT("List With Comment"),
		"P"=>$clang->gT("Multiple Options With Comments"),
		"Q"=>$clang->gT("Multiple Short Text"),
		"R"=>$clang->gT("Ranking"),
		"S"=>$clang->gT("Short Free Text"),
		"T"=>$clang->gT("Long Free Text"),
		"U"=>$clang->gT("Huge Free Text"),
		"W"=>$clang->gT("List (Flexible Labels) (Dropdown)"),
		"X"=>$clang->gT("Boilerplate Question"),
		"Y"=>$clang->gT("Yes/No"),
		"Z"=>$clang->gT("List (Flexible Labels) (Radio)"),
		"!"=>$clang->gT("List (Dropdown)")
		//            "^"=>$clang->gT("Slider"),
		);
        asort($qtypes);
		if ($ReturnType == "array") {return $qtypes;}
		$qtypeselecter = "";
		foreach($qtypes as $TypeCode=>$TypeDescription)
		{
			$qtypeselecter .= "\t\t<option value='$TypeCode'";
			if ($SelectedCode == $TypeCode) {$qtypeselecter .= " selected='selected'";}
			$qtypeselecter .= ">$TypeDescription</option>\n";
		}
		return $qtypeselecter;
	}
}


/**
* getNotificationlist() returns different options for notifications
* @param string $notificationcode - the currently selected one
* @return This string is returned containing <option></option> formatted list of notification methods for current survey
*/
function getNotificationlist($notificationcode)
{
	global $clang;
	$ntypes = array(
	"0"=>$clang->gT("No email notification"),
	"1"=>$clang->gT("Basic email notification"),
	"2"=>$clang->gT("Detailed email notification with result codes")
	);
	if (!isset($ntypeselector)) {$ntypeselector="";}
	foreach($ntypes as $ntcode=>$ntdescription)
	{
		$ntypeselector .= "\t\t<option value='$ntcode'";
		if ($notificationcode == $ntcode) {$ntypeselector .= " selected='selected'";}
		$ntypeselector .= ">$ntdescription</option>\n";
	}
	return $ntypeselector;
}


/**
* getgrouplist() queries the database for a list of all groups matching the current survey sid
* @global string $surveyid
* @global string $dbprefix
* @global string $scriptname
* @param string $gid - the currently selected gid/group
* @return This string is returned containing <option></option> formatted list of groups to current survey
*/
function getgrouplist($gid)
{
	global $surveyid, $dbprefix, $scriptname, $connect, $clang;
	$groupselecter="";
    $gid=sanitize_int($gid);
    $surveyid=sanitize_int($surveyid);
	if (!$surveyid) {$surveyid=returnglobal('sid');}
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	$gidquery = "SELECT gid, group_name FROM ".db_table_name('groups')." WHERE sid='{$surveyid}' AND  language='{$s_lang}'  ORDER BY group_order";
	$gidresult = db_execute_num($gidquery) or safe_die("Couldn't get group list in common.php<br />$gidquery<br />".$connect->ErrorMsg()); //Checked
	while($gv = $gidresult->FetchRow())
	{
		$groupselecter .= "\t\t<option";
		if ($gv[0] == $gid) {$groupselecter .= " selected='selected'"; $gvexist = 1;}
		$groupselecter .= " value='$scriptname?sid=$surveyid&amp;gid=$gv[0]'>".htmlspecialchars($gv[1])."</option>\n";
	}
	if ($groupselecter)
	{
		if (!isset($gvexist)) {$groupselecter = "\t\t<option selected='selected'>".$clang->gT("Please Choose...")."</option>\n".$groupselecter;}
		else {$groupselecter .= "\t\t<option value='$scriptname?sid=$surveyid&amp;gid='>".$clang->gT("None")."</option>\n";}
	}
	return $groupselecter;
}


function getgrouplist2($gid)
{
	global $surveyid, $dbprefix, $connect, $clang;
	$groupselecter = "";
	if (!$surveyid) {$surveyid=returnglobal('sid');}
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	$gidquery = "SELECT gid, group_name FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='{$s_lang}' ORDER BY group_order";


	$gidresult = db_execute_num($gidquery) or safe_die("Plain old did not work!");   //Checked
	while ($gv = $gidresult->FetchRow())
	{
		$groupselecter .= "\t\t<option";
		if ($gv[0] == $gid) {$groupselecter .= " selected='selected'"; $gvexist = 1;}
		$groupselecter .= " value='$gv[0]'>".htmlspecialchars($gv[1])."</option>\n";
	}
	if ($groupselecter)
	{
		if (!$gvexist) {$groupselecter = "\t\t<option selected='selected'>".$clang->gT("Please Choose...")."</option>\n".$groupselecter;}
		else {$groupselecter .= "\t\t<option value=''>".$clang->gT("None")."</option>\n";}
	}
	return $groupselecter;
}


function getgrouplist3($gid)
{
	global $surveyid, $dbprefix, $connect;
    if (!$surveyid) {$surveyid=returnglobal('sid');}
	$groupselecter = "";
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	$gidquery = "SELECT gid, group_name FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='{$s_lang}' ORDER BY group_order";


	$gidresult = db_execute_num($gidquery) or safe_die("Plain old did not work!");      //Checked
	while ($gv = $gidresult->FetchRow())
	{
		$groupselecter .= "\t\t<option";
		if ($gv[0] == $gid) {$groupselecter .= " selected='selected'"; $gvexist = 1;}
		$groupselecter .= " value='$gv[0]'>".htmlspecialchars($gv[1])."</option>\n";
	}
	return $groupselecter;
}


function getgrouplistlang($gid, $language)
{
	global $surveyid, $scriptname, $connect, $clang;

	$groupselecter="";
    if (!$surveyid) {$surveyid=returnglobal('sid');}
	$gidquery = "SELECT gid, group_name FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='".$language."' ORDER BY group_order";
	$gidresult = db_execute_num($gidquery) or safe_die("Couldn't get group list in common.php<br />$gidquery<br />".$connect->ErrorMsg());   //Checked
	while($gv = $gidresult->FetchRow())
	{
		$groupselecter .= "\t\t<option";
		if ($gv[0] == $gid) {$groupselecter .= " selected='selected'"; $gvexist = 1;}
		$groupselecter .= " value='$scriptname?sid=$surveyid&amp;gid=$gv[0]'>".htmlspecialchars(strip_tags($gv[1]))."</option>\n";
	}
	if ($groupselecter)
	{
		if (!isset($gvexist)) {$groupselecter = "\t\t<option selected='selected'>".$clang->gT("Please Choose...")."</option>\n".$groupselecter;}
		else {$groupselecter .= "\t\t<option value='$scriptname?sid=$surveyid&amp;gid='>".$clang->gT("None")."</option>\n";}
	}
	return $groupselecter;
}


function getuserlist($outputformat='fullinfoarray')
{
	global $dbprefix, $connect;
	global $usercontrolSameGroupPolicy;

    if (isset($_SESSION['loginID']))
		{
			$myuid=sanitize_int($_SESSION['loginID']);
		}

	if ($_SESSION['USER_RIGHT_SUPERADMIN'] != 1 && isset($usercontrolSameGroupPolicy) &&
		$usercontrolSameGroupPolicy === true)
	{
		if (isset($myuid))
		{
			// List users from same group as me + all my childs
			$uquery = "SELECT u.* FROM ".db_table_name('users')." AS u, ".db_table_name('user_in_groups')." AS ga ,".db_table_name('user_in_groups')." AS gb WHERE u.uid=$myuid OR (ga.ugid=gb.ugid AND ( (gb.uid=$myuid AND u.uid=ga.uid) OR (u.parent_id=$myuid) ) ) GROUP BY u.uid";
		}
		else
		{
			return Array(); // Or die maybe
		}

	}
	else
	{
		$uquery = "SELECT * FROM ".db_table_name('users')." ORDER BY uid";
	}

	$uresult = db_execute_assoc($uquery); //Checked
    
    if ($uresult->RecordCount()==0)  
    //user is not in a group and usercontrolSameGroupPolicy is activated - at least show his own userinfo
    {
            $uquery = "SELECT u.* FROM ".db_table_name('users')." AS u WHERE u.uid=".$myuid;
            $uresult = db_execute_assoc($uquery);//Checked
    }

	$userlist = array();
	$userlist[0] = "Reserved for logged in user";
	while ($srow = $uresult->FetchRow())
	{
		if ($outputformat != 'onlyuidarray')
		{
			if ($srow['uid'] != $_SESSION['loginID'])
			{
				$userlist[] = array("user"=>$srow['users_name'], "uid"=>$srow['uid'], "email"=>$srow['email'], "password"=>$srow['password'], "full_name"=>$srow['full_name'], "parent_id"=>$srow['parent_id'], "create_survey"=>$srow['create_survey'], "configurator"=>$srow['configurator'], "create_user"=>$srow['create_user'], "delete_user"=>$srow['delete_user'], "superadmin"=>$srow['superadmin'], "manage_template"=>$srow['manage_template'], "manage_label"=>$srow['manage_label']);			//added by Dennis modified by Moses
			}
			else
			{
				$userlist[0] = array("user"=>$srow['users_name'], "uid"=>$srow['uid'], "email"=>$srow['email'], "password"=>$srow['password'], "full_name"=>$srow['full_name'], "parent_id"=>$srow['parent_id'], "create_survey"=>$srow['create_survey'], "configurator"=>$srow['configurator'], "create_user"=>$srow['create_user'], "delete_user"=>$srow['delete_user'], "superadmin"=>$srow['superadmin'], "manage_template"=>$srow['manage_template'], "manage_label"=>$srow['manage_label']);
			}
		}
		else
		{
			if ($srow['uid'] != $_SESSION['loginID'])
			{
				$userlist[] = $srow['uid'];
			}
			else
			{
				$userlist[0] = $srow['uid'];
			}
		}

	}
	return $userlist;
}


function gettemplatelist()
{
	global $publicdir;
	if (!$publicdir) {$publicdir=dirname(getcwd());}
	$tloc="$publicdir/templates";
	if ($handle = opendir($tloc))
	{
		while (false !== ($file = readdir($handle)))
		{
			if (!is_file("$tloc/$file") && $file != "." && $file != ".." && $file!=".svn")
			{
				$list_of_files[] = $file;
			}
		}
		closedir($handle);
	}
	usort($list_of_files, 'StandardSort');
	return $list_of_files;
}


function getSurveyInfo($surveyid, $languagecode='')
// Gets all survey infos in one big array including the language specific settings
// if $languagecode is not set then the base language from the survey is used
// 
{
	global $dbprefix, $siteadminname, $siteadminemail, $connect, $languagechanger;
	$surveyid=sanitize_int($surveyid);
	$languagecode=sanitize_languagecode($languagecode);
	$thissurvey=false;
	// if no language code is set then get the base language one
    if (!isset($languagecode) || $languagecode=='')
	{
	   $languagecode=GetBaseLanguageFromSurveyID($surveyid);;
    }
	$query="SELECT * FROM ".db_table_name('surveys').",".db_table_name('surveys_languagesettings')." WHERE sid=$surveyid and surveyls_survey_id=$surveyid and surveyls_language='$languagecode'";
    $result=db_execute_assoc($query) or safe_die ("Couldn't access survey settings<br />$query<br />".$connect->ErrorMsg());   //Checked
	while ($row=$result->FetchRow())
	{
        $thissurvey=$row;
        // now create some stupid array translations
        // Newly added surveysettings don't have to be added specifically - these will be available by field name automatically
        $thissurvey["name"]=$thissurvey['surveyls_title'];
        $thissurvey["description"]=$thissurvey['surveyls_description'];
        $thissurvey["welcome"]=$thissurvey['surveyls_welcometext'];
        $thissurvey["templatedir"]=$thissurvey['template'];
        $thissurvey["adminname"]=$thissurvey['admin'];
        $thissurvey["tablename"]=$dbprefix."survey_".$thissurvey['sid'];
        $thissurvey["urldescrip"]=$thissurvey['surveyls_urldescription'];
        $thissurvey["sendnotification"]=$thissurvey['notification'];
        $thissurvey["expiry"]=$thissurvey['expires'];
        $thissurvey["email_invite_subj"]=$thissurvey['surveyls_email_invite_subj'];
        $thissurvey["email_invite"]=$thissurvey['surveyls_email_invite'];
        $thissurvey["email_remind_subj"]=$thissurvey['surveyls_email_remind_subj'];
        $thissurvey["email_remind"]=$thissurvey['surveyls_email_remind'];
        $thissurvey["email_confirm_subj"]=$thissurvey['surveyls_email_confirm_subj'];
        $thissurvey["email_confirm"]=$thissurvey['surveyls_email_confirm'];
        $thissurvey["email_register_subj"]=$thissurvey['surveyls_email_register_subj'];
        $thissurvey["email_register"]=$thissurvey['surveyls_email_register'];
	    if (!isset($thissurvey['adminname'])) {$thissurvey['adminname']=$siteadminname;}
	    if (!isset($thissurvey['adminemail'])) {$thissurvey['adminemail']=$siteadminemail;}
	    if (!isset($thissurvey['urldescrip'])) {$thissurvey['urldescrip']=$thissurvey['url'];}
	}              
    
    //not sure this should be here... ToDo: Find a better place
    if (function_exists('makelanguagechanger')) $languagechanger = makelanguagechanger();
	return $thissurvey;
}


function getlabelsets($languages=null)
// Returns a list with label sets
// if the $languages paramter is provided then only labelset containing all of the languages in the paramter are provided
{
	global $dbprefix, $connect, $surveyid;
	if ($languages){
      $languages=sanitize_languagecodeS($languages);
	  $languagesarray=explode(' ',trim($languages));
    } 
	$query = "SELECT ".db_table_name('labelsets').".lid as lid, label_name FROM ".db_table_name('labelsets');
	if ($languages){
        $query .=" where ";
        foreach  ($languagesarray as $item)
        {
        $query .=" ((languages like '% $item %') or (languages='$item') or (languages like '% $item') or (languages like '$item %')) and ";
        }
        $query .=" 1=1 ";
    }
    $query .=" order by label_name";
	$result = db_execute_assoc($query) or safe_die ("Couldn't get list of label sets<br />$query<br />".$connect->ErrorMsg()); //Checked
	$labelsets=array();
	while ($row=$result->FetchRow())
	{
		$labelsets[] = array($row['lid'], $row['lid'].": ".$row['label_name']);
	}
	return $labelsets;
}


function checkactivations()
{
	global $dbprefix, $connect;
	$tablelist = $connect->MetaTables();
	$tablenames[] = "ListofTables"; //dummy entry because in_array never finds the first one!
	foreach ($tablelist as $tbl)
	{
		$tablenames[] = $tbl;
	}
	$caquery = "SELECT sid FROM ".db_table_name('surveys')." WHERE active='Y'";
	$caresult = db_execute_assoc($caquery);    //Checked
	if (!$caresult) {return "Database Error";}
	while ($carow = $caresult->FetchRow())
	{
		$surveyname = "{$dbprefix}survey_{$carow['sid']}";
		if (!in_array($surveyname, $tablenames))
		{
			$udquery = "UPDATE ".db_table_name('surveys')." SET active='N' WHERE sid={$carow['sid']}";
			$udresult = $connect->Execute($udquery);   //Checked    
		}
	}
}


function checkifemptydb()
{
	global $connect, $dbprefix;
	$tablelist = $connect->MetaTables('TABLES');
	if ( in_array($dbprefix.'surveys',$tablelist) ) {Return(false);}
	else {Return(true);}
}

function checkfortables()
{
	global $scriptname, $dbprefix, $setfont, $connect, $clang;
	$alltables=array("{$dbprefix}surveys",
	"{$dbprefix}groups",
	"{$dbprefix}questions",
	"{$dbprefix}answers",
	"{$dbprefix}conditions",
	"{$dbprefix}users",
	"{$dbprefix}labelsets",
	"{$dbprefix}labels");
	$tables = $connect->MetaTables();

	foreach($alltables as $at)
	{
		if (!sql_table_exists($at, $tables))
		{
			$checkfields="Y";
		}
	}
	if (!isset($checkfields)) {$checkfields="";}
	if ($checkfields=="Y")
	{
		echo "<br />\n"
		."<table width='350' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n"
		."\t<tr bgcolor='#555555'><td colspan='2' height='4'><font size='1' face='verdana' color='white'><strong>"
		.$clang->gT("LimeSurvey Setup")."</strong></td></tr>\n"
		."\t<tr bgcolor='#CCCCCC'><td align='center'>$setfont\n"
		."\t\t<font color='red'><strong>"
		.$clang->gT("Error")."</strong></font><br />\n"
		."\t\t"
		.$clang->gT("It appears as if some tables or fields are missing from your database.")."<br /><br />\n"
		."\t\t<input type='submit' value='"
		.$clang->gT("Check Database Fields")."' onclick=\"window.open('checkfields.php', '_self')\" />\n"
		."\t</td></tr>\n"
		."</table>\n"
		."</body></html>\n";
		exit;
	}
}


function sql_table_exists($tableName, $tables)
{
	return(in_array($tableName, $tables));
}


################################################################################
# Compares two elements from an array (passed by the usort function)
# and returns -1, 0 or 1 depending on the result of the comparison of
# the sort order of the group_order and question_order field
function CompareGroupThenTitle($a, $b)
{
	if (isset($a["group_order"]) && isset($b["group_order"]))
	{
		$GroupResult = strnatcasecmp($a["group_order"], $b["group_order"]);
	}
	else
	{
		$GroupResult = "";
	}
	if ($GroupResult == 0)
	{
		$TitleResult = strnatcasecmp($a["question_order"], $b["question_order"]);
		return $TitleResult;
	}
	return $GroupResult;
}


function StandardSort($a, $b)
{
	return strnatcasecmp($a, $b);
}


function conditionscount($qid)
{
	global $dbprefix, $connect;
    $qid=sanitize_int($qid);
	$query="SELECT COUNT(*) FROM ".db_table_name('conditions')." WHERE qid=$qid";
	$result=db_execute_num($query) or safe_die ("Couldn't get conditions<br />$query<br />".$connect->ErrorMsg());
	list($count) = $result->FetchRow();
	return $count;
}


function keycontroljs()
{
	$kcjs="
    <script type=\"text/javascript\">
    <!--

    function getkey(e)
       {
       if (window.event) return window.event.keyCode;
        else if (e) return e.which; else return null;
        }

    function goodchars(e, goods)
        {
       var key, keychar;
       key = getkey(e);
        if (key == null) return true;

        // get character
        keychar = String.fromCharCode(key);
        keychar = keychar.toLowerCase();
       goods = goods.toLowerCase();

       // check goodkeys
        if (goods.indexOf(keychar) != -1)
            return true;

        // control keys
        if ( key==null || key==0 || key==8 || key==9  || key==27 || key==13)
          return true;

      // else return false
     return false;
       }
    //-->
    </script>
";
	return $kcjs;
}


function fixsortorderAnswers($qid) //Function rewrites the sortorder for a group of answers
{
	global $dbprefix, $connect, $surveyid;
    $qid=sanitize_int($qid);
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	$cdresult = db_execute_num("SELECT qid, code, sortorder FROM ".db_table_name('answers')." WHERE qid={$qid} and language='{$baselang}' ORDER BY sortorder"); //Checked    
	$position=0;
	while ($cdrow=$cdresult->FetchRow())
	{
		$cd2query="UPDATE ".db_table_name('answers')." SET sortorder={$position} WHERE qid={$cdrow[0]} AND code='{$cdrow[1]}' AND sortorder={$cdrow[2]} ";
		$cd2result=$connect->Execute($cd2query) or safe_die ("Couldn't update sortorder<br />$cd2query<br />".$connect->ErrorMsg()); //Checked    
		$position++;
	}
}


function fixsortorderQuestions($qid,$gid=0) //Function rewrites the sortorder for questions
{
	global $dbprefix, $connect, $surveyid;
    $qid=sanitize_int($qid);
    $gid=sanitize_int($gid);
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	if ($gid == 0)
    {
    	$result = db_execute_assoc("SELECT gid FROM ".db_table_name('questions')." WHERE qid='{$qid}' and language='{$baselang}'");  //Checked
    	$row=$result->FetchRow();
    	$gid=$row['gid'];
    }
	$cdresult = db_execute_assoc("SELECT qid FROM ".db_table_name('questions')." WHERE gid='{$gid}' and language='{$baselang}' ORDER BY question_order, title ASC");      //Checked    
	$position=0;
	while ($cdrow=$cdresult->FetchRow())
	{
		$cd2query="UPDATE ".db_table_name('questions')." SET question_order='{$position}' WHERE qid='{$cdrow['qid']}' ";
		$cd2result = $connect->Execute($cd2query) or safe_die ("Couldn't update question_order<br />$cd2query<br />".$connect->ErrorMsg());    //Checked    
		$position++;
	}
}


function shiftorderQuestions($sid,$gid,$shiftvalue) //Function shifts the sortorder for questions
{
	global $dbprefix, $connect, $surveyid;
    $sid=sanitize_int($sid);
    $gid=sanitize_int($gid);
    $shiftvalue=sanitize_int($shiftvalue);
    
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	$cdresult = db_execute_assoc("SELECT qid FROM ".db_table_name('questions')." WHERE gid='{$gid}' and language='{$baselang}' ORDER BY question_order, title ASC"); //Checked
	$position=$shiftvalue;
	while ($cdrow=$cdresult->FetchRow())
	{
		$cd2query="UPDATE ".db_table_name('questions')." SET question_order='{$position}' WHERE qid='{$cdrow['qid']}' ";
		$cd2result = $connect->Execute($cd2query) or safe_die ("Couldn't update question_order<br />$cd2query<br />".$connect->ErrorMsg()); //Checked 
		$position++;
	}
}

function fixsortorderGroups() //Function rewrites the sortorder for groups
{
	global $dbprefix, $connect, $surveyid;
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	$cdresult = db_execute_assoc("SELECT gid FROM ".db_table_name('groups')." WHERE language='{$baselang}' ORDER BY group_order, group_name"); //Checked
	$position=0;
	while ($cdrow=$cdresult->FetchRow())
	{
		$cd2query="UPDATE ".db_table_name('groups')." SET group_order='{$position}' WHERE gid='{$cdrow['gid']}' ";
		$cd2result = $connect->Execute($cd2query) or safe_die ("Couldn't update group_order<br />$cd2query<br />".$connect->ErrorMsg());  //Checked   
		$position++;
	}
}

function fixmovedquestionConditions($qid,$oldgid,$newgid) //Function rewrites the cfieldname for a question after group change
{
	global $dbprefix, $connect, $surveyid;
    $qid=sanitize_int($qid);
    $oldgid=sanitize_int($oldgid);
    $newgid=sanitize_int($newgid);

	$cresult = db_execute_assoc("SELECT cid, cfieldname FROM ".db_table_name('conditions')." WHERE cqid={$qid}");  //Checked
	while ($crow=$cresult->FetchRow())
	{

		$mycid=$crow['cid'];
		$mycfieldname=$crow['cfieldname'];
		$cfnregs="";

		if (ereg($surveyid."X".$oldgid."X".$qid."(.*)", $mycfieldname, $cfnregs) > 0) 
		{
			$newcfn=$surveyid."X".$newgid."X".$qid.$cfnregs[1];
			$c2query="UPDATE ".db_table_name('conditions')
			." SET cfieldname='{$newcfn}' WHERE cid={$mycid}";

			$c2result=$connect->Execute($c2query)     //Checked   
			or safe_die ("Couldn't update conditions<br />$c2query<br />".$connect->ErrorMsg());
		}
	}
}

function browsemenubar()
{
	global $surveyid, $scriptname, $imagefiles, $homeurl, $clang, $sumrows5;
	//BROWSE MENU BAR
	$browsemenubar = "\t<tr>\n"
	. "\t\t<td>\n"
	. "\t\t\t<a href='$scriptname?sid=$surveyid' onmouseout=\"hideTooltip()\" " .
			"title=\"".$clang->gTview("Return to Survey Administration")."\" " .
			"onmouseover=\"showTooltip(event,'".$clang->gT("Return to Survey Administration", "js")."')\">" .
			"<img name='Administration' src='$imagefiles/home.png' title='' alt='' align='left' /></a>\n"
	. "\t\t\t<img src='$imagefiles/blank.gif' alt='' width='11'  align='left' />\n"
	. "\t\t\t<img src='$imagefiles/seperator.gif' alt=''  align='left' />\n"
	. "\t\t\t<a href='$scriptname?action=browse&amp;sid=$surveyid' onmouseout=\"hideTooltip()\"" .
			" title=\"".$clang->gTview("Show summary information")."\" " .
			" onmouseover=\"showTooltip(event,'".$clang->gT("Show summary information", "js")."')\"" .
			"><img name='SurveySummary' src='$imagefiles/summary.png' title='' alt='' align='left' /></a>\n";
    if (count(GetAdditionalLanguagesFromSurveyID($surveyid)) == 0)
    {
        $browsemenubar .="\t\t\t<a href='$scriptname?action=browse&amp;sid=$surveyid&amp;subaction=all' onmouseout=\"hideTooltip()\"" .
        "title=\"".$clang->gTview("Display Responses")."\" " .
        "onmouseover=\"showTooltip(event,'".$clang->gT("Display Responses", "js")."')\">" .
        "<img name='ViewAll' src='$imagefiles/document.png' title='' alt='' align='left' /></a>\n";
    
    } else {
            $browsemenubar .= "<a href=\"#\" accesskey='b' onclick=\"hideTooltip(); document.getElementById('browsepopup').style.visibility='visible';\""
            . "onmouseout=\"hideTooltip()\""
            . "title=\"".$clang->gTview("Display Responses")."\" " 
            . "onmouseover=\"showTooltip(event,'".$clang->gT("Display Responses", "js")."');return false\">"
            ."<img src='$imagefiles/document.png' title='".$clang->gTview("Display Responses")."' "
            . "name='ViewAll' align='left' alt='' /></a>";
            
            $tmp_survlangs = GetAdditionalLanguagesFromSurveyID($surveyid);
            $baselang = GetBaseLanguageFromSurveyID($surveyid);
            $tmp_survlangs[] = $baselang;
            rsort($tmp_survlangs);
            
            $browsemenubar .="<div class=\"langpopup1\" id=\"browsepopup\"><table width=\"100%\"><tr><td>".$clang->gT("Please select a language:")."</td></tr>";
            foreach ($tmp_survlangs as $tmp_lang)
            {
                $browsemenubar .= "<tr><td><a href=\"$scriptname?action=browse&amp;sid=$surveyid&amp;subaction=all&amp;browselang=".$tmp_lang."\" accesskey='d' onclick=\"document.getElementById('browsepopup').style.visibility='hidden';\"><font color=\"#097300\"><b>".getLanguageNameFromCode($tmp_lang,false)."</b></font></a></td></tr>";
            }
            $browsemenubar .= "<tr><td align=\"center\"><a href=\"#\" accesskey='d' onclick=\"document.getElementById('browsepopup').style.visibility='hidden';\"><font color=\"#DF3030\">".$clang->gT("Cancel")."</font></a></td></tr></table></div>";
            
            $tmp_pheight = getPopupHeight();
            $browsemenubar .= "<script type='text/javascript'>document.getElementById('browsepopup').style.height='".$tmp_pheight."px';</script>";

        }            
            
            
	$browsemenubar .= "\t\t\t<a href='$scriptname?action=browse&amp;sid=$surveyid&amp;subaction=all&amp;limit=50&amp;order=desc'" .
			" title=\"".$clang->gTview("Display Last 50 Responses")."\" " .
			"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Display Last 50 Responses", "js")."')\">" .
			"<img name='ViewLast' src='$imagefiles/viewlast.png' title='' alt='' align='left' /></a>\n"
	. "\t\t\t<a href='$scriptname?action=dataentry&amp;sid=$surveyid' onmouseout=\"hideTooltip()\" ".
			" title=\"".$clang->gTview("Dataentry Screen for Survey")."\" " .
			" onmouseover=\"showTooltip(event,'".$clang->gT("Dataentry Screen for Survey", "js")."')\">" .
	  "<img name='DataEntry' src='$imagefiles/dataentry.png' title='' alt='' align='left' /></a>\n"
	. "\t\t\t<a href='$scriptname?action=statistics&amp;sid=$surveyid' "
	."title=\"".$clang->gTview("Get statistics from these responses")."\" "
	."onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Get statistics from these responses", "js")."')\">"
	."<img name='Statistics' src='$imagefiles/statistics.png' title='' alt='' align='left' /></a>\n"
	. "\t\t\t<img src='$imagefiles/seperator.gif' alt=''  align='left' />\n";
	if ($sumrows5['export'] == "1" || $_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
	{
		$browsemenubar .= "\t\t\t<a href='$scriptname?action=exportresults&amp;sid=$surveyid' onmouseout=\"hideTooltip()\" "
		. "title=\"".$clang->gTview("Export Results to Application")."\" "
		. "onmouseover=\"showTooltip(event,'".$clang->gT("Export Results to Application", "js")."')\">"
		. "<img name='Export' src='$imagefiles/export.png' "
		. "title='' alt=''align='left' /></a>\n"
		. "\t\t\t<a href='$scriptname?action=exportspss&amp;sid=$surveyid' onmouseout=\"hideTooltip()\" "
		. "title=\"".$clang->gTview("Export result to a SPSS command file")."\" "
		. "onmouseover=\"showTooltip(event,'".$clang->gT("Export result to a SPSS command file", "js")."')\">"
		. "<img src='$imagefiles/exportspss.png' align='left' "
		. "title='' border='0' alt='". $clang->gT("Export result to a SPSS command file")."' /></a>\n";
	}
	$browsemenubar .= "\t\t\t<a href='$scriptname?action=importoldresponses&amp;sid=$surveyid' onmouseout=\"hideTooltip()\" "
	. " title=\"".$clang->gTview("Import answers from a deactivated survey table")."\" "
	. " onmouseover=\"showTooltip(event,'".$clang->gT("Import answers from a deactivated survey table", "js")."')\" >" .
			"<img name='ImportOld' src='$imagefiles/importold.png' title='' alt=''align='left' /></a>\n"
	. "\t\t\t<img src='$imagefiles/seperator.gif' alt=''  align='left' />\n"
	. "\t\t\t<a href='$scriptname?action=saved&amp;sid=$surveyid' onmouseout=\"hideTooltip()\" "
	. " title=\"".$clang->gTview("View Saved but not submitted Responses")."\" "
	. " onmouseover=\"showTooltip(event,'".$clang->gT("View Saved but not submitted Responses", "js")."')\" >" .
		"<img src='$imagefiles/saved.png' title='' alt='' align='left'  name='BrowseSaved' /></a>\n"
	. "\t\t\t<a href='$scriptname?action=vvimport&amp;sid=$surveyid' onmouseout=\"hideTooltip()\" "
	. " title=\"".$clang->gTview("Import a VV survey file")."\" "
	. " onmouseover=\"showTooltip(event,'".$clang->gT("Import a VV survey file", "js")."')\">\n"
	. "<img src='$imagefiles/importvv.png' align='left' title='' border='0' alt='' /></a>\n";
	if ($sumrows5['export'] == "1" || $_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
	{
		$browsemenubar .= "\t\t\t<a href='$scriptname?action=vvexport&amp;sid=$surveyid' onmouseout=\"hideTooltip()\" " .
		" title=\"".$clang->gTview("Export a VV survey file")."\" " .
		" onmouseover=\"showTooltip(event,'".$clang->gT("Export a VV survey file", "js")."')\">" .
		"<img src='$imagefiles/exportvv.png' align='left' title='' alt='' border='0' /></a>\n";
	}
	$browsemenubar .= "\t\t</td>\n"
	. "\t</tr>\n";
	return $browsemenubar;
}


function returnglobal($stringname)
{

	if (isset($_REQUEST[$stringname]))
		{
		if ($stringname == "sid" || $stringname == "gid" || 
			$stringname == "qid" || $stringname == "tid" || 
			$stringname == "lid" || $stringname == "ugid"|| $stringname == "thisstep" || 
            $stringname == "qaid" || $stringname == "scid")
		{
			return sanitize_int($_REQUEST[$stringname]);
		}
        elseif ($stringname =="lang" || $stringname =="adminlang")
        {
            return sanitize_languagecode($_REQUEST[$stringname]);
        }
        elseif ($stringname =="htmleditormode")
        {
            return sanitize_paranoid_string($_REQUEST[$stringname]);    
        }
		return $_REQUEST[$stringname];
	}
    else return NULL;
}


function sendcacheheaders()
{
	global $embedded;
	if ( $embedded ) return;
    if (!headers_sent())
    {
	    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	    header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	    header("Cache-Control: post-check=0, pre-check=0", false);
	    header("Pragma: no-cache");
	    header('Content-Type: text/html; charset=utf-8');
    }   
}


function getLegitQids($surveyid)
{
	global $dbprefix;
    $surveyid=sanitize_int($surveyid);

	//GET LIST OF LEGIT QIDs FOR TESTING LATER
	$lq = "SELECT DISTINCT qid FROM ".db_table_name('questions')." WHERE sid=$surveyid AND language='".$_SESSION['s_lang']."'";
	$lr = db_execute_num($lq);        //Checked
	return array_merge(array("DUMMY ENTRY"), $lr->GetRows());
}


function returnquestiontitlefromfieldcode($fieldcode)
{
	// Performance optimized	: Nov 13, 2006
	// Performance Improvement	: 37%
	// Optimized By				: swales

	global $dbprefix, $surveyid, $connect, $clang;
	if (!isset($fieldcode)) {return $clang->gT("Preset");}
	if ($fieldcode == "token") {return $clang->gT("Token");}
	if ($fieldcode == "datestamp") {return $clang->gT("Date Stamp");}
	if ($fieldcode == "ipaddr") {return $clang->gT("IP Address");}
	if ($fieldcode == "refurl") {return $clang->gT("Referring URL");}

	//Find matching information;
	$details=arraySearchByKey($fieldcode, createFieldMap($surveyid), "fieldname", 1);

	$fqid=$details['qid'];
	$qq = "SELECT question, other FROM ".db_table_name('questions')." WHERE qid=$fqid AND language='".$_SESSION['s_lang']."'";

	$qr = db_execute_assoc($qq);    //Checked   
	if (!$qr)
	{
		echo "<!-- ERROR Finding Question Name for qid $fqid - $qq - ".htmlspecialchars($connect->ErrorMsg())."! -->";
		$qname="[QID: $fqid]";
	}
	else
	{
		while($qrow=$qr->FetchRow())
		{
			$qname=strip_tags($qrow['question']);
		}
	}
	if (isset($details['aid']) && $details['aid']) //Add answer if necessary (array type questions)
	{
		$qq = "SELECT answer FROM ".db_table_name('answers')." WHERE qid=$fqid AND code='{$details['aid']}' AND language='".$_SESSION['s_lang']."'";
		$qr = db_execute_assoc($qq) or safe_die ("ERROR: ".$connect->ErrorMsg()."<br />$qq"); //Checked
		while($qrow=$qr->FetchRow())
		{
			$qname.=" [".$qrow['answer']."]";
		}
	}
	if (substr($fieldcode, -5) == "other")
	{
		$qname .= " [Other]";
	}
	return $qname;
}


function getsidgidqidaidtype($fieldcode)
{
	// use simple parsing to get {sid}, {gid}
	// and what may be {qid} or {qid}{aid} combination
	list($fsid, $fgid, $fqid) = split("X", $fieldcode);
	$fsid=sanitize_int($fsid);
	$fgid=sanitize_int($fgid);
	if (!$fqid) {$fqid=0;}
    $fqid=sanitize_int($fqid);                  
	// try a true parsing of fieldcode (can separate qid from aid)
	// but fails for type M and type P multiple choice
	// questions because the SESSION fieldcode is combined
	// and we want here to pass only the sidXgidXqid for type M and P
	$fields=arraySearchByKey($fieldcode, createFieldMap($fsid), "fieldname", 1);

	if (count($fields) != 0)
	{
		$aRef['sid']=$fields['sid'];
		$aRef['gid']=$fields['gid'];
		$aRef['qid']=$fields['qid'];
		$aRef['aid']=$fields['aid'];
		$aRef['type']=$fields['type'];
	}
	else
	{
		// either the fielcode doesn't match a question
		// or it is a type M or P question
		$aRef['sid']=$fsid;
		$aRef['gid']=$fgid;
		$aRef['qid']=sanitize_int($fqid);

		$s_lang = GetBaseLanguageFromSurveyID($fsid);
		$query = "SELECT type FROM ".db_table_name('questions')." WHERE qid=".$fqid." AND language='".$s_lang."'";  
		$result = db_execute_assoc($query) or safe_die ("Couldn't get question type - getsidgidqidaidtype() in common.php<br />".$connect->ErrorMsg()); //Checked   
		if ( $result->RecordCount() == 0 )
		{ // question doesn't exist
			return Array();
		}
		else
		{	// certainly is type M or P
			while($row=$result->FetchRow())
			{
				$aRef['type']=$row['type'];
			}		
		}

	}

	//return array("sid"=>$fsid, "gid"=>$fgid, "qid"=>$fqid);
	return $aRef;
}

/*
*
*
*/
function getextendedanswer($fieldcode, $value, $format='')
{
	// Performance optimized	: Nov 13, 2006
	// Performance Improvement	: 36%
	// Optimized By				: swales

	global $dbprefix, $surveyid, $connect, $clang, $action;

	// use Survey base language if s_lang isn't set in _SESSION (when browsing answers)
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	if  (!isset($action) || (isset($action) && $action!='browse') ) 
    {
        if (isset($_SESSION['s_lang'])) $s_lang = $_SESSION['s_lang'];  //This one does not work in admin mode when you browse a particular answer
    }

	//Fieldcode used to determine question, $value used to match against answer code
	//Returns NULL if question type does not suit
	if (substr_count($fieldcode, "X") > 1) //Only check if it looks like a real fieldcode
	{
		$fields=arraySearchByKey($fieldcode, createFieldMap($surveyid), "fieldname", 1);
		//Find out the question type
		$query = "SELECT type, lid, lid1 FROM ".db_table_name('questions')." WHERE qid={$fields['qid']} AND language='".$s_lang."'";
		$result = db_execute_assoc($query) or safe_die ("Couldn't get question type - getextendedanswer() in common.php<br />".$connect->ErrorMsg()); //Checked   
		while($row=$result->FetchRow())
		{
			$this_type=$row['type'];
			$this_lid=$row['lid'];
			$this_lid1=$row['lid1'];
		} // while
		switch($this_type)
		{
			case "L":
			case "!":
			case "O":
			case "^":
			case "I":
			case "R":
			$query = "SELECT code, answer FROM ".db_table_name('answers')." WHERE qid={$fields['qid']} AND code='".$connect->escape($value)."' AND language='".$s_lang."'";
			$result = db_execute_assoc($query) or safe_die ("Couldn't get answer type L - getextendedanswer() in common.php<br />$query<br />".$connect->ErrorMsg()); //Checked   
			while($row=$result->FetchRow())
			{
				$this_answer=$row['answer'];
			} // while
			if ($value == "-oth-")
			{
				$this_answer=$clang->gT("Other");
			}
			break;
			case "M":
			case "J":
			case "P":
			switch($value)
			{
				case "Y": $this_answer=$clang->gT("Yes"); break;
			}
			break;
			case "Y":
			switch($value)
			{
				case "Y": $this_answer=$clang->gT("Yes"); break;
				case "N": $this_answer=$clang->gT("No"); break;
				default: $this_answer=$clang->gT("No answer");
			}
			break;
			case "G":
			switch($value)
			{
				case "M": $this_answer=$clang->gT("Male"); break;
				case "F": $this_answer=$clang->gT("Female"); break;
				default: $this_answer=$clang->gT("No answer");
			}
			break;
			case "C":
			switch($value)
			{
				case "Y": $this_answer=$clang->gT("Yes"); break;
				case "N": $this_answer=$clang->gT("No"); break;
				case "U": $this_answer=$clang->gT("Uncertain"); break;
			}
			break;
			case "E":
			switch($value)
			{
				case "I": $this_answer=$clang->gT("Increase"); break;
				case "D": $this_answer=$clang->gT("Decrease"); break;
				case "S": $this_answer=$clang->gT("Same"); break;
			}
			break;
			case "F":
			case "H":
			case "W":
			case "Z":
			case "1":
			$query = "SELECT title FROM ".db_table_name('labels')." WHERE ((lid=$this_lid) OR (lid=$this_lid1)) AND code='".$connect->escape($value)."' AND language='".$s_lang."'";
			$result = db_execute_assoc($query) or safe_die ("Couldn't get answer type F/H - getextendedanswer() in common.php");   //Checked
			while($row=$result->FetchRow())
			{
				$this_answer=$row['title'];
			} // while
			break;
			default:
			;
		} // switch
	}
	if (isset($this_answer))
	{
		if ($format != 'INSERTANS')
		{
			return $this_answer." [$value]";
		}
		else
		{
			return strip_tags($this_answer);
		}
	}
	else
	{
		return $value;
	}
}

function validate_email($email)
{
	// Create the syntactical validation regular expression
	// Validate the syntax

	// see http://data.iana.org/TLD/tlds-alpha-by-domain.txt
	$maxrootdomainlength = 6;
    return ( ! preg_match("/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.(([0-9]{1,3})|([a-zA-Z]{2,".$maxrootdomainlength."}))$/ix", $email)) ? FALSE : TRUE;  
}

function validate_templatedir($templatename)
{
    global $publicdir, $defaulttemplate;
    if (is_dir("$publicdir/templates/{$templatename}/"))
    {
         return $templatename;
    }
    else 
    {
         return $defaulttemplate;

    }     
}


function crlf_lineendings($text)
{
	$text=str_replace("\r\n", "~~~~", $text); //First replace any good line endings with ~~~~
	$text=str_replace("\n", "~~~~", $text); //Then replace any solitary \n's with ~~~~
	$text=str_replace("\r", "~~~~", $text); //Then replace any solitary \r's with ~~~~
	$text=str_replace("~~~~", "\r\n", $text); //Finally replace all ~~~~'s with \r\n
	return $text;
}



//This function generates an array containing the fieldcode, and matching data in the same
//order as the activate script
// @param: $force_refresh  - Forces to really refresh the array, not just take the session copy

function createFieldMap($surveyid, $style="null", $force_refresh=false) {

	global $dbprefix, $connect, $globalfieldmap, $clang;
    $surveyid=sanitize_int($surveyid);
	//checks to see if fieldmap has already been built for this page.
	if (isset($globalfieldmap) && $globalfieldmap[0] == $surveyid  && $force_refresh==false) {
		return $globalfieldmap[1];
	}

	//Check for any additional fields for this survey and create necessary fields (token and datestamp and ipaddr)
	$pquery = "SELECT private, datestamp, ipaddr, refurl FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
	$presult=db_execute_assoc($pquery); //Checked
	$counter=0;
	while($prow=$presult->FetchRow())
	{
		if ($prow['private'] == "N")
		{
			$fieldmap[]=array("fieldname"=>"token", "type"=>"", "sid"=>$surveyid, "gid"=>"", "qid"=>"", "aid"=>"");
			if ($style == "full")
			{
				$fieldmap[$counter]['title']="";
				$fieldmap[$counter]['question']="token";
				$fieldmap[$counter]['group_name']="";
			}
			$counter++;
		}
		if ($prow['datestamp'] == "Y")
		{
			$fieldmap[]=array("fieldname"=>"datestamp", "type"=>"", "sid"=>$surveyid, "gid"=>"", "qid"=>"", "aid"=>"");
			if ($style == "full")
			{
				$fieldmap[$counter]['title']="";
				$fieldmap[$counter]['question']="datestamp";
				$fieldmap[$counter]['group_name']="";
			}
			$counter++;
		}
		if ($prow['ipaddr'] == "Y")
		{
			$fieldmap[]=array("fieldname"=>"ipaddr", "type"=>"", "sid"=>$surveyid, "gid"=>"", "qid"=>"", "aid"=>"");
			if ($style == "full")
			{
				$fieldmap[$counter]['title']="";
				$fieldmap[$counter]['question']="ipaddr";
				$fieldmap[$counter]['group_name']="";
			}
			$counter++;
		}
		// Add 'refurl' to fieldmap.
		if ($prow['refurl'] == "Y")
		{
			$fieldmap[]=array("fieldname"=>"refurl", "type"=>"", "sid"=>$surveyid, "gid"=>"", "qid"=>"", "aid"=>"");
			if ($style == "full")
			{
				$fieldmap[$counter]['title']="";
				$fieldmap[$counter]['question']="refurl";
				$fieldmap[$counter]['group_name']="";
			}
			$counter++;
		}

	}
	//Get list of questions
	$s_lang = GetBaseLanguageFromSurveyID($surveyid);
	$aquery = "SELECT * FROM ".db_table_name('questions').", ".db_table_name('groups')
	." WHERE ".db_table_name('questions').".gid=".db_table_name('groups').".gid AND "
	.db_table_name('questions').".sid=$surveyid AND "
	.db_table_name('questions').".language='{$s_lang}' AND "
	.db_table_name('groups').".language='{$s_lang}' "
	." ORDER BY {$dbprefix}groups.group_order, title";
	$aresult = db_execute_assoc($aquery) or safe_die ("Couldn't get list of questions in createFieldMap function.<br />$query<br />".$connect->ErrorMsg()); //Checked
	while ($arow=$aresult->FetchRow()) //With each question, create the appropriate field(s)
	{
		if ($arow['type'] != "M" && $arow['type'] != "A" && $arow['type'] != "B" &&
		$arow['type'] !="C" && $arow['type'] != "E" && $arow['type'] != "F" &&
		$arow['type'] != "H" && $arow['type'] !="P" && $arow['type'] != "R" &&
		$arow['type'] != "Q" && $arow['type'] != "J" && $arow['type'] != "K" && 
		$arow['type'] != "^" && $arow['type'] != "1")
		{
			$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}", "type"=>"{$arow['type']}", "sid"=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>"");
			if ($style == "full")
			{
				$fieldmap[$counter]['title']=$arow['title'];
				$fieldmap[$counter]['question']=$arow['question'];
				$fieldmap[$counter]['group_name']=$arow['group_name'];
			}
			$counter++;
			switch($arow['type'])
			{
				case "L":  //RADIO LIST
				case "!":  //DROPDOWN LIST
				case "W":  //FLEXIBLE DROPDOWN LIST
				case "Z":  //FLEXIBLE RADIO LIST
				if ($arow['other'] == "Y")
				{
					$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}other",
					"type"=>$arow['type'],
					"sid"=>$surveyid,
					"gid"=>$arow['gid'],
					"qid"=>$arow['qid'],
					"aid"=>"other");
					// dgk bug fix line above. aid should be set to "other" for export to append to the field name in the header line.
					if ($style == "full")
					{
						$fieldmap[$counter]['title']=$arow['title'];
						$fieldmap[$counter]['question']=$arow['question']."[".$clang->gT("Other")."]";
						$fieldmap[$counter]['group_name']=$arow['group_name'];
					}
					$counter++;
				}
				break;
				case "O": //DROPDOWN LIST WITH COMMENT
				$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}comment",
				"type"=>$arow['type'],
				"sid"=>$surveyid,
				"gid"=>$arow['gid'],
				"qid"=>$arow['qid'],
				"aid"=>"comment");
				// dgk bug fix line below. aid should be set to "comment" for export to append to the field name in the header line. Also needed set the type element correctly.
				if ($style == "full")
				{
					$fieldmap[$counter]['title']=$arow['title'];
					$fieldmap[$counter]['question']=$arow['question']."[".$clang->gT("Comment")."]";
					$fieldmap[$counter]['group_name']=$arow['group_name'];
				}
				$counter++;
				break;
			}
		}
		elseif ($arow['type'] == "M" || $arow['type'] == "A" || $arow['type'] == "B" ||
		$arow['type'] == "C" || $arow['type'] == "E" || $arow['type'] == "F" ||
		$arow['type'] == "H" || $arow['type'] == "P" || $arow['type'] == "^" || $arow['type'] == "J")
		{
			//MULTI ENTRY
			$abquery = "SELECT ".db_table_name('answers').".*, ".db_table_name('questions').".other\n"
			." FROM ".db_table_name('answers').", ".db_table_name('questions')
			." WHERE sid=$surveyid AND ".db_table_name('answers').".qid=".db_table_name('questions').".qid "
			. "AND ".db_table_name('questions').".language='".$s_lang."'"
			." AND ".db_table_name('answers').".language='".$s_lang."'"
			." AND ".db_table_name('questions').".qid={$arow['qid']} "
			." ORDER BY ".db_table_name('answers').".sortorder, ".db_table_name('answers').".answer";
			$abresult=db_execute_assoc($abquery) or safe_die ("Couldn't get list of answers in createFieldMap function (case M/A/B/C/E/F/H/P)<br />$abquery<br />".$connect->ErrorMsg());  //Checked
			while ($abrow=$abresult->FetchRow())
			{
				$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}", "type"=>$arow['type'], "sid"=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>$abrow['code']);
				if ($abrow['other']=="Y") {$alsoother="Y";}
				if ($style == "full")
				{
					$fieldmap[$counter]['title']=$arow['title'];
					$fieldmap[$counter]['question']=$arow['question']."[".$abrow['answer']."]";
					$fieldmap[$counter]['group_name']=$arow['group_name'];
				}
				$counter++;
				if ($arow['type'] == "P")
				{
					$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}comment", "type"=>$arow['type'], "sid"=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>"comment");
					if ($style == "full")
					{
						$fieldmap[$counter]['title']=$arow['title'];
						$fieldmap[$counter]['question']=$arow['question']."[comment]";
						$fieldmap[$counter]['group_name']=$arow['group_name'];
					}
					$counter++;
				}
			}
			if ((isset($alsoother) && $alsoother=="Y") && ($arow['type']=="M" || $arow['type']=="P"))
			{
				$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}other", "type"=>$arow['type'], "sid"=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>"other");
				if ($style == "full")
				{
					$fieldmap[$counter]['title']=$arow['title'];
					$fieldmap[$counter]['question']=$arow['question']."[".$clang->gT("Other")."]";
					$fieldmap[$counter]['group_name']=$arow['group_name'];
				}
				$counter++;
				if ($arow['type']=="P")
				{
					$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}othercomment", "type"=>$arow['type'], "sid"=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>"othercomment");
					if ($style == "full")
					{
						$fieldmap[$counter]['title']=$arow['title'];
						$fieldmap[$counter]['question']=$arow['question']."[".$clang->gT("Other")."comment]";
						$fieldmap[$counter]['group_name']=$arow['group_name'];
					}
					$counter++;
				}
			}
		}
		elseif ($arow['type'] == "Q" || $arow['type'] == "K")
		{
			$abquery = "SELECT ".db_table_name('answers').".*, ".db_table_name('questions').".other FROM "
			.db_table_name('answers').", ".db_table_name('questions')." WHERE sid=$surveyid AND "
			.db_table_name('answers').".qid=".db_table_name('questions').".qid AND "
			.db_table_name('answers').".language='".$s_lang."' AND "
			.db_table_name('questions').".language='".$s_lang."' AND "
			.db_table_name('questions').".qid={$arow['qid']} ORDER BY ".db_table_name('answers').".sortorder, ".db_table_name('answers').".answer";
			$abresult=db_execute_assoc($abquery) or safe_die ("Couldn't get list of answers in createFieldMap function (type Q)<br />$abquery<br />".$connect->ErrorMsg()); //Checked
			while ($abrow=$abresult->FetchRow())
			{
				$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}", "type"=>$arow['type'], "sid"=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>$abrow['code']);
				if ($style == "full")
				{
					$fieldmap[$counter]['title']=$arow['title'];
					$fieldmap[$counter]['question']=$arow['question']."[".$abrow['answer']."]";
					$fieldmap[$counter]['group_name']=$arow['group_name'];
				}
				$counter++;
			}
		}
		elseif ($arow['type'] == "1")
		{
			$abquery = "SELECT a.*, q.other, q.lid, q.lid1 FROM {$dbprefix}answers as a, {$dbprefix}questions as q"
                       ." WHERE a.qid=q.qid AND sid=$surveyid AND q.qid={$arow['qid']} "
                       ." AND a.language='".$s_lang. "' "
                       ." AND q.language='".$s_lang. "' "
                       ." ORDER BY a.sortorder, a.answer";
			$abresult=db_execute_assoc($abquery) or safe_die ("Couldn't get perform answers query<br />$abquery<br />".$connect->ErrorMsg());    //Checked    
			$abcount=$abresult->RecordCount();
			while ($abrow=$abresult->FetchRow())
			{
				$abmultiscalequery = "SELECT l.* FROM {$dbprefix}questions as q, {$dbprefix}labels as l, {$dbprefix}answers as a"
					     ." WHERE a.qid=q.qid AND sid=$surveyid AND q.qid={$arow['qid']} "
	                     ." AND l.lid=q.lid AND sid=$surveyid AND q.qid={$arow['qid']}"
                         ." AND l.language='".$s_lang. "' "
                         ." AND a.language='".$s_lang. "' "
                         ." AND q.language='".$s_lang. "' ";
                        
				$abmultiscaleresult=db_execute_assoc($abmultiscalequery) or safe_die ("Couldn't get perform answers query<br />$abquery<br />".$connect->ErrorMsg()); //Checked
				$abmultiscalecount=$abmultiscaleresult->RecordCount();
				//if ($abmultiscalecount>0)
				if ($abmultiscaleresultrow=$abmultiscaleresult->FetchRow())
					{
 					$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}#0", "type"=>$arow['type'], "sid"=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>$abrow['code'], "lid"=>$abmultiscaleresultrow["lid"], "lid1"=>$arow["lid1"]);
     					if ($style == "full")
						{
							$fieldmap[$counter]['title']=$arow['title'];
							$fieldmap[$counter]['question']=$arow['question']."[".$abrow['answer']."]";
							$fieldmap[$counter]['group_name']=$arow['group_name'];
						}
     					
     					$counter++;	
	
					} 
				// multi-scale
				$abmultiscalequery = "SELECT l.* FROM {$dbprefix}questions as q, {$dbprefix}labels as l, {$dbprefix}answers as a"
					     ." WHERE a.qid=q.qid AND sid=$surveyid AND q.qid={$arow['qid']} "
	                     ." AND l.lid=q.lid1 AND sid=$surveyid AND q.qid={$arow['qid']}"
                       ." AND l.language='".$s_lang. "' "
                       ." AND a.language='".$s_lang. "' "
                       ." AND q.language='".$s_lang. "' ";
                       
				$abmultiscaleresult=db_execute_assoc($abmultiscalequery) or safe_die ("Couldn't get perform answers query<br />$abquery<br />".$connect->ErrorMsg()); //Checked
				$abmultiscalecount=$abmultiscaleresult->RecordCount();
				if ($abmultiscaleresultrow=$abmultiscaleresult->FetchRow())
				{
 					$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}#1", "type"=>$arow['type'], "sid"=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>$abrow['code'], "lid"=>$abmultiscaleresultrow["lid"], "lid1"=>$arow["lid1"]);
					if ($style == "full")
					{
						$fieldmap[$counter]['title']=$arow['title'];
						$fieldmap[$counter]['question']=$arow['question']."[".$abrow['answer']."]";
						$fieldmap[$counter]['group_name']=$arow['group_name'];
					}
					
				$counter++;				
			}
		}
		}
		
		elseif ($arow['type'] == "R")
		{
			//MULTI ENTRY
			$abquery = "SELECT ".db_table_name('answers').".*, ".db_table_name('questions').".other FROM "
			.db_table_name('answers').", ".db_table_name('questions')." WHERE "
			.db_table_name('answers').".qid=".db_table_name('questions').".qid AND sid=$surveyid AND "
			.db_table_name('answers').".language='".$s_lang."' AND "
			.db_table_name('questions').".language='".$s_lang."' AND"
			.db_table_name('questions').".qid={$arow['qid']} ORDER BY ".db_table_name('answers')
			.".sortorder, ".db_table_name('answers').".answer";
			$abresult=db_execute_assoc($abquery) or safe_die ("Couldn't get list of answers in createFieldMap function (type R)<br />$abquery<br />".$connect->ErrorMsg()); //Checked
			$abcount=$abresult->RecordCount();
			for ($i=1; $i<=$abcount; $i++)
			{
				$fieldmap[]=array("fieldname"=>"{$arow['sid']}X{$arow['gid']}X{$arow['qid']}$i", "type"=>$arow['type'], "sid"=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>$i);
				if ($style == "full")
				{
					$fieldmap[$counter]['title']=$arow['title'];
					$fieldmap[$counter]['question']=$arow['question']."[$i]";
					$fieldmap[$counter]['group_name']=$arow['group_name'];
				}
				$counter++;
			}
		}
	}
	if (isset($fieldmap)) {
		$globalfieldmap[0] = $surveyid;
		$globalfieldmap[1] = $fieldmap;

		return $fieldmap;
	}
}

function arraySearchByKey($needle, $haystack, $keyname, $maxanswers="") {
	$output=array();
	foreach($haystack as $hay) {
		if (array_key_exists($keyname, $hay)) {
			if ($hay[$keyname] == $needle) {
				if ($maxanswers == 1) {
					return $hay;
				} else {
					$output[]=$hay;
				}
			}
		}
	}
	return $output;
}

function templatereplace($line)
{
	// Performance optimized	: Nov 10, 2006
	// Performance Improvement	: 49%
	// Optimized By				: swales

	global $surveylist, $sitename, $clienttoken;
	global $thissurvey, $imagefiles, $defaulttemplate;
	global $percentcomplete, $move;
	global $groupname, $groupdescription, $question;
	global $questioncode, $answer, $navigator;
	global $help, $totalquestions, $surveyformat;
	global $completed, $register_errormsg;
	global $notanswered, $privacy, $surveyid;
	global $publicurl, $templatedir, $token;
	global $assessments, $s_lang;
	global $errormsg, $clang;
	global $saved_id;
	global $totalBoilerplatequestions, $relativeurl;
    global $languagechanger;    
    global $printoutput, $captchapath, $loadname;
                     
	if (stripos ($line,"</head>"))
	{
		$line=str_ireplace("</head>",
			"<script type=\"text/javascript\" src=\"scripts/surveyRuntime.js\">\n"
			."</script>\n"
			."</head>", $line);
	}


	// If there are non-bracketed replacements to be made do so above this line.
	// Only continue in this routine if there are bracketed items to replace {}
	if (strpos($line, "{") === false) {
		return $line;
	}

	if (strpos($line, "{SURVEYLISTHEADING}") !== false) $line=str_replace("{SURVEYLISTHEADING}", $surveylist['listheading'], $line);
	if (strpos($line, "{SURVEYLIST}") !== false) $line=str_replace("{SURVEYLIST}", $surveylist['list'], $line);
	if (strpos($line, "{NOSURVEYID}") !== false) $line=str_replace("{NOSURVEYID}", $surveylist['nosid'], $line);
	if (strpos($line, "{SURVEYCONTACT}") !== false) $line=str_replace("{SURVEYCONTACT}", $surveylist['contact'], $line);
	
	if (strpos($line, "{SITENAME}") !== false) $line=str_replace("{SITENAME}", $sitename, $line);
	
	if (strpos($line, "{SURVEYLIST}") !== false) $line=str_replace("{SURVEYLIST}", $surveylist, $line);
	if (strpos($line, "{CHECKJAVASCRIPT}") !== false) $line=str_replace("{CHECKJAVASCRIPT}", "<noscript><span class='warningjs'>".$clang->gT("Caution: JavaScript execution is disabled in your browser. You may not be able to answer all questions in this survey. Please, verify your browser parameters.")."</span></noscript>", $line);
    if (strpos($line, "{ANSWERTABLE}") !== false) $line=str_replace("{ANSWERTABLE}", $printoutput, $line);
	if (strpos($line, "{SURVEYNAME}") !== false) $line=str_replace("{SURVEYNAME}", $thissurvey['name'], $line);
	if (strpos($line, "{SURVEYDESCRIPTION}") !== false) $line=str_replace("{SURVEYDESCRIPTION}", $thissurvey['description'], $line);
	if (strpos($line, "{WELCOME}") !== false) $line=str_replace("{WELCOME}", $thissurvey['welcome'], $line);
    if (strpos($line, "{LANGUAGECHANGER}") !== false) $line=str_replace("{LANGUAGECHANGER}", $languagechanger, $line);  
	if (strpos($line, "{PERCENTCOMPLETE}") !== false) $line=str_replace("{PERCENTCOMPLETE}", $percentcomplete, $line);
	if (strpos($line, "{GROUPNAME}") !== false) $line=str_replace("{GROUPNAME}", $groupname, $line);
	if (strpos($line, "{GROUPDESCRIPTION}") !== false) $line=str_replace("{GROUPDESCRIPTION}", $groupdescription, $line);
	if (strpos($line, "{QUESTION}") !== false) $line=str_replace("{QUESTION}", $question, $line);
	if (strpos($line, "{QUESTION_CODE}") !== false) $line=str_replace("{QUESTION_CODE}", $questioncode, $line);
	if (strpos($line, "{ANSWER}") !== false) $line=str_replace("{ANSWER}", $answer, $line);
	$totalquestionsAsked = $totalquestions - $totalBoilerplatequestions;
	if ($totalquestionsAsked < 1)
	{
		if (strpos($line, "{THEREAREXQUESTIONS}") !== false) $line=str_replace("{THEREAREXQUESTIONS}", $clang->gT("There are no questions in this survey"), $line); //Singular
	}
	if ($totalquestionsAsked == 1)
	{
		if (strpos($line, "{THEREAREXQUESTIONS}") !== false) $line=str_replace("{THEREAREXQUESTIONS}", $clang->gT("There is 1 question in this survey"), $line); //Singular
	}	
	else
	{
		if (strpos($line, "{THEREAREXQUESTIONS}") !== false) $line=str_replace("{THEREAREXQUESTIONS}", $clang->gT("There are {NUMBEROFQUESTIONS} questions in this survey."), $line); //Note this line MUST be before {NUMBEROFQUESTIONS}
	}
	if (strpos($line, "{NUMBEROFQUESTIONS}") !== false) $line=str_replace("{NUMBEROFQUESTIONS}", $totalquestionsAsked, $line);

	if (strpos($line, "{TOKEN}") !== false) {
		if (isset($token)) {
			$line=str_replace("{TOKEN}", $token, $line);
		}
		elseif (isset($clienttoken)) {
			$line=str_replace("{TOKEN}", htmlentities($clienttoken,ENT_QUOTES,'UTF-8'), $line);
		}
		else {
			$line=str_replace("{TOKEN}",'', $line);
		}
	}

	if (strpos($line, "{SID}") !== false) $line=str_replace("{SID}", $surveyid, $line);
	if ($help) {
		if (strpos($line, "{QUESTIONHELP}") !== false) $line=str_replace("{QUESTIONHELP}", "<img src='".$imagefiles."/help.gif' alt='Help' align='left' />".$help, $line);
		if (strpos($line, "{QUESTIONHELPPLAINTEXT}") !== false) $line=str_replace("{QUESTIONHELPPLAINTEXT}", strip_tags(addslashes($help)), $line);
	}
	else
	{
		if (strpos($line, "{QUESTIONHELP}") !== false) $line=str_replace("{QUESTIONHELP}", $help, $line);
		if (strpos($line, "{QUESTIONHELPPLAINTEXT}") !== false) $line=str_replace("{QUESTIONHELPPLAINTEXT}", strip_tags(addslashes($help)), $line);
	}
	while (strpos($line, "{INSERTANS:") !== false)
	{
		$answreplace=substr($line, strpos($line, "{INSERTANS:"), strpos($line, "}", strpos($line, "{INSERTANS:"))-strpos($line, "{INSERTANS:")+1);
		$answreplace2=substr($answreplace, 11, strpos($answreplace, "}", strpos($answreplace, "{INSERTANS:"))-11);
		$answreplace3=strip_tags(retrieve_Answer($answreplace2));
		$line=str_replace($answreplace, $answreplace3, $line);
	}
	if (strpos($line, "{NAVIGATOR}") !== false) $line=str_replace("{NAVIGATOR}", $navigator, $line);
	if (strpos($line, "{SUBMITBUTTON}") !== false) {
		$submitbutton="<input class='submit' type='submit' value=' ".$clang->gT("Submit")." ' name='move2' onclick=\"javascript:document.limesurvey.move.value = 'movesubmit';\" />";
		$line=str_replace("{SUBMITBUTTON}", $submitbutton, $line);
	}
	if (strpos($line, "{COMPLETED}") !== false) $line=str_replace("{COMPLETED}", $completed, $line);
	if (strpos($line, "{URL}") !== false) {
		if ($thissurvey['url']!=""){$linkreplace="<a href='{$thissurvey['url']}'>{$thissurvey['urldescrip']}</a>";}
		else {$linkreplace="";}
		$line=str_replace("{URL}", $linkreplace, $line);            
        $line=str_replace("{SAVEDID}",$saved_id, $line);     // to activate the SAVEDID in the END URL 
        if (isset($clienttoken)) {$token=$clienttoken;} else {$token='';}
		$line=str_replace("{TOKEN}",urlencode($token), $line);			// to activate the TOKEN in the END URL 
        $line=str_replace("{SID}", $surveyid, $line);       // to activate the SID in the RND URL
	}
	if (strpos($line, "{PRIVACY}") !== false) 
    {
        $line=str_replace("{PRIVACY}", $privacy, $line);
    }
	if (strpos($line, "{PRIVACYMESSAGE}") !== false) 
    {
        $line=str_replace("{PRIVACYMESSAGE}", "<strong><i>".$clang->gT("A Note On Privacy")."</i></strong><br />".$clang->gT("This survey is anonymous.")."<br />".$clang->gT("The record kept of your survey responses does not contain any identifying information about you unless a specific question in the survey has asked for this. If you have responded to a survey that used an identifying token to allow you to access the survey, you can rest assured that the identifying token is not kept with your responses. It is managed in a separate database, and will only be updated to indicate that you have (or haven't) completed this survey. There is no way of matching identification tokens with survey responses in this survey."), $line);
    }
	if (strpos($line, "{CLEARALL}") !== false) 	{
		$clearall = "\t\t\t\t\t<div class='clearall'>"
		. "<a href='{$_SERVER['PHP_SELF']}?sid=$surveyid&amp;move=clearall&amp;lang=".$_SESSION['s_lang'];
		if (returnglobal('token'))
		{
			$clearall .= "&amp;token=".urlencode(trim(sanitize_xss_string(strip_tags(returnglobal('token')))));
		}
		$clearall .="' onclick='return confirm(\""
		. $clang->gT("Are you sure you want to clear all your responses?")."\")'>["
		. $clang->gT("Exit and Clear Survey")."]</a></div>\n";


		$line=str_replace("{CLEARALL}", $clearall, $line);
	}
	// --> START NEW FEATURE - SAVE
	if (strpos($line, "{DATESTAMP}") !== false) {
		if (isset($_SESSION['datestamp'])) {
			$line=str_replace("{DATESTAMP}", $_SESSION['datestamp'], $line);
		}
		else {
			$line=str_replace("{DATESTAMP}", "-", $line);
		}
	}
	// <-- END NEW FEATURE - SAVE

	if (strpos($line, "{SAVE}") !== false)	{
		//Set up save/load feature
		if ($thissurvey['allowsave'] == "Y")
		{
			// Find out if the user has any saved data
			
			if (!isset($_SESSION['step']) || !$_SESSION['step'])  //First page, show LOAD
			{
				$saveall = "<input type='submit' name='loadall' value='".$clang->gT("Load Unfinished Survey")."' class='saveall' ". (($thissurvey['active'] != "Y")? "disabled='disabled'":"") ."/>";
			}
			elseif (isset($_SESSION['scid']) && (isset($move) && $move == "movelast"))  //Already saved and on Submit Page, dont show Save So Far button
			{
				$saveall="";
			}
			else
			{
				$saveall= "<input type='button' name='saveallbtn' value='".$clang->gT("Resume Later")."' class='saveall' onclick=\"javascript:document.limesurvey.move.value = this.value;addHiddenField(document.getElementById('limesurvey'),'saveall',this.value);document.getElementById('limesurvey').submit();\" ". (($thissurvey['active'] != "Y")? "disabled='disabled'":"") ."/>";  // Show Save So Far button
			}
		}
		else
		{
			$saveall="";
		}
		$line=str_replace("{SAVE}", $saveall, $line);
	}
	if (strpos($line, "{TEMPLATEURL}") !== false) {
        
    
		if ($thissurvey['templatedir']) 
		{
			$templateurl="$publicurl/templates/".validate_templatedir($thissurvey['templatedir'])."/";
		}
        else  {
            $templateurl="$publicurl/templates/{$defaulttemplate}/";
        }
		$line=str_replace("{TEMPLATEURL}", $templateurl, $line);
	}
	if (strpos($line, "{SUBMITCOMPLETE}") !== false) $line=str_replace("{SUBMITCOMPLETE}", "<strong>".$clang->gT("Thank You!")."<br /><br />".$clang->gT("You have completed answering the questions in this survey.")."</strong><br /><br />".$clang->gT("Click on 'Submit' now to complete the process and save your answers."), $line);
	if (strpos($line, "{SUBMITREVIEW}") !== false) {
		if (isset($thissurvey['allowprev']) && $thissurvey['allowprev'] == "N") {
			$strreview = "";
		}
		else {
			$strreview=$clang->gT("If you want to check any of the answers you have made, and/or change them, you can do that now by clicking on the [<< prev] button and browsing through your responses.");
		}
		$line=str_replace("{SUBMITREVIEW}", $strreview, $line);
	}
	if (isset($_SESSION['thistoken']))
	{
		if (strpos($line, "{TOKEN:FIRSTNAME}") !== false) $line=str_replace("{TOKEN:FIRSTNAME}", $_SESSION['thistoken']['firstname'], $line);
		if (strpos($line, "{TOKEN:LASTNAME}") !== false) $line=str_replace("{TOKEN:LASTNAME}", $_SESSION['thistoken']['lastname'], $line);
		if (strpos($line, "{TOKEN:EMAIL}") !== false) $line=str_replace("{TOKEN:EMAIL}", $_SESSION['thistoken']['email'], $line);
		if (strpos($line, "{TOKEN:ATTRIBUTE_1}") !== false) $line=str_replace("{TOKEN:ATTRIBUTE_1}", $_SESSION['thistoken']['attribute_1'], $line);
		if (strpos($line, "{TOKEN:ATTRIBUTE_2}") !== false) $line=str_replace("{TOKEN:ATTRIBUTE_2}", $_SESSION['thistoken']['attribute_2'], $line);
	}
	else
	{
		if (strpos($line, "{TOKEN:FIRSTNAME}") !== false) $line=str_replace("{TOKEN:FIRSTNAME}", "", $line);
		if (strpos($line, "{TOKEN:LASTNAME}") !== false) $line=str_replace("{TOKEN:LASTNAME}", "", $line);
		if (strpos($line, "{TOKEN:EMAIL}") !== false) $line=str_replace("{TOKEN:EMAIL}", "", $line);
		if (strpos($line, "{TOKEN:ATTRIBUTE_1}") !== false) $line=str_replace("{TOKEN:ATTRIBUTE_1}", "", $line);
		if (strpos($line, "{TOKEN:ATTRIBUTE_2}") !== false) $line=str_replace("{TOKEN:ATTRIBUTE_2}", "", $line);
	}

	if (strpos($line, "{ANSWERSCLEARED}") !== false) $line=str_replace("{ANSWERSCLEARED}", $clang->gT("Answers Cleared"), $line);
	if (strpos($line, "{RESTART}") !== false)
	{
		if ($thissurvey['active'] == "N") 
		{
			$line=str_replace("{RESTART}",  "<a href='{$_SERVER['PHP_SELF']}?sid=$surveyid&amp;newtest=Y&amp;lang=".$s_lang."'>".$clang->gT("Restart this Survey")."</a>", $line);
		} else {
			$restart_extra = "";
			$restart_token = returnglobal('token');
			if (!empty($restart_token)) $restart_extra .= "&amp;token=".urlencode($restart_token);
			if (!empty($_GET['lang'])) $restart_extra .= "&amp;lang=".returnglobal('lang');
			$line=str_replace("{RESTART}",  "<a href='{$_SERVER['PHP_SELF']}?sid=$surveyid".$restart_extra."'>".$clang->gT("Restart this Survey")."</a>", $line);
		}
	}
	if (strpos($line, "{CLOSEWINDOW}") !== false) $line=str_replace("{CLOSEWINDOW}", "<a href='javascript:%20self.close()'>".$clang->gT("Close this Window")."</a>", $line);
	if (strpos($line, "{SAVEERROR}") !== false) $line=str_replace("{SAVEERROR}", $errormsg, $line);
	if (strpos($line, "{SAVEHEADING}") !== false) $line=str_replace("{SAVEHEADING}", $clang->gT("Save Your Unfinished Survey"), $line);
	if (strpos($line, "{SAVEMESSAGE}") !== false) $line=str_replace("{SAVEMESSAGE}", $clang->gT("Enter a name and password for this survey and click save below.")."<br />\n".$clang->gT("Your survey will be saved using that name and password, and can be completed later by logging in with the same name and password.")."<br /><br />\n".$clang->gT("If you give an email address, an email containing the details will be sent to you."), $line);
	if (strpos($line, "{RETURNTOSURVEY}") !== false) 
	{
		$savereturn = "<a href='$relativeurl/index.php?sid=$surveyid";
		if (returnglobal('token'))
		{
			$savereturn.= "&amp;token=".urlencode(trim(sanitize_xss_string(strip_tags(returnglobal('token')))));
		}
 		$savereturn .= "'>".$clang->gT("Return To Survey")."</a>";
		$line=str_replace("{RETURNTOSURVEY}", $savereturn, $line);
	}	
	if (strpos($line, "{SAVEFORM}") !== false) {
		//SAVE SURVEY DETAILS
		$saveform = "<table><tr><td align='right'>".$clang->gT("Name").":</td><td><input type='text' name='savename' value='";
		if (isset($_POST['savename'])) {$saveform .= html_escape(auto_unescape($_POST['savename']));}
		$saveform .= "' /></td></tr>\n"
		. "<tr><td align='right'>".$clang->gT("Password").":</td><td><input type='password' name='savepass' value='";
		if (isset($_POST['savepass'])) {$saveform .= html_escape(auto_unescape($_POST['savepass']));}
		$saveform .= "' /></td></tr>\n"
		. "<tr><td align='right'>".$clang->gT("Repeat Password").":</td><td><input type='password' name='savepass2' value='";
		if (isset($_POST['savepass2'])) {$saveform .= html_escape(auto_unescape($_POST['savepass2']));}
		$saveform .= "' /></td></tr>\n"
		. "<tr><td align='right'>".$clang->gT("Your Email").":</td><td><input type='text' name='saveemail' value='";
		if (isset($_POST['saveemail'])) {$saveform .= html_escape(auto_unescape($_POST['saveemail']));}
		$saveform .= "' /></td></tr>\n";
        if (function_exists("ImageCreate") && captcha_enabled('saveandloadscreen',$thissurvey['usecaptcha']))
        {
		    $saveform .="<tr><td align='right'>".$clang->gT("Security Question").":</td><td><table><tr><td valign='middle'><img src='{$captchapath}verification.php' alt='' /></td><td valign='middle'><input type='text' size='5' maxlength='3' name='loadsecurity' value='' /></td></tr></table></td></tr>\n";
        }
		$saveform .= "<tr><td align='right'></td><td></td></tr>\n"
		. "<tr><td></td><td><input type='submit' name='savesubmit' value='".$clang->gT("Save Now")."'></td></tr>\n"
		. "</table>";
		$line=str_replace("{SAVEFORM}", $saveform, $line);
	}
	if (strpos($line, "{LOADERROR}") !== false) $line=str_replace("{LOADERROR}", $errormsg, $line);
	if (strpos($line, "{LOADHEADING}") !== false) $line=str_replace("{LOADHEADING}", $clang->gT("Load A Previously Saved Survey"), $line);
	if (strpos($line, "{LOADMESSAGE}") !== false) $line=str_replace("{LOADMESSAGE}", $clang->gT("You can load a survey that you have previously saved from this screen.")."<br />".$clang->gT("Type in the 'name' you used to save the survey, and the password.")."<br />", $line);
	if (strpos($line, "{LOADFORM}") !== false) {
		//LOAD SURVEY DETAILS
		$loadform = "<table><tr><td align='right'>".$clang->gT("Saved name").":</td><td><input type='text' name='loadname' value='";
		if ($loadname) {$loadform .= html_escape(auto_unescape($loadname));}
		$loadform .= "' /></td></tr>\n"
		. "<tr><td align='right'>".$clang->gT("Password").":</td><td><input type='password' name='loadpass' value='";
		if (isset($loadpass)) { $loadform .= html_escape(auto_unescape($loadpass)); }
		$loadform .= "' /></td></tr>\n";
        if (function_exists("ImageCreate") && captcha_enabled('saveandloadscreen',$thissurvey['usecaptcha']))
        {
            $loadform .="<tr><td align='right'>".$clang->gT("Security Question").":</td><td><table><tr><td valign='middle'><img src='{$captchapath}verification.php' alt='' /></td><td valign='middle'><input type='text' size='5' maxlength='3' name='loadsecurity' value='' alt=''/></td></tr></table></td></tr>\n";
        }

        
		$loadform .="<tr><td align='right'></td><td></td></tr>\n"
		. "<tr><td></td><td><input type='submit' value='".$clang->gT("Load Now")."' /></td></tr></table>\n";
		$line=str_replace("{LOADFORM}", $loadform, $line);
	}
	//REGISTER SURVEY DETAILS
	if (strpos($line, "{REGISTERERROR}") !== false) $line=str_replace("{REGISTERERROR}", $register_errormsg, $line);
	if (strpos($line, "{REGISTERMESSAGE1}") !== false) $line=str_replace("{REGISTERMESSAGE1}", $clang->gT("You must be registered to complete this survey"), $line);
	if (strpos($line, "{REGISTERMESSAGE2}") !== false) $line=str_replace("{REGISTERMESSAGE2}", $clang->gT("You may register for this survey if you wish to take part.")."<br />\n".$clang->gT("Enter your details below, and an email containing the link to participate in this survey will be sent immediately."), $line);
	if (strpos($line, "{REGISTERFORM}") !== false)
	{
		$registerform="<form method='post' action='register.php'>\n"
		."<table class='register' summary='Registrationform'>\n"
		."<tr><td align='right'>"
		."<input type='hidden' name='sid' value='$surveyid' id='sid' />\n"
		.$clang->gT("First Name").":</td>"
		."<td align='left'><input class='text' type='text' name='register_firstname'";
		if (isset($_POST['register_firstname']))
		{
			$registerform .= " value='".htmlentities(returnglobal('register_firstname'),ENT_QUOTES,'UTF-8')."'";
		}
		$registerform .= " /></td></tr>"
		."<tr><td align='right'>".$clang->gT("Last Name").":</td>\n"
		."<td align='left'><input class='text' type='text' name='register_lastname'";
		if (isset($_POST['register_lastname']))
		{
			$registerform .= " value='".htmlentities(returnglobal('register_lastname'),ENT_QUOTES,'UTF-8')."'";
		}
		$registerform .= " /></td></tr>\n"
		."<tr><td align='right'>".$clang->gT("Email Address").":</td>\n"
		."<td align='left'><input class='text' type='text' name='register_email'";
		if (isset($_POST['register_email']))
		{
			$registerform .= " value='".htmlentities(returnglobal('register_email'),ENT_QUOTES,'UTF-8')."'";
		}
		$registerform .= " /></td></tr>\n";
        if (!isset($_REQUEST['lang']))
        {
		    $reglang = GetBaseLanguageFromSurveyID($surveyid);
        }
        else
            {
              $reglang = returnglobal('lang');    
            }
        

        if (function_exists("ImageCreate") && captcha_enabled('registrationscreen',$thissurvey['usecaptcha']))
        {
            $registerform .="<tr><td align='right'>".$clang->gT("Security Question").":</td><td><table><tr><td valign='middle'><img src='{$captchapath}verification.php' alt='' /></td><td valign='middle'><input type='text' size='5' maxlength='3' name='loadsecurity' value='' /></td></tr></table></td></tr>\n";
        }
      

		$registerform .= "<tr><td align='right'><input type='hidden' name='lang' value='".$reglang."' /></td><td></td></tr>\n";
		if(isset($thissurvey['attribute1']) && $thissurvey['attribute1'])
		{
			$registerform .= "<tr><td align='right'>".$thissurvey['attribute1'].":</td>\n"
			."<td align='left'><input class='text' type='text' name='register_attribute1'";
			if (isset($_POST['register_attribute1']))
			{
				$registerform .= " value='".htmlentities(returnglobal('register_attribute1'),ENT_QUOTES,'UTF-8')."'";
			}
			$registerform .= " /></td></tr>\n";
		}
		if(isset($thissurvey['attribute2']) && $thissurvey['attribute2'])
		{
			$registerform .= "<tr><td align='right'>".$thissurvey['attribute2'].":</td>\n"
			."<td align='left'><input class='text' type='text' name='register_attribute2'";
			if (isset($_POST['register_attribute2']))
			{
				$registerform .= " value='".htmlentities(returnglobal('register_attribute2'),ENT_QUOTES,'UTF-8')."'";
			}
			$registerform .= " /></td></tr>\n";
		}
		$registerform .= "<tr><td></td><td><input class='submit' type='submit' value='".$clang->gT("Continue")."' />"
		."</td></tr>\n"
		."</table>\n"
		."</form>\n";
		$line=str_replace("{REGISTERFORM}", $registerform, $line);
	}
	if (strpos($line, "{ASSESSMENTS}") !== false) $line=str_replace("{ASSESSMENTS}", $assessments, $line);
	if (strpos($line, "{ASSESSMENT_HEADING}") !== false) $line=str_replace("{ASSESSMENT_HEADING}", $clang->gT("Your Assessment"), $line);

	//queXS Addition
	include_once("quexs.php");
	$line = quexs_template_replace($line);

	return $line;
}

function getSavedCount($surveyid)
{
	//This function returns a count of the number of saved responses to a survey
	global $dbprefix, $connect;
    $surveyid=sanitize_int($surveyid);
    
	$query = "SELECT COUNT(*) FROM ".db_table_name('saved_control')." WHERE sid=$surveyid";
	$result=db_execute_num($query) or safe_die ("Couldn't get saved summary<br />$query<br />".$connect->ErrorMsg());    //Checked
	list($count) = $result->FetchRow();
	return $count;
}

function GetBaseLanguageFromSurveyID($surveyid)
{
	global $connect;
	//This function loads the local language file applicable to a survey
	$surveylanguage='en';
    $surveyid=sanitize_int($surveyid);
	$query = "SELECT language FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
	$result = db_execute_num($query); //Checked
	while ($result && ($row=$result->FetchRow())) {$surveylanguage = $row[0];}
	return $surveylanguage;
}


function GetAdditionalLanguagesFromSurveyID($surveyid)
{
	global $connect;
    $surveyid=sanitize_int($surveyid);
	//This function loads the local language file applicable to a survey
	$query = "SELECT additional_languages FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
	$result = db_execute_num($query);
	while ($result && ($row=$result->FetchRow())) {$surveylanguage = $row[0];}
	if (isset($surveylanguage) && $surveylanguage !="") $additional_languages = explode(" ", trim($surveylanguage));
	if (!isset($additional_languages) || $additional_languages==false) { $additional_languages = array();}
	return $additional_languages;
}



//For multilanguage surveys
// If null or 0 is given for $surveyid then the default language from config-defaults.php is returned
function SetSurveyLanguage($surveyid, $language)// SetSurveyLanguage($surveyid)
{

		global $rootdir, $defaultlang;
        $surveyid=sanitize_int($surveyid);
		require_once($rootdir.'/classes/core/language.php');
		if (isset($surveyid) && $surveyid>0)
		{
	 		// see if language actually is present in survey
			$query = "SELECT language, additional_languages FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
			$result = db_execute_assoc($query); //Checked
			while ($result && ($row=$result->FetchRow())) {
				$additional_languages = $row['additional_languages'];
				$default_language = $row['language'];
			}
	
			//echo "Language: ".$language."<br>Default language: ".$default_language."<br>Available languages: ".$additional_languages."<br />";
			if ((isset($additional_languages) && strpos($additional_languages, $language) === false) or (isset($default_language) && $default_language == $language) or !isset($language)) {
				// Language not supported, or default language for survey, fall back to survey's default language
				$_SESSION['s_lang'] = $default_language;
				//echo "Language not supported, resorting to ".$_SESSION['s_lang']."<br />";
			} else {
				$_SESSION['s_lang'] = $language;
				//echo "Language will be set to ".$_SESSION['s_lang']."<br />";
			}
		$clang = new limesurvey_lang($_SESSION['s_lang']);
		}
		else {
			 $clang = new limesurvey_lang($defaultlang);
			 }
		return $clang;
}


function buildLabelSetCheckSumArray()
{
	global $connect;
	// BUILD CHECKSUMS FOR ALL EXISTING LABEL SETS
	$query = "SELECT lid
              FROM ".db_table_name('labelsets')."
              ORDER BY lid";
	$result = db_execute_assoc($query) or safe_die("safe_died collecting labelset ids<br />$query<br />".$connect->ErrorMsg());  //Checked  
	$csarray=array();
	while ($row=$result->FetchRow())
	{
		$thisset="";
		$query2 = "SELECT code, title, sortorder, language
                   FROM ".db_table_name('labels')."
                   WHERE lid={$row['lid']}
                   ORDER BY language, sortorder, code";
		$result2 = db_execute_num($query2) or safe_die("safe_died querying labelset $lid<br />$query2<br />".$connect->ErrorMsg()); //Checked  
		while($row2=$result2->FetchRow())
		{
			$thisset .= implode('.', $row2);
		} // while
		$csarray[$row['lid']]=dechex(crc32($thisset)*1);
	}
	return $csarray;
}

function getQuestionAttributes($qid)
{
	global $dbprefix, $connect;
    $qid=sanitize_int($qid);
	$query = "SELECT * FROM ".db_table_name('question_attributes')." WHERE qid=$qid";
	$result = db_execute_assoc($query) or safe_die("Error finding question attributes");  //Checked
	$qid_attributes=array();
	while ($row=$result->FetchRow())
	{
		$qid_attributes[]=$row;
	}
	//echo "<pre>";print_r($qid_attributes);echo "</pre>";
	return $qid_attributes;
}

function questionAttributes()
{
	//For each question attribute include a key:
	// name - the display name
	// types - a string with one character representing each question typ to which the attribute applies
	// help - a short explanation
    $qattributes[]=array("name"=>"answer_width",
    "types"=>"ABCEF1",
    "help"=>"The percentage width of the answer column");
	$qattributes[]=array("name"=>"display_columns",
	"types"=>"LMZG",
	"help"=>"Number of columns to display");
    $qattributes[]=array("name"=>"array_filter",
    "types"=>"ABCEF",
    "help"=>"Filter an Array's Answers from a Multiple Options Question");
    $qattributes[]=array("name"=>"display_rows",
    "types"=>"TU",
    "help"=>"How many rows to display");
	$qattributes[]=array("name"=>"hide_tip",
	"types"=>"!LMOPWZK",
	"help"=>"Hide the tip that is normally supplied with question");
	$qattributes[]=array("name"=>"code_filter",
	"types"=>"WZ",
	"help"=>"Filter the available answers by this value");
	$qattributes[]=array("name"=>"max_answers",
	"types"=>"MP",
	"help"=>"Limit the number of possible answers");
    $qattributes[]=array("name"=>"maximum_chars",
    "types"=>"STUNQK",
    "help"=>"Maximum Characters Allowed");
    $qattributes[]=array("name"=>"random_order",
    "types"=>"!LMOPQKRWZFHABCE1",
    "help"=>"Present Answers in random order");
    $qattributes[]=array("name"=>"text_input_width",
    "types"=>"NSTU",
    "help"=>"Width of text input box");
    $qattributes[]=array("name"=>"numbers_only",
    "types"=>"Q",
    "help"=>"Allow only numerical input");
	$qattributes[]=array("name"=>"max_num_value",
	"types"=>"K",
	"help"=>"Maximum numeric value of multiple numeric input");
	$qattributes[]=array("name"=>"equals_num_value",
	"types"=>"K",
	"help"=>"Multiple numeric inputs must equal this value");
	$qattributes[]=array("name"=>"min_num_value",
	"types"=>"K",
	"help"=>"Multiple numeric inputs must be greater than this value");
	$qattributes[]=array("name"=>"prefix",
	"types"=>"KNSQ",
	"help"=>"Add a prefix to the answer field");
	$qattributes[]=array("name"=>"suffix",
	"types"=>"KNSQ",
	"help"=>"Add a suffix to the answer field");
	$qattributes[]=array("name"=>"dropdown_dates",
	"types"=>"D",
	"help"=>"Use dropdown dates layout instead of calendar popup");
	$qattributes[]=array("name"=>"exclude_all_others",
	"types"=>"M",
	"help"=>"Excludes all other options if this is selected");
	$qattributes[]=array("name"=>"use_dropdown",
	"types"=>"1",
	"help"=>"Use Dual Dropdown instead of Dual Scale");
	$qattributes[]=array("name"=>"dropdown_prepostfix",
	"types"=>"1",
	"help"=>"Prefix|Suffix for dropdown lists");
	$qattributes[]=array("name"=>"dualscale_headerA",
	"types"=>"1",
	"help"=>"Header for Column A");
	$qattributes[]=array("name"=>"dualscale_headerB",
	"types"=>"1",
	"help"=>"Header for Column B");
	$qattributes[]=array("name"=>"dropdown_separators",
	"types"=>"1",
	"help"=>"Post-Answer-Separator|Inter-Dropdownlist-Separator for dropdown lists");
	$qattributes[]=array("name"=>"other_replace_text",
	"types"=>"LMPWZ!",
	"help"=>"Replaces the 'other' label with text");
	/* -- > Commented out since not yet used
    $qattributes[]=array("name"=>"permission",
    "types"=>"5DGL!OMPQNRSTUYABCEFHWZ",
    "help"=>"Flexible attribute for permissions");
	$qattributes[]=array("name"=>"default_value",
	"types"=>"^",
	"help"=>"What value to use as the default");
	$qattributes[]=array("name"=>"minimum_value",
	"types"=>"^",
	"help"=>"The lowest value on the slider");
	$qattributes[]=array("name"=>"maximum_value",
	"types"=>"^",
	"help"=>"The highest value on the slider");
	//	$qattributes[]=array("name"=>"left_label",
	//				"types"=>"^",
	//				"help"=>"The label to the left of the slider");
	//	$qattributes[]=array("name"=>"centre_label",
	//				"types"=>"^"
	//				"help"=>"The centre label on the slider");
	//	$qattributes[]=array("name"=>"right_label",
	//				"types"=>"^",
	//				"help"=>"The ")

	*/

	//This builds a more useful array (don't modify)
	foreach($qattributes as $qa)
	{
		for ($i=0; $i<=strlen($qa['types'])-1; $i++)
		{
			$qat[substr($qa['types'], $i, 1)][]=array("name"=>$qa['name'],
			"help"=>$qa['help']);
		}
	}
	return $qat;
}

// make sure the given string (which comes from a POST or GET variable)
// is safe to use in MySQL.  This does nothing if gpc_magic_quotes is on.
function auto_escape($str) {
	global $connect;
	if (!get_magic_quotes_gpc()) {
		return $connect->escape($str);
	}
	return $str;
}
// the opposite of the above: takes a POST or GET variable which may or
// may not have been 'auto-quoted', and return the *unquoted* version.
// this is useful when the value is destined for a web page (eg) not
// a SQL query.
function auto_unescape($str) {
    if (!isset($str)) {return null;};
	if (!get_magic_quotes_gpc())
	return $str;
	return stripslashes($str);
}
// make a string safe to include in an HTML 'value' attribute.
function html_escape($str) {
	// escape newline characters, too, in case we put a value from
	// a TEXTAREA  into an <input type="hidden"> value attribute.
	return str_replace(array("\x0A","\x0D"),array("&#10;","&#13;"),
	htmlspecialchars( $str, ENT_QUOTES ));
}

// make a string safe to include in a JavaScript String parameter.
function javascript_escape($str, $strip_tags=false, $htmldecode=false) {
    $new_str ='';

    if ($htmldecode==true) {
        $str=html_entity_decode_php4($str);
    }
    if ($strip_tags==true)
    {
        $str=strip_tags($str);
    }
    
    return str_replace('\'',"\\'",$str);
} 

// This function returns the header as result string
// If you want to echo the header use doHeader() !
function getHeader()
{
	global $embedded, $surveyid, $rooturl,$defaultlang;

    if (isset($_SESSION['s_lang']) && $_SESSION['s_lang'])
    {
        $surveylanguage= $_SESSION['s_lang'];
    }
    elseif (isset($surveyid) && $surveyid) 
    {
        $surveylanguage=GetBaseLanguageFromSurveyID($surveyid);
    }
	else 
    {
        $surveylanguage=$defaultlang;
    }
	if ( !$embedded )
	{
		$header=  "<?xml version=\"1.0\" encoding=\"UTF-8\"?><!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n"
        		. "<html ";
        if (getLanguageRTL($surveylanguage))
        {
            $header.=" dir=\"rtl\" ";
        }
        $header.= "><head>\n"
//        		. "<link type=\"text/css\" rel=\"StyleSheet\" href=\"".$rooturl."/scripts/slider/swing.css\" />\n"
        		. "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"".$rooturl."/scripts/calendar/calendar-blue.css\" title=\"win2k-cold-1\" />"
//        		. "<script type=\"text/javascript\" src=\"".$rooturl."/scripts/slider/range.js\"></script>\n"
//        		. "<script type=\"text/javascript\" src=\"".$rooturl."/scripts/slider/timer.js\"></script>\n"
//        		. "<script type=\"text/javascript\" src=\"".$rooturl."/scripts/slider/slider.js\"></script>\n"
        		. "<script type=\"text/javascript\" src=\"".$rooturl."/scripts/calendar/calendar.js\"></script>\n"
        		. "<script type=\"text/javascript\" src=\"".$rooturl."/scripts/calendar/lang/calendar-".$surveylanguage.".js\"></script>\n"
        		. "<script type=\"text/javascript\" src=\"".$rooturl."/scripts/calendar/calendar-setup.js\"></script>\n";
        return $header;        
    }

	global $embedded_headerfunc;

	if ( function_exists( $embedded_headerfunc ) )
	return $embedded_headerfunc();
}

function doHeader()
{
	echo getHeader();
}

function doAdminFooter()
{
	echo getAdminFooter();
}

function getAdminFooter($url, $explanation)
{
	global $versionnumber, $buildnumber, $setfont, $imagefiles, $clang;

	if ($buildnumber != "")
	{
		$buildtext="($buildnumber)";
	}
	else
	{
		$buildtext="";
	}


	$strHTMLFooter = "<div class='footer'>\n"
	. "\t\t\t<div style='float:left;'><img alt='LimeSurvey - ".$clang->gT("Online Manual")."' title='LimeSurvey - ".$clang->gT("Online Manual")."' src='$imagefiles/help.gif' "
	. "onclick=\"window.open('$url')\" onmouseover=\"document.body.style.cursor='pointer'\" "
	. "onmouseout=\"document.body.style.cursor='auto'\" /></div>\n"
	. "\t\t\t<div style='float:right;'><img alt='".$clang->gT("Support this project - Donate to ")."LimeSurvey' title='".$clang->gT("Support this project - Donate to ")."LimeSurvey!' src='$imagefiles/donate.png' "
	. "onclick=\"window.open('http://www.limesurvey.org/component/option,com_dtdonate/lang,en/index.php?option=com_dtdonate')\" "
	. "onmouseover=\"document.body.style.cursor='pointer'\" onmouseout=\"document.body.style.cursor='auto'\" /></div>\n"
	. "\t\t\t<div class='subtitle'><a class='subtitle' title='".$clang->gT("Visit our website!")."' href='http://www.limesurvey.org' target='_blank'>LimeSurvey</a><br />".$clang->gT('Version')." $versionnumber $buildtext</div>"
	. "</div></body>\n</html>";
	return $strHTMLFooter;
}


function doAdminHeader()
{
	echo getAdminHeader();
}

function getAdminHeader($meta=false)
{
	global $sitename, $admintheme, $rooturl;
	if (!isset($_SESSION['adminlang']) || $_SESSION['adminlang']=='') {$_SESSION['adminlang']='en';}
	$strAdminHeader="<?xml version=\"1.0\"?><!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n"
	."<html ";
    
    if (getLanguageRTL($_SESSION['adminlang']))
    {
        $strAdminHeader.=" dir=\"rtl\" ";
    }
    $strAdminHeader.=">\n<head>\n"
	. "<!--[if lt IE 7]>\n"
	. "<script defer type=\"text/javascript\" src=\"scripts/pngfix.js\"></script>\n"
	. "<![endif]-->\n"
	. "<title>$sitename</title>\n";
    
	if ($meta)
        {
        $strAdminHeader.=$meta;
        }
	$strAdminHeader.="<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n"
	. "<script type=\"text/javascript\" src=\"scripts/tabpane/js/tabpane.js\"></script>\n"
	. "<script type=\"text/javascript\" src=\"scripts/tooltips.js\"></script>\n"
    . "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"../scripts/calendar/calendar-blue.css\" title=\"win2k-cold-1\" />\n"
    . "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"scripts/tabpane/css/tab.webfx.css \" />\n"
    . "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles/$admintheme/adminstyle.css\" />\n"
	. "<script type=\"text/javascript\" src=\"../scripts/calendar/calendar.js\"></script>\n"
	. "<script type=\"text/javascript\" src=\"../scripts/calendar/lang/calendar-".$_SESSION['adminlang'].".js\"></script>\n"
	. "<script type=\"text/javascript\" src=\"../scripts/calendar/calendar-setup.js\"></script>\n"
	. "<script type=\"text/javascript\" src=\"scripts/validation.js\"></script>"
	. "</head>\n<body>\n"
	. "<div class=\"maintitle\">\n"
	. "\t\t$sitename\n"
	. "</div>\n";
	return $strAdminHeader;
}



// This function returns the Footer as result string
// If you want to echo the Footer use doFooter() !
function getFooter()
{
	global $embedded;

	if ( !$embedded )
	{
		return "</html>\n";
	}

	global $embedded_footerfunc;

	if ( function_exists( $embedded_footerfunc ) )
	return $embedded_footerfunc();
}


function doFooter()
{
	echo getFooter();
}



// This function replaces field names in a text with the related values
// (e.g. for email and template functions)
function ReplaceFields ($text,$fieldsarray)
{

	foreach ( $fieldsarray as $key => $value )
	{
		$text=str_replace($key, $value, $text);
	}
	return $text;
}

function MailTextMessage($body, $subject, $to, $from, $sitename, $ishtml=false, $bouncemail=null)
{
// This function mails a text $body to the recipient $to. YOu can use more than one 
// recipient when using a comma separated string with recipients.

	global $emailmethod, $emailsmtphost, $emailsmtpuser, $emailsmtppassword, $defaultlang, $rootdir, $maildebug, $maildebugbody;

    //if ($ishtml) {$body=htmlwrap($body,110);}

	if (is_null($bouncemail) )
	{
		$sender=$from;
	}
	else
	{
		$sender=$bouncemail;
	}

	$mail = new PHPMailer;
    if (!$mail->SetLanguage($defaultlang,$rootdir.'/classes/phpmailer/language/')) 
    {
        $mail->SetLanguage('en',$rootdir.'/classes/phpmailer/language/');
    }
	$mail->CharSet = "UTF-8";
	if (isset($emailsmtpssl) && $emailsmtpssl==1) {$mail->Protocol = "ssl";}

	$fromname='';
	$fromemail=$from;
	if (strpos($from,'<'))
	{
		$fromemail=substr($from,strpos($from,'<')+1,strpos($from,'>')-1-strpos($from,'<'));
		$fromname=trim(substr($from,0, strpos($from,'<')-1));
	}

	$sendername='';
	$senderemail=$sender;
	if (strpos($sender,'<'))
	{
		$senderemail=substr($sender,strpos($sender,'<')+1,strpos($sender,'>')-1-strpos($sender,'<'));
		$sendername=trim(substr($sender,0, strpos($sender,'<')-1));
	}

	$mail->Mailer = $emailmethod;
	if ($emailmethod=="smtp")
	{ $mail->Host = $emailsmtphost;
	$mail->Username =$emailsmtpuser;
	$mail->Password =$emailsmtppassword;
	if ($emailsmtpuser!="")
	{$mail->SMTPAuth = true;}
	}
	$mail->From = $fromemail;
	$mail->Sender = $senderemail; // Sets Return-Path for error notifications
    $toemails = explode(";", $to);
    foreach ($toemails as $singletoemail)
    {
        if (strpos($singletoemail, '<') )
        {
	       $toemail=substr($singletoemail,strpos($singletoemail,'<')+1,strpos($singletoemail,'>')-1-strpos($singletoemail,'<'));
           $toname=trim(substr($singletoemail,0, strpos($singletoemail,'<')-1));
           $mail->AddAddress($toemail,$toname);
        }
        else
        {
            $mail->AddAddress($singletoemail);
        }
    }	
	$mail->FromName = $fromname;
	$mail->AddCustomHeader("X-Surveymailer: $sitename:Emailer (LimeSurvey.sourceforge.net)");
	if (get_magic_quotes_gpc() != "0")	{$body = stripcslashes($body);}
	$textbody = strip_tags($body);
	$textbody = str_replace("&quot;", '"', $textbody);
    if ($ishtml) { 
        $mail->IsHTML(true);
    	$mail->Body = $body;
    	$mail->AltBody = strip_tags(br2nl(html_entity_decode_php4($textbody)));
    } else
        {
        $mail->IsHTML(false);
    	$mail->Body = $textbody;
        }

	if (trim($subject)!='') {$mail->Subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";}
    $sent=$mail->Send();
    $maildebug=$mail->ErrorInfo;
    $maildebugbody=$mail->Body;
	return $sent;
}

// This functions removes all tags, CRs, linefeeds and other strange chars from a given text
function FlattenText($texttoflatten)
{
	$nicetext = strip_tags($texttoflatten);
	$nicetext = str_replace("\"", "`", $nicetext);
	$nicetext = str_replace("'", "`", $nicetext);
	$nicetext = str_replace("\r", "", $nicetext);
	$nicetext = trim(str_replace("\n", "", $nicetext));
	return  $nicetext;
}
/**
* getreferringurl() returns the reffering URL
*/
function getreferringurl()
{
  global $clang,$stripQueryFromRefurl;
  if (isset($_SESSION['refurl']))
  {
    return; // do not overwrite refurl
  }

  // refurl is not set in session, read it from server variable
  if(isset($_SERVER["HTTP_REFERER"]))
  {
    if(!ereg($_SERVER["SERVER_NAME"], $_SERVER["HTTP_REFERER"]))
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
       $_SESSION['refurl'] = $clang->gT("Local Submission");
    }
  }
  else
  {
    $_SESSION['refurl'] = null;
  }
}

function getRandomID()
{        // Create a random survey ID - based on code from Ken Lyle
	// Random sid/ question ID generator...
	$totalChar = 5; // number of chars in the sid
	$salt = "123456789"; // This is the char. that is possible to use
	srand((double)microtime()*1000000); // start the random generator
	$sid=""; // set the inital variable
	for ($i=0;$i<$totalChar;$i++) // loop and create sid
	$sid = $sid . substr ($salt, rand() % strlen($salt), 1);
	return $sid;
}

/**
* getArrayFiltersForGroup() queries the database and produces a list of array_filter questions and targets with in the same group
* @global string $surveyid
* @global string $gid
* @global string $dbprefix
* @return returns an nested array which contains arrays with the keys: question id (qid), question manditory, target type (type), and list_filter id (fid)
*/
function getArrayFiltersForGroup($surveyid,$gid)
{
	// TODO: Check list_filter values to make sure questions are previous?
	global $dbprefix;
    $surveyid=sanitize_int($surveyid);
    $gid=sanitize_int($gid);
	// Get All Questions in Current Group
	$qquery = "SELECT * FROM ".db_table_name('questions')." WHERE sid='$surveyid' AND gid='$gid' AND language='".$_SESSION['s_lang']."' ORDER BY qid";
	$qresult = db_execute_assoc($qquery);  //Checked
	$grows = array(); //Create an empty array in case query not return any rows
	// Store each result as an array with in the $grows array
	while ($qrow = $qresult->FetchRow()) {
		$grows[$qrow['qid']] = array('qid' => $qrow['qid'],'type' => $qrow['type'], 'mandatory' => $qrow['mandatory'], 'title' => $qrow['title']);
	}
	$attrmach = array(); // Stores Matches of filters that have their values as questions with in current group
	$grows2 = $grows;
	foreach ($grows as $qrow) // Cycle through questions to see if any have list_filter attributes
	{
		$qquery = "SELECT value FROM ".db_table_name('question_attributes')." WHERE attribute='array_filter' AND qid='".$qrow['qid']."'";
		$qresult = db_execute_num($qquery);     //Checked
		if ($qresult->RecordCount() == 1) // We Found a array_filter attribute
		{
			$val = $qresult->FetchRow(); // Get the Value of the Attribute ( should be a previous question's title in same group )
			foreach ($grows2 as $avalue)
			{
				if ($avalue['title'] == $val[0])
				{
					$filter = array('qid' => $qrow['qid'], 'mandatory' => $qrow['mandatory'], 'type' => $avalue['type'], 'fid' => $avalue['qid']);
					array_push($attrmach,$filter);
				}
			}
			reset($grows2);
		}
	}
	return $attrmach;
}

/**
* getArrayFiltersForQuestion($qid) finds out if a question has an array_filter attribute and what codes where selected on target question
* @global string $surveyid
* @global string $gid
* @global string $dbprefix
* @return returns an array of codes that were selected else returns false
*/
function getArrayFiltersForQuestion($qid)
{
	// TODO: Check list_filter values to make sure questions are previous?
	global $surveyid, $dbprefix;
    $qid=sanitize_int($qid);
	$query = "SELECT value FROM ".db_table_name('question_attributes')." WHERE attribute='array_filter' AND qid='".$qid."'";
	$result = db_execute_assoc($query);  //Checked
	if ($result->RecordCount() == 1) // We Found a array_filter attribute
	{
		$val = $result->FetchRow(); // Get the Value of the Attribute ( should be a previous question's title in same group )
		foreach ($_SESSION['fieldarray'] as $fields)
		{
			if ($fields[2] == $val['value'])
			{
				// we found the target question, now we need to know what the answers where, we know its a multi!
                $fields[0]=sanitize_int($fields[0]);
				$query = "SELECT code FROM ".db_table_name('answers')." where qid='{$fields[0]}' AND language='".$_SESSION['s_lang']."' order by sortorder";
				$qresult = db_execute_assoc($query);  //Checked
				$selected = array();
				while ($code = $qresult->fetchRow())
				{
					if ($_SESSION[$fields[1].$code['code']] == "Y") array_push($selected,$code['code']);
				}
				return $selected;
			}
		}
		return false;
	}
	return false;
}

/**
* getArrayFiltersForQuestion($qid) finds out if a question is in the currect group or not for array filter
* @global string $surveyid
* @global string $gid
* @global string $dbprefix
* @return returns true if its not in currect group and false if it is..
*/
function getArrayFiltersOutGroup($qid)
{
	// TODO: Check list_filter values to make sure questions are previous?
	global $surveyid, $dbprefix, $gid;
    $qid=sanitize_int($qid);
	$query = "SELECT value FROM ".db_table_name('question_attributes')." WHERE attribute='array_filter' AND qid='".$qid."'";
	$result = db_execute_assoc($query); //Checked
	if ($result->RecordCount() == 1) // We Found a array_filter attribute
	{
		$val = $result->FetchRow(); // Get the Value of the Attribute ( should be a previous question's title in same group )

		// we found the target question, now we need to know what the answers where, we know its a multi!
		$query = "SELECT gid FROM ".db_table_name('questions')." where title='{$val['value']}' AND language='".$_SESSION['s_lang']."' AND sid = $surveyid";
		$qresult = db_execute_assoc($query); //Checked
		if ($qresult->RecordCount() == 1)
		{
			$val2 = $qresult->FetchRow();
			if ($val2['gid'] != $gid) return true;
			if ($val2['gid'] == $gid) return false;
		}
		return false;
	}
	return false;
}


/**
 * Run an arbitrary sequence of semicolon-delimited SQL commands
 *
 * Assumes that the input text (file or string) consists of
 * a number of SQL statements ENDING WITH SEMICOLONS.  The
 * semicolons MUST be the last character in a line.
 * Lines that are blank or that start with "#" or "--" (postgres) are ignored.
 * Only tested with mysql dump files (mysqldump -p -d limesurvey)
 * Function kindly borrowed by Moodle
 * @uses $dbprefix
 * @param string $sqlfile The path where a file with sql commands can be found on the server.
 * @param string $sqlstring If no path is supplied then a string with semicolon delimited sql
 * commands can be supplied in this argument.
 * @return bool Returns true if database was modified successfully.
 */
function modify_database($sqlfile='', $sqlstring='') {

	global $dbprefix;
	global $defaultuser;
	global $defaultpass;
	global $siteadminemail;
	global $siteadminname;
	global $defaultlang;
	global $codeString;
	global $rootdir;
    global $connect;
    global $clang;
    global $modifyoutput;
    global $databasetabletype;

	require_once($rootdir."/admin/classes/core/sha256.php");

	$success = true;  // Let's be optimistic
    $modifyoutput='';

	if (!empty($sqlfile)) {
		if (!is_readable($sqlfile)) {
			$success = false;
			echo '<p>Tried to modify database, but "'. $sqlfile .'" doesn\'t exist!</p>';
			return $success;
		} else {
			$lines = file($sqlfile);
		}
	} else {
		$sqlstring = trim($sqlstring);
		if ($sqlstring{strlen($sqlstring)-1} != ";") {
			$sqlstring .= ";"; // add it in if it's not there.
		}
		$lines[] = $sqlstring;
	}

	$command = '';

	foreach ($lines as $line) {
		$line = rtrim($line);
		$length = strlen($line);

		if ($length and $line[0] <> '#' and substr($line,0,2) <> '--') {
			if (substr($line, $length-1, 1) == ';') {
  				$line = substr($line, 0, $length-1);   // strip ;
				$command .= $line;
				$command = str_replace('prefix_', $dbprefix, $command); // Table prefixes
				$command = str_replace('$defaultuser', $defaultuser, $command); // variables By Moses
				$command = str_replace('$defaultpass', SHA256::hash($defaultpass), $command); // variables By Moses
				$command = str_replace('$siteadminname', $siteadminname, $command);
				$command = str_replace('$siteadminemail', $siteadminemail, $command); // variables By Moses
				$command = str_replace('$defaultlang', $defaultlang, $command); // variables By Moses
				$command = str_replace('$sessionname', 'ls'.getRandomID().getRandomID().getRandomID().getRandomID(), $command); // variables By Moses
				$command = str_replace('$databasetabletype', $databasetabletype, $command);

				if (! db_execute_num($command)) {  //Checked
                  $modifyoutput .="<br />".$clang->gT("Executing").".....".$command."<font color='#FF0000'>...".$clang->gT("Failed! Reason: ").$connect->ErrorMsg()."</font>";
				  $success = false;
				}
                 else
                 {
                    $modifyoutput .="<br />".$clang->gT("Executing").".....".$command."<font color='#00FF00'>...".$clang->gT("Success!")."</font>";
                 }

				$command = '';
			} else {
				$command .= $line;
			}
		}
	}

	return $success;

}



// unsets all Session variables to kill session
function killSession()	//added by Dennis
	{
		// Delete the Session Cookie
		$CookieInfo = session_get_cookie_params();
		if ( (empty($CookieInfo['domain'])) && (empty($CookieInfo['secure'])) ) {
			setcookie(session_name(), '', time()-3600, $CookieInfo['path']);
		} elseif (empty($CookieInfo['secure'])) {
			setcookie(session_name(), '', time()-3600, $CookieInfo['path'], $CookieInfo['domain']);
		} else {
			setcookie(session_name(), '', time()-3600, $CookieInfo['path'], $CookieInfo['domain'], $CookieInfo['secure']);
		}
		unset($_COOKIE[session_name()]);
        foreach ($_SESSION as $key =>$value) 
        {
		  //echo $key." = ".$value."<br />";
		  unset($_SESSION[$key]);
		}
		$_SESSION = array(); // redundant with previous lines
		session_unset();
		session_destroy();
  	}







// set the rights of a user and his children
function setuserrights($uid, $rights)
	{
	global $connect;
    $uid=sanitize_int($uid);
	$updates = "create_survey=".$rights['create_survey']
	. ", create_user=".$rights['create_user']
	. ", delete_user=".$rights['delete_user']
	. ", superadmin=".$rights['superadmin']
	. ", configurator=".$rights['configurator']
	. ", manage_template=".$rights['manage_template']
	. ", manage_label=".$rights['manage_label'];
	$uquery = "UPDATE ".db_table_name('users')." SET ".$updates." WHERE uid = ".$uid;
 	return $connect->Execute($uquery);     //Checked
	}

// set the rights for a survey
function setsurveyrights($uids, $rights)
	{
	global $connect, $surveyid;
    $uids=array_map('sanitize_int',$uids);  
	$uids_implode = implode(" OR uid = ", $uids);

	$updates = "edit_survey_property=".$rights['edit_survey_property']
	. ", define_questions=".$rights['define_questions']
	. ", browse_response=".$rights['browse_response']
	. ", export=".$rights['export']
	. ", delete_survey=".$rights['delete_survey']
	. ", activate_survey=".$rights['activate_survey'];
	$uquery = "UPDATE ".db_table_name('surveys_rights')." SET ".$updates." WHERE sid = {$surveyid} AND uid = ".$uids_implode;
	// TODO
	return $connect->Execute($uquery);   //Checked 
	}

function createPassword()
	{
	$pwchars = "abcdefhjmnpqrstuvwxyz23456789";
	$password_length = 8;
	$passwd = '';

	for ($i=0; $i<$password_length; $i++)
		{
		$passwd .= $pwchars[floor(rand(0,strlen($pwchars)-1))];
		}
	return $passwd;
	}

function getgroupuserlist()
    {
    global $ugid, $dbprefix, $scriptname, $connect, $clang;

    $ugid=sanitize_int($ugid);
	$surveyidquery = "SELECT a.uid, a.users_name FROM ".db_table_name('users')." AS a LEFT JOIN (SELECT uid AS id FROM ".db_table_name('user_in_groups')." WHERE ugid = {$ugid}) AS b ON a.uid = b.id WHERE id IS NULL ORDER BY a.users_name";

    $surveyidresult = db_execute_assoc($surveyidquery);  //Checked
    if (!$surveyidresult) {return "Database Error";}
    $surveyselecter = "";
    $surveynames = $surveyidresult->GetRows();
    if ($surveynames)
        {
        foreach($surveynames as $sv)
            {
			$surveyselecter .= "\t\t\t<option";
            $surveyselecter .=" value='{$sv['uid']}'>{$sv['users_name']}</option>\n";
            }
        }
    $surveyselecter = "\t\t\t<option value='-1' selected='selected'>".$clang->gT("Please Choose...")."</option>\n".$surveyselecter;
    return $surveyselecter;
    }

function getsurveyuserlist()
    {
    global $surveyid, $dbprefix, $scriptname, $connect, $clang, $usercontrolSameGroupPolicy;
    $surveyid=sanitize_int($surveyid);
	$surveyidquery = "SELECT a.uid, a.users_name FROM ".db_table_name('users')." AS a LEFT OUTER JOIN (SELECT uid AS id FROM ".db_table_name('surveys_rights')." WHERE sid = {$surveyid}) AS b ON a.uid = b.id WHERE id IS NULL ORDER BY a.users_name";

    $surveyidresult = db_execute_assoc($surveyidquery);  //Checked
    if (!$surveyidresult) {return "Database Error";}
    $surveyselecter = "";
    $surveynames = $surveyidresult->GetRows();

    if (isset($usercontrolSameGroupPolicy) &&
		$usercontrolSameGroupPolicy === true)
    {
	$authorizedUsersList = getuserlist('onlyuidarray');
    }

    if ($surveynames)
        {
        foreach($surveynames as $sv)
            {
		if (!isset($usercontrolSameGroupPolicy) ||
			$usercontrolSameGroupPolicy === false ||
			in_array($sv['uid'],$authorizedUsersList))
		{
			$surveyselecter .= "\t\t\t<option";
			$surveyselecter .=" value='{$sv['uid']}'>{$sv['users_name']}</option>\n";
		}
            }
        }
    if (!isset($svexist)) {$surveyselecter = "\t\t\t<option value='-1' selected='selected'>".$clang->gT("Please Choose...")."</option>\n".$surveyselecter;}
    else {$surveyselecter = "\t\t\t<option value='-1'>".$clang->gT("None")."</option>\n".$surveyselecter;}
    return $surveyselecter;
    }

function getsurveyusergrouplist($outputformat='htmloptions')
    {
    global $surveyid, $dbprefix, $scriptname, $connect, $clang, $usercontrolSameGroupPolicy;
    $surveyid=sanitize_int($surveyid);

	//$surveyidquery = "SELECT a.ugid, a.name, MAX(d.ugid) AS da FROM ".db_table_name('user_groups')." AS a LEFT JOIN (SELECT b.ugid FROM ".db_table_name('user_in_groups')." AS b LEFT JOIN (SELECT * FROM ".db_table_name('surveys_rights')." WHERE sid = {$surveyid}) AS c ON b.uid = c.uid WHERE c.uid IS NULL) AS d ON a.ugid = d.ugid GROUP BY a.ugid, a.name HAVING da IS NOT NULL";
	//n.b: the original query (above) uses 'da' in the HAVING clause. MS SQL Server doesn't like that, and forces you to redeclare the expression used in the select. Stupid, stupid, SQL Server.
	//     I'm hoping this will not bork MySQL. If it does, we'll need to drop a switch in here.
	$surveyidquery = "SELECT a.ugid, a.name, MAX(d.ugid) AS da FROM ".db_table_name('user_groups')." AS a LEFT JOIN (SELECT b.ugid FROM ".db_table_name('user_in_groups')." AS b LEFT JOIN (SELECT * FROM ".db_table_name('surveys_rights')." WHERE sid = {$surveyid}) AS c ON b.uid = c.uid WHERE c.uid IS NULL) AS d ON a.ugid = d.ugid GROUP BY a.ugid, a.name HAVING MAX(d.ugid) IS NOT NULL";
	$surveyidresult = db_execute_assoc($surveyidquery);  //Checked
    if (!$surveyidresult) {return "Database Error";}
    $surveyselecter = "";
    $surveynames = $surveyidresult->GetRows();

    if (isset($usercontrolSameGroupPolicy) &&
		$usercontrolSameGroupPolicy === true)
    {
	 $authorizedGroupsList=getusergrouplist('simplegidarray');
    }

    if ($surveynames)
        {
        foreach($surveynames as $sv)
            {
		if (!isset($usercontrolSameGroupPolicy) ||
			$usercontrolSameGroupPolicy === false ||
			in_array($sv['ugid'],$authorizedGroupsList))
		{
			$surveyselecter .= "\t\t\t<option";
			$surveyselecter .=" value='{$sv['ugid']}'>{$sv['name']}</option>\n";
			$simpleugidarray[] = $sv['ugid'];
		}
            }
        }
    if (!isset($svexist)) {$surveyselecter = "\t\t\t<option value='-1' selected='selected'>".$clang->gT("Please Choose...")."</option>\n".$surveyselecter;}
    else {$surveyselecter = "\t\t\t<option value='-1'>".$clang->gT("None")."</option>\n".$surveyselecter;}

    if ($outputformat == 'simpleugidarray')
    {
	return $simpleugidarray;
    }
    else
    {
    	return $surveyselecter;
    }
}

function getusergrouplist($outputformat='optionlist')
    {
    global $dbprefix, $scriptname, $connect, $clang;

	//$squery = "SELECT ugid, name FROM ".db_table_name('user_groups') ." WHERE owner_id = {$_SESSION['loginID']} ORDER BY name";
	$squery = "SELECT a.ugid, a.name, a.owner_id, b.uid FROM ".db_table_name('user_groups') ." AS a LEFT JOIN ".db_table_name('user_in_groups') ." AS b ON a.ugid = b.ugid WHERE uid = {$_SESSION['loginID']} ORDER BY name";

    $sresult = db_execute_assoc($squery); //Checked
    if (!$sresult) {return "Database Error";}
    $selecter = "";
    $groupnames = $sresult->GetRows();
    $simplegidarray=array();
    if ($groupnames)
        {
        foreach($groupnames as $gn)
            {
		$selecter .= "\t\t\t<option ";
		if($_SESSION['loginID'] == $gn['owner_id']) {$selecter .= " style=\"font-weight: bold;\"";}
		if (isset($_GET['ugid']) && $gn['ugid'] == $_GET['ugid']) {$selecter .= " selected='selected'"; $svexist = 1;}
		$selecter .=" value='$scriptname?action=editusergroups&amp;ugid={$gn['ugid']}'>{$gn['name']}</option>\n";
		$simplegidarray[] = $gn['ugid'];
            }
        }
    if (!isset($svexist)) {$selecter = "\t\t\t<option value='-1' selected='selected'>".$clang->gT("Please Choose...")."</option>\n".$selecter;}
    //else {$selecter = "\t\t\t<option value='-1'>".$clang->gT("None")."</option>\n".$selecter;}

    if ($outputformat == 'simplegidarray')
    {
    	return $simplegidarray;
    }
    else
    {
    	return $selecter;
    }
    }


function languageDropdown($surveyid,$selected)
{
	$slangs = GetAdditionalLanguagesFromSurveyID($surveyid);
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	array_unshift($slangs,$baselang);
	$html = "<select class='listboxquestions' name='langselect' onchange=\"window.open(this.options[this.selectedIndex].value, '_self')\">\n";
	foreach ($slangs as $lang)
	{
		if ($lang == $selected) $html .= "\t<option value='{$_SERVER['PHP_SELF']}?action=dataentry&sid={$surveyid}&language={$lang}' selected='selected'>".getLanguageNameFromCode($lang,false)."</option>\n";
		if ($lang != $selected) $html .= "\t<option value='{$_SERVER['PHP_SELF']}?action=dataentry&sid={$surveyid}&language={$lang}'>".getLanguageNameFromCode($lang,false)."</option>\n";
	}
	$html .= "</select>";
	return $html;
}

function languageDropdownClean($surveyid,$selected)
{
	$slangs = GetAdditionalLanguagesFromSurveyID($surveyid);
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	array_unshift($slangs,$baselang);
	$html = "<select class='listboxquestions' name='language'>\n";
	foreach ($slangs as $lang)
	{
		if ($lang == $selected) $html .= "\t<option value='$lang' selected='selected'>".getLanguageNameFromCode($lang,false)."</option>\n";
		if ($lang != $selected) $html .= "\t<option value='$lang'>".getLanguageNameFromCode($lang,false)."</option>\n";
	}
	$html .= "</select>";
	return $html;
}

function include2var($file)
//This function includes a file but doesn't output it - instead it writes it into the return variable
// by Carsten Schmitz
{
   ob_start();
   include $file;
   $output = ob_get_contents();
   @ob_end_clean();
   return $output;
} 

function BuildCSVFromQuery($Query)
{
	global $dbprefix, $connect;
	$QueryResult = db_execute_assoc($Query) or safe_die ("ERROR: $QueryResult<br />".$connect->ErrorMsg()); //safe
	preg_match('/FROM (\w+)( |,)/i', $Query, $MatchResults);
	$TableName = $MatchResults[1];;
	if ($dbprefix)
	{
		$TableName = substr($TableName, strlen($dbprefix), strlen($TableName));
	}
	$Output = "\n#\n# " . strtoupper($TableName) . " TABLE\n#\n";
	$HeaderDone = false;	$ColumnNames = "";
	while ($Row = $QueryResult->FetchRow())
	{

       if (!$HeaderDone)
       {
    		foreach ($Row as $Key=>$Value)
    		{
    			$ColumnNames .= CSVEscape($Key).","; //Add all the column names together
    		}
			$ColumnNames = substr($ColumnNames, 0, -1); //strip off last comma space
     		$Output .= "$ColumnNames\n";
    		$HeaderDone=true;
       }
		$ColumnValues = "";
		foreach ($Row as $Key=>$Value)
		{
			$Value=str_replace("\r\n", "\n", $Value);
			$Value=str_replace("\r", "\n", $Value);
			$ColumnValues .= CSVEscape($Value) . ",";
		}
		$ColumnValues = substr($ColumnValues, 0, -1); //strip off last comma space
		$Output .= str_replace("\n","\\n","$ColumnValues")."\n";
	}
	return $Output;
}

function CSVEscape($str) 
{
   return '"' . str_replace('"','""', $str) . '"';
}

function convertCSVRowToArray($string, $seperator, $quotechar) 
{
	$fields=preg_split('/,(?=([^"]*"[^"]*")*(?![^"]*"))/',trim($string));
	$fields=array_map('CSVUnquote',$fields);
	return $fields;
}

function CSVUnquote($field)
// This function removes surrounding and masking quotes from the CSV field
// c_schmitz
{
	//print $field.":";
	$field = preg_replace ("/^\040*\"/", "", $field);
	$field = preg_replace ("/\"\040*$/", "", $field);
    $field=str_replace('""','"',$field);
    //print $field."\n";
    return $field;
}

/**
* CleanLanguagesFromSurvey() removes any languages from survey tables that are not in the passed list
* @param string $sid - the currently selected survey
* @param string $availlangs - space seperated list of additional languages in survey
* @return bool - always returns true
*/
function CleanLanguagesFromSurvey($sid, $availlangs)
{
	global $connect;
	$sid=sanitize_int($sid);
	$baselang = GetBaseLanguageFromSurveyID($sid);
	
	if (!empty($availlangs) && $availlangs != " ")
	{
		$availlangs=sanitize_languagecodeS($availlangs);
        $langs = explode(" ",$availlangs);
		if($langs[count($langs)-1] == "") array_pop($langs);
	}
	
	$sqllang = "language <> '".$baselang."' ";;
	
	if (!empty($availlangs) && $availlangs != " ")
	{
		foreach ($langs as $lang)
		{
			$sqllang .= "and language <> '".$lang."' ";
		}
	}
	
	// Remove From Answers Table
	$query = "SELECT qid FROM ".db_table_name('questions')." WHERE sid='{$sid}' and ($sqllang)";
	$qidresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());    //Checked
	while ($qrow =  $qidresult->FetchRow())
	{
		$myqid = $qrow[0];
		$query = "DELETE FROM ".db_table_name('answers')." WHERE qid='$myqid' and ($sqllang)";
		$connect->Execute($query) or safe_die($connect->ErrorMsg());    //Checked
	}
	
	// Remove From Questions Table
	$query = "DELETE FROM ".db_table_name('questions')." WHERE sid='{$sid}' and ($sqllang)";
	$connect->Execute($query) or safe_die($connect->ErrorMsg());   //Checked
	
	// Remove From Groups Table
	$query = "DELETE FROM ".db_table_name('groups')." WHERE sid='{$sid}' and ($sqllang)";
	$connect->Execute($query) or safe_die($connect->ErrorMsg());   //Checked
	
	return true;
}

/**
* FixLanguageConsistency() fixes missing groups,questions,answers for languages on a survey
* @param string $sid - the currently selected survey
* @param string $availlangs - space seperated list of additional languages in survey
* @return bool - always returns true
*/
function FixLanguageConsistency($sid, $availlangs)
{
	global $connect, $databasetype;
	
	if (!empty($availlangs) && $availlangs != " ")
	{
		$availlangs=sanitize_languagecodeS($availlangs);
        $langs = explode(" ",$availlangs);
		if($langs[count($langs)-1] == "") array_pop($langs);
	} else {
		return true;
	}
	
	$baselang = GetBaseLanguageFromSurveyID($sid);
	$sid=sanitize_int($sid);
	$query = "SELECT * FROM ".db_table_name('groups')." WHERE sid='{$sid}' AND language='{$baselang}'  ORDER BY group_order";
	$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());  //Checked
	if ($result->RecordCount() > 0)
	{
		while($group = $result->FetchRow())
		{
			foreach ($langs as $lang)
			{
				$query = "SELECT gid FROM ".db_table_name('groups')." WHERE sid='{$sid}' AND gid='{$group['gid']}' AND language='{$lang}'";
				$gresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg()); //Checked
				if ($gresult->RecordCount() < 1)
				{
                    if ($databasetype=='odbc_mssql') {$connect->Execute("SET IDENTITY_INSERT ".db_table_name('groups')." ON");}   //Checked
					$query = "INSERT INTO ".db_table_name('groups')." (gid,sid,group_name,group_order,description,language) VALUES('{$group['gid']}','{$group['sid']}',".db_quoteall($group['group_name']).",'{$group['group_order']}',".db_quoteall($group['description']).",'{$lang}')";  
					$connect->Execute($query) or safe_die($connect->ErrorMsg());  //Checked
                    if ($databasetype=='odbc_mssql') {$connect->Execute("SET IDENTITY_INSERT ".db_table_name('groups')." OFF");}   //Checked
				}
			}
			reset($langs);
		}
	}
	
	$quests = array();
	$query = "SELECT * FROM ".db_table_name('questions')." WHERE sid='{$sid}' AND language='{$baselang}' ORDER BY question_order";
	$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());  //Checked
	if ($result->RecordCount() > 0)
	{
		while($question = $result->FetchRow())
		{
			array_push($quests,$question['qid']);
			foreach ($langs as $lang)
			{
				$query = "SELECT qid FROM ".db_table_name('questions')." WHERE sid='{$sid}' AND qid='{$question['qid']}' AND language='{$lang}'";
				$gresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());   //Checked
				if ($gresult->RecordCount() < 1)
				{
                    if ($databasetype=='odbc_mssql') {@$connect->Execute("SET IDENTITY_INSERT ".db_table_name('questions')." ON");}    //Checked
					$query = "INSERT INTO ".db_table_name('questions')." (qid,sid,gid,type,title,question,preg,help,other,mandatory,lid,question_order,language) VALUES('{$question['qid']}','{$question['sid']}','{$question['gid']}','{$question['type']}',".db_quoteall($question['title']).",".db_quoteall($question['question']).",".db_quoteall($question['preg']).",".db_quoteall($question['help']).",'{$question['other']}','{$question['mandatory']}','{$question['lid']}','{$question['question_order']}','{$lang}')";
					$connect->Execute($query) or safe_die($query."<br />".$connect->ErrorMsg());   //Checked
                    if ($databasetype=='odbc_mssql') {$connect->Execute("SET IDENTITY_INSERT ".db_table_name('questions')." OFF");}      //Checked
				}
			}
			reset($langs);
		}

		$sqlans = "";
		foreach ($quests as $quest)
		{
			$sqlans .= " OR qid = '".$quest."' ";
		}

		$query = "SELECT * FROM ".db_table_name('answers')." WHERE language='{$baselang}' and (".trim($sqlans,' OR').") ORDER BY qid, code";
		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg()); //Checked
		if ($result->RecordCount() > 0)
		{
			while($answer = $result->FetchRow())
			{
				foreach ($langs as $lang)
				{
					$query = "SELECT qid FROM ".db_table_name('answers')." WHERE code='{$answer['code']}' AND qid='{$answer['qid']}' AND language='{$lang}'";
					$gresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());  //Checked
					if ($gresult->RecordCount() < 1)
					{
                        if ($databasetype=='odbc_mssql') {@$connect->Execute("SET IDENTITY_INSERT ".db_table_name('answers')." ON");}    //Checked
						$query = "INSERT INTO ".db_table_name('answers')." (qid,code,answer,default_value,sortorder,language) VALUES('{$answer['qid']}',".db_quoteall($answer['code']).",".db_quoteall($answer['answer']).",".db_quoteall($answer['default_value']).",'{$answer['sortorder']}','{$lang}')";
						$connect->Execute($query) or safe_die($connect->ErrorMsg()); //Checked
                        if ($databasetype=='odbc_mssql') {$connect->Execute("SET IDENTITY_INSERT ".db_table_name('answers')." OFF");}   //Checked
					}
				}
				reset($langs);
			}
		}
	}
	return true;
}

/**
* GetGroupDepsForConditions() get Dependencies between groups caused by conditions 
* @param string $sid - the currently selected survey
* @param string $depgid - (optionnal) get only the dependencies applying to the group with gid depgid
* @param string $targgid - (optionnal) get only the dependencies for groups dependents on group targgid
* @param string $index-by - (optionnal) "by-depgid" for result indexed with $res[$depgid][$targgid]
* 					"by-targgid" for result indexed with $res[$targgid][$depgid]
* @return array - returns an array describing the conditions or NULL if no dependecy is found
*
* Example outupt assumin $index-by="by-depgid":
*Array 
*(
*    [125] => Array				// Group Id 125 is dependent on
*        (
*            [123] => Array			// Group Id 123
*                (
*                    [depgpname] => G3		// GID-125 has name G3
*                    [targetgpname] => G1	// GID-123 has name G1
*                    [conditions] => Array
*                        (
*                            [189] => Array	// Because Question Id 189
*                                (
*                                    [0] => 9	// Have condition 9 set
*                                    [1] => 10	// and condition 10 set
*                                    [2] => 14  // and condition 14 set
*                                )
*
*                        )
*
*                )
*
*            [124] => Array			// GID 125 is also dependent on GID 124
*                (
*                    [depgpname] => G3
*                    [targetgpname] => G2
*                    [conditions] => Array
*                        (
*                            [189] => Array	// Because Question Id 189 have conditions set
*                                (
*                                    [0] => 11
*                                )
*
*                            [215] => Array	// And because Question Id 215 have conditions set
*                                (
*                                    [0] => 12
*                                )
*
*                        )
*
*                )
*
*        )
*
*)
*
* Usage example:
*	* Get all group dependencies for SID $sid indexed by depgid:
*		$result=GetGroupDepsForConditions($sid);
*	* Get all group dependencies for GID $gid in survey $sid indexed by depgid:
*		$result=GetGroupDepsForConditions($sid,$gid);
*	* Get all group dependents on group $gid in survey $sid indexed by targgid:
*		$result=GetGroupDepsForConditions($sid,"all",$gid,"by-targgid");
*/
function GetGroupDepsForConditions($sid,$depgid="all",$targgid="all",$indexby="by-depgid")
{
	global $connect, $clang;
    $sid=sanitize_int($sid);
	$condarray = Array();

	$sqldepgid="";
	$sqltarggid="";
	if ($depgid != "all") { $depgid = sanitize_int($depgid); $sqldepgid="AND tq.gid=$depgid";}
	if ($targgid != "all") {$targgid = sanitize_int($targgid); $sqltarggid="AND tq2.gid=$targgid";}

	$baselang = GetBaseLanguageFromSurveyID($sid);
	$condquery = "SELECT tg.gid as depgid, tg.group_name as depgpname, "
		. "tg2.gid as targgid, tg2.group_name as targgpname, tq.qid as depqid, tc.cid FROM "
		. db_table_name('conditions')." AS tc, "
		. db_table_name('questions')." AS tq, "
		. db_table_name('questions')." AS tq2, "
		. db_table_name('groups')." AS tg ,"
		. db_table_name('groups')." AS tg2 "
		. "WHERE tq.language='{$baselang}' AND tq2.language='{$baselang}' AND tg.language='{$baselang}' AND tg2.language='{$baselang}' AND tc.qid = tq.qid AND tq.sid=$sid "
		. "AND tq.gid = tg.gid AND tg2.gid = tq2.gid "
		. "AND tq2.qid=tc.cqid AND tq.gid != tg2.gid $sqldepgid $sqltarggid";
	$condresult=db_execute_assoc($condquery) or safe_die($connect->ErrorMsg());   //Checked
	
	if ($condresult->RecordCount() > 0) {
		while ($condrow = $condresult->FetchRow())
		{

			switch ($indexby)
			{
				case "by-depgid":
				$depgid=$condrow['depgid'];
				$targetgid=$condrow['targgid'];
				$depqid=$condrow['depqid'];
				$cid=$condrow['cid'];
				$condarray[$depgid][$targetgid]['depgpname'] = $condrow['depgpname'];
				$condarray[$depgid][$targetgid]['targetgpname'] = $condrow['targgpname'];
				$condarray[$depgid][$targetgid]['conditions'][$depqid][]=$cid;
				break;

				case "by-targgid":
				$depgid=$condrow['depgid'];
				$targetgid=$condrow['targgid'];
				$depqid=$condrow['depqid'];
				$cid=$condrow['cid'];
				$condarray[$targetgid][$depgid]['depgpname'] = $condrow['depgpname'];
				$condarray[$targetgid][$depgid]['targetgpname'] = $condrow['targgpname'];
				$condarray[$targetgid][$depgid]['conditions'][$depqid][] = $cid;
				break;
			}
		}
		return $condarray;
	}
	return null;
}

/**
* GetQuestDepsForConditions() get Dependencies between groups caused by conditions 
* @param string $sid - the currently selected survey
* @param string $gid - (optionnal) only search dependecies inside the Group Id $gid
* @param string $depqid - (optionnal) get only the dependencies applying to the question with qid depqid
* @param string $targqid - (optionnal) get only the dependencies for questions dependents on question Id targqid
* @param string $index-by - (optionnal) "by-depqid" for result indexed with $res[$depqid][$targqid]
* 					"by-targqid" for result indexed with $res[$targqid][$depqid]
* @return array - returns an array describing the conditions or NULL if no dependecy is found
*
* Example outupt assumin $index-by="by-depqid":
*Array
*(
*    [184] => Array		// Question Id 184
*        (
*            [183] => Array	// Depends on Question Id 183
*                (
*                    [0] => 5	// Because of condition Id 5
*                )
*
*        )
*
*)
*
* Usage example:
*	* Get all questions dependencies for Survey $sid and group $gid indexed by depqid:
*		$result=GetQuestDepsForConditions($sid,$gid);
*	* Get all questions dependencies for question $qid in survey/group $sid/$gid indexed by depqid:
*		$result=GetGroupDepsForConditions($sid,$gid,$qid);
*	* Get all questions dependents on question $qid in survey/group $sid/$gid indexed by targqid:
*		$result=GetGroupDepsForConditions($sid,$gid,"all",$qid,"by-targgid");
*/
function GetQuestDepsForConditions($sid,$gid="all",$depqid="all",$targqid="all",$indexby="by-depqid", $searchscope="samegroup")
{
	global $connect, $clang;
	$condarray = Array();
	
	$baselang = GetBaseLanguageFromSurveyID($sid);
	$sqlgid="";
	$sqldepqid="";
	$sqltargqid="";
	$sqlsearchscope="";
	if ($gid != "all") {$gid = sanitize_int($gid); $sqlgid="AND tq.gid=$gid";}
	if ($depqid != "all") {$depqid = sanitize_int($depqid); $sqldepqid="AND tq.qid=$depqid";}
	if ($targqid != "all") {$targqid = sanitize_int($targqid); $sqltargqid="AND tq2.qid=$targqid";}
	if ($searchscope == "samegroup") {$sqlsearchscope="AND tq2.gid=tq.gid";}

	$condquery = "SELECT tq.qid as depqid, tq2.qid as targqid, tc.cid FROM "
		. db_table_name('conditions')." AS tc, "
		. db_table_name('questions')." AS tq, "
		. db_table_name('questions')." AS tq2 "
		. "WHERE tq.language='{$baselang}' AND tq2.language='{$baselang}' AND tc.qid = tq.qid AND tq.sid=$sid "
		. "AND  tq2.qid=tc.cqid $sqlsearchscope $sqlgid $sqldepqid $sqltargqid";

		$condresult=db_execute_assoc($condquery) or safe_die($connect->ErrorMsg());    //Checked

	if ($condresult->RecordCount() > 0) {
		while ($condrow = $condresult->FetchRow())
		{
			$depqid=$condrow['depqid'];
			$targetqid=$condrow['targqid'];
			$condid=$condrow['cid'];
			switch ($indexby)
			{
				case "by-depqid":
				$condarray[$depqid][$targetqid][] = $condid;
				break;

				case "by-targqid":
				$condarray[$targetqid][$depqid][] = $condid;
				break;
			}
		}
		return $condarray;
	}
	return null;
}


/**
* checkMovequestionConstraintsForConditions() 
* @param string $sid - the currently selected survey
* @param string $qid - qid of the question you want to check possible moves 
* @param string $newgid - (optionnal) get only constraints when trying to move to this particular GroupId
*                                     otherwise, get all moves constraints for this question
*
* @return array - returns an array describing the conditions
*                 Array
*                 (
*                   ['notAbove'] = null | Array
*						(
*						  Array ( gid1, group_order1, qid1, cid1 )
*						)
*                   ['notBelow'] = null | Array
*						(
*						  Array ( gid2, group_order2, qid2, cid2 )
*						)
*                 )
*
* This should be read as:
*    - this question can't be move above group gid1 in position group_order1 because of the condition cid1 on question qid1
*    - this question can't be move below group gid2 in position group_order2 because of the condition cid2 on question qid2
*
*/
function checkMovequestionConstraintsForConditions($sid,$qid,$newgid="all")
{
	global $connect;
	$resarray=Array();
	$resarray['notAbove']=null; // defaults to no constraint
	$resarray['notBelow']=null; // defaults to no constraint
    $sid=sanitize_int($sid);
    $qid=sanitize_int($qid);

	if ($newgid != "all")
	{
        $newgid=sanitize_int($newgid);
		$newgorder=getGroupOrder($sid,$newgid);
	}
	else
	{
		$neworder=""; // Not used in this case
	}

	$baselang = GetBaseLanguageFromSurveyID($sid);
	
	// First look for 'my dependencies': questions on which I have set conditions
	$condquery = "SELECT tq.qid as depqid, tq.gid as depgid, tg.group_order as depgorder, "
		. "tq2.qid as targqid, tq2.gid as targgid, tg2.group_order as targgorder, "
		. "tc.cid FROM "
		. db_table_name('conditions')." AS tc, "
		. db_table_name('questions')." AS tq, "
		. db_table_name('questions')." AS tq2, "
		. db_table_name('groups')." AS tg, "
		. db_table_name('groups')." AS tg2 "
		. "WHERE tq.language='{$baselang}' AND tq2.language='{$baselang}' AND tc.qid = tq.qid AND tq.sid=$sid "
		. "AND  tq2.qid=tc.cqid AND tg.gid=tq.gid AND tg2.gid=tq2.gid AND tq.qid=$qid ORDER BY tg2.group_order DESC";
	
	$condresult=db_execute_assoc($condquery) or safe_die($connect->ErrorMsg());    //Checked

	if ($condresult->RecordCount() > 0) {

		while ($condrow = $condresult->FetchRow() )
		{
			// This Question can go up to the minimum GID on the 1st row
			$depqid=$condrow['depqid'];
			$depgid=$condrow['depgid'];
			$depgorder=$condrow['depgorder'];
			$targetqid=$condrow['targqid'];
			$targetgid=$condrow['targgid'];
			$targetgorder=$condrow['targgorder'];
			$condid=$condrow['cid'];
			//echo "This question can't go above to GID=$targetgid/order=$targetgorder because of CID=$condid";
			if ($newgid != "all")
			{ // Get only constraints when trying to move to this group
				if ($newgorder < $targetgorder)
				{
					$resarray['notAbove'][]=Array($targetgid,$targetgorder,$depqid,$condid);
				}
			}
			else
			{ // get all moves constraints
				$resarray['notAbove'][]=Array($targetgid,$targetgorder,$depqid,$condid);	
			}
		}
	}

	// Secondly look for 'questions dependent on me': questions that have conditions on my answers
	$condquery = "SELECT tq.qid as depqid, tq.gid as depgid, tg.group_order as depgorder, "
		. "tq2.qid as targqid, tq2.gid as targgid, tg2.group_order as targgorder, "
		. "tc.cid FROM "
		. db_table_name('conditions')." AS tc, "
		. db_table_name('questions')." AS tq, "
		. db_table_name('questions')." AS tq2, "
		. db_table_name('groups')." AS tg, "
		. db_table_name('groups')." AS tg2 "
		. "WHERE tq.language='{$baselang}' AND tq2.language='{$baselang}' AND tc.qid = tq.qid AND tq.sid=$sid "
		. "AND  tq2.qid=tc.cqid AND tg.gid=tq.gid AND tg2.gid=tq2.gid AND tq2.qid=$qid ORDER BY tg.group_order";
	
	$condresult=db_execute_assoc($condquery) or safe_die($connect->ErrorMsg());        //Checked    

	if ($condresult->RecordCount() > 0) {

		while ($condrow = $condresult->FetchRow())
		{
			// This Question can go down to the maximum GID on the 1st row
			$depqid=$condrow['depqid'];
			$depgid=$condrow['depgid'];
			$depgorder=$condrow['depgorder'];
			$targetqid=$condrow['targqid'];
			$targetgid=$condrow['targgid'];
			$targetgorder=$condrow['targgorder'];
			$condid=$condrow['cid'];
			//echo "This question can't go below to GID=$depgid/order=$depgorder because of CID=$condid";
			if ($newgid != "all")
			{ // Get only constraints when trying to move to this group
				if ($newgorder > $depgorder)
				{
					$resarray['notBelow'][]=Array($depgid,$depgorder,$depqid,$condid);
				}
			}
			else
			{ // get all moves constraints
				$resarray['notBelow'][]=Array($depgid,$depgorder,$depqid,$condid);
			}
		}
	}
	return $resarray;
}


// array_combine function is PHP5 only so we have to provide 
// our own in case it doesn't exist as in PHP 4
if (!function_exists('array_combine')) {
   function array_combine($a, $b) {
       $c = array();
       if (is_array($a) && is_array($b))
           while (list(, $va) = each($a))
               if (list(, $vb) = each($b))
                   $c[$va] = $vb;
               else
                   break 1;
       return $c;
   }
}

if (!function_exists("stripos")) {
  function stripos($str,$needle,$offset=0)
  {
      return strpos(strtolower($str),strtolower($needle),$offset);
  }
}

if(!function_exists('str_ireplace')) {
    function str_ireplace($search,$replace,$subject) 
    {
        $search = preg_quote($search, "/");
        return preg_replace("/".$search."/i", $replace, $subject); 
    } 
}

function incompleteAnsFilterstate()
{
	global $filterout_incomplete_answers;
	$letsfliter='';
	$letsfilter = returnglobal('filterinc'); //read get/post filterinc

	// first let's initialize the incompleteanswers session variable
	if ($letsfilter != '')
	{ // use the read value if not empty
		$_SESSION['incompleteanswers']=$letsfilter;
	}
	elseif (!isset($_SESSION['incompleteanswers']))
	{ // sets default variable value from config file
		$_SESSION['incompleteanswers'] = $filterout_incomplete_answers;
	}

	if  ($_SESSION['incompleteanswers']=='filter') {
		return true;
	}
	elseif ($_SESSION['incompleteanswers']=='show') {
		return false;
	}
	else
	{ // last resort is to prevent filtering
		return false;
	}
}

/**
* captcha_enabled($screen, $usecaptchamode) 
* @param string $screen - the screen name for which to test captcha activation
*
* @return boolean - returns true if captcha must be enabled
**/
function captcha_enabled($screen, $captchamode='')
{
	switch($screen)
	{
	case 'registrationscreen':
		if ($captchamode == 'A' ||
			$captchamode == 'B' ||
			$captchamode == 'D' ||
			$captchamode == 'R')
		{
			return true;
		}
		else
		{
			return false;
		}
		break;
	case 'surveyaccessscreen':
		if ($captchamode == 'A' ||
			$captchamode == 'B' ||
			$captchamode == 'C' ||
			$captchamode == 'X')
		{
			return true;
		}
		else
		{
			return false;
		}
		break;
	case 'saveandloadscreen':
		if ($captchamode == 'A' ||
			$captchamode == 'C' ||
			$captchamode == 'D' ||
			$captchamode == 'S')
		{
			return true;
		}
		else
		{
			return false;
		}
		return true;
	break;
	default:
		return true;
		break;
	}
}

// used for import[survey|questions|groups]
function convertCsvreturn2return($string)
{
        return str_replace('\n', "\n", $string);
}

// Checks that each object from an array of CSV data
// [question-rows,answer-rows,labelsets-row] 
// supports iat least a given language
//
// param:
// $csvarray : array with a line of csv data per row
// $idkeysarray: array of integers giving the csv-row numbers of the object keys
// $langfieldnum: integer giving the csv-row number of the language(s) filed
//		==> the language field  can be a single language code or a 
//		    space separated language code list
// $langcode: the language code to be tested
// $hasheader: if we should strip off the first line (if it contains headers)
//
function  bDoesImportarraySupportsLanguage($csvarray,$idkeysarray,$langfieldnum,$langcode, $hasheader = false)
{
	// An array with one row per object id and langsupport status as value
	$objlangsupportarray=Array();
	if ($hasheader === true)
	{ // stripping first row to skip headers if any
		array_shift($csvarray);
	}

	foreach ($csvarray as $csvrow)
	{
		$rowcontents = convertCSVRowToArray($csvrow,',','"');		
		$rowid = "";
		foreach ($idkeysarray as $idfieldnum)
		{
			$rowid .= $rowcontents[$idfieldnum]."-";
		}
		$rowlangarray = split (" ", $rowcontents[$langfieldnum]);
		if (!isset($objlangsupportarray[$rowid]))
		{
			if (array_search($langcode,$rowlangarray)!== false)
			{
				$objlangsupportarray[$rowid] = "true";
			}
			else
			{
				$objlangsupportarray[$rowid] = "false";
			}
		}
		else
		{
			if ($objlangsupportarray[$rowid] == "false" && 
				array_search($langcode,$rowlangarray) !== false)
			{
				$objlangsupportarray[$rowid] = "true";
			}
		}
	} // end foreach rown

	// If any of the object doesn't support the given language, return false
	if (array_search("false",$objlangsupportarray) === false)
	{
		return true;
	}
	else
	{
		return false;
	}
}

// returns the answerText from session vraiable  corresponding to a question code
//
function retrieve_Answer($code)
{
	//This function checks to see if there is an answer saved in the survey session
	//data that matches the $code. If it does, it returns that data.
	//It is used when building a questions text to allow incorporating the answer
	//to an earlier question into the text of a later question.
	//IE: Q1: What is your name? [Jason]
	//    Q2: Hi [Jason] how are you ?
	//This function is called from the retriveAnswers function.
	global $dbprefix, $connect, $clang;
	//Find question details
	if (isset($_SESSION[$code]))
	{
		$questiondetails=getsidgidqidaidtype($code);
		//the getsidgidqidaidtype function is in common.php and returns
		//a SurveyID, GroupID, QuestionID and an Answer code
		//extracted from a "fieldname" - ie: 1X2X3a
		// also returns question type

		if ($questiondetails['type'] == "M" ||
			$questiondetails['type'] == "P")
		{
			$query="SELECT * FROM {$dbprefix}answers WHERE qid='".$questiondetails['qid']."' AND language='".$_SESSION['s_lang']."'";
			$result=db_execute_assoc($query) or safe_die("Error getting answer<br />$query<br />".$connect->ErrorMsg());  //Checked
			while($row=$result->FetchRow())
			{
				if (isset($_SESSION[$code.$row['code']]) && $_SESSION[$code.$row['code']] == "Y")
				{
					$returns[] = $row['answer'];
				}
			}
			if (isset($_SESSION[$code."other"]) && $_SESSION[$code."other"])
			{
				$returns[]=$_SESSION[$code."other"];
			}
			if (isset($returns))
			{
				$return=implode(", ", $returns);
				if (strpos($return, ","))
				{
					$return=substr_replace($return, " &", strrpos($return, ","), 1);
				}
			}
			else
			{
				$return=$clang->gT("No answer");
			}
		}
		elseif (!$_SESSION[$code])
		{
			$return=$clang->gT("No answer");
		}
		else
		{
			$return=getextendedanswer($code, $_SESSION[$code], 'INSERTANS');
		}
	}
	else
	{
		$return=$clang->gT("Error") . "($code)";
	}
	return html_escape($return);
}

// returns true if thesurvey has a token table defined
function bHasSurveyGotTokentable($thesurvey, $sid=null)
{
	global $connect;
	if (is_array($thesurvey))
	{
		$surveyid = $thesurvey['sid'];
	}
	elseif (!is_null($sid))
	{
		$surveyid = $sid;
	}

	$tablelist = $connect->MetaTables() or safe_die ("Error getting tokens<br />".$connect->ErrorMsg());
	foreach ($tablelist as $tbl)
	{
		if (db_quote_id($tbl) == db_table_name('tokens_'.$surveyid)) 
		{
			return true;
		}
	}
	return false;
}

// Returns false if the survey is anonymous, but answers must be datestamp
// and a token table exists: in this case the completed field of a token
// will contain 'Y' instead of the submitted date to ensure privacy
// Returns true otherwise
function bIsTokenCompletedDatestamped($thesurvey)
{
	if ($thesurvey['private'] == 'Y' &&
		bHasSurveyGotTokentable($thesurvey) )
	{
		return false;
	}
	else
	{
		return true;
	}
}

function date_shift($date, $dformat, $shift)
/* example usage

$date = "2006-12-31 21:00";
$shift "+6 hours"; // could be days, weeks... see function strtotime() for usage

echo sql_date_shift($date, "Y-m-d H:i:s", $shift);

// will output: 2007-01-01 03:00:00
*/
{
return date($dformat, strtotime($shift, strtotime($date)));
}

function mydebug($strOutput)
{
  $datei = fopen("d:\debug.txt","a+");
  fwrite($datei, "$strOutput \n");
  fclose($datei);
}
function mydebug_var($strOutput)
{
  $datei = fopen("d:\debug.txt","a+");
  fwrite($datei, var_export($strOutput, TRUE));
  fwrite($datei, "\n");
  fclose($datei);
}

// getBounceEmail: returns email used to receive error notifications
function getBounceEmail($surveyid)
{
    $surveyInfo=getSurveyInfo($surveyid);
	
	if ($surveyInfo['bounce_email'] == '')
	{
		return null; // will be converted to from in MailText
	}
	else
	{
		return $surveyInfo['bounce_email'];
	}	
}

// getEmailFormat: returns email format for the survey
// returns 'text' or 'html'
function getEmailFormat($surveyid)
{

	$surveyInfo=getSurveyInfo($surveyid);
	if ($surveyInfo['htmlemail'] == 'Y')
	{
		return 'html';
	}
	else
	{
		return 'text';
	}	

}

// Check if user has manage rights for a template
function hasTemplateManageRights($userid, $templatefolder) {
      global $connect;
      global $dbprefix;
      $userid=sanitize_int($userid);
      $templatefolder=sanitize_paranoid_string($templatefolder);
      $query = "SELECT ".db_quote_id('use')." FROM {$dbprefix}templates_rights WHERE uid=".$userid." AND folder LIKE '".$templatefolder."'";

      $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());  //Safe

      if ($result->RecordCount() == 0)	return false;

      $row = $result->FetchRow();

      return $row["use"];
}

// This function creates an incrementing answer code based on the previous source-code
function getNextCode($sourcecode)
{
   $i=1; 
   $found=true;
   $foundnumber=-1;
   while ($i<=strlen($sourcecode) && $found)   
   {
     $found=is_numeric(substr($sourcecode,-$i));
     if ($found) 
        {
        $foundnumber=substr($sourcecode,-$i);
        $i++;
        }
   }
   if ($foundnumber==-1) 
    {
        return($sourcecode);
    }
    else 
    {
       $foundnumber++; 
       $result=substr($sourcecode,0,strlen($sourcecode)-$i+1).$foundnumber;
       return($result);
    }
    
}

// translink
function translink($type, $oldid,$newid,$text)
{
	if (!isset($_POST['translinksfields']))
	{
		return $text;
	}

		if ($type == 'survey')
		{
			$pattern = "upload/surveys/$oldid/";
			$replace = "upload/surveys/$newid/";
			return ereg_replace($pattern, $replace, $text);
		}
		elseif ($type == 'label')
		{
			$pattern = "upload/labels/$oldid/";
			$replace = "upload/labels/$newid/";
			return ereg_replace($pattern, $replace, $text);
		}
		else
		{
			return $text;
		}
}

function transInsertAns($newsid,$oldsid,$fieldnames)
{ 
	global $connect, $dbprefix;

	if (!isset($_POST['translinksfields']))
	{
		return;
	}

    $newsid=sanitize_int($newsid);
    $oldsid=sanitize_int($oldsid);
	$sql = "SELECT qid, language, question, help from {$dbprefix}questions WHERE sid=".$newsid." AND question LIKE '%{INSERTANS:".$oldsid."X%' OR help LIKE '%{INSERTANS:".$oldsid."X%'";
	$res = db_execute_assoc($sql) or safe_die("Can't read question table in transInsertAns ".$connect->ErrorMsg());     // Checked

	while ($qentry = $res->FetchRow())
	{
		$question = $qentry['question'];
		$help = $qentry['help'];
		$qid = $qentry['qid'];
		$language = $qentry['language'];

		foreach ($fieldnames as $fnrow)
		{
			$pattern = "\{INSERTANS:".$fnrow['oldfieldname']."\}";
			$replacement = "\{INSERTANS:".$fnrow['newfieldname']."\}";
			$question=ereg_replace($pattern, $replacement, $question);
			$help=ereg_replace($pattern, $replacement, $help);
		}

		if (strcmp($question,$qentry['question']) !=0 ||
			strcmp($help,$qentry['help']) !=0)
		{
			// Update Field
			$sqlupdate = "UPDATE {$dbprefix}questions SET question='".$question."', help='".$help."' WHERE qid=$qid AND language='$language'";
			$updateres=$connect->Execute($sqlupdate) or safe_die ("Couldn't update INSERTANS in question<br />$sqlupdate<br />".$connect->ErrorMsg());    //Checked
		} // Enf if modified
	} // end while qentry
}

function hasResources($id,$type='survey')
{
	global $publicdir;
	$dirname = "$publicdir/upload";

	if ($type == 'survey')
	{
		$dirname .= "/surveys/$id";
	}
	elseif ($type == 'label')
	{
		$dirname .= "/labels/$id";
	}
	else
	{
		return false;
	}

	if (is_dir($dirname) && $dh=opendir($dirname))
	{
		while(($entry = readdir($dh)) !== false)
		{
			if($entry !== '.' && $entry !== '..')
			{
				return true;
				break;
			}
		}
		closedir($dh);
	}
	else
	{
		return false;
	}

	return false;
}


function randomkey($length)
{
	$pattern = "23456789abcdefghijkmnpqrstuvwxyz";
	$patternlength = strlen($pattern)-1; 
	for($i=0;$i<$length;$i++)
	{
		if(isset($key))
		$key .= $pattern{rand(0,$patternlength)};
		else
		$key = $pattern{rand(0,$patternlength)};
	}
	return $key;
}


                           

function conditional_nl2br($mytext,$ishtml)
{
	if ($ishtml === true)
	{
		// $mytext has been processed by clang->gT with html mode
		// and thus \n has already been translated to &#10;
		return str_replace('&#10;', '<br />',$mytext);
	}
	else
	{
		return $mytext;
	}
}

function conditional2_nl2br($mytext,$ishtml)
{
	if ($ishtml === true)
	{
		return str_replace("\n", '<br />',$mytext);
	}
	else
	{
		return $mytext;
	}
}

function br2nl( $data ) {
     return preg_replace( '!<br.*>!iU', "\n", $data );
}

function getPopupHeight() 
{
    global $clang, $surveyid;
    
    $rowheight = 20;
    $height = 0;
    $bottomPad = 15;
    
    // header text height
    $htext = ceil(strlen($clang->gT("Please select a language:")) / 17);
    $height += $rowheight * $htext;
        
    // language list height
    $survlangs = GetAdditionalLanguagesFromSurveyID($surveyid);
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    $survlangs[] = $baselang;
    
    foreach ($survlangs as $lang)
    {
        $ltext = ceil(strlen(getLanguageNameFromCode($lang,false)) / 17);
        $height += $rowheight * $ltext;
        if ($ltext > 1) $height += ($ltext * 3);
    }

    // footer height
    $ftext = ceil(count($clang->gT("Cancel")) / 17);
    $height += $rowheight * $ftext;
    
    $height += $bottomPad;
    
    return $height;
}

function safe_die($text)
{
    //Only allowed tag: <br />
    $textarray=explode('<br />',$text);
    array_map('htmlspecialchars',$textarray);
    die(implode( '<br />',$textarray));
}

?>
