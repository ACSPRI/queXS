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

if (isset($_POST))
{
	foreach($_POST as $key => $val)
	{
		if (substr($key,0,8) == "password")
		{
			if (HTPASSWD_PATH !== false)
			{
				$operator_id = intval(substr($key,8));
				//update password in htaccess
				include_once(dirname(__FILE__).'/../functions/functions.htpasswd.php');
				$htp = New Htpasswd(HTPASSWD_PATH);
				$htp->deleteUser($_POST["username" . $operator_id]);
				$htp->addUser($_POST["username" . $operator_id],$val);
			}
		}
		else if (substr($key,0,8) == "timezone")
		{
			$operator_id = intval(substr($key,8));
			$tzone = $db->qstr($val);
			$sql = "UPDATE operator
				SET Time_zone_name = $tzone
				WHERE operator_id = '$operator_id'";
			$db->Execute($sql);
		}
	}
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
			END
			as voipenabledisable,
			username
		FROM operator";
	
	$rs = $db->GetAll($sql);
	
	xhtml_head(T_("Operator list"),true,array("../css/table.css"));
	
	$columns = array("name","username","enabledisable","timezone");
	$titles = array(T_("Operator"),T_("Username"),T_("Enable/Disable"),T_("Update timezone"));

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

	if (HTPASSWD_PATH !== false)
	{
		$columns[] = "password";
		$titles[] = T_("Update password");
	}

	xhtml_table($rs,$columns,$titles);

	
	xhtml_foot();
}
?>
