<?php 
/**
 * Download VoIP client on an operator by operator basis
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
 * @subpackage voip
 * @link http://www.acspri.org.au/software queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */


include_once("../config.inc.php");
include_once("../db.inc.php");

/** 
 * Authentication
 */
require ("../auth-interviewer.php");

include_once("../functions/functions.operator.php");

//---------------------
// Comes from http://fr2.php.net/tempnam
function tempdir($dir, $prefix='', $mode=0700)
{
	if (substr($dir, -1) != '/') $dir .= '/';

	if (is_writable($dir))
	{
		do
		{
			$path = $dir.$prefix.mt_rand(0, 9999999);
		} while (!mkdir($path, $mode));
	
		return $path;
	}
	else
		die(T_("Error: Cannot write to temporary directory"));
}

$tempdir = realpath(dirname(__FILE__) . '/../include/limesurvey/tmp');
$operator_id = get_operator_id();

if ($operator_id)
{

	$sql = "SELECT *,SUBSTRING_INDEX(extension, '/', -1) as ext
		FROM extension
		WHERE current_operator_id = $operator_id";

	$rs = $db->GetRow($sql);

	if (!empty($rs))
	{
		$zipdir=tempdir($tempdir);

		$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']); 
		if (preg_match('/linux/', $userAgent)) {
			//assume linux
			copy(realpath(dirname(__FILE__) . '/../voipclient'),"$zipdir/voipclient");
			$f1 = "$zipdir/voipclient";
			$f2 = "$zipdir/startvoip";
			file_put_contents($f2, "./voipclient -i -u {$rs['ext']} -p {$rs['password']} -h " . $_SERVER['SERVER_NAME']);

		} 
		else
		{
			//assume windows
			copy(realpath(dirname(__FILE__) . '/../voipclient.exe'),"$zipdir/voipclient.exe");
			$f1 = "$zipdir/voipclient.exe";
			$f2 = "$zipdir/startvoip.bat";
			file_put_contents($f2, "voipclient.exe -i -u {$rs['ext']} -p {$rs['password']} -h " . $_SERVER['SERVER_NAME']);

		}

		require_once(dirname(__FILE__) . "/../include/limesurvey/admin/classes/phpzip/phpzip.inc.php");
		$z = new PHPZip();
		$zipfile="$tempdir/voipclient.zip";
		$z->Zip($zipdir, $zipfile);

		unlink($f1);
		unlink($f2);
		rmdir($zipdir);

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="voipclient.zip"'); 
		header('Content-Transfer-Encoding: binary');
		// load the file to send:
		readfile($zipfile);
		unlink($zipfile);
	}
}
exit();
