<?php 
/**
 * Respondent selection - Business answers
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
 * @copyright Australian Consortium for Social and Political Research Inc 2007,2008
 * @package queXS
 * @subpackage user
 * @link http://www.acspri.org.au/ queXS was writen for Australian Consortium for Social and Political Research Incorporated (ACSPRI)
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include ("config.inc.php");

/**
 * XHTML
 */
include ("functions/functions.xhtml.php");

/**
 * Language
 */
include ("lang.inc.php");

$js = array("js/popup.js","include/jquery/jquery-1.4.2.min.js","include/jquery-ui/jquery-ui.min.js");

if (AUTO_LOGOUT_MINUTES !== false)
{  
        $js[] = "js/childnap.js";
}


xhtml_head(T_("Respondent Selection - Business answers"),true,array("css/rs.css","include/jquery-ui/jquery-ui.min.css"), $js);


?>
<p class='rstext'><?php  echo T_("Sorry to bother you, I have called the wrong number")?></p>

<p class='rsoption'><a href="javascript:parent.location.href = 'index_interface2.php?outcome=16&endcase=endcase'"><?php  echo T_("End call with outcome: Business number"); ?></a></p>
<p class='rsoption'><a href="rs_intro_interface2.php"><?php  echo T_("Go Back"); ?></a></p>
<?php 

xhtml_foot();

?>
