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

xhtml_head(T_("Administrative Tools"),true,array("../css/table.css","../css/admin.css"),array("../js/link.js"));

print "<div id='menu'><h3>" . T_("Questionnaire creation and management") . "</h3>";
print "<div><a href=\"javascript:link('mainobj','new.php');\">" . T_("Create a new questionnaire") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','" . LIME_URL . "admin/admin.php');\">" . T_("Administer questionnaires with Limesurvey") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','import.php');\">" . T_("Import a sample file (in CSV form)") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','assignsample.php');\">" . T_("Assign samples to questionnaires") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','questionnaireprefill.php');\">" . T_("Set values in questionnaire to pre fill") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','operators.php');\">" . T_("Add operators to the system") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','operatorquestionnaire.php');\">" . T_("Assign operators to questionnaires") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','operatorskill.php');\">" . T_("Modify operator skills") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','addshift.php');\">" . T_("Shift management (add/remove)") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','dataoutput.php');\">" . T_("Data output") . "</a></div>";

print "<h3>" . T_("Questionnaire progress") . "</h3>";
print "<div><a href=\"javascript:link('mainobj','displayappointments.php');\">" . T_("Display all future appointments") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','outcomes.php');\">" . T_("Questionnaire outcomes") . "</a></div>";

print "<h3>" . T_("Performance") . "</h3>";
print "<div><a href=\"javascript:link('mainobj','operatorperformance.php');\">" . T_("Operator performance") . "</a></div>";

print "<h3>" . T_("Client management") . "</h3>";
print "<div><a href=\"javascript:link('mainobj','clients.php');\">" . T_("Add clients to the system") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','clientquestionnaire.php');\">" . T_("Assign clients to questionnaires") . "</a></div>";

print "<h3>" . T_("Supervisor functions") . "</h3>";
print "<div><a href=\"javascript:link('mainobj','supervisor.php');\">" . T_("Assign outcomes to cases") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','samplesearch.php');\">" . T_("Search the sample") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','callhistory.php');\">" . T_("Call history") . "</a></div>";
print "<div><a href=\"javascript:link('mainobj','shiftreport.php');\">" . T_("Shift reports") . "</a></div>";


print "</div>";


print "<div id='main'><object class='embeddedobject' id='mainobj' data='new.php' standby='Loading panel...' type='application/xhtml+xml'><p>Error, try with Firefox</p></object></div>";


xhtml_foot();

?>

