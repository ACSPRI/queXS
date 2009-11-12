<?
/**
 * Record calls using Asterisk (if enabled)
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
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include("functions/functions.operator.php");


$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);

if (is_on_call($operator_id) == 3)
{
	if (isset($_GET['start']))
	{
		$newtext = T_("Stop REC");
		xhtml_head(T_("Record"),true,array("css/call.css"),array("js/window.js"),"onload='toggleRec(\"$newtext\",\"record.php?stop=stop\",\"online\")'");
		if (is_voip_enabled($operator_id))
		{
			$call_id = get_call($operator_id);
			if ($call_id)
			{
				include("functions/functions.voip.php");
				$v = new voip();
				$v->connect(VOIP_SERVER);
				$v->beginRecord(get_extension($operator_id),"$case_id-$call_id-$operator_id-" . get_operator_time($operator_id,$format = "%Y-%m-%d-%H-%i-%S"));
				print "<p>" . T_("Beginning recording...") .  "</p>";
			}
			else
				print "<p>" . T_("Not on a call, so not beginning a recording") . "</p>";
		}
		else
		{
			print "<p>" . T_("Begin the manual recording now...") .  "</p>";
		}
	}
	else if (isset($_GET['stop']))
	{
		$newtext = T_("Start REC");
		xhtml_head(T_("Record"),true,array("css/call.css"),array("js/window.js"),"onload='toggleRec(\"$newtext\",\"record.php?start=start\",\"offline\")'");
		if (is_voip_enabled($operator_id))
		{
			include("functions/functions.voip.php");
			$v = new voip();
			$v->connect(VOIP_SERVER);
			$v->endRecord(get_extension($operator_id));
			print "<p>" . T_("Stopping recording...") .  "</p>";
		}
		else
		{
			print "<p>" . T_("Stop the manual recording now...") .  "</p>";
		}
	}
}
else
{
	xhtml_head(T_("Record"),true,array("css/call.css"));
	print "<p>" . T_("Not on a call, so not beginning a recording") . "</p>";
}

xhtml_foot();




?>
