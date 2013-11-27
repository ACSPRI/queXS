<?php /**
 * Display a list of cases including status. Allow for assigning to operators in a queue
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2013
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
 * Generate the case status report
 *
 * @param mixed  $questionnaire_id The quesitonnaire, if specified
 * @param string $sample_id        The sample, if speified
 * @param mixed  $outcome_id           THe outcome id, if specified
 * 
 * @return false if empty otherwise true if table drawn
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2012-10-02
 */
function case_status_report($questionnaire_id = false, $sample_id = false, $outcome_id  = false)
{
	global $db;

	$q = "";
	if ($questionnaire_id !== false)
		$q = "AND c.questionnaire_id = $questionnaire_id";

	$s = "";
	if ($sample_id !== false)
		$s = "AND s.import_id = '$sample_id'";

	$o = "";
	if ($outcome_id !== false)
		$o = "AND c.current_outcome_id = $outcome_id";
 
	

	
	$sql = "SELECT 	CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>') as case_id,
			o.description as outcomes,
			si.description as samples,	
			CASE 	WHEN ca.end IS NULL THEN '" . TQ_("Now") . "'
				WHEN TIME_TO_SEC(TIMEDIFF(ca.end,CONVERT_TZ(DATE_SUB(NOW(), INTERVAL co.default_delay_minutes MINUTE),'System','UTC'))) < 0 THEN '" . TQ_("Now") . "'
				ELSE ROUND(TIME_TO_SEC(TIMEDIFF(ca.end,CONVERT_TZ(DATE_SUB(NOW(), INTERVAL co.default_delay_minutes MINUTE),'System','UTC'))) / 60)
			END AS availableinmin,
			CASE 	WHEN oq.operator_id IS NULL THEN CONCAT('". TQ_("Not assigned, select to assign") ." ','<input type=\"checkbox\" name=\"c', c.case_id, '\" value=\"', c.case_id, '\"/>')
				ELSE CONCAT('<a href=\"?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_id&amp;unassign=', cq.case_queue_id, '\"/>". TQ_("Assigned to") . ": ', oq.firstName, ' " . TQ_("Order") . ":', cq.sortorder , ' (". TQ_("Click to unassign") .")</a>')
			END AS assignedoperator
		FROM `case` as c
		JOIN questionnaire as q ON (q.questionnaire_id = c.questionnaire_id and q.enabled = 1)
		JOIN outcome as o ON (o.outcome_id = c.current_outcome_id AND o.outcome_type_id = 1)
		JOIN sample as s ON (s.sample_id = c.sample_id $s)
		JOIN sample_import as si ON (s.import_id = si.sample_import_id)
		LEFT JOIN `call` as ca ON (ca.call_id = c.last_call_id)
		LEFT JOIN outcome as co ON (co.outcome_id = ca.outcome_id)
		LEFT JOIN case_queue as cq ON (cq.case_id = c.case_id)
		LEFT JOIN operator as oq ON (cq.operator_id = oq.operator_id)
		WHERE c.current_operator_id IS NULL $q $o
		ORDER BY availableinmin ASC";

//	print $sql;

	print ("<form method=\"post\" action=\"?questionnaire_id=$questionnaire_id&sample_import_id=$sample_id\">");

	xhtml_table($db->GetAll($sql),array('case_id','outcomes','samples','availableinmin','assignedoperator'),array(T_("Case id"),T_("Outcome"),T_("Sample"),T_("Case available in x minutes"),T_("Assigned to operator")));
	
	$sql = "SELECT operator_id as value,CONCAT(firstName,' ', lastName) as description, '' selected
		FROM operator
		WHERE enabled = 1";
	
	$rs3 = $db->GetAll($sql);

	print "<label for=\"operator_id\">" . T_("Choose operator to assign selected cases to") . ": </label>";
	display_chooser($rs3, "operator_id", "operator_id",true,false,false);
	
	print ("<input type=\"submit\" value=\"" . T_("Assign cases to operator queue") . "\"/>");
	print ("</form>");

	return true;
}

if (isset($_POST['operator_id']) && !empty($_POST['operator_id']))
{
	$operator_id = intval($_POST['operator_id']);

	$db->StartTrans();

	$sql = "SELECT MAX(sortorder)
		FROM case_queue
		WHERE operator_id = '$operator_id'";

	$sortorder = $db->GetOne($sql);

	foreach($_POST as $key => $val)
	{
		$sortorder++;

		if (substr($key,0,1) == "c")
		{
			$sql = "INSERT INTO case_queue (case_id,operator_id,sortorder)
				VALUES ('" . bigintval($val) . "', '$operator_id', '$sortorder')";

			$db->Execute($sql);
		}
	}

	$db->CompleteTrans();
}

if (isset($_GET['unassign']))
{
	$case_queue_id = bigintval($_GET['unassign']);

	$db->StartTrans();

	$sql = "SELECT operator_id 
		FROM case_queue
		WHERE case_queue_id = '$case_queue_id'";

	$operator_id = $db->GetOne($sql);

	$sql = "DELETE FROM case_queue
		WHERE case_queue_id = '$case_queue_id'";

	$db->Execute($sql);

	$sql = "SELECT case_queue_id
		FROM case_queue
		WHERE operator_id = '$operator_id'
		ORDER BY sortorder ASC";

	$rs = $db->GetAll($sql);

	$sortorder = 1;
	foreach($rs as $r)
	{
		$sql = "UPDATE case_queue
			SET sortorder = '$sortorder'
			WHERE case_queue_id = '{$r['case_queue_id']}'";

		$db->Execute($sql);

		$sortorder++;			
	}


	$db->CompleteTrans();

}

xhtml_head(T_("Case status and assignment"),true,array("../css/table.css"),array("../js/window.js"));

print "<p>" . T_("List cases by questionnaire and sample with the ability to assign them to be called next in a queue by a particular operator. If you assign cases to an operator, it will override the normal scheduling process and call them as soon as the operator is available.") . "</p>";

$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);
$sample_import_id = false;
if (isset($_GET['sample_import_id']) && !empty($_GET['sample_import_id'])) $sample_import_id = bigintval($_GET['sample_import_id']);
$outcome_id = false;

print "<label for='questionnaire'>" . T_("Questionnaire") . ":</label>";
display_questionnaire_chooser($questionnaire_id);
print "<label for='sample'>" . T_("Sample") . ":</label>";
display_sample_chooser($questionnaire_id,$sample_import_id);

if ($questionnaire_id)
	case_status_report($questionnaire_id,$sample_import_id,$outcome_id);


xhtml_foot();

?>
