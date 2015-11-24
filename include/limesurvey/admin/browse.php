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
*/

include_once("login_check.php");  //Login Check dies also if the script is started directly

if (!isset($limit)) {$limit=(int)returnglobal('limit');}
if (!isset($surveyid)) {$surveyid=returnglobal('sid');}
if (!isset($id)) {$id=(int)returnglobal('id');}
if (!isset($order)) {$order=returnglobal('order');}
if (!isset($browselang)) {$browselang=returnglobal('browselang');}

//Ensure script is not run directly, avoid path disclosure
if (!isset($dbprefix) || isset($_REQUEST['dbprefix'])) {die("Cannot run this script directly");}

// Some test for survey table
if (!$surveyid && !$subaction) //NO SID OR ACTION PROVIDED
{
    $browseoutput = "\t<div class='messagebox ui-corner-all'><div class='header ui-widget-header'>"
            . $clang->gT("Browse Responses")."</div><div class='warningheader'>"
            .$clang->gT("Error")."\t</div>\n"
            . $clang->gT("You have not selected a survey to browse.")."<br />\n"
            ."<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_self')\" /><br />\n"
            ."</div>";
    return;
}
//CHECK IF SURVEY IS ACTIVATED AND EXISTS
$actquery = "SELECT * FROM ".db_table_name('surveys')." as a inner join ".db_table_name('surveys_languagesettings')." as b on (b.surveyls_survey_id=a.sid and b.surveyls_language=a.language) WHERE a.sid=$surveyid";

$actresult = db_execute_assoc($actquery);
$actcount = $actresult->RecordCount();
if ($actcount > 0)
{
    while ($actrow = $actresult->FetchRow())
    {
        $surveytable = db_table_name("survey_".$actrow['sid']);
        $surveytimingstable = db_table_name("survey_".$actrow['sid']."_timings");
        $tokentable = $dbprefix."tokens_".$actrow['sid'];
        /*
         * DO NEVER EVER PUT VARIABLES AND FUNCTIONS WHICH GIVE BACK DIFFERENT QUOTES
         * IN DOUBLE QUOTED(' and " and \" is used) JAVASCRIPT/HTML CODE!!! (except for: you know what you are doing)
         *
         * Used for deleting a record, fix quote bugs..
         */
        $surveytableNq = db_table_name_nq("survey_".$surveyid);

        $surveyname = "{$actrow['surveyls_title']}";
        if ($actrow['active'] == "N") //SURVEY IS NOT ACTIVE YET
        {
            $browseoutput = "\t<div class='messagebox ui-corner-all'><div class='header ui-widget-header'>"
                    . $clang->gT("Browse Responses")."</div><div class='warningheader'>"
                    .$clang->gT("Error")."\t</div>\n"
                    . $clang->gT("This survey has not been activated. There are no results to browse.")."<br />\n"
                    ."<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname?sid=$surveyid', '_self')\" /><br />\n"
                    ."</div>";
            return;
        }
    }
}
else //SURVEY MATCHING $surveyid DOESN'T EXIST
{
    $browseoutput = "\t<div class='messagebox ui-corner-all'><div class='header ui-widget-header'>"
            . $clang->gT("Browse Responses")."</div><div class='warningheader'>"
            .$clang->gT("Error")."\t</div>\n"
            . $clang->gT("There is no matching survey.")."<br />\n"
            ."<input type='submit' value='".$clang->gT("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_self')\" /><br />\n"
            ."</div>";
    return;
}

//OK. IF WE GOT THIS FAR, THEN THE SURVEY EXISTS AND IT IS ACTIVE, SO LETS GET TO WORK.

$surveyinfo=getSurveyInfo($surveyid);
include_once("../quexs.php");
require_once(dirname(__FILE__).'/sessioncontrol.php');

// Set language for questions and labels to base language of this survey

if (isset($browselang) && $browselang!='')
{
    $_SESSION['browselang']=$browselang;
    $language=$_SESSION['browselang'];
}
elseif (isset($_SESSION['browselang']))
{
    $language=$_SESSION['browselang'];
    $languagelist = GetAdditionalLanguagesFromSurveyID($surveyid);
    $languagelist[]=GetBaseLanguageFromSurveyID($surveyid);
    if (!in_array($language,$languagelist))
    {
        $language = GetBaseLanguageFromSurveyID($surveyid);
    }
}
else
{
    $language = GetBaseLanguageFromSurveyID($surveyid);
}

$surveyoptions = browsemenubar($clang->gT("Browse Responses"));
$browseoutput = "";

$js_admin_includes[]='scripts/browse.js';

$qulanguage = GetBaseLanguageFromSurveyID($surveyid);


// Looking at a SINGLE entry

if ($subaction == "id")
{
    $dateformatdetails=getDateFormatData($_SESSION['dateformat']);

    //SHOW HEADER
    if (!isset($_SESSION['sql']) || !$_SESSION['sql']) {$browseoutput .= $surveyoptions;} // Don't show options if coming from tokens/statistics script
    //FIRST LETS GET THE NAMES OF THE QUESTIONS AND MATCH THEM TO THE FIELD NAMES FOR THE DATABASE

    $fncount = 0;


    $fieldmap=createFieldMap($surveyid,'full',false,false,$language);

    //add token to top of list if survey is not private
    if ($surveyinfo['anonymized'] == "N" && tableExists('tokens_'.$surveyid))
    {
        $fnames[] = array("token", "Token", $clang->gT("Token ID"), 0);
        $fnames[] = array("firstname", "First name", $clang->gT("First name"), 0);
        $fnames[] = array("lastname", "Last name", $clang->gT("Last name"), 0);
        $fnames[] = array("email", "Email", $clang->gT("Email"), 0);
    }
    $fnames[] = array("submitdate", $clang->gT("Submission date"), $clang->gT("Completed"), "0", 'D');
    $fnames[] = array("completed", $clang->gT("Completed"), "0");

    foreach ($fieldmap as $field)
    {
        if ($field['fieldname']=='lastpage' || $field['fieldname'] == 'submitdate')
            continue;
        if ($field['type']=='interview_time')
            continue;
        if ($field['type']=='page_time')
            continue;
        if ($field['type']=='answer_time')
            continue;

        $question=$field['question'];
        if ($field['type'] != "|")
        {
            if (isset($field['subquestion']) && $field['subquestion']!='')
                $question .=' ('.$field['subquestion'].')';
            if (isset($field['subquestion1']) && isset($field['subquestion2']))
                $question .=' ('.$field['subquestion1'].':'.$field['subquestion2'].')';
            if (isset($field['scale_id']))
                $question .='['.$field['scale'].']';
            $fnames[]=array($field['fieldname'],$question);
        }
        else
        {
            if ($field['aid']!=='filecount')
            {
                $qidattributes=getQuestionAttributes($field['qid']);

                for ($i = 0; $i < $field['max_files']; $i++)
                {
                    if ($qidattributes['show_title'] == 1)
                        $fnames[] = array($field['fieldname'], "File ".($i+1)." - ".$field['question']." (Title)",     "type"=>"|", "metadata"=>"title",   "index"=>$i);

                    if ($qidattributes['show_comment'] == 1)
                        $fnames[] = array($field['fieldname'], "File ".($i+1)." - ".$field['question']." (Comment)",   "type"=>"|", "metadata"=>"comment", "index"=>$i);

                    $fnames[] = array($field['fieldname'], "File ".($i+1)." - ".$field['question']." (File name)", "type"=>"|", "metadata"=>"name",    "index"=>$i);
                    $fnames[] = array($field['fieldname'], "File ".($i+1)." - ".$field['question']." (File size)", "type"=>"|", "metadata"=>"size",    "index"=>$i);
                    //$fnames[] = array($field['fieldname'], "File ".($i+1)." - ".$field['question']." (extension)", "type"=>"|", "metadata"=>"ext",     "index"=>$i);
                }
            }
            else
                $fnames[] = array($field['fieldname'], "File count");
        }
}

    $nfncount = count($fnames)-1;
    //SHOW INDIVIDUAL RECORD
    $idquery = "SELECT * FROM {$surveytable} s";
    if ($surveyinfo['anonymized'] == "N" && db_tables_exist($tokentable))
        $idquery .= " LEFT JOIN {$tokentable} t ON s.token = t.token ";


    //queXS Addition
    $qfs = questionnaireSampleFilterstate();
    if ($qfs != false)
    {
        //Limit responses by questionnaire and/or sample
        $query .= "     JOIN `case` AS c ON (s.token = c.token AND c.questionnaire_id = '{$qfs[0]}') ";
        if ($qfs[1] != 0) //if a sample is selected
                $query .= "     JOIN `sample` AS ss ON (ss.sample_id = c.sample_id AND ss.import_id = '{$qfs[1]}') ";
    }


    if (incompleteAnsFilterstate() == "inc")
        $idquery .= " WHERE (submitdate = ".$connect->DBDate('1980-01-01'). " OR submitdate IS NULL) AND ";
    elseif (incompleteAnsFilterstate() == "filter")
        $idquery .= " WHERE submitdate >= ".$connect->DBDate('1980-01-01'). " AND ";
    else
        $idquery .= " WHERE ";
    if ($id < 1) { $id = 1; }
    $idquery .= " s.id = $id";
    $idresult = db_execute_assoc($idquery) or safe_die ("Couldn't get entry<br />\n$idquery<br />\n".$connect->ErrorMsg());
    while ($idrow = $idresult->FetchRow())
    {
        $id=$idrow['id'];
        $rlanguage=$idrow['startlanguage'];
    }
    $next=$id+1;
    $last=$id-1;
    $browseoutput .= "<div class='menubar'>\n"
            ."<div class='menubar-title ui-widget-header'>".sprintf($clang->gT("View response ID %d"),$id)."</div>"
            ."\t<div class='menubar-main'>\n"
            ."<img src='$imageurl/blank.gif' width='31' height='20' border='0' hspace='0' align='left' alt='' />\n"
            ."<img src='$imageurl/seperator.gif' border='0' hspace='0' align='left' alt='' />\n";
    if (isset($rlanguage))
    {
        $browseoutput .="<a href='$scriptname?action=dataentry&amp;subaction=edit&amp;id=$id&amp;sid=$surveyid&amp;language=$rlanguage' "
                ."title='".$clang->gTview("Edit this entry")."'>"
                ."<img align='left' src='$imageurl/edit.png' alt='".$clang->gT("Edit this entry")."' /></a>\n";
    }
    if (bHasSurveyPermission($surveyid,'responses','delete') && isset($rlanguage))
    {

        $browseoutput .= "<a href='#' title='".$clang->gTview("Delete this entry")."' onclick=\"if (confirm('".$clang->gT("Are you sure you want to delete this entry?","js")."')) {".get2post($scriptname.'?action=dataentry&amp;subaction=delete&amp;id='.$id.'&amp;sid='.$surveyid)."}\" >"
                ."<img align='left' hspace='0' border='0' src='$imageurl/delete.png' alt='".$clang->gT("Delete this entry")."' /></a>\n";
    }
    else
    {
        $browseoutput .=  "<img align='left' hspace='0' border='0' src='$imageurl/delete_disabled.png' alt='".$clang->gT("You don't have permission to delete this entry.")."'/>";
    }

    if (bHasFileUploadQuestion($surveyid))
    {
        $browseoutput .= "<a href='#' title='".$clang->gTview("Download files for this entry")."' onclick=\" ".get2post($scriptname.'?action=browse&amp;subaction=all&amp;downloadfile='.$id.'&amp;sid='.$surveyid)."\" >"
                ."<img align='left' hspace='0' border='0' src='$imageurl/download.png' alt='".$clang->gT("Download files for this entry")."' /></a>\n";
    }

    //Export this response
    $browseoutput .= "<a href='$scriptname?action=exportresults&amp;sid=$surveyid&amp;id=$id'" .
            "title='".$clang->gTview("Export this Response")."' >" .
            "<img name='ExportAnswer' src='$imageurl/export.png' alt='". $clang->gT("Export this Response")."' align='left' /></a>\n"
            ."<img src='$imageurl/seperator.gif' border='0' hspace='0' align='left' alt='' />\n"
            ."<img src='$imageurl/blank.gif' width='20' height='20' border='0' hspace='0' align='left' alt='' />\n"
            ."<a href='$scriptname?action=browse&amp;subaction=id&amp;id=$last&amp;sid=$surveyid' "
            ."title='".$clang->gTview("Show previous...")."' >"
            ."<img name='DataBack' align='left' src='$imageurl/databack.png' alt='".$clang->gT("Show previous...")."' /></a>\n"
            ."<img src='$imageurl/blank.gif' width='13' height='20' border='0' hspace='0' align='left' alt='' />\n"
            ."<a href='$scriptname?action=browse&amp;subaction=id&amp;id=$next&amp;sid=$surveyid' title='".$clang->gTview("Show next...")."'>"
            ."<img name='DataForward' align='left' src='$imageurl/dataforward.png' alt='".$clang->gT("Show next...")."' /></a>\n"
            ."</div>\n"
            ."\t</div>\n";

    $browseoutput .= "<table class='detailbrowsetable' width='99%'>\n";
    $idresult = db_execute_assoc($idquery) or safe_die ("Couldn't get entry<br />$idquery<br />".$connect->ErrorMsg());
    while ($idrow = $idresult->FetchRow())
    {
        $highlight=false;
        for ($i = 0; $i < $nfncount+1; $i++)
        {
            if ($fnames[$i][0] != 'completed' && is_null($idrow[$fnames[$i][0]]))
            {
                continue;   // irrelevant, so don't show
            }
            $inserthighlight='';
            if ($highlight)
                $inserthighlight="class='highlight'";
            $browseoutput .= "\t<tr $inserthighlight>\n"
                    ."<th align='right' width='50%'>"
                    .strip_tags(strip_javascript($fnames[$i][1]))."</th>\n"
                    ."<td align='left' >";
            if ($fnames[$i][0] == 'completed')
            {
                if ($idrow['submitdate'] == NULL || $idrow['submitdate'] == "N") { $browseoutput .= "N"; }
                else { $browseoutput .= "Y"; }
            }
            else
            {
                if (isset($fnames[$i]['type']) && $fnames[$i]['type'] == "|")
                {
                    $index = $fnames[$i]['index'];
                    $metadata = $fnames[$i]['metadata'];
                    $phparray = json_decode($idrow[$fnames[$i][0]], true);
                    if (isset($phparray[$index]))
                    {
                        if ($metadata === "size")
                            $browseoutput .= rawurldecode(((int)($phparray[$index][$metadata]))." KB");
                        else if ($metadata === "name")
                            $browseoutput .= "<a href='#' onclick=\" ".get2post($scriptname.'?action=browse&amp;subaction=all&amp;downloadindividualfile=' . $phparray[$index][$metadata] . '&amp;fieldname='.$fnames[$i][0].'&amp;id='.$id.'&amp;sid='.$surveyid)."\" >".rawurldecode($phparray[$index][$metadata])."</a>";
                        else
                            $browseoutput .= rawurldecode($phparray[$index][$metadata]);
                    }
                    else
                        $browseoutput .= "";
                }
                else
                    $browseoutput .= htmlspecialchars(strip_tags(strip_javascript(getextendedanswer($fnames[$i][0], $idrow[$fnames[$i][0]], '', $dateformatdetails['phpdate']))), ENT_QUOTES);
            }
            $browseoutput .= "</td>\n\t</tr>\n";
            $highlight=!$highlight;
        }
    }
    $browseoutput .= "</table>\n";

}

elseif ($subaction == "all")
{
    /**
     * fnames is used as informational array
     * it containts
     *             $fnames[] = array(<dbfieldname>, <some strange title>, <questiontext>, <group_id>, <questiontype>);
     */

    $browseoutput .= "\n<script type='text/javascript'>
                          var strdeleteconfirm='".$clang->gT('Do you really want to delete this response?','js')."';
                          var strDeleteAllConfirm='".$clang->gT('Do you really want to delete all marked responses?','js')."';
                          var noFilesSelectedForDeletion = '".$clang->gT('Please select at least one file for deletion','js')."';
                          var noFilesSelectedForDnld = '".$clang->gT('Please select at least one file for download','js')."';
                        </script>\n";
    $browseoutput .= $surveyoptions;
    $bClearFilter=returnglobal('clearfilter');
    if ($bClearFilter)
    {
        unset($_SESSION['sql']);
    }
    if (isset($_SESSION['sql']))
    {
        $browseoutput .= "<form action='$scriptname?clearfilter=1' method='post'>
        <p>".$clang->gT("Note:").'&nbsp;'.$clang->gT("Showing Filtered Results")." "
        ."&nbsp;<input type='submit' value='".$clang->gT("Remove filter")."'>
        <input type='hidden' name='sid' value='$surveyid' />
        <input type='hidden' name='action' value='browse' />
        <input type='hidden' name='subaction' value='all' />
        </p></form>\n";
    }

    //Delete Individual answer using inrow delete buttons/links - checked
    if (isset($_POST['deleteanswer']) && $_POST['deleteanswer'] != '' && $_POST['deleteanswer'] != 'marked' && bHasSurveyPermission($surveyid,'responses','delete'))
    {
        $_POST['deleteanswer']=(int) $_POST['deleteanswer']; // sanitize the value

        // delete the files as well if its a fuqt

        $fieldmap = createFieldMap($surveyid);
        $fuqtquestions = array();
        // find all fuqt questions
        foreach ($fieldmap as $field)
        {
            if ($field['type'] == "|" && strpos($field['fieldname'], "_filecount") == 0)
                $fuqtquestions[] = $field['fieldname'];
        }
        if (!empty($fuqtquestions))
        {
            // find all responses (filenames) to the fuqt questions
            $query="SELECT " . implode(", ", $fuqtquestions) . " FROM $surveytable where id={$_POST['deleteanswer']}";
            $responses = db_execute_assoc($query) or safe_die("Could not fetch responses<br />$query<br />".$connect->ErrorMsg());

            while($json = $responses->FetchRow())
            {
                foreach ($fuqtquestions as $fieldname)
                {
                    $phparray = json_decode($json[$fieldname]);
                    foreach($phparray as $metadata)
                    {
                        $path = "{$uploaddir}/surveys/{$surveyid}/files/";
                        unlink($path.$metadata->filename); // delete the file
                    }
                }
            }
        }

        // delete the row
        $query="delete FROM $surveytable where id={$_POST['deleteanswer']}";
        $connect->execute($query) or safe_die("Could not delete response<br />$dtquery<br />".$connect->ErrorMsg()); // checked
    }
    // Marked responses -> deal with the whole batch of marked responses
    if (isset($_POST['markedresponses']) && count($_POST['markedresponses'])>0 && bHasSurveyPermission($surveyid,'responses','delete'))
    {
        // Delete the marked responses - checked
        if (isset($_POST['deleteanswer']) && $_POST['deleteanswer'] === 'marked')
        {
            $fieldmap = createFieldMap($surveyid);
            $fuqtquestions = array();
            // find all fuqt questions
            foreach ($fieldmap as $field)
            {
                if ($field['type'] == "|" && strpos($field['fieldname'], "_filecount") == 0)
                    $fuqtquestions[] = $field['fieldname'];
            }

            foreach ($_POST['markedresponses'] as $iResponseID)
            {
                $iResponseID = (int)$iResponseID; // sanitize the value

                if (!empty($fuqtquestions))
                {
                    // find all responses (filenames) to the fuqt questions
                    $query="SELECT " . implode(", ", $fuqtquestions) . " FROM $surveytable where id={$iResponseID}";
                    $responses = db_execute_assoc($query) or safe_die("Could not fetch responses<br />$query<br />".$connect->ErrorMsg());

                    while($json = $responses->FetchRow())
                    {
                        foreach ($fuqtquestions as $fieldname)
                        {
                            $phparray = json_decode($json[$fieldname]);
                            foreach($phparray as $metadata)
                            {
                                $path = "{$uploaddir}/surveys/{$surveyid}/files/";
                                unlink($path.$metadata->filename); // delete the file
                            }
                        }
                    }
                }

                $query="delete FROM {$surveytable} where id={$iResponseID}";
                $connect->execute($query) or safe_die("Could not delete response<br />{$dtquery}<br />".$connect->ErrorMsg());  // checked
            }
        }
        // Download all files for all marked responses  - checked
        else if (isset($_POST['downloadfile']) && $_POST['downloadfile'] === 'marked')
        {
            // Now, zip all the files in the filelist
            $zipfilename = "Responses_for_survey_" . $surveyid . ".zip";
            zipFiles($_POST['markedresponses'], $zipfilename);
        }
    }
    // Download all files for this entry - checked
    else if (isset($_POST['downloadfile']) && $_POST['downloadfile'] != '' && $_POST['downloadfile'] !== true)
    {
        // Now, zip all the files in the filelist
        $zipfilename = "LS_Responses_for_" . $_POST['downloadfile'] . ".zip";
        zipFiles($_POST['downloadfile'], $zipfilename);
    }
    else if (isset($_POST['downloadindividualfile']) && $_POST['downloadindividualfile'] != '')
    {
        $id = (int)$_POST['id'];
        $downloadindividualfile = $_POST['downloadindividualfile'];
        $fieldname = $_POST['fieldname'];

        $query = "SELECT ".db_quote_id($fieldname)." FROM {$surveytable} WHERE id={$id}";
        $result=db_execute_num($query);
        $row=$result->FetchRow();
        $phparray = json_decode($row[0]);

        for ($i = 0; $i < count($phparray); $i++)
        {
            if ($phparray[$i]->name == $downloadindividualfile)
            {
                $file = $uploaddir. "/surveys/" . $surveyid . "/files/" . $phparray[$i]->filename;
                if (file_exists($file)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . rawurldecode($phparray[$i]->name) . '"');
                    header('Content-Transfer-Encoding: binary');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file));
                    ob_clean();
                    flush();
                    readfile($file);
                    exit;
                }
                break;
            }
        }
    }


    //add token to top of list if survey is not private
    if ($surveyinfo['anonymized'] == "N" && db_tables_exist($tokentable)) //add token to top of list if survey is not private
    {
        $fnames[] = array("token", "Token", $clang->gT("Token ID"), 0);
        $fnames[] = array("firstname", "First name", $clang->gT("First name"), 0);
        $fnames[] = array("lastname", "Last name", $clang->gT("Last name"), 0);
        $fnames[] = array("email", "Email", $clang->gT("Email"), 0);
    }

    $fnames[] = array("submitdate", $clang->gT("Completed"), $clang->gT("Completed"), "0", 'D');
    $fields = createFieldMap($surveyid, 'full', false, false, $language);

    foreach ($fields as $fielddetails)
    {
        if ($fielddetails['fieldname']=='lastpage' || $fielddetails['fieldname'] == 'submitdate')
            continue;

        $question=$fielddetails['question'];
        if ($fielddetails['type'] != "|")
        {
            if ($fielddetails['fieldname']=='lastpage' || $fielddetails['fieldname'] == 'submitdate' || $fielddetails['fieldname'] == 'token')
                continue;

            // no headers for time data
            if ($fielddetails['type']=='interview_time')
			    continue;
            if ($fielddetails['type']=='page_time')
			    continue;
		    if ($fielddetails['type']=='answer_time')
			    continue;
            if (isset($fielddetails['subquestion']) && $fielddetails['subquestion']!='')
                $question .=' ('.$fielddetails['subquestion'].')';
            if (isset($fielddetails['subquestion1']) && isset($fielddetails['subquestion2']))
                $question .=' ('.$fielddetails['subquestion1'].':'.$fielddetails['subquestion2'].')';
            if (isset($fielddetails['scale_id']))
            $question .='['.$fielddetails['scale'].']';
            $fnames[]=array($fielddetails['fieldname'],$question);
        }
        else
        {
            if ($fielddetails['aid']!=='filecount')
            {
                $qidattributes=getQuestionAttributes($fielddetails['qid']);

                for ($i = 0; $i < $fielddetails['max_files']; $i++)
                {
                    if ($qidattributes['show_title'] == 1)
                        $fnames[] = array($fielddetails['fieldname'], "File ".($i+1)." - ".$fielddetails['question']."(Title)",     "type"=>"|", "metadata"=>"title",   "index"=>$i);

                    if ($qidattributes['show_comment'] == 1)
                        $fnames[] = array($fielddetails['fieldname'], "File ".($i+1)." - ".$fielddetails['question']."(Comment)",   "type"=>"|", "metadata"=>"comment", "index"=>$i);

                    $fnames[] = array($fielddetails['fieldname'], "File ".($i+1)." - ".$fielddetails['question']."(File name)", "type"=>"|", "metadata"=>"name",    "index"=>$i);
                    $fnames[] = array($fielddetails['fieldname'], "File ".($i+1)." - ".$fielddetails['question']."(File size)", "type"=>"|", "metadata"=>"size",    "index"=>$i);
                    //$fnames[] = array($fielddetails['fieldname'], "File ".($i+1)." - ".$fielddetails['question']."(extension)", "type"=>"|", "metadata"=>"ext",     "index"=>$i);
                }
            }
            else
                $fnames[] = array($fielddetails['fieldname'], "File count");
        }
    }

    $fncount = count($fnames);

    //NOW LETS CREATE A TABLE WITH THOSE HEADINGS

    $tableheader = "<!-- DATA TABLE -->";
    if ($fncount < 10) {$tableheader .= "<table class='browsetable' width='100%'>\n";}
    else {$tableheader .= "<table class='browsetable'>\n";}
    $tableheader .= "\t<thead><tr valign='top'>\n"
            . "<th><input type='checkbox' id='selectall'></th>\n"
            . "<th>".$clang->gT('Actions')."</th>\n";
    foreach ($fnames as $fn)
    {
        if (!isset($currentgroup))  {$currentgroup = $fn[1]; $gbc = "odd";}
        if ($currentgroup != $fn[1])
        {
            $currentgroup = $fn[1];
            if ($gbc == "odd") {$gbc = "even";}
            else {$gbc = "odd";}
            }
        $tableheader .= "<th class='$gbc'><strong>"
                . FlattenText("$fn[1]")
                . "</strong></th>\n";
    }
    $tableheader .= "\t</tr></thead>\n\n";
    $tableheader .= "\t<tfoot><tr><td colspan=".($fncount+2).">";
    if (bHasSurveyPermission($surveyid,'responses','delete'))
    {
        $tableheader .= "<img id='imgDeleteMarkedResponses' src='{$imageurl}/token_delete.png' alt='".$clang->gT('Delete marked responses')."' />";
    }
    if (bHasFileUploadQuestion($surveyid))
        $tableheader .="<img id='imgDownloadMarkedFiles' src='{$imageurl}/down_all.png' alt='".$clang->gT('Download marked files')."' />";

    $tableheader .="</td></tr></tfoot>\n\n";

    $start=returnglobal('start');
    $limit=returnglobal('limit');
    if (!isset($limit) || $limit== '') {$limit = 50;}
    if (!isset($start) || $start =='') {$start = 0;}

    //Create the query
    if ($surveyinfo['anonymized'] == "N" && db_tables_exist($tokentable))
    {
        $sql_from = "{$surveytable} LEFT JOIN {$tokentable} ON {$surveytable}.token = {$tokentable}.token";
    } else {
        $sql_from = $surveytable;
    }

    $qfs = questionnaireSampleFilterstate();
    if ($qfs != false)
    {
        //Limit responses by questionnaire and/or sample
        $sql_from .= "     JOIN `case` AS c ON ({$surveytable}.token = c.token AND c.questionnaire_id = '{$qfs[0]}') ";
        if ($qfs[1] != 0) //if a sample is selected
                $sql_from .= "     JOIN `sample` AS s ON (s.sample_id = c.sample_id AND s.import_id = '{$qfs[1]}') ";
    }



    $selectedgroup = returnglobal('selectgroup'); // group token id

    $sql_where = "";
    if (incompleteAnsFilterstate() == "inc")
    {
        $sql_where .= "submitdate IS NULL";

    }
    elseif (incompleteAnsFilterstate() == "filter")
    {
        $sql_where .= "submitdate IS NOT NULL";

    }

    //LETS COUNT THE DATA
    //$dtquery = "SELECT count(*) FROM $sql_from $sql_where";
    $dtquery = "SELECT count(*) FROM $sql_from";
    if ($sql_where!="")
    {
        $dtquery .=" WHERE $sql_where";
    }
    $dtresult=db_execute_num($dtquery) or safe_die("Couldn't get response data<br />$dtquery<br />".$connect->ErrorMsg());
    while ($dtrow=$dtresult->FetchRow()) {$dtcount=$dtrow[0];}

    if ($limit > $dtcount) {$limit=$dtcount;}

    //NOW LETS SHOW THE DATA
    if (isset($_SESSION['sql']))
    {
        if ($_SESSION['sql'] == "NULL" )
        {
            if ($surveyinfo['anonymized'] == "N" && db_tables_exist($tokentable))
                $dtquery = "SELECT * FROM $surveytable LEFT JOIN $tokentable ON $surveytable.token = $tokentable.token ";
            else
                $dtquery = "SELECT * FROM $surveytable ";

	    $qfs = questionnaireSampleFilterstate();
	    if ($qfs != false)
	    {
	        //Limit responses by questionnaire and/or sample
	        $dtquery .= "     JOIN `case` AS c ON ({$surveytable}.token = c.token AND c.questionnaire_id = '{$qfs[0]}') ";
	        if ($qfs[1] != 0) //if a sample is selected
	                $dtquery .= "     JOIN `sample` AS s ON (s.sample_id = c.sample_id AND s.import_id = '{$qfs[1]}') ";
	    }

            // group token id
            $selectedgroup = returnglobal('selectgroup');
            if (incompleteAnsFilterstate() == "inc")
            {
                $dtquery .= "WHERE submitdate IS NULL ";
            }
            elseif (incompleteAnsFilterstate() == "filter")
            {
                $dtquery .= " WHERE submitdate IS NOT NULL ";
            }

            $dtquery .= " ORDER BY {$surveytable}.id";
        }
        else
        {

            if ($surveytable['anonymized'] == "N" && db_tables_exist($tokentable))
                $dtquery = "SELECT * FROM $surveytable LEFT JOIN $tokentable ON $surveytable.token = $tokentable.token WHERE 1=1 ";
            else
                $dtquery = "SELECT * FROM $surveytable WHERE 1=1 ";

	    $qfs = questionnaireSampleFilterstate();
	    if ($qfs != false)
	    {
	        //Limit responses by questionnaire and/or sample
	        $dtquery .= "     JOIN `case` AS c ON ({$surveytable}.token = c.token AND c.questionnaire_id = '{$qfs[0]}') ";
	        if ($qfs[1] != 0) //if a sample is selected
	                $dtquery .= "     JOIN `sample` AS s ON (s.sample_id = c.sample_id AND s.import_id = '{$qfs[1]}') ";
	    }

            $selectedgroup = returnglobal('selectgroup');
            if (incompleteAnsFilterstate() == "inc")
            {
                $dtquery .= " AND submitdate IS NULL ";

            }
            elseif (incompleteAnsFilterstate() == "filter")
            {
                $dtquery .= " AND submitdate IS NOT NULL ";
            }
            if ($_SESSION['sql'] !== "")
            {
                $dtquery .= ' AND '.$_SESSION['sql']." ";
            }
            $dtquery .= " ORDER BY {$surveytable}.id";
        }
    }
    else
    {
        if ($surveyinfo['anonymized'] == "N" && db_tables_exist($tokentable))
            $dtquery = "SELECT * FROM $surveytable LEFT JOIN $tokentable ON $surveytable.token = $tokentable.token ";
        else
            $dtquery = "SELECT * FROM $surveytable ";

	    $qfs = questionnaireSampleFilterstate();
	    if ($qfs != false)
	    {
	        //Limit responses by questionnaire and/or sample
	        $dtquery .= "     JOIN `case` AS c ON ({$surveytable}.token = c.token AND c.questionnaire_id = '{$qfs[0]}') ";
	        if ($qfs[1] != 0) //if a sample is selected
	                $dtquery .= "     JOIN `sample` AS s ON (s.sample_id = c.sample_id AND s.import_id = '{$qfs[1]}') ";
	    }


        if (incompleteAnsFilterstate() == "inc")
        {
            $dtquery .= " WHERE submitdate IS NULL ";

        }
        elseif (incompleteAnsFilterstate() == "filter")
        {
            $dtquery .= " WHERE submitdate IS NOT NULL ";
        }

        $dtquery .= " ORDER BY {$surveytable}.id";
    }
    if ($order == "desc") {$dtquery .= " DESC";}

    if (isset($limit))
    {
        if (!isset($start)) {$start = 0;}
        $dtresult = db_select_limit_assoc($dtquery, $limit, $start) or safe_die("Couldn't get surveys<br />$dtquery<br />".$connect->ErrorMsg());
    }
    else
    {
        $dtresult = db_execute_assoc($dtquery) or safe_die("Couldn't get surveys<br />$dtquery<br />".$connect->ErrorMsg());
    }
    $dtcount2 = $dtresult->RecordCount();
    $cells = $fncount+1;


    //CONTROL MENUBAR
    $last=$start-$limit;
    $next=$start+$limit;
    $end=$dtcount-$limit;
    if ($end < 0) {$end=0;}
    if ($last <0) {$last=0;}
    if ($next >= $dtcount) {$next=$dtcount-$limit;}
    if ($end < 0) {$end=0;}

    $browseoutput .= "<div class='menubar'>\n"
            . "\t<div class='menubar-title ui-widget-header'>\n"
            . "<strong>".$clang->gT("Data view control")."</strong></div>\n"
            . "\t<div class='menubar-main'>\n";
    if (!isset($_POST['sql']))
    {
        $browseoutput .= "<a href='$scriptname?action=browse&amp;subaction=all&amp;sid=$surveyid&amp;start=0&amp;limit=$limit' "
                ."title='".$clang->gTview("Show start...")."' >"
                ."<img name='DataBegin' align='left' src='$imageurl/databegin.png' alt='".$clang->gT("Show start...")."' /></a>\n"
                ."<a href='$scriptname?action=browse&amp;subaction=all&amp;sid=$surveyid&amp;start=$last&amp;limit=$limit' "
                ."title='".$clang->gTview("Show previous..")."' >"
                ."<img name='DataBack' align='left'  src='$imageurl/databack.png' alt='".$clang->gT("Show previous..")."' /></a>\n"
                ."<img src='$imageurl/blank.gif' width='13' height='20' border='0' hspace='0' align='left' alt='' />\n"

                ."<a href='$scriptname?action=browse&amp;subaction=all&amp;sid=$surveyid&amp;start=$next&amp;limit=$limit' " .
                "title='".$clang->gT("Show next...")."' >".
                "<img name='DataForward' align='left' src='$imageurl/dataforward.png' alt='".$clang->gT("Show next..")."' /></a>\n"
                ."<a href='$scriptname?action=browse&amp;subaction=all&amp;sid=$surveyid&amp;start=$end&amp;limit=$limit' " .
                "title='".$clang->gT("Show last...")."' >" .
                "<img name='DataEnd' align='left' src='$imageurl/dataend.png' alt='".$clang->gT("Show last..")."' /></a>\n"
                ."<img src='$imageurl/seperator.gif' border='0' hspace='0' align='left' alt='' />\n";
    }
    $selectshow='';
    $selectinc='';
    $selecthide='';

    if(incompleteAnsFilterstate() == "inc") { $selectinc="selected='selected'"; }
    elseif (incompleteAnsFilterstate() == "filter") { $selecthide="selected='selected'"; }
    else { $selectshow="selected='selected'"; }

    $browseoutput .="<form action='$scriptname?action=browse' id='browseresults' method='post'><font size='1' face='verdana'>\n"
            ."<img src='$imageurl/blank.gif' width='31' height='20' border='0' hspace='0' align='right' alt='' />\n"
            ."&nbsp;&nbsp; ".$clang->gT("Display:")."<select name='filterinc' onchange='javascript:document.getElementById(\"limit\").value=\"\";submit();'>\n"
            ."\t<option value='show' $selectshow>".$clang->gT("All responses")."</option>\n"
            ."\t<option value='filter' $selecthide>".$clang->gT("Completed responses only")."</option>\n"
            ."\t<option value='incomplete' $selectinc>".$clang->gT("Incomplete responses only")."</option>\n"
            ."</select>\n";



$quexsfilterstate = questionnaireSampleFilterstate();

    //queXS Addition
    $browseoutput .= "&nbsp;".T_("Questionnaire and Sample selection:")."<select id='quexsfilterinc' name='quexsfilterinc' onchange='javascript:document.getElementById(\"limit\").value=\"\";submit();'>\n"
    ."\t<option value='all' >".T_("All queXS questionnaires and samples associated with this instrument")."</option>\n"
    . get_questionnaire_sample_list($surveyid,$quexsfilterstate)
    ."</select>\n";

     $browseoutput .= "".$clang->gT("Records displayed:")."<input type='text' size='4' value='$dtcount2' name='limit' id='limit' />\n"
            ."&nbsp;&nbsp; ".$clang->gT("Starting from:")."<input type='text' size='4' value='$start' name='start' id='start' />\n"
            ."&nbsp;&nbsp; <input type='submit' value='".$clang->gT("Show")."' />\n";
 


     $browseoutput .= "</font>\n"
            ."<input type='hidden' name='sid' value='$surveyid' />\n"
            ."<input type='hidden' name='action' value='browse' />\n"
            ."<input type='hidden' name='subaction' value='all' />\n";

    if (isset($_POST['sql']))
    {
        $browseoutput .= "<input type='hidden' name='sql' value='".html_escape($_POST['sql'])."' />\n";
    }
    $browseoutput .= 	 "</form></div>\n"
            ."\t</div><form action='$scriptname?action=browse' id='resulttableform' method='post'>\n";

    $browseoutput .= $tableheader;
    $dateformatdetails=getDateFormatData($_SESSION['dateformat']);

    while ($dtrow = $dtresult->FetchRow())
    {
        if (!isset($bgcc)) {$bgcc="even";}
        else
        {
            if ($bgcc == "even") {$bgcc = "odd";}
            else {$bgcc = "even";}
            }
        $browseoutput .= "\t<tr class='{$bgcc}' valign='top'>\n"
                ."<td align='center'><input type='checkbox' class='cbResponseMarker' value='{$dtrow['id']}' name='markedresponses[]' /></td>\n"
                ."<td align='center'>
        <a href='{$scriptname}?action=browse&amp;sid={$surveyid}&amp;subaction=id&amp;id={$dtrow['id']}'><img src='$imageurl/token_viewanswer.png' alt='".$clang->gT('View response details')."'/></a>";

        if (bHasSurveyPermission($surveyid,'responses','update'))
        {
            $browseoutput .= " <a href='{$scriptname}?action=dataentry&amp;sid={$surveyid}&amp;subaction=edit&amp;id={$dtrow['id']}'><img src='$imageurl/token_edit.png' alt='".$clang->gT('Edit this response')."'/></a>";
        }

        // Do not show the download image if the question doesn't contain the File Upload Question Type
        if (bHasFileUploadQuestion($surveyid))
            $browseoutput .=" <a><img id='downloadfile_{$dtrow['id']}' src='{$imageurl}/down.png' alt='".$clang->gT('Download all files in this response as a zip file')."' class='downloadfile'/></a>";

        if (bHasSurveyPermission($surveyid,'responses','delete'))
        {
            $browseoutput .= "<a><img id='deleteresponse_{$dtrow['id']}' src='{$imageurl}/token_delete.png' alt='".$clang->gT('Delete this response')."' class='deleteresponse'/></a>\n";
        }
        $browseoutput .= "</td>";
        $i = 0;
        //If not private, display the token info and link to the token screen
        if ($surveyinfo['anonymized'] == "N" && $dtrow['token'] && db_tables_exist($tokentable))
        {
            if (isset($dtrow['tid']) && !empty($dtrow['tid']))
            {
                //If we have a token, create a link to edit it
                $browsedatafield = "<a href='$scriptname?action=tokens&amp;sid=$surveyid&amp;subaction=edit&amp;tid={$dtrow['tid']}' title='".$clang->gT("Edit this token")."'>";
                $browsedatafield .= "{$dtrow['token']}";
                $browsedatafield .= "</a>";
            } else {
                //No corresponding token in the token tabel, just display the token
                $browsedatafield .= "{$dtrow['token']}";
            }
            $browseoutput .= "<td align='center'>$browsedatafield</td>\n";
            $i++;   //We skip the first record (=token) as we just outputted that one
            }

        for ($i; $i<$fncount; $i++)
        {
            $browsedatafield=htmlspecialchars($dtrow[$fnames[$i][0]]);

            if ( isset($fnames[$i][4]) && $fnames[$i][4] == 'D' && $fnames[$i][0] != '')
            {
                if ($dtrow[$fnames[$i][0]] == NULL)
                    $browsedatafield = "N";
                else
                    $browsedatafield = "Y";
            }
            if (isset($fnames[$i]['type']) && $fnames[$i]['type'] == "|")
            {
                $index = $fnames[$i]['index'];
                $metadata = $fnames[$i]['metadata'];
                $phparray = json_decode($dtrow[$fnames[$i][0]], true);
                if (isset($phparray[$index]))
                {
                    if ($metadata === "size")
                        $browseoutput .= "<td align='center'>".rawurldecode(((int)($phparray[$index][$metadata]))." KB")."</td>\n";
                    else if ($metadata === "name")
                        $browseoutput .= "<td align='center'><a href='#' onclick=\" ".get2post($scriptname.'?action=browse&amp;subaction=all&amp;downloadindividualfile='.$phparray[$index][$metadata].'&amp;fieldname='.$fnames[$i][0].'&amp;id='.$dtrow['id'].'&amp;sid='.$surveyid)."\" >".rawurldecode($phparray[$index][$metadata])."</a></td>\n";
                    else
                        $browseoutput .= "<td align='center'>".rawurldecode($phparray[$index][$metadata])."</td>\n";
                }
                else
                    $browseoutput .= "<td align='center'>&nbsp;</td>\n";
            }
            else
                $browseoutput .= "<td align='center'>$browsedatafield</td>\n";
        }
        $browseoutput .= "\t</tr>\n";
    }
    $browseoutput .= "</table>
    <input type='hidden' name='sid' value='$surveyid' />
    <input type='hidden' name='subaction' value='all' />
    <input id='deleteanswer' name='deleteanswer' value='' type='hidden' />
    <input id='downloadfile' name='downloadfile' value='' type='hidden' />
    </form>\n<br />\n";
}
elseif ($surveyinfo['savetimings']=="Y" && $subaction == "time"){
	$browseoutput .= $surveyoptions;
	$browseoutput .= '<div class="header ui-widget-header">'.$clang->gT('Time statistics').'</div>';

	// table of time statistics - only display completed surveys
    $browseoutput .= "\n<script type='text/javascript'>
                          var strdeleteconfirm='".$clang->gT('Do you really want to delete this response?','js')."';
                          var strDeleteAllConfirm='".$clang->gT('Do you really want to delete all marked responses?','js')."';
                        </script>\n";

    if (isset($_POST['deleteanswer']) && $_POST['deleteanswer']!='')
    {
        $_POST['deleteanswer']=(int) $_POST['deleteanswer']; // sanitize the value
        $query="delete FROM $surveytable where id={$_POST['deleteanswer']}";
        $connect->execute($query) or safe_die("Could not delete response<br />$dtquery<br />".$connect->ErrorMsg()); // checked
    }

    if (isset($_POST['markedresponses']) && count($_POST['markedresponses'])>0)
    {
        foreach ($_POST['markedresponses'] as $iResponseID)
        {
            $iResponseID=(int)$iResponseID; // sanitize the value
            $query="delete FROM $surveytable where id={$iResponseID}";
            $connect->execute($query) or safe_die("Could not delete response<br />$dtquery<br />".$connect->ErrorMsg());  // checked
        }
    }

    $fields=createTimingsFieldMap($surveyid,'full');

    foreach ($fields as $fielddetails)
    {
        // headers for answer id and time data
        if ($fielddetails['type']=='id')
            $fnames[]=array($fielddetails['fieldname'],$fielddetails['question']);
        if ($fielddetails['type']=='interview_time')
            $fnames[]=array($fielddetails['fieldname'],$clang->gT('Total time'));
        if ($fielddetails['type']=='page_time')
            $fnames[]=array($fielddetails['fieldname'],$clang->gT('Group').": ".$fielddetails['group_name']);
        if ($fielddetails['type']=='answer_time')
            $fnames[]=array($fielddetails['fieldname'],$clang->gT('Question').": ".$fielddetails['title']);
    }
    $fncount = count($fnames);

    //NOW LETS CREATE A TABLE WITH THOSE HEADINGS
    $tableheader = "<!-- DATA TABLE -->";
    if ($fncount < 10) {$tableheader .= "<table class='browsetable' width='100%'>\n";}
    else {$tableheader .= "<table class='browsetable'>\n";}
    $tableheader .= "\t<thead><tr valign='top'>\n"
            . "<th><input type='checkbox' id='selectall'></th>\n"
            . "<th>".$clang->gT('Actions')."</th>\n";
    foreach ($fnames as $fn)
    {
        if (!isset($currentgroup))  {$currentgroup = $fn[1]; $gbc = "oddrow";}
        if ($currentgroup != $fn[1])
        {
            $currentgroup = $fn[1];
            if ($gbc == "oddrow") {$gbc = "evenrow";}
            else {$gbc = "oddrow";}
            }
        $tableheader .= "<th class='$gbc'><strong>"
                . strip_javascript("$fn[1]")
                . "</strong></th>\n";
    }
    $tableheader .= "\t</tr></thead>\n\n";
    $tableheader .= "\t<tfoot><tr><td colspan=".($fncount+2).">"
                   ."<img id='imgDeleteMarkedResponses' src='$imageurl/token_delete.png' alt='".$clang->gT('Delete marked responses')."' />"
                   ."\t</tr></tfoot>\n\n";

    $start=returnglobal('start');
    $limit=returnglobal('limit');
    if (!isset($limit) || $limit== '') {$limit = 50;}
    if (!isset($start) || $start =='') {$start = 0;}

    //LETS COUNT THE DATA
    $dtquery = "SELECT count(t.id) FROM {$surveytimingstable} t INNER JOIN {$surveytable} ON t.id={$surveytable}.id WHERE submitdate IS NOT NULL ";

    $dtresult=db_execute_num($dtquery) or safe_die("Couldn't get response data<br />$dtquery<br />".$connect->ErrorMsg());
    while ($dtrow=$dtresult->FetchRow()) {$dtcount=$dtrow[0];}

    if ($limit > $dtcount) {$limit=$dtcount;}

    //NOW LETS SHOW THE DATA
    $dtquery = "SELECT t.* FROM {$surveytimingstable} t INNER JOIN {$surveytable} ON t.id={$surveytable}.id WHERE submitdate IS NOT NULL ORDER BY {$surveytable}.id";

    if ($order == "desc") {$dtquery .= " DESC";}

    if (isset($limit))
    {
        if (!isset($start)) {$start = 0;}
        $dtresult = db_select_limit_assoc($dtquery, $limit, $start) or safe_die("Couldn't get surveys<br />$dtquery<br />".$connect->ErrorMsg());
    }
else
{
        $dtresult = db_execute_assoc($dtquery) or safe_die("Couldn't get surveys<br />$dtquery<br />".$connect->ErrorMsg());
    }
    $dtcount2 = $dtresult->RecordCount();
    $cells = $fncount+1;

    //CONTROL MENUBAR
    $last=$start-$limit;
    $next=$start+$limit;
    $end=$dtcount-$limit;
    if ($end < 0) {$end=0;}
    if ($last <0) {$last=0;}
    if ($next >= $dtcount) {$next=$dtcount-$limit;}
    if ($end < 0) {$end=0;}

    $browseoutput .= "<div class='menubar'>\n"
            . "\t<div class='menubar-title ui-widget-header'>\n"
            . "<strong>".$clang->gT("Data view control")."</strong></div>\n"
            . "\t<div class='menubar-main'>\n";
    if (!isset($_POST['sql']))
    {
        $browseoutput .= "<a href='$scriptname?action=browse&amp;subaction=time&amp;sid=$surveyid&amp;start=0&amp;limit=$limit' "
                ."title='".$clang->gTview("Show start...")."' >"
                ."<img name='DataBegin' align='left' src='$imageurl/databegin.png' alt='".$clang->gT("Show start...")."' /></a>\n"
                ."<a href='$scriptname?action=browse&amp;subaction=time&amp;sid=$surveyid&amp;start=$last&amp;limit=$limit' "
                ."title='".$clang->gTview("Show previous..")."' >"
                ."<img name='DataBack' align='left'  src='$imageurl/databack.png' alt='".$clang->gT("Show previous..")."' /></a>\n"
                ."<img src='$imageurl/blank.gif' width='13' height='20' border='0' hspace='0' align='left' alt='' />\n"

                ."<a href='$scriptname?action=browse&amp;subaction=time&amp;sid=$surveyid&amp;start=$next&amp;limit=$limit' " .
                "title='".$clang->gT("Show next...")."' >".
                "<img name='DataForward' align='left' src='$imageurl/dataforward.png' alt='".$clang->gT("Show next..")."' /></a>\n"
                ."<a href='$scriptname?action=browse&amp;subaction=time&amp;sid=$surveyid&amp;start=$end&amp;limit=$limit' " .
                "title='".$clang->gT("Show last...")."' >" .
                "<img name='DataEnd' align='left' src='$imageurl/dataend.png' alt='".$clang->gT("Show last..")."' /></a>\n"
                ."<img src='$imageurl/seperator.gif' border='0' hspace='0' align='left' alt='' />\n";
    }
    $selectshow='';
    $selectinc='';
    $selecthide='';

    if(incompleteAnsFilterstate() == "inc") { $selectinc="selected='selected'"; }
    elseif (incompleteAnsFilterstate() == "filter") { $selecthide="selected='selected'"; }
    else { $selectshow="selected='selected'"; }

    $browseoutput .="<form action='$scriptname?action=browse' id='browseresults' method='post'><font size='1' face='verdana'>\n"
            ."<img src='$imageurl/blank.gif' width='31' height='20' border='0' hspace='0' align='right' alt='' />\n"
            ."".$clang->gT("Records displayed:")."<input type='text' size='4' value='$dtcount2' name='limit' id='limit' />\n"
            ."&nbsp;&nbsp; ".$clang->gT("Starting from:")."<input type='text' size='4' value='$start' name='start' id='start' />\n"
            ."&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' value='".$clang->gT("Show")."' />\n"
            ."</font>\n"
            ."<input type='hidden' name='sid' value='$surveyid' />\n"
            ."<input type='hidden' name='action' value='browse' />\n"
            ."<input type='hidden' name='subaction' value='time' />\n";
    if (isset($_POST['sql']))
    {
        $browseoutput .= "<input type='hidden' name='sql' value='".html_escape($_POST['sql'])."' />\n";
    }
    $browseoutput .= 	 "</form></div>\n"
            ."\t</div><form action='$scriptname?action=browse' id='resulttableform' method='post'>\n";

    $browseoutput .= $tableheader;
    $dateformatdetails=getDateFormatData($_SESSION['dateformat']);

    while ($dtrow = $dtresult->FetchRow())
    {
        if (!isset($bgcc)) {$bgcc="evenrow";}
        else
        {
            if ($bgcc == "evenrow") {$bgcc = "oddrow";}
            else {$bgcc = "evenrow";}
            }
        $browseoutput .= "\t<tr class='$bgcc' valign='top'>\n"
                ."<td align='center'><input type='checkbox' class='cbResponseMarker' value='{$dtrow['id']}' name='markedresponses[]' /></td>\n"
                ."<td align='center'>
        <a href='$scriptname?action=browse&amp;sid=$surveyid&amp;subaction=id&amp;id={$dtrow['id']}'><img src='$imageurl/token_viewanswer.png' alt='".$clang->gT('View response details')."'/></a>
        <a href='$scriptname?action=dataentry&amp;sid=$surveyid&amp;subaction=edit&amp;id={$dtrow['id']}'><img src='$imageurl/token_edit.png' alt='".$clang->gT('Edit this response')."'/></a>
        <a><img id='deleteresponse_{$dtrow['id']}' src='$imageurl/token_delete.png' alt='".$clang->gT('Delete this response')."' class='deleteresponse'/></a></td>\n";

        for ($i = 0; $i<$fncount; $i++)
        {
            $browsedatafield=htmlspecialchars($dtrow[$fnames[$i][0]]);

            // seconds -> minutes & seconds
			if (strtolower(substr($fnames[$i][0],-4)) == "time")
			{
                $minutes = (int)($browsedatafield/60);
                $seconds = $browsedatafield%60;
                $browsedatafield = '';
                if ($minutes > 0)
				    $browsedatafield .= "$minutes min ";
                $browsedatafield .= "$seconds s";
            }
            $browseoutput .= "<td align='center'>$browsedatafield</td>\n";
        }
        $browseoutput .= "\t</tr>\n";
    }
    $browseoutput .= "</table>
    <input type='hidden' name='sid' value='$surveyid' />
    <input type='hidden' name='subaction' value='time' />
    <input id='deleteanswer' name='deleteanswer' value='' type='hidden' />
    </form>\n<br />\n";

	// Interview time
	$browseoutput .= '<div class="header ui-widget-header">'.$clang->gT('Interview time').'</div>';

	//interview Time statistics
	$count=false;
	//$survstats=substr($surveytableNq);
	$queryAvg="SELECT AVG(timings.interviewtime) AS avg, COUNT(timings.id) AS count FROM {$surveytableNq}_timings AS timings JOIN {$surveytable} AS surv ON timings.id=surv.id WHERE surv.submitdate IS NOT NULL";
	$queryAll="SELECT timings.interviewtime FROM {$surveytableNq}_timings AS timings JOIN {$surveytable} AS surv ON timings.id=surv.id WHERE surv.submitdate IS NOT NULL ORDER BY timings.interviewtime";
	$browseoutput .= '<table class="statisticssummary">';
	if($result=db_execute_assoc($queryAvg)){

		$row=$result->FetchRow();
		$min = (int)($row['avg']/60);
		$sec = $row['avg']%60;
		$count=$row['count'];
		$browseoutput .= '<tr><Th>'.$clang->gT('Average interview time: ')."</th><td>{$min} min. {$sec} sec.</td></tr>";
	}

	if($count && $result=db_execute_assoc($queryAll)){

		$middleval = floor(($count-1)/2);
		$i=0;
		if($count%2){
			while($row=$result->FetchRow()){

				if($i==$middleval){
					$median=$row['interviewtime'];
					break;
				}
				$i++;
			}
		}else{
			while($row=$result->FetchRow()){
				if($i==$middleval){
					$nextrow=$result->FetchRow();
					$median=($row['interviewtime']+$nextrow['interviewtime'])/2;
					break;
				}
				$i++;
			}
		}
		$min = (int)($median/60);
		$sec = $median%60;
		$browseoutput.='<tr><Th>'.$clang->gT('Median: ')."</th><td>{$min} min. {$sec} sec.</td></tr>";
	}
	$browseoutput .= '</table>';
}
elseif ($subaction=="time")
{
    $browseoutput .= $surveyoptions;
    $browseoutput .= "<div class='header ui-widget-header'>".$clang->gT("Timings")."</div>";
	$browseoutput .= "Timing saving is disabled or the timing table does not exist. Try to reactivate survey.\n";
}
else
{
    $browseoutput .= $surveyoptions;
    $num_total_answers=0;
    $num_completed_answers=0;
    $gnquery = "SELECT count(id) FROM $surveytable";
    $gnquery2 = "SELECT count(id) FROM $surveytable WHERE submitdate IS NOT NULL";
    $gnresult = db_execute_num($gnquery);
    $gnresult2 = db_execute_num($gnquery2);

    while ($gnrow=$gnresult->FetchRow()) {$num_total_answers=$gnrow[0];}
    while ($gnrow2=$gnresult2->FetchRow()) {$num_completed_answers=$gnrow2[0];}
    $browseoutput .= "<div class='header ui-widget-header'>".$clang->gT("Response summary")."</div>"
            ."<p><table class='statisticssummary'>\n"
            ."<tfoot><tr><th>".$clang->gT("Total responses:")."</th><td>".$num_total_answers."</td></tr></tfoot>"
            ."\t<tbody>"
            ."<tr><th>".$clang->gT("Full responses:")."</th><td>".$num_completed_answers."</td></tr>"
            ."<tr><th>".$clang->gT("Incomplete responses:")."</th><td>".($num_total_answers-$num_completed_answers)."</td></tr></tbody>"
            ."</table>";

}

/**
 * Supply an array with the responseIds and all files will be added to the zip
 * and it will be be spit out on success
 *
 * @param array $responseIds
 * @return ZipArchive
 */
function zipFiles($responseIds, $zipfilename) {
    global $uploaddir, $surveyid, $surveytable;

    require_once('classes/pclzip/pclzip.lib.php');
    $tmpdir = $uploaddir. "/surveys/" . $surveyid . "/files/";

    $filelist = array();
    $fieldmap = createFieldMap($surveyid, 'full');

    foreach ($fieldmap as $field)
    {
        if ($field['type'] == "|" && $field['aid']!=='filecount')
        {
            $filequestion[] = $field['fieldname'];
        }
    }
    $filequestion = array_map('db_quote_id',$filequestion);
    $initquery = "SELECT " . implode(', ', $filequestion);

    foreach ((array)$responseIds as $responseId)
    {
        $responseId=(int)$responseId; // sanitize the value

        $query = $initquery . " FROM $surveytable WHERE id=$responseId";
        $filearray = db_execute_assoc($query) or safe_die("Could not download response<br />$query<br />".$connect->ErrorMsg());
        $metadata = array();
        $filecount = 0;
        while ($metadata = $filearray->FetchRow())
        {
            foreach ($metadata as $data)
            {
                $phparray = json_decode($data, true);
                if (is_array($phparray)) {
                    foreach($phparray as $file) {
                        $filecount++;
                        $file['responseid'] = $responseId;
                        $file['name'] = rawurldecode($file['name']);
                        $file['index'] = $filecount;
                        /*
                         * Now add the file to the archive, prefix files with responseid_index to keep them
                         * unique. This way we can have 234_1_image1.gif, 234_2_image1.gif as it could be
                         * files from a different source with the same name.
                         */
                        $filelist[] = array(PCLZIP_ATT_FILE_NAME          =>$tmpdir . $file['filename'],
                                            PCLZIP_ATT_FILE_NEW_FULL_NAME =>sprintf("%05s_%02s_%s", $file['responseid'], $file['index'], $file['name']));
                    }
                }
            }
        }
    }

    if (count($filelist)>0) {
        $zip = new PclZip($tmpdir . $zipfilename);
        if ($zip->create($filelist)===0) {
            //Oops something has gone wrong!
        }

        if (file_exists($tmpdir."/".$zipfilename)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($zipfilename));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($tmpdir."/".$zipfilename));
            ob_clean();
            flush();
            readfile($tmpdir . "/" . $zipfilename);
            unlink($tmpdir . "/" . $zipfilename);
            exit;
        }
    }
}
