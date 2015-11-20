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


if (isset($_REQUEST['homedir'])) {die('You cannot start this script directly');}
include_once("login_check.php");  //Login Check dies also if the script is started directly
require_once($homedir."/classes/core/sha256.php");

if (isset($_POST['user'])) {$postuser=sanitize_user($_POST['user']);}
if (isset($_POST['email'])) {$postemail=sanitize_email($_POST['email']);}
if (isset($_POST['loginlang'])) {$postloginlang=sanitize_languagecode($_POST['loginlang']);}
if (isset($_POST['new_user'])) {$postnew_user=sanitize_user($_POST['new_user']);}
if (isset($_POST['new_email'])) {$postnew_email=sanitize_email($_POST['new_email']);}
if (isset($_POST['new_full_name'])) {$postnew_full_name=sanitize_userfullname($_POST['new_full_name']);}
if (isset($_POST['uid'])) {$postuserid=sanitize_int($_POST['uid']);}
if (isset($_POST['full_name'])) {$postfull_name=sanitize_userfullname($_POST['full_name']);}



if (!isset($_SESSION['loginID']))
{
    // If Web server Authent delegation is ON, then
    // read the loginname. This can be either PHP_AUTH_USER or
    // REMOTE_USER
    if ($useWebserverAuth === true &&
    !isset($_SERVER['PHP_AUTH_USER']) &&
    isset($_SERVER['REMOTE_USER']) )
    {
        $_SERVER['PHP_AUTH_USER'] = $_SERVER['REMOTE_USER'];
    }

    if($action == "forgotpass" && $display_user_password_in_email === true)
    {
        $loginsummary = "<br /><strong>".$clang->gT("Forgot password")."</strong><br />\n";

        if (isset($postuser) && isset($postemail))
        {
            include("database.php");
            $emailaddr = $postemail;
            $query = "SELECT users_name, password, uid FROM ".db_table_name('users')." WHERE users_name=".$connect->qstr($postuser)." AND email=".$connect->qstr($emailaddr);
            $result = db_select_limit_assoc($query, 1) or safe_die ($query."<br />".$connect->ErrorMsg());  // Checked

            if ($result->RecordCount() < 1)
            {
                // wrong or unknown username and/or email
                $loginsummary .= "<br />".$clang->gT("User name and/or email not found!")."<br />";
                $loginsummary .= "<br /><br /><a href='$scriptname?action=forgotpassword'>".$clang->gT("Continue")."</a></div><br />&nbsp;\n";
            }
            else
            {
                $fields = $result->FetchRow();

                // send Mail
                $new_pass = createPassword();
                $body = sprintf($clang->gT("Your user data for accessing %s"),$sitename). "<br />\n";;
                $body .= $clang->gT("Username") . ": " . $fields['users_name'] . "<br />\n";
                $body .= $clang->gT("New password") . ": " . $new_pass . "<br />\n";

                $subject = $clang->gT("User data","unescaped");
                $to = $emailaddr;
                $from = $siteadminemail;


                if(SendEmailMessage(null, $body, $subject, $to, $from, $sitename, false,$siteadminbounce))
                {
                    $query = "UPDATE ".db_table_name('users')." SET password='".SHA256::hashing($new_pass)."' WHERE uid={$fields['uid']}";
                    $connect->Execute($query); //Checked
                    $loginsummary .= "<br />".$clang->gT("Username").": {$fields['users_name']}<br />".$clang->gT("Email").": {$emailaddr}<br />";
                    $loginsummary .= "<br />".$clang->gT("An email with your login data was sent to you.");
                    $loginsummary .= "<br /><br /><a href='$scriptname'>".$clang->gT("Continue")."</a><br />&nbsp;\n";
                }
                else
                {
                    $tmp = str_replace("{NAME}", "<strong>".$fields['users_name']."</strong>", $clang->gT("Email to {NAME} ({EMAIL}) failed."));
                    $loginsummary .= "<br />".str_replace("{EMAIL}", $emailaddr, $tmp) . "<br />";
                    $loginsummary .= "<br /><br /><a href='$scriptname?action=forgotpassword'>".$clang->gT("Continue")."</a><br />&nbsp;\n";
                }
            }
        }
    }
    elseif($action == "login" && $useWebserverAuth === false)	// normal login
    {
        $loginsummary = '';

        if (isset($postuser) && isset($_POST['password']))
        {
            include("database.php");

            $sIp = getIPAddress();
            $query = "SELECT * FROM ".db_table_name('failed_login_attempts'). " WHERE ip='$sIp';";
            $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
            $result = $connect->query($query);
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
                    $query = "DELETE FROM ".db_table_name('failed_login_attempts'). " WHERE ip='$sIp';";
                    $result = $connect->query($query) or safe_die ($query."<br />".$connect->ErrorMsg());

                }

            }
            if(!$bCannotLogin){
                $query = "SELECT * FROM ".db_table_name('users')." WHERE users_name=".$connect->qstr($postuser);

                $result = $connect->SelectLimit($query, 1) or safe_die ($query."<br />".$connect->ErrorMsg());
                if ($result->RecordCount() < 1)
                {
                    $query = fGetLoginAttemptUpdateQry($bLoginAttempted,$sIp);

                    $result = $connect->Execute($query) or safe_die ($query."<br />".$connect->ErrorMsg());;
                    if ($result)
                    {
                        // wrong or unknown username
                        $loginsummary .= "<p>".$clang->gT("Incorrect username and/or password!")."</p><br />";
                        if ($intNthAttempt+1>=$maxLoginAttempt)
                            $loginsummary .= sprintf($clang->gT("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br />";
                        $loginsummary .= "<br /><a href='$scriptname'>".$clang->gT("Continue")."</a><br />&nbsp;\n";
                    }


                }
                else
                {
                    $fields = $result->FetchRow();
                    if (SHA256::hashing($_POST['password']) == $fields['password'])
                    {
                        // Anmeldung ERFOLGREICH
                        if (strtolower($_POST['password'])=='password')
                        {
                            $_SESSION['pw_notify']=true;
						    $_SESSION['flashmessage']=$clang->gT("Warning: You are still using the default password ('password'). Please change your password and re-login again.");
                        }
                        else
                        {
                            $_SESSION['pw_notify']=false;
                        } // Check if the user has changed his default password

                        if ($sessionhandler=='db')
                        {
                            adodb_session_regenerate_id();
                        }
                        else
                        {
                            session_regenerate_id();

                        }
                        $_SESSION['loginID'] = intval($fields['uid']);
                        $_SESSION['user'] = $fields['users_name'];
                        $_SESSION['full_name'] = $fields['full_name'];
                        $_SESSION['htmleditormode'] = $fields['htmleditormode'];
                        $_SESSION['questionselectormode'] = $fields['questionselectormode'];
                        $_SESSION['templateeditormode'] = $fields['templateeditormode'];
                        $_SESSION['dateformat'] = $fields['dateformat'];
                        // Compute a checksession random number to test POSTs
                        $_SESSION['checksessionpost'] = sRandomChars(10);
                        if (isset($postloginlang) && $postloginlang!='default')
                        {
                            $_SESSION['adminlang'] = $postloginlang;
                            $clang = new limesurvey_lang($postloginlang);
                            $uquery = "UPDATE {$dbprefix}users "
                            . "SET lang='{$postloginlang}' "
                            . "WHERE uid={$_SESSION['loginID']}";
                            $uresult = $connect->Execute($uquery);  // Checked
                        }
                        else
                        {

                            if ( $fields['lang']=='auto' && isset( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) )
                            {
                                $browlang=strtolower( $_SERVER["HTTP_ACCEPT_LANGUAGE"] );
                                $browlang=str_replace(' ', '', $browlang);
                                $browlang=explode( ",", $browlang);
                                $browlang=$browlang[0];
                                $browlang=explode( ";", $browlang);
                                $browlang=$browlang[0];
                                $check=0;
                                $value=26;
                                if ($browlang!="zh-hk" && $browlang!="zh-tw" && $browlang!="es-mx" && $browlang!="pt-br")
                                {
                                    $browlang=explode( "-",$browlang);
                                    $browlang=$browlang[0];
                                }
                                $_SESSION['adminlang']=$browlang;
                            }
                            else
                            {
                                $_SESSION['adminlang'] = $fields['lang'];
                            }
                            $clang = new limesurvey_lang($_SESSION['adminlang']);
                        }
                        $login = true;

                                            $loginsummary .= "<div class='messagebox ui-corner-all'>\n";
                        $loginsummary .= "<div class='header ui-widget-header'>" . $clang->gT("Logged in") . "</div>";
                                            $loginsummary .= "<br />".sprintf($clang->gT("Welcome %s!"),$_SESSION['full_name'])."<br />&nbsp;";
                                            $loginsummary .= "</div>\n";

                        if (isset($_POST['refererargs']) && $_POST['refererargs'] &&
                        strpos($_POST['refererargs'], "action=logout") === FALSE)
                        {
                        	require_once("../classes/inputfilter/class.inputfilter_clean.php");
                        	$myFilter = new InputFilter('','',1,1,1);
                        	// Prevent XSS attacks
                        	$sRefererArg=$myFilter->process($_POST['refererargs']);
                            $_SESSION['metaHeader']="<meta http-equiv=\"refresh\""
                            . " content=\"1;URL={$scriptname}?".$sRefererArg."\" />";
                            $loginsummary .= "<p><font size='1'><i>".$clang->gT("Reloading screen. Please wait.")."</i></font>\n";
                        }
                        $loginsummary .= "<br /><br />\n";
                        GetSessionUserRights($_SESSION['loginID']);

                        //go to queXS
                        $loc = "";
                        if ($_SESSION['USER_RIGHT_SUPERADMIN'] == 1) 
                          $loc = "admin";
                        else
                        {
                          $utest = $connect->GetOne("SELECT username FROM client WHERE username = '" . $_SESSION['user'] . "'");
                          if (!empty($utest))
                            $loc = "client";
                        }
                        header('Location: ' . QUEXS_URL . $loc);
                        die();
                    }
                    else
                    {
                        $query = fGetLoginAttemptUpdateQry($bLoginAttempted,$sIp);

                        $result = $connect->Execute($query) or safe_die ($query."<br />".$connect->ErrorMsg());;
                        if ($result)
                        {
                            // wrong or unknown username
                            $loginsummary .= "<p>".$clang->gT("Incorrect username and/or password!")."<br />";
                            if ($intNthAttempt+1>=$maxLoginAttempt)
                                $loginsummary .= sprintf($clang->gT("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br />";
                            $loginsummary .= "<br /><a href='$scriptname'>".$clang->gT("Continue")."</a><br />&nbsp;\n";
                        }

                    }
                }

            }
            else{
                $loginsummary .= "<p>".sprintf($clang->gT("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br />";
                $loginsummary .= "<br /><a href='$scriptname'>".$clang->gT("Continue")."</a><br />&nbsp;\n";
            }
        }
    }
    elseif($useWebserverAuth === true && !isset($_SERVER['PHP_AUTH_USER']))	// LimeSurvey expects webserver auth  but it has not been achieved
    {
        $loginsummary .= "<br />".$clang->gT("LimeSurvey is setup to use the webserver authentication, but it seems you have not already been authenticated")."<br />";
        $loginsummary .= "<br /><br />".$clang->gT("Please contact your system administrator")."<br />&nbsp;\n";
    }
    elseif($useWebserverAuth === true && isset($_SERVER['PHP_AUTH_USER']))	// normal login through webserver authentication
    {
        $action = 'login';
        // we'll include database.php
        // we need to unset surveyid
        // that could be set if the user clicked on
        // a link with all params before first auto-login
        unset($surveyid);

        $loginsummary = '';
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

        include("database.php");
        $query = "SELECT uid, users_name, password, parent_id, email, lang, htmleditormode, questionselectormode, templateeditormode, dateformat FROM ".db_table_name('users')." WHERE users_name=".$connect->qstr($mappeduser);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC; //Checked
        $result = $connect->SelectLimit($query, 1) or safe_die ($query."<br />".$connect->ErrorMsg());
        if ($result->RecordCount() < 1)
        {
            // In case the hook function is defined
            // overrite the default auto-import profile
            // by this function's result
            if (function_exists("hook_get_autouserprofile"))
            {
                // If defined this function returns an array
                // describing the defaukt profile for this user
                $WebserverAuth_autouserprofile = hook_get_autouserprofile($mappeduser);
            }

            if (isset($WebserverAuth_autocreateUser) &&
            $WebserverAuth_autocreateUser === true &&
            isset($WebserverAuth_autouserprofile) &&
            is_array ($WebserverAuth_autouserprofile) &&
            count($WebserverAuth_autouserprofile) > 0 )
            { // user doesn't exist but auto-create user is set
                $isAuthenticated=false;
                $new_pass = createPassword();

                $uquery = "INSERT INTO {$dbprefix}users "
                ."(users_name, password,full_name,parent_id,lang,email,create_survey,create_user,delete_user,superadmin,configurator,manage_template,manage_label) "
                ."VALUES ("
                . $connect->qstr($mappeduser).", "
                . "'".SHA256::hashing($new_pass)."', "
                . "'".db_quote($WebserverAuth_autouserprofile['full_name'])."', "
                . getInitialAdmin_uid()." , "
                . "'".$WebserverAuth_autouserprofile['lang']."', "
                . "'".db_quote($WebserverAuth_autouserprofile['email'])."', "
                . intval($WebserverAuth_autouserprofile['create_survey']).","
                . intval($WebserverAuth_autouserprofile['create_user']).","
                . intval($WebserverAuth_autouserprofile['delete_user']).","
                . intval($WebserverAuth_autouserprofile['superadmin']).","
                . intval($WebserverAuth_autouserprofile['configurator']).","
                . intval($WebserverAuth_autouserprofile['manage_template']).","
                . intval($WebserverAuth_autouserprofile['manage_label'])
                .")";

                $uresult = $connect->Execute($uquery); //Checked
                if ($uresult)
                {
                    $isAuthenticated=true;
                    $newqid = $connect->Insert_ID("{$dbprefix}users","uid");
                    $arrayTemplates=explode(",",$WebserverAuth_autouserprofile['templatelist']);
                    foreach ($arrayTemplates as $tplname)
                    {
                        $template_query = "INSERT INTO {$dbprefix}templates_rights VALUES('$newqid','$tplname','1')";
                        $connect->Execute($template_query);  //Checked
                    }

                    // read again user from newly created entry
                    $result = $connect->SelectLimit($query, 1) or safe_die ($query."<br />".$connect->ErrorMsg());//Checked
                }
                else
                {
                    $loginsummary .= "<br />".$clang->gT("Auto-import of user failed!")."<br />";
                    $loginsummary .= "<br /><br /><a href='$scriptname'>".$clang->gT("Continue")."</a><br />&nbsp;\n";
                    $isAuthenticated=false;
                }

            }
            else
            {
                $query = fGetLoginAttemptUpdateQry($bLoginAttempted,$sIp);

                $result = $connect->Execute($query) or safe_die ($query."<br />".$connect->ErrorMsg());;
                if ($result)
                {
                    // wrong or unknown username
                    $loginsummary .= "<p>".$clang->gT("Incorrect username and/or password!")."<br />";
                    if ($intNthAttempt+1>=$maxLoginAttempt)
                        $loginsummary .= sprintf($clang->gT("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br />";
                    $loginsummary .= "<br /><a href='$scriptname'>".$clang->gT("Continue")."</a><br />&nbsp;\n";
                }
                $isAuthenticated=false;
            }

        }
        else
        { // User already exists
            $isAuthenticated=true;
        }

        if ($isAuthenticated ===true)
        { // user exists and was authenticated by webserver
            $fields = $result->FetchRow();

            $_SESSION['loginID'] = intval($fields['uid']);
            $_SESSION['user'] = $fields['users_name'];
            $_SESSION['adminlang'] = $fields['lang'];
            $_SESSION['htmleditormode'] = $fields['htmleditormode'];
            $_SESSION['questionselectormode'] = $fields['questionselectormode'];
            $_SESSION['templateeditormode'] = $fields['templateeditormode'];
            $_SESSION['dateformat'] = $fields['dateformat'];
            $_SESSION['checksessionpost'] = sRandomChars(10);
            $_SESSION['pw_notify']=false;
            $clang = new limesurvey_lang($_SESSION['adminlang']);
            $login = true;

            $loginsummary .= "<br /><span style='font-weight:bold;'>" .sprintf($clang->gT("Welcome %s!"),$_SESSION['user']) . "</span><br />";
            $loginsummary .= $clang->gT("You logged in successfully.");

            if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] &&
            strpos($_SERVER['QUERY_STRING'], "action=logout") === FALSE)
            {
                $_SESSION['metaHeader']="<meta http-equiv=\"refresh\""
                . " content=\"1;URL={$scriptname}?".$_SERVER['QUERY_STRING']."\" />";
                $loginsummary .= "<p><font size='1'><i>".$clang->gT("Reloading screen. Please wait.")."</i></font>\n";
            }
            $loginsummary .= "<br /><br />\n";
            GetSessionUserRights($_SESSION['loginID']);
        }
    }
}
elseif ($action == "logout")
{
    killSession();
    $logoutsummary = '<p>'.$clang->gT("Logout successful.");
}

elseif ($action == "adduser" && $_SESSION['USER_RIGHT_CREATE_USER'])
{
    $addsummary = "<div class='header ui-widget-header'>".$clang->gT("Add user")."</div>\n";

    $new_user = FlattenText($postnew_user,true);
    $new_email = FlattenText($postnew_email,true);
    $new_full_name = FlattenText($postnew_full_name,true);

    $valid_email = true;
    if(!validate_email($new_email))
    {
        $valid_email = false;
        $addsummary .= "<div class='messagebox ui-corner-all'><div class='warningheader'>".$clang->gT("Failed to add user")."</div><br />\n" . " " . $clang->gT("The email address is not valid.")."<br />\n";
    }
    if(empty($new_user))
    {
        if($valid_email) $addsummary .= "<br /><strong>".$clang->gT("Failed to add user")."</strong><br />\n" . " ";
        $addsummary .= $clang->gT("A username was not supplied or the username is invalid.")."<br />\n";
    }
    elseif($valid_email)
    {
        $new_pass = createPassword();
        $uquery = "INSERT INTO {$dbprefix}users (users_name, password,full_name,parent_id,lang,email,create_survey,create_user,delete_user,superadmin,configurator,manage_template,manage_label)
                   VALUES ('".db_quote($new_user)."', '".SHA256::hashing($new_pass)."', '".db_quote($new_full_name)."', {$_SESSION['loginID']}, 'auto', '".db_quote($new_email)."',0,0,0,0,0,0,0)";
        $uresult = $connect->Execute($uquery); //Checked

        if($uresult)
        {
            $newqid = $connect->Insert_ID("{$dbprefix}users","uid");

            // add default template to template rights for user
            $template_query = "INSERT INTO {$dbprefix}templates_rights VALUES('$newqid','default','1')";
            $connect->Execute($template_query); //Checked

            // add new user to userlist
            $squery = "SELECT uid, users_name, password, parent_id, email, create_survey, configurator, create_user, delete_user, superadmin, manage_template, manage_label FROM ".db_table_name('users')." WHERE uid='{$newqid}'";			//added by Dennis
            $sresult = db_execute_assoc($squery);//Checked
            $srow = $sresult->FetchRow();
            $userlist = getuserlist();
            array_push($userlist, array("user"=>$srow['users_name'], "uid"=>$srow['uid'], "email"=>$srow['email'],
			"password"=>$srow["password"], "parent_id"=>$srow['parent_id'], // "level"=>$level,
			"create_survey"=>$srow['create_survey'], "configurator"=>$srow['configurator'], "create_user"=>$srow['create_user'],
			"delete_user"=>$srow['delete_user'], "superadmin"=>$srow['superadmin'], "manage_template"=>$srow['manage_template'],
			"manage_label"=>$srow['manage_label']));

            // send Mail
            $body = sprintf($clang->gT("Hello %s,",'unescaped'), $new_full_name)."<br /><br />\n";
            $body .= sprintf($clang->gT("this is an automated email to notify that a user has been created for you on the site '%s'.",'unescaped'), $sitename)."<br /><br />\n";
            $body .= $clang->gT("You can use now the following credentials to log into the site:",'unescaped')."<br />\n";
            $body .= $clang->gT("Username",'unescaped') . ": " . $new_user . "<br />\n";
            if ($useWebserverAuth === false)
            { // authent is not delegated to web server
                // send password (if authorized by config)
                if ($display_user_password_in_email === true)
                {
                    $body .= $clang->gT("Password",'unescaped') . ": " . $new_pass . "<br />\n";
                }
                else
                {
                    $body .= $clang->gT("Password",'unescaped') . ": " . $clang->gT("Please ask your LimeSurvey administrator for your password.") . "<br />\n";
                }
            }

            $body .= "<a href='" . $homeurl . "/admin.php'>".$clang->gT("Click here to log in.",'unescaped')."</a><br /><br />\n";
            $body .=  sprintf($clang->gT('If you have any questions regarding this mail please do not hesitate to contact the site administrator at %s. Thank you!','unescaped'),$siteadminemail)."<br />\n";

            $subject = sprintf($clang->gT("User registration at '%s'","unescaped"),$sitename);
            $to = $new_user." <$new_email>";
            $from = $siteadminname." <$siteadminemail>";
            $addsummary .="<div class='messagebox ui-corner-all'>";
            if(SendEmailMessage(null, $body, $subject, $to, $from, $sitename, true, $siteadminbounce))
            {
                $addsummary .= "<br />".$clang->gT("Username").": $new_user<br />".$clang->gT("Email").": $new_email<br />";
                $addsummary .= "<br />".$clang->gT("An email with a generated password was sent to the user.");
            }
            else
            {
                // has to be sent again or no other way
                $tmp = str_replace("{NAME}", "<strong>".$new_user."</strong>", $clang->gT("Email to {NAME} ({EMAIL}) failed."));
                $addsummary .= "<br />".str_replace("{EMAIL}", $new_email, $tmp) . "<br />";
            }

            $addsummary .= "<br />\t\t\t<form method='post' action='$scriptname'>"
            ."<input type='submit' value='".$clang->gT("Set user permissions")."'>"
            ."<input type='hidden' name='action' value='setuserrights'>"
            ."<input type='hidden' name='user' value='{$new_user}'>"
            ."<input type='hidden' name='uid' value='{$newqid}'>"
            ."</form></div>";
        }
        else{
            $addsummary .= "<div class='messagebox ui-corner-all'><div class='warningheader'>".$clang->gT("Failed to add user")."</div><br />\n" . " " . $clang->gT("The user name already exists.")."<br />\n";
        }
    }
    $addsummary .= "<p><input type=\"submit\" onclick=\"window.open('$scriptname?action=editusers', '_self')\" value=\"".$clang->gT("Continue")."\"/></div>\n";
}

elseif (($action == "deluser" || $action == "finaldeluser") && ($_SESSION['USER_RIGHT_SUPERADMIN'] == 1 || $_SESSION['USER_RIGHT_DELETE_USER'] ))
{
    $addsummary = "<div class=\"header\">".$clang->gT("Deleting user")."</div>\n";
    $addsummary .= "<div class=\"messagebox\">\n";

    // CAN'T DELETE ORIGINAL SUPERADMIN
    // Initial SuperAdmin has parent_id == 0
    $adminquery = "SELECT uid FROM {$dbprefix}users WHERE parent_id=0";
    $adminresult = db_select_limit_assoc($adminquery, 1);//Checked
    $row=$adminresult->FetchRow();

    if($row['uid'] == $postuserid)	// it's the original superadmin !!!
    {
        $addsummary .= "<div class=\"warningheader\">".$clang->gT("Initial Superadmin cannot be deleted!")."</div>\n";
    }
    else
    {
        if (isset($postuserid))
        {
            $sresultcount = 0;// 1 if I am parent of $postuserid
            if ($_SESSION['USER_RIGHT_SUPERADMIN'] != 1)
            {
                $squery = "SELECT uid FROM {$dbprefix}users WHERE uid=$postuserid AND parent_id=".$_SESSION['loginID'];
                $sresult = $connect->Execute($squery); //Checked
                $sresultcount = $sresult->RecordCount();
            }

            if ($_SESSION['USER_RIGHT_SUPERADMIN'] == 1 || $sresultcount > 0 || $postuserid == $_SESSION['loginID'])
            {
                $transfer_surveys_to = 0;
                $query = "SELECT users_name, uid FROM ".db_table_name('users').";";
                $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());

                $current_user = $_SESSION['loginID'];
                if($result->RecordCount() == 2) {

                    $action = "finaldeluser";
                    while($rows = $result->FetchRow()){
                            $intUid = $rows['uid'];
                            $selected = '';
                            if ($intUid == $current_user)
                                $selected = " selected='selected'";

                            if ($postuserid != $intUid)
                                $transfer_surveys_to = $intUid;
                    }
                }

                $query = "SELECT sid FROM ".db_table_name('surveys')." WHERE owner_id = $postuserid ;";
                $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
                if($result->RecordCount() == 0) {
                    $action = "finaldeluser";
                 }

                if ($action=="finaldeluser")
                {
                    if (isset($_POST['transfer_surveys_to'])) {$transfer_surveys_to=sanitize_int($_POST['transfer_surveys_to']);}
                    if ($transfer_surveys_to > 0){
                        $query = "UPDATE ".db_table_name('surveys')." SET owner_id = $transfer_surveys_to WHERE owner_id=$postuserid";
                        $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
                    }
                    $squery = "SELECT parent_id FROM {$dbprefix}users WHERE uid=".$postuserid;
                    $sresult = $connect->Execute($squery); //Checked
                    $fields = $sresult->FetchRow($sresult);

                    if (isset($fields[0]))
                    {
                        $uquery = "UPDATE ".db_table_name('users')." SET parent_id={$fields[0]} WHERE parent_id=".$postuserid;	//		added by Dennis
                        $uresult = $connect->Execute($uquery); //Checked
                    }

                    //DELETE USER FROM TABLE
                    $dquery="DELETE FROM {$dbprefix}users WHERE uid=".$postuserid;	//	added by Dennis
                    $dresult=$connect->Execute($dquery);  //Checked

                    // Delete user rights
                    $dquery="DELETE FROM {$dbprefix}survey_permissions WHERE uid=".$postuserid;
                    $dresult=$connect->Execute($dquery); //Checked

                    if($postuserid == $_SESSION['loginID'])
                    {
                      killSession();    // user deleted himself
                      header( "Location: " . $homeurl . "/admin.php");
                      die();
                    }

                    $addsummary .= "<br />".$clang->gT("Username").": {$postuser}<br /><br />\n";
                    $addsummary .= "<div class=\"successheader\">".$clang->gT("Success!")."</div>\n";
                    if ($transfer_surveys_to>0){
                        $sTransferred_to = getUserNameFromUid($transfer_surveys_to);
                        $addsummary .= sprintf($clang->gT("All of the user's surveys were transferred to %s."),$sTransferred_to);
                    }
                    $addsummary .= "<br/><input type=\"submit\" onclick=\"window.open('$scriptname?action=editusers', '_self')\" value=\"".$clang->gT("Continue")."\"/>\n";
                }
                else
                {
                    $current_user = $_SESSION['loginID'];
                    $addsummary .= "<br />".$clang->gT("Transfer the user's surveys to: ")."\n";
                    $addsummary .= "<form method='post' name='deluserform' action='admin.php?action=finaldeluser'><select name='transfer_surveys_to'>\n";
                    $query = "SELECT users_name, uid FROM ".db_table_name('users').";";
                    $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
                    if($result->RecordCount() > 0) {
                        while($rows = $result->FetchRow()){
                                $intUid = $rows['uid'];
                                $sUsersName = $rows['users_name'];
                                $selected = '';
                                if ($intUid == $current_user)
                                    $selected = " selected='selected'";

                                if ($postuserid != $intUid)
                                    $addsummary .= "<option value='$intUid'$selected>$sUsersName</option>\n";
                        }
                    }
                    $addsummary .= "</select><input type='hidden' name='uid' value='$postuserid'>";
                    $addsummary .= "<input type='hidden' name='user' value='$postuser'>";
                    $addsummary .= "<input type='hidden' name='action' value='finaldeluser'><br /><br />";
                    $addsummary .= "<input type='submit' value='".$clang->gT("Delete User")."'></form>";
                }

            }
            else
            {
                include("access_denied.php");
            }
        }
        else
        {
            $addsummary .= "<div class=\"warningheader\">".$clang->gT("Could not delete user. User was not supplied.")."</div>\n";
            $addsummary .= "<br/><input type=\"submit\" onclick=\"window.open('$scriptname?action=editusers', '_self')\" value=\"".$clang->gT("Continue")."\"/>\n";
        }
    }
    $addsummary .= "</div>\n";
}



elseif ($action == "moduser")
{
    $addsummary = "<div class='header ui-widget-header'>".$clang->gT("Editing user")."</div>\n";
    $addsummary .= "<div class=\"messagebox\">\n";

    $squery = "SELECT uid FROM {$dbprefix}users WHERE uid=$postuserid AND parent_id=".$_SESSION['loginID'];
    $sresult = $connect->Execute($squery); //Checked
    $sresultcount = $sresult->RecordCount();

    if(($_SESSION['USER_RIGHT_SUPERADMIN'] == 1 || $postuserid == $_SESSION['loginID'] ||
    ($sresultcount > 0 && $_SESSION['USER_RIGHT_CREATE_USER'])) && !($demoModeOnly == true && $postuserid == 1)
    )
    {
        $users_name = html_entity_decode($postuser, ENT_QUOTES, 'UTF-8');
        $email = html_entity_decode($postemail,ENT_QUOTES, 'UTF-8');
        $sPassword = html_entity_decode($_POST['pass'],ENT_QUOTES, 'UTF-8');
        if ($sPassword=='%%unchanged%%') $sPassword='';
        $full_name = html_entity_decode($postfull_name,ENT_QUOTES, 'UTF-8');
        $valid_email = true;

        if(!validate_email($email))
        {
            $valid_email = false;
            $failed = true;
            $addsummary .= "<div class=\"warningheader\">".$clang->gT("Could not modify user data.")."</div><br />\n"
            . " ".$clang->gT("Email address is not valid.")."<br />\n";
        }
        elseif($valid_email)
        {
            $failed = false;
            if(empty($sPassword))
            {
                $uquery = "UPDATE ".db_table_name('users')." SET email='".db_quote($email)."', full_name='".db_quote($full_name)."' WHERE uid=".$postuserid;
            } else {
                $uquery = "UPDATE ".db_table_name('users')." SET email='".db_quote($email)."', full_name='".db_quote($full_name)."', password='".SHA256::hashing($sPassword)."' WHERE uid=".$postuserid;
            }

            $uresult = $connect->Execute($uquery);//Checked

            if($uresult && empty($sPassword))
            {
                $addsummary .= "<br />".$clang->gT("Username").": $users_name<br />".$clang->gT("Password").": (".$clang->gT("Unchanged").")<br /><br />\n";
                $addsummary .= "<div class=\"successheader\">".$clang->gT("Success!")."</div>\n";
            } elseif($uresult && !empty($sPassword))
            {
                if ($display_user_password_in_html === true)
                {
                    $displayedPwd = $sPassword;
                }
                else
                {
                    $displayedPwd = preg_replace('/./','*',$sPassword);
                }
                $addsummary .= "<br />".$clang->gT("Username").": $users_name<br />".$clang->gT("Password").": {$displayedPwd}<br /><br />\n";
                $addsummary .= "<div class=\"successheader\">".$clang->gT("Success!")."</div>\n";
            }
            else
            {
                // Username and/or email adress already exists.
                $addsummary .= "<div class=\"warningheader\">".$clang->gT("Could not modify user data.")."</div><br />\n"
                . " ".$clang->gT("Email address already exists.")."<br />\n";
            }
        }
        if($failed)
        {
            $addsummary .= "<br /><form method='post' action='$scriptname'>"
            ."<input type='submit' value='".$clang->gT("Back")."'>"
            ."<input type='hidden' name='action' value='modifyuser'>"
            ."<input type='hidden' name='uid' value='{$postuserid}'>"
            ."</form>";
        }
        else
        {
            $addsummary .= "<br/><input type=\"submit\" onclick=\"window.open('$scriptname?action=editusers', '_self')\" value=\"".$clang->gT("Continue")."\"/>\n";
        }
    }
    else
    {
        include("access_denied.php");
    }
    $addsummary .= "</div>\n";
}

elseif ($action == "userrights")
{
    $addsummary = "<div class='header ui-widget-header'>".$clang->gT("Set user permissions")."</div>\n";
    $addsummary .= "<div class=\"messagebox\">\n";

    // A user can't modify his own rights ;-)
    if($postuserid != $_SESSION['loginID'])
    {
        $squery = "SELECT uid FROM {$dbprefix}users WHERE uid=$postuserid AND parent_id=".$_SESSION['loginID'];
        $sresult = $connect->Execute($squery); // Checked
        $sresultcount = $sresult->RecordCount();

        if($_SESSION['USER_RIGHT_SUPERADMIN'] != 1 && $sresultcount > 0)
        { // Not Admin, just a user with childs
            $rights = array();

            // Forbids Allowing more privileges than I have
            if(isset($_POST['create_survey']) && $_SESSION['USER_RIGHT_CREATE_SURVEY'])$rights['create_survey']=1;		else $rights['create_survey']=0;
            if(isset($_POST['configurator']) && $_SESSION['USER_RIGHT_CONFIGURATOR'])$rights['configurator']=1;			else $rights['configurator']=0;
            if(isset($_POST['create_user']) && $_SESSION['USER_RIGHT_CREATE_USER'])$rights['create_user']=1;			else $rights['create_user']=0;
            if(isset($_POST['delete_user']) && $_SESSION['USER_RIGHT_DELETE_USER'])$rights['delete_user']=1;			else $rights['delete_user']=0;

            $rights['superadmin']=0; // ONLY Initial Superadmin can give this right
            if(isset($_POST['manage_template']) && $_SESSION['USER_RIGHT_MANAGE_TEMPLATE'])$rights['manage_template']=1;	else $rights['manage_template']=0;
            if(isset($_POST['manage_label']) && $_SESSION['USER_RIGHT_MANAGE_LABEL'])$rights['manage_label']=1;			else $rights['manage_label']=0;

            if ($postuserid<>1) setuserrights($postuserid, $rights);
            $addsummary .= "<div class=\"successheader\">".$clang->gT("User permissions were updated successfully.")."</div>\n";
            $addsummary .= "<br/><input type=\"submit\" onclick=\"window.open('$scriptname?action=editusers', '_self')\" value=\"".$clang->gT("Continue")."\"/>\n";
        }
        elseif ($_SESSION['USER_RIGHT_SUPERADMIN'] == 1)
        {
            $rights = array();

            if(isset($_POST['create_survey']))$rights['create_survey']=1;		else $rights['create_survey']=0;
            if(isset($_POST['configurator']))$rights['configurator']=1;			else $rights['configurator']=0;
            if(isset($_POST['create_user']))$rights['create_user']=1;			else $rights['create_user']=0;
            if(isset($_POST['delete_user']))$rights['delete_user']=1;			else $rights['delete_user']=0;

            // Only Initial Superadmin can give this right
            if(isset($_POST['superadmin']))
            {
                // Am I original Superadmin ?

                // Initial SuperAdmin has parent_id == 0
                $adminquery = "SELECT uid FROM {$dbprefix}users WHERE parent_id=0";
                $adminresult = db_select_limit_assoc($adminquery, 1);
                $row=$adminresult->FetchRow();

                if($row['uid'] == $_SESSION['loginID'])	// it's the original superadmin !!!
                {
                    $rights['superadmin']=1;
                }
                else
                {
                    $rights['superadmin']=0;
                }
            }
            else
            {
                $rights['superadmin']=0;
            }

            if(isset($_POST['manage_template']))$rights['manage_template']=1;	else $rights['manage_template']=0;
            if(isset($_POST['manage_label']))$rights['manage_label']=1;			else $rights['manage_label']=0;

            setuserrights($postuserid, $rights);
            $addsummary .= "<div class=\"successheader\">".$clang->gT("User permissions were updated successfully.")."</div>\n";
            $addsummary .= "<br/><input type=\"submit\" onclick=\"window.open('$scriptname?action=editusers', '_self')\" value=\"".$clang->gT("Continue")."\"/>\n";
        }
        else
        {
            include("access_denied.php");
        }
    }
    else
    {
        $addsummary .= "<div class=\"warningheader\">".$clang->gT("You are not allowed to change your own permissions!")."</div>\n";
        $addsummary .= "<br/><input type=\"submit\" onclick=\"window.open('$scriptname?action=editusers', '_self')\" value=\"".$clang->gT("Continue")."\"/>\n";
    }
    $addsummary .= "</div>\n";
}

elseif ($action == "usertemplates")
{
    $addsummary = "<div class='header ui-widget-header'>".$clang->gT("Set template permissions")."</div>\n";
    $addsummary .= "<div class=\"messagebox\">\n";

    // SUPERADMINS AND MANAGE_TEMPLATE USERS CAN SET THESE RIGHTS
    if( $_SESSION['USER_RIGHT_SUPERADMIN'] == 1 || $_SESSION['USER_RIGHT_MANAGE_TEMPLATE'] == 1)
    {
        $templaterights = array();
        $tquery = "SELECT * FROM ".$dbprefix."templates";
        $tresult = db_execute_assoc($tquery);
        while ($trow = $tresult->FetchRow()) {
            if (isset($_POST[$trow["folder"]."_use"]))
            $templaterights[$trow["folder"]] = 1;
            else
            $templaterights[$trow["folder"]] = 0;
        }
        foreach ($templaterights as $key => $value) {
            $uquery = "INSERT INTO {$dbprefix}templates_rights (uid,".db_quote_id('folder').",".db_quote_id('use').")  VALUES ({$postuserid},'".$key."',$value)";
            $uresult = $connect->execute($uquery);
            if (!$uresult)
            {
                $uquery = "UPDATE {$dbprefix}templates_rights  SET  ".db_quote_id('use')."=$value where ".db_quote_id('folder')."='$key' AND uid=".$postuserid;
                $uresult = $connect->execute($uquery);
            }
        }
        if ($uresult)
        {
            $addsummary .= "<div class=\"successheader\">".$clang->gT("Template permissions were updated successfully.")."</div>\n";
            $addsummary .= "<br/><input type=\"submit\" onclick=\"window.open('$scriptname?action=editusers', '_self')\" value=\"".$clang->gT("Continue")."\"/>\n";
        }
        else
        {
            $addsummary .= "<div class=\"warningheader\">".$clang->gT("Error")."</div>\n";
            $addsummary .= "<br />".$clang->gT("Error while updating usertemplates.")."<br />\n";
            $addsummary .= "<br/><input type=\"submit\" onclick=\"window.open('$scriptname?action=editusers', '_self')\" value=\"".$clang->gT("Continue")."\"/>\n";
        }
    }
    else
    {
        include("access_denied.php");
    }
    $addsummary .= "</div>\n";
}


function getInitialAdmin_uid()
{
    global $dbprefix;
    // Initial SuperAdmin has parent_id == 0
    $adminquery = "SELECT uid FROM {$dbprefix}users WHERE parent_id=0";
    $adminresult = db_select_limit_assoc($adminquery, 1);
    $row=$adminresult->FetchRow();
    return $row['uid'];
}

function fGetLoginAttemptUpdateQry($la,$sIp)
{
    $timestamp = date("Y-m-d H:i:s");
    if ($la)
        $query = "UPDATE ".db_table_name('failed_login_attempts')
                 ." SET number_attempts=number_attempts+1, last_attempt = '$timestamp' WHERE ip='$sIp'";
    else
        $query = "INSERT INTO ".db_table_name('failed_login_attempts') . "(ip, number_attempts,last_attempt)"
                 ." VALUES('$sIp',1,'$timestamp')";

    return $query;
}


function getUserNameFromUid($uid){
    $query = "SELECT users_name, uid FROM ".db_table_name('users')." WHERE uid = $uid;";

    $result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());


    if($result->RecordCount() > 0) {
        while($rows = $result->FetchRow()){
            return $rows['users_name'];
        }
    }
}
