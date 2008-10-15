<?php
/**
 * Example of Using HTML_AJAX in proxy operation
 *
 * All AJAX calls are handled by the xmlserver.php
 *
 * The only needed interaction is creation of a new object from the proxy defintion, all AJAX calls happen transparently from there
 *
 * If you want to perform async calls a callback object must be passed to the constructor of the object
 *
 * @category   HTML
 * @package    AJAX
 * @author     Elizabeth Smith <auroraeosrose@gmail.com>
 * @copyright  2006 Elizabeth Smith
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */
 
?><html>
<head>

<script type='text/javascript' src="xmlserver.php?client=all"></script>
<script type='text/javascript' src="xmlserver.php?stub=testxml"></script>

<script type='text/javascript'>

// function to display xml received from server
function showItems(xml)
{
	var list=xml.getElementsByTagName('item');
	document.getElementById('target').innerHTML = '<p>My Fridge</p>';
	for (var i=0;i<list.length;i++)
	{
		var node = list[i];
		document.getElementById('target').innerHTML += '<p>' + node.firstChild.nodeValue
			+ ' is a ' + node.getAttribute('type') + '</p>';
	}
}

// function to display xml created here
function showMessage(xml)
{
	var list=xml.getElementsByTagName('tag');
	document.getElementById('target').innerHTML = '';
	for (var i=0;i<list.length;i++)
	{
		var node = list[i];
		document.getElementById('target').innerHTML += '<p>' + node.firstChild.nodeValue + '</p>';
	}
}

// definition of the callback javascript class, used to handle async requests
function callback() {}
callback.prototype = {
	createJunk: function(result) {
		showItems(result);
	},
	writeDoc: function(result) {
		dom = HTML_AJAX.grab('test.xml');
		showMessage(dom);
	}
}
// function used to clear out the target div
function clearTarget() {
	document.getElementById('target').innerHTML = 'clear';
}

//create xml document to send back to server
var xmlhello = '<' + '?xml version="1.0"?><root><tag>Hello</tag></root>';
xmlhello = new DOMParser().parseFromString(xmlhello, 'text/xml');

var xmlgoodbye = '<' + '?xml version="1.0"?><root><tag>Goodbye</tag></root>';
xmlgoodbye = new DOMParser().parseFromString(xmlgoodbye, 'text/xml');

</script>
</head>
<body>
<script type="text/javascript">
// create a proxy in sync mode
var syncProxy = new TestXml();
// create a proxy in async mode
var asyncProxy = new TestXml(new callback());

// run a sync call and set its results to the target div
function syncCall() {
	dom = syncProxy.createHealthy();
	showItems(dom);
}
function syncSend(xml) {
	syncProxy.writeDoc(xml);
	dom = HTML_AJAX.grab('test.xml');
	showMessage(dom);
}

// run a sync call, callback class will handle its results
function asyncCall() {
	asyncProxy.createJunk();
}
// run a sync call, callback class will handle its results
function asyncSend(xml) {
	asyncProxy.writeDoc(xml);
}
</script>

<p>HTML_AJAX XML functionality needs the Dom extensions in PHP5 or the DOMXML extension in PHP4.<br>
It looks like you have:<br>
<?php
if (extension_loaded('Dom')) {
	echo 'The Dom extension';
}
else if (extension_loaded('Domxml')) {
	echo 'The Domxml extension';
}
else {
	echo 'No XML DOM support, so you can expect these examples to fail';
}
?>
</p>
<ul>
	<li><a href="javascript:clearTarget()">Clear Target</a></li>
	<li><a href="javascript:syncCall()">Retrieve XmlDom Sync</a></li>
	<li><a href="javascript:asyncCall();">Retrieve XmlDom Async</a></li>
	<li><a href="javascript:syncSend(xmlhello);">Send XmlDom Sync</a></li>
	<li><a href="javascript:asyncSend(xmlgoodbye);">Send XmlDom Async</a></li>
</ul>

<div style="white-space: pre; padding: 1em; margin: 1em; width: 600px; height: 300px; border: solid 2px black; overflow: auto;" id="target">Target</div>
</div>

</body>
</html>
