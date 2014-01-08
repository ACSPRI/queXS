<?php 
/**
 * Display the end work screen to the operator
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
 * @todo integrate with users system: have a forwarding URL?
 * 
 */

/**
 * Language file
 */
include_once("lang.inc.php");


/**
 * XHTML functions
 */
include_once("functions/functions.xhtml.php");

xhtml_head(T_("End of work"));

if (isset($_GET['auto']))
{
	print "<h1>" . T_("You have been automatically logged out of work due to inactivity") . "</h1>";
}

print "<h1>" . T_("Work has ended. That is it") . "</h1>";

if (ALLOW_OPERATOR_EXTENSION_SELECT && VOIP_ENABLED)
{
  //unassign extension
  include_once("functions/functions.operator.php");
  $operator_id = get_operator_id();

  if (get_case_id($operator_id) == false && is_voip_enabled($operator_id))
  {
    $sql = "UPDATE `extension`
            SET current_operator_id = NULL
            WHERE current_operator_id = $operator_id";

    $rs = $db->Execute($sql);

    if ($rs)
    {
      print "<p>" . T_("You have been unassigned from your extension") ."</p>";
    }
  }  
}

print "<p><a href='index.php'>" . T_("Go back to work") . "</a></p>";

xhtml_foot();



?>
