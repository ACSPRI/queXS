<?php 
/**
 * Display status of case
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
include("functions/functions.operator.php");



$operator_id = get_operator_id();
$state = is_on_call($operator_id);

$js = array("js/popup.js");

if (browser_ie())
	$js[] = "js/window_ie6.js";
else
	$js[] = "js/window.js";

if (AUTO_LOGOUT_MINUTES !== false)
{
	$js[] = "include/jquery-ui/js/jquery-1.4.2.min.js";
	$js[] = "js/childnap.js";
}

xhtml_head(T_("Status"),false,array("include/bootstrap-3.3.2/css/bootstrap.min.css","css/status_interface2.css"),$js);

$ca = get_call_attempt($operator_id,false);
if ($ca)
{
	$respondent_id  = get_respondent_id($ca);
	$case_id = get_case_id($operator_id);
	$fname = get_respondent_variable("firstName",$respondent_id);
	$lname = get_respondent_variable("lastName",$respondent_id);
	print "<h4>" . T_("Name") . ": $fname $lname</h4>";

	$appointment = is_on_appointment($ca);

	$call_id = get_call($operator_id);

	$sql = "SELECT o.tryanother, o.require_note
		FROM `call` as c, `outcome` as o
		WHERE c.call_attempt_id = '$ca'
		AND c.outcome_id = o.outcome_id
		ORDER BY call_id DESC
		LIMIT 1";
	
	$rst = $db->GetRow($sql);
	
	if ((empty($rst) || $rst['tryanother'] == 1)) //dial another number only when available and not ending
	{

		if (isset($_POST['contactphone']))
		{
			$pcontact_phone_id = intval($_POST['contactphone']);

			//If an outcome already assigned, end the current call and start the enxt one to pcontact_phone_id
			//Otherwise bring up the assign outcome window
			if (!$call_id) //outcome assigned
			{
				$call_id = get_call($operator_id,$respondent_id,$pcontact_phone_id,true);
				echo "<script type='text/javascript'>openParentObject(\"main-content\",\"" . get_respondentselection_url($operator_id,false) . "\");</script>";
			}
			else
			{
				//bring up assign outcome window
				print "<script type='text/javascript'>parent.poptastic('call_interface2.php');</script>";
			}
		}
		
		if (!$call_id)
		{
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
                                 WHERE call_attempt_id = '$ca'
                                 AND outcome_id != 18
                                ) as ca on ca.contact_phone_id = c.contact_phone_id
                                WHERE c.case_id = '$case_id'
                                AND l.contact_phone_id IS NULL
                                AND ca.contact_phone_id IS NULL
				order by c.priority ASC";
			
			$numsa = $db->GetRow($sql);

			if (!empty($numsa))
			{
				if ($appointment)
				{
					//create a call on the appointment number
					$sql = "SELECT cp.*
						FROM contact_phone as cp, appointment as a
						WHERE cp.case_id = '$case_id'
						AND a.appointment_id = '$appointment'
						AND a.contact_phone_id = cp.contact_phone_id";
		
					$rs = $db->GetRow($sql);
					$contact_phone_id = $rs['contact_phone_id'];				
				}
				else
				{
					$contact_phone_id = $numsa['contact_phone_id'];
				}
			
				$call_id = get_call($operator_id,$respondent_id,$contact_phone_id,true);
			}
		}
		
		if ($appointment)
		{	
			$sql = "SELECT DATE_FORMAT(CONVERT_TZ(a.start,'System', o.Time_zone_name),'%Y-%b-%d %H:%i') as time
				FROM appointment as a, operator as o
				WHERE o.operator_id = '$operator_id'
				AND a.appointment_id = '$appointment'";

			$rs = $db->GetRow($sql);

			$apdate = $rs['time'];


			print "<div class='tobecoded statusbox'>" . T_("Appointment") . ": " . $apdate .  "</div><div style='clear: both;'/>";
			//if (missed_appointment($ca)) print "<div class='tobecoded statusbutton'>" . T_("MISSED") . "</div>";
		}

		if ($call_id)
		{
			$sql = "SELECT c.*, CASE WHEN c.contact_phone_id = ccc.contact_phone_id THEN 'checked=\"checked\"' ELSE '' END as checked
				FROM contact_phone as c
				LEFT JOIN `call` as ccc ON (ccc.call_id = '$call_id')
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
					WHERE call_attempt_id = '$ca'
					AND outcome_id != 18
					AND outcome_id != 0
					) as ca on ca.contact_phone_id = c.contact_phone_id
				WHERE c.case_id = '$case_id'
				AND l.contact_phone_id IS NULL
				AND ca.contact_phone_id IS NULL";
	
	
			$rs = $db->GetAll($sql);
	

			//Display all available numbers for this case as a list of radio buttons
			//By default, the selected radio button should have a "call" started for it
		 	//When then next one clicked, it should bring up call screen if no outcome otherwise start new call
			print "<div>";
			foreach($rs as $r)
			{
				print "<form method='post' action='?'><div class='text'>";
				print "<input onclick='this.form.submit();' type='radio' name='contactphone' value='{$r['contact_phone_id']}' id='contactphone{$r['contact_phone_id']}' {$r['checked']}/>";
				print "<label for='contactphone{$r['contact_phone_id']}'>{$r['phone']}";
				if ($r['checked']) print "&emsp;<a href='callto:{$r['phone']}'>" . T_('Dial') . "</a>";
				if (!empty($r['description'])) print " - " . $r['description'];
				print "</label>";
				print "</div></form></br>";
			}
			print "</div>";
		}
		else
			print "<div class='text'>" . T_("No more numbers to call") . "</div>";
	}
	else
		print "<div class='text'>" . T_("No more numbers to call") . "</div>";
}
else
	print "<div class='text'>" . T_("No case") . "</div>";

xhtml_foot();

?>
