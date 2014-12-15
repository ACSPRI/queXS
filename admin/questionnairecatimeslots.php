<?php 
/**
 * Assign call attempt time slots to a questionnaire
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
 * @copyright Australian Consortium for Social and Political Research Inc (ACSPRI) 2011
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au queXS was writen for ACSPRI
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
 * Input functions
 */
include("../functions/functions.input.php");

global $db;


if (isset($_GET['questionnaire_id']) && isset($_GET['availability_group']))
{
	//need to add availability_group to questionnaire

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$availability_group = bigintval($_GET['availability_group']);

	$sql = "INSERT INTO questionnaire_timeslot(questionnaire_id,availability_group_id)
		VALUES('$questionnaire_id','$availability_group')";

	$db->Execute($sql);

}

if (isset($_GET['questionnaire_id']) && isset($_GET['ravailability_group']))
{
	//need to remove rsid from questionnaire

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$availability_group = bigintval($_GET['ravailability_group']);

	$sql = "DELETE FROM questionnaire_timeslot
		WHERE questionnaire_id = '$questionnaire_id'
		AND availability_group_id = '$availability_group'";

	$db->Execute($sql);
}


$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) 	$questionnaire_id = bigintval($_GET['questionnaire_id']);

xhtml_head(T_("Assign call attempt time slots to questionnaire"),true,false,array("../js/window.js"));

print "<p>" . T_("Assigning call attempt time slots to questionnaires will only allow cases to be attempted in a time slot for the n + 1th time where it has been called at least n times in all assigned timeslots") ."</p>";

print "<h3>" . T_("Select a questionnaire from the list below") . "</h3>";
display_questionnaire_chooser($questionnaire_id);


if ($questionnaire_id != false)
{

	$sql = "SELECT q.availability_group_id,a.description as description
		FROM questionnaire_timeslot as q, availability_group as a
		WHERE q.availability_group_id = a.availability_group_id
		AND q.questionnaire_id = '$questionnaire_id'";

	$qs = $db->GetAll($sql);

	if (empty($qs))
	{
		print "<h2>" . T_("There are no call attempt time slots selected for this questionnaire") . "</h2>";
	}
	else
	{
		print "<h2>" . T_("Call attempt time slots selected for this questionnaire") . "</h2>";
		foreach($qs as $q)
		{
			print "<p><a href=\"?questionnaire_id=$questionnaire_id&amp;ravailability_group={$q['availability_group_id']}\">{$q['availability_group_id']} - {$q['description']} (" . T_("Click to unassign") . ")</a></p>";
		}
	}

	$sql = "SELECT si.availability_group_id,si.description
		FROM availability_group as si
		LEFT JOIN questionnaire_timeslot as q ON (q.questionnaire_id = '$questionnaire_id' AND q.availability_group_id = si.availability_group_id)
		WHERE q.questionnaire_id is NULL";
	
	$qs = $db->GetAll($sql);

	if (!empty($qs))
	{


		print "<h2>" . T_("Add a call attempt time slot to this questionnaire:") . "</h2>";
		?>
		<form action="" method="get">
		<p><label for="availability_group"><?php  echo T_("Select call attempt time slot:"); ?></label><select name="availability_group" id="availability_group">
		<?php 
	
		foreach($qs as $q)
		{
			print "<option value=\"{$q['availability_group_id']}\">{$q['description']}</option>";
		}
	
		?>
		</select><br/>
		<input type="hidden" name="questionnaire_id" value="<?php  print($questionnaire_id); ?>"/>
    <input type="submit" name="add_availability" value="<?php echo TQ_("Add call attempt time slot") ?>"/></p>
		</form>
		<?php 
	}
}
xhtml_foot();


?>
