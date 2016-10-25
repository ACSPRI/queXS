<?php /**
 * Display extension status
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2010
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/software/ queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
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
require ("auth-admin.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

$msg = "";

if (isset($_GET))
{
  foreach($_GET as $key=>$val)
  {
    if (substr($key,0,12) == "operator_id_")
    {
      if (isset($_GET['extension_id']))
      {
        $ex = intval($_GET['extension_id']);
        $op = intval($val);

        $sql = "UPDATE `extension`
                SET current_operator_id = $op
                WHERE extension_id = $ex
                AND current_operator_id IS NULL";

        $db->Execute($sql);
      }
    }
  }
}

if (isset($_POST['extension']))
{
  $extension = $db->qstr($_POST['extension']);
  $password = $db->qstr($_POST['password']);
  $extension_id = "NULL";

  if (isset($_POST['extensionid']))
    $extension_id = intval($_POST['extensionid']);

  if (isset($_POST['delete']))
  {
    $sql = "DELETE FROM `extension`
            WHERE current_operator_id IS NULL
            AND extension_id = $extension_id";

    $rs = $db->Execute($sql);

    if (!$rs)
      $msg = ("Failed to delete extension. There may be an operator currently assigned to it");
  }
  else
  {
    if (!empty($_POST['extension']))
    {
     $sql = "INSERT INTO `extension` (extension_id,extension,password)
              VALUES ($extension_id,$extension,$password)
              ON DUPLICATE KEY UPDATE extension=$extension,password=$password";
    
     $rs = $db->Execute($sql);
    
      if (!$rs)
        $msg = T_("Failed to add extension. There already may be an extension of this name");
    }
  }
}

if (isset($_GET['unassign']))
{
  $e = intval($_GET['unassign']);

  $db->StartTrans();

  $sql = "SELECT e.current_operator_id
          FROM `extension` as e
          LEFT JOIN `case` as c ON (c.current_operator_id = e.current_operator_id)
          WHERE e.extension_id = $e
          AND c.case_id IS NULL";

  $cid = $db->GetOne($sql);

  if (!empty($cid))
  {
    $sql = "UPDATE `extension` as e
            SET current_operator_id = NULL
            WHERE extension_id = $e
            AND current_operator_id = $cid";
    
    $db->Execute($sql);
  }

  $db->CompleteTrans();
}

xhtml_head(T_("Extensions & status"),true,array("../include/bootstrap/css/bootstrap.min.css","../css/custom.css"),array("../js/window.js"));
?>
<script type="text/javascript">	
//Password generator
upp = new Array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
low = new Array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
dig = new Array('0','1','2','3','4','5','6','7','8','9');
//sym = new Array('~','!','@','#','$','%','^','&','*','(',')','_','+','=','|',';','.','/','?','<','>','{','}','[',']');
// --------------------------------------------------------------------------------------------------------------------------------------------------------------
function rnd(x,y,z) { 
	var num;
	do {
		num = parseInt(Math.random()*z);
		if (num >= x && num <= y) break;
	} while (true);
return(num);
}
// --------------------------------------------------------------------------------------------------------------------------------------------------------------
function generate() {																
	var pwd = '';
	var res, s;
	var k = 0;
	var n = document.editext.number.value;
	var pass = new Array();
	var w = rnd(30,80,100);
	for (var r = 0; r < w; r++) {
		res = rnd(1,25,100); pass[k] = upp[res]; k++; 
		res = rnd(1,25,100); pass[k] = low[res]; k++;
		res = rnd(1,9,100); pass[k] = dig[res]; k++;
		//res = rnd(1,24,100); pass[k] = sym[res]; k++;		
	}
	for (var i = 0; i < n; i++) {
		s = rnd(1,k-1,100);
		pwd+= pass[s];
	}
	document.editext.password.value = pwd;
}
</script>

<?php

if (isset($_GET['edit']) || isset($_GET['addext']))
{
	if (isset($_GET['edit'])){	

	$sql = "SELECT extension,password,current_operator_id
          FROM extension
          WHERE extension_id = " . intval($_GET['edit']);

	$rs = $db->GetRow($sql);
	}

?>
	<div class="panel-body ">
	<h3 class="col-lg-offset-3"><?php if (isset($_GET['edit']))echo T_("Edit extension"); else echo T_("Add an extension");?></h3>
	<form enctype="multipart/form-data" action="?" method="post" name="editext" class="form-horizontal">
	<div class="form-group form-inline">
		<label class="control-label col-lg-3"><?php  echo T_("Extension name: ");?></label>
		<input name="extension" type="text" placeholder="<?php echo T_("such as SIP/1000");?>" maxlength="12" required value="<?php if (isset($_GET['edit']))echo $rs['extension'];?>" class="form-control"/>
	</div>
	<div class="form-group form-inline">
		<label class="control-label col-lg-3"><?php  echo T_("Extension password: ");?></label>
		<input name="password" type="text" required style="width:20em;" maxlength="50" value="<?php if (isset($_GET['edit'])) echo $rs['password'];?>" class="form-control pull-left" placeholder="<?php echo T_("Enter New Password");?>"/>&emsp;&emsp;<?php echo T_(" or ");?>&ensp;
		<input type="button" onclick="generate();" value="<?php echo T_("Generate");?>" class="btn btn-default fa" />&emsp;<?php echo T_("New password");?>&ensp;
		<input type="number" name="number" value="25" min="8" max="50" style="width:5em;"  class="form-control" />&ensp;<?php echo T_("characters long");?>
	</div>

	<div class="form-group form-inline">
		<div class='col-lg-3'>
			<a href='?' class='btn btn-default'><?php echo T_("Cancel") ;?></a>
		</div>

		<input type="submit" class="btn btn-primary pull-left" value="<?php if (isset($_GET['edit'])) echo T_("Save changes"); else echo T_("Add extension"); ?>" />
	
<?php if (isset($_GET['edit'])){?>	
	
	<input name="extensionid" type="hidden" value="<?php echo intval($_GET['edit']);?>"/>
	
<?php if (empty($rs['current_operator_id'])) { ?>
		
	<input type="submit" name="delete" class="btn btn-danger col-lg-offset-2 pull-left" value="<?php  echo T_("Delete extension"); ?>" />
		
<?php 	} else 
		print "</br></br><b class='well text-danger'>" . T_("Unassign the operator from this extension to be able to delete it") . "</b>";
	} 

	print "</div></form></div>";
}
else
{
  $sql=  "SELECT CONCAT('<a href=\'operatorlist.php?edit=',o.operator_id,'\'>',o.firstName,'  ', o.lastname,'</a>') as firstName,
                 CONCAT('<a href=\'?edit=',e.extension_id,'\' class=\'\'>',e.extension,'</a>') as extension,
                 IF(c.case_id IS NULL,IF(e.current_operator_id IS NULL,'list'
                 ,CONCAT('<a href=\'?unassign=',e.extension_id,'\'>". TQ_("Unassign")  ."</a>')),'". TQ_("End case to change assignment")."') as assignment, 
                 CASE e.status WHEN 0 THEN '" . TQ_("VoIP Offline") . "' ELSE '" . TQ_("VoIP Online") . "' END as status, 
                 CASE ca.state WHEN 0 THEN '" . TQ_("Not called") . "' WHEN 1 THEN '" . TQ_("Requesting call") . "' WHEN 2 THEN '" . TQ_("Ringing") . "' WHEN 3 THEN '" . TQ_("Answered") . "' WHEN 4 THEN '" . TQ_("Requires coding") . "' ELSE '" . TQ_("Done") . "' END as state,
                 CONCAT('<a href=\'supervisor.php?case_id=', c.case_id , '\'>' , c.case_id, '</a>') as case_id, SEC_TO_TIME(TIMESTAMPDIFF(SECOND,cal.start,CONVERT_TZ(NOW(),'SYSTEM','UTC'))) as calltime, 
                 e.status as vs,
                 e.extension_id
          FROM extension as e
          LEFT JOIN `operator` as o ON (o.operator_id = e.current_operator_id)
        	LEFT JOIN `case` as c ON (c.current_operator_id = o.operator_id)
        	LEFT JOIN `call_attempt` as cal ON (cal.operator_id = o.operator_id AND cal.end IS NULL and cal.case_id = c.case_id)
        	LEFT JOIN `call` as ca ON (ca.case_id = c.case_id AND ca.operator_id = o.operator_id AND ca.outcome_id= 0 AND ca.call_attempt_id = cal.call_attempt_id)
        	ORDER BY e.extension_id ASC";

  $rs = $db->GetAll($sql);
  
    print "<div class='panel-body'>";
  
  if ($msg != "")
    print "<p class='alert alert-warning'>$msg</p></br>";
  
  if (!empty($rs))
  {
    $sql = "SELECT o.operator_id as value, CONCAT(o.firstName,' ',o.lastname) as description
            FROM `operator` as o
            LEFT JOIN `extension` as e ON (e.current_operator_id = o.operator_id)
            WHERE e.extension_id IS NULL";

    $ers = $db->GetAll($sql);

    for ($i = 0; $i < count($rs); $i++)
    {
      if ($rs[$i]['assignment'] == "list")
        $rs[$i]['assignment'] = display_chooser($ers,"operator_id_" . $rs[$i]["extension_id"],"operator_id_" . $rs[$i]["extension_id"],true,"extension_id=".$rs[$i]["extension_id"],true,false,false,false);
    }

  	xhtml_table($rs,array("extension","firstName","assignment","status","case_id","state","calltime"),array(T_("Extension"),T_("Operator"),T_("Assignment"),T_("VoIP Status"),T_("Case ID"),T_("Call state"),T_("Time on call")),"tclass",array("vs" => "1"));
	print "</br>";
  }
  else
  	print "<p class='alert alert-warning'>" . T_("No extensions") . "</p>";
  
  print "<a href='?addext=addext' class='btn btn-primary '>" . T_("Add extension") . "</a>
		</div>";

}

xhtml_foot();

?>
