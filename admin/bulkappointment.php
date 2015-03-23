<?php 
/**
 * Generate bulk appointments from a Headered CSV file
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
 * @copyright Australian Consortium for Social and Political Research (ACSPRI) 2012
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au/software queXS was writen for ACSPRI
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
 * Database functions
 */
include ("../db.inc.php");

/**
 * Operator functions
 */
include("../functions/functions.operator.php");

/**
 * Validate that an uploaded CSV file contains a caseid, starttime and endtime column and generate 
 * an array containing the details for confirming on screen or updating the database
 * 
 * @param string $tmpfname File name of uploaded CSV file
 * 
 * @return bool|array False if invalid otherwise an array of arrays containing caseid,starttime,endtime and note
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2012-11-02
 */
function validate_bulk_appointment($tmpfname)
{
	$handle = fopen($tmpfname, "r");
	$row = 1;
	$cols = array("caseid" => -1,"starttime" => -1,"endtime" => -1);
	$index = array();
	$optcols = array("note" => -1);
	
	$todo = array();
        
	while (($data = fgetcsv($handle)) !== FALSE)
        {
                //data contains an array of elements in the csv
                //selected contains an indexed array of elements to import with the type attached


                if ($row == 1) //validate
                {
			$colcount = 0;
			$ic = 0;
			foreach($data as $col)
			{
				if (array_key_exists(strtolower($col),$cols))
				{
					$cols[strtolower($col)] = $ic;	
					$colcount++;
				}
				if (array_key_exists(strtolower($col),$optcols))
				{
					$optcols[strtolower($col)] = $ic;
				}
				$ic++;
			}
			if ($colcount != 3)
			{
				return false;
			}
		}
		else
		{
			$note = "";
			if (isset($data[$optcols['note']])) 
				$note = $data[$optcols['note']];

			$sd = getdate(strtotime($data[$cols['starttime']]));
			$s = $sd['year'] . "-" . str_pad($sd['mon'],2,"0", STR_PAD_LEFT) . "-" . str_pad($sd['mday'],2,"0",STR_PAD_LEFT) . " " . str_pad($sd['hours'],2,"0",STR_PAD_LEFT) . ":" . str_pad($sd['minutes'],2,"0",STR_PAD_LEFT) . ":" . str_pad($sd['seconds'],2,"0",STR_PAD_LEFT);

			$sd = getdate(strtotime($data[$cols['endtime']]));
			$e = $sd['year'] . "-" . str_pad($sd['mon'],2,"0", STR_PAD_LEFT) . "-" . str_pad($sd['mday'],2,"0",STR_PAD_LEFT) . " " . str_pad($sd['hours'],2,"0",STR_PAD_LEFT) . ":" . str_pad($sd['minutes'],2,"0",STR_PAD_LEFT) . ":" . str_pad($sd['seconds'],2,"0",STR_PAD_LEFT);

			$todor = array($data[$cols['caseid']],$s,$e,$note);

			$todo[] = $todor;
		}

                $row++;
        }

        fclose($handle);

	return $todo;
}


if (isset($_POST['tmpfname']))
{
	$subtitle = T_("Result");
	xhtml_head(T_("Bulk appointment generator"),true,array("../include/bootstrap-3.3.2/css/bootstrap.min.css","../css/custom.css"),false,false,false,false,$subtitle);
	$todo = validate_bulk_appointment($_POST['tmpfname']);

	if (is_array($todo))
	{
		$res = array();
		foreach($todo as $r)
		{
			$db->StartTrans();

			//check the current case id exists and outcome is not final
			$sql = "SELECT c.case_id
				FROM `case` as c, `outcome` as o
				WHERE c.current_outcome_id = o.outcome_id
				AND o.outcome_type_id != 4
				AND c.case_id = {$r[0]}
				AND c.current_operator_id IS NULL";

			$caseid = $db->GetOne($sql);
	
			if (!empty($caseid))
			{
				//insert an appointment in respondent time
				$sql = "SELECT respondent_id
					FROM respondent
					WHERE case_id = {$r[0]}";

				$rid = $db->GetOne($sql);

				$sql = "SELECT contact_phone_id
					FROM contact_phone
					WHERE case_id = {$r[0]}
					ORDER BY priority ASC";
				
				$cid = $db->GetOne($sql);
			
				$oid = get_operator_id();	

			        $sql = "INSERT INTO call_attempt (call_attempt_id,case_id,operator_id,respondent_id,start,end)
			                VALUES (NULL,{$r[0]},$oid,$rid,CONVERT_TZ(NOW(),'System','UTC'),CONVERT_TZ(NOW(),'System','UTC'))";
			
			        $db->Execute($sql);
			
			        $call_attempt_id = $db->Insert_ID();
			
				$sql = "INSERT INTO appointment (case_id,contact_phone_id,`start`,`end`,respondent_id,call_attempt_id)
					SELECT {$r[0]},$cid,CONVERT_TZ('{$r[1]}',Time_zone_name,'UTC'),CONVERT_TZ('{$r[2]}',Time_zone_name,'UTC'),$rid,$call_attempt_id
					FROM respondent
					WHERE respondent_id = $rid";

				$db->Execute($sql);

				$aid = $db->Insert_ID();
	
				//change the outcome to unspecified appointment, other
				$sql = "UPDATE `case`
					SET current_outcome_id = 22
					WHERE case_id = {$r[0]}";

				$db->Execute($sql);

				//add a note if not blank
				if (!empty($r[3]))
				{	
					$note = $db->qstr($r[3]);
					$sql = "INSERT INTO case_note (case_id,operator_id,note,datetime)
						VALUES ({$r[0]},$oid,$note,CONVERT_TZ(NOW(),'System','UTC'))";
					$db->Execute($sql);
						
				}
			
				$cnote = T_("Added appointment") . " <a href='displayappointments?case_id={$r[0]}&amp;appointment_id=$aid'>$aid</a>";
			}
			else
			{
				$cnote = T_("No such case id, or case set to a final outcome, or case currently assigned to an operator");
			}
			
			$res[] = array("<a href='supervisor.php?case_id=" . $r[0] . "'>" . $r[0] . "</a>",$cnote);		
			$db->CompleteTrans();
		}
		xhtml_table($res,array(0,1),array(T_("Case id"),T_("Result")));
	}
	xhtml_foot();
}
else if (isset($_POST['import_file']))
{
	//file has been submitted
	$subtitle = T_("Check data to submit");
	xhtml_head(T_("Bulk appointment generator"),true,array("../include/bootstrap-3.3.2/css/bootstrap.min.css","../css/custom.css"),false,false,false,false,$subtitle);
	?>
	<form action="" method="post" >
	<?php 

	$tmpfname = tempnam(TEMPORARY_DIRECTORY, "FOO");

	move_uploaded_file($_FILES['file']['tmp_name'],$tmpfname);

	$todo = validate_bulk_appointment($tmpfname);
		
	if (is_array($todo) && !empty($todo)) {
			
		print "<p class='well'>" . T_("Please check the case id's, appointment start and end times and notes are correct before accepting below") . "</p>";
		$todoh = array(T_("Case id"), T_("Start time"), T_("End time"), T_("Note"));
		xhtml_table($todo,array(0,1,2,3),$todoh);
		?>
		<form action="" method="post">
			<input type="hidden" name="tmpfname" value="<?php  echo $tmpfname; ?>" />
			<input type="submit" name="import_file" value="<?php  echo T_("Accept and generate bulk appointments"); ?>" class="btn btn-primary"/>
		</form>
		<?php 
	}
	else
		print "<p class='well text-danger'>" . T_("The file does not contain at least caseid, starttime and endtime columns. Please try again.") ."</p>";
	
	print "</form>";
	
	
	xhtml_foot();

}
else
{
	//need to supply file to upload
	$subtitle = T_("Import: Select file to upload");
	xhtml_head(T_("Bulk appointment generator"),true,array("../include/bootstrap-3.3.2/css/bootstrap.min.css","../include/font-awesome-4.3.0/css/font-awesome.css","../css/custom.css"),array("../js/jquery-2.1.3.min.js","../js/bootstrap-filestyle.min.js"),false,false,false,$subtitle );
	
	$ua = $_SERVER['HTTP_USER_AGENT'];
	if (preg_match('/Firefox/i', $ua)) $csv= "text/csv"; else $csv= ".csv";
	
	?>
	
	<p class="well"><?php echo T_("Provide a headered CSV file containing at least 3 columns - caseid, starttime and endtime. </br> Optionally you can include a note column to attach a note to the case in addition to setting an appointment. </br>Only cases that have temporary (non final) outcomes will have appointments generated, and the outcome of the case will be updated to an appointment outcome."); ?><p>
	
	<div class="panel-body">
		<h5><u><?php echo T_("Example CSV file:"); ?></u></h5>
		<table class="table-bordered table-condensed form-group">
			<tr><th>caseid</th><th>starttime</th><th>endtime</th><th>note</th></tr>
			<tr><td>1</td><td>2012-08-15 11:00:00</td><td>2012-08-15 13:00:00</td><td>Appointment automatically generated</td></tr>
			<tr><td>2</td><td>2012-08-15 12:00:00</td><td>2012-08-15 14:00:00</td><td>Appointment automatically generated</td></tr>
			<tr><td>3</td><td>2012-08-15 13:00:00</td><td>2012-08-15 15:00:00</td><td>Appointment automatically generated</td></tr>
		</table>
	</div>
	
	<form enctype="multipart/form-data" action="" method="post">
	<input type="hidden" name="MAX_FILE_SIZE" value="1000000000" />
	
	<h4 class="pull-left" ><?php  echo T_("Select bulk appointment CSV file to upload"); ?>:&ensp;</h4>
	
	<div class="col-sm-4">
		<input name="file" class="filestyle" type="file" required data-buttonBefore="true" data-iconName="fa fa-folder-open fa-lg text-primary " data-buttonText="<?php  echo T_("Select file"); ?>" type="file" accept="<?php echo $csv; ?>"/>&emsp;
	</div>
	<button type="submit" class="btn btn-primary" name="import_file" value=""><i class='fa fa-upload fa-lg'></i>&emsp;<?php  echo "" . T_("Upload file"); ?></button>
	
	</form>

	<?php 
	xhtml_foot();
}

?>