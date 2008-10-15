<?
/**
 * Display notes for this case and the ability to add notes
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
include("functions/functions.operator.php");


xhtml_head(T_("Case Notes"),true,array("css/table.css","css/casenote.css"),false,  (isset($_GET['add'])) ? "onload=\"document.getElementById('note').focus();\"" : false);

//List the case note history
// display in the operators time

if (isset($_GET['add']))
{
?>
	<form method="post" action="?">
		<p>
		<input type="text" class="textclass" name="note" id="note"/><input class="submitclass" type="submit" name="submit" value="<? echo T_("Add note"); ?>"/>
		</p>
	</form>
	<p><a href="?"><? echo T_("Go back"); ?></a></p>

<?
}
else
{
	global $db;
	
	$operator_id = get_operator_id();
	$case_id = get_case_id($operator_id);
	
	if ($case_id)
	{
		if (isset($_POST['note']))
		{
			$note = $db->qstr($_POST['note']);
		
			$sql = "INSERT INTO `case_note` (case_note_id,case_id,operator_id,note,datetime)
				VALUES (NULL,'$case_id','$operator_id',$note,CONVERT_TZ(NOW(),'System','UTC'))";
		
			$db->Execute($sql);
		}
	
	
	
		$sql = "SELECT DATE_FORMAT(CONVERT_TZ(c.datetime,'UTC',op.Time_zone_name),'".DATE_TIME_FORMAT."') as time, op.firstName, op.lastName, c.note as note
			FROM `case_note` as c
			JOIN (operator as op) on (c.operator_id = op.operator_id)
			WHERE c.case_id = '$case_id'
			ORDER BY c.datetime DESC";
		
		
		$rs = $db->GetAll($sql);
		
		print "<div><a href=\"?add=add\">" . T_("Add note") . "</a></div>";
		
		if (empty($rs))
			print "<p>" . T_("No notes") . "</p>";
		else
			xhtml_table($rs,array("time","firstName","note"),array(T_("Date/Time"),T_("Operator"),T_("Note")));
	}
	else
		print "<p>" . T_("No case") . "</p>";
}	

xhtml_foot();


?>
