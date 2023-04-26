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
 * $Id: usercontrol.php 12260 2012-01-31 00:32:32Z c_schmitz $
 */

// Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

/**
 * Configuration file
 */
include_once ("config.inc.php");

/**
 * Database file
 */
include_once ("db.inc.php");


include_once("login_check.php");  //Login Check dies also if the script is started directly

$maxLoginAttempt = 10;
$timeOutTime = 600; // 10 minutes

// sanitize a username
// allow for instance 0-9a-zA-Z@_-.
function sanitize_user($string)
{
    $username_length=64;
    $string=mb_substr($string,0,$username_length);
    return $string;
}



if (isset($_POST['user'])) {$postuser=sanitize_user($_POST['user']);}


if (!isset($_SESSION['loginID']))
{
    if($action != "logout")	// normal login
    {
        $loginsummary = '';

        if (isset($postuser) && isset($_POST['password']))
        {
            $sIp = getIPAddress();
            $query = "SELECT * FROM failed_login_attempts WHERE ip='$sIp';";
            $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
            $result = $db->query($query);
            $bLoginAttempted = false;
            $bCannotLogin = false;

            $intNthAttempt = 0;
            if ($result!==false && $result->RecordCount() >= 1)
            {
                $bLoginAttempted = true;
                $field = $result->FetchRow();
                $intNthAttempt = $field['number_attempts'];
                if ($intNthAttempt>=$maxLoginAttempt){
                    $bCannotLogin = true;
                }

                $iLastAttempt = strtotime($field['last_attempt']);

                if (time() > $iLastAttempt + $timeOutTime){
                    $bCannotLogin = false;
                    $query = "DELETE FROM failed_login_attempts WHERE ip='$sIp';";
                    $result = $db->query($query) or die ($query."<br />".$db->ErrorMsg());

                }

            }
            if(!$bCannotLogin){
                $query = "SELECT * FROM users WHERE users_name=".$db->qstr($postuser);

                $result = $db->SelectLimit($query, 1) or die ($query."<br />".$db->ErrorMsg());
                if ($result->RecordCount() < 1)
                {
                    $query = fGetLoginAttemptUpdateQry($bLoginAttempted,$sIp);

                    $result = $db->Execute($query) or die ($query."<br />".$db->ErrorMsg());;
                    if ($result)
                    {
                        // wrong or unknown username
                        $loginsummary .= "<p>".T_("Incorrect username and/or password!")."</p><br />";
                        if ($intNthAttempt+1>=$maxLoginAttempt)
                            $loginsummary .= sprintf(T_("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br />";
                        $loginsummary .= "<br /><a href='$scriptname'>".T_("Continue")."</a><br />&nbsp;\n";
                    }


                }
                else
                {
                    $fields = $result->FetchRow();
                    if (hash('sha256',$_POST['password']) == $fields['password'])
                    {
                        // Anmeldung ERFOLGREICH
                        if (strtolower($_POST['password'])=='password')
                        {
                            $_SESSION['pw_notify']=true;
						    $_SESSION['flashmessage']=T_("Warning: You are still using the default password ('password'). Please change your password and re-login again.");
                        }
                        else
                        {
                            $_SESSION['pw_notify']=false;
                        } // Check if the user has changed his default password

                        session_regenerate_id();

                        $_SESSION['loginID'] = intval($fields['uid']);
                        $_SESSION['user'] = $fields['users_name'];
                        $_SESSION['full_name'] = $fields['full_name'];
                        // Compute a checksession random number to test POSTs
                        $_SESSION['checksessionpost'] = sRandomChars(10);
                        $login = true;

                                            $loginsummary .= "<div class='messagebox ui-corner-all'>\n";
                        $loginsummary .= "<div class='header ui-widget-header'>" . T_("Logged in") . "</div>";
                                            $loginsummary .= "<br />".sprintf(T_("Welcome %s!"),$_SESSION['full_name'])."<br />&nbsp;";
                                            $loginsummary .= "</div>\n";

                        $_SESSION['USER_RIGHT_SUPERADMIN'] = 0;
                                              
                                            if ($fields['superadmin'] == 1) {

                        $_SESSION['USER_RIGHT_SUPERADMIN'] = 1;
                                               }
                                                  

                        //go to queXS
                        $loc = "";
                        if ($_SESSION['USER_RIGHT_SUPERADMIN'] == 1) 
                          $loc = "admin";
                        else
                        {
                          $utest = $db->GetOne("SELECT username FROM client WHERE username = '" . $_SESSION['user'] . "'");
                          if (!empty($utest))
                            $loc = "client";
                        }
                        header('Location: ' . QUEXS_URL . $loc);
                        die();
                    }
                    else
                    {
                        $query = fGetLoginAttemptUpdateQry($bLoginAttempted,$sIp);

                        $result = $db->Execute($query) or die ($query."<br />".$db->ErrorMsg());;
                        if ($result)
                        {
                            // wrong or unknown username
                            $loginsummary .= "<p>".T_("Incorrect username and/or password!")."<br />";
                            if ($intNthAttempt+1>=$maxLoginAttempt)
                                $loginsummary .= sprintf(T_("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br />";
                            $loginsummary .= "<br /><a href='$scriptname'>".T_("Continue")."</a><br />&nbsp;\n";
                        }

                    }
                }

            }
            else{
                $loginsummary .= "<p>".sprintf(T_("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br />";
                $loginsummary .= "<br /><a href='$scriptname'>".T_("Continue")."</a><br />&nbsp;\n";
            }
        }
    }
}
elseif ($action == "logout")
{
    killSession();
    $logoutsummary = '<p>'.T_("Logout successful.");
}

// unsets all Session variables to kill session
function killSession()  //added by Dennis
{
    // Delete the Session Cookie
    $CookieInfo = session_get_cookie_params();
    if ( (empty($CookieInfo['domain'])) && (empty($CookieInfo['secure'])) ) {
        setcookie(session_name(), '', time()-3600, $CookieInfo['path']);
    } elseif (empty($CookieInfo['secure'])) {
        setcookie(session_name(), '', time()-3600, $CookieInfo['path'], $CookieInfo['domain']);
    } else {
        setcookie(session_name(), '', time()-3600, $CookieInfo['path'], $CookieInfo['domain'], $CookieInfo['secure']);
    }
    unset($_COOKIE[session_name()]);
    foreach ($_SESSION as $key =>$value)
    {
        //echo $key." = ".$value."<br />";
        unset($_SESSION[$key]);
    }
    $_SESSION = array(); // redundant with previous lines
    session_unset();
    @session_destroy();
}


function fGetLoginAttemptUpdateQry($la,$sIp)
{
    $timestamp = date("Y-m-d H:i:s");
    if ($la)
        $query = "UPDATE failed_login_attempts"
                 ." SET number_attempts=number_attempts+1, last_attempt = '$timestamp' WHERE ip='$sIp'";
    else
        $query = "INSERT INTO failed_login_attempts(ip, number_attempts,last_attempt)"
                 ." VALUES('$sIp',1,'$timestamp')";

    return $query;
}

