<?
/**
 * Display the main page including all panels and tabs
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
 * @subpackage user
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 *
 */

/**
 * Configuration file
 */
include ("config.inc.php");

/**
 * XHTML functions
 */
include ("functions/functions.xhtml.php");

/**
 * Operator functions
 */
include("functions/functions.operator.php");

$db->StartTrans();

$operator_id = get_operator_id();

if (isset($_GET['endwork']))
{
	
	if (isset($_GET['note']))
	{
		$case_id = get_case_id($operator_id,false);
		$note = $db->qstr($_GET['note']);
		$sql = "INSERT INTO `case_note` (case_note_id,case_id,operator_id,note,datetime)
			VALUES (NULL,'$case_id','$operator_id',$note,CONVERT_TZ(NOW(),'System','UTC'))";
		$db->Execute($sql);
	}
	end_call_attempt($operator_id);
	end_case($operator_id);

	//if ($db->HasFailedTrans()){ print "<p>FAILED AT ENDWORK</p>";  exit();}
	$db->CompleteTrans();

	include("endwork.php");
	exit();
}


if (isset($_GET['endcase']))
{

	if (isset($_GET['note']))
	{
		$case_id = get_case_id($operator_id,false);
		$note = $db->qstr($_GET['note']);
		$sql = "INSERT INTO `case_note` (case_note_id,case_id,operator_id,note,datetime)
			VALUES (NULL,'$case_id','$operator_id',$note,CONVERT_TZ(NOW(),'System','UTC'))";
		$db->Execute($sql);
	}
	end_call_attempt($operator_id);
	end_case($operator_id);

	//if ($db->HasFailedTrans()) {print "<p>FAILED AT ENDCASE</p>"; exit();}
}

xhtml_head(T_("queXS"), true, array("css/index.css","css/tabber.css") , array("js/popup.js","js/tabber.js"));
?>

<div id="casefunctions">
	<div class='box'><a href="javascript:poptastic('call.php?end=end');"><? echo T_("End"); ?></a></div>
	<div class='box'><a href="javascript:poptastic('appointment.php');"><? echo T_("Appointment"); ?></a></div>
	<div class='box'><a href="javascript:poptastic('call.php');"><? echo T_("Call/Hangup"); ?></a></div>
	<div class='box'><a href="javascript:poptastic('supervisor.php');"><? echo T_("Supervisor"); ?></a></div>
	<div class='box' id='recbox'><a id='reclink' class='offline' href="javascript:poptastic('record.php?start=start');"><? echo T_("Start REC"); ?></a></div>
</div>

<div id="content">
<object class="embeddedobject" id="main-content" data="<? get_case_id($operator_id,true); get_call_attempt($operator_id,true); if (!is_respondent_selection($operator_id)) print(get_limesurvey_url($operator_id)); else print "rs_intro.php"; ?>" standby="Loading questionnaire..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>
</div>

<div id="respondent">
<object class="embeddedobject" id="main-respondent" data="respondent.php" standby="Loading panel..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>
</div>

<div id="qstatus">
<object class="embeddedobject" id="main-qstatus" data="status.php" standby="Loading panel..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>
</div>


<div id="calllist">


<div class="tabber">

     <div class="tabbertab">
	  <h2><? echo T_("Notes"); ?></h2>
	<object class="embeddedobject" id="main-casenotes" data="casenote.php" standby="Loading panel..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>     
   </div>


     <div class="tabbertab">
	  <h2><? echo T_("Call history"); ?></h2>
	  <object class="embeddedobject" id="main-calllist" data="calllist.php" standby="Loading panel..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>
     </div>


     <div class="tabbertab">
	  <h2><? echo T_("Shifts"); ?></h2>
	  <object class="embeddedobject" id="main-shifts" data="shifts.php" standby="Loading panel..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>
     </div>


     <div class="tabbertab">
	  <h2><? echo T_("Appointments"); ?></h2>
	  <object class="embeddedobject" id="main-appointmentlist" data="appointmentlist.php" standby="Loading panel..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>
     </div>


     <div class="tabbertab">
	  <h2><? echo T_("Performance"); ?></h2>
	  <object class="embeddedobject" id="main-performance" data="performance.php" standby="Loading panel..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>
     </div>

     <div class="tabbertab">
	  <h2><? echo T_("Work history"); ?></h2>
	  <object class="embeddedobject" id="main-callhistory" data="callhistory.php" standby="Loading panel..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>
     </div>


     <div class="tabbertab">
	  <h2><? echo T_("Info"); ?></h2>
	  <object class="embeddedobject" id="main-info" data="info.php" standby="Loading panel..." type="application/xhtml+xml"><p>Error, try with Firefox</p></object>
     </div>


</div>


</div>

<?

xhtml_foot();


	//if ($db->HasFailedTrans()){ print "<p>FAILED AT END of index</p>"; exit();}
$db->CompleteTrans();

?>
