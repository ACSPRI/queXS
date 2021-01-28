<?php 
/**
 * Modify the default timezones
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
 *
 */

/**
 * Configuration file
 */
include("../config.inc.php");

/**
 * Database file
 */
include ("../db.inc.php");

/**
 * Authentication file
 */
require ("auth-admin.php");

/**
 * XHTML functions
 */
include("../functions/functions.xhtml.php");

/**
 * Display functions
 */
include("../functions/functions.display.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

global $db;

if (isset($_POST['dtime_zone']))
{
  set_setting('DEFAULT_TIME_ZONE', $_POST['dtime_zone']);
  
}

if (isset($_GET['time_zone']))
{
	//need to add sample to questionnaire
	
	$tz = $db->qstr($_GET['time_zone'],0);

	$sql = "INSERT INTO timezone_template(Time_zone_name)
		VALUES($tz)";

	$db->Execute($sql);

}

if (isset($_GET['tz']))
{
	//need to remove rsid from questionnaire

	$tz = $db->qstr($_GET['tz'],0);

	$sql = "DELETE FROM timezone_template
		WHERE Time_zone_name = $tz";

	$db->Execute($sql);
}


xhtml_head(T_("Set Timezones"),true,array("../include/bootstrap/css/bootstrap.min.css","../css/custom.css"),array("../js/window.js")); //,"../include/bootstrap/css/bootstrap-theme.min.css"

$dtz = get_setting("DEFAULT_TIME_ZONE");

$sql = "SELECT name as value, name as description, 
		CASE WHEN name LIKE '$dtz' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM mysql.time_zone_name";

$tzl = $db->GetAll($sql);

if (empty($tzl) || !$tzl)
{
        print "<div class='alert alert-danger'>" . T_("Your database does not have timezones installed, please see here for details") . "<a href='http://dev.mysql.com/doc/mysql/en/time-zone-support.html'> ... </a></br>" .T_("or") . "</br>" . T_("Check that you have granted relevant permissions on 'time_zone_...' tables in database named 'mysql'.") . "</div>";
}

print "<div class='col-sm-4 '><h3 class=''>" . T_("Default Timezone: ") . "&emsp;<b class='text-primary'>$dtz</b></h3>";//<div class='panel-body'>
		?>
		<form action="" method="post" class="form-horizontal">
		<?php  display_chooser($tzl, 'dtime_zone', 'dtime_zone',false,false,false,true,false,true,"form-inline pull-left"); ?>&emsp;
		<input type="submit" class='btn btn-default fa' name="set_dtimezone" value="<?php  echo T_("Set default timezone"); ?>"/>
		</form>
		<?php 
print "</div>";

print "<div class='col-sm-5'><h3>" . T_("Timezone list") . "</h3>";

$sql = "SELECT Time_zone_name, TIME_FORMAT(CONVERT_TZ(NOW(),'System',Time_zone_name),'". TIME_FORMAT ."') as time, CONCAT('<p class=\'text-center\' style=\'margin-bottom: 3px;\'><b class=\'label label-default\' style=\'font-size:85%;\'>', TIME_FORMAT(TIMEDIFF( CONVERT_TZ(NOW(),'$dtz','$dtz'),CONVERT_TZ(NOW(), Time_zone_name,'$dtz')),' %H : %i'), '</b></p>') AS timediff,
CONCAT('<a href=\"?tz=', Time_zone_name ,'\" title=\"" . T_("Remove Timezone") . "\"><i class=\"fa fa-trash fa-lg text-danger\">" . T_("Remove") . "</i></a>') as link
	FROM timezone_template ORDER BY time ASC";

$qs = $db->GetAll($sql);
		xhtml_table($qs, array("Time_zone_name","timediff","time","link"), array(T_("Timezone name"),T_("Time diff to Default Time zone"),T_("Current time"),T_("Remove")));
print "</div>";

print "<div class='col-sm-3'><h3 class=''>" . T_("Add a Timezone:") . "&emsp;</h3>";
		?>
		<form action="" method="get" class="form-horizontal">
		<?php  display_chooser($tzl, 'time_zone', 'time_zone',false,false,false,true,false,true,"form-inline pull-left"); ?>&emsp;
		<input type="submit" class='btn btn-default fa' name="add_timezone" value="<?php  echo T_("Add Timezone"); ?>"/>
		</form>
		<?php 
print "</div>";

xhtml_foot();
?>
