<?php 
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


xhtml_head(T_("Respondent Selection - Introduction"),false,array("include/bootstrap-3.3.2/css/bootstrap.min.css","css/rs.css","css/rs.css","include/jquery-ui/css/smoothness/jquery-ui-1.8.2.custom.css"), $js);

$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);
$questionnaire_id = get_questionnaire_id($operator_id);

//display outcomes
?>
<div class="col-sm-3 text-danger">
<h4><?php  echo T_("End call with outcome:"); ?></h4>
<ul class="" style="padding: 0 20px;">
<p class=''><a class="btn btn-default text-danger" href="javascript:parent.location.href = 'index_interface2.php?outcome=1&endcase=endcase'"><?php  echo T_("Not attempted or worked"); ?></a></p></ul>
<ul class="panel-body" style="padding: 0 20px;"><b><?php echo T_("Not Contacted");?></b>
<p class=''><a class="btn btn-default text-danger" href="javascript:parent.location.href = 'index_interface2.php?outcome=2&endcase=endcase'"><?php  echo T_("No answer (ring out or busy) "); ?></a></p>
<p class=''><a class="btn btn-default text-danger" href="javascript:parent.location.href = 'index_interface2.php?outcome=3&endcase=endcase'"><?php  echo T_("Technical phone problems"); ?></a></p>
<p class=''><a class="btn btn-default text-danger" href="javascript:parent.location.href = 'index_interface2.php?outcome=18&endcase=endcase'"><?php  echo T_("Accidental hang up"); ?></a></p></ul>
<ul class="panel-body" style="padding: 0 20px;"><b><?php echo T_("Contacted");?></b>
<p class=''><a class="btn btn-default text-danger" href="javascript:parent.location.href = 'index_interface2.php?outcome=8&endcase=endcase'"><?php  echo T_("Refusal by respondent"); ?></a></p>
<p class=''><a class="btn btn-default text-danger" href="javascript:parent.location.href = 'index_interface2.php?outcome=6&endcase=endcase'"><?php  echo T_("Refusal by unknown person"); ?></a></p>
<p class=''><a class="btn btn-default text-danger" href="javascript:parent.location.href = 'index_interface2.php?outcome=17&endcase=endcase'" title = "<?php  echo T_("No eligible respondent (person never available on this number)"); ?>" ><?php  echo T_("No eligible respondent"); ?> * </a></p>
<p class=''><a class="btn btn-default text-danger" href="javascript:parent.location.href = 'index_interface2.php?outcome=31&endcase=endcase'" title ="<?php  echo T_("Non contact (person not currently available on this number: no appointment made)"); ?>" ><?php  echo T_("Non contact"); ?> * </a></p>
<p class=''><a class="btn btn-default text-danger" href="javascript:parent.location.href = 'index_interface2.php?outcome=30&endcase=endcase'"><?php  echo T_("Out of sample (already completed in another mode)"); ?></a></p>
<p class=''><a class="btn btn-default text-danger" href="rs_business_interface2.php"><?php  echo T_("Business number"); ?></a></p>
<p class=''><a class="btn btn-default text-danger" href="rs_answeringmachine_interface2.php"><?php  echo T_("Answering machine"); ?></a></p></ul>
</div>
<?php 
//display introduction text
$sql = "SELECT rs_intro,rs_project_intro,rs_callback
	FROM questionnaire
	WHERE questionnaire_id = '$questionnaire_id'";

$r = $db->GetRow($sql);
print "</br><div class=\"col-sm-7 rs\">";
print "<p class='rstext'>". template_replace($r['rs_intro'],$operator_id,$case_id) . "</p>";
print "</div>";

// display continue
print "<div class=\"col-sm-2 \" style=\"margin-top: 50px;\">";
if (limesurvey_percent_complete($case_id) == false)
{
	if(empty($r['rs_project_intro']))
	{
		//If nothing is specified as a project introduction, skip straight to questionnaire
		?>
		<p class=''><a class="btn btn-lg btn-primary col-sm-offset-1" href="<?php  print(get_limesurvey_url($operator_id)); ?>"><?php  echo T_("Yes - Continue"); ?></a></p>
		<?php 
	}
	else
	{
		?>
		<p class=''><a class="btn btn-primary" href="rs_project_intro_interface2.php"><?php  echo T_("Yes - Continue"); ?></a></p>
		<?php 
	}
} else {
	if(empty($r['rs_callback']))
	{
		//If nothing is specified as a callback screen, skip straight to questionnaire
		?>
		<p class=''><a class="btn btn-primary" href="<?php  print(get_limesurvey_url($operator_id)); ?>"><?php  echo T_("Yes - Continue"); ?></a></p>
		<?php 
	}
	else
	{
		?>
		<p class=''><a class="btn btn-primary" href="rs_callback_interface2.php"><?php  echo T_("Yes - Continue"); ?></a></p>
		<?php 
	}
}
print "</div>";

xhtml_foot();


?>
