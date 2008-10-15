<?
/**
 * Respondent selection - Project Introduction 
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


xhtml_head(T_("Respondent Selection - Project Introduction"),true,array("css/rs.css"),array("js/popup.js"));


$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);
$questionnaire_id = get_questionnaire_id($operator_id);

//display introduction text
$sql = "SELECT rs_project_intro
	FROM questionnaire
	WHERE questionnaire_id = '$questionnaire_id'";

$r = $db->GetRow($sql);

print "<p class='rstext'>" . template_replace($r['rs_project_intro'],$operator_id,$case_id) . "</p>";


//display outcomes

?>

<p class='rsoption'><a href="<? print(get_limesurvey_url($operator_id)); ?>"><? echo T_("Yes - Continue"); ?></a></p>

<p class='rsoption'><a href="javascript:poptastic('call.php?defaultoutcome=8');"><? echo T_("End call with outcome: Refusal by respondent"); ?></a></p>
<p class='rsoption'><a href="javascript:poptastic('call.php?defaultoutcome=17');"><? echo T_("End call with outcome: No eligible respondent (person not available on this number)"); ?></a></p>
<p class='rsoption'><a href="javascript:poptastic('call.php?defaultoutcome=30');"><? echo T_("End call with outcome: Out of sample (already completed in another mode)"); ?></a></p>

<p class='rsoption'><a href="rs_intro.php"><? echo T_("Go Back"); ?></a></p>

<?

xhtml_foot();


?>
