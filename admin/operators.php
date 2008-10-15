<?
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

if (isset($_POST['operator']))
{
	$operator = $db->qstr($_POST['operator'],get_magic_quotes_gpc());
	$firstname = $db->qstr($_POST['firstname'],get_magic_quotes_gpc());
	$lastname = $db->qstr($_POST['lastname'],get_magic_quotes_gpc());
	$time_zone_name = $db->qstr($_POST['Time_zone_name'],get_magic_quotes_gpc());
	$extension = $db->qstr($_POST['extension'],get_magic_quotes_gpc());
	$supervisor = 0;
	$temporary = 0;
	$refusal = 0;
	if (isset($_POST['supervisor']) && $_POST['supervisor'] == "on") $supervisor = 1;
	if (isset($_POST['refusal']) && $_POST['refusal'] == "on") $refusal = 1;
	if (isset($_POST['temporary']) && $_POST['temporary'] == "on") $temporary = 1;	
	if (!empty($_POST['operator']))
	{
		$sql = "INSERT INTO operator
			(`operator_id` ,`username` ,`firstName` ,`lastName`, `extension`, `Time_zone_name`)
			VALUES (NULL , $operator, $firstname , $lastname, $extension, $time_zone_name);";
	
		if ($db->Execute($sql))
		{
			$a = "Added: $operator";	
	
			$oid = $db->Insert_ID();

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



		}else
		{
			$a = "Could not add $operator. There may already be an operator of this name";
		}


	}
}


xhtml_head(T_("Add an operator"));

if ($a)
{
?>
	<h3><? echo $a; ?></h3>
<?
}
?>
<h1><? echo T_("Add an operator"); ?></h1>
<p><? echo T_("Adding an operator here will give the user the ability to call cases"); ?> <a href="operatorquestionnaire.php"><? echo T_("Assign Operator to Questionnaire"); ?></a> <? echo T_("tool"); ?>.</p>
<p><? echo T_("Use this form to enter the username of a user based on your directory security system. For example, if you have secured the base directory of queXS using Apache file based security, enter the usernames of the users here."); ?></p>
<form enctype="multipart/form-data" action="" method="post">
	<p><? echo T_("Enter the username of an operator to add:"); ?> <input name="operator" type="text"/></p>
	<p><? echo T_("Enter the first name of an operator to add:"); ?> <input name="firstname" type="text"/></p>
	<p><? echo T_("Enter the surname of an operator to add:"); ?> <input name="lastname" type="text"/></p>
	<p><? echo T_("Enter the Time Zone of an operator to add:"); ?> <input name="Time_zone_name" type="text" value="<? echo DEFAULT_TIME_ZONE; ?>"/></p>
	<p><? echo T_("Enter the telephone extension number:"); ?> <input name="extension" type="text"/></p>
	<p><? echo T_("Is the operator a normal interviewer?"); ?> <input name="temporary" type="checkbox" checked="checked"/></p>
	<p><? echo T_("Is the operator a supervisor?"); ?> <input name="supervisor" type="checkbox"/></p>
	<p><? echo T_("Is the operator a refusal converter?"); ?> <input name="refusal" type="checkbox"/></p>
	<p><input type="submit" value="<? echo T_("Add user"); ?>" /></p>
</form>

<?

xhtml_foot();

?>

