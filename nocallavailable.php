<?php 
/**
 * Display error message when no current call available
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
 * @copyright ACSPRI 2010
 * @package queXS
 * @subpackage user
 * @link http://www.acspri.org.au/ queXS was writen for ACSPRI - Australian Consortium for Social and Political Research Inc.
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
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
 * Authentication
 */
require ("auth-interviewer.php");

/** 
 * Language functions
 */
include_once ("lang.inc.php");

if (isset($_GET['contact_phone']))
{
  include_once ("functions/functions.operator.php");
  include_once ("functions/functions.input.php");
  $operator_id = get_operator_id();
	$contact_phone_id = bigintval($_GET['contact_phone']);
	$call_attempt_id = get_call_attempt($operator_id,false);
	$respondent_id = get_respondent_id($call_attempt_id);
	$call_id = get_call($operator_id,$respondent_id,$contact_phone_id,true);
	if ($call_id)
	{
		if (is_voip_enabled($operator_id))
		{
			include("functions/functions.voip.php");
			$v = new voip();
    	$v->connect(VOIP_SERVER);
		  $v->dial(get_extension($operator_id),get_call_number($call_id));
     }
     header('Location: '.get_respondentselection_url($operator_id,false));
     die();
  }
}


$js = array();
if (AUTO_LOGOUT_MINUTES !== false)
        $js = array("include/jquery/jquery-1.4.2.min.js","js/childnap.js");

if (isset($_GET['interface2'])) { if (browser_ie()) $jsw = "js/window_ie6_interface2.js"; else $jsw = "js/window_interface2.js"; } 
else { if (browser_ie()) $jsw = "js/window_ie6.js"; else $jsw = "js/window.js"; }

$js[] = $jsw;

xhtml_head(T_("No call available"),true,array("css/table.css"),$js);

?>
<h1><?php  echo T_("Please click on:") . " " . T_("Call/Hangup") . " " .T_("to display call script"); ?></h1>
<?php 

//auto dial, if set should check for numbers to call. If one or more, set a timer for calling the first available number
//if none - timer should end the case
//
//if the user clicks on call / hangup the timer should stop

if (AUTO_DIAL_SECONDS !== false)
{
  include_once ("functions/functions.operator.php");
  $operator_id = get_operator_id();
  $call_attempt_id = get_call_attempt($operator_id,false);
  $case_id = get_case_id($operator_id);
  $contact_phone_id = false;

  //first check we aren't already on a call
  if (is_on_call($operator_id) == false)
  {
    //check if voip is enabled and available
		if (is_voip_enabled($operator_id))
		{
      if (get_extension_status($operator_id))
      {
        //if we are on an appointment, we will just call the specified number for the appointment
        $appointment_id = is_on_appointment($call_attempt_id);

        if ($appointment_id)
        {
          $sql = "SELECT c.contact_phone_id
                  FROM contact_phone as c, appointment as a
                  WHERE a.appointment_id = '$appointment_id'
                  AND a.contact_phone_id = c.contact_phone_id";
        
          $contact_phone_id = $db->GetOne($sql);
        }
        else
        {
          //determine whether we should make any more calls based on the last call outcome
      
          $sql = "SELECT o.tryanother, o.require_note
                  FROM `call` as c, `outcome` as o
                  WHERE c.call_attempt_id = '$call_attempt_id'
                  AND c.outcome_id = o.outcome_id
                  ORDER BY call_id DESC
                  LIMIT 1";
      
          $rs = $db->GetRow($sql);
      
          if ((empty($rs) || $rs['tryanother'] == 1)) //dial another number only when available and not ending
          {
            $rn = 0;
            if (!empty($rs) && $rs['require_note'] == 1) $rn = 1;
      
            //an exclusion left join
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
      
              //could be updated to take in to account the time delay and outcome
      
            $rs = $db->GetAll($sql);
      
            if (!empty($rs))
            {
              $contact_phone_id = $rs[0]['contact_phone_id'];
            }
          }
        }  	

        $jsfunctocall = "";
        $texttodisplay = "";
        $endtexttodisplay = "";

        if ($contact_phone_id !== false)
        {
          //got a number to dial so initiate the countdown to begin dialing
          $texttodisplay = TQ_("Will dial in");
          $endtexttodisplay = TQ_("Dialling now");
          $jsfunctocall = "document.location.href = 'nocallavailable.php?contact_phone=" . $contact_phone_id . "';";
        }
        else
        {
          //no more numbers to dial so initiate the countdown to end the case
          $texttodisplay = TQ_("Will end case in");
          $endtexttodisplay = TQ_("Ending case now");
          $jsfunctocall = "openParent('endcase=endcase');";
        }
        print "<div id='timer'></div>";
        print " <script type='text/javascript'>
                var count=" . AUTO_DIAL_SECONDS . ";
                var counter=setInterval(timer, 1000); 
             
                function timer()
                {
                  count=count-1;
                  if (count <= 0)
                  {
                    clearInterval(counter);
                    document.getElementById('timer').innerHTML='". $endtexttodisplay  ."';
                    " . $jsfunctocall .  " 
                    return;
                  }
                  document.getElementById('timer').innerHTML='". $texttodisplay  ." ' + count + ' " . TQ_("seconds") . "';
                }

                window.onload = function()
                {
                  timer();
                }
               </script>";

      }
      else
      {
        //voip extension not active
				print "<div>" . T_("Your VoIP extension is not active. Please activate VoIP by clicking once on the red button that says 'VoIP Off'") . "</div>";
      }
    }
    else
    {
      //voip isn't enabled so can't auto dial
			print "<div>" . T_("Auto dialling unavailable as VoIP is not enabled") . "</div>";
    }
  }
  else
  {
    //on a call so can't proceed
		print "<div>" . T_("Auto dialling unavailable as you are already on a call") . "</div>";
  }
}


xhtml_foot();


?>
