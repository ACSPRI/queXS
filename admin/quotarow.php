<?php 
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
	$value = -1;
	$comparison = -1;
	$completions = -1;
	$sgqa = -1;
	$autoprioritise = 0;

	if (isset($_GET['autoprioritise'])) $autoprioritise = 1;
	
	$priority = intval($_GET['priority']);
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sample_import_id = bigintval($_GET['sample_import_id']);
	if ($_GET['sgqa'] != -1)
	{
		if ($_GET['sgqa'] != -2)
		{
			$comparison = $db->quote($_GET['comparison']);
			$value = $db->quote($_GET['value']);
			$sgqa = $db->quote($_GET['sgqa']);
		}
		else
		{
			$sgqa = -2;
		}
		$completions = $db->quote($_GET['completions']);
	}
	$exclude_val = $db->quote($_GET['exclude_val']);
	$exclude_var = $db->quote($_GET['exclude_var']);
	$description = $db->quote($_GET['description']);

	$sql = "INSERT INTO questionnaire_sample_quota_row(questionnaire_id, sample_import_id, lime_sgqa,value,completions,comparison,exclude_var,exclude_val,description, priority, autoprioritise)
		VALUES ($questionnaire_id, $sample_import_id, $sgqa, $value, $completions, $comparison, $exclude_var, $exclude_val, $description, $priority, $autoprioritise)";

	$db->Execute($sql);

	//Make sure to calculate on the spot
	update_quotas($questionnaire_id);
}

if (isset($_GET['questionnaire_id']) && isset($_GET['questionnaire_sample_quota_row_id']))
{
	//need to remove quota

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$questionnaire_sample_quota_row_id = bigintval($_GET['questionnaire_sample_quota_row_id']);

	open_row_quota($questionnaire_sample_quota_row_id);
}

$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) 	$questionnaire_id = bigintval($_GET['questionnaire_id']);

xhtml_head(T_("Quota row management"),true,array("../include/bootstrap-3.3.2/css/bootstrap.min.css","../css/custom.css"),array("../js/window.js"));
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
		print "<h3 class='form-inline pull-left'>&emsp;&emsp;&emsp;" . T_("Sample") . ":&emsp;</h3>";
		display_chooser($s,"sample","sample_import_id",true,"questionnaire_id=$questionnaire_id",true,true,false,true,"pull-left");
	} else {	
		print "<div class='clearfix'></div><div class='well text-info'>" . T_("No samples assigned to this questionnaire.") . "</div>";
	}
	print "<div class='clearfix'></div>";
	
	if ($sample_import_id != false)
	{
		if (isset($_POST['copy_sample_import_id']))
		{
			copy_row_quota($questionnaire_id,$sample_import_id,bigintval($_POST['copy_sample_import_id']));
			print "<h3>" . T_("Copied quotas") . ":</h3>";
		}

        if (isset($_POST['copy_sample_import_id_with_adjustment']))
		{
			copy_row_quota_with_adjusting($questionnaire_id,$sample_import_id,bigintval($_POST['copy_sample_import_id_with_adjustment']));
			print "<h3>" . T_("Copied quotas with adjustment") . ":</h3>";
		}

		print "<h2>" . T_("Current row quotas ") . ":</h2>"; //(click to delete)
		
		$sql = "SELECT questionnaire_sample_quota_row_id,lime_sgqa,value,completions,quota_reached,lime_sid,comparison,exclude_var,exclude_val,current_completions
			FROM questionnaire_sample_quota_row as qsq, questionnaire as q
			WHERE qsq.questionnaire_id = '$questionnaire_id'
			AND qsq.sample_import_id = '$sample_import_id'
			AND q.questionnaire_id = '$questionnaire_id'";
	
		$r = $db->GetAll($sql);
	
		if (empty($r))
		{
			print "<p class='well text-info'>" . T_("Currently no row quotas") . "</p>";
		}
		else
		{
			foreach($r as $v)
			{
				if ($v['lime_sgqa'] == -1)
					print "<div><a href='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id&amp;questionnaire_sample_quota_row_id={$v['questionnaire_sample_quota_row_id']}'>" . T_("Replicate: Where") . " " . $v['exclude_var'] . " " . T_("like") . " " . $v['exclude_val'] . "</a> - ";
				else if ($v['lime_sgqa'] == -2)
					print "<div><a href='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id&amp;questionnaire_sample_quota_row_id={$v['questionnaire_sample_quota_row_id']}'>" . T_("Sample only. Stop calling where") . " " . $v['exclude_var'] . " " . T_("like") . " " . $v['exclude_val'] .  " " . T_("rows from this sample when:") . " {$v['completions']} " . T_("completions") .  "</a> - ";

				else
					print "<div><a href='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id&amp;questionnaire_sample_quota_row_id={$v['questionnaire_sample_quota_row_id']}'>" . T_("Stop calling") . " " . $v['exclude_var'] . " " . T_("like") . " " . $v['exclude_val'] .  " " . T_("rows from this sample when:") . " {$v['lime_sgqa']} {$v['comparison']} {$v['value']} " . T_("for") .  ": {$v['completions']} " . T_("completions") ."</a> - ";
			
				if ($v['quota_reached'] == 1)
					print T_("Row quota reached (Closed)");
				else
					print T_("Row quota not yet reached (Open)");

				if ($v['lime_sgqa'] != -1)
					print " - " . T_("Current completions: ") . $v['current_completions'] . ":" . limesurvey_quota_completions($v['lime_sgqa'],$v['lime_sid'],$questionnaire_id,$sample_import_id,$v['value'],$v['comparison']);

				print "</div>";
	
			}

			$sql = "SELECT s.sample_import_id as value,s.description, '' AS selected
			 	FROM sample_import as s, questionnaire_sample as q
				WHERE q.questionnaire_id = $questionnaire_id
				AND q.sample_import_id = s.sample_import_id
				AND s.sample_import_id != '$sample_import_id'";
	
			$ss = $db->GetAll($sql);

			if (!empty($ss))
			{
				print "<form action='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id' method='post'>
						<p>" . T_("Copy quotas for this sample to (No error/duplicate checking): ");
						display_chooser($ss,"copy_sample_import_id","copy_sample_import_id",false,false,false,false);
				print "<input type='submit' id='submit' value=\"" . T_("Copy") . "\"/></p></form>";

                print "<form action='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id' method='post'>
						<p>" . T_("Copy quotas for this sample to (No error/duplicate checking) with adjusting: ");
						display_chooser($ss,"copy_sample_import_id_with_adjustment","copy_sample_import_id_with_adjustment",false,false,false,false);
				print "<input type='submit' id='submit' value=\"" . T_("Copy adjustments") . "\"/></p></form>";
			}

		}
	
	
		print "<h3>" . T_("Select a question for the row quota") . ":&emsp;</h3>";
		
		$sql = "SELECT lime_sid
			FROM questionnaire
			WHERE questionnaire_id = '$questionnaire_id'";
	
		$r = $db->GetRow($sql);
	
		$lime_sid = $r['lime_sid'];
	
		$sgqa = false;
		if (isset($_GET['sgqa'])) 	$sgqa = $_GET['sgqa'];

		$sql = "SELECT CONCAT( lq.sid, 'X', lq.gid, 'X', CASE WHEN lq.parent_qid = 0 THEN lq.qid ELSE CONCAT(lq.parent_qid, lq.title) END) as value, CASE WHEN lq.parent_qid = 0 THEN lq.question ELSE CONCAT(lq2.question, ': ', lq.question) END as description, CASE WHEN CONCAT( lq.sid, 'X', lq.gid, 'X', CASE WHEN lq.parent_qid = 0 THEN lq.qid ELSE CONCAT(lq.parent_qid, lq.title) END) = '$sgqa' THEN 'selected=\'selected\'' ELSE '' END AS selected
			FROM `" . LIME_PREFIX . "questions` AS lq
			LEFT JOIN `" . LIME_PREFIX . "questions` AS lq2 ON ( lq2.qid = lq.parent_qid )
			JOIN `" . LIME_PREFIX . "groups` as g ON (g.gid = lq.gid)
			WHERE lq.sid = '$lime_sid'
			ORDER BY lq.parent_qid ASC, lq.question_order ASC";

		$rs = $db->GetAll($sql);
		
		$selected = "";
		if ($sgqa == -1) $selected = "selected='selected'";
		array_unshift($rs,array("value" => -1, "description" => T_("No question (Replicate)"), "selected" => $selected));
		
		$selected = "";
		if ($sgqa == -2) $selected = "selected='selected'";
		array_unshift($rs,array("value" => -2, "description" => T_("Sample only quota"), "selected" => $selected));

		display_chooser($rs,"sgqa","sgqa",true,"questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id");
		
		print "</br>";
	
		if ($sgqa != false)
		{
			$sample_var = false;
			if (isset($_GET['sample_var']))
				$sample_var = $_GET['sample_var'];

			print "<h3 class='form-inline pull-left'>" . T_("Select the sample variable to exclude") . ":&emsp;</h3>";

			$sql = "SELECT sv.var as value, sv.var as description, CASE WHEN sv.var LIKE '$sample_var' THEN 'selected=\'selected\'' ELSE '' END AS selected
				FROM sample_var AS sv, sample AS s
				WHERE s.import_id = $sample_import_id
				AND s.sample_id = sv.sample_id
				GROUP BY sv.var";

			display_chooser($db->GetAll($sql),"sample_var","sample_var",true,"questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id&amp;sgqa=$sgqa",true,true,false,true,"pull-left");
			
			print "<div class='clearfix form-group'></div>";

			if ($sample_var != false)
			{
							
				print "<div class='col-sm-6 panel-body'><h3>" . T_("Enter the details for creating the row quota:") . "</h3>";
				
				?>
				<form action="" method="get" class="form-inline table">
				
				<p><label for="description"><?php  echo T_("Describe this quota"); ?>:&emsp;</label>
				<input type="text" class="form-control" name="description" id="description" required size="60"/></p>
				
				<p><label for="priority"><?php  echo T_("Quota priority (50 is default, 100 highest, 0 lowest)"); ?>:&emsp;</label>
				<input type="number" class="form-control" name="priority" id="priority" value="50" min="0" max="100" style="width:5em;"/></p>
				
				<p><label for="autoprioritise"><?php  echo T_("Should the priority be automatically updated ?</br> (based on the number of completions in this quota)"); ?>&emsp;</label>
				<input type="checkbox" name="autoprioritise" id="autoprioritise"/></p>
				<?php  if ($sgqa != -1) { if ($sgqa != -2) { ?>
				
				<p><label for="comparison"><?php  echo T_("The type of comparison"); ?>:&emsp;</label>
				<select name="comparison" class="form-control" id="comparison">
					<option value="LIKE">LIKE</option>
					<option value="NOT LIKE">NOT LIKE</option>
					<option value="=">=</option><option value="!=">!=</option>
					<option value="&lt;">&lt;</option><option value="&gt;">&gt;</option>
					<option value="&lt;=">&lt;=</option><option value="&gt;=">&gt;=</option>
				</select></p>
				
				<p><label for="value"><?php  echo T_("The code value to compare"); ?>:&emsp;</label>
				<input type="text" class="form-control" name="value" id="value" required /></p>
				
				<?php  } else { ?>
				<input type="hidden" name="value" value="-2"/>
				<input type="hidden" name="comparison" value="-2"/>
				<?php  } ?>
				
				<p><label for="completions"><?php  echo T_("The number of completions to stop calling at"); ?>:&emsp;</label>
				<input type="number" class="form-control" name="completions" id="completions" size="6" maxlength="6" style="width:6em;" required/></p>
				
				<?php  } else { ?>
				<input type="hidden" name="value" value="-1"/>
				<input type="hidden" name="comparison" value="-1"/>
				<input type="hidden" name="completions" value="-1"/>
				<?php  } ?>
				
				<p><label for="exclude_val"><?php  echo T_("Exclude from the sample where the value is like"); ?>:&emsp;</label>
				<?php 
				
				$sql = "SELECT sv.val as value, sv.val as description, ''  AS selected
					FROM sample_var AS sv, sample AS s
					WHERE s.import_id = $sample_import_id
					AND s.sample_id = sv.sample_id
					AND sv.var = '$sample_var'
					GROUP BY sv.val";

				display_chooser($db->GetAll($sql),"exclude_val","exclude_val",false,false,false,false);
				flush();
				?>
				</p>
				<input type="hidden" name="exclude_var" value="<?php  print($sample_var); ?>"/>
				<input type="hidden" name="questionnaire_id" value="<?php  print($questionnaire_id); ?>"/>
				<input type="hidden" name="sample_import_id" value="<?php  print($sample_import_id); ?>"/>
				<input type="hidden" name="sgqa" value="<?php  print($sgqa); ?>"/>
				
				<input type="submit" name="add_quota" value="<?php  print(T_("Add row quota")); ?>" class="btn btn-primary fa"/>
				</form>
				<?php 
				
				print "</div>";
				
				print "<div class='col-sm-6 panel-body'><h3>" . T_("Code values for this question") . ":</h3>";

				$rs = "";

				if ($sgqa != -2 && $sgqa != -1 && !empty($sgqa))
				{
					$qid = explode("X", $sgqa);
					$qid = $qid[2];
		
					$sql = "SELECT CONCAT('<b class=\'fa\'>&emsp;', l.code , '</b>')as code, l.answer as title
						FROM `" . LIME_PREFIX . "answers` as l
						WHERE l.qid = '$qid'";
		
					$rs = $db->GetAll($sql);
				}
	
				if (!isset($rs) || empty($rs))
					print "<p class='well text-info'>" . T_("No labels defined for this question") ."</p>";
				else
					xhtml_table($rs,array('code','title'),array(T_("Code value"), T_("Description")));
				
				print "</div>";
			}
		}
	}
}

xhtml_foot();

?>