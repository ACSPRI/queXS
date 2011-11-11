<?php

//Remote Control 2 backported from Limesurvey_CI to Limesurvey 1.91+.
//Only implemented functions required for queXS - i.e
// * webserver based authentication only
// * addResponse RPC call only

require_once(dirname(__FILE__).'/../classes/core/startup.php');
require_once(dirname(__FILE__).'/../config-defaults.php');
require_once(dirname(__FILE__).'/../common.php');
require_once(dirname(__FILE__)."/classes/xmlrpc/lib/xmlrpc.inc");
require_once(dirname(__FILE__)."/classes/xmlrpc/lib/xmlrpcs.inc");
    

$config = array('get_session_key' => array('function' => 'getSessionKey'),
		'add_response' => array('function' => 'addResponse'));

$s=new xmlrpc_server($config);
//$s->setdebug(3);
//$s->compress_response = true;
$s->service();

/**
* XML-RPC routine to create a session key
*
* @param array $request Array containing username and password
*/
function getSessionKey($request)
{
    if (!is_object($request)) die();
    //$sUserName=$request->getParam(0);
    //$sPassword=$request->getParam(1);
    if (_doLogin())
    {
        return new xmlrpcresp(new xmlrpcval('OK'));
    }
    else
    {
	return new xmlrpcresp(0, 1, 'Login failed');
    }
}


/**
* XML-RPC routine to add a response to the survey table
* Returns the id of the inserted survey response
*
* @param array $request Array containing the following elements (in that order):
* - Session key (string)
* - Survey ID (integer)
* - ResponseData (array)
* 
*/
function addResponse($request)
{
    global $connect,$dbprefix;

    if (!is_object($request)) die();

    if ($request->getNumParams() != 3)
    {
	return new xmlrpcresp(0, 3, 'Missing parameters');
    }

    $sSessionKey=$request->getParam(0)->scalarVal();
    $iSurveyID=(int)$request->getParam(1)->scalarVal();
    $aResponseData=php_xmlrpc_decode($request->getParam(2));

    if (!is_array($aResponseData))
	return new xmlrpcresp(0, '14', 'Survey data is not in array form');
    
    $uid = _doLogin();

    if($uid)
    {
       if(bHasSurveyPermission($iSurveyID,'responses','create',$uid))
       {
           $surveytable = db_table_name("survey_".$iSurveyID);
           if (!db_tables_exist($dbprefix."survey_".$iSurveyID))
           {
                return new xmlrpcresp(0, '12', 'No survey table');
           }

            //set required values if not set
            if (!isset($aResponseData['submitdate'])) $aResponseData['submitdate'] = date("Y-m-d H:i:s");
            if (!isset($aResponseData['datestamp'])) $aResponseData['datestamp'] = date("Y-m-d H:i:s");
            if (!isset($aResponseData['startdate'])) $aResponseData['startdate'] = date("Y-m-d H:i:s");
            if (!isset($aResponseData['startlanguage'])) $aResponseData['startlanguage'] = GetBaseLanguageFromSurveyID($iSurveyID);
            $SQL = "INSERT INTO $surveytable
                                        (".implode(',',array_keys($aResponseData)).")
                                        VALUES
                                        (".implode(',',array_map('db_quoteall',$aResponseData)).")";

            $iinsert = $connect->Execute($SQL);

            if ($iinsert)
            {
                $thisid=$connect->Insert_ID();
        	return new xmlrpcresp(new xmlrpcval($thisid,'int'));
            }
            else
            {
                //Failed to insert return error
	        return new xmlrpcresp(0, '13', 'Unable to add response');
            }
        }
        else
	    return new xmlrpcresp(0, '2', 'No permission');
    }
	die();
} 

/**
* Tries to login using webserver auth
*
* @param string $sUsername
* @param mixed $sPassword
*/
function _doLogin($sUsername = '', $sPassword = '')
{
    global $connect,$useWebserverAuth;
    if ($useWebserverAuth !== true) //only implement webserver auth atm
	 return false;

    if (!isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['REMOTE_USER']))
        $_SERVER['PHP_AUTH_USER'] = $_SERVER['REMOTE_USER'];


    // getting user name, optionnally mapped
    if (isset($userArrayMap) && is_array($userArrayMap) &&
    isset($userArrayMap[$_SERVER['PHP_AUTH_USER']]))
    {
        $mappeduser=$userArrayMap[$_SERVER['PHP_AUTH_USER']];
    }
    else
    {
        $mappeduser=$_SERVER['PHP_AUTH_USER'];
    }

    $query = "SELECT uid, users_name, password, parent_id, email, lang, htmleditormode, dateformat FROM ".db_table_name('users')." WHERE users_name=".$connect->qstr($mappeduser);
    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC; //Checked
    $result = $connect->SelectLimit($query, 1) or safe_die ($query."<br />".$connect->ErrorMsg());


    if ($result->RecordCount() == 1)
    {
	$srow = $result->FetchRow();
	return $srow['uid'];
    }

    return false;
}
