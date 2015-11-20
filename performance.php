<?php 
/**
 * Display the performance of this operator
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
 * Operator functions
 */
include("functions/functions.operator.php");

/**
 * Performance functions
 */
include("functions/functions.performance.php");

$js = false;
if (AUTO_LOGOUT_MINUTES !== false)
        $js = array("include/jquery/jquery-1.4.2.min.js","js/childnap.js");

xhtml_head(T_("Performance"),true,array("css/table.css"),$js,false,false,false,false,false);

$operator_id = get_operator_id();
$questionnaire_id = get_questionnaire_id($operator_id);

if ($questionnaire_id)
{
	$shift_id = is_on_shift($operator_id);

	if ($shift_id)
	{
		/**
		 * Get the outcomes for all operators on this shift
		 *
		 */
		$rs = get_CPH_by_shift($questionnaire_id,$shift_id);
		print "<div>" . T_("This shift") . "</div>";
		xhtml_table($rs,array("firstName","completions","CPH"),array(T_("Operator"),T_("Completions"),T_("Completions per hour")),"tclass",array("operator_id" => $operator_id));
	}


	
	$rs = get_CPH_by_questionnaire($questionnaire_id);
	print "<div>" . T_("This project") . "</div>";
	xhtml_table($rs,array("firstName","completions","CPH"),array(T_("Operator"),T_("Completions"),T_("Completions per hour")),"tclass",array("operator_id" => $operator_id));
}

/* Don't display overall performance - not useful to operators?
$rs = get_CPH();
print "<div>" . T_("Overall") . "</div>";
xhtml_table($rs,array("firstName","completions","CPH"),array(T_("Operator"),T_("Completions"),T_("Completions per hour")),"tclass",array("operator_id" => $operator_id));
*/

xhtml_foot();


?>
