<?php 
/**
 * Update case availability
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
 * @subpackage user
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
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
 * Authentication
 */
include ("auth-interviewer.php");

/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include("functions/functions.operator.php");

$js = false;
if (AUTO_LOGOUT_MINUTES !== false)
        $js = array("include/jquery/jquery-1.4.2.min.js","js/childnap.js");

xhtml_head(T_("Availability"),false,array("css/table.css"),$js);

$operator_id = get_operator_id();
$case_id = get_case_id($operator_id);

if (is_using_availability($case_id))
{
	if (isset($_POST['count']))
	{
		$db->StartTrans();

		$sql = "DELETE FROM case_availability
			WHERE case_id = '$case_id'";

		$db->Execute($sql);

		//if number of selected items != count then add		
		$count = intval($_POST['count']);

		$pcount = 0;
		foreach($_POST as $key => $val)
		{
			if (substr($key,0,2) == "ag") 
				$pcount++;
		}
	
		//if ($pcount != $count)
		//{
			foreach($_POST as $key => $val)
			{
				if (substr($key,0,2) == "ag")
				{
					$sql = "INSERT INTO case_availability (case_id,availability_group_id)
						VALUES ($case_id,'$val')";
					$db->Execute($sql);						
				}
			}
		//}

		$db->CompleteTrans();
	}

	//List all availability group items and whether selected or not (all selected by default unless >= 1 availability group is in use for this case

	$sql = "SELECT qa.availability_group_id,ag.description,ca.availability_group_id as selected_group_id
		FROM `case` as c
		JOIN questionnaire_availability AS qa ON (qa.questionnaire_id = c.questionnaire_id)
		JOIN availability_group AS ag ON (ag.availability_group_id = qa.availability_group_id)
		LEFT JOIN case_availability AS ca ON (ca.availability_group_id = qa.availability_group_id and ca.case_id = c.case_id)
		WHERE c.case_id = '$case_id'";

	$rs = $db->GetAll($sql);

	//See if all are selecetd or not
	$allselected = true;
	$count = count($rs);
	foreach($rs as $r)
	{
		if (!empty($r['selected_group_id']))
		{
			$allselected = false;
			break;
		}
	}

	//Display all availability groups as checkboxes

	print "<p>" . T_("Select groups to limit availability (Selecting none means always available)") .  "</p>";
	print "<form action='?' method='post' id='agform'>";
	foreach ($rs as $r)
	{
		$checked = "";

		//if ($allselected || $r['availability_group_id'] == $r['selected_group_id'])
		if ($r['availability_group_id'] == $r['selected_group_id'])
			$checked = "checked='checked'";

		
		print "	<div><input type='checkbox' name='ag{$r['availability_group_id']}' id='ag{$r['availability_group_id']}'
			value='{$r['availability_group_id']}' $checked onclick='document.forms[\"agform\"].submit();' />
			<label for='ag{$r['availability_group_id']}'>{$r['description']}</label></div>";
	
	}
	print "<input type='hidden' name='count' id='count' value='$count'/></form>";
}
else
{
	print "<p>" . T_("Availability groups not defined for this questionnaire") . "</p>";
}



xhtml_foot();


?>
