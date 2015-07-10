<?php 
/**
 * Set information about this centre for diplay to operators
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
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2011
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 *
 */

/**
 * Configuration file
 */
include("../config.inc.php");

/**
 * Database file
 */
include ("../db.inc.php");

/**
 * XHTML functions
 */
include("../functions/functions.xhtml.php");

/**
 * CKEditor
 */
include("../include/ckeditor/ckeditor.php");

global $db;

$CKEditor = new CKEditor();
$CKEditor->basePath = "../include/ckeditor/";

if (isset($_POST['information']))
{
	set_setting("information",$_POST['information']);
}

xhtml_head(T_("Set centre information"),true,array("../include/bootstrap/css/bootstrap.min.css","../css/custom.css"),array("../js/window.js"));
?>
		<form action="" method="post" class="panel-body">
		<!-- <label for="information"><?php  //echo T_("Set centre information: "); ?></label> -->
		<?php  echo $CKEditor->editor("information",get_setting("information")); ?>
		<br/><input class="btn btn-primary" type="submit" name="update" value="<?php  echo T_("Update centre information"); ?>"/>
		</form>
<?php 
xhtml_foot();
?>
