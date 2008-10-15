<?php
/**
 * Advanced usage of HTML_AJAX_Server
 * Allows for a single server to manage exporting a large number of classes without high overhead per call
 * Also gives a single place to handle setup tasks especially useful if session setup is required
 *
 * The server responds to ajax calls and also serves the js client libraries, so they can be used directly from the PEAR data dir
 * 304 not modified headers are used when server client libraries so they will be cached on the browser reducing overhead
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

 // include the server class
include 'HTML/AJAX/Server.php';


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
		$this->registerClass(new testHaa(),'testHaa',array('updateClassName','greenText','highlight','duplicate'));
	}
	
	
}

// create an instance of our test server
$server = new TestServer();

// init methods can also be added to the server by registering init objects, this is useful in cases where you want to dynamically add init methods
class initObject {
	// init method for the test class, includes needed files an registers it for ajax
	function initTest2() {
		include 'support/test2.class.php';
		$this->server->registerClass(new test2());
	}
}
$init = new initObject();
$server->registerInitObject($init);

// you can use HTML_AJAX_Server to deliver your own custom javascript libs, when used with comma seperated client lists you can
// use just one javascript include for all your library files 
// example url: auto_server.php?client=auto_server.php?client=Util,Main,Request,HttpClient,Dispatcher,Behavior,customLib
$server->registerJSLibrary('customLib','customLib.js','./support/');

// handle requests as needed
$server->handleRequest();
?>
