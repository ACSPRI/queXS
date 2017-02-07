<?php 
/**
 * Select and set questions to pre fill in the questionnaire
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

global $db;


if (isset($_GET['questionnaire_id']) && isset($_GET['sgqa'])  && isset($_GET['value']))
{
	//need to add prefill to questionnaire

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$value = $db->quote($_GET['value']);
	$svar = $db->quote($_GET['svar']);
	$sgqa = $db->quote($_GET['sgqa']);

	if (!empty($_GET['svar']) && empty($_GET['value']))
		$value = $svar;

	$sql = "INSERT INTO questionnaire_prefill(questionnaire_id,lime_sgqa,value)
		VALUES('$questionnaire_id',$sgqa,$value)";

	$db->Execute($sql);

}

if (isset($_GET['questionnaire_id']) && isset($_GET['questionnaire_prefill_id']))
{
	//need to remove prefill from questionnaire

	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$questionnaire_prefill_id = bigintval($_GET['questionnaire_prefill_id']);

	$sql = "DELETE FROM questionnaire_prefill
		WHERE questionnaire_prefill_id = '$questionnaire_prefill_id'";

	$db->Execute($sql);

}


$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) 	$questionnaire_id = bigintval($_GET['questionnaire_id']);

$subtitle = T_("Set values for questionnaire to prefill");

xhtml_head(T_("Prefill questionnaire:"),true,array("../include/bootstrap/css/bootstrap.min.css","../css/custom.css"),array("../js/window.js"), false, false, false, $subtitle);
print "<h3 class='form-inline pull-left'>" . T_("Select a questionnaire") . ":&emsp;</h3>";

$sql = "SELECT questionnaire_id as value,description, 
	CASE WHEN questionnaire_id = '$questionnaire_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
 	FROM questionnaire
	WHERE enabled = 1";
display_chooser($db->GetAll($sql),"questionnaire","questionnaire_id", true,false,true,true,false,true,"form-inline form-group");


if ($questionnaire_id != false)
{
	print "<h2>" . T_("Current pre fills") . "</h2>";
	
	$sql = "SELECT questionnaire_prefill_id,lime_sgqa,value
		FROM questionnaire_prefill
		WHERE questionnaire_id = '$questionnaire_id'";

	$r = $db->GetAll($sql);

	if (empty($r))
	{
		print "<p class='well text-info'>" . T_("Currently no pre fills") . "</p>";
	}
	else
	{
		foreach($r as $v)
		{
			print "<ul class='form-group clearfix'><p class='col-sm-2'>" . T_("SGQA code") . ":&emsp;<b class='text-primary'>{$v['lime_sgqa']}</b></p><p class='col-sm-4'>" . T_("Sample variable") . ":&emsp;<b class='text-primary'>{$v['value']}</b></p><a  href='?questionnaire_id=$questionnaire_id&amp;questionnaire_prefill_id={$v['questionnaire_prefill_id']}'><i class='fa fa-lg text-danger'>" . T_("Delete") . "</i></a></ul>";
		}
	}
	print "";

	print "<h3 class='pull-left'>" . T_("Select a question to pre fill") . "&emsp;</h3>";
	
	$sql = "SELECT lime_sid
		FROM questionnaire
		WHERE questionnaire_id = '$questionnaire_id'";

	$r = $db->GetRow($sql);

	$lime_sid = $r['lime_sid'];

	$sgqa = false;
	if (isset($_GET['sgqa'])) 	$sgqa = $_GET['sgqa'];

    /**
     * Display functions
     */
    include_once("../functions/functions.limesurvey.php");

    $rs = lime_list_questions($questionnaire_id);

    //TODO: Test output of listing questions
    var_dump($rs); die();

	for ($i=0; $i<count($rs); $i++)
	{
		$rs[$i]['description'] = substr(strip_tags($rs[$i]['description']),0,400);
	}

	display_chooser($rs,"sgqa","sgqa",true,"questionnaire_id=$questionnaire_id",true,true,false,true,"pull-left");
	print "<div class='clearfix'></div>";
	
	if ($sgqa != false)
	{
		print "<h2 >" . T_("Enter a value to pre fill this question with:") . "</h2>";
		print "<div class='well'><p>";
		print T_("Possible uses:");
		print "</p><ul>";
		print "<li>" . T_("{Respondent:firstName} First name of the respondent") . "</li>";
		print "<li>" . T_("{Respondent:lastName} Last name of the respondent") . "</li>";
		print "<li>" . T_("{Sample:var} A record from the sample where the column name is 'var'") . "</li>";	
		print "</ul></div>";
		
		$sql = "SELECT sivr.var as description, CONCAT('{Sample:', sivr.var, '}') as value
			FROM `sample_import_var_restrict` as sivr, questionnaire_sample as qs
			WHERE qs.questionnaire_id = '$questionnaire_id' 
			AND sivr.sample_import_id = qs.sample_import_id";
		?>
		<form action="" method="get" class="form-inline form-group">
		<p><label for="value"><?php  echo T_("The value to pre fill"); ?>:&emsp;</label><input type="text" name="value" id="value" size="50" class="form-control"/></p>
		<p><label for="svar"><?php  echo T_("or: Select pre fill from sample list"); ?>&emsp;</label>
<?php 	//display a list of possible sample variables for this questionnaire
		display_chooser($db->GetAll($sql),"svar","svar",true,false,false,false,false,true,"form-group");
?>
		</p>
		<input type="hidden" name="questionnaire_id" value="<?php  print($questionnaire_id); ?>"/>
		<input type="hidden" name="sgqa" value="<?php  print($sgqa); ?>"/>
		</br>
		<p><input type="submit" name="add_prefill" class="btn btn-primary fa" value="<?php  print(T_("Add pre fill")); ?>"/></p>
		</form>
		<?php 
	}
}


xhtml_foot();

?>
