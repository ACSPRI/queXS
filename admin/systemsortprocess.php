<?
/**
 * Run the system wide case sorting process
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2011
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXS was written for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include (dirname(__FILE__) . "/../config.inc.php");

/**
 * Database file
 */
include (dirname(__FILE__) . "/../db.inc.php");

/**
 * Process
 */
include (dirname(__FILE__) . "/../functions/functions.process.php");

/**
 * Update the database with the new data from the running script
 *
 * @param string $buffer The data to append to the database
 * @return string Return a blank string to empty the buffer
 */
function update_callback($buffer)
{
	global $process_id;

	process_append_data($process_id,"<p>" . $buffer . "</p>");

	return ""; //empty buffer
}

/**
 * Disable system sort on shutdown
 * 
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2011-01-31
 */
function disable_systemsort()
{
	set_setting('systemsort',false);
}


//get the arguments from the command line (this process_id)
if ($argc != 2) exit();

$process_id = $argv[1];

//register an exit function which will tell the database we have ended
register_shutdown_function('end_process',$process_id);
register_shutdown_function('disable_systemsort');

//all output send to database instead of stdout
ob_start('update_callback',2);

print T_("Sorting cases process starting");

$sleepinterval = 10; // in seconds so we can monitor if the process has been killed

while (!is_process_killed($process_id)) //check if process killed every $sleepinterval
{
	//Make sure that the system knows we are system sorting 
	set_setting('systemsort',true);	

	print date("Y-m-d H:i") . " : " .  T_("Sorting cases");

	$time_start = microtime(true);

	$db->StartTrans();

	//First set all cases as unavailable
	$sql = "UPDATE `case`
		SET sortorder = NULL
		WHERE 1";

	$db->Execute($sql);


	//Sort current cases for all enabled questionnaires
	
	
	$sql = "SELECT c.case_id
		FROM `case`  as c
		LEFT JOIN `call` as a on (a.call_id = c.last_call_id)
		JOIN (sample as s, sample_import as si) on (s.sample_id = c.sample_id and si.sample_import_id = s.import_id)
		JOIN (questionnaire_sample as qs, questionnaire as q,  outcome as ou) on (c.questionnaire_id = q.questionnaire_id and qs.sample_import_id = s.import_id and ou.outcome_id = c.current_outcome_id and q.enabled = 1 and qs.questionnaire_id = c.questionnaire_id)
		LEFT JOIN shift as sh on (sh.questionnaire_id = q.questionnaire_id and (CONVERT_TZ(NOW(),'System','UTC') >= sh.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= sh.end))
		LEFT JOIN appointment as ap on (ap.case_id = c.case_id AND ap.completed_call_id is NULL AND (ap.start > CONVERT_TZ(NOW(),'System','UTC')))
		LEFT JOIN appointment as apn on (apn.case_id = c.case_id AND apn.completed_call_id is NULL AND (CONVERT_TZ(NOW(),'System','UTC') >= apn.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= apn.end))
		LEFT JOIN call_restrict as cr on (cr.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= cr.start and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= cr.end)
		LEFT JOIN questionnaire_sample_exclude_priority AS qsep ON (qsep.questionnaire_id = c.questionnaire_id AND qsep.sample_id = c.sample_id)
		WHERE c.current_operator_id IS NULL
		AND (a.call_id is NULL or (a.end < CONVERT_TZ(DATE_SUB(NOW(), INTERVAL ou.default_delay_minutes MINUTE),'System','UTC')))
		AND ap.case_id is NULL
		AND ((qsep.questionnaire_id is NULL) or qsep.exclude = 0)
		AND !(q.restrict_work_shifts = 1 AND sh.shift_id IS NULL)
		AND !(si.call_restrict = 1 AND cr.day_of_week IS NULL)
		AND ((apn.appointment_id IS NOT NULL) or qs.call_attempt_max = 0 or ((SELECT count(*) FROM call_attempt WHERE case_id = c.case_id) < qs.call_attempt_max))
		AND ((apn.appointment_id IS NOT NULL) or qs.call_max = 0 or ((SELECT count(*) FROM `call` WHERE case_id = c.case_id) < qs.call_max))
	AND (SELECT count(*) FROM `questionnaire_sample_quota` WHERE questionnaire_id = c.questionnaire_id AND sample_import_id = s.import_id AND quota_reached = 1) = 0
		GROUP BY c.case_id
		ORDER BY apn.start DESC, a.start ASC, qsep.priority DESC";
	
	$rs = $db->GetAll($sql);

	$i = 1;
	foreach ($rs as $r)
	{
		$sql = "UPDATE `case`
			SET sortorder = '$i'
			WHERE case_id = '{$r['case_id']}'";

		$db->Execute($sql);
		$i++;
	}
	

	//First set all sample records as unavailable
	$sql = "UPDATE `questionnaire_sample_exclude_priority`
		SET sortorder = NULL
		WHERE 1";

	$db->Execute($sql);



	//Sort sample list where attached to an enabled questionnaire

	$sql = "SELECT s.sample_id as sample_id,qs.questionnaire_id as questionnaire_id
		FROM sample as s
		JOIN (questionnaire_sample as qs, questionnaire as q, sample_import as si) on (qs.sample_import_id = s.import_id and si.sample_import_id = s.import_id and q.questionnaire_id = qs.questionnaire_id AND q.enabled = 1)
		LEFT JOIN `case` as c on (c.sample_id = s.sample_id and c.questionnaire_id = qs.questionnaire_id)
		LEFT JOIN call_restrict as cr on (cr.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= cr.start and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= cr.end)
		LEFT JOIN shift as sh on (sh.questionnaire_id = q.questionnaire_id and (CONVERT_TZ(NOW(),'System','UTC') >= sh.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= sh.end))
		LEFT JOIN questionnaire_sample_exclude_priority AS qsep ON (qsep.questionnaire_id = qs.questionnaire_id AND qsep.sample_id = s.sample_id)
		WHERE c.case_id is NULL
		AND ((qsep.questionnaire_id IS NULL) or qsep.exclude = 0)
		AND !(q.restrict_work_shifts = 1 AND sh.shift_id IS NULL)
		AND !(si.call_restrict = 1 AND cr.day_of_week IS NULL)
		AND (SELECT count(*) FROM `questionnaire_sample_quota` WHERE questionnaire_id = qs.questionnaire_id AND sample_import_id = s.import_id AND quota_reached = 1) = 0
		GROUP BY s.sample_id,qs.questionnaire_id
		ORDER BY qsep.priority DESC, rand() * qs.random_select, s.sample_id";
				
	$rs = $db->GetAll($sql);

	$i = 1;
	foreach ($rs as $r)
	{
	        $sql = "INSERT INTO questionnaire_sample_exclude_priority (questionnaire_id,sample_id,exclude,priority,sortorder)
	                VALUES ('{$r['questionnaire_id']}', '{$r['sample_id']}', 0, 50,'$i')
	                ON DUPLICATE KEY UPDATE sortorder = '$i'";

		$db->Execute($sql);
		$i++;
	}
	
	


	$result = $db->CompleteTrans();
	
	$time_end = microtime(true);
	$timer = $time_end - $time_start;

	if ($result)
		print T_("Completed sort") . ". " . T_("This task took") . ": $timer " . T_("seconds");
	else
		print T_("Failed to complete sort") . ". " . T_("This task took") . ": $timer " . T_("seconds");

	for ($i = 0; $i < (SYSTEM_SORT_MINUTES * 60); $i += $sleepinterval)
	{
		if (is_process_killed($process_id)){break;}
		sleep($sleepinterval);
	}
}

disable_systemsort();

ob_get_contents();
ob_end_clean();

?>
