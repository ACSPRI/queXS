<?php 


/**
 * Configuration file
 */
include ("../config.inc.php");

/**
 * Database file
 */
include ("../db.inc.php");

/**
 * Authentication file
 */
include ("auth-admin.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

$css = array(
"../include/bootstrap/css/bootstrap.min.css", 
"../include/bootstrap/css/bootstrap-theme.min.css",
"../include/font-awesome/css/font-awesome.css",
"../include/bootstrap-toggle/css/bootstrap-toggle.min.css",
"../css/custom.css"
			);
$js_head = array(
"../include/jquery/jquery.min.js",
"../include/bootstrap/js/bootstrap.min.js",
"../include/bootstrap-toggle/js/bootstrap-toggle.min.js",
				);
$js_foot = array(
"../js/new.js",
"../js/custom.js"
				);
global $db;	

xhtml_head(T_("Create a new questionnaire"),true,$css,$js_head); 

if (isset($_POST['import_file']))
{
	//file has been submitted         
	$ras =0;
	$rws = 0;
	$testing = 0;
	$referral = 0;
	$rs = 0;
	$lime_sid = 0;
	$respsc = 0;
	$lime_rs_sid = "NULL";
	if (isset($_POST['ras'])) $ras = 1;
	if (isset($_POST['rws'])) $rws = 1;
	if (isset($_POST['testing'])) $testing = 1;
	if (isset($_POST['respsc'])) $respsc = 1;
	if (isset($_POST['referral'])) $respsc = 1;
	if ($_POST['selectrs'] != "none") $rs = 1;
	
	$name = $db->qstr($_POST['description']);
	$rs_intro = $db->qstr(html_entity_decode($_POST['rs_intro'],ENT_QUOTES,'UTF-8'));
	$rs_project_intro = $db->qstr(html_entity_decode($_POST['rs_project_intro'],ENT_QUOTES,'UTF-8'));
	$rs_project_end = $db->qstr(html_entity_decode($_POST['rs_project_end'],ENT_QUOTES,'UTF-8'));
	$rs_callback = $db->qstr(html_entity_decode($_POST['rs_callback'],ENT_QUOTES,'UTF-8'));
	$rs_answeringmachine = $db->qstr(html_entity_decode($_POST['rs_answeringmachine'],ENT_QUOTES,'UTF-8'));
	$info  = $db->qstr(html_entity_decode($_POST['info'],ENT_QUOTES,'UTF-8'));

	//use existing lime instrument
	$lime_sid = bigintval($_POST['select']);


	if (is_numeric($_POST['selectrs']))
	{
		$lime_rs_sid = bigintval($_POST['selectrs']);
	}

//**  get default coma-separated outcomes list and use it for new questionnaire as initial set
	$sql = "SELECT o.outcome_id
			FROM `outcome` as o
			WHERE o.default = 1;";
	$def = $db->GetAll($sql);

	for ($i=0; $i < count($def); $i++){
		foreach($def[$i] as $key => $val){
			$do[] = $val;
		}	
	}

	$do = implode($do,",");
	
//** - end 
	
	$sql = "INSERT INTO questionnaire (questionnaire_id,description,lime_sid,restrict_appointments_shifts,restrict_work_shifts,respondent_selection,rs_intro,rs_project_intro,rs_project_end,rs_callback,rs_answeringmachine,testing,lime_rs_sid,info,self_complete,referral,outcomes)
		VALUES (NULL,$name,'$lime_sid','$ras','$rws','$rs',$rs_intro,$rs_project_intro,$rs_project_end,$rs_callback,$rs_answeringmachine,'$testing',$lime_rs_sid,$info,$respsc,$referral,'$do')";

	$rs = $db->Execute($sql);

	if ($rs)
	{
		$qid = $db->Insert_ID();
		if ($respsc == 1)
		{
			$lime_mode = $db->qstr($_POST['lime_mode']);
			$lime_template = $db->qstr($_POST['lime_template']);
			$lime_endurl = $db->qstr($_POST['lime_endurl']);

			$sql = "UPDATE questionnaire
				SET lime_mode = $lime_mode, lime_template = $lime_template, lime_endurl = $lime_endurl
				WHERE questionnaire_id = $qid";

			$db->Execute($sql);
		}
		$cl = "info";
		$message =  T_("Successfully inserted") . "&ensp;" . T_("with ID") . "&ensp; $qid, </h4><h4>" . T_("linked to survey") . "&ensp; $lime_sid ";
				
	}
	else{
		$cl = "danger";
		$message = T_("Error: Failed to insert questionnaire");
	}
	
	
?>
<script type="text/javascript" >
$(function() {
    $('#modal-confirm').modal('show');
});
</script>

<?php
$_POST['import_file'] = false;
}
?>

<!-- Modal window confirmation start  -->
<div class="modal fade " id="modal-confirm">
  <div class="modal-dialog ">
    <div class="modal-content ">
      <div class="modal-header" style="border-bottom:none;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<h4 class="modal-header"><?php echo  T_("Questionnaire");?>&emsp; <strong class="text-<?php echo $cl;?>"> <?php echo $name; ?></strong></h4>
      </div>
      <div class="modal-body ">
		<div class="alert alert-<?php echo $cl;?> text-center" role="alert">
			<h4> <?php print $message ;?></h4>
		</div>
      </div>
      <div class="modal-footer" style="borfer-top:none">
        <button  class="btn btn-default pull-left" data-dismiss="modal" style="width: 250px;" ><i class="fa fa-check fa-2x pull-right text-<?php echo $cl;?>"></i>&emsp;<?php echo T_("Create another ?");?><br><?php echo T_("Questionnaire");?></button> &emsp;
		<a href="questionnairelist.php" class="btn btn-default pull-right" style="width: 250px;" ><i class="fa fa-list text-<?php echo $cl;?> fa-2x pull-left"></i><?php echo T_("No, Thank you, go to");?>&ensp;<br><?php echo T_("Questionnaire management");?></a>
      </div>
    </div>
  </div>
</div><!-- /modal end -->

<!-- create new questionnaire  -->
<body>


<a href="questionnairelist.php" class="btn btn-default pull-left" ><i class="fa fa-list text-primary"></i>&emsp;<?php echo T_("Go to");?>&ensp;<?php echo T_("Questionnaire management");?> </a>
	
<?php	
$sql = "SELECT s.sid as sid, CONCAT(s.sid,' -> ',sl.surveyls_title) AS title
	FROM " . LIME_PREFIX . "surveys AS s
	LEFT JOIN " . LIME_PREFIX . "surveys_languagesettings AS sl ON ( s.sid = sl.surveyls_survey_id)
	WHERE s.active = 'Y'
	GROUP BY s.sid";
$surveys = $db->GetAll($sql);

if (!empty($surveys)){?>

<form enctype="multipart/form-data" action="" method="post" class="form-horizontal col-lg-12" >

	<input type="hidden" name="MAX_FILE_SIZE" value="1000000000" />
	<div class="form-group">
		<label class="col-lg-4 control-label" ><?php  echo T_("Name for questionnaire:"); ?> </label>
		<div class="col-lg-4">
			<input type="text" name="description" class="form-control" required placeholder="<?php echo T_("Enter New questionnaire name..");?>" title="<?php echo T_("Name for questionnaire:") ; ?>" />
		</div>
	</div>
	
	<div class="form-group row">
		<label class="col-sm-4 control-label" ><?php  echo T_("Select limesurvey instrument:");?> </label>
		<div class='col-sm-4'>
			<select name="select" class="form-control">
			<?php foreach($surveys as $s){?>  
				<option value="<?php echo $s['sid'];?>"><?php echo T_("Survey"), ":&ensp;", $s['title'] ;?></option><?php } ?>
			</select>
		</div>
		<div class='col-sm-4'>
			<strong><?php echo T_("or") ;?>&emsp;</strong>
			<a class="btn btn-lime" href="<?php echo LIME_URL ;?>admin/admin.php?action=newsurvey"><i class="fa fa-lemon-o text-danger"></i>&emsp;<?php echo T_("Create an instrument in Limesurvey") ;?></a>
		</div>
	</div>
		
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Respondent selection type:"); ?> </label>
		<div class="col-sm-4">
			<select class="form-control" name="selectrs" id="selectrs" onchange="if(this.value == 'old') show(this,'rstext');  else hide(this,'rstext')">
				<option value="none"><?php  echo T_("No respondent selection (go straight to questionnaire)"); ?></option>
				<option value="old" ><?php echo T_("Use basic respondent selection text (below)"); ?></option>
			<?php 
			foreach($surveys as $s){ ?> 
				<option value="<?php echo $s['sid'];?>"><?php echo T_("Survey") ,":&ensp;", $s['title'] ;?></option>
			<?php }?>
			</select>
		</div>
	</div>
	
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Restrict appointments to shifts?"); ?></label>
		<div class="col-sm-4" style="height: 30px;">
			<input name="ras" type="checkbox" checked="checked" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-width="80" />
		</div>
	</div>
	
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Restrict work to shifts?"); ?></label>
		<div class="col-sm-4"style="height: 30px;">
			<input name="rws" type="checkbox" checked="checked" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-width="80"/>
		</div>
	</div>
	
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Questionnaire for testing only?"); ?></label>
		<div class="col-sm-4"style="height: 30px;">
			<input name="testing" type="checkbox" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-onstyle="danger" data-width="80" />
		</div>
	</div>
	
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Allow operators to generate referrals?"); ?></label>
		<div class="col-sm-4"style="height: 30px;">
			<input name="referral" type="checkbox" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-width="80"/>
		</div>
	</div>
	
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php  echo T_("Allow for respondent self completion via email invitation?"); ?> </label>
		<div class="col-sm-4"style="height: 30px;">
			<input name="respsc" type="checkbox"  onchange="if(this.checked==true) {show(this,'limesc'); $('#url').attr('required','required');} else{ hide(this,'limesc'); $('#url').removeAttr('required');}" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-width="80"/>
		</div>
	</div>
	
	<div id="limesc" style="display:none" >
	<div class="form-group">
		<label class="col-sm-4 control-label" ><?php echo T_("Questionnaire display mode for respondent");?>: </label>
		<div class="col-sm-4">
			<select class="form-control"  name="lime_mode">
				<option value="survey"><?php echo T_("All in one"); ?></option>
				<option value="question"><?php echo T_("Question by question"); ?></option>
				<option value="group"><?php echo T_("Group at a time"); ?></option>
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
						echo "<option value=\"$entry\">$entry</option>";
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
			<input class="form-control"  name="lime_endurl" id="url" type="url" />
		</div>
	</div>
</div>


<?php
/*   CKEditor  */
 
include("../include/ckeditor/ckeditor.php");

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

<div id="rstext"   class=" " style="display:none ">

	<div class="panel  panel-default" >
		<div class="panel-heading">
			<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
			<h3 class="panel-title text-primary "><?php  echo T_("Respondent selection introduction:");?></h3>
		</div>
		<div class="content">
			<?php echo $CKEditor->editor("rs_intro","",$ckeditorConfig);?>
		</div>
	</div>

	<div class="panel  panel-default" >
		<div class="panel-heading">
			<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
			<h3 class="panel-title text-primary "><?php echo T_("Respondent selection project introduction:");?></h3>
		</div>
		<div class="content">
			<?php echo $CKEditor->editor("rs_project_intro","",$ckeditorConfig);?>
		</div>
	</div>

	<div class="panel  panel-default">
		<div class="panel-heading">
			<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
			<h3 class="panel-title text-primary"><?php echo T_("Respondent selection callback (already started questionnaire):");?></h3>
		</div>
		<div class="content">
			<?php  echo $CKEditor->editor("rs_callback","",$ckeditorConfig);?>
		</div>
	</div>

	<div class="panel  panel-default">
		<div class="panel-heading">
			<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
			<h3 class="panel-title "><?php echo T_("Message to leave on an answering machine:");?></h3>
		</div>
		<div class="content">
			<?php  echo $CKEditor->editor("rs_answeringmachine","",$ckeditorConfig);?>
		</div>
	</div>
</div>


<div class="panel panel-default">
  <div class="panel-heading">
	<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
    <h3 class="panel-title "><?php  echo T_("Project end text (thank you screen):");?></h3>
  </div>
  <div class="content" >
    <?php  echo $CKEditor->editor("rs_project_end","",$ckeditorConfig); ?>
  </div>
</div>

<div class="panel panel-default">
  <div class="panel-heading">
	<i class="fa fa-fw fa-2x wminimize fa-chevron-circle-up text-primary pull-left" data-toggle="tooltip" title="<?php echo T_("Expand/Collapse");?>" style="margin-top: -5px;"></i>
    <h3 class="panel-title"><?php echo T_("Project information for interviewers/operators:");?></h3>
  </div>
  <div class="content">
    <?php  echo $CKEditor->editor("info","",$ckeditorConfig);?>
  </div>
</div>

<div class="row form-group">
	<div class="col-sm-4 ">
		<a href="questionnairelist.php"  class="btn btn-default pull-right" ><i class="fa fa-list text-primary"></i>&emsp;<?php echo T_("Cancel");?></a>
	</div>
	<div class="col-sm-4 ">
		<button type="submit" class="btn btn-primary pull-right btn-lg" name="import_file" ><i class="fa fa-check-square-o fa-lg"></i>&emsp;<?php  echo T_("Create Questionnaire"); ?></button>
	</div>
</div>

</form>

<?php }
else { ?>
		<div class='col-sm-6 col-sm-offset-1'>
		<h3 class="alert alert-warning"> <?php echo T_("NO active Lime surveys available");?> </h3>
			<a class="btn btn-lime btn-lg btn-block" href="<?php echo LIME_URL ;?>admin/admin.php?action=newsurvey"><i class="fa fa-lemon-o text-danger"></i>&emsp;<?php echo T_("Create an instrument in Limesurvey");?>  </a>
			<h4 class="text-center"><?php echo T_("or"); ?></h4>
			<a class="btn btn-lime btn-lg btn-block" href="<?php echo LIME_URL ;?>admin/admin.php?action=listsurveys"><i class="fa fa-lemon-o text-danger"></i>&emsp;<?php echo T_("Administer instruments with Limesurvey");?>  </a> 
		</div>
<?php } ?>


<?php 
xhtml_foot($js_foot);//
?>
