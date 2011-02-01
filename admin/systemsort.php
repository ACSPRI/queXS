<?
/**
 * Run the system wide case sorting process and monitor it's progress
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2011
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
 * Process
 */
include ("../functions/functions.process.php");

/**
 * XHTML functions
 */
include("../functions/functions.xhtml.php");



if (isset($_GET['watch']))
{
	//start watching process
	start_process(realpath(dirname(__FILE__) . "/systemsortprocess.php"),2);
}

$p = is_process_running(2);


if ($p)
{
	if (isset($_GET['kill']))
	{
		if ($_GET['kill'] == "force")
			end_process($p);
		else
			kill_process($p);
	}

	xhtml_head(T_("Monitor system wide case sorting"),true,false,false,false,false,true);

	print "<h1>" . T_("Running process:") . " $p</h1>";

	if (is_process_killed($p))
	{
		print "<h3>" . T_("Kill signal sent: Please wait...") . "</h3>";
		print "<p><a href='?kill=force'>" . T_("Process is already closed (eg. server was rebooted) - click here to confirm") . "</a></p>";
	}
	else
	{
		print "<p><a href='?kill=kill'>" . T_("Kill the running process") . "</a></p>";
	}

	print process_get_data($p);
}
else
{
	xhtml_head(T_("Monitor system wide case sorting"));
	print "<h2>" . T_("Monitor system wide case sorting") . "</h2>";
	print "<p><a href='?watch=watch'>" . T_("Click here to enable and begin system wide case sorting") . "</a></p>";
	print "<p>"  . T_("System wide case sorting is periodically (via SYSTEM_SORT_MINUTES configuration directive) sorting cases on a system wide basis instead of finding the most appropriate case each time an operator requests a new case. This may increase performance where there are a large number of cases or complex quotas in place. If you are not experiencing any performance problems, it is not recommended to use this feature.") . "</p>";
	print "<h2>" . T_("Outcome of last process run (if any)") . "</h2>";
	print process_get_last_data(2);
}
xhtml_foot();

?>
