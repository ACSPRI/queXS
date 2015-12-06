<?php 
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

$js = array("js/popup.js","include/jquery/jquery-1.4.2.min.js","include/jquery-ui/jquery-ui.min.js");

if (AUTO_LOGOUT_MINUTES !== false) $js[] = "js/childnap.js";


xhtml_head(T_("Respondent Selection") . " - " . T_("Project Introduction"),true,array("include/bootstrap/css/bootstrap.min.css","css/rs.css"), $js); //,"include/jquery-ui/jquery-ui.min.css"

$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);
$questionnaire_id = get_questionnaire_id($operator_id);

//display introduction text
$sql = "SELECT rs_project_intro
	FROM questionnaire
	WHERE questionnaire_id = '$questionnaire_id'";

$r = $db->GetRow($sql);

if (!empty($r['rs_project_intro'])) print "<div class='rstext well rs'>" . template_replace($r['rs_project_intro'],$operator_id,$case_id) . "</div>";

print "<div class=' '>
		<div class='col-lg-2'><p><a class='btn btn-default' href=\"rs_intro.php\" >" . T_("Go Back") . "</a></p></div>";
			
	if ($questionnaire_id){
		//filter displayed outcomes
		$outcomes = $db->GetOne("SELECT q.outcomes FROM `questionnaire` as q WHERE q.questionnaire_id = $questionnaire_id");//
		$outcomes = explode(",",$outcomes);
		
		$des = $db->GetAll("SELECT description FROM outcome WHERE outcome_id IN (8,17,30)");
		translate_array($des,array("description"));
		
		print "<div class='col-lg-6'>
				<p><h4 class=''>" . T_("End call with outcome:") . "</h4></p>";
			
			if (in_array(8,$outcomes)){
				print "<p><a class='btn btn-primary' ";
					if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=8&amp;endcase=endcase'\">";
					else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=29');\">";
				print $des[0]['description'] . "</a></p>";
			}

			if (in_array(17,$outcomes)){
				print "<p><a class='btn btn-primary' ";
					if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=17&amp;endcase=endcase'\">";
					else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=23');\">";
				print $des[1]['description'] . "</a></p>";
			}

			if (in_array(30,$outcomes)){
				print "<p><a class='btn btn-primary' ";
					if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=30&amp;endcase=endcase'\">";
					else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=24');\">";
				print $des[2]['description'] . "</a></p>";
			}
		print "</div>";
	}
		print "<div class='col-lg-4'><p><a class=\"btn btn-lg btn-primary\" href=\"" . (get_limesurvey_url($operator_id)) . "\">" . T_("Yes - Continue") . "</a></p></div>";
print "</div>";


xhtml_foot();

?>
