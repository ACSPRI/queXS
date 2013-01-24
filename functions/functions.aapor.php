<?php 
/**
 * Functions to calculate AAPOR outcomes based on Standard Definitions here:
 *
 * The American Association for Public Opinion Research. 2004. Standard Definitions: Final Dispositions of Case Codes and Outcome Rates for Surveys. 3rd edition. Lenexa, Kansas: AAPOR.
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
 * Return a cleaned array containing all required elements
 * for AAPOR calculations
 *
 * @param array $a Array containing some of I,P,R,NC,O,UH,UO keys
 * @return array  Array containing all I,P,R,NC,O,UH,UO keys
 */
function aapor_clean($a)
{
	if (!isset($a['I']) || empty($a['I'])) $a['I'] = 0;
	if (!isset($a['P']) || empty($a['P'])) $a['P'] = 0;
	if (!isset($a['R']) || empty($a['R'])) $a['R'] = 0;
	if (!isset($a['NC']) || empty($a['NC'])) $a['NC'] = 0;
	if (!isset($a['O']) || empty($a['O'])) $a['O'] = 0;
	if (!isset($a['UH']) || empty($a['UH'])) $a['UH'] = 0;
	if (!isset($a['UO']) || empty($a['UO'])) $a['UO'] = 0;	
	if (!isset($a[' ']) || empty($a[' '])) $a[' '] = 0;

	return $a;
}


/**
 * Calculate AAPOR's RR1
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Response rate as a decimal
 *
 */
function aapor_rr1($a)
{
	$d = (($a['I'] + $a['P']) + ($a['R'] + $a['NC'] + $a['O']) + ($a['UH'] + $a['UO']));
	if ($d == 0) return 0;
	return $a['I'] / $d;
}


/**
 * Calculate AAPOR's RR2
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Response rate as a decimal
 *
 */
function aapor_rr2($a)
{
	$d = (($a['I'] + $a['P']) + ($a['R'] + $a['NC'] + $a['O']) + ($a['UH'] + $a['UO']));
	if ($d == 0) return 0;
	return ($a['I'] + $a['P']) / $d;
}



/**
 * Calculate AAPOR's RR3
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @param float $e Estimated proportion of cases of unknown eligibility that are eligible
 * @return float Response rate as a decimal
 *
 */
function aapor_rr3($a,$e)
{
	$d = (($a['I'] + $a['P']) + ($a['R'] + $a['NC'] + $a['O']) + ($e*($a['UH'] + $a['UO'])));
	if ($d == 0) return 0;
	return ($a['I']) / $d;
}


/**
 * Calculate AAPOR's RR4
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @param float $e Estimated proportion of cases of unknown eligibility that are eligible
 * @return float Response rate as a decimal
 *
 */
function aapor_rr4($a,$e)
{
	$d = (($a['I'] + $a['P']) + ($a['R'] + $a['NC'] + $a['O']) + ($e*($a['UH'] + $a['UO'])));
	if ($d == 0) return 0;
	return ($a['I'] + $a['P']) / $d;
}


/**
 * Calculate AAPOR's RR5
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Response rate as a decimal
 *
 */
function aapor_rr5($a)
{
	return aapor_rr3($a,0);
}

/**
 * Calculate AAPOR's RR6
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Response rate as a decimal
 *
 */
function aapor_rr6($a)
{
	return aapor_rr4($a,0);
}


/**
 * Calculate AAPOR's COOP1
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Cooperation rate as a decimal
 *
 */
function aapor_coop1($a)
{
	$d =  (($a['I'] + $a['P']) + $a['R'] + $a['O']);
	if ($d == 0) return 0;
	return $a['I'] / $d;
}

/**
 * Calculate AAPOR's COOP2
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Cooperation rate as a decimal
 *
 */
function aapor_coop2($a)
{
	$d = (($a['I'] + $a['P']) + $a['R'] + $a['O']);
	if ($d == 0) return 0;
	return ($a['I'] + $a['P']) / $d;
}

/**
 * Calculate AAPOR's COOP3
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Cooperation rate as a decimal
 *
 */
function aapor_coop3($a)
{
	$d = (($a['I'] + $a['P']) + $a['R']);
	if ($d == 0) return 0;
	return $a['I'] / $d;
}

/**
 * Calculate AAPOR's COOP4
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Cooperation rate as a decimal
 *
 */
function aapor_coop4($a)
{
	$d = (($a['I'] + $a['P']) + $a['R']);
	if ($d == 0) return 0;
	return ($a['I'] + $a['P']) / $d;
}


/**
 * Calculate AAPOR's REF1
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Refusal rate as a decimal
 *
 */
function aapor_ref1($a)
{
	$d = (($a['I'] + $a['P']) + ($a['R'] + $a['NC'] + $a['O']) + ($a['UH'] + $a['UO']));
	if ($d == 0) return 0;
	return $a['R'] / $d;
}


/**
 * Calculate AAPOR's REF2
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @param float $e Estimated proportion of cases of unknown eligibility that are eligible
 * @return float Refusal rate as a decimal
 *
 */
function aapor_ref2($a,$e)
{
	$d = (($a['I'] + $a['P']) + ($a['R'] + $a['NC'] + $a['O']) + ($e*($a['UH'] + $a['UO'])));
	if ($d == 0) return 0;
	return ($a['R']) / $d;
}


/**
 * Calculate AAPOR's REF3
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Refusal rate as a decimal
 *
 */
function aapor_ref3($a)
{
	return aapor_ref2($a,0);
}

/**
 * Calculate AAPOR's CON1
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Contact rate as a decimal
 *
 */
function aapor_con1($a)
{
	$d = (($a['I'] + $a['P']) + ($a['R'] + $a['NC'] + $a['O']) + ($a['UH'] + $a['UO']));
	if ($d == 0) return 0;
	return (($a['I'] + $a['P']) + $a['R'] +$a['O']) / $d;
}


/**
 * Calculate AAPOR's CON2
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @param float $e Estimated proportion of cases of unknown eligibility that are eligible
 * @return float Contact rate as a decimal
 *
 */
function aapor_con2($a,$e)
{
	$d = (($a['I'] + $a['P']) + ($a['R'] + $a['NC'] + $a['O']) + ($e*($a['UH'] + $a['UO'])));
	if ($d == 0) return 0;
	return (($a['I'] + $a['P']) + $a['R'] +$a['O']) / $d;
}


/**
 * Calculate AAPOR's CON3
 *
 * @param array $a Array containing I,P,R,NC,O,UH,UO keys
 * @return float Contact rate as a decimal
 *
 */
function aapor_con3($a)
{
	return aapor_con2($a,0);
}




?>
