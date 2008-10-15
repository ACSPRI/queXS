<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */
// +-----------------------------------------------------------------------+
// |                                                                       |
// | Copyright � 2003 Heino H. Gehlsen. All Rights Reserved.               |
// |                  http://www.heino.gehlsen.dk/software/license         |
// |                                                                       |
// +-----------------------------------------------------------------------+
// |                                                                       |
// | This work (including software, documents, or other related items) is  |
// | being provided by the copyright holders under the following license.  |
// | By obtaining, using and/or copying this work, you (the licensee)      |
// | agree that you have read, understood, and will comply with the        |
// | following terms and conditions:                                       |
// |                                                                       |
// | Permission to use, copy, modify, and distribute this software and     |
// | its documentation, with or without modification, for any purpose and  |
// | without fee or royalty is hereby granted, provided that you include   |
// | the following on ALL copies of the software and documentation or      |
// | portions thereof, including modifications, that you make:             |
// |                                                                       |
// | 1. The full text of this NOTICE in a location viewable to users of    |
// |    the redistributed or derivative work.                              |
// |                                                                       |
// | 2. Any pre-existing intellectual property disclaimers, notices, or    |
// |    terms and conditions. If none exist, a short notice of the         |
// |    following form (hypertext is preferred, text is permitted) should  |
// |    be used within the body of any redistributed or derivative code:   |
// |    "Copyright � 2003 Heino H. Gehlsen. All Rights Reserved.           |
// |     http://www.heino.gehlsen.dk/software/license"                     |
// |                                                                       |
// | 3. Notice of any changes or modifications to the files, including     |
// |    the date changes were made. (We recommend you provide URIs to      |
// |    the location from which the code is derived.)                      |
// |                                                                       |
// | THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT    |
// | HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED,    |
// | INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR        |
// | FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE    |
// | OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD PARTY PATENTS,           |
// | COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.                               |
// |                                                                       |
// | COPYRIGHT HOLDERS WILL NOT BE LIABLE FOR ANY DIRECT, INDIRECT,        |
// | SPECIAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF ANY USE OF THE        |
// | SOFTWARE OR DOCUMENTATION.                                            |
// |                                                                       |
// | The name and trademarks of copyright holders may NOT be used in       |
// | advertising or publicity pertaining to the software without specific, |
// | written prior permission. Title to copyright in this software and any |
// | associated documentation will at all times remain with copyright      |
// | holders.                                                              |
// |                                                                       |
// +-----------------------------------------------------------------------+
// |                                                                       |
// | This license is based on the "W3C� SOFTWARE NOTICE AND LICENSE".      |
// | No changes have been made to the "W3C� SOFTWARE NOTICE AND LICENSE",  |
// | except for the references to the copyright holder, which has either   |
// | been changes or removed.                                              |
// |                                                                       |
// +-----------------------------------------------------------------------+
// $Id: Client.php,v 1.2.2.2 2005/01/14 20:03:22 heino Exp $

require_once 'Net/NNTP/Protocol/Client.php';


// {{{ constants

/* NNTP Authentication modes */
define('NET_NNTP_CLIENT_AUTH_ORIGINAL', 'original');
define('NET_NNTP_CLIENT_AUTH_SIMPLE',   'simple');
define('NET_NNTP_CLIENT_AUTH_GENERIC',  'generic');

// }}}
// {{{ Net_NNTP_Client

/**
 * Implementation of the client side of NNTP (Network News Transfer Protocol)
 *
 * The Net_NNTP_Client class is a frontend class to the Net_NNTP_Protocol_Client class.
 *
 * @category   Net
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @version    $Id: Client.php,v 1.2.2.2 2005/01/14 20:03:22 heino Exp $
 * @access     public
 * @see        Net_NNTP_Protocol_Client
 * @since      Class available since Release 0.11.0
 */
class Net_NNTP_Client extends Net_NNTP_Protocol_Client
{
    // {{{ properties

    /**
     * Used for storing information about the currently selected group
     *
     * @var array
     * @access private
     * @since 0.3
     */
    var $_currentGroup = null;

    // }}}
    // {{{ constructor

    /**
     * Constructor
     *
     * @access public
     */
    function Net_NNTP_Client()
    {
    	parent::Net_NNTP_Protocol_Client();
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the NNTP-server.
     *
     * @param optional string $host The adress of the NNTP-server to connect to.
     * @param optional int $port The port to connect to.
     *
     * @return mixed (bool) true on success or (object) pear_error on failure
     * @access public
     * @see Net_NNTP_Client::quit()
     * @see Net_NNTP_Client::authenticate()
     * @see Net_NNTP_Client::connectAuthenticated()
     */
    function connect($host = NET_NNTP_PROTOCOL_CLIENT_DEFAULT_HOST,
                     $port = NET_NNTP_PROTOCOL_CLIENT_DEFAULT_PORT)
    {
    	return parent::connect($host, $port);
    }

    // }}}
    // {{{ quit()

    /**
     * Close connection to the newsserver
     *
     * @access public
     * @see Net_NNTP_Client::connect()
     */
    function quit()
    {
        return $this->cmdQuit();
    }

    // }}}
    // {{{ authenticate()

    /**
     * Authenticate
     * 
     * Auth process (not yet standarized but used any way)
     * http://www.mibsoftware.com/userkt/nntpext/index.html
     *
     * @param string $user The username
     * @param optional string $pass The password
     * @param optional string $mode The authentication mode (original, simple, generic).
     *
     * @return mixed (bool) true on success or (object) pear_error on failure
     * @access public
     * @see Net_NNTP_Client::connect()
     * @see Net_NNTP_Client::connectAuthenticated()
     */
    function authenticate($user, $pass, $mode = NET_NNTP_CLIENT_AUTH_ORIGINAL)
    {
        // Username is a must...
        if ($user == null) {
            return PEAR::throwError('No username supplied', null);
        }

        // Use selected authentication method
        switch ($mode) {
            case NET_NNTP_CLIENT_AUTH_ORIGINAL:
                return $this->cmdAuthinfo($user, $pass);
                break;
            case NET_NNTP_CLIENT_AUTH_SIMPLE:
                return $this->cmdAuthinfoSimple($user, $pass);
                break;
            case NET_NNTP_CLIENT_AUTH_GENERIC:
                return $this->cmdAuthinfoGeneric($user, $pass);
                break;
            default:
                return PEAR::throwError("The auth mode: '$mode' is unknown", null);
        }
    }

    // }}}
    // {{{ isConnected()

    /**
     * Test whether a connection is currently open.
     *
     * @return bool true or false
     * @access public
     * @see Net_NNTP_Client::connect()
     * @see Net_NNTP_Client::quit()
     */
    function isConnected()
    {
        return parent::isConnected();
    }

    // }}}
    // {{{ selectGroup()

    /**
     * Selects a newsgroup
     *
     * @param string $newsgroup Newsgroup name
     *
     * @return mixed (array) Info about the newsgroup on success or (object) pear_error on failure
     * @access public
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::count()
     * @see Net_NNTP_Client::getGroups()
     */
    function selectGroup($newsgroup)
    {
        $response_arr = $this->cmdGroup($newsgroup);
    	if (PEAR::isError($response_arr)) {
    	    return $response_arr;
    	}

    	// Store group info in the object
    	$this->_currentGroup = $response_arr;

    	return $response_arr;
    }

    // }}}
    // {{{ getGroups()

    /**
     * Fetches a list of all avaible newsgroups
     *
     * @return mixed (array) nested array with informations about existing newsgroups on success or (object) pear_error on failure
     * @access public
     * @see Net_NNTP_Client::selectGroup()
     * @see Net_NNTP_Client::getDescriptions()
     */
    function getGroups()
    {
    	// Get groups
    	$groups = $this->cmdList();
    	if (PEAR::isError($groups)) {
    	    return $groups;
    	}

    	return $groups;
    }

    // }}}
    // {{{ getDescriptions()

    /**
     * Fetches a list of all avaible newsgroup descriptions.
     *
     * @return mixed (array) nested array with description of existing newsgroups on success or (object) pear_error on failure
     * @access public
     * @see Net_NNTP_Client::getGroups()
     */
    function getDescriptions()
    {
    	// Get group descriptions
    	$descriptions = $this->cmdListNewsgroups();
    	if (PEAR::isError($descriptions)) {
    	    return $descriptions;
    	}
	
    	return $descriptions;
    }

    // }}}
    // {{{ getOverview()

    /**
     * Fetch message header fields from message number $first to $last
     *
     * The format of the returned array is:
     * $messages[message_id][header_name]
     *
     * @param integer $first first article to fetch
     * @param integer $last  last article to fetch
     *
     * @return mixed (array) nested array of message and their headers on success or (object) pear_error on failure
     * @access public
     * @see Net_NNTP_Client::getOverviewFormat()
     * @see Net_NNTP_Client::getReferencesOverview()
     */
    function getOverview($first, $last)
    {
    	$overview = $this->cmdXOver($first.'-'.$last);
    	if (PEAR::isError($overview)) {
    	    return $overview;
    	}
	
    	return $overview;
    }

    // }}}
    // {{{ getOverviewFmt()

    /**
     * Returns a list of avaible headers which are send from NNTP-server to the client for every news message
     *
     * @return mixed (array) header names on success or (object) pear_error on failure
     * @access public
     * @see Net_NNTP_Client::getOverview()
     */
    function getOverviewFormat()
    {
    	return $this->cmdListOverviewFmt();
    }

    // }}}
    // {{{ post()

    /**
     * Post an article to a number of newsgroups.
     *
     * (Among the aditional headers you might think of adding could be:
     * "NNTP-Posting-Host: <ip-of-author>", which should contain the IP-address
     * of the author of the post, so the message can be traced back to him.
     * Or "Organization: <org>" which contain the name of the organization
     * the post originates from)
     *
     * @param string $newsgroups The newsgroup to post to.
     * @param string $subject The subject of the post.
     * @param string $body The body of the post itself.
     * @param string $from Name + email-adress of sender.
     * @param optional string $aditional Aditional headers to send.
     *
     * @return mixed (string) server response on success or (object) pear_error on failure
     * @access public
     */
    function post($newsgroups, $subject, $body, $from, $aditional = null)
    {
    	return $this->cmdPost($newsgroups, $subject, $body, $from, $aditional);
    }

    // }}}
    // {{{ getArticleRaw()

    /**
     * Get a article (raw data)
     *
     * @param mixed $article Either the message-id or the message-number on the server of the article to fetch.
     * @param optional bool  $implode When true the result array is imploded to a string, defaults to false.
     *
     * @return mixed (array/string) The article on success or (object) pear_error on failure
     * @access public
     * @see Net_NNTP_Client::getArticle()
     * @see Net_NNTP_Client::getHeaderRaw()
     * @see Net_NNTP_Client::getBodyRaw()
     */
    function getArticleRaw($article, $implode = false)
    {
        $data = $this->cmdArticle($article);
        if (PEAR::isError($data)) {
    	    return $data;
    	}

    	if ($implode == true) {
    	    $data = implode("\r\n", $data);
    	}

    	return $data;
    }

    // }}}
    // {{{ getHeaderRaw()

    /**
     * Get the header of an article (raw data)
     *
     * @param mixed $article Either the (string) message-id or the (int) message-number on the server of the article to fetch.
     * @param optional bool $implode When true the result array is imploded to a string, defaults to false.
     *
     * @return mixed (array/string) header fields on success or (object) pear_error on failure
     * @access public
     * @see Net_NNTP_Client::getHeader()
     * @see Net_NNTP_Client::getArticleRaw()
     * @see Net_NNTP_Client::getBodyRaw()
     */
    function getHeaderRaw($article, $implode = false)
    {
        $data = $this->cmdHead($article);
        if (PEAR::isError($data)) {
    	    return $data;
    	}

    	if ($implode == true) {
    	    $data = implode("\r\n", $data);
    	}

    	return $data;
    }

    // }}}
    // {{{ getBodyRaw()

    /**
     * Get the body of an article (raw data)
     *
     * @param mixed $article Either the message-id or the message-number on the server of the article to fetch.
     * @param optional bool $implode When true the result array is imploded to a string, defaults to false.
     *
     * @return mixed (array/string) body on success or (object) pear_error on failure
     * @access public
     * @see Net_NNTP_Client::getBody()
     * @see Net_NNTP_Client::getHeaderRaw()
     * @see Net_NNTP_Client::getArticleRaw()
     */
    function getBodyRaw($article, $implode = false)
    {
        $data = $this->cmdBody($article);
        if (PEAR::isError($data)) {
    	    return $data;
    	}
	
    	if ($implode == true) {
    	    $data = implode("\r\n", $data);
    	}
	
    	return $data;
    }

    // }}}
    // {{{ getDate()

    /**
     * Get the NNTP-server's internal date
     *
     * Get the date from the newsserver format of returned date:
     *
     * @param optional int $format
     *  - 0: $date - timestamp
     *  - 1: $date['y'] - year
     *       $date['m'] - month
     *       $date['d'] - day
     *
     * @return mixed (mixed) date on success or (object) pear_error on failure
     * @access public
     * @since 0.3
     */
    function getDate($format = 1)
    {
        $date = $this->cmdDate();
        if (PEAR::isError($date)) {
    	    return $date;
    	}

    	switch ($format) {
    	    case 1:
    	        return array('y' => substr($date, 0, 4), 'm' => substr($date, 4, 2), 'd' => substr($date, 6, 2));
    	        break;

    	    case 0:
    	    default:
    	        return $date;
    	        break;
    	}
    }

    // }}}
    // {{{ count()

    /**
     * Number of articles in currently selected group
     *
     * @return integer number of article in group
     * @access public
     * @since 0.3
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::selectGroup()
     */
    function count()
    {
        return $this->_currentGroup['count'];
    }

    // }}}
    // {{{ last()

    /**
     * Maximum article number in currently selected group
     *
     * @return integer number of last article
     * @access public
     * @since 0.3
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::count()
     * @see Net_NNTP_Client::selectGroup()
     */
    function last()
    {
    	return $this->_currentGroup['last'];
    }

    // }}}
    // {{{ first()

    /**
     * Minimum article number in currently selected group
     *
     * @return integer number of first article
     * @access public
     * @since 0.3
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::count()
     * @see Net_NNTP_Client::selectGroup()
     */
    function first()
    {
    	return $this->_currentGroup['first'];
    }

    // }}}
    // {{{ group()

    /**
     * Currently selected group
     *
     * @return string group name
     * @access public
     * @since 0.3
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::count()
     * @see Net_NNTP_Client::selectGroup()
     */
    function group()
    {
    	return $this->_currentGroup['group'];
    }

    // }}}

}

// }}}

?>
