<html>
<head>
<title>HTML_AJAX 0.5.2 Examples</title>
</head>
<body>
<h1>HTML_AJAX 0.5.2 Examples</h1>
<p>
These are examples showing the basics of using HTML_AJAX
</p>

<p>
These examples show off many of the features of HTML_AJAX, but you'll find them most useful as a learning tool.  
Reading through the commented code as you view the examples in your browser.
</p>

<p>
These examples are available online at: http://bluga.net/projects/HTML_AJAX or in your local PEAR install in the documentation dir.

On most Linux systems the location is /usr/share/pear/docs/HTML_AJAX/examples/

You can find the location of your PEAR documentation dir running
<code>pear config-get doc_dir</code>
</p>

<p>The term proxy in these examples refers to a javascript class that is generated and has functions that map to the eqivalent php class.
These proxy classes work much in the same way as a SOAP proxy class that is generated from wsdl.
</p>

<p>
Front end files for examples, you can actually run these and see some example output
</p>
<ul>
<li><a href='proxyless_usage.php'>proxyless_usage.php</a> - Using HTML_AJAX in standalone mode, possible doesn't require PHP or any backend HTML_AJAX classes</li>
<li><a href='proxy_usage_inline_javascript.php'>proxy_usage_inline_javascript.php</a> - Single file proxy style usage</li>
<li><a href='proxy_usage_server.php'>proxy_usage_server.php</a> - Multi-file proxy usage, either server file could be used with this example</li>
<li><a href='queue_usage.php'>queue_usage.php</a> - An example of using a queue to manage ajax calls, a simple live search example</li>
<li><a href='slow_livesearch.php'>slow_livesearch.php</a> - An example showing how the ordered queue can be used to manage high latency</li>
<li><a href='helper_usage.php'>helper_usage.php</a> - An example showing the basics of the helper api</li>
<li><a href='form.php'>form.php</a> - Basic AJAX form submission example</a></li>
<li><a href='action_usage.php'>action_usage.php</a> - Basic HTML_AJAX_Action usage</a></li>
<li><a href='xml_usage.php'>xml_usage.php</a> - Basic XML serializer usage</a></li>
</ul>

<p>Real Life Examples</p>
<ul>
<li><a href='login/index.php'>login/index.php</a> - An example creating an AJAX driven login</a></li>
<li><a href='review/index.php'>review/index.php</a> - A simple live review system, AJAX form submission and click to edit</a></li>
<li><a href='guestbook/index.php'>guestbook/index.php</a> - A simple guestbook system, uses action system so you never write a line of javascript</a></li>
<li><a href='shoutbox.php'>shoutbox.php</a> - How to use AJAX form submission</a></li>
</ul>

<p>
2 server examples are provided, both provide the same output but the auto_server example has code to help you deal with managing multiple ajax classes
</p>
<ul>
<li>server.php	- Basic server operation, serving ajax calls and client lib requests</li>
<li>auto_server.php	- Advanced server operation, only create php classes as needed</li>
<li><a href='server.php?client=util,main'>server.php?client=util,main</a> - server.php generating a javascript file with the main and util libs in it</li>
<li><a href='auto_server.php?stub=test2'>server.php?stub=test2</a> - auto_server.php generating a javascript file a which contains a generated proxy class for the test2 php class</li>
<li><a href='auto_server.php?stub=all'>server.php?stub=all</a> - auto_server.php generating a javascript file which contains proxies for all the php classes registered with it</li>
</ul>

<p>
Examples files showing howto use HTML_AJAX_Util javascript class
</p>
<ul>
<li><a href='tests/js_utils_vardump.php'>js_utils_vardump.php</a>	- Shows the output of HTML_AJAX_Util.varDump() and compares its against PHP's var_dump
</ul>


<p>
Other Example files:
</p>
<ul>
<li><a href='tests/test_speed.php'>test_speed.php</a>	- A basic setup for measuring the speed of calls</li>
<li><a href='tests/test_priority.php'>test_priority.php</a> - A basic test showing how Priority queue works</li>
<li><a href='tests/serialize.php.examples.php'>serialize.php.examples.php</a>	- Internal tests for the php serialize format serializer</li>
<li><a href='tests/serialize.url.examples.php'>serialize.url.examples.php</a>	- Internal tests for the urlencoded format serializer</li>
<li><a href='tests/setInnerHTML.php'>setInnerHTML.php</a>	- Tests used to verify the operation of HTML_AJAX_Util.setInnerHTML</li>
<li><a href='tests/duplicateJSLib.php'>duplicateJSLib.php</a>	- Tests used to verify that HTML_AJAX_Server is removing duplicate JS libraries from client generation correctly</li>
<li><a href='tests/behaviorSpeed.php'>behaviorSpeed.php</a>	- Tests used to see how fast the JavaScript behavior code runs.</li>
<li><a href='interceptors.php'>interceptors.php</a> - Interceptors test</a></li>
</ul>

<p>
Javascript and Html Examples:
</p>
<ul>
<li><a href="tests/test_behavior.html">test_behavior.html</a> - A short overview of how to use behavior.js.  Behavior uses css selectors to apply javascript behaviors without throwing lots of javascript handlers into your html.</li>
</ul>
</body>
</html>
