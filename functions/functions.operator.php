<?php 
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
* Creates a random sequence of characters
*
* @param mixed $length Length of resulting string
* @param string $pattern To define which characters should be in the resulting string
* 
* From Limesurvey
*/
function sRandomChars($length = 15,$pattern="23456789abcdefghijkmnpqrstuvwxyz")
{
    $patternlength = strlen($pattern)-1;
    for($i=0;$i<$length;$i++)
    {   
        if(isset($key))
            $key .= $pattern{rand(0,$patternlength)};
        else
            $key = $pattern{rand(0,$patternlength)};
    }
    return $key;
}

/**
 * Check if the project associated with this case is using 
 * questionnaire availability
 * 
 * @param int $case_id 
 * 
 * @return boolean True if using case availability, false if not
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2011-07-01
 */
function is_using_availability($case_id)
{
	global $db;

	$sql = "SELECT count(qa.questionnaire_id) as cc
		FROM `case` as c, questionnaire_availability as qa
		WHERE qa.questionnaire_id = c.questionnaire_id
		AND c.case_id = $case_id";

	$rs = $db->GetRow($sql);

	if ($rs['cc'] > 0)
		return true;

	return false;
}

/**
 * Return if chat is enabled for this operator
 * 
 * @param int $operator_id the operator id
 * 
 * @return bool True if enabled, false if not
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2013-07-16
 */
function operator_chat_enabled($operator_id)
{
	global $db;

	$sql = "SELECT chat_enable
		FROM `operator`
		WHERE operator_id = '$operator_id'";

	$c = $db->GetOne($sql);	

	if ($c == 1)
		return true;

	return false;
}

/**
 * Return if VOIP is enabled on an operator by operator basis
 * Will always return false if VOIP is globally disabled
 *
 * @param int $operator_id the operator id
 * @return bool True if enabled, false if not
 */
function is_voip_enabled($operator_id)
{
	if (VOIP_ENABLED == false)
		return false;

	global $db;

	$sql = "SELECT o.voip
		FROM `operator` as o, `extension` as e
    WHERE o.operator_id = '$operator_id'
    AND e.current_operator_id = o.operator_id";

	$rs = $db->GetRow($sql);

	if (isset($rs['voip']) && $rs['voip'] == '1')
		return true;

	return false;
}

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
		JOIN `case` as c on (c.case_id = '$case_id' and s.sample_id = c.sample_id), `sample_import_var_restrict` as sivr
		WHERE sivr.var = '$variable'
		AND s.var_id = sivr.var_id";

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
	$respondent_id = get_respondent_id(get_call_attempt($operator_id,false));

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
 * @return bool|int True if respondent selection enabled, lime_sid if rs enabled with limesurvey otherwise false
 *
 */
function is_respondent_selection($operator_id)
{
	global $db;

	$db->StartTrans();

	$questionnaire_id = get_questionnaire_id($operator_id);

	if (!$questionnaire_id)
		return false;

	$sql = "SELECT respondent_selection, lime_rs_sid
		FROM questionnaire 
		WHERE questionnaire_id = '$questionnaire_id'";
	
	$rs = $db->GetRow($sql);

	
	//if ($db->HasFailedTrans()) { print "FAILED in is_respondent_selection"; exit; }

	if (!$db->CompleteTrans())
		return false;

	if (!$rs) return false;
	if ($rs['respondent_selection'] == 1){
		if ($rs['lime_rs_sid'] > 0)
		{
			return $rs['lime_rs_sid'];	
		}
		return true;
	}
	return false;
}

/**
 * Add a case to the system based on a sample record
 *
 * @param int $sample_id The sample id
 * @param int $questionnaire_id The questionnaire id
 * @param int $operator_id The operator id (Default NULL)
 * @param int $testing 0 if a live case otherwise 1 for a testing case
 * 
 * @return int The case id
 */
function add_case($sample_id,$questionnaire_id,$operator_id = "NULL",$testing = 0)
{
	global $db;

	$token = sRandomChars();

	$sql = "INSERT INTO `case` (case_id, sample_id, questionnaire_id, last_call_id, current_operator_id, current_call_id, current_outcome_id,token)
		VALUES (NULL, $sample_id, $questionnaire_id, NULL, $operator_id, NULL, 1, '$token')";

	$db->Execute($sql);

	$case_id = $db->Insert_ID();

	//if this sample is set as testing, assign internal numbers as numbers
	if ($testing == 1)
	{
		$db->Execute("SET @row := 0");

		$sql = "INSERT INTO contact_phone (case_id,priority,phone,description)
			SELECT $case_id as case_id,@row := @row + 1 AS priority,IFNULL(SUBSTRING_INDEX(e.extension,'/',-1),'312345678') as phone, CONCAT(o.firstName, ' ', o.lastName)
      FROM operator as o
      LEFT JOIN `extension` as e ON (e.current_operator_id = o.operator_id)
      WHERE o.enabled = 1";
	
		$db->Execute($sql);
	}
	else
	{
		//add any phone numbers to contact phone

		//$db->Execute("SET @row := 0");

		$sql = "SELECT sv.val as phone
			FROM sample_var as sv, sample_import_var_restrict as sivr
			WHERE sv.sample_id = '$sample_id'
			AND sv.var_id = sivr.var_id
			AND sv.val > 0
			AND sv.val is NOT NULL
			AND sv.val != \"\"
			AND sivr.`type` IN (2,3)
			ORDER BY sivr.`type` DESC";

		$r5 = $db->GetAll($sql);

		if (!empty($r5))
		{
			$i = 1;
			foreach ($r5 as $r5v)
			{
				$tnum =  preg_replace("/[^0-9]/", "",$r5v['phone']); 
				if (empty($tnum)) $tnum = "88888888"; //handle error condition
				$sql = "INSERT INTO contact_phone (case_id,priority,phone,description)
					VALUES ($case_id,$i,$tnum,'')";
				$db->Execute($sql);
				$i++;
			}
		}
		else
		{
			$sql = "INSERT INTO contact_phone (case_id,priority,phone,description)
				VALUES ($case_id,1,88888888,'test only')";
			$db->Execute($sql);
		}

	}

	//add respondent details to respondent (if such details exist in the sample)

	$sql = "INSERT INTO respondent (case_id,firstName,lastName,Time_zone_name) 
		SELECT $case_id as case_id, IFNULL(s1.val,'') as firstName, IFNULL(s2.val,'') as lastName, s3.Time_zone_name as Time_zone_name  
		FROM sample as s3
		LEFT JOIN (sample_var as s2 , sample_import_var_restrict as sivr2) on (s2.sample_id = '$sample_id' and s2.var_id = sivr2.var_id and sivr2.type = 7)  
		LEFT JOIN (sample_var as s1 , sample_import_var_restrict as sivr1) on (s1.sample_id = '$sample_id' and s1.var_id = sivr1.var_id and sivr1.type = 6) 
		WHERE s3.sample_id = '$sample_id'";

	$db->Execute($sql);


	//add resopndent to Lime Survey token table for this questionnaire

	//first we need to get the limesurvey survey id 

	if (!$db->HasFailedTrans()) //if the transaction hasn't failed
	{
		$sql = "SELECT lime_sid
			FROM questionnaire
			WHERE questionnaire_id = '$questionnaire_id'";

		$lime_sid = $db->GetOne($sql);

		if ($lime_sid)
		{
			$sql = "INSERT INTO ".LIME_PREFIX."tokens_$lime_sid (tid,firstname,lastname,email,token,language,sent,completed,mpid)
			VALUES (NULL,'','','','$token','".DEFAULT_LOCALE."','N','N',NULL)";

			$db->Execute($sql);
		}
	}

	return $case_id;
}


/**
 * Get the current or next case id
 *
 * @param int $operator_id The operator id
 * @param bool $create True if a case can be created
 * @return bool|int False if no case available else the case_id
 */
function get_case_id($operator_id, $create = false)
{

	global $db;

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
		$sql = "SELECT o.next_case_id 
			FROM `operator` as o, `case` as c
			WHERE o.operator_id = '$operator_id'
			AND c.case_id = o.next_case_id
			AND c.current_operator_id IS NULL";

		$rnc = $db->GetRow($sql);

		$sql = "SELECT cq.case_id, cq.case_queue_id
			FROM case_queue as cq, `case` as c
			WHERE cq.operator_id = '$operator_id'
			AND cq.case_id = c.case_id
			AND c.current_operator_id IS NULL
			ORDER BY cq.sortorder ASC
			LIMIT 1";
		
		$sq = $db->GetRow($sql);

		if (isset($rnc['next_case_id']) && !empty($rnc['next_case_id']))
		{
			$case_id = $rnc['next_case_id'];

			$sql = "UPDATE `case`
				SET current_operator_id = '$operator_id'
				WHERE current_operator_id IS NULL
				AND case_id = '$case_id'";
	
			$db->Execute($sql);
		
			//should fail transaction if already assigned to another case	
			if ($db->Affected_Rows() != 1)
			{
				$db->FailTrans();
			}
			else
			{
				//remove next case setting
				$sql = "UPDATE `operator`
					SET next_case_id = NULL
					WHERE operator_id = '$operator_id'";

				$db->Execute($sql);
			}

		}
		else if (isset($sq['case_id']) && !empty($sq['case_id']))
		{
			$case_id = $sq['case_id'];
			$case_queue_id = $sq['case_queue_id'];

			$sql = "UPDATE `case`
				SET current_operator_id = '$operator_id'
				WHERE current_operator_id IS NULL
				AND case_id = '$case_id'";
	
			$db->Execute($sql);
		
			//should fail transaction if already assigned to another case	
			if ($db->Affected_Rows() != 1)
			{
				$db->FailTrans();
			}
			else
			{
				//remove case from queue and update sortorder
				$sql = "DELETE FROM case_queue
					WHERE case_queue_id = '$case_queue_id'";

				$db->Execute($sql);

				$sql = "SELECT case_queue_id
					FROM case_queue
					WHERE operator_id = '$operator_id'
					ORDER BY sortorder ASC";

				$rs = $db->GetAll($sql);

				$sortorder = 1;
				foreach($rs as $r)
				{
					$sql = "UPDATE case_queue
						SET sortorder = '$sortorder'
						WHERE case_queue_id = '{$r['case_queue_id']}'";
			
					$db->Execute($sql);
			
					$sortorder++;			
				}
			}
		}
		else if ($create)
		{
			$systemsort = get_setting('systemsort');

			if ($systemsort)
			{
				//Just make sure that this case should go to this operator (assigned to this project and skill)
				//Also check if this is an exclusive appointment and that the questionnaire is enabled
				$sql = "SELECT c.case_id as caseid
					FROM `case` as c
					JOIN operator_questionnaire AS oq ON (oq.operator_id = '$operator_id' AND oq.questionnaire_id = c.questionnaire_id)
					JOIN questionnaire as q ON (q.questionnaire_id = c.questionnaire_id AND q.enabled = 1)
					JOIN outcome as ou ON (ou.outcome_id = c.current_outcome_id)
					JOIN operator_skill as os ON (os.operator_id = '$operator_id' AND os.outcome_type_id = ou.outcome_type_id)
					LEFT JOIN appointment as apn on (apn.case_id = c.case_id AND apn.completed_call_id is NULL AND (CONVERT_TZ(NOW(),'System','UTC') >= apn.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= apn.end))
					WHERE c.sortorder IS NOT NULL
					AND c.current_operator_id IS NULL
					AND ((apn.require_operator_id IS NULL) OR (apn.require_operator_id = '$operator_id'))
					ORDER BY c.sortorder ASC
					LIMIT 1";

			}
			else
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
				 *    Has not reached the quota
				 *    Is part of an enabled questionnaire
				 *
				 *    
				 *   THINGS TO ADD:
				 *
				 *   @todo also could check the respondent_not_available table to see if now is a "bad time" to call
				 */	
			
				$sql = "SELECT c.case_id as caseid
					FROM `case` as c
					LEFT JOIN `call` as a on (a.call_id = c.last_call_id)
					JOIN (sample as s, sample_import as si) on (s.sample_id = c.sample_id and si.sample_import_id = s.import_id)
					JOIN (questionnaire_sample as qs, operator_questionnaire as o, questionnaire as q, operator as op, outcome as ou) on (c.questionnaire_id = q.questionnaire_id and q.enabled = 1 and op.operator_id = '$operator_id' and qs.sample_import_id = s.import_id and o.operator_id = op.operator_id and o.questionnaire_id = qs.questionnaire_id and q.questionnaire_id = o.questionnaire_id and ou.outcome_id = c.current_outcome_id)
					LEFT JOIN shift as sh on (sh.questionnaire_id = q.questionnaire_id and (CONVERT_TZ(NOW(),'System','UTC') >= sh.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= sh.end))
					LEFT JOIN appointment as ap on (ap.case_id = c.case_id AND ap.completed_call_id is NULL AND (ap.start > CONVERT_TZ(NOW(),'System','UTC')))
					LEFT JOIN appointment as apn on (apn.case_id = c.case_id AND apn.completed_call_id is NULL AND (CONVERT_TZ(NOW(),'System','UTC') >= apn.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= apn.end))
					LEFT JOIN call_restrict as cr on (cr.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= cr.start and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= cr.end)
					LEFT JOIN questionnaire_sample_exclude_priority AS qsep ON (qsep.questionnaire_id = c.questionnaire_id AND qsep.sample_id = c.sample_id)
					LEFT JOIN case_availability AS casa ON (casa.case_id = c.case_id)
					LEFT JOIN availability AS ava ON (ava.availability_group_id = casa.availability_group_id)
					LEFT JOIN questionnaire_timeslot AS qast ON (qast.questionnaire_id = c.questionnaire_id)
					LEFT JOIN questionnaire_sample_timeslot AS qasts ON (qasts.questionnaire_id = c.questionnaire_id AND qasts.sample_import_id = si.sample_import_id)
					JOIN operator_skill as os on (os.operator_id = op.operator_id and os.outcome_type_id = ou.outcome_type_id)
					WHERE c.current_operator_id IS NULL
					AND ((apn.appointment_id IS NOT NULL) OR (casa.case_id IS NULL) OR (ava.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(),'System',s.Time_zone_name)) AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= ava.start AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= ava.end  ))
      AND ((apn.appointment_id IS NOT NULL) OR (qast.questionnaire_id IS NULL) OR ((SELECT COUNT(*) FROM availability WHERE availability.availability_group_id = qast.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(),'System',s.Time_zone_name)) AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= availability.end)) >= 1 AND (SELECT COUNT(call_attempt_id) FROM `call_attempt`, availability WHERE call_attempt.case_id = c.case_id AND (availability.availability_group_id = qast.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(call_attempt.start,'UTC',s.Time_zone_name)) AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) <= availability.end))) = (SELECT (SELECT COUNT(*) FROM availability, call_attempt WHERE call_attempt.case_id = c.case_id AND availability.availability_group_id = availability_group.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(call_attempt.start,'UTC',s.Time_zone_name)) AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) <= availability.end))  as cou FROM availability_group, questionnaire_timeslot WHERE questionnaire_timeslot.questionnaire_id = c.questionnaire_id AND availability_group.availability_group_id = questionnaire_timeslot.availability_group_id ORDER BY cou ASC LIMIT 1)))
          AND ((apn.appointment_id IS NOT NULL) OR (qasts.questionnaire_id IS NULL) OR ((SELECT COUNT(*) FROM availability WHERE availability.availability_group_id = qasts.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(),'System',s.Time_zone_name)) AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= availability.end)) >= 1 AND (SELECT COUNT(call_attempt_id) FROM `call_attempt`, availability WHERE call_attempt.case_id = c.case_id AND (availability.availability_group_id = qasts.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(call_attempt.start,'UTC',s.Time_zone_name)) AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) <= availability.end))) = (   SELECT (SELECT COUNT(*) FROM availability, call_attempt WHERE call_attempt.case_id = c.case_id AND availability.availability_group_id = availability_group.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(call_attempt.start,'UTC',s.Time_zone_name)) AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) <= availability.end))  as cou FROM availability_group, questionnaire_sample_timeslot WHERE questionnaire_sample_timeslot.questionnaire_id = c.questionnaire_id AND questionnaire_sample_timeslot.sample_import_id = si.sample_import_id AND availability_group.availability_group_id = questionnaire_sample_timeslot.availability_group_id ORDER BY cou ASC LIMIT 1)))
		            AND ((a.call_id is NULL) OR (a.end < CONVERT_TZ(DATE_SUB(NOW(), INTERVAL ou.default_delay_minutes MINUTE),'System','UTC')))
					AND ap.case_id is NULL
					AND ((qsep.questionnaire_id is NULL) OR (qsep.exclude = 0))
					AND !(q.restrict_work_shifts = 1 AND sh.shift_id IS NULL AND os.outcome_type_id != 2)
					AND !(si.call_restrict = 1 AND cr.day_of_week IS NULL AND os.outcome_type_id != 2)
					AND ((apn.appointment_id IS NOT NULL) OR (qs.call_attempt_max = 0) OR ((SELECT count(*) FROM call_attempt WHERE case_id = c.case_id) < qs.call_attempt_max))
					AND ((apn.appointment_id IS NOT NULL) OR (qs.call_max = 0) OR ((SELECT count(*) FROM `call` WHERE case_id = c.case_id) < qs.call_max))
					AND ((apn.require_operator_id IS NULL) OR (apn.require_operator_id = '$operator_id'))
					AND (SELECT count(*) FROM `questionnaire_sample_quota` WHERE questionnaire_id = c.questionnaire_id AND sample_import_id = s.import_id AND quota_reached = 1) = 0
					ORDER BY IF(ISNULL(apn.end),1,0),apn.end ASC, qsep.priority DESC, CONVERT_TZ(NOW(), 'System' , s.Time_zone_name) DESC , a.start ASC
					LIMIT 1";

 				//apn.appointment_id contains the id of an appointment if we are calling on an appointment
			}
			$r2 = $db->GetRow($sql);
	
			if (empty($r2))
			{
	
				if ($systemsort)
				{
					//Just make sure that this case should go to this operator (assigned to this project and skill)
					$sql = "SELECT qsep.sample_id as sample_id, qsep.questionnaire_id as questionnaire_id, q.testing as testing
						FROM questionnaire_sample_exclude_priority as qsep
						JOIN operator_skill as os ON (os.operator_id = '$operator_id' AND os.outcome_type_id = 1)
						JOIN operator_questionnaire AS oq ON (oq.operator_id = '$operator_id' AND oq.questionnaire_id = qsep.questionnaire_id)
						JOIN questionnaire as q ON (q.questionnaire_id = qsep.questionnaire_id and q.enabled = 1)
						LEFT JOIN `case` as c ON (c.sample_id = qsep.sample_id AND c.questionnaire_id = qsep.questionnaire_id)
						WHERE qsep.sortorder IS NOT NULL 
						AND c.case_id IS NULL
						ORDER BY qsep.sortorder ASC
						LIMIT 1";
	
				}
				else
				{

					/**
					 * If no case found, we must draw the next available case from the sample
					 * only if no case due to lack of cases to call not out of shift time/etc and
					 * only draw cases that are new (Temporary outcome_type_id) - this makes sure that we are not drawing
					 * a case just because the operator doesn't have access to temporary outcome id's.
					 *
					 *
					 * Method:
					 *    next available that has not been assigned
					 *    if none available - return false? report to operator that no one available to call at currenet settings
					 *
					 */
					
				  
					$sql = "SELECT s.sample_id as sample_id,c.case_id as case_id,qs.questionnaire_id as questionnaire_id,CONVERT_TZ(NOW(), 'System' , s.Time_zone_name) as resptime, q.testing as testing
						FROM sample as s
						JOIN (questionnaire_sample as qs, operator_questionnaire as o, questionnaire as q, operator as op, sample_import as si, operator_skill as os) on (op.operator_id = '$operator_id' and qs.sample_import_id = s.import_id and o.operator_id = op.operator_id and o.questionnaire_id = qs.questionnaire_id and q.questionnaire_id = o.questionnaire_id and si.sample_import_id = s.import_id and os.operator_id = op.operator_id and os.outcome_type_id = 1 and q.enabled = 1 and qs.allow_new = 1)
						LEFT JOIN `case` as c on (c.sample_id = s.sample_id and c.questionnaire_id = qs.questionnaire_id)
						LEFT JOIN call_restrict as cr on (cr.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= cr.start and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= cr.end)
						LEFT JOIN shift as sh on (sh.questionnaire_id = q.questionnaire_id and (CONVERT_TZ(NOW(),'System','UTC') >= sh.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= sh.end))
						LEFT JOIN questionnaire_sample_exclude_priority AS qsep ON (qsep.questionnaire_id = qs.questionnaire_id AND qsep.sample_id = s.sample_id)
						WHERE c.case_id is NULL
						AND ((qsep.questionnaire_id IS NULL) OR (qsep.exclude = 0))
						AND !(q.restrict_work_shifts = 1 AND sh.shift_id IS NULL)
						AND !(si.call_restrict = 1 AND cr.day_of_week IS NULL)
						AND (SELECT count(*) FROM `questionnaire_sample_quota` WHERE questionnaire_id = qs.questionnaire_id AND sample_import_id = s.import_id AND quota_reached = 1) = 0
						ORDER BY qsep.priority DESC, CONVERT_TZ(NOW(), 'System' , s.Time_zone_name) DESC, rand() * qs.random_select, s.sample_id
						LIMIT 1";
				}			
	
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
					$case_id = add_case($r3['sample_id'],$r3['questionnaire_id'],$operator_id,$r3['testing']);
				}	
			}
			else
			{
				$case_id = $r2['caseid'];
	
				$sql = "UPDATE `case`
					SET current_operator_id = '$operator_id'
					WHERE current_operator_id IS NULL
					AND case_id = '$case_id'";
	
				$db->Execute($sql);
		
				//should fail transaction if already assigned to another case	
				if ($db->Affected_Rows() != 1)
				{
					$db->FailTrans();
				}
	
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

	if ($db->HasFailedTrans()) 
	{ 
		error_log("FAILED in get_case_id for case $case_id",0);
		$case_id = false;  //make sure we aren't returning an invalid case id
	}
	$db->CompleteTrans();

	return $case_id;

}

/**
 * Get the token based on the case id
 * 
 * @param int $case_id The case id
 * 
 * @return string|bool The token otherwise false if case doesn't exist
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2013-02-25
 */
function get_token($case_id)
{
	global $db;

	$sql = "SELECT token 
		FROM `case`
		WHERE case_id = $case_id";

	$token = $db->GetOne($sql);

	if (empty($token)) return FALSE;

	return $token;
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
 * Set the extension status in the database
 *
 * @param int $operator_id The queXS Operator ID
 * @param bool the extension status (false for offline, true for online)
 *
 */
function set_extension_status($operator_id,$online = true)
{
	global $db;
		
	$s = 0;

	if ($online) $s = 1;

	$sql = "UPDATE `extension`
		SET status = '$s'
		WHERE current_operator_id = '$operator_id'";

	$db->Execute($sql);
}

/**
 * Return the extension status from the database
 *
 * @param int $operator_id The queXS Operator ID
 * @return bool the extension status (false for offline, true for online)
 *
 */
function get_extension_status($operator_id)
{
	global $db;
		
	$sql = "SELECT e.status
		FROM `extension` as e
    WHERE e.current_operator_id = '$operator_id'";

	$rs = $db->GetRow($sql);
	if (!empty($rs) && $rs['status'] == 1  ) return true;
	return false;		
}

/**
 * Return the extension password of an operator
 *
 * @param int $operator_id The queXS Operator ID
 * @return string|bool the extension password or false if cannot find
 *
 */
function get_extension_password($operator_id)
{
	global $db;
		
	$sql = "SELECT e.password
		FROM `extension` as e
		WHERE e.current_operator_id = '$operator_id'";

	$rs = $db->GetRow($sql);
	if (!empty($rs) && isset($rs['password'])) return $rs['password'];
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
		
	$sql = "SELECT e.extension
		FROM `extension` as e
    WHERE e.current_operator_id = '$operator_id'";

	$rs = $db->GetRow($sql);
	if (!empty($rs) && isset($rs['extension'])) return $rs['extension'];
	return false;		
}


/**
 * Return the current operator id based on SESSION loginID
 *
 * @return bool|int False if none otherwise the operator id
 *
 */
function get_operator_id()
{
	if (!isset($_SESSION['user']))
	{
    print "<p>" . T_("ERROR: You are not logged in.") . "</p>";
		die();
	}

	global $db;

	$sql = "SELECT operator_id
		FROM operator
		WHERE username = " . $db->qstr($_SESSION['user']) . "
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
	
	$db->StartTrans();

	$case_id = get_case_id($operator_id,false);

	$shift_id = false;

	if ($case_id)
	{
		$sql = "SELECT s.shift_id
			FROM `case` as c, `shift` as s
			WHERE c.case_id = '$case_id'
			AND c.questionnaire_id = s.questionnaire_id
			AND s.`start` <= CONVERT_TZ(NOW(),'System','UTC')
			AND s.`end` >= CONVERT_TZ(NOW(),'System','UTC')";
	
		$row = $db->GetRow($sql);
	
		if (!empty($row))
			$shift_id = $row['shift_id'];
	}
	
	//if ($db->HasFailedTrans()) { print "FAILED in is_on_shift"; exit; }
	if ($db->CompleteTrans())
		return $shift_id;
		
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

	$db->StartTrans();

	$case_id = get_case_id($operator_id,false);

	$call_state_id = false;

	if ($case_id)
	{
		$ca = get_call_attempt($operator_id,false);
	
		if ($ca)
		{
			$sql = "SELECT call_id,state
				FROM `call`
				WHERE case_id = '$case_id'
				AND operator_id = '$operator_id'
				AND call_attempt_id = '$ca'
				AND outcome_id = '0'";
		
			$row = $db->GetRow($sql);
		
			if (!empty($row))
				$call_state_id = $row['state'];
		}
	}
	
	//if ($db->HasFailedTrans()) { print "FAILED in is_on_call"; exit; }
	if ($db->CompleteTrans())
		return $call_state_id;
	
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

	$db->StartTrans();

	$case_id = get_case_id($operator_id,false);

	$return = false;

	if ($case_id)
	{
		$sql = "SELECT call_attempt_id
			FROM `call_attempt`
			WHERE case_id = '$case_id'
			AND operator_id = '$operator_id'
			AND end IS NULL";
	
		$row = $db->GetRow($sql);
	
		if (!empty($row))
			$return = true;
	}

	//if ($db->HasFailedTrans()) { print "FAILED in is_on_call_attempt"; exit; }
	if ($db->CompleteTrans())
		return $return;

	return false;
}



/**
 * Get the current call if on a call attempt 
 * If no call, create one
 *
 * @param int $operator_id The operator
 * @param string|int $respondent_id The respondent
 * @param string|int $contact_phone_id The number to contact the respondent on
 * @param bool $create Whether or not to create a call
 * @return bool|int False if no call exists or can be created otherwise the call_id
 *
 */
function get_call($operator_id,$respondent_id = "",$contact_phone_id = "",$create = false)
{
	global $db;

	$db->StartTrans();

	$case_id = get_case_id($operator_id,false);
	$ca = get_call_attempt($operator_id,false);

	$id = false;

	if ($case_id && $ca)
	{
		$sql = "SELECT call_id
			FROM `call`
			WHERE case_id = '$case_id'
			AND operator_id = '$operator_id'
			AND call_attempt_id = '$ca'
			AND outcome_id = '0'";

		$row = $db->GetRow($sql);
		if (empty($row))
		{
			if (!empty($respondent_id) && !empty($contact_phone_id) && $create)
			{
				$sql = "INSERT INTO `call` (call_id,operator_id,case_id,call_attempt_id,start,end,respondent_id,contact_phone_id,outcome_id,state)
				VALUES (NULL,'$operator_id','$case_id','$ca',CONVERT_TZ(NOW(),'System','UTC'),NULL,'$respondent_id','$contact_phone_id','0','1')";
				$db->Execute($sql);
				$id = $db->Insert_Id();

				//If respondent selection is enabled, add token to RS Limesurvey database
				$lime_rsid = is_respondent_selection($operator_id);

				if ($lime_rsid !== true && $lime_rsid > 0 && !$db->HasFailedTrans())
				//if the transaction hasn't failed and Limesurvey RS is enabled
				{
					$sql = "INSERT INTO ".LIME_PREFIX."tokens_$lime_rsid (tid,firstname,lastname,email,token,language,sent,completed,mpid)
					VALUES (NULL,'','','',$id,'en','N','N',NULL)";
		
					//Insert the token as the call_id
					$db->Execute($sql);
				}
				
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
		
	}

	//if ($db->HasFailedTrans()) { print "FAILED in get_call"; exit; }
	if ($db->CompleteTrans())
		return $id;

	return false;
}


/**
 * Get the complete URL for the Limesurvey questionnaire of respondent selection
 * If no call available, return an error screen
 *
 * @param int $operator_id The operator id
 * @param bool $escape Whether to escape the ampersands default true
 * @param bool $interface2 Whether we are using the alternate interface
 * @return string The URL of the LimeSurvey questionnaire, or the URL of an error screen if none available
 *
 */
function get_respondentselection_url($operator_id,$escape = true,$interface2 = false)
{
	global $db;

	$db->StartTrans();

	$url = "nocallavailable.php";

	$call_id = get_call($operator_id);

	$amp = "&amp;";
	if (!$escape) $amp = "&";

	if ($call_id)
	{
		$sid = get_limesurvey_id($operator_id,true); //true for RS
		if ($sid != false && !empty($sid) && $sid != 'NULL')
			$url = LIME_URL . "index.php?interviewer=interviewer" . $amp . "loadall=reload" . $amp . "sid=$sid" . $amp . "token=$call_id" . $amp . "lang=" . DEFAULT_LOCALE;
		else
    {
      if (is_respondent_selection($operator_id) === false)
      {
        $url = get_limesurvey_url($operator_id);
        if (!$escape)
          $url = str_replace("&amp;","&",$url);
      }
      else
      {
  			if ($interface2)
  				$url = 'rs_intro_interface2.php';
  			else
          $url = 'rs_intro.php';
      }
		}
	}

	//if ($db->HasFailedTrans()) { print "FAILED in get_limesurvey_url"; exit; }
	$db->CompleteTrans();

	return $url;
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

	$db->StartTrans();

	$url = "nocaseavailable.php";

	$case_id = get_case_id($operator_id,false);

	if ($case_id)
	{
		$sql = "SELECT token
			FROM `case`
			WHERE case_id = $case_id";

		$token = $db->GetOne($sql);

		$sid = get_limesurvey_id($operator_id);
		$url = LIME_URL . "index.php?interviewer=interviewer&amp;loadall=reload&amp;sid=$sid&amp;token=$token&amp;lang=" . DEFAULT_LOCALE;
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
	}

	//if ($db->HasFailedTrans()) { print "FAILED in get_limesurvey_url"; exit; }
	$db->CompleteTrans();

	return $url;
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
 * Update the quota table (sample wide)
 *
 * @param int $questionnaire_id The questionnaire ID to update
 */
function update_quota($questionnaire_id)
{
	global $db;

	$sql = "SELECT questionnaire_sample_quota_id,q.questionnaire_id,sample_import_id,lime_sgqa,value,comparison,completions,quota_reached,q.lime_sid
		FROM questionnaire_sample_quota as qsq, questionnaire as q
		WHERE qsq.questionnaire_id = '$questionnaire_id'
		AND q.questionnaire_id = '$questionnaire_id'
		and qsq.quota_reached != '1'";
	
	$rs = $db->GetAll($sql);

	if (isset($rs) && !empty($rs))
	{
		//include limesurvey functions
		include_once(dirname(__FILE__).'/functions.limesurvey.php');

		//update all quotas for this questionnaire
		foreach($rs as $r)
		{
			$completions = limesurvey_quota_completions($r['lime_sgqa'],$r['lime_sid'],$r['questionnaire_id'],$r['sample_import_id'],$r['value'],$r['comparison']);
			
			if ($completions >= $r['completions'])
			{
				//set quota to reached
				$sql = "UPDATE questionnaire_sample_quota
					SET quota_reached = '1'
					WHERE questionnaire_sample_quota_id = {$r['questionnaire_sample_quota_id']}";

				$db->Execute($sql);
			}
		}
	}

	return false;

}

/**
 * "Open" a row quota (allow to access)
 *
 * @param int $questionnaire_sample_quota_row_id The qsqri
 */
function open_row_quota($questionnaire_sample_quota_row_id,$delete = true)
{
	global $db;

	$db->StartTrans();

	$sql = "SELECT questionnaire_id
		FROM questionnaire_sample_quota_row
		WHERE questionnaire_sample_quota_row_id = '$questionnaire_sample_quota_row_id'";

	$rs = $db->GetRow($sql);

	if ($delete)
	{
		$sql = "DELETE FROM questionnaire_sample_quota_row
			WHERE questionnaire_sample_quota_row_id = '$questionnaire_sample_quota_row_id'";

		$db->Execute($sql);

    $sql = "DELETE FROM qsqr_sample
			WHERE questionnaire_sample_quota_row_id = '$questionnaire_sample_quota_row_id'";

    $db->Execute($sql);

    $sql = "DELETE FROM qsqr_question
			WHERE questionnaire_sample_quota_row_id = '$questionnaire_sample_quota_row_id'";

		$db->Execute($sql);

	}

	$sql = "DELETE FROM questionnaire_sample_quota_row_exclude
		WHERE questionnaire_sample_quota_row_id = '$questionnaire_sample_quota_row_id'";

	$db->Execute($sql);
	
	if (!empty($rs))
		update_quota_priorities($rs['questionnaire_id']);
	else
		die("error in open_row_quota");

	$db->CompleteTrans();
}

/**
 * "Close" a row quota (set to completed)
 *
 *
 */
function close_row_quota($questionnaire_sample_quota_row_id,$update = true)
{	
	global $db;

	$db->StartTrans();
		
	//only insert where we have to
	$sql = "SELECT count(*) as c
		FROM questionnaire_sample_quota_row_exclude
		WHERE questionnaire_sample_quota_row_id = '$questionnaire_sample_quota_row_id'";

	$coun = $db->GetRow($sql);

	$sql = "SELECT questionnaire_id
		FROM questionnaire_sample_quota_row
		WHERE questionnaire_sample_quota_row_id = '$questionnaire_sample_quota_row_id'";

	$rs = $db->GetRow($sql);

	if (isset($coun['c']) && $coun['c'] == 0)
  {
      $sql2 = "SELECT exclude_val,exclude_var,exclude_var_id,comparison
               FROM qsqr_sample
               WHERE questionnaire_sample_quota_row_id = $questionnaire_sample_quota_row_id
               AND exclude_var_id > 0";

      $rev = $db->GetAll($sql2);
      
      //store list of sample records to exclude
  		$sql = "INSERT INTO questionnaire_sample_quota_row_exclude (questionnaire_sample_quota_row_id,questionnaire_id,sample_id)
        			SELECT $questionnaire_sample_quota_row_id,qs.questionnaire_id,s.sample_id 
              FROM sample as s ";
			
      //reduce sample by every item in the qsqr_sample table
      $x = 1;
      foreach($rev as $ev)
      {
          $sql .= " JOIN sample_var as sv$x ON (sv$x.sample_id = s.sample_id AND sv$x.var_id = '{$ev['exclude_var_id']}' AND sv$x.val {$ev['comparison']} '{$ev['exclude_val']}') ";
          $x++;
      }

      $sql .= "JOIN questionnaire_sample_quota_row as qs ON (qs.questionnaire_sample_quota_row_id = $questionnaire_sample_quota_row_id)
        WHERE s.import_id = qs.sample_import_id";

		$db->Execute($sql);
		
		if ($db->HasFailedTrans()) die ($sql);		

		if ($update) 
		{
			if (!empty($rs))
				update_quota_priorities($rs['questionnaire_id']);
			else
				die("error in close_row_quota");
		}
	}

	$db->CompleteTrans();
}


/**
 * Copy row quotas from one sample to another
 * Set quota_reached to 0 by default
 *
 * @param int $questionnaire_id
 * @param int $sample_import_id
 * @param int $copy_sample_import_id The sample_import_id to copy to
 * @param bool $blocking Block (copy quota)?
 */
function copy_row_quota($questionnaire_id,$sample_import_id,$copy_sample_import_id, $block = false)
{
	global $db;

	$db->StartTrans();

  //Set quota_reached to 0 always if not blocking
  $b = 0;
  if ($block == true) 
    $b = "quota_reached";

	$sql = "INSERT INTO questionnaire_sample_quota_row (questionnaire_id,sample_import_id,completions,quota_reached,description)
		SELECT questionnaire_id, $copy_sample_import_id, completions,$b,description
		FROM questionnaire_sample_quota_row
		WHERE questionnaire_id = '$questionnaire_id'
		AND sample_import_id = '$sample_import_id'";
	
  $db->Execute($sql);

  $nqsqr = $db->Insert_ID();

  $sql = "INSERT INTO qsqr_sample (questionnaire_sample_quota_row_id,exclude_var_id,exclude_var,exclude_val,comparison,description)
    SELECT $nqsqr, qs.exclude_var_id, qs.exclude_var, qs.exclude_val, qs.comparison, qs.description
    FROM qsqr_sample as qs, questionnaire_sample_quota_row as q
    WHERE qs.questionnaire_sample_quota_row_id = q.questionnaire_sample_quota_row_id
    AND q.questionnaire_id = '$questionnaire_id'
    AND q.sample_import_id = '$sample_import_id'";

	update_quotas($questionnaire_id);

	$db->CompleteTrans();
}

/**
 * Copy row quotas from one sample to another with blocking
 * Set quota_reached to 0 by default
 *
 * @param int $questionnaire_id
 * @param int $sample_import_id
 * @param int $copy_sample_import_id The sample_import_id to copy to
 */
function copy_row_quota_with_blocking($questionnaire_id,$sample_import_id,$copy_sample_import_id)
{
  copy_row_quota($questionnaire_id,$sample_import_id,$copy_sample_import_id,true);

	$db->CompleteTrans();
}

/**
 * Copy row quotas from one sample to another and adjust completion number appropriately to completed in the sample.
 *
 * @param int $questionnaire_id
 * @param int $sample_import_id
 * @param int $copy_sample_import_id The sample_import_id to copy to
 */
function copy_row_quota_with_adjusting($questionnaire_id,$sample_import_id,$copy_sample_import_id)
{
	global $db;

    // Copy quotas (defalt Quexs function)
    copy_row_quota_with_blocking($questionnaire_id,$sample_import_id,$copy_sample_import_id);

  /*
	$db->StartTrans();

    // Select quotas from the old sample rows and calculate
	$sql = "SELECT questionnaire_sample_quota_row_id,q.questionnaire_id,sample_import_id,lime_sgqa,value,comparison,completions,quota_reached,q.lime_sid,qsq.exclude_var_id,qsq.exclude_var,qsq.exclude_val
		FROM questionnaire_sample_quota_row as qsq, questionnaire as q
		WHERE qsq.questionnaire_id = '$questionnaire_id'
		AND q.questionnaire_id = '$questionnaire_id'
		#AND qsq.quota_reached != '1'
		AND qsq.lime_sgqa != -1
        AND sample_import_id='".$sample_import_id."'
        ";

	$rs = $db->GetAll($sql);

	if (isset($rs) && !empty($rs))
	{
		//include limesurvey functions
		include_once(dirname(__FILE__).'/functions.limesurvey.php');

		//update all row quotas for this questionnaire
		foreach($rs as $r)
		{
			$completions = limesurvey_quota_completions($r['lime_sgqa'],$r['lime_sid'],$r['questionnaire_id'],$r['sample_import_id'],$r['value'],$r['comparison']);
			if ($completions > 0)
			{
				//Update adjusting the completion number
				$sql = "UPDATE questionnaire_sample_quota_row
					SET completions = IF(completions>".$completions.",(completions-".$completions."),0), quota_reached = IF(quota_reached = 0,IF(completions=0,1,0),1)
					WHERE questionnaire_id = '".$questionnaire_id."'
                    AND sample_import_id='".$copy_sample_import_id."'
                    AND lime_sgqa='".$r['lime_sgqa']."'
                    AND value='".$r['value']."'
                    AND comparison='".$r['comparison']."'";

				$db->Execute($sql);
			}

		}
	}

  $db->CompleteTrans();
   */
}

/**
 * Update a single row quota
 *
 * @param int $qsqri The quota row id
 * @param int|bool $case_id The case id if known to limit the scope of the search
 * @return bool If priorities need to be updated or not
 */
function update_single_row_quota($qsqri,$case_id = false)
{
  global $db;

  $sql = "SELECT q.lime_sid, qs.questionnaire_id, qs.sample_import_id, qs.completions, qs.autoprioritise
          FROM questionnaire as q, questionnaire_sample_quota_row as qs
          WHERE q.questionnaire_id = qs.questionnaire_id
          AND qs.questionnaire_sample_quota_row_id = $qsqri";

  $rs = $db->GetRow($sql);

  $lime_sid = $rs['lime_sid'];
  $questionnaire_id = $rs['questionnaire_id'];
  $sample_import_id = $rs['sample_import_id'];
  $target_completions = $rs['completions'];
  $autoprioritise = $rs['autoprioritise'];

  //all variables to exclude for this row quota
  $sql2 = "SELECT exclude_val,exclude_var,exclude_var_id,comparison
           FROM qsqr_sample
           WHERE questionnaire_sample_quota_row_id = $qsqri
           AND exclude_var_id > 0";

  $rev = $db->GetAll($sql2);

  //all variables to check in limesurvey for this row quota
  $sql2 = "SELECT lime_sgqa,value,comparison
           FROM qsqr_question
           WHERE questionnaire_sample_quota_row_id = $qsqri";

  $qev = $db->GetAll($sql2);

  //whether a completion was changed for this quota
  $updatequota = false;
  //whether priorites need to be updated
  $update = false;
  //default completions at 0
  $completions = 0;

  //if a case_Id is specified, we can just check if this case matches
  //the quota criteria, and if so, increment the quota completions counter
  if ($case_id != false)
  {
    if (empty($qev))
    {
      //just determine if this case is linked to a matching sample record
      $sql2 = "SELECT count(*) as c
               FROM " . LIME_PREFIX . "survey_$lime_sid as s
               JOIN `case` as c ON (c.case_id = '$case_id')
               JOIN `sample` as sam ON (c.sample_id = sam.sample_id) ";
      
      $x = 1;
      foreach($rev as $ev)
      {
        $sql2 .= " JOIN sample_var as sv$x ON (sv$x.sample_id = sam.sample_id AND sv$x.var_id = '{$ev['exclude_var_id']}' AND sv$x.val {$ev['comparison']} '{$ev['exclude_val']}') ";
        $x++;
      }

      $sql2 .= " WHERE s.token = c.token";

      $match = $db->GetOne($sql2); 
    }
    else
    {
      //determine if the case is linked to a matching limesurvey record
      $sql2 = "SELECT count(*) as c
               FROM " . LIME_PREFIX . "survey_$lime_sid as s
               JOIN `case` as c ON (c.case_id = '$case_id')
               JOIN `sample` as sam ON (c.sample_id = sam.sample_id)
               WHERE s.token = c.token
               ";

      foreach($qev as $ev)
        $sql2 .= " AND s.`{$ev['lime_sgqa']}` {$ev['comparison']} '{$ev['value']}' ";

      $match = $db->GetOne($sql2);
    }

    if ($match == 1)
    {
      //increment completions
      $sql = "SELECT (current_completions + 1) as c
        FROM questionnaire_sample_quota_row
        WHERE questionnaire_sample_quota_row_id = '$qsqri'";
      $cc = $db->GetRow($sql);

      $completions = $cc['c'];
      
      $updatequota = true;
    }
  }
  else
  {
    if (empty($qev))
    {
      //find all completions from cases with matching sample records

      $sql2 = "SELECT count(*) as c
              FROM " . LIME_PREFIX . "survey_$lime_sid as s
              JOIN `case` as c ON (c.questionnaire_id = '$questionnaire_id')
              JOIN `sample` as sam ON (c.sample_id = sam.sample_id AND sam.import_id = '$sample_import_id')";

      $x = 1;
      foreach($rev as $ev)
      {
        $sql2 .= " JOIN sample_var as sv$x ON (sv$x.sample_id = sam.sample_id AND sv$x.var_id = '{$ev['exclude_var_id']}' AND sv$x.val {$ev['comparison']} '{$ev['exclude_val']}') ";
        $x++;
      }

      $sql2 .= "  WHERE s.submitdate IS NOT NULL
              AND s.token = c.token";

      $completions = $db->GetOne($sql2);
    }
    else
    {
      //find all completions from cases with matching limesurvey records
      $sql2 = "SELECT count(*) as c 
              FROM " . LIME_PREFIX . "survey_$lime_sid as s 
              JOIN `case` as c ON (c.questionnaire_id = '$questionnaire_id') 
              JOIN `sample` as sam ON (c.sample_id = sam.sample_id AND sam.import_id = '$sample_import_id') 
              WHERE s.submitdate IS NOT NULL 
              AND s.token = c.token ";

      foreach($qev as $ev)
        $sql2 .= " AND s.`{$ev['lime_sgqa']}` {$ev['comparison']} '{$ev['value']}' ";

       $completions = $db->GetOne($sql2);
    }

    $updatequota = true;
  }

  if ($updatequota)
  {
    if ($completions >= $target_completions)
    {
      //set row quota to reached
      $sql = "UPDATE questionnaire_sample_quota_row
        SET quota_reached = '1', current_completions = '$completions'
        WHERE questionnaire_sample_quota_row_id = '$qsqri'";

      $db->Execute($sql);

      close_row_quota($qsqri,false); //don't update priorires just yet
      $update = true;
    }
    else
    {
      $sql = "UPDATE questionnaire_sample_quota_row
        SET current_completions = '$completions' ";

      //If autopriority is set update it here
      if ($autoprioritise == 1)
      {
        //priority is 100 - the percentage of completions
        $pr = 100 - round(100 * ($completions / $target_completions));
        $sql .= ", priority = '$pr' ";				

        //need to update quotas now
        $update = true;
      }

      $sql .= " WHERE questionnaire_sample_quota_row_id = '$qsqri'";

      $db->Execute($sql);

   }
  }
  return $update;
}


/**
 * Update the row quota table
 *
 * @param int $questionnaire_id The questionnaire ID to update
 * @param int|bool $case_id The case id if known to limit the scope of the search
 */
function update_row_quota($questionnaire_id,$case_id = false)
{
	global $db;

	$update = false; //whether to update priorities (only if changed)

	$db->StartTrans();

	$sql = "SELECT qsq.questionnaire_sample_quota_row_id
		FROM questionnaire_sample_quota_row as qsq
		WHERE qsq.questionnaire_id = '$questionnaire_id'
    AND qsq.quota_reached != '1'
    GROUP BY qsq.questionnaire_sample_quota_row_id";

	$rs = $db->GetAll($sql);

  if (isset($rs) && !empty($rs))
  {
    foreach ($rs as $r)
    {
      $tmp = update_single_row_quota($r['questionnaire_sample_quota_row_id'],$case_id);
      if ($tmp) 
        $update = true;
    }
  }

  if ($update) 
    update_quota_priorities($questionnaire_id);
  
	$db->CompleteTrans();

	return false;
}

/**
 * Check if any quotas apply to this questionnaire
 * and if so, update them
 *
 * @param int $questionnaire_id The questionnaire id
 * @param int $case_id The case id if specified
 *
 */
function update_quotas($questionnaire_id,$case_id = false)
{
	update_quota($questionnaire_id);
	update_row_quota($questionnaire_id,$case_id);
}

/**
 * Update quota priorities and exclusion table
 *
 * @param int $questionnaire_id The questionnaire_id to update priorities for
 *
 */
function update_quota_priorities($questionnaire_id)
{
	global $db;

	//update questionnaire_sample_exclude_priority table to have records for each q and s
	//set exclude == 1 where records exist in qsqre table
	//placeholder to set priorities also

	$db->StartTrans();

	$sql = "INSERT INTO questionnaire_sample_exclude_priority (questionnaire_id,sample_id,exclude,priority)
		SELECT '$questionnaire_id', s.sample_id, 0, 50
		FROM `sample` as s, questionnaire_sample as qs
		WHERE qs.questionnaire_id = '$questionnaire_id'
		AND s.import_id = qs.sample_import_id
		ON DUPLICATE KEY UPDATE exclude = 0, priority = 50";

	$db->Execute($sql);

	if ($db->HasFailedTrans()) die ($sql);

	//Update the priority record

	//Select all quota rows that are open, and have a priority != 50
	$sql = "SELECT questionnaire_sample_quota_row_id, priority
		FROM questionnaire_sample_quota_row
		WHERE questionnaire_id = '$questionnaire_id'
		AND quota_reached = 0
		AND priority != 50
		ORDER BY priority ASC";

	$rs = $db->GetAll($sql);

	if ($db->HasFailedTrans()) die ($sql);

	if (!empty($rs)) foreach ($rs as $r)
	{
		$qsqri = $r['questionnaire_sample_quota_row_id'];
		$priority = $r['priority'];

		$sql2 = "SELECT exclude_val,exclude_var,exclude_var_id,comparison
             FROM qsqr_sample
             WHERE questionnaire_sample_quota_row_id = $qsqri
             AND exclude_var_id > 0";

		$rev = $db->GetAll($sql2);

		//find all cases that match this quota, and update it to the new priority
		$sql = "UPDATE sample as s, questionnaire_sample_quota_row as qs, questionnaire_sample_exclude_priority as qsep ";

		//reduce sample by every item in the qsqr_sample table
		$x = 1;
		foreach ($rev as $ev)
		{
			$sql .= ", sample_var as sv$x";
			$x++;
		}

		$sql .= " SET qsep.priority = '$priority'
			WHERE s.import_id = qs.sample_import_id
			AND qs.questionnaire_sample_quota_row_id = '$qsqri'
			AND qsep.questionnaire_id = qs.questionnaire_id
			AND qsep.sample_id = s.sample_id ";
			
		$x = 1;
		foreach ($rev as $ev)
		{
		$sql .= " AND sv$x.sample_id = s.sample_id AND sv$x.var_id = '{$ev['exclude_var_id']}' AND sv$x.val {$ev['comparison']} '{$ev['exclude_val']}' ";
		$x++;
		}

		$db->Execute($sql);

		if ($db->HasFailedTrans()) die ($sql);			
	}


	//Update the exclusion record to be 1 where exists in the qsqre table

	$sql = "SELECT questionnaire_id,sample_id
		FROM questionnaire_sample_quota_row_exclude
		WHERE questionnaire_id = '$questionnaire_id'
		GROUP BY questionnaire_id,sample_id";

	$rs = $db->GetAll($sql);

	if ($db->HasFailedTrans()) die ($sql);

	foreach($rs as $r)
	{
		$q = $r['questionnaire_id'];
		$s = $r['sample_id'];

		$sql = "UPDATE questionnaire_sample_exclude_priority
			SET exclude = 1
			WHERE questionnaire_id = '$q'
			AND sample_id = '$s'";

		$db->Execute($sql);


		if ($db->HasFailedTrans()) die ($sql);
	}

	$db->CompleteTrans();
}


/**
 * End the current case
 *
 * @param int $operator_id The operator to end the case for
 *
 * @see get_case()
 */
function end_case($operator_id)
{
	global $db;

	$db->StartTrans();

	$case_id = get_case_id($operator_id,false);
	$questionnaire_id = get_questionnaire_id($operator_id);

	$return = false;

	if ($case_id)
	{
		//End all calls (with not attempted or worked if there is a call);
		end_call($operator_id,1);

		//Make sure to end call attempts
		end_call_attempt($operator_id);

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
			AND (o.outcome_type_id = 4)
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
					AND o.outcome_id != 18
					ORDER BY c.call_id DESC
					LIMIT 1";
			
				$t = $db->GetRow($sql);
		
				if (!empty($t))
				{	
					$outcome = $t['outcome_id'];
				}
			}
			else if ($count >= 1) //one or more numbers to be tried again - first code as eligible if ever eligible...
			{
				//$r[0]['contact_phone_id'];
				//code as eligible if ever eligible, or if referred to the supervisor, code as that if last call
				$sql = "SELECT c.outcome_id as outcome_id
					FROM `call` as c
					JOIN outcome AS o ON ( c.outcome_id = o.outcome_id AND (o.eligible = 1 OR o.outcome_type_id = 2 OR o.outcome_type_id = 1) )
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

			//if the outcome is complete, then update the quota's for this questionnaire (if any)
			if ($outcome == 10)
				update_quotas($questionnaire_id,$case_id);
		}
		
		$sql = "UPDATE `case`
			SET current_operator_id = NULL, current_call_id = NULL, sortorder = NULL, current_outcome_id = '$outcome', last_call_id = '$lastcall'
			WHERE case_id = '$case_id'";

		$o = $db->Execute($sql);

		$return = true;
	}
	else
		$return = false;

	//if ($db->HasFailedTrans()) { print "FAILED in end_case"; exit; }
	if ($db->CompleteTrans())
		return $return;

	return false;
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
		return T_($r['description']);
	else
		return "";

}


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

	$db->StartTrans();

	$return = false;

	$ca = get_call_attempt($operator_id,false);

	if ($ca)
	{
		$sql = "UPDATE `call_attempt`
			SET end = CONVERT_TZ(NOW(),'System','UTC')
			WHERE call_attempt_id = '$ca'";

		$o = $db->Execute($sql);

		$return = true;
	}
	
	//if ($db->HasFailedTrans()) { print "FAILED in end_call_attempt"; exit; }
	if ($db->CompleteTrans())
		return $return;

	return false;
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
 * @param bool $create If true, will create a call attempt if none exists
 * @return bool|int False if no case otherwise the call_attempt_id
 *
 */
function get_call_attempt($operator_id,$create = false)
{
	global $db;

	$db->StartTrans();
	
	$case_id = get_case_id($operator_id,false);

	$id = false;

	if ($case_id)
	{
		$sql = "SELECT call_attempt_id
			FROM `call_attempt`
			WHERE case_id = '$case_id'
			AND operator_id = '$operator_id'
			AND end IS NULL";

		$row = $db->GetRow($sql);

		/**
		 * If no call_attempt, create one
		 */
		if (empty($row))
		{
			if ($create)
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
		}
		else
		{
			$id = $row['call_attempt_id'];
		}
	}

	//if ($db->HasFailedTrans()) { print "FAILED in get_call_attempt"; exit; }
	if ($db->CompleteTrans())
		return $id;
	
	return false;
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

	$db->StartTrans();

	$o = false;

	$ca = get_call($operator_id);

	if ($ca)
	{
		$c = get_call_attempt($operator_id,false);
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

		$db->Execute($sql);
	}

	//if ($db->HasFailedTrans()) { print "FAILED in end_call"; exit; }
	if ($db->CompleteTrans())
		return $o;

	return false;
}

/**
 * Get the limesurvey "survey id" of the current questionnaire assigned to the operator
 *
 * @param int $operator_id The operator
 * @param bool $rs If asking for respondent selection
 * @return bool|int False if none found else the limesurvey sid
 *
 *
 */
function get_limesurvey_id($operator_id,$rs = false)
{
	global $db;

	if ($rs)
		$sql = "SELECT q.lime_rs_sid as sid";
	else
		$sql = "SELECT q.lime_sid as sid";

	$sql .= " FROM `case` as c, questionnaire_sample as qs, sample as s, questionnaire as q
		WHERE c.current_operator_id = '$operator_id'
		AND c.sample_id = s.sample_id
		AND s.import_id = qs.sample_import_id
		AND q.questionnaire_id = qs.questionnaire_id
		AND c.questionnaire_id = q.questionnaire_id";

	$rs = $db->GetRow($sql);

	if (empty($rs))
		return false;

	return $rs['sid'];

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
