<?
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

xhtml_head(T_("Pre fill questionnaire: Set values for questionnaire to prefill"),true,false,array("../js/window.js"));
print "<h1>" . T_("Select a questionnaire from the list below") . "</h1>";

$sql = "SELECT questionnaire_id as value,description, CASE WHEN questionnaire_id = '$questionnaire_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
 	FROM questionnaire
	WHERE enabled = 1";
display_chooser($db->GetAll($sql),"questionnaire","questionnaire_id");


if ($questionnaire_id != false)
{
	print "<h1>" . T_("Current pre fills (click to delete)") . "</h1>";
	
	$sql = "SELECT questionnaire_prefill_id,lime_sgqa,value
		FROM questionnaire_prefill
		WHERE questionnaire_id = '$questionnaire_id'";

	$r = $db->GetAll($sql);

	if (empty($r))
	{
		print "<p>" . T_("Currently no pre fills") . "</p>";
	}
	else
	{
		foreach($r as $v)
		{
			print "<div><a href='?questionnaire_id=$questionnaire_id&amp;questionnaire_prefill_id={$v['questionnaire_prefill_id']}'>{$v['lime_sgqa']}: {$v['value']}</a></div>";

		}
	}


	print "<h1>" . T_("Select a question to pre fill") . "</h1>";
	
	$sql = "SELECT lime_sid
		FROM questionnaire
		WHERE questionnaire_id = '$questionnaire_id'";

	$r = $db->GetRow($sql);

	$lime_sid = $r['lime_sid'];

	$sgqa = false;
	if (isset($_GET['sgqa'])) 	$sgqa = $_GET['sgqa'];

	$sql = "SELECT CONCAT( q.sid, 'X', q.gid, 'X', q.qid, IFNULL( a.code, '' ) ) AS value, SUBSTR(CONCAT(q.question, ': ', IFNULL(a.answer,'')),1,100) as description, CASE WHEN CONCAT( q.sid, 'X', q.gid, 'X', q.qid, IFNULL( a.code, '' ) ) = '$sgqa' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM `" . LIME_PREFIX . "questions` AS q
		LEFT JOIN `" . LIME_PREFIX . "answers` AS a ON ( a.qid = q.qid )
		WHERE q.sid = '$lime_sid'";


	display_chooser($db->GetAll($sql),"sgqa","sgqa",true,"questionnaire_id=$questionnaire_id");

	if ($sgqa != false)
	{
		print "<h1>" . T_("Enter a value to pre fill this question with:") . "</h1>";
		print "<p>";
		print T_("Possible uses:");
		print "</p><ul>";
		print "<li>" . T_("{Respondent:firstName} First name of the respondent") . "</li>";
		print "<li>" . T_("{Respondent:lastName} Last name of the respondent") . "</li>";
		print "<li>" . T_("{Sample:var} A record from the sample where the column name is 'var'") . "</li>";	
		
		$sql = "SELECT sv.var as description, CONCAT('{Sample:', sv.var, '}') as value
			FROM `sample` AS s, sample_var AS sv, questionnaire_sample as qs
			WHERE qs.questionnaire_id = '$questionnaire_id' 
			AND s.import_id = qs.sample_import_id
			AND s.sample_id = sv.sample_id
			GROUP BY sv.var";

				print "</ul>";
		?>
		<form action="" method="get">
		<p>
		<label for="value"><? echo T_("The value to pre fill"); ?> </label><input type="text" name="value" id="value"/>		<br/>
		<label for="svar"><? echo T_("or: Select pre fill from sample list"); ?> </label>
<?	//display a list of possible sample variables for this questionnaire
		display_chooser($db->GetAll($sql),"svar","svar",true,false,false,false,false);
?>		<br/>
	<input type="hidden" name="questionnaire_id" value="<? print($questionnaire_id); ?>"/>
		<input type="hidden" name="sgqa" value="<? print($sgqa); ?>"/>
		<input type="submit" name="add_prefill" value="<? print(T_("Add pre fill")); ?>"/></p>
		</form>
		<?
	}
}
xhtml_foot();


?>
