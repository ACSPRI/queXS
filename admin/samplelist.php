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


if (isset($_GET['edit']))
{
	xhtml_head(T_("Deidentify"),true,array("../css/table.css"));

	$sample_import_id = intval($_GET['edit']);

	$sql = "SELECT si.description, sv.val, sv.var,
		CONCAT('<input type=\'checkbox\' name=\'',sv.var,'\' value=\'',sv.var,'\'/>') as box	
		FROM sample_import as si, sample_var as sv, sample as s
		WHERE si.sample_import_id = $sample_import_id
		AND sv.sample_id = s.sample_id
		AND s.import_id = si.sample_import_id
		GROUP BY sv.var";

	$rs = $db->GetAll($sql);

	print "<h2>" . T_("Deidentify") . ": " . $rs[0]['description'] . "</h2>";
	echo "<p><a href='?'>" . T_("Go back") . "</a></p>";

	print "<p>" . T_("Select which fields from this sample to deidentify. Deidentified fields will be permanently deleted from the sample.") . "</p>";

	?>
	<form action="?" method="post">
	<?php
	xhtml_table($rs,array("var","val","box"),array(T_("Field"),T_("Example data"),T_("Deidentify")));
	?>
	<div><input type='hidden' name='sample_import_id' value='<?php echo $sample_import_id;?>'/></div>
	<div><input type="submit" name="submit" value="<?php echo T_("Delete selected fields");?>"/></div>
	</form>
	<?php	

	
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
		CASE WHEN enabled = 0 THEN
			CONCAT('<a href=\'?sampleenable=',sample_import_id,'\'>" . T_("Enable") . "</a>') 
		ELSE
			CONCAT('<a href=\'?sampledisable=',sample_import_id,'\'>" . T_("Disable") . "</a>') 
		END
		as enabledisable,
		CONCAT('<a href=\'?edit=',sample_import_id,'\'>" . T_("Deidentify") . "</a>')  as did,
		description
	FROM sample_import";

$rs = $db->GetAll($sql);

xhtml_head(T_("Sample list"),true,array("../css/table.css"));

$columns = array("description","enabledisable","did");
$titles = array(T_("Sample"),T_("Enable/Disable"),T_("Deidentify"));

xhtml_table($rs,$columns,$titles);

xhtml_foot();
?>
