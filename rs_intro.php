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

/**
 * Limesurvey functions
 */
include ("functions/functions.limesurvey.php");

$js = array("js/popup.js","include/jquery/jquery-1.4.2.min.js","include/jquery-ui/jquery-ui.min.js");

if (AUTO_LOGOUT_MINUTES !== false) $js[] = "js/childnap.js";

$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);
$questionnaire_id = get_questionnaire_id($operator_id);

if($questionnaire_id && $operator_id){ 

	xhtml_head(T_("Respondent Selection") . " - " . T_("Introduction"),false,array("include/bootstrap/css/bootstrap.min.css","css/rs.css"), $js);// "include/bootstrap/css/bootstrap-theme.min.css",

	//display outcomes
	$outcomes = $db->GetOne("SELECT q.outcomes FROM `questionnaire` as q WHERE q.questionnaire_id = $questionnaire_id");//
	$outcomes = explode(",",$outcomes);

	$des = $db->GetAll("SELECT description FROM outcome WHERE outcome_id IN (1,2,3,6,8,16,17,18,30,31)");
	translate_array($des,array("description"));

	print "<div class='col-lg-4 text-danger'>
					<h3>" .  T_("End call with outcome:") . "</h3>";
		print "<div class='panel panel-danger'><div class='panel-heading'><t class='panel-title'>" . T_("Not Contacted") . "</t></div>
				<div class='panel-body'>";
				if ( ALTERNATE_INTERFACE ) print "<p><a class='btn btn-default' href=\"javascript:parent.location.href = 'index_interface2.php?outcome=1&amp;endcase=endcase'\">" . $des[0]['description'] . "</a></p>";
					print "<p><a class='btn btn-default'";
						if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=2&amp;endcase=endcase'\">";
						else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=2');\">";
					print $des[1]['description'] . "</a></p>";
				if ( ALTERNATE_INTERFACE ) print "<p><a class='btn btn-default' href=\"javascript:parent.location.href = 'index_interface2.php?outcome=3&amp;endcase=endcase'\">" . $des[2]['description'] . "</a></p>";
		print "</div></div>";

		print "<div class='panel panel-info'><div class='panel-heading'><t class='panel-title'>" . T_("Contacted") . "</t></div>
				<div class='panel-body' style='padding:10px;'>";
			if (in_array(8,$outcomes)){
					print "<p><a class='btn btn-default' ";
						if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=8&amp;endcase=endcase'\">";
						else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=8');\">";
					print $des[4]['description'] . "</a></p>"; }
			if (in_array(6,$outcomes)){
					print "<p><a class='btn btn-default' ";
						if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=6&amp;endcase=endcase'\">";
						else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=6');\">";
					print $des[3]['description'] . "</a></p>"; }
			if (in_array(17,$outcomes)){
					print "<p><a class='btn btn-default' title = \"" . T_("No eligible respondent (person never available on this number)") . "\"";
						if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=17&amp;endcase=endcase'\">";
						else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=17');\">";
					print $des[6]['description'] . " * </a></p>"; }
			if (in_array(18,$outcomes)){		
					print "<p><a class='btn btn-default' ";
						if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=18&amp;endcase=endcase'\">";
						else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=18');\">";
					print $des[7]['description'] . "</a></p>"; }
			if (in_array(31,$outcomes)){
					print "<p><a class='btn btn-default' title = \"" . T_("Non contact (person not currently available on this number: no appointment made)") . "\"";
						if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=31&amp;endcase=endcase'\">";
						else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=31');\">";
					print $des[9]['description'] . " * </a></p>"; }	
			if (in_array(30,$outcomes)){
					print "<p><a class='btn btn-default' ";
						if ( ALTERNATE_INTERFACE ) print "href=\"javascript:parent.location.href = 'index_interface2.php?outcome=30&amp;endcase=endcase'\">";
						else print "href=\"javascript:parent.poptastic('call.php?defaultoutcome=30');\">";
					print $des[8]['description'] . "</a></p>"; }
			if (in_array(16,$outcomes))
					print "<p><a class='btn btn-default' href=\"rs_business.php\">" . $des[5]['description'] . "</a></p>";
			if (in_array(23,$outcomes) || in_array(24,$outcomes) || in_array(29,$outcomes))
					print "<p><a class='btn btn-default' href=\"rs_answeringmachine.php\">" . T_("Answering machine") . "</a></p>";	
		print "</div></div>";
	print "</div>";

	print "<div class=\"col-sm-7 \"><h3 class='text-primary'>" . T_("Respondent Selection") . " - " . T_("Introduction") . "</h3>";
		//display introduction text
		$sql = "SELECT rs_intro,rs_project_intro,rs_callback
			FROM questionnaire
			WHERE questionnaire_id = '$questionnaire_id'";

		$r = $db->GetRow($sql);
		if (!empty($r['rs_intro']))	print "<div class='rstext well rs'>" . template_replace($r['rs_intro'],$operator_id,$case_id) . "</div>";
	print "</div>";

	// display continue
	print "<div class=\"col-sm-7 text-right\" style=\"margin-top: 50px;\">";
		print "<p><a class=\"btn btn-lg btn-primary\" href=\"";
		if (limesurvey_percent_complete($case_id) == false){
			if(empty($r['rs_project_intro'])) {	//If nothing is specified as a project introduction, skip straight to questionnaire
				print(get_limesurvey_url($operator_id)); }
			else print "rs_project_intro.php";
		} 
		else {
			if(empty($r['rs_callback'])) { //If nothing is specified as a callback screen, skip straight to questionnaire
				print(get_limesurvey_url($operator_id)); }
			else print "rs_callback.php";
		}
		print "\">" . T_("Yes - Continue") . "</a></p>";
	print "</div>";

}

xhtml_foot();


?>
