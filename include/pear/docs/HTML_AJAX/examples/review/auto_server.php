<?php
/**
 * Server that exposes a class for doing a fake review
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
class ReviewServer extends HTML_AJAX_Server {
	// this flag must be set to on init methods
	var $initMethods = true;

	// init method for the test class, includes needed files an registers it for ajax
	function initReview() {
		include 'review.class.php';
		$this->registerClass(new review(),'Review',array('newReview','updateReview')); // specify methods so that we get case in php4
	}
}

session_start();
// create an instance of our test server
$server = new ReviewServer();

// handle requests as needed
$server->handleRequest();
?>
