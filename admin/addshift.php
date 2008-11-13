<?
/**
 * Add and modify shifts by questionnaire
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
include ("../config.inc.php");

/**
 * Database file
 */
include ("../db.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Operator functions
 */
include ("../functions/functions.operator.php");

/**
 * Display functions
 */
include ("../functions/functions.display.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

global $db;


/**
 * Add shifts to the DB based on the shift_template table
 */


if (isset($_POST['year'])) $year = bigintval($_POST['year']); else $year = "YEAR(NOW())";
if (isset($_POST['woy'])) $woy = bigintval($_POST['woy']); else $woy = "WEEK(NOW(), 3)";
if (isset($_POST['qid'])) $questionnaire_id = bigintval($_POST['qid']); else $questionnaire_id = false;
if (isset($_GET['year'])) $year = bigintval($_GET['year']);
if (isset($_GET['woy'])) $woy = bigintval($_GET['woy']);
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);

$y = $db->GetRow("SELECT $year as y");
$year = $y['y'];
$y = $db->GetRow("SELECT $woy as y");
$woy = $y['y'];


$operator_id = get_operator_id();


if (isset($_POST['submit']))
{
	//process
	//update or delete existing shifts
	foreach($_POST as $key => $val)
	{
		if (substr($key,0,5) == "start")
		{
			$num = bigintval(substr($key,6));
			if (isset($_POST["use_$num"]))
			{
				$sql = "UPDATE shift as s, operator as o
					SET s.start = CONVERT_TZ(CONCAT(STR_TO_DATE(CONCAT($year, ' ',$woy,' ', {$_POST["dow_$num"]}),'%x %v %w'),' ','" . $_POST["start_$num"] . "'), o.Time_zone_name, 'UTC'),
					s.end = CONVERT_TZ(CONCAT(STR_TO_DATE(CONCAT($year, ' ',$woy,' ', {$_POST["dow_$num"]}),'%x %v %w'),' ','" . $_POST["end_$num"] .  "'), o.Time_zone_name, 'UTC')
					WHERE o.operator_id = '$operator_id'
					AND shift_id = '$num'";

				$db->Execute($sql);
			}
			else
			{
				$sql = "DELETE FROM shift
					WHERE shift_id = '$num'";
				$db->Execute($sql);
			}
		}
	}
	//insert new shifts
	foreach($_POST as $key => $val)
	{
		if (substr($key,0,7) == "NEW_use")
		{
			if ($val == "on")
			{
				$num = bigintval(substr($key,8));
				$sql = "INSERT INTO shift (shift_id,questionnaire_id,start,end)
					SELECT NULL,'$questionnaire_id', CONVERT_TZ(CONCAT(STR_TO_DATE(CONCAT($year, ' ',$woy,' ', {$_POST["NEW_dow_$num"]}),'%x %v %w'),' ','" . $_POST["NEW_start_$num"] . "'), Time_zone_name, 'UTC') , CONVERT_TZ(CONCAT(STR_TO_DATE(CONCAT($year, ' ',$woy,' ', {$_POST["NEW_dow_$num"]}),'%x %v %w'),' ','" . $_POST["NEW_end_$num"] .  "'), Time_zone_name, 'UTC')
					FROM operator
					WHERE operator_id = '$operator_id'";
				$db->Execute($sql);
			}
		}
	}
}


xhtml_head(T_("Add shifts"),true,array("../css/shifts.css"),array("../js/window.js"));

/**
 * Display warning if timezone data not installed
 *
 */

$sql = "SELECT CONVERT_TZ(NOW(),'Australia/Victoria','UTC') as t";
$rs = $db->GetRow($sql);

if (empty($rs) || !$rs || empty($rs['t']))
	print "<div class='warning'><a href='http://dev.mysql.com/doc/mysql/en/time-zone-support.html'>" . T_("Your database does not have timezones installed, please see here for details") . "</a></div>";


/**
 * display years including current selected year
 * display weeks of year including current selected week of year
 * find if there are already shifts defined for this week of year / year and display them as selected, else show from template
 * when submitted, add checked shifts, and delete unchecked shifts if they exist
 *
 * @todo Use javascript to add shifts if necessarry outside the template
 */

print "<h2>" . T_("Add shifts in your Time Zone") . "</h2>";

print "<h3>" . T_("Select a questionnaire from the list below") . "</h3>";
display_questionnaire_chooser($questionnaire_id);	

if ($questionnaire_id != false)
{
	print "<p>";
	for ($i = $year - 1; $i < $year + 4; $i++)
	{
		if ($i == $year)
			print "$i ";
		else
			print "<a href=\"?year=$i&amp;woy=$woy&amp;questionnaire_id=$questionnaire_id\">$i</a> ";
	}
	print "</p>";
	
	
	print "<p>";
	for ($i = 1; $i <= 53; $i++)
	{
		if ($i == $woy)
			print "$i ";
		else
			print "<a href=\"?woy=$i&amp;year=$year&amp;questionnaire_id=$questionnaire_id\">$i</a> ";
	}
	print "</p>";
	
	
	$sql = "SELECT shift_id, dt, dta,start,end
		FROM (
			(
			SELECT shift_id, DATE_FORMAT( CONVERT_TZ( s.start, 'UTC', o.Time_zone_name ) , '%W %d %M %Y' ) AS dt,
				DATE( CONVERT_TZ( s.start, 'UTC', o.Time_zone_name ) ) AS dta,
				TIME( CONVERT_TZ( s.start, 'UTC', o.Time_zone_name ) ) AS start,
				TIME( CONVERT_TZ( s.end, 'UTC', o.Time_zone_name ) ) AS end
			FROM shift AS s, operator AS o
			WHERE WEEK( CONVERT_TZ( s.start, 'UTC', o.Time_zone_name ) , 3 ) = '$woy'
				AND YEAR( CONVERT_TZ( s.start, 'UTC', o.Time_zone_name ) ) = '$year'
				AND o.operator_id = '$operator_id'
				AND s.questionnaire_id = '$questionnaire_id'
			) 
		UNION (
			SELECT NULL AS shift_id,
				DATE_FORMAT( STR_TO_DATE( CONCAT( '$year', ' ', '$woy', ' ', day_of_week -1 ) , '%x %v %w' ) , '%W %d %M %Y' ) AS dt,
		       		STR_TO_DATE( CONCAT( '$year', ' ', '$woy', ' ', day_of_week -1 ) , '%x %v %w' ) AS dta,
				start,end
			FROM shift_template
			) 
		) AS sb
		GROUP BY dta,start,end";	
	
	
	$shifts = $db->GetAll($sql);
	
	
	$sql = "SELECT DATE_FORMAT(STR_TO_DATE(CONCAT($year, ' ',$woy,' ',day_of_week - 1),'%x %v %w'), '%W %d %M %Y') as dt, day_of_week - 1 as dow
		FROM shift_template
		GROUP BY dow";
	
	$daysofweek = $db->Execute($sql);
	
	
	
	?>
		<form method="post" action="">
		<table>
	<?
		print "<tr><th>" . T_("Day") . "</th><th>" . T_("Start") . "</th><th>" . T_("End") . "</th><th>" . T_("Use shift?") . "</th></tr>";
		$count = 1;
		foreach($shifts as $shift)
		{
			$checked="";
			$shift_id="";
			$prefix="";
			if (!empty($shift['shift_id']))
			{ 
				$checked="checked=\"checked\""; $shift_id = $shift['shift_id']; 
			}
			else
			{
				$shift_id = $count;
				$prefix = "NEW_";
			}
			print "<tr><td>" . $daysofweek->GetMenu($prefix . "dow_$shift_id",$shift['dt']) . "</td><td><input size=\"8\" name=\"" . $prefix ."start_$shift_id\" maxlength=\"8\" type=\"text\" value=\"{$shift['start']}\"/></td><td><input name=\"" . $prefix ."end_$shift_id\" type=\"text\" size=\"8\" maxlength=\"8\" value=\"{$shift['end']}\"/></td><td><input name=\"" . $prefix ."use_$shift_id\" type=\"checkbox\" $checked/></td></tr>";
			$daysofweek->MoveFirst();
			$count++;
		}
	?>
		<!--<tr><td/><td/><td/><td>Select all</td></tr>-->
		</table>
		<!--<p><input type="submit" name="addshift" value="Add Shift"/></p>-->
		<p><input type="submit" name="submit" value="Submit changes"/></p>
		<p><input type="hidden" name="year" value="<? echo $year; ?>"/></p>
		<p><input type="hidden" name="woy" value="<? echo $woy; ?>"/></p>
		<p><input type="hidden" name="qid" value="<? echo $questionnaire_id; ?>"/></p>
		</form>
	<?
}	
	
xhtml_foot();
	
?>
