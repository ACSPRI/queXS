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
* $Id: lsrc.server.php 7206 2009-07-02 13:51:27Z wahrendorff $
* 
*/

// include the lsrc.config.php
include_once("lsrc.config.php");

// functions helping lsrcService to do some things with Limesurvey (import, activation, checks)
include("lsrc.helper.php");

// translation Object
require_once($rootdir.'/classes/core/language.php');
$clang = new limesurvey_lang($defaultlang);

/**
 * if ?wsdl is set, generate wsdl with correct uri and send it back to whoever requesting
 */
if(isset($_GET['wsdl']))
{
	if($lsrcOverSSL)
		$http = "https://";
	else
		$http = "http://";
		
	$wsdlString = file_get_contents("lsrc_orig.wsdl");
	$wsdlString = str_replace("{lsrclocation}",$http.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'],$wsdlString);
	file_put_contents("lsrc.wsdl",$wsdlString);
	header('Content-type: text/wsdl');
	header('Content-Disposition: attachment; filename=lsrc.wsdl');
}
//we initiate a SoapServer Objekt
if($useCert && $sslCert!=''){
	
	/**
	 * TODO: no documentation in PHP manual... no doc here... Can't tell you what to do in order to get Communication working with fixed Certificates
	 * Can't even say, which certificates, however it says in the docu that this should be a .pem RSA encoded file with .key and .crt together.
	 * 
	 */
	$context = array(
		'ssl'=> array(
				'verify_peer' => false,
				'allow_self_signed'	=> true,
				'local_cert' => $sslCert,
				'passphrase' => 'hisuser',
				'capture_peer_cert' => true
				
			)	
	);
	
  	$stream_context = stream_context_create($context);
	
  	$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_1, 
	 			'stream_context' => $stream_context));
}
else{
	$server = new SoapServer($wsdl, array('soap_version' => SOAP_1_1));
}

	/**
	 * adds the functions to the SoapServer Object, 
	 * 
	 * the sChangeSurvey function should be commented out for productive Use 
	 */
	//$server->addFunction("sChangeSurvey");
	$server->addFunction("sDeleteSurvey");
	$server->addFunction("sActivateSurvey");
	$server->addFunction("sCreateSurvey");
	$server->addFunction("sInsertToken");
	$server->addFunction("sTokenReturn");
	$server->addFunction("sInsertParticipants");
	$server->addFunction("sImportGroup");
	$server->addFunction("sAvailableModules");
	$server->addFunction("sImportQuestion");
	$server->addFunction("sImportMatrix");
	$server->addFunction("sImportFreetext");
	$server->addFunction("sSendEmail");
	$server->addFunction("sGetFieldmap");
	// handle the soap request!
if($enableLsrc==true)
{
	$server->handle();
}
/**
 * 
 * Function to change tables in Limesurvey Database, this is too sensitive for productive use, but useful for development and testing
 * @param $sUser
 * @param $sPass
 * @param $table
 * @param $key
 * @param $value
 * @param $where
 * @param $mode
 * @return unknown_type
 */
function sChangeSurvey($sUser, $sPass, $table, $key, $value, $where, $mode='0') 
{
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	// check for appropriate rights
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	//check for Surveyowner. Only owners and superadmins can change and activate surveys
	if(!$_SESSION['USER_RIGHT_SUPERADMIN']=='1')
	{
		throw new SoapFault("Authentication: ", "You have no right to change Databasetables");
		exit;
	}
	
	return $lsrcHelper->changeTable($table, $key, $value, $where, $mode);
}
/**
 * 
 * Function to send reminder, invitation or custom mails to participants of a specific survey
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @param $type
 * @param $maxLsrcEmails
 * @param $subject
 * @param $emailText
 * @return unknown_type
 */
function sSendEmail($sUser, $sPass, $iVid, $type, $maxLsrcEmails='', $subject='', $emailText='')
{
	global $sitename, $siteadminemail;
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", START OK "); 
	
	// wenn maxmails ber den lsrc gegeben wird das nurtzen, ansonsten die default werte aus der config.php
	if($maxLsrcEmails!='')
	$maxemails = $maxLsrcEmails;
		
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	
	// Check if all mandatory parameters are present, else abort...
	if(!is_int($iVid) || $iVid==0 || $type=='')
	{
		throw new SoapFault("Server: ", "Mandatory Parameters missing");
		exit;
	}
	
	if($type=='custom' && $subject!='' && $emailText!='')
	{
		//GET SURVEY DETAILS not working here... don't know why...
		//$thissurvey=getSurveyInfo($iVid);
		$from = $siteadminemail;
		
				$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", Admin Email: $from ; survey: $iVid  ; dump: ".print_r($thissurvey)."");
		$emquery = "SELECT firstname, lastname, email, token, tid, language";
		//if ($ctfieldcount > 7) {$emquery .= ", attribute_1, attribute_2";}

		$emquery .= " FROM ".db_table_name("tokens_{$iVid}")." WHERE email != '' ";

		if (isset($tokenid)) {$emquery .= " and tid='{$tokenid}'";}
		$tokenoutput .= "\n\n<!-- emquery: $emquery -->\n\n";
		//$emresult = db_select_limit_assoc($emquery,$maxemails);
		$emresult = db_execute_assoc($emquery);
		$emcount = $emresult->RecordCount();
		
		if ($emcount > 0)
		{
			$mailsSend = 0;
			while ($emrow = $emresult->FetchRow())
			{
				if (MailTextMessage($emailText, $subject, $emrow['email'] , $from, $sitename, $ishtml=false, getBounceEmail($iVid)))
				{
					$mailsSend++;
				}
				else
				{
					//$tokenoutput .= ReplaceFields($clang->gT("Email to {FIRSTNAME} {LASTNAME} ({EMAIL}) failed. Error Message:")." ".$maildebug."<br />", $fieldsarray);
					if($n==1)
						$failedAddresses .= ",".$emrow['email'];
					else
					{
						$failedAddresses = $emrow['email'];
						$n=1;
					}	
								
				}
			}
			
		}
		else
		{
			return "No Mails to send";
		}
//		if ($ctcount > $emcount)
//		{
//			$lefttosend = $ctcount-$maxemails;
//
//		}else{$lefttosend = 0;}
								
//		if($maxemails>0)
//		{
//			$returnValue = "".$mailsSend." Mails send. ".$lefttosend." Mails left to send";	
//			if(isset($failedAddresses))
//				$returnValue .= "\nCould not send to: ".$failedAddresses;
//			return $returnValue;
//		}
		
		if(isset($mailsSend))
		{
			$returnValue = "".$mailsSend." Mails send. ";
			if(isset($failedAddresses))
				$returnValue .= "\nCould not send to: ".$failedAddresses;
			return $returnValue;
		}	
	}
	
	if($type=='invite' || $type=='remind')
	{
		$emailSenderReturn = $lsrcHelper->emailSender($iVid, $type, $maxLsrcEmails);
		
		return $emailSenderReturn;

	}
	else
	{
		throw new SoapFault("Type: ", "Wrong send Type given. Possible types are: custom, invite or remind");
		exit;
	}
	
}

/**
 * 
 * Function to activate a survey in the database and change some Values (starttime, endtime. Required parameters are:
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @param $dStart
 * @param $dEnd
 * @return unknown_type
 */
function sActivateSurvey($sUser, $sPass, $iVid, $dStart, $dEnd)
{
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", START OK "); 
	// check for apropriate rights
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	
	// Check if all mandatory parameters are present, else abort...
	if(!is_int($iVid) || $iVid==0)
	{
		throw new SoapFault("Server: ", "Mandatory Parameters missing");
		exit;
	}
	
	// Check if the survey to create already exists. If not, abort with Fault.
	if(!$lsrcHelper->surveyExists($iVid))
	{
		throw new SoapFault("Database: ", "Survey you want to activate does not exists");
		exit;
	}
	
	//check for Surveyowner. Only owners and superadmins can change and activate surveys
	if($lsrcHelper->getSurveyOwner($iVid)!=$_SESSION['loginID'] && !$_SESSION['USER_RIGHT_SUPERADMIN']=='1')
	{
		throw new SoapFault("Authentication: ", "You have no right to change Surveys from other people");
		exit;
	}
	
	if(!$lsrcHelper->activateSurvey($iVid))
	{
		throw new SoapFault("Server: ", "Activation went wrong somehow");
		exit;
	}
	
	if($dStart!='' && substr($dStart,0,10)!='1980-01-01')
	{
		$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", CHANGE start ");
		$lsrcHelper->changeTable('surveys','startdate',$dStart,'sid='.$iVid);
	}
	if($dEnd!='' && substr($dEnd,0,10)!='1980-01-01')
	{
		$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", CHANGE end ");
		$lsrcHelper->changeTable('surveys','expires',$dEnd,'sid='.$iVid);
	}
	
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", aktivierung OK ");
	return $iVid;
}

/**
 * 
 * Function to import a survey into the database and change some Values. Required parameters are:
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @param $sVtit
 * @param $sVbes
 * @param $sVwel
 * @param $sMail
 * @param $sName
 * @param $sUrl
 * @param $sUbes
 * @param $sVtyp
 * @param $autoRd
 * @return unknown_type
 */
function sCreateSurvey($sUser, $sPass, $iVid, $sVtit, $sVbes, $sVwel, $sVend, $sMail, $sName, $sUrl, $sUbes, $sVtyp, $autoRd='N' ) 
{
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.",surveyid=$iVid START OK ");
	
	
	if($sVwel=='')
	{//if no welcometext is given, set this one
		$sVwel	= "Herzlich Willkommen zur Evaluation von \"".$sVtit."\"";
	}
//	if($sVend=='')
//	{//if no endtext is given, set this one
//		$sVend	= "Vielen Dank für Ihre Teilnahme an der Umfrage!";
//	}
	
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{// check for appropriate rights
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	
	if((!is_int($iVid) || $iVid==0) || $sVtit=='' || $sVbes=='')
	{// Check if mandatory parameters are empty, if so-> abort
		throw new SoapFault("Server: ", "Mandatory Parameters missing");
		exit;
	}
	
	if($lsrcHelper->surveyExists($iVid))
	{// Check if the survey to create already exists. If so, abort with Fault.
		throw new SoapFault("Database: ", "Survey already exists");
		exit;
	}
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.",vor import OK ");
	
	
	if($lsrcHelper->importSurvey($iVid, $sVtit , $sVbes, $sVwel, $sUbes, $sVtyp))
	{// if import of survey went ok it returns true, else nothing
		$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.",surveyid=$iVid nach import OK ");
		
		//get the optional data into db
		if($sMail!='')
		{
			$lsrcHelper->changeTable("surveys", "adminemail", $sMail, "sid='$iVid'");
			$lsrcHelper->changeTable("surveys", "bounce_email", $sMail, "sid='$iVid'");
		}
		if($sName!='')
			$lsrcHelper->changeTable("surveys", "admin", $sName, "sid='$iVid'");
		if($sUrl!='')
			$lsrcHelper->changeTable("surveys_languagesettings", "surveyls_url", $sUrl, "surveyls_survey_id='$iVid'");
		if($autoRd=='Y')
			$lsrcHelper->changeTable("surveys", "autoredirect", "Y", "sid='$iVid'");
		if($sVend!='')
			$lsrcHelper->changeTable("surveys_languagesettings", "surveyls_endtext", $sVend, "surveyls_survey_id='$iVid'");
			
		$lsrcHelper->changeTable("surveys", "datecreated", date("Y-m-d"), "sid='$iVid'");
		
		return $iVid;
	}
	else
	{
		throw new SoapFault("Server: ", "Import went wrong somehow");
		exit;
	}
	
}//end of function sCreateSurvey


/**
 * 
 * Function to insert Tokens to an existing Survey, makes it "closed"
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @param $sToken
 * @return unknown_type
 */
function sInsertToken($sUser, $sPass, $iVid, $sToken) 
{
	global $connect ;
	global $dbprefix ;
	//$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", START OK ");

	// check for apropriate rights
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	
	// check if there is a $iVid, else abort
	if(!isset($iVid) || $iVid=='' || $iVid==0 )
	{
		throw new SoapFault("Server: ", "No SurveyId given");
		exit;
	}
	
	// check if the Survey we want to populate with tokens already exists, else -> Fault
	if(!$lsrcHelper->surveyExists($iVid))
	{
		throw new SoapFault("Database: ", "Survey does not exists");
		exit;
	}
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", ckecks ueberstanden ");
	
	// check if the Token table already exists, if not, create it...
	if(!db_tables_exist("{$dbprefix}tokens_".$iVid))
	{
		$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", TOken Table existiert nicht ");
		$createtokentable=
		"tid int I NOTNULL AUTO PRIMARY,\n "
		. "firstname C(40) ,\n "
		. "lastname C(40) ,\n ";
        //MSSQL needs special treatment because of some strangeness in ADODB
        if ($databasetype == 'odbc_mssql' || $databasetype == 'odbtp' || $databasetype == 'mssql_n')
		{
			$createtokentable.= "email C(320) ,\n "
			."emailstatus C(300) DEFAULT 'OK',\n ";
		}
        else
		{
			$createtokentable.= "email X(320) ,\n "
			."emailstatus X(300) DEFAULT 'OK',\n ";
		}
        
		$createtokentable.= "token C(36) ,\n "
		. "language C(25) ,\n "
		. "sent C(17) DEFAULT 'N',\n "
		. "remindersent C(17) DEFAULT 'N',\n "
		. "remindercount int I DEFAULT 0,\n "
		. "completed C(17) DEFAULT 'N',\n "
		. "attribute_1 C(100) ,\n"
		. "attribute_2 C(100) ,\n"
		. "mpid I ";
		
		$tabname = "{$dbprefix}tokens_{$iVid}"; # not using db_table_name as it quotes the table name (as does CreateTableSQL)
        $taboptarray = array('mysql' => 'ENGINE='.$databasetabletype.'  CHARACTER SET utf8 COLLATE utf8_unicode_ci',
                             'mysqli' => 'ENGINE='.$databasetabletype.'  CHARACTER SET utf8 COLLATE utf8_unicode_ci');
		$dict = NewDataDictionary($connect);
		$sqlarray = $dict->CreateTableSQL($tabname, $createtokentable, $taboptarray);
		$execresult=$dict->ExecuteSQLArray($sqlarray, false);
		
		$createtokentableindex = $dict->CreateIndexSQL("{$tabname}_idx", $tabname, array('token'));
		$dict->ExecuteSQLArray($createtokentableindex, false);
	}
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", Token tabelle sollte erstellt sein ");
	#################
	
	//if the lsrcSeperator is not set, set the default seperator, a comma...
	if($sLsrcSeparator=='')
	{
		$sLsrcSeparator = ",";
	}
	
	// prepare to fill the table lime_tokens_*
	// this is sensitiv, if the Seperator is not the defined one, almost everything could happen, BE AWARE OF YOUR SEPERATOR!...
	$asTokens = explode($sLsrcSeparator, $sToken);
	$givenTokenCount = count($asTokens);
	// write the tokens in the token_table
	$insertedTokenCount=0;
	
	foreach($asTokens as $value)
	{
		if($value!='')
		{
			$sInsertToken = "INSERT INTO {$dbprefix}tokens_".$iVid." 
				(token,language) VALUES ('".$value."' , '".$_SESSION['lang']."'); ";
			if(!$connect->Execute($sInsertToken))
			{
				throw new SoapFault("Server: ", "Token could not be inserted");
				exit;
			}
			else
			{
				++$insertedTokenCount;
			}			
		}
	}
	return "".$givenTokenCount." Token given, ".$insertedTokenCount." Token inserted.";
	
} //end of function sInsertToken  

/**
 * TODO: redo better... maybe some other function for compatibility
 * Function to insert Participants data while auto creating tokens for everyone...
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @param $sParticipantData
 * @return unknown_type
 */
function sInsertParticipants($sUser, $sPass, $iVid, $sParticipantData) 
{
	global $connect ;
	global $dbprefix ;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	// check for appropriate rights
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	
	// check if there is a $iVid, else abort
	if(!isset($iVid) || $iVid=='' || $iVid==0 )
	{
		throw new SoapFault("Server: ", "No SurveyId given");
		exit;
	}
	
	// check if the Survey we want to populate with data and tokens already exists, else -> Fault
	if(!$lsrcHelper->surveyExists($iVid))
	{
		throw new SoapFault("Database: ", "Survey does not exists");
		exit;
	}

	// check if the Token table already exists, if not, create it...
	if(!db_tables_exist("{$dbprefix}tokens_".$iVid))
	{
		$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", Token Table existiert nicht ");
		$createtokentable=
		"tid int I NOTNULL AUTO PRIMARY,\n "
		. "firstname C(40) ,\n "
		. "lastname C(40) ,\n ";
        //MSSQL needs special treatment because of some strangeness in ADODB
        if ($databasetype == 'odbc_mssql' || $databasetype == 'odbtp' || $databasetype == 'mssql_n')
		{
			$createtokentable.= "email C(320) ,\n "
			."emailstatus C(300) DEFAULT 'OK',\n ";
		}
        else
		{
			$createtokentable.= "email X(320) ,\n "
			."emailstatus X(300) DEFAULT 'OK',\n ";
		}
        
		$createtokentable.= "token C(36) ,\n "
		. "language C(25) ,\n "
		. "sent C(17) DEFAULT 'N',\n "
		. "remindersent C(17) DEFAULT 'N',\n "
		. "remindercount int I DEFAULT 0,\n "
		. "completed C(17) DEFAULT 'N',\n "
		. "attribute_1 C(255) ,\n"
		. "attribute_2 C(255) ,\n"
		. "mpid I ";
		
		$tabname = "{$dbprefix}tokens_{$iVid}"; # not using db_table_name as it quotes the table name (as does CreateTableSQL)
        $taboptarray = array('mysql' => 'ENGINE='.$databasetabletype.'  CHARACTER SET utf8 COLLATE utf8_unicode_ci',
                             'mysqli' => 'ENGINE='.$databasetabletype.'  CHARACTER SET utf8 COLLATE utf8_unicode_ci');
		$dict = NewDataDictionary($connect);
		$sqlarray = $dict->CreateTableSQL($tabname, $createtokentable, $taboptarray);
		$execresult=$dict->ExecuteSQLArray($sqlarray, false);
		
		$createtokentableindex = $dict->CreateIndexSQL("{$tabname}_idx", $tabname, array('token'));
		$dict->ExecuteSQLArray($createtokentableindex, false);
	}
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", Token tabelle sollte erstellt sein ");
	
	//set the Seperators to default if nothing is set in the lsrc.config.php
	if(!isset($sDatasetSeperator) || $sDatasetSeperator=='')
	{$sDatasetSeperator = "::";}
	if(!isset($sDatafieldSeperator) || $sDatafieldSeperator=='')
	{$sDatafieldSeperator = ";";}
	
	// prepare to fill the table lime_tokens_*
	// this is sensitiv, if the Seperator is not the defined one, almost everything could happen, BE AWARE OF YOUR SEPERATOR!...
	$asDataset = explode($sDatasetSeperator, $sParticipantData);
	// write the tokens to the token_table
	$iCountParticipants =  count($asDataset);
	$iInsertedParticipants=0;
	
	foreach($asDataset as $sData)
	{
		if($sData!='')
		{
			$asDatafield = explode($sDatafieldSeperator, $sData);			
			$checkCnt=1;
			// token generieren
			while($checkCnt>0)
			{
				$value = randomkey(5); //change randomkey value for different tokenlength (up to 36 chars max.)
				$cQuery= "select token from ".$dbprefix."tokens_".$iVid." where token = '".$value."'; ";
				$result = db_execute_assoc($cQuery);
				$checkCnt = $result->RecordCount();
			}
			if(!isset($asDatafield[5]) || $asDatafield[5]=='')
			{
				$asDatafield[5]= $value;
			}
			
			$iDataLength = count($asDatafield);
			for($n=0;$n>=$iDataLength;++$n)
			{
				
				if($asDatafield[$n]=='')
				{
					$asDatafield[$n]=null;
				}
			}
			$sInsertParti = "INSERT INTO ".$dbprefix."tokens_".$iVid 
					."(firstname,lastname,email,emailstatus,token,"
					."language,sent,completed,attribute_1,attribute_2,mpid)"
					."VALUES ('".$asDatafield[0]."' , 
					'".$asDatafield[1]."' , '".$asDatafield[2]."' , 'OK' , '".$asDatafield[5]."',
					'".$_SESSION['lang']."', 'N', 'N', '".$asDatafield[3]."' , '".$asDatafield[4]."' , NULL); ";
				
			$connect->Execute($sInsertParti);	
			++$iInsertedParticipants;
		}
			
	}

	return "".$iCountParticipants."Datasets given, ".$iInsertedParticipants." rows inserted. ";
	//return  $sParticipantData;
} //end of function sInsertParticipants

/**
 * 
 * function to return unused Tokens as String, seperated by commas, to get the people who did not complete the Survey
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @return unknown_type
 */ 
function sTokenReturn($sUser, $sPass, $iVid) 
{
	global $connect ;
	global $dbprefix ;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", START OK ");
	
	// check for appropriate rights
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	
	// check if there is a $iVid, else abort
	if(!isset($iVid) || $iVid=='' || $iVid==0 )
	{
		throw new SoapFault("Server: ", "No SurveyId given");
		exit;
	}
	
	// check if the Survey exists, else -> Fault
	if(!$lsrcHelper->surveyExists($iVid))
	{
		throw new SoapFault("Database: ", "Survey does not exists");
		exit;
	}
		
	// check if the token table exists, else throw fault message
	if(db_tables_exist($dbprefix."tokens_".$iVid))
	{		
		// select all the tokens that did not complete the Survey
		$query2select_token = "SELECT token from {$dbprefix}tokens_".$iVid." WHERE completed = 'N'; ";		
		$rs = db_execute_assoc($query2select_token);
		if($rs->RecordCount()<1)
		{
			throw new SoapFault("Database: ", "No unused Tokens found");
			exit;
		}
		
		$n=0;
		while($row = $rs->FetchRow())
		{
			if($n == 0)
			{
				$sReturn = $row['token'];
			}
			else
			{
				$sReturn .= ",".$row['token'];
			} 
			$n++;
		}  
		// return Response: array([iVid],[return]) on the client side, you get this as an Array resp. list
		// the keys in the array, containing the values, are named as defined in the wsdl under the response Message, in this case: array(iVid =>$iVid, return=>$sReturn)

		return $sReturn;
		exit;	
	}
	else
	{
		throw new SoapFault("Database: ", "Token table for this Survey does not exists");
		exit;
	}		
}//end of function sTokenReturn  

/**
 * 
 * function to import an exported group of questions into a survey
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @param $sMod
 * @param $gName
 * @param $gDesc
 * @return unknown_type
 */
function sImportGroup($sUser, $sPass, $iVid, $sMod, $gName='', $gDesc='')
{
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", START OK ");
	
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or Password wrong");
		exit;
	}
	
	//check for Surveyowner
	if($lsrcHelper->getSurveyOwner($iVid)!=$_SESSION['loginID'] && !$_SESSION['USER_RIGHT_SUPERADMIN']=='1')
	{
		throw new SoapFault("Authentication: ", "You have no right to change Surveys from other people");
		exit;
	}
	
	if(!is_file($modDir.$sMod.".csv"))
	{
		throw new SoapFault("Server: ", "Survey Module $sMod does not exist");
		exit;
	}
	
	$checkImport = $lsrcHelper->importGroup($iVid, $sMod);
	if(is_array($checkImport))
	{
		if($gName!='')
		$lsrcHelper->changeTable("groups", "group_name", $gName, "gid='".$checkImport['gid']."'");
		if($gDesc!='')
		$lsrcHelper->changeTable("groups", "description", $gDesc, "gid='".$checkImport['gid']."'");
		
		return "Import OK";
	}
	else
	{
		throw new SoapFault("Server: ", $checkImport);
		exit;
	}
	
}

/**
 * 
 * function to import a fixed question 
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @param $sMod
 * @param $mandatory
 * @return unknown_type
 */
function sImportQuestion($sUser, $sPass, $iVid, $sMod, $mandatory='N')
{
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", START OK ");
	
	// check for appropriate rights
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	
	//check for Surveyowner
	if($lsrcHelper->getSurveyOwner($iVid)!=$_SESSION['loginID'] && !$_SESSION['USER_RIGHT_SUPERADMIN']=='1')
	{
		throw new SoapFault("Authentication: ", "You have no right to change Surveys from other people");
		exit;
	}
	// Check if the file to import exists
	if(!is_file($queDir.$sMod.".csv"))
	{
		throw new SoapFault("Server: ", "Survey Module $sMod does not exist");
		exit;
	}
	
	//import the module
	$lastId = $lsrcHelper->importQuestion($iVid,$sMod);
	if(is_array($lastId))
	{
//		$lsrcHelper->changeTable("questions", "title", $qTitle, "qid='".$lastId['qid']."'");
//		$lsrcHelper->changeTable("questions", "question", $qText, "qid='".$lastId['qid']."'");
//		$lsrcHelper->changeTable("questions", "help", $qHelp, "qid='".$lastId['qid']."'");
		$lsrcHelper->changeTable("questions", "mandatory", $mandatory, "qid='".$lastId['qid']."'");
		return "OK";
	}
	else
	{
		throw new SoapFault("Server: ", $lastId);
		exit;
	}
}

/**
 * 
 * function to import one freetext question and change it 
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @param $qTitle
 * @param $qText
 * @param $qHelp
 * @param $sMod
 * @param $mandatory
 * @return unknown_type
 */
function sImportFreetext($sUser, $sPass, $iVid, $qTitle, $qText, $qHelp, $sMod='Freitext', $mandatory='N')
{
	/*
	 * this var maybe added later to constructor, 
	 * to determine if a new group should be build for the question 
	 * or if the question should be added to the last group in survey
	 */ 	
	if($sMod=='')
		$sMod='Freitext';
	if($mandatory=='')
		$mandatory='N';
		
	$newGroup=0;
	
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", START OK ");
	
	// check for appropriate rights
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	
	//check for Surveyowner
	if($lsrcHelper->getSurveyOwner($iVid)!=$_SESSION['loginID'] && !$_SESSION['USER_RIGHT_SUPERADMIN']=='1')
	{
		throw new SoapFault("Authentication: ", "You have no right to change Surveys from other people");
		exit;
	}
	// Check if the file to import exists
	if(!is_file($queDir.$sMod.".csv"))
	{
		throw new SoapFault("Server: ", "Survey Module $sMod does not exist");
		exit;
	}
	
	//import the module
	$lastId = $lsrcHelper->importQuestion($iVid,$sMod,0);
	if(is_array($lastId))
	{
		$lsrcHelper->changeTable("questions", "title", $qTitle, "qid='".$lastId['qid']."'");
		$lsrcHelper->changeTable("questions", "question", $qText, "qid='".$lastId['qid']."'");
		$lsrcHelper->changeTable("questions", "help", $qHelp, "qid='".$lastId['qid']."'");
		$lsrcHelper->changeTable("questions", "mandatory", $mandatory, "qid='".$lastId['qid']."'");
		return "OK";
	}
	else
	{
		throw new SoapFault("Server: ", $lastId);
		exit;
	}
}

/**
 * 
 * function to import a five scale Matrix question and set 1 to n items
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @param $qTitle
 * @param $qText
 * @param $qHelp
 * @param $sItems
 * @param $sMod
 * @param $mandatory
 * @return unknown_type
 */
function sImportMatrix($sUser, $sPass, $iVid, $qTitle, $qText, $qHelp, $sItems, $sMod='Matrix5', $mandatory='N')
{
	/*
	 * this var maybe added later to constructor, 
	 * to determine if a new group should be build for the question 
	 * or if the question should be added to the last group in survey
	 */ 	
	if($sMod=='')
		$sMod='Matrix5';
	if($mandatory=='')
		$mandatory='N';
		
	$newGroup=0;
		
	global $connect ;
	global $dbprefix ;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	include("lsrc.config.php");
	
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.", START OK");
	
	// check for appropriate rights
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	//check for surveyOwner
	if($lsrcHelper->getSurveyOwner($iVid)!=$_SESSION['loginID'] && !$_SESSION['USER_RIGHT_SUPERADMIN']=='1')
	{
		throw new SoapFault("Authentication: ", "You have no right to change Surveys from other people");
		exit;
	}
	// Check if the file to import exists
	if(!is_file($queDir.$sMod.".csv"))
	{
		throw new SoapFault("Server: ", "Survey Module $sMod does not exist");
		exit;
	}
	$lastId = $lsrcHelper->importQuestion($iVid,$sMod,$newGroup);
	if(is_array($lastId))
	{
		$lsrcHelper->changeTable("questions", "title", $qTitle, "qid='".$lastId['qid']."'");
		$lsrcHelper->changeTable("questions", "question", $qText, "qid='".$lastId['qid']."'");
		$lsrcHelper->changeTable("questions", "help", $qHelp, "qid='".$lastId['qid']."'");
		if($mandatory==''){$mandatory='N';}
		$lsrcHelper->changeTable("questions", "mandatory", $mandatory, "qid='".$lastId['qid']."'");
		
		$aItems = explode(",", $sItems);
		$n=0;
		foreach($aItems as $item)
		{
			++$n;
			$lsrcHelper->changeTable("answers", "qid,code,answer,default_value,sortorder,language", "'".$lastId['qid']."', '$n','$item','N','$n','".GetBaseLanguageFromSurveyID($iVid)."' " , "", 1);
		}
		return "OK";
	}
	else
	{
		throw new SoapFault("Server: ", $lastId);
		exit;
	}
	
}

/**
 * 
 * function to collect all available Modules and send them comma seperated to the client
 * @param String $sUser
 * @param String $sPass
 * @param String $mode ("mod" or "core")
 * @return commma seperated list of available Modules (groups)
 */
function sAvailableModules($sUser, $sPass, $mode='mod')
{
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.",mode=$mode START OK ");
	
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	switch($mode){
		case ('mod'):
		
			$mDir = opendir($modDir);
			$n=0;
			while(false !== ($file = readdir($mDir))) 
			{
				if($file!='.' && $file!='..' && substr($file,-4,4)==".csv")
				{
					$file = basename ($file, ".csv");
					//$file = str_replace("mod_", "", $file);
					
					if($n == 0)
					{
						$return = $file;
						$n=1;
					}
					else
					{
						$return .= ",".$file;
					} 
				}
				
			}  
			return $return;	
		break;
		case ('core'):
		
			$cDir = opendir($coreDir);
			$n=0;
			while(false !== ($file = readdir($cDir))) 
			{
				if($file!='.' && $file!='..' && substr($file,-4,4)==".csv")
				{
					$file = basename ($file, ".csv");
					//$file = str_replace("mod_", "", $file);
					if($n == 0)
					{
						$return = $file;
						$n=1;
						
					}
					else
					{
						$return .= ",".$file;
					} 
				}
			}  
			return $return;	
		break;
		case ('que'):
		
			$cDir = opendir($queDir);
			$n=0;
			while(false !== ($file = readdir($cDir))) 
			{
				if($file!='.' && $file!='..' && substr($file,-4,4)==".csv")
				{
					$file = basename ($file, ".csv");
					//$file = str_replace("mod_", "", $file);
					if($n == 0)
					{
						$return = $file;
						$n=1;
						
					}
					else
					{
						$return .= ",".$file;
					} 
				}
			}  
			return $return;	
		break;
	}
}

/**
 * 
 * function to delete a survey
 * @param unknown_type $sUser
 * @param unknown_type $sPass
 * @param unknown_type $iVid
 */
function sDeleteSurvey($sUser, $sPass, $iVid)
{
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	$lsrcHelper->debugLsrc("wir sind in ".__FUNCTION__." Line ".__LINE__.",sid=$iVid START OK ");
	
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	
	if($lsrcHelper->getSurveyOwner($iVid)!=$_SESSION['loginID'] && !$_SESSION['USER_RIGHT_SUPERADMIN']=='1')
	{
		throw new SoapFault("Authentication: ", "You have no right to delete Surveys from other people");
		exit;
	}
	// check if the Survey exists, else -> Fault
	if(!$lsrcHelper->surveyExists($iVid))
	{
		throw new SoapFault("Database: ", "Survey $iVid does not exists");
		exit;
	}
	
	if($lsrcHelper->deleteSurvey($iVid))
	{
		return "Survey $iVid deleted";
	}
	else
	{
		throw new SoapFault("Server: ", "Survey $iVid was not deleted");
		exit;
	}
	
	
}

/**
 * 
 * This function pulls a CSV representation of the Field map
 * @param $sUser
 * @param $sPass
 * @param $iVid
 * @return unknown_type
 */
function sGetFieldmap($sUser, $sPass, $iVid)
{
	include("lsrc.config.php");
	$lsrcHelper = new lsrcHelper();
	
	if(!$lsrcHelper->checkUser($sUser, $sPass))
	{
		throw new SoapFault("Authentication: ", "User or password wrong");
		exit;
	}
	if($lsrcHelper->getSurveyOwner($iVid)!=$_SESSION['loginID'] && !$_SESSION['USER_RIGHT_SUPERADMIN']=='1')
	{
		throw new SoapFault("Authentication: ", "You have no right to get fieldmaps from other peoples Surveys");
		exit;
	}
	if(!$lsrcHelper->surveyExists($iVid))
	{
		throw new SoapFault("Database: ", "Survey $iVid does not exists");
		exit;
	}
	
	$returnCSV = "".$lsrcHelper->FieldMap2CSV($iVid);
	return $returnCSV;
	
}
?>