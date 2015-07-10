<?php 
/**
 * Assign clients to questionnaires in a checkbox matrix
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
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Return if an client has already been assigned to this questionnaire
 *
 * @param int $client Client id
 * @param int $questionnaire_id Questionnaire id
 * @return int 1 if assigned otherwise 0
 *
 */
function vq($client_id,$questionnaire_id)
{
	global $db;

	$sql = "SELECT client_id,questionnaire_id
		FROM client_questionnaire
		WHERE client_id = '$client_id' and questionnaire_id = '$questionnaire_id'";

	$vq = $db->Execute($sql);

	if ($vq)
		return $vq->RecordCount();
	else
		return 0;
}

/**
 * Assign an client to a questionnaire
 *
 * @param int $client_id Client id
 * @param int $questionnaire_id Questionnaire id
 *
 */
function vqi($client_id,$questionnaire_id)
{
	global $db;

	$sql = "INSERT INTO
		client_questionnaire (client_id,questionnaire_id)
		VALUES('$client_id','$questionnaire_id')";

	$db->Execute($sql);
}


/**
 * Unassign an client from a questionnaire
 *
 * @param int $client_id Client id
 * @param int $questionnaire_id Questionnaire id
 *
 */
function vqd($client_id,$questionnaire_id)
{
	global $db;

	$sql = "DELETE FROM
		client_questionnaire	
		WHERE client_id = '$client_id' and questionnaire_id = '$questionnaire_id'";

	$db->Execute($sql);
}


if (isset($_POST['submit']))
{
	$db->StartTrans();

	$sql = "DELETE 
		FROM client_questionnaire
		WHERE questionnaire_id IN (
			SELECT questionnaire_id
			FROM questionnaire	
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


$sql = "SELECT questionnaire_id,description
	FROM questionnaire
	WHERE enabled = 1
	ORDER by questionnaire_id ASC";

$questionnaires = $db->GetAll($sql);

$sql = "SELECT client_id, CONCAT(firstName,' ', lastName ) as description, username
	FROM client
	ORDER by client_id ASC";

$clients = $db->GetAll($sql);


xhtml_head(T_("Assign clients to questionnaires"),true,array("../include/bootstrap/css/bootstrap.min.css","../include/iCheck/skins/square/blue.css","../css/custom.css"),array("../include/jquery/jquery.min.js","../include/iCheck/icheck.min.js"));

?>

<script type="text/javascript">

<?php 
print "questionnaire_id = new Array(";

$s = "";

foreach($questionnaires as $q)
{
	$s .= "'{$q['questionnaire_id']}',";
}

$s = substr($s,0,strlen($s) - 1);
print "$s);\n";

print "client_id = new Array(";

$s = "";

foreach($clients as $q)
{
	$s .= "'{$q['client_id']}',";
}

$s = substr($s,0,strlen($s) - 1);
print "$s);\n";

?>

var QidOn = 0;
var VidOn = 0;

function checkQid(q)
{
	
	for (y in client_id)
	{
		v = client_id[y];

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
}


function checkVid(v)
{
	
	for (y in questionnaire_id)
	{
		q = questionnaire_id[y];

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
}

</script>

</head>
<body>


<?php 

print "<form action=\"\" method=\"post\" class=''><table class='table-bordered table-hover table-condensed form-group'><thead>";

print "<tr><th>&emsp;" . T_("Username") . "&emsp;</th><th>&emsp;" . T_("Client") . "&emsp;</th>";
foreach($questionnaires as $q)
{
	print "<th><a href=\"javascript:checkQid({$q['questionnaire_id']})\">{$q['description']}</a></th>";
}
print "</tr></thead>";


foreach($clients as $v)
{
	print "<tr class=''>
			<th>&emsp;{$v['username']}&emsp;</th>
			<th>&emsp;<a href=\"javascript:checkVid({$v['client_id']})\">{$v['description']}</a>&emsp;</th>";
	foreach($questionnaires as $q)
	{
		$checked = "";
		if (vq($v['client_id'],$q['questionnaire_id'])) $checked="checked=\"checked\"";
		print "<td class='text-center'><input type=\"checkbox\" name=\"cb_{$q['questionnaire_id']}_{$v['client_id']}\" id=\"cb_{$q['questionnaire_id']}_{$v['client_id']}\" $checked></input></td>";
	}

	print "</tr>";
}


print "</table><input type=\"submit\" class='btn btn-default fa' name=\"submit\" value=\"" . T_("Assign clients to questionnaires") . "\"/></form>";


xhtml_foot();

?>

<script type="text/javascript">
$('input').iCheck({
	checkboxClass: 'icheckbox_square-blue',
	increaseArea: '30%'
});
</script>
