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
 * $Id: login_check_cas.php 12211 2012-01-26 17:02:27Z shnoulle $
 */
if (!isset($dbprefix) || isset($_REQUEST['dbprefix'])) {die("Cannot run this script directly");}
if (!isset($action)) {$action=returnglobal('action');}
//
// phpCAS simple client
//

if(!isset($_SESSION['CASauthenticated']) || (isset($_SESSION['CASauthenticated']) && $_SESSION['CASauthenticated']==FALSE) || (isset($_REQUEST['action']) && $_REQUEST['action'] =='logout') )
{
    //echo "bla";
    // import phpCAS lib
    include_once('classes/phpCAS/CAS.php');


//        phpCAS::setDebug();

        
        phpCAS::client(CAS_VERSION_2_0, $casAuthServer,$casAuthPort, $casAuthUri);

        phpCAS::setNoCasServerValidation();

        if (isset($_REQUEST['action']) && $_REQUEST['action']=='logout')
    {
        phpCAS::handleLogoutRequests();
        //session_unset();
        phpCAS::logout();
        session_destroy();
        session_write_close();
        //phpCAS::forceAuthentication();
    }
  else
  {
        // force CAS authentication
        $auth = phpCAS::forceAuthentication();

        if($auth)
        {
           
            $query = "SELECT uid, users_name, password, one_time_pw, dateformat, full_name, htmleditormode, questionselectormode, templateeditormode FROM ".db_table_name('users')." WHERE users_name=".$connect->qstr(phpCAS::getUser());
            $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC; //Checked
            $result = $connect->SelectLimit($query, 1) or safe_die ($query."<br />".$connect->ErrorMsg());
            if(!$result)
            {
                echo "<br />".$connect->ErrorMsg();
            }
            if ($result->RecordCount() < 1)
            {
                // wrong or unknown username
              $loginsummary = sprintf($clang->gT("No user"))."<br />";
                if ($sessionhandler=='db')
                {
                    adodb_session_regenerate_id();
                }
                else
                {
                    session_regenerate_id();
                }
            }
            else
            {
         
                  $srow = $result->FetchRow();
                    $_SESSION['user'] = $srow['users_name'];
                    $_SESSION['checksessionpost'] = sRandomChars(10);
                    $_SESSION['loginID'] = $srow['uid'];
                    $_SESSION['dateformat'] = $srow['dateformat'];
                    $_SESSION['htmleditormode'] = $srow['htmleditormode'];
                    $_SESSION['questionselectormode'] = $srow['questionselectormode'];
                    $_SESSION['templateeditormode'] = $srow['templateeditormode'];
                    $_SESSION['full_name'] = $srow['full_name'];
                    GetSessionUserRights($_SESSION['loginID']);

                    $auth = TRUE;
                    $_SESSION['CASauthenticated'] = $auth;
                
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

        }
        else
        {
            $auth = FALSE;
            $_SESSION['CASauthenticated'] = $auth;
        }

  }

}

?>
