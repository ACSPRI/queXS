<?php
/**
 * Server that exposes a class for doing a fake guestbook
 *
 * @category   HTML
 * @package    AJAX
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

 // include the server class
include 'HTML/AJAX/Server.php';


// extend HTML_AJAX_Server creating our own custom one with init{ClassName} methods for each class it supports calls on
class GuestbookServer extends HTML_AJAX_Server {
	// this flag must be set to on init methods
	var $initMethods = true;

	// init method for the test class, includes needed files an registers it for ajax
	function initGuestbook() {
		include 'guestbook.class.php';
		$this->registerClass(new Guestbook(),'guestbook',array('newEntry', 'clearGuestbook', 'deleteEntry', 'editEntry', 'updateSelect')); // specify methods so that we get case in php4
	}
}

session_start();
// create an instance of our test server
$server = new GuestbookServer();

// handle requests as needed
$server->handleRequest();
?>
