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
* $Id: upgrade-mssql.php 7122 2009-06-16 13:42:29Z c_schmitz $
*/

// There will be a file for each database (accordingly named to the dbADO scheme)
// where based on the current database version the database is upgraded
// For this there will be a settings table which holds the last time the database was upgraded

function db_upgrade($oldversion) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality
global $modifyoutput, $dbprefix;
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
        modify_database("","update [prefix_settings_global] set [stg_value]='111' where stg_name='DBVersion'"); echo $modifyoutput;

    }

    if ($oldversion < 112) {
        //The size of the users_name field is now 64 char (20 char before version 112)
        modify_database("","ALTER TABLE [prefix_users] ALTER COLUMN [users_name] VARCHAR( 64 ) NOT NULL"); echo $modifyoutput; flush();
        modify_database("","update [prefix_settings_global] set [stg_value]='112' where stg_name='DBVersion'"); echo $modifyoutput; flush();
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
	    modify_database("","UPDATE [prefix_conditions] SET [method]='==' where ( [method] is null) or [method]='' or [method]='0'"); echo $modifyoutput; flush();
        
        modify_database("","update [prefix_settings_global] set [stg_value]='126' where stg_name='DBVersion'"); echo $modifyoutput; flush();
    }
    
	if ($oldversion < 127) {
        modify_database("","create index [answers_idx2] on [prefix_answers] ([sortorder])"); echo $modifyoutput; 
        modify_database("","create index [assessments_idx2] on [prefix_assessments] ([sid])"); echo $modifyoutput; 
        modify_database("","create index [assessments_idx3] on [prefix_assessments] ([gid])"); echo $modifyoutput; 
        modify_database("","create index [conditions_idx2] on [prefix_conditions] ([qid])"); echo $modifyoutput; 
        modify_database("","create index [conditions_idx3] on [prefix_conditions] ([cqid])"); echo $modifyoutput; 
        modify_database("","create index [groups_idx2] on [prefix_groups] ([sid])"); echo $modifyoutput; 
        modify_database("","create index [question_attributes_idx2] on [prefix_question_attributes] ([qid])"); echo $modifyoutput; 
        modify_database("","create index [questions_idx2] on [prefix_questions] ([sid])"); echo $modifyoutput; 
        modify_database("","create index [questions_idx3] on [prefix_questions] ([gid])"); echo $modifyoutput; 
        modify_database("","create index [questions_idx4] on [prefix_questions] ([type])"); echo $modifyoutput; 
        modify_database("","create index [quota_idx2] on [prefix_quota] ([sid])"); echo $modifyoutput; 
        modify_database("","create index [saved_control_idx2] on [prefix_saved_control] ([sid])"); echo $modifyoutput; 
        modify_database("","create index [user_in_groups_idx1] on [prefix_user_in_groups] ([ugid], [uid])"); echo $modifyoutput; 
        modify_database("","update [prefix_settings_global] set [stg_value]='127' where stg_name='DBVersion'"); echo $modifyoutput; flush();
}

	if ($oldversion < 128) {
		upgrade_token_tables128();
        modify_database("","update [prefix_settings_global] set [stg_value]='128' where stg_name='DBVersion'"); echo $modifyoutput; flush();
	}
	
	if ($oldversion < 129) {
		//128
        modify_database("","ALTER TABLE [prefix_surveys] ADD [startdate] DATETIME"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_surveys] ADD [usestartdate] char(1) NOT NULL default 'N'"); echo $modifyoutput; flush();
        modify_database("","update [prefix_settings_global] set [stg_value]='129' where stg_name='DBVersion'"); echo $modifyoutput; flush();
	}
	if ($oldversion < 130)
	{
		modify_database("","ALTER TABLE [prefix_conditions] ADD [scenario] int NOT NULL DEFAULT '1'"); echo $modifyoutput; flush();
		modify_database("","UPDATE [prefix_conditions] SET [scenario]=1 where ( [scenario] is null) or [scenario]='' or [scenario]=0"); echo $modifyoutput; flush();
        modify_database("","update [prefix_settings_global] set [stg_value]='130' where stg_name='DBVersion'"); echo $modifyoutput; flush();
	}
    if ($oldversion < 131)
    {
        modify_database("","ALTER TABLE [prefix_surveys] ADD [publicstatistics] char(1) NOT NULL default 'N'"); echo $modifyoutput; flush();
        modify_database("","update [prefix_settings_global] set [stg_value]='131' where stg_name='DBVersion'"); echo $modifyoutput; flush();
    }    
    if ($oldversion < 132)
    {
        modify_database("","ALTER TABLE [prefix_surveys] ADD [publicgraphs] char(1) NOT NULL default 'N'"); echo $modifyoutput; flush();
        modify_database("","update [prefix_settings_global] set [stg_value]='132' where stg_name='DBVersion'"); echo $modifyoutput; flush();
    }    
    
    if ($oldversion < 133)
	{
        modify_database("","ALTER TABLE [prefix_users] ADD [one_time_pw] text"); echo $modifyoutput; flush();
        // Add new assessment setting
        modify_database("","ALTER TABLE [prefix_surveys] ADD [assessments] char(1) NOT NULL default 'N'"); echo $modifyoutput; flush();
        // add new assessment value fields to answers & labels
        modify_database("","ALTER TABLE [prefix_answers] ADD [assessment_value] int NOT NULL default '0'"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_labels] ADD [assessment_value] int NOT NULL default '0'"); echo $modifyoutput; flush();
        // copy any valid codes from code field to assessment field
        modify_database("","update [prefix_answers] set [assessment_value]=CAST([code] as int)");// no output here is intended
        modify_database("","update [prefix_labels] set [assessment_value]=CAST([code] as int)");// no output here is intended
        // activate assessment where assesment rules exist
        modify_database("","update [prefix_surveys] set [assessments]='Y' where [sid] in (SELECT [sid] FROM [prefix_assessments] group by [sid])"); echo $modifyoutput; flush();
        // add language field to assessment table
        modify_database("","ALTER TABLE [prefix_assessments] ADD [language] varchar(20) NOT NULL default 'en'"); echo $modifyoutput; flush();
        // update language field with default language of that particular survey
        modify_database("","update [prefix_assessments] set [language]=(select [language] from [prefix_surveys] where [sid]=[prefix_assessments].[sid])"); echo $modifyoutput; flush();
        // copy assessment link to message since from now on we will have HTML assignment messages
        modify_database("","update [prefix_assessments] set [message]=cast([message] as varchar) +'<br /><a href=\"'+[link]+'\">'+[link]+'</a>'"); echo $modifyoutput; flush();
        // drop the old link field
         modify_database("","ALTER TABLE [prefix_assessments] DROP COLUMN [link]"); echo $modifyoutput; flush();
        // change the primary index to include language
        // and fix missing translations for assessments
        upgrade_survey_tables133a();

        // Add new fields to survey language settings
        modify_database("","ALTER TABLE [prefix_surveys_languagesettings] ADD [surveyls_url] varchar(255)"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_surveys_languagesettings] ADD [surveyls_endtext] text"); echo $modifyoutput; flush();
        // copy old URL fields ot language specific entries
        modify_database("","update [prefix_surveys_languagesettings] set [surveyls_url]=(select [url] from [prefix_surveys] where [sid]=[prefix_surveys_languagesettings].[surveyls_survey_id])"); echo $modifyoutput; flush();
        // drop old URL field 
        mssql_drop_constraint('url','surveys');
        modify_database("","ALTER TABLE [prefix_surveys] DROP COLUMN [url]"); echo $modifyoutput; flush();
        
        modify_database("","update [prefix_settings_global] set [stg_value]='133' where stg_name='DBVersion'"); echo $modifyoutput; flush();
    }   
        
    if ($oldversion < 134)
    {
        // Add new assessment setting
        modify_database("","ALTER TABLE [prefix_surveys] ADD [usetokens] char(1) NOT NULL default 'N'"); echo $modifyoutput; flush();
        mssql_drop_constraint('attribute1','surveys');        
        mssql_drop_constraint('attribute2','surveys');        
        modify_database("", "ALTER TABLE [prefix_surveys] ADD [attributedescriptions] TEXT;"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_surveys] DROP COLUMN [attribute1]"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_surveys] DROP COLUMN [attribute2]"); echo $modifyoutput; flush();
        upgrade_token_tables134();
        modify_database("","update [prefix_settings_global] set [stg_value]='134' where stg_name='DBVersion'"); echo $modifyoutput; flush();
    }   
     if ($oldversion < 135)
    {
        mssql_drop_constraint('value','question_attributes');        
        modify_database("","ALTER TABLE [prefix_question_attributes] ALTER COLUMN [value] text"); echo $modifyoutput; flush();
        modify_database("","ALTER TABLE [prefix_answers] ALTER COLUMN [answer] varchar(8000)"); echo $modifyoutput; flush();
        modify_database("","update [prefix_settings_global] set [stg_value]='135' where stg_name='DBVersion'"); echo $modifyoutput; flush();        
    }
    if ($oldversion < 136) //New quota functions
    {
	    modify_database("", "ALTER TABLE[prefix_quota] ADD [autoload_url] int NOT NULL default '0'"); echo $modifyoutput; flush();
        modify_database("","CREATE TABLE [prefix_quota_languagesettings] (
  							[quotals_id] int NOT NULL IDENTITY (1,1),
							[quotals_quota_id] int,
  							[quotals_language] varchar(45) NOT NULL default 'en',
  							[quotals_name] varchar(255),
  							[quotals_message] text,
  							[quotals_url] varchar(255),
  							[quotals_urldescrip] varchar(255),
  							PRIMARY KEY ([quotals_id])
							);");echo $modifyoutput; flush(); 
        modify_database("","update [prefix_settings_global] set [stg_value]='136' where stg_name='DBVersion'"); echo $modifyoutput; flush();        
	}
    if ($oldversion < 137) //New date format specs
    {
	    modify_database("", "ALTER TABLE [prefix_surveys_languagesettings] ADD [surveyls_dateformat] int NOT NULL default '1'"); echo $modifyoutput; flush();
        modify_database("", "ALTER TABLE [prefix_users] ADD [dateformat] int NOT NULL default '1'"); echo $modifyoutput; flush();
        modify_database("", "update [prefix_surveys] set startdate=null where usestartdate='N'"); echo $modifyoutput; flush();
        modify_database("", "update [prefix_surveys] set expires=null where useexpiry='N'"); echo $modifyoutput; flush();
        mssql_drop_constraint('usestartdate','surveys');        
        mssql_drop_constraint('useexpiry','surveys');        
        modify_database("", "ALTER TABLE [prefix_surveys] DROP COLUMN usestartdate"); echo $modifyoutput; flush();
        modify_database("", "ALTER TABLE [prefix_surveys] DROP COLUMN useexpiry"); echo $modifyoutput; flush();
        modify_database("","update [prefix_settings_global] set [stg_value]='137' where stg_name='DBVersion'"); echo $modifyoutput; flush();        
	}

	if ($oldversion < 138) //Modify quota field
	{
	    modify_database("", "ALTER TABLE [prefix_quota_members] ALTER COLUMN [code] VARCHAR(11) NULL"); echo $modifyoutput; flush();
        modify_database("", "UPDATE [prefix_settings_global] SET [stg_value]='138' WHERE stg_name='DBVersion'"); echo $modifyoutput; flush();        
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
            modify_database("","ALTER TABLE ".$sv." ADD [emailstatus] VARCHAR(300) DEFAULT 'OK'"); echo $modifyoutput; flush();
            }
}


function upgrade_token_tables128()
{
  	global $connect,$modifyoutput,$dbprefix;
  	$tokentables=$connect->MetaTables('TABLES',false,$dbprefix."tokens%");
    foreach ($tokentables as $sv)
            {
            modify_database("","ALTER TABLE ".$sv." ADD [remindersent] VARCHAR(17) DEFAULT 'OK'"); echo $modifyoutput; flush();
            modify_database("","ALTER TABLE ".$sv." ADD [remindercount] int DEFAULT '0'"); echo $modifyoutput; flush();
            }
}


function upgrade_survey_tables133a()
{
    global $dbprefix, $connect, $modifyoutput;
    // find out the constraint name of the old primary key
    $pkquery = " SELECT CONSTRAINT_NAME "
              ."FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS "
              ."WHERE     (TABLE_NAME = '{$dbprefix}assessments') AND (CONSTRAINT_TYPE = 'PRIMARY KEY')";

    $primarykey=$connect->GetRow($pkquery);
    if ($primarykey!=false) 
    {
        modify_database("","ALTER TABLE [prefix_assessments] DROP CONSTRAINT {$primarykey[0]}"); echo $modifyoutput; flush();    
    }
    // add the new primary key
    modify_database("","ALTER TABLE [prefix_assessments] ADD CONSTRAINT pk_assessments_id_lang PRIMARY KEY ([id],[language])"); echo $modifyoutput; flush();    
    $surveyidquery = "SELECT sid,additional_languages FROM ".db_table_name('surveys');
    $surveyidresult = db_execute_num($surveyidquery);
    while ( $sv = $surveyidresult->FetchRow() )
    {
        FixLanguageConsistency($sv[0],$sv[1]);   
    }
}


function upgrade_token_tables134()
{
      global $connect,$modifyoutput,$dbprefix;
      $tokentables=$connect->MetaTables('TABLES',false,$dbprefix."tokens%");
    foreach ($tokentables as $sv)
            {
            modify_database("","ALTER TABLE ".$sv." ADD [validfrom] DATETIME"); echo $modifyoutput; flush();
            modify_database("","ALTER TABLE ".$sv." ADD [validuntil] DATETIME"); echo $modifyoutput; flush();
            }
}

function mssql_drop_constraint($fieldname, $tablename)
{
    global $dbprefix, $connect, $modifyoutput;
    // find out the name of the default constraint
    // Did I already mention that this is the most suckiest thing I have ever seen in MSSQL database?
    // It proves how badly designer some Microsoft software is!
    $dfquery ="SELECT c_obj.name AS constraint_name 
                FROM  sys.sysobjects AS c_obj INNER JOIN
                      sys.sysobjects AS t_obj ON c_obj.parent_obj = t_obj.id INNER JOIN
                      sys.sysconstraints AS con ON c_obj.id = con.constid INNER JOIN
                      sys.syscolumns AS col ON t_obj.id = col.id AND con.colid = col.colid
                WHERE (c_obj.xtype = 'D') AND (col.name = '$fieldname') AND (t_obj.name='$dbprefix$tablename')";
    $defaultname=$connect->GetRow($dfquery);
    if ($defaultname!=false) 
    {
        modify_database("","ALTER TABLE [prefix_$tablename] DROP CONSTRAINT {$defaultname[0]}"); echo $modifyoutput; flush();    
    }
                

}


?>
