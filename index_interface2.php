<?php 
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
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright Australian Consortium for Social and Political Research Inc 2007,2008
 * @package queXS
 * @subpackage user
 * @link http://www.acspri.org.au/ queXS was writen for Australian Consortium for Social and Political Research Incorporated (ACSPRI)
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

/** 
 * Authentication
 */
include ("auth-interviewer.php");




$popupcall = false;
$operator_id = get_operator_id();

if ($operator_id === false) die();

$db->StartTrans();

if (isset($_GET['endwork']))
{
	$call_id = get_call($operator_id);

	if ($call_id)
	{
		//Don't end the case if we are on a call
		$popupcall = true;
	}
	else
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
	
		require("waitnextcase_interface2.php");
		unset ($_GET['endwork']);
		exit();
	}
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

	$endthecase = true;

	if (isset($_GET['outcome']))
	{
		$outcome_id = intval($_GET['outcome']);
		end_call($operator_id,$outcome_id);

		$sql = "SELECT tryanother
			FROM outcome
			WHERE outcome_id = '$outcome_id'";

		$rs = $db->GetRow($sql);

		if (!empty($rs) && $rs['tryanother'] == 1)
		{
			//we can try another number... 

			$case_id = get_case_id($operator_id,false);
			$call_attempt_id = get_call_attempt($operator_id,false);
			//check if there is another number to try...
                        $sql = "SELECT c. *
                                FROM contact_phone AS c
                                LEFT JOIN (
                                                SELECT contact_phone.contact_phone_id
                                                FROM contact_phone
                                                LEFT JOIN `call` ON ( call.contact_phone_id = contact_phone.contact_phone_id )
                                                LEFT JOIN outcome ON ( call.outcome_id = outcome.outcome_id )
                                                WHERE contact_phone.case_id = '$case_id'
                                                AND outcome.tryagain =0
                                          ) AS l ON l.contact_phone_id = c.contact_phone_id
                                LEFT JOIN
                                (
                                 SELECT contact_phone_id
                                 FROM `call`
                                 WHERE call_attempt_id = '$call_attempt_id'
                                 AND outcome_id != 18
                                ) as ca on ca.contact_phone_id = c.contact_phone_id
                                WHERE c.case_id = '$case_id'
                                AND l.contact_phone_id IS NULL
                                AND ca.contact_phone_id IS NULL"; //only select numbers that should be tried again and have not been tried in this attempt which are not the accidental hang up outcome

                        $rs = $db->GetAll($sql);

			if (!empty($rs))			
				$endthecase = false;
		}
	}

	if ($endthecase)
	{
		end_call_attempt($operator_id);
		end_case($operator_id);
	
		$db->CompleteTrans(); //need to complete here otherwise getting the case later will fail
	
		require("waitnextcase_interface2.php");
		exit();
	}

}

$js = array("js/popup.js","js/tabber.js","include/jquery/jquery.min.js","include/jquery-ui/jquery-ui.min.js");
$body = true;
$script = "";
if (AUTO_LOGOUT_MINUTES !== false)
{
	$js[] = "include/nap-1.0.0/js/jquery.nap-1.0.0.js";
	$script = "<script type='text/javascript'>
		   $(document).nap(
			function() { 
				location.replace('" . QUEXS_URL . "?endwork=endwork&auto=auto');
			},
			function() { 
				//do nothing if woken up as shouldn't get here
			},
			" . (AUTO_LOGOUT_MINUTES * 60) . "
		);</script></head><body>";

	$body = false;
}

if ($popupcall)
	$js[] = "js/popupcallonload.js";

if (HEADER_EXPANDER) 
{
	$js[] = "js/headerexpand_interface2.js";
	$js[] = "js/headerexpandmanual_interface2.js";
}
else if (HEADER_EXPANDER_MANUAL) 
{
	$js[] = "js/headerexpand_interface2.js";
	$js[] = "js/headerexpandmanual_interface2.js";
}


xhtml_head(T_("Case"), $body, array("include/bootstrap/css/bootstrap.min.css","include/bootstrap/css/bootstrap-theme.min.css","include/font-awesome/css/font-awesome.css","css/index_interface2.css","css/tabber_interface2.css","include/jquery-ui/jquery-ui.min.css"),$js,false,false, false,false,false);
print $script;

$case_id = get_case_id($operator_id,true);

$sql = "SELECT q.self_complete, q.referral
        FROM questionnaire as q, `case` as c
        WHERE c.case_id = $case_id 
        AND c.questionnaire_id = q.questionnaire_id";

$scr = $db->GetRow($sql);
$sc = $scr['self_complete'];
$ref = $scr['referral'];


$availability = is_using_availability($case_id);
?>
<div class="container-fluid "> 
	<div class="row ">

		<div id="casefunctions" class="col-sm-2 panel-body">

			<a href="javascript:poptastic('call_interface2.php');" class="btn btn-default btn-block" style="border-radius:15px; color:blue"><strong><?php  echo T_("Outcome"); ?>  <i class="fa fa-lg fa-check-square-o fa-fw"></i></strong></a></br>
			<a href="javascript:poptastic('appointment.php');" class="btn btn-default btn-block " style="border-radius:15px; color:green"><strong><?php  echo T_("Appointment"); ?> <i class="fa fa-lg fa-clock-o fa-fw"></i></strong></a></br>
			<?php if ($sc == 1) { ?>
			<a href="javascript:poptastic('email.php?interface2=true');" class="btn btn-default btn-block" style="border-radius:15px; color:blue"><strong><?php  echo T_("Email"); ?>  <i class="fa fa-lg fa-envelope-o fa-fw"></i></strong></a></br>
			<?php } ?>
			<?php if ($ref == 1) { ?>
			<a href="javascript:poptastic('referral.php?interface2=true');" class="btn btn-default btn-block" style="border-radius:15px; color:blue"><strong><?php  echo T_("Referral"); ?>  <i class="fa fa-lg fa-link fa-fw"></i></strong></a></br>
			<?php } ?>
			<a href="?endwork=endwork"; class="btn btn-default btn-block" style="border-radius:15px; color:red"><strong><?php  echo T_("End work"); ?>  <i class="fa fa-lg fa-ban fa-fw"></i></strong></a>

		</div>

		<div id="qstatus" class="col-sm-3 panel-body">
			<?php xhtml_object("status_interface2.php","main-qstatus", "col-sm-12" );?>
		</div>
		
			<?php  if (HEADER_EXPANDER_MANUAL){ ?><div class="headerexpand"><i id='headerexpandimage' class="fa fa-lg fa-toggle-down fa-fw" title='<?php  echo T_('Arrow for expanding or contracting'); ?>'></i></div> <?php  } ?>
		
		<div id="calllist" class="col-sm-7">
		
			<div class="tabber" id="tab-main">
			
			<?php  
			 if (isset($appointment)) {} else {$appointment = "";}

				   if (TAB_CASENOTES) { ?>
				<div class="tabbertab <?php  if ((DEFAULT_TAB == 'casenotes' && !$appointment) || (DEFAULT_TAB_APPOINTMENT == 'casenotes' && $appointment)) 
								print "tabbertabdefault"; ?>">
				  <h2><?php  echo T_("Notes"); ?></h2>
				  <div id="div-casenotes" class="tabberdiv"><?php xhtml_object("casenote.php","main-casenotes","col-sm-12");?></div>
			   </div>
			<?php  }?>

			<?php  if ($availability) { ?>
				<div class="tabbertab <?php  if ((DEFAULT_TAB == 'availability' && !$appointment) || (DEFAULT_TAB_APPOINTMENT == 'availability' && $appointment)) 
								print "tabbertabdefault"; ?>">
				  <h2><?php  echo T_("Availability"); ?></h2>
				  <div id="div-casenotes" class="tabberdiv"><?php xhtml_object("availability.php","main-availability","col-sm-12");?></div>
			   </div>
			<?php  }?>

			<?php  if (TAB_CONTACTDETAILS) { ?>
				 <div class="tabbertab <?php  if ((DEFAULT_TAB == 'contactdetails' && !$appointment) || (DEFAULT_TAB_APPOINTMENT == 'contactdetails' && $appointment)) 
								print "tabbertabdefault"; ?>">
				  <h2><?php  echo T_("Contact details"); ?></h2>
				  <div id="div-contactdetails" class="tabberdiv"><?php xhtml_object("contactdetails.php","main-contactdetails","col-sm-12");?></div>
			   </div>
			<?php  }?>

			<?php  if (TAB_CALLLIST) { ?>
				 <div class="tabbertab <?php  if ((DEFAULT_TAB == 'calllist' && !$appointment) || (DEFAULT_TAB_APPOINTMENT == 'calllist' && $appointment)) 
								print "tabbertabdefault"; ?>">
				  <h2><?php  echo T_("Call history"); ?></h2>
				  <div id="div-calllist" class="tabberdiv"><?php xhtml_object("calllist.php","main-calllist","col-sm-12");?></div>
				 </div>
			<?php  }?>

			<?php  if (TAB_SHIFTS) { ?>
				 <div class="tabbertab <?php  if ((DEFAULT_TAB == 'shifts' && !$appointment) || (DEFAULT_TAB_APPOINTMENT == 'shifts' && $appointment)) 
								print "tabbertabdefault"; ?>" id="tab-shifts">
				  <h2><?php  echo T_("Shifts"); ?></h2>
				  <div id="div-shifts" class="tabberdiv"><?php xhtml_object("shifts.php","main-shifts","col-sm-12");?></div>
				 </div>
			<?php  }?>

			<?php  if (TAB_APPOINTMENTLIST) { ?>
				 <div class="tabbertab <?php  if ((DEFAULT_TAB == 'appointmentlist' && !$appointment) || (DEFAULT_TAB_APPOINTMENT == 'appointmentlist' && $appointment)) 
								print "tabbertabdefault"; ?>">
				  <h2><?php  echo T_("Appointments"); ?></h2>
				  <div id="div-appointmentlist" class="tabberdiv"><?php xhtml_object("appointmentlist.php","main-appointmentlist","col-sm-12");?></div>
				 </div>
			<?php  }?>

			<?php  if (TAB_PERFORMANCE) { ?>
				 <div class="tabbertab <?php  if ((DEFAULT_TAB == 'performance' && !$appointment) || (DEFAULT_TAB_APPOINTMENT == 'performance' && $appointment)) 
								print "tabbertabdefault"; ?>">
				  <h2><?php  echo T_("Performance"); ?></h2>
				  <div id="div-performance" class="tabberdiv"><?php xhtml_object("performance.php","main-performance","col-sm-12");?></div>
				 </div>
			<?php  }?>

			<?php  if (TAB_CALLHISTORY) { ?>
				 <div class="tabbertab <?php  if ((DEFAULT_TAB == 'callhistory' && !$appointment) || (DEFAULT_TAB_APPOINTMENT == 'callhistory' && $appointment)) 
								print "tabbertabdefault"; ?>">
				  <h2><?php  echo T_("Work history"); ?></h2>
				  <div id="div-callhistory" class="tabberdiv"><?php xhtml_object("callhistory.php","main-callhistory","col-sm-12");?></div>
				 </div>
			<?php  }?>

			<?php  if (TAB_PROJECTINFO) { ?>
				 <div class="tabbertab <?php  if ((DEFAULT_TAB == 'projectinfo' && !$appointment) || (DEFAULT_TAB_APPOINTMENT == 'projectinfo' && $appointment)) 
								print "tabbertabdefault"; ?>">
				  <h2><?php  echo T_("Project information"); ?></h2>
				  <div id="div-projectinfo" class="tabberdiv"><?php xhtml_object("project_info.php","main-projectinfo","col-sm-12");?></div>
				 </div>
			<?php  }?>


			<?php  if (TAB_INFO) { ?>
				 <div class="tabbertab <?php  if ((DEFAULT_TAB == 'info' && !$appointment) || (DEFAULT_TAB_APPOINTMENT == 'info' && $appointment)) 
								print "tabbertabdefault"; ?>">
				  <h2><?php  echo T_("Info"); ?></h2>
				  <div id="div-info" class="tabberdiv"><?php xhtml_object("info.php","main-info","col-sm-12");?></div>
				 </div>
			<?php  }?>

			</div>
			
		</div>	

	</div>


	
	<div class="row"> 

		<?php  

			$ca = get_call_attempt($operator_id,true);
			$call_id = get_call($operator_id);
			$appointment = false;
			if ($ca)
			{
				$appointment = is_on_appointment($ca);
				$respondent_id  = get_respondent_id($ca);
			}

			if (!$call_id)
			{
				if ($appointment)
				{
					//create a call on the appointment number
					$sql = "SELECT cp.*, a.respondent_id
						FROM contact_phone as cp, appointment as a
						WHERE cp.case_id = '$case_id'
						AND a.appointment_id = '$appointment'
						AND a.contact_phone_id = cp.contact_phone_id";
				}
				else
				{
					//create a call on the first available number by priority
					$sql = "SELECT c. *
						FROM contact_phone AS c
						LEFT JOIN (
								SELECT contact_phone.contact_phone_id
								FROM contact_phone
								LEFT JOIN `call` ON ( call.contact_phone_id = contact_phone.contact_phone_id )
								LEFT JOIN outcome ON ( call.outcome_id = outcome.outcome_id )
								WHERE contact_phone.case_id = '$case_id'
								AND outcome.tryagain =0
							  ) AS l ON l.contact_phone_id = c.contact_phone_id
						LEFT JOIN
						(
						 SELECT contact_phone_id
						 FROM `call`
						 WHERE call_attempt_id = '$ca'
						 AND outcome_id NOT IN (15,18)
						) as ca on ca.contact_phone_id = c.contact_phone_id
						WHERE c.case_id = '$case_id'
						AND l.contact_phone_id IS NULL
						AND ca.contact_phone_id IS NULL
						order by c.priority ASC";


				}
				$rs = $db->GetRow($sql);

				if (!empty($rs))
				{
					$contact_phone_id = $rs['contact_phone_id'];				

					if (!isset($rs['respondent_id']))
					{
						$sql = "SELECT respondent_id
							FROM respondent
							WHERE case_id = $case_id";

						$respondent_id = $db->GetOne($sql);
					}
					else
					{
						$respondent_id = $rs['respondent_id'];
					}
					$call_id = get_call($operator_id,$respondent_id,$contact_phone_id,true);
				}
			}
				
			if (!is_respondent_selection($operator_id))
				$data = get_limesurvey_url($operator_id);
			else 
				$data = get_respondentselection_url($operator_id,true,true); //use second interface

			xhtml_object($data,"main-content", "embeddedobject content"); 

		?>
		

	</div>
</div>

<?php 

xhtml_foot();

	//if ($db->HasFailedTrans()){ print "<p>FAILED AT END of index</p>"; exit();}
$db->CompleteTrans();

?>
