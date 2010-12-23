<?php
/**
 * Display extension status
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2010
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/software/ queXS was writen for ACSPRI
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
include ("../db.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

xhtml_head(T_("Display extension status"),true,array("../css/table.css"));
	
$sql=  "SELECT o.firstName, o.extension, CASE o.voip_status WHEN 0 THEN '" . T_("VoIP Offline") . "' ELSE '" . T_("VoIP Online") . "' END as voip_status, CASE ca.state WHEN 0 THEN '" . T_("Not called") . "' WHEN 1 THEN '" . T_("Requesting call") . "' WHEN 2 THEN '" . T_("Ringing") . "' WHEN 3 THEN '" . T_("Answered") . "' WHEN 4 THEN '" . T_("Requires coding") . "' ELSE '" . T_("Done") . "' END as state, CONCAT('<a href=\'supervisor.php?case_id=', c.case_id , '\'>' , c.case_id, '</a>') as case_id, SEC_TO_TIME(TIMESTAMPDIFF(SECOND,cal.start,CONVERT_TZ(NOW(),'SYSTEM','UTC'))) as calltime, voip_status as vs
	FROM operator as o
	LEFT JOIN `case` as c ON (c.current_operator_id = o.operator_id)
	LEFT JOIN `call_attempt` as cal ON (cal.operator_id = o.operator_id AND cal.end IS NULL and cal.case_id = c.case_id)
	LEFT JOIN `call` as ca ON (ca.case_id = c.case_id AND ca.operator_id = o.operator_id AND ca.outcome_id= 0 AND ca.call_attempt_id = cal.call_attempt_id)
	WHERE o.voip = 1
	ORDER BY o.operator_id ASC";

$rs = $db->GetAll($sql);


if (!empty($rs))
{
	xhtml_table($rs,array("extension","firstName","voip_status","case_id","state","calltime"),array(T_("Extension"),T_("Operator"),T_("VoIP Status"),T_("Case ID"),T_("Call state"),T_("Time on call")),"tclass",array("vs" => "1"));
}
else
	print "<p>" . T_("No operators") . "</p>";
	
xhtml_foot();

?>
