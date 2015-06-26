<<<<<<< TREE
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

if (isset($_GET['questionnaire_id']) && isset($_GET['sgqa'])  && isset($_GET['value']) && isset($_GET['completions']) && isset($_GET['sample_import_id']) && isset($_GET['comparison']) && isset($_GET['exclude_var_id']) && isset($_GET['exclude_var']) && isset($_GET['exclude_val']))
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
	$exclude_var_id = $db->quote($_GET['exclude_var_id']);
	$exclude_val = $db->quote($_GET['exclude_val']);
	$exclude_var = $db->quote($_GET['exclude_var']);
	$description = $db->quote($_GET['description']);

	$sql = "INSERT INTO questionnaire_sample_quota_row(questionnaire_id, sample_import_id, lime_sgqa,value,completions,comparison,exclude_var_id,exclude_var,exclude_val,description, priority, autoprioritise)
		VALUES ($questionnaire_id, $sample_import_id, $sgqa, $value, $completions, $comparison, $exclude_var_id, $exclude_var, $exclude_val, $description, $priority, $autoprioritise)";

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

xhtml_head(T_("Quota row management"),true,array("../include/bootstrap-3.3.2/css/bootstrap.min.css","../include/bootstrap-toggle/css/bootstrap-toggle.min.css","../css/custom.css"),array("../js/jquery-2.1.3.min.js","../include/bootstrap-toggle/js/bootstrap-toggle.min.js","../js/window.js"));
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
		
		$sql = "SELECT questionnaire_sample_quota_row_id, lime_sgqa, value, completions, quota_reached, lime_sid, comparison, exclude_var, exclude_val, current_completions
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
			ORDER BY CASE WHEN lq2.question_order IS NULL THEN lq.question_order ELSE lq2.question_order + (lq.question_order / 1000) END ASC";

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
			$sample_var_id = false;
			if (isset($_GET['sample_var_id']))
				$sample_var_id = $_GET['sample_var_id'];

			print "<h3 class='form-inline pull-left'>" . T_("Select the sample variable to exclude") . ":&emsp;</h3>";

			$sql = "SELECT sivr.var_id as value, sivr.var as description, 
					CASE WHEN sivr.var_id = '$sample_var_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
					FROM `sample_import_var_restrict` as sivr
					WHERE sivr.sample_import_id = '$sample_import_id'";
					
			$rsv = $db->GetAll($sql);
			
			$sample_var = $rsv[0]['description'];

			display_chooser($rsv,"sample_var_id","sample_var_id",true,"questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id&amp;sgqa=$sgqa",true,true,false,true,"pull-left");
			
			print "<div class='clearfix form-group'></div>";

			if ($sample_var_id != false)
			{
							
				print "<div class='col-sm-6 panel-body'><h3>" . T_("Enter the details for creating the row quota:") . "</h3>";
				
				?>
				<form action="" method="get" class="form-inline table">
				
				<p><label for="description"><?php  echo T_("Describe this quota"); ?>:&emsp;</label>
				<input type="text" class="form-control" name="description" id="description" required size="60"/></p>
				
				<p><label for="priority"><?php  echo T_("Quota priority (50 is default, 100 highest, 0 lowest)"); ?>:&emsp;</label>
				<input type="number" class="form-control" name="priority" id="priority" value="50" min="0" max="100" style="width:5em;"/></p>
				
				<p><label for="autoprioritise"><?php  echo T_("Should the priority be automatically updated ?</br> (based on the number of completions in this quota)"); ?>&emsp;</label>
				<input type="checkbox" name="autoprioritise" id="autoprioritise" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-offstyle="warning"/></p>
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
					AND sv.var_id = '$sample_var_id'
					GROUP BY sv.val";

				display_chooser($db->GetAll($sql),"exclude_val","exclude_val",false,false,false,false);
				flush();
				?>
				</p>
				<input type="hidden" name="exclude_var" value="<?php  print($sample_var); ?>"/>
				<input type="hidden" name="exclude_var_id" value="<?php  print($sample_var_id); ?>"/>
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

?>=======
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


if (isset($_POST['submitdelete']))
{
  foreach($_POST as $key => $val)
  {
    if (substr($key,0,7) == "select_")
    {
      $tmp = bigintval(substr($key,7));
      open_row_quota($tmp);
    }
  }
}

if (isset($_POST['submitexport']))
{
  $csv = array();
  foreach($_POST as $key => $val)
  {
    if (substr($key,0,7) == "select_")
    {
      $tmp = bigintval(substr($key,7));

      $sql = "SELECT description,completions,autoprioritise
              FROM questionnaire_sample_quota_row
              WHERE questionnaire_sample_quota_row_id = $tmp";

      $rs = $db->GetRow($sql);

      $sql = "SELECT lime_sgqa,comparison,value
              FROM qsqr_question
              WHERE questionnaire_sample_quota_row_id = $tmp";

      $q2 = $db->GetAll($sql);

      $sql = "SELECT exclude_var as samplerecord,comparison,exclude_val as value
              FROM qsqr_sample
              WHERE questionnaire_sample_quota_row_id = $tmp";

      $q3 = $db->GetAll($sql);

      $ta = array($rs['description'],$rs['completions'],$rs['autoprioritise']);

      //just search where col 1 looks like 333X2X2 and assume it is a question

      foreach($q2 as $qr)
        foreach($qr as $qe => $val)
          $ta[] = $val;
    
      foreach($q3 as $qr)
        foreach($qr as $qe => $val)
          $ta[] = $val;

      $csv[] = $ta;
    }
  }
  if (!empty($csv))
  {
    $fn = T_("Quota") .".csv";

  	header("Content-Type: text/csv");
  	header("Content-Disposition: attachment; filename=$fn");
  	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
  	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
  	Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  	header("Pragma: no-cache");                          // HTTP/1.0

    foreach($csv as $cr)
    {
      for ($i = 0; $i < count($cr); $i++)
      {
        echo str_replace(","," ",$cr[$i]);
        if ($i < (count($cr) - 1)) 
          echo ",";
      }
      echo "\n";      
    }
    die();
  }
}

if (isset($_GET['delete']))
{
  $qsqri = bigintval($_GET['qsqri']);

  if (isset($_GET['qsqrqi']))
  {
    $qsqrqi = bigintval($_GET['qsqrqi']);
    $sql = "DELETE FROM qsqr_question
            WHERE qsqr_question_id = $qsqrqi";
    $db->Execute($sql);

	  //Make sure to calculate on the spot
  	update_single_row_quota($qsqri);
  }
  if (isset($_GET['qsqrsi']))
  {
    $qsqrsi = bigintval($_GET['qsqrsi']);
    $sql = "DELETE FROM qsqr_sample
            WHERE qsqr_sample_id = $qsqrsi";
    $db->Execute($sql);

	  //Make sure to calculate on the spot
  	update_single_row_quota($qsqri);
  }
}


if (isset($_POST['add_quota']))
{
	//need to add quota
	$completions = intval($_POST['completions']);
	$autoprioritise = 0;
	if (isset($_POST['autoprioritise'])) $autoprioritise = 1;
	$priority = intval($_POST['priority']);
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
  $sample_import_id = bigintval($_GET['sample_import_id']);
	$description = $db->quote($_POST['description']);

	$sql = "INSERT INTO questionnaire_sample_quota_row(questionnaire_id, sample_import_id, completions, description, priority, autoprioritise)
		VALUES ($questionnaire_id, $sample_import_id, $completions, $description, $priority, $autoprioritise)";

  $db->Execute($sql);

  $qq = $db->Insert_ID();

	//Make sure to calculate on the spot
	update_single_row_quota($qq);
}

if (isset($_POST['edit_quota']))
{
	$completions = intval($_POST['completions']);
	$autoprioritise = 0;
	if (isset($_POST['autoprioritise'])) $autoprioritise = 1;
	$priority = intval($_POST['priority']);
	$description = $db->quote($_POST['description']);
  $qsqri = bigintval($_POST['qsqri']);

  $sql = "UPDATE questionnaire_sample_quota_row 
          SET completions = $completions,
              autoprioritise = $autoprioritise,
              priority = $priority,
              description = $description
          WHERE questionnaire_sample_quota_row_id = $qsqri";

	$db->Execute($sql);

  $_GET['qsqri'] = $qsqri;
  $_GET['edit'] = "edit";

	//Make sure to calculate on the spot
	update_single_row_quota($qsqri);
}


$qsqri = false;
$qsqrid = false;
if (isset($_GET['qsqri']) & isset($_GET['edit']))
{
  $qsqri = bigintval($_GET['qsqri']);

  $sql = "SELECT questionnaire_id,sample_import_id,description,autoprioritise,priority,completions
          FROM questionnaire_sample_quota_row
          WHERE questionnaire_sample_quota_row_id = $qsqri";

  $rs = $db->GetRow($sql);

  $_GET['questionnaire_id'] = $rs['questionnaire_id'];
  $_GET['sample_import_id'] = $rs['sample_import_id'];
  $qsqrid = $rs['description'];
  $qsqrich = "";
  if ($rs['autoprioritise'] == 1)
     $qsqrich = "checked=\"checked\"";
  $qsqric = $rs['completions'];
  $qsqrip = $rs['priority'];

  if (isset($_POST['adds']))
  {
    $comparison = $db->qstr($_POST['comparisons']);
    $exvar = $db->qstr(substr($_POST['sample_var'],12,strpos($_POST['sample_var'],'&')-12));
    $exval = $db->qstr($_POST['exclude_val']);
    //add ssample
    $sql = "INSERT INTO qsqr_sample (questionnaire_sample_quota_row_id,exclude_var,exclude_val,comparison)
            VALUES ($qsqri,$exvar,$exval,$comparison)";

    $db->Execute($sql);

	  //Make sure to calculate on the spot
  	update_single_row_quota($qsqri);
  }

  if (isset($_POST['addq']))
  {
    $comparison = $db->qstr($_POST['comparison']);
    $value = $db->qstr($_POST['value']);
    $sgqa = $db->qstr(substr($_POST['sgqa'],6,strpos($_POST['sgqa'],'&')-6));
    //add ssample
    $sql = "INSERT INTO qsqr_question (questionnaire_sample_quota_row_id,lime_sgqa,value,comparison)
            VALUES ($qsqri,$sgqa,$value,$comparison)";

    $db->Execute($sql);

  	//Make sure to calculate on the spot
  	update_single_row_quota($qsqri);
  }
}

$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) 	$questionnaire_id = bigintval($_GET['questionnaire_id']);

xhtml_head(T_("Quota row management"),true,array("../css/table.css"),array("../js/window.js"));
print "<h1>" . T_("Select a questionnaire from the list below") . "</h1>";

$sql = "SELECT questionnaire_id as value,description, CASE WHEN questionnaire_id = '$questionnaire_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
 	FROM questionnaire
	WHERE enabled = 1";
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
    if (isset($_POST['import_quota']))
    {
      if (isset($_FILES['file']['tmp_name']))
      {
        $handle = fopen($_FILES['file']['tmp_name'], "r");
      	while (($data = fgetcsv($handle)) !== FALSE) 
        {
          if (count($data) > 2)
          {
            //one quota record per row
          	$completions = intval($data[1]);
          	$autoprioritise = 0;
          	if ($data[2] != 0) $autoprioritise = 1;
          	$description = $db->quote($data[0]);
      
          	$sql = "INSERT INTO questionnaire_sample_quota_row(questionnaire_id, sample_import_id, completions, description, priority, autoprioritise)
                		VALUES ($questionnaire_id, $sample_import_id, $completions, $description, 50, $autoprioritise)";

            $db->Execute($sql);

            $qq = $db->Insert_ID();

            if (count($data) > 5) //also some other records
            {
              //check if records exist (come in triplets
              for ($i = 1; isset($data[$i * 3]) && !empty($data[$i*3]); $i++)
              {
                if (preg_match("/\d+X\d+X.+/",$data[$i*3]))
                {
                  //is a limesurvey question
                  $comparison = $db->qstr($data[($i*3) + 1]);
                  $value = $db->qstr($data[($i*3) + 2]);
                  $sgqa = $db->qstr($data[$i*3]);

                  $sql = "INSERT INTO qsqr_question (questionnaire_sample_quota_row_id,lime_sgqa,value,comparison)
                          VALUES ($qq,$sgqa,$value,$comparison)";

                  $db->Execute($sql);
                }
                else
                {
                  //is a sample variable
                  $comparison = $db->qstr($data[($i*3) + 1]);
                  $value = $db->qstr($data[($i*3) + 2]);
                  $var = $db->qstr($data[$i*3]);

                  $sql = "INSERT INTO qsqr_sample (questionnaire_sample_quota_row_id,exclude_var,exclude_val,comparison)
                          VALUES ($qq,$var,$value,$comparison)";

                  $db->Execute($sql);
                 }
              }
            }

          	//Make sure to calculate on the spot
          	update_single_row_quota($qq);
          }
        }
      	fclose($handle);
      }
    }



    if ($qsqri != false)
    {
      print "<h2>" . T_("Quota") . ": $qsqrid</h2>";
      print "<p><a href='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id'>" . T_("Go back") . "</a></p>";
  
      ?>
      <form action="?<?php echo "questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id"; ?>" method="post">
				<p>
        <label for="description"><?php  echo T_("Describe this quota"); ?> </label><input type="text" name="description" id="description" value="<?php echo $qsqrid;?>"/>		<br/>
        <label for="priority"><?php  echo T_("Quota priority (50 is default, 100 highest, 0 lowest)"); ?> </label><input type="text" name="priority" id="priority" value="<?php echo $qsqrip;?>"/>		<br/>
        <label for="autoprioritise"><?php  echo T_("Should the priority be automatically updated based on the number of completions in this quota?"); ?> </label><input type="checkbox" name="autoprioritise" id="autoprioritise" <?php echo $qsqrich; ?>/>		<br/>
        <label for="completions"><?php  echo T_("The number of completions to stop calling at"); ?> </label><input type="text" name="completions" id="completions" value="<?php echo $qsqric; ?>"/>		<br/>
        <input type="hidden" name="qsqri" value="<?php echo $qsqri; ?>"/>
				<input type="submit" name="edit_quota" value="<?php  print(T_("Edit row quota")); ?>"/></p>
      </form>
      <?php

      //display questionnaire references
      $sql = "SELECT qsqr_question_id,lime_sgqa,value,comparison,description,
              CONCAT('<a href=\'?edit=edit&amp;qsqri=$qsqri&amp;delete=delete&amp;qsqrqi=', qsqr_question_id, '\'>" . TQ_("Delete") .  "</a>') as qdelete
              FROM qsqr_question
              WHERE questionnaire_sample_quota_row_id = $qsqri";

      
      $rs = $db->GetAll($sql);

      if (empty($rs))
      {
        print "<h3>" . T_("All completed responses that match the sample criteria below will be counted towards the quota") . "</h3>";
      }
      else
      {
        print "<h3>" . T_("Only completed responses that have answered the following will be counted") . "</h3>";
        xhtml_table($rs,array('lime_sgqa','comparison','value','qdelete'),array(T_("Question"),T_("Comparison"),T_("Value"),T_("Delete")));
      }

      //add questionnaire references if any (refer to sample only or count completions based on responses to questions)

      $sql = "SELECT lime_sid 
              FROM questionnaire
              WHERE questionnaire_id = $questionnaire_id";

      $lime_sid = $db->GetOne($sql);

      $ssgqa = "''";
      if (isset($_GET['sgqa']))
        $ssgqa = $db->qstr($_GET['sgqa']);

      //select question
  		$sql = "SELECT CONCAT( lq.sid, 'X', lq.gid, 'X', CASE WHEN lq.parent_qid = 0 THEN lq.qid ELSE CONCAT(lq.parent_qid, lq.title) END) as value, CASE WHEN lq.parent_qid = 0 THEN lq.question ELSE CONCAT(lq2.question, ': ', lq.question) END as description, CASE WHEN $ssgqa LIKE CONCAT( lq.sid, 'X', lq.gid, 'X', CASE WHEN lq.parent_qid = 0 THEN lq.qid ELSE CONCAT(lq.parent_qid, lq.title) END) THEN 'selected=\'selected\'' ELSE '' END AS selected
	  		FROM `" . LIME_PREFIX . "questions` AS lq
  			LEFT JOIN `" . LIME_PREFIX . "questions` AS lq2 ON ( lq2.qid = lq.parent_qid )
  			JOIN `" . LIME_PREFIX . "groups` as g ON (g.gid = lq.gid)
  			WHERE lq.sid = '$lime_sid'
  			ORDER BY g.group_order ASC, lq.question_order ASC";

	  	$rs = $db->GetAll($sql);
  
      if (!empty($rs))
      {
        print "<form method='post' action='?qsqri=$qsqri&amp;edit=edit'>";
        print "<h4>" . T_("Add restriction based on answered questions") . "</h4>";
        print "<label for='sgqa'>" . T_("Question") . "</label>";
        display_chooser($rs,"sgqa","sgqa",false,"edit=edit&amp;qsqri=$qsqri",true,false);
        ?>
        <br/><label for="comparison"><?php  echo T_("The type of comparison"); ?></label><select name="comparison" id="comparison"><option value="LIKE">LIKE</option><option value="NOT LIKE">NOT LIKE</option><option value="=">=</option><option value="!=">!=</option><option value="&lt;">&lt;</option><option value="&gt;">&gt;</option><option value="&lt;=">&lt;=</option><option value="&gt;=">&gt;=</option></select><br/>
  			<label for="value"><?php  echo T_("The code value to compare"); ?> </label><input type="text" name="value" id="value"/>		<br/>
        <input type="submit" name="addq" value="<?php echo TQ_("Add restriction") ?>"/>
        </form>
        <?php
      }

      //list sample records to exclude

      $sql = "SELECT qsqr_sample_id,exclude_var,exclude_val,comparison,description,
              CONCAT('<a href=\'?qsqri=$qsqri&amp;edit=edit&amp;delete=delete&amp;qsqrsi=',qsqr_sample_id,'\'>" . TQ_("Delete") .  "</a>') as sdelete
              FROM qsqr_sample
              WHERE questionnaire_sample_quota_row_id = $qsqri";

      $rs = $db->GetAll($sql);

      if (empty($rs))
      {
        print "<h3>" . T_("All sample records will be excluded") . "</h3>";
      }
      else
      {
        print "<h3>" . T_("Completed responses that have the following sample details will be counted towards the quota and excluded when the quota is reached") . "</h3>";
        xhtml_table($rs,array('exclude_var','comparison','exclude_val','sdelete'),array(T_("Sample record"),T_("Comparison"),T_("Value"),T_("Delete")));
      }


      $ssample_var = "''";
      if (isset($_GET['sample_var']))
        $ssample_var = $db->qstr($_GET['sample_var']);

      //add sample references (records from sample to exclude when quota reached)
			$sql = "SELECT sv.var as value, sv.var as description, CASE WHEN sv.var LIKE $ssample_var THEN 'selected=\'selected\'' ELSE '' END AS selected
      				FROM sample_var AS sv, sample AS s
      				WHERE s.import_id = $sample_import_id
      				AND s.sample_id = sv.sample_id
              GROUP BY sv.var";

      $rs = $db->GetAll($sql);

      if (!empty($rs))
      {
        if ($ssample_var == "''")
           $ssample_var = "'" . $rs[0]['value']. "'";

        print "<h4>" . T_("Add restriction based on sample records") . "</h4>";
        print "<form method='post' action='?edit=edit&amp;qsqri=$qsqri'>";
        print "<label for='sample_var'>" . T_("Sample record") . "</label>";
			  display_chooser($rs,"sample_var","sample_var",false,"edit=edit&amp;qsqri=$qsqri",true,false);
        ?>
        <br/><label for="comparisons"><?php  echo T_("The type of comparison"); ?></label><select name="comparisons" id="comparisons"><option value="LIKE">LIKE</option><option value="NOT LIKE">NOT LIKE</option><option value="=">=</option><option value="!=">!=</option><option value="&lt;">&lt;</option><option value="&gt;">&gt;</option><option value="&lt;=">&lt;=</option><option value="&gt;=">&gt;=</option></select><br/>
        <label for="exclude_val"><?php  echo T_("Value"); ?></label>
				<?php 
				
				$sql = "SELECT sv.val as value, sv.val as description, ''  AS selected
					FROM sample_var AS sv, sample AS s
					WHERE s.import_id = $sample_import_id
					AND s.sample_id = sv.sample_id
					AND sv.var = $ssample_var
					GROUP BY sv.val";

				display_chooser($db->GetAll($sql),"exclude_val","exclude_val",false,false,false,false);
				flush();
		    ?>
        <br/><input type="submit" name="adds" value="<?php echo TQ_("Add restriction") ?>"/>
        </form>
        <?php
      }
    }
    else
    {


		print "<h1>" . T_("Current row quotas (click to edit)") . "</h1>";


    $sql = "SELECT questionnaire_sample_quota_row_id,qsq.description,
            CONCAT('<a href=\'?edit=edit&amp;qsqri=',questionnaire_sample_quota_row_id,'\'>', qsq.description, '</a>') as qedit,
            CONCAT('<input type=\'checkbox\' name=\'select_',questionnaire_sample_quota_row_id,'\'/>') as qselect,
            qsq.completions,qsq.quota_reached,qsq.current_completions,
            CASE WHEN qsq.autoprioritise = 1 THEN '" . TQ_("Yes") . "' ELSE '" . TQ_("No") . "' END AS ap, qsq.priority,
            CASE WHEN qsq.quota_reached = 1 THEN '" . TQ_("closed") . "' ELSE '" . TQ_("open") . "' END AS status
            FROM questionnaire_sample_quota_row as qsq, questionnaire as q
      			WHERE qsq.questionnaire_id = '$questionnaire_id'
      			AND qsq.sample_import_id = '$sample_import_id'
      			AND q.questionnaire_id = '$questionnaire_id'";
	
		$r = $db->GetAll($sql);

   print "<form method='post' action='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id'>";
		if (empty($r))
		{
			print "<p>" . T_("Currently no row quotas") . "</p>";
		}
		else
		{
       xhtml_table($r,array('qedit','completions','current_completions','status','priority','ap','qselect'),array(T_("Description"),T_("Quota"),T_("Completions"),T_("Status"),T_("Priority"),T_("Auto prioritise"),T_("Select")));
       print "<input type='submit' name='submitdelete' value='" . TQ_("Delete selected") . "'/>";
       print "<input type='submit' name='submitexport' value='" . TQ_("Export selected") . "'/>";

    //select sample

	    }
    print "</form>";
    print "<h2>" . T_("Add row quota") . "</h2>";
    ?>
      <form action="?<?php echo "questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id"; ?>" method="post">
				<p>
				<label for="description"><?php  echo T_("Describe this quota"); ?> </label><input type="text" name="description" id="description"/>		<br/>
				<label for="priority"><?php  echo T_("Quota priority (50 is default, 100 highest, 0 lowest)"); ?> </label><input type="text" name="priority" id="priority" value="50"/>		<br/>
				<label for="autoprioritise"><?php  echo T_("Should the priority be automatically updated based on the number of completions in this quota?"); ?> </label><input type="checkbox" name="autoprioritise" id="autoprioritise"/>		<br/>
        <label for="completions"><?php  echo T_("The number of completions to stop calling at"); ?> </label><input type="text" name="completions" id="completions"/>		<br/>
				<input type="submit" name="add_quota" value="<?php  print(T_("Add row quota")); ?>"/></p>
    </form>
    <?php

    print "<h2>" . T_("Import row quota") . "</h2>";
    ?>
  <form enctype="multipart/form-data" action="<?php echo "?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id"; ?>" method="post">
	<p><input type="hidden" name="MAX_FILE_SIZE" value="1000000000" /></p>
	<p><?php  echo T_("Choose the CSV row quota file to import:"); ?><input name="file" type="file" /></p>
	<p><input type="submit" name="import_quota" value="<?php  print(T_("Import row quota")); ?>"/></p>
	</form>
    <?php
    }
  }
}
xhtml_foot();


?>
>>>>>>> MERGE-SOURCE
