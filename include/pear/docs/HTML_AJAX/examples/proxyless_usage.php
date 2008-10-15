<?php
/**
 * Example of Using HTML_AJAX in proxyless operation
 *
 * This is the simplest way to use HTML_AJAX if your just using grab and replace functions you don't even need a server
 * You need every javascript file except JSON which is optional and is only needed if your using that encoding
 *
 * The proxyless api is provided by Main.js
 *
 * There are 4 main methods and 2 properties to the proxyless api, they all exist as static methods on HTML_AJAX
 *	HTML_AJAX.grab(url)
 *	HTML_AJAX.post(url,payload)
 *	HTML_AJAX.replace(id,url) or HTML_AJAX.replace(id,class,method,arg1,arg2,etc)
 *	HTML_AJAX.call(class,method,callback,arg1,arg2,etc)
 *
 *	HTML_AJAX.defaultServerUrl = 'serverurl';
 *	HTML_AJAX.defaultEncoding = 'Null';
 *
 * The api is demonstrated below, server.php is used for call examples and to load the needed js files
 *
 * @category   HTML
 * @package    AJAX
 * @author     Joshua Eichorn <josh@bluga.net>
 * @copyright  2005 Joshua Eichorn
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */
?>
<html>
<head>

<script type='text/javascript' src="server.php?client=Util"></script>
<script type='text/javascript' src="server.php?client=main"></script>
<script type='text/javascript' src="server.php?client=dispatcher"></script>
<script type='text/javascript' src="server.php?client=HttpClient"></script>
<script type='text/javascript' src="server.php?client=Request"></script>
<script type='text/javascript' src="server.php?client=json"></script>
<script type='text/javascript' src="server.php?client=loading"></script>
<script type='text/javascript' src="server.php?client=iframe"></script>
<script type='text/javascript' src="server.php?client=urlserializer"></script>

</head>
<body>
<script type="text/javascript">
function clearTarget() {
	document.getElementById('target').innerHTML = 'clear';
}

// Add an error handler so we get an alert if any errors happen while making an AJAX call
HTML_AJAX.onError = function(e) 
{
	alert(HTML_AJAX_Util.varDump(e));
}


// Grab is the simplest usage of HTML_AJAX you use it to perform a request to a page and get its results back
// It can be used in either Sync mode where it returns directory or with a call back, both methods are shown below
var url = 'README';
function grabSync() {
	document.getElementById('target').innerHTML = HTML_AJAX.grab(url);
}

function grabAsync() {
	HTML_AJAX.grab(url,grabCallback);
}

function grabCallback(result) {
	document.getElementById('target').innerHTML = result;
}

// POST lets you make simple POST requests to a url, if you use a hash {key:value,key2:value,etc} as the payload then the
// data will be automatically be urlencoded, making it look like a form submission to the server
// if the data is a string it will be sent as is
var postUrl = 'support/post_tester.php';
function postSync() {
	document.getElementById('target').innerHTML = HTML_AJAX.post(postUrl,{dog:'bob',cat:'tigger'});
}

function postAsync() {
	HTML_AJAX.post(postUrl,{dog:'bob',cat:'tigger'},grabCallback);
}

function postCallback(result) {
	document.getElementById('target').innerHTML = result;
}


// replace can operate either against a url like grab or against a remote method
// if its going to be used against a remote method defaultServerUrl needs to be set to a url that is exporting the class its trying to call
// note that replace replace always works async

HTML_AJAX.defaultServerUrl = 'server.php';

function replaceUrl() {
	HTML_AJAX.replace('target',url);
}

function replaceFromMethod() {
	HTML_AJAX.replace('target','test','echo_string','Im a method call replacement');
}
function replaceFromMethodMulti() {
	// Null encoding does not support multiple arguments, you have to use JSON for that
	HTML_AJAX.defaultEncoding = 'JSON'; // set encoding to JSON encoding method
	HTML_AJAX.replace('target','test','multiarg','argument1','argument2','argument3');
	HTML_AJAX.defaultEncoding = 'Null'; // return it to default which is Null
}


// call is used to call a method on a remote server
// you need to set HTML_AJAX.defaultServerUrl to use it
// you might also want to set HTML_AJAX.defaultEncoding, options are Null and JSON, the server will autodetect this encoding from your content type
// but the return content type will be based on whatever the servers settings are
// You can use call in either Sync or Async mode depending on if you pass it a callback function

function callSync() {
	HTML_AJAX.defaultEncoding = 'JSON'; // set encoding to JSON encoding method
	document.getElementById('target').innerHTML = HTML_AJAX.call('test','echo_string',false,'Im text that was echoed');
	HTML_AJAX.defaultEncoding = 'Null'; // return it to default which is Null
}

function callAsync() {
	HTML_AJAX.call('test','echo_string',callCallback,'Im text that was echoed Async');
}

function callCallback(result) {
	document.getElementById('target').innerHTML = result;
}

</script>
<ul>
	<li><a href="javascript:clearTarget()">Clear Target</a></li>
	<li><a href="javascript:grabSync()">Run Sync Grab Example</a></li>
	<li><a href="javascript:grabAsync()">Run Async Grab  Example</a></li>
	<li><a href="javascript:postSync()">Run Sync Post Example</a></li>
	<li><a href="javascript:postAsync()">Run Async Post  Example</a></li>
	<li><a href="javascript:replaceUrl()">Replace with content from a url</a></li>
	<li><a href="javascript:replaceFromMethod()">Replace with content from a method call</a></li>
	<li><a href="javascript:replaceFromMethodMulti()">Replace with content from a method call (multiple method arguments)</a></li>
	<li><a href="javascript:callSync()">Sync Call</a></li>
	<li><a href="javascript:callAsync()">ASync Call</a></li>
</ul>

<div style="white-space: pre; padding: 1em; margin: 1em; width: 600px; height: 300px; border: solid 2px black; overflow: auto;" id="target">Target</div>
</body>
</html>
<?php 
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
?>
