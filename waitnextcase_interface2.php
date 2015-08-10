<?php 
/**
 * Display the main page including all panels and tabs
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
 *
 */

/**
 * Language file
 */
include_once("lang.inc.php");

/**
 * XHTML functions
 */
include_once("functions/functions.xhtml.php");

xhtml_head(T_("Standby"),false,array("include/bootstrap/css/bootstrap.min.css","css/index_interface2.css"), array(), false, 300);

if (isset($_GET['auto']))
{
	include_once("functions/functions.operator.php");

	$operator_id = get_operator_id();
	$case_id = get_case_id($operator_id,false);
	end_case($operator_id);
	 //add case note  
      $sql = "INSERT INTO case_note (case_note_id,case_id,operator_id,note,`datetime`)
        VALUES (NULL,'$case_id','$operator_id','" . TQ_("Operator Automatically logged out after: ") . AUTO_LOGOUT_MINUTES . TQ_(" minutes") . "', CONVERT_TZ(NOW(),'System','UTC'))";
      $db->Execute($sql);

	print "<div class='error well' style='margin:2%; color:red;'><b>" . T_("You have been automatically logged out of work due to inactivity") . "</b></div>";
}


/* $sql = "SELECT sample_id FROM `sample` where import_id = 1";
$rs = $db->GetAll($sql);
for($i=0;$i<=count($rs)-1;$i++){ $ssseedss[] = $rs[$i]['sample_id'] ;}
$ssseedssd = implode(",",$ssseedss);
 print $ssseedssd; */
 
 
/**
 * check if cases available
 */

//if assigned to a questionnaire

$sql = "SELECT oq.questionnaire_id, q.description
	FROM operator_questionnaire as oq, questionnaire as q
	WHERE q.enabled = 1
	AND oq.operator_id = '$operator_id'
	AND q.questionnaire_id = oq.questionnaire_id";

$rs = $db->GetAll($sql);

if (empty($rs))
	
	print "<div class='error well' style='margin:2%; color:red; font-size:1.5em;'><b>" . T_("ERROR: No questionnaires assigned to you") . "</b></div>";
	
else
{
	print "<div class='col-sm-3'>";
	print "<div class=' well' style='padding:2%;'><p>" . T_("Assigned questionnaires:") . "";
			
	/* if (isset($_GET['auto'])) {
		echo "</p></br>";
		xhtml_table($rs,array("questionnaire_id","description"),array(T_("ID"),T_("Description")));
	}
	else  */
	echo "&emsp;<b style='color:green;'>" . count($rs) . "</b></p>";
	print "</div>";
	
	for ($i = 0; $i <= count($rs)-1;$i++){ 	$oq[] = $rs[$i]['questionnaire_id']; }
	$oqid = implode(",",$oq);

	//no sample
	$sql = "SELECT q.questionnaire_id, q.description, si.description as sdescription, si.sample_import_id
	FROM questionnaire as q, sample_import as si LEFT JOIN questionnaire_sample_quota AS qsq ON (qsq.sample_import_id = si.sample_import_id), questionnaire_sample as qs
	WHERE q.questionnaire_id IN ($oqid)
	AND si.enabled = 1
	AND (qsq.quota_reached = 0 OR qsq.quota_reached IS NULL)
	AND qs.questionnaire_id = q.questionnaire_id
	AND si.sample_import_id = qs.sample_import_id
	GROUP BY si.sample_import_id";

	$rs = $db->GetAll($sql);

	if (empty($rs))
		print "<div class='error well' style='margin:2%; color:red; font-size:1.5em;'><b>" . T_("ERROR: No samples assigned to the questionnaires") . "</b></div>";
	else {
		print "<div class=' well' style='padding:2%;'>" . T_("Assigned samples:") . "&emsp;<b style='color:green;'>" . count($rs) . "</b></div>";
		//xhtml_table($rs,array("questionnaire_id","description","sdescription"),array(T_("ID"),T_("Description"),T_("Sample")));
	
	for ($i = 0; $i <= count($rs)-1;$i++){ 	$si[] = $rs[$i]['sample_import_id']; }
	$siid = implode(",",$si);
	
	//shift restrictions and no shift
	$sql = "SELECT q.description, CONVERT_TZ(sh.start, 'UTC', @@session.time_zone) as st, CONVERT_TZ(sh.end, 'UTC',@@session.time_zone) as en
			FROM questionnaire AS q
			LEFT JOIN shift AS sh ON (sh.questionnaire_id = q.questionnaire_id AND (CONVERT_TZ( NOW( ) ,@@session.time_zone, 'UTC' ) >= sh.start ) AND (CONVERT_TZ( NOW( ) ,@@session.time_zone, 'UTC' ) <= sh.end ))
			WHERE q.questionnaire_id IN ($oqid)
			AND !(q.restrict_work_shifts = 1 AND sh.shift_id IS NULL)";

	$rs = $db->GetAll($sql);

	if (empty($rs))
		print "<div class='error well' style='margin:2%; color:red; font-size:1.5em;'><b>" . T_("ERROR: No shifts at this time") . "</b></div>";

	else{
			print "<div class=' well' style='padding:2%;'>" . T_("Current shifts available:") . "&emsp;<b style='color:green;'>" . T_("Yes") . "</b></div>";
			//xhtml_table($rs,array("description","st","en"),array(T_("Questionnaire"),T_("Shift start"),T_("Shift end")));
		
			//assigned to operator
	
			// call restrictions and outside times, operator skill and filter outcomes 10,25,28,42,43,40
			$sql = "SELECT COUNT( DISTINCT sv.sample_id) as count_samples
			FROM `sample` as s
			JOIN `sample_var` as sv on( s.sample_id = sv.sample_id )
			JOIN sample_import as si on (s.import_id = si.sample_import_id)
			LEFT JOIN (`case` as c,  `outcome` as ou ) on (s.sample_id = c.sample_id and ou.outcome_id = c.current_outcome_id)
			LEFT JOIN call_restrict as cr on ( cr.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= cr.start 	and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= cr.end)
			WHERE c.questionnaire_id IN ($oqid)
            AND s.import_id IN ($siid)
			AND !(si.call_restrict = 1 AND cr.day_of_week IS NULL)
			AND ou.outcome_type_id IN( SELECT outcome_type_id FROM operator_skill WHERE operator_id = '$operator_id')
			AND ou.outcome_id NOT IN (10,25,28,42,43,44,45,40)";
			$cases_count = $db->GetRow($sql);

			if ($cases_count['count_samples'] == 0){
			print "<div class='error well' style='padding:2%;'>" . T_("Cases currently available to call") . ": <b style='color:red;'> " . $cases_count['count_samples'] . "</b></div>";
			}
			else {
			print "<div class='well' style='padding:2%;'>" . T_("Cases currently available to call") . ": <b style='color:green;'> " . $cases_count['count_samples'] . "</b></div>";
			}
		
			//new samples available 
			$sql = "SELECT COUNT( DISTINCT sv.sample_id) as count_samples
				FROM `sample` as s
				JOIN `sample_var` as sv on( s.sample_id = sv.sample_id )
				JOIN sample_import as si on (s.import_id = si.sample_import_id)
				LEFT JOIN `case` as c on (s.sample_id = c.sample_id )
				LEFT JOIN call_restrict as cr on ( cr.day_of_week = DAYOFWEEK(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) >= cr.start 	and TIME(CONVERT_TZ(NOW(), 'System' , s.Time_zone_name)) <= cr.end)
				WHERE s.import_id IN ($siid)
				AND !(si.call_restrict = 1 AND cr.day_of_week IS NULL)
				AND c.sample_id IS NULL";
			$new_samples = $db->GetRow($sql);
			
			if ($new_samples['count_samples'] == 0){
			print "<div class='error well' style='margin:2%; font-size:1.5em;'>" . T_("New samples available to call") . ": <b style='color:red;'> " . $new_samples['count_samples'] . "</b></div>";
				}
			else {
				print "<div class='well' style='padding:2%;'>" . T_("New samples available to call") . ": <b style='color:green;'> " . $new_samples['count_samples'] . "</b></div>";
			}
		
		}
	}
	
	print "</div>";
}

?>
			<div id="">
				<ul class="wait_wrapper">
<?php if ($cases_count['count_samples'] != 0 or $new_samples['count_samples'] != 0){  ?>
					<li class="wait_li_1"><a href="index_interface2.php"><?php  echo T_("Get a new case"); ?> <img src="css/images/play.jpg" /></a></li>
<?php } ?>
					<li class="wait_li_2"><a href="endwork.php"><?php  echo T_("End work"); ?> <img src="css/images/end.jpg" /></a></li>
				</ul>
			</div>
<?php 
			
xhtml_foot();

?>
