<?php 
/**
 * Run the VoIP monitoring process and monitor it via the database
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
 * @subpackage admin
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include (dirname(__FILE__) . "/../config.inc.php");

/**
 * Database file
 */
include (dirname(__FILE__) . "/../db.inc.php");

/**
 * Process
 */
include (dirname(__FILE__) . "/../functions/functions.process.php");

/**
 * VoIP functions
 */
include(dirname(__FILE__) . "/../functions/functions.voip.php");

/**
 * Update the database with the new data from the running script
 *
 * @param string $buffer The data to append to the database
 * @return string Return a blank string to empty the buffer
 */
function update_callback($buffer)
{
	global $process_id;

	process_append_data($process_id,$buffer);

	return ""; //empty buffer
}


//get the arguments from the command line (this process_id)
if ($argc != 2) exit();

$process_id = $argv[1];

//register an exit function which will tell the database we have ended
register_shutdown_function('end_process',$process_id);

//all output send to database instead of stdout
ob_start('update_callback',2);

print "Monitoring " . VOIP_SERVER;

$t = new voipWatch();
$t->connect(VOIP_SERVER,VOIP_ADMIN_USER,VOIP_ADMIN_PASS,true);

if ($t->isConnected()) 
{
	$t->watch($process_id);
}
else
{
	print T_("Cannot connect to VoIP Server");
}

ob_get_contents();
ob_end_clean();

?>
