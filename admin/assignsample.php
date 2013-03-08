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


	$sql = "INSERT INTO questionnaire_sample(questionnaire_id,sample_import_id,call_max,call_attempt_max,random_select,answering_machine_messages)
		VALUES('$questionnaire_id','$sid','$cm','$cam','$selecttype','$am')";

	$db->Execute($sql);

}

if (isset($_GET['questionnaire_id']) && isset($_GET['rsid']))
{
	//need to remove rsid from questionnaire

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sid = bigintval($_GET['rsid']);

	$sql = "DELETE FROM questionnaire_sample
		WHERE questionnaire_id = '$questionnaire_id'
		AND sample_import_id = '$sid'";

	$db->Execute($sql);

}


$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) 	$questionnaire_id = bigintval($_GET['questionnaire_id']);

xhtml_head(T_("Assign Sample: Select sample to assign"),true,false,array("../js/window.js"));
print "<h1>" . T_("Select a questionnaire from the list below") . "</h1>";
display_questionnaire_chooser($questionnaire_id);


if ($questionnaire_id != false)
{
	print "<h1>" . T_("Samples selected for this questionnaire") . "</h1>";

	$sql = "SELECT q.sample_import_id as sample_import_id,si.description as description, q.call_max, q.call_attempt_max, q.random_select
		FROM questionnaire_sample as q, sample_import as si
		WHERE q.sample_import_id = si.sample_import_id
		AND q.questionnaire_id = '$questionnaire_id'";

	$qs = $db->GetAll($sql);

	foreach($qs as $q)
	{
		if ($q['random_select'] == 0) $rs = T_("Sequentially selected");
		else $rs = T_("Randomly selected");
		print "<p><a href=\"?questionnaire_id=$questionnaire_id&amp;rsid={$q['sample_import_id']}\">{$q['sample_import_id']} - {$q['description']}: " . T_("Max calls:") . " {$q['call_max']}  " . T_("Max call attempts:") . " {$q['call_attempt_max']}  $rs (" . T_("Click to unassign") . ")</a></p>";
	}


	$sql = "SELECT si.sample_import_id,si.description
		FROM sample_import as si
		LEFT JOIN questionnaire_sample as q ON (q.questionnaire_id = '$questionnaire_id' AND q.sample_import_id = si.sample_import_id)
		WHERE q.questionnaire_id is NULL
		AND si.enabled = 1";
	
	$qs = $db->GetAll($sql);

	if (!empty($qs))
	{


		print "<h1>" . T_("Add a sample to this questionnaire:") . "</h1>";
		?>
		<form action="" method="get">
		<p><label for="sample"><?php  echo T_("Select sample:"); ?></label><select name="sample" id="sample">
		<?php 
	
		foreach($qs as $q)
		{
			print "<option value=\"{$q['sample_import_id']}\">{$q['description']}</option>";
		}
	
		?>
		</select><br/>
		<label for="call_max"><?php  echo T_("Max calls (0 for unlimited)"); ?></label><input type="text" name="call_max" id="call_max" value="0"/><br/>
		<label for="call_attempt_max"><?php  echo T_("Max call attempts (0 for unlimited)"); ?></label><input type="text" name="call_attempt_max" id="call_attempt_max" value="0"/>		<br/>
		<label for="answering_machine_messages"><?php  echo T_("Number of answering machine messages to leave per case (0 for never)"); ?></label><input type="text" name="answering_machine_messages" id="answering_machine_messages" value="1"/>		<br/>
		<label for="selecttype"><?php  echo T_("Select from sample randomly? (otherwise sequentially)"); ?></label><input type="checkbox" id = "selecttype" name="selecttype" />		<br/>
		<input type="hidden" name="questionnaire_id" value="<?php  print($questionnaire_id); ?>"/>
		<input type="submit" name="add_sample" value="Add sample"/></p>
		</form>
		<?php 
	}
}
xhtml_foot();


?>
