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
    die($e->getMessage());  
  }

  if (is_array($limeKey) && isset($limeKey['status'])) {
    die($limeKey['status']);
  }
  
  return true;
}

function limerpc_close()
{
    global $limeRPC;
    global $limeKey;

    if (!empty($limeRPC) && !empty($limeKey))
        $limeRPC->release_session_key($limeKey);
}

function limerpc_init_qid($qid)
{
  global $db;

  $sql = "SELECT r.rpc_url, r.username, r.password, r.description, q.lime_sid
          FROM remote as r, questionnaire as q
          WHERE q.questionnaire_id = '$qid'
          AND q.remote_id = r.id";

  $r = $db->GetRow($sql);

  $ret = false;

  if (limerpc_init($r['rpc_url'],$r['username'],$r['password']) === true) {
      return $r['lime_sid'];
  }

  return false;

}

function lime_list_questions($qid)
{
  global $limeKey;
  global $limeRPC;

  $ret = false;
  $lime_id = limerpc_init_qid($qid);

  if ($lime_id !== false) {
      $q = $limeRPC->list_questions($limeKey,$lime_id);
      if (!isset($q['status'])) {
        $ret = $q;
      }
  } 

  limerpc_close();
  return $ret;
}


function lime_list_answeroptions($qid,$qcode)
{
  global $limeKey;
  global $limeRPC;

  $ret = false;

  $q = lime_list_questions($qid);

  if ($q !== false)
  {
    
    foreach($q as $qid => $val) {
        if ($val['title'] == $qcode) {
            $qp = $limeRPC->get_question_properties($limeKey,$qid,array('answeroptions'));
            if (!isset($qp['status'])) {
                $ret = $qp;
            }
            break;
        }
    }
  }

  limerpc_close();
  return $ret;

}

/** Get completed responses as an array based on the case_id
 */
function lime_get_responses_by_case($case_id,$fields)
{
	global $db;
    global $limeRPC;
    global $limeKey;

	$sql = "SELECT c.token,c.questionnaire_id
		FROM `case` as c
		WHERE c.case_id = '$case_id'";
	
	$rs = $db->GetRow($sql);

    $token = $rs['token'];
    $qid = $rs['qid'];

    $lime_id = limerpc_init_qid($qid);

    $ret = false;

    if ($lime_id !== false) {
      $q = $limeRPC->export_responses_by_token($limeKey,$lime_id,'json',$token,null,'complete','code','short',$fields);
      if (!isset($q['status'])) {
          $ret = json_decode(base64_decode($q));
            //TODO: check how this returns
          var_dump($ret); die();
      }
    } 

    limerpc_close();

	return $ret;


}

/** Get completd responses as an array based on the questionnaire
 * indexed by token 
 */
function lime_get_responses_by_questionnaire($qid,$fields)
{
    global $limeRPC;
    global $limeKey;

    $lime_id = limerpc_init_qid($qid);

    $ret = false;

    if ($lime_id !== false) {
      $q = $limeRPC->export_responses($limeKey,$lime_id,'json',null,'complete','code','short',null,null,$fields);
      if (!isset($q['status'])) {
          $ret = json_decode(base64_decode($q));
            //TODO: check how this returns
          var_dump($ret); die();
      }
    } 

    limerpc_close();

	return $ret;
}



function lime_add_token($qid,$params)
{
  global $limeKey;
  global $limeRPC;

  $ret = false;
  $lime_id = limerpc_init_qid($qid);

  if ($lime_id !== false) {
    $l = $limeRPC->add_participants($limeKey,$lime_id,array($params),false); //don't create token
    if (!isset($l['status'])) {
        $ret = $l; //array of data
    } 
  }

  limerpc_close();
  return $ret;
}


function get_token_value($questionnaire_id,$token, $value = 'sent')
{
  global $limeKey;
  global $limeRPC;

  $ret = false;
  $lime_id = limerpc_init_qid($questionnaire_id);

  if ($lime_id !== false) {
    $l = $limeRPC->get_participant_properties($limeKey,$lime_id,array('token'=>$token),array($value));
    if (isset($l[$value])) {
      $ret= $l[$value];
    }
  }

  limerpc_close();
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
      limerpc_close();
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

    $resp = lime_get_responses_by_questionnaire($questionnaire_id,array($lime_sgqa));

    $completions = false;

    if ($resp !== false) {
    	$sql = "SELECT c.token
    		    FROM `case` as c
    		    JOIN `sample` as sam ON (c.sample_id = sam.sample_id AND sam.import_id = '$sample_import_id')
    		    WHERE c.questionnaire_id = '$questionnaire_id'";
    
    	$rs = $db->GetAssoc($sql);

        $completions = 0;

        foreach($resp as $r) {
            
        }

    }
	
	return $completions;
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
 * Return the limesurvey id given the case_id
 *
 * @param int $case_id The case id
 * @return bool|int False if no lime_id otherwise the lime_id
 *
 */
function get_lime_id($case_id)
{
	global $db;
    global $limeRPC;
    global $limeKey;

	$sql = "SELECT c.token,c.questionnaire_id
		FROM `case` as c
		WHERE c.case_id = '$case_id'";
	
	$rs = $db->GetRow($sql);

    $token = $rs['token'];
    $qid = $rs['questionnaire_id'];

    $lime_id = limerpc_init_qid($qid);

    $ret = false;

    if ($lime_id !== false) {
      $q = $limeRPC->get_response_ids($limeKey,$lime_id,$token);
      if (!isset($q['status'])) {
        $ret = current($q); //return first id;
      }
    } 

    limerpc_close();

	return $ret;
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

    $sql = "SELECT questionnaire_id, token
            FROM `case`
            WHERE case_id = '$case_id'";

    $rs = $db->GetRow($sql);

    $r = get_token_value($rs['questionnaire_id'],$rs['token'], 'completed');

	if ($r == 'Q') return true;

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

    $sql = "SELECT questionnaire_id, token
            FROM `case`
            WHERE case_id = '$case_id'";

    $rs = $db->GetRow($sql);

    $r = get_token_value($rs['questionnaire_id'],$rs['token'], 'completed');

    //hasn't failed, not quota filled or not marked as incomplete
	if ($r !== false && $r != 'Q' && $r != 'N') return true;

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


    //TODO: use export_responses_by_token and check the lastpage variable
    //
    
	return false;

}


?>
