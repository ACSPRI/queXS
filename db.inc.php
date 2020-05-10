<?php 
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
//if (!(include_once(ADODB_PATH . 'session/adodb-session2.php')))
//{
//	print "<p>ERROR: Please modify config.inc.php for ADODB_PATH to point to your ADODb installation</p>";
//}

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
$db->ssl_ca = DB_SSL;
$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$db->SetFetchMode(ADODB_FETCH_ASSOC);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
if (DEBUG == true) $db->debug = true;

$db->Execute("set names 'utf8'");

//make sure sql_mode is right
$sqlSet = "SET SESSION sql_mode = '';";
$db->Execute($sqlSet);

/**
 * Get a setting from the database
 * 
 * @param mixed $name The setting name
 * 
 * @return mixed The setting value
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2011-01-17
 */
function get_setting($name)
{
	global $db;

	$qname = $db->qstr($name);

	$sql = "SELECT value
		FROM setting
		WHERE field LIKE $qname";

	$rs = $db->GetRow($sql);

	if (!empty($rs))
		return unserialize($rs['value']);
}

/**
 * Update or create a new setting to store in the database
 * 
 * @param mixed $name  
 * @param mixed $value An array or string to save 
 * 
 * @return bool Successful database insert/update?
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2011-01-17
 */
function set_setting($name,$value)
{
	global $db;

	$qname = $db->qstr($name);
	$qvalue = serialize($value);

	$sql = "INSERT INTO setting (setting_id,field,value)
		VALUES (NULL,$qname,'$qvalue')
		ON DUPLICATE KEY UPDATE value = '$qvalue'";


	return $db->Execute($sql);
}


/**
 * Get the IP address of the client
 */
function getIPAddress()
{
    if (!empty($_SERVER['REMOTE_ADDR']))
    {
        return $_SERVER['REMOTE_ADDR'];
    }
    else
    {
        return '127.0.0.1';
    }
}

/**
* Creates a random sequence of characters
*
* @param mixed $length Length of resulting string
* @param string $pattern To define which characters should be in the resulting string
* 
* From Limesurvey
*/
function sRandomChars($length = 15,$pattern="23456789abcdefghijkmnpqrstuvwxyz")
{
    $patternlength = strlen($pattern)-1;
    for($i=0;$i<$length;$i++)
    {   
        if(isset($key))
            $key .= $pattern{mt_rand(0,$patternlength)};
        else
            $key = $pattern{mt_rand(0,$patternlength)};
    }
    return $key;
}


?>
