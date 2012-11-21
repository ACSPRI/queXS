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
 * $Id: listcolumn.php 11664 2011-12-16 05:19:42Z tmswhite $
 */

include_once("login_check.php");

sendcacheheaders();


if (!isset($surveyid)) {$surveyid=returnglobal('sid');}
if (!isset($column)) {$column=returnglobal('column');}
if (!isset($order)) {$order=returnglobal('order');}
if (!isset($sql)) {$sql=$_SESSION['sql'];}

if (!$surveyid)
{
    //NOSID
    exit;
}
if (!$column)
{
    //NOCOLUMN
    exit;
}
$aColumnNames=$connect->MetaColumnNames("{$dbprefix}survey_{$surveyid}");
if (!isset($aColumnNames[strtoupper($column)])) safe_die('Invalid column name');

if ($connect->databaseType == 'odbc_mssql' || $connect->databaseType == 'odbtp' || $connect->databaseType == 'mssql_n' || $connect->databaseType == 'mssqlnative')
{ $query = "SELECT id, ".db_quote_id($column)." FROM {$dbprefix}survey_$surveyid WHERE (".db_quote_id($column)." NOT LIKE '')"; }
else
{ $query = "SELECT id, ".db_quote_id($column)." FROM {$dbprefix}survey_$surveyid WHERE (".db_quote_id($column)." != '')"; }

if ($sql && $sql != "NULL")
{
    $query .= " AND ".auto_unescape(urldecode($sql));
}

switch (incompleteAnsFilterstate()) {
    case 'inc':
        //Inclomplete answers only
        $query .= ' AND submitdate is null ';
        break;
    case 'filter':
        //Inclomplete answers only
        $query .= ' AND submitdate is not null ';
        break;
}

if ($order == "alpha")
{
    $query .= " ORDER BY ".db_quote_id($column);
}
else
{
    $query .= " ORDER BY id";
}

$result=db_execute_assoc($query) or safe_die("Error with query: ".$query."<br />".$connect->ErrorMsg());
$listcolumnoutput= "<table width='98%' class='statisticstable' border='1' cellpadding='2' cellspacing='0'>\n";
$listcolumnoutput.= "<thead><tr><th><input type='image' src='$imageurl/downarrow.png' align='middle' onclick=\"window.open('admin.php?action=listcolumn&amp;sid=$surveyid&amp;column=$column&amp;order=id', '_self')\" /></th>\n";
$listcolumnoutput.= "<th valign='top'><input type='image' align='right' src='$imageurl/close.gif' onclick='window.close()' />";
if ($connect->databaseType != 'odbc_mssql' && $connect->databaseType != 'odbtp' && $connect->databaseType != 'mssql_n' || $connect->databaseType == 'mssqlnative')
{ $listcolumnoutput.= "<input type='image' src='$imageurl/downarrow.png' align='left' onclick=\"window.open('admin.php?action=listcolumn&amp;sid=$surveyid&amp;column=$column&amp;order=alpha', '_self')\" />"; }
$listcolumnoutput.= "</th></tr>\n";
while ($row=$result->FetchRow())
{
    $listcolumnoutput.=  "<tr><td valign='top' align='center' >"
    . "<a href='$scriptname?action=browse&amp;sid=$surveyid&amp;subaction=id&amp;id=".$row['id']."' target='home'>"
    . $row['id']."</a></td>"
    . "<td valign='top'>".htmlspecialchars($row[$column])."</td></tr>\n";
}
$listcolumnoutput.= "</table>\n";


?>
