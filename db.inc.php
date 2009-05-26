<?
/**
 * Database configuration file
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
 * @subpackage configuration
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */



/**
 * Set locale
 */
include_once(dirname(__FILE__).'/lang.inc.php');

/**
 * Include ADODB
 */
if (!(include_once(ADODB_PATH . 'adodb.inc.php')))
{
	print "<p>ERROR: Please modify config.inc.php for ADODB_PATH to point to your ADODb installation</p>";
}

/**
 * Include ADODB session handling functions
 */
if (!(include_once(ADODB_PATH . 'session/adodb-session2.php')))
{
	print "<p>ERROR: Please modify config.inc.php for ADODB_PATH to point to your ADODb installation</p>";
}

define('ADODB_OUTP',"outputDebug");

/**
 * Output for debugging
 */
function outputDebug($text,$newline)
{
	error_log($text,0);	
}

//if PEAR not installed:
set_include_path("."); //TEMP ONLY
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__).'/include/pear/');

//global database variable
$db = newADOConnection(DB_TYPE);
$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$db->SetFetchMode(ADODB_FETCH_ASSOC);
if (DEBUG == true) $db->debug = true;

//global database variable for limesurvey
$ldb = newADOConnection(LDB_TYPE);
$ldb->Connect(LDB_HOST, LDB_USER, LDB_PASS, LDB_NAME);
$ldb->SetFetchMode(ADODB_FETCH_ASSOC);
if (DEBUG == true) $ldb->debug = true;

//store session in database (see sessions2 table)
ADOdb_Session::config(DB_TYPE, DB_HOST, DB_USER, DB_PASS, DB_NAME,$options=false);

?>
