<?php 
/**
 * FreePBX Functions
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2007,2008,2009,2010,2011
 * @package queXS
 * @subpackage functions
 * @link http://www.acspri.org.au/software queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

include_once(dirname(__FILE__).'/../db.inc.php');

/**
 * FreePBX functions to add an extension
 * This needs to be re-implemented here as there are too many code conflicts in including freepbx code
 * The queXS database user should have access to the FreePBX database to avoid making a new connection
 * FREEPBX_DATABASE needs to be set to the freepbx database name (i.e. asterisk)
 *
 * @param string $extension The extension number
 * @param string $name The name of the extension
 * @param string $password The password for the extension
 * @return bool True if successfully added else false
 */
function freepbx_add_extension($extension,$name,$password)
{
	if (FREEPBX_PATH == false) return false; //break out if not defined

	global $db;

	$iaxelements = array(
		array('id' => $extension, 'keyword' => 'deny', 'data' => '0.0.0.0', 'flags' => '14'),
		array('id' => $extension, 'keyword' => 'mailbox', 'data' => $extension . "@device", 'flags' => '13'),
		array('id' => $extension, 'keyword' => 'accountcode', 'data' => '', 'flags' => '12'),
		array('id' => $extension, 'keyword' => 'dial', 'data' => "IAX2/" . $extension, 'flags' => '11'),
		array('id' => $extension, 'keyword' => 'allow', 'data' => '', 'flags' => '10'),
		array('id' => $extension, 'keyword' => 'disallow', 'data' => '', 'flags' => '9'),
		array('id' => $extension, 'keyword' => 'qualify', 'data' => 'yes', 'flags' => '8'),
		array('id' => $extension, 'keyword' => 'port', 'data' => '4569', 'flags' => '7'),
		array('id' => $extension, 'keyword' => 'type', 'data' => 'friend', 'flags' => '6'),
		array('id' => $extension, 'keyword' => 'host', 'data' => 'dynamic', 'flags' => '5'),
		array('id' => $extension, 'keyword' => 'context', 'data' => 'from-internal', 'flags' => '4'),
		array('id' => $extension, 'keyword' => 'transfer', 'data' => 'yes', 'flags' => '3'),
		array('id' => $extension, 'keyword' => 'secret', 'data' => $password, 'flags' => '2'),
		array('id' => $extension, 'keyword' => 'permit', 'data' => '0.0.0.0/0.0.0.0', 'flags' => '15'),
		array('id' => $extension, 'keyword' => 'requirecalltoken', 'data' => 'no', 'flags' => '16'),
		array('id' => $extension, 'keyword' => 'account', 'data' => $extension, 'flags' => '17'),
		array('id' => $extension, 'keyword' => 'callerid', 'data' => "device <" . $extension . ">", 'flags' => '18'),
		array('id' => $extension, 'keyword' => 'setvar', 'data' => "REALCALLERIDNUM=" . $extension, 'flags' => '19'),
	);	

	$devices = array('id' => $extension, 'tech' => 'iax2', 'dial' => "IAX2/$extension", 'devicetype' => 'fixed', 'user' => $extension, 'description' => $name, 'emergency_cid' => '');

	$users = array('extension' => $extension, 'password' => '', 'name' => $extension, 'voicemail' => 'novm', 'ringtimer' => '0', 'noanswer' => '', 'recording' => '', 'outboundcid' => '', 'sipname' => '', 'noanswer_cid' => '', 'busy_cid' => '', 'chanunavail_cid' => '', 'noanswer_dest' => '', 'busy_dest' => '', 'chanunavail_dest' => '', 'mohclass' => 'default');

	foreach ($iaxelements as $iax)
		$db->AutoExecute(FREEPBX_DATABASE . ".iax",$iax,'INSERT');

	$db->AutoExecute(FREEPBX_DATABASE . ".devices",$devices,'INSERT');
	$db->AutoExecute(FREEPBX_DATABASE . ".users",$users,'INSERT');
}

?>
