<?

/**
 * Functions relating to integration with {@link http://www.limesurvey.org/ LimeSurvey}
 *
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
 * @subpackage functions
 * @link http://www.deakin.edu.au/dcarf/ queXS was writen for DCARF - Deakin Computer Assisted Research Facility
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */


/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

/**
 * Database file
 */
include_once(dirname(__FILE__).'/../db.inc.php');


/** 
 * Taken from common.php in the LimeSurvey package
 * Add a prefix to a database name
 *
 * @param string $name Database name
 * @link http://www.limesurvey.org/ LimeSurvey
 */
function db_table_name($name)
{
	return "`".LIME_PREFIX.$name."`";
}


/** 
 * Taken from common.php in the LimeSurvey package
 * Get a random survey ID
 *
 * @link http://www.limesurvey.org/ LimeSurvey
 */
function getRandomID()
{        // Create a random survey ID - based on code from Ken Lyle
        // Random sid/ question ID generator...
        $totalChar = 5; // number of chars in the sid
        $salt = "123456789"; // This is the char. that is possible to use
        srand((double)microtime()*1000000); // start the random generator
        $sid=""; // set the inital variable
        for ($i=0;$i<$totalChar;$i++) // loop and create sid
        $sid = $sid . substr ($salt, rand() % strlen($salt), 1);
        return $sid;
}




/** 
 * Taken from admin/database.php in the LimeSurvey package
 * With modifications
 *
 * @param string $title Questionnaire name
 * @link http://www.limesurvey.org/ LimeSurvey
 */
function create_limesurvey_questionnaire($title)
{
	global $ldb;

	// Get random ids until one is found that is not used
	do
	{
		$surveyid = getRandomID();
		$isquery = "SELECT sid FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
		$isresult = $ldb->Execute($isquery);
	}
	while (!empty($isresult) && $isresult->RecordCount() > 0);

	$isquery = "INSERT INTO ". LIME_PREFIX ."surveys\n"
	. "(sid, owner_id, admin, active, useexpiry, expires, "
	. "adminemail, private, faxto, format, template, url, "
	. "language, datestamp, ipaddr, refurl, usecookie, notification, allowregister, attribute1, attribute2, "
	. "allowsave, autoredirect, allowprev,datecreated,tokenanswerspersistence)\n"
	. "VALUES ($surveyid, 1,\n"
	. "'', 'N', \n"
	. "'N','1980-01-01', '', 'N',\n"
	. "'', 'S', 'quexs', '" . QUEXS_URL . "rs_project_end.php',\n"
	. "'en', 'Y', 'N', 'N',\n"
	. "'N', '0', 'Y',\n"
	. "'att1', 'att2', \n"
	. "'Y', 'Y', 'Y','".date("Y-m-d")."','Y')";
	$isresult = $ldb->Execute($isquery);

	// insert base language into surveys_language_settings
	$isquery = "INSERT INTO ".db_table_name('surveys_languagesettings')
	. "(surveyls_survey_id, surveyls_language, surveyls_title, surveyls_description, surveyls_welcometext, surveyls_urldescription, "
	. "surveyls_email_invite_subj, surveyls_email_invite, surveyls_email_remind_subj, surveyls_email_remind, "
	. "surveyls_email_register_subj, surveyls_email_register, surveyls_email_confirm_subj, surveyls_email_confirm)\n"
	. "VALUES ($surveyid, 'en', $title, $title,\n"
	. "'',\n"
	. "'', '',\n"
	. "'', '',\n"
	. "'', '',\n"
	. "'', '',\n"
	. "'')";
	$isresult = $ldb->Execute($isquery);

	// Insert into survey_rights
	$isrquery = "INSERT INTO ". LIME_PREFIX . "surveys_rights VALUES($surveyid,1,1,1,1,1,1,1)";
	$isrresult = $ldb->Execute($isrquery) or die ($isrquery."<br />".$ldb->ErrorMsg());

	return $surveyid;
}




/**
 * Return the lime_sid given the case_id
 *
 * @param int $case_id The case id
 * @return bool|int False if no lime_sid otherwise the lime_sid
 *
 */
function get_lime_sid($case_id)
{
	global $db;

	$sql = "SELECT q.lime_sid
		FROM questionnaire as q, `case` as c
		WHERE c.case_id = '$case_id'
		AND q.questionnaire_id = c.questionnaire_id";

	$l = $db->GetRow($sql);

	if (empty($l)) return false;

	return $l['lime_sid'];
}


/**
 * Check if LimeSurvey has marked a questionnaire as complete
 *
 * @param int $case_id The case id
 * @return bool True if complete, false if not or unknown
 *
 */
function limesurvey_is_completed($case_id)
{
	global $ldb;

	$lime_sid = get_lime_sid($case_id);
	if ($lime_sid == false) return false;

	$sql = "SELECT completed
		FROM " . LIME_PREFIX . "tokens_$lime_sid 
		WHERE token = '$case_id'";
	
	$r = $ldb->GetRow($sql);

	if (!empty($r))
		if ($r['completed'] != 'N') return true;

	return false;



}


/**
 * Return the number of questions in the given questionnaire
 *
 * @param int $lime_sid The limesurvey sid
 * @return bool|int False if no data, otherwise the number of questions
 *
 */
function limesurvey_get_numberofquestions($lime_sid)
{
	global $ldb;

	$sql = "SELECT count(qid) as c
		FROM " . LIME_PREFIX . "questions
		WHERE sid = '$lime_sid'";

	$r = $ldb->GetRow($sql);

	if (!empty($r))
		return $r['c'];

	return false;
}

/**
 * Return the percent complete a questionnaire is, or false if not started
 *
 * @param int $case_id The case id
 * @return bool|float False if no data, otherwise the percentage of questions answered
 *
 */
function limesurvey_percent_complete($case_id)
{
	global $ldb;

	$lime_sid = get_lime_sid($case_id);
	if ($lime_sid == false) return false;

	$sql = "SELECT saved_thisstep
		FROM ". LIME_PREFIX ."saved_control
		WHERE sid = '$lime_sid'
		AND identifier = '$case_id'";

	$r = $ldb->GetRow($sql);

	if (!empty($r))
	{
		$step = $r['saved_thisstep'];
		$questions = limesurvey_get_numberofquestions($lime_sid);
		return ($step / $questions) * 100.0;
	}

	return false;

}


function limesurvey_get_width($qid,$default)
{
	global $ldb;

	$sql = "SELECT value FROM ".LIME_PREFIX."question_attributes WHERE qid = '$qid' and attribute = 'maximum_chars'";
	$r = $ldb->GetRow($sql);

	if (!empty($r))
		$default = $r['value'];

	return $default;
}



function limesurvey_fixed_width($lid)
{
	global $ldb;

	$sql = "SELECT MAX(LENGTH(code)) as c FROM ".LIME_PREFIX."labels WHERE lid = $lid";
	$r = $ldb->GetRow($sql);

	$val = 1;

	if (!empty($r))
		$val = $r['c'];

	return $val;
}

function limesurvey_create_multi(&$varwidth,&$vartype,$qid,$varname,$length,$type)
{
	global $ldb;

	$sql = "SELECT *
		FROM ".LIME_PREFIX."answers
		WHERE qid = $qid
		ORDER BY sortorder ASC";

	$r = $ldb->GetAll($sql);

	foreach($r as $Row)
	{
		$v = $varname . $Row['code'];
		$varwidth[$v] = $length;
		$vartype[$v] = $type;
	}

	return;
}

/**
 * Return a string with only ASCII characters in it
 *
 * This function was sourced from the php website, help on str_replace
 * No author was listed at the time of access
 *
 * @param string $stringIn The string
 * @return string A string containing only ASCII characters
 */
function all_ascii( $stringIn ){
    $final = '';
    $search = array(chr(145),chr(146),chr(147),chr(148),chr(150),chr(151),chr(13),chr(10));
    $replace = array("'","'",'"','"','-','-',' ',' ');

    $hold = str_replace($search[0],$replace[0],$stringIn);
    $hold = str_replace($search[1],$replace[1],$hold);
    $hold = str_replace($search[2],$replace[2],$hold);
    $hold = str_replace($search[3],$replace[3],$hold);
    $hold = str_replace($search[4],$replace[4],$hold);
    $hold = str_replace($search[5],$replace[5],$hold);
    $hold = str_replace($search[6],$replace[6],$hold);
    $hold = str_replace($search[7],$replace[7],$hold);

    if(!function_exists('str_split')){
       function str_split($string,$split_length=1){
           $count = strlen($string);
           if($split_length < 1){
               return false;
           } elseif($split_length > $count){
               return array($string);
           } else {
               $num = (int)ceil($count/$split_length);
               $ret = array();
               for($i=0;$i<$num;$i++){
                   $ret[] = substr($string,$i*$split_length,$split_length);
               }
               return $ret;
           }
       }
    }

    $holdarr = str_split($hold);
    foreach ($holdarr as $val) {
       if (ord($val) < 128) $final .= $val;
    }
    return $final;
}


/**
 * Produce a fixed width string containing the data from a questionnaire
 *
 * @param int $questionnaire_id The quesitonnaire id
 * @param int|false $sample_import_id The sample importid or false for all data
 * @return string Fixed width data from the limesurvey database
 *
 */
function limesurvey_export_fixed_width($questionnaire_id,$sample_import_id = false)
{
	global $ldb;
	global $db;

	//array of varname and width
	$varwidth = array();
	$vartype = array();

	$sql = "SELECT lime_sid
		FROM questionnaire
		WHERE questionnaire_id = '$questionnaire_id'";

	$r = $db->GetRow($sql);

	if (!empty($r))
		$surveyid = $r['lime_sid']; 
	else
		return;

	//foreach question
	$sql = "SELECT * 
		FROM ".LIME_PREFIX."questions
		WHERE sid=$surveyid
		AND type NOT LIKE 'X'
		ORDER BY gid,question_order ASC";

	$r = $ldb->GetAll($sql);
	foreach ($r as $RowQ)
	{
		$type = $RowQ['type'];
		$qid = $RowQ['qid'];
		$lid = $RowQ['lid'];
		$gid = $RowQ['gid'];
	
		$varName = $surveyid . "X" . $gid . "X" . $qid;
	
		switch ($type)
	        {
	       		case "X": //BOILERPLATE QUESTION - none should appear
		            
		            break;
		        case "5": //5 POINT CHOICE radio-buttons
		            $varwidth[$varName]=1;
			    $vartype[$varName] = 1;
		            break;
		        case "D": //DATE
		            $varwidth[$varName]=8;
			    $vartype[$varName] = 1;
		            break;
		        case "Z": //LIST Flexible drop-down/radio-button list
		            $varwidth[$varName]=limesurvey_fixed_width($lid);
			    $vartype[$varName] = 1;
		            break;
		        case "L": //LIST drop-down/radio-button list
		            $varwidth[$varName]=limesurvey_fixed_width($lid);
			    $vartype[$varName] = 1;
		            break;
		        case "W": //List - dropdown
		            $varwidth[$varName]=limesurvey_fixed_width($lid);
			    $vartype[$varName] = 1;
		            break;
		        case "!": //List - dropdown
		            $varwidth[$varName]=limesurvey_fixed_width($lid);
			    $vartype[$varName] = 1;
		            break;
		        case "O": //LIST WITH COMMENT drop-down/radio-button list + textarea
		            //Not yet implemented		            
		            break;
		        case "R": //RANKING STYLE
		            //Not yet implemented
		            break;
		        case "M": //MULTIPLE OPTIONS checkbox
		            limesurvey_create_multi($varwidth,$vartype,$qid,$varName,1,3);
		            break;
		        case "P": //MULTIPLE OPTIONS WITH COMMENTS checkbox + text
	     		            //Not yet implemented
			    break;
		        case "Q": //MULTIPLE SHORT TEXT
		            limesurvey_create_multi($varwidth,$vartype,$qid,$varName,limesurvey_get_width($qid,24),2);		            
		            break;
		        case "K": //MULTIPLE NUMERICAL
		            limesurvey_create_multi($varwidth,$vartype,$qid,$varName,limesurvey_get_width($qid,10),1);		            
 		            break;
	  	        case "N": //NUMERICAL QUESTION TYPE
		            $varwidth[$varName]= limesurvey_get_width($qid,10);
			    $vartype[$varName] = 1;
		            break;
		        case "S": //SHORT FREE TEXT
		            $varwidth[$varName]= limesurvey_get_width($qid,240);
			    $vartype[$varName] = 2;
		            break;
		        case "T": //LONG FREE TEXT
		            $varwidth[$varName]= limesurvey_get_width($qid,1024);
			    $vartype[$varName] = 2;
			    break;
		        case "U": //HUGE FREE TEXT
		            $varwidth[$varName]= limesurvey_get_width($qid,2048);
			    $vartype[$varName] = 2;
		            break;
		        case "Y": //YES/NO radio-buttons
		            $varwidth[$varName]=1;
			    $vartype[$varName] = 1;
			    break;
		        case "G": //GENDER drop-down list
		            $varwidth[$varName]=1;
			    $vartype[$varName] = 1;
			    break;
		        case "A": //ARRAY (5 POINT CHOICE) radio-buttons
		            $varwidth[$varName]=1;
			    $vartype[$varName] = 1;		    
				break;
		        case "B": //ARRAY (10 POINT CHOICE) radio-buttons
		            $varwidth[$varName]=2;
			    $vartype[$varName] = 1;    
				break;
		        case "C": //ARRAY (YES/UNCERTAIN/NO) radio-buttons
		            $varwidth[$varName]=1;
			    $vartype[$varName] = 1;    
				break;
		        case "E": //ARRAY (Increase/Same/Decrease) radio-buttons
		            $varwidth[$varName]=1;
			    $vartype[$varName] = 1;    
				break;
		        case "F": //ARRAY (Flexible) - Row Format
				limesurvey_create_multi($varwidth,$vartype,$qid,$varName,limesurvey_fixed_width($lid),1);    
		            break;
		        case "H": //ARRAY (Flexible) - Column Format
				limesurvey_create_multi($varwidth,$vartype,$qid,$varName,limesurvey_fixed_width($lid),1);
	    			break;
			case "^": //SLIDER CONTROL
		            //Not yet implemented
			    break;
		} //End Switch
			
			
	}
	

	$fn = "survey_$surveyid.dat";

	header("Content-Type: application/download");
	header("Content-Disposition: attachment; filename=$fn");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
	Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Pragma: no-cache");                          // HTTP/1.0
	
	$sql3 = "SELECT c.case_id as case_id
		FROM `case` as c
		WHERE c.questionnaire_id = '$questionnaire_id'";

	$r = $db->GetAll($sql3);

	if (!empty($r))
	{
		$sql = "SELECT *
			FROM ".LIME_PREFIX."survey_$surveyid
			WHERE submitdate IS NOT NULL";


		if ($sample_import_id == false)
		{
			$sql .= " AND (";
			foreach($r as $row)
			{
				$token = $row['case_id'];
				$sql .= " token = '$token'";
				if (next($r)) $sql .= " or ";
			}
			$sql .= ")";
		}
		else
		{
			$sql2 = "SELECT c.case_id as case_id
				FROM `case` as c, `sample` as s
				WHERE c.questionnaire_id = '$questionnaire_id'
				AND c.sample_id = s.sample_id
				AND s.import_id = '$sample_import_id'";
	
			$r = $db->GetAll($sql2);
	
			if (!empty($r))
			{
				$sql .= " AND (";
				foreach($r as $row)
				{
					$token = $row['case_id'];
					$sql .= " token = '$token'";
					if (next($r)) $sql .= " or ";
				}
				$sql .= ")";
			}
	
		}

		$r = $ldb->GetAll($sql);
		
		foreach($r as $Row)
		{
			foreach ($varwidth as $var => $width)
			{
				if ($vartype[$var] == 1)
					echo str_pad(substr(all_ascii($Row[$var]),0,$width), $width, " ", STR_PAD_LEFT);
				else if ($vartype[$var] == 2)
					echo str_pad(substr(all_ascii($Row[$var]),0,$width), $width, " ", STR_PAD_RIGHT);
				else if ($vartype[$var] == 3)
					if (empty($Row[$var])) echo " "; else echo "1";
			}
			echo str_pad(substr($Row['token'],0,9), 9, " ", STR_PAD_LEFT);
			echo str_pad(substr($Row['datestamp'],0,16), 16, " ", STR_PAD_LEFT);
			echo "\n";
		}

	}
	
}


?>
