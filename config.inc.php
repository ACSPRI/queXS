<?
/**
 * Configuration file
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
 * The default time zone
 */
define('DEFAULT_TIME_ZONE', 'Australia/Victoria');


/**
 * Date time format for displaying 
 * 
 * see http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_date-format 
 * for configuration details for DATE_TIME_FORMAT and TIME_FORMAT
 */
define('DATE_TIME_FORMAT','%a %d %b %I:%i%p'); 

/**
 * Time format for displaying: see above for mySQL details
 */
define('TIME_FORMAT','%I:%i%p');

/**
 * Flag for VoIP with Asterisk to be enabled or not
 */
define('VOIP_ENABLED',true);

/**
 * The Asterisk server address
 */
define('VOIP_SERVER','asterisk.dcarf');

/**
 * The meet me room id for the VOIP Server
 */
define('MEET_ME_ROOM','5000');

/**
 * Whether to automatically pop up a coding window when the respondent hangs up
 */
define('AUTO_POPUP',false);

/**
 * The extension of the supervisor for dialing the supervisor
 */
define('SUPERVISOR_EXTENSION',"0392517290");

/**
 * The path to limesurvey
 */
define('LIME_PATH', 'include/limesurvey/');

/**
 * The path to queXS from the server root
 */
define('QUEXS_PATH', '/quexs/');

/**
 * The complete URL to limesurvey
 */
define('LIME_URL','http://' . $_SERVER['SERVER_NAME'] . QUEXS_PATH . LIME_PATH);

/**
 * The complete URL to this copy of queXS
 */
define('QUEXS_URL','http://' . $_SERVER['SERVER_NAME'] . QUEXS_PATH);

/**
 * The default locale (language)
 */
define('DEFAULT_LOCALE','en');


/**
 * Path to ADODB
 */
define('ADODB_PATH',dirname(__FILE__).'/../adodb/');

/**
 * Database configuration for queXS
 */
define('DB_USER', 'quexs');
define('DB_PASS', 'quexs');
define('DB_HOST', 'database.dcarf');
define('DB_NAME', 'quexs');
define('DB_TYPE', 'mysqlt');

/**
 * The prefix for the limesurvey database
 */
define('LIME_PREFIX','lime_');

/**
 * Limesurvey database information
 */
define('LDB_USER', 'quexs');
define('LDB_PASS', 'quexs');
define('LDB_HOST', 'database.dcarf');
define('LDB_NAME', 'quexs');
define('LDB_TYPE', 'mysqlt');


?>
