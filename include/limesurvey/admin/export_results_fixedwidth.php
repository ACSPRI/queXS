<?php

	
/*fixedwidth.php
**
**Export the data from this survey to a fixed width ASCII file
**Designed to work with quexml.php which exports to queXML
**And the queXML to DDI stylesheet which then will produce data documentation
**for your questionnaire, and the ability to import to SPSS/etc
**
**See http://quexml.sourceforge.net/ for more information about queXML
**Copyright: Deakin University 2007
**Author: Adam Zammit
**
Licence:
# This program is free software; you can redistribute 		#
# it and/or modify it under the terms of the GNU General 	#
# Public License as published by the Free Software 		#
# Foundation; either version 2 of the License, or (at your 	#
# option) any later version.					#
#								#
# This program is distributed in the hope that it will be 	#
# useful, but WITHOUT ANY WARRANTY; without even the 		#
# implied warranty of MERCHANTABILITY or FITNESS FOR A 		#
# PARTICULAR PURPOSE.  See the GNU General Public License 	#
# for more details.						#
#								#
# You should have received a copy of the GNU General 		#
# Public License along with this program; if not, write to 	#
# the Free Software Foundation, Inc., 59 Temple Place - 	#
# Suite 330, Boston, MA  02111-1307, USA.			#
#############################################################	
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
		._GO_ADMIN."' onClick=\"window.open('$scriptname', '_self')\">\n"
		."\t</td></tr>\n"
		."</table>\n"
		."</body></html>\n";
	exit;
	}


//array of varname and width
$varwidth = array();
$vartype = array();

function get_width($qid,$default)
{
	global $dbprefix;
	$Query = "SELECT value FROM {$dbprefix}question_attributes WHERE qid = '$qid' and attribute = 'maximum_chars'";
	$QueryResult = mysql_query($Query) or die ("ERROR: $QueryResult<br />".mysql_error());

	while ($Row = mysql_fetch_assoc($QueryResult))
	{
	    $default = $Row['value'];
	}

	return $default;
}



function fixed_width($lid)
{
	global $dbprefix;
	$Query = "SELECT MAX(LENGTH(code)) as c FROM {$dbprefix}labels WHERE lid = $lid";
	$QueryResult = mysql_query($Query) or die ("ERROR: $QueryResult<br />".mysql_error());

	$val = 1;

	while ($Row = mysql_fetch_assoc($QueryResult))
	{
	    $val = $Row['c'];
	}

	return $val;
}

function create_multi($qid,$varname,$length,$type)
{
	global $varwidth;
	global $vartype;
	global $dbprefix;
	$Query = "SELECT * FROM {$dbprefix}answers WHERE qid = $qid ORDER BY sortorder ASC";
	$QueryResult = mysql_query($Query) or die ("ERROR: $QueryResult<br />".mysql_error());

	while ($Row = mysql_fetch_assoc($QueryResult))
	{
		$v = $varname . $Row['code'];
		$varwidth[$v] = $length;
		$vartype[$v] = $type;
	}

	return;

}

/*This function was sourced from the php website, help on str_replace
 * No author was listed at the time of access
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



//foreach question
$Query = "SELECT * FROM {$dbprefix}questions WHERE sid=$surveyid AND type NOT LIKE 'X' ORDER BY gid,question_order ASC";
$QR = mysql_query($Query) or die ("ERROR: $QueryResult<br />".mysql_error());
while ($RowQ = mysql_fetch_assoc($QR))
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
	            $varwidth[$varName]=fixed_width($lid);
		    $vartype[$varName] = 1;
	            break;
	        case "L": //LIST drop-down/radio-button list
	            $varwidth[$varName]=fixed_width($lid);
		    $vartype[$varName] = 1;
	            break;
	        case "W": //List - dropdown
	            $varwidth[$varName]=fixed_width($lid);
		    $vartype[$varName] = 1;
	            break;
	        case "!": //List - dropdown
	            $varwidth[$varName]=fixed_width($lid);
		    $vartype[$varName] = 1;
	            break;
	        case "O": //LIST WITH COMMENT drop-down/radio-button list + textarea
	            //Not yet implemented		            
	            break;
	        case "R": //RANKING STYLE
	            //Not yet implemented
	            break;
	        case "M": //MULTIPLE OPTIONS checkbox
	            create_multi($qid,$varName,1,3);
	            break;
	        case "P": //MULTIPLE OPTIONS WITH COMMENTS checkbox + text
     		            //Not yet implemented
		    break;
	        case "Q": //MULTIPLE SHORT TEXT
	            create_multi($qid,$varName,get_width($qid,24),2);		            
	            break;
	        case "K": //MULTIPLE NUMERICAL
	            create_multi($qid,$varName,get_width($qid,10),1);		            
 	            break;
  	        case "N": //NUMERICAL QUESTION TYPE
	            $varwidth[$varName]= get_width($qid,10);
		    $vartype[$varName] = 1;
	            break;
	        case "S": //SHORT FREE TEXT
	            $varwidth[$varName]= get_width($qid,240);
		    $vartype[$varName] = 2;
	            break;
	        case "T": //LONG FREE TEXT
	            $varwidth[$varName]= get_width($qid,1024);
		    $vartype[$varName] = 2;
		    break;
	        case "U": //HUGE FREE TEXT
	            $varwidth[$varName]= get_width($qid,2048);
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
			create_multi($qid,$varName,fixed_width($lid),1);    
	            break;
	        case "H": //ARRAY (Flexible) - Column Format
			create_multi($qid,$varName,fixed_width($lid),1);
    			break;
		case "^": //SLIDER CONTROL
	            //Not yet implemented
		    break;
	} //End Switch
		
		
}


//print_r($varwidth);


$fn = "survey_$surveyid.dat";

header("Content-Type: application/download");
header("Content-Disposition: attachment; filename=$fn");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache");                          // HTTP/1.0

$Query = "SELECT * FROM {$dbprefix}survey_$surveyid WHERE submitdate IS NOT NULL";
$QueryResult = mysql_query($Query) or die ("ERROR: $QueryResult<br />".mysql_error());

while ($Row = mysql_fetch_assoc($QueryResult))
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
	echo str_pad(substr($Row['id'],0,9), 9, " ", STR_PAD_LEFT);
	echo str_pad(substr($Row['datestamp'],0,16), 16, " ", STR_PAD_LEFT);
	echo "\n";
}

exit;

?>
