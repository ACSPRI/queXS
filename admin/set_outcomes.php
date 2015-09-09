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
"../js/bootstrap-confirmation.js",
"../js/custom.js"
				);
				
global $db;	


if (isset($_POST['default']) && isset($_POST['save'])){
	
	$sql = "UPDATE `outcome` as o
			SET `deflt` = 0
			WHERE o.const != 1";
	$db->Execute($sql);	

	if(!empty($_POST['select']) ){
		foreach($_POST['select'] as $n => $val)
			{
				$sel[] = $val;
			}
		$sel=implode($sel,",");

		$sql = "UPDATE `outcome` as o
				SET `deflt` = 1
				WHERE o.const != 1
				AND o.outcome_id IN ($sel)";

		$db->Execute($sql);	 
	}
	
	$_GET['default'] = $_POST['default'];
	
	unset($_POST['default']);
	unset($_POST['save']);
}
				
if (isset($_POST['qid']) && isset($_POST['save'])){

	//get id's for 'constant' outcomes
	$sql = "SELECT o.outcome_id
			FROM `outcome` as o
			WHERE o.const = 1
			AND o.deflt = 1;";
	$def = $db->GetAll($sql);
			
	for ($i=0; $i < count($def); $i++){
			foreach($def[$i] as $key => $val){
				$sel[] = $val;
			}
	}
	
	if(!empty($_POST['select']) ){		
		//add selected outcomes
		foreach($_POST['select'] as $n => $val)
			{
				$sel[] = $val;
			}
	}
	
	$sel=implode($sel,",");
		
	print $sel . "</br>";
	
	$qid = intval($_POST['qid']);
	$sql = "UPDATE questionnaire
			SET outcomes = '$sel'
			WHERE questionnaire_id = $qid";

	$db->Execute($sql);	 
	
	
	$_GET['qid'] = $_POST['qid'];
	
	unset($_POST['qid']);
	unset($_POST['save']);
}



/*select outcomes list*/

if (isset($_GET['default'])) { $title = T_("Default outcomes"); } 
else if (isset($_GET['qid'])){ $title = T_("Questionnaire outcomes"); $qid = intval($_GET['qid']); }
else die();

xhtml_head($title,true,$css,$js_head); 

/* for questionnire outcomes  */
if (isset($_GET['qid'])) {
	
	if($qid == 0) $qid = false;

	print "<h3 class='form-inline pull-left'>" . T_("Questionnaire") . ":&emsp;</h3>";

	$sql = "SELECT questionnaire_id as value,description, CASE WHEN questionnaire_id = '$qid' THEN 'selected=\'selected\'' ELSE '' END AS selected 
			FROM questionnaire
			WHERE enabled = 1";
	display_chooser($db->GetAll($sql),"questionnaire","qid", true,false,true,true,false,true,"form-inline pull-left ");
	
	
	if ($qid != false)
	{
		$qoutc = $db->GetOne("SELECT q.outcomes FROM `questionnaire` as q WHERE q.questionnaire_id = $qid");
		
		if (empty($qoutc)) {  //  update q.outcomes with default list
			
			$sql = "SELECT o.outcome_id
					FROM `outcome` as o
					WHERE o.deflt = 1;";
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
			CONCAT('<input type=\'checkbox\' ', CASE WHEN o.outcome_id IN ($qoutc) THEN 'checked=\"checked\"' ELSE '' END ,' ', CASE WHEN o.const = 1 THEN 'disabled=\"disabled\" data-onstyle=\"success\"' ELSE '' END ,' name=\"select[]\" value=\'',o.outcome_id,'\' data-toggle=\"toggle\" data-size=\"small\" data-style=\"center-block\" data-on=" . TQ_("Yes") . " data-off=" . TQ_("No") . " data-width=\"70\"/>') as `select`
			from `outcome`  as o, `outcome_type` as ot
			WHERE o.outcome_type_id = ot.outcome_type_id
			ORDER BY `o`.`outcome_id` ASC";
	
		$rs = $db->GetAll($sql);
	
		$row = array("outcome_id","description","type","select");
		$hdr = array(T_("Outcome ID"),T_("Description"),T_("Outcome type"),T_("Select"));
		$hid  = "qid";
		$value = "$qid";
	}
}

/* for default outcomes  */
if (isset($_GET['default'])) {
	
	$sql = "SELECT o.*, ot.description as type, 
			CONCAT('<h4>&ensp;<span class=\"label label-', CASE WHEN o.tryanother = 1 THEN  'primary\">" . T_("Yes") . "' ELSE 'default\">" . T_("No") . "' END , '</span></h4>') as tryanother,
			CONCAT('<h4>&ensp;<span class=\"label label-', CASE WHEN o.tryagain = 1 THEN  'primary\">" . T_("Yes") . "' ELSE 'default\">" . T_("No") . "' END , '</span></h4>') as tryagain,
			CONCAT('<h4>&ensp;<span class=\"label label-', CASE WHEN o.contacted = 1 THEN  'primary\">" . T_("Yes") . "' ELSE 'default\">" . T_("No") . "' END , '</span></h4>') as contacted,
			CONCAT('<h4>&ensp;<span class=\"label label-', CASE WHEN o.eligible = 1 THEN  'primary\">" . T_("Yes") . "' ELSE 'default\">" . T_("No") . "' END , '</span></h4>') as eligible,
			CONCAT('<h4>&ensp;<span class=\"label label-', CASE WHEN o.require_note = 1 THEN  'primary\">" . T_("Yes") . "' ELSE 'default\">" . T_("No") . "' END , '</span></h4>') as require_note,			
			CONCAT('<input type=\'checkbox\' ', CASE WHEN o.deflt = 1 THEN 'checked=\"checked\"' ELSE '' END ,' ', CASE WHEN o.const = 1 THEN 'disabled=\"disabled\" data-onstyle=\"success\"' ELSE '' END ,' name=\"select[]\" value=\'',o.outcome_id,'\' data-toggle=\"toggle\" data-size=\"small\" data-style=\"center-block\" data-on=" . TQ_("Yes") . " data-off=" . TQ_("No") . " data-width=\"70\" />') as `select`
			from `outcome`  as o, `outcome_type` as ot
			WHERE o.outcome_type_id = ot.outcome_type_id
			ORDER BY `o`.`outcome_id` ASC";
	
	$rs = $db->GetAll($sql);

	$row = array("outcome_id","description","select","type","default_delay_minutes","contacted","tryanother","tryagain","eligible","require_note");
	$hdr = array(T_("Outcome ID"),T_("Description"),T_("Default"),T_("Outcome type"),T_("Delay, min"),T_("Contacted"),T_("Try another"),T_("Try again"),T_("Eligible"),T_("Require note"));
	$hid  = "default";
	$value = "";

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


?>

	<form enctype="multipart/form-data" action="?" method="post" class="form-horizontal col-lg-12" >
	
		<?php xhtml_table($rs,$row,$hdr); ?>
		
		<input type='hidden' name='<?php echo $hid; ?>' value='<?php echo $value;?>' /> </br>
		
		<div class="row form-group">
			<div class="col-sm-4 ">
				<a href="questionnairelist.php"  class="btn btn-default pull-right" ><i class="fa fa-list text-primary"></i>&emsp;<?php echo T_("Cancel");?></a>
			</div>
			<div class="col-sm-4 ">
				<button type="submit" class="btn btn-primary pull-right btn-lg" name="save" ><i class="fa fa-check-square-o fa-lg"></i>&emsp;<?php  echo T_("Save selection"); ?></button>
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