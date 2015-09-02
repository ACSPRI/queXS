<?php /**
 * Display a list of cases including status. Allow for assigning to operators in a queue
 */

/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

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
 * Display functions
 */
include("../functions/functions.display.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

$css = array(
"../include/bootstrap/css/bootstrap.min.css", 
"../include/bootstrap/css/bootstrap-theme.min.css",
"../include/font-awesome/css/font-awesome.css",
"../include/bs-data-table/css/jquery.bdt.css",
//"../include/iCheck/skins/square/blue.css",
"../css/custom.css"
			);
$js_head = array(
"../include/jquery/jquery.min.js",
"../include/bootstrap/js/bootstrap.min.js"
				);
$js_foot = array(
"../include/bs-data-table/js/vendor/jquery.sortelements.js",
"../include/bs-data-table/js/jquery.bdt.js",
"../include/iCheck/icheck.min.js",
"../js/window.js",
"../js/custom.js"
				);

/**
 * Generate the case status report
 *
 * @param mixed  $questionnaire_id The quesitonnaire, if specified
 * @param string $sample_id        The sample, if speified
 * @param mixed  $outcome_id           THe outcome id, if specified
 * 
 * @return false if empty otherwise true if table drawn
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2012-10-02
 */
 
 
function case_status_report($questionnaire_id = false, $sample_id = false, $outcome_id  = false)
{
	global $db;

	$q = "";
	if ($questionnaire_id !== false)
		$q = "AND c.questionnaire_id = $questionnaire_id";

	$s = "";
	if ($sample_id !== false)
		$s = "AND s.import_id = '$sample_id'";

	$o = "";
	if ($outcome_id !== false)
		$o = "AND c.current_outcome_id = $outcome_id";

	$sql = "SELECT 	CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>') as case_id,
			o.description as outcomes,
			si.description as samples, s.Time_zone_name as timezone, TIME_FORMAT(CONVERT_TZ(NOW(),@@session.time_zone,s.Time_zone_name),'". TIME_FORMAT ."') as time, (SELECT COUNT(*) FROM `call` WHERE `call`.case_id = c.case_id) as nrcalls, (SELECT COUNT(*) FROM call_attempt WHERE call_attempt.case_id = c.case_id) as nrattempts, 	
			CASE WHEN ca.end IS NULL THEN '" . TQ_("Available") . "'
				WHEN TIME_TO_SEC(TIMEDIFF(ca.end,CONVERT_TZ(DATE_SUB(NOW(), INTERVAL co.default_delay_minutes MINUTE),'System','UTC'))) < 0 THEN '" . TQ_("Available") . "'
				ELSE CONCAT(ROUND(TIME_TO_SEC(TIMEDIFF(ca.end,CONVERT_TZ(DATE_SUB(NOW(), INTERVAL co.default_delay_minutes MINUTE),'System','UTC'))) / 60),'&emsp;" . TQ_("minutes") . "')
			END AS availableinmin,
			CASE WHEN oq.operator_id IS NULL THEN 
				CONCAT('')
			ELSE CONCAT('<span class=\'text-info\'>', oq.firstName,' ',oq.lastName,'</span>')
			END AS assignedoperator,
			CASE WHEN oq.operator_id IS NULL THEN 
				CONCAT('')
			ELSE CONCAT(' &emsp; ', cq.sortorder ,'&emsp;')
			END AS ordr,
			CASE WHEN oq.operator_id IS NULL THEN 
				CONCAT('<span data-toggle=\'tooltip\' title=\'" . TQ_("Not assigned, select to assign") . "\'><input  type=\'checkbox\' name=\'c', c.case_id, '\' value=\'', c.case_id, '\' /></span>')
			ELSE CONCAT('<a href=\"?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_id&amp;unassign=', cq.case_queue_id, '\" data-toggle=\'tooltip\' title=\'" . TQ_("Click to unassign") ."\'><i class=\'fa fa-trash-o fa-lg text-danger\'></i></a>')
			END AS flag	
		FROM `case` as c
		JOIN questionnaire as q ON (q.questionnaire_id = c.questionnaire_id and q.enabled = 1)
		JOIN outcome as o ON (o.outcome_id = c.current_outcome_id AND o.outcome_type_id = 1)
		JOIN sample as s ON (s.sample_id = c.sample_id $s)
		JOIN sample_import as si ON (s.import_id = si.sample_import_id AND si.enabled = 1)
		JOIN questionnaire_sample as qs ON (qs.questionnaire_id = $questionnaire_id AND qs.sample_import_id = s.import_id)
		LEFT JOIN `call` as ca ON (ca.call_id = c.last_call_id)
		LEFT JOIN outcome as co ON (co.outcome_id = ca.outcome_id)
		LEFT JOIN case_queue as cq ON (cq.case_id = c.case_id)
		LEFT JOIN operator as oq ON (cq.operator_id = oq.operator_id)
		WHERE c.current_operator_id IS NULL $q $o
		ORDER BY c.case_id ASC";

//	print $sql;

	print ("<form method=\"post\" action=\"?questionnaire_id=$questionnaire_id&sample_import_id=$sample_id\">");

	$datacol = array('case_id','samples','timezone','time','nrattempts','nrcalls','outcomes','availableinmin','assignedoperator','ordr','flag');
	$headers = array(T_("Case id"),T_("Sample"),T_("Timezone"),T_("Time NOW"),T_("Call attempts"),T_("Calls"),T_("Outcome"),T_("Available in"),T_("Assigned to"),T_("Order"),"<i class='fa fa-check-square-o fa-lg'></i>");
	
	if (isset($_GET['sample_import_id'])){ 	unset($datacol[1]);  unset($headers[1]); }

	xhtml_table($db->GetAll($sql),$datacol,$headers,"tclass",false,false,"bs-table");
	
	$sql = "SELECT operator_id as value,CONCAT(firstName,' ', lastName) as description, '' selected
		FROM operator
		WHERE enabled = 1";
	
	$rs3 = $db->GetAll($sql);

	print "<h4 class='col-sm-offset-5 pull-left text-right control-label'>" . T_("Assign selected cases to") . "&ensp;" . T_("operator") . "&ensp;:&emsp;</h4> ";
	display_chooser($rs3, "operator_id", "operator_id",true,false,false,true,false,true,"pull-left");
	
	print "&emsp;<button class='btn btn-default' type='submit' data-toggle='tooltip' title='" . T_("Assign cases to operator queue") . "'><i class='fa fa-link fa-lg text-primary'></i>&emsp;" . T_("Assign") . "</button>";
	print "</form></br>";

	return true;
}

if (isset($_POST['operator_id']) && !empty($_POST['operator_id']))
{
	$operator_id = intval($_POST['operator_id']);

	$db->StartTrans();

	$sql = "SELECT MAX(sortorder)
		FROM case_queue
		WHERE operator_id = '$operator_id'";

	$sortorder = $db->GetOne($sql);

	foreach($_POST as $key => $val)
	{
		$sortorder++;

		if (substr($key,0,1) == "c")
		{
			$sql = "INSERT INTO case_queue (case_id,operator_id,sortorder)
				VALUES ('" . bigintval($val) . "', '$operator_id', '$sortorder')";

			$db->Execute($sql);
		}
	}

	$db->CompleteTrans();
}

if (isset($_GET['unassign']))
{
	$case_queue_id = bigintval($_GET['unassign']);

	$db->StartTrans();

	$sql = "SELECT operator_id 
		FROM case_queue
		WHERE case_queue_id = '$case_queue_id'";

	$operator_id = $db->GetOne($sql);

	$sql = "DELETE FROM case_queue
		WHERE case_queue_id = '$case_queue_id'";

	$db->Execute($sql);

	$sql = "SELECT case_queue_id
		FROM case_queue
		WHERE operator_id = '$operator_id'
		ORDER BY sortorder ASC";

	$rs = $db->GetAll($sql);

	$sortorder = 1;
	foreach($rs as $r)
	{
		$sql = "UPDATE case_queue
			SET sortorder = '$sortorder'
			WHERE case_queue_id = '{$r['case_queue_id']}'";

		$db->Execute($sql);

		$sortorder++;			
	}

	$db->CompleteTrans();
}

xhtml_head(T_("Case status and assignment"),true,$css,$js_head);//array("../css/table.css"),array("../js/window.js")
echo "<a href='' onclick='history.back();return false;' class='btn btn-default pull-left' ><i class='fa fa-chevron-left text-primary'></i>&emsp;" . T_("Go back") . "</a>
		<i class='fa fa-question-circle fa-3x text-primary pull-right btn' data-toggle='modal' data-target='.inform'></i>";
 ?>
<div class="modal fade inform" id="inform" tabindex="-1" role="dialog" aria-labelledby="inform" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
		<div class="modal-header">
          <h3 class="modal-title"><small class="text-info"><?php echo T_("INFORMATION");?></small></h4>
        </div>
		<div class="modal-body">
			<p><?php echo T_("List cases by questionnaire and sample with the ability to assign them to be called next in a queue by a particular operator. <br/>If you assign cases to an operator, it will override the normal scheduling process and call them as soon as the operator is available.");?></p>		
		</div>
	  <div class="modal-footer">
        <button type="button" class="btn btn-info" data-dismiss="modal"><?php echo T_("OK");?></button>
      </div>
    </div>
  </div>
</div>
<?php

$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);
$sample_import_id = false;
if (isset($_GET['sample_import_id']) && !empty($_GET['sample_import_id'])) $sample_import_id = bigintval($_GET['sample_import_id']);
$outcome_id = false;

print "<div class='form-group '><h3 class=' col-sm-2 text-right'>" . T_("Questionnaire") . ":</h3>";
display_questionnaire_chooser($questionnaire_id, false, "pull-left", "form-control");
if ($questionnaire_id){
	print "<h3 class=' col-sm-2 text-right'>" . T_("Sample") . ":</h3>";
	display_sample_chooser($questionnaire_id,$sample_import_id,false, "pull-left", "form-control");
	print "</div>
	 <div class='clearfix'></div>";
	
	case_status_report($questionnaire_id,$sample_import_id,$outcome_id);
}
xhtml_foot($js_foot);
?>
<script type="text/javascript">
$('#bs-table').bdt();
$('input').iCheck({
	//checkboxClass: 'icheckbox_square-blue',
	//increaseArea: '30%'
	checkboxClass: 'fa fa-lg ', // text-primary
	checkedCheckboxClass: 'fa-check-square-o text-primary',
	uncheckedCheckboxClass: 'fa-square-o'	
});
</script>
