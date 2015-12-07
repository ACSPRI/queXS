<?php 
/**
 * Popup screen to manage calling and hanging up and assigning outcomes to calls
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
 * Authentication
 */
require ("auth-interviewer.php");

/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Calendar functions
 */
include("functions/functions.calendar.php");

/**
 * Operator functions
 */
include("functions/functions.operator.php");

/**
 * Input functions
 */
include("functions/functions.input.php");

/**
 * LimeSurvey functions
 */
include("functions/functions.limesurvey.php");

/**
 * Display appropriate outcomes based on current call attempt status
 *
 * @param int $contacted 0 for not contacted, 1 for contacted (a person on the phone)
 * @param int $ca Call attempt id
 * @param int $case_id The Case id
 *
 */
function display_outcomes($contacted,$ca,$case_id)
{
	global $db;

	$completed = limesurvey_is_completed($case_id);

	//see if the case is completed
	if ($completed)
	{
		$sql = "SELECT outcome_id,description,contacted
			FROM outcome
			WHERE outcome_id = 10";
	}
	else if (limesurvey_is_quota_full($case_id))
	{
		$sql = "SELECT outcome_id,description,contacted
			FROM outcome
			WHERE outcome_id = 32";
	}
	else
	{
		//see if we have made an appointment on this call attempt
	
		$sql = "SELECT appointment_id
			FROM appointment
			WHERE completed_call_id IS NULL
			AND call_attempt_id = '$ca'";
	
		$rs = $db->GetAll($sql);
		
		$outcomes = $db->GetOne("SELECT q.outcomes FROM `questionnaire` as q JOIN `case` as c ON (c.questionnaire_id =q.questionnaire_id) WHERE c.case_id = $case_id");
		
		if (!empty($rs))
		{
			//we have an appointment made ... only select appointment ID's
			$sql = "SELECT outcome_id,description,contacted
				FROM outcome
				WHERE outcome_type_id = '5'
				AND outcome_id IN ($outcomes)";	
		}
		else
		{
			if ($contacted) $contacted = bigintval($contacted);
			else{
				print "<div class=\"form-group\" ><a href=\"?contacted=1\" class=\"btn btn-info\" style=\"margin-left: 15px; margin-right: 30px; min-width: 150px;\">".T_("CONTACTED")."</a>";
				print "<a href=\"?contacted=0\" class=\"btn btn-default\" style=\"margin-left: 30px; margin-right: 15px; min-width: 150px;\">".T_("NOT CONTACTED")."</a></div>";
				
				if (isset ($_GET['contacted'])) $contacted = bigintval($_GET['contacted']);
			}	
			if ($contacted == 1 || $contacted === 0){
		
				$sql = "SELECT outcome_id,description,contacted
					FROM outcome
					WHERE outcome_type_id != '5'
					AND contacted = '$contacted'
					AND outcome_id IN ($outcomes)
					AND outcome_id NOT IN(10,32,42,43,44,45)"; //don't show completed if not, Lime_Quota_full if not, hide max calls as they supposed to be automatic or admin-privileged
			} 
		}
	}
	$rs = $db->GetAll($sql);

	print "<div class=\"panel-body\">";
	if (!empty($rs))
	{
		$do = false;
		
		$lime_sid = get_limesurvey_id(get_operator_id());

		//Check to see if we have sent an email on this call and set the default outcome
		$sql = "SELECT 41
			FROM `case` as c, " . LIME_PREFIX . "tokens_$lime_sid as t
			WHERE t.sent = '$ca'
			AND c.case_id = $case_id
			AND t.token = c.token";
		$do = $db->GetOne($sql);
		
		if (isset($_GET['defaultoutcome'])) $do = bigintval($_GET['defaultoutcome']);
		foreach($rs as $r)
		{
			if ($do == $r['outcome_id']) $selected = "checked='checked'"; else $selected = "";
			if (isset($r['contacted']) && $r['contacted'] == 1) $highlight = ""; else $highlight = "style='color:black;'";
			print "<div><a><label $highlight class='btn-link'><input type='radio' class='radio' name='outcome' id='outcome-{$r['outcome_id']}' value='{$r['outcome_id']}' $selected style='float:left'/>&emsp;" . T_($r['description']) . "</label></a></div>";
		}
		
		$_POST['confirm'] = true; //check that outcome selected
	}
	print "</div>";
}

if (browser_ie()) $js = "js/window_ie6_interface2.js"; else $js = "js/window_interface2.js";

//display the respondents phone numbers as a drop down list for this call

global $db;

$db->StartTrans();

$operator_id = get_operator_id();

if (isset($_POST['submit']))
{
	if (is_voip_enabled($operator_id))
	{ //prepare common voip  functions
		include("functions/functions.voip.php");
		$v = new voip();
		$v->connect(VOIP_SERVER);
	}
	
	$btext = "onload='parent.closePopup();'"; // set default, change on conditions
	
	if (isset($_POST['contact_phone']))
	{
		$contact_phone_id = bigintval($_POST['contact_phone']);
		$call_attempt_id = get_call_attempt($operator_id,false);
		$respondent_id = get_respondent_id($call_attempt_id);
		$call_id = get_call($operator_id,$respondent_id,$contact_phone_id,true);
		if ($call_id)
		{
			if (is_voip_enabled($operator_id))
				$v->dial(get_extension($operator_id),get_call_number($call_id));
		}
		else exit();
	}
	else 
	{
		//hang up the call first
		if (is_voip_enabled($operator_id)) 
			$v->hangup(get_extension($operator_id));
		
		//disable recording --->  Not actual for call_interface2 ??
		$newtext = T_("Start REC");
		
		if (isset($_POST['outcome'])){ //process with outcome
			$outcome_id = bigintval($_POST['outcome']);
			end_call($operator_id,$outcome_id);
			
			//If outcome is final, close the case 
			$sql = "SELECT o.tryanother
				FROM `outcome` as o
				WHERE o.outcome_id = '$outcome_id'";

			$rs = $db->GetRow($sql);

			if (!empty($rs) && $rs['tryanother'] == 0)
			{
				$btext = "onload='openParent(\"endcase=endcase\");'";
			}
			else
			{	
				$call_attempt_id = get_call_attempt($operator_id,false);
				$case_id = get_case_id($operator_id);
				//see if we have exhausted the available numbers
				$sql = "SELECT c. *
					FROM contact_phone AS c
					LEFT JOIN (
							SELECT contact_phone.contact_phone_id
							FROM contact_phone
							LEFT JOIN `call` ON ( call.contact_phone_id = contact_phone.contact_phone_id )
							LEFT JOIN outcome ON ( call.outcome_id = outcome.outcome_id )
							WHERE contact_phone.case_id = '$case_id'
							AND outcome.tryagain =0
						) AS l ON l.contact_phone_id = c.contact_phone_id
					LEFT JOIN (
							SELECT contact_phone_id
							FROM `call`
							WHERE call_attempt_id = '$call_attempt_id'
							AND outcome_id != 18
						) as ca on ca.contact_phone_id = c.contact_phone_id
					WHERE c.case_id = '$case_id'
					AND l.contact_phone_id IS NULL
					AND ca.contact_phone_id IS NULL"; //only select numbers that should be tried again and have not been tried in this attempt which are not the accidental hang up outcome

				$rs = $db->GetAll($sql);

				if (empty($rs)) //no more numbers to call, end the case
				{
					$btext = "onload='openParent(\"endcase=endcase\");'";
				}
			}
		}
	}
	
	xhtml_head(T_("Call"),true,array("css/call.css"),array($js),$btext);

	print "<p></p>"; //for XHTML
	xhtml_foot();
	$db->CompleteTrans();
	exit();
}

$call_attempt_id = get_call_attempt($operator_id,false);
$case_id = get_case_id($operator_id);

/**
 * Set the state manually if necessary (i.e if VOIP state is playing up)
 */
if (isset($_GET['newstate']))
{
	$ns = bigintval($_GET['newstate']);
	$sql = "UPDATE  `call`
		SET state = '$ns'
		WHERE case_id = '$case_id'
		AND operator_id = '$operator_id'
		AND call_attempt_id = '$call_attempt_id'
		AND outcome_id = '0'";
	$db->Execute($sql);
}


xhtml_head(T_("Set outcome"),true,array("include/bootstrap/css/bootstrap.min.css"),array($js,"include/jquery/jquery-1.4.2.min.js"));

$state = is_on_call($operator_id);

function print_endcase($trigger = ""){
	print "<p><a href='javascript:" . $trigger . "(\"endcase=endcase\")' class='btn btn-primary'>" . T_("End case") . "</a></p>";
}

function print_endwork($trigger = ""){
	print "<p><a href='javascript:" . $trigger . "(\"endwork=endwork\")' class='btn btn-info'>" . T_("End work") . "</a></p>";
}

switch($state)
{
	case false: //no call made
	case 0: //not called -- shouldn't come here as we should create requesting call immediately
		print "<div class='status'>" . T_("Not on a call") . "</div>";


		//if we are on an appointment, we will just call the specified number for the appointment
		$appointment_id = is_on_appointment($call_attempt_id);

		if ($appointment_id)
		{
			if (isset($_GET['end']))
			{
				//end the case
				if (!isset($_GET['end'])) print "<div>" . T_("End work") . "</div>";
				print_endcase("openParent");
				print_endwork("openParent");
			}
			else
			{
				//determine whether to begin calling based on extension status
				$es = 1;
				if (is_voip_enabled($operator_id))
				{
					if (get_extension_status($operator_id))
						$es = 1;	
					else
						$es = 0;
				}

				if ($es)
				{

					$sql = "SELECT c.*
						FROM contact_phone as c, appointment as a
						WHERE a.appointment_id = '$appointment_id'
						AND a.contact_phone_id = c.contact_phone_id";
		
					$r = $db->GetRow($sql);
					
					print "<div>" . T_("Press the call button to dial the number for this appointment:") . "</div>";
		
					print "<form action='?' method='post'>
							<div><p>" . T_("Number to call:") . " {$r['phone']} - {$r['description']}</p></div>
							<input type='hidden' id='contact_phone' name='contact_phone' value='{$r['contact_phone_id']}'/>
							<div><input type='submit' value=\"" . T_("Call") . "\" name='submit' id='submit' class='btn btn-primary'/></div>
						   </form>";
				}
				else
					print "<div>" . T_("Your VoIP extension is not enabled. Please close this window and enable VoIP by clicking once on the red button that says 'VoIP Off'") . "</div>";
			}
		}
		else
		{
			//determine whether we should make any more calls based on the last call outcome
	
			$sql = "SELECT o.tryanother, o.require_note
				FROM `call` as c, `outcome` as o
				WHERE c.call_attempt_id = '$call_attempt_id'
				AND c.outcome_id = o.outcome_id
				ORDER BY call_id DESC
				LIMIT 1";
	
			$rs = $db->GetRow($sql);
			
			if (!empty($rs) && $rs['require_note'] == 1) $rn = 1; else $rn = 0;
	
			if (!isset($_GET['end']) && (empty($rs) || $rs['tryanother'] == 1)) //dial another number only when available and not ending
			{
				//an exclusion left join
				$sql = "SELECT c. *
					FROM contact_phone AS c
					LEFT JOIN (
						SELECT contact_phone.contact_phone_id
						FROM contact_phone
						LEFT JOIN `call` ON ( call.contact_phone_id = contact_phone.contact_phone_id )
						LEFT JOIN outcome ON ( call.outcome_id = outcome.outcome_id )
						WHERE contact_phone.case_id = '$case_id'
						AND outcome.tryagain =0
						) AS l ON l.contact_phone_id = c.contact_phone_id
					LEFT JOIN
						(
						SELECT contact_phone_id
						FROM `call`
						WHERE call_attempt_id = '$call_attempt_id'
						AND outcome_id != 18
						) as ca on ca.contact_phone_id = c.contact_phone_id
					WHERE c.case_id = '$case_id'
					AND l.contact_phone_id IS NULL
					AND ca.contact_phone_id IS NULL"; //only select numbers that should be tried again and have not been tried in this attempt which are not the accidental hang up outcome
	
					//could be updated to take in to account the time delay and outcome
	
				$rs = $db->GetAll($sql);
	
				if (!empty($rs))
				{
					//determine whether to begin calling based on extension status
					$es = 1;
					if (is_voip_enabled($operator_id))
					{
						if (get_extension_status($operator_id))
							$es = 1;	
						else
							$es = 0;
					}

					if ($es)
					{
						print "<div>" . T_("Select phone number to dial:") . "</div>";
			
						print "<form action='?' method='post'><div><select id='contact_phone' name='contact_phone'>";
						foreach($rs as $r)
						{
							print "<option value='{$r['contact_phone_id']}'>{$r['phone']} - {$r['description']}</option>";
						}
						print "</select></div><div><input type='submit' value=\"" . T_("Call") . "\" name='submit' id='submit' class='btn btn-primary'/></div></form>";
					}
					else
						print "<div>" . T_("Your VoIP extension is not enabled. Please close this window and enable VoIP by clicking once on the red button that says 'VoIP Off'") . "</div>";
				}
				else //no phone numbers left
				{
					//end the case
					print "<div>" . T_("The last call completed this call attempt") . "</div>";

					if ($rn) // a note is required to be entered
					{
						print "<div><label for='note' class='control-label'>" . T_("Enter a reason for this outcome before completing this case:") . "</label><textarea type='text' id='note' name='note' class='form-control' rows='3'></textarea><br/></div>";
						//give focus on load
						print '<script type="text/javascript">$(document).ready(function(){$("#note").focus();});</script>';
						//put these lower on the screen so they don't get "automatically" clicked
						print_endcase("openParentNote");
						print_endwork("openParentNote");
					}
					else
					{
						print_endcase("openParent");
						print_endwork("openParent");
					}
				}
			}
			else //don't try any more
			{
				//end the case

				if ($rn) // a note is required to be entered
				{
					print "<div><label for='note' class='control-label'>" . T_("Enter a reason for this outcome before completing this case:") . "</label><textarea type='text' id='note' name='note' class='form-control' rows='3'></textarea><br/></div>";
					print '<script type="text/javascript">$(document).ready(function(){$("#note").focus();});</script>';
					print_endcase("openParentNote");
					print_endwork("openParentNote");
				}
				else
				{
					if (!isset($_GET['end'])) print "<div class='alert alert-info'>" . T_("The last call completed this call attempt") . "</div>";
					print_endcase("openParent");
					print_endwork("openParent");
				}
			}
		}
		break;
	case 1: //requesting call
	case 2: //ringing
	case 3: //answered
	case 4: //requires coding
	//	print "<div class='status'>" . T_("Requires coding") . "</div>";
		print "<form action='?' method='post'><div class=\" \">";
		display_outcomes(false,$call_attempt_id,$case_id);
		if (isset($_POST['confirm'])){
			print "</div><input type='submit' class=\"btn btn-primary btn-lg\" style=\"margin:0px 20px;\" value=\"" . T_("Assign outcome") . "\" name='submit' id='submit'/></form>";
		}
		break;
	case 5: //done -- shouldn't come here as should be coded + done
	default:
		print "<div class='alert alert-danger'>" . T_("Error: Close window") . "</div>";
		break;

}


xhtml_foot();
$db->CompleteTrans();

?>
