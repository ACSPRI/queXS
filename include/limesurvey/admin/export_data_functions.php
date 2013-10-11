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
 * $Id: export_data_functions.php 11664 2011-12-16 05:19:42Z tmswhite $
 */

/**
 * Strips html tags and replaces new lines
 *
 * @param $string
 * @return $string
 */
function strip_tags_full($string) {
    $string=html_entity_decode($string, ENT_QUOTES, "UTF-8");
    //combining these into one mb_ereg_replace call ought to speed things up
    //$string = str_replace(array("\r\n","\r","\n",'-oth-'), '', $string);
    //The backslashes must be escaped twice, once for php, and again for the regexp
    //$string = str_replace("'|\\\\'", "&apos;", $string);
    return FlattenText($string);
}

/**
 * Returns true if passed $value is numeric
 *
 * @param $value
 * @return bool
 */
function my_is_numeric($value)  {
    if (empty($value)) return true;
    $eng_or_world = preg_match
    ('/^[+-]?'. // start marker and sign prefix
  '(((([0-9]+)|([0-9]{1,4}(,[0-9]{3,4})+)))?(\\.[0-9])?([0-9]*)|'. // american 
  '((([0-9]+)|([0-9]{1,4}(\\.[0-9]{3,4})+)))?(,[0-9])?([0-9]*))'. // world 
  '(e[0-9]+)?'. // exponent 
  '$/', // end marker 
    $value) == 1;
    return ($eng_or_world);
}

function spss_export_data ($na = null) {
    global $length_data;

    // Build array that has to be returned
    $fields = spss_fieldmap();

    //Now get the query string with all fields to export
    $query = spss_getquery();

    $result=db_execute_num($query) or safe_die("Couldn't get results<br />$query<br />".$connect->ErrorMsg()); //Checked
    $num_fields = $result->FieldCount();

    //This shouldn't occur, but just to be safe:
    if (count($fields)<>$num_fields) safe_die("Database inconsistency error");

    while (!$result->EOF) {
        $row = $result->GetRowAssoc(true);	//Get assoc array, use uppercase
        reset($fields);	//Jump to the first element in the field array
        $i = 1;
        foreach ($fields as $field)
        {
            $fieldno = strtoupper($field['sql_name']);
            if ($field['SPSStype']=='DATETIME23.2'){
                #convert mysql  datestamp (yyyy-mm-dd hh:mm:ss) to SPSS datetime (dd-mmm-yyyy hh:mm:ss) format
                if (isset($row[$fieldno]))
                {
                    list( $year, $month, $day, $hour, $minute, $second ) = preg_split( '([^0-9])', $row[$fieldno] );
                    if ($year != '' && (int)$year >= 1970)
                    {
                        echo "'".date('d-m-Y H:i:s', mktime( $hour, $minute, $second, $month, $day, $year ) )."'";
                    } else
                    {
                        echo ($na);
                    }
                }  else
                {
                    echo ($na);
                }
            } else if ($field['LStype'] == 'Y')
            {
                if ($row[$fieldno] == 'Y')    // Yes/No Question Type
                {
                    echo( "'1'");
                } else if ($row[$fieldno] == 'N'){
                    echo( "'2'");
                } else {
                    echo($na);
                }
            } else if ($field['LStype'] == 'G')    //Gender
            {
                if ($row[$fieldno] == 'F')
                {
                    echo( "'1'");
                } else if ($row[$fieldno] == 'M'){
                    echo( "'2'");
                } else {
                    echo($na);
                }
            } else if ($field['LStype'] == 'C')    //Yes/No/Uncertain
            {
                if ($row[$fieldno] == 'Y')
                {
                    echo( "'1'");
                } else if ($row[$fieldno] == 'N'){
                    echo( "'2'");
                } else if ($row[$fieldno] == 'U'){
                    echo( "'3'");
                } else {
                    echo($na);
                }
            } else if ($field['LStype'] == 'E')     //Increase / Same / Decrease
            {
                if ($row[$fieldno] == 'I')
                {
                    echo( "'1'");
                } else if ($row[$fieldno] == 'S'){
                    echo( "'2'");
                } else if ($row[$fieldno] == 'D'){
                    echo( "'3'");
                } else {
                    echo($na);
                }
            } elseif (($field['LStype'] == 'P' || $field['LStype'] == 'M') && (substr($field['code'],-7) != 'comment' && substr($field['code'],-5) != 'other'))
            {
                if ($row[$fieldno] == 'Y')
                {
                    echo("'1'");
                } else
                {
                    echo("'0'");
                }
            } elseif (!$field['hide']) {
					$strTmp=mb_substr(strip_tags_full($row[$fieldno]), 0, $length_data);
                if (trim($strTmp) != ''){
						$strTemp=str_replace(array("'","\n","\r"),array("''",' ',' '),trim($strTmp));
                    /*
                     * Temp quick fix for replacing decimal dots with comma's
                     if (my_is_numeric($strTemp)) {
                     $strTemp = str_replace('.',',',$strTemp);
                     }
                     */
                    echo "'$strTemp'";
                }
                else
                {
                    echo $na;
                }
            }
            if ($i<$num_fields && !$field['hide']) echo ',';
            $i++;
        }
        echo "\n";
        $result->MoveNext();
    }
}

/**
 * Check it the gives field has a labelset and return it as an array if true
 *
 * @param $field array field from spss_fieldmap
 * @return array or false
 */
function spss_getvalues ($field = array(), $qidattributes = null ) {
    global $surveyid, $dbprefix, $connect, $clang, $language, $length_vallabel;

    if (!isset($field['LStype']) || empty($field['LStype'])) return false;
    $answers=array();
    if (strpos("!LORFWZWH1",$field['LStype']) !== false) {
        if (substr($field['code'],-5) == 'other' || substr($field['code'],-7) == 'comment') {
            //We have a comment field, so free text
        } else {
            $query = "SELECT {$dbprefix}answers.code, {$dbprefix}answers.answer,
			{$dbprefix}questions.type FROM {$dbprefix}answers, {$dbprefix}questions WHERE";
            
            if (isset($field['scale_id'])) $query .= " {$dbprefix}answers.scale_id = " . (int) $field['scale_id'] . " AND";
            
            $query .= " {$dbprefix}answers.qid = '".$field["qid"]."' and {$dbprefix}questions.language='".$language."' and  {$dbprefix}answers.language='".$language."'
			    and {$dbprefix}questions.qid='".$field['qid']."' ORDER BY sortorder ASC";
            $result=db_execute_assoc($query) or safe_die("Couldn't lookup value labels<br />$query<br />".$connect->ErrorMsg()); //Checked
            $num_results = $result->RecordCount();
            if ($num_results > 0)
            {
                $displayvaluelabel = 0;
                # Build array that has to be returned
                for ($i=0; $i < $num_results; $i++)
                {
                    $row = $result->FetchRow();
                    $answers[] = array('code'=>$row['code'], 'value'=>mb_substr(strip_tags_full($row["answer"]),0,$length_vallabel));
                }
            }
        }
    } elseif ($field['LStype'] == ':') {
        $displayvaluelabel = 0;
        //Get the labels that could apply!
        if (is_null($qidattributes)) $qidattributes=getQuestionAttributes($field["qid"], $field['LStype']);
        if (trim($qidattributes['multiflexible_max'])!='') {
            $maxvalue=$qidattributes['multiflexible_max'];
        } else {
            $maxvalue=10;
        }
        if (trim($qidattributes['multiflexible_min'])!='')
        {
            $minvalue=$qidattributes['multiflexible_min'];
        } else {
            $minvalue=1;
        }
        if (trim($qidattributes['multiflexible_step'])!='')
        {
            $stepvalue=$qidattributes['multiflexible_step'];
        } else {
            $stepvalue=1;
        }
        if ($qidattributes['multiflexible_checkbox']!=0) {
            $minvalue=0;
            $maxvalue=1;
            $stepvalue=1;
        }
        for ($i=$minvalue; $i<=$maxvalue; $i+=$stepvalue)
        {
            $answers[] = array('code'=>$i, 'value'=>$i);
        }
    } elseif ($field['LStype'] == 'M' && substr($field['code'],-5) != 'other' && $field['size'] > 0)
    {
        $answers[] = array('code'=>1, 'value'=>$clang->gT('Yes'));
        $answers[] = array('code'=>0, 'value'=>$clang->gT('Not Selected'));
    } elseif ($field['LStype'] == "P" && substr($field['code'],-5) != 'other' && substr($field['code'],-7) != 'comment')
    {
        $answers[] = array('code'=>1, 'value'=>$clang->gT('Yes'));
        $answers[] = array('code'=>0, 'value'=>$clang->gT('Not Selected'));
    } elseif ($field['LStype'] == "G" && $field['size'] > 0)
    {
        $answers[] = array('code'=>1, 'value'=>$clang->gT('Female'));
        $answers[] = array('code'=>2, 'value'=>$clang->gT('Male'));
    } elseif ($field['LStype'] == "Y" && $field['size'] > 0)
    {
        $answers[] = array('code'=>1, 'value'=>$clang->gT('Yes'));
        $answers[] = array('code'=>2, 'value'=>$clang->gT('No'));
    } elseif ($field['LStype'] == "C" && $field['size'] > 0)
    {
        $answers[] = array('code'=>1, 'value'=>$clang->gT('Yes'));
        $answers[] = array('code'=>2, 'value'=>$clang->gT('No'));
        $answers[] = array('code'=>3, 'value'=>$clang->gT('Uncertain'));
    } elseif ($field['LStype'] == "E" && $field['size'] > 0)
    {
        $answers[] = array('code'=>1, 'value'=>$clang->gT('Increase'));
        $answers[] = array('code'=>2, 'value'=>$clang->gT('Same'));
        $answers[] = array('code'=>3, 'value'=>$clang->gT('Decrease'));
    }
    if (count($answers)>0) {
        //check the max width of the answers
        $size = 0;
        $spsstype = $field['SPSStype'];
        foreach ($answers as $answer) {
            $len = mb_strlen($answer['code']);
            if ($len>$size) $size = $len;
            if ($spsstype=='F' && (my_is_numeric($answer['code'])===false || $size>16)) $spsstype='A';
        }
        $answers['SPSStype'] = $spsstype;
        $answers['size'] = $size;
        return $answers;
    } else {
        return false;
    }
}

/**
 * Creates a fieldmap with all information necessary to output the fields
 *
 * @param $prefix string prefix for the variable ID
 * @return array
 */
function spss_fieldmap($prefix = 'V') {
    global $surveyid, $dbprefix, $typeMap, $connect, $clang;
    global $surveyprivate, $tokensexist, $language;

    $fieldmap = createFieldMap($surveyid, 'full');		//Create a FULL fieldmap

    #See if tokens are being used
    $tokensexist = tableExists('tokens_'.$surveyid);

    #Lookup the names of the attributes
    $query="SELECT sid, anonymized, language FROM {$dbprefix}surveys WHERE sid=$surveyid";
    $result=db_execute_assoc($query) or safe_die("Couldn't count fields<br />$query<br />".$connect->ErrorMsg());  //Checked
    $num_results = $result->RecordCount();
    $num_fields = $num_results;
    # Build array that has to be returned
    for ($i=0; $i < $num_results; $i++) {
        $row = $result->FetchRow();
        $surveyprivate=$row['anonymized'];
        $language=$row['language'];
    }

    $fieldno=0;

    $fields=array();
    if (isset($tokensexist) && $tokensexist == true && $surveyprivate == 'N') {
        $tokenattributes=GetTokenFieldsAndNames($surveyid,false,false);
        foreach ($tokenattributes as $attributefield=>$attributedescription)
        {
            //Drop the token field, since it is in the survey too
	    if($attributefield!='token' && $attributefield!='callattempts' && $attributefield!='onappointment') {
                $fieldno++;
                $fields[] = array('id'=>"$prefix$fieldno",'name'=>mb_substr($attributefield, 0, 8),
			    'qid'=>0,'code'=>'','SPSStype'=>'A','LStype'=>'Undef',
			    'VariableLabel'=>$attributedescription,'sql_name'=>$attributefield,'size'=>'100',
			    'title'=>$attributefield,'hide'=>0, 'scale'=>'');
            }
        }
    }

	//queXS addition - caseid
	$fieldno++;
	$fields[] = array('id'=>"$prefix$fieldno",'name'=>'caseid',
		    'qid'=>0,'code'=>'','SPSStype'=>'F','LStype'=>'Undef',
		    'VariableLabel'=>'Case ID','sql_name'=>'caseid','size'=>'15',
		    'title'=>'caseid','hide'=>0, 'scale'=>'');

    $tempArray = array();
    $fieldnames = array_values($connect->MetaColumnNames("{$dbprefix}survey_$surveyid", true));
    $num_results = count($fieldnames);
    $num_fields = $num_results;
    $diff = 0;
    $noQID = Array('id', 'token', 'datestamp', 'submitdate', 'startdate', 'startlanguage', 'ipaddr', 'refurl', 'lastpage');
    # Build array that has to be returned
    for ($i=0; $i < $num_results; $i++) {
        #Conditions for SPSS fields:
        # - Length may not be longer than 8 characters
        # - Name may not begin with a digit
        $fieldname = $fieldnames[$i];
        $fieldtype = '';
        $ftype='';
        $val_size = 1;
        $hide = 0;
        $export_scale = '';
        $code='';
        $scale_id = null;
        $aQuestionAttribs=array();
         
        #Determine field type
        if ($fieldname=='submitdate' || $fieldname=='startdate' || $fieldname == 'datestamp') {
            $fieldtype = 'DATETIME23.2';
        } elseif ($fieldname=='startlanguage') {
            $fieldtype = 'A';
            $val_size = 19;
        } elseif ($fieldname=='token') {
            $fieldtype = 'A';
            $val_size = 16;
        } elseif ($fieldname=='id') {
            $fieldtype = 'F';
            $val_size = 7; //Arbitrarilty restrict to 9,999,999 (7 digits) responses/survey
        } elseif ($fieldname == 'ipaddr') {
            $fieldtype = 'A';
            $val_size = 15;
        } elseif ($fieldname == 'refurl') {
            $fieldtype = 'A';
            $val_size = 255;
        } elseif ($fieldname == 'lastpage') {
        	$hide = 1;
        }
         
        #Get qid (question id)
        if (in_array($fieldname, $noQID) || substr($fieldname,0,10)=='attribute_'){
            $qid = 0;
            $varlabel = $fieldname;
            $ftitle = $fieldname;
        } else{
            //GET FIELD DATA
            if (!isset($fieldmap[$fieldname])) {
                //Field in database but no longer in survey... how is this possible?
                //@TODO: think of a fix.
                $fielddata = array();
                $qid=0;
                $varlabel = $fieldname;
                $ftitle = $fieldname;
                $fieldtype = "F";
                $val_size = 1;
            } else {
                $fielddata=$fieldmap[$fieldname];
                $qid=$fielddata['qid'];
                $ftype=$fielddata['type'];
                $fsid=$fielddata['sid'];
                $fgid=$fielddata['gid'];
                $code=mb_substr($fielddata['fieldname'],strlen($fsid."X".$fgid."X".$qid));
                $varlabel=$fielddata['question'];
                if (isset($fielddata['scale'])) $varlabel = "[{$fielddata['scale']}] ". $varlabel;
                if (isset($fielddata['subquestion'])) $varlabel = "[{$fielddata['subquestion']}] ". $varlabel;
                if (isset($fielddata['subquestion2'])) $varlabel = "[{$fielddata['subquestion2']}] ". $varlabel;
                if (isset($fielddata['subquestion1'])) $varlabel = "[{$fielddata['subquestion1']}] ". $varlabel;
                $ftitle=$fielddata['title'];
                if (!is_null($code) && $code<>"" ) $ftitle .= "_$code";
                if (isset($typeMap[$ftype]['size'])) $val_size = $typeMap[$ftype]['size'];
                if (isset($fielddata['scale_id'])) $scale_id = $fielddata['scale_id'];
                if($fieldtype == '') $fieldtype = $typeMap[$ftype]['SPSStype'];
                if (isset($typeMap[$ftype]['hide'])) {
                    $hide = $typeMap[$ftype]['hide'];
                    $diff++;
                }
                //Get default scale for this type
                if (isset($typeMap[$ftype]['Scale'])) $export_scale = $typeMap[$ftype]['Scale'];
                //But allow override
                $aQuestionAttribs = getQuestionAttributes($qid,$ftype);
                if (isset($aQuestionAttribs['scale_export'])) $export_scale = $aQuestionAttribs['scale_export'];
            }

        }
        $fieldno++;
        $fid = $fieldno - $diff;
        $lsLong = isset($typeMap[$ftype]["name"])?$typeMap[$ftype]["name"]:$ftype;
        $tempArray = array('id'=>"$prefix$fid",'name'=>mb_substr($fieldname, 0, 8),
		    'qid'=>$qid,'code'=>$code,'SPSStype'=>$fieldtype,'LStype'=>$ftype,"LSlong"=>$lsLong,
		    'ValueLabels'=>'','VariableLabel'=>$varlabel,"sql_name"=>$fieldname,"size"=>$val_size,
		    'title'=>$ftitle,'hide'=>$hide,'scale'=>$export_scale, 'scale_id'=>$scale_id);
        //Now check if we have to retrieve value labels
        $answers = spss_getvalues($tempArray, $aQuestionAttribs);
        if (is_array($answers)) {
            //Ok we have answers
            if (isset($answers['size'])) {
                $tempArray['size'] = $answers['size'];
                unset($answers['size']);
            }
            if (isset($answers['SPSStype'])) {
                $tempArray['SPSStype'] = $answers['SPSStype'];
                unset($answers['SPSStype']);
            }
            $tempArray['answers'] = $answers;
        }
        $fields[] = $tempArray;
    }
    return $fields;
}

/**
 * Creates a query string with all fields for the export
 *
 * @return string
 */
function spss_getquery() {
    global $surveyprivate, $dbprefix, $surveyid, $tokensexist;

    #See if tokens are being used
    if (isset($tokensexist) && $tokensexist == true && $surveyprivate == 'N') {
        $query="SELECT case1.case_id as caseid, ";
        $tokenattributes=GetTokenFieldsAndNames($surveyid,false,false);
        foreach ($tokenattributes as $attributefield=>$attributedescription) {
            //Drop the token field, since it is in the survey too
	    if($attributefield!='token' && $attributefield!='callattempts' && $attributefield!='onappointment') {
                $query .= "{$dbprefix}tokens_$surveyid.$attributefield, ";
            }
        }
        $query .= "{$dbprefix}survey_$surveyid.*
	    FROM {$dbprefix}survey_$surveyid
	    JOIN `case` as case1 ON ({$dbprefix}survey_$surveyid.token = case1.token)
	    LEFT JOIN {$dbprefix}tokens_$surveyid ON {$dbprefix}survey_$surveyid.token = {$dbprefix}tokens_$surveyid.token";
    } else {
        $query = "SELECT *
	    FROM {$dbprefix}survey_$surveyid";
    }

    $qfs = questionnaireSampleFilterstate();
    if ($qfs != false)
    {
	//Limit responses by questionnaire and/or sample
	$query .= "	JOIN `case` AS c ON ({$dbprefix}survey_$surveyid.token = c.token AND c.questionnaire_id = '{$qfs[0]}') ";
	if ($qfs[1] != 0) //if a sample is selected
		$query .= "	JOIN `sample` AS s ON (s.sample_id = c.sample_id AND s.import_id = '{$qfs[1]}') ";	
    }

    switch (incompleteAnsFilterstate()) {
        case 'inc':
            //Inclomplete answers only
            $query .= ' WHERE submitdate is null ';
            break;
        case 'filter':
            //Inclomplete answers only
            $query .= ' WHERE submitdate is not null ';
            break;
    }
    return $query;
}

/**
* BuildXMLFromQuery() creates a datadump of a table in XML using XMLWriter
* 
* @param mixed $xmlwriter  The existing XMLWriter object
* @param mixed $Query  The table query to build from
* @param mixed $tagname  If the XML tag of the resulting question should be named differently than the table name set it here
* @param array $excludes array of columnames not to include in export 
*/
function BuildXMLFromQuery($xmlwriter, $Query, $tagname='', $excludes = array())
{
    global $dbprefix, $connect;
    $QueryResult = db_execute_assoc($Query) or safe_die ("ERROR: $QueryResult<br />".$connect->ErrorMsg()); //safe
    preg_match('/FROM (\w+)( |,)/', $Query, $MatchResults);
    if ($tagname!='')
    {
        $TableName=$tagname;
    }
    else
    {
        $TableName = $MatchResults[1];;
        $TableName = substr($TableName, strlen($dbprefix), strlen($TableName));
    }
    if ($QueryResult->RecordCount()>0)
    {
        $exclude = array_flip($excludes);    //Flip key/value in array for faster checks
        $xmlwriter->startElement($TableName);
        $xmlwriter->startElement('fields');
        $Columninfo =  $QueryResult->fields;
        foreach ($Columninfo as $fieldname=>$value)
        {
            if (!isset($exclude[$fieldname])) $xmlwriter->writeElement('fieldname',$fieldname);
        }
        $xmlwriter->endElement(); // close columns
        $xmlwriter->startElement('rows');
        while ($Row = $QueryResult->FetchRow())
        {
            $xmlwriter->startElement('row');
            foreach ($Row as $Key=>$Value)
            {
                if (!isset($exclude[$Key])) {
                $xmlwriter->startElement($Key);
                    // Remove invalid XML characters
                    $xmlwriter->writeCData(preg_replace('/[^\x9\xA\xD\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u','',$Value));
                $xmlwriter->endElement();
            }
            }
            $xmlwriter->endElement(); // close row
        }
        $xmlwriter->endElement(); // close rows
        $xmlwriter->endElement(); // close tablename
    }
}
