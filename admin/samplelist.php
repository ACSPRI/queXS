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

if (isset($_POST['ren']))
{
	$sample_import_id = intval($_POST['sample_import_id']);
	
	unset($_POST['ren']);
	unset($_POST['sample_import_id']);
	
	$sql = "UPDATE sample_import
		SET description = "  . $db->qstr($_POST['description']) . "
		WHERE sample_import_id = $sample_import_id";

	$db->Execute($sql);

	$_GET['edit'] = $sample_import_id; 
}


if (isset($_POST['ed']))
{
	$sample_import_id = intval($_POST['sample_import_id']);
	
	unset($_POST['ed']);
	unset($_POST['sample_import_id']);
	
//	print_r($_POST). "</br>"; //посмотрим чего отравляется

	if (isset($_POST['del']) && isset($_POST['type']) ) { 
		$a = array(); $b = array(); $cert = array(); $a = $_POST['type']; 
		
		//echo "<p> type before unset->>"; foreach($_POST['type'] as $key => $val) { echo ' | ', $key,' => ',$val,' | '; }; print "</p>";

		foreach($_POST['del'] as $p) {
			unset ($_POST['type'][$p]);
			$deleted[] = $p;
		}
		$b = $_POST['type']; $cert = array_diff_assoc($a, $b);

		foreach($cert as $key => $val ) { 
			
			if ($val == 3 || $val==4 || $val == 5 || $val == 6 || $val == 7){
				echo "<div class='alert alert-danger'>", T_("Attention!!  It's not recommended to delete string '$key'.</br> Please BE Careful and check again strings to delete."), "</div>";
			} 
			/* else { echo "<div class='alert alert-info'>", T_("You can delete string '$key'. "), "</div>";  } */
		}
		//echo "<p>del ->>"; foreach($_POST['del'] as $key => $val) { echo ' | ', $key,' => ',$val,' | '; }; print "</p>"; 
	}	

	if (isset($_POST['type'])){ 
	
	//check that we have correct var types  and quantity
		$prph = 0; $pcd = 0; $st = 0; $fn = 0; $eml =0;
		foreach($_POST['type'] as $key => $val) {
				 if ($val == 3) $prph++;
			else if ($val == 5) $pcd++;
			else if ($val == 4) $st++;
			else if ($val == 6) $fn++;
			else if ($val == 7) $ln++;
			else if ($val == 8) $eml++;
		}
		/* if($prph == 1) {$ch1 = true;} 










		if ($ch1 && $ch2 && $ch3 && $ch4)  */$typecheck = true; //echo $ch1,$ch2,$ch3,$ch4, "typecheck=",$typecheck, "</br>" ; 
		
		if ($typecheck){
			
			foreach($_POST['type'] as $p => $val)
			{
				$sql = "UPDATE sample_import_var_restrict
						SET `type` = $val
						WHERE `var_id` = $p";
				$db->Execute($sql);
			}
			
			if (isset($_POST['del'])) {
				$db->StartTrans();
	
				foreach($_POST["del"] as $p) {
					$sql = "DELETE FROM `sample_import_var_restrict`
							WHERE var_id = $p";
					$db->Execute($sql);	
					echo "<div class='alert alert-warning col-sm-6'>", T_(" String  $p  DELETED"), "</div>";	
					
					$sql = "DELETE FROM `sample_var`
							WHERE var_id = $p
							AND sample_id IN (SELECT sample_id FROM `sample` WHERE import_id = $sample_import_id)";
					$db->Execute($sql);
					echo "<div class='alert alert-warning col-sm-6'>", T_(" Sample_Var data for  variable $p  DELETED from this sample"), "</div>";	
				}
				unset($_POST['del']);
				
				$db->CompleteTrans();

			}
		}
		else { 
			echo "<div class='alert alert-danger'>", T_("Smth wrong with selected field types. </br>Please check again var types selection and/or  fields that you supposed to delete."), "</div>";
			//exit ();
		}
	}
	
	if (isset($_POST['var'])){ 

		foreach($_POST['var'] as $p => $val)
		{//		 echo  '| ',$p,' => ',$val,'+ ';
			$v = str_replace(" ", "_", $val);

			$sql = "UPDATE sample_import_var_restrict
				SET `var` = '$v'
				WHERE sample_import_id = $sample_import_id
				AND `var_id` = $p";
			$db->Execute($sql);
		}
		
		unset($_POST['var']);
	}
	
	if (isset($_POST['see'])){

		$sql = "UPDATE sample_import_var_restrict
			SET `restrict` = 1
			WHERE sample_import_id = $sample_import_id";
		$db->Execute($sql);

		foreach($_POST['see'] as $p => $val)
		{//		 echo $p,' => ',$val,' + ';
			$sql = "UPDATE sample_import_var_restrict
				SET `restrict` = 0
				WHERE sample_import_id = $sample_import_id
				AND `var_id` = $val";
			$db->Execute($sql);	
		}
		unset($_POST['see']);
	}
	
	unset($_POST['type']); 
	
	$_GET['edit'] = $sample_import_id;
	
}

if (isset($_GET['delete_sample'])){
	
	$sample_import_id = $_GET['delete_sample'];

	// -->check questionnaire_sample - unassign sample if assigned
	$sql = "SELECT questionnaire_id FROM `questionnaire_sample` WHERE sample_import_id = $sample_import_id";
	$rs = $db->GetAll($sql);
	if (count($rs) != 0){
		echo "<div class='alert alert-danger'>", T_("Sample $sample_import_id is still assigned to questionnaire. </br>Please unassign sample prior to delete."), "</div>";
	} 
	else{
		//get the string list of sample_id's 
		$sql = "SELECT sample_id FROM `sample` where import_id = $sample_import_id";
		$rs = $db->GetAll($sql);
		for($i=0;$i<=count($rs)-1;$i++){ $samimdel[] = $rs[$i]['sample_id'] ;}
		$samimdel_s = implode(",",$samimdel);
		
		$db->StartTrans();
	 
		//del from questionnaire_sample_exclude_priority
		$sql = "DELETE FROM `questionnaire_sample_exclude_priority` WHERE sample_id IN ($samimdel_s)";
		$db->Execute($sql);
		
		//del from questionnaire_sample_quota
		$sql = "DELETE FROM `questionnaire_sample_quota` WHERE sample_import_id  = $sample_import_id";
		$db->Execute($sql);
		
		//del from questionnaire_sample_quota_row 
		$sql = "DELETE FROM `questionnaire_sample_quota_row` WHERE sample_import_id  = $sample_import_id";
		$db->Execute($sql);	 
	 
		//del from questionnaire_sample_quota_row_exclude
		$sql = "DELETE FROM `questionnaire_sample_quota_row_exclude` WHERE sample_id IN ($samimdel_s)";
		$db->Execute($sql);	
	
		//del from questionnaire_sample_timeslot 
		$sql = "DELETE FROM `questionnaire_sample_timeslot` WHERE sample_import_id  = $sample_import_id";
		$db->Execute($sql);
		
		//delete from sample_var
		$sql = "DELETE FROM `sample_var` WHERE sample_id IN ($samimdel_s)";
		$db->Execute($sql);
		
		//del from sample_import_var_restrict
		$sql = "DELETE FROM `sample_import_var_restrict` WHERE sample_import_id  = $sample_import_id";
		$db->Execute($sql);
		
		//del from sample
		$sql = "DELETE FROM `sample` WHERE import_id  = $sample_import_id";
		$db->Execute($sql);
		
		//del from sample_import
		$sql = "DELETE FROM `sample_import` WHERE sample_import_id  = $sample_import_id";
		$db->Execute($sql);
	
		$db->CompleteTrans();
		
		if (mysql_errno() == 0){echo "<div class='alert alert-warning'>", T_("Sample $sample_import_id was deleted."), "</div>";	}
		else {echo "<div class='alert alert-warning'>ERROR ".mysql_errno()." ".mysql_error()."\n</div>";}
	}
	
	unset($_GET['delete_sample']);
	$samimdel_s = '';
}


if (isset($_GET['edit']) )
{
	$subtitle=T_("Rename, Set viewing permissions & Manage sample variables") ;
	xhtml_head(T_("Sample management"),true,$css,$js_head,false,false,false,$subtitle);
	
	echo "<div class='col-sm-2'><a href='?' class='btn btn-default' ><i class='fa fa-chevron-left fa-lg' style='color:blue;'></i>&emsp;" . T_("Go back") . "</a></div>";
	
	$sample_import_id = intval($_GET['edit']); 
	
	$sql = "SELECT * FROM sample_import WHERE sample_import_id = $sample_import_id";
	$sd = $db->GetRow($sql);
	
	if($sd['enabled'] == 1) $dis = disabled; // -> disable edit and delete if sample is enabled

	$sql = "SELECT type, description
		FROM sample_var_type";
	$rd = $db->GetAll($sql);
	
	$sql = "SELECT sir.var_id, 
		CONCAT('<input type=\"text\" onInput=\"$(this).attr(\'name\',\'var[',sir.var_id,']\');\"  value=\"' ,sir.var, '\" required class=\"form-control\" style=\"min-width: 300px;\" $dis />') as var, 
		CONCAT ('<select name=\"type[',sir.var_id,']\" class=\"form-control\" $dis >
		<option value=\"' ,svt.type, '\" $selected>' ,svt.description, '</option>";
		$selected = "selected=\"selected\"";
			foreach($rd as $r)
			{	
					$sql .= "<option value=\"{$r['type']}\">" . T_($r['description']) . "</option>";
			}	
	$sql .= "</select>') as type, sv.val,
		CONCAT('<input type=\'checkbox\' ', CASE WHEN (sir.restrict IS NULL || sir.restrict = 0) THEN 'checked=\"checked\"' ELSE '' END   ,'  name=\"see[]\" value=\'',sir.var_id,'\' data-toggle=\"toggle\" data-size=\"small\" data-style=\"center-block\" data-on=" . TQ_("Yes") . " data-off=" . TQ_("No") . " data-width=\"70\"/>') as see,
		CONCAT('<input type=\'checkbox\' name=\"del[',sir.var_id,']\" value=\'',sir.var_id,'\' $dis data-toggle=\"toggle\" data-size=\"small\" data-style=\"center-block\" data-on=" . TQ_("Yes") . " data-off=" . TQ_("No") . " data-width=\"70\" data-onstyle=\'danger \'/>') as del,
		sir.restrict IS NULL as existss
		FROM sample_import as si, sample_var as sv, sample as s, sample_import_var_restrict as sir, sample_var_type as svt
		WHERE si.sample_import_id = $sample_import_id
		AND sir.sample_import_id = si.sample_import_id 
		AND sir.var_id = sv.var_id
		AND sv.sample_id = s.sample_id
		AND s.import_id = si.sample_import_id
		AND svt.type = sir.type
		GROUP BY sir.var_id";
	$rs = $db->GetAll($sql);

	print "<div class='col-sm-8'><h3>" . T_("Sample") . ":&emsp;" . $sd['description'] . "&emsp;<small>ID <b> $sample_import_id</b> </small></h3></div>";
	print "<div class='col-sm-2'><a href='samplesearch.php?sample_import_id=$sample_import_id' class='btn btn-default' ><i class='fa fa-search fa-lg fa-fw text-primary'></i>&emsp;" . T_("Search this sample") . "</a></div>";
	print "<div class='clearfix'></div>";

	if($sd['enabled'] == 0){
	
?>
	<form action="?" method="post" class="form-group " >
	<div class="form-group">
	<h4 class="col-sm-3 control-label text-right" for="description"><?php echo T_("Enter"),"&ensp;", T_("new"),"&ensp;", T_("Description"),":" ; ?></h4>
	<div class="col-sm-4"><input type='text' name='description' class="form-control" value="<?php echo $sd['description'];?>" <?php echo $dis ;?>/></div>
	</div>
	<div><input type='hidden' name='sample_import_id' value='<?php echo $sample_import_id;?>'/></div>
	<div><button type="submit" name="ren" class="btn btn-default"><i class="fa fa-edit fa-lg fa-fw text-primary"></i>&ensp;<?php echo T_("Rename");?></button></div>
	</form>
<?php
	}
if ($rs){
	
?>
	<form action="?" method="post" class="col-sm-9 form-group">
<?php
	xhtml_table($rs,array("var_id","var","see","type","val","del"),array(T_("ID"),T_("Sample variable (Column)"),T_("Visible?"),T_("Var type"),T_("Example data"),T_("Delete ?")));
?>
	<div><input type='hidden' name='sample_import_id' value='<?php echo $sample_import_id;?>'/></div>
	</br>
	<div class="col-sm-6"><button type="submit" name="ed" class="btn btn-primary pull-right"> <i class="fa fa-floppy-o fa-lg fa-fw "></i>&emsp;<?php  echo T_("Save changes");
/* if($sd['enabled'] == 0){ 	?>
	</button></div>
	<div class="col-sm-6 "><button type="submit" name="del__" class="btn btn-danger pull-right"> <i class="fa fa-trash-o fa-lg fa-fw "></i>&emsp;<?php echo T_("Delete var fields");
}	 */?>
	</button></div>
	</form>

<?php

	print "<div class='well text-danger col-sm-3'><p>" . T_("Select which fields from this sample to deidentify. </p> Deidentified fields will be permanently deleted from the sample.") . "</p></div>";
	}
else 
{
	print "<div class='alert alert-info' role='alert'><h4>" . T_("There's no data in this sample.  Probably was deidentified earlier.") .  "</h4></div>";
	
	/*check  `Time_zone_name` and `phone` values for deidentified records*/ 
	$sql = "SELECT `sample`.sample_id FROM `sample`
			LEFT JOIN `sample_var` ON (`sample`.sample_id = `sample_var`.sample_id)
			WHERE `sample_var`.sample_id IS NULL
			AND `sample`.import_id = $sample_import_id 
			AND (`sample`.Time_zone_name !='' || `sample`.phone !='')";
	$rs = $db->GetAll($sql);
	
	if (!empty($rs)) { 
		$num = count($rs);
		print "<div class='well text-danger '><p>" . T_("There're still $num records  for `Time_zone_name` and `phone` values for deidentified records") . "</p>";
 
		print "<form method='POST'><button type='submit' name='dtzph' class='btn btn-danger '> <i class='fa fa-trash-o fa-lg fa-fw '></i>&emsp;" . T_("Clean it") . "?</button></form></div>";
	}

}

	unset ($rs);

	/*check if there's sample_var data not matching sample_import_var_restrict.var_id */
	$sql = "SELECT `sv`.var_id, `sv`.var, `sv`.type  FROM `sample_var` as sv
			LEFT JOIN `sample_import_var_restrict` as sivr ON (`sivr`.var_id = `sv`.var_id)
			WHERE `sivr`.var_id IS NULL
			AND `sv`.sample_id IN (SELECT sample_id FROM `sample`  WHERE import_id = $sample_import_id)
			GROUP BY  `sv`.var_id";

	$rs = $db->GetAll($sql);
	if (!empty($rs)) {
		$count = count($rs);
		//print_r($rs); 
		print "<div class='well text-danger col-sm-3'><p>" . T_("Fix  this sample ") . "</p>";
		print "<p>" . $count . " var id's not match</p>";
 
/* 		print "<div class=' '><form method='POST'>
				<button type='submit' name='restore___' class='btn btn-default pull-left'> <i class='fa fa-reload-o fa-lg fa-fw '></i>&emsp;" . T_("Restore vars") . "</button>
				<button type='submit' name='delvarf___' class='btn btn-danger pull-right'> <i class='fa fa-trash-o fa-lg fa-fw '></i>&emsp;" . T_("Delete vars") . "</button>
				
				</form></div>"; */
		print "</div>";
		
			/* if (isset($_POST['restore___'])){
				$sql = "INSERT INTO sample_import_var_restrict
				(`sample_import_id`,`var_id`,`var`,`type`,`restrict`)
				VALUES ($sample_import_id,' ',' ',' ',1)";
				
				$db->Execute($sql);
				unset($_POST['restore___']);
			}
			if (isset($_POST['delvarf___'])){
				$sql = "";
				
				$db->Execute($sql);
				unset($_POST['delvarf___']);
			} */
			
		unset($rs);	
	}

	if (isset($_POST['dtzph'])){
			
		/*delete `Time_zone_name` and `phone` values for deidentified records*/ 
		$db->StartTrans();
		
		$sql = "UPDATE `sample` 
				LEFT JOIN `sample_var` ON (`sample`.sample_id = `sample_var`.sample_id) 
				SET `Time_zone_name`='',`phone`='' 
				WHERE `sample_var`.sample_id IS NULL
				AND `sample`.import_id = $sample_import_id";
		$db->Execute($sql);
			
		unset($_POST['dtzph']);
		
		$db->CompleteTrans();
	}	
					
	xhtml_foot($js_foot);
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
		CONCAT('<a href=\'?edit=',sample_import_id,'\' class=\'btn btn-default btn-block\' data-toggle=\'tooltip\' title=\'" . TQ_("Edit") ."&ensp;" .  TQ_("Viewing permissions") . "\'><i class=\'fa fa-eye fa-lg fa-fw \'></i></a>')
	ELSE
		CONCAT('<a href=\'?edit=',sample_import_id,'\' class=\'btn btn-default \' data-toggle=\'tooltip\' title=\'" . TQ_("Edit sample parameters") . "\'><i class=\'fa fa-eye fa-lg fa-fw text-primary\'></i> + <i class=\'fa fa-edit fa-lg fa-fw text-primary\'></i> + <i class=\'fa fa-minus-circle fa-lg fa-fw text-danger \'></i></a>')
	END as did,
	CASE WHEN enabled = 1 THEN
		CONCAT('<a href=\'\' class=\'btn btn-default disabled\'><i class=\'fa fa-trash fa-lg fa-fw\' style=\'color:grey;\'></i></a>')
	ELSE
		CONCAT('<a href=\'\' class=\'btn btn-default \' data-toggle=\'confirmation\' data-href=\'?delete_sample=',sample_import_id,'\' data-title=\'" . TQ_("ARE YOU SHURE?") . "\' data-btnOkLabel=\'" . TQ_("Yes") . "\' data-btnCancelLabel=\'" . TQ_("Cancel") . "\' ><i class=\'fa fa-trash fa-lg fa-fw text-danger \' data-toggle=\'tooltip\' title=\'" . TQ_("DELETE SAMPLE") . "\'></i></a>')
	END as delsample,
	CONCAT('<a href=\'samplesearch.php?sample_import_id=',sample_import_id,'\' class=\'btn btn-default\' data-toggle=\'tooltip\' title=\'" . TQ_("Search the sample") . "',sample_import_id,'\'><i class=\'fa fa-search fa-lg fa-fw text-primary\'></i></a>')  as ssearch,
	CONCAT('<a href=\'callhistory.php?sample_import_id=',sample_import_id,'\' class=\'btn btn-default\' data-toggle=\'tooltip\' title=\'" . TQ_("Call history"). "&ensp;\n" . TQ_("sample"). "&ensp;',sample_import_id,'\'><i class=\'fa fa-phone fa-lg text-primary\'></i></a>') as calls,
	CONCAT('<h4>',description,'&emsp;</h4>') as description,
	CONCAT('<h4 class=\'fa fa-lg text-primary pull-right\'>',(SELECT COUNT( DISTINCT`sample_var`.sample_id) FROM `sample_var`, `sample` WHERE `sample`.sample_id = `sample_var`.sample_id AND `sample`.import_id = sample_import_id ),'&emsp;</h4>') as cnt
	FROM sample_import ORDER BY sample_import_id DESC";
	
	$rs = $db->GetAll($sql); 

$subtitle=T_("Sample list");
xhtml_head(T_("Sample management"),true,$css,$js_head,false,false,false,$subtitle);
echo "<div class='form-group'>
		<a href='' onclick='history.back();return false;' class='btn btn-default'><i class='fa fa-chevron-left fa-lg text-primary'></i>&emsp;" . T_("Go back") . "</a>
		<a href='import.php' class='btn btn-default col-sm-offset-4' ><i class='fa fa-upload fa-lg'></i>&emsp;" . T_("Import a sample file") . "</a>
	</div>";
$columns = array("id","description","cnt","status","enabledisable","calls","did","ssearch","delsample"); //"vp","rname",
//$titles = array(T_("ID"),T_("Sample"), T_("Call History"),T_("Enable/Disable"), T_("Status"), T_("Deidentify"), T_("View"), T_("Rename"), T_("Search")); 
xhtml_table($rs,$columns, false, "table-hover table-condensed ");

xhtml_foot($js_foot);
?>
<script type="text/javascript">
$('[data-toggle="confirmation"]').confirmation();
</script>