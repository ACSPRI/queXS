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
* $Id: question.php 5106 2008-06-18 16:24:37Z c_schmitz $
*/

//Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB       

if (!isset($homedir) || isset($_REQUEST['$homedir'])) {die("Cannot run this script directly");}

//Move current step
if (!isset($_SESSION['step'])) {$_SESSION['step']=0;}
if (!isset($_SESSION['totalsteps'])) {$_SESSION['totalsteps']=0;}
if (!isset($_POST['newgroupondisplay'])) {$_POST['newgroupondisplay'] = "";}
if (isset($move) && $move == "moveprev" && !$_POST['newgroupondisplay']) {$_SESSION['step'] = $thisstep-1;}
elseif (isset($move) && $move == "moveprev" && $_POST['newgroupondisplay'] == "Y") {$_SESSION['step'] = $thisstep;}
if (isset($move) && $move == "movenext") {$_SESSION['step'] = $thisstep+1;}

// This prevents the user from going back to the question pages and keeps him on the final page
// That way his session can be kept so he can still print his answers until he closes the browser
if (isset($_SESSION['finished'])) {$move="movesubmit"; }

//CHECK IF ALL MANDATORY QUESTIONS HAVE BEEN ANSWERED ############################################
//First, see if we are moving backwards or doing a Save so far, and its OK not to check:
if ($allowmandbackwards==1 && ((isset($move) &&  $move == "moveprev") || (isset($_POST['saveall']) && $_POST['saveall'] == $clang->gT("Save your responses so far"))))
{
	$backok="Y";
}
else
{
	$backok="N";
}

//Now, we check mandatory questions if necessary
//CHECK IF ALL CONDITIONAL MANDATORY QUESTIONS THAT APPLY HAVE BEEN ANSWERED
$notanswered=addtoarray_single(checkmandatorys($move,$backok),checkconditionalmandatorys($move,$backok));

//CHECK PREGS
$notvalidated=checkpregs($move,$backok);

//CHECK QUOTA
if ($thissurvey['active'] == "Y")
{ 
    check_quota('enforce',$surveyid);
}

//SEE IF $surveyid EXISTS ####################################################################
if ($surveyexists <1)
{
	sendcacheheaders();
	doHeader();
	//SURVEY DOES NOT EXIST. POLITELY EXIT.
	echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
	echo "\t<center><br />\n";
	echo "\t".$clang->gT("Sorry. There is no matching survey.")."<br /></center>&nbsp;\n";
	echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
	doFooter();
	exit;
}

//RUN THIS IF THIS IS THE FIRST TIME
if (!isset($_SESSION['step']) || !$_SESSION['step'])
{
	$totalquestions = buildsurveysession();
/*	sendcacheheaders();
	doHeader();

	echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
	echo "\n<form method='post' action='{$_SERVER['PHP_SELF']}' id='limesurvey' name='limesurvey'>\n";

	echo "\n\n<!-- START THE SURVEY -->\n";

	echo templatereplace(file_get_contents("$thistpl/welcome.pstpl"))."\n";
	if ($thissurvey['private'] == "Y")
	{
		echo templatereplace(file_get_contents("$thistpl/privacy.pstpl"))."\n";
	}
	$navigator = surveymover();
	echo templatereplace(file_get_contents("$thistpl/navigator.pstpl"));
	if ($thissurvey['active'] != "Y")
	{
		echo "\t\t<center><font color='red' size='2'>".$clang->gT("This survey is not currently active. You will not be able to save your responses.")."</font></center>\n";
	}
	echo "\n<input type='hidden' name='sid' value='$surveyid' id='sid' />\n";
	echo "\n<input type='hidden' name='token' value='$token' id='token' />\n";
	echo "\n<input type='hidden' name='lastgroupname' value='_WELCOME_SCREEN_' id='lastgroupname' />\n"; //This is to ensure consistency with mandatory checks, and new group test
	echo "\n</form>\n";
	echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
	doFooter();
	exit;*/
	$_SESSION['step'] = 1;
}

//******************************************************************************************************
//PRESENT SURVEY
//******************************************************************************************************

//GET GROUP DETAILS

if ($_SESSION['step'] == "0") {$currentquestion=$_SESSION['step'];}
else {$currentquestion=$_SESSION['step']-1;}

$ia=$_SESSION['fieldarray'][$currentquestion];

list($newgroup, $gid, $groupname, $groupdescription, $gl)=checkIfNewGroup($ia);

// MANAGE CONDITIONAL QUESTIONS
$conditionforthisquestion=$ia[7];
$questionsSkipped=0;
while ($conditionforthisquestion == "Y") //IF CONDITIONAL, CHECK IF CONDITIONS ARE MET
{
	$cquery="SELECT distinct cqid FROM {$dbprefix}conditions WHERE qid={$ia[0]}";
	$cresult=db_execute_assoc($cquery) or safe_die("Couldn't count cqids<br />$cquery<br />".$connect->ErrorMsg());  //Checked
	$cqidcount=$cresult->RecordCount();
	$cqidmatches=0;
	while ($crows=$cresult->FetchRow())//Go through each condition for this current question
	{
		//Check if the condition is multiple type
		$ccquery="SELECT type FROM {$dbprefix}questions WHERE qid={$crows['cqid']} AND language='".$_SESSION['s_lang']."' ";
		$ccresult=db_execute_assoc($ccquery) or safe_die ("Coudn't get type from questions<br />$ccquery<br />".$connect->ErrorMsg());   //Checked
		while($ccrows=$ccresult->FetchRow())
		{
			$thistype=$ccrows['type'];
		}
		// In case thistype = Q or K, then multiple conditions are ANDed
		// and thus  must match
		// ==> increase $cqidcount to the number of conditions
		// avoiding the 'distinct' keyword in the SQL above
		// (which is used for type M or P questions whose conditions
		//  are ORed)
		if ($thistype =="Q" || $thistype =="K")
		{
			$cquery2="SELECT cqid FROM {$dbprefix}conditions WHERE qid={$ia[0]} AND cqid={$crows['cqid']}";
			$cresult2=db_execute_assoc($cquery2) or safe_die("Couldn't count cqids<br />$cquery<br />".$connect->ErrorMsg()); //Checked
			$cqidcount2=$cresult2->RecordCount();
			$cqidcount += $cqidcount2 - 1; // substract 1 as it has been already counted once by $cquery
		}
		$cqquery = "SELECT cfieldname, value, cqid, method FROM {$dbprefix}conditions WHERE qid={$ia[0]} AND cqid={$crows['cqid']}";
		$cqresult = db_execute_assoc($cqquery) or safe_die("Couldn't get conditions for this question/cqid<br />$cquery<br />".$connect->ErrorMsg()); //Checked
		$amatchhasbeenfound="N";
		while ($cqrows=$cqresult->FetchRow()) //Check each condition
		{
			$currentcqid=$cqrows['cqid'];
			$conditionfieldname=$cqrows['cfieldname'];
			if (!$cqrows['value'] || $cqrows['value'] == ' ')
			{
				$conditionvalue="NULL";
			} 
			else
			{
				$conditionvalue=$cqrows['value'];
			}
			if ($thistype == "M" || $thistype == "P") //Adjust conditionfieldname for multiple option type questions
			{
				$conditionfieldname .= $conditionvalue;
				$conditionvalue = "Y";
			}
			// If condition value is @SIDXGIDXQID[aid]@ field
			if (ereg('^@([0-9]+X[0-9]+X[^@]+)@',$conditionvalue, $targetconditionfieldname))
			{
				$conditionvalue = $_SESSION[$targetconditionfieldname[1]];
			}
			if (trim($cqrows['method'])=='') {$cqrows['method']='==';}
			if (!isset($_SESSION[$conditionfieldname]) || 
				$_SESSION[$conditionfieldname] == '' || 
				$_SESSION[$conditionfieldname] == ' ')
			{
			    if($thistype == "K")
			    {
			      $currentvalue = 0;
			    } else {
				  $currentvalue="NULL";
				}
			} 
			else 
			{
				$currentvalue=$_SESSION[$conditionfieldname];
			}

			if ( $cqrows['method'] != 'RX')
			{
			
				if (eval('if ($currentvalue'. $cqrows['method'].'$conditionvalue) return true; else return false;'))
				{
					$amatchhasbeenfound="Y";
				}
			}
			else
			{
				if (ereg($conditionvalue,$currentvalue))
				{
					$amatchhasbeenfound="Y";
				}
			}
			if ( ($thistype =="Q" || $thistype =="K") &&
				$amatchhasbeenfound=="Y")
			{ 
				// For type Q/K questions each match is counted
				// because this is an AND condition
				$cqidmatches++;
				// then we reset matchfound switch in order
				// to check the next condition
				$amatchhasbeenfound="N";
			}
		}
		if ($amatchhasbeenfound == "Y" && 
			($thistype !="Q" && $thistype !="K") )
		{
			// For all other question type than Q and K, 
			// conditions on same Question are ORed (type M or P)
			// so increment counter at least one of the conditions matches
			$cqidmatches++;
		}
	}
	if ($cqidmatches == $cqidcount)
	{
		//a match has been found in ALL distinct cqids. The question WILL be displayed
		$conditionforthisquestion="N";
	}
	else
	{
		//matches have not been found in ALL distinct cqids. The question WILL NOT be displayed
		$questionsSkipped++;
		if (returnglobal('move') == "movenext")
		{
			$currentquestion++;
			if(isset($_SESSION['fieldarray'][$currentquestion]))
			{
				$ia=$_SESSION['fieldarray'][$currentquestion];
			}
            if ($_SESSION['step']>=$_SESSION['totalsteps']) 
            {
                $move="movesubmit"; 
		submitanswer(); // complete this answer (submitdate)
                break;       
            }
            $_SESSION['step']++;
			foreach ($_SESSION['grouplist'] as $gl)
			{
				if ($gl[0] == $ia[5])
				{
					$gid=$gl[0];
					$groupname=$gl[1];
					$groupdescription=$gl[2];
					if (auto_unescape($_POST['lastgroupname']) != strip_tags($groupname) && $groupdescription) {$newgroup = "Y";} else {$newgroup == "N";}
				}
			}
		}
		elseif (returnglobal('move') == "moveprev")
		{
			$currentquestion--;
			$ia=$_SESSION['fieldarray'][$currentquestion];
			$_SESSION['step']--;
		}
		$conditionforthisquestion=$ia[7];
	}
}

//SUBMIT
if ((isset($move) && $move == "movesubmit")  && (!isset($notanswered) || !$notanswered)  && (!isset($notvalidated) || !$notvalidated ))   
{
    if ($thissurvey['refurl'] == "Y")
    {
        if (!in_array("refurl", $_SESSION['insertarray'])) //Only add this if it doesn't already exist
        {
            $_SESSION['insertarray'][] = "refurl";
        }
//        $_SESSION['refurl'] = $_SESSION['refurl'];
    }


    //COMMIT CHANGES TO DATABASE
    if ($thissurvey['active'] != "Y")
    {
        sendcacheheaders();
        doHeader();
        echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));

        //Check for assessments
        $assessments = doAssessment($surveyid);
        if ($assessments)
        {
            echo templatereplace(file_get_contents("$thistpl/assessment.pstpl"));
        }

        $completed = "<br /><strong><font size='2' color='red'>".$clang->gT("Did Not Save")."</font></strong><br /><br />\n\n";
        $completed .= $clang->gT("Your survey responses have not been recorded. This survey is not yet active.")."<br /><br />\n";
        $completed .= "<a href='".$_SERVER['PHP_SELF']."?sid=$surveyid&amp;move=clearall'>".$clang->gT("Clear Responses")."</a><br /><br />\n";
    }
    else
    {


        if ($thissurvey['usecookie'] == "Y" && $tokensexist != 1) //don't use cookies if tokens are being used
        {
            $cookiename="PHPSID".returnglobal('sid')."STATUS";
            setcookie("$cookiename", "COMPLETE", time() + 31536000);
        }


        $content='';
        $content .= templatereplace(file_get_contents("$thistpl/startpage.pstpl"));

        //Check for assessments
        $assessments = doAssessment($surveyid);
        if ($assessments)
        {
            $content .= templatereplace(file_get_contents("$thistpl/assessment.pstpl"));
        }

        $completed = "<br /><font size='2'><font color='green'><strong>"
        .$clang->gT("Thank you")."</strong></font><br /><br />\n\n"
        .$clang->gT("Your survey responses have been recorded.")."<br />\n"
        ."<a href='javascript:window.close()'>"
        .$clang->gT("Close this Window")."</a></font><br /><br />\n";

        // Link to Print Answer Preview  **********
        if ($thissurvey['printanswers']=='Y')
        {
            $completed .= "<br /><br />"
            ."<a class='printlink' href='printanswers.php' target='_blank'>"
            .$clang->gT("Click here to print your answers.")
            ."</a><br />\n";
         }
        //*****************************************

        //Update the token if needed and send a confirmation email
        if (isset($clienttoken) && $clienttoken)
        {
            submittokens();
        }

        //Send notification to survey administrator //Thanks to Jeff Clement http://jclement.ca
        if ($thissurvey['sendnotification'] > 0 && $thissurvey['adminemail'])
        {
            sendsubmitnotification($thissurvey['sendnotification']);
        }

        $_SESSION['finished']=true;
        $_SESSION['sid']=$surveyid;

        if (isset($thissurvey['autoredirect']) && $thissurvey['autoredirect'] == "Y" && $thissurvey['url'])
        {
            //Automatically redirect the page to the "url" setting for the survey
            $url = $thissurvey['url'];
            $url=str_replace("{SAVEDID}",$saved_id, $url);    // to activate the SAVEDID in the END URL
            $url=str_replace("{TOKEN}",$clienttoken, $url);   // to activate the TOKEN in the END URL
            $url=str_replace("{SID}", $surveyid, $url);       // to activate the SID in the RND URL

            header("Location: {$url}");
        }

        doHeader();
        if (isset($content)) {echo $content;}

    }

    echo templatereplace(file_get_contents("$thistpl/completed.pstpl"));

    echo "\n<br />\n";
    echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
    doFooter();
	if($thissurvey['printanswers'] != 'Y' && $thissurvey['usecookie'] != 'Y' && $tokensexist !=1)
    {
      session_unset();
      session_destroy();
    }    
    exit;
}


if ($questionsSkipped == 0 && $newgroup == "Y" && isset($move) && $move == "moveprev" && (isset($_POST['grpdesc']) && $_POST['grpdesc']=="Y")) //a small trick to manage moving backwards from a group description
{
	//This does not work properly in all instances.
	$currentquestion++;
	$ia=$_SESSION['fieldarray'][$currentquestion];
	$_SESSION['step']++;
}

list($newgroup, $gid, $groupname, $groupdescription, $gl)=checkIfNewGroup($ia);

//Check if current page is for group description only
$bIsGroupDescrPage = false;
if ($newgroup == "Y" && $groupdescription && (isset($move) && $move != "moveprev"))
{
	// This is a group description page
	//  - $ia contains next question description, 
	//    but his question is not displayed, it is only used to know current group
	//  - in this case answers' inputnames mustn't be added to filednames hidden input
	$bIsGroupDescrPage = true;
}



require_once("qanda.php");
$mandatorys=array();
$mandatoryfns=array();
$conmandatorys=array();
$conmandatoryfns=array();
$conditions=array();
$inputnames=array();

list($plus_qanda, $plus_inputnames)=retrieveAnswers($ia);
if ($plus_qanda)
{
	$qanda[]=$plus_qanda;
}
if ($plus_inputnames && !$bIsGroupDescrPage)
{ 
	// Add answers' inputnames to $inputnames unless this is a group description page
	$inputnames = addtoarray_single($inputnames, $plus_inputnames);
}

//Display the "mandatory" popup if necessary
if (isset($notanswered) && $notanswered!=false)
{
	list($mandatorypopup, $popup)=mandatory_popup($ia, $notanswered);
}

//Display the "validation" popup if necessary
if (isset($notvalidated))
{
	list($validationpopup, $vpopup)=validation_popup($ia, $notvalidated);
}

//Get list of mandatory questions
list($plusman, $pluscon)=create_mandatorylist($ia);
if ($plusman !== null)
{
	list($plus_man, $plus_manfns)=$plusman;
	$mandatorys=addtoarray_single($mandatorys, $plus_man);
	$mandatoryfns=addtoarray_single($mandatoryfns, $plus_manfns);
}
if ($pluscon !== null)
{
	list($plus_conman, $plus_conmanfns)=$pluscon;
	$conmandatorys=addtoarray_single($conmandatorys, $plus_conman);
	$conmandatoryfns=addtoarray_single($conmandatoryfns, $plus_conmanfns);
}

//Build an array containing the conditions that apply for this page
$plus_conditions=retrieveConditionInfo($ia); //Returns false if no conditions
if ($plus_conditions)
{
	$conditions = addtoarray_single($conditions, $plus_conditions);
}
//------------------------END DEVELOPMENT OF QUESTION

$percentcomplete = makegraph($_SESSION['step'], $_SESSION['totalsteps']);

//READ TEMPLATES, INSERT DATA AND PRESENT PAGE
sendcacheheaders();
doHeader();

if (isset($popup)) {echo $popup;}
if (isset($vpopup)) {echo $vpopup;}

echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));

echo "\n<form method='post' action='{$_SERVER['PHP_SELF']}' id='limesurvey' name='limesurvey'>\n";

//PUT LIST OF FIELDS INTO HIDDEN FORM ELEMENT
echo "\n\n<!-- INPUT NAMES -->\n";
echo "\t<input type='hidden' name='fieldnames' value='";
echo implode("|", $inputnames);
echo "' id='fieldnames'  />\n";
echo "\n\n<!-- JAVASCRIPT FOR MODIFIED QUESTIONS -->\n";
echo "\t<script type='text/javascript'>\n";
echo "\t<!--\n";
echo "\t\t\t\tfunction ValidDate(oObject)\n";
echo "\t\t\t\t{// Regular expression used to check if date is in correct format\n";
echo "\t\t\t\t\tvar str_regexp = /[1-9][0-9]{3}-(0[1-9]|1[0-2])-([0-2][0-9]|3[0-1])/;\n";
echo "\t\t\t\t\tvar pattern = new RegExp(str_regexp);\n";
echo "\t\t\t\t\tif ((oObject.value.match(pattern)!=null))\n";
echo "\t\t\t\t\t{var date_array = oObject.value.split('-');\n";
echo "\t\t\t\t\t\tvar day = date_array[2];\n";
echo "\t\t\t\t\t\tvar month = date_array[1];\n";
echo "\t\t\t\t\t\tvar year = date_array[0];\n";
echo "\t\t\t\t\t\tstr_regexp = /1|3|5|7|8|10|12/;\n";
echo "\t\t\t\t\t\tpattern = new RegExp(str_regexp);\n";
echo "\t\t\t\t\t\tif ( day <= 31 && (month.match(pattern)!=null))\n";
echo "\t\t\t\t\t\t{ return true;\n";
echo "\t\t\t\t\t\t}\n";
echo "\t\t\t\t\t\tstr_regexp = /4|6|9|11/;\n";
echo "\t\t\t\t\t\tpattern = new RegExp(str_regexp);\n";
echo "\t\t\t\t\t\tif ( day <= 30 && (month.match(pattern)!=null))\n";
echo "\t\t\t\t\t\t{ return true;\n";
echo "\t\t\t\t\t\t}\n";
echo "\t\t\t\t\t\tif (day == 29 && month == 2 && (year % 4 == 0))\n";
echo "\t\t\t\t\t\t{ return true;\n";
echo "\t\t\t\t\t\t}\n";
echo "\t\t\t\t\t\tif (day <= 28 && month == 2)\n";
echo "\t\t\t\t\t\t{ return true;\n";
echo "\t\t\t\t\t\t}        \n";
echo "\t\t\t\t\t}\n";
echo "\t\t\t\t\twindow.alert('".$clang->gT("Date is not valid!")."');\n";
echo "\t\t\t\t\toObject.focus();\n";
echo "\t\t\t\t\toObject.select();\n";
echo "\t\t\t\t\treturn false;\n";
echo "\t\t\t\t}\n";
//echo "\t\t}\n";
echo "\t//-->\n";
echo "\t</script>\n\n";
// <-- END NEW FEATURE - SAVE

echo "\n\n<!-- START THE SURVEY -->\n";
echo templatereplace(file_get_contents("$thistpl/survey.pstpl"));

if ($bIsGroupDescrPage)
{
	$presentinggroupdescription = "yes";
	echo "\n\n<!-- START THE GROUP DESCRIPTION -->\n";
	echo "\t\t\t<input type='hidden' name='grpdesc' value='Y' id='grpdesc' />\n";
	echo templatereplace(file_get_contents("$thistpl/startgroup.pstpl"));
	echo "\n<br />\n";

	//if ($groupdescription)
	//{
		echo templatereplace(file_get_contents("$thistpl/groupdescription.pstpl"));
	//}
	echo "\n";

	echo "\n\n<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->\n";
	echo "\t<script type='text/javascript'>\n";
	echo "\t<!--\n";
	echo "\t\tfunction checkconditions(value, name, type)\n";
	echo "\t\t\t{\n";
	echo "\t\t\t}\n";
	echo "\t//-->\n";
	echo "\t</script>\n\n";
	echo "\n\n<!-- END THE GROUP -->\n";
	echo templatereplace(file_get_contents("$thistpl/endgroup.pstpl"));
	echo "\n";

	$_SESSION['step']--;
	echo "\t\t\t<input type='hidden' name='newgroupondisplay' value='Y' id='newgroupondisplay' />\n";
}
else
{
	echo "\n\n<!-- START THE GROUP -->\n";
//	foreach(file("$thistpl/startgroup.pstpl") as $op)
//	{
//		echo "\t".templatereplace($op);
//	}
	echo templatereplace(file_get_contents("$thistpl/startgroup.pstpl"));
	echo "\n";

	echo "\n\n<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->\n";
	echo "\t<script type='text/javascript'>\n";
	echo "\t<!--\n";
	echo "\t\tfunction checkconditions(value, name, type)\n";
	echo "\t\t\t{\n";
	echo "\t\t\t}\n";
	echo "\t//-->\n";
	echo "\t</script>\n\n";

	echo "\n\n<!-- PRESENT THE QUESTIONS -->\n";
	if (is_array($qanda))
	{
		foreach ($qanda as $qa)
		{
			echo "\n\t<!-- NEW QUESTION -->\n";
			echo "\n\t<!-- QUESTION TYPE ".$qa[5]."-->\n";
			echo "\t\t\t\t<div id='question$qa[4]'>";
			$question="<label for='$ia[7]'>" . $qa[0] . "</label>";
			$answer=$qa[1];
			$help=$qa[2];
			$questioncode=$qa[5];
			echo templatereplace(file_get_contents("$thistpl/question.pstpl"));
			echo "\t\t\t\t</div>\n";
		}
	}
	echo "\n\n<!-- END THE GROUP -->\n";
	echo templatereplace(file_get_contents("$thistpl/endgroup.pstpl"));
	echo "\n";
}

$navigator = surveymover();

echo "\n\n<!-- PRESENT THE NAVIGATOR -->\n";
echo templatereplace(file_get_contents("$thistpl/navigator.pstpl"));
echo "\n";

if ($thissurvey['active'] != "Y")
{
	echo "\t\t<center><font color='red' size='2'>".$clang->gT("This survey is not currently active. You will not be able to save your responses.")."</font></center>\n";
}

echo "\n";

if (isset($conditions) && is_array($conditions) && count($conditions) != 0)
{
	//if conditions exist, create hidden inputs for 'previously' answered questions
	// Note that due to move 'back' possibility, there may be answers from next pages
	// However we make sure that no answer from this page are inserted here
	foreach (array_keys($_SESSION) as $SESak)
	{
		if (in_array($SESak, $_SESSION['insertarray']) && !in_array($SESak, $inputnames))
		{
			echo "<input type='hidden' name='java$SESak' id='java$SESak' value='" . htmlspecialchars($_SESSION[$SESak],ENT_QUOTES) . "' />\n";
		}
	}
}


//SOME STUFF FOR MANDATORY QUESTIONS
if (remove_nulls_from_array($mandatorys) && $newgroup != "Y")
{
	$mandatory=implode("|", remove_nulls_from_array($mandatorys));
	echo "<input type='hidden' name='mandatory' value='$mandatory' id='mandatory' />\n";
}
if (remove_nulls_from_array($conmandatorys))
{
	$conmandatory=implode("|", remove_nulls_from_array($conmandatorys));
	echo "<input type='hidden' name='conmandatory' value='$conmandatory' id='conmandatory' />\n";
}
if (remove_nulls_from_array($mandatoryfns))
{
	$mandatoryfn=implode("|", remove_nulls_from_array($mandatoryfns));
	echo "<input type='hidden' name='mandatoryfn' value='$mandatoryfn' id='mandatoryfn' />\n";
}
if (remove_nulls_from_array($conmandatoryfns))
{
	$conmandatoryfn=implode("|", remove_nulls_from_array($conmandatoryfns));
	echo "<input type='hidden' name='conmandatoryfn' value='$conmandatoryfn' id='conmandatoryfn' />\n";
}

echo "<input type='hidden' name='thisstep' value='{$_SESSION['step']}' id='thisstep' />\n";
echo "<input type='hidden' name='sid' value='$surveyid' id='sid' />\n";
echo "<input type='hidden' name='token' value='$token' id='token' />\n";
echo "<input type='hidden' name='lastgroupname' value='".htmlspecialchars(strip_tags($groupname),ENT_QUOTES,'UTF-8')."' id='lastgroupname' />\n";
echo "</form>\n";
//foreach(file("$thistpl/endpage.pstpl") as $op)
//{
//	echo templatereplace($op);
//}
echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
doFooter();


function checkIfNewGroup($ia)
{
	foreach ($_SESSION['grouplist'] as $gl)
	{
		if ($gl[0] == $ia[5])
		{
			$gid=$gl[0];
			$groupname=$gl[1];
			$groupdescription=$gl[2];
			if (isset($_POST['lastgroupname']) && auto_unescape($_POST['lastgroupname']) != strip_tags($groupname) && $groupdescription)
			{
				$newgroup = "Y";
			}
			else
			{
				$newgroup = "N";
			}
			if (!isset($_POST['lastgroupname'])) {$newgroup="Y";}
		}
	}
	return array($newgroup, $gid, $groupname, $groupdescription, $gl);
}
?>
