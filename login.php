<?php

include_once("config.inc.php");

$usresult = LS_SESSION_NAME; //queXS Addition
@session_name($usresult);

if (session_id() == "")
{
    session_set_cookie_params(0,QUEXS_PATH);
    session_start();
}



if(CAS_ENABLED==true)
{
  include_once("login_check_cas.php");
}
else
{
  include_once('login_check.php');
}



if(!isset($_SESSION['loginID']))
{ //not logged in
  
    $adminoutput = <<<EOD
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">

    <title>queXS Authentication</title>

    <!-- Bootstrap core CSS -->
    <link href="include/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/signin.css" rel="stylesheet">
  </head>

  <body>

    <div class="container">
EOD;
    
      $adminoutput .= $loginsummary;
      $adminoutput .= "</div></body></html>";
      echo $adminoutput;

      die();
}


