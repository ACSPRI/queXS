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
 * $Id: vvexport.php 11664 2011-12-16 05:19:42Z tmswhite $
 */

// Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

//Exports all responses to a survey in special "Verified Voting" format.

include_once("login_check.php");

if (!bHasSurveyPermission($surveyid, 'responses','export'))
{
    return;
}

if (!$subaction == "export")
{
    if(incompleteAnsFilterstate() == "inc")
    {
        $selecthide="";
        $selectshow="";
        $selectinc="selected='selected'";
    }
    elseif (incompleteAnsFilterstate() == "filter")
    {
        $selecthide="selected='selected'";
        $selectshow="";
        $selectinc="";
    }
    else
    {
        $selecthide="";
        $selectshow="selected='selected'";
        $selectinc="";
    }

    $vvoutput = browsemenubar($clang->gT("Export VV file")).
        "<form id='vvexport' method='post' action='admin.php?action=vvexport&amp;sid=$surveyid'>"
    ."<div class='header ui-widget-header'>".$clang->gT("Export a VV survey file")."</div>"
    ."<ul>"
    ."<li>"
    ."<label for='sid'>".$clang->gT("Export Survey").":</label>"
    ."<input type='text' size='10' value='$surveyid' id='sid' name='sid' readonly='readonly' />"
    ."</li>\n"
    ."<li>\n"
    ." <label for='filterinc'>".$clang->gT("Export").":</label>"
    ." <select name='filterinc' id='filterinc'>\n"
    ."  <option value='filter' $selecthide>".$clang->gT("Completed responses only")."</option>\n"
    ."  <option value='show' $selectshow>".$clang->gT("All responses")."</option>\n"
    ."  <option value='incomplete' $selectinc>".$clang->gT("Incomplete responses only")."</option>\n"
    ." </select>\n"
    ."</li>\n";

$quexsfilterstate = questionnaireSampleFilterstate();

    //queXS Addition
    include_once("../quexs.php");
    $vvoutput .= "<li><label for='quexsfilterinc'>".T_("Questionnaire and Sample selection:")."</label><select id='quexsfilterinc' name='quexsfilterinc'>\n"
    ."\t<option value='all' >".T_("All queXS questionnaires and samples associated with this instrument")."</option>\n"
    . get_questionnaire_sample_list($surveyid,$quexsfilterstate)
    ."</select></li>\n";

    $vvoutput .=

    "<li>\n"
    ." <label for='extension'>".$clang->gT("File Extension").": </label>\n"
    ." <input type='text' id='extension' name='extension' size='3' value='csv' /><span style='font-size: 7pt'>*</span>\n"
    ."</li>\n"
    ."</ul>\n"
    ."<p><input type='submit' value='".$clang->gT("Export results")."' />&nbsp;"
    ."<input type='hidden' name='subaction' value='export' />"
    ."</form>"

    ."<p><span style='font-size: 7pt'>* ".$clang->gT("For easy opening in MS Excel, change the extension to 'tab' or 'txt'")."</span><br />\n";
}
elseif (isset($surveyid) && $surveyid)
{
    //Export is happening
    $extension=sanitize_paranoid_string(returnglobal('extension'));
    header("Content-Disposition: attachment; filename=vvexport_$surveyid.".$extension);
    header("Content-type: text/comma-separated-values; charset=UTF-8");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: cache");
    $s="\t";

    $fieldmap=createFieldMap($surveyid, "full");
    $surveytable = "{$dbprefix}survey_$surveyid";

    GetBaseLanguageFromSurveyID($surveyid);

    $fieldnames = array_values($connect->MetaColumnNames($surveytable, true));

    //Create the human friendly first line
    $firstline="";
    $secondline="";
    foreach ($fieldnames as $field)
    {
        $fielddata=arraySearchByKey($field, $fieldmap, "fieldname", 1);
        //$vvoutput .= "<pre>";print_r($fielddata);$vvoutput .= "</pre>";
        if (count($fielddata) < 1) {$firstline.=$field;}
        else
        //{$firstline.=str_replace("\n", " ", str_replace("\t", "   ", strip_tags($fielddata['question'])));}
        {$firstline.=preg_replace('/\s+/',' ',strip_tags($fielddata['question']));}
        $firstline .= $s;
        $secondline .= $field.$s;
    }
    $vvoutput = $firstline."\n";
    $vvoutput .= $secondline."\n";
    $query = "SELECT * FROM $surveytable";

    $qfs = questionnaireSampleFilterstate();
    if ($qfs != false)
    {
        //Limit responses by questionnaire and/or sample
        $query .= "     JOIN `case` AS c ON ({$dbprefix}survey_$surveyid.token = c.token AND c.questionnaire_id = '{$qfs[0]}') ";
        if ($qfs[1] != 0) //if a sample is selected
                $query .= "     JOIN `sample` AS s ON (s.sample_id = c.sample_id AND s.import_id = '{$qfs[1]}') ";
    }

    if (incompleteAnsFilterstate() == "inc")
    {
        $query .= " WHERE submitdate IS NULL ";
    }
    elseif (incompleteAnsFilterstate() == "filter")
    {
        $query .= " WHERE submitdate >= ".$connect->DBDate('1980-01-01'). " ";
    }
    $result = db_execute_assoc($query) or safe_die("Error:<br />$query<br />".$connect->ErrorMsg()); //Checked

    while ($row=$result->FetchRow())
    {
        foreach ($fieldnames as $field)
        {
            if (is_null($row[$field]))
            {
                $value='{question_not_shown}';
            }
            else
            {
                $value=trim($row[$field]);
                // sunscreen for the value. necessary for the beach.
                // careful about the order of these arrays:
                // lbrace has to be substituted *first*
                $value=str_replace(array("{",
                "\n",
                "\r",
                "\t"),
                array("{lbrace}",
                "{newline}",
                "{cr}",
                "{tab}"),
                $value);
            }
            // one last tweak: excel likes to quote values when it
            // exports as tab-delimited (esp if value contains a comma,
            // oddly enough).  So we're going to encode a leading quote,
            // if it occurs, so that we can tell the difference between
            // strings that "really are" quoted, and those that excel quotes
            // for us.
            $value=preg_replace('/^"/','{quote}',$value);
            // yay!  that nasty soab won't hurt us now!
            if($field == "submitdate" && !$value) {$value = "NULL";}
            $sun[]=$value;
        }
        $beach=implode($s, $sun);
        $vvoutput .= $beach;
        unset($sun);
        $vvoutput .= "\n";
    }
    echo $vvoutput;
    exit;

    //$vvoutput .= "<pre>$firstline</pre>";
    //$vvoutput .= "<pre>$secondline</pre>";
    //$vvoutput .= "<pre>"; print_r($fieldnames); $vvoutput .= "</pre>";
    //$vvoutput .= "<pre>"; print_r($fieldmap); $vvoutput .= "</pre>";

}

?>
