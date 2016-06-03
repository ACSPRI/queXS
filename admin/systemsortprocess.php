<?php 
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
 * Operator functions (for quotas)
 */
include (dirname(__FILE__) . "/../functions/functions.operator.php");

/**
 * Update the database with the new data from the running script
 *
 * @param string $buffer The data to append to the database
 * @return string Return a blank string to empty the buffer
 */
function update_callback($buffer)
{
	global $process_id;

	process_append_data($process_id,$buffer);

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

$closecasescounter = 0;

print T_("Sorting cases process starting");

$closecasesinterval = (24 * 60) / SYSTEM_SORT_MINUTES; //check for closed cases once every day
$sleepinterval = 10; // in seconds so we can monitor if the process has been killed

while (!is_process_killed($process_id)) //check if process killed every $sleepinterval
{
	//Make sure that the system knows we are system sorting 
	set_setting('systemsort',true);	

  if ($closecasescounter == 0 || $closecasescounter > $closecasesinterval)
  {
	  $time_start = microtime(true);
    print T_("Checking for cases open for more than 24 hours");

    $closecasescounter = 0;
  	$db->StartTrans();

  	//find all call attempts without an end that started more than 24 hours ago
  
  	$sql = "SELECT case_id, call_attempt_id
  		FROM `call_attempt` 
  		WHERE TIMESTAMPDIFF(HOUR, start, CONVERT_TZ(NOW(),'System','UTC')) > 24
  		AND end IS NULL";
  	
  	$rs = $db->GetAll($sql);
  	
  	foreach ($rs as $r)
  	{
  		//refer to supervisor if case still assigned
  	
  		$sql = "UPDATE `case`
  			SET current_operator_id = NULL, current_outcome_id = 5
  			WHERE case_id = '{$r['case_id']}'
  			AND current_operator_id IS NOT NULL
  			AND current_call_id IS NULL";
  
  		$db->Execute($sql);
  
  		//add note

      $sql = "INSERT INTO case_note (case_id,operator_id,note,`datetime`)
        VALUES ('{$r['case_id']}',1,'" . TQ_("System automatically closed case as not closed for more than 24 hours") ."', CONVERT_TZ(NOW(),'System','UTC'))";

      $db->Execute($sql);
    
      //finish the call attempt
        
      $sql =	"UPDATE `call_attempt` 
         SET end = start
         WHERE call_attempt_id = '{$r['call_attempt_id']}'";

      $db->Execute($sql);

      print T_("System automatically closed case as not closed for more than 24 hours") . " - " . T_("Case id") . ": {$r['case_id']}";
    }
    
    //find all calls without an end that started more than 24 hours ago
    
    $sql = "SELECT case_id, call_id
      FROM `call` 
      WHERE TIMESTAMPDIFF(HOUR, start, CONVERT_TZ(NOW(),'System','UTC')) > 24
      AND end IS NULL";
    
    $rs = $db->GetAll($sql);
    
    foreach ($rs as $r)
    {
      //refer to supervisor if case still assigned
    
      $sql = "UPDATE `case`
        SET current_operator_id = NULL, current_outcome_id = 5, current_call_id = NULL
        WHERE case_id = '{$r['case_id']}'
        AND current_operator_id IS NOT NULL";

      $db->Execute($sql);

      //add note
    
      $sql = "INSERT INTO case_note (case_id,operator_id,note,`datetime`)
        VALUES ('{$r['case_id']}',1,'" . TQ_("System automatically closed case as not closed for more than 24 hours") ."', CONVERT_TZ(NOW(),'System','UTC'))";

      $db->Execute($sql);

      //finish the call 
        
      $sql =	"UPDATE `call` 
         SET end = start, outcome_id = 5, state = 5
         WHERE call_id = '{$r['call_id']}'";

      $db->Execute($sql);

      print T_("System automatically closed case as not closed for more than 24 hours") . " - " . T_("Case id") . ": {$r['case_id']}";
    }

    $result = $db->CompleteTrans();
  
    $time_end = microtime(true);
  	$timer = $time_end - $time_start;

	  if ($result)
		  print T_("Completed case closing") . ". " . T_("This task took") . ": $timer " . T_("seconds");
  	else
  		print T_("Failed to complete case closing") . ". " . T_("This task took") . ": $timer " . T_("seconds");
  }


  $closecasescounter++;

  //Sort cases on a questionnaire by questionnaire basis
	$sql = "SELECT questionnaire_id, description
		FROM questionnaire
		WHERE enabled = 1";

	$qs = $db->GetAll($sql);

	foreach($qs as $q)
  {
    print T_("Sorting cases for ") . $q['description'];

    $questionnaire_id = $q['questionnaire_id'];

  	$time_start = microtime(true);

    $db->StartTrans();


	//Delete all completed call attempts with no call in them
	$sql = "SELECT ca.call_attempt_id FROM call_attempt as ca 
		JOIN `case` as cs ON (cs.case_id = ca.case_id and cs.questionnaire_id = '$questionnaire_id')
		LEFT JOIN `call` as c ON (c.call_attempt_id = ca.call_attempt_id) 
		WHERE ca.end < CONVERT_TZ(NOW(),'System','UTC') 
		AND c.call_attempt_id IS NULL";

	$rs = $db->GetAll($sql);

	$cad = count($rs);

	if ($cad > 0)
	{
		foreach($rs as $r)
		{
			$sql = "DELETE from call_attempt 
				WHERE call_attempt_id = '{$r['call_attempt_id']}'";
			$db->Execute($sql);
		}
		print T_("Deleted") . " $cad " . T_("call attempts with no calls");
	}
	else
	{
		print T_("No call attempts without calls");
	}

  	//Set all cases as unavailable
  	$sql = "UPDATE `case`
  		SET sortorder = NULL
      WHERE sortorder IS NOT NULL
      AND questionnaire_id = '$questionnaire_id'";

  	$db->Execute($sql);


    //update quotas  
		update_quotas($questionnaire_id);	
	

  	//Sort current cases for this questionnaire
	
		$sql = "SELECT c.case_id
	  	FROM `case`  as c
  		LEFT JOIN `call` as a on (a.call_id = c.last_call_id)
  		JOIN (sample as s, sample_import as si) on (s.sample_id = c.sample_id and si.sample_import_id = s.import_id)
  		JOIN (questionnaire_sample as qs, questionnaire as q,  outcome as ou) on (c.questionnaire_id = q.questionnaire_id and qs.sample_import_id = s.import_id and ou.outcome_id = c.current_outcome_id and q.questionnaire_id = '$questionnaire_id' and qs.questionnaire_id = c.questionnaire_id)
  		LEFT JOIN shift as sh on (sh.questionnaire_id = q.questionnaire_id and (CONVERT_TZ(NOW(),'System','UTC') >= sh.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= sh.end))
  		LEFT JOIN appointment as ap on (ap.case_id = c.case_id AND ap.completed_call_id is NULL AND (ap.start > CONVERT_TZ(NOW(),'System','UTC')))
  		LEFT JOIN appointment as apn on (apn.case_id = c.case_id AND apn.completed_call_id is NULL AND (CONVERT_TZ(NOW(),'System','UTC') >= apn.start) AND (CONVERT_TZ(NOW(),'System','UTC') <= apn.end))
  		LEFT JOIN call_restrict as cr on (cr.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= cr.start and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= cr.end)
  		LEFT JOIN questionnaire_sample_exclude_priority AS qsep ON (qsep.questionnaire_id = c.questionnaire_id AND qsep.sample_id = c.sample_id)
  		LEFT JOIN case_availability AS casa ON (casa.case_id = c.case_id)
  		LEFT JOIN availability AS ava ON (ava.availability_group_id = casa.availability_group_id)
		LEFT JOIN questionnaire_timeslot AS qast ON (qast.questionnaire_id = c.questionnaire_id)
		LEFT JOIN questionnaire_sample_timeslot AS qasts ON (qasts.questionnaire_id = c.questionnaire_id AND qasts.sample_import_id = si.sample_import_id)
		WHERE c.current_operator_id IS NULL
		AND c.questionnaire_id = '$questionnaire_id'
  		AND ((apn.appointment_id IS NOT NULL) OR casa.case_id IS NULL OR (ava.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(),'System',s.Time_zone_name)) AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= ava.start AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= ava.end  ))
		
		AND ((apn.appointment_id IS NOT NULL) OR qast.questionnaire_id IS NULL OR ((SELECT COUNT(*) FROM availability WHERE availability.availability_group_id = qast.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(),'System',s.Time_zone_name)) AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= availability.end)) >= 1 AND (SELECT COUNT(call_attempt_id) FROM `call_attempt`, availability WHERE call_attempt.case_id = c.case_id AND (availability.availability_group_id = qast.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(call_attempt.start,'UTC',s.Time_zone_name)) AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) <= availability.end))) = (   SELECT (SELECT COUNT(*) FROM availability, call_attempt WHERE call_attempt.case_id = c.case_id AND availability.availability_group_id = availability_group.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(call_attempt.start,'UTC',s.Time_zone_name)) AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) <= availability.end))  as cou FROM availability_group, questionnaire_timeslot WHERE questionnaire_timeslot.questionnaire_id = c.questionnaire_id AND availability_group.availability_group_id = questionnaire_timeslot.availability_group_id ORDER BY cou ASC LIMIT 1)))
		
		AND ((apn.appointment_id IS NOT NULL) OR qasts.questionnaire_id IS NULL OR ((SELECT COUNT(*) FROM availability WHERE availability.availability_group_id = qasts.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(),'System',s.Time_zone_name)) AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= availability.end)) >= 1 AND (SELECT COUNT(call_attempt_id) FROM `call_attempt`, availability WHERE call_attempt.case_id = c.case_id AND (availability.availability_group_id = qasts.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(call_attempt.start,'UTC',s.Time_zone_name)) AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) <= availability.end))) = (   SELECT (SELECT COUNT(*) FROM availability, call_attempt WHERE call_attempt.case_id = c.case_id AND availability.availability_group_id = availability_group.availability_group_id AND (availability.day_of_week = DAYOFWEEK(CONVERT_TZ(call_attempt.start,'UTC',s.Time_zone_name)) AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) >= availability.start AND TIME(CONVERT_TZ(call_attempt.start, 'UTC' , s.Time_zone_name)) <= availability.end))  as cou FROM availability_group, questionnaire_sample_timeslot WHERE questionnaire_sample_timeslot.questionnaire_id = c.questionnaire_id AND questionnaire_sample_timeslot.sample_import_id = si.sample_import_id AND availability_group.availability_group_id = questionnaire_sample_timeslot.availability_group_id ORDER BY cou ASC LIMIT 1)))
		
  		AND (a.call_id is NULL or (a.end < CONVERT_TZ(DATE_SUB(NOW(), INTERVAL ou.default_delay_minutes MINUTE),'System','UTC')))
  		AND ap.case_id is NULL
  		AND ((qsep.questionnaire_id is NULL) or qsep.exclude = 0)
  		AND !(q.restrict_work_shifts = 1 AND sh.shift_id IS NULL)
  		AND !(si.call_restrict = 1 AND cr.day_of_week IS NULL)
  		AND ((apn.appointment_id IS NOT NULL) or qs.call_attempt_max = 0 or ((SELECT count(*) FROM call_attempt WHERE call_attempt.case_id = c.case_id) < qs.call_attempt_max))
  		AND ((apn.appointment_id IS NOT NULL) or qs.call_max = 0 or ((SELECT count(*) FROM `call` WHERE `call`.case_id = c.case_id) < qs.call_max))
    	AND (SELECT count(*) FROM `questionnaire_sample_quota` WHERE questionnaire_id = c.questionnaire_id AND sample_import_id = s.import_id AND quota_reached = 1) = 0
  		GROUP BY c.case_id
			ORDER BY IF(ISNULL(apn.end),1,0),apn.end ASC, qsep.priority DESC, CONVERT_TZ(NOW(), 'System' , s.Time_zone_name) DESC , a.start ASC, qs.sort_order ASC";
    
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
      WHERE sortorder IS NOT NULL
      AND questionnaire_id = '$questionnaire_id'";

    $db->Execute($sql);


    //Sort sample list where attached to this questionnaire

    $sql = "SELECT s.sample_id as sample_id,qs.questionnaire_id as questionnaire_id
      FROM sample as s
      JOIN (questionnaire_sample as qs, questionnaire as q, sample_import as si) on (qs.sample_import_id = s.import_id and si.sample_import_id = s.import_id and q.questionnaire_id = qs.questionnaire_id AND q.questionnaire_id = '$questionnaire_id' AND qs.allow_new = 1)
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
      ORDER BY qsep.priority DESC, rand() * qs.random_select, qs.sort_order ASC";
          
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
  }

	for ($i = 0; $i < (SYSTEM_SORT_MINUTES * 60); $i += $sleepinterval)
	{
		if (is_process_killed($process_id)){break;}
		sleep($sleepinterval);
	}

	process_clear_log();
}

disable_systemsort();

ob_get_contents();
ob_end_clean();

?>
