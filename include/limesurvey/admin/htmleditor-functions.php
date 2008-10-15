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
* $Id: htmleditor-functions.php 4916 2008-05-25 17:25:33Z c_schmitz $
*/

//include_once("login_check.php");
//Security Checked: POST/GET/SESSION/DB/returnglobal   

function PrepareEditorPopupScript()
{
	global $clang,$imagefiles,$homeurl;

	$script = "<script type='text/javascript'>\n"
	. "<!--\n"
	. "var editorwindowsHash = new Object();\n"
	. "function find_popup_editor(fieldname)\n"
	. "\t{\t\n"
	. "\t\tvar window = null;\n"
	. "\t\tfor (var key in editorwindowsHash)\n"
	. "\t\t{\n"
	. "\t\t\tif (key==fieldname && !editorwindowsHash[key].closed)\n"
	. "\t\t\t{\n"
	. "\t\t\t\twindow = editorwindowsHash[key];\n"
	. "\t\t\t\treturn window;\n"
	. "\t\t\t}\n"
	. "\t\t}\n"
	. "\treturn null;\n"
	. "\t}\t\n"
	. "\n"
	. "function start_popup_editor(fieldname, fieldtext, sid, gid, qid, fieldtype, action)\n"	
	. "\t{\t\n"
//	. "\t\tcontrolid = fieldname + '_popupctrl';\n"
	. "\t\tcontrolidena = fieldname + '_popupctrlena';\n"
	. "\t\tcontroliddis = fieldname + '_popupctrldis';\n"
	. "\t\tnumwindows = editorwindowsHash.length;\n"
	. "\t\tactivepopup = find_popup_editor(fieldname);\n"
	. "\t\tif (activepopup == null)\n"
	. "\t\t{\n"
	. "\t\t\tdocument.getElementsByName(fieldname)[0].readOnly=true;\n"
	. "\t\t\tdocument.getElementsByName(fieldname)[0].className='readonly';\n"
//	. "\t\t\tdocument.getElementById(controlid).src='".$imagefiles."/edithtmlpopup_disabled.png';\n"
	. "\t\t\tdocument.getElementById(controlidena).style.display='none';\n"
	. "\t\t\tdocument.getElementById(controliddis).style.display='';\n"
	. "\t\t\tpopup = window.open('".$homeurl."/htmleditor-popup.php?fieldname='+fieldname+'&fieldtext='+fieldtext+'&fieldtype='+fieldtype+'&action='+action+'&sid='+sid+'&gid='+gid+'&qid='+qid+'&lang=".$clang->getlangcode()."','', 'location=no, status=yes, scrollbars=auto, menubar=no, resizable=yes, width=600, height=400');\n"
	. "\t\t\teditorwindowsHash[fieldname] = popup;\n"
	. "\t\t}\n"
	. "\t\telse\n"
	. "\t\t{\n"
	. "\t\t\tactivepopup.focus();\n"
	. "\t\t}\n"
	. "\t}\n"
	. "\n"
	. "function updateFCKeditor(fieldname,value)\n" 
	. "{\t\n"
	. "\tvar mypopup= editorwindowsHash[fieldname];\n"
	. "\tif (mypopup)\n"
	. "\t{\n"
	. "\t\tvar oMyEditor = mypopup.FCKeditorAPI.GetInstance('MyTextarea');\n"
	. "\t\tif (oMyEditor) {oMyEditor.SetHTML(value);}\n"
	. "\t\tmypopup.focus();\n"
	. "\t}\n"
	. "}\n"
	. "--></script>\n";

	return $script;
}

function PrepareEditorInlineScript()
{
	global $homeurl, $fckeditordir;
	$script ="<script type=\"text/javascript\" src=\"".$fckeditordir."/fckeditor.js\"></script>\n"
	. "<script type=\"text/javascript\">\n"
	. "<!--\n"
	. "function updateFCKeditor(fieldname,value)\n"
	. "{\n"
	. "\tvar oMyEditor = FCKeditorAPI.GetInstance(fieldname);\n"
	. "\toMyEditor.SetHTML(value);\n"
	. "}\n"
	. "-->\n"
	. "</script>\n";
/*** Commented because of inconsistencies
	$script .= ""
	. "<script type='text/javascript'>\n"
	. "<!--\n"
	."function FCKeditor_OnComplete( editorInstance )\n"
	. "{\n"
	. "\teditorInstance.Events.AttachEvent( 'OnBlur'	, FCKeditor_OnBlur ) ;\n"
	. "\teditorInstance.Events.AttachEvent( 'OnFocus', FCKeditor_OnFocus ) ;\n"
	."}\n"
	. "function FCKeditor_OnBlur( editorInstance )\n"
	. "{\n"
	. "\teditorInstance.ToolbarSet.Collapse() ;\n"
	. "}\n"
	. "function FCKeditor_OnFocus( editorInstance )\n"
	. "{\n"
	."\teditorInstance.ToolbarSet.Expand() ;\n"
	."}\n"
	. "--></script>\n";
***/
	return $script;
}

function PrepareEditorScript($fieldtype=null)
{
	global $defaulthtmleditormode;

	if (isset($_SESSION['htmleditormode']) &&
		$_SESSION['htmleditormode'] == 'none')
	{
		return "<script type=\"text/javascript\">\n"
		. "<!--\n"
		. "function updateFCKeditor(fieldname,value) { return true;}\n"
		. "-->\n"
		. "</script>\n";
	}

	if (!isset($_SESSION['htmleditormode']) ||
		($_SESSION['htmleditormode'] != 'inline' &&
		$_SESSION['htmleditormode'] != 'popup') )
	{
		$htmleditormode = $defaulthtmleditormode;
	}
	else
	{
		$htmleditormode = $_SESSION['htmleditormode'];
	}

	if ($htmleditormode == 'popup' ||
		$fieldtype == 'editanswer' ||
		$fieldtype == 'addanswer' ||
		$fieldtype == 'editlabel' ||
		$fieldtype == 'addlabel')
	{
		return PrepareEditorPopupScript();
	}
	elseif ($htmleditormode == 'inline')
	{
		return PrepareEditorInlineScript();
	}
	else
	{
		return '';
	}
}

function getEditor($fieldtype,$fieldname,$fieldtext, $surveyID=null,$gID=null,$qID=null,$action=null)
{
	global $defaulthtmleditormode;

	if (isset($_SESSION['htmleditormode']) &&
		$_SESSION['htmleditormode'] == 'none')
	{
		return '';
	}


	if (!isset($_SESSION['htmleditormode']) ||
		($_SESSION['htmleditormode'] != 'inline' &&
		$_SESSION['htmleditormode'] != 'popup') )
	{
		$htmleditormode = $defaulthtmleditormode;
	}
	else
	{
		$htmleditormode = $_SESSION['htmleditormode'];
	}

	if ( ($fieldtype == 'email-inv' ||
		$fieldtype == 'email-reg' ||
		$fieldtype == 'email-conf' ||
		$fieldtype == 'email-rem' ) &&
	      getEmailFormat($surveyID) != 'html')
	{
		return '';
	}

	if ($htmleditormode == 'popup' ||
		$fieldtype == 'editanswer' ||
		$fieldtype == 'addanswer' ||
		$fieldtype == 'editlabel' ||
		$fieldtype == 'addlabel')
	{
		return getPopupEditor($fieldtype,$fieldname,$fieldtext, $surveyID,$gID,$qID,$action);
	}
	elseif ($htmleditormode == 'inline')
	{
		return getInlineEditor($fieldtype,$fieldname,$fieldtext, $surveyID,$gID,$qID,$action);
	}
	else
	{
		return '';
	}
}

function getPopupEditor($fieldtype,$fieldname,$fieldtext, $surveyID=null,$gID=null,$qID=null,$action=null)
{
	global $clang, $imagefiles, $homeurl;

	$htmlcode = '';
	$imgopts = '';
	$toolbarname = 'Basic';

	if ($fieldtype == 'editanswer' || 
		$fieldtype == 'addanswer' ||
		$fieldtype == 'editlabel' ||
		 $fieldtype == 'addlabel')
	{
		$imgopts = "width='14px' height='14px'";
	}

	$htmlcode .= ""
	. "<a href=\"javascript:start_popup_editor('".$fieldname."','".$fieldtext."','".$surveyID."','".$gID."','".$qID."','".$fieldtype."','".$action."')\" id='".$fieldname."_ctrl' title=\"".$clang->gTview("Start HTML Editor in a Popup Window")."\"><img alt=\"".$clang->gT("Start HTML Editor in a Popup Window")."\" id='".$fieldname."_popupctrlena' name='".$fieldname."_popupctrlena' border='0' src='".$imagefiles."/edithtmlpopup.png'  $imgopts /><img alt=\"".$clang->gT("Give focus to the HTML Editor Popup Window")."\" id='".$fieldname."_popupctrldis' name='".$fieldname."_popupctrldis' border='0' src='".$imagefiles."/edithtmlpopup_disabled.png' style='display: none'  $imgopts align='top'/></a>";

	return $htmlcode;
}

function getInlineEditor($fieldtype,$fieldname,$fieldtext, $surveyID=null,$gID=null,$qID=null,$action=null)
{
	global $clang, $imagefiles, $homeurl, $rooturl, $fckeditordir;

	$htmlcode = '';
	$imgopts = '';
	$toolbarname = 'Basic';
	$toolbaroption="";
	$htmlformatoption="";

	if ($fieldtype == 'editanswer' || 
		$fieldtype == 'addanswer' ||
		$fieldtype == 'editlabel' ||
		 $fieldtype == 'addlabel')
	{
		$toolbarname = 'LimeSurveyToolbarfull';
		$toolbaroption="oFCKeditor_$fieldname.Config[\"ToolbarLocation\"]=\"Out:xToolbar\";\n"
		. "oFCKeditor_$fieldname.Config[\"ToolbarStartExpanded\"]=true;\n"
		. "oFCKeditor_$fieldname.Config[\"ToolbarCanCollapse\"]=false;\n"
		. "oFCKeditor_$fieldname.Height = \"50\"\n";
	}

	if ( $fieldtype == 'email-inv' ||
		$fieldtype == 'email-reg' ||
		$fieldtype == 'email-conf' ||
		$fieldtype == 'email-rem' ) 
	{
		$htmlformatoption = "oFCKeditor_$fieldname.Config[\"FullPage\"]=true;\n";
	}

	$htmlcode .= ""
	. "<script type=\"text/javascript\">\n"
	. "var oFCKeditor_$fieldname = new FCKeditor('$fieldname');\n"
	. "oFCKeditor_$fieldname.BasePath     = '".$fckeditordir."/';\n"
	. "oFCKeditor_$fieldname.Config[\"CustomConfigurationsPath\"] = \"".$fckeditordir."/limesurvey-config.js\";\n"
	. "oFCKeditor_$fieldname.Config[\"LimeReplacementFieldsType\"] = \"".$fieldtype."\";\n"
	. "oFCKeditor_$fieldname.Config[\"LimeReplacementFieldsSID\"] = \"".$surveyID."\";\n"
	. "oFCKeditor_$fieldname.Config[\"LimeReplacementFieldsGID\"] = \"".$gID."\";\n"
	. "oFCKeditor_$fieldname.Config[\"LimeReplacementFieldsQID\"] = \"".$qID."\";\n"
	. "oFCKeditor_$fieldname.Config[\"LimeReplacementFieldsType\"] = \"".$fieldtype."\";\n"
	. "oFCKeditor_$fieldname.Config[\"LimeReplacementFieldsAction\"] = \"".$action."\";\n"
	. "oFCKeditor_$fieldname.Config[\"SmileyPath\"] = \"".$rooturl."/upload/images/smiley/msn/\";\n"
	. $htmlformatoption
	. $toolbaroption; 

	if ($fieldtype == 'answer' || $fieldtype == 'label')
	{
		 $htmlcode .= ""
		. "oFCKeditor_$fieldname.Config[ 'ToolbarLocation' ] = 'Out:xToolbar' ;\n";
	}

	 $htmlcode .= ""
	. "oFCKeditor_$fieldname.ToolbarSet = '".$toolbarname."';\n"
	. "oFCKeditor_$fieldname.ReplaceTextarea() ;\n"
	. '</script>';

	return $htmlcode;
}

?>
