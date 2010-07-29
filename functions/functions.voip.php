<?
/**
 * Functions to interact with Asterisk
 *      Some examples taken from {@link http://www.voip-info.org/wiki/index.php?page=Asterisk+manager+Example%3A+PHP voip-info.org}
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
 * Class to interact with Asterisk
 *
 * @package queXS
 */
class voip {
	/**
	 * Socket connection to Asterisk server
	 */
	var $socket;

	/**
	 * Close the socket gracefully on destruct
	 */
	function __destruct() {
		//close the socket
		if ($this->socket !== false)
		{
			fclose($this->socket); 
			$this->socket = false; 	
		}
	}


	/**
	 * Return a list of IAX extensions
	 * as an associative array
	 *
	 * @return array Key is extension, value is status
	 *
	 */
	function getIAXStatus()
	{
		$ret = $this->query("Action: IAXPeerList\r\n\r\n","PeerlistComplete");

		$c = spliti("\r\n\r\n",$ret);
		$chans = array();
		foreach ($c as $s)
		{
			if(eregi("Event: PeerEntry.*ObjectName: ([0-9a-zA-Z-]+).*Status: ([/0-9a-zA-Z-]+)",$s,$regs))
			{
				//print T_("Channel: SIP/") . $regs[1] . " BridgedChannel " . $regs[2] . "\n";
				$chan = substr($regs[1],0,4);
				$chans[$chan] = $regs[2];
			}
		}
		return $chans;
	}



	/**
	 * Return a list of active extensions and their corresponding
	 * channels as an associative array
	 *
	 * @return array Key is extension, value is Asterisk channel 
	 *
	 */
	function getChannels()
	{
		$ret = $this->query("Action: Status\r\n\r\n","StatusComplete");

		$c = spliti("\r\n\r\n",$ret);
		$chans = array();
		foreach ($c as $s)
		{
			if(eregi("Event: Status.*Channel: (SIP/|IAX2/[0-9a-zA-Z-]+).*BridgedChannel: (SIP/|IAX2/[/0-9a-zA-Z-]+)",$s,$regs))
			{
				//print T_("Channel: SIP/") . $regs[1] . " BridgedChannel " . $regs[2] . "\n";
				$ccs = explode('-',$regs[1]);
				$chan = $ccs[0];
				$chans[$chan] = array($regs[1],$regs[2]);
			}
			else if(eregi("Event: Status.*Channel: (SIP/|IAX2/[0-9a-zA-Z-]+).*",$s,$regs))
			{
				//print T_("Channel: ") . $regs[1] .  "\n";
				$ccs = explode('-', $regs[1]);
				$chan = $ccs[0];
				$chans[$chan] = array($regs[1],false);
			}
		}
		return $chans;
		
	}


	/**
	 * Return the channel (if active) given the extension
	 * Return false if no channel found
	 *
	 * @param string $ext Extension as in Asterisk
	 * @return string|bool The Asterisk channel or false if no channel exists
	 *
	 */
	function getChannel($ext,$link = false)
	{
		$v = $this->getChannels();
		if (isset($v[$ext]))
		{
			if ($link)
				return $v[$ext][1];
			else
				return $v[$ext][0];	
		}
		else
			return false;
	}


	/**
	 * Add another party to an active call (eg add in the supervisor)
	 *
	 * @param int $ext The extension of the current call
	 * @param int $number The phone number to add to the call
	 * @todo CHECK IF THE MEETING ROOM IS EMPTY before adding use meetme list
	 *
	 */
	function addParty($ext,$number)
	{
		if($ext)
		{
			$channel = $this->getChannel($ext);
			$link = $this->getChannel($ext,true);
			
	
			//check if the meeting room is empty

			//if so:
			// 1. call the supervisor to the room
			$q = "Action: Originate\r\nChannel: Local/$number@from-internal\r\nPriority: 1\r\nContext: default\r\nApplication: MeetMe\r\nData: " . MEET_ME_ROOM . ",d\r\n\r\n";
			$r = $this->query($q,"Meetme");

				
			// 2. transfer the current call to the room
			$r = $this->query("Action: Redirect\r\nChannel: $channel\r\nExten: " . MEET_ME_ROOM . "\r\nPriority: 1\r\n\r\n","Response");
			$r = $this->query("Action: Redirect\r\nChannel: $link\r\nExten: " . MEET_ME_ROOM . "\r\nContext: from-internal-xfer\r\nPriority: 1\r\n\r\n","Response");

			
		}

	}

	/**
	 * Dial call from the call database
	 *
	 * @param string $ext The extension to originate the call from
	 * @param string $number The number to dial
	 *
	 */
	function dial($ext,$number)
	{
		$r = $this->query("Action: Originate\r\nChannel: $ext\r\nExten: $number\r\nPriority: 1\r\nCallerid: $ext\r\n\r\n","Response");
	}


	/**
	 * Hang up the current call by the extension
	 *
	 * @param int $ext The extension to hang up for
	 *
	 */
	function hangup($ext)
	{
		if($ext)
		{
			$channel = $this->getChannel($ext);
			$r = $this->query("Action: Hangup\r\nChannel: $channel\r\n\r\n","Response");
		}

	}

	/**
	 * Begin recording the call to a file
	 *
	 * @param string $ext The Asterisk extension
	 * @param bool|string $filename False for an auto generated file name else specify file name
 	 * @todo Handle multiple recordings
	 *
	 */
	function beginRecord($ext,$filename = false)
	{

		if($ext)
		{
			$channel = $this->getChannel($ext);
			$r = $this->query("Action: Monitor\r\nChannel: $channel\r\nFile: $filename\r\nFormat: gsm\r\nMix: 1\r\n\r\n","Response");
		}

	}


	/**
	 * End the recording on this extension
	 *
	 * @param string $ext The Asterisk extension
	 * @todo Handle multiple recordings
	 * @see beginRecord()
	 *
	 */
	function endRecord($ext)
	{

		if($ext)
		{
			$channel = $this->getChannel($ext);
			$r = $this->query("Action: StopMonitor\r\nChannel: $channel\r\n\r\n","Response");
		}
	}

	/**
	 * Return the status of an extension
	 *
	 * @param int $ext The extension
	 * @return bool|int false if not available, 1 for available, 2 for available and on a call
	 *
	 */
	function getExtensionStatus($ext)
	{
		if($ext)
		{
			$type = "SIP";
			$exts = explode('/', $ext, 2);
			if (isset($exts[0]))
				$type = $exts[0];
			if (isset($exts[1]))
				$ext = $exts[1];
	
			if ($type == "SIP")
			{
				$ret = $this->query("Action: ExtensionState\r\nContext: from-internal\r\nExten: $ext\r\nActionID: \r\n\r\n","Status:");
				if(eregi("Status: ([0-9]+)",$ret,$regs))
				{
					if (isset($regs[1]))
					{
						// 0 appears to be online, 1 online and on a call
						if ($regs[1] == 0)
							return 1;
						else if ($regs[1] == 1 || $regs[1] == 8)
							return 2;
					}
				}
			}
			else if ($type == "IAX2")
			{
				$exts = $this->getIAXStatus();
				if (isset($exts[$ext]))
				{
					$status = $exts[$ext];
					if ($status == "OK")
						return 1;
				}				
			}
		}
		return false;
	}

	/**
	 * Return whether we are connected to the Asterisk server or not
	 *
	 * @return True if connected else false
	 *
	 */
	function isConnected()
	{
		if ($this->socket)
			return true;
		else
			return false;
	}

	/**
	 * Connect to the Asterisk server
	 *
	 *  @param string $ip The IP Address
	 *  @param string $user Username for Asterisk manager
	 *  @param string $pass Password for Asterisk manager
	 *  @param bool $events If events should be enabled or not (default false)
	 *
	 *  @return bool True if connected successfully, else false
	 */
	function connect($ip=VOIP_SERVER,$user=VOIP_ADMIN_USER,$pass=VOIP_ADMIN_PASS,$events = false)
	{
		$this->socket = fsockopen($ip,VOIP_PORT,$errno,$errstr,1);
		if (!$this->socket)
		{
			//print "$errno: $errstr";
			//exit();
			return false;
		}

		stream_set_timeout($this->socket, 1);

		$q = "Action: Login\r\nUsername: $user\r\nSecret: $pass\r\nEvents: ";

		if ($events)
			$q .= "on";
		else
			$q .= "off";

		$q .= "\r\n\r\n";

		$r = $this->query($q,"accepted");

		if (strpos($r,"Response: Success"))
		{
			return true;
		}
		else
		{
			fclose($this->socket);
			return false;
		}
	}

	/**
	 * Query the Asterisk server and wait for a response or timeout
	 *
	 * @param string $query The string to send to the Asterisk manager, see {@link http://www.voip-info.org/wiki/view/Asterisk+manager+API API} for details
	 * @param string $waitfor A string within the return string to wait for before returning
	 * @return string The response string from Asterisk
	 *
	 */
	function query($query,$waitfor=false)
	{
		$wrets = "";
   
		if ($this->socket === false)
		     return false;
     
		fputs($this->socket, $query); 

		$c = 1;
		do
		{
			$line = fgets($this->socket, 4096);
			$wrets .= $line;
			$info = stream_get_meta_data($this->socket);
		} while ($line != "\n" && !$info['timed_out'] && (strpos($line,$waitfor) === false));
		
		return $wrets;
	} 
}



/**
 * Class used to watch Asterisk events and effect changes to queXS database
 *
 * @package queXS
 * @todo automatically code a call if we know it is busy
 */
class voipWatch extends voip {

	var $keepWatching = true;


	function dbReconnect()
	{
		global $db;
		
		//keep reconnecting to the db so it doesn't time out
		$db = newADOConnection(DB_TYPE);
		$db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$db->SetFetchMode(ADODB_FETCH_ASSOC);
	
	}

	/**
	 * Get the call_id based on the extension
	 *
	 * @param int $ext the extension
	 * @return int The call_id
	 */	
	function getCallId($ext)
	{	
		global $db;
	
		$sql = "SELECT l.call_id
                        FROM operator AS o
                        JOIN (`case` AS c, `call_attempt` AS ca, `call` AS l) ON
                                                      ( c.current_operator_id = o.operator_id
                                                        AND c.case_id = ca.case_id
                                                        AND ca.operator_id = o.operator_id
                                                        AND ca.end IS NULL
                                                        AND l.call_attempt_id = ca.call_attempt_id
                        	                        AND l.outcome_id =0 )
                        WHERE o.extension = '$ext'";

		$rs = $db->GetRow($sql);
		$call_id =0;
		if (!empty($rs))
			$call_id =$rs['call_id'];

		return $call_id;	
	}


	function setState($call_id,$state,$checkOutcome = false)
	{
		global $db;


		$sql = "UPDATE `call`
			SET state = '$state'
			WHERE call_id = '$call_id'";

		if ($checkOutcome) $sql .= " AND outcome_id = 0";

		$db->Execute($sql);	
	}

	/**
	 *  Watch for Asterisk events and make changes to the queXS databse if
	 *  appropriate
	 *
	 *  
	 */
	function watch($process_id = false)
	{
		/**
		 * Process file
		 */
		if ($process_id) include_once(dirname(__FILE__).'/../functions/functions.process.php');
	
	
		$line = "";
   
		if ($this->socket === false)
		     return false;

		do
		{
			if (!$this->isConnected() || $this->socket === false){
				print(T_("Disconnected") . "\n");
				$this->connect(VOIP_SERVER,VOIP_ADMIN_USER,VOIP_ADMIN_PASS,true);
				if ($this->isConnected()) print (T_("Reconnected") . "\n");
			}

			$in = fgets($this->socket, 4096);

			//print "IN: $in\n";

			/**
			 * When we have reached the end of a message, process it
			 *
			 */
			if ($in == "\r\n")
			{
				//print "PROCESS: ";
				/**
				 * The call is ringing
				 */
				if (eregi("Event: Dial.*SubEvent: Begin.*Channel: ((SIP/|IAX2/)[0-9]+)",$line,$regs))
				{
					$call_id = $this->getCallId($regs[1]);
					if ($call_id != 0)
					{
						print T_("Ringing") . T_(" Extension ") . $regs[1] . "\n"; 
						$this->setState($call_id,2);	
					}
				}
				/**
				 * The call has been answered
				 */
				else if (eregi("Event: Bridge.*Channel1: ((SIP/|IAX2/)[0-9]+)",$line,$regs))
				{
					$call_id = $this->getCallId($regs[1]);
					if ($call_id != 0)
					{
						print T_("Answered") . T_(" Extension ") . $regs[1] .  "\n";
						$this->setState($call_id,3);
					}
				}
				/**
				 * The call has been hung up
				 */
				else if (eregi("Event: Hangup.*Channel: ((SIP/|IAX2/)[0-9]+)",$line,$regs))
				{
					$call_id = $this->getCallId($regs[1]);
					if ($call_id != 0)
					{
						print T_("Hangup") . T_(" Extension ") . $regs[1] . "\n";
						$this->setState($call_id,4,true);
					}
				}

				//print $line . "\n\n";
				$line = "";
			}
			else
			{
				/**
				 * Append the lines to the message if we are not yet at the end of one
				 */
				$line .= $in;
			}

			

			@flush();

			if ($process_id)
			{
				$this->dbReconnect();
				$this->keepWatching = !is_process_killed($process_id);
			}

		} while ($this->keepWatching);
		
	} 
}



?>
