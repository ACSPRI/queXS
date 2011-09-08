<?php
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
 * $Id: index.php 10268 2011-06-15 09:17:29Z c_schmitz $
 */


if (isset($_REQUEST['rootdir'])) {die('You cannot start this script directly');}
$action='';
require_once(dirname(__FILE__).'/../../config-defaults.php');
require_once(dirname(__FILE__).'/../../common.php');
require_once(dirname(__FILE__).'/../admin_functions.php');
require_once(dirname(__FILE__).'/../update/updater.php');
// SET THE LANGUAGE???? -> DEFAULT SET TO EN FOR NOW
require_once($rootdir.'/classes/core/language.php');
$clang = new limesurvey_lang("en");
$adminoutput=getAdminHeader();  // Alle future output is written into this and then outputted at the end of file
ob_implicit_flush(true);
sendcacheheaders();

if (!$database_exists)
{
    $adminoutput.= "<br />\n"
    ."<table width='350' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n"
    ."\t<tr bgcolor='#555555'><td colspan='2' height='4'><font size='1' face='verdana' color='white'><strong>"
    .$clang->gT("LimeSurvey Setup")."</strong></td></tr>\n"
    ."\t<tr bgcolor='#CCCCCC'><td align='center'>\n"
    ."<strong>".$clang->gT("Welcome to LimeSurvey Setup!")."</strong><br /><br />\n"
    .$clang->gT("The database defined in config.php does not exist.")."<br />\n"
    .$clang->gT("LimeSurvey can attempt to create this database for you.")."<br /><br />\n"
    .$clang->gT("Your selected database name is:")."<strong> $databasename</strong><br />\n"
    ."<br /><input type='submit' value='"
    .$clang->gT("Create Database")."' onclick='location.href=\"createdb.php\"' /></center>\n"
    ."</td></tr></table>\n"
    ."</body>\n</html>\n";
}
elseif ($dbexistsbutempty && !(returnglobal('createdbstep2')==$clang->gT("Populate Database")))
{
    $connect->database = $databasename;
    $connect->Execute("USE DATABASE `$databasename`");
    $adminoutput.= "<div class='messagebox ui-corner-all'><div class='header ui-widget-header' >"
    .$clang->gT("LimeSurvey Setup")."</div>\n";
    $adminoutput.= "<br /><div class='successtitle'>\n";
    $adminoutput.= sprintf($clang->gT('A database named "%s" already exists.'),$databasename)."</div><p>\n";
    $adminoutput.= $clang->gT("Do you want to populate that database now by creating the necessary tables?")."<br /><br />\n";
    $adminoutput.= "<form method='post' action='createdb.php'>";
    $adminoutput.= "<input type='submit' name='createdbstep2' value='".$clang->gT("Populate Database")."'></form></div>";
}
else
{
    //DB EXISTS, CHECK FOR APPROPRIATE UPGRADES
    $connect->database = $databasename;
    $connect->Execute("USE DATABASE `$databasename`");
    ob_start();
    CheckForDBUpgrades();
    $output=ob_get_clean();
    if ($output== '') {$adminoutput.='<br />LimeSurvey Database is up to date. No action needed';}
    else {$adminoutput.=$output;}
    $adminoutput.="<br />Please <a href='$homeurl/$scriptname'>log in.</a>";

}
echo $adminoutput;




?>
