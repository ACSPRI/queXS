<?php
/**
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
* $Id: survey.php 7433 2009-08-10 16:39:30Z c_schmitz $
*/

//Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB       

if (!isset($homedir) || isset($_REQUEST['$homedir'])) {die("Cannot run this script directly");}

//Move current step ###########################################################################
if (!isset($_SESSION['step'])) {$_SESSION['step'] = 0;}
if (!isset($_SESSION['totalsteps'])) {$_SESSION['totalsteps']=0;}
if (isset($move) && $move == "moveprev") {$_SESSION['step'] = $thisstep-1;}
if (isset($move) && $move == "movenext") {$_SESSION['step'] = $thisstep+1;}

// We do not keep the participant session anymore when the same browser is used to answer a second time a survey (let's think of a library PC for instance).
// Previously we used to keep the session and redirect the user to the
// submit page.
//if (isset($_SESSION['finished'])) {$move="movesubmit"; }


//CHECK IF ALL MANDATORY QUESTIONS HAVE BEEN ANSWERED ############################################
//CHECK IF ALL CONDITIONAL MANDATORY QUESTIONS THAT APPLY HAVE BEEN ANSWERED
$notanswered=addtoarray_single(checkmandatorys($move),checkconditionalmandatorys($move));

//CHECK PREGS
$notvalidated=checkpregs($move);

//CHECK QUOTA
if ($thissurvey['active'] == "Y")
{ 
    check_quota('enforce',$surveyid);
}

//SUBMIT
if ((isset($move) && $move == "movesubmit") && (!isset($notanswered) || !$notanswered) && (!isset($notvalidated) && !$notvalidated))
{
	if ($thissurvey['private'] == "Y")
	{
		$privacy = templatereplace(file_get_contents("$thistpl/privacy.pstpl"));
	}
	if ($thissurvey['refurl'] == "Y")
	{
		if (!in_array("refurl", $_SESSION['insertarray'])) //Only add this if it doesn't already exist
		{
			$_SESSION['insertarray'][] = "refurl";
		}
	}


	//COMMIT CHANGES TO DATABASE
	if ($thissurvey['active'] != "Y")
	{
        if ($thissurvey['assessments']== "Y") 
        {
            $assessments = doAssessment($surveyid); // assessments are using session data so this has to be placed before killSession
        }

        //Before doing the "templatereplace()" function, check the $thissurvey['url']
        //field for limereplace stuff, and do transformations!

        killSession();    
        sendcacheheaders();
		doHeader();
		echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));

		//Check for assessments

		if ($thissurvey['assessments']== "Y" && $assessments)
		{
			echo templatereplace(file_get_contents("$thistpl/assessment.pstpl"));
		}

        $completed = $thissurvey['surveyls_endtext'];    
        $completed .= "<br /><strong><font size='2' color='red'>".$clang->gT("Did Not Save")."</strong></font><br /><br />\n\n"
		. $clang->gT("Your survey responses have not been recorded. This survey is not yet active.")."<br /><br />\n";
		if ($thissurvey['printanswers'] == 'Y')
		{ 
			// ClearAll link is only relevant for survey with printanswers enabled
			// in other cases the session is cleared at submit time
			$completed .= "<a href='{$_SERVER['PHP_SELF']}?sid=$surveyid&amp;move=clearall'>".$clang->gT("Clear Responses")."</a><br /><br />\n";
		}
	}
	else //THE FOLLOWING DEALS WITH SUBMITTING ANSWERS AND COMPLETING AN ACTIVE SURVEY
	{
		if ($thissurvey['usecookie'] == "Y" && $tokensexist != 1) //don't use cookies if tokens are being used
		{
			$cookiename="PHPSID".returnglobal('sid')."STATUS";
			setcookie("$cookiename", "COMPLETE", time() + 31536000); //Cookie will expire in 365 days
		}

        //Before doing the "templatereplace()" function, check the $thissurvey['url']
        //field for limereplace stuff, and do transformations!
        $thissurvey['surveyls_url']=insertansReplace($thissurvey['surveyls_url']);
		$thissurvey['surveyls_url']=passthruReplace($thissurvey['surveyls_url'], $thissurvey);

		$content='';
			$content .= templatereplace(file_get_contents("$thistpl/startpage.pstpl"));

		//Check for assessments
		$assessments = doAssessment($surveyid);
		if ($assessments)
		{
			$content .= templatereplace(file_get_contents("$thistpl/assessment.pstpl"));
		}

        // Unsetting $postedfieldnames tells the createinsertquery() function only to set the sbumit date, nothing else
        unset($postedfieldnames);
        
        // only update submitdate if the user did not already visit the submit page
        if (!isset($_SESSION['finished']))
        {
            $subquery = createinsertquery();
            $connect->Execute($subquery);   // Checked
        }
        
        
        //Survey end text
        if (trim(strip_tags($thissurvey['surveyls_endtext']))=='')
        {
            $completed = "<br /><span class='success'>".$clang->gT("Thank you!")."</span><br /><br />\n\n"
                        . $clang->gT("Your survey responses have been recorded.")."<br /><br />\n";           
        }
        else
        {
            $completed = $thissurvey['surveyls_endtext'];
        }
        
        // Link to Print Answer Preview  **********
        if ($thissurvey['printanswers']=='Y')
        {
            $completed .= "<br /><br />"
            ."<a class='printlink' href='printanswers.php' target='_blank'>"
            .$clang->gT("Click here to print your answers.")
            ."</a><br />\n";
        }
        //*****************************************

        if ($thissurvey['publicstatistics']=='Y' && $thissurvey['printanswers']=='Y') {$completed .='<br />'.$clang->gT("or");}

         // Link to Public statistics  **********
         if ($thissurvey['publicstatistics']=='Y')
         {
            $completed .= "<br /><br />"
            ."<a class='publicstatisticslink' href='statistics_user.php?sid=$surveyid' target='_blank'>"
            .$clang->gT("View the statistics for this survey.")
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

		sendcacheheaders();
		if (!$embedded && isset($thissurvey['autoredirect']) && $thissurvey['autoredirect'] == "Y" && $thissurvey['surveyls_url'])
		{
			
            $url = insertansReplace($thissurvey['surveyls_url']);
            $url = passthruReplace($url, $thissurvey);
			$url=str_replace("{SAVEDID}",$saved_id, $url);			           // to activate the SAVEDID in the END URL
            $url=str_replace("{TOKEN}",$clienttoken, $url);          // to activate the TOKEN in the END URL
            $url=str_replace("{SID}", $surveyid, $url);              // to activate the SID in the END URL
            $url=str_replace("{LANG}", $clang->getlangcode(), $url); // to activate the LANG in the END URL

	//queXS Addition
	include_once("quexs.php");
	$quexs_url = get_start_interview_url();	
	$url = str_replace("{STARTINTERVIEWURL}", $quexs_url, $url);

	$end_url = get_end_interview_url();
	$url = str_replace("{ENDINTERVIEWURL}", $end_url, $url);

			//Automatically redirect the page to the "url" setting for the survey
			session_write_close();

			header("Location: {$url}");
		}

		//if($thissurvey['printanswers'] != 'Y' && $thissurvey['usecookie'] != 'Y' && $tokensexist !=1)
		if($thissurvey['printanswers'] != 'Y')
		{
			killSession();
		}
		doHeader();
		echo $content;

	}

	echo templatereplace(file_get_contents("$thistpl/completed.pstpl"));

	echo "\n<br />\n";
	echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
	doFooter();
	exit;
}


//SEE IF $surveyid EXISTS
if ($surveyexists <1)
{
	sendcacheheaders();
	doHeader();
	//SURVEY DOES NOT EXIST. POLITELY EXIT.
	echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
	echo "\t<center><br />\n"
	."\t".$clang->gT("Sorry. There is no matching survey.")."<br /></center>&nbsp;\n";
	echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
	doFooter();
	exit;
}

//RUN THIS IF THIS IS THE FIRST TIME
if ((!isset($_SESSION['step']) || !$_SESSION['step'] || !isset($totalquestions))  && (!isset($notanswered) || !$notanswered) && (!isset($notvalidated) && !$notvalidated))
	{
	$totalquestions = buildsurveysession();
	$_SESSION['step'] = 1;
	}

// If the survey uses answer persistence and a srid is registered in SESSION
// then loadanswers from this srid
if ($thissurvey['tokenanswerspersistence'] == 'Y' &&
	$thissurvey['private'] == "N" &&
	isset($_SESSION['srid']) &&
	$thissurvey['active'] == "Y")
{
	loadanswers();
}

//******************************************************************************************************
//PRESENT SURVEY
//******************************************************************************************************

//GET GROUP DETAILS
require_once("qanda.php");
$mandatorys=array();
$mandatoryfns=array();
$conmandatorys=array();
$conmandatoryfns=array();
$conditions=array();
$inputnames=array();
foreach ($_SESSION['grouplist'] as $gl)
{
	$gid=$gl[0];
	foreach ($_SESSION['fieldarray'] as $ia)
	{
		if ($ia[5] == $gid)
		{
			$qtypesarray[$ia[1]] = $ia[4];
			list($plus_qanda, $plus_inputnames)=retrieveAnswers($ia);
			if ($plus_qanda)
			{
				$plus_qanda[] = $ia[4];
				$plus_qanda[] = $ia[6]; // adds madatory identifyer for adding mandatory class to question wrapping div
				$qanda[]=$plus_qanda;
			}
			if ($plus_inputnames)
			{
				$inputnames = addtoarray_single($inputnames, $plus_inputnames);
			}

			//Display the "mandatory" popup if necessary
			if (isset($notanswered))
			{
				list($mandatorypopup, $popup)=mandatory_popup($ia, $notanswered);
			}

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
		}
	}
}

//READ TEMPLATES, INSERT DATA AND PRESENT PAGE
sendcacheheaders();
doHeader();
if(isset($popup)) {echo $popup;}
if(isset($vpopup)) {echo $vpopup;}
echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"));
echo "\n<form method='post' action='{$_SERVER['PHP_SELF']}' id='limesurvey' name='limesurvey'>\n";
//PUT LIST OF FIELDS INTO HIDDEN FORM ELEMENT
echo "\n<!-- INPUT NAMES -->\n"
    ."\t<input type='hidden' name='fieldnames' id='fieldnames' value='"
    .implode("|", $inputnames)
    ."' />\n";

// <-- END FEATURE - SAVE

echo templatereplace(file_get_contents("$thistpl/welcome.pstpl"))."\n";

if ($thissurvey['private'] == "Y")
{
	echo templatereplace(file_get_contents("$thistpl/privacy.pstpl"))."\n";
}

print <<<END

<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->
<script type='text/javascript'>
<!--

END;
// Find out if there are any array_filter questions in this group
$array_filterqs = getArrayFiltersForGroup($surveyid, "");
// Put in the radio button reset javascript for the array filter unselect
if (isset($array_filterqs) && is_array($array_filterqs)) {
	print <<<END

		function radio_unselect(radioObj)
		{
			var radioLength = radioObj.length;
			for(var i = 0; i < radioLength; i++)
			{
				radioObj[i].checked = false;
			}
		}

END;

}

print <<<END
	function checkconditions(value, name, type)
	{
    
END;

// initialize the variables used to check if we should hide groupdescr
// for groups having all questions hidden by conditions
$aUniqGroupIds=array();
foreach ($conditions as $cd)
{
	$aUniqGroupIds[]=$cd[8];
}
$aUniqGroupIds=array_unique($aUniqGroupIds);
foreach ($aUniqGroupIds as $agroupid)
{
	// in fact this is the count of questions that are displayed and have conditions set
	// if there is at least one non conditional question, then hide/show will just do nothing
	echo "\tcountDisplayedConditionnalQuestionsInGroup_{$agroupid}=0;\n";	
}

// If there are conditions or arrray_filter questions then include the appropriate Javascript
if ((isset($conditions) && is_array($conditions)) || 
    (isset($array_filterqs) && is_array($array_filterqs)))
{
	if (!isset($endzone)) 
	{
	    $endzone="";
	}
	
print <<<END
        if (type == 'radio' || type == 'select-one')
        {
            var hiddenformname='java'+name;
            document.getElementById(hiddenformname).value=value;
        }

        if (type == 'checkbox')
        {
            var hiddenformname='java'+name;
            var chkname='answer'+name;
            if (document.getElementById(chkname).checked)
            {
                document.getElementById(hiddenformname).value='Y';
            } else
            {
		        document.getElementById(hiddenformname).value='';
            }
        }
END;
	$java="";
	$cqcount=1;

    /* $conditions element structure
	* $conditions element structure
	* $condition[n][0] => qid = question id
	* $condition[n][1] => cqid = question id of the target question, or 0 for TokenAttr leftOperand
	* $condition[n][2] => field name of element [1] (Except for type M or P)
    * $condition[n][3] => value to be evaluated on answers labeled. 
    * $condition[n][4] => type of question
	* $condition[n][5] => SGQ code of element [1] (sub-part of [2])
	* $condition[n][6] => method used to evaluate
    * $condition[n][7] => scenario *NEW BY R.L.J. van den Burg*
    * $condition[n][8] => group id of the question having the condition set ($condition[n][0])
    */
	
	foreach ($conditions as $cd)
	{
	  if (trim($cd[6])=='') {$cd[6]='==';} 
		if ((isset($oldq) && $oldq != $cd[0]) || !isset($oldq)) //New if statement
		{
			$java .= $endzone;
			$endzone = "";
			$cqcount=1;
      $java .= "\n   if (((";
    }

    if (!isset($oldcq) || !$oldcq)
    {
        $oldcq = $cd[2];
	}

		$idname=retrieveJSidname($cd);

		// Addition of "scenario" by Ron L.J. van den Burg.
		// Different scenario's are or-ed; within 1 scenario, conditions are and-ed.
		if ($cqcount > 1 && isset($oldscenario) && $oldscenario != $cd[7]) // We have an new scenario, so "or" the scenario.
		{
			$java .= ")) || ((";
		}
		else
    {
			if ($cqcount > 1 && $oldcq ==$cd[2])
			{
				$java .= " || ";
    }
			elseif ($cqcount >1 && $oldcq != $cd[2])
    {
				$java .= ") && (";
    }
		}
		$oldscenario = $cd[7];

		// Source of condition can be a token attr
		// Thus local evaluation of the condition may be possible
		// Let's try localEval (php), 
		// else set $JSsourceElt and $JSsourceValue and use them in later Javascript evals
		unset($localEvaluationPossible);
		unset($localEvaluation);
		unset($JSsourceElt);
		unset($JSsourceVal);
		if ($thissurvey['private'] == "N" && preg_match('/^{TOKEN:([^}]*)}$/', $cd[2], $sourceconditiontokenattr))
		{ // source is a token attr 
			// first check if token is readable in session
			if ( isset($_SESSION['token']) &&
					in_array(strtolower($sourceconditiontokenattr[1]),GetTokenConditionsFieldNames($surveyid)))
    {
				$tokenAttrSourceValue=GetAttributeValue($surveyid,strtolower($sourceconditiontokenattr[1]),$_SESSION['token']);
				// local evaluation avoids transmitting
				// the comparison values to the client in Javascript
				// It is possible if target value is not an @SGQA@ answer
				if (preg_match('/^@([0-9]+X[0-9]+X[^@]+)@/', $cd[3], $comparedfieldname))
				{ // the comparison right operand is a previous answer
					$localEvaluationPossible =false;
					$JSsourceElt = "document"; // let's use an always existing Elt
					$JSsourceVal = "'".javascript_escape($tokenAttrSourceValue)."'";

    }
				else
				{ // local eval possible
					$localEvaluationPossible = true;

					if ($cd[6] == 'RX')
					{ // the comparison right operand is a RegExp
						if (preg_match('/'.trim($cd[3]).'/',trim($tokenAttrSourceValue)))
    {
							$localEvaluation = 'true';
    }
    else
    {
							$localEvaluation = 'false';
    }
					}
					elseif (preg_match('/^{TOKEN:([^}]*)}$/', $cd[3], $comparedtokenattr))
					{ // the comparison right operand is a Token Attribute
						if ( isset($_SESSION['token']) &&
								in_array(strtolower($comparedtokenattr[1]),GetTokenConditionsFieldNames($surveyid)))
    {
							$comparedtokenattrValue = GetAttributeValue($surveyid,strtolower($comparedtokenattr[1]),$_SESSION['token']);
							if (eval('if (trim($tokenAttrSourceValue) '.$cd[6].' trim($comparedtokenattrValue)) return true; else return false;'))
							{
								$localEvaluation = 'true';
								//$localEvaluation = "'tokenmatch' == 'tokenmatch'";
    }
    else
    {
								$localEvaluation = 'false';
							}
						}
						else
	    {
							$localEvaluation = 'false';
	    }
					}
					else
					{ // the comparison right operand is a constant
						if (eval('if (trim($tokenAttrSourceValue) '.$cd[6].' trim($cd[3])) return true; else return false;'))
	    {
							$localEvaluation = 'true';
	    }
						else	
						{
							$localEvaluation = 'false';
    }
					}
				}
			}
			else
			{ // token not available, let's evaluate as false
				$localEvaluationPossible = true;
				$localEvaluation = 'false';
			}

		}
		else
		{ // this is a normal answer based condition
		  // the source HTML element and value are defined for future use
			$localEvaluationPossible = false;
			unset($localEvaluation);
			$JSsourceElt = "document.getElementById('$idname')";
			$JSsourceVal = "document.getElementById('$idname').value";
		}

		if ($localEvaluationPossible == true && isset($localEvaluation))
    {
			$java .= "$localEvaluation";
    }
		else
		{
			if ($cd[3] == '' || $cd[3] == ' ')
			{ // empty == no answer is a specific case and must be evaluated differently
				$java .= "$JSsourceElt != null && ( $JSsourceVal $cd[6] ' ' || !$JSsourceVal )";
			}
	elseif($cd[4] == "M" || $cd[4] == "P")
			{ // Type M and P questions are processed specifically
		$java .= "document.getElementById('$idname') != undefined && document.getElementById('$idname').value $cd[6] 'Y'";
	}
	else
	{
      // NEW
				// If the value is enclosed by @
      // the value of this question must be evaluated instead.
      if (preg_match('/^@([0-9]+X[0-9]+X[^@]+)@/', $cd[3], $comparedfieldname))
				{ // when the right operand is the answer of a previous question
		$sgq_from_sgqa=$_SESSION['fieldnamesInfo'][$comparedfieldname[1]];
					preg_match('/^([0-9]+)X([0-9]+)X([0-9]+)$/',$sgq_from_sgqa,$qidMatched);
					$qid_from_sgq=$qidMatched[3];
		$q2type=$qtypesarray[$sgq_from_sgqa];
					$idname2 = retrieveJSidname(Array('',$qid_from_sgq,$comparedfieldname[1],'Y',$q2type,$sgq_from_sgqa));
					$cqidattributes = getQuestionAttributes($cd[1]);

					if (in_array($cd[4],array("A","B","K","N","5",":")) || (in_array($cd[4],array("Q",";")) && arraySearchByKey('numbers_only', $cqidattributes, 'attribute', 1)))
					{ // Numerical questions

						$java .= "$JSsourceElt != null && document.getElementById('".$idname2."') !=null && parseFloat($JSsourceVal) $cd[6] parseFloat(document.getElementById('".$idname2."').value)";
      }
      else
      {
						$java .= "$JSsourceElt != null && document.getElementById('".$idname2."') !=null && $JSsourceVal $cd[6] document.getElementById('".$idname2."').value";
	    }

				}
				elseif ($thissurvey['private'] == "N" && preg_match('/^{TOKEN:([^}]*)}$/', $cd[3], $comparedtokenattr))
				{ 
					if ( isset($_SESSION['token']) &&
							in_array(strtolower($comparedtokenattr[1]),GetTokenConditionsFieldNames($surveyid)))
					{
						$comparedtokenattrValue = GetAttributeValue($surveyid,strtolower($comparedtokenattr[1]),$_SESSION['token']);
		if (in_array($cd[4],array("A","B","K","N","5",":")) || (in_array($cd[4],array("Q",";")) && arraySearchByKey('numbers_only', $cqidattributes, 'attribute', 1)))
		{ // Numerical questions
							$java .= "$JSsourceElt != null && parseFloat($JSsourceVal) $cd[6] parseFloat('".javascript_escape($comparedtokenattrValue)."')";
		}
		else
		{
							$java .= "$JSsourceElt != null && $JSsourceVal $cd[6] '".javascript_escape($comparedtokenattrValue)."'";
		}
      }
      else
      {
						$java .= " 'impossible to evaluate tokenAttr' == 'tokenAttr'";
					}
				}
				else
				{
        if ($cd[6] == 'RX')
        {
						$java .= "$JSsourceElt != null  && match_regex($JSsourceVal,'$cd[3]')";
        }
        else
        {
		$cqidattributes = getQuestionAttributes($cd[1]);
		if (in_array($cd[4],array("A","B","K","N","5",":"))  || (in_array($cd[4],array("Q",";")) && arraySearchByKey('numbers_only', $cqidattributes, 'attribute', 1)))
		{ // Numerical questions
							$java .= "$JSsourceElt != null && parseFloat($JSsourceVal) $cd[6] parseFloat('$cd[3]')";
		}
		else
		{
							$java .= "$JSsourceElt != null && $JSsourceVal $cd[6] '$cd[3]'";
        }
      }
    }
    }
		}

		if ((isset($oldq) && $oldq != $cd[0]) || !isset($oldq))//Close if statement
		{
			$endzone = ")))\n"
      . "    {\n"
      . "    document.getElementById('question$cd[0]').style.display='';\n"
      . "    document.getElementById('display$cd[0]').value='on';\n"
      . "    countDisplayedConditionnalQuestionsInGroup_".$cd[8]."++;\n"
      . "    }\n"
      . "   else\n"
      . "    {\n"
      . "    document.getElementById('question$cd[0]').style.display='none';\n"
      . "    document.getElementById('display$cd[0]').value='';\n"
      . "    }\n";
			$cqcount++;
		}
		$oldq = $cd[0]; //Update oldq for next loop
		$oldcq = $cd[2];  //Update oldcq for next loop
	}
	$java .= $endzone;
}


if (isset($array_filterqs) && is_array($array_filterqs))
{
	if (!isset($appendj)) {$appendj="";}

	foreach ($array_filterqs as $attralist)
	{
		//die(print_r($attralist));
		$qbase = $surveyid."X".$attralist['gid']."X".$attralist['qid'];
		$qfbase = $surveyid."X".$attralist['gid2']."X".$attralist['fid'];
		if ($attralist['type'] == "M")
		{
			$qquery = "SELECT code FROM {$dbprefix}answers WHERE qid='".$attralist['qid']."' AND language='".$_SESSION['s_lang']."' order by code;";
			$qresult = db_execute_assoc($qquery); //Checked
			while ($fansrows = $qresult->FetchRow())
			{
				$fquestans = "java".$qfbase.$fansrows['code'];
				$tbody = "javatbd".$qbase.$fansrows['code'];
				$dtbody = "tbdisp".$qbase.$fansrows['code'];
				$tbodyae = $qbase.$fansrows['code'];
				$appendj .= "\n";
                $appendj .= "\tif ((document.getElementById('$fquestans') != undefined && document.getElementById('$fquestans').value == 'Y'))\n";
				$appendj .= "\t{\n";
				$appendj .= "\t\tdocument.getElementById('$tbody').style.display='';\n";
				$appendj .= "\t\tdocument.getElementById('$dtbody').value='on';\n";
				$appendj .= "\t}\n";
				$appendj .= "\telse\n";
				$appendj .= "\t{\n";
				$appendj .= "\t\tdocument.getElementById('$tbody').style.display='none';\n";
				$appendj .= "\t\tdocument.getElementById('$dtbody').value='off';\n";
				$appendj .= "\t\tradio_unselect(document.forms['limesurvey'].elements['$tbodyae']);\n";
				$appendj .= "\t}\n";
			}
		}
	}
	$java .= $appendj;
}

if (isset($java)) {echo $java;}
foreach ($aUniqGroupIds as $agroupid)
{
	echo "\t\tshow_hide_group_{$agroupid}();\n";	
}
echo "\t}\n"
."\t//-->\n"
."\t</script>\n\n"; // End checkconditions javascript function

//Display the "mandatory" message on page if necessary
if (isset($showpopups) && $showpopups == 0 && isset($notanswered) && $notanswered == true)
{
	echo "<p><span class='errormandatory'>" . $clang->gT("One or more mandatory questions have not been answered. You cannot proceed until these have been completed.") . "</span></p>";
}

//Display the "validation" message on page if necessary
if (isset($showpopups) && $showpopups == 0 && isset($notvalidated) && $notvalidated == true)
{
	echo "<p><span class='errormandatory'>" . $clang->gT("One or more questions have not been answered in a valid manner. You cannot proceed until these answers are valid.") . "</span></p>";
}

foreach ($_SESSION['grouplist'] as $gl)
{
	$gid=$gl[0];
	$groupname=$gl[1];
	$groupdescription=$gl[2];
	echo "\n\n<!-- START THE GROUP -->\n";
	echo "\n\n<div id='group-$gid'>\n";
	echo templatereplace(file_get_contents("$thistpl/startgroup.pstpl"));
	echo "\n";

	if ($groupdescription)
	{
		echo templatereplace(file_get_contents("$thistpl/groupdescription.pstpl"));
	}
	echo "\n";

	// count the number of non-conditionnal and conditionnal questions in this group
	$count_nocond_questions=0;
	$count_cond_questions=0;
	echo "\n\n<!-- PRESENT THE QUESTIONS -->\n";
	if (is_array($qanda))
	{
		foreach ($qanda as $qa)
		{
			if ($gl[0] == $qa[6])
			{
				$q_class = question_class($qa[8]); // render question class (see common.php)

				if ($qa[9] == 'Y')
				{
					$man_class = ' mandatory';
				}
				else
				{
					$man_class = '';
				}

				if ($qa[3] != 'Y')
				{
					$n_q_display = '';
					$count_nocond_questions++;
				} 
				else 
				{ 
					$n_q_display = ' style="display: none;"';
					$count_cond_questions++;
				}

				echo '
	<!-- NEW QUESTION -->
				<div id="question'.$qa[4].'" class="'.$q_class.$man_class.'"'.$n_q_display.'>';
				$question= $qa[0];
				$answer=$qa[1];
				$help=$qa[2];
				$questioncode=$qa[5];
				echo templatereplace(file_get_contents("$thistpl/question.pstpl"));
				echo "\t\t\t\t</div>\n";
			}
		}
	}

	echo "\n\n<!-- END THE GROUP -->\n";

	if ($hide_groupdescr_allinone == true && $count_nocond_questions==0)
	{
		$show_hide_group_script = "\n\n" 
			."<script type='text/javascript'>\n"
//			."\tcountDisplayedConditionnalQuestionsInGroup[$gid]=$count_cond_questions+$count_nocond_questions;\n"
			."\tfunction show_hide_group_$gid() {\n"
			."\t\tif (countDisplayedConditionnalQuestionsInGroup_$gid > 0) {\n"
			."\t\t\tdocument.getElementById('group-$gid').style.display='';\n"
			."\t\t} else {\n"
			."\t\t\tdocument.getElementById('group-$gid').style.display='none';\n"
			."\t\t}\n"
			."\t}\n"
			."</script>\n"
			."\n";
	}
	else
	{ 
		// The groupdescr will not be hidden
		// * either because the option to hide it when all conditional
		//   questions are hidden is not set
		// * or if at least one question of the group is not conditionnal
		$show_hide_group_script = "\n\n" 
			."<script type='text/javascript'>\n"
//			."\tcountDisplayedConditionnalQuestionsInGroup[$gid]=$count_cond_questions+$count_nocond_questions;\n"
			."\tfunction show_hide_group_$gid() {\n"
			."\t}\n"
			."</script>\n"
			."\n";
	}

	echo $show_hide_group_script;

	echo templatereplace(file_get_contents("$thistpl/endgroup.pstpl"));
	echo "\n\n</div>\n";
	echo "\n";
}
//echo "&nbsp;\n";
$navigator = surveymover();
echo "\n\n<!-- PRESENT THE NAVIGATOR -->\n";
echo templatereplace(file_get_contents("$thistpl/navigator.pstpl"));
echo "\n";

if ($thissurvey['active'] != "Y") {echo "\t\t<center><font color='red' size='2'>".$clang->gT("This survey is not currently active. You will not be able to save your responses.")."</font></center>\n";}


if (is_array($conditions) && count($conditions) != 0 ) 
{
	//if conditions exist, create hidden inputs for 'previously' answered questions
	// Note that due to move 'back' possibility, there may be answers from next pages
	// However we make sure that no answer from this page are inserted here
	foreach (array_keys($_SESSION) as $SESak)
	{
		if (in_array($SESak, $_SESSION['insertarray']) && !in_array($SESak, $inputnames))
		{
			echo "<input type='hidden' name='java$SESak' id='java$SESak' value='" . $_SESSION[$SESak] . "' />\n";
		}
	}
}
//SOME STUFF FOR MANDATORY QUESTIONS
if (remove_nulls_from_array($mandatorys))
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

echo "<input type='hidden' name='thisstep' value='{$_SESSION['step']}' id='thisstep' />\n"
."<input type='hidden' name='sid' value='$surveyid' id='sid' />\n"
."<input type='hidden' name='token' value='$token' id='token' />\n"
."</form>\n";
echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"));
echo "\n";
doFooter();

?>
