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
 * $Id: assessments.php 11664 2011-12-16 05:19:42Z tmswhite $
 */


include_once("login_check.php");
if (!isset($surveyid)) {$surveyid=returnglobal('sid');}
if (!isset($action)) {$action=returnglobal('action');}

$surveyinfo=getSurveyInfo($surveyid);

$js_admin_includes[]= $homeurl.'/scripts/assessments.js';
$js_admin_includes[]='../scripts/jquery/jquery.tablesorter.min.js';
//                          . "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"styles/default/jquery-ui.css\" />\n";

$assessmentlangs = GetAdditionalLanguagesFromSurveyID($surveyid);
$baselang = GetBaseLanguageFromSurveyID($surveyid);
array_unshift($assessmentlangs,$baselang);      // makes an array with ALL the languages supported by the survey -> $assessmentlangs

if (!bHasSurveyPermission($surveyid, 'assessments','read'))
{
    $action = "assessment";
    include("access_denied.php");
    include("admin.php");
    exit;
}


if ($action == "assessmentadd" && bHasSurveyPermission($surveyid, 'assessments','create')) {
    $inserttable=$dbprefix."assessments";
    $first=true;
    foreach ($assessmentlangs as $assessmentlang)
    {
        if (!isset($_POST['gid'])) $_POST['gid']=0;

        $datarray=array(
        'sid' => $surveyid,
        'scope' => $_POST['scope'],
        'gid' => $_POST['gid'],
        'minimum' => $_POST['minimum'],
        'maximum' => $_POST['maximum'],
        'name' => $_POST['name_'.$assessmentlang],
        'language' => $assessmentlang,
        'message' => $_POST['assessmentmessage_'.$assessmentlang]);

        if ($first==false)
        {
            $datarray['id']=$aid;
        }

        $query = $connect->GetInsertSQL($inserttable, $datarray, get_magic_quotes_gpc());
        $result=$connect->Execute($query) or safe_die("Error inserting<br />$query<br />".$connect->ErrorMsg());
        if ($first==true)
        {
            $first=false;
            $aid=$connect->Insert_ID(db_table_name_nq('assessments'),"id");
        }
    }
} elseif ($action == "assessmentupdate" && bHasSurveyPermission($surveyid, 'assessments','update')) {

    if ($filterxsshtml)
    {
        require_once("../classes/inputfilter/class.inputfilter_clean.php");
        $myFilter = new InputFilter('','',1,1,1);
    }

    foreach ($assessmentlangs as $assessmentlang)
    {

        if (!isset($_POST['gid'])) $_POST['gid']=0;
        if ($filterxsshtml)
        {
            $_POST['name_'.$assessmentlang]=$myFilter->process($_POST['name_'.$assessmentlang]);
            $_POST['assessmentmessage_'.$assessmentlang]=$myFilter->process($_POST['assessmentmessage_'.$assessmentlang]);
        }
        $query = "UPDATE {$dbprefix}assessments
			      SET scope='".db_quote($_POST['scope'],true)."',
			      gid=".sanitize_int($_POST['gid']).",
			      minimum='".sanitize_signedint($_POST['minimum'])."',
			      maximum='".sanitize_signedint($_POST['maximum'])."',
			      name='".db_quote($_POST['name_'.$assessmentlang],true)."',
			      message='".db_quote($_POST['assessmentmessage_'.$assessmentlang],true)."'
			      WHERE language='$assessmentlang' and id=".sanitize_int($_POST['id']);
        $result = $connect->Execute($query) or safe_die("Error updating<br />$query<br />".$connect->ErrorMsg());
    }
} elseif ($action == "assessmentdelete" && bHasSurveyPermission($surveyid, 'assessments','delete')) {
    $query = "DELETE FROM {$dbprefix}assessments
			  WHERE id=".sanitize_int($_POST['id']);
    $result=$connect->Execute($query);
}

if (bHasSurveyPermission($surveyid, 'assessments','read'))
{

    $assessmentsoutput=PrepareEditorScript();
    $assessmentsoutput.="<script type=\"text/javascript\">
                        <!--
                            var strnogroup='".$clang->gT("There are no groups available.", "js")."';
                        --></script>\n";
    $assessmentsoutput.="<div class='menubar'>\n"
    . "\t<div class='menubar-title ui-widget-header'>\n"
    . "<strong>".$clang->gT("Assessments")."</strong>\n";

    $assessmentsoutput.= "\t</div>\n"
    . "\t<div class='menubar-main'>\n"
    . "<div class='menubar-left'>\n"
    . "\t<a href=\"#\" onclick=\"window.open('$scriptname?sid=$surveyid', '_self')\" title='".$clang->gTview("Return to survey administration")."'>"
    . "<img name='Administration' src='$imageurl/home.png' alt='".$clang->gT("Return to survey administration")."' /></a>\n"
	. "\t<img src='$imageurl/blank.gif' alt='' width='11'  />\n"
	. "\t<img src='$imageurl/seperator.gif' alt='' />\n";

	if ($surveyinfo['assessments']!='Y')
	{
		$assessmentsoutput.='<span style="font-size:11px;">'.sprintf($clang->gT("Notice: Assessment mode for this survey is not activated. You can activate it in the %s survey settings %s (tab 'Notification & data management')."),'<a href="admin.php?action=editsurvey&amp;sid='.$surveyid.'">','</a>').'</span>';
	}
	$assessmentsoutput.= "</div>\n"
	. "\t</div>\n"
	. "</div>\n";
	$assessmentsoutput .= "<p style='margin:0;font-size:1px;line-height:1px;height:1px;'>&nbsp;</p>"; //CSS Firefox 2 transition fix

	if ($surveyid == "") {
		$assessmentsoutput.= $clang->gT("No SID Provided");
		exit;
	}

	$assessments=getAssessments($surveyid);
	//$assessmentsoutput.= "<pre>";print_r($assessments);echo "</pre>";
	$groups=getGroups($surveyid);
	$groupselect="<select name='gid' id='gid'>\n";
	foreach($groups as $group) {
		$groupselect.="<option value='".$group['gid']."'>".$group['group_name']."</option>\n";
	}
	$groupselect .="</select>\n";
	$headings=array($clang->gT("Scope"), $clang->gT("Question group"), $clang->gT("Minimum"), $clang->gT("Maximum"));
	$actiontitle=$clang->gT("Add");
	$actionvalue="assessmentadd";
	$thisid="";

	if ($action == "assessmentedit" && bHasSurveyPermission($surveyid, 'assessments','update')) {
		$query = "SELECT * FROM {$dbprefix}assessments WHERE id=".sanitize_int($_POST['id'])." and language='$baselang'";
		$results = db_execute_assoc($query);
		while($row=$results->FetchRow()) {
			$editdata=$row;
		}
		$groupselect=str_replace("'".$editdata['gid']."'", "'".$editdata['gid']."' selected", $groupselect);
		$actiontitle=$clang->gT("Edit");
		$actionvalue="assessmentupdate";
		$thisid=$editdata['id'];
	}
	//$assessmentsoutput.= "<pre>"; print_r($edits); $assessmentsoutput.= "</pre>";
	//PRESENT THE PAGE


	$assessmentsoutput.= "<div class='header ui-widget-header'>".$clang->gT("Assessment rules")."</div>"


	."<table class='assessmentlist'><thead>"
	."<tr><th>".$clang->gT("ID")."</th><th>".$clang->gT("Actions")."</th><th>".$clang->gT("SID")."</th>\n";
	foreach ($headings as $head) {
		$assessmentsoutput.= "<th>$head</th>\n";
	}
	$assessmentsoutput.= "<th>".$clang->gT("Title")."</th><th>".$clang->gT("Message")."</th>";
	$assessmentsoutput.= "</tr></thead>\n";
	$flipflop=true;
	foreach($assessments as $assess) {
		$flipflop=!$flipflop;
		$assessmentsoutput.= "<tbody>\n";
		if ($flipflop==true){$assessmentsoutput.= "<tr class='oddrow'>\n";}
		else {$assessmentsoutput.= "<tr class='evenrow'>\n";}
		$assessmentsoutput.= "<td>".$assess['id']."</td>\n";
		$assessmentsoutput.= "<td>";
        if (bHasSurveyPermission($surveyid, 'assessments','update'))
        {
            $assessmentsoutput.="<form method='post' action='$scriptname?sid=$surveyid'>
                <input type='image' src='$imageurl/token_edit.png' alt='".$clang->gT("Edit")."' />
                <input type='hidden' name='action' value='assessmentedit' />
                <input type='hidden' name='id' value='".$assess['id']."' />
                </form>";
        }

        if (bHasSurveyPermission($surveyid, 'assessments','delete'))
        {
            $assessmentsoutput.="<form method='post' action='$scriptname?sid=$surveyid'>
             <input type='image' src='$imageurl/token_delete.png' alt='".$clang->gT("Delete")."' onclick='return confirm(\"".$clang->gT("Are you sure you want to delete this entry?","js")."\")' />
             <input type='hidden' name='action' value='assessmentdelete' />
             <input type='hidden' name='id' value='".$assess['id']."' />
             </form>";
        }
		$assessmentsoutput.= "</td><td>".$assess['sid']."</td>\n";

		if ($assess['scope'] == "T")
		{
			$assessmentsoutput.= "<td>".$clang->gT("Total")."</td>\n";
			$assessmentsoutput.= "<td>-</td>\n";
		}
		else
		{
			$assessmentsoutput.= "<td>".$clang->gT("Question group")."</td>\n";
			$assessmentsoutput.= "<td>".$groups[$assess['gid']]['group_name']." (".$assess['gid'].")</td>\n";
		}


		$assessmentsoutput.= "<td>".$assess['minimum']."</td>\n";
		$assessmentsoutput.= "<td>".$assess['maximum']."</td>\n";
		$assessmentsoutput.= "<td>".stripslashes($assess['name'])."</td>\n";
		$assessmentsoutput.= "<td>".strip_tags(strip_javascript($assess['message']))."</td>\n";

		$assessmentsoutput.= "</tr></tbody>\n";
	}
	$assessmentsoutput.= "</table>";

    if ((bHasSurveyPermission($surveyid, 'assessments','update') && $actionvalue=="assessmentupdate") || (bHasSurveyPermission($surveyid, 'assessments','create')&& $actionvalue=="assessmentadd"))
    {

	    //now present edit/insert form
	    $assessmentsoutput.= "<br /><form method='post' class='form30' id='assessmentsform' name='assessmentsform' action='$scriptname?sid=$surveyid'><div class='header ui-widget-header'>\n";
	    $assessmentsoutput.= "$actiontitle</div>\n";

	    $assessmentsoutput.="<ul>\n"
	    ."<li><label>".$clang->gT("Scope")."</label><input type='radio' id='radiototal' name='scope' value='T' ";
	    if (!isset($editdata) || $editdata['scope'] == "T") {$assessmentsoutput .= " checked='checked' ";}
	    $assessmentsoutput.=" /><label for='radiototal'>".$clang->gT("Total")."</label>
                     <input type='radio' id='radiogroup' name='scope' value='G'";
	    if (isset($editdata) && $editdata['scope'] == "G") {$assessmentsoutput .= " checked='checked' ";}
	    $assessmentsoutput.="/><label for='radiogroup'>".$clang->gT("Group")."</label></li>\n";
	    $assessmentsoutput.="<li><label for='gid'>".$clang->gT("Question group")."</label>$groupselect</li>\n"
	    ."<li><label for='minimum'>".$clang->gT("Minimum")."</label><input type='text' id='minimum' name='minimum' class='numbersonly'";
	    if (isset($editdata)) {$assessmentsoutput .= " value='{$editdata['minimum']}' ";}
	    $assessmentsoutput.="/></li>\n"
	    ."<li><label for='maximum'>".$clang->gT("Maximum")."</label><input type='text' id='maximum' name='maximum' class='numbersonly'";
	    if (isset($editdata)) {$assessmentsoutput .= " value='{$editdata['maximum']}' ";}
	    $assessmentsoutput.="/></li>\n"
	    ."</ul>";

	    // start tabs
	    $assessmentsoutput.= "<div id=\"languagetabs\">"
	    ."<ul>\n";
	    foreach ($assessmentlangs as $assessmentlang)
	    {
		    $position=0;
		    $assessmentsoutput .= '<li><a href="#tablang'.$assessmentlang.'"><span>'.getLanguageNameFromCode($assessmentlang, false);
		    if ($assessmentlang==$baselang) {$assessmentsoutput .= ' ('.$clang->gT("Base language").')';}
		    $assessmentsoutput .="</span></a></li>\n";
	    }
	    $assessmentsoutput.= "</ul>\n";
	    foreach ($assessmentlangs as $assessmentlang)
	    {
		    $heading=''; $message='';
		    if ($action == "assessmentedit")
		    {
			    $query = "SELECT * FROM {$dbprefix}assessments WHERE id=".sanitize_int($_POST['id'])." and language='$assessmentlang'";
			    $results = db_execute_assoc($query);
			    while($row=$results->FetchRow()) {
			        $editdata=$row;
			    }
			    $heading=htmlspecialchars($editdata['name'],ENT_QUOTES);
			    $message=htmlspecialchars($editdata['message']);
		    }
		    $assessmentsoutput .= "<div id=\"tablang".$assessmentlang."\">\n"
			."\t<div class='settingrow'>\n"
		    ."\t\t<span class=\"settingcaption\">".$clang->gT("Heading")."</span>\n"
		    ."\t\t<span class=\"settingentry\"><input type='text' name='name_$assessmentlang' size='80' value='$heading'/></span>\n"
			."\t</div>\n"
			."\t<div class='settingrow'>\n"
		    ."\t\t<span class=\"settingcaption\">".$clang->gT("Message")."</span>\n"
		    ."\t\t<span class=\"settingentry\">\n\t\t\t<textarea name='assessmentmessage_$assessmentlang' id='assessmentmessage_$assessmentlang' rows='10' cols='80'>$message</textarea >\n";
		    $assessmentsoutput.=getEditor("assessment-text","assessmentmessage_$assessmentlang", "[".$clang->gT("Message:", "js")."]",$surveyid,$gid,$qid,$action);
			$assessmentsoutput .="</span>\n\t</div>\n"
			."\t<div style='clear:both'></div>\n"
			."</div>";
	    }
	    $assessmentsoutput .='</div>';

	    $assessmentsoutput.= "<p>\n"
	    ."<input type='submit' value='".$clang->gT("Save")."' />\n";
	    if ($action == "assessmentedit") $assessmentsoutput.= "&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' value='".$clang->gT("Cancel")."' onclick=\"document.assessmentsform.action.value='assessments'\" />\n";
	    $assessmentsoutput.= "<input type='hidden' name='sid' value='$surveyid' />\n"
	    ."<input type='hidden' name='action' value='$actionvalue' />\n"
	    ."<input type='hidden' name='id' value='$thisid' />\n"
	    ."</p>\n"
	    ."</form>\n";
    }
}

function getAssessments($surveyid) {
    global $dbprefix, $connect, $baselang;
    $query = "SELECT id, sid, scope, gid, minimum, maximum, name, message
			  FROM ".db_table_name('assessments')."
			  WHERE sid='$surveyid' and language='$baselang'
			  ORDER BY scope, gid";
    $result=db_execute_assoc($query) or safe_die("Error getting assessments<br />$query<br />".$connect->ErrorMsg());
    $output=array();
    while($row=$result->FetchRow()) {
        $output[]=$row;
    }
    return $output;
}

function getGroups($surveyid) {
    global $dbprefix, $connect;
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    $query = "SELECT gid, group_name
			  FROM ".db_table_name('groups')."
			  WHERE sid='$surveyid' and language='$baselang'
			  ORDER BY group_order";
    $result = db_execute_assoc($query) or safe_die("Error getting groups<br />$query<br />".$connect->ErrorMsg());
    $output=array();
    while($row=$result->FetchRow()) {
        $output[$row['gid']]=$row;
    }
    return $output;
}
?>
