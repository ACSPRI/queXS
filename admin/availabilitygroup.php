<?php 
/**
 * List and create availability groups
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
 * XHTML functions
 */
include("../functions/functions.xhtml.php");

/**
 * Display functions
 */
include("../functions/functions.display.php");

/**
 * Limesurvey functions
 */
include("../functions/functions.limesurvey.php");

/**
 * Operator functions
 */
include("../functions/functions.operator.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

$css = array(
"../include/bootstrap-3.3.2/css/bootstrap.min.css", 
"../css/custom.css"
			);
$js_head = array(
				);
$js_foot = array(
"../js/window.js",
"../js/custom.js"
				);
global $db;

xhtml_head(T_("Time slots"),true,$css,$js_head);//array("../css/table.css"),array("../js/window.js")

if (isset($_POST['subdel']))
{
	$availability_group = intval($_POST['availability_group']);

	$db->StartTrans();

	$sql = "DELETE FROM availability
		WHERE availability_group_id = $availability_group";

	$db->Execute($sql);

	$sql = "DELETE FROM questionnaire_availability
		WHERE availability_group_id = $availability_group";

	$db->Execute($sql);

	$sql = "DELETE FROM availability_group
		WHERE availability_group_id = $availability_group";

	$db->Execute($sql);

	$db->CompleteTrans();
}
else if (isset($_POST['availability_group']))
{
	$availability_group = $db->qstr($_POST['availability_group']);
	
	$sql = "INSERT INTO `availability_group` (availability_group_id,description)
		VALUES (NULL,$availability_group)";

	$db->Execute($sql);
}
	
//view groups
$sql = "SELECT availability_group_id,description,
	CONCAT('<a href=\'availability.php?availability_group=', availability_group_id, '\'>". TQ_("Modify") . "</a>') as link 
	FROM availability_group";
	
	$rs = $db->GetAll($sql);

print "<div class='well'>" . T_("Time slots define periods of time during particular days of the week. These are used for the availability function and also the call attempt time slot function.") . "</div>";

if (empty($rs))
	print "<div class='alert alert-danger'>" . T_("No time slots") . "</div>";
else{
	print "<div class='panel-body col-sm-6'>";
		xhtml_table($rs,array("availability_group_id","description","link"),array(T_("ID"),T_("Time slot name"),T_("Modify")),"table table-hover");
	print "</div>";
}

//add a time slot (ex- availablity group)  
?>
<div class=" panel-body col-sm-4"><form method="post" action="?">
	<h3><?php echo T_("Add new time slot")," :";?></h3>
	<p><input type="text" class="textclass form-control" name="availability_group" id="availability_group" placeholder="<?php echo T_("Enter"),"&ensp;",T_("new"),"&ensp;",T_("Time slot name"); ?>"/></p>
	<p><input class="submitclass btn btn-default" type="submit" name="submit" value="<?php  echo T_("Add time slot"); ?>"/></p>
</form></div>
<?php 
xhtml_foot($js_foot);
?>
