<?PHP
/*
* LimeSurvey
* Copyright (C) 2007 The LimeSurvey Project Team / Carsten Schmitz
* All rights reserved.
* License: http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* LimeSurvey is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*
* $Id: upgrade-odbc_mssql.php 4453 2008-03-13 22:41:44Z c_schmitz $
*/

// There will be a file for each database (accordingly named to the dbADO scheme)
// where based on the current database version the database is upgraded
// For this there will be a settings table which holds the last time the database was upgraded

function db_upgrade($oldversion) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality
global $modifyoutput;
echo str_pad('Loading... ',4096)."<br />\n";
    if ($oldversion < 111) {
      // Language upgrades from version 110 to 111 since the language names did change

       $oldnewlanguages=array('german_informal'=>'german-informal',
                              'cns'=>'cn-Hans',
                              'cnt'=>'cn-Hant',
                              'pt_br'=>'pt-BR',
                              'gr'=>'el',
                              'jp'=>'ja',
                              'si'=>'sl',
                              'se'=>'sv',
                              'vn'=>'vi');

        foreach  ($oldnewlanguages as $oldlang=>$newlang)
        {
            modify_database("","update [prefix_answers] set [language`='$newlang' where language='$oldlang'");  echo $modifyoutput;      flush();
            modify_database("","update [prefix_questions] set [language`='$newlang' where language='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_groups] set [language`='$newlang' where language='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_labels] set [language`='$newlang' where language='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_surveys] set [language`='$newlang' where language='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_surveys_languagesettings] set [surveyls_language`='$newlang' where surveyls_language='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_users] set [lang`='$newlang' where lang='$oldlang'");echo $modifyoutput;flush();
        }



        $resultdata=db_execute_assoc("select * from ".db_table_name("labelsets"));
        while ($datarow = $resultdata->FetchRow()){
           $toreplace=$datarow['languages'];
           $toreplace=str_replace('german_informal','german-informal',$toreplace);
           $toreplace=str_replace('cns','cn-Hans',$toreplace);
           $toreplace=str_replace('cnt','cn-Hant',$toreplace);
           $toreplace=str_replace('pt_br','pt-BR',$toreplace);
           $toreplace=str_replace('gr','el',$toreplace);
           $toreplace=str_replace('jp','ja',$toreplace);
           $toreplace=str_replace('si','sl',$toreplace);
           $toreplace=str_replace('se','sv',$toreplace);
           $toreplace=str_replace('vn','vi',$toreplace);
           modify_database("","update [prefix_labelsets] set [languages`='$toreplace' where lid=".$datarow['lid']);echo $modifyoutput;flush();
        }


        $resultdata=db_execute_assoc("select * from ".db_table_name("surveys"));                 
        while ($datarow = $resultdata->FetchRow()){
           $toreplace=$datarow['additional_languages'];
           $toreplace=str_replace('german_informal','german-informal',$toreplace);
           $toreplace=str_replace('cns','cn-Hans',$toreplace);
           $toreplace=str_replace('cnt','cn-Hant',$toreplace);
           $toreplace=str_replace('pt_br','pt-BR',$toreplace);
           $toreplace=str_replace('gr','el',$toreplace);
           $toreplace=str_replace('jp','ja',$toreplace);
           $toreplace=str_replace('si','sl',$toreplace);
           $toreplace=str_replace('se','sv',$toreplace);
           $toreplace=str_replace('vn','vi',$toreplace);
           modify_database("","update [prefix_surveys] set [additional_languages`='$toreplace' where sid=".$datarow['sid']);echo $modifyoutput;flush();
        }
        modify_database("","update [prefix_settings_global] set [stg_value`='111' where stg_name='DBVersion'"); echo $modifyoutput;

    }

    if ($oldversion < 112) {
        //The size of the users_name field is now 64 char (20 char before version 112)
        modify_database("","ALTER TABLE [prefix_users] ALTER COLUMN [users_name] VARCHAR( 64 ) NOT NULL"); echo $modifyoutput; flush();
        modify_database("","update [prefix_settings_global] set [stg_value`='112' where stg_name='DBVersion'"); echo $modifyoutput; flush();
    }
    
    if ($oldversion < 113) {
	//No action needed
        modify_database("","update [prefix_settings_global] set [stg_value]='113' where stg_name='DBVersion'"); echo $modifyoutput; flush();
    }
    
    if ($oldversion < 114) {
        modify_database("","ALTER TABLE [prefix_saved_control] ALTER COLUMN [email] VARCHAR(320) NOT NULL"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_surveys] ALTER COLUMN [adminemail] VARCHAR(320) NOT NULL"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_users] ALTER COLUMN [email] VARCHAR(320) NOT NULL"); echo $modifyoutput; flush();
        modify_database("",'INSERT INTO [prefix_settings_global] VALUES (\'SessionName\', \'$sessionname\');');echo $modifyoutput; flush();
        modify_database("","update [prefix_settings_global] set [stg_value]='114' where stg_name='DBVersion'"); echo $modifyoutput; flush();
    }
    
    if ($oldversion < 126) {
        modify_database("","ALTER TABLE [prefix_surveys] ADD  [printanswers] CHAR(1) DEFAULT 'N'"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_surveys] ADD  [listpublic] CHAR(1) DEFAULT 'N'"); echo $modifyoutput; flush();
        upgrade_survey_tables117();
        upgrade_survey_tables118();
        //119
        modify_database("","CREATE TABLE [prefix_quota] (
						  [id] int NOT NULL IDENTITY (1,1),
						  [sid] int,
						  [name] varchar(255) ,
						  [qlimit] int ,
						  [action] int ,
						  [active] int NOT NULL default '1',
						  PRIMARY KEY  ([id])
						);");echo $modifyoutput; flush();
        modify_database("","CREATE TABLE [prefix_quota_members] (
						  [id] int NOT NULL IDENTITY (1,1),
						  [sid] int ,
						  [qid] int ,
						  [quota_id] int ,
						  [code] varchar(5) ,
						  PRIMARY KEY  ([id])
						);");echo $modifyoutput; flush();
						
       // Rename Norwegian language code from NO to NB
       $oldnewlanguages=array('no'=>'nb');
        foreach  ($oldnewlanguages as $oldlang=>$newlang)
        {
            modify_database("","update [prefix_answers] set [language]='$newlang' where [language]='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_questions] set [language]='$newlang' where [language]='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_groups] set [language]='$newlang' where [language]='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_labels] set [language]='$newlang' where [language]='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_surveys] set [language]='$newlang' where [language]='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_surveys_languagesettings] set [surveyls_language]='$newlang' where surveyls_language='$oldlang'");echo $modifyoutput;flush();
            modify_database("","update [prefix_users] set [lang]='$newlang' where lang='$oldlang'");echo $modifyoutput;flush();
        }

        $resultdata=db_execute_assoc("select * from ".db_table_name("labelsets"));
        while ($datarow = $resultdata->FetchRow()){
           $toreplace=$datarow['languages'];
           $toreplace2=str_replace('no','nb',$toreplace);
           if ($toreplace2!=$toreplace) {modify_database("","update  [prefix_labelsets] set [languages]='$toreplace' where lid=".$datarow['lid']);echo $modifyoutput;flush();}
        }

        $resultdata=db_execute_assoc("select * from ".db_table_name("surveys"));                 
        while ($datarow = $resultdata->FetchRow()){
           $toreplace=$datarow['additional_languages'];
           $toreplace2=str_replace('no','nb',$toreplace);
           if ($toreplace2!=$toreplace) {modify_database("","update [prefix_surveys] set [additional_languages]='$toreplace' where sid=".$datarow['sid']);echo $modifyoutput;flush();}
        }	
        
        modify_database("","ALTER TABLE [prefix_surveys] ADD [htmlemail] CHAR(1) DEFAULT 'N'"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_surveys] ADD [usecaptcha] CHAR(1) DEFAULT 'N'"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_surveys] ADD [tokenanswerspersistence] CHAR(1) DEFAULT 'N'"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_users] ADD [htmleditormode] CHAR(7) DEFAULT 'default'"); echo $modifyoutput; flush();
        modify_database("","CREATE TABLE [prefix_templates_rights] (
						  [uid] int NOT NULL,
						  [folder] varchar(255) NOT NULL,
						  [use] int NOT NULL,
						  PRIMARY KEY  ([uid],[folder])
						  );");echo $modifyoutput; flush();
        modify_database("","CREATE TABLE [prefix_templates] (
						  [folder] varchar(255) NOT NULL,
						  [creator] int NOT NULL,
						  PRIMARY KEY  ([folder])
						  );");echo $modifyoutput; flush();        
	    //123
        modify_database("","ALTER TABLE [prefix_conditions] ALTER COLUMN [value] VARCHAR(255)"); echo $modifyoutput; flush();
        // There is no other way to remove the previous default value
        /*modify_database("","DECLARE @STR VARCHAR(100)
									SET @STR = (
									SELECT NAME
									FROM SYSOBJECTS SO
									JOIN SYSCONSTRAINTS SC ON SO.ID = SC.CONSTID
									WHERE OBJECT_NAME(SO.PARENT_OBJ) = 'lime_labels'
									AND SO.XTYPE = 'D' AND SC.COLID =
									(SELECT COLID FROM SYSCOLUMNS WHERE ID = OBJECT_ID('lime_labels') AND NAME = 'title'))
									SET @STR = 'ALTER TABLE lime_labels DROP CONSTRAINT ' + @STR 
	 								exec (@STR);"); echo $modifyoutput; flush();     */
	 								
        modify_database("","ALTER TABLE [prefix_labels] ALTER COLUMN [title] varchar(4000)"); echo $modifyoutput; flush();
        //124
        modify_database("","ALTER TABLE [prefix_surveys] ADD [bounce_email] text"); echo $modifyoutput; flush();
        //125
        upgrade_token_tables125();
        modify_database("","EXEC sp_rename 'prefix_users.move_user','superadmin'"); echo $modifyoutput; flush();
        modify_database("","UPDATE [prefix_users] SET [superadmin]=1 where ([create_survey]=1 AND [create_user]=1 AND [delete_user]=1 AND [configurator]=1)"); echo $modifyoutput; flush();
        //126
        modify_database("","ALTER TABLE [prefix_questions] ADD [lid1] int NOT NULL DEFAULT '0'"); echo $modifyoutput; flush();
        modify_database("","UPDATE [prefix_conditions] SET [method]='==' where ( [method] is null) or [method]=''"); echo $modifyoutput; flush();
        
        modify_database("","update [prefix_settings_global] set [stg_value]='126' where stg_name='DBVersion'"); echo $modifyoutput; flush();
    }
    
    return true;
}



function upgrade_survey_tables117()
{
    global $modifyoutput;
    $surveyidquery = "SELECT sid FROM ".db_table_name('surveys')." WHERE active='Y' and datestamp='Y'";
    $surveyidresult = db_execute_num($surveyidquery);
    if (!$surveyidresult) {return "Database Error";}
    else
        {
        while ( $sv = $surveyidresult->FetchRow() )
            {
            modify_database("","ALTER TABLE ".db_table_name('survey_'.$sv[0])." ADD [startdate] datetime"); echo $modifyoutput; flush();
            }
        }
}


function upgrade_survey_tables118()
{
  	global $connect,$modifyoutput,$dbprefix;
  	$tokentables=$connect->MetaTables('TABLES',false,$dbprefix."tokens%");
    foreach ($tokentables as $sv)
            {
            modify_database("","ALTER TABLE ".$sv." ALTER COLUMN [token] VARCHAR(36)"); echo $modifyoutput; flush();
            }
}


function upgrade_token_tables125()
{
  	global $connect,$modifyoutput,$dbprefix;
  	$tokentables=$connect->MetaTables('TABLES',false,$dbprefix."tokens%");
    foreach ($tokentables as $sv)
            {
            modify_database("","ALTER TABLE ".$sv." ADD COLUMN [emailstatus ] VARCHAR(300) DEFAULT 'OK'"); echo $modifyoutput; flush();
            }
}

?>
