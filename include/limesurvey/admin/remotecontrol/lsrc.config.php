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
 * $Id: lsrc.config.php 8540 2010-03-31 11:37:19Z texens $
 *
 */

### Including
// including LimeSurvey configs, for database variables and more...
// only include if this config is not used to save a survey.csv for the lsrc
if(!isset($export4lsrc))
{
    include_once("../../config-defaults.php");
    require_once(dirname(__FILE__).'/../../common.php');
}
### Error Handling
// simple debug Option
ini_set("error_reporting","E_ALL");

//specialized debug option, true for own debuglog
$lsrcDebug = true;
$lsrcDebugLog = "lsrc.log";

// error log enabled, hint(.../apache/logs/error.log) this is very handy while developing, since SOAP does not echo php error messages to the client
// it's also recommended to set this in productive environment
ini_set("log_errors", "1");


### Caching
//we don't like caching while testing, so we disable it...
//for productiv use it's recommended to set this to 1 or comment it out for webserver default
ini_set("soap.wsdl_cache_enabled", "0");


### Security
// enable for ssl connections
// this is for wsdl generation, on true the url to the server in the wsdl beginns with https instead of http
$lsrcOverSSL=false; //default: false

// enable if you use a certificate for the Connections
// IMPORTANT NOTE: your Client need the same certificate to connect with.
$useCert=false; //default: false
// path to your local certificate
$sslCert='D:\\xampp\apache\privkey.pem';
//C:\\path\myCert.pem

### Variables
// path to the wsdl definition for this server... normally it is in the same directory, so you don't need to change it.
$wsdl= $homedir."/remotecontrol/lsrc.wsdl"; //default: $homedir."/remotecontrol/lsrc.wsdl";

/**
 * These are the Dirs where the prepared survey csv's are or have to be.
 * one for the core surveys,
 * one for addable groups,
 * one for addable questions
 */
$coreDir = "./surveys/";
$modDir = "./groups/";
$queDir = "./questions/";


//seperator for Tokens in sInsertToken function
$sLsrcSeparator = ","; //default: ","

//set the Seperators for Participant Datasets in sInsertParticipants
$sDatasetSeperator = "::"; //default: "::"
$sDatafieldSeperator = ";"; //default: ";"

?>