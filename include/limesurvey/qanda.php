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
* $Id: qanda.php 12439 2012-02-10 17:38:30Z tmswhite $
*/

// Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

if (!isset($homedir) || isset($_REQUEST['$homedir'])) {die("Cannot run this script directly");}
global $thissurvey;

/*
* Let's explain what this strange $ia var means
*
* The $ia string comes from the $_SESSION['insertarray'] variable which is built at the commencement of the survey.
* See index.php, function "buildsurveysession()"
* One $ia array exists for every question in the survey. The $_SESSION['insertarray']
* string is an array of $ia arrays.
*
* $ia[0] => question id
* $ia[1] => fieldname
* $ia[2] => title
* $ia[3] => question text
* $ia[4] => type --  text, radio, select, array, etc
* $ia[5] => group id
* $ia[6] => mandatory Y || N
* $ia[7] => conditions exist for this question
* $ia[8] => other questions have conditions which rely on this question (including array_filter and array_filter_exclude attributes)
* $ia[9] => incremental question count (used by {QUESTION_NUMBER})
*
* $conditions element structure
* $condition[n][0] => qid = question id
* $condition[n][1] => cqid = question id of the target question, or 0 for TokenAttr leftOperand
* $condition[n][2] => field name of element [1] (Except for type M or P)
* $condition[n][3] => value to be evaluated on answers labeled.
* $condition[n][4] => type of question
* $condition[n][5] => SGQ code of element [1] (sub-part of [2])
* $condition[n][6] => method used to evaluate
* $condition[n][7] => scenario *NEW BY R.L.J. van den Burg*
*/

if($shownoanswer > 0 && $thissurvey['shownoanswer'] != 'N')
{
    define('SHOW_NO_ANSWER',1);
}
else
{
    define('SHOW_NO_ANSWER',0);
};

//queXS addition
function quexs_submit_on_click($do = true)
{
	include_once(dirname(__FILE__) . '/quexs.php');

	$r = "; $('.submit').css('display', ''); ";

  $interviewer=returnglobal('interviewer');
  if (empty($interviewer))
  {
    $interviewer = false;
  }
  if (!isset($_SESSION['interviewer'])) {
    $_SESSION['interviewer'] = $interviewer;
  }
  
	if (LIME_AUTO_ADVANCE && $do && $_SESSION['interviewer'])
	{
		$r .= " document.limesurvey.move.value = '";
		if (isset($_SESSION['step']) && $_SESSION['step'] && ($_SESSION['step'] == $_SESSION['totalsteps']))
			$r .= "movesubmit";
		else
			$r .= "movenext";
		$r .= "'; document.limesurvey.submit(); ";
	}

	return $r;
}

function quexs_appointment($do)
{
	include_once(dirname(__FILE__) . '/quexs.php');
	
	$r = "";

	if ($do)
	{
		$r = "; $('.submit').css('display', 'none'); parent.poptastic('" . QUEXS_URL . "appointment.php'); ";
	}
	
	return $r;	
}

function quexs_outcome($outcome)
{
	include_once(dirname(__FILE__) . '/quexs.php');

	$r = "";

	if (is_numeric($outcome) && $outcome != 0)
	{
		$r = "; $('.submit').css('display', 'none'); parent.poptastic('" . QUEXS_URL . "call.php?defaultoutcome=$outcome'); ";
	}

	return $r;
}

/**
* This function returns an array containing the "question/answer" html display
* and a list of the question/answer fieldnames associated. It is called from
* question.php, group.php or survey.php
*
* @param mixed $ia
* @param mixed $notanswered
* @param mixed $notvalidated
* @param mixed $filenotvalidated
* @return mixed
*/
function retrieveAnswers($ia)
{
    //globalise required config variables
    global $dbprefix, $clang; //These are from the config-defaults.php file
    global $thissurvey, $gl; //These are set by index.php
    global $connect;

    //DISPLAY
    $display = $ia[7];

    //QUESTION NAME
    $name = $ia[0];

    $qtitle=$ia[3];
    $inputnames=array();

    // TMSW - eliminate this - get from LEM
    //A bit of housekeeping to stop PHP Notices
    $answer = "";
    if (!isset($_SESSION[$ia[1]])) {$_SESSION[$ia[1]] = "";}
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    //Create the question/answer html

    // Previously in limesurvey, it was virtually impossible to control how the start of questions were formatted.
    // this is an attempt to allow users (or rather system admins) some control over how the starting text is formatted.
    $number = isset($ia[9]) ? $ia[9] : '';

    // TMSW - populate this directly from LEM? - this this is global
    $question_text = array(
				 'all' => '' // All has been added for backwards compatibility with templates that use question_start.pstpl (now redundant)
    ,'text' => $qtitle
    ,'code' => $ia[2]
    ,'number' => $number
    ,'help' => ''
    ,'mandatory' => ''
    ,'man_message' => ''
    ,'valid_message' => ''
    ,'file_valid_message' => ''
    ,'class' => ''
    ,'man_class' => ''
    ,'input_error_class' => ''// provides a class.
    ,'essentials' => ''
    );

    switch ($ia[4])
    {
        case 'X': //BOILERPLATE QUESTION
            $values = do_boilerplate($ia);
            break;
        case '5': //5 POINT CHOICE radio-buttons
            $values = do_5pointchoice($ia);
            break;
        case 'D': //DATE
            $values = do_date($ia);
            break;
        case 'L': //LIST drop-down/radio-button list
            $values = do_list_radio($ia);
            if ($qidattributes['hide_tip']==0)
            {
                $qtitle .= "<br />\n<span class=\"questionhelp\">"
                . $clang->gT('Choose one of the following answers').'</span>';
                $question_text['help'] = $clang->gT('Choose one of the following answers');
            }
            break;
        case '!': //List - dropdown
            $values=do_list_dropdown($ia);
            if ($qidattributes['hide_tip']==0)
            {
                $qtitle .= "<br />\n<span class=\"questionhelp\">"
                . $clang->gT('Choose one of the following answers').'</span>';
                $question_text['help'] = $clang->gT('Choose one of the following answers');
            }
            break;
        case 'O': //LIST WITH COMMENT drop-down/radio-button list + textarea
            $values=do_listwithcomment($ia);
            if (count($values[1]) > 1 && $qidattributes['hide_tip']==0)
            {
                $qtitle .= "<br />\n<span class=\"questionhelp\">"
                . $clang->gT('Choose one of the following answers').'</span>';
                $question_text['help'] = $clang->gT('Choose one of the following answers');
            }
            break;
        case 'R': //RANKING STYLE
            $values=do_ranking($ia);
            if (count($values[1]) > 1 && $qidattributes['hide_tip']==0)
            {
                $question_text['help'] = $clang->gT("Click on an item in the list on the left, starting with your highest ranking item, moving through to your lowest ranking item.");
            }
            break;
        case 'M': //Multiple choice checkbox
            $values=do_multiplechoice($ia);
            if (count($values[1]) > 1 && $qidattributes['hide_tip']==0)
            {
                $maxansw=trim($qidattributes['max_answers']);
                $minansw=trim($qidattributes['min_answers']);
                if (!($maxansw || $minansw))
                {
                    $qtitle .= "<br />\n<span class=\"questionhelp\">"
                    . $clang->gT('Check any that apply').'</span>';
                    $question_text['help'] = $clang->gT('Check any that apply');
                }
            }
            break;

        case 'I': //Language Question
            $values=do_language($ia);
            if (count($values[1]) > 1)
            {
                $qtitle .= "<br />\n<span class=\"questionhelp\">"
                . $clang->gT('Choose your language').'</span>';
                $question_text['help'] = $clang->gT('Choose your language');
            }
            break;
        case 'P': //Multiple choice with comments checkbox + text
            $values=do_multiplechoice_withcomments($ia);
            if (count($values[1]) > 1 && $qidattributes['hide_tip']==0)
            {
                $maxansw=trim($qidattributes["max_answers"]);
                $minansw=trim($qidattributes["min_answers"]);
                if (!($maxansw || $minansw))
                {
                    $qtitle .= "<br />\n<span class=\"questionhelp\">"
                    . $clang->gT('Check any that apply').'</span>';
                    $question_text['help'] = $clang->gT('Check any that apply');
                }
            }
            break;
        case '|': //File Upload
            $values=do_file_upload($ia);
            break;
        case 'Q': //MULTIPLE SHORT TEXT
            $values=do_multipleshorttext($ia);
            break;
        case 'K': //MULTIPLE NUMERICAL QUESTION
            $values=do_multiplenumeric($ia);
            break;
        case 'N': //NUMERICAL QUESTION TYPE
            $values=do_numerical($ia);
            break;
        case 'S': //SHORT FREE TEXT
            $values=do_shortfreetext($ia);
            break;
        case 'T': //LONG FREE TEXT
            $values=do_longfreetext($ia);
            break;
        case 'U': //HUGE FREE TEXT
            $values=do_hugefreetext($ia);
            break;
        case 'Y': //YES/NO radio-buttons
            $values=do_yesno($ia);
            break;
        case 'G': //GENDER drop-down list
            $values=do_gender($ia);
            break;
        case 'A': //ARRAY (5 POINT CHOICE) radio-buttons
            $values=do_array_5point($ia);
            break;
        case 'B': //ARRAY (10 POINT CHOICE) radio-buttons
            $values=do_array_10point($ia);
            break;
        case 'C': //ARRAY (YES/UNCERTAIN/NO) radio-buttons
            $values=do_array_yesnouncertain($ia);
            break;
        case 'E': //ARRAY (Increase/Same/Decrease) radio-buttons
            $values=do_array_increasesamedecrease($ia);
            break;
        case 'F': //ARRAY (Flexible) - Row Format
            $values=do_array($ia);
            break;
        case 'H': //ARRAY (Flexible) - Column Format
            $values=do_arraycolumns($ia);
            break;
        case ':': //ARRAY (Multi Flexi) 1 to 10
            $values=do_array_multiflexi($ia);
            break;
        case ';': //ARRAY (Multi Flexi) Text
            $values=do_array_multitext($ia);  //It's like the "5th element" movie, come to life
            break;
        case '1': //Array (Flexible Labels) dual scale
            $values=do_array_dual($ia);
            break;
        case '*': // Equation
            $values=do_equation($ia);
            break;
    } //End Switch

    if (isset($values)) //Break apart $values array returned from switch
    {
        //$answer is the html code to be printed
        //$inputnames is an array containing the names of each input field
        list($answer, $inputnames)=$values;
    }

    if ($ia[6] == 'Y')
    {
        $qtitle = '<span class="asterisk">'.$clang->gT('*').'</span>'.$qtitle;
        $question_text['mandatory'] = $clang->gT('*');
    }
    //If this question is mandatory but wasn't answered in the last page
    //add a message HIGHLIGHTING the question
    if (($_SESSION['step'] != $_SESSION['maxstep']) || ($_SESSION['step'] == $_SESSION['prevstep']) && returnglobal('action')!='previewquestion' && returnglobal('action')!='previewgroup') {
        $mandatory_msg = mandatory_message($ia);
    }
    else {
        $mandatory_msg = '';
    }
    $qtitle .= $mandatory_msg;
    $question_text['man_message'] = $mandatory_msg;


    if (!isset($qidattributes['hide_tip']) || $qidattributes['hide_tip']==0) {
        $_vshow = true; // whether should initially be visible - TODO should also depend upon 'hidetip'?
    }
    else {
        $_vshow = false;
    }
    list($validation_msg,$isValid) = validation_message($ia,$_vshow);

    $qtitle .= $validation_msg;
    $question_text['valid_message'] = $validation_msg;

    if (($_SESSION['step'] != $_SESSION['maxstep']) || ($_SESSION['step'] == $_SESSION['prevstep'])) {
        $file_validation_msg = file_validation_message($ia);
    }
    else {
        $file_validation_msg = '';
        $isValid = true;    // don't want to show any validation messages.
    }
    $qtitle .= $ia[4] == "|" ? $file_validation_msg : "";
    $question_text['file_valid_message'] = $ia[4] == "|" ? $file_validation_msg : "";

    if(!empty($question_text['man_message']) || !$isValid || !empty($question_text['file_valid_message']))
    {
        $question_text['input_error_class'] = ' input-error';// provides a class to style question wrapper differently if there is some kind of user input error;
    }

    // =====================================================
    // START: legacy question_start.pstpl code
    // The following section adds to the templating system by allowing
    // templaters to control where the various parts of the question text
    // are put.

    if(is_file('templates/'.validate_templatedir($thissurvey['template']).'/question_start.pstpl'))
    {
        $qtitle_custom = '';

        $replace=array();
        foreach($question_text as $key => $value)
        {
            $find[] = '{QUESTION_'.strtoupper($key).'}'; // Match key words from template
            $replace[] = $value; // substitue text
        };
        if(!defined('QUESTION_START'))
        {
            define('QUESTION_START' , file_get_contents(sGetTemplatePath($thissurvey['template']).'/question_start.pstpl' , true));
        };
        $qtitle_custom = str_replace( $find , $replace , QUESTION_START);

        $c = 1;
        // START: <EMBED> work-around step 1
        $qtitle_custom = preg_replace( '/(<embed[^>]+>)(<\/embed>)/i' , '\1NOT_EMPTY\2' , $qtitle_custom );
        // END <EMBED> work-around step 1
        while($c > 0) // This recursively strips any empty tags to minimise rendering bugs.
        {
            $matches = 0;
            $oldtitle=$qtitle_custom;
            $qtitle_custom = preg_replace( '/<([^ >]+)[^>]*>[\r\n\t ]*<\/\1>[\r\n\t ]*/isU' , '' , $qtitle_custom , -1); // I removed the $count param because it is PHP 5.1 only.

            $c = ($qtitle_custom!=$oldtitle)?1:0;
        };
        // START <EMBED> work-around step 2
        $qtitle_custom = preg_replace( '/(<embed[^>]+>)NOT_EMPTY(<\/embed>)/i' , '\1\2' , $qtitle_custom );
        // END <EMBED> work-around step 2
        while($c > 0) // This recursively strips any empty tags to minimise rendering bugs.
        {
            $matches = 0;
            $oldtitle=$qtitle_custom;
            $qtitle_custom = preg_replace( '/(<br(?: ?\/)?>(?:&nbsp;|\r\n|\n\r|\r|\n| )*)+$/i' , '' , $qtitle_custom , -1 ); // I removed the $count param because it is PHP 5.1 only.
            $c = ($qtitle_custom!=$oldtitle)?1:0;
        };

        $question_text['all'] = $qtitle_custom;
    }
    else
    {
        $question_text['all'] = $qtitle;
    };
    // END: legacy question_start.pstpl code
    //===================================================================
    $qtitle = $question_text;
    // =====================================================

    $qanda=array($qtitle, $answer, 'help', $display, $name, $ia[2], $gl[0], $ia[1] );
    //New Return
    return array($qanda, $inputnames);
}

function mandatory_message($ia)
{
    $qinfo = LimeExpressionManager::GetQuestionStatus($ia[0]);
    if ($qinfo['mandViolation']) {
        return $qinfo['mandTip'];
    }
    else {
        return "";
    }
}

/**
 *
 * @param <type> $ia
 * @param <type> $show - true if should initially be visible
 * @return <type>
 */
function validation_message($ia,$show)
{
    $qinfo = LimeExpressionManager::GetQuestionStatus($ia[0]);
    $class = "questionhelp";
    if (!$show) {
        $class .= ' hide-tip';
    }
    $tip = '<span class="' . $class . '" id="vmsg_' . $ia[0] . '">' . $qinfo['validTip'] . "</span>";
    $isValid = $qinfo['valid'];
    return array($tip,$isValid);
}

// TMSW Validation -> EM
function file_validation_message($ia)
{
    global $filenotvalidated, $clang;
    $qtitle = "";
    if (isset($filenotvalidated) && is_array($filenotvalidated) && $ia[4] == "|")
    {
        global $filevalidationpopup, $popup;

        foreach ($filenotvalidated as $k => $v)
        {
            if ($ia[1] == $k || strpos($k, "_") && $ia[1] == substr(0, strpos($k, "_") - 1));
                $qtitle .= '<br /><span class="errormandatory">'.$clang->gT($filenotvalidated[$k]).'</span><br />';
        }
    }
    return $qtitle;
}

// TMSW Mandatory -> EM
function mandatory_popup($ia, $notanswered=null)
{
    global $showpopups;
    //This sets the mandatory popup message to show if required
    if ($notanswered === null) {unset($notanswered);}
    if (isset($notanswered) && $notanswered && isset($showpopups) && $showpopups == 1) //ADD WARNINGS TO QUESTIONS IF THEY WERE MANDATORY BUT NOT ANSWERED
    {
        global $mandatorypopup, $popup, $clang;
        //POPUP WARNING
        if (!isset($mandatorypopup) && ($ia[4] == 'T' || $ia[4] == 'S' || $ia[4] == 'U'))
        {
            $popup="<script type=\"text/javascript\">\n
                    <!--\n $(document).ready(function(){
                        alert(\"".$clang->gT("You cannot proceed until you enter some text for one or more questions.", "js")."\");});\n //-->\n
                    </script>\n";
            $mandatorypopup="Y";
        }else
        {
            $popup="<script type=\"text/javascript\">\n
                    <!--\n $(document).ready(function(){
                        alert(\"".$clang->gT("One or more mandatory questions have not been answered. You cannot proceed until these have been completed.", "js")."\");});\n //-->\n
                    </script>\n";
            $mandatorypopup="Y";
        }
        return array($mandatorypopup, $popup);
    }
    else
    {
        return false;
    }
}

// TMSW Validation -> EM
function validation_popup($ia, $notvalidated=null)
{
    global $showpopups;
    //This sets the validation popup message to show if required
    if ($notvalidated === null) {unset($notvalidated);}
    $qtitle="";
    if (isset($notvalidated) && $notvalidated && isset($showpopups) && $showpopups == 1)  //ADD WARNINGS TO QUESTIONS IF THEY ARE NOT VALID
    {
        global $validationpopup, $vpopup, $clang;
        //POPUP WARNING
        if (!isset($validationpopup))
        {
            $vpopup="<script type=\"text/javascript\">\n
                    <!--\n $(document).ready(function(){
                        alert(\"".$clang->gT("One or more questions have not been answered in a valid manner. You cannot proceed until these answers are valid.", "js")."\");});\n //-->\n
                    </script>\n";
            $validationpopup="Y";
        }
        return array($validationpopup, $vpopup);
    }
    else
    {
        return false;
    }
}

// TMSW Validation -> EM
function file_validation_popup($ia, $filenotvalidated = null)
{
    global $showpopups;
    if ($filenotvalidated === null) { unset($filenotvalidated); }
    if (isset($filenotvalidated) && is_array($filenotvalidated) && isset($showpopups) && $showpopups == 1)
    {
        global $filevalidationpopup, $fpopup, $clang;

        if (!isset($filevalidationpopup))
        {
            $fpopup="<script type=\"text/javascript\">\n
                    <!--\n $(document).ready(function(){
                        alert(\"".$clang->gT("One or more file have either exceeded the filesize/are not in the right format or the minimum number of required files have not been uploaded. You cannot proceed until these have been completed", "js")."\");});\n //-->\n
                    </script>\n";
            $filevalidationpopup = "Y";
        }
        return array($filevalidationpopup, $fpopup);
    }
    else
        return false;
}

function return_timer_script($qidattributes, $ia, $disable=null) {
    global $thissurvey, $clang;

    /* The following lines cover for previewing questions, because no $_SESSION['fieldarray'] exists.
     This just stops error messages occuring */
    if(!isset($_SESSION['fieldarray']))
    {
        $_SESSION['fieldarray'] = array();
    }
    /* End */

    if(isset($thissurvey['timercount']))
    {
        $thissurvey['timercount']++; //Used to count how many timer questions in a page, and ensure scripts only load once
    } else {
        $thissurvey['timercount']=1;
    }

    if($thissurvey['format'] != "S")
    {
        if($thissurvey['format'] != "G")
        {
            return "\n\n<!-- TIMER MODE DISABLED DUE TO INCORRECT SURVEY FORMAT -->\n\n";
            //We don't do the timer in any format other than question-by-question
        }
    }

    $time_limit=$qidattributes['time_limit'];

    $disable_next=trim($qidattributes['time_limit_disable_next']) != '' ? $qidattributes['time_limit_disable_next'] : 0;
    $disable_prev=trim($qidattributes['time_limit_disable_prev']) != '' ? $qidattributes['time_limit_disable_prev'] : 0;
    $time_limit_action=trim($qidattributes['time_limit_action']) != '' ? $qidattributes['time_limit_action'] : 1;
    $time_limit_message_delay=trim($qidattributes['time_limit_message_delay']) != '' ? $qidattributes['time_limit_message_delay']*1000 : 1000;
    $time_limit_message=trim($qidattributes['time_limit_message']) != '' ? htmlspecialchars($qidattributes['time_limit_message'], ENT_QUOTES) : $clang->gT("Your time to answer this question has expired");
    $time_limit_warning=trim($qidattributes['time_limit_warning']) != '' ? $qidattributes['time_limit_warning'] : 0;
    $time_limit_warning_2=trim($qidattributes['time_limit_warning_2']) != '' ? $qidattributes['time_limit_warning_2'] : 0;
    $time_limit_countdown_message=trim($qidattributes['time_limit_countdown_message']) != '' ? htmlspecialchars($qidattributes['time_limit_countdown_message'], ENT_QUOTES) : $clang->gT("Time remaining");
    $time_limit_warning_message=trim($qidattributes['time_limit_warning_message']) != '' ? htmlspecialchars($qidattributes['time_limit_warning_message'], ENT_QUOTES) : $clang->gT("Your time to answer this question has nearly expired. You have {TIME} remaining.");
    $time_limit_warning_message=str_replace("{TIME}", "<div style='display: inline' id='LS_question".$ia[0]."_Warning'> </div>", $time_limit_warning_message);
    $time_limit_warning_display_time=trim($qidattributes['time_limit_warning_display_time']) != '' ? $qidattributes['time_limit_warning_display_time']+1 : 0;
    $time_limit_warning_2_message=trim($qidattributes['time_limit_warning_2_message']) != '' ? htmlspecialchars($qidattributes['time_limit_warning_2_message'], ENT_QUOTES) : $clang->gT("Your time to answer this question has nearly expired. You have {TIME} remaining.");
    $time_limit_warning_2_message=str_replace("{TIME}", "<div style='display: inline' id='LS_question".$ia[0]."_Warning_2'> </div>", $time_limit_warning_2_message);
    $time_limit_warning_2_display_time=trim($qidattributes['time_limit_warning_2_display_time']) != '' ? $qidattributes['time_limit_warning_2_display_time']+1 : 0;
    $time_limit_message_style=trim($qidattributes['time_limit_message_style']) != '' ? $qidattributes['time_limit_message_style'] : "position: absolute;
        top: 10px;
        left: 35%;
        width: 30%;
        height: 60px;
        padding: 16px;
        border: 8px solid #555;
        background-color: white;
        z-index:1002;
		text-align: center;
        overflow: auto;";
    $time_limit_message_style.="\n		display: none;"; //Important to hide time limit message at start
    $time_limit_warning_style=trim($qidattributes['time_limit_warning_style']) != '' ? $qidattributes['time_limit_warning_style'] : "position: absolute;
        top: 10px;
        left: 35%;
        width: 30%;
        height: 60px;
        padding: 16px;
        border: 8px solid #555;
        background-color: white;
        z-index:1001;
		text-align: center;
        overflow: auto;";
    $time_limit_warning_style.="\n		display: none;"; //Important to hide time limit warning at the start
    $time_limit_warning_2_style=trim($qidattributes['time_limit_warning_2_style']) != '' ? $qidattributes['time_limit_warning_2_style'] : "position: absolute;
        top: 10px;
        left: 35%;
        width: 30%;
        height: 60px;
        padding: 16px;
        border: 8px solid #555;
        background-color: white;
        z-index:1001;
		text-align: center;
        overflow: auto;";
    $time_limit_warning_2_style.="\n		display: none;"; //Important to hide time limit warning at the start
    $time_limit_timer_style=trim($qidattributes['time_limit_timer_style']) != '' ? $qidattributes['time_limit_timer_style'] : "position: relative;
		width: 150px;
		margin-left: auto;
		margin-right: auto;
		border: 1px solid #111;
		text-align: center;
		background-color: #EEE;
		margin-bottom: 5px;
		font-size: 8pt;";
    $timersessionname="timer_question_".$ia[0];
    if(isset($_SESSION[$timersessionname])) {
        $time_limit=$_SESSION[$timersessionname];
    }

    $output = "
	<input type='hidden' name='timerquestion' value='".$timersessionname."' />
	<input type='hidden' name='".$timersessionname."' id='".$timersessionname."' value='".$time_limit."' />\n";
    if($thissurvey['timercount'] < 2)
    {
        $output .="
    <script type='text/javascript'>
	<!--
		function freezeFrame(elementid) {
			if(document.getElementById(elementid) !== null) {
				var answer=document.getElementById(elementid);
				answer.blur();
				answer.onfocus=function() { answer.blur();};
			}
		};
	//-->
	</script>";
        $output .= "
    <script type='text/javascript'>
	<!--\n
		function countdown(questionid,timer,action,warning,warning2,warninghide,warning2hide,disable){
		    if(!timeleft) { var timeleft=timer;}
			if(!warning) { var warning=0;}
			if(!warning2) { var warning2=0;}
			if(!warninghide) { var warninghide=0;}
			if(!warning2hide) { var warning2hide=0;}";

        if($thissurvey['format'] == "G")
        {
            global $gid;
            $qcount=0;
            foreach($_SESSION['fieldarray'] as $ib)
            {
                if($ib[5] == $gid)
                {
                    $qcount++;
                }
            }
            //Override all other options and just allow freezing, survey is presented in group by group mode
            if($qcount > 1) {
                $output .="
					action = 3;";
            }
        }
        $output .="
			var timerdisplay='LS_question'+questionid+'_Timer';
			var warningtimedisplay='LS_question'+questionid+'_Warning';
			var warningdisplay='LS_question'+questionid+'_warning';
			var warning2timedisplay='LS_question'+questionid+'_Warning_2';
			var warning2display='LS_question'+questionid+'_warning_2';
			var expireddisplay='question'+questionid+'_timer';
			var timersessionname='timer_question_'+questionid;
			document.getElementById(timersessionname).value=timeleft;
			timeleft--;
			cookietimer=subcookiejar.fetch('limesurvey_timers',timersessionname);
			if(cookietimer) {
				if(cookietimer <= timeleft) {
				  timeleft=cookietimer;
				}
			}
			var timeleftobject=new Object();
			subcookiejar.crumble('limesurvey_timers', timersessionname);
			timeleftobject[timersessionname]=timeleft;
			subcookiejar.bake('limesurvey_timers', timeleftobject, 7)\n";
        if($disable_next > 0) {
            $output .= "
		if(document.getElementById('movenextbtn') !== null && timeleft > $disable_next) {
			document.getElementById('movenextbtn').disabled=true;
		} else if (document.getElementById('movenextbtn') !== null && $disable_next > 1 && timeleft <= $disable_next) {
		    document.getElementById('movenextbtn').disabled=false;
		}\n";
        }
        if($disable_prev > 0) {
            $output .= "
		if(document.getElementById('moveprevbtn') !== null && timeleft > $disable_prev) {
			document.getElementById('moveprevbtn').disabled=true;
		} else if (document.getElementById('moveprevbtn') !== null && $disable_prev > 1 && timeleft <= $disable_prev) {
		    document.getElementById('moveprevbtn').disabled=false;
		}\n";
        }
        if(!is_numeric($disable_prev)) {
            $output .= "
		if(document.getElementById('moveprevbtn') !== null) {
			document.getElementById('moveprevbtn').disabled=true;
		}\n";
        }
        $output .="
			if(warning > 0 && timeleft<=warning) {
			  var wsecs=warning%60;
			  if(wsecs<10) wsecs='0' + wsecs;
			  var WT1 = (warning - wsecs) / 60;
			  var wmins = WT1 % 60; if (wmins < 10) wmins = '0' + wmins;
			  var whours = (WT1 - wmins) / 60;
			  var dmins=''
			  var dhours=''
			  var dsecs=''
			  if (whours < 10) whours = '0' + whours;
			  if (whours > 0) dhours = whours + ' ".$clang->gT('hours').", ';
			  if (wmins > 0) dmins = wmins + ' ".$clang->gT('mins').", ';
			  if (wsecs > 0) dsecs = wsecs + ' ".$clang->gT('seconds')."';
			  if(document.getElementById(warningtimedisplay) !== null) {
			      document.getElementById(warningtimedisplay).innerHTML = dhours+dmins+dsecs;
			  }
			  document.getElementById(warningdisplay).style.display='';
			}
			if(warning2 > 0 && timeleft<=warning2) {
			  var w2secs=warning2%60;
			  if(wsecs<10) w2secs='0' + wsecs;
			  var W2T1 = (warning2 - w2secs) / 60;
			  var w2mins = W2T1 % 60; if (w2mins < 10) w2mins = '0' + w2mins;
			  var w2hours = (W2T1 - w2mins) / 60;
			  var d2mins=''
			  var d2hours=''
			  var d2secs=''
			  if (w2hours < 10) w2hours = '0' + w2hours;
			  if (w2hours > 0) d2hours = w2hours + ' ".$clang->gT('hours').", ';
			  if (w2mins > 0) d2mins = w2mins + ' ".$clang->gT('mins').", ';
			  if (w2secs > 0) d2secs = w2secs + ' ".$clang->gT('seconds')."';
			  if(document.getElementById(warning2timedisplay) !== null) {
			      document.getElementById(warning2timedisplay).innerHTML = dhours+dmins+dsecs;
			  }
			  document.getElementById(warning2display).style.display='';
			}
			if(warning > 0 && warninghide > 0 && document.getElementById(warningdisplay).style.display != 'none') {
			  if(warninghide == 1) {
			    document.getElementById(warningdisplay).style.display='none';
			    warning=0;
			  }
			  warninghide--;
			}
			if(warning2 > 0 && warning2hide > 0 && document.getElementById(warning2display).style.display != 'none') {
			  if(warning2hide == 1) {
			    document.getElementById(warning2display).style.display='none';
			    warning2=0;
			  }
			  warning2hide--;
			}
			var secs = timeleft % 60;
			if (secs < 10) secs = '0'+secs;
			var T1 = (timeleft - secs) / 60;
			var mins = T1 % 60; if (mins < 10) mins = '0'+mins;
			var hours = (T1 - mins) / 60;
			if (hours < 10) hours = '0'+hours;
			var d2hours='';
			var d2mins='';
			var d2secs='';
			if (hours > 0) d2hours = hours+' ".$clang->gT('hours').": ';
			if (mins > 0) d2mins = mins+' ".$clang->gT('mins').": ';
			if (secs > 0) d2secs = secs+' ".$clang->gT('seconds')."';
			if (secs < 1) d2secs = '0 ".$clang->gT('seconds')."';
			document.getElementById(timerdisplay).innerHTML = '".$time_limit_countdown_message."<br />'+d2hours + d2mins + d2secs;
			if (timeleft>0){
				var text='countdown('+questionid+', '+timeleft+', '+action+', '+warning+', '+warning2+', '+warninghide+', '+warning2hide+', \"'+disable+'\")';
				setTimeout(text,1000);
			} else {
			    //Countdown is finished, now do action
				switch(action) {
					case 2: //Just move on, no warning
						if(document.getElementById('movenextbtn') !== null) {
						    if(document.getElementById('movenextbtn').disabled==true) document.getElementById('movenextbtn').disabled=false;
						}
						if(document.getElementById('moveprevbtn') !== null) {
							if(document.getElementById('moveprevbtn').disabled==true && '$disable_prev' > 0) document.getElementById('moveprevbtn').disabled=false;
						}
						freezeFrame(disable);
						subcookiejar.crumble('limesurvey_timers', timersessionname);
						if(document.getElementById('movenextbtn') != null) {
						  document.limesurvey.submit();
						} else {
							setTimeout(\"document.limesurvey.submit();\", 1000);
						}
						break;
					case 3: //Just warn, don't move on
						document.getElementById(expireddisplay).style.display='';
						if(document.getElementById('movenextbtn') !== null) {
						    if(document.getElementById('movenextbtn').disabled==true) document.getElementById('movenextbtn').disabled=false;
						}
						if(document.getElementById('moveprevbtn') !== null) {
						    if(document.getElementById('moveprevbtn').disabled==true && '$disable_prev' > 0) document.getElementById('moveprevbtn').disabled=false;
						}
						freezeFrame(disable);
						this.onsubmit=function() { subcookiejar.crumble('limesurvey_timers', timersessionname);};
						break;
					default: //Warn and move on
						document.getElementById(expireddisplay).style.display='';
						if(document.getElementById('movenextbtn') !== null) {
						    if(document.getElementById('movenextbtn').disabled==true) document.getElementById('movenextbtn').disabled=false;
						}
						if(document.getElementById('moveprevbtn') !== null) {
						    if(document.getElementById('moveprevbtn').disabled==true && '$disable_prev' > 0) document.getElementById('moveprevbtn').disabled=false;
						}
						freezeFrame(disable);
						subcookiejar.crumble('limesurvey_timers', timersessionname);
						setTimeout('document.limesurvey.submit()', ".$time_limit_message_delay.");
						break;
				}
			}
		}
	//-->
	</script>";
    }
    $output .= "<div id='question".$ia[0]."_timer' style='".$time_limit_message_style."'>".$time_limit_message."</div>\n\n";

    $output .= "<div id='LS_question".$ia[0]."_warning' style='".$time_limit_warning_style."'>".$time_limit_warning_message."</div>\n\n";
    $output .= "<div id='LS_question".$ia[0]."_warning_2' style='".$time_limit_warning_2_style."'>".$time_limit_warning_2_message."</div>\n\n";
    $output .= "<div id='LS_question".$ia[0]."_Timer' style='".$time_limit_timer_style."'></div>\n\n";
    //Call the countdown script
    $output .= "<script type='text/javascript'>
	$(document).ready(function() {
		countdown(".$ia[0].", ".$time_limit.", ".$time_limit_action.", ".$time_limit_warning.", ".$time_limit_warning_2.", ".$time_limit_warning_display_time.", ".$time_limit_warning_2_display_time.", '".$disable."');
	});
    </script>\n\n";
    return $output;
}

function return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $rowname, $trbc='', $valuename, $method="tbody", $class=null) {
    $htmltbody2 = "\n\n\t<$method id='javatbd$rowname'";
    $htmltbody2 .= ($class !== null) ? " class='$class'": "";
    if (isset($_SESSION['relevanceStatus'][$rowname]) && !$_SESSION['relevanceStatus'][$rowname])
    {
        // If using exclude_all_others, then need to know whether irrelevant rows should be hidden or disabled
        if (isset($qidattributes['exclude_all_others']))
        {
            $disableit=false;
            foreach(explode(';',trim($qidattributes['exclude_all_others'])) as $eo)
            {
                $eorow = $ia[1] . $eo;
                if ((!isset($_SESSION['relevanceStatus'][$eorow]) || $_SESSION['relevanceStatus'][$eorow])
                    && (isset($_SESSION[$eorow]) && $_SESSION[$eorow] == "Y"))
                {
                    $disableit = true;
                }
            }
            if ($disableit)
            {
                $htmltbody2 .= " disabled='disabled'";
            }
            else
            {
                if (!isset($qidattributes['array_filter_style']) || $qidattributes['array_filter_style'] == '0')
                {
                    $htmltbody2 .= " style='display: none'";
                }
                else
                {
                    $htmltbody2 .= " disabled='disabled'";
                }
            }
        }
        else
        {
            if (!isset($qidattributes['array_filter_style']) || $qidattributes['array_filter_style'] == '0')
            {
                $htmltbody2 .= " style='display: none'";
            }
            else
            {
                $htmltbody2 .= " disabled='disabled'";
            }
        }
    }
    $htmltbody2 .= ">\n";
    return array($htmltbody2, "");
}

// ==================================================================
// setting constants for 'checked' and 'selected' inputs
define('CHECKED' , ' checked="checked"' , true);
define('SELECTED' , ' selected="selected"' , true);

// ==================================================================
// QUESTION METHODS =================================================

function do_boilerplate($ia)
{
    global $js_header_includes;
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    $answer='';

    if (trim($qidattributes['time_limit'])!='')
    {
        $js_header_includes[] = '/scripts/coookies.js';
        $answer .= return_timer_script($qidattributes, $ia);
    }

    $answer .= '<input type="hidden" name="'.$ia[1].'" id="answer'.$ia[1].'" value="" />';
    $inputnames[]=$ia[1];

    return array($answer, $inputnames);
}


function do_equation($ia)
{
    $answer='<input type="hidden" name="'.$ia[1].'" id="java'.$ia[1].'" value="';
    if (isset($_SESSION[$ia[1]]))
    {
        $answer .= htmlspecialchars($_SESSION[$ia[1]],ENT_QUOTES);
    }
    $answer .= '">';
    $inputnames[]=$ia[1];
    $mandatory=null;

    return array($answer, $inputnames);
}


// ---------------------------------------------------------------
function do_5pointchoice($ia)
{
    global $clang, $imageurl;
	global $js_header_includes, $css_header_includes;

    $checkconditionFunction = "checkconditions";
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    //print_r($qidattributes);
	$id = 'slider'.time().rand(0,100);
    $answer = "\n<ul id=\"{$id}\">\n";
    for ($fp=1; $fp<=5; $fp++)
    {
        $answer .= "\t<li>\n<input class=\"radio\" type=\"radio\" name=\"$ia[1]\" id=\"answer$ia[1]$fp\" value=\"$fp\"";
        if ($_SESSION[$ia[1]] == $fp)
        {
            $answer .= CHECKED;
        }
        $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)" . quexs_submit_on_click() . "\" />\n<label for=\"answer$ia[1]$fp\" class=\"answertext\">$fp</label>\n\t</li>\n";
    }

    if ($ia[6] != "Y"  && SHOW_NO_ANSWER == 1) // Add "No Answer" option if question is not mandatory
    {
        $answer .= "\t<li>\n<input class=\"radio noAnswer\" type=\"radio\" name=\"$ia[1]\" id=\"answer".$ia[1]."NANS\" value=\"\"";
        if (!$_SESSION[$ia[1]])
        {
            $answer .= CHECKED;
        }
        $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)" . quexs_submit_on_click() . "\" />\n<label for=\"answer".$ia[1]."NANS\" class=\"answertext\">".$clang->gT('No answer')."</label>\n\t</li>\n";

    }
    $answer .= "</ul>\n<input type=\"hidden\" name=\"java$ia[1]\" id=\"java$ia[1]\" value=\"{$_SESSION[$ia[1]]}\" />\n";
    $inputnames[]=$ia[1];
    if($qidattributes['slider_rating']==1){
    	$css_header_includes[]= '/admin/scripts/rating/jquery.rating.css';
    	$js_header_includes[]='/admin/scripts/rating/jquery.rating.js';
    	//  write the alternative HTML only for activated javascript
	    $answer.="
			<script type=\"text/javascript\">
			document.write('";
	    $answer.='<ul id="'.$id.'div" class="answers-list stars-wrapper"><li class="item-list answer-star"><input type="radio" id="stars1" name="stars" class="'.$id.'st" value="1"/></li><li class="item-list answer-star"><input type="radio" id="stars2" name="stars" class="'.$id.'st" value="2"/></li><li class="item-list answer-star"><input type="radio" name="stars" id="stars3" class="'.$id.'st" value="3"/></li><li class="item-list answer-star"><input type="radio" id="stars4" name="stars" class="'.$id.'st" value="4"/></li><li class="item-list answer-star"><input type="radio" name="stars" id="stars5" class="'.$id.'st" value="5"/></li><li class="item-list answer-star"></u>';
	    $answer.="');
			</script>
			";
	    $answer.="
			<script type=\"text/javascript\">
				$('#$id').hide();
				var checked = $('#$id input:checked').attr('value');
				if(checked!=''){
					$('#stars'+checked).attr('checked','checked');
    			}
				$('.{$id}st').rating({
    				callback: function(value,link){
    					if(value==undefined || value==''){
    						$('#$id input').each(function(){ $(this).removeAttr('checked');});
    						$('#{$id} #NoAnswer').attr('checked','checked');
    					}
    					else{
    						$('#$id input').each(function(){ $(this).removeAttr('checked');});
    						$('#answer$ia[1]'+value).attr('checked','checked');
    					}
                        checkconditions(value,'$ia[1]','radio');
    				}

    			});
			</script>
			";
    }

    if($qidattributes['slider_rating']==2){
	    if(!IsSet($_SESSION[$ia[1]]) OR $_SESSION[$ia[1]]==''){
	    	$value=1;
	    }else{
	    	$value=$_SESSION[$ia[1]];
	    }
	    // write the alternative HTML only for activated javascript
	    $answer.="
			<script type=\"text/javascript\">
			document.write('";
	    $answer.="<div style=\"float:left;\">'+
    		'<div style=\"text-align:center; margin-bottom:6px; width:370px;\"><div style=\"width:2%; float:left;\">1</div><div style=\"width:46%;float:left;\">2</div><div style=\"width:4%;float:left;\">3</div><div style=\"width:46%;float:left;\">4</div><div style=\"width:2%;float:left;\">5</div></div><br/>'+
    		'<div id=\"{$id}sliderBg\" style=\"background-image:url(\'{$imageurl}/sliderBg.png\'); text-align:center; background-repeat:no-repeat; height:22px; width:396px;\">'+
    		'<center>'+
    		'<div id=\"{$id}slider\" style=\"width:365px;\"></div>'+
    		'</center>'+
    		'</div></div>'+
    		'<div id=\"{$id}emoticon\" style=\"text-align:left; margin:10px; padding-left:10px;\"><img id=\"{$id}img1\" style=\"margin-left:10px;\" src=\".{$imageurl}/emoticons/{$value}.png\"/><img id=\"{$id}img2\" style=\"margin-left:-31px;margin-top:-31px;\" src=\"{$imageurl}/emoticons/{$value}.png\" />'+
    		'</div>";
	    $answer.="');
			</script>
			";
	    $answer.="
			<script type=\"text/javascript\">
				$('#$id').hide();
				var value=$value;
				var checked = $('#$id input:checked').attr('value');
				if(checked!=''){
					value=checked;
    			}
    			var time=200;
    			var old=value;
				$('#{$id}slider').slider({
				value: value,
				min: 1,
				max: 5,
				step: 1,
				slide: function(event,ui){
						$('#{$id}img2').attr('src','{$imageurl}/emoticons/'+ui.value+'.png');
						$('#{$id}img2').fadeIn(time);
						$('#$id input').each(function(){ $(this).removeAttr('checked');});
    					$('#answer$ia[1]'+ui.value).attr('checked','checked');
						$('#{$id}img1').fadeOut(time,function(){
    						$('#{$id}img1').attr('src',$('#{$id}img2').attr('src'));
    						$('#{$id}img1').show();
    						$('#{$id}img2').hide();
    					});
        $checkconditionFunction(ui.value,'$ia[1]','radio');
    				}
				});
				$('#{$id}slider a').css('background-image', 'url(\'{$imageurl}/slider.png\')');
				$('#{$id}slider a').css('width', '11px');
				$('#{$id}slider a').css('height', '28px');
				$('#{$id}slider a').css('border', 'none');
				//$('#{$id}slider').css('background-image', 'url(\'{$imageurl}/sliderBg.png\')');
				$('#{$id}slider').css('visibility','hidden');
				$('#{$id}slider a').css('visibility', 'visible');
			</script>
			";

    }
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
function do_date($ia)
{
    global $clang, $js_header_includes, $css_header_includes, $thissurvey;
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    $js_header_includes[] = '/scripts/jquery/lime-calendar.js';


    $checkconditionFunction = "checkconditions";

    $dateformatdetails=getDateFormatData($thissurvey['surveyls_dateformat']);
    $numberformatdatat = getRadixPointData($thissurvey['surveyls_numberformat']);

    if (trim($qidattributes['dropdown_dates'])!=0) {
        if (!empty($_SESSION[$ia[1]]))
        {
            list($currentyear, $currentmonth, $currentdate) = explode('-', $_SESSION[$ia[1]]);
        } else {
            $currentdate='';
            $currentmonth='';
            $currentyear='';
        }

        $dateorder = preg_split('/[-\.\/ ]/', $dateformatdetails['phpdate']);
        $answer='<p class="question">';
        foreach($dateorder as $datepart)
        {
            switch($datepart)
            {
                // Show day select box
                case 'j':
                case 'd':   $answer .= ' <label for="day'.$ia[1].'" class="hide">'.$clang->gT('Day').'</label><select id="day'.$ia[1].'" class="day">
                                                <option value="">'.$clang->gT('Day')."</option>\n";
                for ($i=1; $i<=31; $i++) {
                    if ($i == $currentdate)
                    {
                        $i_date_selected = SELECTED;
                    }
                    else
                    {
                        $i_date_selected = '';
                    }
                    $answer .= '    <option value="'.sprintf('%02d', $i).'"'.$i_date_selected.'>'.sprintf('%02d', $i)."</option>\n";
                }
                $answer .='</select>';
                break;
                // Show month select box
                case 'n':
                case 'm':   $answer .= ' <label for="month'.$ia[1].'" class="hide">'.$clang->gT('Month').'</label><select id="month'.$ia[1].'" class="month">
                                            <option value="">'.$clang->gT('Month')."</option>\n";
                $montharray=array(
                $clang->gT('Jan'),
                $clang->gT('Feb'),
                $clang->gT('Mar'),
                $clang->gT('Apr'),
                $clang->gT('May'),
                $clang->gT('Jun'),
                $clang->gT('Jul'),
                $clang->gT('Aug'),
                $clang->gT('Sep'),
                $clang->gT('Oct'),
                $clang->gT('Nov'),
                $clang->gT('Dec'));
                for ($i=1; $i<=12; $i++) {
                    if ($i == $currentmonth)
                    {
                        $i_date_selected = SELECTED;
                    }
                    else
                    {
                        $i_date_selected = '';
                    }

                    $answer .= '    <option value="'.sprintf('%02d', $i).'"'.$i_date_selected.'>'.$montharray[$i-1].'</option>';
                }
                $answer .= '    </select>';
                break;
                // Show year select box
                case 'Y':   $answer .= ' <label for="year'.$ia[1].'" class="hide">'.$clang->gT('Year').'</label><select id="year'.$ia[1].'" class="year">
                                            <option value="">'.$clang->gT('Year').'</option>';

                /*
                 *  New question attributes used only if question attribute
                 * "dropdown_dates" is used (see IF(...) above).
                 *
                 * yearmin = Minimum year value for dropdown list, if not set default is 1900
                 * yearmax = Maximum year value for dropdown list, if not set default is 2020
                 */
                if (trim($qidattributes['dropdown_dates_year_min'])!='')
                {
                    $yearmin = $qidattributes['dropdown_dates_year_min'];
                }
                else
                {
                    $yearmin = 1900;
                }

                if (trim($qidattributes['dropdown_dates_year_max'])!='')
                {
                    $yearmax = $qidattributes['dropdown_dates_year_max'];
                }
                else
                {
                    $yearmax = 2020;
                }

                if ($yearmin > $yearmax)
                {
                    $yearmin = 1900;
                    $yearmax = 2020;
                }

                if ($qidattributes['reverse']==1)
                {
                    $tmp = $yearmin;
                    $yearmin = $yearmax;
                    $yearmax = $tmp;
                    $step = 1;
                    $reverse = true;
                }
                else
                {
                    $step = -1;
                    $reverse = false;
                }

                for ($i=$yearmax; ($reverse? $i<=$yearmin: $i>=$yearmin); $i+=$step) {
                    if ($i == $currentyear)
                    {
                        $i_date_selected = SELECTED;
                    }
                    else
                    {
                        $i_date_selected = '';
                    }
                    $answer .= '  <option value="'.$i.'"'.$i_date_selected.'>'.$i.'</option>';
                }
                $answer .= '</select>';

                break;
            }
        }

        $answer .= '<input class="text" type="text" size="10" name="'.$ia[1].'" style="display: none" id="answer'.$ia[1].'" value="'.$_SESSION[$ia[1]].'" maxlength="10" alt="'.$clang->gT('Answer').'" onchange="'.$checkconditionFunction.'(this.value, this.name, this.type)" />
			</p>';
        $answer .= '<input type="hidden" name="qattribute_answer[]" value="'.$ia[1].'" />
			        <input type="hidden" id="qattribute_answer'.$ia[1].'" name="qattribute_answer'.$ia[1].'" />
                    <input type="hidden" id="dateformat'.$ia[1].'" value="'.$dateformatdetails['jsdate'].'"/>';


    }
    else
    {
        if ($clang->langcode !== 'en')
        {
        $js_header_includes[] = '/scripts/jquery/locale/jquery.ui.datepicker-'.$clang->langcode.'.js';
        }
        $css_header_includes[]= '/scripts/jquery/css/start/jquery-ui.css';

        // Format the date  for output
        if (trim($_SESSION[$ia[1]])!='')
        {
            $datetimeobj = new Date_Time_Converter($_SESSION[$ia[1]] , "Y-m-d");
            $dateoutput=$datetimeobj->convert($dateformatdetails['phpdate']);
        }
        else
        {
            $dateoutput='';
        }

        if (trim($qidattributes['dropdown_dates_year_min'])!='') {
            $minyear=$qidattributes['dropdown_dates_year_min'];
        }
        else
        {
            $minyear='1980';
        }

        if (trim($qidattributes['dropdown_dates_year_max'])!='') {
            $maxyear=$qidattributes['dropdown_dates_year_max'];
        }
        else
        {
            $maxyear='2020';
        }

        $goodchars = str_replace( array("m","d","y"), "", $dateformatdetails['jsdate']);
        $goodchars = "0123456789".$goodchars[0];

        $answer ="<p class=\"question\">
                        <label for='answer{$ia[1]}' class='hide label'>{$clang->gT('Date picker')}</label>
                        <input class='popupdate' type=\"text\" title=\"".sprintf($clang->gT('Format: %s'),$dateformatdetails['dateformat'])."\" size=\"10\" name=\"{$ia[1]}\" id=\"answer{$ia[1]}\" value=\"$dateoutput\" maxlength=\"10\" onkeypress=\"return goodchars(event,'".$goodchars."')\" onchange=\"$checkconditionFunction(this.value, this.name, this.type)\" />
                        <input  type='hidden' name='dateformat{$ia[1]}' id='dateformat{$ia[1]}' value='{$dateformatdetails['jsdate']}'  />
                        <input  type='hidden' name='datelanguage{$ia[1]}' id='datelanguage{$ia[1]}' value='{$clang->langcode}'  />
                        <input  type='hidden' name='dateyearrange{$ia[1]}' id='dateyearrange{$ia[1]}' value='{$minyear}:{$maxyear}'  />

			         </p>
			         <p class=\"tip\">
				         ".sprintf($clang->gT('Format: %s'),$dateformatdetails['dateformat'])."
			         </p>";
    }
    $inputnames[]=$ia[1];

    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
function do_language($ia)
{
    global $dbprefix, $surveyid, $clang;

    $checkconditionFunction = "checkconditions";

    $answerlangs = GetAdditionalLanguagesFromSurveyID($surveyid);
    $answerlangs [] = GetBaseLanguageFromSurveyID($surveyid);
    $answer = "\n\t<p class=\"question\">\n<label for='answer{$ia[1]}' class='hide label'>{$clang->gT('Choose your language')}</label><select name=\"$ia[1]\" id=\"answer$ia[1]\" onchange=\"document.getElementById('lang').value=this.value; $checkconditionFunction(this.value, this.name, this.type);\">\n";
    if (!$_SESSION[$ia[1]]) {$answer .= "\t<option value=\"\" selected=\"selected\">".$clang->gT('Please choose...')."</option>\n";}
    foreach ($answerlangs as $ansrow)
    {
        $answer .= "\t<option value=\"{$ansrow}\"";
        if ($_SESSION[$ia[1]] == $ansrow)
        {
            $answer .= SELECTED;
        }
        $answer .= '>'.getLanguageNameFromCode($ansrow, true)."</option>\n";
    }
    $answer .= "</select>\n";
    $answer .= "<input type=\"hidden\" name=\"java$ia[1]\" id=\"java$ia[1]\" value=\"{$_SESSION[$ia[1]]}\" />\n";

    $inputnames[]=$ia[1];
    $answer .= "\n<input type=\"hidden\" name=\"lang\" id=\"lang\" value=\"\" />\n\t</p>\n";

    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_list_dropdown($ia)
{
    global $dbprefix,  $dropdownthreshold, $lwcdropdowns, $connect;
    global $clang;
    $checkconditionFunction = "checkconditions";
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    if (trim($qidattributes['other_replace_text'])!='')
    {
        $othertext=$qidattributes['other_replace_text'];
    }
    else
    {
        $othertext=$clang->gT('Other:');
    }

    if (trim($qidattributes['category_separator'])!='')
    {
        $optCategorySeparator = $qidattributes['category_separator'];
    }



    $answer='';


    $query = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."' ";
    $result = db_execute_assoc($query);      //Checked
    while($row = $result->FetchRow()) {$other = $row['other'];}

    //question attribute random order set?
    if ($qidattributes['random_order']==1)
    {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY ".db_random();
    }
    //question attribute alphasort set?
    elseif ($qidattributes['alphasort']==1)
    {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY answer";
    }
    //no question attributes -> order by sortorder
    else
    {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY sortorder, answer";
    }

    $ansresult = db_execute_assoc($ansquery) or safe_die('Couldn\'t get answers<br />'.$ansquery.'<br />'.$connect->ErrorMsg());    //Checked

    $dropdownSize = '';
    if (isset($qidattributes['dropdown_size']) && $qidattributes['dropdown_size'] > 0)
    {
        $_height = sanitize_int($qidattributes['dropdown_size']) ;
        $_maxHeight = $ansresult->RowCount();
        if ((isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] != '') && $ia[6] != 'Y' && $ia[6] != 'Y' && SHOW_NO_ANSWER == 1) {
            ++$_maxHeight;  // for No Answer
        }
        if (isset($other) && $other=='Y') {
            ++$_maxHeight;  // for Other
        }
        if (!$_SESSION[$ia[1]]) {
            ++$_maxHeight;  // for 'Please choose:'
        }

        if ($_height > $_maxHeight) {
            $_height = $_maxHeight;
        }
        $dropdownSize = ' size="'.$_height.'"';
    }

    $prefixStyle = 0;
    if (isset($qidattributes['dropdown_prefix']))
    {
        $prefixStyle = sanitize_int($qidattributes['dropdown_prefix']) ;
    }
    $_rowNum=0;
    $_prefix='';

    if (!isset($optCategorySeparator))
    {
        while ($ansrow = $ansresult->FetchRow())
        {
            $opt_select = '';
            if ($_SESSION[$ia[1]] == $ansrow['code'])
            {
                $opt_select = SELECTED;
            }
            if ($prefixStyle == 1) {
                $_prefix = ++$_rowNum . ') ';
            }
            $answer .= "<option value='{$ansrow['code']}' {$opt_select}>{$_prefix}{$ansrow['answer']}</option>\n";
        }
    }
    else
    {
        $defaultopts = Array();
        $optgroups = Array();
        while ($ansrow = $ansresult->FetchRow())
        {
            // Let's sort answers in an array indexed by subcategories
            @list ($categorytext, $answertext) = explode($optCategorySeparator,$ansrow['answer']);
            // The blank category is left at the end outside optgroups
            if ($categorytext == '')
            {
                $defaultopts[] = array ( 'code' => $ansrow['code'], 'answer' => $answertext);
            }
            else
            {
                $optgroups[$categorytext][] = array ( 'code' => $ansrow['code'], 'answer' => $answertext);
            }


        }

        foreach ($optgroups as $categoryname => $optionlistarray)
        {
            $answer .= '                                   <optgroup class="dropdowncategory" label="'.$categoryname.'">
                                ';

            foreach ($optionlistarray as $optionarray)
            {
                if ($_SESSION[$ia[1]] == $optionarray['code'])
                {
                    $opt_select = SELECTED;
                }
                else
                {
                    $opt_select = '';
                }

                $answer .= '     					<option value="'.$optionarray['code'].'"'.$opt_select.'>'.$optionarray['answer'].'</option>
					';
            }

            $answer .= '                                   </optgroup>';
        }
        $opt_select='';
        foreach ($defaultopts as $optionarray)
        {
            if ($_SESSION[$ia[1]] == $optionarray['code'])
            {
                $opt_select = SELECTED;
            }
            else
            {
                $opt_select = '';
            }

            $answer .= '     					<option value="'.$optionarray['code'].'"'.$opt_select.'>'.$optionarray['answer'].'</option>
				';
        }
    }

    if (!$_SESSION[$ia[1]])
    {
        $answer = '					<option value=""'.SELECTED.'>'.$clang->gT('Please choose...').'</option>'."\n".$answer;
    }

    if (isset($other) && $other=='Y')
    {
        if ($_SESSION[$ia[1]] == '-oth-')
        {
            $opt_select = SELECTED;
        }
        else
        {
            $opt_select = '';
        }
        if ($prefixStyle == 1) {
            $_prefix = ++$_rowNum . ') ';
        }
        $answer .= '<option value="-oth-"'.$opt_select.'>'.$_prefix.$othertext."</option>\n";
    }

    if ((isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] != '') && $ia[6] != 'Y' && $ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
    {
        if ($prefixStyle == 1) {
            $_prefix = ++$_rowNum . ') ';
        }
        $answer .= '<option value="">'.$_prefix.$clang->gT('No answer')."</option>\n";
    }
    $answer .= '</select><input type="hidden" name="java'.$ia[1].'" id="java'.$ia[1].'" value="'.$_SESSION[$ia[1]].'" />';

    if (isset($other) && $other=='Y')
    {
        $sselect_show_hide = ' showhideother(this.name, this.value);';
    }
    else
    {
        $sselect_show_hide = '';
    }
    $sselect = '
			<p class="question">
                <label for="answer'.$ia[1].'" class="hide label">'.$clang->gT('Please choose').'</label>
				<select name="'.$ia[1].'" id="answer'.$ia[1].'"'.$dropdownSize.' onchange="'.$checkconditionFunction.'(this.value, this.name, this.type);'.$sselect_show_hide.'">
    ';
    $answer = $sselect.$answer;

    if (isset($other) && $other=='Y')
    {
        $answer = "\n<script type=\"text/javascript\">\n"
        ."<!--\n"
        ."function showhideother(name, value)\n"
        ."\t{\n"
        ."\tvar hiddenothername='othertext'+name;\n"
        ."\tif (value == \"-oth-\")\n"
        ."{\n"
        ."document.getElementById(hiddenothername).style.display='';\n"
        ."document.getElementById(hiddenothername).focus();\n"
        ."}\n"
        ."\telse\n"
        ."{\n"
        ."document.getElementById(hiddenothername).style.display='none';\n"
        ."document.getElementById(hiddenothername).value='';\n" // reset othercomment field
        ."}\n"
        ."\t}\n"
        ."//--></script>\n".$answer;
        $answer .= '				<label for="othertext'.$ia[1].'"><input type="text" id="othertext'.$ia[1].'" name="'.$ia[1].'other" style="display:';

        $inputnames[]=$ia[1].'other';

        if ($_SESSION[$ia[1]] != '-oth-')
        {
            $answer .= 'none';
        }

        $answer .= '"';

        $answer .= "  alt='".$clang->gT('Other answer')."' onchange='$checkconditionFunction(this.value, this.name, this.type);'";
        $thisfieldname="$ia[1]other";
        if (isset($_SESSION[$thisfieldname])) { $answer .= " value='".htmlspecialchars($_SESSION[$thisfieldname],ENT_QUOTES)."' ";}
        $answer .= ' /></label>';
        $answer .= "</p>";
        $inputnames[]=$ia[1]."other";
    }
    else
    {
        $answer .= "</p>";
    }

    $inputnames[]=$ia[1];
    return array($answer, $inputnames);
}








// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_list_radio($ia)
{
    global $dbprefix, $dropdownthreshold, $lwcdropdowns, $connect, $clang;
    global $thissurvey;

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "text-keypad";
    }
    else
    {
        $kpclass = "";
    }

    $checkconditionFunction = "checkconditions";

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    $query = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."' ";
    $result = db_execute_assoc($query);  //Checked
    while($row = $result->FetchRow())
    {
        $other = $row['other'];
    }

    //question attribute random order set?
    if ($qidattributes['random_order']==1) {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY ".db_random();
    }

    //question attribute alphasort set?
    elseif ($qidattributes['alphasort']==1)
    {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY answer";
    }

    //no question attributes -> order by sortorder
    else
    {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY sortorder, answer";
    }

    $ansresult = db_execute_assoc($ansquery) or safe_die('Couldn\'t get answers<br />$ansquery<br />'.$connect->ErrorMsg());  //Checked
    $anscount = $ansresult->RecordCount();


    if (trim($qidattributes['display_columns'])!='') {
        $dcols = $qidattributes['display_columns'];
    }
    else
    {
        $dcols= 1;
    }

    if (trim($qidattributes['other_replace_text'])!='')
    {
        $othertext=$qidattributes['other_replace_text'];
    }
    else
    {
        $othertext=$clang->gT('Other:');
    }

    if (isset($other) && $other=='Y') {$anscount++;} //Count up for the Other answer
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) {$anscount++;} //Count up if "No answer" is showing

    $wrapper = setup_columns($dcols , $anscount);
    $answer = $wrapper['whole-start'];

    // Get array_filter stuff

    $rowcounter = 0;
    $colcounter = 1;
    $trbc='';

    while ($ansrow = $ansresult->FetchRow())
    {
        $myfname = $ia[1].$ansrow['code'];
        $check_ans = '';
        if ($_SESSION[$ia[1]] == $ansrow['code'])
        {
            $check_ans = CHECKED;
        }

        list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname, "li");

        if($wrapper['item-start'] == "\t<li>\n")
        {
            $startitem = "\t$htmltbody2\n";
        } else {
            $startitem = $wrapper['item-start'];
        }

	//queXS check if this is designed to set an outcome:
	$quexs_outcome = false;
	$quexs_outcome_code = 0;
	if (strncasecmp($ansrow['answer'],"{OUTCOME:",9) == 0)
	{
		$quexs_pos = strrpos($ansrow['answer'],"}",8);
		if ($quexs_pos != false)
		{
			$quexs_outcome_code = substr($ansrow['answer'],9,$quexs_pos - 9);
			$quexs_outcome = true;
			include_once(dirname(__FILE__) . '/quexs.php');
			$ansrow['answer'] = quexs_template_replace($ansrow['answer']);
		}
	}	

	//queXS check if this is designed to schedule an appointment:
	$quexs_appointment = false;
	if (strncasecmp($ansrow['answer'],"{SCHEDULEAPPOINTMENT}",21) == 0)
	{
	  include_once(dirname(__FILE__) . '/quexs.php');
		$ansrow['answer'] = T_("Schedule Appointment");
		$quexs_appointment = true;
	}


        $answer .= $startitem;
        $answer .= "\t$hiddenfield\n";
        $answer .='		<input class="radio" type="radio" value="'.$ansrow['code'].'" name="'.$ia[1].'" id="answer'.$ia[1].$ansrow['code'].'"'.$check_ans.' onclick="if (document.getElementById(\'answer'.$ia[1].'othertext\') != null) document.getElementById(\'answer'.$ia[1].'othertext\').value=\'\';'.$checkconditionFunction.'(this.value, this.name, this.type)' . quexs_submit_on_click(!$quexs_outcome && !$quexs_appointment).quexs_outcome($quexs_outcome_code).quexs_appointment($quexs_appointment)  . '" />
		<label for="answer'.$ia[1].$ansrow['code'].'" class="answertext">'.$ansrow['answer'].'</label>
        '.$wrapper['item-end'];

        ++$rowcounter;
        if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
        {
            if($colcounter == $wrapper['cols'] - 1)
            {
                $answer .= $wrapper['col-devide-last'];
            }
            else
            {
                $answer .= $wrapper['col-devide'];
            }
            $rowcounter = 0;
            ++$colcounter;
        }
    }

    if (isset($other) && $other=='Y')
    {

        $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
        $sSeperator = $sSeperator['seperator'];

        if ($qidattributes['other_numbers_only']==1)
        {
            $oth_checkconditionFunction = 'fixnum_checkconditions';
        }
        else
        {
            $oth_checkconditionFunction = 'checkconditions';
        }


        if ($_SESSION[$ia[1]] == '-oth-')
        {
            $check_ans = CHECKED;
        }
        else
        {
            $check_ans = '';
        }

        $thisfieldname=$ia[1].'other';
        if (isset($_SESSION[$thisfieldname]))
        {
            $dispVal = $_SESSION[$thisfieldname];
            if ($qidattributes['other_numbers_only']==1)
            {
                $dispVal = str_replace('.',$sSeperator,$dispVal);
            }
            $answer_other = ' value="'.htmlspecialchars($dispVal,ENT_QUOTES).'"';
        }
        else
        {
            $answer_other = ' value=""';
        }

        list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, array("code"=>"other"), $thisfieldname, $trbc, $myfname, "li", "other");

        if($wrapper['item-start-other'] == "\t<li class=\"other\">\n")
        {
            $startitem = "\t$htmltbody2\n";
        } else {
            $startitem = $wrapper['item-start-other'];
        }
        $answer .= $startitem;
        $answer .= "\t$hiddenfield\n";
        $answer .= '		<input class="radio" type="radio" value="-oth-" name="'.$ia[1].'" id="SOTH'.$ia[1].'"'.$check_ans.' onclick="'.$checkconditionFunction.'(this.value, this.name, this.type)" />
		<label for="SOTH'.$ia[1].'" class="answertext">'.$othertext.'</label>
		<label for="answer'.$ia[1].'othertext">
			<input type="text" class="text '.$kpclass.'" id="answer'.$ia[1].'othertext" name="'.$ia[1].'other" title="'.$clang->gT('Other').'"'.$answer_other.' onkeyup="if($.trim($(this).val())!=\'\'){ $(\'#SOTH'.$ia[1].'\').attr(\'checked\',\'checked\'); }; '.$oth_checkconditionFunction.'(this.value, this.name, this.type);" />
		</label>
        '.$wrapper['item-end'];

        $inputnames[]=$thisfieldname;

        ++$rowcounter;
        if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
        {
            if($colcounter == $wrapper['cols'] - 1)
            {
                $answer .= $wrapper['col-devide-last'];
            }
            else
            {
                $answer .= $wrapper['col-devide'];
            }
            $rowcounter = 0;
            ++$colcounter;
        }
    }

    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
    {
        if ((!isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] == '') || ($_SESSION[$ia[1]] == ' ' ))
        {
            $check_ans = CHECKED; //Check the "no answer" radio button if there is no answer in session.
        }
        else
        {
            $check_ans = '';
        }

        $answer .= $wrapper['item-start'].'		<input class="radio" type="radio" name="'.$ia[1].'" id="answer'.$ia[1].'NANS" value=""'.$check_ans.' onclick="if (document.getElementById(\'answer'.$ia[1].'othertext\') != null) document.getElementById(\'answer'.$ia[1].'othertext\').value=\'\';'.$checkconditionFunction.'(this.value, this.name, this.type)" />
		<label for="answer'.$ia[1].'NANS" class="answertext">'.$clang->gT('No answer').'</label>
        '.$wrapper['item-end'];

        ++$rowcounter;
        if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
        {
            if($colcounter == $wrapper['cols'] - 1)
            {
                $answer .= $wrapper['col-devide-last'];
            }
            else
            {
                $answer .= $wrapper['col-devide'];
            }
            $rowcounter = 0;
            ++$colcounter;
        }

    }
    //END OF ITEMS
    $answer .= $wrapper['whole-end'].'
    <input type="hidden" name="java'.$ia[1].'" id="java'.$ia[1]."\" value=\"{$_SESSION[$ia[1]]}\" />\n";

    $inputnames[]=$ia[1];
    return array($answer, $inputnames);
}

// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_listwithcomment($ia)
{
    global $maxoptionsize, $dbprefix, $dropdownthreshold, $lwcdropdowns, $thissurvey;
    global $clang;

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "text-keypad";
    }
    else
    {
        $kpclass = "";
    }

    $checkconditionFunction = "checkconditions";

    $answer = '';

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    if (!isset($maxoptionsize)) {$maxoptionsize=35;}

    //question attribute random order set?
    if ($qidattributes['random_order']==1) {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY ".db_random();
    }
    //question attribute alphasort set?
    elseif ($qidattributes['alphasort']==1)
    {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY answer";
    }
    //no question attributes -> order by sortorder
    else
    {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY sortorder, answer";
    }

    $ansresult = db_execute_assoc($ansquery);      //Checked
    $anscount = $ansresult->RecordCount();


    $hint_comment = $clang->gT('Please enter your comment here');

    if ($lwcdropdowns == 'R' && $anscount <= $dropdownthreshold)
    {
        $answer .= '<div class="list">
	                    <ul>
                    ';

        while ($ansrow=$ansresult->FetchRow())
        {
            $check_ans = '';
            if ($_SESSION[$ia[1]] == $ansrow['code'])
            {
                $check_ans = CHECKED;
            }

		//queXS check if this is designed to set an outcome:
		$quexs_outcome = false;
		$quexs_outcome_code = 0;
		if (strncasecmp($ansrow['answer'],"{OUTCOME:",9) == 0)
		{
			$quexs_pos = strrpos($ansrow['answer'],"}",8);
			if ($quexs_pos != false)
			{
				$quexs_outcome_code = substr($ansrow['answer'],9,$quexs_pos - 9);
				$quexs_outcome = true;
				include_once(dirname(__FILE__) . '/quexs.php');
				$ansrow['answer'] = quexs_template_replace($ansrow['answer']);
			}
		}	

		//queXS check if this is designed to schedule an appointment:
		$quexs_appointment = false;
		if (strncasecmp($ansrow['answer'],"{SCHEDULEAPPOINTMENT}",21) == 0)
		{
      include_once(dirname(__FILE__) . '/quexs.php');
			$ansrow['answer'] = T_("Schedule Appointment");
			$quexs_appointment = true;
		}


            $answer .= '		<li>
			<input type="radio" name="'.$ia[1].'" id="answer'.$ia[1].$ansrow['code'].'" value="'.$ansrow['code'].'" class="radio" '.$check_ans.' onclick="'.$checkconditionFunction.'(this.value, this.name, this.type)' . quexs_submit_on_click(!$quexs_outcome && !$quexs_appointment).quexs_outcome($quexs_outcome_code).quexs_appointment($quexs_appointment)  . '" />
			<label for="answer'.$ia[1].$ansrow['code'].'" class="answertext">'.$ansrow['answer'].'</label>
		</li>
            ';
        }

        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
        {
            if ((!isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] == '') ||($_SESSION[$ia[1]] == ' ' ))
            {
                $check_ans = CHECKED;
            }
            elseif ((isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] != ''))
            {
                $check_ans = '';
            }
            $answer .= '		<li>
			<input class="radio" type="radio" name="'.$ia[1].'" id="answer'.$ia[1].'" value=" " onclick="'.$checkconditionFunction.'(this.value, this.name, this.type)"'.$check_ans.' />
			<label for="answer'.$ia[1].'" class="answertext">'.$clang->gT('No answer').'</label>
		</li>
            ';
        }

        $fname2 = $ia[1].'comment';
        if ($anscount > 8) {$tarows = $anscount/1.2;} else {$tarows = 4;}
        $answer .= '	</ul>
        </div>

        <p class="comment">
	<label for="answer'.$ia[1].'comment">'.$hint_comment.':</label>

	<textarea class="textarea '.$kpclass.'" name="'.$ia[1].'comment" id="answer'.$ia[1].'comment" rows="'.floor($tarows).'" cols="30" >';
        if (isset($_SESSION[$fname2]) && $_SESSION[$fname2])
        {
            $answer .= htmlspecialchars(htmlspecialchars(str_replace("\\", "", $_SESSION[$fname2])));
        }
        $answer .= '</textarea>
        </p>

        <input class="radio" type="hidden" name="java'.$ia[1].'" id="java'.$ia[1]."\" value=\"{$_SESSION[$ia[1]]}\" />
        ";
        $inputnames[]=$ia[1];
        $inputnames[]=$ia[1].'comment';
    }
    else //Dropdown list
    {
        $answer .= '<p class="select">
        <select class="select" name="'.$ia[1].'" id="answer'.$ia[1].'" onchange="'.$checkconditionFunction.'(this.value, this.name, this.type)" >
        ';
        while ($ansrow=$ansresult->FetchRow())
        {
            $check_ans = '';
            if ($_SESSION[$ia[1]] == $ansrow['code'])
            {
                $check_ans = SELECTED;
            }
            $answer .= '		<option value="'.$ansrow['code'].'"'.$check_ans.'>'.$ansrow['answer']."</option>\n";

            if (strlen($ansrow['answer']) > $maxoptionsize)
            {
                $maxoptionsize = strlen($ansrow['answer']);
            }
        }
        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
        {
            if ((!isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] == '') ||($_SESSION[$ia[1]] == ' '))
            {
                $check_ans = SELECTED;
            }
            elseif (isset($_SESSION[$ia[1]]) || $_SESSION[$ia[1]] != '')
            {
                $check_ans = '';
            }
            $answer .= '<option value=""'.$check_ans.'>'.$clang->gT('No answer')."</option>\n";
        }
        $answer .= '	</select>
        </p>
        ';
        $fname2 = $ia[1].'comment';
        if ($anscount > 8) {$tarows = $anscount/1.2;} else {$tarows = 4;}
        if ($tarows > 15) {$tarows=15;}
        $maxoptionsize=$maxoptionsize*0.72;
        if ($maxoptionsize < 33) {$maxoptionsize=33;}
        if ($maxoptionsize > 70) {$maxoptionsize=70;}
        $answer .= '<p class="comment">
	'.$hint_comment.'
	<textarea class="textarea '.$kpclass.'" name="'.$ia[1].'comment" id="answer'.$ia[1].'comment" rows="'.$tarows.'" cols="'.$maxoptionsize.'" >';
        if (isset($_SESSION[$fname2]) && $_SESSION[$fname2])
        {
            $answer .= htmlspecialchars(htmlspecialchars(str_replace("\\", "", $_SESSION[$fname2])));
        }
        $answer .= '</textarea>
	<input class="radio" type="hidden" name="java'.$ia[1].'" id="java'.$ia[1]." value=\"{$_SESSION[$ia[1]]}\" />\n</p>\n";
        $inputnames[]=$ia[1];
        $inputnames[]=$ia[1].'comment';
    }
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_ranking($ia)
{
    global $dbprefix, $imageurl, $clang, $thissurvey, $showpopups;

    $checkconditionFunction = "checkconditions";

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    $answer="";
    if ($qidattributes['random_order']==1) {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY ".db_random();
    } else {
        $ansquery = "SELECT * FROM {$dbprefix}answers WHERE qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY sortorder, answer";
    }
    $ansresult = db_execute_assoc($ansquery);   //Checked
    $anscount= $ansresult->RecordCount();
    if (trim($qidattributes["max_answers"])!='')
    {
        $max_answers=trim($qidattributes["max_answers"]);
        $max_ans_val = LimeExpressionManager::ProcessString('{'.$max_answers.'}',$ia[0]);
        if (!is_numeric($max_ans_val))  // this happens when try to do dynamic max ranking values and the starting value is blank
        {
            $max_ans_val = $anscount;
        }
    } else {
        $max_answers=$anscount;
        $max_ans_val = $anscount;
    }
    if (trim($qidattributes["min_answers"])!='')
    {
        $min_answers = trim($qidattributes["min_answers"]);
    }
    else
    {
        $min_answers = 0;
    }

    $answer .= "\t<script type='text/javascript'>\n"
    . "\t<!--\n"
    . "function rankthis_{$ia[0]}(\$code, \$value)\n"
    . "\t{\n"
    . "\t\$index=document.getElementById('CHOICES_{$ia[0]}').selectedIndex;\n"
    . "\tvar _maxans = $.trim(LEMstrip_tags($('#RANK_{$ia[0]}_maxans').html()));\n"
    . "\tvar _minans = $.trim(LEMstrip_tags($('#RANK_{$ia[0]}_minans').html()));\n"
    . "\tvar maxval = (LEMempty(_maxans) ? $anscount : Math.floor(_maxans));\n"
    . "\tif (($anscount - document.getElementById('CHOICES_{$ia[0]}').options.length) >= maxval) {\n"
    . "\t\tdocument.getElementById('CHOICES_{$ia[0]}').disabled=true;\n"
    . "\t\tdocument.getElementById('CHOICES_{$ia[0]}').selectedIndex=-1;\n"
    . "\t\treturn true;\n"
    . "\t}\n"
    . "\tfor (i=1; i<=maxval; i++)\n"
    . "{\n"
    . "\$b=i;\n"
    . "\$b += '';\n"
    . "\$inputname=\"RANK_{$ia[0]}\"+\$b;\n"
    . "\$hiddenname=\"fvalue_{$ia[0]}\"+\$b;\n"
    . "\$cutname=\"cut_{$ia[0]}\"+i;\n"
    . "document.getElementById(\$cutname).style.display='none';\n"
    . "if (!document.getElementById(\$inputname).value)\n"
    . "\t{\n"
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
    . "\t\t\t\t\t\t\ti=maxval;\n"
    . "\t\t\t\t\t\t\t}\n"
    . "\t\t\t\t\t\t}\n"
    . "\t\t\t\t\tif (document.getElementById('CHOICES_{$ia[0]}').options.length == ($anscount - maxval))\n"
    . "\t\t\t\t\t\t{\n"
    . "\t\t\t\t\t\tdocument.getElementById('CHOICES_{$ia[0]}').disabled=true;\n"
    . "\t\t\t\t\t\t}\n"
    . "\t\t\t\t\tdocument.getElementById('CHOICES_{$ia[0]}').selectedIndex=-1;\n"
    . "\t\t\t\t\t$checkconditionFunction(\$code);\n"
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
    . "\t\t\t\t\t$checkconditionFunction('');\n"
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
    for ($i=1; $i<=floor($max_ans_val); $i++)
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
        $ranklist .= "\t<tr><td class=\"position\">&nbsp;<label for='RANK_{$ia[0]}$i'>"
        ."$i:&nbsp;</label></td><td class=\"item\"><input class=\"text\" type=\"text\" name=\"RANK_{$ia[0]}$i\" id=\"RANK_{$ia[0]}$i\"";
        if (isset($_SESSION[$myfname]) && $_SESSION[$myfname])
        {
            $ranklist .= " value='";
            $ranklist .= htmlspecialchars($thistext, ENT_QUOTES);
            $ranklist .= "'";
        }
        $ranklist .= " onfocus=\"this.blur()\" />\n";
        $ranklist .= "<input type=\"hidden\" name=\"$myfname\" id=\"fvalue_{$ia[0]}$i\" value='";
        $chosen[]=""; //create array
        if (isset($_SESSION[$myfname]) && $_SESSION[$myfname])
        {
            $ranklist .= $thiscode;
            $chosen[]=array($thiscode, $thistext);
        }
        $ranklist .= "' />\n";
        $ranklist .= "<img src=\"$imageurl/cut.gif\" alt=\"".$clang->gT("Remove this item")."\" title=\"".$clang->gT("Remove this item")."\" ";
        if ($i != $existing)
        {
            $ranklist .= "style=\"display:none\"";
        }
        $ranklist .= " id=\"cut_{$ia[0]}$i\" onclick=\"deletethis_{$ia[0]}(document.getElementById('RANK_{$ia[0]}$i').value, document.getElementById('fvalue_{$ia[0]}$i').value, document.getElementById('RANK_{$ia[0]}$i').name, this.id)\" /><br />\n";
        $inputnames[]=$myfname;
        $ranklist .= "</td></tr>\n";
    }

    $maxselectlength=0;
    $choicelist = "<select size=\"$anscount\" name=\"CHOICES_{$ia[0]}\" ";
    if (isset($choicewidth)) {$choicelist.=$choicewidth;}

    $choicelist .= " id=\"CHOICES_{$ia[0]}\" onchange=\"if (this.options.length>0 && this.selectedIndex<0) { this.options[this.options.length-1].selected=true; }; rankthis_{$ia[0]}(this.options[this.selectedIndex].value, this.options[this.selectedIndex].text)\" class=\"select\">\n";

        foreach ($answers as $ans)
        {
            if (!in_array($ans, $chosen))
            {
                $choicelist .= "\t\t\t\t\t\t\t<option id='javatbd{$ia[1]}{$ans[0]}' value='{$ans[0]}'>{$ans[1]}</option>\n";
            }
        if (strlen($ans[1]) > $maxselectlength) {$maxselectlength = strlen($ans[1]);}
        }
    $choicelist .= "</select>\n";

    $answer .= "\t<table border='0' cellspacing='0' class='rank'>\n"
    . "<tr>\n"
    . "\t<td align='left' valign='top' class='rank label'>\n"
    . "<strong>&nbsp;&nbsp;<label for='CHOICES_{$ia[0]}'>".$clang->gT("Your Choices").":</label></strong><br />\n"
    . "&nbsp;".$choicelist
    . "\t&nbsp;</td>\n";
    $maxselectlength=$maxselectlength+2;
    if ($maxselectlength > 60)
    {
        $maxselectlength=60;
    }
    $ranklist = str_replace("<input class=\"text\"", "<input size='{$maxselectlength}' class='text'", $ranklist);
    $answer .= "\t<td style=\"text-align:left; white-space:nowrap;\" class='rank output'>\n"
        . "\t<table border='0' cellspacing='1' cellpadding='0'>\n"
        . "\t<tr><td></td><td><strong>".$clang->gT("Your Ranking").":</strong>"
        . "<div style='display:none' id='RANK_{$ia[0]}_maxans'>{".$max_answers."}</div>"
        . "<div style='display:none' id='RANK_{$ia[0]}_minans'>{".$min_answers."}</div>"
        . "</td></tr>\n";

    $answer .= $ranklist
    . "\t</table>\n"
    . "\t</td>\n"
    . "</tr>\n"
    . "<tr>\n"
    . "\t<td colspan='2' class='rank helptext'><font size='1'>\n"
    . "".$clang->gT("Click on the scissors next to each item on the right to remove the last entry in your ranked list")
    . "\t</font size='1'></td>\n"
    . "</tr>\n"
    . "\t</table>\n";

    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_multiplechoice($ia)
{
    global $dbprefix, $clang, $connect, $thissurvey;

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "text-keypad";
    }
    else
    {
        $kpclass = "";
    }

    // Find out if any questions have attributes which reference this questions
    // based on value of attribute. This could be array_filter and array_filter_exclude

    $attribute_ref=false;
    $inputnames=array();

    $qaquery = "SELECT qid,attribute FROM ".db_table_name('question_attributes')." WHERE value LIKE '".strtolower($ia[2])."' and (attribute='array_filter' or attribute='array_filter_exclude')";
    $qaresult = db_execute_assoc($qaquery);     //Checked
    while($qarow = $qaresult->FetchRow())
    {
        $qquery = "SELECT qid FROM ".db_table_name('questions')." WHERE sid=".$thissurvey['sid']." AND scale_id=0 AND qid=".$qarow['qid'];
        $qresult = db_execute_assoc($qquery);     //Checked
        if ($qresult->RecordCount() > 0)
        {
            $attribute_ref = true;
        }
    }

    $checkconditionFunction = "checkconditions";

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    if (trim($qidattributes['other_replace_text'])!='')
    {
        $othertext=$qidattributes['other_replace_text'];
    }
    else
    {
        $othertext=$clang->gT('Other:');
    }

    if (trim($qidattributes['display_columns'])!='')
    {
        $dcols = $qidattributes['display_columns'];
    }
    else
    {
        $dcols = 1;
    }

    if ($qidattributes['other_numbers_only']==1)
    {
        $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
        $sSeperator= $sSeperator['seperator'];
        $oth_checkconditionFunction = "fixnum_checkconditions";
    }
    else
    {
        $oth_checkconditionFunction = "checkconditions";
    }

    $qquery = "SELECT other FROM ".db_table_name('questions')." WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."' and parent_qid=0";
    $qresult = db_execute_assoc($qquery);     //Checked
    while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}

    if ($qidattributes['random_order']==1) {
        $ansquery = "SELECT * FROM ".db_table_name('questions')." WHERE parent_qid=$ia[0] AND scale_id=0 AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
    }
    else
    {
        $ansquery = "SELECT * FROM ".db_table_name('questions')." WHERE parent_qid=$ia[0] AND scale_id=0 AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
    }

    $ansresult = $connect->GetAll($ansquery);  //Checked
    $anscount = count($ansresult);

    if (trim($qidattributes['exclude_all_others'])!='' && $qidattributes['random_order']==1)
    {
        //if  exclude_all_others is set then the related answer should keep its position at all times
        //thats why we have to re-position it if it has been randomized
        $position=0;
        foreach ($ansresult as $answer)
        {
            if ((trim($qidattributes['exclude_all_others']) != '')  &&    ($answer['title']==trim($qidattributes['exclude_all_others'])))
            {
                if ($position==$answer['question_order']-1) break; //already in the right position
                $tmp  = array_splice($ansresult, $position, 1);
                array_splice($ansresult, $answer['question_order']-1, 0, $tmp);
                break;
            }
            $position++;
        }
    }


    if ($other == 'Y')
    {
        $anscount++; //COUNT OTHER AS AN ANSWER FOR MANDATORY CHECKING!
    }

    $wrapper = setup_columns($dcols, $anscount);

    $answer = '<input type="hidden" name="MULTI'.$ia[1].'" value="'.$anscount."\" />\n\n".$wrapper['whole-start'];

    $fn = 1;
    if (!isset($multifields))
    {
        $multifields = '';
    }

    $rowcounter = 0;
    $colcounter = 1;
    $startitem='';
    $postrow = '';
    $trbc='';
    foreach ($ansresult as $ansrow)
    {
        $myfname = $ia[1].$ansrow['title'];

        $trbc='';
        /* Check for array_filter */
        list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname, "li");

        if($wrapper['item-start'] == "\t<li>\n")
        {
            $startitem = "\t$htmltbody2\n";
        } else {
            $startitem = $wrapper['item-start'];
        }

        /* Print out the checkbox */
        $answer .= $startitem;
        $answer .= "\t$hiddenfield\n";
        $answer .= '		<input class="checkbox" type="checkbox" name="'.$ia[1].$ansrow['title'].'" id="answer'.$ia[1].$ansrow['title'].'" value="Y"';

        /* If the question has already been ticked, check the checkbox */
        if (isset($_SESSION[$myfname]))
        {
            if ($_SESSION[$myfname] == 'Y')
            {
                $answer .= CHECKED;
            }
        }
        $answer .= " onclick='cancelBubbleThis(event);";

        $answer .= ''
        .  "$checkconditionFunction(this.value, this.name, this.type)' />\n"
        .  "<label for=\"answer$ia[1]{$ansrow['title']}\" class=\"answertext\">"
        .  $ansrow['question']
        .  "</label>\n";


        ++$fn;
        /* Now add the hidden field to contain information about this answer */
        $answer .= '		<input type="hidden" name="java'.$myfname.'" id="java'.$myfname.'" value="';
        if (isset($_SESSION[$myfname]))
        {
            $answer .= $_SESSION[$myfname];
        }
        $answer .= "\" />\n{$wrapper['item-end']}";

        $inputnames[]=$myfname;

        ++$rowcounter;
        if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
        {
            if($colcounter == $wrapper['cols'] - 1)
            {
                $answer .= $wrapper['col-devide-last'];
            }
            else
            {
                $answer .= $wrapper['col-devide'];
            }
            $rowcounter = 0;
            ++$colcounter;
        }
    }
    if ($other == 'Y')
    {
        $myfname = $ia[1].'other';
        list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, array("code"=>"other"), $myfname, $trbc, $myfname, "li","other");

        if($wrapper['item-start'] == "\t<li>\n")
        {
            $startitem = "\t$htmltbody2\n";
        } else {
            $startitem = $wrapper['item-start'];
        }
        $answer .= $startitem;
		$answer .= $hiddenfield.'
		<input class="checkbox other-checkbox" type="checkbox" name="'.$myfname.'cbox" alt="'.$clang->gT('Other').'" id="answer'.$myfname.'cbox" ';
		// othercbox can not be display, Can use css to hide it.

        if (isset($_SESSION[$myfname]) && trim($_SESSION[$myfname])!='')
        {
            $answer .= CHECKED;
        }
        $answer .= " onclick='cancelBubbleThis(event);if(this.checked===false){ document.getElementById(\"answer$myfname\").value=\"\"; document.getElementById(\"java$myfname\").value=\"\"; $checkconditionFunction(\"\", \"$myfname\", \"text\"); }";
        $answer .= " if(this.checked===true) { document.getElementById(\"answer$myfname\").focus(); }; LEMflagMandOther(\"$myfname\",this.checked);";
        $answer .= "' />
		<label for=\"answer$myfname\" class=\"answertext\">".$othertext."</label>
		<input class=\"text ".$kpclass."\" type=\"text\" name=\"$myfname\" id=\"answer$myfname\"";
        if (isset($_SESSION[$myfname]))
        {
            $dispVal = $_SESSION[$myfname];
            if ($qidattributes['other_numbers_only']==1)
            {
                $dispVal = str_replace('.',$sSeperator,$dispVal);
            }
            $answer .= ' value="'.htmlspecialchars($dispVal,ENT_QUOTES).'"';
        }
#        $answer .= " onkeyup='if ($.trim(this.value)!=\"\") { \$(\"#answer{$myfname}cbox\").attr(\"checked\",\"checked\"); } else { \$(\"#answer{$myfname}cbox\").attr(\"checked\",\"\"); }; $(\"#java{$myfname}\").val(this.value);$oth_checkconditionFunction(this.value, this.name, this.type); LEMflagMandOther(\"$myfname\",\$(\"#answer{$myfname}cbox\").attr(\"checked\"));' />";
        $answer .=" />";
        $answer .="<script type='text/javascript'>\n";
        $answer .="$('#answer{$myfname}').bind('keyup blur',function(){\n";
        $answer .= " if ($.trim($(this).val())!=\"\") { \$(\"#answer{$myfname}cbox\").attr(\"checked\",true); } else { \$(\"#answer{$myfname}cbox\").attr(\"checked\",false); }; $(\"#java{$myfname}\").val($(this).val());$oth_checkconditionFunction(this.value, this.name, this.type); LEMflagMandOther(\"$myfname\",\$(\"#answer{$myfname}cbox\").attr(\"checked\"));\n";
        $answer .="});\n";
        $answer .="</script>\n";
        $answer .= '<input type="hidden" name="java'.$myfname.'" id="java'.$myfname.'" value="';

        if (isset($_SESSION[$myfname]))
        {
            $dispVal = $_SESSION[$myfname];
            if ($qidattributes['other_numbers_only']==1)
            {
                $dispVal = str_replace('.',$sSeperator,$dispVal);
            }
            $answer .= htmlspecialchars($dispVal,ENT_QUOTES);
        }

        $answer .= "\" />\n{$wrapper['item-end']}";
        $inputnames[]=$myfname;
        ++$anscount;

        ++$rowcounter;
        if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
        {
            if($colcounter == $wrapper['cols'] - 1)
            {
                $answer .= $wrapper['col-devide-last'];
            }
            else
            {
                $answer .= $wrapper['col-devide'];
            }
            $rowcounter = 0;
            ++$colcounter;
        }
    }
    $answer .= $wrapper['whole-end'];

#   No need $checkotherscript : already done by check mandatory
#    $checkotherscript = "";
#    if ($other == 'Y')
#    {
#        // Multiple choice with 'other' is a specific case as the checkbox isn't recorded into DB
#        // this means that if it is cehcked We must force the end-user to enter text in the input
#        // box
#        $checkotherscript = "<script type='text/javascript'>\n"
#        . "\t<!--\n"
#        . "oldonsubmitOther_{$ia[0]} = document.limesurvey.onsubmit;\n"
#        . "function ensureOther_{$ia[0]}()\n"
#        . "{\n"
#        . "\tothercboxval=document.getElementById('answer".$myfname."cbox').checked;\n"
#        . "\totherval=document.getElementById('answer".$myfname."').value;\n"
#        . "\tif (otherval != '' || othercboxval != true) {\n"
#        . "if(typeof oldonsubmitOther_{$ia[0]} == 'function') {\n"
#        . "\treturn oldonsubmitOther_{$ia[0]}();\n"
#        . "}\n"
#        . "\t}\n"
#        . "\telse {\n"
#        . "alert('".sprintf($clang->gT("You've marked the '%s' field, please also fill in the accompanying comment field.","js"),$othertext)."');\n"
#        . "return false;\n"
#        . "\t}\n"
#        . "}\n"
#        . "document.limesurvey.onsubmit = ensureOther_{$ia[0]};\n"
#        . "\t-->\n"
#        . "</script>\n";
#    }

#    $answer = $checkotherscript . $answer;

    $answer .= $postrow;
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_multiplechoice_withcomments($ia)
{
    global $dbprefix, $clang, $thissurvey;

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "text-keypad";
    }
    else
    {
        $kpclass = "";
    }

    $inputnames = array();
    $attribute_ref=false;
    $qaquery = "SELECT qid,attribute FROM ".db_table_name('question_attributes')." WHERE value LIKE '".strtolower($ia[2])."'";
    $qaresult = db_execute_assoc($qaquery);     //Checked
    $attribute_ref=false;
    while($qarow = $qaresult->FetchRow())
    {
        $qquery = "SELECT qid FROM ".db_table_name('questions')." WHERE sid=".$thissurvey['sid']." AND qid=".$qarow['qid'];
        $qresult = db_execute_assoc($qquery);     //Checked
        if ($qresult->RecordCount() > 0)
        {
            $attribute_ref = true;
        }
    }

    $checkconditionFunction = "checkconditions";

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    if ($qidattributes['other_numbers_only']==1)
    {
        $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
        $sSeperator = $sSeperator['seperator'];
        $oth_checkconditionFunction = "fixnum_checkconditions";
    }
    else
    {
        $oth_checkconditionFunction = "checkconditions";
    }

    if (trim($qidattributes['other_replace_text'])!='')
    {
        $othertext=$qidattributes['other_replace_text'];
    }
    else
    {
        $othertext=$clang->gT('Other:');
    }
    // Check if the max_answers attribute is set
    $callmaxanswscriptother = '';

    $qquery = "SELECT other FROM {$dbprefix}questions
               WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."' and parent_qid=0";
    $qresult = db_execute_assoc($qquery);     //Checked
    while ($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}
    if ($qidattributes['random_order']==1) {
        $ansquery = "SELECT * FROM {$dbprefix}questions
                     WHERE parent_qid=$ia[0]  AND language='".$_SESSION['s_lang']."'
                     ORDER BY ".db_random();
    } else {
        $ansquery = "SELECT * FROM {$dbprefix}questions
                     WHERE parent_qid=$ia[0]  AND language='".$_SESSION['s_lang']."'
                     ORDER BY question_order";
    }
    $ansresult = db_execute_assoc($ansquery);  //Checked
    $anscount = $ansresult->RecordCount()*2;

    $answer = "<input type='hidden' name='MULTI$ia[1]' value='$anscount' />\n";
    $answer_main = '';

    $fn = 1;
    if (!isset($other)){
        $other = 'N';
    }
    if($other == 'Y')
    {
        $label_width = 25;
    }
    else
    {
        $label_width = 0;
    }

    while ($ansrow = $ansresult->FetchRow())
    {
        $myfname = $ia[1].$ansrow['title'];
        $trbc='';
        /* Check for array_filter */

        list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname, "li");

        if($label_width < strlen(trim(strip_tags($ansrow['question']))))
        {
            $label_width = strlen(trim(strip_tags($ansrow['question'])));
        }

        $myfname2 = $myfname."comment";
        $startitem = "\t$htmltbody2\n";
        /* Print out the checkbox */
        $answer_main .= $startitem;
        $answer_main .= "\t$hiddenfield\n";
        $answer_main .= "<span class=\"option\">\n"
        . "\t<input class=\"checkbox\" type=\"checkbox\" name=\"$myfname\" id=\"answer$myfname\" value=\"Y\"";

        /* If the question has already been ticked, check the checkbox */
        if (isset($_SESSION[$myfname]))
        {
            if ($_SESSION[$myfname] == 'Y')
            {
                $answer_main .= CHECKED;
            }
        }
        $answer_main .=" onclick='cancelBubbleThis(event);$checkconditionFunction(this.value, this.name, this.type);if (!$(this).attr(\"checked\")) { $(\"#answer$myfname2\").val(\"\"); $checkconditionFunction(document.getElementById(\"answer{$myfname2}\").value,\"$myfname2\",\"checkbox\");}' />\n"
        . "\t<label for=\"answer$myfname\" class=\"answertext\">\n"
        . $ansrow['question']."</label>\n";

        $answer_main .= "<input type='hidden' name='java$myfname' id='java$myfname' value='";
        if (isset($_SESSION[$myfname]))
        {
            $answer_main .= $_SESSION[$myfname];
        }
        $answer_main .= "' />\n";
        $fn++;
        $answer_main .= "</span>\n<span class=\"comment\">\n\t<label for='answer$myfname2' class=\"answer-comment hide\">".$clang->gT("Make a comment on your choice here:")."</label>\n"
        ."<input class='text ".$kpclass."' type='text' size='40' id='answer$myfname2' name='$myfname2' title='".$clang->gT("Make a comment on your choice here:")."' value='";
        if (isset($_SESSION[$myfname2])) {$answer_main .= htmlspecialchars($_SESSION[$myfname2],ENT_QUOTES);}
        $answer_main .= "' onkeyup='if (jQuery.trim($(\"#answer{$myfname2}\").val())!=\"\") { document.getElementById(\"answer{$myfname}\").checked=true;$checkconditionFunction(document.getElementById(\"answer{$myfname2}\").value,\"$myfname2\",\"text\");}' />\n</span>\n"

        . "\t</li>\n";

        $fn++;
        $inputnames[]=$myfname;
        $inputnames[]=$myfname2;
    }
    if ($other == 'Y')
    {
        $myfname = $ia[1].'other';
        $myfname2 = $myfname.'comment';
        $anscount = $anscount + 2;
        $answer_main .= "\t<li class=\"other\" id=\"javatbd$myfname\">\n<span class=\"option\">\n"
        . "\t<label for=\"answer$myfname\" class=\"answertext\">\n".$othertext."\n<input class=\"text other ".$kpclass."\" type=\"text\" name=\"$myfname\" id=\"answer$myfname\" title=\"".$clang->gT('Other').'" size="10"';
        $answer_main .= " onkeyup='$oth_checkconditionFunction(this.value, this.name, this.type); if($.trim(this.value)==\"\") { $(\"#answer$myfname2\").val(\"\"); $checkconditionFunction(\"\",\"$myfname2\",\"text\"); }'";
        if (isset($_SESSION[$myfname]) && $_SESSION[$myfname])
        {
            $dispVal = $_SESSION[$myfname];
            if ($qidattributes['other_numbers_only']==1)
            {
                $dispVal = str_replace('.',$sSeperator,$dispVal);
            }
            $answer_main .= ' value="'.htmlspecialchars($dispVal,ENT_QUOTES).'"';
        }
        $fn++;
        $answer_main .= "  $callmaxanswscriptother />\n\t</label>\n</span>\n"
        . "<span class=\"comment\">\n\t<label for=\"answer$myfname2\" class=\"answer-comment\">\n"
        . '<input class="text '.$kpclass.'" type="text" size="40" name="'.$myfname2.'" id="answer'.$myfname2.'"'
        . " onkeyup='$checkconditionFunction(this.value,this.name,this.type);'"
        . ' title="'.$clang->gT('Make a comment on your choice here:').'" value="';

        if (isset($_SESSION[$myfname2])) {$answer_main .= htmlspecialchars($_SESSION[$myfname2],ENT_QUOTES);}
        $answer_main .= "\"/>\n";

        $answer_main .= "\t</label>\n</span>\n\t</li>\n";

        $inputnames[]=$myfname;
        $inputnames[]=$myfname2;
    }
    $answer .= "<ul>\n".$answer_main."</ul>\n";


    return array($answer, $inputnames);
}



// ---------------------------------------------------------------
function do_file_upload($ia)
{
    global $rooturl,$clang, $js_header_includes, $thissurvey, $surveyid;

    $checkconditionFunction = "checkconditions";

   	$qidattributes=getQuestionAttributes($ia[0]);

    // Fetch question attributes
    $_SESSION['fieldname'] = $ia[1];

    // Basic uploader
  /*  $basic  = '<br /><br /><table border="0" cellpadding="10" cellspacing="10" align="center">'
                    .'<tr>';
    if ($_SESSION['show_title']) { $basic .= '<th align="center"><b>Title</b></th><th>&nbsp;&nbsp;</th>'; }
    if ($_SESSION['show_comment']) { $basic .= '<th align="center"><b>Comment</b></th><th>&nbsp;&nbsp;</th>'; }
    $basic .=           '<th align="center"><b>Select file</b></th>'
                    .'</tr>'
                    .'<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>'
                    .'<tbody>';

    for ($i = 1; $i <= $_SESSION['maxfiles']; $i++) {
         $basic .= '<tr>'
                        .'<td>';
         if ($_SESSION['show_title'])
             $basic .=      '<input class="basic_'.$ia[1].'" type="text" name="'.$ia[1].'_title_'.$i
                            .'" id="'.$ia[1].'_title_'.$i.'" value="'.$_SESSION[$ia[1]]
                            .'" maxlength="100" />'
                        .'</td>'
                        .'<td>&nbsp;&nbsp;</td>';
         if ($_SESSION['show_comment'])
             $basic .=  '<td>'
                            .'<input class="basic_'.$ia[1].'" type="/extarea" name="'.$ia[1].'_comment_'.$i
                            .'" id="'.$ia[1].'_comment_'.$i.'" value="'.$_SESSION[$ia[1]]
                            .'" maxlength="100" />'
                        .'</td>'
                        .'<td>&nbsp;&nbsp;</td>';

         $basic .=      '<td>'
                            .' <input class="basic_'.$ia[1].'" '
                            .'type="file" name="'.$ia[1].'_file_'.$i.'" id="'.$ia[1].'_'.$i.'" alt="'
                            .$clang->gT("Answer").'" ></input></td>'
                        .'</tr>'
                        .'<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
    }

    $basic .= '</tbody></table>';
    $basic .= '<br /><br /><a href="#" onclick="hideBasic()">Hide Simple Uploader</a>';
    */
    $currentdir = getcwd();
    $bIsPreview = (substr($currentdir,strlen(dirname($currentdir))+1)=="admin");
    $scriptloc = $rooturl.'/uploader.php';

    if ($bIsPreview)
    {
        $_SESSION['preview'] = 1;
        $questgrppreview = 1;   // Preview is launched from Question or group level

    }
    else if ($thissurvey['active'] != "Y")
        {
            $_SESSION['preview'] = 1;
            $questgrppreview = 0;
        }
        else
        {
            $_SESSION['preview'] = 0;
            $questgrppreview = 0;
    }

    $uploadbutton = "<h2><a id='upload_".$ia[1]."' class='upload' ";
    $uploadbutton .= " href='#' onclick='javascript:upload_$ia[1]();'";
    $uploadbutton .=">" .$clang->gT('Upload files'). "</a></h2><br /><br />";

    $answer = "<script type='text/javascript'>
        function upload_$ia[1]() {
            var uploadurl = '{$scriptloc}?sid={$surveyid}&amp;fieldname={$ia[1]}&amp;qid={$ia[0]}';
            uploadurl += '&amp;preview={$questgrppreview}&amp;show_title={$qidattributes['show_title']}';
            uploadurl += '&amp;show_comment={$qidattributes['show_comment']}&amp;pos=".($bIsPreview?1:0)."';
            uploadurl += '&amp;minfiles=' + LEMval('{$qidattributes['min_num_of_files']}');
            uploadurl += '&amp;maxfiles=' + LEMval('{$qidattributes['max_num_of_files']}');
            $('#upload_$ia[1]').attr('href',uploadurl);
        }
        var translt = {
             title: '" . $clang->gT('Upload your files','js') . "',
             returnTxt: '" . $clang->gT('Return to survey','js') . "',
             headTitle: '" . $clang->gT('Title','js') . "',
             headComment: '" . $clang->gT('Comment','js') . "',
             headFileName: '" . $clang->gT('File name','js') . "'
            };
    </script>\n";
    /*if ($pos)
        $answer .= "<script type='text/javascript' src='{$rooturl}/scripts/modaldialog.js'></script>";
    else
        $answer .= "<script type='text/javascript' src='{$rooturl}/scripts/modaldialog.js'></script>";*/
    $js_header_includes[]= '/scripts/modaldialog.js';

    // Modal dialog
    $answer .= $uploadbutton;

    $answer .= "<input type='hidden' id='".$ia[1]."' name='".$ia[1]."' value='".$_SESSION[$ia[1]]."' />";
    $answer .= "<input type='hidden' id='".$ia[1]."_filecount' name='".$ia[1]."_filecount' value=";

    if (array_key_exists($ia[1]."_filecount", $_SESSION))
    {
        $tempval = $_SESSION[$ia[1]."_filecount"];
        if (is_numeric($tempval))
        {
            $answer .= $tempval . " />";
        }
        else
        {
            $answer .= "0 />";
        }
    }
    else {
        $answer .= "0 />";
    }

    $answer .= "<div id='".$ia[1]."_uploadedfiles'></div>";

    $answer .= '<script type="text/javascript">
                    var surveyid = '.$surveyid.';
                    var rooturl = "'.$rooturl.'";
                    $(document).ready(function(){
                        var fieldname = "'.$ia[1].'";
                        var filecount = $("#"+fieldname+"_filecount").val();
                        var json = $("#"+fieldname).val();
                        var show_title = "'.$qidattributes["show_title"].'";
                        var show_comment = "'.$qidattributes["show_comment"].'";
                        var pos = "'.($bIsPreview ? 1 : 0).'";
                        displayUploadedFiles(json, filecount, fieldname, show_title, show_comment, pos);
                    });
                </script>';

    $answer .= '<script type="text/javascript">
                    $(".basic_'.$ia[1].'").change(function() {
                        var i;
                        var jsonstring = "[";

                        for (i = 1, filecount = 0; i <= LEMval("'.$qidattributes['max_num_of_files'].'"); i++)
                        {
                            if ($("#'.$ia[1].'_"+i).val() == "")
                                continue;

                            filecount++;
                            if (i != 1)
                                jsonstring += ", ";

                            if ($("#answer'.$ia[1].'_"+i).val() != "")
                                jsonstring += "{ ';

    if ($qidattributes['show_title'])
        $answer .= '\"title\":\""+$("#'.$ia[1].'_title_"+i).val()+"\",';
    else
        $answer .= '\"title\":\"\",';

    if ($qidattributes['show_comment'])
        $answer .= '\"comment\":\""+$("#'.$ia[1].'_comment_"+i).val()+"\",';
    else
        $answer .= '\"comment\":\"\",';

    $answer .= '\"size\":\"\",\"name\":\"\",\"ext\":\"\"}";
                        }
                        jsonstring += "]";

                        $("#'.$ia[1].'").val(jsonstring);
                        $("#'.$ia[1].'_filecount").val(filecount);
                    });
                </script>';

    $inputnames[] = $ia[1];
    $inputnames[] = $ia[1]."_filecount";
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_multipleshorttext($ia)
{
    global $dbprefix, $clang, $thissurvey;

    $answer='';
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    if ($qidattributes['numbers_only']==1)
    {
        $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
        $sSeperator = $sSeperator['seperator'];
        $checkconditionFunction = "fixnum_checkconditions";
    }
    else
    {
        $checkconditionFunction = "checkconditions";
    }
    if (intval(trim($qidattributes['maximum_chars']))>0)
    {
        // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
        $maximum_chars= intval(trim($qidattributes['maximum_chars']));
        $maxlength= "maxlength='{$maximum_chars}' ";
    }
    else
    {
        $maxlength= "";
    }
    if (trim($qidattributes['text_input_width'])!='')
    {
        $tiwidth=$qidattributes['text_input_width'];
    }
    else
    {
        $tiwidth=20;
    }

    if (trim($qidattributes['prefix'])!='') {
        $prefix=$qidattributes['prefix'];
    }
    else
    {
        $prefix = '';
    }

    if (trim($qidattributes['suffix'])!='') {
        $suffix=$qidattributes['suffix'];
    }
    else
    {
        $suffix = '';
    }

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "text-keypad";
    }
    else
    {
        $kpclass = "";
    }

    if ($qidattributes['random_order']==1) {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
    }
    else
    {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
    }

    $ansresult = db_execute_assoc($ansquery);    //Checked
    $anscount = $ansresult->RecordCount()*2;
    //$answer .= "\t<input type='hidden' name='MULTI$ia[1]' value='$anscount'>\n";
    $fn = 1;

    $answer_main = '';

    $label_width = 0;

    if ($anscount==0)
    {
        $inputnames=array();
        $answer_main .= '	<li>'.$clang->gT('Error: This question has no answers.')."</li>\n";
    }
    else
    {
        if (trim($qidattributes['display_rows'])!='')
        {
            //question attribute "display_rows" is set -> we need a textarea to be able to show several rows
            $drows=$qidattributes['display_rows'];

            while ($ansrow = $ansresult->FetchRow())
            {
                $myfname = $ia[1].$ansrow['title'];
                if ($ansrow['question'] == "")
                {
                    $ansrow['question'] = "&nbsp;";
                }

		$quexs_answer = false;

		if (strncasecmp($ansrow['question'],"{SAMPLEUPDATE:",14) == 0) //queXS Addition
		{
			$ansrow['question'] = substr($ansrow['question'],14,-1); //remove token text
			include_once('quexs.php');
			$quexs_operator_id = get_operator_id();
			$quexs_case_id = get_case_id($quexs_operator_id);
			if ($quexs_case_id)
			{
				$quexs_answer = get_sample_variable($ansrow['question'],$quexs_case_id);
				$tiwidth = strlen($quexs_answer) + 5;
				$maxsize = $tiwidth + 255;
			}
		}


                list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, '', $myfname, "li");

                $answer_main .= "\t$htmltbody2\n"
                . "<label for=\"answer$myfname\">{$ansrow['question']}</label>\n"
                . "\t<span>\n".$prefix."\n".'
				<textarea class="textarea '.$kpclass.'" name="'.$myfname.'" id="answer'.$myfname.'"
				rows="'.$drows.'" cols="'.$tiwidth.'" '.$maxlength.' onkeyup="'.$checkconditionFunction.'(this.value, this.name, this.type);" >';

                if($label_width < strlen(trim(strip_tags($ansrow['question']))))
                {
                    $label_width = strlen(trim(strip_tags($ansrow['question'])));
                }

		if ($quexs_answer !== false)
		{
			$answer_main .= $quexs_answer;
		}	
                else if (isset($_SESSION[$myfname]))
                {
                    $dispVal = $_SESSION[$myfname];
                    if ($qidattributes['numbers_only']==1)
                    {
                        $dispVal = str_replace('.',$sSeperator,$dispVal);
                    }
                    $answer_main .= htmlspecialchars(htmlspecialchars($dispVal));
                }

                $answer_main .= "</textarea>\n".$suffix."\n\t</span>\n"
                . "\t</li>\n";

                $fn++;
                $inputnames[]=$myfname;
            }

        }
        else
        {
            while ($ansrow = $ansresult->FetchRow())
            {
                $myfname = $ia[1].$ansrow['title'];
                if ($ansrow['question'] == "") {$ansrow['question'] = "&nbsp;";}

		$quexs_answer = false;

		if (strncasecmp($ansrow['question'],"{SAMPLEUPDATE:",14) == 0) //queXS Addition
		{
			$ansrow['question'] = substr($ansrow['question'],14,-1); //remove token text
			include_once('quexs.php');
			$quexs_operator_id = get_operator_id();
			$quexs_case_id = get_case_id($quexs_operator_id);
			if ($quexs_case_id)
			{
				$quexs_answer = get_sample_variable($ansrow['question'],$quexs_case_id);
				$tiwidth = strlen($quexs_answer) + 5;
				$maxsize = $tiwidth + 255;
			}
		}


                list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, '', $myfname, "li");

                // color code missing mandatory questions red
                if ($ia[6]=='Y' && (($_SESSION['step'] == $_SESSION['prevstep']) || ($_SESSION['maxstep'] > $_SESSION['step'])) && $_SESSION[$myfname] == '') {
                    $ansrow['question'] = "<span class='errormandatory'>{$ansrow['question']}</span>";
                }

                $answer_main .= "\t$htmltbody2\n"
                . "<label for=\"answer$myfname\">{$ansrow['question']}</label>\n"
                . "\t<span>\n".$prefix."\n".'<input class="text '.$kpclass.'" type="text" size="'.$tiwidth.'" name="'.$myfname.'" id="answer'.$myfname.'" value="';

                if($label_width < strlen(trim(strip_tags($ansrow['question']))))
                {
                    $label_width = strlen(trim(strip_tags($ansrow['question'])));
                }

		if ($quexs_answer !== false)
		{
			$answer_main .= $quexs_answer;
		}
                else if (isset($_SESSION[$myfname]))
                {
                    $dispVal = $_SESSION[$myfname];
                    if ($qidattributes['numbers_only']==1)
                    {
                        $dispVal = str_replace('.',$sSeperator,$dispVal);
                    }
                    $answer_main .= htmlspecialchars($dispVal,ENT_QUOTES,'UTF-8');
                }

                $answer_main .= '" onkeyup="'.$checkconditionFunction.'(this.value, this.name, this.type);" '.$maxlength.' />'."\n".$suffix."\n\t</span>\n"
                . "\t</li>\n";

                $fn++;
                $inputnames[]=$myfname;
            }

        }
    }

    $answer = "<ul>\n".$answer_main."</ul>\n";

    return array($answer, $inputnames);
}


// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_multiplenumeric($ia)
{
    global $dbprefix, $clang, $js_header_includes, $css_header_includes, $thissurvey;

    $checkconditionFunction = "fixnum_checkconditions";
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    $answer='';
    $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
    $sSeperator = $sSeperator['seperator'];
    $numbersonly = '';  // 'onkeypress="inputField = event.srcElement ? event.srcElement : event.target || event.currentTarget; if (inputField.value.indexOf(\''.$sSeperator.'\')>0 && String.fromCharCode(getkey(event))==\''.$sSeperator.'\') return false; return goodchars(event,\'-0123456789'.$sSeperator.'\')"';
    if (intval(trim($qidattributes['maximum_chars']))>0)
    {
        // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
        $maximum_chars= intval(trim($qidattributes['maximum_chars']));
        $maxlength= "maxlength='{$maximum_chars}' ";
    }
    else
    {
        $maxlength= "maxlength='25'";
    }

    if (trim($qidattributes['prefix'])!='') {
        $prefix=$qidattributes['prefix'];
    }
    else
    {
        $prefix = '';
    }

    if (trim($qidattributes['suffix'])!='') {
        $suffix=$qidattributes['suffix'];
    }
    else
    {
        $suffix = '';
    }

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "num-keypad";
    }
    else
    {
        $kpclass = "";
    }

    if(!empty($numbersonlyonblur))
    {
        $numbersonly .= ' onblur="'.implode(';', $numbersonlyonblur).'"';
        $numbersonly_slider = implode(';', $numbersonlyonblur);
    }
    else
    {
        $numbersonly_slider = '';
    }

    if (trim($qidattributes['text_input_width'])!='')
    {
        $tiwidth=$qidattributes['text_input_width'];
    }
    else
    {
        $tiwidth=10;
    }
    if ($qidattributes['slider_layout']==1)
    {
        $slider_layout=true;
        $css_header_includes[]= '/scripts/jquery/css/start/jquery-ui.css';


        if (trim($qidattributes['slider_accuracy'])!='')
        {
            //$slider_divisor = 1 / $slider_accuracy['value'];
            $decimnumber = strlen($qidattributes['slider_accuracy']) - strpos($qidattributes['slider_accuracy'],'.') -1;
            $slider_divisor = pow(10,$decimnumber);
            $slider_stepping = $qidattributes['slider_accuracy'] * $slider_divisor;
            //	error_log('acc='.$slider_accuracy['value']." div=$slider_divisor stepping=$slider_stepping");
        }
        else
        {
            $slider_divisor = 1;
            $slider_stepping = 1;
        }

        if (trim($qidattributes['slider_min'])!='')
        {
            $slider_mintext = $qidattributes['slider_min'];
            $slider_min = $qidattributes['slider_min'] * $slider_divisor;
        }
        else
        {
            $slider_mintext = 0;
            $slider_min = 0;
        }
        if (trim($qidattributes['slider_max'])!='')
        {
            $slider_maxtext = $qidattributes['slider_max'];
            $slider_max = $qidattributes['slider_max'] * $slider_divisor;
        }
        else
        {
            $slider_maxtext = "100";
            $slider_max = 100 * $slider_divisor;
        }
        if (trim($qidattributes['slider_default'])!='')
        {
            $slider_default = $qidattributes['slider_default'];
        }
        else
        {
            $slider_default = '';
        }
        if ($slider_default == '' && $qidattributes['slider_middlestart']==1)
        {
            $slider_middlestart = intval(($slider_max + $slider_min)/2);
        }
        else
        {
            $slider_middlestart = '';
        }

        if (trim($qidattributes['slider_separator'])!='')
        {
            $slider_separator = $qidattributes['slider_separator'];
        }
        else
        {
            $slider_separator = '';
        }
    }
    else
    {
        $slider_layout = false;
    }
    $hidetip=$qidattributes['hide_tip'];
    if ($slider_layout === true) // auto hide tip when using sliders
    {
        $hidetip=1;
    }

    if ($qidattributes['random_order']==1)
    {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
    }
    else
    {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0]  AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
    }

    $ansresult = db_execute_assoc($ansquery);	//Checked
    $anscount = $ansresult->RecordCount()*2;
    //$answer .= "\t<input type='hidden' name='MULTI$ia[1]' value='$anscount'>\n";
    $fn = 1;

    $answer_main = '';

    if ($anscount==0)
    {
        $inputnames=array();
        $answer_main .= '	<li>'.$clang->gT('Error: This question has no answers.')."</li>\n";
    }
    else
    {
        $label_width = 0;
        while ($ansrow = $ansresult->FetchRow())
        {
            $myfname = $ia[1].$ansrow['title'];
            if ($ansrow['question'] == "") {$ansrow['question'] = "&nbsp;";}
            if ($slider_layout === false || $slider_separator == '')
            {
                $theanswer = $ansrow['question'];
                $sliderleft='';
                $sliderright='';
            }
            else
            {
                $answer_and_slider_array=explode($slider_separator,$ansrow['question']);
                if (isset($answer_and_slider_array[0]))
                $theanswer=$answer_and_slider_array[0];
                else
                $theanswer="";
                if (isset($answer_and_slider_array[1]))
                $sliderleft=$answer_and_slider_array[1];
                else
                $sliderleft="";
                if (isset($answer_and_slider_array[2]))
                $sliderright=$answer_and_slider_array[2];
                else
                $sliderright="";

                $sliderleft="<div class=\"slider_lefttext\">$sliderleft</div>";
                $sliderright="<div class=\"slider_righttext\">$sliderright</div>";
            }

            // color code missing mandatory questions red
            if ($ia[6]=='Y' && (($_SESSION['step'] == $_SESSION['prevstep']) || ($_SESSION['maxstep'] > $_SESSION['step'])) && $_SESSION[$myfname] == '') {
                $theanswer = "<span class='errormandatory'>{$theanswer}</span>";
            }

            list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, '', $myfname, "li");
            $answer_main .= "\t$htmltbody2\n";

            if ($slider_layout === false)
            {
                $answer_main .= "<label for=\"answer$myfname\">{$theanswer}</label>\n";

            }
            else
            {
                $answer_main .= "<label for=\"answer$myfname\" class=\"slider-label\">{$theanswer}</label>\n";

            }

            if($label_width < strlen(trim(strip_tags($ansrow['question']))))
            {
                $label_width = strlen(trim(strip_tags($ansrow['question'])));
            }

            if ($slider_layout === false)
            {
                $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
                $sSeperator = $sSeperator['seperator'];


                $answer_main .= "<span class=\"input\">\n\t".$prefix."\n\t<input class=\"text $kpclass\" type=\"text\" size=\"".$tiwidth.'" name="'.$myfname.'" id="answer'.$myfname.'" value="';
                if (isset($_SESSION[$myfname]))
                {
                    $dispVal = str_replace('.',$sSeperator,$_SESSION[$myfname]);
                    $answer_main .= $dispVal;
                }

                $answer_main .= '" onkeyup="'.$checkconditionFunction.'(this.value, this.name, this.type);" '." {$numbersonly} {$maxlength} />\n\t".$suffix."\n</span>\n\t</li>\n";

            }
            else
            {

                if ($qidattributes['slider_showminmax']==1)
                {
                    //$slider_showmin=$slider_min;
                    $slider_showmin= "\t<div id=\"slider-left-$myfname\" class=\"slider_showmin\">$slider_mintext</div>\n";
                    $slider_showmax= "\t<div id=\"slider-right-$myfname\" class=\"slider_showmax\">$slider_maxtext</div>\n";
                }
                else
                {
                    $slider_showmin='';
                    $slider_showmax='';
                }

                $js_header_includes[] = '/scripts/jquery/lime-slider.js';

                if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] != '')
                {
                    $slider_startvalue = $_SESSION[$myfname] * $slider_divisor;
                    $displaycallout_atstart=1;
                }
                elseif ($slider_default != "")
                {
                    $slider_startvalue = $slider_default * $slider_divisor;
                    $displaycallout_atstart=1;
                }
                elseif ($slider_middlestart != '')
                {
                    $slider_startvalue = $slider_middlestart;
                    $displaycallout_atstart=0;
                }
                else
                {
                    $slider_startvalue = 'NULL';
                    $displaycallout_atstart=0;
                }
                $answer_main .= "$sliderleft<div id='container-$myfname' class='multinum-slider'>\n"
                . "\t<input type=\"text\" id=\"slider-modifiedstate-$myfname\" value=\"$displaycallout_atstart\" style=\"display: none;\" />\n"
                . "\t<input type=\"text\" id=\"slider-param-min-$myfname\" value=\"$slider_min\" style=\"display: none;\" />\n"
                . "\t<input type=\"text\" id=\"slider-param-max-$myfname\" value=\"$slider_max\" style=\"display: none;\" />\n"
                . "\t<input type=\"text\" id=\"slider-param-stepping-$myfname\" value=\"$slider_stepping\" style=\"display: none;\" />\n"
                . "\t<input type=\"text\" id=\"slider-param-divisor-$myfname\" value=\"$slider_divisor\" style=\"display: none;\" />\n"
                . "\t<input type=\"text\" id=\"slider-param-startvalue-$myfname\" value='$slider_startvalue' style=\"display: none;\" />\n"
                . "\t<input type=\"text\" id=\"slider-onchange-js-$myfname\" value=\"$numbersonly_slider\" style=\"display: none;\" />\n"
                . "\t<input type=\"text\" id=\"slider-prefix-$myfname\" value=\"$prefix\" style=\"display: none;\" />\n"
                . "\t<input type=\"text\" id=\"slider-suffix-$myfname\" value=\"$suffix\" style=\"display: none;\" />\n"
                . "<div id=\"slider-$myfname\" class=\"ui-slider-1\">\n"
                .  $slider_showmin
                . "<div class=\"slider_callout\" id=\"slider-callout-$myfname\"></div>\n"
                . "<div class=\"ui-slider-handle\" id=\"slider-handle-$myfname\"></div>\n";
                $answer_main .= "<input class=\"text\" type=\"text\" name=\"$myfname\" id=\"answer$myfname\" value=\"";
                if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] != '')
                {
                    $answer_main .= $_SESSION[$myfname];
                }
                elseif ($slider_default != "")
                {
                    $answer_main .= $slider_default;
                }
                $answer_main .= "\"/>\n";
                $answer_main .=  $slider_showmax
                . "\t</div>"
                . "</div>$sliderright\n";
                $answer_main .=   "\t</li>\n";
            }

            //			$answer .= "\t</tr>\n";

            $fn++;
            $inputnames[]=$myfname;
        }
        $question_tip = '';
        if($hidetip == 0)
        {
            $question_tip .= '<p class="tip">'.$clang->gT('Only numbers may be entered in these fields')."</p>\n";
        }
        if (trim($qidattributes['equals_num_value'])!=''
            || trim($qidattributes['min_num_value'])!=''
            || trim($qidattributes['max_num_value'])!=''
            )
        {
            $qinfo = LimeExpressionManager::GetQuestionStatus($ia[0]);
            if (trim($qidattributes['equals_num_value'])!='')
            {
                $answer_main .= "\t<li class='multiplenumerichelp'>\n"
                    . "<label for=\"remainingvalue_{$ia[0]}\">".$clang->gT('Remaining: ')."</label>\n"
                    . "<span id=\"remainingvalue_{$ia[0]}\" class=\"dynamic_remaining\">$prefix\n"
                    . "{" . $qinfo['sumRemainingEqn'] . "}\n"
                    . "$suffix</span>\n"
                    . "\t</li>\n";
            }
            $answer_main .= "\t<li class='multiplenumerichelp'>\n"
                . "<label for=\"totalvalue_{$ia[0]}\">".$clang->gT('Total: ')."</label>\n"
                . "<span id=\"totalvalue_{$ia[0]}\" class=\"dynamic_sum\">$prefix\n"
                . "{" . $qinfo['sumEqn'] . "}\n"
                . "$suffix</span>\n"
                . "\t</li>\n";

        }

        $answer .= $question_tip."<ul>\n".$answer_main."</ul>\n";
    }
    //just added these here so its easy to change in one place
    $errorClass = 'tip problem';
    $goodClass = 'tip good';
    /* ==================================
     Style to be applied to all templates.
     .numeric-multi p.tip.error
     {
     color: #f00;
     }
     .numeric-multi p.tip.good
     {
     color: #0f0;
     }
     */
    $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
    $sSeperator = $sSeperator['seperator'];

    return array($answer, $inputnames);
}





// ---------------------------------------------------------------
function do_numerical($ia)
{
    global $clang, $thissurvey;

    $checkconditionFunction = "fixnum_checkconditions";
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    if (trim($qidattributes['prefix'])!='') {
        $prefix=$qidattributes['prefix'];
    }
    else
    {
        $prefix = '';
    }
    if (trim($qidattributes['suffix'])!='') {
        $suffix=$qidattributes['suffix'];
    }
    else
    {
        $suffix = '';
    }
    if (intval(trim($qidattributes['maximum_chars']))>0 && intval(trim($qidattributes['maximum_chars']))<20) // Limt to 20 chars for numeric
    {
        // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
        $maximum_chars= intval(trim($qidattributes['maximum_chars']));
        $maxlength= "maxlength='{$maximum_chars}' ";
    }
    else
    {
        $maxlength= "maxlength='20' ";
    }
    if (trim($qidattributes['text_input_width'])!='')
    {
        $tiwidth=$qidattributes['text_input_width'];
    }
    else
    {
        $tiwidth=10;
    }

    if (trim($qidattributes['num_value_int_only'])==1)
    {
        $acomma="";
        $intonly=1;
    }
    else
    {
        $acomma=getRadixPointData($thissurvey['surveyls_numberformat']);
        $acomma = $acomma['seperator'];
        $intonly=0;

    }
    $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
    $sSeperator = $sSeperator['seperator'];
    $dispVal = str_replace('.',$sSeperator,$_SESSION[$ia[1]]);

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "num-keypad";
    }
    else
    {
        $kpclass = "";
    }
    $answer = "<p class=\"question\"><label for='answer{$ia[1]}' class='hide label'>{$clang->gT('Answer')}</label>\n$prefix\t<input class=\"text $kpclass\" type=\"text\" size=\"$tiwidth\" name=\"$ia[1]\" "
    . "id=\"answer{$ia[1]}\" value=\"{$dispVal}\" title=\"".$clang->gT('Only numbers may be entered in this field')."\" onkeyup='$checkconditionFunction(this.value, this.name, this.type,null, $intonly)' "
    . " {$maxlength} />\n\t{$suffix}\n</p>\n";
    if ($qidattributes['hide_tip']==0)
    {
        $answer .= "<p class=\"tip\">".$clang->gT('Only numbers may be entered in this field')."</p>\n";
    }

    $inputnames[]=$ia[1];
    $mandatory=null;
    return array($answer, $inputnames, $mandatory);
}




// ---------------------------------------------------------------
function do_shortfreetext($ia)
{
    global $clang, $js_header_includes, $thissurvey,$googleMapsAPIKey;

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    if ($qidattributes['numbers_only']==1)
    {
        $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
        $sSeperator = $sSeperator['seperator'];
        $checkconditionFunction = "fixnum_checkconditions";
    }
    else
    {
        $checkconditionFunction = "checkconditions";
    }
    if (intval(trim($qidattributes['maximum_chars']))>0)
    {
        // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
        $maximum_chars= intval(trim($qidattributes['maximum_chars']));
        $maxlength= "maxlength='{$maximum_chars}' ";
    }
    else
    {
        $maxlength= "";
    }
    if (trim($qidattributes['text_input_width'])!='')
    {
        $tiwidth=$qidattributes['text_input_width'];
    }
    else
    {
        $tiwidth=50;
    }
    if (trim($qidattributes['prefix'])!='') {
        $prefix=$qidattributes['prefix'];
    }
    else
    {
        $prefix = '';
    }
    if (trim($qidattributes['suffix'])!='') {
        $suffix=$qidattributes['suffix'];
    }
    else
    {
        $suffix = '';
    }
    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "text-keypad";
    }
    else
    {
        $kpclass = "";
    }
    if (trim($qidattributes['display_rows'])!='')
    {
        //question attribute "display_rows" is set -> we need a textarea to be able to show several rows
        $drows=$qidattributes['display_rows'];

        //if a textarea should be displayed we make it equal width to the long text question
        //this looks nicer and more continuous
        if($tiwidth == 50)
        {
            $tiwidth=40;
        }

        $answer = '<label for="answer'.$ia[1].'" class="hide label">'.$clang->gT('Answer').'</label><textarea class="textarea '.$kpclass.'" name="'.$ia[1].'" id="answer'.$ia[1].'"'
        .'rows="'.$drows.'" cols="'.$tiwidth.'" '.$maxlength.' onkeyup="'.$checkconditionFunction.'(this.value, this.name, this.type);">';

        if ($_SESSION[$ia[1]]) {
            $dispVal = str_replace("\\", "", $_SESSION[$ia[1]]);
            if ($qidattributes['numbers_only']==1)
            {
                $dispVal = str_replace('.',$sSeperator,$dispVal);
            }
            $answer .= htmlspecialchars(htmlspecialchars($dispVal));
        }

        $answer .= "</textarea>\n";
    }
    elseif((int)($qidattributes['location_mapservice'])!=0){

        $mapservice = $qidattributes['location_mapservice'];
        $currentLocation = $_SESSION[$ia[1]];
        $currentLatLong = null;

        $floatLat = 0;
        $floatLng = 0;

        // Get the latitude/longtitude for the point that needs to be displayed by default
        if (strlen($currentLocation) > 2){
            $currentLatLong = explode(';',$currentLocation);
            $currentLatLong = array($currentLatLong[0],$currentLatLong[1]);
        }
        else{
            if ((int)($qidattributes['location_nodefaultfromip'])==0)
                $currentLatLong = getLatLongFromIp(getIPAddress());
            if (!isset($currentLatLong) || $currentLatLong==false){
                $floatLat = 0;
                $floatLng = 0;
                $LatLong = explode(" ",trim($qidattributes['location_defaultcoordinates']));

                if (isset($LatLong[0]) && isset($LatLong[1])){
                    $floatLat = $LatLong[0];
                    $floatLng = $LatLong[1];
                }

                $currentLatLong = array($floatLat,$floatLng);
            }
        }
        // 2 - city; 3 - state; 4 - country; 5 - postal
        $strBuild = "";
        if ($qidattributes['location_city'])
            $strBuild .= "2";
        if ($qidattributes['location_state'])
            $strBuild .= "3";
        if ($qidattributes['location_country'])
            $strBuild .= "4";
        if ($qidattributes['location_postal'])
            $strBuild .= "5";

        $currentLocation = $currentLatLong[0] . " " . $currentLatLong[1];
        $answer = "
        	<script type=\"text/javascript\">
        		zoom['$ia[1]'] = {$qidattributes['location_mapzoom']};
        	</script>
            <p class=\"question\">
            <input type=\"hidden\" name=\"$ia[1]\" id=\"answer$ia[1]\" value=\"{$_SESSION[$ia[1]]}\">

            <input class=\"text location ".$kpclass."\" type=\"text\" size=\"20\" name=\"$ia[1]_c\"
                id=\"answer$ia[1]_c\" value=\"$currentLocation\"
                onchange=\"$checkconditionFunction(this.value, this.name, this.type)\" />
            </p>

            <input type=\"hidden\" name=\"boycott_$ia[1]\" id=\"boycott_$ia[1]\"
                value = \"{$strBuild}\" >
            <input type=\"hidden\" name=\"mapservice_$ia[1]\" id=\"mapservice_$ia[1]\"
                class=\"mapservice\" value = \"{$qidattributes['location_mapservice']}\" >
            <div id=\"gmap_canvas_$ia[1]_c\" style=\"width: {$qidattributes['location_mapwidth']}px; height: {$qidattributes['location_mapheight']}px\"></div>";

        if ($qidattributes['location_mapservice']==1 && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off")
            $js_header_includes[] = "https://maps.googleapis.com/maps/api/js?sensor=false";
        else if ($qidattributes['location_mapservice']==1)
            $js_header_includes[] = "http://maps.googleapis.com/maps/api/js?sensor=false";
        elseif ($qidattributes['location_mapservice']==2)
            $js_header_includes[] = "http://www.openlayers.org/api/OpenLayers.js";

	    if (isset($qidattributes['hide_tip']) && $qidattributes['hide_tip']==0)
            {
                $answer .= "<br />\n<span class=\"questionhelp\">"
                . $clang->gT('Drag and drop the pin to the desired location. You may also right click on the map to move the pin.').'</span>';
                $question_text['help'] = $clang->gT('Drag and drop the pin to the desired location. You may also right click on the map to move the pin.');
            }


    }
    else
    {
        //no question attribute set, use common input text field
        $answer = "<p class=\"question\">\n<label for='answer{$ia[1]}' class='hide label'>{$clang->gT('Answer')}</label>\t$prefix\n\t<input class=\"text $kpclass\" type=\"text\" size=\"$tiwidth\" name=\"$ia[1]\" id=\"answer$ia[1]\"";

        $dispVal = $_SESSION[$ia[1]];
        if ($qidattributes['numbers_only']==1)
        {
            $dispVal = str_replace('.',$sSeperator,$dispVal);
        }
        $dispVal = htmlspecialchars($dispVal,ENT_QUOTES,'UTF-8');
        $answer .= " value=\"$dispVal\"";

        $answer .=" {$maxlength} onkeyup=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n\t$suffix\n</p>\n";
    }


    if (trim($qidattributes['time_limit'])!='')
    {
		$js_header_includes[] = '/scripts/coookies.js';
        $answer .= return_timer_script($qidattributes, $ia, "answer".$ia[1]);
    }

    $inputnames[]=$ia[1];
    return array($answer, $inputnames);

}

function getLatLongFromIp($ip){
    global $ipInfoDbAPIKey;
    $xml = simplexml_load_file("http://api.ipinfodb.com/v2/ip_query.php?key=$ipInfoDbAPIKey&ip=$ip&timezone=false");
    if ($xml->{'Status'} == "OK"){
        $lat = (float)$xml->{'Latitude'};
        $lng = (float)$xml->{'Longitude'};

        return(array($lat,$lng));
    }
    else
        return false;
}



// ---------------------------------------------------------------
function do_longfreetext($ia)
{
    global $clang, $js_header_includes, $thissurvey;

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "text-keypad";
    }
    else
    {
        $kpclass = "";
    }

    $checkconditionFunction = "checkconditions";

   	$qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    if (intval(trim($qidattributes['maximum_chars']))>0)
    {
        // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
        $maximum_chars= intval(trim($qidattributes['maximum_chars']));
        $maxlength= "maxlength='{$maximum_chars}' ";
    }
    else
    {
        $maxlength= "";
    }

    if (trim($qidattributes['display_rows'])!='')
    {
        $drows=$qidattributes['display_rows'];
    }
    else
    {
        $drows=5;
    }

    if (trim($qidattributes['text_input_width'])!='')
    {
        $tiwidth=$qidattributes['text_input_width'];
    }
    else
    {
        $tiwidth=40;
    }

    $answer = '<label for="answer'.$ia[1].'" class="hide label">'.$clang->gT('Answer').'</label><textarea class="textarea '.$kpclass.'" name="'.$ia[1].'" id="answer'.$ia[1].'" '
    .'rows="'.$drows.'" cols="'.$tiwidth.'" '.$maxlength.' onkeyup="'.$checkconditionFunction.'(this.value, this.name, this.type)" >';

    if ($_SESSION[$ia[1]]) {$answer .= htmlspecialchars(htmlspecialchars(str_replace("\\", "", $_SESSION[$ia[1]])));}

    $answer .= "</textarea>\n";

    if (trim($qidattributes['time_limit'])!='')
    {
		$js_header_includes[] = '/scripts/coookies.js';
        $answer .= return_timer_script($qidattributes, $ia, "answer".$ia[1]);
    }

    $inputnames[]=$ia[1];
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
function do_hugefreetext($ia)
{
    global $clang, $js_header_includes, $thissurvey;

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "text-keypad";
    }
    else
    {
        $kpclass = "";
    }

    $checkconditionFunction = "checkconditions";

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    if (intval(trim($qidattributes['maximum_chars']))>0)
    {
        // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
        $maximum_chars= intval(trim($qidattributes['maximum_chars']));
        $maxlength= "maxlength='{$maximum_chars}' ";
    }
    else
    {
        $maxlength= "";
    }

    if (trim($qidattributes['display_rows'])!='')
    {
        $drows=$qidattributes['display_rows'];
    }
    else
    {
        $drows=30;
    }

    if (trim($qidattributes['text_input_width'])!='')
    {
        $tiwidth=$qidattributes['text_input_width'];
    }
    else
    {
        $tiwidth=70;
    }

    $answer = '<label for="answer'.$ia[1].'" class="hide label">'.$clang->gT('Answer').'</label><textarea class="textarea '.$kpclass.'" name="'.$ia[1].'" id="answer'.$ia[1].'" '
    .'rows="'.$drows.'" cols="'.$tiwidth.'" '.$maxlength.' onkeyup="'.$checkconditionFunction.'(this.value, this.name, this.type)" >';

    if ($_SESSION[$ia[1]]) {$answer .= htmlspecialchars(htmlspecialchars(str_replace("\\", "", $_SESSION[$ia[1]])));}

    $answer .= "</textarea>\n";

    if (trim($qidattributes['time_limit']) != '')
    {
		$js_header_includes[] = '/scripts/coookies.js';
        $answer .= return_timer_script($qidattributes, $ia, "answer".$ia[1]);
    }

    $inputnames[]=$ia[1];
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
function do_yesno($ia)
{
    global $clang;

    $checkconditionFunction = "checkconditions";

    $answer = "<ul>\n"
    . "\t<li>\n<input class=\"radio\" type=\"radio\" name=\"{$ia[1]}\" id=\"answer{$ia[1]}Y\" value=\"Y\"";

    if ($_SESSION[$ia[1]] == 'Y')
    {
        $answer .= CHECKED;
    }
    $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)" . quexs_submit_on_click() . "\" />\n<label for=\"answer{$ia[1]}Y\" class=\"answertext\">\n\t".$clang->gT('Yes')."\n</label>\n\t</li>\n"
    . "\t<li>\n<input class=\"radio\" type=\"radio\" name=\"{$ia[1]}\" id=\"answer{$ia[1]}N\" value=\"N\"";

    if ($_SESSION[$ia[1]] == 'N')
    {
        $answer .= CHECKED;
    }
    $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)" . quexs_submit_on_click() . "\" />\n<label for=\"answer{$ia[1]}N\" class=\"answertext\" >\n\t".$clang->gT('No')."\n</label>\n\t</li>\n";

    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
    {
        $answer .= "\t<li>\n<input class=\"radio\" type=\"radio\" name=\"{$ia[1]}\" id=\"answer{$ia[1]}\" value=\"\"";
        if ($_SESSION[$ia[1]] == '')
        {
            $answer .= CHECKED;
        }
        $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)" . quexs_submit_on_click() . "\" />\n<label for=\"answer{$ia[1]}\" class=\"answertext\">\n\t".$clang->gT('No answer')."\n</label>\n\t</li>\n";
    }

    $answer .= "</ul>\n\n<input type=\"hidden\" name=\"java{$ia[1]}\" id=\"java{$ia[1]}\" value=\"{$_SESSION[$ia[1]]}\" />\n";
    $inputnames[]=$ia[1];
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
function do_gender($ia)
{
    global $clang;

    $checkconditionFunction = "checkconditions";

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    $answer = "<ul>\n"
    . "\t<li>\n"
    . '		<input class="radio" type="radio" name="'.$ia[1].'" id="answer'.$ia[1].'F" value="F"';
    if ($_SESSION[$ia[1]] == 'F')
    {
        $answer .= CHECKED;
    }
    $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)" . quexs_submit_on_click()  ."\" />\n"
    . '		<label for="answer'.$ia[1].'F" class="answertext">'.$clang->gT('Female')."</label>\n\t</li>\n";

    $answer .= "\t<li>\n<input class=\"radio\" type=\"radio\" name=\"$ia[1]\" id=\"answer".$ia[1].'M" value="M"';

    if ($_SESSION[$ia[1]] == 'M')
    {
        $answer .= CHECKED;
    }
    $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)" . quexs_submit_on_click() . "\" />\n<label for=\"answer$ia[1]M\" class=\"answertext\">".$clang->gT('Male')."</label>\n\t</li>\n";

    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
    {
        $answer .= "\t<li>\n<input class=\"radio\" type=\"radio\" name=\"$ia[1]\" id=\"answer".$ia[1].'" value=""';
        if ($_SESSION[$ia[1]] == '')
        {
            $answer .= CHECKED;
        }
        $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)" . quexs_submit_on_click() . "\" />\n<label for=\"answer$ia[1]\" class=\"answertext\">".$clang->gT('No answer')."</label>\n\t</li>\n";

    }
    $answer .= "</ul>\n\n<input type=\"hidden\" name=\"java$ia[1]\" id=\"java$ia[1]\" value=\"{$_SESSION[$ia[1]]}\" />\n";

    $inputnames[]=$ia[1];
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
/**
* DONE: well-formed valid HTML is appreciated
* Enter description here...
* @param $ia
* @return unknown_type
*/
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_array_5point($ia)
{
    global $dbprefix, $notanswered, $thissurvey, $clang;
    $inputnames=array();

    $checkconditionFunction = "checkconditions";

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    if (trim($qidattributes['answer_width'])!='')
    {
        $answerwidth=$qidattributes['answer_width'];
    }
    else
    {
        $answerwidth = 20;
    }
    $cellwidth  = 5; // number of columns

    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        ++$cellwidth; // add another column
    }
    $cellwidth = round((( 100 - $answerwidth ) / $cellwidth) , 1); // convert number of columns to percentage of table width

    $ansquery = "SELECT question FROM {$dbprefix}questions WHERE parent_qid=".$ia[0]." AND question like '%|%'";
    $ansresult = db_execute_assoc($ansquery);   //Checked

    if ($ansresult->RecordCount()>0) {$right_exists=true;$answerwidth=$answerwidth/2;} else {$right_exists=false;}
    // $right_exists is a flag to find out if there are any right hand answer parts. If there arent we can leave out the right td column


    if ($qidattributes['random_order']==1) {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
    }
    else
    {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
    }

    $ansresult = db_execute_assoc($ansquery);     //Checked
    $anscount = $ansresult->RecordCount();

    $fn = 1;
    $answer = "\n<table class=\"question\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - a five point Likert scale array\">\n\n"
    . "\t<colgroup class=\"col-responses\">\n"
    . "\t<col class=\"col-answers\" width=\"$answerwidth%\" />\n";
    $odd_even = '';

    for ($xc=1; $xc<=5; $xc++)
    {
        $odd_even = alternation($odd_even);
        $answer .= "<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
    }
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        $odd_even = alternation($odd_even);
        $answer .= "<col class=\"col-no-answer $odd_even\" width=\"$cellwidth%\" />\n";
    }
    $answer .= "\t</colgroup>\n\n"
    . "\t<thead>\n<tr class=\"array1\">\n"
    . "\t<th>&nbsp;</th>\n";
    for ($xc=1; $xc<=5; $xc++)
    {
        $answer .= "\t<th>$xc</th>\n";
    }
    if ($right_exists) {$answer .= "\t<td width='$answerwidth%'>&nbsp;</td>\n";}
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        $answer .= "\t<th>".$clang->gT('No answer')."</th>\n";
    }
    $answer .= "</tr></thead>\n";

    $answer_t_content = '';
    $trbc = '';
    $n=0;
    //return array($answer, $inputnames);
    while ($ansrow = $ansresult->FetchRow())
    {
        $myfname = $ia[1].$ansrow['title'];

        $answertext=dTexts__run($ansrow['question']);
        if (strpos($answertext,'|')) {$answertext=substr($answertext,0,strpos($answertext,'|'));}

        /* Check if this item has not been answered: the 'notanswered' variable must be an array,
         containing a list of unanswered questions, the current question must be in the array,
         and there must be no answer available for the item in this session. */
        if ($ia[6]=='Y' && (is_array($notanswered)) && (array_search($myfname, $notanswered) !== FALSE) && ($_SESSION[$myfname] == '') ) {
            $answertext = "<span class=\"errormandatory\">{$answertext}</span>";
        }

        $trbc = alternation($trbc , 'row');

        // Get array_filter stuff
        list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname);

        $answer_t_content .= $htmltbody2;

        $answer_t_content .= "<tr class=\"$trbc\">\n"
        . "\t<th class=\"answertext\" width=\"$answerwidth%\">\n$answertext\n"
        . $hiddenfield
        . "<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";
        if (isset($_SESSION[$myfname]))
        {
            $answer_t_content .= $_SESSION[$myfname];
        }
        $answer_t_content .= "\" />\n\t</th>\n";
        for ($i=1; $i<=5; $i++)
        {
            $answer_t_content .= "\t<td class=\"answer_cell_00$i\">\n<label for=\"answer$myfname-$i\">"
            ."\n\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-$i\" value=\"$i\" title=\"$i\"";
            if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $i)
            {
                $answer_t_content .= CHECKED;
            }
            $answer_t_content .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n</label>\n\t</td>\n";
        }

        $answertext2=dTexts__run($ansrow['question']);
        if (strpos($answertext2,'|'))
        {
            $answertext2=substr($answertext2,strpos($answertext2,'|')+1);
            $answer_t_content .= "\t<td class=\"answertextright\" style='text-align:left;' width=\"$answerwidth%\">$answertext2</td>\n";
        }
        elseif ($right_exists)
        {
            $answer_t_content .= "\t<td class=\"answertextright\" style='text-align:left;' width=\"$answerwidth%\">&nbsp;</td>\n";
        }


        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
        {
            $answer_t_content .= "\t<td>\n<label for=\"answer$myfname-\">"
            ."\n\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-\" value=\"\" title=\"".$clang->gT('No answer').'"';
            if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == '')
            {
                $answer_t_content .= CHECKED;
            }
            $answer_t_content .= " onclick='$checkconditionFunction(this.value, this.name, this.type)'  />\n</label>\n\t</td>\n";
        }

        $answer_t_content .= "</tr>\n\n\t</tbody>";
        $fn++;
        $inputnames[]=$myfname;
    }

    $answer .= $answer_t_content . "\t</table>\n";
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
/**
* DONE: well-formed valid HTML is appreciated
* Enter description here...
* @param $ia
* @return unknown_type
*/
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_array_10point($ia)
{
    global $dbprefix, $notanswered, $thissurvey, $clang;

    $checkconditionFunction = "checkconditions";

    $qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]."  AND language='".$_SESSION['s_lang']."'";
    $qresult = db_execute_assoc($qquery);      //Checked
    while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    if (trim($qidattributes['answer_width'])!='')
    {
        $answerwidth=$qidattributes['answer_width'];
    }
    else
    {
        $answerwidth = 20;
    }
    $cellwidth  = 10; // number of columns
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        ++$cellwidth; // add another column
    }
    $cellwidth = round((( 100 - $answerwidth ) / $cellwidth) , 1); // convert number of columns to percentage of table width

    if ($qidattributes['random_order']==1) {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
    }
    else
    {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
    }
    $ansresult = db_execute_assoc($ansquery);   //Checked
    $anscount = $ansresult->RecordCount();

    $fn = 1;
    $answer = "\n<table class=\"question\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - a ten point Likert scale array\" >\n\n"
    . "\t<colgroup class=\"col-responses\">\n"
    . "\t<col class=\"col-answers\" width=\"$answerwidth%\" />\n";

    $odd_even = '';
    for ($xc=1; $xc<=10; $xc++)
    {
        $odd_even = alternation($odd_even);
        $answer .= "<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
    }
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        $odd_even = alternation($odd_even);
        $answer .= "<col class=\"col-no-answer $odd_even\" width=\"$cellwidth$\" />\n";
    }
    $answer .= "\t</colgroup>\n\n"
    . "\t<thead>\n<tr class=\"array1\">\n"
    . "\t<th>&nbsp;</th>\n";
    for ($xc=1; $xc<=10; $xc++)
    {
        $answer .= "\t<th>$xc</th>\n";
    }
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        $answer .= "\t<th>".$clang->gT('No answer')."</th>\n";
    }
    $answer .= "</tr>\n</thead>";
    $answer_t_content = '';
    $trbc = '';
    while ($ansrow = $ansresult->FetchRow())
    {
        $myfname = $ia[1].$ansrow['title'];
        $answertext=dTexts__run($ansrow['question']);
        /* Check if this item has not been answered: the 'notanswered' variable must be an array,
         containing a list of unanswered questions, the current question must be in the array,
         and there must be no answer available for the item in this session. */
        if ($ia[6]=='Y' && (is_array($notanswered)) && (array_search($myfname, $notanswered) !== FALSE) && ($_SESSION[$myfname] == "") ) {
            $answertext = "<span class='errormandatory'>{$answertext}</span>";
        }
        $trbc = alternation($trbc , 'row');

        //Get array filter stuff
        list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname);

        $answer_t_content .= $htmltbody2;

        $answer_t_content .= "<tr class=\"$trbc\">\n"
        . "\t<th class=\"answertext\">\n$answertext\n"
        . $hiddenfield
        . "<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";
        if (isset($_SESSION[$myfname]))
        {
            $answer_t_content .= $_SESSION[$myfname];
        }
        $answer_t_content .= "\" />\n\t</th>\n";

        for ($i=1; $i<=10; $i++)
        {
            $answer_t_content .= "\t<td class=\"answer_cell_00$i\">\n<label for=\"answer$myfname-$i\">\n"
            ."\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-$i\" value=\"$i\" title=\"$i\"";
            if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $i)
            {
                $answer_t_content .= CHECKED;
            }
            $answer_t_content .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n</label>\n\t</td>\n";

        }
        if ($ia[6] != "Y" && SHOW_NO_ANSWER == 1)
        {
            $answer_t_content .= "\t<td>\n<label for=\"answer$myfname-\">\n"
            ."\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-\" value=\"\" title=\"".$clang->gT('No answer')."\"";
            if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == '')
            {
                $answer_t_content .= CHECKED;
            }
            $answer_t_content .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n</label>\n\t</td>\n";

        }
        $answer_t_content .= "</tr>\n</tbody>";
        $inputnames[]=$myfname;
        $fn++;
    }
    $answer .=  $answer_t_content . "\t\n</table>\n";
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_array_yesnouncertain($ia)
{
    global $dbprefix, $notanswered, $thissurvey, $clang;

    $checkconditionFunction = "checkconditions";

    $qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
    $qresult = db_execute_assoc($qquery);	//Checked
    while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    if (trim($qidattributes['answer_width'])!='')
    {
        $answerwidth=$qidattributes['answer_width'];
    }
    else
    {
        $answerwidth = 20;
    }
    $cellwidth  = 3; // number of columns
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        ++$cellwidth; // add another column
    }
    $cellwidth = round((( 100 - $answerwidth ) / $cellwidth) , 1); // convert number of columns to percentage of table width

    if ($qidattributes['random_order']==1) {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
    }
    else
    {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
    }
    $ansresult = db_execute_assoc($ansquery);	//Checked
    $anscount = $ansresult->RecordCount();
    $fn = 1;
    $answer = "\n<table class=\"question\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - a Yes/No/uncertain Likert scale array\">\n"
    . "\t<colgroup class=\"col-responses\">\n"
    . "\n\t<col class=\"col-answers\" width=\"$answerwidth%\" />\n";
    $odd_even = '';
    for ($xc=1; $xc<=3; $xc++)
    {
        $odd_even = alternation($odd_even);
        $answer .= "<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
    }
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        $odd_even = alternation($odd_even);
        $answer .= "<col class=\"col-no-answer $odd_even\" width=\"$cellwidth%\" />\n";
    }
    $answer .= "\t</colgroup>\n\n"
    . "\t<thead>\n<tr class=\"array1\">\n"
    . "\t<td>&nbsp;</td>\n"
    . "\t<th>".$clang->gT('Yes')."</th>\n"
    . "\t<th>".$clang->gT('Uncertain')."</th>\n"
    . "\t<th>".$clang->gT('No')."</th>\n";
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        $answer .= "\t<th>".$clang->gT('No answer')."</th>\n";
    }
    $answer .= "</tr>\n\t</thead>";
    $answer_t_content = '';
    if ($anscount==0)
    {
        $inputnames=array();
        $answer.="<tr>\t<th class=\"answertext\">".$clang->gT('Error: This question has no answers.')."</th>\n</tr>\n";
    }
    else
    {
        $trbc = '';
        while ($ansrow = $ansresult->FetchRow())
        {
            $myfname = $ia[1].$ansrow['title'];
            $answertext=dTexts__run($ansrow['question']);
            /* Check if this item has not been answered: the 'notanswered' variable must be an array,
             containing a list of unanswered questions, the current question must be in the array,
             and there must be no answer available for the item in this session. */
            if ($ia[6]=='Y' && (is_array($notanswered)) && (array_search($myfname, $notanswered) !== FALSE) && ($_SESSION[$myfname] == '') ) {
                $answertext = "<span class='errormandatory'>{$answertext}</span>";
            }
            $trbc = alternation($trbc , 'row');

            // Get array_filter stuff
            list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname);

            $answer_t_content .= $htmltbody2;

            $answer_t_content .= "<tr class=\"$trbc\">\n"
            . "\t<th class=\"answertext\">\n"
            . $hiddenfield
            . "\t\t\t\t$answertext</th>\n"
            . "\t<td class=\"answer_cell_Y\">\n<label for=\"answer$myfname-Y\">\n"
            . "\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-Y\" value=\"Y\" title=\"".$clang->gT('Yes').'"';
            if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == 'Y')
            {
                $answer_t_content .= CHECKED;
            }
            $answer_t_content .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n\t</label>\n\t</td>\n"
            . "\t<td class=\"answer_cell_U\">\n<label for=\"answer$myfname-U\">\n"
            . "<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-U\" value=\"U\" title=\"".$clang->gT('Uncertain')."\"";

            if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == 'U')
            {
                $answer_t_content .= CHECKED;
            }
            $answer_t_content .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n</label>\n\t</td>\n"
            . "\t<td class=\"answer_cell_N\">\n<label for=\"answer$myfname-N\">\n"
            . "<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-N\" value=\"N\" title=\"".$clang->gT('No').'"';

            if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == 'N')
            {
                $answer_t_content .= CHECKED;
            }
            $answer_t_content .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n</label>\n"
            . "<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";
            if (isset($_SESSION[$myfname]))
            {
                $answer_t_content .= $_SESSION[$myfname];
            }
            $answer_t_content .= "\" />\n\t</td>\n";

            if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
            {
                $answer_t_content .= "\t<td>\n\t<label for=\"answer$myfname-\">\n"
                . "\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-\" value=\"\" title=\"".$clang->gT('No answer')."\"";
                if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == '')
                {
                    $answer_t_content .= CHECKED;
                }
                $answer_t_content .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n</label>\n\t</td>\n";
            }
            $answer_t_content .= "</tr>\n</tbody>";
            $inputnames[]=$myfname;
            $fn++;
        }
    }
    $answer .=  $answer_t_content . "\t\n</table>\n";
    return array($answer, $inputnames);
}

// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_array_increasesamedecrease($ia)
{
    global $dbprefix, $thissurvey, $clang;
    global $notanswered;

    $checkconditionFunction = "checkconditions";

    $qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
    $qresult = db_execute_assoc($qquery);   //Checked
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    if (trim($qidattributes['answer_width'])!='')
    {
        $answerwidth=$qidattributes['answer_width'];
    }
    else
    {
        $answerwidth = 20;
    }
    $cellwidth  = 3; // number of columns
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        ++$cellwidth; // add another column
    }
    $cellwidth = round((( 100 - $answerwidth ) / $cellwidth) , 1); // convert number of columns to percentage of table width

    while($qrow = $qresult->FetchRow())
    {
        $other = $qrow['other'];
    }
    if ($qidattributes['random_order']==1) {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
    }
    else
    {
        $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
    }
    $ansresult = db_execute_assoc($ansquery);  //Checked
    $anscount = $ansresult->RecordCount();

    $fn = 1;

    $answer = "\n<table class=\"question\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - Increase/Same/Decrease Likert scale array\">\n"
    . "\t<colgroup class=\"col-responses\">\n"
    . "\t<col class=\"col-answers\" width=\"$answerwidth%\" />\n";

    $odd_even = '';
    for ($xc=1; $xc<=3; $xc++)
    {
        $odd_even = alternation($odd_even);
        $answer .= "<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
    }
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        $odd_even = alternation($odd_even);
        $answer .= "<col class=\"col-no-answer $odd_even\" width=\"$cellwidth%\" />\n";
    }
    $answer .= "\t</colgroup>\n"
    . "\t<thead>\n"
    . "<tr>\n"
    . "\t<td>&nbsp;</td>\n"
    . "\t<th>".$clang->gT('Increase')."</th>\n"
    . "\t<th>".$clang->gT('Same')."</th>\n"
    . "\t<th>".$clang->gT('Decrease')."</th>\n";
    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
    {
        $answer .= "\t<th>".$clang->gT('No answer')."</th>\n";
    }
    $answer .= "</tr>\n"
    ."\t</thead>\n";
    $answer_body = '';
    $trbc = '';
    while ($ansrow = $ansresult->FetchRow())
    {
        $myfname = $ia[1].$ansrow['title'];
        $answertext=dTexts__run($ansrow['question']);
        /* Check if this item has not been answered: the 'notanswered' variable must be an array,
         containing a list of unanswered questions, the current question must be in the array,
         and there must be no answer available for the item in this session. */
        if ($ia[6]=='Y' && (is_array($notanswered)) && (array_search($myfname, $notanswered) !== FALSE) && ($_SESSION[$myfname] == "") )
        {
            $answertext = "<span class=\"errormandatory\">{$answertext}</span>";
        }

        $trbc = alternation($trbc , 'row');

        // Get array_filter stuff
        list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname);

        $answer_body .= $htmltbody2;

        $answer_body .= "<tr class=\"$trbc\">\n"
        . "\t<th class=\"answertext\">\n"
        . "$answertext\n"
        . $hiddenfield
        . "<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";
        if (isset($_SESSION[$myfname]))
        {
            $answer_body .= $_SESSION[$myfname];
        }
        $answer_body .= "\" />\n\t</th>\n";

        $answer_body .= "\t<td class=\"answer_cell_I\">\n"
        . "<label for=\"answer$myfname-I\">\n"
        ."\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-I\" value=\"I\" title=\"".$clang->gT('Increase').'"';
        if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == 'I')
        {
            $answer_body .= CHECKED;
        }

        $answer_body .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n"
        . "</label>\n"
        . "\t</td>\n"
        . "\t<td class=\"answer_cell_S\">\n"
        . "<label for=\"answer$myfname-S\">\n"
        . "\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-S\" value=\"S\" title=\"".$clang->gT('Same').'"';

        if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == 'S')
        {
            $answer_body .= CHECKED;
        }

        $answer_body .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n"
        . "</label>\n"
        . "\t</td>\n"
        . "\t<td class=\"answer_cell_D\">\n"
        . "<label for=\"answer$myfname-D\">\n"
        . "\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-D\" value=\"D\" title=\"".$clang->gT('Decrease').'"';
        if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == 'D')
        {
            $answer_body .= CHECKED;
        }

        $answer_body .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n"
        . "</label>\n"
        . "<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";

        if (isset($_SESSION[$myfname])) {$answer_body .= $_SESSION[$myfname];}
        $answer_body .= "\" />\n\t</td>\n";

        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
        {
            $answer_body .= "\t<td>\n"
            . "<label for=\"answer$myfname-\">\n"
            . "\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" id=\"answer$myfname-\" value=\"\" title=\"".$clang->gT('No answer').'"';
            if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == '')
            {
                $answer_body .= CHECKED;
            }
            $answer_body .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n"
            . "</label>\n"
            . "\t</td>\n";
        }
        $answer_body .= "</tr>\n\t</tbody>";
        $inputnames[]=$myfname;
        $fn++;
    }
    $answer .=  $answer_body . "\t\n</table>\n";
    return array($answer, $inputnames);
}

// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_array($ia)
{
    global $dbprefix, $connect, $thissurvey, $clang;
    global $repeatheadings;
    global $notanswered;
    global $minrepeatheadings;

    $checkconditionFunction = "checkconditions";

    $qquery = "SELECT other FROM {$dbprefix}questions WHERE qid={$ia[0]} AND language='".$_SESSION['s_lang']."'";
    $qresult = db_execute_assoc($qquery);     //Checked
    while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}
    $lquery = "SELECT * FROM {$dbprefix}answers WHERE qid={$ia[0]} AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY sortorder, code";

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    if (trim($qidattributes['answer_width'])!='')
    {
        $answerwidth=$qidattributes['answer_width'];
    }
    else
    {
        $answerwidth=20;
    }
    $columnswidth=100-$answerwidth;

   if ($qidattributes['use_dropdown'] == 1)
   {
       $useDropdownLayout = true;
   }
   else
   {
       $useDropdownLayout = false;
   }

    $lresult = db_execute_assoc($lquery);   //Checked
    if ($useDropdownLayout === false && $lresult->RecordCount() > 0)
    {
        while ($lrow=$lresult->FetchRow())
        {
            $labelans[]=$lrow['answer'];
            $labelcode[]=$lrow['code'];
        }

        //		$cellwidth=sprintf('%02d', $cellwidth);

        $ansquery = "SELECT question FROM {$dbprefix}questions WHERE parent_qid={$ia[0]} AND question like '%|%' ";
        $ansresult = db_execute_assoc($ansquery);  //Checked
        if ($ansresult->RecordCount()>0) {$right_exists=true;$answerwidth=$answerwidth/2;} else {$right_exists=false;}
        // $right_exists is a flag to find out if there are any right hand answer parts. If there arent we can leave out the right td column
        if ($qidattributes['random_order']==1) {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid={$ia[0]} AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
        }
        else
        {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid={$ia[0]} AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
        }
        $ansresult = db_execute_assoc($ansquery); //Checked
        $anscount = $ansresult->RecordCount();
        $fn=1;

        $numrows = count($labelans);
        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
        {
            ++$numrows;
        }
        if ($right_exists)
        {
            ++$numrows;
        }
        $cellwidth = round( ($columnswidth / $numrows ) , 1 );

        $answer_start = "\n<table class=\"question\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - an array type question\" >\n";
        $answer_head = "\t<thead>\n"
        . "<tr>\n"
        . "\t<td>&nbsp;</td>\n";
        foreach ($labelans as $ld)
        {
            $answer_head .= "\t<th>".$ld."</th>\n";
        }
        if ($right_exists) {$answer_head .= "\t<td>&nbsp;</td>\n";}
        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory and we can show "no answer"
        {
            $answer_head .= "\t<th>".$clang->gT('No answer')."</th>\n";
        }
        $answer_head .= "</tr>\n\t</thead>\n\n\t\n";

        $answer = '';
        $trbc = '';
        $inputnames=array();

        while ($ansrow = $ansresult->FetchRow())
        {
            if (isset($repeatheadings) && $repeatheadings > 0 && ($fn-1) > 0 && ($fn-1) % $repeatheadings == 0)
            {
                if ( ($anscount - $fn + 1) >= $minrepeatheadings )
                {
                    $answer .= "<tr class=\"repeat headings\">\n"
                    . "\t<td>&nbsp;</td>\n";
                    foreach ($labelans as $ld)
                    {
                        $answer .= "\t<th>".$ld."</th>\n";
                    }
                    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory and we can show "no answer"
                    {
                        $answer .= "\t<th>".$clang->gT('No answer')."</th>\n";
                    }
                    $answer .= "</tr>\n";
                }
            }
            $myfname = $ia[1].$ansrow['title'];
            $answertext=dTexts__run($ansrow['question']);
            $answertextsave=$answertext;
            if (strpos($answertext,'|'))
            {
                $answertext=substr($answertext,0, strpos($answertext,'|'));
            }
            /* Check if this item has not been answered: the 'notanswered' variable must be an array,
             containing a list of unanswered questions, the current question must be in the array,
             and there must be no answer available for the item in this session. */

            if (strpos($answertext,'|')) {$answerwidth=$answerwidth/2;}

            if ($ia[6]=='Y' && (is_array($notanswered)) && (array_search($myfname, $notanswered) !== FALSE) && ($_SESSION[$myfname] == '') ) {
                $answertext = '<span class="errormandatory">'.$answertext.'</span>';
            }
            // Get array_filter stuff
            //
            list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname);
				$trbc = alternation($trbc , 'row');
				$fn++;
			$answer .= $htmltbody2;

            $answer .= "<tr class=\"$trbc\">\n"
            . "\t<th class=\"answertext\">\n$answertext"
            . $hiddenfield
            . "<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";
            if (isset($_SESSION[$myfname]))
            {
                $answer .= $_SESSION[$myfname];
            }
            $answer .= "\" />\n\t</th>\n";

            $thiskey=0;
            foreach ($labelcode as $ld)
            {
                $answer .= "\t\t\t<td class=\"answer_cell_00$ld\">\n"
                . "<label for=\"answer$myfname-$ld\">\n"
                . "\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" value=\"$ld\" id=\"answer$myfname-$ld\" title=\""
                . html_escape(strip_tags($labelans[$thiskey])).'"';
                if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $ld)
                {
                    $answer .= CHECKED;
                }
                $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n"
                . "</label>\n"
                . "\t</td>\n";

                $thiskey++;
            }
            if (strpos($answertextsave,'|'))
            {
                $answertext=substr($answertextsave,strpos($answertextsave,'|')+1);
                $answer .= "\t<th class=\"answertextright\">$answertext</th>\n";
            }
            elseif ($right_exists)
            {
                $answer .= "\t<td class=\"answertextright\">&nbsp;</td>\n";
            }

            if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
            {
                $answer .= "\t<td>\n<label for=\"answer$myfname-\">\n"
                ."\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" value=\"\" id=\"answer$myfname-\" title=\"".$clang->gT('No answer').'"';
                if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == '')
                {
                    $answer .= CHECKED;
                }
                $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\"  />\n</label>\n\t</td>\n";
            }

            $answer .= "</tr>\n";
            $inputnames[]=$myfname;
            //IF a MULTIPLE of flexi-redisplay figure, repeat the headings
        }

        $answer_cols = "\t<colgroup class=\"col-responses\">\n"
        ."\t<col class=\"col-answers\" width=\"$answerwidth%\" />\n" ;

        $odd_even = '';
        foreach ($labelans as $c)
        {
            $odd_even = alternation($odd_even);
            $answer_cols .= "<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
        }
        if ($right_exists)
        {
            $odd_even = alternation($odd_even);
            $answer_cols .= "<col class=\"answertextright $odd_even\" width=\"$answerwidth%\" />\n";
        }
        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory
        {
            $odd_even = alternation($odd_even);
            $answer_cols .= "<col class=\"col-no-answer $odd_even\" width=\"$cellwidth%\" />\n";
        }
        $answer_cols .= "\t</colgroup>\n";

        $answer = $answer_start . $answer_cols . $answer_head .$answer . "\t</tbody>\n</table>\n";
    }
   elseif ($useDropdownLayout === true && $lresult->RecordCount() > 0)
   {
       while ($lrow=$lresult->FetchRow())
           $labels[]=Array('code' => $lrow['code'],
                           'answer' => $lrow['answer']);
        $ansquery = "SELECT question FROM {$dbprefix}questions WHERE parent_qid={$ia[0]} AND question like '%|%' ";
       $ansresult = db_execute_assoc($ansquery);  //Checked
       if ($ansresult->RecordCount()>0) {$right_exists=true;$answerwidth=$answerwidth/2;} else {$right_exists=false;}
       // $right_exists is a flag to find out if there are any right hand answer parts. If there arent we can leave out the right td column
        if ($qidattributes['random_order']==1) {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid={$ia[0]} AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
        }
        else
        {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid={$ia[0]} AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
        }
       $ansresult = db_execute_assoc($ansquery); //Checked
       $anscount = $ansresult->RecordCount();
       $fn=1;

       $numrows = count($labels);
       if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
       {
           ++$numrows;
       }
       if ($right_exists)
       {
           ++$numrows;
       }
       $cellwidth = round( ($columnswidth / $numrows ) , 1 );

       $answer_start = "\n<table class=\"question\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - an array type question\" >\n";

       $answer = "\t<tbody>\n";
       $trbc = '';
        $inputnames=array();

       while ($ansrow = $ansresult->FetchRow())
       {
           $myfname = $ia[1].$ansrow['title'];
           $trbc = alternation($trbc , 'row');
           $answertext=$ansrow['question'];
            $answertextsave=$answertext;
           if (strpos($answertext,'|'))
           {
               $answertext=substr($answertext,0, strpos($answertext,'|'));
           }
           /* Check if this item has not been answered: the 'notanswered' variable must be an array,
           containing a list of unanswered questions, the current question must be in the array,
           and there must be no answer available for the item in this session. */

           if (strpos($answertext,'|')) {$answerwidth=$answerwidth/2;}

           if ($ia[6]=='Y' && (is_array($notanswered)) && (array_search($myfname, $notanswered) !== FALSE) && ($_SESSION[$myfname] == '') ) {
               $answertext = '<span class="errormandatory">'.$answertext.'</span>';
           }
           // Get array_filter stuff
           list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname);
           $answer .= $htmltbody2;

           $answer .= "<tr class=\"$trbc\">\n"
           . "\t<th class=\"answertext\">\n$answertext"
           . $hiddenfield
           . "<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";
           if (isset($_SESSION[$myfname]))
           {
               $answer .= $_SESSION[$myfname];
           }
           $answer .= "\" />\n\t</th>\n";

           $answer .= "\t<td >\n"
           . "<select name=\"$myfname\" id=\"answer$myfname\" onchange=\"$checkconditionFunction(this.value, this.name, this.type);\">\n";

           if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] =='')
           {
               $answer .= "\t<option value=\"\" ".SELECTED.'>'.$clang->gT('Please choose')."...</option>\n";
           }

           foreach ($labels as $lrow)
           {
               $answer .= "\t<option value=\"".$lrow['code'].'" ';
               if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $lrow['code'])
               {
                   $answer .= SELECTED;
               }
               $answer .= '>'.$lrow['answer']."</option>\n";
           }
           // If not mandatory and showanswer, show no ans
           if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
           {
               $answer .= "\t<option value=\"\" ";
               if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == '')
               {
                   $answer .= SELECTED;
               }
               $answer .= '>'.$clang->gT('No answer')."</option>\n";
           }
           $answer .= "</select>\n";

           if (strpos($answertextsave,'|'))
           {
               $answertext=substr($answertextsave,strpos($answertextsave,'|')+1);
               $answer .= "\t<th class=\"answertextright\">$answertext</th>\n";
           }
           elseif ($right_exists)
           {
               $answer .= "\t<td class=\"answertextright\">&nbsp;</td>\n";
           }

           $answer .= "</tr>\n</tbody>";
           $inputnames[]=$myfname;
           //IF a MULTIPLE of flexi-redisplay figure, repeat the headings
           $fn++;
       }

       $answer = $answer_start . $answer . "\t</tbody>\n</table>\n";
   }
   else
    {
        $answer = "\n<p class=\"error\">".$clang->gT("Error: There are no answer options for this question and/or they don't exist in this language.")."</p>\n";
        $inputnames='';
    }
    return array($answer, $inputnames);
}




// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_array_multitext($ia)
{
    global $dbprefix, $connect, $thissurvey, $clang;
    global $repeatheadings;
    global $notanswered;
    global $minrepeatheadings;

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "text-keypad";
    }
    else
    {
        $kpclass = "";
    }

    $checkconditionFunction = "checkconditions";
    $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
    $sSeperator = $sSeperator['seperator'];

    //echo "<pre>"; print_r($_POST); echo "</pre>";
    $defaultvaluescript = "";
    $qquery = "SELECT other FROM {$dbprefix}questions WHERE qid={$ia[0]} AND language='".$_SESSION['s_lang']."'";
    $qresult = db_execute_assoc($qquery);
    while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    if (intval(trim($qidattributes['maximum_chars']))>0)
    {
        // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
        $maximum_chars= intval(trim($qidattributes['maximum_chars']));
        $maxlength= "maxlength='{$maximum_chars}' ";
    }
    else
    {
        $maxlength= "";
    }

    $show_grand = $qidattributes['show_grand_total'];
    $totals_class = '';
    $num_class = '';
    $show_totals = '';
    $col_total = '';
    $row_total = '';
    $total_col = '';
    $col_head = '';
    $row_head = '';
    $grand_total = '';
    $q_table_id = '';
    $q_table_id_HTML = '';

    if ($qidattributes['numbers_only']==1)
    {
        $checkconditionFunction = "fixnum_checkconditions";
        $q_table_id = 'totals_'.$ia[0];
	$q_table_id_HTML = ' id="'.$q_table_id.'"';
        $num_class = ' numbers-only';
	switch ($qidattributes['show_totals'])
	{
	    case 'R':
	        $totals_class = $show_totals = 'row';
		$row_total = '			<td class="total">
 				<label>
 					<input name="[[ROW_NAME]]_total" title="[[ROW_NAME]] total" size="[[INPUT_WIDTH]]" value="" type="text" disabled="disabled" class="disabled" />
 				</label>
 			</td>';
 		$col_head = '			<th class="total">Total</th>';
 		if($show_grand == true)
 		{
 			$row_head = '
 			<th class="answertext total">Grand total</th>';
 			$col_total = '
 			<td>&nbsp;</td>';
 			$grand_total = '
 			<td class="total grand">
 				<input type="text" size="[[INPUT_WIDTH]]" value="" disabled="disabled" class="disabled" />
 			</td>';
 		};
 		break;
	    case 'C':
	        $totals_class = $show_totals = 'col';
		$col_total = '
 			<td>
 				<input type="text" size="[[INPUT_WIDTH]]" value="" disabled="disabled" class="disabled" />
 			</td>';
 		$row_head = '
 			<th class="answertext total">Total</th>';
 		if($show_grand == true)
 		{
 		    $row_total = '
 			<td class="total">&nbsp;</td>';
 		    $col_head = '			<th class="total">Grand Total</th>';
		    $grand_total = '
 			<td class="total grand">
 				<input type="text" size="[[INPUT_WIDTH]]" value="" disabled="disabled" class="disabled" />
 			</td>';
 		};
 		break;
 	    case 'B':
	        $totals_class = $show_totals = 'both';
		$row_total = '			<td class="total">
 				<label>
 					<input name="[[ROW_NAME]]_total" title="[[ROW_NAME]] total" size="[[INPUT_WIDTH]]" value="" type="text" disabled="disabled" class="disabled" />
 				</label>
 			</td>';
 		$col_total = '
 			<td>
 				<input type="text" size="[[INPUT_WIDTH]]" value="" disabled="disabled" class="disabled" />
 			</td>';
 		$col_head = '			<th class="total">Total</th>';
		$row_head = '
 			<th class="answertext">Total</th>';
 		if($show_grand == true)
 		{
 		    $grand_total = '
 			<td class="total grand">
 				<input type="text" size="[[INPUT_WIDTH]]" value="" disabled="disabled"/>
 			</td>';
 		}
 		else
 		{
 		    $grand_total = '
 			<td>&nbsp;</td>';
 		};
 		break;
 	};
 	if(!empty($totals_class))
 	{
 	    $totals_class = ' show-totals '.$totals_class;
	    if($qidattributes['show_grand_total'])
	    {
	        $totals_class .= ' grand';
		$show_grand = true;
	    };
	};
    }
    else
    {
        $numbersonly = '';
    };
    if (trim($qidattributes['answer_width'])!='')
    {
        $answerwidth=$qidattributes['answer_width'];
    }
    else
    {
        $answerwidth=20;
    };
    if (trim($qidattributes['text_input_width'])!='')
    {
        $inputwidth=$qidattributes['text_input_width'];
    }
    else
    {
        $inputwidth = 20;
    }
    $columnswidth=100-($answerwidth*2);

    $lquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid={$ia[0]}  AND language='".$_SESSION['s_lang']."' and scale_id=1 ORDER BY question_order";
    $lresult = db_execute_assoc($lquery);
    if ($lresult->RecordCount() > 0)
    {
        while ($lrow=$lresult->FetchRow())
        {
            $labelans[]=$lrow['question'];
            $labelcode[]=$lrow['title'];
        }
        $numrows=count($labelans);
        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) {$numrows++;}
	if( ($show_grand == true &&  $show_totals == 'col' ) || $show_totals == 'row' ||  $show_totals == 'both' )
	{
	    ++$numrows;
	};
        $cellwidth=$columnswidth/$numrows;

        $cellwidth=sprintf('%02d', $cellwidth);

        $ansquery = "SELECT count(question) FROM {$dbprefix}questions WHERE parent_qid={$ia[0]} and scale_id=0 AND question like '%|%'";
        $ansresult = $connect->GetOne($ansquery);
        if ($ansresult>0)
        {
            $right_exists=true;
            $answerwidth=$answerwidth/2;
        }
        else
        {
            $right_exists=false;
        }
        // $right_exists is a flag to find out if there are any right hand answer parts. If there arent we can leave out the right td column
        if ($qidattributes['random_order']==1) {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] and scale_id=0 AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
        }
        else
        {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] and scale_id=0 AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
        }
        $ansresult = db_execute_assoc($ansquery);
        $anscount = $ansresult->RecordCount();
        $fn=1;

        $answer_cols = "\t<colgroup class=\"col-responses\">\n"
        ."\n\t\t<col class=\"answertext\" width=\"$answerwidth%\" />\n";

        $answer_head = "\n\t<thead>\n"
        . "\t\t<tr>\n"
        . "\t\t\t<td width='$answerwidth%'>&nbsp;</td>\n";

        $odd_even = '';
        foreach ($labelans as $ld)
        {
            $answer_head .= "\t<th>".$ld."</th>\n";
            $odd_even = alternation($odd_even);
            $answer_cols .= "<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
        }
        if ($right_exists)
        {
            $answer_head .= "\t<td>&nbsp;</td>\n";// class=\"answertextright\"
            $odd_even = alternation($odd_even);
            $answer_cols .= "<col class=\"answertextright $odd_even\" width=\"$cellwidth%\" />\n";
        }

	if( ($show_grand == true &&  $show_totals == 'col' ) || $show_totals == 'row' ||  $show_totals == 'both' )
	{
	    $answer_head .= $col_head;
	    $odd_even = alternation($odd_even);
	    $answer_cols .= "\t\t<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
	};
        $answer_cols .= "\t</colgroup>\n";

        $answer_head .= "</tr>\n"
        . "\t</thead>\n";

	$answer = "\n<table$q_table_id_HTML class=\"question$num_class"."$totals_class\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - an array of text responses\">\n" . $answer_cols . $answer_head;

        $trbc = '';
        while ($ansrow = $ansresult->FetchRow())
        {
            if (isset($repeatheadings) && $repeatheadings > 0 && ($fn-1) > 0 && ($fn-1) % $repeatheadings == 0)
            {
                if ( ($anscount - $fn + 1) >= $minrepeatheadings )
                {
                    $trbc = alternation($trbc , 'row');
                    $answer .= "<tbody>\n<tr class=\"$trbc repeat\">\n"
                    . "\t<td>&nbsp;</td>\n";
                    foreach ($labelans as $ld)
                    {
                        $answer .= "\t<th>".$ld."</th>\n";
                    }
                    $answer .= "</tr>\n</tbody>\n";
                }
            }
            $myfname = $ia[1].$ansrow['title'];
            $answertext=dTexts__run($ansrow['question']);
            $answertextsave=$answertext;
            /* Check if this item has not been answered: the 'notanswered' variable must be an array,
             containing a list of unanswered questions, the current question must be in the array,
             and there must be no answer available for the item in this session. */
            if ($ia[6]=='Y' && is_array($notanswered))
            {
                //Go through each labelcode and check for a missing answer! If any are found, highlight this line
                $emptyresult=0;
                foreach($labelcode as $ld)
                {
                    $myfname2=$myfname.'_'.$ld;
                    if((array_search($myfname2, $notanswered) !== FALSE) && $_SESSION[$myfname2] == '')
                    {
                        $emptyresult=1;
                    }
                }
                if ($emptyresult == 1)
                {
                    $answertext = "<span class=\"errormandatory\">{$answertext}</span>";
                }
            }

            // Get array_filter stuff
            list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname);

            $answer .= $htmltbody2;

            if (strpos($answertext,'|')) {$answertext=substr($answertext,0, strpos($answertext,'|'));}
            $trbc = alternation($trbc , 'row');
            $answer .= "\t\t<tr class=\"$trbc\" id=\"$myfname\">\n"
            . "\t\t\t<th class=\"answertext\">\n"
            . "\t\t\t\t".$hiddenfield
            . "$answertext\n"
            . "\t\t\t\t<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";
            if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
            $answer .= "\" />\n\t\t\t</th>\n";
            $thiskey=0;
            foreach ($labelcode as $ld)
            {

                $myfname2=$myfname."_$ld";
                $myfname2value = isset($_SESSION[$myfname2]) ? $_SESSION[$myfname2] : "";
                $answer .= "\t<td class=\"answer_cell_00$ld\">\n"
                . "\t\t\t\t<label for=\"answer{$myfname2}\">\n"
                . "\t\t\t\t<input type=\"hidden\" name=\"java{$myfname2}\" id=\"java{$myfname2}\" />\n"
                . "\t\t\t\t<input type=\"text\" name=\"$myfname2\" id=\"answer{$myfname2}\" {$maxlength} class=\"".$kpclass."\" title=\""
                . FlattenText($labelans[$thiskey]).'" '
                . 'size="'.$inputwidth.'" '
                . ' value="'.str_replace ('"', "'", str_replace('\\', '', $myfname2value))."\" />\n";
                $inputnames[]=$myfname2;
                $answer .= "\t\t\t\t</label>\n\t\t\t</td>\n";
                $thiskey += 1;
            }
            if (strpos($answertextsave,'|'))
            {
                $answertext=substr($answertextsave,strpos($answertextsave,'|')+1);
                $answer .= "\t\t\t<td class=\"answertextright\" style=\"text-align:left;\" width=\"$answerwidth%\">$answertext</td>\n";
            }
            elseif ($right_exists)
            {
                $answer .= "\t\t\t<td class=\"answertextright\" style='text-align:left;' width='$answerwidth%'>&nbsp;</td>\n";
            }

            $answer .= str_replace(array('[[ROW_NAME]]','[[INPUT_WIDTH]]') , array(strip_tags($answertext),$inputwidth) , $row_total);
            $answer .= "\n\t\t</tr>\n";
            $answer .= "</tbody>\n";
            //IF a MULTIPLE of flexi-redisplay figure, repeat the headings
            $fn++;
        }
        if($show_totals == 'col' || $show_totals = 'both' || $grand_total == true)
        {
                $answer .= "\t\t<tr class=\"total\">$row_head";
            for( $a = 0; $a < count($labelcode) ; ++$a )
            {
                $answer .= str_replace(array('[[ROW_NAME]]','[[INPUT_WIDTH]]') , array(strip_tags($answertext),$inputwidth) , $col_total);
            };
            $answer .= str_replace(array('[[ROW_NAME]]','[[INPUT_WIDTH]]') , array(strip_tags($answertext),$inputwidth) , $grand_total)."\n\t\t</tr>\n";
            };
            $answer .= "\t</tbody>\n</table>\n";
        if(!empty($q_table_id))
        {
                if ($qidattributes['numbers_only']==1)
                {
                    $radix = $sSeperator;
                }
                else {
                    $radix = 'X';   // to indicate that should not try to change entered values
                }
                $answer .= "\n<script type=\"text/javascript\">new multi_set('$q_table_id','$radix');</script>\n";
        }
        else
        {
            $addcheckcond = <<< EOD
<script type="text/javascript">
<!--
$(document).ready(function()
{
    $('#question{$ia[0]} :input:visible:enabled').each(function(index){
        $(this).bind('keyup',function(e) {
            checkconditions($(this).attr('value'), $(this).attr('name'), $(this).attr('type'));
            return true;
        })
    })
})
// -->
</script>
EOD;
            $answer .= $addcheckcond;
        }
    }
    else
    {
        $answer = "\n<p class=\"error\">".$clang->gT("Error: There are no answer options for this question and/or they don't exist in this language.")."</p>\n";
        $inputnames='';
    }
    return array($answer, $inputnames);
}


// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_array_multiflexi($ia)
{
    global $dbprefix, $connect, $thissurvey, $clang;
    global $repeatheadings;
    global $notanswered;
    global $minrepeatheadings;

    $checkconditionFunction = "fixnum_checkconditions";

    //echo '<pre>'; print_r($_POST); echo '</pre>';
    $defaultvaluescript = '';
    $qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."' and parent_qid=0";
    $qresult = db_execute_assoc($qquery);
    while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    if (trim($qidattributes['multiflexible_max'])!='' && trim($qidattributes['multiflexible_min']) ==''){
        $maxvalue=$qidattributes['multiflexible_max'];
        if(isset($minvalue['value']) && $minvalue['value'] == 0) {$minvalue = 0;} else {$minvalue=1;}
    }
    if (trim($qidattributes['multiflexible_min'])!='' && trim($qidattributes['multiflexible_max']) ==''){
        $minvalue=$qidattributes['multiflexible_min'];
        $maxvalue=$qidattributes['multiflexible_min'] + 10;
    }
    if (trim($qidattributes['multiflexible_min'])=='' && trim($qidattributes['multiflexible_max']) ==''){
        if(isset($minvalue['value']) && $minvalue['value'] == 0) {$minvalue = 0;} else {$minvalue=1;}
        $maxvalue=10;
    }
    if (trim($qidattributes['multiflexible_min']) !='' && trim($qidattributes['multiflexible_max']) !=''){
        if($qidattributes['multiflexible_min'] < $qidattributes['multiflexible_max']){
            $minvalue=$qidattributes['multiflexible_min'];
            $maxvalue=$qidattributes['multiflexible_max'];
        }
    }

    if (trim($qidattributes['multiflexible_step'])!='' && $qidattributes['multiflexible_step'] > 0)
    {
        $stepvalue=$qidattributes['multiflexible_step'];
    }
    else
    {
        $stepvalue=1;
    }

    if($qidattributes['reverse']==1)
    {
        $tmp = $minvalue;
        $minvalue = $maxvalue;
        $maxvalue = $tmp;
        $reverse=true;
        $stepvalue=-$stepvalue;
    }
    else
    {
        $reverse=false;
    }

    $checkboxlayout=false;
    if ($qidattributes['multiflexible_checkbox']!=0)
    {
        $minvalue=0;
        $maxvalue=1;
        $checkboxlayout=true;
    }

    $inputboxlayout=false;
    if ($qidattributes['input_boxes']!=0)
    {
        $inputboxlayout=true;
    }

    if (intval(trim($qidattributes['maximum_chars']))>0)
    {
        // Only maxlength attribute, use textarea[maxlength] jquery selector for textarea
        $maximum_chars= intval(trim($qidattributes['maximum_chars']));
        $maxlength= "maxlength='{$maximum_chars}' ";
    }
    else
    {
        $maxlength= "";
    }

    if ($thissurvey['nokeyboard']=='Y')
    {
        vIncludeKeypad();
        $kpclass = "num-keypad";
    }
    else
    {
        $kpclass = "";
    }

    if (trim($qidattributes['answer_width'])!='')
    {
        $answerwidth=$qidattributes['answer_width'];
    }
    else
    {
        $answerwidth=20;
    }
    $columnswidth=100-($answerwidth*2);

    $lquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid={$ia[0]}  AND language='".$_SESSION['s_lang']."' and scale_id=1 ORDER BY question_order";
    $lresult = db_execute_assoc($lquery);
    if ($lresult->RecordCount() > 0)
    {
        while ($lrow=$lresult->FetchRow())
        {
            $labelans[]=$lrow['question'];
            $labelcode[]=$lrow['title'];
        }
        $numrows=count($labelans);
        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) {$numrows++;}
        $cellwidth=$columnswidth/$numrows;

        $cellwidth=sprintf('%02d', $cellwidth);

        $ansquery = "SELECT question FROM {$dbprefix}questions WHERE parent_qid=".$ia[0]." AND scale_id=0 AND question like '%|%'";
        $ansresult = db_execute_assoc($ansquery);
        if ($ansresult->RecordCount()>0) {$right_exists=true;$answerwidth=$answerwidth/2;} else {$right_exists=false;}
        // $right_exists is a flag to find out if there are any right hand answer parts. If there arent we can leave out the right td column
        if ($qidattributes['random_order']==1) {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND scale_id=0 AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
        }
        else
        {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND scale_id=0 AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
        }
        $ansresult = db_execute_assoc($ansquery);
        $anscount = $ansresult->RecordCount();
        $fn=1;

        $mycols = "\t<colgroup class=\"col-responses\">\n"
        . "\n\t<col class=\"answertext\" width=\"$answerwidth%\" />\n";

        $myheader = "\n\t<thead>\n"
        . "<tr>\n"
        . "\t<td >&nbsp;</td>\n";

        $odd_even = '';
        foreach ($labelans as $ld)
        {
            $myheader .= "\t<th>".$ld."</th>\n";
            $odd_even = alternation($odd_even);
            $mycols .= "<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
        }
        if ($right_exists)
        {
            $myheader .= "\t<td>&nbsp;</td>";
            $odd_even = alternation($odd_even);
            $mycols .= "<col class=\"answertextright $odd_even\" width=\"$answerwidth%\" />\n";
        }
        $myheader .= "</tr>\n"
        . "\t</thead>\n";
        $mycols .= "\t</colgroup>\n";

        $trbc = '';
        $answer = "\n<table class=\"question\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - an array type question with dropdown responses\">\n" . $mycols . $myheader . "\n";

        while ($ansrow = $ansresult->FetchRow())
        {
            if (isset($repeatheadings) && $repeatheadings > 0 && ($fn-1) > 0 && ($fn-1) % $repeatheadings == 0)
            {
                if ( ($anscount - $fn + 1) >= $minrepeatheadings )
                {
                    $trbc = alternation($trbc , 'row');
                    $answer .= "<tbody>\n<tr class=\"$trbc repeat\">\n"
                    . "\t<td>&nbsp;</td>\n";
                    foreach ($labelans as $ld)
                    {
                        $answer .= "\t<th>".$ld."</th>\n";
                    }
                    $answer .= "</tr>\n</tbody>\n";
                }
            }
            $myfname = $ia[1].$ansrow['title'];
            $answertext=dTexts__run($ansrow['question']);
            $answertextsave=$answertext;
            /* Check if this item has not been answered: the 'notanswered' variable must be an array,
             containing a list of unanswered questions, the current question must be in the array,
             and there must be no answer available for the item in this session. */
            if ($ia[6]=='Y' && is_array($notanswered))
            {
                //Go through each labelcode and check for a missing answer! If any are found, highlight this line
                $emptyresult=0;
                foreach($labelcode as $ld)
                {
                    $myfname2=$myfname.'_'.$ld;
                    if((array_search($myfname2, $notanswered) !== FALSE) && $_SESSION[$myfname2] == "")
                    {
                        $emptyresult=1;
                    }
                }
                if ($emptyresult == 1)
                {
                    $answertext = '<span class="errormandatory">'.$answertext.'</span>';
                }
            }

            // Get array_filter stuff
            list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname, $trbc, $myfname);

            $answer .= $htmltbody2;

            if (strpos($answertext,'|')) {$answertext=substr($answertext,0, strpos($answertext,'|'));}

            $trbc = alternation($trbc , 'row');

            $answer .= "<tr class=\"$trbc\">\n"
            . "\t<th class=\"answertext\" width=\"$answerwidth%\">\n"
            . "$answertext\n"
            . $hiddenfield
            . "<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";
            if (isset($_SESSION[$myfname]))
            {
                $answer .= $_SESSION[$myfname];
            }
            $answer .= "\" />\n\t</th>\n";
            $first_hidden_field = '';
            $thiskey=0;
            foreach ($labelcode as $ld)
            {

                if ($checkboxlayout == false)
                {
                    $myfname2=$myfname."_$ld";
                    if(isset($_SESSION[$myfname2]))
                    {
                        $myfname2_java_value = " value=\"$_SESSION[$myfname2]\" ";
                    }
                    else
                    {
                        $myfname2_java_value = "";
                    }
                    $answer .= "\t<td class=\"answer_cell_00$ld\">\n"
                    . "<label for=\"answer{$myfname2}\">\n"
                    . "\t<input type=\"hidden\" name=\"java{$myfname2}\" id=\"java{$myfname2}\" $myfname2_java_value />\n";

                    if($inputboxlayout == false) {
                        $answer .= "\t<select class=\"multiflexiselect\" name=\"$myfname2\" id=\"answer{$myfname2}\" title=\""
                        . html_escape($labelans[$thiskey]).'"'
                        . " onchange=\"$checkconditionFunction(this.value, this.name, this.type)\">\n"
                        . "<option value=\"\">".$clang->gT('...')."</option>\n";

                        for($ii=$minvalue; ($reverse? $ii>=$maxvalue:$ii<=$maxvalue); $ii+=$stepvalue) {
                            $answer .= "<option value=\"$ii\"";
                            if(isset($_SESSION[$myfname2]) && $_SESSION[$myfname2] == $ii) {
                                $answer .= SELECTED;
                            }
                            $answer .= ">$ii</option>\n";
                        }
                        $answer .= "\t</select>\n";
                    } elseif ($inputboxlayout == true)
                    {
                        $sSeperator = getRadixPointData($thissurvey['surveyls_numberformat']);
                        $sSeperator = $sSeperator['seperator'];
                        $answer .= "\t<input type='text' class=\"multiflexitext $kpclass\" name=\"$myfname2\" id=\"answer{$myfname2}\" {$maxlength} size=5 title=\""
                        . html_escape($labelans[$thiskey]).'"'
                        . " onkeyup=\"$checkconditionFunction(this.value, this.name, this.type)\""
                        . " value=\"";
                        if(isset($_SESSION[$myfname2]) && $_SESSION[$myfname2]) {
                            $dispVal = str_replace('.',$sSeperator,$_SESSION[$myfname2]);
                            $answer .= $dispVal;
                        }
                        $answer .= "\" />\n";
                    }
                    $answer .= "</label>\n"
                    . "\t</td>\n";

                    $inputnames[]=$myfname2;
                    $thiskey++;
                }
                else
                {
                    $myfname2=$myfname."_$ld";
                    if(isset($_SESSION[$myfname2]) && $_SESSION[$myfname2] == '1')
                    {
                        $myvalue = '1';
                        $setmyvalue = CHECKED;
                    }
                    else
                    {
                        $myvalue = '';
                        $setmyvalue = '';
                    }
                    $answer .= "\t<td class=\"answer_cell_00$ld\">\n"
                    //					. "<label for=\"answer{$myfname2}\">\n"
                    . "\t<input type=\"hidden\" name=\"java{$myfname2}\" id=\"java{$myfname2}\" value=\"$myvalue\"/>\n"
                    . "\t<input type=\"hidden\" name=\"$myfname2\" id=\"answer{$myfname2}\" value=\"$myvalue\" />\n";
                    $answer .= "\t<input type=\"checkbox\" name=\"cbox_$myfname2\" id=\"cbox_$myfname2\" $setmyvalue "
                    . " onclick=\"cancelBubbleThis(event); "
                    . " aelt=document.getElementById('answer{$myfname2}');"
                    . " jelt=document.getElementById('java{$myfname2}');"
                    . " if(this.checked) {"
                    . "  aelt.value=1;jelt.value=1;$checkconditionFunction(1,'{$myfname2}',aelt.type);"
                    . " } else {"
                    . "  aelt.value=0;jelt.value=0;$checkconditionFunction(0,'{$myfname2}',aelt.type);"
                    . " }; return true;\" "
                    //					. " onchange=\"checkconditions(this.value, this.name, this.type)\" "
                    . " />\n";
                    $inputnames[]=$myfname2;
                    //					$answer .= "</label>\n"
                    $answer .= ""
                    . "\t</td>\n";
                    $thiskey++;
                }
            }
            if (strpos($answertextsave,'|'))
            {
                $answertext=substr($answertextsave,strpos($answertextsave,'|')+1);
                $answer .= "\t<td class=\"answertextright\" style='text-align:left;' width=\"$answerwidth%\">$answertext</td>\n";
            }
            elseif ($right_exists)
            {
                $answer .= "\t<td class=\"answertextright\" style='text-align:left;' width=\"$answerwidth%\">&nbsp;</td>\n";
            }

            $answer .= "</tr>\n\t</tbody>";
            //IF a MULTIPLE of flexi-redisplay figure, repeat the headings
            $fn++;
        }
        $answer .= "\n</table>\n";
    }
    else
    {
        $answer = "\n<p class=\"error\">".$clang->gT("Error: There are no answer options for this question and/or they don't exist in this language.")."</p>\n";
        $inputnames = '';
    }
    return array($answer, $inputnames);
}


// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_arraycolumns($ia)
{
    global $dbprefix;
    global $notanswered, $clang;

    $checkconditionFunction = "checkconditions";

    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);
    $qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
    $qresult = db_execute_assoc($qquery);    //Checked
    while($qrow = $qresult->FetchRow()) {$other = $qrow['other'];}
    $lquery = "SELECT * FROM {$dbprefix}answers WHERE qid=".$ia[0]."  AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY sortorder, code";
    $lresult = db_execute_assoc($lquery);   //Checked
    if ($lresult->RecordCount() > 0)
    {
        while ($lrow=$lresult->FetchRow())
        {
            $labelans[]=$lrow['answer'];
            $labelcode[]=$lrow['code'];
            $labels[]=array("answer"=>$lrow['answer'], "code"=>$lrow['code']);
        }
        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
        {
            $labelcode[]='';
            $labelans[]=$clang->gT('No answer');
            $labels[]=array('answer'=>$clang->gT('No answer'), 'code'=>'');
        }
        if ($qidattributes['random_order']==1) {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
        }
        else
        {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
        }
        $ansresult = db_execute_assoc($ansquery);  //Checked
        $anscount = $ansresult->RecordCount();
        if ($anscount>0)
        {
            $fn=1;
            $cellwidth=$anscount;
            $cellwidth=round(( 50 / $cellwidth ) , 1);
            $answer = "\n<table class=\"question\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - an array type question with a single response per row\">\n\n"
            . "\t<colgroup class=\"col-responses\">\n"
            . "\t<col class=\"col-answers\" width=\"50%\" />\n";
            $odd_even = '';
            for( $c = 0 ; $c < $anscount ; ++$c )
            {
                $odd_even = alternation($odd_even);
                $answer .= "<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
            }
            $answer .= "\t</colgroup>\n\n"
            . "\t<thead>\n"
            . "<tr>\n"
            . "\t<td>&nbsp;</td>\n";
            while ($ansrow = $ansresult->FetchRow())
            {
                $anscode[]=$ansrow['title'];
                $answers[]=dTexts__run($ansrow['question']);
            }
            $trbc = '';
            $odd_even = '';
            for ($_i=0;$_i<count($answers);++$_i)
            {
                $ld = $answers[$_i];
                $myfname = $ia[1].$anscode[$_i];
                $trbc = alternation($trbc , 'row');
                /* Check if this item has not been answered: the 'notanswered' variable must be an array,
                 containing a list of unanswered questions, the current question must be in the array,
                 and there must be no answer available for the item in this session. */
                if ($ia[6]=='Y' && (is_array($notanswered)) && (array_search($myfname, $notanswered) !== FALSE) && ($_SESSION[$myfname] == "") )
                {
                    $ld = "<span class=\"errormandatory\">{$ld}</span>";
                }
                $odd_even = alternation($odd_even);
                $answer .= "\t<th class=\"$odd_even\">$ld</th>\n";
            }
            unset($trbc);
            $answer .= "</tr>\n\t</thead>\n\n\t<tbody>\n";
            $ansrowcount=0;
            $ansrowtotallength=0;
            while ($ansrow = $ansresult->FetchRow())
            {
                $ansrowcount++;
                $ansrowtotallength=$ansrowtotallength+strlen($ansrow['question']);
            }
            $percwidth=100 - ($cellwidth*$anscount);
            foreach($labels as $ansrow)
            {
                $answer .= "<tr>\n"
                . "\t<th class=\"arraycaptionleft\">{$ansrow['answer']}</th>\n";
                foreach ($anscode as $ld)
                {
                    //if (!isset($trbc) || $trbc == 'array1') {$trbc = 'array2';} else {$trbc = 'array1';}
                    $myfname=$ia[1].$ld;
                    $answer .= "\t<td class=\"answer_cell_00$ld\">\n"
                    . "<label for=\"answer".$myfname.'-'.$ansrow['code']."\">\n"
                    . "\t<input class=\"radio\" type=\"radio\" name=\"".$myfname.'" value="'.$ansrow['code'].'" '
                    . 'id="answer'.$myfname.'-'.$ansrow['code'].'" '
                    . 'title="'.html_escape(strip_tags($ansrow['answer'])).'"';
                    if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $ansrow['code'])
                    {
                        $answer .= CHECKED;
                    }
                    elseif (!isset($_SESSION[$myfname]) && $ansrow['code'] == '')
                    {
                        $answer .= CHECKED;
                        // Humm.. (by lemeur), not sure this section can be reached
                        // because I think $_SESSION[$myfname] is always set (by save.php ??) !
                        // should remove the !isset part I think !!
                    }
                    $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n</label>\n\t</td>\n";
                }
                unset($trbc);
                $answer .= "</tr>\n";
                $fn++;
            }

            $answer .= "\t</tbody>\n</table>\n";
            foreach($anscode as $ld)
            {
                $myfname=$ia[1].$ld;
                $answer .= '<input type="hidden" name="java'.$myfname.'" id="java'.$myfname.'" value="';
                if (isset($_SESSION[$myfname]))
                {
                    $answer .= $_SESSION[$myfname];
                }
                $answer .= "\" />\n";
                $inputnames[]=$myfname;
            }
        }
        else
        {
            $answer = '<p class="error">'.$clang->gT('Error: There are no answers defined for this question.')."</p>";
            $inputnames="";
        }
    }
    else
    {
        $answer = "<p class='error'>".$clang->gT("Error: There are no answer options for this question and/or they don't exist in this language.")."</p>\n";
        $inputnames = '';
    }
    return array($answer, $inputnames);
}


// ---------------------------------------------------------------
// TMSW TODO - Can remove DB query by passing in answer list from EM
function do_array_dual($ia)
{
    global $dbprefix, $connect, $thissurvey, $clang;
    global $repeatheadings;
    global $notanswered;
    global $minrepeatheadings;

    $checkconditionFunction = "checkconditions";

    $inputnames=array();
    $labelans1=array();
    $labelans=array();
    $qquery = "SELECT other FROM {$dbprefix}questions WHERE qid=".$ia[0]." AND language='".$_SESSION['s_lang']."'";
    $other = $connect->GetOne($qquery);    //Checked
    $lquery =  "SELECT * FROM {$dbprefix}answers WHERE scale_id=0 AND qid={$ia[0]} AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, code";
    $lquery1 = "SELECT * FROM {$dbprefix}answers WHERE scale_id=1 AND qid={$ia[0]} AND language='".$_SESSION['s_lang']."' ORDER BY sortorder, code";
    $qidattributes=getQuestionAttributes($ia[0],$ia[4]);

    if ($qidattributes['use_dropdown']==1)
    {
        $useDropdownLayout = true;
    }
    else
    {
        $useDropdownLayout = false;
    }

    if (trim($qidattributes['dualscale_headerA'])!='') {
        $leftheader= $clang->gT($qidattributes['dualscale_headerA']);
    }
    else
    {
        $leftheader ='';
    }

    if (trim($qidattributes['dualscale_headerB'])!='')
    {
        $rightheader= $clang->gT($qidattributes['dualscale_headerB']);
    }
    else {
        $rightheader ='';
    }

    $lresult = db_execute_assoc($lquery); //Checked
    if ($useDropdownLayout === false && $lresult->RecordCount() > 0)
    {

        if (trim($qidattributes['answer_width'])!='')
        {
            $answerwidth=$qidattributes['answer_width'];
        }
        else
        {
            $answerwidth=20;
        }
        $columnswidth = 100 - $answerwidth;


        while ($lrow=$lresult->FetchRow())
        {
            $labelans[]=$lrow['answer'];
            $labelcode[]=$lrow['code'];
        }
        $lresult1 = db_execute_assoc($lquery1); //Checked
        if ($lresult1->RecordCount() > 0)
        {
            while ($lrow1=$lresult1->FetchRow())
            {
                $labelans1[]=$lrow1['answer'];
                $labelcode1[]=$lrow1['code'];
            }
        }
        $numrows=count($labelans) + count($labelans1);
        if ($ia[6] != "Y" && SHOW_NO_ANSWER == 1) {$numrows++;}
        $cellwidth=$columnswidth/$numrows;

        $cellwidth=sprintf("%02d", $cellwidth);

        $ansquery = "SELECT question FROM {$dbprefix}questions WHERE parent_qid=".$ia[0]." and scale_id=0 AND question like '%|%'";
        $ansresult = db_execute_assoc($ansquery);   //Checked
        if ($ansresult->RecordCount()>0)
        {
            $right_exists=true;
        }
        else
        {
            $right_exists=false;
        }
        // $right_exists is a flag to find out if there are any right hand answer parts. If there arent we can leave out the right td column
        if ($qidattributes['random_order']==1) {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY ".db_random();
        }
        else
        {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] AND language='".$_SESSION['s_lang']."' and scale_id=0 ORDER BY question_order";
        }
        $ansresult = db_execute_assoc($ansquery);   //Checked
        $anscount = $ansresult->RecordCount();
        $fn=1;
        // unselect second scale when using "no answer"
        $answer = "<script type='text/javascript'>\n"
        . "<!--\n"
        . "function noanswer_checkconditions(value, name, type)\n"
        . "{\n"
        . "\tvar vname;\n"
        . "\tvname = name.replace(/#.*$/,\"\");\n"
        . "\t$('input[name^=\"' + vname + '\"]').attr('checked',false);\n"
        . "\t$('input[id=\"answer' + vname + '#0-\"]').attr('checked',true);\n"
        . "\t$('input[name^=\"java' + vname + '\"]').val('');\n"
        . "\t$checkconditionFunction(value, name, type);\n"
        . "}\n"
        . "function secondlabel_checkconditions(value, name, type)\n"
        . "{\n"
        . "\tvar vname;\n"
        . "\tvname = \"answer\"+name.replace(/#1/g,\"#0-\");\n"
        . "\tif(document.getElementById(vname))\n"
        . "\t{\n"
        . "\tdocument.getElementById(vname).checked=false;\n"
        . "\t}\n"
        . "\t$checkconditionFunction(value, name, type);\n"
        . "}\n"
        . " //-->\n"
        . " </script>\n";



        // Header row and colgroups
        $mycolumns = "\t<colgroup class=\"col-responses group-1\">\n"
        ."\t<col class=\"col-answers\" width=\"$answerwidth%\" />\n";


        $myheader2 = "\n<tr class=\"array1 header_row\">\n"
        . "\t<th class=\"header_answer_text\">&nbsp;</th>\n\n";
        $odd_even = '';
        foreach ($labelans as $ld)
        {
            $myheader2 .= "\t<th>".$ld."</th>\n";
            $odd_even = alternation($odd_even);
            $mycolumns .= "<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
        }
        $mycolumns .= "\t</colgroup>\n";

        if (count($labelans1)>0) // if second label set is used
        {
            $mycolumns .= "\t<colgroup class=\"col-responses group-2\">\n"
            . "\t<col class=\"seperator\" />\n";
            $myheader2 .= "\n\t<td class=\"header_separator\">&nbsp;</td>\n\n"; // Separator
            foreach ($labelans1 as $ld)
            {
                $myheader2 .= "\t<th>".$ld."</th>\n";
                $odd_even = alternation($odd_even);
                $mycolumns .= "<col class=\"$odd_even\" width=\"$cellwidth%\" />\n";
            }

        }
        if ($right_exists)
        {
        	$myheader2 .= "\t<td class=\"header_answer_text_right\">&nbsp;</td>\n";
            $mycolumns .= "\n\t<col class=\"answertextright\" />\n\n";
        }
        if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory and we can show "no answer"
        {
        	$myheader2 .= "\t<td class=\"header_separator\">&nbsp;</td>\n"; // Separator
            $myheader2 .= "\t<th class=\"header_no_answer\">".$clang->gT('No answer')."</th>\n";
            $odd_even = alternation($odd_even);
            $mycolumns .= "\n\t<col class=\"seperator\" />\n\n";
            $mycolumns .= "\t<col class=\"col-no-answer $odd_even\" width=\"$cellwidth%\" />\n";
        }

        $mycolumns .= "\t</colgroup>\n";
        $myheader2 .= "</tr>\n";



        // build first row of header if needed
        if ($leftheader != '' || $rightheader !='')
        {
            $myheader1 = "<tr class=\"array1 groups header_row\">\n"
            . "\t<th class=\"header_answer_text\">&nbsp;</th>\n"
            . "\t<th colspan=\"".count($labelans)."\" class=\"dsheader\">$leftheader</th>\n";

            if (count($labelans1)>0)
            {
                $myheader1 .= "\t<td class=\"header_separator\">&nbsp;</td>\n" // Separator
                ."\t<th colspan=\"".count($labelans1)."\" class=\"dsheader\">$rightheader</th>\n";
            }
			if ($right_exists)
			{
				$myheader1 .= "\t<td class=\"header_answer_text_right\">&nbsp;</td>\n";
			}
            if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
            {
            	$myheader1 .= "\t<td class=\"header_separator\">&nbsp;</td>\n"; // Separator
                $myheader1 .= "\t<th class=\"header_no_answer\">&nbsp;</th>\n";
            }
            $myheader1 .= "</tr>\n";
        }
        else
        {
            $myheader1 = '';
        }

        $answer .= "\n<table class=\"question\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - a dual array type question\">\n"
        . $mycolumns
        . "\n\t<thead>\n"
        . $myheader1
        . $myheader2
        . "\n\t</thead>\n";

        $trbc = '';
        while ($ansrow = $ansresult->FetchRow())
        {
            // Build repeat headings if needed
            if (isset($repeatheadings) && $repeatheadings > 0 && ($fn-1) > 0 && ($fn-1) % $repeatheadings == 0)
            {
                if ( ($anscount - $fn + 1) >= $minrepeatheadings )
                {
                    $answer .= "<tbody>\n<tr  class=\"repeat\">\n"
                    . "\t<th class=\"header_answer_text\">&nbsp;</th>\n";
                    foreach ($labelans as $ld)
                    {
                        $answer .= "\t<th>".$ld."</th>\n";
                    }
                    if (count($labelans1)>0) // if second label set is used
                    {
                        $answer .= "<th class=\"header_separator\">&nbsp;</th>\n"; // Separator
                        foreach ($labelans1 as $ld)
                        {
                            $answer .= "\t<th>".$ld."</th>\n";
                        }
                    }
					if ($right_exists)
					{
						$answer .= "\t<td class=\"header_answer_text_right\">&nbsp;</td>\n";
					}
                    if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1) //Question is not mandatory and we can show "no answer"
                    {
                        $answer .= "\t<td class=\"header_separator\">&nbsp;</td>\n"; // Separator
                        $answer .= "\t<th class=\"header_no_answer\">".$clang->gT('No answer')."</th>\n";
                    }
                    $answer .= "</tr>\n</tbody>\n";
                }
            }

            $trbc = alternation($trbc , 'row');
            $answertext=dTexts__run($ansrow['question']);
            $answertextsave=$answertext;

            $dualgroup=0;
            $myfname0= $ia[1].$ansrow['title'];
            $myfname = $ia[1].$ansrow['title'].'#0';
            $myfname1 = $ia[1].$ansrow['title'].'#1'; // new multi-scale-answer
            /* Check if this item has not been answered: the 'notanswered' variable must be an array,
            containing a list of unanswered questions, the current question must be in the array,
            and there must be no answer available for the item in this session. */
            if ($ia[6]=='Y' && (is_array($notanswered)) && ((array_search($myfname, $notanswered) !== FALSE) || (array_search($myfname1, $notanswered) !== FALSE)) && (($_SESSION[$myfname] == '') || ($_SESSION[$myfname1] == '')) )
            {
                $answertext = "<span class='errormandatory'>{$answertext}</span>";
            }

            // Get array_filter stuff
            list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $myfname0, $trbc, $myfname);

            $answer .= $htmltbody2;

            if (strpos($answertext,'|')) {$answertext=substr($answertext,0, strpos($answertext,'|'));}

            array_push($inputnames,$myfname);
            $answer .= "<tr class=\"$trbc\">\n"
            . "\t<th class=\"answertext\">\n"
            . $hiddenfield
            . "$answertext\n"
            . "<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";
            if (isset($_SESSION[$myfname])) {$answer .= $_SESSION[$myfname];}
            $answer .= "\" />\n\t</th>\n";
            $hiddenanswers='';
            $thiskey=0;

            foreach ($labelcode as $ld)
            {
                $answer .= "\t<td class=\"answer_cell_1_00$ld\">\n"
                . "<label for=\"answer$myfname-$ld\">\n"
                . "\t<input class=\"radio\" type=\"radio\" name=\"$myfname\" value=\"$ld\" id=\"answer$myfname-$ld\" title=\""
                . html_escape(strip_tags($labelans[$thiskey])).'"';
                if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $ld)
                {
                    $answer .= CHECKED;
                }
                $answer .= " onclick=\"$checkconditionFunction(this.value, this.name, this.type)\" />\n</label>\n";
                $answer .= "\n\t</td>\n";
                $thiskey++;
            }
            if (count($labelans1)>0) // if second label set is used
            {
                $dualgroup++;
                $hiddenanswers='';
                $answer .= "\t<td class=\"dual_scale_separator\">&nbsp;</td>\n";		// separator
                array_push($inputnames,$myfname1);
                $hiddenanswers .= "<input type=\"hidden\" name=\"java$myfname1\" id=\"java$myfname1\" value=\"";
                if (isset($_SESSION[$myfname1])) {$hiddenanswers .= $_SESSION[$myfname1];}
                $hiddenanswers .= "\" />\n";
                $thiskey=0;
                foreach ($labelcode1 as $ld) // second label set
                {
                    $answer .= "\t<td class=\"answer_cell_2_00$ld\">\n";
                    if ($hiddenanswers!='')
                    {
                        $answer .=$hiddenanswers;
                        $hiddenanswers='';
                    }
                    $answer .= "<label for=\"answer$myfname1-$ld\">\n"
                    . "\t<input class=\"radio\" type=\"radio\" name=\"$myfname1\" value=\"$ld\" id=\"answer$myfname1-$ld\" title=\""
                    . html_escape(strip_tags($labelans1[$thiskey])).'"';
                    if (isset($_SESSION[$myfname1]) && $_SESSION[$myfname1] == $ld)
                    {
                        $answer .= CHECKED;
                    }
                    $answer .= " onclick=\"secondlabel_checkconditions(this.value, this.name, this.type)\" />\n</label>\n";

                    $answer .= "\t</td>\n";
                    $thiskey++;
                }
            }
            if (strpos($answertextsave,'|'))
            {
                $answertext=substr($answertextsave,strpos($answertextsave,'|')+1);
                $answer .= "\t<td class=\"answertextright\">$answertext</td>\n";
                $hiddenanswers = '';
            }
            elseif ($right_exists)
            {
                $answer .= "\t<td class=\"answertextright\">&nbsp;</td>\n";
            }

            if ($ia[6] != "Y" && SHOW_NO_ANSWER == 1)
            {
                $answer .= "\t<td class=\"dual_scale_separator\">&nbsp;</td>\n"; // separator
				$answer .= "\t<td class=\"dual_scale_no_answer\">\n"
                . "<label for='answer$myfname-'>\n"
                . "\t<input class='radio' type='radio' name='$myfname' value='' id='answer$myfname-' title='".$clang->gT("No answer")."'";
                if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == "")
                {
                    $answer .= CHECKED;
                }
                $answer .= " onclick=\"noanswer_checkconditions(this.value, this.name, this.type)\" />\n"
                . "</label>\n"
                . "\t</td>\n";
            }

            $answer .= "</tr>\n";
        	$answer .= "\t</tbody>\n";
            // $inputnames[]=$myfname;
            //IF a MULTIPLE of flexi-redisplay figure, repeat the headings
            $fn++;
        }
        $answer .= "</table>\n";
    }
    elseif ($useDropdownLayout === true && $lresult->RecordCount() > 0)
    {

        if (trim($qidattributes['answer_width'])!='')
        {
            $answerwidth=$qidattributes['answer_width'];
        } else {
            $answerwidth=20;
        }
        $separatorwidth=(100-$answerwidth)/10;
        $columnswidth=100-$answerwidth-($separatorwidth*2);

        $answer = "";

        // Get Answers

        //question atribute random_order set?
        if ($qidattributes['random_order']==1) {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] and scale_id=0 AND language='".$_SESSION['s_lang']."' ORDER BY ".db_random();
        }

        //no question attributes -> order by sortorder
        else
        {
            $ansquery = "SELECT * FROM {$dbprefix}questions WHERE parent_qid=$ia[0] and scale_id=0 AND language='".$_SESSION['s_lang']."' ORDER BY question_order";
        }
        $ansresult = db_execute_assoc($ansquery);    //Checked
        $anscount = $ansresult->RecordCount();

        if ($anscount==0)
        {
            $inputnames = array();
            $answer .="\n<p class=\"error\">".$clang->gT('Error: This question has no answers.')."</p>\n";
        }
        else
        {

            //already done $lresult = db_execute_assoc($lquery);
            while ($lrow=$lresult->FetchRow())
            {
                $labels0[]=Array('code' => $lrow['code'],
						'title' => $lrow['answer']);
            }
            $lresult1 = db_execute_assoc($lquery1);   //Checked
            while ($lrow1=$lresult1->FetchRow())
            {
                $labels1[]=Array('code' => $lrow1['code'],
						'title' => $lrow1['answer']);
            }


            // Get attributes for Headers and Prefix/Suffix

            if (trim($qidattributes['dropdown_prepostfix'])!='') {
                list ($ddprefix, $ddsuffix) =explode("|",$qidattributes['dropdown_prepostfix']);
                $ddprefix = $ddprefix;
                $ddsuffix = $ddsuffix;
            }
            else
            {
                $ddprefix ='';
                $ddsuffix='';
            }
            if (trim($qidattributes['dropdown_separators'])!='') {
                list ($postanswSep, $interddSep) =explode('|',$qidattributes['dropdown_separators']);
                $postanswSep = $postanswSep;
                $interddSep = $interddSep;
            }
            else {
                $postanswSep = '';
                $interddSep = '';
            }

            $colspan_1 = '';
            $colspan_2 = '';
            $suffix_cell = '';
            $answer .= "\n<table class=\"question\" summary=\"".str_replace('"','' ,strip_tags($ia[3]))." - an dual array type question\">\n\n"
            . "\t<col class=\"answertext\" width=\"$answerwidth%\" />\n";
            if($ddprefix != '')
            {
                $answer .= "\t<col class=\"ddprefix\" />\n";
                $colspan_1 = ' colspan="2"';
            }
            $answer .= "\t<col class=\"dsheader\" />\n";
            if($ddsuffix != '')
            {
                $answer .= "\t<col class=\"ddsuffix\" />\n";
                if(!empty($colspan_1))
                {
                    $colspan_2 = ' colspan="3"';
                }
                $suffix_cell = "\t<td>&nbsp;</td>\n"; // suffix
            }
            $answer .= "\t<col class=\"ddarrayseparator\" width=\"$separatorwidth%\" />\n";
            if($ddprefix != '')
            {
                $answer .= "\t<col class=\"ddprefix\" />\n";
            }
            $answer .= "\t<col class=\"dsheader\" />\n";
            if($ddsuffix != '')
            {
                $answer .= "\t<col class=\"ddsuffix\" />\n";
            };
            // headers
            $answer .= "\n\t<thead>\n"
            . "<tr>\n"
            . "\t<td$colspan_1>&nbsp;</td>\n" // prefix
            . "\n"
            //			. "\t<td align='center' width='$columnswidth%'><span class='dsheader'>$leftheader</span></td>\n"
            . "\t<th>$leftheader</th>\n"
            . "\n"
            . "\t<td$colspan_2>&nbsp;</td>\n" // suffix // Inter DD separator // prefix
            //			. "\t<td align='center' width='$columnswidth%'><span class='dsheader'>$rightheader</span></td>\n"
            . "\t<th>$rightheader</th>\n"
            . $suffix_cell."</tr>\n"
            . "\t</thead>\n\n";

            $trbc = '';
            while ($ansrow = $ansresult->FetchRow())
            {
                $rowname = $ia[1].$ansrow['title'];
                $dualgroup=0;
                $myfname = $ia[1].$ansrow['title']."#".$dualgroup;
                $dualgroup1=1;
                $myfname1 = $ia[1].$ansrow['title']."#".$dualgroup1;

                if ($ia[6]=='Y' && (is_array($notanswered)) && ((array_search($myfname, $notanswered) !== FALSE) || (array_search($myfname1, $notanswered) !== FALSE)) && (($_SESSION[$myfname] == '') || ($_SESSION[$myfname1] == '')) )
                {
                    $answertext="<span class='errormandatory'>".dTexts__run($ansrow['question'])."</span>";
                }
                else
                {
                    $answertext=dTexts__run($ansrow['question']);
                }

                $trbc = alternation($trbc , 'row');

                // Get array_filter stuff
                list($htmltbody2, $hiddenfield)=return_array_filter_strings($ia, $qidattributes, $thissurvey, $ansrow, $rowname, $trbc, $myfname);

                $answer .= $htmltbody2;

                $answer .= "<tr class=\"$trbc\">\n"
                . "\t<th class=\"answertext\">\n"
                . "<label for=\"answer$rowname\">\n"
                . $hiddenfield
                . "$answertext\n"
                . "</label>\n"
                . "\t</th>\n";

                // Label0

                // prefix
                if($ddprefix != '')
                {
                    $answer .= "\t<td class=\"ddprefix\">$ddprefix</td>\n";
                }
                $answer .= "\t<td >\n"
                . "<select name=\"$myfname\" id=\"answer$myfname\" onchange=\"array_dual_dd_checkconditions(this.value, this.name, this.type,$dualgroup,$checkconditionFunction);\">\n";

                if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] =='')
                {
                    $answer .= "\t<option value=\"\" ".SELECTED.'>'.$clang->gT('Please choose...')."</option>\n";
                }

                foreach ($labels0 as $lrow)
                {
                    $answer .= "\t<option value=\"".$lrow['code'].'" ';
                    if (isset($_SESSION[$myfname]) && $_SESSION[$myfname] == $lrow['code'])
                    {
                        $answer .= SELECTED;
                    }
                    $answer .= '>'.$lrow['title']."</option>\n";
                }
                // If not mandatory and showanswer, show no ans
                if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
                {
                    $answer .= "\t<option value=\"\" ";
                    if (!isset($_SESSION[$myfname]) || $_SESSION[$myfname] == '')
                    {
                        $answer .= SELECTED;
                    }
                    $answer .= '>'.$clang->gT('No answer')."</option>\n";
                }
                $answer .= "</select>\n";

                // suffix
                if($ddsuffix != '')
                {
                    $answer .= "\t<td class=\"ddsuffix\">$ddsuffix</td>\n";
                }
                $answer .= "<input type=\"hidden\" name=\"java$myfname\" id=\"java$myfname\" value=\"";
                if (isset($_SESSION[$myfname]))
                {
                    $answer .= $_SESSION[$myfname];
                }
                $answer .= "\" />\n"
                . "\t</td>\n";

                $inputnames[]=$myfname;

                $answer .= "\t<td class=\"ddarrayseparator\">$interddSep</td>\n"; //Separator

                // Label1

                // prefix
                if($ddprefix != '')
                {
                    $answer .= "\t<td class='ddprefix'>$ddprefix</td>\n";
                }
                //				$answer .= "\t<td align='left' width='$columnswidth%'>\n"
                $answer .= "\t<td>\n"
                . "<select name=\"$myfname1\" id=\"answer$myfname1\" onchange=\"array_dual_dd_checkconditions(this.value, this.name, this.type,$dualgroup1,$checkconditionFunction);\">\n";

                if (!isset($_SESSION[$myfname1]) || $_SESSION[$myfname1] =='')
                {
                    $answer .= "\t<option value=\"\"".SELECTED.'>'.$clang->gT('Please choose...')."</option>\n";
                }

                foreach ($labels1 as $lrow1)
                {
                    $answer .= "\t<option value=\"".$lrow1['code'].'" ';
                    if (isset($_SESSION[$myfname1]) && $_SESSION[$myfname1] == $lrow1['code'])
                    {
                        $answer .= SELECTED;
                    }
                    $answer .= '>'.$lrow1['title']."</option>\n";
                }
                // If not mandatory and showanswer, show no ans
                if ($ia[6] != 'Y' && SHOW_NO_ANSWER == 1)
                {
                    $answer .= "\t<option value='' ";
                    if (!isset($_SESSION[$myfname1]) || $_SESSION[$myfname1] == '')
                    {
                        $answer .= SELECTED;
                    }
                    $answer .= ">".$clang->gT('No answer')."</option>\n";
                }
                $answer .= "</select>\n";

                // suffix
                if($ddsuffix != '')
                {
                    $answer .= "\t<td class=\"ddsuffix\">$ddsuffix</td>\n";
                }
                $answer .= "<input type=\"hidden\" name=\"java$myfname1\" id=\"java$myfname1\" value=\"";
                if (isset($_SESSION[$myfname1]))
                {
                    $answer .= $_SESSION[$myfname1];
                }
                $answer .= "\" />\n"
                . "\t</td>\n";
                $inputnames[]=$myfname1;

                $answer .= "</tr>\n";
				$answer .= "\t</tbody>\n";
            }
        } // End there are answers
        $answer .= "</table>\n";
    }
    else
    {
        $answer = "<p class='error'>".$clang->gT("Error: There are no answer options for this question and/or they don't exist in this language.")."</p>\n";
        $inputnames="";
    }
    return array($answer, $inputnames);
}

// Closing PHP tag intentionally left out - yes, it is okay
