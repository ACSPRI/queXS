<?php 
/**
 *  Select an extension before continuing
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
 *
 */

/**
 * Configuration file
 */
include_once("config.inc.php");

/**
 * XHTML functions
 */
include_once("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include_once("functions/functions.operator.php");

$operator_id = get_operator_id();

if (!$operator_id)
  die();

//if already assigned just get straight to it
$sql = "SELECT extension
        FROM `extension`
        WHERE current_operator_id = '$operator_id'";

$e = $db->GetOne($sql);

if (!empty($e))
{
  header('Location: index.php');
  die();
}

if (isset($_POST['extension_id']) && !empty($_POST['extension_id']))
{
  if ($operator_id)
  {
    $e = intval($_POST['extension_id']);

    $sql = "UPDATE `extension`
            SET current_operator_id = $operator_id
            WHERE current_operator_id IS NULL
            AND extension_id = $e";

    $r = $db->Execute($sql);

    if ($r)
    {
      header('Location: index.php');
      die();
    }
  }
}


xhtml_head(T_("queXS"));


$sql = "SELECT e.extension_id as value, e.extension as description
        FROM `extension` as e
        WHERE e.current_operator_id IS NULL";

$ers = $db->GetAll($sql);

if (empty($ers))
{
  print "<p>" . T_("There are no extensions available, please contact the supervisor or click below to try again for an available extension") . "</p>";
  print "<p><a href='?'>" . T_("Try again") . "</a></p>";
}
else
{
  print "<h2>" . T_("Select extension") . "</h2>";
  print "<p>" . T_("Please select your extension from the list below then click on 'Choose extension'") . "</p>";
  
  print "<form action='?' method='post'>";
  print "<label for='extension_id'>" . T_("Extension") . ":</label>";
  display_chooser($ers,"extension_id","extension_id",false,false,false,false);
  print "<p><input type='submit' value='" .T_ ("Choose extension") . "'/></p></form>";
}
xhtml_foot();

?>
