<?
/**
 * Create a queXS questionnaire and link it to a LimeSurvey questionnaire
 *
 *
 *	This file is part of queXS
 *	
 *	queXS is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *	
 *	queXS is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *	
 *	You should have received a copy of the GNU General Public License
 *	along with queXS; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *
 * @author Adam Zammit <adam.zammit@deakin.edu.au>
 * @copyright Deakin University 2007,2008
 * @package queXS
 * @subpackage admin
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 *
 *
 * @todo Create from queXML
 * 
 */

/**
 * Configuration file
 */
include ("../config.inc.php");

/**
 * Database file
 */
include ("../db.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

global $ldb;
global $db;

xhtml_head(T_("New: Create new questionnaire"),true,false,array("../js/new.js"));

if (isset($_POST['import_file']))
{
	//file has been submitted
	global $db;	

	$ras =0;
	$rws = 0;
	$testing = 0;
	$rs = 0;
	$lime_sid = 0;
	if (isset($_POST['ras'])) $ras = 1;
	if (isset($_POST['rws'])) $rws = 1;
	if (isset($_POST['testing'])) $testing = 1;
	if (isset($_POST['rs'])) $rs = 1;
	
	$name = $db->qstr($_POST['description'],get_magic_quotes_gpc());
	$rs_intro = $db->qstr($_POST['rs_intro'],get_magic_quotes_gpc());
	$rs_project_intro = $db->qstr($_POST['rs_project_intro'],get_magic_quotes_gpc());
	$rs_project_end = $db->qstr($_POST['rs_project_end'],get_magic_quotes_gpc());
	$rs_callback = $db->qstr($_POST['rs_callback'],get_magic_quotes_gpc());
	$rs_answeringmachine = $db->qstr($_POST['rs_answeringmachine'],get_magic_quotes_gpc());

	if ($_POST['select'] == "new")
	{
		//create one from scratch
		include("../functions/functions.limesurvey.php");

		$lime_sid = create_limesurvey_questionnaire($name);
		

	}else if ($_POST['select'] == "quexml")
	{
		//create from queXML

	}else
	{
		//use existing lime instrument
		$lime_sid = bigintval($_POST['select']);

	}

	$sql = "INSERT INTO questionnaire (questionnaire_id,description,lime_sid,restrict_appointments_shifts,restrict_work_shifts,respondent_selection,rs_intro,rs_project_intro,rs_project_end,rs_callback,rs_answeringmachine,testing)
		VALUES (NULL,$name,'$lime_sid','$ras','$rws','$rs',$rs_intro,$rs_project_intro,$rs_project_end,$rs_callback,$rs_answeringmachine,'$testing')";

	$rs = $db->Execute($sql);

	if ($rs)
	{
		$qid = $db->Insert_ID();
		print "<p>Successfully inserted $name as questionnaire $qid, linked to $lime_sid</p>";
	}else
	{
		print "<p>Error: Failed to insert questionnaire</p>";
	}

	
}


//create new questionnaire
?>
	<form enctype="multipart/form-data" action="" method="post">
	<p><input type="hidden" name="MAX_FILE_SIZE" value="1000000000" /></p>
	<p><? echo T_("Name for questionnaire:"); ?> <input type="text" name="description"/></p>
	<p><? echo T_("Select creation type:"); ?> <select name="select"><option value="quexml"><? echo T_("Create from queXML"); ?></option><option value="new"><? echo T_("Create new questionnaire in Limesurvey"); ?></option><?
$sql = "SELECT s.sid as sid, sl.surveyls_title AS title
	FROM " . LIME_PREFIX . "surveys AS s
	LEFT JOIN " . LIME_PREFIX . "surveys_languagesettings AS sl ON ( s.sid = sl.surveyls_survey_id
	AND sl.surveyls_language = 'en' )
	WHERE s.active = 'Y'";

$surveys = $ldb->GetAll($sql);
foreach($surveys as $s)
{
	print "<option value=\"{$s['sid']}\">" . T_("Existing questionnaire:") . " {$s['title']}</option>";
}

?></select></p>
<p><? echo T_("Choose the queXML file (if required):"); ?> <input name="file" type="file" /></p>
<p><? echo T_("Restrict appointments to shifts?"); ?> <input name="ras" type="checkbox" checked="checked"/></p>
<p><? echo T_("Restrict work to shifts?"); ?> <input name="rws" type="checkbox" checked="checked"/></p>
<p><? echo T_("Questionnaire for testing only?"); ?> <input name="testing" type="checkbox"/></p>
<p><? echo T_("Use respondent selection text?"); ?> <input name="rs" id="rs" type="checkbox" checked="checked" onclick="showHide(this,'rstext');"/></p>
<div id='rstext'>
<p><? echo T_("Respondent selection introduction:"); ?> <textarea cols="40" rows="4" name="rs_intro"></textarea></p>
<p><? echo T_("Respondent selection project introduction:"); ?> <textarea cols="40" rows="4" name="rs_project_intro"></textarea></p>
<p><? echo T_("Respondent selection project end:"); ?> <textarea cols="40" rows="4" name="rs_project_end"></textarea></p>
<p><? echo T_("Respondent selection callback (already started questionnaire):"); ?> <textarea cols="40" rows="4" name="rs_callback"></textarea></p>
<p><? echo T_("Message to leave on an answering machine:"); ?> <textarea cols="40" rows="4" name="rs_answeringmachine"></textarea></p>
</div>
<p><input type="submit" name="import_file" value="<? echo T_("Create Questionnaire"); ?>"/></p>
</form>
<?
xhtml_foot();


?>
