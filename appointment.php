<?php 
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
 * Authentication
 */
require ("auth-interviewer.php");

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
	$require_operator_id = false;
	if (isset($_POST['require_operator_id'])) $require_operator_id = bigintval($_POST['require_operator_id']);

	make_appointment($respondent_id,$case_id,$contact_phone_id,$call_attempt_id,$day,$month,$year,$start,$end,$require_operator_id);

	$db->CompleteTrans();

	xhtml_head(T_("Appointment made"),true,false,false,"onload='parent.closePopup();'");
	xhtml_foot();
	exit();
}


$js = array("js/window.js");
if (AUTO_LOGOUT_MINUTES !== false)
{
    $js[] = "include/jquery/jquery-1.4.2.min.js";
	$js[] = "js/childnap.js";
}
xhtml_head(T_("Create appointment"),false,array("include/bootstrap/css/bootstrap.min.css", "css/respondent.css"),$js);//"include/clockpicker/dist/bootstrap-clockpicker.min.css",

//select a respondent from a list or create a new one
print "<h4>" . T_("Respondent") . ":";
$sr = display_respondent_list($case_id,isset($_GET['respondent_id'])?bigintval($_GET['respondent_id']):false,true);
print "</h4>";
if ($sr != false) $_GET['respondent_id'] = $sr;

if(isset($_GET['respondent_id']) && $_GET['respondent_id'] == 0) 
{
	//ability to create a new one
	?>
	<p><?php  echo T_("Create new respondent:"); ?></p>
	<form id="addRespondent" method="post" action="">
	<?php  display_respondent_form(); ?>
	<p><input type="submit" value="<?php  echo T_("Add this respondent"); ?>"/></p>
	</form>
	<?php 
}
else if(isset($_GET['respondent_id']))
{
	$respondent_id = bigintval($_GET['respondent_id']);
	
	$sql = "SELECT TIME(CONVERT_TZ(NOW(),'System',r.Time_zone_name)) as tme, r.Time_zone_name as tzn FROM `respondent` as r WHERE r.respondent_id = $respondent_id";
	$ct = $db->GetRow($sql);

	print "<p>".T_("Timezone").":&ensp;".$ct['tzn']. "&emsp;".T_("Current Time").":&ensp;<b class=\"fa text-primary\">" . $ct['tme'] . "</b></p>";

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

			print "<div class=\"clearfix form-group\"><label class=\"pull-left\" style=\"padding-top: 5px;\">" . T_("Select phone number:") . "&ensp;</label>";

			if (isset($_GET['contact_phone_id'])) $contact_phone_id = bigintval($_GET['contact_phone_id']);
			else $contact_phone_id = -1;

			print "<div class=\"pull-left\"><select class=\"form-control\" id='phonenum' name='phonenum' onchange=\"LinkUp('phonenum')\"><option></option>";
			foreach($list as $l)
			{
				$id = $l['contact_phone_id'];
				$selected = "";
				if ($id == $contact_phone_id) $selected="selected='selected'";
				print "<option value='?contact_phone_id=$id&amp;start={$_GET['start']}&amp;end={$_GET['end']}&amp;d=$day&amp;y=$year&amp;m=$month&amp;respondent_id=$respondent_id' $selected>{$l['phone']} - {$l['description']}</option>";

			}
			print "<option value='?contact_phone_id=0&amp;start={$_GET['start']}&amp;end={$_GET['end']}&amp;d=$day&amp;y=$year&amp;m=$month&amp;respondent_id=$respondent_id' class='addresp'>" . T_("Add new phone number") . "</option></select></div></div>";


			if(isset($_GET['contact_phone_id']))
			{
				$contact_phone_id = bigintval($_GET['contact_phone_id']);
				
				print "<div class=\"clearfix form-group\">";
	
				if ($contact_phone_id == 0)
				{
					//ability to add a new one
					?>
					<p><?php  echo T_("Add new phone number (with area code, eg 0398761234):"); ?></p>
					<form id="addPhone" method="get" action="" class="form-inline form-group">
					<div class="pull-left"><input type="tel" maxlength="10" size="12" pattern="[0-9]{10}" class="form-control" name="phonenum"/></div>
					&emsp;<input type="submit" class="btn btn-info" value="<?php  echo T_("Add this phone number"); ?>"/>
					<input type="hidden" name="start" value="<?php  print $_GET['start']; ?>"/>
					<input type="hidden" name="end" value="<?php  print $_GET['end']; ?>"/>
					<input type="hidden" name="d" value="<?php  print $day; ?>"/>
					<input type="hidden" name="m" value="<?php  print $month; ?>"/>
					<input type="hidden" name="y" value="<?php  print $year; ?>"/>
					<input type="hidden" name="respondent_id" value="<?php  print $respondent_id; ?>"/>
					</form>
					<?php 
				}
				else
				{
					foreach($list as $l)
					{
						if ($l['contact_phone_id'] == $contact_phone_id)
							$phonenum = $l['phone'];
					}
	
					?>
					<form id="appointment" method="post" action="">
					<?php  print "<div class=\"alert alert-info\">".T_("Accept appointment from ")."<b>".$_GET['start']."</b>".T_(" till ")."<b>".$_GET['end']."</b>".T_(" on ") . "<b> $day/$month/$year </b>" . T_("on") . "<b> $phonenum  </b> ?</div>"; ?>
					<label for='require_operator_id'><?php echo T_("Appointment with myself only?"); ?>&emsp;</label>
					<input type="checkbox" id="require_operator_id" name="require_operator_id" value="<?php echo $operator_id;?>">
					<input type="hidden" name="start" value="<?php  print $_GET['start']; ?>"/>
					<input type="hidden" name="end" value="<?php  print $_GET['end']; ?>"/>
					<input type="hidden" name="day" value="<?php  print $day; ?>"/>
					<input type="hidden" name="month" value="<?php  print $month; ?>"/>
					<input type="hidden" name="year" value="<?php  print $year; ?>"/>
					<input type="hidden" name="respondent_id" value="<?php  print $respondent_id; ?>"/>
					<input type="hidden" name="contact_phone_id" value="<?php  print $contact_phone_id; ?>"/>
					<input type="submit" class="btn btn-primary pull-right" value="<?php echo T_("Schedule Appointment"); ?>"/>
					</form>
					<?php 
				}
				
				print "</div>";
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
