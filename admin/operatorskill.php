<?php 
/**
 * Assign operators to skills in a checkbox matrix
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
 * Authentication file
 */
include ("auth-admin.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Determine if an operator has been assigned to a skill (outcome_type)
 *
 * @param int $operator_id Operator id
 * @param int $outcome_type_id Outcome type id (skill)
 * @return int 1 if assigned to that skill otherwise 0
 *
 */
function vq($operator_id,$outcome_type_id)
{
	global $db;

	$sql = "SELECT operator_id,outcome_type_id
		FROM operator_skill
		WHERE operator_id = '$operator_id' and outcome_type_id = '$outcome_type_id'";

	$vq = $db->Execute($sql);

	if ($vq)
		return $vq->RecordCount();
	else
		return 0;
}

/**
 * Assign an operator to a skill (outcome_type)
 *
 * @param int $operator_id Operator id
 * @param int $outcome_type_id Outcome type id (skill)
 *
 */
function vqi($operator_id,$outcome_type_id)
{
	global $db;

	$sql = "INSERT INTO
		operator_skill (operator_id,outcome_type_id)
		VALUES('$operator_id','$outcome_type_id')";

	$db->Execute($sql);
}

/**
 * Delete a skill (outcome_type) from an operator
 *
 * @param int $operator_id Operator id
 * @param int $outcome_type_id Outcome type id (skill)
 *
 */
function vqd($operator_id,$outcome_type_id)
{
	global $db;

	$sql = "DELETE FROM
		operator_skill	
		WHERE operator_id = '$operator_id' and outcome_type_id = '$outcome_type_id'";

	$db->Execute($sql);
}


if (isset($_POST['submit']))
{
	$db->StartTrans();

	$sql = "DELETE 
		FROM operator_skill
		WHERE operator_id IN (
			SELECT operator_id
			FROM operator
			WHERE enabled = 1)";

	$db->Execute($sql);

	foreach ($_POST as $g => $v)
	{
		$a = explode("_",$g);
		if ($a[0] == "cb")
			vqi($a[2],$a[1]);
	}

	$db->CompleteTrans();
}



$sql = "SELECT outcome_type_id,description
	FROM outcome_type
	ORDER by outcome_type_id ASC";

$outcome_types = $db->GetAll($sql);

$sql = "SELECT operator_id, CONCAT(firstName,' ', lastName) as description, username
	FROM operator
	WHERE enabled = 1
	ORDER by operator_id ASC";

$operators = $db->GetAll($sql);


xhtml_head(T_("Assign operators to Skills"),true,array("../include/bootstrap/css/bootstrap.min.css","../include/iCheck/skins/square/blue.css","../css/custom.css"),array("../include/jquery/jquery.min.js","../include/iCheck/icheck.min.js"));

print "<div class='well'>" . T_("Set which types of cases will be made available to each operator. </br> Please note that all operators will be allowed to assign all possible outcomes to a case. This restricts which ones will be assigned to an operator.") . "</div>";


?>

<script type="text/javascript">

<?php 
print "outcome_type_id = new Array(";

$s = "";

foreach($outcome_types as $q)
{
	$s .= "'{$q['outcome_type_id']}',";
}

$s = substr($s,0,strlen($s) - 1);
print "$s);\n";

print "operator_id = new Array(";

$s = "";

foreach($operators as $q)
{
	$s .= "'{$q['operator_id']}',";
}

$s = substr($s,0,strlen($s) - 1);
print "$s);\n";

?>

var QidOn = 0;
var VidOn = 0;

function checkQid(q)
{
	
	for (y in operator_id)
	{
		v = operator_id[y];

		cb = document.getElementById('cb_' + q + "_" + v);

		if (QidOn == 0)
			cb.checked = 'checked';
		else
			cb.checked = '';
			
	}

	if (QidOn == 0)
		QidOn = 1;
	else
		QidOn = 0;
  
  $('input').iCheck('update');
}


function checkVid(v)
{
	
	for (y in outcome_type_id)
	{
		q = outcome_type_id[y];

		cb = document.getElementById('cb_' + q + "_" + v);

		if (VidOn == 0)
			cb.checked = 'checked';
		else
			cb.checked = '';
			
	}

	if (VidOn == 0)
		VidOn = 1;
	else
    VidOn = 0;
  
  $('input').iCheck('update');
}

</script>

<?php

	
//$country_code = 'RU'; // Nederlandse Antillen
//print_r(DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code));


print "<form action=\"\" method=\"post\"><table class='table-bordered table-hover table-condensed form-group'><thead>";

print "<tr><th>&emsp;" . T_("Username") . "&emsp;</th><th>&emsp;" . T_("Operator") . "&emsp;</th>";
foreach($outcome_types as $q)
{
	print "<th>&emsp;<a href=\"javascript:checkQid({$q['outcome_type_id']})\">" . T_($q['description']) . "</a>&emsp;</th>";
}
print "</tr></thead>";


foreach($operators as $v)
{
	print "<tr class=''>
		<th>&emsp;{$v['username']}&emsp;</th>
		<th>&emsp;<a href=\"javascript:checkVid({$v['operator_id']})\">{$v['description']}</a>&emsp;</th>";
	foreach($outcome_types as $q)
	{
		$checked = "";
		if (vq($v['operator_id'],$q['outcome_type_id'])) $checked="checked=\"checked\"";
		print "<td class='text-center'><input type=\"checkbox\" name=\"cb_{$q['outcome_type_id']}_{$v['operator_id']}\" id=\"cb_{$q['outcome_type_id']}_{$v['operator_id']}\" $checked></input></td>";
	}

	print "</tr>";
}


print "</table><input type=\"submit\" class='btn btn-default fa' name=\"submit\" value=\"" . T_("Modify operator skills") ."\"/></form>";

?>

<script type="text/javascript">
$('input').iCheck({
	checkboxClass: 'icheckbox_square-blue',
	increaseArea: '30%'
});
</script>

<?php
xhtml_foot();
