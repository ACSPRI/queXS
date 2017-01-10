<?php 
/**
 * List and create remote connections to questionnaire servers
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
 * @copyright Australian Consortium for Social and Political Research Inc (2011)
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
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
 * Authentication file
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

/**
 * Limesurvey functions
 */
include("../functions/functions.limesurvey.php");

$css = array(
"../include/bootstrap/css/bootstrap.min.css", 
"../css/custom.css"
			);
$js_head = array(
				);
$js_foot = array(
"../js/window.js",
"../js/custom.js"
				);
global $db;

xhtml_head(T_("Questionnaire services"),true,$css,$js_head);//array("../css/table.css"),array("../js/window.js")

$error = false;

if (isset($_POST['description']))
{
  $test = limerpc_init($_POST['rpc_url'],$_POST['username'],$_POST['password']);

  if ($test === true) {
  	$description = $db->qstr($_POST['description']);
  	$rpc_url = $db->qstr($_POST['rpc_url']);
  	$username = $db->qstr($_POST['username']);
  	$password = $db->qstr($_POST['password']);
  	$entry_url = $db->qstr($_POST['entry_url']);
  	
  	$sql = "INSERT INTO `remote` (description,rpc_url,username,password,entry_url)
  		VALUES ($description,$rpc_url,$username,$password,$entry_url)";
  
  	$db->Execute($sql);
  } else {
    $error = $test;
  }
}
	
//view groups
$sql = "SELECT id, rpc_url, username, description,
	CONCAT('<a href=\'?id=',id, '\'>". TQ_("Modify") . "</a>') as link 
	FROM remote";
	
	$rs = $db->GetAll($sql);

print "<div class='well'>" . T_("Questionnaire services available. Services include Limesurvey remote control.") . "</div>";

if ($error !== false) {
  print "<div class='alert alert-danger'>" . T_("Service not added. Error: ") . $error . "</div>";

}

if (empty($rs))
	print "<div class='alert alert-danger'>" . T_("No questionnaire services defined") . "</div>";
else{
	print "<div class='panel-body col-sm-6'>";
		xhtml_table($rs,array("id","description","link"),array(T_("ID"),T_("Service"),T_("Modify")),"table table-hover");
	print "</div>";
}

//add a service
?>
<div class=" panel-body col-sm-4"><form method="post" action="?">
	<h3><?php echo T_("Add new questionnaire service")," :";?></h3>
	<p><input type="text" class="textclass form-control" name="description" id="description" placeholder="<?php echo T_("Enter"),"&ensp;",T_("new"),"&ensp;",T_("Questionnaire service descripion"); ?>"/></p>
	<p><input type="text" class="textclass form-control" name="rpc_url" id="rpc_url" placeholder="<?php echo T_("RPC Url (eg: http://localhost/limesurvey/admin/remotecontrol)"); ?>"/></p>
	<p><input type="text" class="textclass form-control" name="username" id="username" placeholder="<?php echo T_("Username (eg: admin)"); ?>"/></p>
	<p><input type="text" class="textclass form-control" name="password" id="password" placeholder="<?php echo T_("Password (eg: password)"); ?>"/></p>
  <p><input type="text" class="textclass form-control" name="entry_url" id="entry_url" placeholder="<?php echo T_("Questionnaire entry Url (eg: http://localhost/limesurvey/)"); ?>"/></p>
	<p><input class="submitclass btn btn-default" type="submit" name="submit" value="<?php  echo T_("Add questionnaire serivce"); ?>"/></p>
</form></div>
<?php 
xhtml_foot($js_foot);
?>
