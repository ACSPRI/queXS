<?php 
/**
 * List operators and allow for customised VoIP downloads, changing passwords, disabling, etc
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2007,2008,2009,2010,2011
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/software queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

/**
 * Database
 */
include_once(dirname(__FILE__).'/../db.inc.php');

/**
 * XHTML functions
 */
include_once(dirname(__FILE__).'/../functions/functions.xhtml.php');

$display = true;
$msg = "";

if (isset($_POST['submit']))
{
	$operator_id = intval($_POST['operator_id']);
	$voip = $enabled = 0;
	if (isset($_POST['voip'])) $voip = 1;
	if (isset($_POST['enabled'])) $enabled = 1;

	$sql = "UPDATE operator
		SET username = " . $db->qstr($_POST['username']) . ",
		lastName = " . $db->qstr($_POST['lastName']) . ",
		firstName = " . $db->qstr($_POST['firstName']) . ",
		extension = " . $db->qstr($_POST['extension']) . ",
		extension_password = " . $db->qstr($_POST['extension_password']) . ",
		Time_zone_name = " . $db->qstr($_POST['timezone']) . ",
		voip = $voip, enabled = $enabled
		WHERE operator_id = $operator_id";

	$rs = $db->Execute($sql);

	if (!empty($rs))
	{
		if (HTPASSWD_PATH !== false && !empty($_POST['password']))
		{
			//update password in htaccess
			include_once(dirname(__FILE__).'/../functions/functions.htpasswd.php');
			$htp = New Htpasswd(HTPASSWD_PATH);
			$htp->deleteUser($_POST["existing_username"]);
			$htp->deleteUser($_POST["username"]);
			$htp->addUser($_POST["username"],$_POST["password"]);
		}

		$msg = T_("Successfully updated user");
	}
	else
	{
		$msg = T_("Failed to update user. Please make sure the username and extension are unique");
	}

	$_GET['edit'] = $operator_id;
}


if (isset($_GET['edit']))
{
	xhtml_head(T_("Operator edit"),true,array("../css/table.css"));

	$operator_id = intval($_GET['edit']);

	$sql = "SELECT *,
	CONCAT('<select name=\'timezone\'>', (SELECT GROUP_CONCAT(CONCAT('<option ', CASE WHEN timezone_template.Time_zone_name LIKE operator.Time_zone_name THEN ' selected=\"selected\" ' ELSE '' END ,'value=\"', Time_zone_name, '\">', Time_zone_name, '</option>') SEPARATOR '') as tzones
                FROM timezone_template),'</select>') as timezone
		FROM operator
		WHERE operator_id = $operator_id";

	$rs = $db->GetRow($sql);

	print "<h2>" . T_("Edit") . ": " . $rs['username'] . "</h2>";
	echo "<p><a href='?'>" . T_("Go back") . "</a></p>";
	if (!empty($msg)) print "<h3>$msg</h3>";

	?>
	<form action="?" method="post">
	<div><label for="username"><?php echo T_("Username") . ": "; ?></label><input type='text' name='username' value="<?php echo $rs['username'];?>"/></div>
	<?php
	if (HTPASSWD_PATH !== false) 
	{ ?>
	<div><label for="password"><?php echo T_("Update password (leave blank to keep existing password)") . ": "; ?></label><input type='text' name='password'/></div>
	<?php }
	?>
	<div><label for="firstName"><?php echo T_("First name") . ": "; ?></label><input type='text' name='firstName' value="<?php echo $rs['firstName'];?>"/></div>
	<div><label for="lastName"><?php echo T_("Last name") . ": "; ?></label><input type='text' name='lastName' value="<?php echo $rs['lastName'];?>"/></div>
	<div><label for="extension"><?php echo T_("Extension") . ": "; ?></label><input type='text' name='extension' value="<?php echo $rs['extension'];?>"/></div>
	<div><label for="extension_password"><?php echo T_("Extension Password") . ": "; ?></label><input type='text' name='extension_password' value="<?php echo $rs['extension_password'];?>"/></div>
	<div><label for="timezone"><?php echo T_("Timezone") . ": ";?></label><?php echo $rs['timezone'];?></div>
	<div><label for="enabled"><?php echo T_("Enabled") . "? ";?></label><input type="checkbox" name="enabled" <?php if ($rs['enabled'] == 1) echo "checked=\"checked\"";?> value="1" /></div>
	<div><label for="voip"><?php echo T_("Uses VoIP") . "? ";?></label><input type="checkbox" name="voip" <?php if ($rs['voip'] == 1) echo "checked=\"checked\"";?> value="1" /></div>
	<div><input type='hidden' name='operator_id' value='<?php echo $operator_id;?>'/></div>
	<div><input type='hidden' name='existing_username' value="<?php echo $rs['username'];?>"/></div>
	<div><input type="submit" name="submit" value="<?php echo T_("Update operator");?>"/></div>
	</form>
	<?php	

	
	xhtml_foot();
	exit();
}



if (isset($_GET['voipdisable']))
{
	$operator_id = intval($_GET['voipdisable']);

	$sql = "UPDATE operator
		SET voip = 0
		WHERE operator_id = '$operator_id'";

	$db->Execute($sql);	
}

if (isset($_GET['voipenable']))
{
	$operator_id = intval($_GET['voipenable']);

	$sql = "UPDATE operator
		SET voip = 1
		WHERE operator_id = '$operator_id'";

	$db->Execute($sql);	
}


if (isset($_GET['disable']))
{
	$operator_id = intval($_GET['disable']);

	$sql = "UPDATE operator
		SET enabled = 0
		WHERE operator_id = '$operator_id'";

	$db->Execute($sql);	
}

if (isset($_GET['enable']))
{
	$operator_id = intval($_GET['enable']);

	$sql = "UPDATE operator
		SET enabled = 1
		WHERE operator_id = '$operator_id'";

	$db->Execute($sql);	
}

if (isset($_GET['operator_id']))
{
	$operator_id = intval($_GET['operator_id']);

	$sql = "SELECT *,SUBSTRING_INDEX(extension, '/', -1) as ext
		FROM operator
		WHERE operator_id = $operator_id";

	$rs = $db->GetRow($sql);

	if (!empty($rs))
	{
		$display = false;	

		if (isset($_GET['winbat']) || isset($_GET['sh']))
		{
			header("Content-Type: text/txt");
			if (isset($_GET['winbat']))
				header("Content-Disposition: attachment; filename=operator_$operator_id.bat");
			else
				header("Content-Disposition: attachment; filename=operator_$operator_id.sh");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
			header("Pragma: public");                          // HTTP/1.0
	
			if (isset($_GET['winbat']))
				echo "voipclient.exe -i -u {$rs['ext']} -p {$rs['extension_password']} -h " . $_SERVER['SERVER_NAME'];
			else
				echo "./voipclient -i -u {$rs['ext']} -p {$rs['extension_password']} -h " . $_SERVER['SERVER_NAME'];
		}
	}
}

if ($display)
{
	$sql = "SELECT
			CONCAT(firstName, ' ', lastName) as name,
			CONCAT('<form action=\'?\' method=\'post\'><input type=\'text\' name=\'password', operator_id, '\'/><input type=\'hidden\' name=\'username', operator_id, '\' value=\'', username, '\'/><input type=\'submit\' value=\'" . T_("Update password") . "\'/></form>') as password,
			CONCAT('<form action=\'?\' method=\'post\'><select name=\'timezone', operator_id, '\'/>', 

(SELECT GROUP_CONCAT(CONCAT('<option ', CASE WHEN timezone_template.Time_zone_name LIKE operator.Time_zone_name THEN ' selected=\"selected\" ' ELSE '' END ,'value=\"', Time_zone_name, '\">', Time_zone_name, '</option>') SEPARATOR '') as tzones
                FROM timezone_template)

			  ,'</select><input type=\'submit\' value=\'" . T_("Update timezone") . "\'/></form>') as timezone,
			CONCAT('<a href=\'?winbat=winbat&amp;operator_id=',operator_id,'\'>" . T_("Windows bat file") . "</a>') as winbat,
			CONCAT('<a href=\'?sh=sh&amp;operator_id=',operator_id,'\'>" . T_("*nix script file") . "</a>') as sh,
			CASE WHEN enabled = 0 THEN
				CONCAT('<a href=\'?enable=',operator_id,'\'>" . T_("Enable") . "</a>') 
			ELSE
				CONCAT('<a href=\'?disable=',operator_id,'\'>" . T_("Disable") . "</a>') 
			END
			as enabledisable,
			CASE WHEN voip = 0 THEN
				CONCAT('<a href=\'?voipenable=',operator_id,'\'>" . T_("Enable VoIP") . "</a>') 
			ELSE
				CONCAT('<a href=\'?voipdisable=',operator_id,'\'>" . T_("Disable VoIP") . "</a>') 
			END as voipenabledisable,
			CONCAT('<a href=\'?edit=',operator_id,'\'>" . T_("Edit") . "</a>')  as edit,
			username
		FROM operator";
	
	$rs = $db->GetAll($sql);
	
	xhtml_head(T_("Operator list"),true,array("../css/table.css"));
	
	$columns = array("name","username","enabledisable","edit");
	$titles = array(T_("Operator"),T_("Username"),T_("Enable/Disable"),T_("Edit"));

	if (VOIP_ENABLED)
	{
		print "<p>" . T_("Download the file for each user and save in the same folder as the voip.exe executable. When the file is executed, it will run the voip.exe program with the correct connection details to connect the operator to the VoIP server") . "</p>";
	
		print "<p><a href='../voipclient.exe'>" . T_("Download Windows VoIP Executable")  . "</a></p>";
		print "<p><a href='../voipclient'>" . T_("Download Linux VoIP Executable")  . "</a></p>";

		$columns[] = "voipenabledisable";
		$columns[] = "winbat";
		$columns[] = "sh";
		$titles[] = T_("Enable/Disable VoIP");
		$titles[] = T_("Windows VoIP");
		$titles[] = T_("*nix VoIP");
	}

	xhtml_table($rs,$columns,$titles);

	
	xhtml_foot();
}
?>
