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
include_once ("config.inc.php");

/**
 * Database file
 */
include_once ("db.inc.php");

/**
 * XHTML functions
 */
include_once ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include_once ("functions/functions.operator.php");


$js = array("js/popup.js","include/jquery/jquery-1.4.2.min.js","include/jquery-ui/jquery-ui.min.js");

if (AUTO_LOGOUT_MINUTES !== false)
{  
        $js[] = "js/childnap.js";
}



xhtml_head(T_("Respondent Selection - Project end"),true,array("css/rs.css","include/jquery-ui/jquery-ui.min.css"), $js);

$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);
$questionnaire_id = get_questionnaire_id($operator_id);

//display introduction text
$sql = "SELECT rs_project_end
	FROM questionnaire
	WHERE questionnaire_id = '$questionnaire_id'";

$r = $db->GetRow($sql);

print "<p class='rstext'>" . template_replace($r['rs_project_end'],$operator_id,$case_id) . "</p>";

if (!is_voip_enabled($operator_id) && AUTO_COMPLETE_OUTCOME)
{
	end_call($operator_id,10);
	print "<p class='rsoption'><a href=\"javascript:parent.location.href = 'index_interface2.php?endcase=endcase'\">" . T_("Call automatically ended with outcome: Complete - End case") . "</a></p>";
}
else
{
	?>
	<p class='rsoption'><a href="javascript:parent.location.href = 'index_interface2.php?outcome=10&endcase=endcase'"><?php  echo T_("End call with outcome: Complete"); ?></a></p>
	<?php 
}
xhtml_foot();

?>
