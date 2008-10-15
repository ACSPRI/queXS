<?
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

	//see if the case is completed
	if (limesurvey_is_completed($case_id))
	{
		$sql = "SELECT outcome_id,description
			FROM outcome
			WHERE outcome_id = 10";
	}
	else
	{
		//see if we have made an appointment on this call attempt
	
		$sql = "SELECT appointment_id
			FROM appointment
			WHERE completed_call_id IS NULL
			AND call_attempt_id = '$ca'";
	
		$rs = $db->GetAll($sql);
	
		if (!empty($rs))
		{
			//we have an appointment made ... only select appointment ID's
			$sql = "SELECT outcome_id,description
				FROM outcome
				WHERE outcome_type_id = '5'";		
		}
		else
		{
			if ($contacted === false)
			{
				$sql = "SELECT outcome_id,description
					FROM outcome";
			}
			else
			{
				$contacted = bigintval($contacted);
		
				$sql = "SELECT outcome_id,description
					FROM outcome
					WHERE contacted = '$contacted'";
			}
		}
	}
	$rs = $db->GetAll($sql);

	print "<div>";
	if (!empty($rs))
	{
		$do = false;
		if (isset($_GET['defaultoutcome'])) $do = bigintval($_GET['defaultoutcome']);
		foreach($rs as $r)
		{
			if ($do == $r['outcome_id']) $selected = "checked='checked'"; else $selected = "";
			print "<div><label class='label'><input type='radio' class='radio' name='outcome' id='outcome-{$r['outcome_id']}' value='{$r['outcome_id']}' $selected/>" . T_($r['description']) . "</label></div>";
		}
	}
	print "</div>";


}


//display the respondents phone numbers as a drop down list for this call

global $db;


$operator_id = get_operator_id();

if (isset($_POST['submit']))
{
	if (isset($_POST['contact_phone']))
	{
		$contact_phone_id = bigintval($_POST['contact_phone']);
		$call_attempt_id = get_call_attempt($operator_id);
		$respondent_id = get_respondent_id($call_attempt_id);
		$call_id = get_call($operator_id,$respondent_id,$contact_phone_id);
		if (VOIP_ENABLED && $call_id)
		{
			include("functions/functions.voip.php");
			$v = new voip();
			$v->connect(VOIP_SERVER);
			$v->dial(get_extension($operator_id),get_call_number($call_id));
			if (is_respondent_selection($operator_id))
				$btext = "onload='openParentObject(\"main-content\",\"rs_intro.php\"); top.close();'";
			else
				$btext = "onload='top.close();'";
			xhtml_head(T_("Call"),true,array("css/call.css"),array("js/window.js"),$btext);
		}
	}
	else if (isset($_POST['outcome']))
	{
		$outcome_id = bigintval($_POST['outcome']);
		end_call($operator_id,$outcome_id);
		if (VOIP_ENABLED)
		{
			include("functions/functions.voip.php");
			$v = new voip();
			$v->connect(VOIP_SERVER);
			$v->hangup(get_extension($operator_id));
			//disable recording
			$newtext = T_("Start REC");
			xhtml_head(T_("Call"),true,array("css/call.css"),array("js/window.js"),"onload='toggleRec(\"$newtext\",\"record.php?start=start\",\"offline\"); top.close();'");
		}
	}
	else
	{
		//if no outcome selected, just hang up the call
		if (VOIP_ENABLED)
		{
			include("functions/functions.voip.php");
			$v = new voip();
			$v->connect(VOIP_SERVER);
			$v->hangup(get_extension($operator_id));
			//disable recording
			$newtext = T_("Start REC");
			xhtml_head(T_("Call"),true,array("css/call.css"),array("js/window.js"),"onload='toggleRec(\"$newtext\",\"record.php?start=start\",\"offline\"); top.close();'");
		}
	}

	print "<p></p>"; //for XHTML
	xhtml_foot();
	exit();
}

$call_attempt_id = get_call_attempt($operator_id);
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


xhtml_head(T_("Call"),true,array("css/call.css"),array("js/window.js"));

$state = is_on_call($operator_id);
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
				print "<p><a href='javascript:openParent(\"endcase=endcase\")'>" . T_("End case") . "</a></p>";
				print "<p><a href='javascript:openParent(\"endwork=endwork\")'>" . T_("End work") . "</a></p>";
			}
			else
			{
				//determine whether to begin calling based on extension status
				$es = 1;
				if (VOIP_ENABLED)
				{
					include("functions/functions.voip.php");
					$v = new voip();
					$v->connect(VOIP_SERVER);
					$ext = get_extension($operator_id);
					if ($v->getExtensionStatus($ext))
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
		
					print "<form action='?' method='post'><div>";
					print "<p>" . T_("Number to call:") . " {$r['phone']} - {$r['description']}</p>";
					print "</div><div><input type='hidden' id='contact_phone' name='contact_phone' value='{$r['contact_phone_id']}'/><input type='submit' value='Call' name='submit' id='submit'/></div></form>";
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
	
			if (!isset($_GET['end']) && (empty($rs) || $rs['tryanother'] == 1)) //dial another number only when available and not ending
			{
				$rn = 0;
				if (!empty($rs) && $rs['require_note'] == 1) $rn = 1;
	
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
					if (VOIP_ENABLED)
					{
						include("functions/functions.voip.php");
						$v = new voip();
						$v->connect(VOIP_SERVER);
						$ext = get_extension($operator_id);
						if ($v->getExtensionStatus($ext))
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
						print "</select></div><div><input type='submit' value='Call' name='submit' id='submit'/></div></form>";
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
						print "<div><label for='note'>" . T_("Enter a reason for this outcome before completing this case:") . "</label><input type='text' id='note' name='note' size='48'/><br/><br/><br/><br/></div>";
						//put these lower on the screen so they don't get "automatically" clicked
						print "<p><a href='javascript:openParentNote(\"endcase=endcase\")'>" . T_("End case") . "</a></p>";
						print "<p><a href='javascript:openParentNote(\"endwork=endwork\")'>" . T_("End work") . "</a></p>";
					}
					else
					{
						print "<p><a href='javascript:openParent(\"endcase=endcase\")'>" . T_("End case") . "</a></p>";
						print "<p><a href='javascript:openParent(\"endwork=endwork\")'>" . T_("End work") . "</a></p";
					}
				}
			}
			else //don't try any more
			{
				$rn = 0;
				if (!empty($rs) && $rs['require_note'] == 1) $rn = 1;

				//end the case

				if ($rn) // a note is required to be entered
				{
					print "<div><label for='note'>" . T_("Enter a reason for this outcome before completing this case:") . "</label><input type='text' id='note' name='note' size='48'/><br/><br/><br/><br/></div>";
					print "<p><a href='javascript:openParentNote(\"endcase=endcase\")'>" . T_("End case") . "</a></p>";
					print "<p><a href='javascript:openParentNote(\"endwork=endwork\")'>" . T_("End work") . "</a></p>";
				}
				else
				{
					if (!isset($_GET['end'])) print "<div>" . T_("The last call completed this call attempt") . "</div>";
					print "<p><a href='javascript:openParent(\"endcase=endcase\")'>" . T_("End case") . "</a></p>";
					print "<p><a href='javascript:openParent(\"endwork=endwork\")'>" . T_("End work") . "</a></p>";
				}
			}
		}
		break;
	case 1: //requesting call
		print "<div class='status'>" . T_("Requesting call") . "</div>";
		print "<div><a href='?newstate=3'>" . T_("Call Answered") . "</a></div>";
		print "<form action='?' method='post'>";
		display_outcomes(0,$call_attempt_id,$case_id);
		print "<div><input type='submit' value='" . T_("Hangup") . "' name='submit' id='submit'/></div></form>";
		break;
	case 2: //ringing
		print "<div class='status'>" . T_("Ringing") . "</div>";
		print "<div><a href='?newstate=3'>" . T_("Call Answered") . "</a></div>";
		print "<form action='?' method='post'>";
		display_outcomes(0,$call_attempt_id,$case_id);
		print "<div><input type='submit' value='" . T_("Hangup") . "' name='submit' id='submit'/></div></form>";
		break;
	case 3: //answered
		print "<div class='status'>" . T_("Answered") . "</div>";
		print "<div><a href='?newstate=2'>" . T_("Not Answered") . "</a></div>";
		print "<form action='?' method='post'>";
		display_outcomes(1,$call_attempt_id,$case_id);
		print "<div><input type='submit' value='" . T_("Hangup") . "' name='submit' id='submit'/></div></form>";
		break;
	case 4: //requires coding
		print "<div class='status'>" . T_("Requires coding") . "</div>";
		print "<form action='?' method='post'>";
		display_outcomes(false,$call_attempt_id,$case_id);
		print "<div><input type='submit' value='" . T_("Assign outcome") . "' name='submit' id='submit'/></div></form>";
		break;
	case 5: //done -- shouldn't come here as should be coded + done
	default:
		print "<div class='status'>" . T_("Error: Close window") . "</div>";
		break;

}


xhtml_foot();


?>
