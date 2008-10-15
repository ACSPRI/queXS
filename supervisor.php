<?
/**
 * Supervisor help functions
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
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include ("functions/functions.operator.php");

xhtml_head(T_("Supervisor"));

//display introduction text

$operator_id = get_operator_id();

if (is_on_call($operator_id) == 3)
{
	if (VOIP_ENABLED)
	{
		if (isset($_GET['callsupervisor']))
		{
			include("functions/functions.voip.php");
			$v = new voip();
			$v->connect(VOIP_SERVER);
			$v->addParty($operator_id,SUPERVISOR_EXTENSION);
			print "<p>" . T_("Calling the supervisor, you may close this window") .  "</p>";
		}
		else
		{
			//print "<p><a href='?callsupervisor=callsupervisor'>" . T_("Click here to call the supervisor's phone. Otherwise close this window") . "</a></p>";
			print "<p>" . T_("Currently Disabled: Please see your supervisor in person") . "</p>";
		}
	}
	else
	{
		print "<p>" . T_("Try calling the supervisor") .  "</p>";
	}
}
else
{
	print "<p>" . T_("Not on a call, so not calling the supervisor") . "</p>";
}

xhtml_foot();


?>
