<?php


if($casEnabled==true)
{
    include_once("login_check_cas.php");
}
else
{
    include_once('login_check.php');
}
