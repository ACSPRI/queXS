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
 * $Id: export_data_r.php 12179 2012-01-24 13:27:31Z adevries $
 */

// Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

/*
 * The SPSS DATA LIST / BEGIN DATA parser is rather simple minded, the number after the type
 * specifier identifies the field width (maximum number of characters to scan)
 * It will stop short of that number of characters, honouring quote delimited
 * space separated strings, however if the width is too small the remaining data in the current
 * line becomes part of the next column.  Since we want to restrict this script to ONE scan of
 * the data (scan & output at same time), the information needed to construct the
 * DATA LIST is held in the $fields array, while the actual data is written to a
 * to a temporary location, updating length (size) values in the $fields array as
 * the tmp file is generated (uses @fwrite's return value rather than strlen).
 * Final output renders $fields to a DATA LIST, and then stitches in the tmp file data.
 *
 * Optimization opportunities remain in the VALUE LABELS section, which runs a query / column
 */

$length_vallabel = '120'; // Set the max text length of Value Labels
$length_data = '25500'; // Set the max text length of Text Data
$length_varlabel = '25500'; // Set the max text length of Variable Labels
$headerComment = '';
$tempFile = '';

include_once('login_check.php');
//for scale 1=nominal, 2=ordinal, 3=scale
$typeMap = array(
'5'=>Array('name'=>'5 Point Choice','size'=>1,'SPSStype'=>'F','Scale'=>3),
'B'=>Array('name'=>'Array (10 Point Choice)','size'=>1,'SPSStype'=>'F','Scale'=>3),
'A'=>Array('name'=>'Array (5 Point Choice)','size'=>1,'SPSStype'=>'F','Scale'=>3),
'F'=>Array('name'=>'Array (Flexible Labels)','size'=>1,'SPSStype'=>'F'),
'1'=>Array('name'=>'Array (Flexible Labels) Dual Scale','size'=>1,'SPSStype'=>'F'),
'H'=>Array('name'=>'Array (Flexible Labels) by Column','size'=>1,'SPSStype'=>'F'),
'E'=>Array('name'=>'Array (Increase, Same, Decrease)','size'=>1,'SPSStype'=>'F','Scale'=>2),
'C'=>Array('name'=>'Array (Yes/No/Uncertain)','size'=>1,'SPSStype'=>'F'),
'X'=>Array('name'=>'Boilerplate Question','size'=>1,'SPSStype'=>'A','hide'=>1),
'D'=>Array('name'=>'Date','size'=>10,'SPSStype'=>'SDATE'),
'G'=>Array('name'=>'Gender','size'=>1,'SPSStype'=>'F'),
'U'=>Array('name'=>'Huge Free Text','size'=>1,'SPSStype'=>'A'),
'I'=>Array('name'=>'Language Switch','size'=>1,'SPSStype'=>'A'),
'!'=>Array('name'=>'List (Dropdown)','size'=>1,'SPSStype'=>'F'),
'W'=>Array('name'=>'List (Flexible Labels) (Dropdown)','size'=>1,'SPSStype'=>'F'),
'Z'=>Array('name'=>'List (Flexible Labels) (Radio)','size'=>1,'SPSStype'=>'F'),
'L'=>Array('name'=>'List (Radio)','size'=>1,'SPSStype'=>'F'),
'O'=>Array('name'=>'List With Comment','size'=>1,'SPSStype'=>'F'),
'T'=>Array('name'=>'Long free text','size'=>1,'SPSStype'=>'A'),
'K'=>Array('name'=>'Multiple Numerical Input','size'=>1,'SPSStype'=>'F'),
'M'=>Array('name'=>'Multiple choice','size'=>1,'SPSStype'=>'F'),
'P'=>Array('name'=>'Multiple choice with comments','size'=>1,'SPSStype'=>'F'),
'Q'=>Array('name'=>'Multiple Short Text','size'=>1,'SPSStype'=>'F'),
'N'=>Array('name'=>'Numerical Input','size'=>3,'SPSStype'=>'F','Scale'=>3),
'R'=>Array('name'=>'Ranking','size'=>1,'SPSStype'=>'F'),
'S'=>Array('name'=>'Short free text','size'=>1,'SPSStype'=>'F'),
'Y'=>Array('name'=>'Yes/No','size'=>1,'SPSStype'=>'F'),
':'=>Array('name'=>'Multi flexi numbers','size'=>1,'SPSStype'=>'F'),
';'=>Array('name'=>'Multi flexi text','size'=>1,'SPSStype'=>'A'),
'*'=>Array('name'=>'Equation','size'=>1,'SPSStype'=>'A'),
);

if (!isset($surveyid)) {$surveyid=returnglobal('sid');}
$filterstate = incompleteAnsFilterstate();

$quexsfilterstate = questionnaireSampleFilterstate();

$headerComment = '#$Rev: 12179 $' . " $filterstate.\n";

if (isset($_GET['dldata'])) $subaction = "dldata";
if (isset($_GET['dlstructure'])) $subaction = "dlstructure";

if  (!isset($subaction))
{
    $exportroutput = browsemenubar($clang->gT('Export results'));

    $exportroutput = browsemenubar($clang->gT('Export results'));
    $exportroutput .= "<div class='header ui-widget-header'>".$clang->gT("Export result data to R")."</div>\n";

	$selecthide="";
	$selectshow="";
	$selectinc="";
	switch ($filterstate) {
		case "inc":
		    $selectinc="selected='selected'";
		    break;
		case "filter":
			$selecthide="selected='selected'";
			break;
		default:
			$selectshow="selected='selected'";
	}

	$exportroutput .= "<form action='$scriptname' id='exportspss' method='get'><ul>\n"
	."<li><label for='filterinc'>".$clang->gT("Data selection:")."</label><select id='filterinc' name='filterinc' onchange='this.form.submit();'>\n"
    ."\t<option value='filter' $selecthide>".$clang->gT("Completed responses only")."</option>\n"
    ."\t<option value='show' $selectshow>".$clang->gT("All responses")."</option>\n"
    ."\t<option value='incomplete' $selectinc>".$clang->gT("Incomplete responses only")."</option>\n"
	."</select></li>\n";

    //queXS Addition
    include_once("../quexs.php");
    $exportroutput .= "<li><label for='quexsfilterinc'>".$clang->gT("Questionnaire and Sample selection:")."</label><select id='quexsfilterinc' name='quexsfilterinc' onchange='this.form.submit();'>\n"
    ."\t<option value='all' >".$clang->gT("All queXS questionnaires and samples associated with this instrument")."</option>\n"
    . get_questionnaire_sample_list($surveyid,$quexsfilterstate)
    ."</select></li>\n";

    $filename = "survey_".$surveyid."_R_syntax_file.R";

    $exportroutput .= "<input type='hidden' name='sid' value='$surveyid' />\n"
	."<input type='hidden' name='action' value='exportr' /></li>\n"
	."<li><label for='dlstructure'>" . $clang->gT("Step 1:") . "</label><input type='submit' name='dlstructure' id='dlstructure' value='" . $clang->gT("Export R syntax file") . "'/></li>\n"
	."<li><label for='dldata'/>" . $clang->gT("Step 2:") . "</label><input type='submit' name='dldata' id='dldata' value='" . $clang->gT("Export .csv data file") . "'/></li></ul>\n"
	."</form>\n"

    ."<p><div class='messagebox ui-corner-all'><div class='header ui-widget-header'>".$clang->gT("Instructions for the impatient")."</div>"
    ."<br/><ol style='margin:0 auto; font-size:8pt;'>"
    ."<li>".$clang->gT("Download the data and the syntax file.")."</li>"
    ."<li>".$clang->gT("Save both of them on the R working directory (use getwd() and setwd() on the R command window to get and set it)").".</li>"
    ."<li>".sprintf($clang->gT("digit:       source(\"%s\", encoding = \"UTF-8\")        on the R command window"), $filename)."</li>"
    ."</ol><br />"
    .$clang->gT("Your data should be imported now, the data.frame is named \"data\", the variable.labels are attributes of data (\"attributes(data)\$variable.labels\"), like for foreign:read.spss.")
    ."</div>";
}
else
{
    // Get Base language:
    $language = GetBaseLanguageFromSurveyID($surveyid);
    $clang = new limesurvey_lang($language);
    require_once ("export_data_functions.php");
}


if  ($subaction=='dldata')
{
    header("Content-Disposition: attachment; filename=survey_".$surveyid."_R_data_file.csv");
    header("Content-type: text/comma-separated-values; charset=UTF-8");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

    $na="";	//change to empty string instead of two double quotes to fix warnings on NA
    spss_export_data($na);

    exit;
}

if  ($subaction=='dlstructure')
{
    header("Content-Disposition: attachment; filename=survey_".$surveyid."_R_syntax_file.R");
    header("Content-type: application/download; charset=UTF-8");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

  echo $headerComment;
  echo "data <- read.table(\"survey_".$surveyid
      ."_R_data_file.csv\", sep=\",\", quote = \"'\", "
      ."na.strings=c(\"\",\"\\\"\\\"\"), "
      ."stringsAsFactors=FALSE)\n\n";
//  echo "names(data) <- paste(\"V\",1:dim(data)[2],sep=\"\")\n\n";

    // Build array that has to be returned
  $fields = spss_fieldmap("V");

    //Now get the query string with all fields to export
    $query = spss_getquery();

    $result=db_execute_num($query) or safe_die("Couldn't get results<br />$query<br />".$connect->ErrorMsg()); //Checked
    $num_fields = $result->FieldCount();

    //Now we check if we need to adjust the size of the field or the type of the field
    while ($row = $result->FetchRow()) {
        $fieldno = 0;
        while ($fieldno < $num_fields)
        {
            //Performance improvement, don't recheck fields that have valuelabels
            if (!isset($fields[$fieldno]['answers'])) {
                $strTmp=mb_substr(strip_tags_full($row[$fieldno]), 0, $length_data);
                $len = mb_strlen($strTmp);
                if($len > $fields[$fieldno]['size']) $fields[$fieldno]['size'] = $len;

                if (trim($strTmp) != ''){
                    if ($fields[$fieldno]['SPSStype']=='F' && (my_is_numeric($strTmp)===false || $fields[$fieldno]['size']>16))
                    {
                        $fields[$fieldno]['SPSStype']='A';
                    }
                }
            }
            $fieldno++;
        }
    }

  $errors = "";
  $i = 1;
  foreach ($fields as $field)
  {
    if($field['SPSStype'] == 'DATETIME23.2') $field['size']='';
    if($field['LStype'] == 'N' || $field['LStype']=='K')
    {
        $field['size'].='.'.($field['size']-1);
    }
    switch ($field['SPSStype'])
    {
      case 'F':
          $type="numeric";
          break;
      case 'A':
          $type="character";
          break;
      case 'DATETIME23.2':
      case 'SDATE':
          $type="character";
          //@TODO set $type to format for date
          break;
    }

    if (!$field['hide'])
    {
      echo "data[, " . $i . "] <- "
           ."as.$type(data[, " . $i . "])\n";

      echo 'attributes(data)$variable.labels[' . $i . '] <- "'
              . addslashes(
                  htmlspecialchars_decode(
                    mb_substr(
                      strip_tags_full(
                        $field['VariableLabel']),0,$length_varlabel)))  
              . '"' . "\n";

      // Create the value Labels!
      if (isset($field['answers']))
      {
            $answers = $field['answers'];
            //print out the value labels!
            if($field['LStype'] != ':') // Exclude array (numeric) type questions. There must be a better way of doing this, but I can't find it. (AdV)
              {
                echo 'data[, ' . $i .'] <- factor(data[, ' . $i . '], levels=c(';
                $str="";
                foreach ($answers as $answer) {
                    if ($field['SPSStype']=="F" && my_is_numeric($answer['code'])) {
                        $str .= ",{$answer['code']}";
                    } else {
                        $str .= ",\"{$answer['code']}\"";
                    }
                }
                $str = mb_substr($str,1);
                echo $str . '),labels=c(';
                $str="";
                foreach ($answers as $answer) {
                    $str .= ",\"{$answer['value']}\"";
                }
                $str = mb_substr($str,1);
            if($field['scale']!=='' && $field['scale'] == 2 ) {
                    $scale = ",ordered=TRUE";
                } else {
                    $scale = "";
                }
                echo "$str)$scale)\n";
            }
          }

    //Rename the Variables (in case somethings goes wrong, we still have the OLD values
      if (isset($field['sql_name']))
      {
            $ftitle = $field['title'];
          if (!preg_match ("/^([a-z]|[A-Z])+.*$/", $ftitle))
          {
                $ftitle = "q_" . $ftitle;
            }
            $ftitle = str_replace(array("-",":",";","!"), array("_hyph_","_dd_","_dc_","_excl_"), $ftitle);
          if (!$field['hide'])
          {
            if ($ftitle != $field['title'])
            {
              $errors .= "# Variable name was incorrect and was changed from {$field['title']} to $ftitle .\n";
            }
            echo "names(data)[" . $i . "] <- "
                 . "\"". $ftitle . "\"\n";  // <AdV> added \n
        }
          $i++;
    }
        else
        {
          echo "#sql_name not set\n";
        }

      }
      else
      {
        echo "#Field hidden\n";
      }
      echo "\n";

    }  // end foreach
    echo $errors;
    exit;
}

?>
