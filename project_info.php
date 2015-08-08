<?php 
/**
 * Display information about this project
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2011
 * @package queXS
 * @subpackage user
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
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

/**
 * Database
 */
include ("db.inc.php");

/**
 * Operator
 */
include ("functions/functions.operator.php");

$js = false;
if (AUTO_LOGOUT_MINUTES !== false)
        $js = array("include/jquery/jquery-1.4.2.min.js","js/childnap.js");

xhtml_head(T_("Project information"),true,false,$js,false,false,false,false,false);

$operator_id = get_operator_id();

if ($operator_id)
{
	$questionnaire_id = get_questionnaire_id($operator_id);

	if ($questionnaire_id)
	{

		$sql = "SELECT info
			FROM questionnaire
			WHERE questionnaire_id = '$questionnaire_id'";

		$rs = $db->GetRow($sql);

		if (!empty($rs))
			print $rs['info'];
	}
	else
		print "<p>" . T_("No case") . "</p>";
}

xhtml_foot();

?>
