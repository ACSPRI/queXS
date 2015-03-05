<?php 
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

$css = array(
"../include/bootstrap-3.3.2/css/bootstrap.min.css", 
"../include/bootstrap-3.3.2/css/bootstrap-theme.min.css",
"../include/font-awesome-4.3.0/css/font-awesome.css",
"../css/bootstrap-switch.min.css",
"../css/custom.css"
			);
$js_head = array(
"../js/jquery-2.1.3.min.js",
"../include/bootstrap-3.3.2/js/bootstrap.min.js",
"../js/bootstrap-switch.min.js"
				);
$js_foot = array(
"../js/bootstrap-filestyle.min.js",
"../js/custom.js"
				);
				
if (isset($_POST['import_form']))
{
	//form has been submitted
	$subtitle = T_("Validating and uploading");
	xhtml_head(T_("Import sample") . ":",true,$css,$js_head,false,false,false,$subtitle);
	echo "<a href='?' class='btn btn-default pull-left' ><i class='fa fa-chevron-left fa-lg text-primary'></i>&emsp;" . T_("Go back") . "</a>";


	//verify each GET field is unique (except import_form)
	$sfields = array();
	foreach($_POST as $getv => $val)
		//clean up?
		$sfields[$getv] = $val;

	$error = verify_fields($sfields);

	$description = $_POST['description'];

	if ($error == "")
	{	//verified so upload
		if (import_file($_POST['filename'],$description,$sfields))
		{
			print "<div class='well text-primary col-md-offset-2'><p>" . T_("Successfully imported sample") . "&emsp;<h3>$description</h3></p></div>";
		}
		else
		{
			print -"<div class='alert alert-danger col-md-offset-2'><p>" . T_("Error importing file. Please try again") . "</p></div>";
		}
	}
	else
		print "<div class='alert alert-danger col-md-offset-2'><p>" . T_("Error:") . " $error </p><p>" . T_("Please go back in your browser and fix the problem") . "</p></div>";

	//verifiy that exactly one primary phone number is selected
	//upload to database

	xhtml_foot($js_foot);

}
else if (isset($_POST['import_file']))
{
	//file has been submitted
	$subtitle = T_("Select columns to import");
	xhtml_head(T_("Import sample") . ":",true,$css,$js_head,false,false,false,$subtitle);
	echo "<a href='' onclick='history.back();return false;' class='btn btn-default pull-left' ><i class='fa fa-chevron-left fa-lg text-primary'></i>&emsp;" . T_("Go back") . "</a>";
	
	
	print "<form action='' method='post' class='col-md-10 form-group col-md-offset-1'>"; 

	$tmpfname = tempnam(TEMPORARY_DIRECTORY, "FOO");
	move_uploaded_file($_FILES['file']['tmp_name'],$tmpfname);

	display_table(get_first_row($tmpfname));



	?>
	<p><input type="hidden" name="description" value="<?php  if (isset($_POST['description'])) print($_POST['description']); ?>"/></p>
	<input type="hidden" name="filename" value="<?php  echo $tmpfname; ?>"/>
	<div class="form-group">
		<label class="col-md-4 control-label" for="submit"></label>
		<div class="col-md-4">
			<button id="submit" type="submit" name="import_form" class="btn btn-primary"><i class="fa fa-plus-square-o fa-lg"></i>&emsp;<?php  echo T_("Add sample"); ?></button>
		</div>
	</div>
	
	</form>
	
<script type="text/javascript">
$('[switch="yes"]').bootstrapSwitch()
</script>

	<?php 
	xhtml_foot($js_foot);
}
else
{
	//need to supply file to upload
	$subtitle = T_("Select file to upload");
	xhtml_head(T_("Import sample") .":",true,$css,$js_head,false,false,false,$subtitle);
	echo "<a href='' onclick='history.back();return false;' class='btn btn-default'><i class='fa fa-chevron-left fa-lg text-primary'></i>&emsp;" . T_("Go back") . "</a>";

	$ua = $_SERVER['HTTP_USER_AGENT'];
	if (preg_match('/Firefox/i', $ua)) $csv= "text/csv"; else $csv= ".csv";
	//print "ua=" . $_SERVER['HTTP_USER_AGENT'];
	?>

	<form class="form-horizontal col-sm-12 " enctype="multipart/form-data" action="" method="post">
	<fieldset>
	
	<input type="hidden" name="MAX_FILE_SIZE" value="1000000000" />
	
	<!-- File Button --> 
	<div class="form-group">
	
		<label class="col-md-4 control-label" for="file"><?php  echo T_("Choose the CSV sample file to upload:"); ?></label>
		<div class="col-md-4">
			<input id="file" name="file" class="filestyle"  data-buttonBefore="true" data-iconName="fa fa-folder-open fa-lg text-primary " data-buttonText="<?php  echo T_("Select file"); ?>" type="file" accept="<?php echo $csv; ?>" />
		</div>
	</div>

	<!-- Text input-->
	<div class="form-group">
		<label class="col-md-4 control-label" for="description"><?php  echo T_("Sample description :"); ?></label>  
		<div class="col-md-4">
			<input id="description" name="description" type="text" placeholder="<?php  echo T_("Enter new sample name..."); ?>" class="form-control input-md">
		</div>
	</div>
	
	<!-- Button -->
	<div class="form-group">
		<label class="col-md-4 control-label" for="submit"></label>
		<div class="col-md-4">
			<button id="submit" type="submit" name="import_file" class="btn btn-primary"><i class="fa fa-plus-square-o fa-lg"></i>&emsp;<?php  echo T_("Add sample"); ?></button>
		</div>
	</div>

	</fieldset>
	</form>

<?php 
xhtml_foot($js_foot);
}

?>
