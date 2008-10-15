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
* $Id: exportquexml.php 3659 2007-11-18 12:41:21Z c_schmitz $
*/


//Ensure script is not run directly, avoid path disclosure
include_once("login_check.php");



if (!isset($surveyid)) {$surveyid=returnglobal('sid');}

if (!$surveyid)
	{
	echo $htmlheader
		."<br />\n"
		."<table width='350' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n"
		."\t<tr bgcolor='#555555'><td colspan='2' height='4'><font size='1' face='verdana' color='white'><strong>"
		._EXPORTSURVEY."</strong></td></tr>\n"
		."\t<tr><td align='center'>\n"
		."$setfont<br /><strong><font color='red'>"
		._ERROR."</font></strong><br />\n"
		._ES_NOSID."<br />\n"
		."<br /><input type='submit' $btstyle value='"
		._GO_ADMIN."' onClick=\"window.open('$scriptname', '_top')\">\n"
		."\t</td></tr>\n"
		."</table>\n"
		."</body></html>\n";
	exit;
	}





function cleanup($string)
{
	return strip_tags($string);
}
	

function create_free($f,$len,$lab="")
{
	global $dom;
	$free = $dom->create_element("free");
	
	$format = $dom->create_element("format");
	$format->set_content(cleanup($f));
	
	$length = $dom->create_element("length");
	$length->set_content(cleanup($len));
	
	$label = $dom->create_element("label");
	$label->set_content(cleanup($lab));

	$free->append_child($format);
	$free->append_child($length);
	$free->append_child($label);
	
	
	return $free;
}


function fixed_array($array)
{
	global $dom;
	$fixed = $dom->create_element("fixed");
	
	foreach ($array as $key => $v)
	{
	    $category = $dom->create_element("category");
	    
	    $label = $dom->create_element("label");
	    $label->set_content(cleanup("$key"));

	    $value= $dom->create_element("value");
	    $value->set_content(cleanup("$v"));
	    
	    $category->append_child($label);
	    $category->append_child($value);	    

	    $fixed->append_child($category);
	}


	return $fixed;
}



function create_fixed($qlid,$rotate=false,$labels=true)
{
	global $dom, $connect;
	global $dbprefix;
	if ($labels)
		$Query = "SELECT * FROM {$dbprefix}labels WHERE lid = $qlid ORDER BY sortorder ASC";
	else
		$Query = "SELECT code,answer as title,sortorder FROM {$dbprefix}answers WHERE qid = $qlid ORDER BY sortorder ASC";
	$QueryResult = db_execute_assoc($Query) or safe_die ("ERROR: $QueryResult<br />".$connect->ErrorMsg());

	$fixed = $dom->create_element("fixed");
	
	while ($Row = $QueryResult->FetchRow())
	{
	    $category = $dom->create_element("category");
	    
	    $label = $dom->create_element("label");
	    $label->set_content(cleanup($Row['title']));

	    $value= $dom->create_element("value");
	    $value->set_content(cleanup($Row['code']));
	    
	    $category->append_child($label);
	    $category->append_child($value);	    

	    $fixed->append_child($category);
	}

	if ($rotate) $fixed->set_attribute("rotate","true");
	
	return $fixed;
}

function create_multi(&$question,$qid,$varname)
{
	global $dom, $connect;
	global $dbprefix;
	$Query = "SELECT * FROM {$dbprefix}answers WHERE qid = $qid ORDER BY sortorder ASC";
	$QueryResult = db_execute_assoc($Query) or safe_die ("ERROR: $QueryResult<br />".$connect->ErrorMsg());

	while ($Row = $QueryResult->FetchRow())
	{
	    $response = $dom->create_element("response");
	    $fixed = $dom->create_element("fixed");
	    $category = $dom->create_element("category");
	    
	    $label = $dom->create_element("label");
	    $label->set_content(cleanup($Row['answer']));

	    $value= $dom->create_element("value");
	    $value->set_content(cleanup($Row['code']));
	    
	    $category->append_child($label);
	    $category->append_child($value);	    

	    $fixed->append_child($category);
	    
	    $response->append_child($fixed);
	    $response->set_attribute("varName",$varname . "_" . cleanup($Row['code']));
	  
    	    $question->append_child($response);
	}

	return;

}

function create_subQuestions(&$question,$qid,$varname)
{
	global $dom, $connect;
	global $dbprefix;
	$Query = "SELECT * FROM {$dbprefix}answers WHERE qid = $qid ORDER BY sortorder ASC";
	$QueryResult = db_execute_assoc($Query) or safe_die ("ERROR: $QueryResult<br />".$connect->ErrorMsg());

	while ($Row = $QueryResult->FetchRow())
	{
	    $subQuestion = $dom->create_element("subQuestion");
            $text = $dom->create_element("text");
	    $text->set_content(cleanup($Row['answer']));
	    $subQuestion->append_child($text);
	    $subQuestion->set_attribute("varName",$varname . "_" . cleanup($Row['code']));
	    $question->append_child($subQuestion);
	}

	return;
}

	
$dom = domxml_new_doc("1.0");


//Title and survey id
$questionnaire = $dom->create_element("questionnaire");
$title = $dom->create_element("title");

$baselang=GetBaseLanguageFromSurveyID($surveyid);
$Query = "SELECT * FROM {$dbprefix}surveys,{$dbprefix}surveys_languagesettings WHERE sid=$surveyid and surveyls_survey_id=sid and surveyls_language='".$baselang."'";
$QueryResult = db_execute_assoc($Query) or safe_die ("ERROR: $QueryResult<br />".$connect->ErrorMsg());
$Row = $QueryResult->FetchRow();
$questionnaire->set_attribute("id", $Row['sid']);
$title->set_content(cleanup($Row['surveyls_title']));
$questionnaire->append_child($title);

//investigator and datacollector
$investigator = $dom->create_element("investigator");
$name = $dom->create_element("name");
$name = $dom->create_element("firstName");
$name = $dom->create_element("lastName");
$dataCollector = $dom->create_element("dataCollector");

$questionnaire->append_child($investigator);
$questionnaire->append_child($dataCollector);

//questionnaireInfo == welcome
$questionnaireInfo = $dom->create_element("questionnaireInfo");
$position = $dom->create_element("position");
$text = $dom->create_element("text");
$administration = $dom->create_element("administration");

$position->set_content("before");
$text->set_content(cleanup($Row['surveyls_welcometext']));
$administration->set_content("self");
$questionnaireInfo->append_child($position);
$questionnaireInfo->append_child($text);
$questionnaireInfo->append_child($administration);
$questionnaire->append_child($questionnaireInfo);

//section == group


$Query = "SELECT * FROM {$dbprefix}groups WHERE sid=$surveyid order by group_order ASC";
$QueryResult = db_execute_assoc($Query) or safe_die ("ERROR: $QueryResult<br />".$connect->ErrorMsg());
//for each section
while ($Row = $QueryResult->FetchRow())
{
	$gid = $Row['gid'];
	
	$section = $dom->create_element("section");
	$sectionInfo = $dom->create_element("sectionInfo");	
	$position = $dom->create_element("position");
	$text = $dom->create_element("text");
	$administration = $dom->create_element("administration");

	$position->set_content("title");
	$text->set_content(cleanup($Row['description']));
	$administration->set_content("self");
	$sectionInfo->append_child($position);
	$sectionInfo->append_child($text);
	$sectionInfo->append_child($administration);

	$section->append_child($sectionInfo);
	$section->set_attribute("id", $gid);
	
	//boilerplate questions convert to sectionInfo elements
	$Query = "SELECT * FROM {$dbprefix}questions WHERE sid=$surveyid AND gid = $gid AND type LIKE 'X' ORDER BY question_order ASC";
	$QR = db_execute_assoc($Query) or safe_die ("ERROR: $QueryResult<br />".$connect->ErrorMsg());
	while ($RowQ = $QR->FetchRow())
	{
		$sectionInfo = $dom->create_element("sectionInfo");	
		$position = $dom->create_element("position");
		$text = $dom->create_element("text");
		$administration = $dom->create_element("administration");
	
		$position->set_content("before");
		$text->set_content(cleanup($RowQ['question']));
		$administration->set_content("self");
		$sectionInfo->append_child($position);
		$sectionInfo->append_child($text);
		$sectionInfo->append_child($administration);
	
		$section->append_child($sectionInfo);
	}


	
	//foreach question
	$Query = "SELECT * FROM {$dbprefix}questions WHERE sid=$surveyid AND gid = $gid AND type NOT LIKE 'X' ORDER BY question_order ASC";
	$QR = db_execute_assoc($Query) or safe_die ("ERROR: $QueryResult<br />".$connect->ErrorMsg());
	while ($RowQ = $QR->FetchRow())
	{
		$question = $dom->create_element("question");
		$type = $RowQ['type'];
		$qid = $RowQ['qid'];
		$lid = $RowQ['lid'];

		$text = $dom->create_element("text");
		$text->set_content(cleanup($RowQ['question']));
		$question->append_child($text);
		
		//directive
		if (!empty($RowQ['help']))
		{
			$directive = $dom->create_element("directive");
			$position = $dom->create_element("position");
			$position->set_content("during");
			$text = $dom->create_element("text");
			$text->set_content(cleanup($RowQ['help']));
			$administration = $dom->create_element("administration");
			$administration->set_content("self");
			
			$directive->append_child($position);
			$directive->append_child($text);
			$directive->append_child($administration);

			$question->append_child($directive);
		}
		
		$response = $dom->create_element("response");		
	        $response->set_attribute("varName",cleanup($RowQ['title']));
		
		switch ($type)
	        {
        		case "X": //BOILERPLATE QUESTION - none should appear
		            
		            break;
		        case "5": //5 POINT CHOICE radio-buttons
		            $response->append_child(fixed_array(array("1" => 1,"2" => 2,"3" => 3,"4" => 4,"5" => 5)));
			    $question->append_child($response);		            
		            break;
		        case "D": //DATE
			    $response->append_child(create_free("date","8",""));
			    $question->append_child($response);
		            break;
		        case "Z": //LIST Flexible drop-down/radio-button list
			    $response->append_child(create_fixed($lid));
			    $question->append_child($response);
		            break;
		        case "L": //LIST drop-down/radio-button list
			    $response->append_child(create_fixed($qid,false,false));
			    $question->append_child($response);
		            break;
		        case "W": //List - dropdown
			    $response->append_child(create_fixed($lid));
			    $question->append_child($response);
		            break;
		        case "!": //List - dropdown
			    $response->append_child(create_fixed($qid,false,false));
			    $question->append_child($response);		            
		            break;
		        case "O": //LIST WITH COMMENT drop-down/radio-button list + textarea
		            //Not yet implemented		            
		            break;
		        case "R": //RANKING STYLE
		            //Not yet implemented
		            break;
		        case "M": //MULTIPLE OPTIONS checkbox
		            create_multi(&$question,$qid,$RowQ['title']);
		            break;
		        case "P": //MULTIPLE OPTIONS WITH COMMENTS checkbox + text
      		            //Not yet implemented
			    break;
		        case "Q": //MULTIPLE SHORT TEXT
		            //Not yet implemented		            
		            break;
		        case "N": //NUMERICAL QUESTION TYPE
			    $response->append_child(create_free("integer","10",""));
			    $question->append_child($response);
		            break;
		        case "S": //SHORT FREE TEXT
			    $response->append_child(create_free("text","24",""));
			    $question->append_child($response);
		            break;
		        case "T": //LONG FREE TEXT
			    $response->append_child(create_free("text","240",""));
			    $question->append_child($response);
		            break;
		        case "U": //HUGE FREE TEXT
			    $response->append_child(create_free("longtext","8",""));
			    $question->append_child($response);         		            
		            break;
		        case "Y": //YES/NO radio-buttons
       			    $response->append_child(fixed_array(array("Yes" => 1,"No" => 2)));
			    $question->append_child($response);		            
		            break;
		        case "G": //GENDER drop-down list
       			    $response->append_child(fixed_array(array("Female" => 1,"Male" => 2)));
			    $question->append_child($response);
		            break;
		        case "A": //ARRAY (5 POINT CHOICE) radio-buttons
			    create_subQuestions(&$question,$qid,$RowQ['title']);
			    $response->append_child(fixed_array(array("1" => 1,"2" => 2,"3" => 3,"4" => 4,"5" => 5)));
			    $question->append_child($response);		            
		            break;
		        case "B": //ARRAY (10 POINT CHOICE) radio-buttons
			    create_subQuestions(&$question,$qid,$RowQ['title']);
			    $response->append_child(fixed_array(array("1" => 1,"2" => 2,"3" => 3,"4" => 4,"5" => 5,"6" => 6,"7" => 7,"8" => 8,"9" => 9,"10" => 10)));
			    $question->append_child($response);		            
			    break;
		        case "C": //ARRAY (YES/UNCERTAIN/NO) radio-buttons
			    create_subQuestions(&$question,$qid,$RowQ['title']);
			    $response->append_child(fixed_array(array("Yes" => 1,"Uncertain" => 2,"No" => 3)));
			    $question->append_child($response);		            
		            break;
		        case "E": //ARRAY (Increase/Same/Decrease) radio-buttons
			    create_subQuestions(&$question,$qid,$RowQ['title']);
			    $response->append_child(fixed_array(array("Increase" => 1,"Same" => 2,"Decrease" => 3)));
			    $question->append_child($response);		            
		            break;
		        case "F": //ARRAY (Flexible) - Row Format
			    //select subQuestions from answers table where QID 
			    create_subQuestions(&$question,$qid,$RowQ['title']);
			    $response->append_child(create_fixed($lid,false));
			    $question->append_child($response);
			    //select fixed responses from
		            break;
		        case "H": //ARRAY (Flexible) - Column Format
			    create_subQuestions(&$question,$qid,$RowQ['title']);
			    $response->append_child(create_fixed($lid,true));
			    $question->append_child($response);			    
	                    break;
//			case "^": //SLIDER CONTROL
		            //Not yet implemented
			    break;
		} //End Switch

		

		
		$section->append_child($question);
	}

			
	$questionnaire->append_child($section);
}


$dom->append_child($questionnaire);


$fn = "survey_$surveyid.xml";
header("Content-Type: application/download");
header("Content-Disposition: attachment; filename=$fn");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache");                          // HTTP/1.0

echo $dom->dump_mem(true,'UTF-8');
exit;
?>
