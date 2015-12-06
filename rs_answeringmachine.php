<?php 
/**
 * Respondent selection - Answering machine
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
 * XHTML
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include ("functions/functions.operator.php");

$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);
$questionnaire_id = get_questionnaire_id($operator_id);
$leavemessage = leave_message($case_id);

$js = array("js/popup.js","include/jquery/jquery-1.4.2.min.js","include/jquery-ui/jquery-ui.min.js");

if (AUTO_LOGOUT_MINUTES !== false) $js[] = "js/childnap.js";



xhtml_head(T_("Respondent Selection") . " - " . T_("Answering machine"),true,array("include/bootstrap/css/bootstrap.min.css","css/rs.css"),$js);//,"include/jquery-ui/jquery-ui.min.css"

print "<div class='col-lg-12'>";
if ($leavemessage)
{
	//display answering machine text
	$sql = "SELECT rs_answeringmachine
		FROM questionnaire
		WHERE questionnaire_id = '$questionnaire_id'";
	
	$r = $db->GetRow($sql);
	
	if (!empty($r['rs_answeringmachine'])) print "<p class='rstext well'>" . template_replace($r['rs_answeringmachine'],$operator_id,$case_id) . "</p>";
}
else
	print "<p class='rstext alert alert-warning'>" . T_("Do not leave a message, please hang up") . "</p>";

print "<div class=' '>
		<div class='col-lg-2'><p class=''><a class='btn btn-default'";
		
		//to remove after rs_intro and rs_intro_interface2 merging //
			if ( ALTERNATE_INTERFACE ) print "href=\"rs_intro_interface2.php\""; else print "href=\"rs_intro.php\"";
		print ">" . T_("Go Back") . "</a></p></div>";
		
		if ($questionnaire_id){
			$outcomes = $db->GetOne("SELECT q.outcomes FROM `questionnaire` as q WHERE q.questionnaire_id = $questionnaire_id");//
			$outcomes = explode(",",$outcomes);
			
			$des = $db->GetAll("SELECT description FROM outcome WHERE outcome_id IN (23,24,29)");
			translate_array($des,array("description"));
			
			print "<div class='col-lg-4'><p class=''><h4 class='text-right'>" . T_("End call with outcome:") . "</h4></p></div>
					<div class='col-lg-6'>";
				if (in_array(29,$outcomes)){ //preg_match('/29/',$outcomes)
					print "<p class=''><a class='btn btn-primary' ";
						if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=29&amp;endcase=endcase'\">";
						else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=29');\">";
					print $des[2]['description'] . "</a></p>";
				}

				if (in_array(23,$outcomes) && $leavemessage){ //preg_match('/23/',$outcomes
					print "<p class=''><a class='btn btn-primary' ";
						if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=23&amp;endcase=endcase'\">";
						else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=23');\">";
					print $des[0]['description'] . "</a></p>";
				}

				if (in_array(24,$outcomes)){ //preg_match('/24/',$outcomes
					print "<p class=''><a class='btn btn-primary' ";
						if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=24&amp;endcase=endcase'\">";
						else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=24');\">";
					print $des[1]['description'] . "</a></p>";
				}
			print "</div>";
		}
 
print "</div>";


xhtml_foot();

?>
