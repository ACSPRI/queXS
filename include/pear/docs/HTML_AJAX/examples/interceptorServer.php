<?php
/**
 * HTML_AJAX_Server with a register itnerceptor class
 *
 * The server responds to ajax calls and also serves the js client libraries, so they can be used directly from the PEAR data dir
 * 304 not modified headers are used when server client libraries so they will be cached on the browser reducing overhead
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2007 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

 // include the server class
include 'HTML/AJAX/Server.php';

// include the test class will be registering
	include 'support/test.class.php';
	include 'support/test2.class.php';
	include 'support/interceptor.php';

// create our new server
$server = new HTML_AJAX_Server();

// register an instance of the class were registering
$test = new test();
$server->registerClass($test,'test');

$test2 = new test2();
$server->registerClass($test2,'test2');

$server->ajax->packJavaScript = true;

$server->ajax->setInterceptor(new Interceptor());

$server->handleRequest();
?>
