<?php 
/**
 * Modify availability within this availability group
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
 * @copyright Australian Consortium for Social and Political Research Inc (ACSPRI) 2011
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
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

if (isset($_GET['availability_group']))
	$availability_group = intval($_GET['availability_group']);
else if (isset($_POST['availability_group']))
	$availability_group = intval($_POST['availability_group']);
else
	die(T_("No time slot group set"));


if (isset($_POST['day']))
{
	$db->StartTrans();
	
	$sql = "DELETE FROM availability
		WHERE availability_group_id = $availability_group";
	$db->Execute($sql);
	
	foreach($_POST['day'] as $key => $val)
	{
		if (!empty($val))
		{
			$val = intval($val);
			$key = intval($key);

			$start = $db->qstr($_POST['start'][$key],get_magic_quotes_gpc());
			$end = $db->qstr($_POST['end'][$key],get_magic_quotes_gpc());

			$sql = "INSERT INTO availability (day_of_week,start,end,availability_group_id)
				VALUES ('$val',$start,$end,$availability_group)";
			$db->Execute($sql);
		}
	}

	$sql = "UPDATE availability_group
		SET description = " . $db->qstr($_POST['description']) .  "
		WHERE availability_group_id=  $availability_group";
	$db->Execute($sql);
	$db->CompleteTrans();
}

xhtml_head(T_("Modify time slots"),true,$css,$js_head);//,true,array("../css/shifts.css"),array("../js/addrow-v2.js")

/**
 * Display warning if timezone data not installed
 *
 */

$sql = "SELECT CONVERT_TZ(NOW(),'" . DEFAULT_TIME_ZONE . "','UTC') as t";//'Australia/Victoria'
$rs = $db->GetRow($sql);

if (empty($rs) || !$rs || empty($rs['t']))
	print "<div class='alert alert-danger'><a href='http://dev.mysql.com/doc/mysql/en/time-zone-support.html'>" . T_("Your database does not have timezones installed, please see here for details") . "</a></div>";


print "<div><a class='btn btn-default' href='availabilitygroup.php'>" . T_("Go back") . "</a></div><br/>";


$sql = "SELECT description 
	FROM availability_group
	WHERE availability_group_id = $availability_group";

$rs = $db->GetRow($sql);

$description = $rs['description'];

print "<h3>" . T_("Time slot") . " :&emsp;<span class = 'text-primary '>" . $rs['description'] . "</span></h3>";

/**
 * Begin displaying currently loaded restriction times
 */

$sql = "SELECT DATE_FORMAT( STR_TO_DATE( CONCAT( '$year', ' ', '$woy', ' ', day_of_week -1 ) , '%x %v %w' ) , '%W' ) AS dt,day_of_week,start,end
	FROM availability
	WHERE availability_group_id = $availability_group";	
		
$availabilitys = $db->GetAll($sql);
translate_array($availabilitys,array("dt"));	
	
$sql = "SELECT DATE_FORMAT(STR_TO_DATE(CONCAT($year, ' ',$woy,' ',day_of_week - 1),'%x %v %w'), '%W') as description, day_of_week as value, '' as selected 
	FROM day_of_week";
	
$daysofweek = $db->GetAll($sql);
translate_array($daysofweek,array("description"));	
	
?>
	<div class=" panel-body col-sm-4"><form method="post" action="">
	<p><label class="control-label"for="description"><?php echo T_("Edit Time slot group name");  ?>: </label><input class="form-control" type="text" name="description" id="description" value="<?php echo $description;?>"/></p>
	<table class="table-hover table-condensed "><thead class="highlight">
<?php 

	print "<div class='well text-info'>" . T_("Enter the start and end times for each day of the week to restrict calls within") . "</div>";

	print "<tr><th>" . T_("Day") . "</th><th>" . T_("Start") . "</th><th>" . T_("End") . "</th></tr></thead><tbody>";
	$count = 0;
	foreach($availabilitys as $availability)
	{
		print "<tr id='row-$count' ><td>";//class='row_to_clone'
		display_chooser($daysofweek, "day[$count]", false, true, false, false, false, array("description",$availability['dt']));
		print "</td><td><input class=\"form-control clockpicker\" size=\"8\" name=\"start[$count]\" maxlength=\"8\" type=\"text\" value=\"{$availability['start']}\"/></td><td><input class=\"form-control clockpicker\" name=\"end[$count]\" type=\"text\" size=\"8\" maxlength=\"8\" value=\"{$availability['end']}\"/></td></tr>";
		$count++;
	}
	print "<tr class='row_to_clone' id='row-$count'><td>"; 
	display_chooser($daysofweek, "day[$count]", false, true, false, false, false, false);
	print "</td><td><input class=\"form-control clockpicker\" size=\"8\" name=\"start[$count]\" maxlength=\"8\" type=\"text\" value=\"08:00:00\"/></td><td><input class=\"form-control clockpicker\" name=\"end[$count]\" type=\"text\" size=\"8\" maxlength=\"8\" value=\"20:00:00\"/></td></tr>";


?>
	</tbody></table>
	<a class="btn btn-default btn-sm" onclick="addRow(); return false;" href="#"><?php  echo T_("Add row"); ?></a><br/><br/>
	<input class="btn btn-primary" type="submit" name="submit" value="<?php  echo T_("Save changes to time slot group"); ?>"/>
	<input type="hidden" name="availability_group" value="<?php  echo $availability_group;?>"/>
	</form><br/><br/>
	<form method="post" action="availabilitygroup.php">
	<input class="btn btn-danger pull-right" type="submit" name="subdel" value="<?php  echo T_("Delete this time slot group"); ?>"/>
	<input type="hidden" name="availability_group" value="<?php  echo $availability_group;?>"/>
	</form></div>
<?php 
xhtml_foot($js_foot);
?>
<script type="text/javascript">
$('.clockpicker').clockpicker({
    autoclose: true
});
</script>
