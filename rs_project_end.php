<?php 
/**
 * Respondent selection - Project End
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
 * @copyright Australian Consortium for Social and Political Research Inc 2007,2008
 * @package queXS
 * @subpackage user
 * @link http://www.acspri.org.au/ queXS was writen for Australian Consortium for Social and Political Research Incorporated (ACSPRI)
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include ("config.inc.php");

/**
 * Database file
 */
include ("db.inc.php");

/** 
 * Authentication
 */
require ("auth-interviewer.php");


/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include ("functions/functions.operator.php");

$operator_id = get_operator_id();

$js = array("js/popup.js","include/jquery/jquery-1.4.2.min.js","include/jquery-ui/jquery-ui.min.js");

if (AUTO_LOGOUT_MINUTES !== false) $js[] = "js/childnap.js";


xhtml_head(T_("Respondent Selection") . " - " . T_("Project end"),true,array("include/bootstrap/css/bootstrap.min.css","css/rs.css"), $js);

$case_id = get_case_id($operator_id);
$questionnaire_id = get_questionnaire_id($operator_id);

//display introduction text
$sql = "SELECT rs_project_end
	FROM questionnaire
	WHERE questionnaire_id = '$questionnaire_id'";

$r = $db->GetRow($sql);

if (!empty($r['rs_project_end']))  print "<p class='rstext well'>" . template_replace($r['rs_project_end'],$operator_id,$case_id) . "</p>";

print "<p class='well'>";

if (!is_voip_enabled($operator_id) && AUTO_COMPLETE_OUTCOME)
{
	end_call($operator_id,10);

	print T_("Call automatically ended with outcome:") . "&ensp;<b>" . T_("Complete") . "</b>"; 
	//check for alternate interface
	if (ALTERNATE_INTERFACE && !is_voip_enabled($operator_id))
		print "&emsp;<a href=\"javascript:parent.location.href = 'index_interface2.php?endcase=endcase'\" class='btn btn-primary'>" . T_("End case") . "</a>";
}
else
{	
	print T_("End call with outcome:") . "&emsp;<a class='btn btn-primary' ";
	//check for alternate interface
	if (ALTERNATE_INTERFACE && !is_voip_enabled($operator_id))
		print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=10&amp;endcase=endcase'\">"; 
	else 
		print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=10');\">";
	
	print T_("Complete") . "</a>";
}

print "</p>";

xhtml_foot();

?>
