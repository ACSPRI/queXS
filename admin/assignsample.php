<?php 
/**
 * Assign sample(s) to a questionnaire
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
 */

/**
 * Configuration file
 */
include("../config.inc.php");

/**
 * Database file
 */
include ("../db.inc.php");

/** 
 * Authentication
 */
require ("auth-admin.php");

/**
 * XHTML functions
 */
include("../functions/functions.xhtml.php");

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
"../include/bootstrap-toggle/css/bootstrap-toggle.min.css",
"../css/custom.css"
			);
$js_head = array(
"../include/jquery/jquery.min.js",
"../include/bootstrap/js/bootstrap.min.js",
"../include/bootstrap-toggle/js/bootstrap-toggle.min.js",
"../js/window.js"
				);
$js_foot = array(
"../js/bootstrap-confirmation.js",
"../js/custom.js"
				);
global $db;

$error = "";


if (isset($_GET['questionnaire_id']) && isset($_GET['sample'])  && isset($_GET['call_max']) && isset($_GET['call_attempt_max']))
{
	//need to add sample to questionnaire

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sid = bigintval($_GET['sample']);
	$cm = bigintval($_GET['call_max']);
	$cam = bigintval($_GET['call_attempt_max']);
	$am = bigintval($_GET['answering_machine_messages']);
	$selecttype = 0;
	if (isset($_GET['selecttype'])) $selecttype = 1;
	$an = 0;
	if (isset($_GET['allownew'])) $an = 1;

	$sql = "SELECT MAX(sort_order) + 1 FROM questionnaire_sample";
	if(!$so = $db->GetOne($sql)) $so = 1;

	$sql = "INSERT INTO questionnaire_sample(questionnaire_id,sample_import_id,call_max,call_attempt_max,random_select,answering_machine_messages,allow_new,sort_order)
		VALUES('$questionnaire_id','$sid','$cm','$cam','$selecttype','$am', '$an', '$so')";

	$db->Execute($sql);

	if (isset($_GET['generatecases']))
	{

    //find the number of sample variables required
    $sql = "SELECT count(*)
            FROM sample_import_var_restrict
            WHERE sample_import_id = '$sid'
            AND type = 1";

    $varsrequired = $db->GetOne($sql);

    $addsample = false;

    if ($varsrequired > 0) {
      $addsample = true;
    }

    include_once("../functions/functions.operator.php");

    $db->StartTrans();

    $lime_sid = $db->GetOne("SELECT lime_sid FROM questionnaire WHERE questionnaire_id = '$questionnaire_id'");
    $testing = $db->GetOne("SELECT testing FROM questionnaire WHERE questionnaire_id = '$questionnaire_id'");
  
        //generate one case for each sample record and set outcome to 41
    $sql = "SELECT sample_id
      FROM sample
      WHERE import_id = '$sid'";

    $rs = $db->GetAll($sql);

    foreach($rs as $r)
    {
      set_time_limit(30);
      if (add_case($r['sample_id'],$questionnaire_id,"NULL",$testing,41, $addsample) === false) {
        $error = "Could not add case - please ensure there enough additional attributes available in your Limesurvey participant table";
        break;
      }
    }

    $db->CompleteTrans();
  }
}

if (isset($_POST['edit']))
{
	//need to add sample to questionnaire
	$questionnaire_id = bigintval($_POST['questionnaire_id']);
	$sid = bigintval($_POST['sample_import_id']);
	$cm = bigintval($_POST['call_max']);
	$cam = bigintval($_POST['call_attempt_max']);
	$am = bigintval($_POST['answering_machine_messages']);
	$selecttype = 0;
	if (isset($_POST['selecttype'])) $selecttype = 1;
	$an = 0;
	if (isset($_POST['allownew'])) $an = 1;

	$sql = "UPDATE questionnaire_sample
		SET call_max = '$cm',
		call_attempt_max = '$cam',
		random_select = '$selecttype',
    answering_machine_messages = '$am',
    allow_new = '$an'
		WHERE questionnaire_id = '$questionnaire_id'
		AND sample_import_id = '$sid'";

	$db->Execute($sql);
}


if (isset($_GET['questionnaire_id']) && isset($_GET['rsid']))
{
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sid = bigintval($_GET['rsid']);

	if (isset($_GET['edit']))
	{
		$subtitle = T_("Edit assignment parameters");
		xhtml_head(T_("Assign samples to questionnaire: "),true,$css,$js_head,false,false,false,$subtitle);

		$sql = "SELECT si.description as description, 
				qr.description as qdescription,
				q.call_max,
				q.call_attempt_max,
				q.random_select,
				q.answering_machine_messages,
				q.allow_new
			FROM questionnaire_sample as q, sample_import as si, questionnaire as qr
			WHERE q.sample_import_id = si.sample_import_id
			AND q.questionnaire_id = '$questionnaire_id'
			AND si.sample_import_id = '$sid'
			AND qr.questionnaire_id = q.questionnaire_id";

		$qs = $db->GetRow($sql);

		//print "<h1>" . T_("Edit sample details") . "<h1>";
		print "	<p><a href='?questionnaire_id=$questionnaire_id' class='btn btn-default '><i class='fa fa-chevron-left fa-lg text-primary'></i>&emsp;" . T_("Go back") . "</a></p>
				<h2 class='col-lg-6'>" . T_("Questionnaire") . ": <span class='text-primary'>" . $qs['qdescription'] . "</span></h2>
				<h2 class='col-lg-6'>" . T_("Sample") . ": <span class='text-primary'>" . $qs['description'] . "</span></h2>
				<div class='clearfix'></div><div class='panel-body form-group'>";

		$allownew = $selected ="";
		if ($qs['random_select'] == 1)
			$selected = "checked=\"checked\"";
		if ($qs['allow_new'] == 1)
			$allownew = "checked=\"checked\"";
		?>
		<form action="?questionnaire_id=<?php echo $questionnaire_id;?>" method="post" class="form-horizontal">
		
		<label for="call_max" class="control-label col-sm-4"><?php echo T_("Max calls");?></label>
		<div class="col-sm-1"><input type="number" min="0" max="20" style="width:6em;" name="call_max" id="call_max" value="<?php echo $qs['call_max'];?>" class="form-control"/></div>
		<label class="control-label text-info"><?php echo "0 = " . T_("Unlimited");?></label><br/><br/>
		<label for="call_attempt_max" class="control-label col-sm-4"><?php echo T_("Max call attempts");?></label>
		<div class="col-sm-1"><input type="number" min="0" max="20" style="width:6em;" name="call_attempt_max" id="call_attempt_max" value="<?php echo $qs['call_attempt_max'];?>" class="form-control"/></div>
		<label class="control-label text-info"><?php echo "0 = " . T_("Unlimited");?></label><br/><br/>
		<label for="answering_machine_messages" class="control-label col-sm-4"><?php echo T_("Number of answering machine messages to leave per case");?></label>
		<div class="col-sm-1"> <input type="number" min="0" max="20" style="width:6em;" name="answering_machine_messages" id="answering_machine_messages" value="<?php echo $qs['answering_machine_messages'];?>" class="form-control"/></div>
		<label class="control-label text-info"><?php echo "0 = " . T_("Never");?></label><br/><br/>
		<label for="selecttype" class="control-label col-sm-4"><?php echo T_("Select from sample randomly?");?></label>
		<div class="col-sm-1"><input type="checkbox" id = "selecttype" name="selecttype" <?php echo $selected;?> data-toggle="toggle" data-size="small" data-on="<?php echo T_("Yes");?>" data-off="<?php echo T_("No");?>" data-width="85"/></div>
		<label class="control-label text-info"><?php echo T_("No") ." = ". T_("Sequentially");?></label><br/><br/>
		<label for="allownew" class="control-label col-sm-4"><?php echo T_("Allow new numbers to be drawn?");?></label>
		<div class="col-sm-1"><input type="checkbox" id = "allownew" name="allownew" <?php echo $allownew;?> class="col-sm-1" data-toggle="toggle" data-size="small" data-on="<?php echo T_("Yes");?>" data-off="<?php echo T_("No");?>" data-width="85"/></div><br/><br/><br/>
		<input type="hidden" name="questionnaire_id" value="<?php  print($questionnaire_id); ?>"/>
		<input type="hidden" name="sample_import_id" value="<?php  print($sid); ?>"/>
		<div class="col-sm-offset-4 col-sm-3"><button type="submit" name="edit" class="btn btn-primary"><i class="fa fa-floppy-o fa-lg"></i>&emsp;<?php echo T_("Save changes");?></button></div>
		</form></div>

		<?php 
		xhtml_foot($js_foot);
		die();
	}
	else if (isset($_GET['sort']) && ($_GET['sort'] == "up" || $_GET['sort'] == "down"))   
	{
		$cso = $db->GetOne("	SELECT sort_order
				FROM questionnaire_sample
				WHERE questionnaire_id = $questionnaire_id 
				AND sample_import_id = $sid");

		if ($_GET['sort'] == "up")
		{	//find previous in sort order
			$dir = "<";
		}
		else if ($_GET['sort'] == "down")
		{	//find next in sort order
			$dir = ">";
		}

		$sql = "SELECT sample_import_id,sort_order
	            FROM questionnaire_sample
	            WHERE questionnaire_id = $questionnaire_id
	            AND sort_order $dir $cso
	            ORDER BY sort_order DESC LIMIT 1";

		$rs = $db->GetRow($sql);
		
		//do a swap
		$ssid = $rs['sample_import_id'];
		$sso = $rs['sort_order'];

		$sql = "UPDATE questionnaire_sample
		     SET sort_order = $sso
		     WHERE sample_import_id = $sid
		     AND questionnaire_id = $questionnaire_id";

		$db->Execute($sql);

		$sql = "UPDATE questionnaire_sample
		     SET sort_order = $cso
		     WHERE sample_import_id = $ssid
		     AND questionnaire_id = $questionnaire_id";

		$db->Execute($sql);
		
		unset($_GET['sort']);
	}
	else
	{
		//need to remove rsid from questionnaire
		$sql = "DELETE FROM questionnaire_sample
			WHERE questionnaire_id = '$questionnaire_id'
			AND sample_import_id = '$sid'";
		$db->Execute($sql);
		
		// update sort_order after unassigning 
		$db->Execute("SELECT @i := 0");
		$db->Execute("UPDATE `questionnaire_sample` SET sort_order = @i:=@i+1 WHERE 1=1 ORDER BY sort_order ASC");
	}
}


$subtitle = T_("List & Add Sample");
xhtml_head(T_("Assign samples to questionnaires"),true,$css,$js_head,false,false,false,$subtitle);//array("../css/table.css"),array("../js/window.js")

print "<a href='' onclick='history.back();return false;' class='btn btn-default pull-left'><i class='fa fa-chevron-left fa-lg text-primary'></i>&emsp;" . T_("Go back") . "</a>";

	
$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) 	$questionnaire_id = bigintval($_GET['questionnaire_id']);	
	
print "<div class='form-group clearfix'><h2 class='col-lg-4 text-right'><i class='fa fa-link text-primary'></i>&emsp;" . T_("Select a questionnaire") . "</h2>";
display_questionnaire_chooser($questionnaire_id,false, "pull-left btn", "form-control ");
print "</div>";

if ($questionnaire_id != false)
{
  if ($error != "") {
    print "<div class='alert text-danger'>$error</div>";
  }
  
  print "<div class='panel-body'>
			<h3 class=''><i class='fa fa-list-ul text-primary'></i>&emsp;". T_("Samples assigned to questionnaire") ."  <span class='text-primary'>" . $db->GetOne("SELECT description from questionnaire WHERE questionnaire_id = $questionnaire_id") . "</span></h3>";

	$sql = "SELECT q.sort_order, si.description as description,si.sample_import_id, 
			CASE WHEN q.call_max = 0 THEN '". TQ_("Unlimited") ."' ELSE q.call_max END as call_max,
			CASE WHEN q.call_attempt_max = 0 THEN '". TQ_("Unlimited") . "' ELSE q.call_attempt_max END AS call_attempt_max,
			CASE WHEN q.random_select = 0 THEN '". TQ_("Sequential") ."' ELSE '". TQ_("Random") . "' END as random_select,
			CASE WHEN q.answering_machine_messages = 0 THEN '". TQ_("Never") . "' ELSE q.answering_machine_messages END as answering_machine_messages,
			CASE WHEN q.allow_new = 0 THEN '". TQ_("No") ."' ELSE '".TQ_("Yes")."' END as allow_new,
			CONCAT('<a href=\"?edit=edit&amp;questionnaire_id=$questionnaire_id&amp;rsid=', si.sample_import_id ,'\" data-toggle=\'tooltip\' title=\'". TQ_("Edit") ."\' class=\'btn center-block\'><i class=\'fa fa-pencil-square-o fa-lg\'></i></a>') as edit,
			CONCAT('<a href=\'\' data-toggle=\'confirmation\' data-title=\'".TQ_("ARE YOU SURE?")."\' data-btnOkLabel=\'&emsp;".TQ_("Yes")."\' data-btnCancelLabel=\'&emsp;".TQ_("No")."\' data-placement=\'top\' data-href=\"?questionnaire_id=$questionnaire_id&amp;rsid=', si.sample_import_id ,'\" class=\'btn center-block\'><i class=\'fa fa-chain-broken fa-lg\' data-toggle=\'tooltip\' title=\'". TQ_("Click to unassign") ."\'></i></a>') as unassign
			FROM questionnaire_sample as q, sample_import as si
			WHERE q.sample_import_id = si.sample_import_id
			AND q.questionnaire_id = '$questionnaire_id'
			ORDER BY q.sort_order ASC";
	$qs = $db->GetAll($sql);

  if (!empty($qs))
  {
     $co = count($qs);
     if ($co > 1)
     {
       for($i = 0; $i < $co; $i++)
       {
        $down = "<a href='?questionnaire_id=$questionnaire_id&amp;sort=down&amp;rsid={$qs[$i]['sample_import_id']}' data-toggle=\"tooltip\" title=\"". T_("Pull step Down") ."\"><i class=\"fa fa-arrow-down fa-lg\"></i></a>";
        $up = "<a href='?questionnaire_id=$questionnaire_id&amp;sort=up&amp;rsid={$qs[$i]['sample_import_id']}' data-toggle=\"tooltip\" title=\"". T_("Push step Up") ."\"><i class=\"fa fa-arrow-up fa-lg\"></i></a>";
         if ($i == 0) //down only
         {
          $qs[$i]['sort_order'] = "<div>&emsp;&emsp;   <span class=\"badge\">" . $qs[$i]['sort_order'] . "</span>&emsp;" . $down . "</div>";
         }
         else if ($i == ($co - 1)) //up only
         {
          $qs[$i]['sort_order'] = "<div style=\"min-width:5em;\"> " .$up . "&emsp;<span class=\"badge\">" . $qs[$i]['sort_order'] . "</span>"; 
         }
         else
         {
          $qs[$i]['sort_order'] = "<div> " . $up . "&emsp;<span class=\"badge\">" . $qs[$i]['sort_order'] . "</span>&emsp;" . $down . "</div>";
         }
       }
     }
     else
       $qs[0]['sort_order'] = "&emsp;<span class=\"badge\">" . $qs[0]['sort_order'] . "</span>&emsp;";


    xhtml_table($qs,array("sort_order","description","call_max","call_attempt_max","answering_machine_messages","random_select","allow_new","edit","unassign"),array(T_("Sort order"),T_("Sample"), T_("Max calls"), T_("Max call attempts"), T_("Answering machine messages"), T_("Selection type"), T_("Allow new numbers to be drawn?"), T_("Edit"), T_("Unassign sample")));
  }
	else
		print "<div class='alert text-danger'><h4>". T_("No samples selected for this questionnaire") ."</h4></div>";
	print"</div>";
	
	$sql = "SELECT si.sample_import_id,si.description
		FROM sample_import as si
		LEFT JOIN questionnaire_sample as q ON (q.questionnaire_id = '$questionnaire_id' AND q.sample_import_id = si.sample_import_id)
		WHERE q.questionnaire_id is NULL
		AND si.enabled = 1";
	$qs = $db->GetAll($sql);
	
	if (!empty($qs))
	{
		print "<div class='clearfix '></div>";
		print "<div class='panel-body form-group'><h3 class='text-primary col-lg-offset-4'>" . T_("Add a sample to this questionnaire:") . "</h3>";
		?>
		<form action="" method="get" class="form-horizontal">
		<label for="sample" class="control-label  col-lg-4"><?php  echo T_("Select sample:");?></label>
		<div class="col-lg-4"><select name="sample" id="sample" class="form-control " >
		<?php foreach($qs as $q) { print "<option value=\"{$q['sample_import_id']}\">{$q['description']}</option>"; } ?> </select></div><br/><br/>
		
		<label for="call_max" class="control-label col-lg-4"><?php echo T_("Max calls");?></label>
		<div class="col-sm-1"><input type="number" min="0" max="20" style="width:6em;" name="call_max" id="call_max" value="0" class="form-control"/></div>
		<label class="control-label text-info"><?php echo "0 = " . T_("Unlimited");?></label><br/><br/>
		
		<label for="call_attempt_max" class="control-label col-lg-4"><?php echo T_("Max call attempts");?></label>
		<div class="col-sm-1"><input type="number" min="0" max="20" style="width:6em;" name="call_attempt_max" id="call_attempt_max" value="0" class="form-control"/></div>
		<label class="control-label text-info"><?php echo "0 = " . T_("Unlimited");?></label><br/><br/>
		
		<label for="answering_machine_messages" class="control-label col-lg-4"><?php echo T_("Number of answering machine messages to leave per case");?></label>
		<div class="col-sm-1"><input type="number" min="0" max="20" style="width:6em;" name="answering_machine_messages" id="answering_machine_messages" value="0" class="form-control"/></div>
		<label class="control-label text-info"><?php echo "0 = " . T_("Never");?></label><br/><br/>
		
		<label for="selecttype" class="control-label col-lg-4"><?php echo T_("Select from sample randomly?");?></label>
		<div class="col-sm-1"><input type="checkbox" id = "selecttype" name="selecttype" data-toggle="toggle" data-size="small" data-on="<?php echo T_("Yes");?>" data-off="<?php echo T_("No");?>" data-width="85"/></div>
		<label class="control-label text-info"><?php echo T_("No") ." = ". T_("Sequentially");?></label><br/><br/>
		
		<label for="allownew" class="control-label col-lg-4"><?php echo T_("Allow new numbers to be drawn?");?></label>
		<div class="col-sm-1"><input type="checkbox" id = "allownew" name="allownew" checked="checked" class="col-sm-1" data-toggle="toggle" data-size="small" data-on="<?php echo T_("Yes");?>" data-off="<?php echo T_("No");?>" data-width="85"/></div>
		<label class="control-label text-info"><?php   ;?></label><br/><br/><br/>
		
		<?php $self_complete = $db->GetOne("SELECT self_complete FROM questionnaire WHERE questionnaire_id = '$questionnaire_id'");
		if ($self_complete) {?>
		<label for="generatecases" class="control-label col-lg-4"><?php echo T_("Generate cases for all sample records and set outcome to 'Self completion email invitation sent'?");?></label>
		<div class="col-sm-1"><input type="checkbox" id = "generatecases" name="generatecases" class="col-sm-1" data-toggle="toggle" data-size="small" data-on="<?php echo T_("Yes");?>" data-off="<?php echo T_("No");?>" data-width="85"/></div>
		<em class="control-label"> * <?php echo T_("Ideal if you intend to send an email invitation to sample members before attempting to call using queXS");?></em>
		<div class='clearfix '></div></br>
		<?php }?>

		<input type="hidden" name="questionnaire_id" value="<?php print($questionnaire_id);?>"/>
		
		<div class="col-lg-offset-4 col-lg-3"><button type="submit" name="add_sample" class="btn btn-primary"><i class="fa fa-plus-circle fa-lg"></i>&emsp;<?php echo T_("Add sample");?></button></div>
		
		</form></div>
		<?php 
	}
}

xhtml_foot($js_foot);
?>
<script type="text/javascript">
$('[data-toggle="confirmation"]').confirmation()
</script>
