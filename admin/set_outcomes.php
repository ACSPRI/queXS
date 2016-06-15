<?php
/*
*
*	
*
*
*/

/**
 * Configuration file
 */
require(dirname(__FILE__).'/../config.inc.php');

/**
 * Database
 */
require(dirname(__FILE__).'/../db.inc.php');

/**
 * Authentication file
 */
include ("auth-admin.php");

/**
 * XHTML functions
 */
require(dirname(__FILE__).'/../functions/functions.xhtml.php');


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
"../js/window.js",
"../js/custom.js"
				);
				
global $db;

if (isset($_POST['default']) && isset($_POST['save'])){
	
	$db->StartTrans();

	$sql = "UPDATE `outcome` as o
			SET `default` = 0
			WHERE o.permanent != 1";
	$db->Execute($sql);	

	if(!empty($_POST['select']) ){
		foreach($_POST['select'] as $n => $val)
			{
				$sel[] = $val;
			}
		$sel=implode($sel,",");

		$sql = "UPDATE `outcome` as o
				SET `default` = 1
				WHERE o.permanent != 1
				AND o.outcome_id IN ($sel)";
		$db->Execute($sql);
	}
	if(!empty($_POST['delay'])){
		foreach($_POST['delay'] as $n => $val) {
			$db->Execute("UPDATE `outcome`SET default_delay_minutes = $val WHERE outcome_id = $n");
		}
	}
	if(!empty($_POST['delete'])){
		foreach($_POST['delete'] as $n => $val) {
			$db->Execute("DELETE FROM `outcome` WHERE outcome_id = $n AND outcome_id >= 100");
		}
	}
	
	if($db->CompleteTrans()) $msg_ok = T_("Default outcomes updated"); else $msg_err = T_("Default outcomes NOT updated");
	
	$_GET['default'] = $_POST['default'];
	unset($_POST['default']);
	unset($_POST['save']);
}
				
if (isset($_POST['qid']) && $_POST['qid'] > 0 && isset($_POST['save'])){

	//get id's for 'permanent' outcomes
	$sql = "SELECT o.outcome_id
			FROM `outcome` as o
			WHERE o.permanent = 1
			AND o.default = 1;";
	$def = $db->GetAll($sql);
			
	for ($i=0; $i < count($def); $i++){
		foreach($def[$i] as $key => $val){
			$sel[] = $val;
		}
	}
	
	if(!empty($_POST['select']) ){		
		//add selected outcomes
		foreach($_POST['select'] as $n => $val){
			$sel[] = $val;
		}
	}
	
	$sel=implode($sel,",");
	
	$qid = intval($_POST['qid']);
	$sql = "UPDATE questionnaire
			SET outcomes = '$sel'
			WHERE questionnaire_id = $qid";
	if ($db->Execute($sql)) $msg_ok = T_("Questionnaire outcomes saved");	else $msg_err = T_("Error:") . " " . T("Questionnaire outcomes not saved");
	
	$_GET['qid'] = $_POST['qid'];
	unset($_POST['qid']);
	unset($_POST['save']);
}

if (isset($_POST['addoutcome']) && isset($_POST['save'])){
	if (isset($_POST['description']) && !empty($_POST['description']) && intval($_POST['outcome_type_id']) > 0 ) {
		$desc = $_POST['description'];
		$outcome_type_id = intval($_POST['outcome_type_id']);
		if (isset($_POST['default_delay_minutes'])) $ddm = $_POST['default_delay_minutes']; else $ddm = 0;
		if (isset($_POST['contacted'])) $contacted = 1; else $contacted = 0;
		if (isset($_POST['tryanother'])) $tryanother = 1; else $tryanother = 0;
		if (isset($_POST['tryagain'])) $tryagain = 1; else $tryagain = 0;
		if (isset($_POST['eligible'])) $eligible = 1; else $eligible = 0;
		if (isset($_POST['require_note'])) $require_note = 1; else $require_note = 0;
		if (isset($_POST['calc'])) $calc = $_POST['calc']; else $calc = "";
		if (isset($_POST['aapor_id'])) $aapor_id = $_POST['aapor_id']; else $aapor_id = "";
		if (isset($_POST['default_o'])) $def = 1; else $def = 0;
		if (isset($_POST['permanent'])) $perm = 1; else $perm = 0;
			
		$sql = "INSERT INTO `outcome` VALUES ('NULL','$aapor_id','$desc','$ddm','$outcome_type_id','$tryanother','$contacted','$tryagain','$eligible','$require_note','$calc','$def','$perm')";	
		if ($db->Execute($sql)) {
			$msg_ok = T_("Custom outcome") . " <b>" . $desc . "</b> "  . T_("saved");
		}
		else $msg_err = T_("Error:") . " " . T_("New outcome not saved");
	}
	else {
			if (empty($_POST['description'])) $ms = T_("Description"); 
			if ($_POST['outcome_type_id'] <= 0) $ms =  T_("Outcome type");
			$msg_err = T_("Error:") . " " . $ms . " " . T_("is not set");
			$_GET['addoutcome'] = $_POST['addoutcome'];
	}
	
	if (isset($_POST['h']) && isset($_POST['v'])) {$h = $_POST['h']; $_GET[$h] = $_POST['v'];}
	unset($_POST['addoutcome']);
	unset($_POST['save']);
}


/*select outcomes list*/

if (isset($_GET['default']))  $title = T_("Set default outcomes");
else if (isset($_GET['qid'])) $title = T_("Set questionnaire outcomes");
else if (isset($_GET['addoutcome'])) $title = T_("ADD custom outcome");
else die();

xhtml_head($title,true,$css,$js_head); 


/* to add customm outcome*/
if (isset($_GET['addoutcome'])){

	$rs[] = ["description" => "<label class='text-capitalize' style='width:20em;' >" . T_("Outcome description") . "</label>", "value" => "<input name='description' type='text' class='form-control' required size=60 maxlength=60 />"];
	$sql = "SELECT outcome_type_id as value,description FROM `outcome_type`"; 
	$ot = $db->GetAll($sql); translate_array($ot, array("description"));
	$select = display_chooser($ot,"outcome_type_id","outcome_type_id",true,false,false,true,false,false);
	$rs[] = ["description" => "<label>" . T_("Outcome type") . "</label>", "value" => "{$select}"];
	$rs[] = ["description" => "<label>" . T_("Default delay, minutes") . "</label>", "value" => "<input name='default_delay_minutes' type='number' min='0' max='600000' size=8 class='form-control' style='width:8em;' />"];
	$rs[] = ["description" => "<label>" . T_("Contacted") . " ?</label>", "value" => "<input name='contacted' type='checkbox' data-toggle=\"toggle\" data-on=" . T_("Yes") . " data-off=" . T_("No") . " />"];
	$rs[] = ["description" => "<label>" . T_("Try another number") . " ?</label>", "value" => "<input name='tryanother' type='checkbox' data-toggle=\"toggle\" data-on=" . T_("Yes") . " data-off=" . T_("No") . " />"];
	$rs[] = ["description" => "<label>" . T_("Try again") . " ?</label>", "value" => "<input name='tryagain' type='checkbox' data-toggle=\"toggle\" data-on=" . T_("Yes") . " data-off=" . T_("No") . " />"];
	$rs[] = ["description" => "<label>" . T_("Eligible") . " ?</label>", "value" => "<input name='eligible' type='checkbox' data-toggle=\"toggle\" data-on=" . T_("Yes") . " data-off=" . T_("No") . " />"];
	$rs[] = ["description" => "<label>" . T_("Require note") . " ?</label>", "value" => "<input name='require_note' type='checkbox' data-toggle=\"toggle\" data-on=" . T_("Yes") . " data-off=" . T_("No") . " />"];
	$rs[] = ["description" => "<label>" . T_("Calculation") . "</label>", "value" => "<input name='calc' type='text' class='form-control' size=10 style='width:8em;' maxlength=10 />"];
	$rs[] = ["description" => "<label>" . T_("AAPOR code") . "</label>", "value" => "<input name='aapor_id' type='text' class='form-control' size=10 style='width:8em;' maxlength=10 />"];
	$rs[] = ["description" => "<label>" . T_("Default outcome") . " ?</label>", "value" => "<input name='default_o' type='checkbox' data-toggle=\"toggle\" data-on=" . T_("Yes") . " data-off=" . T_("No") . " />"];
	$rs[] = ["description" => "<label>" . T_("Permanent outcome") . " ?</label>", "value" => "<input name='permanent' type='checkbox' data-toggle=\"toggle\" data-on=" . T_("Yes") . " data-off=" . T_("No") . " />"];
	
	$hid  = "addoutcome"; $value = "newoutcome"; $h = $_GET['h']; $v = $_GET['v'];
	$row = array("description","value");
	$hdr = array(T_("Description"),T_("Value"));
	$sbtn = T_("Save custom Outcome");
	$class = "table-hover table-condensed";
}

/* for questionnire outcomes  */
if (isset($_GET['qid'])) {
	
	$qid = intval($_GET['qid']); 
	if($qid == 0) $qid = false;

	print "<div class='form-group'><h3 class='form-inline text-right col-lg-4'>" . T_("Questionnaire") . ":&emsp;</h3>";

	$sql = "SELECT questionnaire_id as value,description, CASE WHEN questionnaire_id = '$qid' THEN 'selected=\'selected\'' ELSE '' END AS selected 
			FROM questionnaire
			WHERE enabled = 1";
	display_chooser($db->GetAll($sql),"questionnaire","qid",true,false,true,true,false,true,"form-inline");
	print "</div>";
	
	if ($qid != 0)
	{
		$qd = $db->GetRow("SELECT outcomes, self_complete, referral FROM `questionnaire` WHERE questionnaire_id = $qid");

		$qoutc = $qd['outcomes'];
		$sc = $qd['self_complete'];
		$ref = $qd['referral'];
		
		if (empty($qoutc)) {  //  update q.outcomes with default list
			
			$sql = "SELECT o.outcome_id
					FROM `outcome` as o
					WHERE o.default = 1;";
			$def = $db->GetAll($sql);
			
			for ($i=0; $i < count($def); $i++){
				foreach($def[$i] as $key => $val){
					$do[] = $val;
				}
			}
			$qoutc = implode($do,",");
			
			$sql = "UPDATE questionnaire
					SET outcomes = '$qoutc'
					WHERE questionnaire_id = $qid";
			$db->Execute($sql);
		}
	
		$sql = "SELECT o.*, ot.description as type, 
			CONCAT('<input type=\'checkbox\'', 
			CASE WHEN ((o.outcome_id = 40 AND $sc = 1) OR (o.outcome_id = 41 AND $ref = 1)) THEN 'checked=\"checked\" data-onstyle=\"success\"' ELSE '' END ,'',
			CASE WHEN ((o.outcome_id = 40 AND $sc != 1) OR (o.outcome_id = 41 AND $ref != 1)) THEN 'disabled=\"disabled\"' ELSE '' END ,'', 
			CASE WHEN o.outcome_id NOT IN (40,41) AND o.outcome_id IN ($qoutc) THEN 'checked=\"checked\"' ELSE '' END ,'', 
			CASE WHEN o.outcome_id NOT IN (40,41) AND o.permanent = 1 THEN 'disabled=\"disabled\" data-onstyle=\"success\"' ELSE '' END ,' 
			name=\"select[]\" value=\'',o.outcome_id,'\' data-toggle=\"toggle\" data-size=\"small\" data-style=\"center-block\" data-on=" . TQ_("Yes") . " data-off=" . TQ_("No") . " data-width=\"70\"/>') as `select`
			from `outcome`  as o, `outcome_type` as ot
			WHERE o.outcome_type_id = ot.outcome_type_id
			ORDER BY `o`.`outcome_id` ASC";
	
		$rs = $db->GetAll($sql);
		$hid  = "qid"; $value = $qid; $h  = "qid"; $v = $qid;
		$row = array("outcome_id","description","type","select");
		$hdr = array(T_("Outcome ID"),T_("Description"),T_("Outcome type"),T_("Select")."&nbsp;?");
		$abtn = T_("Add custom Outcome");
		$sbtn = T_("Save questionnaire outcomes");
		$class = "tclass";
	}
}

/* for default outcomes  */
if (isset($_GET['default'])) {
	
	/* allow delay edit only to superadmins (currenlty admin) */
		$delay = "CONCAT('<input type=\'number\' name=\"delay[', o.outcome_id ,']\" class=\'form-control text-right\' style=\'width:6em;\' max=50000 min=0 required value=\'', o.default_delay_minutes ,'\' />') ";
		$delete = "CASE WHEN o.outcome_id >= 100 THEN CONCAT('<input type=\'checkbox\' class=\' \' data-onstyle=\"danger\" title=\'".TQ_("Delete outcome")." ?\' name=\"delete[', o.outcome_id ,']\" data-toggle=\"toggle\" data-size=\"small\" data-style=\"center-block\" data-on=".TQ_("Yes")." data-off=".TQ_("No")." data-width=\"60\"  />') ELSE '' END as `delete`,";
	$sql = "SELECT o.*, ot.description as type, $delay as `delay`, $delete 
			CONCAT('<h4>&ensp;<span class=\"label label-', CASE WHEN o.tryanother = 1 THEN  'primary\">".T_("Yes")."' ELSE 'default\">".T_("No")."' END , '</span></h4>') as tryanother,
			CONCAT('<h4>&ensp;<span class=\"label label-', CASE WHEN o.tryagain = 1 THEN  'primary\">" . T_("Yes")."' ELSE 'default\">".T_("No")."' END , '</span></h4>') as tryagain,
			CONCAT('<h4>&ensp;<span class=\"label label-', CASE WHEN o.contacted = 1 THEN  'primary\">" . T_("Yes")."' ELSE 'default\">".T_("No")."' END , '</span></h4>') as contacted,
			CONCAT('<h4>&ensp;<span class=\"label label-', CASE WHEN o.eligible = 1 THEN  'primary\">".T_("Yes")."' ELSE 'default\">".T_("No")."' END , '</span></h4>') as eligible,
			CONCAT('<h4>&ensp;<span class=\"label label-', CASE WHEN o.require_note = 1 THEN  'primary\">".T_("Yes")."' ELSE 'default\">".T_("No")."' END , '</span></h4>') as require_note,			
			CONCAT('<input type=\'checkbox\' ', CASE WHEN o.default = 1 THEN 'checked=\"checked\"' ELSE '' END ,' ', CASE WHEN o.permanent = 1 THEN 'disabled=\"disabled\" data-onstyle=\"success\"' ELSE '' END ,' name=\"select[]\" value=\'',o.outcome_id,'\' data-toggle=\"toggle\" data-size=\"small\" data-style=\"center-block\" data-on=".TQ_("Yes")." data-off=".TQ_("No")." data-width=\"70\" />') as `select`
			from `outcome`  as o, `outcome_type` as ot
			WHERE o.outcome_type_id = ot.outcome_type_id 
			ORDER BY `o`.`outcome_id` ASC";
	
	$rs = $db->GetAll($sql);

	$row = array("outcome_id","description","select","type","delay","contacted","tryanother","tryagain","eligible","require_note");
	$hdr = array(T_("Outcome ID"),T_("Description"),T_("Set default")."&nbsp;?",T_("Outcome type"),T_("Delay, min"),T_("Contacted")."&nbsp;?",T_("Try another")."&nbsp;?",T_("Try again")."&nbsp;?",T_("Eligible")."&nbsp;?",T_("Require note")."&nbsp;?");
	$row[] = "delete"; $hdr[] = T_("Delete")."&nbsp;?";
	$hid  = "default"; $value = ""; $h  = "default"; $v = "";
	$abtn = T_("Add custom Outcome");
	$sbtn = T_("Update default outcomes");
	$class = "tclass";

} 

	
if (isset($rs) && !empty($rs)){
	
	translate_array($rs, array("description","type"));	
 	
	for ($i = 0; $i < count($rs); $i++){
		foreach ($rs[$i] as $key => $val){
			if ($key == "type"){
				$rs[$i]['type'] = preg_replace("#\s*\(.+#m", '', $val); // cut description in bracets for 'outcome_type'
			}			
		}
	}
	
	if (isset($msg_ok))  print "<div class='alert alert-success'>" . $msg_ok . "</div>";
	if (isset($msg_err))  print "<div class='alert alert-danger'>" . $msg_err . "</div>";
  
?>

	<form enctype="multipart/form-data" action="?" method="post" class="form-horizontal col-lg-12" >
	
		<?php xhtml_table($rs,$row,$hdr,$class); ?>
		<input type='hidden' name='h' value='<?php echo $h; ?>' />
		<input type='hidden' name='v' value='<?php echo $v; ?>' />
		<input type='hidden' name='<?php echo $hid; ?>' value='<?php echo $value; ?>' /> </br>
		
		<div class="row form-group">
			<div class="col-lg-3">
				<a href="set_outcomes.php?<?php echo $h;?>=<?php echo $v;?>"  class="btn btn-default" ><i class="fa fa-undo fa-lg text-primary"></i>&emsp;<?php echo T_("Reset");?></a>
			</div>
			<div class="col-lg-3">
			<?php if (!isset($_GET['addoutcome'])) { ?> 
				<a href="set_outcomes.php?addoutcome&amp;h=<?php echo $h;?>&amp;v=<?php echo $v;?>"  class="btn btn-default" ><i class="fa fa-plus fa-lg text-primary"></i>&emsp;<?php echo $abtn; ?></a>
			 <?php } ?>
			</div>
			<div class="col-lg-6">
				<button type="submit" class="btn btn-primary btn-lg" name="save" ><i class="fa fa-check-square-o fa-lg"></i>&emsp;<?php  echo $sbtn; ?></button>
			</div>
		</div>
	</form>

<?php

}
/* else {
	if (isset($_GET['default'])) { ?>
		<div class="well text-danger col-sm-4"><p><?php echo T_("ERROR: Check tables 'outcome' and 'outcome_type' in  DB"); ?></p></div>
	<?php }

	if (isset($_GET['qid']) ) { ?>
		<div class="well text-danger col-sm-4"><p><?php echo T_("ERROR: Check tables 'outcome' and 'questionnaire' in  DB"); ?></p></div> 
	<?php }
} */
	
xhtml_foot($js_foot);

?>
