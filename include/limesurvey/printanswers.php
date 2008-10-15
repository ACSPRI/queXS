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
*/

//Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

require_once(dirname(__FILE__).'/classes/core/startup.php');   
require_once(dirname(__FILE__).'/config-defaults.php');
require_once(dirname(__FILE__).'/common.php');
if(isset($usepdfexport) && $usepdfexport == 1)
{
    require_once(dirname(__FILE__).$pdfexportdir."/extensiontcpdf.php");
}

//DEFAULT SETTINGS FOR TEMPLATES
if (!$publicdir) {$publicdir=".";}
$tpldir="$publicdir/templates";

@session_start();
if (isset($_SESSION['sid'])) {$surveyid=$_SESSION['sid'];}  else die(); 

//Debut session time out
if (!isset($_SESSION['finished']) || !isset($_SESSION['srid']))
// Argh ... a session time out! RUN!
//display "sorry but your session has expired"
{
    require_once($rootdir.'/classes/core/language.php');
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
//Fin session time out

$id=$_SESSION['srid']; //I want to see the answers with this id
$clang = $_SESSION['s_lang'];

//A little bit of debug to see in the noodles plate
/*if ($debug==2)
{
	echo "MonSurveyID $surveyid et ma langue ". $_SESSION['s_lang']. " et SRID = ". $_SESSION['srid'] ."<br />";
	echo "session id".session_id()." \n"."<br />";

	echo //"secanswer ". $_SESSION['secanswer']
	"oldsid ". $_SESSION['oldsid']."<br />"
	."step ". $_SESSION['step']."<br />"
	."scid ". $_SESSION['scid']
	."srid ". $_SESSION['srid']."<br />"
	."datestamp ". $_SESSION['datestamp']."<br />"
	."insertarray ". $_SESSION['insertarray']."<br />"
	."fieldarray ". $_SESSION['fieldarray']."<br />";
	."holdname". $_SESSION['holdname'];

	print " limit ". $limit."<br />"; //afficher les 50 derniéres réponses par ex. (pas nécessaire)
	print " surveyid ".$surveyid."<br />"; //sid
	print " id ".$id."<br />"; //identifiant de la réponses
	print " order ". $order ."<br />"; //ordre de tri (pas nécessaire)              
	print " this survey ". $thissurvey['tablename'];
};   */

//Ensure script is not run directly, avoid path disclosure
if (!isset($rootdir) || isset($_REQUEST['$rootdir'])) {die("browse - Cannot run this script directly");}

//Select public language file
$query = "SELECT language FROM ".db_table_name("surveys")." WHERE sid=$surveyid";
$result = db_execute_assoc($query) or safe_die("Error selecting language: <br />".$query."<br />".$connect->ErrorMsg());  //Checked


// Set language for questions and labels to base language of this survey
$language = GetBaseLanguageFromSurveyID($surveyid);
$thissurvey = getSurveyInfo($surveyid);
//SET THE TEMPLATE DIRECTORY
if (!$thissurvey['templatedir']) {$thistpl=$tpldir."/default";} else {$thistpl=$tpldir."/{$thissurvey['templatedir']}";}
if (!is_dir($thistpl)) {$thistpl=$tpldir."/default";}

if ($thissurvey['printanswers']=='N') die();  //Die quietly if print answers is not permitted






//CHECK IF SURVEY IS ACTIVATED AND EXISTS
$actquery = "SELECT * FROM ".db_table_name('surveys')." as a inner join ".db_table_name('surveys_languagesettings')." as b on (b.surveyls_survey_id=a.sid and b.surveyls_language=a.language) WHERE a.sid=$surveyid";

$actresult = db_execute_assoc($actquery);    //Checked
$actcount = $actresult->RecordCount();
if ($actcount > 0)
{
	while ($actrow = $actresult->FetchRow())
	{
		$surveytable = db_table_name("survey_".$actrow['sid']);
		$surveyname = "{$actrow['surveyls_title']}";
	}
}


//OK. IF WE GOT THIS FAR, THEN THE SURVEY EXISTS AND IT IS ACTIVE, SO LETS GET TO WORK.

require_once($rootdir.'/classes/core/language.php');  // has been secured
if (isset($_SESSION['s_lang']))
{
    $clang = SetSurveyLanguage( $surveyid, $_SESSION['s_lang']);
} else {
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    $clang = SetSurveyLanguage( $surveyid, $baselang);
}
	//SHOW HEADER
    $printoutput = '';
    if(isset($usepdfexport) && $usepdfexport == 1)
    {
        $printoutput .= "<form action='printanswers.php' method='post'>\n<center><input type='submit' value='".$clang->gT("PDF Export")."'id=\"exportbutton\"/><input type='hidden' name='printableexport' /></center></form>";
    }
    if(isset($_POST['printableexport']))
    {
        $pdf = new PDF('P');
        $pdf->SetFont($pdfdefaultfont,'',$pdffontsize);
        $pdf->AddPage(); 
        $pdf->titleintopdf("Survey Name: ".$surveyname,"SurveyID: ".$surveyid);
    }
	$printoutput .= "\t<span class='printouttitle'><strong>".$clang->gT("Survey Name (ID)").":</strong> $surveyname ($surveyid)</span><br />\n";

	//FIRST LETS GET THE NAMES OF THE QUESTIONS AND MATCH THEM TO THE FIELD NAMES FOR THE DATABASE
	$fnquery = "SELECT * FROM ".db_table_name("questions").", ".db_table_name("groups").", ".db_table_name("surveys")."
	WHERE ".db_table_name("questions").".gid=".db_table_name("groups").".gid AND ".db_table_name("groups").".sid=".db_table_name("surveys").".sid
	AND ".db_table_name("questions").".sid='$surveyid' AND
	".db_table_name("questions").".language='{$language}' AND ".db_table_name("groups").".language='{$language}' ORDER BY ".db_table_name("groups").".group_order, ".db_table_name("questions").".title";
	$fnresult = db_execute_assoc($fnquery);  //Checked   
	$fncount = 0;

	$fnrows = array(); //Create an empty array in case fetch_array does not return any rows
	while ($fnrow = $fnresult->FetchRow()) {++$fncount; $fnrows[] = $fnrow; $private = $fnrow['private']; $datestamp=$fnrow['datestamp']; $ipaddr=$fnrow['ipaddr']; $refurl=$fnrow['refurl'];} // Get table output into array

	// Perform a case insensitive natural sort on group name then question title of a multidimensional array
	usort($fnrows, 'CompareGroupThenTitle');

	$fnames[] = array("id", "id", "id");

	if ($private == "N") //add token to top ofl ist is survey is not private
	{
		$fnames[] = array("token", "token", $clang->gT("Token ID"));
	}
	$fnames[] = array("submitdate", "submitdate", $clang->gT("Date Submitted"));
	if ($datestamp == "Y") //add datetime to list if survey is datestamped
	{
		$fnames[] = array("datestamp", "datestamp", $clang->gT("Date Stamp"));
	}
	if ($ipaddr == "Y") //add ipaddr to list if survey should save submitters IP address
	{
		$fnames[] = array("ipaddr", "ipaddr", $clang->gT("IP Address"));
	}
	if ($refurl == "Y") //add refer_URL  to list if survey should save referring URL
	{
		$fnames[] = array("refurl", "refurl", $clang->gT("Referring URL"));
	}
	foreach ($fnrows as $fnrow)
	{
		$field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}";
		$ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}";
		$fquestion = $fnrow['question'];
		if ($fnrow['type'] == "Q" || $fnrow['type'] == "M" ||
		$fnrow['type'] == "A" || $fnrow['type'] == "B" ||
		$fnrow['type'] == "C" || $fnrow['type'] == "E" ||
		$fnrow['type'] == "F" || $fnrow['type'] == "H" ||
		$fnrow['type'] == "J" || $fnrow['type'] == "K" ||     
		$fnrow['type'] == "P" || $fnrow['type'] == "^")
		{
			$fnrquery = "SELECT * FROM ".db_table_name("answers")." WHERE qid={$fnrow['qid']} AND	language='{$language}' ORDER BY sortorder, answer";
			$fnrresult = db_execute_assoc($fnrquery);  //Checked   
			while ($fnrrow = $fnrresult->FetchRow())
			{
				$fnames[] = array("$field{$fnrrow['code']}", "$ftitle ({$fnrrow['code']})", "{$fnrow['question']} ({$fnrrow['answer']})");
				if ($fnrow['type'] == "P") {$fnames[] = array("$field{$fnrrow['code']}"."comment", "$ftitle"."comment", "{$fnrow['question']} (comment)");}
			}
			if ($fnrow['other'] == "Y" and ($fnrow['type']=="!" or $fnrow['type']=="L" or $fnrow['type']=="M" or $fnrow['type']=="P"))
			{
				$fnames[] = array("$field"."other", "$ftitle"."other", "{$fnrow['question']}(other)");
			}
		}
		elseif ($fnrow['type'] == "R")
		{
			$fnrquery = "SELECT * FROM ".db_table_name("answers")." WHERE qid={$fnrow['qid']} AND
			language='{$language}'
			ORDER BY sortorder, answer";
			$fnrresult = $connect->Execute($fnrquery); //Checked   
			$fnrcount = $fnrresult->RecordCount();
			for ($i=1; $i<=$fnrcount; $i++)
			{
				$fnames[] = array("$field$i", "$ftitle ($i)", "{$fnrow['question']} ($i)");
			}
		}
        elseif ($fnrow['type'] == "1") //Multi Scale
        {
            $field = "{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}";
            $ftitle = "Grp{$fnrow['gid']}Qst{$fnrow['title']}";
            $fquestion = $fnrow['question'];

            $aquery="SELECT * "
                ."FROM {$dbprefix}answers "
                ."WHERE qid={$fnrow['qid']} "
                ."AND {$dbprefix}answers.language='".GetBaseLanguageFromSurveyID($surveyid)."' "
                ."ORDER BY sortorder, "
                ."answer";
            $aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Array questions<br />$aquery<br />".$connect->ErrorMsg()); //Checked   
        
            while ($arows = $aresult->FetchRow())
            {
                $fnames[] = array("{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}{$arows['code']}#0", "$ftitle ", "{$fnrow['question']} {$arows['answer']}");                
                $fnames[] = array("{$fnrow['sid']}X{$fnrow['gid']}X{$fnrow['qid']}{$arows['code']}#1", "$ftitle ", "{$fnrow['question']} {$arows['answer']}");
            } //while
        }
        
		elseif ($fnrow['type'] == "O")
		{
			$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}");
			$field2 = $field."comment";
			$ftitle2 = $ftitle."[Comment]";
			$longtitle = "{$fnrow['question']}<br />[Comment]";
			$fnames[] = array("$field2", "$ftitle2", "$longtitle");
		}
		else
		{
			$fnames[] = array("$field", "$ftitle", "{$fnrow['question']}");
			if (($fnrow['type'] == "L" || $fnrow['type'] == "!") && $fnrow['other'] == "Y")
			{
				$fnames[] = array("$field"."other", "$ftitle"."other", "{$fnrow['question']}(other)");
			}
		}
	}

	$nfncount = count($fnames)-1;
	//SHOW INDIVIDUAL RECORD
	$idquery = "SELECT * FROM $surveytable WHERE id=$id";
	$idresult = db_execute_assoc($idquery) or safe_die ("Couldn't get entry<br />\n$idquery<br />\n".$connect->ErrorMsg()); //Checked   
	while ($idrow = $idresult->FetchRow()) {$id=$idrow['id']; $rlangauge=$idrow['startlanguage'];}
	$next=$id+1;
	$last=$id-1;
	$printoutput .= "<table class='printouttable' >\n";
    if(isset($_POST['printableexport']))
    {
        $pdf->intopdf($clang->gT("Question").": ".$clang->gT("Your Answer"));
    }
    $printoutput .= "<tr><th>".$clang->gT("Question")."</th><th>".$clang->gT("Your Answer")."</th></tr>\n";
	$idresult = db_execute_assoc($idquery) or safe_die ("Couldn't get entry<br />$idquery<br />".$connect->ErrorMsg()); //Checked   
	while ($idrow = $idresult->FetchRow())
	{
		$i=0;
		for ($i; $i<$nfncount+1; $i++)
		{
			$printoutput .= "\t<tr>\n"
			."\t\t<td>{$fnames[$i][2]}</td>\n"
			."\t\t<td>"
			.getextendedanswer($fnames[$i][0], $idrow[$fnames[$i][0]])
			."</td>\n"
			."\t</tr>\n";
            
            if(isset($_POST['printableexport']))
            {
                $pdf->intopdf(strip_tags($fnames[$i][2]).": ".strip_tags(getextendedanswer($fnames[$i][0], $idrow[$fnames[$i][0]])));
                $pdf->ln(2);
            }
		}
	}
    $printoutput .= "</table>\n";
    if(isset($_POST['printableexport']))
    {
        $pdf->write_out($clang->gT($surveyname)." ".$surveyid.".pdf");
    }


//tadaaaaaaaaaaa : display the page with the answers of user
sendcacheheaders();
doHeader();
echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
echo templatereplace(file_get_contents("$thistpl/printanswers.pstpl"));
echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
echo "</html>";
?>
