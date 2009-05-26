<?
/**
 * Default Configuration file
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
 *
 * DO NOT MODIFY THIS FILE!
 *
 * Make your configuration changes in config.inc.php
 *
 *
 */


/**
 * The default time zone
 */
if (!defined('DEFAULT_TIME_ZONE')) define('DEFAULT_TIME_ZONE', 'Australia/Victoria');


/**
 * Date time format for displaying 
 * 
 * see http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_date-format 
 * for configuration details for DATE_TIME_FORMAT and TIME_FORMAT
 */
if (!defined('DATE_TIME_FORMAT')) define('DATE_TIME_FORMAT','%a %d %b %I:%i%p'); 

/**
 * Time format for displaying: see above for mySQL details
 */
if (!defined('TIME_FORMAT')) define('TIME_FORMAT','%I:%i%p');

/**
 * Flag for VoIP with Asterisk to be enabled or not
 */
if (!defined('VOIP_ENABLED')) define('VOIP_ENABLED',false);

/**
 * The Asterisk server address
 */
if (!defined('VOIP_SERVER')) define('VOIP_SERVER','asterisk.dcarf');

/**
 * The Asterisk server username for the monitor interface
 */
if (!defined('VOIP_ADMIN_USER')) define('VOIP_ADMIN_USER','admin');

/**
 * The Asterisk server password for the monitor interface
 */
if (!defined('VOIP_ADMIN_PASS')) define('VOIP_ADMIN_PASS','amp111');

/**
 * The Asterisk server port for the monitor interface
 */
if (!defined('VOIP_PORT')) define('VOIP_PORT','5038');

/**
 * The meet me room id for the VOIP Server
 */
if (!defined('MEET_ME_ROOM')) define('MEET_ME_ROOM','5000');

/**
 * Whether to automatically pop up a coding window when the respondent hangs up
 */
if (!defined('AUTO_POPUP')) define('AUTO_POPUP',false);

/**
 * The extension of the supervisor for dialing the supervisor
 */
if (!defined('SUPERVISOR_EXTENSION')) define('SUPERVISOR_EXTENSION',"0392517290");

/**
 * The path to limesurvey
 */
if (!defined('LIME_PATH')) define('LIME_PATH', 'include/limesurvey/');

/**
 * The path to queXS from the server root
 */
if (!defined('QUEXS_PATH')) define('QUEXS_PATH', '/quexs/');

/**
 * The complete URL to limesurvey
 */
if (!defined('LIME_URL')) define('LIME_URL','http://' . $_SERVER['SERVER_NAME'] . QUEXS_PATH . LIME_PATH);

/**
 * The complete URL to this copy of queXS
 */
if (!defined('QUEXS_URL')) define('QUEXS_URL','http://' . $_SERVER['SERVER_NAME'] . QUEXS_PATH);

/**
 * The default locale (language)
 */
if (!defined('DEFAULT_LOCALE')) define('DEFAULT_LOCALE','en');


/**
 * PHP Executables (for forking when running background processes)
 */
if (!defined('WINDOWS_PHP_EXEC')) define('WINDOWS_PHP_EXEC', "start /b php");
if (!defined('PHP_EXEC')) define('PHP_EXEC', "php");

/**
 * Path to ADODB
 */
if (!defined('ADODB_PATH')) define('ADODB_PATH',dirname(__FILE__).'/../adodb/');

/**
 * Database configuration for queXS
 */
if (!defined('DB_USER')) define('DB_USER', 'quexs');
if (!defined('DB_PASS')) define('DB_PASS', 'quexs');
if (!defined('DB_HOST')) define('DB_HOST', 'databasedev.dcarf');
if (!defined('DB_NAME')) define('DB_NAME', 'quexs');
if (!defined('DB_TYPE')) define('DB_TYPE', 'mysqlt');

/**
 * The prefix for the limesurvey database
 */
if (!defined('LIME_PREFIX')) define('LIME_PREFIX','lime_');

/**
 * Limesurvey database information (default is same as queXS database)
 */
if (!defined('LDB_USER')) define('LDB_USER', DB_USER);
if (!defined('LDB_PASS')) define('LDB_PASS', DB_PASS);
if (!defined('LDB_HOST')) define('LDB_HOST', DB_HOST);
if (!defined('LDB_NAME')) define('LDB_NAME', DB_NAME);
if (!defined('LDB_TYPE')) define('LDB_TYPE', DB_TYPE);

/**
 * Debugging
 */
if (!defined('DEBUG')) define('DEBUG',false);

?>
