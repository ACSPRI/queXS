<?php 
/**
 * Create an operator and link to a webserver username for authentication
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


global $db;

$a = false;

if (isset($_POST['operator']))
{
	$operator = $db->qstr($_POST['operator'],get_magic_quotes_gpc());
	$firstname = $db->qstr($_POST['firstname'],get_magic_quotes_gpc());
	$lastname = $db->qstr($_POST['lastname'],get_magic_quotes_gpc());
	$chat_user = $db->qstr($_POST['chat_user'],get_magic_quotes_gpc());
	$chat_password = $db->qstr($_POST['chat_password'],get_magic_quotes_gpc());
	$time_zone_name = $db->qstr($_POST['Time_zone_name'],get_magic_quotes_gpc());
	$extension = "";
	if (FREEPBX_PATH != false)
	{
		//Generate new extension from last one in database and random password
		$sql = "SELECT SUBSTRING_INDEX(extension, '/', -1) as ext
			FROM extension
			ORDER BY ext DESC
			LIMIT 1";
		
		$laste = $db->GetRow($sql);

		$extensionn = "1000";
		$extension = "'IAX2/1000'";		

		//increment if exists
		if (!empty($laste))
		{
			$extensionn = $laste['ext'] + 1;
			$extension = "'IAX2/$extensionn'";
		}

		//generate random 8 length password
		$extensionnp = "";
		$length = 12;
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		for ($i = 0; $i < $length; $i++) 
			$extensionnp .= $chars[(rand() % strlen($chars))];

		//quote for SQL
		$extensionp = "'$extensionnp'";

	}
	$supervisor = 0;
	$temporary = 0;
	$refusal = 0;
	$voip = 0;
	$chat = 0;
	if (isset($_POST['supervisor']) && $_POST['supervisor'] == "on") $supervisor = 1;
	if (isset($_POST['refusal']) && $_POST['refusal'] == "on") $refusal = 1;
	if (isset($_POST['temporary']) && $_POST['temporary'] == "on") $temporary = 1;	
	if (isset($_POST['voip']) && $_POST['voip'] == "on") $voip = 1;	
	if (isset($_POST['chat_enable']) && $_POST['chat_enable'] == "on") $chat = 1;	

	if (!empty($_POST['operator']))
	{
		$sql = "INSERT INTO operator
			(`operator_id` ,`username` ,`firstName` ,`lastName`, `Time_zone_name`,`voip`,`chat_enable`,`chat_user`,`chat_password`)
			VALUES (NULL , $operator, $firstname , $lastname, $time_zone_name, $voip, $chat, $chat_user, $chat_password);";
	
		if ($db->Execute($sql))
    {
			$oid = $db->Insert_ID();

			if (FREEPBX_PATH !== false)
      {
        //add extension
        $sql = "INSERT INTO extension (`extension`,`extension_password`,`current_operator_id`)
                VALUES ($extension, $extensionp, $oid)";

        $db->Execute($sql);

				//Generate new extension in freepbx
				include_once("../functions/functions.freepbx.php");
				freepbx_add_extension($extensionn, $_POST["firstname"] . " " . $_POST["lastname"], $extensionnp);
      }
      else if (!empty($_POST['extension_id']))
      {
        $sql = "UPDATE extension
                SET current_operator_id = $oid
                WHERE extension_id = " . intval($_POST['extension_id']);
        $db->Execute($sql);
      }

			if (HTPASSWD_PATH !== false && HTGROUP_PATH !== false)
			{
				//Get password and add it to the configured htpassword
				include_once("../functions/functions.htpasswd.php");
				$htp = New Htpasswd(HTPASSWD_PATH);
				$htg = New Htgroup(HTGROUP_PATH);
				
				$htp->addUser($_POST['operator'],$_POST['password']);
				$htg->addUserToGroup($_POST['operator'],HTGROUP_INTERVIEWER);

				if ($supervisor)
					$htg->addUserGroup(HTGROUP_ADMIN);
			}
	
			$a = T_("Added:") . " " .  $operator;	

			if (FREEPBX_PATH !== false)
				$a .= "<br/>" . T_("FreePBX has been reloaded for the new VoIP extension to take effect");
	

			if ($temporary)
			{
				$db->Execute("  INSERT INTO operator_skill (operator_id,outcome_type_id)
					VALUES ('$oid','1')");
				$db->Execute("  INSERT INTO operator_skill (operator_id,outcome_type_id)
					VALUES ('$oid','5')"); //and appointment
			}

			if ($supervisor)
				$db->Execute("  INSERT INTO operator_skill (operator_id,outcome_type_id)
						VALUES ('$oid','2')");

			if ($refusal)
				$db->Execute("  INSERT INTO operator_skill (operator_id,outcome_type_id)
						VALUES ('$oid','3')");



		}
		else
		{
			$a = T_("Could not add operator. There may already be an operator of this name:") . " $operator ";
		}


	}
}


xhtml_head(T_("Add an operator"));

if ($a)
{
?>
	<h3><?php  echo $a; ?></h3>
<?php 
}

$sql = "SELECT Time_zone_name as value, Time_zone_name as description
	FROM timezone_template";

$rs = $db->GetAll($sql);

$sql = "SELECT extension_id as value, extension as description
        FROM extension
        WHERE current_operator_id IS NULL";

$ers = $db->GetAll($sql);

?>
<h1><?php  echo T_("Add an operator"); ?></h1>
<p><?php  echo T_("Adding an operator here will give the user the ability to call cases"); ?> <a href="operatorquestionnaire.php"><?php  echo T_("Assign Operator to Questionnaire"); ?></a> <?php  echo T_("tool"); ?>.</p>
<p><?php  echo T_("Use this form to enter the username of a user based on your directory security system. For example, if you have secured the base directory of queXS using Apache file based security, enter the usernames of the users here."); ?></p>
<p><?php echo T_("The username and extension must be unique for each operator.")?></p>
<form enctype="multipart/form-data" action="" method="post">
	<p><?php  echo T_("Enter the username of an operator to add:"); ?> <input name="operator" type="text"/></p>
<?php  if (HTPASSWD_PATH !== false && HTGROUP_PATH !== false) { ?>
	<p><?php  echo T_("Enter the password of an operator to add:"); ?> <input name="password" type="text"/></p>
<?php  } ?>
	<p><?php  echo T_("Enter the first name of an operator to add:"); ?> <input name="firstname" type="text"/></p>
	<p><?php  echo T_("Enter the surname of an operator to add:"); ?> <input name="lastname" type="text"/></p>
	<p><a href='timezonetemplate.php'><?php  echo T_("Enter the Time Zone of an operator to add:"); echo "</a>"; display_chooser($rs,"Time_zone_name","Time_zone_name",false,false,false,false,array("value",DEFAULT_TIME_ZONE)); ?> </p>
<?php  if (FREEPBX_PATH == false) { ?>
	<p><a href='extensionstatus.php'><?php  echo T_("Select an extension for this operator:"); echo "</a>"; display_chooser($ers,"extension_id","extension_id",true,false,false,false); ?> </p>
<?php  } ?>
	<p><?php  echo T_("Will this operator be using VoIP?"); ?> <input name="voip" type="checkbox" checked="checked"/></p>
	<p><?php  echo T_("Jabber/XMPP chat user"); ?>: <input name="chat_user" type="text"/></p>
	<p><?php  echo T_("Jabber/XMPP chat password"); ?>: <input name="chat_password" type="text"/></p>
	<p><?php  echo T_("Will this operator be using chat?"); ?> <input name="chat_enable" type="checkbox"/></p>
	<p><?php  echo T_("Is the operator a normal interviewer?"); ?> <input name="temporary" type="checkbox" checked="checked"/></p>
	<p><?php  echo T_("Is the operator a supervisor?"); ?> <input name="supervisor" type="checkbox"/></p>
	<p><?php  echo T_("Is the operator a refusal converter?"); ?> <input name="refusal" type="checkbox"/></p>
	<p><input type="submit" value="<?php  echo T_("Add user"); ?>" /></p>
</form>

<?php 

xhtml_foot();

?>
