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
 * $Id: login_check.php 12211 2012-01-26 17:02:27Z shnoulle $
 */

/**
 * Configuration file
 */
include_once ("config.inc.php");

/**
 * Database file
 */
include_once ("db.inc.php");


function html_escape($str) {
    // escape newline characters, too, in case we put a value from
    // a TEXTAREA  into an <input type="hidden"> value attribute.
    return str_replace(array("\x0A","\x0D"),array("&#10;","&#13;"),
    htmlspecialchars( $str, ENT_QUOTES ));
}



if (!isset($action)) {if (isset($_GET['action'])) {$action=$_GET['action'];} else {$action = "";}}


// check data for login
if( isset($_POST['user']) && isset($_POST['password']) ||
($action == "forgotpass") || ($action == "login") ||
($action == "logout")) {
    include("usercontrol.php");
}

// login form
if(!isset($_SESSION['loginID']) && $action != "forgotpass" && ($action != "logout" || ($action == "logout" && !isset($_SESSION['loginID'])))) // && $action != "login")	// added by Dennis
{
    if (!isset($loginsummary))
    { // could be at login or after logout
        $refererargs=''; // If this is a direct access to admin.php, no args are given
        // If we are called from a link with action and other args set, get them
        if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'])
        {
            $refererargs = html_escape($_SERVER['QUERY_STRING']);
        }

        $sIp = getIPAddress();
        $query = "SELECT * FROM failed_login_attempts WHERE ip='$sIp';";
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $result = $db->query($query) or die ($query."<br />".$db->ErrorMsg());
        $bCannotLogin = false;
        $intNthAttempt = 0;
        if ($result!==false && $result->RecordCount() >= 1)
        {
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
        $loginsummary ="";
        if (!$bCannotLogin)
        {
            if (!isset($logoutsummary))
            {
                $loginsummary = "<form name='loginform' id='loginform' class='form-signin' method='post' action='login.php' ><h2>".T_("You have to login first.")."</h2><br />";
            }
            else
            {
                $loginsummary = "<form name='loginform' id='loginform' class='form-signin' method='post' action='login.php' ><br /><strong>".$logoutsummary."</strong><br /><br />";
            }

            $loginsummary .= "<p><label for='user'>".T_("Username")."</label>
                              <input class='form-control' placeholder='".T_("Username")."' required autofocus name='user' id='user' type='text' size='40' maxlength='40' value='' /></p>
							  <p><label for='password'>".T_("Password")."</label>
                              <input name='password' id='password' class='form-control' placeholder='".T_("Password")."' required type='password' size='40' maxlength='40' /></p>
                           \n
                                                                            <input type='hidden' name='action' value='login' />
                                                                            <input type='hidden' name='refererargs' value='".$refererargs."' />
                                                                            <p><button class='action btn btn-lg btn-primary btn-block' type='submit'>".T_("Login")."</button></p>";
        }
        else{
            $loginsummary .= "<p>".sprintf(T_("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br /></p>";
        }

        $loginsummary .= "                                                </form><br />";
        $loginsummary .= "                                                <script type='text/javascript'>\n";
        $loginsummary .= "                                                  document.getElementById('user').focus();\n";
        $loginsummary .= "                                                </script>\n";
    }
}
