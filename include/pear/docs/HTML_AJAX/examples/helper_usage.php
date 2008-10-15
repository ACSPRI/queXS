<?php
/**
 * Example of Using HTML_AJAX_Helper
 *
 * HTML_AJAX_Helper takes care of basic JavaScript and HTML generation that is needed in many AJAX requests
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

// include the helper class
require_once 'HTML/AJAX/Helper.php';

// create an instance and set the server url
$ajaxHelper = new HTML_AJAX_Helper();
$ajaxHelper->serverUrl = 'auto_server.php';
$ajaxHelper->jsLibraries[] = 'customLib';
?>
<html>
<head>

<?php
    // output a javascript neded to setup HTML_AJAX
    // by default this is all the libraries shipped with HTML_AJAX, take a look at $ajaxHelper->jsLibraries to edit the list
    echo $ajaxHelper->setupAJAX();
?>

</head>
<body>
<?php
    // output a custom loading message
    echo $ajaxHelper->loadingMessage("Waiting on the Server ...");
?>

<div id="updateTarget">I'm an update Target</div>
<?php
    // update the element using ajax
    echo $ajaxHelper->updateElement('updateTarget',array('test','echo_string','Some text to echo'),'replace',true);
?>


<p>Was this page loaded using AJAX: <?php var_dump($ajaxHelper->isAJAX()); ?></p>

Below is the output of HTML_AJAX_Helper::isAJAX() on content loaded from AJAX
<div id="updateTarget2"></div>
<?php
    echo $ajaxHelper->updateElement('updateTarget2','support/isajax.php','replace',true);
?>

</body>
</html>
<?php 
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
?>
