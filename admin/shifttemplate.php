<?php 
/**
 * Modify the standard shift template
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

$css = array(
"../include/bootstrap/css/bootstrap.min.css", 
//"../include/bootstrap/css/bootstrap-theme.min.css",
"../include/clockpicker/dist/bootstrap-clockpicker.min.css",
"../css/custom.css"
			);
$js_head = array(
"../include/jquery/jquery.min.js",
"../include/bootstrap/js/bootstrap.min.js",
"../js/addrow-v2.js",
				);
$js_foot = array(
"../include/clockpicker/dist/bootstrap-clockpicker.js",
"../js/custom.js"
				);

global $db;

$year="2008";
$woy="1";

if (isset($_POST['day']))
{
	$db->StartTrans();
	
	$sql = "DELETE FROM shift_template
		WHERE 1";
	$db->Execute($sql);
	
	foreach($_POST['day'] as $key => $val)
	{
		if (!empty($val))
		{
			$val = intval($val);
			$key = intval($key);

			$start = $db->qstr($_POST['start'][$key],get_magic_quotes_gpc());
			$end = $db->qstr($_POST['end'][$key],get_magic_quotes_gpc());

			$sql = "INSERT INTO shift_template(day_of_week,start,end)
				VALUES ($val,$start,$end)";
			$db->Execute($sql);
		}
	}
	$db->CompleteTrans();
}

xhtml_head(T_("Set default shift times"),true,$css,$js_head);//T_("Modify shift template"),array("../css/shifts.css"),array("../js/addrow-v2.js")

$sql = "SELECT CONVERT_TZ(NOW(),'" . get_setting("DEFAULT_TIME_ZONE") . "','UTC') as t";//'Australia/Victoria'

$rs = $db->GetRow($sql);

if (empty($rs) || !$rs || empty($rs['t']))
	print "<div class='alert alert-danger'><a href='http://dev.mysql.com/doc/mysql/en/time-zone-support.html'>" . T_("Your database does not have timezones installed, please see here for details") . "</a></div>";

/**
 * Display warning if timezone data not installed
 *
 */

//print "<h1>" . T_("Set default shift times") . "</h1>";
print "<div class='well'><t>" . T_("Enter standard shift start and end times for each day of the week in local time") . "</t></div>";

/**
 * Begin displaying currently loaded shifts
 */

$sql = "SELECT DATE_FORMAT(STR_TO_DATE(CONCAT('$year',' ','$woy',' ',day_of_week -1),'%x %v %w'), '%W') AS dt,day_of_week,start,end
	FROM shift_template";	
		
$shifts = $db->GetAll($sql);
translate_array($shifts,array("dt"));		
	
$sql = "SELECT DATE_FORMAT(STR_TO_DATE(CONCAT($year,' ',$woy,' ',day_of_week - 1),'%x %v %w'), '%W') as description, day_of_week as value, '' as selected 
	FROM day_of_week";
	
$daysofweek = $db->GetAll($sql);
translate_array($daysofweek,array("description"));	

?>
	<div class="panel-body col-sm-4"><form method="post" action="" class="form-horizontal">
	<table class="table-hover table-condensed " id="shifts"><thead class="text-center highlight">
<?php 
	print "<tr ><th >" . T_("Day") . "</th><th  >" . T_("Start") . "</th><th >" . T_("End") . "</th></tr></thead><tbody>";
	$count = 0;
	foreach($shifts as $shift)
	{
		print "<tr id='row-$count'><td>";// class='row_to_clone' /* these are not the rows to clone...*/
		display_chooser($daysofweek, "day[$count]", false, true, false, false, false, array("description",$shift['dt']));
		print "</td>
		<td><div class=\"input-group clockpicker\"><input readonly class=\"form-control\" size=\"8\" maxlength=\"8\" name=\"start[$count]\" type=\"text\" value=\"{$shift['start']}\"/><span class=\"input-group-addon\"><span class=\"glyphicon glyphicon-time fa\"></span></span></div></td>
		<td><div class=\"input-group clockpicker\"><input readonly class=\"form-control\" size=\"8\" maxlength=\"8\" name=\"end[$count]\" type=\"text\" value=\"{$shift['end']}\"/><span class=\"input-group-addon\"><span class=\"glyphicon glyphicon-time fa\"></span></span></div></td></tr>";
		$count++;
	}
	print "<tr  class='row_to_clone' id='row-$count'><td>";
	display_chooser($daysofweek, "day[$count]", false, true, false, false, false, false);
	print "	</td><td><div class=\"input-group clockpicker\"><input readonly class=\"form-control\" size=\"8\" maxlength=\"8\" name=\"start[$count]\" type=\"text\" value=\"08:00:00\"/><span class=\"input-group-addon\"><span class=\"glyphicon glyphicon-time fa\"></span></span></div></td>
			<td><div class=\"input-group clockpicker\"><input readonly class=\"form-control\" size=\"8\" maxlength=\"8\" name=\"end[$count]\"   type=\"text\"  value=\"20:00:00\"/><span class=\"input-group-addon\"><span class=\"glyphicon glyphicon-time fa\"></span></span></div></td></tr>";

?>
	</tbody></table>
	<a class="btn btn-default btn-sm" onclick="addRow(); return false;" href=""><?php  echo T_("Add row"); ?></a><br/><br/>
	<input class="btn btn-default " type="submit" name="submit" value="<?php  echo T_("Save changes to shifts"); ?>"/>
	</form></div>
<?php 
xhtml_foot($js_foot);
?>
<script type="text/javascript">
$('.clockpicker').clockpicker({
    autoclose: true
});
</script>
