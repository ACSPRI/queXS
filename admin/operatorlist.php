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
	$chat_enable = $voip = $enabled = 0;
	if (isset($_POST['voip'])) $voip = 1;
	if (isset($_POST['chat_enable'])) $chat_enable = 1;
	if (isset($_POST['enabled'])) $enabled = 1;

	if (HTPASSWD_PATH !== false && $_POST['existing_username'] != $_POST['username'] && empty($_POST['password']))
	{
		$msg = "<div class='alert alert-danger'><h3>" . T_("If changing usernames, you must specify a new password") . "</h3></div>";
	}
	else
	{
		$sql = "UPDATE operator
			SET username = " . $db->qstr($_POST['username']) . ",
			lastName = " . $db->qstr($_POST['lastName']) . ",
			firstName = " . $db->qstr($_POST['firstName']) . ",
			chat_user = " . $db->qstr($_POST['chat_user']) . ",
			chat_password = " . $db->qstr($_POST['chat_password']) . ",
			Time_zone_name = " . $db->qstr($_POST['timezone']) . ",
			voip = $voip, enabled = $enabled, chat_enable = $chat_enable
			WHERE operator_id = $operator_id";

		$rs = $db->Execute($sql);

		if (!empty($rs))
    {
      //only update extension if we aren't on a case
      $sql = "SELECT case_id
              FROM `case`
              WHERE current_operator_id = $operator_id";

      $cc= $db->GetOne($sql);

      if (empty($cc))
      {
        $sql = "UPDATE extension
                SET current_operator_id = NULL
                WHERE current_operator_id= $operator_id";

        $db->Execute($sql);
  
        if (!empty($_POST['extension_id']))
        {
          $sql = "UPDATE extension
                  SET current_operator_id = $operator_id
                  WHERE extension_id = " . intval($_POST['extension_id']);
  
          $db->Execute($sql);
        }
      }

			if (HTPASSWD_PATH !== false && !empty($_POST['password']))
			{
				//update password in htaccess
				include_once(dirname(__FILE__).'/../functions/functions.htpasswd.php');
				$htp = New Htpasswd(HTPASSWD_PATH);
				$htp->deleteUser($_POST["existing_username"]);
				$htp->deleteUser($_POST["username"]);
				$htp->addUser($_POST["username"],$_POST["password"]);
				$htg = New Htgroup(HTGROUP_PATH);
				$htg->deleteUserFromGroup($_POST["existing_username"],HTGROUP_INTERVIEWER);
				$htg->addUserToGroup($_POST["username"],HTGROUP_INTERVIEWER);
			}

			$msg = "<div class='alert alert-info'><h3>" . T_("Successfully updated user") . ": " . $_POST['username'] . "</h3></div>";
		}
		else
		{
			$msg = "<div class='alert alert-danger'><h3>" . T_("Failed to update user") . ": " . $_POST['username'] . " " . T_("Please make sure the username is unique") . "</h3></div>";
		}
	}
	$_GET['edit'] = $operator_id;
}


if (isset($_GET['edit']))
{
	xhtml_head(T_("Edit Operator settings"),true,array("../include/bootstrap-3.3.2/css/bootstrap.min.css","../include/bootstrap-toggle/css/bootstrap-toggle.min.css", "../css/custom.css"),array("../js/jquery-2.1.3.min.js","../include/bootstrap-3.3.2/js/bootstrap.min.js","../include/bootstrap-toggle/js/bootstrap-toggle.min.js"));

	$operator_id = intval($_GET['edit']);

	$sql = "SELECT *
		FROM operator
		WHERE operator_id = $operator_id";

	$rs = $db->GetRow($sql);

	$sql = "SELECT Time_zone_name as value, Time_zone_name as description
		FROM timezone_template";

	$tz = $db->GetAll($sql);
?>

<script type="text/javascript">	
//Password generator
upp = new Array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
low = new Array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
dig = new Array('0','1','2','3','4','5','6','7','8','9');
sym = new Array('~','!','@','#','$','%','^','&','*','(',')','_','+','=','|',';','.','/','?','<','>','{','}','[',']');
// --------------------------------------------------------------------------------------------------------------------------------------------------------------
function rnd(x,y,z) { 
	var num;
	do {
		num = parseInt(Math.random()*z);
		if (num >= x && num <= y) break;
	} while (true);
return(num);
}
// --------------------------------------------------------------------------------------------------------------------------------------------------------------
function generate() {																
	var pwd = '';
	var res, s;
	var k = 0;
	var n = document.operform.number.value;
	var pass = new Array();
	var w = rnd(30,80,100);
	for (var r = 0; r < w; r++) {
		res = rnd(1,25,100); pass[k] = upp[res]; k++; 
		res = rnd(1,25,100); pass[k] = low[res]; k++;
		res = rnd(1,9,100); pass[k] = dig[res]; k++;
		res = rnd(1,24,100); pass[k] = sym[res]; k++;		
	}
	for (var i = 0; i < n; i++) {
		s = rnd(1,k-1,100);
		pwd+= pass[s];
	}
	document.operform.password.value = pwd;
}
</script>

<div class="form-group clearfix"><div class="col-sm-3"><a href='?' class="btn btn-default"><?php echo T_("Go back") ;?></a></div><div class="col-sm-6">
<?php
	print "<h3>" . T_("Operator") . ": " . $rs['username'] . "</h3>";
	echo "</div></div>";
	
	if (!empty($msg)) echo $msg;

  $sql = "SELECT extension_id as value, extension as description,
        CASE WHEN current_operator_id = $operator_id THEN 'selected=\'selected\'' ELSE '' END AS selected
        FROM extension
        WHERE current_operator_id IS NULL
        OR current_operator_id = $operator_id";

  $ers = $db->GetAll($sql);
  
?>
	<form action="?" method="post" class="form-horizontal panel-body" name="operform">
	<div class="form-group">
		<label for="username" class="col-sm-3 control-label"><?php echo T_("Username") . ": "; ?></label>
		<div class="col-sm-3"><input type='text' name='username' class="form-control" value="<?php echo $rs['username'];?>"/></div>
	</div>
<?php if (HTPASSWD_PATH !== false) { ?>
	<div class="form-group">
		<label for="password" class="col-sm-3 control-label"><?php echo T_("Password") . ": "; ?></label>
		<div class="col-sm-3"><input type='text' name='password' class="form-control" placeholder="<?php echo T_("leave blank to keep existing password");?>"/></div>
		<div class="col-sm-6 form-inline">&emsp;
			<input type="button" onclick="generate();" value="<?php echo T_("Generate");?>" class="btn btn-default"/>&emsp;<?php echo T_("Password with");?>&ensp;
			<input type="number" name="number" value="25" min="8" max="50" style="width:5em;" class="form-control" />&ensp;<?php echo T_("characters");?>
		</div>
	</div>
<?php } ?>
	<div class="form-group">
		<label for="firstName" class="col-sm-3 control-label"><?php echo T_("First name") . ": "; ?></label>
		<div class="col-sm-3"><input type='text' name='firstName' class="form-control" value="<?php echo $rs['firstName'];?>"/></div>
	</div>
	<div class="form-group">
		<label for="lastName" class="col-sm-3 control-label"><?php echo T_("Last name") . ": "; ?></label>
		<div class="col-sm-3"><input type='text' name='lastName'  class="form-control" value="<?php echo $rs['lastName'];?>"/></div>
	</div>
	<div class="form-group">
		<label for="timezone" class="col-sm-3 control-label"><?php echo T_("Timezone") . ": ";?></label>
		<div class="col-sm-3"><?php display_chooser($tz,"timezone","timezone",false,false,false,true,array("value",$rs['Time_zone_name']),true,"form-inline"); ?></div>
		<div class="col-sm-6 form-inline">
			<?php echo T_("Edit") . "&emsp;";?>
			<a  href='timezonetemplate.php' class="btn btn-default"><?php echo T_("TimeZones list");?></a>
		</div>
	</div>
 	<div class="form-group">
		<label for="extension_id" class="col-sm-3 control-label"><?php  echo T_("Extension") . ": "; ?></label> 
		<div class="col-sm-3"><?php echo display_chooser($ers,"extension_id","extension_id",true,false,false,true,false,true,"form-inline"); ?> </div>
		<div class="col-sm-6 form-inline">
			<?php echo T_("Edit") . "&emsp;";?>
			<a  href='extensionstatus.php' class="btn btn-default"><?php echo T_("Extensions");?></a>
		</div>
	</div>
	<div class="form-group">
		<label for="voip" class="col-sm-3 control-label"><?php echo T_("Uses VoIP") . "? ";?></label>
		<div class="col-sm-3"><input type="checkbox" name="voip" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" <?php if ($rs['voip'] == 1) echo "checked=\"checked\"";?> value="1" /></div>
	</div>
	<div class="form-group">
		<label for="chat_user" class="col-sm-3 control-label"><?php echo T_("Jabber/XMPP chat user") . ": "; ?></label>
		<div class="col-sm-3"><input type='text' name='chat_user' class="form-control" value="<?php echo $rs['chat_user'];?>"/></div>
	</div>
	<div class="form-group">
		<label for="chat_password" class="col-sm-3 control-label"><?php echo T_("Jabber/XMPP chat password") . ": "; ?></label>
		<div class="col-sm-3"><input type='text' name='chat_password' class="form-control" value="<?php echo $rs['chat_password'];?>"/></div>
	</div>
	<div class="form-group">
		<label for="chat_enable" class="col-sm-3 control-label"><?php echo T_("Uses chat") . "? ";?></label>
		<div class="col-sm-3"><input type="checkbox" name="chat_enable" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" <?php if ($rs['chat_enable'] == 1) echo "checked=\"checked\"";?> value="1"/></div>
	</div>
	<div class="form-group">
		<label for="enabled" class="col-sm-3 control-label"><?php echo T_("Enabled") . "? ";?></label>
		<div class="col-sm-3"><input type="checkbox" name="enabled" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" <?php if ($rs['enabled'] == 1) echo "checked=\"checked\"";?> value="1" /></div>
	</div>
	<div><input type='hidden' name='operator_id' value='<?php echo $operator_id;?>'/></div>
	<div><input type='hidden' name='existing_username' value="<?php echo $rs['username'];?>"/></div>

	<div class="form-group"><div class="col-sm-3 col-sm-offset-3"><input type="submit" name="submit" class="btn btn-primary btn-block" value="<?php echo T_("Update operator");?>"/></div></div>
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

	$sql = "SELECT *,SUBSTRING_INDEX(e.extension, '/', -1) as ext
		FROM extension as e
		WHERE e.current_operator_id = $operator_id";

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
				echo "voipclient.exe -i -u {$rs['ext']} -p {$rs['password']} -h " . $_SERVER['SERVER_NAME'];
			else
				echo "./voipclient -i -u {$rs['ext']} -p {$rs['password']} -h " . $_SERVER['SERVER_NAME'];
		}
	}
}

if ($display)
{
	$sql = "SELECT operator_id,
    CONCAT(firstName, ' ', lastName) as name, 
	CONCAT ('<a href=\'extensionstatus.php?edit=',e.extension_id,'\'>', e.extension ,'</a>') as `extension`,
	CONCAT('<a href=\'?winbat=winbat&amp;operator_id=',operator_id,'\'>" . TQ_("Win .bat file") . "</a>') as winbat,
	CONCAT('<a href=\'?sh=sh&amp;operator_id=',operator_id,'\'>" . TQ_("*nix script file") . "</a>') as sh,
	CASE WHEN enabled = 0 THEN
		CONCAT('&ensp;<a href=\'?enable=',operator_id,'\'><i data-toggle=\'tooltip\' title=\'" . TQ_("Enable") . "\' class=\'fa fa-toggle-off fa-2x\' style=\'color:grey;\'></i></a>&ensp;') 
	ELSE
		CONCAT('&ensp;<a href=\'?disable=',operator_id,'\'><i data-toggle=\'tooltip\' title=\'" . TQ_("Disable") . "\' class=\'fa fa-toggle-on fa-2x\'></i></a>&ensp;')
	END as enabledisable,
	CASE WHEN voip = 0 THEN
		CONCAT('<a href=\'?voipenable=',operator_id,'\'>" . TQ_("Enable VoIP") . "</a>') 
	ELSE
		CONCAT('<a href=\'?voipdisable=',operator_id,'\'>" . TQ_("Disable VoIP") . "</a>') 
	END as voipenabledisable,
	CONCAT('&emsp;<a href=\'?edit=',operator_id,'\'><i data-toggle=\'tooltip\' title=\'" . TQ_("Edit") . "\' class=\'fa fa-pencil-square-o fa-lg\'></i></a>&emsp;') as edit,  username
    FROM operator
    LEFT JOIN `extension` as e ON (e.current_operator_id = operator_id)";

	$rs = $db->GetAll($sql);
	
	xhtml_head(T_("Operator list"),true,array("../include/bootstrap-3.3.2/css/bootstrap.min.css","../include/font-awesome-4.3.0/css/font-awesome.css","../css/custom.css"));
	
	$columns = array("operator_id","name","username","extension","enabledisable","edit");
	$titles = array("ID",T_("Operator"),T_("Username"),T_("Extension"),"&emsp;<i class='fa fa-lg fa-power-off '></i>","&emsp;<i class='fa fa-lg fa-pencil-square-o'></i>"); 
	
	if (VOIP_ENABLED)
	{
		$columns[] = "voipenabledisable";
		$columns[] = "winbat";
		$columns[] = "sh";
		$titles[] = T_("VoIP ON/Off");
		$titles[] = T_("Win file");//Windows VoIP
		$titles[] = T_("*nix flle");//*nix VoIP
	}
	echo "<div class=' col-sm-10'><div class=' panel-body'>";
	xhtml_table($rs,$columns,$titles);
	echo "</div></div>";
	
	echo "<div class='form-group col-sm-2'>
			<div class='panel-body'><a href='operators.php?add=add' class='btn btn-default btn-block'><i class='fa fa-lg fa-user-plus'></i>&emsp;" . T_("Add an operator") . "</a></div>
			<div class='panel-body'><a href='extensionstatus.php' class='btn btn-default btn-block'><i class='fa fa-lg fa-whatsapp'></i>&emsp;" . T_("Extensions") . "</a></div>
			<div class='panel-body'><a href='operatorquestionnaire.php' class='btn btn-default btn-block'><i class='fa fa-lg fa-link'></i>  " . T_("Assign to questionnaire") . "</a></div>
			<div class='panel-body'><a href='operatorskill.php' class='btn btn-default btn-block'><i class='fa fa-lg fa-user-md'></i>&emsp;" . T_("Operator skills") . "</a></div>
			<div class='panel-body'><a href='operatorperformance.php' class='btn btn-default btn-block'><i class='fa fa-lg fa-signal'></i>&emsp;" . T_("Operator performance") . "</a></div>";
			
	if (VOIP_ENABLED)
	{
		print "<div class='well'>" . T_("Download the file for each user and save in the same folder as the voip.exe executable. When the file is executed, it will run the voip.exe program with the correct connection details to connect the operator to the VoIP server"). "</br></br>";
	
		print "<a href='../voip/voipclient.exe' class='btn btn-default btn-block' title='" . T_("Download Windows VoIP Client Executable file")  . "'><i class='fa fa-lg fa-download'></i>&emsp;" . T_("Download Win file")  . "</a></br>";
		print "<a href='../voip/voipclient' class='btn btn-default btn-block' title='" . T_("Download Linux VoIP Executable file")  . "'><i class='fa fa-lg fa-download'></i>&emsp;" . T_("Download Linux file")  . "</a></div>";

	}
	print	"</div>";
		
	xhtml_foot();
}
?>