<?php 
/**
 * Import a sample from a Headered CSV file
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
"../include/bootstrap/css/bootstrap.min.css", 
"../include/bootstrap/css/bootstrap-theme.min.css",
"../include/font-awesome/css/font-awesome.css",
"../include/bootstrap-toggle/css/bootstrap-toggle.min.css",
"../css/custom.css"
			);
$js_head = array(
"../include/jquery/jquery.min.js",
"../include/bootstrap/js/bootstrap.min.js",
"../include/bootstrap-toggle/js/bootstrap-toggle.min.js",
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
		print "<div class='alert alert-danger col-md-offset-2'><p>" . T_("Error:") . " $error </p><p>" . T_("Please check imported file, go back in your browser and fix the problem") . "</p></div>";

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
	<input type="hidden" name="description" value="<?php  if (isset($_POST['description'])) print($_POST['description']); ?>"/>
	<input type="hidden" name="filename" value="<?php  echo $tmpfname; ?>"/>
	<div class="form-group">
		<label class="col-md-4 control-label" for="submit"></label>
		<div class="col-md-4">
			<button id="submit" type="submit" name="import_form" class="btn btn-primary"><i class="fa fa-plus-square-o fa-lg"></i>&emsp;<?php  echo T_("Add sample"); ?></button>
		</div>
	</div>
	
	</form>


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
	
		<label class="col-md-4 control-label" for="file"><?php  echo T_("Choose the CSV sample file to upload"); ?>:</label>
		<div class="col-md-4">
			<input id="file" name="file" class="filestyle" required data-buttonBefore="true" data-iconName="fa fa-folder-open fa-lg text-primary " data-buttonText="<?php  echo T_("Select file"); ?>..." type="file" accept="<?php echo $csv; ?>" />
		</div>
	</div>

	<!-- Text input-->
	<div class="form-group">
		<label class="col-md-4 control-label" for="description"><?php  echo T_("Sample description"); ?>:</label>  
		<div class="col-md-4">
			<input id="description" name="description" type="text" required placeholder="<?php  echo T_("Enter new sample name..."); ?>" class="form-control">
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
