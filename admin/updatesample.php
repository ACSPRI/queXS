<?php 
/**
 * Update a sample from a Headered CSV file
 */

/**
 * Configuration file
 */
include ("../config.inc.php");

/**
 * Authentication file
 */
require ("auth-admin.php");

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
				
if (isset($_POST['import_file']))
{
	//file has been submitted
	$subtitle = T_("Validating and uploading");
	xhtml_head(T_("Import sample") . ":",true,$css,$js_head,false,false,false,$subtitle);
	echo "<a href='?' class='btn btn-default pull-left' ><i class='fa fa-chevron-left fa-lg text-primary'></i>&emsp;" . T_("Go back") . "</a>";

  $import_id = intval($_POST['import_id']);

  $tmpfname = tempnam(TEMPORARY_DIRECTORY, "FOO");                              
  move_uploaded_file($_FILES['file']['tmp_name'],$tmpfname);   

	$valid = verify_file($tmpfname,$import_id);

	if ($valid)
	{	//verified so upload
		if (update_file($tmpfname,$import_id))
		{
			print "<div class='well text-primary col-md-offset-2'><p>" . T_("Successfully updated sample") . "&emsp;<h3>$description</h3></p></div>";
		}
		else
		{
			print "<div class='alert alert-danger col-md-offset-2'><p>" . T_("Error importing file. Please try again") . "</p></div>";
		}
	}
	else
		print "<div class='alert alert-danger col-md-offset-2'><p>" . T_("Error:") . " $error </p><p>" . T_("Please check imported file matches column count and names from original sample file, go back in your browser and fix the problem") . "</p></div>";


	xhtml_foot($js_foot);

}
else
{
	//need to supply file to upload
	$subtitle = T_("Select file to upload");
	xhtml_head(T_("Add to existing sample") .":",true,$css,$js_head,false,false,false,$subtitle);
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
		<label class="col-md-4 control-label" for="description"><?php  echo T_("Sample"); ?>:</label>  
    <div class="col-md-4">
      <select id="import_id" name="import_id"  class="form-inline">
        <?php
          $sql = "SELECT sample_import_id,description
                  FROM sample_import
                  WHERE enabled = 1";
          $samples = $db->GetAll($sql);

          $selected = "";

          foreach ($samples as $s) {
            if (isset($_GET['sample_import_id']) && $_GET['sample_import_id'] == $s['sample_import_id']) {
              $selected = "selected='selected'";
            }
            print "<option value=\"{$s['sample_import_id']}\" $selected>{$s['description']}</option>";
          }
        ?>
      </select>
    
		</div>
	</div>
	
	<!-- Button -->
	<div class="form-group">
		<label class="col-md-4 control-label" for="submit"></label>
		<div class="col-md-4">
			<button id="submit" type="submit" name="import_file" class="btn btn-primary"><i class="fa fa-plus-square-o fa-lg"></i>&emsp;<?php  echo T_("Add to existing sample"); ?></button>
		</div>
	</div>

	</fieldset>
	</form>

<?php 
xhtml_foot($js_foot);
}
?>
