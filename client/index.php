<?php
/**
 * Display outcomes for each questionnaire assigned to this client
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
 * @subpackage client
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
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
include_once(dirname(__FILE__).'/../db.inc.php');

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * AAPOR calculation functions
 */
include ("../functions/functions.aapor.php");

/**
 * Client functions
 */
include ("../functions/functions.client.php");


$client_id = get_client_id();

xhtml_head(T_("Questionnaire Outcomes"),true,array("css/table.css"));

if ($client_id)
{
	$sql = "SELECT q.questionnaire_id,q.description
		FROM questionnaire as q, client_questionnaire as cq
		WHERE cq.questionnaire_id = q.questionnaire_id
		AND q.enabled = 1
		AND cq.client_id = '$client_id'";
	
	$qs = $db->GetAll($sql);

	if (empty($qs))
		print "<p>" . T_("There are no questionnaires assigned to you") . "</p>";
	else
	{
		foreach($qs as $q)
		{
			print "<h2>{$q['description']}</h2>";

			$questionnaire_id = $q['questionnaire_id'];

			$sql = "SELECT o.calc, count( c.case_id )
				FROM `case` AS c, `outcome` AS o
				WHERE c.questionnaire_id = '$questionnaire_id'
				AND c.current_outcome_id = o.outcome_id
				GROUP BY o.calc";
			
			$a = $db->GetAssoc($sql);
			$a = aapor_clean($a);
		
			
			print "<table><tr><th>" . T_("Outcome") . "</th><th>" . T_("Rate") . "</th></tr>"; 
			print "<tr><td>" . T_("Response Rate 1") . "</td><td>" . round(aapor_rr1($a),2) . "</td></tr>";
			print "<tr><td>" . T_("Refusal Rate 1") . "</td><td>" . round(aapor_ref1($a),2) . "</td></tr>";
			print "<tr><td>" . T_("Cooperation Rate 1") . "</td><td>" . round(aapor_coop1($a),2) . "</td></tr>";
			print "<tr><td>" . T_("Contact Rate 1") . "</td><td>" . round(aapor_con1($a),2) . "</td></tr>";
			print "</table>";
			
			
			$sql = "SELECT o.description as des, o.outcome_id, count( c.case_id ) as count
				FROM `case` AS c, `outcome` AS o
				WHERE c.questionnaire_id = '$questionnaire_id'
				AND c.current_outcome_id = o.outcome_id
				GROUP BY o.outcome_id";
			
			$rs = $db->GetAll($sql);
			
			if (!empty($rs))
			{
				translate_array($rs,array("des"));
				xhtml_table($rs,array("des","count"),array(T_("Outcome"),T_("Count")),"tclass",array("des" => "Complete"));
			}
			else
				print "<p>" . T_("No outcomes recorded for this questionnaire") . "</p>";
		
		}

	}



}
else
	print "<p>" . T_("You are not a valid client") . "</p>";



xhtml_foot();

?>
