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
* $Id: qanda.php 5095 2008-06-17 23:30:15Z jcleeland $
*/

// Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

if (!isset($homedir) || isset($_REQUEST['$homedir'])) {die("Cannot run this script directly");}

/*
* Let's explain what this strange $ia var means
*
* $ia[0] => question id
* $ia[1] => fieldname
* $ia[2] => title
* $ia[3] => question text
* $ia[4] => type --  text, radio, select, array, etc
* $ia[5] => group id
* $ia[6] => mandatory Y || N
* $ia[7] => conditions ??
*
* $conditions element structure
* $condition[n][0] => question id
* $condition[n][1] => question with value to evaluate
* $condition[n][2] => internal field name of element [1]
* $condition[n][3] => value to be evaluated on answers labeled. *NEW* tittle of questions to evaluate.
* $condition[n][4] => type of question
* $condition[n][5] => equal to [2], but concatenated in this time (why the same value 2 times?)
* $condition[n][6] => method used to evaluate *NEW*
*/
function retrieveConditionInfo($ia)
{
	//This function returns an array containing all related conditions
	//for a question - the array contains the fields from the conditions table
	global $dbprefix, $connect;

	if ($ia[7] == "Y")
	{ //DEVELOP CONDITIONS ARRAY FOR THIS QUESTION
		$cquery = "SELECT {$dbprefix}conditions.qid, "
		                ."{$dbprefix}conditions.cqid, "
		                ."{$dbprefix}conditions.cfieldname, "
		                ."{$dbprefix}conditions.value, "
		                ."{$dbprefix}questions.type, "
		                ."{$dbprefix}questions.sid, "
                		."{$dbprefix}questions.gid, "
                		."{$dbprefix}conditions.method "
		           ."FROM {$dbprefix}conditions, "
		                ."{$dbprefix}questions "
		          ."WHERE {$dbprefix}conditions.cqid={$dbprefix}questions.qid "
		            ."AND {$dbprefix}conditions.qid=$ia[0] "
		            ."AND {$dbprefix}questions.language='".$_SESSION['s_lang']."' "
        	   ."ORDER BY {$dbprefix}conditions.cqid, "
        	            ."{$dbprefix}conditions.cfieldname";
		$cresult = db_execute_assoc($cquery) or safe_die ("OOPS<br />$cquery<br />".$connect->ErrorMsg());     //Checked
		while ($crow = $cresult->FetchRow())
		{
			$conditions[] = array ($crow['qid'],
			                       $crow['cqid'],
			                       $crow['cfieldname'],
			                       $crow['value'],
			                       $crow['type'],
			                       $crow['sid']."X".$crow['gid']."X".$crow['cqid'],
			                       $crow['method']);
		}
		return $conditions;
	}
	else
	{
		return null;
	}
}

function create_mandatorylist($ia)
{
	//Checks current question and returns required mandatory arrays if required
	if ($ia[6] == "Y")
	{
		switch($ia[4])
		{
			case "R":
			$thismandatory=setman_ranking($ia);
			break;
			case "M":
			$thismandatory=setman_questionandcode($ia);
			break;
			case "J":
			case "P":
			case "Q":
			case "K":
			case "A":
			case "B":
			case "C":
			case "E":
			case "F":
			case "H":
			$thismandatory=setman_questionandcode($ia);
			break;
			case "1":
			$thismandatory=setman_questionandcode_multiscale($ia);
			break;
			case "X":
			//Do nothing - boilerplate questions CANNOT be mandatory
			break;
			default:
			$thismandatory=setman_normal($ia);
		}
		if ($ia[7] != "Y" && isset($thismandatory)) //Question is not conditional - addto mandatory arrays
		{
			$mandatory=$thismandatory;
		}
		if ($ia[7] == "Y" && isset($thismandatory)) //Question IS conditional - add to conmandatory arrays
		{
			$conmandatory=$thismandatory;
		}
	}

	if (isset($mandatory))
	{
		return array($mandatory, null);
	}
	elseif (isset($conmandatory))
	{
		return array(null, $conmandatory);
	}
	else
	{
		return array(null, null);
	}
}

function setman_normal($ia)
{
	$mandatorys[]=$ia[1];
	$mandatoryfns[]=$ia[1];
	return array($mandatorys, $mandatoryfns);
}

function setman_ranking($ia)
{
	global $dbprefix, $connect;
	$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$ia[0]} AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	$ansresult = $connect->Execute($ansquery);  //Checked
	$anscount = $ansresult->RecordCount();
	for ($i=1; $i<=$anscount; $i++)
	{
		$mandatorys[]=$ia[1].$i;
		$mandatoryfns[]=$ia[1];
	}
	return array($mandatorys, $mandatoryfns);
}

function setman_questionandcode($ia)
{
	global $dbprefix, $connect;
	$qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);     //Checked
	while ($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}
	$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$ia[0]} AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	$ansresult = db_execute_assoc($ansquery); //Checked
	while ($ansrow = $ansresult->FetchRow())
	{
		$mandatorys[]=$ia[1].$ansrow['code'];
		$mandatoryfns[]=$ia[1];
	}
	if ($other == "Y" and ($ia[4]=="!" or $ia[4]=="L" or $ia[4]=="M" or $ia[4]=="P"))
	{
		$mandatorys[]=$ia[1]."other";
		$mandatoryfns[]=$ia[1];
	}
	return array($mandatorys, $mandatoryfns);
}

function setman_questionandcode_multiscale($ia)
{
	global $dbprefix, $connect;
	$qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);   //Checked
	while ($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}
	$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$ia[0]} AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	$ansresult = db_execute_assoc($ansquery); //Checked

	$lquery = "SELECT q.qid FROM {$dbprefix}labels l, {$dbprefix}questions q WHERE l.lid = q.lid AND q.qid=".$ia[0]." AND l.language='".$_SESSION['s_lang']."' AND q.language='".$_SESSION['s_lang']."'";
	$labelsresult = db_execute_assoc($lquery);   //Checked
	$labelscount = $labelsresult->RowCount();
	
	$lquery1 = "SELECT q.qid FROM {$dbprefix}labels l, {$dbprefix}questions q WHERE l.lid = q.lid1 AND q.qid=".$ia[0]." AND l.language='".$_SESSION['s_lang']."' AND q.language='".$_SESSION['s_lang']."'";
	$labelsresult1 = db_execute_assoc($lquery1);   //Checked
	$labelscount1 = $labelsresult1->RowCount();

	while ($ansrow = $ansresult->FetchRow())
	{

		if ($labelscount > 0)
		{
				$mandatorys[]=$ia[1].$ansrow['code']."#0";
				$mandatoryfns[]=$ia[1];
		}
		else
		{
			$mandatorys[]=$ia[1].$ansrow['code'];
			$mandatoryfns[]=$ia[1];
		}
		// second label set
		if ($labelscount1 > 0)
		{
				$mandatorys[]=$ia[1].$ansrow['code']."#1";
				$mandatoryfns[]=$ia[1];
		}
		else
		{
			$mandatorys[]=$ia[1].$ansrow['code'];
			$mandatoryfns[]=$ia[1];
		}

	 	
	}
	if ($other == "Y" and ($ia[4]=="!" or $ia[4]=="L" or $ia[4]=="M" or $ia[4]=="P" or $ia[4]=="1"))
	{
		$mandatorys[]=$ia[1]."other";
		$mandatoryfns[]=$ia[1];
	}
	return array($mandatorys, $mandatoryfns);
}


function retrieveAnswers($ia, $notanswered=null, $notvalidated=null)
{
	//This function returns an array containing the "question/answer" html display
	//and a list of the question/answer fieldnames associated. It is called from
	//question.php, group.php or survey.php

	//globalise required config variables
	global $dbprefix, $shownoanswer, $clang; //These are from the config-defaults.php file
	//-----
	global $thissurvey, $gl; //These are set by index.php
	global $connect;

	//DISPLAY
	$display = $ia[7];

	//QUESTION NAME
	$name = $ia[0];

	$qtitle=$ia[3];
	//Replace INSERTANS statements with previously provided answers;
	while (strpos($qtitle, "{INSERTANS:") !== false)
	{
		$replace=substr($qtitle, strpos($qtitle, "{INSERTANS:"), strpos($qtitle, "}", strpos($qtitle, "{INSERTANS:"))-strpos($qtitle, "{INSERTANS:")+1);
		$replace2=substr($replace, 11, strpos($replace, "}", strpos($replace, "{INSERTANS:"))-11);
		$replace3=retrieve_Answer($replace2);
		$qtitle=str_replace($replace, $replace3, $qtitle);
	} //while

	//GET HELP
	$hquery="SELECT help FROM {$dbprefix}questions WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."'";
	$hresult=db_execute_num($hquery) or safe_die($connect->ErrorMsg());       //Checked
	$help="";
	while ($hrow=$hresult->FetchRow()) {$help=$hrow[0];}

	//A bit of housekeeping to stop PHP Notices
	$answer = "";
	if (!isset($_SESSION[$ia[1]])) {$_SESSION[$ia[1]] = "";}
	$qidattributes=getQuestionAttributes($ia[0]);
	//echo "<pre>";print_r($qidattributes);echo "</pre>";
	//Create the question/answer html
	switch ($ia[4])
	{
		case "X": //BOILERPLATE QUESTION
		$values=do_boilerplate($ia);
		break;
		case "5": //5 POINT CHOICE radio-buttons
		$values=do_5pointchoice($ia);
		break;
		case "D": //DATE
		$values=do_date($ia);
		break;
		case "Z": //LIST Flexible drop-down/radio-button list
		$values=do_list_flexible_radio($ia);
		if (!$displaycols=arraySearchByKey("hide_tip", $qidattributes, "attribute", 1))
		{
			$qtitle .= "<br />\n<font class = \"questionhelp\">"
			. $clang->gT("Choose one of the following answers")."</font>";
		}
		break;
		case "L": //LIST drop-down/radio-button list
		$values=do_list_radio($ia);
		if (!$displaycols=arraySearchByKey("hide_tip", $qidattributes, "attribute", 1))
		{
			$qtitle .= "<br />\n<font class = \"questionhelp\">"
			. $clang->gT("Choose one of the following answers")."</font>";
		}
		break;
		case "W": //List - dropdown
		$values=do_list_flexible_dropdown($ia);
		if (!$displaycols=arraySearchByKey("hide_tip", $qidattributes, "attribute", 1))
		{
			$qtitle .= "<br />\n<font class = \"questionhelp\">"
			. $clang->gT("Choose one of the following answers")."</font>";
		}
		break;
		case "!": //List - dropdown
		$values=do_list_dropdown($ia);
		if (!$displaycols=arraySearchByKey("hide_tip", $qidattributes, "attribute", 1))
		{
			$qtitle .= "<br />\n<font class = \"questionhelp\">"
			. $clang->gT("Choose one of the following answers")."</font>";
		}
		break;
		case "O": //LIST WITH COMMENT drop-down/radio-button list + textarea
		$values=do_listwithcomment($ia);
		if (count($values[1]) > 1 && !$displaycols=arraySearchByKey("hide_tip", $qidattributes, "attribute", 1))
		{
			$qtitle .= "<br />\n<font class = \"questionhelp\">"
			. $clang->gT("Choose one of the following answers")."</font>";
		}
		break;
		case "R": //RANKING STYLE
		$values=do_ranking($ia);
		break;
		case "M": //MULTIPLE OPTIONS checkbox
		$values=do_multiplechoice($ia);
		if (count($values[1]) > 1 && !$displaycols=arraySearchByKey("hide_tip", $qidattributes, "attribute", 1))
		{
			if (!$maxansw=arraySearchByKey("max_answers", $qidattributes, "attribute", 1))
			{
				$qtitle .= "<br />\n<font class = \"questionhelp\">"
				. $clang->gT("Check any that apply")."</font>";
			}
			else
			{
				$qtitle .= "<br />\n<font class = \"questionhelp\">"
				. $clang->gT("Check at most")." ".$maxansw['value']." ".$clang->gT("answers")."</font>";
			}
		}
		break;

		case "I": //Language Question
		$values=do_language($ia);
		if (count($values[1]) > 1)
		{
			$qtitle .= "<br />\n<font class = \"questionhelp\">"
			. $clang->gT("Choose your language")."</font>";
		}
		break;
		case "P": //MULTIPLE OPTIONS WITH COMMENTS checkbox + text
		$values=do_multiplechoice_withcomments($ia);
		if (count($values[1]) > 1 && !$displaycols=arraySearchByKey("hide_tip", $qidattributes, "attribute", 1))
		{
			if (!$maxansw=arraySearchByKey("max_answers", $qidattributes, "attribute", 1))
			{
				$qtitle .= "<br />\n<font class = \"questionhelp\">"
				. $clang->gT("Check any that apply")."</font>";
			}
			else
			{
				$qtitle .= "<br />\n<font class = \"questionhelp\">"
				. $clang->gT("Check at most")." ".$maxansw['value']." ".$clang->gT("answers")."</font>";
			}
		}
		break;
		case "Q": //MULTIPLE SHORT TEXT
		$values=do_multipleshorttext($ia);
		break;
		case "K": //MULTIPLE NUMERICAL QUESTION
		$values=do_multiplenumeric($ia);
		break;
		case "N": //NUMERICAL QUESTION TYPE
		$values=do_numerical($ia);
		break;
		case "S": //SHORT FREE TEXT
		$values=do_shortfreetext($ia);
		break;
		case "T": //LONG FREE TEXT
		$values=do_longfreetext($ia);
		break;
		case "U": //HUGE FREE TEXT
		$values=do_hugefreetext($ia);
		break;
		case "Y": //YES/NO radio-buttons
		$values=do_yesno($ia);
		break;
		case "G": //GENDER drop-down list
		$values=do_gender($ia);
		break;
		case "A": //ARRAY (5 POINT CHOICE) radio-buttons
		$values=do_array_5point($ia);
		break;
		case "B": //ARRAY (10 POINT CHOICE) radio-buttons
		$values=do_array_10point($ia);
		break;
		case "C": //ARRAY (YES/UNCERTAIN/NO) radio-buttons
		$values=do_array_yesnouncertain($ia);
		break;
		case "E": //ARRAY (Increase/Same/Decrease) radio-buttons
		$values=do_array_increasesamedecrease($ia);
		break;
		case "F": //ARRAY (Flexible) - Row Format
		$values=do_array_flexible($ia);
		break;
		case "H": //ARRAY (Flexible) - Column Format
		$values=do_array_flexiblecolumns($ia);
		break;
//		case "^": //SLIDER CONTROL
//		$values=do_slider($ia);
//		break;
		case "1": //Array (Flexible Labels) dual scale
		$values=do_array_flexible_dual($ia);
		break;
		
	} //End Switch

	if (isset($values)) //Break apart $values array returned from switch
	{
		//$answer is the html code to be printed
		//$inputnames is an array containing the names of each input field
		list($answer, $inputnames)=$values;
	}

	$answer .= "\n\t\t\t<input type='hidden' name='display$ia[1]' id='display$ia[0]' value='";
	if ($thissurvey['format'] == "S")
	{
		$answer .= "on"; //Ifthis is single format, then it must be showing. Needed for checking conditional mandatories
	}
	$answer .= "' />\n"; //for conditional mandatory questions

	if ($ia[6] == "Y")
	{
		$qtitle = '<span class=\'asterisk\'>'.$clang->gT('*').'</span>'.$qtitle;
	}
	//If this question is mandatory but wasn't answered in the last page
	//add a message HIGHLIGHTING the question
	$qtitle .= mandatory_message($ia);

	$qtitle .= validation_message($ia);

	$qanda=array($qtitle, $answer, $help, $display, $name, $ia[2], $gl[0], $ia[1]);
	//New Return
	return array($qanda, $inputnames);
}

function validation_message($ia)
{
	//This function checks to see if this question requires validation and
	//that validation has not been met.
	global $notvalidated, $dbprefix, $connect, $clang;
	$qtitle="";
	if (isset($notvalidated) && is_array($notvalidated)) //ADD WARNINGS TO QUESTIONS IF THEY ARE NOT VALID
	{
		global $validationpopup, $popup;
		if (in_array($ia[1], $notvalidated))
		{
			$help="";
			$helpselect="SELECT help\n"
			."FROM {$dbprefix}questions\n"
			."WHERE qid={$ia[0]} AND language='".$_SESSION['s_lang']."'";
			$helpresult=db_execute_assoc($helpselect) or safe_die("$helpselect<br />".$connect->ErrorMsg());     //Checked
			while ($helprow=$helpresult->FetchRow())
			{
				$help=" <font class = \"questionhelp\">(".$helprow['help'].")</font>";
			}
			$qtitle .= "<strong><br /><span class='errormandatory'>".$clang->gT("This question must be answered correctly")." $help</span></strong><br />\n";
		}
	}
	return $qtitle;
}

function mandatory_message($ia)
{
	//This function checks to see if this question is mandatory and
	//is being re-displayed because it wasn't answered. It returns
	global $notanswered, $clang, $dbprefix;
	$qtitle="";
	if (isset($notanswered) && is_array($notanswered)) //ADD WARNINGS TO QUESTIONS IF THEY WERE MANDATORY BUT NOT ANSWERED
	{
		global $mandatorypopup, $popup;
		if (in_array($ia[1], $notanswered))
		{
			$qtitle .= "<strong><br /><span class='errormandatory'>".$clang->gT("This question is mandatory").".";
			switch($ia[4])
			{
				case "A":
				case "B":
				case "C":
				case "Q":
				case "K":
				case "F":
				case "J":
				case "H":
				$qtitle .= "<br />\n".$clang->gT("Please complete all parts").".";
				break;
				case "1":
				$qtitle .= "<br />\n".$clang->gT("Please check the items").".";
				case "R":
				$qtitle .= "<br />\n".$clang->gT("Please rank all items").".";
				break;
				case "M":
				case "P":
				$qtitle .= " ".$clang->gT("Please check at least one item").".";
                $qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0];
                $qresult = db_execute_assoc($qquery);    //Checked
                $qrow = $qresult->FetchRow();
                if ($qrow['other']=='Y')
                {
                    $qtitle .= "<br />\n".$clang->gT("If you choose 'Other:' you must provide a description.");
                }
				break;
			} // end switch
			$qtitle .= "</span></strong><br />\n";
		}
	}
	return $qtitle;
}

function mandatory_popup($ia, $notanswered=null)
{
	//This sets the mandatory popup message to show if required
	//Called from question.php, group.php or survey.php
	if ($notanswered === null) {unset($notanswered);}
	if (isset($notanswered) && is_array($notanswered)) //ADD WARNINGS TO QUESTIONS IF THEY WERE MANDATORY BUT NOT ANSWERED
	{
		global $mandatorypopup, $popup, $clang;
		//POPUP WARNING
		if (!isset($mandatorypopup) && ($ia[4] == "T" || $ia[4] == "S" || $ia[4] == "U"))
		{
			$popup="<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("You cannot proceed until you enter some text for one or more questions.", "js")."\")\n //-->\n</script>\n";
			$mandatorypopup="Y";
		}else
		{
			$popup="<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("One or more mandatory questions have not been answered. You cannot proceed until these have been completed", "js")."\")\n //-->\n</script>\n";
			$mandatorypopup="Y";
		}
		return array($mandatorypopup, $popup);
	}
	else
	{
		return false;
	}
}

function validation_popup($ia, $notvalidated=null)
{   
	//This sets the validation popup message to show if required
	//Called from question.php, group.php or survey.php
	if ($notvalidated === null) {unset($notvalidated);}
	$qtitle="";
	if (isset($notvalidated) && is_array($notvalidated))  //ADD WARNINGS TO QUESTIONS IF THEY ARE NOT VALID
	{
		global $validationpopup, $vpopup, $clang;
		//POPUP WARNING
		if (!isset($validationpopup))
		{
			$vpopup = "<script type\"text/javascript\">\n<!--\n alert(\"".$clang->gT("One or more questions have not been answered in a valid manner. You cannot proceed until these answers are valid", "js")."\")\n //-->\n</script>\n";
			$validationpopup="Y";
		}
		return array($validationpopup, $vpopup);
	}
	else
	{
		return false;
	}
}
//QUESTION METHODS
function do_boilerplate($ia)
{
	$answer="";
	$inputnames[]="";
	return array($answer, $inputnames);
}

function do_5pointchoice($ia)
{
	global $shownoanswer, $clang;
	
	$answer="";
	for ($fp=1; $fp<=5; $fp++)
	{
		$answer .= "\t\t\t<input class='radio' type='radio' name='$ia[1]' id='answer$ia[1]$fp' value='$fp'";
		if ($_SESSION[$ia[1]] == $fp) {$answer .= " checked='checked'";}
		$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /><label for='answer$ia[1]$fp' class='answertext'>$fp</label>\n";
	}
	
	if ($ia[6] != "Y"  && $shownoanswer == 1) // Add "No Answer" option if question is not mandatory
	{
		$answer .= "\t\t\t<input class='radio' type='radio' name='$ia[1]' id='NoAnswer' value=''";
		if (!$_SESSION[$ia[1]])
		{
			$answer .= " checked='checked'";
		}
		$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /><label for='NoAnswer' class='answertext'>".$clang->gT("No answer")."</label>\n";

	}
	$answer .= "\t\t\t<input type='hidden' name='java$ia[1]' id='java$ia[1]' value='{$_SESSION[$ia[1]]}' />\n";
	$inputnames[]=$ia[1];
	return array($answer, $inputnames);
}

function do_date($ia)
{
	global $clang;
	$qidattributes=getQuestionAttributes($ia[0]);
	if (arraySearchByKey("dropdown_dates", $qidattributes, "attribute", 1)) {
	   if (!empty($_SESSION[$ia[1]]))
	   {
	     list($currentyear, $currentmonth, $currentdate) = explode("-", $_SESSION[$ia[1]]);
	   } else {
	    $currentdate=""; 
		$currentmonth=""; 
		$currentyear="";
	   }
       $answer = keycontroljs();
       $answer .= "\t\t\t<select id='day{$ia[1]}' onChange='dateUpdater(\"{$ia[1]}\");'>\n";
       $answer .= "\t\t\t\t<option value=''>".$clang->gT("Day")."</option>\n";
       for ($i=1; $i<=31; $i++) {
	      $answer .= "\t\t\t\t<option value='".sprintf("%02d", $i)."'";
		  if ($i == $currentdate) {$answer .= " selected";}
		  $answer .= ">".sprintf("%02d", $i)."</option>\n";
	   }
	   $answer .= "\t\t\t</select>\n";
       $answer .= "\t\t\t<select id='month{$ia[1]}' onChange='dateUpdater(\"{$ia[1]}\");'>\n";
       $answer .= "\t\t\t\t<option value=''>".$clang->gT("Month")."</option>\n";
       $montharray=array($clang->gT("Jan"), 
	                $clang->gT("Feb"), 
					$clang->gT("Mar"), 
					$clang->gT("Apr"), 
					$clang->gT("May"),
					$clang->gT("Jun"),
					$clang->gT("Jul"),
					$clang->gT("Aug"),
					$clang->gT("Sep"),
					$clang->gT("Oct"),
					$clang->gT("Nov"),
					$clang->gT("Dec")); 
       for ($i=1; $i<=12; $i++) {
	      $answer .= "\t\t\t\t<option";
		  if ($i == $currentmonth) {$answer .= " selected";}
		  $answer .= " value='".sprintf("%02d", $i)."'>".$montharray[$i-1]."</option>\n";
	   }
	   $answer .= "\t\t\t</select>\n";
       $answer .= "\t\t\t<select id='year{$ia[1]}' onChange='dateUpdater(\"{$ia[1]}\");'>\n";
       $answer .= "\t\t\t\t<option value=''>".$clang->gT("Year")."</option>\n";
       
       for ($i=date("Y"); $i>=(date("Y")-115); $i--) {
	      $answer .= "\t\t\t\t<option value='$i'";
	      if ($i == $currentyear) {$answer .= " selected";}
		  $answer .= ">$i</option>\n";
	   }
	   $answer .= "\t\t\t</select>\n";
       $answer .= "\t\t\t<input class='text' type='text' size='10' name='$ia[1]' style='display: none' "
        . "id='answer{$ia[1]}' value=\"".$_SESSION[$ia[1]]
        . "\" maxlength='10' onchange='checkconditions(this.value, this.name, this.type)'/>\n"
      	. "\t\t\t<table class='question'>\n"
      	. "\t\t\t\t<tr>\n"
      	. "\t\t\t\t\t<td>\n"
      	. "\t\t\t\t\t</font></td>\n"
      	. "\t\t\t\t</tr>\n"
      	. "\t\t\t</table>\n";
	   $answer .= "<input type='hidden' name='qattribute_answer[]' value='".$ia[1]."'>\n";
	   $answer .= "<input type='hidden' name='qattribute_answer".$ia[1]."'>\n";
	   $answer .= "<script type=\"text/javascript\">\n"
	            . "function dateUpdater(val) {\n"
	            . "  label='answer'+val;\n"
	            . "  yearlabel='year'+val;\n"
	            . "  monthlabel='month'+val;\n"
	            . "  daylabel='day'+val;\n"
	            . "  bob = eval('document.limesurvey.qattribute_answer".$ia[1]."');\n"
	            . "  document.getElementById(label).value=document.getElementById(yearlabel).value+'-'+document.getElementById(monthlabel).value+'-'+document.getElementById(daylabel).value;\n"
                . "  if(document.getElementById(yearlabel).value != '' && document.getElementById(monthlabel).value != '' && document.getElementById(daylabel).value != '')\n"
                . "  {\n"
                . "    ValidDate(document.getElementById(label));\n"
                . "    bob.value='';\n"
                . "  } else if (document.getElementById(yearlabel).value == '' && document.getElementById(monthlabel).value == '' && document.getElementById(daylabel).value == '') {\n"
                . "    bob.value='';\n"
				. "  } else {\n"
                . "    bob.value='".$clang->gT("Please complete all parts of the date")."';\n"
                . "  }\n"
	            . "}\n"
	            . "dateUpdater(\"{$ia[1]}\");\n"
	            . "</script>\n";

	} else {
       $answer = keycontroljs()
        . "\t\t\t<input class='text' type='text' size='10' name='$ia[1]' "
        . "id='answer{$ia[1]}' value=\"".$_SESSION[$ia[1]]
        . "\" maxlength='10' onkeypress=\"return goodchars(event,'0123456789-')\" onchange='checkconditions(this.value, this.name, this.type)' onBlur='ValidDate(this)'/><button type='reset' id='f_trigger_{$ia[1]}'>...</button>\n"
      	. "\t\t\t<table class='question'>\n"
      	. "\t\t\t\t<tr>\n"
      	. "\t\t\t\t\t<td>\n"
      	. "\t\t\t\t\t\t<font size='1'>".$clang->gT("Format: YYYY-MM-DD")."<br />\n"
      	. "\t\t\t\t\t\t".$clang->gT("(eg: 2003-12-25 for Christmas day)")."\n"
      	. "\t\t\t\t\t</font></td>\n"
      	. "\t\t\t\t</tr>\n"
      	. "\t\t\t</table>\n";
      	// Here we do setup the date javascript
      	$answer .= "<script type=\"text/javascript\">\n"
     	. "Calendar.setup({\n"
      	. "inputField     :    \"answer{$ia[1]}\",\n"    // id of the input field
      	. "ifFormat       :    \"%Y-%m-%d\",\n"   // format of the input field
      	. "showsTime      :    false,\n"                    // will display a time selector
      	. "button         :    \"f_trigger_{$ia[1]}\",\n"         // trigger for the calendar (button ID)
      	. "singleClick    :    true,\n"                   // double-click mode
      	. "step           :    1\n"                        // show all years in drop-down boxes (instead of every other year as default)
      	. "});\n"
      	. "</script>\n";
	}
	$inputnames[]=$ia[1];

	return array($answer, $inputnames);
}


function do_language($ia)
{
	global $dbprefix, $surveyid, $clang;
	$answerlangs = GetAdditionalLanguagesFromSurveyID($surveyid);
	$answerlangs [] = GetBaseLanguageFromSurveyID($surveyid);
	$answer = "\n\t\t\t\t\t<select name='$ia[1]' id='answer$ia[1]' onchange='document.getElementById(\"lang\").value=this.value; checkconditions(this.value, this.name, this.type);'>\n";
	if (!$_SESSION[$ia[1]]) {$answer .= "\t\t\t\t\t\t<option value='' selected='selected'>".$clang->gT("Please choose")."..</option>\n";}
	foreach ($answerlangs as $ansrow)
	{
		$answer .= "\t\t\t\t\t\t<option value='{$ansrow}'";
		if ($_SESSION[$ia[1]] == $ansrow)
		{
			$answer .= " selected='selected'";
		}
		elseif ($ansrow['default_value'] == "Y")
		{
			$answer .= " selected='selected'"; 
		 	$defexists = "Y";
		}
		$answer .= ">".getLanguageNameFromCode($ansrow, true)."</option>\n";
	}
	$answer .= "\t\t\t\t\t</select>\n";
	$answer .= "\t\t\t\t\t<input type='hidden' name='java$ia[1]' id='java$ia[1]' value='{$_SESSION[$ia[1]]}' />\n";

	$inputnames[]=$ia[1];
    $answer .= "\n\t\t\t<input type='hidden' name='lang' id='lang' value='' />";
		
	return array($answer, $inputnames);
}


function do_list_dropdown($ia)
{
	global $dbprefix,  $dropdownthreshold, $lwcdropdowns, $connect;
	global $shownoanswer, $clang;
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($othertexts=arraySearchByKey("other_replace_text", $qidattributes, "attribute", 1))
	{
		$othertext=$othertexts['value'];
	}
	else
	{
		$othertext=$clang->gT("Other");
	}
	$answer="";
	if (isset($defexists)) {unset ($defexists);}
	$query = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."' ";
	$result = db_execute_assoc($query);      //Checked
	while($row = $result->FetchRow()) {$other = $row['other'];}
	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery) or safe_die("Couldn't get answers<br />$ansquery<br />".$connect->ErrorMsg());    //Checked
	while ($ansrow = $ansresult->FetchRow())
	{
		$answer .= "\t\t\t\t\t\t<option value='{$ansrow['code']}'";
		if ($_SESSION[$ia[1]] == $ansrow['code'])
		{
			$answer .= " selected='selected'";
		}
		elseif ($ansrow['default_value'] == "Y")
		{
			$answer .= " selected='selected'"; 
			$defexists = "Y";
		}
		$answer .= ">{$ansrow['answer']}</option>\n";
	}
	if (!$_SESSION[$ia[1]] && (!isset($defexists) || !$defexists))
	{
		$answer = "\t\t\t\t\t\t<option value='' selected='selected'>".$clang->gT("Please choose")."..</option>\n".$answer;
	}
	if (isset($other) && $other=="Y")
	{
		$answer .= "\t\t\t\t\t\t<option value='-oth-'";
		if ($_SESSION[$ia[1]] == "-oth-")
		{
			$answer .= " selected='selected'";
		}
		$answer .= ">".$othertext."</option>\n";
	}
	if ((isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] != "") && (!isset($defexists) || !$defexists) && $ia[6] != "Y" && $shownoanswer == 1) {$answer .= "\t\t\t\t\t\t<option value=' '>".$clang->gT("No answer")."</option>\n";}
	$answer .= "\t\t\t\t\t</select>\n";
    $answer .= "\t\t\t\t\t<input type='hidden' name='java$ia[1]' id='java$ia[1]' value='{$_SESSION[$ia[1]]}' />\n";
  
	$sselect = "\n\t\t\t\t\t<select name='$ia[1]' id='answer$ia[1]' onchange='checkconditions(this.value, this.name, this.type);";
	if (isset($other) && $other=="Y")
	{
		$sselect .= "; showhideother(this.name, this.value)";
	}
	$sselect .= "'>\n";
	$answer = $sselect.$answer;
	if (isset($other) && $other=="Y")
	{
		$answer = "\n<script type=\"text/javascript\">\n"
		."<!--\n"
		."function showhideother(name, value)\n"
		."\t{\n"
		."\tvar hiddenothername='othertext'+name;\n"
		."\tif (value == \"-oth-\")\n"
		."\t\t{\n"
		."\t\tdocument.getElementById(hiddenothername).style.display='';\n"
		."\t\tdocument.getElementById(hiddenothername).focus();\n"
		."\t\t}\n"
		."\telse\n"
		."\t\t{\n"
		."\t\tdocument.getElementById(hiddenothername).style.display='none';\n"
		."\t\t}\n"
		."\t}\n"
		."//--></script>\n".$answer;
		$answer .= "<input type='text' id='othertext".$ia[1]."' name='$ia[1]other' style='display:";

		$inputnames[]=$ia[1]."other";

		if ($_SESSION[$ia[1]] != "-oth-")
		{
			$answer .= "none";
		}

		// --> START BUG FIX - text field for other was not repopulating when returning to page via << PREV
		$answer .= "'";
		$thisfieldname=$ia[1]."other";
		if (isset($_SESSION[$thisfieldname])) { $answer .= "' value='".htmlspecialchars($_SESSION[$thisfieldname],ENT_QUOTES)."' ";}
		// --> END BUG FIX

		// --> START NEW FEATURE - SAVE
		$answer .= "  />";
		// --> END NEW FEATURE - SAVE
	}

	$inputnames[]=$ia[1];
	return array($answer, $inputnames);
}

function do_list_flexible_dropdown($ia)
{
	global $dbprefix, $dropdownthreshold, $lwcdropdowns, $connect;
	global $shownoanswer, $clang;
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($othertexts=arraySearchByKey("other_replace_text", $qidattributes, "attribute", 1))
	{
		$othertext=$othertexts['value'];
	}
	else
	{
		$othertext=$clang->gT("Other");
	}
	$answer="";
	$qquery = "SELECT other, lid FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);  //Checked
	while($row = $qresult->FetchRow()) {$other = $row['other']; $lid=$row['lid'];}
	$filter="";
	if ($code_filter=arraySearchByKey("code_filter", $qidattributes, "attribute", 1))
	{
		$filter=$code_filter['value'];
		if(in_array($filter, $_SESSION['insertarray']))
		{
			$filter=trim($_SESSION[$filter]);
		}
	}
	$filter .= "%";
	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}labels WHERE lid=$lid AND code LIKE '$filter' AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}labels WHERE lid=$lid AND code LIKE '$filter' AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, code";
	}
	$ansresult = db_execute_assoc($ansquery) or safe_die("Couldn't get answers<br />$ansquery<br />".$connect->ErrorMsg());//Checked

	if (labelset_exists($lid,$_SESSION['s_lang']))
	{
		while ($ansrow = $ansresult->FetchRow())
		{
			$answer .= "\t\t\t\t\t\t<option value='{$ansrow['code']}'";
			if ($_SESSION[$ia[1]] == $ansrow['code'])
			{
				$answer .= " selected='selected'";
			}
			$answer .= ">{$ansrow['title']}</option>\n";
		}
		if (!$_SESSION[$ia[1]] && (!isset($defexists) || !$defexists))
		{
			$answer = "\t\t\t\t\t\t<option value='' selected='selected'>".$clang->gT("Please choose")."..</option>\n".$answer;
		}
		if (isset($other) && $other=="Y")
		{
			$answer .= "\t\t\t\t\t\t<option value='-oth-'";
			if ($_SESSION[$ia[1]] == "-oth-")
			{
				$answer .= " selected='selected'";
			}
			$answer .= ">".$othertext."</option>\n";
		}
		if ((isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] != "") && (!isset($defexists) || !$defexists) && $ia[6] != "Y" && $shownoanswer == 1) {$answer .= "\t\t\t\t\t\t<option value=' '>".$clang->gT("No answer")."</option>\n";}
		$answer .= "\t\t\t\t\t</select>\n";
	}
	  else 
	  {
	    $answer .= "<option>".$clang->gT('Error: The labelset used for this question is not available in this language.')."</option>";
	  }	
	
    $answer .= "\t\t\t\t\t<input type='hidden' name='java$ia[1]' id='java$ia[1]' value='{$_SESSION[$ia[1]]}' />\n";
	$sselect = "\n\t\t\t\t\t<select name='$ia[1]' id='answer$ia[1]' onchange='checkconditions(this.value, this.name, this.type);";
	if (isset($other) && $other=="Y")
	{
		$sselect .= "; showhideother(this.name, this.value)";
	}
	$sselect .= "'>\n";
	$answer = $sselect.$answer;
	if (isset($other) && $other=="Y")
	{
		$answer = "\n<script type=\"text/javascript\">\n"
		."<!--\n"
		."function showhideother(name, value)\n"
		."\t{\n"
		."\tvar hiddenothername='othertext'+name;\n"
		."\tif (value == \"-oth-\")\n"
		."\t\t{\n"
		."\t\tdocument.getElementById(hiddenothername).style.display='';\n"
		."\t\tdocument.getElementById(hiddenothername).focus();\n"
		."\t\t}\n"
		."\telse\n"
		."\t\t{\n"
		."\t\tdocument.getElementById(hiddenothername).style.display='none';\n"
		."\t\t}\n"
		."\t}\n"
		."//--></script>\n".$answer;
		$answer .= "<input type='text' id='othertext".$ia[1]."' name='$ia[1]other' style='display:";
		if ($_SESSION[$ia[1]] != "-oth-")
		{
			$answer .= " none";
		}
		// --> START NEW FEATURE - SAVE
		$answer .= "'  />";
		// --> END NEW FEATURE - SAVE
	}

	$inputnames[]=$ia[1];
	return array($answer, $inputnames);
}

function do_list_radio($ia)
{
	global $dbprefix, $dropdownthreshold, $lwcdropdowns, $connect, $clang;
	global $shownoanswer;

	$answer="";
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($displaycols=arraySearchByKey("display_columns", $qidattributes, "attribute", 1))
	{
		$dcols=$displaycols['value'];
	}
	else
	{
		$dcols=0;
	}
	if ($othertexts=arraySearchByKey("other_replace_text", $qidattributes, "attribute", 1))
	{
		$othertext=$othertexts['value'];
	}
	else
	{
		$othertext=$clang->gT("Other");
	}
	if (isset($defexists)) {unset ($defexists);}
	$query = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."' ";
	$result = db_execute_assoc($query);  //Checked
	while($row = $result->FetchRow()) {$other = $row['other'];}
	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery) or safe_die("Couldn't get answers<br />$ansquery<br />".$connect->ErrorMsg());  //Checked
	$anscount = $ansresult->RecordCount();
	if (isset($other) && $other=="Y") {$anscount++;} //Count up for the Other answer
	if ($ia[6] != "Y" && $shownoanswer == 1) {$anscount++;} //Count up if "No answer" is showing
	$divider="";
	$maxrows=0;
	if ($dcols >0 && $anscount >= $dcols) //Break into columns
	{
		$denominator=$dcols; //Change this to set the number of columns
		$width=sprintf("%0d", 100/$denominator);
		$maxrows=ceil(100*($anscount/$dcols)/100); //Always rounds up to nearest whole number
		$answer .= "<table class='question'><tr>\n <td valign='top' width='$width%' nowrap='nowrap'>";
		$divider=" </td>\n <td valign='top' width='$width%' nowrap='nowrap'>";
	}
	else
	{
		$answer .= "\n\t\t\t\t\t<table class='question'>\n"
		. "\t\t\t\t\t\t<tr>\n"
		. "\t\t\t\t\t\t\t<td align='left'>\n";
	}
	$rowcounter=0;
	while ($ansrow = $ansresult->FetchRow())
	{
		$rowcounter++;
		$answer .= "\t\t\t\t\t\t\t\t<input class='radio' type='radio' value='{$ansrow['code']}' name='$ia[1]' id='answer$ia[1]{$ansrow['code']}'";
		if ($_SESSION[$ia[1]] == $ansrow['code'])
		{
			$answer .= " checked='checked'";
		}
		elseif ($ansrow['default_value'] == "Y") 
            {
                $answer .= " checked='checked'"; $defexists = "Y";
            }
		// --> START NEW FEATURE - SAVE
		$answer .= " onclick=\"checkconditions(this.value, this.name, this.type); document.limesurvey.move.value = '";
		if (isset($_SESSION['step']) && $_SESSION['step'] && ($_SESSION['step'] == $_SESSION['totalsteps'])) 
				$answer .= "movesubmit";
			else
				$answer .= "movenext";
		$answer .= "'; document.limesurvey.submit();\"  /><label for='answer$ia[1]{$ansrow['code']}' class='answertext'>{$ansrow['answer']}</label><br />\n";
		// --> END NEW FEATURE - SAVE

		if ($rowcounter==$maxrows) {$answer .= $divider; $rowcounter=0;}
	}
	
	if (isset($other) && $other=="Y")
	{
		$rowcounter++;
		$answer .= "\t\t\t\t\t\t\t\t  <div style='text-indent: -22px; margin: 0px 0px 0px 22px;'> <input class='radio' type='radio' value='-oth-' name='$ia[1]' id='SOTH$ia[1]'";
		if ($_SESSION[$ia[1]] == "-oth-")
		{
			$answer .= " checked='checked'";
		}
		// --> START NEW FEATURE - SAVE
		$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /><label for='SOTH$ia[1]' class='answertext'>".$othertext."</label>\n";
		// --> END NEW FEATURE - SAVE

		$answer .= "<label for='answer$ia[1]othertext'><input type='text' class='text' id='answer$ia[1]othertext' name='$ia[1]other' size='20' title='".$clang->gT("Other")."' ";
		$thisfieldname=$ia[1]."other";
		if (isset($_SESSION[$thisfieldname])) { $answer .= "value='".htmlspecialchars($_SESSION[$thisfieldname],ENT_QUOTES)."' ";}
		// --> START NEW FEATURE - SAVE
		$answer .= "onclick=\"javascript:document.getElementById('SOTH$ia[1]').checked=true; checkconditions(document.getElementById('SOTH$ia[1]').value, document.getElementById('SOTH$ia[1]').name, document.getElementById('SOTH$ia[1]').type);\" /></label><br /></div>\n";
		// --> END NEW FEATURE - SAVE
		$inputnames[]=$thisfieldname;
		if ($rowcounter==$maxrows) {$answer .= $divider; $rowcounter=0;}
	}
	if ($ia[6] != "Y" && $shownoanswer == 1)
	{
		$rowcounter++;
		$answer .= "\t\t\t\t\t\t  <input class='radio' type='radio' name='$ia[1]' id='answer$ia[1]NANS' value=' ' ";
		if (((!isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] == "") && (!isset($defexists) || !$defexists)) || ($_SESSION[$ia[1]] == ' ' && (!isset($defexists) || !$defexists)))
		{
			$answer .= " checked='checked'"; //Check the "no answer" radio button if there is no default, and user hasn't answered this.
		}
		// --> START NEW FEATURE - SAVE
		$answer .=" onclick='checkconditions(this.value, this.name, this.type)' />"
		. "<label for='answer$ia[1]NANS' class='answertext'>".$clang->gT("No answer")."</label>\n";
		// --> END NEW FEATURE - SAVE

		if ($rowcounter==$maxrows) {$answer .= $divider; $rowcounter=0;}
	}
	$answer .= "\t\t\t\t\t\t\t<input type='hidden' name='java$ia[1]' id='java$ia[1]' value='{$_SESSION[$ia[1]]}' />\n"
	. "\t\t\t\t\t\t\t</td>\n"
	. "\t\t\t\t\t\t</tr>\n"
	. "\t\t\t\t\t</table>\n";
	$inputnames[]=$ia[1];
	return array($answer, $inputnames);
}

function do_list_flexible_radio($ia)
{
	global $dbprefix, $dropdownthreshold, $lwcdropdowns, $connect;
	global $shownoanswer, $clang;
	$answer="";
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($othertexts=arraySearchByKey("other_replace_text", $qidattributes, "attribute", 1))
	{
		$othertext=$othertexts['value'];
	}
	else
	{
		$othertext=$clang->gT("Other");
	}
	if ($displaycols=arraySearchByKey("display_columns", $qidattributes, "attribute", 1))
	{
		$dcols=$displaycols['value'];
	}
	else
	{
		$dcols=0;
	}
	if (isset($defexists)) {unset ($defexists);}
	$query = "SELECT other, lid FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
	$result = db_execute_assoc($query);       //Checked
	while($row = $result->FetchRow()) {$other = $row['other']; $lid = $row['lid'];}
	$filter="";
	if ($code_filter=arraySearchByKey("code_filter", $qidattributes, "attribute", 1))
	{
		$filter=$code_filter['value'];
		if(in_array($filter, $_SESSION['insertarray']))
		{
			$filter=trim($_SESSION[$filter]);
		}
	}
	$filter .= "%";
	
	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}labels WHERE lid=$lid AND code LIKE '$filter' AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}labels WHERE lid=$lid AND code LIKE '$filter' AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, code";
	}
	$ansresult = db_execute_assoc($ansquery) or safe_die("Couldn't get answers<br />$ansquery<br />".$connect->ErrorMsg());    //Checked
	$anscount = $ansresult->RecordCount();
	
	
	
	if ((isset($other) && $other=="Y") || ($ia[6] != "Y" && $shownoanswer == 1)) {$anscount++;} //Count
	$divider="";
	$maxrows=0;
	if ($dcols >0 && $anscount >= $dcols) //Break into columns
	{
		$denominator=$dcols; //Change this to set the number of columns
		$width=sprintf("%0d", 100/$denominator);
		$maxrows=ceil(100*($anscount/$dcols)/100); //Always rounds up to nearest whole number
		$answer .= "<table class='question'><tr>\n <td valign='top' width='$width%' nowrap='nowrap'>";
		$divider=" </td>\n <td valign='top' width='$width%' nowrap='nowrap'>";
	}
	else
	{
		$answer .= "\n\t\t\t\t\t<table class='question'>\n"
		. "\t\t\t\t\t\t<tr>\n"
		. "\t\t\t\t\t\t\t<td align='left'>\n";
	}
	$rowcounter=0;

	if (labelset_exists($lid,$_SESSION['s_lang']))
	{
	
		while ($ansrow = $ansresult->FetchRow())
		{
			$rowcounter++;
			$answer .= "\t\t\t\t\t\t\t\t  <input class='radio' type='radio' value='{$ansrow['code']}' name='$ia[1]' id='answer$ia[1]{$ansrow['code']}'";
			if ($_SESSION[$ia[1]] == $ansrow['code'])
			{
				$answer .= " checked='checked'";
			}
			// --> START NEW FEATURE - SAVE
			$answer .= " onclick=\"checkconditions(this.value, this.name, this.type); document.limesurvey.move.value = '";
			if (isset($_SESSION['step']) && $_SESSION['step'] && ($_SESSION['step'] == $_SESSION['totalsteps'])) 
				$answer .= "movesubmit";
			else
				$answer .= "movenext";
		        $answer .= "'; document.limesurvey.submit();\" /><label for='answer$ia[1]{$ansrow['code']}' class='answertext'>{$ansrow['title']}</label><br />\n";
			// --> END NEW FEATURE - SAVE
	
			if ($rowcounter==$maxrows) {$answer .= $divider; $rowcounter=0;}
		}
	}
	  else 
	  {
	    $answer .= $clang->gT('Error: The labelset used for this question is not available in this language.')."<br />";
	  }	
	if (isset($other) && $other=="Y")
	{
		$rowcounter++;
		$answer .= "\t\t\t\t\t\t\t\t  <input class='radio' type='radio' value='-oth-' name='$ia[1]' id='SOTH$ia[1]'";
		if ($_SESSION[$ia[1]] == "-oth-")
		{
			$answer .= " checked='checked'";
		}
		// --> START NEW FEATURE - SAVE
		$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /><label for='SOTH$ia[1]' class='answertext'>".$othertext."</label>\n";
		// --> END NEW FEATURE - SAVE

		$answer .= "<label for='answer$ia[1]othertext'><input type='text' class='text' id='answer$ia[1]othertext' name='$ia[1]other' size='20' title='".$clang->gT("Other")."' ";
		$thisfieldname=$ia[1]."other";
		if (isset($_SESSION[$thisfieldname])) { $answer .= "value='".htmlspecialchars($_SESSION[$thisfieldname],ENT_QUOTES)."' ";}
		// --> START NEW FEATURE - SAVE
		$answer .= "onclick=\"javascript:document.getElementById('SOTH$ia[1]').checked=true; checkconditions(document.getElementById('SOTH$ia[1]').value, document.getElementById('SOTH$ia[1]').name, document.getElementById('SOTH$ia[1]').type)\" ></label><br />\n";
		// --> END NEW FEATURE - SAVE

		$inputnames[]=$thisfieldname;
		if ($rowcounter==$maxrows) {$answer .= $divider; $rowcounter=0;}
	}
	if ($ia[6] != "Y" && $shownoanswer == 1)
	{
		$rowcounter++;
		$answer .= "\t\t\t\t\t\t  <input class='radio' type='radio' name='$ia[1]' id='answer$ia[1]NANS' value=' ' ";
		if ((!isset($defexists) || $defexists != "Y") && (!isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] == "" || $_SESSION[$ia[1]] == " "))
		{
			$answer .= " checked='checked'"; //Check the "no answer" radio button if there is no default, and user hasn't answered this.
		}
		$answer .=" onclick='checkconditions(this.value, this.name, this.type)' />"
		. "<label for='answer$ia[1]NANS' class='answertext'>".$clang->gT("No answer")."</label>\n";

		if ($rowcounter==$maxrows) {$answer .= $divider; $rowcounter=0;}
	}
	$answer .= "\t\t\t\t\t\t<input type='hidden' name='java$ia[1]' id='java$ia[1]' value='{$_SESSION[$ia[1]]}' />\n"
	. "\t\t\t\t\t\t\t</td>\n"
	. "\t\t\t\t\t\t</tr>\n"
	. "\t\t\t\t\t</table>\n";
	$inputnames[]=$ia[1];
	return array($answer, $inputnames);
}

function do_listwithcomment($ia)
{
	global $maxoptionsize, $dbprefix, $dropdownthreshold, $lwcdropdowns;
	global $shownoanswer, $clang;
	$answer="";
	$qidattributes=getQuestionAttributes($ia[0]);
	if (!isset($maxoptionsize)) {$maxoptionsize=35;}
	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery);      //Checked
	$anscount = $ansresult->RecordCount();
	if ($lwcdropdowns == "R" && $anscount <= $dropdownthreshold)
	{
		$answer .= "\t\t\t<table class='question'>\n"
		. "\t\t\t\t<tr>\n"
		. "\t\t\t\t\t<td><u>".$clang->gT("Please choose one of the following").":</u></td>\n"
		. "\t\t\t\t\t<td><u><label for='answer$ia[1]comment'>".$clang->gT("Please enter your comment here").":</label></u></td>\n"
		. "\t\t\t\t</tr>\n"
		. "\t\t\t\t<tr>\n"
		. "\t\t\t\t\t<td valign='top' align='left'>\n";

		while ($ansrow=$ansresult->FetchRow())
		{
			$answer .= "\t\t\t\t\t\t<input class='radio' type='radio' value='{$ansrow['code']}' name='$ia[1]' id='answer$ia[1]{$ansrow['code']}'";
			if ($_SESSION[$ia[1]] == $ansrow['code'])
			{$answer .= " checked='checked'";}
			elseif ($ansrow['default_value'] == "Y")
			{
				$answer .= " checked='checked'"; 
				$defexists = "Y";
			}
			$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /><label for='answer$ia[1]{$ansrow['code']}' class='answertext'>{$ansrow['answer']}</label><br />\n";

		}
		if ($ia[6] != "Y" && $shownoanswer == 1)
		{
			$answer .= "\t\t\t\t\t\t<input class='radio' type='radio' name='$ia[1]' id='answer$ia[1]' value=' ' onclick='checkconditions(this.value, this.name, this.type)' ";
			if (((!isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] == "") && (!isset($defexists) || !$defexists)) ||($_SESSION[$ia[1]] == ' ' && (!isset($defexists) || !$defexists)))
			{
				$answer .= "checked='checked' />";
			}
			elseif ((isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] != "") && (!isset($defexists) || !$defexists))
			{
				$answer .= " />";
			}
			$answer .= "<label for='answer$ia[1] ' class='answertext'>".$clang->gT("No answer")."</label>\n";
		}
		$answer .= "\t\t\t\t\t</td>\n";
		$fname2 = $ia[1]."comment";
		if ($anscount > 8) {$tarows = $anscount/1.2;} else {$tarows = 4;}
		// --> START NEW FEATURE - SAVE
		//    --> START ORIGINAL
		//        $answer .= "\t\t\t\t\t<td valign='top'>\n"
		//                 . "\t\t\t\t\t\t<textarea class='textarea' name='$ia[1]comment' id='answer$ia[1]comment' rows='$tarows' cols='30'>";
		//    --> END ORIGINAL
		$answer .= "\t\t\t\t\t<td valign='top'>\n"
		. "\t\t\t\t\t\t<textarea class='textarea' name='$ia[1]comment' id='answer$ia[1]comment' rows='$tarows' cols='30' >";
		// --> END NEW FEATURE - SAVE
		if (isset($_SESSION[$fname2]) && $_SESSION[$fname2])
		{
			$answer .= str_replace("\\", "", $_SESSION[$fname2]);
		}
		$answer .= "</textarea>\n"
		. "\t\t\t\t<input class='radio' type='hidden' name='java$ia[1]' id='java$ia[1]' value='{$_SESSION[$ia[1]]}' />\n"
		. "\t\t\t\t\t</td>\n"
		. "\t\t\t\t</tr>\n"
		. "\t\t\t</table>\n";
		$inputnames[]=$ia[1];
		$inputnames[]=$ia[1]."comment";
	}
	else //Dropdown list
	{
		// --> START NEW FEATURE - SAVE
		$answer .= "\t\t\t<table class='question'>\n"
		. "\t\t\t\t<tr>\n"
		. "\t\t\t\t\t<td valign='top' align='center'>\n"
		. "\t\t\t\t\t<select class='select' name='$ia[1]' id='answer$ia[1]' onclick='checkconditions(this.value, this.name, this.type)' >\n";
		// --> END NEW FEATURE - SAVE
		while ($ansrow=$ansresult->FetchRow())
		{
			$answer .= "\t\t\t\t\t\t<option value='{$ansrow['code']}'";
			if ($_SESSION[$ia[1]] == $ansrow['code'])
			{$answer .= " selected='selected'";}
			elseif ($ansrow['default_value'] == "Y")
			{
				$answer .= " selected='selected'"; 
				$defexists = "Y";
			}
			$answer .= ">{$ansrow['answer']}</option>\n";
			if (strlen($ansrow['answer']) > $maxoptionsize)
			{
				$maxoptionsize = strlen($ansrow['answer']);
			}
		}
		if ($ia[6] != "Y" && $shownoanswer == 1)
		{
			if (((!isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] == "") && (!isset($defexists) || !$defexists)) ||($_SESSION[$ia[1]] == ' ' && (!isset($defexists) || !$defexists)))
			{
				$answer .= "\t\t\t\t\t\t<option value=' ' selected='selected'>".$clang->gT("No answer")."</option>\n";
			}
			elseif ((isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] != "") && (!isset($defexists) || !$defexists))
			{
				$answer .= "\t\t\t\t\t\t<option value=' '>".$clang->gT("No answer")."</option>\n";
			}
		}
		$answer .= "\t\t\t\t\t</select>\n"
		. "\t\t\t\t\t</td>\n"
		. "\t\t\t\t</tr>\n"
		. "\t\t\t\t<tr>\n";
		$fname2 = $ia[1]."comment";
		if ($anscount > 8) {$tarows = $anscount/1.2;} else {$tarows = 4;}
		if ($tarows > 15) {$tarows=15;}
		$maxoptionsize=$maxoptionsize*0.72;
		if ($maxoptionsize < 33) {$maxoptionsize=33;}
		if ($maxoptionsize > 70) {$maxoptionsize=70;}
		$answer .= "\t\t\t\t\t<td valign='top'>\n";
		// --> START NEW FEATURE - SAVE
		$answer .= "\t\t\t\t\t\t<textarea class='textarea' name='$ia[1]comment' id='answer$ia[1]comment' rows='$tarows' cols='$maxoptionsize' >";
		// --> END NEW FEATURE - SAVE
		if (isset($_SESSION[$fname2]) && $_SESSION[$fname2])
		{
			$answer .= str_replace("\\", "", $_SESSION[$fname2]);
		}
		$answer .= "</textarea>\n"
		. "\t\t\t\t<input class='radio' type='hidden' name='java$ia[1]' id='java$ia[1]' value='{$_SESSION[$ia[1]]}' />\n"
		. "\t\t\t\t\t</td>\n"
		. "\t\t\t\t</tr>\n"
		. "\t\t\t</table>\n";
		$inputnames[]=$ia[1];
		$inputnames[]=$ia[1]."comment";
	}
	return array($answer, $inputnames);
}

function do_ranking($ia)
{
	global $dbprefix, $imagefiles, $clang;
	$qidattributes=getQuestionAttributes($ia[0]);
	$answer="";
	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery);   //Checked
	$anscount = $ansresult->RecordCount();
	$answer .= "\t\t\t<script type='text/javascript'>\n"
	. "\t\t\t<!--\n"
	. "\t\t\t\tfunction rankthis_{$ia[0]}(\$code, \$value)\n"
	. "\t\t\t\t\t{\n"
	. "\t\t\t\t\t\$index=document.limesurvey.CHOICES_{$ia[0]}.selectedIndex;\n"
	. "\t\t\t\t\tdocument.limesurvey.CHOICES_{$ia[0]}.selectedIndex=-1;\n"
	. "\t\t\t\t\tfor (i=1; i<=$anscount; i++)\n"
	. "\t\t\t\t\t\t{\n"
	. "\t\t\t\t\t\t\$b=i;\n"
	. "\t\t\t\t\t\t\$b += '';\n"
	. "\t\t\t\t\t\t\$inputname=\"RANK_{$ia[0]}\"+\$b;\n"
	. "\t\t\t\t\t\t\$hiddenname=\"fvalue_{$ia[0]}\"+\$b;\n"
	. "\t\t\t\t\t\t\$cutname=\"cut_{$ia[0]}\"+i;\n"
	. "\t\t\t\t\t\tdocument.getElementById(\$cutname).style.display='none';\n"
	. "\t\t\t\t\t\tif (!document.getElementById(\$inputname).value)\n"
	. "\t\t\t\t\t\t\t{\n"
	. "\t\t\t\t\t\t\tdocument.getElementById(\$inputname).value=\$value;\n"
	. "\t\t\t\t\t\t\tdocument.getElementById(\$hiddenname).value=\$code;\n"
	. "\t\t\t\t\t\t\tdocument.getElementById(\$cutname).style.display='';\n"
	. "\t\t\t\t\t\t\tfor (var b=document.getElementById('CHOICES_{$ia[0]}').options.length-1; b>=0; b--)\n"
	. "\t\t\t\t\t\t\t\t{\n"
	. "\t\t\t\t\t\t\t\tif (document.getElementById('CHOICES_{$ia[0]}').options[b].value == \$code)\n"
	. "\t\t\t\t\t\t\t\t\t{\n"
	. "\t\t\t\t\t\t\t\t\tdocument.getElementById('CHOICES_{$ia[0]}').options[b] = null;\n"
	. "\t\t\t\t\t\t\t\t\t}\n"
	. "\t\t\t\t\t\t\t\t}\n"
	. "\t\t\t\t\t\t\ti=$anscount;\n"
	. "\t\t\t\t\t\t\t}\n"
	. "\t\t\t\t\t\t}\n"
	. "\t\t\t\t\tif (document.getElementById('CHOICES_{$ia[0]}').options.length == 0)\n"
	. "\t\t\t\t\t\t{\n"
	. "\t\t\t\t\t\tdocument.getElementById('CHOICES_{$ia[0]}').disabled=true;\n"
	. "\t\t\t\t\t\t}\n"
	. "\t\t\t\t\tcheckconditions(\$code);\n"
	. "\t\t\t\t\t}\n"
	. "\t\t\t\tfunction deletethis_{$ia[0]}(\$text, \$value, \$name, \$thisname)\n"
	. "\t\t\t\t\t{\n"
	. "\t\t\t\t\tvar qid='{$ia[0]}';\n"
	. "\t\t\t\t\tvar lngth=qid.length+4;\n"
	. "\t\t\t\t\tvar cutindex=\$thisname.substring(lngth, \$thisname.length);\n"
	. "\t\t\t\t\tcutindex=parseFloat(cutindex);\n"
	. "\t\t\t\t\tdocument.getElementById(\$name).value='';\n"
	. "\t\t\t\t\tdocument.getElementById(\$thisname).style.display='none';\n"
	. "\t\t\t\t\tif (cutindex > 1)\n"
	. "\t\t\t\t\t\t{\n"
	. "\t\t\t\t\t\t\$cut1name=\"cut_{$ia[0]}\"+(cutindex-1);\n"
	. "\t\t\t\t\t\t\$cut2name=\"fvalue_{$ia[0]}\"+(cutindex);\n"
	. "\t\t\t\t\t\tdocument.getElementById(\$cut1name).style.display='';\n"
	. "\t\t\t\t\t\tdocument.getElementById(\$cut2name).value='';\n"
	. "\t\t\t\t\t\t}\n"
	. "\t\t\t\t\telse\n"
	. "\t\t\t\t\t\t{\n"
	. "\t\t\t\t\t\t\$cut2name=\"fvalue_{$ia[0]}\"+(cutindex);\n"
	. "\t\t\t\t\t\tdocument.getElementById(\$cut2name).value='';\n"
	. "\t\t\t\t\t\t}\n"
	. "\t\t\t\t\tvar i=document.getElementById('CHOICES_{$ia[0]}').options.length;\n"
	. "\t\t\t\t\tdocument.getElementById('CHOICES_{$ia[0]}').options[i] = new Option(\$text, \$value);\n"
	. "\t\t\t\t\tif (document.getElementById('CHOICES_{$ia[0]}').options.length > 0)\n"
	. "\t\t\t\t\t\t{\n"
	. "\t\t\t\t\t\tdocument.getElementById('CHOICES_{$ia[0]}').disabled=false;\n"
	. "\t\t\t\t\t\t}\n"
	. "\t\t\t\t\tcheckconditions('');\n"
	. "\t\t\t\t\t}\n"
	. "\t\t\t//-->\n"
	. "\t\t\t</script>\n";
	unset($answers);
	//unset($inputnames);
	unset($chosen);
	$ranklist="";
	while ($ansrow = $ansresult->FetchRow())
	{
		$answers[] = array($ansrow['code'], $ansrow['answer']);
	}
	$existing=0;
	for ($i=1; $i<=$anscount; $i++)
	{
		$myfname=$ia[1].$i;
		if (isset($_SESSION[$myfname]) && $_SESSION[$myfname])
		{
			$existing++;
		}
	}
	for ($i=1; $i<=$anscount; $i++)
	{
		$myfname = $ia[1].$i;
		if (isset($_SESSION[$myfname]) && $_SESSION[$myfname])
		{
			foreach ($answers as $ans)
			{
				if ($ans[0] == $_SESSION[$myfname])
				{
					$thiscode=$ans[0];
					$thistext=$ans[1];
				}
			}
		}
		$ranklist .= "\t\t\t\t\t\t\t<tr><td align=right>&nbsp;<label for='RANK_{$ia[0]}$i'>"
		."$i:&nbsp;</label></td><td><input class='text' type='text' name='RANK_{$ia[0]}$i' id='RANK_{$ia[0]}$i'";
		if (isset($_SESSION[$myfname]) && $_SESSION[$myfname])
		{
			$ranklist .= " value='";
			$ranklist .= htmlspecialchars($thistext, ENT_QUOTES);
			$ranklist .= "'";
		}
		$ranklist .= " onFocus=\"this.blur()\" />\n";
		$ranklist .= "\t\t\t\t\t\t<input type='hidden' name='$myfname' id='fvalue_{$ia[0]}$i' value='";
		$chosen[]=""; //create array
		if (isset($_SESSION[$myfname]) && $_SESSION[$myfname])
		{
			$ranklist .= $thiscode;
			$chosen[]=array($thiscode, $thistext);
		}
		$ranklist .= "' />\n";
		$ranklist .= "\t\t\t\t\t\t<img src='$imagefiles/cut.gif' alt='".$clang->gT("Remove this item")."' title='".$clang->gT("Remove this item")."' ";
		if ($i != $existing)
		{
			$ranklist .= "style='display:none'";
		}
		$ranklist .= " id='cut_{$ia[0]}$i' onclick=\"deletethis_{$ia[0]}(document.limesurvey.RANK_{$ia[0]}$i.value, document.limesurvey.fvalue_{$ia[0]}$i.value, document.limesurvey.RANK_{$ia[0]}$i.name, this.id)\" /><br />\n";
		$inputnames[]=$myfname;
		$ranklist .= "</td></tr>\n";
	}

	$choicelist = "\t\t\t\t\t\t<select size='$anscount' name='CHOICES_{$ia[0]}' ";
	if (isset($choicewidth)) {$choicelist.=$choicewidth;}
    $choicelist .= " id='CHOICES_{$ia[0]}' onclick=\"if (this.options.length>0 && this.selectedIndex<0) {this.options[this.options.length-1].selected=true;}; rankthis_{$ia[0]}(this.options[this.selectedIndex].value, this.options[this.selectedIndex].text)\" class='select'>\n";
	if (_PHPVERSION <= "4.2.0")
	{
		foreach ($chosen as $chs) {$choose[]=$chs[0];}
		foreach ($answers as $ans)
		{
			if (!in_array($ans[0], $choose))
			{
				$choicelist .= "\t\t\t\t\t\t\t<option value='{$ans[0]}'>{$ans[1]}</option>\n";
				if (strlen($ans[1]) > $maxselectlength) {$maxselectlength = strlen($ans[1]);}
			}
		}
	}
	else
	{
		foreach ($answers as $ans)
		{
			if (!in_array($ans, $chosen))
			{
				$choicelist .= "\t\t\t\t\t\t\t<option value='{$ans[0]}'>{$ans[1]}</option>\n";
				if (isset($maxselectlength) && strlen($ans[1]) > $maxselectlength) {$maxselectlength = strlen($ans[1]);}
			}
		}
	}
	$choicelist .= "\t\t\t\t\t\t</select>\n";

	$answer .= "\t\t\t<table border='0' cellspacing='5' width='500' class='rank'>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td colspan='2' class='rank'><font size='1'>\n"
	. "\t\t\t\t\t\t".$clang->gT("Click on an item in the list on the left, starting with your")
	. "\t\t\t\t\t\t".$clang->gT("highest ranking item, moving through to your lowest ranking item.")
	. "\t\t\t\t\t</font></td>\n"
	. "\t\t\t\t</tr>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td align='left' valign='top' class='rank'>\n"
	. "\t\t\t\t\t\t<strong>&nbsp;&nbsp;<label for='CHOICES_{$ia[0]}'>".$clang->gT("Your Choices").":</label></strong><br />\n"
	. "&nbsp;".$choicelist
	. "\t\t\t\t\t&nbsp;</td>\n";
	if (isset($maxselectlength) && $maxselectlength > 60)
	{
		$ranklist = str_replace("<input class='text'", "<input size='60' class='text'", $ranklist);
		$answer .= "\t\t\t\t</tr>\n\t\t\t\t<tr>\n"
		. "\t\t\t\t\t<td align='left' width='250' class='rank'>\n"
		. "\t\t\t\t\t\t\t<table border='0' cellspacing='1' cellpadding='0'>\n"
		. "\t\t\t\t\t\t\t<tr><td></td><td><strong>".$clang->gT("Your Ranking").":</strong></td></tr>\n";
	}
	else
	{
		$answer .= "\t\t\t\t\t<td align='left' width='250' class='rank' nowrap>\n"
		. "\t\t\t\t\t\t\t<table border='0' cellspacing='1' cellpadding='0'>\n"
		. "\t\t\t\t\t\t\t<tr><td></td><td><strong>".$clang->gT("Your Ranking").":</strong></td></tr>\n";
	}
	$answer .= $ranklist
	. "\t\t\t\t\t\t\t</table>\n"
	. "\t\t\t\t\t</td>\n"
	. "\t\t\t\t</tr>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td colspan='2' class='rank'><font size='1'>\n"
	. "\t\t\t\t\t\t".$clang->gT("Click on the scissors next to each item on the right")
	. "\t\t\t\t\t\t".$clang->gT("to remove the last entry in your ranked list").""
	. "\t\t\t\t\t</font></td>\n"
	. "\t\t\t\t</tr>\n"
	. "\t\t\t</table>\n";

	return array($answer, $inputnames);
}

function do_multiplechoice($ia)
{
	global $dbprefix, $clang, $connect;
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($othertexts=arraySearchByKey("other_replace_text", $qidattributes, "attribute", 1))
	{
		$othertext=$othertexts['value'];
	}
	else
	{
		$othertext=$clang->gT("Other");
	}
	if ($displaycols=arraySearchByKey("display_columns", $qidattributes, "attribute", 1))
	{
		$dcols=$displaycols['value'];
	}
	else
	{
		$dcols=0;
	}
	// Check if the max_answers attribute is set
	$maxansw=0;
	$callmaxanswscriptcheckbox = "";
	$callmaxanswscriptother = "";
	$maxanswscript = "";
	if ($excludeothers=arraySearchByKey("exclude_all_others", $qidattributes, "attribute", 1))
	{
	    $excludeallothers=$excludeothers['value'];
	    $excludeallotherscript = "
		<script type='text/javascript'>
		<!--
		function excludeAllOthers$ia[1](value, doconditioncheck)
		{\n";
		$excludeallotherscripton="";
		$excludeallotherscriptoff="";
	} else {
	    $excludeallothers="";
	}
	
	if ($maxanswattr=arraySearchByKey("max_answers", $qidattributes, "attribute", 1))
	{
		$maxansw=$maxanswattr['value'];
		$callmaxanswscriptcheckbox = "limitmaxansw_{$ia[0]}(this);";
		$callmaxanswscriptother = "onkeyup='limitmaxansw_{$ia[0]}(this)'";

		$maxanswscript = "\t\t\t<script type='text/javascript'>\n"
			. "\t\t\t<!--\n"
			. "\t\t\t\tfunction limitmaxansw_{$ia[0]}(me)\n"
			. "\t\t\t\t\t{\n"
			. "\t\t\t\t\tmax=$maxansw\n"
			. "\t\t\t\t\tcount=0;\n"
			. "\t\t\t\t\tif (max == 0) { return count; }\n";
	}
	$answer  = "\t\t\t<table class='question'>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td>&nbsp;</td>\n"
	. "\t\t\t\t\t<td align='left' class='answertext'>\n";
	$qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);     //Checked
	while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}
	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
//    echo $ansquery;
	$ansresult = db_execute_assoc($ansquery);  //Checked
	$anscount = $ansresult->RecordCount();
	if ($other == "Y") {$anscount++;} //COUNT OTHER AS AN ANSWER FOR MANDATORY CHECKING!
	$answer .= "\t\t\t\t\t<input type='hidden' name='MULTI$ia[1]' value='$anscount' />\n";
	$divider="";
	$maxrows=0;
	$closetable=false;
	if ($dcols >0 && $anscount >= $dcols) //Break into columns
	{
		$width=sprintf("%0d", 100/$dcols);
		$maxrows=ceil(100*($anscount/$dcols)/100); //Always rounds up to nearest whole number
		$answer .= "<table class='question'><tr>\n <td valign='top' width='$width%' nowrap='nowrap'>";
		$divider=" </td>\n <td valign='top' width='$width%' nowrap='nowrap'>";
		$closetable=true;
	}
	$fn = 1;
	if (!isset($multifields)) {$multifields="";}
	$rowcounter=0;
	$postrow="";
	while ($ansrow = $ansresult->FetchRow())
	{
		$rowcounter++;
		$myfname = $ia[1].$ansrow['code'];
		$answer .= "\t\t\t\t\t\t<input class='checkbox' type='checkbox' name='$ia[1]{$ansrow['code']}' id='answer$ia[1]{$ansrow['code']}' value='Y'";
		if (isset($_SESSION[$myfname]))
		{
			if ($_SESSION[$myfname] == "Y")
			{
				$answer .= " checked='checked'";
				if($ansrow['code'] == $excludeallothers) 
				{
				  $postrow.="\n\n<script type='text/javascript'>
				  <!--
				  excludeAllOthers$ia[1]('answer$ia[1]{$ansrow['code']}', 'no');
				  -->
				  </script>\n";
				}
			}
		}
		elseif ($ansrow['default_value'] == 'Y')
		{
			$answer .= " checked='checked'";
		}
		// --> START NEW FEATURE - SAVE
		$answer .= " onclick='";
		if($ansrow['code'] == $excludeallothers) 
		{
		  $answer .= "excludeAllOthers$ia[1](this.id, \"yes\");"; // was "this.id"
		} elseif ($excludeallothers != "") {
		  $excludeallotherscripton .= "thiselt=document.getElementById('answer$ia[1]{$ansrow['code']}');\n"
					. "\t\tthiselt.checked='';\n"
					. "\t\tthiselt.disabled='true';\n"
					. "\t\tif (doconditioncheck == 'yes') {\n"
					. "\t\t\tcheckconditions(thiselt.value, thiselt.name, thiselt.type);\n"
					. "\t\t}\n";
		  $excludeallotherscriptoff.= "document.getElementById('answer$ia[1]{$ansrow['code']}').disabled='';\n";
		}
		$answer .= $callmaxanswscriptcheckbox."checkconditions(this.value, this.name, this.type)'  /><label for='answer$ia[1]{$ansrow['code']}' class='answertext'>{$ansrow['answer']}</label><br />\n";
		// --> END NEW FEATURE - SAVE

		if ($maxansw > 0) {$maxanswscript .= "\t\t\t\t\tif (document.getElementById('answer".$myfname."').checked) { count += 1; }\n";}

		$fn++;
		$answer .= "\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
		if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
		$answer .= "' />\n";
		$inputnames[]=$myfname;
		if ($rowcounter==$maxrows) {$answer .= $divider; $rowcounter=0;}
	}
	if ($other == "Y")
	{
		$rowcounter++;
		$myfname = $ia[1]."other";
	    if($excludeallothers != "") 
		{
		  $excludeallotherscripton .= "thiselt=document.getElementById('$ia[1]othercbox');\n"
		                            . "\t\tthiselt.checked='';\n"
		                            . "\t\tthiselt.disabled='true';\n";
		  $excludeallotherscripton .= "thiselt=document.getElementById('answer$ia[1]other');\n"
		                            . "\t\tthiselt.value='';\n"
		                            . "\t\tthiselt.disabled='true';\n"
									. "\t\tif (doconditioncheck == 'yes') {\n"
		                            . "\t\t\tcheckconditions(thiselt.value, thiselt.name, thiselt.type);\n"
									. "\t\t}\n";
		  $excludeallotherscriptoff .="document.getElementById('answer$ia[1]other').disabled='';\n";
		  $excludeallotherscriptoff .="document.getElementById('$ia[1]othercbox').disabled='';\n";
		}
		$answer .= "\t\t\t\t\t\t<input class='checkbox' type='checkbox' name='{$myfname}cbox' id='{$myfname}cbox'";
		if (isset($_SESSION[$myfname]) && trim($_SESSION[$myfname])!='') {$answer .= " checked='checked'";}
		$answer .= " onchange='".$callmaxanswscriptcheckbox."document.getElementById(\"answer$myfname\").value=\"\"'  />";
		$answer .= "\t\t\t\t\t\t<label for='answer$myfname' class='answertext'>".$othertext.":</label> <input class='text' type='text' name='$myfname' id='answer$myfname'";
		if (isset($_SESSION[$myfname])) {$answer .= " value='".htmlspecialchars($_SESSION[$myfname],ENT_QUOTES)."'";}
		// --> START NEW FEATURE - SAVE
		$answer .= " onkeypress='document.getElementById(\"{$myfname}cbox\").checked=true;' ".$callmaxanswscriptother."/>\n"
		. "\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
		// --> END NEW FEATURE - SAVE

		if ($maxansw > 0)
		{
			$maxanswscript .= "\t\t\t\t\tif (document.getElementById('answer".$myfname."').value != '' || document.getElementById('".$myfname."cbox').checked ) { count += 1; }\n"; 
		}

		if (isset($_SESSION[$myfname])) {$answer .= htmlspecialchars($_SESSION[$myfname],ENT_QUOTES);}

		$answer .= "' />\n";
		$inputnames[]=$myfname;
		$anscount++;
		if ($rowcounter==$maxrows) {$answer .= $divider; $rowcounter=0;}
	}
	if ($closetable) $answer.="</td></tr></table>\n";
	$answer .= "\t\t\t\t\t</td>\n";
	if ($dcols <1)
	{ //This just makes a single column look a bit nicer
		$answer .= "\t\t\t\t\t<td>&nbsp;</td>\n";
	}
	$answer .= "\t\t\t\t</tr>\n"
	. "\t\t\t</table>\n";

	if ( $maxansw > 0 )
	{
		$maxanswscript .= "\t\t\t\t\tif (count > max)\n"
			. "\t\t\t\t\t\t{\n"
			. "\t\t\t\t\t\talert('".$clang->gT("Please choose at most","js")." ' + max + ' ".$clang->gT("answer(s) for question","js")." \"".trim(javascript_escape($ia[3],true,true))."\"');\n"
			. "\t\t\t\t\t\tif (me.type == 'checkbox') {me.checked = false;}\n"
			. "\t\t\t\t\t\tif (me.type == 'text') {\n"
			. "\t\t\t\t\t\t\tme.value = '';\n"
			. "\t\t\t\t\t\t\tif (document.getElementById(me.name + 'cbox') ){\n"
			. "\t\t\t\t\t\t\t\t document.getElementById(me.name + 'cbox').checked = false;\n"
			. "\t\t\t\t\t\t\t}\n"
			. "\t\t\t\t\t\t}"
			. "\t\t\t\t\t\treturn max;\n"
			. "\t\t\t\t\t\t}\n"
			. "\t\t\t\t\t}\n"
			. "\t\t\t//-->\n"
			. "\t\t\t</script>\n";
		$answer = $maxanswscript . $answer;  
	}
	if ($excludeallothers != "")
	{
	    $excludeallotherscript .= "
		if (document.getElementById(value).checked)
	      {
	      $excludeallotherscripton
	      }
	    else
	      {
	      $excludeallotherscriptoff
	      }
        }
		//-->
		</script>";
	    $answer = $excludeallotherscript . $answer;
	}
	$answer .= $postrow;
	return array($answer, $inputnames);
}

function do_multiplechoice_withcomments($ia)
{
	global $dbprefix, $clang;
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($othertexts=arraySearchByKey("other_replace_text", $qidattributes, "attribute", 1))
	{
		$othertext=$othertexts['value'];
	}
	else
	{
		$othertext=$clang->gT("Other");
	}
	// Check if the max_answers attribute is set
	$maxansw=0;
	$callmaxanswscriptcheckbox = "";
	$callmaxanswscriptother = "";
	$maxanswscript = "";
	if ($maxanswattr=arraySearchByKey("max_answers", $qidattributes, "attribute", 1))
	{
		$maxansw=$maxanswattr['value'];
		$callmaxanswscriptcheckbox = "limitmaxansw_{$ia[0]}(this);";
		$callmaxanswscriptother = "onkeyup='limitmaxansw_{$ia[0]}(this)'";

		$maxanswscript = "\t\t\t<script type='text/javascript'>\n"
			. "\t\t\t<!--\n"
			. "\t\t\t\tfunction limitmaxansw_{$ia[0]}(me)\n"
			. "\t\t\t\t\t{\n"
			. "\t\t\t\t\tmax=$maxansw\n"
			. "\t\t\t\t\tcount=0;\n"
			. "\t\t\t\t\tif (max == 0) { return count; }\n";
	}

	$answer  = "\t\t\t<table class='question'>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td>&nbsp;</td>\n"
	. "\t\t\t\t\t<td align='left'>\n";
	$qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."' ";
	$qresult = db_execute_assoc($qquery);     //Checked
	while ($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}
	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery);  //Checked
	$anscount = $ansresult->RecordCount()*2;
	$answer .= "\t\t\t\t\t<input type='hidden' name='MULTI$ia[1]' value='$anscount' />\n"
	. "\t\t\t\t\t\t<table class='question'>\n";
	$fn = 1;
	while ($ansrow = $ansresult->FetchRow())
	{
		$myfname = $ia[1].$ansrow['code'];
		$myfname2 = $myfname."comment";
		$answer .= "\t\t\t\t\t\t\t<tr>\n"
		. "\t\t\t\t\t\t\t\t<td>\n"
		. "\t\t\t\t\t\t\t\t\t<input class='checkbox' type='checkbox' name='$myfname' id='answer$myfname' value='Y'";
		if (isset($_SESSION[$myfname]))
		{
			if ($_SESSION[$myfname] == "Y")
			{
				$answer .= " checked='checked'";
			}
		}
		elseif ($ansrow['default_value'] == 'Y')
		{
			$answer .= " checked='checked'";
		}
		$answer .=" onclick='".$callmaxanswscriptcheckbox."checkconditions(this.value, this.name, this.type)' "
  				. " onchange='document.getElementById(\"answer$myfname2\").value=\"\"'' />"
				. "<label for='answer$myfname' class='answertext'>"
				. $ansrow['answer']."</label>\n";

		if ($maxansw > 0) {$maxanswscript .= "\t\t\t\t\tif (document.getElementById('answer".$myfname."').checked) { count += 1; }\n";}

		$answer.= "\t\t\t\t\t\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
		if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
		$answer .= "' />\n"
		. "\t\t\t\t\t\t\t\t</td>\n";
		$fn++;
		$answer .= "\t\t\t\t\t\t\t\t<td>\n"
		. "\t\t\t\t\t\t\t\t\t<label for='answer$myfname2'>"
		."<input class='text' type='text' size='40' id='answer$myfname2' name='$myfname2' title='".$clang->gT("Make a comment on your choice here:")."' value='";
		if (isset($_SESSION[$myfname2])) {$answer .= htmlspecialchars($_SESSION[$myfname2],ENT_QUOTES);}
		// --> START NEW FEATURE - SAVE
		$answer .= "'  onkeypress='document.getElementById(\"answer{$myfname}\").checked=true;' /></label>\n"
		
		. "\t\t\t\t\t\t\t\t</td>\n"
		. "\t\t\t\t\t\t\t</tr>\n";
		// --> END NEW FEATURE - SAVE

		$fn++;
		$inputnames[]=$myfname;
		$inputnames[]=$myfname2;
	}
	if ($other == "Y")
	{
		$myfname = $ia[1]."other";
		$myfname2 = $myfname."comment";
		$anscount = $anscount + 2;
		$answer .= "\t\t\t\t\t\t\t<tr>\n"
		. "\t\t\t\t\t\t\t\t<td class='answertext'>\n"
		. "\t\t\t\t\t\t\t\t\t<label for='answer$myfname' class='answertext'>".$othertext.":</label><input class='text' type='text' name='$myfname' id='answer$myfname' title='".$clang->gT("Other")."' size='10'";
		if (isset($_SESSION[$myfname]) && $_SESSION[$myfname]) {$answer .= " value='".htmlspecialchars($_SESSION[$myfname],ENT_QUOTES)."'";}
		$fn++;
		// --> START NEW FEATURE - SAVE
		$answer .= "  $callmaxanswscriptother/>\n"
		. "\t\t\t\t\t\t\t\t</td>\n"
		. "\t\t\t\t\t\t\t\t<td valign='bottom'><label for='answer$myfname2'>\n"
		. "\t\t\t\t\t\t\t\t\t<input class='text' type='text' size='40' name='$myfname2' id='answer$myfname2' title='".$clang->gT("Make a comment on your choice here:")."' value='";
		// --> END NEW FEATURE - SAVE

		if (isset($_SESSION[$myfname2])) {$answer .= htmlspecialchars($_SESSION[$myfname2],ENT_QUOTES);}
		// --> START NEW FEATURE - SAVE
		$answer .= "'  />\n";

		if ($maxansw > 0)
		{
			$maxanswscript .= "\t\t\t\t\tif (document.getElementById('answer".$myfname."').value != '') { count += 1; }\n"; 
		}

		$answer .= "\t\t\t\t\t\t\t\t</label></td>\n"
		. "\t\t\t\t\t\t\t</tr>\n";
		// --> END NEW FEATURE - SAVE

		$inputnames[]=$myfname;
		$inputnames[]=$myfname2;
	}
	$answer .= "\t\t\t\t\t\t</table>\n"
	. "\t\t\t\t\t</td>\n"
	. "\t\t\t\t\t<td>&nbsp;</td>\n"
	. "\t\t\t\t</tr>\n"
	. "\t\t\t</table>\n";

	if ( $maxansw > 0 )
	{
		$maxanswscript .= "\t\t\t\t\tif (count > max)\n"
			. "\t\t\t\t\t\t{\n"
			. "\t\t\t\t\t\talert('".$clang->gT("Please choose at most","js")." ' + max + ' ".$clang->gT("answer(s) for question","js")." \"".trim(javascript_escape($ia[3],true,true))."\"');\n"
			. "\t\t\t\t\t\tif (me.type == 'checkbox') {me.checked = false;}\n"
			. "\t\t\t\t\t\tif (me.type == 'text') {\n"
			. "\t\t\t\t\t\t\tme.value = '';\n"
			. "\t\t\t\t\t\t\tif (document.getElementById(me.name + 'cbox') ){\n"
			. "\t\t\t\t\t\t\t\t document.getElementById(me.name + 'cbox').checked = false;\n"
			. "\t\t\t\t\t\t\t}\n"
			. "\t\t\t\t\t\t}"
			. "\t\t\t\t\t\treturn max;\n"
			. "\t\t\t\t\t\t}\n"
			. "\t\t\t\t\t}\n"
			. "\t\t\t//-->\n"
			. "\t\t\t</script>\n";
		$answer = $maxanswscript . $answer;
	}

	return array($answer, $inputnames);
}

function do_multipleshorttext($ia)
{
	global $dbprefix, $clang;
	$qidattributes=getQuestionAttributes($ia[0]);
    if (arraySearchByKey("numbers_only", $qidattributes, "attribute", 1)) {
        $numbersonly = "onkeypress=\"return goodchars(event,'0123456789.')\"";
    } else {
        $numbersonly = "";
    }
    if ($maxchars=arraySearchByKey("maximum_chars", $qidattributes, "attribute", 1))
    {
        $maxsize=$maxchars['value'];
    } else {
        $maxsize=255;
    }
	if ($prefix=arraySearchByKey("prefix", $qidattributes, "attribute", 1))
	{
	    $prefix = $prefix['value'];
	} else {
	    $prefix = "";
	}
	if ($suffix=arraySearchByKey("suffix", $qidattributes, "attribute", 1))
	{
	    $suffix = $suffix['value'];
	} else {
	    $suffix = "";
	}
	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery);    //Checked
	$anscount = $ansresult->RecordCount()*2;
	//$answer .= "\t\t\t\t\t<input type='hidden' name='MULTI$ia[1]' value='$anscount'>\n";
	$fn = 1;
    $answer = keycontroljs();
	$answer .= "\t\t\t\t\t\t<table class='question'>\n";
	if ($anscount==0) 
	   {
		  $inputnames=array();
		  $answer.="<tr><td class='answertext'>".$clang->gT("Error: This question has no answers.")."</td></tr>\n";
	   }
    else 
    {
	 	while ($ansrow = $ansresult->FetchRow())
		{
			$myfname = $ia[1].$ansrow['code'];
			$answer .= "\t\t\t\t\t\t\t<tr>\n"
			. "\t\t\t\t\t\t\t\t<td align='right' class='answertext'>\n"
			. "\t\t\t\t\t\t\t\t\t<label for='answer$myfname'>{$ansrow['answer']}</label>\n"
			. "\t\t\t\t\t\t\t\t</td>\n"
			. "\t\t\t\t\t\t\t\t<td align='left'>\n"
			. "\t\t\t\t\t\t\t\t\t$prefix<input class='text' type='text' size='40' name='$myfname' id='answer$myfname' value='";
			if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
	
			// --> START NEW FEATURE - SAVE
			$answer .= "' onchange='checkconditions(this.value, this.name, this.type);' $numbersonly maxlength='$maxsize'/>$suffix\n"
			. "\t\t\t\t\t\t\t\t</td>\n"
			. "\t\t\t\t\t\t\t</tr>\n";
			// --> END NEW FEATURE - SAVE
	
			$fn++;
			$inputnames[]=$myfname;
		}
	}
	$answer .= "\t\t\t\t\t\t</table>\n";
	return array($answer, $inputnames);
}

function do_multiplenumeric($ia)
{
	global $dbprefix, $clang;
	$qidattributes=getQuestionAttributes($ia[0]);
	//Must turn on the "numbers only javascript"
	$numbersonly = "onkeypress=\"return goodchars(event,'0123456789.')\"";
    if ($maxchars=arraySearchByKey("maximum_chars", $qidattributes, "attribute", 1))
    {
        $maxsize=$maxchars['value'];
    } else {
        $maxsize=255;
    }
    if ($equalvalue=arraySearchByKey("equals_num_value", $qidattributes, "attribute", 1))
    {
	    $equals_num_value=$equalvalue['value'];
	    $numbersonlyonblur[]="calculateValue".$ia[1]."(3)";
	    $calculateValue[]=3;
	} else {
	    $equals_num_value[]=0;
	}
    if ($minvalue=arraySearchByKey("min_num_value", $qidattributes, "attribute", 1))
    {
	    $min_num_value=$minvalue['value'];
	    $numbersonlyonblur[]="calculateValue".$ia[1]."(2)";
	    $calculateValue[]=2;
	} else {
	    $min_num_value=0;
	}
    if ($maxvalue=arraySearchByKey("max_num_value", $qidattributes, "attribute", 1))
    {
	    $max_num_value = $maxvalue['value'];
        $numbersonlyonblur[]="calculateValue".$ia[1]."(1)"; 
	    $calculateValue[]=1;
	} else {
	    $max_num_value = 0;
	}
	if ($prefix=arraySearchByKey("prefix", $qidattributes, "attribute", 1))
	{
	    $prefix = $prefix['value'];
	} else {
	    $prefix = "";
	}
	if ($suffix=arraySearchByKey("suffix", $qidattributes, "attribute", 1))
	{
	    $suffix = $suffix['value'];
	} else {
	    $suffix = "";
	}
	if(!empty($numbersonlyonblur))
	{
	    $numbersonly .= " onblur=\"".implode(";", $numbersonlyonblur)."\"";
	}
	if ($maxchars=arraySearchByKey("text_input_width", $qidattributes, "attribute", 1))
	{
		$tiwidth=$maxchars['value'];
	}
	else
	{
		$tiwidth=10;
	}
    if ($hidetip=arraySearchByKey("hide_tip", $qidattributes, "attribute", 1))
    {
        $hidetip=$hidetip['value'];
    } else {
        $hidetip=0;
    }
	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery);       //Checked
	$anscount = $ansresult->RecordCount()*2;
	//$answer .= "\t\t\t\t\t<input type='hidden' name='MULTI$ia[1]' value='$anscount'>\n";
	$fn = 1;
    $answer = keycontroljs();
	$answer .= "\t\t\t\t\t\t<table class='question'>\n";
	if ($anscount==0) 
	   {
		  $inputnames=array();
		  $answer.="<tr><td class='answertext'>".$clang->gT("Error: This question has no answers.")."</td></tr>\n";
	   }
    else 
    {
	 	while ($ansrow = $ansresult->FetchRow())
		{
			$myfname = $ia[1].$ansrow['code'];
			$answer .= "\t\t\t\t\t\t\t<tr>\n"
			. "\t\t\t\t\t\t\t\t<td align='right' class='answertext'>\n"
			. "\t\t\t\t\t\t\t\t\t<label for='answer$myfname'>{$ansrow['answer']}</label>\n"
			. "\t\t\t\t\t\t\t\t</td>\n"
			. "\t\t\t\t\t\t\t\t<td align='left'>\n"
			. "\t\t\t\t\t\t\t\t\t$prefix<input class='text' type='text' size='$tiwidth' name='$myfname' id='answer$myfname' value='";
			if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
	
			// --> START NEW FEATURE - SAVE
			$answer .= "' onchange='checkconditions(this.value, this.name, this.type);' $numbersonly maxlength='$maxsize'/>$suffix\n"
			. "\t\t\t\t\t\t\t\t</td>\n"
			. "\t\t\t\t\t\t\t</tr>\n";
			// --> END NEW FEATURE - SAVE
	
			$fn++;
			$inputnames[]=$myfname;
		}
		if($hidetip == 0) 
		{
		    $answer .= "<br />\t\t\t<font size='1'><i>".$clang->gT("Only numbers may be entered in these fields")."</i></font>\n";
        }
        if ($maxvalue)
            {
			$answer .= "\t\t\t<div id='max_num_value_{$ia[1]}'><font size='1'><i>".$clang->gT("Total of all entries must not exceed ").$max_num_value."</i></font></div>\n";
			}
        if ($equalvalue)
            {
			$answer .= "\t\t\t<div id='equals_num_value_{$ia[1]}'><font size='1'><i>".$clang->gT("Total of all entries must equal ").$equals_num_value."</i></font></div>\n";
			}
        if ($minvalue)
            {
			$answer .= "\t\t\t<div id='min_num_value_{$ia[1]}'><font size='1'><i>".$clang->gT("Total of all entries must be at least ").$min_num_value."</i></font></div>\n";
			}
		if ($maxvalue || $equalvalue || $minvalue)
		    {
	    	$answer .= "\t\t\t\t\t\t<tr><td colspan='2'><table class='question' style='border: 1px solid #111111'>\n";
			$answer .= "<tr><td align='right' class='answertext'>".$clang->gT("Total: ")."</td><td>$prefix<input type='text' id='totalvalue_{$ia[1]}' disabled style='border: 0px' size='$tiwidth'>$suffix</td></tr>\n";
    		if ($equalvalue)
    		    {
    			$answer .= "<tr><td align='right' class='answertext'>".$clang->gT("Remaining: ")."</td><td>$prefix<input type='text' id='remainingvalue_{$ia[1]}' disabled style='border: 0px' size='$tiwidth'>$suffix</td></tr>\n";
    			}
			$answer .= "</table></td></tr>\n";
			}
	}
	$answer .= "\t\t\t\t\t\t</table>\n";
	
	if ($maxvalue || $equalvalue || $minvalue) 
    { //Do value validation
	    $answer .= "<input type='hidden' name='qattribute_answer[]' value='".$ia[1]."'>\n";
		$answer .= "<input type='hidden' name='qattribute_answer".$ia[1]."'>\n";

	    $answer .= "<script type='text/javascript'>\n";
	    $answer .= "    function calculateValue".$ia[1]."(method) {\n";
	    //Make all empty fields 0 (or else calculation won't work
	    foreach ($inputnames as $inputname)
	    {
		    $answer .= "       if(document.limesurvey.answer".$inputname.".value == '') { document.limesurvey.answer".$inputname.".value = 0; }\n";
            $javainputnames[]="parseInt(parseFloat(document.limesurvey.answer".$inputname.".value)*1000)"; 
		}
	    $answer .= "       bob = eval('document.limesurvey.qattribute_answer".$ia[1]."');\n";
	    $answer .= "       totalvalue_".$ia[1]."=(";
	    $answer .= implode(" + ", $javainputnames);
	    $answer .= ")/1000;\n";
	    $answer .= "       document.getElementById('totalvalue_{$ia[1]}').value=parseFloat(totalvalue_{$ia[1]});\n";
	    $answer .= "       switch(method)\n";
	    $answer .= "       {\n";
	    $answer .= "       case 1:\n";
	    $answer .= "          if (totalvalue_".$ia[1]." > $max_num_value)\n";
	    $answer .= "             {\n";
	    $answer .= "               bob.value = '".$clang->gT("Answer is invalid. The total of all entries should not add up to more than ").$max_num_value."';\n";
	    $answer .= "               document.getElementById('totalvalue_{$ia[1]}').style.color='red';\n";
	    $answer .= "               document.getElementById('max_num_value_{$ia[1]}').style.color='red';\n";
		$answer .= "             }\n";
		$answer .= "             else\n";
		$answer .= "             {\n";
		$answer .= "               if (bob.value == '' || bob.value == '".$clang->gT("Answer is invalid. The total of all entries should not add up to more than ").$max_num_value."')\n";
		$answer .= "               {\n";
		$answer .= "                 bob.value = '';\n";
	    $answer .= "                 document.getElementById('totalvalue_{$ia[1]}').style.color='black';\n";
		$answer .= "               }\n";
	    $answer .= "               document.getElementById('max_num_value_{$ia[1]}').style.color='black';\n";
		$answer .= "             }\n";
		$answer .= "          break;\n";
		$answer .= "       case 2:\n";
	    $answer .= "          if (totalvalue_".$ia[1]." < $min_num_value)\n";
	    $answer .= "             {\n";
	    $answer .= "               bob.value = '".$clang->gT("Answer is invalid. The total of all entries should add up to at least ").$min_num_value."';\n";
	    $answer .= "               document.getElementById('totalvalue_".$ia[1]."').style.color='red';\n";
	    $answer .= "               document.getElementById('min_num_value_".$ia[1]."').style.color='red';\n";
		$answer .= "             }\n";
		$answer .= "             else\n";
		$answer .= "             {\n";
		$answer .= "               if (bob.value == '' || bob.value == '".$clang->gT("Answer is invalid. The total of all entries should add up to at least ").$min_num_value."')\n";
		$answer .= "               {\n";
		$answer .= "                 bob.value = '';\n";
	    $answer .= "                 document.getElementById('totalvalue_".$ia[1]."').style.color='black';\n";
		$answer .= "               }\n";
	    $answer .= "               document.getElementById('min_num_value_".$ia[1]."').style.color='black';\n";
		$answer .= "             }\n";
		$answer .= "          break;\n";
		$answer .= "       case 3:\n";
		$answer .= "          remainingvalue = (parseInt(parseFloat($equals_num_value)*1000) - parseInt(parseFloat(totalvalue_".$ia[1].")*1000))/1000;\n";
		$answer .= "          document.getElementById('remainingvalue_".$ia[1]."').value=remainingvalue;\n";
	    $answer .= "          if (totalvalue_".$ia[1]." == $equals_num_value)\n";
		$answer .= "             {\n";
		$answer .= "               if (bob.value == '' || bob.value == '".$clang->gT("Answer is invalid. The total of all entries should not add up to more than ").$equals_num_value."')\n";
		$answer .= "               {\n";
		$answer .= "                 bob.value = '';\n";
	    $answer .= "                 document.getElementById('totalvalue_".$ia[1]."').style.color='black';\n";
	    $answer .= "                 document.getElementById('equals_num_value_".$ia[1]."').style.color='black';\n";
		$answer .= "               }\n";
		$answer .= "             }\n";
		$answer .= "             else\n";
		$answer .= "             {\n";
	    $answer .= "             bob.value = '".$clang->gT("Answer is invalid. The total of all entries should not add up to more than ").$equals_num_value."';\n";
	    $answer .= "             document.getElementById('totalvalue_".$ia[1]."').style.color='red';\n";
	    $answer .= "             document.getElementById('equals_num_value_".$ia[1]."').style.color='red';\n";
		$answer .= "             }\n";
		$answer .= "             break;\n";
		$answer .= "       }\n";
		$answer .= "    }\n";
	    foreach($calculateValue as $cValue) 
		{
		    $answer .= "    calculateValue".$ia[1]."($cValue);\n";
		}
		$answer .= "</script>\n";
	    
	}
	
	return array($answer, $inputnames);
}

function do_numerical($ia)
{
	global $clang;
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($prefix=arraySearchByKey("prefix", $qidattributes, "attribute", 1))
	{
	    $prefix = $prefix['value'];
	} else {
	    $prefix = "";
	}
	if ($suffix=arraySearchByKey("suffix", $qidattributes, "attribute", 1))
	{
	    $suffix = $suffix['value'];
	} else {
	    $suffix = "";
	}
	if ($maxchars=arraySearchByKey("maximum_chars", $qidattributes, "attribute", 1))
	{
		$maxsize=$maxchars['value'];
        if ($maxsize>20) {$maxsize=20;}
	}
	else
	{
		$maxsize=20;  // The field length for numerical fields is 20
	}
	if ($maxchars=arraySearchByKey("text_input_width", $qidattributes, "attribute", 1))
	{
		$tiwidth=$maxchars['value'];
	}
	else
	{
		$tiwidth=10;
	}
	// --> START NEW FEATURE - SAVE
	$answer = keycontroljs()
	. "\t\t\t$prefix<input class='text' type='text' size='$tiwidth' name='$ia[1]' "
	. "id='answer{$ia[1]}' value=\"{$_SESSION[$ia[1]]}\" onkeypress=\"return goodchars(event,'0123456789.')\" onkeyup='checkconditions(this.value, this.name, this.type)'"
	. "maxlength='$maxsize' />$suffix<br />\n"
	. "\t\t\t<font size='1'><i>".$clang->gT("Only numbers may be entered in this field")."</i></font>\n";
	// --> END NEW FEATURE - SAVE

	$inputnames[]=$ia[1];
	$mandatory=null;
	return array($answer, $inputnames, $mandatory);
}

function do_shortfreetext($ia)
{
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($maxchars=arraySearchByKey("maximum_chars", $qidattributes, "attribute", 1))
	{
		$maxsize=$maxchars['value'];
	}
	else
	{
		$maxsize=255;
	}
	if ($maxchars=arraySearchByKey("text_input_width", $qidattributes, "attribute", 1))
	{
		$tiwidth=$maxchars['value'];
	}
	else
	{
		$tiwidth=50;
	}
	if ($prefix=arraySearchByKey("prefix", $qidattributes, "attribute", 1))
	{
	    $prefix = $prefix['value'];
	} else {
	    $prefix = "";
	}
	if ($suffix=arraySearchByKey("suffix", $qidattributes, "attribute", 1))
	{
	    $suffix = $suffix['value'];
	} else {
	    $suffix = "";
	}
	// --> START NEW FEATURE - SAVE
	$answer = "\t\t\t$prefix<input class='text' type='text' size='$tiwidth' name='$ia[1]' id='answer$ia[1]' value=\""
	.str_replace ("\"", "'", str_replace("\\", "", $_SESSION[$ia[1]]))
	."\" maxlength='$maxsize' onkeyup='checkconditions(this.value, this.name, this.type)'/>$suffix\n";
	// --> END NEW FEATURE - SAVE

	$inputnames[]=$ia[1];
	return array($answer, $inputnames);
}

function do_longfreetext($ia)
{
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($maxchars=arraySearchByKey("maximum_chars", $qidattributes, "attribute", 1))
	{
		$maxsize=$maxchars['value'];
	}
	else
	{
		$maxsize=65525;
	}

	// --> START ENHANCEMENT - DISPLAY ROWS
	if ($displayrows=arraySearchByKey("display_rows", $qidattributes, "attribute", 1))
	{
		$drows=$displayrows['value'];
	}
	else
	{
		$drows=5;
	}
	// <-- END ENHANCEMENT - DISPLAY ROWS

	// --> START ENHANCEMENT - TEXT INPUT WIDTH
	if ($maxchars=arraySearchByKey("text_input_width", $qidattributes, "attribute", 1))
	{
		$tiwidth=$maxchars['value'];
	}
	else
	{
		$tiwidth=40;
	}
	// <-- END ENHANCEMENT - TEXT INPUT WIDTH


	$answer = "<script type='text/javascript'>
               <!--
               function textLimit(field, maxlen) {
                if (document.getElementById(field).value.length > maxlen)
                document.getElementById(field).value = document.getElementById(field).value.substring(0, maxlen);
                }
               //-->
               </script>\n";

	// --> START ENHANCEMENT - DISPLAY ROWS
	// --> START ENHANCEMENT - TEXT INPUT WIDTH

	// --> START NEW FEATURE - SAVE
	$answer .= "<textarea class='textarea' name='{$ia[1]}' id='answer{$ia[1]}' "
	."rows='{$drows}' cols='{$tiwidth}' onkeyup=\"textLimit('answer".$ia[1]."', $maxsize); checkconditions(this.value, this.name, this.type)\">";
	// --> END NEW FEATURE - SAVE

	// <-- END ENHANCEMENT - TEXT INPUT WIDTH
	// <-- END ENHANCEMENT - DISPLAY ROWS

	if ($_SESSION[$ia[1]]) {$answer .= str_replace("\\", "", $_SESSION[$ia[1]]);}

	$answer .= "</textarea>\n";

	$inputnames[]=$ia[1];
	return array($answer, $inputnames);
}

function do_hugefreetext($ia)
{
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($maxchars=arraySearchByKey("maximum_chars", $qidattributes, "attribute", 1))
	{
		$maxsize=$maxchars['value'];
	}
	else
	{
		$maxsize=65525;
	}

	// --> START ENHANCEMENT - DISPLAY ROWS
	if ($displayrows=arraySearchByKey("display_rows", $qidattributes, "attribute", 1))
	{
		$drows=$displayrows['value'];
	}
	else
	{
		$drows=30;
	}
	// <-- END ENHANCEMENT - DISPLAY ROWS

	// --> START ENHANCEMENT - TEXT INPUT WIDTH
	if ($maxchars=arraySearchByKey("text_input_width", $qidattributes, "attribute", 1))
	{
		$tiwidth=$maxchars['value'];
	}
	else
	{
		$tiwidth=70;
	}
	// <-- END ENHANCEMENT - TEXT INPUT WIDTH

	$answer = "<script type='text/javascript'>
               <!--
               function textLimit(field, maxlen) {
                if (document.getElementById(field).value.length > maxlen)
                document.getElementById(field).value = document.getElementById(field).value.substring(0, maxlen);
                }
               //-->
               </script>\n";
	// --> START ENHANCEMENT - DISPLAY ROWS
	// --> START ENHANCEMENT - TEXT INPUT WIDTH

	// --> START NEW FEATURE - SAVE
	$answer .= "<textarea class='display' name='{$ia[1]}' id='answer$ia[1]' "
	."rows='{$drows}' cols='{$tiwidth}' onkeyup=\"textLimit('answer".$ia[1]."', $maxsize); checkconditions(this.value, this.name, this.type)\">";
	// --> END NEW FEATURE - SAVE

	// <-- END ENHANCEMENT - TEXT INPUT WIDTH
	// <-- END ENHANCEMENT - DISPLAY ROWS

	if ($_SESSION[$ia[1]]) {$answer .= str_replace("\\", "", $_SESSION[$ia[1]]);}

	$answer .= "</textarea>\n";
	$inputnames[]=$ia[1];
	return array($answer, $inputnames);
}

function do_yesno($ia)
{
	global $shownoanswer, $clang;
	$answer = "\t\t\t<table class='question'>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td align='left'>\n"
	. "\t\t\t\t\t\t<input class='radio' type='radio' name='$ia[1]' id='answer$ia[1]Y' value='Y'";
	if ($_SESSION[$ia[1]] == "Y") {$answer .= " checked='checked'";}
	// --> START NEW FEATURE - SAVE
	$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /><label for='answer$ia[1]Y' class='answertext'>".$clang->gT("Yes")."</label><br />\n"
	. "\t\t\t\t\t\t<input class='radio' type='radio' name='$ia[1]' id='answer$ia[1]N' value='N'";
	// --> END NEW FEATURE - SAVE

	if ($_SESSION[$ia[1]] == "N") {$answer .= " checked='checked'";}
	// --> START NEW FEATURE - SAVE
	$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /><label for='answer$ia[1]N' class='answertext'>".$clang->gT("No")."</label><br />\n";
	// --> END NEW FEATURE - SAVE

	if ($ia[6] != "Y" && $shownoanswer == 1)
	{
		$answer .= "\t\t\t\t\t\t<input class='radio' type='radio' name='$ia[1]' id='answer$ia[1] ' value=''";
		if ($_SESSION[$ia[1]] == "")
		{
			$answer .= " checked='checked'";
		}
		// --> START NEW FEATURE - SAVE
		$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /><label for='answer$ia[1] ' class='answertext'>".$clang->gT("No answer")."</label><br />\n";
		// --> END NEW FEATURE - SAVE
	}
    
	$answer .= "\t\t\t\t<input type='hidden' name='java$ia[1]' id='java$ia[1]' value='{$_SESSION[$ia[1]]}' />\n"
	. "\t\t\t\t\t</td>\n"
	. "\t\t\t\t</tr>\n"
	. "\t\t\t</table>\n";
	$inputnames[]=$ia[1];
	return array($answer, $inputnames);
}

function do_gender($ia)
{
	global $shownoanswer, $clang;
	
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($displaycols=arraySearchByKey("display_columns", $qidattributes, "attribute", 1))
	{
		$dcols=$displaycols['value'];
	}
	else
	{
		$dcols=0;
	}
	
	$answer = "\t\t\t<table class='question'>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td align='left'>\n"
	. "\t\t\t\t\t\t<input class='radio' type='radio' name='$ia[1]' id='answer$ia[1]F' value='F'";
	if ($_SESSION[$ia[1]] == "F") {$answer .= " checked='checked'";}
	// --> START NEW FEATURE - SAVE
	$answer .= " onclick='checkconditions(this.value, this.name, this.type)' />"
	. "<label for='answer$ia[1]F' class='answertext'>".$clang->gT("Female")."</label>\n";
	if ($dcols > 1 ) //Break into columns - don't need any detailed calculations becauase there's only ever 2 possible columns
	{
	    $answer .= "\n</td><td>\n";
    } else {
	    $answer .= "<br />\n";
	}	
	$answer .= "\t\t\t\t\t\t<input class='radio' type='radio' name='$ia[1]' id='answer$ia[1]M' value='M'";
	// --> END NEW FEATURE - SAVE

	if ($_SESSION[$ia[1]] == "M") {$answer .= " checked='checked'";}
	// --> START NEW FEATURE - SAVE
	$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /><label for='answer$ia[1]M' class='answertext'>".$clang->gT("Male")."</label>\n";
	// --> END NEW FEATURE - SAVE

	if ($ia[6] != "Y" && $shownoanswer == 1)
	{
        if ($dcols > 2)
        {
		  $answer .= "\n</td><td>\n";
		} elseif ($dcols > 1) {
		  $answer .= "\n</td></tr><tr><td colspan='2' align='center'>\n";
		} else {
		  $answer .= "<br />";
		}
		$answer .= "\t\t\t\t\t\t<input class='radio' type='radio' name='$ia[1]' id='answer$ia[1] ' value=''";
		if ($_SESSION[$ia[1]] == "")
		{
			$answer .= " checked='checked'";
		}
		// --> START NEW FEATURE - SAVE
		$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /><label for='answer$ia[1] ' class='answertext'>".$clang->gT("No answer")."</label>\n";
		// --> END NEW FEATURE - SAVE

	}
	$answer .= "\t\t\t\t<input type='hidden' name='java$ia[1]' id='java$ia[1]' value='{$_SESSION[$ia[1]]}' />\n"
	. "\t\t\t\t\t</td>\n"
	. "\t\t\t\t</tr>\n"
	. "\t\t\t</table>\n";
	$inputnames[]=$ia[1];
	return array($answer, $inputnames);
}

function do_array_5point($ia)
{
	global $dbprefix, $shownoanswer, $notanswered, $thissurvey, $clang;
	
	$ansquery = "SELECT answer FROM {$dbprefix}answers WHERE qid=".$ia[0]." AND answer like '%|%'";
	$ansresult = db_execute_assoc($ansquery);   //Checked
    if ($ansresult->RecordCount()>0) {$right_exists=true;} else {$right_exists=false;} 
	// $right_exists is a flag to find out if there are any right hand answer parts. If there arent we can leave out the right td column

	$qidattributes=getQuestionAttributes($ia[0]);
	if ($answerwidth=arraySearchByKey("answer_width", $qidattributes, "attribute", 1)) {
		$answerwidth=$answerwidth['value'];
	} else {
		$answerwidth=20;
	}

	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery);     //Checked
	$anscount = $ansresult->RecordCount();

	$fn = 1;
	$answer = "\t\t\t<table class='question'>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td width='$answerwidth%'></td>\n";
	for ($xc=1; $xc<=5; $xc++)
	{
		$answer .= "\t\t\t\t\t<td class='array1'>$xc</td>\n";
	}
	if ($right_exists) {$answer .= "<td>&nbsp;</td>";} 
	if ($ia[6] != "Y" && $shownoanswer == 1) //Question is not mandatory
	{
		$answer .= "\t\t\t\t\t<td class='array1'>".$clang->gT("No answer")."</td>\n";
	}
	$answer .= "\t\t\t\t</tr>\n";
	while ($ansrow = $ansresult->FetchRow())
	{
		$myfname = $ia[1].$ansrow['code'];

		$answertext=answer_replace($ansrow['answer']);
		if (strpos($answertext,'|')) {$answertext=substr($answertext,0,strpos($answertext,'|'));}

		/* Check if this item has not been answered: the 'notanswered' variable must be an array,
		containing a list of unanswered questions, the current question must be in the array,
		and there must be no answer available for the item in this session. */
		if ((is_array($notanswered)) && (array_search($ia[1], $notanswered) !== FALSE) && ($_SESSION[$myfname] == "") ) {
			$answertext = "<span class='errormandatory'>{$answertext}</span>";
		}
		if (!isset($trbc) || $trbc == "array1" || !$trbc) {$trbc = "array2";} else {$trbc = "array1";}
		$htmltbody2 = "";
		if (($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == false)  || ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "A"))
		{
			$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off'  />";
		} else if (($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "S") || ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == true))
		{
			$selected = getArrayFiltersForQuestion($ia[0]);
			if (!in_array($ansrow['code'],$selected))
			{
				$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
				$_SESSION[$myfname] = "";
			} else
			{
				$htmltbody2 = "<tbody id='javatbd$myfname' style='display: '><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='on' />";
			}
		}
		$answer .= "\t\t\t\t$htmltbody2<tr class='$trbc'>\n"
		. "\t\t\t\t\t<td align='right' class='answertext' width='$answerwidth%'>$answertext\n"
		. "\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
		if (isset($_SESSION[$myfname])){$answer .= $_SESSION[$myfname];}
		$answer .= "' /></td>\n";
		for ($i=1; $i<=5; $i++)
		{
			$answer .= "\t\t\t\t\t<td><label for='answer$myfname-$i'>"
			."<input class='radio' type='radio' name='$myfname' id='answer$myfname-$i' value='$i' title='$i'";
			if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $i) {$answer .= " checked='checked'";}
			$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n";
		}

		$answertext2=answer_replace($ansrow['answer']);
		if (strpos($answertext2,'|')) 
		   {
			  $answertext2=substr($answertext2,strpos($answertext2,'|')+1);
			  $answer .= "\t\t\t\t\t<td class='answertextright' width='$answerwidth%'>$answertext2</td>\n";
           } 
		elseif 
		    ($right_exists)  {$answer .= "\t\t\t\t\t<td class='answertextright'>&nbsp</td>\n";}

		
		if ($ia[6] != "Y" && $shownoanswer == 1)
		{
			$answer .= "\t\t\t\t\t<td align='center'><label for='answer$myfname-'>"
			."<input class='radio' type='radio' name='$myfname' id='answer$myfname-' value='' title='".$clang->gT("No answer")."'";
			if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == "")
			{
				$answer .= " checked='checked'";
			}
			$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n";
		}
		
    	
		$answer .= "\t\t\t\t</tr>\n";
		$fn++;
		$inputnames[]=$myfname;
	}

	$answer .= "\t\t\t</table>\n";
	return array($answer, $inputnames);
}

function do_array_10point($ia)
{
	global $dbprefix, $shownoanswer, $notanswered, $thissurvey, $clang;
	$qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]."  AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);      //Checked
	while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}

	$qidattributes=getQuestionAttributes($ia[0]);
	if ($answerwidth=arraySearchByKey("answer_width", $qidattributes, "attribute", 1)) {
		$answerwidth=$answerwidth['value'];
	} else {
		$answerwidth=20;
	}

	if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
		$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery);   //Checked
	$anscount = $ansresult->RecordCount();

	$fn = 1;
	$answer = "\t\t\t<table class='question'>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td width='$answerwidth%'></td>\n";
	for ($xc=1; $xc<=10; $xc++)
	{
		$answer .= "\t\t\t\t\t<td class='array1'>$xc</td>\n";
	}
	if ($ia[6] != "Y" && $shownoanswer == 1) //Question is not mandatory
	{
		$answer .= "\t\t\t\t\t<td  class='array1'>".$clang->gT("No answer")."</td>\n";
	}
	$answer .= "\t\t\t\t</tr>\n";
	while ($ansrow = $ansresult->FetchRow())
	{
		$myfname = $ia[1].$ansrow['code'];
		$answertext=answer_replace($ansrow['answer']);
		/* Check if this item has not been answered: the 'notanswered' variable must be an array,
		containing a list of unanswered questions, the current question must be in the array,
		and there must be no answer available for the item in this session. */
		if ((is_array($notanswered)) && (array_search($ia[1], $notanswered) !== FALSE) && ($_SESSION[$myfname] == "") ) {
			$answertext = "<span class='errormandatory'>{$answertext}</span>";
		}
		if (!isset($trbc) || $trbc == "array1" || !$trbc) {$trbc = "array2";} else {$trbc = "array1";}
		$htmltbody2 = "";
		if ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == false)
		{
			$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
		} else if (($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "S") || ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == true))
		{
			$selected = getArrayFiltersForQuestion($ia[0]);
			if (!in_array($ansrow['code'],$selected))
			{
				$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
				$_SESSION[$myfname] = "";
			} else
			{
				$htmltbody2 = "<tbody id='javatbd$myfname' style='display: '><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='on' />";
			}
		}
		$answer .= "\t\t\t\t$htmltbody2<tr class='$trbc'>\n"
		. "\t\t\t\t\t<td align='right' class='answertext'>$answertext\n"
		. "\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
		if (isset($_SESSION[$myfname])){$answer .= $_SESSION[$myfname];}
		$answer .= "' /></td>\n";

		for ($i=1; $i<=10; $i++)
		{
			$answer .= "\t\t\t\t\t\t<td><label for='answer$myfname-$i'>"
			."<input class='radio' type='radio' name='$myfname' id='answer$myfname-$i' value='$i' title='$i'";
			if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $i) {$answer .= " checked='checked'";}
			// --> START NEW FEATURE - SAVE
			$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n";
			// --> END NEW FEATURE - SAVE

		}
		if ($ia[6] != "Y" && $shownoanswer == 1)
		{
			$answer .= "\t\t\t\t\t<td align='center'><label for='answer$myfname-'>"
			."<input class='radio' type='radio' name='$myfname' id='answer$myfname-' value='' title='".$clang->gT("No answer")."'";
			if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == "")
			{
				$answer .= " checked='checked'";
			}
			$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n";

		}
		$answer .= "\t\t\t\t</tr>\n";
		$inputnames[]=$myfname;
		$fn++;
	}
	$answer .= "\t\t\t</table>\n";
	return array($answer, $inputnames);
}

function do_array_yesnouncertain($ia)
{
	global $dbprefix, $shownoanswer, $notanswered, $thissurvey, $clang;
	$qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);        //Checked
	while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($answerwidth=arraySearchByKey("answer_width", $qidattributes, "attribute", 1)) {
		$answerwidth=$answerwidth['value'];
	} else {
		$answerwidth=20;
	}
    if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
	    $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
	    $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery);     //Checked
	$anscount = $ansresult->RecordCount();
	$fn = 1;
	$answer = "\t\t\t<table class='question'>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td width='$answerwidth%'></td>\n"
	. "\t\t\t\t\t<td class='array1'>".$clang->gT("Yes")."</td>\n"
	. "\t\t\t\t\t<td class='array1'>".$clang->gT("Uncertain")."</td>\n"
	. "\t\t\t\t\t<td class='array1'>".$clang->gT("No")."</td>\n";
	if ($ia[6] != "Y" && $shownoanswer == 1) //Question is not mandatory
	{
		$answer .= "\t\t\t\t\t<td  class='array1'>".$clang->gT("No answer")."</td>\n";
	}
	$answer .= "\t\t\t\t</tr>\n";
	
	if ($anscount==0) 
	   {
		  $inputnames=array();
		  $answer.="<tr><td class='answertext'>".$clang->gT("Error: This question has no answers.")."</td></tr>\n";
	   }
	else
	{
		while ($ansrow = $ansresult->FetchRow())
		{
			$myfname = $ia[1].$ansrow['code'];
			$answertext=answer_replace($ansrow['answer']);
			/* Check if this item has not been answered: the 'notanswered' variable must be an array,
			containing a list of unanswered questions, the current question must be in the array,
			and there must be no answer available for the item in this session. */
			if ((is_array($notanswered)) && (array_search($ia[1], $notanswered) !== FALSE) && ($_SESSION[$myfname] == "") ) {
				$answertext = "<span class='errormandatory'>{$answertext}</span>";
			}
			if (!isset($trbc) || $trbc == "array1") {$trbc = "array2";} else {$trbc = "array1";}
			$htmltbody2 = "";
			if ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == false)
			{
				$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
			} else if (($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "S") || ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == true))
			{
				$selected = getArrayFiltersForQuestion($ia[0]);
				if (!in_array($ansrow['code'],$selected))
				{
					$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
					$_SESSION[$myfname] = "";
				} else
				{
					$htmltbody2 = "<tbody id='javatbd$myfname' style='display: '><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='on' />";
				}
			}
			$answer .= "\t\t\t\t$htmltbody2<tr class='$trbc'>\n"
			. "\t\t\t\t\t<td align='right' class='answertext'>$answertext</td>\n"
			. "\t\t\t\t\t\t<td align='center'><label for='answer$myfname-Y'>"
			."<input class='radio' type='radio' name='$myfname' id='answer$myfname-Y' value='Y' title='".$clang->gT("Yes")."'";
			if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == "Y") {$answer .= " checked='checked'";}
			// --> START NEW FEATURE - SAVE
			$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n"
			. "\t\t\t\t\t\t<td align='center'><label for='answer$myfname-U'>"
			."<input class='radio' type='radio' name='$myfname' id='answer$myfname-U' value='U' title='".$clang->gT("Uncertain")."'";
			// --> END NEW FEATURE - SAVE
	
			if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == "U") {$answer .= " checked='checked'";}
			// --> START NEW FEATURE - SAVE
			$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n"
			. "\t\t\t\t\t\t<td align='center'><label for='answer$myfname-N'>"
			."<input class='radio' type='radio' name='$myfname' id='answer$myfname-N' value='N' title='".$clang->gT("No")."'";
			// --> END NEW FEATURE - SAVE
	
			if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == "N") {$answer .= " checked='checked'";}
			// --> START NEW FEATURE - SAVE
			$answer .= " onclick='checkconditions(this.value, this.name, this.type)' /></label>\n"
			. "\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
			// --> END NEW FEATURE - SAVE
			if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
			$answer .= "' /></td>\n";
	
			if ($ia[6] != "Y" && $shownoanswer == 1)
			{
				$answer .= "\t\t\t\t\t<td align='center'><label for='answer$myfname-'>"
				."<input class='radio' type='radio' name='$myfname' id='answer$myfname-' value='' title='".$clang->gT("No answer")."'";
				if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == "")
				{
					$answer .= " checked='checked'";
				}
				// --> START NEW FEATURE - SAVE
				$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n";
				// --> END NEW FEATURE - SAVE
			}
			$answer .= "\t\t\t\t</tr>\n";
			$inputnames[]=$myfname;
			$fn++;
		}
	}
	$answer .= "\t\t\t</table>\n";
	return array($answer, $inputnames);
}

/*function do_slider($ia)
{
	global $shownoanswer;
	global $dbprefix;

	$qidattributes=getQuestionAttributes($ia[0]);
	if ($defaultvalue=arraySearchByKey("default_value", $qidattributes, "attribute", 1)) {
		$defaultvalue=$defaultvalue['value'];
	} else {$defaultvalue=0;}
	if ($minimumvalue=arraySearchByKey("minimum_value", $qidattributes, "attribute", 1)) {
		$minimumvalue=$minimumvalue['value'];
	} else {
		$minimumvalue=0;
	}
	if ($maximumvalue=arraySearchByKey("maximum_value", $qidattributes, "attribute", 1)) {
		$maximumvalue=$maximumvalue['value'];
	} else {
		$maximumvalue=50;
	}
	if ($answerwidth=arraySearchByKey("answer_width", $qidattributes, "attribute", 1)) {
		$answerwidth=$answerwidth['value'];
	} else {
		$answerwidth=20;
	}
	$sliderwidth=100-$answerwidth;

	//Get answers
	$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$ia[0]}  AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	$ansresult = db_execute_assoc($ansquery);     //Checked
	$anscount = $ansresult->RecordCount();

	//Get labels
	$qquery = "SELECT lid FROM {$dbprefix}questions WHERE qid=".$ia[0]."  AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);      //Checked
	while($qrow = $qresult->FetchRow()) {$lid = $qrow['lid'];}
	$lquery = "SELECT * FROM {$dbprefix}labels WHERE lid=$lid  AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, code";
	$lresult = db_execute_assoc($lquery);     //Checked

	$answer = "\t\t\t<table class='question'>\n";
	$answer .= "\t\t\t\t<tr><th width='$answerwidth%'></th>\n";
	$lcolspan=$lresult->RecordCount();
	$lcount=1;
	while($lrow=$lresult->FetchRow()) {
		$answer .= "<th align='";
		if ($lcount == 1) {
			$answer .= "left";
		} elseif ($lcount == $lcolspan) {
			$answer .= "right";
		} else {
			$answer .= "center";
		}
		$answer .= "' class='array1'><font size='1'>".$lrow['title']."</font></th>\n";
		$lcount++;
	}
	$answer .= "\t\t\t\t</tr>\n";


	$answer .="\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td>\n"
	. "\t\t\t\t\t\t";
	$fn=1;
	while ($ansrow = $ansresult->FetchRow())
	{
		//A row for each slider control
		$myfname = $ia[1].$ansrow['code'];
		$answertext=answer_replace($ansrow['answer']);
		if (!isset($trbc) || $trbc == "array1") {$trbc = "array2";} else {$trbc = "array1";}
		$answer .= "\t\t\t\t<tr class='$trbc'>\n"
		. "\t\t\t\t\t<td align='right'>$answertext</td>\n";
		$answer .= "\t\t\t\t\t<td width='$sliderwidth%' colspan='$lcolspan'>"
		. "<div class=\"slider\" id=\"slider-$myfname\" style='width:100%'>"
		. "<input class=\"slider-input\" id=\"slider-input-$myfname\" name=\"$myfname\" />"
		. "</div>";
		$answer .= "
<script type=\"text/javascript\">

var s = new Slider(document.getElementById(\"slider-$myfname\"),
                   document.getElementById(\"slider-input-$myfname\"));
	s.setValue(";
		if (isset($_SESSION[$myfname])) {
			$answer .= $_SESSION[$myfname];
		} else {
			$answer .= $defaultvalue;
		}
		$answer .= ");
	s.setMinimum($minimumvalue);
	s.setMaximum($maximumvalue);
</script>\n"
		. "\n";
		$answer .= "\t\t\t\t\n"
		. "\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
		if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
		$answer .= "' />\n</td></tr>";
		$inputnames[]=$myfname;
		$fn++;
	}

	$answer .="\t\t\t\t\t</td>\n"
	. "\t\t\t\t</tr>\n"
	. "\t\t\t</table>\n";

	$inputnames[]=$ia[1];

	return array($answer, $inputnames);
}*/

function do_array_increasesamedecrease($ia)
{
	global $dbprefix, $thissurvey, $clang;
	global $shownoanswer;
	global $notanswered;
	$qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);   //Checked
	$qidattributes=getQuestionAttributes($ia[0]);
	if ($answerwidth=arraySearchByKey("answer_width", $qidattributes, "attribute", 1)) {
		$answerwidth=$answerwidth['value'];
	} else {
		$answerwidth=20;
	}
	while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}
    if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	} else {
	    $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	}
	$ansresult = db_execute_assoc($ansquery);  //Checked
	$anscount = $ansresult->RecordCount();

	$fn = 1;
	$answer = "\t\t\t<table class='question'>\n"
	. "\t\t\t\t<tr>\n"
	. "\t\t\t\t\t<td width='$answerwidth%'></td>\n"
	. "\t\t\t\t\t<td class='array1'>".$clang->gT("Increase")."</td>\n"
	. "\t\t\t\t\t<td class='array1'>".$clang->gT("Same")."</td>\n"
	. "\t\t\t\t\t<td class='array1'>".$clang->gT("Decrease")."</td>\n";
	if ($ia[6] != "Y" && $shownoanswer == 1) //Question is not mandatory
	{
		$answer .= "\t\t\t\t\t<td class='array1'>".$clang->gT("No answer")."</td>\n";
	}
	$answer .= "\t\t\t\t</tr>\n";
	while ($ansrow = $ansresult->FetchRow())
	{
		$myfname = $ia[1].$ansrow['code'];
		$answertext=answer_replace($ansrow['answer']);
		/* Check if this item has not been answered: the 'notanswered' variable must be an array,
		containing a list of unanswered questions, the current question must be in the array,
		and there must be no answer available for the item in this session. */
		if ((is_array($notanswered)) && (array_search($ia[1], $notanswered) !== FALSE) && ($_SESSION[$myfname] == "") ) {
			$answertext = "<span class='errormandatory'>{$answertext}</span>";
		}
		if (!isset($trbc) || $trbc == "array1") {$trbc = "array2";} else {$trbc = "array1";}
		$htmltbody2 = "";
		if ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == false)
		{
			$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
		} else if (($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "S") || ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == true))
		{
			$selected = getArrayFiltersForQuestion($ia[0]);
			if (!in_array($ansrow['code'],$selected))
			{
				$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
				$_SESSION[$myfname] = "";
			} else
			{
				$htmltbody2 = "<tbody id='javatbd$myfname' style='display: '><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='on' />";
			}
		}
		$answer .= "\t\t\t\t$htmltbody2<tr class='$trbc'>\n"
		. "\t\t\t\t\t<td align='right' class='answertext'>$answertext</td>\n"
		. "\t\t\t\t\t\t<td align='center'><label for='answer$myfname-I'>"
		."<input class='radio' type='radio' name='$myfname' id='answer$myfname-I' value='I' title='".$clang->gT("Increase")."'";
		if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == "I") {$answer .= " checked='checked'";}

		$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n"
		. "\t\t\t\t\t\t<td align='center'><label for='answer$myfname-S'>"
		."<input class='radio' type='radio' name='$myfname' id='answer$myfname-S' value='S' title='".$clang->gT("Same")."'";

		if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == "S") {$answer .= " checked='checked'";}

		$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n"
		. "\t\t\t\t\t\t<td align='center'><label for='answer$myfname-D'>"
		."<input class='radio' type='radio' name='$myfname' id='answer$myfname-D' value='D' title='".$clang->gT("Decrease")."'";
		// --> END NEW FEATURE - SAVE
		if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == "D") {$answer .= " checked='checked'";}

		$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label>\n"
		. "\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";

		if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
		$answer .= "' /></td>\n";

		if ($ia[6] != "Y" && $shownoanswer == 1)
		{
			$answer .= "\t\t\t\t\t<td align='center'><label for='answer$myfname-'>"
			."<input class='radio' type='radio' name='$myfname' id='answer$myfname-' value='' title='".$clang->gT("No answer")."'";
			if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == "")
			{
				$answer .= " checked='checked'";
			}
			$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n";
		}
		$answer .= "\t\t\t\t</tr>\n";
		$inputnames[]=$myfname;
		$fn++;
	}
	$answer .= "\t\t\t</table>\n";
	return array($answer, $inputnames);
}

function do_array_flexible($ia)
{
	global $dbprefix, $connect, $thissurvey, $clang;
	global $shownoanswer;
	global $repeatheadings;
	global $notanswered;
	global $minrepeatheadings;
	$qquery = "SELECT other, lid FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);     //Checked
	while($qrow = $qresult->FetchRow()) {$other = $qrow['other']; $lid = $qrow['lid'];}
	$lquery = "SELECT * FROM {$dbprefix}labels WHERE lid=$lid  AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, code";

	$qidattributes=getQuestionAttributes($ia[0]);
	if ($answerwidth=arraySearchByKey("answer_width", $qidattributes, "attribute", 1)) {
		$answerwidth=$answerwidth['value'];
	} else {
		$answerwidth=20;
	}
	$columnswidth=100-($answerwidth*2);

	$lresult = db_execute_assoc($lquery);   //Checked
	if ($lresult->RecordCount() > 0)
	{
		while ($lrow=$lresult->FetchRow())
		{
			$labelans[]=$lrow['title'];
			$labelcode[]=$lrow['code'];
		}
		$numrows=count($labelans);
		if ($ia[6] != "Y" && $shownoanswer == 1) {$numrows++;}
		$cellwidth=$columnswidth/$numrows;

		$cellwidth=sprintf("%02d", $cellwidth);
		
		$ansquery = "SELECT answer FROM {$dbprefix}answers WHERE qid=".$ia[0]." AND answer like '%|%'";
		$ansresult = db_execute_assoc($ansquery);  //Checked
    	if ($ansresult->RecordCount()>0) {$right_exists=true;} else {$right_exists=false;} 
		// $right_exists is a flag to find out if there are any right hand answer parts. If there arent we can leave out the right td column
        if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		    $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	    } else {
		    $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	    }
		$ansresult = db_execute_assoc($ansquery); //Checked
		$anscount = $ansresult->RecordCount();
		$fn=1;
		$answer = "\t\t\t<table class='question'><thead>\n"
		. "\t\t\t\t<tr>\n"
		. "\t\t\t\t\t<td width='$answerwidth%'></td>\n";
		foreach ($labelans as $ld)
		{
			$answer .= "\t\t\t\t\t<th class='array1' width='$cellwidth%'><font size='1'>".$ld."</font></th>\n";
		}
		if ($right_exists) {$answer .= "<td>&nbsp;</td>";} 
		if ($ia[6] != "Y" && $shownoanswer == 1) //Question is not mandatory and we can show "no answer"
		{
			$answer .= "\t\t\t\t\t<th class='array1' width='$cellwidth%'><font size='1'>".$clang->gT("No answer")."</font></th>\n";
		}
		$answer .= "\t\t\t\t</tr></thead>\n";

		while ($ansrow = $ansresult->FetchRow())
		{
			if (isset($repeatheadings) && $repeatheadings > 0 && ($fn-1) > 0 && ($fn-1) % $repeatheadings == 0)
			{
				if ( ($anscount - $fn + 1) >= $minrepeatheadings )
				{
					$answer .= "\t\t\t\t<tr>\n"
					. "\t\t\t\t\t<td></td>\n";
					foreach ($labelans as $ld)
					{
						$answer .= "\t\t\t\t\t<td  class='array1'><font size='1'>".$ld."</font></td>\n";
					}
					if ($ia[6] != "Y" && $shownoanswer == 1) //Question is not mandatory and we can show "no answer"
					{
						$answer .= "\t\t\t\t\t<td class='array1'><font size='1'>".$clang->gT("No answer")."</font></td>\n";
					}
					$answer .= "\t\t\t\t</tr>\n";
				}
			}
			$myfname = $ia[1].$ansrow['code'];
			if (!isset($trbc) || $trbc == "array1") {$trbc = "array2";} else {$trbc = "array1";}
			$answertext=answer_replace($ansrow['answer']);
			$answertextsave=$answertext;
			/* Check if this item has not been answered: the 'notanswered' variable must be an array,
			containing a list of unanswered questions, the current question must be in the array,
			and there must be no answer available for the item in this session. */
			if ((is_array($notanswered)) && (array_search($ia[1], $notanswered) !== FALSE) && ($_SESSION[$myfname] == "") ) {
				$answertext = "<span class='errormandatory'>{$answertext}</span>";
			}
			$htmltbody2 = "";
			if ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == false)
			{
				$htmltbody2 = "<tr id='javatbd$myfname' style='display: none' class='$trbc'><td align='right' class='answertext' width='$answerwidth%'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
			} else if (($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "S") || ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == true))
			{
				$selected = getArrayFiltersForQuestion($ia[0]);
				if (!in_array($ansrow['code'],$selected))
				{
					$htmltbody2 = "<tr id='javatbd$myfname' style='display: none' class='$trbc'><td align='right' class='answertext' width='$answerwidth%'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
					$_SESSION[$myfname] = "";
				} else
				{
					$htmltbody2 = "<tr id='javatbd$myfname' style='display: ' class='$trbc'><td align='right' class='answertext' width='$answerwidth%'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='on' />";
				}
			}
            else 
            {
                    $htmltbody2 = "<tr id='javatbd$myfname' class='$trbc'><td align='right' class='answertext' width='$answerwidth%'><input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='on' />";
            }
            if (strpos($answertext,'|')) {$answertext=substr($answertext,0, strpos($answertext,'|'));}

			$answer .= "\t\t\t\t$htmltbody2\n"
			. "\t\t\t\t\t$answertext\n"
			. "\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
			if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
			$answer .= "' /></td>\n";
			$thiskey=0;
			foreach ($labelcode as $ld)
			{
				$answer .= "\t\t\t\t\t<td align='center' width='$cellwidth%'><label for='answer$myfname-$ld'>";
				$answer .= "<input class='radio' type='radio' name='$myfname' value='$ld' id='answer$myfname-$ld' title='"
				. html_escape(strip_tags($labelans[$thiskey]))."'";
				if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $ld) {$answer .= " checked='checked'";}
				// --> START NEW FEATURE - SAVE
				$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n";
				// --> END NEW FEATURE - SAVE

				$thiskey++;
			}
            if (strpos($answertextsave,'|')) 
            {
                $answertext=substr($answertextsave,strpos($answertextsave,'|')+1);
       			$answer .= "\t\t\t\t<td class='answertextright' width='$answerwidth%'>$answertext</td>\n";

            }
             elseif ($right_exists)
               {
       			$answer .= "\t\t\t\t<td class='answertextright'>&nbsp;</td>\n";
			   }

			if ($ia[6] != "Y" && $shownoanswer == 1)
			{
				$answer .= "\t\t\t\t\t<td align='center' width='$cellwidth%'><label for='answer$myfname-'>"
				."<input class='radio' type='radio' name='$myfname' value='' id='answer$myfname-' title='".$clang->gT("No answer")."'";
				if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == "")
				{
					$answer .= " checked='checked'";
				}
				// --> START NEW FEATURE - SAVE
				$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n";
				// --> END NEW FEATURE - SAVE
			}
			
			$answer .= "\t\t\t\t</tr>\n";
			$inputnames[]=$myfname;
			//IF a MULTIPLE of flexi-redisplay figure, repeat the headings
			$fn++;
		}
		$answer .= "\t\t\t</table>\n";
	}
	else
	{
		$answer = "<font color=red>".$clang->gT('Error: The labelset used for this question is not available in this language and/or does not exist.')."</font>";
		$inputnames="";
	}
	return array($answer, $inputnames);
}

function do_array_flexiblecolumns($ia)
{
	global $dbprefix;
	global $shownoanswer;
	global $notanswered, $clang;
	$qidattributes=getQuestionAttributes($ia[0]);
	$qquery = "SELECT other, lid FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);    //Checked
	while($qrow = $qresult->FetchRow()) {$other = $qrow['other']; $lid = $qrow['lid'];}
	$lquery = "SELECT * FROM {$dbprefix}labels WHERE lid=$lid  AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, code";
	$lresult = db_execute_assoc($lquery);   //Checked
	if ($lresult->RecordCount() > 0)
	{
		while ($lrow=$lresult->FetchRow())
		{
			$labelans[]=$lrow['title'];
			$labelcode[]=$lrow['code'];
			$labels[]=array("answer"=>$lrow['title'], "code"=>$lrow['code']);
		}
		if ($ia[6] != "Y" && $shownoanswer == 1) {
			$labelcode[]="";
			$labelans[]=$clang->gT("No answer");
			$labels[]=array("answer"=>$clang->gT("No answer"), "code"=>"");
		}
        if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		    $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	    } else {
		    $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	    }
		$ansresult = db_execute_assoc($ansquery);  //Checked
		$anscount = $ansresult->RecordCount();
        if ($anscount>0)
		{
            $fn=1;
		    $answer = "\t\t\t<table class='question' align='center'>\n"
		    . "\t\t\t\t<tr>\n"
		    . "\t\t\t\t\t<td></td>\n";
		    $cellwidth=$anscount;
	    
		    $cellwidth=round(50/$cellwidth);
		    while ($ansrow = $ansresult->FetchRow())
		    {
			    $anscode[]=$ansrow['code'];
			    $answers[]=answer_replace($ansrow['answer']);
		    }
		    foreach ($answers as $ld)
		    {
                $myfname = $ia[1].$ansrow['code'];
			    if (!isset($trbc) || $trbc == "array2") {$trbc = "array1";} else {$trbc = "array2";}
			    /* Check if this item has not been answered: the 'notanswered' variable must be an array,
			    containing a list of unanswered questions, the current question must be in the array,
			    and there must be no answer available for the item in this session. */
			    if ((is_array($notanswered)) && (array_search($ia[1], $notanswered) !== FALSE) && ($_SESSION[$myfname] == "") ) {
				    $ld = "<span class='errormandatory'>{$ld}</span>";
			    }
			    $answer .= "\t\t\t\t\t<td class='$trbc'><span class='answertext'>"
			    . $ld."</span></td>\n";
		    }
		    unset($trbc);
		    $answer .= "\t\t\t\t</tr>\n";
		    $ansrowcount=0;
		    $ansrowtotallength=0;
		    while ($ansrow = $ansresult->FetchRow())
		    {
			    $ansrowcount++;
			    $ansrowtotallength=$ansrowtotallength+strlen($ansrow['answer']);
		    }
		    $percwidth=100 - ($cellwidth*$anscount);
		    foreach($labels as $ansrow)
		    {
			    $answer .= "\t\t\t\t<tr>\n"
			    . "\t\t\t\t\t<td class='arraycaptionleft'>{$ansrow['answer']}</td>\n";
			    foreach ($anscode as $ld)
			    {
				    if (!isset($trbc) || $trbc == "array1") {$trbc = "array2";} else {$trbc = "array1";}
				    $myfname=$ia[1].$ld;
				    $answer .= "\t\t\t\t\t<td align='center' class='$trbc' width='$cellwidth%'>"
				    . "<label for='answer$myfname-".$ansrow['code']."'>";
				    $answer .= "<input class='radio' type='radio' name='$myfname' value='".$ansrow['code']."' id='answer$myfname-".$ansrow['code']."'"
				    . " title='".html_escape(strip_tags($ansrow['answer']))."'";
				    if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $ansrow['code']) {$answer .= " checked='checked'";}
				    elseif (!isset($_SESSION[$myfname]) && $ansrow['code'] == "")
				    {
					    $answer .= " checked='checked'";
					    // Humm.. (by lemeur), not sure this section can be reached
					    // because I think $_SESSION[$myfname] is always set (by save.php ??) !
					    // should remove the !isset part I think !!
				    }
				    $answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label></td>\n";
			    }
		        unset($trbc);
			    $answer .= "\t\t\t\t</tr>\n";
			    $fn++;
		    }
	
		    $answer .= "\t\t\t</table>\n";
		    foreach($anscode as $ld)
		    {
			    $myfname=$ia[1].$ld;
			    $answer .= "\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
			    if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
			    $answer .= "' />\n";
			    $inputnames[]=$myfname;
		    }
        }
        else
        {
        $answer = "<font color=red>".$clang->gT('Error: There are no answers defined for this question.')."</font>";
        $inputnames="";
        }    
		
	}
	else
	{
		$answer = "<font color=red>".$clang->gT('Error: The labelset used for this question is not available in this language and/or does not exist.')."</font>";
		$inputnames="";
	}
	return array($answer, $inputnames);
}

function do_array_flexible_dual($ia)
{
	global $dbprefix, $connect, $thissurvey, $clang;
	global $shownoanswer;
	global $repeatheadings;
	global $notanswered;
	global $minrepeatheadings;
	$inputnames=array();
	$qquery = "SELECT other, lid, lid1 FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
	$qresult = db_execute_assoc($qquery);    //Checked
	while($qrow = $qresult->FetchRow()) {$other = $qrow['other']; $lid = $qrow['lid']; $lid1 = $qrow['lid1'];}
	$lquery = "SELECT * FROM {$dbprefix}labels WHERE lid=$lid  AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, code";
	$lquery1 = "SELECT * FROM {$dbprefix}labels WHERE lid=$lid1  AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, code";
	$qidattributes=getQuestionAttributes($ia[0]);

	if ($useDropdownLayout=arraySearchByKey("use_dropdown", $qidattributes, "attribute", 1))
	{
		$useDropdownLayout = true;
	}
	else
	{
		$useDropdownLayout = false;
	}

	if ($dsheaderA=arraySearchByKey("dualscale_headerA", $qidattributes, "attribute", 1))
	{
		$leftheader= $dsheaderA['value'];
	}
	else
	{
		$leftheader ='';
	}
	if ($dsheaderB=arraySearchByKey("dualscale_headerB", $qidattributes, "attribute", 1))
	{
		$rightheader= $dsheaderB['value'];
	}
	else
	{
		$rightheader ='';
	}

	$lresult = db_execute_assoc($lquery); //Checked
	if ($useDropdownLayout === false && $lresult->RecordCount() > 0)
	{

		if ($answerwidth=arraySearchByKey("answer_width", $qidattributes, "attribute", 1)) {
			$answerwidth=$answerwidth['value'];
		} else {
			$answerwidth=20;
		}
		$columnswidth=100-($answerwidth*2);


		while ($lrow=$lresult->FetchRow())
		{
			$labelans[]=$lrow['title'];
			$labelcode[]=$lrow['code'];
		}
		$lresult1 = db_execute_assoc($lquery1); //Checked
		if ($lresult1->RecordCount() > 0)
		{
			while ($lrow1=$lresult1->FetchRow())
			{
				$labelans1[]=$lrow1['title'];
				$labelcode1[]=$lrow1['code'];
			}
		}
		$numrows=count($labelans) + count($labelans1);
		if ($ia[6] != "Y" && $shownoanswer == 1) {$numrows++;}
		$cellwidth=$columnswidth/$numrows;

		$cellwidth=sprintf("%02d", $cellwidth);
		
		$ansquery = "SELECT answer FROM {$dbprefix}answers WHERE qid=".$ia[0]." AND answer like '%|%'";
		$ansresult = db_execute_assoc($ansquery);   //Checked
    	if ($ansresult->RecordCount()>0) {$right_exists=true;} else {$right_exists=false;} 
		// $right_exists is a flag to find out if there are any right hand answer parts. If there arent we can leave out the right td column
        if (arraySearchByKey("random_order", $qidattributes, "attribute", 1)) {
		    $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
	    } else {
		    $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
	    }
		$ansresult = db_execute_assoc($ansquery);   //Checked
		$anscount = $ansresult->RecordCount();
		$fn=1;
		// unselect second scale when using "no answer"
		$answer = "<script type='text/javascript'>\n"
		. "<!--\n"
    	. "\tfunction noanswer_checkconditions(value, name, type)\n"
    	. "\t\t{\n"
    	. "\t\t\tvar vname;\n"
        . "\t\t\tvname = name.replace(/#0/g,\"#1\");\n"
		. "\t\t\tfor(var i=0, n=document.getElementsByName(vname).length; i<n; ++i)\n"
    	. "\t\t\t{\n"
    	. "\t\t\t\tdocument.getElementsByName(vname)[i].checked=false;\n"
    	. "\t\t\t}\n"
    	. "\t\t\tcheckconditions(value, name, type);\n"
		. "\t\t}\n"
        . "\tfunction secondlabel_checkconditions(value, name, type)\n"
        . "\t\t{\n"
        . "\t\t\tvar vname;\n"
        . "\t\t\tvname = \"answer\"+name.replace(/#1/g,\"#0-\");\n"
        . "\t\t\tif(document.getElementById(vname))\n"
        . "\t\t\t{\n"
        . "\t\t\t\tdocument.getElementById(vname).checked=false;\n"
        . "\t\t\t}\n"  
        . "\t\t\tcheckconditions(value, name, type);\n"
        . "\t\t}\n"        
		. " //-->\n"
		. " </script>\n";


		// build header if needed
		if ($leftheader != '' || $rightheader !='')
		{
			$myheader = "\t\t\t\t<tr>\n"
			."\t\t\t\t\t<td width='$answerwidth%'></td>\n";

			$myheader .= "\t\t\t\t\t<td class='array1' width='$cellwidth%' colspan='".count($labelans)."'><span class='dsheader'>$leftheader</span></td>\n";

			if (count($labelans1)>0)
			{
				$myheader .= "\t\t\t\t\t<td class='array1' width='$cellwidth%'></td>\n";
				$myheader .= "\t\t\t\t\t<td class='array1' width='$cellwidth%' colspan='".count($labelans1)."'><span class='dsheader'>$rightheader</span></td>\n";
			}

			if ($right_exists) {$myheader .= "<td>&nbsp;</td>";}
			if ($ia[6] != "Y" && $shownoanswer == 1)
			{
				$myheader .= "\t\t\t\t\t<td class='array1' width='$cellwidth%'></td>\n";
				$myheader .= "\t\t\t\t\t<td class='array1' width='$cellwidth%'></td>\n";
			}
			$myheader .= "\t\t\t\t</tr>\n";
		}		
		else
		{
			$myheader = '';
		}


		$answer .= "\t\t\t<table class='question'>\n"
		. $myheader
		. "\t\t\t\t<tr>\n"
		. "\t\t\t\t\t<td width='$answerwidth%'></td>\n";

		foreach ($labelans as $ld)
		{
			$answer .= "\t\t\t\t\t<th class='array1' width='$cellwidth%'><font size='1'>".$ld."</font></th>\n";
		}
		
		if (count($labelans1)>0) // if second label set is used
		{
			$answer .= "\t\t\t\t\t<th class='array1' width='$cellwidth%'><font size='1'></font></th>\n";			
			foreach ($labelans1 as $ld)
			{
				$answer .= "\t\t\t\t\t<th class='array1' width='$cellwidth%'><font size='1'>".$ld."</font></th>\n";
			}
		}
		if ($right_exists) {$answer .= "<td>&nbsp;</td>";} 
		if ($ia[6] != "Y" && $shownoanswer == 1) //Question is not mandatory and we can show "no answer"
		{
			$answer .= "\t\t\t\t\t<th class='array1' width='$cellwidth%'><font size='1'></font></th>\n";
			$answer .= "\t\t\t\t\t<th class='array1' width='$cellwidth%'><font size='1'>".$clang->gT("No answer")."</font></th>\n";
		}
		$answer .= "\t\t\t\t</tr>\n";

		while ($ansrow = $ansresult->FetchRow())
		{
			if (isset($repeatheadings) && $repeatheadings > 0 && ($fn-1) > 0 && ($fn-1) % $repeatheadings == 0)
			{
				if ( ($anscount - $fn + 1) >= $minrepeatheadings )
				{
					$answer .= "\t\t\t\t<tr>\n"
					. "\t\t\t\t\t<td></td>\n";
					foreach ($labelans as $ld)
					{
						$answer .= "\t\t\t\t\t<td  class='array1'><font size='1'>".$ld."</font></td>\n";
					}
					if (count($labelans1)>0) // if second label set is used
					{
						$answer .= "\t\t\t\t\t<td  class='array1'><font size='1'></font></td>\n";		// separator	
						foreach ($labelans1 as $ld)
						{
						$answer .= "\t\t\t\t\t<td  class='array1'><font size='1'>".$ld."</font></td>\n";
						}
					}
					if ($ia[6] != "Y" && $shownoanswer == 1) //Question is not mandatory and we can show "no answer"
					{
						$answer .= "\t\t\t\t\t<td  class='array1'><font size='1'></font></td>\n";		// separator	
						$answer .= "\t\t\t\t\t<td class='array1'><font size='1'>".$clang->gT("No answer")."</font></td>\n";
					}
					$answer .= "\t\t\t\t</tr>\n";
				}
			}

			if (!isset($trbc) || $trbc == "array1") {$trbc = "array2";} else {$trbc = "array1";}
			$answertext=answer_replace($ansrow['answer']);
			$answertextsave=$answertext;
            
            $dualgroup=0; 
            $myfname = $ia[1].$ansrow['code']."#0";
            $myfname1 = $ia[1].$ansrow['code']."#1"; // new multi-scale-answer
			/* Check if this item has not been answered: the 'notanswered' variable must be an array,
			containing a list of unanswered questions, the current question must be in the array,
			and there must be no answer available for the item in this session. */
			if ((is_array($notanswered)) && (array_search($ia[1], $notanswered) !== FALSE) && (($_SESSION[$myfname] == "") || ($_SESSION[$myfname1] == "")) ) 
            {
				$answertext = "<span class='errormandatory'>{$answertext}</span>";
			}
			$htmltbody2 = "";
            $hiddenanswers="";
			if ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == false)
			{
				$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'>"; $hiddenanswers  .="<input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
			} else if (($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "S") || ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == true))
			{
				$selected = getArrayFiltersForQuestion($ia[0]);
				if (!in_array($ansrow['code'],$selected))
				{
					$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'>"; $hiddenanswers  .="<input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' />";
					$_SESSION[$myfname] = "";
				} else
				{
					$htmltbody2 = "<tbody id='javatbd$myfname' style='display: '>"; $hiddenanswers  .="<input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='on' />";
				}
			}
            if (strpos($answertext,'|')) {$answertext=substr($answertext,0, strpos($answertext,'|'));}
            

			array_push($inputnames,$myfname);
			$answer .= "\t\t\t\t$htmltbody2<tr class='$trbc'>\n"
			. "\t\t\t\t\t<td align='right' class='answertext' width='$answerwidth%'> $answertext\n"
			. "\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
			if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
			$answer .= "' />$hiddenanswers</td>\n";
            $hiddenanswers="";
     		$thiskey=0;
			
			foreach ($labelcode as $ld)
			{
				$answer .= "\t\t\t\t\t<td align='center' width='$cellwidth%'>";
				$answer .= "<label for='answer$myfname-$ld'>";
				$answer .= "<input class='radio' type='radio' name='$myfname' value='$ld' id='answer$myfname-$ld' title='"
				. html_escape(strip_tags($labelans[$thiskey]))."'";
				if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $ld) {$answer .= " checked='checked'";}
				// --> START NEW FEATURE - SAVE
				$answer .= " onclick='checkconditions(this.value, this.name, this.type)'  /></label>";
				// --> END NEW FEATURE - SAVE
				$answer .= "</td>\n";
				$thiskey++;
			}
			if (count($labelans1)>0) // if second label set is used
			{			
				$dualgroup++;
                $hiddenanswers='';
				$answer .= "\t\t\t\t\t<td  class='array1'><font size='1'></font></td>\n";		// separator
    			array_push($inputnames,$myfname1);
				$hiddenanswers .= "<input type='hidden' name='java$myfname1' id='java$myfname1' value='";
                if (isset($_SESSION[$myfname1])) {$hiddenanswers .= $_SESSION[$myfname1];}
				$hiddenanswers .= "' />";
                $thiskey=0;
				foreach ($labelcode1 as $ld) // second label set
				{
					$answer .= "\t\t\t\t\t<td align='center' width='$cellwidth%'>";
                    if ($hiddenanswers!='')
                    	{
                    		$answer .=$hiddenanswers;
                    		$hiddenanswers='';
                    	}
					$answer .= "<label for='answer$myfname1-$ld'>";
					$answer .= "<input class='radio' type='radio' name='$myfname1' value='$ld' id='answer$myfname1-$ld' title='"
					. html_escape(strip_tags($labelans1[$thiskey]))."'";
					if (isset($_SESSION[$myfname1]) && $_SESSION[$myfname1] == $ld) {$answer .= " checked='checked'";}
					// --> START NEW FEATURE - SAVE
					$answer .= " onclick='secondlabel_checkconditions(this.value, this.name, this.type)'  /></label>";
					// --> END NEW FEATURE - SAVE

					$answer .= "</td>\n";
					$thiskey++;
				}
			}
      if (strpos($answertextsave,'|')) 
      {
      	$answertext=substr($answertextsave,strpos($answertextsave,'|')+1);
      	$answer .= "\t\t\t\t<td class='answertextright' width='$answerwidth%'>$answertext</td>\n";
        $hiddenanswers='';
      }
      elseif ($right_exists)
      {
	   		$answer .= "\t\t\t\t<td class='answertextright'>&nbsp;</td>\n";
			}

			if ($ia[6] != "Y" && $shownoanswer == 1)
			{
				$answer .= "\t\t\t\t\t<td  class='array1'><font size='1'></font></td>\n";		// separator	
				$answer .= "\t\t\t\t\t<td align='center' width='$cellwidth%'><label for='answer$myfname-'>"
				."<input class='radio' type='radio' name='$myfname' value='' id='answer$myfname-' title='".$clang->gT("No answer")."'";
				if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == "")
				{
					$answer .= " checked='checked'";
				}
				// --> START NEW FEATURE - SAVE
				$answer .= " onclick='noanswer_checkconditions(this.value, this.name, this.type)'  /></label></td>\n";
				// --> END NEW FEATURE - SAVE
			}
			
			$answer .= "\t\t\t\t</tr>\n";
			// $inputnames[]=$myfname;
			//IF a MULTIPLE of flexi-redisplay figure, repeat the headings
			$fn++;
		}
		$answer .= "\t\t\t</table>\n";
	}
	elseif ($useDropdownLayout === true && $lresult->RecordCount() > 0)
	{ //TIBO

		if ($answerwidth=arraySearchByKey("answer_width", $qidattributes, "attribute", 1)) {
			$answerwidth=$answerwidth['value'];
		} else {
			$answerwidth=20;
		}
		$separatorwidth=(100-$answerwidth)/10;
		$columnswidth=100-$answerwidth-($separatorwidth*2);

		$answer = "<script type='text/javascript'>\n"
		. "<!--\n"
		. "\tfunction special_checkconditions(value, name, type, rank)\n"
		. "\t\t{\n"
		. "\t\t\tif (value == '') {\n"
		. "\t\t\t\tif (rank == 0) { dualname = name.replace(/#0/g,\"#1\"); }\n"
		. "\t\t\t\telse if (rank == 1) { dualname = name.replace(/#1/g,\"#0\"); }\n"
		. "\t\t\t\tdocument.getElementsByName(dualname)[0].value=value;\n"
		. "\t\t\t}\n"
		. "\t\t\t\tcheckconditions(value, name, type);\n"
		. "}\n"
		. " //-->\n"
		. " </script>\n";

		// Get Answers
		if (arraySearchByKey("random_order", $qidattributes, "attribute", 1))
		{
			$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
		}
		else
		{
			$ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, answer";
		}
		$ansresult = db_execute_assoc($ansquery);    //Checked
		$anscount = $ansresult->RecordCount();

		$answer .= "\t\t\t<table class='question'>\n";

		if ($anscount==0) 
		{
			$inputnames=array();
			$answer.="<tr><td class='answertext'>".$clang->gT("Error: This question has no answers.")."</td></tr>\n";
		}
		else 
		{
			//already done $lresult = db_execute_assoc($lquery);
			while ($lrow=$lresult->FetchRow())
			{
				$labels0[]=Array('code' => $lrow['code'],
						'title' => $lrow['title']);
			}
			$lresult1 = db_execute_assoc($lquery1);   //Checked
			while ($lrow1=$lresult1->FetchRow())
			{
				$labels1[]=Array('code' => $lrow1['code'],
						'title' => $lrow1['title']);
			}


			// Get attributes for Headers and Prefix/Suffix

			if ($ddprepostfix=arraySearchByKey("dropdown_prepostfix", $qidattributes, "attribute", 1))
			{
				list ($ddprefix, $ddsuffix) =explode("|",$ddprepostfix['value']);
				$ddprefix= $ddprefix;
				$ddsuffix= $ddsuffix;
			}
			else
			{
				$ddprefix ='';
				$ddsuffix='';
			}
			if ($ddseparators=arraySearchByKey("dropdown_separators", $qidattributes, "attribute", 1))
			{
				list ($postanswSep, $interddSep) =explode("|",$ddseparators['value']);
				$postanswSep = $postanswSep;
				$interddSep = $interddSep;
			}
			else
			{
				$postanswSep = '';
				$interddSep = '';
			}

			// headers
			$answer .= "\t\t\t\t\t\t\t<tr>\n"
			. "\t\t\t\t\t\t\t\t<td align='right' width='$answerwidth%' class='answertext'>\n"
			. "\t\t\t\t\t\t\t\t</td>\n"
			. "\t\t\t\t\t\t\t\t<td align='center' class='ddarrayseparator' width='$separatorwidth%'>\n" // postansSeparator
			. "\t\t\t\t\t\t\t\t</td>\n"
			. "\t\t\t\t\t\t\t\t<td align='right' class='ddprefix'>\n" // prefix
			. "\t\t\t\t\t\t\t\t</td>\n"
//			. "\t\t\t\t\t\t\t\t<td align='center' width='$columnswidth%'><span class='dsheader'>$leftheader</span></td>\n"
			. "\t\t\t\t\t\t\t\t<td align='center'><span class='dsheader'>$leftheader</span></td>\n"
			. "\t\t\t\t\t\t\t\t<td align='right' class='ddsuffix'>\n" // prefix
			. "\t\t\t\t\t\t\t\t</td>\n"
			. "\t\t\t\t\t\t\t\t<td align='right' class='ddarrayseparator' width='$separatorwidth%'>\n" // Inter DD separator
			. "\t\t\t\t\t\t\t\t</td>\n"
			. "\t\t\t\t\t\t\t\t<td align='right' class='ddprefix'>\n" // prefix
			. "\t\t\t\t\t\t\t\t</td>\n"
//			. "\t\t\t\t\t\t\t\t<td align='center' width='$columnswidth%'><span class='dsheader'>$rightheader</span></td>\n"
			. "\t\t\t\t\t\t\t\t<td align='center'><span class='dsheader'>$rightheader</span></td>\n"
			. "\t\t\t\t\t\t\t\t<td align='right' class='ddsuffix'>\n" // prefix
			. "\t\t\t\t\t\t\t\t</td>\n"
			. "\t\t\t\t\t\t\t</tr>\n";
			
			while ($ansrow = $ansresult->FetchRow())
			{
				$rowname = $ia[1].$ansrow['code'];
				$dualgroup=0;
				$myfname = $ia[1].$ansrow['code']."#".$dualgroup;
				$dualgroup1=1;
				$myfname1 = $ia[1].$ansrow['code']."#".$dualgroup1;

				if ((is_array($notanswered)) && (array_search($ia[1], $notanswered) !== FALSE) && ($_SESSION[$myfname] == "" || $_SESSION[$myfname1] == "") )
				{
					$answertext="<span class='errormandatory'>".answer_replace($ansrow['answer'])."</span>";
				}
				else
				{
					$answertext=answer_replace($ansrow['answer']);
				}

				if (!isset($trbc) || $trbc == "array1dropdown" || !$trbc) {$trbc = "array2dropdown";} else {$trbc = "array1dropdown";}
				$htmltbody2 = "";
				if (($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == false)  || ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "A"))
				{
					$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'>";$hiddenanswers="<input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off'  /><input type='hidden' name='tbdisp$myfname1' id='tbdisp$myfname1' value='off' />";
				} else if (($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "S") || ($htmltbody=arraySearchByKey("array_filter", $qidattributes, "attribute", 1) && $thissurvey['format'] == "G" && getArrayFiltersOutGroup($ia[0]) == true))
				{
					$selected = getArrayFiltersForQuestion($ia[0]);
					if (!in_array($ansrow['code'],$selected))
					{
						$htmltbody2 = "<tbody id='javatbd$myfname' style='display: none'>";$hiddenanswers="<input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='off' /><input type='hidden' name='tbdisp$myfname1' id='tbdisp$myfname1' value='off' />";
						$_SESSION[$myfname] = "";
					} else
					{
						$htmltbody2 = "<tbody id='javatbd$myfname' style='display: '>";$hiddenanswers="<input type='hidden' name='tbdisp$myfname' id='tbdisp$myfname' value='on' /><input type='hidden' name='tbdisp$myfname1' id='tbdisp$myfname1' value='on' />";
					}
				}

				$answer .= "\t\t\t\t\t\t\t$htmltbody2<tr class='$trbc'>\n"
				. "\t\t\t\t\t\t\t\t<td align='right' width='$answerwidth%' class='answertext'>\n"
				. "\t\t\t\t\t\t\t\t\t<label for='answer$rowname'>$answertext</label>\n"
				. "\t\t\t\t\t\t\t\t</td>\n";

				$answer .= "\t\t\t\t\t\t\t\t<td align='center' class='ddarrayseparator' width='$separatorwidth%'>$postanswSep</td>\n"; //Separator
		
				// Label0

				// prefix
				$answer .= "\t\t\t\t\t\t\t\t<td align='right' class='ddprefix'>$ddprefix</td>\n";

//				$answer .= "\t\t\t\t\t\t\t\t<td align='left' width='$columnswidth%'>\n"
				$answer .= "\t\t\t\t\t\t\t\t<td align='center'>\n"
				. "\t\t\t\t\t\t\t\t<select name='$myfname' id='answer$myfname' onchange='special_checkconditions(this.value, this.name, this.type,$dualgroup);'>\n";

				if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] =='')
				{
					$answer .= "\t\t\t\t\t\t\t\t\t<option value='' selected='selected'>".$clang->gT("Please choose")."..</option>\n";
				}

				foreach ($labels0 as $lrow)
				{
					$answer .= "\t\t\t\t\t\t\t\t\t<option value='".$lrow['code']."' ";
					if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $lrow['code'])
					{
						$answer .= " selected='selected' ";
					}
					$answer .= ">".$lrow['title']."</option>\n";
				}
				// If not mandatory and showanswer, show no ans
				if ($ia[6] != "Y" && $shownoanswer == 1)
				{
					$answer .= "\t\t\t\t\t\t\t\t\t<option value='' ";
					if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == "")
					{
						$answer .= " selected='selected' ";
					}
					$answer .= ">".$clang->gT("No answer")."</option>\n";
				}
				$answer .= "\t\t\t\t\t\t\t\t</select>\n";

				// suffix
				$answer .= "\t\t\t\t\t\t\t\t<td align='left' class='ddsuffix'>$ddsuffix</td>\n"
				. "\t\t\t\t\t\t\t\t<input type='hidden' name='java$myfname' id='java$myfname' value='";
				if (isset($_SESSION[$myfname]))
				{
					$answer .= $_SESSION[$myfname];
				}
				$answer .= "' />\n"
				. "\t\t\t\t\t\t\t\t</td>\n";

				$inputnames[]=$myfname;

				$answer .= "\t\t\t\t\t\t\t\t<td align='center' class='ddarrayseparator' width='$separatorwidth%'>$interddSep</td>\n"; //Separator

				// Label1

				// prefix
				$answer .= "\t\t\t\t\t\t\t\t<td align='right' class='ddprefix'>$ddprefix</td>\n";
//				$answer .= "\t\t\t\t\t\t\t\t<td align='left' width='$columnswidth%'>\n"
				$answer .= "\t\t\t\t\t\t\t\t<td align='center'>\n"
				. "\t\t\t\t\t\t\t\t<select name='$myfname1' id='answer$myfname1' onchange='special_checkconditions(this.value, this.name, this.type,$dualgroup1);'>\n";

				if (!isset($_SESSION[$myfname1]) || $_SESSION[$myfname1] =='')
				{
					$answer .= "\t\t\t\t\t\t\t\t\t<option value='' selected='selected'>".$clang->gT("Please choose")."..</option>\n";
				}

				foreach ($labels1 as $lrow1)
				{
					$answer .= "\t\t\t\t\t\t\t\t\t<option value='".$lrow1['code']."' ";
					if (isset($_SESSION[$myfname1]) && $_SESSION[$myfname1] == $lrow1['code'])
					{
						$answer .= " selected='selected' ";
					}
					$answer .= ">".$lrow1['title']."</option>\n";
				}
				// If not mandatory and showanswer, show no ans
				if ($ia[6] != "Y" && $shownoanswer == 1)
				{
					$answer .= "\t\t\t\t\t\t\t\t\t<option value='' ";
					if (!isset($_SESSION[$myfname1]) || $_SESSION[$myfname1] == "")
					{
						$answer .= " selected='selected' ";
					}
					$answer .= ">".$clang->gT("No answer")."</option>\n";
				}
				$answer .= "\t\t\t\t\t\t\t\t</select>\n";

				// suffix
				$answer .= "\t\t\t\t\t\t\t\t<td align='left' class='ddsuffix'>$ddsuffix</td>\n"
				. "\t\t\t\t\t\t\t\t<input type='hidden' name='java$myfname1' id='java$myfname1' value='";
				if (isset($_SESSION[$myfname1]))
				{
					$answer .= $_SESSION[$myfname1];
				}
				$answer .= "' />\n"
				. "\t\t\t\t\t\t\t\t</td>\n";
				$inputnames[]=$myfname1;

				$answer .= "\t\t\t\t\t\t\t</tr>\n";
			}
		} // End there are answers
		$answer .= "\t\t\t</table>$hiddenanswers\n";
        $hiddenanswers='';
	}
	else
	{
		$answer = "<font color=red>".$clang->gT('Error: The labelset used for this question is not available in this language and/or does not exist.')."</font>";
		$inputnames="";
	}
	return array($answer, $inputnames);
}


function answer_replace($text) {
	while (strpos($text, "{INSERTANS:") !== false)
	{
		$replace=substr($text, strpos($text, "{INSERTANS:"), strpos($text, "}", strpos($text, "{INSERTANS:"))-strpos($text, "{INSERTANS:")+1);
		$replace2=substr($replace, 11, strpos($replace, "}", strpos($replace, "{INSERTANS:"))-11);
		$replace3=retrieve_Answer($replace2);
		$text=str_replace($replace, $replace3, $text);
	} //while
	return $text;
}

function labelset_exists($labelid,$language) {

	$qulabel = "SELECT * FROM ".db_table_name('labels')." WHERE lid=$labelid AND language='$language'";
	$tablabel = db_execute_assoc($qulabel) or safe_die("Couldn't check for labelset<br />$ansquery<br />".$connect->ErrorMsg()); //Checked
	if ($tablabel->RecordCount()>0) {return true;} else {return false;}
}

?>
