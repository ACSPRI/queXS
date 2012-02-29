<?
/**
 * Display a list of cases for a questionnaire based on the current outcome 
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2012
 * @package queXS
 * @subpackage user
 * @link http://www.acspri.org.au/software queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include ("../config.inc.php");

/**
 * Database file
 */
include ("../db.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Operator functions
 */
include("../functions/functions.operator.php");

xhtml_head(T_("Cases by outcome"),true,array("../css/table.css"));

//List the cases by outcome
$operator_id = get_operator_id();

if ($operator_id)
{
	//get the outcome and the questionnaire
	$outcome_id = intval($_GET['outcome_id']);
	$questionnaire_id = intval($_GET['questionnaire_id']);

        $sql = "SELECT o.description, q.description as qd
                FROM `outcome` as o, questionnaire as q
                WHERE o.outcome_id = '$outcome_id'
                AND q.questionnaire_id = '$questionnaire_id'";

        $rs = $db->GetRow($sql);

        if (!empty($rs))
        {
                print "<h1>" . T_("Project") . ": {$rs['qd']}</h1>";
                print "<h2>". T_("Current outcome:") ." " . T_($rs['description']) . "</h2>";

		$sql = "SELECT CONCAT('<a href=\'supervisor.php?case_id=', c.case_id, '\'>', c.case_id, '</a>') as case_id
			FROM `case` as c
			WHERE c.questionnaire_id = '$questionnaire_id'
			AND c.current_outcome_id = '$outcome_id'
			LIMIT 500";
			
		$rs = $db->GetAll($sql);
		
		if (empty($rs))
			print "<p>" . T_("No cases with this outcome") . "</p>";
		else
		{
			xhtml_table($rs,array("case_id"),array(T_("Case ID")));
		}
	}
	else
		print "<p>" . T_("Error with input") . "</p>";
}
else
	print "<p>" . T_("No operator") . "</p>";

xhtml_foot();


?>
