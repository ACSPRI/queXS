<?
/**
 * Functions for interacting with queXS
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
 */

/**
 * Configuration file
 */
require_once(dirname(__FILE__).'/../../config.inc.php');

/**
 * Return the phone number of the latest appointment for this respondent
 *
 * @param int $respondent_id The respondent id
 * @return string The phone number
 */
function get_appointment_number($respondent_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT CONVERT_TZ(a.start,'UTC',r.Time_zone_name) as start, CONVERT_TZ(a.end,'UTC',r.Time_zone_name) as end, DATE(CONVERT_TZ(a.start,'UTC',r.Time_zone_name)) as startdate, c.phone
		FROM appointment as a
		JOIN (contact_phone as c, respondent as r) on (a.contact_phone_id = c.contact_phone_id AND r.respondent_id = '$respondent_id')
		WHERE a.respondent_id = '$respondent_id'
		ORDER BY a.appointment_id DESC";
		
	$rs = $db->GetRow($sql); //Get the last one only

	if (!empty($rs))
		return $rs['phone'];

	return "";
}
	

/**
 * Return the time of the latest appointment for this respondent
 *
 * @param int $respondent_id The respondent id
 * @return string The time
 */
function get_appointment_time($respondent_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT DATE_FORMAT(CONVERT_TZ(a.start,'UTC',r.Time_zone_name),'".TIME_FORMAT."') as start
		FROM appointment as a
		JOIN (contact_phone as c, respondent as r) on (a.contact_phone_id = c.contact_phone_id AND r.respondent_id = '$respondent_id')
		WHERE a.respondent_id = '$respondent_id'
		ORDER BY a.appointment_id DESC";
		
	$rs = $db->GetRow($sql); //Get the last one only

	if (!empty($rs))
		return $rs['start'];

	return "";
}
	

/**
 * Return the date of the latest appointment for this respondent
 *
 * @param int $respondent_id The respondent id
 * @return string The date
 */
function get_appointment_date($respondent_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT DATE_FORMAT(CONVERT_TZ(a.start,'UTC',r.Time_zone_name),'".DATE_FORMAT."') as startdate, c.phone
		FROM appointment as a
		JOIN (contact_phone as c, respondent as r) on (a.contact_phone_id = c.contact_phone_id AND r.respondent_id = '$respondent_id')
		WHERE a.respondent_id = '$respondent_id'
		ORDER BY a.appointment_id DESC";
		
	$rs = $db->GetRow($sql); //Get the last one only

	if (!empty($rs))
		return $rs['startdate'];

	return "";
}
	


/**
 * Return the period of the day for the respondent
 *
 * @param int $respondent_id The respondent id
 * @return string Either morning, afternoon or evening based on the respondents time zone
 * @todo internationalise text : limesurvey or quexs?
 *
 */
function get_period_of_day($respondent_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);

	$sql = "SELECT HOUR(CONVERT_TZ(NOW(),'System',Time_zone_name)) as h
		FROM respondent
		WHERE respondent_id = '$respondent_id'";

	$rs = $db->GetRow($sql);

	$hour = 10;
	if (!empty($rs))
		$hour = $rs['h'];

	if ($hour < 12) return "morning";
	if ($hour < 17) return "afternoon";
	return "evening";
}


/**
 * Update the sample record from data entered in limesurvey form
 *
 * @param int $lime_sid The limesurvey survey id 
 * @param int $id The limesurvey record id of the data
 * @param array $postedfieldnames An array containing the fields that were just posted by limesurvey
 *
 */
function quexs_update_sample($lime_sid,$id,$postedfieldnames)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);

	//Search over the questionnaire database to find multiple short text responses which reference the sample
	$sql = "SELECT lq.sid,lq.gid,lq.qid,la.code,SUBSTR(la.answer,15,CHAR_LENGTH(la.answer)-15) as answer
                FROM `lime_questions` AS lq
                JOIN `lime_answers` AS la ON ( la.qid = lq.qid )
                WHERE lq.sid = '$lime_sid'
		AND lq.type = 'Q' 
		AND (";

	foreach($postedfieldnames as $pf) //restrict to only the ones just updated
	{
		$sql .= " CONCAT(lq.sid, 'X', lq.gid, 'X', lq.qid, la.code) LIKE '$pf' OR ";
	}

	$sql = substr($sql,0,-4);
	$sql .= ") AND la.answer LIKE '{SAMPLEUPDATE:%'";

	$rs = $db->GetAll($sql);

	$db->StartTrans();

	if (!empty($rs))
	{
		$operator_id = get_operator_id();
		$case_id = get_case_id($operator_id);
	
		if ($case_id)
		{
			$sql = "SELECT sample_id
				FROM `case`
				WHERE case_id = '$case_id'";
	
			$c = $db->GetRow($sql);
	
			$sample_id = $c['sample_id'];
	
			foreach($rs as $r) //Update the queXS sample database to reflect the updated data
			{
				$sgqa = $r['sid'] . 'X' . $r['gid'] . 'X' . $r['qid'] . $r['code'];
				$var = $r['answer'];
		
				$sql = "UPDATE sample_var as sv, ".LIME_PREFIX."survey_$lime_sid as ld
					SET sv.val = ld.$sgqa
					WHERE sv.var LIKE '$var'
					AND sv.sample_id = '$sample_id'
					AND ld.id = '$id'";

				$db->Execute($sql);
			}
		}
	}

	$db->CompleteTrans();
}

/**
 * Get the number of call attempts for this case
 *
 * @param $case_id The case id
 * @return int The number of call attempts
 */
function get_call_attempts($case_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT COUNT(call_attempt_id) AS c
		FROM call_attempt 
		WHERE case_id = '$case_id'";

	$rs = $db->GetRow($sql);


	if (empty($rs)) return "";

	return $rs['c'];

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
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT s.val as r
		FROM sample_var as s
		JOIN `case` as c on (c.case_id = '$case_id' and s.sample_id = c.sample_id)
		WHERE s.var = '$variable'";

	$rs = $db->GetRow($sql);


	if (empty($rs)) return "";

	return $rs['r'];

}

/**
 * Get the outcome code description
 *
 * @param string $variable The bit of information about the operator (eg firstName)
 * @param int $operator_id The operator id
 * @return string The information or a blank string if none found
 *
 */
function get_outcome_variable($variable)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT description as r
		FROM outcome
		WHERE outcome_id = '$variable'";

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
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


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
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT `$variable` as r
		FROM respondent
		WHERE respondent_id = '$respondent_id'";

	$rs = $db->GetRow($sql);


	if (empty($rs)) return "";

	return $rs['r'];

}

/**
 * Return the current operator id based on PHP_AUTH_USER
 *
 * @return bool|int False if none otherwise the operator id
 *
 */
function get_operator_id()
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT operator_id
		FROM operator
		WHERE username = '{$_SERVER['PHP_AUTH_USER']}'
		AND enabled = 1";

	$o = $db->GetRow($sql);

	if (empty($o)) 	return false;

	return $o['operator_id'];

}



/**
 * Get the current case id
 *
 * @param int $operator_id The operator id
 * @param bool $create True if a case can be created
 * @return bool|int False if no case available else the case_id
 */
function get_case_id($operator_id)
{

	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);
	

	/**
	 * See if case already assigned
	 */
	$sql = "SELECT case_id
		FROM `case`
		WHERE current_operator_id = '$operator_id'";

	$r1 = $db->GetRow($sql);

	if (!empty($r1) && isset($r1['case_id'])) return $r1['case_id'];
	return false;
}

/**
 * Get the respondent id
 *
 *
 */
function get_respondent_id($case_id,$operator_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT respondent_id
		FROM `call_attempt`
		WHERE case_id = '$case_id'
		AND operator_id = '$operator_id'
		AND end IS NULL";

	$row = $db->GetRow($sql);

	if (!empty($row) && isset($row['respondent_id'])) return $row['respondent_id'];
	return false;
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
function quexs_template_replace($string)
{
	$operator_id = get_operator_id();
	$case_id = get_case_id($operator_id);
	$respondent_id = get_respondent_id($case_id,$operator_id);

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

	while (stripos($string, "{Outcome:") !== false)
	{
		$answreplace=substr($string, stripos($string, "{Outcome:"), stripos($string, "}", stripos($string, "{Outcome:"))-stripos($string, "{Outcome:")+1);
		$answreplace2=substr($answreplace, 9, stripos($answreplace, "}", stripos($answreplace, "{Outcome:"))-9);
		$answreplace3=get_outcome_variable($answreplace2);
		$string=str_replace($answreplace, $answreplace3, $string);
	}

	while (stripos($string, "{CALLATTEMPTS}") !== false)
	{
		$call_attempts = get_call_attempts($case_id);
		$string=str_ireplace("{CALLATTEMPTS}", $call_attempts, $string);
	}

	while (stripos($string, "{ONAPPOINTMENT}") !== false)
	{
		$on_appointment = is_on_appointment($case_id,$operator_id);
		//todo: These must be internationalised, but I think I need to use Limesurveys so as not to conflict?
		$str = "Not on an appointment";
		if ($on_appointment)
			$str = "On an appointment";
		$string=str_ireplace("{ONAPPOINTMENT}", $str, $string);
	}

	if (stripos($string, "{PERIODOFDAY}") !== false) $string=str_ireplace("{PERIODOFDAY}", get_period_of_day($respondent_id), $string);
	if (stripos($string, "{APPOINTMENTDATE}") !== false) $string=str_ireplace("{APPOINTMENTDATE}", get_appointment_date($respondent_id), $string);
	if (stripos($string, "{APPOINTMENTTIME}") !== false) $string=str_ireplace("{APPOINTMENTTIME}", get_appointment_time($respondent_id), $string);
	if (stripos($string, "{APPOINTMENTNUMBER}") !== false) $string=str_ireplace("{APPOINTMENTNUMBER}", get_appointment_number($respondent_id), $string);

	return $string;
}

/**
 * Get the limesurvey "survey id" of the current questionnaire assigned to the operator
 *
 * @param int $operator_id The operator
 * @return bool|int False if none found else the limesurvey sid
 *
 */
function get_limesurvey_id($operator_id)
{
        $db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);

        $sql = "SELECT q.lime_sid  as lime_sid
                FROM `case` as c, questionnaire_sample as qs, sample as s, questionnaire as q
                WHERE c.current_operator_id = '$operator_id'
                AND c.sample_id = s.sample_id
                AND s.import_id = qs.sample_import_id
                AND q.questionnaire_id = qs.questionnaire_id
                AND c.questionnaire_id = q.questionnaire_id";

        $rs = $db->GetRow($sql);

        if (empty($rs))
                return false;

        return $rs['lime_sid'];

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
        $db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


        $sql = "SELECT questionnaire_id
                FROM `case` as c
                WHERE c.current_operator_id = '$operator_id'";

        $rs = $db->GetRow($sql);

        if (empty($rs)) return false;

        return $rs['questionnaire_id'];

}


/**
 * Get start interviewer URL
 *
 * @return string The URL to start the interview
 */
function get_start_interview_url()
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);

	$operator_id = get_operator_id();

        $url = QUEXS_URL . "nocaseavailable.php";

        $case_id = get_case_id($operator_id);

        if ($case_id)
        {
                $sid = get_limesurvey_id($operator_id);
                $url = LIME_URL . "index.php?loadall=reload&sid=$sid&token=$case_id&lang=" . DEFAULT_LOCALE;
                $questionnaire_id = get_questionnaire_id($operator_id);
                
                //get prefills
                $sql = "SELECT lime_sgqa,value
                        FROM questionnaire_prefill
                        WHERE questionnaire_id = '$questionnaire_id'";
                $pf = $db->GetAll($sql);
        
                if (!empty($pf))
                {
                        foreach ($pf as $p)
                                $url .= "&" . $p['lime_sgqa'] . "=" . quexs_template_replace($p['value']);
                }
        }

        //if ($db->HasFailedTrans()) { print "FAILED in get_limesurvey_url"; exit; }
        $db->CompleteTrans();

        return $url;
	

}


/**
 * Return 1 if this operator is on an appointment
 *
 * @param int $case_id The case id
 * @param int $operator_id The operator id
 * @return int 0 if not on appointment 1 if they are
 *
 */
function is_on_appointment($case_id,$operator_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);

        $sql = "SELECT a.appointment_id
                FROM call_attempt as ca
                LEFT JOIN appointment as a on (a.case_id = ca.case_id and (ca.start >= a.start and ca.start <= a.end) and a.completed_call_id is NULL)
                WHERE ca.case_id = '$case_id'
		AND ca.operator_id = '$operator_id'
		AND ca.end IS NULL";

        $a = $db->GetRow($sql);

        if (empty($a) || empty($a['appointment_id']))
                return 0;
        else
                return 1;

}


?>
