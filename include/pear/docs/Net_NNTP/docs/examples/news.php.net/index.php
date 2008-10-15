<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Alexander Merz <alexmerz@php.net>                           |
// |          Heino H. Gehlsen <heino@gehlsen.dk>                         |
// +----------------------------------------------------------------------+
//
// $Id: index.php,v 1.2.2.1 2005/01/30 15:44:44 heino Exp $
?>
<html>
<head>
    <title>NNTP news.php.net</title>
</head>
<body>
<?php
require_once "Net/NNTP/Client.php";

$nntp = new Net_NNTP_Client();

$ret = $nntp->connect("news.php.net");
if( PEAR::isError($ret)) {
 echo '<font color="red">No connection to newsserver!</font><br>' ;
 echo $ret->getMessage();
} else {
    echo "<h1>Avaible groups</h1>";    
    $groups = $nntp->getGroups();
    $descriptions = $nntp->getDescriptions();
    foreach($groups as $group) {
        echo '<a href="group.php?group='.urlencode($group['group']).
            '&writable='.urlencode($group['posting']).'">'.
            $group['group'].'</a>' ;
        $msgcount = $group['last']-$group['first']; 
        echo '&nbsp;('.$msgcount.' messages)<br>';
        echo $descriptions[$group['group']].'<br><br>';
    }
    $nntp->quit();
}    
?>
</body>
</html>