<?php 
/**
 * Set quota's for answered questions 
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


if (isset($_GET['questionnaire_id']) && isset($_GET['sgqa'])  && isset($_GET['value']) && isset($_GET['completions']) && isset($_GET['sample_import_id']) && isset($_GET['comparison']))
{
	//need to add quota

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sample_import_id = bigintval($_GET['sample_import_id']);
	$value = $db->quote($_GET['value']);
	$completions = $db->quote($_GET['completions']);
	$sgqa = $db->quote($_GET['sgqa']);
	$comparison = $db->quote($_GET['comparison']);

	$sql = "INSERT INTO questionnaire_sample_quota(questionnaire_id, sample_import_id, lime_sgqa,value,completions,comparison)
		VALUES ($questionnaire_id, $sample_import_id, $sgqa, $value, $completions, $comparison)";

	$db->Execute($sql);

	//Make sure to calculate on the spot
	update_quotas($questionnaire_id);
}

if (isset($_GET['questionnaire_id']) && isset($_GET['questionnaire_sample_quota_id']))
{
	//need to remove quota

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$questionnaire_sample_quota_id = bigintval($_GET['questionnaire_sample_quota_id']);

	$sql = "DELETE FROM questionnaire_sample_quota
		WHERE questionnaire_sample_quota_id = '$questionnaire_sample_quota_id'";

	$db->Execute($sql);

}

$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) 	$questionnaire_id = bigintval($_GET['questionnaire_id']);

xhtml_head(T_("Quota management"),true,array("../include/bootstrap-3.3.2/css/bootstrap.min.css","../css/custom.css"),array("../js/window.js"));
print "<h3 class='form-inline pull-left'>" . T_("Questionnaire") . ":&emsp;</h3>";

$sql = "SELECT questionnaire_id as value,description, CASE WHEN questionnaire_id = '$questionnaire_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
 	FROM questionnaire
	WHERE enabled = 1";
display_chooser($db->GetAll($sql),"questionnaire","questionnaire_id", true,false,true,true,false,true,"form-inline pull-left ");

if ($questionnaire_id != false)
{
	$sample_import_id = false;
	if (isset($_GET['sample_import_id'])) 	$sample_import_id = bigintval($_GET['sample_import_id']);

	
	
	$sql = "SELECT s.sample_import_id as value,s.description, CASE WHEN s.sample_import_id = '$sample_import_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
	 	FROM sample_import as s, questionnaire_sample as q
		WHERE q.questionnaire_id = $questionnaire_id
		AND q.sample_import_id = s.sample_import_id";
	$s = $db->GetAll($sql);
	if (!empty($s)){
		
		print "<h3 class='form-inline  pull-left'>&emsp;&emsp;&emsp;" . T_("Sample") . ": &emsp;</h3>";
		display_chooser($db->GetAll($sql),"sample","sample_import_id",true,"questionnaire_id=$questionnaire_id",true,true,false,true,"pull-left");
	
	} else {
		print "<div class='clearfix'></div><div class='well text-info'>" . T_("No samples assigned to this questionnaire.") . "</div>";
		
	}

	print "<div class='clearfix'></div>";
	
	if ($sample_import_id != false)
	{
		print "<h2>" . T_("Current quotas") . ":</h2>";//(click to delete)
		
		$sql = "SELECT questionnaire_sample_quota_id,lime_sgqa,value,completions,quota_reached,lime_sid,comparison
			FROM questionnaire_sample_quota as qsq, questionnaire as q
			WHERE qsq.questionnaire_id = '$questionnaire_id'
			AND qsq.sample_import_id = '$sample_import_id'
			AND q.questionnaire_id = '$questionnaire_id'";
	
		$r = $db->GetAll($sql);
	
		if (empty($r))
		{
			print "<p class='well text-info'>" . T_("Currently no quotas") . "</p>";
		}
		else
		{
			foreach($r as $v)
			{
				print "<div><a href='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id&amp;questionnaire_sample_quota_id={$v['questionnaire_sample_quota_id']}'>" . T_("Stop calling this sample when:") . " {$v['lime_sgqa']} {$v['comparison']} {$v['value']} " . T_("for") .  ": {$v['completions']} " . T_("completions") ."</a> - ";
			
				if ($v['quota_reached'] == 1)
					print T_("Quota reached");
				else
					print T_("Quota not yet reached");

				print " - " . T_("Current completions: ") . limesurvey_quota_completions($v['lime_sgqa'],$v['lime_sid'],$questionnaire_id,$sample_import_id,$v['value'],$v['comparison']);

				print "</div>";
	
			}
		}
	
	
		print "<h3 class=' '>" . T_("Select a question for the quota") . "</h3>";
		
		$sql = "SELECT lime_sid
			FROM questionnaire
			WHERE questionnaire_id = '$questionnaire_id'";
	
		$r = $db->GetRow($sql);
	
		$lime_sid = $r['lime_sid'];
	
		$sgqa = false;
		if (isset($_GET['sgqa'])) 	$sgqa = $_GET['sgqa'];
	
		$sql = "SELECT CONCAT( lq.sid, 'X', lq.gid, 'X', CASE WHEN lq.parent_qid = 0 THEN lq.qid ELSE CONCAT(lq.parent_qid, lq.title) END) as value,
		CASE WHEN lq.parent_qid = 0 THEN lq.question ELSE CONCAT(lq2.question, ': ', lq.question) END as description,
		CASE WHEN CONCAT( lq.sid, 'X', lq.gid, 'X', CASE WHEN lq.parent_qid = 0 THEN lq.qid ELSE CONCAT(lq.parent_qid, lq.title) END) = '$sgqa' THEN 'selected=\'selected\'' ELSE '' END AS selected
			FROM `" . LIME_PREFIX . "questions` AS lq
			LEFT JOIN `" . LIME_PREFIX . "questions` AS lq2 ON ( lq2.qid = lq.parent_qid )
			JOIN `" . LIME_PREFIX . "groups` as g ON (g.gid = lq.gid)
			WHERE lq.sid = '$lime_sid'
			ORDER BY CASE WHEN lq2.question_order IS NULL THEN lq.question_order ELSE lq2.question_order + (lq.question_order / 1000) END ASC";

		display_chooser($db->GetAll($sql),"sgqa","sgqa",true,"questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id",true,true,false,true,"form-group");
		
		print "<div class='clearfix'></div>";
	
		if ($sgqa != false)
		{
			
			print "<div class='col-sm-6 panel-body'><h3>" . T_("Enter the details for creating the quota:") . "</h3>";

			?>
			<form action="" method="get" class="form-inline form-group">
			
			<p><label for="comparison"><?php  echo T_("The type of comparison"); ?>:&emsp;</label>
			<select name="comparison" class='form-control' id="comparison">
				<option value="LIKE">LIKE</option>
				<option value="NOT LIKE">NOT LIKE</option>
				<option value="=">=</option><option value="!=">!=</option>
				<option value="&lt;">&lt;</option>
				<option value="&gt;">&gt;</option>
				<option value="&lt;=">&lt;=</option>
				<option value="&gt;=">&gt;=</option></select></p>
				
			<p><label for="value"><?php  echo T_("The code value to compare"); ?>:&emsp;</label>
			<input type="text" name="value" id="value" class="form-control" size="35" required /></p>
			
			<p><label for="completions"><?php  echo T_("The number of completions to stop calling at"); ?>:&emsp;</label>
			<input type="number" name="completions" id="completions" class="form-control" size="6" maxlength="6" style="width:8em;" required /></p>
			
			<input type="hidden" name="questionnaire_id" value="<?php  print($questionnaire_id); ?>"/>
			<input type="hidden" name="sample_import_id" value="<?php  print($sample_import_id); ?>"/>
			<input type="hidden" name="sgqa" value="<?php  print($sgqa); ?>"/>
			
			<p><input type="submit" name="add_quota" value="<?php  print(T_("Add quota")); ?>" class="btn btn-primary fa"/></p>
			</form>
			<?php 
			
			print "</div>";
			
			print "<div class='col-sm-6 panel-body'><h3>" . T_("Code values for this question") . ":</h3>";

			$qid = explode("X", $sgqa);
			$qid = $qid[2];

			$sql = "SELECT CONCAT('<b class=\'fa\'>&emsp;', l.code , '</b>')as code,l.answer as title
				FROM `" . LIME_PREFIX . "answers` as l 
				WHERE l.qid = '$qid'";

			$rs = $db->GetAll($sql);

			if (!isset($rs) || empty($rs))
				print "<p class='well text-info'>" . T_("No labels defined for this question") ."</p>";
			else
				xhtml_table($rs,array('code','title'),array(T_("Code value"), T_("Description")));
			
			
			print "</div>";
			
		}
	}
}

xhtml_foot();

?>