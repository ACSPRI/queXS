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

require_once(dirname(__FILE__).'/../classes/core/startup.php');

require_once(dirname(__FILE__).'/../config-defaults.php');
require_once(dirname(__FILE__).'/../common.php');
require_once('login_check.php');

if (!isset($_SESSION['loginID'])) die();

if (!isset($_GET['lang']))
{
    $clang = new limesurvey_lang("en");
}
else
{
    $clang = new limesurvey_lang($_GET['lang']);
}


if (!isset($_GET['fieldname']) || !isset($_GET['fieldtext']))
{
    $output = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN">
<html>
<head>
	<title>LimeSurvey '.$clang->gT("HTML Editor").'</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />
</head>'

    . '
	<body>
		<div class="maintitle">
			LimeSurvey '.$clang->gT("HTML Editor").'
		</div>
		<hr />

		<tr><td align="center"><br /><span style="color:red;"><strong>
		</strong></span><br />
		</table>
		<form  onsubmit="self.close()">
			<input type="submit" value="'.$clang->gT("Close Editor").'" />
			<input type="hidden" name="checksessionbypost" value="'.$_SESSION['checksessionpost'].'" />
		</form>
	</body>
	</html>';
}
else {
    require_once("../classes/inputfilter/class.inputfilter_clean.php");
    $oFilter = new InputFilter('','',1,1,1);

    $fieldname=$oFilter->process($_GET['fieldname']);
    $fieldtext=$oFilter->process($_GET['fieldtext']);
    if (get_magic_quotes_gpc()) $fieldtext = stripslashes($fieldtext);
    $controlidena=$_GET['fieldname'].'_popupctrlena';
    $controliddis=$_GET['fieldname'].'_popupctrldis';

    $sid=sanitize_int($_GET['sid']);
    $gid=sanitize_int($_GET['gid']);
    $qid=sanitize_int($_GET['qid']);
    $fieldtype=preg_replace("/[^_.a-zA-Z0-9-]/", "",$_GET['fieldtype']);
    $action=preg_replace("/[^_.a-zA-Z0-9-]/", "",$_GET['action']);

    $toolbarname='popup';
    $htmlformatoption='';

    if ( $fieldtype == 'email-inv' ||
    $fieldtype == 'email-reg' ||
    $fieldtype == 'email-conf' ||
    $fieldtype == 'email-rem' )
    {
        $htmlformatoption = ",fullPage:true";
    }

    $output = '
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN">
	<html>
	<head>
		<title>'.sprintf($clang->gT("Editing %s"), $fieldtext).'</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="robots" content="noindex, nofollow" />
        <script type="text/javascript" src="'.$rooturl.'/scripts/jquery/jquery.js"></script>
		<script type="text/javascript" src="'.$sCKEditorURL.'/ckeditor.js"></script>
	</head>';


    $output .= "
	<body>
	<form method='post' onsubmit='saveChanges=true;'>

			<input type='hidden' name='checksessionbypost' value='".$_SESSION['checksessionpost']."' />
			<script type='text/javascript'>
	<!--
	function closeme()
	{
		window.onbeforeunload = new Function('var a = 1;');
		self.close();
	}

	window.onbeforeunload= function (evt) {
		close_editor();
		closeme();
	}


	var saveChanges = false;
    $(document).ready(function(){
        CKEDITOR.on('instanceReady',CKeditor_OnComplete);
    	var oCKeditor = CKEDITOR.replace( 'MyTextarea' ,  { height	: '350',
    	                                            width	: '98%',
    	                                            customConfig : \"".$sCKEditorURL."/limesurvey-config.js\",
                                                    toolbarStartupExpanded : true,
                                                    ToolbarCanCollapse : false,
                                                    toolbar : '".$toolbarname."',
                                                    LimeReplacementFieldsSID : \"".$sid."\",
                                                    LimeReplacementFieldsGID : \"".$gid."\",
                                                    LimeReplacementFieldsQID : \"".$qid."\",
                                                    LimeReplacementFieldsType: \"".$fieldtype."\",
                                                    LimeReplacementFieldsAction: \"".$action."\",
                                                    smiley_path: \"".$rooturl."/upload/images/smiley/msn/\"
                                                    {$htmlformatoption} });
    });

	function CKeditor_OnComplete( evt )
	{
        var editor = evt.editor;
        editor.setData(window.opener.document.getElementsByName(\"".$fieldname."\")[0].value);
        editor.execCommand('maximize');
		window.status='LimeSurvey ".$clang->gT("Editing", "js")." ".javascript_escape($fieldtext,true)."';
	}

	function html_transfert()
	{
		var oEditor = CKEDITOR.instances['MyTextarea'];\n";

	if ($fieldtype == 'editanswer' ||
	$fieldtype == 'addanswer' ||
	$fieldtype == 'editlabel' ||
	$fieldtype == 'addlabel')
	{
	    $output .= "\t\tvar editedtext = oEditor.getData().replace(new RegExp( \"\\n\", \"g\" ),'');\n";
	    $output .= "\t\tvar editedtext = oEditor.getData().replace(new RegExp( \"\\r\", \"g\" ),'');\n";
	}
	else
	{
	    //$output .= "\t\tvar editedtext = oEditor.GetXHTML();\n";
	    $output .= "\t\tvar editedtext = oEditor.getData('no strip new line');\n"; // adding a parameter avoids stripping \n
	}



	$output .=	"

		window.opener.document.getElementsByName('".$fieldname."')[0].value = editedtext;
	}


	function close_editor()
	{
				html_transfert();

		window.opener.document.getElementsByName('".$fieldname."')[0].readOnly= false;
		window.opener.document.getElementsByName('".$fieldname."')[0].className='htmlinput';
		window.opener.document.getElementById('".$controlidena."').style.display='';
		window.opener.document.getElementById('".$controliddis."').style.display='none';
		window.opener.focus();
		return true;
	}

	//-->
			</script>";

	$output .= "<textarea id='MyTextarea' name='MyTextarea'></textarea>";
	$output .= "
	</form>
	</body>
	</html>";
}

echo $output;

// Yes, closing PHP tag was intentionally left out