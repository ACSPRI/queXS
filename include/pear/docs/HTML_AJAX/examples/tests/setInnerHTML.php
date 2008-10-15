<?php
/**
 * HTML_AJAX_Util.setInnerHTML tests
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
<style type="text/css">
	div {
		padding: 1em;
		margin: 1em;
		border: solid 1px black;
	}
	pre {
		padding: 1em;
		border: dotted 1px black;
	}
</style>
<script type="text/javascript" src="../server.php?client=util"></script>
<script type="text/javascript">
function test(compareto,input,mode) {
	if (compareto == true) {
		compareto = input;
	}
	document.getElementById('input').innerHTML = HTML_AJAX_Util.htmlEscape(input);
	HTML_AJAX_Util.setInnerHTML('target',input,mode);
	document.getElementById('source').innerHTML = HTML_AJAX_Util.htmlEscape(document.getElementById('target').innerHTML);

	document.getElementById('status').innerHTML = 'Test Successful';
	document.getElementById('status').style.color = 'green';
	if (compareto == false) {
		document.getElementById('status').innerHTML = 'Nothing to compare against';
		document.getElementById('status').style.color = 'yellow';
		return;
	}
	if (compareto != document.getElementById('target').innerHTML) {
		document.getElementById('status').innerHTML = 'Test Failed';
		document.getElementById('status').style.color = 'red';
	}
}
function test1() {
	test(true,'<script type="text/javascript">alert("hello world");</'+'script>');
}
function test2() {
	test(true,'<div id="blah">Blah Blah</div><script type="text/javascript">document.getElementById("target").style.color = "blue";</'+'script><div>blah blah</div>');
}
function test3() {
	test(true,'<script type="text/javascript">function blah() { document.getElementById("target").style.color = "blue"; }</'+'script><div>Blah Blah</div><script type="text/javascript">blah();</'+'script><div>blah blah</div>');
}
function test4() {
	test(true,'<script type="text/javascript">function blah() { document.getElementById("target").style.color = "blue"; }</'+'script>'+"\n"+
		'<form><fieldset><legend>Fieldset</legend>'+"\n"+
		'<div>Blah Blah</div>'+"\n"+
		'<script type="text/javascript">blah();</'+'script>'+"\n"+
		'<div>blah blah</div></fieldset></form>');
}
function test5() {
	test(false,'<div>start</div>');
	test('<div>Add</div><div>start</div>','<div>Add</div>','prepend');
}
function test6() {
	test(false,'<div>start</div>');
	test('<div>start</div><div>Add</div>','<div>Add</div>','append');
}
function test7() {
	test(false,'<img src="http://phpdoc.org/images/logo-trans.png" onload="this.style.border = \'solid 10px orange\'">');
}
function test8() {
	test(false,'<script type="text/javascript">document.write("Hello World")</'+'script>');
}
function test9() {
	test(false,'<script type="text/javascript" src="setInnerHTML.js"></'+'script>');
}
function test10() {
	test(false,'<script type="text/javascript">function blah() { document.getElementById("target").style.color = "blue"; }</'+'script>Test');
	blah();
}
function test11() {
	HTML_AJAX_Util.setInnerHTML('target','<p>blah</p>');
	HTML_AJAX_Util.setInnerHTML('target','<p>blah</a>');
}
function test12() {
	test(false,'<div id="test12">Blah</div><script type="text/javascript">document.getElementById("test12").style.color = "orange";</s'+'cript>');
}
function test13() {
         test(false, '<script type="text/javascript">\n<!--\n alert("hello world from comments!");\n//-->\n</' + 'script>');
}
function test14() {
         test(false, '<script type="text/javascript">\n<![CDATA[\n alert("hello world from cdata comments!");\n]]>\n</' + 'script>');
}
</script>
</head>
<body>
<ol>
	<li><a href="javascript:test1()">Basic Test just a script block</a></li>
	<li><a href="javascript:test2()">Script block with content before and after</a></li>
	<li><a href="javascript:test3()">Script content Script content</a></li>
	<li><a href="javascript:test4()">Script form content Script content /form</a></li>
	<li><a href="javascript:test5()">Prepend</a></li>
	<li><a href="javascript:test6()">Append</a></li>
	<li><a href="javascript:test7()">onload</a></li>
	<li><a href="javascript:test8()">document.write</a></li>
	<li><a href="javascript:test9()">load an external js file</a></li>
	<li><a href="javascript:test10()">Create a function and call it in the parent scope</a></li>
	<li><a href="javascript:test11()">Replace/Replace make sure default mode is detected properly</a></li>
	<li><a href="javascript:test12()">Use an element adding in this set latter in the process</a></li>
	<li><a href="javascript:test13()">Script inside comment</a></li>
	<li><a href="javascript:test14()">Script inside cdata comment</a></li>
</ol>

<div id="status"></div>

<h3>Input String</h3>
<pre id="input">
</pre>

<h3>HTML Source Output</h3>
<pre id="source">
</pre>

<h3>Normal Output</h3>
<div id="target">
</div>
