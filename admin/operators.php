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
 * Authentication file
 */
require ("auth-admin.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");


global $db;

$a = false;

if (isset($_POST['operator']) && isset($_POST['adduser']))
{
	$operator = $db->qstr($_POST['operator'],get_magic_quotes_gpc());
	$email= $db->qstr($_POST['email'],get_magic_quotes_gpc());
	$password = $db->qstr($_POST['password'],get_magic_quotes_gpc());
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
		$length = 25;
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
		for ($i = 0; $i < $length; $i++) 
			$extensionnp .= $chars[(mt_rand() % strlen($chars))];

		//quote for SQL
		$extensionp = "'$extensionnp'";

	}

	$admin = 0;
	$voip = 0;
	$chat = 0;
	
	if (isset($_POST['admin']) && $_POST['admin'] == "on") $admin = 1;	
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

      include_once("../include/sha256.php");

      //Insert into lime_users
      $sql = "INSERT INTO " . LIME_PREFIX . "users (`users_name`,`password`,`full_name`,`parent_id`,`superadmin`,`email`,`lang`)
              VALUES ($operator, '" . SHA256::hashing($_POST['password']) . "',$firstname,1,$admin,$email,'auto')";

      $db->Execute($sql);

			if (FREEPBX_PATH !== false)
      {
        //add extension
        $sql = "INSERT INTO extension (`extension`,`password`,`current_operator_id`)
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

			$a = "<div class='alert alert-info'><h3>" . T_("Added operator :") . " " .  $operator . "</h3>";	

			if (FREEPBX_PATH !== false) 
				$a .= "<br/><a href='/voip/admin/'>" . T_("FreePBX needs to be reloaded for the new VoIP extension to take effect") . "</a>";
			
			$a .= "</div>";	

			// set default skills = 1 and 5 for all new operators

				$db->Execute("  INSERT INTO operator_skill (operator_id,outcome_type_id)
					VALUES ('$oid','1')");
				$db->Execute("  INSERT INTO operator_skill (operator_id,outcome_type_id)
					VALUES ('$oid','5')"); //and appointment

		}
		else
		{
			$a = T_("Could not add operator. There may already be an operator of this name:") . " $operator ";
		}
	}
}


xhtml_head(T_("Add an operator"),true,array("../include/bootstrap/css/bootstrap.min.css","../include/bootstrap-toggle/css/bootstrap-toggle.min.css", "../css/custom.css"), array("../include/jquery/jquery.min.js", "../include/bootstrap/js/bootstrap.min.js","../include/bootstrap-toggle/js/bootstrap-toggle.min.js"));

if ($a) {
  echo $a; 
} 
else {
	echo "<div class='well'>";
		//echo "<p>" . T_("Adding an operator here will give the user the ability to call cases") . "<a href='operatorquestionnaire.php'>" . T_("Assign Operator to Questionnaire") . "</a>" . T_("tool") . ".</p>"; 
		//echo "<p>" . T_("Use this form to enter the username of a user based on your directory security system. For example, if you have secured the base directory of queXS using Apache file based security, enter the usernames of the users here.") . "</p>"; 
		echo "<p>" . T_("The username and extension must be unique for each operator.") . "</p>";
	echo "</div>";
}

$sql = "SELECT Time_zone_name as value, Time_zone_name as description
	FROM timezone_template";

$rs = $db->GetAll($sql);

$sql = "SELECT extension_id as value, extension as description
        FROM extension
        WHERE current_operator_id IS NULL";

$ers = $db->GetAll($sql);
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

<form enctype="multipart/form-data" action="" method="post" class="form-horizontal panel-body" name="operform">
	<div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("Username") . ": ";?></label>
		<div class="col-lg-3"><input name="operator" type="text" class="form-control" required /></div>
	</div>
	<div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("Password") . ": ";?></label>
		<div class="col-lg-3"><input name="password" id="password" type="text" class="form-control" required /></div>
		<div class="col-lg-6 form-inline">&emsp;
			<input type="button" onclick="generate();" value="<?php echo T_("Generate");?>" class="btn btn-default fa" />&emsp;<?php echo T_("Password with");?>&ensp;
			<input type="number" name="number" value="25" min="8" max="50" style="width:5em;"  class="form-control" />&ensp;<?php echo T_("characters");?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("First name") . ": ";?></label>
		<div class="col-lg-3"><input name="firstname" type="text" class="form-control" required/></div>
	</div>
	<div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("Last name") . ": ";?></label>
		<div class="col-lg-3"><input name="lastname" type="text" class="form-control"/></div>
	</div>
  <div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("Email") . ": ";?></label>
		<div class="col-lg-3"><input name="email" type="text" class="form-control"/></div>
	</div>
	<div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("Timezone") . ": ";?></label>
		<div class="col-lg-3"><?php display_chooser($rs,"Time_zone_name","Time_zone_name",false,false,false,true,array("value",get_setting("DEFAULT_TIME_ZONE")),true,"form-inline");?></div>
		<div class="col-lg-6 form-inline">
			<?php echo T_("Edit") . "&emsp;";?>
			<a  href='timezonetemplate.php' class="btn btn-default fa"><?php echo T_("TimeZones list");?></a>
		</div>
	</div>
<?php  if (VOIP_ENABLED != false) { ?>
	<div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("Uses VoIP") . "? ";?></label>
		<div class="col-lg-3"><input name="voip" type="checkbox" onchange="if(this.checked==true){show(this,'usesvoip');} else{ hide(this,'usesvoip');}" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" /></div><!-- checked="checked" -->
	</div>
	<div id="usesvoip" style="display:none" >
	<div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("Extension") . ": ";?></label>
		<div class="col-lg-3"><?php display_chooser($ers,"extension_id","extension_id",true,false,false,true,false,true,"form-inline");?></div>
		<div class="col-lg-6 form-inline">
			<?php echo T_("Edit") . "&emsp;";?>
			<a  href='extensionstatus.php' class="btn btn-default fa"><?php echo T_("Extensions");?></a>
		</div>
	</div>
	</div>
<?php  } ?>

	<div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("Uses chat") . "? ";?></label>
		<div class="col-lg-3"><input name="chat_enable" type="checkbox" onchange="if(this.checked==true){show(this,'jabdata');} else{ hide(this,'jabdata');}" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" /></div>
	</div>

	<div id="jabdata" style="display:none" >
		<div class="form-group">
			<label class="col-lg-3 control-label"><?php echo T_("Jabber/XMPP chat user") . ": ";?></label>
			<div class="col-lg-3"><input name="chat_user" type="text" class="form-control"/></div>
		</div>
		<div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("Jabber/XMPP chat password") . ": ";?></label>
			<div class="col-lg-3"><input name="chat_password" type="text" class="form-control"/></div>
		</div>
	</div>

	<div class="form-group">
		<label class="col-lg-3 control-label"><?php echo T_("Is the operator a system administrator?");?></label>
		<div class="col-lg-3"><input name="admin" type="checkbox" data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-onstyle="danger"/></div>
	</div>

	<br/>
	<div class="form-group form-inline">
		<div class='col-lg-3'>
			<a href='operatorlist.php' class='btn btn-default col-lg-6'><?php echo T_("Cancel") ;?></a>
		</div>
		
		<div class="col-lg-3">
			<input type="submit" name="adduser" class="btn btn-primary btn-block" value="<?php  echo T_("Add an operator"); ?>" />
		</div>
	</div>
</form>

<?php 
xhtml_foot(array("../js/new.js"));
?>
