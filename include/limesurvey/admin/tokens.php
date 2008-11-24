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
* $Id: tokens.php 5107 2008-06-18 17:10:57Z c_schmitz $
*/


# TOKENS FILE

include_once("login_check.php");

if ($enableLdap)
{
	require_once(dirname(__FILE__).'/../config-ldap.php');
}
if (!isset($surveyid)) {$surveyid=returnglobal('sid');}
if (!isset($order)) {$order=returnglobal('order');}
if (!isset($limit)) {$limit=returnglobal('limit');}
if (!isset($start)) {$start=returnglobal('start');}
if (!isset($searchstring)) {$searchstring=returnglobal('searchstring');}
if (!isset($tokenid)) {$tokenid=returnglobal('tid');}
if (!isset($starttokenid)) {$starttokenid=sanitize_int(returnglobal('last_tid'));}

include_once("login_check.php");
include_once("database.php");


//$invitationBody = "Dear {FIRSTNAME},\n\nYou have been invited to participate in a survey.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}";
//$reminderBody = "Dear {FIRSTNAME},\n\nRecently we invited you to participate in a survey.\n\nWe note that you have not yet completed the survey, and wish to remind you that the survey is still available should you wish to take part.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}";
//$confirmationBody = "Dear {FIRSTNAME},\n\nThis email is to confirm that you have completed the survey titled {SURVEYNAME} and your response has been saved. Thank you for participating.\n\nIf you have any further questions about this email, please contact {ADMINNAME} on {ADMINEMAIL}.\n\nSincerely,\n\n{ADMINNAME}";
//$registrationBody = "Dear {FIRSTNAME},\n\nYou, or someone using your email address, have registered to participate in an online survey titled {SURVEYNAME}.\n\nTo complete this survey, click on the following URL:\n\n{SURVEYURL}\n\nIf you have any questions about this survey, or if you did not register to participate and believe this email is in error, please contact {ADMINNAME} at {ADMINEMAIL}.");


if ($subaction == "import" || $subaction == "upload" )  // THis array only needs to be defined for these two functions
{
    $encodingsarray = array("armscii8"=>$clang->gT("ARMSCII-8 Armenian")
		                   ,"ascii"=>$clang->gT("US ASCII")
		                   ,"auto"=>$clang->gT("Automatic")
		                   ,"big5"=>$clang->gT("Big5 Traditional Chinese")
		                   ,"binary"=>$clang->gT("Binary pseudo charset")
		                   ,"cp1250"=>$clang->gT("Windows Central European")
		                   ,"cp1251"=>$clang->gT("Windows Cyrillic")
		                   ,"cp1256"=>$clang->gT("Windows Arabic")
		                   ,"cp1257"=>$clang->gT("Windows Baltic")
		                   ,"cp850"=>$clang->gT("DOS West European")
		                   ,"cp852"=>$clang->gT("DOS Central European")
		                   ,"cp866"=>$clang->gT("DOS Russian")
		                   ,"cp932"=>$clang->gT("SJIS for Windows Japanese")
		                   ,"dec8"=>$clang->gT("DEC West European")
		                   ,"eucjpms"=>$clang->gT("UJIS for Windows Japanese")
		                   ,"euckr"=>$clang->gT("EUC-KR Korean")
		                   ,"gb2312"=>$clang->gT("GB2312 Simplified Chinese")
		                   ,"gbk"=>$clang->gT("GBK Simplified Chinese")
		                   ,"geostd8"=>$clang->gT("GEOSTD8 Georgian")
		                   ,"greek"=>$clang->gT("ISO 8859-7 Greek")
		                   ,"hebrew"=>$clang->gT("ISO 8859-8 Hebrew")
		                   ,"hp8"=>$clang->gT("HP West European")
		                   ,"keybcs2"=>$clang->gT("DOS Kamenicky Czech-Slovak")
		                   ,"koi8r"=>$clang->gT("KOI8-R Relcom Russian")
		                   ,"koi8u"=>$clang->gT("KOI8-U Ukrainian")
		                   ,"latin1"=>$clang->gT("cp1252 West European")
		                   ,"latin2"=>$clang->gT("ISO 8859-2 Central European")
		                   ,"latin5"=>$clang->gT("ISO 8859-9 Turkish")
		                   ,"latin7"=>$clang->gT("ISO 8859-13 Baltic")
		                   ,"macce"=>$clang->gT("Mac Central European")
		                   ,"macroman"=>$clang->gT("Mac West European")
		                   ,"sjis"=>$clang->gT("Shift-JIS Japanese")
		                   ,"swe7"=>$clang->gT("7bit Swedish")
		                   ,"tis620"=>$clang->gT("TIS620 Thai")
		                   ,"ucs2"=>$clang->gT("UCS-2 Unicode")
		                   ,"ujis"=>$clang->gT("EUC-JP Japanese")
		                   ,"utf8"=>$clang->gT("UTF-8 Unicode"));
   if (isset($_POST['csvcharset']) && $_POST['csvcharset'])  //sanitize charset - if encoding is not found sanitize to 'auto'
   {
   $uploadcharset=$_POST['csvcharset'];
   if (!array_key_exists($uploadcharset,$encodingsarray)) {$uploadcharset='auto';}
   $filterduplicatetoken=(isset($_POST['filterduplicatetoken']) && $_POST['filterduplicatetoken']=='on'); 
   $filterblankemail=(isset($_POST['filterblankemail']) && $_POST['filterblankemail']=='on'); 
   }
   					   
}
if ($subaction == "importldap" || $subaction == "uploadldap" )
{
   $filterduplicatetoken=(isset($_POST['filterduplicatetoken']) && $_POST['filterduplicatetoken']=='on'); 
   $filterblankemail=(isset($_POST['filterblankemail']) && $_POST['filterblankemail']=='on'); 
}


$tokenoutput = "";

$sumquery5 = "SELECT b.* FROM {$dbprefix}surveys AS a INNER JOIN {$dbprefix}surveys_rights AS b ON a.sid = b.sid WHERE a.sid=$surveyid AND b.uid = ".$_SESSION['loginID']; //Getting rights for this survey and user
$sumresult5 = db_execute_assoc($sumquery5);
$sumrows5 = $sumresult5->FetchRow();


if ($subaction == "export" && ( $sumrows5['export'] || $_SESSION['USER_RIGHT_SUPERADMIN'] == 1) )//EXPORT FEATURE SUBMITTED BY PIETERJAN HEYSE
{

	header("Content-Disposition: attachment; filename=tokens_".$surveyid.".csv");
	header("Content-type: text/comma-separated-values; charset=UTF-8");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Pragma: cache");

	$bquery = "SELECT * FROM ".db_table_name("tokens_$surveyid");
	$bquery .= " ORDER BY tid";

	$bresult = db_execute_assoc($bquery) or die ("$bquery<br />".htmlspecialchars($connect->ErrorMsg()));
	$bfieldcount=$bresult->FieldCount();

	$tokenoutput .= "firstname, lastname, email, emailstatus, token, language code, attribute1, attribute2, tid\n";
	while ($brow = $bresult->FetchRow())
	{
		$tokenoutput .= '"'.trim($brow['firstname'])."\",";
		$tokenoutput .= '"'.trim($brow['lastname'])."\",";
		$tokenoutput .= '"'.trim($brow['email'])."\",";
		$tokenoutput .= '"'.trim($brow['emailstatus'])."\",";
		$tokenoutput .= '"'.trim($brow['token'])."\",";
		$tokenoutput .= '"'.trim($brow['language'])."\"";
		if($bfieldcount > 8)
		{
			$tokenoutput .= ",";
			$tokenoutput .= '"'.trim($brow['attribute_1'])."\",";
			$tokenoutput .= '"'.trim($brow['attribute_2'])."\",";
			$tokenoutput .= '"'.trim($brow['tid'])."\"";
		}
		$tokenoutput .= "\n";
	}
	echo $tokenoutput;
	exit;
}

if ($subaction == "delete" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   ) 
{
	$_SESSION['metaHeader']="<meta http-equiv=\"refresh\" content=\"1;URL={$scriptname}?action=tokens&amp;subaction=browse&amp;sid=".returnglobal('sid')."&amp;start=$start&amp;limit=$limit&amp;order=$order\" />";
}


//Show Help
$tokenoutput .= "<script type='text/javascript'>\n"
."<!--\n"
."\tfunction showhelp(action)\n"
."\t\t{\n"
."\t\tvar name='help';\n"
."\t\tif (action == \"hide\")\n"
."\t\t\t{\n"
."\t\t\tdocument.getElementById(name).style.display='none';\n"
."\t\t\t}\n"
."\t\telse if (action == \"show\")\n"
."\t\t\t{\n"
."\t\t\tdocument.getElementById(name).style.display='';\n"
."\t\t\t}\n"
."\t\t}\n"
."-->\n"
."</script>\n";

$tokenoutput .= "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n"
."\t<tr>\n"
."\t\t<td valign='top' align='center' >\n"
."\t\t<table><tr><td></td></tr></table>\n";

$tokenoutput .= "<table class='menubar' cellpadding='1' cellspacing='0'>\n";


// MAKE SURE THAT THERE IS A SID
if (!isset($surveyid) || !$surveyid)
{
	$tokenoutput .= "\t<tr><td colspan='2' height='4'><font size='1'><strong>"
	.$clang->gT("Token Control").":</strong></font></td></tr>\n"
	."\t<tr><td align='center'><br /><font color='red'><strong>"
	.$clang->gT("Error")."</strong></font><br />".$clang->gT("You have not selected a survey")."<br /><br />"
	."<input type='submit' value='"
	.$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_self')\" /><br /><br /></td></tr>\n"
	."</table>\n"
	."</body>\n</html>";
	return;
}

// MAKE SURE THAT THE SURVEY EXISTS
$chquery = "SELECT * FROM ".db_table_name('surveys')." as a inner join ".db_table_name('surveys_languagesettings')." as b on (b.surveyls_survey_id=a.sid and b.surveyls_language=a.language) WHERE a.sid=$surveyid";

$chresult=db_execute_assoc($chquery);
$chcount=$chresult->RecordCount();
if (!$chcount)
{
	$tokenoutput .= "\t<tr><td colspan='2' height='4'><font size='1'><strong>"
	.$clang->gT("Token Control").":</strong></font></td></tr>\n"
	."\t<tr><td align='center'><br /><font color='red'><strong>"
	.$clang->gT("Error")."</strong></font><br />".$clang->gT("The survey you selected does not exist")
	."<br /><br />\n\t<input type='submit' value='"
	.$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_self')\" /><br /><br /></td></tr>\n"
	."</table>\n"
	."</body>\n</html>";
	return;
}
// A survey DOES exist
while ($chrow = $chresult->FetchRow())
{
	$tokenoutput .= "\t<tr><td colspan='2' height='4'><strong>"
	.$clang->gT("Token Control").":</strong> "
	."{$chrow['surveyls_title']}</td></tr>\n";
	$surveyprivate = $chrow['private'];
}

// CHECK TO SEE IF A TOKEN TABLE EXISTS FOR THIS SURVEY
$tkquery = "SELECT * FROM ".db_table_name("tokens_$surveyid");
if (!$tkresult = $connect->Execute($tkquery)) //If the query fails, assume no tokens table exists
{
	if (isset($_POST['createtable']) && $_POST['createtable']=="Y" && 
		($sumrows5['edit_survey_property'] || 
			$sumrows5['activate_survey'] || 
			$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
	   )
	{
		$createtokentable=
		"tid int I NOTNULL AUTO PRIMARY,\n "
		. "firstname C(40) ,\n "
		. "lastname C(40) ,\n ";
        //MSSQL needs special treatment because of some strangeness in ADODB
        if ($databasetype=='odbc_mssql')
             {
                          $createtokentable.= "email C(320) ,\n "
                                             ."emailstatus C(300) DEFAULT 'OK',\n ";
             }
        else
            {
             $createtokentable.= "email X(320) ,\n "
                                ."emailstatus X(300) DEFAULT 'OK',\n ";
            }
        
		$createtokentable.= "token C(36) ,\n "
		. "language C(25) ,\n "
		. "sent C(17) DEFAULT 'N',\n "
		. "completed C(17) DEFAULT 'N',\n "
		. "attribute_1 C(100) ,\n"
		. "attribute_2 C(100) ,\n"
		. "mpid I ";


		$tabname = "{$dbprefix}tokens_{$surveyid}"; # not using db_table_name as it quotes the table name (as does CreateTableSQL)
        $taboptarray = array('mysql' => 'TYPE='.$databasetabletype.'  CHARACTER SET utf8 COLLATE utf8_unicode_ci');
		$dict = NewDataDictionary($connect);
		$sqlarray = $dict->CreateTableSQL($tabname, $createtokentable, $taboptarray);
		$execresult=$dict->ExecuteSQLArray($sqlarray, false);

		if ($execresult==0 || $execresult==1)
		{
			
			$tokenoutput .= "\t<tr>\n"
			."\t\t<td align='center'>\n"
			. "<br />\n<table width='350' align='center' class='menubar' cellpadding='1' cellspacing='0'>\n" .
			"<tr><td height='4'><font size='1'><strong><center>".$clang->gT("Token table could not be created.")."</center></strong></font></td></tr>\n" .
			"<tr><td>\n" .
			$clang->gT("Error").": \n<font color='red'>" . $connect->ErrorMsg() . "</font>\n" .
			"<pre>".implode(" ",$sqlarray)."</pre>\n" .
			"</td></tr></table>"
			."\t\t\t<input type='submit' value='"
			.$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname?sid=$surveyid', '_self')\" />\n"
			."\t\t</td>\n"
			."\t</tr>\n"
			."</table>\n"
			."<table><tr><td></td></tr></table>\n"
			."</td></tr></table>\n";
			
		} else {
			$createtokentableindex = $dict->CreateIndexSQL("{$tabname}_idx", $tabname, array('token'));
			$dict->ExecuteSQLArray($createtokentableindex, false) or safe_die ("Failed to create token table index<br />$createtokentableindex<br /><br />".$connect->ErrorMsg());

			$tokenoutput .= "\t<tr>\n"
			."\t\t<td align='center'>\n"
			."\t\t\t<br /><br />\n"
			."\t\t\t".$clang->gT("A token table has been created for this survey.")." (\"".$dbprefix."tokens_$surveyid\")<br /><br />\n"
			."\t\t\t<input type='submit' value='"
			.$clang->gT("Continue")."' onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid', '_self')\" />\n"
			."\t\t</td>\n"
			."\t</tr>\n"
			."</table>\n"
			."<table><tr><td></td></tr></table>\n"
			."</td></tr></table>\n";
		}
		return;
	}
	elseif (returnglobal('restoretable') == "Y" && returnglobal('oldtable') && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
	)
	{
		$query = db_rename_table(returnglobal('oldtable') , db_table_name("tokens_$surveyid"));
		$result=$connect->Execute($query) or safe_die("Failed Rename!<br />".$query."<br />".$connect->ErrorMsg());
		$tokenoutput .= "\t<tr>\n"
		."\t\t<td align='center'>\n"
		."\t\t\t<br /><br />\n"
		."\t\t\t".$clang->gT("A token table has been created for this survey.")." (\"".$dbprefix."tokens_$surveyid\")<br /><br />\n"
		."\t\t\t<input type='submit' value='"
		.$clang->gT("Continue")."' onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid', '_self')\" />\n"
		."\t\t</td>\n"
		."\t</tr>\n"
		."</table>\n"
		."<table><tr><td></td></tr></table>\n"
		."</td></tr></table>\n";
		return;
	}
	else
	{
		$query=db_select_tables_like("{$dbprefix}old_tokens_".$surveyid."_%");
		$result=db_execute_num($query) or safe_die("Couldn't get old table list<br />".$query."<br />".$connect->ErrorMsg());
		$tcount=$result->RecordCount();
		if ($tcount > 0)
		{
			while($rows=$result->FetchRow())
			{
				$oldlist[]=$rows[0];
			}
		}
		$tokenoutput .= "\t<tr>\n"
		."\t\t<td align='center'>\n"
		."\t\t\t<br /><font color='red'><strong>".$clang->gT("Warning")."</strong></font><br />\n"
		."\t\t\t<strong>".$clang->gT("Tokens have not been initialised for this survey.")."</strong><br /><br />\n";
		if ($sumrows5['edit_survey_property'] || 
			$sumrows5['activate_survey'] ||
			$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
		{
			$tokenoutput .= "\t\t\t".$clang->gT("If you initialise tokens for this survey, the survey will only be accessible to users who have been assigned a token.")
			."\t\t\t<br /><br />\n";

			$thissurvey=getSurveyInfo($surveyid);

			if ($thissurvey['private'] == 'Y')
			{
				$tokenoutput .= "\t\t\t".$clang->gT("If you turn on the -Anonymous answers- option and create a tokens table, LimeSurvey will mark your completed tokens only with a 'Y' instead of date/time to ensure the anonymity of your participants.")
					."\t\t\t<br /><br />\n";
			}

			$tokenoutput .= "\t\t\t".$clang->gT("Do you want to create a tokens table for this survey?");
			$tokenoutput .= "<br /><br />\n";
			$tokenoutput .= "\t\t\t<input type='submit' value='"
//			.$clang->gT("Initialise Tokens")."' onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;createtable=Y', '_self')\" /><br />\n";
			.$clang->gT("Initialise Tokens")."' onclick=\"".get2post("$scriptname?action=tokens&amp;sid=$surveyid&amp;createtable=Y")."\" /><br />\n";
		}
		$tokenoutput .= "\t\t\t<input type='submit' value='"
		.$clang->gT("Main Admin Screen")."' onclick=\"window.open('$homeurl/admin.php?sid=$surveyid', '_self')\" /><br /><br />\n";
		// Do not offer old postgres token tables for restore since these are having an issue with missing index
        if ($tcount>0 && $databasetype!='postgres' &&
			($sumrows5['edit_survey_property'] || 
				$sumrows5['activate_survey'] ||
				$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
		   )
		{
			$tokenoutput .= "<table width='400' border='0' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'><tr>\n"
			."<td class='settingcaption'><font>".$clang->gT("Restore Options").":\n"
			."</font></td></tr>\n"
			."<tr>\n"
			."<td class='evenrow' align='center'><form method='post' action='$scriptname?action=tokens'>\n"
			.$clang->gT("The following old token tables could be restored:")."<br />\n"
			."<select size='4' name='oldtable' style='width:250px;'>\n";
			foreach($oldlist as $ol)
			{
				$tokenoutput .= "<option>".$ol."</option>\n";
			}
			$tokenoutput .= "</select><br />\n"
			."<input type='submit' value='".$clang->gT("Restore")."' />\n"
			."<input type='hidden' name='restoretable' value='Y' />\n"
			."<input type='hidden' name='sid' value='$surveyid' />\n"
			."</form></td>\n"
			."</tr></table>\n";
		}

		$tokenoutput .= "\t\t</td>\n"
		."\t</tr>\n"
		."</table>\n"
		."<table><tr><td></td></tr></table>\n"
		."</td></tr></table>\n";
		return;
	}
}

#Lookup the names of the attributes
$query = "SELECT attribute1, attribute2 FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
$result = db_execute_assoc($query) or safe_die("Couldn't execute query: <br />$query<br />".$connect->ErrorMsg());
$row = $result->FetchRow();
if ($row["attribute1"]) {$attr1_name = $row["attribute1"];} else {$attr1_name=$clang->gT("Attribute 1");}
if ($row["attribute2"]) {$attr2_name = $row["attribute2"];} else {$attr2_name=$clang->gT("Attribute 2");}

// IF WE MADE IT THIS FAR, THEN THERE IS A TOKENS TABLE, SO LETS DEVELOP THE MENU ITEMS
$tokenoutput .= "\t<tr>\n"
."\t\t<td>\n"
."\t\t\t<a href=\"#\" onclick=\"showhelp('show')\" onmouseout=\"hideTooltip()\""
."onmouseover=\"showTooltip(event,'".$clang->gT("Show Help", "js")."');return false\">" .
"<img src='$imagefiles/showhelp.png' title='' align='right' alt='' /></a>\n"
."\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?sid=$surveyid', '_self')\" onmouseout=\"hideTooltip()\""
."onmouseover=\"showTooltip(event,'".$clang->gT("Return to Survey Administration", "js")."');return false\">" .
"<img name='HomeButton' src='$imagefiles/home.png' align='left'  alt=''  /></a>\n"
."\t\t\t<img src='$imagefiles/blank.gif' alt='' width='11' border='0' hspace='0' align='left' />\n"
."\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid', '_self')\" onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show Token Summary", "js")."');return false\" >" 
."\t\t\t<img src='$imagefiles/seperator.gif' alt='' border='0' hspace='0' align='left' />\n"
."<img name='SummaryButton' src='$imagefiles/summary.png' title='' align='left'  alt=''  /></a>\n"
."\t\t\t<img src='$imagefiles/seperator.gif' alt='' border='0' hspace='0' align='left' />\n"
."\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse', '_self')\" onmouseout=\"hideTooltip()\""
."onmouseover=\"showTooltip(event,'".$clang->gT("Display Tokens", "js")."');return false\">" 
."<img name='ViewAllButton' src='$imagefiles/document.png' title='' align='left'  alt=''  /></a>\n";

if ($sumrows5['edit_survey_property'] || 
	$sumrows5['activate_survey'] ||
	$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
{
	$tokenoutput .= "\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=addnew', '_self')\" onmouseout=\"hideTooltip()\"" .
	"onmouseover=\"showTooltip(event,'".$clang->gT("Add new token entry", "js")."');return false\">" .
	"<img name='AddNewButton' src='$imagefiles/add.png' title='' align='left'  alt=''  /></a>\n"
	."\t\t\t<img src='$imagefiles/seperator.gif' alt='' border='0' hspace='0' align='left' />\n"
	."\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=import', '_self')\" onmouseout=\"hideTooltip()\" ".
	"onmouseover=\"showTooltip(event,'".$clang->gT("Import Tokens from CSV File", "js")."');return false\"> <img name='ImportButton' src='$imagefiles/importcsv.png' title='' align='left' alt='' /></a>"
	."\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=importldap', '_self')\" onmouseout=\"hideTooltip()\" ".
	"onmouseover=\"showTooltip(event,'".$clang->gT("Import Tokens from LDAP Query", "js")."');return false\"> <img name='ImportLdapButton' src='$imagefiles/importldap.png' title=''  alt=''  align='left' /></a>";
}

if ($sumrows5['export'] || $_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
{
	$tokenoutput .= "\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=export', '_self')\" onmouseout=\"hideTooltip()\"" .
	"onmouseover=\"showTooltip(event,'".$clang->gT("Export Tokens to CSV file", "js")."');return false\">".
	"<img name='ExportButton' src='$imagefiles/exportcsv.png' align='left' alt='' /></a>\n";
}
if ($sumrows5['edit_survey_property'] || 
	$sumrows5['activate_survey'] ||
	$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
{
	$tokenoutput .= "\t\t\t<img src='$imagefiles/seperator.gif' alt='' border='0' hspace='0' align='left' />\n"
	."\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=emailsettings', '_self')\" onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Edit Email Templates", "js")."');return false\">" .
	"<img name='EmailSettingsButton' src='$imagefiles/emailsettings.png' title='' align='left'  alt='' /></a>\n"
	."\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=email', '_self')\" onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Send email invitation", "js")."');return false\">" .
	"<img name='InviteButton' src='$imagefiles/invite.png' title='' align='left'  alt='' /></a>\n"
	."\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=remind', '_self')\" onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Send email reminder", "js")."');return false\">" .
	"<img name='RemindButton' src='$imagefiles/remind.png' title='' align='left'   alt='' /></a>\n"
	."\t\t\t<img src='$imagefiles/seperator.gif'  border='0' hspace='0' align='left'  alt='' />\n"
//	."\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=tokenify', '_self')\" onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Generate Tokens", "js")."');return false\">" .
	."\t\t\t<a href=\"#\" onclick=\"".get2post("$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=tokenify")."\" onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Generate Tokens", "js")."');return false\">" .
	"<img name='TokenifyButton' src='$imagefiles/tokenify.png' title='' align='left'  alt='' /></a>\n"
	."\t\t\t<img src='$imagefiles/seperator.gif' border='0' hspace='0' align='left'  alt='' />\n"
//	."\t\t\t<a href=\"#\" onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=kill', '_self')\" onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Drop tokens table", "js")."');return false\">" .
	."\t\t\t<a href=\"#\" onclick=\"".get2post("$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=kill")."\" onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Drop tokens table", "js")."');return false\">" .
	"<img name='DeleteTokensButton' src='$imagefiles/delete.png' title='' align='left'  alt=''  /></a>\n"
	."\t\t</td>\n";
}

$tokenoutput .= "\t</tr>\n";

// SEE HOW MANY RECORDS ARE IN THE TOKEN TABLE
$tkcount = $tkresult->RecordCount();
	$tokenoutput .= "\t<tr>\n"
	."\t\t<td align='center'>\n";

// GIVE SOME INFORMATION ABOUT THE TOKENS
if ($subaction==''){
	$tokenoutput .= "\t<tr>\n"
	."\t\t<td align='center'>\n"
	."\t\t<br />\n"
	."\t\t\t<table align='center' cellpadding='2' style='border: 1px solid #555555'>\n"
	."\t\t\t\t<tr>\n"
	."\t\t\t\t\t<td align='center'>\n"
	."\t\t\t\t\t<strong>".$clang->gT("Total Records in this Token Table").": $tkcount</strong><br />\n";
	
	
	
	$tksq = "SELECT count(*) FROM ".db_table_name("tokens_$surveyid")." WHERE token IS NULL OR token=''";
	$tksr = db_execute_num($tksq);
	while ($tkr = $tksr->FetchRow())
	{$tokenoutput .= "\t\t\t\t\t\t".$clang->gT("Total With No Unique Token").": $tkr[0] / $tkcount<br />\n";}
	
	$tksq = "SELECT count(*) FROM ".db_table_name("tokens_$surveyid")." WHERE (sent!='N' and sent<>'')";
	
	$tksr = db_execute_num($tksq);
	while ($tkr = $tksr->FetchRow())
	
	{$tokenoutput .= "\t\t\t\t\t\t".$clang->gT("Total Invitations Sent").": $tkr[0] / $tkcount<br />\n";}
	$tksq = "SELECT count(*) FROM ".db_table_name("tokens_$surveyid")." WHERE (completed!='N' and completed<>'')";
	
	$tksr = db_execute_num($tksq) or safe_die ("Couldn't execute token selection query<br />$abquery<br />".$connect->ErrorMsg());
	while ($tkr = $tksr->FetchRow())
	{$tokenoutput .= "\t\t\t\t\t\t".$clang->gT("Total Surveys Completed").": $tkr[0] / $tkcount\n";}
	$tokenoutput .= "\t\t\t\t\t</td>\n"
	."\t\t\t\t</tr>\n"
	."\t\t\t</table>\n"
	."\t\t\t<br />\n"
	."\t\t</td>\n"
	."\t</tr>\n"
	."</table>\n"
	."<table ><tr><td></td></tr></table>\n";
}

$tokenoutput .= "<table width='99%' class='menubar' cellpadding='1' cellspacing='0'>\n";

#############################################################################################
// NOW FOR VARIOUS ACTIONS:




if ($subaction == "emailsettings") 
{

	$grplangs = GetAdditionalLanguagesFromSurveyID($surveyid);
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	array_unshift($grplangs,$baselang);
	if (getEmailFormat($surveyid) == 'html')
	{
		$ishtml=true;
	}
	else
	{
		$ishtml=false;
	}    

	$tokenoutput .= PrepareEditorScript();
	$tokenoutput .="<tr><td align='center'><script type='text/javascript'>\n"
		. "<!--\n"
		. "function fillin(tofield, fromfield)\n"
		. "\t{\n"
		. "\t\tif (confirm(\"".$clang->gT("This will replace the existing text. Continue?","js")."\")) {\n"
		. "\t\t\tif (document.getElementById(tofield).readOnly == false)\n"
		. "\t\t\t{\n"
		. "\t\t\t\tdocument.getElementById(tofield).value = document.getElementById(fromfield).value;\n"
		. "\t\t\t}\n"
		. "\t\t\tupdateFCKeditor(tofield,document.getElementById(fromfield).value);\n"
		. "\t\t}\n"
		. "\t}\n"
		. "--></script>\n"
		. "<form name='addnewsurvey' action='$scriptname' method='post'>\n"
		. "<table width='100%' border='0'>\n\t<tr><td class='settingcaption'>"
		. "\t\t".$clang->gT("Edit Email Settings")."</td></tr></table>\n"
		. '<div class="tab-pane" id="tab-pane-1">';
	$surveyinfo=getSurveyInfo($surveyid);
	foreach ($grplangs as $grouplang)
	{
		// this one is created to get the right default texts fo each language
		$bplang = new limesurvey_lang($grouplang);		
		$esquery = "SELECT * FROM ".db_table_name("surveys_languagesettings")." WHERE surveyls_survey_id=$surveyid and surveyls_language='$grouplang'";
		$esresult = db_execute_assoc($esquery);
		$esrow = $esresult->FetchRow();
		$tokenoutput .= '<div class="tab-page"> <h2 class="tab">'.getLanguageNameFromCode($esrow['surveyls_language'],false);
		if ($esrow['surveyls_language']==GetBaseLanguageFromSurveyID($surveyid)) {$tokenoutput .= '('.$clang->gT("Base Language").')';}
		$tokenoutput .= '</h2>';
		$tokenoutput .= "\t<div class='settingrow'><span class='settingcaption'>".$clang->gT("Invitation Email Subject:")."</span>\n"
			. "\t\t<span class='settingentry'><input type='text' size='80' name='email_invite_subj_".$esrow['surveyls_language']."' id='email_invite_subj_{$grouplang}' value=\"{$esrow['surveyls_email_invite_subj']}\" />\n"
			. "\t\t<input type='hidden' name='email_invite_subj_default_".$esrow['surveyls_language']."' id='email_invite_subj_default_{$grouplang}' value='".$bplang->gT("Invitation to participate in survey")."' />\n"
			. "\t\t<input type='button' value='".$clang->gT("Use default")."' onclick='javascript: fillin(\"email_invite_subj_{$grouplang}\",\"email_invite_subj_default_{$grouplang}\")' />\n"
			. "\t</span></div>\n";
		$tokenoutput .= "\t<div class='settingrow'><span class='settingcaption'>".$clang->gT("Invitation Email:")."</span>\n"
			. "\t\t<span class='settingentry'><textarea cols='80' rows='10' name='email_invite_".$esrow['surveyls_language']."' id='email_invite_{$grouplang}'>{$esrow['surveyls_email_invite']}</textarea>\n"
			. getEditor("email-inv","email_invite_{$grouplang}", "[".$clang->gT("Invitation Email:", "js")."](".$grouplang.")",$surveyid,'','',$action)
			. "\t\t<input type='hidden' name='email_invite_default_".$esrow['surveyls_language']."' id='email_invite_default_{$grouplang}' value='".conditional_nl2br($bplang->gT("Dear {FIRSTNAME},\n\nYou have been invited to participate in a survey.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}"),$ishtml)."' />\n"
			. "\t\t<input type='button' value='".$clang->gT("Use default")."' onclick='javascript: fillin(\"email_invite_{$grouplang}\",\"email_invite_default_{$grouplang}\")' />\n"
			. "\t</span></div>\n";
		$tokenoutput .= "\t<div class='settingrow'><span class='settingcaption'>".$clang->gT("Email Reminder Subject:")."</span>\n"
			. "\t\t<span class='settingentry'><input type='text' size='80' name='email_remind_subj_".$esrow['surveyls_language']."' id='email_remind_subj_{$grouplang}' value=\"{$esrow['surveyls_email_remind_subj']}\" />\n"
			. "\t\t<input type='hidden' name='email_remind_subj_default_".$esrow['surveyls_language']."' id='email_remind_subj_default_{$grouplang}' value='".$bplang->gT("Reminder to participate in survey")."' />\n"
			. "\t\t<input type='button' value='".$clang->gT("Use default")."' onclick='javascript: fillin(\"email_remind_subj_{$grouplang}\",\"email_remind_subj_default_{$grouplang}\")' />\n"
			. "\t</span></div>\n";
		$tokenoutput .= "\t<div class='settingrow'><span class='settingcaption'>".$clang->gT("Email Reminder:")."</span>\n"
			. "\t\t<span class='settingentry'><textarea cols='80' rows='10' name='email_remind_".$esrow['surveyls_language']."' id='email_remind_{$grouplang}'>{$esrow['surveyls_email_remind']}</textarea>\n"
			. getEditor("email-rem","email_remind_{$grouplang}", "[".$clang->gT("Email Reminder:", "js")."](".$grouplang.")",$surveyid,'','',$action)
			. "\t\t<input type='hidden' name='email_remind_default_".$esrow['surveyls_language']."' id='email_remind_default_{$grouplang}' value='".conditional_nl2br($bplang->gT("Dear {FIRSTNAME},\n\nRecently we invited you to participate in a survey.\n\nWe note that you have not yet completed the survey, and wish to remind you that the survey is still available should you wish to take part.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}"),$ishtml)."' />\n"
			. "\t\t<input type='button' value='".$clang->gT("Use default")."' onclick='javascript: fillin(\"email_remind_{$grouplang}\",\"email_remind_default_{$grouplang}\")' />\n"
			. "\t</span></div>\n";
		$tokenoutput .= "\t<div class='settingrow'><span class='settingcaption'>".$clang->gT("Confirmation Email Subject")."</span>\n"
			. "\t\t<span class='settingentry'><input type='text' size='80' name='email_confirm_subj_".$esrow['surveyls_language']."' id='email_confirm_subj_{$grouplang}' value=\"{$esrow['surveyls_email_confirm_subj']}\" />\n"
			. "\t\t<input type='hidden' name='email_confirm_subj_default_".$esrow['surveyls_language']."' id='email_confirm_subj_default_{$grouplang}' value='".$bplang->gT("Confirmation of completed survey")."' />\n"
			. "\t\t<input type='button' value='".$clang->gT("Use default")."' onclick='javascript: fillin(\"email_confirm_subj_{$grouplang}\",\"email_confirm_subj_default_{$grouplang}\")' />\n"
			. "\t</span></div>\n";
		$tokenoutput .= "\t<div class='settingrow'><span class='settingcaption'>".$clang->gT("Confirmation Email")."</span>\n"
			. "\t\t<span class='settingentry'><textarea cols='80' rows='10' name='email_confirm_".$esrow['surveyls_language']."' id='email_confirm_{$grouplang}'>{$esrow['surveyls_email_confirm']}</textarea>\n"
			. getEditor("email-conf","email_confirm_{$grouplang}", "[".$clang->gT("Confirmation Email", "js")."](".$grouplang.")",$surveyid,'','',$action)
			. "\t\t<input type='hidden' name='email_confirm_default_".$esrow['surveyls_language']."' id='email_confirm_default_{$grouplang}' value='".conditional_nl2br($bplang->gT("Dear {FIRSTNAME},\n\nThis email is to confirm that you have completed the survey titled {SURVEYNAME} and your response has been saved. Thank you for participating.\n\nIf you have any further questions about this email, please contact {ADMINNAME} on {ADMINEMAIL}.\n\nSincerely,\n\n{ADMINNAME}"),$ishtml)."' />\n"
			. "\t\t<input type='button' value='".$clang->gT("Use default")."' onclick='javascript: fillin(\"email_confirm_{$grouplang}\",\"email_confirm_default_{$grouplang}\")' />\n"
			. "\t</span></div>\n";
		$tokenoutput .= "\t<div class='settingrow'><span class='settingcaption'>".$clang->gT("Public registration Email Subject:")."</span>\n"
			. "\t\t<span class='settingentry'><input type='text' size='80' name='email_register_subj_".$esrow['surveyls_language']."' id='email_register_subj_{$grouplang}' value=\"{$esrow['surveyls_email_register_subj']}\" />\n"
			. "\t\t<input type='hidden' name='email_register_subj_default_".$esrow['surveyls_language']."' id='email_register_subj_default_{$grouplang}' value='".$bplang->gT("Survey Registration Confirmation")."' />\n"
			. "\t\t<input type='button' value='".$clang->gT("Use default")."' onclick='javascript:  fillin(\"email_register_subj_{$grouplang}\",\"email_register_subj_default_{$grouplang}\")' />\n"
			. "\t</span></div>\n";
		$tokenoutput .= "\t<div class='settingrow'><span class='settingcaption'>".$clang->gT("Public registration Email:")."</span>\n"
			. "\t\t<span class='settingentry'><textarea cols='80' rows='10' name='email_register_{$grouplang}' id='email_register_{$grouplang}'>{$esrow['surveyls_email_register']}</textarea>\n"
			. getEditor("email-reg","email_register_{$grouplang}", "[".$clang->gT("Public registration Email:", "js")."](".$grouplang.")",$surveyid,'','',$action)
			. "\t\t<input type='hidden' name='email_register_default_".$esrow['surveyls_language']."' id='email_register_default_{$grouplang}' value='".conditional_nl2br($bplang->gT("Dear {FIRSTNAME},\n\nYou, or someone using your email address, have registered to participate in an online survey titled {SURVEYNAME}.\n\nTo complete this survey, click on the following URL:\n\n{SURVEYURL}\n\nIf you have any questions about this survey, or if you did not register to participate and believe this email is in error, please contact {ADMINNAME} at {ADMINEMAIL}."),$ishtml)."' />\n"
			. "\t\t<input type='button' value='".$clang->gT("Use default")."' onclick='javascript:  fillin(\"email_register_{$grouplang}\",\"email_register_default_{$grouplang}\")' />\n"
			. "\t</span><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /></div>\n</div>";
	}
	$tokenoutput .= '</div>';
	$tokenoutput .= "\t<p><p><p><input type='submit' class='standardbtn' value='".$clang->gT("Save")."' />\n"
		. "\t<input type='hidden' name='action' value='tokens' />\n"
		. "\t<input type='hidden' name='subaction' value='updateemailsettings' />\n"
		. "\t<input type='hidden' name='sid' value=\"{$surveyid}\" />\n"
		. "\t<input type='hidden' name='language' value=\"{$esrow['surveyls_language']}\" />\n"
		. "\t</p>\n"
		. "</form></td></tr></table></td></tr></table>\n";
}

	// Save the updated email settings
if ($subaction == "updateemailsettings" && 
	($_SESSION['USER_RIGHT_SUPERADMIN'] == 1 || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
	{
		$_POST  = array_map('db_quote', $_POST);
		$languagelist = GetAdditionalLanguagesFromSurveyID($surveyid);
		$languagelist[]=GetBaseLanguageFromSurveyID($surveyid);
		foreach ($languagelist as $langname)
		{
			if ($langname)
			{
				$usquery = "UPDATE ".db_table_name('surveys_languagesettings')." \n"
				. "SET surveyls_email_invite_subj='".$_POST['email_invite_subj_'.$langname]."',\n"
				. "surveyls_email_invite='".$_POST['email_invite_'.$langname]."', surveyls_email_remind_subj='".$_POST['email_remind_subj_'.$langname]."',\n"
				. "surveyls_email_remind='".$_POST['email_remind_'.$langname]."', surveyls_email_register_subj='".$_POST['email_register_subj_'.$langname]."',\n"
				. "surveyls_email_register='".$_POST['email_register_'.$langname]."', surveyls_email_confirm_subj='".$_POST['email_confirm_subj_'.$langname]."',\n"
				. "surveyls_email_confirm='".$_POST['email_confirm_'.$langname]."'\n"
				. "WHERE surveyls_survey_id=".$surveyid." and surveyls_language='".$langname."'";
				$usresult = $connect->Execute($usquery) or safe_die("Error updating<br />".$usquery."<br /><br />".$connect->ErrorMsg());
			}
		}
	$tokenoutput .= "<tr><td align='center'><br /><strong><font class='successtitle'>".$clang->gT("Token Email Settings have been saved.")."</font></strong><br />&nbsp;</td></tr>\n";

	}




if ($subaction == "deleteall" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$query="DELETE FROM ".db_table_name("tokens_$surveyid");
	$result=$connect->Execute($query) or safe_die ("Couldn't update sent field<br />$query<br />".$connect->ErrorMsg());
	$tokenoutput .= "<tr><td  align='center'><strong><font class='successtitle'>".$clang->gT("All token entries have been deleted.")."</font></strong></td></tr>\n";
	$subaction="";
}

if ($subaction == "clearinvites" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] || 
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$query="UPDATE ".db_table_name("tokens_$surveyid")." SET sent='N'";
	$result=$connect->Execute($query) or safe_die ("Couldn't update sent field<br />$query<br />".$connect->ErrorMsg());
	$tokenoutput .= "<tr><td align='center'><strong><font class='successtitle'>".$clang->gT("All invite entries have been set to 'Not Invited'.")."</font></strong></td></tr>\n";
	$subaction="";
}

if ($subaction == "cleartokens" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$query="UPDATE ".db_table_name("tokens_$surveyid")." SET token=''";
	$result=$connect->Execute($query) or safe_die("Couldn't reset the tokens field<br />$query<br />".$connect->ErrorMsg());
	$tokenoutput .= "<tr><td align='center'><strong><font class='successtitle'>".$clang->gT("All unique token numbers have been removed.")."</font></strong></td></tr>\n";
	$subaction="";
}


if (!$subaction && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$tokenoutput .= "\t<tr><td colspan='2' height='4'><font size='1'><strong>"
	.$clang->gT("Token Database Administration Options").":</strong></font></td></tr>\n"
	."\t<tr>\n"
	."\t\t<td align='center'>\n"
	."\t\t\t<table align='center'><tr><td>\n"
	."\t\t\t<br />\n"
//	."\t\t\t<ul><li><a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=clearinvites' onclick='return confirm(\""
//	.$clang->gT("Are you really sure you want to reset all invitation records to NO?")."\")'>".$clang->gT("Set all entries to 'No invitation sent'.")."</a></li>\n"
	."\t\t\t<ul><li><a href='#' onclick=\"if( confirm('"
	.$clang->gT("Are you really sure you want to reset all invitation records to NO?","js")."')) {".get2post("$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=clearinvites")."}\">".$clang->gT("Set all entries to 'No invitation sent'.")."</a></li>\n"
//	."\t\t\t<li><a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=cleartokens' onclick='return confirm(\""
//	.$clang->gT("Are you sure you want to delete all unique token numbers?")."\")'>".$clang->gT("Delete all unique token numbers")."</a></li>\n"
	."\t\t\t<li><a href='#' onclick=\"if ( confirm('"
	.$clang->gT("Are you sure you want to delete all unique token numbers?","js")."')) {".get2post("$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=cleartokens")."}\">".$clang->gT("Delete all unique token numbers")."</a></li>\n"
//	."\t\t\t<li><a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=deleteall' onclick='return confirm(\""
//	.$clang->gT("Are you really sure you want to delete ALL token entries?")."\")'>".$clang->gT("Delete all token entries")."</a></li>\n";
	."\t\t\t<li><a href='#' onclick=\" if (confirm('"
	.$clang->gT("Are you really sure you want to delete ALL token entries?","js")."')) {".get2post("$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=deleteall")."}\">".$clang->gT("Delete all token entries")."</a></li>\n";
	$bquery = "SELECT * FROM ".db_table_name("tokens_$surveyid");
	$bresult = db_select_limit_assoc($bquery, 1) or safe_die($clang->gT("Error")." counting fields<br />".$connect->ErrorMsg());
	$bfieldcount=$bresult->FieldCount();
	$tokenoutput .= "\t\t\t<li><a href='#' onclick=\"".get2post("$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=kill")."\">".$clang->gT("Drop tokens table")."</a></li></ul>\n"
	."\t\t\t</td></tr></table>\n"
	."\t\t</td>\n"
	."\t</tr>\n"
	."</table>\n";
}

if ($subaction == "settings" && 
	($sumrows5['export'] || $_SESSION['USER_RIGHT_SUPERADMIN'] == 1))  //ToDO: Which right?
{

}


if ($subaction == "browse" || $subaction == "search")
{
	if (!isset($limit)) {$limit = 100;}
	if (!isset($start)) {$start = 0;}

	if ($limit > $tkcount) {$limit=$tkcount;}
	$next=$start+$limit;
	$last=$start-$limit;
	$end=$tkcount-$limit;
	if ($end < 0) {$end=0;}
	if ($last <0) {$last=0;}
	if ($next >= $tkcount) {$next=$tkcount-$limit;}
	if ($end < 0) {$end=0;}

	//ALLOW SELECTION OF NUMBER OF RECORDS SHOWN
	$tokenoutput .= "\t<tr><td colspan='3' height='4'><strong>"
	.$clang->gT("Data View Control").":</strong></td></tr>\n"
	."\t<tr><td width='230' align='left' valign='middle'>\n"
	."\t\t\t<img src='$imagefiles/blank.gif' alt='' width='31' height='20' border='0' hspace='0' align='left' />\n"
	."\t\t\t<img src='$imagefiles/seperator.gif' alt='' border='0' hspace='0' align='left' />\n"
	."\t\t\t<a href='$scriptname?action=tokens&amp;subaction=browse&amp;sid=$surveyid&amp;start=0&amp;limit=$limit&amp;order=$order&amp;searchstring=$searchstring'" .
	"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show start..", "js")."');return false\">".
	"<img name='DBeginButton' align='left' src='$imagefiles/databegin.png' title='' /></a>\n"
	."\t\t\t<a href='$scriptname?action=tokens&amp;subaction=browse&amp;sid=$surveyid&amp;start=$last&amp;limit=$limit&amp;order=$order&amp;searchstring=$searchstring'" .
	"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show previous...", "js")."');return false\">" .
	"<img name='DBackButton' align='left' src='$imagefiles/databack.png' title='' /></a>\n"
	."\t\t\t<img src='$imagefiles/blank.gif' alt='' width='13' height='20' border='0' hspace='0' align='left' />\n"
	."\t\t\t<a href='$scriptname?action=tokens&amp;subaction=browse&amp;sid=$surveyid&amp;start=$next&amp;limit=$limit&amp;order=$order&amp;searchstring=$searchstring'" .
	"onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show next...", "js")."');return false\">" .
	"<img name='DForwardButton' align='left' src='$imagefiles/dataforward.png' title='' /></a>\n"
	."\t\t\t<a href='$scriptname?action=tokens&amp;subaction=browse&amp;sid=$surveyid&amp;start=$end&amp;limit=$limit&amp;order=$order&amp;searchstring=$searchstring'" .
	" onmouseout=\"hideTooltip()\" onmouseover=\"showTooltip(event,'".$clang->gT("Show last...", "js")."');return false\">".
	"<img name='DEndButton' align='left'  src='$imagefiles/dataend.png' title='' /></a>\n"
	."\t\t\t<img src='$imagefiles/seperator.gif' alt='' border='0' hspace='0' align='left' />\n"
	."\t\t\t\n</td><td align='left'>"
	."\t\t\t\t<form method='post' action='$scriptname?action=tokens'>\n"
	."\t\t\t\t\t<input type='text' name='searchstring' value='$searchstring' />\n"
	."\t\t\t\t\t<input type='submit' value='".$clang->gT("Search")."' />\n"
	."\t\t\t\t<input type='hidden' name='order' value='$order' />\n"
	."\t\t\t\t<input type='hidden' name='subaction' value='search' />\n"
	."\t\t\t\t<input type='hidden' name='sid' value='$surveyid' />\n"
	."\t\t\t\t</form>\n"
	."\t\t</td>\n"
	."\t\t<td align='right'><form action='$homeurl/admin.php'>\n"
	."\t\t<font size='1' face='verdana'>"
	."&nbsp;".$clang->gT("Records Displayed:")."<input type='text' size='4' value='$limit' name='limit' />"
	."&nbsp;".$clang->gT("Starting From:")."<input type='text' size='4' value='$start' name='start' />"
	."&nbsp;<input type='submit' value='".$clang->gT("Show")."' />\n"
	."\t\t</font>\n"
	."\t\t<input type='hidden' name='sid' value='$surveyid' />\n"
	."\t\t<input type='hidden' name='action' value='tokens' />\n"
	."\t\t<input type='hidden' name='subaction' value='browse' />\n"
	."\t\t<input type='hidden' name='order' value='$order' />\n"
	."\t\t<input type='hidden' name='searchstring' value='$searchstring' />\n"
	."\t\t</form></td>\n"
	."\t</tr>\n";
	$bquery = "SELECT * FROM ".db_table_name("tokens_$surveyid");
	$bresult = db_select_limit_assoc($bquery, 1) or safe_die($clang->gT("Error")." counting fields<br />".$connect->ErrorMsg());
	$bfieldcount=$bresult->FieldCount()-1;
	$bquery = "SELECT tid,firstname,lastname,email,emailstatus,token,language,sent,completed,attribute_1,attribute_2,mpid FROM ".db_table_name("tokens_$surveyid");
	if ($searchstring)
	{
		$bquery .= " WHERE firstname LIKE '%$searchstring%' "
		. "OR lastname LIKE '%$searchstring%' "
		. "OR email LIKE '%$searchstring%' "
		. "OR emailstatus LIKE '%$searchstring%' "
		. "OR token LIKE '%$searchstring%'";
		if ($bfieldcount == 11)
		{
			$bquery .= " OR attribute_1 like '%$searchstring%' "
			. "OR attribute_2 like '%$searchstring%'";
		}
	}
	if (!isset($order) || !$order) {$bquery .= " ORDER BY tid";}
	else {$bquery .= " ORDER BY $order"; }
	//safe_die($bquery.":::".$start.":::".$limit);
	$bresult = db_select_limit_assoc($bquery, $limit, $start) or safe_die ($clang->gT("Error").": $bquery<br />".$connect->ErrorMsg());
	$bgc="";

	$tokenoutput .= "<tr><td colspan='3'>\n"
	."<table width='100%' cellpadding='1' cellspacing='1' border='0' style='border: 1px solid rgb(85, 85, 85);'>\n";
	//COLUMN HEADINGS
	$tokenoutput .= "\t<tr>\n"
	."\t\t<th align='left' valign='top' class='settingcaption'>"
	."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=tid&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
	."<img src='$imagefiles/downarrow.png' alt='' title='"
	.$clang->gT("Sort by: ")."ID' border='0' align='left' hspace='0' /></a>"."ID</th>\n"
	."\t\t<th align='left' valign='top' class='settingcaption'>"
	."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=firstname&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
	."<img src='$imagefiles/downarrow.png' alt='' title='"
	.$clang->gT("Sort by: ").$clang->gT("First Name")."' border='0' align='left' /></a>".$clang->gT("First Name")."</th>\n"
	."\t\t<th align='left' valign='top' class='settingcaption'>"
	."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=lastname&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
	."<img src='$imagefiles/downarrow.png' alt='' title='"
	.$clang->gT("Sort by: ").$clang->gT("Last Name")."' border='0' align='left' /></a>".$clang->gT("Last Name")."</th>\n"

	."\t\t<th align='left' valign='top' class='settingcaption'>"
	."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=email&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
	."<img src='$imagefiles/downarrow.png' alt='' title='"
	.$clang->gT("Sort by: ").$clang->gT("Email")."' border='0' align='left' /></a>".$clang->gT("Email")."</th>\n"

	."\t\t<th align='left' valign='top' class='settingcaption'>"
	."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=emailstatus&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
	."<img src='$imagefiles/downarrow.png' alt='' title='"
	.$clang->gT("Sort by: ").$clang->gT("Email Status")."' border='0' align='left' /></a>".$clang->gT("Email Status")."</th>\n"

	."\t\t<th align='left' valign='top' class='settingcaption'>"
	."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=token&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
	."<img src='$imagefiles/downarrow.png' alt='' title='"
	.$clang->gT("Sort by: ").$clang->gT("Token")."' border='0' align='left' /></a>".$clang->gT("Token")."</th>\n"

	."\t\t<th align='left' valign='top' class='settingcaption'>"
	."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=language&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
	."<img src='$imagefiles/downarrow.png' alt='' title='"
	.$clang->gT("Sort by: ").$clang->gT("Language")."' border='0' align='left' /></a>".$clang->gT("Language")."</th>\n"

	."\t\t<th align='left' valign='top' class='settingcaption'>"
	."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=sent%20desc&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
	."<img src='$imagefiles/downarrow.png' alt='' title='"
	.$clang->gT("Sort by: ").$clang->gT("Invite sent?")."' border='0' align='left' /></a>".$clang->gT("Invite sent?")."</th>\n"
	."\t\t<th align='left' valign='top' class='settingcaption'>"
	."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=completed%20desc&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
	."<img src='$imagefiles/downarrow.png' alt='' title='"
	.$clang->gT("Sort by: ").$clang->gT("Completed?")."' border='0' align='left' /></a>".$clang->gT("Completed?")."</th>\n";
	if ($bfieldcount == 11)
	{
		$tokenoutput .= "\t\t<th align='left' valign='top' class='settingcaption'>"
		."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=attribute_1&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
		."<img src='$imagefiles/downarrow.png' alt='' title='"
		.$clang->gT("Sort by: ").$clang->gT("Attribute 1")."' border='0' align='left' /></a>".$attr1_name."</th>\n"
		."\t\t<th align='left' valign='top' class='settingcaption'>"
		."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=attribute_2&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
		."<img src='$imagefiles/downarrow.png' alt='' title='"
		.$clang->gT("Sort by: ").$clang->gT("Attribute 2")."' border='0' align='left' /></a>".$attr2_name."</th>\n";
		//."\t\t<th align='left' valign='top'>"
		//."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse&amp;order=mpid&amp;start=$start&amp;limit=$limit&amp;searchstring=$searchstring'>"
		//."<img src='$imagefiles/downarrow.png' alt='"
		//.$clang->gT("Sort by: ").$clang->gT("MPID")."' border='0' align='left'></a>".$clang->gT("MPID")."</th>\n";
	}
	$tokenoutput .= "\t\t<th align='left' valign='top' colspan='4' class='settingcaption'>".$clang->gT("Actions")."</th>\n"
	."\t</tr>\n";

	while ($brow = $bresult->FetchRow())
	{
		$brow['token'] = trim($brow['token']);
		if ($bgc == "evenrow") {$bgc = "oddrow";} else {$bgc = "evenrow";}
		$tokenoutput .= "\t<tr class='$bgc'>\n";
		foreach ($brow as $a=>$b)
		{
			if ($a =='email' && $brow['emailstatus'] != 'OK')
			{
				$tokenoutput .= "\t\t<td class='$bgc'><span class='invalidemail'>$brow[$a]</span></td>\n";
			}
			else
			{
				$tokenoutput .= "\t\t<td class='$bgc'>$brow[$a]</td>\n";
			}
		}
		if ($sumrows5['edit_survey_property'] || 
			$sumrows5['activate_survey'] ||
			$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
		{
			$tokenoutput .= "\t\t<td align='left'>\n"
			."\t\t\t<input style='height: 16; width: 16px; font-size: 8; font-family: verdana' type='submit' value='E' title='"
			.$clang->gT("Edit Token Entry")."' onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=edit&amp;tid=".$brow['tid']."', '_self')\" />"
			."<input style='height: 16; width: 16px; font-size: 8; font-family: verdana' type='submit' value='D' title='"
//			.$clang->gT("Delete Token Entry")."' onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=delete&amp;tid=".$brow['tid']."&amp;limit=$limit&amp;start=$start&amp;order=$order', '_self')\" />";
			.$clang->gT("Delete Token Entry")."' onclick=\"".get2post("$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=delete&amp;tid=".$brow['tid']."&amp;limit=$limit&amp;start=$start&amp;order=$order")."\" />";
			if (($brow['completed'] == "N" || $brow['completed'] == "") &&$brow['token']) {$tokenoutput .= "<input style='height: 16; width: 16px; font-size: 8; font-family: verdana' type='submit' value='S' title='".$clang->gT("Do Survey")."' onclick=\"window.open('$publicurl/index.php?sid=$surveyid&amp;lang=".$brow['language']."&amp;token=".trim($brow['token'])."', '_blank')\" />\n";}
			$tokenoutput .= "\n\t\t</td>\n";
		}
		if ($brow['completed'] != "N" && $brow['completed']!="" && $surveyprivate == "N")
		{
			// Get response Id
			$query="SELECT id FROM ".db_table_name("survey_$surveyid")." WHERE token='".$brow['token']."' ORDER BY id desc";
			$result=db_execute_num($query) or safe_die ("<br />Could not find token!<br />\n" .$connect->ErrorMsg());
			list($id) = $result->FetchRow();


			// UPDATE button to the tokens display in the MPID Actions column
			if  ($id)
			{
				$tokenoutput .= "\t\t<form action='$homeurl/$scriptname?action=browse' method='post' target='_blank'>\n"
				."\t\t<td align='center' valign='top'>\n"
				."\t\t\t<input style='height: 16; width: 16px; font-size: 8; font-family: verdana' type='submit' value='V' title='"
				.$clang->gT("View Response")."' />\n"
				."\t\t</td>\n"
				."\t\t<input type='hidden' name='sid' value='$surveyid' />\n"
				."\t\t<input type='hidden' value='id' name='subaction' />\n"
				."\t\t<input type='hidden' value='$id' name='id' />\n"
				."\t\t</form>\n";


				$tokenoutput .= "\t\t<form action='$homeurl/$scriptname?action=dataentry&amp;subaction=edit' method='post' target='_blank'>\n";
				$tokenoutput .= "\t\t<form action='$homeurl/$scriptname?action=dataentry' method='post' target='_blank'>\n"
				."\t\t<td align='center' valign='top'>\n"
				."\t\t\t<input style='height: 16; width: 16px; font-size: 8; font-family: verdana' type='submit' value='U' title='"
				.$clang->gT("Update Response")."' />\n"
				."\t\t</td>\n"
				."\t\t<input type='hidden' name='sid' value='$surveyid' />\n"
//				."\t\t<input type='hidden' $subaction value='edit' />\n"
				."\t\t<input type='hidden' name='surveytable' value='survey_$surveyid' />\n"
				."\t\t<input type='hidden' name='id' value='$id' />\n"
				."\t\t</form>\n";
			}
		}

		elseif ($brow['completed'] == "N" && $brow['token'] && $brow['sent'] == "N")

		{
			$tokenoutput .= "\t\t<td align='center' valign='middle'>\n"
			."\t\t\t<input style='height: 16; width: 16px; font-size: 8; font-family: verdana' type='submit' value='I' title='"
			.$clang->gT("Send invitation email to this entry")."' onclick=\"window.open('{$_SERVER['PHP_SELF']}?action=tokens&amp;sid=$surveyid&amp;subaction=email&amp;tid=".$brow['tid']."', '_self')\" />"
			."\t\t</td>\n";
		}

		elseif ($brow['completed'] == "N" && $brow['token'] && $brow['sent'] != "N")

		{
			$tokenoutput .= "\t\t<td align='center' valign='middle'>\n"
			."\t\t\t<input style='height: 16; width: 16px; font-size: 8; font-family: verdana' type='submit' value='R' title='"
			.$clang->gT("Send reminder email to this entry")."' onclick=\"window.open('{$_SERVER['PHP_SELF']}?sid=$surveyid&amp;action=tokens&amp;subaction=remind&amp;tid=".$brow['tid']."', '_self')\" />"
			."\t\t</td>\n";
		}
		else
		{
			$tokenoutput .= "\t\t<td>\n"
			."\t\t</td>\n";
		}
		$tokenoutput .= "\t</tr>\n";
	}
	$tokenoutput .= "</table>\n"
	."</td></tr></table>\n";
}

if ($subaction == "kill" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$date = date('YmdHis');
	$tokenoutput .= "\t<tr><td colspan='2' height='4' align='center'>"
	."<strong>".$clang->gT("Delete Tokens Table").":</strong>"
	."</td></tr>\n"
	."\t<tr><td colspan='2' align='center'>\n"
	."<br />\n";
	// ToDo: Just delete it if there is no token in the table
	if (!isset($_POST['ok']) || !$_POST['ok'])
	{
		$tokenoutput .= "<font color='red'><strong>".$clang->gT("Warning")."</strong></font><br />\n"
		.$clang->gT("If you delete this table tokens will no longer be required to access this survey.")."<br />".$clang->gT("A backup of this table will be made if you proceed. Your system administrator will be able to access this table.")."<br />\n"
		."( \"old_tokens_{$surveyid}_$date\" )<br /><br />\n"
		."<input type='submit' value='"
//		.$clang->gT("Delete Tokens")."' onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=kill&amp;ok=surething', '_self')\" /><br />\n"
		.$clang->gT("Delete Tokens")."' onclick=\"".get2post("$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=kill&amp;ok=surething")."\" /><br />\n"
		."<input type='submit' value='"
		.$clang->gT("Cancel")."' onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid', '_self')\" />\n";
	}
	elseif (isset($_POST['ok']) && $_POST['ok'] == "surething")
	{
		$oldtable = "tokens_$surveyid";
		$newtable = "old_tokens_{$surveyid}_$date";   
		$deactivatequery = db_rename_table( db_table_name_nq($oldtable), db_table_name_nq($newtable));
	  
	if ($databasetype=='postgres')
	    {
	    // If you deactivate a postgres table you have to rename the according sequence too and alter the id field to point to the changed sequence
	    	$oldTableJur = db_table_name_nq($oldtable);
		$deactivatequery = db_rename_table(db_table_name_nq($oldtable),db_table_name_nq($newtable).'_tid_seq');
			$deactivateresult = $connect->Execute($deactivatequery) or die ("oldtable : ".$oldtable. " / oldtableJur : ". $oldTableJur . " / ".htmlspecialchars($deactivatequery)." / Could not rename the old sequence for this token table. The database reported the following error:<br />".htmlspecialchars($connect->ErrorMsg())."<br /><br /><a href='$scriptname?sid={$_GET['sid']}'>".$clang->gT("Main Admin Screen")."</a>");
	        $setsequence="ALTER TABLE ".db_table_name_nq($newtable)."_tid_seq ALTER COLUMN tid SET DEFAULT nextval('".db_table_name_nq($newtable)."_tid_seq'::regclass);";
			$deactivateresult = $connect->Execute($setsequence) or die (htmlspecialchars($setsequence)." Could not alter the field tid to point to the new sequence name for this token table. The database reported the following error:<br />".htmlspecialchars($connect->ErrorMsg())."<br /><br />Survey was not deactivated either.<br /><br /><a href='$scriptname?sid={$_GET['sid']}'>".$clang->gT("Main Admin Screen")."</a>");
	    	$setidx="ALTER INDEX ".db_table_name_nq($oldtable)."_idx RENAME TO ".db_table_name_nq($newtable)."_idx;";
			$deactivateresult = $connect->Execute($setidx) or die (htmlspecialchars($setidx)." Could not alter the index for this token table. The database reported the following error:<br />".htmlspecialchars($connect->ErrorMsg())."<br /><br />Survey was not deactivated either.<br /><br /><a href='$scriptname?sid={$_GET['sid']}'>".$clang->gT("Main Admin Screen")."</a>");
		} else {
		$deactivateresult = $connect->Execute($deactivatequery) or die ("Couldn't deactivate because:<br />\n".htmlspecialchars($connect->ErrorMsg())." - Query: ".htmlspecialchars($deactivatequery)." <br /><br />\n<a href='$scriptname?sid=$surveyid'>Admin</a>\n");
	    }
		$tokenoutput .= "<span style='display: block; text-align: center; width: 70%'>\n"
		.$clang->gT("The tokens table has now been removed and tokens are no longer required to access this survey.")."<br /> ".$clang->gT("A backup of this table has been made and can be accessed by your system administrator.")."<br />\n"
		."(\"{$dbprefix}old_tokens_{$surveyid}_$date\")"."<br /><br />\n"
		."<input type='submit' value='"
		.$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname?sid={$surveyid}', '_self')\" />\n"
		."</span>\n";
	}
	$tokenoutput .= "</font></td></tr></table>\n"
	."<table><tr><td></td></tr></table>\n";

}


if ($subaction == "email" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
    if (getEmailFormat($surveyid) == 'html')
    {
        $ishtml=true;
    }
    else
    {
        $ishtml=false;
    }    

	$tokenoutput .= PrepareEditorScript();
	$tokenoutput .= "\t<tr>\n\t\t<td colspan='2' height='4'>"
	."<strong>"
	.$clang->gT("Email Invitation").":</strong></td>\n\t</tr>\n"
	."\t<tr>\n\t\t<td colspan='2' align='center'>\n";
    if (!isset($_POST['ok']) || !$_POST['ok'])
	{

		$tokenoutput .= "<form method='post' action='$scriptname?action=tokens&amp;sid=$surveyid'>";
		
		$surveylangs = GetAdditionalLanguagesFromSurveyID($surveyid);
		$baselang = GetBaseLanguageFromSurveyID($surveyid);
		array_unshift($surveylangs,$baselang);
		$tokenoutput .= "<div class='tab-pane' id='tab-pane-1'>";
        foreach ($surveylangs as $language)
	    {
			//GET SURVEY DETAILS
			$thissurvey=getSurveyInfo($surveyid,$language);
			if (!$thissurvey['email_invite']) 
            {
                if ($ishtml===true)
                {
                    $thissurvey['email_invite']=html_escape(str_replace("\n", "<br />", $clang->gT("Dear {FIRSTNAME},\n\nYou have been invited to participate in a survey.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}",'unescaped')));
                }
                else
                {
                    $thissurvey['email_invite']=str_replace("\n", "\r\n", $clang->gT("Dear {FIRSTNAME},\n\nYou have been invited to participate in a survey.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}"));
                }
            }
            if (!$thissurvey['email_invite_subj'])
            {
                $thissurvey['email_invite_subj']=$clang->gT("Invitation to participate in survey");
            }
			$fieldsarray["{ADMINNAME}"]= $thissurvey['adminname'];
			$fieldsarray["{ADMINEMAIL}"]=$thissurvey['adminemail'];
			$fieldsarray["{SURVEYNAME}"]=$thissurvey['name'];
			$fieldsarray["{SURVEYDESCRIPTION}"]=$thissurvey['description'];

			$subject=Replacefields($thissurvey['email_invite_subj'], $fieldsarray);
			$textarea=Replacefields($thissurvey['email_invite'], $fieldsarray);

	    	$tokenoutput .= '<div class="tab-page"> <h2 class="tab">'.getLanguageNameFromCode($language,false);
	    	if ($language==$baselang) 
	        {
	            $tokenoutput .= "(".$clang->gT("Base Language").")";
	        }    
	        $tokenoutput .= "</h2><table class='table2columns'>\n"
			."\n";

			$tokenoutput .= "\t<tr>\n"
			."\t\t<td align='right'><strong>".$clang->gT("From").":</strong></font></td>\n"
			."\t\t<td><input type='text' size='50' name='from_$language' value=\"{$thissurvey['adminname']} <{$thissurvey['adminemail']}>\" /></td>\n"
			."\t</tr>\n"
			."\t<tr>\n"
			."\t\t<td align='right'><strong>".$clang->gT("Subject").":</strong></font></td>\n"
			."\t\t<td><input type='text' size='83' name='subject_$language' value=\"$subject\" /></td>\n"
			."\t</tr>\n"
			."\t<tr>\n"
			."\t\t<td align='right' valign='top'><strong>".$clang->gT("Message").":</strong></font></td>\n"
			."\t\t<td>\n"
			."\t\t\t<textarea name='message_$language' id='message_$language' rows='20' cols='80'>\n"
			.$textarea
			."\t\t\t</textarea>\n"
			. getEditor("email-inv","message_$language","[".$clang->gT("Invitation Email:", "js")."](".$language.")",$surveyid,'','',$action)
			."\t\t</td>\n"
			."\t</tr></table></div>\n";
		}
		$tokenoutput .= "</div><table class='table2columns'>";
		if (isset($tokenid))
			{
				$tokenoutput .= "<tr><td colspan='2'>"
				.$clang->gT("Sending to Token ID").":&nbsp;".$tokenid
				."</td></tr>";
			}		
		$tokenoutput .="\t<tr><td>&nbsp;</td>\n"
		."\t<td align='left'>".$clang->gT("Bypass token with failing email addresses").":&nbsp;<select name='bypassbademails'>\n"
		. "\t\t<option value='Y'>".$clang->gT("Yes")."</option>"
		. "\t\t<option value='N'>".$clang->gT("No")."</option>"
		. "\t</select><br/>\n"
		. "\t\n"
		. "\t<input type='submit' value='"
		.$clang->gT("Send Invitations")."'>\n"
		."\t<input type='hidden' name='ok' value='absolutely' />\n"
		."\t<input type='hidden' name='sid' value='{$_GET['sid']}' />\n"
		."\t<input type='hidden' name='subaction' value='email' /></td></tr>\n";
		if (isset($tokenid)) {$tokenoutput .= "\t<input type='hidden' name='tid' value='$tokenid' />";}
		$tokenoutput .= "\n"
		."</table></form>\n";
	}
	else
	{
		$tokenoutput .= $clang->gT("Sending Invitations");
		if (isset($tokenid)) {$tokenoutput .= " (".$clang->gT("Sending to Token ID").":&nbsp;{$tokenid})";}
		$tokenoutput .= "<br />\n";

		if (isset($_POST['bypassbademails']) && $_POST['bypassbademails'] == 'Y')
		{
			$SQLemailstatuscondition = " AND emailstatus = 'OK'";
		}
		else
		{
			$SQLemailstatuscondition = "";
		}

		$ctquery = "SELECT * FROM ".db_table_name("tokens_{$surveyid}")." WHERE ((completed ='N') or (completed='')) AND ((sent ='N') or (sent='')) AND token !='' AND email != '' $SQLemailstatuscondition";

		if (isset($tokenid)) {$ctquery .= " AND tid='{$tokenid}'";}
		$tokenoutput .= "<!-- ctquery: $ctquery -->\n";
		$ctresult = $connect->Execute($ctquery) or safe_die("Database error!<br />\n" . $connect->ErrorMsg());
		$ctcount = $ctresult->RecordCount();
		$ctfieldcount = $ctresult->FieldCount();

		$emquery = "SELECT firstname, lastname, email, token, tid, language";
		if ($ctfieldcount > 7) {$emquery .= ", attribute_1, attribute_2";}

		$emquery .= " FROM ".db_table_name("tokens_{$surveyid}")." WHERE ((completed ='N') or (completed='')) AND ((sent ='N') or (sent='')) AND token !='' AND email != '' $SQLemailstatuscondition";

		if (isset($tokenid)) {$emquery .= " and tid='{$tokenid}'";}
		$tokenoutput .= "\n\n<!-- emquery: $emquery -->\n\n";
		$emresult = db_select_limit_assoc($emquery,$maxemails) or safe_die ("Couldn't do query.<br />\n$emquery<br />\n".$connect->ErrorMsg());
		$emcount = $emresult->RecordCount();

		$tokenoutput .= "<table width='500px' align='center' >\n"
		."\t<tr>\n"
		."\t\t<td><font size='1'>\n";

		$surveylangs = GetAdditionalLanguagesFromSurveyID($surveyid);
		$baselanguage = GetBaseLanguageFromSurveyID($surveyid);
		array_unshift($surveylangs,$baselanguage);
		
		foreach ($surveylangs as $language)
		    {
			$_POST['message_'.$language]=auto_unescape($_POST['message_'.$language]);
			$_POST['subject_'.$language]=auto_unescape($_POST['subject_'.$language]);
            if ($ishtml) $_POST['message_'.$language] = html_entity_decode_php4($_POST['message_'.$language], ENT_QUOTES, "UTF-8");
			
			}

		
		if ($emcount > 0)
		{
			while ($emrow = $emresult->FetchRow())
			{
				unset($fieldsarray);
				$to = $emrow['email'];
				$fieldsarray["{EMAIL}"]=$emrow['email'];
				$fieldsarray["{FIRSTNAME}"]=$emrow['firstname'];
				$fieldsarray["{LASTNAME}"]=$emrow['lastname'];
				$fieldsarray["{TOKEN}"]=$emrow['token'];
				$fieldsarray["{LANGUAGE}"]=$emrow['language'];
				$fieldsarray["{ATTRIBUTE_1}"]=$emrow['attribute_1'];
				$fieldsarray["{ATTRIBUTE_2}"]=$emrow['attribute_2'];

				$emrow['language']=trim($emrow['language']);
				if ($emrow['language']=='') {$emrow['language']=$baselanguage;} //if language is not give use default
				$found = array_search($emrow['language'], $surveylangs);
				if ($found==false) {$emrow['language']=$baselanguage;} 
				
				$from = $_POST['from_'.$emrow['language']];


				if ($ishtml === false)
				{
					$fieldsarray["{SURVEYURL}"]="$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}";
				}
				else
				{
					$fieldsarray["{SURVEYURL}"]="<a href='$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}'>".htmlspecialchars("$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}")."</a>";

                }
                
				$modsubject=Replacefields($_POST['subject_'.$emrow['language']], $fieldsarray);
				$modmessage=Replacefields($_POST['message_'.$emrow['language']], $fieldsarray);
                if (MailTextMessage($modmessage, $modsubject, $to , $from, $sitename, $ishtml, getBounceEmail($surveyid)))
				{
					// Put date into sent
					$today = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i", $timeadjust);
					$udequery = "UPDATE ".db_table_name("tokens_{$surveyid}")."\n"
					."SET sent='$today' WHERE tid={$emrow['tid']}";
					//
					$uderesult = $connect->Execute($udequery) or safe_die ("Could not update tokens<br />$udequery<br />".$connect->ErrorMsg());
					$tokenoutput .= "[".$clang->gT("Invitation sent to:")."{$emrow['firstname']} {$emrow['lastname']} ($to)]<br />\n";
				}
				else
				{
					$tokenoutput .= ReplaceFields($clang->gT("Email to {FIRSTNAME} {LASTNAME} ({EMAIL}) failed. Error Message:")." ".$maildebug."<br />", $fieldsarray);
					if ($debug>0) 
					{
						$tokenoutput .= "<br /><pre>Subject : $modsubject<br /><br />".htmlspecialchars($maildebugbody)."<br /></pre>";
					}
				}
			}
			if ($ctcount > $emcount)
			{
				$lefttosend = $ctcount-$maxemails;
				$tokenoutput .= "\t\t</td>\n"
				."\t</tr>\n"
				."\t<tr>\n"
				."\t\t<td align='center'><strong>".$clang->gT("Warning")."</strong><br />\n"
                ."\t\t\t<form method='post' action='$scriptname?action=tokens&amp;sid=$surveyid'>"
				.$clang->gT("There are more emails pending than can be sent in one batch. Continue sending emails by clicking below.")."<br /><br />\n";
				$tokenoutput .= str_replace("{EMAILCOUNT}", "$lefttosend", $clang->gT("There are {EMAILCOUNT} emails still to be sent."));
				$tokenoutput .= "<br /><br />\n";
				$tokenoutput .= "\t\t\t<input type='submit' value='".$clang->gT("Continue")."' />\n"
				."\t\t\t<input type='hidden' name='ok' value=\"absolutely\" />\n"
				."\t\t\t<input type='hidden' name='subaction' value=\"email\" />\n"
                ."\t\t\t<input type='hidden' name='action' value=\"tokens\" />\n"
				."\t\t\t<input type='hidden' name='sid' value=\"{$surveyid}\" />\n";
		        foreach ($surveylangs as $language)
				    {
          			$message = html_escape($_POST['message_'.$language]);
          			$subject = html_escape($_POST['subject_'.$language]);
					$tokenoutput .="\t\t\t<input type='hidden' name='from_$language' value=\"".$_POST['from_'.$language]."\" />\n"
					."\t\t\t<input type='hidden' name='subject_$language' value=\"".$_POST['subject_'.$language]."\" />\n"
					."\t\t\t<input type='hidden' name='message_$language' value=\"$message\" />\n";
					}
				$tokenoutput .="\t\t\t</form>\n";
			}
		}
		else
		{
			$tokenoutput .= "<center><strong>".$clang->gT("Warning")."</strong><br />\n".$clang->gT("There were no eligible emails to send. This will be because none satisfied the criteria of - having an email address, not having been sent an invitation already, having already completed the survey and having a token.")."</center>\n";
		}
		$tokenoutput .= "\t\t</td>\n";

	}
	$tokenoutput .= "</td></tr></table>\n";
}


if ($subaction == "remind" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$tokenoutput .= PrepareEditorScript();
	$tokenoutput .= "\t<tr><td colspan='2' height='4'><strong>"
	.$clang->gT("Email Reminder").":</strong></td></tr>\n"
	."\t<tr><td colspan='2' align='center'>\n";
	if (!isset($_POST['ok']) || !$_POST['ok'])
	{
		//GET SURVEY DETAILS
		$tokenoutput .= "<form method='post' action='$scriptname?action=tokens'>";
		$surveylangs = GetAdditionalLanguagesFromSurveyID($surveyid);
		$baselang = GetBaseLanguageFromSurveyID($surveyid);
		array_unshift($surveylangs,$baselang);

		$tokenoutput .= "<div class='tab-pane' id='tab-pane-1'>";
        foreach ($surveylangs as $language)
	    {
			//GET SURVEY DETAILS
			$thissurvey=getSurveyInfo($surveyid,$language);
			if (!$thissurvey['email_remind']) {$thissurvey['email_remind']=str_replace("\n", "\r\n", $clang->gT("Dear {FIRSTNAME},\n\nRecently we invited you to participate in a survey.\n\nWe note that you have not yet completed the survey, and wish to remind you that the survey is still available should you wish to take part.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}"));}
	    	$tokenoutput .= '<div class="tab-page"> <h2 class="tab">'.getLanguageNameFromCode($language,false);
	    	if ($language==$baselang) 
	        {
	            $tokenoutput .= "(".$clang->gT("Base Language").")";
	        }    
	        $tokenoutput .= "</h2><table class='table2columns' >\n"
			."\n"
			."\t<tr>\n"
			."\t\t<td align='right' width='150'><strong>".$clang->gT("From").":</strong></td>\n"
			."\t\t<td><input type='text' size='50' name='from_$language' value=\"{$thissurvey['adminname']} <{$thissurvey['adminemail']}>\" /></td>\n"
			."\t</tr>\n"
			."\t<tr>\n"
			."\t\t<td align='right' width='150'><strong>".$clang->gT("Subject").":</strong></td>\n";

            $fieldsarray["{ADMINNAME}"]= $thissurvey['adminname'];
            $fieldsarray["{ADMINEMAIL}"]=$thissurvey['adminemail'];
            $fieldsarray["{SURVEYNAME}"]=$thissurvey['name'];
            $fieldsarray["{SURVEYDESCRIPTION}"]=$thissurvey['description'];
    
            $subject=Replacefields($thissurvey['email_remind_subj'], $fieldsarray);
            $textarea=Replacefields($thissurvey['email_remind'], $fieldsarray);

			$tokenoutput .= "\t\t<td><input type='text' size='83' name='subject_$language' value=\"$subject\" /></td>\n"
			."\t</tr>\n";
	
			$tokenoutput .= "\t<tr>\n"
			."\t\t<td align='right' width='150' valign='top'><strong>"
			.$clang->gT("Message").":</strong></td>\n"
			."\t\t<td>\n"
			."\t\t\t<textarea name='message_$language' id='message_$language' rows='20' cols='80' >\n";
	
			$tokenoutput .= $textarea;
	
			$tokenoutput .= "\t\t\t</textarea>\n"
			. getEditor("email-rem","message_$language","[".$clang->gT("Reminder Email:", "js")."](".$language.")",$surveyid,'','',$action)
			."\t\t</td>\n"
			."\t</tr>\n"
			."</table></div>";
		}	

        $tokenoutput .= "</div><table class='table2columns'>\n";
		if (isset($tokenid))
		{
			$tokenoutput .= "\t<tr>\n"
			."\t\t<td align='right' width='150' valign='top'><strong>"
			.$clang->gT("Start at Token ID No:")."</strong></td>\n"
			."\t\t<td><input type='text' size='5' name='last_tid' /></td>\n"
			."\t</tr>\n";
		}
		else
		{
			$tokenoutput .= "\t<tr>\n"
			."\t\t<td align='right' width='150' valign='top'><strong>"
			.$clang->gT("Sending to Token ID").":&nbsp;</strong></font></td>\n"
			."\t\t<td>{$tokenid}</font></td>\n"
			."\t</tr>\n";
		}		
		$tokenoutput .="\t\t<tr><td>&nbsp;</td><td align='left'>\n"
		.$clang->gT("Bypass token with failing email addresses").":&nbsp;\n"
		."\t\t\t<select name='bypassbademails'>\n"
		."\t\t\t\t<option value='Y'>".$clang->gT("Yes")."</option>\n"
		."\t\t\t\t<option value='N'>".$clang->gT("No")."</option>\n"
		."\t\t\t</select><br/>\n"
		."\t\t\t<input type='submit' value='".$clang->gT("Send Reminders")."' />\n"
		."\t<input type='hidden' name='ok' value='absolutely' />\n"
		."\t<input type='hidden' name='sid' value='{$_GET['sid']}' />\n"
		."\t<input type='hidden' name='subaction' value='remind' />\n"
		."\t\t</td>\n"
		."\t</tr>\n";
		if (isset($tokenid)) {$tokenoutput .= "\t<input type='hidden' name='tid' value='{$tokenid}' />\n";}
		$tokenoutput .= "\t</table>\n"
		."</form>\n";
	}
	else
	{
		$tokenoutput .= $clang->gT("Sending Reminders")."<br />\n";
		
		$surveylangs = GetAdditionalLanguagesFromSurveyID($surveyid);
		$baselanguage = GetBaseLanguageFromSurveyID($surveyid);
		array_unshift($surveylangs,$baselanguage);
		
		foreach ($surveylangs as $language)
		    {
			$_POST['message_'.$language]=auto_unescape($_POST['message_'.$language]);
			$_POST['subject_'.$language]=auto_unescape($_POST['subject_'.$language]);
			}

		if (isset($starttokenid)) {$tokenoutput .= " (".$clang->gT("From Token ID").":&nbsp;{$starttokenid})";}
		if (isset($tokenid)) {$tokenoutput .= " (".$clang->gT("Sending to Token ID").":&nbsp;{$tokenid})";}

		if (isset($_POST['bypassbademails']) && $_POST['bypassbademails'] == 'Y')
		{
			$SQLemailstatuscondition = " AND emailstatus = 'OK'";
		}
		else
		{
			$SQLemailstatuscondition = "";
		}

		$ctquery = "SELECT * FROM ".db_table_name("tokens_{$surveyid}")." WHERE (completed ='N' or completed ='') AND sent<>'' AND sent<>'N' AND token <>'' AND email <> '' $SQLemailstatuscondition";

		if (isset($starttokenid)) {$ctquery .= " AND tid > '{$starttokenid}'";}
		if (isset($tokenid) && $tokenid) {$ctquery .= " AND tid = '{$tokenid}'";}
		$tokenoutput .= "<!-- ctquery: $ctquery -->\n";
		$ctresult = $connect->Execute($ctquery) or safe_die ("Database error!<br />\n" . $connect->ErrorMsg());
		$ctcount = $ctresult->RecordCount();
		$ctfieldcount = $ctresult->FieldCount();
		$emquery = "SELECT firstname, lastname, email, token, tid, language ";
		if ($ctfieldcount > 7) {$emquery .= ", attribute_1, attribute_2";}

		// TLR change to put date into sent
		$emquery .= " FROM ".db_table_name("tokens_{$surveyid}")." WHERE (completed = 'N' or completed = '') AND sent <> 'N' and sent<>'' AND token <>'' AND EMAIL <>'' $SQLemailstatuscondition";

		if (isset($starttokenid)) {$emquery .= " AND tid > '{$starttokenid}'";}
		if (isset($tokenid) && $tokenid) {$emquery .= " AND tid = '{$tokenid}'";}
		$emquery .= " ORDER BY tid ";
		$emresult = db_select_limit_assoc($emquery, $maxemails) or safe_die ("Couldn't do query.<br />$emquery<br />".$connect->ErrorMsg());
		$emcount = $emresult->RecordCount();
		$tokenoutput .= "<table width='500' align='center' >\n"
		."\t<tr>\n"
		."\t\t<td><font size='1'>\n";



		if ($emcount > 0)
		{
			while ($emrow = $emresult->FetchRow())
			{
				unset($fieldsarray);
				$to = $emrow['email'];
				$fieldsarray["{EMAIL}"]=$emrow['email'];
				$fieldsarray["{FIRSTNAME}"]=$emrow['firstname'];
				$fieldsarray["{LASTNAME}"]=$emrow['lastname'];
				$fieldsarray["{TOKEN}"]=$emrow['token'];
				$fieldsarray["{LANGUAGE}"]=$emrow['language'];
				$fieldsarray["{ATTRIBUTE_1}"]=$emrow['attribute_1'];
				$fieldsarray["{ATTRIBUTE_2}"]=$emrow['attribute_2'];

				$emrow['language']=trim($emrow['language']);
				if ($emrow['language']=='') {$emrow['language']=$baselanguage;} //if language is not give use default
				$found = array_search($emrow['language'], $surveylangs);
				if ($found==false) {$emrow['language']=$baselanguage;} 

				$from = $_POST['from_'.$emrow['language']];

				if (getEmailFormat($surveyid) == 'html')
				{
					$ishtml=true;
				}
				else
				{
					$ishtml=false;
				}

				if ($ishtml === false)
				{
					$fieldsarray["{SURVEYURL}"]="$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}";
				}
				else
				{
					$fieldsarray["{SURVEYURL}"]="<a href='$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}'>".htmlspecialchars("$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}")."</a>";
                    $_POST['message_'.$emrow['language']] = html_entity_decode_php4($_POST['message_'.$emrow['language']], ENT_QUOTES, "UTF-8");
                }

				$msgsubject=Replacefields($_POST['subject_'.$emrow['language']], $fieldsarray);
				$sendmessage=Replacefields($_POST['message_'.$emrow['language']], $fieldsarray);

				if (MailTextMessage($sendmessage, $msgsubject, $to, $from, $sitename,$ishtml,getBounceEmail($surveyid)))
				{
					$tokenoutput .= "\t\t\t({$emrow['tid']})[".$clang->gT("Reminder sent to:")." {$emrow['firstname']} {$emrow['lastname']}]<br />\n";
				}
				else
				{
                    $tokenoutput .= $emrow['tid'] ." ".ReplaceFields($clang->gT("Email to {FIRSTNAME} {LASTNAME} ({EMAIL}) failed. Error Message:")." ".$maildebug."<br />", $fieldsarray);
                    if ($debug>0) 
                    {
                        $tokenoutput .= "<pre>Subject : $msgsubject<br /><br />".htmlspecialchars($maildebugbody)."<br /></pre>";
                    }
               
                }
				$lasttid = $emrow['tid'];
			}
			if ($ctcount > $emcount)
			{
				$lefttosend = $ctcount-$maxemails;
				$tokenoutput .= "\t\t</td>\n"
				."\t</tr>\n"
                ."\t<tr><form method='post' action='$scriptname?action=tokens&amp;sid=$surveyid'>"
				."\t\t<td align='center'>\n"
				."\t\t\t<strong>".$clang->gT("Warning")."</strong><br /><br />\n"
				.$clang->gT("There are more emails pending than can be sent in one batch. Continue sending emails by clicking below.")."<br /><br />\n"
				.str_replace("{EMAILCOUNT}", $lefttosend, $clang->gT("There are {EMAILCOUNT} emails still to be sent."))
				."<br />\n"
				."\t\t\t<input type='submit' value='".$clang->gT("Continue")."' />\n"
				."\t\t</td>\n"
				."\t<input type='hidden' name='ok' value=\"absolutely\" />\n"
                ."\t<input type='hidden' name='subaction' value=\"remind\" />\n"
                ."\t<input type='hidden' name='action' value=\"tokens\" />\n"
				."\t<input type='hidden' name='sid' value=\"{$surveyid}\" />\n";
		        foreach ($surveylangs as $language)
				    {
          			$message = html_escape($_POST['message_'.$language]);
					$tokenoutput .="\t\t\t<input type='hidden' name='from_$language' value=\"".$_POST['from_'.$language]."\" />\n"
					."\t\t\t<input type='hidden' name='subject_$language' value=\"".$_POST['subject_'.$language]."\" />\n"
					."\t\t\t<input type='hidden' name='message_$language' value=\"$message\" />\n";
					}
				$tokenoutput.="\t<input type='hidden' name='last_tid' value=\"$lasttid\" />\n"
				."\t</form>\n";
			}
		}
		else
		{
			$tokenoutput .= "<center><strong>".$clang->gT("Warning")."</strong><br />\n"
			.$clang->gT("There were no eligible emails to send. This will be because none satisfied the criteria of - having an email address, having been sent an invitation, but not having yet completed the survey.")."\n"
			."<br /><br />\n"
			."\t\t</td>\n";
		}
		$tokenoutput .= "\t</tr>\n"
		."</table>\n";
	}
	$tokenoutput .= "</td></tr></table>\n";
}

if ($subaction == "tokenify" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$tokenoutput .= "\t<tr ><td colspan='2' height='4'><strong>".$clang->gT("Create Tokens").":</strong></td></tr>\n";
	$tokenoutput .= "\t<tr><td align='center'><br />\n";
	if (!isset($_POST['ok']) || !$_POST['ok'])
	{
		$tokenoutput .= "<br />".$clang->gT("Clicking yes will generate tokens for all those in this token list that have not been issued one. Is this OK?")."<br /><br />\n"
		."<input type='submit' value='"
//		.$clang->gT("Yes")."' onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=tokenify&amp;ok=Y', '_self')\" />\n"
		.$clang->gT("Yes")."' onclick=\"".get2post("$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=tokenify&amp;ok=Y")."\" />\n"
		."<input type='submit' value='"
		.$clang->gT("No")."' onclick=\"window.open('$scriptname?action=tokens&amp;sid=$surveyid', '_self')\" />\n"
		."<br /><br />\n";
	}
	else
	{
		$newtokencount = 0;
		$tkquery = "SELECT * FROM ".db_table_name("tokens_$surveyid")." WHERE token IS NULL OR token=''";
		$tkresult = db_execute_assoc($tkquery) or safe_die ("Mucked up!<br />$tkquery<br />".$connect->ErrorMsg());
		while ($tkrow = $tkresult->FetchRow())
		{
			$insert = "NO";
			while ($insert != "OK")
			{
				$newtoken = randomkey(15);
				$ntquery = "SELECT * FROM ".db_table_name("tokens_$surveyid")." WHERE token='$newtoken'";
				$ntresult = $connect->Execute($ntquery);
				if (!$ntresult->RecordCount()) {$insert = "OK";}
			}
			$itquery = "UPDATE ".db_table_name("tokens_$surveyid")." SET token='$newtoken' WHERE tid={$tkrow['tid']}";
			$itresult = $connect->Execute($itquery);
			$newtokencount++;
		}
		$message=str_replace("{TOKENCOUNT}", $newtokencount, $clang->gT("{TOKENCOUNT} tokens have been created"));
		$tokenoutput .= "<br /><strong>$message</strong><br /><br />\n";
	}
	$tokenoutput .= "\t</font></td></tr></table>\n";
}


if ($subaction == "delete" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$dlquery = "DELETE FROM ".db_table_name("tokens_$surveyid")." WHERE tid={$tokenid}";
	$dlresult = $connect->Execute($dlquery) or safe_die ("Couldn't delete record {$tokenid}<br />".$connect->ErrorMsg());
	$tokenoutput .= "\t<tr ><td colspan='2' height='4'><strong>"
	.$clang->gT("Delete")."</strong></td></tr>\n"
	."\t<tr><td align='center'><br />\n"
	."<br /><strong>".$clang->gT("Token has been deleted.")."</strong><br />\n"
	."<font size='1'><i>".$clang->gT("Reloading Screen. Please wait.")."</i><br /><br /></font>\n"
	."\t</td></tr></table>\n";
}

if (($subaction == "edit" || $subaction == "addnew") && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	if ($subaction == "edit")
	{
		$edquery = "SELECT * FROM ".db_table_name("tokens_$surveyid")." WHERE tid={$tokenid}";
		$edresult = db_execute_assoc($edquery);
		$edfieldcount = $edresult->FieldCount();
		while($edrow = $edresult->FetchRow())
		{
			//Create variables with the same names as the database column names and fill in the value
			foreach ($edrow as $Key=>$Value) {$$Key = $Value;}
		}
	}
	if ($subaction != "edit")
	{
		$edquery = "SELECT * FROM ".db_table_name("tokens_$surveyid");
		$edresult = db_select_limit_assoc($edquery, 1);
		$edfieldcount = $edresult->FieldCount();
	}

	$tokenoutput .= "\t</table>\n"
	."<form method='post' action='$scriptname?action=tokens'>\n"
	."<table width='100%' class='form2columns'>\n"
    ."<tr><th colspan='2' ><strong>\n"
    .$clang->gT("Add or Edit Token Entry")."</strong></th></tr><tr>\n"
	."\t<td align='right' width='20%'><strong>ID:</strong></td>\n"
	."\t<td>";
	if ($subaction == "edit")
	{$tokenoutput .=$tokenid;} else {$tokenoutput .=$clang->gT("Auto");}
	$tokenoutput .= "</td>\n"
	."</tr>\n"
	."<tr>\n"
	."\t<td align='right' width='20%'><strong>".$clang->gT("First Name").":</strong></td>\n"
	."\t<td><input type='text' size='30' name='firstname' value=\"";
	if (isset($firstname)) {$tokenoutput .= $firstname;}
	$tokenoutput .= "\" /></td>\n"
	."</tr>\n"
	."<tr>\n"
	."\t<td align='right' width='20%'><strong>".$clang->gT("Last Name").":</strong></td>\n"
	."\t<td ><input type='text' size='30' name='lastname' value=\"";
	if (isset($lastname)) {$tokenoutput .= $lastname;}
	$tokenoutput .= "\" /></td>\n"
	."</tr>\n"
	."<tr>\n"
	."\t<td align='right' width='20%'><strong>".$clang->gT("Email").":</strong></td>\n"
	."\t<td ><input type='text' maxlength='320' size='50' name='email' value=\"";
	if (isset($email)) {$tokenoutput .= $email;}
	$tokenoutput .= "\" /></td>\n"
	."</tr>\n"
	."<tr>\n"
	."\t<td align='right' width='20%'><strong>".$clang->gT("Email Status").":</strong></td>\n"
	."\t<td ><input type='text' maxlength='320' size='50' name='emailstatus' value=\"";
	if (isset($emailstatus))
	{
		$tokenoutput .= $emailstatus;
	}
	else
	{
		$tokenoutput .= "OK";
	}
	$tokenoutput .= "\" /></td>\n"
	."</tr>\n"
	."<tr>\n"
	."\t<td align='right' width='20%'><strong>".$clang->gT("Token").":</strong></td>\n"
	."\t<td ><input type='text' size='15' name='token' value=\"";
	if (isset($token)) {$tokenoutput .= $token;}
	$tokenoutput .= "\" />\n";
	if ($subaction == "addnew")
	{
		$tokenoutput .= "\t\t<font size='1' color='red'>".$clang->gT("You can leave this blank, and automatically generate tokens using 'Create Tokens'")."</font>\n";
	}
	$tokenoutput .= "\t</td>\n"
	."</tr>\n"

	."<tr>\n"
	."\t<td align='right' width='20%'><strong>".$clang->gT("Language").":</strong></td>\n"
	."\t<td>";
	if (isset($language)) {$tokenoutput .= languageDropdownClean($surveyid,$language);}
	else {
		$tokenoutput .= languageDropdownClean($surveyid,GetBaseLanguageFromSurveyID($surveyid));
	}
	$tokenoutput .= "</td>\n"
	."</tr>\n"


	."<tr>\n"
	."\t<td align='right' width='20%'><strong>".$clang->gT("Invite sent?").":</strong></td>\n"

	// TLR change to put date into sent and completed
	//	."\t<td bgcolor='#EEEEEE'><input type='text' size='1' name='sent' value=\"";
	."\t<td><input type='text' size='15' name='sent' value=\"";

	if (isset($sent)) {$tokenoutput .= $sent;}	else {$tokenoutput .= "N";}
	$tokenoutput .= "\" /></td>\n"
	."</tr>\n"
	."<tr>\n"
	."\t<td align='right' width='20%'><strong>".$clang->gT("Completed?").":</strong></td>\n"

	// TLR change to put date into sent and completed
	//	."\t<td bgcolor='#EEEEEE'><input type='text' size='1' name='completed' value=\"";
	."\t<td><input type='text' size='15' name='completed' value=\"";

	if (isset($completed)) {$tokenoutput .= $completed;} else {$tokenoutput .= "N";}
	if ($edfieldcount > 7)
	{
		$tokenoutput .= "\" /></td>\n"
		."</tr>\n"
		."<tr>\n"
		."\t<td align='right' width='20%'><strong>".$attr1_name.":</strong></td>\n"
		."\t<td ><input type='text' size='50' name='attribute1' value=\"";
		if (isset($attribute_1)) {$tokenoutput .= $attribute_1;}
		$tokenoutput .= "\" /></td>\n"
		."</tr>\n"
		."<tr>\n"
		."\t<td align='right' width='20%'><strong>".$attr2_name.":</strong></td>\n"
		."\t<td><input type='text' size='50' name='attribute2' value=\"";
		if (isset($attribute_2)) {$tokenoutput .= $attribute_2;}
	}
	$tokenoutput .= "\" /></td>\n"
	."</tr>\n"
	."<tr>\n"
	."\t<td colspan='2' class='centered'>";
	switch($subaction)
	{
		case "edit":
			$tokenoutput .= "\t\t<input type='submit' value='".$clang->gT("Update Token")."' />\n"
			."\t\t<input type='hidden' name='subaction' value='updatetoken' />\n"
			."\t\t<input type='hidden' name='tid' value='{$tokenid}' />\n";
			break;
		case "addnew":
			$tokenoutput .= "\t\t<input type='submit' value='".$clang->gT("Add Token")."' />\n"
			."\t\t<input type='hidden' name='subaction' value='inserttoken' />\n";
			break;
	}
	$tokenoutput .= "\t\t<input type='hidden' name='sid' value='$surveyid' />\n"
	."\t</td>\n"
	."</tr>\n\n"
	."</table></form></table>\n";
}


if ($subaction == "updatetoken" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$tokenoutput .= "\t<tr><td colspan='2' height='4'><strong>"
	.$clang->gT("Add or Edit Token Entry")."</strong></td></tr>\n"
	."\t<tr><td align='center'>\n";
	$data = array();
	$data[] = $_POST['firstname'];
	$data[] = $_POST['lastname'];
	$data[] = sanitize_email($_POST['email']);
	$data[] = $_POST['emailstatus'];
	$santitizedtoken=sanitize_xss_string(strip_tags($_POST['token']));
	$data[] = $santitizedtoken;
	$data[] = sanitize_languagecode($_POST['language']);
	$data[] = $_POST['sent'];
	$data[] = $_POST['completed'];

	$udresult = $connect->Execute("Select * from ".db_table_name("tokens_$surveyid")." where tid<>{$tokenid} and token<>'' and token='{$santitizedtoken}'") or safe_die ("Update record {$tokenid} failed:<br />\n$udquery<br />\n".$connect->ErrorMsg());
	if ($udresult->RecordCount()==0)
	{
		// Using adodb Execute with blinding method so auto-dbquote is done
		$udquery = "UPDATE ".db_table_name("tokens_$surveyid")." SET firstname=?, "
		. "lastname=?, email=?, emailstatus=?, "
		. "token=?, language=?, sent=?, completed=?";
		if (isset($_POST['attribute1']))
		{
			$data[] = $_POST['attribute1'];
			$data[] = $_POST['attribute2'];
			$udquery .= ", attribute_1=?, attribute_2=?";
		}
		$udquery .= " WHERE tid={$tokenid}";
		$udresult = $connect->Execute($udquery, $data) or safe_die ("Update record {$tokenid} failed:<br />\n$udquery<br />\n".$connect->ErrorMsg());
		$tokenoutput .=  "<br /><font class='successtitle'><strong>".$clang->gT("Success")."</strong></font><br />\n"
						."<br />".$clang->gT("Updated Token")."<br /><br />\n"
						."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse'>".$clang->gT("Display Tokens")."</a><br /><br />\n"
						."\t</td></tr></table>\n";
	}
	  else 
	  {
		$tokenoutput .=  "<br /><font color='red'><strong>".$clang->gT("Failed")."</strong></font><br />\n"
						."<br />".$clang->gT("There is already an entry with that exact token in the table. The same token cannot be used in multiple entries.")."<br /><br />\n"
						."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=edit&amp;tid={$tokenid}'>".$clang->gT("Show this token entry")."</a><br /><br />\n"
						."\t</td></tr></table>\n";
	  
	  }
		
}

if ($subaction == "inserttoken" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$santitizedtoken=trim(sanitize_xss_string(strip_tags($_POST['token'])));
	$tokenoutput .= "\t<tr><td colspan='2' height='4'><strong>"
	.$clang->gT("Add or Edit Token Entry")."</strong></td></tr>\n"
	."\t<tr><td align='center'>\n";
	$data = array('firstname' => $_POST['firstname'],
	'lastname' => $_POST['lastname'],
	'email' => sanitize_email($_POST['email']),
	'emailstatus' => $_POST['emailstatus'],
	'token' => $santitizedtoken,
	'language' => sanitize_languagecode($_POST['language']),
	'sent' => $_POST['sent'],
	'completed' => $_POST['completed']);
	if (isset($_POST['attribute1']))
	{
		$data['attribute_1'] = $_POST['attribute1'];
		$data['attribute_2'] = $_POST['attribute2'];
	}
	$tblInsert=db_table_name('tokens_'.$surveyid);


	$udresult = $connect->Execute("Select * from ".db_table_name("tokens_$surveyid")." where  token<>'' and token='{$santitizedtoken}'");
	if ($udresult->RecordCount()==0)
	{
		// AutoExecute
		$inresult = $connect->AutoExecute($tblInsert, $data, 'INSERT') or safe_die ("Add new record failed:<br />\n$inquery<br />\n".$connect->ErrorMsg());
		$tokenoutput .= "<br /><font class='successtitle'><strong>".$clang->gT("Success")."</strong></font><br />\n"
		."<br />".$clang->gT("Added New Token")."<br /><br />\n"
		."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse'>".$clang->gT("Display Tokens")."</a><br />\n"
		."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=addnew'>".$clang->gT("Add new token entry")."</a><br /><br />\n"
		."\t</td></tr></table>\n";
	}
	  else 
	  {
		$tokenoutput .=  "<br /><font color='red'><strong>".$clang->gT("Failed")."</strong></font><br />\n"
						."<br />".$clang->gT("There is already an entry with that exact token in the table. The same token cannot be used in multiple entries.")."<br /><br />\n"
						."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=browse'>".$clang->gT("Display Tokens")."</a><br />\n"
						."<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=addnew'>".$clang->gT("Add new token entry")."</a><br /><br />\n"
						."\t</td></tr></table>\n";
	  
	  }
}			

if ($subaction == "import" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$tokenoutput .= "\t<tr><td colspan='2' height='4'>"
	."<strong>".$clang->gT("Upload CSV File")."</strong></td></tr>\n"
	."\t<tr><td align='center'><br />\n";
	form_csv_upload();
	$tokenoutput .= "<br /><table class='alertbox'>\n"
	."\t<tr>\n"
	."\t\t<td align='center'>\n"
	."\t\t\t<font size='1'><strong>".$clang->gT("Note:")."</strong><br />\n"
	."\t\t\t".$clang->gT("File should be a standard CSV (comma delimited) file with double quotes around values (default for openoffice and excel). The first line should contain header information (will be removed). Data should be ordered as \"firstname, lastname, email, [emailstatus], [token], [language code], [attribute1], [attribute2]\".")."\n"
	."\t\t</font></td>\n"
	."\t</tr>\n"
	."</table><br />\n"  
	."</td></tr></table>\n";
}

if ($subaction == "importldap" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$tokenoutput .= "\t<tr><td colspan='2' height='4'>"
	."<strong>"
	.$clang->gT("Upload LDAP entries")."</strong></td></tr>\n"
	."\t<tr><td align='center'>\n";
	formldap();
	$tokenoutput .= "<table width='500' class='alertbox'>\n"
	."\t<tr>\n"
	."\t\t<td align='center'>\n"
	."\t\t\t<font size='1'><strong>".$clang->gT("Note:")."</strong><br />\n"
	."\t\t\t".$clang->gT("LDAP queries are defined by the administrator in the config-ldap.php file")."\n"
	."\t\t</font></td>\n"
	."\t</tr>\n"
	."</table><br />\n"
	."</td></tr></table>\n";
}

if ($subaction == "upload" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$tokenoutput .= "\t<tr><td colspan='2' height='4'><strong>"
	.$clang->gT("Upload CSV File")."</strong></td></tr>\n"
	."\t<tr><td align='center'>\n";
	if (!isset($tempdir))
	{
		$the_path = $homedir;
	}
	else
	{
		$the_path = $tempdir;
	}
	$the_file_name = $_FILES['the_file']['name'];
	$the_file = $_FILES['the_file']['tmp_name'];
	$the_full_file_path = $the_path."/".$the_file_name;
	if (!@move_uploaded_file($the_file, $the_full_file_path))
	{
		$errormessage="<strong><font color='red'>".$clang->gT("Error").":</font> ".$clang->gT("Upload file not found. Check your permissions and path for the upload directory")."</strong>\n";
		form_csv_upload($errormessage);
	}
	else
	{
		$tokenoutput .= "<br /><strong>".$clang->gT("Importing CSV File")."</strong><br />\n<font class='successtitle'>".$clang->gT("Success")."</font><br /><br />\n"
		.$clang->gT("Creating Token Entries")."<br />\n";
		$xz = 0; $xx = 0; $xy = 0; $xv = 0; $invalidemailcount = 0;
		// This allows to read file with MAC line endings too
		@ini_set('auto_detect_line_endings', true);
		// open it and trim the ednings
		$tokenlistarray = array_map('rtrim',file($the_full_file_path));
		if (!isset($tokenlistarray)) {$tokenoutput .= "Failed to open the uploaded file!\n";}
		foreach ($tokenlistarray as $buffer)
		{
            $buffer=@mb_convert_encoding($buffer,"UTF-8",$uploadcharset);
			$firstname = ""; $lastname = ""; $email = ""; $emailstatus="OK"; $token = ""; $language=""; $attribute1=""; $attribute2=""; //Clear out values from the last path, in case the next line is missing a value
			if ($xx==0)
			{
				//THIS IS THE FIRST LINE. IT IS THE HEADINGS. IGNORE IT
			}
			else
			{

				$line = convertCSVRowToArray($buffer,',','"');
				// sanitize it before writing into table
				$line = array_map('db_quote',$line);
				if (isset($line[0]) && $line[0] != "" & isset($line[1]) && $line[1] != "" && isset($line[2]))
				{
					// If old export file with first col as TID
					// with no attribute
					if (is_numeric($line[0])) 
					{
						$line[8] = $line[0];
						$line[0] = $line[1];
						$line[1] = $line[2];
						$line[2] = $line[3];
						$line[3] = "OK";
						$line[4] = $line[4];
						$line[5] = $line[5];
						$line[6] = $line[6];
					}
					
					$dupfound=false;
					$invalidemail=false;

					if ($filterduplicatetoken)
					{
						$dupquery = "SELECT firstname, lastname from ".db_table_name("tokens_$surveyid")." where email=".$connect->qstr($line[2])." and firstname = ".$connect->qstr($line[0])." and lastname= ".$connect->qstr($line[1])."";
						$dupresult = $connect->Execute($dupquery);
						if ( $dupresult->RecordCount() > 0)
						{
							$dupfound = true;
						}
					}	
				
				
					$line[2] = trim($line[2]);
				
					//treat blank emails
					if ($filterblankemail && $line[2]=='')
					{
						$invalidemail=true;
					} 
					if  ($line[2]!='' && !validate_email($line[2])) 
					{
						$invalidemail=true;;
					} 
					
					if ($invalidemail)
					{
					  ++$invalidemailcount; 
					}
					elseif ($dupfound)
					{
						++$xy;
					}
					else 
					{
						if (!isset($line[3]) || $line[3]=='') $line[3] = "OK";
						if (!isset($line[4]) || $line[4] == "") $line[4] = "";
						if (!isset($line[5]) || $line[5] == "") $line[5] = GetBaseLanguageFromSurveyID($surveyid);
						if (!isset($line[6])) $line[6] = "";
						if (!isset($line[7])) $line[7] = "";
						$iq = "INSERT INTO ".db_table_name("tokens_$surveyid")." \n"
						. "(firstname, lastname, email, emailstatus, token, language, attribute_1, attribute_2, sent, completed) \n"
						. "VALUES (";
						$iq .= $connect->qstr($line[0]).", ".$connect->qstr($line[1]).", ".$connect->qstr($line[2]).", ".$connect->qstr($line[3]).", ".strtolower($connect->qstr($line[4]))." , ".$connect->qstr($line[5]).", ".$connect->qstr($line[6]).", ".$connect->qstr($line[7]).", ".$connect->qstr('N').", ".$connect->qstr('N');
						$iq .= ")";
						$ir = $connect->Execute($iq);
						if (!$ir) $xy++;
						$xz++;
					}
					$xv++;
				}
			}
			$xx++;
		}
		$xx = $xx-1;
		if ($xz != 0)
		{
			$tokenoutput .= "<font class='successtitle'>".$clang->gT("Success")."</font><br /><br />\n";
		} else {
			$tokenoutput .= "<font color='red'>".$clang->gT("Failed")."</font><br /><br />\n";
		}
		$message = "$xx ".$clang->gT("Records in CSV").".<br />\n";
		$message .= "$xv ".$clang->gT("Records met minumum requirements").".<br />\n";
		$message .= "$xz ".$clang->gT("Records imported").".<br />\n";
		$message .= "$xy ".$clang->gT("Duplicate records removed").".<br />\n";
		$message .= "$invalidemailcount ".$clang->gT("Records with invalid email address removed").".<br />\n";
		$tokenoutput .= "<i>$message</i><br />\n";
		unlink($the_full_file_path);
	}
	$tokenoutput .= "\t\t\t</td></tr></table>\n";
}

if ($subaction == "uploadldap" && 
	($sumrows5['edit_survey_property'] || 
		$sumrows5['activate_survey'] ||
		$_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
   )
{
	$tokenoutput .= "\t<tr><td colspan='2' height='4'><strong>"
	.$clang->gT("Uploading LDAP Query")."</strong></td></tr>\n"
	."\t<tr><td align='center'>\n";
	$ldapq=$_POST['ldapQueries']; // the ldap query id

	$ldap_server_id=$ldap_queries[$ldapq]['ldapServerId'];
	$ldapserver=$ldap_server[$ldap_server_id]['server'];
	$ldapport=$ldap_server[$ldap_server_id]['port'];

	// define $attrlist: list of attributes to read from users' entries
	$attrparams = array('firstname_attr','lastname_attr',
	'email_attr','token_attr', 'language',
	'attr1', 'attr2');
	foreach ($attrparams as $id => $attr) {
		if (array_key_exists($attr,$ldap_queries[$ldapq]) &&
		$ldap_queries[$ldapq][$attr] != '') {
			$attrlist[]=$ldap_queries[$ldapq][$attr];
		}
	}

	// Open connection to server
	$ds = ldap_getCnx($ldap_server_id);

	if ($ds) {
		// bind to server
		$resbind=ldap_bindCnx($ds, $ldap_server_id);

		if ($resbind) {
			$ResArray=array();
			$resultnum=ldap_doTokenSearch($ds, $ldapq, $ResArray);
			$xz = 0; // imported token count
			$xv = 0; // meet minim requirement count
			$xy = 0; // duplicate tokens skipped count
			$invalidemailcount = 0;

			if ($resultnum >= 1) {
				foreach ($ResArray as $responseGroupId => $responseGroup) {
					for($j = 0;$j < $responseGroup['count']; $j++) {
						// first let's initialize everything to ''
						$myfirstname='';
						$mylastname='';
						$myemail='';
						$mylanguage='';
						$mytoken='';
						$myattr1='';
						$myattr2='';

						// The first 3 attrs MUST exist in the ldap answer
						// ==> send PHP notice msg to apache logs otherwise
						$meetminirequirements=true;
						if (isset($responseGroup[$j][$ldap_queries[$ldapq]['firstname_attr']]) &&
							isset($responseGroup[$j][$ldap_queries[$ldapq]['lastname_attr']])
						)
						{
							// minimum requirement for ldap
							// * at least a firstanme
							// * at least a lastname
							// * if filterblankemail is set (default): at least an email address
							$myfirstname = ldap_readattr($responseGroup[$j][$ldap_queries[$ldapq]['firstname_attr']]);
							$mylastname = ldap_readattr($responseGroup[$j][$ldap_queries[$ldapq]['lastname_attr']]);
							if (isset($responseGroup[$j][$ldap_queries[$ldapq]['email_attr']]))
							{
								$myemail = ldap_readattr($responseGroup[$j][$ldap_queries[$ldapq]['email_attr']]);
								$myemail= sanitize_email($myemail);
								++$xv;
							}
							elseif ($filterblankemail !==true)
							{
								$myemail = '';
								++$xv;
							}
							else
							{
								$meetminirequirements=false;
							}
						}
						else
						{
							$meetminirequirements=false;
						}

						// The following attrs are optionnal
						if ( isset($responseGroup[$j][$ldap_queries[$ldapq]['token_attr']]) ) $mytoken = ldap_readattr($responseGroup[$j][$ldap_queries[$ldapq]['token_attr']]);
						if ( isset($responseGroup[$j][$ldap_queries[$ldapq]['attr1']]) ) $myattr1 = ldap_readattr($responseGroup[$j][$ldap_queries[$ldapq]['attr1']]);
						if ( isset($responseGroup[$j][$ldap_queries[$ldapq]['attr2']]) ) $myattr2 = ldap_readattr($responseGroup[$j][$ldap_queries[$ldapq]['attr2']]);
						if ( isset($responseGroup[$j][$ldap_queries[$ldapq]['language']]) ) $mylanguage = ldap_readattr($response[$ldap_queries[$ldapq]['language']]);

						// Now check for duplicates or bad formatted email addresses
						$dupfound=false;
						$invalidemail=false;
						if ($filterduplicatetoken)
						{
							$dupquery = "SELECT firstname, lastname from ".db_table_name("tokens_$surveyid")." where email='$myemail' and firstname='$myfirstname' and lastname='$mylastname'";
							$dupresult = $connect->Execute($dupquery);
							if ( $dupresult->RecordCount() > 0)
							{
								$dupfound = true;
							}
						}	
						if ($filterblankemail && $myemail=='')
						{
							$invalidemail=true;
						} 
						if  ($myemail!='' && !validate_email($myemail)) 
						{
							$invalidemail=true;;
						} 
						
						if ($invalidemail)
						{
						  ++$invalidemailcount; 
						}
						elseif ($dupfound)
						{
							++$xy;
						}
						elseif ($meetminirequirements===true)
						{
							// No issue, let's import
							$iq = "INSERT INTO ".db_table_name("tokens_$surveyid")." \n"
							. "(firstname, lastname, email, emailstatus, token, language";
							if (!empty($myattr1)) {$iq .= ", attribute_1";}
							if (!empty($myattr2)) {$iq .= ", attribute_2";}
							$iq .=") \n"
							. "VALUES ('$myfirstname', '$mylastname', '$myemail', 'OK', '$mytoken', '$mylanguage'";
							if (!empty($myattr1)) {$iq .= ", '$myattr1'";}
							if (!empty($myattr2)) {$iq .= ", '$myattr2'";}
							$iq .= ")";
							$ir = $connect->Execute($iq);
							if (!$ir) $xy++;
							$xz++;
							// or safe_die ("Couldn't insert line<br />\n$buffer<br />\n".htmlspecialchars($connect->ErrorMsg())."<pre style='text-align: left'>$iq</pre>\n");
						} 
					} // End for each entry
				} // End foreach responseGroup
			} // End of if resnum >= 1

			if ($xz != 0)
			{
				$tokenoutput .= "<font class='successtitle'>".$clang->gT("Success")."</font><br /><br />\n";
			}
			else
			{
				$tokenoutput .= "<font color='red'>".$clang->gT("Failed")."</font><br /><br />\n";
			}
			$message = "$resultnum ".$clang->gT("Results from LDAP Query").".<br />\n";
			$message .= "$xv ".$clang->gT("Records met minumum requirements").".<br />\n";
			$message .= "$xz ".$clang->gT("Records imported").".<br />\n";
			$message .= "$xy ".$clang->gT("Duplicate records removed").".<br />\n";
			$message .= "$invalidemailcount ".$clang->gT("Records with invalid email address removed").".<br />\n";
			$tokenoutput .= "<i>$message</i><br />\n";
		}
		else {
			$errormessage="<strong><font color='red'>".$clang->gT("Error").":</font> ".$clang->gT("Can't bind to the LDAP directory")."</strong>\n";
			formldap($errormessage);
		}
		@ldap_close($ds);
	}
	else {
		$errormessage="<strong><font color='red'>".$clang->gT("Error").":</font> ".$clang->gT("Can't connect to the LDAP directory")."</strong>\n";
		formldap($errormessage);
	}
}


$tokenoutput .= "\t\t</td>\n"
               ."</tr></table>\n";



function form_csv_upload($error=false)
{
	global $surveyid, $tokenoutput,$scriptname, $clang, $encodingsarray;

	if ($error) {$tokenoutput .= $error . "<br /><br />\n";}
    asort($encodingsarray);               
    $charsetsout='';
    foreach  ($encodingsarray as $charset=>$title)
    {
    $charsetsout.="<option value='$charset' ";
    if ($charset=='auto') {$charsetsout.=" selected ='selected'";}
    $charsetsout.=">$title ($charset)</option>";
    }
	$tokenoutput .= "<form class='token' enctype='multipart/form-data' action='$scriptname?action=tokens' method='post'>\n"
	. "<input type='hidden' name='subaction' value='upload' />\n"
	. "<input type='hidden' name='sid' value='$surveyid' />\n"
	. "<p><label for='the_file'>".$clang->gT("Choose the CSV file to upload:")."</label><input type='file' name='the_file' size='35' /></p>\n"
	. "<p><label for='csvcharset'>".$clang->gT("Character set of the file:")."</label><select name='csvcharset' size='1'>$charsetsout</select></p>\n"
	. "<p><label for='filterblankemail'>".$clang->gT("Filter blank email addresses:")."</label><input type='checkbox' name='filterblankemail' checked='checked' /></p>\n"
	. "<p><label for='filterduplicatetoken'>".$clang->gT("Filter duplicate records:")."</label><input type='checkbox' name='filterduplicatetoken' checked='checked' /></p>\n"
	. "<p><input class='submit' type='submit' value='".$clang->gT("Upload")."' /></p>\n"
	. "</form></table>\n\n";
} # END form

function formldap($error=false)
{
	global $surveyid, $tokenoutput, $ldap_queries, $clang;

	if ($error) {$tokenoutput .= $error . "<br /><br />\n";}

	if (!function_exists('ldap_connect'))
    {
        $tokenoutput .= '<br />';
        $tokenoutput .= $clang->gT('Sorry, but the LDAP module is missing in your PHP configuration.');
        $tokenoutput .= '<br /><br /><br />';
    }
    
    elseif (! isset($ldap_queries) || ! is_array($ldap_queries) || count($ldap_queries) == 0) {
		$tokenoutput .= '<br />';
		$tokenoutput .= $clang->gT('LDAP is disabled or no LDAP query defined.');
		$tokenoutput .= '<br /><br /><br />';
	}
	else {
		$tokenoutput .= '<br />';
		$tokenoutput .= $clang->gT("Select the LDAP query you want to run:");
		$tokenoutput .= '<br />';
		$tokenoutput .= "<form method='post' action='" . $_SERVER['PHP_SELF'] . "?action=tokens' method='post'>";
		$tokenoutput .= "<select name='ldapQueries' style='length=35'><br />";
		foreach ($ldap_queries as $q_number => $q) {
			$tokenoutput .= " <option value=".$q_number.">".$q['name']."</option>";
		}
		$tokenoutput .= "</select><br />";
		$tokenoutput .= "<p><label for='filterblankemail'>".$clang->gT("Filter blank email addresses:")."</label><input type='checkbox' name='filterblankemail' checked='checked' /></p>\n"
		. "<p><label for='filterduplicatetoken'>".$clang->gT("Filter duplicate records:")."</label><input type='checkbox' name='filterduplicatetoken' checked='checked' /></p>\n";
		$tokenoutput .= "<input type='hidden' name='sid' value='$surveyid' />";
		$tokenoutput .= "<input type='hidden' name='subaction' value='uploadldap' />";
		$tokenoutput .= "<input type='submit' name='submit' />";
		$tokenoutput .= '</form></font>';
	}
}

function getLine($file)
{
	$buffer="";
	// iterate over each character in line.
	while (!feof($file))
	{
		// append the character to the buffer.
		$character = fgetc($file);
		$buffer .= $character;
		// check for end of line.
		if (($character == "\n") or ($character == "\r"))
		{
			// checks if the next character is part of the line ending, as in
			// the case of windows '\r\n' files, or not as in the case of
			// mac classic '\r', and unix/os x '\n' files.
			$character = fgetc($file);
			if ($character == "\n")
			{
				// part of line ending, append to buffer.
				$buffer .= $character;
			}
			else
			{
				// not part of line ending, roll back file pointer.
				fseek($file, -1, SEEK_CUR);
			}
			// end of line, so stop reading.
			break;
		}
	}
	// return the line buffer.
	return $buffer;
}


?>
