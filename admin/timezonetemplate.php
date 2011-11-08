<?
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


if (isset($_GET['time_zone']))
{
	//need to add sample to questionnaire
	
	$tz = $db->qstr($_GET['time_zone'],get_magic_quotes_gpc());

	$sql = "INSERT INTO timezone_template(Time_zone_name)
		VALUES($tz)";

	$db->Execute($sql);

}

if (isset($_GET['tz']))
{
	//need to remove rsid from questionnaire

	$tz = $db->qstr($_GET['tz'],get_magic_quotes_gpc());

	$sql = "DELETE FROM timezone_template
		WHERE Time_zone_name = $tz";

	$db->Execute($sql);
}


xhtml_head(T_("Add/Remove Timezones"),true,array("../css/shifts.css"),array("../js/window.js"));

$sql = "SELECT name as value, name as description, 
		CASE WHEN name LIKE '" . DEFAULT_TIME_ZONE . "' THEN 'selected=\'selected\'' ELSE '' END AS selected
	FROM mysql.time_zone_name";

$tzl = $db->GetAll($sql);

if (empty($tzl) || !$tzl)
{
        print "<div class='warning'><a href='http://dev.mysql.com/doc/mysql/en/time-zone-support.html'>" . T_("Your database does not have timezones installed, please see here for details") . "</a></div>";
}


print "<h1>" . T_("Click to remove a Timezone from the default list") . "</h1>";

$sql = "SELECT Time_zone_name
	FROM timezone_template";

$qs = $db->GetAll($sql);

foreach($qs as $q)
{
	print "<p><a href=\"?tz={$q['Time_zone_name']}\">{$q['Time_zone_name']} </a></p>";
}

print "<h1>" . T_("Add a Timezone:") . "</h1>";
		?>
		<form action="" method="get"><p>
		<label for="time_zone"><? echo T_("Timezone: "); ?></label><? display_chooser($tzl, 'time_zone', 'time_zone', false,  false, false, false, false); ?>
		<input type="submit" name="add_timezone" value="<? echo T_("Add Timezone"); ?>"/></p>
		</form>
		<?
xhtml_foot();


?>
