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

xhtml_head(T_("Display extension status"),true,array("../css/table.css"),array("../js/window.js"));

if (isset($_GET['edit']))
{
  $sql = "SELECT extension,password,current_operator_id
          FROM extension
          WHERE extension_id = " . intval($_GET['edit']);

  $rs = $db->GetRow($sql);

  print "<p><a href='?'>" . T_("Go back") . "</a></p>";
?>
  <form enctype="multipart/form-data" action="?" method="post">
  <p><?php  echo T_("Extension name (such as SIP/1000):"); ?> <input name="extension" type="text" value="<?php echo $rs['extension'];?>"/></p>
  <p><?php  echo T_("Extension password:"); ?> <input name="password" type="text" value="<?php echo $rs['password'];?>"/></p>
  <input name="extensionid" type="hidden" value="<?php echo intval($_GET['edit']);?>"/></p>
  <p><input type="submit" value="<?php  echo T_("Edit extension"); ?>" /></p>
<?php
  if (empty($rs['current_operator_id']))
  {
?>
  <br/>
  <p><input type="submit" name="delete" value="<?php  echo T_("Delete extension"); ?>" /></p>
  </form>
<?php
  }
  else
    print "<p>" . T_("Unassign the operator from this extension to be able to delete it") . "</p>";

}
else
{
  $sql=  "SELECT CONCAT('<a href=\'operatorlist.php?edit=',o.operator_id,'\'>',o.firstName,'</a>') as firstName,
                 CONCAT('<a href=\'?edit=',e.extension_id,'\'>',e.extension,'</a>') as extension,
                 IF(c.case_id IS NULL,IF(e.current_operator_id IS NULL,'list'
                 ,CONCAT('<a href=\'?unassign=',e.extension_id,'\'>". T_("Unassign")  ."</a>')),'". T_("End case to change assignment")."') as assignment, 
                 CASE e.status WHEN 0 THEN '" . T_("VoIP Offline") . "' ELSE '" . T_("VoIP Online") . "' END as status, 
                 CASE ca.state WHEN 0 THEN '" . T_("Not called") . "' WHEN 1 THEN '" . T_("Requesting call") . "' WHEN 2 THEN '" . T_("Ringing") . "' WHEN 3 THEN '" . T_("Answered") . "' WHEN 4 THEN '" . T_("Requires coding") . "' ELSE '" . T_("Done") . "' END as state,
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
  
  if ($msg != "")
    print "<p>$msg</p>";
  
  if (!empty($rs))
  {
    $sql = "SELECT o.operator_id as value, o.firstName as description
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
  }
  else
  	print "<p>" . T_("No extensions") . "</p>";
  
  print "<h2>" . T_("Add an extension") . "</h2>";
  ?>
  
  <form enctype="multipart/form-data" action="" method="post">
  	<p><?php  echo T_("Extension name (such as SIP/1000):"); ?> <input name="extension" type="text"/></p>
  	<p><?php  echo T_("Extension password:"); ?> <input name="password" type="text"/></p>
  	<p><input type="submit" value="<?php  echo T_("Add extension"); ?>" /></p>
  </form>
  
  <?php
}

xhtml_foot();

?>
