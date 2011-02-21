<?
/**
 * FreePBX Functions
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
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright Australian Consortium for Social and Political Research Incorporated (ACSPRI) 2007,2008,2009,2010,2011
 * @package queXS
 * @subpackage functions
 * @link http://www.acspri.org.au/software queXS was writen for ACSPRI
 * @license http://opensource.org/licenses/gpl-2.0.php The GNU General Public License (GPL) Version 2
 * 
 */

/**
 * Configuration file
 */
include_once(dirname(__FILE__).'/../config.inc.php');

/**
 * FreePBX functions to add an extension
 * This needs to be re-implemented here as there are too many code conflicts in including freepbx code
 * The queXS database user should have access to the FreePBX database to avoid making a new connection
 * FREEPBX_DATABASE needs to be set to the freepbx database name (i.e. asterisk)
 *
 * @param string $extension The extension number
 * @param string $name The name of the extension
 * @param string $password The password for the extension
 * @return bool True if successfully added else false
 */
function freepbx_add_extension($extension,$name,$password)
{
	global $amp_conf_defaults;

	if (FREEPBX_PATH == false) return false; //break out if not defined

	//include freepbx functions
	require_once(FREEPBX_PATH . "/functions.inc.php");
	require_once(FREEPBX_PATH . "/common/php-asmanager.php");

	// get settings
	$amp_conf       = parse_amportal_conf("/etc/amportal.conf");
	$asterisk_conf  = parse_asterisk_conf($amp_conf["ASTETCDIR"]."/asterisk.conf");
        $astman         = new AGI_AsteriskManager();

        // attempt to connect to asterisk manager proxy
	if (!isset($amp_conf["ASTMANAGERPROXYPORT"]) || !$res = $astman->connect($amp_conf["ASTMANAGERHOST"] . ":" . $amp_conf["ASTMANAGERPROXYPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"])) {
                // attempt to connect directly to asterisk, if no proxy or if proxy failed
                if (!$res = $astman->connect($amp_conf["ASTMANAGERHOST"] . ":" . $amp_conf["ASTMANAGERPORT"], $amp_conf["AMPMGRUSER"] , $amp_conf["AMPMGRPASS"], 'off')) {
                        // couldn't connect at all
                        unset( $astman );
                }
        }

	//Defines from bulkextensions module of FreePBX
	$vars = array (
			"action" => "add",
			"extension" => $extension,
			"name" => $name,
			"cid_masquerade" => $extension,
			"ringtimer" => 0,
			"callwaiting" => "disabled",
			"call_screen" => 0,
			"pinless" => "disabled",
			"tech" => "iax2",
			"devinfo_secret" => $password,
			"devinfo_notransfer" => "yes",		// for iax2 devices
			"devinfo_context" => "from-internal",
			"devinfo_host" => "dynamic",
			"devinfo_type" => "friend",
			"devinfo_port" => 4569,
			"devinfo_qualify" => "yes",
			"devinfo_dial" => "IAX2/$extension",
			"devinfo_mailbox" => $extension . "@device",
			"devinfo_deny" => "0.0.0.0/0.0.0.0",
			"devinfo_permit" => "0.0.0.0/0.0.0.0",
			"deviceid" => $extension,
			"devicetype" => "fixed",
			"deviceuser" => $extension,
			"description" => $name,
			"dictenabled" => "disabled",
			"dictformat" => "ogg",
			"record_in" => "Adhoc",
			"record_out" => "Adhoc",
			"vm" => "disabled",
			"voicemail" => "disabled",
			"attach" => "attach=no",
			"saycid" => "saycid=no",
			"envelope" => "envelope=no",
			"delete" => "delete=no",
			"requirecalltoken" => "no",      
			"vmx_play_instructions" => "checked",
			"vmx_option_0_system_default" => "checked",
			"emergency_cid" => "null"
			);


		freepbx_core_users_add($amp_conf,$astman,$vars);
		freepbx_core_devices_add($amp_conf,$astman,$vars["deviceid"],$vars["tech"],$vars["devinfo_dial"],$vars["devicetype"],$vars["deviceuser"],$vars["description"],$vars["emergency_cid"],false,$vars);
		freepbx_do_reload($amp_conf,$astman,$asterisk_conf);
}

function freepbx_do_reload(&$amp_conf,&$astman,&$asterisk_conf) 
{
        global $db;

       	array($return);
 
        if (isset($amp_conf["PRE_RELOAD"]) && !empty($amp_conf['PRE_RELOAD']))  {
                exec( $amp_conf["PRE_RELOAD"], $output, $exit_val );
                
        }
        
        $retrieve = $amp_conf['AMPBIN'].'/retrieve_conf 2>&1';
        //exec($retrieve.'&>'.$asterisk_conf['astlogdir'].'/freepbx-retrieve.log', $output, $exit_val);
        exec($retrieve, $output, $exit_val);
        
        
        if ($exit_val != 0) {
                $return['status'] = false;
                $return['message'] = sprintf(_('Reload failed because retrieve_conf encountered an error: %s'),$exit_val);
                $return['num_errors']++;
                $notify->add_critical('freepbx','RCONFFAIL', _("retrieve_conf failed, config not applied"), $return['message']);
                return $return;
        }

        if (!isset($astman) || !$astman) {
                $return['status'] = false;
                $return['message'] = _('Reload failed because FreePBX could not connect to the asterisk manager interface.');
                $return['num_errors']++;
                return $return;
        }

        //reload MOH to get around 'reload' not actually doing that.
        $astman->send_request('Command', array('Command'=>'moh reload'));

        //reload asterisk (>= 1.4)
          $astman->send_request('Command', array('Command'=>'module reload'));

        $return['status'] = true;

        if ($amp_conf['FOPRUN'] && !$amp_conf['FOPDISABLE']) {
                //bounce op_server.pl
                $wOpBounce = $amp_conf['AMPBIN'].'/bounce_op.sh';
                exec($wOpBounce.' &>'.$asterisk_conf['astlogdir'].'/freepbx-bounce_op.log', $output, $exit_val);

                if ($exit_val != 0) {
                        $desc = _('Could not reload the FOP operator panel server using the bounce_op.sh script. Configuration changes may not be reflected in the panel display.');
                        $return['num_errors']++;
		}
        }

        if (isset($amp_conf["POST_RELOAD"]) && !empty($amp_conf['POST_RELOAD']))  {
                exec( $amp_conf["POST_RELOAD"], $output, $exit_val );

                if ($exit_val != 0) {
                        $desc = sprintf(_("Exit code was %s and output was: %s"), $exit_val, "\n\n".implode("\n",$output));
                        $return['num_errors']++;
		}
        }

	$sql = "UPDATE ".FREEPBX_DATABASE.".admin SET value = 'false' WHERE variable = 'need_reload'";
	
	$db->Execute($sql);

        return $return;
}



function freepbx_need_reload()
{
	global $db;

	$sql = "UPDATE ".FREEPBX_DATABASE.".admin SET value = 'true' WHERE variable = 'need_reload'";
	
	$db->Execute($sql);
}


function freepbx_core_devices_add(&$amp_conf,&$astman,$id,$tech,$dial,$devicetype,$user,$description,$emergency_cid=null,$editmode=false,$vars){
	global $db;

	//ensure this id is not already in use
	$sql = "SELECT *
		FROM ".FREEPBX_DATABASE.".devices
		WHERE id = '$id'";

	$rs = $db->GetAll($sql);

	if (!empty($rs))
		return false; //device already exists

	//unless defined, $dial is TECH/id
	if ( $dial == '' ) {
		$dial = strtoupper($tech)."/".$id;
	}
	
	//check to see if we are requesting a new user
	if ($user == "new") {
		$user = $id;
		$jump = true;
	}
	
	//insert into devices table
	$sql="INSERT INTO ".FREEPBX_DATABASE.".devices (id,tech,dial,devicetype,user,description,emergency_cid) values (\"$id\",\"$tech\",\"$dial\",\"$devicetype\",\"$user\",\"$description\",\"$emergency_cid\")";
	$db->Execute($sql);
	
	//add details to astdb
	if ($astman) {
		// if adding or editting a fixed device, user property should always be set
		if ($devicetype == 'fixed' || !$editmode) {
			$astman->database_put("DEVICE",$id."/user",$user);
		}
		// If changing from a fixed to an adhoc, the user property should be intialized
		// to the new default, not remain as the previous fixed user
		if ($editmode) {
			$previous_type = $astman->database_get("DEVICE",$id."/type");
			if ($previous_type == 'fixed' && $devicetype == 'adhoc') {
				$astman->database_put("DEVICE",$id."/user",$user);
			}
		}
		$astman->database_put("DEVICE",$id."/dial",$dial);
		$astman->database_put("DEVICE",$id."/type",$devicetype);
		$astman->database_put("DEVICE",$id."/default_user",$user);
		if($emergency_cid != '') {
			$astman->database_put("DEVICE",$id."/emergency_cid","\"".$emergency_cid."\"");
		}

		if ($user != "none") {
			$existingdevices = $astman->database_get("AMPUSER",$user."/device");
			if (empty($existingdevices)) {
				$astman->database_put("AMPUSER",$user."/device",$id);
			} else {
				$existingdevices_array = explode('&',$existingdevices);
				if (!in_array($id, $existingdevices_array)) {
					$existingdevices_array[]=$id;
					$existingdevices = implode('&',$existingdevices_array);
					$astman->database_put("AMPUSER",$user."/device",$existingdevices);
				}
			}
		}

	} else {
		die("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	
	// create a voicemail symlink if needed
	/*
	$thisUser = core_users_get($user);
	if(isset($thisUser['voicemail']) && ($thisUser['voicemail'] != "novm")) {
		if(empty($thisUser['voicemail'])) {
			$vmcontext = "default";
		} else { 
			$vmcontext = $thisUser['voicemail'];
		}
		
		//voicemail symlink
		exec("rm -f /var/spool/asterisk/voicemail/device/".$id);
		exec("/bin/ln -s /var/spool/asterisk/voicemail/".$vmcontext."/".$user."/ /var/spool/asterisk/voicemail/device/".$id);
	}
	*/
	
	//take care of sip/iax/zap config
	$funct = "freepbx_core_devices_add".strtolower($tech);
  
	if(function_exists($funct)){
		$funct($id,$vars);
	}
	
	return true;
}

function freepbx_core_devices_addiax2($account,$vars) {
	global $db;

	$flag = 2;
	foreach ($vars as $req=>$data) {
		if ( substr($req, 0, 8) == 'devinfo_' ) {
			$keyword = substr($req, 8);
			if ( $keyword == 'dial' && $data == '' ) {
				$iaxfields[] = array($account, $keyword, 'IAX2/'.$account, $flag++);
			} elseif ($keyword == 'mailbox' && $data == '') {
				$iaxfields[] = array($account,'mailbox',$account.'@device', $flag++);
			} else {
				$iaxfields[] = array($account, $keyword, $data, $flag++);
			}
		}
	}
	
	if ( !is_array($iaxfields) ) { // left for compatibilty....lord knows why !
		$iaxfields = array(
			array($account,'secret',$db->qstr(($vars['secret'])?$vars['secret']:''),$flag++),
			array($account,'notransfer',$db->qstr(($vars['notransfer'])?$vars['notransfer']:'yes'),$flag++),
			array($account,'context',$db->qstr(($vars['context'])?$vars['context']:'from-internal'),$flag++),
			array($account,'host',$db->qstr(($vars['host'])?$vars['host']:'dynamic'),$flag++),
			array($account,'type',$db->qstr(($vars['type'])?$vars['type']:'friend'),$flag++),
			array($account,'mailbox',$db->qstr(($vars['mailbox'])?$vars['mailbox']:$account.'@device'),$flag++),
			array($account,'username',$db->qstr(($vars['username'])?$vars['username']:$account),$flag++),
			array($account,'port',$db->qstr(($vars['port'])?$vars['port']:'4569'),$flag++),
			array($account,'qualify',$db->qstr(($vars['qualify'])?$vars['qualify']:'yes'),$flag++),
			array($account,'deny',$db->qstr((isset($vars['deny']))?$vars['deny']:''),$flag++),
			array($account,'permit',$db->qstr((isset($vars['permit']))?$vars['permit']:''),$flag++),			
			array($account,'disallow',$db->qstr(($vars['disallow'])?$vars['disallow']:''),$flag++),
			array($account,'allow',$db->qstr(($vars['allow'])?$vars['allow']:''),$flag++),
			array($account,'accountcode',$db->qstr(($vars['accountcode'])?$vars['accountcode']:''),$flag++),
			array($account,'requirecalltoken',$db->qstr(($vars['requirecalltoken'])?$vars['requirecalltoken']:'no'),$flag++)
		);
	}

	// Very bad
	$iaxfields[] = array($account,'account',($account),$flag++);	
	$iaxfields[] = array($account,'callerid',((isset($vars['description']) && $vars['description'] != '')?$vars['description']." <".$account.'>':'device'." <".$account.'>'),$flag++);
	// Asterisk treats no caller ID from an IAX device as 'hide callerid', and ignores the caller ID
	// set in iax.conf. As we rely on this for pretty much everything, we need to specify the 
	// callerid as a variable which gets picked up in macro-callerid.
	// Ref - http://bugs.digium.com/view.php?id=456
	$iaxfields[] = array($account,'setvar',"REALCALLERIDNUM=$account",$flag++);
	
	// Where is this in the interface ??????
	$iaxfields[] = array($account,'record_in',(($vars['record_in'])?$vars['record_in']:'On-Demand'),$flag++);
	$iaxfields[] = array($account,'record_out',(($vars['record_out'])?$vars['record_out']:'On-Demand'),$flag++);
	
	$iaxfields[] = array($account,'requirecalltoken','no',$flag++);	
	
	$compiled = $db->Prepare('INSERT INTO '.FREEPBX_DATABASE.'.iax (id, keyword, data, flags) values (?,?,?,?)');
	foreach($iaxfields as $i)
		$db->Execute($compiled, $i);
}



function freepbx_core_users_add(&$amp_conf, &$astman, $vars, $editmode=false) {
	extract($vars);
	
	global $db;

	$thisexten = isset($thisexten) ? $thisexten : '';

	if (trim($extension) == '' ) {
		return false;
	}

	//ensure this id is not already in use
	$sql = "SELECT *
		FROM ".FREEPBX_DATABASE.".users
		WHERE extension = '$extension'";

	$rs = $db->GetAll($sql);

	if (!empty($rs))
		return false; //device already exists

	$newdid_name = isset($newdid_name) ? $db->qstr($newdid_name) : '';
	$newdid = isset($newdid) ? $newdid : '';
	$newdid = preg_replace("/[^0-9._XxNnZz\[\]\-\+]/" ,"", trim($newdid));

	$newdidcid = isset($newdidcid) ? trim($newdidcid) : '';
	if (!preg_match('/^priv|^block|^unknown|^restrict|^unavail|^anonym/',strtolower($newdidcid))) {
		$newdidcid = preg_replace("/[^0-9._XxNnZz\[\]\-\+]/" ,"", $newdidcid);
	}

	//build the recording variable
	$recording = "out=".$record_out."|in=".$record_in;
	
	//escape quotes and any other bad chddddars:
	if(!get_magic_quotes_gpc()) {
		$outboundcid = '';
		$name = $db->qstr($name);
	}

	// Clean replace any <> with () in display name - should have javascript stopping this but ...
	//
	$name = preg_replace(array('/</','/>/'), array('(',')'), trim($name));
	
	//insert into users table
	$sql="INSERT INTO ".FREEPBX_DATABASE.".users (extension,password,name,voicemail,ringtimer,noanswer,recording,outboundcid,sipname) values (\"";
	$sql.= "$extension\", \"";
	$sql.= isset($password)?$password:'';
	$sql.= "\", ";
	$sql.= isset($name)?$name:'';
	$sql.= ", \"";
	$sql.= isset($voicemail)?$voicemail:'novm';
	$sql.= "\", \"";
	$sql.= isset($ringtimer)?$ringtimer:'';
	$sql.= "\", \"";
	$sql.= isset($noanswer)?$noanswer:'';
	$sql.= "\", \"";
	$sql.= isset($recording)?$recording:'';
	$sql.= "\", \"";
	$sql.= isset($outboundcid)?$outboundcid:'';
	$sql.= "\", \"";
	$sql.= isset($sipname)?$sipname:'';
	$sql.= "\")";

	$db->Execute($sql);

	//write to astdb
	if ($astman) {
		$cid_masquerade = (isset($cid_masquerade) && trim($cid_masquerade) != "")?trim($cid_masquerade):$extension;
		$astman->database_put("AMPUSER",$extension."/password",isset($password)?$password:'');
		$astman->database_put("AMPUSER",$extension."/ringtimer",isset($ringtimer)?$ringtimer:'');
		$astman->database_put("AMPUSER",$extension."/noanswer",isset($noanswer)?$noanswer:'');
		$astman->database_put("AMPUSER",$extension."/recording",isset($recording)?$recording:'');
		$astman->database_put("AMPUSER",$extension."/outboundcid",isset($outboundcid)?"\"".$outboundcid."\"":'');
		$astman->database_put("AMPUSER",$extension."/cidname",isset($name)?"\"".$name."\"":'');
		$astman->database_put("AMPUSER",$extension."/cidnum",$cid_masquerade);
		$astman->database_put("AMPUSER",$extension."/voicemail","\"".isset($voicemail)?$voicemail:''."\"");
		switch ($call_screen) {
			case '0':
				$astman->database_del("AMPUSER",$extension."/screen");
				break;
			case 'nomemory':
				$astman->database_put("AMPUSER",$extension."/screen","\"nomemory\"");
				break;
			case 'memory':
				$astman->database_put("AMPUSER",$extension."/screen","\"memory\"");
				break;
			default:
		}

		if (!$editmode) {
			$astman->database_put("AMPUSER",$extension."/device","\"".((isset($device))?$device:'')."\"");
		}

		if (trim($callwaiting) == 'enabled') {
			$astman->database_put("CW",$extension,"\"ENABLED\"");
		} else if (trim($callwaiting) == 'disabled') {
			$astman->database_del("CW",$extension);
		} else {
			echo "ERROR: this state should not exist<br>";
		}

		if (trim($pinless) == 'enabled') {
			$astman->database_put("AMPUSER",$extension."/pinless","\"NOPASSWD\"");
		} else if (trim($pinless) == 'disabled') {
			$astman->database_del("AMPUSER",$extension."/pinless");
		} else {
			echo "ERROR: this state should not exist<br>";
		}

		// Moved VmX setup to voicemail module since it is part of voicemail
		//
	} else {
		die("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}

	// OK - got this far, if they entered a new inbound DID/CID let's deal with it now
	// remember - in the nice and ugly world of this old code, $vars has been extracted
	// newdid and newdidcid

	// Now if $newdid is set we need to add the DID to the routes
	//
	if ($newdid != '' || $newdidcid != '') {
		$did_dest                = 'from-did-direct,'.$extension.',1';
		$did_vars['extension']   = $newdid;
		$did_vars['cidnum']      = $newdidcid;
		$did_vars['privacyman']  = '';
		$did_vars['alertinfo']   = '';
		$did_vars['ringing']     = '';
		$did_vars['mohclass']    = 'default';
		$did_vars['description'] = $newdid_name;
		$did_vars['grppre']      = '';
		$did_vars['delay_answer']= '0';
		$did_vars['pricid']= '';
		freepbx_core_did_add($did_vars, $did_dest);
	}

	return true;
}


function freepbx_core_did_add($incoming,$target=false){
	global $db;
	foreach ($incoming as $key => $val) { ${$key} = $val; } // create variables from request

	$destination= ($target) ? $target : ${$goto0.'0'};
	$sql="INSERT INTO ".FREEPBX_DATABASE.".incoming (cidnum,extension,destination,privacyman,pmmaxretries,pmminlength,alertinfo, ringing, mohclass, description, grppre, delay_answer, pricid) values ('$cidnum','$extension','$destination','$privacyman','$pmmaxretries','$pmminlength','$alertinfo', '$ringing', '$mohclass', '$description', '$grppre', '$delay_answer', '$pricid')";

	return($db->Execute($sql));
}




?>
