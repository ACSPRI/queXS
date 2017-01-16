<?php 
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
 * Date time format for displaying 
 * 
 * see http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_date-format 
 * for configuration details for DATE_TIME_FORMAT and TIME_FORMAT
 */
if (!defined('DATE_TIME_FORMAT')) define('DATE_TIME_FORMAT','%a %d %b %I:%i%p'); 

/**
 * Date format for displaying: see above for mySQL details
 */
if (!defined('DATE_FORMAT')) define('DATE_FORMAT','%a %d %b');

/**
 * Time format for displaying: see above for mySQL details
 */
if (!defined('TIME_FORMAT')) define('TIME_FORMAT','%I:%i%p');

/**
 * Flag for VoIP with Asterisk to be enabled or not
 */
if (!defined('VOIP_ENABLED')) define('VOIP_ENABLED',false);

/**
 * Allow operators to choose their extension?
 */
if (!defined('ALLOW_OPERATOR_EXTENSION_SELECT')) define('ALLOW_OPERATOR_EXTENSION_SELECT',false);

/**
 * The Asterisk server address
 */
if (!defined('VOIP_SERVER')) define('VOIP_SERVER','localhost');

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
 * The Asterisk context to originate calls from (in FreePBX this should be 
 * 'from-internal' otherwise try 'default'
 */
if (!defined('ORIGINATE_CONTEXT')) define('ORIGINATE_CONTEXT','from-internal');

/**
 * The freepbx root path (if installed) otherwise false to disable freepbx integration
 */
if (!defined ('FREEPBX_PATH')) define('FREEPBX_PATH', false);

/**
 * The freepbx database name
 */
if (!defined ('FREEPBX_DATABASE')) define('FREEPBX_DATABASE', 'asterisk');

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
if (!defined('SUPERVISOR_EXTENSION')) define('SUPERVISOR_EXTENSION',"1000");

/**
 * The path to queXS from the server root
 */
if (!defined('QUEXS_PATH')) define('QUEXS_PATH', '/quexs/');

/**
 * The port queXS is running on (default blank, use :8080 for example if using 
 * port 8080)
 */
if (!defined('QUEXS_PORT')) define('QUEXS_PORT', '');

$protocol = "http://";

if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) 
{
	$protocol = "https://";
}

/**
 * The complete URL to this copy of queXS
 */
if (!defined('QUEXS_URL')) define('QUEXS_URL', $protocol . $_SERVER['SERVER_NAME'] . QUEXS_PORT . QUEXS_PATH);

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
if (!defined('ADODB_PATH')) define('ADODB_PATH','/usr/share/php/adodb/');

/**
 * Whether to automatically assign a call as complete if VoIP disabled at the end of a completed questionnaire
 */
if (!defined('AUTO_COMPLETE_OUTCOME')) define('AUTO_COMPLETE_OUTCOME',false);

/**
 * The number of minutes of inactivity to wait before automatically logging out an operator with an open screen
 * False to disable
 */
if (!defined('AUTO_LOGOUT_MINUTES')) define('AUTO_LOGOUT_MINUTES',false);

/**
 * The number of seconds to wait before automatically dialling the first available number or ending the case 
 * where an operator has an open screen
 * False to disable
 */
if (!defined('AUTO_DIAL_SECONDS')) define('AUTO_DIAL_SECONDS',false);

/**
 * The default tab to start on on the main screen
 */
if (!defined('DEFAULT_TAB')) define('DEFAULT_TAB','casenotes');

/**
 * The default tab to start on for appointments
 */
if (!defined('DEFAULT_TAB_APPOINTMENT')) define('DEFAULT_TAB_APPOINTMENT','casenotes');

/**
 * Show tabs?
 */
if (!defined('TAB_CONTACTDETAILS')) define('TAB_CONTACTDETAILS', false);
if (!defined('TAB_CASENOTES')) define('TAB_CASENOTES', true);
if (!defined('TAB_CALLLIST')) define('TAB_CALLLIST', true);
if (!defined('TAB_SHIFTS')) define('TAB_SHIFTS', true);
if (!defined('TAB_APPOINTMENTLIST')) define('TAB_APPOINTMENTLIST', true);
if (!defined('TAB_MYAPPOINTMENTLIST')) define('TAB_MYAPPOINTMENTLIST', true);
if (!defined('TAB_PERFORMANCE')) define('TAB_PERFORMANCE', true);
if (!defined('TAB_CALLHISTORY')) define('TAB_CALLHISTORY', true);
if (!defined('TAB_PROJECTINFO')) define('TAB_PROJECTINFO', true);
if (!defined('TAB_INFO')) define('TAB_INFO', true);

/**
 * Enable a header expander for the main page to shrink/expand when not in use?
 */
if (!defined('HEADER_EXPANDER')) define('HEADER_EXPANDER', false);

/**
 * Enable a header expander for the main page to shrink/expand when clicking on an arrow?
 */
if (!defined('HEADER_EXPANDER_MANUAL')) define('HEADER_EXPANDER_MANUAL', false);

/**
 * Contract header at start of questionnaire
 */
if (!defined('HEADER_EXPANDER_QUESTIONNAIRE')) define('HEADER_EXPANDER_QUESTIONNAIRE', false);

/**
 * Define how many minutes between each system sort (defaults to 5 as this is a common interval for appointments)
 */
if (!defined('SYSTEM_SORT_MINUTES')) define ('SYSTEM_SORT_MINUTES',5);

/**
 * Allow page refreshing
 */
if (!defined('ALLOW_PAGE_REFRESH')) define ('ALLOW_PAGE_REFRESH',true);

/**
 * Allow operator to select respondent from list or add respondents
 */
if (!defined('ALLOW_RESPONDENT_SELECTOR')) define ('ALLOW_RESPONDENT_SELECTOR',true);

/**
 * Display a faster alternate interface where VoIP is disabled
 */
if (!defined('ALTERNATE_INTERFACE')) define ('ALTERNATE_INTERFACE',false);

/**
 * Number of log records to display
 */
if (!defined('PROCESS_LOG_LIMIT')) define('PROCESS_LOG_LIMIT', 500);

/**
 * Temporary upload directory
 */ 
if (!defined('TEMPORARY_DIRECTORY')) define('TEMPORARY_DIRECTORY', "/tmp");

/**
 * Database configuration for queXS
 */
if (!defined('DB_USER')) define('DB_USER', 'quexs');
if (!defined('DB_PASS')) define('DB_PASS', 'quexs');
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'quexs');
if (!defined('DB_TYPE')) define('DB_TYPE', 'mysqli');

if (!defined('COMPANY_NAME')) define ('COMPANY_NAME', 'queXS ');
if (!defined('ADMIN_PANEL_NAME')) define ('ADMIN_PANEL_NAME',' Administration Panel');

/** 
 * Session name
 * - If changed must also be changed in the lime_settings_global table
 */
if (!defined('LS_SESSION_NAME')) define ('LS_SESSION_NAME', 'ls28629164789259281352');

/* CAS Authentication
 *
 */
if (!defined('CAS_ENABLED')) define ('CAS_ENABLED', false);
if (!defined('CAS_AUTH_SERVER')) define ('CAS_AUTH_SERVER', 'www.acspri.org.au');
if (!defined('CAS_AUTH_PORT')) define ('CAS_AUTH_PORT', 443);
if (!defined('CAS_AUTH_URI')) define ('CAS_AUTH_URI', 'cas');


/**
 * Debugging
 */
if (!defined('DEBUG')) define('DEBUG',false);

?>
