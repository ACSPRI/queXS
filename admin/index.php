<?php
/**
 * Display an index of Admin tools
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
 * Language file
 */
include ("../lang.inc.php");

/**
 * Config file
 */
include ("../config.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

xhtml_head(T_("Administrative Tools"),true,array("../css/table.css","../css/admin.css","../css/timepicker.css"));

print "<div id='menu'><ul class='navmenu'>";

print "<li><h3>" . T_("Questionnaire creation and management") . "</h3>";
print "<ul><li><a href=\"?page=new.php\">" . T_("Create a new questionnaire") . "</a></li>";
print "<li><a href=\"?page=questionnairelist.php\">" . T_("Questionnaire management") . "</a></li>";
print "<li><a href=\"?page=" . LIME_URL . "admin/admin.php\">" . T_("Administer questionnaires with Limesurvey") . "</a></li></ul></li>";

print "<li><h3>" . T_("Sample/List management") . "</h3><ul>";
print "<li><a href=\"?page=import.php\">" . T_("Import a sample file (in CSV form)") . "</a></li>";
print "<li><a href=\"?page=assignsample.php\">" . T_("Assign samples to questionnaires") . "</a></li>";
print "<li><a href=\"?page=questionnaireprefill.php\">" . T_("Set values in questionnaire to pre fill") . "</a></li></ul></li>";

print "<li><h3>" . T_("Quota management") . "</h3><ul>";
print "<li><a href=\"?page=quota.php\">" . T_("Quota management") . "</a></li>";
print "<li><a href=\"?page=quotarow.php\">" . T_("Quota row management") . "</a></li></ul></li>";

print "<li><h3>" . T_("Operator management") . "</h3><ul>";
print "<li><a href=\"?page=operators.php\">" . T_("Add operators to the system") . "</a></li>";
print "<li><a href=\"?page=operatorlist.php\">" . T_("Operator management") . "</a></li>";
print "<li><a href=\"?page=operatorquestionnaire.php\">" . T_("Assign operators to questionnaires") . "</a></li>";
print "<li><a href=\"?page=operatorskill.php\">" . T_("Modify operator skills") . "</a></li></ul></li>";

print "<li><h3>" . T_("Availability and shift management") . "</h3><ul>";
print "<li><a href=\"?page=availabilitygroup.php\">" . T_("Manage availablity groups") . "</a></li>";
print "<li><a href=\"?page=questionnaireavailability.php\">" . T_("Assign availabilities to questionnaires") . "</a></li>";
print "<li><a href=\"?page=addshift.php\">" . T_("Shift management (add/remove)") . "</a></li></ul></li>";

print "<li><h3>" . T_("Questionnaire progress") . "</h3>";
print "<ul><li><a href=\"?page=displayappointments.php\">" . T_("Display all future appointments") . "</a></li>";
print "<li><a href=\"?page=samplecallattempts.php\">" . T_("Sample call attempts report") . "</a></li>";
print "<li><a href=\"?page=quotareport.php\">" . T_("Quota report") . "</a></li>";
print "<li><a href=\"?page=outcomes.php\">" . T_("Questionnaire outcomes") . "</a></li>";
print "<li><a href=\"?page=dataoutput.php\">" . T_("Data output") . "</a></li></ul></li>";

print "<li><h3>" . T_("Performance") . "</h3>";
print "<ul><li><a href=\"?page=operatorperformance.php\">" . T_("Operator performance") . "</a></li></ul></li>";

print "<li><h3>" . T_("Client management") . "</h3>";
print "<ul><li><a href=\"?page=clients.php\">" . T_("Add clients to the system") . "</a></li>";
print "<li><a href=\"?page=clientquestionnaire.php\">" . T_("Assign clients to questionnaires") . "</a></li></ul></li>";

print "<li><h3>" . T_("Supervisor functions") . "</h3>";
print "<ul><li><a href=\"?page=supervisor.php\">" . T_("Assign outcomes to cases") . "</a></li>";
print "<li><a href=\"?page=samplesearch.php\">" . T_("Search the sample") . "</a></li>";
print "<li><a href=\"?page=callhistory.php\">" . T_("Call history") . "</a></li>";
print "<li><a href=\"?page=shiftreport.php\">" . T_("Shift reports") . "</a></li></ul></li>";

print "<li><h3>" . T_("System settings") . "</h3>";
print "<ul><li><a href=\"?page=timezonetemplate.php\">" . T_("Set default timezone list") . "</a></li>";
print "<li><a href=\"?page=shifttemplate.php\">" . T_("Set default shift times") . "</a></li>";
print "<li><a href=\"?page=callrestrict.php\">" . T_("Set call restriction times") . "</a></li>";
print "<li><a href=\"?page=centreinfo.php\">" . T_("Set centre information") . "</a></li>";
print "<li><a href=\"?page=systemsort.php\">" . T_("Start and monitor system wide case sorting") . "</a></li></ul></li>";

if (VOIP_ENABLED)
{
	print "<li><h3>" . T_("VoIP") . "</h3>";
	print "<ul><li><a href=\"?page=voipmonitor.php\">" . T_("Start and monitor VoIP") . "</a></li>";
	print "<li><a href=\"?page=operatorlist.php\">" . T_("Operator management") . "</a></li>";
	print "<li><a href=\"?page=extensionstatus.php\">" . T_("Extension status") . "</a></li></ul></li>";
}

print "</ul></div>";

$page = "new.php";

if (isset($_GET['page']))
	$page = $_GET['page'];

print "<div id='main'>";
xhtml_object($page,"mainobj");
print "</div>";


xhtml_foot();

?>
