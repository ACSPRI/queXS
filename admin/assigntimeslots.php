<?php 
/**
 * Assign availability groups to a questionnaire
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
 * @copyright Australian Consortium for Social and Political Research Inc (ACSPRI) 2011
 * @package queXS
 * @subpackage admin
 * @link http://www.acspri.org.au queXS was writen for ACSPRI
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
 * Display functions
 */
include("../functions/functions.display.php");

/**
 * Input functions
 */
include("../functions/functions.input.php");

$css = array(
"../include/bootstrap-3.3.2/css/bootstrap.min.css", 
"../include/bootstrap-3.3.2/css/bootstrap-theme.min.css",
//"../include/font-awesome-4.3.0/css/font-awesome.css",
"../css/custom.css"
			);
$js_head = array(
				);
$js_foot = array(
"../js/window.js",
//"../js/custom.js"
				);

global $db;

//block availability
if (isset($_GET['questionnaire_id']) && isset($_GET['av_availability_group'])) 
{
	//need to add availability_group to questionnaire
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$availability_group = bigintval($_GET['av_availability_group']);

	$sql = "INSERT INTO questionnaire_availability(questionnaire_id,availability_group_id)
		VALUES('$questionnaire_id','$availability_group')";
	$db->Execute($sql);
}

if (isset($_GET['questionnaire_id']) && isset($_GET['av_ravailability_group']))
{
	//need to remove rsid from questionnaire
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$availability_group = bigintval($_GET['av_ravailability_group']);

	$sql = "DELETE FROM questionnaire_availability
		WHERE questionnaire_id = '$questionnaire_id'
		AND availability_group_id = '$availability_group'";
	$db->Execute($sql);
}

//block call_attempts
if (isset($_GET['questionnaire_id']) && isset($_GET['ca_availability_group']))
{
	//need to add availability_group to questionnaire
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$availability_group = bigintval($_GET['ca_availability_group']);

	$sql = "INSERT INTO questionnaire_timeslot(questionnaire_id,availability_group_id)
		VALUES('$questionnaire_id','$availability_group')";
	$db->Execute($sql);
}

if (isset($_GET['questionnaire_id']) && isset($_GET['ca_ravailability_group']))
{
	//need to remove rsid from questionnaire
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$availability_group = bigintval($_GET['ca_ravailability_group']);

	$sql = "DELETE FROM questionnaire_timeslot
		WHERE questionnaire_id = '$questionnaire_id'
		AND availability_group_id = '$availability_group'";
	$db->Execute($sql);
}


//block call_attempts by sample
if (isset($_GET['questionnaire_id']) && isset($_GET['sample_import_id']) && isset($_GET['qs_availability_group']))
{
	//need to add availability_group to questionnaire
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sample_import_id = bigintval($_GET['sample_import_id']);
	$availability_group = bigintval($_GET['qs_availability_group']);

	$sql = "INSERT INTO questionnaire_sample_timeslot (questionnaire_id,sample_import_id,availability_group_id)
		VALUES('$questionnaire_id','$sample_import_id','$availability_group')";
	$db->Execute($sql);
}

if (isset($_GET['questionnaire_id']) && isset($_GET['sample_import_id']) && isset($_GET['qs_ravailability_group']))
{
	//need to remove rsid from questionnaire
	$questionnaire_id = bigintval($_GET['questionnaire_id']);
	$sample_import_id = bigintval($_GET['sample_import_id']);
	$availability_group = bigintval($_GET['qs_ravailability_group']);

	$sql = "DELETE FROM questionnaire_sample_timeslot
		WHERE questionnaire_id = '$questionnaire_id'
    AND sample_import_id = '$sample_import_id'
    AND availability_group_id = '$availability_group'";
	$db->Execute($sql);
}

$questionnaire_id = false;
if (isset($_GET['questionnaire_id'])) 	$questionnaire_id = bigintval($_GET['questionnaire_id']);
$sample_import_id = false;
if (isset($_GET['sample_import_id'])) 	$sample_import_id = bigintval($_GET['sample_import_id']);

xhtml_head(T_("Assign Time slots"),true,$css,$js_head);//false,array("../js/window.js")

print "<div class='clearfix form-group'><h3 class='col-sm-6 text-right text-uppercase'>" . T_("Select a questionnaire") . ":</h3>";
display_questionnaire_chooser($questionnaire_id,false, "form-inline", "form-control"); 
print "</div>";
if ($questionnaire_id != false)
{
	
//page questionnaireavailability.php 
print "<div class=col-sm-4><h2>" . T_("Time slot groups") . "</h2>";
print "<div class='well'>" . T_("Assigning an availability group to a questionnaire will allow interviewers to select from those groups to restrict calls to a particular case to the times within the group") ."</div>";

	$sql = "SELECT q.availability_group_id,a.description as description, CONCAT('<a href=\'?questionnaire_id=$questionnaire_id&amp;av_ravailability_group=', a.availability_group_id,'\'  >" . T_("Click to unassign") . "</a>') as link
		FROM questionnaire_availability as q, availability_group as a
		WHERE q.availability_group_id = a.availability_group_id
		AND q.questionnaire_id = '$questionnaire_id'";

	$qs = $db->GetAll($sql);
	print "</br></br></br><div class='panel-body'>";
	
	if (empty($qs))
	{
		print "<h4 class='alert text-danger'>" . T_("There are no time slots groups selected for this questionnaire") . "</h4>";
	}
	else
	{
		print "<h4>" . T_("Time slots groups selected for this questionnaire") . "</h4>";
		xhtml_table ($qs,array("availability_group_id","description","link"),false,"table table-hover");
	}
	print "</div>";
	$sql = "SELECT si.availability_group_id,si.description
		FROM availability_group as si
		LEFT JOIN questionnaire_availability as q ON (q.questionnaire_id = '$questionnaire_id' AND q.availability_group_id = si.availability_group_id)
		WHERE q.questionnaire_id is NULL";
	
	$qs = $db->GetAll($sql);

	if (!empty($qs))
	{
		print "<div class='panel-body'>"; 
		//print "<h3>" . T_("Add time slot to this questionnaire:") . "</h3>";
		print "<form action='' method='get'><div class='pull-left'><select class='form-control ' name='av_availability_group' id='av_availability_group'>";
		foreach($qs as $q)
		{
			print "<option value=\"{$q['availability_group_id']}\">{$q['description']}</option>";
		}
		print "</select></div>
				<input type='hidden' name='questionnaire_id' value='$questionnaire_id'/>
				&ensp;<input type='submit' class='btn btn-default' name='add_av_availability' value='" . T_("Add time slot group") . "'/>
				</form></div>";
	}

print "</div>";



//page questionnairecatimeslots.php 
print "<div class=col-sm-4><h2>" . T_("Call attempt time slots") . "</h2>";
print "<div class='well'>" . T_("Assigning call attempt time slots to questionnaires will only allow cases to be attempted in a time slot for the n + 1th time where it has been attempted at least n times in all assigned timeslots. Please note timeslots must cover all possible time periods otherwise no cases will be available during missing timeslots.") ."</div>";

	$sql = "SELECT q.availability_group_id,a.description as description, CONCAT('<a href=\'?questionnaire_id=$questionnaire_id&amp;ca_ravailability_group=', q.availability_group_id,'\'  >" . T_("Click to unassign") . "</a>') as link
	
		FROM questionnaire_timeslot as q, availability_group as a
		WHERE q.availability_group_id = a.availability_group_id
		AND q.questionnaire_id = '$questionnaire_id'";

	$qs = $db->GetAll($sql);
	print "</br><div class='panel-body'>";
	if (empty($qs))
	{
		print "<h4 class='alert text-danger'>" . T_("There are no call attempt time slots selected for this questionnaire") . "</h4>";
	}
	else
	{
		print "<h4>" . T_("Call attempt time slots selected for this questionnaire") . "</h4>";
		xhtml_table ($qs,array("availability_group_id","description","link"),false,"table table-hover");
	}
	print "</div>";
	
	$sql = "SELECT si.availability_group_id,si.description
		FROM availability_group as si
		LEFT JOIN questionnaire_timeslot as q ON (q.questionnaire_id = '$questionnaire_id' AND q.availability_group_id = si.availability_group_id)
		WHERE q.questionnaire_id is NULL";
	
	$qs = $db->GetAll($sql);

	if (!empty($qs))
	{
		print "<div class='panel-body'>"; 
		//print "<h3>" . T_("Add a call attempt time slot to this questionnaire:") . "</h3>";
		print "<form action='' method='get'><div class='pull-left'><select class='form-control ' name='ca_availability_group' id='ca_availability_group'>";
		foreach($qs as $q)
		{
			print "<option value=\"{$q['availability_group_id']}\">{$q['description']}</option>";
		}
		print "</select></div>
				<input type='hidden' name='questionnaire_id' value='$questionnaire_id'/>
				&ensp;<input type='submit' class='btn btn-default' name='add_ca_availability' value='" . TQ_("Add call attempt time slot") . "'/>
				</form></div>";
	}
print "</div>";

//page questionnairetimeslosample.php 
print "<div class=col-sm-4><h2>" . T_("Call attempt time slots for sample") . "</h2>";
print "<div class='well'>" . T_("Assigning call attempt time slots to questionnaires will only allow cases to be attempted in a time slot for the n + 1th time where it has been attempted at least n times in all assigned timeslots. Please note timeslots must cover all possible time periods otherwise no cases will be available during missing timeslots.") ."</div>";

  print "<h3 class='pull-left'>" . T_("Sample") . ":&ensp;</h3>";
  
  $sample_import_id = false;
    if (isset($_GET['sample_import_id'])) 	$sample_import_id = bigintval($_GET['sample_import_id']);
  display_sample_chooser($questionnaire_id,$sample_import_id,false, "form-inline", "form-control");

  if ($sample_import_id !== false)
  {
    $sql = "SELECT q.availability_group_id,a.description as description, CONCAT('<a href=\'?sample_import_id=$sample_import_id&amp;questionnaire_id=$questionnaire_id&amp;qs_ravailability_group=', q.availability_group_id,'\'  >" . T_("Click to unassign") . "</a>') as link
      FROM questionnaire_sample_timeslot as q, availability_group as a
      WHERE q.availability_group_id = a.availability_group_id
      AND q.questionnaire_id = '$questionnaire_id'
      AND q.sample_import_id = '$sample_import_id'";

    $qs = $db->GetAll($sql);

    if (empty($qs))
    {
      print "<h4 class='alert text-danger'>" . T_("There are no call attempt time slots selected for this questionnaire sample") . "</h4>";
    }
    else
    {
      print "<h4>" . T_("Call attempt time slots selected for this sample") . ":</h4>";
	  xhtml_table ($qs,array("availability_group_id","description","link"),false,"table table-hover");
    }

    $sql = "SELECT si.availability_group_id,si.description
      FROM availability_group as si
      LEFT JOIN questionnaire_sample_timeslot as q ON (q.sample_import_id = '$sample_import_id' AND q.questionnaire_id = '$questionnaire_id' AND q.availability_group_id = si.availability_group_id)
      WHERE q.questionnaire_id is NULL";
    
    $qs = $db->GetAll($sql);

    if (!empty($qs))
    {
      print "<div class='panel-body'>";
	  //print "<h3>" . T_("Add a call attempt time slot to this questionnaire sample:") . "</h3>";
	  print "<form action='' method='get'><div class='pull-left'><select class='form-control ' name='qs_availability_group' id='qs_availability_group'>";   
      foreach($qs as $q)
      {
        print "<option value=\"{$q['availability_group_id']}\">{$q['description']}</option>";
      }
	  print "</select></div>
      <input type='hidden' name='questionnaire_id' value='$questionnaire_id'/>
      <input type='hidden' name='sample_import_id' value='$sample_import_id'/>
      &ensp;<input type='submit' name='add_qs_availability' class='btn btn-default' value='" . T_("Add call attempt time slot for sample") . "'/>
      </form></div>";
    }
  }
print "</div>";
}

xhtml_foot($js_foot);
?>