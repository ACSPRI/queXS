<?php

include 'HTML/AJAX/Server.php';
include 'support/xml.class.php';

$server = new HTML_AJAX_Server();
// register an instance of the class were registering
$xml =& new TestXml();
$server->registerClass($xml,'TestXml',array('createHealthy','createJunk','writeDoc'));
$server->setSerializer('XML');
$server->handleRequest();
?>
