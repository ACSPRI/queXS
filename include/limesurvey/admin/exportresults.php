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
* $Id: exportresults.php 11664 2011-12-16 05:19:42Z tmswhite $
*/


//Ensure script is not run directly, avoid path disclosure
include_once("login_check.php");

if (!isset($imageurl)) {$imageurl="./images";}
if (!isset($surveyid)) {$surveyid=returnglobal('sid');}
if (!isset($exportstyle)) {$exportstyle=returnglobal('exportstyle');}
if (!isset($answers)) {$answers=returnglobal('answers');}
if (!isset($type)) {$type=returnglobal('type');}
if (!isset($convertyto1)) {$convertyto1=returnglobal('convertyto1');}
if (!isset($convertnto2)) {$convertnto2=returnglobal('convertnto2');}
if (!isset($convertspacetous)) {$convertspacetous=returnglobal('convertspacetous');}

if (!bHasSurveyPermission($surveyid, 'responses','export'))
{
    exit;
}

include_once(dirname(__FILE__)."/classes/pear/Spreadsheet/Excel/Writer.php");
include_once(dirname(__FILE__)."/classes/tcpdf/extensiontcpdf.php");

$surveybaselang=GetBaseLanguageFromSurveyID($surveyid);
$exportoutput="";

// Get info about the survey
$thissurvey=getSurveyInfo($surveyid);

if (!$exportstyle)
{

    //FIND OUT HOW MANY FIELDS WILL BE NEEDED - FOR 255 COLUMN LIMIT
    $excesscols=createFieldMap($surveyid,'full',false,false,$surveybaselang);
    if ($thissurvey['savetimings'] === "Y") {
        //Append survey timings to the fieldmap array
        $excesscols = $excesscols + createTimingsFieldMap($surveyid);
    }
    $excesscols=array_keys($excesscols);

	//queXS remove token from initial list
	$t = array_search('token',$excesscols);
	if ($t)
		unset($excesscols[$t]);


    $afieldcount = count($excesscols);
    $exportoutput .= browsemenubar($clang->gT("Export results"));
    $exportoutput .= "<div class='header ui-widget-header'>".$clang->gT("Export results").'</div>'
    ."<div class='wrap2columns'>\n"
    ."<form id='resultexport' action='$scriptname?action=exportresults' method='post'><div class='left'>\n";

    if (isset($_POST['sql'])) {$exportoutput .= " - ".$clang->gT("Filtered from statistics script");}
    if (returnglobal('id')<>'') {$exportoutput .= " - ".$clang->gT("Single response");}

    if (incompleteAnsFilterstate() == "filter")
    {
        $selecthide="selected='selected'";
        $selectshow="";
        $selectinc="";
    }
    elseif (incompleteAnsFilterstate() == "inc")
    {
        $selecthide="";
        $selectshow="";
        $selectinc="selected='selected'";
    }
    else
    {
        $selecthide="";
        $selectshow="selected='selected'";
        $selectinc="";
    }

    //get max number of datasets

    $max_datasets_query = "SELECT COUNT(id) FROM {$dbprefix}survey_$surveyid";
    $max_datasets = $connect->GetOne($max_datasets_query);


    $exportoutput .='<fieldset><legend>'.$clang->gT("General").'</legend>'
    // form fields to limit export from X to Y
    ."<ul><li><label>".$clang->gT("Range:")."</label> ".$clang->gT("From")." <input type='text' name='export_from' size='8' value='1' />";
    $exportoutput .= " ".$clang->gT("to")." <input type='text' name='export_to' size='8' value='$max_datasets' /></li>"

    ."<li><label for='filterinc'>".$clang->gT("Completion state")."</label> <select id='filterinc' name='filterinc'>\n"
    ."<option value='filter' $selecthide>".$clang->gT("Completed responses only")."</option>\n"
    ."<option value='show' $selectshow>".$clang->gT("All responses")."</option>\n"
    ."<option value='incomplete' $selectinc>".$clang->gT("Incomplete responses only")."</option>\n"
    ."\t</select>\n"
    ."</li>";

$quexsfilterstate = questionnaireSampleFilterstate();

    //queXS Addition
    include_once("../quexs.php");
    $exportoutput .= "<li><label for='quexsfilterinc'>".T_("Questionnaire and Sample selection:")."</label><select id='quexsfilterinc' name='quexsfilterinc'>\n"
    ."\t<option value='all' >".T_("All queXS questionnaires and samples associated with this instrument")."</option>\n"
    . get_questionnaire_sample_list($surveyid,$quexsfilterstate)
    ."</select></li></ul></fieldset>\n";


    $exportoutput .='<fieldset><legend>'

    .$clang->gT("Questions")."</legend>\n"
    ."<ul>\n"
    ."<li><input type='radio' class='radiobtn' name='exportstyle' value='abrev' id='headabbrev' />"
    ."<label for='headabbrev'>".$clang->gT("Abbreviated headings")."</label></li>\n"
    ."<li><input type='radio' class='radiobtn' checked name='exportstyle' value='full' id='headfull'  />"
    ."<label for='headfull'>".$clang->gT("Full headings")."</label></li>\n"
    ."<li><input type='radio' class='radiobtn' checked name='exportstyle' value='headcodes' id='headcodes' />"
    ."<label for='headcodes'>".$clang->gT("Question codes")."</label></li>\n"
    ."<li><br /><input type='checkbox' value='Y' name='convertspacetous' id='convertspacetous' />"
    ."<label for='convertspacetous'>"
    .$clang->gT("Convert spaces in question text to underscores")."</label></li>\n"
    ."</ul>\n"
    ."</fieldset>\n"

    ."<fieldset>\n"
    ."<legend>".$clang->gT("Answers")."</legend>\n"
    ."<ul>\n"
    ."<li><input type='radio' class='radiobtn' name='answers' value='short' id='ansabbrev' />"
    ."<label for='ansabbrev'>".$clang->gT("Answer Codes")."</label></li>";

    $exportoutput .= "<li><input type='checkbox' value='Y' name='convertyto1' id='convertyto1' style='margin-left: 25px' />"
    ."<label for='convertyto1'>".$clang->gT("Convert Y to")."</label> <input type='text' name='convertyto' size='3' value='1' maxlength='1' style='width:10px'  />";
    $exportoutput .= "</li>\n"
    ."<li><input type='checkbox' value='Y' name='convertnto2' id='convertnto2' style='margin-left: 25px' />"
    ."<label for='convertnto2'>".$clang->gT("Convert N to")."</label> <input type='text' name='convertnto' size='3' value='2' maxlength='1' style='width:10px' />";
    $exportoutput .= "</li><li>\n"
    ."<input type='radio' class='radiobtn' checked name='answers' value='long' id='ansfull' />"
    ."<label for='ansfull'>"
    .$clang->gT("Full Answers")."</label></li>\n"
    ."</ul></fieldset>"
    ."<fieldset><legend>".$clang->gT("Format")."</legend>\n"
    ."<ul>\n"
    ."<li>\n"
    ."\t<input type='radio' class='radiobtn' name='type' value='doc' id='worddoc' onclick='dument.getElementById(\"ansfull\").checked=true;document.getElementById(\"ansabbrev\").disabled=true;' />"
    ."<label for='worddoc'>"
    .$clang->gT("Microsoft Word (Latin charset)")."</label></li>\n"
    ."\t<li><input type='radio' class='radiobtn' name='type' value='xls' checked id='exceldoc'";
    if (!function_exists('iconv'))
    {
        $exportoutput.=' disabled="disabled" ';
    }
    $exportoutput.="onclick='document.getElementById(\"ansabbrev\").disabled=false;' />"
    ."<label for='exceldoc'>"
    .$clang->gT("Microsoft Excel (All charsets)");
    if (!function_exists('iconv'))
    {
        $exportoutput.='<font class="warningtitle">'.$clang->gT("(Iconv Library not installed)").'</font>';
    }
    $exportoutput.="</label></li>\n"
    ."\t<li><input type='radio' class='radiobtn' name='type' value='csv' id='csvdoc'";
    if (!function_exists('iconv'))
    {
        $exportoutput.=' checked="checked" ';
    }
    $exportoutput.=" onclick='document.getElementById(\"ansabbrev\").disabled=false;' />"
    ."<label for='csvdoc'>"
    .$clang->gT("CSV File (All charsets)")."</label></li>\n";
    if(isset($usepdfexport) && $usepdfexport == 1)
    {
        $exportoutput .= "\t<li><input type='radio' class='radiobtn' name='type' value='pdf' id='pdfdoc' onclick='document.getElementById(\"ansabbrev\").disabled=false;' />"
        ."<label for='pdfdoc'>"
        .$clang->gT("PDF")."<br />"
        ."</label></li>\n";
    }
    $exportoutput.="</ul></fieldset>\n"
    ."</div>\n"
    ."<div class='right'>\n"
    ."<fieldset>\n"
    ."<legend>".$clang->gT("Column control")."</legend>\n";

    $exportoutput.="\t<input type='hidden' name='sid' value='$surveyid' />\n";
    if (isset($_POST['sql']))
    {
        $exportoutput .= "\t<input type='hidden' name='sql' value=\""
        .stripcslashes($_POST['sql'])
        ."\" />\n";
    }
    if (returnglobal('id')<>'')
    {
        $exportoutput .= "\t<input type='hidden' name='answerid' value=\""
        .stripcslashes(returnglobal('id'))
        ."\" />\n";
    }

    $exportoutput .= $clang->gT("Choose Columns").":\n";

    if ($afieldcount > 255)
    {
        $exportoutput .= "\t<img src='$imageurl/help.gif' alt='".$clang->gT("Help")."' onclick='javascript:alert(\""
        .$clang->gT("Your survey contains more than 255 columns of responses. Spreadsheet applications such as Excel are limited to loading no more than 255. Select the columns you wish to export in the list below.","js")
        ."\")' />";
    }
    else
    {
        $exportoutput .= "\t<img src='$imageurl/help.gif' alt='".$clang->gT("Help")."' onclick='javascript:alert(\""
        .$clang->gT("Choose the columns you wish to export.","js")
        ."\")' />";
    }
    $exportoutput .= "<br /><select name='colselect[]' multiple size='20'>\n";
    $i=1;
    foreach($excesscols as $ec)
    {
        $exportoutput .= "<option value='$ec'";
        if (isset($_POST['summary']))
        {
            if (in_array($ec, $_POST['summary']))
            {
                $exportoutput .= "selected";
            }
        }
        elseif ($i<256)
        {
            $exportoutput .= " selected";
        }
        $exportoutput .= ">$i: $ec</option>\n";
        $i++;
    }
    $exportoutput .= "\t</select>\n";
    $exportoutput .= "<br />&nbsp;</fieldset>\n";
        //OPTIONAL EXTRAS (FROM TOKENS TABLE)
    // Find out if survey results are anonymous
    if ($thissurvey['anonymized'] == "N" && tableExists("tokens_$surveyid"))
        {
            $exportoutput .= "<fieldset><legend>".$clang->gT("queXS paradata/metadata")."</legend>\n"
            .$clang->gT("Choose fields").":"
            ."<img src='$imageurl/help.gif' alt='".$clang->gT("Help")."' onclick='javascript:alert(\""
            .$clang->gT("You can export associated queXS paradata with each response. Select any additional fields you would like to export.","js")
            ."\")' /><br />"
            ."<select name='attribute_select[]' multiple size='20'>\n"
            ."<option value='firstname' id='firstname' />".$clang->gT("First name")."</option>\n"
            ."<option value='lastname' id='lastname' />".$clang->gT("Last name")."</option>\n"
            ."<option value='email' id='email' />".$clang->gT("Email")."</option>\n";

	$attrfieldnames=GetTokenFieldsAndNames($surveyid,true);
 	foreach ($attrfieldnames as $attr_name=>$attr_desc)
 	{
 	$exportoutput .= "<option value='$attr_name' id='$attr_name' />".$attr_desc."</option>\n";
 	}

            $exportoutput .= "<option value='token' id='token' />".T_("Token")."</option>\n"
            ."<option value='caseid' id='caseid' />".T_("Case ID")."</option>\n"
            ."<option value='outcomeid' id='outcomeid' />".T_("Outcome ID")."</option>\n"
            ."<option value='caseoutcome' id='caseoutcome' />".T_("Case outcome")."</option>\n"
            ."<option value='callattempts' id='callattempts' />".T_("Number of call attempts")."</option>\n"
            ."<option value='messagesleft' id='messagesleft' />".T_("Number of answering machine messages left")."</option>\n"
            ."<option value='casenotes' id='casenotes' />".T_("Case notes")."</option>\n"
            ."<option value='interviewtimec' id='interviewtimec' />".T_("Total interview time over all calls (mins)")."</option>\n"
            ."<option value='interviewtimel' id='interviewtimel' />".T_("Interview time for last call (mins)")."</option>\n"
            ."<option value='lastnumber' id='lastnumber' />".T_("Last number dialled")."</option>\n"
            ."<option value='operatoru' id='operatoru' />".T_("Operator username for last call")."</option>\n"
            ."<option value='shiftr' id='shiftr' />".T_("Shift report")."</option>\n";


	$sql = "SELECT sivr.var,sv.val
		FROM `questionnaire` as q, questionnaire_sample as qs, sample_var as sv, `sample_import_var_restrict` as sivr
		WHERE q.lime_sid = $surveyid
		AND qs.questionnaire_id = q.questionnaire_id 
		AND sivr.sample_import_id = qs.sample_import_id
		AND sv.var_id = sivr.var_id
		GROUP BY qs.sample_import_id,sivr.var";

	$queXSrs = $connect->GetAssoc($sql);


            foreach ($queXSrs as $attr_name=>$val)
            {
                $exportoutput .= "<option value='SAMPLE:$attr_name' id='SAMPLE:$attr_name' />SAMPLE: ".$attr_name."</option>\n";
            }
            $exportoutput .= "</select></fieldset>\n";
        }
    $exportoutput .= "</div>\n"
    ."\t<div style='clear:both;'><p><input type='submit' value='".$clang->gT("Export data")."' /></div></form></div>\n";
    return;
}





// ======================================================================
// Actual export routines start here !
// ======================================================================

$tokenTableExists=tableExists('tokens_'.$surveyid);
$aTokenFieldNames=array();
$attributeFieldAndNames = array();

if ($tokenTableExists)
{
    $aTokenFieldNames=GetTokenFieldsAndNames($surveyid,false,true);

	$sql = "SELECT sivr.var,sv.val
		FROM `questionnaire` as q, questionnaire_sample as qs, sample_var as sv, `sample_import_var_restrict` as sivr
		WHERE q.lime_sid = $surveyid
		AND qs.questionnaire_id = q.questionnaire_id 
		AND sivr.sample_import_id = qs.sample_import_id
		AND sv.var_id = sivr.var_id
		GROUP BY qs.sample_import_id,sivr.var";

	$attributeFields = $connect->GetAssoc($sql);



}

switch ( $_POST["type"] ) {
    case "doc":
        header("Content-Disposition: attachment; filename=results-survey".$surveyid.".doc");
        header("Content-type: application/vnd.ms-word");
        $separator="\t";
        break;
    case "xls":

        $workbook = new Spreadsheet_Excel_Writer();
        $workbook->setVersion(8);
        // Inform the module that our data will arrive as UTF-8.
        // Set the temporary directory to avoid PHP error messages due to open_basedir restrictions and calls to tempnam("", ...)
        if (!empty($tempdir)) {
            $workbook->setTempDir($tempdir);
        }
        $workbook->send('results-survey'.$surveyid.'.xls');
        // Creating the first worksheet

        $query="SELECT * FROM {$dbprefix}surveys_languagesettings WHERE surveyls_survey_id={$surveyid} AND surveyls_language='{$surveybaselang}'" ;
        $result=db_execute_assoc($query) or safe_die("Couldn't get privacy data<br />$query<br />".$connect->ErrorMsg());
        $row = $result->FetchRow();

        $row['surveyls_title']=substr(str_replace(array('*', ':', '/', '\\', '?', '[', ']'),array(' '),$row['surveyls_title']),0,31); // Remove invalid characters
        $sheet =& $workbook->addWorksheet(); // do not translate/change this - the library does not support any special chars in sheet name
//        $row['surveyls_title']
        $sheet->setInputEncoding('utf-8');
        $sheet->name=$row['surveyls_title'] ;
        $separator="~|";
        break;
    case "csv":
        header("Content-Disposition: attachment; filename=results-survey".$surveyid.".csv");
        header("Content-type: text/comma-separated-values; charset=UTF-8");
        $separator=",";
        break;
    case "pdf":
        $pdf = new PDF($pdforientation,'mm','A4');
        $pdf->SetFont($pdfdefaultfont,'',$pdffontsize);
        $pdf->AddPage();
        $pdf->intopdf("PDF Export ".date("Y.m.d-H:i",time()));
        $query="SELECT * FROM {$dbprefix}surveys_languagesettings WHERE surveyls_survey_id={$surveyid}  AND surveyls_language='{$surveybaselang}'" ;
        $result=db_execute_assoc($query) or safe_die("Couldn't get privacy data<br />$query<br />".$connect->ErrorMsg());
        while ($row = $result->FetchRow())
        {
            $pdf->intopdf($clang->gT("General information in language: ").getLanguageNameFromCode($row['surveyls_language']),'B');
            $pdf->ln();
            $pdf->titleintopdf($row['surveyls_title'],$row['surveyls_description']);
            $surveyname=$row['surveyls_title'];
        }
        $pdf->AddPage();
        $separator="\t";
        break;
    default:
        header("Content-Disposition: attachment; filename=results-survey".$surveyid.".csv");
        header("Content-type: text/comma-separated-values; charset=UTF-8");
        $separator=",";
        break;
}
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");

// Export Language is set by default to surveybaselang
// * the explang language code is used in SQL queries
// * the alang object is used to translate headers and hardcoded answers
// In the future it might be possible to 'post' the 'export language' from
// the exportresults form
$explang = $surveybaselang;
$elang=new limesurvey_lang($explang);

//STEP 1: First line is column headings

$fieldmap=createFieldMap($surveyid,'full');
if ($thissurvey['savetimings'] === "Y") {
    //Append survey timings to the fieldmap array
    $fieldmap = $fieldmap + createTimingsFieldMap($surveyid,'full');
}

//Get the fieldnames from the survey table for column headings
$surveytable = "{$dbprefix}survey_$surveyid";
if (isset($_POST['colselect']))
{
    $selectfields="";
    foreach($_POST['colselect'] as $cs)
    {
        if (!isset($fieldmap[$cs]) && !isset($aTokenFieldNames[$cs]) && $cs != 'completed') continue; // skip invalid field names to prevent SQL injection
        if ($tokenTableExists && $cs == 'token' && isset($_POST['attribute_select']) && is_array($_POST['attribute_select']) && in_array('token',$_POST['attribute_select']))
        {
            // We shouldnt include the token field when we are joining with the token field
        }
        elseif ($cs === 'id')
        {
            $selectfields.= db_quote_id($surveytable) . '.' . db_quote_id($cs) . ", ";
        }
        elseif ($cs != 'completed')
        {
            $selectfields.= db_quote_id($cs).", ";
        }
        else
        {
            $selectfields.= "CASE WHEN $surveytable.submitdate IS NULL THEN 'N' ELSE 'Y' END AS completed, ";
        }
    }
    $selectfields = mb_substr($selectfields, 0, strlen($selectfields)-2);
}
else
{
    $selectfields="$surveytable.*, CASE WHEN $surveytable.submitdate IS NULL THEN 'N' ELSE 'Y' END AS completed";
}

$dquery = "SELECT $selectfields";
if ($tokenTableExists && $thissurvey['anonymized']=='N' && isset($_POST['attribute_select']) && is_array($_POST['attribute_select']))
{
    if (in_array('caseid',$_POST['attribute_select']))
    {
        $dquery .= ", 	(SELECT c4.case_id
			FROM `case` as c4
			WHERE c4.token = {$dbprefix}survey_$surveyid.token) as caseid ";
    }
	if (in_array('outcomeid',$_POST['attribute_select']))
    {
        $dquery .= ", 	(SELECT c5.current_outcome_id
			FROM `case` as c5
			WHERE c5.token = {$dbprefix}survey_$surveyid.token) as outcomeid ";
    }
    if (in_array('caseoutcome',$_POST['attribute_select']))
    {
        $dquery .= ", 	(SELECT o1.description
			FROM `case` as ca8, `outcome` as o1
			WHERE ca8.token = {$dbprefix}survey_$surveyid.token
			AND o1.outcome_id = ca8.current_outcome_id) as caseoutcome ";
    }
    if (in_array('callattempts',$_POST['attribute_select']))
    {
        $dquery .= ", 	(SELECT COUNT(c.call_attempt_id) 
			FROM call_attempt as c, `case` as ca
                	WHERE c.case_id = ca.case_id AND ca.token = {$dbprefix}survey_$surveyid.token) as callattempts ";
    }
    if (in_array('messagesleft',$_POST['attribute_select']))
    {
        $dquery .= ",  (SELECT COUNT(c2.call_id) 
                        FROM `call` as c2, `case` as ca2
                        WHERE ca2.case_id = c2.case_id 
			AND ca2.token = {$dbprefix}survey_$surveyid.token 
			AND c2.outcome_id = 23) as messagesleft ";
    }
    if (in_array('casenotes',$_POST['attribute_select']))
    {
        $dquery .= ",  (SELECT GROUP_CONCAT(cn1.note SEPARATOR '|')
			FROM `case_note`  as cn1, `case` as ca3
                        WHERE ca3.case_id = cn1.case_id 
			AND ca3.token = {$dbprefix}survey_$surveyid.token
			GROUP BY cn1.case_id) as casenotes ";
    }
    if (in_array('interviewtimec',$_POST['attribute_select']))
    {
        $dquery .= ",  (SELECT ROUND(SUM( TIMESTAMPDIFF(SECOND , cl2.start,IFNULL(cl2.end,CONVERT_TZ(NOW(),'System','UTC'))))/60,2)
			FROM `call_attempt` as cl2, `case` as ca4
			WHERE cl2.case_id = ca4.case_id
			AND ca4.token = {$dbprefix}survey_$surveyid.token) as interviewtimec ";
    }
    if (in_array('interviewtimel',$_POST['attribute_select']))
    {
        $dquery .= ",  (SELECT ROUND(TIMESTAMPDIFF(SECOND , cl3.start,IFNULL(cl3.end,CONVERT_TZ(NOW(),'System','UTC')))/60,2)
			FROM `call` as cl3, `case` as ca5
			WHERE cl3.case_id = ca5.case_id
			AND ca5.token = {$dbprefix}survey_$surveyid.token
			ORDER BY cl3.call_id DESC
			LIMIT 1) as interviewtimel ";
    }
    if (in_array('lastnumber',$_POST['attribute_select']))
    {
        $dquery .= ",  (SELECT cp1.phone
			FROM `call` as cl4, `case` as ca6, `contact_phone` as cp1
			WHERE cl4.case_id = ca6.case_id
			AND ca6.token = {$dbprefix}survey_$surveyid.token
			AND cp1.contact_phone_id = cl4.contact_phone_id
			ORDER BY cl4.call_id DESC
			LIMIT 1) as lastnumber ";
    }
    if (in_array('operatoru',$_POST['attribute_select']))
    {
        $dquery .= ",  (SELECT op1.username
			FROM `call` as cl5, `case` as ca7, `operator` as op1
			WHERE cl5.case_id = ca7.case_id
			AND ca7.token = {$dbprefix}survey_$surveyid.token
			AND op1.operator_id = cl5.operator_id
			ORDER BY cl5.call_id DESC
			LIMIT 1) as operatoru ";
    }
    if (in_array('shiftr',$_POST['attribute_select']))
    {
      $dquery .= ",  (SELECT GROUP_CONCAT(DISTINCT sr1.report SEPARATOR '|')
      FROM `call` as cl6, `case` as ca8, `shift` as sh1, `shift_report` as sr1
      WHERE cl6.case_id = ca8.case_id
      AND ca8.token = {$dbprefix}survey_$surveyid.token
      AND sr1.shift_id = sh1.shift_id
      AND sh1.questionnaire_id = ca8.questionnaire_id
      AND cl6.start >= sh1.start
      AND cl6.end < sh1.end
      GROUP BY cl6.case_id) as shiftr ";
    }
    if (in_array('token',$_POST['attribute_select']))
    {
        $dquery .= ", {$dbprefix}tokens_$surveyid.token";
    }

    if (in_array('firstname',$_POST['attribute_select']))
    {
        $dquery .= ", {$dbprefix}tokens_$surveyid.firstname";
    }


   if (in_array('lastname',$_POST['attribute_select']))
    {
        $dquery .= ", {$dbprefix}tokens_$surveyid.lastname";
    }


   if (in_array('email',$_POST['attribute_select']))
    {
        $dquery .= ", {$dbprefix}tokens_$surveyid.email";
    }


    $i =1;
	$attrfieldnames=GetTokenFieldsAndNames($surveyid,true);
	foreach ($attrfieldnames as $attr_name=>$attr_desc)
 	{
 	if (in_array($attr_name,$_POST['attribute_select']))
 	{
 	$dquery .= ", {$dbprefix}tokens_$surveyid.$attr_name";
	$attributeFieldAndNames[$attr_name] = $attr_desc;
 	}
	$i++;
 	}


    foreach ($attributeFields as $attr_name => $attr_val)
    {
        if (in_array("SAMPLE:$attr_name",$_POST['attribute_select']))
        {
            $dquery .= ", (	SELECT sv.val
				FROM sample_var as sv, `case` as c3,`sample_import_var_restrict` as sivr
				WHERE c3.token = {$dbprefix}survey_$surveyid.token
				AND c3.sample_id = sv.sample_id
				AND sivr.var_id = sv.var_id
				AND sivr.var LIKE '$attr_name') as attribute_$i ";

		$attributeFieldAndNames["attribute_$i"] = $attr_name;

		$i++;
        }
    }
}
$dquery .= " FROM $surveytable";

if ($thissurvey['savetimings']==="Y") {
    $dquery .= " LEFT OUTER JOIN {$surveytable}_timings"
    . " ON $surveytable.id = {$surveytable}_timings.id";
}

if ($tokenTableExists && $thissurvey['anonymized']=='N')
{
    $dquery .= " LEFT OUTER JOIN {$dbprefix}tokens_$surveyid"
    . " ON $surveytable.token = {$dbprefix}tokens_$surveyid.token";
}


$qfs = questionnaireSampleFilterstate();
if ($qfs != false)
{
    //Limit responses by questionnaire and/or sample
    $dquery .= "     JOIN `case` AS c ON ({$dbprefix}survey_$surveyid.token = c.token AND c.questionnaire_id = '{$qfs[0]}') ";
    if ($qfs[1] != 0) //if a sample is selected
            $dquery .= "     JOIN `sample` AS s ON (s.sample_id = c.sample_id AND s.import_id = '{$qfs[1]}') ";
}

$dresult = db_select_limit_assoc($dquery, 1) or safe_die($clang->gT("Error")." getting results<br />$dquery<br />".$connect->ErrorMsg());
$fieldcount = $dresult->FieldCount();
$firstline="";
$faid="";
for ($i=0; $i<$fieldcount; $i++)
{
    //Iterate through column names and output headings
    $field=$dresult->FetchField($i);
    $fieldinfo=$field->name;

    if ($fieldinfo == "callattempts")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Number of call attempts")."\"$separator";}
        else {$firstline .= T_("Number of call attempts")."$separator";}
    }
    elseif ($fieldinfo == "messagesleft")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Number of answering machine messages left")."\"$separator";}
        else {$firstline .= T_("Number of answering machine messages left")."$separator";}
    }
    elseif ($fieldinfo == "casenotes")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Case notes")."\"$separator";}
        else {$firstline .= T_("Case notes")."$separator";}
    }
    elseif ($fieldinfo == "interviewtimec")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Total interview time over all calls (mins)")."\"$separator";}
        else {$firstline .= T_("Total interview time over all calls (mins)")."$separator";}
    }
    elseif ($fieldinfo == "interviewtimel")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Interview time for last call (mins)")."\"$separator";}
        else {$firstline .= T_("Interview time for last call (mins)")."$separator";}
    }
    elseif ($fieldinfo =="lastnumber")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Last number dialled")."\"$separator";}
        else {$firstline .= T_("Last number dialled")."$separator";}
    }
    elseif ($fieldinfo == "operatoru")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Operator username for last call")."\"$separator";}
        else {$firstline .= T_("Operator username for last call")."$separator";}
    }
    elseif ($fieldinfo == "shiftr")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Shift report")."\"$separator";}
        else {$firstline .= T_("Shift report")."$separator";}
    }
    elseif ($fieldinfo == "caseid")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Case ID")."\"$separator";}
        else {$firstline .= T_("Case ID")."$separator";}
    }
	elseif ($fieldinfo == "outcomeid")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Outcome ID")."\"$separator";}
        else {$firstline .= T_("Outcome ID")."$separator";}
    }
    elseif ($fieldinfo == "caseoutcome")
    {
        if ($type == "csv") {$firstline .= "\"".T_("Case outcome")."\"$separator";}
        else {$firstline .= T_("Case outcome")."$separator";} 	$caseoutcome_key = $i;
    }
    elseif ($fieldinfo == "email")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("Email address")."\"$separator";}
        else {$firstline .= $elang->gT("Email address")."$separator";}
    }
    elseif ($fieldinfo == "firstname")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("First name")."\"$separator";}
        else {$firstline .= $elang->gT("First name")."$separator";}
    }
    elseif ($fieldinfo == "lastname")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("Last name")."\"$separator";}
        else {$firstline .= $elang->gT("Last name")."$separator";}
    }
    elseif ($fieldinfo == "token")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("Token")."\"$separator";}
        else {$firstline .= $elang->gT("Token")."$separator";}
    }
    elseif (substr($fieldinfo,0,10)=="attribute_")
    {
        if ($type == "csv") {$firstline .= "\"".$attributeFieldAndNames[$fieldinfo] ."\"$separator";}
        else {$firstline .= $attributeFieldAndNames[$fieldinfo]."$separator";}
    }
    elseif ($fieldinfo == "id")
    {
        if ($type == "csv") {$firstline .= "\"id\"$separator";}
        else {$firstline .= "id$separator";}
    }
    elseif ($fieldinfo == "datestamp")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("Date Last Action")."\"$separator";}
        else {$firstline .= $elang->gT("Date Last Action")."$separator";}
    }
    elseif ($fieldinfo == "startdate")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("Date Started")."\"$separator";}
        else {$firstline .= $elang->gT("Date Started")."$separator";}
    }
    elseif ($fieldinfo == "submitdate")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("Completed")."\"$separator";}
        else {$firstline .= $elang->gT("Completed")."$separator";}
    }
    elseif ($fieldinfo == "ipaddr")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("IP address")."\"$separator";}
        else {$firstline .= $elang->gT("IP address")."$separator";}
    }
    elseif ($fieldinfo == "refurl")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("Referrer URL")."\"$separator";}
        else {$firstline .= $elang->gT("Referrer URL")."$separator";}
    }
    elseif ($fieldinfo == "lastpage")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("Last page seen")."\"$separator";}
        else {$firstline .= $elang->gT("Last page seen")."$separator";}
    }
    elseif ($fieldinfo == "startlanguage")
    {
        if ($type == "csv") {$firstline .= "\"".$elang->gT("Start language")."\"$separator";}
        else {$firstline .= $elang->gT("Start language")."$separator";}
    }
    else
    {
        // Prepare the header line
        $fielddata=$fieldmap[$fieldinfo];
        $fqid=$fielddata['qid'];
        $ftype=$fielddata['type'];
        $fsid=$fielddata['sid'];
        $fgid=$fielddata['gid'];
        $faid=$fielddata['aid'];
        switch ($exportstyle)
        {
            case 'headcodes': // only question codes
                $fquest=$fielddata['title'];
                if (!empty($fielddata['aid'])) $fquest .= ' [' . $fielddata['aid'] . ']';
                if (isset($fielddata['scale_id'])) $fquest = $fquest."[{$fielddata['scale_id']}]";
                $fquest=FlattenText($fquest,true);
                break;
            case 'abrev': // Abbreviated question text
                $fquest=FlattenText($fielddata['question']);
                $fquest=mb_substr($fquest, 0, 15);
                if (strlen($fquest)==15) $fquest.='...';
                if (isset($faid)) {$fquest .= " [{$faid}]";}
                if (isset($fielddata['scale_id'])) $fquest = $fquest."[{$fielddata['scale_id']}]";
                break;
            default: // Full question text
                $fquest=$fielddata['question'];
                if (isset($fielddata['scale_id'])) $fquest = "[{$fielddata['scale_id']}] ". $fquest;
                if (isset($fielddata['subquestion'])) $fquest = "[{$fielddata['subquestion']}] ". $fquest;
                if (isset($fielddata['subquestion2'])) $fquest = "[{$fielddata['subquestion2']}] ". $fquest;
                if (isset($fielddata['subquestion1'])) $fquest = "[{$fielddata['subquestion1']}] ". $fquest;
                $fquest=FlattenText($fquest,true);
                break;
        }
         if($convertspacetous == "Y")
        {
            $fquest=str_replace(" ", "_", $fquest);
        }
        if ($type == "csv") {$fquest = "\"$fquest\"";}
        $firstline .= $fquest.$separator;


    }
}

if ($type == "csv") { $firstline = mb_substr(trim($firstline),0,strlen($firstline)-1);}
else
{
    $firstline = trim($firstline);
}

$firstline .= "\n";

if ($type == "doc" || $type == "pdf")
{
    $flarray=explode($separator, $firstline);
    $exportoutput .= '<style>
        table {
            border-collapse:collapse;
        }
        td, th {
            border:solid black 1.0pt;
        }
        th {
            background: #c0c0c0;
        }
        </style>';
}
else
    if ($type == "xls")
    {
    //var_dump ($firstline);
    $flarray=explode($separator, $firstline);
    $fli=0;
    foreach ($flarray as $fl)
    {
        $sheet->write(0,$fli,$fl);
        $fli++;
    }
    //print_r($fieldmap);
    }
    else
    {
    $exportoutput .= $firstline; //Sending the header row
}


//calculate interval because the second argument at SQL "limit"
//is the number of records not the ending point
$from_record = sanitize_int($_POST['export_from']) - 1;
$limit_interval = sanitize_int($_POST['export_to']) - sanitize_int($_POST['export_from']) + 1;
$attributefieldAndNames=array();

//Now dump the data but first add some filters to the select statement
$where = array();
if (incompleteAnsFilterstate() == "filter")
{
    $where[] = "$surveytable.submitdate is not null";
} elseif (incompleteAnsFilterstate() == "inc")
{
    $where[] = "$surveytable.submitdate is null";
}

if (isset($_POST['sql'])) //this applies if export has been called from the statistics package
{
    if ($_POST['sql'] != "NULL")
    {
        $where[] = stripcslashes($_POST['sql']);
    }
}
if (isset($_POST['answerid']) && $_POST['answerid'] != "NULL") //this applies if export has been called from single answer view
{
    $where[] = "$surveytable.id=".stripcslashes($_POST['answerid']);
}
if (count($where)>0) $dquery .= ' WHERE ' . join(' AND ', $where);

$dquery .= " ORDER BY $surveytable.id";

if ($answers == "short") //Nice and easy. Just dump the data straight
{
    //$dresult = db_execute_assoc($dquery);
    $dresult = db_select_limit_assoc($dquery, $limit_interval, $from_record);
    $rowcounter=0;
    while ($drow = $dresult->FetchRow())
    {
        $drow=array_map('strip_tags_full',$drow);		if (isset($drow['caseoutcome']) && !empty($drow['caseoutcome'])) $drow['caseoutcome'] = T_($drow['caseoutcome']);
        if($convertyto1 == "Y")
        //Converts "Y" to "1" in export
        {
            $convertyto=returnglobal('convertyto');
            foreach($drow as $key=>$dr) {
                if (isset($fieldmap[$key]))
                {
                $fielddata=$fieldmap[$key];
                    if (isset($fielddata['type']) && ($fielddata['type'] == "M" || $fielddata['type'] == "P" || $fielddata['type'] == "Y"))
                {
                    if($dr == "Y") {$dr = $convertyto;}
                }
                }
                $line[$key]=$dr;
            }
            $drow=$line;
        }
        if($convertnto2 == "Y")
        //Converts "N" to "2" in export
        {
            $convertnto=returnglobal('convertnto');
            foreach($drow as $key=>$dr) {
                if (isset($fieldmap[$key]))
                {
                $fielddata=$fieldmap[$key];
                    if (isset($fielddata['type']) && ($fielddata['type'] == "M" || $fielddata['type'] == "P" || $fielddata['type'] == "Y"))
                {
                    if($dr == "N") {$dr = $convertnto;}
                }
                }
                $line[$key]=$dr;
            }
            $drow=$line;
        }
        $rowcounter++;
        if ($type == "csv")
        {
            $exportoutput .= "\"".implode("\"$separator\"", str_replace("\"", "\"\"", $drow)) . "\"\n";
        }
        elseif ($type == "xls")
        {
            $colcounter=0;
            foreach ($drow as $rowfield)
            {
                $rowfield=str_replace("?","-",$rowfield);
                // Let's enclose in \" if begins by =
                if (substr($rowfield,0,1) ==  "=")
                {
                    $rowfield = "\"".$rowfield."\"";
                }
                $sheet->write($rowcounter,$colcounter,$rowfield);
                $colcounter++;
            }
        }
        else if($type == "pdf")
        {
            $pdf->titleintopdf($clang->gT("New Record"));
            $pdfstring="";
            foreach ($drow as $rowfield)
            {
                $rowfield=str_replace("?","-",$rowfield);
                $pdfstring .=$rowfield." | ";
            }
                $pdf->intopdf($pdfstring);
        }
        else
        {
            $exportoutput .= implode($separator, str_replace("\r\n", " ", $drow)) . "\n"; //create dump from each row
        }
    }
}
elseif ($answers == "long")        //chose complete answers
{

    $labelscache=array();
    //$dresult = db_execute_num($dquery) or safe_die("ERROR: $dquery -".$connect->ErrorMsg());
    $dresult = db_select_limit_num($dquery, $limit_interval, $from_record);
    $fieldcount = $dresult->FieldCount();
    $rowcounter=0;

    while ($drow = $dresult->FetchRow())
    {
		if (isset($caseoutcome_key) && isset($drow[$caseoutcome_key]) && !empty($drow[$caseoutcome_key])) $drow[$caseoutcome_key] = T_($drow[$caseoutcome_key]);
        $rowcounter++;
        if ($type == "pdf")
        {
            //$pdf->Write (5,$exportoutput);
            if($rowcounter != 1)
            {
                $pdf->AddPage();
            }
            $pdf->Cell(0,10,$elang->gT('NEW RECORD')." ".$rowcounter,1,1);
        }

        if ($type == "doc")
        {
            if ($rowcounter !== 1) $exportoutput .= "<br clear='all' style='page-break-before:always'>";
            $exportoutput .= "<table><tr><th colspan='2'>".$elang->gT('NEW RECORD')."</td></tr>";
        }
        for ($i=0; $i<$fieldcount; $i++) //For each field, work out the QID
        {
            $fqid=0;            // By default fqid is set to zero
            $field=$dresult->FetchField($i);
            $fieldinfo=$field->name;
            if ($fieldinfo != "startlanguage" && $fieldinfo != "id" && $fieldinfo != "datestamp" && $fieldinfo != "startdate" && $fieldinfo != "ipaddr"  && $fieldinfo != "refurl" && $fieldinfo != "token" && $fieldinfo != "firstname" && $fieldinfo != "lastname" && $fieldinfo != "email" && (substr($fieldinfo,0,10)!="attribute_") && $fieldinfo != "completed"  && $fieldinfo != "outcomeid" && $fieldinfo != "caseoutcome"&& $fieldinfo != "caseid" && $fieldinfo != "callattempts" && $fieldinfo != "messagesleft"&& $fieldinfo != "casenotes"&& $fieldinfo != "interviewtimec"&& $fieldinfo != "interviewtimel"&& $fieldinfo != "lastnumber"&& $fieldinfo != "operatoru"&& $fieldinfo != "shiftr")
            {
                $fielddata=$fieldmap[$fieldinfo];
                $fqid=$fielddata['qid'];
                $ftype=$fielddata['type'];
                $fsid=$fielddata['sid'];
                $fgid=$fielddata['gid'];
                $faid=$fielddata['aid'];

                if ($type == "doc" || $type == "pdf")
                {
                    $ftitle=$flarray[$i];
                }
            }
            else
            {
                $fsid=""; $fgid="";
                if ($type == "doc" || $type == "pdf")
                {
                    switch($fieldinfo)
                    {
			case "caseid":
	                    $ftitle=T_("Case ID").":";
                            break;
			case "outcomeid":
	                    $ftitle=T_("Outcome ID").":";
                            break;
			case "caseoutcome":
	                    $ftitle=T_("Case outcome").":";
                            break;
 			case "callattempts":
	                    $ftitle=T_("Number of call attempts").":";
                            break;
 			case "messagesleft":
	                    $ftitle=T_("Number of answering machine messages left").":";
                            break;
  			case "casenotes":
	                    $ftitle=T_("Case notes").":";
                            break;
  			case "interviewtimec":
	                    $ftitle=T_("Total interview time over all calls (mins)").":";
                            break;
  			case "interviewtimel":
	                    $ftitle=T_("Interview time for last call (mins)").":";
                            break;
  			case "lastnumber":
	                    $ftitle=T_("Last number dialled").":";
                            break;
  			case "operatoru":
	                    $ftitle=T_("Operator username for last call").":";
                            break;
   			case "shiftr":
	                    $ftitle=T_("Shift report").":";
                            break;
                        case "datestamp":
                            $ftitle=$elang->gT("Date Last Action").":";
                            break;
                        case "startdate":
                            $ftitle=$elang->gT("Date Started").":";
                            break;
                        case "ipaddr":
                            $ftitle=$elang->gT("IP address").":";
                            break;
                        case "completed":
                            $ftitle=$elang->gT("Completed").":";
                            break;
                        case "refurl":
                            $ftitle=$elang->gT("Referrer URL").":";
                            break;
                        case "firstname":
                            $ftitle=$elang->gT("First name").":";
                            break;
                        case "lastname":
                            $ftitle=$elang->gT("Last name").":";
                            break;
                        case "email":
                            $ftitle=$elang->gT("Email").":";
                            break;
                        case "id":
                            $ftitle=$elang->gT("ID").":";
                            break;
                        case "token":
                            $ftitle=$elang->gT("Token").":";
                            break;
                        case "tid":
                            $ftitle=$elang->gT("Token ID").":";
                            break;
                        case "startlanguage":
                            $ftitle=$elang->gT("Language").":";
                            break;
                        default:
                            if (substr($fieldinfo,0,10)=='attribute_')
                            {
                                $ftitle=$attributeFieldAndNames[$fieldinfo];
                            }
                            else
                            {
                                $fielddata=$fieldmap[$fieldinfo];
                                if (isset($fielddata['title']) && !isset($ftitle)) {$ftitle=$fielddata['title'].":";}
                            }
                    } // switch
                }
            }
            if ($fqid == 0)
            {
                $ftype = "-";  //   This is set if it not a normal answer field, but something like tokenID, First name etc
            }
            if ($type == "csv") {$exportoutput .= "\"";}
            if ($type == "doc") {$exportoutput .= "<td>$ftitle</td><td>";}
            if ($type == "pdf"){ $pdf->intopdf($ftitle);}
            switch ($ftype)
            {
                case "-": //SPECIAL Placeholder TYPE
                    $exportoutput .= $drow[$i];
                    if($type == "pdf"){$pdf->intopdf($drow[$i]);}
                    break;
                case "R": //RANKING TYPE
                    $lq = "SELECT * FROM {$dbprefix}answers WHERE qid=$fqid AND language='$explang' AND code = ?";
                    $lr = db_execute_assoc($lq, array($drow[$i]));
                    while ($lrow = $lr->FetchRow())
                    {
                        $exportoutput .= strip_tags_full($lrow['answer']);
                        if($type == "pdf"){$pdf->intopdf(strip_tags_full($lrow['answer']));}
                    }
                    break;
                case "1":
                    if (mb_substr($fieldinfo,-1) == 0)
                    {
                        //$lq = "select a.*, l.*, l.code as lcode, l.title as ltitle from {$dbprefix}answers as a, {$dbprefix}labels as l where qid=$fqid AND l.lid =$flid AND a.language='$explang' AND l.code = ? group by l.lid";
                        $lq = "select answer as ltitle from {$dbprefix}answers where qid=$fqid AND language='$explang' and scale_id=0 AND code = ?";
                    }
                    else
                    {
                        //$lq = "select a.*, l.*, l.code as lcode, l.title as ltitle from {$dbprefix}answers as a, {$dbprefix}labels as l where qid=$fqid AND l.lid =$flid1 AND a.language='$explang' AND l.code = ? group by l.lid";
                        $lq = "select answer as ltitle from {$dbprefix}answers where qid=$fqid AND language='$explang' and scale_id=1 AND code = ?";
                    }
                    $lr = db_execute_assoc($lq, array($drow[$i])) or safe_die($lq."<br />ERROR:<br />".$connect->ErrorMsg());
                    while ($lrow = $lr->FetchRow())
                    {
                        $exportoutput .= strip_tags_full($lrow['ltitle']);
                        if($type == "pdf"){$pdf->intopdf(strip_tags_full($lrow['ltitle']));}
                    }
                    break;
                case "L": //DROPDOWN LIST
                case "!":
                    if (mb_substr($fieldinfo, -5, 5) == "other")
                    {
                        $exportoutput .= strip_tags_full($drow[$i]);
                        if($type == "pdf"){$pdf->intopdf($drow[$i]);}
                    }
                    else
                    {
                        if ($drow[$i] == "-oth-")
                        {
                            $exportoutput .= $elang->gT("Other");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("Other"));}
                        }
                        else
                        {
                            $lq = "SELECT * FROM {$dbprefix}answers WHERE qid=$fqid AND language='$explang' AND code = ?";
                            $lr = db_execute_assoc($lq, array($drow[$i])) or safe_die($lq."<br />ERROR:<br />".$connect->ErrorMsg());
                            while ($lrow = $lr->FetchRow())
                            {
                                //if ($lrow['code'] == $drow[$i]) {$exportoutput .= $lrow['answer'];}
                                if ($type == "csv")
                                {
                                    $exportoutput .= str_replace("\"", "\"\"", strip_tags_full($lrow['answer']));
                                    if($type == "pdf"){$pdf->intopdf(str_replace("\"", "\"\"", strip_tags_full($lrow['answer'])));}
                                }
                                else
                                {
                                    $exportoutput .= strip_tags_full($lrow['answer']);
                                    if($type == "pdf"){$pdf->intopdf(strip_tags_full($lrow['answer']));}
                                }

                            }
                        }
                    }
                    break;
                case "O": //DROPDOWN LIST WITH COMMENT
                    $lq = "SELECT * FROM {$dbprefix}answers WHERE qid=$fqid AND language='$explang' ORDER BY answer";
                    $lr = db_execute_assoc($lq) or safe_die ("Could do it<br />$lq<br />".$connect->ErrorMsg());
                    $found = "";
                    while ($lrow = $lr->FetchRow())
                    {
                        if ($lrow['code'] == $drow[$i])
                        {
                            $exportoutput .= strip_tags_full($lrow['answer']);
                            $found = "Y";
                            if($type == "pdf"){$pdf->intopdf(strip_tags_full($lrow['answer']));}
                        }
                    }
                    //This following section exports the comment field
                    if ($found != "Y")
                    {
                        if ($type == "csv")
                        {$exportoutput .= str_replace("\r\n", "\n", str_replace("\"", "\"\"", strip_tags_full($drow[$i])));}
                        else if ($type == "pdf")
                        {$pdf->intopdf(str_replace("\r\n", " ", strip_tags_full($drow[$i])));}
                        else
                        {$exportoutput .= str_replace("\r\n", " ", $drow[$i]);}
                    }
                    break;
                case "Y": //YES\NO
                    switch($drow[$i])
                    {
                        case "Y":
                            $exportoutput .= $elang->gT("Yes");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("Yes"));}
                            break;
                        case "N":
                            $exportoutput .= $elang->gT("No");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("No"));}
                            break;
                        default:
                            $exportoutput .= $elang->gT("N/A");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("N/A"));}
                            break;
                    }
                    break;
                case "G": //GENDER
                    switch($drow[$i])
                    {
                        case "M":
                            $exportoutput .= $elang->gT("Male");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("Male"));}
                            break;
                        case "F":
                            $exportoutput .= $elang->gT("Female");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("Female"));}
                            break;
                        default:
                            $exportoutput .= $elang->gT("N/A");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("N/A"));}
                            break;
                    }
                    break;
                case "M": //multioption
                case "P":
                    if (mb_substr($fieldinfo, -5, 5) == "other")
                    {
                        $exportoutput .= strip_tags_full($drow[$i]);
                        if($type == "pdf"){$pdf->intopdf($drow[$i]);}
                    }
                    elseif (mb_substr($fieldinfo, -7, 7) == "comment")
                    {
                        $exportoutput .= strip_tags_full($drow[$i]);
                        if($type == "pdf"){$pdf->intopdf($drow[$i]);}
                    }
                    else
                    {
                        switch($drow[$i])
                        {
                            case "Y":
                                $exportoutput .= $elang->gT("Yes");
                                if($type == "pdf"){$pdf->intopdf($elang->gT("Yes"));}
                                break;
                            case "N":
                                $exportoutput .= $elang->gT("No");
                                if($type == "pdf"){$pdf->intopdf($elang->gT("No"));}
                                break;
                            case "":
                                $exportoutput .= $elang->gT("No");
                                if($type == "pdf"){$pdf->intopdf($elang->gT("No"));}
                                break;
                            default:
                                $exportoutput .= $drow[$i];
                                if($type == "pdf"){$pdf->intopdf($drow[$i]);}
                                break;
                        }
                    }
                    break;
                case "C":
                    switch($drow[$i])
                    {
                        case "Y":
                            $exportoutput .= $elang->gT("Yes");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("Yes")); }
                            break;
                        case "N":
                            $exportoutput .= $elang->gT("No");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("No")); }
                            break;
                        case "U":
                            $exportoutput .= $elang->gT("Uncertain");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("Uncertain"));}
                            break;
                    }
                case "E":
                    switch($drow[$i])
                    {
                        case "I":
                            $exportoutput .= $elang->gT("Increase");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("Increase"));}
                            break;
                        case "S":
                            $exportoutput .= $elang->gT("Same");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("Same"));}
                            break;
                        case "D":
                            $exportoutput .= $elang->gT("Decrease");
                            if($type == "pdf"){$pdf->intopdf($elang->gT("Decrease"));}
                            break;
                    }
                    break;
                case "F":
                case "H":
                    if (!isset($labelscache[$fqid.'|'.$explang.'|'.$drow[$i]]))
                    {
                        $fquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$fqid AND language='$explang' AND scale_id=0 AND code='{$drow[$i]}'";
                        $fresult = db_execute_assoc($fquery) or safe_die("ERROR:".$fquery."\n".$qq."\n".$connect->ErrorMsg());
                        if ($fresult)
                        {
                            $frow=$fresult->FetchRow();
                            if($type == "pdf"){$pdf->intopdf(strip_tags_full($frow['answer']));}
                            $exportoutput .= strip_tags_full($frow['answer']);
                            $labelscache[$fqid.'|'.$explang.'|'.$drow[$i]]=strip_tags_full($frow['answer']);
                        }
                    }
                    else
                    {
                        $exportoutput .=$labelscache[$fqid.'|'.$explang.'|'.$drow[$i]];
                        if($type == "pdf"){$pdf->intopdf($labelscache[$fqid.'|'.$explang.'|'.$drow[$i]]);}
                    }
                    break;
                case "1": //dual scale
                    if (mb_substr($fieldinfo,-1) == '0')
                    {
                        $strlabel = "1";
                        $lq = "select title from {$dbprefix}labels as l where l.lid = $flid AND l.language='$surveybaselang'";
                    }
                    else
                    {
                        $strlabel = "2";
                        $lq = "select title from {$dbprefix}labels as l where l.lid = $flid1 AND l.language='$surveybaselang'";
                    }
                    $lr = db_execute_assoc($lq);
                    while ($lrow=$lr->FetchRow())
                    {
                        $fquest .= " [".strip_tags_full($lrow['title'])."][".strip_tags_full($strlabel).". label]";
                    }

                    break;

                default: $tempresult=$dresult->FetchField($i);
                if ($tempresult->name == "token")
                {
                    $tokenquery = "SELECT firstname, lastname FROM {$dbprefix}tokens_$surveyid WHERE token='$drow[$i]'";
                    if ($tokenresult = db_execute_assoc($tokenquery)) //or safe_die ("Couldn't get token info<br />$tokenquery<br />".$connect->ErrorMsg());
                    while ($tokenrow=$tokenresult->FetchRow())
                    {
                        $exportoutput .= "{$tokenrow['lastname']}, {$tokenrow['firstname']}";
                        if($type == "pdf"){$pdf->intopdf($tokenrow['lastname']." , ".$tokenrow['firstname']);}
                    }
                    else
                    {
                        $exportoutput .= $elang->gT("Tokens problem - token table missing");
                        if($type == "pdf"){$pdf->intopdf($elang->gT("Tokens problem - token table missing"));}
                    }
                }
                else
                {
                    if ($type == "csv")
                    {$exportoutput .= str_replace("\r\n", "\n", str_replace("\"", "\"\"", strip_tags_full($drow[$i])));}
                    else if ($type == "pdf")
                    {$pdf->intopdf(trim(strip_tags($drow[$i])));}
                    else if ($type == "doc")
                    {$exportoutput .= trim(strip_tags($drow[$i]));}
                    else
                    {$exportoutput .= str_replace("\r\n", " ", $drow[$i]);}
                }
            }
            if ($type == "doc")
            {
                $exportoutput .= "</td></tr>";
            }
            if ($type == "csv") {$exportoutput .= "\"";}
            $exportoutput .= "$separator";
            $ftype = "";
        }
        $exportoutput=mb_substr($exportoutput,0,-(strlen($separator)));
        if ($type == "doc")
        {
            $exportoutput .= "</table>";
        }
        if ($type=='xls')
        {
            $rowarray=explode($separator, $exportoutput);
            $fli=0;
            foreach ($rowarray as $row)
            {
                // Let's enclose in \" if begins by =
                if (substr($row,0,1) ==  "=")
                {
                    $row = "\"".$row."\"";
                }
                $sheet->write($rowcounter,$fli,$row);
                $fli++;
            }
            $exportoutput='';
        }
        else {$exportoutput .= "\n";}
    }
}
if ($type=='xls')
{
    //    echo memory_get_peak_usage(true); die();
    $workbook->close();
}
else if($type=='pdf')
    {
    $pdf->Output($clang->gT($surveyname)." ".$surveyid.".pdf","D");
    }
    else
    {
    echo $exportoutput;
}
exit;


function strip_tags_full($string) {
    $string=str_replace('-oth-','',$string);
    return FlattenText($string,true,'UTF-8',false);
}
?>
