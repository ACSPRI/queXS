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
* $Id: quota.php 7132 2009-06-17 14:18:05Z c_schmitz $
*/

include_once("login_check.php");  //Login Check dies also if the script is started directly

function getQuotaAnswers($qid,$surveyid,$quota_id)
{
	global $clang;
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	$query = "SELECT type, title FROM ".db_table_name('questions')." WHERE qid='{$qid}' AND language='{$baselang}'";
	$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
	$qtype = $result->FetchRow();

	if ($qtype['type'] == 'G')
	{
		$query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";

		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

		$answerlist = array('M' => array('Title' => $qtype['title'], 'Display' => $clang->gT("Male"), 'code' => 'M'),
		'F' => array('Title' => $qtype['title'],'Display' => $clang->gT("Female"), 'code' => 'F'));

		if ($result->RecordCount() > 0)
		{
			while ($quotalist = $result->FetchRow())
			{
				$answerlist[$quotalist['code']]['rowexists'] = '1';
			}

		}
	}
	
	if ($qtype['type'] == 'M')
	{
		$query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";
		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

		$query = "SELECT code,answer FROM ".db_table_name('answers')." WHERE qid='{$qid}'";
		$ansresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
		
		$answerlist = array();
		
		while ($dbanslist = $ansresult->FetchRow())
		{
			$tmparrayans = array('Title' => $qtype['title'], 'Display' => substr($dbanslist['answer'],0,40), 'code' => $dbanslist['code']);
			$answerlist[$dbanslist['code']]	= $tmparrayans;
		}

		if ($result->RecordCount() > 0)
		{
			while ($quotalist = $result->FetchRow())
			{
				$answerlist[$quotalist['code']]['rowexists'] = '1';
			}

		}
	}
	
	if ($qtype['type'] == 'L' || $qtype['type'] == 'O' || $qtype['type'] == '!')
	{
		$query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";
		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
		
		$query = "SELECT code,answer FROM ".db_table_name('answers')." WHERE qid='{$qid}'";
		$ansresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
		
		$answerlist = array();
		
		while ($dbanslist = $ansresult->FetchRow())
		{
		    $answerlist[$dbanslist['code']] = array('Title'=>$qtype['title'],
		                                                  'Display'=>substr($dbanslist['answer'],0,40),
		                                                  'code'=>$dbanslist['code']);
		}

		if ($result->RecordCount() > 0)
		{
			while ($quotalist = $result->FetchRow())
			{
				$answerlist[$quotalist['code']]['rowexists'] = '1';
			}

		}

	}

	if ($qtype['type'] == 'A')
	{
		$query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";
		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

		$query = "SELECT code,answer FROM ".db_table_name('answers')." WHERE qid='{$qid}'";
		$ansresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
		
		$answerlist = array();
		
		while ($dbanslist = $ansresult->FetchRow())
		{
			for ($x=1; $x<6; $x++)
			{
				$tmparrayans = array('Title' => $qtype['title'], 'Display' => substr($dbanslist['answer'],0,40).' ['.$x.']', 'code' => $dbanslist['code']);
				$answerlist[$dbanslist['code']."-".$x]	= $tmparrayans;
			}
		}

		if ($result->RecordCount() > 0)
		{
			while ($quotalist = $result->FetchRow())
			{
				$answerlist[$quotalist['code']]['rowexists'] = '1';
			}

		}
	}
	
	if ($qtype['type'] == 'B')
	{
		$query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";
		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

		$query = "SELECT code,answer FROM ".db_table_name('answers')." WHERE qid='{$qid}'";
		$ansresult = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
		
		$answerlist = array();
		
		while ($dbanslist = $ansresult->FetchRow())
		{
			for ($x=1; $x<11; $x++)
			{
				$tmparrayans = array('Title' => $qtype['title'], 'Display' => substr($dbanslist['answer'],0,40).' ['.$x.']', 'code' => $dbanslist['code']);
				$answerlist[$dbanslist['code']."-".$x]	= $tmparrayans;
			}
		}

		if ($result->RecordCount() > 0)
		{
			while ($quotalist = $result->FetchRow())
			{
				$answerlist[$quotalist['code']]['rowexists'] = '1';
			}

		}
	}
	
	if ($qtype['type'] == 'Y')
	{
		$query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";

		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

		$answerlist = array('Y' => array('Title' => $qtype['title'], 'Display' => $clang->gT("Yes"), 'code' => 'Y'),
		'N' => array('Title' => $qtype['title'],'Display' => $clang->gT("No"), 'code' => 'N'));

		if ($result->RecordCount() > 0)
		{
			while ($quotalist = $result->FetchRow())
			{
				$answerlist[$quotalist['code']]['rowexists'] = '1';
			}

		}
	}

	if ($qtype['type'] == 'I')
	{
		
		$slangs = GetAdditionalLanguagesFromSurveyID($surveyid);
		array_unshift($slangs,$baselang);
		
		$query = "SELECT * FROM ".db_table_name('quota_members')." WHERE sid='{$surveyid}' and qid='{$qid}' and quota_id='{$quota_id}'";
		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

		while(list($key,$value) = each($slangs))
		{
			$tmparrayans = array('Title' => $qtype['title'], 'Display' => getLanguageNameFromCode($value,false), $value);
			$answerlist[$value]	= $tmparrayans;
		}
		
		if ($result->RecordCount() > 0)
		{
			while ($quotalist = $result->FetchRow())
			{
				$answerlist[$quotalist['code']]['rowexists'] = '1';
			}

		}
	}
	
	if (!isset($answerlist))
	{
		return array();
	}
	else
	{
		return $answerlist;
	}
}

if($sumrows5['edit_survey_property'] || $_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
{
	if (isset($_POST['quotamax'])) $_POST['quotamax']=sanitize_int($_POST['quotamax']);
	if (!isset($action)) $action=returnglobal('action');
	if (!isset($subaction)) $subaction=returnglobal('subaction');
	if (!isset($quotasoutput)) $quotasoutput = "";
	if($subaction == "insertquota")
	{
	    if(!isset($_POST['quota_limit']) || $_POST['quota_limit'] < 1)
	    {
		    $_POST['quota_limit'] = 1;
		}
		$_POST  = array_map('db_quote', $_POST);
		$query = "INSERT INTO ".db_table_name('quota')." (sid,name,qlimit,action,autoload_url) 
		          VALUES ('$surveyid','{$_POST['quota_name']}','{$_POST['quota_limit']}','1', '1')";
		$connect->Execute($query) or safe_die("Error inserting limit".$connect->ErrorMsg());
		$quotaid=$connect->Insert_Id(db_table_name_nq('quota'),"id");
		
		//Get the languages used in this survey
  		$langs = GetAdditionalLanguagesFromSurveyID($surveyid);
  		$baselang = GetBaseLanguageFromSurveyID($surveyid);
  		$langs[] = $baselang;
        //Iterate through each language, and make sure there is a quota message for it
		$errorstring = '';  
        foreach ($langs as $lang)
        {
          if (!$_POST['quotals_message_'.$lang]) { $errorstring.= GetLanguageNameFromCode($lang,false)."\\n";}
		}
        if ($errorstring!='') 
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Quota could not be added.\\n\\nIt is missing a quota message for the following languages","js").":\\n".$errorstring."\")\n //-->\n</script>\n";
        }  
		else
		//All the required quota messages exist, now we can insert this info into the database
		{
	   		require_once("../classes/inputfilter/class.inputfilter_clean.php");
		    $myFilter = new InputFilter('','',1,1,1); 
         
    		foreach ($langs as $lang) //Iterate through each language
	       	{
		     	//Clean XSS
		     	if ($filterxsshtml)
		     	{
  		 		    $_POST['quotals_message_'.$lang]=$myFilter->process($_POST['quotals_message_'.$lang]);
		     	}
                else
                {
                    $_POST['quotals_message_'.$lang] = html_entity_decode($_POST['quotals_message_'.$lang], ENT_QUOTES, "UTF-8");
                }
                
                // Fix bug with FCKEditor saving strange BR types
                $_POST['quotals_message_'.$lang]=fix_FCKeditor_text($_POST['quotals_message_'.$lang]);

		include_once(dirname(__FILE__) . '/../quexs.php');

				//Now save the language to the database:
                $query = "INSERT INTO ".db_table_name('quota_languagesettings')." (quotals_quota_id, quotals_language, quotals_name, quotals_message, quotals_url, quotals_urldescrip)
		        	      VALUES ('$quotaid', '$lang', '".db_quote($_POST['quota_name'])."', '".db_quote($_POST['quotals_message_'.$lang])."', '".QUEXS_URL."rs_quota_end.php', '".QUEXS_URL."rs_quota_end.php')";
		        $connect->Execute($query) or safe_die($connect->ErrorMsg());
			}
		} //End insert language based components
		$viewquota = "1";

	} //End foreach $lang

	if($subaction == "modifyquota")
	{
		$_POST  = array_map('db_quote', $_POST);
		$query = "UPDATE ".db_table_name('quota')." 
			      SET name='{$_POST['quota_name']}', 
				  qlimit='{$_POST['quota_limit']}' 
				  WHERE id='{$_POST['quota_id']}' ";
		$connect->Execute($query) or safe_die("Error modifying quota".$connect->ErrorMsg());

		//Get the languages used in this survey
  		$langs = GetAdditionalLanguagesFromSurveyID($surveyid);
  		$baselang = GetBaseLanguageFromSurveyID($surveyid);
  		$langs[] = $baselang;
        //Iterate through each language, and make sure there is a quota message for it
		$errorstring = '';  
        foreach ($langs as $lang)
        {
          if (!$_POST['quotals_message_'.$lang]) { $errorstring.= GetLanguageNameFromCode($lang,false)."\\n";}
		}
        if ($errorstring!='') 
        {
            $databaseoutput .= "<script type=\"text/javascript\">\n<!--\n alert(\"".$clang->gT("Quota could not be added.\\n\\nIt is missing a quota message for the following languages","js").":\\n".$errorstring."\")\n //-->\n</script>\n";
        }  
		else
		//All the required quota messages exist, now we can insert this info into the database
		{
	   		require_once("../classes/inputfilter/class.inputfilter_clean.php");
		    $myFilter = new InputFilter('','',1,1,1); 
         
    		foreach ($langs as $lang) //Iterate through each language
	       	{
		     	//Clean XSS
		     	if ($filterxsshtml)
		     	{
  		 		    $_POST['quotals_message_'.$lang]=$myFilter->process($_POST['quotals_message_'.$lang]);
		     	}
                else
                {
                    $_POST['quotals_message_'.$lang] = html_entity_decode($_POST['quotals_message_'.$lang], ENT_QUOTES, "UTF-8");
                }
                
                // Fix bug with FCKEditor saving strange BR types
                $_POST['quotals_message_'.$lang]=fix_FCKeditor_text($_POST['quotals_message_'.$lang]);

				//Now save the language to the database:
                $query = "UPDATE ".db_table_name('quota_languagesettings')." 
				          SET quotals_name='".db_quote($_POST['quota_name'])."', 
						  quotals_message='".db_quote($_POST['quotals_message_'.$lang])."' 
				          WHERE quotals_quota_id = '{$_POST['quota_id']}'
						  AND quotals_language = '$lang'";
		$connect->Execute($query) or safe_die($connect->ErrorMsg());
			}
		} //End insert language based components


		$viewquota = "1";
	}
	
	if($subaction == "insertquotaanswer")
	{
		$_POST  = array_map('db_quote', $_POST);
		$query = "INSERT INTO ".db_table_name('quota_members')." (sid,qid,quota_id,code) VALUES ('$surveyid','{$_POST['quota_qid']}','{$_POST['quota_id']}','{$_POST['quota_anscode']}')";
		$connect->Execute($query) or safe_die($connect->ErrorMsg());
		$viewquota = "1";

	}

	if($subaction == "quota_delans")
	{
		$_POST  = array_map('db_quote', $_POST);
		$query = "DELETE FROM ".db_table_name('quota_members')." 
			      WHERE id = '{$_POST['quota_member_id']}' 
				  AND qid='{$_POST['quota_qid']}' and code='{$_POST['quota_anscode']}'";
		$connect->Execute($query) or safe_die($connect->ErrorMsg());
		$viewquota = "1";

	}

	if($subaction == "quota_delquota")
	{
		$_POST  = array_map('db_quote', $_POST);
		$query = "DELETE FROM ".db_table_name('quota')." WHERE id='{$_POST['quota_id']}'";
		$connect->Execute($query) or safe_die($connect->ErrorMsg());

		$query = "DELETE FROM ".db_table_name('quota_languagesettings')." WHERE quotals_quota_id='{$_POST['quota_id']}'";
		$connect->Execute($query) or safe_die($connect->ErrorMsg());
		
		$query = "DELETE FROM ".db_table_name('quota_members')." WHERE quota_id='{$_POST['quota_id']}'";
		$connect->Execute($query) or safe_die($connect->ErrorMsg());
		$viewquota = "1";
	}

	if ($subaction == "quota_editquota")
	{
		if (isset($_GET['quota_id'])) $_POST['quota_id'] = $_GET['quota_id'];
		$_POST  = array_map('db_quote', $_POST);
		
		$query = "SELECT * FROM ".db_table_name('quota')."
		          WHERE id='{$_POST['quota_id']}'";
		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
		$quotainfo = $result->FetchRow();
		
		$quotasoutput .='<form action="'.$scriptname.'" method="post">
					<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#F8F8FF">
  						<tr>
    						<td valign="top">
								<table width="100%" border="0">
        							<tbody>
          								<tr> 
            								<td colspan="2" class="header">'.$clang->gT("Modify Quota").'</td>
          								</tr>
          								<tr class="evenrow"> 
            								<td align="right"><blockquote> 
                								<p><strong>'.$clang->gT("Quota Name").':</strong></p>
              									</blockquote></td>
            								<td align="left"> <input name="quota_name" type="text" size="30" maxlength="255" value="'.$quotainfo['name'].'"></td>
          								</tr>
          								<tr class="evenrow"> 
            								<td align="right"><blockquote> 
                								<p><strong>'.$clang->gT("Quota Limit").':</strong></p>
              									</blockquote></td>
            								<td align="left"><input name="quota_limit" type="text" size="12" maxlength="8" value="'.$quotainfo['qlimit'].'"></td>
          								</tr>
          							</tbody>
          						</table>
          					</td>
          				</tr>
          			</table>';
		$langs = GetAdditionalLanguagesFromSurveyID($surveyid);
		$baselang = GetBaseLanguageFromSurveyID($surveyid);
		array_push($langs,$baselang);

		require_once("../classes/inputfilter/class.inputfilter_clean.php");
	    $myFilter = new InputFilter('','',1,1,1);

		$quotasoutput .= '
			<div class="tab-pane" id="tab-pane-quota-'.$surveyid.'">'."\n\n";
    	foreach ($langs as $lang)
    	{
    	    //Get this one
    	    $langquery = "SELECT * FROM ".db_table_name('quota_languagesettings')." WHERE quotals_quota_id='{$_POST['quota_id']}' AND quotals_language = '$lang'";
 			$langresult = db_execute_assoc($langquery) or safe_die($connect->ErrorMsg());
			$langquotainfo = $langresult->FetchRow();   	    
            $quotasoutput .= '
				<div class="tab-page">
						  	 	 <h2 class="tab">'.GetLanguageNameFromCode($lang,false);
        	if ($lang==$baselang) {$quotasoutput .= '('.$clang->gT("Base Language").')';}
        	$quotasoutput .= "</h2>";
		
			$quotasoutput.='
					<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#F8F8FF">
  						<tr>
    						<td valign="top">
								<table width="100%" border="0">
        							<tbody>
          								<tr class="evenrow">
          								    <td align="right" valign="top"><blockquote>
          								        <p><strong>'.$clang->gT("Quota message").':</strong></p>
          								        </blockquote></td>
          								    <td align="left"> <textarea name="quotals_message_'.$lang.'" cols="60" rows="6">'.$langquotainfo['quotals_message'].'</textarea></td>
										</tr>
       								</tbody>
      							</table>
    						</td>
  						</tr>
					</table>
				</div>';
		};
		$quotasoutput .= '
					<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#F8F8FF">
		 			    <tr>
		 			  	    <td valign="top">
		 			  	        <table width="100%" border="0">
		 			  	            <tbody>
          								<tr align="left" class="evenrow"> 
            								<td>&nbsp;</td>
            								<td><table width="30%"><tr><td align="left"><input name="submit" type="submit" value="'.$clang->gT("Update Quota").'" />
            								<input type="hidden" name="sid" value="'.$surveyid.'" />
            								<input type="hidden" name="action" value="quotas" />
            								<input type="hidden" name="subaction" value="modifyquota" />
            								<input type="hidden" name="quota_id" value="'.$quotainfo['id'].'" />
            								</form></td><td>
            								<form action="'.$scriptname.'" method="post">
            								<input name="submit" type="submit" id="submit" value="'.$clang->gT("Cancel").'">
            								<input type="hidden" name="sid" value="'.$surveyid.'" />
            								<input type="hidden" name="action" value="quotas" />
            								</form></td></tr></table></td>
          								</tr>
       							</tbody>
      						</table>
    					</td>
  					</tr>
				</table>';
	}
	
	$totalquotas=0;
	$totalcompleted=0;
	$csvoutput=array();
	if (($action == "quotas" && !isset($subaction)) || isset($viewquota))
	{

		$query = "SELECT * FROM ".db_table_name('quota')." , ".db_table_name('quota_languagesettings')."
		          WHERE ".db_table_name('quota').".id = ".db_table_name('quota_languagesettings').".quotals_quota_id
		          AND sid='".$surveyid."'
				  AND quotals_language = '".$baselang."'";
		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

		$quotasoutput .='<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#F8F8FF">
  						<tr>
    					<td valign="top">
						<table width="100%" border="0">
       					<tbody>
          				<tr> 
            			<td colspan="6" class="header">'.$clang->gT("Survey Quotas").'</td>
          				</tr>
          				<tr> 
            				<th width="20%">'.$clang->gT("Quota Name").'</th>
            				<th width="20%">'.$clang->gT("Status").'</th>
            				<th width="30%">'.$clang->gT("Quota Action").'</th>
            				<th width="5%">'.$clang->gT("Limit").'</th>
            				<th width="5%">'.$clang->gT("Completed").'</th>
            				<th width="20%">'.$clang->gT("Action").'</th>
          				</tr>';

		if ($result->RecordCount() > 0)
		{

			while ($quotalisting = $result->FetchRow())
			{
				$quotasoutput .='<tr>
            		<td align="center"><a name="quota_'.$quotalisting['id'].'">'.$quotalisting['name'].'</a></td>
            		<td align="center">';
				if ($quotalisting['active'] == 1)
				{
					$quotasoutput .= '<font color="#48B150">'.$clang->gT("Active").'</font>';
				} else {
					$quotasoutput .= '<font color="#B73838">'.$clang->gT("Not Active").'</font>';
				}
				$quotasoutput .='</td>
            		<td align="center">';
				if ($quotalisting['action'] == 1)
				{
					$quotasoutput .= $clang->gT("Terminate Survey");
				} elseif ($quotalisting['action'] == 2) {
					$quotasoutput .= $clang->gT("Terminate Survey With Warning");
				}
				$totalquotas+=$quotalisting['qlimit'];
				$completed=get_quotaCompletedCount($surveyid, $quotalisting['id']);
				$highlight=($completed >= $quotalisting['qlimit']) ? "" : "style='color: red'"; //Incomplete quotas displayed in red
				$totalcompleted=$totalcompleted+$completed;
			    $csvoutput[]=$quotalisting['name'].",".$quotalisting['qlimit'].",".$completed.",".($quotalisting['qlimit']-$completed)."\r\n";

				$quotasoutput .='</td>
            		<td align="center">'.$quotalisting['qlimit'].'</td>
            		<td align="center" '.$highlight.'>'.$completed.'</td>
            		<td align="center" style="padding: 3px;">
            		<table width="100%"><tr><td align="center">
            		<form action="'.$scriptname.'" method="post">
            			<input name="submit" type="submit" id="submit" value="'.$clang->gT("Modify").'">
            			<input type="hidden" name="sid" value="'.$surveyid.'" />
            			<input type="hidden" name="action" value="quotas" />
            			<input type="hidden" name="quota_id" value="'.$quotalisting['id'].'" />
            			<input type="hidden" name="subaction" value="quota_editquota" />
            		</form></td><td>
            		<form action="'.$scriptname.'" method="post">
            			<input name="submit" type="submit" id="submit" value="'.$clang->gT("Remove").'">
            			<input type="hidden" name="sid" value="'.$surveyid.'" />
            			<input type="hidden" name="action" value="quotas" />
            			<input type="hidden" name="quota_id" value="'.$quotalisting['id'].'" />
            			<input type="hidden" name="subaction" value="quota_delquota" />
            		</form></td></tr></table>
            		</td>
          		</tr>
          		<tr class="evenrow"> 
           			<td align="center">&nbsp;</td>
            		<td align="center"><strong>'.$clang->gT("Questions").'</strong></td>
            		<td align="center"><strong>'.$clang->gT("Answers").'</strong></td>
            		<td align="center">&nbsp;</td>
            		<td align="center">&nbsp;</td>
            		<td style="padding: 3px;" align="center"><form action="'.$scriptname.'" method="post">
            				<input name="submit" type="submit" id="quota_new" value="'.$clang->gT("Add Answer").'">
            				<input type="hidden" name="sid" value="'.$surveyid.'" />
            				<input type="hidden" name="action" value="quotas" />
            				<input type="hidden" name="quota_id" value="'.$quotalisting['id'].'" />
            				<input type="hidden" name="subaction" value="new_answer" /></form></td>
          		</tr>';

				$query = "SELECT id,code,qid FROM ".db_table_name('quota_members')." where quota_id='".$quotalisting['id']."'";
				$result2 = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

				if ($result2->RecordCount() > 0)
				{
					while ($quota_questions = $result2->FetchRow())
					{
						$question_answers = getQuotaAnswers($quota_questions['qid'],$surveyid,$quotalisting['id']);
						$quotasoutput .='<tr class="evenrow">
            			<td align="center">&nbsp;</td>
            			<td align="center">'.$question_answers[$quota_questions['code']]['Title'].'</td>
            			<td align="center">'.$question_answers[$quota_questions['code']]['Display'].'</td>
            			<td align="center">&nbsp;</td>
            			<td align="center">&nbsp;</td>
            			<td style="padding: 3px;" align="center">
            			<form action="'.$scriptname.'" method="post">
            				<input name="submit" type="submit" id="submit" value="'.$clang->gT("Remove").'">
            				<input type="hidden" name="sid" value="'.$surveyid.'" />
            				<input type="hidden" name="action" value="quotas" />
            				<input type="hidden" name="quota_member_id" value="'.$quota_questions['id'].'" />
            				<input type="hidden" name="quota_qid" value="'.$quota_questions['qid'].'" />
            				<input type="hidden" name="quota_anscode" value="'.$quota_questions['code'].'" />
            				<input type="hidden" name="subaction" value="quota_delans" />
            			</form>
            			</td>
          				</tr>';
					}
				}

			}




		} else
		{
			$quotasoutput .='<tr>
           	<td colspan="6" align="center">'.$clang->gT("No quotas have been set for this survey").'.</td>
         	</tr>';
		}

		$quotasoutput .='<tr>
            				<td align="center">&nbsp;</td>
            				<td align="center"><a name="quota_end">&nbsp;</a></td>
            				<td align="center">&nbsp;</td>
            				<td align="center">'.$totalquotas.'</td>
            				<td align="center">&nbsp;</td>
            				<td align="center" style="padding: 3px;"><form action="'.$scriptname.'" method="post">
            				<input name="submit" type="submit" id="quota_new" value="'.$clang->gT("Add New Quota").'">
            				<input type="hidden" name="sid" value="'.$surveyid.'" />
            				<input type="hidden" name="action" value="quotas" />
            				<input type="hidden" name="subaction" value="new_quota" /></form></td>
            				</tr>
            				<tr>
            				<td>&nbsp;</td>
            				<td align="center"><a name="quota_end">&nbsp;</a></td>
            				<td align="center">&nbsp;</td>
            				<td align="center">'.$totalquotas.'</td>
            				<td align="center">'.$totalcompleted.'</td>
            				<td align="center" style="padding: 3px;"<input type="button" value="'.$clang->gT("Quick CSV Report").'" onClick="window.open(\'admin.php?action=quotas&amp;sid='.$surveyid.'&amp;quickreport=y\', \'_top\')"></td>
          					</tr>
        					</tbody>
      						</table>
    						</td>
	 						</tr>
							</table>';
	}

    if(isset($_GET['quickreport']) && $_GET['quickreport'])
    {
        header("Content-Disposition: attachment; filename=results-survey".$surveyid.".csv");
		header("Content-type: text/comma-separated-values; charset=UTF-8");
    	echo $clang->gT("Quota Name").",".$clang->gT("Limit").",".$clang->gT("Completed").",".$clang->gT("Remaining")."\r\n";
		foreach($csvoutput as $line)
	    {
		  echo $line;
		}
	die;
	}
	if($subaction == "new_answer" || ($subaction == "new_answer_two" && !isset($_POST['quota_qid'])))
	{
		if ($subaction == "new_answer_two") $_POST['quota_id'] = $_POST['quota_id'];

		$allowed_types = "(type ='G' or type ='M' or type ='Y' or type ='A' or type ='B' or type ='I' or type = 'L' or type='O' or type='!')";
		$query = "SELECT qid, title, question FROM ".db_table_name('questions')." WHERE $allowed_types AND sid='$surveyid' AND language='{$baselang}'";
		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
		if ($result->RecordCount() == 0)
		{
			$quotasoutput .='<table width="100%" border="0">
        								<tbody>
          								<tr> 
           									<td class="header">'.$clang->gT("Add Answer").': '.$clang->gT("Question Selection").'</td>
          								</tr>
          								<tr>
          								<td align="center"><br />'.$clang->gT("Sorry there are no supported question types in this survey.").'<br /><br /></td>
          								</tr>
        								</tbody>
      								</table>';
		} else
		{
			$quotasoutput .='<form action="'.$scriptname.'" method="post">
							<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#F8F8FF">
  							<tr>
    							<td valign="top">
									<table width="100%" border="0">
        								<tbody>
          								<tr> 
           									<td colspan="2" class="header">'.$clang->gT("Survey Quota").': '.$clang->gT("Add Answer").'</td>
          								</tr>
          								<tr class="evenrow"> 
            								<td align="center">&nbsp;</td>
            								<td align="center">&nbsp;</td>
          								</tr>
          								<tr class="evenrow"> 
            								<td width="21%" align="center" valign="top"><strong>'.$clang->gT("Select Question").':</strong></td>
            								<td align="left">
            								<select name="quota_qid" size="15">';

			while ($questionlisting = $result->FetchRow())
			{
				$quotasoutput .='<option value="'.$questionlisting['qid'].'">'.$questionlisting['title'].': '.strip_tags(substr($questionlisting['question'],0,40)).'</option>';
			}


			$quotasoutput .='					</select>
             								</td>
          								</tr>
          								<tr align="left" class="evenrow"> 
            								<td colspan="2">&nbsp;</td>
          								</tr>
          								<tr align="left" class="evenrow"> 
            								<td>&nbsp;</td>
            								<td>
            									<input name="submit" type="submit" id="submit" value="'.$clang->gT("Next").'">
            									<input type="hidden" name="sid" value="'.$surveyid.'" />
            									<input type="hidden" name="action" value="quotas" />
            									<input type="hidden" name="subaction" value="new_answer_two" />
            									<input type="hidden" name="quota_id" value="'.$_POST['quota_id'].'" />
            								</td>
          								</tr>
        								</tbody>
      								</table>
    							</td>
  							</tr>
						</table>';
		}
	}

	if($subaction == "new_answer_two" && isset($_POST['quota_qid']))
	{
		$_POST  = array_map('db_quote', $_POST);

		$question_answers = getQuotaAnswers($_POST['quota_qid'],$surveyid,$_POST['quota_id']);
		$x=0;

		foreach ($question_answers as $qacheck)
		{
			if (isset($qacheck['rowexists'])) $x++;
		}

		reset($question_answers);

		if (count($question_answers) == $x)
		{
			$quotasoutput .='<table width="100%" border="0">
        								<tbody>
          								<tr> 
           									<td class="header">'.$clang->gT("Add Answer").': '.$clang->gT("Question Selection").'</td>
          								</tr>
          								<tr>
          								<td align="center"><br />'.$clang->gT("All answers are already selected in this quota.").'<br /><br /></td>
          								</tr>
        								</tbody>
      								</table>';
		} else
		{
			$quotasoutput .='<form action="'.$scriptname.'#quota_'.$_POST['quota_id'].'" method="post">
							 <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#F8F8FF">
  							<tr>
    							<td valign="top">
									<table width="100%" border="0">
        								<tbody>
          								<tr> 
           									<td colspan="2" class="header">'.$clang->gT("Survey Quota").': '.$clang->gT("Add Answer").'</td>
          								</tr>
          								<tr class="evenrow"> 
            								<td align="center">&nbsp;</td>
            								<td align="center">&nbsp;</td>
          								</tr>
          								<tr class="evenrow"> 
            								<td width="21%" align="center" valign="top"><strong>'.$clang->gT("Select Answer").':</strong></td>
            								<td align="left">
            								<select name="quota_anscode" size="15">';

			while (list($key,$value) = each($question_answers))
			{
				if (!isset($value['rowexists'])) $quotasoutput .='<option value="'.$key.'">'.strip_tags(substr($value['Display'],0,40)).'</option>';
			}


			$quotasoutput .='					</select>
             								</td>
          								</tr>
          								<tr align="left" class="evenrow"> 
            								<td colspan="2">&nbsp;</td>
          								</tr>
          								<tr align="left" class="evenrow"> 
            								<td>&nbsp;</td>
            								<td>
            									<input name="submit" type="submit" id="submit" value="'.$clang->gT("Next").'">
            									<input type="hidden" name="sid" value="'.$surveyid.'" />
            									<input type="hidden" name="action" value="quotas" />
            									<input type="hidden" name="subaction" value="insertquotaanswer" />
            									<input type="hidden" name="quota_qid" value="'.$_POST['quota_qid'].'" />
            									<input type="hidden" name="quota_id" value="'.$_POST['quota_id'].'" />
            								</td>
          								</tr>
        								</tbody>
      								</table>
    							</td>
  							</tr>
						</table>';
		}
	}

	if ($subaction == "new_quota")
	{
		$quotasoutput .='<form action="'.$scriptname.'" method="post" name="addnewquotaform">';
		$quotasoutput.='					<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#F8F8FF">
  						<tr>
    						<td valign="top">
								<table width="100%" border="0">
        							<tbody>
          								<tr> 
            								<td colspan="2" class="header">'.$clang->gT("New Quota").'</td>
          								</tr>
          								<tr class="evenrow"> 
            								<td align="right"><blockquote> 
                								<p><strong>'.$clang->gT("Quota Name").':</strong></p>
              									</blockquote></td>
            								<td align="left"> <input name="quota_name" type="text" size="30" maxlength="255" /></td>
          								</tr>
          								<tr class="evenrow"> 
            								<td align="right"><blockquote> 
                								<p><strong>'.$clang->gT("Quota Limit").':</strong></p>
              									</blockquote></td>
            								<td align="left"><input name="quota_limit" type="text" size="12" maxlength="8" /></td>
          								</tr>
          							</tbody>
          						</table>
            								</td>
          				</tr>
					</table>
					';
            								
		$langs = GetAdditionalLanguagesFromSurveyID($surveyid);
		$baselang = GetBaseLanguageFromSurveyID($surveyid);
		array_push($langs,$baselang);

		require_once("../classes/inputfilter/class.inputfilter_clean.php");
	    $myFilter = new InputFilter('','',1,1,1);

		$thissurvey=getSurveyInfo($surveyid);

		$quotasoutput .= '
            <div class="tab-pane" id="tab-pane-quota-'.$surveyid.'">'."\n\n";        
        foreach ($langs as $lang)
    	{
            $quotasoutput .= '
				<div class="tab-page">
						  	 	 <h2 class="tab">'.GetLanguageNameFromCode($lang,false);
        	if ($lang==$baselang) {$quotasoutput .= '('.$clang->gT("Base Language").')';}
        	$quotasoutput .= "</h2>";
			$quotasoutput.='
					<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#F8F8FF">
  						<tr>
    						<td valign="top">
								<table width="100%" border="0">
        							<tbody>
          								<tr class="evenrow">
          								    <td align="right" valign="top"><blockquote>
          								        <p><strong>'.$clang->gT("Quota message").':</strong></p>
          								        </blockquote></td>
          								    <td align="left"> <textarea name="quotals_message_'.$lang.'" cols="60" rows="6">'.$clang->gT("Sorry your responses have exceeded a quota on this survey.").'</textarea></td>
          								</tr>
       							</tbody>
      						</table>
    					</td>
  					</tr>
					</table>
				</div>';
		};
	
		$quotasoutput .= '
				<input type="hidden" name="sid" value="'.$surveyid.'" />
				<input type="hidden" name="action" value="quotas" />
				<input type="hidden" name="subaction" value="insertquota" />
				</div>
				<input name="submit" type="submit" value="'.$clang->gT("Add New Quota").'" />
			</form>';
	}
}

?>
