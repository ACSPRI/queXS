<?php
/**
 * Example of Using HTML_AJAX in proxy operation
 *
 * All AJAX calls are handled by the auto_server.php file, server.php could also be used, the differences between the two are covered in those files
 * This is a use case very similar to JPSpan (JPSpan only has a server.php equivalent)
 *
 * The only needed interaction is creation of a new object from the proxy defintion, all AJAX calls happen transparently from there
 *
 * If you want to perform async calls a callback object must be passed to the constructor of the object
 *
 * The client JavaScript library is provided by auto_server.php, you could also copy HTML_AJAX.js (all js files combined) or the seperate js files into your webroot
 * from the PEAR data dir and src them directly.  You can use multiple includes of the component files or use the all flag to get them all at once.
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

// set a cookie so we always have an example cookie for the test
setcookie('testcookie',"Doesn't taste as good as a peanut butter cookie");
 
?><html>
<head>

<!-- These two calls can be combined into one call if wanted, but its not recomended since it will hurt caching as you might want stubs of multiple classes -->
<script type='text/javascript' src="auto_server.php?client=all"></script>
<!-- Stub is passed the class you want the proxy definition for, you can also use all to get every registered class but that create those auto server
	has to instanciate every class where here only the class used on this page has to be instanciated -->
<script type='text/javascript' src="auto_server.php?stub=test"></script>

<script type='text/javascript'>
// definition of the callback javascript class, used to handle async requests
function callback() {}
callback.prototype = {
	echo_string: function(result) {
		document.getElementById('target').innerHTML = result;
	},
	cookies: function(result) {
		var ret = "";
		for(var i in result) {
			ret += i+':'+result[i]+"\n";
		}
		document.getElementById('target').innerHTML = ret;
	},
	echo_data: function(result) {
		document.getElementById('target').innerHTML = HTML_AJAX_Util.varDump(result);
	},
	unicode_data: function(result) {
		document.getElementById('target').innerHTML = HTML_AJAX_Util.varDump(result);
	},
	dump: function(result) {
		document.getElementById('target').innerHTML = result;
	}
}

// function used to clear out the target div
function clearTarget() {
	document.getElementById('target').innerHTML = 'clear';
}
</script>
</head>
<body>
<script type="text/javascript">
// create a proxy in sync mode
var syncProxy = new test();
// create a proxy in async mode
var asyncProxy = new test(new callback());

// run a sync call and set its results to the target div
function syncCall() {
	document.getElementById('target').innerHTML = syncProxy.echo_string("I'm a sync call");
}

// run a sync call, callback class will handle its results
function asyncCall() {
	asyncProxy.echo_string("I'm a async call");
}

function unicodeTest() {
	//asyncProxy.echo_data({'suggestion': ['Français', 'caractères']});
	asyncProxy.echo_data({"suggestion":["Fran\u00e7ais","caract\u00e8res"]});
}

function unicodeTest2() {
	asyncProxy.unicode_data();
}

function cookieTest() {
	asyncProxy.cookies();
}
function assocTest() {
	asyncProxy.dump({a: 'first var', b: 'second var'});
}
</script>
<ul>
	<li><a href="javascript:clearTarget()">Clear Target</a></li>
	<li><a href="javascript:syncCall()">Run Sync Echo call</a></li>
	<li><a href="javascript:asyncCall();">Run Async Echo call</a></li>
	<li><a href="javascript:unicodeTest();">Check ability to round trip utf-8 JSON data</a></li>
	<li><a href="javascript:unicodeTest2();">Check ability to recieve utf-8 JSON data</a></li>
	<li><a href="javascript:assocTest();">Check ability to decode js hashes into PHP associative arrays using JSON</a></li>
	<li><a href="javascript:cookieTest();">View Cookies</a></li>
</ul>

<div style="white-space: pre; padding: 1em; margin: 1em; width: 600px; height: 300px; border: solid 2px black; overflow: auto;" id="target">Target</div>

<div>
Runing view Cookies should show you these same cookies being returned by the AJAX call<br>
If the lists don't match, try reloading this page, it sets a test cookie, but we can't see it on the PHP side until the client returns it on a test.
<pre>
<?php
	var_dump($_COOKIE);
?>
</pre>
</div>

</body>
</html>
