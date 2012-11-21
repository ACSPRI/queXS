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
 * $Id: fck_LimeReplacementFields.php 11664 2011-12-16 05:19:42Z tmswhite $
 */

include_once("login_check.php");

if(!isset($_SESSION['loginID']))
{
    die ("Unauthenticated Access Forbiden");
}

$surveyid=returnglobal('sid');
if (!isset($gid)) {$gid=returnglobal('gid');}
if (!isset($qid)) {$qid=returnglobal('qid');}
$fieldtype=preg_replace("/[^_.a-zA-Z0-9-]/", "",$_GET['fieldtype']);
$action=preg_replace("/[^_.a-zA-Z0-9-]/", "",$_GET['editedaction']);

//$InsertansUnsupportedtypes=Array('TEST-A','TEST-B','TEST-C','TEST-D');
$InsertansUnsupportedtypes=Array(); // Currently all question types are supported

$replFields=Array();
$isInstertansEnabled=false;

$limereplacementoutput="\t\t<script language=\"javascript\">\n"
. "\t\t\$(document).ready(function ()\n"
. "\t\t\t{\n"
. "\t\t\t\tLoadSelected() ;\n"
. "\t\t\t\tmydialog.SetOkButton( true ) ;\n"
. "\n"
. "SelectField( 'cquestions' ) ;\n"
. "\t});\n"
. "\n";

/**$limereplacementoutput="\n"
 . "if (! oEditor.FCKBrowserInfo.IsIE)\n"
 . "{\n"
 . "\tinnertext = '' + dialog.EditorWindow.getSelection() + '' ;\n"
 . "}\n"
 . "else\n"
 . "{\n"
 . "\tinnertext = '' + dialog.EditorDocument.selection.createRange().text + '' ;\n"
 . "}\n";
 **/

$limereplacementoutput .= ""
. "\tvar eSelected = dialog.Selection.GetSelectedElement() ;\n"
. "\n";

/**
 $limereplacementoutput="\n"
 . "function LoadSelected()\n"
 . "{\n"
 . "\tif ( innertext == '' )\n"
 . "return ;\n"
 . "var replcode=innertext.substring(innertext.indexOf('{')+1,innertext.lastIndexOf('}'));\n"
 . "document.getElementById('cquestions').value = replcode;\n"
 . "}\n";
 **/

$limereplacementoutput .= ""
. "\tfunction LoadSelected()\n"
. "\t{\n"
. "if ( !eSelected )\n"
. "\treturn ;\n"
. "if ( eSelected.tagName == 'SPAN' && eSelected._fckLimeReplacementFields )\n"
. "\t document.getElementById('cquestions').value = eSelected._fckLimeReplacementFields ;\n"
. "else\n"
. "\teSelected == null ;\n"
. "\t}\n";

$limereplacementoutput .= ""
. "\tfunction Ok()\n"
. "\t{\n"
. "var sValue = document.getElementById('cquestions').value ;\n"

. "FCKLimeReplacementFieldss.Add( sValue ) ;\n"
. "return true ;\n"
. "\t}\n";

$limereplacementoutput .= ""
. "\t</script>\n"
. "</head>\n";

$limereplacementoutput .= "\t<body scroll=\"no\" style=\"OVERFLOW: hidden;\">\n"
. "<table height=\"100%\" cellSpacing=\"0\" cellPadding=\"0\" width=\"100%\" border=\"0\">\n"
. "\t<tr>\n"
. "<td>\n";

switch ($fieldtype)
{
    case 'survey-desc':
    case 'survey-welc':
    case 'survey-endtext':
    case 'edittitle': // for translation
    case 'editdescription': // for translation
    case 'editwelcome': // for translation
    case 'editend': // for translation
        $replFields[]=array('TOKEN:FIRSTNAME',$clang->gT("First name from token"));
        $replFields[]=array('TOKEN:LASTNAME',$clang->gT("Last name from token"));
        $replFields[]=array('TOKEN:EMAIL',$clang->gT("Email from the token"));
        $replFields[]=array('TOKEN:USESLEFT',$clang->gT("Uses left"));
        $attributes=GetTokenFieldsAndNames($surveyid,true);
        foreach ($attributes as $attributefield=>$attributedescription)
        {
            $replFields[]=array('TOKEN:'.strtoupper($attributefield), sprintf($clang->gT("Token attribute: %s"),$attributedescription));
        }
        $replFields[]=array('EXPIRY',$clang->gT("Survey expiration date"));
        break;

    case 'email-admin-notification':
        $replFields[]=array('RELOADURL',$clang->gT("Reload URL"));
        $replFields[]=array('VIEWRESPONSEURL',$clang->gT("View response URL"));
        $replFields[]=array('EDITRESPONSEURL',$clang->gT("Edit response URL"));
        $replFields[]=array('STATISTICSURL',$clang->gT("Statistics URL"));
        $replFields[]=array('TOKEN',$clang->gT("Token code for this participant"));
        $replFields[]=array('TOKEN:FIRSTNAME',$clang->gT("First name from token"));
        $replFields[]=array('TOKEN:LASTNAME',$clang->gT("Last name from token"));
        $replFields[]=array('TOKEN:USESLEFT',$clang->gT("Uses left"));
        $replFields[]=array('SURVEYNAME',$clang->gT("Name of the survey"));
        $replFields[]=array('SURVEYDESCRIPTION',$clang->gT("Description of the survey"));
        $attributes=GetTokenFieldsAndNames($surveyid,true);
        foreach ($attributes as $attributefield=>$attributedescription)
        {
            $replFields[]=array(strtoupper($attributefield), sprintf($clang->gT("Token attribute: %s"),$attributedescription));
        }
        $replFields[]=array('ADMINNAME',$clang->gT("Name of the survey administrator"));
        $replFields[]=array('ADMINEMAIL',$clang->gT("Email address of the survey administrator"));
        break;
    case 'email-admin-resp':
        $replFields[]=array('RELOADURL',$clang->gT("Reload URL"));
        $replFields[]=array('VIEWRESPONSEURL',$clang->gT("View response URL"));
        $replFields[]=array('EDITRESPONSEURL',$clang->gT("Edit response URL"));
        $replFields[]=array('STATISTICSURL',$clang->gT("Statistics URL"));
        $replFields[]=array('ANSWERTABLE',$clang->gT("Answers from this response"));
        $replFields[]=array('TOKEN',$clang->gT("Token code for this participant"));
        $replFields[]=array('TOKEN:FIRSTNAME',$clang->gT("First name from token"));
        $replFields[]=array('TOKEN:LASTNAME',$clang->gT("Last name from token"));
        $replFields[]=array('TOKEN:USESLEFT',$clang->gT("Uses left"));
        $replFields[]=array('SURVEYNAME',$clang->gT("Name of the survey"));
        $replFields[]=array('SURVEYDESCRIPTION',$clang->gT("Description of the survey"));
        $attributes=GetTokenFieldsAndNames($surveyid,true);
        foreach ($attributes as $attributefield=>$attributedescription)
        {
            $replFields[]=array(strtoupper($attributefield), sprintf($clang->gT("Token attribute: %s"),$attributedescription));
        }
        $replFields[]=array('ADMINNAME',$clang->gT("Name of the survey administrator"));
        $replFields[]=array('ADMINEMAIL',$clang->gT("Email address of the survey administrator"));
        break;
    case 'email-inv':
    case 'email-rem':
        // these 3 fields are supported by email-inv and email-rem
        // but not email-reg for the moment
        $replFields[]=array('EMAIL',$clang->gT("Email from the token"));
        $replFields[]=array('USESLEFT',$clang->gT("Uses left"));
        $replFields[]=array('OPTOUTURL',$clang->gT("URL for a respondent to opt-out this survey"));
    case 'email-reg':
        $replFields[]=array('FIRSTNAME',$clang->gT("First name from token"));
        $replFields[]=array('LASTNAME',$clang->gT("Last name from token"));
        $replFields[]=array('SURVEYNAME',$clang->gT("Name of the survey"));
        $replFields[]=array('SURVEYDESCRIPTION',$clang->gT("Description of the survey"));
        $replFields[]=array('TOKEN',$clang->gT("Token code for this participant"));
        $replFields[]=array('SURVEYID',$clang->gT("ID of the survey"));
        $attributes=GetTokenFieldsAndNames($surveyid,true);
        foreach ($attributes as $attributefield=>$attributedescription)
        {
            $replFields[]=array(strtoupper($attributefield), sprintf($clang->gT("Token attribute: %s"),$attributedescription));
        }
        $replFields[]=array('ADMINNAME',$clang->gT("Name of the survey administrator"));
        $replFields[]=array('ADMINEMAIL',$clang->gT("Email address of the survey administrator"));
        $replFields[]=array('SURVEYURL',$clang->gT("URL of the survey"));
        $replFields[]=array('EXPIRY',$clang->gT("Survey expiration date"));
        break;

    case 'email-conf':
        $replFields[]=array('TOKEN',$clang->gT("Token code for this participant"));
        $replFields[]=array('FIRSTNAME',$clang->gT("First name from token"));
        $replFields[]=array('LASTNAME',$clang->gT("Last name from token"));
        $replFields[]=array('SURVEYNAME',$clang->gT("Name of the survey"));
        $replFields[]=array('SURVEYDESCRIPTION',$clang->gT("Description of the survey"));
        $attributes=GetTokenFieldsAndNames($surveyid,true);
        foreach ($attributes as $attributefield=>$attributedescription)
        {
            $replFields[]=array(strtoupper($attributefield), sprintf($clang->gT("Token attribute: %s"),$attributedescription));
        }
        $replFields[]=array('ADMINNAME',$clang->gT("Name of the survey administrator"));
        $replFields[]=array('ADMINEMAIL',$clang->gT("Email address of the survey administrator"));
        $replFields[]=array('SURVEYURL',$clang->gT("URL of the survey"));
        $replFields[]=array('EXPIRY',$clang->gT("Survey expiration date"));

        // email-conf can accept insertans fields for non anonymous surveys
        if (isset($surveyid))
        {
            $surveyInfo = getSurveyInfo($surveyid);
            if ($surveyInfo['anonymized'] == "N")
            {
                $isInstertansEnabled=true;
            }
        }
        break;

    case 'group-desc':
    case 'question-text':
    case 'question-help':
    case 'editgroup': // for translation
    case 'editgroup_desc': // for translation
    case 'editquestion': // for translation
    case 'editquestion_help': // for translation
        $replFields[]=array('TOKEN:FIRSTNAME',$clang->gT("First name from token"));
        $replFields[]=array('TOKEN:LASTNAME',$clang->gT("Last name from token"));
        $replFields[]=array('TOKEN:EMAIL',$clang->gT("Email from the token"));
        $replFields[]=array('TOKEN:USESLEFT',$clang->gT("Uses left"));
        $attributes=GetTokenFieldsAndNames($surveyid,true);
        foreach ($attributes as $attributefield=>$attributedescription)
        {
            $replFields[]=array('TOKEN:'.strtoupper($attributefield), sprintf($clang->gT("Token attribute: %s"),$attributedescription));
        }
        $replFields[]=array('EXPIRY',$clang->gT("Survey expiration date"));
    case 'editanswer':
        $isInstertansEnabled=true;
        break;
    case 'assessment-text':
        $replFields[]=array('TOTAL',$clang->gT("Overall assessment score"));
        $replFields[]=array('PERC',$clang->gT("Assessment group score"));
        break;
}
if ($isInstertansEnabled===true)
{
    if (empty($surveyid)) {safe_die("No SID provided.");}

    //2: Get all other questions that occur before this question that are pre-determined answer types
    $fieldmap = createFieldMap($surveyid, 'full');

    $surveyInfo = getSurveyInfo($surveyid);
    $surveyformat = $surveyInfo['format'];// S, G, A
    $prevquestion=null;
    $previouspagequestion = true;
    //Go through each question until we reach the current one
    //error_log(print_r($qrows,true));
    $questionlist=array();
    foreach ($fieldmap as $field)
    {
        if (empty($field['qid'])) continue;
        $AddQuestion=True;
        switch ($action)
        {
            case 'addgroup':
                $AddQuestion=True;
                break;

            case 'editgroup':
            case 'editgroup_desc':
            case 'translategroup':
                if (empty($gid)) {safe_die("No GID provided.");}

                if ($field['gid'] == $gid)
                {
                    $AddQuestion=False;
                }
                break;

            case 'addquestion':
                if (empty($gid)) {safe_die("No GID provided.");}

                if ( !is_null($prevquestion) &&
                $prevquestion['gid'] == $gid &&
                $field['gid'] != $gid)
                {
                    $AddQuestion=False;
                }
                break;

            case 'editanswer':
            case 'copyquestion':
            case 'editquestion':
            case 'translatequestion':
            case 'translateanswer':
                if (empty($gid)) {safe_die("No GID provided.");}
                if (empty($qid)) {safe_die("No QID provided.");}

                if ($field['gid'] == $gid &&
                $field['qid'] == $qid)
                {
                    $AddQuestion=False;
                }
                break;
            case 'emailtemplates':
                // this is the case for email-conf
                $AddQuestion=True;
                break;
            default:
                safe_die("No Action provided.");
                break;
        }
        if ( $AddQuestion===True)
        {
            if ($action == 'tokens' && $fieldtype == 'email-conf')
            {
                //For confirmation email all fields are valid
                $previouspagequestion = true;
            }
            elseif ($surveyformat == "S")
            {
                $previouspagequestion = true;
            }
            elseif ($surveyformat == "G")
            {
                if ($previouspagequestion === true)
                { // Last question was on a previous page
                    if ($field["gid"] == $gid)
                    { // This question is on same page
                        $previouspagequestion = false;
                    }
                }
            }
            elseif ($surveyformat == "A")
            {
                $previouspagequestion = false;
            }

            $questionlist[]=array_merge($field,Array( "previouspage" => $previouspagequestion));
            $prevquestion=$field;
        }
        else
        {
            break;
        }
    }

    $questionscount=count($questionlist);

    if ($questionscount > 0)
    {
        foreach($questionlist as $rows)
        {
            $question = $rows['question'];

            if (isset($rows['subquestion'])) $question = "[{$rows['subquestion']}] " . $question;
            if (isset($rows['subquestion1'])) $question = "[{$rows['subquestion1']}] " . $question;
            if (isset($rows['subquestion2'])) $question = "[{$rows['subquestion2']}] " . $question;

            $shortquestion=$rows['title'].": ".FlattenText($question);
            $cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['fieldname'],$rows['previouspage']);
        } //foreach questionlist
    } //if questionscount > 0

    // Now I´ll add a hack to add the questions before as option
    // if they are date type

}

if (count($replFields) > 0 || isset($cquestions) )
{
    $limereplacementoutput .= "\t<select name='cquestions' id='cquestions' style='font-family:verdana; background-color: #FFFFFF; font-size:10; border: 0px;width:99%;' size='15' ondblclick='Ok();'>\n";
    $noselection = false;
}
else
{
    $limereplacementoutput .= $clang->gT("No replacement variable available for this field");
    $noselection = true;
}

if (count($replFields) > 0)
{
    $limereplacementoutput .= "<optgroup label='".$clang->gT("Standard Fields")."'>\n";

    foreach ($replFields as $stdfield)
    {
        $limereplacementoutput .= "\t<option value='".$stdfield[0]."' title='".$stdfield[1]."'";
        $limereplacementoutput .= ">".$stdfield[1]."</option>\n";
    }
    $limereplacementoutput .= "</optgroup>\n";
}

if (isset($cquestions))
{
    $limereplacementoutput .= "<optgroup label='".$clang->gT("Previous Answers Fields")."'>\n";
    foreach ($cquestions as $cqn)
    {
        $isDisabled="";
        if (in_array($cqn[2],$InsertansUnsupportedtypes))
        {
            $isDisabled=" disabled='disabled'";
        }
        elseif ($cqn[4] === false)
        {
            $isDisabled=" disabled='disabled'";
        }

        $limereplacementoutput .= "\t<option value='INSERTANS:$cqn[3]' title='".$cqn[0]."'";
        $limereplacementoutput .= " $isDisabled >$cqn[0]</option>\n";
    }
    $limereplacementoutput .= "</optgroup>\n";
}

if ($noselection === false)
{
    $limereplacementoutput .= "\t</select>\n";
}

$limereplacementoutput .= "</td>\n"
. "\t</tr>\n";

if (isset($surveyformat))
{
    switch ($surveyformat)
    {
        case 'A':
            $limereplacementoutput .= "\t<tr>\n"
            . "<td>\n";
            $limereplacementoutput .= "\t<br />\n"
            . "\t<font color='orange'>".$clang->gT("Some Question have been disabled")."</font>\n";
            $limereplacementoutput .= "\t<br />\n"
            . "\t".sprintf($clang->gT("Survey Format is %s:"), $clang->gT("All in one"))
            . "\t<br />\n"
            . "\t<i>".$clang->gT("Only Previous pages answers are available")."</i>\n"
            . "\t<br />\n";
            $limereplacementoutput .= "</td>\n"
            . "\t</tr>\n";
            break;
        case 'G':
            $limereplacementoutput .= "\t<tr>\n"
            . "<td>\n";
            $limereplacementoutput .= "\t<br /><font color='orange'>".$clang->gT("Some Question have been disabled")."</font>";
            $limereplacementoutput .= "<br />".sprintf($clang->gT("Survey mode is set to %s:"), $clang->gT("Group by Group"))."<br/><i>".$clang->gT("Only Previous pages answers are available")."</i><br />";
            $limereplacementoutput .= "</td>\n"
            . "\t</tr>\n";
            break;
    }
}


$limereplacementoutput .= "</table>\n";
echo $limereplacementoutput;
exit;
?>
