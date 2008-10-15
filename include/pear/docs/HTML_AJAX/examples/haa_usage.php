<?php
/**
 * Example of Using HTML_AJAX_Action see support/testHaa.class.php
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
$ajaxHelper->jsLibraries[] = array('haserializer');
$ajaxHelper->stubs[] = 'testHaa';
?>
<html>
<head>

<?php
    echo $ajaxHelper->setupAJAX();

?>
<script type="text/javascript">
var remote = new testHaa();
</script>

<style type="text/css">
.test {
	color: red;
}
</style>

</head>
<body>

<div id="test">
I'm some test content
</div>

<ul>
	<li><a href="#" onclick="remote.updateClassName()">Update className</a></li>
</ul>

</body>
</html>
