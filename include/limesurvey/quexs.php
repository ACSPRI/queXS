<?
/**
 * Functions for interacting with queXS
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
 *
 */

/**
 * Configuration file
 */
require_once(dirname(__FILE__).'/../../config.inc.php');



/**
 * Get information from the sample
 *
 * @param string $variable The bit of information from the sample
 * @param int $case_id The case id
 * @return string The information or a blank string if none found
 *
 */
function get_sample_variable($variable,$case_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT s.val as r
		FROM sample_var as s
		JOIN `case` as c on (c.case_id = '$case_id' and s.sample_id = c.sample_id)
		WHERE s.var = '$variable'";

	$rs = $db->GetRow($sql);


	if (empty($rs)) return "";

	return $rs['r'];

}

/**
 * Get information about the operator
 *
 * @param string $variable The bit of information about the operator (eg firstName)
 * @param int $operator_id The operator id
 * @return string The information or a blank string if none found
 *
 */
function get_operator_variable($variable,$operator_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT `$variable` as r
		FROM operator
		WHERE operator_id = '$operator_id'";

	$rs = $db->GetRow($sql);

	if (empty($rs)) return "";

	return $rs['r'];

}

/**
 * Get information about the respondent
 *
 * @param string $variable The bit of information about the respondent (eg firstName)
 * @param int $respondent_id The respondent id
 * @return string The information or a blank string if none found
 *
 */
function get_respondent_variable($variable,$respondent_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT `$variable` as r
		FROM respondent
		WHERE respondent_id = '$respondent_id'";

	$rs = $db->GetRow($sql);


	if (empty($rs)) return "";

	return $rs['r'];

}

/**
 * Return the current operator id based on PHP_AUTH_USER
 *
 * @return bool|int False if none otherwise the operator id
 *
 */
function get_operator_id()
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT operator_id
		FROM operator
		WHERE username = '{$_SERVER['PHP_AUTH_USER']}'
		AND enabled = 1";

	$o = $db->GetRow($sql);

	if (empty($o)) 	return false;

	return $o['operator_id'];

}



/**
 * Get the current case id
 *
 * @param int $operator_id The operator id
 * @param bool $create True if a case can be created
 * @return bool|int False if no case available else the case_id
 */
function get_case_id($operator_id)
{

	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);
	

	/**
	 * See if case already assigned
	 */
	$sql = "SELECT case_id
		FROM `case`
		WHERE current_operator_id = '$operator_id'";

	$r1 = $db->GetRow($sql);

	if (!empty($r1) && isset($r1['case_id'])) return $r1['case_id'];
	return false;
}

/**
 * Get the respondent id
 *
 *
 */
function get_respondent_id($case_id,$operator_id)
{
	$db = newADOConnection(DB_TYPE);
	$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$db->SetFetchMode(ADODB_FETCH_ASSOC);


	$sql = "SELECT respondent_id
		FROM `call_attempt`
		WHERE case_id = '$case_id'
		AND operator_id = '$operator_id'
		AND end IS NULL";

	$row = $db->GetRow($sql);

	if (!empty($row) && isset($row['respondent_id'])) return $row['respondent_id'];
	return false;
}

/**
 * Replace placeholders in a string with data for this case/operator
 * 
 * @param string $string The string 
 * @param int $operator_id The operator id
 * @param int $case_id The case id
 * @return string The string with replaced text
 *
 */
function quexs_template_replace($string)
{
	$operator_id = get_operator_id();
	$case_id = get_case_id($operator_id);
	$respondent_id = get_respondent_id($case_id,$operator_id);

	while (stripos($string, "{Respondent:") !== false)
	{
		$answreplace=substr($string, stripos($string, "{Respondent:"), stripos($string, "}", stripos($string, "{Respondent:"))-stripos($string, "{Respondent:")+1);
		$answreplace2=substr($answreplace, 12, stripos($answreplace, "}", stripos($answreplace, "{Respondent:"))-12);
		$answreplace3=get_respondent_variable($answreplace2,$respondent_id);
		$string=str_replace($answreplace, $answreplace3, $string);
	}


	while (stripos($string, "{Operator:") !== false)
	{
		$answreplace=substr($string, stripos($string, "{Operator:"), stripos($string, "}", stripos($string, "{Operator:"))-stripos($string, "{Operator:")+1);
		$answreplace2=substr($answreplace, 10, stripos($answreplace, "}", stripos($answreplace, "{Operator:"))-10);
		$answreplace3=get_operator_variable($answreplace2,$operator_id);
		$string=str_replace($answreplace, $answreplace3, $string);
	}

	while (stripos($string, "{Sample:") !== false)
	{
		$answreplace=substr($string, stripos($string, "{Sample:"), stripos($string, "}", stripos($string, "{Sample:"))-stripos($string, "{Sample:")+1);
		$answreplace2=substr($answreplace, 8, stripos($answreplace, "}", stripos($answreplace, "{Sample:"))-8);
		$answreplace3=get_sample_variable($answreplace2,$case_id);
		$string=str_replace($answreplace, $answreplace3, $string);
	}

	return $string;
}



?>
