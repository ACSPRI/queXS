<?php /**
 * Display a report of all quota's
 * a. (Standard quota) Monitor outcomes of questions in completed questionnaires, and exclude selected sample records when completion limit is reached
 * b. (Replicate quota) Exclude selected sample records 
 * c. (Questionnaire quota) Monitor outcomes of questions in completed questionnaires, and abort interview when completion limit is reached 
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2009
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
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
 * Authentication file
 */
require ("auth-admin.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Display functions
 */
include("../functions/functions.display.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

/**
 * Operator functions
 */
include("../functions/functions.operator.php");

/**
 * Limesurvey functions
 */
include("../functions/functions.limesurvey.php");


if (isset($_POST['submit']))
{
	$questionnaire_id = bigintval($_POST['questionnaire_id']);

	$db->StartTrans();

	$sql = "UPDATE questionnaire_sample_quota_row
		SET autoprioritise = 0
		WHERE questionnaire_id = '$questionnaire_id'";

	$db->Execute($sql);

	foreach($_POST as $key => $val)
	{
		$qsqr = bigintval(substr($key,1));
		if (substr($key,0,1) == 'a')
		{
			$sql = "UPDATE questionnaire_sample_quota_row
				SET autoprioritise = 1
				WHERE questionnaire_sample_quota_row_id = $qsqr";

			$db->Execute($sql);
		}
		else if (substr($key,0,1) == 'p')
		{
			$val = intval($val);

			$sql = "UPDATE questionnaire_sample_quota_row
				SET priority = '$val'
				WHERE questionnaire_sample_quota_row_id = $qsqr";

			$db->Execute($sql);
		}
	}
	update_quota_priorities($questionnaire_id);

	$db->CompleteTrans();
}

xhtml_head(T_("Quota report"),true,array("../include/bootstrap/css/bootstrap.min.css","../include/font-awesome/css/font-awesome.css","../include/iCheck/skins/square/blue.css","../css/custom.css"),array("../include/jquery/jquery.min.js","../include/bootstrap/js/bootstrap.min.js","../include/iCheck/icheck.min.js","../js/window.js"));

print "<h3 class='form-inline pull-left'>" . T_("Select a questionnaire") . ":&emsp;</h3>";

$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);
display_questionnaire_chooser($questionnaire_id,false,"form-inline form-group", "form-control");

if ($questionnaire_id)
{
	print "<h3 class='form-inline pull-left'>" . T_("Select a sample") . ":&emsp;</h3>";
	$sample_import_id = false;
	if (isset($_GET['sample_import_id'])) $sample_import_id = bigintval($_GET['sample_import_id']);
	display_sample_chooser($questionnaire_id,$sample_import_id,false,"form-inline form-group clearfix", "form-control");

	if ($sample_import_id)
	{
		if (isset($_GET['rowquota']))
		{
			$qsq = bigintval($_GET['rowquota']);
			$qr = 0;
			if (isset($_GET['close'])) $qr = 1;
			$sql = "UPDATE questionnaire_sample_quota_row
				SET quota_reached = $qr
				WHERE questionnaire_sample_quota_row_id = '$qsq'";
			$db->Execute($sql);
			if ($qr == 1)
				close_row_quota($qsq);
			else
				open_row_quota($qsq,false);
		}

		//Display report of quotas 
		$report = array();
	
		//Rows to display: Strata Status Quota Sample Sample Used Sample Remaining Completes % Complete



		//a. (Standard quota) Monitor outcomes of questions in completed questionnaires, and exclude selected sample records when completion limit is reached
		//b. (Replicate quota) Exclude selected sample records (where no qsqr_question rows) 
		$sql = "SELECT qsq.questionnaire_sample_quota_row_id,completions,quota_reached,lime_sid,qsq.description,current_completions, priority, autoprioritise
      FROM questionnaire_sample_quota_row as qsq, questionnaire as q
      WHERE qsq.questionnaire_id = '$questionnaire_id'
			AND qsq.sample_import_id = '$sample_import_id'
      AND q.questionnaire_id = '$questionnaire_id'";
	
		$r = $db->GetAll($sql);

		foreach ($r as $v)
		{
			$completions = $v['current_completions'];
			$priority = $v['priority'];
			$autoprioritise = $v['autoprioritise'];
			$checked = "";
			if ($autoprioritise) $checked = "checked='checked'";
			$qsqr = $v['questionnaire_sample_quota_row_id'];
			

      $perc = ($v['completions'] <= 0 ? 0 : ROUND(($completions / ($v['completions'])) * 100,2));
			
			//We need to calc Sample size, Sample drawn, Sample remain
			$sql = "SELECT (c.sample_id is not null) as type, count(*) as count
				FROM sample as s
        JOIN questionnaire_sample as qs ON (qs.questionnaire_id = '$questionnaire_id' and qs.sample_import_id = s.import_id) ";

      $sql2 = "SELECT exclude_val,exclude_var,exclude_var_id,comparison
               FROM qsqr_sample
               WHERE questionnaire_sample_quota_row_id = {$v['questionnaire_sample_quota_row_id']}";

      $rev = $db->GetAll($sql2);

      //reduce sample by every item in the qsqr_sample table
      $x = 1;
      foreach($rev as $ev)
      {
          $sql .= " JOIN sample_var as sv$x ON (sv$x.sample_id = s.sample_id AND sv$x.var_id = '{$ev['exclude_var_id']}' AND sv$x.val {$ev['comparison']} '{$ev['exclude_val']}') ";
          $x++;
      }

			$sql .=	" LEFT JOIN `case` as c ON (c.questionnaire_id = qs.questionnaire_id and c.sample_id = s.sample_id)
				WHERE s.import_id = '$sample_import_id'
				GROUP BY (c.sample_id is not null)";

 			$rs = $db->GetAll($sql);
			//type == 1 is drawn from sample, type == 0 is remains in sample
			$drawn = 0;
			$remain = 0;
		
			foreach ($rs as $r)
			{
				if ($r['type'] == 1) $drawn = $r['count'];
				if ($r['type'] == 0) $remain = $r['count'];
			}

			if ($completions < $v['completions']) //if completions less than the quota, allow for closing/opening
			{
				if ($v['quota_reached'] == 1)
					$status = "<span class='label label-default fa-lg'>" . T_("closed") . "</span><a class='btn' data-toggle='tooltip' title='" . T_("open") . "' href='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id&amp;rowquota=$qsqr&amp;open=open'><i class='fa fa-lock fa-2x' style='color:red;'></i></a>";
				else
					$status = "<span class='label label-primary fa-lg'>" . T_("open") . "&emsp;</span><a class='btn' data-toggle='tooltip' title='" . T_("close") . "' href='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id&amp;rowquota=$qsqr&amp;close=close'><i class='fa fa-unlock fa-2x'></i></a>";
			}
			else
			{
				if ($v['quota_reached'] == 1)
					$status = "<span class='label label-default fa-lg'>" . T_("closed") . "</span>";
				else
					$status = "<span class='label label-primary fa-lg'>" . T_("open") . "&emsp;</span>";
			}
			
			$report[] = array("strata" => "<a href='quotarow.php?qsqri=$qsqr&amp;edit=edit&amp;questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id'>" . $v['description'] . "</a>", "status" => $status, "quota" => $v['completions'], "sample" => $drawn + $remain, "sampleused" => $drawn, "sampleremain" => $remain, "completions" => $completions, "perc" => $perc, "priority" => "<input type='number' maxlength='3' min='0' max='100' size='3' style='width:6em;' value='$priority' id='p$qsqr' name='p$qsqr' class='form-control'/>", "autoprioritise" => "&emsp;&emsp;<input type='checkbox' id='a$qsqr' name='a$qsqr' $checked />");
		}

		//c. (Questionnaire quota) Monitor outcomes of questions in completed questionnaires, and abort interview when completion limit is reached 
		$sql = "SELECT *
			FROM " . LIME_PREFIX . "quota as qu, questionnaire as q
			WHERE qu.sid = q.lime_sid
			AND qu.active = 1
			AND q.questionnaire_id = '$questionnaire_id'";
				
		$rs = $db->GetAll($sql);

		//for each limesurvey quota
		foreach($rs as $r)
		{
			//limesurvey quotas for this question
			$quotas = (get_limesurvey_quota_info($r['id']));
			$cob = array();

			foreach ($quotas as $qr)
			{
				$sqlq = array();
				foreach($qr as $qid => $q)
				{
					$sqlq[] = "s." . $q['fieldname'] . " = '" . $q['value'] . "'";
				}
				$cob[] = "( " . implode(' OR ', $sqlq) . " )";
			}
		
			if (!empty($cob))
			{	
			
				$sql = "SELECT COUNT(id) as count
					FROM ".LIME_PREFIX."survey_{$r['sid']} as s
					JOIN `case` as c ON (c.questionnaire_id = '$questionnaire_id')
					JOIN `sample` as sam ON (c.sample_id = sam.sample_id AND sam.import_id = '$sample_import_id')
					WHERE ".implode(' AND ',$cob)." "." 
					AND submitdate IS NOT NULL
					AND s.token = c.token";

				$rs = $db->GetRow($sql);

				$completions = $rs['count'];
				$perc = ROUND(($completions / $r['qlimit']) * 100,2);
				
				$report[] = array("strata" => "<a href='" . LIME_URL . "/admin/admin.php?interviewer=1&action=quotas&sid={$r['sid']}&quota_id={$r['id']}&subaction=quota_editquota'>" . $r['name'] . "</a>", "quota" => $r['qlimit'], "completions" => $completions, "perc" => $perc);
			}
		}

		
		// At the end   - >  the entire sample

		//We need to calc Sample size, Sample drawn, Sample remain, Completions, %complete
		$sql = "SELECT (c.sample_id is not null) as type, count(*) as count
			FROM sample as s
			JOIN questionnaire_sample as qs ON (qs.questionnaire_id = '$questionnaire_id' and qs.sample_import_id = s.import_id)
			LEFT JOIN `case` as c ON (c.questionnaire_id = qs.questionnaire_id and c.sample_id = s.sample_id)
			WHERE s.import_id = '$sample_import_id'
			GROUP BY (c.sample_id is not null)";
 
		$rs = $db->GetAll($sql);

		//type == 1 is drawn from sample, type == 0 is remains in sample
		$drawn = 0;
		$remain = 0;
		
		foreach ($rs as $r)
		{
			if ($r['type'] == 1) $drawn = $r['count'];
			if ($r['type'] == 0) $remain = $r['count'];
		}

		$sql = "SELECT count(*) as count
			FROM `case` as c, sample as s
			WHERE c.current_outcome_id IN (10,40)
			AND s.import_id = '$sample_import_id'
			AND s.sample_id = c.sample_id
			AND c.questionnaire_id = '$questionnaire_id'";

		$rs = $db->GetRow($sql);
		
		$completions = $rs['count'];

		$report[] = array("strata" => T_("Total sample"), "quota" => $drawn + $remain, "sample" => $drawn + $remain, "sampleused" => $drawn, "sampleremain" => $remain, "completions" => $completions, "perc" => ROUND(($completions / ($drawn + $remain)) * 100,2));
		
		print "<form action='' method='post'>";
			xhtml_table($report,array("strata","status","quota","sample","sampleused","sampleremain","completions","perc","priority","autoprioritise"),array(T_("Strata"),T_("Status"),T_("Quota"),T_("Sample"),T_("Sample Used"),T_("Sample Remaining"),T_("Completions"),T_("% Complete"),T_("Set priority"),T_("Auto prioritise")),"tclass",false,false);
			
		if (count($report) > 1)
			print "<input type='hidden' name='questionnaire_id' id='questionnaire_id' value='$questionnaire_id'/></br>
					<button type='submit' id='submit' name='submit' class='btn btn-primary'/><i class=\"fa fa-refresh fa-lg\"></i>&emsp;" . TQ_("Update priorities") . "</button>";
					
		print "</form>";
	}
	
}

xhtml_foot(array("../js/custom.js"));
?>

<script type="text/javascript">
$('input').iCheck({
	checkboxClass: 'icheckbox_square-blue',
	increaseArea: '30%'
});
</script>
