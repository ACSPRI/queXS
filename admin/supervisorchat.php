<?php 
/**
 * Set if supervisor chat should be enabled and required details
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2013
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 *
 */

/**
 * Configuration file
 */
include("../config.inc.php");

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
include("../functions/functions.xhtml.php");


if (isset($_POST['update']))
{
	set_setting("bosh_service",$_POST['bosh']);
	set_setting("supervisor_xmpp",$_POST['supervisor']);
	$enable = false;
	
	if (isset($_POST['enable']))
		$enable = true;

	set_setting("chat_enabled",$enable);
}

xhtml_head(T_("Supervisor chat"),true,array("../include/bootstrap/css/bootstrap.min.css","../include/bootstrap-toggle/css/bootstrap-toggle.min.css","../css/custom.css"),array("../include/jquery/jquery.min.js", "../include/bootstrap/js/bootstrap.min.js","../include/bootstrap-toggle/js/bootstrap-toggle.min.js","../js/window.js"));

print "<p class='well'>" . T_("Allow interviewers to chat with the supervisor over XMPP (Jabber). Required is a BOSH enabled XMPP/Jabber server. The operators and the supervisor will need XMPP/Jabber accounts.") . "</p>";

		$e = get_setting("chat_enabled");
		$checked = "checked='checked'";
		if (empty($e))
			$checked = "";
?>
		<form action="" method="post" class="form-horizontal">

		<div class="form-group form-inline">
			<label class="control-label col-sm-3" for="enable"><?php  echo T_("Enable supervisor chat?"); ?>: </label>
			<input id='enable' type='checkbox' data-toggle="toggle" data-on="<?php echo T_("Yes"); ?>" data-off="<?php echo T_("No"); ?>" data-width="80" name='enable' val='1' <?php echo $checked; ?>/>
		</div>
		<div class="form-group form-inline">
			<label class="control-label col-sm-3" for="bosh"><?php  echo T_("Set BOSH URL"); ?>: </label>
			<input id='bosh' type='text' name='bosh' class="form-control pull-left" required size="60" value='<?php echo get_setting("bosh_service"); ?>'/>
		</div>
		<div class="form-group form-inline">
			<label class="control-label col-sm-3" for="supervisor"><?php  echo T_("Supervisor XMPP/Jabber id"); ?>: </label>
			<input id='supervisor' name='supervisor' type='text' class="form-control pull-left" required size="60" value='<?php echo get_setting("supervisor_xmpp"); ?>'/>
		</div>
		
		<input type="submit" id="update" name="update" class="btn btn-primary col-sm-offset-3 col-sm-3" value="<?php  echo T_("Update"); ?>"/>
		
		</form>

<?php 
xhtml_foot();
?>
