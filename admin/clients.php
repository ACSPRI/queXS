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
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");


global $db;

$a = false;

if (isset($_POST['client']))
{
	$client = $db->qstr($_POST['client'],get_magic_quotes_gpc());
	$firstname = $db->qstr($_POST['firstname'],get_magic_quotes_gpc());
	$lastname = $db->qstr($_POST['lastname'],get_magic_quotes_gpc());
	$time_zone_name = $db->qstr($_POST['Time_zone_name'],get_magic_quotes_gpc());
	
	if (!empty($_POST['client']))
	{
		$sql = "INSERT INTO client
			(`client_id` ,`username` ,`firstName` ,`lastName`, `Time_zone_name`)
			VALUES (NULL , $client, $firstname , $lastname, $time_zone_name);";
	
		if ($db->Execute($sql))
		{
			if (HTPASSWD_PATH !== false && HTGROUP_PATH !== false)
			{
				//Get password and add it to the configured htpassword
				include_once("../functions/functions.htpasswd.php");
				$htp = New Htpasswd(HTPASSWD_PATH);
				$htg = New Htgroup(HTGROUP_PATH);
				
				$htp->addUser($_POST['client'],$_POST['password']);
				$htg->addUserToGroup($_POST['client'],HTGROUP_CLIENT);
			}

			$a =  T_("Added: $client");	
		}
		else
			$a = T_("Could not add") . " " . $client . ". " . T_("There may already be a client of this name");
	}
}


xhtml_head(T_("Add a client"),true,array("../include/bootstrap/css/bootstrap.min.css","../css/custom.css"));

$sql = "SELECT Time_zone_name as value, Time_zone_name as description
	FROM timezone_template";

$rs = $db->GetAll($sql);


if ($a)
{
?>
	<div class='alert alert-info'><?php  echo $a; ?></div>
<?php 
}
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
	<p><?php  echo T_("Adding a client here will allow them to access project information in the client subdirectory. You can assign a client to a particular project using the"); ?> <a href="clientquestionnaire.php"><?php  echo T_("Assign client to Questionnaire"); ?></a> <?php  echo T_("tool."); ?></p>
	<p><?php  echo T_("Use this form to enter the username of a user based on your directory security system. For example, if you have secured the base directory of queXS using Apache file based security, enter the usernames of the users here."); ?></p></div>
	
<form enctype="multipart/form-data" action="" method="post" class="form-horizontal" name="addclient" >
	<div class="form-group form-inline">
		<label class="control-label col-sm-3"><?php  echo T_("Enter the username of a client to add:"); ?></label>
		<input name="client" type="text" class="form-control pull-left" required size="40" />
	</div>
<?php  if (HTPASSWD_PATH !== false && HTGROUP_PATH !== false) { ?>
	<div class="form-group form-inline">
		<label class="control-label col-sm-3"><?php  echo T_("Enter the password of a client to add:"); ?></label>
		<input name="password" type="text" class="form-control pull-left" size="40" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" />
		<div class="form-inline">&emsp;&emsp;
			<input type="button" onclick="generate();" value="<?php echo T_("Generate");?>" class="btn btn-default fa" />&emsp;<?php echo T_("Password with");?>&ensp;
			<input type="number" name="number" value="25" min="8" max="50" style="width:5em;"  class="form-control" />&ensp;<?php echo T_("characters");?>
		</div>
	</div>
<?php  } ?>
	<div class="form-group form-inline">
		<label class="control-label col-sm-3"><?php  echo T_("Enter the first name of a client to add:"); ?></label>
		<input name="firstname" type="text" class="form-control pull-left" size="40" />
	</div>
	<div class="form-group form-inline">
		<label class="control-label col-sm-3"><?php  echo T_("Enter the surname of a client to add:"); ?></label>
		<input name="lastname" type="text" class="form-control pull-left" size="40"/>
	</div>
	<div class="form-group form-inline">
		<label class="control-label col-sm-3"><a href='timezonetemplate.php'><?php  echo T_("Enter the Time Zone of a client to add:"); echo "</a></label>";
		display_chooser($rs,"Time_zone_name","Time_zone_name",false,false,false,false,array("value",get_setting("DEFAULT_TIME_ZONE")),true,"pull-left"); ?>
	</div>
	<input type="submit" value="<?php  echo T_("Add a client"); ?>" class="btn btn-primary col-sm-offset-3 col-sm-3"/>
</form>

<?php 
xhtml_foot();
?>
