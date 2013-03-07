<?php 
/**
 * Functions relating to appointment times and calendars
 *
 *
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
 * @subpackage functions
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */


/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

/**
 * Database file
 */
include_once(dirname(__FILE__).'/../db.inc.php');


/**
 * Add a phone number to a case (via the contact_phone table)
 *
 * @param int $case_id Case id
 * @param int $phone Phone number
 * @return bool Result false if failed to add else true
 */
function add_contact_phone($case_id,$phone)
{
	global $db;
	
	$sql = "INSERT INTO contact_phone
		(contact_phone_id,case_id,priority,phone)
		SELECT NULL,$case_id,MAX(priority)+1,'$phone'
		FROM contact_phone
		WHERE case_id = $case_id";

	return $db->Execute($sql);

}


/**
 * Add an appointment to a case (via the appointment table)
 *
 * @param int $respondent_id The respondent
 * @param int $case_id The case
 * @param int $contact_phone_id The contact phone number to call on
 * @param int $call_attempt_id the current call attempt
 * @param int $d the day of the month
 * @param int $m the month of the year
 * @param int $y the year (4 digit)
 * @param string $start The time in the format HH:MM:SS
 * @param string $end The time in the format HH:MM:SS
 * @param string|int $require_operator_id False if for any operator otherwise restrict this appointment to a particular operator
 * @return bool Result false if failed to add else true
 */
function make_appointment($respondent_id,$case_id,$contact_phone_id,$call_attempt_id,$d,$m,$y,$start,$end,$require_operator_id = false)
{
	global $db;

	$start = "$y-$m-$d $start";
	$end= "$y-$m-$d $end";

	if ($require_operator_id == false)
		$require_operator_id = "NULL";

	$sql = "INSERT INTO `appointment`
		(appointment_id,case_id,contact_phone_id,call_attempt_id,start,end,require_operator_id,respondent_id,completed_call_id)
		SELECT NULL,'$case_id','$contact_phone_id','$call_attempt_id',CONVERT_TZ('$start',r.Time_zone_name,'UTC'),CONVERT_TZ('$end',r.Time_zone_name,'UTC'),$require_operator_id,$respondent_id,NULL
		FROM respondent as r
		WHERE r.respondent_id = '$respondent_id'";

	return $db->Execute($sql);
}



/**
 * Take a 24 hour time in the format: hh:mm:ss and make it more human
 *
 * @param string $time The time in the format HH:MM:SS
 * @return string Human readable time
 */
function convert_time($time)
{
	$h = intval(substr($time,0,2));
	$m = substr($time,3,2);
	$s = intval(substr($time,5,2));
	$p = "am";

	if ($h == 12)
	{
		$p = "pm";
	}
	else if ($h == 0)
	{
		$h = 12;
		$p = "am";
	}
	else if ($h > 12)
	{
		$h = $h - 12;
		$p = "pm";
	}

	//Use the TIME_FORMAT string as defined in mysql http://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_date-format
	$from = array("%H","%h","%I","%i","%S","%s","%p");
	$to = array(substr($time,0,2),$h,$h,$m,$s,$s,$p);
	return str_replace($from,$to,TIME_FORMAT);
}

/**
 * Return whether or not a questionnaire is restricted to work in shifts
 *
 * @param int $questionnaire_id Questionnaire ID
 * @return bool True if shift restricted else false
 */
function is_shift_restricted($questionnaire_id)
{
	global $db;

	$sql = "SELECT restrict_appointments_shifts as r
		FROM questionnaire
		WHERE questionnaire_id = '$questionnaire_id'";

	$rs = $db->GetRow($sql);

	if ($rs['r'] == 1) return true;
	return false;
}


/**
 * Get an arary containing all contact phone numbers for a case
 *
 * @param int $case_id The case ID
 * @return array|bool An array of contact phone numbers else false if none
 */
function return_contact_phone_list($case_id)
{
	global $db;

	$sql = "SELECT contact_phone_id,phone,description
		FROM contact_phone
		WHERE case_id = '$case_id'";

	$rs = $db->GetAll($sql);

	return $rs;
}


/**
 * Print a list of respodnents for a case
 * with an HTML GET request with the respondent_id
 *
 * @param int $case_id The case ID
 * @param bool|int $respondent_id The respondent already selected or false if none selected
 * @param bool $first Select the first respondent available if none specifically selected?
 */
function display_respondent_list($case_id,$respondent_id = false,$first = false)
{
	global $db;

	$sql = "SELECT respondent_id,firstName,lastName
		FROM respondent
		WHERE case_id = '$case_id'";

	$rs = $db->GetAll($sql);

	print "<div><select id='respondent_id' name='respondent_id' onchange=\"LinkUp('respondent_id')\"><option>" . T_("None") . "</option>";
	if (!empty($rs))
	{
		foreach($rs as $r)
		{
			$rid = $r['respondent_id'];
			if ($respondent_id == false && $first == true)
			{
				$first = false;
				$selected = "selected='selected'";
				$respondent_id = $rid;
			}
			else
				$selected = "";
			if ($rid == $respondent_id) $selected="selected='selected'";
			print "<option value='?respondent_id=$rid' $selected>{$r['firstName']} {$r['lastName']}</option>";
		}
	}
	print "<option value='?respondent_id=0' class='addresp'>" . T_("Add respondent") . "</option></select></div>";

	return $respondent_id;
}



/**
 * Print an XHTML form for adding or modifying respondent details
 *
 * @param bool|int $respondent_id The respondent already selected or false if none selected
 * @param bool|int $case_id The case to add a respondent to or false if none selected
 */
function display_respondent_form($respondent_id = false,$case_id = false)
{
	global $db;

	/**
	 * Use the default time zone if none other to work with
	 */
	$rzone = DEFAULT_TIME_ZONE;
	$fn = "";
	$ln = "";

	if ($respondent_id)
	{
		$sql = "SELECT Time_zone_name,firstName,lastName
			FROM respondent
			WHERE respondent_id = '$respondent_id'";
	
		$rs = $db->GetRow($sql);
		$rzone = $rs['Time_zone_name'];
		$fn = $rs['firstName'];
		$ln = $rs['lastName'];
	}
	else if ($case_id)
	{
		$sql = "SELECT Time_zone_name
			FROM respondent
			WHERE case_id = '$case_id'";
	
		$rs = $db->GetRow($sql);
	}


	$sql = "SELECT Time_zone_name
		FROM timezone_template";

	$rs = $db->Execute($sql);

	print "<div><label for='firstName'>" . T_("First name:") . " </label><input type=\"text\" id='firstName' name=\"firstName\" value=\"$fn\"/></div>
	       <div><label for='lastName'>" . T_("Last  name:") . " </label><input type=\"text\" id='lastName' name=\"lastName\" value=\"$ln\"/></div>";

	/**
	 * Display the current respondent zone in a drop down box with other zones from timezone_template
	 */
	print "<div><label>" . T_("Time Zone:") . " ".$rs->GetMenu('Time_zone_name',$rzone,false)."</label></div>";

}


/**
 * Print shift details in XHTML based on the given day
 * Display start time, and if start time selected display end time also
 * Used generally for making an appointment
 *
 * @see make_appointment()
 *
 * @param int $questionnaire_id The questionnaire id
 * @param int $respondent_id The respondent id
 * @param int $day the day of the month
 * @param int $month the month of the year
 * @param int $year the year (4 digit)
 * @param string $time The time in the format HH:MM:SS
 * @param string $timeend The time in the format HH:MM:SS
 *
 * @todo Handle questionnaires without shift restrictions
 */
function display_time($questionnaire_id,$respondent_id, $day, $month, $year, $time = false, $timeend = false)
{
	global $db;

	
	$restricted = is_shift_restricted($questionnaire_id);


	if ($restricted)
	{
		/**
		 * Select shift start and end times for this day
		 */
		$sql = "     SELECT s.shift_id, HOUR(TIME(CONVERT_TZ(s.start,'UTC',r.Time_zone_name))) as sh, MINUTE(TIME(CONVERT_TZ(s.start,'UTC',r.Time_zone_name))) as sm, !(DATE(CONVERT_TZ(NOW(),'System',r.Time_zone_name)) = DATE(CONVERT_TZ(s.start,'UTC',r.Time_zone_name))) as today,  HOUR(TIME(CONVERT_TZ(NOW(),'System',r.Time_zone_name))) as eh, MINUTE(TIME(CONVERT_TZ(NOW(),'System',r.Time_zone_name))) as em, (TIME_TO_SEC( TIMEDIFF( TIME( CONVERT_TZ( s.end, 'UTC', r.Time_zone_name ) ) , TIME( CONVERT_TZ( s.start, 'UTC', r.Time_zone_name ) ) ) ) /300) as intervals, TIME(CONVERT_TZ(s.start,'UTC',r.Time_zone_name)) as start, TIME(CONVERT_TZ(s.end,'UTC',r.Time_zone_name)) as  end
					FROM shift as s, respondent as r, `case` as c
					WHERE r.respondent_id = '$respondent_id'
					AND r.case_id = c.case_id
					AND s.questionnaire_id = c.questionnaire_id
					AND DAY(CONVERT_TZ(s.start,'UTC', r.Time_zone_name)) = '$day'
					AND MONTH(CONVERT_TZ(s.start,'UTC', r.Time_zone_name)) = '$month'
					AND YEAR(CONVERT_TZ(s.start,'UTC', r.Time_zone_name)) = '$year'
					ORDER BY s.start ASC";
	
	}
	else
		$sql = "SELECT  0 as sh, 0 as sm, !(DATE(CONVERT_TZ(NOW(),'System',r.Time_zone_name)) = DATE(CONVERT_TZ('$year-$month-$day 00:00:00','UTC',r.Time_zone_name))) as today,  HOUR(TIME(CONVERT_TZ(NOW(),'System',r.Time_zone_name))) as eh, MINUTE(TIME(CONVERT_TZ(NOW(),'System',r.Time_zone_name))) as em, (TIME_TO_SEC( TIMEDIFF( TIME( CONVERT_TZ(DATE_ADD( CURDATE( ) , INTERVAL '23:59:59' HOUR_SECOND ) , 'System', r.Time_zone_name ) ) , TIME( CONVERT_TZ( CURDATE(), 'System', r.Time_zone_name ) ) ) ) /300) as intervals, TIME(CONVERT_TZ(CURDATE(),'System',r.Time_zone_name)) as start, TIME(CONVERT_TZ(DATE_ADD( CURDATE( ) , INTERVAL '23:59:59' HOUR_SECOND ),'System',r.Time_zone_name)) as  end
			FROM respondent as r
			WHERE r.respondent_id = '$respondent_id'";

	
	$rs = $db->GetAll($sql);

	print "<div class=\"shifts\">";
	foreach($rs as $r)
	{
		print "<p>" . T_("Shift from:") . " ".convert_time($r['start']).T_(" till ")." ".convert_time($r['end'])."</p>";
	}
	print "</div>";



	print "<p>";
	print "<select name=\"start\" id=\"start\" onchange=\"LinkUp('start')\"><option value=\"?y=$year&amp;m=$month&amp;d=$day&amp;respondent_id=$respondent_id\">" . T_("Start Time") . "</option>";
	foreach ($rs as $r)
	{
		$sh = $r['sh'];
		$sm = $r['sm'];
		$intervals = $r['intervals'];

		//eh and em should be the current respondent time
		$eh = $r['eh'];
		$em = $r['em'];

		//today = 0 if the shift is today otherwise 1
		$today = $r['today'];

		/**
		 * Display only times in the future and within the shift in 5 minute intervals
		 *
		 */
		for ($i = 0; $i <= $intervals; $i++)
		{
			$t = str_pad($sh,2,"0",STR_PAD_LEFT).":".str_pad($sm,2,"0",STR_PAD_LEFT).":00";

			if ($today  || ($sh > $eh || ($sh == $eh && $sm > $em)))
			{
				$selected = "";
				if ($t == $time) $selected = "selected=\"selected\"";
			
				print "<option value=\"?y=$year&amp;m=$month&amp;d=$day&amp;respondent_id=$respondent_id&amp;start=$t\" $selected>".convert_time($t)."</option>";
			}

		

			$sm += 5;
			if ($sm >= 60) 
			{
				$sh++;
				if ($sh >= 24) $sh -= 24;
				$sm -= 60;
			}

		}

	}
	print "</select>";

	if ($time)
	{
		$eh = substr($time,0,2);
		$em = substr($time,3,2);

		print "<select name=\"end\" id=\"end\" onchange=\"LinkUp('end')\"><option value=\"?y=$year&amp;m=$month&amp;d=$day&amp;respondent_id=$respondent_id&amp;start=$time\">" . T_("End Time") . "</option>";
		foreach ($rs as $r)
		{
			$sh = $r['sh'];
			$sm = $r['sm'];
			$intervals = $r['intervals'];
	

			/**
			 * Display only times after the start time and within the shift in 5 minute intervals
			 *
			 */
			for ($i = 0; $i <= $intervals; $i++)
			{
				$t = str_pad($sh,2,"0",STR_PAD_LEFT).":".str_pad($sm,2,"0",STR_PAD_LEFT).":00";

				if ($sh > $eh || ($sh == $eh && $sm > $em))
				{
					$selected = "";
					if ($t == $timeend) $selected = "selected=\"selected\"";
		
					print "<option value=\"?y=$year&amp;m=$month&amp;d=$day&amp;respondent_id=$respondent_id&amp;start=$time&amp;end=$t\" $selected>".convert_time($t)."</option>";
				}	
				
				$sm += 5;
				if ($sm >= 60) 
				{
					$sh++;
					if ($sh >= 24) $sh -= 24;
					$sm -= 60;
				}
				
			}
	
		}
		print "</select>";
	}

	print "<input type=\"hidden\" name=\"respondent_id\" value=\"$respondent_id\"/>";
	print "<input type=\"hidden\" name=\"y\" value=\"$year\"/>";
	print "<input type=\"hidden\" name=\"m\" value=\"$month\"/>";
	print "<input type=\"hidden\" name=\"d\" value=\"$day\"/>";
	print "</p>";
}

/**
 * Print a tabular calendar for selecting dates for appointments
 * Based on code from the PEAR package
 *
 * @link http://pear.php.net/package/Calendar PEAR Calendar
 * @link http://pearcalendar.sourceforge.net/examples/3.php Example this code based on
 *
 * @see make_appointment()
 * @see display_time()
 *
 * @param int $respondent_id The respondent id
 * @param int $questionnaire_id The questionnaire id
 * @param bool|int $day the day of the month if selected else false
 * @param bool|int $month the month of the year if selected else false
 * @param bool|int $year the year (4 digit) if selected else false
 *
 */
function display_calendar($respondent_id, $questionnaire_id, $year = false, $month = false, $day = false)
{
	global $db;

	/**
	 * PEAR Caldendar Weekday functions
	 */
	include_once('Calendar/Month/Weekdays.php');
	
	/**
	 * PEAR Caldendar Day functions
	 */
	include_once('Calendar/Day.php');
	
	/**
	 * See if questionnaire has shift restrictions
	 */
	$restricted = is_shift_restricted($questionnaire_id);

	$rtime = strtotime(get_respondent_time($respondent_id));

	$y = date('Y',$rtime);
	$m = date('m',$rtime);
	$d = date('d',$rtime);

	if (!$year) $year = $y;
	if (!$month) $month = $m;
	if (!$day) $day = $d;

	$ttoday = new Calendar_Day($y,$m,$d);

	$Month = new Calendar_Month_Weekdays($year,$month);

	// Construct strings for next/previous links
	$PMonth = $Month->prevMonth('object'); // Get previous month as object
	$prev = '?y='.$PMonth->thisYear().'&amp;m='.$PMonth->thisMonth().'&amp;d='.$PMonth->thisDay().'&amp;respondent_id='.$respondent_id;
	$NMonth = $Month->nextMonth('object');
	$next = '?y='.$NMonth->thisYear().'&amp;m='.$NMonth->thisMonth().'&amp;d='.$NMonth->thisDay().'&amp;respondent_id='.$respondent_id;

	// Build the days in the month
	$Month->build();
	?>
	<table class="calendar">
	<caption>
	<?php echo ( date('F Y',$Month->getTimeStamp())); ?>
	</caption>
	<tr>
	<th>M</th>
	<th>T</th>
	<th>W</th>
	<th>T</th>
	<th>F</th>
	<th>S</th>
	<th>S</th>
	</tr>
<?php 

	while ( $Day = $Month->fetch() ) {

	    // Build a link string for each day
	    $link =	'?y='.$Day->thisYear().
			'&amp;m='.$Day->thisMonth().
			'&amp;d='.$Day->thisDay().
			'&amp;respondent_id='.$respondent_id;

	    $today = "";
	    if ($year == $Day->thisYear() && $month == $Day->thisMonth() && $day == $Day->thisDay()) $today = "today";


	    // isFirst() to find start of week
	    if ( $Day->isFirst() )
		echo ( "<tr>\n" );

	    if ( $Day->isSelected() ) {
	       echo ( "<td class=\"selected $today\">".$Day->thisDay()."</td>\n" );
	    } else if ( $Day->isEmpty() ) {
		echo ( "<td>&nbsp;</td>\n" );
	    } else {
		//if it is in the past -> unavailable
		if ($Day->getTimeStamp() < $ttoday->getTimeStamp())
		{
			echo ( "<td class=\"notavailable\">".$Day->thisDay()."</td>\n" );
		}
		//if there are shift restrictions, restrict
		else if ($restricted)
		{
			$rs = $db->Execute("    SELECT s.shift_id
						FROM shift as s
						LEFT JOIN respondent as r on (r.respondent_id = '$respondent_id')
						WHERE s.questionnaire_id = '$questionnaire_id'
						AND DAY(CONVERT_TZ(s.start,'UTC',r.Time_zone_name)) = '{$Day->thisDay()}'
						AND MONTH(CONVERT_TZ(s.start,'UTC',r.Time_zone_name)) = '{$Day->thisMonth()}'
						AND YEAR(CONVERT_TZ(s.start,'UTC',r.Time_zone_name)) = '{$Day->thisYear()}'");

			if (!empty($rs) && $rs->RecordCount() == 0)
			{
			       echo ( "<td class=\"notavailable $today\">".$Day->thisDay()."</td>\n" );
			}
			else
			{
				echo ( "<td class=\"$today\"><a href=\"".$link."\">".$Day->thisDay()."</a></td>\n" );
			}

		}
		else
		    echo ( "<td class=\"$today\"><a href=\"".$link."\">".$Day->thisDay()."</a></td>\n" );
	    }

	    // isLast() to find end of week
	    if ( $Day->isLast() )
		echo ( "</tr>\n" );
	}
	?>
	<tr>
	<td>
	<a href="<?php echo ($prev);?>" class="prevMonth">&lt;&lt; </a>
	</td>
	<td colspan="5">&nbsp;</td>
	<td>
	<a href="<?php echo ($next);?>" class="nextMonth"> &gt;&gt;</a>
	</td>
	</tr>
	</table>
	<?php 
	print "<div>" . date('l j F Y',mktime(0,0,0,$month,$day,$year)) . "</div>";
}



?>
