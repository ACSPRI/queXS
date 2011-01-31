<?
/**
 * Respondent selection - Call back (respondent already started questionnaire) 
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
 * @subpackage user
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
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
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include ("functions/functions.operator.php");

/**
 * Limesurvey functions
 */
include ("functions/functions.limesurvey.php");

$js = array("js/popup.js","include/jquery-ui/js/jquery-1.4.2.min.js","include/jquery-ui/js/jquery-ui-1.8.2.custom.min.js");

if (AUTO_LOGOUT_MINUTES !== false)
{  
        $js[] = "js/childnap.js";
}



xhtml_head(T_("Respondent Selection - Call back"),true,array("css/rs.css","include/jquery-ui/css/smoothness/jquery-ui-1.8.2.custom.css"), $js );

$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);
$questionnaire_id = get_questionnaire_id($operator_id);

//display introduction text
$sql = "SELECT rs_callback
	FROM questionnaire
	WHERE questionnaire_id = '$questionnaire_id'";

$r = $db->GetRow($sql);

print "<p class='rstext'>" . template_replace($r['rs_callback'],$operator_id,$case_id) . "</p>";
print "<p class='rstext'>" . T_("You are: ") . round(limesurvey_percent_complete($case_id),1) . T_("% complete") . "</p>";


//display outcomes

?>

<p class='rsoption'><a href="<? print(get_limesurvey_url($operator_id)); ?>"><? echo T_("Yes - Continue where we left off"); ?></a></p>
<p class='rsoption'><a href="javascript:parent.poptastic('call.php?defaultoutcome=8');"><? echo T_("End call with outcome: Refusal by respondent"); ?></a></p>
<p class='rsoption'><a href="rs_intro.php"><? echo T_("Go Back"); ?></a></p>
<?

xhtml_foot();


?>
