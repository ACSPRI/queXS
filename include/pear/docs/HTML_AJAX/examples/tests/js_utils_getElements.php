<?php
/**
 * HTML_AJAX_Util.getElements*() examples
 * 
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn josh@bluga.net
 * @copyright  2006 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

?>
<html>
<head>
<title>HTML_AJAX_Util.getElements* examples</title>
	<script type="text/javascript" src="../server.php?client=util"></script>
</head>
<body>
	<div id="t">
	<div id="test_one" class="test">Blah</div>
	<div id="test_two" class="yellow">blah 2</div>
	<div id="three" class="test">Blah 3</div>
	</div>

	<h3>Test HTML</h3>
	<pre id="target0"></pre>


	<h3>getElementsByClassName</h3>
	<div id="target1"></div>

	<h3>getElementsById</h3>
	<div id="target2"></div>
	<script type="text/javascript">
		document.getElementById('target0').innerHTML = HTML_AJAX_Util.htmlEscape(document.getElementById('t').innerHTML);
		var els = HTML_AJAX_Util.getElementsByClassName('test');
		var t = document.getElementById('target1');
		for(var i = 0; i < els.length; i++) {
			t.innerHTML += "Found: "+els[i].innerHTML+"<br>";
		}

		var els = HTML_AJAX_Util.getElementsById('test');
		var t = document.getElementById('target2');
		for(var i = 0; i < els.length; i++) {
			t.innerHTML += "Found: "+els[i].innerHTML+"<br>";
		}
	</script>
</body>
</html>
