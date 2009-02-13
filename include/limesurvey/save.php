<?php
/*
* LimeSurvey
* Copyright (C) 2007 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: GNU/GPL License v2 or later, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
* 
* $Id: save.php 5080 2008-06-15 17:45:25Z c_schmitz $

//Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

Redesigned 7/25/2006 - swales

1.  Save Feature (// --> START NEW FEATURE - SAVE)

How it used to work
-------------------
1. The old save method would save answers to the "survey_x" table only when the submit button was clicked.
2. If "allow saves" was turned on then answers were temporarily recorded in the "saved" table.

Why change this feature?
------------------------
1. If a user did not complete a survey, ALL their answers were lost since no submit (database insert) was performed.

2. The old save design did allow for a person to reload their saved survey, but it did not support
multiple users working on the same survey at the same time.  Issues included saving only at the end, or
when a user clicked the "Save so far" button.  During this save/update ALL the answers were updated, not
just the ones modified.  This would cause the 'last one to save' wins issue.  It also did not reload
answers between pages (group by group), so if someone else was working on the same survey instance you
did not see their updates on your instance.

Save Feature redesign
---------------------
Benefits
1. Partial survey answers are saved (provided at least Next/Prev/Last/Submit/Save so far clicked at least once).
2. Multiple users can work on same survey instance at same time.
3. Answers are reloaded after each page save, so if other people are changing them the current user will see updates.

Details.
1. The answers are saved in the "survey_x" table only.  The "saved" table is no longer used.
2. The "saved_control" table has new column (srid) that points to the "survey_x" record it corresponds to.
3. Answers are saved every time you move between pages (Next,Prev,Last,Submit, or Save so far).
4. Only the fields modified on the page are updated. A new hidden field "modfields" store which fields have changed. - REVERTED
5. Answered are reloaded from the database after the save so that if some other answers were modified by someone else
the updates would be picked up for the current page.  There is still an issue if two people modify the same
answer at the same time.. the 'last one to save' wins.
6. The survey_x datestamp field is updated every time the record is updated.
7. Template can now contain {DATESTAMP} to show the last modified date/time.
8. A new field 'submitdate' has been added to the survey_x table and is written when the submit button is clicked.
9. Save So Far now displays on Submit page. This allows the user one last chance to create a saved_control record so they
can return later.

Notes
-----
1. A new column SRID has been added to saved_control.
2. saved table no longer exists.
*/


if (!isset($homedir) || isset($_REQUEST['$homedir'])) {die("Cannot run this script directly");}


global $connect;
//First, save the posted data to session
//Doing this ensures that answers on the current page are saved as well.
//CONVERT POSTED ANSWERS TO SESSION VARIABLES
if (isset($_POST['fieldnames']) && $_POST['fieldnames'])
{
    $postedfieldnames=explode("|", $_POST['fieldnames']);
    
      // Remove invalid fieldnames from fieldnames array  
      for($x=count($postedfieldnames)-1;$x>=0;$x--)
      {
         if (strpos($postedfieldnames[$x],$surveyid.'X')===false)
         {
             array_remval($postedfieldnames[$x],$postedfieldnames);
         }
         
      }
      $_POST['fieldnames']=implode("|",$postedfieldnames);
 
    
	foreach ($postedfieldnames as $pf)
	{
		if (isset($_POST[$pf])) {$_SESSION[$pf] = $_POST[$pf];}
		if (!isset($_POST[$pf])) {$_SESSION[$pf] = "";}
	}
}                  

//SAVE if on page with questions or on submit page
if ((isset($_POST['fieldnames']) && $_POST['fieldnames']) || (isset($_POST['move']) && $_POST['move'] == "movesubmit"))
{
	if ($thissurvey['active'] == "Y" && !isset($_SESSION['finished'])) 	// Only save if active and the survey wasn't already submitted 
	{
		// SAVE DATA TO SURVEY_X RECORD
		$subquery = createinsertquery();
		if ($subquery)
		{
			if ($result=$connect->Execute($subquery))  // Checked
			{
	            if (substr($subquery,0,6)=='INSERT')
				{
	               $tempID=$connect->Insert_ID($thissurvey['tablename'],"id"); // Find out id immediately if inserted 
	        	   $_SESSION['srid'] = $tempID;
	        	   $saved_id = $tempID;
				}
				if (isset($move) && $move == "movesubmit")
				{
					$connect->Execute("DELETE FROM ".db_table_name("saved_control")." where srid=".$_SESSION['srid']);   // Checked    
				}
			} 
            else 
                {
                    echo submitfailed($connect->ErrorMsg());
                }
		}
	}
}

// CREATE SAVED CONTROL RECORD USING SAVE FORM INFORMATION
if (isset($_POST['saveprompt']) || ($thissurvey['allowsave'] == "Y"  && isset($_POST['saveall']) && !isset($_SESSION['scid'])) )  //Value submitted when clicking on 'Save Now' button on SAVE FORM
{
	if ($thissurvey['active'] == "Y") 	// Only save if active
	{
		savedcontrol();
		if (isset($errormsg) && $errormsg != "")
		{
			showsaveform();
		}
	}
	else
	{
		$_SESSION['scid'] = 0;		// If not active set to a dummy value to save form does not continue to show.
	}
}

// DISPLAY SAVE FORM
// Displays even if not active just to show how it would look when active (for testing purposes)
// Show 'SAVE FORM' only when click the 'Save so far' button the first time
if ($thissurvey['allowsave'] == "Y"  && isset($_POST['saveall']) && !isset($_SESSION['scid']))
{
	//showsaveform();
}
elseif ($thissurvey['allowsave'] == "Y"  && isset($_POST['saveall']) && isset($_SESSION['scid']) )   //update the saved step only
{
    $connect->Execute("update ".db_table_name("saved_control")." set saved_thisstep={$thisstep} where scid=".$_SESSION['scid']);  // Checked    
}



function showsaveform()
{
	//Show 'SAVE FORM' only when click the 'Save so far' button the first time, or when duplicate is found on SAVE FORM.
	global $thistpl, $errormsg, $thissurvey, $surveyid, $clang, $clienttoken, $relativeurl, $thisstep;
	sendcacheheaders();
	echo "<html>\n";
	foreach(file("$thistpl/startpage.pstpl") as $op)
	{
		echo templatereplace($op);
	}
	echo "\n\n<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->\n"
	."\t<script type='text/javascript'>\n"
	."\t<!--\n"
	."\t\tfunction checkconditions(value, name, type)\n"
	."\t\t\t{\n"
	."\t\t\t}\n"
	."\t//-->\n"
	."\t</script>\n\n";

	echo "<form method='post' action='$relativeurl/index.php'>\n";
	//PRESENT OPTIONS SCREEN
	if (isset($errormsg) && $errormsg != "")
	{
		$errormsg .= "<p>".$clang->gT("Please try again.")."</p>";
	}
	foreach(file("$thistpl/save.pstpl") as $op)
	{
		echo templatereplace($op);
	}
	//END
	echo "<input type='hidden' name='sid' value='$surveyid'>\n";
	echo "<input type='hidden' name='thisstep' value='",$thisstep,"'>\n";
	echo "<input type='hidden' name='token' value='",$clienttoken,"'>\n";
	echo "<input type='hidden' name='saveprompt' value='Y'>\n";
	echo "</form>";

	foreach(file("$thistpl/endpage.pstpl") as $op)
	{
		echo templatereplace($op);
	}
	echo "</html>\n";
	exit;
}



function savedcontrol()
{
	//This data will be saved to the "saved_control" table with one row per response.
	// - a unique "saved_id" value (autoincremented)
	// - the "sid" for this survey
	// - the "srid" for the survey_x row id
	// - "saved_thisstep" which is the step the user is up to in this survey
	// - "saved_ip" which is the ip address of the submitter
	// - "saved_date" which is the date ofthe saved response
	// - an "identifier" which is like a username
	// - a "password"
	// - "fieldname" which is the fieldname of the saved response
	// - "value" which is the value of the response
	//We start by generating the first 5 values which are consistent for all rows.

	global $connect, $surveyid, $dbprefix, $thissurvey, $errormsg, $publicurl, $sitename, $timeadjust, $clang, $clienttoken, $thisstep;

	//Check that the required fields have been completed.
	$errormsg="";
	//if (!isset($_POST['savename']) || !$_POST['savename']) {$errormsg.=$clang->gT("You must supply a name for this saved session.")."<br />\n";}
	//if (!isset($_POST['savepass']) || !$_POST['savepass']) {$errormsg.=$clang->gT("You must supply a password for this saved session.")."<br />\n";}
	//if ((isset($_POST['savepass']) && !isset($_POST['savepass2'])) || $_POST['savepass'] != $_POST['savepass2'])
	//{$errormsg.=$clang->gT("Your passwords do not match.")."<br />\n";}
	// if security question asnwer is incorrect
	/*
    if (function_exists("ImageCreate"))
    {
	    if (!isset($_POST['loadsecurity']) || 
		!isset($_SESSION['secanswer']) ||
		$_POST['loadsecurity'] != $_SESSION['secanswer'])
	    {
		    $errormsg .= $clang->gT("The answer to the security question is incorrect")."<br />\n";
	    }
    }
	 */

	if ($errormsg)
	{
		return;
	}
	//All the fields are correct. Now make sure there's not already a matching saved item
	$query = "SELECT COUNT(*) FROM {$dbprefix}saved_control\n"
	."WHERE sid=$surveyid\n"
	."AND identifier=".db_quoteall($_POST['token'],true);
	$result = db_execute_num($query) or safe_die("Error checking for duplicates!<br />$query<br />".$connect->ErrorMsg());   // Checked    
	list($count) = $result->FetchRow();
	if ($count == 1)
	{
		//we should update the field with the latest $_SESSION['step'] - stored in saved_thisstep
		$sql = "UPDATE {$dbprefix}saved_control SET saved_thisstep = '{$_SESSION['step']}' WHERE sid=$surveyid AND identifier='{$_POST['token']}'";
		$connect->Execute($sql);
	}
	else if ($count > 0)
	{
		$errormsg.=$clang->gT("This name has already been used for this survey. You must use a unique save name.")."<br />\n";
		return;
	}
	else
	{
		//INSERT BLANK RECORD INTO "survey_x" if one doesn't already exist
		if (!isset($_SESSION['srid']))
		{
            $today = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust);     
			$sdata = array("datestamp"=>$today,
			"ipaddr"=>$_SERVER['REMOTE_ADDR'],
			"startlanguage"=>GetBaseLanguageFromSurveyID($surveyid),
			"refurl"=>getenv("HTTP_REFERER"),
			"token"=>$_POST['token']);

			//One of the strengths of ADOdb's AutoExecute() is that only valid field names for $table are updated
			if ($connect->AutoExecute($thissurvey['tablename'], $sdata,'INSERT'))    // Checked    
			{
				$srid = $connect->Insert_ID($thissurvey['tablename'],"sid");
				$_SESSION['srid'] = $srid;
			}
			else
			{
				safe_die("Unable to insert record into survey table.<br /><br />".$connect->ErrorMsg());
			}
		}
		//CREATE ENTRY INTO "saved_control"
        $today = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust);      
		$scdata = array("sid"=>$surveyid,
		"srid"=>$_SESSION['srid'],
		"identifier"=>$_POST['token'], // Binding does escape , so no quoting/escaping necessary
		"access_code"=>md5($_POST['token']),
		"email"=>$_POST['token'],
		"ip"=>$_SERVER['REMOTE_ADDR'],
		"refurl"=>getenv("HTTP_REFERER"),
		"saved_thisstep"=>$thisstep,
		"status"=>"S",
		"saved_date"=>$today);


		if ($connect->AutoExecute("{$dbprefix}saved_control", $scdata,'INSERT'))   // Checked    
		{
			$scid = $connect->Insert_ID("{$dbprefix}saved_control",'scid');
			$_SESSION['scid'] = $scid;
		}
		else
		{
			safe_die("Unable to insert record into saved_control table.<br /><br />".$connect->ErrorMsg());
		}

		$_SESSION['holdname']=$_POST['token']; //Session variable used to load answers every page. Unsafe - so it has to be taken care of on output
		$_SESSION['holdpass']=$_POST['token']; //Session variable used to load answers every page.  Unsafe - so it has to be taken care of on output        

		//Email if needed
		/*
		if (isset($_POST['saveemail']))
		{
			if (validate_email($_POST['saveemail']))
			{
				$subject=$clang->gT("Saved Survey Details") . " - " . $thissurvey['name'];
				$message=$clang->gT("Thank you for saving your survey in progress.  The following details can be used to return to this survey and continue where you left off.  Please keep this e-mail for your reference - we cannot retrieve the password for you.","unescaped");
				$message.="\n\n".$thissurvey['name']."\n\n";
				$message.=$clang->gT("Name","unescaped").": ".$_POST['savename']."\n";
				$message.=$clang->gT("Password","unescaped").": ".$_POST['savepass']."\n\n";
				$message.=$clang->gT("Reload your survey by clicking on the following link (or pasting it into your browser):","unescaped").":\n";
				$message.=$publicurl."/index.php?sid=$surveyid&loadall=reload&scid=".$scid."&loadname=".urlencode($_POST['savename'])."&loadpass=".urlencode($_POST['savepass']);

				if ($clienttoken){$message.="&token=".$clienttoken;}
				$from="{$thissurvey['adminname']} <{$thissurvey['adminemail']}>";
				if (MailTextMessage($message, $subject, $_POST['saveemail'], $from, $sitename, false, getBounceEmail($surveyid)))
				{
					$emailsent="Y";
				}
				else
				{
					echo "Error: Email failed, this may indicate a PHP Mail Setup problem on your server. Your survey details have still been saved, however you will not get an email with the details. You should note the \"name\" and \"password\" you just used for future reference.";
				}
			}
		}
		 */
	}
}

//FUNCTIONS USED WHEN SUBMITTING RESULTS:
function createinsertquery()
{

	global $thissurvey, $timeadjust, $move;
	global $deletenonvalues, $thistpl;
	global $surveyid, $connect, $clang, $postedfieldnames;

	require_once("./classes/inputfilter/class.inputfilter_clean.php");
    $myFilter = new InputFilter('','',1,1,1);

	$fieldmap=createFieldMap($surveyid); //Creates a list of the legitimate questions for this survey
	
	if (isset($_SESSION['insertarray']) && is_array($_SESSION['insertarray']))
	{
        $inserts=array_unique($_SESSION['insertarray']);
    
        foreach ($inserts as $value)
        {
            //Work out if the field actually exists in this survey
            $fieldexists = arraySearchByKey($value, $fieldmap, "fieldname", 1);
            //Iterate through possible responses
            if (isset($_SESSION[$value]) && !empty($fieldexists))
            {
                //If deletenonvalues is ON, delete any values that shouldn't exist
                if($deletenonvalues==1) {checkconfield($value);}
                //Only create column name and data entry if there is actually data!
                $colnames[]=$value;
                // most databases do not allow to insert an empty value into a datefield, 
                // therefore if no date was chosen in a date question the insert value has to be NULL
                if ($_SESSION[$value]=='' && $fieldexists['type']=='D')      
                {                        
                    $values[]='NULL';
                }
                  else  {
                		$values[]=$connect->qstr($myFilter->process($_SESSION[$value]));
                        }
            }
        }
            
		if ($thissurvey['datestamp'] == "Y")
		{
			$_SESSION['datestamp']=date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust);      
            
		}


		// First compute the submitdate
		if ($thissurvey['private'] =="Y" && $thissurvey['datestamp'] =="N")
		{
			// In case of anonymous answers survey with no datestamp
			// then the the answer submutdate gets a conventional timestamp
			// 1st Jan 1980
			$mysubmitdate = date("Y-m-d H:i:s",mktime(0,0,0,1,1,1980));
		}
		else
		{
			$mysubmitdate = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust);      
		}
	
		// CHECK TO SEE IF ROW ALREADY EXISTS
        // srid (=Survey Record ID ) is set when the there were already answers saved for that survey
		if (!isset($_SESSION['srid']))
		{
            //Prepare row insertion


            if (!isset($colnames) || !is_array($colnames)) //If something went horribly wrong - ie: none of the insertarray fields exist for this survey, crash out
            {
                echo submitfailed();
                exit;
            }
           	

			if (isset($_POST['token']) && in_array('token',$colnames))
				$values[array_search('token',$colnames)] = $_POST['token'];

			// INSERT NEW ROW
			$query = "INSERT INTO ".db_quote_id($thissurvey['tablename'])."\n"
			."(".implode(', ', array_map('db_quote_id',$colnames));
			if ($thissurvey['datestamp'] == "Y")
			{
				$query .= ",".db_quote_id('datestamp');
				$query .= ",".db_quote_id('startdate');
			}
			if ($thissurvey['ipaddr'] == "Y")
			{
				$query .= ",".db_quote_id('ipaddr');
			}
			$query .= ",".db_quote_id('startlanguage'); 
			if ($thissurvey['refurl'] == "Y")
			{
				$query .= ",".db_quote_id('refurl'); 
			}
			if ((isset($move) && $move == "movesubmit") && ($thissurvey['format'] != "A"))
			{
				$query .= ",".db_quote_id('submitdate'); 
			}
			if (isset($_POST['token']) && !in_array('token',$colnames))
				$query .= ",".db_quote_id('token');
			$query .=") ";
			$query .="VALUES (".implode(", ", $values);
			if ($thissurvey['datestamp'] == "Y")
			{
				$query .= ", '".$_SESSION['datestamp']."'";
				$query .= ", '".$_SESSION['datestamp']."'";
			}
			if ($thissurvey['ipaddr'] == "Y")
			{
				$query .= ", '".$_SERVER['REMOTE_ADDR']."'";
			}
			$query .= ", '".GetBaseLanguageFromSurveyID($surveyid)."'";
			if ($thissurvey['refurl'] == "Y")
			{
				$query .= ", '".$_SESSION['refurl']."'";
			}
			if ((isset($move) && $move == "movesubmit") && ($thissurvey['format'] != "A"))
			{
                // is if a ALL-IN-ONE survey, we don't set the submit date before the data is validated
				$query .= ", ".$connect->DBDate($mysubmitdate);
			}
			if (isset($_POST['token']) && !in_array('token',$colnames))
				$query .= ",'".$_POST['token']."'";
			$query .=")";
		}
		else
		{  // UPDATE EXISTING ROW
			// Updates only the MODIFIED fields posted on current page.
			if (isset($postedfieldnames) && $postedfieldnames)
			{
				$query = "UPDATE {$thissurvey['tablename']} SET ";
				if ($thissurvey['datestamp'] == "Y")
				{
					$query .= " datestamp = '".$_SESSION['datestamp']."',";
				}
				if ($thissurvey['ipaddr'] == "Y")
				{
					$query .= " ipaddr = '".$_SERVER['REMOTE_ADDR']."',";
				}
                // is if a ALL-IN-ONE survey, we don't set the submit date before the data is validated
				if ((isset($move) && $move == "movesubmit") && ($thissurvey['format'] != "A"))       
				{
                    $query .= " submitdate = ".$connect->DBDate($mysubmitdate).", ";
				}
				$fields=$postedfieldnames;
				$fields=array_unique($fields);
				foreach ($fields as $field)
				{
                      if(!empty($field))
                      {
                          $fieldinfo = arraySearchByKey($field, $fieldmap, "fieldname", 1);
                          if (!isset($_POST[$field])) {$_POST[$field]='';}
                          if ($_POST[$field]=='' && $fieldinfo['type']=='D')      
                          {                        
                              $query .= db_quote_id($field)." = NULL,";
                          }
                          else
                          {
                              $query .= db_quote_id($field)." = '".auto_escape($myFilter->process($_POST[$field]))."',";
                          }
                      }
				}
				$query .= "WHERE id=" . $_SESSION['srid'];
				$query = str_replace(",WHERE", " WHERE", $query);   // remove comma before WHERE clause
			}
			else
			{
				$query = "";
				if ((isset($move) && $move == "movesubmit"))
				{
					$query = "UPDATE {$thissurvey['tablename']} SET ";
                    $query .= " submitdate = ".$connect->DBDate($mysubmitdate);
					$query .= " WHERE id=" . $_SESSION['srid'];
				}
			}
		}
		//DEBUG START
		//echo $query;
		//DEBUG END
		return $query;
	}
	else
	{
		sendcacheheaders();
		doHeader();
		foreach(file("$thistpl/startpage.pstpl") as $op)
		{
			echo templatereplace($op);
		}
		echo "<br /><center><font face='verdana' size='2'><font color='red'><strong>".$clang->gT("Error")."</strong></font><br /><br />\n";
		echo $clang->gT("Cannot submit results - there are none to submit.")."<br /><br />\n";
		echo "<font size='1'>".$clang->gT("This error can occur if you have already submitted your responses and pressed 'refresh' on your browser. In this case, your responses have already been saved.")."<br /><br />".$clang->gT("If you receive this message in the middle of completing a survey, you should choose '<- BACK' on your browser and then refresh/reload the previous page. While you will lose answers from the last page all your others will still exist. This problem can occur if the webserver is suffering from overload or excessive use. We apologise for this problem.")."<br />\n";
		echo "</font></center><br /><br />";
		exit;
	}
}

// submitanswer sets the submitdate
// only used by question.php and group.php if next pages
// should not display due to conditions
// In this case all answers have already been updated by save.php
// but movesubmit status was only set after calling save.php
// ==> thus we need to update submitdate here.
function submitanswer()
{
	global $thissurvey,$timeadjust;
	global $surveyid, $connect, $clang;

	if ($thissurvey['private'] =="Y" && $thissurvey['datestamp'] =="N")
	{
		// In case of anonymous answers survey with no datestamp
		// then the the answer submutdate gets a conventional timestamp
		// 1st Jan 1980
		$mysubmitdate = date("Y-m-d H:i:s",mktime(0,0,0,1,1,1980));
	}
	else
	{
		$mysubmitdate = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust);      
	}

	$query = "";
	if (isset($move) && ($move == "movesubmit") && ($thissurvey['active'] == "Y"))
	{
		$query = "UPDATE {$thissurvey['tablename']} SET ";
		$query .= " submitdate = ".$connect->DBDate($mysubmitdate);
		$query .= " WHERE id=" . $_SESSION['srid'];
	}

	$result=$connect->Execute($query);    // Checked    
		return $result;
}

    function array_remval($val, &$arr)
    {
          $array_remval = $arr;
          for($x=0;$x<count($array_remval)-1;$x++)
          {
              $i=array_search($val,$array_remval);
              if($i===false)return false;
              $array_remval=array_merge(array_slice($array_remval, 0,$i), array_slice($array_remval, $i+1));
          }
          return $array_remval;
    }

?>
