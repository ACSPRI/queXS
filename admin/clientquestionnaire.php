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
 * Authentication file
 */
require ("auth-admin.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

$msg = "";

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
 * @param int $lime_sid Lime survey ID
 * @param int $uid  Lime user ID
 *
 */
function vqi($client_id,$questionnaire_id,$lime_sid,$uid)
{
	global $db;
	
	$db->StartTrans();

	$sql = "INSERT INTO
		client_questionnaire (client_id,questionnaire_id)
		VALUES('$client_id','$questionnaire_id')";

	$db->Execute($sql);
	
	/* Add client questionnaire permissions to view Lime results + statistics and quotas,  //preserve superadmin permissions */
	if ($uid != 1 && empty($db->GetAll("SELECT * FROM " . LIME_PREFIX . "survey_permissions WHERE `sid` = '$lime_sid' AND `uid` = '$uid'")))
	{
		$sql = "INSERT INTO " . LIME_PREFIX . "survey_permissions (`sid`,`uid`,`permission`,`create_p`,`read_p`,`update_p`,`delete_p`,`import_p`,`export_p`)
              VALUES ($lime_sid,$uid,'survey',0,1,0,0,0,0),($lime_sid,$uid,'statistics',0,1,0,0,0,0),($lime_sid,$uid,'quotas',0,1,0,0,0,0)";
		$db->Execute($sql);
	}
	
	$db->CompleteTrans();
}


if (isset($_POST['submit']))
{
	$db->StartTrans();
	
	/* Unassign a client from a questionnaire , remove survey_permissions*/
	$sql = "DELETE FROM client_questionnaire
		WHERE questionnaire_id IN ( SELECT questionnaire_id FROM questionnaire WHERE enabled = 1)";
	$db->Execute($sql);
/*Currently disabled -> need to decide how to manage permissions set earlier*/	
/* 	$questionnaires = $db->GetAll("SELECT lime_sid FROM questionnaire WHERE enabled = 1");
	
	$clients = $db->GetAll("SELECT uid FROM client, " . LIME_PREFIX . "users WHERE `users_name` = `username`");

	foreach($questionnaires as $q){
		foreach($clients as $v){
			$sql = "DELETE FROM " . LIME_PREFIX . "survey_permissions WHERE `uid` = {$v['uid']} AND `sid`={$q['lime_sid']} AND `uid` != 1";
			$db->Execute($sql);
		}
	} */
	/* - end - */

	foreach ($_POST as $g => $v)
	{
		$a = explode("_",$g);
		if ($a[0] == "cb")
			vqi($a[2],$a[1],$a[3],$a[4]);
	}

	$db->CompleteTrans();
}

/* delete client from quexs and lime tables*/  //requires data-toggle-confirmation to finalize
if (isset($_GET['delete']) && isset($_GET['uid']) && isset($_GET['uname']))
{
	$client_id = intval($_GET['delete']);
	$uid = intval($_GET['uid']);
	$uname = $_GET['uname'];

	global $db;

	if ($uid !=1){ //double protect superadmin from being deleted
		
		$db->StartTrans();
		
		$sql = "DELETE FROM " . LIME_PREFIX . "templates_rights WHERE `uid` = '$uid' AND `uid` != 1";
		$db->Execute($sql);
		
		$sql = "DELETE FROM " . LIME_PREFIX . "survey_permissions WHERE `uid` = '$uid' AND `uid` != 1";
		$db->Execute($sql);
		
		$sql = "DELETE FROM " . LIME_PREFIX . "user_in_groups WHERE `uid` = '$uid' AND `uid` != 1";
		$db->Execute($sql);
		
		$sql = "DELETE FROM " . LIME_PREFIX . "users WHERE `uid` = '$uid' AND `uid` != 1";
		$db->Execute($sql);
		
		$sql = "DELETE FROM `client_questionnaire` WHERE `client_id` = '$client_id' ";
		$db->Execute($sql);
		
		$sql = "DELETE FROM `client` WHERE `client_id` = '$client_id'";
		$db->Execute($sql);
		
		$db->CompleteTrans();
	}
	
	if ($db->CompleteTrans()) $msg = "<p class='alert alert-info'>". T_("Client with username $uname deleted") . "</p>";
	else $msg = "<p class='alert alert-warning'>". T_("ERROR deleting client with username $uname") . "</p>";
	
	unset($_GET['delete'], $_GET['uid'], $_GET['uname'], $client_id, $username, $uid);
}


$sql = "SELECT questionnaire_id,description, lime_sid
	FROM questionnaire
	WHERE enabled = 1
	ORDER by questionnaire_id ASC";

$questionnaires = $db->GetAll($sql);

$sql = "SELECT client_id, CONCAT(firstName,' ', lastName ) as description, username, uid
	FROM client, " . LIME_PREFIX . "users 
	WHERE `users_name` = `username`
	ORDER by client_id ASC";

$clients = $db->GetAll($sql);


xhtml_head(T_("Clients and questionnaires"),true,array("../include/bootstrap/css/bootstrap.min.css","../include/font-awesome/css/font-awesome.css","../include/iCheck/skins/square/blue.css","../css/custom.css"),array("../include/jquery/jquery.min.js","../include/iCheck/icheck.min.js"));

if (!empty($msg)) print $msg;

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

  $('input').iCheck('update');
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

  $('input').iCheck('update');
}

</script>

<?php 

print "<form action=\"\" method=\"post\" class=''><table class='table-bordered table-hover table-condensed form-group'><thead>";

print "<tr><th>&emsp;" . T_("Username") . "&emsp;</th><th>&emsp;" . T_("Client") . "&emsp;</th>";
foreach($questionnaires as $q)
{
	print "<th><a href=\"".LIME_URL."admin/admin.php?sid={$q['lime_sid']}&amp;action=surveysecurity\" title=\"". T_("NOTICE!  Please, check your user righs to edit client permissions or contact your superviser.") ."\"class=\"btn btn-default btn-sm btn-lime\" >" . T_("Questionnaire permissions") . "</a>
			</br>&emsp;<a href=\"javascript:checkQid({$q['questionnaire_id']})\">{$q['description']}</a>
			</th>";
}
print "</tr></thead>";


foreach($clients as $v)
{
	print "<tr class=''>
			<th>&emsp;{$v['username']}&emsp;<div class=\"pull-right\">
				<a href=\"?delete={$v['client_id']}&amp;uid={$v['uid']}&amp;uname={$v['username']}\" ><i class='fa fa-fw fa-trash-o fa-lg text-danger' data-toggle='tooltip' title=\"" . T_("Delete") . " {$v['username']} ?\"></i></a>&emsp;
				<a href=\"clients.php?edit={$v['client_id']}\" ><i class='fa fa-fw fa-edit fa-lg' data-toggle='tooltip' title=\"" . T_("Edit") . " {$v['username']}\"></i></a>&ensp;</div></th>
			<th>&emsp;<a href=\"javascript:checkVid({$v['client_id']})\">{$v['description']}</a>&emsp;</th>";

	foreach($questionnaires as $q)
	{
		
		if (vq($v['client_id'],$q['questionnaire_id'])) $checked="checked=\"checked\""; else $checked = "";
		print "<td class='text-center'>&emsp;
			<input type=\"checkbox\" name=\"cb_{$q['questionnaire_id']}_{$v['client_id']}_{$q['lime_sid']}_{$v['uid']}\" id=\"cb_{$q['questionnaire_id']}_{$v['client_id']}\" $checked/>&emsp;</td>";
	}

	print "</tr>";
}


print "</table><input type=\"submit\" class='btn btn-primary' name=\"submit\" value=\"" . T_("Assign clients to questionnaires") . "\"/></form>";

?>
<script type="text/javascript">
$('input').iCheck({
	checkboxClass: 'icheckbox_square-blue',
	increaseArea: '30%'
});
</script>
<?php

xhtml_foot();
