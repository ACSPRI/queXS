<?php 
/**
 * Display status of case
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
include ("auth-interviewer.php");


/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include("functions/functions.operator.php");



$operator_id = get_operator_id();

if ($operator_id == false)
{
	xhtml_head(T_("Status"));
	print "<p>" . T_("No operator") . "</p>";
	xhtml_foot();
	die();
}

$state = is_on_call($operator_id);
$btext = false;

if ($state == 4 && AUTO_POPUP)
	$btext = "onload=\"poptastic('call.php')\"";

$js = array("js/popupkeep.js");
if (AUTO_LOGOUT_MINUTES !== false)
{  
        $js[] = "include/jquery/jquery-1.4.2.min.js";
        $js[] = "js/childnap.js";
}

xhtml_head(T_("Status"),true,array("css/status.css"),$js,$btext,60,false,false,false,false);

print "<div class='text'>" . get_operator_time($operator_id,DATE_TIME_FORMAT) ."</div>";

//need to determine VoIP status by confirming with the VoIP server if this operator is online

//Then confirm whether or not they are on a call (or use the database table, call to determine)

if (is_voip_enabled($operator_id))
{
  $ext = get_extension($operator_id);
	$exta = $ext;
	//Get just the start of the extension for auto dial out
	$exts = explode('/', $ext, 2);
		if (isset($exts[1]))
			$exta = $exts[1];
	$extp = get_extension_password($operator_id);
	if (get_extension_status($operator_id))
		print "<div class='online statusbutton'>" . T_("VoIP On") . "</div>";
	else
    print "<div class='offline statusbutton'><a href='voip/downloadvoipclient.php'>" . T_("VoIP Off") . "</a></div>";

  print "<div class='statusbutton'>" . T_("Extension") . ": $ext</div>";
}
else
	print "<div class='online statusbutton'>" . T_("No VoIP") . "</div>";

if (!$state || $state == 5)
{
	print("<div class='offline statusbutton'>" . T_("No call") . "</div>");
}
else if ($state == 4)
{
	print("<div class='tobecoded statusbutton'>" . T_("To be coded") . "</div>");
}
else if ($state == 1)
{
	print("<div class='online statusbutton'>" . T_("Requesting") . "</div>");
}
else if ($state == 2)
{
	print("<div class='online statusbutton'>" . T_("Ringing") . "</div>");
}
else if ($state == 3)
{
	print("<div class='online statusbutton'>" . T_("Answered") . "</div>");
}


$ca = get_call_attempt($operator_id,false);
if ($ca)
{
	print "<div class='text'>" . get_respondent_time(get_respondent_id($ca),TIME_FORMAT) ."</div>";
	if (is_on_appointment($ca)) print "<div class='online statusbutton'>" . T_("APPT") . "</div>";
	if (missed_appointment($ca)) print "<div class='tobecoded statusbutton'>" . T_("MISSED") . "</div>";
}
else
	print "<div class='text'>" . T_("No case") . "</div>";

xhtml_foot();

?>
