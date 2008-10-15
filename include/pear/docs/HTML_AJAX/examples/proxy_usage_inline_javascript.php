<?php
/**
 * Example of Using HTML_AJAX in proxy operation
 *
 * All AJAX calls are handled by this same file and the proxy js is outputed in a js block directly in this script
 * This is a use case very similar to Sajax (Sajax registers functions not objects)
 *
 * The only needed interaction is creation of a new object from the proxy defintion, all AJAX calls happen transparently from
 *
 * If you want to perform async calls a callback object must be passed to the constructor of the object
 *
 * The client JavaScript library is provided by server.php, you could also copy HTML_AJAX.js (all js files combined) or the seperate js files into your webroot
 * from the PEAR data dir and src them directly
 *
 * This example also registers a custom error handler, without this handler errors float out of the app as an Exception
 *
 * An example debugging event listener is also shown
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */
 
// include the main HTML_AJAX class
include 'HTML/AJAX.php';

// our simple test class
include 'support/test.class.php';



// create an instance of HTML_AJAX
$ajax = new HTML_AJAX();
// register an instance of the test class
$ajax->registerClass(new test());

// handle and ajax call, if one is made die, else continue processing
if ($ajax->handleRequest()) {
	die();
}
?><html>
<head>

<!-- the entire client library can be retreived in one call using the all keyword, or each piece can be requested like below -->
<script type='text/javascript' src="server.php?client=Main"></script>
<script type='text/javascript' src="server.php?client=Dispatcher"></script>
<script type='text/javascript' src="server.php?client=HttpClient"></script>
<script type='text/javascript' src="server.php?client=Request"></script>
<script type='text/javascript' src="server.php?client=JSON"></script>
<script type='text/javascript' src="server.php?client=iframe"></script>
<script type='text/javascript' src="server.php?client=loading"></script>

<script type='text/javascript'>
<?php
	// generate proxy definition javascript for all registered class
	echo $ajax->generateJavascriptClient();
?>

// definition of the callback javascript class, used to handle async requests
function callback() {}
callback.prototype = {
	echo_string: function(result) {
		document.getElementById('target').innerHTML = result;
	},
	slow_echo_string: function(result) {
		document.getElementById('target').innerHTML = result;
	},
	error_test: function(result) {
		document.getElementById('target').innerHTML = result;
	}
}

// function used to clear out the target div
function clearTarget() {
	document.getElementById('target').innerHTML = 'clear';
	document.getElementById('eventLog').innerHTML = 'clear';
	document.getElementById('error').innerHTML = 'clear';
}

// register a custom error handler
HTML_AJAX.onError = function(e) {
	msg = "\n\n";
	for(var i in e) {
		msg += i + ':' + e[i] +"\n";
	}
	document.getElementById('error').innerHTML += msg;
}

// register custom event handlers, but lets keep the old ones so we still have that automatic loading message
var Open = HTML_AJAX.Open;
var Load = HTML_AJAX.Load;

HTML_AJAX.Open = function(request) {
	Open(request);
	document.getElementById('eventLog').innerHTML += "Open: "+request.className+'::'+request.methodName+"\n";
}
HTML_AJAX.Send = function(request) {
	document.getElementById('eventLog').innerHTML += "Send: "+request.className+'::'+request.methodName+"\n";
}
HTML_AJAX.rogress = function(request) {
	document.getElementById('eventLog').innerHTML += "Progress: "+request.className+'::'+request.methodName+"\n";
}
HTML_AJAX.Load = function(request) {
	Load(request);
	document.getElementById('eventLog').innerHTML += "Load: "+request.className+'::'+request.methodName+"\n";
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

// run a sync call, callback class will handle its results
// the purpose of this slow call is to show off the built in loading messaging
function slowAsyncCall() {
	asyncProxy.slow_echo_string("I'm a slow async call");
}
// same thing, notice how it locks up your browser in Firefox you can't switch to another tab
function slowSyncCall() {
	document.getElementById('target').innerHTML = syncProxy.slow_echo_string("I'm a slow sync call");
}

// run a sync call, callback class will handle its results
function serverErrorExample() {
	asyncProxy.error_test("I'm an error generated by trigger error in test.class.php");
}
// same as above, shown for completeness
function serverErrorSyncExample() { 
	document.getElementById('target').innerHTML = syncProxy.error_test("I'm an error generated by trigger error in test.class.php");
}
</script>
<ul>
	<li><a href="javascript:clearTarget()">Clear Target</a></li>
	<li><a href="javascript:syncCall()">Run Sync Echo call</a></li>
	<li><a href="javascript:asyncCall();">Run Async Echo call</a></li>
	<li><a href="javascript:slowAsyncCall();">Run Slow Async Echo call (sleep on server to emulate slow processing)</a></li>
	<li><a href="javascript:slowSyncCall();">Run Slow Sync Echo call (notice how it locks your browser)</a></li>
	<li><a href="javascript:serverErrorExample();">Run a call where an error is generated on the server (Async)</a></li>
	<li><a href="javascript:serverErrorSyncExample();">Run a call where an error is generated on the server (Sync)</a></li>
</ul>
<div style="white-space: pre; padding: 1em; margin: 1em; width: 300px; height: 400px; border: solid 2px black; overflow: auto; float: right;" id="error">Error Target</div>

<div style="white-space: pre; padding: 1em; margin: 1em; width: 500px; height: 300px; border: solid 2px black; overflow: auto;" id="target">Target</div>

<div style="white-space: pre; padding: 1em; margin: 1em; width: 500px; height: 400px; border: solid 2px black; overflow: auto;" id="eventLog">Event Log
</div>
</body>
</html>
