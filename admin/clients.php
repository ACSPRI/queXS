<?php 
/**
 * Create a client and link to a webserver username for authentication
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
 *
 * @todo Make timezone a drop down list
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

$client =""; $firstname="";$lastname="";$email=""; $time_zone_name="";

if (isset($_POST['client']) && !empty($_POST['client']))
{
	$client = $db->qstr($_POST['client'],get_magic_quotes_gpc());
	$firstname = $db->qstr($_POST['firstname'],get_magic_quotes_gpc());
	$email = $db->qstr($_POST['email'],get_magic_quotes_gpc());
	$lastname = $db->qstr($_POST['lastname'],get_magic_quotes_gpc());
	$time_zone_name = $db->qstr($_POST['Time_zone_name'],get_magic_quotes_gpc());
	
	/* check if there'a record with this username*/
	$sql = "SELECT `username`,`client_id` from client WHERE `username` LIKE $client";
	$rs = $db->GetAll($sql);
	
	if (isset($_GET['edit']) && $_GET['edit'] >0 ) {
		
		$clid = intval($_GET['edit']);
		$uid = intval($_POST['uid']);
	}
		
	
	if (empty($rs) || count($rs)==1 && $rs[0]['client_id'] == $clid){
		
		// update client
		if (isset($_GET['edit']) && $_GET['edit'] >0 ) {
			
			$sql = "UPDATE `client` SET `username`= $client,`firstName` = $firstname,`lastName` = $lastname,`Time_zone_name` = $time_zone_name
					WHERE `client_id` = $clid ";
					
			if ($db->Execute($sql))
			{
				$sql = "UPDATE " . LIME_PREFIX . "users SET `users_name` = $client, `full_name` = $firstname, `email` = $email";
				
				/* rewrite 'password' only if not blank in edit mode */
				if (isset($_GET['edit']) && $_GET['edit'] >0 && isset($_POST['password']) && !empty($_POST['password'])) {
					
					include_once("../include/limesurvey/admin/classes/core/sha256.php");
					$sql .=",`password` = '" . SHA256::hashing($_POST['password']) . "'";
				}
				
				$sql .= "WHERE `uid` = $uid";

				$db->Execute($sql);

				if ($db->Execute($sql)) $a =  T_("Updated") . ": " . $client; else $a =  T_("Update error");
			}
			else 
				$a = T_("Could not update") . " " . $client;
		}
		else {  //save as a new client

			$sql = "INSERT INTO client (`client_id` ,`username` ,`firstName` ,`lastName`, `Time_zone_name`)
					VALUES (NULL , $client, $firstname , $lastname, $time_zone_name);";
		
			if ($db->Execute($sql)) {
						
				include_once("../include/limesurvey/admin/classes/core/sha256.php");

				//Insert into lime_users 
				$sql = "INSERT INTO " . LIME_PREFIX . "users (`users_name`,`password`,`full_name`,`parent_id`,`superadmin`,`email`,`lang`) 
						VALUES ($client, '" . SHA256::hashing($_POST['password']) . "', $firstname ,1,0,$email,'auto')";

				$db->Execute($sql);

				if ($db->Execute($sql)) $a = T_("Added") . ": " . $client; else $a =  T_("Error adding client");	
			}
			else 
				$a = T_("Could not add") . " " . $client;
		}
	}
	else $a = T_("Username") . " " . $client . ". " . T_("is already in use");
}

$header = T_("Add a client");
$sbut = T_("Add new client");
$req = "required";

if (isset($_GET['edit']) && $_GET['edit'] >0 ) {
	
	$header = T_("Edit client data");

	$clid = intval($_GET['edit']);
			
	$sql = "SELECT client.*, u.email, u.uid from client, " . LIME_PREFIX . "users as u WHERE client_id=$clid and u.users_name=username";
			
	$cdata = $db->GetRow($sql);

	if (!$cdata) {
		unset($_GET['edit']);
		die(T_("NO such client"));
	}  
	else{
		$uid = $cdata['uid'];
		$client = $cdata['username'];
		$firstname= $cdata['firstName'];
		$lastname= $cdata['lastName'];
		$email= $cdata['email'];
		$time_zone_name = $cdata['Time_zone_name'];
		$sbut = T_("Update client data");
		$req = "";
	}
}

xhtml_head($header,true,array("../include/bootstrap/css/bootstrap.min.css","../css/custom.css"));

$sql = "SELECT Time_zone_name as value, Time_zone_name as description
	FROM timezone_template";
$tzs = $db->GetAll($sql);

if ($a) { ?>
	<div class='alert alert-info'><?php  echo $a; ?></div>
<?php } ?>

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
	var n = document.addclient.number.value;
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
	document.addclient.password.value = pwd;
}
</script>


<div class="well">
	<p><?php  echo T_("Adding a client here will allow them to access project information in the client subdirectory.");
	
	if (isset($_GET['edit']) && $_GET['edit'] >0 ){
		echo "&emsp;" . T_("You can assign a client to a particular project with"). "&emsp;"; ?> <a href="clientquestionnaire.php" class="btn btn-default"><?php  echo T_("Assign client to Questionnaire") . "</a>";
	} ?>
	</p>
</div>	

<form enctype="multipart/form-data" action="" method="post" class="form-horizontal" name="addclient" >
	<div class="form-group form-inline">
		<label class="control-label col-lg-3"><?php  echo T_("Username"); ?>:</label>
		<input name="client" type="text" class="form-control" required size="40" value="<?php echo $client;?>"/>
	</div>
	<div class="form-group form-inline">
		<label class="control-label col-lg-3"><?php  echo T_("Password"); ?>:</label>
		<input name="password" type="text" class="form-control pull-left" size="40" <?php echo $req;?> placeholder="<?php if (isset($_GET['edit']) && $_GET['edit'] >0 ) echo T_("Leave this blank to keep current password");?>"/>
		<div class="form-inline">&emsp;&emsp;
			<input type="button" onclick="generate();" value="<?php echo T_("Generate");?>" class="btn btn-default fa" />&emsp;<?php echo T_("Password with");?>&ensp;
			<input type="number" name="number" value="25" min="8" max="50" style="width:5em;"  class="form-control" />&ensp;<?php echo T_("characters");?>
		</div>
	</div>
	<div class="form-group form-inline">
		<label class="control-label col-lg-3"><?php  echo T_("First name"); ?> :</label>
		<input name="firstname" type="text" class="form-control" size="40" value="<?php echo $firstname;?>"/>
	</div>
	<div class="form-group form-inline">
		<label class="control-label col-lg-3"><?php  echo T_("Surname"); ?>:</label>
		<input name="lastname" type="text" class="form-control" size="40"value="<?php echo $lastname;?>"/>
	</div>
	<div class="form-group form-inline">
		<label class="col-lg-3 control-label"><?php echo T_("Email"); ?>:</label>
		<input name="email" type="text" class="form-control" size="40" value="<?php echo $email;?>"/>
	</div>
	<div class="form-group form-inline">
		<label class="control-label col-lg-3"><a href='timezonetemplate.php'><?php  echo T_("Timezone"); echo ":</a></label><div size=\"40\">";
		if (isset($_GET['edit']) && $_GET['edit'] >0)  $dtz = $time_zone_name; else $dtz = get_setting("DEFAULT_TIME_ZONE");
		display_chooser($tzs,"Time_zone_name","Time_zone_name",false,false,false,false,array("value", $dtz),true,"pull-left"); ?> </div>
	</div>
	<?php if (isset($_GET['edit']) && $_GET['edit'] >0 ) { ?>
		<input name="uid" type="hidden" value="<?php echo $uid;?>"/>
	<?php } ?>
	
	<div class="form-group">
		<a href="clientquestionnaire.php" style="" class="btn btn-default col-lg-1 col-lg-offset-1"><?php  echo T_("Cancel"); ?></a>
		<input type="submit" value="<?php  echo $sbut; ?>" style="width:336px;" class="btn btn-primary col-lg-offset-1"/>
	</div>
</form>

<?php 
xhtml_foot();
?>
