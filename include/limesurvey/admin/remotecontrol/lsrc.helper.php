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
 * $Id: lsrc.helper.php 7431 2009-08-10 15:40:51Z c_schmitz $
 *
 */
/**
 * @author Wahrendorff
 *
 */
class LsrcHelper {
	
	
	/**
	 * simple debug function to make life a bit easier
	 *
	 * @param string $text
	 */
	function debugLsrc($text)
	{
		include("lsrc.config.php");
		if($lsrcDebug)
		{
			error_log("\n".date("Y-m-d H:i:s")." ".$text, 3 , $lsrcDebugLog);
		}
		return;
	}

	/**
	 * function to get the id of the surveyowner
	 *
	 * @param unknown_type $iVid
	 * @return unknown
	 */
	function getSurveyOwner($iVid)
	{
		global $connect ;
		//		global $dbprefix ;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		include("lsrc.config.php");
		$lsrcHelper= new LsrcHelper();
		if($lsrcHelper->surveyExists($iVid))
		{
			$query2num = "SELECT owner_id FROM {$dbprefix}surveys WHERE sid=".sanitize_int($iVid)."";
			$rs = db_execute_assoc($query2num);
			$field=$rs->FetchRow(); 
  			return $field['owner_id']; 
				
		}else{return false;}
	}

	/**
	 * This function changes data in LS-DB, its very sensitive, because every table can be changed.
	 *
	 * @param unknown_type $table
	 * @param unknown_type $key
	 * @param unknown_type $value
	 * @param unknown_type $where
	 * @return String
	 */
	function changeTable($table, $key, $value, $where, $mode='0')//XXX
	{//be aware that this function may be a security risk

		global $connect ;
		//		global $dbprefix ;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		include("lsrc.config.php");
		if($mode=='' || !isset($mode) || $mode=='0')
		{
			$where = str_replace("\\","",$where);
			$query2num = "SELECT {$key} FROM {$dbprefix}{$table} WHERE {$where}";
			$rs = db_execute_assoc($query2num);
				
			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ($query2num)");

			$query2update = "update ".$dbprefix.$table." set ".$key."='".$value."' where ".$where."";
				
			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ($query2update)");
				
			if($connect->Execute($query2update)){
				return $rs->RecordCount()." Rows changed";
			}
			else{
				return "nothing changed";
			}
		}
		if($mode==1 || $mode=='1')
		{
			$query2insert = "INSERT INTO {$dbprefix}{$table} ({$key}) VALUES ({$value});";
			$this->debugLsrc("wir sind in Line ".__LINE__.", inserting ($query2insert)");
			if($connect->Execute($query2insert))
			{
				$this->debugLsrc("wir sind in Line ".__LINE__.", inserting OK");
				return true;
					
			}
			else
			{
				return false;
			}
		}


	}

	/**
	 *
	 * Enter description here...
	 * @param $surveyid
	 * @param $type
	 * @param $maxLsrcEmails
	 * @return unknown_type
	 */
	function emailSender($surveyid, $type, $maxLsrcEmails='') //XXX
	{
		global $connect,$sitename ;
		global $dbprefix ;
		$surveyid = sanitize_int($surveyid);
		include("lsrc.config.php");
		$lsrcHelper= new LsrcHelper();


		// wenn maxmails ber den lsrc gegeben wird das nutzen, ansonsten die default werte aus der config.php
		if($maxLsrcEmails!='')
		$maxemails = $maxLsrcEmails;

		switch ($type){
			case "custom":

				break;
			case "invite":
				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", START invite ");



				if(isset($surveyid) && getEmailFormat($surveyid) == 'html')
				{
					$ishtml=true;
				}
				else
				{
					$ishtml=false;
				}

				//$tokenoutput .= ("Sending Invitations");
				//if (isset($tokenid)) {$tokenoutput .= " (".("Sending to Token ID").":&nbsp;{$tokenid})";}
				//$tokenoutput .= "\n";
				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", $surveyid, $type");
				// Texte für Mails aus der Datenbank holen und in die POST Dinger schreiben. Nicht schön aber praktikabel

				$sql = 	"SELECT surveyls_language, surveyls_email_invite_subj, surveyls_email_invite  ".
						"FROM {$dbprefix}surveys_languagesettings ".
						"WHERE surveyls_survey_id = ".$surveyid." ";

				//GET SURVEY DETAILS
				$thissurvey=getSurveyInfo($surveyid);

				//				$connect->SetFetchMode(ADODB_FETCH_ASSOC);
				//				$sqlResult=$connect->Execute($sql);
				$sqlResult = db_execute_assoc($sql);

				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", invite ");

				while($languageRow = $sqlResult->FetchRow())
				{
					$_POST['message_'.$languageRow['surveyls_language']] = $languageRow['surveyls_email_invite'];
					$_POST['subject_'.$languageRow['surveyls_language']] = $languageRow['surveyls_email_invite_subj'];
				}

				//				if (isset($_POST['bypassbademails']) && $_POST['bypassbademails'] == 'Y')
				//				{
				//					$SQLemailstatuscondition = " AND emailstatus = 'OK'";
				//				}
				//				else
				//				{
				//					$SQLemailstatuscondition = "";
				//				}
				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", invite ");
				$ctquery = "SELECT * FROM ".db_table_name("tokens_{$surveyid}")." WHERE ((completed ='N') or (completed='')) AND ((sent ='N') or (sent='')) AND token !='' AND email != '' ";

				if (isset($tokenid)) {$ctquery .= " AND tid='{$tokenid}'";}
				//$tokenoutput .= "<!-- ctquery: $ctquery -->\n";
				$ctresult = $connect->Execute($ctquery);
				$ctcount = $ctresult->RecordCount();
				$ctfieldcount = $ctresult->FieldCount();

				$emquery = "SELECT * ";
				//if ($ctfieldcount > 7) {$emquery .= ", attribute_1, attribute_2";}

				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", invite ");

				$emquery .= " FROM ".db_table_name("tokens_{$surveyid}")." WHERE ((completed ='N') or (completed='')) AND ((sent ='N') or (sent='')) AND token !='' AND email != '' ";

				if (isset($tokenid)) {$emquery .= " and tid='{$tokenid}'";}
				//$tokenoutput .= "\n\n<!-- emquery: $emquery -->\n\n";
				$emresult = db_select_limit_assoc($emquery,$maxemails);
				$emcount = $emresult->RecordCount();

				//$tokenoutput .= "<table width='500px' align='center' >\n"
				////."\t<tr>\n"
				//."\t\t<td><font size='1'>\n";

				$surveylangs = GetAdditionalLanguagesFromSurveyID($surveyid);
				$baselanguage = GetBaseLanguageFromSurveyID($surveyid);
				array_unshift($surveylangs,$baselanguage);

				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", invite ");

				foreach ($surveylangs as $language)
				{
					$_POST['message_'.$language]=auto_unescape($_POST['message_'.$language]);
					$_POST['subject_'.$language]=auto_unescape($_POST['subject_'.$language]);
					if ($ishtml) $_POST['message_'.$language] = html_entity_decode($_POST['message_'.$language], ENT_QUOTES, $emailcharset);

				}

				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", invite ");
				if ($emcount > 0)
				{
					$mailsSend = 0;
					while ($emrow = $emresult->FetchRow())
					{
						$c=1;
						unset($fieldsarray);
						$to = $emrow['email'];
						$fieldsarray["{EMAIL}"]=$emrow['email'];
						$fieldsarray["{FIRSTNAME}"]=$emrow['firstname'];
						$fieldsarray["{LASTNAME}"]=$emrow['lastname'];
						$fieldsarray["{TOKEN}"]=$emrow['token'];
						$fieldsarray["{LANGUAGE}"]=$emrow['language'];
						while(isset($emrow["attribute_$c"]))
						{
							$fieldsarray["{ATTRIBUTE_$c}"]=$emrow["attribute_$c"];
							++$c;
						}
						$fieldsarray["{ADMINNAME}"]= $thissurvey['adminname'];
						$fieldsarray["{ADMINEMAIL}"]=$thissurvey['adminemail'];
						$fieldsarray["{SURVEYNAME}"]=$thissurvey['name'];
						$fieldsarray["{SURVEYDESCRIPTION}"]=$thissurvey['description'];
						$fieldsarray["{EXPIRY}"]=$thissurvey["expiry"];
						$fieldsarray["{EXPIRY-DMY}"]=date("d-m-Y",strtotime($thissurvey["expiry"]));
						$fieldsarray["{EXPIRY-MDY}"]=date("m-d-Y",strtotime($thissurvey["expiry"]));

						$emrow['language']=trim($emrow['language']);
						if ($emrow['language']=='') {$emrow['language']=$baselanguage;} //if language is not give use default
						$found = array_search($emrow['language'], $surveylangs);
						if ($found==false) {$emrow['language']=$baselanguage;}

						$from = $thissurvey['adminemail'];


						if ($ishtml === false)
						{
							if ( $modrewrite )
							{
								$fieldsarray["{SURVEYURL}"]="$publicurl/$surveyid/lang-".trim($emrow['language'])."/tk-{$emrow['token']}";
							}
							else
							{
								$fieldsarray["{SURVEYURL}"]="$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}";
							}
						}
						else
						{
							if ( $modrewrite )
							{
								$fieldsarray["{SURVEYURL}"]="<a href='$publicurl/$surveyid/lang-".trim($emrow['language'])."/tk-{$emrow['token']}'>".htmlspecialchars("$publicurl/$surveyid/lang-".trim($emrow['language'])."/tk-{$emrow['token']}")."</a>";
							}
							else
							{
								$fieldsarray["{SURVEYURL}"]="<a href='$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}'>".htmlspecialchars("$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}")."</a>";
							}
						}
						$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", invite ");
						$modsubject=Replacefields($_POST['subject_'.$emrow['language']], $fieldsarray);
						$modmessage=Replacefields($_POST['message_'.$emrow['language']], $fieldsarray);

						if (MailTextMessage($modmessage, $modsubject, $to , $from, $sitename, $ishtml, getBounceEmail($surveyid)))
						{
							// Put date into sent
							$timeadjust = 0;
							$today = date("Y-m-d H:i");
							$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", invite Today:".$today);
							$udequery = "UPDATE ".db_table_name("tokens_{$surveyid}")."\n"
							."SET sent='$today' WHERE tid={$emrow['tid']}";
							//
							$uderesult = $connect->Execute($udequery);
							$mailsSend++;
							//$tokenoutput .= "[".("Invitation sent to:")."{$emrow['firstname']} {$emrow['lastname']} ($to)]\n";
						}
						else
						{
							//$tokenoutput .= ReplaceFields(("Email to {FIRSTNAME} {LASTNAME} ({EMAIL}) failed. Error Message:")." ".$maildebug."", $fieldsarray);
							if($n==1)
							$failedAddresses .= ",".$to;
							else
							{
								$failedAddresses = $to;
								$n=1;
							}
						}
					}
					$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", invite ");
					if ($ctcount > $emcount)
					{
						$lefttosend = $ctcount-$maxemails;

					}else{$lefttosend = 0;}
				}
				else
				{
					return "No Mails to send";
				}
					
				if($maxemails>0 && $maxemails!='')
				{
					$returnValue = "".$mailsSend." Mails send. ".$lefttosend." Mails left to send";
					if(isset($failedAddresses))
					$returnValue .= "\nCould not send to: ".$failedAddresses;
					return $returnValue;
				}

				if(isset($mailsSend))
				{
					$returnValue = "".$mailsSend." Mails send. ";
					if(isset($failedAddresses))
					$returnValue .= "\nCould not send to: ".$failedAddresses;
					return $returnValue;
				}

					


				break;
			case "remind":
				// XXX:
				// TODO:
				//				if (!isset($_POST['ok']) || !$_POST['ok'])
				//				{

				/*
				 * look if there were reminders send in the past, and if some tokens got lesser reminders than others
				 *
				 * - if so: send reminders to the unremindet participants until they got the same remindcount than the others
				 * - if not: send reminders normally
				 */
					
				$remSQL = "SELECT tid, remindercount "
				. "FROM ".db_table_name("tokens_{$surveyid}")." "
				. "WHERE (completed = 'N' or completed = '') AND sent <> 'N' and sent <>'' AND token <>'' AND EMAIL <>'' "
				. "ORDER BY remindercount desc LIMIT 1";
				$remResult = db_execute_assoc($remSQL);
				$remRow = $remResult->FetchRow();
					
				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", remind ".$remRow['tid']."; ".$remRow['remindercount']." ");
					
				$sendOnlySQL = "SELECT tid, remindercount "
				. "FROM ".db_table_name("tokens_{$surveyid}")." "
				. "WHERE (completed = 'N' or completed = '') AND sent <> 'N' and sent <>'' AND token <>'' AND EMAIL <>'' AND remindercount < ".$remRow['remindercount']." "
				. "ORDER BY tid asc LIMIT 1";
				$sendOnlyResult = db_execute_assoc($sendOnlySQL);
					
					
					
				if($sendOnlyResult->RecordCount()>0)
				{
					$sendOnlyRow = $sendOnlyResult->FetchRow();
					$starttokenid = $sendOnlyRow['tid'];
					$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", remind ".$sendOnlyRow['tid']."; ".$sendOnlyRow['remindercount']." ");
				}
					
				if(isset($surveyid) && getEmailFormat($surveyid) == 'html')
				{
					$ishtml=true;
				}
				else
				{
					$ishtml=false;
				}
					
				//GET SURVEY DETAILS
				$thissurvey=getSurveyInfo($surveyid);
					
				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", $surveyid, $type");
				// Texte für Mails aus der Datenbank holen.
					
				$sql = 	"SELECT surveyls_language, surveyls_email_remind_subj, surveyls_email_remind  ".
							"FROM {$dbprefix}surveys_languagesettings ".
							"WHERE surveyls_survey_id = ".$surveyid." ";

				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", remind ");
					
				$sqlResult = db_execute_assoc($sql);
					
				while($languageRow = $sqlResult->FetchRow())
				{
					$_POST['message_'.$languageRow['surveyls_language']] = $languageRow['surveyls_email_remind'];
					$_POST['subject_'.$languageRow['surveyls_language']] = $languageRow['surveyls_email_remind_subj'];
				}
				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", remind ");
				//$tokenoutput .= ("Sending Reminders")."\n";
					
				$surveylangs = GetAdditionalLanguagesFromSurveyID($surveyid);
				$baselanguage = GetBaseLanguageFromSurveyID($surveyid);
				array_unshift($surveylangs,$baselanguage);
					
				foreach ($surveylangs as $language)
				{
					$_POST['message_'.$language]=auto_unescape($_POST['message_'.$language]);
					$_POST['subject_'.$language]=auto_unescape($_POST['subject_'.$language]);

				}
				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", remind ");	
				$SQLemailstatuscondition = " AND emailstatus = 'OK'";

				if (isset($_POST['maxremindercount']) &&
				$_POST['maxremindercount'] != '' &&
				intval($_POST['maxremindercount']) != 0)
				{
					$SQLremindercountcondition = " AND remindercount < ".intval($_POST['maxremindercount']);
				}
				else
				{
					$SQLremindercountcondition = "";
				}
				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", remind ");	
				if (isset($_POST['minreminderdelay']) &&
				$_POST['minreminderdelay'] != '' &&
				intval($_POST['minreminderdelay']) != 0)
				{
					// $_POST['minreminderdelay'] in days (86400 seconds per day)
					$compareddate = date_shift(
					date("Y-m-d H:i:s",time() - 86400 * intval($_POST['minreminderdelay'])),
								"Y-m-d H:i",
					$timeadjust);
					$SQLreminderdelaycondition = " AND ( "
					. " (remindersent = 'N' AND sent < '".$compareddate."') "
					. " OR "
					. " (remindersent < '".$compareddate."'))";
				}
				else
				{
					$SQLreminderdelaycondition = "";
				}
					$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", remind ");	
				
				$ctquery = "SELECT * FROM ".db_table_name("tokens_{$surveyid}")." WHERE (completed ='N' or completed ='') AND sent<>'' AND sent<>'N' AND token <>'' AND email <> '' $SQLemailstatuscondition $SQLremindercountcondition $SQLreminderdelaycondition";
					
				if (isset($starttokenid)) {$ctquery .= " AND tid >= '{$starttokenid}'";}
				//					if (isset($tokenid) && $tokenid) {$ctquery .= " AND tid = '{$tokenid}'";}
				//					//$tokenoutput .= "<!-- ctquery: $ctquery -->\n";

				$ctresult = $connect->Execute($ctquery) or $this->debugLsrc ("Database error!\n" . $connect->ErrorMsg());
				$ctcount = $ctresult->RecordCount();
				$ctfieldcount = $ctresult->FieldCount();
				$emquery = "SELECT firstname, lastname, email, token, tid, language ";
				if ($ctfieldcount > 7) {$emquery .= ", attribute_1, attribute_2";}
				
					$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", remind ");	
					
				// TLR change to put date into sent
				$emquery .= " FROM ".db_table_name("tokens_{$surveyid}")." WHERE (completed = 'N' or completed = '') AND sent <> 'N' and sent <>'' AND token <>'' AND EMAIL <>'' $SQLemailstatuscondition $SQLremindercountcondition $SQLreminderdelaycondition";
					
				if (isset($starttokenid)) {$emquery .= " AND tid >= '{$starttokenid}'";}
				if (isset($tokenid) && $tokenid) {$emquery .= " AND tid = '{$tokenid}'";}
				$emquery .= " ORDER BY tid ";
				
					$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", remind, maxemails?: $maxemails, emquery: $emquery ");
				
				//$emresult = db_select_limit_assoc($emquery, $maxemails) or $this->debugLsrc ("Database error!\n" . $connect->ErrorMsg());
				$emresult = db_execute_assoc($emquery);
				$emcount = $emresult->RecordCount() or $this->debugLsrc ("Database error!\n" . $connect->ErrorMsg());
				
					$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", remind ");
				
				if ($emcount > 0)
				{
					while ($emrow = $emresult->FetchRow())
					{
						$c=1;
						unset($fieldsarray);
						$to = $emrow['email'];
						$fieldsarray["{EMAIL}"]=$emrow['email'];
						$fieldsarray["{FIRSTNAME}"]=$emrow['firstname'];
						$fieldsarray["{LASTNAME}"]=$emrow['lastname'];
						$fieldsarray["{TOKEN}"]=$emrow['token'];
						$fieldsarray["{LANGUAGE}"]=$emrow['language'];
						while(isset($emrow["attribute_$c"]))
						{
							$fieldsarray["{ATTRIBUTE_$c}"]=$emrow["attribute_$c"];
							++$c;
						}
						
						$fieldsarray["{ADMINNAME}"]= $thissurvey['adminname'];
						$fieldsarray["{ADMINEMAIL}"]=$thissurvey['adminemail'];
						$fieldsarray["{SURVEYNAME}"]=$thissurvey['name'];
						$fieldsarray["{SURVEYDESCRIPTION}"]=$thissurvey['description'];
						$fieldsarray["{EXPIRY}"]=$thissurvey["expiry"];
						$fieldsarray["{EXPIRY-DMY}"]=date("d-m-Y",strtotime($thissurvey["expiry"]));
						$fieldsarray["{EXPIRY-MDY}"]=date("m-d-Y",strtotime($thissurvey["expiry"]));
							
						$emrow['language']=trim($emrow['language']);
						if ($emrow['language']=='') {$emrow['language']=$baselanguage;} //if language is not give use default
						if(!in_array($emrow['language'], $surveylangs)) {$emrow['language']=$baselanguage;} // if given language is not available use default
						$found = array_search($emrow['language'], $surveylangs);
						if ($found==false) {$emrow['language']=$baselanguage;}
						
						 $from = $thissurvey['adminemail']; //$from = $_POST['from_'.$emrow['language']];
						
							
						if (getEmailFormat($surveyid) == 'html')
						{
							$ishtml=true;
						}
						else
						{
							$ishtml=false;
						}
							
						if ($ishtml == false)
						{
							if ( $modrewrite )
							{
								$fieldsarray["{SURVEYURL}"]="$publicurl/$surveyid/lang-".trim($emrow['language'])."/tk-{$emrow['token']}";
							}
							else
							{
								$fieldsarray["{SURVEYURL}"]="$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}";
							}
						}
						else
						{
							if ( $modrewrite )
							{
								$fieldsarray["{SURVEYURL}"]="<a href='$publicurl/$surveyid/lang-".trim($emrow['language'])."/tk-{$emrow['token']}'>".htmlspecialchars("$publicurl/$surveyid/lang-".trim($emrow['language'])."/tk-{$emrow['token']}")."</a>";
							}
							else
							{
								$fieldsarray["{SURVEYURL}"]="<a href='$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}'>".htmlspecialchars("$publicurl/index.php?lang=".trim($emrow['language'])."&sid=$surveyid&token={$emrow['token']}")."</a>";
								$_POST['message_'.$emrow['language']] = html_entity_decode($_POST['message_'.$emrow['language']], ENT_QUOTES, $emailcharset);
							}
						}
							
						$msgsubject=Replacefields($_POST['subject_'.$emrow['language']], $fieldsarray);
						$sendmessage=Replacefields($_POST['message_'.$emrow['language']], $fieldsarray);

						if (MailTextMessage($sendmessage, $msgsubject, $to, $from, $sitename, $ishtml, getBounceEmail($surveyid)))
						{
								
							// Put date into remindersent
							$today = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i", $timeadjust);
							$udequery = "UPDATE ".db_table_name("tokens_{$surveyid}")."\n"
							."SET remindersent='$today',remindercount = remindercount+1  WHERE tid={$emrow['tid']}";
							//
							$uderesult = $connect->Execute($udequery) or $this->debugLsrc ("Could not update tokens$udequery".$connect->ErrorMsg());
							//orig: $tokenoutput .= "\t\t\t({$emrow['tid']})[".("Reminder sent to:")." {$emrow['firstname']} {$emrow['lastname']}]\n";
							//$tokenoutput .= "\t\t\t({$emrow['tid']}) [".("Reminder sent to:")." {$emrow['firstname']} {$emrow['lastname']} ($to)]\n";
							$mailsSend++;
						}
						else
						{
							//$tokenoutput .= ReplaceFields(("Email to {FIRSTNAME} {LASTNAME} ({EMAIL}) failed. Error Message:")." ".$maildebug."", $fieldsarray);
							if($n==1)
							$failedAddresses .= ",".$to;
							else
							{
								$failedAddresses = $to;
								$n=1;
							}

						}
						//$lasttid = $emrow['tid'];
							
					}
					if ($ctcount > $emcount)
					{
						$lefttosend = $ctcount-$maxemails;
					}else{$lefttosend = 0;}
				}
				else
				{
					return "No Reminders to send";
				}
					
				if($maxemails>0)
				{
					$returnValue = "".$mailsSend." Reminders send. ".$lefttosend." Reminders left to send";
					if(isset($failedAddresses))
					$returnValue .= "\nCould not send to: ".$failedAddresses;
					return $returnValue;
				}
					
				if(isset($mailsSend))
				{
					$returnValue = "".$mailsSend." Reminders send. ";
					if(isset($failedAddresses))
					$returnValue .= "\nCould not send to: ".$failedAddresses;
					return $returnValue;
				}


				break;
			default:

				break;
		}
	}

	/**
	 * loginCheck for Lsrc, checks if the user with given password exists in LS Database and
	 * sets the SESSION rights for this user
	 * @param String $sUser
	 * @param String $sPass
	 * @return boolean
	 */
	function checkUser($sUser, $sPass) // XXX
	{
		global $connect ;
		global $dbprefix ;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		include("lsrc.config.php");
		require(dirname(__FILE__)."/../classes/core/sha256.php");

		$query="SELECT uid, password, lang, superadmin FROM {$dbprefix}users WHERE users_name=".$connect->qstr(sanitize_user($sUser));
		// echo $query;
		$result = db_execute_assoc($query);
		$gv = $result->FetchRow();
		if($result->RecordCount() < 1)
		{
			return false;
		}
		else
		{
			if((SHA256::hashing($sPass)==$gv['password']))
			{
				$_SESSION['loginID']=$gv['uid'];
				$_SESSION['lang']=$gv['lang'];

				$squery = "SELECT create_survey, configurator, create_user, delete_user, superadmin, manage_template, manage_label FROM {$dbprefix}users WHERE uid={$gv['uid']}";
				$sresult = db_execute_assoc($squery); //Checked
				if ($sresult->RecordCount()>0)
				{
					$fields = $sresult->FetchRow();
					$_SESSION['USER_RIGHT_CREATE_SURVEY'] = $fields['create_survey'];
					$_SESSION['USER_RIGHT_CONFIGURATOR'] = $fields['configurator'];
					$_SESSION['USER_RIGHT_CREATE_USER'] = $fields['create_user'];
					$_SESSION['USER_RIGHT_DELETE_USER'] = $fields['delete_user'];
					$_SESSION['USER_RIGHT_SUPERADMIN'] = $fields['superadmin'];
					$_SESSION['USER_RIGHT_MANAGE_TEMPLATE'] = $fields['manage_template'];
					$_SESSION['USER_RIGHT_MANAGE_LABEL'] = $fields['manage_label'];
				}
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Lsrc checks the existence of Surveys more than one time, so this makes sense to be DRY
	 *
	 * @param int $sid
	 * @return boolean
	 */
	function surveyExists($sid)//XXX
	{
		global $connect ;
		//		global $dbprefix ;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		include("lsrc.config.php");

		$query="SELECT * FROM {$dbprefix}surveys WHERE sid = ".$sid;
		// echo $query;
		$result = db_execute_assoc($query);
		if($result->RecordCount() < 1)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	 
	/**
	 * function to import surveys, based on new importsurvey.php 6979 2009-05-30 11:59:03Z c_schmitz $
	 *
	 * @param unknown_type $iVid
	 * @param unknown_type $sVtit
	 * @param unknown_type $sVbes
	 * @return boolean
	 */
	function importSurvey($iVid, $sVtit , $sVbes, $sVwel, $sUbes, $sVtyp) //XXX
	{
		global $connect ;
		//		global $dbprefix ;

		include("lsrc.config.php");
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
		// HINT FOR IMPORTERS: go to Line 714 to manipulate the Survey, while it's imported

		$the_full_file_path = $coreDir.$sVtyp.".csv";

		$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.",the_full_file_path ='$the_full_file_path' OK ");
		//$_SERVER['SERVER_NAME'] = "";				// just to avoid notices
		//$_SERVER['SERVER_SOFTWARE'] = "";		// just to avoid notices
		//require_once(dirname(__FILE__).'/../config-defaults.php');
		//require_once(dirname(__FILE__).'/../common.php');


		$handle = fopen($the_full_file_path, "r");
		while (!feof($handle))
		{
			//To allow for very long survey lines (up to 64k)
			$buffer = fgets($handle, 56550);
			$bigarray[] = $buffer;
		}
		fclose($handle);
//		foreach($bigarray as $ba)
//			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ".$ba);
			
		if (isset($bigarray[0])) $bigarray[0]=$this->removeBOM($bigarray[0]);
		// Now we try to determine the dataformat of the survey file.

		if (isset($bigarray[1]) && isset($bigarray[4])&& (substr($bigarray[1], 0, 22) == "# SURVEYOR SURVEY DUMP")&& (substr($bigarray[4], 0, 29) == "# http://www.phpsurveyor.org/"))
		{
			$importversion = 100;  // version 1.0 file
		}
		elseif
		(isset($bigarray[1]) && isset($bigarray[4])&& (substr($bigarray[1], 0, 22) == "# SURVEYOR SURVEY DUMP")&& (substr($bigarray[4], 0, 37) == "# http://phpsurveyor.sourceforge.net/"))
		{
			$importversion = 99;  // Version 0.99 file or older - carries a different URL
		}
		elseif
		(substr($bigarray[0], 0, 24) == "# LimeSurvey Survey Dump" || substr($bigarray[0], 0, 25) == "# PHPSurveyor Survey Dump")
		{  // Wow.. this seems to be a >1.0 version file - these files carry the version information to read in line two
			$importversion=substr($bigarray[1], 12, 3);
		}
		else    // unknown file - show error message
		{
			if ($importingfrom == "http")
			{
				//			    $importsurvey .= "<strong><font color='red'>".("Error")."</font></strong>\n";
				//			  	$importsurvey .= ("This file is not a LimeSurvey survey file. Import failed.")."\n";
				//			  	$importsurvey .= "</font></td></tr></table>\n";
				//			  	$importsurvey .= "</body>\n</html>\n";
				//unlink($the_full_file_path);
				return false;
			}
			else
			{
				//echo ("This file is not a LimeSurvey survey file. Import failed.")."\n";
				return false;
			}
		}


		// okay.. now lets drop the first 9 lines and get to the data
		// This works for all versions
		for ($i=0; $i<9; $i++)
		{
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ".print_r($bigarray));


		//SURVEYS
		if (array_search("# GROUPS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# GROUPS TABLE\n", $bigarray);
		}
		elseif (array_search("# GROUPS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# GROUPS TABLE\r\n", $bigarray);
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$surveyarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//GROUPS
		if (array_search("# QUESTIONS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# QUESTIONS TABLE\n", $bigarray);
		}
		elseif (array_search("# QUESTIONS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# QUESTIONS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$grouparray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//QUESTIONS
		if (array_search("# ANSWERS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# ANSWERS TABLE\n", $bigarray);
		}
		elseif (array_search("# ANSWERS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# ANSWERS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2)
			{
				$questionarray[] = $bigarray[$i];
			}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//ANSWERS
		if (array_search("# CONDITIONS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# CONDITIONS TABLE\n", $bigarray);
		}
		elseif (array_search("# CONDITIONS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# CONDITIONS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2)
			{
				$answerarray[] = str_replace("`default`", "`default_value`", $bigarray[$i]);
			}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//CONDITIONS
		if (array_search("# LABELSETS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# LABELSETS TABLE\n", $bigarray);
		}
		elseif (array_search("# LABELSETS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# LABELSETS TABLE\r\n", $bigarray);
		}
		else
		{ //There is no labelsets information, so presumably this is a pre-0.98rc3 survey.
			$stoppoint = count($bigarray);
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$conditionsarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//LABELSETS
		if (array_search("# LABELS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# LABELS TABLE\n", $bigarray);
		}
		elseif (array_search("# LABELS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# LABELS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$labelsetsarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//LABELS
		if (array_search("# QUESTION_ATTRIBUTES TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# QUESTION_ATTRIBUTES TABLE\n", $bigarray);
		}
		elseif (array_search("# QUESTION_ATTRIBUTES TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# QUESTION_ATTRIBUTES TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}

		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$labelsarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//QUESTION_ATTRIBUTES
		if (array_search("# ASSESSMENTS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# ASSESSMENTS TABLE\n", $bigarray);
		}
		elseif (array_search("# ASSESSMENTS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# ASSESSMENTS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$question_attributesarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);


		//ASSESSMENTS
		if (array_search("# SURVEYS_LANGUAGESETTINGS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# SURVEYS_LANGUAGESETTINGS TABLE\n", $bigarray);
		}
		elseif (array_search("# SURVEYS_LANGUAGESETTINGS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# SURVEYS_LANGUAGESETTINGS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			//	if ($i<$stoppoint-2 || $i==count($bigarray)-1)
			if ($i<$stoppoint-2)
			{
				$assessmentsarray[] = $bigarray[$i];
			}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//LANGAUGE SETTINGS
		if (array_search("# QUOTA TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# QUOTA TABLE\n", $bigarray);
		}
		elseif (array_search("# QUOTA TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# QUOTA TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			//	if ($i<$stoppoint-2 || $i==count($bigarray)-1)
			//$bigarray[$i]=        trim($bigarray[$i]);
			if (isset($bigarray[$i]) && (trim($bigarray[$i])!=''))
			{
				if (strpos($bigarray[$i],"#")===0)
				{
					unset($bigarray[$i]);
					unset($bigarray[$i+1]);
					unset($bigarray[$i+2]);
					break ;
				}
				else
				{
					$surveylsarray[] = $bigarray[$i];
				}
			}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//QUOTA
		if (array_search("# QUOTA_MEMBERS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# QUOTA_MEMBERS TABLE\n", $bigarray);
		}
		elseif (array_search("# QUOTA_MEMBERS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# QUOTA_MEMBERS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			//	if ($i<$stoppoint-2 || $i==count($bigarray)-1)
			if ($i<$stoppoint-2)
			{
				$quotaarray[] = $bigarray[$i];
			}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//Survey Language Settings
		$stoppoint = count($bigarray)-1;
		for ($i=0; $i<$stoppoint-1; $i++)
		{
			if ($i<=$stoppoint) {$quotamembersarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		if (isset($surveyarray)) {$countsurveys = count($surveyarray);} else {$countsurveys = 0;}
		if (isset($surveylsarray)) {$countlanguages = count($surveylsarray)-1;} else {$countlanguages = 1;}
		if (isset($grouparray)) {$countgroups = count($grouparray);} else {$countgroups = 0;}
		if (isset($questionarray)) {$countquestions = count($questionarray);} else {$countquestions=0;}
		if (isset($answerarray)) {$countanswers = count($answerarray);} else {$countanswers=0;}
		if (isset($conditionsarray)) {$countconditions = count($conditionsarray);} else {$countconditions=0;}
		if (isset($labelsetsarray)) {$countlabelsets = count($labelsetsarray);} else {$countlabelsets=0;}
		if (isset($question_attributesarray)) {$countquestion_attributes = count($question_attributesarray);} else {$countquestion_attributes=0;}
		if (isset($assessmentsarray)) {$countassessments=count($assessmentsarray);} else {$countassessments=0;}
		if (isset($quotaarray)) {$countquota=count($quotaarray);} else {$countquota=0;}

		// CREATE SURVEY

		if ($importversion>=111)
		{
			if ($countsurveys>0){$countsurveys--;};
			if ($countanswers>0){$countanswers=($countanswers-1)/$countlanguages;};
			if ($countgroups>0){$countgroups=($countgroups-1)/$countlanguages;};
			if ($countquestions>0){$countquestions=($countquestions-1)/$countlanguages;};
			if ($countassessments>0){$countassessments--;};
			if ($countconditions>0){$countconditions--;};
			if ($countlabelsets>0){$countlabelsets--;};
			if ($countquestion_attributes>0){$countquestion_attributes--;};
			if ($countquota>0){$countquota--;};
			$sfieldorders  =convertCSVRowToArray($surveyarray[0],',','"');
			$sfieldcontents=convertCSVRowToArray($surveyarray[1],',','"');
		}
		else
		{
			$sfieldorders=convertToArray($surveyarray[0], "`, `", "(`", "`)");
			$sfieldcontents=convertToArray($surveyarray[0], "', '", "('", "')");
		}
		$surveyrowdata=array_combine($sfieldorders,$sfieldcontents);
		$surveyid=$surveyrowdata["sid"];


		if (!$surveyid)
		{
			if ($importingfrom == "http")
			{
				//				$importsurvey .= "<strong><font color='red'>".("Error")."</strong></font>\n";
				//				$importsurvey .= ("Import of this survey file failed")."\n";
				//				$importsurvey .= ("File does not contain LimeSurvey data in the correct format.")."\n"; //Couldn't find the SID - cannot continue
				//				$importsurvey .= "</font></td></tr></table>\n";
				//				$importsurvey .= "</body>\n</html>\n";
				//				unlink($the_full_file_path); //Delete the uploaded file
				return false;
			}
			else
			{
				//echo ("Import of this survey file failed")."\n".("File does not contain LimeSurvey data in the correct format.")."\n";
				return false;
			}
		}

		// Use the existing surveyid if it does not already exists
		// This allows the URL links to the survey to keep working because the sid did not change
		$newsid = $iVid; //XXX Changed from $surveyid --> $iVid
		$isquery = "SELECT sid FROM {$dbprefix}surveys WHERE sid=$newsid";
		$isresult = db_execute_assoc($isquery);
		if ($isresult->RecordCount()>0)
		{
			// Get new random ids until one is found that is not used
			do
			{
				$newsid = getRandomID();
				$isquery = "SELECT sid FROM {$dbprefix}surveys WHERE sid=$newsid";
				$isresult = db_execute_assoc($isquery);
			}
			while ($isresult->RecordCount()>0);
		}


		$insert=$surveyarray[0];
		if ($importversion>=111)
		{
			$sfieldorders  =convertCSVRowToArray($surveyarray[0],',','"');
			$sfieldcontents=convertCSVRowToArray($surveyarray[1],',','"');
		}
		else
		{
			$sfieldorders=convertToArray($surveyarray[0], "`, `", "(`", "`)");
			$sfieldcontents=convertToArray($surveyarray[0], "', '", "('", "')");
		}
		$surveyrowdata=array_combine($sfieldorders,$sfieldcontents);
		// Set new owner ID
		$surveyrowdata['owner_id']=$_SESSION['loginID'];
		// Set new survey ID
		$surveyrowdata['sid']=$newsid;
		$surveyrowdata['active']='N';


		if ($importversion<=100)
		// find the old language field and replace its contents with the new language shortcuts
		{
			$oldlanguage=$surveyrowdata['language'];
			$newlanguage='en'; //Default
			switch ($oldlanguage)
			{
				case "bulgarian":
					$newlanguage='bg';
					break;
				case "chinese-simplified":
					$newlanguage='zh-Hans';
					break;
				case "chinese-traditional":
					$newlanguage='zh-Hant-HK';
					break;
				case "croatian":
					$newlanguage='hr';
					break;
				case "danish":
					$newlanguage='da';
					break;
				case "dutch":
					$newlanguage='nl';
					break;
				case "english":
					$newlanguage='en';
					break;
				case "french":
					$newlanguage='fr';
					break;
				case "german-informal":
					$newlanguage='de-informal';
					break;
				case "german":
					$newlanguage='de';
					break;
				case "greek":
					$newlanguage='el';
					break;
				case "hungarian":
					$newlanguage='hu';
					break;
				case "italian":
					$newlanguage='it';
					break;
				case "japanese":
					$newlanguage='ja';
					break;
				case "lithuanian":
					$newlanguage='lt';
					break;
				case "norwegian":
					$newlanguage='nb';
					break;
				case "portuguese":
					$newlanguage='pt';
					break;
				case "romanian":
					$newlanguage='ro';
					break;
				case "russian":
					$newlanguage='ru';
					break;
				case "slovenian":
					$newlanguage='sl';
					break;
				case "spanish":
					$newlanguage='es';
					break;
				case "swedish":
					$newlanguage='sv';
					break;
			}

			$surveyrowdata['language']=$newlanguage;

			// copy the survey row data

			// now prepare the languagesettings table and drop according values from the survey array
			$surveylsrowdata=array();
			$surveylsrowdata['surveyls_survey_id']=$newsid;
			$surveylsrowdata['surveyls_language']=$newlanguage;
			$surveylsrowdata['surveyls_title']=$surveyrowdata['short_title'];
			$surveylsrowdata['surveyls_description']=$surveyrowdata['description'];
			$surveylsrowdata['surveyls_welcometext']=$surveyrowdata['welcome'];
			$surveylsrowdata['surveyls_urldescription']=$surveyrowdata['urldescrip'];
			$surveylsrowdata['surveyls_email_invite_subj']=$surveyrowdata['email_invite_subj'];
			$surveylsrowdata['surveyls_email_invite']=$surveyrowdata['email_invite'];
			$surveylsrowdata['surveyls_email_remind_subj']=$surveyrowdata['email_remind_subj'];
			$surveylsrowdata['surveyls_email_remind']=$surveyrowdata['email_remind'];
			$surveylsrowdata['surveyls_email_register_subj']=$surveyrowdata['email_register_subj'];
			$surveylsrowdata['surveyls_email_register']=$surveyrowdata['email_register'];
			$surveylsrowdata['surveyls_email_confirm_subj']=$surveyrowdata['email_confirm_subj'];
			$surveylsrowdata['surveyls_email_confirm']=$surveyrowdata['email_confirm'];
			unset($surveyrowdata['short_title']);
			unset($surveyrowdata['description']);
			unset($surveyrowdata['welcome']);
			unset($surveyrowdata['urldescrip']);
			unset($surveyrowdata['email_invite_subj']);
			unset($surveyrowdata['email_invite']);
			unset($surveyrowdata['email_remind_subj']);
			unset($surveyrowdata['email_remind']);
			unset($surveyrowdata['email_register_subj']);
			unset($surveyrowdata['email_register']);
			unset($surveyrowdata['email_confirm_subj']);
			unset($surveyrowdata['email_confirm']);


			// translate internal links
			$surveylsrowdata['surveyls_title']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_title']);
			$surveylsrowdata['surveyls_description']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_description']);
			$surveylsrowdata['surveyls_welcometext']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_welcometext']);
			$surveylsrowdata['surveyls_urldescription']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_urldescription']);
			$surveylsrowdata['surveyls_email_invite']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_email_invite']);
			$surveylsrowdata['surveyls_email_remind']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_email_remind']);
			$surveylsrowdata['surveyls_email_register']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_email_register']);
			$surveylsrowdata['surveyls_email_confirm']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_email_confirm']);



			// import the survey language-specific settings
			$values=array_values($surveylsrowdata);
			$values=array_map(array(&$connect, "qstr"),$values); // quote everything accordingly
			$insert = "insert INTO {$dbprefix}surveys_languagesettings (".implode(',',array_keys($surveylsrowdata)).") VALUES (".implode(',',$values).")"; //handle db prefix
			try
			{
				$iresult = $connect->Execute($insert) or $this->debugLsrc("".("Import of this survey file failed")."\n[$insert]{$surveyarray[0]}\n" . $connect->ErrorMsg());
			}
			catch(exception $e)
			{
				throw new SoapFault("Server: ", "$e : $connect->ErrorMsg()");
				exit;
			}


		}



		if (isset($surveyrowdata['datecreated'])) {$surveyrowdata['datecreated']=$connect->BindTimeStamp($surveyrowdata['datecreated']);}
		unset($surveyrowdata['expires']);
		unset($surveyrowdata['attribute1']);
		unset($surveyrowdata['attribute2']);
		unset($surveyrowdata['usestartdate']);
		unset($surveyrowdata['useexpiry']);
		unset($surveyrowdata['url']);
		if (isset($surveyrowdata['startdate'])) {unset($surveyrowdata['startdate']);}
		$surveyrowdata['bounce_email']=$surveyrowdata['adminemail'];
		if (!isset($surveyrowdata['datecreated']) || $surveyrowdata['datecreated']=='' || $surveyrowdata['datecreated']=='null') {$surveyrowdata['datecreated']=$connect->BindTimeStamp(date_shift(date("Y-m-d H:i:s"), "Y-m-d", $timeadjust));}

		$values=array_values($surveyrowdata);
		$values=array_map(array(&$connect, "qstr"),$values); // quote everything accordingly
		$insert = "INSERT INTO {$dbprefix}surveys (".implode(',',array_keys($surveyrowdata)).") VALUES (".implode(',',$values).")"; //handle db prefix
		try
		{
			$iresult = $connect->Execute($insert) or $this->debugLsrc(""."Import of this survey file failed on Line: ".__LINE__."\n[$insert]{$surveyarray[0]}\n" . $connect->ErrorMsg()) and exit;
		}
		catch(exception $e)
		{
			throw new SoapFault("Server: ", "$e : $connect->ErrorMsg()");
			exit;
		}
		$oldsid=$surveyid;

		// Now import the survey language settings
		if ($importversion>=111)
		{
			$fieldorders=convertCSVRowToArray($surveylsarray[0],',','"');
			unset($surveylsarray[0]);
			foreach ($surveylsarray as $slsrow) {
				$fieldcontents=convertCSVRowToArray($slsrow,',','"');
				$surveylsrowdata=array_combine($fieldorders,$fieldcontents);
				// convert back the '\'.'n' cahr from the CSV file to true return char "\n"
				$surveylsrowdata=array_map('convertCsvreturn2return', $surveylsrowdata);
				// Convert the \n return char from welcometext to
				// XXX Change values while Importing here //done by rakete
				$surveylsrowdata['surveyls_title']			= $sVtit;
				$surveylsrowdata['surveyls_description']	= $sVbes;
				$surveylsrowdata['surveyls_welcometext']	= $sVwel;
				$surveylsrowdata['surveyls_urldescription'] = $sUbes;
					
					
				// translate internal links
				$surveylsrowdata['surveyls_title']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_title']);
				$surveylsrowdata['surveyls_description']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_description']);
				$surveylsrowdata['surveyls_welcometext']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_welcometext']);
				$surveylsrowdata['surveyls_urldescription']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_urldescription']);
				$surveylsrowdata['surveyls_email_invite']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_email_invite']);
				$surveylsrowdata['surveyls_email_remind']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_email_remind']);
				$surveylsrowdata['surveyls_email_register']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_email_register']);
				$surveylsrowdata['surveyls_email_confirm']=translink('survey', $surveyid, $newsid, $surveylsrowdata['surveyls_email_confirm']);

				$surveylsrowdata['surveyls_survey_id']=$newsid;
				$newvalues=array_values($surveylsrowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
				$lsainsert = "INSERT INTO {$dbprefix}surveys_languagesettings (".implode(',',array_keys($surveylsrowdata)).") VALUES (".implode(',',$newvalues).")"; //handle db prefix
				$lsiresult=$connect->Execute($lsainsert) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."") and exit;
			}

		}


		// DO SURVEY_RIGHTS
		$isrquery = "INSERT INTO {$dbprefix}surveys_rights VALUES($newsid,".$_SESSION['loginID'].",1,1,1,1,1,1)";
		@$isrresult = $connect->Execute($isrquery);
		$deniedcountlabelsets =0;


		//DO ANY LABELSETS FIRST, SO WE CAN KNOW WHAT THEIR NEW LID IS FOR THE QUESTIONS
		if (isset($labelsetsarray) && $labelsetsarray) {
			$csarray=buildLabelSetCheckSumArray();   // build checksums over all existing labelsets
			$count=0;
			foreach ($labelsetsarray as $lsa) {
					
				if ($importversion>=111)
				{
					$fieldorders  =convertCSVRowToArray($labelsetsarray[0],',','"');
					$fieldcontents=convertCSVRowToArray($lsa,',','"');
					if ($count==0) {$count++; continue;}
				}
				else
				{
					$fieldorders=convertToArray($lsa, "`, `", "(`", "`)");
					$fieldcontents=convertToArray($lsa, "', '", "('", "')");
				}
				$labelsetrowdata=array_combine($fieldorders,$fieldcontents);

				// Save old labelid
				$oldlid=$labelsetrowdata['lid'];
				// set the new language
				if ($importversion<=100)
				{
					$labelsetrowdata['languages']=$newlanguage;
				}
				unset($labelsetrowdata['lid']);
				$newvalues=array_values($labelsetrowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
				$lsainsert = "insert INTO {$dbprefix}labelsets (".implode(',',array_keys($labelsetrowdata)).") VALUES (".implode(',',$newvalues).")"; //handle db prefix
				$lsiresult=$connect->Execute($lsainsert);

				// Get the new insert id for the labels inside this labelset
				$newlid=$connect->Insert_ID("{$dbprefix}labelsets","lid");

				//		$importsurvey .= "OLDLID: $oldlid   NEWLID: $newlid";
				//      For debugging label import

				if ($labelsarray) {
					$count=0;
					foreach ($labelsarray as $la) {
						if ($importversion>=111)
						{
							$lfieldorders  =convertCSVRowToArray($labelsarray[0],',','"');
							$lfieldcontents=convertCSVRowToArray($la,',','"');
							if ($count==0) {$count++; continue;}
						}
						else
						{
							//Get field names into array
							$lfieldorders=convertToArray($la, "`, `", "(`", "`)");
							//Get field values into array
							$lfieldcontents=convertToArray($la, "', '", "('", "')");
						}
						// Combine into one array with keys and values since its easier to handle
						$labelrowdata=array_combine($lfieldorders,$lfieldcontents);
						if ($importversion<=132)
						{
							$labelrowdata["assessment_value"]=(int)$labelrowdata["code"];
						}
						$labellid=$labelrowdata['lid'];
						if ($importversion<=100)
						{
							$labelrowdata['language']=$newlanguage;
						}
						if ($labellid == $oldlid) {
							$labelrowdata['lid']=$newlid;

							// translate internal links
							$labelrowdata['title']=translink('label', $oldlid, $newlid, $labelrowdata['title']);

							$newvalues=array_values($labelrowdata);
							$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
							$lainsert = "insert INTO {$dbprefix}labels (".implode(',',array_keys($labelrowdata)).") VALUES (".implode(',',$newvalues).")"; //handle db prefix
							$liresult=$connect->Execute($lainsert);

						}
					}
				}

				//CHECK FOR DUPLICATE LABELSETS
				$thisset="";

				$query2 = "SELECT code, title, sortorder, language
		                   FROM {$dbprefix}labels
		                   WHERE lid=".$newlid."
		                   ORDER BY language, sortorder, code";
				$result2 = db_execute_num($query2) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());
				while($row2=$result2->FetchRow())
				{
					$thisset .= implode('.', $row2);
				} // while
				$newcs=dechex(crc32($thisset)*1);
				unset($lsmatch);
				if (isset($csarray))
				{
					foreach($csarray as $key=>$val)
					{
						if ($val == $newcs)
						{
							$lsmatch=$key;
						}
					}
				}
				if (isset($lsmatch) || ($_SESSION['USER_RIGHT_MANAGE_LABEL'] != 1))
				{
					//There is a matching labelset or the user is not allowed to edit labels -
					// So, we will delete this one and refer to the matched one.
					$query = "DELETE FROM {$dbprefix}labels WHERE lid=$newlid";
					$result=$connect->Execute($query) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());
					$query = "DELETE FROM {$dbprefix}labelsets WHERE lid=$newlid";
					$result=$connect->Execute($query) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());
					if (isset($lsmatch)) {$newlid=$lsmatch;}
					else {++$deniedcountlabelsets;--$countlabelsets;}
				}
				else
				{
					//There isn't a matching labelset, add this checksum to the $csarray array
					$csarray[$newlid]=$newcs;
				}
				//END CHECK FOR DUPLICATES
				$labelreplacements[]=array($oldlid, $newlid);
			}
		}
		$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
		$importwarning = "";	// used to save the warnings while processing questions
			
		$qtypes = $this->getqtypelist("" ,"array");

		foreach ($qtypes as $type) //XXX FIXME
			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ".$type);
		
			// DO GROUPS, QUESTIONS FOR GROUPS, THEN ANSWERS FOR QUESTIONS IN A NESTED FORMAT!
		if (isset($grouparray) && $grouparray) {
			$count=0;
			$currentgid='';
			foreach ($grouparray as $ga) {
				if ($importversion>=111)
				{
					$gafieldorders   =convertCSVRowToArray($grouparray[0],',','"');
					$gacfieldcontents=convertCSVRowToArray($ga,',','"');
					if ($count==0) {$count++; continue;}
				}
				else
				{
					//Get field names into array
					$gafieldorders=convertToArray($ga, "`, `", "(`", "`)");
					//Get field values into array
					$gacfieldcontents=convertToArray($ga, "', '", "('", "')");
				}
				$grouprowdata=array_combine($gafieldorders,$gacfieldcontents);
				// remember group id
				if ($currentgid=='' || ($currentgid!=$grouprowdata['gid'])) {$currentgid=$grouprowdata['gid'];$newgroup=true;}
				else
				if ($currentgid==$grouprowdata['gid']) {$newgroup=false;}
				$gid=$grouprowdata['gid'];
				$gsid=$grouprowdata['sid'];
				//Now an additional integrity check if there are any groups not belonging into this survey
				if ($gsid != $surveyid)
				{
					if ($importingfrom == "http")
					{
						//		                $importsurvey .= "\n<font color='red'><strong>".("Error")."</strong></font>"
						//		                                ."\n".("A group in the CSV/SQL file is not part of the same survey. The import of the survey was stopped.")."\n";
					}
					else
					{
						//echo ("Error").": A group in the CSV/SQL file is not part of the same Survey. The import of the survey was stopped.\n";
					}
					return false;
				}
				$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
				//remove the old group id
				if ($newgroup) {unset($grouprowdata['gid']);}
				else {$grouprowdata['gid']=$newgid;}
				//replace old surveyid by new surveyid
				$grouprowdata['sid']=$newsid;
				// Version <=100 dont have a language field yet so we set it now
				if ($importversion<=100)
				{
					$grouprowdata['language']=$newlanguage;
				}
				$oldgid=$gid; // save it for later
				$grouprowdata=array_map('convertCsvreturn2return', $grouprowdata);

				// translate internal links
				$grouprowdata['group_name']=translink('survey', $surveyid, $newsid, $grouprowdata['group_name']);
				$grouprowdata['description']=translink('survey', $surveyid, $newsid, $grouprowdata['description']);

				$newvalues=array_values($grouprowdata);

				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly

				if (isset($grouprowdata['gid'])) {@$connect->Execute('SET IDENTITY_INSERT '.db_table_name('groups')." ON");}
				$ginsert = 'insert INTO '.db_table_name('groups').' ('.implode(',',array_keys($grouprowdata)).') VALUES ('.implode(',',$newvalues).')';
				$gres = $connect->Execute($ginsert) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());
				if (isset($grouprowdata['gid'])) {@$connect->Execute('SET IDENTITY_INSERT '.db_table_name('groups').' OFF');}
				//GET NEW GID
				if ($newgroup) {$newgid=$connect->Insert_ID("{$dbprefix}groups","gid");}
				
				//NOW DO NESTED QUESTIONS FOR THIS GID
				//$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ".var_dump($questionarray));
									
				if (isset($questionarray) && $questionarray && $newgroup) {
					$count=0;
					$currentqid='';
					foreach ($questionarray as $qa) {
						
						$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ".$qa);
						
						if ($importversion>=111)
						{
							$qafieldorders   =convertCSVRowToArray($questionarray[0],',','"');
							$qacfieldcontents=convertCSVRowToArray($qa,',','"');
							if ($count==0) {$count++; continue;}
						}
						else
						{
							$qafieldorders=convertToArray($qa, "`, `", "(`", "`)");
							$qacfieldcontents=convertToArray($qa, "', '", "('", "')");
						}
						$questionrowdata=array_combine($qafieldorders,$qacfieldcontents);
						$questionrowdata=array_map('convertCsvreturn2return', $questionrowdata);

						if ($currentqid=='' || ($currentqid!=$questionrowdata['qid'])) {$currentqid=$questionrowdata['qid'];$newquestion=true;}
						else
						if ($currentqid==$questionrowdata['qid']) {$newquestion=false;}

						if (!array_key_exists($questionrowdata["type"], $qtypes))
						{
							$questionrowdata["type"] = strtoupper($questionrowdata["type"]);
							if (!array_key_exists($questionrowdata["type"], $qtypes))
							{
								//$importwarning .= "<li>" . sprintf(("Question \"%s - %s\" was NOT imported because the question type is unknown."), $questionrowdata["title"], $questionrowdata["question"]) . "</li>";
								$countquestions--;
								$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ".$countquestions);
								continue;
							}
							else	// the upper case worked well                                                                                                                                                                            $qtypes[$questionrowdata["type"]]
							{
								//$importwarning .= "<li>" . sprintf(("Question \"%s - %s\" was imported but the type was set to '%s' because it is the most similiar one."), $questionrowdata["title"], $questionrowdata["question"], $qtypes[$questionrowdata["type"]]) . "</li>";
							}
						}

						$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");

						$thisgid=$questionrowdata['gid'];
						
						$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ".$thisgid." == ".$gid);
						if ($thisgid == $gid) {
							$qid = $questionrowdata['qid'];
							// Remove qid field
							if ($newquestion) {unset($questionrowdata['qid']);}
							else {$questionrowdata['qid']=$newqid;}

							$questionrowdata["sid"] = $newsid;
							$questionrowdata["gid"] = $newgid;
							// Version <=100 doesn't have a language field yet so we set it now
							if ($importversion<=100)
							{
								$questionrowdata['language']=$newlanguage;
							}
							$oldqid=$qid;
							if (!isset($questionrowdata["lid1"]))
							{
								$questionrowdata["lid1"]=0;
							}
							// Now we will fix up the label id
							$type = $questionrowdata["type"]; //Get the type
							if ($type == "F" || $type == "H" || $type == "W" ||
							$type == "Z" || $type == "1" || $type == ":" ||
							$type == ";" )
							{//IF this is a flexible label array, update the lid entry
								if (isset($labelreplacements)) {
									// We only replace once in each question label
									// otherwise could lead to double substitution
									// if a new lid collides with an older one
									$already_replaced_label = false;
									$already_replaced_label1 = false;
									foreach ($labelreplacements as $lrp) {
										if ($lrp[0] == $questionrowdata["lid"])
										{
											if (!$already_replaced_label)
											{
												$questionrowdata["lid"]=$lrp[1];
												$already_replaced_label = true;
											}
										}
										if ($lrp[0] == $questionrowdata["lid1"])
										{
											if (!$already_replaced_label1)
											{
												$questionrowdata["lid1"]=$lrp[1];
												$already_replaced_label1 = true;
											}
										}
									}
								}
							}
							if (!isset($questionrowdata["question_order"]) || $questionrowdata["question_order"]=='') {$questionrowdata["question_order"]=0;}
							$other = $questionrowdata["other"]; //Get 'other' field value

							// translate internal links
							$questionrowdata['title']=translink('survey', $surveyid, $newsid, $questionrowdata['title']);
							$questionrowdata['question']=translink('survey', $surveyid, $newsid, $questionrowdata['question']);
							$questionrowdata['help']=translink('survey', $surveyid, $newsid, $questionrowdata['help']);

							$newvalues=array_values($questionrowdata);
							if (isset($questionrowdata['qid'])) {@$connect->Execute('SET IDENTITY_INSERT '.db_table_name('questions').' ON');}
							
							foreach($questionrowdata as $qrd)
								$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ".$qrd);
							
								$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
							$qinsert = "insert INTO {$dbprefix}questions (".implode(',',array_keys($questionrowdata)).") VALUES (".implode(',',$newvalues).")";
							
							$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK | ".$qinsert);
							
							$qres = $connect->Execute($qinsert) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());
							if (isset($questionrowdata['qid'])) {@$connect->Execute('SET IDENTITY_INSERT '.db_table_name('questions').' OFF');}
							if ($newquestion)
							{
								$newqid=$connect->Insert_ID("{$dbprefix}questions","qid");
							}

							$newrank=0;
							$substitutions[]=array($oldsid, $oldgid, $oldqid, $newsid, $newgid, $newqid);
							$this->debugLsrc("HALLO?!:");
							//NOW DO NESTED ANSWERS FOR THIS QID
							if (isset($answerarray) && $answerarray && $newquestion) {
								$count=0;
								foreach ($answerarray as $aa) {
									if ($importversion>=111)
									{
										$aafieldorders   =convertCSVRowToArray($answerarray[0],',','"');
										$aacfieldcontents=convertCSVRowToArray($aa,',','"');
										if ($count==0) {$count++; continue;}
									}
									else
									{
										$aafieldorders=convertToArray($aa, "`, `", "(`", "`)");
										$aacfieldcontents=convertToArray($aa, "', '", "('", "')");
									}
									$answerrowdata=array_combine($aafieldorders,$aacfieldcontents);
									if ($importversion<=132)
									{
										$answerrowdata["assessment_value"]=(int)$answerrowdata["code"];
									}
									$code=$answerrowdata["code"];
									$thisqid=$answerrowdata["qid"];
									if ($thisqid == $qid)
									{
										$answerrowdata["qid"]=$newqid;
										// Version <=100 doesn't have a language field yet so we set it now
										if ($importversion<=100)
										{
											$answerrowdata['language']=$newlanguage;
										}

										// translate internal links
										$answerrowdata['answer']=translink('survey', $surveyid, $newsid, $answerrowdata['answer']);

										$newvalues=array_values($answerrowdata);
										$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
										$ainsert = "insert INTO {$dbprefix}answers (".implode(',',array_keys($answerrowdata)).") VALUES (".implode(',',$newvalues).")";
										$ares = $connect->Execute($ainsert) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());

										if ($type == "M" || $type == "P") {
											$fieldnames[]=array("oldcfieldname"=>$oldsid."X".$oldgid."X".$oldqid,
											"newcfieldname"=>$newsid."X".$newgid."X".$newqid,
											"oldfieldname"=>$oldsid."X".$oldgid."X".$oldqid.$code,
											"newfieldname"=>$newsid."X".$newgid."X".$newqid.$code);
											if ($type == "P") {
												$fieldnames[]=array("oldcfieldname"=>$oldsid."X".$oldgid."X".$oldqid."comment",
												"newcfieldname"=>$newsid."X".$newgid."X".$newqid.$code."comment",
												"oldfieldname"=>$oldsid."X".$oldgid."X".$oldqid.$code."comment",
												"newfieldname"=>$newsid."X".$newgid."X".$newqid.$code."comment");
											}
										}
										elseif ($type == "A" || $type == "B" || $type == "C" || $type == "F" || $type == "H" || $type == "E" || $type == "Q" || $type == "K" || $type == "1") {
											$fieldnames[]=array("oldcfieldname"=>$oldsid."X".$oldgid."X".$oldqid.$code,
											"newcfieldname"=>$newsid."X".$newgid."X".$newqid.$code,
											"oldfieldname"=>$oldsid."X".$oldgid."X".$oldqid.$code,
											"newfieldname"=>$newsid."X".$newgid."X".$newqid.$code);
										}
										elseif ($type == ":" || $type == ";" ) {
											// read all label codes from $questionrowdata["lid"]
											// for each one (as L) set SGQA_L
											$labelq="SELECT DISTINCT code FROM {$dbprefix}labels WHERE lid=".$questionrowdata["lid"];
											$labelqresult=db_execute_num($labelq) or safe_die("Died querying labelset $lid<br />$query2<br />".$connect->ErrorMsg());
											while ($labelqrow=$labelqresult->FetchRow())
											{
												$fieldnames[]=array("oldcfieldname"=>$oldsid."X".$oldgid."X".$oldqid.$code."_".$labelqrow[0],
												"newcfieldname"=>$newsid."X".$newgid."X".$newqid.$code."_".$labelqrow[0],
												"oldfieldname"=>$oldsid."X".$oldgid."X".$oldqid.$code."_".$labelqrow[0],
												"newfieldname"=>$newsid."X".$newgid."X".$newqid.$code."_".$labelqrow[0]);
											}
										}
										elseif ($type == "R") {
											$newrank++;
										}
									}
								}
								if (($type == "A" || $type == "B" || $type == "C" || $type == "M" || $type == "P" || $type == "L") && ($other == "Y")) {
									$fieldnames[]=array("oldcfieldname"=>$oldsid."X".$oldgid."X".$oldqid."other",
									"newcfieldname"=>$newsid."X".$newgid."X".$newqid."other",
									"oldfieldname"=>$oldsid."X".$oldgid."X".$oldqid."other",
									"newfieldname"=>$newsid."X".$newgid."X".$newqid."other");
									if ($type == "P") {
										$fieldnames[]=array("oldcfieldname"=>$oldsid."X".$oldgid."X".$oldqid."othercomment",
										"newcfieldname"=>$newsid."X".$newgid."X".$newqid."othercomment",
										"oldfieldname"=>$oldsid."X".$oldgid."X".$oldqid."othercomment",
										"newfieldname"=>$newsid."X".$newgid."X".$newqid."othercomment");
									}
								}
								if ($type == "R" && $newrank >0) {
									for ($i=1; $i<=$newrank; $i++) {
										$fieldnames[]=array("oldcfieldname"=>$oldsid."X".$oldgid."X".$oldqid.$i,
										"newcfieldname"=>$newsid."X".$newgid."X".$newqid.$i,
										"oldfieldname"=>$oldsid."X".$oldgid."X".$oldqid.$i,
										"newfieldname"=>$newsid."X".$newgid."X".$newqid.$i);
									}
								}
								if ($type != "A" && $type != "B" && $type != "C" && $type != "R" && $type != "M" && $type != "P") {
									$fieldnames[]=array("oldcfieldname"=>$oldsid."X".$oldgid."X".$oldqid,
									"newcfieldname"=>$newsid."X".$newgid."X".$newqid,
									"oldfieldname"=>$oldsid."X".$oldgid."X".$oldqid,
									"newfieldname"=>$newsid."X".$newgid."X".$newqid);
									if ($type == "O") {
										$fieldnames[]=array("oldcfieldname"=>$oldsid."X".$oldgid."X".$oldqid."comment",
										"newcfieldname"=>$newsid."X".$newgid."X".$newqid."comment",
										"oldfieldname"=>$oldsid."X".$oldgid."X".$oldqid."comment",
										"newfieldname"=>$newsid."X".$newgid."X".$newqid."comment");
									}
								}
							} else {
								$fieldnames[]=array("oldcfieldname"=>$oldsid."X".$oldgid."X".$oldqid,
								"newcfieldname"=>$newsid."X".$newgid."X".$newqid,
								"oldfieldname"=>$oldsid."X".$oldgid."X".$oldqid,
								"newfieldname"=>$newsid."X".$newgid."X".$newqid);
							}
						}
					}
				}
			}
		}
		$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
		// Fix sortorder of the groups  - if users removed groups manually from the csv file there would be gaps
		//fixsortorderGroups(); //XXX changed: commented out
		//fixsortorderGroups() in full length (using global vars, i cannot work with such things) - rakete
		$baselang = GetBaseLanguageFromSurveyID($iVid);
		$cdresult = db_execute_assoc("SELECT gid FROM ".db_table_name('groups')." WHERE sid='{$surveyid}' AND language='{$baselang}' ORDER BY group_order, group_name");
		$position=0;
		while ($cdrow=$cdresult->FetchRow())
		{
			$cd2query="UPDATE ".db_table_name('groups')." SET group_order='{$position}' WHERE gid='{$cdrow['gid']}' ";
			$cd2result = $connect->Execute($cd2query) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());  //Checked
			$position++;
		}

		$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");

		//... and for the questions inside the groups
		// get all group ids and fix questions inside each group
		$gquery = "SELECT gid FROM {$dbprefix}groups where sid=$newsid group by gid ORDER BY gid"; //Get last question added (finds new qid)
		$gres = db_execute_assoc($gquery);
		while ($grow = $gres->FetchRow())
		{
			//fixsortorderQuestions(0,$grow['gid']); //XXX changed: commented out
			// and fully written out:
			$qid=sanitize_int(0);
			$gid=sanitize_int($grow['gid']);
			$baselang = GetBaseLanguageFromSurveyID($iVid);
			if ($gid == 0)
			{
				$result = db_execute_assoc("SELECT gid FROM ".db_table_name('questions')." WHERE qid='{$qid}' and language='{$baselang}'");  //Checked
				$row=$result->FetchRow();
				$gid=$row['gid'];
			}
			$cdresult = db_execute_assoc("SELECT qid FROM ".db_table_name('questions')." WHERE gid='{$gid}' and language='{$baselang}' ORDER BY question_order, title ASC");      //Checked
			$position=0;
			while ($cdrow=$cdresult->FetchRow())
			{
				$cd2query="UPDATE ".db_table_name('questions')." SET question_order='{$position}' WHERE qid='{$cdrow['qid']}' ";
				$cd2result = $connect->Execute($cd2query) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());
				$position++;
			}

		}
		$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");

		//We've built two arrays along the way - one containing the old SID, GID and QIDs - and their NEW equivalents
		//and one containing the old 'extended fieldname' and its new equivalent.  These are needed to import conditions and question_attributes.
		if (isset($question_attributesarray) && $question_attributesarray) {//ONLY DO THIS IF THERE ARE QUESTION_ATTRIBUES
			$count=0;
			foreach ($question_attributesarray as $qar) {
				if ($importversion>=111)
				{
					$fieldorders  =convertCSVRowToArray($question_attributesarray[0],',','"');
					$fieldcontents=convertCSVRowToArray($qar,',','"');
					if ($count==0) {$count++; continue;}
				}
				else
				{
					$fieldorders=convertToArray($qar, "`, `", "(`", "`)");
					$fieldcontents=convertToArray($qar, "', '", "('", "')");
				}
				$qarowdata=array_combine($fieldorders,$fieldcontents);
				$newqid="";
				$oldqid=$qarowdata['qid'];
				foreach ($substitutions as $subs) {
					if ($oldqid==$subs[2]) {$newqid=$subs[5];}
				}

				$qarowdata["qid"]=$newqid;
				unset($qarowdata["qaid"]);

				$newvalues=array_values($qarowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
				$qainsert = "insert INTO {$dbprefix}question_attributes (".implode(',',array_keys($qarowdata)).") VALUES (".implode(',',$newvalues).")";
				$result=$connect->Execute($qainsert) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."|  $qainsert  |".$connect->ErrorMsg());
			}
		}

		if (isset($assessmentsarray) && $assessmentsarray) {//ONLY DO THIS IF THERE ARE QUESTION_ATTRIBUES
			$count=0;
			foreach ($assessmentsarray as $qar) {
				if ($importversion>=111)
				{
					$fieldorders  =convertCSVRowToArray($assessmentsarray[0],',','"');
					$fieldcontents=convertCSVRowToArray($qar,',','"');
					if ($count==0) {$count++; continue;}
				}
				else
				{
					$fieldorders=convertToArray($qar, "`, `", "(`", "`)");
					$fieldcontents=convertToArray($qar, "', '", "('", "')");
				}
				$asrowdata=array_combine($fieldorders,$fieldcontents);
				if (isset($asrowdata['link']))
				{
					if (trim($asrowdata['link'])!='') $asrowdata['message']=$asrowdata['message'].'<br /><a href="'.$asrowdata['link'].'">'.$asrowdata['link'].'</a>';
					unset($asrowdata['link']);
				}
				$oldsid=$asrowdata["sid"];
				$oldgid=$asrowdata["gid"];
				if  ($oldgid>0)
				{
					foreach ($substitutions as $subs) {
						if ($oldsid==$subs[0]) {$newsid=$subs[3];}
						if ($oldgid==$subs[1]) {$newgid=$subs[4];}
					}
				}
				else
				{
					$newgid=0;
				}

				$asrowdata["sid"]=$newsid;
				$asrowdata["gid"]=$newgid;
				unset($asrowdata["id"]);


				$newvalues=array_values($asrowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
				$asinsert = "insert INTO {$dbprefix}assessments (".implode(',',array_keys($asrowdata)).") VALUES (".implode(',',$newvalues).")";
				$result=$connect->Execute($asinsert) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());

				unset($newgid);
			}
		}

		if (isset($quotaarray) && $quotaarray) {//ONLY DO THIS IF THERE ARE QUOTAS
			$count=0;
			foreach ($quotaarray as $qar) {

				$fieldorders=convertCSVRowToArray($quotaarray[0],',','"');
				$fieldcontents=convertCSVRowToArray($qar,',','"');
				if ($count==0) {$count++; continue;}
					
				$asrowdata=array_combine($fieldorders,$fieldcontents);

				$oldsid=$asrowdata["sid"];
				foreach ($substitutions as $subs) {
					if ($oldsid==$subs[0]) {$newsid=$subs[3];}
				}

				$asrowdata["sid"]=$newsid;
				$oldid = $asrowdata["id"];
				unset($asrowdata["id"]);

				$newvalues=array_values($asrowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly

				$asinsert = "insert INTO {$dbprefix}quota (".implode(',',array_keys($asrowdata)).") VALUES (".implode(',',$newvalues).")";
				$result=$connect->Execute($asinsert) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());
				$quotaids[] = array($oldid,$connect->Insert_ID(db_table_name_nq('quota'),"id"));

			}
		}

		if (isset($quotamembersarray) && $quotamembersarray) {//ONLY DO THIS IF THERE ARE QUOTAS
			$count=0;
			foreach ($quotamembersarray as $qar) {

				$fieldorders  =convertCSVRowToArray($quotamembersarray[0],',','"');
				$fieldcontents=convertCSVRowToArray($qar,',','"');
				if ($count==0) {$count++; continue;}
					
				$asrowdata=array_combine($fieldorders,$fieldcontents);

				$oldsid=$asrowdata["sid"];
				$newqid="";
				$newquotaid="";
				$oldqid=$asrowdata['qid'];
				$oldquotaid=$asrowdata['quota_id'];

				foreach ($substitutions as $subs) {
					if ($oldsid==$subs[0]) {$newsid=$subs[3];}
					if ($oldqid==$subs[2]) {$newqid=$subs[5];}
				}

				foreach ($quotaids as $quotaid) {
					if ($oldquotaid==$quotaid[0]) {$newquotaid=$quotaid[1];}
				}

				$asrowdata["sid"]=$newsid;
				$asrowdata["qid"]=$newqid;
				$asrowdata["quota_id"]=$newquotaid;
				unset($asrowdata["id"]);

				$newvalues=array_values($asrowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly

				$asinsert = "insert INTO {$dbprefix}quota_members (".implode(',',array_keys($asrowdata)).") VALUES (".implode(',',$newvalues).")";
				$result=$connect->Execute($asinsert) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());

			}
		}

		if (isset($conditionsarray) && $conditionsarray) {//ONLY DO THIS IF THERE ARE CONDITIONS!
			$count='0';
			foreach ($conditionsarray as $car) {
				if ($importversion>=111)
				{
					$fieldorders  =convertCSVRowToArray($conditionsarray[0],',','"');
					$fieldcontents=convertCSVRowToArray($car,',','"');
					if ($count==0) {$count++; continue;}
				}
				else
				{
					$fieldorders=convertToArray($car, "`, `", "(`", "`)");
					$fieldcontents=convertToArray($car, "', '", "('", "')");
				}
				$conditionrowdata=array_combine($fieldorders,$fieldcontents);

				$oldcid=$conditionrowdata["cid"];
				$oldqid=$conditionrowdata["qid"];
				$oldcfieldname=$conditionrowdata["cfieldname"];
				$oldcqid=$conditionrowdata["cqid"];
				$thisvalue=$conditionrowdata["value"];
				$newvalue=$thisvalue;

				foreach ($substitutions as $subs) {
					if ($oldqid==$subs[2])  {$newqid=$subs[5];}
					if ($oldcqid==$subs[2]) {$newcqid=$subs[5];}
				}
				if (preg_match('/^@([0-9]+)X([0-9]+)X([^@]+)@/',$thisvalue,$targetcfieldname))
				{
					foreach ($substitutions as $subs) {
						if ($targetcfieldname[1]==$subs[0])  {$targetcfieldname[1]=$subs[3];}
						if ($targetcfieldname[2]==$subs[1])  {$targetcfieldname[2]=$subs[4];}
						if ($targetcfieldname[3]==$subs[2])  {$targetcfieldname[3]=$subs[5];}
					}
					$newvalue='@'.$targetcfieldname[1].'X'.$targetcfieldname[2].'X'.$targetcfieldname[3].'@';
				}
				foreach($fieldnames as $fns) {
					//if the $fns['oldcfieldname'] is not the same as $fns['oldfieldname'] then this is a multiple type question
					if ($fns['oldcfieldname'] == $fns['oldfieldname']) { //The normal method - non multiples
						if ($oldcfieldname==$fns['oldcfieldname']) {
							$newcfieldname=$fns['newcfieldname'];
						}
					} else {
						if ($oldcfieldname == $fns['oldcfieldname'] && $oldcfieldname.$thisvalue == $fns['oldfieldname']) {
							$newcfieldname=$fns['newcfieldname'];
						}
					}
				}
				if (!isset($newcfieldname)) {$newcfieldname="";}
				unset($conditionrowdata["cid"]);
				$conditionrowdata["qid"]=$newqid;
				$conditionrowdata["cfieldname"]=$newcfieldname;
				$conditionrowdata["value"]=$newvalue;

				if (isset($newcqid)) {
					$conditionrowdata["cqid"]=$newcqid;
					if (!isset($conditionrowdata["method"]) || trim($conditionrowdata["method"])=='')
					{
						$conditionrowdata["method"]='==';
					}
					if (!isset($conditionrowdata["scenario"]) || trim($conditionrowdata["scenario"])=='')
					{
						$conditionrowdata["scenario"]=1;
					}
					$newvalues=array_values($conditionrowdata);
					$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
					$conditioninsert = "insert INTO {$dbprefix}conditions (".implode(',',array_keys($conditionrowdata)).") VALUES (".implode(',',$newvalues).")";
					$result=$connect->Execute($conditioninsert) or $this->debugLsrc("Import of this survey file failed on Line ".__LINE__."| ".$connect->ErrorMsg());
				} else {
					$importsurvey .= "<font size=1>Condition for $oldqid skipped ($oldcqid does not exist)</font>";
					//if ($importingfrom != "http") echo "Condition for $oldqid skipped ($oldcqid does not exist)\n";
					//return; //XXX changed: comment the upper line, returning
				}
				unset($newcqid);
			}
		}

		// Translate INSERTANS codes
		if (isset($fieldnames))
		{
			transInsertAns($newsid,$oldsid,$fieldnames);
		}
		$surveyid=$newsid;

		return true;
	}
	 
	/**
	 * function to activate surveys based on new activate.php 5771 2008-10-13 02:28:40Z jcleeland $
	 *
	 * @param unknown_type $surveyid
	 * @return boolean
	 */
	function activateSurvey($surveyid)//XXX activateSurvey
	{
		global $connect ;
		//		global $dbprefix ;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		include("lsrc.config.php");
		$_GET['sid'] = $surveyid;
		$_POST['sid'] = $surveyid;
		//$postsid = $surveyid;
		//$activateoutput='';

		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");

		if (!isset($_POST['ok']) || !$_POST['ok'])
		{
			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
			if (isset($_GET['fixnumbering']) && $_GET['fixnumbering'])
			{
				$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
				//Fix a question id - requires renumbering a question
				$oldqid = $_GET['fixnumbering'];
				$query = "SELECT qid FROM {$dbprefix}questions ORDER BY qid DESC";
				$result = db_select_limit_assoc($query, 1) or $this->debugLsrc($query."".$connect->ErrorMsg());
				while ($row=$result->FetchRow()) {$lastqid=$row['qid'];}
				$newqid=$lastqid+1;
				$query = "UPDATE {$dbprefix}questions SET qid=$newqid WHERE qid=$oldqid";
				$result = $connect->Execute($query) or $this->debugLsrc($query."".$connect->ErrorMsg());
				//Update conditions.. firstly conditions FOR this question
				$query = "UPDATE {$dbprefix}conditions SET qid=$newqid WHERE qid=$oldqid";
				$result = $connect->Execute($query) or $this->debugLsrc($query."".$connect->ErrorMsg());
				//Now conditions based upon this question
				$query = "SELECT cqid, cfieldname FROM {$dbprefix}conditions WHERE cqid=$oldqid";
				$result = db_execute_assoc($query) or $this->debugLsrc($query."".$connect->ErrorMsg());
				$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
				while ($row=$result->FetchRow())
				{
					$switcher[]=array("cqid"=>$row['cqid'], "cfieldname"=>$row['cfieldname']);
				}
				if (isset($switcher))
				{
					foreach ($switcher as $switch)
					{
						$query = "UPDATE {$dbprefix}conditions
								  SET cqid=$newqid,
								  cfieldname='".str_replace("X".$oldqid, "X".$newqid, $switch['cfieldname'])."'
								  WHERE cqid=$oldqid";
						$result = $connect->Execute($query) or $this->debugLsrc($query."".$connect->ErrorMsg());
					}
				}
				$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
				//Now question_attributes
				$query = "UPDATE {$dbprefix}question_attributes SET qid=$newqid WHERE qid=$oldqid";
				$result = $connect->Execute($query) or $this->debugLsrc($query."".$connect->ErrorMsg());
				//Now answers
				$query = "UPDATE {$dbprefix}answers SET qid=$newqid WHERE qid=$oldqid";
				$result = $connect->Execute($query) or $this->debugLsrc($query."".$connect->ErrorMsg());
			}
			//CHECK TO MAKE SURE ALL QUESTION TYPES THAT REQUIRE ANSWERS HAVE ACTUALLY GOT ANSWERS
			//THESE QUESTION TYPES ARE:
			//	# "L" -> LIST
			//  # "O" -> LIST WITH COMMENT
			//  # "M" -> MULTIPLE OPTIONS
			//	# "P" -> MULTIPLE OPTIONS WITH COMMENTS
			//	# "A", "B", "C", "E", "F", "H", "^" -> Various Array Types
			//  # "R" -> RANKING
			//  # "U" -> FILE CSV MORE
			//  # "I" -> FILE CSV ONE
			//  # ":" -> Array Multi Flexi Numbers
			//  # ";" -> Array Multi Flexi Text
			//  # "1" -> MULTI SCALE
				

			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
				
			$chkquery = "SELECT qid, question, gid FROM {$dbprefix}questions WHERE sid={$surveyid} AND type IN ('L', 'O', 'M', 'P', 'A', 'B', 'C', 'E', 'F', 'R', 'J', '!', '^', ':', '1')";
			$chkresult = db_execute_assoc($chkquery) or $this->debugLsrc ("Couldn't get list of questions$chkquery".$connect->ErrorMsg());
			while ($chkrow = $chkresult->FetchRow())
			{
				$chaquery = "SELECT * FROM {$dbprefix}answers WHERE qid = {$chkrow['qid']} ORDER BY sortorder, answer";
				$charesult=$connect->Execute($chaquery);
				$chacount=$charesult->RecordCount();
				if (!$chacount > 0)
				{
					//$failedcheck[]=array($chkrow['qid'], $chkrow['question'], ": ".("This question is a multiple answer type question but has no answers."), $chkrow['gid']);
				}
			}

			//NOW CHECK THAT ALL QUESTIONS HAVE A 'QUESTION TYPE' FIELD
			$chkquery = "SELECT qid, question, gid FROM {$dbprefix}questions WHERE sid={$surveyid} AND type = ''";
			$chkresult = db_execute_assoc($chkquery) or $this->debugLsrc ("Couldn't check questions for missing types$chkquery".$connect->ErrorMsg());
			while ($chkrow = $chkresult->FetchRow())
			{
				//$failedcheck[]=array($chkrow['qid'], $chkrow['question'], ": ".("This question does not have a question 'type' set."), $chkrow['gid']);
			}

			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
				

			//CHECK THAT FLEXIBLE LABEL TYPE QUESTIONS HAVE AN "LID" SET
			$chkquery = "SELECT qid, question, gid FROM {$dbprefix}questions WHERE sid={$surveyid} AND type IN ('F', 'H', 'W', 'Z', ':', '1') AND (lid = 0 OR lid is null)";
			//$chkresult = db_execute_assoc($chkquery) or $this->debugLsrc ("Couldn't check questions for missing LIDs$chkquery".$connect->ErrorMsg());
			while($chkrow = $chkresult->FetchRow()){
				//	$failedcheck[]=array($chkrow['qid'], $chkrow['question'], ": ".("This question requires a Labelset, but none is set."), $chkrow['gid']);
			} // while
				
			//CHECK THAT FLEXIBLE LABEL TYPE QUESTIONS HAVE AN "LID1" SET FOR MULTI SCALE
			$chkquery = "SELECT qid, question, gid FROM {$dbprefix}questions WHERE sid={$surveyid} AND (type ='1') AND (lid1 = 0 OR lid1 is null)";
			$chkresult = db_execute_assoc($chkquery) or $this->debugLsrc ("Couldn't check questions for missing LIDs$chkquery".$connect->ErrorMsg());
			while($chkrow = $chkresult->FetchRow()){
				//	$failedcheck[]=array($chkrow['qid'], $chkrow['question'], ": ".("This question requires a second Labelset, but none is set."), $chkrow['gid']);
			} // while
			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
				
			// XXX rakete Changed: This was making errors, for we dont have additional languages and this script throws an error when there are none.

			//NOW check that all used labelsets have all necessary languages
			$chkquery = "SELECT qid, question, gid, lid FROM {$dbprefix}questions WHERE sid={$surveyid} AND type IN ('F', 'H', 'W', 'Z', ':', '1') AND (lid > 0) AND (lid is not null)";
			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", $chkquery ");
			$chkresult = db_execute_assoc($chkquery) or $this->debugLsrc ("Couldn't check questions for missing LID languages$chkquery".$connect->ErrorMsg());
			$slangs = GetAdditionalLanguagesFromSurveyID($surveyid);
			$baselang = GetBaseLanguageFromSurveyID($surveyid);
			array_unshift($slangs,$baselang);
			while ($chkrow = $chkresult->FetchRow())
			{
				foreach ($slangs as $surveylanguage)
				{
					$chkquery2 = "SELECT lid FROM {$dbprefix}labels WHERE language='$surveylanguage' AND (lid = {$chkrow['lid']}) ";
					$chkresult2 = db_execute_assoc($chkquery2);
					if ($chkresult2->RecordCount()==0)
					{
						$failedcheck[]=array($chkrow['qid'], $chkrow['question'], ": The labelset used in this question does not exists or is missing a translation.", $chkrow['gid']);
					}
				}  //foreach
			} //while
				
			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
			//CHECK THAT ALL CONDITIONS SET ARE FOR QUESTIONS THAT PRECEED THE QUESTION CONDITION
			//A: Make an array of all the qids in order of appearance
			//	$qorderquery="SELECT * FROM {$dbprefix}questions, {$dbprefix}groups WHERE {$dbprefix}questions.gid={$dbprefix}groups.gid AND {$dbprefix}questions.sid={$surveyid} ORDER BY {$dbprefix}groups.sortorder, {$dbprefix}questions.title";
			//	$qorderresult=$connect->Execute($qorderquery) or $this->debugLsrc("Couldn't generate a list of questions in order$qorderquery".$connect->ErrorMsg());
			//	$qordercount=$qorderresult->RecordCount();
			//	$c=0;
			//	while ($qorderrow=$qorderresult->FetchRow())
			//		{
			//		$qidorder[]=array($c, $qorderrow['qid']);
			//		$c++;
			//		}
			//TO AVOID NATURAL SORT ORDER ISSUES, FIRST GET ALL QUESTIONS IN NATURAL SORT ORDER, AND FIND OUT WHICH NUMBER IN THAT ORDER THIS QUESTION IS
			$qorderquery = "SELECT * FROM {$dbprefix}questions WHERE sid=$surveyid AND type not in ('S', 'D', 'T', 'Q')";
			$qorderresult = db_execute_assoc($qorderquery) or $this->debugLsrc ("$qorderquery".$connect->ErrorMsg());
			$qrows = array(); //Create an empty array in case FetchRow does not return any rows
			while ($qrow = $qorderresult->FetchRow()) {$qrows[] = $qrow;} // Get table output into array
			usort($qrows, 'CompareGroupThenTitle'); // Perform a case insensitive natural sort on group name then question title of a multidimensional array
			$c=0;
			foreach ($qrows as $qr)
			{
				$qidorder[]=array($c, $qrow['qid']);
				$c++;
			}
			$qordercount="";
			//1: Get each condition's question id
			$conquery= "SELECT {$dbprefix}conditions.qid, cqid, {$dbprefix}questions.question, "
			. "{$dbprefix}questions.gid "
			. "FROM {$dbprefix}conditions, {$dbprefix}questions, {$dbprefix}groups "
			. "WHERE {$dbprefix}conditions.qid={$dbprefix}questions.qid "
			. "AND {$dbprefix}questions.gid={$dbprefix}groups.gid ORDER BY {$dbprefix}conditions.qid";
			$conresult=db_execute_assoc($conquery) or $this->debugLsrc("Couldn't check conditions for relative consistency$conquery".$connect->ErrorMsg());
			//2: Check each conditions cqid that it occurs later than the cqid
			while ($conrow=$conresult->FetchRow())
			{
				$cqidfound=0;
				$qidfound=0;
				$b=0;
				while ($b<$qordercount)
				{
					if ($conrow['cqid'] == $qidorder[$b][1])
					{
						$cqidfound = 1;
						$b=$qordercount;
					}
					if ($conrow['qid'] == $qidorder[$b][1])
					{
						$qidfound = 1;
						$b=$qordercount;
					}
					if ($qidfound == 1)
					{
						//$failedcheck[]=array($conrow['qid'], $conrow['question'], ": ".("This question has a condition set, however the condition is based on a question that appears after it."), $conrow['gid']);
					}
					$b++;
				}
			}
			//CHECK THAT ALL THE CREATED FIELDS WILL BE UNIQUE
			$fieldmap=createFieldMap($surveyid, "full");
			if (isset($fieldmap))
			{
				foreach($fieldmap as $fielddata)
				{
					$fieldlist[]=$fielddata['fieldname'];
				}
				$fieldlist=array_reverse($fieldlist); //let's always change the later duplicate, not the earlier one
			}
			$checkKeysUniqueComparison = create_function('$value','if ($value > 1) return true;');
			@$duplicates = array_keys (array_filter (array_count_values($fieldlist), $checkKeysUniqueComparison));
			if (isset($duplicates))
			{
				foreach ($duplicates as $dup)
				{
					$badquestion=arraySearchByKey($dup, $fieldmap, "fieldname", 1);
					$fix = "[<a href='$scriptname?action=activate&amp;sid=$surveyid&amp;fixnumbering=".$badquestion['qid']."'>Click Here to Fix</a>]";
					//$failedcheck[]=array($badquestion['qid'], $badquestion['question'], ": Bad duplicate fieldname $fix", $badquestion['gid']);
				}
			}

			//IF ANY OF THE CHECKS FAILED, PRESENT THIS SCREEN
			if (isset($failedcheck) && $failedcheck)
			{
				//$activateoutput .= "\n<table bgcolor='#FFFFFF' width='500' align='center' style='border: 1px solid #555555' cellpadding='6' cellspacing='0'>\n";
				//$activateoutput .= "\t\t\t\t<tr bgcolor='#555555'><td height='4'><font size='1' face='verdana' color='white'><strong>".("Activate Survey")." ($surveyid)</strong></font></td></tr>\n";
				//$activateoutput .= "\t<tr>\n";
				//$activateoutput .= "\t\t<td align='center' bgcolor='#ffeeee'>\n";
				//$activateoutput .= "\t\t\t<font color='red'><strong>".("Error")."</strong>\n";
				//$activateoutput .= "\t\t\t".("Survey does not pass consistency check")."</font>\n";
				//$activateoutput .= "\t\t</td>\n";
				//$activateoutput .= "\t</tr>\n";
				//$activateoutput .= "\t<tr>\n";
				//$activateoutput .= "\t\t<td>\n";
				//$activateoutput .= "\t\t\t<strong>".("The following problems have been found:")."</strong>\n";
				//$activateoutput .= "\t\t\t<ul>\n";
				foreach ($failedcheck as $fc)
				{
					//$activateoutput .= "\t\t\t\t<li> Question qid-{$fc[0]} (\"<a href='$scriptname?sid=$surveyid&amp;gid=$fc[3]&amp;qid=$fc[0]'>{$fc[1]}</a>\"){$fc[2]}</li>\n";
				}
				//$activateoutput .= "\t\t\t</ul>\n";
				//$activateoutput .= "\t\t\t".("The survey cannot be activated until these problems have been resolved.")."\n";
				//$activateoutput .= "\t\t</td>\n";
				//$activateoutput .= "\t</tr>\n";
				//$activateoutput .= "</table>&nbsp;\n";
				$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", NICHT ERWARTET ");
				return false;
			}
			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
			//$activateoutput .= "\n<table class='alertbox'>\n";
			//$activateoutput .= "\t\t\t\t<tr><td height='4'><strong>".("Activate Survey")." ($surveyid)</strong></td></tr>\n";
			//$activateoutput .= "\t<tr>\n";
			//$activateoutput .= "\t\t<td align='center' bgcolor='#ffeeee'>\n";
			//$activateoutput .= "\t\t\t<font color='red'><strong>".("Warning")."</strong>\n";
			//$activateoutput .= "\t\t\t".("READ THIS CAREFULLY BEFORE PROCEEDING")."\n";
			//$activateoutput .= "\t\t\t</font>\n";
			//$activateoutput .= "\t\t</td>\n";
			//$activateoutput .= "\t</tr>\n";
			//$activateoutput .= "\t<tr>\n";
			//$activateoutput .= "\t\t<td>\n";
			//$activateoutput .= ("You should only activate a survey when you are absolutely certain that your survey setup is finished and will not need changing.")."\n";
			//$activateoutput .= ("Once a survey is activated you can no longer:")."<ul><li>".("Add or delete groups")."</li><li>".("Add or remove answers to Multiple Answer questions")."</li><li>".("Add or delete questions")."</li></ul>\n";
			//$activateoutput .= ("However you can still:")."<ul><li>".("Edit (change) your questions code, text or type")."</li><li>".("Edit (change) your group names")."</li><li>".("Add, Remove or Edit pre-defined question answers (except for Multi-answer questions)")."</li><li>".("Change survey name or description")."</li></ul>\n";
			//$activateoutput .= ("Once data has been entered into this survey, if you want to add or remove groups or questions, you will need to de-activate this survey, which will move all data that has already been entered into a separate archived table.")."\n";
			//$activateoutput .= "\t\t</td>\n";
			//$activateoutput .= "\t</tr>\n";
			//$activateoutput .= "\t<tr>\n";
			//$activateoutput .= "\t\t<td align='center'>\n";
			//$activateoutput .= "\t\t\t<input type='submit' value=\"".("Activate Survey")."\" onclick=\"window.open('$scriptname?action=activate&amp;ok=Y&amp;sid={$surveyid}', '_top')\" />\n";
			//$activateoutput .= "\t\t\t<input type='submit' value=\"".("Activate Survey")."\" onclick=\"".get2post("$scriptname?action=activate&amp;ok=Y&amp;sid={$surveyid}")."\" />\n";
			//$activateoutput .= "\t\t&nbsp;</td>\n";
			//$activateoutput .= "\t</tr>\n";
			//$activateoutput .= "</table>&nbsp;\n";
				
			//XXX Changed rakete, set Post var for lsrc, no else
			$_POST['ok'] = "Y";
			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
		}

		if (isset($_POST['ok']) || $_POST['ok'])
		{
			$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");
			//Create the survey responses table
			$createsurvey = "id I NOTNULL AUTO PRIMARY,\n";
			$createsurvey .= " submitdate T,\n";
			$createsurvey .= " startlanguage C(20) NOTNULL ,\n";
			//Check for any additional fields for this survey and create necessary fields (token and datestamp)
			$pquery = "SELECT private, allowregister, datestamp, ipaddr, refurl FROM {$dbprefix}surveys WHERE sid={$surveyid}";
			$presult=db_execute_assoc($pquery);
			while($prow=$presult->FetchRow())
			{
				if ($prow['private'] == "N")
				{
					$createsurvey .= "  token C(36),\n";
					$surveynotprivate="TRUE";
				}
				if ($prow['allowregister'] == "Y")
				{
					$surveyallowsregistration="TRUE";
				}
				if ($prow['datestamp'] == "Y")
				{
					$createsurvey .= " datestamp T NOTNULL,\n";
					$createsurvey .= " startdate T NOTNULL,\n";
				}
				if ($prow['ipaddr'] == "Y")
				{
					$createsurvey .= " ipaddr X,\n";
				}
				//Check to see if 'refurl' field is required.
				if ($prow['refurl'] == "Y")
				{
					$createsurvey .= " refurl X,\n";
				}
			}
			//Get list of questions for the base language
			$aquery = " SELECT * FROM ".db_table_name('questions').", ".db_table_name('groups')
			." WHERE ".db_table_name('questions').".gid=".db_table_name('groups').".gid "
			." AND ".db_table_name('questions').".sid={$surveyid} "
			." AND ".db_table_name('groups').".language='".GetbaseLanguageFromSurveyid($surveyid). "' "
			." AND ".db_table_name('questions').".language='".GetbaseLanguageFromSurveyid($surveyid). "' "
			." ORDER BY ".db_table_name('groups').".group_order, title";
			$aresult = db_execute_assoc($aquery);
			while ($arow=$aresult->FetchRow()) //With each question, create the appropriate field(s)
			{
				if ( substr($createsurvey, strlen($createsurvey)-2, 2) != ",\n") {$createsurvey .= ",\n";}

				if ($arow['type'] != "M" && $arow['type'] != "A" && $arow['type'] != "B" &&
				$arow['type'] != "C" && $arow['type'] != "E" && $arow['type'] != "F" &&
				$arow['type'] != "H" && $arow['type'] != "P" && $arow['type'] != "R" &&
				$arow['type'] != "Q" && $arow['type'] != "^" && $arow['type'] != "J" &&
				$arow['type'] != "K" && $arow['type'] != ":" && $arow['type'] != ";" &&
				$arow['type'] != "1")
				{
					$createsurvey .= "  `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}`";
					switch($arow['type'])
					{
						case "N":  //NUMERICAL
							$createsurvey .= " F";
							break;
						case "S":  //SHORT TEXT
							if ($databasetype=='mysql' || $databasetype=='mysqli')	{$createsurvey .= " X";}
							else  {$createsurvey .= " C(255)";}
							break;
						case "L":  //LIST (RADIO)
						case "!":  //LIST (DROPDOWN)
						case "W":
						case "Z":
							$createsurvey .= " C(5)";
							if ($arow['other'] == "Y")
							{
								$createsurvey .= ",\n`{$arow['sid']}X{$arow['gid']}X{$arow['qid']}other` X";
							}
							break;
						case "I":  // CSV ONE
							$createsurvey .= " C(5)";
							break;
						case "O": //DROPDOWN LIST WITH COMMENT
							$createsurvey .= " C(5),\n `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}comment` X";
							break;
						case "T":  //LONG TEXT
							$createsurvey .= " X";
							break;
						case "U":  //HUGE TEXT
							$createsurvey .= " X";
							break;
						case "D":  //DATE
							$createsurvey .= " D";
							break;
						case "5":  //5 Point Choice
						case "G":  //Gender
						case "Y":  //YesNo
						case "X":  //Boilerplate
							$createsurvey .= " C(1)";
							break;
					}
				}
				elseif ($arow['type'] == "M" || $arow['type'] == "A" || $arow['type'] == "B" ||
				$arow['type'] == "C" || $arow['type'] == "E" || $arow['type'] == "F" ||
				$arow['type'] == "H" || $arow['type'] == "P" || $arow['type'] == "^")
				{
					//MULTI ENTRY
					$abquery = "SELECT a.*, q.other FROM {$dbprefix}answers as a, {$dbprefix}questions as q"
					." WHERE a.qid=q.qid AND sid={$surveyid} AND q.qid={$arow['qid']} "
					." AND a.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." AND q.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." ORDER BY a.sortorder, a.answer";
					$abresult=db_execute_assoc($abquery) or $this->debugLsrc ("Couldn't get perform answers query$abquery".$connect->ErrorMsg());
					while ($abrow=$abresult->FetchRow())
					{
						$createsurvey .= "  `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}` C(5),\n";
						if ($abrow['other']=="Y") {$alsoother="Y";}
						if ($arow['type'] == "P")
						{
							$createsurvey .= "  `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}comment` X,\n";
						}
					}
					if ((isset($alsoother) && $alsoother=="Y") && ($arow['type']=="M" || $arow['type']=="P"  || $arow['type']=="1")) //Sc: check!
					{
						$createsurvey .= " `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}other` C(255),\n";
						if ($arow['type']=="P")
						{
							$createsurvey .= " `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}othercomment` X,\n";
						}
					}
				}
				elseif ($arow['type'] == ":" || $arow['type'] == ";")
				{
					//MULTI ENTRY
					$abquery = "SELECT a.*, q.other FROM {$dbprefix}answers as a, {$dbprefix}questions as q"
					." WHERE a.qid=q.qid AND sid={$surveyid} AND q.qid={$arow['qid']} "
					." AND a.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." AND q.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." ORDER BY a.sortorder, a.answer";
					$abresult=db_execute_assoc($abquery) or die ("Couldn't get perform answers query$abquery".$connect->ErrorMsg());
					$ab2query = "SELECT ".db_table_name('labels').".*
					             FROM ".db_table_name('questions').", ".db_table_name('labels')."
					             WHERE sid=$surveyid 
								 AND ".db_table_name('labels').".lid=".db_table_name('questions').".lid
								 AND ".db_table_name('labels').".language='".GetbaseLanguageFromSurveyid($surveyid)."' 
					             AND ".db_table_name('questions').".qid=".$arow['qid']."
					             ORDER BY ".db_table_name('labels').".sortorder, ".db_table_name('labels').".title";
					$ab2result=db_execute_assoc($ab2query) or die("Couldn't get list of labels in createFieldMap function (case :)$ab2query".htmlspecialchars($connection->ErrorMsg()));
					while($ab2row=$ab2result->FetchRow())
					{
						$lset[]=$ab2row;
					}
					while ($abrow=$abresult->FetchRow())
					{
						foreach($lset as $ls)
						{
							$createsurvey .= "  `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}_{$ls['code']}` X,\n";
						}
					}
					unset($lset);
				}
				elseif ($arow['type'] == "Q")
				{
					$abquery = "SELECT a.*, q.other FROM {$dbprefix}answers as a, {$dbprefix}questions as q WHERE a.qid=q.qid AND sid={$surveyid} AND q.qid={$arow['qid']} "
					." AND a.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." AND q.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." ORDER BY a.sortorder, a.answer";
					$abresult=db_execute_assoc($abquery) or $this->debugLsrc ("Couldn't get perform answers query$abquery".$connect->ErrorMsg());
					while ($abrow = $abresult->FetchRow())
					{
						$createsurvey .= "  `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}`";
						if ($databasetype=='mysql' || $databasetype=='mysqli')
						{
							$createsurvey .= " X";
						}
						else
						{
							$createsurvey .= " C(255)";
						}
						$createsurvey .= ",\n";
					}
				}

				elseif ($arow['type'] == "K") //Multiple Numeric - replica of multiple short text, except numbers only
				{
					$abquery = "SELECT a.*, q.other FROM {$dbprefix}answers as a, {$dbprefix}questions as q WHERE a.qid=q.qid AND sid={$surveyid} AND q.qid={$arow['qid']} "
					." AND a.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." AND q.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." ORDER BY a.sortorder, a.answer";
					$abresult=db_execute_assoc($abquery) or $this->debugLsrc ("Couldn't get perform answers query$abquery".$connect->ErrorMsg());
					while ($abrow = $abresult->FetchRow())
					{
						$createsurvey .= "  `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}` C(20),\n";
					}
				} //End if ($arow['type'] == "K")
				/*		elseif ($arow['type'] == "J")
				 {
					$abquery = "SELECT {$dbprefix}answers.*, {$dbprefix}questions.other FROM {$dbprefix}answers, {$dbprefix}questions WHERE {$dbprefix}answers.qid={$dbprefix}questions.qid AND sid={$surveyid} AND {$dbprefix}questions.qid={$arow['qid']} ORDER BY {$dbprefix}answers.sortorder, {$dbprefix}answers.answer";
					$abresult=db_execute_assoc($abquery) or $this->debugLsrc ("Couldn't get perform answers query$abquery".$connect->ErrorMsg());
					while ($abrow = $abresultt->FetchRow())
					{
					$createsurvey .= "  `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}` C(5),\n";
					}
					}*/
				elseif ($arow['type'] == "R")
				{
					//MULTI ENTRY
					$abquery = "SELECT a.*, q.other FROM {$dbprefix}answers as a, {$dbprefix}questions as q"
					." WHERE a.qid=q.qid AND sid={$surveyid} AND q.qid={$arow['qid']} "
					." AND a.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." AND q.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." ORDER BY a.sortorder, a.answer";
					$abresult=$connect->Execute($abquery) or $this->debugLsrc ("Couldn't get perform answers query$abquery".$connect->ErrorMsg());
					$abcount=$abresult->RecordCount();
					for ($i=1; $i<=$abcount; $i++)
					{
						$createsurvey .= "  `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}$i` C(5),\n";
					}
				}
				elseif ($arow['type'] == "1")
				{
					$abquery = "SELECT a.*, q.other FROM {$dbprefix}answers as a, {$dbprefix}questions as q"
					." WHERE a.qid=q.qid AND sid={$surveyid} AND q.qid={$arow['qid']} "
					." AND a.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." AND q.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
					." ORDER BY a.sortorder, a.answer";
					$abresult=db_execute_assoc($abquery) or $this->debugLsrc ("Couldn't get perform answers query$abquery".$connect->ErrorMsg());
					$abcount=$abresult->RecordCount();
					while ($abrow = $abresult->FetchRow())
					{
						$abmultiscalequery = "SELECT a.*, q.other FROM {$dbprefix}answers as a, {$dbprefix}questions as q, {$dbprefix}labels as l"
						." WHERE a.qid=q.qid AND sid={$surveyid} AND q.qid={$arow['qid']} "
						." AND l.lid=q.lid AND sid={$surveyid} AND q.qid={$arow['qid']} AND l.title = '' "
						." AND l.language='".GetbaseLanguageFromSurveyid($surveyid). "' "
						." AND q.language='".GetbaseLanguageFromSurveyid($surveyid). "' ";
						$abmultiscaleresult=$connect->Execute($abmultiscalequery) or $this->debugLsrc ("Couldn't get perform answers query$abmultiscalequery".$connect->ErrorMsg());
						$abmultiscaleresultcount =$abmultiscaleresult->RecordCount();
						$abmultiscaleresultcount = 1;
						for ($j=0; $j<=$abmultiscaleresultcount; $j++)
						{
							$createsurvey .= "  `{$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}#$j` C(5),\n";
						}
					}
				}
			}
				
			// If last question is of type MCABCEFHP^QKJR let's get rid of the ending coma in createsurvey
			$createsurvey = rtrim($createsurvey, ",\n")."\n"; // Does nothing if not ending with a comma
			$tabname = "{$dbprefix}survey_{$surveyid}"; # not using db_table_name as it quotes the table name (as does CreateTableSQL)

			$taboptarray = array('mysql' => 'ENGINE='.$databasetabletype.'  CHARACTER SET utf8 COLLATE utf8_unicode_ci',
                                 'mysqli' => 'ENGINE='.$databasetabletype.'  CHARACTER SET utf8 COLLATE utf8_unicode_ci');
			$dict = NewDataDictionary($connect);
			$sqlarray = $dict->CreateTableSQL($tabname, $createsurvey, $taboptarray);
			$execresult=$dict->ExecuteSQLArray($sqlarray,1);
			if ($execresult==0 || $execresult==1)
			{
				//		$activateoutput .= "\n<table width='350' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n" .
				//		"<tr bgcolor='#555555'><td height='4'><font size='1' face='verdana' color='white'><strong>".("Activate Survey")." ($surveyid)</strong></font></td></tr>\n" .
				//		"<tr><td>\n" .
				//		"<font color='red'>".("Survey could not be actived.")."</font>\n" .
				//		"<center><a href='$scriptname?sid={$surveyid}'>".("Main Admin Screen")."</a></center>\n" .
				//		"DB ".("Error").":\n<font color='red'>" . $connect->ErrorMsg() . "</font>\n" .
				//		"<pre>$createsurvey</pre>\n" .
				//		"</td></tr></table></br>&nbsp;\n" .
				//		"</body>\n</html>";
			}
			if ($execresult != 0 && $execresult !=1)
			{
				$anquery = "SELECT autonumber_start FROM {$dbprefix}surveys WHERE sid={$surveyid}";
				if ($anresult=db_execute_assoc($anquery))
				{
					//if there is an autonumber_start field, start auto numbering here
					while($row=$anresult->FetchRow())
					{
						if ($row['autonumber_start'] > 0)
						{
							$autonumberquery = "ALTER TABLE {$dbprefix}survey_{$surveyid} AUTO_INCREMENT = ".$row['autonumber_start'];
							if ($result = $connect->Execute($autonumberquery))
							{
								//We're happy it worked!
							}
							else
							{
								//Continue regardless - it's not the end of the world
							}
						}
					}
				}

				//$activateoutput .= "\n<table class='alertbox'>\n";
				//$activateoutput .= "\t\t\t\t<tr><td height='4'><strong>".("Activate Survey")." ($surveyid)</td></tr>\n";
				//$activateoutput .= "\t\t\t\t<tr><td align='center'><font class='successtitle'>".("Survey has been activated. Results table has been successfully created.")."</font>\n";
				$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
				$acquery = "UPDATE {$dbprefix}surveys SET active='Y' WHERE sid=".$surveyid;
				$acresult = $connect->Execute($acquery);
				$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", FERTIG ");
			}

		}
		return true;
	} // end activateSurvey();

	/**
	 * not used, a test, thought this could maybe enhance security, may be deleted
	 *
	 * @return Error 404 fake
	 */
	function fake404()// XXX
	{
		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" lang="de" xml:lang="de">
	<head>
	<title>Objekt nicht gefunden!</title>
	<link rev="made" href="" />
	<style type="text/css"><!--/*--><![CDATA[/*><!--*/ 
	    body { color: #000000; background-color: #FFFFFF; }
	    a:link { color: #0000CC; }
	    p, address {margin-left: 3em;}
	    span {font-size: smaller;}
	/*]]>*/--></style>
	</head>
	
	<body>
	<h1>Objekt nicht gefunden!</h1>
	<p>
	
	
	    Der angeforderte URL konnte auf dem Server nicht gefunden werden.
	
	  
	
	    Sofern Sie den URL manuell eingegeben haben,
	    &uuml;berpr&uuml;fen Sie bitte die Schreibweise und versuchen Sie es erneut.
	
	  
	
	</p>
	<p>
	Sofern Sie dies f&uuml;r eine Fehlfunktion des Servers halten,
	informieren Sie bitte den 
	<a href="mailto:webmaster@'.$_SERVER["SERVER_NAME"].'">Webmaster</a>
	hier&uuml;ber.
	
	</p>
	
	<h2>Error 404</h2>
	<address>
	
	  <a href="/">'.$_SERVER["SERVER_NAME"].'</a>
	  
	  <span>'.date("m/d/Y H:i:s").'
	  Apache/2.2.9 (Win32) DAV/2 mod_ssl/2.2.9 OpenSSL/0.9.8i mod_autoindex_color PHP/5.2.6 mod_jk/1.2.26</span>
	</address>
	</body>
	</html>
	
	';
	}

	/**
	 * importing a group into an existing survey
	 *
	 * @param int $iVid SurveyID
	 * @param string $sMod Group that should be loaded into the Survey
	 */
	function importGroup($surveyid, $sMod) //XXX
	{
		global $connect ;
		//		global $dbprefix ;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		include("lsrc.config.php");
		$newsid = $surveyid;

		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");

		$the_full_file_path = $modDir.$sMod.".csv";

		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK $the_full_file_path ");

		$handle = fopen($the_full_file_path, "r");
		while (!feof($handle))
		{
			$buffer = fgets($handle);
			$bigarray[] = $buffer;
		}
		fclose($handle);

		if (substr($bigarray[0], 0, 23) != "# LimeSurvey Group Dump" && substr($bigarray[0], 0, 24) != "# PHPSurveyor Group Dump")
		{
			//$importgroup .= "<strong><font color='red'>".("Error")."</font></strong>\n";
			//$importgroup .= ("This file is not a LimeSurvey group file. Import failed.")."\n";
			//$importgroup .= "<input type='submit' value='".("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_top')\">\n";
			//$importgroup .= "</td></tr></table>\n";
			//unlink($the_full_file_path);
			return false;
		}

		for ($i=0; $i<9; $i++)
		{
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);

		//GROUPS
		if (array_search("# QUESTIONS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# QUESTIONS TABLE\n", $bigarray);
		}
		elseif (array_search("# QUESTIONS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# QUESTIONS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$grouparray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//QUESTIONS
		if (array_search("# ANSWERS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# ANSWERS TABLE\n", $bigarray);
		}
		elseif (array_search("# ANSWERS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# ANSWERS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$questionarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//ANSWERS
		if (array_search("# CONDITIONS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# CONDITIONS TABLE\n", $bigarray);
		}
		elseif (array_search("# CONDITIONS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# CONDITIONS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$answerarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//CONDITIONS
		if (array_search("# LABELSETS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# LABELSETS TABLE\n", $bigarray);
		}
		elseif (array_search("# LABELSETS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# LABELSETS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray);
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$conditionsarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//LABELSETS
		if (array_search("# LABELS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# LABELS TABLE\n", $bigarray);
		}
		elseif (array_search("# LABELS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# LABELS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$labelsetsarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//LABELS
		if (array_search("# QUESTION_ATTRIBUTES TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# QUESTION_ATTRIBUTES TABLE\n", $bigarray);
		}
		elseif (array_search("# QUESTION_ATTRIBUTES TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# QUESTION_ATTRIBUTES TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$labelsarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//LAST LOT (now question_attributes)
		if (!isset($noconditions) || $noconditions != "Y")
		{
			// stoppoint is the last line number
			// this is an empty line after the QA CSV lines
			$stoppoint = count($bigarray)-1;
			for ($i=0; $i<=$stoppoint+1; $i++)
			{
				if ($i<=$stoppoint-1) {$question_attributesarray[] = $bigarray[$i];}
				unset($bigarray[$i]);
			}
		}
		$bigarray = array_values($bigarray);

		$countgroups=0;
		if (isset($questionarray))
		{
			$questionfieldnames=convertCSVRowToArray($questionarray[0],',','"');
			unset($questionarray[0]);
			$countquestions = 0;
		}

		if (isset($answerarray))
		{
			$answerfieldnames=convertCSVRowToArray($answerarray[0],',','"');
			unset($answerarray[0]);
			$countanswers = 0;
		}

		$countconditions = 0;
		$countlabelsets=0;
		$countlabels=0;
		$countquestion_attributes = 0;
		$countanswers = 0;

		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		// first check that imported group, questions and labels support the
		// current survey's baselang
		$langcode = GetBaseLanguageFromSurveyID($newsid);
		if (isset($grouparray))
		{
			$groupfieldnames = convertCSVRowToArray($grouparray[0],',','"');
			$langfieldnum = array_search("language", $groupfieldnames);
			$gidfieldnum = array_search("gid", $groupfieldnames);
			$groupssupportbaselang = bDoesImportarraySupportsLanguage($grouparray,Array($gidfieldnum),$langfieldnum,$langcode,true);
			if (!$groupssupportbaselang)
			{
				//$importgroup .= "<strong><font color='red'>".("Error")."</font></strong>\n";
				//$importgroup .= ("You can't import a group which doesn't support the current survey's base language.")."\n";
				//$importgroup .= "<input type='submit' value='".("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_top')\">\n";
				//$importgroup .= "</td></tr></table>\n";
				//unlink($the_full_file_path);
				return "Group does not support Surveys Baselanguage ($langcode)";
			}
		}
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		if (isset($questionarray))
		{
			$langfieldnum = array_search("language", $questionfieldnames);
			$qidfieldnum = array_search("qid", $questionfieldnames);
			$questionssupportbaselang = bDoesImportarraySupportsLanguage($questionarray,Array($qidfieldnum), $langfieldnum,$langcode,false);
			if (!$questionssupportbaselang)
			{
				//$importgroup .= "<strong><font color='red'>".("Error")."</font></strong>\n";
				//$importgroup .= ("You can't import a question which doesn't support the current survey's base language.")."\n";
				//$importgroup .= "<input type='submit' value='".("Main Admin Screen")."' onclick=\"window.open('$scriptname', '_top')\">\n";
				//$importgroup .= "</td></tr></table>\n";
				//unlink($the_full_file_path);
				return "Group does not support Surveys Baselanguage ($langcode)";
			}
		}

		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		if (isset($labelsetsarray))
		{
			$labelsetfieldname = convertCSVRowToArray($labelsetsarray[0],',','"');
			$langfieldnum = array_search("languages", $labelsetfieldname);
			$lidfilednum =  array_search("lid", $labelsetfieldname);
			$labelsetssupportbaselang = bDoesImportarraySupportsLanguage($labelsetsarray,Array($lidfilednum),$langfieldnum,$langcode,true);
			if (!$labelsetssupportbaselang)
			{
				$importquestion .= "<strong><font color='red'>".("Error")."</font></strong>\n"
				.("You can't import label sets which don't support the current survey's base language")."\n"
				."</td></tr></table>\n";
				//unlink($the_full_file_path);
				return "Group does not support Surveys Baselanguage ($langcode)";
			}
		}

		$newlids = array(); // this array will have the "new lid" for the label sets, the key will be the "old lid"
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//DO ANY LABELSETS FIRST, SO WE CAN KNOW WHAT THEIR NEW LID IS FOR THE QUESTIONS
		if (isset($labelsetsarray) && $labelsetsarray) {
			$csarray=buildLabelSetCheckSumArray();   // build checksums over all existing labelsets
			$count=0;
			foreach ($labelsetsarray as $lsa) {
				$fieldorders  =convertCSVRowToArray($labelsetsarray[0],',','"');
				$fieldcontents=convertCSVRowToArray($lsa,',','"');
				if ($count==0) {$count++; continue;}

				$countlabelsets++;

				$labelsetrowdata=array_combine($fieldorders,$fieldcontents);

				// Save old labelid
				$oldlid=$labelsetrowdata['lid'];
				// set the new language
				unset($labelsetrowdata['lid']);
				$newvalues=array_values($labelsetrowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
				$lsainsert = "INSERT INTO {$dbprefix}labelsets (".implode(',',array_keys($labelsetrowdata)).") VALUES (".implode(',',$newvalues).")"; //handle db prefix
				$lsiresult=$connect->Execute($lsainsert);

				// Get the new insert id for the labels inside this labelset
				$newlid=$connect->Insert_ID("{$dbprefix}labelsets",'lid');

				if ($labelsarray) {
					$count=0;
					foreach ($labelsarray as $la) {
						$lfieldorders  =convertCSVRowToArray($labelsarray[0],',','"');
						$lfieldcontents=convertCSVRowToArray($la,',','"');
						if ($count==0) {$count++; continue;}

						// Combine into one array with keys and values since its easier to handle
						$labelrowdata=array_combine($lfieldorders,$lfieldcontents);
						$labellid=$labelrowdata['lid'];
						if ($labellid == $oldlid) {
							$labelrowdata['lid']=$newlid;

							// translate internal links
							$labelrowdata['title']=translink('label', $oldlid, $newlid, $labelrowdata['title']);

							$newvalues=array_values($labelrowdata);
							$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
							$lainsert = "INSERT INTO {$dbprefix}labels (".implode(',',array_keys($labelrowdata)).") VALUES (".implode(',',$newvalues).")"; //handle db prefix
							$liresult=$connect->Execute($lainsert);
							$countlabels++;
						}
					}
				}

				//CHECK FOR DUPLICATE LABELSETS
				$thisset="";
				$query2 = "SELECT code, title, sortorder, language
		                   FROM {$dbprefix}labels
		                   WHERE lid=".$newlid."
		                   ORDER BY language, sortorder, code";    
				$result2 = db_execute_num($query2) or $this->debugLsrc("Died querying labelset $lid$query2".$connect->ErrorMsg());
				while($row2=$result2->FetchRow())
				{
					$thisset .= implode('.', $row2);
				} // while
				$newcs=dechex(crc32($thisset)*1);
				unset($lsmatch);
				if (isset($csarray))
				{
					foreach($csarray as $key=>$val)
					{
						if ($val == $newcs)
						{
							$lsmatch=$key;
						}
					}
				}
				if (isset($lsmatch))
				{
					//There is a matching labelset. So, we will delete this one and refer
					//to the matched one.
					$query = "DELETE FROM {$dbprefix}labels WHERE lid=$newlid";
					$result=$connect->Execute($query) or $this->debugLsrc("Couldn't delete labels$query".$connect->ErrorMsg());
					$query = "DELETE FROM {$dbprefix}labelsets WHERE lid=$newlid";
					$result=$connect->Execute($query) or $this->debugLsrc("Couldn't delete labelset$query".$connect->ErrorMsg());
					$newlid=$lsmatch;
				}
				else
				{
					//There isn't a matching labelset, add this checksum to the $csarray array
					$csarray[$newlid]=$newcs;
				}
				//END CHECK FOR DUPLICATES
				$labelreplacements[]=array($oldlid, $newlid);
				$newlids[$oldlid] = $newlid;
			}
		}
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//these arrays will aloud to insert correctly groups an questions multi languague survey imports correctly, and will eliminate the need to "searh" the imported data
		//$newgids = array(); // this array will have the "new gid" for the groups, the kwy will be the "old gid"    <-- not needed when importing groups
		$newqids = array(); // this array will have the "new qid" for the questions, the kwy will be the "old qid"

		// DO GROUPS, QUESTIONS FOR GROUPS, THEN ANSWERS FOR QUESTIONS IN A __NOT__ NESTED FORMAT!
		if (isset($grouparray) && $grouparray)
		{
			$surveylanguages=GetAdditionalLanguagesFromSurveyID($surveyid);
			$surveylanguages[]=GetBaseLanguageFromSurveyID($surveyid);

			// do GROUPS
			$gafieldorders=convertCSVRowToArray($grouparray[0],',','"');
			unset($grouparray[0]);
			$newgid = 0;
			$group_order = 0;   // just to initialize this variable
			foreach ($grouparray as $ga)
			{
				//GET ORDER OF FIELDS
				$gacfieldcontents=convertCSVRowToArray($ga,',','"');
				$grouprowdata=array_combine($gafieldorders,$gacfieldcontents);

				// Skip not supported languages
				if (!in_array($grouprowdata['language'],$surveylanguages))
				{
					$skippedlanguages[]=$grouprowdata['language'];  // this is for the message in the end.
					continue;
				}

				// replace the sid
				$oldsid=$grouprowdata['sid'];
				$grouprowdata['sid']=$newsid;

				// replace the gid  or remove it if needed (it also will calculate the group order if is a new group)
				$oldgid=$grouprowdata['gid'];
				if ($newgid == 0)
				{
					unset($grouprowdata['gid']);

					// find the maximum group order and use this grouporder+1 to assign it to the new group
					$qmaxgo = "select max(group_order) as maxgo from ".db_table_name('groups')." where sid=$newsid";
					$gres = db_execute_assoc($qmaxgo) or $this->debugLsrc (("Error")." Failed to find out maximum group order value\n$qmaxqo\n".$connect->ErrorMsg());
					$grow=$gres->FetchRow();
					$group_order = $grow['maxgo']+1;
				}
				else
				$grouprowdata['gid'] = $newgid;

				$grouprowdata["group_order"]= $group_order;

				// Everything set - now insert it
				$grouprowdata=array_map('convertCsvreturn2return', $grouprowdata);


				// translate internal links
				$grouprowdata['group_name']=translink('survey', $oldsid, $newsid, $grouprowdata['group_name']);
				$grouprowdata['description']=translink('survey', $oldsid, $newsid, $grouprowdata['description']);

				$newvalues=array_values($grouprowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
				$ginsert = "insert INTO {$dbprefix}groups (".implode(',',array_keys($grouprowdata)).") VALUES (".implode(',',$newvalues).")";
				$gres = $connect->Execute($ginsert) or $this->debugLsrc("Error: ".": Failed to insert group\n$ginsert\n".$connect->ErrorMsg());

				//GET NEW GID  .... if is not done before and we count a group if a new gid is required
				if ($newgid == 0)
				{
					$newgid = $connect->Insert_ID("{$dbprefix}groups",'gid');
					$countgroups++;
				}
			}
			// GROUPS is DONE

			// do QUESTIONS
			if (isset($questionarray) && $questionarray)
			{
				foreach ($questionarray as $qa)
				{
					$qacfieldcontents=convertCSVRowToArray($qa,',','"');
					$questionrowdata=array_combine($questionfieldnames,$qacfieldcontents);

					// Skip not supported languages
					if (!in_array($questionrowdata['language'],$surveylanguages))
					continue;

					// replace the sid
					$questionrowdata["sid"] = $newsid;

					// replace the gid (if the gid is not in the oldgid it means there is a problem with the exported record, so skip it)
					if ($questionrowdata['gid'] == $oldgid)
					$questionrowdata['gid'] = $newgid;
					else
					continue; // a problem with this question record -> don't consider

					// replace the qid or remove it if needed
					$oldqid = $questionrowdata['qid'];
					if (isset($newqids[$oldqid]))
					$questionrowdata['qid'] = $newqids[$oldqid];
					else
					unset($questionrowdata['qid']);

					// replace the lid for the new one (if there is no new lid in the $newlids array it mean that was not imported -> error, skip this record)
					if (in_array($questionrowdata["type"], array("F","H","W","Z", "1", ":", ";")))      // only fot the questions that uses a label set.
					if (isset($newlids[$questionrowdata["lid"]]))
					{
						$questionrowdata["lid"] = $newlids[$questionrowdata["lid"]];
						if(isset($newlids[$questionrowdata["lid1"]]))
						{
							$questionrowdata["lid1"] = $newlids[$questionrowdata["lid1"]];
						}
					}
					else
					{
						continue; // a problem with this question record -> don't consider
					}
					//            $other = $questionrowdata["other"]; //Get 'other' field value
					//            $oldlid = $questionrowdata['lid'];

					// Everything set - now insert it
					$questionrowdata=array_map('convertCsvreturn2return', $questionrowdata);

					// translate internal links ///XXX rakete may change question data here
					//				$questionrowdata['title']=translink('survey', $oldsid, $newsid, $questionrowdata['title']);
					//				$questionrowdata['question']=translink('survey', $oldsid, $newsid, $questionrowdata['question']);
					//				$questionrowdata['help']=translink('survey', $oldsid, $newsid, $questionrowdata['help']);

					$newvalues=array_values($questionrowdata);
					$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
					$qinsert = "insert INTO {$dbprefix}questions (".implode(',',array_keys($questionrowdata)).") VALUES (".implode(',',$newvalues).")";
					$qres = $connect->Execute($qinsert) or $this->debugLsrc ("Error: "."Failed to insert question\n$qinsert\n".$connect->ErrorMsg());

					//GET NEW QID  .... if is not done before and we count a question if a new qid is required
					if (!isset($newqids[$oldqid]))
					{
						$newqids[$oldqid] = $connect->Insert_ID("{$dbprefix}questions",'qid');
						$myQid=$newqids[$oldqid];
						$countquestions++;
					}
					else
					{
						$myQid=$newqids[$oldqid];
					}
				}
			}
			// QESTIONS is DONE
			$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
			// do ANSWERS
			if (isset($answerarray) && $answerarray)
			{
				foreach ($answerarray as $aa)
				{
					$aacfieldcontents=convertCSVRowToArray($aa,',','"');
					$answerrowdata=array_combine($answerfieldnames,$aacfieldcontents);

					// Skip not supported languages
					if (!in_array($answerrowdata['language'],$surveylanguages))
					continue;

					// replace the qid for the new one (if there is no new qid in the $newqids array it mean that this answer is orphan -> error, skip this record)
					if (isset($newqids[$answerrowdata["qid"]]))
					$answerrowdata["qid"] = $newqids[$answerrowdata["qid"]];
					else
					continue; // a problem with this answer record -> don't consider

					// Everything set - now insert it
					$answerrowdata = array_map('convertCsvreturn2return', $answerrowdata);

					// translate internal links
					$answerrowdata['answer']=translink('survey', $oldsid, $newsid, $answerrowdata['answer']);

					$newvalues=array_values($answerrowdata);
					$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
					$ainsert = "insert INTO {$dbprefix}answers (".implode(',',array_keys($answerrowdata)).") VALUES (".implode(',',$newvalues).")";
					$ares = $connect->Execute($ainsert) or $this->debugLsrc ("Error: "."Failed to insert answer\n$ainsert\n".$connect->ErrorMsg());
					$countanswers++;
				}
			}
			// ANSWERS is DONE
			$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
			// Fix Group sortorder
			//fixsortorderGroups(); //XXX commented out by rakete... and written in full length
			$baselang = GetBaseLanguageFromSurveyID($surveyid);
			$cdresult = db_execute_assoc("SELECT gid FROM ".db_table_name('groups')." WHERE sid='{$surveyid}' AND language='{$baselang}' ORDER BY group_order, group_name");
			$position=0;
			while ($cdrow=$cdresult->FetchRow())
			{
				$cd2query="UPDATE ".db_table_name('groups')." SET group_order='{$position}' WHERE gid='{$cdrow['gid']}' ";
				$cd2result = $connect->Execute($cd2query) or $this->debugLsrc ("Couldn't update group_order$cd2query".$connect->ErrorMsg());  //Checked
				$position++;
			}
			 
			 
			//... and for the questions inside the groups
			// get all group ids and fix questions inside each group
			$gquery = "SELECT gid FROM {$dbprefix}groups where sid=$newsid group by gid ORDER BY gid"; //Get last question added (finds new qid)
			$gres = db_execute_assoc($gquery);
			while ($grow = $gres->FetchRow())
			{
				//fixsortorderQuestions(0,$grow['gid']);
				$qid=sanitize_int(0);
				$gid=sanitize_int($grow['gid']);
				$baselang = GetBaseLanguageFromSurveyID($surveyid);
				if ($gid == 0)
				{
					$result = db_execute_assoc("SELECT gid FROM ".db_table_name('questions')." WHERE qid='{$qid}' and language='{$baselang}'");  //Checked
					$row=$result->FetchRow();
					$gid=$row['gid'];
				}
				$cdresult = db_execute_assoc("SELECT qid FROM ".db_table_name('questions')." WHERE gid='{$gid}' and language='{$baselang}' ORDER BY question_order, title ASC");      //Checked
				$position=0;
				while ($cdrow=$cdresult->FetchRow())
				{
					$cd2query="UPDATE ".db_table_name('questions')." SET question_order='{$position}' WHERE qid='{$cdrow['qid']}' ";
					$cd2result = $connect->Execute($cd2query) or $this->debugLsrc ("Couldn't update question_order$cd2query".$connect->ErrorMsg());    //Checked
					$position++;
				}
			}
		}
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		// do ATTRIBUTES
		if (isset($question_attributesarray) && $question_attributesarray)
		{
			$fieldorders  =convertCSVRowToArray($question_attributesarray[0],',','"');
			unset($question_attributesarray[0]);
			foreach ($question_attributesarray as $qar) {
				$fieldcontents=convertCSVRowToArray($qar,',','"');
				$qarowdata=array_combine($fieldorders,$fieldcontents);

				// replace the qid for the new one (if there is no new qid in the $newqids array it mean that this attribute is orphan -> error, skip this record)
				if (isset($newqids[$qarowdata["qid"]]))
				$qarowdata["qid"] = $newqids[$qarowdata["qid"]];
				else
				continue; // a problem with this answer record -> don't consider

				unset($qarowdata["qaid"]);

				// Everything set - now insert it
				$newvalues=array_values($qarowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
				$qainsert = "insert INTO {$dbprefix}question_attributes (".implode(',',array_keys($qarowdata)).") VALUES (".implode(',',$newvalues).")";
				$result=$connect->Execute($qainsert) or $this->debugLsrc ("Couldn't insert question_attribute$qainsert".$connect->ErrorMsg());
				$countquestion_attributes++;
			}
		}
		// ATTRIBUTES is DONE
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		// do CONDITIONS
		if (isset($conditionsarray) && $conditionsarray)
		{
			$fieldorders=convertCSVRowToArray($conditionsarray[0],',','"');
			unset($conditionsarray[0]);
			foreach ($conditionsarray as $car) {
				$fieldcontents=convertCSVRowToArray($car,',','"');
				$conditionrowdata=array_combine($fieldorders,$fieldcontents);

				$oldqid = $conditionrowdata["qid"];
				$oldcqid = $conditionrowdata["cqid"];

				// replace the qid for the new one (if there is no new qid in the $newqids array it mean that this condition is orphan -> error, skip this record)
				if (isset($newqids[$oldqid]))
				$conditionrowdata["qid"] = $newqids[$oldqid];
				else
				continue; // a problem with this answer record -> don't consider

				// replace the cqid for the new one (if there is no new qid in the $newqids array it mean that this condition is orphan -> error, skip this record)
				if (isset($newqids[$oldcqid]))
				$conditionrowdata["cqid"] = $newqids[$oldcqid];
				else
				continue; // a problem with this answer record -> don't consider

				list($oldcsid, $oldcgid, $oldqidanscode) = explode("X",$conditionrowdata["cfieldname"],3);

				if ($oldcgid != $oldgid)    // this means that the condition is in another group (so it should not have to be been exported -> skip it
				continue;

				unset($conditionrowdata["cid"]);

				// recreate the cfieldname with the new IDs
				$newcfieldname = $newsid . "X" . $newgid . "X" . $conditionrowdata["cqid"] .substr($oldqidanscode,strlen($oldqid));

				$conditionrowdata["cfieldname"] = $newcfieldname;
				if (!isset($conditionrowdata["method"]) || trim($conditionrowdata["method"])=='')
				{
					$conditionrowdata["method"]='==';
				}
				$newvalues=array_values($conditionrowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
				$conditioninsert = "insert INTO {$dbprefix}conditions (".implode(',',array_keys($conditionrowdata)).") VALUES (".implode(',',$newvalues).")";
				$result=$connect->Execute($conditioninsert) or $this->debugLsrc ("Couldn't insert condition$conditioninsert".$connect->ErrorMsg());
				$countconditions++;
			}
		}
		$this->debugLsrc("wir sind in - ".__FUNCTION__." Line ".__LINE__.", FERTIG ");
		// CONDITIONS is DONE
		return array('gid'=>$newgid,'qid'=>$myQid);
		//return $newgid;
	}

	/**
	 *
	 * Enter description here...
	 * @param $surveyid
	 * @param $sMod
	 * @param $newGroup
	 * @return unknown_type
	 */
	function importQuestion($surveyid, $sMod, $newGroup=false) //XXX
	{
		global $connect ;
		//global $dbprefix ;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		include("lsrc.config.php");
		$newsid = $surveyid;

		$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", START OK $dbprefix ");

		//$getGidSql = "SELECT gid FROM {$dbprefix}  ";
		$getGidSql = "SELECT gid
	                   FROM {$dbprefix}groups 
	                   WHERE sid=".$surveyid." AND language='".GetBaseLanguageFromSurveyID($surveyid)."'
	                   ORDER BY gid desc ";    
		$getGidRs = db_execute_num($getGidSql);
		$gidRow=$getGidRs->FetchRow();
		$gid = $gidRow[0];

		if($gid=='')#
		{
			$this->debugLsrc("No Group for importing the question, available!");
			return "No Group for importing the question, available! Import failed.";
		}

		if($newGroup===true)
		++$gid;

		$the_full_file_path = $queDir.$sMod.".csv";

		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK $the_full_file_path ");

		$handle = fopen($the_full_file_path, "r");
		while (!feof($handle))
		{
			$buffer = fgets($handle, 10240); //To allow for very long survey welcomes (up to 10k)
			$bigarray[] = $buffer;
		}
		fclose($handle);

		// Now we try to determine the dataformat of the survey file.
		if ((substr($bigarray[1], 0, 24) == "# SURVEYOR QUESTION DUMP")&& (substr($bigarray[4], 0, 29) == "# http://www.phpsurveyor.org/"))
		{
			$importversion = 100;  // version 1.0 file
		}
		elseif
		((substr($bigarray[1], 0, 24) == "# SURVEYOR QUESTION DUMP")&& (substr($bigarray[4], 0, 37) == "# http://phpsurveyor.sourceforge.net/"))
		{
			$importversion = 99;  // Version 0.99 file or older - carries a different URL
		}
		elseif
		(substr($bigarray[0], 0, 26) == "# LimeSurvey Question Dump" || substr($bigarray[0], 0, 27) == "# PHPSurveyor Question Dump")
		{  // Wow.. this seems to be a >1.0 version file - these files carry the version information to read in line two
			$importversion=substr($bigarray[1], 12, 3);
		}
		else    // unknown file - show error message
		{
			//		      $importquestion .= "<strong><font color='red'>".("Error")."</font></strong>\n";
			//		      $importquestion .= ("This file is not a LimeSurvey question file. Import failed.")."\n";
			//		      $importquestion .= "</font></td></tr></table>\n";
			//		      $importquestion .= "</body>\n</html>\n";
			//		      unlink($the_full_file_path);
			return "This is not a Limesurvey question file. Import failed";
		}

		//		if ($importversion != $dbversionnumber)
		//		{
		////		    $importquestion .= "<strong><font color='red'>".("Error")."</font></strong>\n";
		////		    $importquestion .= ("Sorry, importing questions is limited to the same version. Import failed.")."\n";
		////		    $importquestion .= "</font></td></tr></table>\n";
		////		    $importquestion .= "</body>\n</html>\n";
		////		    unlink($the_full_file_path);
		//		    return;
		//		}
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		for ($i=0; $i<9; $i++) //skipping the first lines that are not needed
		{
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//QUESTIONS
		if (array_search("# ANSWERS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# ANSWERS TABLE\n", $bigarray);
		}
		elseif (array_search("# ANSWERS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# ANSWERS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$questionarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//ANSWERS
		if (array_search("# LABELSETS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# LABELSETS TABLE\n", $bigarray);
		}
		elseif (array_search("# LABELSETS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# LABELSETS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$answerarray[] = str_replace("`default`", "`default_value`", $bigarray[$i]);}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//LABELSETS
		if (array_search("# LABELS TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# LABELS TABLE\n", $bigarray);
		}
		elseif (array_search("# LABELS TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# LABELS TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$labelsetsarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//LABELS
		if (array_search("# QUESTION_ATTRIBUTES TABLE\n", $bigarray))
		{
			$stoppoint = array_search("# QUESTION_ATTRIBUTES TABLE\n", $bigarray);
		}
		elseif (array_search("# QUESTION_ATTRIBUTES TABLE\r\n", $bigarray))
		{
			$stoppoint = array_search("# QUESTION_ATTRIBUTES TABLE\r\n", $bigarray);
		}
		else
		{
			$stoppoint = count($bigarray)-1;
		}
		for ($i=0; $i<=$stoppoint+1; $i++)
		{
			if ($i<$stoppoint-2) {$labelsarray[] = $bigarray[$i];}
			unset($bigarray[$i]);
		}
		$bigarray = array_values($bigarray);
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		//Question_attributes
		if (!isset($noconditions) || $noconditions != "Y")
		{
			$stoppoint = count($bigarray);
			for ($i=0; $i<=$stoppoint+1; $i++)
			{
				if ($i<$stoppoint-1) {$question_attributesarray[] = $bigarray[$i];}
				unset($bigarray[$i]);
			}
		}
		$bigarray = array_values($bigarray);

		if (isset($questionarray)) {$countquestions = count($questionarray)-1;}  else {$countquestions=0;}
		if (isset($answerarray))
		{
			$answerfieldnames=convertCSVRowToArray($answerarray[0],',','"');
			unset($answerarray[0]);
			$countanswers = count($answerarray);
		}
		else {$countanswers=0;}
		if (isset($labelsetsarray)) {$countlabelsets = count($labelsetsarray)-1;}  else {$countlabelsets=0;}
		if (isset($labelsarray)) {$countlabels = count($labelsarray)-1;}  else {$countlabels=0;}
		if (isset($question_attributesarray)) {$countquestion_attributes = count($question_attributesarray)-1;} else {$countquestion_attributes=0;}

		$languagesSupported = array();  // this array will keep all the languages supported for the survey

		// Let's check that imported objects support at least the survey's baselang
		$langcode = GetBaseLanguageFromSurveyID($surveyid);

		$languagesSupported[$langcode] = 1;     // adds the base language to the list of supported languages
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		if ($countquestions > 0)
		{
			$questionfieldnames = convertCSVRowToArray($questionarray[0],',','"');
			$langfieldnum = array_search("language", $questionfieldnames);
			$qidfieldnum = array_search("qid", $questionfieldnames);
			$questionssupportbaselang = bDoesImportarraySupportsLanguage($questionarray,Array($qidfieldnum), $langfieldnum,$langcode,true);
			if (!$questionssupportbaselang)
			{
				//				$importquestion .= "<strong><font color='red'>".("Error")."</font></strong>\n"
				//				.("You can't import a question which doesn't support the current survey's base language")."\n"
				//				."</td></tr></table>\n";
				//				unlink($the_full_file_path);
				return "You can't import a question which doesn't support the current survey's base language";
			}
		}
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		foreach (GetAdditionalLanguagesFromSurveyID($surveyid) as $language)
		{
			$languagesSupported[$language] = 1;
		}

		// Let's assume that if the questions do support tye baselang
		// Then the answers do support it as well.
		// ==> So the following section is commented for now
		//if ($countanswers > 0)
		//{
		//	$langfieldnum = array_search("language", $answerfieldnames);
		//	$answercodefilednum1 =  array_search("qid", $answerfieldnames);
		//	$answercodefilednum2 =  array_search("code", $answerfieldnames);
		//	$answercodekeysarr = Array($answercodefilednum1,$answercodefilednum2);
		//	$answerssupportbaselang = bDoesImportarraySupportsLanguage($answerarray,$answercodekeysarr,$langfieldnum,$langcode);
		//	if (!$answerssupportbaselang)
		//	{
		//		$importquestion .= "<strong><font color='red'>".("Error")."</font></strong>\n"
		//		.("You can't import answers which don't support current survey's base language")."\n"
		//		."</td></tr></table>\n";
		//		return;
		//	}
		//
		//}
		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
		if ($countlabelsets > 0)
		{
			$labelsetfieldname = convertCSVRowToArray($labelsetsarray[0],',','"');
			$langfieldnum = array_search("languages", $labelsetfieldname);
			$lidfilednum =  array_search("lid", $labelsetfieldname);
			$labelsetssupportbaselang = bDoesImportarraySupportsLanguage($labelsetsarray,Array($lidfilednum),$langfieldnum,$langcode,true);
			if (!$labelsetssupportbaselang)
			{
				//				$importquestion .= "<strong><font color='red'>".("Error")."</font></strong>\n"
				//				.("You can't import label sets which don't support the current survey's base language")."\n"
				//				."</td></tr></table>\n";
				//				unlink($the_full_file_path);
				return "You can't import label sets which don't support the current survey's base language";
			}
		}
		// I assume that if a labelset supports the survey's baselang,
		// then it's labels do support it as well

		// GET SURVEY AND GROUP DETAILS
		//$surveyid=$postsid;
		//$gid=$postgid;
		$newsid=$surveyid;
		$newgid=$gid;

		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");

		//DO ANY LABELSETS FIRST, SO WE CAN KNOW WHAT THEIR NEW LID IS FOR THE QUESTIONS
		if (isset($labelsetsarray) && $labelsetsarray) {
			$csarray=buildLabelSetCheckSumArray();   // build checksums over all existing labelsets
			$count=0;
			$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
			foreach ($labelsetsarray as $lsa) {
				$fieldorders  =convertCSVRowToArray($labelsetsarray[0],',','"');
				$fieldcontents=convertCSVRowToArray($lsa,',','"');
				if ($count==0) {$count++; continue;}

				$labelsetrowdata=array_combine($fieldorders,$fieldcontents);

				// Save old labelid
				$oldlid=$labelsetrowdata['lid'];
				// set the new language
				unset($labelsetrowdata['lid']);
				$newvalues=array_values($labelsetrowdata);
				$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
				$lsainsert = "INSERT INTO {$dbprefix}labelsets (".implode(',',array_keys($labelsetrowdata)).") VALUES (".implode(',',$newvalues).")"; //handle db prefix
				$lsiresult=$connect->Execute($lsainsert);

				// Get the new insert id for the labels inside this labelset
				$newlid=$connect->Insert_ID("{$dbprefix}labelsets","lid");

				if ($labelsarray) {
					$count=0;
					foreach ($labelsarray as $la) {
						$lfieldorders  =convertCSVRowToArray($labelsarray[0],',','"');
						$lfieldcontents=convertCSVRowToArray($la,',','"');
						if ($count==0) {$count++; continue;}

						// Combine into one array with keys and values since its easier to handle
						$labelrowdata=array_combine($lfieldorders,$lfieldcontents);
						$labellid=$labelrowdata['lid'];
						if ($labellid == $oldlid) {
							$labelrowdata['lid']=$newlid;

							// translate internal links
							$labelrowdata['title']=translink('label', $oldlid, $newlid, $labelrowdata['title']);

							$newvalues=array_values($labelrowdata);
							$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
							$lainsert = "INSERT INTO {$dbprefix}labels (".implode(',',array_keys($labelrowdata)).") VALUES (".implode(',',$newvalues).")"; //handle db prefix
							$liresult=$connect->Execute($lainsert);
						}
					}
				}

				//CHECK FOR DUPLICATE LABELSETS
				$thisset="";
				$query2 = "SELECT code, title, sortorder, language
		                   FROM {$dbprefix}labels
		                   WHERE lid=".$newlid."
		                   ORDER BY language, sortorder, code";    
				$result2 = db_execute_num($query2) or $this->debugLsrc("Died querying labelset $lid$query2".$connect->ErrorMsg());
				while($row2=$result2->FetchRow())
				{
					$thisset .= implode('.', $row2);
				} // while
				$newcs=dechex(crc32($thisset)*1);
				unset($lsmatch);
				if (isset($csarray))
				{
					foreach($csarray as $key=>$val)
					{
						if ($val == $newcs)
						{
							$lsmatch=$key;
						}
					}
				}
				if (isset($lsmatch))
				{
					//There is a matching labelset. So, we will delete this one and refer
					//to the matched one.
					$query = "DELETE FROM {$dbprefix}labels WHERE lid=$newlid";
					$result=$connect->Execute($query) or $this->debugLsrc("Couldn't delete labels$query".$connect->ErrorMsg());
					$query = "DELETE FROM {$dbprefix}labelsets WHERE lid=$newlid";
					$result=$connect->Execute($query) or $this->debugLsrc("Couldn't delete labelset$query".$connect->ErrorMsg());
					$newlid=$lsmatch;
				}
				else
				{
					//There isn't a matching labelset, add this checksum to the $csarray array
					$csarray[$newlid]=$newcs;
				}
				//END CHECK FOR DUPLICATES
				$labelreplacements[]=array($oldlid, $newlid);
			}
		}

		$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");

		// QUESTIONS, THEN ANSWERS FOR QUESTIONS IN A NESTED FORMAT!
		if (isset($questionarray) && $questionarray) {
			$qafieldorders=convertCSVRowToArray($questionarray[0],',','"');
			unset($questionarray[0]);

			//Assuming we will only import one question at a time we will now find out the maximum question order in this group
			//and save it for later
			$qmaxqo = "SELECT MAX(question_order) AS maxqo FROM ".db_table_name('questions')." WHERE sid=$newsid AND gid=$newgid";
			$qres = db_execute_assoc($qmaxqo) or $this->debugLsrc ("Error: ".": Failed to find out maximum question order value\n$qmaxqo\n".$connect->ErrorMsg());
			$qrow=$qres->FetchRow();
			$newquestionorder=$qrow['maxqo']+1;
				
			$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
				
			foreach ($questionarray as $qa) {
				$qacfieldcontents=convertCSVRowToArray($qa,',','"');
				$newfieldcontents=$qacfieldcontents;
				$questionrowdata=array_combine($qafieldorders,$qacfieldcontents);
				if (isset($languagesSupported[$questionrowdata["language"]]))
				{
					$oldqid = $questionrowdata['qid'];
					$oldsid = $questionrowdata['sid'];
					$oldgid = $questionrowdata['gid'];

					// Remove qid field if there is no newqid; and set it to newqid if it's set
					if (!isset($newqid))
					unset($questionrowdata['qid']);
					else
					$questionrowdata['qid'] = $newqid;

					$questionrowdata["sid"] = $newsid;
					$questionrowdata["gid"] = $newgid;
					$questionrowdata["question_order"] = $newquestionorder;

					$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
					// Now we will fix up the label id
					$type = $questionrowdata["type"]; //Get the type
					if ($type == "F" || $type == "H" || $type == "W" ||
					$type == "Z" || $type == "1" || $type == ":" ||
					$type == ";" )
					{//IF this is a flexible label array, update the lid entry
						 
						$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
						 
						if (isset($labelreplacements)) {
							foreach ($labelreplacements as $lrp) {
								if ($lrp[0] == $questionrowdata["lid"]) {
									$questionrowdata["lid"]=$lrp[1];
								}
								if ($lrp[0] == $questionrowdata["lid1"]) {
									$questionrowdata["lid1"]=$lrp[1];
								}
							}
						}
					}
					$other = $questionrowdata["other"]; //Get 'other' field value
					$oldlid = $questionrowdata["lid"];
					$questionrowdata=array_map('convertCsvreturn2return', $questionrowdata);

					// translate internal links
					$questionrowdata['title']=translink('survey', $oldsid, $newsid, $questionrowdata['title']);
					$questionrowdata['question']=translink('survey', $oldsid, $newsid, $questionrowdata['question']);
					$questionrowdata['help']=translink('survey', $oldsid, $newsid, $questionrowdata['help']);

					$newvalues=array_values($questionrowdata);
					$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
					$qinsert = "INSERT INTO {$dbprefix}questions (".implode(',',array_keys($questionrowdata)).") VALUES (".implode(',',$newvalues).")";
					$qres = $connect->Execute($qinsert) or $this->debugLsrc ("Error: ".": Failed to insert question\n$qinsert\n".$connect->ErrorMsg());

					$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
					// set the newqid only if is not set
					if (!isset($newqid))
					$newqid=$connect->Insert_ID("{$dbprefix}questions","qid");
				}
			}
			$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
			//NOW DO ANSWERS FOR THIS QID - Is called just once and only if there was a question
			if (isset($answerarray) && $answerarray) {
				foreach ($answerarray as $aa) {
					$answerfieldcontents=convertCSVRowToArray($aa,',','"');
					$answerrowdata=array_combine($answerfieldnames,$answerfieldcontents);
					if ($answerrowdata===false)
					{
						$importquestion.=''.("Faulty line in import - fields and data don't match").":".implode(',',$answerfieldcontents);
					}
					if (isset($languagesSupported[$answerrowdata["language"]]))
					{
						$code=$answerrowdata["code"];
						$thisqid=$answerrowdata["qid"];
						$answerrowdata["qid"]=$newqid;

						// translate internal links
						$answerrowdata['answer']=translink('survey', $oldsid, $newsid, $answerrowdata['answer']);

						$newvalues=array_values($answerrowdata);
						$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
						$ainsert = "INSERT INTO {$dbprefix}answers (".implode(',',array_keys($answerrowdata)).") VALUES (".implode(',',$newvalues).")";
						$ares = $connect->Execute($ainsert) or $this->debugLsrc ("Error: ".": Failed to insert answer\n$ainsert\n".$connect->ErrorMsg());
					}
				}
			}
			$this->debugLsrc("wir sind in ".__FILE__." - ".__FUNCTION__." Line ".__LINE__.", OK ");
			// Finally the question attributes - Is called just once and only if there was a question
			if (isset($question_attributesarray) && $question_attributesarray) {//ONLY DO THIS IF THERE ARE QUESTION_ATTRIBUES
				$fieldorders  =convertCSVRowToArray($question_attributesarray[0],',','"');
				unset($question_attributesarray[0]);
				foreach ($question_attributesarray as $qar) {
					$fieldcontents=convertCSVRowToArray($qar,',','"');
					$qarowdata=array_combine($fieldorders,$fieldcontents);
					$qarowdata["qid"]=$newqid;
					unset($qarowdata["qaid"]);

					$newvalues=array_values($qarowdata);
					$newvalues=array_map(array(&$connect, "qstr"),$newvalues); // quote everything accordingly
					$qainsert = "INSERT INTO {$dbprefix}question_attributes (".implode(',',array_keys($qarowdata)).") VALUES (".implode(',',$newvalues).")";
					$result=$connect->Execute($qainsert) or $this->debugLsrc ("Couldn't insert question_attribute$qainsert".$connect->ErrorMsg());
				}
			}

		}
		$this->debugLsrc("wir sind in - ".__FUNCTION__." Line ".__LINE__.", FERTIG ");
		// CONDITIONS is DONE
		return array('gid'=>$newgid,'qid'=>$newqid);
		//return $newgid;
	}

	/**
	 * function to delete a Survey with all questions and answersand Tokentable....
	 *
	 * @param int $surveyid
	 * @return boolean
	 */
	function deleteSurvey($surveyid)
	{
		global $connect ;
		// global $dbprefix ;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		include("lsrc.config.php");
		$this->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", OK ");

		$tablelist = $connect->MetaTables();
		$dict = NewDataDictionary($connect);

		if (in_array("{$dbprefix}survey_$surveyid", $tablelist)) //delete the survey_$surveyid table
		{
			$dsquery = $dict->DropTableSQL("{$dbprefix}survey_$surveyid");
			//$dict->ExecuteSQLArray($sqlarray);
			$dsresult = $dict->ExecuteSQLArray($dsquery);
		}

		if (in_array("{$dbprefix}tokens_$surveyid", $tablelist)) //delete the tokens_$surveyid table
		{
			$dsquery = $dict->DropTableSQL("{$dbprefix}tokens_$surveyid");
			$dsresult = $dict->ExecuteSQLArray($dsquery) or $this->debugLsrc ("Couldn't \"$dsquery\" because ".$connect->ErrorMsg());
		}

		$dsquery = "SELECT qid FROM {$dbprefix}questions WHERE sid=$surveyid";
		$dsresult = db_execute_assoc($dsquery) or $this->debugLsrc ("Couldn't find matching survey to delete: \n $dsquery \n".$connect->ErrorMsg());
		while ($dsrow = $dsresult->FetchRow())
		{
			$asdel = "DELETE FROM {$dbprefix}answers WHERE qid={$dsrow['qid']}";
			$asres = $connect->Execute($asdel);
			$cddel = "DELETE FROM {$dbprefix}conditions WHERE qid={$dsrow['qid']}";
			$cdres = $connect->Execute($cddel) or die();
			$qadel = "DELETE FROM {$dbprefix}question_attributes WHERE qid={$dsrow['qid']}";
			$qares = $connect->Execute($qadel);
		}

		$qdel = "DELETE FROM {$dbprefix}questions WHERE sid=$surveyid";
		$qres = $connect->Execute($qdel);

		$scdel = "DELETE FROM {$dbprefix}assessments WHERE sid=$surveyid";
		$scres = $connect->Execute($scdel);

		$gdel = "DELETE FROM {$dbprefix}groups WHERE sid=$surveyid";
		$gres = $connect->Execute($gdel);

		$slsdel = "DELETE FROM {$dbprefix}surveys_languagesettings WHERE surveyls_survey_id=$surveyid";
		$slsres = $connect->Execute($slsdel);

		$srdel = "DELETE FROM {$dbprefix}surveys_rights WHERE sid=$surveyid";
		$srres = $connect->Execute($srdel);

		$srdel = "DELETE FROM {$dbprefix}saved_control WHERE sid=$surveyid";
		$srres = $connect->Execute($srdel);

		$sdel = "DELETE FROM {$dbprefix}surveys WHERE sid=$surveyid";
		$sres = $connect->Execute($sdel);
		$surveyid=false;

		return true;

	}
	/**
	* This function removes the UTF-8 Byte Order Mark from a string
	* 
	* @param string $str
	* @return string
	*/
	private function removeBOM($str=""){
	        if(substr($str, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
	                $str=substr($str, 3);
	        }
	        return $str;
	} 
	/**
	 * This function pulls a CSV representation of the Field map
	 *
	 * @param mixed $surveyid - the survey ID you want the Fieldmap for
	 * @return String $fieldmap
	 */
	function FieldMap2CSV($surveyid)
	{
		$fields=array("fieldname", "type", "sid", "gid", "qid", "aid",'title','question','group_name','lid','lid1');
		$fieldmap=createFieldMap($surveyid,'full',true);

		$result='"'.implode('","',$fields).'"'."\n";
		foreach ($fieldmap as $entry)
		{

			$destfieldmap=array();
			foreach ($fields as $field)
			{
				if (isset($entry[$field]))
				{
					$destfieldmap[$field]=$entry[$field];
				}
				else
				{
					$destfieldmap[$field]='';
				}
			}
			$entry=array_map('CSVEscape',array_values($destfieldmap));
			$result.=implode(',',$entry)."\n";
		}
		return $result;
	}
	
	private function getqtypelist($SelectedCode = "T", $ReturnType = "array")
	{
		include("lsrc.config.php");
		global $publicurl;
		//global $sourcefrom, $clang;
		
	
			$qtypes = array(
			"1"=>"Array (Flexible Labels) Dual Scale",
			"5"=>"5 Point Choice",
			"A"=>"Array (5 Point Choice)",
			"B"=>"Array (10 Point Choice)",
			"C"=>"Array (Yes/No/Uncertain)",
			"D"=>"Date",
			"E"=>"Array (Increase, Same, Decrease)",
			"F"=>"Array (Flexible Labels)",
			"G"=>"Gender",
			"H"=>"Array (Flexible Labels) by Column",
			"I"=>"Language Switch",
			"K"=>"Multiple Numerical Input",
			"L"=>"List (Radio)",
			"M"=>"Multiple Options",
			"N"=>"Numerical Input",
			"O"=>"List With Comment",
			"P"=>"Multiple Options With Comments",
			"Q"=>"Multiple Short Text",
			"R"=>"Ranking",
			"S"=>"Short Free Text",
			"T"=>"Long Free Text",
			"U"=>"Huge Free Text",
			"W"=>"List (Flexible Labels) (Dropdown)",
			"X"=>"Boilerplate Question",
			"Y"=>"Yes/No",
			"Z"=>"List (Flexible Labels) (Radio)",
			"!"=>"List (Dropdown)",
			":"=>"Array (Multi Flexible) (Numbers)",
			";"=>"Array (Multi Flexible) (Text)",
			);
	        asort($qtypes);
			if ($ReturnType == "array") 
				{return $qtypes;}
	
	
	}
}
?>