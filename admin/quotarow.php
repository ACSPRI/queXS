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

      $sql = "SELECT exclude_var_id, exclude_var as samplerecord,comparison,exclude_val as value
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
/* if (isset($_GET['questionnaire_id']) && isset($_GET['sgqa'])  && isset($_GET['value']) && isset($_GET['completions']) && isset($_GET['sample_import_id']) && isset($_GET['comparison']) && isset($_GET['exclude_var_id']) && isset($_GET['exclude_var']) && isset($_GET['exclude_val'])) */
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
	$exvar_id = $db->qstr(substr($_POST['sample_var_id'],15,strpos($_POST['sample_var_id'],'&')-15));
    $exvar = $db->qstr($_POST['sample_var']);
    $exval = $db->qstr($_POST['exclude_val']);
    $comparison = $db->qstr($_POST['comparisons']);
	$description = $db->qstr($_POST['description']);

    //add ssample
    $sql = "INSERT INTO qsqr_sample (questionnaire_sample_quota_row_id,exclude_var_id,exclude_var,exclude_val,comparison,description)
            VALUES ($qsqri,$exvar_id,$exvar,$exval,$comparison,$description)";

    $db->Execute($sql);

	  //Make sure to calculate on the spot
  	update_single_row_quota($qsqri);
  }

  if (isset($_POST['addq']))
  {
    $sgqa = $db->qstr(substr($_POST['sgqa'],6,strpos($_POST['sgqa'],'&')-6));
	$value = $db->qstr($_POST['value']);
	$comparison = $db->qstr($_POST['comparison']);
	$description = $db->qstr($_POST['description']);
    //add ssample
    $sql = "INSERT INTO qsqr_question (questionnaire_sample_quota_row_id,lime_sgqa,value,comparison,description)
            VALUES ($qsqri,$sgqa,$value,$comparison,$description)";

    $db->Execute($sql);

  	//Make sure to calculate on the spot
  	update_single_row_quota($qsqri);
  }
}

$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) 	$questionnaire_id = bigintval($_GET['questionnaire_id']);

xhtml_head(T_("Quota row management"),true,array("../include/bootstrap/css/bootstrap.min.css","../include/bootstrap-toggle/css/bootstrap-toggle.min.css","../include/font-awesome/css/font-awesome.css","../include/iCheck/skins/square/blue.css","../css/custom.css"),array("../include/jquery/jquery.min.js","../include/bootstrap/js/bootstrap.min.js","../include/bootstrap-toggle/js/bootstrap-toggle.min.js","../include/iCheck/icheck.min.js","../js/window.js"));
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
		AND q.sample_import_id = s.sample_import_id
		AND s.enabled = 1";
	$s = $db->GetAll($sql);
	if (!empty($s)){
		print "<h3 class='form-inline pull-left'>&emsp;&emsp;&emsp;" . T_("Sample") . ":&emsp;</h3>";

	display_chooser($s,"sample","sample_import_id",true,"questionnaire_id=$questionnaire_id",true,true,false,true,"pull-left");
	} else {	
		print "<div class='clearfix'></div><div class='well text-info'>" . T_("No samples assigned to this questionnaire.") . "</div>";
	}

	print "<div class='col-sm-2 pull-right'><a href='quotareport.php?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id' class='btn btn-info btn-block'><i class='fa fa-filter fa-lg'></i>&emsp;" . T_("To quota report") . "</a></div>";
	
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
            //one quota record per row,  placed in order of records sequence
          	$description = $db->quote($data[0]);
			$completions = intval($data[1]);
          	$autoprioritise = 0;
          	if ($data[2] != 0) $autoprioritise = 1;
      
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
				  $var_id = $db->qstr($data[$i*3]);
				  $var = $db->qstr($data[($i*3) + 1]);
                  $comparison = $db->qstr($data[($i*3) + 2]);
                  $value = $db->qstr($data[($i*3) + 3]);

                  $sql = "INSERT INTO qsqr_sample (questionnaire_sample_quota_row_id,exclude_var_id,exclude_var,exclude_val,comparison)
                          VALUES ($qq,$var_id,$var,$value,$comparison)";

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
      print "<div class='col-lg-3 pull-right'><a href='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id' class='btn btn-default'><i class='fa fa-arrow-up fa-lg text-primary'></i>&emsp;" . T_("To Row quotas") . "</a></div>";
	  print "<div class='clearfix form-group'></div>";
      print "<h2 class='col-lg-offset-4'>" . T_("Quota") . ": $qsqrid</h2>";
	  
      ?>
	  <div class='panel-body' >
      <form action="?<?php echo "questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id"; ?>" method="post" class="form-inline table">
		<p><label for="description" class="control-label"><?php  echo T_("Describe this quota"); ?>:&emsp;</label><input type="text" name="description" id="description" class="form-control" value="<?php echo $qsqrid;?>" required size="70"/>&emsp;&emsp;<label for="completions" class="control-label"><?php  echo T_("The number of completions to stop calling at"); ?>:&emsp;</label><input type="number" class="form-control" name="completions" id="completions" value="<?php echo $qsqric; ?>" min="0" size="6" maxlength="6" style="width:6em;" required /></p>
        <p><label for="priority" class="control-label"><?php  echo T_("Quota priority (50 is default, 100 highest, 0 lowest)"); ?>:&emsp;</label><input type="number" class="form-control" name="priority" id="priority" value="<?php echo $qsqrip;?>" min="0" max="100" style="width:5em;"/>&emsp;&emsp;<label for="autoprioritise" class="control-label"><?php  echo T_("Should the priority be automatically updated based on the number of completions in this quota?"); ?>&emsp;</label><input type="checkbox" name="autoprioritise" id="autoprioritise" <?php echo $qsqrich; ?>data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-offstyle="warning"/></p>
        <input type="hidden" name="qsqri" value="<?php echo $qsqri; ?>"/>
		<p><button type="submit" name="edit_quota" class="btn btn-primary"><i class="fa fa-floppy-o fa-lg"></i>&emsp;<?php  print(T_("Save changes")); ?></button></p>
      </form>
	  </div>
      <?php

      //display questionnaire references
      $sql = "SELECT qsqr_question_id,lime_sgqa,value,comparison,description,
              CONCAT('&emsp;<a href=\'?edit=edit&amp;qsqri=$qsqri&amp;delete=delete&amp;qsqrqi=', qsqr_question_id, '\'><i class=\"fa fa-trash-o fa-lg text-danger\"></i></a>&emsp;') as qdelete
              FROM qsqr_question
              WHERE questionnaire_sample_quota_row_id = $qsqri";
  
      $rs = $db->GetAll($sql);
	  
		print "<div class='panel panel-default'>";
		print "<div class='panel-heading'><h3 class='text-center'>" . T_("Restrictions based on answered questions") . "</h3></div>";
		print "<div class='panel-body'>";
		
      if (empty($rs))
      {
        print "<h4 class='well text-info'>" . T_("Currently NO Restrictions based on answered questions") . " </p>" . T_("All completed responses that match the sample criteria below will be counted towards the quota") . "</h4>";
      }
      else
      {
        print "<div class='well'><h4 class='text-info'>" . T_("Only completed responses that have answered the following will be counted") . "</h4>";
        xhtml_table($rs,array('description','lime_sgqa','comparison','value','qdelete'),array(T_("Description"),T_("SGQ code"),T_("Comparison"),T_("Value"),"&emsp;<i class='fa fa-trash-o fa-lg' data-toggle='tooltip' title='" . T_("Delete") . "'></i>&emsp;"));
		print "</div>";
      }

      //add questionnaire references if any (refer to sample only or count completions based on responses to questions)

      $sql = "SELECT lime_sid 
              FROM questionnaire
              WHERE questionnaire_id = $questionnaire_id";

      $lime_sid = $db->GetOne($sql);

      $ssgqa = "''";
      if (isset($_GET['sgqa']))
        $ssgqa = $db->qstr($_GET['sgqa']);

      //select question + corrected question order as in questionnaire with subquestions 
  		$sql = "SELECT CONCAT( lq.sid, 'X', lq.gid, 'X', CASE WHEN lq.parent_qid = 0 THEN lq.qid ELSE CONCAT(lq.parent_qid, lq.title) END) as value, CONCAT( lq.sid, 'X', lq.gid, 'X', CASE WHEN lq.parent_qid = 0 THEN lq.qid ELSE CONCAT(lq.parent_qid, lq.title) END, '&ensp;->&ensp;' , CASE WHEN lq.parent_qid = 0 THEN lq.question ELSE CONCAT(lq2.question, ' : ', lq.question) END) as description, CASE WHEN $ssgqa LIKE CONCAT( lq.sid, 'X', lq.gid, 'X', CASE WHEN lq.parent_qid = 0 THEN lq.qid ELSE CONCAT(lq.parent_qid, lq.title) END) THEN 'selected=\'selected\'' ELSE '' END AS selected
	  		FROM `" . LIME_PREFIX . "questions` AS lq
  			LEFT JOIN `" . LIME_PREFIX . "questions` AS lq2 ON ( lq2.qid = lq.parent_qid )
  			JOIN `" . LIME_PREFIX . "groups` as g ON (g.gid = lq.gid)
  			WHERE lq.sid = '$lime_sid'
  			ORDER BY CASE WHEN lq2.question_order IS NULL THEN lq.question_order ELSE lq2.question_order + (lq.question_order / 1000) END ASC";

	  	$rsgqa = $db->GetAll($sql);
  
      if (!empty($rsgqa))
      {
        print "<div class=''><form method='post' action='?qsqri=$qsqri&amp;edit=edit' class='form-group'>";
        print "<h3>" . T_("Add restriction based on answered questions") . " </h3>";
		print "<label for='sgqa' class='control-label'>" . T_("Select Question") . ": </label>";
        display_chooser($rsgqa,"sgqa","sgqa",true,"edit=edit&amp;qsqri=$qsqri");
		
		if (isset($_GET['sgqa'])){
        ?>
		<div class='col-sm-6 panel-body form-inline'>
		<p><label for="comparison" class="control-label"><?php  echo T_("The type of comparison"); ?>:&emsp;</label>
			<select name="comparison" class="form-control" id="comparison"><option value="LIKE">LIKE</option><option value="NOT LIKE">NOT LIKE</option><option value="=">=</option><option value="!=">!=</option><option value="&lt;">&lt;</option><option value="&gt;">&gt;</option><option value="&lt;=">&lt;=</option><option value="&gt;=">&gt;=</option></select>&emsp;&emsp;<label for="value" class="control-label"><?php  echo T_("The code value to compare"); ?>:&emsp;</label><input type="text" name="value" id="value" class="form-control" required /></p>
        <p><label for="description" class="control-label"><?php  echo T_("Description"); ?>:&emsp;</label><input type="text" class="form-control" name="description" id="description"  size="80"/></p>
		<p><button type="submit" name="addq" class="btn btn-primary"/><i class="fa fa-plus-circle fa-lg"></i>&emsp;<?php echo TQ_("Add restriction") ?></button></p>
		</div></form>
        <?php
		
		print "<div class='col-sm-6 '><h4 class= 'pull-left'>" . T_("Code values for this question") . ":&emsp;</h4>";
		
		$rs = "";
		if (isset($_GET['sgqa']))
			{
				$sgqa = $_GET['sgqa'];
				$qid = explode("X", $sgqa);
				$qid = $qid[2];
		
				$sql = "SELECT CONCAT('<b class=\'fa\'>&emsp;', l.code , '</b>')as code, l.answer as title
					FROM `" . LIME_PREFIX . "answers` as l
					WHERE l.qid = '$qid'";
				$rsc = $db->GetAll($sql);
			}
		if (!isset($rsc) || empty($rsc))
			print "<h4 class= 'alert alert-info'>" . T_("No labels defined for this question") ."</h4>";
		else 
			xhtml_table($rsc,array('code','title'),array(T_("Code value"), T_("Description")));
		}
		else { print "</form>";	}
		print "</div></div>";
      }
	print "</div></div>";
	
      //list sample records to exclude

      $sql = "SELECT qsqr_sample_id,exclude_var_id,exclude_var,exclude_val,comparison,description,
              CONCAT('&emsp;<a href=\'?qsqri=$qsqri&amp;edit=edit&amp;delete=delete&amp;qsqrsi=',qsqr_sample_id,'\'><i class=\"fa fa-trash-o fa-lg text-danger\"></i></a>&emsp;') as sdelete
              FROM qsqr_sample
              WHERE questionnaire_sample_quota_row_id = $qsqri";

      $rs = $db->GetAll($sql);
	  
	print "<div class='panel panel-default'>";
	print "<div class='panel-heading'><h3 class='text-center'>" . T_("Restrictions based on sample records") . "</h3></div>";
	print "<div class='panel-body'>";
      if (empty($rs))
      {
        print "<h4 class='well text-info'>" . T_("Currently NO Restrictions based on sample records") . " </p>" . T_("This sample will be limited to number of completions set in quota") . " </p>" . T_("Caling cases for this sample will be stopped when the quota is reached") . "</h4>";
      }
      else
      {
        print "<div class='well'><h4 class='text-info'>" . T_("Completed responses that have the following sample details will be counted towards the quota and excluded when the quota is reached") . "</h4>";
        xhtml_table($rs,array('description','exclude_var_id','exclude_var','comparison','exclude_val','sdelete'),array(T_("Description"),T_("Sample var ID"),T_("Sample variable"),T_("Comparison"),T_("Value"),"&emsp;<i class='fa fa-trash-o fa-lg' data-toggle='tooltip' title='" . T_("Delete") . "'></i>&emsp;"));
		print "</div>";
      }

	  $ssample_var_id = "''";
	  if (isset($_GET['sample_var_id'])) $ssample_var_id = $db->qstr($_GET['sample_var_id']);   

      //add sample references (records from sample to exclude when quota reached)
			$sql = "SELECT sivr.var_id as value, sivr.var as description, 
					CASE WHEN sivr.var_id = $ssample_var_id THEN 'selected=\'selected\'' ELSE '' END AS selected
      				FROM `sample_import_var_restrict` as sivr, `sample_var` AS sv, `sample` AS s 
      				WHERE sivr.sample_import_id = $sample_import_id
      				AND s.sample_id = sv.sample_id
					AND sivr.var_id = sv.var_id
              GROUP BY sivr.var_id";

      $rsvi = $db->GetAll($sql);

      if (!empty($rsvi))
      {
        if ($ssample_var_id == "''")
          $ssample_var_id = $rsvi[0]['value'];

        print "<h3>" . T_("Add restriction based on sample records") . "</h3>";
		print "<form method='post' action='?edit=edit&amp;qsqri=$qsqri' class='form-inline table'>";
        print "<p><label for='sample_var' class='control-label'>" . T_("Sample record") . "</label>:&emsp;";
			  display_chooser($rsvi,"sample_var_id","sample_var_id",true,"edit=edit&amp;qsqri=$qsqri",true,false);
		
		if (isset($_GET['sample_var_id'])){
		?>
			
			&emsp;<label for="comparisons" class="control-label"><?php  echo T_("The type of comparison"); ?></label>:&emsp;<select name="comparisons" id="comparisons" class="form-control"><option value="LIKE">LIKE</option><option value="NOT LIKE">NOT LIKE</option><option value="=">=</option><option value="!=">!=</option><option value="&lt;">&lt;</option><option value="&gt;">&gt;</option><option value="&lt;=">&lt;=</option><option value="&gt;=">&gt;=</option></select>&emsp;
			<label for="exclude_val" class="control-label"><?php  echo T_("Value"); ?>:&emsp;</label>
		<?php 		
			$sql = "SELECT sv.val as value, sv.val as description, ''  AS selected, sivr.var as var
				FROM sample_var AS sv, sample AS s, `sample_import_var_restrict` as sivr
				WHERE s.import_id = $sample_import_id
				AND s.sample_id = sv.sample_id
				AND sv.var_id = $ssample_var_id
				AND sivr.var_id = sv.var_id
				GROUP BY sv.val";
			$val = $db->GetAll($sql);
			$sample_var = $val[0]['var'];
			display_chooser($val,"exclude_val","exclude_val",false,false,false,false);
			flush();
		?>
			&emsp;</p>
			<p><label for="description" class="control-label"><?php  echo T_("Description"); ?>:&emsp;</label><input type="text" class="form-control" name="description" id="description"  size="80"/></p>
			<input type="hidden" name="sample_var" value="<?php  print $sample_var; ?>"/>
			<p><button type="submit" class="btn btn-primary" name="adds" value=""/><i class="fa fa-plus-circle fa-lg"></i>&emsp;<?php echo TQ_("Add restriction") ?></button></p>
        <?php
		} else { print "</p>";}		
		print "</form>";
      }
	  		print "</div></div>";
    }
    else
    {
		$sql = "SELECT questionnaire_sample_quota_row_id,qsq.description,
            CONCAT('&emsp;<a href=\'?edit=edit&amp;qsqri=',questionnaire_sample_quota_row_id,'\'><i class=\"fa fa-pencil-square-o fa-lg text-danger\"></i></a>&emsp;') as qedit,
            CONCAT('<div class=\'text-center\'><input type=\'checkbox\' name=\'select_',questionnaire_sample_quota_row_id,'\'/></div>') as qselect,
            qsq.completions,qsq.quota_reached,qsq.current_completions,
            CASE WHEN qsq.autoprioritise = 1 THEN '" . TQ_("Yes") . "' ELSE '" . TQ_("No") . "' END AS ap, qsq.priority,
            CASE WHEN qsq.quota_reached = 1 THEN '" . TQ_("closed") . "' ELSE '" . TQ_("open") . "' END AS status
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
			print "<form method='post' action='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id'>";
				print "<div class='col-sm-12 panel-body'></br><h2>" . T_("Current row quotas") . "</h2><div class='pull-left'>";
				xhtml_table($r,array('description','qedit','completions','current_completions','status','priority','ap','qselect'),array(T_("Description"),"&emsp;<i class='fa fa-pencil-square-o fa-lg' data-toggle='tooltip' title='" . T_("Edit") . "'></i>&emsp;",T_("Quota"),T_("Completions"),T_("Status"),T_("Priority"),T_("Auto prioritise"),"&emsp;<i class='fa fa-check-square-o fa-lg' data-toggle='tooltip' title='" . T_("Select") . "'></i>&emsp;"));
				print "</div><div class='pull-left'></br>";
				print "<button class='btn btn-default col-sm-offset-2' type='submit' name='submitexport'><i class=\"fa fa-download fa-lg text-primary\"></i>&emsp;" . TQ_("Export selected") . "</button></br></br>";
				print "<button class='btn btn-default col-sm-offset-2' type='submit' name='submitdelete'><i class=\"fa fa-trash-o fa-lg text-danger\"></i>&emsp;
" . TQ_("Delete selected") . "</button></div></div>";
			print "</form>";
	    }

		print "<div class='col-sm-8 panel-body'><h2>" . T_("Add row quota") . "</h2>";
    ?>
		<form action="?<?php echo "questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id"; ?>" method="post" class="form-inline table">
			<p><label for="description" class="control-label"><?php  echo T_("Describe this quota"); ?>: &emsp;</label><input type="text" class="form-control" name="description" id="description" required size="70"/></p>
			<p><label for="priority" class="control-label"><?php  echo T_("Quota priority (50 is default, 100 highest, 0 lowest)"); ?>:&emsp;</label><input type="number" class="form-control" name="priority" id="priority" value="50" min="0" max="100" style="width:5em;"/></p>
			<p><label for="completions" class="control-label"><?php  echo T_("The number of completions to stop calling at"); ?>:&emsp;</label><input type="number" class="form-control" name="completions" id="completions" min="0" size="6" maxlength="6" style="width:6em;" required/></p>
			<p><label for="autoprioritise" class="control-label"><?php  echo T_("Should the priority be automatically updated based on the number of completions in this quota?"); ?> &emsp;</label><input type="checkbox" name="autoprioritise" id="autoprioritise" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-offstyle="warning"/></p>
			<p><button type="submit" class="btn btn-primary" name="add_quota"/><i class="fa fa-plus-circle fa-lg"></i>&emsp;<?php  print(T_("Add row quota")); ?></button></p>
		</form>
		</div>
    <?php
//check user agent to properly filter *.csv file type 
		print "<div class='col-sm-4 panel-body'><h2>" . T_("Import row quota") . "</h2>";
$ua = $_SERVER['HTTP_USER_AGENT'];
if (preg_match('/Firefox/i', $ua)) $csv= "text/csv"; else $csv= ".csv";
    ?>
			<form enctype="multipart/form-data" action="<?php echo "?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id"; ?>" method="post">
				<input type="hidden" name="MAX_FILE_SIZE" value="1000000000" />
				<h4><?php  echo T_("Choose the CSV row quota file to import:"); ?></h4>
				<p><input id="file" name="file" type="file" class="filestyle form-group" required data-buttonBefore="true" data-iconName="fa fa-folder-open fa-lg text-primary " data-buttonText="<?php  echo T_("Select file"); ?>..." accept="<?php echo $csv; ?>"/></p>
				<button type="submit" class="btn btn-primary" name="import_quota" ><i class="fa fa-upload fa-lg"></i>&emsp;<?php  print(T_("Import row quota")); ?></button>
			</form>
		</div>
    <?php
    }
  }
}
xhtml_foot(array("../js/bootstrap-filestyle.min.js","../js/custom.js"));

?>

<script type="text/javascript">
$('input').iCheck({
	checkboxClass: 'icheckbox_square-blue',
	increaseArea: '30%'
});
</script>
