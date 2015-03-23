<?php 
/**
 * List operators and allow for customised VoIP downloads, changing passwords, disabling, etc
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2013
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/software queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

/**
 * Database
 */
include_once(dirname(__FILE__).'/../db.inc.php');

/**
 * XHTML functions
 */
include_once(dirname(__FILE__).'/../functions/functions.xhtml.php');

$css = array(
"../include/bootstrap-3.3.2/css/bootstrap.min.css", 
"../include/bootstrap-3.3.2/css/bootstrap-theme.min.css",
"../include/font-awesome-4.3.0/css/font-awesome.css",
"../include/bootstrap-toggle/css/bootstrap-toggle.min.css",
"../css/custom.css"
			);
$js_head = array(
"../js/jquery-2.1.3.min.js",
"../include/bootstrap-3.3.2/js/bootstrap.min.js",
"../include/bootstrap-toggle/js/bootstrap-toggle.min.js",
				);
$js_foot = array(
"../js/bootstrap-confirmation.js",
"../js/custom.js"
				);

if (isset($_POST['submitr']))
{
	$sample_import_id = intval($_POST['sample_import_id']);
	
	$sql = "UPDATE sample_import
		SET description = "  . $db->qstr($_POST['description']) . "
		WHERE sample_import_id = $sample_import_id";

	$db->Execute($sql);

	$_GET['rename'] = $sample_import_id; 
}

if (isset($_POST['submit']))
{
	$sample_import_id = intval($_POST['sample_import_id']);
	
	unset($_POST['submit']);
	unset($_POST['sample_import_id']);
	
	foreach($_POST as $p)
	{
		$sql = "DELETE FROM sample_var
			WHERE var LIKE " . $db->qstr($p) . "
			AND sample_id IN 
				(SELECT sample_id
				FROM sample
				WHERE import_id = $sample_import_id)";
		$db->Execute($sql);	

	}	
	
	$_GET['edit'] = $sample_import_id;
}

if (isset($_POST['submitvp']))
{
	$sample_import_id = intval($_POST['sample_import_id']);
	
	unset($_POST['submitvp']);
	unset($_POST['sample_import_id']);
	$db->StartTrans();
	
	$sql = "UPDATE sample_import_var_restrict
		SET `restrict` = 1
		WHERE sample_import_id = $sample_import_id";
	$db->Execute($sql);

	foreach($_POST as $p => $val)
	{
		$sql = "UPDATE sample_import_var_restrict
			SET `restrict` = 0
			WHERE sample_import_id = $sample_import_id
			AND `var` LIKE " . $db->qstr($p);
		$db->Execute($sql);	
	}	
	
	$db->CompleteTrans();

	$_GET['view'] = $sample_import_id;
}

if (isset($_GET['rename']))
{
	$subtitle=T_("Rename sample");
	xhtml_head(T_("Sample management"),true,$css,$js_head,false,false,false,$subtitle);
	
	echo "<a href='?' class='btn btn-default' ><i class='fa fa-chevron-left fa-lg' style='color:blue;'></i>&emsp;" . T_("Go back") . "</a>";
	$sample_import_id = intval($_GET['rename']);

	$sql = "SELECT description
		FROM sample_import
		WHERE sample_import_id = $sample_import_id";
	$rs = $db->GetOne($sql);

	print "<h3>" . T_("Sample current description") . ":&ensp;<span class='text-primary'>" . $rs . "</span></h3>";
?>
	<form action="?" method="post" class="form-group " >
	<div class="form-group">
	<h4 class="col-sm-3 control-label text-right" for="description"><?php echo T_("Enter"),"&ensp;", T_("new"),"&ensp;", T_("Description"), ":" ; ?></h4>
	<div class="col-sm-4"><input type='text' name='description' class="form-control" value="<?php echo $rs;?>"/></div>
	</div>
	<div><input type='hidden' name='sample_import_id' value='<?php echo $sample_import_id;?>'/></div>
	<div><button type="submit" name="submitr" class="btn btn-default"><i class="fa fa-edit fa-lg fa-fw text-primary"></i>&emsp;<?php echo T_("Rename");?></button></div>
	</form>
<?php
	xhtml_foot();
	exit();
}

if (isset($_GET['view']))
{
	$subtitle=T_("Operator viewing permissions");
	xhtml_head(T_("Sample management"),true,$css,$js_head,false,false,false,$subtitle);

	echo "<a href='?' class='btn btn-default' ><i class='fa fa-chevron-left fa-lg' style='color:blue;'></i>&emsp;" . T_("Go back") . "</a>";
	
	$sample_import_id = intval($_GET['view']);

	$sql = "SELECT sample_id
		FROM `sample`
		WHERE import_id = $sample_import_id";
	
	$sample_id = $db->GetOne($sql);

	$sql = "SELECT si.description, sv.val, sv.var,
		CONCAT('<input type=\'checkbox\' ', CASE WHEN (sir.restrict IS NULL || sir.restrict = 0) THEN 'checked=\"checked\"' ELSE '' END   ,' name=\'',sv.var,'\' value=\'11\' data-toggle=\"toggle\" data-size=\"small\" data-style=\"center-block\" data-on=" . TQ_("Yes") . " data-off=" . TQ_("No") . " data-width=\"70\"/>') as box,
		sir.restrict IS NULL as existss
		FROM sample_import as si
		JOIN `sample` as s ON (s.import_id = si.sample_import_id AND s.sample_id = $sample_id)
		JOIN sample_var as sv ON (sv.sample_id = s.sample_id)
		LEFT JOIN sample_import_var_restrict as sir ON (sir.sample_import_id = si.sample_import_id AND sir.var = sv.var)
		WHERE si.sample_import_id = $sample_import_id";

	$rs = $db->GetAll($sql);

	//if not in restrict table, then insert
	foreach($rs as $r)
	{
		if ($r['existss'] == 1)
		{
			$sql = "INSERT INTO sample_import_var_restrict (sample_import_id,var,`restrict`)
				VALUES ($sample_import_id,'{$r['var']}',0)";
			$db->Execute($sql);
		}
	}

	print "<h3>" . T_("Operator viewing permissions") . "&ensp;".  T_("for") . "&ensp;" . T_("sample") . ": " . $rs[0]['description'] . "</h3>";
	if (!$rs) print "<div class='alert alert-info  col-sm-6' role='alert'><h4>" . T_("There's no data in this sample. ") .  "</h4></div>";
	else {
	print "<div class='alert alert-info' role='alert'><p>" . T_("Select which fields from this sample should be able to be viewed by operators") . "</p></div>
			<form action='?' method='post' class='form-group form-horisontal'>";
	xhtml_table($rs,array("var","val","box"),array(T_("Field"),T_("Example data"),T_("Allow operator to see?")));
?>
	<div><input type='hidden' name='sample_import_id' value='<?php echo $sample_import_id;?>'/></div></br>
	<div class="col-md-offset-4"><button type="submit" name="submitvp" class="btn btn-default"> <i class="fa fa-eye fa-lg fa-fw text-primary"></i>&emsp;<?php  echo T_("Save changes");?></button></div>
	</form>

<?php
}
	xhtml_foot();
	exit();
}

if (isset($_GET['edit']))
{
	$subtitle=T_("Delete sample variables") ;
	xhtml_head(T_("Sample management"),true,$css,$js_head,false,false,false,$subtitle);
	
	echo "<a href='?' class='btn btn-default' ><i class='fa fa-chevron-left fa-lg' style='color:blue;'></i>&emsp;" . T_("Go back") . "</a>";

	$sample_import_id = intval($_GET['edit']);

	$sql = "SELECT si.description, sv.val, sv.var,
		CONCAT('<input type=\'checkbox\' name=\'',sv.var,'\' value=\'',sv.var,'\' data-toggle=\"toggle\" data-size=\"small\" data-style=\"center-block\" data-on=" . TQ_("Yes") . " data-off=" . TQ_("No") . " data-width=\"70\" data-onstyle=\'danger\'/>') as box
		FROM sample_import as si, sample_var as sv, sample as s
		WHERE si.sample_import_id = $sample_import_id
		AND sv.sample_id = s.sample_id
		AND s.import_id = si.sample_import_id
		GROUP BY sv.var";
	$rs = $db->GetAll($sql);

	print "<h3>" . T_("Sample") . ": " . $rs[0]['description'] . "</h3>";

if ($rs){
	print "<div class='alert alert-danger' role='alert'><p>" . T_("Select which fields from this sample to deidentify. Deidentified fields will be permanently deleted from the sample.") . "</p></div>";
?>
	<form action="?" method="post">
<?php
	xhtml_table($rs,array("var","val","box"),array(T_("Field"),T_("Example data"),T_("Delete")));
?>
	<div><input type='hidden' name='sample_import_id' value='<?php echo $sample_import_id;?>'/></div>
	</br>
	<div class="col-md-offset-3"><button type="submit" name="submit" class="btn btn-danger"> <i class="fa fa-trash-o fa-lg fa-fw "></i>&emsp;<?php echo T_("Delete selected fields");?></button></div>
	</form>

<?php	
	}
else 
{
	print "<div class='alert alert-info col-sm-6' role='alert'><h4>" . T_("There's no data in this sample.  Probably was deidentified earlier.") .  "</h4></div>";
}
	xhtml_foot();
	exit();
}
 
if (isset($_GET['sampledisable']))
{
	$id = intval($_GET['sampledisable']);

	$sql = "UPDATE sample_import
		SET enabled = 0
		WHERE sample_import_id = '$id'";
	$db->Execute($sql);	
}

if (isset($_GET['sampleenable']))
{
	$id = intval($_GET['sampleenable']);

	$sql = "UPDATE sample_import
		SET enabled = 1
		WHERE sample_import_id = '$id'";
	$db->Execute($sql);	
}

$sql = "SELECT
	CONCAT('&ensp;<b class=\'badge\'>',sample_import_id,'</b>&ensp;') as id,
	CASE WHEN enabled = 0 THEN
		CONCAT('&emsp; <span class=\'btn label label-default\'>" . TQ_("Disabled") . "</span>&emsp;') 
	ELSE
		CONCAT('&emsp; <span class=\'btn label label-primary\'>" . TQ_("Enabled") . "</span>&emsp;') 
	END as status,
	CASE WHEN enabled = 0 THEN
		CONCAT('<a href=\'?sampleenable=',sample_import_id,'\' class=\'btn btn-default col-sm-12\'>" . TQ_("Enable") . "&emsp;<i class=\'fa fa-play fa-lg\' style=\'color:blue;\'></i></a>') 
	ELSE
		CONCAT('<a href=\'\' class=\'btn btn-default col-sm-12\' data-toggle=\'confirmation\' data-href=\'?sampledisable=',sample_import_id,'\' data-title=\'" . TQ_("ARE YOU SHURE?") . "\' data-btnOkLabel=\'" . TQ_("Yes") . "\' data-btnCancelLabel=\'" . TQ_("Cancel") . "\'><i class=\'fa fa-ban fa-lg\' style=\'color:red;\'></i>&ensp;&nbsp;" . TQ_("Disable") . "</a> ') 
	END
	as enabledisable,
	CASE WHEN enabled = 1 THEN
		CONCAT('<a href=\'?edit=',sample_import_id,'\' class=\'btn btn-default disabled\'><i class=\'fa fa-minus-circle fa-lg fa-fw\' style=\'color:grey;\'></i></a>')
	ELSE
		CONCAT('<a href=\'?edit=',sample_import_id,'\' class=\'btn btn-default \' data-toggle=\'tooltip\' title=\'" . TQ_("Deidentify") . "\'><i class=\'fa fa-minus-circle fa-lg fa-fw text-danger \'></i></a>')
	END as did,
	CONCAT('<a href=\'?view=',sample_import_id,'\' class=\'btn btn-default\' data-toggle=\'tooltip\' title=\'" . TQ_("Viewing permissions") . "\'><i class=\'fa fa-eye fa-lg fa-fw text-primary\'></i></a>') as vp,
	CONCAT('<a href=\'?rename=',sample_import_id,'\' class=\'btn btn-default\' data-toggle=\'tooltip\' title=\'" . TQ_("Rename") . "\'><i class=\'fa fa-edit fa-lg fa-fw text-primary\'></i></a>') as rname,
	CONCAT('<a href=\'samplesearch.php?sample_import_id=',sample_import_id,'\' class=\'btn btn-default\' data-toggle=\'tooltip\' title=\'" . TQ_("Search the sample") . "',sample_import_id,'\'><i class=\'fa fa-search fa-lg fa-fw text-primary\'></i></a>')  as ssearch,
	CONCAT('<a href=\'callhistory.php?sample_import_id=',sample_import_id,'\' class=\'btn btn-default\' data-toggle=\'tooltip\' title=\'" . TQ_("Call history"). "&ensp;\n" . TQ_("sample"). "&ensp;',sample_import_id,'\'><i class=\'fa fa-phone fa-lg text-primary\'></i></a>') as calls,
	CONCAT('<h4>',description,'&emsp;</h4>') as description
	FROM sample_import";
$rs = $db->GetAll($sql);

$subtitle=T_("Sample list");
xhtml_head(T_("Sample management"),true,$css,$js_head,false,false,false,$subtitle);
echo "<div class='form-group'>
		<a href='' onclick='history.back();return false;' class='btn btn-default'><i class='fa fa-chevron-left fa-lg text-primary'></i>&emsp;" . T_("Go back") . "</a>
		<a href='import.php' class='btn btn-default col-sm-offset-4' ><i class='fa fa-upload fa-lg'></i>&emsp;" . T_("Import a sample file") . "</a>
	</div>";
$columns = array("id","description","status","enabledisable","calls","did","vp","rname", "ssearch");
//$titles = array(T_("ID"),T_("Sample"), T_("Call History"),T_("Enable/Disable"), T_("Status"), T_("Deidentify"), T_("View"), T_("Rename"), T_("Search")); 
xhtml_table($rs,$columns, false, "table-hover table-condensed ");

xhtml_foot($js_foot);
?>
<script type="text/javascript">
$('[data-toggle="confirmation"]').confirmation()
</script>