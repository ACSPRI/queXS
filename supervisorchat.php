<?php 
/**
 * Chat with the supervisor using XMPP
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
 * @subpackage user
 * @link http://www.acspri.org.au/software/ queXS was writen for ACSPRI
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
 * Operator functions
 */
include("functions/functions.operator.php");

$js = array("include/jquery-ui/js/jquery-1.4.2.min.js","include/strophe/strophe.js","js/supervisorchat.js");
if (AUTO_LOGOUT_MINUTES !== false)
        $js[] = "js/childnap.js";

xhtml_head(T_("Supervisor chat"),true,array("css/table.css"),$js);

$operator_id = get_operator_id();

//javascript to activate connection for this user
print "<script type='text/javascript'>";
print "var SUPERVISOR_NAME = 'Adam';";
print "var conn = new Strophe.Connection('/xmpp-httpbind');";
print "conn.connect('mim@surveys.acspri.org.au', 'mim', OnConnectionStatus);";
print "</script>";

//table for chat messages
print "<table class='tclass' id='chattable'><tbody><tr><th>" . T_("From") . "</th><th>" . T_("Message") . "</th></tr></tbody></table>";

xhtml_foot();


?>
