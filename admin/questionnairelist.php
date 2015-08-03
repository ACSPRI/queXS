<?php 
/**
 * Manage questionnaires by editing them or disabling/enabling them
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
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2011
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 *
 */

/**
 * Configuration file
 */
include("../config.inc.php");

/**
 * Database file
 */
include("../db.inc.php");

/**
 * XHTML functions
 */
include("../functions/functions.xhtml.php");

/**
 * Input functions
 */
include("../functions/functions.display.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

/**
 * CKEditor
 */
include("../functions/functions.limesurvey.php");

/**
 * Operator functions
 */
include("../functions/functions.operator.php");

/**
 * CKEditor
 */
include("../include/ckeditor/ckeditor.php");

global $db;

$css = array(
"../include/bootstrap/css/bootstrap.min.css", 
"../include/bootstrap/css/bootstrap-theme.min.css",
"../include/font-awesome/css/font-awesome.css",
"../include/bootstrap-toggle/css/bootstrap-toggle.min.css",
"../css/custom.css"
			);
$js_head = array(
"../include/jquery/jquery.min.js",
"../include/bootstrap/js/bootstrap.min.js"
				);
$js_foot = array(
"../js/new.js",
"../include/bootstrap-toggle/js/bootstrap-toggle.min.js",
"../js/bootstrap-confirmation.js",
"../js/custom.js"
				);

if (isset($_POST['questionnaire_id']) && isset($_POST['submit']))
{
	//Delete the questionnaire

	$questionnaire_id = intval($_POST['questionnaire_id']);

	$db->StartTrans();

	$sql = "DELETE FROM `appointment`
		WHERE case_id IN 
			(SELECT case_id 
			FROM `case` 
			WHERE questionnaire_id = $questionnaire_id)";

	$db->Execute($sql);

	$sql = "DELETE FROM `call`
		WHERE case_id IN 
			(SELECT case_id 
			FROM `case` 
			WHERE questionnaire_id = $questionnaire_id)";

	$db->Execute($sql);


	$sql = "DELETE FROM `call_attempt`
		WHERE case_id IN 
			(SELECT case_id 
			FROM `case` 
			WHERE questionnaire_id = $questionnaire_id)";

	$db->Execute($sql);


	$sql = "DELETE FROM `case_availability`
		WHERE case_id IN 
			(SELECT case_id 
			FROM `case` 
			WHERE questionnaire_id = $questionnaire_id)";

	$db->Execute($sql);

	$sql = "DELETE FROM `case_note`
		WHERE case_id IN 
			(SELECT case_id 
			FROM `case` 
			WHERE questionnaire_id = $questionnaire_id)";

	$db->Execute($sql);

	$sql = "DELETE FROM `contact_phone`
		WHERE case_id IN 
			(SELECT case_id 
			FROM `case` 
			WHERE questionnaire_id = $questionnaire_id)";

	$db->Execute($sql);

	$sql = "DELETE FROM `respondent`
		WHERE case_id IN 
			(SELECT case_id 
			FROM `case` 
			WHERE questionnaire_id = $questionnaire_id)";

	$db->Execute($sql);

	$sql = "DELETE FROM `client_questionnaire`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$sql = "DELETE FROM `operator_questionnaire`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$sql = "DELETE FROM `questionnaire_availability`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$sql = "DELETE FROM `questionnaire_prefill`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$sql = "DELETE FROM `questionnaire_sample`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$sql = "DELETE FROM `questionnaire_sample_exclude_priority`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$sql = "DELETE FROM `questionnaire_sample_quota`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

  $sql = "DELETE FROM qsqr_question
    WHERE questionnaire_sample_quota_row_id IN (
      SELECT questionnaire_sample_quota_row_id
      FROM questionnaire_sample_quota_row
      WHERE questionnaire_id = $questionnaire_id)";

  $db->Execute($sql);

  $sql = "DELETE FROM qsqr_sample
    WHERE questionnaire_sample_quota_row_id IN (
      SELECT questionnaire_sample_quota_row_id
      FROM questionnaire_sample_quota_row
      WHERE questionnaire_id = $questionnaire_id)";

  $db->Execute($sql);

	$sql = "DELETE FROM `questionnaire_sample_quota_row`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$sql = "DELETE FROM `questionnaire_sample_quota_row_exclude`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$sql = "DELETE FROM `shift_report`
		WHERE shift_id IN
			(SELECT shift_id
			FROM `shift`
			WHERE questionnaire_id = $questionnaire_id)";

	$db->Execute($sql);

	$sql = "DELETE FROM `shift`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$sql = "DELETE FROM `case`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$sql = "DELETE FROM `questionnaire`
		WHERE questionnaire_id = $questionnaire_id";

	$db->Execute($sql);

	$db->CompleteTrans();
}

if (isset($_GET['disable']))
{
	$questionnaire_id = intval($_GET['disable']);

	$sql = "UPDATE questionnaire
		SET enabled = 0
		WHERE questionnaire_id = '$questionnaire_id'";

	$db->Execute($sql);	
}

if (isset($_GET['enable']))
{
	$questionnaire_id = intval($_GET['enable']);

	$sql = "UPDATE questionnaire
		SET enabled = 1
		WHERE questionnaire_id = '$questionnaire_id'";

	$db->Execute($sql);	
}

if (isset($_POST['update']) && isset($_GET['modify']))
{
	$questionnaire_id = intval($_GET['modify']);

	$ras =0;
	$rws = 0;
	$rs = 0;
	$respsc = 0;
	$referral = 0;
	if (isset($_POST['ras'])) $ras = 1;
	if (isset($_POST['rws'])) $rws = 1;
	if (isset($_POST['respsc'])) $respsc = 1;
	if (isset($_POST['referral'])) $referral = 1;

	$name = $db->qstr(html_entity_decode($_POST['description'],ENT_QUOTES,'UTF-8'));
	if (isset($_POST['rs_intro']))
	{
		$rs = 1;
		$rs_intro = $db->qstr(html_entity_decode($_POST['rs_intro'],ENT_QUOTES,'UTF-8'));
		$rs_project_intro = $db->qstr(html_entity_decode($_POST['rs_project_intro'],ENT_QUOTES,'UTF-8'));
		$rs_callback = $db->qstr(html_entity_decode($_POST['rs_callback'],ENT_QUOTES,'UTF-8'));
		$rs_answeringmachine = $db->qstr(html_entity_decode($_POST['rs_answeringmachine'],ENT_QUOTES,'UTF-8'));
	}
	$info  = $db->qstr(html_entity_decode($_POST['info'],ENT_QUOTES,'UTF-8'));
	$rs_project_end = $db->qstr(html_entity_decode($_POST['rs_project_end'],ENT_QUOTES,'UTF-8'));

	$sql = "UPDATE questionnaire
		SET description = $name, info = $info, rs_project_end = $rs_project_end, restrict_appointments_shifts = '$ras', restrict_work_shifts = '$rws', self_complete = $respsc, referral = $referral
		WHERE questionnaire_id = '$questionnaire_id'";

	$db->Execute($sql);

	if ($rs)
	{
		$sql = "UPDATE questionnaire
			SET rs_intro = $rs_intro, rs_project_intro = $rs_project_intro, rs_callback =  $rs_callback, rs_answeringmachine = $rs_answeringmachine
			WHERE questionnaire_id = '$questionnaire_id'";
		$db->Execute($sql);
	}

	if ($respsc == 1)
	{
		$lime_mode = $db->qstr($_POST['lime_mode'],get_magic_quotes_gpc());
		$lime_template = $db->qstr($_POST['lime_template'],get_magic_quotes_gpc());
		$lime_endurl = $db->qstr($_POST['lime_endurl'],get_magic_quotes_gpc());

		$sql = "UPDATE questionnaire
			SET lime_mode = $lime_mode, lime_template = $lime_template, lime_endurl = $lime_endurl
			WHERE questionnaire_id = $questionnaire_id";
		$db->Execute($sql);
	}
}

if (isset($_GET['modify']))
{
	$questionnaire_id = intval($_GET['modify']);

	$sql = "SELECT *
		FROM questionnaire
		WHERE questionnaire_id = $questionnaire_id";
	$rs = $db->GetRow($sql);

	$referral = $testing = $rws = $ras = $rsc = "checked=\"checked\"";
	$rscd = "";	

	$aio = $qbq = $gat = "";
	if ($rs['lime_mode'] == "survey") $aio = "selected=\"selected\"";
	if ($rs['lime_mode'] == "question") $qbq = "selected=\"selected\"";
	if ($rs['lime_mode'] == "group") $gat = "selected=\"selected\"";

	if ($rs['restrict_appointments_shifts'] != 1) $ras = "";
	if ($rs['restrict_work_shifts'] != 1) $rws = "";
	if ($rs['testing'] != 1) $testing = "";
	if ($rs['referral'] != 1) $referral = "";
	if ($rs['self_complete'] == 0)
	{
		$rsc = "";
		$rscd = "style='display:none;'";
	}

	xhtml_head(T_("Modify Questionnaire "),true,$css,$js_head, false, false, false, " &ensp;<span class=' text-uppercase'>" . "$rs[description]" . "</span>");
	
	$CKEditor = new CKEditor();
	$CKEditor->basePath = "../include/ckeditor/";

	$ckeditorConfig = array("toolbar" => array(array("tokens","-","Source"),
	array("Cut","Copy","Paste","PasteText","PasteFromWord","-","Print","SpellChecker"),
	array("Undo","Redo","-","Find","Replace","-","SelectAll","RemoveFormat"),
	array('Link','Unlink','Anchor'),
	array('Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'),
	array('About'),
	"/",
	array("Bold","Italic","Underline","Strike","-","Subscript","Superscript"),
	array("NumberedList","BulletedList","-","Outdent","Indent","Blockquote"),
	array('JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'),
	array('BidiLtr', 'BidiRtl'),
	array('Styles','Format','Font','FontSize'),
	array('TextColor','BGColor')),
	"extraPlugins" => "tokens");
?>
	<div class="form-group">
		<div class="col-sm-2"><a href='questionnairelist.php' class='btn btn-default pull-left' ><i class='fa fa-chevron-left fa-lg' style='color:blue;'></i>&emsp;<?php  echo T_("Go back"); ?></a></div>
		<div class="col-sm-8"><?php // ?> </div>
		<div class="col-sm-2"><?php echo "<a class='btn btn-default btn-lime pull-right' href='" . LIME_URL . "admin/admin.php?sid={$rs['lime_sid']}'><i class='fa fa-edit' style='color:blue;'></i>&emsp;" . T_("Edit instrument in Limesurvey") . "&emsp;</a>"; ?> </div>
	</div>

<form action="?modify=<?php  echo $questionnaire_id; ?>" method="post" class="form-horizontal col-sm-12 form-group ">
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Edit"),"&ensp;", T_("Name for questionnaire:"); ?> </label>
		<div class="col-sm-4"><input type="text" name="description" class="form-control" required value="<?php  echo $rs['description']; ?>" label="<?php   echo T_("Name for questionnaire:") ; ?> "/></div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Restrict appointments to shifts?"); ?> </label>
		<div class="col-sm-4" style="height: 30px;"><input  name="ras" type="checkbox" <?php  echo $ras; ?> data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-width="80"/> </div>
	</div>
	<div class="form-group"><label class="col-sm-4 control-label" ><?php  echo T_("Restrict work to shifts?"); ?> </label>
		<div class="col-sm-4" style="height: 30px;" ><input name="rws" type="checkbox" <?php  echo $rws; ?> data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-width="80"/></div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Questionnaire for testing only?"); ?> </label>
		<div class="col-sm-4" style="height: 30px;" ><input name="testing" type="checkbox" disabled="true" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" <?php  echo $testing; ?> data-onstyle="danger" data-width="80"/></div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Allow operators to generate referrals?"); ?></label>
		<div class="col-sm-4" style="height: 30px;"> <input name="referral" type="checkbox"  <?php  echo $referral; ?> data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-width="80"/></div>
	</div>
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Allow for respondent self completion via email invitation?"); ?> </label>
		<div class="col-sm-4" style="height: 30px;"><input name="respsc" id="respsc" type="checkbox" <?php echo $rsc ?> onchange="if(this.checked==true) {show(this,'limesc'); $('#url').attr('required','required');} else{ hide(this,'limesc'); $('#url').removeAttr('required');}" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-width="80"/></div>
	</div>
<div id="limesc" <?php echo $rscd; ?> >
<div class="form-group">
	<label class="col-sm-4 control-label" ><?php echo T_("Questionnaire display mode for respondent");?>: </label>
	<div class="col-sm-4">
		<select class="form-control"  name="lime_mode">
			<option <?php echo $aio;?> value="survey"><?php echo T_("All in one"); ?></option>
			<option <?php echo $qbq;?> value="question"><?php echo T_("Question by question"); ?></option>
			<option <?php echo $gat;?> value="group"><?php echo T_("Group at a time"); ?></option>
		</select>
	</div>
</div>
<div class="form-group">
	<label class="col-sm-4 control-label" ><?php echo T_("Limesurvey template for respondent");?>: </label>
	<div class="col-sm-4">
		<select class="form-control"  name="lime_template">
<?php 
	if ($handle = opendir(dirname(__FILE__)."/../include/limesurvey/templates")) {
	    while (false !== ($entry = readdir($handle))) {
	        if ($entry != "." && $entry != ".." && is_dir(dirname(__FILE__)."/../include/limesurvey/templates/" . $entry)){
	            echo "<option value=\"$entry\" ";
		    if ($rs['lime_template'] == $entry) echo " selected=\"selected\" ";
		    echo ">$entry</option>";
	        }
	    }
	    closedir($handle);
	}
?>
		</select>
	</div>
</div>
	<div class="form-group">
		<label class="col-sm-4 control-label text-danger" ><?php echo T_("URL to forward respondents on self completion (required)");?>: </label>
		<div class="col-sm-4">
			<input class="form-control" name="lime_endurl" id="url" type="url" value="<?php echo $rs['lime_endurl']; ?>"/>
		</div>
	</div>
</div>
<?php 
if ($rs['respondent_selection'] == 1 && empty($rs['lime_rs_sid'])) { ?>
		
	<div class="panel  panel-default" >
		<div class="panel-heading">
			<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
			<h3 class="panel-title text-primary "><?php  echo T_("Respondent selection introduction:");?></h3>
		</div>
		<div class="content">
			<?php echo $CKEditor->editor("rs_intro",$rs['rs_intro'],$ckeditorConfig);?>
		</div>
	</div>
	
	<div class="panel  panel-default" >
		<div class="panel-heading">
			<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
			<h3 class="panel-title text-primary "><?php echo T_("Respondent selection project introduction:");?></h3>
		</div>
		<div class="content">
			<?php echo $CKEditor->editor("rs_project_intro",$rs['rs_project_intro'],$ckeditorConfig);?>
		</div>
	</div>
	
	<div class="panel  panel-default">
		<div class="panel-heading">
			<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
			<h3 class="panel-title text-primary"><?php echo T_("Respondent selection callback (already started questionnaire):");?></h3>
		</div>
		<div class="content">
			<?php  echo $CKEditor->editor("rs_callback",$rs['rs_callback'],$ckeditorConfig);?>
		</div>
	</div>
	
	<div class="panel  panel-default">
		<div class="panel-heading">
			<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
			<h3 class="panel-title "><?php echo T_("Message to leave on an answering machine:");?></h3>
		</div>
		<div class="content">
			<?php  echo $CKEditor->editor("rs_answeringmachine",$rs['rs_answeringmachine'],$ckeditorConfig);?>
		</div>
	</div>
	
	<?php	
	}
else if (!empty($rs['lime_rs_sid'])) { 
	echo "<div class='well text-center'><a href='" . LIME_URL . "admin/admin.php?sid={$rs['lime_rs_sid']}'>" . T_("Edit respondent selection instrument in Limesurvey") . "</a></div>"; } 	
?>	

	<div class="panel  panel-default">
		<div class="panel-heading">
			<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
			<h3 class="panel-title "><?php  echo T_("Project end text (thank you screen):");?></h3>
		</div>
		<div class="content" >
			<?php  echo $CKEditor->editor("rs_project_end",$rs['rs_project_end'],$ckeditorConfig);?>
		</div>
	</div>

	<div class="panel panel-default ">
		<div class="panel-heading">
			<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
			<h3 class="panel-title"><?php echo T_("Project information for interviewers/operators:");?></h3>
		</div>
		<div class="content">
			<?php  echo $CKEditor->editor("info",$rs['info'],$ckeditorConfig);?>
		</div>
	</div>
	
	<a href="questionnairelist.php" class="btn btn-default"><i class="fa fa-chevron-left fa-lg" style="color:blue;"></i>&emsp;<?php echo  T_("Go back") ; ?></a>
	<input type="submit" class="btn btn-primary col-sm-offset-4"  name="update" value="<?php  echo T_("Update Questionnaire"); ?>"/>
	
</form>  
<?php 
}
else if (isset($_GET['delete']))
{
	$questionnaire_id = intval($_GET['delete']);

	$sql = "SELECT *
		FROM questionnaire
		WHERE questionnaire_id = $questionnaire_id";
	$rs = $db->GetRow($sql);
	
	xhtml_head(T_("Delete Questionnaire"),true,$css,$js_head, false, false, false, "&ensp;<span class='text-uppercase'>" . "$rs[description]" . "</span>");

	print "<div class='alert alert-danger'><p>" . T_("Any collected data and the limesurvey instrument will NOT be deleted") . "</p>"; 
	print "<p>" . T_("The questionnaire will be deleted from queXS including call history, cases, case notes, respondent details, appointments and the links between operators, clients and the questionnaire") . "</p>";
	print "<p>" . T_("Please confirm you wish to delete the questionnaire") . "</p></div>";

	print "<form method='post' action='?'>";
	print "<p>&emsp;&emsp;<a href='questionnairelist.php' class='btn btn-default' ><i class='fa fa-chevron-left fa-lg' style='color:blue;'></i>&emsp;" . T_("Go back") . "</a><input type='submit' name='submit' class='btn btn-danger col-sm-offset-4' value=\"" . T_("Delete this questionnaire") . "\"/>";
	print "<input type='hidden' name='questionnaire_id' value='$questionnaire_id'/></p>";
	print "</form>";
}
else
{
	xhtml_head(T_("Questionnaire management"),true,$css,$js_head, false, false, false, "Questionnaire list");
	echo "<div class='form-group'>
		<a href='' onclick='history.back();return false;' class='btn btn-default'><i class='fa fa-chevron-left fa-lg text-primary'></i>&emsp;" . T_("Go back") . "</a>
		<a href='new.php' class='btn btn-default col-sm-offset-6' ><i class='fa fa-file-text-o fa-lg'></i>&emsp;" . T_("Create a new questionnaire") . "</a>
	</div>";
	print "<div>"; // add  timeslots, callattempts,  quotas?

	$sql = "SELECT 
		CONCAT('&ensp;<b class=\'badge\'>',questionnaire_id,'</b>&ensp;') as qid,
		CONCAT('<h4>',description,'</h4>') as description,
		CASE WHEN enabled = 0 THEN
			CONCAT('&ensp;<span class=\'btn label label-default\'>" . TQ_("Disabled") . "</span>&ensp;')
		ELSE
			CONCAT('&ensp;<span class=\'btn label label-primary\'>" . TQ_("Enabled") . "</span>&ensp;')
		END as status,
		CASE WHEN enabled = 0 THEN
			CONCAT('&ensp;<a href=\'?enable=',questionnaire_id,'\'><i data-toggle=\'tooltip\' title=\'" . TQ_("Enable") . "\' class=\'fa fa-toggle-off fa-3x\' style=\'color:grey;\'></i></a>&ensp;')
		ELSE
			CONCAT('&ensp;<a href=\'\' data-toggle=\'confirmation\' data-href=\'?disable=',questionnaire_id,'\'><i data-toggle=\'tooltip\' title=\'" . TQ_("Disable") . "\' class=\'fa fa-toggle-on fa-3x\'></i></a>&ensp;')
		END as enabledisable,
		CONCAT('<a href=\'?modify=',questionnaire_id,'\' class=\'btn\' title=\'" . TQ_("Edit Questionnaire") . "&ensp;',questionnaire_id,'\' data-toggle=\'tooltip\'><i class=\'fa fa-edit fa-2x \'></i></a>') as modify,
		CONCAT('<a href=\'" . LIME_URL . "admin/admin.php?sid=',lime_sid,'\' class=\'btn\' title=\'" . T_("Edit Lime survey") . "&ensp;',lime_sid,'\' data-toggle=\'tooltip\'><i class=\'btn-lime fa fa-lemon-o fa-2x\'></i></a>') as inlime,
		CASE WHEN enabled = 0 THEN 
			CONCAT('<i class=\'btn fa fa-calendar fa-2x\' style=\'color:lightgrey;\'></i>')
		ELSE
			CONCAT('<a href=\'addshift.php?questionnaire_id=',questionnaire_id,'\' class=\'btn\' title=\'" . TQ_("Shifts") . "&ensp;\n" . TQ_("questionnaire") . "&ensp;',questionnaire_id,'\' data-toggle=\'tooltip\'><i class=\'fa fa-calendar fa-2x\'></i></a>')
		END as shifts,
		CASE WHEN enabled = 0 THEN 
			CONCAT('<i class=\'btn fa fa-square-o fa-2x\' style=\'color:lightgrey;\'></i>')
		ELSE
			CONCAT('<a href=\'questionnaireprefill.php?questionnaire_id=',questionnaire_id,'\' class=\'btn\' title=\'" . TQ_("Pre-fill questionnaire"). "&ensp;',questionnaire_id,'\' data-toggle=\'tooltip\'><i class=\'fa fa-check-square-o fa-2x\'></i></a>')
		END as prefill,
		CASE WHEN enabled = 1 THEN
			CONCAT('<i class=\'btn fa fa-trash-o fa-2x\' style=\'color:lightgrey;\'></i>')
		ELSE
			CONCAT('<a href=\'?delete=',questionnaire_id,'\' class=\'btn\' title=\'" . TQ_("Delete questionnaire") . "&ensp;',questionnaire_id,'\' data-toggle=\'tooltip\'><i class=\'fa fa-trash-o fa-2x\' style=\'color:red;\'></i></a>')
		END as deletee,
		CASE WHEN enabled = 0 THEN
			CONCAT('<i class=\'btn fa fa-bar-chart fa-2x\' style=\'color:lightgrey;\'></i>')
		ELSE
			CONCAT('<a href=\'outcomes.php?questionnaire_id=',questionnaire_id,'\' class=\'btn\' title=\'" . TQ_("Outcomes for questionnaire"). "&ensp;',questionnaire_id,'\' data-toggle=\'tooltip\'><i class=\'fa fa-bar-chart fa-2x\'></i></a>')
		END as outcomes,
		CONCAT('<a href=\'callhistory.php?questionnaire_id=',questionnaire_id,'\' class=\'btn\' title=\'" . TQ_("Call history"). "&ensp;\n" . TQ_("questionnaire"). "&ensp;',questionnaire_id,'\' data-toggle=\'tooltip\'><i class=\'fa fa-phone fa-2x\'></i></a>') as calls,
		CASE WHEN enabled = 0 THEN
			CONCAT('<i class=\'btn fa fa-download fa-2x\' style=\'color:lightgrey;\'></i>')
		ELSE
			CONCAT('<a href=\'dataoutput.php?questionnaire_id=',questionnaire_id,'\' class=\'btn\' title=\'" . TQ_("Data output"). "&ensp;\n" . TQ_("questionnaire"). "&ensp;',questionnaire_id,'\' data-toggle=\'tooltip\'><i class=\'fa fa-download fa-2x\'></i></a>')
		END as  dataout,
		CASE WHEN enabled = 0 THEN 
			CONCAT('<i class=\'btn fa fa-book fa-2x\' style=\'color:lightgrey;\'></i>')
		ELSE
			CONCAT('<a href=\'assignsample.php?questionnaire_id=',questionnaire_id,'\' class=\'btn\' title=\'" . TQ_("Assigned samples"). "\' data-toggle=\'tooltip\'><i class=\'fa fa-book fa-2x\'></i></a>')
		END as assample,
		CASE WHEN enabled = 0 THEN 
			CONCAT('<i class=\'btn fa fa-question-circle fa-2x\' style=\'color:lightgrey;\'></i>')
		ELSE
			CONCAT('<a href=\'casestatus.php?questionnaire_id=',questionnaire_id,'\' class=\'btn\' title=\'" . TQ_("Case status and assignment"). "\' data-toggle=\'tooltip\'><i class=\'fa fa-question-circle fa-2x\'></i></a>')
		END as casestatus
		FROM questionnaire";
	$rs = $db->GetAll($sql);

	$columns = array("qid","description","status","enabledisable","outcomes","calls","casestatus","shifts","assample","dataout","modify","inlime","prefill","deletee");
	xhtml_table($rs,$columns,false,"table-hover table-condensed "); 
	
print "</div>";
}
xhtml_foot($js_foot);
?>
<script type="text/javascript">
$('[data-toggle="confirmation"]').confirmation();
</script>
