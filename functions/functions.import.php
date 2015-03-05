<?php 
/**
 * Functions relating to importing a sample file (from CSV)
 *
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
 * @subpackage functions
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */


/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

/**
 * Database file
 */
include_once(dirname(__FILE__).'/../db.inc.php');

/**
 * Return only numbers from a string
 *
 * @param string $str String containing any character
 * @return int A number
 * 
 */
function only_numbers($str)
{
	return preg_replace("/[^0-9]/", "",$str);
}


/**
 * Verify fields in a CSV file
 *  confirm that there are no duplicate names
 *  confirm that there is one and only one primary number selected
 *
 * @param array $fields an array of field information i_ if selected, n_ name, t_ type code
 * @return string An empty string if valid, otherwise invalid with error message
 * 
 */
function verify_fields($fields)
{
	//i_ is if selected
	//n_ is name of field
	//t_ is type of field

	$selected = array();

	foreach($fields as $key => $val)
	{
		if (strncmp($key, "i_", 2) == 0)
		{
			$selected[] = substr($key,2);
		}
	}

	$names = array();

	//check for duplicate names
	foreach($selected as $val)
	{
		if (array_key_exists($fields["n_$val"], $names))
		{
			return T_("Duplicate name");
		}
		else
		{
			//set name to type
			$names[$fields["n_$val"]] = $fields["t_$val"];
		}
	}

	//check that there is one and one only primary phone selected
	$count = 0;
	foreach($names as $val)
	{
		if ($val == 3) $count++;
	}

	if ($count == 1)
	{
		return "";
	}
	else
	{
		return T_("You must select one and one only Primary Phone number");
	}

}

/**
 * Display an XHTML table of the CSV data header
 *
 * @param array $data Header data from a CSV file
 *
 * @see get_first_row()
 *
 */
function display_table($data)
{
	print "<table class='table-hover table-bordered table-condensed tclass'><thead class='highlight'>";
	print "<tr><th>" . T_("Selected file column name") . "</th><th>" . T_("Import ?") . "</th><th class='col-sm-4'>" . T_("New Sample Variable Name") . "</th><th>" . T_("Variable Type") . "</th><th>" . T_("Show to operator?") . "</th></tr></thead><tbody>";
	$row = 1;

	global $db;

	$sql = "SELECT description,type
		FROM sample_var_type";

	$rs = $db->GetAll($sql);

	foreach ($data as $key => $value)
	{
		$val = str_replace(" ", "_", $value);
		$checked = "checked";
		if (empty($val)) $val = "samp_$row";

		print "<tr><td>$value</td>
					<td><input type=\"checkbox\" name=\"i_$row\" checked=\"$checked\" switch=\"yes\" data-size=\"small\" data-on-text=\"" . TQ_("Yes") . "\" data-off-text=" . TQ_("No") . " /></td>
					<td><input type=\"text\" value=\"$val\" name=\"n_$row\" class=\"form-control\" /></td>
					<td>";
					print "<select name=\"t_$row\" class=\"form-control\">";
					print "<option value=\"\" $selected></option>";
					$selected = "selected=\"selected\"";
					foreach($rs as $r)
					{
						print "<option value=\"{$r['type']}\" >" . T_($r['description']) . "</option>";
						$selected = "";
					}
					print "</select></td>";
			print "<td>&emsp;<input type=\"checkbox\" name=\"a_$row\" switch=\"yes\" data-size=\"small\" data-on-text=\"" . TQ_("Yes") . "\" data-off-text=" . TQ_("No") . " /></td>";
		print "</tr>";
		$row++;
	}	
	print "</tbody></table>";

}


/**
 * Return the first row of a CSV file as an array
 *
 * @param string $file File name to open
 *
 */
function get_first_row($file)
{
	$handle = fopen($file, "r");
	$data = fgetcsv($handle);
	fclose($handle);
	return $data;
}


/**
 * Import a CSV file to the sample database
 *
 * @param string $file File name to open
 * @param string $description A description of the sample
 * @param array $fields Which fields to import and what type they are assigned to
 *
 * @see verify_fields()
 * @see display_table()
 *
 */
function import_file($file, $description, $fields, $firstrow = 2)
{

	$row = 1;
	$handle = fopen($file, "r");

	//import into database

	global $db;

	$db->StartTrans();

	$sql = "INSERT INTO sample_import
		(sample_import_id, description)
		VALUES (NULL, '$description')";

	//print("$sql<br/>");
	//if ($db->HasFailedTrans()) { print "FAILED"; exit(); }

	$rs = $db->Execute($sql);
	$id = $db->Insert_ID();

	$selected_type = array();
	$selected_name = array();

	foreach($fields as $key => $val)
	{
		if (strncmp($key, "i_", 2) == 0)
		{
			$selected_type[substr($key,2)] = $fields["t_" . substr($key,2)];
			$selected_name[substr($key,2)] = $fields["n_" . substr($key,2)];

			$restrict = 1;

			//Set restrictions on columns
			if (isset($fields["a_" . substr($key,2)]))
			{
				$restrict = 0;
			}
			
			$sql = "INSERT INTO sample_import_var_restrict
				(`sample_import_id`,`var`,`restrict`)
				VALUES ($id,'" . $fields["n_" . substr($key,2)] . "',$restrict)";

			$db->Execute($sql);			
		}
	}

	/**
	 * create an ordered index of columns that contain data for obtaining the timezone
	 * type of 5,4,3,2 preferred
	 */
	
	arsort($selected_type);

	$imported = 0;

	while (($data = fgetcsv($handle)) !== FALSE) 
	{
		//data contains an array of elements in the csv
		//selected contains an indexed array of elements to import with the type attached

		if ($row >= $firstrow) //don't import the header row
		{
			//determine if there is a phone number - if not - do not import
			$numberavail = 0;
			foreach($selected_type as $key => $val)
			{
				if ($val == 2 || $val == 3)
				{
					$dkey = only_numbers($data[$key - 1]);			
					if (!empty($dkey))
					{
						$data[$key - 1] = $dkey;
						$numberavail = 1;
					}
				}
			}
	
			if ($numberavail == 1)
			{
				//insert into sample field
		
				//first find the timezone
		
				$tzone = get_setting("DEFAULT_TIME_ZONE"); //set this to default
		
				/**
				 * Determine time zone from all possible sources in sample_var_type table
				 *
				 */
				foreach($selected_type as $key => $val)
				{	
					$tz = get_time_zone($data[$key - 1],$val);
	
					if ($tz !== false)
					{
						$tzone = $tz;
						break;
					}
				}
		
		
				/**
				 * insert using primary phone number (3)
				 */
				$ppid = array_search('3', $selected_type);
		
				$dppid = only_numbers($data[$ppid - 1]);
		
				$sql = "INSERT INTO sample (sample_id,import_id,Time_zone_name,phone)
					VALUES (NULL,'$id','$tzone','$dppid')";
		
				$db->Execute($sql);
				$sid = $db->Insert_Id();
			
		
				/**
				 * insert into sample_var field
				 */
				foreach($selected_name as $key => $val)
				{
					$dkey = $db->Quote($data[$key - 1]);			
		
					$sql = "INSERT INTO sample_var (sample_id,var,val,type)
						VALUES ('$sid','$val',{$dkey},'{$selected_type[$key]}')";
		
					$db->Execute($sql);
				
				}

				$imported++;
			}
		}

		$row++;
	}

	fclose($handle);

	//cleanup
	unlink($file);

	return $db->CompleteTrans();
	
}

/**
 * Get the timezone given the sample value and type
 *
 * @param string $value A sample value
 * @param integer $type The type of sample var (see sample_var_type table)
 *
 * @return string|bool Return the timezone name or false if not found
 */
function get_time_zone($value,$type)
{
	global $db;
	
	$sql = "SELECT `table`
		FROM sample_var_type
		WHERE type = '$type'";

	$tname = $db->GetOne($sql);
	
	if (!empty($tname))
	{
		$value = $db->Quote($value);

		$sql = "SELECT Time_zone_name as tz
			FROM `$tname`
			WHERE val = SUBSTR($value, 1, CHAR_LENGTH( val ) )
			ORDER BY CHAR_LENGTH(val) DESC";

		$tz = $db->GetOne($sql);

		if (!empty($tz))
		{
			return $tz;
		}
	}
	return false;
}


?>
