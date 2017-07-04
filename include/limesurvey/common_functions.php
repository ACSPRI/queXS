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
*	$Id: common_functions.php 12418 2012-02-09 11:54:10Z mennodekker $
*	Files Purpose: lots of common functions
*/

if (version_compare(PHP_VERSION,'5.1.2')<0)
{
    die('Your PHP version is outdated. LimeSurvey needs PHP 5.2 or later.');
}
require_once('replacements.php');


/**
* This function gives back an array that defines which survey permissions and what part of the CRUD+Import+Export subpermissions is available.
* - for example it would not make sense to have  a 'create' permissions for survey locale settings as they exist with every survey
*  so the editor for survey permission should not show a checkbox here, therfore the create element of that permission is set to 'false'
*  If you want to generally add a new permission just add it here.
*
*/
function aGetBaseSurveyPermissions()
{
    global $clang;
    $aPermissions=array(
    'assessments'=>array('create'=>true,'read'=>true,'update'=>true,'delete'=>true,'import'=>false,'export'=>false,'title'=>$clang->gT("Assessments"),'description'=>$clang->gT("Permission to create/view/update/delete assessments rules for a survey"),'img'=>'assessments'),  // Checked
    'quotas'=>array('create'=>true,'read'=>true,'update'=>true,'delete'=>true,'import'=>false,'export'=>false,'title'=>$clang->gT("Quotas"),'description'=>$clang->gT("Permission to create/view/update/delete quota rules for a survey"),'img'=>'quota'), // Checked
    'responses'=>array('create'=>true,'read'=>true,'update'=>true,'delete'=>true,'import'=>true,'export'=>true,'title'=>$clang->gT("Responses"),'description'=>$clang->gT("Permission to create(data entry)/view/update/delete/import/export responses"),'img'=>'browse'),
    'statistics'=>array('create'=>false,'read'=>true,'update'=>false,'delete'=>false,'import'=>false,'export'=>false,'title'=>$clang->gT("Statistics"),'description'=>$clang->gT("Permission to view statistics"),'img'=>'statistics'),    //Checked
    'survey'=>array('create'=>false,'read'=>true,'update'=>false,'delete'=>true,'import'=>false,'export'=>false,'title'=>$clang->gT("Survey deletion"),'description'=>$clang->gT("Permission to delete a survey"),'img'=>'delete'),   //Checked
    'surveyactivation'=>array('create'=>false,'read'=>false,'update'=>true,'delete'=>false,'import'=>false,'export'=>false,'title'=>$clang->gT("Survey activation"),'description'=>$clang->gT("Permission to activate/deactivate a survey"),'img'=>'activate_deactivate'),  //Checked
    'surveycontent'=>array('create'=>true,'read'=>true,'update'=>true,'delete'=>true,'import'=>true,'export'=>true,'title'=>$clang->gT("Survey content"),'description'=>$clang->gT("Permission to create/view/update/delete/import/export the questions, groups, answers & conditions of a survey"),'img'=>'add'),
    'surveylocale'=>array('create'=>false,'read'=>true,'update'=>true,'delete'=>false,'import'=>false,'export'=>false,'title'=>$clang->gT("Survey locale settings"),'description'=>$clang->gT("Permission to view/update the survey locale settings"),'img'=>'edit'),
    'surveysecurity'=>array('create'=>true,'read'=>true,'update'=>true,'delete'=>true,'import'=>false,'export'=>false,'title'=>$clang->gT("Survey security"),'description'=>$clang->gT("Permission to modify survey security settings"),'img'=>'survey_security'),
    'surveysettings'=>array('create'=>false,'read'=>true,'update'=>true,'delete'=>false,'import'=>false,'export'=>false,'title'=>$clang->gT("Survey settings"),'description'=>$clang->gT("Permission to view/update the survey settings including token table creation"),'img'=>'survey_settings'),
    'tokens'=>array('create'=>true,'read'=>true,'update'=>true,'delete'=>true,'import'=>true,'export'=>true,'title'=>$clang->gT("Tokens"),'description'=>$clang->gT("Permission to create/update/delete/import/export token entries"),'img'=>'tokens'),
    'translations'=>array('create'=>false,'read'=>true,'update'=>true,'delete'=>false,'import'=>false,'export'=>false,'title'=>$clang->gT("Quick translation"),'description'=>$clang->gT("Permission to view & update the translations using the quick-translation feature"),'img'=>'translate')
    );
    uasort($aPermissions,"aComparePermission");
    return $aPermissions;
}

/**
* Simple function to sort the permissions by title
*
* @param mixed $aPermissionA  Permission A to compare
* @param mixed $aPermissionB  Permission B to compare
*/
function aComparePermission($aPermissionA,$aPermissionB)
{
    if($aPermissionA['title'] >$aPermissionB['title']) {
        return 1;
    }
    else {
        return -1;
    }
}

/**
* getqtypelist() Returns list of question types available in LimeSurvey. Edit this if you are adding a new
*    question type
*
* @global string $publicurl
* @global string $sourcefrom
*
* @param string $SelectedCode Value of the Question Type (defaults to "T")
* @param string $ReturnType Type of output from this function (defaults to selector)
*
* @return depending on $ReturnType param, returns a straight "array" of question types, or an <option></option> list
*
* Explanation of questiontype array:
*
* description : Question description
* subquestions : 0= Does not support subquestions x=Number of subquestion scales
* answerscales : 0= Does not need answers x=Number of answer scales (usually 1, but e.g. for dual scale question set to 2)
* assessable : 0=Does not support assessment values when editing answerd 1=Support assessment values
*/
function getqtypelist($SelectedCode = "T", $ReturnType = "selector")
{
    global $publicurl;
    global $sourcefrom, $clang;

    if (!isset($clang))
    {
        $clang = new limesurvey_lang("en");
    }
    $group['Arrays'] = $clang->gT('Arrays');
    $group['MaskQuestions'] = $clang->gT("Mask questions");
    $group['SinChoiceQues'] = $clang->gT("Single choice questions");
    $group['MulChoiceQues'] = $clang->gT("Multiple choice questions");
    $group['TextQuestions'] = $clang->gT("Text questions");


    $qtypes = array(
    "1"=>array('description'=>$clang->gT("Array dual scale"),
    'group'=>$group['Arrays'],
    'subquestions'=>1,
    'assessable'=>1,
    'hasdefaultvalues'=>0,
    'answerscales'=>2),
    "5"=>array('description'=>$clang->gT("5 Point Choice"),
    'group'=>$group['SinChoiceQues'],
    'subquestions'=>0,
    'hasdefaultvalues'=>0,
    'assessable'=>0,
    'answerscales'=>0),
    "A"=>array('description'=>$clang->gT("Array (5 Point Choice)"),
    'group'=>$group['Arrays'],
    'subquestions'=>1,
    'hasdefaultvalues'=>0,
    'assessable'=>1,
    'answerscales'=>0),
    "B"=>array('description'=>$clang->gT("Array (10 Point Choice)"),
    'group'=>$group['Arrays'],
    'subquestions'=>1,
    'hasdefaultvalues'=>0,
    'assessable'=>1,
    'answerscales'=>0),
    "C"=>array('description'=>$clang->gT("Array (Yes/No/Uncertain)"),
    'group'=>$group['Arrays'],
    'subquestions'=>1,
    'hasdefaultvalues'=>0,
    'assessable'=>1,
    'answerscales'=>0),
    "D"=>array('description'=>$clang->gT("Date"),
    'group'=>$group['MaskQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>1,
    'assessable'=>0,
    'answerscales'=>0),
    "E"=>array('description'=>$clang->gT("Array (Increase/Same/Decrease)"),
    'group'=>$group['Arrays'],
    'subquestions'=>1,
    'hasdefaultvalues'=>0,
    'assessable'=>1,
    'answerscales'=>0),
    "F"=>array('description'=>$clang->gT("Array"),
    'group'=>$group['Arrays'],
    'subquestions'=>1,
    'hasdefaultvalues'=>0,
    'assessable'=>1,
    'answerscales'=>1),
    "G"=>array('description'=>$clang->gT("Gender"),
    'group'=>$group['MaskQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>0,
    'assessable'=>0,
    'answerscales'=>0),
    "H"=>array('description'=>$clang->gT("Array by column"),
    'group'=>$group['Arrays'],
    'hasdefaultvalues'=>0,
    'subquestions'=>1,
    'assessable'=>1,
    'answerscales'=>1),
    "I"=>array('description'=>$clang->gT("Language Switch"),
    'group'=>$group['MaskQuestions'],
    'hasdefaultvalues'=>0,
    'subquestions'=>0,
    'assessable'=>0,
    'answerscales'=>0),
    "K"=>array('description'=>$clang->gT("Multiple Numerical Input"),
    'group'=>$group['MaskQuestions'],
    'hasdefaultvalues'=>1,
    'subquestions'=>1,
    'assessable'=>1,
    'answerscales'=>0),
    "L"=>array('description'=>$clang->gT("List (Radio)"),
    'group'=>$group['SinChoiceQues'],
    'subquestions'=>0,
    'hasdefaultvalues'=>1,
    'assessable'=>1,
    'answerscales'=>1),
    "M"=>array('description'=>$clang->gT("Multiple choice"),
    'group'=>$group['MulChoiceQues'],
    'subquestions'=>1,
    'hasdefaultvalues'=>1,
    'assessable'=>1,
    'answerscales'=>0),
    "N"=>array('description'=>$clang->gT("Numerical Input"),
    'group'=>$group['MaskQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>1,
    'assessable'=>0,
    'answerscales'=>0),
    "O"=>array('description'=>$clang->gT("List with comment"),
    'group'=>$group['SinChoiceQues'],
    'subquestions'=>0,
    'hasdefaultvalues'=>1,
    'assessable'=>1,
    'answerscales'=>1),
    "P"=>array('description'=>$clang->gT("Multiple choice with comments"),
    'group'=>$group['MulChoiceQues'],
    'subquestions'=>1,
    'hasdefaultvalues'=>1,
    'assessable'=>1,
    'answerscales'=>0),
    "Q"=>array('description'=>$clang->gT("Multiple Short Text"),
    'group'=>$group['TextQuestions'],
    'subquestions'=>1,
    'hasdefaultvalues'=>1,
    'assessable'=>0,
    'answerscales'=>0),
    "R"=>array('description'=>$clang->gT("Ranking"),
    'group'=>$group['MaskQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>0,
    'assessable'=>1,
    'answerscales'=>1),
    "S"=>array('description'=>$clang->gT("Short Free Text"),
    'group'=>$group['TextQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>1,
    'assessable'=>0,
    'answerscales'=>0),
    "T"=>array('description'=>$clang->gT("Long Free Text"),
    'group'=>$group['TextQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>1,
    'assessable'=>0,
    'answerscales'=>0),
    "U"=>array('description'=>$clang->gT("Huge Free Text"),
    'group'=>$group['TextQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>1,
    'assessable'=>0,
    'answerscales'=>0),
    "X"=>array('description'=>$clang->gT("Text display"),
    'group'=>$group['MaskQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>0,
    'assessable'=>0,
    'answerscales'=>0),
    "Y"=>array('description'=>$clang->gT("Yes/No"),
    'group'=>$group['MaskQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>0,
    'assessable'=>0,
    'answerscales'=>0),
    "!"=>array('description'=>$clang->gT("List (Dropdown)"),
    'group'=>$group['SinChoiceQues'],
    'subquestions'=>0,
    'hasdefaultvalues'=>1,
    'assessable'=>1,
    'answerscales'=>1),
    ":"=>array('description'=>$clang->gT("Array (Numbers)"),
    'group'=>$group['Arrays'],
    'subquestions'=>2,
    'hasdefaultvalues'=>0,
    'assessable'=>1,
    'answerscales'=>0),
    ";"=>array('description'=>$clang->gT("Array (Texts)"),
    'group'=>$group['Arrays'],
    'subquestions'=>2,
    'hasdefaultvalues'=>0,
    'assessable'=>0,
    'answerscales'=>0),
    "|"=>array('description'=>$clang->gT("File upload"),
    'group'=>$group['MaskQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>0,
    'assessable'=>0,
    'answerscales'=>0),
    "*"=>array('description'=>$clang->gT("Equation"),
    'group'=>$group['MaskQuestions'],
    'subquestions'=>0,
    'hasdefaultvalues'=>0,
    'assessable'=>0,
    'answerscales'=>0),
    );
    asort($qtypes);
    if ($ReturnType == "array") {return $qtypes;}
    if ($ReturnType == "group"){
        foreach($qtypes as $qkey=>$qtype){
            $newqType[$qtype['group']][$qkey] = $qtype;
        }


        $qtypeselecter = "";
        foreach($newqType as $group=>$members)
        {
            $qtypeselecter .= '<optgroup label="'.$group.'">';
            foreach($members as $TypeCode=>$TypeProperties){
                $qtypeselecter .= "<option value='$TypeCode'";
                if ($SelectedCode == $TypeCode) {$qtypeselecter .= " selected='selected'";}
                $qtypeselecter .= ">{$TypeProperties['description']}</option>\n";
            }
            $qtypeselecter .= '</optgroup>';
        }

        return $qtypeselecter;

    };
    $qtypeselecter = "";
    foreach($qtypes as $TypeCode=>$TypeProperties)
    {
        $qtypeselecter .= "<option value='$TypeCode'";
        if ($SelectedCode == $TypeCode) {$qtypeselecter .= " selected='selected'";}
        $qtypeselecter .= ">{$TypeProperties['description']}</option>\n";
    }
    return $qtypeselecter;
}

/**
* isStandardTemplate returns true if a template is a standard template
* This function does not check if a template actually exists
*
* @param mixed $sTemplateName template name to look for
* @return bool True if standard template, otherwise false
*/
function isStandardTemplate($sTemplateName)
{
    return in_array($sTemplateName,array('skeletonquest','quexs',
    'basic',
    'bluengrey',
    'business_grey',
    'citronade',
    'clear_logo',
    'default',
    'eirenicon',
    'limespired',
    'mint_idea',
    'sherpa',
    'vallendar'));
}


function &db_execute_num($sql,$inputarr=false)
{
    global $connect;

    $connect->SetFetchMode(ADODB_FETCH_NUM);
    $dataset=$connect->Execute($sql,$inputarr);  //Checked
    return $dataset;
}

function &db_select_limit_num($sql,$numrows=-1,$offset=-1,$inputarr=false)
{
    global $connect;

    $connect->SetFetchMode(ADODB_FETCH_NUM);
    $dataset=$connect->SelectLimit($sql,$numrows,$offset,$inputarr=false) or safe_die($sql);
    return $dataset;
}

function &db_execute_assoc($sql,$inputarr=false,$silent=false)
{
    global $connect;

    $connect->SetFetchMode(ADODB_FETCH_ASSOC);
    $dataset=$connect->Execute($sql,$inputarr);    //Checked
    if (!$silent && !$dataset)  {safe_die($connect->ErrorMsg().':'.$sql);}
    return $dataset;
}

function &db_select_limit_assoc($sql,$numrows=-1,$offset=-1,$inputarr=false,$dieonerror=true)
{
    global $connect;

    $connect->SetFetchMode(ADODB_FETCH_ASSOC);
    $dataset=$connect->SelectLimit($sql,$numrows,$offset,$inputarr=false);
    if (!$dataset && $dieonerror) {safe_die($connect->ErrorMsg().':'.$sql);}
    return $dataset;
}

/**
* Returns the first row of values of the $sql query result
* as a 1-dimensional array
*
* @param mixed $sql
*/
function &db_select_column($sql)
{
    global $connect;

    $connect->SetFetchMode(ADODB_FETCH_NUM);
    $dataset=$connect->Execute($sql);
    $resultarray=array();
    while ($row = $dataset->fetchRow()) {
        $resultarray[]=$row[0];
    }
    return $resultarray;
}


/**
* This functions quotes fieldnames accordingly
*
* @param mixed $id Fieldname to be quoted
*/
function db_quote_id($id)
{
    global $databasetype;
    // WE DONT HAVE nor USE other thing that alphanumeric characters in the field names
    //  $quote = $connect->nameQuote;
    //  return $quote.str_replace($quote,$quote.$quote,$id).$quote;

    switch ($databasetype)
    {
        case "mysqli" :
        case "mysql" :
            return "`".$id."`";
            break;
        case "mssql_n" :
        case "odbtp" :
        case "mssqlnative" :
        case "odbc_mssql" :
            return "[".$id."]";
            break;
        case "postgres":
            return "\"".$id."\"";
            break;
        default:
            return "`".$id."`";
    }
}

function db_random()
{
    global $connect,$databasetype;
    if ($databasetype=='odbc_mssql' || $databasetype=='mssql_n' || $databasetype=='odbtp')  {$srandom='NEWID()';}
    else {$srandom=$connect->random;}
    return $srandom;

}

function db_quote($str,$ispostvar=false)
// This functions escapes the string only inside
{
    global $connect;
    if ($ispostvar) { return $connect->escape($str, get_magic_quotes_gpc());}
    else {return $connect->escape($str);}
}

function db_quoteall($str,$ispostvar=false)
// This functions escapes the string inside and puts quotes around the string according to the used db type
// IF you are quoting a variable from a POST/GET then set $ispostvar to true so it doesnt get quoted twice.
{
    global $connect;
    if ($ispostvar) { return $connect->qstr($str, get_magic_quotes_gpc());}
    else {return $connect->qstr($str);}

}

function db_table_name($name)
{
    global $dbprefix;
    return db_quote_id($dbprefix.$name);
}

/**
* returns the table name without quotes
*
* @param mixed $name
*/
function db_table_name_nq($name)
{
    global $dbprefix;
    return $dbprefix.$name;
}

/**
*  Return a sql statement for finding LIKE named tables
*  Be aware that you have to escape underscor chars by using a backslash
* otherwise you might get table names returned you don't want
*
* @param mixed $table
*/
function db_select_tables_like($table)
{
    global $databasetype;
    switch ($databasetype) {
        case 'mysqli':
        case 'mysql' :
            return "SHOW TABLES LIKE '$table'";
        case 'odbtp' :
        case 'mssql_n' :
        case 'mssqlnative':
        case 'odbc_mssql' :
            return "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_TYPE='BASE TABLE' and TABLE_NAME LIKE '$table' ESCAPE '\'";
        case 'postgres' :
            $table=str_replace('\\','\\\\',$table);
            return "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' and table_name like '$table'";
        default: safe_die ("Couldn't create 'select tables like' query for connection type 'databaseType'");
    }
}

/**
*  Return a boolean stating if the table(s) exist(s)
*  Accepts '%' in names since it uses the 'like' statement
*
* @param mixed $table
*/
function db_tables_exist($table)
{
    global $connect;

    $surveyHasTokensTblQ = db_select_tables_like("$table");
    $surveyHasTokensTblResult = db_execute_num($surveyHasTokensTblQ); //Checked

    if ($surveyHasTokensTblResult->RecordCount() >= 1)
    {
        return TRUE;
    }
    else
    {
        return FALSE;
    }
}

/**
* getsurveylist() Queries the database (survey table) for a list of existing surveys
*
* @param mixed $returnarray   boolean - if set to true an array instead of an HTML option list is given back
*
* @global string $surveyid
* @global string $dbprefix
* @global string $scriptname
* @global string $connect
* @global string $clang
*
* @return string This string is returned containing <option></option> formatted list of existing surveys
*
*/
function getsurveylist($returnarray=false,$returnwithouturl=false)
{
    global $surveyid, $dbprefix, $scriptname, $connect, $clang, $timeadjust;
    static $cached = null;

    if(is_null($cached)) {
        $surveyidquery = " SELECT a.*, surveyls_title, surveyls_description, surveyls_welcometext, surveyls_url "
        ." FROM ".db_table_name('surveys')." AS a "
        . "INNER JOIN ".db_table_name('surveys_languagesettings')." on (surveyls_survey_id=a.sid and surveyls_language=a.language) ";

        if (!bHasGlobalPermission('USER_RIGHT_SUPERADMIN'))
        {
            $surveyidquery .= "WHERE a.sid in (select sid from ".db_table_name('survey_permissions')." where uid={$_SESSION['loginID']} and permission='survey' and read_p=1) ";
        }

        $surveyidquery .= " order by active DESC, surveyls_title";
        $surveyidresult = db_execute_assoc($surveyidquery);  //Checked
        if (!$surveyidresult) {return "Database Error";}
        $surveynames = $surveyidresult->GetRows();
        $cached=$surveynames;
    } else {
        $surveynames = $cached;
    }
    $surveyselecter = "";
    if ($returnarray===true) return $surveynames;
    $activesurveys='';
    $inactivesurveys='';
    $expiredsurveys='';
    if ($surveynames)
    {
        foreach($surveynames as $sv)
        {

            $surveylstitle=FlattenText($sv['surveyls_title']);
            if (strlen($surveylstitle)>45)
            {
                $surveylstitle = htmlspecialchars(mb_strcut(html_entity_decode($surveylstitle,ENT_QUOTES,'UTF-8'), 0, 45, 'UTF-8'))."...";
            }

            if($sv['active']!='Y')
            {
                $inactivesurveys .= "<option ";
                if($_SESSION['loginID'] == $sv['owner_id'])
                {
                    $inactivesurveys .= " style=\"font-weight: bold;\"";
                }
                if ($sv['sid'] == $surveyid)
                {
                    $inactivesurveys .= " selected='selected'"; $svexist = 1;
                }
                if ($returnwithouturl===false)
                {
                    $inactivesurveys .=" value='$scriptname?sid={$sv['sid']}'>{$surveylstitle}</option>\n";
                } else
                {
                    $inactivesurveys .=" value='{$sv['sid']}'>{$surveylstitle}</option>\n";
                }
            } elseif($sv['expires']!='' && $sv['expires'] < date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust))
            {
                $expiredsurveys .="<option ";
                if ($_SESSION['loginID'] == $sv['owner_id'])
                {
                    $expiredsurveys .= " style=\"font-weight: bold;\"";
                }
                if ($sv['sid'] == $surveyid)
                {
                    $expiredsurveys .= " selected='selected'"; $svexist = 1;
                }
                if ($returnwithouturl===false)
                {
                    $expiredsurveys .=" value='$scriptname?sid={$sv['sid']}'>{$surveylstitle}</option>\n";
                } else
                {
                    $expiredsurveys .=" value='{$sv['sid']}'>{$surveylstitle}</option>\n";
                }
            } else
            {
                $activesurveys .= "<option ";
                if($_SESSION['loginID'] == $sv['owner_id'])
                {
                    $activesurveys .= " style=\"font-weight: bold;\"";
                }
                if ($sv['sid'] == $surveyid)
                {
                    $activesurveys .= " selected='selected'"; $svexist = 1;
                }
                if ($returnwithouturl===false)
                {
                    $activesurveys .=" value='$scriptname?sid={$sv['sid']}'>{$surveylstitle}</option>\n";
                } else
                {
                    $activesurveys .=" value='{$sv['sid']}'>{$surveylstitle}</option>\n";
                }
            }
        } // End Foreach
    }
    //Only show each activesurvey group if there are some
    if ($activesurveys!='')
    {
        $surveyselecter .= "<optgroup label='".$clang->gT("Active")."' class='activesurveyselect'>\n";
        $surveyselecter .= $activesurveys . "</optgroup>";
    }
    if ($expiredsurveys!='')
    {
        $surveyselecter .= "<optgroup label='".$clang->gT("Expired")."' class='expiredsurveyselect'>\n";
        $surveyselecter .= $expiredsurveys . "</optgroup>";
    }
    if ($inactivesurveys!='')
    {
        $surveyselecter .= "<optgroup label='".$clang->gT("Inactive")."' class='inactivesurveyselect'>\n";
        $surveyselecter .= $inactivesurveys . "</optgroup>";
    }
    if (!isset($svexist))
    {
        $surveyselecter = "<option selected='selected' value=''>".$clang->gT("Please choose...")."</option>\n".$surveyselecter;
    } else
    {
        if ($returnwithouturl===false)
        {
            $surveyselecter = "<option value='$scriptname?sid='>".$clang->gT("None")."</option>\n".$surveyselecter;
        } else
        {
            $surveyselecter = "<option value=''>".$clang->gT("None")."</option>\n".$surveyselecter;
        }
    }
    return $surveyselecter;
}

/**
* getQuestions() queries the database for an list of all questions matching the current survey and group id
*
* @global string $surveyid
* @global string $gid
* @global string $selectedqid
*
* @return This string is returned containing <option></option> formatted list of questions in the current survey and group
*/
function getQuestions($surveyid,$gid,$selectedqid)
{
    global $scriptname, $clang;

    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $qquery = 'SELECT * FROM '.db_table_name('questions')." WHERE sid=$surveyid AND gid=$gid AND language='{$s_lang}' and parent_qid=0 order by question_order";
    $qresult = db_execute_assoc($qquery); //checked
    $qrows = $qresult->GetRows();

    if (!isset($questionselecter)) {$questionselecter="";}
    foreach ($qrows as $qrow)
    {
        $qrow['title'] = strip_tags($qrow['title']);
        $questionselecter .= "<option value='$scriptname?sid=$surveyid&amp;gid=$gid&amp;qid={$qrow['qid']}'";
        if ($selectedqid == $qrow['qid']) {$questionselecter .= " selected='selected'"; $qexists="Y";}
        $questionselecter .=">{$qrow['title']}:";
        $questionselecter .= " ";
        $question=FlattenText($qrow['question']);
        if (strlen($question)<35)
        {
            $questionselecter .= $question;
        }
        else
        {
            $questionselecter .= htmlspecialchars(mb_strcut(html_entity_decode($question,ENT_QUOTES,'UTF-8'), 0, 35, 'UTF-8'))."...";
        }
        $questionselecter .= "</option>\n";
    }

    if (!isset($qexists))
    {
        $questionselecter = "<option selected='selected'>".$clang->gT("Please choose...")."</option>\n".$questionselecter;
    }
    return $questionselecter;
}

/**
* getGidPrevious() returns the Gid of the group prior to the current active group
*
* @param string $surveyid
* @param string $gid
*
* @return The Gid of the previous group
*/
function getGidPrevious($surveyid, $gid)
{
    global $scriptname, $clang;

    if (!$surveyid) {$surveyid=returnglobal('sid');}
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $gquery = "SELECT gid FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='{$s_lang}' ORDER BY group_order";
    $qresult = db_execute_assoc($gquery); //checked
    $qrows = $qresult->GetRows();

    $i = 0;
    $iPrev = -1;
    foreach ($qrows as $qrow)
    {
        if ($gid == $qrow['gid']) {$iPrev = $i - 1;}
        $i += 1;
    }
    if ($iPrev >= 0) {$GidPrev = $qrows[$iPrev]['gid'];}
    else {$GidPrev = "";}
    return $GidPrev;
}

/**
* getQidPrevious() returns the Qid of the question prior to the current active question
*
* @param string $surveyid
* @param string $gid
* @param string $qid
*
* @return This Qid of the previous question
*/
function getQidPrevious($surveyid, $gid, $qid)
{
    global $scriptname, $clang;
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $qquery = 'SELECT * FROM '.db_table_name('questions')." WHERE sid=$surveyid AND gid=$gid AND language='{$s_lang}' and parent_qid=0 order by question_order";
    $qresult = db_execute_assoc($qquery); //checked
    $qrows = $qresult->GetRows();

    $i = 0;
    $iPrev = -1;
    foreach ($qrows as $qrow)
    {
        if ($qid == $qrow['qid']) {$iPrev = $i - 1;}
        $i += 1;
    }
    if ($iPrev >= 0) {$QidPrev = $qrows[$iPrev]['qid'];}
    else {$QidPrev = "";}
    return $QidPrev;
}

/**
* getGidNext() returns the Gid of the group next to the current active group
*
* @param string $surveyid
* @param string $gid
*
* @return The Gid of the next group
*/
function getGidNext($surveyid, $gid)
{
    global $scriptname, $clang;

    if (!$surveyid) {$surveyid=returnglobal('sid');}
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $gquery = "SELECT gid FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='{$s_lang}' ORDER BY group_order";
    $qresult = db_execute_assoc($gquery); //checked
    $qrows = $qresult->GetRows();

    $GidNext="";
    $i = 0;
    $iNext = 1;
    foreach ($qrows as $qrow)
    {
        if ($gid == $qrow['gid']) {$iNext = $i + 1;}
        $i += 1;
    }
    if ($iNext < count($qrows)) {$GidNext = $qrows[$iNext]['gid'];}
    else {$GidNext = "";}
    return $GidNext;
}

/**
* getQidNext() returns the Qid of the question prior to the current active question
*
* @param string $surveyid
* @param string $gid
* @param string $qid
*
* @return This Qid of the previous question
*/
function getQidNext($surveyid, $gid, $qid)
{
    global $scriptname, $clang;
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $qquery = 'SELECT qid FROM '.db_table_name('questions')." WHERE sid=$surveyid AND gid=$gid AND language='{$s_lang}' and parent_qid=0 order by question_order";
    $qresult = db_execute_assoc($qquery); //checked
    $qrows = $qresult->GetRows();

    $i = 0;
    $iNext = 1;
    foreach ($qrows as $qrow)
    {
        if ($qid == $qrow['qid']) {$iNext = $i + 1;}
        $i += 1;
    }
    if ($iNext < count($qrows)) {$QidNext = $qrows[$iNext]['qid'];}
    else {$QidNext = "";}
    return $QidNext;
}

/**
* This function calculates how much space is actually used by all files uploaded
* using the File Upload question type
*
* @returns integer Actual space used in MB
*/
function fCalculateTotalFileUploadUsage(){
    global $uploaddir;
    $sQuery="select sid from ".db_table_name('surveys');
    $oResult = db_execute_assoc($sQuery); //checked
    $aRows = $oResult->GetRows();
    $iTotalSize=0.0;
    foreach ($aRows as $aRow)
    {
        $sFilesPath=$uploaddir.'/surveys/'.$aRow['sid'].'/files';
        if (file_exists($sFilesPath))
        {
            $iTotalSize+=(float)iGetDirectorySize($sFilesPath);
        }
    }
    return (float)$iTotalSize/1024/1024;
}

function iGetDirectorySize($directory) {
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
        $size+=$file->getSize();
    }
    return $size;
}

/**
* Gets number of groups inside a particular survey
*
* @param string $surveyid
* @param mixed $lang
*/
function getGroupSum($surveyid, $lang)
{
    global $surveyid,$dbprefix ;
    $sumquery3 = "SELECT * FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='".$lang."'"; //Getting a count of questions for this survey

    $sumresult3 = db_execute_assoc($sumquery3); //Checked
    $groupscount = $sumresult3->RecordCount();

    return $groupscount ;
}


/**
* Gets number of questions inside a particular group
*
* @param string $surveyid
* @param mixed $groupid
*/
function getQuestionSum($surveyid, $groupid)
{
    global $surveyid,$dbprefix ;
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $sumquery3 = "SELECT * FROM ".db_table_name('questions')." WHERE gid=$groupid and sid=$surveyid AND language='{$s_lang}'"; //Getting a count of questions for this survey
    $sumresult3 = db_execute_assoc($sumquery3); //Checked
    $questionscount = $sumresult3->RecordCount();
    return $questionscount ;
}


/**
* getMaxgrouporder($surveyid) queries the database for the maximum sortorder of a group and returns the next higher one.
*
* @param mixed $surveyid
* @global string $surveyid
*/
function getMaxgrouporder($surveyid)
{
    global $surveyid, $connect ;
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $max_sql = "SELECT max( group_order ) AS max FROM ".db_table_name('groups')." WHERE sid =$surveyid AND language='{$s_lang}'" ;
    $current_max = $connect->GetOne($max_sql) ;
    if(is_null($current_max))
    {
        return "0" ;
    }
    else return ++$current_max ;
}


/**
* getGroupOrder($surveyid,$gid) queries the database for the sortorder of a group.
*
* @param mixed $surveyid
* @param mixed $gid
* @return mixed
*/
function getGroupOrder($surveyid,$gid)
{
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $grporder_sql = "SELECT group_order FROM ".db_table_name('groups')." WHERE sid =$surveyid AND language='{$s_lang}' AND gid=$gid" ;
    $grporder_result =db_execute_assoc($grporder_sql); //Checked
    $grporder_row = $grporder_result->FetchRow() ;
    $group_order = $grporder_row['group_order'];
    if($group_order=="")
    {
        return "0" ;
    }
    else return $group_order ;
}

/**
* getMaxquestionorder($gid) queries the database for the maximum sortorder of a question.
*
* @global string $surveyid
*/
function getMaxquestionorder($gid)
{
    global $surveyid ;
    $gid=sanitize_int($gid);
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $max_sql = "SELECT max( question_order ) AS max FROM ".db_table_name('questions')." WHERE gid='$gid' AND language='$s_lang'";

    $max_result =db_execute_assoc($max_sql) ; //Checked
    $maxrow = $max_result->FetchRow() ;
    $current_max = $maxrow['max'];
    if($current_max=="")
    {
        return "0" ;
    }
    else return $current_max ;
}

/**
* question_class() returns a class name for a given question type to allow custom styling for each question type.
*
* @param string $input containing unique character representing each question type.
* @return string containing the class name for a given question type.
*/
function question_class($input)
{

    switch($input)
    {   // I think this is a bad solution to adding classes to question
        // DIVs but I can't think of a better solution. (eric_t_cruiser)

        case 'X': return 'boilerplate';     //  BOILERPLATE QUESTION
        case '5': return 'choice-5-pt-radio';   //  5 POINT CHOICE radio-buttons
        case 'D': return 'date';        //  DATE
//        case 'Z': return 'list-radio-flexible'; //  LIST Flexible radio-button
        case 'L': return 'list-radio';      //  LIST radio-button
//        case 'W': return 'list-dropdown-flexible'; //   LIST drop-down (flexible label)
        case '!': return 'list-dropdown';   //  List - dropdown
        case 'O': return 'list-with-comment';   //  LIST radio-button + textarea
        case 'R': return 'ranking';     //  RANKING STYLE
        case 'M': return 'multiple-opt';    //  Multiple choice checkbox
        case 'I': return 'language';        //  Language Question
        case 'P': return 'multiple-opt-comments'; //    Multiple choice with comments checkbox + text
        case 'Q': return 'multiple-short-txt';  //  TEXT
        case 'K': return 'numeric-multi';   //  MULTIPLE NUMERICAL QUESTION
        case 'N': return 'numeric';     //  NUMERICAL QUESTION TYPE
        case 'S': return 'text-short';      //  SHORT FREE TEXT
        case 'T': return 'text-long';       //  LONG FREE TEXT
        case 'U': return 'text-huge';       //  HUGE FREE TEXT
        case 'Y': return 'yes-no';      //  YES/NO radio-buttons
        case 'G': return 'gender';      //  GENDER drop-down list
        case 'A': return 'array-5-pt';      //  ARRAY (5 POINT CHOICE) radio-buttons
        case 'B': return 'array-10-pt';     //  ARRAY (10 POINT CHOICE) radio-buttons
        case 'C': return 'array-yes-uncertain-no'; //   ARRAY (YES/UNCERTAIN/NO) radio-buttons
        case 'E': return 'array-increase-same-decrease'; // ARRAY (Increase/Same/Decrease) radio-buttons
        case 'F': return 'array-flexible-row';  //  ARRAY (Flexible) - Row Format
        case 'H': return 'array-flexible-column'; //    ARRAY (Flexible) - Column Format
            //      case '^': return 'slider';          //  SLIDER CONTROL
        case ':': return 'array-multi-flexi';   //  ARRAY (Multi Flexi) 1 to 10
        case ";": return 'array-multi-flexi-text';
        case "1": return 'array-flexible-duel-scale'; //    Array dual scale
        case "*": return 'equation';    // Equation
        default:  return 'generic_question';    //  Should have a default fallback
    };
};

function setup_columns($columns, $answer_count)
{
    /**
    * setup_columns() defines all the html tags to be wrapped around
    * various list type answers.
    *
    * @param integer $columns - the number of columns, usually supplied by $dcols
    * @param integer $answer_count - the number of answers to a question, usually supplied by $anscount
    * @return array with all the various opening and closing tags to generate a set of columns.
    *
    * It returns an array with the following items:
    *    $wrapper['whole-start']   = Opening wrapper for the whole list
    *    $wrapper['whole-end']     = closing wrapper for the whole list
    *    $wrapper['col-devide']    = normal column devider
    *    $wrapper['col-devide-last'] = the last column devider (to allow
    *                                for different styling of the last
    *                                column
    *    $wrapper['item-start']    = opening wrapper tag for individual
    *                                option
    *    $wrapper['item-start-other'] = opening wrapper tag for other
    *                                option
    *    $wrapper['item-end']      = closing wrapper tag for individual
    *                                option
    *    $wrapper['maxrows']       = maximum number of rows in each
    *                                column
    *    $wrapper['cols']          = Number of columns to be inserted
    *                                (and checked against)
    *
    * It also expect the global parameter $column_style
    * initialised at the end of config-defaults.php or from within config.php
    *
    * - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    * Columns are a problem.
    * Really there is no perfect solution to columns at the moment.
    *
    * -  Using Tables is problematic semanticly.
    * -  Using inline or float to create columns, causes the answers
    *    flows horizontally, not vertically which is not ideal visually.
    * -  Using CSS3 columns is also a problem because of browser support
    *    and also because if you have answeres split across two or more
    *    lines, and those answeres happen to fall at the bottom of a
    *    column, the answer might be split across columns as well as
    *    lines.
    * -  Using nested unordered list with the first level of <LI>s
    *    floated is the same as using tables and so is bad semantically
    *    for the same reason tables are bad.
    * -  Breaking the unordered lists into consecutive floated unordered
    *    lists is not great semantically but probably not as bad as
    *    using tables.
    *
    * Because I haven't been able to decide which option is the least
    * bad, I have handed over that responsibility to the admin who sets
    * LimeSurvey up on their server.
    *
    * There are four options:
    *    'css'   using one of the various CSS only methods for
    *            rendering columns.
    *            (Check the CSS file for your chosen template to see
    *             how columns are defined.)
    *    'ul'    using multiple floated unordered lists. (DEFAULT)
    *    'table' using conventional tables based layout.
    *     NULL   blocks the use of columns
    *
    * 'ul' is the default because it's the best possible compromise
    * between semantic markup and visual layout.
    */


    global $column_style;
    if ( !in_array($column_style,array('css','ul','table')) && !is_null($column_style) )
    {
        $column_style = 'ul';
    };

    /*
    if(defined('PRINT_TEMPLATE')) // This forces tables based columns for printablesurvey
    {
    $colstyle = 'table';
    };
    */
    if($columns < 2)
    {
        $column_style = null;
        $columns = 1;
    }

    if(($columns > $answer_count) && $answer_count>0)
    {
        $columns = $answer_count;
    };

    if ($answer_count>0 && $columns>0)
    {
        $columns = ceil($answer_count/ceil($answer_count/$columns)); // # of columns is # of answers divided by # of rows (all rounded up)
    }

    $class_first = '';
    if($columns > 1 && !is_null($column_style))
    {
        if($column_style == 'ul')
        {
            $ul = '-ul';
        }
        else
        {
            $ul = '';
        }
        $class_first = ' class="cols-'.$columns . $ul.' first"';
        $class = ' class="cols-'.$columns . $ul.'"';
        $class_last_ul = ' class="cols-'.$columns . $ul.' last"';
        $class_last_table = ' class="cols-'.$columns.' last"';
    }
    else
    {
        $class = '';
        $class_last_ul = '';
        $class_last_table = '';
    };

    $wrapper = array(
    'whole-start'  => "\n<ul$class_first>\n"
    ,'whole-end'    => "</ul>\n"
    ,'col-devide'   => ''
    ,'col-devide-last' => ''
    ,'item-start'   => "\t<li>\n"
    ,'item-start-other' => "\t<li class=\"other\">\n"
    ,'item-end' => "\t</li>\n"
    ,'maxrows'  => ceil($answer_count/$columns) //Always rounds up to nearest whole number
    ,'cols'     => $columns
    );

    switch($column_style)
    {
        case 'ul':  if($columns > 1)
            {
                $wrapper['col-devide']  = "\n</ul>\n\n<ul$class>\n";
                $wrapper['col-devide-last'] = "\n</ul>\n\n<ul$class_last_ul>\n";
            }
            break;

        case 'table':   $table_cols = '';
            for($cols = $columns ; $cols > 0 ; --$cols)
            {
                switch($cols)
                {
                    case $columns:  $table_cols .= "\t<col$class_first />\n";
                        break;
                    case 1:     $table_cols .= "\t<col$class_last_table />\n";
                        break;
                    default:    $table_cols .= "\t<col$class />\n";
                };
            };

            if($columns > 1)
            {
                $wrapper['col-devide']  = "\t</ul>\n</td>\n\n<td>\n\t<ul>\n";
                $wrapper['col-devide-last'] = "\t</ul>\n</td>\n\n<td class=\"last\">\n\t<ul>\n";
            };
            $wrapper['whole-start'] = "\n<table$class>\n$table_cols\n\t<tbody>\n<tr>\n<td>\n\t<ul>\n";
            $wrapper['whole-end']   = "\t</ul>\n</td>\n</tr>\n\t</tbody>\n</table>\n";
            $wrapper['item-start']  = "<li>\n";
            $wrapper['item-end']    = "</li>\n";
    };

    return $wrapper;
};

function alternation($alternate = '' , $type = 'col')
{
    /**
    * alternation() Returns a class identifyer for alternating between
    * two options. Used to style alternate elements differently. creates
    * or alternates between the odd string and the even string used in
    * as column and row classes for array type questions.
    *
    * @param string $alternate = '' (empty) (default) , 'array2' ,  'array1' , 'odd' , 'even'
    * @param string  $type = 'col' (default) or 'row'
    *
    * @return string representing either the first alternation or the opposite alternation to the one supplied..
    */
    /*
    // The following allows type to be left blank for row in subsequent
    // function calls.
    // It has been left out because 'row' must be defined the first time
    // alternation() is called. Since it is only ever written once for each
    // while statement within a function, 'row' is always defined.
    if(!empty($alternate) && $type != 'row')
    {   if($alternate == ('array2' || 'array1'))
    {
    $type = 'row';
    };
    };
    // It has been left in case it becomes useful but probably should be
    // removed.
    */
    if($type == 'row')
    {
        $odd  = 'array2'; // should be row_odd
        $even = 'array1'; // should be row_even
    }
    else
    {
        $odd  = 'odd';  // should be col_odd
        $even = 'even'; // should be col_even
    };
    if($alternate == $odd)
    {
        $alternate = $even;
    }
    else
    {
        $alternate = $odd;
    };
    return $alternate;
}


/**
* longest_string() returns the length of the longest string past to it.
* @peram string $new_string
* @peram integer $longest_length length of the (previously) longest string passed to it.
* @return integer representing the length of the longest string passed (updated if $new_string was longer than $longest_length)
*
* usage should look like this: $longest_length = longest_string( $new_string , $longest_length );
*
*/
function longest_string( $new_string , $longest_length )
{
    if($longest_length < strlen(trim(strip_tags($new_string))))
    {
        $longest_length = strlen(trim(strip_tags($new_string)));
    };
    return $longest_length;
};



/**
* getNotificationlist() returns different options for notifications
*
* @param string $notificationcode - the currently selected one
*
* @return This string is returned containing <option></option> formatted list of notification methods for current survey
*/
function getNotificationlist($notificationcode)
{
    global $clang;
    $ntypes = array(
    "0"=>$clang->gT("No email notification"),
    "1"=>$clang->gT("Basic email notification"),
    "2"=>$clang->gT("Detailed email notification with result codes")
    );
    if (!isset($ntypeselector)) {$ntypeselector="";}
    foreach($ntypes as $ntcode=>$ntdescription)
    {
        $ntypeselector .= "<option value='$ntcode'";
        if ($notificationcode == $ntcode) {$ntypeselector .= " selected='selected'";}
        $ntypeselector .= ">$ntdescription</option>\n";
    }
    return $ntypeselector;
}


/**
* getgrouplist() queries the database for a list of all groups matching the current survey sid
*
* @global string $surveyid
* @global string $dbprefix
* @global string $scriptname
*
* @param string $gid - the currently selected gid/group
*
* @return This string is returned containing <option></option> formatted list of groups to current survey
*/
function getgrouplist($gid)
{
    global $surveyid, $dbprefix, $scriptname, $connect, $clang;
    $groupselecter="";
    $gid=sanitize_int($gid);
    $surveyid=sanitize_int($surveyid);
    if (!$surveyid) {$surveyid=returnglobal('sid');}
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $gidquery = "SELECT gid, group_name FROM ".db_table_name('groups')." WHERE sid='{$surveyid}' AND  language='{$s_lang}'  ORDER BY group_order";
    $gidresult = db_execute_num($gidquery) or safe_die("Couldn't get group list in common.php<br />$gidquery<br />".$connect->ErrorMsg()); //Checked
    while($gv = $gidresult->FetchRow())
    {
        $groupselecter .= "<option";
        if ($gv[0] == $gid) {$groupselecter .= " selected='selected'"; $gvexist = 1;}
        $groupselecter .= " value='$scriptname?sid=$surveyid&amp;gid=$gv[0]'>".htmlspecialchars($gv[1])."</option>\n";
    }
    if ($groupselecter)
    {
        if (!isset($gvexist)) {$groupselecter = "<option selected='selected'>".$clang->gT("Please choose...")."</option>\n".$groupselecter;}
        else {$groupselecter .= "<option value='$scriptname?sid=$surveyid&amp;gid='>".$clang->gT("None")."</option>\n";}
    }
    return $groupselecter;
}


function getgrouplist2($gid)
{
    global $surveyid, $dbprefix, $connect, $clang;
    $groupselecter = "";
    if (!$surveyid) {$surveyid=returnglobal('sid');}
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $gidquery = "SELECT gid, group_name FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='{$s_lang}' ORDER BY group_order";


    $gidresult = db_execute_num($gidquery) or safe_die("Plain old did not work!");   //Checked
    while ($gv = $gidresult->FetchRow())
    {
        $groupselecter .= "<option";
        if ($gv[0] == $gid) {$groupselecter .= " selected='selected'"; $gvexist = 1;}
        $groupselecter .= " value='$gv[0]'>".htmlspecialchars($gv[1])."</option>\n";
    }
    if ($groupselecter)
    {
        if (!$gvexist) {$groupselecter = "<option selected='selected'>".$clang->gT("Please choose...")."</option>\n".$groupselecter;}
        else {$groupselecter .= "<option value=''>".$clang->gT("None")."</option>\n";}
    }
    return $groupselecter;
}


function getgrouplist3($gid)
{
    global $surveyid, $dbprefix;
    if (!$surveyid) {$surveyid=returnglobal('sid');}
    $groupselecter = "";
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $gidquery = "SELECT gid, group_name FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='{$s_lang}' ORDER BY group_order";


    $gidresult = db_execute_num($gidquery) or safe_die("Plain old did not work!");      //Checked
    while ($gv = $gidresult->FetchRow())
    {
        $groupselecter .= "<option";
        if ($gv[0] == $gid) {$groupselecter .= " selected='selected'"; $gvexist = 1;}
        $groupselecter .= " value='$gv[0]'>".htmlspecialchars($gv[1])."</option>\n";
    }
    return $groupselecter;
}

/**
* Gives back the name of a group for a certaing group id
*
* @param integer $gid Group ID
*/
function getgroupname($gid)
{
    global $surveyid;
    if (!$surveyid) {$surveyid=returnglobal('sid');}
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    $gidquery = "SELECT group_name FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='{$s_lang}' and gid=$gid";

    $gidresult = db_execute_num($gidquery) or safe_die("Group name could not be fetched (getgroupname).");      //Checked
    while ($gv = $gidresult->FetchRow())
    {
        $groupname = htmlspecialchars($gv[0]);
    }
    return $groupname;
}

/**
* put your comment there...
*
* @param mixed $gid
* @param mixed $language
*/
function getgrouplistlang($gid, $language)
{
    global $surveyid, $scriptname, $connect, $clang;

    $groupselecter="";
    if (!$surveyid) {$surveyid=returnglobal('sid');}
    $gidquery = "SELECT gid, group_name FROM ".db_table_name('groups')." WHERE sid=$surveyid AND language='".$language."' ORDER BY group_order";
    $gidresult = db_execute_num($gidquery) or safe_die("Couldn't get group list in common.php<br />$gidquery<br />".$connect->ErrorMsg());   //Checked
    while($gv = $gidresult->FetchRow())
    {
        $groupselecter .= "<option";
        if ($gv[0] == $gid) {$groupselecter .= " selected='selected'"; $gvexist = 1;}
        $groupselecter .= " value='$scriptname?sid=$surveyid&amp;gid=$gv[0]'>";
        if (strip_tags($gv[1]))
        {
            $groupselecter .= htmlspecialchars(strip_tags($gv[1]));
        } else {
            $groupselecter .= htmlspecialchars($gv[1]);
        }
        $groupselecter .= "</option>\n";
    }
    if ($groupselecter)
    {
        if (!isset($gvexist)) {$groupselecter = "<option selected='selected'>".$clang->gT("Please choose...")."</option>\n".$groupselecter;}
        else {$groupselecter .= "<option value='$scriptname?sid=$surveyid&amp;gid='>".$clang->gT("None")."</option>\n";}
    }
    return $groupselecter;
}


function getuserlist($outputformat='fullinfoarray')
{
    global $dbprefix, $connect, $databasetype;
    global $usercontrolSameGroupPolicy;

    if (isset($_SESSION['loginID']))
    {
        $myuid=sanitize_int($_SESSION['loginID']);
    }

    if ($_SESSION['USER_RIGHT_SUPERADMIN'] != 1 && isset($usercontrolSameGroupPolicy) &&
    $usercontrolSameGroupPolicy == true)
    {
        if (isset($myuid))
        {
            // List users from same group as me + all my childs
            // a subselect is used here because MSSQL does not like to group by text
            // also Postgres does like this one better
            $uquery = " SELECT * FROM ".db_table_name('users')." where uid in
            (SELECT u.uid FROM ".db_table_name('users')." AS u,
            ".db_table_name('user_in_groups')." AS ga ,".db_table_name('user_in_groups')." AS gb
            WHERE u.uid=$myuid
            OR (ga.ugid=gb.ugid AND ( (gb.uid=$myuid AND u.uid=ga.uid) OR (u.parent_id=$myuid) ) )
            GROUP BY u.uid)";
        }
        else
        {
            return Array(); // Or die maybe
        }

    }
    else
    {
        $uquery = "SELECT * FROM ".db_table_name('users')." ORDER BY uid";
    }

    $uresult = db_execute_assoc($uquery); //Checked

    if ($uresult->RecordCount()==0)
    //user is not in a group and usercontrolSameGroupPolicy is activated - at least show his own userinfo
    {
        $uquery = "SELECT u.* FROM ".db_table_name('users')." AS u WHERE u.uid=".$myuid;
        $uresult = db_execute_assoc($uquery);//Checked
    }

    $userlist = array();
    $userlist[0] = "Reserved for logged in user";
    while ($srow = $uresult->FetchRow())
    {
        if ($outputformat != 'onlyuidarray')
        {
            if ($srow['uid'] != $_SESSION['loginID'])
            {
                $userlist[] = array("user"=>$srow['users_name'], "uid"=>$srow['uid'], "email"=>$srow['email'], "password"=>$srow['password'], "full_name"=>$srow['full_name'], "parent_id"=>$srow['parent_id'], "create_survey"=>$srow['create_survey'], "configurator"=>$srow['configurator'], "create_user"=>$srow['create_user'], "delete_user"=>$srow['delete_user'], "superadmin"=>$srow['superadmin'], "manage_template"=>$srow['manage_template'], "manage_label"=>$srow['manage_label']);           //added by Dennis modified by Moses
            }
            else
            {
                $userlist[0] = array("user"=>$srow['users_name'], "uid"=>$srow['uid'], "email"=>$srow['email'], "password"=>$srow['password'], "full_name"=>$srow['full_name'], "parent_id"=>$srow['parent_id'], "create_survey"=>$srow['create_survey'], "configurator"=>$srow['configurator'], "create_user"=>$srow['create_user'], "delete_user"=>$srow['delete_user'], "superadmin"=>$srow['superadmin'], "manage_template"=>$srow['manage_template'], "manage_label"=>$srow['manage_label']);
            }
        }
        else
        {
            if ($srow['uid'] != $_SESSION['loginID'])
            {
                $userlist[] = $srow['uid'];
            }
            else
            {
                $userlist[0] = $srow['uid'];
            }
        }

    }
    return $userlist;
}


/**
* Gets all survey infos in one big array including the language specific settings
*
* @param string $surveyid  The survey ID
* @param string $languagecode The language code - if not given the base language of the particular survey is used
* @return array Returns array with survey info or false, if survey does not exist
*/
function getSurveyInfo($surveyid, $languagecode='')
{
    global $dbprefix, $siteadminname, $siteadminemail, $connect, $languagechanger;
    $surveyid=sanitize_int($surveyid);
    $languagecode=sanitize_languagecode($languagecode);
    $thissurvey=false;
    // if no language code is set then get the base language one
    if (!isset($languagecode) || $languagecode=='')
    {
        $languagecode=GetBaseLanguageFromSurveyID($surveyid);;
    }
    $query="SELECT * FROM ".db_table_name('surveys').",".db_table_name('surveys_languagesettings')." WHERE sid=$surveyid and surveyls_survey_id=$surveyid and surveyls_language='$languagecode'";
    $result=db_execute_assoc($query) or safe_die ("Couldn't access survey settings<br />$query<br />".$connect->ErrorMsg());   //Checked
    while ($row=$result->FetchRow())
    {
        $thissurvey=$row;
        // now create some stupid array translations - needed for backward compatibility
        // Newly added surveysettings don't have to be added specifically - these will be available by field name automatically
        $thissurvey['name']=$thissurvey['surveyls_title'];
        $thissurvey['description']=$thissurvey['surveyls_description'];
        $thissurvey['welcome']=$thissurvey['surveyls_welcometext'];
        $thissurvey['templatedir']=$thissurvey['template'];
        $thissurvey['adminname']=$thissurvey['admin'];
        $thissurvey['tablename']=$dbprefix.'survey_'.$thissurvey['sid'];
        $thissurvey['urldescrip']=$thissurvey['surveyls_urldescription'];
        $thissurvey['url']=$thissurvey['surveyls_url'];
        $thissurvey['expiry']=$thissurvey['expires'];
        $thissurvey['email_invite_subj']=$thissurvey['surveyls_email_invite_subj'];
        $thissurvey['email_invite']=$thissurvey['surveyls_email_invite'];
        $thissurvey['email_remind_subj']=$thissurvey['surveyls_email_remind_subj'];
        $thissurvey['email_remind']=$thissurvey['surveyls_email_remind'];
        $thissurvey['email_confirm_subj']=$thissurvey['surveyls_email_confirm_subj'];
        $thissurvey['email_confirm']=$thissurvey['surveyls_email_confirm'];
        $thissurvey['email_register_subj']=$thissurvey['surveyls_email_register_subj'];
        $thissurvey['email_register']=$thissurvey['surveyls_email_register'];
        if (!isset($thissurvey['adminname'])) {$thissurvey['adminname']=$siteadminname;}
        if (!isset($thissurvey['adminemail'])) {$thissurvey['adminemail']=$siteadminemail;}
        if (!isset($thissurvey['urldescrip']) ||
        $thissurvey['urldescrip'] == '' ) {$thissurvey['urldescrip']=$thissurvey['surveyls_url'];}
        $thissurvey['passthrulabel']=isset($_SESSION['passthrulabel']) ? $_SESSION['passthrulabel'] : "";
        $thissurvey['passthruvalue']=isset($_SESSION['passthruvalue']) ? $_SESSION['passthruvalue'] : "";
    }

    if (!(isset($languagechanger) && strlen($languagechanger) > 0) && function_exists('makelanguagechanger')) {
        $languagechanger = makelanguagechanger();
    }
    return $thissurvey;
}


function getlabelsets($languages=null)
// Returns a list with label sets
// if the $languages paramter is provided then only labelset containing all of the languages in the paramter are provided
{
    global $dbprefix, $connect, $surveyid;
    if ($languages){
        $languages=sanitize_languagecodeS($languages);
        $languagesarray=explode(' ',trim($languages));
    }
    $query = "SELECT ".db_table_name('labelsets').".lid as lid, label_name FROM ".db_table_name('labelsets');
    if ($languages){
        $query .=" where ";
        foreach  ($languagesarray as $item)
        {
            $query .=" ((languages like '% $item %') or (languages='$item') or (languages like '% $item') or (languages like '$item %')) and ";
        }
        $query .=" 1=1 ";
    }
    $query .=" order by label_name";
    $result = db_execute_assoc($query) or safe_die ("Couldn't get list of label sets<br />$query<br />".$connect->ErrorMsg()); //Checked
    $labelsets=array();
    while ($row=$result->FetchRow())
    {
        $labelsets[] = array($row['lid'], $row['label_name']);
    }
    return $labelsets;
}

/**
* Compares two elements from an array (passed by the usort function)
* and returns -1, 0 or 1 depending on the result of the comparison of
* the sort order of the group_order and question_order field
*
* @param mixed $a
* @param mixed $b
* @return int
*/
function GroupOrderThenQuestionOrder($a, $b)
{
    if (isset($a['group_order']) && isset($b['group_order']))
    {
        $GroupResult = strnatcasecmp($a['group_order'], $b['group_order']);
    }
    else
    {
        $GroupResult = "";
    }
    if ($GroupResult == 0)
    {
        $TitleResult = strnatcasecmp($a["question_order"], $b["question_order"]);
        return $TitleResult;
    }
    return $GroupResult;
}


function StandardSort($a, $b)
{
    return strnatcasecmp($a, $b);
}


function fixsortorderAnswers($qid) //Function rewrites the sortorder for a group of answers
{
    global $dbprefix, $connect, $surveyid;
    $qid=sanitize_int($qid);
    $baselang = GetBaseLanguageFromSurveyID($surveyid);

    $cdresult = db_execute_num("SELECT qid, code, sortorder FROM ".db_table_name('answers')." WHERE qid={$qid} and language='{$baselang}' ORDER BY sortorder"); //Checked
    $position=0;
    while ($cdrow=$cdresult->FetchRow())
    {
        $cd2query="UPDATE ".db_table_name('answers')." SET sortorder={$position} WHERE qid={$cdrow[0]} AND code='{$cdrow[1]}' AND sortorder={$cdrow[2]} ";
        $cd2result=$connect->Execute($cd2query) or safe_die ("Couldn't update sortorder<br />$cd2query<br />".$connect->ErrorMsg()); //Checked
        $position++;
    }
}

/**
* This function rewrites the sortorder for questions inside the named group
*
* @param integer $groupid the group id
* @param integer $surveyid the survey id
*/
function fixsortorderQuestions($groupid, $surveyid) //Function rewrites the sortorder for questions
{
    global $connect;
    $gid = sanitize_int($groupid);
    $surveyid = sanitize_int($surveyid);
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    $cdresult = db_execute_assoc("SELECT qid FROM ".db_table_name('questions')." WHERE gid='{$gid}' and language='{$baselang}' ORDER BY question_order, title ASC");      //Checked
    $position=0;
    while ($cdrow=$cdresult->FetchRow())
    {
        $cd2query="UPDATE ".db_table_name('questions')." SET question_order='{$position}' WHERE qid='{$cdrow['qid']}' ";
        $cd2result = $connect->Execute($cd2query) or safe_die ("Couldn't update question_order<br />$cd2query<br />".$connect->ErrorMsg());    //Checked
        $position++;
    }
}


function shiftorderQuestions($sid,$gid,$shiftvalue) //Function shifts the sortorder for questions
{
    global $dbprefix, $connect, $surveyid;
    $sid=sanitize_int($sid);
    $gid=sanitize_int($gid);
    $shiftvalue=sanitize_int($shiftvalue);

    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    $cdresult = db_execute_assoc("SELECT qid FROM ".db_table_name('questions')." WHERE gid='{$gid}' and language='{$baselang}' ORDER BY question_order, title ASC"); //Checked
    $position=$shiftvalue;
    while ($cdrow=$cdresult->FetchRow())
    {
        $cd2query="UPDATE ".db_table_name('questions')." SET question_order='{$position}' WHERE qid='{$cdrow['qid']}' ";
        $cd2result = $connect->Execute($cd2query) or safe_die ("Couldn't update question_order<br />$cd2query<br />".$connect->ErrorMsg()); //Checked
        $position++;
    }
}

function fixSortOrderGroups($surveyid) //Function rewrites the sortorder for groups
{
    global $dbprefix, $connect;
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    $cdresult = db_execute_assoc("SELECT gid FROM ".db_table_name('groups')." WHERE sid='{$surveyid}' AND language='{$baselang}' ORDER BY group_order, group_name");
    $position=0;
    while ($cdrow=$cdresult->FetchRow())
    {
        $cd2query="UPDATE ".db_table_name('groups')." SET group_order='{$position}' WHERE gid='{$cdrow['gid']}' ";
        $cd2result = $connect->Execute($cd2query) or safe_die ("Couldn't update group_order<br />$cd2query<br />".$connect->ErrorMsg());  //Checked
        $position++;
    }
}

function fixmovedquestionConditions($qid,$oldgid,$newgid) //Function rewrites the cfieldname for a question after group change
{
    global $dbprefix, $connect, $surveyid;
    $qid=sanitize_int($qid);
    $oldgid=sanitize_int($oldgid);
    $newgid=sanitize_int($newgid);

    $cresult = db_execute_assoc("SELECT cid, cfieldname FROM ".db_table_name('conditions')." WHERE cqid={$qid}");  //Checked
    while ($crow=$cresult->FetchRow())
    {

        $mycid=$crow['cid'];
        $mycfieldname=$crow['cfieldname'];
        $cfnregs="";

        if (preg_match('/'.$surveyid."X".$oldgid."X".$qid."(.*)/", $mycfieldname, $cfnregs) > 0)
        {
            $newcfn=$surveyid."X".$newgid."X".$qid.$cfnregs[1];
            $c2query="UPDATE ".db_table_name('conditions')
            ." SET cfieldname='{$newcfn}' WHERE cid={$mycid}";

            $c2result=$connect->Execute($c2query)     //Checked
            or safe_die ("Couldn't update conditions<br />$c2query<br />".$connect->ErrorMsg());
        }
    }
}


/**
* This function returns GET/POST/REQUEST vars, for some vars like SID and others they are also sanitized
*
* @param mixed $stringname
*/
function returnglobal($stringname)
{
    global $useWebserverAuth;
    if ((isset($useWebserverAuth) && $useWebserverAuth === true) || $stringname=='sid') // don't read SID from a Cookie
    {
        if (isset($_GET[$stringname])) $urlParam = $_GET[$stringname];
        if (isset($_POST[$stringname])) $urlParam = $_POST[$stringname];
    }
    elseif (isset($_REQUEST[$stringname]))
    {
        $urlParam = $_REQUEST[$stringname];
    }

    if (isset($urlParam))
    {
        if ($stringname == 'sid' || $stringname == "gid" || $stringname == "oldqid" ||
        $stringname == "qid" || $stringname == "tid" ||
        $stringname == "lid" || $stringname == "ugid"||
        $stringname == "thisstep" || $stringname == "scenario" ||
        $stringname == "cqid" || $stringname == "cid" ||
        $stringname == "qaid" || $stringname == "scid" ||
        $stringname == "loadsecurity")
        {
            return sanitize_int($urlParam);
        }
        elseif ($stringname =="lang" || $stringname =="adminlang")
        {
            return sanitize_languagecode($urlParam);
        }
        elseif ($stringname =="htmleditormode" ||
        $stringname =="subaction" ||
        $stringname =="questionselectormode" ||
        $stringname =="templateeditormode"
        )
        {
            return sanitize_paranoid_string($urlParam);
        }
        elseif ( $stringname =="cquestions")
        {
            return sanitize_cquestions($urlParam);
        }
        return $urlParam;
    }
    else
    {
        return NULL;
    }
}


function sendcacheheaders()
{
    global $embedded;
    if ( $embedded ) return;
    if (!headers_sent())
    {
        header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');  // this line lets IE7 run LimeSurvey in an iframe
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
        header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: text/html; charset=utf-8');
    }
}

function getsidgidqidaidtype($fieldcode)
{
    // use simple parsing to get {sid}, {gid}
    // and what may be {qid} or {qid}{aid} combination
    list($fsid, $fgid, $fqid) = explode('X', $fieldcode);
    $fsid=sanitize_int($fsid);
    $fgid=sanitize_int($fgid);
    if (!$fqid) {$fqid=0;}
    $fqid=sanitize_int($fqid);
    // try a true parsing of fieldcode (can separate qid from aid)
    // but fails for type M and type P multiple choice
    // questions because the SESSION fieldcode is combined
    // and we want here to pass only the sidXgidXqid for type M and P
    $fields=arraySearchByKey($fieldcode, createFieldMap($fsid), "fieldname", 1);

    if (count($fields) != 0)
    {
        $aRef['sid']=$fields['sid'];
        $aRef['gid']=$fields['gid'];
        $aRef['qid']=$fields['qid'];
        $aRef['aid']=$fields['aid'];
        $aRef['type']=$fields['type'];
    }
    else
    {
        // either the fielcode doesn't match a question
        // or it is a type M or P question
        $aRef['sid']=$fsid;
        $aRef['gid']=$fgid;
        $aRef['qid']=sanitize_int($fqid);

        $s_lang = GetBaseLanguageFromSurveyID($fsid);
        $query = "SELECT type FROM ".db_table_name('questions')." WHERE qid=".$fqid." AND language='".$s_lang."'";
        $result = db_execute_assoc($query) or safe_die ("Couldn't get question type - getsidgidqidaidtype() in common.php<br />".$connect->ErrorMsg()); //Checked
        if ( $result->RecordCount() == 0 )
        { // question doesn't exist
            return Array();
        }
        else
        {   // certainly is type M or P
            while($row=$result->FetchRow())
            {
                $aRef['type']=$row['type'];
            }
        }

    }

    //return array('sid'=>$fsid, "gid"=>$fgid, "qid"=>$fqid);
    return $aRef;
}

/**
* put your comment there...
*
* @param mixed $fieldcode
* @param mixed $value
* @param mixed $format
* @param mixed $dateformatid
* @return string
*/
function getextendedanswer($fieldcode, $value, $format='', $dateformatphp='d.m.Y')
{

    global $dbprefix, $surveyid, $connect, $clang, $action;

    // use Survey base language if s_lang isn't set in _SESSION (when browsing answers)
    $s_lang = GetBaseLanguageFromSurveyID($surveyid);
    if  (!isset($action) || (isset($action) && $action!='browse') )
    {
        if (isset($_SESSION['s_lang'])) $s_lang = $_SESSION['s_lang'];  //This one does not work in admin mode when you browse a particular answer
    }

    //Fieldcode used to determine question, $value used to match against answer code
    //Returns NULL if question type does not suit
    if (substr_count($fieldcode, "X") > 1) //Only check if it looks like a real fieldcode
    {
        $fieldmap = createFieldMap($surveyid);
        if (isset($fieldmap[$fieldcode]))
            $fields = $fieldmap[$fieldcode];
        else
            return false;
        //Find out the question type
        $this_type = $fields['type'];
        switch($this_type)
        {
            case 'D': if (trim($value)!='')
                {
                    $datetimeobj = new Date_Time_Converter($value , "Y-m-d H:i:s");
                    $value=$datetimeobj->convert($dateformatphp);
                }
                break;
            case "L":
            case "!":
            case "O":
            case "^":
            case "I":
            case "R":
                $query = "SELECT code, answer FROM ".db_table_name('answers')." WHERE qid={$fields['qid']} AND code='".$connect->escape($value)."' AND scale_id=0 AND language='".$s_lang."'";
                $result = db_execute_assoc($query) or safe_die ("Couldn't get answer type L - getextendedanswer() in common.php<br />$query<br />".$connect->ErrorMsg()); //Checked
                while($row=$result->FetchRow())
                {
                    $this_answer=$row['answer'];
                } // while
                if ($value == "-oth-")
                {
                    $this_answer=$clang->gT("Other");
                }
                break;
            case "M":
            case "J":
            case "P":
            switch($value)
            {
                case "Y": $this_answer=$clang->gT("Yes"); break;
            }
            break;
            case "Y":
            switch($value)
            {
                case "Y": $this_answer=$clang->gT("Yes"); break;
                case "N": $this_answer=$clang->gT("No"); break;
                default: $this_answer=$clang->gT("No answer");
            }
            break;
            case "G":
            switch($value)
            {
                case "M": $this_answer=$clang->gT("Male"); break;
                case "F": $this_answer=$clang->gT("Female"); break;
                default: $this_answer=$clang->gT("No answer");
            }
            break;
            case "C":
            switch($value)
            {
                case "Y": $this_answer=$clang->gT("Yes"); break;
                case "N": $this_answer=$clang->gT("No"); break;
                case "U": $this_answer=$clang->gT("Uncertain"); break;
            }
            break;
            case "E":
            switch($value)
            {
                case "I": $this_answer=$clang->gT("Increase"); break;
                case "D": $this_answer=$clang->gT("Decrease"); break;
                case "S": $this_answer=$clang->gT("Same"); break;
            }
            break;
            case "F":
            case "H":
            case "1":
                $query = "SELECT answer FROM ".db_table_name('answers')." WHERE qid={$fields['qid']} AND code='".$connect->escape($value)."' AND language='".$s_lang."'";
                if (isset($fields['scale_id']))
                {
                    $query.=" AND scale_id={$fields['scale_id']}";
                }
                $result = db_execute_assoc($query) or safe_die ("Couldn't get answer type F/H - getextendedanswer() in common.php");   //Checked
                while($row=$result->FetchRow())
                {
                    $this_answer=$row['answer'];
                } // while
                if ($value == "-oth-")
                {
                    $this_answer=$clang->gT("Other");
                }
                break;
            case "|": //File upload
                if (substr($fieldcode, -9) == 'filecount') {
                    $this_answer = $clang->gT("File count");
                } else {
                    //Show the filename, size, title and comment -- no link!
                    $files = json_decode($value);
                    $value = '';
                    if (is_array($files)) {
                        foreach ($files as $file) {
                            $value .= $file->name .
                            ' (' . $file->size . 'KB) ' .
                            strip_tags($file->title) .
                            ' - ' . strip_tags($file->comment) . "<br/>";
                        }
                    }
                }
                break;
            default:
                ;
        } // switch
    }
    if (isset($this_answer))
    {
        if ($format != 'INSERTANS')
        {
            return $this_answer." [$value]";
        }
        else
        {
            if (strip_tags($this_answer) == "")
            {
                switch ($this_type)
                {// for questions with answers beeing
                    // answer code, it is safe to return the
                    // code instead of the blank stripped answer
                    case "A":
                    case "B":
                    case "C":
                    case "E":
                    case "F":
                    case "H":
                    case "1":
                    case "M":
                    case "P":
                    case "!":
                    case "5":
                    case "L":
                    case "O":
                        return $value;
                        break;
                    default:
                        return strip_tags($this_answer);
                        break;
                }
            }
            else
            {
                return strip_tags($this_answer);
            }
        }
    }
    else
    {
        return $value;
    }
}

/*function validate_email($email)
{
// Create the syntactical validation regular expression
// Validate the syntax

// see http://data.iana.org/TLD/tlds-alpha-by-domain.txt
$maxrootdomainlength = 6;
return ( ! preg_match("/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.(([0-9]{1,3})|([a-zA-Z]{2,".$maxrootdomainlength."}))$/ix", $email)) ? FALSE : TRUE;
}*/

function validate_email($email){


    $no_ws_ctl    = "[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]";
    $alpha        = "[\\x41-\\x5a\\x61-\\x7a]";
    $digit        = "[\\x30-\\x39]";
    $cr        = "\\x0d";
    $lf        = "\\x0a";
    $crlf        = "(?:$cr$lf)";


    $obs_char    = "[\\x00-\\x09\\x0b\\x0c\\x0e-\\x7f]";
    $obs_text    = "(?:$lf*$cr*(?:$obs_char$lf*$cr*)*)";
    $text        = "(?:[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f]|$obs_text)";


    $text        = "(?:$lf*$cr*$obs_char$lf*$cr*)";
    $obs_qp        = "(?:\\x5c[\\x00-\\x7f])";
    $quoted_pair    = "(?:\\x5c$text|$obs_qp)";


    $wsp        = "[\\x20\\x09]";
    $obs_fws    = "(?:$wsp+(?:$crlf$wsp+)*)";
    $fws        = "(?:(?:(?:$wsp*$crlf)?$wsp+)|$obs_fws)";
    $ctext        = "(?:$no_ws_ctl|[\\x21-\\x27\\x2A-\\x5b\\x5d-\\x7e])";
    $ccontent    = "(?:$ctext|$quoted_pair)";
    $comment    = "(?:\\x28(?:$fws?$ccontent)*$fws?\\x29)";
    $cfws        = "(?:(?:$fws?$comment)*(?:$fws?$comment|$fws))";


    $outer_ccontent_dull    = "(?:$fws?$ctext|$quoted_pair)";
    $outer_ccontent_nest    = "(?:$fws?$comment)";
    $outer_comment        = "(?:\\x28$outer_ccontent_dull*(?:$outer_ccontent_nest$outer_ccontent_dull*)+$fws?\\x29)";



    $atext        = "(?:$alpha|$digit|[\\x21\\x23-\\x27\\x2a\\x2b\\x2d\\x2f\\x3d\\x3f\\x5e\\x5f\\x60\\x7b-\\x7e])";
    $atext_domain     = "(?:$alpha|$digit|[\\x2b\\x2d\\x5f])";

    $atom        = "(?:$cfws?(?:$atext)+$cfws?)";
    $atom_domain       = "(?:$cfws?(?:$atext_domain)+$cfws?)";


    $qtext        = "(?:$no_ws_ctl|[\\x21\\x23-\\x5b\\x5d-\\x7e])";
    $qcontent    = "(?:$qtext|$quoted_pair)";
    $quoted_string    = "(?:$cfws?\\x22(?:$fws?$qcontent)*$fws?\\x22$cfws?)";


    $quoted_string    = "(?:$cfws?\\x22(?:$fws?$qcontent)+$fws?\\x22$cfws?)";
    $word        = "(?:$atom|$quoted_string)";


    $obs_local_part    = "(?:$word(?:\\x2e$word)*)";


    $obs_domain    = "(?:$atom_domain(?:\\x2e$atom_domain)*)";

    $dot_atom_text     = "(?:$atext+(?:\\x2e$atext+)*)";
    $dot_atom_text_domain    = "(?:$atext_domain+(?:\\x2e$atext_domain+)*)";


    $dot_atom    	   = "(?:$cfws?$dot_atom_text$cfws?)";
    $dot_atom_domain   = "(?:$cfws?$dot_atom_text_domain$cfws?)";


    $dtext        = "(?:$no_ws_ctl|[\\x21-\\x5a\\x5e-\\x7e])";
    $dcontent    = "(?:$dtext|$quoted_pair)";
    $domain_literal    = "(?:$cfws?\\x5b(?:$fws?$dcontent)*$fws?\\x5d$cfws?)";


    $local_part    = "(($dot_atom)|($quoted_string)|($obs_local_part))";
    $domain        = "(($dot_atom_domain)|($domain_literal)|($obs_domain))";
    $addr_spec    = "$local_part\\x40$domain";


    if (strlen($email) > 256) return FALSE;


    $email = strip_comments($outer_comment, $email, "(x)");



    if (!preg_match("!^$addr_spec$!", $email, $m)){

        return FALSE;
    }

    $bits = array(
    'local'            => isset($m[1]) ? $m[1] : '',
    'local-atom'        => isset($m[2]) ? $m[2] : '',
    'local-quoted'        => isset($m[3]) ? $m[3] : '',
    'local-obs'        => isset($m[4]) ? $m[4] : '',
    'domain'        => isset($m[5]) ? $m[5] : '',
    'domain-atom'        => isset($m[6]) ? $m[6] : '',
    'domain-literal'    => isset($m[7]) ? $m[7] : '',
    'domain-obs'        => isset($m[8]) ? $m[8] : '',
    );



    $bits['local']    = strip_comments($comment, $bits['local']);
    $bits['domain']    = strip_comments($comment, $bits['domain']);




    if (strlen($bits['local']) > 64) return FALSE;
    if (strlen($bits['domain']) > 255) return FALSE;



    if (strlen($bits['domain-literal'])){

        $Snum            = "(\d{1,3})";
        $IPv4_address_literal    = "$Snum\.$Snum\.$Snum\.$Snum";

        $IPv6_hex        = "(?:[0-9a-fA-F]{1,4})";

        $IPv6_full        = "IPv6\:$IPv6_hex(:?\:$IPv6_hex){7}";

        $IPv6_comp_part        = "(?:$IPv6_hex(?:\:$IPv6_hex){0,5})?";
        $IPv6_comp        = "IPv6\:($IPv6_comp_part\:\:$IPv6_comp_part)";

        $IPv6v4_full        = "IPv6\:$IPv6_hex(?:\:$IPv6_hex){5}\:$IPv4_address_literal";

        $IPv6v4_comp_part    = "$IPv6_hex(?:\:$IPv6_hex){0,3}";
        $IPv6v4_comp        = "IPv6\:((?:$IPv6v4_comp_part)?\:\:(?:$IPv6v4_comp_part\:)?)$IPv4_address_literal";



        if (preg_match("!^\[$IPv4_address_literal\]$!", $bits['domain'], $m)){

            if (intval($m[1]) > 255) return FALSE;
            if (intval($m[2]) > 255) return FALSE;
            if (intval($m[3]) > 255) return FALSE;
            if (intval($m[4]) > 255) return FALSE;

        }else{


            while (1){

                if (preg_match("!^\[$IPv6_full\]$!", $bits['domain'])){
                    break;
                }

                if (preg_match("!^\[$IPv6_comp\]$!", $bits['domain'], $m)){
                    list($a, $b) = explode('::', $m[1]);
                    $folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
                    $groups = explode(':', $folded);
                    if (count($groups) > 6) return FALSE;
                    break;
                }

                if (preg_match("!^\[$IPv6v4_full\]$!", $bits['domain'], $m)){

                    if (intval($m[1]) > 255) return FALSE;
                    if (intval($m[2]) > 255) return FALSE;
                    if (intval($m[3]) > 255) return FALSE;
                    if (intval($m[4]) > 255) return FALSE;
                    break;
                }

                if (preg_match("!^\[$IPv6v4_comp\]$!", $bits['domain'], $m)){
                    list($a, $b) = explode('::', $m[1]);
                    $b = substr($b, 0, -1); # remove the trailing colon before the IPv4 address
                    $folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
                    $groups = explode(':', $folded);
                    if (count($groups) > 4) return FALSE;
                    break;
                }

                return FALSE;
            }
        }
    }else{


        $labels = explode('.', $bits['domain']);


        if (count($labels) == 1) return FALSE;


        foreach ($labels as $label){

            if (strlen($label) > 63) return FALSE;
            if (substr($label, 0, 1) == '-') return FALSE;
            if (substr($label, -1) == '-') return FALSE;
        }

        if (preg_match('!^[0-9]+$!', array_pop($labels))) return FALSE;
    }


    return TRUE;
}

##################################################################################

function strip_comments($comment, $email, $replace=''){

    while (1){
        $new = preg_replace("!$comment!", $replace, $email);
        if (strlen($new) == strlen($email)){
            return $email;
        }
        $email = $new;
    }
}


function validate_templatedir($templatename)
{
    global $usertemplaterootdir, $defaulttemplate;
    if (isStandardTemplate($templatename))
    {
        return $templatename;
    }
    elseif (is_dir("$usertemplaterootdir/{$templatename}/"))
    {
        return $templatename;
    }
    elseif (isStandardTemplate($defaulttemplate))
    {
        return $defaulttemplate;
    }
    elseif (is_dir("$usertemplaterootdir/{$defaulttemplate}/"))
    {
        return $defaulttemplate;
    }
    else
    {
        return 'default';
    }
}


/**
* This function generates an array containing the fieldcode, and matching data in the same order as the activate script
*
* @param string $surveyid The Survey ID
* @param mixed $style 'short' (default) or 'full' - full creates extra information like default values
* @param mixed $force_refresh - Forces to really refresh the array, not just take the session copy
* @param int $questionid Limit to a certain qid only (for question preview) - default is false
* @return array
*/
function createFieldMap($surveyid, $style='full', $force_refresh=false, $questionid=false, $sQuestionLanguage=null) {

    global $dbprefix, $connect, $clang, $aDuplicateQIDs;
    $surveyid=sanitize_int($surveyid);

    //Get list of questions
    if (is_null($sQuestionLanguage))
    {
        if (isset($_SESSION['s_lang'])&& in_array($_SESSION['s_lang'],GetAdditionalLanguagesFromSurveyID($surveyid)) ) {
            $sQuestionLanguage = $_SESSION['s_lang'];
        }
        else {
            $sQuestionLanguage = GetBaseLanguageFromSurveyID($surveyid);
        }
    }
    $sQuestionLanguage = sanitize_languagecode($sQuestionLanguage);
    if ($clang->langcode != $sQuestionLanguage) {
        SetSurveyLanguage($surveyid, $sQuestionLanguage);
    }
    $s_lang = $clang->langcode;

    //checks to see if fieldmap has already been built for this page.
    if (isset($_SESSION['fieldmap-' . $surveyid . $s_lang]) && !$force_refresh && $questionid == false) {
        if (isset($_SESSION['adminlang']) && $clang->langcode != $_SESSION['adminlang']) {
            $clang = new limesurvey_lang($_SESSION['adminlang']);
        }
        return $_SESSION['fieldmap-' . $surveyid . $s_lang];
    }

    $fieldmap["id"]=array("fieldname"=>"id", 'sid'=>$surveyid, 'type'=>"id", "gid"=>"", "qid"=>"", "aid"=>"");
    if ($style == "full")
    {
        $fieldmap["id"]['title']="";
        $fieldmap["id"]['question']=$clang->gT("Response ID");
        $fieldmap["id"]['group_name']="";
    }

    $fieldmap["submitdate"]=array("fieldname"=>"submitdate", 'type'=>"submitdate", 'sid'=>$surveyid, "gid"=>"", "qid"=>"", "aid"=>"");
    if ($style == "full")
    {
        $fieldmap["submitdate"]['title']="";
        $fieldmap["submitdate"]['question']=$clang->gT("Date submitted");
        $fieldmap["submitdate"]['group_name']="";
    }

    $fieldmap["lastpage"]=array("fieldname"=>"lastpage", 'sid'=>$surveyid, 'type'=>"lastpage", "gid"=>"", "qid"=>"", "aid"=>"");
    if ($style == "full")
    {
        $fieldmap["lastpage"]['title']="";
        $fieldmap["lastpage"]['question']=$clang->gT("Last page");
        $fieldmap["lastpage"]['group_name']="";
    }

    $fieldmap["startlanguage"]=array("fieldname"=>"startlanguage", 'sid'=>$surveyid, 'type'=>"startlanguage", "gid"=>"", "qid"=>"", "aid"=>"");
    if ($style == "full")
    {
        $fieldmap["startlanguage"]['title']="";
        $fieldmap["startlanguage"]['question']=$clang->gT("Start language");
        $fieldmap["startlanguage"]['group_name']="";
    }


    //Check for any additional fields for this survey and create necessary fields (token and datestamp and ipaddr)
    $pquery = "SELECT anonymized, datestamp, ipaddr, refurl FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
    $presult=db_execute_assoc($pquery); //Checked
    while($prow=$presult->FetchRow())
    {
        if ($prow['anonymized'] == "N")
        {
            $fieldmap["token"]=array("fieldname"=>"token", 'sid'=>$surveyid, 'type'=>"token", "gid"=>"", "qid"=>"", "aid"=>"");
            if ($style == "full")
            {
                $fieldmap["token"]['title']="";
                $fieldmap["token"]['question']=$clang->gT("Token");
                $fieldmap["token"]['group_name']="";
            }
        }
        if ($prow['datestamp'] == "Y")
        {
            $fieldmap["datestamp"]=array("fieldname"=>"datestamp",
            'type'=>"datestamp",
            'sid'=>$surveyid,
            "gid"=>"",
            "qid"=>"",
            "aid"=>"");
            if ($style == "full")
            {
                $fieldmap["datestamp"]['title']="";
                $fieldmap["datestamp"]['question']=$clang->gT("Date last action");
                $fieldmap["datestamp"]['group_name']="";
            }
            $fieldmap["startdate"]=array("fieldname"=>"startdate",
            'type'=>"startdate",
            'sid'=>$surveyid,
            "gid"=>"",
            "qid"=>"",
            "aid"=>"");
            if ($style == "full")
            {
                $fieldmap["startdate"]['title']="";
                $fieldmap["startdate"]['question']=$clang->gT("Date started");
                $fieldmap["startdate"]['group_name']="";
            }

        }
        if ($prow['ipaddr'] == "Y")
        {
            $fieldmap["ipaddr"]=array("fieldname"=>"ipaddr",
            'type'=>"ipaddress",
            'sid'=>$surveyid,
            "gid"=>"",
            "qid"=>"",
            "aid"=>"");
            if ($style == "full")
            {
                $fieldmap["ipaddr"]['title']="";
                $fieldmap["ipaddr"]['question']=$clang->gT("IP address");
                $fieldmap["ipaddr"]['group_name']="";
            }
        }
        // Add 'refurl' to fieldmap.
        if ($prow['refurl'] == "Y")
        {
            $fieldmap["refurl"]=array("fieldname"=>"refurl", 'type'=>"url", 'sid'=>$surveyid, "gid"=>"", "qid"=>"", "aid"=>"");
            if ($style == "full")
            {
                $fieldmap["refurl"]['title']="";
                $fieldmap["refurl"]['question']=$clang->gT("Referrer URL");
                $fieldmap["refurl"]['group_name']="";
            }
        }
    }

    // Collect all default values once so don't need separate query for each question with defaults
    // First collect language specific defaults
    $defaultsQuery = "SELECT a.qid, a.sqid, a.scale_id, a.specialtype, a.defaultvalue"
    . " FROM ".db_table_name('defaultvalues')." as a, ".db_table_name('questions')." as b"
    . " WHERE a.qid = b.qid"
    . " AND a.language = b.language"
    . " AND a.language = '$s_lang'"
    . " AND b.same_default=0"
    . " AND b.sid = ".$surveyid;
    $defaultResults = db_execute_assoc($defaultsQuery) or safe_die ("Couldn't get list of default values in createFieldMap.<br/>$defaultsQuery<br/>".$conect->ErrorMsg());

    $defaultValues = array();   // indexed by question then subquestion
    foreach($defaultResults as $dv)
    {
        if ($dv['specialtype'] != '') {
            $sq = $dv['specialtype'];
        }
        else {
            $sq = $dv['sqid'];
        }
        $defaultValues[$dv['qid'].'~'.$sq] = $dv['defaultvalue'];
    }

    // Now overwrite language-specific defaults (if any) base language values for each question that uses same_defaults=1
    $baseLanguage = GetBaseLanguageFromSurveyID($surveyid);
    $defaultsQuery = "SELECT a.qid, a.sqid, a.scale_id, a.specialtype, a.defaultvalue"
    . " FROM ".db_table_name('defaultvalues')." as a, ".db_table_name('questions')." as b"
    . " WHERE a.qid = b.qid"
    . " AND a.language = b.language"
    . " AND a.language = '$baseLanguage'"
    . " AND b.same_default=1"
    . " AND b.sid = ".$surveyid;
    $defaultResults = db_execute_assoc($defaultsQuery) or safe_die ("Couldn't get list of default values in createFieldMap.<br/>$defaultsQuery<br/>".$conect->ErrorMsg());

    foreach($defaultResults as $dv)
    {
        if ($dv['specialtype'] != '') {
            $sq = $dv['specialtype'];
        }
        else {
            $sq = $dv['sqid'];
        }
        $defaultValues[$dv['qid'].'~'.$sq] = $dv['defaultvalue'];
    }

    $qtypes=getqtypelist('','array');
    $aquery = "SELECT * "
    ." FROM ".db_table_name('questions')." as questions, ".db_table_name('groups')." as groups"
    ." WHERE questions.gid=groups.gid AND "
    ." questions.sid=$surveyid AND "
    ." questions.language='{$s_lang}' AND "
    ." questions.parent_qid=0 AND "
    ." groups.language='{$s_lang}' ";
    if ($questionid!==false)
    {
        $aquery.=" and questions.qid={$questionid} ";
    }
    $aquery.=" ORDER BY group_order, question_order";
    $aresult = db_execute_assoc($aquery) or safe_die ("Couldn't get list of questions in createFieldMap function.<br />$query<br />".$connect->ErrorMsg()); //Checked

    $questionSeq=-1; // this is incremental question sequence across all groups
    $groupSeq=-1;
    $_groupOrder=-1;

    while ($arow=$aresult->FetchRow()) //With each question, create the appropriate field(s)
    {
        ++$questionSeq;

        // fix fact taht group_order may have gaps
        if ($_groupOrder != $arow['group_order']) {
            $_groupOrder = $arow['group_order'];
            ++$groupSeq;
        }

        // Conditions indicators are obsolete with EM.  However, they are so tightly coupled into LS code that easider to just set values to 'N' for now and refactor later.
        $conditions = 'N';
        $usedinconditions = 'N';

        // Field identifier
        // GXQXSXA
        // G=Group  Q=Question S=Subquestion A=Answer Option
        // If S or A don't exist then set it to 0
        // Implicit (subqestion intermal to a question type ) or explicit qubquestions/answer count starts at 1

        // Types "L", "!" , "O", "D", "G", "N", "X", "Y", "5","S","T","U","*"
        $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}";

        if ($qtypes[$arow['type']]['subquestions']==0  && $arow['type'] != "R" && $arow['type'] != "|")
        {
            if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);
            $fieldmap[$fieldname]=array("fieldname"=>$fieldname, 'type'=>"{$arow['type']}", 'sid'=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>"");
            if ($style == "full")
            {
                $fieldmap[$fieldname]['title']=$arow['title'];
                $fieldmap[$fieldname]['question']=$arow['question'];
                $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                $fieldmap[$fieldname]['hasconditions']=$conditions;
                $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                if (isset($defaultValues[$arow['qid'].'~0'])) {
                    $fieldmap[$fieldname]['defaultvalue'] = $defaultValues[$arow['qid'].'~0'];
                }
            }
            switch($arow['type'])
            {
                case "L":  //RADIO LIST
                case "!":  //DROPDOWN LIST
                    $fieldmap[$fieldname]['other']=$arow['other'];  // so that base variable knows whether has other value
                    if ($arow['other'] == "Y")
                    {
                        $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}other";
                        if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);

                        $fieldmap[$fieldname]=array("fieldname"=>$fieldname,
                        'type'=>$arow['type'],
                        'sid'=>$surveyid,
                        "gid"=>$arow['gid'],
                        "qid"=>$arow['qid'],
                        "aid"=>"other");
                        // dgk bug fix line above. aid should be set to "other" for export to append to the field name in the header line.
                        if ($style == "full")
                        {
                            $fieldmap[$fieldname]['title']=$arow['title'];
                            $fieldmap[$fieldname]['question']=$arow['question'];
                            $fieldmap[$fieldname]['subquestion']=$clang->gT("Other");
                            $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                            $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                            $fieldmap[$fieldname]['hasconditions']=$conditions;
                            $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                            $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                            $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                            $fieldmap[$fieldname]['other']=$arow['other'];
                            if (isset($defaultValues[$arow['qid'].'~other'])) {
                                $fieldmap[$fieldname]['defaultvalue'] = $defaultValues[$arow['qid'].'~other'];
                            }
                        }
                    }
                    break;
                case "O": //DROPDOWN LIST WITH COMMENT
                    $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}comment";
                    if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);

                    $fieldmap[$fieldname]=array("fieldname"=>$fieldname,
                    'type'=>$arow['type'],
                    'sid'=>$surveyid,
                    "gid"=>$arow['gid'],
                    "qid"=>$arow['qid'],
                    "aid"=>"comment");
                    // dgk bug fix line below. aid should be set to "comment" for export to append to the field name in the header line. Also needed set the type element correctly.
                    if ($style == "full")
                    {
                        $fieldmap[$fieldname]['title']=$arow['title'];
                        $fieldmap[$fieldname]['question']=$arow['question'];
                        $fieldmap[$fieldname]['subquestion']=$clang->gT("Comment");
                        $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                        $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                        $fieldmap[$fieldname]['hasconditions']=$conditions;
                        $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                        $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                        $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                    }
                    break;
            }
        }
        // For Multi flexi question types
        elseif ($qtypes[$arow['type']]['subquestions']==2 && $qtypes[$arow['type']]['answerscales']==0)
        {
            //MULTI FLEXI
            $abrows = getSubQuestions($surveyid,$arow['qid'],$s_lang);
            //Now first process scale=1
            $answerset=array();
            $answerList = array();
            foreach ($abrows as $key=>$abrow)
            {
                if($abrow['scale_id']==1) {
                    $answerset[]=$abrow;
                    $answerList[] = array(
                    'code'=>$abrow['title'],
                    'answer'=>$abrow['question'],
                    );
                    unset($abrows[$key]);
                }
            }
            reset($abrows);
            foreach ($abrows as $abrow)
            {
                foreach($answerset as $answer)
                {
                    $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['title']}_{$answer['title']}";
                    if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);
                    $fieldmap[$fieldname]=array("fieldname"=>$fieldname,
                    'type'=>$arow['type'],
                    'sid'=>$surveyid,
                    "gid"=>$arow['gid'],
                    "qid"=>$arow['qid'],
                    "aid"=>$abrow['title']."_".$answer['title'],
                    "sqid"=>$abrow['qid']);
                    if ($abrow['other']=="Y") {$alsoother="Y";}
                    if ($style == "full")
                    {
                        $fieldmap[$fieldname]['title']=$arow['title'];
                        $fieldmap[$fieldname]['question']=$arow['question'];
                        $fieldmap[$fieldname]['subquestion1']=$abrow['question'];
                        $fieldmap[$fieldname]['subquestion2']=$answer['question'];
                        $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                        $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                        $fieldmap[$fieldname]['hasconditions']=$conditions;
                        $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                        $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                        $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                        $fieldmap[$fieldname]['preg']=$arow['preg'];
                        $fieldmap[$fieldname]['answerList']=$answerList;
                    }
                }
            }
            unset($answerset);
        }
        elseif ($arow['type'] == "1")
        {
            $abrows = getSubQuestions($surveyid,$arow['qid'],$s_lang);
            foreach ($abrows as $abrow)
            {
                $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['title']}#0";
                if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);
                $fieldmap[$fieldname]=array("fieldname"=>$fieldname, 'type'=>$arow['type'], 'sid'=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>$abrow['title'], "scale_id"=>0);
                if ($style == "full")
                {
                    $fieldmap[$fieldname]['title']=$arow['title'];
                    $fieldmap[$fieldname]['question']=$arow['question'];
                    $fieldmap[$fieldname]['subquestion']=$abrow['question'];
                    $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                    $fieldmap[$fieldname]['scale']=$clang->gT('Scale 1');
                    $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                    $fieldmap[$fieldname]['hasconditions']=$conditions;
                    $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                    $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                    $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                }

                $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['title']}#1";
                if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);
                $fieldmap[$fieldname]=array("fieldname"=>$fieldname, 'type'=>$arow['type'], 'sid'=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>$abrow['title'], "scale_id"=>1);
                if ($style == "full")
                {
                    $fieldmap[$fieldname]['title']=$arow['title'];
                    $fieldmap[$fieldname]['question']=$arow['question'];
                    $fieldmap[$fieldname]['subquestion']=$abrow['question'];
                    $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                    $fieldmap[$fieldname]['scale']=$clang->gT('Scale 2');
                    $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                    $fieldmap[$fieldname]['hasconditions']=$conditions;
                    $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                    $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                    $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                }
            }
        }

        elseif ($arow['type'] == "R")
        {
            //MULTI ENTRY
            $slots=$connect->GetOne("select count(code) from ".db_table_name('answers')." where qid={$arow['qid']} and language='{$s_lang}'");
            for ($i=1; $i<=$slots; $i++)
            {
                $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}$i";
                if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);
                $fieldmap[$fieldname]=array("fieldname"=>$fieldname, 'type'=>$arow['type'], 'sid'=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>$i);
                if ($style == "full")
                {
                    $fieldmap[$fieldname]['title']=$arow['title'];
                    $fieldmap[$fieldname]['question']=$arow['question'];
                    $fieldmap[$fieldname]['subquestion']=sprintf($clang->gT('Rank %s'),$i);
                    $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                    $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                    $fieldmap[$fieldname]['hasconditions']=$conditions;
                    $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                    $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                    $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                }
            }
        }
        elseif ($arow['type'] == "|")
        {
            $abquery = "SELECT value FROM ".db_table_name('question_attributes')
            ." WHERE attribute='max_num_of_files' AND qid=".$arow['qid'];
            $abresult = db_execute_assoc($abquery) or safe_die ("Couldn't get maximum
            number of files that can be uploaded <br />$abquery<br />".$connect->ErrorMsg());
            $abrow = $abresult->FetchRow();

                $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}";
                $fieldmap[$fieldname]=array("fieldname"=>$fieldname,
                'type'=>$arow['type'],
                'sid'=>$surveyid,
                "gid"=>$arow['gid'],
                "qid"=>$arow['qid'],
                "aid"=>''
                );
                if ($style == "full")
                {
                    $fieldmap[$fieldname]['title']=$arow['title'];
                    $fieldmap[$fieldname]['question']=$arow['question'];
                    $fieldmap[$fieldname]['max_files']=$abrow['value'];
                    $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                    $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                    $fieldmap[$fieldname]['hasconditions']=$conditions;
                    $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                    $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                    $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                }
                $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}"."_filecount";
                $fieldmap[$fieldname]=array("fieldname"=>$fieldname,
                'type'=>$arow['type'],
                'sid'=>$surveyid,
                "gid"=>$arow['gid'],
                "qid"=>$arow['qid'],
                "aid"=>"filecount"
                );
                if ($style == "full")
                {
                    $fieldmap[$fieldname]['title']=$arow['title'];
                    $fieldmap[$fieldname]['question']="filecount - ".$arow['question'];
                    //$fieldmap[$fieldname]['subquestion']=$clang->gT("Comment");
                    $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                    $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                    $fieldmap[$fieldname]['hasconditions']=$conditions;
                    $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                    $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                    $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                }
        }
        else  // Question types with subquestions and one answer per subquestion  (M/A/B/C/E/F/H/P)
        {
            //MULTI ENTRY
            $abrows = getSubQuestions($surveyid,$arow['qid'],$s_lang);
            foreach ($abrows as $abrow)
            {
                $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['title']}";
                if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);
                $fieldmap[$fieldname]=array("fieldname"=>$fieldname,
                'type'=>$arow['type'],
                'sid'=>$surveyid,
                'gid'=>$arow['gid'],
                'qid'=>$arow['qid'],
                'aid'=>$abrow['title'],
                'sqid'=>$abrow['qid']);
                if ($style == "full")
                {
                    $fieldmap[$fieldname]['title']=$arow['title'];
                    $fieldmap[$fieldname]['question']=$arow['question'];
                    $fieldmap[$fieldname]['subquestion']=$abrow['question'];
                    $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                    $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                    $fieldmap[$fieldname]['hasconditions']=$conditions;
                    $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                    $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                    $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                    $fieldmap[$fieldname]['preg']=$arow['preg'];
                    if (isset($defaultValues[$arow['qid'].'~'.$abrow['qid']])) {
                        $fieldmap[$fieldname]['defaultvalue'] = $defaultValues[$arow['qid'].'~'.$abrow['qid']];
                    }
                }
                if ($arow['type'] == "P")
                {
                    $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['title']}comment";
                    if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);
                    $fieldmap[$fieldname]=array("fieldname"=>$fieldname, 'type'=>$arow['type'], 'sid'=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>$abrow['title']."comment");
                    if ($style == "full")
                    {
                        $fieldmap[$fieldname]['title']=$arow['title'];
                        $fieldmap[$fieldname]['question']=$arow['question'];
                        $fieldmap[$fieldname]['subquestion']=$clang->gT('Comment');
                        $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                        $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                        $fieldmap[$fieldname]['hasconditions']=$conditions;
                        $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                        $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                        $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                    }
                }
            }
            if ($arow['other']=="Y" && ($arow['type']=="M" || $arow['type']=="P"))
            {
                $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}other";
                if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);
                $fieldmap[$fieldname]=array("fieldname"=>$fieldname, 'type'=>$arow['type'], 'sid'=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>"other");
                if ($style == "full")
                {
                    $fieldmap[$fieldname]['title']=$arow['title'];
                    $fieldmap[$fieldname]['question']=$arow['question'];
                    $fieldmap[$fieldname]['subquestion']=$clang->gT('Other');
                    $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                    $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                    $fieldmap[$fieldname]['hasconditions']=$conditions;
                    $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                    $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                    $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                    $fieldmap[$fieldname]['other']=$arow['other'];
                }
                if ($arow['type']=="P")
                {
                    $fieldname="{$arow['sid']}X{$arow['gid']}X{$arow['qid']}othercomment";
                    if (isset($fieldmap[$fieldname])) $aDuplicateQIDs[$arow['qid']]=array('fieldname'=>$fieldname,'question'=>$arow['question'],'gid'=>$arow['gid']);
                    $fieldmap[$fieldname]=array("fieldname"=>$fieldname, 'type'=>$arow['type'], 'sid'=>$surveyid, "gid"=>$arow['gid'], "qid"=>$arow['qid'], "aid"=>"othercomment");
                    if ($style == "full")
                    {
                        $fieldmap[$fieldname]['title']=$arow['title'];
                        $fieldmap[$fieldname]['question']=$arow['question'];
                        $fieldmap[$fieldname]['subquestion']=$clang->gT('Other comment');
                        $fieldmap[$fieldname]['group_name']=$arow['group_name'];
                        $fieldmap[$fieldname]['mandatory']=$arow['mandatory'];
                        $fieldmap[$fieldname]['hasconditions']=$conditions;
                        $fieldmap[$fieldname]['usedinconditions']=$usedinconditions;
                        $fieldmap[$fieldname]['questionSeq']=$questionSeq;
                        $fieldmap[$fieldname]['groupSeq']=$groupSeq;
                        $fieldmap[$fieldname]['other']=$arow['other'];
                    }
                }
            }
        }
        if (isset($fieldmap[$fieldname])) // only add these fields if there is actually a valid field
        {
            $fieldmap[$fieldname]['relevance']=$arow['relevance'];
            $fieldmap[$fieldname]['grelevance']=$arow['grelevance'];
            $fieldmap[$fieldname]['questionSeq']=$questionSeq;
            $fieldmap[$fieldname]['groupSeq']=$groupSeq;
            $fieldmap[$fieldname]['preg']=$arow['preg'];
            $fieldmap[$fieldname]['other']=$arow['other'];
            $fieldmap[$fieldname]['help']=$arow['help'];
        }
        else
        {
            --$questionSeq; // didn't generate a valid $fieldmap entry, so decrement the question counter to ensure they are sequential
        }
    }
    if (isset($fieldmap)) {
        if ($questionid == false)
        {
            // If the fieldmap was randomized, the master will contain the proper order.  Copy that fieldmap with the new language settings.
            if (isset($_SESSION['fieldmap-' . $surveyid . '-randMaster']))
            {
                $masterFieldmap = $_SESSION['fieldmap-' . $surveyid . '-randMaster'];
                $mfieldmap = $_SESSION[$masterFieldmap];

                foreach ($mfieldmap as $fieldname => $mf)
                {
                    if (isset($fieldmap[$fieldname]))
                    {
                        $f = $fieldmap[$fieldname];
                        if (isset($f['question']))
                        {
                            $mf['question'] = $f['question'];
                        }
                        if (isset($f['subquestion']))
                        {
                            $mf['subquestion'] = $f['subquestion'];
                        }
                        if (isset($f['subquestion1']))
                        {
                            $mf['subquestion1'] = $f['subquestion1'];
                        }
                        if (isset($f['subquestion2']))
                        {
                            $mf['subquestion2'] = $f['subquestion2'];
                        }
                        if (isset($f['group_name']))
                        {
                            $mf['group_name'] = $f['group_name'];
                        }
                        if (isset($f['answerList']))
                        {
                            $mf['answerList'] = $f['answerList'];
                        }
                        if (isset($f['defaultvalue']))
                        {
                            $mf['defaultvalue'] = $f['defaultvalue'];
                        }
                        if (isset($f['help']))
                        {
                            $mf['help'] = $f['help'];
                        }
                    }
                    $mfieldmap[$fieldname] = $mf;
                }
                $fieldmap = $mfieldmap;
            }

            $_SESSION['fieldmap-' . $surveyid . $clang->langcode]=$fieldmap;
        }

        if (isset($_SESSION['adminlang']) && $clang->langcode != $_SESSION['adminlang']) {
            $clang = new limesurvey_lang($_SESSION['adminlang']);
        }
        return $fieldmap;
    }
}


/**
* This function generates an array containing the fieldcode, and matching data in the same order as the activate script
*
* @param string $surveyid The Survey ID
* @param mixed $style 'short' (default) or 'full' - full creates extra information like default values
* @param mixed $force_refresh - Forces to really refresh the array, not just take the session copy
* @param int $questionid Limit to a certain qid only (for question preview) - default is false
* @return array
*/
function createTimingsFieldMap($surveyid, $style='full', $force_refresh=false, $questionid=false, $sQuestionLanguage=null) {

    global $dbprefix, $connect, $clang, $aDuplicateQIDs;
    static $timingsFieldMap;

    $surveyid=sanitize_int($surveyid);
    //checks to see if fieldmap has already been built for this page.
    if (isset($timingsFieldMap[$surveyid][$style][$clang->langcode]) && $force_refresh==false) {
        return $timingsFieldMap[$surveyid][$style][$clang->langcode];
    }

    //do something
    $fields = createFieldMap($surveyid, $style, $force_refresh, $questionid, $sQuestionLanguage);
    $fieldmap['interviewtime']=array('fieldname'=>'interviewtime','type'=>'interview_time','sid'=>$surveyid, 'gid'=>'', 'qid'=>'', 'aid'=>'', 'question'=>$clang->gT('Total time'), 'title'=>'interviewtime');
    foreach ($fields as $field) {
        if (!empty($field['gid'])) {
            // field for time spent on page
            $fieldname="{$field['sid']}X{$field['gid']}time";
            if (!isset($fieldmap[$fieldname]))
            {
                $fieldmap[$fieldname]=array("fieldname"=>$fieldname, 'type'=>"page_time", 'sid'=>$surveyid, "gid"=>$field['gid'], "group_name"=>$field['group_name'], "qid"=>'', 'aid'=>'', 'title'=>'groupTime'.$field['gid'], 'question'=>$clang->gT('Group time').": ".$field['group_name']);
            }

            // field for time spent on answering a question
            $fieldname="{$field['sid']}X{$field['gid']}X{$field['qid']}time";
            if (!isset($fieldmap[$fieldname]))
            {
                $fieldmap[$fieldname]=array("fieldname"=>$fieldname, 'type'=>"answer_time", 'sid'=>$surveyid, "gid"=>$field['gid'], "group_name"=>$field['group_name'], "qid"=>$field['qid'], 'aid'=>'', "title"=>$field['title'].'Time', "question"=>$clang->gT('Question time').": ".$field['title']);
            }
        }
    }

    $timingsFieldMap[$surveyid][$style][$clang->langcode] = $fieldmap;
    return $timingsFieldMap[$surveyid][$style][$clang->langcode];
}

/**
* put your comment there...
*
* @param mixed $needle
* @param mixed $haystack
* @param mixed $keyname
* @param mixed $maxanswers
*/
function arraySearchByKey($needle, $haystack, $keyname, $maxanswers="") {
    $output=array();
    foreach($haystack as $hay) {
        if (array_key_exists($keyname, $hay)) {
            if ($hay[$keyname] == $needle) {
                if ($maxanswers == 1) {
                    return $hay;
                } else {
                    $output[]=$hay;
                }
            }
        }
    }
    return $output;
}


/**
* This function returns a count of the number of saved responses to a survey
*
* @param mixed $surveyid Survey ID
*/
function getSavedCount($surveyid)
{
    global $dbprefix, $connect;
    $surveyid=(int)$surveyid;

    $query = "SELECT COUNT(*) FROM ".db_table_name('saved_control')." WHERE sid=$surveyid";
    $count=$connect->getOne($query);
    return $count;
}

function GetBaseLanguageFromSurveyID($surveyid)
{
    static $cache = array();
    global $connect,$defaultlang;
    $surveyid=(int)($surveyid);
    if (!isset($cache[$surveyid])) {
        $query = "SELECT language FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
        $surveylanguage = $connect->GetOne($query); //Checked
        if (is_null($surveylanguage))
        {
            $surveylanguage=$defaultlang;
        }
        $cache[$surveyid] = $surveylanguage;
    } else {
        $surveylanguage = $cache[$surveyid];
    }
    return $surveylanguage;
}


function GetAdditionalLanguagesFromSurveyID($surveyid)
{
    static $cache = array();
    global $connect;
    $surveyid=sanitize_int($surveyid);
    if (!isset($cache[$surveyid])) {
        $query = "SELECT additional_languages FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
        $additional_languages = $connect->GetOne($query);
        if (trim($additional_languages)=='')
        {
            $additional_languages = array();
        }
        else
        {
            $additional_languages = explode(" ", trim($additional_languages));
        }
        $cache[$surveyid] = $additional_languages;
    } else {
        $additional_languages = $cache[$surveyid];
    }
    return $additional_languages;
}



//For multilanguage surveys
// If null or 0 is given for $surveyid then the default language from config-defaults.php is returned
function SetSurveyLanguage($surveyid, $language)
{
    global $rootdir, $defaultlang, $clang;
    $surveyid=sanitize_int($surveyid);
    require_once($rootdir.'/classes/core/language.php');
    if (isset($surveyid) && $surveyid>0)
    {
        // see if language actually is present in survey
#        $query = "SELECT language, additional_languages FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
#        $result = db_execute_assoc($query); //Checked
#        while ($result && ($row=$result->FetchRow())) {
#            $additional_languages = $row['additional_languages'];
#            $default_language = $row['language'];
#        }
        $default_language=GetBaseLanguageFromSurveyID($surveyid);
        $additional_languages=GetAdditionalLanguagesFromSurveyID($surveyid);
        if  ( !isset($language) || ($language=='')
        || !( in_array($language,$additional_languages) || $language==$default_language)
        )
        {
            // Language not supported, fall back to survey's default language
            $_SESSION['s_lang'] = $default_language;
        }
        else
        {
            $_SESSION['s_lang'] = $language;
        }
        $clang = new limesurvey_lang($_SESSION['s_lang']);
    }
    else {
        $clang = new limesurvey_lang($defaultlang);
    }

    $thissurvey=getSurveyInfo($surveyid, $_SESSION['s_lang']);
    $_SESSION['dateformats'] = getDateFormatData($thissurvey['surveyls_dateformat']);

    LimeExpressionManager::SetEMLanguage($_SESSION['s_lang']);
    return $clang;
}


function buildLabelSetCheckSumArray()
{
    global $connect;
    // BUILD CHECKSUMS FOR ALL EXISTING LABEL SETS
    $query = "SELECT lid
    FROM ".db_table_name('labelsets')."
    ORDER BY lid";
    $result = db_execute_assoc($query) or safe_die("safe_died collecting labelset ids<br />$query<br />".$connect->ErrorMsg());  //Checked
    $csarray=array();
    while ($row=$result->FetchRow())
    {
        $thisset="";
        $query2 = "SELECT code, title, sortorder, language, assessment_value
        FROM ".db_table_name('labels')."
        WHERE lid={$row['lid']}
        ORDER BY language, sortorder, code";
        $result2 = db_execute_num($query2) or safe_die("safe_died querying labelset $lid<br />$query2<br />".$connect->ErrorMsg()); //Checked
        while($row2=$result2->FetchRow())
        {
            $thisset .= implode('.', $row2);
        } // while
        $csarray[$row['lid']]=dechex(crc32($thisset)*1);
    }
    return $csarray;
}


/**
*
* Returns a flat array with all question attributes for the question only (and the qid we gave it)!
* @author: c_schmitz
* @param $qid The question ID
* @param $type optional The question type - saves a DB query if you provide it
* @return array{attribute=>value , attribute=>value} or false if the question ID does not exist (anymore)
*/
function getQuestionAttributes($qid, $type='')
{
    static $cache = array();
    static $availableattributesarr = null;

    if (isset($cache[$qid])) {
        return $cache[$qid];
    }
    if ($type=='')  // If type is not given find it out
    {
        $query = "SELECT type FROM ".db_table_name('questions')." WHERE qid=$qid and parent_qid=0 group by type";
        $result = db_execute_assoc($query) or safe_die("Error finding question attributes");  //Checked
        $row=$result->FetchRow();
        if ($row===false) // Question was deleted while running the survey
        {
            $cache[$qid]=false;
            return false;
        }
        $type=$row['type'];
    }

    //Now read available attributes, make sure we do this only once per request to save
    //processing cycles and memory
    if (is_null($availableattributesarr)) $availableattributesarr=questionAttributes();
    if (isset($availableattributesarr[$type]))
    {
        $availableattributes=$availableattributesarr[$type];
    }
    else
    {
        $cache[$qid]=array();
        return array();
    }

    foreach($availableattributes as $attribute){
        $defaultattributes[$attribute['name']]=$attribute['default'];
    }
    $setattributes=array();
    $qid=sanitize_int($qid);
    $query = "SELECT attribute, value FROM ".db_table_name('question_attributes')." WHERE qid=$qid";
    $result = db_execute_assoc($query) or safe_die("Error finding question attributes");  //Checked
    $setattributes=array();
    while ($row=$result->FetchRow())
    {
        $setattributes[$row['attribute']]=$row['value'];
    }
    //echo "<pre>";print_r($qid_attributes);echo "</pre>";
    $qid_attributes=array_merge($defaultattributes,$setattributes);
    $cache[$qid]=$qid_attributes;
    return $qid_attributes;
}

/**
*
* Returns the questionAttribtue value set or '' if not set
* @author: lemeur
* @param $questionAttributeArray
* @param $attributeName
* @return string
*/
function getQuestionAttributeValue($questionAttributeArray, $attributeName)
{
    if (isset($questionAttributeArray[$attributeName]))
    {
        return $questionAttributeArray[$attributeName];
    }
    else
    {
        return '';
    }
}

/**
* Returns array of question type chars with attributes
*
* @param mixed $returnByName If set to true the array will be by attribute name
*/
function questionAttributes($returnByName=false)
{
    global $clang;
    //For each question attribute include a key:
    // name - the display name
    // types - a string with one character representing each question typy to which the attribute applies
    // help - a short explanation

    // If you insert a new attribute please do it in correct alphabetical order!

    $qattributes["alphasort"]=array(
    "types"=>"!LO",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT("Sort the answer options alphabetically"),
    "caption"=>$clang->gT('Sort answers alphabetically'));

    $qattributes["answer_width"]=array(
    "types"=>"ABCEF1:;",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'integer',
    'min'=>'1',
    'max'=>'100',
    "help"=>$clang->gT('Set the percentage width of the answer column (1-100)'),
    "caption"=>$clang->gT('Answer width'));

    $qattributes["array_filter"]=array(
    "types"=>"1ABCEF:;MPLKQ",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT("Enter the code(s) of Multiple choice question(s) (separated by semicolons) to only show the matching answer options in this question."),
    "caption"=>$clang->gT('Array filter'));

    $qattributes["array_filter_exclude"]=array(
    "types"=>"1ABCEF:;MPLKQ",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT("Enter the code(s) of Multiple choice question(s) (separated by semicolons) to exclude the matching answer options in this question."),
    "caption"=>$clang->gT('Array filter exclusion'));

    $qattributes["array_filter_style"]=array(
    "types"=>"1ABCEF:;MPLKQ",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('Hidden'),
    1=>$clang->gT('Disabled')),
    'default'=>0,
    "help"=>$clang->gT("Specify how array-filtered sub-questions should be displayed"),
    "caption"=>$clang->gT('Array filter style'));

    $qattributes["assessment_value"]=array(
    "types"=>"MP",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>100,
    'default'=>'1',
    'inputtype'=>'integer',
    "help"=>$clang->gT("If one of the subquestions is marked then for each marked subquestion this value is added as assessment."),
    "caption"=>$clang->gT('Assessment value'));

    $qattributes["category_separator"]=array(
    "types"=>"!",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('Category separator'),
    "caption"=>$clang->gT('Category separator'));

    $qattributes["display_columns"]=array(
    "types"=>"LM",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'integer',
    'default'=>'1',
    'min'=>'1',
    'max'=>'100',
    "help"=>$clang->gT('The answer options will be distributed across the number of columns set here'),
    "caption"=>$clang->gT('Display columns'));

    $qattributes["display_rows"]=array(
    "types"=>"QSTU",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('How many rows to display'),
    "caption"=>$clang->gT('Display rows'));

    $qattributes["dropdown_dates"]=array(
    "types"=>"D",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Use accessible dropdown boxes instead of calendar popup'),
    "caption"=>$clang->gT('Display dropdown boxes'));

    $qattributes["dropdown_dates_year_min"]=array(
    "types"=>"D",
    'category'=>$clang->gT('Display'),
    'sortorder'=>110,
    'inputtype'=>'text',
    "help"=>$clang->gT('Minimum year value in calendar'),
    "caption"=>$clang->gT('Minimum year'));

    $qattributes["dropdown_dates_year_max"]=array(
    "types"=>"D",
    'category'=>$clang->gT('Display'),
    'sortorder'=>111,
    'inputtype'=>'text',
    "help"=>$clang->gT('Maximum year value for calendar'),
    "caption"=>$clang->gT('Maximum year'));

    $qattributes["dropdown_prepostfix"]=array(
    "types"=>"1",
    'category'=>$clang->gT('Display'),
    'sortorder'=>112,
    'inputtype'=>'text',
    "help"=>$clang->gT('Prefix|Suffix for dropdown lists'),
    "caption"=>$clang->gT('Dropdown prefix/suffix'));

    $qattributes["dropdown_separators"]=array(
    "types"=>"1",
    'category'=>$clang->gT('Display'),
    'sortorder'=>120,
    'inputtype'=>'text',
    "help"=>$clang->gT('Post-Answer-Separator|Inter-Dropdownlist-Separator for dropdown lists'),
    "caption"=>$clang->gT('Dropdown separator'));

    $qattributes["dualscale_headerA"]=array(
    "types"=>"1",
    'category'=>$clang->gT('Display'),
    'sortorder'=>110,
    'inputtype'=>'text',
    "help"=>$clang->gT('Enter a header text for the first scale'),
    "caption"=>$clang->gT('Header for first scale'));

    $qattributes["dualscale_headerB"]=array(
    "types"=>"1",
    'category'=>$clang->gT('Display'),
    'sortorder'=>111,
    'inputtype'=>'text',
    "help"=>$clang->gT('Enter a header text for the second scale'),
    "caption"=>$clang->gT('Header for second scale'));

    $qattributes["equals_num_value"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('Multiple numeric inputs sum must equal this value'),
    "caption"=>$clang->gT('Equals sum value'));

    $qattributes["em_validation_q"]=array(
    "types"=>":;ABCEFKMNPQRSTU",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>200,
    'inputtype'=>'textarea',
    "help"=>$clang->gT('Boolean equation to validate the whole question.'),
    "caption"=>$clang->gT('Question validation equation'));

    $qattributes["em_validation_q_tip"]=array(
    "types"=>":;ABCEFKMNPQRSTU",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>210,
    'inputtype'=>'textarea',
    "help"=>$clang->gT('Tip to show user describing the question validation equation.'),
    "caption"=>$clang->gT('Question validation tip'));

    $qattributes["em_validation_sq"]=array(
    "types"=>";:KQSTUN",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>220,
    'inputtype'=>'textarea',
    "help"=>$clang->gT('Boolean equation to validate each sub-question.'),
    "caption"=>$clang->gT('Sub-question validation equation'));

    $qattributes["em_validation_sq_tip"]=array(
    "types"=>";:KQSTUN",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>230,
    'inputtype'=>'textarea',
    "help"=>$clang->gT('Tip to show user describing the sub-question validation equation.'),
    "caption"=>$clang->gT('Sub-question validation tip'));

    $qattributes["exclude_all_others"]=array(
    "types"=>":ABCEFMPKQ",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>130,
    'inputtype'=>'text',
    "help"=>$clang->gT('Excludes all other options if a certain answer is selected - just enter the answer code(s) seperated with a semikolon.'),
    "caption"=>$clang->gT('Exclusive option'));

    $qattributes["exclude_all_others_auto"]=array(
    "types"=>"MP",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>131,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('If the participant marks all options, uncheck all and check the option set in the "Exclusive option" setting'),
    "caption"=>$clang->gT('Auto-check exclusive option if all others are checked'));

    // Map Options

    $qattributes["location_city"]=array(
    "types"=>"S",
    'readonly_when_active'=>true,
    'category'=>$clang->gT('Location'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('Yes'),
    1=>$clang->gT('No')),
    "help"=>$clang->gT("Store the city?"),
    "caption"=>$clang->gT("Save city"));

    $qattributes["location_state"]=array(
    "types"=>"S",
    'readonly_when_active'=>true,
    'category'=>$clang->gT('Location'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('Yes'),
    1=>$clang->gT('No')),
    "help"=>$clang->gT("Store the state?"),
    "caption"=>$clang->gT("Save state"));

    $qattributes["location_postal"]=array(
    "types"=>"S",
    'readonly_when_active'=>true,
    'category'=>$clang->gT('Location'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('Yes'),
    1=>$clang->gT('No')),
    "help"=>$clang->gT("Store the postal code?"),
    "caption"=>$clang->gT("Save postal code"));

    $qattributes["location_country"]=array(
    "types"=>"S",
    'readonly_when_active'=>true,
    'category'=>$clang->gT('Location'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('Yes'),
    1=>$clang->gT('No')),
    "help"=>$clang->gT("Store the country?"),
    "caption"=>$clang->gT("Save country"));

    $qattributes["location_mapservice"]=array(
    "types"=>"S",
    'category'=>$clang->gT('Location'),
    'sortorder'=>90,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('Off'),
    1=>$clang->gT('Google Maps')),
    "help"=>$clang->gT("Activate this to show a map above the input field where the user can select a location"),
    "caption"=>$clang->gT("Use mapping service"));

    $qattributes["location_mapwidth"]=array(
    "types"=>"S",
    'category'=>$clang->gT('Location'),
    'sortorder'=>102,
    'inputtype'=>'text',
    'default'=>'500',
    "help"=>$clang->gT("Width of the map in pixel"),
    "caption"=>$clang->gT("Map width"));

    $qattributes["location_mapheight"]=array(
    "types"=>"S",
    'category'=>$clang->gT('Location'),
    'sortorder'=>103,
    'inputtype'=>'text',
    'default'=>'300',
    "help"=>$clang->gT("Height of the map in pixel"),
    "caption"=>$clang->gT("Map height"));

    $qattributes["location_nodefaultfromip"]=array(
    "types"=>"S",
    'category'=>$clang->gT('Location'),
    'sortorder'=>91,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('Yes'),
    1=>$clang->gT('No')),
    "help"=>$clang->gT("Get the default location using the user's IP address?"),
    "caption"=>$clang->gT("IP as default location"));

    $qattributes["location_defaultcoordinates"]=array(
    "types"=>"S",
    'category'=>$clang->gT('Location'),
    'sortorder'=>101,
    'inputtype'=>'text',
    "help"=>$clang->gT('Default coordinates of the map when the page first loads. Format: latitude [space] longtitude'),
    "caption"=>$clang->gT('Default position'));

    $qattributes["location_mapzoom"]=array(
    "types"=>"S",
    'category'=>$clang->gT('Location'),
    'sortorder'=>101,
    'inputtype'=>'text',
    'default'=>'11',
    "help"=>$clang->gT("Map zoom level"),
    "caption"=>$clang->gT("Zoom level"));

    // End Map Options

    $qattributes["hide_tip"]=array(
    "types"=>"15ABCDEFGHIKLMNOPQRSTUXY!:;|",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Hide the tip that is normally shown with a question'),
    "caption"=>$clang->gT('Hide tip'));

    $qattributes['hidden']=array(
    'types'=>'15ABCDEFGHIKLMNOPQRSTUXY!:;|*',
    'category'=>$clang->gT('Display'),
    'sortorder'=>101,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    'help'=>$clang->gT('Hide this question at any time. This is useful for including data using answer prefilling.'),
    'caption'=>$clang->gT('Always hide this question'));

    $qattributes["max_answers"]=array(
    "types"=>"MPR1:;ABCEFKQ",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>11,
    'inputtype'=>'integer',
    "help"=>$clang->gT('Limit the number of possible answers'),
    "caption"=>$clang->gT('Maximum answers'));

    $qattributes["max_num_value"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('Maximum sum value of multiple numeric input'),
    "caption"=>$clang->gT('Maximum sum value'));

    $qattributes["max_num_value_n"]=array(
    "types"=>"NK",
    'category'=>$clang->gT('Input'),
    'sortorder'=>110,
    'inputtype'=>'integer',
    "help"=>$clang->gT('Maximum value of the numeric input'),
    "caption"=>$clang->gT('Maximum value'));

    $qattributes["maximum_chars"]=array(
    "types"=>"STUNQK:;",
    'category'=>$clang->gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('Maximum characters allowed'),
    "caption"=>$clang->gT('Maximum characters'));

    $qattributes["min_answers"]=array(
    "types"=>"MPR1:;ABCEFKQ",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>10,
    'inputtype'=>'integer',
    "help"=>$clang->gT('Ensure a minimum number of possible answers (0=No limit)'),
    "caption"=>$clang->gT('Minimum answers'));

    $qattributes["min_num_value"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('The sum of the multiple numeric inputs must be greater than this value'),
    "caption"=>$clang->gT('Minimum sum value'));

    $qattributes["min_num_value_n"]=array(
    "types"=>"NK",
    'category'=>$clang->gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'integer',
    "help"=>$clang->gT('Minimum value of the numeric input'),
    "caption"=>$clang->gT('Minimum value'));

    $qattributes["multiflexible_max"]=array(
    "types"=>":",
    'category'=>$clang->gT('Display'),
    'sortorder'=>112,
    'inputtype'=>'text',
    "help"=>$clang->gT('Maximum value for array(mult-flexible) question type'),
    "caption"=>$clang->gT('Maximum value'));

    $qattributes["multiflexible_min"]=array(
    "types"=>":",
    'category'=>$clang->gT('Display'),
    'sortorder'=>110,
    'inputtype'=>'text',
    "help"=>$clang->gT('Minimum value for array(multi-flexible) question type'),
    "caption"=>$clang->gT('Minimum value'));

    $qattributes["multiflexible_step"]=array(
    "types"=>":",
    'category'=>$clang->gT('Display'),
    'sortorder'=>111,
    'inputtype'=>'text',
    "help"=>$clang->gT('Step value'),
    "caption"=>$clang->gT('Step value'));

    $qattributes["multiflexible_checkbox"]=array(
    "types"=>":",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Use checkbox layout'),
    "caption"=>$clang->gT('Checkbox layout'));

    $qattributes["reverse"]=array(
    "types"=>"D:",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Present answer options in reverse order'),
    "caption"=>$clang->gT('Reverse answer order'));

    $qattributes["num_value_int_only"]=array(
    "types"=>"N",
    'category'=>$clang->gT('Input'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(
    0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Restrict input to integer values'),
    "caption"=>$clang->gT('Integer only'));

    $qattributes["numbers_only"]=array(
    "types"=>"Q;S*",
    'category'=>$clang->gT('Other'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(
    0=>$clang->gT('No'),
    1=>$clang->gT('Yes')
    ),
    'default'=>0,
    "help"=>$clang->gT('Allow only numerical input'),
    "caption"=>$clang->gT('Numbers only')
    );

    $qattributes['show_totals'] =	array(
    'types' =>	';',
    'category' =>	$clang->gT('Other'),
    'sortorder' =>	100,
    'inputtype'	=> 'singleselect',
    'options' =>	array(
    'X' =>	$clang->gT('Off'),
    'R' =>	$clang->gT('Rows'),
    'C' =>	$clang->gT('Columns'),
    'B' =>	$clang->gT('Both rows and columns')
    ),
    'default' =>	'X',
    'help' =>	$clang->gT('Show totals for either rows, columns or both rows and columns'),
    'caption' =>	$clang->gT('Show totals for')
    );

    $qattributes['show_grand_total'] =	array(
    'types' =>	';',
    'category' =>	$clang->gT('Other'),
    'sortorder' =>	100,
    'inputtype' =>	'singleselect',
    'options' =>	array(
    0 =>	$clang->gT('No'),
    1 =>	$clang->gT('Yes')
    ),
    'default' =>	0,
    'help' =>	$clang->gT('Show grand total for either columns or rows'),
    'caption' =>	$clang->gT('Show grand total')
    );

    $qattributes["input_boxes"]=array(
    "types"=>":",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT("Present as text input boxes instead of dropdown lists"),
    "caption"=>$clang->gT("Text inputs"));

    $qattributes["other_comment_mandatory"]=array(
    "types"=>"PL!",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT("Make the 'Other:' comment field mandatory when the 'Other:' option is active"),
    "caption"=>$clang->gT("'Other:' comment mandatory"));

    $qattributes["other_numbers_only"]=array(
    "types"=>"LMP",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT("Allow only numerical input for 'Other' text"),
    "caption"=>$clang->gT("Numbers only for 'Other'"));

    $qattributes["other_replace_text"]=array(
    "types"=>"LMP!",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT("Replaces the label of the 'Other:' answer option with a custom text"),
    "caption"=>$clang->gT("Label for 'Other:' option"));

    $qattributes["page_break"]=array(
    "types"=>"15ABCDEFGHKLMNOPQRSTUXY!:;|*",
    'category'=>$clang->gT('Other'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Insert a page break before this question in printable view by setting this to Yes.'),
    "caption"=>$clang->gT('Insert page break in printable view'));

    $qattributes["prefix"]=array(
    "types"=>"KNQS",
    'category'=>$clang->gT('Display'),
    'sortorder'=>10,
    'inputtype'=>'text',
    "help"=>$clang->gT('Add a prefix to the answer field'),
    "caption"=>$clang->gT('Answer prefix'));

    $qattributes["public_statistics"]=array(
    "types"=>"15ABCEFGHKLMNOPRY!:*",
    'category'=>$clang->gT('Other'),
    'sortorder'=>80,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Show statistics of this question in the public statistics page'),
    "caption"=>$clang->gT('Show in public statistics'));

    $qattributes["random_order"]=array(
    "types"=>"!ABCEFHKLMOPQR1:;",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Present answers in random order'),
    "caption"=>$clang->gT('Random answer order'));

    $qattributes["slider_layout"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Slider'),
    'sortorder'=>1,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Use slider layout'),
    "caption"=>$clang->gT('Use slider layout'));

    $qattributes["slider_min"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('Slider minimum value'),
    "caption"=>$clang->gT('Slider minimum value'));

    $qattributes["slider_max"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('Slider maximum value'),
    "caption"=>$clang->gT('Slider maximum value'));

    $qattributes["slider_accuracy"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('Slider accuracy'),
    "caption"=>$clang->gT('Slider accuracy'));

    $qattributes["slider_default"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('Slider initial value'),
    "caption"=>$clang->gT('Slider initial value'));

    $qattributes["slider_middlestart"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Slider'),
    'sortorder'=>10,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('The handle is displayed at the middle of the slider (this will not set the initial value)'),
    "caption"=>$clang->gT('Slider starts at the middle position'));

    $qattributes["slider_rating"]=array(
    "types"=>"5",
    'category'=>$clang->gT('Display'),
    'sortorder'=>90,
    'inputtype'=>'singleselect',
    'options'=>array(
    0=>$clang->gT('No'),
    1=>$clang->gT('Yes - stars'),
    2=>$clang->gT('Yes - slider with emoticon'),
    ),
    'default'=>0,
    "help"=>$clang->gT('Use slider layout'),
    "caption"=>$clang->gT('Use slider layout'));


    $qattributes["slider_showminmax"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Display min and max value under the slider'),
    "caption"=>$clang->gT('Display slider min and max value'));

    $qattributes["slider_separator"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Slider'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('Answer|Left-slider-text|Right-slider-text separator character'),
    "caption"=>$clang->gT('Slider left/right text separator'));

    $qattributes["suffix"]=array(
    "types"=>"KNQS",
    'category'=>$clang->gT('Display'),
    'sortorder'=>11,
    'inputtype'=>'text',
    "help"=>$clang->gT('Add a suffix to the answer field'),
    "caption"=>$clang->gT('Answer suffix'));

    $qattributes["text_input_width"]=array(
    "types"=>"KNSTUQ;",
    'category'=>$clang->gT('Display'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT('Width of text input box'),
    "caption"=>$clang->gT('Input box width'));

    $qattributes["use_dropdown"]=array(
    "types"=>"1F",
    'category'=>$clang->gT('Display'),
    'sortorder'=>112,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>0,
    "help"=>$clang->gT('Use dropdown boxes instead of list of radio buttons'),
    "caption"=>$clang->gT('Use dropdown boxes'));

    $qattributes["dropdown_size"]=array(
    "types"=>"!",   // TODO add these later?  "1F",
    'category'=>$clang->gT('Display'),
    'sortorder'=>200,
    'inputtype'=>'text',
    'default'=>0,
    "help"=>$clang->gT('For list dropdown boxes, show up to this many rows'),
    "caption"=>$clang->gT('Height of dropdown'));

    $qattributes["dropdown_prefix"]=array(
    "types"=>"!",   // TODO add these later?  "1F",
    'category'=>$clang->gT('Display'),
    'sortorder'=>201,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('None'),
    1=>$clang->gT('Order'),
    ),
    'default'=>0,
    "help"=>$clang->gT('Accelerator keys for list items'),
    "caption"=>$clang->gT('Prefix for list items'));

    $qattributes["scale_export"]=array(
    "types"=>"CEFGHLMOPY1!:*",
    'category'=>$clang->gT('Other'),
    'sortorder'=>100,
    'inputtype'=>'singleselect',
    'options'=>array(0=>$clang->gT('Default'),
    1=>$clang->gT('Nominal'),
    2=>$clang->gT('Ordinal'),
    3=>$clang->gT('Scale')),
    'default'=>0,
    "help"=>$clang->gT("Set a specific SPSS export scale type for this question"),
    "caption"=>$clang->gT('SPSS export scale type'));

    //Timer attributes
    $qattributes["time_limit"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>90,
    "inputtype"=>"integer",
    "help"=>$clang->gT("Limit time to answer question (in seconds)"),
    "caption"=>$clang->gT("Time limit"));

    $qattributes["time_limit_action"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>92,
    'inputtype'=>'singleselect',
    'options'=>array(1=>$clang->gT('Warn and move on'),
    2=>$clang->gT('Move on without warning'),
    3=>$clang->gT('Disable only')),
    "help"=>$clang->gT("Action to perform when time limit is up"),
    "caption"=>$clang->gT("Time limit action"));

    $qattributes["time_limit_disable_next"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>94,
    "inputtype"=>"singleselect",
    'default'=>0,
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    "help"=>$clang->gT("Disable the next button until time limit expires"),
    "caption"=>$clang->gT("Time limit disable next"));

    $qattributes["time_limit_disable_prev"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>96,
    "inputtype"=>"singleselect",
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    "help"=>$clang->gT("Disable the prev button until the time limit expires"),
    "caption"=>$clang->gT("Time limit disable prev"));

    $qattributes["time_limit_countdown_message"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>98,
    "inputtype"=>"textarea",
    "help"=>$clang->gT("The text message that displays in the countdown timer during the countdown"),
    "caption"=>$clang->gT("Time limit countdown message"));

    $qattributes["time_limit_timer_style"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>100,
    "inputtype"=>"textarea",
    "help"=>$clang->gT("CSS Style for the message that displays in the countdown timer during the countdown"),
    "caption"=>$clang->gT("Time limit timer CSS style"));

    $qattributes["time_limit_message_delay"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>102,
    "inputtype"=>"integer",
    "help"=>$clang->gT("Display the 'time limit expiry message' for this many seconds before performing the 'time limit action' (defaults to 1 second if left blank)"),
    "caption"=>$clang->gT("Time limit expiry message display time"));

    $qattributes["time_limit_message"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>104,
    "inputtype"=>"textarea",
    "help"=>$clang->gT("The message to display when the time limit has expired (a default message will display if this setting is left blank)"),
    "caption"=>$clang->gT("Time limit expiry message"));

    $qattributes["time_limit_message_style"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>106,
    "inputtype"=>"textarea",
    "help"=>$clang->gT("CSS style for the 'time limit expiry message'"),
    "caption"=>$clang->gT("Time limit message CSS style"));

    $qattributes["time_limit_warning"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>108,
    "inputtype"=>"integer",
    "help"=>$clang->gT("Display a 'time limit warning' when there are this many seconds remaining in the countdown (warning will not display if left blank)"),
    "caption"=>$clang->gT("1st time limit warning message timer"));

    $qattributes["time_limit_warning_display_time"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>110,
    "inputtype"=>"integer",
    "help"=>$clang->gT("The 'time limit warning' will stay visible for this many seconds (will not turn off if this setting is left blank)"),
    "caption"=>$clang->gT("1st time limit warning message display time"));

    $qattributes["time_limit_warning_message"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>112,
    "inputtype"=>"textarea",
    "help"=>$clang->gT("The message to display as a 'time limit warning' (a default warning will display if this is left blank)"),
    "caption"=>$clang->gT("1st time limit warning message"));

    $qattributes["time_limit_warning_style"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>114,
    "inputtype"=>"textarea",
    "help"=>$clang->gT("CSS style used when the 'time limit warning' message is displayed"),
    "caption"=>$clang->gT("1st time limit warning CSS style"));

    $qattributes["time_limit_warning_2"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>116,
    "inputtype"=>"integer",
    "help"=>$clang->gT("Display the 2nd 'time limit warning' when there are this many seconds remaining in the countdown (warning will not display if left blank)"),
    "caption"=>$clang->gT("2nd time limit warning message timer"));

    $qattributes["time_limit_warning_2_display_time"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>118,
    "inputtype"=>"integer",
    "help"=>$clang->gT("The 2nd 'time limit warning' will stay visible for this many seconds (will not turn off if this setting is left blank)"),
    "caption"=>$clang->gT("2nd time limit warning message display time"));

    $qattributes["time_limit_warning_2_message"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>120,
    "inputtype"=>"textarea",
    "help"=>$clang->gT("The 2nd message to display as a 'time limit warning' (a default warning will display if this is left blank)"),
    "caption"=>$clang->gT("2nd time limit warning message"));

    $qattributes["time_limit_warning_2_style"]=array(
    "types"=>"STUX",
    'category'=>$clang->gT('Timer'),
    'sortorder'=>122,
    "inputtype"=>"textarea",
    "help"=>$clang->gT("CSS style used when the 2nd 'time limit warning' message is displayed"),
    "caption"=>$clang->gT("2nd time limit warning CSS style"));

    $qattributes["show_title"]=array(
    "types"=>"|",
    'category'=>$clang->gT('File metadata'),
    'sortorder'=>124,
    "inputtype"=>"singleselect",
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>1,
    "help"=>$clang->gT("Is the participant required to give a title to the uploaded file?"),
    "caption"=>$clang->gT("Show title"));

    $qattributes["show_comment"]=array(
    "types"=>"|",
    'category'=>$clang->gT('File metadata'),
    'sortorder'=>126,
    "inputtype"=>"singleselect",
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>1,
    "help"=>$clang->gT("Is the participant required to give a comment to the uploaded file?"),
    "caption"=>$clang->gT("Show comment"));


    $qattributes["max_filesize"]=array(
    "types"=>"|",
    'category'=>$clang->gT('Other'),
    'sortorder'=>128,
    "inputtype"=>"integer",
    'default'=>1024,
    "help"=>$clang->gT("The participant cannot upload a single file larger than this size"),
    "caption"=>$clang->gT("Maximum file size allowed (in KB)"));

    $qattributes["max_num_of_files"]=array(
    "types"=>"|",
    'category'=>$clang->gT('Other'),
    'sortorder'=>130,
    "inputtype"=>"text",
    'default'=>1,
    "help"=>$clang->gT("Maximum number of files that the participant can upload for this question"),
    "caption"=>$clang->gT("Max number of files"));

    $qattributes["min_num_of_files"]=array(
    "types"=>"|",
    'category'=>$clang->gT('Other'),
    'sortorder'=>132,
    "inputtype"=>"text",
    'default'=>0,
    "help"=>$clang->gT("Minimum number of files that the participant must upload for this question"),
    "caption"=>$clang->gT("Min number of files"));

    $qattributes["allowed_filetypes"]=array(
    "types"=>"|",
    'category'=>$clang->gT('Other'),
    'sortorder'=>134,
    'inputtype'=>'text',
    'default'=>"png, gif, doc, odt",
    "help"=>$clang->gT("Allowed file types in comma separated format. e.g. pdf,doc,odt"),
    "caption"=>$clang->gT("Allowed file types"));

    $qattributes["random_group"]=array(
    "types"=>"15ABCDEFGHIKLMNOPQRSTUXY!:;|",
    'category'=>$clang->gT('Logic'),
    'sortorder'=>100,
    'inputtype'=>'text',
    "help"=>$clang->gT("Place questions into a specified randomization group, all questions included in the specified group will appear in a random order"),
    "caption"=>$clang->gT("Randomization group name"));

    // This is added to support historical behavior.  Early versions of 1.92 used a value of "No", so if there was a min_sum_value or equals_sum_value, the question was not valid
    // unless those criteria were met.  In later releases of 1.92, the default was changed so that missing values were allowed even if those attributes were set
    // This attribute lets authors control whether missing values should be allowed in those cases without needing to set min_answers
    // Existing surveys will use the old behavior, but if the author edits the question, the default will be the new behavior.
    $qattributes["value_range_allows_missing"]=array(
    "types"=>"K",
    'category'=>$clang->gT('Input'),
    'sortorder'=>100,
    "inputtype"=>"singleselect",
    'options'=>array(0=>$clang->gT('No'),
    1=>$clang->gT('Yes')),
    'default'=>1,
    "help"=>$clang->gT("Is no answer (missing) allowed when either 'Equals sum value' or 'Minimum sum value' are set?"),
    "caption"=>$clang->gT("Value range allows missing"));

    //This builds a more useful array (don't modify)
    if ($returnByName==false)
    {
        foreach($qattributes as $qname=>$qvalue)
        {
            for ($i=0; $i<=strlen($qvalue['types'])-1; $i++)
            {
                $qat[substr($qvalue['types'], $i, 1)][]=array("name"=>$qname,
                "inputtype"=>$qvalue['inputtype'],
                "category"=>$qvalue['category'],
                "sortorder"=>$qvalue['sortorder'],
                "readonly"=>isset($qvalue['readonly_when_active'])?$qvalue['readonly_when_active']:false,
                "options"=>isset($qvalue['options'])?$qvalue['options']:'',
                "default"=>isset($qvalue['default'])?$qvalue['default']:'',
                "help"=>$qvalue['help'],
                "caption"=>$qvalue['caption']);
            }
        }
        return $qat;
    }
    else {
        return $qattributes;
    }
}


function CategorySort($a, $b)
{
    $result=strnatcasecmp($a['category'], $b['category']);
    if ($result==0)
    {
        $result=$a['sortorder']-$b['sortorder'];
    }
    return $result;
}

if (!function_exists('get_magic_quotes_gpc')) {
    /**
    * Gets the current configuration setting of magic_quotes_gpc
    * NOTE: Compat variant for PHP 6+ versions
    *
    * @link http://www.php.net/manual/en/function.get-magic-quotes-gpc.php
    * @return int 0 if magic_quotes_gpc is off, 1 otherwise.
    */
    function get_magic_quotes_gpc() {
        return 0;
    }
}

// make sure the given string (which comes from a POST or GET variable)
// is safe to use in MySQL.  This does nothing if gpc_magic_quotes is on.
function auto_escape($str) {
    global $connect;
    if (!get_magic_quotes_gpc()) {
        return $connect->escape($str);
    }
    return $str;
}
// the opposite of the above: takes a POST or GET variable which may or
// may not have been 'auto-quoted', and return the *unquoted* version.
// this is useful when the value is destined for a web page (eg) not
// a SQL query.
function auto_unescape($str) {
    if (!isset($str)) {return null;};
    if (!get_magic_quotes_gpc()) {
        return $str;
    }
    return stripslashes($str);
}
// make a string safe to include in an HTML 'value' attribute.
function html_escape($str) {
    // escape newline characters, too, in case we put a value from
    // a TEXTAREA  into an <input type="hidden"> value attribute.
    return str_replace(array("\x0A","\x0D"),array("&#10;","&#13;"),
    htmlspecialchars( $str, ENT_QUOTES ));
}

// make a string safe to include in a JavaScript String parameter.
function javascript_escape($str, $strip_tags=false, $htmldecode=false) {
    $new_str ='';

    if ($htmldecode==true) {
        $str=html_entity_decode($str,ENT_QUOTES,'UTF-8');
    }
    if ($strip_tags==true)
    {
        $str=strip_tags($str);
    }
    return str_replace(array('\'','"', "\n", "\r"),
    array("\\'",'\u0022', "\\n",'\r'),
    $str);
}

// This function returns the header as result string
// If you want to echo the header use doHeader() !
function getHeader($meta = false)
{
    global $embedded, $surveyid, $rooturl, $defaultlang, $js_header_includes, $css_header_includes;

    $js_header_includes = array_unique($js_header_includes);
    $css_header_includes = array_unique($css_header_includes);

  $interviewer=returnglobal('interviewer');
  if (empty($interviewer))
  {
    $interviewer = false;
  }
  if (!isset($_SESSION['interviewer'])) {
    $_SESSION['interviewer'] = $interviewer;
  }
     if ($SESSION['interviewer'])
    {
    	$js_header_includes[] = '/../../js/popup.js'; //queXS Addition
	    include_once("quexs.php");
	    if (AUTO_LOGOUT_MINUTES !== false)
	    {
	        $js_header_includes[] = $rooturl . "/../../js/childnap.js"; //queXS Addition
	    }
    }
	
    if (isset($_SESSION['s_lang']) && $_SESSION['s_lang'])
    {
        $surveylanguage= $_SESSION['s_lang'];
    }
    elseif (isset($surveyid) && $surveyid)
    {
        $surveylanguage=GetBaseLanguageFromSurveyID($surveyid);
    }
    else
    {
        $surveylanguage=$defaultlang;
    }

    $js_header = ''; $css_header='';
    foreach ($js_header_includes as $jsinclude)
    {
        if (substr($jsinclude,0,4) == 'http')
            $js_header .= "<script type=\"text/javascript\" src=\"$jsinclude\"></script>\n";
        else
            $js_header .= "<script type=\"text/javascript\" src=\"".$rooturl."$jsinclude\"></script>\n";
    }

    foreach ($css_header_includes as $cssinclude)
    {
        $css_header .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"".$rooturl.$cssinclude."\" />\n";
    }



    $header=  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n"
    . "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"".$surveylanguage."\" lang=\"".$surveylanguage."\"";
    if (getLanguageRTL($surveylanguage))
    {
        $header.=" dir=\"rtl\" ";
    }
    $header.= ">\n\t<head>\n"
    . $css_header
    . "<script type=\"text/javascript\" src=\"".$rooturl."/scripts/jquery/jquery.js\"></script>\n"
    . "<script type=\"text/javascript\" src=\"".$rooturl."/scripts/jquery/jquery-ui.js\"></script>\n"
    . "<script type=\"text/javascript\" src=\"".$rooturl."/scripts/jquery/jquery.ui.touch-punch.min.js\"></script>\n"
    . "<link href=\"".$rooturl."/scripts/jquery/css/start/jquery-ui.css\" media=\"all\" type=\"text/css\" rel=\"stylesheet\" />"
    . "<link href=\"".$rooturl."/scripts/jquery/css/start/lime-progress.css\" media=\"all\" type=\"text/css\" rel=\"stylesheet\" />"
    . $js_header;

    if ($meta)
        $header .= $meta;

    if ( !$embedded )
    {
        return $header;
    }
    else
    {
        global $embedded_headerfunc;
        if ( function_exists( $embedded_headerfunc ) )
            return $embedded_headerfunc($header);
    }
}

function doHeader()
{
    echo getHeader();
}

function doAdminFooter()
{
    echo getAdminFooter();
}

function getAdminFooter($url, $explanation)
{
    global $js_admin_includes, $homeurl;
    global $versionnumber, $buildnumber, $setfont, $imageurl, $clang;

    if ($buildnumber != "")
    {
        $buildtext="Build $buildnumber";
    }
    else
    {
        $buildtext="";
    }

    //If user is not logged in, don't print the version number information in the footer.
    $versiontitle=$clang->gT('Version');
    if(!isset($_SESSION['loginID']))
    {
        $versionnumber="";
        $buildtext="";
        $versiontitle="";
    }

    $strHTMLFooter = "<div class='footer'>\n"
    . "<div style='float:left;width:110px;text-align:left;'><img alt='LimeSurvey - ".$clang->gT("Online Manual")."' title='LimeSurvey - ".$clang->gT("Online Manual")."' src='$imageurl/docs.png' "
    . "onclick=\"window.open('$url')\" onmouseover=\"document.body.style.cursor='pointer'\" "
    . "onmouseout=\"document.body.style.cursor='auto'\" /></div>\n"
    . "<div style='float:right;'><img alt='".$clang->gT("Support this project - Donate to ")."LimeSurvey' title='".$clang->gT("Support this project - Donate to ")."LimeSurvey!' src='$imageurl/donate.png' "
    . "onclick=\"window.open('http://www.donate.limesurvey.org')\" "
    . "onmouseover=\"document.body.style.cursor='pointer'\" onmouseout=\"document.body.style.cursor='auto'\" /></div>\n"
    . "<div class='subtitle'><a class='subtitle' title='".$clang->gT("Visit our website!")."' href='http://www.limesurvey.org' target='_blank'>LimeSurvey</a><br />".$versiontitle." $versionnumber $buildtext</div>"
    . "</div>\n";
    $js_admin_includes = array_unique($js_admin_includes);
    foreach ($js_admin_includes as $jsinclude)
    {
        $strHTMLFooter .= "<script type=\"text/javascript\" src=\"".$jsinclude."\"></script>\n";
    }

    $strHTMLFooter.="</body>\n</html>";
    return $strHTMLFooter;
}




/**
* This function returns the header for the printable survey
* @return String
*
*/
function getPrintableHeader()
{
    global $rooturl,$homeurl;
    $headelements = '
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <script type="text/javascript" src="'.$rooturl.'/scripts/jquery/jquery.js"></script>
    <script type="text/javascript" src="'.$homeurl.'/scripts/printablesurvey.js"></script>
    ';
    return $headelements;
}



// This function returns the Footer as result string
// If you want to echo the Footer use doFooter() !
function getFooter()
{
    global $embedded;

    if ( !$embedded )
    {
        return "\n\n\t</body>\n</html>\n";
    }
    else
    {
        global $embedded_footerfunc;
        if ( function_exists( $embedded_footerfunc ) )
            return $embedded_footerfunc();
    }
}


function doFooter()
{
    echo getFooter();
}



// This function replaces field names in a text with the related values
// (e.g. for email and template functions)
function ReplaceFields ($text,$fieldsarray, $bReplaceInsertans=true, $staticReplace=true)
{

    if ($bReplaceInsertans)
    {
        $replacements = array();
        foreach ( $fieldsarray as $key => $value )
        {
            $replacements[substr($key,1,-1)] = $value;
        }
        $text = LimeExpressionManager::ProcessString($text, NULL, $replacements, false, 2, 1, false, false, $staticReplace);
    }
    else
    {
        foreach ( $fieldsarray as $key => $value )
        {
            $text=str_replace($key, $value, $text);
        }
    }
    return $text;
}


/**
* This function mails a text $body to the recipient $to.
* You can use more than one recipient when using a semikolon separated string with recipients.
* If you send several emails at once please supply an email object so that it can be re-used over and over. Especially with SMTP connections this speeds up things by 200%.
* If you supply an email object Do not forget to close the mail connection by calling $mail->SMTPClose();
*
* @param mixed $mail This is an PHPMailer object. If null, one will be created automatically and unset afterwards. If supplied it won't be unset.
* @param string $body Body text of the email in plain text or HTML
* @param mixed $subject Email subject
* @param mixed $to Array with several email addresses or single string with one email address
* @param mixed $from
* @param mixed $sitename
* @param mixed $ishtml
* @param mixed $bouncemail
* @param mixed $attachment
* @return bool If successful returns true
*/
function SendEmailMessage($mail, $body, $subject, $to, $from, $sitename, $ishtml=false, $bouncemail=null, $attachment=null, $customheaders="")
{

    global $emailmethod, $emailsmtphost, $emailsmtpuser, $emailsmtppassword, $defaultlang, $emailsmtpdebug;
    global $rootdir, $maildebug, $maildebugbody, $emailsmtpssl, $clang, $demoModeOnly, $emailcharset;
    if (!is_array($to)){
        $to=array($to);
    }
    if (!is_array($customheaders) && $customheaders == '')
    {
        $customheaders=array();
    }
    if ($demoModeOnly==true)
    {
        $maildebug=$clang->gT('Email was not sent because demo-mode is activated.');
        $maildebugbody='';
        return false;
    }

    if (is_null($bouncemail) )
    {
        $sender=$from;
    }
    else
    {
        $sender=$bouncemail;
    }
    $bUnsetEmail=false;
    if (is_null($mail))
    {
        $bUnsetEmail=true;
        $mail = new PHPMailer;
    }
    else
    {
        $mail->SMTPKeepAlive=true;
    }

    if (!$mail->SetLanguage($defaultlang,$rootdir.'/classes/phpmailer/language/'))
    {
        $mail->SetLanguage('en',$rootdir.'/classes/phpmailer/language/');
    }
    $mail->CharSet = $emailcharset;
    if (isset($emailsmtpssl) && trim($emailsmtpssl)!=='' && $emailsmtpssl!==0) {
        if ($emailsmtpssl===1) {$mail->SMTPSecure = "ssl";}
        else {$mail->SMTPSecure = $emailsmtpssl;}
    }

    $fromname='';
    $fromemail=$from;
    if (strpos($from,'<'))
    {
        $fromemail=substr($from,strpos($from,'<')+1,strpos($from,'>')-1-strpos($from,'<'));
        $fromname=trim(substr($from,0, strpos($from,'<')-1));
    }

    $sendername='';
    $senderemail=$sender;
    if (strpos($sender,'<'))
    {
        $senderemail=substr($sender,strpos($sender,'<')+1,strpos($sender,'>')-1-strpos($sender,'<'));
        $sendername=trim(substr($sender,0, strpos($sender,'<')-1));
    }

    switch ($emailmethod) {
        case "qmail":
            $mail->IsQmail();
            break;
        case "smtp":
            $mail->IsSMTP();
            if ($emailsmtpdebug>0)
            {
                $mail->SMTPDebug = $emailsmtpdebug;
            }
            if (strpos($emailsmtphost,':')>0)
            {
                $mail->Host = substr($emailsmtphost,0,strpos($emailsmtphost,':'));
                $mail->Port = substr($emailsmtphost,strpos($emailsmtphost,':')+1);
            }
            else {
                $mail->Host = $emailsmtphost;
            }
            $mail->Username =$emailsmtpuser;
            $mail->Password =$emailsmtppassword;
            if (trim($emailsmtpuser)!="")
            {
                $mail->SMTPAuth = true;
            }
            break;
        case "sendmail":
            $mail->IsSendmail();
            break;
        default:
            //Set to the default value to rule out incorrect settings.
            $emailmethod="mail";
            $mail->IsMail();
    }

    $mail->SetFrom($fromemail, $fromname);
    $mail->Sender = $senderemail; // Sets Return-Path for error notifications
    foreach ($to as $singletoemail)
    {
        if (strpos($singletoemail, '<') )
        {
            $toemail=substr($singletoemail,strpos($singletoemail,'<')+1,strpos($singletoemail,'>')-1-strpos($singletoemail,'<'));
            $toname=trim(substr($singletoemail,0, strpos($singletoemail,'<')-1));
            $mail->AddAddress($toemail,$toname);
        }
        else
        {
            $mail->AddAddress($singletoemail);
        }
    }
    if (is_array($customheaders))
    {
        foreach ($customheaders as $key=>$val) {
            $mail->AddCustomHeader($val);
        }
    }
    $mail->AddCustomHeader("X-Surveymailer: $sitename Emailer (LimeSurvey.sourceforge.net)");
    if (get_magic_quotes_gpc() != "0")	{$body = stripcslashes($body);}
    if ($ishtml) {
        $mail->IsHTML(true);
        $mail->Body = $body;
        $mail->AltBody = trim(strip_tags(html_entity_decode($body,ENT_QUOTES,'UTF-8')));
    } else
    {
        $mail->IsHTML(false);
        $mail->Body = $body;
    }

    // add the attachment if there is one
    if(!is_null($attachment))
        $mail->AddAttachment($attachment);

    if (trim($subject)!='') {$mail->Subject = "=?$emailcharset?B?" . base64_encode($subject) . "?=";}
    if ($emailsmtpdebug>0) {
        ob_start();
    }
    $sent=$mail->Send();
    $maildebug=$mail->ErrorInfo;
    if ($emailsmtpdebug>0) {
        $maildebug .= '<li>'.$clang->gT('SMTP debug output:').'</li><pre>'.strip_tags(ob_get_contents()).'</pre>';
        ob_end_clean();
    }
    $maildebugbody=$mail->Body;
    $mail->ClearAddresses();
    $mail->ClearCustomHeaders();
    if ($bUnsetEmail)
    {
        unset($mail);
    }
    return $sent;
}



/**
*  This functions removes all HTML tags, Javascript, CRs, linefeeds and other strange chars from a given text
*
* @param string $sTextToFlatten  Text you want to clean
* @param boolan $bDecodeHTMLEntities If set to true then all HTML entities will be decoded to the specified charset. Default: false
* @param string $sCharset Charset to decode to if $decodeHTMLEntities is set to true
*
* @return string  Cleaned text
*/
function FlattenText($sTextToFlatten, $bDecodeHTMLEntities=false, $sCharset='UTF-8', $bStripNewLines=true, $keepSpan=false)
{
    $sNicetext = strip_javascript($sTextToFlatten);
    // When stripping tags, add a space before closing tags so that strings with embedded HTML tables don't get concatenated
    $sNicetext = str_replace('</td',' </td', $sNicetext);
    if ($keepSpan) {
        // Keep <span> so can show EM syntax-highlighting; add space before tags so that word-wrapping not destroyed when remove tags.
        $sNicetext = strip_tags($sNicetext,'<span><table><tr><td><th>');
    }
    else {
        $sNicetext = strip_tags($sNicetext);
    }
    if ($bStripNewLines ){  // strip new lines
        $sNicetext = preg_replace(array('~\Ru~'),array(' '), $sNicetext);
    }
    else // unify newlines to \r\n
    {
        $sNicetext = preg_replace(array('~\Ru~'), array("\r\n"), $sNicetext);
    }
    if ($bDecodeHTMLEntities==true)
    {
        $sNicetext = str_replace('&nbsp;',' ', $sNicetext); // html_entity_decode does not convert &nbsp; to spaces
        $sNicetext = html_entity_decode($sNicetext, ENT_QUOTES, $sCharset);
    }
    $sNicetext = trim($sNicetext);
    return  $sNicetext;
}

/**
* getGroupsByQuestion($surveyid)
* @global string $surveyid
* @return returns a keyed array of groups to questions ie: array([1]=>[2]) question qid 1, is in group gid 2.
*/
function getGroupsByQuestion($surveyid) {
    global $surveyid, $dbprefix;

    $output=array();

    $surveyid=sanitize_int($surveyid);
    $query="SELECT qid, gid FROM ".db_table_name('questions')." WHERE sid='$surveyid'";
    $result = db_execute_assoc($query);
    while ($val = $result->FetchRow())
    {
        $output[$val['qid']]=$val['gid'];
    }
    return $output;
}

/**
* Run an arbitrary sequence of semicolon-delimited SQL commands
*
* Assumes that the input text (file or string) consists of
* a number of SQL statements ENDING WITH SEMICOLONS.  The
* semicolons MUST be the last character in a line.
* Lines that are blank or that start with "#" or "--" (postgres) are ignored.
* Only tested with mysql dump files (mysqldump -p -d limesurvey)
* Function kindly borrowed by Moodle
* @uses $dbprefix
* @param string $sqlfile The path where a file with sql commands can be found on the server.
* @param string $sqlstring If no path is supplied then a string with semicolon delimited sql
* commands can be supplied in this argument.
* @return bool Returns true if database was modified successfully.
*/
function modify_database($sqlfile='', $sqlstring='')
{
    global $dbprefix;
    global $defaultuser;
    global $defaultpass;
    global $siteadminemail;
    global $siteadminname;
    global $defaultlang;
    global $codeString;
    global $rootdir, $homedir;
    global $connect;
    global $clang;
    global $modifyoutput;
    global $databasetabletype;

    require_once($homedir."/classes/core/sha256.php");

    $success = true;  // Let's be optimistic
    $modifyoutput='';

    if (!empty($sqlfile)) {
        if (!is_readable($sqlfile)) {
            $success = false;
            echo '<p>Tried to modify database, but "'. $sqlfile .'" doesn\'t exist!</p>';
            return $success;
        } else {
            $lines = file($sqlfile);
        }
    } else {
        $sqlstring = trim($sqlstring);
        if ($sqlstring{strlen($sqlstring)-1} != ";") {
            $sqlstring .= ";"; // add it in if it's not there.
        }
        $lines[] = $sqlstring;
    }

    $command = '';

    foreach ($lines as $line) {
        $line = rtrim($line);
        $length = strlen($line);

        if ($length and $line[0] <> '#' and substr($line,0,2) <> '--') {
            if (substr($line, $length-1, 1) == ';') {
                $line = substr($line, 0, $length-1);   // strip ;
                $command .= $line;
                $command = str_replace('prefix_', $dbprefix, $command); // Table prefixes
                $command = str_replace('$defaultuser', $defaultuser, $command);
                $command = str_replace('$defaultpass', SHA256::hashing($defaultpass), $command);
                $command = str_replace('$siteadminname', $siteadminname, $command);
                $command = str_replace('$siteadminemail', $siteadminemail, $command);
                $command = str_replace('$defaultlang', $defaultlang, $command);
                $command = str_replace('$sessionname', 'ls'.sRandomChars(20,'123456789'), $command);
                $command = str_replace('$databasetabletype', $databasetabletype, $command);

                if (! db_execute_num($command)) {  //Checked
                    $command=htmlspecialchars($command);
                    $modifyoutput .="<br />".sprintf($clang->gT("SQL command failed: %s Reason: %s"),"<span style='font-size:10px;'>".$command."</span>","<span style='color:#ee0000;font-size:10px;'>".$connect->ErrorMsg()."</span><br/>");
                    $success = false;
                }
                else
                {
                    $command=htmlspecialchars($command);
                    $modifyoutput .=". ";
                }

                $command = '';
            } else {
                $command .= $line;
            }
        }
    }

    return $success;

}



// unsets all Session variables to kill session
function killSession()  //added by Dennis
{
    // Delete the Session Cookie
    $CookieInfo = session_get_cookie_params();
    if ( (empty($CookieInfo['domain'])) && (empty($CookieInfo['secure'])) ) {
        setcookie(session_name(), '', time()-3600, $CookieInfo['path']);
    } elseif (empty($CookieInfo['secure'])) {
        setcookie(session_name(), '', time()-3600, $CookieInfo['path'], $CookieInfo['domain']);
    } else {
        setcookie(session_name(), '', time()-3600, $CookieInfo['path'], $CookieInfo['domain'], $CookieInfo['secure']);
    }
    unset($_COOKIE[session_name()]);
    foreach ($_SESSION as $key =>$value)
    {
        //echo $key." = ".$value."<br />";
        unset($_SESSION[$key]);
    }
    $_SESSION = array(); // redundant with previous lines
    session_unset();
    @session_destroy();
}







// set the rights of a user and his children
function setuserrights($uid, $rights)
{
    global $connect;
    $uid=sanitize_int($uid);
    $updates = "create_survey=".$rights['create_survey']
    . ", create_user=".$rights['create_user']
    . ", delete_user=".$rights['delete_user']
    . ", superadmin=".$rights['superadmin']
    . ", configurator=".$rights['configurator']
    . ", manage_template=".$rights['manage_template']
    . ", manage_label=".$rights['manage_label'];
    $uquery = "UPDATE ".db_table_name('users')." SET ".$updates." WHERE uid = ".$uid;
    return $connect->Execute($uquery);     //Checked
}


function createPassword()
{
    $pwchars = "abcdefhjmnpqrstuvwxyz23456789";
    $password_length = 8;
    $passwd = '';

    for ($i=0; $i<$password_length; $i++)
    {
        $passwd .= $pwchars[(int)floor(rand(0,strlen($pwchars)-1))];
    }
    return $passwd;
}

function getgroupuserlist()
{
    global $ugid, $dbprefix, $scriptname, $connect, $clang;

    $ugid=sanitize_int($ugid);
    $surveyidquery = "SELECT a.uid, a.users_name FROM ".db_table_name('users')." AS a LEFT JOIN (SELECT uid AS id FROM ".db_table_name('user_in_groups')." WHERE ugid = {$ugid}) AS b ON a.uid = b.id WHERE id IS NULL ORDER BY a.users_name";

    $surveyidresult = db_execute_assoc($surveyidquery);  //Checked
    if (!$surveyidresult) {return "Database Error";}
    $surveyselecter = "";
    $surveynames = $surveyidresult->GetRows();
    if ($surveynames)
    {
        foreach($surveynames as $sv)
        {
            $surveyselecter .= "<option";
            $surveyselecter .=" value='{$sv['uid']}'>{$sv['users_name']}</option>\n";
        }
    }
    $surveyselecter = "<option value='-1' selected='selected'>".$clang->gT("Please choose...")."</option>\n".$surveyselecter;
    return $surveyselecter;
}

/**
* Retrieve a HTML <OPTION> list of survey admin users
*
* @param mixed $bIncludeOwner If the survey owner should be included
* @param mixed $bIncludeSuperAdmins If Super admins should be included
* @return string
*/
function sGetSurveyUserlist($bIncludeOwner=true, $bIncludeSuperAdmins=true)
{
    global $surveyid, $dbprefix, $scriptname, $connect, $clang, $usercontrolSameGroupPolicy;
    $surveyid=sanitize_int($surveyid);

    $sSurveyIDQuery = "SELECT a.uid, a.users_name, a.full_name FROM ".db_table_name('users')." AS a
    LEFT OUTER JOIN (SELECT uid AS id FROM ".db_table_name('survey_permissions')." WHERE sid = {$surveyid}) AS b ON a.uid = b.id
    WHERE id IS NULL ";
    if (!$bIncludeSuperAdmins)
    {
        $sSurveyIDQuery.='and superadmin=0 ';
    }
    $sSurveyIDQuery.= 'ORDER BY a.users_name';
    $surveyidresult = db_execute_assoc($sSurveyIDQuery);  //Checked

    if (!$surveyidresult) {return "Database Error";}
    $surveyselecter = "";
    $surveynames = $surveyidresult->GetRows();

    if (isset($usercontrolSameGroupPolicy) &&
    $usercontrolSameGroupPolicy == true)
    {
        $authorizedUsersList = getuserlist('onlyuidarray');
    }

    if ($surveynames)
    {
        foreach($surveynames as $sv)
        {
            if (!isset($usercontrolSameGroupPolicy) ||
            $usercontrolSameGroupPolicy == false ||
            in_array($sv['uid'],$authorizedUsersList))
            {
                $surveyselecter .= "<option";
                $surveyselecter .=" value='{$sv['uid']}'>{$sv['users_name']} {$sv['full_name']}</option>\n";
            }
        }
    }
    if (!isset($svexist)) {$surveyselecter = "<option value='-1' selected='selected'>".$clang->gT("Please choose...")."</option>\n".$surveyselecter;}
    else {$surveyselecter = "<option value='-1'>".$clang->gT("None")."</option>\n".$surveyselecter;}
    return $surveyselecter;
}

function getsurveyusergrouplist($outputformat='htmloptions')
{
    global $surveyid, $dbprefix, $scriptname, $connect, $clang, $usercontrolSameGroupPolicy;
    $surveyid=sanitize_int($surveyid);

    $surveyidquery = "SELECT a.ugid, a.name, MAX(d.ugid) AS da FROM ".db_table_name('user_groups')." AS a LEFT JOIN (SELECT b.ugid FROM ".db_table_name('user_in_groups')." AS b LEFT JOIN (SELECT * FROM ".db_table_name('survey_permissions')." WHERE sid = {$surveyid}) AS c ON b.uid = c.uid WHERE c.uid IS NULL) AS d ON a.ugid = d.ugid GROUP BY a.ugid, a.name HAVING MAX(d.ugid) IS NOT NULL";
    $surveyidresult = db_execute_assoc($surveyidquery);  //Checked
    if (!$surveyidresult) {return "Database Error";}
    $surveyselecter = "";
    $surveynames = $surveyidresult->GetRows();

    if (isset($usercontrolSameGroupPolicy) &&
    $usercontrolSameGroupPolicy == true)
    {
        $authorizedGroupsList=getusergrouplist('simplegidarray');
    }

    if ($surveynames)
    {
        foreach($surveynames as $sv)
        {
            if (!isset($usercontrolSameGroupPolicy) ||
            $usercontrolSameGroupPolicy == false ||
            in_array($sv['ugid'],$authorizedGroupsList))
            {
                $surveyselecter .= "<option";
                $surveyselecter .=" value='{$sv['ugid']}'>{$sv['name']}</option>\n";
                $simpleugidarray[] = $sv['ugid'];
            }
        }
    }
    if (!isset($svexist)) {$surveyselecter = "<option value='-1' selected='selected'>".$clang->gT("Please choose...")."</option>\n".$surveyselecter;}
    else {$surveyselecter = "<option value='-1'>".$clang->gT("None")."</option>\n".$surveyselecter;}

    if ($outputformat == 'simpleugidarray')
    {
        return $simpleugidarray;
    }
    else
    {
        return $surveyselecter;
    }
}

function getusergrouplist($outputformat='optionlist')
{
    global $dbprefix, $scriptname, $connect, $clang;

    //$squery = "SELECT ugid, name FROM ".db_table_name('user_groups') ." WHERE owner_id = {$_SESSION['loginID']} ORDER BY name";
    $squery = "SELECT a.ugid, a.name, a.owner_id, b.uid FROM ".db_table_name('user_groups') ." AS a LEFT JOIN ".db_table_name('user_in_groups') ." AS b ON a.ugid = b.ugid WHERE uid = {$_SESSION['loginID']} ORDER BY name";

    $sresult = db_execute_assoc($squery); //Checked
    if (!$sresult) {return "Database Error";}
    $selecter = "";
    $groupnames = $sresult->GetRows();
    $simplegidarray=array();
    if ($groupnames)
    {
        foreach($groupnames as $gn)
        {
            $selecter .= "<option ";
            if($_SESSION['loginID'] == $gn['owner_id']) {$selecter .= " style=\"font-weight: bold;\"";}
            if (isset($_GET['ugid']) && $gn['ugid'] == $_GET['ugid']) {$selecter .= " selected='selected'"; $svexist = 1;}
            $selecter .=" value='$scriptname?action=editusergroups&amp;ugid={$gn['ugid']}'>{$gn['name']}</option>\n";
            $simplegidarray[] = $gn['ugid'];
        }
    }
    if (!isset($svexist)) {$selecter = "<option value='-1' selected='selected'>".$clang->gT("Please choose...")."</option>\n".$selecter;}
    //else {$selecter = "<option value='-1'>".$clang->gT("None")."</option>\n".$selecter;}

    if ($outputformat == 'simplegidarray')
    {
        return $simplegidarray;
    }
    else
    {
        return $selecter;
    }
}


function languageDropdown($surveyid,$selected)
{
    global $homeurl;
    $slangs = GetAdditionalLanguagesFromSurveyID($surveyid);
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    array_unshift($slangs,$baselang);
    $html = "<select class='listboxquestions' name='langselect' onchange=\"window.open(this.options[this.selectedIndex].value, '_self')\">\n";
    foreach ($slangs as $lang)
    {
        if ($lang == $selected) $html .= "\t<option value='{$homeurl}/admin.php?action=dataentry&sid={$surveyid}&language={$lang}' selected='selected'>".getLanguageNameFromCode($lang,false)."</option>\n";
        if ($lang != $selected) $html .= "\t<option value='{$homeurl}/admin.php?action=dataentry&sid={$surveyid}&language={$lang}'>".getLanguageNameFromCode($lang,false)."</option>\n";
    }
    $html .= "</select>";
    return $html;
}

function languageDropdownClean($surveyid,$selected)
{
    $slangs = GetAdditionalLanguagesFromSurveyID($surveyid);
    $baselang = GetBaseLanguageFromSurveyID($surveyid);
    array_unshift($slangs,$baselang);
    $html = "<select class='listboxquestions' id='language' name='language'>\n";
    foreach ($slangs as $lang)
    {
        if ($lang == $selected) $html .= "\t<option value='$lang' selected='selected'>".getLanguageNameFromCode($lang,false)."</option>\n";
        if ($lang != $selected) $html .= "\t<option value='$lang'>".getLanguageNameFromCode($lang,false)."</option>\n";
    }
    $html .= "</select>";
    return $html;
}

function BuildCSVFromQuery($Query)
{
    global $dbprefix, $connect;
    $QueryResult = db_execute_assoc($Query) or safe_die ("ERROR: $QueryResult<br />".$connect->ErrorMsg()); //safe
    preg_match('/FROM (\w+)( |,)/i', $Query, $MatchResults);
    $TableName = $MatchResults[1];;
    if ($dbprefix)
    {
        $TableName = substr($TableName, strlen($dbprefix), strlen($TableName));
    }
    $Output = "\n#\n# " . strtoupper($TableName) . " TABLE\n#\n";
    $HeaderDone = false;    $ColumnNames = "";
    while ($Row = $QueryResult->FetchRow())
    {

        if (!$HeaderDone)
        {
            foreach ($Row as $Key=>$Value)
            {
                $ColumnNames .= CSVEscape($Key).","; //Add all the column names together
            }
            $ColumnNames = substr($ColumnNames, 0, -1); //strip off last comma space
            $Output .= "$ColumnNames\n";
            $HeaderDone=true;
        }
        $ColumnValues = "";
        foreach ($Row as $Key=>$Value)
        {
            $Value=str_replace("\r\n", "\n", $Value);
            $Value=str_replace("\r", "\n", $Value);
            $ColumnValues .= CSVEscape($Value) . ",";
        }
        $ColumnValues = substr($ColumnValues, 0, -1); //strip off last comma space
        $Output .= str_replace("\n","\\n","$ColumnValues")."\n";
    }
    return $Output;
}

function CSVEscape($str)
{
    $str= str_replace('\n','\%n',$str);
    return '"' . str_replace('"','""', $str) . '"';
}

function convertCSVRowToArray($string, $seperator, $quotechar)
{
    $fields=preg_split('/' . $seperator . '(?=([^"]*"[^"]*")*(?![^"]*"))/',trim($string));
    $fields=array_map('CSVUnquote',$fields);
    return $fields;
}


/**
* This function removes surrounding and masking quotes from the CSV field
*
* @param mixed $field
* @return mixed
*/
function CSVUnquote($field)
{
    //print $field.":";
    $field = preg_replace ("/^\040*\"/", "", $field);
    $field = preg_replace ("/\"\040*$/", "", $field);
    $field= str_replace('""','"',$field);
    //print $field."\n";
    return $field;
}

/**
* CleanLanguagesFromSurvey() removes any languages from survey tables that are not in the passed list
* @param string $sid - the currently selected survey
* @param string $availlangs - space seperated list of additional languages in survey
* @return bool - always returns true
*/
function CleanLanguagesFromSurvey($sid, $availlangs)
{
    global $connect;
    $sid=sanitize_int($sid);
    $baselang = GetBaseLanguageFromSurveyID($sid);

    if (!empty($availlangs) && $availlangs != " ")
    {
        $availlangs=sanitize_languagecodeS($availlangs);
        $langs = explode(" ",$availlangs);
        if($langs[count($langs)-1] == "") array_pop($langs);
    }

    $sqllang = "language <> '".$baselang."' ";;

    if (!empty($availlangs) && $availlangs != " ")
    {
        foreach ($langs as $lang)
        {
            $sqllang .= "and language <> '".$lang."' ";
        }
    }

    // Remove From Answers Table
    $query = "SELECT qid FROM ".db_table_name('questions')." WHERE sid='{$sid}' and ($sqllang)";
    $qidresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());    //Checked
    while ($qrow =  $qidresult->FetchRow())
    {
        $myqid = $qrow['qid'];
        $query = "DELETE FROM ".db_table_name('answers')." WHERE qid='$myqid' and ($sqllang)";
        $connect->Execute($query) or safe_die($connect->ErrorMsg());    //Checked
    }

    // Remove From Questions Table
    $query = "DELETE FROM ".db_table_name('questions')." WHERE sid='{$sid}' and ($sqllang)";
    $connect->Execute($query) or safe_die($connect->ErrorMsg());   //Checked

    // Remove From Groups Table
    $query = "DELETE FROM ".db_table_name('groups')." WHERE sid='{$sid}' and ($sqllang)";
    $connect->Execute($query) or safe_die($connect->ErrorMsg());   //Checked

    return true;
}

/**
* FixLanguageConsistency() fixes missing groups,questions,answers & assessments for languages on a survey
* @param string $sid - the currently selected survey
* @param string $availlangs - space seperated list of additional languages in survey - if empty all additional languages of a survey are checked against the base language
* @return bool - always returns true
*/
function FixLanguageConsistency($sid, $availlangs='')
{
    global $connect, $databasetype;

    if (trim($availlangs)!='')
    {
        $availlangs=sanitize_languagecodeS($availlangs);
        $langs = explode(" ",$availlangs);
        if($langs[count($langs)-1] == "") array_pop($langs);
    } else {
        $langs=GetAdditionalLanguagesFromSurveyID($sid);
    }

    $baselang = GetBaseLanguageFromSurveyID($sid);
    $sid=sanitize_int($sid);

    $query = "SELECT * FROM ".db_table_name('quota_languagesettings')." join ".db_table_name('quota')." q on quotals_quota_id=q.id WHERE q.sid='{$sid}' AND quotals_language='{$baselang}'";
    $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
    if ($result->RecordCount() > 0)
    {
        while ($qls = $result->FetchRow())
        {
            foreach ($langs as $lang)
            {
                $query = "SELECT quotals_id FROM ".db_table_name('quota_languagesettings')." WHERE quotals_quota_id='{$qls['quotals_quota_id']}' AND quotals_language='{$lang}'";
                $gresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg()); //Checked
                if ($gresult->RecordCount() < 1)
                {
                    $query = "INSERT INTO ".db_table_name('quota_languagesettings')
                    ." (quotals_quota_id,quotals_language,quotals_name,quotals_message,quotals_url,quotals_urldescrip) VALUES ("
                    . "'".$qls['quotals_quota_id']."',"
                    . "'".$lang."',"
                    . db_quoteall($qls['quotals_name']).","
                    . db_quoteall($qls['quotals_message']).","
                    . "'".$qls['quotals_url']."',"
                    . db_quoteall($qls['quotals_urldescrip']).""
                    . ")";
                    $connect->Execute($query) or safe_die($connect->ErrorMsg());  //Checked
                    db_switchIDInsert('quota_languagesettings',false);
                }
            }
            reset($langs);
        }
    }

    $query = "SELECT * FROM ".db_table_name('groups')." WHERE sid='{$sid}' AND language='{$baselang}'  ORDER BY group_order";
    $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());  //Checked
    if ($result->RecordCount() > 0)
    {
        while($group = $result->FetchRow())
        {
            foreach ($langs as $lang)
            {
                $query = "SELECT gid FROM ".db_table_name('groups')." WHERE sid='{$sid}' AND gid='{$group['gid']}' AND language='{$lang}'";
                $gresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg()); //Checked
                if ($gresult->RecordCount() < 1)
                {
                    db_switchIDInsert('groups',true);
                    $query = "INSERT INTO ".db_table_name('groups')." (gid,sid,group_name,group_order,description,grelevance,language) VALUES('{$group['gid']}','{$group['sid']}',".db_quoteall($group['group_name']).",'{$group['group_order']}',".db_quoteall($group['description']).",'".db_quote($group['grelevance'])."','{$lang}')";
                    $connect->Execute($query) or safe_die($connect->ErrorMsg());  //Checked
                    db_switchIDInsert('groups',false);
                }
            }
            reset($langs);
        }
    }

    $quests = array();
    $query = "SELECT * FROM ".db_table_name('questions')." WHERE sid='{$sid}' AND language='{$baselang}' ORDER BY question_order";
    $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());  //Checked
    if ($result->RecordCount() > 0)
    {
        while($question = $result->FetchRow())
        {
            array_push($quests,$question['qid']);
            foreach ($langs as $lang)
            {
                $query = "SELECT qid FROM ".db_table_name('questions')." WHERE sid='{$sid}' AND qid='{$question['qid']}' AND language='{$lang}' AND scale_id={$question['scale_id']}";
                $gresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());   //Checked
                if ($gresult->RecordCount() < 1)
                {
                    db_switchIDInsert('questions',true);
                    $query = "INSERT INTO ".db_table_name('questions')." (qid,sid,gid,type,title,question,preg,help,other,mandatory,question_order,language, scale_id,parent_qid, relevance) VALUES('{$question['qid']}','{$question['sid']}','{$question['gid']}','{$question['type']}',".db_quoteall($question['title']).",".db_quoteall($question['question']).",".db_quoteall($question['preg']).",".db_quoteall($question['help']).",'{$question['other']}','{$question['mandatory']}','{$question['question_order']}','{$lang}',{$question['scale_id']},{$question['parent_qid']}, '{$question['relevance']}')";
                    $connect->Execute($query) or safe_die($query."<br />".$connect->ErrorMsg());   //Checked
                    db_switchIDInsert('questions',false);
                }
            }
            reset($langs);
        }

        $sqlans = "";
        foreach ($quests as $quest)
        {
            $sqlans .= " OR qid = '".$quest."' ";
        }

        $query = "SELECT * FROM ".db_table_name('answers')." WHERE language='{$baselang}' and (".trim($sqlans,' OR').") ORDER BY qid, code";
        $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg()); //Checked
        if ($result->RecordCount() > 0)
        {
            while($answer = $result->FetchRow())
            {
                foreach ($langs as $lang)
                {
                    $query = "SELECT qid FROM ".db_table_name('answers')." WHERE code='{$answer['code']}' AND qid='{$answer['qid']}' AND language='{$lang}' AND scale_id={$answer['scale_id']}";
                    $gresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());  //Checked
                    if ($gresult->RecordCount() < 1)
                    {
                        db_switchIDInsert('answers',true);
                        $query = "INSERT INTO ".db_table_name('answers')." (qid,code,answer,scale_id,sortorder,language,assessment_value) VALUES('{$answer['qid']}',".db_quoteall($answer['code']).",".db_quoteall($answer['answer']).",{$answer['scale_id']},'{$answer['sortorder']}','{$lang}',{$answer['assessment_value']})";
                        $connect->Execute($query) or safe_die($connect->ErrorMsg()); //Checked
                        db_switchIDInsert('answers',false);
                    }
                }
                reset($langs);
            }
        }
    }


    $query = "SELECT * FROM ".db_table_name('assessments')." WHERE sid='{$sid}' AND language='{$baselang}'";
    $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());  //Checked
    if ($result->RecordCount() > 0)
    {
        while($assessment = $result->FetchRow())
        {
            foreach ($langs as $lang)
            {
                $query = "SELECT id FROM ".db_table_name('assessments')." WHERE sid='{$sid}' AND id='{$assessment['id']}' AND language='{$lang}'";
                $gresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg()); //Checked
                if ($gresult->RecordCount() < 1)
                {
                    db_switchIDInsert('assessments',true);
                    $query = "INSERT INTO ".db_table_name('assessments')." (id,sid,scope,gid,name,minimum,maximum,message,language) "
                    ."VALUES('{$assessment['id']}','{$assessment['sid']}',".db_quoteall($assessment['scope']).",".db_quoteall($assessment['gid']).",".db_quoteall($assessment['name']).",".db_quoteall($assessment['minimum']).",".db_quoteall($assessment['maximum']).",".db_quoteall($assessment['message']).",'{$lang}')";
                    $connect->Execute($query) or safe_die($connect->ErrorMsg());  //Checked
                    db_switchIDInsert('assessments',false);
                }
            }
            reset($langs);
        }
    }



    return true;
}

function incompleteAnsFilterstate()
{
    global $filterout_incomplete_answers;
    $letsfilter='';
    $letsfilter = returnglobal('filterinc'); //read get/post filterinc

    // first let's initialize the incompleteanswers session variable
    if ($letsfilter != '')
    { // use the read value if not empty
        $_SESSION['incompleteanswers']=$letsfilter;
    }
    elseif (!isset($_SESSION['incompleteanswers']))
    { // sets default variable value from config file
        $_SESSION['incompleteanswers'] = $filterout_incomplete_answers;
    }

    if  ($_SESSION['incompleteanswers']=='filter') {
        return "filter"; //COMPLETE ANSWERS ONLY
    }
    elseif ($_SESSION['incompleteanswers']=='show') {
        return false; //ALL ANSWERS
    }
    elseif ($_SESSION['incompleteanswers']=='incomplete') {
        return "inc"; //INCOMPLETE ANSWERS ONLY
    }
    else
    { // last resort is to prevent filtering
        return false;
    }
}

/**
* captcha_enabled($screen, $usecaptchamode)
* @param string $screen - the screen name for which to test captcha activation
*
* @return boolean - returns true if captcha must be enabled
**/
function captcha_enabled($screen, $captchamode='')
{
    switch($screen)
    {
        case 'registrationscreen':
            if ($captchamode == 'A' ||
            $captchamode == 'B' ||
            $captchamode == 'D' ||
            $captchamode == 'R')
            {
                return true;
            }
            else
            {
                return false;
            }
            break;
        case 'surveyaccessscreen':
            if ($captchamode == 'A' ||
            $captchamode == 'B' ||
            $captchamode == 'C' ||
            $captchamode == 'X')
            {
                return true;
            }
            else
            {
                return false;
            }
            break;
        case 'saveandloadscreen':
            if ($captchamode == 'A' ||
            $captchamode == 'C' ||
            $captchamode == 'D' ||
            $captchamode == 'S')
            {
                return true;
            }
            else
            {
                return false;
            }
            return true;
            break;
        default:
            return true;
            break;
    }
}


/**
* used for import[survey|questions|groups]
*
* @param mixed $string
* @return mixed
*/
function convertCsvreturn2return($string)
{
    $string= str_replace('\n', "\n", $string);
    return str_replace('\%n', '\n', $string);
}



/**
*  Checks that each object from an array of CSV data [question-rows,answer-rows,labelsets-row] supports at least a given language
*
* @param mixed $csvarray array with a line of csv data per row
* @param mixed $idkeysarray  array of integers giving the csv-row numbers of the object keys
* @param mixed $langfieldnum  integer giving the csv-row number of the language(s) filed
*        ==> the language field  can be a single language code or a
*            space separated language code list
* @param mixed $langcode  the language code to be tested
* @param mixed $hasheader  if we should strip off the first line (if it contains headers)
*/
function  bDoesImportarraySupportsLanguage($csvarray,$idkeysarray,$langfieldnum,$langcode, $hasheader = false)
{
    // An array with one row per object id and langsupport status as value
    $objlangsupportarray=Array();
    if ($hasheader === true)
    { // stripping first row to skip headers if any
        array_shift($csvarray);
    }

    foreach ($csvarray as $csvrow)
    {
        $rowcontents = convertCSVRowToArray($csvrow,',','"');
        $rowid = "";
        foreach ($idkeysarray as $idfieldnum)
        {
            $rowid .= $rowcontents[$idfieldnum]."-";
        }
        $rowlangarray = explode (" ", $rowcontents[$langfieldnum]);
        if (!isset($objlangsupportarray[$rowid]))
        {
            if (array_search($langcode,$rowlangarray)!== false)
            {
                $objlangsupportarray[$rowid] = "true";
            }
            else
            {
                $objlangsupportarray[$rowid] = "false";
            }
        }
        else
        {
            if ($objlangsupportarray[$rowid] == "false" &&
            array_search($langcode,$rowlangarray) !== false)
            {
                $objlangsupportarray[$rowid] = "true";
            }
        }
    } // end foreach rown

    // If any of the object doesn't support the given language, return false
    if (array_search("false",$objlangsupportarray) === false)
    {
        return true;
    }
    else
    {
        return false;
    }
}

/** This function checks to see if there is an answer saved in the survey session
* data that matches the $code. If it does, it returns that data.
* It is used when building a questions text to allow incorporating the answer
* to an earlier question into the text of a later question.
* IE: Q1: What is your name? [Jason]
*     Q2: Hi [Jason] how are you ?
* This function is called from the retriveAnswers function.
*
* @param mixed $code
* @param mixed $phpdateformat  The date format in which any dates are shown
* @return mixed returns the answerText from session variable corresponding to a question code
*/
function retrieve_Answer($code, $phpdateformat=null)
{
    //This function checks to see if there is an answer saved in the survey session
    //data that matches the $code. If it does, it returns that data.
    //It is used when building a questions text to allow incorporating the answer
    //to an earlier question into the text of a later question.
    //IE: Q1: What is your name? [Jason]
    //    Q2: Hi [Jason] how are you ?
    //This function is called from the retriveAnswers function.
    global $dbprefix, $connect, $clang;
    //Find question details
    if (isset($_SESSION[$code]))
    {
        $questiondetails=getsidgidqidaidtype($code);
        //the getsidgidqidaidtype function is in common.php and returns
        //a SurveyID, GroupID, QuestionID and an Answer code
        //extracted from a "fieldname" - ie: 1X2X3a
        // also returns question type

        if ($questiondetails['type'] == "M" || $questiondetails['type'] == "P")
        {
            if ((strpos($code,'comment')>0 || strpos($code,'other')>0) && isset($_SESSION[$code]))
            {
                return $_SESSION[$code];
            }
            $query="SELECT * FROM {$dbprefix}questions WHERE parent_qid='".$questiondetails['qid']."' AND language='".$_SESSION['s_lang']."'";
            $result=db_execute_assoc($query) or safe_die("Error getting answer<br />$query<br />".$connect->ErrorMsg());  //Checked
            while($row=$result->FetchRow())
            {
                if (isset($_SESSION[$code.$row['title']]) && $_SESSION[$code.$row['title']] == "Y")
                {
                    $returns[] = $row['question'];
                }
                elseif (isset($_SESSION[$code]) && $_SESSION[$code] == "Y" && $questiondetails['aid']==$row['title'])
                {
                    return $row['question'];
                }
            }
            if (isset($_SESSION[$code."other"]) && $_SESSION[$code."other"])
            {
                $returns[]=$_SESSION[$code."other"];
            }
            if (isset($returns))
            {
                $return=implode(", ", $returns);
                if (strpos($return, ","))
                {
                    $return=substr_replace($return, " &", strrpos($return, ","), 1);
                }
            }
            else
            {
                //$return=$clang->gT("No answer");
		$return = ""; //queXS Addition
            }
        }
        elseif (!$_SESSION[$code] && $_SESSION[$code] !=0)
        {
            //$return=$clang->gT("No answer");
		$return = ""; //queXS Addition
        }
        else
        {
            $return=getextendedanswer($code, $_SESSION[$code], 'INSERTANS',$phpdateformat);
        }
    }
    else
    {
        $return=$clang->gT("Error") . "($code)";
    }
    return html_escape($return);
}

/**
* Check if a table does exist in the database
*
* @param mixed $sid  Table name to check for (without dbprefix!))
* @return boolean True or false if table exists or not
*/
function tableExists($tablename)
{
    global $connect;
    static $tablelist;

    if (!isset($tablelist)) $tablelist = $connect->MetaTables();
    if ($tablelist==false)
    {
        return false;
    }
    foreach ($tablelist as $tbl)
    {
        if (db_quote_id($tbl) == db_table_name($tablename))
        {
            return true;
        }
    }
    return false;
}

// Returns false if the survey is anonymous,
// and a token table exists: in this case the completed field of a token
// will contain 'Y' instead of the submitted date to ensure privacy
// Returns true otherwise
function bIsTokenCompletedDatestamped($thesurvey)
{
    if ($thesurvey['anonymized'] == 'Y' &&  tableExists('tokens_'.$thesurvey['sid']))
    {
        return false;
    }
    else
    {
        return true;
    }
}

/**
* example usage
* $date = "2006-12-31 21:00";
* $shift "+6 hours"; // could be days, weeks... see function strtotime() for usage
*
* echo sql_date_shift($date, "Y-m-d H:i:s", $shift);
*
* will output: 2007-01-01 03:00:00
*
* @param mixed $date
* @param mixed $dformat
* @param mixed $shift
* @return string
*/
function date_shift($date, $dformat, $shift)
{
    return date($dformat, strtotime($shift, strtotime($date)));
}


// getBounceEmail: returns email used to receive error notifications
function getBounceEmail($surveyid)
{
    $surveyInfo=getSurveyInfo($surveyid);

    if ($surveyInfo['bounceprocessing'] == 'G')
    {
        return getGlobalSetting('siteadminbounce');
    }
    else if ($surveyInfo['bounce_email'] == '')
        {
            return null; // will be converted to from in MailText
        }
        else
        {
            return $surveyInfo['bounce_email'];
    }
}

// getEmailFormat: returns email format for the survey
// returns 'text' or 'html'
function getEmailFormat($surveyid)
{

    $surveyInfo=getSurveyInfo($surveyid);
    if ($surveyInfo['htmlemail'] == 'Y')
    {
        return 'html';
    }
    else
    {
        return 'text';
    }

}

// Check if user has manage rights for a template
function hasTemplateManageRights($userid, $templatefolder) {
    global $connect;
    global $dbprefix;
    $userid=sanitize_int($userid);
    $templatefolder=sanitize_paranoid_string($templatefolder);
    $query = "SELECT ".db_quote_id('use')." FROM {$dbprefix}templates_rights WHERE uid=".$userid." AND folder LIKE '".$templatefolder."'";

    $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());  //Safe

    if ($result->RecordCount() == 0)  return false;

    $row = $result->FetchRow();

    return $row["use"];
}

/**
* This function creates an incrementing answer code based on the previous source-code
*
* @param mixed $sourcecode The previous answer code
*/
function getNextCode($sourcecode)
{
    $i=1;
    $found=true;
    $foundnumber=-1;
    while ($i<=strlen($sourcecode) && $found)
    {
        $found=is_numeric(substr($sourcecode,-$i));
        if ($found)
        {
            $foundnumber=substr($sourcecode,-$i);
            $i++;
        }
    }
    if ($foundnumber==-1)
    {
        return($sourcecode);
    }
    else
    {
        $foundnumber++;
        $result=substr($sourcecode,0,strlen($sourcecode)-strlen($foundnumber)).$foundnumber;
        return($result);
    }

}

/**
* Translink
*
* @param mixed $type
* @param mixed $oldid
* @param mixed $newid
* @param mixed $text
* @return mixed
*/
function translink($type, $oldid, $newid, $text)
{
    global $relativeurl;
    if (!isset($_POST['translinksfields']))
    {
        return $text;
    }

    if ($type == 'survey')
    {
        $pattern = "([^'\"]*)/upload/surveys/$oldid/";
        $replace = "$relativeurl/upload/surveys/$newid/";
        return preg_replace('#'.$pattern.'#', $replace, $text);
    }
    elseif ($type == 'label')
    {
        $pattern = "([^'\"]*)/upload/labels/$oldid/";
        $replace = "$relativeurl/upload/labels/$newid/";
        return preg_replace('#'.$pattern.'#', $replace, $text);
    }
    else
    {
        return $text;
    }
}

/**
* This function creates the old fieldnames for survey import
*
* @param mixed $iOldSID  The old survey id
* @param mixed $iNewSID  The new survey id
* @param array $aGIDReplacements An array with group ids (oldgid=>newgid)
* @param array $aQIDReplacements An array with question ids (oldqid=>newqid)
*/
function aReverseTranslateFieldnames($iOldSID,$iNewSID,$aGIDReplacements,$aQIDReplacements)
{
    $aGIDReplacements=array_flip($aGIDReplacements);
    $aQIDReplacements=array_flip($aQIDReplacements);
    if ($iOldSID==$iNewSID) {
        $forceRefresh=true; // otherwise grabs the cached copy and throws undefined index exceptions
    }
    else {
        $forceRefresh=false;
    }
    $aFieldMap=createFieldMap($iNewSID,'full',$forceRefresh);
    $aFieldMappings=array();
    foreach ($aFieldMap as $sFieldname=>$aFieldinfo)
    {
        if ($aFieldinfo['qid']!=null)
        {
            $aFieldMappings[$sFieldname]=$iOldSID.'X'.$aGIDReplacements[$aFieldinfo['gid']].'X'.$aQIDReplacements[$aFieldinfo['qid']].$aFieldinfo['aid'];
            // now also add a shortened field mapping which is needed for certain kind of condition mappings
            $aFieldMappings[$iNewSID.'X'.$aFieldinfo['gid'].'X'.$aFieldinfo['qid']]=$iOldSID.'X'.$aGIDReplacements[$aFieldinfo['gid']].'X'.$aQIDReplacements[$aFieldinfo['qid']];
        }
    }
    return array_flip($aFieldMappings);
}


/**
 * Return an array describing what queXS questionnaireId and sampleId to filter results on
 * 
 * @return array The questionnaire Id and sample Id to filter to (sample id is null for entire questionnaire)
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2011-09-07
 */
function questionnaireSampleFilterstate()
{
	$letsfilter = returnglobal('quexsfilterinc');

	$_SESSION['quexsanswers']=$letsfilter;

	$qs = explode(":",$_SESSION['quexsanswers']);

	if (count($qs) == 2)
	{
		return $qs;
	}
	return false;
}

/**
* This function replaces the old insertans tags with new ones across a survey
*
* @param string $newsid  Old SID
* @param string $oldsid  New SID
* @param mixed $fieldnames Array  array('oldfieldname'=>'newfieldname')
*/
function TranslateInsertansTags($newsid,$oldsid,$fieldnames)
{
    global $connect, $dbprefix;

    $newsid=sanitize_int($newsid);
    $oldsid=sanitize_int($oldsid);


    # translate 'surveyls_urldescription' and 'surveyls_url' INSERTANS tags in surveyls
    $sql = "SELECT surveyls_survey_id, surveyls_language, surveyls_urldescription, surveyls_url from {$dbprefix}surveys_languagesettings WHERE surveyls_survey_id=".$newsid." AND (surveyls_urldescription LIKE '%{INSERTANS:".$oldsid."X%' OR surveyls_url LIKE '%{INSERTANS:".$oldsid."X%')";
    $res = db_execute_assoc($sql) or safe_die("Can't read groups table in transInsertAns ".$connect->ErrorMsg());     // Checked

    while ($qentry = $res->FetchRow())
    {
        $urldescription = $qentry['surveyls_urldescription'];
        $endurl  = $qentry['surveyls_url'];
        $language = $qentry['surveyls_language'];

        foreach ($fieldnames as $sOldFieldname=>$sNewFieldname)
        {
            $pattern = $sOldFieldname;
            $replacement = $sNewFieldname;
            $urldescription=preg_replace('/'.$pattern.'/', $replacement, $urldescription);
            $endurl=preg_replace('/'.$pattern.'/', $replacement, $endurl);
        }

        if (strcmp($urldescription,$qentry['surveyls_urldescription']) !=0  ||
        (strcmp($endurl,$qentry['surveyls_url']) !=0))
        {
            // Update Field
            $sqlupdate = "UPDATE {$dbprefix}surveys_languagesettings SET surveyls_urldescription='".db_quote($urldescription)."', surveyls_url='".db_quote($endurl)."' WHERE surveyls_survey_id=$newsid AND surveyls_language='$language'";
            $updateres=$connect->Execute($sqlupdate) or safe_die ("Couldn't update INSERTANS in surveys_languagesettings<br />$sqlupdate<br />".$connect->ErrorMsg());    //Checked
        } // Enf if modified
    } // end while qentry

    # translate 'quotals_urldescrip' and 'quotals_url' INSERTANS tags in quota_languagesettings
    $sql = "SELECT quotals_id, quotals_urldescrip, quotals_url from {$dbprefix}quota_languagesettings qls,{$dbprefix}quota q WHERE sid=".$newsid." AND q.id=qls.quotals_quota_id AND (quotals_urldescrip LIKE '%{INSERTANS:".$oldsid."X%' OR quotals_url LIKE '%{INSERTANS:".$oldsid."X%')";
    $res = db_execute_assoc($sql) or safe_die("Can't read quota table in transInsertAns ".$connect->ErrorMsg());     // Checked

    while ($qentry = $res->FetchRow())
    {
        $urldescription = $qentry['quotals_urldescrip'];
        $endurl  = $qentry['quotals_url'];

        foreach ($fieldnames as $sOldFieldname=>$sNewFieldname)
        {
            $pattern = $sOldFieldname;
            $replacement = $sNewFieldname;
            $urldescription=preg_replace('/'.$pattern.'/', $replacement, $urldescription);
            $endurl=preg_replace('/'.$pattern.'/', $replacement, $endurl);
        }

        if (strcmp($urldescription,$qentry['quotals_urldescrip']) !=0  ||
        (strcmp($endurl,$qentry['quotals_url']) !=0))
        {
            // Update Field
            $sqlupdate = "UPDATE {$dbprefix}quota_languagesettings SET quotals_urldescrip='".db_quote($urldescription)."', quotals_url='".db_quote($endurl)."' WHERE quotals_id={$qentry['quotals_id']}";
            $updateres=$connect->Execute($sqlupdate) or safe_die ("Couldn't update INSERTANS in quota_languagesettings<br />$sqlupdate<br />".$connect->ErrorMsg());    //Checked
        } // Enf if modified
    } // end while qentry


    # translate 'description' INSERTANS tags in groups
    $sql = "SELECT gid, language, group_name, description from {$dbprefix}groups WHERE sid=".$newsid." AND description LIKE '%{INSERTANS:".$oldsid."X%' OR group_name LIKE '%{INSERTANS:".$oldsid."X%'";
    $res = db_execute_assoc($sql) or safe_die("Can't read groups table in transInsertAns ".$connect->ErrorMsg());     // Checked

    while ($qentry = $res->FetchRow())
    {
        $gpname = $qentry['group_name'];
        $description = $qentry['description'];
        $gid = $qentry['gid'];
        $language = $qentry['language'];

        foreach ($fieldnames as $sOldFieldname=>$sNewFieldname)
        {
            $pattern = $sOldFieldname;
            $replacement = $sNewFieldname;
            $gpname = preg_replace('/'.$pattern.'/', $replacement, $gpname);
            $description=preg_replace('/'.$pattern.'/', $replacement, $description);
        }

        if (strcmp($description,$qentry['description']) !=0  ||
        strcmp($gpname,$qentry['group_name']) !=0)
        {
            // Update Fields
            $sqlupdate = "UPDATE {$dbprefix}groups SET description='".db_quote($description)."', group_name='".db_quote($gpname)."' WHERE gid=$gid AND language='$language'";
            $updateres=$connect->Execute($sqlupdate) or safe_die ("Couldn't update INSERTANS in groups<br />$sqlupdate<br />".$connect->ErrorMsg());    //Checked
        } // Enf if modified
    } // end while qentry

    # translate 'question' and 'help' INSERTANS tags in questions
    $sql = "SELECT qid, language, question, help from {$dbprefix}questions WHERE sid=".$newsid." AND (question LIKE '%{INSERTANS:".$oldsid."X%' OR help LIKE '%{INSERTANS:".$oldsid."X%')";
    $res = db_execute_assoc($sql) or safe_die("Can't read question table in transInsertAns ".$connect->ErrorMsg());     // Checked

    while ($qentry = $res->FetchRow())
    {
        $question = $qentry['question'];
        $help = $qentry['help'];
        $qid = $qentry['qid'];
        $language = $qentry['language'];

        foreach ($fieldnames as $sOldFieldname=>$sNewFieldname)
        {
            $pattern = $sOldFieldname;
            $replacement = $sNewFieldname;
            $question=preg_replace('/'.$pattern.'/', $replacement, $question);
            $help=preg_replace('/'.$pattern.'/', $replacement, $help);
        }

        if (strcmp($question,$qentry['question']) !=0 ||
        strcmp($help,$qentry['help']) !=0)
        {
            // Update Field
            $sqlupdate = "UPDATE {$dbprefix}questions SET question='".db_quote($question)."', help='".db_quote($help)."' WHERE qid=$qid AND language='$language'";
            $updateres=$connect->Execute($sqlupdate) or safe_die ("Couldn't update INSERTANS in question<br />$sqlupdate<br />".$connect->ErrorMsg());    //Checked
        } // Enf if modified
    } // end while qentry


    # translate 'answer' INSERTANS tags in answers
    $sql = "SELECT a.qid, a.language, a.code, a.answer from {$dbprefix}answers as a INNER JOIN {$dbprefix}questions as b ON a.qid=b.qid WHERE b.sid=".$newsid." AND a.answer LIKE '%{INSERTANS:".$oldsid."X%'";
    $res = db_execute_assoc($sql) or safe_die("Can't read answers table in transInsertAns ".$connect->ErrorMsg());     // Checked

    while ($qentry = $res->FetchRow())
    {
        $answer = $qentry['answer'];
        $code = $qentry['code'];
        $qid = $qentry['qid'];
        $language = $qentry['language'];

        foreach ($fieldnames as $sOldFieldname=>$sNewFieldname)
        {
            $pattern = $sOldFieldname;
            $replacement = $sNewFieldname;
            $answer=preg_replace('/'.$pattern.'/', $replacement, $answer);
        }

        if (strcmp($answer,$qentry['answer']) !=0)
        {
            // Update Field
            $sqlupdate = "UPDATE {$dbprefix}answers SET answer='".db_quote($answer)."' WHERE qid=$qid AND code='$code' AND language='$language'";
            $updateres=$connect->Execute($sqlupdate) or safe_die ("Couldn't update INSERTANS in answers<br />$sqlupdate<br />".$connect->ErrorMsg());    //Checked
        } // Enf if modified
    } // end while qentry
}


/**
* put your comment there...
*
* @param mixed $id
* @param mixed $type
*/
function hasResources($id,$type='survey')
{
    global $publicdir,$uploaddir;
    $dirname = $uploaddir;

    if ($type == 'survey')
    {
        $dirname .= "/surveys/$id";
    }
    elseif ($type == 'label')
    {
        $dirname .= "/labels/$id";
    }
    else
    {
        return false;
    }

    if (is_dir($dirname) && $dh=opendir($dirname))
    {
        while(($entry = readdir($dh)) !== false)
        {
            if($entry !== '.' && $entry !== '..')
            {
                return true;
                break;
            }
        }
        closedir($dh);
    }
    else
    {
        return false;
    }

    return false;
}

/**
* Creates a random sequence of characters
*
* @param mixed $length Length of resulting string
* @param string $pattern To define which characters should be in the resulting string
*/
function sRandomChars($length,$pattern="23456789abcdefghijkmnpqrstuvwxyz")
{
    $patternlength = strlen($pattern)-1;
    for($i=0;$i<$length;$i++)
    {
        if(isset($key))
            $key .= $pattern{rand(0,$patternlength)};
        else
            $key = $pattern{rand(0,$patternlength)};
    }
    return $key;
}



/**
* used to translate simple text to html (replacing \n with <br />
*
* @param mixed $mytext
* @param mixed $ishtml
* @return mixed
*/
function conditional_nl2br($mytext,$ishtml,$encoded='')
{
    if ($ishtml === true)
    {
        // $mytext has been processed by clang->gT with html mode
        // and thus \n has already been translated to &#10;
        if ($encoded == '')
        {
            $mytext=str_replace('&#10;', '<br />',$mytext);
        }
        return str_replace("\n", '<br />',$mytext);
    }
    else
    {
        return $mytext;
    }
}

function conditional2_nl2br($mytext,$ishtml)
{
    if ($ishtml === true)
    {
        return str_replace("\n", '<br />',$mytext);
    }
    else
    {
        return $mytext;
    }
}

function br2nl( $data ) {
    return preg_replace( '!<br.*>!iU', "\n", $data );
}


function safe_die($text)
{
    //Only allowed tag: <br />
    $textarray=explode('<br />',$text);
    $textarray=array_map('htmlspecialchars',$textarray);
    die(implode( '<br />',$textarray));
}

/**
* getQuotaInformation() returns quota information for the current survey
* @param string $surveyid - Survey identification number
* @param string $quotaid - Optional quotaid that restricts the result to a given quota
* @return array - nested array, Quotas->Members->Fields
*/
function getQuotaInformation($surveyid,$language,$quotaid='all')
{
    global $clang, $clienttoken;
    $baselang = GetBaseLanguageFromSurveyID($surveyid);

    $query = "SELECT * FROM ".db_table_name('quota').", ".db_table_name('quota_languagesettings')."
    WHERE ".db_table_name('quota').".id = ".db_table_name('quota_languagesettings').".quotals_quota_id
    AND sid='{$surveyid}'
    AND quotals_language='".$language."'";
    if ($quotaid != 'all')
    {
        $query .= " AND id=$quotaid";
    }

    $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());    //Checked
    $quota_info = array();
    $x=0;

    $surveyinfo=getSurveyInfo($surveyid);

    // Check all quotas for the current survey
    if ($result->RecordCount() > 0)
    {
        while ($survey_quotas = $result->FetchRow())
        {
            //Modify the URL - thanks janokary
            $survey_quotas['quotals_url']=str_replace("{SAVEDID}",isset($_SESSION['srid']) ? $_SESSION['srid'] : '', $survey_quotas['quotals_url']);
            $survey_quotas['quotals_url']=str_replace("{SID}", $surveyid, $survey_quotas['quotals_url']);
            $survey_quotas['quotals_url']=str_replace("{LANG}", $clang->getlangcode(), $survey_quotas['quotals_url']);
            $survey_quotas['quotals_url']=str_replace("{TOKEN}",$clienttoken, $survey_quotas['quotals_url']);

            array_push($quota_info,array('Name' => $survey_quotas['name'],
            'Limit' => $survey_quotas['qlimit'],
            'Action' => $survey_quotas['action'],
            'Message' => $survey_quotas['quotals_message'],
            'Url' => templatereplace(passthruReplace($survey_quotas['quotals_url'], $surveyinfo)),
            'UrlDescrip' => $survey_quotas['quotals_urldescrip'],
            'AutoloadUrl' => $survey_quotas['autoload_url']));
            $query = "SELECT * FROM ".db_table_name('quota_members')." WHERE quota_id='{$survey_quotas['id']}'";
            $result_qe = db_execute_assoc($query) or safe_die($connect->ErrorMsg());      //Checked
            $quota_info[$x]['members'] = array();
            if ($result_qe->RecordCount() > 0)
            {
                while ($quota_entry = $result_qe->FetchRow())
                {
                    $query = "SELECT type, title,gid FROM ".db_table_name('questions')." WHERE qid='{$quota_entry['qid']}' AND language='{$baselang}'";
                    $result_quest = db_execute_assoc($query) or safe_die($connect->ErrorMsg());     //Checked
                    $qtype = $result_quest->FetchRow();

                    $fieldnames = "0";

                    if ($qtype['type'] == "I" || $qtype['type'] == "G" || $qtype['type'] == "Y")
                    {
                        $fieldnames=array(0 => $surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid']);
                        $value = $quota_entry['code'];
                    }

                    if($qtype['type'] == "L" || $qtype['type'] == "O" || $qtype['type'] =="!")
                    {
                        $fieldnames=array(0 => $surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid']);
                        $value = $quota_entry['code'];
                    }

                    if($qtype['type'] == "M")
                    {
                        $fieldnames=array(0 => $surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid'].$quota_entry['code']);
                        $value = "Y";
                    }

                    if($qtype['type'] == "A" || $qtype['type'] == "B")
                    {
                        $temp = explode('-',$quota_entry['code']);
                        $fieldnames=array(0 => $surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid'].$temp[0]);
                        $value = $temp[1];
                    }

                    array_push($quota_info[$x]['members'],array('Title' => $qtype['title'],
                    'type' => $qtype['type'],
                    'code' => $quota_entry['code'],
                    'value' => $value,
                    'qid' => $quota_entry['qid'],
                    'fieldnames' => $fieldnames));
                }
            }
            $x++;
        }
    }
    return $quota_info;
}

/**
* get_quotaCompletedCount() returns the number of answers matching the quota
* @param string $surveyid - Survey identification number
* @param string $quotaid - quota id for which you want to compute the completed field
* @return string - number of mathing entries in the result DB or 'N/A'
*/
function get_quotaCompletedCount($surveyid, $quotaid)
{
    $result ="N/A";
    $quota_info = getQuotaInformation($surveyid,GetBaseLanguageFromSurveyID($surveyid),$quotaid);
    $quota = $quota_info[0];

    if ( db_tables_exist(db_table_name_nq('survey_'.$surveyid))  &&
    count($quota['members']) > 0)
    {
        $fields_list = array(); // Keep a list of fields for easy reference
        // construct an array of value for each $quota['members']['fieldnames']
        unset($querycond);
        $fields_query = array();
        foreach($quota['members'] as $member)
        {
            foreach($member['fieldnames'] as $fieldname)
            {
                if (!in_array($fieldname,$fields_list)){
                    $fields_list[] = $fieldname;
                    $fields_query[$fieldname] = array();
                }
                $fields_query[$fieldname][]= db_quote_id($fieldname)." = '{$member['value']}'";
            }
        }

        foreach($fields_list as $fieldname)
        {
            $select_query = " ( ".implode(' OR ',$fields_query[$fieldname]).' )';
            $querycond[] = $select_query;
        }

        $querysel = "SELECT count(id) as count FROM ".db_table_name('survey_'.$surveyid)." WHERE ".implode(' AND ',$querycond)." "." AND submitdate IS NOT NULL";
        $result = db_execute_assoc($querysel) or safe_die($connect->ErrorMsg()); //Checked
        $quota_check = $result->FetchRow();
        $result = $quota_check['count'];
    }

    return $result;
}

function fix_FCKeditor_text($str)
{
    $str = str_replace('<br type="_moz" />','',$str);
    if ($str == "<br />" || $str == " " || $str == "&nbsp;")
    {
        $str = "";
    }
    if (preg_match("/^[\s]+$/",$str))
    {
        $str='';
    }
    if ($str == "\n")
    {
        $str = "";
    }
    if (trim($str) == "&nbsp;" || trim($str)=='')
    { // chrome adds a single &nbsp; element to empty fckeditor fields
        $str = "";
    }

    return $str;
}


function recursive_stripslashes($array_or_string)
{
    if (is_array($array_or_string))
    {
        return array_map('recursive_stripslashes', $array_or_string);
    }
    else
    {
        return stripslashes($array_or_string);
    }
}




/**
* This is a helper function for GetAttributeFieldNames
*
* @param mixed $fieldname
*/
function filterforattributes ($fieldname)
{
    if (strpos($fieldname,'attribute_')===false) return false; else return true;
}


/**
* Retrieves the attribute field names from the related token table
*
* @param mixed $surveyid  The survey ID
* @return array The fieldnames
*/
function GetAttributeFieldNames($surveyid,$filter=true)
{
    global $dbprefix, $connect;
    if (tableExists('tokens_'.$surveyid) === false)
    {
        return Array();
    }
    $tokenfieldnames = array_values($connect->MetaColumnNames("{$dbprefix}tokens_$surveyid", true));
    if ($filter)
    {
        return array_filter($tokenfieldnames,'filterforattributes');
    }
    return $tokenfieldnames;
}

/**
* Retrieves the token field names usable for conditions from the related token table
*
* @param mixed $surveyid  The survey ID
* @return array The fieldnames
*/
function GetTokenConditionsFieldNames($surveyid)
{
    $extra_attrs=GetAttributeFieldNames($surveyid);
    $basic_attrs=Array('firstname','lastname','email','token','language','sent','remindersent','remindercount','usesleft');
    $basic_attrs[] = 'callattempts'; //queXS addition
    $basic_attrs[] = 'onappointment'; //queXS addition
    $basic_attrs[] = 'perccomplete'; //queXS addition
    $basic_attrs[] = 'messagesleft'; //queXS addition
    return array_merge($basic_attrs,$extra_attrs);
}

/**
* Retrieves the attribute names from the related token table
*
* @param mixed $surveyid  The survey ID
* @param boolean $onlyAttributes Set this to true if you only want the fieldnames of the additional attribue fields - defaults to false
* @param boolean $quexs True to include queXS fieldnames
* @return array The fieldnames as key and names as value in an Array
*/
function GetTokenFieldsAndNames($surveyid, $onlyAttributes=false, $quexs=true)
{
    global $dbprefix, $connect, $clang;
    if (tableExists('tokens_'.$surveyid) === false)
    {
        return Array();
    }
    $extra_attrs=GetAttributeFieldNames($surveyid);
    $basic_attrs=Array('firstname','lastname','email','token','language','sent','remindersent','remindercount','usesleft');
    if ($quexs)
    {
          $basic_attrs[] = 'callattempts'; //queXS addition
          $basic_attrs[] = 'onappointment'; //queXS addition
          $basic_attrs[] = 'perccomplete'; //queXS addition
          $basic_attrs[] = 'messagesleft'; //queXS addition
    }
    $basic_attrs_names=Array(
    $clang->gT('First name'),
    $clang->gT('Last name'),
    $clang->gT('Email address'),
    $clang->gT('Token code'),
    $clang->gT('Language code'),
    $clang->gT('Invitation sent date'),
    $clang->gT('Last Reminder sent date'),
    $clang->gT('Total numbers of sent reminders'),
    $clang->gT('Uses left')
    );

    if ($quexs)
    {
     include_once(dirname(__FILE__) . '/quexs.php');
     $basic_attrs_names[] = T_('queXS: Number of call attempts'); //queXS addition
     $basic_attrs_names[] = T_('queXS: On appointment?'); //queXS addition
     $basic_attrs_names[] = T_('queXS: Percentage complete'); //queXS addition
     $basic_attrs_names[] = T_('queXS: Number of answering machine messages left'); //queXS addition
    }

    $thissurvey=getSurveyInfo($surveyid);
    $attdescriptiondata=!empty($thissurvey['attributedescriptions']) ? $thissurvey['attributedescriptions'] : "";
    $attdescriptiondata=explode("\n",$attdescriptiondata);
    $attributedescriptions=array();
    $basic_attrs_and_names=array();
    $extra_attrs_and_names=array();
    foreach ($attdescriptiondata as $attdescription)
    {
        $attributedescriptions['attribute_'.substr($attdescription,10,strpos($attdescription,'=')-10)] = substr($attdescription,strpos($attdescription,'=')+1);
    }
    foreach ($extra_attrs as $fieldname)
    {
        if (isset($attributedescriptions[$fieldname]))
        {
            $extra_attrs_and_names[$fieldname]=$attributedescriptions[$fieldname];
        }
        else
        {
            $extra_attrs_and_names[$fieldname]=sprintf($clang->gT('Attribute %s'),substr($fieldname,10));
        }
    }
    if ($onlyAttributes===false)
    {
        $basic_attrs_and_names=array_combine($basic_attrs,$basic_attrs_names);
        return array_merge($basic_attrs_and_names,$extra_attrs_and_names);
    }
    else
    {
        return $extra_attrs_and_names;
    }
}

/**
* Retrieves the token attribute value from the related token table
*
* @param mixed $surveyid  The survey ID
* @param mixed $attrName  The token-attribute field name
* @param mixed $token  The token code
* @return string The token attribute value (or null on error)
*/
function GetAttributeValue($surveyid,$attrName,$token)
{
    global $dbprefix, $connect;
    $attrName=strtolower($attrName);
    if ($attrName == 'callattempts' || $attrName == 'onappointment' || $attrName == 'perccomplete' || $attrName == 'messagesleft') //queXS addition
    {
	include_once("quexs.php");
	$quexs_operator_id = get_operator_id();
	$quexs_case_id = get_case_id($quexs_operator_id);
	if ($quexs_case_id)
	{
		if ($attrName == 'callattempts')
			return get_call_attempts($quexs_case_id);
		else if ($attrName == 'onappointment')
			return is_on_appointment($quexs_case_id,$quexs_operator_id);
		else if ($attrName == 'perccomplete')
			return get_percent_complete($quexs_case_id);
		else if ($attrName == 'messagesleft')
			return get_messages_left($quexs_case_id);
	}
	else
		return 0;
    }
    else if (!tableExists('tokens_'.$surveyid) || !in_array($attrName,GetTokenConditionsFieldNames($surveyid)))
    {
        return null;
    }
    $sanitized_token=$connect->qstr($token,get_magic_quotes_gpc());
    $surveyid=sanitize_int($surveyid);

    $query="SELECT $attrName FROM {$dbprefix}tokens_$surveyid WHERE token=$sanitized_token";
    $result=db_execute_num($query);
    $count=$result->RecordCount();
    if ($count != 1)
    {
        return null;
    }
    else
    {
        $row=$result->FetchRow();
        return $row[0];
    }
}

/**
* This function strips any content between and including <style>  & <javascript> tags
*
* @param string $content String to clean
* @return string  Cleaned string
*/
function strip_javascript($content){
    $search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript
    '@<style[^>]*?>.*?</style>@siU'    // Strip style tags properly
    /*               ,'@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
    '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
    */
    );
    $text = preg_replace($search, '', $content);
    return $text;
}


/**
* This function cleans files from the temporary directory being older than 1 day
* @todo Make the days configurable
*/
function cleanTempDirectory()
{
    global $tempdir;
    $dir=  $tempdir.'/';
    $dp = opendir($dir) or die ('Could not open temporary directory');
    while ($file = readdir($dp)) {
        if (is_file($dir.$file) && (filemtime($dir.$file)) < (strtotime('-1 days')) && $file!='.gitignore' && $file!='index.html' && $file!='readme.txt' && $file!='..' && $file!='.' && $file!='.svn') {
            @unlink($dir.$file);
        }
    }
    $dir=  $tempdir.'/upload/';
    $dp = opendir($dir) or die ('Could not open temporary directory');
    while ($file = readdir($dp)) {
        if (is_file($dir.$file) && (filemtime($dir.$file)) < (strtotime('-1 days')) && $file!='.gitignore' && $file!='index.html' && $file!='readme.txt' && $file!='..' && $file!='.' && $file!='.svn') {
            @unlink($dir.$file);
        }
    }
    closedir($dp);
}


function use_firebug()
{
    if(FIREBUG == true)
    {
        return '<script type="text/javascript" src="http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js"></script>';
    };
};

/**
* This is a convenience function for the coversion of datetime values
*
* @param mixed $value
* @param mixed $fromdateformat
* @param mixed $todateformat
* @return string
*/
function convertDateTimeFormat($value, $fromdateformat, $todateformat)
{
    $datetimeobj = new Date_Time_Converter($value , $fromdateformat);
    return $datetimeobj->convert($todateformat);
}


/**
* This function removes the UTF-8 Byte Order Mark from a string
*
* @param string $str
* @return string
*/
function removeBOM($str=""){
    if(substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
        $str=substr($str, 3);
    }
    return $str;
}

/**
* This function requests the latest update information from the LimeSurvey.org website
*
* @returns array Contains update information or false if the request failed for some reason
*/
function GetUpdateInfo()
{
	return false; //queXS Addition
    global $homedir, $debug, $buildnumber, $versionnumber;
    require_once($homedir."/classes/http/http.php");

    $http=new http_class;

    /* Connection timeout */
    $http->timeout=0;
    /* Data transfer timeout */
    $http->data_timeout=0;
    $http->user_agent="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
    $http->GetRequestArguments("http://update.limesurvey.org?build=$buildnumber",$arguments);

    $updateinfo=false;
    $error=$http->Open($arguments);
    $error=$http->SendRequest($arguments);

    $http->ReadReplyHeaders($headers);


    if($error=="") {
        $body=''; $full_body='';
        for(;;){
            $error = $http->ReadReplyBody($body,10000);
            if($error != "" || strlen($body)==0) break;
            $full_body .= $body;
        }
        $updateinfo=json_decode($full_body,true);
        if ($http->response_status!='200')
        {
            $updateinfo['errorcode']=$http->response_status;
            $updateinfo['errorhtml']=$full_body;
        }
    }
    else
    {
        $updateinfo['errorcode']=$error;
        $updateinfo['errorhtml']=$error;
    }
    unset( $http );
    return $updateinfo;
}



/**
* This function updates the actual global variables if an update is available after using GetUpdateInfo
* @return Array with update or error information
*/
function updatecheck()
{
	return false;
    global $buildnumber;
    $updateinfo=GetUpdateInfo();
    if (isset($updateinfo['Targetversion']['build']) && (int)$updateinfo['Targetversion']['build']>(int)$buildnumber && trim($buildnumber)!='')
    {
        setGlobalSetting('updateavailable',1);
        setGlobalSetting('updatebuild',$updateinfo['Targetversion']['build']);
        setGlobalSetting('updateversion',$updateinfo['Targetversion']['versionnumber']);
    }
    else
    {
        setGlobalSetting('updateavailable',0);
    }
    setGlobalSetting('updatelastcheck',date('Y-m-d H:i:s'));
    return $updateinfo;
}

/**
* This function removes a directory recursively
*
* @param mixed $dirname
* @return bool
*/
function rmdirr($dirname)
{
    // Sanity check
    if (!file_exists($dirname)) {
        return false;
    }

    // Simple delete for a file
    if (is_file($dirname) || is_link($dirname)) {
        return @unlink($dirname);
    }

    // Loop through the folder
    $dir = dir($dirname);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Recurse
        rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
    }

    // Clean up
    $dir->close();
    return @rmdir($dirname);
}

function getTokenData($surveyid, $token)
{
    global $dbprefix, $connect;
    $query = "SELECT * FROM ".db_table_name('tokens_'.$surveyid)." WHERE token='".db_quote($token)."'";
    $result = db_execute_assoc($query) or safe_die("Couldn't get token info in getTokenData()<br />".$query."<br />".$connect->ErrorMsg());    //Checked
    $thistoken=array(); // so has default value
    while($row=$result->FetchRow())
    {
        $thistoken=array("firstname"=>$row['firstname'],
        "lastname"=>$row['lastname'],
        "email"=>$row['email'],
        "language" =>$row['language'],
        "usesleft" =>$row['usesleft'],
        );
        $attrfieldnames=GetAttributeFieldnames($surveyid);
        foreach ($attrfieldnames as $attr_name)
        {
            $thistoken[$attr_name]=$row[$attr_name];
        }
    } // while
    return $thistoken;
}


/**
* This function returns the complete directory path to a given template name
*
* @param mixed $sTemplateName
*/
function sGetTemplatePath($sTemplateName)
{
    global $standardtemplaterootdir, $usertemplaterootdir, $defaulttemplate;
    if (isStandardTemplate($sTemplateName))
    {
        return $standardtemplaterootdir.'/'.$sTemplateName;
    }
    else
    {
        if (is_dir($usertemplaterootdir.'/'.$sTemplateName))
        {
            return $usertemplaterootdir.'/'.$sTemplateName;
        }
        elseif (is_dir($usertemplaterootdir.'/'.$defaulttemplate))
        {
            return $usertemplaterootdir.'/'.$defaulttemplate;
        }
        elseif (isStandardTemplate($defaulttemplate))
        {
            return $standardtemplaterootdir.'/'.$defaulttemplate;
        }
        else
        {
            return $standardtemplaterootdir.'/default';
        }
    }
}

/**
* This function returns the complete URL path to a given template name
*
* @param mixed $sTemplateName
*/
function sGetTemplateURL($sTemplateName)
{
    global $standardtemplaterooturl, $standardtemplaterootdir, $usertemplaterooturl, $usertemplaterootdir, $defaulttemplate;
    if (isStandardTemplate($sTemplateName))
    {
        return $standardtemplaterooturl.'/'.$sTemplateName;
    }
    else
    {
        if (file_exists($usertemplaterootdir.'/'.$sTemplateName))
        {
            return $usertemplaterooturl.'/'.$sTemplateName;
        }
        elseif (file_exists($usertemplaterootdir.'/'.$defaulttemplate))
        {
            return $usertemplaterooturl.'/'.$defaulttemplate;
        }
        elseif (isStandardTemplate($defaulttemplate))
        {
            return $standardtemplaterooturl.'/'.$defaulttemplate;
        }
        else
        {
            return $standardtemplaterooturl.'/default';
        }
    }
}

/**
* Return the goodchars to be used when filtering input for numbers.
*
* @param $lang 	string	language used, for localisation
* @param $integer	bool	use only integer
* @param $negative	bool	allow negative values
*/
function getNumericalFormat($lang = 'en', $integer = false, $negative = true) {
    $goodchars = "0123456789";
    if ($integer === false) $goodchars .= ".";    //Todo, add localisation
    if ($negative === true) $goodchars .= "-";    //Todo, check databases
    return $goodchars;
}

/**
* Return an array of subquestions for a given sid/qid
*
* @param int $sid
* @param int $qid
* @param $sLanguage Language of the subquestion text
*/
function getSubQuestions($sid, $qid, $sLanguage) {
    global $dbprefix, $connect, $clang;
    static $subquestions;

    if (!isset($subquestions[$sid]))
    {
        $subquestions[$sid]=array();
    }
    if (!isset($subquestions[$sid][$sLanguage])) {
        $sid = sanitize_int($sid);
        $query = "SELECT sq.*, q.other FROM {$dbprefix}questions as sq, {$dbprefix}questions as q"
        ." WHERE sq.parent_qid=q.qid AND q.sid=$sid"
        ." AND sq.language='".$sLanguage. "' "
        ." AND q.language='".$sLanguage. "' "
        ." ORDER BY sq.parent_qid, q.question_order,sq.scale_id , sq.question_order";
        $result=db_execute_assoc($query) or safe_die ("Couldn't get perform answers query<br />$query<br />".$connect->ErrorMsg());    //Checked
        $resultset=array();
        while ($row=$result->FetchRow())
        {
            $resultset[$row['parent_qid']][] = $row;
        }
        $subquestions[$sid][$sLanguage] = $resultset;
    }
    if (isset($subquestions[$sid][$sLanguage][$qid])) return $subquestions[$sid][$sLanguage][$qid];
    return array();
}

/**
* Wrapper function to retrieve an xmlwriter object and do error handling if it is not compiled
* into PHP
*/
function getXMLWriter() {
    if (!extension_loaded('xmlwriter')) {
        safe_die('XMLWriter class not compiled into PHP, please contact your system administrator');
    } else {
        $xmlwriter = new XMLWriter();
    }
    return $xmlwriter;
}



/*
* Return a sql statement for renaming a table
*/
function db_rename_table($oldtable, $newtable)
{
    global $connect;

    $dict = NewDataDictionary($connect);
    $result=$dict->RenameTableSQL($oldtable, $newtable);
    return $result[0];
}

/**
* Returns true when a token can not be used (either doesn't exist or has less then one usage left
*
* @param mixed $tid Token
*/
function usedTokens($token)
{
    global $dbprefix, $surveyid;

    $utresult = true;
    $query = "SELECT tid, usesleft from {$dbprefix}tokens_$surveyid WHERE token=".db_quoteall($token);

    $result=db_execute_assoc($query,false,true);
    if ($result !== false) {
        $row=$result->FetchRow();
        if ($row['usesleft']>0) $utresult = false;
    }
    return $utresult;
}

/**
* Return true if the actual survey answer is completed
*
* @param int $surveyid The survey id
* @param int $srid The survey answer id
*/
function isCompleted($surveyid,$srid)
{
    global $connect;
    $completed = false;
    if($surveyid && $srid)
    {
        $sRow=$connect->GetRow("SELECT active FROM ".db_table_name('surveys')." WHERE sid=$surveyid");
        if($sRow['active']=='Y')
        {
            $sridRow=$connect->GetRow("SELECT submitdate FROM ".db_table_name('survey_'.$surveyid)." WHERE id=$srid");
            if($sridRow && $sridRow['submitdate'])
            {
                $completed=true;
            }
        }
    }
    return $completed;
};

/**
* redirect() generates a redirect URL for the apporpriate SSL mode then applies it.
*
* @param $ssl_mode string 's' or '' (empty).
*/
function redirect($ssl_mode)
{
    $url = 'http'.$ssl_mode.'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    if (!headers_sent())
    {	// If headers not sent yet... then do php redirect
        ob_clean();
        header('Location: '.$url);
        ob_flush();
        exit;
    };
};

/**
* SSL_mode() $force_ssl is on or off, it checks if the current
* request is to HTTPS (or not). If $force_ssl is on, and the
* request is not to HTTPS, it redirects the request to the HTTPS
* version of the URL, if the request is to HTTPS, it rewrites all
* the URL variables so they also point to HTTPS.
*/
function SSL_mode()
{
    global $rooturl , $homeurl , $publicurl , $tempurl , $imageurl , $uploadurl;
    global $usertemplaterooturl , $standardtemplaterooturl;
    global $parsedurl , $relativeurl , $fckeditordir , $ssl_emergency_override;

    $https = isset($_SERVER['HTTPS'])?$_SERVER['HTTPS']:'';
    if($ssl_emergency_override !== true )
    {
        $force_ssl = strtolower(getGlobalSetting('force_ssl'));
    }
    else
    {
        $force_ssl = 'off';
    };
    if( $force_ssl == 'on' && $https == '' )
    {
        redirect('s');
    }
    if( $force_ssl == 'off' && $https != '')
    {
        redirect('');
    };
};


/**
* Creates an array with details on a particular response for display purposes
* Used in Print answers (done), Detailed response view (Todo:)and Detailed admin notification email (done)
*
* @param mixed $iSurveyID
* @param mixed $iResponseID
* @param mixed $sLanguageCode
* @param boolean $bHonorConditions Apply conditions
*/
function aGetFullResponseTable($iSurveyID, $iResponseID, $sLanguageCode, $bHonorConditions=false)
{
    global $connect;
    $aFieldMap = createFieldMap($iSurveyID,'full',false,false,$sLanguageCode);
    //Get response data
    $idquery = "SELECT * FROM ".db_table_name('survey_'.$iSurveyID)." WHERE id=".$iResponseID;
    $idrow=$connect->GetRow($idquery) or safe_die ("Couldn't get entry<br />\n$idquery<br />\n".$connect->ErrorMsg()); //Checked

    // Create array of non-null values - those are the relevant ones
    $aRelevantFields = array();

    foreach ($aFieldMap as $sKey=>$fname)
    {
        if (!is_null($idrow[$fname['fieldname']]))
        {
            $aRelevantFields[$sKey]=$fname;
        }
    }

    $aResultTable=array();

    $oldgid = 0;
    $oldqid = 0;
    foreach ($aRelevantFields as $sKey=>$fname)
    {
        if (!empty($fname['qid']))
        {
            $attributes = getQuestionAttributes($fname['qid']);
            if (getQuestionAttributeValue($attributes, 'hidden') == 1)
            {
                continue;
            }
        }
        $question = $fname['question'];
        $subquestion='';
        if (isset($fname['gid']) && !empty($fname['gid'])) {
            //Check to see if gid is the same as before. if not show group name
            if ($oldgid !== $fname['gid'])
            {
                $oldgid = $fname['gid'];
                $aResultTable['gid_'.$fname['gid']]=array($fname['group_name']);
            }
        }
        if (isset($fname['qid']) && !empty($fname['qid']))
        {
            if ($oldqid !== $fname['qid'])
            {
                $oldqid = $fname['qid'];
                if (isset($fname['subquestion']) || isset($fname['subquestion1']) || isset($fname['subquestion2']))
                {
                    $aResultTable['qid_'.$fname['sid'].'X'.$fname['gid'].'X'.$fname['qid']]=array($fname['question'],'','');
                }
                else
                {
                    $answer=getextendedanswer($fname['fieldname'], $idrow[$fname['fieldname']]);
                    $aResultTable[$fname['fieldname']]=array($question,'',$answer);
                    continue;
                }
            }
        }
        else
        {
            $answer=getextendedanswer($fname['fieldname'], $idrow[$fname['fieldname']]);
            $aResultTable[$fname['fieldname']]=array($question,'',$answer);
            continue;
        }
        if (isset($fname['subquestion']))
            $subquestion = "{$fname['subquestion']}";

        if (isset($fname['subquestion1']))
            $subquestion = "{$fname['subquestion1']}";

        if (isset($fname['subquestion2']))
            $subquestion .= "[{$fname['subquestion2']}]";

        $answer=getextendedanswer($fname['fieldname'], $idrow[$fname['fieldname']]);
        $aResultTable[$fname['fieldname']]=array('',$subquestion,$answer);
    }
    return $aResultTable;
}



/**
* Check if $str is an integer, or string representation of an integer
*
* @param mixed $mStr
*/
function bIsNumericInt($mStr)
{
    if(is_int($mStr))
        return true;
    elseif(is_string($mStr))
        return preg_match("/^[0-9]+$/", $mStr);
    return false;
}

/**
* Invert key/values of an associative array, preserving multiple values in
* the source array as a single key with multiple values in the resulting
* array.
*
* This is not the same as array_flip(), which flattens the structure of the
* source array.
*
* @param array $aArr
*/
function aArrayInvert($aArr)
{
    $aRet = array();
    foreach($aArr as $k => $v)
        $aRet[$v][] = $k;
    return $aRet;
}

/**
* Include Keypad headers
*/
function vIncludeKeypad()
{
    global $js_header_includes, $css_header_includes, $clang;

    $js_header_includes[] = '/scripts/jquery/jquery.keypad.min.js';
    if ($clang->langcode !== 'en')
    {
        $js_header_includes[] = '/scripts/jquery/locale/jquery.ui.keypad-'.$clang->langcode.'.js';
    }
    $css_header_includes[] = '/scripts/jquery/css/jquery.keypad.alt.css';
}

/**
* Strips the DB prefix from a string - does not verify just strips the according number of characters
*
* @param mixed $sTableName
* @return string
*/
function sStripDBPrefix($sTableName)
{
    global $dbprefix;
    return substr($sTableName,strlen($dbprefix));
}

/*
* Emit the standard (last) onsubmit handler for the survey.
*
* This code in injected in the three questionnaire modes right after the <form> element,
* before the individual questions emit their own onsubmit replacement code.
*/
function sDefaultSubmitHandler()
{
    return <<<EOS
    <script type='text/javascript'>
    <!--
        // register the standard (last) onsubmit handler *first*
        document.limesurvey.onsubmit = std_onsubmit_handler;
    -->
    </script>
EOS;
}

/**
* This function fixes the group ID and type on all subquestions
*
*/
function fixSubquestions()
{
    $surveyidresult=db_select_limit_assoc("select sq.qid, sq.parent_qid, sq.gid as sqgid, q.gid, sq.type as sqtype, q.type
    from ".db_table_name('questions')." sq JOIN ".db_table_name('questions')." q on sq.parent_qid=q.qid
    where sq.parent_qid>0 and  (sq.gid!=q.gid or sq.type!=q.type)",1000);
    while ($sv = $surveyidresult->FetchRow())
    {
        db_execute_assoc('update '.db_table_name('questions')." set type='{$sv['type']}', gid={$sv['gid']} where qid={$sv['qid']}");
    }

}

/**
* Need custom version of JSON encode to avoid having Expression Manager mangle it
* @param type $val
* @return type
*/
function ls_json_encode($val)
{
    $ans = json_encode($val);
    $ans = str_replace(array('{','}'),array('{ ',' }'), $ans);
    return $ans;
}

/**
* This function returns the real IP address under all configurations
*
*/
function getIPAddress()
{
    global $bServerBehindProxy;
    if ($bServerBehindProxy)
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
    }
    if (!empty($_SERVER['REMOTE_ADDR']))
    {
        return $_SERVER['REMOTE_ADDR'];
    }
    else
    {
        return '127.0.0.1';
    }
}

// Closing PHP tag intentionally omitted - yes, it is okay
