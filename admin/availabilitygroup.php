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

global $db;

xhtml_head(T_("Availability groups"),true,array("../css/table.css"),array("../js/window.js"));


if (isset($_GET['availability_group']))
{
	$availability_group = $db->qstr($_GET['availability_group']);
	
	$sql = "INSERT INTO `availability_group` (availability_group_id,description)
		VALUES (NULL,$availability_group)";

	$db->Execute($sql);
}
	
//view groups
$sql = "SELECT description,
	CONCAT('<a href=\'availability.php?availability_group=', availability_group_id, '\'>". T_("Modify") . "</a>') as link 
	FROM availability_group";
	
	$rs = $db->GetAll($sql);

print "<h3>" . T_("Availability groups")."</h3>";

print "<p>" . T_("Availability groups define periods of time of respondent availability.") . "</p>";

if (empty($rs))
	print "<p>" . T_("No availability groups") . "</p>";
else
	xhtml_table($rs,array("description","link"),array(T_("Availablity group"),T_("Modify")));


//add a note
?>
<form method="get" action="?">
	<p><input type="text" class="textclass" name="availability_group" id="availability_group"/><input class="submitclass" type="submit" name="submit" value="<?php  echo T_("Add availability group"); ?>"/>
	</p>
</form>
<?php 

xhtml_foot();


?>
