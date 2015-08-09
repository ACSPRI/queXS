<?php 
/**
 * Display a "full screen" view of outcomes for display on a large
 * communal screen - will change views periodically
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
 *
 */

/**
 * Configuration file
 */
include ("../config.inc.php");

/**
 * Database file
 */
include('../db.inc.php');

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Display functions
 */
include ("../functions/functions.performance.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

$shift_id = 0;
$questionnaire_id = 0;
$display_type = 0;

if (isset($_GET['shift_id'])) $shift_id = bigintval($_GET['shift_id']);
if (isset($_GET['questionnaire_id'])) $questionnaire_id = bigintval($_GET['questionnaire_id']);
if (isset($_GET['display_type'])) $display_type= bigintval($_GET['display_type']);


if ($display_type >= 6)
{
	$sql = "SELECT shift_id,questionnaire_id
		FROM shift
		WHERE start <= CONVERT_TZ(NOW(),'System','UTC')
		AND end >= CONVERT_TZ(NOW(),'System','UTC')
		AND shift_id > '$shift_id'
		ORDER BY shift_id ASC
		LIMIT 1";
	$s = $db->GetRow($sql);

	$display_type = 0;
	$shift_id = 0;
	$questionnaire_id = 0;

	if (!empty($s)) 
	{
		$shift_id = $s['shift_id'];
		$questionnaire_id = $s['questionnaire_id'];
	}
}

if ($shift_id == 0)
{
	$sql = "SELECT shift_id,questionnaire_id
		FROM shift
		WHERE start <= CONVERT_TZ(NOW(),'System','UTC')
		AND end >= CONVERT_TZ(NOW(),'System','UTC')
		ORDER BY shift_id ASC
		LIMIT 1";

	$s = $db->GetRow($sql);

	$display_type = 0;

	if (!empty($s))
	{
		$shift_id = $s['shift_id'];
		$questionnaire_id = $s['questionnaire_id'];
	}	
}

$dt1 = $display_type + 1;
xhtml_head(T_("Display"),true,array("../include/bootstrap/css/bootstrap.min.css","../include/bootstrap/css/bootstrap-theme.min.css","../css/custom.css"),false,false,"6;url=?shift_id=$shift_id&amp;questionnaire_id=$questionnaire_id&amp;display_type=$dt1");

if ($shift_id == 0 || $questionnaire_id == 0)
	display_none();
else
{
	$sql = "SELECT description
		FROM questionnaire
		WHERE questionnaire_id = '$questionnaire_id'";
	$n = $db->GetRow($sql);

	print "<h1>{$n['description']}</h1>\n";

	switch($display_type)
	{
		case 0:
			display_total_completions($questionnaire_id);
			break;
		case 1:
			display_completions_this_shift($questionnaire_id,$shift_id);
			break;
		case 2:
			display_completions_same_time_last_shift($questionnaire_id,$shift_id);
			break;
		case 3:
			display_completions_last_shift($questionnaire_id,$shift_id);
			break;
		case 4:
			display_top_cph_this_shift($questionnaire_id,$shift_id);
			break;
		case 5:
			display_top_cph($questionnaire_id);
			break;
	}
}

xhtml_foot();

?>
