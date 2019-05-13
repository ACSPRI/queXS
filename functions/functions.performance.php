<?php 
/**
 * Functions that display data about the project
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
 *  Get completions per hour by shift with interviewer id and first name
 *
 *  @param int $qid The questionnaire ID
 *  @param int $sid The shift ID
 *  @return array An array containing operator_id,firstName,CPH
 */
function get_CPH_by_shift($qid,$sid)
{
	global $db;

	$sql = "SELECT o.firstName,o.operator_id,c.completions,ca.time as time, c.completions/(ca.time / 3600) as CPH
		FROM operator as o
		JOIN (  SELECT count(*) as completions,a.operator_id
			FROM `call` as a, `case` as b, `shift` as s
			WHERE a.outcome_id in (10,40)
			AND a.case_id = b.case_id
			AND b.questionnaire_id = '$qid'
			AND s.shift_id = '$sid'
			AND s.`start` <= a.`start`
			AND s.`end` >= a.`start`
			GROUP BY a.operator_id) as c on (c.operator_id = o.operator_id)
		JOIN (  SELECT SUM( TIMESTAMPDIFF(SECOND , a.start, IFNULL(a.end,a.start))) as time, a.operator_id
			FROM `call_attempt` as a, `case` as b, `shift` as s
			WHERE a.case_id = b.case_id
			AND b.questionnaire_id = '$qid'
			AND s.shift_id = '$sid'
			AND s.`start` <= a.`start`
			AND s.`end` >= a.`start`
			GROUP BY operator_id) as ca on (ca.operator_id = o.operator_id)
		WHERE o.enabled = 1
    ORDER BY cph DESC";

  $r = $db->GetAll($sql);

  foreach($r as $key => $value) {
    $r[$key]['time'] = sec_to_time($value['time']);
  }

  return $r;
}

/**
 *  Get completions per hour by questionnaire with interviewer id and first name
 *
 *  @param int $qid The questionnaire ID
 *  @return array An array containing operator_id,firstName,CPH
 */
function get_CPH_by_questionnaire($qid)
{
	global $db;

	$sql = "SELECT o.firstName,o.operator_id,c.completions,ca.time as time, c.completions/(ca.time / 3600) as CPH
		FROM operator as o
		JOIN (  SELECT count(*) as completions,a.operator_id
			FROM `call` as a, `case` as b
			WHERE a.outcome_id in (10,40)
			AND a.case_id = b.case_id
			AND b.questionnaire_id = '$qid'
			GROUP BY a.operator_id) as c on (c.operator_id = o.operator_id)
		JOIN (  SELECT SUM( TIMESTAMPDIFF(SECOND , a.start, IFNULL(a.end,a.start))) as time, a.operator_id
			FROM `call_attempt` as a, `case` as b
			WHERE a.case_id = b.case_id
			AND b.questionnaire_id = '$qid'
			GROUP BY operator_id) as ca on (ca.operator_id = o.operator_id)
		WHERE o.enabled = 1
		ORDER BY cph DESC";

  $r = $db->GetAll($sql);

  foreach($r as $key => $value) {
    $r[$key]['time'] = sec_to_time($value['time']);
  }

  return $r;
}

/**
 * Get stats by interviewer by questionnaire by shift
 *
 * @param int $questionnaire_id The questionnaire
 * @param int $shift_id The shift id
 * @return arrayh An array containing operator_id,firstName,CPH,effectiveness,
 */
function get_stats_by_shift($questionnaire_id,$shift_id)
{
	global $db;

	$sql = "SELECT o.operator_id, o.firstName, (calltime.totaltime / callattempttime.totaltime) AS effectiveness, c.completions,callattempttime.totaltime as time, c.completions/(callattempttime.totaltime / 3600) as CPH, calltime.totaltime as callt, callattempttime.totaltime as callatemptt, calls.calls as totalcalls, calls.calls/(callattempttime.totaltime / 3600) as CALLSPH, calltime.totaltime as calltotaltime, callattempttime.totaltime as callattempttotaltime

		FROM operator AS o
		JOIN (
			SELECT SUM( TIMESTAMPDIFF(
			SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id
			FROM `call` AS c, `case` as b, `shift` as s
			WHERE c.case_id = b.case_id
			AND b.questionnaire_id = '$questionnaire_id'
			AND s.shift_id = '$shift_id'
			AND s.`start` <= c.`start`
			AND s.`end` >= c.`start`
			GROUP BY operator_id
			) AS calltime ON ( calltime.operator_id = o.operator_id )
		JOIN (
			SELECT SUM( TIMESTAMPDIFF(
			SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id
			FROM `call_attempt` AS c, `case` as b, `shift` as s
			WHERE c.case_id = b.case_id
			AND s.shift_id = '$shift_id'
			AND s.`start` <= c.`start`
			AND s.`end` >= c.`start`
			AND b.questionnaire_id = '$questionnaire_id'
			GROUP BY operator_id
		) AS callattempttime ON ( callattempttime.operator_id = o.operator_id )
		JOIN (  SELECT count(*) as completions,a.operator_id
			FROM `call` as a, `case` as b, `shift` as s
			WHERE a.outcome_id in (10,40)
			AND s.shift_id = '$shift_id'
			AND s.`start` <= a.`start`
			AND s.`end` >= a.`start`
			AND a.case_id = b.case_id
			AND b.questionnaire_id = '$questionnaire_id'
			GROUP BY a.operator_id) as c on (c.operator_id = o.operator_id)
		JOIN (  SELECT count(*) as calls,a.operator_id
			FROM `call` as a, `case` as b, `shift` as s
			WHERE a.case_id = b.case_id
			AND s.shift_id = '$shift_id'
			AND s.`start` <= a.`start`
			AND s.`end` >= a.`start`
			AND b.questionnaire_id = '$questionnaire_id'
			GROUP BY a.operator_id) as calls on (calls.operator_id = o.operator_id)
		WHERE o.enabled = 1
		ORDER BY effectiveness DESC";

  $r = $db->GetAll($sql);

  foreach($r as $key => $value) {
    $r[$key]['time'] = sec_to_time($value['time']);
    $r[$key]['callt'] = sec_to_time($value['callt']);
    $r[$key]['callatemptt'] = sec_to_time($value['callatemptt']);
  }

  return $r;
}

/**
 * Get stats by interviewer by questionnaire by time
 *
 * @param string $start The start time
 * @param string $end The end time
 * @param bool $byoperator Display report by operator (false by questionnaire)
 * @return arrayh An array containing operator,questionnaire,firstName,CPH,effectiveness,
 */
function get_stats_by_time($start,$end,$byoperator = true)
{
	global $db;

  $start = $db->qstr($start);
  $end = $db->qstr($end);

	$sql = "SELECT q.questionnaire_id,o.operator_id, qq.description, o.firstName, (calltime.totaltime / callattempttime.totaltime) AS effectiveness, c.completions,callattempttime.totaltime as time, c.completions/(callattempttime.totaltime / 3600) as CPH, calltime.totaltime as callt, callattempttime.totaltime as callatemptt, calls.calls as totalcalls, calls.calls/(callattempttime.totaltime / 3600) as CALLSPH, calltime.totaltime as calltotaltime, callattempttime.totaltime as callattempttotaltime

    FROM operator AS o
    JOIN operator_questionnaire as q ON (q.operator_id = o.operator_id)
    JOIN questionnaire as qq ON (qq.questionnaire_id = q.questionnaire_id) 

    JOIN (
      SELECT SUM( TIMESTAMPDIFF(
      SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id, questionnaire_id
      FROM `call` AS c, `case` as b
      WHERE c.case_id = b.case_id
      AND c.`start` >= $start
      AND c.`end` <= $end
      GROUP BY operator_id, questionnaire_id
      ) AS calltime ON ( calltime.operator_id = o.operator_id AND calltime.questionnaire_id = q.questionnaire_id )

    JOIN (
      SELECT SUM( TIMESTAMPDIFF(
      SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id, questionnaire_id
      FROM `call_attempt` AS c, `case` as b
      WHERE c.case_id = b.case_id
      AND c.`start` >= $start
      AND c.`end` <= $end
      GROUP BY operator_id, questionnaire_id
    ) AS callattempttime ON ( callattempttime.operator_id = o.operator_id  AND callattempttime.questionnaire_id = q.questionnaire_id )

    JOIN (  SELECT count(*) as completions,a.operator_id,b.questionnaire_id
      FROM `call` as a, `case` as b
      WHERE a.outcome_id in (10,40)
      AND a.`start` >= $start
      AND a.`end` <= $end
      AND a.case_id = b.case_id
      GROUP BY a.operator_id,b.questionnaire_id) as c on (c.operator_id = o.operator_id AND c.questionnaire_id = q.questionnaire_id)

    JOIN (  SELECT count(*) as calls,a.operator_id, b.questionnaire_id
      FROM `call` as a, `case` as b
      WHERE a.case_id = b.case_id
      AND a.`start` >= $start
      AND a.`end` <= $end
      GROUP BY a.operator_id,b.questionnaire_id) as calls on (calls.operator_id = o.operator_id AND calls.questionnaire_id = q.questionnaire_id)";

  if ($byoperator) {
    $sql .= " WHERE o.enabled = 1
    ORDER BY o.operator_id ASC, q.questionnaire_id ASC";
  } else {
    $sql .= " WHERE qq.enabled = 1
    ORDER BY q.questionnaire_id ASC, o.operator_id ASC";
  }

  $report = $db->GetAll($sql);
 
  foreach($report as $key => $value) {
    $report[$key]['time'] = sec_to_time($value['time']);
    $report[$key]['callt'] = sec_to_time($value['callt']);
    $report[$key]['callatemptt'] = sec_to_time($value['callatemptt']);
  }




  //add totals
  $overalltotal = get_stats_total($report);

  //split by groupings and add subtotals
  $group = 'questionnaire_id';
  if ($byoperator) {
    $group = 'operator_id';
  }


  $initial = $report[0][$group];
  $rsplit = array(); //this part of the report
  $freport = array(); //final report;

  foreach($report as $key => $value) {
    if ($value[$group] != $initial) {
      $initial = $value[$group];
      $rtotal = get_stats_total($rsplit);
      $rtotal = end($rtotal);
      if ($byoperator) {
        $rtotal['firstName'] = $rsplit[0]['firstName'];
      } else {
        $rtotal['description'] = $rsplit[0]['description'];
      }
      $rsplit = array();
      $freport[] = $rtotal;
    }
    $rsplit[] = $value;
    $freport[] = $value;
  }
  $rtotal = get_stats_total($rsplit);
  $rtotal = end($rtotal);
  if ($byoperator) {
    $rtotal['firstName'] = $rsplit[0]['firstName'];
  } else {
    $rtotal['description'] = $rsplit[0]['description'];
  }
  $freport[] = $rtotal;

  $overalltotal =  end($overalltotal);

  if ($byoperator) {
    $overalltotal['firstName'] = T_("Total");
  } else {
    $overalltotal['description'] = T_("Total");
  }
  
  $freport[] = $overalltotal;

  return $freport;
}

/**
 * Convert seconds to time in HH:MM:SS
 *
 * @param int Seconds
 * @return string Time in format HH:MM:SS
 */
function sec_to_time($seconds)
{
	$h = 0;
	$m = 0;
	if($seconds >= 3600){
      		$h = floor($seconds/3600);
		$seconds = ($seconds%3600);
    	}
    	if($seconds >= 60){
		$m = floor($seconds/60);
    		$seconds = ($seconds%60);
    	}
    	$s = floor($seconds);

	return sprintf("%02d:%02d:%02d", $h, $m, $s);
}


/**
 * Given the stats, add a total line
 *
 * @param array The result set from the database
 * @return array The result set plus a total row
 */
function get_stats_total($rs)
{
	$completions = 0;
	$calls = 0;
	$totaltime = 0;
	$calltime = 0;

	foreach($rs as $r)
	{
		$completions += $r['completions'];
		$calls += $r['totalcalls'];
		$totaltime += $r['callattempttotaltime'];
		$calltime += $r['calltotaltime'];
	}

	if ($totaltime == 0)
	{
		$effectiveness = 0;
		$cph = 0;
		$callsph = 0;
	}
	else
	{
		$effectiveness = round($calltime / $totaltime,4);
		$cph = round($completions/($totaltime/3600),4);
		$callsph = round($calls/($totaltime/3600),4);
	}

	$rs[] = array("effectiveness" => '<b>' . $effectiveness . '</b>', "completions" =>  '<b>' .$completions. '</b>',"time" =>  '<b>' . sec_to_time($totaltime). '</b>',"totalcalls" =>  '<b>' . $calls. '</b>',"callt" =>  '<b>' . sec_to_time($calltime). '</b>',"CPH" =>  '<b>' . $cph. '</b>',"CALLSPH" =>  '<b>' . $callsph. '</b>');

	return $rs;
}


/**
 * Get stats by interviewer 
 *
 * @return arrayh An array containing operator_id,firstName,CPH,effectiveness,
 */
function get_stats()
{
	global $db;

	$sql = "SELECT o.operator_id, o.firstName, (calltime.totaltime / callattempttime.totaltime) AS effectiveness, c.completions,callattempttime.totaltime as time, c.completions/(callattempttime.totaltime / 3600) as CPH, calltime.totaltime as callt, callattempttime.totaltime as callatemptt, calls.calls as totalcalls, calls.calls/(callattempttime.totaltime / 3600) as CALLSPH, calltime.totaltime as calltotaltime, callattempttime.totaltime as callattempttotaltime
		FROM operator AS o
		JOIN (
			SELECT SUM( TIMESTAMPDIFF(
			SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id
			FROM `call` AS c
			GROUP BY operator_id
			) AS calltime ON ( calltime.operator_id = o.operator_id )
		JOIN (
			SELECT SUM( TIMESTAMPDIFF(
			SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id
			FROM `call_attempt` AS c, `case` as b
			GROUP BY operator_id
		) AS callattempttime ON ( callattempttime.operator_id = o.operator_id )
		JOIN (  SELECT count(*) as completions,a.operator_id
			FROM `call` as a
			WHERE a.outcome_id in (10,40)
			GROUP BY a.operator_id) as c on (c.operator_id = o.operator_id)
		JOIN (  SELECT count(*) as calls,a.operator_id
			FROM `call` as a
			GROUP BY a.operator_id) as calls on (calls.operator_id = o.operator_id)
		WHERE o.enabled = 1
		ORDER BY effectiveness DESC";

  $r = $db->GetAll($sql);

  foreach($r as $key => $value) {
    $r[$key]['time'] = sec_to_time($value['time']);
    $r[$key]['callt'] = sec_to_time($value['callt']);
    $r[$key]['callatemptt'] = sec_to_time($value['callatemptt']);
  }

  return $r;
}


/**
 * Get stats by interviewer by questionnaire
 *
 * @param int $questionnaire_id The questionnaire
 * @return arrayh An array containing operator_id,firstName,CPH,effectiveness,
 */
function get_stats_by_questionnaire($questionnaire_id)
{
	global $db;

	$sql = "SELECT o.operator_id, o.firstName, (calltime.totaltime / callattempttime.totaltime) AS effectiveness, c.completions,callattempttime.totaltime as time, c.completions/(callattempttime.totaltime / 3600) as CPH, calltime.totaltime as callt, callattempttime.totaltime as callatemptt, calls.calls as totalcalls, calls.calls/(callattempttime.totaltime / 3600) as CALLSPH, calltime.totaltime as calltotaltime, callattempttime.totaltime as callattempttotaltime

		FROM operator AS o
		JOIN (
			SELECT SUM( TIMESTAMPDIFF(
			SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id
			FROM `call` AS c, `case` as b
			WHERE c.case_id = b.case_id
			AND b.questionnaire_id = '$questionnaire_id'
			GROUP BY operator_id
			) AS calltime ON ( calltime.operator_id = o.operator_id )
		JOIN (
			SELECT SUM( TIMESTAMPDIFF(
			SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id
			FROM `call_attempt` AS c, `case` as b
			WHERE c.case_id = b.case_id
			AND b.questionnaire_id = '$questionnaire_id'
			GROUP BY operator_id
		) AS callattempttime ON ( callattempttime.operator_id = o.operator_id )
		JOIN (  SELECT count(*) as completions,a.operator_id
			FROM `call` as a, `case` as b
			WHERE a.outcome_id in (10,40)
			AND a.case_id = b.case_id
			AND b.questionnaire_id = '$questionnaire_id'
			GROUP BY a.operator_id) as c on (c.operator_id = o.operator_id)
		JOIN (  SELECT count(*) as calls,a.operator_id
			FROM `call` as a, `case` as b
			WHERE a.case_id = b.case_id
			AND b.questionnaire_id = '$questionnaire_id'
			GROUP BY a.operator_id) as calls on (calls.operator_id = o.operator_id)
		WHERE o.enabled = 1
		ORDER BY effectiveness DESC";

  $r = $db->GetAll($sql);

  foreach($r as $key => $value) {
    $r[$key]['time'] = sec_to_time($value['time']);
    $r[$key]['callt'] = sec_to_time($value['callt']);
    $r[$key]['callatemptt'] = sec_to_time($value['callatemptt']);
  }

  return $r;
}

/**
 *  Get completions per hour overall with interviewer id and first name
 *
 *  @param int $qid The questionnaire ID
 *  @return array An array containing operator_id,firstName,CPH
 */
function get_CPH()
{
	global $db;

	$sql = "SELECT o.firstName,o.operator_id,c.completions,ca.time as time, c.completions/(ca.time / 3600) as CPH
		FROM operator as o
		JOIN (  SELECT count(*) as completions,operator_id
			FROM `call`
			WHERE outcome_id in (10,40)
			GROUP BY operator_id) as c on (c.operator_id = o.operator_id)
		JOIN (  SELECT SUM( TIMESTAMPDIFF(SECOND , start, IFNULL(end,start))) as time, operator_id
			FROM `call_attempt`
			GROUP BY operator_id) as ca on (ca.operator_id = o.operator_id)
		WHERE o.enabled = 1
		ORDER BY cph DESC";
	
  $r = $db->GetAll($sql);

  foreach($r as $key => $value) {
    $r[$key]['time'] = sec_to_time($value['time']);
  }

  return $r;

}



/**
 *  Get effectiveness by questionnaire with interviewer id and first name
 *
 *  @param int $qid The questionnaire ID
 *  @return array An array containing operator_id,firstName,effectiveness
 */
function get_effectiveness_by_questionnaire($questionnaire_id)
{
	global $db;

	$sql = "SELECT o.operator_id, o.firstName, (calltime.totaltime / callattempttime.totaltime) AS effectiveness
		FROM operator AS o
		JOIN (
			SELECT SUM( TIMESTAMPDIFF(
			SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id
			FROM `call` AS c, `case` as b
			WHERE c.case_id = b.case_id
			AND b.questionnaire_id = '$questionnaire_id'
			GROUP BY operator_id
			) AS calltime ON ( calltime.operator_id = o.operator_id )
		JOIN (
			SELECT SUM( TIMESTAMPDIFF(
			SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id
			FROM `call_attempt` AS c, `case` as b
			WHERE c.case_id = b.case_id
			AND b.questionnaire_id = '$questionnaire_id'
			GROUP BY operator_id
		) AS callattempttime ON ( callattempttime.operator_id = o.operator_id )
		WHERE o.enabled = 1
		ORDER BY effectiveness DESC";
	
	return $db->GetAll($sql);
}



/**
 *  Get effectiveness overall with interviewer id and first name
 *
 *  @return array An array containing operator_id,firstName,effectiveness
 */
function get_effectiveness()
{
	global $db;

	$sql = "SELECT o.operator_id, o.firstName, (calltime.totaltime / callattempttime.totaltime) AS effectiveness
		FROM operator AS o
		JOIN (
			SELECT SUM( TIMESTAMPDIFF(
			SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id
			FROM `call` AS c
			GROUP BY operator_id
			) AS calltime ON ( calltime.operator_id = o.operator_id )
		JOIN (
			SELECT SUM( TIMESTAMPDIFF(
			SECOND , c.start, IFNULL( c.end, c.start ) ) ) AS totaltime, operator_id
			FROM `call_attempt` AS c
			GROUP BY operator_id
		) AS callattempttime ON ( callattempttime.operator_id = o.operator_id )
		WHERE o.enabled = 1
		ORDER BY effectiveness DESC";
	
	return $db->GetAll($sql);
}



/**
 * Get the average time on a call with an outcome
 * in seconds by questionnaire
 *
 * @param int $outcome_id The outcome id
 * @param int $questionnaire_id The questionnaire id
 * @return int Seconds average of calls with this outcome
 */
function get_average_time_questionnaire($outcome_id,$questionnaire_id)
{
	global $db;

	$sql = "SELECT AVG(TIMESTAMPDIFF(SECOND,c.start,c.end)) as average
		FROM `call` as c, `case` as q
		WHERE c.outcome_id = '$outcome_id'
		AND q.case_id = c.case_id
		AND q.questionnaire_id = '$questionnaire_id'";

	$rs = $db->GetRow($sql);

	if (!empty($rs))
		return $rs['average'];
	else
		return 0;

}



/**
 * Get the average time on a call with an outcome
 * in seconds
 *
 * @param int $outcome_id The outcome id
 * @return int Seconds average of calls with this outcome
 */
function get_average_time($outcome_id)
{
	global $db;

	$sql = "SELECT AVG(TIMESTAMPDIFF(SECOND,c.start,c.end)) as average
		FROM `call` as c
		WHERE c.outcome_id = '$outcome_id'";

	$rs = $db->GetRow($sql);

	if (!empty($rs))
		return $rs['average'];
	else
		return 0;

}



/**
 * If not on a shift, display a message
 */
function display_none()
{
	print "<h1>" . T_("No shift") . "</h1>";
}

/** 
 * Display the total number of completions for this project
 *
 * @param int $qid The questionnaire id
 *
 */
function display_total_completions($qid)
{
	global $db;

	$sql = "SELECT count(case_id) as c
		FROM `case`
		WHERE current_outcome_id in (10,40)
		AND questionnaire_id = '$qid'";

	$rs = $db->GetRow($sql);

	$c = 0;
	if (!empty($rs)) $c = $rs['c'];

	print "<h3>" . T_("Total completions") . "</h3><h2>$c</h2>";
}

/** 
 * Display the total number of completions for this shift
 *
 * @param int $qid The questionnaire id
 * @param int $sid The shift id
 *
 */
function display_completions_this_shift($qid,$sid)
{
	global $db;

	$sql = "SELECT count(ca.call_id) as c
		FROM `call` as ca, `case` as cs, `shift` as s
		WHERE ca.outcome_id in (10,40)
		AND ca.case_id = cs.case_id
		AND cs.questionnaire_id = '$qid'
		AND s.questionnaire_id = '$qid'
		AND s.shift_id = '$sid'
		AND ca.start >= s.start
		AND ca.start <= s.end";

	$rs = $db->GetRow($sql);

	$c = 0;
	if (!empty($rs)) $c = $rs['c'];

	print "<h3>" . T_("Completions this shift") . "</h3><h2>$c</h2>";
}

/** 
 * Display the total number of completions for the last shift
 *
 * @param int $qid The questionnaire id
 * @param int $sid The current shift id
 *
 */
function display_completions_last_shift($qid,$sid)
{
	global $db;

	$sql = "SELECT shift_id
		FROM shift
		WHERE questionnaire_id = '$qid'
		AND shift_id < '$sid'
		ORDER BY shift_id DESC
		LIMIT 1";

	$ps = $db->GetRow($sql);
	if (empty($ps))
		print "<h3>" . T_("No previous shift") . "</h3>";
	else
	{
		$psid = $ps['shift_id'];

		$sql = "SELECT count(ca.call_id) as c
			FROM `call` as ca, `case` as cs, `shift` as s
			WHERE ca.outcome_id in (10,40)
			AND ca.case_id = cs.case_id
			AND cs.questionnaire_id = '$qid'
			AND s.questionnaire_id = '$qid'
			AND s.shift_id = '$psid'
			AND ca.start >= s.start
			AND ca.start <= s.end";
	
		$rs = $db->GetRow($sql);
	
		$c = 0;
		if (!empty($rs)) $c = $rs['c'];
	
		print "<h3>" . T_("Completions on the previous shift") . "</h3><h2>$c</h2>";
	}
}	


/** 
 * Display the total number of completions for the last shift
 * at the same number of seconds in to the last shift
 *
 * @param int $qid The questionnaire id
 * @param int $sid The current shift id
 *
 */
function display_completions_same_time_last_shift($qid,$sid)
{
	global $db;

	$sql = "SELECT shift_id
		FROM shift
		WHERE questionnaire_id = '$qid'
		AND shift_id < '$sid'
		ORDER BY shift_id DESC
		LIMIT 1";

	$ps = $db->GetRow($sql);
	if (empty($ps))
		print "<h3>" . T_("No previous shift") . "</h3>";
	else
	{
		$psid = $ps['shift_id'];

		$sql = "SELECT count(ca.call_id) as c
			FROM `call` as ca, `case` as cs, `shift` as s
			JOIN `shift` as s2 on (s2.shift_id = '$sid')
			WHERE ca.outcome_id in (10,40)
			AND ca.case_id = cs.case_id
			AND cs.questionnaire_id = '$qid'
			AND s.questionnaire_id = '$qid'
			AND s.shift_id = '$psid'
			AND ca.start >= s.start
			AND ca.start <= DATE_SUB(s.end, INTERVAL TIMESTAMPDIFF(SECOND , CONVERT_TZ(NOW(),'System','UTC'), s2.end) SECOND)";
	
		$rs = $db->GetRow($sql);
	
		$c = 0;
		if (!empty($rs)) $c = $rs['c'];
	
		print "<h3>" . T_("Completions this time on the previous shift") . "</h3><h2>$c</h2>";
	}
}	

/** 
 * Display the interviewer with the top CPH for this shift
 *
 * @param int $qid The questionnaire id
 * @param int $sid The current shift id
 *
 */
function display_top_cph_this_shift($qid,$sid)
{
	global $db;

	$rs = get_CPH_by_shift($qid,$sid);
	
	if (empty($rs))
		print "<h3>" . T_("No calls made for this shift") . "</h3>";
	else
		print "<h3>" . T_("Top CPH for this shift") . "</h3><h2>{$rs[0]['firstName']} - ". round($rs[0]['CPH'],2) ."</h2>";

}

/** 
 * Display the interviewer with the top CPH overall
 *
 * @param int $qid The questionnaire id
 *
 */
function display_top_cph($qid)
{
	global $db;

	$rs = get_CPH_by_questionnaire($qid);

	if (empty($rs))
		print "<h3>" . T_("No calls made for this project") . "</h3>";
	else
		print "<h3>" . T_("Top CPH") . "</h3><h2>{$rs[0]['firstName']} - ". round($rs[0]['CPH'],2) ."</h2>";


}



?>
