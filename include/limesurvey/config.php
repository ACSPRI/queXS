<?php
/*
 * LimeSurvey
 * Copyright (C) 2007 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or is
 * derivative of works licensed under the GNU General Public License or other
 * free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 * $Id$
 */

/* IMPORTANT NOTICE
 *  With LimeSurvey v1.70+ the configuration of LimeSurvey was simplified,
 *  Now config.php only contains the basic required settings.
 *  Some optional settings are also set by default in config-defaults.php.
 *  If you want to change an optional parameter, DON'T change values in config-defaults.php!!!
 *  Just copy the parameter into your config.php-file and adjust the value!
 *  All settings in config.php overwrite the default values from config-defaults.php
 */

 
/**
 * queXS Configuration file - so you do not have to configure this file manually
 */
require_once(dirname(__FILE__).'/../../config.inc.php');


// Basic Setup

$databasetype       =   'mysqli';       // ADOdb database driver - valid values are mysql, mysqli, odbc_mssql, mssql_n, odbtp or postgres
                                       // mysql: Recommended driver for mysql
                                       // mysqli: Slightly faster driver for mysql - not on all server systems available
                                       // odbc_mssql: MSSQL driver using ODBC with MS SQL Server
                                       // mssqlnative: Native SQL Server driver for SQL Server 2005+
                                       // mssql_n: Experimental driver for MS SQL Server which handles UTF-8 charsets
                                       // odbtp: ODBTP driver to access MSSQL-Server is needed for this one - we also recommend to activate $databasepersistent for decent speed
                                       // postgres: Standard postgres driver

$databaselocation   =   LDB_HOST;   // Network location of your Database - for odbc_mssql or mssqlnative use the mssql servername, not localhost or IP
$databasename       =   LDB_NAME;  // The name of the database that we will create
$databaseuser       =   LDB_USER;        // The name of a user with rights to create db (or if db already exists, then rights within that db)
$databasepass       =   LDB_PASS;            // Password of db user
$dbprefix           =   LIME_PREFIX;       // A global prefix that can be added to all LimeSurvey tables. Use this if you are sharing
// a database with other applications. Suggested prefix is 'lime_'

// File Locations
$rooturl            =   substr(LIME_URL,0,-1); // The root web url for your limesurvey installation (without a trailing slash).
// The double quotes (") are important.

$rootdir            =   dirname(__FILE__); // This is the physical disk location for your limesurvey installation. Normally you don't have to touch this
// setting. If you use IIS then you MUST enter the complete rootdir e.g. : $rootDir='C:\Inetpub\wwwroot\limesurvey'!
// Some IIS and OS/2 installations also require to use forward slashes
// instead of backslashes, e.g.  $rootDir='C:/Inetpub/wwwroot/limesurvey'!

// Installation Setup
$defaultuser        =   'admin';           // This is the username when LimeSurvey is installed and the administration user is created on installation
$defaultpass        =   'password';        // This is the password for the administration user when LimeSurvey is installed

// Debug Settings
$debug              =   0;                 // Set this to 1 if you are looking for errors. If you still get no errors after enabling this
                                           // then please check your error-logs - either in your hosting provider admin panel or in some /logs dir
                                           // on your webspace.
                                           // LimeSurvey developers: Set this to 3 to circumvent the restriction to remove the installation directory and full access to standard templates
                                           // or to change the password. If you set it to 3 then PHP STRICT warnings will be shown additionally.

$defaultlang = isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2) : DEFAULT_LOCALE;

$defaulttemplate = "quexs";
$siteadminemail = "quexs@acspri.org.au";
//$useWebserverAuth = true;
//$WebserverAuth_autocreateUser = true;
//$WebserverAuth_autouserprofile = Array(
//				'full_name' => 'autouser',
//				'email' => $siteadminemail,
//				'htmledtirmode' => $defaulthtmleditormode,
//				'templatelist' => 'default,basic',
//				'create_survey' => 1,
//				'lang' => DEFAULT_LOCALE,
//				'create_user' => 1,
//				'delete_user' => 1,
//				'superadmin' => 1,
//				'configurator' => 1,
//				'manage_template' => 1,
//				'manage_label' => 1);
//

