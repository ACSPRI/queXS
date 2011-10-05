<?
/**
 * Display the main page including all panels and tabs
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
 *
 */

/**
 * Configuration file
 */
include_once("config.inc.php");

/**
 * XHTML functions
 */
include_once("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include_once("functions/functions.operator.php");


xhtml_head(T_("queXS"), false, array("css/index_interface2.css","css/tabber.css"),array("js/tabber.js"));

?>

<div id="casefunctions">
	<div class='box'><a href="index_interface2.php"><? echo T_("Get a new case"); ?></a></div>
	<div class='box'><a href="endwork.php"><? echo T_("End work"); ?></a></div>
</div>

<div id="content">
</div>

<div id="respondent">
</div>

<div id="qstatus">
</div>


<div id="calllist">
<div class="tabber" id="tab-main">
     <div class="tabbertab">
	  <h2><? echo T_("Info"); ?></h2>
	  <div id="div-info" class="tabberdiv"><?xhtml_object("info.php","main-info");?></div>
     </div>


</div>
</div>


<?

xhtml_foot();

?>
