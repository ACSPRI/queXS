<?php
/**
 * A server that adds artificial latency to requests
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */
session_start();

 // include the server class
include 'HTML/AJAX/Server.php';

// don't add delays to client library requests
if (!isset($_GET['client']) && !isset($_GET['stub'])) {
	if (!isset($_SESSION['sleep']) || $_SESSION['sleep'] == 0) {
		$_SESSION['sleep'] = 5;
	}
	sleep($_SESSION['sleep']--);
}

// extend HTML_AJAX_Server creating our own custom one with init{ClassName} methods for each class it supports calls on
class TestServer extends HTML_AJAX_Server {
	// this flag must be set to on init methods
	var $initMethods = true;

	// init method for the test class, includes needed files an registers it for ajax
	function initTest() {
		include 'support/test.class.php';
		$this->registerClass(new test());
	}

	// init method for the livesearch class, includes needed files an registers it for ajax
	function initLivesearch() {
		include 'support/livesearch.class.php';
		$this->registerClass(new livesearch());
	}

	// init method for the testHaa class, includes needed files an registers it for ajax, directly passes in methods to register to specify case in php4
	function initTestHaa() {
		include 'support/testHaa.class.php';
		$this->registerClass(new testHaa(),'testHaa',array('updateClassName'));
	}
	
	
}

$server = new TestServer();

// handle requests as needed
$server->handleRequest();
?>
