<?php

require_once 'HTML/AJAX/Server.php';

class Foo {
    function bar()
    {
        return 'hello';
    }
}

function foobar()
{
    return 'hello';
}

// start server
$server = new HTML_AJAX_Server();

// register normal function
$callback = 'foobar';
$server->registerPhpCallback($callback);

// register static method
$callback = array('Foo', 'bar');
$server->registerPhpCallback($callback);

// register object method
$foo = new Foo;
$callback = array($foo, 'bar');
$server->registerPhpCallback($callback);
    
// handle the request
if ($server->handleRequest()) {
    exit;
}

?>
<html>
    <head>
        <script type='text/javascript' src="?client=all&amp;stub=all"></script>
        <script type="text/javascript" language="Javascript">
        <!--
        
        HTML_AJAX.defaultServerUrl = 'php_callback.php';
        
        function testCallPhpCallback(cb)
        {
            HTML_AJAX.callPhpCallback(cb, showResult);
        }
        
        function showResult(result)
        {
            alert(result);
        }
        
        var foo = new foo;
        
        //-->
        </script>
    </head>
    <body>
        using HTML_AJAX.callPhpCallback()
        <ul>
            <li><a href="javascript:testCallPhpCallback('foobar')">normal function</a></li>
            <li><a href="javascript:testCallPhpCallback(['Foo', 'bar'])">static method</a></li>
            <li><a href="javascript:testCallPhpCallback([foo, 'bar'])">object method</a></li>
        </ul>
        exported
        <ul>
            <li><a href="javascript:alert(foo.bar())">object method</a></li>
        </ul>
    </body>
</html>