<?php
/**
 * Front end for interceptor examples, see support/interceptor.php for the interceptor class that is being used, and interceptorServer.php for how to register one
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

?><html>
<head>

<script type='text/javascript' src="interceptorServer.php?client=all"></script>
<script type='text/javascript' src="interceptorServer.php?stub=all"></script>

<script type='text/javascript'>
// definition of the callback javascript class, used to handle async requests
var callback = {
	test1: function(result) {
		document.getElementById('target').innerHTML = HTML_AJAX_Util.varDump(result);
	},
	test2: function(result) {
		document.getElementById('target').innerHTML = HTML_AJAX_Util.varDump(result);
	},
	test3: function(result) {
		document.getElementById('target').innerHTML = HTML_AJAX_Util.varDump(result);
	}
}

// function used to clear out the target div
function clearTarget() {
	document.getElementById('target').innerHTML = 'clear';
}

HTML_AJAX.onError = function(e) {
	document.getElementById('errors').innerHTML = HTML_AJAX_Util.varDump(e);
}
</script>
</head>
<body>
<script type="text/javascript">
// create a proxy in async mode
var testProxy = new test(callback);
var test2Proxy = new test2({test: function(result) { document.getElementById('target').innerHTML = HTML_AJAX_Util.varDump(result); }});

// run a sync call and set its results to the target div
</script>
<ul>
	<li><a href="javascript:clearTarget()">Clear Target</a></li>
	<li><a href="javascript:testProxy.test1('One')">Run test::test1, matches interceptor for specific method</a></li>
	<li><a href="javascript:testProxy.test2('Two')">Run test::test2, matches interceptor for class</a></li>
	<li><a href="javascript:testProxy.test3('Three')">Run test::test3, matches interceptor for class</a></li>
	<li><a href="javascript:test2Proxy.test('Four')">Run test2::test, matches global interceptor</a></li>
</ul>

<div style="white-space: pre; padding: 1em; margin: 1em; width: 600px; height: 300px; border: solid 2px black; overflow: auto;" id="target">Target</div>

<div style="white-space: pre; padding: 1em; margin: 1em; width: 600px; height: 300px; border: solid 2px black; overflow: auto;" id="errors">Errors</div>

</div>

</body>
</html>
