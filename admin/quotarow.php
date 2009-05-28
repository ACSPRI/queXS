<?
/**
 * Set quota's for answered questions and be able to exclude sample records by row 
 * instead of an entire sample
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
 * @copyright Deakin University 2007,2008,2009
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

/**
 * Limesurvey functions
 */
include("../functions/functions.limesurvey.php");

/**
 * Operator functions
 */
include("../functions/functions.operator.php");


global $db;


if (isset($_GET['questionnaire_id']) && isset($_GET['sgqa'])  && isset($_GET['value']) && isset($_GET['completions']) && isset($_GET['sample_import_id']) && isset($_GET['comparison']) && isset($_GET['exclude_var']) && isset($_GET['exclude_val']))
{
	//need to add quota

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sample_import_id = bigintval($_GET['sample_import_id']);
	$value = $db->quote($_GET['value']);
	$completions = $db->quote($_GET['completions']);
	$sgqa = $db->quote($_GET['sgqa']);
	$comparison = $db->quote($_GET['comparison']);
	$exclude_val = $db->quote($_GET['exclude_val']);
	$exclude_var = $db->quote($_GET['exclude_var']);

	$sql = "INSERT INTO questionnaire_sample_quota_row(questionnaire_id, sample_import_id, lime_sgqa,value,completions,comparison,exclude_var,exclude_val)
		VALUES ($questionnaire_id, $sample_import_id, $sgqa, $value, $completions, $comparison, $exclude_var, $exclude_val)";

	$db->Execute($sql);

	//Make sure to calculate on the spot
	update_quotas($questionnaire_id);
}

if (isset($_GET['questionnaire_id']) && isset($_GET['questionnaire_sample_quota_row_id']))
{
	//need to remove quota

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$questionnaire_sample_quota_row_id = bigintval($_GET['questionnaire_sample_quota_row_id']);

	$db->StartTrans();

	$sql = "DELETE FROM questionnaire_sample_quota_row
		WHERE questionnaire_sample_quota_row_id = '$questionnaire_sample_quota_row_id'";

	$db->Execute($sql);

	$sql = "DELETE FROM questionnaire_sample_quota_row_exclude
		WHERE questionnaire_sample_quota_row_id = '$questionnaire_sample_quota_row_id'";

	$db->Execute($sql);

	$db->CompleteTrans();

}

$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) 	$questionnaire_id = bigintval($_GET['questionnaire_id']);

xhtml_head(T_("Quota row management"),true,false,array("../js/window.js"));
print "<h1>" . T_("Select a questionnaire from the list below") . "</h1>";

$sql = "SELECT questionnaire_id as value,description, CASE WHEN questionnaire_id = '$questionnaire_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
 	FROM questionnaire";
display_chooser($db->GetAll($sql),"questionnaire","questionnaire_id");


if ($questionnaire_id != false)
{
	$sample_import_id = false;
	if (isset($_GET['sample_import_id'])) 	$sample_import_id = bigintval($_GET['sample_import_id']);

	print "<h1>" . T_("Select a sample from the list below") . "</h1>";
	
	$sql = "SELECT s.sample_import_id as value,s.description, CASE WHEN s.sample_import_id = '$sample_import_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	 	FROM sample_import as s, questionnaire_sample as q
		WHERE q.questionnaire_id = $questionnaire_id
		AND q.sample_import_id = s.sample_import_id";

	display_chooser($db->GetAll($sql),"sample","sample_import_id",true,"questionnaire_id=$questionnaire_id");

	if ($sample_import_id != false)
	{
		print "<h1>" . T_("Current row quotas (click to delete)") . "</h1>";
		
		$sql = "SELECT questionnaire_sample_quota_row_id,lime_sgqa,value,completions,quota_reached,lime_sid,comparison,exclude_var,exclude_val
			FROM questionnaire_sample_quota_row as qsq, questionnaire as q
			WHERE qsq.questionnaire_id = '$questionnaire_id'
			AND qsq.sample_import_id = '$sample_import_id'
			AND q.questionnaire_id = '$questionnaire_id'";
	
		$r = $db->GetAll($sql);
	
		if (empty($r))
		{
			print "<p>" . T_("Currently no row quotas") . "</p>";
		}
		else
		{
			foreach($r as $v)
			{
				print "<div><a href='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id&amp;questionnaire_sample_quota_row_id={$v['questionnaire_sample_quota_row_id']}'>" . T_("Stop calling") . " " . $v['exclude_var'] . " " . T_("like") . " " . $v['exclude_val'] .  " " . T_("rows from this sample when:") . " {$v['lime_sgqa']} {$v['comparison']} {$v['value']} " . T_("for") .  ": {$v['completions']} " . T_("completions") ."</a> - ";
			
				if ($v['quota_reached'] == 1)
					print T_("Row quota reached");
				else
					print T_("Row quota not yet reached");

				print " - " . T_("Current completions: ") . limesurvey_quota_completions($v['lime_sgqa'],$v['lime_sid'],$questionnaire_id,$sample_import_id,$v['value'],$v['comparison']);

				print "</div>";
	
			}
		}
	
	
		print "<h1>" . T_("Select a question for the row quota") . "</h1>";
		
		$sql = "SELECT lime_sid
			FROM questionnaire
			WHERE questionnaire_id = '$questionnaire_id'";
	
		$r = $db->GetRow($sql);
	
		$lime_sid = $r['lime_sid'];
	
		$sgqa = false;
		if (isset($_GET['sgqa'])) 	$sgqa = $_GET['sgqa'];
	
		$sql = "SELECT CONCAT( q.sid, 'X', q.gid, 'X', q.qid, IFNULL( a.code, '' ) ) AS value, CONCAT(q.question, ': ', IFNULL(a.answer,'')) as description, CASE WHEN CONCAT( q.sid, 'X', q.gid, 'X', q.qid, IFNULL( a.code, '' ) ) = '$sgqa' THEN 'selected=\'selected\'' ELSE '' END AS selected
			FROM `" . LIME_PREFIX . "questions` AS q
			LEFT JOIN `" . LIME_PREFIX . "answers` AS a ON ( a.qid = q.qid )
			WHERE q.sid = '$lime_sid'";
	
	
		display_chooser($ldb->GetAll($sql),"sgqa","sgqa",true,"questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id");
	
		if ($sgqa != false)
		{
			$sample_var = false;
			if (isset($_GET['sample_var']))
				$sample_var = $_GET['sample_var'];

			print "<h1>" . T_("Select the sample variable to exclude") . "</h1>";

			$sql = "SELECT sv.var as value, sv.var as description, CASE WHEN sv.var LIKE '$sample_var' THEN 'selected=\'selected\'' ELSE '' END AS selected
				FROM sample_var AS sv, sample AS s
				WHERE s.import_id = $sample_import_id
				AND s.sample_id = sv.sample_id
				GROUP BY sv.var";

			display_chooser($db->GetAll($sql),"sample_var","sample_var",true,"questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id&amp;sgqa=$sgqa");

			if ($sample_var != false)
			{
				print "<h1>" . T_("Enter the details for creating the row quota:") . "</h1>";
				print "<h2>" . T_("Pre defined values for this question:") . "</h2>";

				$qid = explode("X", $sgqa);
				$qid = $qid[2];
	
				$sql = "SELECT l.code,l.title
					FROM `" . LIME_PREFIX . "labels` as l, `" . LIME_PREFIX . "questions` as q
					WHERE q.qid = '$qid'
					AND l.lid = q.lid";
	
				$rs = $ldb->GetAll($sql);
	
				if (!isset($rs) || empty($rs))
					print "<p>" . T_("No labels defined for this question") ."</p>";
				else
					xhtml_table($rs,array('code','title'),array(T_("Code value"), T_("Description")));
	
	
				?>
				<form action="" method="get">
				<p>
				<label for="value"><? echo T_("The code value to compare"); ?> </label><input type="text" name="value" id="value"/>		<br/>
				<label for="comparison"><? echo T_("The type of comparison"); ?></label><select name="comparison" id="comparison"><option value="LIKE">LIKE</option><option value="NOT LIKE">NOT LIKE</option><option value="=">=</option><option value="!=">!=</option><option value="&lt;">&lt;</option><option value="&gt;">&gt;</option><option value="&lt;=">&lt;=</option><option value="&gt;=">&gt;=</option></select><br/>
				<label for="completions"><? echo T_("The number of completions to stop calling at"); ?> </label><input type="text" name="completions" id="completions"/>		<br/>
				<label for="exclude_val"><? echo T_("Exclude from the sample where the value is like"); ?></label>
				<?
				
				$sql = "SELECT sv.val as value, sv.val as description, ''  AS selected
					FROM sample_var AS sv, sample AS s
					WHERE s.import_id = $sample_import_id
					AND s.sample_id = sv.sample_id
					AND sv.var = '$sample_var'
					GROUP BY sv.val";

				display_chooser($db->GetAll($sql),"exclude_val","exclude_val",false,false,false,false);
				flush();
				?>
				<br/>
				<input type="hidden" name="exclude_var" value="<? print($sample_var); ?>"/>
				<input type="hidden" name="questionnaire_id" value="<? print($questionnaire_id); ?>"/>
				<input type="hidden" name="sample_import_id" value="<? print($sample_import_id); ?>"/>
				<input type="hidden" name="sgqa" value="<? print($sgqa); ?>"/>
				<input type="submit" name="add_quota" value="<? print(T_("Add row quota")); ?>"/></p>
				</form>
				<?
			}
		}
	}
}
xhtml_foot();


?>
