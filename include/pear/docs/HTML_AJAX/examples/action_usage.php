<?php
/**
 * Example of Using HTML_AJAX_Action
 *
 * All the work happens in support/testHaa.class.php
 * This class just attaches some acctions to calls to the server class
 *
 * This just shows basic functionality, what were doing isn't actually useful
 * For an example on how one would actually use HTML_AJAX_Action check out the guestbook example
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2006 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */
?>

<html>
<head>
<script type='text/javascript' src="auto_server.php?client=all"></script>
<script type='text/javascript' src="auto_server.php?stub=testHaa"></script>

<script type='text/javascript'>
// create our remote object so we can use it elsewhere
var remote = new testHaa({}); // pass in an empty hash so were in async mode
</script>
</head>
<body>

<h1>Basic HTML_AJAX_Action Usage</h1>

<ul>
	<li><a href="#" onclick="remote.greenText('target')">Make Target Green</a></li>
	<li><a href="#" onclick="remote.highlight('target')">Highlight Target</a></li>
	<li><a href="#" onclick="remote.duplicate('target','dest')">Duplicate Target</a></li>
</ul>

<div id="target">
I'm some random text.  Ain't I fun.
</div>

<div id="dest">
</div>

</body>
</html>
