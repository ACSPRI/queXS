<?
/**
 * Respondent selection introduction 
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


xhtml_head(T_("Respondent Selection - Introduction"),true,array("css/rs.css","include/jquery-ui/css/smoothness/jquery-ui-1.8.2.custom.css"), $js);

//display introduction text


$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);
$questionnaire_id = get_questionnaire_id($operator_id);

//display introduction text
$sql = "SELECT rs_intro,rs_project_intro,rs_callback
	FROM questionnaire
	WHERE questionnaire_id = '$questionnaire_id'";

$r = $db->GetRow($sql);

print "<p class='rstext'>". template_replace($r['rs_intro'],$operator_id,$case_id) . "</p>";


//display outcomes

if (limesurvey_percent_complete($case_id) == false)
{
	if(empty($r['rs_project_intro']))
	{
		//If nothing is specified as a project introduction, skip straight to questionnaire
		?>
		<p class='rsoption'><a href="<? print(get_limesurvey_url($operator_id)); ?>"><? echo T_("Yes - Continue"); ?></a></p>
		<?
	}
	else
	{
		?>
		<p class='rsoption'><a href="rs_project_intro.php"><? echo T_("Yes - Continue"); ?></a></p>
		<?
	}
} else {
	if(empty($r['rs_callback']))
	{
		//If nothing is specified as a callback screen, skip straight to questionnaire
		?>
		<p class='rsoption'><a href="<? print(get_limesurvey_url($operator_id)); ?>"><? echo T_("Yes - Continue"); ?></a></p>
		<?
	}
	else
	{
		?>
		<p class='rsoption'><a href="rs_callback_interface2.php"><? echo T_("Yes - Continue"); ?></a></p>
		<?
	}
}
?>
<p class='rsoption'><a href="rs_business_interface2.php"><? echo T_("Business number"); ?></a></p>
<p class='rsoption'><a href="rs_answeringmachine_interface2.php"><? echo T_("Answering machine"); ?></a></p>
<p class='rsoption'><a href="javascript:parent.location.href = 'index_interface2.php?outcome=2&endcase=endcase'"><? echo T_("End call with outcome: No answer (ring out or busy) "); ?></a></p>
<p class='rsoption'><a href="javascript:parent.location.href = 'index_interface2.php?outcome=18&endcase=endcase'"><? echo T_("End call with outcome: Accidental hang up"); ?></a></p>
<p class='rsoption'><a href="javascript:parent.location.href = 'index_interface2.php?outcome=6&endcase=endcase'"><? echo T_("End call with outcome: Refusal by unknown person"); ?></a></p>
<p class='rsoption'><a href="javascript:parent.location.href = 'index_interface2.php?outcome=8&endcase=endcase'"><? echo T_("End call with outcome: Refusal by respondent"); ?></a></p>
<p class='rsoption'><a href="javascript:parent.location.href = 'index_interface2.php?outcome=17&endcase=endcase'"><? echo T_("End call with outcome: No eligible respondent (person never available on this number)"); ?></a></p>
<p class='rsoption'><a href="javascript:parent.location.href = 'index_interface2.php?outcome=31&endcase=endcase'"><? echo T_("End call with outcome: Non contact (person not currently available on this number: no appointment made)"); ?></a></p>
<p class='rsoption'><a href="javascript:parent.location.href = 'index_interface2.php?outcome=30&endcase=endcase'"><? echo T_("End call with outcome: Out of sample (already completed in another mode)"); ?></a></p>
<?

xhtml_foot();


?>
