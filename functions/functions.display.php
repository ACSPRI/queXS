<?
/**
 * Functions relating to displaying for XHTML
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
 * Display a list of questionnaires to choose from in a drop down list
 *
 * @param int|bool $questionnaire_id The questionnaire id or false if none selecetd
 *
 */
function display_questionnaire_chooser($questionnaire_id = false)
{
	global $db;


	$sql = "SELECT questionnaire_id,description,CASE WHEN questionnaire_id = '$questionnaire_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM questionnaire
		WHERE enabled = 1";
		
	$rs = $db->GetAll($sql);

	print "<div><select id='questionnaire' name='questionnaire' onchange=\"LinkUp('questionnaire')\"><option value='?'></option>";
	if (!empty($rs))
	{
		foreach($rs as $r)
		{
			print "<option value='?questionnaire_id={$r['questionnaire_id']}' {$r['selected']}>{$r['description']}</option>";
		}
	}
	print "</select></div>";
}

/**
 * Display a list of shifts to choose from in a drop down list
 *
 * @param int $questionnaire_id The questionnaire id
 * @param int|bool $shift_id The shift id or false if none selected
 */
function display_shift_chooser($questionnaire_id, $shift_id = false)
{
	global $db;

	$sql = "SELECT s.shift_id,DATE_FORMAT(s.start,'" . DATE_TIME_FORMAT . "') as start,DATE_FORMAT(s.end,'" . TIME_FORMAT . "') as end,CASE WHEN s.shift_id = '$shift_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM shift as s
		WHERE s.questionnaire_id = '$questionnaire_id'
		ORDER BY s.start ASC";
		
	$rs = $db->GetAll($sql);

	print "<div><select id='shift' name='shift' onchange=\"LinkUp('shift')\"><option value='?questionnaire_id=$questionnaire_id'></option>";
	if (!empty($rs))
	{
		foreach($rs as $r)
		{
			print "<option value='?shift_id={$r['shift_id']}&amp;questionnaire_id=$questionnaire_id' {$r['selected']}>{$r['start']} till {$r['end']}</option>";
		}
	}
	print "</select></div>";
}

/**
 * Display a list of samples to choose from in a drop down list
 *
 * @param int $questionnaire_id The questionnaire id
 * @param int|bool $sample_import_id The sample import id or false if none selected
 */
function display_sample_chooser($questionnaire_id, $sample_import_id = false)
{
	global $db;

	$sql = "SELECT s.sample_import_id,si.description,CASE WHEN s.sample_import_id = '$sample_import_id' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM questionnaire_sample as s, sample_import as si
		WHERE s.questionnaire_id = '$questionnaire_id'
		AND s.sample_import_id = si.sample_import_id";
		
	$rs = $db->GetAll($sql);

	print "<div><select id='sample' name='sample' onchange=\"LinkUp('sample')\"><option value='?questionnaire_id=$questionnaire_id'></option>";
	if (!empty($rs))
	{
		foreach($rs as $r)
		{
			print "<option value='?sample_import_id={$r['sample_import_id']}&amp;questionnaire_id=$questionnaire_id' {$r['selected']}>{$r['sample_import_id']}: {$r['description']}</option>";
		}
	}
	print "</select></div>";
}

/**
 * Display a list of quota rows to choose from in a drop down list
 *
 * @param int $questionnaire_id The questionnaire id
 * @param int $sample_import_id The sample import id 
 * @param int|bool $qsqri The sample import id or false if none selected
 */
function display_quota_chooser($questionnaire_id, $sample_import_id, $qsqri = false)
{
	global $db;

	$sql = "SELECT q.questionnaire_sample_quota_row_id,q.description,CASE WHEN q.questionnaire_sample_quota_row_id = '$qsqri' THEN 'selected=\'selected\'' ELSE '' END AS selected
		FROM questionnaire_sample_quota_row as q
		WHERE q.questionnaire_id = '$questionnaire_id'
		AND q.sample_import_id = '$sample_import_id'";
		
	$rs = $db->GetAll($sql);

	print "<div><select id='questionnaire_sample_quota_row_id' name='questionnaire_sample_quota_row_id' onchange=\"LinkUp('questionnaire_sample_quota_row_id')\"><option value='?questionnaire_id=$questionnaire_id&amp;sample_import_id=$sample_import_id'></option>";
	if (!empty($rs))
	{
		foreach($rs as $r)
		{
			print "<option value='?sample_import_id=$sample_import_id&amp;questionnaire_id=$questionnaire_id&amp;questionnaire_sample_quota_row_id={$r['questionnaire_sample_quota_row_id']}' {$r['selected']}>{$r['questionnaire_sample_quota_row_id']}: {$r['description']}</option>";
		}
	}
	print "</select></div>";
}



?>
