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
 *	$Id: admin_functions.php 9586 2010-12-06 03:08:07Z c_schmitz $
 *	Files Purpose:
 */


function get2post($url)
{
    $url = preg_replace('/&amp;/i','&',$url);
    list($calledscript,$query) = explode('?',$url);
    $aqueryitems = explode('&',$query);
    $arrayParam = Array();
    $arrayVal = Array();

    foreach ($aqueryitems as $queryitem)
    {
        list($paramname, $value) = explode ('=', $queryitem);
        $arrayParam[] = "'".$paramname."'";
        $arrayVal[] = substr($value, 0, 9) != "document." ? "'".$value."'" : $value;
    }
    //	$Paramlist = "[" . implode(",",$arrayParam) . "]";
    //	$Valuelist = "[" . implode(",",$arrayVal) . "]";
    $Paramlist = "new Array(" . implode(",",$arrayParam) . ")";
    $Valuelist = "new Array(" . implode(",",$arrayVal) . ")";
    $callscript = "sendPost('$calledscript','".$_SESSION['checksessionpost']."',$Paramlist,$Valuelist);";
    return $callscript;
}

/**
* This function switches identity insert on/off for the MSSQL database
*
* @param string $table table name (without prefix)
* @param mixed $state  Set to true to activate ID insert, or false to deactivate
*/
function db_switchIDInsert($table,$state)
{
    global $databasetype, $connect;
    if ($databasetype=='odbc_mssql' || $databasetype=='odbtp' || $databasetype=='mssql_n' || $databasetype=='mssqlnative')
    {
        if ($state==true)
        {
            $connect->Execute('SET IDENTITY_INSERT '.db_table_name($table).' ON');
        }
        else
        {
            $connect->Execute('SET IDENTITY_INSERT '.db_table_name($table).' OFF');
        }
    }
}

/**
 * Returns true if a user has permissions in the particular survey
 *
 * @param $iSID The survey ID
 * @param $sPermission
 * @param $sCRUD
 * @param $iUID User ID - if not given the one of the current user is used
 * @return bool
 */
function bHasSurveyPermission($iSID, $sPermission, $sCRUD, $iUID=null)
{
    global $dbprefix, $connect;
    if (!in_array($sCRUD,array('create','read','update','delete','import','export'))) return false;
    $sCRUD=$sCRUD.'_p';
    $iSID = (int)$iSID;
    global $aSurveyPermissionCache;
    
    if (is_null($iUID))
    {
      if (isset($_SESSION['loginID'])) $iUID = $_SESSION['loginID']; 
       else return false;
      if ($_SESSION['USER_RIGHT_SUPERADMIN']==1) return true; //Superadmin has access to all
    }

    if (!isset($aSurveyPermissionCache[$iSID][$iUID][$sPermission][$sCRUD]))
    {
        $sSQL = "SELECT {$sCRUD} FROM " . db_table_name('survey_permissions') . " 
                WHERE sid={$iSID} AND uid = {$iUID}
                and permission=".db_quoteall($sPermission); //Getting rights for this survey
        $bPermission = $connect->GetOne($sSQL);
        if ($bPermission==0 || is_null($bPermission)) $bPermission=false;
        if ($bPermission==1) $bPermission=true;
        $aSurveyPermissionCache[$iSID][$iUID][$sPermission][$sCRUD]=$bPermission;
    }
    return $aSurveyPermissionCache[$iSID][$iUID][$sPermission][$sCRUD];
}

/**
 * Returns true if the given survey has a File Upload Question Type
 * @param $surveyid The survey ID
 * @return bool
 */
function bHasFileUploadQuestion($surveyid) {
    $fieldmap = createFieldMap($surveyid);

    foreach ($fieldmap as $field) {
        if (isset($field['type']) &&  $field['type'] === '|') return true;
    }
}

/**
 * Returns true if a user has global permission for a certain action. Available permissions are
 * 
 * USER_RIGHT_CREATE_SURVEY
 * USER_RIGHT_CONFIGURATOR
 * USER_RIGHT_CREATE_USER
 * USER_RIGHT_DELETE_USER
 * USER_RIGHT_SUPERADMIN
 * USER_RIGHT_MANAGE_TEMPLATE
 * USER_RIGHT_MANAGE_LABEL
 *
 * @param $sPermission
 * @return bool
 */
function bHasGlobalPermission($sPermission)
{
    global $dbprefix, $connect;
    global $aSurveyGlobalPermissionCache;

    if (isset($_SESSION['loginID'])) $iUID = $_SESSION['loginID']; 
        else return false;
    if ($_SESSION['USER_RIGHT_SUPERADMIN']==1) return true; //Superadmin has access to all
    if ($_SESSION[$sPermission]==1)
    {
        return true;
    }
    else
    {
        return false;
    }

}

/**
* Set the survey permissions for a user. Beware that all survey permissions for the particual survey are removed before the new ones are written.
* 
* @param int $iUserID The User ID
* @param int $iSurveyID The Survey ID 
* @param array $aPermissions  Array with permissions in format <permissionname>=>array('create'=>0/1,'read'=>0/1,'update'=>0/1,'delete'=>0/1)
*/
function SetSurveyPermissions($iUserID, $iSurveyID, $aPermissions)
{
    global $connect, $surveyid;
    $iUserID=sanitize_int($iUserID);
    $sQuery = "delete from ".db_table_name('survey_permissions')." WHERE sid = {$iSurveyID} AND uid = {$iUserID}";
    $connect->Execute($sQuery);
    $bResult=true;
    
    foreach($aPermissions as $sPermissionname=>$aPermissions)
    {
        if (!isset($aPermissions['create'])) {$aPermissions['create']=0;}
        if (!isset($aPermissions['read'])) {$aPermissions['read']=0;}
        if (!isset($aPermissions['update'])) {$aPermissions['update']=0;}
        if (!isset($aPermissions['delete'])) {$aPermissions['delete']=0;}
        if (!isset($aPermissions['import'])) {$aPermissions['import']=0;}
        if (!isset($aPermissions['export'])) {$aPermissions['export']=0;}
        if ($aPermissions['create']==1 || $aPermissions['read']==1 ||$aPermissions['update']==1 || $aPermissions['delete']==1  || $aPermissions['import']==1  || $aPermissions['export']==1)
        {
            $sQuery = "INSERT INTO ".db_table_name('survey_permissions')." (sid, uid, permission, create_p, read_p, update_p, delete_p, import_p, export_p)
                       VALUES ({$iSurveyID},{$iUserID},'{$sPermissionname}',{$aPermissions['create']},{$aPermissions['read']},{$aPermissions['update']},{$aPermissions['delete']},{$aPermissions['import']},{$aPermissions['export']})";
            $bResult=$connect->Execute($sQuery);
        }
    }
    return $bResult;
}

/**
* Gives all available survey permissions for a certain survey to a user 
* 
* @param mixed $iUserID  The User ID
* @param mixed $iSurveyID The Survey ID
*/
function GiveAllSurveyPermissions($iUserID, $iSurveyID)
{
     $aPermissions=aGetBaseSurveyPermissions();
     $aPermissionsToSet=array();
     foreach ($aPermissions as $sPermissionName=>$aPermissionDetails)
     {
         foreach ($aPermissionDetails as $sPermissionDetailKey=>$sPermissionDetailValue)
         {
           if (in_array($sPermissionDetailKey,array('create','read','update','delete','import','export')) && $sPermissionDetailValue==true)
           {
               $aPermissionsToSet[$sPermissionName][$sPermissionDetailKey]=1;    
           }
             
         }
     }
     SetSurveyPermissions($iUserID, $iSurveyID, $aPermissionsToSet);
}

function gettemplatelist()
{
    global $usertemplaterootdir, $standardtemplates,$standardtemplaterootdir;

    if (!$usertemplaterootdir) {die("gettemplatelist() no template directory");}
    if ($handle = opendir($standardtemplaterootdir))
    {
        while (false !== ($file = readdir($handle)))
        {
            if (!is_file("$standardtemplaterootdir/$file") && $file != "." && $file != ".." && $file!=".svn" && isStandardTemplate($file))
            {
                $list_of_files[$file] = $standardtemplaterootdir.DIRECTORY_SEPARATOR.$file;
            }
        }
        closedir($handle);
    }

    if ($handle = opendir($usertemplaterootdir))
    {
        while (false !== ($file = readdir($handle)))
        {
            if (!is_file("$usertemplaterootdir/$file") && $file != "." && $file != ".." && $file!=".svn")
            {
                $list_of_files[$file] = $usertemplaterootdir.DIRECTORY_SEPARATOR.$file;
            }
        }
        closedir($handle);
    }
    ksort($list_of_files);
    return $list_of_files;
}


/**
* This function set a question attribute to a certain value
*
* @param mixed $qid
* @param mixed $sAttributeName
* @param mixed $sAttributeValue
*/
function setQuestionAttribute($qid,$sAttributeName,$sAttributeValue)
{
    global $dbprefix,$connect;
    $tablename=$dbprefix.'question_attributes';
    $aInsertArray=array('qid'=>$qid,
                        'attribute'=>$sAttributeName,
                        'value'=>$sAttributeValue);
    $sQuery=$connect->GetInsertSQL($tablename,$aInsertArray);
    $connect->Execute('delete from '.db_table_name('question_attributes')." where qid={$qid} and attribute=".db_quoteall($sAttributeName));
    $connect->Execute($sQuery);
}

/**
* Returns the default email template texts as array
* 
* @param mixed $oLanguage Required language translationb object
* @param string $mode Escape mode for the translation function
* @return array
*/
function aTemplateDefaultTexts($oLanguage, $mode='html'){
    return array(
      'admin_detailed_notification_subject'=>$oLanguage->gT("Response submission for survey {SURVEYNAME} with results",$mode),
      'admin_detailed_notification'=>$oLanguage->gT("Hello,\n\nA new response was submitted for your survey '{SURVEYNAME}'.\n\nClick the following link to reload the survey:\n{RELOADURL}\n\nClick the following link to see the individual response:\n{VIEWRESPONSEURL}\n\nClick the following link to edit the individual response:\n{EDITRESPONSEURL}\n\nView statistics by clicking here:\n{STATISTICSURL}\n\n\nThe following answers were given by the participant:\n{ANSWERTABLE}",$mode),
      'admin_detailed_notification_css'=>'<style type="text/css">
                                                .printouttable {
                                                  margin:1em auto;
                                                }
                                                .printouttable th {
                                                  text-align: center;
                                                }
                                                .printouttable td {
                                                  border-color: #ddf #ddf #ddf #ddf;
                                                  border-style: solid;
                                                  border-width: 1px;
                                                  padding:0.1em 1em 0.1em 0.5em;
                                                }

                                                .printouttable td:first-child {
                                                  font-weight: 700;
                                                  text-align: right;
                                                  padding-right: 5px;
                                                  padding-left: 5px;

                                                }
                                                .printouttable .printanswersquestion td{
                                                  background-color:#F7F8FF;
                                                }

                                                .printouttable .printanswersquestionhead td{
                                                  text-align: left;
                                                  background-color:#ddf;
                                                }

                                                .printouttable .printanswersgroup td{
                                                  text-align: center;        
                                                  font-weight:bold;
                                                  padding-top:1em;
                                                }
                                                </style>',
      'admin_notification_subject'=>$oLanguage->gT("Response submission for survey {SURVEYNAME}",$mode),
      'admin_notification'=>$oLanguage->gT("Hello,\n\nA new response was submitted for your survey '{SURVEYNAME}'.\n\nClick the following link to reload the survey:\n{RELOADURL}\n\nClick the following link to see the individual response:\n{VIEWRESPONSEURL}\n\nClick the following link to edit the individual response:\n{EDITRESPONSEURL}\n\nView statistics by clicking here:\n{STATISTICSURL}",$mode),
      'confirmation_subject'=>$oLanguage->gT("Confirmation of your participation in our survey"),
      'confirmation'=>$oLanguage->gT("Dear {FIRSTNAME},\n\nthis email is to confirm that you have completed the survey titled {SURVEYNAME} and your response has been saved. Thank you for participating.\n\nIf you have any further questions about this email, please contact {ADMINNAME} on {ADMINEMAIL}.\n\nSincerely,\n\n{ADMINNAME}",$mode),
      'invitation_subject'=>$oLanguage->gT("Invitation to participate in a survey",$mode),
      'invitation'=>$oLanguage->gT("Dear {FIRSTNAME},\n\nyou have been invited to participate in a survey.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}",$mode)."\n\n".$oLanguage->gT("If you do not want to participate in this survey and don't want to receive any more invitations please click the following link:\n{OPTOUTURL}",$mode),
      'reminder_subject'=>$oLanguage->gT("Reminder to participate in a survey",$mode),
      'reminder'=>$oLanguage->gT("Dear {FIRSTNAME},\n\nRecently we invited you to participate in a survey.\n\nWe note that you have not yet completed the survey, and wish to remind you that the survey is still available should you wish to take part.\n\nThe survey is titled:\n\"{SURVEYNAME}\"\n\n\"{SURVEYDESCRIPTION}\"\n\nTo participate, please click on the link below.\n\nSincerely,\n\n{ADMINNAME} ({ADMINEMAIL})\n\n----------------------------------------------\nClick here to do the survey:\n{SURVEYURL}",$mode)."\n\n".$oLanguage->gT("If you do not want to participate in this survey and don't want to receive any more invitations please click the following link:\n{OPTOUTURL}",$mode),
      'registration_subject'=>$oLanguage->gT("Survey registration confirmation",$mode),
      'registration'=>$oLanguage->gT("Dear {FIRSTNAME},\n\nYou, or someone using your email address, have registered to participate in an online survey titled {SURVEYNAME}.\n\nTo complete this survey, click on the following URL:\n\n{SURVEYURL}\n\nIf you have any questions about this survey, or if you did not register to participate and believe this email is in error, please contact {ADMINNAME} at {ADMINEMAIL}.",$mode)
    );
}

// Closing PHP tag intentionally left out - yes, it is okay

function doAdminHeader()
{
    echo getAdminHeader();
}

function getAdminHeader($meta=false)
{
    global $sitename, $admintheme, $rooturl, $defaultlang, $css_admin_includes, $homeurl;
    if (!isset($_SESSION['adminlang']) || $_SESSION['adminlang']=='') {$_SESSION['adminlang']=$defaultlang;}
    $strAdminHeader="<?xml version=\"1.0\"?><!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n"
    ."<html ";
	
    if (getLanguageRTL($_SESSION['adminlang']))
    {
        $strAdminHeader.=" dir=\"rtl\" ";
    }
    $strAdminHeader.=">\n<head>\n";

    if ($meta)
    {
        $strAdminHeader.=$meta;
    }
    $strAdminHeader.="<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />\n";
    $strAdminHeader.= "<script type=\"text/javascript\" src=\"{$homeurl}/scripts/tabpane/js/tabpane.js\"></script>\n"
    . "<script type=\"text/javascript\" src=\"{$rooturl}/scripts/jquery/jquery.js\"></script>\n"
    . "<script type=\"text/javascript\" src=\"{$rooturl}/scripts/jquery/jquery-ui.js\"></script>\n"
    . "<script type=\"text/javascript\" src=\"{$rooturl}/scripts/jquery/jquery.qtip.js\"></script>\n"
    . "<script type=\"text/javascript\" src=\"{$rooturl}/scripts/jquery/jquery.notify.js\"></script>\n"
    . "<script type=\"text/javascript\" src=\"{$homeurl}/scripts/admin_core.js\"></script>\n";

    if ($_SESSION['adminlang']!='en')
    {
        $strAdminHeader.= "<script type=\"text/javascript\" src=\"{$rooturl}/scripts/jquery/locale/jquery.ui.datepicker-{$_SESSION['adminlang']}.js\"></script>\n";
    }

    $strAdminHeader.= "<title>$sitename</title>\n";

    $strAdminHeader.= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$homeurl}//styles/$admintheme/tab.webfx.css \" />\n"
    . "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$rooturl}/scripts/jquery/css/start/jquery-ui.css\" />\n"
    . "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$homeurl}/styles/$admintheme/printablestyle.css\" media=\"print\" />\n"
    . "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$homeurl}/styles/$admintheme/adminstyle.css\" />\n"
    . "<link rel=\"shortcut icon\" href=\"{$homeurl}/favicon.ico\" type=\"image/x-icon\" />\n"
    . "<link rel=\"icon\" href=\"{$homeurl}/favicon.ico\" type=\"image/x-icon\" />\n";

    if (getLanguageRTL($_SESSION['adminlang']))
    {
        $strAdminHeader.="<link rel=\"stylesheet\" type=\"text/css\" href=\"styles/$admintheme/adminstyle-rtl.css\" />\n";
    }

    $css_admin_includes = array_unique($css_admin_includes);

    foreach ($css_admin_includes as $cssinclude)
    {
        $strAdminHeader .= "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"$cssinclude\" />\n";
    }
    $strAdminHeader.= use_firebug()
    . "</head>\n<body>\n";
    if (isset($_SESSION['dateformat']))
    {
        $formatdata=getDateFormatData($_SESSION['dateformat']);
        $strAdminHeader .= "<script type='text/javascript'>
                               var userdateformat='".$formatdata['jsdate']."';
                               var userlanguage='".$_SESSION['adminlang']."';
                           </script>";
    }
    // Prepare flashmessage
    if (isset($_SESSION['flashmessage']) && $_SESSION['flashmessage']!='')
    {
        $strAdminHeader .='<div id="flashmessage" style="display:none;">
         
                <div id="themeroller" class="ui-state-highlight ui-corner-all">
                    <!-- close link -->
                    <a class="ui-notify-close" href="#">
                        <span class="ui-icon ui-icon-close" style="float:right"></span>
                    </a>
             
                    <!-- alert icon -->
                    <span style="float:left; margin:2px 5px 0 0;" class="ui-icon ui-icon-info"></span>
             
                    <h1></h1>
                    <p>'.$_SESSION['flashmessage'].'</p>
                </div>
             
                <!-- other templates here, maybe.. -->
            </div>';
        unset($_SESSION['flashmessage']);
    }
                 
    // Standard header
    $strAdminHeader .="<div class='maintitle'>{$sitename}</div>\n";
    return $strAdminHeader;
}
