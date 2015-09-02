<?php 
/**
 * Start the VoIP process from the command line 
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
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright Australian Consortium for Social and Political Research (ACSPRI) 2011
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

if (php_sapi_name() !== "cli")
{
  die();
}

/**
 * Configuration file
 */
include (realpath(dirname(__FILE__) . "/../config.inc.php"));

/**
 * Database file
 */
include (realpath(dirname(__FILE__) . "/../db.inc.php"));

/**
 * Process
 */
include (realpath(dirname(__FILE__) . "/../functions/functions.process.php"));

//end any other process
$p = is_process_running(2);
if ($p)
{
	kill_process($p);
	end_process($p);
}
start_process(realpath(dirname(__FILE__) . "/../admin/systemsortprocess.php"),2);


$p = is_process_running();
if ($p)
{
	kill_process($p);
	end_process($p);
}
start_process(realpath(dirname(__FILE__) . "/../admin/process.php"));


?>
