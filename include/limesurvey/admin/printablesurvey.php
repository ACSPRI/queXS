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
 * $Id: printablesurvey.php 12418 2012-02-09 11:54:10Z mennodekker $
 */

//Ensure script is not run directly, avoid path disclosure
include_once("login_check.php");

//gett all settings for the printable survey
include_once(dirname(__FILE__)."/../config-defaults.php");

//we need this one for PDF export
include_once(dirname(__FILE__)."/classes/tcpdf/extensiontcpdf.php");

// TEMP function for debugging
function try_debug($line)
{
    global $debug;
    if($debug > 0)
    {
        return '<!-- printablesurvey.php: '.$line.' -->';
    }
}
$surveyid = $_GET['sid'];

//echo '<pre>'.print_r($_SESSION,true).'</pre>';
// PRESENT SURVEY DATAENTRY SCREEN
if(isset($_POST['printableexport']))
{
    $pdf = new PDF ($pdforientation,'mm','A4');
    $pdf->SetFont($pdfdefaultfont,'',$pdffontsize);
    $pdf->AddPage();
}
// Set the language of the survey, either from GET parameter of session var
if (isset($_GET['lang']))
{
    $_GET['lang'] = preg_replace("/[^a-zA-Z0-9-]/", "", $_GET['lang']);
    if ($_GET['lang']) $surveyprintlang = $_GET['lang'];
} else
{
    $surveyprintlang=GetbaseLanguageFromSurveyid($surveyid);
}

// Setting the selected language for printout
$clang = new limesurvey_lang($surveyprintlang);

$desquery = "SELECT * FROM ".db_table_name('surveys')." inner join ".db_table_name('surveys_languagesettings')." on (surveyls_survey_id=sid) WHERE sid={$surveyid} and surveyls_language=".$connect->qstr($surveyprintlang); //Getting data for this survey

$desrow = $connect->GetRow($desquery);
if ($desrow==false || count($desrow)==0)
{
    safe_die('Invalid survey ID');
}
    //echo '<pre>'.print_r($desrow,true).'</pre>';
    $template = $desrow['template'];
    $welcome = $desrow['surveyls_welcometext'];
    $end = $desrow['surveyls_endtext'];
    $surveyname = $desrow['surveyls_title'];
    $surveydesc = $desrow['surveyls_description'];
    $surveyactive = $desrow['active'];
    $surveytable = db_table_name("survey_".$desrow['sid']);
    $surveyexpirydate = $desrow['expires'];
    $surveystartdate = $desrow['startdate'];
    $surveyfaxto = $desrow['faxto'];
    $dateformattype = $desrow['surveyls_dateformat'];

if(isset($_POST['printableexport'])){$pdf->titleintopdf($surveyname,$surveydesc);}


$dformat=getDateFormatData($dateformattype);
$dformat=$dformat['phpdate'];

$expirytimestamp = strtotime($surveyexpirydate);
$expirytimeofday_h = date('H',$expirytimestamp);
$expirytimeofday_m = date('i',$expirytimestamp);

$surveyexpirydate = date($dformat,$expirytimestamp);

if(!empty($expirytimeofday_h) || !empty($expirytimeofday_m))
{
    $surveyexpirydate .= ' &ndash; '.$expirytimeofday_h.':'.$expirytimeofday_m;
};

//define('PRINT_TEMPLATE' , '/templates/print/' , true);
if(is_file($usertemplaterootdir.'/'.$template.'/print_survey.pstpl'))
{
	define('PRINT_TEMPLATE_DIR' , $usertemplaterootdir.'/'.$template.'/' , true);
	define('PRINT_TEMPLATE_URL' , $usertemplaterooturl.'/'.$template.'/' , true);
}
else
{
	define('PRINT_TEMPLATE_DIR' , $standardtemplaterootdir.'/default/' , true);
	define('PRINT_TEMPLATE_URL' , $standardtemplaterooturl.'/default/' , true);
}

LimeExpressionManager::StartSurvey($surveyid, 'survey',NULL,false,LEM_PRETTY_PRINT_ALL_SYNTAX);
$moveResult = LimeExpressionManager::NavigateForwards();


$degquery = "SELECT * FROM ".db_table_name("groups")." WHERE sid='{$surveyid}' AND language='{$surveyprintlang}' ORDER BY ".db_table_name("groups").".group_order";
$degresult = db_execute_assoc($degquery);

if (!isset($surveyfaxto) || !$surveyfaxto and isset($surveyfaxnumber))
{
    $surveyfaxto=$surveyfaxnumber; //Use system fax number if none is set in survey.
}

$pdf_form='';
if(isset($usepdfexport) && $usepdfexport == 1 && !in_array($surveyprintlang,$notsupportlanguages))
{
    $pdf_form = '
    <form action="'.$scriptname.'?action=showprintablesurvey&amp;sid='.$surveyid.'&amp;lang='.$surveyprintlang.'" method="post">
	    <input type="submit" value="'.$clang->gT('PDF Export').'"/>
	    <input type="hidden" name="checksessionbypost" value="'.htmlspecialchars($_SESSION['checksessionpost']).'"/>
	    <input type="hidden" name="printableexport" value="true"/>
    </form>
    ';
}

$headelements = getPrintableHeader();

//if $showsgqacode is enabled at config.php show table name for reference
if(isset($showsgqacode) && $showsgqacode == true)
{
	$surveyname =  $surveyname."<br />[".$clang->gT('Database')." ".$clang->gT('table').": $surveytable]";
}
else
{
	$surveyname = $surveyname;
}

$survey_output = array(
			 'SITENAME' => $sitename
,'SURVEYNAME' => $surveyname
,'SURVEYDESCRIPTION' => $surveydesc
,'WELCOME' => $welcome
,'END' => $end
,'THEREAREXQUESTIONS' => 0
,'SUBMIT_TEXT' => $clang->gT("Submit Your Survey.")
,'SUBMIT_BY' => $surveyexpirydate
,'THANKS' => $clang->gT("Thank you for completing this survey.")
,'PDF_FORM' => $pdf_form
,'HEADELEMENTS' => $headelements
,'TEMPLATEURL' => PRINT_TEMPLATE_URL
,'FAXTO' => $surveyfaxto
,'PRIVACY' => ''
,'GROUPS' => ''
);



$survey_output['FAX_TO'] ='';
if(!empty($surveyfaxto) && $surveyfaxto != '000-00000000') //If no fax number exists, don't display faxing information!
{
    $survey_output['FAX_TO'] = $clang->gT("Please fax your completed survey to:")." $surveyfaxto";
}


if ($surveystartdate!='')
{
    $survey_output['SUBMIT_BY'] = sprintf($clang->gT("Please submit by %s"), $surveyexpirydate);
}

/**
 * Output arrays:
 *	$survey_output  =       final vaiables for whole survey
 *		$survey_output['SITENAME'] =
 *		$survey_output['SURVEYNAME'] =
 *		$survey_output['SURVEY_DESCRIPTION'] =
 *		$survey_output['WELCOME'] =
 *		$survey_output['THEREAREXQUESTIONS'] =
 *		$survey_output['PDF_FORM'] =
 *		$survey_output['HEADELEMENTS'] =
 *		$survey_output['TEMPLATEURL'] =
 *		$survey_output['SUBMIT_TEXT'] =
 *		$survey_output['SUBMIT_BY'] =
 *		$survey_output['THANKS'] =
 *		$survey_output['FAX_TO'] =
 *		$survey_output['SURVEY'] = 	contains an array of all the group arrays
 *
 *	$groups[]       =       an array of all the groups output
 *		$group['GROUPNAME'] =
 *		$group['GROUPDESCRIPTION'] =
 *		$group['QUESTIONS'] = 	templated formatted content if $question is appended to this at the end of processing each question.
 *		$group['ODD_EVEN'] = 	class to differentiate alternate groups
 *		$group['SCENARIO'] =
 *
 *	$questions[]    =       contains an array of all the questions within a group
 *		$question['QUESTION_CODE'] = 		content of the question code field
 *		$question['QUESTION_TEXT'] = 		content of the question field
 *		$question['QUESTION_SCENARIO'] = 		if there are conditions on a question, list the conditions.
 *		$question['QUESTION_MANDATORY'] = 	translated 'mandatory' identifier
 *		$question['QUESTION_CLASS'] = 		classes to be added to wrapping question div
 *		$question['QUESTION_TYPE_HELP'] = 		instructions on how to complete the question
 *		$question['QUESTION_MAN_MESSAGE'] = 	(not sure if this is used) mandatory error
 *		$question['QUESTION_VALID_MESSAGE'] = 	(not sure if this is used) validation error
 *		$question['ANSWER'] =        		contains formatted HTML answer
 *		$question['QUESTIONHELP'] = 		content of the question help field.
 *
 */

function populate_template( $template , $input  , $line = '')
{
    global $rootdir, $debug;
    /**
     * A poor mans templating system.
     *
     * 	$template	template filename (path is privided by config.php)
     * 	$input		a key => value array containg all the stuff to be put into the template
     * 	$line	 	for debugging purposes only.
     *
     * Returns a formatted string containing template with
     * keywords replaced by variables.
     *
     * How:
     */
    $full_path = PRINT_TEMPLATE_DIR.'print_'.$template.'.pstpl';
    $full_constant = 'TEMPLATE'.$template.'.pstpl';
    if(!defined($full_constant))
    {
        if(is_file($full_path))
        {
            define( $full_constant , file_get_contents($full_path));

            $template_content = constant($full_constant);
            $test_empty = trim($template_content);
            if(empty($test_empty))
            {
                return "<!--\n\t$full_path\n\tThe template was empty so is useless.\n-->";
            }
        }
        else
        {
            define($full_constant , '');
            return "<!--\n\t$full_path is not a propper file or is missing.\n-->";
        }
    }
    else
    {
        $template_content = constant($full_constant);
        $test_empty = trim($template_content);
        if(empty($test_empty))
        {
            return "<!--\n\t$full_path\n\tThe template was empty so is useless.\n-->";
        }
    }

    if(is_array($input))
    {
        foreach($input as $key => $value)
        {
            $find[] = '{'.$key.'}';
            $replace[] = $value;
        }
        return str_replace( $find , $replace , $template_content );
    }
    else
    {
        if($debug > 0)
        {
            if(!empty($line))
            {
                $line =  'LINE '.$line.': ';
            }
            return '<!-- '.$line.'There was nothing to put into the template -->'."\n";
        }
    }
}


function input_type_image( $type , $title = '' , $x = 40 , $y = 1 , $line = '' )
{
    global $rooturl, $rootdir;

    if($type == 'other' or $type == 'othercomment')
    {
        $x = 1;
    }
    $tail = substr($x , -1 , 1);
    switch($tail)
    {
        case '%':
        case 'm':
        case 'x':	$x_ = $x;
        break;
        default:	$x_ = $x / 2;
    }

    if($y < 2)
    {
        $y_ = 2;
    }
    else
    {
        $y_ = $y * 2;
    }

    if(!empty($title))
    {
        $div_title = ' title="'.htmlspecialchars($title).'"';
    }
    else
    {
        $div_title = '';
    }
    switch($type)
    {
        case 'textarea':
        case 'text':	$style = ' style="width:'.$x_.'em; height:'.$y_.'em;"';
        break;
        default:	$style = '';
    }

    switch($type)
    {
        case 'radio':
        case 'checkbox':if(!defined('IMAGE_'.$type.'_SIZE'))
        {
            $image_dimensions = getimagesize(PRINT_TEMPLATE_DIR.'print_img_'.$type.'.png');
            // define('IMAGE_'.$type.'_SIZE' , ' width="'.$image_dimensions[0].'" height="'.$image_dimensions[1].'"');
            define('IMAGE_'.$type.'_SIZE' , ' width="14" height="14"');
        }
        $output = '<img src="'.PRINT_TEMPLATE_URL.'print_img_'.$type.'.png"'.constant('IMAGE_'.$type.'_SIZE').' alt="'.htmlspecialchars($title).'" class="input-'.$type.'" />';
        break;

        case 'rank':
        case 'other':
        case 'othercomment':
        case 'text':
        case 'textarea':$output = '<div class="input-'.$type.'"'.$style.$div_title.'>{NOTEMPTY}</div>';
        break;

        default:	$output = '';
    }
    return $output;
}

function star_replace($input)
{
    return preg_replace(
			 '/\*(.*)\*/U'
			 ,'<strong>\1</strong>'
			 ,$input
			 );
}

$total_questions = 0;
$mapquestionsNumbers=Array();
$answertext = '';   // otherwise can throw an error on line 1617

// =========================================================
// START doin the business:
$pdfoutput = '';
while ($degrow = $degresult->FetchRow())
{
    // ---------------------------------------------------
    // START doing groups

    $deqquery = "SELECT * FROM ".db_table_name("questions")." WHERE sid=$surveyid AND gid={$degrow['gid']} AND language='{$surveyprintlang}' AND parent_qid=0 AND TYPE<>'I' ORDER BY question_order";
    $deqresult = db_execute_assoc($deqquery);
    $deqrows = array(); //Create an empty array in case FetchRow does not return any rows
    while ($deqrow = $deqresult->FetchRow()) {$deqrows[] = $deqrow;} // Get table output into array

    // Perform a case insensitive natural sort on group name then question title of a multidimensional array
    usort($deqrows, 'GroupOrderThenQuestionOrder');

    if ($degrow['description'])
    {
        $group_desc = $degrow['description'];
    }
    else
    {
        $group_desc = '';
    }

    $group = array(
			 'GROUPNAME' => $degrow['group_name']
    ,'GROUPDESCRIPTION' => $group_desc
    ,'QUESTIONS' => '' // templated formatted content if $question is appended to this at the end of processing each question.
    );

    // A group can have only hidden questions. In that case you don't want to see the group's header/description either.
    $bGroupHasVisibleQuestions = false;

    if(isset($_POST['printableexport'])){$pdf->titleintopdf($degrow['group_name'],$degrow['description']);}

    $gid = $degrow['gid'];
    //Alternate bgcolor for different groups
    if (!isset($group['ODD_EVEN']) || $group['ODD_EVEN'] == ' g-row-even')
    {
        $group['ODD_EVEN'] = ' g-row-odd';}
        else
        {
            $group['ODD_EVEN'] = ' g-row-even';
        }

        //Loop through questions
        foreach ($deqrows as $deqrow)
        {
            // - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
            // START doing questions

            $qidattributes=getQuestionAttributes($deqrow['qid'],$deqrow['type']);
            if ($qidattributes['hidden']==1 && $deqrow['type'] != '*')
            {
                continue;
            }
            $bGroupHasVisibleQuestions = true;

            //GET ANY CONDITIONS THAT APPLY TO THIS QUESTION

            $printablesurveyoutput = '';
            $explanation = ''; //reset conditions explanation
            $s=0;

            $qinfo = LimeExpressionManager::GetQuestionStatus($deqrow['qid']);
            $relevance = trim($qinfo['info']['relevance']);
            $explanation = $qinfo['relEqn'];

            if (trim($relevance) != '' && trim($relevance) != '1')
            {
                $explanation = "<b>".$clang->gT('Only answer this question if the following conditions are met:')."</b>"
                ."<br/> ° ".$explanation;
            }
            else
            {
                $explanation = '';
            }

            ++$total_questions;


            //TIBO map question qid to their q number
            $mapquestionsNumbers[$deqrow['qid']]=$total_questions;
            //END OF GETTING CONDITIONS

            $qid = $deqrow['qid'];
            $fieldname = "$surveyid"."X"."$gid"."X"."$qid";

        	if(isset($showsgqacode) && $showsgqacode == true)
			{
				$deqrow['question'] = $deqrow['question']."<br />".$clang->gT("ID:")." $fieldname <br />".
									  $clang->gT("Question code:")." ".$deqrow['title'];
			}

            $question = array(
					 'QUESTION_NUMBER' => $total_questions	// content of the question code field
            ,'QUESTION_CODE' => $deqrow['title']
            ,'QUESTION_TEXT' => preg_replace('/(?:<br ?\/?>|<\/(?:p|h[1-6])>)$/is' , '' , tokenReplace($deqrow['question']))	// content of the question field
            ,'QUESTION_SCENARIO' => $explanation	// if there are conditions on a question, list the conditions.
            ,'QUESTION_MANDATORY' => ''		// translated 'mandatory' identifier
            ,'QUESTION_ID' => $deqrow['qid']    // id to be added to wrapping question div
            ,'QUESTION_CLASS' => question_class( $deqrow['type'])	// classes to be added to wrapping question div
            ,'QUESTION_TYPE_HELP' => $qinfo['validTip']   // ''		// instructions on how to complete the question // prettyValidTip is too verbose; assuming printable surveys will use static values
            ,'QUESTION_MAN_MESSAGE' => ''		// (not sure if this is used) mandatory error
            ,'QUESTION_VALID_MESSAGE' => ''		// (not sure if this is used) validation error
            ,'QUESTION_FILE_VALID_MESSAGE' => ''// (not sure if this is used) file validation error
            ,'QUESTIONHELP' => ''			// content of the question help field.
            ,'ANSWER' => ''				// contains formatted HTML answer
            );

            if($question['QUESTION_TYPE_HELP'] != "") {
                $question['QUESTION_TYPE_HELP'] .= "<br />\n";
            }


            if ($deqrow['mandatory'] == 'Y')
            {
                $question['QUESTION_MANDATORY'] = $clang->gT('*');
                $question['QUESTION_CLASS'] .= ' mandatory';
                $pdfoutput .= $clang->gT("*");
            }

            $pdfoutput ='';

            //DIFFERENT TYPES OF DATA FIELD HERE


            if(isset($_POST['printableexport'])){$pdf->intopdf($deqrow['title']." ".$deqrow['question']);}

            if ($deqrow['help'])
            {
                $hh = $deqrow['help'];
                $question['QUESTIONHELP'] = $hh;

                if(isset($_POST['printableexport'])){$pdf->helptextintopdf($hh);}
            }


            if ($qidattributes['page_break']!=0)
            {
                $question['QUESTION_CLASS'] .=' breakbefore ';
            }


            if (isset($qidattributes['maximum_chars']) && $qidattributes['maximum_chars']!='') {
                $question['QUESTION_CLASS'] ="max-chars-{$qidattributes['maximum_chars']} ".$question['QUESTION_CLASS'];
            }

            switch($deqrow['type'])
            {
                // ==================================================================
                case "5":	//5 POINT CHOICE
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT('Please choose *only one* of the following:');
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose *only one* of the following:"),"U");}
                    $pdfoutput ='';
                    $question['ANSWER'] .= "\n\t<ul>\n";
                    for ($i=1; $i<=5; $i++)
                    {
                        $pdfoutput .=" o ".$i." ";
                        //						$printablesurveyoutput .="\t\t\t<input type='checkbox' name='$fieldname' value='$i' readonly='readonly' />$i \n";
                        $question['ANSWER'] .="\t\t<li>\n\t\t\t".input_type_image('radio',$i)."\n\t\t\t$i ".addsgqacode("($i)")."\n\t\t</li>\n";
                    }
                    if(isset($_POST['printableexport'])){$pdf->intopdf($pdfoutput);}
                    $question['ANSWER'] .="\t</ul>\n";

                    break;

                    // ==================================================================
                case "D":  //DATE
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT('Please enter a date:');
                    $question['ANSWER'] .= "\t".input_type_image('text',$question['QUESTION_TYPE_HELP'],30,1);
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please enter a date:")." ___________");}

                    break;

                    // ==================================================================
                case "G":  //GENDER
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose *only one* of the following:");

                    $question['ANSWER'] .= "\n\t<ul>\n";
                    $question['ANSWER'] .= "\t\t<li>\n\t\t\t".input_type_image('radio',$clang->gT("Female"))."\n\t\t\t".$clang->gT("Female")." ".addsgqacode("(F)")."\n\t\t</li>\n";
                    $question['ANSWER'] .= "\t\t<li>\n\t\t\t".input_type_image('radio',$clang->gT("Male"))."\n\t\t\t".$clang->gT("Male")." ".addsgqacode("(M)")."\n\t\t</li>\n";
                    $question['ANSWER'] .= "\t</ul>\n";

                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose *only one* of the following:"));}
                    if(isset($_POST['printableexport'])){$pdf->intopdf(" o ".$clang->gT("Female")." | o ".$clang->gT("Male"));}

                    break;

                    // ==================================================================
                case "L": //LIST drop-down/radio-button list

                    // ==================================================================
                case "!": //List - dropdown
                    if (isset($qidattributes['display_columns']) && trim($qidattributes['display_columns'])!='')
                    {
                        $dcols=$qidattributes['display_columns'];
                    }
                    else
                    {
                        $dcols=0;
                    }
                    if (isset($qidattributes['category_separator']) && trim($qidattributes['category_separator'])!='') {
                        $optCategorySeparator = $qidattributes['category_separator'];
                    }
                    else
                    {
                        unset($optCategorySeparator);
                    }

                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose *only one* of the following:");
                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);

                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose *only one* of the following:"));}
                    $deaquery = "SELECT * FROM ".db_table_name("answers")." WHERE qid={$deqrow['qid']} AND language='{$surveyprintlang}' ORDER BY sortorder, answer";
                    $dearesult = db_execute_assoc($deaquery);
                    $deacount=$dearesult->RecordCount();
                    if ($deqrow['other'] == "Y") {$deacount++;}

                    $wrapper = setup_columns(0, $deacount);

                    $question['ANSWER'] = $wrapper['whole-start'];

                    $rowcounter = 0;
                    $colcounter = 1;

                    while ($dearow = $dearesult->FetchRow())
                    {
                        if (isset($optCategorySeparator))
                        {
                            list ($category, $answer) = explode($optCategorySeparator,$dearow['answer']);
                            if ($category != '')
                            {
                                $dearow['answer'] = "($category) $answer ".addsgqacode("(".$dearow['code'].")");
                            }
                            else
                            {
                                $dearow['answer'] = $answer.addsgqacode(" (".$dearow['code'].")");
                            }
                            $question['ANSWER'] .= "\t".$wrapper['item-start']."\t\t".input_type_image('radio' , $dearow['answer'])."\n\t\t\t".$dearow['answer']."\n".$wrapper['item-end'];
                        }
                        else
                        {
                        	$question['ANSWER'] .= "\t".$wrapper['item-start']."\t\t".input_type_image('radio' , $dearow['answer'])."\n\t\t\t".$dearow['answer'].addsgqacode(" (".$dearow['code'].")")."\n".$wrapper['item-end'];
                        }

                        if(isset($_POST['printableexport'])){$pdf->intopdf(" o ".$dearow['answer']);}

                        ++$rowcounter;
                        if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
                        {
                            if($colcounter == $wrapper['cols'] - 1)
                            {
                                $question['ANSWER'] .= $wrapper['col-devide-last'];
                            }
                            else
                            {
                                $question['ANSWER']  .= $wrapper['col-devide'];
                            }
                            $rowcounter = 0;
                            ++$colcounter;
                        }
                    }
                    if ($deqrow['other'] == 'Y')
                    {
                        if(trim($qidattributes["other_replace_text"])=='')
                        {$qidattributes["other_replace_text"]="Other";}
                        //					$printablesurveyoutput .="\t".$wrapper['item-start']."\t\t".input_type_image('radio' , $clang->gT("Other"))."\n\t\t\t".$clang->gT("Other")."\n\t\t\t<input type='text' size='30' readonly='readonly' />\n".$wrapper['item-end'];
                        $question['ANSWER']  .= $wrapper['item-start-other'].input_type_image('radio',$clang->gT($qidattributes["other_replace_text"])).' '.$clang->gT($qidattributes["other_replace_text"]).addsgqacode(" (-oth-)")."\n\t\t\t".input_type_image('other').addsgqacode(" (".$deqrow['sid']."X".$deqrow['gid']."X".$deqrow['qid']."other)")."\n".$wrapper['item-end'];
                        if(isset($_POST['printableexport'])){$pdf->intopdf(" o ".$clang->gT($qidattributes["other_replace_text"]).": ________");}
                    }
                    $question['ANSWER'] .= $wrapper['whole-end'];
                    //Let's break the presentation into columns.
                    break;

                    // ==================================================================
                case "O":  //LIST WITH COMMENT
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose *only one* of the following:");
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose *only one* of the following:"),"U");}
                    $deaquery = "SELECT * FROM ".db_table_name("answers")." WHERE qid={$deqrow['qid']} AND language='{$surveyprintlang}' ORDER BY sortorder, answer ";
                    $dearesult = db_execute_assoc($deaquery);
                    $question['ANSWER'] = "\t<ul>\n";
                    while ($dearow = $dearesult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t<li>\n\t\t\t".input_type_image('radio',$dearow['answer'])."\n\t\t\t".$dearow['answer'].addsgqacode(" (".$dearow['code'].")")."\n\t\t</li>\n";
                        if(isset($_POST['printableexport'])){$pdf->intopdf($dearow['answer']);}
                    }
                    $question['ANSWER'] .= "\t</ul>\n";

                    $question['ANSWER'] .= "\t<p class=\"comment\">\n\t\t".$clang->gT("Make a comment on your choice here:")."\n";
                    if(isset($_POST['printableexport'])){$pdf->intopdf("Make a comment on your choice here:");}
                    $question['ANSWER'] .= "\t\t".input_type_image('textarea',$clang->gT("Make a comment on your choice here:"),50,8).addsgqacode(" (".$deqrow['sid']."X".$deqrow['gid']."X".$deqrow['qid']."comment)")."\n\t</p>\n";

                    for($i=0;$i<9;$i++)
                    {
                        if(isset($_POST['printableexport'])){$pdf->intopdf("____________________");}
                    }
                    break;

                    // ==================================================================
                case "R":  //RANKING Type Question
                    $reaquery = "SELECT * FROM ".db_table_name("answers")." WHERE qid={$deqrow['qid']} AND language='{$surveyprintlang}' ORDER BY sortorder, answer";
                    $rearesult = db_execute_assoc($reaquery) or safe_die ("Couldn't get ranked answers<br />".$connect->ErrorMsg());
                    $reacount = $rearesult->RecordCount();
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please number each box in order of preference from 1 to")." $reacount";
                	$question['QUESTION_TYPE_HELP'] .= min_max_answers_help($qidattributes, $surveyprintlang, $surveyid);
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please number each box in order of preference from 1 to ").$reacount,"U");}
                    $question['ANSWER'] = "\n<ul>\n";
                    while ($rearow = $rearesult->FetchRow())
                    {
                        $question['ANSWER'] .="\t<li>\n\t".input_type_image('rank','',4,1)."\n\t\t&nbsp;".$rearow['answer'].addsgqacode(" (".$fieldname.$rearow['code'].")")."\n\t</li>\n";
                        if(isset($_POST['printableexport'])){$pdf->intopdf("__ ".$rearow['answer']);}
                    }
                    $question['ANSWER'] .= "\n</ul>\n";
                    break;

                    // ==================================================================
                case "M":  //Multiple choice (Quite tricky really!)

                    if (trim($qidattributes['display_columns'])!='')
                    {
                        $dcols=$qidattributes['display_columns'];
                    }
                    else
                    {
                        $dcols=0;
                    }
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose *all* that apply:");
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose *all* that apply:"),"U");}

                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']} AND language='{$surveyprintlang}' ORDER BY question_order";
                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);
                    $mearesult = db_execute_assoc($meaquery);
                    $meacount = $mearesult->RecordCount();
                    if ($deqrow['other'] == 'Y') {$meacount++;}

                    $wrapper = setup_columns($dcols, $meacount);
                    $question['ANSWER'] = $wrapper['whole-start'];

                    $rowcounter = 0;
                    $colcounter = 1;

                    while ($mearow = $mearesult->FetchRow())
                    {
                        $question['ANSWER'] .= $wrapper['item-start'].input_type_image('checkbox',$mearow['question'])."\n\t\t".$mearow['question'].addsgqacode(" (".$fieldname.$mearow['title'].") ").$wrapper['item-end'];
                        if(isset($_POST['printableexport'])){$pdf->intopdf(" o ".$mearow['answer']);}
                        //						$upto++;

                        ++$rowcounter;
                        if ($rowcounter == $wrapper['maxrows'] && $colcounter < $wrapper['cols'])
                        {
                            if($colcounter == $wrapper['cols'] - 1)
                            {
                                $question['ANSWER'] .= $wrapper['col-devide-last'];
                            }
                            else
                            {
                                $question['ANSWER'] .= $wrapper['col-devide'];
                            }
                            $rowcounter = 0;
                            ++$colcounter;
                        }
                    }
                    if ($deqrow['other'] == "Y")
                    {
                        if (trim($qidattributes['other_replace_text'])=='')
                        {
                            $qidattributes["other_replace_text"]="Other";
                        }
                        $question['ANSWER'] .= $wrapper['item-start-other'].input_type_image('checkbox',$mearow['answer']).$clang->gT($qidattributes["other_replace_text"]).":\n\t\t".input_type_image('other').addsgqacode(" (".$fieldname."other) ").$wrapper['item-end'];
                        if(isset($_POST['printableexport'])){$pdf->intopdf(" o ".$clang->gT($qidattributes["other_replace_text"]).": ________");}
                    }
                    $question['ANSWER'] .= $wrapper['whole-end'];
                    //				}
                    break;

                     // ==================================================================
                case "P":  //Multiple choice with comments
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose *all* that apply and provide a comment:");
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose all that apply and provide a comment:"),"U");}
                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND language='{$surveyprintlang}' ORDER BY question_order";
                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);
                    $mearesult = db_execute_assoc($meaquery);
                    //				$printablesurveyoutput .="\t\t\t<u>".$clang->gT("Please choose all that apply and provide a comment:")."</u><br />\n";
                    $pdfoutput=array();
                    $j=0;
                    $longest_string = 0;
                    while ($mearow = $mearesult->FetchRow())
                    {
                        $longest_string = longest_string($mearow['question'] , $longest_string );
                        $question['ANSWER'] .= "\t<li><span>\n\t\t".input_type_image('checkbox',$mearow['question']).$mearow['question'].addsgqacode(" (".$fieldname.$mearow['title'].") ")."</span>\n\t\t".input_type_image('text','comment box',60).addsgqacode(" (".$fieldname.$mearow['title']."comment) ")."\n\t</li>\n";
                        $pdfoutput[$j]=array(" o ".$mearow['title']," __________");
                        $j++;
                    }
                    if ($deqrow['other'] == "Y")
                    {
                        $question['ANSWER'] .= "\t<li class=\"other\">\n\t\t<div class=\"other-replacetext\">".$clang->gT('Other:').input_type_image('other','',1)."</div>".input_type_image('othercomment','comment box',50).addsgqacode(" (".$fieldname."other) ")."\n\t</li>\n";
                        // lemeur: PDFOUTPUT HAS NOT BEEN IMPLEMENTED for these fields
                        // not sure who did implement this.
                        $pdfoutput[$j][0]=array(" o "."Other"," __________");
                        $pdfoutput[$j][1]=array(" o "."OtherComment"," __________");
                        $j++;
                    }

                    $question['ANSWER'] = "\n<ul>\n".$question['ANSWER']."</ul>\n";
                    if(isset($_POST['printableexport'])){$pdf->tableintopdf($pdfoutput);}
                    break;


                    // ==================================================================
                case "Q":  //MULTIPLE SHORT TEXT
                    $width=60;

                    // ==================================================================
                case "K":  //MULTIPLE NUMERICAL
                    $width=(isset($width))?$width:16;
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please write your answer(s) here:"),"U");}

                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please write your answer(s) here:");

                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND language='{$surveyprintlang}' ORDER BY question_order";
                    $mearesult = db_execute_assoc($meaquery);
                    $longest_string = 0;
                    while ($mearow = $mearesult->FetchRow())
                    {
                        $longest_string = longest_string($mearow['question'] , $longest_string );
                        if (isset($qidattributes['slider_layout']) && $qidattributes['slider_layout']==1)
                        {
                          $mearow['question']=explode(':',$mearow['question']);
                          $mearow['question']=$mearow['question'][0];
                        }
                        $question['ANSWER'] .=  "\t<li>\n\t\t<span>".$mearow['question']."</span>\n\t\t".input_type_image('text',$mearow['question'],$width).addsgqacode(" (".$fieldname.$mearow['title'].") ")."\n\t</li>\n";
                        if(isset($_POST['printableexport'])){$pdf->intopdf($mearow['question'].": ____________________");}
                    }
                    $question['ANSWER'] =  "\n<ul>\n".$question['ANSWER']."</ul>\n";
                    break;


                    // ==================================================================
                case "S":  //SHORT TEXT
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please write your answer here:");
                    $question['ANSWER'] = input_type_image('text',$question['QUESTION_TYPE_HELP'], 50);
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please write your answer here:"),"U");}
                    if(isset($_POST['printableexport'])){$pdf->intopdf("____________________");}
                    break;


                    // ==================================================================
                case "T":  //LONG TEXT
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please write your answer here:");
                    $question['ANSWER'] = input_type_image('textarea',$question['QUESTION_TYPE_HELP'], '100%' , 8);

                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please write your answer here:"),"U");}
                    for($i=0;$i<9;$i++)
                    {
                        if(isset($_POST['printableexport'])){$pdf->intopdf("____________________");}
                    }
                    break;


                    // ==================================================================
                case "U":  //HUGE TEXT
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please write your answer here:");
                    $question['ANSWER'] = input_type_image('textarea',$question['QUESTION_TYPE_HELP'], '100%' , 30);

                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please write your answer here:"),"U");}
                    for($i=0;$i<20;$i++)
                    {
                        if(isset($_POST['printableexport'])){$pdf->intopdf("____________________");}
                    }
                    break;


                    // ==================================================================
                case "N":  //NUMERICAL
                	$prefix="";
                	$suffix="";
                	if($qidattributes['prefix'] != "") {
                		$prefix=$qidattributes['prefix'];
                	}
                	if($qidattributes['suffix'] != "") {
                		$suffix=$qidattributes['suffix'];
                	}
                	$question['QUESTION_TYPE_HELP'] .= $clang->gT("Please write your answer here:");
                    $question['ANSWER'] = "<ul>\n\t<li>\n\t\t<span>$prefix</span>\n\t\t".input_type_image('text',$question['QUESTION_TYPE_HELP'],20)."\n\t\t<span>$suffix</span>\n\t\t</li>\n\t</ul>";

                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please write your answer here:"),"U");}
                    if(isset($_POST['printableexport'])){$pdf->intopdf("____________________");}

                    break;

                    // ==================================================================
                case "Y":  //YES/NO
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose *only one* of the following:");
                    $question['ANSWER'] = "\n<ul>\n\t<li>\n\t\t".input_type_image('radio',$clang->gT('Yes'))."\n\t\t".$clang->gT('Yes').addsgqacode(" (Y)")."\n\t</li>\n";
                    $question['ANSWER'] .= "\n\t<li>\n\t\t".input_type_image('radio',$clang->gT('No'))."\n\t\t".$clang->gT('No').addsgqacode(" (N)")."\n\t</li>\n</ul>\n";

                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose *only one* of the following:"),"U");}
                    if(isset($_POST['printableexport'])){$pdf->intopdf(" o ".$clang->gT("Yes"));}
                    if(isset($_POST['printableexport'])){$pdf->intopdf(" o ".$clang->gT("No"));}
                    break;


                    // ==================================================================
                case "A":  //ARRAY (5 POINT CHOICE)
                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']} AND language='{$surveyprintlang}'  ORDER BY question_order";
                    $mearesult = db_execute_assoc($meaquery);
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose the appropriate response for each item:");
                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);

                    $question['ANSWER'] = "
<table>
	<thead>
		<tr>
			<td>&nbsp;</td>
			<th>1".addsgqacode(" (1)")."</th>
			<th>2".addsgqacode(" (2)")."</th>
			<th>3".addsgqacode(" (3)")."</th>
			<th>4".addsgqacode(" (4)")."</th>
			<th>5".addsgqacode(" (5)")."</th>
		</tr>
	</thead>
	<tbody>";

                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose the appropriate response for each item:"),"U");}
                    $pdfoutput = array();
                    $j=0;
                    $rowclass = 'array1';
                    while ($mearow = $mearesult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t<tr class=\"$rowclass\">\n";
                        $rowclass = alternation($rowclass,'row');

                        //semantic differential question type?
                        if (strpos($mearow['question'],'|'))
                        {
                        	$answertext = substr($mearow['question'],0, strpos($mearow['question'],'|')).addsgqacode(" (".$fieldname.$mearow['title'].")")." ";
                        }
                        else
                        {
                        	$answertext=$mearow['question'].addsgqacode(" (".$fieldname.$mearow['title'].")");
                        }
                        $question['ANSWER'] .= "\t\t\t<th class=\"answertext\">$answertext</th>\n";

                        $pdfoutput[$j][0]=$answertext;
                        for ($i=1; $i<=5; $i++)
                        {
                            $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio',$i)."</td>\n";
                            $pdfoutput[$j][$i]=" o ".$i;
                        }

                        $answertext .= $mearow['question'];

                        //semantic differential question type?
                        if (strpos($mearow['question'],'|'))
                        {
                            $answertext2 = substr($mearow['question'],strpos($mearow['question'],'|')+1);
                            $question['ANSWER'] .= "\t\t\t<th class=\"answertextright\">$answertext2</td>\n";
                        }
                        $question['ANSWER'] .= "\t\t</tr>\n";
                        $j++;
                    }
                    $question['ANSWER'] .= "\t</tbody>\n</table>\n";
                    if(isset($_POST['printableexport'])){$pdf->tableintopdf($pdfoutput);}
                    break;

                    // ==================================================================
                case "B":  //ARRAY (10 POINT CHOICE)
                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND language='{$surveyprintlang}' ORDER BY question_order";

                    $mearesult = db_execute_assoc($meaquery);
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose the appropriate response for each item:");
                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);

                    $question['ANSWER'] .= "\n<table>\n\t<thead>\n\t\t<tr>\n\t\t\t<td>&nbsp;</td>\n";
                    for ($i=1; $i<=10; $i++)
                    {
                        $question['ANSWER'] .= "\t\t\t<th>$i".addsgqacode(" ($i)")."</th>\n";
                    }
                    $question['ANSWER'] .= "\t</thead>\n\n\t<tbody>\n";
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose the appropriate response for each item:"),"U");}
                    $pdfoutput=array();
                    $j=0;
                    $rowclass = 'array1';
                    while ($mearow = $mearesult->FetchRow())
                    {

                        $question['ANSWER'] .= "\t\t<tr class=\"$rowclass\">\n\t\t\t<th class=\"answertext\">{$mearow['question']}".addsgqacode(" (".$fieldname.$mearow['title'].")")."</th>\n";
                        $rowclass = alternation($rowclass,'row');

                        $pdfoutput[$j][0]=$mearow['question'];
                        for ($i=1; $i<=10; $i++)
                        {
                            $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio',$i)."</td>\n";
                            $pdfoutput[$j][$i]=" o ".$i;
                        }
                        $question['ANSWER'] .= "\t\t</tr>\n";
                        $j++;
                    }
                    $question['ANSWER'] .= "\t</tbody>\n</table>\n";
                    if(isset($_POST['printableexport'])){$pdf->tableintopdf($pdfoutput);}
                    break;

                    // ==================================================================
                case "C":  //ARRAY (YES/UNCERTAIN/NO)
                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND language='{$surveyprintlang}' ORDER BY question_order";
                    $mearesult = db_execute_assoc($meaquery);
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose the appropriate response for each item:");
                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);

                    $question['ANSWER'] = '
<table>
	<thead>
		<tr>
			<td>&nbsp;</td>
			<th>'.$clang->gT("Yes").addsgqacode(" (Y)").'</th>
			<th>'.$clang->gT("Uncertain").addsgqacode(" (U)").'</th>
			<th>'.$clang->gT("No").addsgqacode(" (N)").'</th>
		</tr>
	</thead>
	<tbody>
';
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose the appropriate response for each item:"),"U");}
                    $pdfoutput = array();
                    $j=0;

                    $rowclass = 'array1';

                    while ($mearow = $mearesult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t<tr class=\"$rowclass\">\n";
                        $question['ANSWER'] .= "\t\t\t<th class=\"answertext\">{$mearow['question']}".addsgqacode(" (".$fieldname.$mearow['title'].")")."</th>\n";
                        $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio',$clang->gT("Yes"))."</td>\n";
                        $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio',$clang->gT("Uncertain"))."</td>\n";
                        $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio',$clang->gT("No"))."</td>\n";
                        $question['ANSWER'] .= "\t\t</tr>\n";

                        $pdfoutput[$j]=array($mearow['question']," o ".$clang->gT("Yes")," o ".$clang->gT("Uncertain")," o ".$clang->gT("No"));
                        $j++;
                        $rowclass = alternation($rowclass,'row');
                    }
                    $question['ANSWER'] .= "\t</tbody>\n</table>\n";
                    if(isset($_POST['printableexport'])){$pdf->tableintopdf($pdfoutput);}
                    break;

                case "E":  //ARRAY (Increase/Same/Decrease)
                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']} AND language='{$surveyprintlang}'  ORDER BY question_order";
                    $mearesult = db_execute_assoc($meaquery);
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose the appropriate response for each item:");
                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);

                    $question['ANSWER'] = '
<table>
	<thead>
		<tr>
			<td>&nbsp;</td>
			<th>'.$clang->gT("Increase").addsgqacode(" (I)").'</th>
			<th>'.$clang->gT("Same").addsgqacode(" (S)").'</th>
			<th>'.$clang->gT("Decrease").addsgqacode(" (D)").'</th>
		</tr>
	</thead>
	<tbody>
';
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose the appropriate response for each item:"),"U");}
                    $pdfoutput = array();
                    $j=0;
                    $rowclass = 'array1';

                    while ($mearow = $mearesult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t<tr class=\"$rowclass\">\n";
                        $question['ANSWER'] .= "\t\t\t<th class=\"answertext\">{$mearow['question']}".addsgqacode(" (".$fieldname.$mearow['title'].")")."</th>\n";
                        $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio',$clang->gT("Increase"))."</td>\n";
                        $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio',$clang->gT("Same"))."</td>\n";
                        $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio',$clang->gT("Decrease"))."</td>\n";
                        $question['ANSWER'] .= "\t\t</tr>\n";
                        $pdfoutput[$j]=array($mearow['question'].":"," o ".$clang->gT("Increase")," o ".$clang->gT("Same")," o ".$clang->gT("Decrease"));
                        $j++;
                        $rowclass = alternation($rowclass,'row');
                    }
                    $question['ANSWER'] .= "\t</tbody>\n</table>\n";
                    if(isset($_POST['printableexport'])){$pdf->tableintopdf($pdfoutput);}
                    break;

                    // ==================================================================
                case ":": //ARRAY (Multi Flexible) (Numbers)
                    $headstyle="style='padding-left: 20px; padding-right: 7px'";
                    if (trim($qidattributes['multiflexible_max'])!='' && trim($qidattributes['multiflexible_min']) =='') {
                        $maxvalue=$qidattributes['multiflexible_max'];
                        $minvalue=1;
                    }
                    if (trim($qidattributes['multiflexible_min'])!='' && trim($qidattributes['multiflexible_max']) =='') {
                        $minvalue=$qidattributes['multiflexible_min'];
                        $maxvalue=$qidattributes['multiflexible_min'] + 10;
                    }
                    if (trim($qidattributes['multiflexible_min'])=='' && trim($qidattributes['multiflexible_max']) =='') {
                        $minvalue=1;
                        $maxvalue=10;
                    }
                    if (trim($qidattributes['multiflexible_min']) !='' && trim($qidattributes['multiflexible_max']) !='') {
                        if($qidattributes['multiflexible_min'] < $qidattributes['multiflexible_max']){
                            $minvalue=$qidattributes['multiflexible_min'];
                            $maxvalue=$qidattributes['multiflexible_max'];
                        }
                    }

                    if (trim($qidattributes['multiflexible_step'])!='') {
                        $stepvalue=$qidattributes['multiflexible_step'];
                    }
                    else
                    {
                        $stepvalue=1;
                    }
                    if ($qidattributes['multiflexible_checkbox']!=0) {
                        $checkboxlayout=true;
                    } else {
                        $checkboxlayout=false;
                    }
                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND scale_id=0 AND language='{$surveyprintlang}' ORDER BY question_order";
                    $mearesult = db_execute_assoc($meaquery);

                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);

                    $question['ANSWER'] .= "\n<table>\n\t<thead>\n\t\t<tr>\n\t\t\t<td>&nbsp;</td>\n";
                    $fquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND scale_id=1 AND language='{$surveyprintlang}' ORDER BY question_order";
                    $fresult = db_execute_assoc($fquery);

                    $fcount = $fresult->RecordCount();
                    $fwidth = "120";
                    $i=0;
                    $pdfoutput = array();
                    $pdfoutput[0][0]=' ';

                    //array to temporary store X axis question codes
                    $xaxisarray = array();
                    while ($frow = $fresult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t\t<th>{$frow['question']}</th>\n";
                        $i++;
                        $pdfoutput[0][$i]=$frow['question'];

                        //add current question code
                        $xaxisarray[$i] = $frow['title'];
                    }
                    $question['ANSWER'] .= "\t\t</tr>\n\t</thead>\n\n\t<tbody>\n";
                    $a=1; //Counter for pdfoutput
                    $rowclass = 'array1';

                    while ($mearow = $mearesult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t<tr class=\"$rowclass\">\n";
                        $rowclass = alternation($rowclass,'row');

                        $answertext=$mearow['question'];
                        if (strpos($answertext,'|')) {$answertext=substr($answertext,0, strpos($answertext,'|'));}
                        $question['ANSWER'] .= "\t\t\t\t\t<th class=\"answertext\">$answertext</th>\n";
                        //$printablesurveyoutput .="\t\t\t\t\t<td>";
                        $pdfoutput[$a][0]=$answertext;
                        for ($i=1; $i<=$fcount; $i++)
                        {

                            $question['ANSWER'] .= "\t\t\t<td>\n";
                            if ($checkboxlayout === false)
                            {
                                $question['ANSWER'] .= "\t\t\t\t".input_type_image('text','',4).addsgqacode(" (".$fieldname.$mearow['title']."_".$xaxisarray[$i].") ")."\n";
                                $pdfoutput[$a][$i]="__";
                            }
                            else
                            {
                                $question['ANSWER'] .= "\t\t\t\t".input_type_image('checkbox').addsgqacode(" (".$fieldname.$mearow['title']."_".$xaxisarray[$i].") ")."\n";
                                $pdfoutput[$a][$i]="o";
                            }
                            $question['ANSWER'] .= "\t\t\t</td>\n";
                        }
                        $answertext=$mearow['question'];
                        if (strpos($answertext,'|'))
                        {
                            $answertext=substr($answertext,strpos($answertext,'|')+1);
                            $question['ANSWER'] .= "\t\t\t<th class=\"answertextright\">$answertext</th>\n";
                            //$pdfoutput[$a][$i]=$answertext;
                        }
                        $question['ANSWER'] .= "\t\t</tr>\n";
                        $a++;
                    }
                    $question['ANSWER'] .= "\t</tbody>\n</table>\n";
                    if(isset($_POST['printableexport'])){$pdf->tableintopdf($pdfoutput);}
                    break;

                    // ==================================================================
                case ";": //ARRAY (Multi Flexible) (text)
                    $headstyle="style='padding-left: 20px; padding-right: 7px'";
                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND scale_id=0 AND language='{$surveyprintlang}' ORDER BY question_order";
                    $mearesult = db_execute_assoc($meaquery);

                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);

                    $question['ANSWER'] .= "\n<table>\n\t<thead>\n\t\t<tr>\n\t\t\t<td>&nbsp;</td>\n";
                    $fquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND scale_id=1 AND language='{$surveyprintlang}' ORDER BY question_order";
                    $fresult = db_execute_assoc($fquery);
                    $fcount = $fresult->RecordCount();
                    $fwidth = "120";
                    $i=0;
                    $pdfoutput=array();
                    $pdfoutput[0][0]='';

                    //array to temporary store X axis question codes
                    $xaxisarray = array();
                    while ($frow = $fresult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t\t<th>{$frow['question']}</th>\n";
                        $i++;
                        $pdfoutput[0][$i]=$frow['question'];

                        //add current question code
                        $xaxisarray[$i] = $frow['title'];
                    }
                    $question['ANSWER'] .= "\t\t</tr>\n\t</thead>\n\n<tbody>\n";
                    $a=1;
                    $rowclass = 'array1';

                    while ($mearow = $mearesult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t<tr class=\"$rowclass\">\n";
                        $rowclass = alternation($rowclass,'row');
                        $answertext=$mearow['question'];
                        if (strpos($answertext,'|')) {$answertext=substr($answertext,0, strpos($answertext,'|'));}
                        $question['ANSWER'] .= "\t\t\t<th class=\"answertext\">$answertext</th>\n";
                        $pdfoutput[$a][0]=$answertext;
                        //$printablesurveyoutput .="\t\t\t\t\t<td>";
                        for ($i=1; $i<=$fcount; $i++)
                        {
                            $question['ANSWER'] .= "\t\t\t<td>\n";
                            $question['ANSWER'] .= "\t\t\t\t".input_type_image('text','',23).addsgqacode(" (".$fieldname.$mearow['title']."_".$xaxisarray[$i].") ")."\n";
                            $question['ANSWER'] .= "\t\t\t</td>\n";
                            $pdfoutput[$a][$i]="_____________";
                        }
                        $answertext=$mearow['question'];
                        if (strpos($answertext,'|'))
                        {
                            $answertext=substr($answertext,strpos($answertext,'|')+1);
                            $question['ANSWER'] .= "\t\t\t\t<th class=\"answertextright\">$answertext</th>\n";
                        }
                        $question['ANSWER'] .= "\t\t</tr>\n";
                        $a++;
                    }
                    $question['ANSWER'] .= "\t</tbody>\n</table>\n";
                    if(isset($_POST['printableexport'])){$pdf->tableintopdf($pdfoutput);}
                    break;

                    // ==================================================================
                case "F": //ARRAY (Flexible Labels)

                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND language='{$surveyprintlang}' ORDER BY question_order";
                    $mearesult = db_execute_assoc($meaquery);

                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose the appropriate response for each item:");
                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);

                    $fquery = "SELECT * FROM ".db_table_name("answers")." WHERE scale_id=0 and qid='{$deqrow['qid']}'  AND language='{$surveyprintlang}' ORDER BY sortorder, code";
                    $fresult = db_execute_assoc($fquery);
                    $fcount = $fresult->RecordCount();
                    $fwidth = "120";
                    $i=1;
                    $pdfoutput = array();
                    $pdfoutput[0][0]='';
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose the appropriate response for each item:"),"U");}
                    $column_headings = array();
                    while ($frow = $fresult->FetchRow())
                    {
                        $column_headings[] = $frow['answer'].addsgqacode(" (".$frow['code'].")");
                    }
                    if (trim($qidattributes['answer_width'])!='')
                    {
                        $iAnswerWidth=100-$qidattributes['answer_width'];
                    }
                    else
                    {
                        $iAnswerWidth=80;
                    }
                    if (count($column_headings)>0)
                    {
                        $col_width = round($iAnswerWidth / count($column_headings));

                    }
                    else
                    {
                        $heading='';
                    }
                    $question['ANSWER'] .= "\n<table>\n\t<thead>\n\t\t<tr>\n";
                    $question['ANSWER'] .= "\t\t\t<td>&nbsp;</td>\n";
                    foreach($column_headings as $heading)
                    {
                        $question['ANSWER'] .= "\t\t\t<th style=\"width:$col_width%;\">$heading</th>\n";
                    }
                    $pdfoutput[0][$i] = $heading;
                    $i++;
                    $question['ANSWER'] .= "\t\t</tr>\n\t</thead>\n\n\t<tbody>\n";
                    $counter = 1;
                    $rowclass = 'array1';

                    while ($mearow = $mearesult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t<tr class=\"$rowclass\">\n";
                        $rowclass = alternation($rowclass,'row');
                        if (trim($answertext)=='') $answertext='&nbsp;';

                        //semantic differential question type?
                        if (strpos($mearow['question'],'|'))
                        {
                        	$answertext = substr($mearow['question'],0, strpos($mearow['question'],'|')).addsgqacode(" (".$fieldname.$mearow['title'].")")." ";
                        }
                        else
                        {
                        	$answertext=$mearow['question'].addsgqacode(" (".$fieldname.$mearow['title'].")");
                        }

                        if (trim($qidattributes['answer_width'])!='')
                        {
                            $sInsertStyle=' style="width:'.$qidattributes['answer_width'].'%" ';
                        }
                        else
                        {
                            $sInsertStyle='';
                        }
                        $question['ANSWER'] .= "\t\t\t<th $sInsertStyle class=\"answertext\">$answertext</th>\n";

                        $pdfoutput[$counter][0]=$answertext;
                        for ($i=1; $i<=$fcount; $i++)
                        {
                            $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio')."</td>\n";
                            $pdfoutput[$counter][$i] = "o";

                        }
                        $counter++;

                        $answertext=$mearow['question'];

                        //semantic differential question type?
                        if (strpos($mearow['question'],'|'))
                        {
                            $answertext2=substr($mearow['question'],strpos($mearow['question'],'|')+1);
                            $question['ANSWER'] .= "\t\t\t<th class=\"answertextright\">$answertext2</th>\n";
                        }
                        $question['ANSWER'] .= "\t\t</tr>\n";
                    }
                    $question['ANSWER'] .= "\t</tbody>\n</table>\n";
                    if(isset($_POST['printableexport'])){$pdf->tableintopdf($pdfoutput);}
                    break;

                    // ==================================================================
                case "1": //ARRAY (Flexible Labels) multi scale

                    $leftheader= $qidattributes['dualscale_headerA'];
                    $rightheader= $qidattributes['dualscale_headerB'];

                    $headstyle = 'style="padding-left: 20px; padding-right: 7px"';
                    $meaquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND language='{$surveyprintlang}' ORDER BY question_order";
                    $mearesult = db_execute_assoc($meaquery);

                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose the appropriate response for each item:");
                    $question['QUESTION_TYPE_HELP'] .= array_filter_help($qidattributes, $surveyprintlang, $surveyid);

                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose the appropriate response for each item:"),"U");}
                    $question['ANSWER'] .= "\n<table>\n\t<thead>\n";

                    $fquery = "SELECT * FROM ".db_table_name("answers")." WHERE qid='{$deqrow['qid']}'  AND language='{$surveyprintlang}' AND scale_id=0 ORDER BY sortorder, code";
                    $fresult = db_execute_assoc($fquery);
                    $fcount = $fresult->RecordCount();
                    $fwidth = "120";
                    $l1=0;
                    $printablesurveyoutput2 = "\t\t\t<td>&nbsp;</td>\n";
                    $myheader2 = '';
                    $pdfoutput = array();
                    $pdfoutput[0][0]='';
                    while ($frow = $fresult->FetchRow())
                    {
                        $printablesurveyoutput2 .="\t\t\t<th>{$frow['answer']}".addsgqacode(" (".$frow['code'].")")."</th>\n";
                        $myheader2 .= "<td></td>";
                        $pdfoutput[0][$l1+1]=$frow['answer'];
                        $l1++;
                    }
                    // second scale
                    $printablesurveyoutput2 .="\t\t\t<td>&nbsp;</td>\n";
                    $fquery1 = "SELECT * FROM ".db_table_name("answers")." WHERE qid='{$deqrow['qid']}'  AND language='{$surveyprintlang}' AND scale_id=1 ORDER BY sortorder, code";
                    $fresult1 = db_execute_assoc($fquery1);
                    $fcount1 = $fresult1->RecordCount();
                    $fwidth = "120";
                    $l2=0;

                    //array to temporary store second scale question codes
                    $scale2array = array();
                    while ($frow1 = $fresult1->FetchRow())
                    {
                        $printablesurveyoutput2 .="\t\t\t<th>{$frow1['answer']}".addsgqacode(" (".$frow1['code'].")")."</th>\n";
                        $pdfoutput[1][$l2]=$frow['answer'];

                        //add current question code
                        $scale2array[$l2] = $frow1['code'];

                        $l2++;
                    }
                    // build header if needed
                    if ($leftheader != '' || $rightheader !='')
                    {
                        $myheader = "\t\t\t<td>&nbsp;</td>";
                        $myheader .= "\t\t\t<th colspan=\"".$l1."\">$leftheader</th>\n";

                        if ($rightheader !='')
                        {
                            // $myheader .= "\t\t\t\t\t" .$myheader2;
                            $myheader .= "\t\t\t<td>&nbsp;</td>";
                            $myheader .= "\t\t\t<th colspan=\"".$l2."\">$rightheader</td>\n";
                        }

                        $myheader .= "\t\t\t\t</tr>\n";
                    }
                    else
                    {
                        $myheader = '';
                    }
                    $question['ANSWER'] .= $myheader . "\t\t</tr>\n\n\t\t<tr>\n";
                    $question['ANSWER'] .= $printablesurveyoutput2;
                    $question['ANSWER'] .= "\t\t</tr>\n\t</thead>\n\n\t<tbody>\n";

                    $rowclass = 'array1';

                    //counter for each subquestion
                    $sqcounter = 0;
                    while ($mearow = $mearesult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t<tr class=\"$rowclass\">\n";
                        $rowclass = alternation($rowclass,'row');
                        $answertext=$mearow['question'].addsgqacode(" (".$fieldname.$mearow['title']."#0) / (".$fieldname.$mearow['title']."#1)");
                        if (strpos($answertext,'|')) {$answertext=substr($answertext,0, strpos($answertext,'|'));}
                        $question['ANSWER'] .= "\t\t\t<th class=\"answertext\">$answertext</th>\n";
                        for ($i=1; $i<=$fcount; $i++)
                        {
                            $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio')."</td>\n";
                        }
                        $question['ANSWER'] .= "\t\t\t<td>&nbsp;</td>\n";
                        for ($i=1; $i<=$fcount1; $i++)
                        {
                            $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio')."</td>\n";
                        }

                        $answertext=$mearow['question'];
                        if (strpos($answertext,'|'))
                        {
                            $answertext=substr($answertext,strpos($answertext,'|')+1);
                            $question['ANSWER'] .= "\t\t\t<th class=\"answertextright\">$answertext</th>\n";
                        }
                        $question['ANSWER'] .= "\t\t</tr>\n";

                        //increase subquestion counter
                        $sqcounter++;
                    }
                    $question['ANSWER'] .= "\t</tbody>\n</table>\n";
                    if(isset($_POST['printableexport'])){$pdf->tableintopdf($pdfoutput);}
                    break;

                    // ==================================================================
                case "H": //ARRAY (Flexible Labels) by Column
                    //$headstyle="style='border-left-style: solid; border-left-width: 1px; border-left-color: #AAAAAA'";
                    $headstyle="style='padding-left: 20px; padding-right: 7px'";
                    $fquery = "SELECT * FROM ".db_table_name("questions")." WHERE parent_qid={$deqrow['qid']}  AND language='{$surveyprintlang}' ORDER BY question_order, title";
                    $fresult = db_execute_assoc($fquery);
                    $question['QUESTION_TYPE_HELP'] .= $clang->gT("Please choose the appropriate response for each item:");
                    if(isset($_POST['printableexport'])){$pdf->intopdf($clang->gT("Please choose the appropriate response for each item:"),"U");}
                    $question['ANSWER'] .= "\n<table>\n\t<thead>\n\t\t<tr>\n\t\t\t<td>&nbsp;</td>\n";
                    $meaquery = "SELECT * FROM ".db_table_name("answers")." WHERE qid='{$deqrow['qid']}' AND scale_id=0 AND language='{$surveyprintlang}' ORDER BY sortorder, code";
                    $mearesult = db_execute_assoc($meaquery);
                    $fcount = $fresult->RecordCount();
                    $fwidth = "120";
                    $i=0;
                    $pdfoutput = array();
                    $pdfoutput[0][0]='';
                    while ($frow = $fresult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t\t<th>{$frow['question']}".addsgqacode(" (".$fieldname.$frow['title'].")")."</th>\n";
                        $i++;
                        $pdfoutput[0][$i]=$frow['question'];
                    }
                    $question['ANSWER'] .= "\t\t</tr>\n\t</thead>\n\n\t<tbody>\n";
                    $a=1;
                    $rowclass = 'array1';


                    while ($mearow = $mearesult->FetchRow())
                    {
                        $question['ANSWER'] .= "\t\t<tr class=\"$rowclass\">\n";
                        $rowclass = alternation($rowclass,'row');
                        $question['ANSWER'] .= "\t\t\t<th class=\"answertext\">{$mearow['answer']}".addsgqacode(" (".$mearow['code'].")")."</th>\n";
                        //$printablesurveyoutput .="\t\t\t\t\t<td>";
                        $pdfoutput[$a][0]=$mearow['answer'];
                        for ($i=1; $i<=$fcount; $i++)
                        {
                            $question['ANSWER'] .= "\t\t\t<td>".input_type_image('radio')."</td>\n";
                            $pdfoutput[$a][$i]="o";
                        }
                        //$printablesurveyoutput .="\t\t\t\t\t</tr></table></td>\n";
                        $question['ANSWER'] .= "\t\t</tr>\n";
                        $a++;
                    }
                    $question['ANSWER'] .= "\t</tbody>\n</table>\n";

                    if(isset($_POST['printableexport'])){$pdf->tableintopdf($pdfoutput);}
                    break;
                case "|":   // File Upload
                    $question['QUESTION_TYPE_HELP'] .= "Kindly attach the aforementioned documents along with the survey";
                    break;
                    // === END SWITCH ===================================================
            }
            if(isset($_POST['printableexport'])){$pdf->ln(5);}

            $question['QUESTION_TYPE_HELP'] = star_replace($question['QUESTION_TYPE_HELP']);
            $group['QUESTIONS'] .= populate_template( 'question' , $question);

        }
        if ($bGroupHasVisibleQuestions)
        {
        $survey_output['GROUPS'] .= populate_template( 'group' , $group );
        }
}

$survey_output['THEREAREXQUESTIONS'] =  str_replace( '{NUMBEROFQUESTIONS}' , $total_questions , $clang->gT('There are {NUMBEROFQUESTIONS} questions in this survey'));

// START recursive tag stripping.
// PHP 5.1.0 introduced the count parameter for preg_replace() and thus allows this procedure to run with only one regular expression.
// Previous version of PHP needs two regular expressions to do the same thing and thus will run a bit slower.
$server_is_newer = version_compare(PHP_VERSION , '5.1.0' , '>');
$rounds = 0;
while($rounds < 1)
{
    $replace_count = 0;
    if($server_is_newer) // Server version of PHP is at least 5.1.0 or newer
    {
        $survey_output['GROUPS'] = preg_replace(
        array(
                                 '/<td>(?:&nbsp;|&#160;| )?<\/td>/isU'
                                 ,'/<th[^>]*>(?:&nbsp;|&#160;| )?<\/th>/isU'
                                 ,'/<([^ >]+)[^>]*>(?:&nbsp;|&#160;|\r\n|\n\r|\n|\r|\t| )*<\/\1>/isU'
                                 )
                                 ,array(
								 '[[EMPTY-TABLE-CELL]]'
								 ,'[[EMPTY-TABLE-CELL-HEADER]]'
								 ,''
								 )
								 ,$survey_output['GROUPS']
								 ,-1
								 ,$replace_count
								 );
    }
    else // Server version of PHP is older than 5.1.0
    {
        $survey_output['GROUPS'] = preg_replace(
        array(
								 '/<td>(?:&nbsp;|&#160;| )?<\/td>/isU'
								 ,'/<th[^>]*>(?:&nbsp;|&#160;| )?<\/th>/isU'
								 ,'/<([^ >]+)[^>]*>(?:&nbsp;|&#160;|\r\n|\n\r|\n|\r|\t| )*<\/\1>/isU'
								 )
								 ,array(
								 '[[EMPTY-TABLE-CELL]]'
								 ,'[[EMPTY-TABLE-CELL-HEADER]]'
								 ,''
								 )
								 ,$survey_output['GROUPS']
								 );
								 $replace_count = preg_match(
						 '/<([^ >]+)[^>]*>(?:&nbsp;|&#160;|\r\n|\n\r|\n|\r|\t| )*<\/\1>/isU'
						 , $survey_output['GROUPS']
						 );
    }

    if($replace_count == 0)
    {
        ++$rounds;
        $survey_output['GROUPS'] = preg_replace(
        array(
						 '/\[\[EMPTY-TABLE-CELL\]\]/'
						 ,'/\[\[EMPTY-TABLE-CELL-HEADER\]\]/'
						 ,'/\n(?:\t*\n)+/'
						 )
						 ,array(
						 '<td>&nbsp;</td>'
						 ,'<th>&nbsp;</th>'
						 ,"\n"
						 )
						 ,$survey_output['GROUPS']
						 );

    }
}

$survey_output['GROUPS'] = preg_replace( '/(<div[^>]*>){NOTEMPTY}(<\/div>)/' , '\1&nbsp;\2' , $survey_output['GROUPS']);

// END recursive empty tag stripping.

if(isset($_POST['printableexport']))
{
    if ($surveystartdate!='')
    {
        if(isset($_POST['printableexport'])){$pdf->intopdf(sprintf($clang->gT("Please submit by %s"), $surveyexpirydate));}
    }
    if(!empty($surveyfaxto) && $surveyfaxto != '000-00000000') //If no fax number exists, don't display faxing information!
    {
        if(isset($_POST['printableexport'])){$pdf->intopdf(sprintf($clang->gT("Please fax your completed survey to: %s"),$surveyfaxto),'B');}
    }
    $pdf->titleintopdf($clang->gT("Submit Your Survey."),$clang->gT("Thank you for completing this survey."));
    $pdf->write_out($clang->gT($surveyname)." ".$surveyid.".pdf");
} else {
    echo populate_template( 'survey' , $survey_output );
}
exit;

function min_max_answers_help($qidattributes, $surveyprintlang, $surveyid) {
	global $clang;
	$output = "";
	if(!empty($qidattributes['min_answers'])) {
		$output .= "\n<p class='extrahelp'>".sprintf($clang->gT("Please choose at least %s item(s)"), $qidattributes['min_answers'])."</p>\n";
	}
    if(!empty($qidattributes['max_answers'])) {
		$output .= "\n<p class='extrahelp'>".sprintf($clang->gT("Please choose no more than %s item(s)"),$qidattributes['max_answers'])."</p>\n";
	}
	return $output;
}

function array_filter_help($qidattributes, $surveyprintlang, $surveyid) {
    global $clang;
    $output = "";
    if(!empty($qidattributes['array_filter']))
    {
        $newquery="SELECT question FROM ".db_table_name("questions")." WHERE title='{$qidattributes['array_filter']}' AND language='{$surveyprintlang}' AND sid = '$surveyid'";
        $newresult=db_execute_assoc($newquery);
        $newquestiontext=$newresult->fetchRow();
        $output .= "\n<p class='extrahelp'>
		    ".sprintf($clang->gT("Only answer this question for the items you selected in question *%s* ('%s')"),$qidattributes['array_filter'], FlattenText(br2nl($newquestiontext['question'])))."
		</p>\n";
    }
    if(!empty($qidattributes['array_filter_exclude']))
    {
        $newquery="SELECT question FROM ".db_table_name("questions")." WHERE title='{$qidattributes['array_filter_exclude']}' AND language='{$surveyprintlang}' AND sid = '$surveyid'";
        $newresult=db_execute_assoc($newquery);
        $newquestiontext=$newresult->fetchRow();
        $output .= "\n    <p class='extrahelp'>
		    ".sprintf($clang->gT("Only answer this question for the items you did not select in question *%s* ('%s')"),$qidattributes['array_filter_exclude'], br2nl($newquestiontext['question']))."
		</p>\n";
    }
    return $output;
}

/*
 * $code: Text string containing the reference (column heading) for the current (sub-) question
 *
 * Checks if the $showsgqacode setting is enabled at config and adds references to the column headings
 * to the output so it can be used as a code book for customized SQL queries when analysing data.
 *
 * return: adds the text string to the overview
 */
function addsgqacode($code)
{
	global $showsgqacode;
	if(isset($showsgqacode) && $showsgqacode == true)
	{
		return $code;
	}
}
?>
