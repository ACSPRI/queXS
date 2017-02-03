<?php 

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
 * JSON RPC
 */
include_once(dirname(__FILE__).'/../include/JsonRPCClient.php');


$limeRPC = "";
$limeKey = "";

function limerpc_init ($url,$user,$pass)
{
  global $limeRPC;
  global $limeKey;

  $limeRPC = new \org\jsonrpcphp\JsonRPCClient($url);

  try {
    $limeKey = $limeRPC->get_session_key($user,$pass);  
  } catch (Exception $e) {
    return $e->getMessage();  
  }

  if (is_array($limeKey) && isset($limeKey['status'])) {
    return $limeKey['status'];
  }
  
  return true;
}

function get_token_value($questionnaire_id,$token, $value = 'sent')
{
  global $limeRPC;
  global $limeKey;
  global $db;

  $sql = "SELECT r.rpc_url, r.username, r.password, r.description, q.lime_id
          FROM remote as r, questionnaire as q
          WHERE q.questoinnaire_d = '$questionnaire_id'
          AND q.remote_id = r.id";

  $r = $db->GetRow($sql);

  $ret = false;

  if (limerpc_init($r['rpc_url'],$r['username'],$r['password']) === true) {
    $l = $limeRPC->get_participant_properties($limeKey,$r['lime_id'],array('token'=>$token),array($value));
    if (isset($l[$value]) {
      $ret= $l[$value];
    }
  }

  return $ret;
}


function get_survey_list ()
{
  global $db;
  global $limeRPC;
  global $limeKey;

  //get a list of surveys from each possible remote

  $sql = "SELECT id, rpc_url, username, password, description
          FROM remote";

  $rs = $db->GetAll($sql);

  $ret = array();

  foreach($rs as $r) {
    if (limerpc_init($r['rpc_url'],$r['username'],$r['password']) === true) {
      $l = $limeRPC->list_surveys($limeKey);
      if (isset($l['status'])) {
        //none available
      } else if (is_array($l)) {
        foreach ($l as $s) {
          if ($s["active"] == "Y") {
            $ret[] = array("sid" => $s['sid'],
                        "description" => $s['surveyls_title'],
                        "host" => $r['description'],
                        "remote_id" => $r['id']);
          }
        }
      }
    }
  }

  return $ret;
}



/**
 * Strip comments from email taken from limesurvey common functions
 * 
 * @param mixed    $comment 
 * @param mixed    $email   
 * @param resource $replace Optional, defaults to ''. 
 * 
 * @return TODO
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2013-02-26
 */
function strip_comments($comment, $email, $replace=''){

    while (1){
        $new = preg_replace("!$comment!", $replace, $email);
        if (strlen($new) == strlen($email)){
            return $email;
        }
        $email = $new;
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

/**
 * Return the number of completions for a given
 * questionnaire, where the given sample var has
 * the given sample value
 *
 * @param int $lime_sid The limesurvey survey id 
 * @param int $questionnaire_id The questionnaire ID
 * @param int $sample_import_id The sample import ID
 * @param string $val The value to compare
 * --- changed @param string $var the variable to compare
 * to          @param string $var_id  - ID for  variable to compare
 * @return bool|int False if failed, otherwise the number of completions
 */
function limesurvey_quota_replicate_completions($lime_sid,$questionnaire_id,$sample_import_id,$val,$var_id)
{
	global $db;

  $sql = "SELECT COUNT(*)
          FROM information_schema.tables
          WHERE table_schema = '".DB_NAME."'
          AND table_name = '" . LIME_PREFIX . "survey_$lime_sid'";

  $rs = $db->GetOne($sql);

  if ($rs != 1)
    return false;

	$sql = "SELECT count(*) as c
		FROM " . LIME_PREFIX . "survey_$lime_sid as s
		JOIN `case` as c ON (c.questionnaire_id = '$questionnaire_id')
		JOIN `sample` as sam ON (c.sample_id = sam.sample_id AND sam.import_id = '$sample_import_id')
		JOIN `sample_var` as sv ON (sv.sample_id = sam.sample_id AND sv.var_id = '$var_id' AND sv.val LIKE '$val')
		WHERE s.submitdate IS NOT NULL
		AND s.token = c.token";

	$rs = $db->GetRow($sql);

	if (isset($rs) && !empty($rs))
		return $rs['c'];
	
	return false;
}

/**
 * Return whether the given case matches the requested quota
 *
 * @param string $lime_sgqa The limesurvey SGQA
 * @param int $lime_sid The limesurvey survey id 
 * @param int $case_id The case id
 * @param string $value The value to compare
 * @param string $comparison The type of comparison
 * @param int $sample_import_id The sample import ID
 *
 * @return bool|int False if failed, otherwise 1 if matched, 0 if doesn't
 * 
 */
function limesurvey_quota_match($lime_sgqa,$lime_sid,$case_id,$value,$comparison,$sample_import_id)
{
	global $db;

	$sql = "SELECT count(*) as c
		FROM " . LIME_PREFIX . "survey_$lime_sid as s
		JOIN `case` as c ON (c.case_id = '$case_id')
		JOIN `sample` as sam ON (c.sample_id = sam.sample_id and sam.import_id = $sample_import_id)
		WHERE s.token = c.token
		AND s.`$lime_sgqa` $comparison '$value'";

	$rs = $db->GetRow($sql);

	if (isset($rs) && !empty($rs))
		return $rs['c'];
	
	return false;
}

/**
 * Return whether the given case matches the replicate sample only quota
 * 
 * @param int $lime_sid The Limesurvey survey id
 * @param int $case_id The case id
 * @param string $val The sample value to compare
 * --- changed @param string $var the variable to compare
 * to          @param string $var_id  - ID for  variable to compare
 * @param int $sample_import_id The sample import id we are looking at
 * 
 * @return bool|int False if failed, otherwise 1 if matched, 0 if doesn't
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @since  2012-04-30
 */
function limesurvey_quota_replicate_match($lime_sid,$case_id,$val,$var_id,$sample_import_id)
{
	global $db;
	
	$sql = "SELECT count(*) as c
		FROM " . LIME_PREFIX . "survey_$lime_sid as s
		JOIN `case` as c ON (c.case_id = '$case_id')
		JOIN `sample` as sam ON (c.sample_id = sam.sample_id and sam.import_id = $sample_import_id)
		JOIN `sample_var` as sv ON (sv.sample_id = sam.sample_id AND sv.var_id = '$var_id' AND sv.val LIKE '$val')
		WHERE s.token = c.token";

	$rs = $db->GetRow($sql);

	if (isset($rs) && !empty($rs))
		return $rs['c'];
	
	return false;

}

/**
 * Return the number of completions for a given
 * questionnaire, where the given question has
 * the given value
 *
 * @param string $lime_sgqa The limesurvey SGQA
 * @param int $lime_sid The limesurvey survey id 
 * @param int $questionnaire_id The questionnaire ID
 * @param int $sample_import_id The sample import ID
 * @param string $value The value to compare
 * @param string $comparison The type of comparison
 * @return bool|int False if failed, otherwise the number of completions
 * 
 */
function limesurvey_quota_completions($lime_sgqa,$lime_sid,$questionnaire_id,$sample_import_id,$value,$comparison)
{
	global $db;

  $sql = "SELECT COUNT(*)
          FROM information_schema.tables
          WHERE table_schema = '".DB_NAME."'
          AND table_name = '" . LIME_PREFIX . "survey_$lime_sid'";

  $rs = $db->GetOne($sql);

  if ($rs != 1)
    return false;

	$sql = "SELECT count(*) as c
		FROM " . LIME_PREFIX . "survey_$lime_sid as s
		JOIN `case` as c ON (c.questionnaire_id = '$questionnaire_id')
		JOIN `sample` as sam ON (c.sample_id = sam.sample_id AND sam.import_id = '$sample_import_id')
		WHERE s.submitdate IS NOT NULL
		AND s.token = c.token
		AND s.`$lime_sgqa` $comparison '$value'";

	$rs = $db->GetRow($sql);


	if (isset($rs) && !empty($rs))
		return $rs['c'];
	
	return false;
}

/**
 * Get information on limesurvey quota's
 * Based on GetQuotaInformation() from common.php in Limesurvey
 *
 * @param int $lime_quota_id The quota id to get information on
 * @return array An array containing the question information for comparison
 */
function get_limesurvey_quota_info($lime_quota_id)
{
	global $db;

	$ret = array();

	$sql = "SELECT q.qid
		FROM ".LIME_PREFIX."quota_members as q, ".LIME_PREFIX."surveys as s
    WHERE q.quota_id='$lime_quota_id'
    AND s.sid = q.sid
	GROUP BY q.qid";
	
	$rsq = $db->GetAll($sql);

	foreach ($rsq as $q)
	{
		$qid = $q['qid'];

		$sql = "SELECT q.*,s.language
			FROM ".LIME_PREFIX."quota_members as q, ".LIME_PREFIX."surveys as s
			WHERE q.quota_id='$lime_quota_id'
			AND s.sid = q.sid
			AND q.qid = $qid";
	
		$rs = $db->GetAll($sql);

		$r2 = array();

		foreach($rs as $quota_entry)
		{
			$lime_qid = $quota_entry['qid'];
			$surveyid = $quota_entry['sid'];
			$language = $quota_entry['language'];

			$sql = "SELECT type, title,gid
				FROM ".LIME_PREFIX."questions
				WHERE qid='$lime_qid' 
				AND language='$language'";

			$qtype = $db->GetRow($sql);
		
			$fieldnames = "0";
			
			if ($qtype['type'] == "I" || $qtype['type'] == "G" || $qtype['type'] == "Y")
			{
				$fieldnames= ($surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid']);
				$value = $quota_entry['code'];
			}
			
			if($qtype['type'] == "L" || $qtype['type'] == "O" || $qtype['type'] =="!") 
			{
			    $fieldnames=( $surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid']);
			    $value = $quota_entry['code'];
			}

			if($qtype['type'] == "M")
			{
				$fieldnames=( $surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid'].$quota_entry['code']);
				$value = "Y";
			}
			
			if($qtype['type'] == "A" || $qtype['type'] == "B")
			{
				$temp = explode('-',$quota_entry['code']);
				$fieldnames=( $surveyid.'X'.$qtype['gid'].'X'.$quota_entry['qid'].$temp[0]);
				$value = $temp[1];
			}
			

			$r2[] = array('code' => $quota_entry['code'], 'value' => $value, 'qid' => $quota_entry['qid'], 'fieldname' => $fieldnames);
		}
	
		$ret[$qid] = $r2;
	}
	return $ret;
}

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
 * @param bool $exittoend Whether to exit to the project end, or to the start of the questionnaire
 * @link http://www.limesurvey.org/ LimeSurvey
 */
function create_limesurvey_questionnaire($title,$exittoend = true)
{
	global $db;

	// Get random ids until one is found that is not used
	do
	{
		$surveyid = getRandomID();
		$isquery = "SELECT sid FROM ".db_table_name('surveys')." WHERE sid=$surveyid";
		$isresult = $db->Execute($isquery);
	}
	while (!empty($isresult) && $isresult->RecordCount() > 0);

	$isquery = "INSERT INTO ". LIME_PREFIX ."surveys\n"
	. "(sid, owner_id, admin, active, expires, "
	. "adminemail, private, faxto, format, template, "
	. "language, datestamp, ipaddr, refurl, usecookie, notification, allowregister, "
	. "allowsave, autoredirect, allowprev,datecreated,tokenanswerspersistence)\n"
	. "VALUES ($surveyid, 1,\n"
	. "'', 'N', \n"
	. "NULL, '', 'N',\n"
	. "'', 'S', 'quexs',\n"
	. "'" . DEFAULT_LOCALE . "', 'Y', 'N', 'N',\n"
	. "'N', '0', 'Y',\n"
	. "'Y', 'Y', 'Y','".date("Y-m-d")."','Y')";
	$isresult = $db->Execute($isquery) or die ($isquery."<br/>".$db->ErrorMsg());

	// insert base language into surveys_language_settings
	$isquery = "INSERT INTO ".db_table_name('surveys_languagesettings')
	. "(surveyls_survey_id, surveyls_language, surveyls_title, surveyls_description, surveyls_welcometext, surveyls_urldescription, "
	. "surveyls_email_invite_subj, surveyls_email_invite, surveyls_email_remind_subj, surveyls_email_remind, "
	. "surveyls_email_register_subj, surveyls_email_register, surveyls_email_confirm_subj, surveyls_email_confirm,surveyls_url)\n"
	. "VALUES ($surveyid, '" . DEFAULT_LOCALE . "', $title, $title,\n"
	. "'',\n"
	. "'', '',\n"
	. "'', '',\n"
	. "'', '',\n"
	. "'', '',\n"
	. "'', '";

	if ($exittoend)
		$isquery .=  "{ENDINTERVIEWURL}')";
	else
		$isquery .=  "{STARTINTERVIEWURL}')";
	
	$isresult = $db->Execute($isquery) or die ($isquery."<br/>".$db->ErrorMsg());


	// Insert into survey_rights
	$isrquery = "INSERT INTO ". LIME_PREFIX . "surveys_rights VALUES($surveyid,1,1,1,1,1,1,1)";
	$isrresult = $db->Execute($isrquery) or die ($isrquery."<br />".$db->ErrorMsg());

	return $surveyid;
}


function get_lime_url($case_id)
{
  global $db;

  $sql = "SELECT r.entry_url
          FROM remote as r, `case` as c, questionnaire as q
          WHERE c.case_id = $case_id
          AND c.questionnaire_id = q.questionnaire_id
          AND q.remote_id = r.id";

  return $db->GetOne($sql);
}


/**
 * Return the limesurvey id given the case_id
 *
 * @param int $case_id The case id
 * @return bool|int False if no lime_id otherwise the lime_id
 *
 */
function get_lime_id($case_id)
{
	global $db;

	$lime_sid = get_lime_sid($case_id);
	if ($lime_sid == false) return false;

	$sql = "SELECT s.id
		FROM " . LIME_PREFIX . "survey_$lime_sid as s, `case` as c
		WHERE c.case_id = '$case_id'
		AND c.token = s.token";
	
	$r = $db->GetRow($sql);

	if (!empty($r) && isset($r['id']))
		return $r['id'];

	return false;


}


/**
 * Return the limesurvey tid given the case_id
 *
 * @param int $case_id The case id
 * @return bool|int False if no lime_tid otherwise the lime_tid
 *
 */
function get_lime_tid($case_id)
{
	global $db;

	$lime_sid = get_lime_sid($case_id);
	if ($lime_sid == false) return false;

	$sql = "SELECT t.tid
		FROM " . LIME_PREFIX . "tokens_$lime_sid as t, `case` as c
		WHERE c.case_id = '$case_id'
		AND c.token = t.token";
	
	$r = $db->GetRow($sql);

	if (!empty($r) && isset($r['tid']))
		return $r['tid'];

	return false;


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
 * Check if LimeSurvey has marked a questionnaire as quota filled
 *
 * @param int $case_id The case id
 * @return bool True if complete, false if not or unknown
 *
 */
function limesurvey_is_quota_full($case_id)
{
	global $db;

	$lime_sid = get_lime_sid($case_id);
	if ($lime_sid == false) return false;

	$sql = "SELECT t.completed
		FROM " . LIME_PREFIX . "tokens_$lime_sid as t, `case` as c
		WHERE c.case_id = '$case_id'
		AND c.token = t.token";
	
	$r = $db->GetRow($sql);

	if (!empty($r))
		if ($r['completed'] == 'Q') return true;

	return false;
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
	global $db;

	$lime_sid = get_lime_sid($case_id);
	if ($lime_sid == false) return false;

	$sql = "SELECT t.completed
		FROM " . LIME_PREFIX . "tokens_$lime_sid as t, `case` as c
		WHERE c.case_id = '$case_id'
		AND t.token = c.token";
	
	$r = $db->GetRow($sql);

	if (!empty($r))
		if ($r['completed'] != 'N' && $r['completed'] != 'Q') return true;

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
	global $db;

	$sql = "SELECT count(qid) as c
		FROM " . LIME_PREFIX . "questions
		WHERE sid = '$lime_sid'";

	$r = $db->GetRow($sql);

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
	global $db;

	$lime_sid = get_lime_sid($case_id);
	if ($lime_sid == false) return false;

	$sql = "SELECT saved_thisstep
		FROM ". LIME_PREFIX ."saved_control
		WHERE sid = '$lime_sid'
		AND identifier = '$case_id'";

	$r = $db->GetRow($sql);

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
	global $db;

	$sql = "SELECT value FROM ".LIME_PREFIX."question_attributes WHERE qid = '$qid' and attribute = 'maximum_chars'";
	$r = $db->GetRow($sql);

	if (!empty($r))
		$default = $r['value'];

	return $default;
}

?>
