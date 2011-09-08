<?
/**
 * Create an appointment for the currently assigned case
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
 * @subpackage user
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include ("config.inc.php");

/**
 * Database file
 */
include ("db.inc.php");

/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Calendar functions
 */
include("functions/functions.calendar.php");

/**
 * Operator functions
 */
include("functions/functions.operator.php");

/**
 * Input functions
 */
include("functions/functions.input.php");

$db->StartTrans();

$operator_id = get_operator_id(); 
$questionnaire_id = get_questionnaire_id($operator_id);
$case_id = get_case_id($operator_id);

if (!$case_id){
	xhtml_head(T_("Appointment error"));
	print("<div>" . T_("You have not been assigned a case therefore cannot create an appointment") . "</div>");
	xhtml_foot();
	$db->CompleteTrans();
	exit();
}

if(isset($_POST['firstName']) && isset($_POST['lastName']) && isset($_POST['Time_zone_name']))
{
	//add a new respondent
	add_respondent($case_id,$_POST['firstName'],$_POST['lastName'],$_POST['Time_zone_name']);	
}


if(isset($_GET['phonenum']))
{
	//add a new phone number
	add_contact_phone($case_id,$_GET['phonenum']);	
}


if(isset($_POST['start']) && isset($_POST['end']) && isset($_POST['day']) && isset($_POST['month']) && isset($_POST['year']) && isset($_POST['respondent_id']) && isset($_POST['contact_phone_id']))
{
	//make appointment
	
	$day = bigintval($_POST['day']);
	$month = bigintval($_POST['month']);
	$year = bigintval($_POST['year']);
	$respondent_id = bigintval($_POST['respondent_id']);
	$contact_phone_id = bigintval($_POST['contact_phone_id']);
	$start = $_POST['start'];
	$end = $_POST['end'];
	$call_attempt_id = get_call_attempt($operator_id,false);

	make_appointment($respondent_id,$case_id,$contact_phone_id,$call_attempt_id,$day,$month,$year,$start,$end);

	$db->CompleteTrans();

	xhtml_head(T_("Appointment made"),true,false,false,"onload='parent.closePopup();'");
	xhtml_foot();
	exit();
}


$js = array("js/window.js");
if (AUTO_LOGOUT_MINUTES !== false)
{
        $js[] = "include/jquery-ui/js/jquery-1.4.2.min.js";
	$js[] = "js/childnap.js";
}
xhtml_head(T_("Appointment"),true,array("css/respondent.css"),$js);

//select a respondent from a list or create a new one
print("<p>" . T_("Select a respondent") . "</p>");

$sr = display_respondent_list($case_id,isset($_GET['respondent_id'])?bigintval($_GET['respondent_id']):false,true);

if ($sr != false) $_GET['respondent_id'] = $sr;

if(isset($_GET['respondent_id']) && $_GET['respondent_id'] == 0) 
{
	//ability to create a new one
	?>
	<p><? echo T_("Create new respondent:"); ?></p>
	<form id="addRespondent" method="post" action="">
	<? display_respondent_form(); ?>
	<p><input type="submit" value="<? echo T_("Add this respondent"); ?>"/></p>
	</form>
	<?
}
else if(isset($_GET['respondent_id']))
{
	$respondent_id = bigintval($_GET['respondent_id']);

	if (isset($_GET['d']) && isset($_GET['m']) && isset($_GET['y']))
	{
		$day = bigintval($_GET['d']);
		$month = bigintval($_GET['m']);
		$year = bigintval($_GET['y']);

		display_calendar($respondent_id,$questionnaire_id,$year,$month,$day);

		display_time($questionnaire_id,$respondent_id,$day,$month,$year,isset($_GET['start'])?$_GET['start']:false,isset($_GET['end'])?$_GET['end']:false);

		if (isset($_GET['end']) && isset($_GET['start']))
		{
			$list = return_contact_phone_list($case_id);

			print "<p>" . T_("Select phone number:") . "</p>";

			if (isset($_GET['contact_phone_id'])) $contact_phone_id = bigintval($_GET['contact_phone_id']);
			else $contact_phone_id = -1;

			print "<div><select id='phonenum' name='phonenum' onchange=\"LinkUp('phonenum')\"><option>" . T_("None") . "</option>";
			foreach($list as $l)
			{
				$id = $l['contact_phone_id'];
				$selected = "";
				if ($id == $contact_phone_id) $selected="selected='selected'";
				print "<option value='?contact_phone_id=$id&amp;start={$_GET['start']}&amp;end={$_GET['end']}&amp;d=$day&amp;y=$year&amp;m=$month&amp;respondent_id=$respondent_id' $selected>{$l['phone']} - {$l['description']}</option>";

			}
			print "<option value='?contact_phone_id=0&amp;start={$_GET['start']}&amp;end={$_GET['end']}&amp;d=$day&amp;y=$year&amp;m=$month&amp;respondent_id=$respondent_id' class='addresp'>" . T_("Add new phone number") . "</option></select></div>";


			if(isset($_GET['contact_phone_id']))
			{
				$contact_phone_id = bigintval($_GET['contact_phone_id']);
	
				if ($contact_phone_id == 0)
				{
					//ability to add a new one
					?>
					<p><? echo T_("Add new phone number (with area code, eg 0398761234):"); ?></p>
					<form id="addPhone" method="get" action="">
					<p><input type="text" name="phonenum"/></p>
					<p><input type="submit" value="<? echo T_("Add this phone number"); ?>"/>
					<input type="hidden" name="start" value="<? print $_GET['start']; ?>"/>
					<input type="hidden" name="end" value="<? print $_GET['end']; ?>"/>
					<input type="hidden" name="d" value="<? print $day; ?>"/>
					<input type="hidden" name="m" value="<? print $month; ?>"/>
					<input type="hidden" name="y" value="<? print $year; ?>"/>
					<input type="hidden" name="respondent_id" value="<? print $respondent_id; ?>"/></p>
					</form>
					<?
				}
				else
				{
					foreach($list as $l)
					{
						if ($l['contact_phone_id'] == $contact_phone_id)
							$phonenum = $l['phone'];
					}
	
					?>
					<p><? echo T_("Appointment:"); ?></p>
					<form id="appointment" method="post" action="">
					<? print "<p>" . T_("Accept appointment from ") .convert_time($_GET['start']).T_(" till ").convert_time($_GET['end']).T_(" on ") . "$day/$month/$year? " . T_("on") . " $phonenum </p>"; ?>
					<p>
					<input type="hidden" name="start" value="<? print $_GET['start']; ?>"/>
					<input type="hidden" name="end" value="<? print $_GET['end']; ?>"/>
					<input type="hidden" name="day" value="<? print $day; ?>"/>
					<input type="hidden" name="month" value="<? print $month; ?>"/>
					<input type="hidden" name="year" value="<? print $year; ?>"/>
					<input type="hidden" name="respondent_id" value="<? print $respondent_id; ?>"/>
					<input type="hidden" name="contact_phone_id" value="<? print $contact_phone_id; ?>"/>
					<input type="submit" value="Make appointment"/></p>
					</form>
					<?
				}
			}
			
		}
			
	}
	else
	{
		display_calendar($respondent_id,$questionnaire_id);
	}
}



xhtml_foot();

$db->CompleteTrans();

?>
