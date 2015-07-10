<?php /**
 * List and edit reports on shifts
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
 * @subpackage admin
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
include ("../db.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Display functions
 */
include("../functions/functions.display.php");

/**
 * Operator functions
 */
include("../functions/functions.operator.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

xhtml_head(T_("Shift reports"),true,array("../include/bootstrap/css/bootstrap.min.css","../css/custom.css"),array("../js/window.js"));

$operator_id = get_operator_id();

print "<h3>" . T_("Please select a questionnaire") . "</h3>";
$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);
display_questionnaire_chooser($questionnaire_id ,false,"form-inline clearfix", "form-control");

if ($questionnaire_id)
{
	print "<h3>" . T_("Please select a shift") . "</h3>";

	$shift_id = false;
	if (isset($_GET['shift_id'])) $shift_id = bigintval($_GET['shift_id']);

	//get shifts for this questionnaire in operator time
	$sql = "SELECT s.shift_id as value, CONCAT(DATE_FORMAT(CONVERT_TZ(s.start,'UTC',o.Time_zone_name),'" . DATE_TIME_FORMAT ."'), ' - ', DATE_FORMAT(CONVERT_TZ(s.end,'UTC',o.Time_zone_name),'" . TIME_FORMAT ."')) as description,
			CASE WHEN s.shift_id = '$shift_id' THEN 'selected=\'selected\'' ELSE '' END AS selected 
		FROM `shift` as s, operator as o
		WHERE s.questionnaire_id = '$questionnaire_id'
		AND o.operator_id = '$operator_id'
		ORDER BY s.start ASC";

	$r = $db->GetAll($sql);

	if (!empty($r))
		display_chooser($r,"shift","shift_id",true,"questionnaire_id=$questionnaire_id",true,true,false,true,"form-inline form-group");

	if ($shift_id)
	{
		print "<h3>" . T_("Reports for this shift") . "</h3>";

		//list current reports with a link to edit
		$sql = "SELECT s.report,o.firstName,DATE_FORMAT(CONVERT_TZ(s.datetime,'UTC',o.Time_zone_name),'" . DATE_TIME_FORMAT ."') as d,
			CONCAT('<a href=\'?questionnaire_id=$questionnaire_id&amp;shift_id=$shift_id&amp;shift_report_id=', s.shift_report_id, '\'>". TQ_("Edit") . "</a>') as link
			FROM shift_report as s, operator as o
			WHERE s.operator_id = o.operator_id
			AND s.shift_id = '$shift_id'";

		$r = $db->GetAll($sql);

		if (!empty($r))
			xhtml_table($r,array("firstName", "d", "report","link"),array(T_("Operator"),T_("Date"),T_("Report"),T_("Edit")),"tclass");

		//link to create a new report
		print "<p><a href='?questionnaire_id=$questionnaire_id&amp;shift_id=$shift_id&amp;createnewreport=yes'>" . T_("Create new report for this shift") . "</a></p>";


		if (isset($_GET['createnewreport']))
		{
			//create a new report
			print "<h3>" . T_("Enter report for this shift") . "</h3>";
			print "<form action='?' method='get'><p><textarea name='report' id='report' rows='15' cols='80'></textarea></p>";
			print "<p><input type='hidden' name='questionnaire_id' id='questionnaire_id' value='$questionnaire_id'/>";
			print "<input type='hidden' name='shift_id' id='shift_id' value='$shift_id'/>";
			print "<input type='submit' name='submit' id='submit' value=\"" . T_("Add report") . "\"/>";
			print "</p></form>";
		}
		else if (isset($_GET['report']))
		{
			//add report to database
			$report = $db->qstr($_GET['report']);

			$sql = "INSERT INTO shift_report (shift_id,operator_id,datetime,report,shift_report_id)
				VALUES ('$shift_id','$operator_id',CONVERT_TZ(NOW(),'System','UTC'),$report,NULL)";

			$db->Execute($sql);
		}
		else if (isset($_GET['shift_report_id']))
		{
			$shift_report_id = bigintval($_GET['shift_report_id']);

			if (isset($_GET['ereport']))
			{
				//edit report
				$report = $db->qstr($_GET['ereport']);
	
				$sql = "UPDATE shift_report
					SET operator_id = '$operator_id', datetime = CONVERT_TZ(NOW(),'System','UTC'), report = $report
					WHERE shift_report_id = '$shift_report_id'";

				$db->Execute($sql);
			}

			$sql = "SELECT report
				FROM shift_report
				WHERE shift_report_id = '$shift_report_id'";
	
			$r = $db->GetRow($sql);
			if (empty($r))
			{
				print "<h3>" . T_("This report does not exist in the database") . "</h3>";
			}
			else
			{
				//edit report
				print "<h3>" . T_("Edit report for this shift") . "</h3>";
				print "<form action='?' method='get'><p><textarea name='ereport' id='ereport' rows='15' cols='80'>{$r['report']}</textarea></p>";
				print "<p><input type='hidden' name='questionnaire_id' id='questionnaire_id' value='$questionnaire_id'/>";
				print "<input type='hidden' name='shift_id' id='shift_id' value='$shift_id'/>";
				print "<input type='hidden' name='shift_report_id' id='shift_report_id' value='$shift_report_id'/>";				
				print "<input type='submit' name='submit' id='submit' value=\"" . T_("Modify report") . "\"/>";
				print "</p></form>";
			}
		}

	}
}

xhtml_foot();



?>

