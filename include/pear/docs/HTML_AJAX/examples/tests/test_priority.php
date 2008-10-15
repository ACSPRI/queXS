<?php
/**
 * Priority queue test.
 *
 * Makes 10 calls at one priority, then 10 calls at a higher priority.
 *
 * @category   HTML
 * @package    AJAX
 * @author     Arpad Ray <arpad@php.net>
 * @copyright  2005 Arpad Ray
 * @license    http://www.opensource.org/licenses/lgpl-license.php  LGPL
 * @version    Release: 0.5.2
 * @link       http://pear.php.net/package/HTML_AJAX
 */

include 'HTML/AJAX.php';
include '../support/test.class.php';

$ajax = new HTML_AJAX();
$ajax->serializer = "Null";
$ajax->unserializer = "Null";
$ajax->registerClass(new test());

if ($ajax->handleRequest()) {
    die();
}
?><html>
<head>
<script type='text/javascript' src="../server.php?client=all&amp;stub=all"></script>
<script type="text/javascript">

HTML_AJAX.queues['priority'] = new HTML_AJAX_Queue_Priority_Simple(40);

var t = new test({echo_string: function(result){ endCall(result); }});

var time1;
var count = 0;

function priorityTest() {
    document.getElementById('target').innerHTML += "<br><br>";
	count = 0;
	for (var i = 0; i < 10; i++) {
        runLow(i);
	}
    for (var i = 0; i < 10; i++) {
        runHigh(i);
    }
	total = 0;
}
function runLow(i) {
	startCall();
    t.dispatcher.queue = 'priority';
    t.dispatcher.priority = 10;
	return t.echo_string('Not urgent, number ' + i + ' ');
}
function runHigh(i) {
    startCall();
    t.dispatcher.queue = 'priority';
    t.dispatcher.priority = 0;
    return t.echo_string('Urgent, number ' + i + ' ');
}
function startCall() {
	time1 = new Date();
}
function endCall(name) {
    var time = 0;
    var time2 = new Date();
    time = time2.getTime() - time1.getTime();
    
    document.getElementById('target').innerHTML += name + "time: " + time + "<br>";
    if (++count == 20) {
        document.getElementById('target').innerHTML += "Done<br>";
    }
}

</script>
</head>
<body>
<a href="javascript:priorityTest()">Start Priority Test</a>
<div id="target">
</div>
</body>
</html>
