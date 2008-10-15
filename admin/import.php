<?
/**
 * Import a sample from a Headered CSV file
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
include ("../config.inc.php");

/**
 * XHTML functions
 */
include ("../functions/functions.xhtml.php");

/**
 * Sample import functions
 */
include("../functions/functions.import.php");


session_start();

if (isset($_GET['import_form']))
{
	//form has been submitted
	xhtml_head(T_("Import: Validating and uploading"));

	//verify each GET field is unique (except import_form)
	$sfields = array();
	foreach($_GET as $getv => $val)
		//clean up?
		$sfields[$getv] = $val;

	$error = verify_fields($sfields);

	$description = $_GET['description'];

	if ($error == "")
	{	//verified so upload
		if (import_file($_SESSION['filename'],$description,$sfields))
		{
			print "<p>" . T_("Successfully imported file") . "</p>";
		}
		else
		{
			print "<p>" . T_("Error importing file. Please try again") . "</p>";
		}
	}
	else
		print "<p>" . T_("Error:") . " $error </p><p>" . T_("Please go back in your browser and fix the problem") . "</p>";

	//verifiy that exactly one primary phone number is selected
	//upload to database

	xhtml_foot();

}
else if (isset($_POST['import_file']))
{
	//file has been submitted
	
	xhtml_head(T_("Import: Select columns to import"));
	?>
	<form action="" method="get">
	<?

	$tmpfname = tempnam("/tmp", "FOO");
	move_uploaded_file($_FILES['file']['tmp_name'],$tmpfname);
	$_SESSION['filename'] = $tmpfname;

	display_table(get_first_row($tmpfname));



	?>
	<p><input type="hidden" name="description" value="<? if (isset($_POST['description'])) print($_POST['description']); ?>"/></p>
	<p><input type="submit" name="import_form"/></p>
	</form>

	<?
	xhtml_foot();

}
else
{
	//need to supply file to upload
	xhtml_head(T_("Import: Select file to upload"));
	?>

	<form enctype="multipart/form-data" action="" method="post">
	<p><input type="hidden" name="MAX_FILE_SIZE" value="1000000000" /></p>
	<p><? echo T_("Choose the CSV sample file to upload:"); ?><input name="file" type="file" /></p>
	<p><? echo T_("Description for file:"); ?><input name="description" type="text" /></p>
	<p><input type="submit" name="import_file"/></p>
	</form>

	<?
	xhtml_foot();

}



?>
