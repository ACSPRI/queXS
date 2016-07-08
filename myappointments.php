<?php 
/**
 *  Display appointments for this operator
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI), 2016
 * @package queXS
 * @subpackage user
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
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
 * Operator functions
 */
include("functions/functions.operator.php");

$js = false;
if (AUTO_LOGOUT_MINUTES !== false)
	$js = array("include/jquery/jquery-1.4.2.min.js","js/childnap.js");

xhtml_head(T_("My appointments"),false,array("css/table.css"),$js,false,60);

//List all upcoming appointments for this interviewer

$operator_id = get_operator_id(); 

if (isset($_GET['callnext']))
{
  $cn = intval($_GET['callnext']);

  $db->StartTrans();
  $sql = "SELECT next_case_id FROM `operator` WHERE operator_id = $operator_id";
  $nc = $db->GetOne($sql);
  if (!empty($nc))
    print "<p>" . T_("Already calling case") . " $nc " . T_("next") . "</p>";
  else
  {
    $sql = "UPDATE `operator` SET next_case_id = $cn WHERE operator_id = $operator_id";
    $db->Execute($sql);
    print "<p>" . T_("Will call case") . " $cn " . T_("next") . "</p>";
  }
  $db->CompleteTrans();
}

$rs = "";

  $sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.start,'UTC',op.Time_zone_name),'".DATE_TIME_FORMAT."') as start,DATE_FORMAT(CONVERT_TZ(c.end,'UTC',op.Time_zone_name),'".TIME_FORMAT."') as end,
    DATE_FORMAT(CONVERT_TZ(c.start,'UTC',r.Time_zone_name),'".TIME_FORMAT."') as rstart,DATE_FORMAT(CONVERT_TZ(c.end,'UTC',r.Time_zone_name),'".TIME_FORMAT."') as rend, c.completed_call_id, 
    CONCAT(r.firstName, ' ', r.lastName) as respname, IFNULL(ao.firstName,'" . TQ_("Any operator") . "') as witho,
    CASE WHEN op.next_case_id IS NULL THEN CONCAT('<a href=\"?callnext=',c.case_id,'\">".T_("Call next")."</a>') ELSE CONCAT('".T_("Calling case")." ', op.next_case_id, ' ".T_("next")."') END as callnext
    FROM `appointment` as c
    JOIN operator as op on (op.operator_id = $operator_id)
		JOIN respondent as r on  (r.respondent_id = c.respondent_id)
		LEFT JOIN operator AS ao ON (ao.operator_id = c.require_operator_id)
    WHERE c.end >= CONVERT_TZ(NOW(),'System','UTC')
    AND c.completed_call_id IS NULL
    AND (c.require_operator_id IS NULL OR c.require_operator_id = $operator_id)
		ORDER BY c.start DESC";
	
$rs = $db->GetAll($sql);


if (empty($rs))
{
  print "<p>" . T_("No future appointments scheduled") . "</p>";
}
else
{
	translate_array($rs,array("des"));
	xhtml_table($rs,array("start","end","rstart","respname","witho","callnext"),array(T_("Start"),T_("End"),T_("RTime Start"),T_("Respondent"),T_("Operator"),T_("Call next")));
}
		

xhtml_foot();

?>
