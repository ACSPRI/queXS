<?
/**
 * Operator functions for interacting with the database and getting/storing state
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
 * @subpackage functions
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 *
 *
 * @todo Add session data to reduce calls to the database
 *
 */

/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

/**
 * Database file
 */
include_once(dirname(__FILE__).'/../db.inc.php');


/**
 * Return the period of the day for the respondent
 *
 * @param int $respondent_id The respondent id
 * @return string Either morning, afternoon or evening based on the respondents time zone
 *
 */
function get_period_of_day($respondent_id)
{
	global $db;

	$sql = "SELECT HOUR(CONVERT_TZ(NOW(),'System',Time_zone_name)) as h
		FROM respondent
		WHERE respondent_id = '$respondent_id'";

	$rs = $db->GetRow($sql);

	$hour = 10;
	if (!empty($rs))
		$hour = $rs['h'];

	if ($hour < 12) return T_("morning");
	if ($hour < 17) return T_("afternoon");
	return T_("evening");
}


/**
 * Get information from the sample
 *
 * @param string $variable The bit of information from the sample
 * @param int $case_id The case id
 * @return string The information or a blank string if none found
 *
 */
function get_sample_variable($variable,$case_id)
{
	global $db;

	$sql = "SELECT s.val as r
		FROM sample_var as s
		JOIN `case` as c on (c.case_id = '$case_id' and s.sample_id = c.sample_id)
		WHERE s.var = '$variable'";

	$rs = $db->GetRow($sql);


	if (empty($rs)) return "";

	return $rs['r'];

}

/**
 * Get information about the operator
 *
 * @param string $variable The bit of information about the operator (eg firstName)
 * @param int $operator_id The operator id
 * @return string The information or a blank string if none found
 *
 */
function get_operator_variable($variable,$operator_id)
{
	global $db;

	$sql = "SELECT `$variable` as r
		FROM operator
		WHERE operator_id = '$operator_id'";

	$rs = $db->GetRow($sql);

	if (empty($rs)) return "";

	return $rs['r'];

}

/**
 * Get information about the respondent
 *
 * @param string $variable The bit of information about the respondent (eg firstName)
 * @param int $respondent_id The respondent id
 * @return string The information or a blank string if none found
 *
 */
function get_respondent_variable($variable,$respondent_id)
{
	global $db;

	$sql = "SELECT `$variable` as r
		FROM respondent
		WHERE respondent_id = '$respondent_id'";

	$rs = $db->GetRow($sql);


	if (empty($rs)) return "";

	return $rs['r'];

}


/**
 * Replace placeholders in a string with data for this case/operator
 * 
 * @param string $string The string 
 * @param int $operator_id The operator id
 * @param int $case_id The case id
 * @return string The string with replaced text
 *
 */
function template_replace($string,$operator_id,$case_id)
{
	$respondent_id = get_respondent_id(get_call_attempt($operator_id));

	while (stripos($string, "{Respondent:") !== false)
	{
		$answreplace=substr($string, stripos($string, "{Respondent:"), stripos($string, "}", stripos($string, "{Respondent:"))-stripos($string, "{Respondent:")+1);
		$answreplace2=substr($answreplace, 12, stripos($answreplace, "}", stripos($answreplace, "{Respondent:"))-12);
		$answreplace3=get_respondent_variable($answreplace2,$respondent_id);
		$string=str_replace($answreplace, $answreplace3, $string);
	}


	while (stripos($string, "{Operator:") !== false)
	{
		$answreplace=substr($string, stripos($string, "{Operator:"), stripos($string, "}", stripos($string, "{Operator:"))-stripos($string, "{Operator:")+1);
		$answreplace2=substr($answreplace, 10, stripos($answreplace, "}", stripos($answreplace, "{Operator:"))-10);
		$answreplace3=get_operator_variable($answreplace2,$operator_id);
		$string=str_replace($answreplace, $answreplace3, $string);
	}

	while (stripos($string, "{Sample:") !== false)
	{
		$answreplace=substr($string, stripos($string, "{Sample:"), stripos($string, "}", stripos($string, "{Sample:"))-stripos($string, "{Sample:")+1);
		$answreplace2=substr($answreplace, 8, stripos($answreplace, "}", stripos($answreplace, "{Sample:"))-8);
		$answreplace3=get_sample_variable($answreplace2,$case_id);
		$string=str_replace($answreplace, $answreplace3, $string);
	}

	while (stripos($string, "{PeriodOfDay") !== false)
	{
		$answreplace=substr($string, stripos($string, "{PeriodOfDay"), stripos($string, "}", stripos($string, "{PeriodOfDay"))-stripos($string, "{PeriodOfDay")+1);
		$answreplace3=get_period_of_day($respondent_id);
		$string=str_replace($answreplace, $answreplace3, $string);
	}


	return $string;
}


/**
 * Return true if the current questionnaire has respondent selection
 * enabled
 *
 * @param int $operator_id The operator id
 * @return bool True if respondent selection enabled otherwise false
 *
 */
function is_respondent_selection($operator_id)
{
	global $db;

	$questionnaire_id = get_questionnaire_id($operator_id);

	if (!$questionnaire_id) return false;

	$sql = "SELECT respondent_selection
		FROM questionnaire 
		WHERE questionnaire_id = '$questionnaire_id'";
	
	$rs = $db->GetRow($sql);

	if (!$rs) return false;
	if ($rs['respondent_selection'] == 1) return true;
	return false;
}

/**
 * Get the current or next case id
 *
 * @param int $operator_id The operator id
 * @param bool $create True if a case can be created
 * @return bool|int False if no case available else the case_id
 */
function get_case_id($operator_id, $create = true)
{

	global $db;
	global $ldb;

	$db->StartTrans();

	/**
	 * See if case already assigned
	 */
	$sql = "SELECT case_id
		FROM `case`
		WHERE current_operator_id = '$operator_id'";

	$r1 = $db->GetRow($sql);


	$case_id = false;

	if (empty($r1))
	{	
		if ($create)
		{
			/**
			 * find a case that:
			 *    Has not been called in the last x hours based on last outcome 
			 *    Is available for this operator
			 *    Has no appointments scheduled in the future (can also check if outcome is appointment)
			 *    Nobody else is servicing the call at the moment
			 *    The case is not referred to the supervisor and the operator is not the supervisor
			 *    The case is not on a refusal outcome and the operator is not a refusal converter
			 *    Give priority if there is an appointment scheduled now
			 *    If restricted to shift times to work, make sure we are in those
			 *    If restricted to respondent call times, make sure we are in those
			 *    Only assign if outcome type is assigned to the operator
			 *
			 *    
			 *   THINGS TO ADD:
			 *
			 *   @todo also option of "time of day" calls - try once in the morning/etc
			 *   @todo also could check the respondent_not_available table to see if now is a "bad time" to call
			 */	
		
			$sql = "SELECT c.case_id as caseid,s.*,apn.*,a.*,sh.*,op.*,cr.*,si.*,CONVERT_TZ(NOW(), 'System' , s.Time_zone_name) as resptime
				FROM `case`  as c
				LEFT JOIN `call` as a on (a.call_id = c.last_call_id)
				JOIN (sample as s, sample_import as si) on (s.sample_id = c.sample_id and si.sample_import_id = s.import_id)
				JOIN (questionnaire_sample as qs, operator_questionnaire as o, questionnaire as q, operator as op, outcome as ou) on (c.questionnaire_id = q.questionnaire_id and op.operator_id = '$operator_id' and qs.sample_import_id = s.import_id and o.operator_id = op.operator_id and o.questionnaire_id = qs.questionnaire_id and q.questionnaire_id = o.questionnaire_id and ou.outcome_id = c.current_outcome_id)
				LEFT JOIN shift as sh on (sh.questionnaire_id = q.questionnaire_id and (CONVERT_TZ(NOW(),'System','UTC') >= sh.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= sh.end))
				LEFT JOIN appointment as ap on (ap.case_id = c.case_id AND ap.completed_call_id is NULL AND (ap.start > CONVERT_TZ(NOW(),'System','UTC')))
				LEFT JOIN appointment as apn on (apn.case_id = c.case_id AND apn.completed_call_id is NULL AND (CONVERT_TZ(NOW(),'System','UTC') >= apn.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= apn.end))
				LEFT JOIN call_restrict as cr on (cr.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= cr.start and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= cr.end)
				JOIN operator_skill as os on (os.operator_id = op.operator_id and os.outcome_type_id = ou.outcome_type_id)
				WHERE c.current_operator_id IS NULL
				AND (a.call_id is NULL or (a.end < CONVERT_TZ(DATE_SUB(NOW(), INTERVAL ou.default_delay_minutes MINUTE),'System','UTC')))
				AND ap.case_id is NULL
				AND !(q.restrict_work_shifts = 1 AND sh.shift_id IS NULL AND os.outcome_type_id != 2)
				AND !(si.call_restrict = 1 AND cr.day_of_week IS NULL AND os.outcome_type_id != 2)
				AND ((apn.appointment_id IS NOT NULL) or qs.call_attempt_max = 0 or ((SELECT count(*) FROM call_attempt WHERE case_id = c.case_id) < qs.call_attempt_max))
				AND ((apn.appointment_id IS NOT NULL) or qs.call_max = 0 or ((SELECT count(*) FROM `call` WHERE case_id = c.case_id) < qs.call_max))
				ORDER BY apn.start DESC, a.start ASC
				LIMIT 1";
	
				//apn.appointment_id contains the id of an appointment if we are calling on an appointment
	
			$r2 = $db->GetRow($sql);
	
			if (empty($r2))
			{
	
	
				/**
				 * If no case found, we must draw the next available case from the sample
				 * only if no case due to lack of cases to call not out of shift time/etc and
				 * only draw cases that are new (Temporary outcome_type_id)
				 *
				 *
				 * Method:
				 *    next available that has not been assigned
				 *    if none available - return false? report to operator that no one available to call at currenet settings
				 *
				 */
				
			  
				$sql = "SELECT s.sample_id as sample_id,c.case_id as case_id,qs.questionnaire_id as questionnaire_id,CONVERT_TZ(NOW(), 'System' , s.Time_zone_name) as resptime, q.testing as testing
					FROM sample as s
					JOIN (questionnaire_sample as qs, operator_questionnaire as o, questionnaire as q, operator as op, sample_import as si, operator_skill as os) on (op.operator_id = '$operator_id' and qs.sample_import_id = s.import_id and o.operator_id = op.operator_id and o.questionnaire_id = qs.questionnaire_id and q.questionnaire_id = o.questionnaire_id and si.sample_import_id = s.import_id and os.operator_id = op.operator_id and os.outcome_type_id = 1)
					LEFT JOIN `case` as c on (c.sample_id = s.sample_id and c.questionnaire_id = qs.questionnaire_id)
					LEFT JOIN call_restrict as cr on (cr.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= cr.start and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= cr.end)
					LEFT JOIN shift as sh on (sh.questionnaire_id = q.questionnaire_id and (CONVERT_TZ(NOW(),'System','UTC') >= sh.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= sh.end))
					WHERE c.case_id is NULL
					AND !(q.restrict_work_shifts = 1 AND sh.shift_id IS NULL)
					AND !(si.call_restrict = 1 AND cr.day_of_week IS NULL)
					ORDER BY rand() * qs.random_select, s.sample_id
					LIMIT 1";
				
	
				$r3 = $db->GetRow($sql);
	
	
				/**
				 * If the above statement returns no rows, then there are no cases to be added to the sample at this time
				 * We could do a select of how many are actually available to reassure the operator that the sample has not been exhausted
				 *
				 *
				 */
				
				
				/**
				 * Now we have to add phone numbers to the contact_phone table, the case to the case table,
				 * assign this case to this operator
				 */
		
				if (!empty($r3))
				{
					$sql = "INSERT INTO `case` (case_id, sample_id, questionnaire_id, last_call_id, current_operator_id, current_call_id, current_outcome_id)
						VALUES (NULL, {$r3['sample_id']}, {$r3['questionnaire_id']} , NULL, $operator_id, NULL, 1)";
	
					$db->Execute($sql);
	
					$case_id = $db->Insert_ID();

					//if this sample is set as testing, assign internal numbers as numbers
					if ($r3['testing'] == 1)
					{
						$db->Execute("SET @row := 0");
		
						$sql = "INSERT INTO contact_phone (case_id,priority,phone,description)
							SELECT $case_id as case_id,@row := @row + 1 AS priority,extension as phone, CONCAT(firstName, ' ', lastName)
							FROM operator";
		
						$db->Execute($sql);
					}
					else
					{
						//add any phone numbers to contact phone
		
						//$db->Execute("SET @row := 0");
		
						$sql = "SELECT val as phone
							FROM sample_var
							WHERE sample_id = '{$r3['sample_id']}'
							AND val is NOT NULL
							AND val != \"\"
							AND (`type` = 2 or `type` = 3)
							ORDER BY `type` DESC";
		
						$r5 = $db->GetAll($sql);

						if (!empty($r5))
						{
							$i = 1;
							foreach ($r5 as $r5v)
							{
								$sql = "INSERT INTO contact_phone (case_id,priority,phone)
									VALUES ($case_id,$i," . ereg_replace('[^0-9]*','',$r5v['phone']) . ")";
								$db->Execute($sql);
								$i++;
							}
						}
						else
						{
							$sql = "INSERT INTO contact_phone (case_id,priority,phone)
								VALUES ($case_id,1,312345678)";
							$db->Execute($sql);
						}

					}
	
					//add respondent details to respondent (if such details exist in the sample)
	
					$sql = "INSERT INTO respondent (case_id,firstName,lastName,Time_zone_name) 
						SELECT $case_id as case_id, s1.val as firstName, s2.val as lastName, s3.Time_zone_name as Time_zone_name  
						FROM sample as s3
						LEFT JOIN sample_var as s2 on (s2.sample_id = '{$r3['sample_id']}' and s2.type = 7) 
						LEFT JOIN sample_var as s1 on (s1.sample_id = '{$r3['sample_id']}' and s1.type = 6)  						   WHERE s3.sample_id = '{$r3['sample_id']}'";
	
					$db->Execute($sql);
	
	
					//add resopndent to Lime Survey token table for this questionnaire
	
					//first we need to get the limesurvey survey id 
	
					$lime_sid = get_limesurvey_id($operator_id);
	
					if ($lime_sid)
					{
						$sql = "INSERT INTO ".LIME_PREFIX."tokens_$lime_sid (tid,firstname,lastname,email,token,language,sent,completed,attribute_1,attribute_2,mpid)
						VALUES (NULL,'','','',$case_id,'en','N','N','','',NULL)";
	
						if (!$ldb->Execute($sql)) //if we cannot insert
						{
							$db->FailTrans();
							$case_id = false;
						}
					}
	
				}
			}
			else
			{
				$case_id = $r2['caseid'];
	
				$sql = "UPDATE `case`
					SET current_operator_id = '$operator_id'
					WHERE case_id = '$case_id'";
	
	
				$db->Execute($sql);
		
	
			}
		}
		else
		{
			$case_id = false;
		}
	}
	else
	{
		$case_id = $r1['case_id'];
	
	}

	$db->CompleteTrans();

	/**
	 * @todo should re return some sort of status? Like "on appointment" "refusal" "supervisor"?
	 */

	return $case_id;

}

/**
 * Return the phone number of a call
 *
 * @param int $call_id The call id
 * @return bool|string The number to call otherwise False if cannot find
 *
 */
function get_call_number($call_id)
{
	global $db;

	$sql = "SELECT p.phone
		FROM `call` as c
		JOIN (contact_phone as p) on (p.contact_phone_id = c.contact_phone_id)
		WHERE c.call_id = '$call_id'";

	$rs = $db->GetRow($sql);

	if(!empty($rs))
		return $rs['phone'];
	else
		return false;
}

/**
 * Return the extension of an operator
 *
 * @param int $operator_id The queXS Operator ID
 * @return string|bool the extension or false if cannot find
 *
 */
function get_extension($operator_id)
{
	global $db;
		
	$sql = "SELECT o.extension
		FROM `operator` as o
		WHERE o.operator_id = '$operator_id'";

	$rs = $db->GetRow($sql);
	if (!empty($rs) && isset($rs['extension'])) return $rs['extension'];
	return false;		
}


/**
 * Return the current operator id based on PHP_AUTH_USER
 *
 * @return bool|int False if none otherwise the operator id
 *
 */
function get_operator_id()
{
	global $db;

	$sql = "SELECT operator_id
		FROM operator
		WHERE username = '{$_SERVER['PHP_AUTH_USER']}'
		AND enabled = 1";

	$o = $db->GetRow($sql);

	if (empty($o)) 	return false;

	return $o['operator_id'];

}

/**
 * Return the time in UTC from the database
 *
 * @return string The date and time in format: YYYY-MM-DD HH:MM:SS
 *
 */
function get_db_time()
{
	global $db;

	$sql = "SELECT CONVERT_TZ(NOW(),'System','UTC') as time";

	$rs = $db->GetRow($sql);

	return $rs['time'];

}


/**
 * Return the time for the operator
 *
 * @param int $operator_id The operator id
 * @param string $format Defaults to: YYYY-MM-DD HH:MM:SS, see {@link http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_date-format MySQL Date format}
 * @return string The date and time in the given format
 *
 */
function get_operator_time($operator_id,$format = "%Y-%m-%d %H:%i:%S")
{
	global $db;

	$sql = "SELECT DATE_FORMAT(CONVERT_TZ(NOW(),'System', Time_zone_name),'$format') as time
		FROM operator
		WHERE operator_id = '$operator_id'";

	$rs = $db->GetRow($sql);

	return $rs['time'];

}

/**
 * Return the time for the respondent
 *
 * @param int $respondent_id The respondent id
 * @param string $format Defaults to: YYYY-MM-DD HH:MM:SS, see {@link http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_date-format MySQL Date format}
 * @return string The date and time in the given format
 *
 */
function get_respondent_time($respondent_id,$format="%Y-%m-%d %H:%i:%S")
{
	global $db;

	$sql = "SELECT DATE_FORMAT(CONVERT_TZ(NOW(),'System', Time_zone_name),'$format') as time
		FROM respondent
		WHERE respondent_id = '$respondent_id'";

	$rs = $db->GetRow($sql);

	return $rs['time'];
}

/**
 * Return the current questionnaire assigned to the operator
 * false if none
 *
 * @param int $operator_id The operator id
 * @return bool|int False if none otherwise the questionnare id
 *
 */
function get_questionnaire_id($operator_id)
{
	global $db;

	$sql = "SELECT questionnaire_id
		FROM `case` as c
		WHERE c.current_operator_id = '$operator_id'";

	$rs = $db->GetRow($sql);

	if (empty($rs)) return false;

	return $rs['questionnaire_id'];

}

/**
 * Return the id of the shift the operator is currently on
 * 
 *
 * @param int $operator_id The operator id
 * @return bool|int False if none otherwise the shift id
 *
 */
function is_on_shift($operator_id)
{
	global $db;

	$case_id = get_case_id($operator_id,false);
	if ($case_id)
	{
		$sql = "SELECT s.shift_id
			FROM `case` as c, `shift` as s
			WHERE c.case_id = '$case_id'
			AND c.questionnaire_id = s.questionnaire_id
			AND s.`start` <= CONVERT_TZ(NOW(),'System','UTC')
			AND s.`end` >= CONVERT_TZ(NOW(),'System','UTC')";
	
		$row = $db->GetRow($sql);
	
		if (empty($row)) return false;
		return $row['shift_id'];
	}
	else
		return false;

}


/**
 * Return the state of a call if the operator is currently on a call
 * (see the call_state table for details)
 *
 * @param int $operator_id The operator id
 * @return bool|int False if none otherwise the call state id
 *
 */
function is_on_call($operator_id)
{
	global $db;

	$case_id = get_case_id($operator_id,false);
	if ($case_id)
	{
		$ca = get_call_attempt($operator_id);
	
		$sql = "SELECT call_id,state
			FROM `call`
			WHERE case_id = '$case_id'
			AND operator_id = '$operator_id'
			AND call_attempt_id = '$ca'
			AND outcome_id = '0'";
	
		$row = $db->GetRow($sql);
	
		if (empty($row)) return false;
		return $row['state'];
	}
	else
		return false;
}


/** 
 * Return true if the operator is currently on a call attempt
 * A call attempt is a set of calls within a "session"
 * 
 * @param int $operator_id The operator id
 * @return bool True if on a call attempt otherwise false
 *
 */
function is_on_call_attempt($operator_id)
{
	global $db;

	$case_id = get_case_id($operator_id,false);

	if ($case_id)
	{
		$sql = "SELECT call_attempt_id
			FROM `call_attempt`
			WHERE case_id = '$case_id'
			AND operator_id = '$operator_id'
			AND end IS NULL";
	
		$row = $db->GetRow($sql);
	
		if (empty($row)) return false;
		return true;
	}
		return false;

}



/**
 * Get the current call if on a call attempt 
 * If no call, create one
 *
 * @param int $operator_id The operator
 * @param string|int $respondent_id The respondent
 * @param string|int $contact_phone_id The number to contact the respondent on
 * @return bool|int False if no call exists or can be created otherwise the call_id
 *
 */
function get_call($operator_id,$respondent_id = "",$contact_phone_id = "")
{
	global $db;

	$case_id = get_case_id($operator_id,false);
	$ca = get_call_attempt($operator_id);

	if ($case_id && $ca)
	{

		$db->StartTrans();

		$sql = "SELECT call_id
			FROM `call`
			WHERE case_id = '$case_id'
			AND operator_id = '$operator_id'
			AND call_attempt_id = '$ca'
			AND outcome_id = '0'";

		$row = $db->GetRow($sql);
		if (empty($row))
		{
			if (!empty($respondent_id) && !empty($contact_phone_id))
			{
				$sql = "INSERT INTO `call` (call_id,operator_id,case_id,call_attempt_id,start,end,respondent_id,contact_phone_id,outcome_id,state)
				VALUES (NULL,'$operator_id','$case_id','$ca',CONVERT_TZ(NOW(),'System','UTC'),NULL,'$respondent_id','$contact_phone_id','0','1')";
				$db->Execute($sql);
				$id = $db->Insert_Id();
			}
			else
			{
				$id = false;
			}
		}
		else
		{
			$id = $row['call_id'];
		}

		
		$db->CompleteTrans();
		return $id;
	}
	else
	{
		return false;
	}

}


/**
 * Get the complete URL for the Limesurvey questionnaire
 * If no case available, return an error screen
 *
 * @param int $operator_id The operator id
 * @return string The URL of the LimeSurvey questionnaire, or the URL of an error screen if none available
 *
 */
function get_limesurvey_url($operator_id)
{
	global $db;

	$case_id = get_case_id($operator_id);
	if ($case_id)
	{
		$sid = get_limesurvey_id($operator_id);
		$url = LIME_URL . "index.php?loadall=reload&amp;sid=$sid&amp;token=$case_id&amp;lang=en";
		$questionnaire_id = get_questionnaire_id($operator_id);
		//get prefills
		$sql = "SELECT lime_sgqa,value
			FROM questionnaire_prefill
			WHERE questionnaire_id = '$questionnaire_id'";
		$pf = $db->GetAll($sql);
		if (!empty($pf))
		{
			foreach ($pf as $p)
				$url .= "&amp;" . $p['lime_sgqa'] . "=" . template_replace($p['value'],$operator_id,$case_id);
		}
		return $url;
	}
	else
	{
		//no cases currently available
		return "nocaseavailable.php";
	}
}


/**
 * Return the appointment id if this call attempt is on an appointment
 *
 * @param int $call_attempt_id The current call attempt id
 * @return bool|int False if no appointment otherwise the appointment id
 *
 */
function is_on_appointment($call_attempt_id)
{
	global $db;
	//return the appointment id if this call attempt is on an appointment

	$sql = "SELECT a.appointment_id
		FROM call_attempt as ca
		LEFT JOIN appointment as a on (a.case_id = ca.case_id and (ca.start >= a.start and ca.start <= a.end) and a.completed_call_id is NULL)
		WHERE ca.call_attempt_id = '$call_attempt_id'
		";

	$a = $db->GetRow($sql);

	if (empty($a) || empty($a['appointment_id']))
		return false;
	else
		return $a['appointment_id'];

}


/**
 * Whether we should leave a message on the answering machine or not
 *
 * @param int $case_id The current case id
 * @return bool True if we should leave a message on the machine, false otherwise
 *
 */
function leave_message($case_id)
{
	global $db;

	$sql = "SELECT (SELECT count(*) as count FROM `call` WHERE case_id = '$case_id' AND outcome_id = '23') as messages_left, qs.answering_machine_messages
		FROM `questionnaire_sample` as qs, `case` as c, `sample` as s
		WHERE c.case_id = '$case_id'
		AND qs.questionnaire_id = c.questionnaire_id
		AND s.sample_id = c.sample_id
		AND qs.sample_import_id = s.import_id";

	$a = $db->GetRow($sql);

	if (!empty($a))
	{
		//if ($a['answering_machine_messages'] == 0) return true; //unlimited
		if ($a['messages_left'] < $a['answering_machine_messages']) return true;
	}
	return false;

}



/**
 * Return the appointment id if this call attempt is calling 
 * where an appointment was made and not kept
 *
 * @param int $call_attempt_id The current call attempt id
 * @return bool|int False if no appointment otherwise the appointment id
 *
 */
function missed_appointment($call_attempt_id)
{
	global $db;

	$sql = "SELECT a.appointment_id
		FROM call_attempt as ca
		LEFT JOIN appointment as a on (a.case_id = ca.case_id and ca.start >= a.end and a.completed_call_id is NULL)
		WHERE ca.call_attempt_id = '$call_attempt_id'";

	$a = $db->GetRow($sql);

	if (empty($a) || empty($a['appointment_id']))
		return false;
	else
		return $a['appointment_id'];

}



/**
 * End the current case
 *
 * @param int $operator_id The operator to end the case for
 *
 * @see get_case()
 * @todo implement session handling to decrease database requests 
 */
function end_case($operator_id)
{
	global $db;

	$case_id = get_case_id($operator_id,false);

	if ($case_id)
	{
		$db->StartTrans();

		//determine current final outcome code
		//Look over all calls, for each phone number that is to be tried again
		//Calculate outcome based on 
			//If no phone number is to be tried again, use the outcome from the last call
		//If one phone number is to be tried again, use: "Differences in Response Rates using Most recent versus Final dispositions in Telephone Surveys" by Christopher McCarty
		//



		//Look for any calls where none should be tried again (this should be a final outcome)
		$sql = "SELECT c.call_id, c.outcome_id
			FROM `call` as c, `outcome` as o
			WHERE c.case_id = '$case_id'
			AND c.outcome_id = o.outcome_id
			AND o.tryanother = 0
			AND o.outcome_type_id = 4
			ORDER BY c.call_id DESC
			LIMIT 1";

		$a = $db->GetRow($sql);

		if (empty($a))
		{
		
			$sql = "SELECT c.*
				FROM contact_phone AS c
				LEFT JOIN (
					SELECT contact_phone.contact_phone_id
					FROM contact_phone
					LEFT JOIN `call` ON ( call.contact_phone_id = contact_phone.contact_phone_id )
					LEFT JOIN outcome ON ( call.outcome_id = outcome.outcome_id )
					WHERE contact_phone.case_id = '$case_id'
					AND outcome.tryagain =0
					) AS l ON l.contact_phone_id = c.contact_phone_id
				WHERE c.case_id = '$case_id'
				AND l.contact_phone_id IS NULL";
	
			$r = $db->GetAll($sql);
	
			//$r contains one row for each phone number that is to be tried again
			if (!empty($r))
				$count = count($r);
			else 
				$count = 0;
	
			$outcome = 1; //default outcome is 1 - not attempted
	
			//last call
			$sql = "SELECT call_id,outcome_id
				FROM `call`
				WHERE case_id = '$case_id'
				ORDER BY call_id DESC
				LIMIT 1";
	
			$l = $db->GetRow($sql);
	
			$lastcall = 0;
			if (!empty($l))
				$lastcall = $l['call_id'];
		
	
			if ($count == 0) //no numbers to be tried again, get last outcome or 1
			{
				//last call
				$sql = "SELECT c.outcome_id as outcome_id
					FROM `call` as c
					JOIN outcome AS o ON ( c.outcome_id = o.outcome_id)
					WHERE c.case_id = '$case_id'
					ORDER BY o.contacted DESC,c.call_id DESC
					LIMIT 1";
			
			$t = $db->GetRow($sql);
		
			if (!empty($t))
				$outcome = $t['outcome_id'];
			}
			else if ($count >= 1) //one or more numbers to be tried again - first code as eligible if ever eligible...
			{
				//$r[0]['contact_phone_id'];
				$sql = "SELECT c.outcome_id as outcome_id
					FROM `call` as c
					JOIN outcome AS o ON ( c.outcome_id = o.outcome_id AND o.eligible = 1)
					WHERE c.case_id = '$case_id'
					ORDER BY c.call_id DESC";
			
				$t = $db->GetRow($sql);

				if (!empty($t))
					$outcome = $t['outcome_id'];
			}
		}
		else
		{
			//the last call is the call with the final otucome
			$outcome = $a['outcome_id'];
			$lastcall = $a['call_id'];
		}
		
		$sql = "UPDATE `case`
			SET current_operator_id = NULL, current_call_id = NULL, current_outcome_id = '$outcome', last_call_id = '$lastcall'
			WHERE case_id = '$case_id'";

		$o = $db->Execute($sql);
		$db->CompleteTrans();

		return $o;
	}
	else
	{
		return false;
	}

}

/**
 * Outcome description
 * 
 * @param int $outcome_id The outcome id
 * @return string The description of the outcome
 */
function outcome_description($outcome_id)
{
	global $db;

	$sql = "SELECT description 
		FROM outcome
		WHERE outcome_id = '$outcome_id'";

	$r = $db->CacheGetRow($sql);

	if (!empty($r))
		return $r['description'];
	else
		return "";

}


/**
 * Determine the outcome for this call attempt
 *
 * @param int $call_attempt The call attempt
 * @return int The outcome_id for this call attempt
 
 function determine_outcome($call_attempt,$update = false)
{
	global $db;

	//determine outcome code (for now select the last one)
	$sql = "SELECT c.outcome_id as outcome_id,c.call_id
		FROM `call` as c
		LEFT JOIN `outcome` as o on (o.outcome_id = c.outcome_id)
		WHERE c.call_attempt_id = '$call_attempt'
		ORDER BY o.outcome_type_id ASC, o.default_delay_minutes ASC";

	$r = $db->GetRow($sql);

	$sql = "SELECT appointment_id
		FROM appointment
		WHERE call_attempt_id = '$call_attempt'";

	$a = $db->GetRow($sql);

	$outcome = 1; //default outcome is 1 - not attempted
	if (!empty($r))
	{
		$outcome = $r['outcome_id'];
		$call = $r['call_id'];

		$a = is_on_appointment($call_attempt); //if we were on an appointment, complete it
		if ($update && $a)
		{
			$sql = "UPDATE appointment
				SET completed_call_id = '$call'
				WHERE appointment_id = '$a'";
			$db->Execute($sql);
		}
	}
	else
	{
		if (!empty($a)) //made an appointment without making a call
			$outcome = 20;
	}

	return $outcome;
}
 */


/**
 * End the current call attempt
 *
 * @param int $operator_id The operator
 *
 * @see get_call_attempt()
 */
function end_call_attempt($operator_id)
{
	global $db;

	$ca = get_call_attempt($operator_id);

	if ($ca)
	{
		$db->StartTrans();
		
		$sql = "UPDATE `call_attempt`
			SET end = CONVERT_TZ(NOW(),'System','UTC')
			WHERE call_attempt_id = '$ca'";

		$o = $db->Execute($sql);

		$db->CompleteTrans();

		return $o;
	}
	else
	{
		return false;
	}

}

/**
 * Return the respondent of the given call attempt
 *
 * @param int $call_attempt_id The call attempt
 * @return bool|int False if no call attempt/respondent otherwise the respondent_id
 *
 */
function get_respondent_id($call_attempt_id)
{
	global $db;

	$sql = "SELECT respondent_id
		FROM call_attempt
		WHERE call_attempt_id = '$call_attempt_id'";

	$row = $db->GetRow($sql);

	if (!empty($row))
		return $row['respondent_id'];
	else
		return false;

}

/**
 * Return the call attempt of the given operator
 *
 * @param int $operator_id The oeprator
 * @return bool|int False if no case otherwise the call_attempt_id
 *
 */
function get_call_attempt($operator_id)
{
	global $db;

	$case_id = get_case_id($operator_id,false);

	if ($case_id)
	{
		$db->StartTrans();

		$sql = "SELECT call_attempt_id
			FROM `call_attempt`
			WHERE case_id = '$case_id'
			AND operator_id = '$operator_id'
			AND end IS NULL";

		$row = $db->GetRow($sql);

		$id = false;
			
		/**
		 * If no call_attempt, create one
		 */
		if (empty($row))
		{
			$sql = "SELECT respondent_id
				FROM respondent
				WHERE case_id = '$case_id'";

			$row2 = $db->GetRow($sql);

			$respondent_id = 0;
			if (!empty($row2)) $respondent_id = $row2['respondent_id'];

			$sql = "INSERT INTO `call_attempt` (call_attempt_id,operator_id,case_id,respondent_id,start,end)
				VALUES (NULL,'$operator_id','$case_id','$respondent_id',CONVERT_TZ(NOW(),'System','UTC'),NULL)";

			$db->Execute($sql);
			$id = $db->Insert_Id();
		}
		else
		{
			$id = $row['call_attempt_id'];
		}

		
		$db->CompleteTrans();
		return $id;
	}
	else
	{
		return false;
	}

}

/**
 * End the current call
 * Store the time and outcome in the database
 *
 * @param int $operator_id The operator
 * @param int $outcome_id The outcome to the call
 * @param int $state The end state of the call 5 default
 * @return bool True if database execution succeeded
 *
 * @todo Implement session destruction here
 *
 */
function end_call($operator_id,$outcome_id,$state = 5)
{
	global $db;

	$ca = get_call($operator_id);

	if ($ca)
	{
		$c = get_call_attempt($operator_id);
		if ($c)
		{
			$a = is_on_appointment($c); //if we were on an appointment, complete it with this call
			if ($a)
			{
				$sql = "UPDATE appointment
					SET completed_call_id = '$ca'
					WHERE appointment_id = '$a'";
				$db->Execute($sql);
			}
		}

		$sql = "UPDATE `call`
			SET end = CONVERT_TZ(NOW(),'System','UTC'), outcome_id = '$outcome_id', state = '$state'
			WHERE call_id = '$ca'";

		$o = $db->Execute($sql);

		return $o;
	}
	else
	{
		return false;
	}

}

/**
 * Get the limesurvey "survey id" of the current questionnaire assigned to the operator
 *
 * @param int $operator_id The operator
 * @return bool|int False if none found else the limesurvey sid
 *
 * @todo Implement session destruction here
 *
 */
function get_limesurvey_id($operator_id)
{
	global $db;

	$sql = "SELECT q.lime_sid  as lime_sid
		FROM `case` as c, questionnaire_sample as qs, sample as s, questionnaire as q
		WHERE c.current_operator_id = '$operator_id'
		AND c.sample_id = s.sample_id
		AND s.import_id = qs.sample_import_id
		AND q.questionnaire_id = qs.questionnaire_id
		AND c.questionnaire_id = q.questionnaire_id";

	$rs = $db->GetRow($sql);

	if (empty($rs)) return false;

	return $rs['lime_sid'];

}

/**
 * Add a respondent to the case
 *
 *  @param int $case_id The case id
 *  @param string $firstName The first name of the respondent
 *  @param string $lastName The last name of the respondent
 *  @param string $Time_zone_name The TimeZone in MySQL format
 *
 *  @return int The respondent ID
 *
 */
function add_respondent($case_id,$firstName,$lastName,$Time_zone_name)
{
	global $db;

	$case_id = $db->qstr($case_id,get_magic_quotes_gpc());
	$firstName = $db->qstr($firstName,get_magic_quotes_gpc());
	$lastName = $db->qstr($lastName,get_magic_quotes_gpc());
	$Time_zone_name = $db->qstr($Time_zone_name,get_magic_quotes_gpc());

	$sql = "INSERT INTO respondent (respondent_id,case_id,firstName,lastName,Time_zone_name)
		VALUES (NULL,$case_id,$firstName,$lastName,$Time_zone_name)";

	$db->Execute($sql);

	return $db->Insert_ID();

}


?>