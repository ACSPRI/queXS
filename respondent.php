<?php 
/**
 * Display a list of respondents for this case, and the ability to add them if necessary
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
 * Calendar functions
 */
include("functions/functions.calendar.php");

/**
 * Operator functions
 */
include("functions/functions.operator.php");

/**
 * Input functions
 */
include("functions/functions.input.php");

$js = array("js/window.js","js/showhide.js");

if (AUTO_LOGOUT_MINUTES !== false)
{
        $js[] = "include/jquery-ui/js/jquery-1.4.2.min.js";
	$js[] = "js/childnap.js";
}

xhtml_head(T_("Respondent Selector"),true,array("css/table.css","css/respondent.css"),$js);


//display the respondents as a drop down list for this call attempt
// display in the operators time


global $db;

$db->StartTrans();

$operator_id = get_operator_id();
$call_attempt_id = get_call_attempt($operator_id,false);
$case_id = get_case_id($operator_id);

if (isset($_POST['submit']))
	add_respondent($case_id,$_POST['firstName'],$_POST['lastName'],$_POST['Time_zone_name']);


if (isset($_GET['respondent_id']) && $_GET['respondent_id'] == 0)
{
?>
	<form method="post" action="?">
	<?php  display_respondent_form(false,$case_id); ?>
	<div><input type='submit' name='submit' id='submit' value="<?php  echo T_("Add respondent"); ?>"/></div>
	</form>
	<div><a href="?"><?php  echo T_("Go back"); ?></a></div>

<?php 
}
else
{
	print "<div>" . T_("Case id:") . " $case_id</div>";
	print "<div>" . T_("Respondent:") . "</div>";

	if (isset($_GET['respondent_id']) && $_GET['respondent_id'] != 0)
	{
		$respondent_id = bigintval($_GET['respondent_id']);
	
		$sql = "UPDATE `call_attempt` 
			SET respondent_id = '$respondent_id'
			WHERE call_attempt_id = '$call_attempt_id'";
	
		$db->Execute($sql);
	}

	/* List respondents
	 *
	 */


	$sql = "SELECT r.firstName, r.lastName, r.respondent_id,r.Time_zone_name,CASE WHEN c.respondent_id = r.respondent_id THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM respondent AS r
		LEFT JOIN call_attempt AS c ON ( c.call_attempt_id = '$call_attempt_id' )
		WHERE r.case_id = '$case_id'";
		
	$rs = $db->GetAll($sql);


	$timezone = "";
	if (ALLOW_RESPONDENT_SELECTOR)
	{
		print "<div><select id='respondent' name='respondent' onchange=\"LinkUp('respondent')\"><option value='?respondent_id=0' class='addresp'>" . T_("Add respondent") . "</option>";
		if (!empty($rs))
		{
			foreach($rs as $r)
			{
				if (!empty($r['selected'])) $timezone = $r['Time_zone_name'];
				print "<option value='?respondent_id={$r['respondent_id']}' {$r['selected']}>{$r['firstName']} {$r['lastName']}</option>";
			}

		}
		print "</select></div>";
	}
	else
	{
		if (isset($rs[0]))
			print "<div>" . $rs[0]['firstName'] . " " . $rs[0]['lastName'] . "</div>";
	}


	print "<div>$timezone</div>";



	//display sample details
	//limit to those allowed by admin
	$sql = "SELECT sivr.var,sv.val
			FROM `sample_var` as sv, `sample_import_var_restrict` as sivr, `case` as c
			WHERE c.case_id = '$case_id'
			AND sv.sample_id = c.sample_id
			AND sivr.var_id = sv.var_id
			AND (sivr.restrict IS NULL OR sivr.restrict = 0)";
	
	$rs = $db->GetAll($sql);

	print "<div><a id='link1' href=\"javascript:showHide('details','link1');\">" . T_("Show details") . "</a></div>";
	print "<div id='details'>";
	if (!empty($rs))
	{
		xhtml_table($rs,array("var","val"),array(T_("Var"),T_("Value")));
	}
	print "</div>";

}	

xhtml_foot();

$db->CompleteTrans();
?>
